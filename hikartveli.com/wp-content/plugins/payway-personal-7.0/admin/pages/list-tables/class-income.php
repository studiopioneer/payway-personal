<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Income_List_Table extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => 'earning',
            'plural'   => 'earnings',
            'ajax'     => false,
        ]);
    }

    public function get_columns(): array
    {
        return [
            'cb'         => '<input type="checkbox" />',
            'email'      => 'Email',
            'earnings'   => 'Сумма (USD)',
            'period'     => 'Период',
            'status'     => 'Статус',
            'created_at' => 'Дата загрузки',
        ];
    }

    public function get_sortable_columns(): array
    {
        return [
            'email'      => ['email', false],
            'earnings'   => ['earnings', false],
            'period'     => ['period', false],
            'status'     => ['status', false],
            'created_at' => ['created_at', true],
        ];
    }

    /**
     * Checkbox column for bulk actions.
     */
    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="earning_ids[]" value="%d" />', absint($item['id']));
    }

    /**
     * Email column with row action "Delete".
     */
    public function column_email($item)
    {
        $delete_url = wp_nonce_url(
            add_query_arg([
                'page'   => 'payway-income',
                'action' => 'delete',
                'id'     => $item['id'],
            ], admin_url('admin.php')),
            'payway_delete_income_' . $item['id']
        );

        $actions = [
            'delete' => sprintf(
                '<a href="%s" style="color:#b32d2e;" onclick="return confirm(\'Удалить эту запись и списать $%s с баланса пользователя?\')">Удалить</a>',
                esc_url($delete_url),
                number_format((float)$item['earnings'], 2)
            ),
        ];

        return esc_html($item['email']) . $this->row_actions($actions);
    }

    /**
     * Bulk actions dropdown.
     */
    public function get_bulk_actions(): array
    {
        return [
            'bulk_delete' => 'Удалить выбранные',
        ];
    }

    public function prepare_items(): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'payway_earnings';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            $this->items = [];
            return;
        }

        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        // Sorting
        $orderby = isset($_GET['orderby']) ? sanitize_sql_orderby($_GET['orderby']) : 'created_at';
        $order   = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';

        $allowed_columns = ['email', 'earnings', 'period', 'status', 'created_at'];
        if (!in_array($orderby, $allowed_columns)) {
            $orderby = 'created_at';
        }

        $total_items = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $this->items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);

        $this->_column_headers = [
            $this->get_columns(),
            [],
            $this->get_sortable_columns(),
        ];
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'earnings':
                return '$' . number_format((float)$item['earnings'], 2);
            case 'period':
                return esc_html($item['period']);
            case 'status':
                if ($item['status'] === 'credited') {
                    return '<span style="color:green; font-weight:bold;">Начислено</span>';
                } elseif ($item['status'] === 'not_found') {
                    return '<span style="color:red; font-weight:bold;">Email не найден</span>';
                }
                return esc_html($item['status']);
            case 'created_at':
                return esc_html($item['created_at']);
            default:
                return '';
        }
    }

    public function no_items(): void
    {
        echo 'Нет данных о начислениях. Загрузите CSV-файл.';
    }
}
