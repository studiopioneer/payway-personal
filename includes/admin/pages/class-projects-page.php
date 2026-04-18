<?php

namespace Payway\Pages;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Выход, если напрямую обращаются к файлу
}

/**
 * Implements the functionality for rendering and managing the "Projects" page
 * within the WordPress admin dashboard.
 *
 * Extends the AbstractAdminPage class to provide a structured implementation
 * for defining page settings, menu details, and rendering the associated
 * admin interface.
 *
 * @author Alexander Kovalev <alex.kovalevv@gmail.com> <Tg:@alex_kovalevv>
 * @copyright (c) 08.01.2025, CreativeMotion
 */
class ProjectsPage extends AbstractAdminPage {

	/**
	 * Initializes the page settings including title, menu, slug, columns, and list table configurations.
	 *
	 * @return void
	 */
	protected function init_page_settings(): void {
		$this->page_title            = 'Проекты';
		$this->menu_title            = 'Проекты';
		$this->menu_slug             = 'payway-projects';
		$this->columns               = [
			'comments' => 250,
			'status'   => 150,
			'time'     => 160,
			'actions'  => 80,
		];
		$this->list_table_file       = 'class-projects.php';
		$this->list_table_class_name = 'Projects_List_Table';
		$this->db_table_name         = 'payway_projects';
	}

	/**
	 * Renders the admin page by invoking the parent method and displaying the popup modal.
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		parent::render_admin_page();
		$this->render_popup_modal();
	}

	/**
	 * Renders the edit form for the specified entity based on the provided ID.
	 *
	 * @param int $id The unique identifier of the entity to be edited.
	 *
	 * @return void
	 */
	protected function render_edit_form( int $id ) {
		if ( $id <= 0 ) {
			wp_die( 'Неверный идентификатор проекта.' );
		}

		global $wpdb;

		$table_name = $wpdb->prefix . $this->db_table_name;

		$record = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $id ),
			ARRAY_A
		);

		if ( ! $record ) {
			wp_die( 'Проект с указанным ID не найден.' );
		}
		?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Редактировать проект</h1>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $this->menu_slug ) ); ?>"
               class="page-title-action">Вернуться к списку</a>
            <form method="post">
				<?php wp_nonce_field( 'edit_project_action' ); ?>
                <input type="hidden" name="project_id" value="<?php echo esc_attr( $record['id'] ); ?>">
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row">
                            <label for="user_id">Пользователь</label>
                        </th>
                        <td>
                            <select name="user_id" id="user_id" class="regular-text" required>
                                <option value="">Выберите пользователя</option>
								<?php
								$users = get_users();
								foreach ( $users as $user ) {
									$user_info = sprintf( '%s (%s)', esc_html( $user->user_email ), esc_html( $user->display_name ) );
									$selected  = selected( $record['user_id'], $user->ID, false );
									printf(
										'<option value="%d" %s>%s</option>',
										esc_attr( $user->ID ),
										$selected,
										esc_html( $user_info )
									);
								}
								?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="url">Ссылка</label>
                        </th>
                        <td>
                            <input name="url" type="url" id="url" value="<?php echo esc_attr( $record['url'] ); ?>"
                                   class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="amount">Оборот</label>
                        </th>
                        <td>
                            <input name="amount" type="text" id="amount"
                                   value="<?php echo esc_attr( $record['amount'] ); ?>"
                                   class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="count_users">Количество пользователей</label>
                        </th>
                        <td>
                            <input name="count_users" type="number" id="count_users"
                                   value="<?php echo esc_attr( $record['count_users'] ); ?>"
                                   class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="comments">Комментарии</label>
                        </th>
                        <td>
                        <textarea name="comments" id="comments" rows="5"
                                  class="large-text"><?php echo esc_textarea( $record['comments'] ); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="contacts">Дополнительные контакты</label>
                        </th>
                        <td>
                            <input name="contacts" type="text" id="contacts"
                                   value="<?php echo esc_attr( $record['contacts'] ); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="status">Статус</label>
                        </th>
                        <td>
                            <select name="status" id="status" class="regular-text">
                                <option value="approved" <?php selected( $record['status'], 'approved' ); ?>>
                                    Подтвержден
                                </option>
                                <option value="review" <?php selected( $record['status'], 'review' ); ?>>На проверке
                                </option>
                                <option value="rejected" <?php selected( $record['status'], 'rejected' ); ?>>Отклонен
                                </option>
                            </select>
                        </td>
                    </tr>
                    </tbody>
                </table>
				<?php submit_button( 'Сохранить изменения', 'primary', 'edit_project_submit' ); ?>
            </form>
        </div>
		<?php
	}

	/**
	 * Handles the processing of the edit form functionality.
	 *
	 * @return void
	 */
	/**
	 * Handles the submission of the edit form.
	 *
	 * @return void
	 */
	public function handle_edit_form() {
		if ( ! isset( $_POST['edit_project_submit'] ) ) {
			return;
		}

		// Проверяем безопасность запроса
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'edit_project_action' ) ) {
			wp_die( 'Ошибка проверки безопасности.' );
		}

		// Проверяем права пользователя
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'У вас нет доступа.' );
		}

		// Получаем данные из формы
		$project_id      = isset( $_POST['project_id'] ) ? intval( $_POST['project_id'] ) : 0;
		$user_id         = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		$url             = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';
		$amount          = isset( $_POST['amount'] ) ? sanitize_text_field( $_POST['amount'] ) : '';
		$count_users     = isset( $_POST['count_users'] ) ? intval( $_POST['count_users'] ) : 0;
		$comments        = isset( $_POST['comments'] ) ? sanitize_textarea_field( $_POST['comments'] ) : '';
		$review_comments = isset( $_POST['review_comments'] ) ? sanitize_textarea_field( $_POST['review_comments'] ) : '';
		$contacts        = isset( $_POST['contacts'] ) ? sanitize_text_field( $_POST['contacts'] ) : '';
		$status          = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';

		// Проверка минимальных полей
		if ( $project_id <= 0 || empty( $url ) || empty( $amount ) || $count_users <= 0 ) {
			wp_die( 'Пожалуйста, заполните все обязательные поля.' );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . $this->db_table_name;

		// Убедимся, что проект существует
		$existing_record = $wpdb->get_row(
			$wpdb->prepare( "SELECT id FROM {$table_name} WHERE id = %d", $project_id ),
			ARRAY_A
		);

		if ( ! $existing_record ) {
			wp_die( 'Проект с указанным ID не найден.' );
		}

		// Обновление данных в БД
		$updated = $wpdb->update(
			$table_name,
			[
				'user_id'         => $user_id,
				'url'             => $url,
				'amount'          => $amount,
				'count_users'     => $count_users,
				'comments'        => $comments,
				'review_comments' => $review_comments,
				'contacts'        => $contacts,
				'status'          => $status
			],
			[ 'id' => $project_id ],
			[
				'%d', // user_id
				'%s', // url
				'%s', // amount
				'%d', // count_users
				'%s', // comments
				'%s', // review_comments
				'%s', // contacts
				'%s'  // status
			],
			[ '%d' ] // id
		);

		if ( false === $updated ) {
			wp_die( 'Ошибка при сохранении данных.' );
		}

		// Перенаправление после успешного обновления
		wp_redirect( admin_url( 'admin.php?page=' . $this->menu_slug . '&updated=true' ) );
		exit;
	}
}