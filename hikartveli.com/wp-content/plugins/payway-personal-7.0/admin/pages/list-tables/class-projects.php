<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Выход, если напрямую обращаются к файлу
}

// Include the necelssary WordPress files
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class for managing and displaying a list table of projects in WordPress.
 * Extends the WP_List_Table class to fetch, prepare, and display project data.
 *
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @copyright (c) 15.09.2024, CreativeMotion
 */
class Projects_List_Table extends WP_List_Table {
	// Property for storing table items
	public $items = [];

	public function __construct() {
		parent::__construct( [
			'singular' => 'Данные',
			'plural'   => 'Данные',
			'ajax'     => false
		] );
	}

	/**
	 * Get the list of table columns.
	 *
	 * @return array An associative array of columns in the format ['key' => 'Title'].
	 */
	public function get_columns(): array {
		return [
			'url'      => 'Ссылка',
			'user_id'  => 'Пользователь',
			'comments' => 'Комментарии',
			'time'     => 'Дата',
			'status'   => 'Статус',
			//'actions'  => 'Действия'
		];
	}

	/**
	 * Get the list of sortable columns.
	 *
	 * @return array An associative array of sortable columns.
	 */
	public function get_sortable_columns(): array {
		return [
			'status' => [ 'status', false ],
			'time'   => [ 'time', false ],
		];
	}

	/**
	 * Prepare items for rendering in the table.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		global $wpdb;

		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];

		$data = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'payway_projects', ARRAY_A );
		usort( $data, [ $this, 'usort_reorder' ] );

		$this->items = $data;
	}

	/**
	 * Default column rendering logic.
	 *
	 * @param array $item An associative array of the table row data.
	 * @param string $column_name The name of the current column being rendered.
	 *
	 * @return string The value of the corresponding column or an empty string.
	 */
	public function column_default( $item, $column_name ): string {
		return $item[ $column_name ] ?? '';
	}

	/**
	 * Generate the output for the URL column.
	 *
	 * @param array $item Data for the current row.
	 *
	 * @return string The generated HTML for the URL column.
	 */
	public function column_url( array $item ): string {
		$output = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( $item['url'] ),
			esc_html( $item['url'] )
		);

		$actions = [
			'edit'   => sprintf(
				'<a href="?page=%s&action=%s&id=%s">Редактировать</a>',
				esc_attr( $_REQUEST['page'] ),
				'edit',
				absint( $item['id'] )
			),
			'delete' => sprintf(
				'<a href="?page=%s&action=%s&id=%s">Удалить</a>',
				esc_attr( $_REQUEST['page'] ),
				'delete',
				absint( $item['id'] )
			)
		];

		$output .= $this->row_actions( $actions );

		return $output;
	}

	/**
	 * Generate the content of the user ID column.
	 *
	 * @param array $data Data for the current row.
	 *
	 * @return void
	 */
	public function column_user_id( array $data ): void {
		$user_id = (int) $data['user_id'];
		$user    = get_user_by( 'id', $user_id );

		echo '<a href="' . admin_url( '/user-edit.php?user_id=' . $user_id ) . '">' . $user->user_email . '</a>';
		echo '<div>Оборот: ' . $data['amount'] . ', Пользователей: ' . $data['count_users'] . '</div>';
		echo '<div>Доп. Контакты: ' . $data['contacts'] . '</div>';
	}

	/**
	 * Generate the content of the status column.
	 *
	 * @param array $data Data for the current row.
	 *
	 * @return void
	 */
	public function column_status( array $data ): void {
		echo "<select class='payway-status-select' data-entity='projects' data-default-value='" . esc_attr( $data['status'] ) . "'  data-id='" . esc_attr( $data['id'] ) . "'>
  <option value='approved' " . selected( $data['status'], 'approved', false ) . ">Подтвержден</option>
  <option value='review' " . selected( $data['status'], 'review', false ) . ">На проверке</option>
  <option value='rejected' " . selected( $data['status'], 'rejected', false ) . ">Отклонен</option>
</select>";
	}

	/**
	 * Sort the table data.
	 *
	 * @param array $a The first row to compare.
	 * @param array $b The second row to compare.
	 *
	 * @return int The comparison result for the usort() function.
	 */
	private function usort_reorder( array $a, array $b ): int {
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'status'; // If no sort, default to status
		$order   = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'asc'; // If no order, default to asc
		$result  = strcmp( $a[ $orderby ], $b[ $orderby ] ); // Determine sort order

		return ( $order === 'asc' ) ? $result : - $result; // Send final sort direction to usort
	}

	/**
	 * Generate action links for the current row.
	 *
	 * @param array $data Data of the current row.
	 *
	 * @return void
	 */
	public function column_actions( array $data ): void {
		echo '<a href="#" class="payway-action-delete" data-entity="projects" data-id="' . esc_attr( $data['id'] ) . '">Удалить</a>';
	}
}