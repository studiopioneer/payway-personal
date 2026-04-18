<?php
/**
 * Projects
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @copyright (c) 15.09.2024, CreativeMotion
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Выход, если напрямую обращаются к файлу
}

// Include the necelssary WordPress files
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Unlock_List_Table extends WP_List_Table {
	public function __construct() {
		parent::__construct( [
			'singular' => 'Data',
			'plural'   => 'Data',
			'ajax'     => false
		] );
	}

	public function get_columns() {
		return [
			'id'           => '#',
			'amount'       => 'Сумма',
			'user_id'      => 'Пользователь',
			'time'         => 'Дата',
			'status'       => 'Статус заказа',
			'actions'  => 'Действия'
		];
	}

	public function get_sortable_columns() {
		return [
			'time' => [ 'time', false ],
		];
	}

	public function prepare_items() {
		global $wpdb;

		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];

		$data = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'payway_unlock', ARRAY_A );
		usort( $data, [ $this, 'usort_reorder' ] );

		$this->items = $data;
	}

	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	public function column_url( $data ) {
		echo '<a href="' . esc_url( $data['url'] ) . '">' . esc_url( $data['url'] ) . '</a>';
	}

	public function column_user_id( $data ) {
		$user_id = (int) $data['user_id'];
		$user    = get_user_by( 'id', $user_id );

		echo '<a href="' . admin_url( '/user-edit.php?user_id=' . $user_id ) . '">' . $user->user_email . '</a>';
	}

	public function column_status( $data ) {
		echo "<select class='payway-status-select' data-entity='unlock' data-default-value='" . esc_attr($data['status']) . "'  data-id='" . esc_attr($data['id']) . "'>
  <option value='approved' " . selected($data['status'], 'approved', false) . ">Подтвержден</option>
  <option value='review' " . selected($data['status'], 'review', false) . ">На проверке</option>
  <option value='rejected' " . selected($data['status'], 'rejected', false) . ">Отклонен</option>
  <option value='paid' " . selected($data['status'], 'paid', false) . ">Выплачено</option>
</select>";
	}

	private function usort_reorder( $a, $b ) {
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'status'; // If no sort, default to title
		$order   = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'asc'; // If no order, default to asc
		$result  = strcmp( $a[ $orderby ], $b[ $orderby ] ); // Determine sort order

		return ( $order === 'asc' ) ? $result : - $result; // Send final sort direction to usort
	}

	public function column_actions( $data ) {
		echo '<a href="#" class="payway-action-delete" data-entity="unlock" data-id="' . esc_attr( $data['id'] ) . '">Удалить</a>';
	}
}