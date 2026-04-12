<?php
/**
 * WP Admin page: Аудиты каналов
 * Sprint 6 — uses WP_List_Table
 *
 * @package Payway
 */

namespace Payway\Pages;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Inner list-table class (must be defined before the page class uses it).
 */
class Audit_List_Table extends \WP_List_Table {

	/** @var string DB table name */
	private string $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'payway_channel_audits';

		parent::__construct( [
			'singular' => 'audit',
			'plural'   => 'audits',
			'ajax'     => false,
		] );
	}

	/* ------------------------------------------------------------------ */
	/*  Column definitions                                                  */
	/* ------------------------------------------------------------------ */

	public function get_columns(): array {
		return [
			'cb'                  => '<input type="checkbox" />',
			'id'                  => 'ID',
			'user'                => 'Пользователь',
			'channel'             => 'Канал',
			'status'              => 'Статус',
			'admission_verdict'   => 'Вердикт',
			'demonetization_risk' => 'Риск',
			'is_paid'             => 'Оплата',
			'created_at'          => 'Дата',
			'actions_col'         => 'Отчёт',
		];
	}

	public function get_sortable_columns(): array {
		return [
			'id'         => [ 'id', true ],
			'status'     => [ 'status', false ],
			'is_paid'    => [ 'is_paid', false ],
			'created_at' => [ 'created_at', false ],
		];
	}

	protected function get_bulk_actions(): array {
		return [];
	}

	/* ------------------------------------------------------------------ */
	/*  Column rendering                                                    */
	/* ------------------------------------------------------------------ */

	protected function column_cb( $item ): string {
		return sprintf( '<input type="checkbox" name="audit[]" value="%s" />', esc_attr( $item['id'] ) );
	}

	protected function column_id( $item ): string {
		return '#' . (int) $item['id'];
	}

	protected function column_user( $item ): string {
		$user = get_userdata( (int) $item['user_id'] );
		if ( ! $user ) {
			return '<em>—</em>';
		}
		$admin_url = esc_url( add_query_arg( 'user_id', $user->ID, admin_url( 'user-edit.php' ) ) );
		return sprintf(
			'<a href="%s">%s</a><br><small>%s</small>',
			$admin_url,
			esc_html( $user->display_name ),
			esc_html( $user->user_email )
		);
	}

	protected function column_channel( $item ): string {
		$title = $item['channel_title'] ?: $item['channel_id'];
		$url   = $item['channel_url'];
		if ( $url ) {
			return sprintf(
				'<a href="%s" target="_blank" rel="noopener">%s</a><br><small>%s</small>',
				esc_url( $url ),
				esc_html( $title ),
				esc_html( $item['channel_id'] )
			);
		}
		return esc_html( $title ) . '<br><small>' . esc_html( $item['channel_id'] ) . '</small>';
	}

	protected function column_status( $item ): string {
		$map = [
			'pending'    => [ 'color' => '#888', 'label' => 'Ожидание' ],
			'processing' => [ 'color' => '#f0a500', 'label' => 'Обработка' ],
			'done'       => [ 'color' => '#46b450', 'label' => 'Готово' ],
			'error'      => [ 'color' => '#dc3232', 'label' => 'Ошибка' ],
		];
		$s = $item['status'];
		$d = $map[ $s ] ?? [ 'color' => '#888', 'label' => esc_html( $s ) ];
		return sprintf(
			'<span style="color:%s;font-weight:600;">%s</span>',
			esc_attr( $d['color'] ),
			esc_html( $d['label'] )
		);
	}

	protected function column_admission_verdict( $item ): string {
		$map = [
			'allowed'     => [ 'color' => '#46b450', 'label' => '✔ Допущен' ],
			'denied'      => [ 'color' => '#dc3232', 'label' => '✘ Отказ' ],
			'needs_check' => [ 'color' => '#f0a500', 'label' => '⚠ Проверить' ],
		];
		$v = $item['admission_verdict'];
		if ( ! $v ) {
			return '<em>—</em>';
		}
		$d = $map[ $v ] ?? [ 'color' => '#888', 'label' => esc_html( $v ) ];
		return sprintf(
			'<span style="color:%s;font-weight:600;">%s</span>',
			esc_attr( $d['color'] ),
			esc_html( $d['label'] )
		);
	}

	protected function column_demonetization_risk( $item ): string {
		$map = [
			'low'    => [ 'color' => '#46b450', 'label' => '▼ Низкий' ],
			'medium' => [ 'color' => '#f0a500', 'label' => '▶ Средний' ],
			'high'   => [ 'color' => '#dc3232', 'label' => '▲ Высокий' ],
		];
		$r = $item['demonetization_risk'];
		if ( ! $r ) {
			return '<em>—</em>';
		}
		$d = $map[ $r ] ?? [ 'color' => '#888', 'label' => esc_html( $r ) ];
		return sprintf(
			'<span style="color:%s;">%s</span>',
			esc_attr( $d['color'] ),
			esc_html( $d['label'] )
		);
	}

	protected function column_is_paid( $item ): string {
		return $item['is_paid']
			? '<span style="color:#46b450;font-weight:600;">✔ Да</span>'
			: '<span style="color:#888;">— Нет</span>';
	}

	protected function column_created_at( $item ): string {
		return esc_html( wp_date( 'd.m.Y H:i', strtotime( $item['created_at'] ) ) );
	}

	protected function column_actions_col( $item ): string {
		if ( $item['status'] !== 'done' || ! $item['is_paid'] ) {
			return '<em>—</em>';
		}
		$url = home_url( '/audit-result/' . (int) $item['id'] );
		return sprintf(
			'<a href="%s" target="_blank" class="button button-small">Открыть</a>',
			esc_url( $url )
		);
	}

	protected function column_default( $item, $column_name ) {
		return esc_html( $item[ $column_name ] ?? '—' );
	}

	/* ------------------------------------------------------------------ */
	/*  Data loading                                                        */
	/* ------------------------------------------------------------------ */

	public function prepare_items(): void {
		global $wpdb;

		$per_page     = 20;
		$current_page = $this->get_pagenum();

		// --- filters ---
		$where   = [];
		$prepare = [];

		$filter_status  = sanitize_text_field( $_GET['filter_status']  ?? '' );
		$filter_is_paid = $_GET['filter_is_paid'] ?? '';
		$search         = sanitize_text_field( $_GET['s'] ?? '' );

		if ( $filter_status !== '' ) {
			$where[]   = 'status = %s';
			$prepare[] = $filter_status;
		}
		if ( $filter_is_paid !== '' ) {
			$where[]   = 'is_paid = %d';
			$prepare[] = (int) $filter_is_paid;
		}
		if ( $search !== '' ) {
			$where[]   = '(channel_id LIKE %s OR channel_title LIKE %s)';
			$prepare[] = '%' . $wpdb->esc_like( $search ) . '%';
			$prepare[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		$where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

		// total
		$total_sql   = "SELECT COUNT(*) FROM `{$this->table}` {$where_sql}";
		$total_items = $prepare
			? (int) $wpdb->get_var( $wpdb->prepare( $total_sql, ...$prepare ) )
			: (int) $wpdb->get_var( $total_sql );

		// sorting
		$orderby_col = in_array( $_GET['orderby'] ?? '', [ 'id', 'status', 'is_paid', 'created_at' ], true )
			? sanitize_key( $_GET['orderby'] )
			: 'id';
		$order       = ( strtoupper( $_GET['order'] ?? '' ) === 'ASC' ) ? 'ASC' : 'DESC';

		$offset = ( $current_page - 1 ) * $per_page;

		$data_sql = "SELECT * FROM `{$this->table}` {$where_sql}
		             ORDER BY {$orderby_col} {$order}
		             LIMIT %d OFFSET %d";

		$data_prepare = array_merge( $prepare, [ $per_page, $offset ] );
		$this->items  = $wpdb->get_results(
			$wpdb->prepare( $data_sql, ...$data_prepare ),
			ARRAY_A
		);

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => (int) ceil( $total_items / $per_page ),
		] );

		$this->_column_headers = [
			$this->get_columns(),
			[],
			$this->get_sortable_columns(),
		];
	}

	/* ------------------------------------------------------------------ */
	/*  Custom search box + filters above the table                        */
	/* ------------------------------------------------------------------ */

	public function render_filters(): void {
		$current_status  = sanitize_text_field( $_GET['filter_status']  ?? '' );
		$current_is_paid = $_GET['filter_is_paid'] ?? '';
		$current_search  = sanitize_text_field( $_GET['s'] ?? '' );
		?>
		<form method="get" style="margin-bottom:8px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
			<input type="hidden" name="page" value="payway-audits" />

			<input type="text"
			       name="s"
			       value="<?php echo esc_attr( $current_search ); ?>"
			       placeholder="Поиск по каналу..."
			       style="min-width:220px;" />

			<select name="filter_status">
				<option value="">— Все статусы —</option>
				<?php foreach ( [ 'pending' => 'Ожидание', 'processing' => 'Обработка', 'done' => 'Готово', 'error' => 'Ошибка' ] as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current_status, $val ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="filter_is_paid">
				<option value="">— Оплата —</option>
				<option value="1" <?php selected( $current_is_paid, '1' ); ?>>Оплачено</option>
				<option value="0" <?php selected( $current_is_paid, '0' ); ?>>Не оплачено</option>
			</select>

			<?php submit_button( 'Применить', 'secondary', '', false ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=payway-audits' ) ); ?>"
			   class="button">Сбросить</a>
		</form>
		<?php
	}
}

/* ======================================================================= */
/*  Page class                                                              */
/* ======================================================================= */

class AuditAdminPage {

	const MENU_SLUG = 'payway-audits';

	public static function init(): void {
		$instance = new self();
		add_action( 'admin_menu', [ $instance, 'register_menu' ], 10 );
	}

	public function register_menu(): void {
		add_submenu_page(
			'payway-cabinet',
			'Аудиты каналов — Payway',
			'Аудиты каналов',
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ]
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.' ) );
		}

		$table = new Audit_List_Table();
		$table->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">Аудиты каналов</h1>
			<hr class="wp-header-end">

			<?php $table->render_filters(); ?>

			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>" />
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}
}
