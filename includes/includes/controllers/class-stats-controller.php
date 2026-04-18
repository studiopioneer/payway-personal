<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Контроллер для работы со статистикой проектов
 *
 * Данный класс реализует функционал для взаимодействия с базой данных,
 * выполняет обработку запросов, валидацию входных данных и возвращает статистику
 * с поддержкой пагинации для связанных проектов пользователей.
 * @author  Alex Kovalev <alex.kovalevv@gmail.com> <Telegram:@alex_kovalevv>
 * @copyright (c) 14.02.2025, CreativeMotion
 */
final class StatsController extends BaseController
{
    /**
     * Имя основной таблицы статистики
     *
     * @var string
     */
    protected string $table_name = "payway_stats";

    /**
     * Массив допустимых полей для статистики
     *
     * Используется для фильтрации данных при возврате ответа.
     *
     * @var array
     */
    private array $allowed_fields = [
        'id',
        'estimated_earnings_usd',
        'page_views',
        'page_rpm_usd',
        'impressions',
        'impression_rpm_usd',
        'active_view_viewable',
        'clicks',
        'date_start',
        'date_end',
        'site',
        'project_id'
    ];

    /**
     * Валидация параметров запроса на получение статистики
     *
     * Проверяет формат временного диапазона на соответствие формату "Y-m-d".
     *
     * @return bool Возвращает true, если данные корректны, иначе false.
     */
    protected function validate_request_data(): bool
    {
        $params = $this->request->get_params();

        // Валидация временного диапазона
        if (!empty($params['start_date']) && !DateTime::createFromFormat('Y-m-d', $params['start_date'])) {
            $this->errors[] = 'Неверный формат даты начала периода';
        }

        if (!empty($params['end_date']) && !DateTime::createFromFormat('Y-m-d', $params['end_date'])) {
            $this->errors[] = 'Неверный формат даты окончания периода';
        }

        return empty($this->errors);
    }

    /**
     * Санитизация параметров запроса
     *
     * Санитизирует и фильтрует параметры временного диапазона из входного запроса.
     *
     * @return array Возвращает массив с отсанитизированными параметрами.
     */
    protected function sanitize_request_data(): array
    {
        $params = $this->request->get_params();

        return [
            'start_date' => !empty($params['start_date']) && DateTime::createFromFormat('Y-m-d', $params['start_date'])
                ? sanitize_text_field($params['start_date'])
                : null,
            'end_date' => !empty($params['end_date']) && DateTime::createFromFormat('Y-m-d', $params['end_date'])
                ? sanitize_text_field($params['end_date'])
                : null,
        ];
    }

    /**
     * Обработка запроса на получение статистики
     *
     * Метод проверяет права доступа, валидирует входные данные и возвращает статистику
     * с поддержкой пагинации для текущего пользователя.
     *
     * @return WP_REST_Response Ответ с данными статистики или сообщением об ошибке.
     */
    public function handle_get_request(): WP_REST_Response
    {
        // Проверка прав пользователя
        if (!$this->check_permissions()) {
            return $this->send_error('У вас нет прав для доступа к этой функции.');
        }

        // Валидация данных
        if (!$this->validate_request_data()) {
            return $this->send_error('Неверные данные запроса: ' . implode(', ', $this->errors));
        }

        // Получение статистики
        $data = $this->get_stats();

        return $this->send_success($data);
    }

    /**
     * Получение статистики из БД с учетом пагинации
     *
     * Выполняет выборку статистики из базы данных для текущего пользователя
     * с учетом переданных параметров пагинации и фильтров по дате.
     *
     * @return array Возвращает массив с данными статистики и мета-информацией.
     */
    private function get_stats(): array
    {
        global $wpdb;

        // Получаем параметры запроса
        //$params = $this->get_request_params();
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $per_page = isset($params['per_page']) ? (int)$params['per_page'] : 10;
        $sort_field = isset($params['order_by']) ? $params['order_by'] : 'id';
        $sort_order = isset($params['order']) ? $params['order'] : 'DESC';

        // Фильтр по месяцу
        $month = $this->request->get_param('month') ?? null;

        // Рассчитываем смещение
        $offset = ($page - 1) * $per_page;

        // Формируем запрос для получения данных с учетом лимита, смещения и сортировки
        // Fix: build params conditionally so empty $month does not bind to LIMIT slot
        $_stats_params = $month
            ? array( get_current_user_id(), $month, $per_page, $offset )
            : array( get_current_user_id(), $per_page, $offset );
        $query = $wpdb->prepare("
        SELECT 
            stats.*,
            projects.url AS project_url
        FROM {$wpdb->prefix}{$this->table_name} AS stats
        INNER JOIN {$wpdb->prefix}payway_projects AS projects
            ON stats.project_id = projects.id
        WHERE 
            projects.user_id = %d
            " . ($month ? " AND DATE_FORMAT(stats.date_start, '%%Y-%%m') = %s" : "") . "
        ORDER BY stats.{$sort_field} {$sort_order}
        LIMIT %d OFFSET %d",
            $_stats_params
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        // Получаем общее количество записей (без учета лимита и пагинации)
        $total_records_query = $wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->prefix}{$this->table_name} AS stats
        INNER JOIN {$wpdb->prefix}payway_projects AS projects
            ON stats.project_id = projects.id
        WHERE 
            projects.user_id = %d
            " . ($month ? " AND DATE_FORMAT(stats.date_start, '%%Y-%%m') = %s" : ""),
            get_current_user_id(),
            $month
        );
        $total_records = (int)$wpdb->get_var($total_records_query);

