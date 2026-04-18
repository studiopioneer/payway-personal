<?php
require_once __DIR__ . '/class-base-controller.php';

class ReferralController extends BaseController {

    public function handle_get_request(): WP_REST_Response {
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return new WP_Error( 'not_logged_in', 'Not authenticated', [ 'status' => 401 ] );
        }
        $code = payway_get_or_create_referral_code( $user_id );
        $url  = home_url( '/?ref=' . $code );
        return rest_ensure_response( [ 'code' => $code, 'url' => $url ] );
    }

    public function get_my_referrals( $request ) {
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return new WP_Error( 'not_logged_in', 'Not authenticated', [ 'status' => 401 ] );
        }
        global $wpdb;
        $table = $wpdb->prefix . 'payway_referrals';
        $rows  = $wpdb->get_results( $wpdb->prepare(
            "SELECT referral_email, created_at FROM {$table} WHERE referrer_id = %d ORDER BY created_at DESC",
            $user_id
        ), ARRAY_A );
        return rest_ensure_response( [ 'data' => $rows ?: [] ] );
    }

    public function get_all_referrals( $request ) {
        global $wpdb;
        $table = $wpdb->prefix . 'payway_referrals';
        $rows  = $wpdb->get_results(
            "SELECT r.*, u.user_email AS referrer_email
             FROM {$table} r
             LEFT JOIN {$wpdb->users} u ON r.referrer_id = u.ID
             ORDER BY r.created_at DESC",
            ARRAY_A
        );
        return rest_ensure_response( [ 'data' => $rows ?: [] ] );
    }
    protected function validate_request_data(): bool { return true; }
    protected function sanitize_request_data(): array { return []; }
    protected function format_response_data(array $items): array { return $items; }
}
