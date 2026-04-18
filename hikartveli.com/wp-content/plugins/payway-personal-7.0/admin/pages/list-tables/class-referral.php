<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Referral_List_Table extends WP_List_Table {
    public function get_columns() {
        return [
            'id'              => 'ID',
            'referrer_email'  => "\xd0\xa0\xd0\xb5\xd1\x84\xd0\xb5\xd1\x80\xd0\xb5\xd1\x80",
            'referral_email'  => "\xd0\xa0\xd0\xb5\xd1\x84\xd0\xb5\xd1\x80\xd0\xb0\xd0\xbb",
            'referral_code'   => "\xd0\x9a\xd0\xbe\xd0\xb4",
            'created_at'      => "\xd0\x94\xd0\xb0\xd1\x82\xd0\xb0",
        ];
    }

    public function get_sortable_columns() {
        return [ 'created_at' => [ 'created_at', true ] ];
    }

    public function prepare_items() {
        global $wpdb;
        $this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
        $table = $wpdb->prefix . 'payway_referrals';
        $data = $wpdb->get_results(
            "SELECT r.*, u.user_email AS referrer_email
             FROM {$table} r
             LEFT JOIN {$wpdb->users} u ON r.referrer_id = u.ID
             ORDER BY r.created_at DESC",
            ARRAY_A
        );
        $this->items = $data ?: [];
    }

    public function column_default( $item, $column_name ) {
        return esc_html( $item[ $column_name ] ?? '' );
    }

    public function column_created_at( $item ) {
        return date_i18n( 'd.m.Y H:i', strtotime( $item['created_at'] ) );
    }
}
