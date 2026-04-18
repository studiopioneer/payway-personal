<?php
/**
 * Unlock ajax handlers
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @copyright (c) 18.10.2024, CreativeMotion
 * @version 1.0
 */


add_action( 'wp_ajax_payway-create-unlock', function () {

	if ( ! current_user_can( 'read' ) ) {
		wp_send_json_error( 'You do not have permission to access this feature!' );
	}

	//check_ajax_referer( 'generate-post-thumbnails' );

	if ( ! isset( $_POST['amount'] ) ) {
		wp_send_json_error( 'Required fields are not filled in!' );
	}

	$user_id = get_current_user_id();
	$amount  = floatval( $_POST['amount'] );

	global $wpdb;

	$wpdb->insert(
		$wpdb->prefix . 'payway_unlock',
		[
			'user_id' => $user_id,
			'amount'  => $amount,
			'time'    => current_time( 'mysql', 1 ),
			'status'  => 'review',
		]
	);

	wp_send_json_success( [ 'success', $_POST ] );

	//wp_send_json_error( 'Unknown error while updating status!' );
} );