        // Форматируем данные
        $formatted_results = $this->format_response_data($results);

        // Возвращаем данные вместе с информацией о пагинации
        return [
            'data' => $formatted_results,
            'meta' => [
                'page' => $page,
                'per_page' => $per_page,
                'total_records' => $total_records,
                'total_pages' => ceil($total_records / $per_page),
                'sortField' => $sort_field,
                'sortOrder' => $sort_order,
            ]
        ];
    }

    /**
     * Получение списка месяцев, за которые существует статистика
     *
     * Метод возвращает массив месяцев в формате "YYYY-MM", за которые есть записи в базе данных
     * для текущего пользователя и его проектов.
     *
     * @return array Массив месяцев в формате "YYYY-MM", отформатированный через format_response_data.
     */
    public function get_available_months(): array
    {
        global $wpdb;

        $user_id = !empty($_GET['user_id']) && current_user_can('manage_options')
            ? $this->request->get_param('user_id')
            : get_current_user_id();

        // Формируем запрос для получения уникальных месяцев и годов из date_start
        $query = $wpdb->prepare("
        SELECT DISTINCT DATE_FORMAT(stats.date_start, '%%Y-%%m') AS month_year
        FROM {$wpdb->prefix}{$this->table_name} AS stats
        INNER JOIN {$wpdb->prefix}payway_projects AS projects
            ON stats.project_id = projects.id
        WHERE 
            projects.user_id = %d
        ORDER BY month_year DESC
    ", $user_id);

        return $wpdb->get_col($query);
    }

    /**
     * Получение статистики за определенный месяц
     *
     * @return array
     */
    public function get_stats_by_month(): array
    {
        global $wpdb;

        // Получаем параметр "month" из запроса
        $month = $this->request->get_param('month') ?? null;

        // Проверяем корректность формата "Y-m"
        if (!$month || !DateTime::createFromFormat('Y-m', $month)) {
            return [
                'error' => 'Неверный формат параметра month. Ожидается формат: YYYY-MM.'
            ];
        }

        // Определяем начало и конец месяца
        $start_date = date('Y-m-01', strtotime($month)); // Первый день месяца
        $end_date = date('Y-m-t', strtotime($month));   // Последний день месяца

        // Формируем SQL-запрос
        $stats_table = $wpdb->prefix . $this->table_name;
        $projects_table = $wpdb->prefix . 'payway_projects';

        $query = $wpdb->prepare("
        SELECT 
            stats.*,
            projects.url AS project_url
        FROM {$stats_table} AS stats
        INNER JOIN {$projects_table} AS projects
            ON stats.project_id = projects.id
        WHERE 
            projects.user_id = %d
            AND stats.date_start BETWEEN %s AND %s
        ORDER BY stats.date_start ASC
    ",
            get_current_user_id(), $start_date, $end_date);

        // Выполнение запроса к базе данных
        $results = $wpdb->get_results($query, ARRAY_A);

        // Проверяем, есть ли ошибка при выполнении запроса
        if ($wpdb->last_error) {
            return [
                'error' => 'Ошибка базы данных: ' . $wpdb->last_error
            ];
        }

        // Если данных нет, возвращаем информацию об отсутствии статистики
        if (empty($results)) {
            return [
                'message' => 'Данные за указанный месяц отсутствуют.'
            ];
        }

        // Форматируем и возвращаем данные
        return $this->format_response_data($results);
    }

    /**
     * Расчет баланса по estimated_earnings_usd за каждый месяц
     *
     * @param string|null $month Месяц в формате "Y-m" (например, "2023-10"). Если null, возвращает баланс за последний месяц.
     * @return float
     */
    public function calculate_monthly_balance(): array
    {
        global $wpdb;

        $user_id = !empty($_GET['user_id']) && current_user_can('manage_options')
            ? $this->request->get_param('user_id')
            : get_current_user_id();
        $month = $this->request->get_param('month') ?? null;

        // Проверяем, что значение "month" корректно
        if ($month && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return $this->format_response_data([
                'error' => 'Некорректный формат месяца. Используйте формат YYYY-MM.'
            ]);
        }

        // Получаем все данные для текущего пользователя
        $query = $wpdb->prepare("
        SELECT 
            stats.estimated_earnings_usd,
            stats.date_start
        FROM {$wpdb->prefix}{$this->table_name} AS stats
        INNER JOIN {$wpdb->prefix}payway_projects AS projects
            ON stats.project_id = projects.id
        WHERE 
            projects.user_id = %d
        ORDER BY stats.date_start ASC
    ", $user_id);

        $results = $wpdb->get_results($query, ARRAY_A);

        // Группируем данные по месяцам
        $balance_by_months = [];
        foreach ($results as $item) {
            $month_key = date('Y-m', strtotime($item['date_start']));
            $earnings = floatval($item['estimated_earnings_usd']);

            if (!isset($balance_by_months[$month_key])) {
                $balance_by_months[$month_key] = 0;
            }
            $balance_by_months[$month_key] += $earnings;
        }

        // Определяем, какой баланс возвращать
        $balance = 0;
        if ($month) {
            $balance = $balance_by_months[$month] ?? 0;
        } elseif (!empty($balance_by_months)) {
            $last_month = array_key_last($balance_by_months);
            $balance = $balance_by_months[$last_month];
        }

        // Подготавливаем данные для возвращения, включая отформатированный результат
        $response_data = [
            'month' => $month ?: 'last_month',
            'balance' => round($balance, 2),
            'details' => $balance_by_months,
        ];

        return $response_data;
    }

    public function get_user_balance()
    {
        $balance = get_user_meta(get_current_user_id(), 'payway_withdrawal_balance', true);
        return $balance;
    }

    /**
     * Получение и валидация параметров запроса
     *
     * Метод теперь фильтрует параметры по разрешенным полям
     * и добавляет фильтр по временным диапазонам.
     *
     * @return array
     */
    protected function get_request_params(): array
    {
        $params = $this->request->get_params();

        // Установка параметров страницы
        $page = max(1, isset($params['page']) ? (int)$params['page'] : 1);
        $per_page = max(1, isset($params['per_page']) ? (int)$params['per_page'] : 10);

        // Поля для сортировки
        $sort_field = isset($params['order_by']) && in_array($params['order_by'], $this->allowed_fields, true)
            ? $params['order_by']
            : 'id';
        $sort_order = isset($params['order']) && in_array(strtoupper($params['order']), ['ASC', 'DESC'], true)
            ? strtoupper($params['order'])
            : 'DESC';

        // Фильтр по месяцу
        $month = isset($params['month']) ? sanitize_text_field($params['month']) : null;

        // Даты (валидируются и санитизируются)
        $date_filters = $this->sanitize_request_data();

        return array_merge($date_filters, [
            'page' => $page,
            'per_page' => $per_page,
            'order_by' => $sort_field,
            'order' => $sort_order,
            'month' => $month,
        ]);
    }

    /**
     * Форматирование данных для ответа
     *
     * Преобразует запись статистики в безопасный и отформатированный вид.
     *
     * @param array $items Массив необработанных записей из базы данных.
     * @return array Отформатированный массив данных.
     */
    protected function format_response_data(array $items): array
    {
        $data = [];

        foreach ($items as $item) {
            $data[] = $this->sanitize_stat_item($item);
        }

        return $data;
    }

    /**
     * Санитизация одной записи статистики
     *
     * Фильтрует данные одной записи, оставляя только допустимые поля и
     * выполняя экранирование значений.
     *
     * @param array $item Запись из базы данных.
     * @return array Санитизированная запись.
     */
    private function sanitize_stat_item(array $item): array
    {
        $clean = [];

        foreach ($this->allowed_fields as $field) {
            if (isset($item[$field])) {
                $clean[$field] = $this->esc_field($field, $item[$field]);
            }
        }

        if (isset($item['project_url'])) {
            $clean['project_url'] = esc_url($item['project_url']);
        }

        return $clean;
    }

    /**
     * Санитизация поля по типу
     */
    private function esc_field(string $field, $value)
    {
        return match ($field) {
            'date_start', 'date_end' => date('Y-m-d', strtotime($value)),
            'estimated_earnings_usd',
            'page_rpm_usd',
            'impression_rpm_usd' => round(floatval($value), 2),
            'active_view_viewable' => round(floatval($value), 2) . '%',
            default => intval($value)
        };
    }

    /**
     * Добавление фильтра по дате в запрос
     */
    private function add_date_filter(array $sanitized_data): string
    {
        global $wpdb;

        $filter = '';

        if (!empty($sanitized_data['start_date'])) {
            $filter .= $wpdb->prepare(" AND stats.date_start >= %s", $sanitized_data['start_date']);
        }

        if (!empty($sanitized_data['end_date'])) {
            $filter .= $wpdb->prepare(" AND stats.date_end <= %s", $sanitized_data['end_date']);
        }

        return $filter;
    }
}