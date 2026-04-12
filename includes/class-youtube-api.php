<?php
/**
 * Обёртка над YouTube Data API v3.
 *
 * Использует server-side API key из wp_options (ключ 'payway_youtube_api_key').
 * Все запросы — публичные (без OAuth пользователя).
 *
 * @package Payway
 * @since   7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Payway_YouTube_API {

	private const BASE_URL   = 'https://www.googleapis.com/youtube/v3/';
	private const MAX_VIDEOS = 100;

	private string $api_key;

	public function __construct() {
		$this->api_key = (string) get_option( 'payway_youtube_api_key', '' );
	}

	/**
	 * Загрузить полные данные канала по URL.
	 *
	 * @param string $channel_url
	 * @return array
	 * @throws RuntimeException
	 */
	public function get_channel_data( string $channel_url ): array {
		$this->assert_key();
		$params = $this->resolve_channel_params( $channel_url );
		$data = $this->request( 'channels', array_merge( $params, [
			'part'       => 'snippet,statistics,contentDetails',
			'maxResults' => 1,
		] ) );
		if ( empty( $data['items'] ) ) {
			throw new RuntimeException( 'YouTube канал не найден: ' . $channel_url );
		}
		$item    = $data['items'][0];
		$snippet = $item['snippet']        ?? [];
		$stats   = $item['statistics']     ?? [];
		$content = $item['contentDetails'] ?? [];
		return [
			'channel_id'          => $item['id'],
			'title'               => $snippet['title']                       ?? '',
			'description'         => $snippet['description']                 ?? '',
			'custom_url'          => $snippet['customUrl']                   ?? '',
			'country'             => $snippet['country']                     ?? '',
			'subscriber_count'    => (int) ( $stats['subscriberCount']       ?? 0 ),
			'video_count'         => (int) ( $stats['videoCount']            ?? 0 ),
			'view_count'          => (int) ( $stats['viewCount']             ?? 0 ),
			'uploads_playlist_id' => $content['relatedPlaylists']['uploads'] ?? '',
			'published_at'        => $snippet['publishedAt']                 ?? '',
			'thumbnail_url'       => $snippet['thumbnails']['high']['url']   ?? '',
		];
	}

	/**
	 * Загрузить последние N video_id из uploads-плейлиста.
	 *
	 * @param string $playlist_id
	 * @param int    $limit
	 * @return string[]
	 */
	public function get_video_ids( string $playlist_id, int $limit = self::MAX_VIDEOS ): array {
		$this->assert_key();
		$ids        = [];
		$page_token = null;
		while ( count( $ids ) < $limit ) {
			$params = [
				'part'       => 'contentDetails',
				'playlistId' => $playlist_id,
				'maxResults' => min( 50, $limit - count( $ids ) ),
			];
			if ( $page_token ) {
				$params['pageToken'] = $page_token;
			}
			$data = $this->request( 'playlistItems', $params );
			foreach ( $data['items'] ?? [] as $item ) {
				$ids[] = $item['contentDetails']['videoId'] ?? '';
			}
			$ids        = array_filter( $ids );
			$page_token = $data['nextPageToken'] ?? null;
			if ( ! $page_token ) break;
		}
		return array_values( array_slice( $ids, 0, $limit ) );
	}

	/**
	 * Загрузить детали видео батчами по 50.
	 *
	 * @param string[] $video_ids
	 * @return array[]
	 */
	public function get_videos_details( array $video_ids ): array {
		$this->assert_key();
		$videos  = [];
		$batches = array_chunk( $video_ids, 50 );
		foreach ( $batches as $batch ) {
			$data = $this->request( 'videos', [
				'part'       => 'snippet,contentDetails,statistics,status',
				'id'         => implode( ',', $batch ),
				'maxResults' => 50,
			] );
			foreach ( $data['items'] ?? [] as $item ) {
				$snippet = $item['snippet']        ?? [];
				$content = $item['contentDetails'] ?? [];
				$stats   = $item['statistics']     ?? [];
				$status  = $item['status']         ?? [];
				$videos[] = [
					'id'                   => $item['id'],
					'title'                => $snippet['title']                      ?? '',
					'description'          => mb_substr( $snippet['description'] ?? '', 0, 500 ),
					'tags'                 => $snippet['tags']                       ?? [],
					'category_id'          => (int) ( $snippet['categoryId']          ?? 0 ),
					'published_at'         => $snippet['publishedAt']                ?? '',
					'duration'             => $content['duration']                   ?? '',
					'made_for_kids'        => (bool) ( $status['madeForKids']        ?? false ),
					'privacy_status'       => $status['privacyStatus']               ?? 'public',
					'license'              => $status['license']                     ?? 'youtube',
					'embeddable'           => (bool) ( $status['embeddable']         ?? true ),
					'view_count'           => (int) ( $stats['viewCount']            ?? 0 ),
					'like_count'           => (int) ( $stats['likeCount']            ?? 0 ),
					'comment_count'        => (int) ( $stats['commentCount']         ?? 0 ),
					'has_custom_thumbnail' => ! empty( $snippet['thumbnails']['maxres'] ),
				];
			}
		}
		return $videos;
	}

	// ---- Internal helpers ----

	private function resolve_channel_params( string $url ): array {
		$url = trim( $url );
		if ( preg_match( '#youtube\.com/@([\w\-]+)#i', $url, $m ) ) {
			return [ 'forHandle' => '@' . $m[1] ];
		}
		if ( preg_match( '#youtube\.com/channel/(UC[\w\-]+)#i', $url, $m ) ) {
			return [ 'id' => $m[1] ];
		}
		if ( preg_match( '#youtube\.com/(?:c|user)/([\w\-]+)#i', $url, $m ) ) {
			return [ 'forUsername' => $m[1] ];
		}
		if ( preg_match( '#^UC[\w\-]{22}$#', $url ) ) {
			return [ 'id' => $url ];
		}
		throw new RuntimeException( 'Не удалось определить ID канала: ' . $url );
	}

	private function request( string $endpoint, array $params ): array {
		$params['key'] = $this->api_key;
		$url           = self::BASE_URL . $endpoint . '?' . http_build_query( $params );
		$response      = wp_remote_get( $url, [ 'timeout' => 15 ] );
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( 'YouTube API error: ' . $response->get_error_message() );
		}
		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code !== 200 ) {
			$msg = $body['error']['message'] ?? 'неизвестная ошибка';
			throw new RuntimeException( "YouTube API {$code}: {$msg}" );
		}
		return $body;
	}

	private function assert_key(): void {
		if ( empty( $this->api_key ) ) {
			throw new RuntimeException( 'YouTube API key не настроен (wp_options: payway_youtube_api_key).' );
		}
	}
}
