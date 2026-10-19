<?php
/**
 * Vehicle Checkout & Delete Handler.
 *
 * @package Ifs_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * গাড়ি চেক-আউট ও রিলিজ সম্পন্ন করার ফাংশন
 *
 * @param string $table_logs লগ টেবিল নাম।
 * @param int    $log_id     লগ আইডি।
 */
function ifs_parking_checkout_vehicle( $table_logs, $log_id ) {
	global $wpdb;

	$log_item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_logs WHERE id = %d", $log_id ) );
	if ( $log_item && 'Parked' === $log_item->status ) {
		$wpdb->update(
			$table_logs,
			array(
				'exit_time' => current_time( 'mysql' ),
				'status'    => 'Exited',
			),
			array( 'id' => $log_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
				<p class="ifs-notice-text">
					<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
					' . esc_html( sprintf( __( 'গাড়ি (%s) সফলভাবে চেক-আউট সম্পন্ন হয়েছে!', 'ifs-parking' ), $log_item->vehicle_number ) ) . '
				</p>
			  </div>';
	}
}