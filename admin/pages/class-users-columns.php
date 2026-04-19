<?php
/**
 * Class for adding additional user columns
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @copyright (c) 17.09.2024, CreativeMotion
 * @version 1.0
 */
 
class PaywayUserColumns
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        // Initialize hooks and filters for adding custom user columns
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_filter('manage_users_columns', [$this, 'add_custom_columns']);
        add_action('manage_users_custom_column', [$this, 'render_custom_column'], 10, 3);
        add_action('pre_get_users', [$this, 'default_sort_by_newest']);
    }
 
    /**
     * Enqueue scripts for the users page
     *
     * @param string $hook Current page hook
     */
    public function enqueue_scripts($hook)
    {
        if ('users.php' === $hook) {
            wp_enqueue_script(
                'payway-users',
                PAYWAY_PLUGIN_URL . '/admin/assets/js/users.js',
                ['jquery'],
                PAYWAY_PLUGIN_VERSION
            );
 
            wp_localize_script('payway-users', 'paywayData', [
                'apiUrl' => rest_url('payway/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
            ]);
 
            wp_enqueue_style(
                'payway-users-styles',
                PAYWAY_PLUGIN_URL . '/admin/assets/css/users.css',
                [],
                PAYWAY_PLUGIN_VERSION
            );
        }
    }
 
    /**
     * Add custom columns to the user table
     *
     * @param array $columns Array of existing columns
     *
     * @return array Modified array of columns
     */
    public function add_custom_columns($columns): array
    {
        return array_merge($columns, [
            //'payway_balance'            => __( 'Payway Balance' ),
            'payway_balance' => __(''),
            'payway_withdrawal_balance' => __('Payway Withdrawal Balance'),
            'payway_projects' => __(''),
        ]);
    }
 
    /**
     * Render content for each custom column
     *
     * @param string $val Column value
     * @param string $column_name Column name
     * @param int $user_id User ID
     *
     * @return string Returns HTML to display in the column
     */
    public function render_custom_column($val, $column_name, $user_id)
    {
        if ('payway_withdrawal_balance' === $column_name) {
            $balance = (float)get_user_meta($user_id, 'payway_withdrawal_balance', true);
            // FIX v5.5: восстановлен класс payway-user-withdrawal-balance-input,
            // который слушает users.js для сохранения изменений через AJAX.
            // Классы payway-balance-positive / payway-balance-zero сохранены для стилей.
            $style_class = $balance > 0 ? 'payway-balance-positive' : 'payway-balance-zero';
            return '<input type="text" class="payway-user-withdrawal-balance-input ' . esc_attr($style_class) . '"
                            data-default-balance="' . esc_attr($balance) . '"
                            data-user-id="' . esc_attr($user_id) . '"
                            min="0"
                            value="' . esc_attr($balance) . '">';
        } else if ('payway_balance' === $column_name) {
            $balance = (float)get_user_meta($user_id, 'payway_balance', true);
            return '
                <div class="payway-balance-container">
                    <span class="payway-balance-value">' . esc_html($balance) . '</span>
                    <select class="payway-month-selector" data-user-id="' . esc_attr($user_id) . '">
                        <option value=""> </option>
                    </select>
                </div>
            ';
        } else if ('payway_projects' === $column_name) {
            global $wpdb;
            $table = $wpdb->prefix . 'payway_projects';
            $projects = $wpdb->get_results($wpdb->prepare(
                "SELECT url, status FROM {$table} WHERE user_id = %d ORDER BY time DESC",
                $user_id
            ));
 
            if (empty($projects)) {
                return $val;
            }
 
            $links = [];
            $status_colors = [
                'approved' => '#28a745',
                'review'   => '#007bff',
                'rejected' => '#dc3545',
            ];
 
            foreach ($projects as $project) {
                $url = esc_url($project->url);
                $color = isset($status_colors[$project->status]) ? $status_colors[$project->status] : '#666';
 
                // Extract display name from URL
                $display = $project->url;
                if (preg_match('#youtube\.com|youtu\.be#i', $project->url)) {
                    $display = preg_replace('#^https?://(www\.)?#i', '', $project->url);
                    $display = rtrim($display, '/');
                } elseif (preg_match('#play\.google\.com/store/apps/details\?id=([^&]+)#i', $project->url, $m)) {
                    $display = $m[1];
                } else {
                    $parsed = parse_url($project->url);
                    $display = isset($parsed['host']) ? $parsed['host'] : $project->url;
                    if (isset($parsed['path']) && $parsed['path'] !== '/') {
                        $display .= rtrim($parsed['path'], '/');
                    }
                }
 
                $links[] = '<a href="' . $url . '" target="_blank" style="color:' . esc_attr($color) . ';text-decoration:none;font-weight:500;">' . esc_html($display) . '</a>';
            }
 
            return implode('<br>', $links);
        }
 
        return $val;
    }
 
    /**
     * Default sort users by newest first
     */
    public function default_sort_by_newest($query)
    {
        if (is_admin() && !isset($_GET['orderby'])) {
            $query->set('orderby', 'registered');
            $query->set('order', 'DESC');
        }
    }
}
 
new PaywayUserColumns();
