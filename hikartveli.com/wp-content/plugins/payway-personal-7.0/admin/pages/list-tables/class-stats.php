<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Выход, если напрямую обращаются к файлу
}

// Include the necelssary WordPress files
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Extends the WP_List_Table to manage and display a custom WordPress table for statistics.
 *
 * @author  Alex Kovalev <alex.kovalevv@gmail.com> <Telegram:@alex_kovalevv>
 * @copyright (c) 09.01.2025, CreativeMotion
 */
class Stats_List_Table extends WP_List_Table {

	/**
	 * Constructor for Stats_List_Table.
	 * Sets up the table titles, singular/plural names, and AJAX compatibility.
	 */
	public function __construct() {
		parent::__construct( [
			'singular' => 'Stat',
			'plural'   => 'Stats',
			'ajax'     => false
		] );
	}

	/**
	 * Defines the columns to be displayed in the table.
	 *
	 * @return array Array of column keys and their corresponding titles.
	 */
	public function get_columns() {
		return [
			'site'                   => 'Site',
			'estimated_earnings_usd' => 'Earnings (USD)',
			'page_views'             => 'Page Views',
			'clicks'                 => 'Clicks',
			'page_rpm_usd'           => 'Page RPM (USD)',
			'impressions'            => 'Impressions',
			'impression_rpm_usd'     => 'Impression RPM (USD)',
			'active_view_viewable'   => 'Active View %',
			'period'                 => 'Period'
		];
	}


	/**
	 * Defines the sortable columns for the table.
	 *
	 * @return array Array of sortable columns and their configurations.
	 */
	public function get_sortable_columns() {
		return [
			'estimated_earnings_usd' => [ 'estimated_earnings_usd', false ],
			'page_views'             => [ 'page_views', false ],
			'clicks'                 => [ 'clicks', false ],
			'page_rpm_usd'           => [ 'page_rpm_usd', false ],
			'impressions'            => [ 'impressions', false ],
			'impression_rpm_usd'     => [ 'impression_rpm_usd', false ],
			'active_view_viewable'   => [ 'active_view_viewable', false ],
			// 'site' intentionally excluded to prevent sorting for this column
		];
	}

	/**
	 * Prepares the list of items to display in the table, including pagination.
	 * Fetches data from the database and organizes it.
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'payway_stats';

		$per_page     = 10;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );

		$sql  = $wpdb->prepare(
			"SELECT * FROM $table_name ORDER BY id DESC LIMIT %d OFFSET %d",
			$per_page,
			$offset
		);
		$data = $wpdb->get_results( $sql, ARRAY_A );

		usort( $data, [ $this, 'usort_reorder' ] );

		// Устанавливаем данные и параметры пагинации.
		$this->items = $data;

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		] );

		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
	}

	/**
	 * Default column handler for rows in the table.
	 *
	 * @param array $item The current row of data being processed.
	 * @param string $column_name The column key being rendered.
	 *
	 * @return string Content for the column.
	 */
	public function column_default( $item, $column_name ) {
		return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
	}

	/**
	 * Generates the content for the 'Site' column with row actions.
	 *
	 * @param array $item The current row of data being processed.
	 *
	 * @return string Content for the column with row actions.
	 */
	public function column_site( $item ) {
		// Генерация основного контента колонки
		$output = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( $item['site'] ),
			esc_html( $item['site'] )
		);

		// Генерация действий строки (row actions)
		$actions = [
			'edit'   => sprintf(
				'<a href="?page=%s&action=%s&id=%s">Edit</a>',
				esc_attr( $_REQUEST['page'] ), // Текущая страница
				'edit', // Действие
				absint( $item['id'] ) // ID записи
			),
			'delete' => sprintf(
				'<a href="?page=%s&action=%s&id=%s">Delete</a>',
				esc_attr( $_REQUEST['page'] ),
				'delete',
				absint( $item['id'] )
			)
		];

		// Добавляем действия строки под основным контентом
		$output .= $this->row_actions( $actions );

		return $output;
	}

	/**
	 * Default column handler for rows in the table.
	 *
	 * @param array $item The current row of data being processed.
	 * @param string $column_name The column key being rendered.
	 *
	 * @return string Content for the column.
	 */
	public function column_period( $item ) {
		// Объединяем date_start и date_end в диапазон
		if ( isset( $item['date_start'], $item['date_end'] ) ) {
			return 'Start: ' . esc_html( $item['date_start'] ) . '<br>To: ' . esc_html( $item['date_end'] );
		}

		return '—'; // Если нет данных, вернуть прочерк
	}

	/**
	 * Handles table sorting by the selected column and order.
	 *
	 * @param array $a First row of data for comparison.
	 * @param array $b Second row of data for comparison.
	 *
	 * @return int Sorting result: -1, 0, or 1.
	 */
	private function usort_reorder( $a, $b ) {
		$orderby = ! empty( $_GET['orderby'] ) ? $_GET['orderby'] : 'id';
		$order   = ! empty( $_GET['order'] ) ? $_GET['order'] : 'asc';

		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );

		return ( $order === 'asc' ) ? $result : - $result;
	}


}