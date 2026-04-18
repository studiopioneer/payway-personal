<?php

namespace Payway\Pages;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Manages the "Statistics" page within the WordPress admin dashboard.
 *
 * This class provides functionality for rendering, managing, and importing CSV data
 * related to project statistics. It extends the AbstractAdminPage class for
 * integration into the WordPress admin interface.
 *
 * @author Alexander Kovalev <alex.kovalevv@gmail.com> <Tg:@alex_kovalevv>
 * @copyright (c) 08.01.2025, CreativeMotion
 */
class StatsPage extends AbstractAdminPage {

	/**
	 * Initializes page-specific settings such as titles, menu configuration,
	 * column settings, and references to the associated list table file and class.
	 *
	 * @return void
	 */
	protected function init_page_settings(): void {
		$this->page_title            = 'Статистика';
		$this->menu_title            = 'Статистика';
		$this->menu_slug             = 'payway-stats';
		$this->columns               = [
			'estimated_earnings_usd' => 140,
			'clicks'                 => 100,
			'page_views'             => 120,
			'page_rpm_usd'           => 150,
			'impressions'            => 120,
			'period'                 => 120,
			'active_view_viewable'   => 130

		];
		$this->list_table_file       = 'class-stats.php';
		$this->list_table_class_name = 'Stats_List_Table';
		$this->db_table_name         = 'payway_stats';
	}

