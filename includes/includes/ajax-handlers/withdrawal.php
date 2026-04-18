<?php
/**
 * Withdrawal ajax handlers
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @copyright (c) 18.10.2024, CreativeMotion
 * @version 1.0
 */

add_action( 'wp_ajax_payway-create-withdrawal', function () {

	if ( ! current_user_can( 'read' ) ) {
		wp_send_json_error( 'You do not have permission to access this feature!' );
	}

	//check_ajax_referer( 'generate-post-thumbnails' );

	if ( empty( $_POST['payment_type'] ) || ! isset( $_POST['amount'] ) ) {
		wp_send_json_error( 'Required fields are not filled in!' );
	}

	$user_id         = get_current_user_id();
	$payment_type    = sanitize_text_field( $_POST['payment_type'] );
	$payment_details = sanitize_text_field( $_POST['payment_details'] );
	$amount          = floatval( $_POST['amount'] );
	$comments        = sanitize_text_field( $_POST['comments'] );

	global $wpdb;

	$wpdb->insert(
		$wpdb->prefix . 'payway_withdrawal',
		[
			'user_id'         => $user_id,
			'amount'          => $amount,
			'payment_type'    => $payment_type,
			'payment_details' => $payment_details,
			'comments'        => $comments,
			'time'            => current_time( 'mysql', 1 ),
			'status'          => 'review',
		]
	);

	wp_send_json_success( [ 'success', $_POST ] );

	//wp_send_json_error( 'Unknown error while updating status!' );
} );