<?php
/**
 * PW_Audit_Analyzer — вычисление PHP-метрик и reused-сигналов
 * Строго по ТЗ §5
 * Sprint v5.1: добавлен eval_aislop_signals() — 8 сигналов AI Slop
 */
class PW_Audit_Analyzer {
 
    public function analyze( array $yt_data ) {
        $channel = $yt_data['channel'];
        $videos  = $yt_data['videos'];
 
        $age_months       = $this->calc_age_months( $channel['snippet']['publishedAt'] ?? '' );
        $subscriber_count = (int) ( $channel['statistics']['subscriberCount'] ?? 0 );
        $video_count      = (int) ( $channel['statistics']['videoCount'] ?? 0 );
        $view_count       = (int) ( $channel['statistics']['viewCount'] ?? 0 );
 
        $videos_per_month = $this->calc_videos_per_month( $videos, 3 );
        $avg_er           = $this->calc_avg_er( $videos );
        $has_reg_gap      = $this->has_regularity_gap( $videos, 60 );
 
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
 
        $php_signals = $this->eval_reused_signals( $videos, $videos_per_month, $avg_er, $subscriber_count, $channel );
        $videos_list = $this->prepare_videos_list( $videos, $php_signals );
 
        // Sprint v5.1: AI Slop signals
        $aislop = $this->eval_aislop_signals( $yt_data, [
            'videos'           => $videos,
            'channel'          => $channel,
            'videos_per_month' => $videos_per_month,
            'avg_er'           => $avg_er,
            'subscriber_count' => $subscriber_count,
        ] );
 
        return [
            'age_months'       => $age_months,
            'videos_per_month' => $videos_per_month,
            'avg_er'           => $avg_er,
            'block1_criteria'  => $block1['criteria'],
            'block1_status'    => $block1['status'],
            'php_signals'      => $php_signals,
            'aislop_signals'   => $aislop['signals'],
            'aislop_risk'      => $aislop['risk'],
            'channel_metrics'  => [
                'subscriber_count'   => $subscriber_count,
                'view_count'         => $view_count,
                'video_count'        => $video_count,
                'age_months'         => $age_months,
                'videos_per_month'   => $videos_per_month,
                'avg_er'             => $avg_er,
                'madeForKids'        => $channel['status']['madeForKids'] ?? false,
                'longUploadsStatus'  => $channel['status']['longUploadsStatus'] ?? '',
                'topicCategories'    => $channel['topicDetails']['topicCategories'] ?? [],
                'country'            => $channel['snippet']['country'] ?? '',
                'customUrl'          => $channel['snippet']['customUrl'] ?? '',
                'title'              => $channel['snippet']['title'] ?? '',
                'publishedAt'        => $channel['snippet']['publishedAt'] ?? '',
                'channel_title'      => $channel['snippet']['title'] ?? '',
                'channel_handle'     => $channel['snippet']['customUrl'] ?? '',
                'channel_thumb'      => $channel['snippet']['thumbnails']['default']['url'] ?? '',
                'channel_created_at' => $channel['snippet']['publishedAt'] ?? '',
                'channel_country'    => $channel['snippet']['country'] ?? '',
                'topic_categories'   => $channel['topicDetails']['topicCategories'] ?? [],
                'retry_date'         => $this->calc_retry_date( $channel['snippet']['publishedAt'] ?? '' ),
                'retry_months_left'  => $this->calc_months_left( $channel['snippet']['publishedAt'] ?? '' ),
                'videos_list'        => $videos_list,
            ],
        ];
    }
 
    // ─────────────────────────────────────────────────────────
    // Sprint v5.1: AI Slop — 8 сигналов
    // ─────────────────────────────────────────────────────────
 
