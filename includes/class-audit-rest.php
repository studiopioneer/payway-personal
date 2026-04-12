<?php
defined( "ABSPATH" ) || exit;
require_once __DIR__ . '/class-audit-db.php';
require_once __DIR__ . '/class-audit-rate-limiter.php';
require_once __DIR__ . '/class-audit-repository.php';
require_once __DIR__ . '/class-audit-cron.php';
require_once __DIR__ . '/class-audit-credit.php';

class PW_Audit_REST {
    private const NS = "payway/v1";
    public static function register_routes(): void {
        register_rest_route( self::NS, "/audit/start", [
            "methods"             => WP_REST_Server::CREATABLE,
            "callback"            => [ self::class, "start_audit" ],
            "permission_callback" => [ self::class, "require_auth" ],
            "args"                => [
                "channel_url" => [
                    "required"          => true,
                    "type"              => "string",
                    "sanitize_callback" => "sanitize_text_field",
                    "validate_callback" => [ self::class, "validate_channel_url" ],
                ],
            ],
        ] );
        register_rest_route( self::NS, "/audit/(?P<id>\d+)", [
            "methods"             => WP_REST_Server::READABLE,
            "callback"            => [ self::class, "get_audit" ],
            "permission_callback" => [ self::class, "require_auth" ],
            "args"                => [ "id" => [ "required" => true, "type" => "integer" ] ],
        ] );
        register_rest_route( self::NS, "/audit/(?P<id>\d+)/status", [
            "methods" => WP_REST_Server::READABLE,
            "callback" => [ self::class, "get_audit" ],
            "permission_callback" => [ self::class, "require_auth" ],
            "args" => [ "id" => [ "required" => true, "type" => "integer" ] ],
        ] );
        register_rest_route( self::NS, "/audit/history", [
            "methods"             => WP_REST_Server::READABLE,
            "callback"            => [ self::class, "get_history" ],
            "permission_callback" => [ self::class, "require_auth" ],
            "args"                => [
                "page"     => [ "type" => "integer", "default" => 1,  "minimum" => 1 ],
                "per_page" => [ "type" => "integer", "default" => 10, "minimum" => 1, "maximum" => 50 ],
            ],
        ] );
        register_rest_route( self::NS, "/audit/(?P<id>\d+)/unlock", [
            "methods"             => WP_REST_Server::CREATABLE,
            "callback"            => [ self::class, "unlock_report" ],
            "permission_callback" => [ self::class, "require_auth" ],
            "args"                => [
                "id" => [ "required" => true, "type" => "integer" ],
            ],
        ] );
    }
    public static function require_auth() {
        if ( ! is_user_logged_in() ) {
            return new WP_Error( "rest_forbidden", "You must be logged in.", [ "status" => 401 ] );
        }
        return true;
    }
    public static function validate_channel_url( $value ) {
        $value = trim( $value );
        if ( empty( $value ) ) return new WP_Error( "invalid_url", "channel_url is required." );
        $patterns = [
            "#^https?://(www\.)?youtube\.com/(@[\w.-]+|channel/UC[\w-]+|c/[\w.-]+|user/[\w.-]+)#i",
            "#^@[\w.-]+$#",
        ];
        foreach ( $patterns as $p ) { if ( preg_match( $p, $value ) ) return true; }
        return new WP_Error( "invalid_channel_url", "Provide a valid YouTube channel URL or @handle.", [ "status" => 422 ] );
    }
    public static function start_audit( WP_REST_Request $request ) {
        $user_id     = get_current_user_id();
        $channel_url = trim( $request->get_param( "channel_url" ) );
        if ( ! Payway_Audit_Rate_Limiter::can_start_audit( $user_id ) ) {
            if ( ! Payway_Audit_Rate_Limiter::can_use_daily_credit( $user_id ) ) {
                return new WP_Error( "rate_limit_exceeded", "Hourly limit reached. Daily bonus credit is also spent.", [ "status" => 429 ] );
            }
            Payway_Audit_Rate_Limiter::consume_daily_credit( $user_id );
        } else {
            Payway_Audit_Rate_Limiter::record_audit_start( $user_id );
        }
        $audit_id = Payway_Audit_Repository::create( $user_id, $channel_url );
        if ( ! $audit_id ) return new WP_Error( "db_error", "Failed to create audit record.", [ "status" => 500 ] );
        wp_schedule_single_event( time(), PW_Audit_Cron::HOOK, [ $audit_id ] );
        return new WP_REST_Response( [ "audit_id" => $audit_id, "status" => "pending" ], 201 );
    }
    public static function get_audit( WP_REST_Request $request ) {
        $user_id  = get_current_user_id();
        $audit_id = (int) $request->get_param( "id" );
        $audit = Payway_Audit_Repository::find_for_user( $audit_id, $user_id );
        if ( ! $audit ) return new WP_Error( "not_found", "Audit not found.", [ "status" => 404 ] );
        $preview     = ! empty( $audit["report_preview"] ) ? json_decode( $audit["report_preview"], true ) : null;
        $full_report = ( ! empty( $audit["report_full"] ) && (bool) $audit["is_paid"] ) ? json_decode( $audit["report_full"], true ) : null;
        return new WP_REST_Response( [
            "id"                  => (int) $audit["id"],
            "channel_id"          => $audit["channel_id"],
            "channel_title"       => $audit["channel_title"],
            "channel_url"         => $audit["channel_url"],
            "status"              => $audit["status"],
            "admission_verdict"   => $audit["admission_verdict"],
            "demonetization_risk" => $audit["demonetization_risk"],
            "copyright_risk"      => $audit["copyright_risk"],
            "is_paid"             => (bool) $audit["is_paid"],
            "report_preview"      => $preview,
            "report_full"         => $full_report,
            "report" => self::normalize_report( $full_report ?? ($preview['preview'] ?? $preview) ),
            "error_message"       => $audit["error_message"],
            "created_at"          => $audit["created_at"],
            "updated_at"          => $audit["updated_at"],
        ], 200 );
    }
    public static function get_history( WP_REST_Request $request ) {
        $user_id  = get_current_user_id();
        $page     = (int) $request->get_param( "page" );
        $per_page = (int) $request->get_param( "per_page" );
        $result   = Payway_Audit_Repository::list_for_user( $user_id, $page, $per_page );
        $items = array_map( static function ( array $row ): array {
            return [
                "id"                  => (int) $row["id"],
                "channel_id"          => $row["channel_id"],
                "channel_title"       => $row["channel_title"],
                "channel_url"         => $row["channel_url"],
                "status"              => $row["status"],
                "admission_verdict"   => $row["admission_verdict"],
                "demonetization_risk" => $row["demonetization_risk"],
                "copyright_risk"      => $row["copyright_risk"],
                "is_paid"             => (bool) $row["is_paid"],
                "created_at"          => $row["created_at"],
                "updated_at"          => $row["updated_at"],
            ];
        }, $result["items"] );
        return new WP_REST_Response( [
            "items"       => $items,
            "total"       => (int) $result["total"],
            "total_pages" => (int) $result["total_pages"],
            "page"        => $page,
            "per_page"    => $per_page,
        ], 200 );
    }
    /**
     * POST /payway/v1/audit/{id}/unlock
     *
     * Branch A: balance >= $1.00  -> atomic debit $1.00  + is_paid = 1
     * Branch B: balance = 0, first unlock today -> free daily credit + is_paid = 1
     * Branch C: insufficient funds -> 402 with shortfall
     */
    public static function unlock_report( WP_REST_Request $request ): WP_REST_Response {
        global $wpdb;

        $user_id  = get_current_user_id();
        $audit_id = (int) $request->get_param( 'id' );
        $table    = $wpdb->prefix . 'payway_channel_audits';

        // Fetch audit row and verify ownership
        $audit = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, user_id, status, is_paid FROM {$table} WHERE id = %d",
            $audit_id
        ) );

        if ( ! $audit ) {
            return new WP_REST_Response( [ 'message' => 'Audit not found.' ], 404 );
        }
        if ( (int) $audit->user_id !== $user_id ) {
            return new WP_REST_Response( [ 'message' => 'Access denied.' ], 403 );
        }
        if ( 'done' !== $audit->status ) {
            return new WP_REST_Response( [ 'message' => 'Audit is not complete yet.' ], 409 );
        }

        // Idempotent: already unlocked
        if ( (bool) $audit->is_paid ) {
            return new WP_REST_Response( [ 'unlocked' => true, 'method' => 'already_paid' ], 200 );
        }

        // Read current balance
        $balance = floatval( get_user_meta( $user_id, 'payway_withdrawal_balance', true ) );

        // == Branch A: balance debit ($1.00) ==================================
        if ( $balance >= 1.00 ) {
            $wpdb->query( 'START TRANSACTION' );

            $new_balance = $balance - 1.00;
            $meta_ok     = update_user_meta( $user_id, 'payway_withdrawal_balance', $new_balance );
            $flag_ok     = $wpdb->update(
                $table,
                [ 'is_paid' => 1 ],
                [ 'id'      => $audit_id ],
                [ '%d' ],
                [ '%d' ]
            );

            if ( false === $flag_ok || false === $meta_ok ) {
                $wpdb->query( 'ROLLBACK' );
                return new WP_REST_Response( [ 'message' => 'Payment failed. Please try again.' ], 500 );
            }

            $wpdb->query( 'COMMIT' );

            return new WP_REST_Response( [
                'unlocked'      => true,
                'method'        => 'balance',
                'charged'       => 1.00,
                'balance_after' => round( $new_balance, 2 ),
            ], 200 );
        }

        // == Branch B: free daily credit ======================================
        if ( PW_Audit_Credit::can_use_daily_credit( $user_id ) ) {
            $flag_ok = $wpdb->update(
                $table,
                [ 'is_paid' => 1 ],
                [ 'id'      => $audit_id ],
                [ '%d' ],
                [ '%d' ]
            );

            if ( false === $flag_ok ) {
                return new WP_REST_Response( [ 'message' => 'Could not unlock. Please try again.' ], 500 );
            }

            PW_Audit_Credit::consume_daily_credit( $user_id );

            return new WP_REST_Response( [
                'unlocked' => true,
                'method'   => 'daily_credit',
            ], 200 );
        }

        // == Branch C: insufficient funds =====================================
        $shortfall = round( 1.00 - $balance, 2 );
        return new WP_REST_Response( [
            'unlocked'  => false,
            'message'   => 'Insufficient balance.',
            'balance'   => round( $balance, 2 ),
            'shortfall' => $shortfall,
        ], 402 );
    }


    private static function normalize_report( ?array $r ): ?array {
        if ( ! $r ) return null;
        $verdict = $r['admission']['verdict'] ?? 'allowed';
        $map = ['denied' => 'high', 'needs_check' => 'medium', 'allowed' => 'low'];
        return [
            'summary'         => $r['overall_summary'] ?? ( $r['summary'] ?? '' ),
            'admission'       => [
                'risk'    => $map[ $verdict ] ?? 'low',
                'details' => $r['admission']['summary'] ?? '',
            ],
            'demonetization'  => [
                'risk'    => $r['demonetization']['risk'] ?? 'low',
                'details' => $r['demonetization']['summary'] ?? '',
            ],
            'copyright'       => [
                'risk'    => $r['copyright']['risk'] ?? 'low',
                'details' => $r['copyright']['summary'] ?? '',
            ],
            'recommendations'    => $r['recommendations'] ?? [],
            'problematic_videos' => $r['problematic_videos'] ?? [],
            'action_plan'        => $r['action_plan'] ?? [],
        ];
    }
}
