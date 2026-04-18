<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Withdrawal_List_Table extends WP_List_Table {

	public function get_columns() {
		return [
			'user_id'         => 'Пользователь',
			'amount'          => 'Сумма',
			'payment_type'    => 'Способ оплаты',
			'payment_details' => 'Реквизиты',
			'comments'        => 'Комментарии',
			'time'            => 'Дата',
			'status'          => 'Статус заказа',
			'actions'         => 'Действия'
		];
	}

	public function get_sortable_columns() {
		return [
			'time'   => [ 'time', false ],
			'amount' => [ 'amount', false ],
			'status' => [ 'status', false ],
		];
	}

	protected function extra_tablenav( $which ) {
		if ( $which !== 'top' ) {
			return;
		}

		$date_from    = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '';
		$date_to      = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '';
		$payment_type = isset( $_GET['payment_type'] ) ? sanitize_text_field( $_GET['payment_type'] ) : '';
		$status       = isset( $_GET['filter_status'] ) ? sanitize_text_field( $_GET['filter_status'] ) : '';

		$types = [
			''               => 'Все способы оплаты',
			'cards'          => 'Cards',
			'cryptocurrency' => 'Cryptocurrency',
			'swift'          => 'Swift',
		];

		$statuses = [
			''         => 'Все статусы',
			'review'   => 'На проверке',
			'approved' => 'Подтвержден',
			'paid'     => 'Выплачено',
			'rejected' => 'Отклонен',
		];

		echo '<div class="alignleft actions">';

		// Date from
		echo '<label for="date_from" class="screen-reader-text">Дата от</label>';
		echo '<input type="date" id="date_from" name="date_from" value="' . esc_attr( $date_from ) . '" placeholder="Дата от" style="width:140px;" />';

		// Date to
		echo '<label for="date_to" class="screen-reader-text">Дата до</label>';
		echo '<input type="date" id="date_to" name="date_to" value="' . esc_attr( $date_to ) . '" placeholder="Дата до" style="width:140px; margin-left:4px;" />';

		// Payment type dropdown
		echo '<select name="payment_type" style="margin-left:4px;">';
		foreach ( $types as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"' . selected( $payment_type, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';

		// Status dropdown
		echo '<select name="filter_status" style="margin-left:4px;">';
		foreach ( $statuses as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"' . selected( $status, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';

		// Hidden fields to preserve page context
		echo '<input type="hidden" name="page" value="payway-withdrawal" />';

		// Apply button
		submit_button( 'Применить', 'action', 'filter_action', false );

		// Reset button
		$reset_url = admin_url( 'admin.php?page=payway-withdrawal' );
		echo ' <a href="' . esc_url( $reset_url ) . '" class="button">Сбросить</a>';

		echo '</div>';
	}

	public function prepare_items() {
		global $wpdb;

		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];

		// Build WHERE clauses
		$where = [];
		$values = [];

		// Date filter
		if ( ! empty( $_GET['date_from'] ) ) {
			$where[] = 'time >= %s';
			$values[] = sanitize_text_field( $_GET['date_from'] ) . ' 00:00:00';
		}
		if ( ! empty( $_GET['date_to'] ) ) {
			$where[] = 'time <= %s';
			$values[] = sanitize_text_field( $_GET['date_to'] ) . ' 23:59:59';
		}

		// Payment type filter
		if ( ! empty( $_GET['payment_type'] ) ) {
			$allowed_types = [ 'cards', 'cryptocurrency', 'swift' ];
			$type = sanitize_text_field( $_GET['payment_type'] );
			if ( in_array( $type, $allowed_types, true ) ) {
				$where[] = 'payment_type = %s';
				$values[] = $type;
			}
		}

		// Status filter
		if ( ! empty( $_GET['filter_status'] ) ) {
			$allowed_statuses = [ 'review', 'approved', 'paid', 'rejected' ];
			$st = sanitize_text_field( $_GET['filter_status'] );
			if ( in_array( $st, $allowed_statuses, true ) ) {
				$where[] = 'status = %s';
				$values[] = $st;
			}
		}

		// Build query
		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'payway_withdrawal';
		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}

		// Sorting
		$allowed_orderby = [ 'time', 'amount', 'status' ];
		$orderby = ( ! empty( $_GET['orderby'] ) && in_array( $_GET['orderby'], $allowed_orderby, true ) ) ? $_GET['orderby'] : 'time';
		$order   = ( ! empty( $_GET['order'] ) && in_array( strtolower( $_GET['order'] ), [ 'asc', 'desc' ], true ) ) ? strtoupper( $_GET['order'] ) : 'DESC';
		$sql .= " ORDER BY {$orderby} {$order}";

		if ( ! empty( $values ) ) {
			$data = $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A );
		} else {
			$data = $wpdb->get_results( $sql, ARRAY_A );
		}

		// Pagination
		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$total_items  = count( $data );

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page,
		] );

		$data = array_slice( $data, ( $current_page - 1 ) * $per_page, $per_page );

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

	public function column_payment_details( $data ) {
		echo esc_html( $data['payment_details'] );
	}

	public function column_status( $data ) {
		echo "<select class='payway-status-select' data-entity='withdrawal' data-default-value='" . esc_attr( $data['status'] ) . "' data-id='" . esc_attr( $data['id'] ) . "'>";
		echo "<option value='approved' " . selected( $data['status'], 'approved', false ) . ">Подтвержден</option>";
		echo "<option value='review' " . selected( $data['status'], 'review', false ) . ">На проверке</option>";
		echo "<option value='rejected' " . selected( $data['status'], 'rejected', false ) . ">Отклонен</option>";
		echo "<option value='paid' " . selected( $data['status'], 'paid', false ) . ">Выплачено</option>";
		echo "</select>";
	}

	private function usort_reorder( $a, $b ) {
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'status';
		$order   = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'asc';
		$result  = strcmp( $a[ $orderby ], $b[ $orderby ] );

		return ( $order === 'asc' ) ? $result : - $result;
	}

	public function column_actions( $data ) {
		echo '<a href="#" class="payway-action-delete" data-entity="withdrawal" data-id="' . esc_attr( $data['id'] ) . '">Удалить</a>';
	}
}