    public function eval_aislop_signals( array $yt_data, array $ctx ): array {
        $videos  = $ctx['videos']  ?? $yt_data['videos']  ?? [];
        $channel = $ctx['channel'] ?? $yt_data['channel'] ?? [];
        $signals = [];
 
        if ( empty( $videos ) ) {
            return [ 'signals' => [], 'risk' => 'low', 'total' => 0 ];
        }
 
        // Сигнал 1: Одинаковая длина видео (HIGH)
        $dur = $this->check_uniform_duration( $videos );
        if ( $dur['ratio'] >= 0.6 ) {
            $signals[] = [
                'id'     => 'uniform_duration',
                'level'  => 'high',
                'title'  => 'Все видео одинаковой длины',
                'detail' => round( $dur['ratio'] * 100 ) . '% видео имеют длину ' . gmdate( 'i:s', $dur['median'] ) . ' (±30 сек)',
                'advice' => 'Снимайте видео разной длины — от коротких (3-5 мин) до длинных (20+ мин). Длина должна диктоваться темой, а не шаблоном.',
            ];
        }
 
        // Сигнал 2: Шаблонные заголовки (HIGH)
        $tmpl_count = $this->count_template_titles( $videos );
        $tmpl_ratio = count( $videos ) > 0 ? $tmpl_count / count( $videos ) : 0;
        if ( $tmpl_ratio >= 0.4 ) {
            $signals[] = [
                'id'     => 'template_titles',
                'level'  => 'high',
                'title'  => 'Шаблонные заголовки видео',
                'detail' => round( $tmpl_ratio * 100 ) . '% видео используют шаблонные паттерны (ТОП-N, Как..., Подборка и т.д.)',
                'advice' => 'Заголовки должны отражать уникальный угол подачи конкретного видео, а не шаблон. Избегайте списков и формул.',
            ];
        }
 
        // Сигнал 3: Reused-ключевые слова (HIGH)
        $kw = $this->check_reused_keywords( $videos );
        if ( $kw ) {
            $signals[] = [
                'id'     => 'reused_keywords',
                'level'  => 'high',
                'title'  => 'Ключевые слова reused-контента',
                'detail' => $kw['detail'],
                'advice' => 'Уберите эти теги из видео. Они прямо указывают алгоритму YouTube на компилятивный контент.',
            ];
        }
 
        // Сигнал 4: Аномально низкий comment ratio (HIGH)
        $comment_ratios = [];
        foreach ( $videos as $v ) {
            $views    = (int) ( $v['viewCount'] ?? 0 );
            $comments = (int) ( $v['commentCount'] ?? 0 );
            if ( $views >= 10000 ) {
                $comment_ratios[] = $views > 0 ? $comments / $views : 0;
            }
        }
        if ( count( $comment_ratios ) >= 3 ) {
            $avg_cr = array_sum( $comment_ratios ) / count( $comment_ratios );
            if ( $avg_cr < 0.002 ) {
                $pct       = round( $avg_cr * 100, 3 );
                $signals[] = [
                    'id'     => 'low_comment_ratio',
                    'level'  => 'high',
                    'title'  => 'Аномально низкая активность в комментариях',
                    'detail' => "Среднее соотношение комментариев к просмотрам: {$pct}% (норма > 0.2%). AI slop каналы имеют пассивную аудиторию.",
                    'advice' => 'Задавайте вопросы аудитории в конце видео. Отвечайте на каждый комментарий в первые 48 часов. YouTube учитывает comment engagement как сигнал подлинности контента.',
                ];
            }
        }
 
        // Сигнал 5: Высокая upload velocity (MEDIUM)
        $cutoff_30  = strtotime( '-30 days' );
        $recent_30  = array_filter( $videos, function ( $v ) use ( $cutoff_30 ) {
            return strtotime( $v['publishedAt'] ?? '' ) >= $cutoff_30;
        } );
        $velocity = count( $recent_30 );
        if ( $velocity >= 15 ) {
            $signals[] = [
                'id'     => 'high_velocity',
                'level'  => 'medium',
                'title'  => 'Высокая скорость публикаций',
                'detail' => "{$velocity} видео за последние 30 дней. YouTube считает >15 видео/мес признаком автоматизированного производства.",
                'advice' => 'Снизьте частоту публикаций, но повысьте качество каждого видео. Алгоритм YouTube предпочитает каналы с вовлечённой аудиторией, а не высоким объёмом.',
            ];
        }
 
        // Сигнал 6: Нет AI disclosure (MEDIUM) — только если уже есть high-сигналы
        $ai_markers = [ '#ai', '#ии', 'ai-generated', 'ai generated', 'сгенерирован',
            'создан ии', 'нейросеть', 'нейросети', 'artificial intelligence',
            'midjourney', 'elevenlabs', 'heygen', 'sora', 'veo' ];
        $has_ai_in_titles = false;
        $has_ai_in_desc   = false;
        foreach ( $videos as $v ) {
            $title_lower = mb_strtolower( $v['title'] ?? '' );
            $desc_lower  = mb_strtolower( $v['description'] ?? '' );
            foreach ( $ai_markers as $marker ) {
                if ( strpos( $title_lower, $marker ) !== false ) { $has_ai_in_titles = true; break; }
                if ( strpos( $desc_lower,  $marker ) !== false ) { $has_ai_in_desc   = true; break; }
            }
            if ( $has_ai_in_titles && $has_ai_in_desc ) break;
        }
        $high_signals_so_far = count( array_filter( $signals, fn( $s ) => $s['level'] === 'high' ) );
        if ( $high_signals_so_far >= 1 && ! $has_ai_in_titles && ! $has_ai_in_desc ) {
            $signals[] = [
                'id'     => 'no_ai_disclosure',
                'level'  => 'medium',
                'title'  => 'Нет маркировки AI-контента',
                'detail' => 'С июля 2025 YouTube требует обязательного раскрытия использования AI при создании «реалистичного» контента. Маркировка не найдена.',
                'advice' => 'Если вы используете AI-инструменты (голос, видеоряд, сценарий), добавьте #AI или явное упоминание в описание. Это снижает риск нарушения политики YouTube.',
            ];
        }
 
        // Сигнал 7: Шаблонность описаний (MEDIUM)
        $desc_lengths = [];
        foreach ( $videos as $v ) {
            $desc = $v['description'] ?? '';
            if ( mb_strlen( $desc ) > 0 ) {
                $desc_lengths[] = mb_strlen( $desc );
            }
        }
        if ( count( $desc_lengths ) >= 5 ) {
            $mean     = array_sum( $desc_lengths ) / count( $desc_lengths );
            $variance = array_sum( array_map( fn( $l ) => pow( $l - $mean, 2 ), $desc_lengths ) ) / count( $desc_lengths );
            $std_dev  = sqrt( $variance );
            if ( $std_dev < 50 && $mean > 0 ) {
                $signals[] = [
                    'id'     => 'uniform_descriptions',
                    'level'  => 'medium',
                    'title'  => 'Шаблонные описания видео',
                    'detail' => 'Длина описаний всех видео практически одинакова (σ=' . round( $std_dev ) . ' симв.). Признак автогенерации описаний.',
                    'advice' => 'Пишите уникальные описания для каждого видео, отражающие его конкретное содержание.',
                ];
            }
        }
 
        // Сигнал 8: AI-нишевой риск (MEDIUM)
        $HIGH_RISK_NICHES = [ 'News', 'True_Crime', 'Finance', 'Celebrity', 'Entertainment', 'Society', 'Politics', 'Lifestyle' ];
        $topics_str  = implode( ' ', $channel['topicDetails']['topicCategories'] ?? [] );
        $niche_hits  = [];
        foreach ( $HIGH_RISK_NICHES as $niche ) {
            if ( stripos( $topics_str, $niche ) !== false ) {
                $niche_hits[] = $niche;
            }
        }
        if ( ! empty( $niche_hits ) ) {
            $signals[] = [
                'id'     => 'high_risk_niche',
                'level'  => 'medium',
                'title'  => 'Ниша с повышенным вниманием YouTube',
                'detail' => 'Категория канала (' . implode( ', ', $niche_hits ) . ') входит в топ ниш по количеству удалений AI slop каналов в 2025-2026.',
                'advice' => 'В вашей нише YouTube применяет строжайшие фильтры. Убедитесь что каждое видео содержит авторский голос, уникальный угол и не дублирует другие источники.',
            ];
        }
 
        // Итоговый risk
        $high_count   = count( array_filter( $signals, fn( $s ) => $s['level'] === 'high' ) );
        $medium_count = count( array_filter( $signals, fn( $s ) => $s['level'] === 'medium' ) );
 
        if ( $high_count >= 2 )                              $risk = 'high';
        elseif ( $high_count >= 1 || $medium_count >= 2 )   $risk = 'medium';
        else                                                 $risk = 'low';
 
        return [ 'signals' => $signals, 'risk' => $risk, 'total' => count( $signals ) ];
    }
 
