<?php
/**
 * Rate limiter для запусков аудита.
 *
 * Правила:
 *   1. <= 5 аудитов в час на пользователя (hard limit).
 *   2. 1 бесплатный разблок (кредит) в день на пользователя
 *      (TTL до полуночи UTC следующего дня).
 *
 * Хранилище: WP transients (без внешнего кеша — шэренный хост).
 *
 * @package Payway
 * @since   7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Payway_Audit_Rate_Limiter {

	/** Максимум запусков аудита за час. */
	private const MAX_PER_HOUR = 5;

	/** TTL hourly-счётчика в секундах. */
	private const HOUR_TTL = HOUR_IN_SECONDS;

	// -------------------------------------------------------------------------
	// Hourly audit limit
	// -------------------------------------------------------------------------

	/**
	 * Проверить, не превышен ли лимит запусков за час.
	 *
	 * @param int $user_id
	 * @return bool  true = лимит не превышен (можно запускать).
	 */
	public static function can_start_audit( int $user_id ): bool {
		return self::get_hourly_count( $user_id ) < self::MAX_PER_HOUR;
	}

	/**
	 * Записать факт запуска аудита (инкрементировать счётчик).
	 *
	 * @param int $user_id
	 */
	public static function record_audit_start( int $user_id ): void {
		$key   = self::hourly_key( $user_id );
		$count = (int) get_transient( $key );

		if ( $count === 0 ) {
			// Первый запуск в текущем часу — устанавливаем TTL
			set_transient( $key, 1, self::HOUR_TTL );
		} else {
			// Обновляем значение (delete + set, т.к. WP не даёт просто increment)
			delete_transient( $key );
			set_transient( $key, $count + 1, self::HOUR_TTL );
		}
	}

	/**
	 * Получить текущий счётчик запусков за час.
	 *
	 * @param int $user_id
	 * @return int
	 */
	public static function get_hourly_count( int $user_id ): int {
		return (int) get_transient( self::hourly_key( $user_id ) );
	}

	/**
	 * Остаток доступных аудитов в текущем часу.
	 *
	 * @param int $user_id
	 * @return int
	 */
	public static function remaining_this_hour( int $user_id ): int {
		return max( 0, self::MAX_PER_HOUR - self::get_hourly_count( $user_id ) );
	}

	// -------------------------------------------------------------------------
	// Daily free unlock (credit)
	// -------------------------------------------------------------------------

	/**
	 * Проверить, доступен ли бесплатный разблок сегодня.
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public static function can_use_daily_credit( int $user_id ): bool {
		return false === get_transient( self::credit_key( $user_id ) );
	}

	/**
	 * Использовать дневной кредит.
	 *
	 * TTL = секунды до следующей полуночи UTC.
	 *
	 * @param int $user_id
	 */
	public static function consume_daily_credit( int $user_id ): void {
		set_transient( self::credit_key( $user_id ), 1, self::seconds_until_midnight_utc() );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private static function hourly_key( int $user_id ): string {
		return 'pw_audit_hr_' . $user_id;
	}

	private static function credit_key( int $user_id ): string {
		return 'pw_audit_credit_' . $user_id;
	}

	/**
	 * Секунды до следующей полуночи UTC.
	 */
	private static function seconds_until_midnight_utc(): int {
		$now      = time();
		$midnight = strtotime( 'tomorrow', $now ); // 00:00:00 UTC next day
		return max( 60, $midnight - $now );
	}
}
