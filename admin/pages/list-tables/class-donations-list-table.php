<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
 
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
 
/**
 * DonationsListTable — таблица донатов в WP Admin
 *
 * @package Payway
 * @version 4.9
 */
class DonationsListTable extends WP_List_Table {
 
    public function __construct() {
        parent::__construct( [
            'singular' => 'donation',
            'plural'   => 'donations',
            'ajax'     => false,
        ] );
    }
 
    public function get_columns() {
        return [
            'id'         => 'ID',
            'user'       => 'Пользователь',
            'amount'     => 'Сумма',
            'message'    => 'Сообщение',
            'created_at' => 'Дата',
        ];
    }
 
    protected function get_sortable_columns() {
        return [
            'amount'     => [ 'amount',     false ],
            'created_at' => [ 'created_at', true  ],
        ];
    }
 
    protected function column_default( $item, $column_name ) {
        return esc_html( $item[ $column_name ] ?? '—' );
    }
 
    protected function column_id( $item ) {
        return '#' . (int) $item['id'];
    }
 
    protected function column_user( $item ) {
        $name  = esc_html( $item['display_name'] ?? '—' );
        $email = esc_html( $item['user_email']   ?? '' );
        return $name . ( $email ? '<br><small style="color:#888">' . $email . '</small>' : '' );
    }
 
    protected function column_amount( $item ) {
        return '<strong style="color:#16a34a">$' . number_format( (float) $item['amount'], 2 ) . '</strong>';
    }
 
    protected function column_message( $item ) {
        $msg = trim( $item['message'] ?? '' );
        return $msg ? esc_html( $msg ) : '<span style="color:#ccc">—</span>';
    }
 
    protected function column_created_at( $item ) {
        $ts = strtotime( $item['created_at'] );
        return $ts ? date_i18n( 'd.m.Y H:i', $ts ) : '—';
    }
 
    public function prepare_items() {
        global $wpdb;
 
        $table      = $wpdb->prefix . 'payway_donations';
        $users_tbl  = $wpdb->users;
        $per_page   = 25;
        $current    = $this->get_pagenum();
        $offset     = ( $current - 1 ) * $per_page;
 
        // Сортировка
        $orderby = in_array( $_GET['orderby'] ?? '', [ 'amount', 'created_at' ] )
            ? sanitize_sql_orderby( $_GET['orderby'] )
            : 'created_at';
        $order   = strtoupper( $_GET['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';
 
        // Получаем строки с JOIN на wp_users
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results(
            "SELECT d.*, u.display_name, u.user_email
             FROM {$table} d
             LEFT JOIN {$users_tbl} u ON u.ID = d.user_id
             ORDER BY d.{$orderby} {$order}
             LIMIT {$per_page} OFFSET {$offset}",
            ARRAY_A
        );
        // phpcs:enable
 
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
 
        $this->items = $rows ?: [];
 
        $this->set_pagination_args( [
            'total_items' => $total,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total / $per_page ),
        ] );
 
        $this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
    }
 
    protected function display_tablenav( $which ) {
        parent::display_tablenav( $which );
 
        if ( $which === 'bottom' ) {
            global $wpdb;
            $table        = $wpdb->prefix . 'payway_donations';
            $total_amount = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table}" );
            $total_count  = (int)   $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
            echo '<div style="padding:10px 0;font-size:13px;color:#555">'
                . 'Всего донатов: <strong>' . $total_count . '</strong>'
                . ' &nbsp;·&nbsp; Итоговая сумма: '
                . '<strong style="color:#16a34a">$' . number_format( $total_amount, 2 ) . '</strong>'
                . '</div>';
        }
    }
}
