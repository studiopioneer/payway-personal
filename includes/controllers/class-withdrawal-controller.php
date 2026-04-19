<?php
 
if (!defined('ABSPATH')) {
    exit;
}
 
/**
 * Контроллер для обработки заявок на вывод средств через REST API
 *
 * v5.5-patch: дифференцированный тариф крипты по дате регистрации.
 * Регистрация до 07.04.2026 → cryptocurrency 10 (10%)
 * Регистрация с 07.04.2026  → cryptocurrency 11 (11%)
 */
final class WithdrawalController extends BaseController
{
    /**
     * Дата отсечки: пользователи, зарег. ДО этой даты, получают тариф 10%
     */
    const TARIFF_CUTOFF_DATE = '2026-04-07';
 
    /**
     * Список допустимых типов оплаты (base-типы, без суффикса тарифа)
     */
    private array $allowed_payment_types = ['swift', 'cards', 'cryptocurrency'];
 
    /**
     * Имя таблицы в базе данных
     */
    protected string $table_name = "payway_withdrawal";
 
    /**
     * Определяет тариф криптовалюты для текущего пользователя
     * на основании даты регистрации.
     *
     * @return int 10 или 11
     */
    private function get_crypto_tariff(): int
    {
        $user = get_userdata(get_current_user_id());
        if (!$user) {
            return 11;
        }
        $cutoff     = strtotime(self::TARIFF_CUTOFF_DATE . ' 00:00:00');
        $registered = strtotime($user->user_registered);
        return ($registered < $cutoff) ? 10 : 11;
    }
 
    /**
     * Валидация входных данных
     */
    protected function validate_request_data(): bool
    {
        $parameters = $this->request->get_json_params();
 
        // Принимаем 'cryptocurrency', 'cryptocurrency 10', 'cryptocurrency 11', 'swift', 'cards'
        if (empty($parameters['payment_type'])) {
            $this->errors[] = 'Поле payment_type является обязательным.';
        } else {
            // Отрезаем суффикс " 10" / " 11", если он уже пришёл с фронта
            $base_type = preg_replace('/\s+\d+$/', '', trim($parameters['payment_type']));
            if (!in_array($base_type, $this->allowed_payment_types, true)) {
                $this->errors[] = 'Недопустимый тип payment_type. Возможные значения: '
                    . implode(', ', $this->allowed_payment_types) . '.';
            }
        }
 
        if (!isset($parameters['amount'])) {
            $this->errors[] = 'Поле amount является обязательным.';
        } elseif (!is_numeric($parameters['amount']) || $parameters['amount'] <= 0) {
            $this->errors[] = 'Поле amount должно быть числом больше 0.';
        }
 
        return empty($this->errors);
    }
 
    /**
     * Сбор и очистка данных из запроса.
     * Для cryptocurrency автоматически добавляется суффикс тарифа.
     */
    protected function sanitize_request_data(): array
    {
        $parameters   = $this->request->get_json_params();
        $raw_type     = sanitize_text_field($parameters['payment_type'] ?? '');
        $base_type    = preg_replace('/\s+\d+$/', '', trim($raw_type));
 
        // Если тип — криптовалюта, определяем тариф по дате регистрации
        if ($base_type === 'cryptocurrency') {
            $tariff       = $this->get_crypto_tariff();
            $payment_type = 'cryptocurrency ' . $tariff;
        } else {
            $payment_type = $base_type;
        }
 
        return [
            'user_id'         => get_current_user_id(),
            'payment_type'    => $payment_type,
            'payment_details' => sanitize_text_field($parameters['payment_details'] ?? ''),
            'amount'          => floatval($parameters['amount']),
            'comments'        => sanitize_text_field($parameters['comments'] ?? ''),
            'time'            => current_time('mysql', 1),
            'status'          => 'review',
        ];
    }
 
    /**
     * Обработка создания заявки с проверкой баланса
     */
    public function handle_create_request(): WP_REST_Response
    {
        if (!$this->check_permissions()) {
            return $this->send_error('У вас нет прав для доступа к этой функции.');
        }
 
        if (!$this->validate_request_data()) {
            return $this->send_error('Неверные данные запроса: ' . implode(', ', $this->errors));
        }
 
        $user_id    = get_current_user_id();
        $balance    = floatval(get_user_meta($user_id, 'payway_withdrawal_balance', true));
        $parameters = $this->request->get_json_params();
        $amount     = floatval($parameters['amount']);
 
        if ($amount > $balance) {
            return $this->send_error(
                sprintf('Недостаточно средств. Ваш баланс: $%.2f', $balance),
                422
            );
        }
 
        $data = $this->sanitize_request_data();
 
        if ($this->save_request_data($data)) {
            return $this->send_success(['message' => 'Заявка успешно создана!', 'data' => $data]);
        }
 
        return $this->send_error('Произошла ошибка при обработке вашего запроса.');
    }
 
    /**
     * Форматирование данных для ответа
     */
    protected function format_response_data(array $items): array
    {
        $response_data = [];
        foreach ($items as $item) {
            $response_data[] = [
                'id'               => $item['id'],
                'time'             => $item['time'],
                'amount'           => $item['amount'],
                'payment_type'     => $item['payment_type'],
                'status'           => $item['status'],
                'rejected_comment' => $item['review_comments'] ?? 'Отклонен без обоснования',
                'comments'         => $item['comments'] ?? 'Нет примечания',
            ];
        }
        return $response_data;
    }
}