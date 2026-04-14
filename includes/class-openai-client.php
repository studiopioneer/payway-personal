<?php
/**
 * PW_OpenAI_Client — формирование промпта и запрос к gpt-4o
 * Промпт строго по ТЗ §6
 */
class PW_OpenAI_Client {

    private $api_key;
    const API_URL  = 'https://api.openai.com/v1/chat/completions';
    const MODEL    = 'gpt-4o';
    const MAX_RETRY = 1;

    public function __construct() {
        $this->api_key = get_option( 'payway_openai_api_key', '' );
    }

    /**
     * Запускает анализ и возвращает валидированный JSON-ответ.
     *
     * @param array $channel_data   Данные YouTube (channel + videos)
     * @param array $analyzer_data  Результат PW_Audit_Analyzer::analyze()
     * @return array|WP_Error
     */
    public function analyze( array $channel_data, array $analyzer_data ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', 'OpenAI API key не настроен' );
        }

        $user_message = $this->build_user_message( $channel_data, $analyzer_data );
        $messages = [
            [ 'role' => 'system', 'content' => $this->get_system_prompt() ],
            [ 'role' => 'user',   'content' => $user_message ],
        ];

        // Первая попытка
        $result = $this->call_api( $messages );
        if ( is_wp_error( $result ) ) {
            // Retry
            $result = $this->call_api( $messages );
        }

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Валидация JSON-схемы
        $validated = $this->validate_response( $result );
        if ( is_wp_error( $validated ) ) {
            // Retry с явным указанием на ошибку
            $messages[] = [ 'role' => 'assistant', 'content' => json_encode( $result ) ];
            $messages[] = [ 'role' => 'user', 'content' => 'Ответ не соответствует требуемой JSON-схеме. Повтори ответ строго по схеме, без лишних ключей.' ];
            $result2    = $this->call_api( $messages );
            if ( ! is_wp_error( $result2 ) ) {
                $validated = $this->validate_response( $result2 );
            }
        }

        if ( is_wp_error( $validated ) ) {
            return new WP_Error( 'ai_validation_failed', 'AI вернул невалидный формат JSON: ' . $validated->get_error_message() );
        }

        return $validated;
    }

    // ─────────────────────────────────────────────────────────
    // SYSTEM PROMPT (строго по ТЗ §6.1)
    // ─────────────────────────────────────────────────────────

    private function get_system_prompt() {
        return <<<'PROMPT'
Ты — эксперт 