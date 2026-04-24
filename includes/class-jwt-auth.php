<?php
/**
 * PW_JWT_Auth — собственная JWT-авторизация для PayWay Personal.
 * Заменяет внешний плагин "JWT Authentication for WP-API".
 *
 * Функционал:
 * 1. REST endpoint POST /wp-json/jwt-auth/v1/token — логин (совместимость с фронтендом)
 * 2. REST endpoint POST /wp-json/jwt-auth/v1/token/validate — проверка токена
 * 3. Хук determine_current_user — валидация Bearer токена из заголовка Authorization
 * 4. Метод generate_token() — генерация JWT (используется при регистрации)
 *
 * JWT реализован на чистом PHP (HMAC-SHA256), без внешних библиотек.
 *
 * @package PayWay
 * @since   8.0
 */
 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
 
class PW_JWT_Auth {
 
    /**
     * Время жизни токена в секундах (7 дней).
     */
    const TOKEN_LIFETIME = 604800;
 
    /**
     * Инициализация: регистрация хуков.
     */
    public static function init(): void {
        // REST endpoints (тот же namespace что использовал внешний плагин)
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
 
        // Валидация Bearer токена для определения текущего пользователя
        add_filter( 'determine_current_user', [ __CLASS__, 'determine_current_user' ], 20 );
 
        // Снимаем ошибку cookie-auth когда Bearer JWT токен валиден
        // WordPress выбрасывает rest_cookie_check_errors если cookie без nonce,
        // но при наличии валидного JWT nonce не нужен.
        add_filter( 'rest_authentication_errors', [ __CLASS__, 'bypass_cookie_error_for_jwt' ], 101 );
 
        // Разрешить Authorization заголовок
        add_filter( 'rest_pre_serve_request', [ __CLASS__, 'add_cors_headers' ], 15 );
 
        // DIAGNOSTIC: ловушка на максимальном приоритете — ловит ЛЮБУЮ ошибку auth
        add_filter( 'rest_authentication_errors', function( $result ) {
            if ( is_wp_error( $result ) ) {
                error_log( 'PW_JWT DIAGNOSTIC [p999] rest_auth ERROR: code=' . $result->get_error_code()
                    . ' msg=' . $result->get_error_message()
                    . ' status=' . ( $result->get_error_data()['status'] ?? '?' )
                    . ' uid=' . get_current_user_id()
                    . ' uri=' . $_SERVER['REQUEST_URI'] );
            }
            return $result;
        }, 999 );
    }
 
    // =====================================================================
    // REST Routes
    // =====================================================================
 
