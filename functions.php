<?php
/**
 * Functions
 * @author Ilya Sukharev <studiopioneer@gmail.com>
 * @copyright (c) 15.09.2024, CreativeMotion
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Выход, если напрямую обращаются к файлу
}

/**
 * Formats the project status based on the given status code.
 *
 * @param string $status The status code to be formatted. Possible values are 'rejected', 'review', 'approved', 'paid'.
 *
 * @return string The formatted status text corresponding to the given code. Returns an empty string if the status code is not recognized.
 */
function payway_format_project_status( $status ) {
	switch ( $status ) {
		case 'rejected':
			return 'Отклонен';
		case 'review':
			return 'На проверке';
		case 'approved':
			return 'Подтвержден';
		case 'paid':
			return 'Выплачено';
	}

	return '';
}

/**
 * Retrieves the balance based on the specified type.
 *
 * @param string $type Optional. The type of balance to retrieve. Can be 'withdrawal' for withdrawal balance or empty for the general balance.
 *
 * @return float The balance associated with the specified type for the current user.
 */
function payway_get_balance( $type = '' ) {
	$meta_key = 'withdrawal' == $type ? 'payway_withdrawal_balance' : 'payway_balance';

	return floatval( get_user_meta( get_current_user_id(), $meta_key, true ) );
}


// === Referral System Functions ===
function payway_get_or_create_referral_code( $user_id ) {
    $code = get_user_meta( $user_id, 'payway_referral_code', true );
    if ( empty( $code ) ) {
        $code = substr( md5( $user_id . wp_salt() ), 0, 12 );
        update_user_meta( $user_id, 'payway_referral_code', $code );
    }
    return $code;
}

function payway_handle_referral_cookie() {
    if ( isset( $_GET['ref'] ) && ! is_user_logged_in() ) {
        $code = sanitize_text_field( $_GET['ref'] );
        setcookie( 'payway_ref', $code, time() + 30 * DAY_IN_SECONDS, '/' );
    }
}
add_action( 'init', 'payway_handle_referral_cookie' );

function payway_link_referral( $user_id, $email ) {
    $code = isset( $_COOKIE['payway_ref'] ) ? sanitize_text_field( $_COOKIE['payway_ref'] ) : '';
    if ( empty( $code ) ) return;

    global $wpdb;
    $table = $wpdb->prefix . 'payway_referrals';

    // Find referrer by code
    $referrer_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'payway_referral_code' AND meta_value = %s LIMIT 1",
        $code
    ) );

    if ( ! $referrer_id || $referrer_id == $user_id ) return;

    // Check duplicate
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE referral_email = %s",
        $email
    ) );
    if ( $exists ) return;

    $wpdb->insert( $table, [
        'referrer_id'    => $referrer_id,
        'referral_email' => $email,
        'referral_code'  => $code,
        'created_at'     => current_time( 'mysql' ),
    ] );

    // Clear cookie
    setcookie( 'payway_ref', '', time() - 3600, '/' );
}


// Inject JS to load audit report when navigating to /audit?id=N from history
function payway_inject_audit_history_loader() {
    echo '<script>
(function(){
    var id = new URLSearchParams(location.search).get("id");
    if (!id || !location.pathname.match(/\/audit\/?$/)) return;
    function tryPatch(attempts) {
        if (attempts <= 0) return;
        var el = document.querySelector("[data-v-app]");
        if (!el || !el.__vue_app__) { setTimeout(function(){ tryPatch(attempts-1); }, 300); return; }
        var pinia = el.__vue_app__.config.globalProperties.$pinia;
        if (!pinia || !pinia._s) { setTimeout(function(){ tryPatch(attempts-1); }, 300); return; }
        var store = pinia._s.get("audit");
        if (!store) { setTimeout(function(){ tryPatch(attempts-1); }, 300); return; }
        store.auditId = parseInt(id);
        store.pollStatus();
    }
    setTimeout(function(){ tryPatch(20); }, 800);
})();
</script>';
}
add_action( 'wp_footer', 'payway_inject_audit_history_loader' );
