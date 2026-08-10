<?php

/**
 * CSV export/import for the "events" custom post type and its ACF fields.
 *
 * Adds an "Import/Export" submenu under the Events post type admin menu with
 * a button to download all events as CSV, and a form to upload a CSV and
 * create/update events from it (including sideloading image URLs into the
 * media library for event_banner_image/event_past_image_square).
 *
 * @link       https://lydbaligroup.com
 * @since      1.0.0
 *
 * @package    Labrisa_Core
 * @subpackage Labrisa_Core/admin
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Labrisa_Core_Admin_Events_CSV
 *
 * @since      1.0.0
 * @package    Labrisa_Core
 * @subpackage Labrisa_Core/admin
 */
class Labrisa_Core_Admin_Events_CSV {

	/**
	 * Capability required to view the screen and run an export/import.
	 */
	const CAPABILITY = 'edit_posts';

	/**
	 * Nonce action shared by the export/import/sample-download forms.
	 */
	const NONCE_ACTION = 'labrisa_core_events_csv';

	/**
	 * Column order used by both export and import. Column names match the
	 * ACF field names on the "events" post type 1:1 (see
	 * sources/acf-export-2026-08-05.json) except for the WordPress-native
	 * ID/post_title/post_content/post_status columns.
	 *
	 * @var string[]
	 */
	const CSV_COLUMNS = array(
		'ID',
		'post_title',
		'post_content',
		'post_status',
		'event_type',
		'event_line_up',
		'event_place',
		'event_date',
		'event_end_date',
		'event_banner_image',
		'event_past_image_square',
		'event_ticket_url',
		'event_terms_and_conditions',
	);

