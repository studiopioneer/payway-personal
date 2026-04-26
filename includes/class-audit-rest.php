<?php
/**
 * PW_Audit_REST — REST API контроллер аудита
 * Оркестрирует PW_YouTube_API, PW_Audit_Analyzer, PW_OpenAI_Client
 * Эндпоинты строго по ТЗ §7
 * Sprint v5.0: сохранение и отдача niche_analysis
 * Sprint v5.1: сохранение и отдача aislop_signals, aislop_risk, aislop_summary
 * Sprint v5.2: эндпоинт GET /audit/{id}/competitors — поиск конкурентов + donation gate + 7-day cache
 */
class PW_Audit_REST {
 
    private $yt_api;
    private $analyzer;
    private $openai;
 
    public function __construct() {
        $this->yt_api   = new PW_YouTube_API();
        $this->analyzer = new PW_Audit_Analyzer();
        $this->openai   = new PW_OpenAI_Client();
    }
 
    public function register_routes() {
        register_rest_route( 'payway/v1', '/audit', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'start_audit' ],
            'permission_callback' => [ $this, 'check_audit_owner' ],
        ]);
 
        register_rest_route( 'payway/v1', '/nonce', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_nonce' ],
            'permission_callback' => '__return_true',
        ]);
 
        register_rest_route( 'payway/v1', '/audit/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_audit' ],
            'permission_callback' => [ $this, 'check_audit_owner' ],
        ]);
 
        register_rest_route( 'payway/v1', '/audit/(?P<id>\d+)/unlock', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'unlock_report' ],
            'permission_callback' => [ $this, 'check_audit_owner' ],
        ]);
 
        register_rest_route( 'payway/v1', '/audit/(?P<id>\d+)/status', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_audit' ],
            'permission_callback' => [ $this, 'check_audit_owner' ],
        ]);
 
        register_rest_route( 'payway/v1', '/audit/start', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'start_audit' ],
            'permission_callback' => [ $this, 'check_audit_owner' ],
        ]);
 
        register_rest_route( 'payway/v1', '/audit/history', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_history' ],
            'permission_callback' => [ $this, 'check_audit_owner' ],
        ]);
 
        // v4.9: Донаты
        register_rest_route( 'payway/v1', '/donate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'process_donate' ],
            'permission_callback' => [ $this, 'check_audit_owner' ],
        ]);
 
        // Sprint v5.2: конкуренты в нише
        register_rest_route( 'payway/v1', '/audit/(?P<id>\d+)/competitors', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'get_competitors' ],
            'permission_callback' => [ $this, 'check_audit_owner' ],
        ]);
    }
 
    public function get_nonce() {
        $cookie_keys    = array_keys( $_COOKIE );
        $wp_cookie_name = '';
        $wp_cookie_val  = '';
        foreach ( $_COOKIE as $name => $val ) {
            if ( strpos( $name, 'wordpress_logged_in_' ) === 0 ) {
                $wp_cookie_name = $name;
                $wp_cookie_val  = substr( $val, 0, 20 ) . '...';
                break;
            }
        }
        $cookie_auth_uid = $wp_cookie_name
            ? wp_validate_auth_cookie( $_COOKIE[ $wp_cookie_name ], 'logged_in' )
            : 'no_cookie';
 
        $auth_token = null;
        $uid = get_current_user_id();
        if ( $uid ) {
            $token = wp_generate_password( 32, false );
            set_transient( 'payway_tok_' . md5( $token ), $uid, 2 * HOUR_IN_SECONDS );
            $auth_token = $token;
        }
        return rest_ensure_response( [
            'success' => true,
            'data'    => [
                'nonce'      => wp_create_nonce( 'wp_rest' ),
                'is_admin'   => current_user_can( 'manage_options' ),
                'auth_token' => $auth_token,
                '_debug'     => [
                    'uid'             => $uid,
                    'cookies_count'   => count( $cookie_keys ),
                    'cookie_names'    => $cookie_keys,
                    'wp_cookie_found' => ! empty( $wp_cookie_name ),
                    'wp_cookie_name'  => $wp_cookie_name,
                    'cookie_auth_uid' => $cookie_auth_uid,
                ],
            ],
        ] );
    }
 
    // ─────────────────────────────────────────────────────────
    // POST /audit — запуск аудита
    // ─────────────────────────────────────────────────────────
 
    public function start_audit( WP_REST_Request $request ) {
        global $wpdb;
 
        $user_id     = get_current_user_id();
        $channel_url = sanitize_text_field( $request->get_param( 'channel_url' ) );
 
        if ( empty( $channel_url ) ) {
            return new WP_Error( 'missing_url', 'Укажите URL канала', [ 'status' => 400 ] );
        }
 
        // Rate limiting: ≤ 5 запросов / час (не для админа)
        if ( ! current_user_can( 'manage_options' ) ) {
            $rate_key = 'payway_audit_rate_' . $user_id;
            $rate_cnt = (int) get_transient( $rate_key );
            if ( $rate_cnt >= 5 ) {
                return new WP_Error( 'rate_limit', 'Превышен лимит запросов (5 в час)', [ 'status' => 429 ] );
            }
            set_transient( $rate_key, $rate_cnt + 1, HOUR_IN_SECONDS );
        }
 
        // 1. YouTube API
        $yt_data = $this->yt_api->get_channel_full_data( $channel_url );
        if ( is_wp_error( $yt_data ) ) {
            return new WP_Error( $yt_data->get_error_code(), $yt_data->get_error_message(), [ 'status' => 422 ] );
        }
 
        $channel       = $yt_data['channel'];
        $channel_id    = $channel['id'];
        $channel_title = $channel['snippet']['title'] ?? '';
        $channel_thumb = $channel['snippet']['thumbnails']['default']['url'] ?? '';
 
        // 2. PHP-анализ
        $ad = $this->analyzer->analyze( $yt_data );
 
        // 3. Определяем verdict на основе Блока 1 (до OpenAI)
        $early_verdict = null;
        if ( $ad['block1_status'] === 'fail' ) {
            $early_verdict = 'reject';
        }
 
        // 4. Определяем block2_risk из PHP-сигналов
        $php_block2_risk = $this->analyzer->get_block2_risk_from_signals( $ad['php_signals'] );
 
        // 5. OpenAI анализ
        $ai_result = $this->openai->analyze( $yt_data, $ad );
 
        $ai_ok = ! is_wp_error( $ai_result );
 
        // Итоговые значения
        if ( $ai_ok ) {
            $final_block2_risk = $this->merge_risk( $php_block2_risk, $ai_result['block2_risk'] );
            $final_block3_risk = $ai_result['block3_risk'];
            $final_verdict     = $early_verdict ?? $ai_result['verdict'];
 
            if ( $final_block2_risk === 'high' && $final_verdict === 'accept' ) {
                $final_verdict = 'manual';
            }
 
            $verdict_reason  = $ai_result['verdict_reason'] ?? '';
            $report_full_raw = $ai_result;
        } else {
            $final_block2_risk = $php_block2_risk;
            $final_block3_risk = 'medium';
            $final_verdict     = $early_verdict ?? 'manual';
            $verdict_reason    = 'AI-анализ временно недоступен. Аудит отправлен на ручную проверку.';
            $report_full_raw   = [ 'error' => $ai_result->get_error_message() ];
        }
 
        $risk_block1 = $ad['block1_status'];
 
        $report_preview = [
            'subscriber_count'  => $ad['channel_metrics']['subscriber_count'],
            'view_count'        => $ad['channel_metrics']['view_count'],
            'video_count'       => $ad['channel_metrics']['video_count'],
            'age_months'        => $ad['age_months'],
            'videos_per_month'  => $ad['videos_per_month'],
            'avg_er'            => $ad['avg_er'],
            'country'           => $ad['channel_metrics']['country'],
            'topic_categories'  => $ad['channel_metrics']['topicCategories'],
            'block1_criteria'   => $ad['block1_criteria'],
            'php_signals'       => $ad['php_signals'],
            'php_signals_count' => count( $ad['php_signals'] ),
            'verdict_reason'    => $verdict_reason,
            'aislop_signals'    => $ad['aislop_signals'] ?? [],
            'aislop_risk'       => $ad['aislop_risk'] ?? 'low',
        ];
 
        $report_full = array_merge( $report_full_raw, [
            'block1_criteria' => $ad['block1_criteria'],
            'php_signals'     => $ad['php_signals'],
            'channel_metrics' => $ad['channel_metrics'],
            'niche_analysis'  => $ai_ok ? ( $ai_result['niche_analysis'] ?? null ) : null,
            'aislop_signals'  => $ad['aislop_signals'] ?? [],
            'aislop_risk'     => $ad['aislop_risk'] ?? 'low',
            'aislop_summary'  => $ai_ok ? ( $ai_result['aislop_summary'] ?? null ) : null,
        ]);
 
        $table = $wpdb->prefix . 'pw_channel_audits';
        $wpdb->insert( $table, [
            'user_id'        => $user_id,
            'channel_id'     => $channel_id,
            'channel_url'    => esc_url_raw( $channel_url ),
            'channel_title'  => $channel_title,
            'channel_thumb'  => $channel_thumb,
            'channel_data'   => wp_json_encode( $yt_data ),
            'php_signals'    => wp_json_encode( $ad['php_signals'] ),
            'verdict'        => $final_verdict,
            'risk_block1'    => $risk_block1,
            'risk_block2'    => $final_block2_risk,
            'risk_block3'    => $final_block3_risk,
            'report_preview' => wp_json_encode( $report_preview ),
            'report_full'    => wp_json_encode( $report_full ),
            'is_paid'        => 0,
            'amount_charged' => '0.00',
            'time'           => current_time( 'mysql' ),
        ], [ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%s' ] );
 
        $audit_id = $wpdb->insert_id;
        if ( ! $audit_id ) {
            return new WP_Error( 'db_error', 'Ошибка сохранения результата', [ 'status' => 500 ] );
        }
 
        $audit = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $audit_id ) );
 
        $normalized = (array) $this->normalize_report( $audit, $user_id );
        $normalized['audit_id'] = (int) $audit_id;
        return rest_ensure_response( $normalized );
    }
 
    // ─────────────────────────────────────────────────────────
    // GET /audit/{id}
    // ─────────────────────────────────────────────────────────
 
    public function get_audit( WP_REST_Request $request ) {
        global $wpdb;
 
        $audit_id = (int) $request->get_param( 'id' );
        $user_id  = get_current_user_id();
        $table    = $wpdb->prefix . 'pw_channel_audits';
 
        if ( current_user_can( 'manage_options' ) ) {
            $audit = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $audit_id ) );
        } else {
            $audit = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND user_id = %d", $audit_id, $user_id ) );
        }
 
        if ( ! $audit ) {
            return new WP_Error( 'not_found', 'Аудит не найден', [ 'status' => 404 ] );
        }
 
        return rest_ensure_response( $this->normalize_report( $audit, $user_id ) );
    }
 
    // ─────────────────────────────────────────────────────────
    // POST /audit/{id}/unlock — только бесплатные кредиты (v4.9-free-only)
    // ─────────────────────────────────────────────────────────
 
    public function unlock_report( WP_REST_Request $request ) {
        global $wpdb;
 
        $audit_id = (int) $request->get_param( 'id' );
        $user_id  = get_current_user_id();
        $table    = $wpdb->prefix . 'pw_channel_audits';
 
        if ( current_user_can( 'manage_options' ) ) {
            $audit = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d", $audit_id
            ) );
        } else {
            $audit = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d AND user_id = %d",
                $audit_id, $user_id
            ) );
        }
 
        if ( ! $audit ) {
            return new WP_Error( 'not_found', 'Аудит не найден', [ 'status' => 404 ] );
        }
 
        if ( $audit->is_paid ) {
            return rest_ensure_response( $this->normalize_report( $audit, $user_id ) );
        }
 
        if ( ! current_user_can( 'manage_options' ) ) {
            $credit_check = PW_Audit_Credit::check( $user_id );
            if ( ! $credit_check['allowed'] ) {
                $reason = $credit_check['reason'] ?? '';
                if ( $reason === 'daily_limit' ) {
                    return new WP_Error(
                        'dailyCreditLimitReached',
                        'Вы уже получали бесплатный отчёт сегодня. Следующий доступен завтра.',
                        [ 'status' => 429 ]
                    );
                }
                return new WP_Error(
                    'creditLimitReached',
                    'Все 3 бесплатных отчёта использованы.',
                    [ 'status' => 402 ]
                );
            }
            PW_Audit_Credit::consume( $user_id );
        }
 
        $wpdb->update( $table,
            [ 'is_paid' => 1, 'amount_charged' => 0.00 ],
            [ 'id' => $audit_id ], [ '%d', '%f' ], [ '%d' ]
        );
 
        $audit    = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d", $audit_id
        ) );
        $response = (array) $this->normalize_report( $audit, $user_id );
        $response['credit_used'] = true;
        return rest_ensure_response( $response );
    }
 
    // ─────────────────────────────────────────────────────────
    // POST /donate — списание доната с баланса (v4.9)
    // ─────────────────────────────────────────────────────────
 
    public function process_donate( WP_REST_Request $request ) {
        global $wpdb;
 
        $user_id = get_current_user_id();
        $amount  = floatval( $request->get_param( 'amount' ) );
 
        if ( $amount <= 0 ) {
            return new WP_Error( 'invalid_amount',
                'Сумма должна быть больше нуля', [ 'status' => 400 ] );
        }
 
        $balance = (float) get_user_meta( $user_id, 'payway_withdrawal_balance', true );
        if ( $balance < $amount ) {
            return new WP_Error( 'insufficient_balance',
                'Недостаточно средств на балансе (доступно $'
                . number_format( $balance, 2 ) . ')', [ 'status' => 402 ] );
        }
 
        $wpdb->query( 'START TRANSACTION' );
        $fresh = (float) $wpdb->get_var(
            "SELECT meta_value FROM {$wpdb->usermeta}
             WHERE user_id = {$user_id}
             AND meta_key = 'payway_withdrawal_balance' FOR UPDATE"
        );
        if ( $fresh < $amount ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_Error( 'insufficient_balance',
                'Недостаточно средств', [ 'status' => 402 ] );
        }
        $new_balance = $fresh - $amount;
        $wpdb->update( $wpdb->usermeta,
            [ 'meta_value' => number_format( $new_balance, 2, '.', '' ) ],
            [ 'user_id' => $user_id, 'meta_key' => 'payway_withdrawal_balance' ],
            [ '%s' ], [ '%d', '%s' ]
        );
        $table = $wpdb->prefix . 'payway_donations';
        $wpdb->insert( $table, [
            'user_id'    => $user_id,
            'amount'     => $amount,
            'message'    => sanitize_text_field( $request->get_param( 'message' ) ?? '' ),
            'created_at' => current_time( 'mysql' ),
        ], [ '%d', '%f', '%s', '%s' ] );
        $wpdb->query( 'COMMIT' );
 
        return rest_ensure_response( [
            'success'     => true,
            'amount'      => $amount,
            'new_balance' => $new_balance,
            'message'     => 'Спасибо! Ваш донат принят.',
        ] );
    }
 
    // ─────────────────────────────────────────────────────────
    // GET /audit/history
    // ─────────────────────────────────────────────────────────
 
    public function get_history( WP_REST_Request $request ) {
        global $wpdb;
        $user_id = get_current_user_id();
        $table   = $wpdb->prefix . 'pw_channel_audits';
 
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY time DESC LIMIT 20",
            $user_id
        ));
 
        $result = [];
        foreach ( $rows as $row ) {
            $result[] = $this->normalize_report( $row, $user_id, true );
        }
 
        return rest_ensure_response( [ 'items' => $result, 'total' => count( $result ) ] );
    }
 
    // ─────────────────────────────────────────────────────────
    // normalize_report — формирование ответа для Frontend
    // ─────────────────────────────────────────────────────────
 
    public function normalize_report( $audit, int $user_id, bool $list_mode = false ) {
        $preview = json_decode( $audit->report_preview ?? '{}', true ) ?: [];
        $full    = json_decode( $audit->report_full   ?? '{}', true ) ?: [];
        $balance = (float) get_user_meta( $user_id, 'payway_withdrawal_balance', true );
 
        $_b1_risk = 'low';
        if ( $audit->risk_block1 === 'fail' )     $_b1_risk = 'high';
        elseif ( $audit->risk_block1 === 'warn' ) $_b1_risk = 'medium';
        $_sig_txt = static function( $sigs ) {
            if ( empty( $sigs ) || ! is_array( $sigs ) ) return '';
            return implode( '. ', array_filter( array_map(
                fn( $s ) => trim( ( $s['title'] ?? '' ) . ( ! empty( $s['description'] ) ? ': ' . $s['description'] : '' ) ),
                $sigs ) ) );
        };
        $_b1_det = ! empty( $full['block1_criteria'] ) && is_array( $full['block1_criteria'] )
            ? implode( '. ', array_filter( array_map( fn($c) => $c['description'] ?? $c['title'] ?? '', $full['block1_criteria'] ) ) )
            : '';
        $_report_vue = [
            'summary'        => $full['summary_for_moderator'] ?? ( $full['summary'] ?? '' ),
            'admission'      => $full['admission'] ?? [ 'risk' => $_b1_risk,               'details' => $_b1_det ],
            'demonetization' => $full['demonetization'] ?? [ 'risk' => $full['block2_risk'] ?? 'low', 'details' => $_sig_txt( $full['block2_signals'] ?? [] ) ],
            'copyright'      => $full['copyright']      ?? [ 'risk' => $full['block3_risk'] ?? 'low', 'details' => $_sig_txt( $full['block3_signals'] ?? [] ) ],
        ];
        $response = [
            'id'             => (int) $audit->id,
            'status'         => 'done',
            'channel_url'    => $audit->channel_url,
            'channel_title'  => $audit->channel_title,
            'channel_thumb'  => $audit->channel_thumb,
            'verdict'        => $audit->verdict,
            'verdict_reason' => $preview['verdict_reason'] ?? ( $full['verdict_reason'] ?? '' ),
            'block1_status'  => $audit->risk_block1,
            'block2_risk'    => $audit->risk_block2,
            'block3_risk'    => $audit->risk_block3,
            'is_paid'        => (bool) $audit->is_paid,
            'amount_charged' => (float) $audit->amount_charged,
            'time'           => $audit->time,
            'created_at'     => $audit->time,
            'preview'        => [
                'subscriber_count'  => $preview['subscriber_count']  ?? 0,
                'view_count'        => $preview['view_count']         ?? 0,
                'video_count'       => $preview['video_count']        ?? 0,
                'age_months'        => $preview['age_months']         ?? 0,
                'videos_per_month'  => $preview['videos_per_month']   ?? 0,
                'avg_er'            => $preview['avg_er']             ?? 0,
                'country'           => $preview['country']            ?? '',
                'topic_categories'  => $preview['topic_categories']   ?? [],
                'php_signals'       => $preview['php_signals']        ?? [],
                'php_signals_count' => $preview['php_signals_count']  ?? 0,
                'block1_criteria'   => $preview['block1_criteria']    ?? [],
                'aislop_signals'    => $preview['aislop_signals']    ?? $full['aislop_signals'] ?? [],
                'aislop_risk'       => $preview['aislop_risk']       ?? $full['aislop_risk']    ?? 'low',
            ],
            'unlock_info'    => [
                'balance'          => $balance,
                'credit_available' => PW_Audit_Credit::check( get_current_user_id() )['allowed'],
                'credit_status'    => PW_Audit_Credit::get_status( get_current_user_id() ),
            ],
        ];
 
        if ( $list_mode ) {
            return $response;
        }
 
        if ( $audit->is_paid || current_user_can( 'manage_options' ) ) {
            $response['full'] = [
                'block1_criteria'          => $full['block1_criteria']            ?? $preview['block1_criteria'] ?? [],
                'block2_signals'           => $full['block2_signals']             ?? [],
                'block3_signals'           => $full['block3_signals']             ?? [],
                'php_signals'              => $full['php_signals']                ?? $preview['php_signals'] ?? [],
                'summary_for_moderator'    => $full['summary_for_moderator']      ?? '',
                'recommendations_for_user' => $full['recommendations_for_user']   ?? [],
                'channel_metrics'          => $full['channel_metrics']            ?? [],
                'priority_action'          => $full['priority_action']            ?? '',
                'retry_context'            => $full['retry_context']              ?? '',
                'checklist_moderator'      => $full['checklist_moderator']        ?? [],
                'metric_explanations'      => $full['metric_explanations']        ?? null,
                'content_allowed'          => $full['content_allowed']            ?? [],
                'content_forbidden'        => $full['content_forbidden']          ?? [],
                'niche_analysis'           => $full['niche_analysis']             ?? null,
                'aislop_signals'           => $full['aislop_signals']             ?? [],
                'aislop_risk'              => $full['aislop_risk']                ?? 'low',
                'aislop_summary'           => $full['aislop_summary']             ?? null,
            ];
        } else {
            $response['full'] = null;
        }
 
        return $response;
    }
 
 
    // ─────────────────────────────────────────────────────────
    // POST /audit/{id}/competitors — конкуренты в нише (v5.2)
    // ─────────────────────────────────────────────────────────
 
    public function get_competitors( WP_REST_Request $request ) {
        global $wpdb;
 
        $audit_id = (int) $request->get_param( 'id' );
        $user_id  = get_current_user_id();
        $table    = $wpdb->prefix . 'pw_channel_audits';
 
        // Проверяем аудит
        if ( current_user_can( 'manage_options' ) ) {
            $audit = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d", $audit_id
            ) );
        } else {
            $audit = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d AND user_id = %d",
                $audit_id, $user_id
            ) );
        }
 
        if ( ! $audit ) {
            return new WP_Error( 'not_found', 'Аудит не найден', [ 'status' => 404 ] );
        }
 
        // Кеш — 7 дней
        $cache_key = 'pw_competitors_' . $audit_id;
        $cached    = get_option( $cache_key );
        if ( ! empty( $cached ) && is_array( $cached ) ) {
            return rest_ensure_response( [
                'success'     => true,
                'competitors' => $cached,
                'from_cache'  => true,
            ] );
        }
 
        // Donation gate: донат ПОСЛЕ создания аудита
        if ( ! current_user_can( 'manage_options' ) ) {
            $donations_table = $wpdb->prefix . 'payway_donations';
            $donation = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$donations_table}
                 WHERE user_id = %d AND created_at > %s
                 ORDER BY created_at DESC LIMIT 1",
                $user_id,
                $audit->time
            ) );
 
            if ( ! $donation ) {
                return new WP_Error(
                    'donation_required',
                    'Для просмотра конкурентов необходим донат после запуска аудита.',
                    [ 'status' => 402 ]
                );
            }
        }
 
        // Получаем topic_categories и country из report_preview
        $preview          = json_decode( $audit->report_preview ?? '{}', true ) ?: [];
        $topic_categories = $preview['topic_categories'] ?? [];
        $country          = $preview['country'] ?? '';
 
        // Собственный channel_id аудита — исключаем из конкурентов
        $own_channel_id = $audit->channel_id ?? '';
 
        // Строим поисковый запрос
        $niche_query = $this->build_niche_query( $topic_categories, $country );
 
        // search.list: 8 каналов (100 квот)
        $search_results = $this->yt_api->search_channels( $niche_query, 8 );
        if ( is_wp_error( $search_results ) ) {
            return new WP_Error(
                'yt_search_failed',
                'YouTube поиск недоступен: ' . $search_results->get_error_message(),
                [ 'status' => 502 ]
            );
        }
 
        // Собираем channel_ids, фильтруем свой канал
        $channel_ids = array_values( array_filter(
            array_map( fn( $r ) => $r['channelId'] ?? '', $search_results ),
            fn( $id ) => $id && $id !== $own_channel_id
        ) );
        $channel_ids = array_slice( $channel_ids, 0, 7 ); // берём с запасом
 
        if ( empty( $channel_ids ) ) {
            return new WP_Error( 'no_competitors', 'Конкуренты не найдены для данной ниши.', [ 'status' => 404 ] );
        }
 
        // channels.list: обогащаем данные (1 квота для всего массива)
        $channels_data = $this->yt_api->get_channels_data( $channel_ids );
        if ( is_wp_error( $channels_data ) ) {
            return new WP_Error(
                'yt_channels_failed',
                'Ошибка получения данных каналов: ' . $channels_data->get_error_message(),
                [ 'status' => 502 ]
            );
        }
 
        // Форматируем и берём первые 5
        $competitors = array_slice(
            array_map( [ $this, 'format_competitor' ], $channels_data ),
            0, 5
        );
 
        // Сохраняем в кеш на 7 дней
        update_option( $cache_key, $competitors, false );
        wp_schedule_single_event(
            time() + 7 * DAY_IN_SECONDS,
            'pw_delete_competitors_cache',
            [ $cache_key ]
        );
 
        return rest_ensure_response( [
            'success'     => true,
            'competitors' => $competitors,
            'from_cache'  => false,
        ] );
    }
 
    /**
     * Строит поисковый запрос для ниши из topic_categories и country
     */
    private function build_niche_query( array $topics, string $country ): string {
        $niches = [];
        foreach ( $topics as $url ) {
            $parts = explode( '/', rtrim( $url, '/' ) );
            $niche = str_replace( '_', ' ', end( $parts ) );
            if ( $niche ) $niches[] = $niche;
        }
        $query = implode( ' ', array_slice( $niches, 0, 2 ) );
        if ( in_array( $country, [ 'RU', 'BY', 'KZ', 'UA' ], true ) ) {
            $query .= ' канал';
        }
        return trim( $query ) ?: 'YouTube channel';
    }
 
    /**
     * Форматирует данные одного канала для ответа
     */
    private function format_competitor( array $channel ): array {
        $stats   = $channel['statistics'] ?? [];
        $snippet = $channel['snippet']    ?? [];
        $subs    = (int) ( $stats['subscriberCount'] ?? 0 );
        $views   = (int) ( $stats['viewCount']        ?? 0 );
        $handle  = $snippet['customUrl'] ?? '';
        return [
            'channel_id'       => $channel['id']     ?? '',
            'title'            => $snippet['title']   ?? '',
            'handle'           => $handle,
            'thumb'            => $snippet['thumbnails']['default']['url'] ?? '',
            'subscriber_count' => $subs,
            'subscriber_fmt'   => $this->format_number( $subs ),
            'view_count'       => $views,
            'view_count_fmt'   => $this->format_number( $views ),
            'video_count'      => (int) ( $stats['videoCount'] ?? 0 ),
            'country'          => $snippet['country'] ?? '',
            'url'              => 'https://youtube.com/' . ( $handle ?: 'channel/' . ( $channel['id'] ?? '' ) ),
        ];
    }
 
    /**
     * Форматирует число в читаемый вид: 1250000 → 1.2M, 45000 → 45k
     */
    private function format_number( int $n ): string {
        if ( $n >= 1_000_000 ) return round( $n / 1_000_000, 1 ) . 'M';
        if ( $n >= 1_000 )     return round( $n / 1_000 )       . 'k';
        return (string) $n;
    }
 
    // ─────────────────────────────────────────────────────────
    // Вспомогательные методы
    // ─────────────────────────────────────────────────────────
 
    private function is_credit_available( int $user_id, float $balance ) {
        if ( $balance != 0 ) return false;
        return ! get_transient( 'payway_audit_credit_' . $user_id );
    }
 
    private function merge_risk( string $r1, string $r2 ) {
        $order = [ 'low' => 0, 'medium' => 1, 'high' => 2 ];
        return ( $order[ $r1 ] ?? 0 ) >= ( $order[ $r2 ] ?? 0 ) ? $r1 : $r2;
    }
 
    public function check_audit_owner( WP_REST_Request $request ) {
        if ( is_user_logged_in() ) return true;
 
        foreach ( $_COOKIE as $name => $val ) {
            if ( strpos( $name, 'wordpress_logged_in_' ) === 0 ) {
                $uid = wp_validate_auth_cookie( $val, 'logged_in' );
                if ( $uid ) { wp_set_current_user( $uid ); return true; }
            }
        }
 
        $token = $request->get_header( 'X-Payway-Token' );
        if ( $token ) {
            $uid = get_transient( 'payway_tok_' . md5( $token ) );
            if ( $uid ) { wp_set_current_user( (int) $uid ); return true; }
        }
 
        error_log( 'PW_Audit FAILED. URI=' . ( $_SERVER['REQUEST_URI'] ?? '' )
            . ' Cookies=' . implode( ',', array_keys( $_COOKIE ) )
            . ' HasToken=' . ( ! empty( $token ) ? 'yes' : 'no' )
            . ' UID=' . get_current_user_id() );
        return false;
    }
}
