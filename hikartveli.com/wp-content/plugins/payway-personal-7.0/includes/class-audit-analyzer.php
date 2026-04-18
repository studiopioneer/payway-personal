<?php
/**
 * PW_Audit_Analyzer — вычисление PHP-метрик и reused-сигналов
 * Строго по ТЗ §5
 */
class PW_Audit_Analyzer {

    /**
     * Главный метод: вычисляет всё на основе данных YouTube API.
     *
     * @param array $yt_data  ['channel' => [...], 'videos' => [...]]
     * @return array {
     *   age_months, videos_per_month, avg_er,
     *   block1_criteria, block1_status,
     *   php_signals,
     *   channel_metrics
     * }
     */
    public function analyze( array $yt_data ) {
        $channel = $yt_data['channel'];
        $videos  = $yt_data['videos'];

        // --- Базовые метрики ---
        $age_months      = $this->calc_age_months( $channel['snippet']['publishedAt'] ?? '' );
        $subscriber_count = (int) ( $channel['statistics']['subscriberCount'] ?? 0 );
        $video_count      = (int) ( $channel['statistics']['videoCount'] ?? 0 );
        $view_count       = (int) ( $channel['statistics']['viewCount'] ?? 0 );

        $videos_per_month = $this->calc_videos_per_month( $videos, 3 );
        $avg_er           = $this->calc_avg_er( $videos );
        $has_reg_gap      = $this->has_regularity_gap( $videos, 60 );

        // --- Блок 1: PHP-критерии допуска (§5.1) ---
        $block1 = $this->eval_block1([
            'age_months'        => $age_months,
            'videos_per_month'  => $videos_per_month,
            'video_count'       => $video_count,
            'subscriber_count'  => $subscriber_count,
            'madeForKids'       => $channel['status']['madeForKids'] ?? false,
            'longUploadsStatus' => $channel['status']['longUploadsStatus'] ?? '',
            'hiddenSubscribers' => $channel['statistics']['hiddenSubscriberCount'] ?? false,
            'has_reg_gap'       => $has_reg_gap,
            'topic_categories'  => $channel['topicDetails']['topicCategories'] ?? [],
        ]);

        // --- Блок 2: PHP-сигналы reused / mass-produced (§5.2.1) ---
        $php_signals = $this->eval_reused_signals( $videos, $videos_per_month, $avg_er, $subscriber_count, $channel );

        return [
            'age_months'       => $age_months,
            'videos_per_month' => $videos_per_month,
            'avg_er'           => $avg_er,
            'block1_criteria'  => $block1['criteria'],
            'block1_status'    => $block1['status'],   // 'ok' | 'warn' | 'fail'
            'php_signals'      => $php_signals,
            'channel_metrics'  => [
                'subscriber_count'  => $subscriber_count,
                'view_count'        => $view_count,
                'video_count'       => $video_count,
                'age_months'        => $age_months,
                'videos_per_month'  => $videos_per_month,
                'avg_er'            => $avg_er,
                'madeForKids'       => $channel['status']['madeForKids'] ?? false,
                'longUploadsStatus' => $channel['status']['longUploadsStatus'] ?? '',
                'topicCategories'   => $channel['topicDetails']['topicCategories'] ?? [],
                'country'           => $channel['snippet']['country'] ?? '',
                'customUrl'         => $channel['snippet']['customUrl'] ?? '',
                'title'             => $channel['snippet']['title'] ?? '',
                'publishedAt'       => $channel['snippet']['publishedAt'] ?? '',
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────
    // БЛОК 1: критерии допуска
    // ─────────────────────────────────────────────────────────

    private function eval_block1( array $p ) {
        $criteria = [];
        $has_fail = false;
        $has_warn = false;

        // 1. Возраст ≥ 6 месяцев
        if ( $p['age_months'] >= 6 ) {
            $status = 'ok';
            $detail = "{$p['age_months']} мес.";
        } else {
            $status   = 'fail';
            $has_fail = true;
            $detail   = "Только {$p['age_months']} мес. (требуется ≥ 6)";
        }
        $criteria[] = [ 'name' => 'Возраст ≥ 6 месяцев', 'status' => $status, 'detail' => $detail ];

        // 2. Регулярность (нет пауз > 60 дней за 3 мес)
        if ( ! $p['has_reg_gap'] ) {
            $criteria[] = [ 'name' => 'Регулярные публикации', 'status' => 'ok', 'detail' => round( $p['videos_per_month'], 1 ) . ' видео/мес' ];
        } else {
            $has_fail   = true;
            $criteria[] = [ 'name' => 'Регулярные публикации', 'status' => 'fail', 'detail' => 'Обнаружена пауза > 60 дней' ];
        }

        // 3. Не детский
        if ( ! $p['madeForKids'] ) {
            $criteria[] = [ 'name' => 'Не «Сделано для детей»', 'status' => 'ok', 'detail' => 'madeForKids = false' ];
        } else {
            $has_fail   = true;
            $criteria[] = [ 'name' => 'Не «Сделано для детей»', 'status' => 'fail', 'detail' => 'Канал помечен как детский' ];
        }

        // 4. Минимум 5 видео
        if ( $p['video_count'] >= 5 ) {
            $criteria[] = [ 'name' => 'Минимум 5 видео', 'status' => 'ok', 'detail' => "{$p['video_count']} видео" ];
        } else {
            $has_fail   = true;
            $criteria[] = [ 'name' => 'Минимум 5 видео', 'status' => 'fail', 'detail' => "Только {$p['video_count']} публичных видео" ];
        }

        // 5. Верификация (longUploadsStatus)
        $lus = strtolower( $p['longUploadsStatus'] );
        if ( $lus === 'allowed' ) {
            $criteria[] = [ 'name' => 'Верификация канала', 'status' => 'ok', 'detail' => 'longUploadsStatus = allowed' ];
        } elseif ( $lus === 'eligible' ) {
            $has_warn   = true;
            $criteria[] = [ 'name' => 'Верификация канала', 'status' => 'warn', 'detail' => 'longUploadsStatus = eligible (частичная верификация)' ];
        } else {
            $has_warn   = true;
            $criteria[] = [ 'name' => 'Верификация канала', 'status' => 'warn', 'detail' => 'longUploadsStatus = disallowed' ];
        }

        // 6. Скрытые подписчики
        if ( ! $p['hiddenSubscribers'] ) {
            $criteria[] = [ 'name' => 'Открытое число подписчиков', 'status' => 'ok', 'detail' => 'Подписчики публичны' ];
        } else {
            $has_warn   = true;
            $criteria[] = [ 'name' => 'Открытое число подписчиков', 'status' => 'warn', 'detail' => 'Подписчики скрыты' ];
        }

        // Итоговый статус блока 1
        if ( $has_fail ) {
            $block_status = 'fail';
        } elseif ( $has_warn ) {
            $block_status = 'warn';
        } else {
            $block_status = 'ok';
        }

        return [ 'criteria' => $criteria, 'status' => $block_status ];
    }

    // ─────────────────────────────────────────────────────────
    // БЛОК 2: PHP-сигналы reused / mass-produced (§5.2.1)
    // ─────────────────────────────────────────────────────────

    private function eval_reused_signals( array $videos, float $vpm, float $avg_er, int $subs, array $channel ) {
        $signals = [];
        if ( empty( $videos ) ) return $signals;

        $total = count( $videos );

        // 1. Шаблонные названия (≥40% видео с одинаковой структурой)
        $pattern_count = $this->count_template_titles( $videos );
        $pattern_ratio = $total > 0 ? $pattern_count / $total : 0;
        if ( $pattern_ratio >= 0.4 ) {
            $pct       = round( $pattern_ratio * 100 );
            $signals[] = [
                'type'   => 'template_titles',
                'level'  => 'high',
                'title'  => 'Шаблонные названия видео',
                'detail' => "{$pattern_count} из {$total} видео ({$pct}%) используют одинаковый паттерн названия",
            ];
        }

        // 2. Одинаковая длительность (≥60% в диапазоне ±30 сек)
        $duration_result = $this->check_uniform_duration( $videos );
        if ( $duration_result['ratio'] >= 0.6 ) {
            $pct       = round( $duration_result['ratio'] * 100 );
            $min_str   = gmdate( 'H:i:s', $duration_result['median'] );
            $signals[] = [
                'type'   => 'uniform_duration',
                'level'  => 'high',
                'title'  => 'Одинаковая длительность видео',
                'detail' => "{$pct}% видео имеют длительность ~{$min_str} (±30 сек) — признак конвейерного производства",
            ];
        }

        // 3. Высокая частота + низкий ER (>20 вид/мес И ER <1% при <100k подписчиков)
        if ( $vpm > 20 && $avg_er < 1.0 && $subs < 100000 ) {
            $signals[] = [
                'type'   => 'high_freq_low_er',
                'level'  => 'high',
                'title'  => 'Аномально высокая частота при низком ER',
                'detail' => round( $vpm, 1 ) . ' видео/мес при среднем ER ' . round( $avg_er, 2 ) . '% — признак массового производства контента',
            ];
        }

        // 4. Ключевые слова reused в тегах/названиях
        $kw_signal = $this->check_reused_keywords( $videos );
        if ( $kw_signal ) {
            $signals[] = $kw_signal;
        }

        // 5. Новости/дайджесты + высокая частота
        $topics = implode( ' ', $channel['topicDetails']['topicCategories'] ?? [] );
        if ( stripos( $topics, 'News' ) !== false && $vpm > 10 ) {
            $signals[] = [
                'type'   => 'news_high_freq',
                'level'  => 'high',
                'title'  => 'Новостной контент с высокой частотой',
                'detail' => 'Категория канала содержит "News" при частоте ' . round( $vpm, 1 ) . ' видео/мес',
            ];
        }

        // 6. Умеренная частота (15–20 без прочих сигналов)
        if ( $vpm >= 15 && $vpm <= 20 && empty( $signals ) ) {
            $signals[] = [
                'type'   => 'moderate_freq',
                'level'  => 'medium',
                'title'  => 'Умеренно высокая частота публикаций',
                'detail' => round( $vpm, 1 ) . ' видео/мес — пограничный показатель, требует ручной проверки контента',
            ];
        }

        return $signals;
    }

    /**
     * Считает видео с шаблонными названиями.
     * Паттерны: «ТОП-N», «[Что-то] за [время]», «Как [глагол]», повторяющийся префикс/суффикс.
     */
    private function count_template_titles( array $videos ) {
        $titles = array_column( $videos, 'title' );

        // Паттерны явного шаблона
        $patterns = [
            '/^ТОП[-–\s]?\d+/ui',
            '/^TOP[-–\s]?\d+/ui',
            '/\bза \d+\s*(минут|час|секунд|ден|нед|мес)/ui',
            '/\bin \d+\s*(min|sec|hour|day|week)/ui',
            '/^Как\s+\w+\s+\w+/ui',
            '/^How to\s+/ui',
            '/\bподборка\b/ui',
            '/\bкомпиляция\b/ui',
            '/\bнарезка\b/ui',
            '/\breaction|реакция\b/ui',
            '/\bфакты о\b/ui',
            '/\binteresting facts\b/ui',
        ];

        $count = 0;
        foreach ( $titles as $title ) {
            foreach ( $patterns as $pattern ) {
                if ( preg_match( $pattern, $title ) ) {
                    $count++;
                    break;
                }
            }
        }

        // Дополнительно: если >50% названий начинаются с одинакового слова/фразы
        if ( count( $titles ) >= 5 ) {
            $first_words = [];
            foreach ( $titles as $t ) {
                $w = mb_strtolower( preg_replace( '/[^\p{L}]/u', ' ', mb_substr( $t, 0, 20 ) ) );
                $first_words[] = trim( preg_replace( '/\s+/', ' ', $w ) );
            }
            $groups = [];
            foreach ( $first_words as $w ) {
                $prefix = mb_substr( $w, 0, 8 );
                $groups[ $prefix ] = ( $groups[ $prefix ] ?? 0 ) + 1;
            }
            arsort( $groups );
            $top_count = reset( $groups );
            if ( $top_count / count( $titles ) >= 0.5 ) {
                $count = max( $count, $top_count );
            }
        }

        return min( $count, count( $titles ) );
    }

    /**
     * Проверяет однородность длительности (±30 сек вокруг медианы).
     */
    private function check_uniform_duration( array $videos ) {
        $durations = array_column( $videos, 'duration_sec' );
        $durations  = array_filter( $durations, fn( $d ) => $d > 0 );
        if ( count( $durations ) < 3 ) return [ 'ratio' => 0, 'median' => 0 ];

        sort( $durations );
        $mid    = (int) floor( count( $durations ) / 2 );
        $median = $durations[ $mid ];

        $in_range = array_filter( $durations, fn( $d ) => abs( $d - $median ) <= 30 );
        $ratio    = count( $in_range ) / count( $durations );

        return [ 'ratio' => $ratio, 'median' => $median ];
    }

    /**
     * Ищет reused-ключевые слова в тегах и названиях (§5.2.1 п.4).
     */
    private function check_reused_keywords( array $videos ) {
        $keywords = [
            'подборка', 'нарезка', 'compilation', 'реакция', 'reaction',
            'перезалив', 'reupload', 'ai озвучка', 'ai voice', 'нейросеть',
            'нейросети', 'artificial intelligence', 'shorts compilation',
            'tiktok compilation', 'reels', 'лучшие моменты', 'топ моментов',
            'приколы', 'мемы', 'memes', 'смешное', 'смешные',
        ];

        $found = [];
        foreach ( $videos as $v ) {
            $text = mb_strtolower( $v['title'] );
            foreach ( ( $v['tags'] ?? [] ) as $tag ) {
                $text .= ' ' . mb_strtolower( $tag );
            }
            foreach ( $keywords as $kw ) {
                if ( strpos( $text, $kw ) !== false ) {
                    $found[ $kw ] = ( $found[ $kw ] ?? 0 ) + 1;
                }
            }
        }

        if ( empty( $found ) ) return null;

        arsort( $found );
        $top   = array_slice( $found, 0, 3, true );
        $words = implode( ', ', array_map( fn( $k, $v ) => "«{$k}» ({$v} видео)", array_keys( $top ), $top ) );

        return [
            'type'   => 'reused_keywords',
            'level'  => 'high',
            'title'  => 'Ключевые слова reused-контента',
            'detail' => "В названиях/тегах найдены: {$words}",
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Вспомогательные метрики
    // ─────────────────────────────────────────────────────────

    public function calc_age_months( $published_at ) {
        if ( empty( $published_at ) ) return 0;
        $created = strtotime( $published_at );
        if ( ! $created ) return 0;
        $diff = ( time() - $created ) / ( 30.44 * 24 * 3600 );
        return max( 0, round( $diff, 1 ) );
    }

    /**
     * Видео в месяц за последние N месяцев (только публичные видео из $videos).
     */
    public function calc_videos_per_month( array $videos, int $months = 3 ) {
        if ( empty( $videos ) ) return 0;
        $cutoff = strtotime( "-{$months} months" );
        $count  = 0;
        foreach ( $videos as $v ) {
            $pub = strtotime( $v['publishedAt'] ?? '' );
            if ( $pub && $pub >= $cutoff ) {
                $count++;
            }
        }
        return round( $count / $months, 1 );
    }

    /**
     * Средний ER = (likes / views) * 100 по последним видео с >0 просмотров.
     */
    public function calc_avg_er( array $videos ) {
        $ers = [];
        foreach ( $videos as $v ) {
            if ( $v['viewCount'] > 0 ) {
                $ers[] = ( $v['likeCount'] / $v['viewCount'] ) * 100;
            }
        }
        if ( empty( $ers ) ) return 0;
        return round( array_sum( $ers ) / count( $ers ), 2 );
    }

    /**
     * Проверяет наличие паузы > $days_threshold дней среди последних видео.
     */
    public function has_regularity_gap( array $videos, int $days_threshold = 60 ) {
        if ( count( $videos ) < 2 ) return false;
        $dates = array_map( fn( $v ) => strtotime( $v['publishedAt'] ?? 0 ), $videos );
        $dates = array_filter( $dates );
        sort( $dates );

        $cutoff = strtotime( '-3 months' );
        $recent = array_filter( $dates, fn( $d ) => $d >= $cutoff );
        if ( count( $recent ) < 2 ) return false;

        $recent = array_values( $recent );
        for ( $i = 1; $i < count( $recent ); $i++ ) {
            $gap_days = ( $recent[ $i ] - $recent[ $i - 1 ] ) / 86400;
            if ( $gap_days > $days_threshold ) return true;
        }
        return false;
    }

    /**
     * Определяет risk_block2 по количеству и уровню PHP-сигналов.
     * По ТЗ §5.2.1: 2+ HIGH → 'high'; 1 HIGH → 'medium'; 0 HIGH → 'low'
     */
    public function get_block2_risk_from_signals( array $php_signals ) {
        $high_count = 0;
        foreach ( $php_signals as $s ) {
            if ( ( $s['level'] ?? '' ) === 'high' ) {
                $high_count++;
            }
        }
        if ( $high_count >= 2 ) return 'high';
        if ( $high_count >= 1 ) return 'medium';
        return 'low';
    }
}
