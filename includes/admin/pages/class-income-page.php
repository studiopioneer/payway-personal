<?php
namespace Payway\Pages;

if (!defined('ABSPATH')) exit;

class Income_Page extends AbstractAdminPage
{
    protected function init_page_settings(): void {
        $this->page_title         = 'Доход';
        $this->menu_title         = 'Доход';
        $this->menu_slug          = 'payway-income';
        $this->columns            = [
            'email'      => 200,
            'earnings'   => 120,
            'period'     => 180,
            'status'     => 100,
        ];
        $this->list_table_file      = 'class-income.php';
        $this->list_table_class_name = 'Income_List_Table';
        $this->db_table_name         = 'payway_earnings';
    }

    /**
     * Renders the admin page with CSV upload form and results table.
     */
    public function render_admin_page(): void
    {
        // Ensure DB table exists
        $this->maybe_create_table();

        // Handle CSV upload
        $upload_result = null;
        if (isset($_POST['payway_import_income_csv']) && check_admin_referer('payway_import_income_csv')) {
            $upload_result = $this->handle_csv_upload();
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->page_title) . '</h1>';

        // Show delete notices
        if (isset($_GET['deleted'])) {
            if ($_GET['deleted'] === 'true') {
                $count = isset($_GET['count']) ? absint($_GET['count']) : 1;
                $amount = isset($_GET['reversed']) ? floatval($_GET['reversed']) : 0;
                $msg = "Удалено записей: {$count}.";
                if ($amount > 0) {
                    $msg .= " Списано с балансов: $" . number_format($amount, 2);
                }
                echo '<div class="notice notice-success"><p>' . esc_html($msg) . '</p></div>';
            } elseif ($_GET['deleted'] === 'false') {
                echo '<div class="notice notice-error"><p>Не удалось удалить запись!</p></div>';
            }
        }

        // Show upload results
        if ($upload_result !== null) {
            $this->render_upload_results($upload_result);
        }

        // CSV Upload Form
        $this->render_csv_form();

        // List table with earnings history
        $this->render_list_table();

        echo '</div>';
    }