    // ─────────────────────────────────────────────────────────
    // БЛОК 1: критерии допуска
    // ─────────────────────────────────────────────────────────
 
    private function eval_block1( array $p ) {
        $criteria = [];
        $has_fail = false;
        $has_warn = false;
 
        if ( $p['age_months'] >= 6 ) {
            $status = 'ok';
            $detail = "{$p['age_months']} мес.";
        } else {
            $status   = 'fail';
            $has_fail = true;
            $detail   = "Только {$p['age_months']} мес. (требуется ≥ 6)";
        }
        $criteria[] = [ 'name' => 'Возраст ≥ 6 месяцев', 'status' => $status, 'detail' => $detail ];
 
        if ( ! $p['has_reg_gap'] ) {
            $criteria[] = [ 'name' => 'Регулярные публикации', 'status' => 'ok', 'detail' => round( $p['videos_per_month'], 1 ) . ' видео/мес' ];
        } else {
            $has_fail   = true;
            $criteria[] = [ 'name' => 'Регулярные публикации', 'status' => 'fail', 'detail' => 'Обнаружена пауза > 60 дней' ];
        }
 
        if ( ! $p['madeForKids'] ) {
            $criteria[] = [ 'name' => 'Не «Сделано для детей»', 'status' => 'ok', 'detail' => 'madeForKids = false' ];
        } else {
            $has_fail   = true;
            $criteria[] = [ 'name' => 'Не «Сделано для детей»', 'status' => 'fail', 'detail' => 'Канал помечен как детский' ];
        }
 
        if ( $p['video_count'] >= 5 ) {
            $criteria[] = [ 'name' => 'Минимум 5 видео', 'status' => 'ok', 'detail' => "{$p['video_count']} видео" ];
        } else {
            $has_fail   = true;
            $criteria[] = [ 'name' => 'Минимум 5 видео', 'status' => 'fail', 'detail' => "Только {$p['video_count']} публичных видео" ];
        }
 
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
 
        if ( ! $p['hiddenSubscribers'] ) {
            $criteria[] = [ 'name' => 'Открытое число подписчиков', 'status' => 'ok', 'detail' => 'Подписчики публичны' ];
        } else {
            $has_warn   = true;
            $criteria[] = [ 'name' => 'Открытое число подписчиков', 'status' => 'warn', 'detail' => 'Подписчики скрыты' ];
        }
 
        if ( $has_fail )      $block_status = 'fail';
        elseif ( $has_warn )  $block_status = 'warn';
        else                  $block_status = 'ok';
 
        return [ 'criteria' => $criteria, 'status' => $block_status ];
    }
 
