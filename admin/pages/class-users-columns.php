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
            'payway_balance' => __('Баланс'),
            'payway_withdrawal_balance' => __('Payway Withdrawal Balance'),
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
    public function render_custom_column($val, $column_name, $user_id): string
    {
        if ('payway_withdrawal_balance' === $column_name) {

            $balance = (float)get_user_meta($user_id, $column_name, true);

            $class = "payway-user-" . str_replace(['payway_', '_'], ['', '-'], $column_name) . "-input";

            return '<input type="text" class="' . esc_attr($class) . '" 
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
                        <option value="">Выберите месяц</option>
                    </select>
                </div>
            ';
        }

        return $val;
    }
}


new PaywayUserColumns();