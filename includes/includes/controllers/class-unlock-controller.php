<?php

if (!defined('ABSPATH')) {
    exit; // Защита от прямого доступа
}

/**
 * Контроллер для обработки заявок на вывод средств через REST API
 *
 * @author Alexander Kovalev
 * @copyright (c) 18.10.2024, CreativeMotion
 * @version 1.0
 */
final class UnlockController extends BaseController
{
    /**
     * Имя таблицы в базе данных
     *
     * @var string
     */
    protected string $table_name = "payway_unlock";

    /**
     * Валидация входных данных
     *
     * @return bool Возвращает true, если все проверки прошли успешно
     */
    protected function validate_request_data(): bool
    {
        $parameters = $this->request->get_json_params();

        // Проверка наличия amount
        if (!isset($parameters['amount'])) {
            $this->errors[] = 'Поле amount является обязательным.';
        } elseif (!is_numeric($parameters['amount']) || $parameters['amount'] <= 0) {
            $this->errors[] = 'Поле amount должно быть числом больше 0.';
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
            'amount' => floatval($parameters['amount']),
            //'comments' => sanitize_text_field($parameters['comments'] ?? ''),
            'time' => current_time('mysql', 1),
            'status' => 'review',
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
                'amount' => $item['amount'],
                'status' => $item['status'],
                'rejected_comment' => $item['review_comments'] ?? 'Отклонен без обоснования',
                'comments' => !empty($item['comments']) ? $item['comments'] : 'Нет примечания',
            ];
        }
        return $response_data;
    }
}