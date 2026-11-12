<?php
/**
 * Vehicle Checkout & Delete Handler.
 *
 * @package IFS_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * গাড়ি চেক-আউট ও রিলিজ সম্পন্ন করার ফাংশন।
 *
 * @param string $table_logs লগ টেবিল নাম।
 * @param int    $log_id     লগ আইডি।
 * @return void
 */
function ifs_parking_checkout_vehicle( $table_logs, $log_id ) {
	global $wpdb;

	$log_id      = absint( $log_id );
	$table_slots = $wpdb->prefix . 'ifs_parking_slots';

	if ( $log_id <= 0 ) {
		return;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$log_item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_logs}` WHERE id = %d", $log_id ) );

	if ( $log_item && 'Parked' === $log_item->status ) {
		$entry_ts  = strtotime( $log_item->entry_time );
		$exit_ts   = current_time( 'timestamp' );
		$diff_secs = $entry_ts ? max( 0, $exit_ts - $entry_ts ) : 0;
		$hours     = max( 1, (int) ceil( $diff_secs / 3600 ) );
		$rate      = (float) $log_item->fee;
		$final_fee = $rate > 0 ? round( $hours * $rate, 2 ) : 0.00;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$table_logs,
			array(
				'exit_time' => current_time( 'mysql' ),
				'fee'       => $final_fee,
				'status'    => 'Exited',
			),
			array( 'id' => $log_id ),
			array( '%s', '%f', '%s' ),
			array( '%d' )
		);

		if ( false !== $updated ) {
			// সংশ্লিষ্ট পার্কিং স্লট খালি করা।
			if ( ! empty( $log_item->slot_number ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table_slots,
					array( 'is_occupied' => 0 ),
					array( 'slot_name' => $log_item->slot_number ),
					array( '%d' ),
					array( '%s' )
				);
			}

			echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
					<p class="ifs-notice-text">
						<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
						' . esc_html(
							sprintf(
								/* translators: %s: গাড়ির নম্বর */
								__( 'গাড়ি (%s) সফলভাবে চেক-আউট ও রিলিজ সম্পন্ন হয়েছে!', 'ifs-parking' ),
								$log_item->vehicle_number
							)
						) . '
					</p>
				  </div>';
		}
	}
}

/**
 * পার্কিং লগ রেকর্ড স্থায়ীভাবে মুছে ফেলার ফাংশন।
 *
 * @param string $table_logs লগ টেবিল নাম।
 * @param int    $log_id     লগ আইডি।
 * @return void
 */
function ifs_parking_delete_vehicle( $table_logs, $log_id ) {
	global $wpdb;

	$log_id      = absint( $log_id );
	$table_slots = $wpdb->prefix . 'ifs_parking_slots';

	if ( $log_id <= 0 ) {
		return;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$log_item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_logs}` WHERE id = %d", $log_id ) );

	if ( ! $log_item ) {
		return;
	}

	// গাড়িটি পার্ক করা অবস্থায় ডিলিট হলে স্লটটি মুক্ত করা।
	if ( 'Parked' === $log_item->status && ! empty( $log_item->slot_number ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table_slots,
			array( 'is_occupied' => 0 ),
			array( 'slot_name' => $log_item->slot_number ),
			array( '%d' ),
			array( '%s' )
		);
	}

	// সংযুক্ত এনআইডি ফাইল থাকলে আপলোড ডিরেক্টরি থেকে ডিলিট করা।
	if ( ! empty( $log_item->nid_path ) ) {
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['baseurl'] ) && 0 === strpos( $log_item->nid_path, $upload_dir['baseurl'] ) ) {
			$relative_path = substr( $log_item->nid_path, strlen( $upload_dir['baseurl'] ) );
			$file_path     = $upload_dir['basedir'] . $relative_path;
			if ( file_exists( $file_path ) ) {
				wp_delete_file( $file_path );
			}
		}
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$deleted = $wpdb->delete(
		$table_logs,
		array( 'id' => $log_id ),
		array( '%d' )
	);

	if ( false !== $deleted ) {
		echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
				<p class="ifs-notice-text">
					<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
					' . esc_html(
						sprintf(
							/* translators: %s: গাড়ির নম্বর */
							__( 'গাড়ির রেকর্ড (%s) সফলভাবে মুছে ফেলা হয়েছে!', 'ifs-parking' ),
							$log_item->vehicle_number
						)
					) . '
				</p>
			  </div>';
	}
}

/**
 * URL অ্যাকশনের মাধ্যমে ডিলিট রিকোয়েস্ট প্রসেস করার হুক।
 *
 * @return void
 */
function ifs_parking_handle_vehicle_delete_request() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$page   = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	$log_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( 'ifs-parking-vehicles' === $page && 'delete_vehicle' === $action && $log_id > 0 && current_user_can( 'read' ) ) {
		check_admin_referer( 'ifs_delete_vehicle_' . $log_id );

		global $wpdb;
		$table_logs = $wpdb->prefix . 'ifs_parking_logs';
		ifs_parking_delete_vehicle( $table_logs, $log_id );
	}
}
add_action( 'admin_notices', 'ifs_parking_handle_vehicle_delete_request', 5 );