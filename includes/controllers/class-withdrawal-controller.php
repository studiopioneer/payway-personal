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
final class WithdrawalController extends BaseController
{
    /**
     * Список допустимых типов оплаты
     *
     * @var array
     */
    private array $allowed_payment_types = ['swift', 'cards', 'cryptocurrency'];

    /**
     * Имя таблицы в базе данных
     *
     * @var string
     */
    protected string $table_name = "payway_withdrawal";

    /**
     * Валидация входных данных
     *
     * @return bool Возвращает true, если все проверки прошли успешно
     */
    protected function validate_request_data(): bool
    {
        $parameters = $this->request->get_json_params();

        // Проверка наличия payment_type
        if (empty($parameters['payment_type'])) {
            $this->errors[] = 'Поле payment_type является обязательным.';
        } elseif (!in_array($parameters['payment_type'], $this->allowed_payment_types, true)) {
            $this->errors[] = 'Недопустимый тип payment_type. Возможные значения: ' . implode(', ', $this->allowed_payment_types) . '.';
        }

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
            'payment_type' => sanitize_text_field($parameters['payment_type']),
            'payment_details' => sanitize_text_field($parameters['payment_details'] ?? ''),
            'amount' => floatval($parameters['amount']),
            'comments' => sanitize_text_field($parameters['comments'] ?? ''),
            'time' => current_time('mysql', 1),
            'status' => 'review',
        ];
    }


    /**
     * Переопределение обработки создания заявки на вывод
     * Добавлена проверка баланса пользователя перед созданием заявки
     *
     * @return WP_REST_Response
     */
    public function handle_create_request(): WP_REST_Response
    {
        // Проверяем права доступа
        if (!$this->check_permissions()) {
            return $this->send_error('У вас нет прав для доступа к этой функции.');
        }

        // Проверяем валидность данных запроса
        if (!$this->validate_request_data()) {
            return $this->send_error('Неверные данные запроса: ' . implode(', ', $this->errors));
        }

        // Проверяем баланс пользователя
        $user_id = get_current_user_id();
        $balance = floatval(get_user_meta($user_id, 'payway_withdrawal_balance', true));
        $parameters = $this->request->get_json_params();
        $amount = floatval($parameters['amount']);

        if ($amount > $balance) {
            return $this->send_error(
                sprintf('Недостаточно средств. Ваш баланс: $%.2f', $balance),
                422
            );
        }

        // Очищаем данные
        $data = $this->sanitize_request_data();

        // Сохраняем заявку
        if ($this->save_request_data($data)) {
            return $this->send_success(['message' => 'Заявка успешно создана!', 'data' => $data]);
        }

        return $this->send_error('Произошла ошибка при обработке вашего запроса.');
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
                'payment_type' => $item['payment_type'],
                'status' => $item['status'],
                'rejected_comment' => $item['review_comments'] ?? 'Отклонен без обоснования',
                'comments' => $item['comments'] ?? 'Нет примечания',
            ];
        }
        return $response_data;
    }
}