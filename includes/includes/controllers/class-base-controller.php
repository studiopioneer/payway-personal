<?php
if (!defined('ABSPATH')) {
    exit; // Защита от прямого доступа
}

/**
 * Абстрактный базовый контроллер для обработки запросов через REST API
 *
 * @author Alexander Kovalev
 * @copyright (c) 18.10.2024, CreativeMotion
 * @version 1.0
 */
abstract class BaseController
{
    /**
     * Экземпляр запроса WP_REST_Request
     *
     * @var WP_REST_Request
     */
    protected WP_REST_Request $request;

    /**
     * Имя таблицы в базе данных
     *
     * @var string
     */
    protected string $table_name;

    /**
     * Массив для хранения сообщений об ошибках валидации
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * Конструктор класса
     *
     * @param WP_REST_Request $request Объект текущего REST API запроса
     */
    public function __construct(WP_REST_Request $request)
    {
        $this->request = $request;
    }

    /**
     * Проверка прав доступа пользователя
     *
     * @return bool Вернет true, если у пользователя есть доступ
     */
    public function check_permissions(): bool
    {
        return is_user_logged_in() && current_user_can('read');
    }

    /**
     * Главный метод для обработки запроса на создание
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

        // Очищаем данные
        $data = $this->sanitize_request_data();

        // Сохраняем заявку
        if ($this->save_request_data($data)) {
            return $this->send_success(['message' => 'Заявка успешно создана!', 'data' => $data]);
        }

        return $this->send_error('Произошла ошибка при обработке вашего запроса.');
    }

    /**
     * Главный метод для обработки запроса на удаление записи
     *
     * @return WP_REST_Response
     */
    public function handle_delete_request(): WP_REST_Response
    {
        // Проверяем права доступа
        if (!$this->check_permissions()) {
            return $this->send_error('У вас нет прав для доступа к этой функции.');
        }

        // Получаем параметры из запроса
        $id = (int)$this->request->get_param('id');

        // Проверяем, передан ли ID и является ли он числовым
        if (empty($id)) {
            return $this->send_error('Некорректный ID для удаления!');
        }

        global $wpdb;

        // Формируем SQL-запрос для удаления записи
        $table = $wpdb->prefix . $this->table_name;
        $deleted = $wpdb->delete($table, ['id' => (int)$id], ['%d']);

        // Проверяем успешность удаления
        if ($deleted === false) {
            return $this->send_error('Не удалось удалить запись. Попробуйте позже.');
        }

        if ($deleted === 0) {
            return $this->send_error('Запись с указанным ID не найдена.');
        }

        return $this->send_success(['message' => 'Запись успешно удалена.']);
    }

    /**
     * Главный метод для обработки запроса на получение данных
     *
     * @return WP_REST_Response
     */
    public function handle_get_request(): WP_REST_Response
    {
        // Проверяем права доступа
        if (!$this->check_permissions()) {
            return $this->send_error('У вас нет прав для доступа к этой функции.');
        }

        global $wpdb;

        // Получаем параметры запроса
        $params = $this->get_request_params();

        // Вычисляем смещение для пагинации
        $offset = ($params['page'] - 1) * $params['per_page'];

        // Формируем SQL-запрос с учетом сортировки и пагинации
        $query = sprintf(
            'SELECT * FROM %s WHERE user_id = %%d ORDER BY %s %s LIMIT %%d OFFSET %%d',
            $wpdb->prefix . $this->table_name,
            $params['order_by'],
            $params['order']
        );

        // Подготавливаем запрос с безопасными параметрами
        $prepared_query = $wpdb->prepare(
            $query,
            get_current_user_id(),
            $params['per_page'],
            $offset
        );

        // Получаем данные
        $records = $wpdb->get_results($prepared_query, ARRAY_A);

        // Получаем общее количество записей для пагинации
        $total_records = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . $wpdb->prefix . $this->table_name . ' WHERE user_id = %d',
                get_current_user_id()
            )
        );

        // Форматируем данные для ответа
        $response_data = $this->format_response_data($records);

        // Возвращаем успешный ответ с данными и мета-информацией для пагинации
        return $this->send_success([
            'message' => 'Данные успешно получены.',
            'data' => $response_data,
            'meta' => [
                'page' => $params['page'],
                'per_page' => $params['per_page'],
                'total_records' => $total_records,
                'total_pages' => ceil($total_records / $params['per_page']),
            ],
        ]);
    }

    /**
     * Получение и валидация параметров запроса
     *
     * @return array
     */
    protected function get_request_params(): array
    {
        $page = (int)$this->request->get_param('page') ?? 1;
        $per_page = (int)$this->request->get_param('per_page') ?? 10;
        $order_by = $this->request->get_param('order_by') ?? 'time';
        $order = strtoupper($this->request->get_param('order')) === 'ASC' ? 'ASC' : 'DESC';

        // Валидация имени столбца для сортировки
        $allowed_columns = ['time', 'amount', 'payment_type', 'status']; // Пример допустимых столбцов
        if (!in_array($order_by, $allowed_columns, true)) {
            $order_by = 'time'; // Значение по умолчанию, если передан недопустимый столбец
        }

        return [
            'page' => max(1, $page), // Страница не может быть меньше 1
            'per_page' => max(1, $per_page), // Количество записей на странице не может быть меньше 1
            'order_by' => $order_by,
            'order' => $order,
        ];
    }

    /**
     * Сохранение данных в базу данных
     *
     * @param array $data Данные для сохранения
     * @return bool Возвращает true, если запись успешно добавлена
     */
    protected function save_request_data(array $data): bool
    {
        global $wpdb;

        return (bool)$wpdb->insert(
            $wpdb->prefix . $this->table_name,
            $data
        );
    }

    /**
     * Валидация входных данных
     *
     * @return bool Возвращает true, если все проверки прошли успешно
     */
    abstract protected function validate_request_data(): bool;

    /**
     * Сбор и очистка данных из запроса
     *
     * @return array Ассоциативный массив очищенных данных
     */
    abstract protected function sanitize_request_data(): array;

    /**
     * Форматирование данных для ответа
     *
     * @param array $items Массив записей из базы данных
     * @return array Массив данных для ответа
     */
    abstract protected function format_response_data(array $items): array;

    /**
     * Отправка успешного ответа
     *
     * @param array $data Ответные данные
     * @return WP_REST_Response
     */
    protected function send_success(array $data): WP_REST_Response
    {
        $response = array_merge([
            'success' => true,
            'message' => $data['message'] ?? '', // Поддержка сообщения по умолчанию
        ], $data);

        return new WP_REST_Response($response, 200);
    }


    /**
     * Отправка ответа с ошибкой
     *
     * @param string $message Сообщение об ошибке
     * @param int $status_code Код статуса HTTP (по умолчанию 400)
     * @return WP_REST_Response
     */
    protected function send_error(string $message, int $status_code = 400): WP_REST_Response
    {
        return new WP_REST_Response([
            'success' => false,
            'message' => $message,
        ], $status_code);
    }
}