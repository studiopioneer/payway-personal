<?php
/**
 * Ajax handlers
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @copyright (c) 15.09.2024, CreativeMotion
 * @version 1.0
 */


add_action( 'wp_ajax_payway-update-user-balance', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'You do not have permission to access this feature!' );
	}
	//check_ajax_referer( 'generate-post-thumbnails' );

	if ( isset( $_POST['entity'] ) && isset( $_POST['balance'] ) && ! empty( $_POST['user_id'] ) ) {
		$user_id = intval( $_POST['user_id'] );
		$balance = floatval( $_POST['balance'] );

		$meta_key = 'withdrawal' === $_POST['entity'] ? 'payway_withdrawal_balance' : 'payway_balance';

		update_user_meta( $user_id, $meta_key, $balance );

		wp_send_json_success( [ 'success' ] );
	}

	wp_send_json_error( 'Unknown error while updating status!' );
} );

//todo: требует доработки
add_action( 'wp_ajax_payway-update-status', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'You do not have permission to access this feature!' );
	}
	//check_ajax_referer( 'generate-post-thumbnails' );

	if ( ! empty( $_POST['id'] ) && ! empty( $_POST['entity'] ) && ! empty( $_POST['status'] ) ) {
		$entity    = sanitize_text_field( $_POST['entity'] );
		$entity_id = intval( $_POST['id'] );
		$status    = sanitize_text_field( $_POST['status'] );

		$review_comments = '';

		if ( isset( $_POST['review_comments'] ) ) {
			$review_comments = sanitize_text_field( $_POST['review_comments'] );
		}

		if ( ! in_array( $entity, [ 'withdrawal', 'unlock', 'projects' ] ) ) {
			wp_send_json_error( 'Получена недопустимая сущность для обновления!' );
		}

		global $wpdb;

            // Получаем предыдущий статус перед обновлением (для обработки баланса)
            $old_status = null;
            if ( $entity === 'withdrawal' ) {
                $old_status = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT status FROM {$wpdb->prefix}payway_withdrawal WHERE id = %d",
                        $entity_id
                    )
                );
            }

            $wpdb->update(
			$wpdb->prefix . 'payway_' . $entity,
			[
				'status'          => $status,
				'review_comments' => $review_comments
			],
			[ 'id' => $entity_id ]
		);

		
            // Обработка баланса при изменении статуса вывода
            if ( $entity === 'withdrawal' ) {
                $row = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT user_id, amount FROM {$wpdb->prefix}payway_withdrawal WHERE id = %d",
                        $entity_id
                    ),
                    ARRAY_A
                );

                if ( $row ) {
                    $user_id = intval( $row['user_id'] );
                    $amount = floatval( $row['amount'] );
                    $current_balance = floatval( get_user_meta( $user_id, 'payway_withdrawal_balance', true ) );

                    // Списание: переход в статус "Выплачено" (защита от двойного списания)
                    if ( $status === 'paid' && $old_status !== 'paid' ) {
                        $new_balance = $current_balance - $amount;
                        update_user_meta( $user_id, 'payway_withdrawal_balance', $new_balance );
                    }

                    // Возврат: переход ИЗ статуса "Выплачено" в другой статус
                    if ( $old_status === 'paid' && $status !== 'paid' ) {
                        $new_balance = $current_balance + $amount;
                        update_user_meta( $user_id, 'payway_withdrawal_balance', $new_balance );
                    }
                }
            }

            wp_send_json_success( [ 'success' ] );
	}

	wp_send_json_error( 'Неизвестная ошибка при обновлении статуса!', );
} );

add_action( 'wp_ajax_payway-delete-entity', function () {
	global $wpdb;

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'You do not have permission to access this feature!' );
	}
	//check_ajax_referer( 'generate-post-thumbnails' );

	if ( ! empty( $_POST['id'] ) && ! empty( $_POST['entity'] ) ) {
		$entity    = sanitize_text_field( $_POST['entity'] );
		$entity_id = intval( $_POST['id'] );


		if ( ! in_array( $entity, [ 'withdrawal', 'unlock', 'projects' ] ) ) {
			wp_send_json_error( 'Получена недопустимая сущность для обновления!' );
		}

		$result = $wpdb->delete( $wpdb->prefix . 'payway_' . $entity, [
			'id' => $entity_id
		] );

		if ( $result ) {
			wp_send_json_success();
		}
	}

	wp_send_json_error( 'Неизвестная ошибка при обновлении статуса!', );
} );