    public static function register_routes(): void {
        // POST /wp-json/jwt-auth/v1/token — логин
        register_rest_route( 'jwt-auth/v1', '/token', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'handle_login' ],
            'permission_callback' => '__return_true',
        ] );
 
        // POST /wp-json/jwt-auth/v1/token/validate — проверка токена
        register_rest_route( 'jwt-auth/v1', '/token/validate', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'handle_validate' ],
            'permission_callback' => '__return_true',
        ] );
    }
 
    /**
     * POST /jwt-auth/v1/token — логин по username + password.
     */
    public static function handle_login( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $username = $request->get_param( 'username' );
        $password = $request->get_param( 'password' );
 
        if ( empty( $username ) || empty( $password ) ) {
            return new WP_Error(
                'jwt_auth_bad_request',
                'Укажите email и пароль.',
                [ 'status' => 400 ]
            );
        }
 
        // Аутентификация через WordPress
        $user = wp_authenticate( $username, $password );
 
        if ( is_wp_error( $user ) ) {
            return new WP_Error(
                'jwt_auth_invalid_credentials',
                'Неверный email или пароль.',
                [ 'status' => 403 ]
            );
        }
 
        // Устанавливаем WordPress cookie-сессию
        // Без этого wp_head не знает пользователя → nonce/authToken будут анонимными
        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, true );
 
        // Генерация токена
        $token = self::generate_token_for_user( $user );
 
        return new WP_REST_Response( [
            'token'             => $token,
            'user_email'        => $user->user_email,
            'user_nicename'     => $user->user_nicename,
            'user_display_name' => $user->display_name,
        ], 200 );
    }
 
    /**
     * POST /jwt-auth/v1/token/validate — проверка токена.
     */
    public static function handle_validate( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $token = self::extract_token_from_headers();
 
        if ( ! $token ) {
            return new WP_Error( 'jwt_auth_no_token', 'Токен не найден.', [ 'status' => 403 ] );
        }
 
        $payload = self::decode_token( $token );
 
        if ( is_wp_error( $payload ) ) {
            return $payload;
        }
 
        return new WP_REST_Response( [
            'code' => 'jwt_auth_valid_token',
            'data' => [ 'status' => 200 ],
        ], 200 );
    }
 
    // =====================================================================
    // determine_current_user — хук для авторизации по Bearer токену
    // =====================================================================
 
    /**
     * Если в заголовках есть Authorization: Bearer {token},
     * валидируем токен и устанавливаем user_id.
     *
     * @param int $user_id Текущий user_id (может быть 0).
     * @return int
     */
    public static function determine_current_user( int $user_id ): int {
        $token = self::extract_token_from_headers();
 
        if ( ! $token ) {
            return $user_id;
        }
 
        // DEBUG: временное логирование (удалить после отладки)
        error_log( 'PW_JWT determine_current_user: token FOUND, incoming uid=' . $user_id );
 
        $payload = self::decode_token( $token );
 
        if ( is_wp_error( $payload ) ) {
            error_log( 'PW_JWT determine_current_user: decode FAILED — ' . $payload->get_error_code() );
            return $user_id;
        }
 
        error_log( 'PW_JWT determine_current_user: decode OK, payload=' . json_encode( $payload['data'] ?? 'no_data' ) );
 
        $token_user_id = isset( $payload['data']['user']['id'] ) ? (int) $payload['data']['user']['id'] : 0;
 
        if ( $token_user_id > 0 && get_userdata( $token_user_id ) ) {
            error_log( 'PW_JWT determine_current_user: setting user_id=' . $token_user_id );
            return $token_user_id;
        }
 
        error_log( 'PW_JWT determine_current_user: user not found or id=0, token_user_id=' . $token_user_id );
        return $user_id;
    }
 
    // =====================================================================
    // Token Generation
    // =====================================================================
 
    /**
     * Генерация JWT токена для пользователя.
     * Совместима с фронтендом (тот же формат что использовал внешний плагин).
     *
     * @param WP_User $user
     * @return string JWT token
     */
    public static function generate_token_for_user( WP_User $user ): string {
        $issued_at  = time();
        $expires_at = $issued_at + self::TOKEN_LIFETIME;
 
        $payload = [
            'iss'  => get_bloginfo( 'url' ),
            'iat'  => $issued_at,
            'nbf'  => $issued_at,
            'exp'  => $expires_at,
            'data' => [
                'user' => [
                    'id' => $user->ID,
                ],
            ],
        ];
 
        return self::encode_token( $payload );
    }
 
    /**
     * Обёртка для использования из RegistrationController.
     * Совместима по формату с JWT_Auth_Public::generate_token().
     *
     * @param WP_REST_Request $request (не используется, для совместимости)
     * @return array ['token' => '...']
     */
    public static function generate_token( WP_REST_Request $request = null ): array {
        $user = wp_get_current_user();
 
        if ( ! $user || ! $user->ID ) {
            return [ 'token' => null ];
        }
 
        return [ 'token' => self::generate_token_for_user( $user ) ];
    }
 
    // =====================================================================
    // JWT Encoding / Decoding (pure PHP, HMAC-SHA256)
    // =====================================================================
 
    /**
     * Кодирование JWT токена.
     */
    private static function encode_token( array $payload ): string {
        $header = self::base64url_encode( json_encode( [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ] ) );
 
        $body = self::base64url_encode( json_encode( $payload ) );
 
        $signature = self::base64url_encode(
            hash_hmac( 'sha256', "$header.$body", self::get_secret_key(), true )
        );
 
        return "$header.$body.$signature";
    }
 
    /**
     * Декодирование и валидация JWT токена.
     *
     * @param string $token
     * @return array|WP_Error Payload или WP_Error
     */
    private static function decode_token( string $token ) {
        $parts = explode( '.', $token );
 
        if ( count( $parts ) !== 3 ) {
            return new WP_Error( 'jwt_auth_bad_token', 'Неверный формат токена.', [ 'status' => 403 ] );
        }
 
        list( $header_b64, $body_b64, $signature_b64 ) = $parts;
 
        // Проверка подписи
        $expected_sig = self::base64url_encode(
            hash_hmac( 'sha256', "$header_b64.$body_b64", self::get_secret_key(), true )
        );
 
        if ( ! hash_equals( $expected_sig, $signature_b64 ) ) {
            return new WP_Error( 'jwt_auth_invalid_token', 'Подпись токена невалидна.', [ 'status' => 403 ] );
        }
 
        // Декодируем payload
        $payload = json_decode( self::base64url_decode( $body_b64 ), true );
 
        if ( ! $payload ) {
            return new WP_Error( 'jwt_auth_bad_payload', 'Не удалось декодировать токен.', [ 'status' => 403 ] );
        }
 
        // Проверка срока действия
        if ( isset( $payload['exp'] ) && $payload['exp'] < time() ) {
            return new WP_Error( 'jwt_auth_expired_token', 'Токен истёк.', [ 'status' => 403 ] );
        }
 
        // Проверка nbf (not before)
        if ( isset( $payload['nbf'] ) && $payload['nbf'] > time() ) {
            return new WP_Error( 'jwt_auth_premature_token', 'Токен ещё не активен.', [ 'status' => 403 ] );
        }
 
        return $payload;
    }
 
    // =====================================================================
    // Helpers
    // =====================================================================
 
    /**
     * Получить секретный ключ для подписи JWT.
     * Используем SECURE_AUTH_KEY из wp-config.php (всегда доступен).
     */
    private static function get_secret_key(): string {
        if ( defined( 'JWT_AUTH_SECRET_KEY' ) && JWT_AUTH_SECRET_KEY ) {
            error_log( 'PW_JWT get_secret_key: using JWT_AUTH_SECRET_KEY' );
            return JWT_AUTH_SECRET_KEY;
        }
 
        if ( defined( 'SECURE_AUTH_KEY' ) && SECURE_AUTH_KEY ) {
            error_log( 'PW_JWT get_secret_key: using SECURE_AUTH_KEY' );
            return SECURE_AUTH_KEY;
        }
 
        error_log( 'PW_JWT get_secret_key: using AUTH_KEY (fallback)' );
        return AUTH_KEY;
    }
 
    /**
     * Извлечь Bearer токен из HTTP заголовков.
     */
    private static function extract_token_from_headers(): ?string {
        $auth = '';
 
        // Стандартный способ
        if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif ( isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
            $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif ( function_exists( 'getallheaders' ) ) {
            $headers = getallheaders();
            if ( isset( $headers['Authorization'] ) ) {
                $auth = $headers['Authorization'];
            }
        }
 
        if ( empty( $auth ) ) {
            return null;
        }
 
        // Извлекаем токен из "Bearer {token}"
        if ( preg_match( '/Bearer\s+(\S+)/', $auth, $matches ) ) {
            return $matches[1];
        }
 
        return null;
    }
 
    /**
     * Если запрос содержит валидный JWT Bearer токен, снимаем ошибку
     * rest_cookie_check_errors (которая возникает когда есть cookie без nonce).
     *
     * @param WP_Error|null|true $result
     * @return WP_Error|null|true
     */
    public static function bypass_cookie_error_for_jwt( $result ) {
        $token = self::extract_token_from_headers();
        if ( ! $token ) {
            return $result;
        }
 
        $payload = self::decode_token( $token );
        if ( is_wp_error( $payload ) ) {
            return $result;
        }
 
        $token_user_id = isset( $payload['data']['user']['id'] ) ? (int) $payload['data']['user']['id'] : 0;
 
        // WordPress rest_cookie_check_errors (p100) может вызвать
        // wp_set_current_user(0) когда есть cookie без nonce,
        // и вернуть true (не WP_Error!) — пользователь теряется.
        // Восстанавливаем пользователя из JWT.
        if ( $token_user_id > 0 && get_current_user_id() === 0 ) {
            wp_set_current_user( $token_user_id );
            error_log( 'PW_JWT bypass: user was reset to 0 by cookie check, restored to ' . $token_user_id );
        }
 
        // Если $result — WP_Error (cookie ошибка), очищаем
        if ( is_wp_error( $result ) ) {
            error_log( 'PW_JWT bypass: clearing WP_Error ' . $result->get_error_code() );
            return true;
        }
 
        return $result;
    }
 
    /**
     * CORS заголовки для Authorization.
     */
    public static function add_cors_headers( $served ): bool {
        header( 'Access-Control-Allow-Headers: Authorization, Content-Type' );
        return $served;
    }
 
    private static function base64url_encode( string $data ): string {
        return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
    }
 
    private static function base64url_decode( string $data ): string {
        return base64_decode( strtr( $data, '-_', '+/' ) );
    }
}
