<?php
/**
 * Репозиторий для работы с таблицей pw_channel_audits.
 *
 * CRUD + смена статусов. Все методы статические — класс-утилита.
 *
 * @package Payway
 * @since   7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Payway_Audit_Repository {

	// -------------------------------------------------------------------------
	// Create
	// -------------------------------------------------------------------------

	/**
	 * Создать новую запись аудита со статусом 'pending'.
	 *
	 * @param int    $user_id
	 * @param string $channel_url
	 * @return int  ID вставленной записи.
	 * @throws RuntimeException При ошибке INSERT.
	 */
	public static function create( int $user_id, string $channel_url ): int {
		global $wpdb;

		$inserted = $wpdb->insert(
			Payway_Audit_DB::table_name(),
			[
				'user_id'     => $user_id,
				'channel_url' => sanitize_url( $channel_url ),
				'status'      => 'pending',
				'created_at'  => current_time( 'mysql', true ),
				'updated_at'  => current_time( 'mysql', true ),
			],
			[ '%d', '%s', '%s', '%s', '%s' ]
		);

		if ( ! $inserted ) {
			throw new RuntimeException( 'Не удалось создать запись аудита: ' . $wpdb->last_error );
		}

		return (int) $wpdb->insert_id;
	}

	// -------------------------------------------------------------------------
	// Read
	// -------------------------------------------------------------------------

	/**
	 * Получить одну запись по ID.
	 *
	 * @param int $id
	 * @return array|null  Запись или null если не найдена.
	 */
	public static function find( int $id ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . Payway_Audit_DB::table_name() . ' WHERE id = %d LIMIT 1',
				$id
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	/**
	 * Получить запись по ID, принадлежащую конкретному пользователю.
	 *
	 * @param int $id
	 * @param int $user_id
	 * @return array|null
	 */
	public static function find_for_user( int $id, int $user_id ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . Payway_Audit_DB::table_name() . ' WHERE id = %d AND user_id = %d LIMIT 1',
				$id,
				$user_id
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	/**
	 * Получить список аудитов пользователя с пагинацией.
	 *
	 * @param int $user_id
	 * @param int $page      Страница (с 1).
	 * @param int $per_page
	 * @return array{ items: array[], total: int, total_pages: int }
	 */
	public static function list_for_user( int $user_id, int $page = 1, int $per_page = 10 ): array {
		global $wpdb;

		$table  = Payway_Audit_DB::table_name();
		$offset = ( max( 1, $page ) - 1 ) * $per_page;

		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, channel_id, channel_title, channel_url, status,
				        admission_verdict, demonetization_risk, copyright_risk,
				        is_paid, created_at
				 FROM {$table}
				 WHERE user_id = %d
				 ORDER BY created_at DESC
				 LIMIT %d OFFSET %d",
				$user_id,
				$per_page,
				$offset
			),
			ARRAY_A
		);

		$total = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE user_id = %d", $user_id )
		);

		return [
			'items'       => $items ?: [],
			'total'       => $total,
			'total_pages' => $per_page > 0 ? (int) ceil( $total / $per_page ) : 1,
		];
	}

	// -------------------------------------------------------------------------
	// Update
	// -------------------------------------------------------------------------

	/**
	 * Перевести статус в 'processing'.
	 */
	public static function mark_processing( int $id ): void {
		self::update_fields( $id, [ 'status' => 'processing' ] );
	}

	/**
	 * Сохранить результат анализа (preview, статус done, verdicts).
	 *
	 * @param int   $id
	 * @param array $channel  Данные канала из Payway_Audit_Analyzer.
	 * @param array $preview  report_preview (rule-based, из анализатора).
	 * @param array $metrics  Агрегированные метрики.
	 */
	public static function save_preview_result( int $id, array $channel, array $preview, array $metrics ): void {
		self::update_fields( $id, [
			'channel_id'          => sanitize_text_field( $channel['channel_id'] ),
			'channel_title'       => sanitize_text_field( $channel['title'] ),
			'status'              => 'done',
			'admission_verdict'   => $preview['admission']['verdict']   ?? 'needs_check',
			'demonetization_risk' => $preview['demonetization']['risk'] ?? 'medium',
			'copyright_risk'      => $preview['copyright']['risk']      ?? 'medium',
			'report_preview'      => wp_json_encode( [
				'preview' => $preview,
				'metrics' => $metrics,
			] ),
		] );
	}

	/**
	 * Сохранить полный AI-отчёт и пометить is_paid = 1.
	 *
	 * @param int   $id
	 * @param array $report_full  Массив данных от OpenAI.
	 */
	public static function save_full_report( int $id, array $report_full ): void {
		self::update_fields( $id, [
			'report_full' => wp_json_encode( $report_full ),
			'is_paid'     => 1,
		] );
	}

	/**
	 * Пометить is_paid = 1 без изменения report_full (кредитный разблок).
	 */
	public static function mark_paid( int $id ): void {
		self::update_fields( $id, [ 'is_paid' => 1 ] );
	}

	/**
	 * Записать ошибку обработки.
	 *
	 * @param int    $id
	 * @param string $message
	 */
	public static function mark_error( int $id, string $message ): void {
		self::update_fields( $id, [
			'status'        => 'error',
			'error_message' => mb_substr( sanitize_text_field( $message ), 0, 500 ),
		] );
	}

	// -------------------------------------------------------------------------
	// Internal
	// -------------------------------------------------------------------------

	/**
	 * Обновить произвольный набор полей по ID.
	 *
	 * updated_at обновляется автоматически через ON UPDATE CURRENT_TIMESTAMP.
	 *
	 * @param int   $id
	 * @param array $fields  [ column => value ]
	 * @throws RuntimeException При ошибке UPDATE.
	 */
	private static function update_fields( int $id, array $fields ): void {
		global $wpdb;

		$result = $wpdb->update(
			Payway_Audit_DB::table_name(),
			$fields,
			[ 'id' => $id ]
		);

		if ( $result === false ) {
			throw new RuntimeException( 'Ошибка обновления аудита #' . $id . ': ' . $wpdb->last_error );
		}
	}
}
