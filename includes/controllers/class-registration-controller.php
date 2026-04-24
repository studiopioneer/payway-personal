<?php
 
if (!defined('ABSPATH')) {
    exit; // Защита от прямого доступа
}
 
/**
 * Контроллер для обработки регистрации пользователей через REST API
 *
 * @author Alexander Kovalev
 * @copyright (c) 18.10.2024, CreativeMotion
 * @version 1.1 — JWT через PW_JWT_Auth (без внешнего плагина)
 */
final class RegistrationController extends BaseController
{
    /**
     * Валидация входных данных для регистрации
     *
     * @return bool Возвращает true, если все проверки прошли успешно
     */
    protected function validate_request_data(): bool
    {
        $parameters = $this->request->get_json_params();
 
        if (!isset($parameters['username'])) {
            $this->errors[] = 'Поле email является обязательным.';
        } elseif (!is_email($parameters['username'])) {
            $this->errors[] = 'Неверный формат email.';
        }
 
        if (!isset($parameters['password'])) {
            $this->errors[] = 'Поле password является обязательным.';
        } elseif (strlen($parameters['password']) < 6) {
            $this->errors[] = 'Пароль должен содержать не менее 6 символов.';
        }
 
        // Проверка, существует ли пользователь с таким email
        if (isset($parameters['username']) && email_exists($parameters['username'])) {
            $this->errors[] = 'Пользователь с таким email уже существует.';
        }
 
        return empty($this->errors);
    }
 
    /**
     * Сбор и очистка данных из запроса
     *
     * @return array Ассоциативный массив очищенных данных
     */
    protected function sanitize_request_data(): array
    {
        $parameters = $this->request->get_json_params();
 
        return [
            'email' => sanitize_email($parameters['username']), // Очистка email
            'password' => sanitize_text_field($parameters['password']), // Очистка пароля
        ];
    }
 
    /**
     * Форматирование данных для ответа
     *
     * @param array $items Массив записей из базы данных
     * @return array Массив данных для ответа
     */
    protected function format_response_data(array $items): array
    {
        // В данном случае форматирование не требуется, так как регистрация возвращает только токен
        return [];
    }
 
    /**
     * Обработка запроса на регистрацию пользователя
     *
     * @return WP_REST_Response
     */
    public function handle_create_request(): WP_REST_Response
    {
        // Проверяем валидность данных запроса
        if (!$this->validate_request_data()) {
            return $this->send_error('Неверные данные запроса: ' . implode(', ', $this->errors));
        }
 
        // Очищаем данные
        $data = $this->sanitize_request_data();
 
        // Создаем пользователя
        $user_id = wp_create_user($data['email'], $data['password'], $data['email']);
 
        if (is_wp_error($user_id)) {
            return $this->send_error('Ошибка регистрации: ' . $user_id->get_error_message());
        }
 
        // Устанавливаем пользователя как текущего и создаем JWT-токен
        // Link referral if cookie exists
        payway_link_referral( $user_id, $data['email'] );
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
 
        // Генерация JWT-токена
        $jwt_data = $this->generate_jwt_token();
 
        if (is_wp_error($jwt_data) || !isset($jwt_data['token'])) {
            return $this->send_error('Ошибка генерации JWT-токена!');
        }
 
        $jwt_token = $jwt_data['token'];
 
        if (!$jwt_token) {
            return $this->send_error('Ошибка генерации JWT-токена.');
        }
 
        // Возвращаем успешный ответ с токеном
        return $this->send_success([
            'message' => 'Пользователь успешно зарегистрирован.',
            'token' => $jwt_token,
        ]);
    }
 
    /**
     * Генерация JWT-токена через встроенный PW_JWT_Auth.
     *
     * @return WP_Error|array|null
     */
    protected function generate_jwt_token(): WP_Error|array|null
    {
        if (!class_exists('PW_JWT_Auth')) {
            return null;
        }
 
        // Используем собственный класс вместо внешнего плагина
        return PW_JWT_Auth::generate_token($this->request);
    }
 
    /**
     * Проверка прав доступа пользователя
     *
     * @return bool Вернет true, если у пользователя есть доступ
     */
    public function check_permissions(): bool
    {
        return true;
    }
}