	/**
	 * Register the "Import/Export" submenu page under the Events post type.
	 *
	 * @since    1.0.0
	 */
	public function register_menu() {
		add_submenu_page(
			'edit.php?post_type=' . Labrisa_Core_Events::POST_TYPE,
			__( 'Import/Export Events', 'labrisa-core' ),
			__( 'Import/Export', 'labrisa-core' ),
			self::CAPABILITY,
			'labrisa-core-events-csv',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the Import/Export admin screen.
	 *
	 * @since    1.0.0
	 */
	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to access this page.', 'labrisa-core' ) );
		}

		$result = get_transient( 'labrisa_core_events_csv_import_result_' . get_current_user_id() );
		delete_transient( 'labrisa_core_events_csv_import_result_' . get_current_user_id() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import/Export Events', 'labrisa-core' ); ?></h1>

			<?php if ( $result ) : ?>
				<div class="notice notice-<?php echo empty( $result['errors'] ) ? 'success' : 'warning'; ?> is-dismissible">
					<p>
						<?php
						printf(
							/* translators: 1: number of events created, 2: number of events updated */
							esc_html__( 'Import finished — %1$d created, %2$d updated.', 'labrisa-core' ),
							(int) $result['created'],
							(int) $result['updated']
						);
						?>
					</p>
					<?php if ( ! empty( $result['errors'] ) ) : ?>
						<ul style="list-style:disc;margin-left:20px;">
							<?php foreach ( $result['errors'] as $error ) : ?>
								<li><?php echo esc_html( $error ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Export', 'labrisa-core' ); ?></h2>
			<p><?php esc_html_e( 'Download every event (any status) as a CSV file, including its ACF fields.', 'labrisa-core' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<input type="hidden" name="action" value="labrisa_core_export_events" />
				<?php submit_button( __( 'Download CSV', 'labrisa-core' ), 'secondary', 'submit', false ); ?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Import', 'labrisa-core' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %s: list of CSV column names */
					esc_html__( 'Upload a CSV with these columns: %s', 'labrisa-core' ),
					'<code>' . esc_html( implode( ', ', self::CSV_COLUMNS ) ) . '</code>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
				?>
			</p>
			<ul style="list-style:disc;margin-left:20px;">
				<li><?php esc_html_e( 'Leave ID empty to create a new event; fill it in to update an existing one.', 'labrisa-core' ); ?></li>
				<li><?php esc_html_e( 'event_line_up supports multiple terms separated by a pipe character, e.g. "Term A|Term B". Terms that do not exist yet are created automatically.', 'labrisa-core' ); ?></li>
				<li><?php esc_html_e( 'event_date and event_end_date must be in "Y-m-d H:i:s" format, e.g. 2026-08-15 21:00:00.', 'labrisa-core' ); ?></li>
				<li><?php esc_html_e( 'event_banner_image and event_past_image_square accept either an image URL (downloaded into the media library) or an existing attachment ID.', 'labrisa-core' ); ?></li>
			</ul>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<input type="hidden" name="action" value="labrisa_core_import_events" />
				<input type="file" name="labrisa_core_csv" accept=".csv,text/csv" required />
				<?php submit_button( __( 'Upload and Import', 'labrisa-core' ), 'primary', 'submit', false ); ?>
			</form>

			<p style="margin-top:16px;">
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=labrisa_core_events_csv_sample' ), self::NONCE_ACTION ) ); ?>">
					<?php esc_html_e( 'Download a sample CSV', 'labrisa-core' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Stream every event as a CSV download.
	 * Hooked to admin_post_labrisa_core_export_events.
	 *
	 * @since    1.0.0
	 */
	public function handle_export() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'labrisa-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION );

		$posts = get_posts(
			array(
				'post_type'      => Labrisa_Core_Events::POST_TYPE,
				'post_status'    => 'any',
				'numberposts'    => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="events-export-' . gmdate( 'Y-m-d' ) . '.csv"' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, self::CSV_COLUMNS );

		foreach ( $posts as $post ) {
			fputcsv( $output, $this->post_to_row( $post ) );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Send a one-row sample CSV so users know the expected format.
	 * Hooked to admin_post_labrisa_core_events_csv_sample.
	 *
	 * @since    1.0.0
	 */
	public function handle_sample() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'labrisa-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="events-sample.csv"' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, self::CSV_COLUMNS );
		fputcsv(
			$output,
			array(
				'',
				'La Brisa Presents Fantasia 2',
				'An evening of music, light, and atmosphere.',
				'publish',
				'Party',
				'DJ One|DJ Two',
				'La Brisa',
				'2026-08-08 21:00:00',
				'2026-08-08 23:59:00',
				'https://example.com/banner.jpg',
				'https://example.com/square.jpg',
				'https://tickets.example.com/fantasia-2',
				'<p>21+ event. Doors 9PM.</p>',
			)
		);
		fclose( $output );
		exit;
	}

	/**
	 * Read an uploaded CSV and create/update events from it.
	 * Hooked to admin_post_labrisa_core_import_events.
	 *
	 * @since    1.0.0
	 */
	public function handle_import() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'labrisa-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION );

		$redirect = admin_url( 'edit.php?post_type=' . Labrisa_Core_Events::POST_TYPE . '&page=labrisa-core-events-csv' );

		if ( empty( $_FILES['labrisa_core_csv']['tmp_name'] ) || UPLOAD_ERR_OK !== $_FILES['labrisa_core_csv']['error'] ) {
			$this->store_result_and_redirect(
				$redirect,
				array(
					'created' => 0,
					'updated' => 0,
					'errors'  => array( __( 'No file was uploaded, or the upload failed.', 'labrisa-core' ) ),
				)
			);
		}

		$tmp_name = $_FILES['labrisa_core_csv']['tmp_name'];
		$filetype = wp_check_filetype( sanitize_file_name( wp_unslash( $_FILES['labrisa_core_csv']['name'] ) ) );

		if ( ! is_uploaded_file( $tmp_name ) || 'csv' !== $filetype['ext'] ) {
			$this->store_result_and_redirect(
				$redirect,
				array(
					'created' => 0,
					'updated' => 0,
					'errors'  => array( __( 'Please upload a valid .csv file.', 'labrisa-core' ) ),
				)
			);
		}

		$handle = fopen( $tmp_name, 'r' );

		if ( ! $handle ) {
			$this->store_result_and_redirect(
				$redirect,
				array(
					'created' => 0,
					'updated' => 0,
					'errors'  => array( __( 'Could not read the uploaded file.', 'labrisa-core' ) ),
				)
			);
		}

		$header = fgetcsv( $handle );

		if ( ! $header ) {
			fclose( $handle );
			$this->store_result_and_redirect(
				$redirect,
				array(
					'created' => 0,
					'updated' => 0,
					'errors'  => array( __( 'The CSV file appears to be empty.', 'labrisa-core' ) ),
				)
			);
		}

		$header  = array_map( 'trim', $header );
		$created = 0;
		$updated = 0;
		$errors  = array();
		$row_num = 1;

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			++$row_num;

			if ( 1 === count( $row ) && null === $row[0] ) {
				continue; // Skip blank lines.
			}

			// Pad/truncate so column count always matches the header row,
			// regardless of trailing empty columns some spreadsheet
			// exporters omit or extra stray columns in a malformed row.
			$row  = array_slice( array_pad( $row, count( $header ), '' ), 0, count( $header ) );
			$data = array_combine( $header, $row );

			$is_update = ! empty( $data['ID'] ) && get_post( absint( $data['ID'] ) );

			$post_id = $this->import_row( $data, $is_update ? absint( $data['ID'] ) : 0 );

			if ( is_wp_error( $post_id ) ) {
				$errors[] = sprintf(
					/* translators: 1: CSV row number, 2: error message */
					__( 'Row %1$d: %2$s', 'labrisa-core' ),
					$row_num,
					$post_id->get_error_message()
				);
				continue;
			}

			if ( $is_update ) {
				++$updated;
			} else {
				++$created;
			}
		}

		fclose( $handle );

		$this->store_result_and_redirect(
			$redirect,
			array(
				'created' => $created,
				'updated' => $updated,
				'errors'  => $errors,
			)
		);
	}

	/**
	 * Create or update a single event post + its ACF meta/taxonomies/images
	 * from one parsed CSV row.
	 *
	 * @since    1.0.0
	 * @param    array $data     CSV row keyed by column name.
	 * @param    int   $post_id  Existing post ID to update, or 0 to insert new.
	 * @return   int|WP_Error    The event post ID, or a WP_Error on failure.
	 */
	private function import_row( array $data, $post_id ) {
		$postarr = array(
			'post_type'    => Labrisa_Core_Events::POST_TYPE,
			'post_title'   => isset( $data['post_title'] ) ? sanitize_text_field( $data['post_title'] ) : '',
			'post_content' => isset( $data['post_content'] ) ? wp_kses_post( $data['post_content'] ) : '',
			'post_status'  => ! empty( $data['post_status'] ) ? sanitize_key( $data['post_status'] ) : 'publish',
		);

		if ( $post_id ) {
			$postarr['ID'] = $post_id;
			$result        = wp_update_post( $postarr, true );
		} else {
			$result = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$post_id = $result;

		if ( ! empty( $data['event_type'] ) ) {
			wp_set_object_terms( $post_id, sanitize_text_field( $data['event_type'] ), Labrisa_Core_Events::TAX_EVENT_TYPES, false );
		}

		if ( ! empty( $data['event_line_up'] ) ) {
			$terms = array_filter( array_map( 'trim', explode( '|', $data['event_line_up'] ) ) );
			wp_set_object_terms( $post_id, array_map( 'sanitize_text_field', $terms ), Labrisa_Core_Events::TAX_EVENT_LINE_UP, false );
		}

		update_post_meta( $post_id, 'event_place', isset( $data['event_place'] ) ? sanitize_text_field( $data['event_place'] ) : '' );
		update_post_meta( $post_id, 'event_date', isset( $data['event_date'] ) ? sanitize_text_field( $data['event_date'] ) : '' );
		update_post_meta( $post_id, 'event_end_date', isset( $data['event_end_date'] ) ? sanitize_text_field( $data['event_end_date'] ) : '' );
		update_post_meta( $post_id, 'event_ticket_url', isset( $data['event_ticket_url'] ) ? esc_url_raw( $data['event_ticket_url'] ) : '' );
		update_post_meta( $post_id, 'event_terms_and_conditions', isset( $data['event_terms_and_conditions'] ) ? wp_kses_post( $data['event_terms_and_conditions'] ) : '' );

		if ( ! empty( $data['event_banner_image'] ) ) {
			$this->set_image_meta( $post_id, 'event_banner_image', $data['event_banner_image'] );
		}

		if ( ! empty( $data['event_past_image_square'] ) ) {
			$this->set_image_meta( $post_id, 'event_past_image_square', $data['event_past_image_square'] );
		}

		return $post_id;
	}

	/**
	 * Resolve a CSV image value (URL or existing attachment ID) to an
	 * attachment ID and store it on the given ACF image field meta key.
	 * URLs are sideloaded into the media library; failures are silently
	 * skipped (the rest of the row still imports) rather than failing the
	 * whole row over one bad image.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id   Event post ID.
	 * @param    string $meta_key  ACF image field name.
	 * @param    string $value     URL or numeric attachment ID from the CSV.
	 */
	private function set_image_meta( $post_id, $meta_key, $value ) {
		$value = trim( $value );

		if ( is_numeric( $value ) ) {
			update_post_meta( $post_id, $meta_key, absint( $value ) );
			return;
		}

		if ( ! preg_match( '#^https?://#i', $value ) ) {
			return;
		}

		$attachment_id = media_sideload_image( esc_url_raw( $value ), $post_id, null, 'id' );

		if ( ! is_wp_error( $attachment_id ) ) {
			update_post_meta( $post_id, $meta_key, absint( $attachment_id ) );
		}
	}

	/**
	 * Build one CSV row (matching self::CSV_COLUMNS) for a given event post.
	 *
	 * @since    1.0.0
	 * @param    WP_Post $post
	 * @return   array
	 */
	private function post_to_row( $post ) {
		$meta = Labrisa_Core_Events::get_event_meta( $post->ID );

		$event_types = wp_get_post_terms( $post->ID, Labrisa_Core_Events::TAX_EVENT_TYPES, array( 'fields' => 'names' ) );
		$line_up     = wp_get_post_terms( $post->ID, Labrisa_Core_Events::TAX_EVENT_LINE_UP, array( 'fields' => 'names' ) );

		$banner_id = Labrisa_Core_Events::get_event_image_id( $post->ID, 'event_banner_image' );
		$square_id = Labrisa_Core_Events::get_event_image_id( $post->ID, 'event_past_image_square' );

		return array(
			$post->ID,
			$post->post_title,
			$post->post_content,
			$post->post_status,
			is_wp_error( $event_types ) ? '' : implode( '|', $event_types ),
			is_wp_error( $line_up ) ? '' : implode( '|', $line_up ),
			$meta['event_place'],
			$meta['event_date'],
			$meta['event_end_date'],
			$banner_id ? wp_get_attachment_url( $banner_id ) : '',
			$square_id ? wp_get_attachment_url( $square_id ) : '',
			$meta['event_ticket_url'],
			$meta['event_terms_and_conditions'],
		);
	}

	/**
	 * Persist the import result for the current user (read once on the next
	 * page load, see render_page()) and redirect back to the admin screen —
	 * avoids re-submitting the upload if the user refreshes.
	 *
	 * @since    1.0.0
	 * @param    string $redirect
	 * @param    array  $result
	 */
	private function store_result_and_redirect( $redirect, array $result ) {
		set_transient( 'labrisa_core_events_csv_import_result_' . get_current_user_id(), $result, MINUTE_IN_SECONDS * 5 );
		wp_safe_redirect( $redirect );
		exit;
	}
}
