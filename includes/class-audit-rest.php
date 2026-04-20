<?php
/**
 * PW_Audit_REST — REST API контроллер аудита
 * Оркестрирует PW_YouTube_API, PW_Audit_Analyzer, PW_OpenAI_Client
 * Эндпоинты строго по ТЗ §7
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
            'permission_callback' => 'is_user_logged_in',
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
            'permission_callback' => 'is_user_logged_in',
        ]);
 
        register_rest_route( 'payway/v1', '/audit/history', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_history' ],
            'permission_callback' => 'is_user_logged_in',
        ]);
    }

    public function get_nonce() {
        return rest_ensure_response( [
            'success' => true,
            'data'    => [
                'nonce'    => wp_create_nonce( 'wp_rest' ),
                'is_admin' => current_user_can( 'manage_options' ),
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
            // PHP-сигналы могут повысить риск блока 2
            $final_block2_risk = $this->merge_risk( $php_block2_risk, $ai_result['block2_risk'] );
            $final_block3_risk = $ai_result['block3_risk'];
            $final_verdict     = $early_verdict ?? $ai_result['verdict'];
 
            // Если блок 2 = high → вердикт manual (даже если AI дал accept)
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
 
        // Карта risk_block1 (fail→fail, warn→warn, ok→ok)
        $risk_block1 = $ad['block1_status']; // ok | warn | fail
 
        // report_preview — данные для бесплатного экрана
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
        ];
 
        // report_full — полный AI отчёт
        $report_full = array_merge( $report_full_raw, [
            'block1_criteria'  => $ad['block1_criteria'],
            'php_signals'      => $ad['php_signals'],
            'channel_metrics'  => $ad['channel_metrics'],
        ]);
 
        // Сохраняем в БД
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
    // POST /audit/{id}/unlock — три ветки баланса (§7)
    // ─────────────────────────────────────────────────────────
 
public function unlock_report( WP_REST_Request $request ) {
    global $wpdb;
 
    $audit_id = (int) $request->get_param( 'id' );
    $user_id  = get_current_user_id();
    $table    = $wpdb->prefix . 'pw_channel_audits';
 
    // ADMIN BYPASS: администратор может разблокировать любой аудит
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
        return new WP_Error( 'alreadyUnlocked', 'Отчёт уже оплачен', [ 'status' => 400 ] );
    }
 
    $balance = (float) get_user_meta( $user_id, 'payway_withdrawal_balance', true );
 
    // Ветка 1: баланс >= $1 — платное списание
    if ( $balance >= 1 ) {
        $wpdb->query( 'START TRANSACTION' );
        $fresh_balance = (float) $wpdb->get_var(
            "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = {$user_id} AND meta_key = 'payway_withdrawal_balance' FOR UPDATE"
        );
        if ( $fresh_balance < 1 ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_Error( 'insufficientBalance', 'Недостаточно средств', [ 'status' => 402 ] );
        }
        $new_balance = $fresh_balance - 1.00;
        $wpdb->update(
            $wpdb->usermeta,
            [ 'meta_value' => number_format( $new_balance, 2, '.', '' ) ],
            [ 'user_id' => $user_id, 'meta_key' => 'payway_withdrawal_balance' ],
            [ '%s' ], [ '%d', '%s' ]
        );
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$table} SET is_paid = 1, amount_charged = 1.00 WHERE id = %d",
            $audit_id
        ) );
        $wpdb->query( 'COMMIT' );
 
        $audit = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $audit_id ) );
        return rest_ensure_response( $this->normalize_report( $audit, $user_id ) );
    }
 
    // Ветка 2: бесплатный кредит (баланс < $1)
    // Для администраторов разрешаем без проверки кредитов
    if ( ! current_user_can( 'manage_options' ) ) {
        $credit_check = PW_Audit_Credit::check( $user_id );
        if ( ! $credit_check['allowed'] ) {
            $reason = $credit_check['reason'] ?? '';
            if ( $reason === 'daily_limit' ) {
                return new WP_Error(
                    'dailyCreditLimitReached',
                    'Дневной лимит бесплатного отчёта использован. Доступен снова завтра.',
                    [ 'status' => 429 ]
                );
            }
            if ( $reason === 'limit_reached' ) {
                return new WP_Error(
                    'creditLimitReached',
                    'Все 3 бесплатных отчёта использованы. Пополните баланс для продолжения.',
                    [ 'status' => 402 ]
                );
            }
            return new WP_Error(
                'insufficientBalance',
                'Недостаточно средств для разблокировки отчёта.',
                [ 'status' => 402 ]
            );
        }
        // Списываем кредит (инкрементирует payway_audit_free_used + ставит daily transient)
        PW_Audit_Credit::consume( $user_id );
    }
 
    // Помечаем аудит как разблокированный (amount_charged = 0 т.к. реальных денег не списано)
    $wpdb->query( $wpdb->prepare(
        "UPDATE {$table} SET is_paid = 1, amount_charged = 0.00 WHERE id = %d",
        $audit_id
    ) );
 
    $audit    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $audit_id ) );
    $response = (array) $this->normalize_report( $audit, $user_id );
    $response['credit_used'] = true;
    return rest_ensure_response( $response );
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
 
    /**
     * @param object $audit      Строка из БД
     * @param int    $user_id    Текущий пользователь
     * @param bool   $list_mode  true = облегчённый вид для истории
     */
    public function normalize_report( $audit, int $user_id, bool $list_mode = false ) {
        $preview = json_decode( $audit->report_preview ?? '{}', true ) ?: [];
        $full    = json_decode( $audit->report_full   ?? '{}', true ) ?: [];
        $balance = (float) get_user_meta( $user_id, 'payway_withdrawal_balance', true );
 
        // Базовые поля — всегда присутствуют
            //  report  Vue: {summary, admission, demonetization, copyright}
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
            'block1_status'  => $audit->risk_block1,   // ok | warn | fail
            'block2_risk'    => $audit->risk_block2,   // low | medium | high
            'block3_risk'    => $audit->risk_block3,   // low | medium | high
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
 
        // Полный отчёт — только если оплачен (или admin)
        if ( $audit->is_paid || current_user_can( 'manage_options' ) ) {
            $response['full'] = [
                'block1_criteria'          => $full['block1_criteria']            ?? $preview['block1_criteria'] ?? [],
                'block2_signals'           => $full['block2_signals']             ?? [],
                'block3_signals'           => $full['block3_signals']             ?? [],
                'php_signals'              => $full['php_signals']                ?? $preview['php_signals'] ?? [],
                'summary_for_moderator'    => $full['summary_for_moderator']      ?? '',
                'recommendations_for_user' => $full['recommendations_for_user']   ?? [],
                'channel_metrics'          => $full['channel_metrics']            ?? [],
                // Sprint 1: новые поля AI-ответа
                'priority_action'          => $full['priority_action']            ?? '',
                'retry_context'            => $full['retry_context']              ?? '',
                'checklist_moderator'      => $full['checklist_moderator']        ?? [],
                'metric_explanations'      => $full['metric_explanations']        ?? null,
                'content_allowed'          => $full['content_allowed']            ?? [],
                'content_forbidden'        => $full['content_forbidden']          ?? [],
            ];
        } else {
            $response['full'] = null;
        }
 
        return $response;
    }
 
    // ─────────────────────────────────────────────────────────
    // Вспомогательные методы
 
    // ─────────────────────────────────────────────────────────
 
    private function is_credit_available( int $user_id, float $balance ) {
        if ( $balance != 0 ) return false;
        return ! get_transient( 'payway_audit_credit_' . $user_id );
    }
 
    /**
     * Берёт максимальный из двух уровней риска.
     */
    private function merge_risk( string $r1, string $r2 ) {
        $order = [ 'low' => 0, 'medium' => 1, 'high' => 2 ];
        return ( $order[ $r1 ] ?? 0 ) >= ( $order[ $r2 ] ?? 0 ) ? $r1 : $r2;
    }
 
    public function check_audit_owner( WP_REST_Request $request ) {
        return is_user_logged_in();
    }
}