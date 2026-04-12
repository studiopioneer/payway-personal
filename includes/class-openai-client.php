<?php
/**
 * Sprint 2: OpenAI API client wrapper
 *
 * Reads API key from WP option 'payway_openai_api_key'.
 * Uses wp_remote_post() with 90 s timeout.
 *
 * @package PaywayPersonal
 */

defined( 'ABSPATH' ) || exit;

class PW_OpenAI_Client {

    private const API_URL = 'https://api.openai.com/v1/chat/completions';
    private const TIMEOUT  = 90; // seconds

    private string $api_key;

    public function __construct() {
        $this->api_key = (string) get_option( 'payway_openai_api_key', '' );
    }

    /**
     * Send a chat/completions request.
     *
     * @param  array  $messages    Array of {role, content} message objects.
     * @param  string $model       OpenAI model identifier.
     * @param  int    $max_tokens  Maximum tokens in the response.
     * @param  float  $temperature Sampling temperature.
     *
     * @return array Decoded JSON response body.
     * @throws RuntimeException On HTTP error or API error.
     */
    public function complete(
        array  $messages,
        string $model       = 'gpt-4o',
        int    $max_tokens  = 2000,
        float  $temperature = 0.3
    ): array {
        if ( empty( $this->api_key ) ) {
            throw new RuntimeException( 'OpenAI API key is not configured.' );
        }

        $body = wp_json_encode( [
            'model'       => $model,
            'messages'    => $messages,
            'max_tokens'  => $max_tokens,
            'temperature' => $temperature,
        ] );

        $response = wp_remote_post( self::API_URL, [
            'timeout' => self::TIMEOUT,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => $body,
        ] );

        if ( is_wp_error( $response ) ) {
            throw new RuntimeException(
                'OpenAI request failed: ' . $response->get_error_message()
            );
        }

        $http_code = wp_remote_retrieve_response_code( $response );
        $raw_body  = wp_remote_retrieve_body( $response );
        $decoded   = json_decode( $raw_body, true );

        if ( $http_code !== 200 ) {
            $err_msg = $decoded['error']['message'] ?? $raw_body;
            throw new RuntimeException(
                "OpenAI API error (HTTP {$http_code}): {$err_msg}"
            );
        }

        if ( ! is_array( $decoded ) ) {
            throw new RuntimeException( 'OpenAI returned non-JSON response.' );
        }

        return $decoded;
    }

    /**
     * Convenience wrapper: send a single user message and return the assistant
     * text content.
     *
     * @param  string $system_prompt System message.
     * @param  string $user_prompt   User message.
     * @param  string $model         OpenAI model identifier.
     * @param  int    $max_tokens    Maximum tokens.
     *
     * @return string Assistant reply text.
     * @throws RuntimeException On error.
     */
    public function ask(
        string $system_prompt,
        string $user_prompt,
        string $model      = 'gpt-4o',
        int    $max_tokens = 2000
    ): string {
        $response = $this->complete(
            [
                [ 'role' => 'system', 'content' => $system_prompt ],
                [ 'role' => 'user',   'content' => $user_prompt   ],
            ],
            $model,
            $max_tokens
        );

        return $response['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Ask for a JSON response and decode it automatically.
     *
     * @param  string $system_prompt System message (should instruct JSON output).
     * @param  string $user_prompt   User message.
     * @param  string $model         OpenAI model identifier.
     * @param  int    $max_tokens    Maximum tokens.
     *
     * @return array Decoded JSON as associative array.
     * @throws RuntimeException On error or invalid JSON.
     */
    public function ask_json(
        string $system_prompt,
        string $user_prompt,
        string $model      = 'gpt-4o',
        int    $max_tokens = 2000
    ): array {
        $text = $this->ask( $system_prompt, $user_prompt, $model, $max_tokens );

        // Strip markdown code fences if present
        $text = preg_replace( '/^```(?:json)?\s*/i', '', trim( $text ) );
        $text = preg_replace( '/\s*```$/', '', $text );

        $data = json_decode( $text, true );
        if ( ! is_array( $data ) ) {
            throw new RuntimeException(
                'OpenAI did not return valid JSON. Raw: ' . substr( $text, 0, 300 )
            );
        }

        return $data;
    }
}
