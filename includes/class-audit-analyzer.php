<?php
/**
 * Анализатор данных канала.
 *
 * Собирает метрики из YouTube API и формирует payload для OpenAI.
 * OpenAI-вызов — в Sprint 2; здесь только агрегация данных
 * и вычисление предварительного (rule-based) вердикта.
 *
 * @package Payway
 * @since   7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Payway_Audit_Analyzer {

	// -------------------------------------------------------------------------
	// Thresholds для rule-based preview
	// -------------------------------------------------------------------------

	/** Минимальный % мейкфоркидс-видео → риск отказа */
	private const MFK_THRESHOLD = 0.3;

	/** Минимальный % видео с явной лицензией Creative Commons */
	private const CC_THRESHOLD  = 0.5;

	/** Минимальный % видео без кастомных превью */
	private const NO_THUMB_HIGH = 0.6;

	private Payway_YouTube_API $yt;

	public function __construct() {
		$this->yt = new Payway_YouTube_API();
	}

	// -------------------------------------------------------------------------
	// Public
	// -------------------------------------------------------------------------

	/**
	 * Запустить полный анализ канала.
	 *
	 * @param string $channel_url URL канала.
	 * @return array{
	 *   channel: array,
	 *   metrics: array,
	 *   preview: array,
	 *   ai_payload: array,
	 * }
	 * @throws RuntimeException При ошибках YouTube API.
	 */
	public function analyze( string $channel_url ): array {
		// 1. Данные канала
		$channel = $this->yt->get_channel_data( $channel_url );

		// 2. ID последних 100 видео
		$video_ids = $this->yt->get_video_ids( $channel['uploads_playlist_id'], 100 );

		// 3. Детали видео
		$videos = ! empty( $video_ids )
			? $this->yt->get_videos_details( $video_ids )
			: [];

		// 4. Агрегированные метрики
		$metrics = $this->compute_metrics( $channel, $videos );

		// 5. Rule-based preview (без AI)
		$preview = $this->compute_preview( $channel, $metrics );

		// 6. Payload для OpenAI (Sprint 2)
		$ai_payload = $this->build_ai_payload( $channel, $videos, $metrics );

		return compact( 'channel', 'metrics', 'preview', 'ai_payload' );
	}

	// -------------------------------------------------------------------------
	// Private: metrics
	// -------------------------------------------------------------------------

	/**
	 * Агрегировать числовые метрики по массиву видео.
	 */
	private function compute_metrics( array $channel, array $videos ): array {
		$total = count( $videos );
		if ( $total === 0 ) {
			return $this->empty_metrics( $channel );
		}

		$mfk_count        = 0;
		$cc_count         = 0;
		$no_thumb_count   = 0;
		$private_count    = 0;
		$short_count      = 0; // duration < PT1M
		$total_views      = 0;
		$total_likes      = 0;
		$total_comments   = 0;
		$categories       = [];

		foreach ( $videos as $v ) {
			if ( $v['made_for_kids'] )               $mfk_count++;
			if ( $v['license'] === 'creativeCommon' ) $cc_count++;
			if ( ! $v['has_custom_thumbnail'] )       $no_thumb_count++;
			if ( $v['privacy_status'] !== 'public' )  $private_count++;
			if ( $this->is_short( $v['duration'] ) )  $short_count++;

			$total_views    += $v['view_count'];
			$total_likes    += $v['like_count'];
			$total_comments += $v['comment_count'];
			$categories[]    = $v['category_id'];
		}

		$avg_views    = round( $total_views    / $total );
		$avg_likes    = round( $total_likes    / $total );
		$avg_comments = round( $total_comments / $total );

		$category_freq = array_count_values( $categories );
		arsort( $category_freq );
		$top_category = array_key_first( $category_freq ) ?? 0;

		return [
			'total_videos_analyzed' => $total,
			'subscriber_count'      => $channel['subscriber_count'],
			'total_view_count'      => $channel['view_count'],
			'channel_video_count'   => $channel['video_count'],
			'country'               => $channel['country'],
			'mfk_ratio'             => round( $mfk_count    / $total, 4 ),
			'cc_ratio'              => round( $cc_count      / $total, 4 ),
			'no_thumb_ratio'        => round( $no_thumb_count/ $total, 4 ),
			'private_ratio'         => round( $private_count / $total, 4 ),
			'shorts_ratio'          => round( $short_count   / $total, 4 ),
			'avg_views'             => $avg_views,
			'avg_likes'             => $avg_likes,
			'avg_comments'          => $avg_comments,
			'engagement_rate'       => $avg_views > 0
				? round( ( $avg_likes + $avg_comments ) / $avg_views, 4 )
				: 0,
			'top_category_id'       => $top_category,
		];
	}

	/**
	 * Пустые метрики (для каналов без видео).
	 */
	private function empty_metrics( array $channel ): array {
		return [
			'total_videos_analyzed' => 0,
			'subscriber_count'      => $channel['subscriber_count'],
			'total_view_count'      => $channel['view_count'],
			'channel_video_count'   => $channel['video_count'],
			'country'               => $channel['country'],
			'mfk_ratio'             => 0,
			'cc_ratio'              => 0,
			'no_thumb_ratio'        => 0,
			'private_ratio'         => 0,
			'shorts_ratio'          => 0,
			'avg_views'             => 0,
			'avg_likes'             => 0,
			'avg_comments'          => 0,
			'engagement_rate'       => 0,
			'top_category_id'       => 0,
		];
	}

	// -------------------------------------------------------------------------
	// Private: rule-based preview
	// -------------------------------------------------------------------------

	/**
	 * Вычислить предварительный вердикт без AI (rule-based).
	 *
	 * Возвращает JSON-совместимый массив — сохраняется в report_preview.
	 */
	private function compute_preview( array $channel, array $metrics ): array {
		// Block 1: Admission
		$admission = $this->compute_admission( $channel, $metrics );

		// Block 2: Demonetization risk
		$demonetization = $this->compute_demonetization( $metrics );

		// Block 3: Copyright risk
		$copyright = $this->compute_copyright( $metrics );

		return [
			'admission'      => $admission,
			'demonetization' => $demonetization,
			'copyright'      => $copyright,
			'generated_at'   => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'is_ai'          => false,
		];
	}

	private function compute_admission( array $channel, array $metrics ): array {
		$flags    = [];
		$verdict  = 'allowed';

		if ( $metrics['subscriber_count'] < 1000 ) {
			$flags[]  = 'subscriber_count_low';
			$verdict  = 'needs_check';
		}
		if ( $metrics['mfk_ratio'] > self::MFK_THRESHOLD ) {
			$flags[]  = 'made_for_kids_high';
			$verdict  = 'denied';
		}
		if ( $metrics['total_videos_analyzed'] === 0 ) {
			$flags[]  = 'no_public_videos';
			$verdict  = 'needs_check';
		}

		return [
			'verdict' => $verdict,
			'flags'   => $flags,
			'summary' => $this->admission_summary( $verdict ),
		];
	}

	private function compute_demonetization( array $metrics ): array {
		$score = 0;

		if ( $metrics['mfk_ratio']       > 0.1  ) $score += 2;
		if ( $metrics['shorts_ratio']     > 0.7  ) $score += 1;
		if ( $metrics['avg_views']        < 500  ) $score += 1;
		if ( $metrics['engagement_rate']  < 0.01 ) $score += 1;
		if ( $metrics['private_ratio']    > 0.2  ) $score += 1;

		$risk = match ( true ) {
			$score >= 4 => 'high',
			$score >= 2 => 'medium',
			default     => 'low',
		};

		return [
			'risk'    => $risk,
			'score'   => $score,
			'summary' => $this->risk_summary( 'demonetization', $risk ),
		];
	}

	private function compute_copyright( array $metrics ): array {
		$score = 0;

		if ( $metrics['cc_ratio']       > self::CC_THRESHOLD ) $score += 2;
		if ( $metrics['no_thumb_ratio'] > self::NO_THUMB_HIGH ) $score += 1;

		$risk = match ( true ) {
			$score >= 3 => 'high',
			$score >= 1 => 'medium',
			default     => 'low',
		};

		return [
			'risk'    => $risk,
			'score'   => $score,
			'summary' => $this->risk_summary( 'copyright', $risk ),
		];
	}

	// -------------------------------------------------------------------------
	// Private: AI payload builder
	// -------------------------------------------------------------------------

	/**
	 * Сформировать payload для OpenAI (используется в Sprint 2).
	 */
	private function build_ai_payload( array $channel, array $videos, array $metrics ): array {
		$videos_summary = array_map( function ( $v ) {
			return [
				'title'         => $v['title'],
				'description'   => mb_substr( $v['description'], 0, 200 ),
				'tags'          => array_slice( $v['tags'], 0, 10 ),
				'category_id'   => $v['category_id'],
				'duration'      => $v['duration'],
				'license'       => $v['license'],
				'made_for_kids' => $v['made_for_kids'],
			];
		}, $videos );

		return [
			'channel' => [
				'title'            => $channel['title'],
				'description'      => mb_substr( $channel['description'], 0, 500 ),
				'country'          => $channel['country'],
				'subscriber_count' => $channel['subscriber_count'],
				'video_count'      => $channel['video_count'],
			],
			'metrics'         => $metrics,
			'videos_analyzed' => count( $videos_summary ),
			'videos'          => $videos_summary,
		];
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Определить, является ли видео Shorts (duration < 1 минута).
	 */
	private function is_short( string $iso_duration ): bool {
		if ( preg_match( '/^PT(\d+)S$/', $iso_duration, $m ) ) {
			return (int) $m[1] < 60;
		}
		return false;
	}

	private function admission_summary( string $verdict ): string {
		return match ( $verdict ) {
			'allowed'     => 'Канал соответствует базовым критериям допуска к платформе.',
			'denied'      => 'Канал не соответствует требованиям монетизации.',
			'needs_check' => 'Канал требует дополнительной проверки.',
			default       => '',
		};
	}

	private function risk_summary( string $block, string $risk ): string {
		$map = [
			'demonetization' => [
				'low'    => 'Низкий риск демонетизации. Показатели вовлечённости в норме.',
				'medium' => 'Средний риск. Рекомендуется улучшить охват и вовлечённость.',
				'high'   => 'Высокий риск демонетизации. Необходимы корректировки контент-стратегии.',
			],
			'copyright' => [
				'low'    => 'Нарушений авторских прав не обнаружено по базовым критериям.',
				'medium' => 'Возможны единичные нарушения. Детальный анализ рекомендован.',
				'high'   => 'Высокий риск проблем с авторскими правами.',
			],
		];
		return $map[ $block ][ $risk ] ?? '';
	}
    //  Sprint 2: OpenAI full analysis 

    /**
     * Send the AI payload to OpenAI gpt-4o and get the full structured report.
     *
     * Expected JSON response shape:
     * {
     *   "admission":       { "verdict": "allowed|denied|needs_check", "summary": "...", "bullets": [...] },
     *   "demonetization":  { "risk": "low|medium|high", "summary": "...", "bullets": [...] },
     *   "copyright":       { "risk": "low|medium|high", "summary": "...", "bullets": [...] },
     *   "overall_summary": "...",
     *   "recommendations": [...]
     * }
     *
     * @param  array $ai_payload Output of build_ai_payload().
     * @return array Decoded AI report.
     * @throws RuntimeException If OpenAI fails or returns invalid JSON.
     */
    public function analyze_with_ai( array $ai_payload ): array {
        $client = new PW_OpenAI_Client();

        $system = <<<PROMPT
You are an expert YouTube channel compliance analyst. Analyse the provided channel data and return a structured JSON audit report. Be objective and concise.

Return ONLY a valid JSON object with this exact structure:
{
  "admission": {
    "verdict": "allowed | denied | needs_check",
    "summary": "2-3 sentence explanation",
    "bullets": ["key point 1", "key point 2", "key point 3"]
  },
  "demonetization": {
    "risk": "low | medium | high",
    "summary": "2-3 sentence explanation",
    "bullets": ["key point 1", "key point 2", "key point 3"]
  },
  "copyright": {
    "risk": "low | medium | high",
    "summary": "2-3 sentence explanation",
    "bullets": ["key point 1", "key point 2", "key point 3"]
  },
  "overall_summary": "3-4 sentence executive summary",
  "recommendations": ["action 1", "action 2", "action 3"]
}

Use the rule-based pre-analysis (admission_verdict, demonetization_risk, copyright_risk) as guidance but apply your own reasoning based on the full video data.
PROMPT;

        $user = json_encode( $ai_payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

        $report = $client->ask_json( $system, $user, 'gpt-4o', 2000 );

        // Validate required top-level keys
        $required = [ 'admission', 'demonetization', 'copyright', 'overall_summary', 'recommendations' ];
        foreach ( $required as $key ) {
            if ( ! array_key_exists( $key, $report ) ) {
                throw new \RuntimeException( "OpenAI report missing key: {$key}" );
            }
        }

        return $report;
    }

}