    // ─────────────────────────────────────────────────────────
    // БЛОК 2: PHP-сигналы reused / mass-produced
    // ─────────────────────────────────────────────────────────
 
    private function eval_reused_signals( array $videos, float $vpm, float $avg_er, int $subs, array $channel ) {
        $signals = [];
        if ( empty( $videos ) ) return $signals;
 
        $total = count( $videos );
 
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
 
        if ( $vpm > 20 && $avg_er < 1.0 && $subs < 100000 ) {
            $signals[] = [
                'type'   => 'high_freq_low_er',
                'level'  => 'high',
                'title'  => 'Аномально высокая частота при низком ER',
                'detail' => round( $vpm, 1 ) . ' видео/мес при среднем ER ' . round( $avg_er, 2 ) . '% — признак массового производства контента',
            ];
        }
 
        $kw_signal = $this->check_reused_keywords( $videos );
        if ( $kw_signal ) {
            $signals[] = $kw_signal;
        }
 
        $topics = implode( ' ', $channel['topicDetails']['topicCategories'] ?? [] );
        if ( stripos( $topics, 'News' ) !== false && $vpm > 10 ) {
            $signals[] = [
                'type'   => 'news_high_freq',
                'level'  => 'high',
                'title'  => 'Новостной контент с высокой частотой',
                'detail' => 'Категория канала содержит "News" при частоте ' . round( $vpm, 1 ) . ' видео/мес',
            ];
        }
 
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
 
    private function count_template_titles( array $videos ) {
        $titles = array_column( $videos, 'title' );
 
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
    // prepare_videos_list (Sprint v5.1: добавлен description)
    // ─────────────────────────────────────────────────────────
 
    private function prepare_videos_list( array $videos, array $php_signals ): array {
        if ( empty( $videos ) ) return [];
 
        $durations = array_filter( array_column( $videos, 'duration_sec' ), fn( $d ) => $d > 0 );
        $median_duration = 0;
        if ( count( $durations ) >= 3 ) {
            sort( $durations );
            $mid = (int) floor( count( $durations ) / 2 );
            $median_duration = $durations[ $mid ];
        }
 
        $has_uniform_duration_signal = false;
        foreach ( $php_signals as $s ) {
            if ( ( $s['type'] ?? '' ) === 'uniform_duration' ) {
                $has_uniform_duration_signal = true;
                break;
            }
        }
 
        $list  = [];
        $slice = array_slice( $videos, 0, 20 );
 
        foreach ( $slice as $v ) {
            $view_count    = (int) ( $v['viewCount'] ?? $v['view_count'] ?? 0 );
            $like_count    = (int) ( $v['likeCount'] ?? $v['like_count'] ?? 0 );
            $comment_count = (int) ( $v['commentCount'] ?? $v['comment_count'] ?? 0 );
            $duration_sec  = (int) ( $v['duration_sec'] ?? 0 );
            $title_raw     = $v['title'] ?? $v['snippet']['title'] ?? '';
            $title         = mb_substr( $title_raw, 0, 80 );
            $published_at  = $v['publishedAt'] ?? $v['snippet']['publishedAt'] ?? '';
 
            $er = $view_count > 0 ? round( ( $like_count / $view_count ) * 100, 2 ) : 0;
            $view_count_fmt = $this->format_number( $view_count );
            $duration_fmt   = $this->format_duration( $duration_sec );
 
            $issues = [];
            if ( $has_uniform_duration_signal && $median_duration > 0 && abs( $duration_sec - $median_duration ) <= 30 ) {
                $issues[] = 'reused';
            }
            if ( $er < 1.5 && $view_count > 0 ) {
                $issues[] = 'low_er';
            }
            if ( $median_duration > 0 && abs( $duration_sec - $median_duration ) <= 30 ) {
                $issues[] = 'duration_match';
            }
 
            $list[] = [
                'title'          => $title,
                'view_count'     => $view_count,
                'view_count_fmt' => $view_count_fmt,
                'like_count'     => $like_count,
                'comment_count'  => $comment_count,
                'duration_sec'   => $duration_sec,
                'duration_fmt'   => $duration_fmt,
                'published_at'   => $published_at,
                'er'             => $er,
                'issues'         => $issues,
                'description'    => mb_substr( $v['description'] ?? '', 0, 500 ),
            ];
        }
 
        return $list;
    }
 
    private function format_number( int $n ): string {
        if ( $n >= 1000000 ) return round( $n / 1000000, 1 ) . 'M';
        if ( $n >= 1000 )    return round( $n / 1000, 1 ) . 'k';
        return (string) $n;
    }
 
    private function format_duration( int $sec ): string {
        if ( $sec <= 0 ) return '0:00';
        $h = (int) floor( $sec / 3600 );
        $m = (int) floor( ( $sec % 3600 ) / 60 );
        $s = $sec % 60;
        if ( $h > 0 ) return sprintf( '%d:%02d:%02d', $h, $m, $s );
        return sprintf( '%d:%02d', $m, $s );
    }
 
    // ─────────────────────────────────────────────────────────
    // Вспомогательные метрики (публичные — используются из REST)
    // ─────────────────────────────────────────────────────────
 
    public function calc_age_months( $published_at ) {
        if ( empty( $published_at ) ) return 0;
        $created = strtotime( $published_at );
        if ( ! $created ) return 0;
        $diff = ( time() - $created ) / ( 30.44 * 24 * 3600 );
        return max( 0, round( $diff, 1 ) );
    }
 
    public function calc_videos_per_month( array $videos, int $months = 3 ) {
        if ( empty( $videos ) ) return 0;
        $cutoff = strtotime( "-{$months} months" );
        $count  = 0;
        foreach ( $videos as $v ) {
            $pub = strtotime( $v['publishedAt'] ?? '' );
            if ( $pub && $pub >= $cutoff ) $count++;
        }
        return round( $count / $months, 1 );
    }
 
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
 
    public function get_block2_risk_from_signals( array $php_signals ) {
        $high_count = 0;
        foreach ( $php_signals as $s ) {
            if ( ( $s['level'] ?? '' ) === 'high' ) $high_count++;
        }
        if ( $high_count >= 2 ) return 'high';
        if ( $high_count >= 1 ) return 'medium';
        return 'low';
    }
 
    private function calc_retry_date( string $published_at ): string {
        if ( empty( $published_at ) ) return '';
        try {
            $date = new DateTimeImmutable( $published_at );
        } catch ( \Exception $e ) {
            return '';
        }
        $retry = $date->modify( '+6 months' );
        $now   = new DateTimeImmutable();
        if ( $retry <= $now ) return '';
 
        if ( class_exists( 'IntlDateFormatter' ) ) {
            $fmt = new \IntlDateFormatter( 'ru_RU', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE, 'UTC', \IntlDateFormatter::GREGORIAN, 'd MMMM yyyy' );
            $result = $fmt->format( $retry );
            if ( $result !== false ) return $result;
        }
 
        $months_ru = [ 'января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря' ];
        return (int)$retry->format('j') . ' ' . $months_ru[(int)$retry->format('n') - 1] . ' ' . (int)$retry->format('Y');
    }
 
    private function calc_months_left( string $published_at ): float {
        if ( empty( $published_at ) ) return 0;
        $created = strtotime( $published_at );
        if ( ! $created ) return 0;
        $age_months = ( time() - $created ) / ( 30.44 * 24 * 3600 );
        $left = 6.0 - $age_months;
        return $left <= 0 ? 0 : round( $left, 1 );
    }
}