    /**
     * Override base class delete to add balance reversal.
     * Handles single delete with nonce verification.
     */
    public function handle_delete_action(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Single delete
        if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
            $item_id = absint($_GET['id']);
            if (!$item_id) return;

            // Verify nonce
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'payway_delete_income_' . $item_id)) {
                wp_die('Ошибка безопасности. Попробуйте ещё раз.');
            }

            $reversed = $this->delete_earning_with_reversal($item_id);

            wp_redirect(add_query_arg([
                'deleted'  => $reversed !== false ? 'true' : 'false',
                'count'    => 1,
                'reversed' => $reversed !== false ? $reversed : 0,
            ], remove_query_arg(['action', 'id', '_wpnonce'])));
            exit;
        }

        // Bulk delete
        if (isset($_GET['action']) && $_GET['action'] === 'bulk_delete'
            || isset($_GET['action2']) && $_GET['action2'] === 'bulk_delete') {

            if (empty($_GET['earning_ids']) || !is_array($_GET['earning_ids'])) {
                return;
            }

            $ids = array_map('absint', $_GET['earning_ids']);
            $total_reversed = 0;
            $count = 0;

            foreach ($ids as $id) {
                $reversed = $this->delete_earning_with_reversal($id);
                if ($reversed !== false) {
                    $total_reversed += $reversed;
                    $count++;
                }
            }

            wp_redirect(add_query_arg([
                'deleted'  => $count > 0 ? 'true' : 'false',
                'count'    => $count,
                'reversed' => $total_reversed,
            ], remove_query_arg(['action', 'action2', 'earning_ids', '_wpnonce'])));
            exit;
        }
    }

    /**
     * Deletes a single earning record and reverses the balance if it was credited.
     *
     * @param int $id Record ID
     * @return float|false Amount reversed, or false on failure
     */
    private function delete_earning_with_reversal(int $id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->db_table_name;

        // Get record before deleting
        $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
        if (!$record) {
            return false;
        }

        $reversed_amount = 0;

        // Reverse balance only for credited records with a valid user
        if ($record['status'] === 'credited' && $record['user_id'] > 0) {
            $earnings = (float) $record['earnings'];
            $current_balance = (float) get_user_meta($record['user_id'], 'payway_withdrawal_balance', true);
            $new_balance = max(0, $current_balance - $earnings);
            update_user_meta($record['user_id'], 'payway_withdrawal_balance', $new_balance);
            $reversed_amount = $earnings;
        }

        // Delete the record
        $deleted = $wpdb->delete($table_name, ['id' => $id], ['%d']);

        return $deleted ? $reversed_amount : false;
    }

    /**
     * Renders the CSV upload form.
     */
    private function render_csv_form(): void
    {
        echo '<div class="card" style="max-width:600px; margin: 20px 0; padding: 15px;">';
        echo '<h2>Загрузка доходов из CSV</h2>';
        echo '<p>Формат CSV: <code>User (email), Earnings (USD), Period</code></p>';
        echo '<form method="post" enctype="multipart/form-data">';
        wp_nonce_field('payway_import_income_csv');
        echo '<input type="file" name="income_csv" accept=".csv" required style="margin-bottom:10px;" /><br>';
        echo '<input type="submit" name="payway_import_income_csv" class="button button-primary" value="Загрузить CSV" />';
        echo '</form>';
        echo '</div>';
    }

    /**
     * Handles CSV file upload and processing.
     */
    private function handle_csv_upload(): array
    {
        $result = [
            'processed' => 0,
            'skipped'   => 0,
            'not_found' => [],
            'empty_earnings' => [],
            'errors'    => [],
            'success'   => [],
        ];

        if (!isset($_FILES['income_csv']) || $_FILES['income_csv']['error'] !== UPLOAD_ERR_OK) {
            $result['errors'][] = 'Ошибка загрузки файла.';
            return $result;
        }

        $file = $_FILES['income_csv']['tmp_name'];
        $handle = fopen($file, 'r');

        if (!$handle) {
            $result['errors'][] = 'Не удалось открыть файл.';
            return $result;
        }

        // Read and validate header
        $header = fgetcsv($handle);
        if (!$header || count($header) < 3) {
            $result['errors'][] = 'Неверный формат CSV. Ожидается: User (email), Earnings (USD), Period';
            fclose($handle);
            return $result;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . $this->db_table_name;

        // Aggregate earnings per email (in case of duplicates)
        $earnings_map = [];
        $period = '';

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) continue;

            $email    = trim(strtolower($row[0]));
            $earnings = trim($row[1]);
            $period   = trim($row[2]);

            if (empty($email)) continue;

            if ($earnings === '' || $earnings === null) {
                $result['empty_earnings'][] = $email;
                continue;
            }

            $earnings_val = floatval(str_replace(',', '.', $earnings));

            if (!isset($earnings_map[$email])) {
                $earnings_map[$email] = ['amount' => 0, 'period' => $period];
            }
            $earnings_map[$email]['amount'] += $earnings_val;
        }
        fclose($handle);

        // Process each user
        foreach ($earnings_map as $email => $data) {
            $result['processed']++;
            $amount = $data['amount'];
            $period = $data['period'];

            // Find user by email
            $user = get_user_by('email', $email);
            if (!$user) {
                $result['not_found'][] = $email;
                $result['skipped']++;

                // Still save to DB with status 'not_found'
                $wpdb->insert($table_name, [
                    'email'      => $email,
                    'user_id'    => 0,
                    'earnings'   => $amount,
                    'period'     => $period,
                    'status'     => 'not_found',
                    'created_at' => current_time('mysql'),
                ]);
                continue;
            }

            // Get current balance and add earnings
            $current_balance = (float) get_user_meta($user->ID, 'payway_withdrawal_balance', true);
            $new_balance = $current_balance + $amount;
            update_user_meta($user->ID, 'payway_withdrawal_balance', $new_balance);

            // Save to DB
            $wpdb->insert($table_name, [
                'email'      => $email,
                'user_id'    => $user->ID,
                'earnings'   => $amount,
                'period'     => $period,
                'status'     => 'credited',
                'created_at' => current_time('mysql'),
            ]);

            $result['success'][] = $email . ' (+$' . number_format($amount, 2) . ' → $' . number_format($new_balance, 2) . ')';
        }

        return $result;
    }

    /**
     * Renders upload results as WP admin notices.
     */
    private function render_upload_results(array $result): void
    {
        if (!empty($result['errors'])) {
            echo '<div class="notice notice-error"><p><strong>Ошибки:</strong> ' . implode(', ', array_map('esc_html', $result['errors'])) . '</p></div>';
            return;
        }

        $total = $result['processed'];
        $ok = count($result['success']);
        $skipped = $result['skipped'];
        $empty = count($result['empty_earnings']);

        echo '<div class="notice notice-success"><p>';
        echo "<strong>Загрузка завершена.</strong> Обработано: {$total}, Начислено: {$ok}, Пропущено (email не найден): {$skipped}";
        if ($empty > 0) {
            echo ", Пустые суммы: {$empty}";
        }
        echo '</p></div>';

        if (!empty($result['not_found'])) {
            echo '<div class="notice notice-warning"><p><strong>Email не найдены:</strong> ' . implode(', ', array_map('esc_html', $result['not_found'])) . '</p></div>';
        }

        if (!empty($result['empty_earnings'])) {
            echo '<div class="notice notice-info"><p><strong>Пустые суммы (пропущены):</strong> ' . implode(', ', array_map('esc_html', $result['empty_earnings'])) . '</p></div>';
        }
    }

    /**
     * Renders the list table with earnings history.
     */
    private function render_list_table(): void
    {
        $list_table_path = PAYWAY_PLUGIN_DIR . '/admin/pages/list-tables/' . $this->list_table_file;
        if (file_exists($list_table_path)) {
            require_once $list_table_path;
            $class = $this->list_table_class_name;
            $table = new $class();
            $table->prepare_items();

            echo '<h2 style="margin-top:30px;">История начислений</h2>';
            echo '<form method="get">';
            echo '<input type="hidden" name="page" value="' . esc_attr($this->menu_slug) . '" />';
            $table->display();
            echo '</form>';
        }
    }

    /**
     * Creates the earnings DB table if it doesn't exist.
     */
    private function maybe_create_table(): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->db_table_name;

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL,
            user_id BIGINT(20) NOT NULL DEFAULT 0,
            earnings DECIMAL(11, 2) NOT NULL DEFAULT 0,
            period VARCHAR(100) NOT NULL DEFAULT '',
            status VARCHAR(20) NOT NULL DEFAULT 'credited',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    // Required abstract methods from base class (not used for income page)
    protected function render_edit_form(int $id): void {}
    protected function handle_edit_form(): void {}
}

Income_Page::init();