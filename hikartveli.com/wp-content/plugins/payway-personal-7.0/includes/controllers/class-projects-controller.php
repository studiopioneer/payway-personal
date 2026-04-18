<?php

if (!defined('ABSPATH')) {
    exit; // Защита от прямого доступа
}

/**
 * Контроллер для обработки заявок на создание проектов через REST API
 *
 * @author Alexander Kovalev
 * @copyright (c) 18.10.2024, CreativeMotion
 * @version 1.0
 */
final class ProjectsController extends BaseController
{
    /**
     * Имя таблицы в базе данных
     *
     * @var string
     */
    protected string $table_name = "payway_projects";

    /**
     * Валидация входных данных
     *
     * @return bool Возвращает true, если все проверки прошли успешно
     */
    protected function validate_request_data(): bool
    {
        $parameters = $this->request->get_json_params();

        // Проверка наличия обязательных полей
        if (!isset($parameters['url'])) {
            $this->errors[] = 'Поле url является обязательным.';
        } elseif (!filter_var($parameters['url'], FILTER_VALIDATE_URL)) {
            $this->errors[] = 'Поле url должно быть корректным URL.';
        }

        if (!isset($parameters['amount'])) {
            $this->errors[] = 'Поле amount является обязательным.';
        } elseif (!is_numeric($parameters['amount']) || $parameters['amount'] <= 0) {
            $this->errors[] = 'Поле amount должно быть числом больше 0.';
        }

        if (!isset($parameters['count_users'])) {
            $this->errors[] = 'Поле count_users является обязательным.';
        } elseif (!is_numeric($parameters['count_users']) || $parameters['count_users'] <= 0) {
            $this->errors[] = 'Поле count_users должно быть числом больше 0.';
        }

        if (!isset($parameters['contacts'])) {
            $this->errors[] = 'Поле contacts является обязательным.';
        } elseif (strlen($parameters['contacts']) > 250) {
            $this->errors[] = 'Поле contacts не должно превышать 250 символов.';
        }

        if (isset($parameters['comments']) && mb_strlen($parameters['comments'], 'UTF-8') > 500) {
            $this->errors[] = 'Поле comments не должно превышать 500 символов.';
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
            'user_id' => get_current_user_id(),
            'url' => esc_url_raw($parameters['url']), // Очистка URL
            'amount' => floatval($parameters['amount']), // Преобразование в число
            'count_users' => intval($parameters['count_users']), // Преобразование в целое число
            'comments' => sanitize_text_field($parameters['comments'] ?? ''), // Очистка текста
            'contacts' => sanitize_text_field($parameters['contacts']), // Очистка текста
            'time' => current_time('mysql', 1), // Текущее время
            'status' => 'review', // Статус по умолчанию
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
        $response_data = [];
        foreach ($items as $item) {
            $response_data[] = [
                'id' => $item['id'],
                'time' => $item['time'],
                'url' => $item['url'],
                'amount' => $item['amount'],
                'count_users' => $item['count_users'],
                'rejected_comment' => $item['review_comments'] ?? 'Отклонен без обоснования',
                'comments' => !empty($item['comments']) ? $item['comments'] : 'Нет примечания',
                'contacts' => $item['contacts'],
                'status' => $item['status'],
            ];
        }
        return $response_data;
    }
}