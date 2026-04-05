<?php
/**
 * Projects ajax handlers
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @copyright (c) 18.10.2024, CreativeMotion
 * @version 1.0
 */

add_action( 'wp_ajax_payway-delete-project', function () {
	global $wpdb;

	$project_id = (int) $_POST['project_id'];

	if ( ! current_user_can( 'read' ) ) {
		wp_send_json_error( 'You do not have permission to access this feature!' );
	}

	check_ajax_referer( 'cancel_project_' . $project_id );

	if ( ! $project_id ) {
		wp_send_json_error( 'Не передан id проекта!' );
	}

	$user_id = get_current_user_id();

	$result = $wpdb->delete( $wpdb->prefix . 'payway_projects', [
		'id'      => $project_id,
		'user_id' => $user_id
	] );

	if ( $result ) {
		wp_send_json_success();
	}
} );

add_action( 'wp_ajax_payway-create-project', function () {
	global $wpdb;

	if ( ! current_user_can( 'read' ) ) {
		wp_send_json_error( 'You do not have permission to access this feature!' );
	}

	check_ajax_referer( 'create_project_' . get_current_user_id() );

	if ( empty( $_POST['url'] ) ) {
		wp_send_json_error( [ '#url' => 'Поле url яляется обязательным для заполнения!' ] );
	}

	$user_id = get_current_user_id();

	$url         = sanitize_url( $_POST['url'] ?? '' );
	$amount      = sanitize_text_field( $_POST['amount'] ?? '' );
	$count_users = intval( $_POST['count_users'] ?? 0 );
	$comments    = sanitize_text_field( $_POST['comments'] ?? '' );
	$contacts    = sanitize_text_field( $_POST['contacts'] ?? '' );

	$result = $wpdb->insert(
		$wpdb->prefix . 'payway_projects',
		[
			'user_id'     => $user_id,
			'amount'      => $amount,
			'count_users' => $count_users,
			'time'        => current_time( 'mysql' ),
			'comments'    => $comments,
			'url'         => $url,
			'contacts'    => $contacts,
			'status'      => 'review',
		]
	);

	if ( $result ) {
		wp_send_json_success();
	}

	wp_send_json_error( 'Неизвестная ошибка при добавлении проекта!' );
} );
