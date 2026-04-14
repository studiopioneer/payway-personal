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

        $published_at = $channel['snippet']['publishedAt'] ?? '';

        return [
            'age_months'       => $age_months,
            'videos_per_month' => $videos_per_month,
            'avg_er'           => $avg_er,
            'block1_criteria'  => $block1['criteria'],
            'block1_status'    => $block1['status'],   // 'ok' | 'warn' | 'fail'
            'php_signals'      => $php_signals,
        ];
    }

