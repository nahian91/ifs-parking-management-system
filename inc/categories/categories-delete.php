<?php
/**
 * Category & Department Delete Handlers.
 *
 * @package IFS_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * একক ক্যাটাগরি ডিলিট হ্যান্ডলার।
 *
 * @param string $table_cats ক্যাটাগরি টেবিল নাম।
 * @param int    $cat_id     ক্যাটাগরি আইডি।
 * @return bool
 */
function ifs_parking_delete_category( $table_cats, $cat_id ) {
	global $wpdb;

	$cat_id = absint( $cat_id );
	if ( $cat_id <= 0 ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_cats ) ) !== $table_cats ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$deleted = $wpdb->delete(
		$table_cats,
		array( 'id' => $cat_id ),
		array( '%d' )
	);

	if ( false !== $deleted ) {
		echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
				<p class="ifs-notice-text">
					<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
					' . esc_html__( 'ক্যাটাগরি সফলভাবে মুছে ফেলা হয়েছে!', 'ifs-parking' ) . '
				</p>
			  </div>';
		return true;
	}

	echo '<div class="notice notice-error is-dismissible">
			<p>' . esc_html__( 'ক্যাটাগরিটি মুছে ফেলা সম্ভব হয়নি। আবার চেষ্টা করুন।', 'ifs-parking' ) . '</p>
		  </div>';
	return false;
}

/**
 * সম্পূর্ণ ডিপার্টমেন্ট ও এর অন্তর্ভুক্ত সকল প্যাকেজ ডিলিট হ্যান্ডলার।
 *
 * @param string $table_cats ক্যাটাগরি টেবিল নাম।
 * @param string $dept_name  ডিপার্টমেন্ট নাম।
 * @return bool
 */
function ifs_parking_delete_department( $table_cats, $dept_name ) {
	global $wpdb;

	$dept_name = sanitize_text_field( $dept_name );
	if ( empty( $dept_name ) ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_cats ) ) !== $table_cats ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$deleted = $wpdb->delete(
		$table_cats,
		array( 'department' => $dept_name ),
		array( '%s' )
	);

	if ( false !== $deleted ) {
		echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
				<p class="ifs-notice-text">
					<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
					' . esc_html(
						sprintf(
							/* translators: %s: ডিপার্টমেন্টের নাম */
							__( 'ডিপার্টমেন্ট (%s) এবং এর সকল প্যাকেজ সফলভাবে মুছে ফেলা হয়েছে!', 'ifs-parking' ),
							$dept_name
						)
					) . '
				</p>
			  </div>';
		return true;
	}

	echo '<div class="notice notice-error is-dismissible">
			<p>' . esc_html__( 'ডিপার্টমেন্টটি মুছে ফেলা সম্ভব হয়নি। আবার চেষ্টা করুন।', 'ifs-parking' ) . '</p>
		  </div>';
	return false;
}