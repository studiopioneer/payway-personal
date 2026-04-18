<?php
/**
 * Класс управления схемой БД для функционала Channel Audit.
 *
 * Создаёт и обновляет таблицу pw_channel_audits.
 *
 * @package Payway
 * @since   7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Payway_Audit_DB {

	/** Суффикс имени таблицы (без $wpdb->prefix). */
	const TABLE = 'payway_channel_audits';

	/** Текущая версия схемы — при изменении структуры увеличивать. */
	const SCHEMA_VERSION = 1;

	/** Ключ wp_options для хранения версии схемы. */
	const OPTION_KEY = 'payway_audit_db_version';

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table           = $wpdb->prefix . self::TABLE;
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$table} (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id     BIGINT UNSIGNED NOT NULL,
			channel_id  VARCHAR(64)     NOT NULL DEFAULT '',
			channel_title VARCHAR(255)  NOT NULL DEFAULT '',
			channel_url VARCHAR(512)    NOT NULL DEFAULT '',
			status      ENUM('pending','processing','done','error') NOT NULL DEFAULT 'pending',
			admission_verdict    ENUM('allowed','denied','needs_check') DEFAULT NULL,
			demonetization_risk  ENUM('low','medium','high')           DEFAULT NULL,
			copyright_risk       ENUM('low','medium','high')           DEFAULT NULL,
			report_preview LONGTEXT DEFAULT NULL,
			report_full    LONGTEXT DEFAULT NULL,
			is_paid        TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
			error_message  TEXT DEFAULT NULL,
			created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_user_id    (user_id),
			KEY idx_channel_id (channel_id),
			KEY idx_status     (status),
			KEY idx_created_at (created_at)
		) {$charset_collate};";
		dbDelta( $sql );
		update_option( self::OPTION_KEY, self::SCHEMA_VERSION );
	}

	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	public static function table_exists(): bool {
		global $wpdb;
		$table = self::table_name();
		return $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table;
	}
}
