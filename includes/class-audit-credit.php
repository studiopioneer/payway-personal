<?php
/**
 * PW_Audit_Credit — логика бесплатных отчётов: 3 на аккаунт, 1 в день.
 *
 * @package PayWay
 * @since   7.2
 */
 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
 
class PW_Audit_Credit {
 
    const FREE_REPORTS_TOTAL = 3;
    const TRANSIENT_PREFIX   = 'payway_audit_daily_';
 
    /**
     * Проверяет может ли пользователь получить бесплатный отчёт.
     * Возвращает массив: ['allowed' => bool, 'reason' => string, 'used_total' => int, 'used_today' => bool]
     */
    public static function check( int $user_id ): array {
        // Администраторы — без ограничений
        if ( user_can( $user_id, 'manage_options' ) ) {
            return [ 'allowed' => true, 'reason' => 'admin', 'used_total' => 0, 'used_today' => false ];
        }
 
        // Счётчик всего использованных бесплатных отчётов
        $used_total = (int) get_user_meta( $user_id, 'payway_audit_free_used', true );
 
        if ( $used_total >= self::FREE_REPORTS_TOTAL ) {
            return [ 'allowed' => false, 'reason' => 'limit_reached', 'used_total' => $used_total, 'used_today' => false ];
        }
 
        // Ограничение: 1 в день (transient сбрасывается в UTC-полночь)
        $transient_key = self::TRANSIENT_PREFIX . $user_id;
        $used_today    = (bool) get_transient( $transient_key );
 
        if ( $used_today ) {
            return [ 'allowed' => false, 'reason' => 'daily_limit', 'used_total' => $used_total, 'used_today' => true ];
        }
 
        return [ 'allowed' => true, 'reason' => 'free', 'used_total' => $used_total, 'used_today' => false ];
    }
 
    /**
     * Списывает 1 бесплатный отчёт (вызывать только после check() = allowed).
     */
    public static function consume( int $user_id ): void {
        $used = (int) get_user_meta( $user_id, 'payway_audit_free_used', true );
        update_user_meta( $user_id, 'payway_audit_free_used', $used + 1 );
 
        // Transient до UTC-полуночи
        $seconds_until_midnight = self::seconds_until_midnight_utc();
        set_transient( self::TRANSIENT_PREFIX . $user_id, 1, $seconds_until_midnight );
    }
 
    /**
     * Статус для передачи во фронтенд (unlock_info).
     */
    public static function get_status( int $user_id ): array {
        $check = self::check( $user_id );
        $used  = (int) get_user_meta( $user_id, 'payway_audit_free_used', true );
 
        return [
            'free_available' => $check['allowed'],
            'free_used_total' => $used,
            'free_remaining'  => max( 0, self::FREE_REPORTS_TOTAL - $used ),
            'free_total'      => self::FREE_REPORTS_TOTAL,
            'daily_used'      => $check['used_today'] ?? false,
            'reason'          => $check['reason'],
        ];
    }
 
    // --- Обратная совместимость со старым API ---
 
    /**
     * @deprecated Use check() instead.
     */
    public static function can_use_daily_credit( int $user_id ): bool {
        return self::check( $user_id )['allowed'];
    }
 
    /**
     * @deprecated Use consume() instead.
     */
    public static function consume_daily_credit( int $user_id ): void {
        self::consume( $user_id );
    }
 
    // --- Helpers ---
 
    private static function seconds_until_midnight_utc(): int {
        $tomorrow = gmdate( 'Y-m-d', time() + 86400 );
        $midnight = (int) strtotime( $tomorrow . ' 00:00:00 UTC' );
        return max( 1, $midnight - time() );
    }
}
