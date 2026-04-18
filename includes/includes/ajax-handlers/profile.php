<?php
/**
 * Profile ajax handlers
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @copyright (c) 18.10.2024, CreativeMotion
 * @version 1.0
 */


/*add_action( 'wp_ajax_payway-check-email', function () {
	if ( ! current_user_can( 'read' ) ) {
		wp_send_json_error( 'У вас недостаточно прав для использования этой функции!' );
	}

	check_ajax_referer( 'edit_profile_' . get_current_user_id() );

	if ( empty( $_POST['email'] ) ) {
		wp_send_json_error( 'Не передан email пользователя!' );
	}

	$email = sanitize_text_field( $_POST['email'] );
	$user  = wp_get_current_user();

	$owner_id = email_exists( $email );

	if ( $owner_id && ( $owner_id !== $user->ID ) ) {
		wp_send_json_error( 'Пользователь с таким email уже сущестует!' );
	}

	wp_send_json_success();
} );*/

add_action( 'wp_ajax_payway-edit-profile', function () {
	if ( ! current_user_can( 'read' ) ) {
		wp_send_json_error( 'У вас недостаточно прав для использования этой функции!' );
	}

	check_ajax_referer( 'edit_profile_' . get_current_user_id() );

	if ( empty( $_POST['email'] ) && empty( $_POST['name'] ) ) {
		wp_send_json_error( 'Не переданы обязательные поля имя и email!' );
	}

	$password_changed = $email_changed = false;

	$user = wp_get_current_user();

	$name  = sanitize_text_field( $_POST['name'] );
	$email = sanitize_text_field( trim( $_POST['email'] ) );

	if ( !is_email( $email ) ) {
		wp_send_json_error( [ '#email' => 'Введен некорректный email адрес!' ] );
	}

	if ( $email !== $user->user_email ) {
		$owner_id = email_exists( $email );

		if ( $owner_id && ( $owner_id !== $user->ID ) ) {
			wp_send_json_error( [ '#email' => 'Пользователь с таким email уже сущестует!' ] );
		}

		$email_changed = true;
	}

	$user->display_name = $name;
	$user->user_email   = $email;

	if ( ! empty( $_POST['password'] ) && ! empty( $_POST['repassword'] ) ) {
		$password   = trim( $_POST['password'] );
		$repassword = trim( $_POST['repassword'] );

		if ( $password !== $repassword ) {
			wp_send_json_error( [ '#repassword' => 'Введенные пароли не совпадают!' ] );
		}

		$user->user_pass  = $password;
		$password_changed = true;
	}


	$user_id = wp_update_user( $user );

	if ( is_wp_error( $user_id ) ) {
		wp_send_json_error( $user_id->get_error_message() );
	}

	wp_send_json_success(['password_changed' => $password_changed, 'email_changed' => $email_changed]);
} );