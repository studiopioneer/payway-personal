<?php
/**
 * WP Admin page: Аудиты каналов
 * Sprint 6 — uses WP_List_Table
 *
 * Реальная таблица: {prefix}pw_channel_audits
 * Колонки: id, user_id, channel_id, channel_title, channel_url, channel_thumb,
 *          channel_data, php_signals, verdict (accept|reject|manual),
 *          risk_block1 (ok|warn|fail), risk_block2 (low|medium|high),
 *          risk_block3 (low|medium|high), report_preview, report_full,
 *          is_paid, amount_charged, time
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
		$this->table = $wpdb->prefix . 'pw_channel_audits';
 
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
			'cb'           => '<input type="checkbox" />',
			'id'           => 'ID',
			'user'         => 'Пользователь',
			'channel'      => 'Канал',
			'verdict'      => 'Вердикт',
			'risk_block2'  => 'Демонетизация',
			'risk_block3'  => 'Copyright',
			'is_paid'      => 'Оплата',
			'time'         => 'Дата',
			'actions_col'  => 'Действия',
		];
	}
 
	public function get_sortable_columns(): array {
		return [
			'id'      => [ 'id', true ],
			'verdict' => [ 'verdict', false ],
			'is_paid' => [ 'is_paid', false ],
			'time'    => [ 'time', false ],
		];
	}
 
	protected function get_bulk_actions(): array {
		return [
			'bulk_delete' => 'Удалить выбранные',
		];
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
		$thumb = $item['channel_thumb'] ?? '';
		$title = $item['channel_title'] ?: $item['channel_id'];
		$url   = $item['channel_url'];
		$img   = $thumb
			? sprintf( '<img src="%s" width="24" height="24" style="border-radius:50%%;vertical-align:middle;margin-right:6px;" />', esc_url( $thumb ) )
			: '';
		if ( $url ) {
			return sprintf(
				'%s<a href="%s" target="_blank" rel="noopener">%s</a><br><small>%s</small>',
				$img,
				esc_url( $url ),
				esc_html( $title ),
				esc_html( $item['channel_id'] )
			);
		}
		return $img . esc_html( $title ) . '<br><small>' . esc_html( $item['channel_id'] ) . '</small>';
	}
 
	protected function column_verdict( $item ): string {
		$map = [
			'accept' => [ 'color' => '#46b450', 'label' => '✔ Допущен' ],
			'reject' => [ 'color' => '#dc3232', 'label' => '✘ Отказ' ],
			'manual' => [ 'color' => '#f0a500', 'label' => '⚠ Проверить' ],
		];
		$v = $item['verdict'] ?? '';
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
 
	protected function column_risk_block2( $item ): string {
		return $this->render_risk( $item['risk_block2'] ?? '' );
	}
 
	protected function column_risk_block3( $item ): string {
		return $this->render_risk( $item['risk_block3'] ?? '' );
	}
 
	private function render_risk( string $risk ): string {
		$map = [
			'low'    => [ 'color' => '#46b450', 'label' => '▼ Низкий' ],
			'medium' => [ 'color' => '#f0a500', 'label' => '▶ Средний' ],
			'high'   => [ 'color' => '#dc3232', 'label' => '▲ Высокий' ],
		];
		if ( ! $risk ) {
			return '<em>—</em>';
		}
		$d = $map[ $risk ] ?? [ 'color' => '#888', 'label' => esc_html( $risk ) ];
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
 
	protected function column_time( $item ): string {
		$ts = $item['time'] ?? '';
		if ( ! $ts ) return '<em>—</em>';
		return esc_html( wp_date( 'd.m.Y H:i', strtotime( $ts ) ) );
	}
 
	protected function column_actions_col( $item ): string {
		$view_url = home_url( '/audit/?id=' . (int) $item['id'] );
		$delete_url = wp_nonce_url(
			add_query_arg( [
				'page'     => AuditAdminPage::MENU_SLUG,
				'action'   => 'delete_audit',
				'audit_id' => (int) $item['id'],
			], admin_url( 'admin.php' ) ),
			'delete_audit_' . (int) $item['id']
		);
		return sprintf(
			'<a href="%s" target="_blank" class="button button-small">Открыть</a> ' .
			'<a href="%s" class="button button-small" style="color:#b32d2e;border-color:#b32d2e;" ' .
			'onclick="return confirm(\'Удалить аудит #%d? Это действие необратимо.\');">Удалить</a>',
			esc_url( $view_url ),
			esc_url( $delete_url ),
			(int) $item['id']
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
 
		$filter_verdict = sanitize_text_field( $_GET['filter_verdict'] ?? '' );
		$filter_is_paid = $_GET['filter_is_paid'] ?? '';
		$search         = sanitize_text_field( $_GET['s'] ?? '' );
 
		if ( $filter_verdict !== '' ) {
			$where[]   = 'verdict = %s';
			$prepare[] = $filter_verdict;
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
		$allowed_order = [ 'id', 'verdict', 'is_paid', 'time' ];
		$orderby_col   = in_array( $_GET['orderby'] ?? '', $allowed_order, true )
			? sanitize_key( $_GET['orderby'] )
			: 'id';
		$order = ( strtoupper( $_GET['order'] ?? '' ) === 'ASC' ) ? 'ASC' : 'DESC';
 
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
		$current_verdict = sanitize_text_field( $_GET['filter_verdict'] ?? '' );
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
 
			<select name="filter_verdict">
				<option value="">— Все вердикты —</option>
				<?php foreach ( [ 'accept' => '✔ Допущен', 'reject' => '✘ Отказ', 'manual' => '⚠ Проверить' ] as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current_verdict, $val ); ?>>
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
		add_action( 'admin_init', [ $instance, 'handle_actions' ] );
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
 
	/**
	 * Обработка действий: удаление одного аудита или bulk-удаление.
	 */
	public function handle_actions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
 
		global $wpdb;
		$table = $wpdb->prefix . 'pw_channel_audits';
 
		// Одиночное удаление
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_audit' && isset( $_GET['audit_id'] ) ) {
			$audit_id = (int) $_GET['audit_id'];
			check_admin_referer( 'delete_audit_' . $audit_id );
 
			$wpdb->delete( $table, [ 'id' => $audit_id ], [ '%d' ] );
 
			wp_redirect( add_query_arg( [
				'page'    => self::MENU_SLUG,
				'deleted' => 1,
			], admin_url( 'admin.php' ) ) );
			exit;
		}
 
		// Bulk-удаление (чекбоксы + выпадающее действие)
		if (
			isset( $_GET['action'] ) && $_GET['action'] === 'bulk_delete'
			&& ! empty( $_GET['audit'] ) && is_array( $_GET['audit'] )
		) {
			check_admin_referer( 'bulk-audits' );
 
			$ids = array_map( 'intval', $_GET['audit'] );
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM `{$table}` WHERE id IN ({$placeholders})",
				...$ids
			) );
 
			wp_redirect( add_query_arg( [
				'page'    => self::MENU_SLUG,
				'deleted' => count( $ids ),
			], admin_url( 'admin.php' ) ) );
			exit;
		}
	}
 
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.' ) );
		}
 
		// Уведомление об удалении
		if ( isset( $_GET['deleted'] ) ) {
			$count = (int) $_GET['deleted'];
			printf(
				'<div class="notice notice-success is-dismissible"><p>Удалено аудитов: %d</p></div>',
				$count
			);
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
