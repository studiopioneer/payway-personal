<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
 
/**
 * Класс-активатор плагина Payway.
 * Создаёт / обновляет таблицы БД и WP-страницы при активации.
 *
 * @package Payway
 * @version 7.0  — добавлена таблица channel_audits (Payway_Audit_DB)
 * @version 4.9  — добавлена таблица payway_donations
 */
class Payway_Activator {
 
	private static function get_charset_collate(): string {
		global $wpdb;
		return $wpdb->get_charset_collate();
	}
 
	public static function activate(): void {
		self::create_tables();
		self::update_column_enum();
		self::create_admin_pages();
		// ── Sprint 1: Channel Audit ──────────────────────────────────────────
		Payway_Audit_DB::install();
	}
 
	private static function create_tables(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
 
		$charset_collate = self::get_charset_collate();
 
		$tables = [
			"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}payway_projects (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				user_id int(11) NOT NULL,
				url varchar(255) DEFAULT '' NOT NULL,
				amount varchar(55) DEFAULT 0 NOT NULL,
				count_users int(11) DEFAULT 0 NOT NULL,
				comments text DEFAULT '' NOT NULL,
				review_comments text DEFAULT '' NOT NULL,
				contacts varchar(255) DEFAULT '' NOT NULL,
				status enum('approved', 'review', 'rejected') DEFAULT 'review' NOT NULL,
				time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;",
 
			"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}payway_withdrawal (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				user_id int(11) NOT NULL,
				payment_type enum('swift', 'cryptocurrency', 'cards') DEFAULT 'cards' NOT NULL,
				payment_details varchar(255) DEFAULT '' NOT NULL,
				amount decimal(11,2) NOT NULL,
				comments text DEFAULT '' NOT NULL,
				review_comments text DEFAULT '' NOT NULL,
				status enum('approved', 'review', 'rejected', 'paid') DEFAULT 'review' NOT NULL,
				time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;",
 
			"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}payway_unlock (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				user_id int(11) NOT NULL,
				amount decimal(11,2) NOT NULL,
				review_comments text DEFAULT '' NOT NULL,
				status enum('approved', 'review', 'rejected', 'paid') DEFAULT 'review' NOT NULL,
				time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;",
 
			"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}payway_stats (
				id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
				project_id MEDIUMINT(9) NOT NULL,
				site VARCHAR(255) NOT NULL,
				estimated_earnings_usd DECIMAL(11, 2) NOT NULL,
				page_views INT(11) NOT NULL,
				page_rpm_usd DECIMAL(11, 2) NOT NULL,
				impressions INT(11) NOT NULL,
				impression_rpm_usd DECIMAL(11, 2) NOT NULL,
				active_view_viewable DECIMAL(5, 2) NOT NULL,
				clicks INT(11) NOT NULL,
				date_start DATE NOT NULL,
				date_end DATE NOT NULL,
				PRIMARY KEY (id),
				FOREIGN KEY (project_id) REFERENCES {$wpdb->prefix}payway_projects(id) ON DELETE CASCADE
			) $charset_collate;",
 
			"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}payway_referrals (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				referrer_id BIGINT(20) UNSIGNED NOT NULL,
				referral_email VARCHAR(255) NOT NULL,
				referral_code VARCHAR(32) NOT NULL,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY referrer_id (referrer_id),
				KEY referral_code (referral_code)
			) $charset_collate;",
 
			// v4.9: Таблица донатов
			"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}payway_donations (
				id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id     BIGINT(20) UNSIGNED NOT NULL,
				amount      DECIMAL(11,2) NOT NULL,
				message     TEXT DEFAULT '',
				created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY user_id (user_id)
			) $charset_collate;",
		];
 
		foreach ( $tables as $sql ) {
			dbDelta( $sql );
		}
	}
 
	private static function update_column_enum(): void {
		global $wpdb;
 
		$new_enum_type = "ENUM('approved', 'review', 'rejected', 'paid') DEFAULT 'review' NOT NULL";
 
		$targets = [
			'payway_unlock'     => 'status',
			'payway_withdrawal' => 'status',
		];
 
		foreach ( $targets as $table_suffix => $column_name ) {
			$table_name = $wpdb->prefix . $table_suffix;
			$query      = $wpdb->prepare( 'SHOW COLUMNS FROM ' . $table_name . ' LIKE %s', $column_name );
			$column     = $wpdb->get_row( $query );
 
			if ( $column && isset( $column->Type ) && strpos( $column->Type, "'paid'" ) === false ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$wpdb->query( "ALTER TABLE {$table_name} MODIFY {$column_name} {$new_enum_type}" );
			}
		}
	}
 
	private static function create_admin_pages(): void {
		$pages = [
			[ 'slug' => 'account',  'title' => 'Payway личный кабинет' ],
			[ 'slug' => 'unlock',   'title' => 'Разблокировка счета'   ],
			[ 'slug' => 'profile',  'title' => 'Профайл'               ],
			[ 'slug' => 'projects', 'title' => 'Мои проекты'           ],
			[ 'slug' => 'stats',    'title' => 'Статистика'            ],
		];
 
		foreach ( $pages as $page ) {
			if ( ! get_page_by_path( $page['slug'] ) ) {
				wp_insert_post( [
					'post_title'   => $page['title'],
					'post_name'    => $page['slug'],
					'post_content' => '',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				] );
			}
		}
	}
}
 
register_activation_hook( PAYWAY_PLUGIN_FILE, [ 'Payway_Activator', 'activate' ] );