	/**
	 * Renders the admin page, including the list table display or
	 * the CSV import form based on the current action.
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' && isset( $_GET['id'] ) ) {
			$this->render_edit_form( absint( $_GET['id'] ) );

			return;
		}

		if ( isset( $_GET['action'] ) && $_GET['action'] === 'import_csv' ) {
			$this->render_csv_import_form();

			return;
		}

		require_once PAYWAY_PLUGIN_DIR . '/admin/pages/list-tables/' . $this->list_table_file;

		$list_table = new $this->list_table_class_name();
		$list_table->prepare_items();

		$import_url = add_query_arg( 'action', 'import_csv', admin_url( 'admin.php?page=' . $this->menu_slug ) );
		?>

        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html( $this->page_title ); ?></h1>
            <a href="<?php echo esc_url( $import_url ); ?>" class="page-title-action">Import CSV</a>

            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr( $this->menu_slug ); ?>"/>
				<?php $list_table->display(); ?>
            </form>
        </div>
		<?php
	}

	/**
	 * Renders the edit form for a specific record.
	 *
	 * @param int $id The ID of the record to edit.
	 *
	 * @return void
	 */
	protected function render_edit_form( int $id ): void {
		if ( $id <= 0 ) {
			wp_die( 'Неверный идентификатор проекта.' );
		}

		global $wpdb;

		$table_name = $wpdb->prefix . 'payway_stats';
		$record     = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ), ARRAY_A );

		if ( ! $record ) {
			wp_die( 'Статистка с указанным ID не найдена.' );
		}

		?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Edit Record</h1>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $this->menu_slug ) ); ?>"
               class="button secondary">
                Back to List
            </a>
            <form method="post">
				<?php wp_nonce_field( 'edit_stat_action' ); ?>
                <input type="hidden" name="record_id" value="<?php echo esc_attr( $id ); ?>">

                <table class="form-table">
                    <tr>
                        <th><label for="estimated_earnings_usd">Estimated Earnings (USD)</label></th>
                        <td>
                            <input type="number" step="0.01" name="estimated_earnings_usd" id="estimated_earnings_usd"
                                   value="<?php echo esc_attr( $record['estimated_earnings_usd'] ); ?>"
                                   class="regular-text"
                                   required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="clicks">Clicks</label></th>
                        <td>
                            <input type="number" name="clicks" id="clicks"
                                   value="<?php echo esc_attr( $record['clicks'] ); ?>"
                                   class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="page_views">Page Views</label></th>
                        <td>
                            <input type="number" name="page_views" id="page_views"
                                   value="<?php echo esc_attr( $record['page_views'] ); ?>" class="regular-text"
                                   required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="page_rpm_usd">Page RPM (USD)</label></th>
                        <td>
                            <input type="number" step="0.01" name="page_rpm_usd" id="page_rpm_usd"
                                   value="<?php echo esc_attr( $record['page_rpm_usd'] ); ?>" class="regular-text"
                                   required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="impressions">Impressions</label></th>
                        <td>
                            <input type="number" name="impressions" id="impressions"
                                   value="<?php echo esc_attr( $record['impressions'] ); ?>" class="regular-text"
                                   required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="active_view_viewable">Active View Viewable</label></th>
                        <td>
                            <input type="text" name="active_view_viewable" id="active_view_viewable"
                                   value="<?php echo esc_attr( $record['active_view_viewable'] ); ?>"
                                   class="regular-text"
                                   required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="period">Date start</label></th>
                        <td>
                            <input type="text" name="date_start" id="date-start"
                                   value="<?php echo esc_attr( $record['date_start'] ); ?>"
                                   class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="period">Date end</label></th>
                        <td>
                            <input type="text" name="date_end" id="date-end"
                                   value="<?php echo esc_attr( $record['date_end'] ); ?>"
                                   class="regular-text" required>
                        </td>
                    </tr>
                </table>


				<?php submit_button( 'Сохранить изменения', 'primary', 'edit_stat_submit' ); ?>
            </form>
        </div>
		<?php
	}

	/**
	 * Renders the form for CSV file import, processes uploaded files,
	 * and displays the processed data or error messages.
	 *
	 * @return void
	 */
	private function render_csv_import_form(): void {
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_FILES['csv_file'] ) ) {
			check_admin_referer( 'csv_file_upload' );

			try {
				$this->handle_csv_upload();

				if ( ! empty( $this->errors ) ) {
					$this->render_errors();
				} else {
					echo '<div class="updated"><p>CSV file successfully imported.</p></div>';
				}

				return;
			} catch ( Exception $e ) {
				// Show critical errors
				echo '<div class="error"><p>' . esc_html( $e->getMessage() ) . '</p></div>';
			}
		}

		?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Upload CSV</h1>
            <a href="<?php echo esc_url( remove_query_arg( 'action' ) ); ?>" class="button secondary">
                Go back
            </a>
            <form method="post" enctype="multipart/form-data" class="csv-import-form">
				<?php wp_nonce_field( 'csv_file_upload' ); ?>
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row">
                            <label for="csv_file">CSV file</label>
                        </th>
                        <td>
                            <input type="file" name="csv_file" id="csv_file" accept=".csv" required
                                   class="regular-text">
                            <p class="description">Please upload a valid CSV file.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="date_start">Start Date</label>
                        </th>
                        <td>
                            <input type="date" name="date_start" id="date_start" required class="regular-text">
                            <p class="description">Select the starting date (YYYY-MM-DD).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="date_end">End Date</label>
                        </th>
                        <td>
                            <input type="date" name="date_end" id="date_end" value="<?php echo date( 'Y-m-d' ); ?>"
                                   required class="regular-text">
                            <p class="description">Select the ending date (YYYY-MM-DD).</p>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <div class="form-action-buttons">
					<?php submit_button( 'Upload CSV', 'primary', 'upload_csv' ); ?>
                </div>
            </form>
        </div>
		<?php
	}

	/**
	 * Renders a list of errors, displaying each error's associated row and message in a structured format.
	 * Outputs HTML for the error list directly.
	 *
	 * @return void This method does not return a value.
	 */
	private function render_errors(): void {
		if ( empty( $this->errors ) ) {
			return;
		}
		echo '<div class="error-list">';
		foreach ( $this->errors as $error ) {
			echo '<div class="error-row">';
			echo '<pre>' . esc_html( print_r( $error['row'], true ) ) . '</pre>';
			echo '<p>' . esc_html( $error['message'] ) . '</p>';
			echo '</div>';
		}
		echo '</div>';
	}

	/**
	 * Validates the format of the provided date string (YYYY-MM-DD).
	 *
	 * @param string $date Date string to validate.
	 *
	 * @return bool True if valid, false otherwise.
	 */
	private function is_valid_date( string $date ): bool {
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) && strtotime( $date ) !== false;
	}

	/**
	 * Handles the edit form submission and updates the record in the database.
	 *
	 * @return void
	 */
	public function handle_edit_form(): void {
		if ( isset( $_POST['edit_stat_submit'] ) ) {
			check_admin_referer( 'edit_stat_action' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}

			global $wpdb;

			$id                     = absint( $_POST['record_id'] );
			$estimated_earnings_usd = floatval( $_POST['estimated_earnings_usd'] );
			$clicks                 = absint( $_POST['clicks'] );
			$page_views             = absint( $_POST['page_views'] );
			$page_rpm_usd           = floatval( $_POST['page_rpm_usd'] );
			$impressions            = absint( $_POST['impressions'] );
			$active_view_viewable   = sanitize_text_field( $_POST['active_view_viewable'] );
			$date_start             = date( "Y-m-d", strtotime( $_POST['date_start'] ) );
			$date_end               = date( "Y-m-d", strtotime( $_POST['date_end'] ) );

			$table_name = $wpdb->prefix . $this->db_table_name;

			$updated = $wpdb->update(
				$table_name,
				[
					'estimated_earnings_usd' => $estimated_earnings_usd,
					'clicks'                 => $clicks,
					'page_views'             => $page_views,
					'page_rpm_usd'           => $page_rpm_usd,
					'impressions'            => $impressions,
					'active_view_viewable'   => $active_view_viewable,
					'date_start'             => $date_start,
					'date_end'               => $date_end,
				],
				[ 'id' => $id ],
				[ '%f', '%d', '%d', '%f', '%d', '%s', '%s', '%s' ],
				[ '%d' ]
			);

			if ( false === $updated ) {
				wp_die( 'Ошибка при сохранении данных.' );
			}

			if ( $updated !== false ) {
				wp_redirect( admin_url( 'admin.php?page=' . $this->menu_slug . '&updated=true' ) );
				exit;
			}
		}
	}

	/**
	 * Processes the uploaded CSV file and parses its data into an array.
	 *
	 * @throws Exception If the file upload or parsing fails
	 */
	private function handle_csv_upload() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$date_start = sanitize_text_field( $_POST['date_start'] ?? '' );
		$date_end   = sanitize_text_field( $_POST['date_end'] ?? '' );

		if ( ! $this->is_valid_date( $date_start ) || ! $this->is_valid_date( $date_end ) ) {
			throw new Exception( 'Invalid date format. Use YYYY-MM-DD.' );
		}

		if ( strtotime( $date_start ) > strtotime( $date_end ) ) {
			throw new Exception( 'Start date cannot be later than end date.' );
		}

		$this->validate_uploaded_file();

		$uploaded_file = $_FILES['csv_file']['tmp_name'];

		if ( ( $handle = fopen( $uploaded_file, 'r' ) ) === false ) {
			throw new Exception( 'Failed to open the CSV file.' );
		}

		$header = fgetcsv( $handle );
		fclose( $handle );

		$expected_header = array_keys( $this->get_csv_headers_map() );
		if ( $header !== $expected_header ) {
			throw new Exception( 'CSV header does not match the expected format.' );
		}

		$parsed_data = $this->parse_csv_data( $uploaded_file, $header );

		$mapped_data = $this->map_csv_headers( $parsed_data );

		if ( empty( $mapped_data ) ) {
			throw new Exception( 'No valid data found in the CSV file.' );
		}


		foreach ( $mapped_data as $row ) {
			$this->save_csv_row( $row, $date_start, $date_end );
		}

	}

	/**
	 * Inserts a single row of CSV data into the database.
	 *
	 * @param array $row The associative array containing the data row.
	 * @param string $date_start The start date for the data period.
	 * @param string $date_end The end date for the data period.
	 *
	 * @return void
	 */
	private function save_csv_row( array $row, string $date_start, string $date_end ): void {
		global $wpdb;

		$site_url = sanitize_text_field( $row['site'] );

		if ( empty( $site_url ) ) {
			$this->errors[] = [
				'row'     => $row,
				'message' => 'Missing "site" value.',
			];

			return;
		}

		$parsed_url = wp_parse_url( $site_url );
		$domain     = $parsed_url['host'] ?? $site_url;

		$project_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}payway_projects WHERE url LIKE %s",
				'%' . $wpdb->esc_like( $domain ) . '%'
			)
		);

		if ( ! $project_id ) {
			$this->errors[] = [
				'row'     => $row,
				'message' => "Project not found for site URL: $site_url",
			];

			return;
		}

		// Проверка на существование записей за указанный период
		$existing_entry = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}payway_stats
            WHERE project_id = %d AND site = %s AND date_start = %s AND date_end =  %s",
				$project_id,
				$site_url,
				$date_start,
				$date_end
			)
		);

		if ( $existing_entry > 0 ) {
			throw new Exception( "Data for the period between $date_start and $date_end already exists for site: $site_url." );
		}

		$insert_data = [
			'project_id'             => (int) $project_id,
			'site'                   => $site_url,
			'estimated_earnings_usd' => $row['estimated_earnings_usd'] ?? null,
			'page_views'             => $row['page_views'] ?? null,
			'page_rpm_usd'           => $row['page_rpm_usd'] ?? null,
			'impressions'            => $row['impressions'] ?? null,
			'impression_rpm_usd'     => $row['impression_rpm_usd'] ?? null,
			'active_view_viewable'   => $row['active_view_viewable'] ?? null,
			'clicks'                 => $row['clicks'] ?? null,
			'date_start'             => $date_start,
			'date_end'               => $date_end,
		];

		$wpdb->insert(
			"{$wpdb->prefix}payway_stats",
			$insert_data,
			[
				'%d',  // project_id
				'%s',  // site
				'%f',  // estimated_earnings_usd
				'%d',  // page_views
				'%f',  // page_rpm_usd
				'%d',  // impressions
				'%f',  // impression_rpm_usd
				'%f',  // active_view_viewable
				'%d',  // clicks
				'%s',  // date_start
				'%s',  // date_end
			]
		);

		if ( $wpdb->last_error ) {
			$this->errors[] = [
				'row'     => $row,
				'message' => "Failed to insert row into stats: " . $wpdb->last_error,
			];
		}
	}

	/**
	 * Validates the uploaded CSV file's structure and format.
	 *
	 * @throws Exception If the uploaded file is invalid or not a CSV
	 */
	private function validate_uploaded_file(): void {
		if ( empty( $_FILES['csv_file'] ) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
			throw new Exception( 'There was an error uploading the file. Please try again.' );
		}

		$file_extension = strtolower( pathinfo( $_FILES['csv_file']['name'], PATHINFO_EXTENSION ) );
		if ( $file_extension !== 'csv' ) {
			throw new Exception( 'The uploaded file must be a CSV file.' );
		}
	}

	/**
	 * Parses CSV data and transforms rows into associative arrays.
	 *
	 * @param string $file_path Path to the uploaded CSV file.
	 * @param array $header CSV header keys.
	 *
	 * @return array Parsed rows as associative arrays.
	 * @throws Exception If the file cannot be opened or read
	 */
	private function parse_csv_data( string $file_path, array $header ): array {
		$parsed_data = [];

		if ( ( $handle = fopen( $file_path, 'r' ) ) === false ) {
			throw new Exception( 'Failed to open the CSV file.' );
		}

		// Skip the header row, since it's already processed
		fgetcsv( $handle );

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( count( $row ) !== count( $header ) ) {
				continue; // Skip invalid rows
			}

			$parsed_data[] = array_combine( $header, $row );
		}

		fclose( $handle );

		return $parsed_data;
	}

	/**
	 * Maps CSV header keys to internal logical field names using a predefined map.
	 *
	 * @param array $parsed_data Data parsed from the CSV file as associative arrays.
	 *
	 * @return array Transformed data with logical field keys.
	 */
	private function map_csv_headers( array $parsed_data ): array {
		$header_map  = $this->get_csv_headers_map();
		$mapped_data = [];

		foreach ( $parsed_data as $original_row ) {
			$mapped_row = [];
			foreach ( $original_row as $key => $value ) {
				$logical_key                = $header_map[ $key ] ?? $key; // Replace keys
				$mapped_row[ $logical_key ] = $value;
			}
			$mapped_data[] = $mapped_row;
		}

		return $mapped_data;
	}

	/**
	 * Provides the mapping of CSV headers to internal field names.
	 *
	 * @return array Mapping of CSV headers to logical field names.
	 */
	private function get_csv_headers_map(): array {
		return [
			'Site'                     => 'site',
			'Estimated earnings (USD)' => 'estimated_earnings_usd',
			'Page views'               => 'page_views',
			'Page RPM (USD)'           => 'page_rpm_usd',
			'Impressions'              => 'impressions',
			'Impression RPM (USD)'     => 'impression_rpm_usd',
			'Active View Viewable'     => 'active_view_viewable',
			'Clicks'                   => 'clicks',
		];
	}
}