<?php
/**
 * PW_YouTube_API — получение данных канала через YouTube Data API v3
 * Поля строго по ТЗ §4.3
 */
class PW_YouTube_API {

    private $api_key;
    const API_BASE = 'https://www.googleapis.com/youtube/v3/';

    public function __construct() {
        $this->api_key = get_option( 'payway_youtube_api_key', '' );
    }

    /**
     * Основной метод: возвращает данные канала + последние 20 видео.
     * Кэш через WP transients (TTL 24 ч).
     *
     * @param string $channel_url  URL канала (любой поддерживаемый формат)
     * @return array|WP_Error
     */
    public function get_channel_full_data( $channel_url ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', 'YouTube API key не настроен' );
        }

        $channel_id = $this->resolve_channel_id( $channel_url );
        if ( is_wp_error( $channel_id ) ) {
            return $channel_id;
        }

        // Кэш данных канала
        $cache_key = 'payway_yt_' . md5( $channel_id );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        // 1. channels.list
        $channel = $this->channels_list( $channel_id );
        if ( is_wp_error( $channel ) ) return $channel;

        // 2. Получаем ID плейлиста загрузок
        $uploads_playlist = $channel['contentDetails']['relatedPlaylists']['uploads'] ?? '';
        if ( empty( $uploads_playlist ) ) {
            return new WP_Error( 'no_uploads', 'Не найден плейлист загрузок канала' );
        }

        // 3. playlistItems.list → последние 20 видео
        $video_ids = $this->get_playlist_video_ids( $uploads_playlist, 20 );
        if ( is_wp_error( $video_ids ) ) return $video_ids;

        // 4. videos.list с детальными данными
        $videos = [];
        if ( ! empty( $video_ids ) ) {
            $videos = $this->videos_list( $video_ids );
            if ( is_wp_error( $videos ) ) return $videos;
        }

        $result = [
            'channel' => $channel,
            'videos'  => $videos,
        ];

        set_transient( $cache_key, $result, 24 * HOUR_IN_SECONDS );

        return $result;
    }

    /**
     * Определяет channel_id из различных форматов URL YouTube.
     */
    public function resolve_channel_id( $url ) {
        $url = trim( $url );

        // Прямой ID: UCxxxxxx
        if ( preg_match( '/^UC[a-zA-Z0-9_-]{22}$/', $url ) ) {
            return $url;
        }

        // /channel/UCxxxxxx
        if ( preg_match( '#youtube\.com/channel/(UC[a-zA-Z0-9_-]{22})#', $url, $m ) ) {
            return $m[1];
        }

        // /@handle или /c/name — нужен дополнительный запрос
        $handle = null;
        if ( preg_match( '#youtube\.com/@([^/?&]+)#', $url, $m ) ) {
            $handle = $m[1];
            return $this->resolve_by_handle( '@' . $handle );
        }
        if ( preg_match( '#youtube\.com/c/([^/?&]+)#', $url, $m ) ) {
            $handle = $m[1];
            return $this->resolve_by_handle( $handle );
        }
        if ( preg_match( '#youtube\.com/user/([^/?&]+)#', $url, $m ) ) {
            $handle = $m[1];
            return $this->resolve_by_handle( $handle );
        }

        return new WP_Error( 'invalid_url', 'Неверный формат URL YouTube-канала' );
    }

    private function resolve_by_handle( $handle ) {
        $params = [
            'part'           => 'id',
            'forHandle'      => ltrim( $handle, '@' ),
            'key'            => $this->api_key,
            'maxResults'     => 1,
        ];
        $data = $this->make_request( 'channels', $params );
        if ( is_wp_error( $data ) ) return $data;
        $channel_id = $data['items'][0]['id'] ?? null;
        if ( ! $channel_id ) {
            return new WP_Error( 'channel_not_found', 'Канал не найден: ' . $handle );
        }
        return $channel_id;
    }

    /**
     * channels.list — все части по ТЗ §4.3
     */
    private function channels_list( $channel_id ) {
        $params = [
            'part' => 'snippet,statistics,status,topicDetails,contentDetails',
            'id'   => $channel_id,
            'key'  => $this->api_key,
        ];
        $data = $this->make_request( 'channels', $params );
        if ( is_wp_error( $data ) ) return $data;
        if ( empty( $data['items'] ) ) {
            return new WP_Error( 'channel_not_found', 'Канал не найден по ID: ' . $channel_id );
        }
        return $data['items'][0];
    }

    /**
     * playlistItems.list — получаем ID последних N видео
     */
    private function get_playlist_video_ids( $playlist_id, $max = 20 ) {
        $params = [
            'part'       => 'contentDetails',
            'playlistId' => $playlist_id,
            'maxResults' => $max,
            'key'        => $this->api_key,
        ];
        $data = $this->make_request( 'playlistItems', $params );
        if ( is_wp_error( $data ) ) return $data;

        $ids = [];
        foreach ( $data['items'] ?? [] as $item ) {
            $vid = $item['contentDetails']['videoId'] ?? null;
            if ( $vid ) $ids[] = $vid;
        }
        return $ids;
    }

    /**
     * videos.list — детальные данные видео по ТЗ §4.3
     * snippet, statistics, contentDetails, status
     */
    private function videos_list( array $video_ids ) {
        if ( empty( $video_ids ) ) return [];

        // API принимает до 50 ID за раз; берём первый 20
        $ids = array_slice( $video_ids, 0, 20 );

        $params = [
            'part' => 'snippet,statistics,contentDetails,status',
            'id'   => implode( ',', $ids ),
            'key'  => $this->api_key,
        ];
        $data = $this->make_request( 'videos', $params );
        if ( is_wp_error( $data ) ) return $data;

        $videos = [];
        foreach ( $data['items'] ?? [] as $item ) {
            // Пропускаем скрытые видео
            if ( ( $item['status']['privacyStatus'] ?? 'public' ) !== 'public' ) {
                continue;
            }
            $videos[] = [
                'id'           => $item['id'],
                'title'        => $item['snippet']['title'] ?? '',
                'publishedAt'  => $item['snippet']['publishedAt'] ?? '',
                'tags'         => $item['snippet']['tags'] ?? [],
                'categoryId'   => $item['snippet']['categoryId'] ?? '',
                'viewCount'    => (int) ( $item['statistics']['viewCount'] ?? 0 ),
                'likeCount'    => (int) ( $item['statistics']['likeCount'] ?? 0 ),
                'commentCount' => (int) ( $item['statistics']['commentCount'] ?? 0 ),
                'duration_sec' => $this->parse_duration( $item['contentDetails']['duration'] ?? 'PT0S' ),
                'privacyStatus'=> $item['status']['privacyStatus'] ?? 'public',
            ];
        }
        return $videos;
    }

    /**
     * Парсит ISO 8601 duration (PT1H2M3S) в секунды
     */
    public function parse_duration( $duration ) {
        preg_match( '/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $m );
        return (int) ( $m[1] ?? 0 ) * 3600
             + (int) ( $m[2] ?? 0 ) * 60
             + (int) ( $m[3] ?? 0 );
    }

    /**
     * HTTP-запрос к YouTube Data API v3
     */
    private function make_request( $endpoint, array $params ) {
        $url      = self::API_BASE . $endpoint . '?' . http_build_query( $params );
        $response = wp_remote_get( $url, [ 'timeout' => 15 ] );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'yt_http_error', $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code !== 200 ) {
            $msg = $data['error']['message'] ?? "HTTP {$code}";
            return new WP_Error( 'yt_api_error', 'YouTube API: ' . $msg );
        }

        return $data;
    }
}
