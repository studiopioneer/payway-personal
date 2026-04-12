<?php
/**
 * PW_Audit_Credit - daily free unlock credit for completed audits.
 * Each user gets one free report unlock per calendar day (UTC).
 * Credit state is stored as a WP transient that auto-expires at midnight UTC.
 *
 * @package PayWay
 * @since   7.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PW_Audit_Credit {

    /** Transient key prefix. Full key: pw_audit_credit_{user_id}_{Y-m-d} */
    private const KEY_PREFIX = 'pw_audit_credit_';

    /**
     * Returns TRUE if the user has NOT yet used their free daily unlock today (UTC).
     */
    public static function can_use_daily_credit( int $user_id ): bool {
        return false === get_transient( self::build_key( $user_id ) );
    }

    /**
     * Marks the daily credit as consumed.
     * TTL = seconds until next midnight UTC so the transient auto-expires
     * at the start of the next calendar day.
     */
    public static function consume_daily_credit( int $user_id ): void {
        set_transient(
            self::build_key( $user_id ),
            1,
            self::seconds_until_midnight_utc()
        );
    }

    // -----------------------------------------------------------------------

    private static function build_key( int $user_id ): string {
        return self::KEY_PREFIX . $user_id . '_' . gmdate( 'Y-m-d' );
    }

    /**
     * Returns seconds between now (UTC) and next midnight UTC. Minimum 1.
     */
    private static function seconds_until_midnight_utc(): int {
        $tomorrow = gmdate( 'Y-m-d', time() + 86400 );
        $midnight = (int) strtotime( $tomorrow . ' 00:00:00 UTC' );
        return max( 1, $midnight - time() );
    }
}
