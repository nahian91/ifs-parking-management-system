<?php
/**
 * All-in-One Fast Vehicle Check-In, Instant Print & Live Recent List Module.
 *
 * @package IFS_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ইংরেজি সংখ্যাকে বাংলা সংখ্যায় রূপান্তর করার হেল্পার ফাংশন।
 *
 * @param string|int|float $number ইনপুট সংখ্যা বা স্ট্রিং।
 * @return string
 */
function ifs_parking_veh_bn_num( $number ) {
	if ( function_exists( 'ifs_parking_to_bn_number' ) ) {
		return ifs_parking_to_bn_number( $number );
	}

	$en_digits = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	$bn_digits = array( '০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯' );

	return str_replace( $en_digits, $bn_digits, (string) $number );
}

/**
 * বাংলাদেশ টাইমজোন (Asia/Dhaka, UTC+6) অবজেক্ট রিটার্ন।
 *
 * @return DateTimeZone
 */
function ifs_parking_bd_timezone() {
	return new DateTimeZone( 'Asia/Dhaka' );
}

/**
 * MySQL ডেটটাইম স্ট্রিং থেকে বাংলাদেশ টাইমজোন অনুযায়ী Timestamp বের করার হেল্পার।
 *
 * @param string $mysql_datetime MySQL তারিখ ও সময় (Y-m-d H:i:s)।
 * @return int
 */
function ifs_parking_parse_local_timestamp( $mysql_datetime ) {
	if ( empty( $mysql_datetime ) || '0000-00-00 00:00:00' === $mysql_datetime ) {
		return 0;
	}

	try {
		$dt = new DateTimeImmutable( $mysql_datetime, ifs_parking_bd_timezone() );
		return $dt->getTimestamp();
	} catch ( Exception $e ) {
		$ts = strtotime( $mysql_datetime );
		return $ts ? $ts : 0;
	}
}

/**
 * MySQL ডেটটাইম স্ট্রিংকে বাংলাদেশ সময়ে (Asia/Dhaka) বাংলায় ফরম্যাট করার হেল্পার।
 *
 * @param string $mysql_datetime MySQL তারিখ ও সময়।
 * @param string $format         তারিখ ফরম্যাট।
 * @return string
 */
function ifs_parking_format_local_datetime( $mysql_datetime, $format = 'h:i A, j M' ) {
	$ts = ifs_parking_parse_local_timestamp( $mysql_datetime );
	if ( ! $ts ) {
		return (string) $mysql_datetime;
	}

	return ifs_parking_veh_bn_num( wp_date( $format, $ts, ifs_parking_bd_timezone() ) );
}

/**
 * গাড়ির ধরণ অনুযায়ী পরিচ্ছন্ন SVG আইকন রিটার্ন।
 *
 * @param string $type গাড়ির ধরণ।
 * @return string
 */
function ifs_parking_get_quick_veh_svg( $type ) {
	$t = strtolower( trim( (string) $type ) );
	if ( false !== strpos( $t, 'bike' ) || false !== strpos( $t, 'cycle' ) ) {
		return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 20.5c-2.48 0-4.5-2.02-4.5-4.5S2.52 11.5 5 11.5c2.09 0 3.84 1.43 4.34 3.38l2.79-1.86-1.5-3.02H9V8h3.33l1.5 3.02 2.76-1.84c-.37-.62-.59-1.34-.59-2.18 0-2.21 1.79-4 4-4s4 1.79 4 4-1.79 4-4 4c-.75 0-1.45-.21-2.05-.57l-2.47 1.65 1.83 3.66c.22-.04.45-.06.69-.06 2.48 0 4.5 2.02 4.5 4.5s-2.02 4.5-4.5 4.5-4.5-2.02-4.5-4.5c0-.98.32-1.89.86-2.63L11.7 18.5H8.77c-.45 1.18-1.57 2-2.87 2l-.9-.01z"/></svg>';
	}
	if ( false !== strpos( $t, 'noah' ) || false !== strpos( $t, 'hiace' ) || false !== strpos( $t, 'van' ) ) {
		return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm12 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm1.5-7.5H16V9h2.2l1.3 2z"/></svg>';
	}
	if ( false !== strpos( $t, 'truck' ) ) {
		return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 1c-2.76 0-5 2.24-5 5v1H4c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h1c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h1c1.1 0 2-.9 2-2v-5.5L20.5 7H18V6c0-1.66 1.34-3 3-3s3 1.34 3 3v1h-2v2h3c.55 0 1-.45 1-1V6c0-2.76-2.24-5-5-5h-3z"/></svg>';
	}
	if ( false !== strpos( $t, 'cng' ) ) {
		return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 15V8c0-1.1-.9-2-2-2h-3l-2-3H9L7 6H4c-1.1 0-2 .9-2 2v7c0 .55.45 1 1 1h1c0 1.66 1.34 3 3 3s3-1.34 3-3h4c0 1.66 1.34 3 3 3s3-1.34 3-3h1c.55 0 1-.45 1-1z"/></svg>';
	}
	return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.85 7h10.29l1.04 3H5.81l1.04-3zM19 17H5v-4.66l.12-.34h13.76l.12.34V17z"/><circle cx="7.5" cy="14.5" r="1.5"/><circle cx="16.5" cy="14.5" r="1.5"/></svg>';
}

/**
 * গাড়ির ধরণ ও ক্যাটাগরি আলাদাভাবে বের করার হেল্পার।
 *
 * @param string $parking_type  লগের parking_type / category_slug।
 * @param array  $cats_by_slug  ক্যাটাগরি অবজেক্ট ম্যাপ।
 * @param array  $veh_labels_bd গাড়ির ধরণের বাংলা লেবেল।
 * @return array {
 *     @type string $veh_type_label গাড়ির ধরণ।
 *     @type string $category_label ক্যাটাগরি / প্যাকেজ।
 * }
 */
function ifs_parking_split_veh_and_category( $parking_type, $cats_by_slug, $veh_labels_bd ) {
	$slug_key    = strtolower( trim( (string) $parking_type ) );
	$matched_cat = isset( $cats_by_slug[ $slug_key ] ) ? $cats_by_slug[ $slug_key ] : null;

	$raw_veh = $matched_cat && ! empty( $matched_cat->vehicle_type ) ? $matched_cat->vehicle_type : '';
	if ( '' === $raw_veh && '' !== $slug_key ) {
		$parts   = explode( '-', $slug_key );
		$raw_veh = ucfirst( trim( $parts[0] ) );
	}

	$veh_type_label = __( 'সাধারণ', 'ifs-parking' );
	if ( '' !== $raw_veh ) {
		if ( isset( $veh_labels_bd[ $raw_veh ] ) ) {
			$veh_type_label = $veh_labels_bd[ $raw_veh ];
		} else {
			foreach ( $veh_labels_bd as $k => $lbl ) {
				if ( strtolower( $k ) === strtolower( $raw_veh ) ) {
					$veh_type_label = $lbl;
					break;
				}
			}
			if ( __( 'সাধারণ', 'ifs-parking' ) === $veh_type_label ) {
				$veh_type_label = ucfirst( $raw_veh );
			}
		}
	}

	$category_label = __( 'General', 'ifs-parking' );
	if ( $matched_cat ) {
		if ( ! empty( $matched_cat->customer_type ) ) {
			$category_label = $matched_cat->customer_type;
		} elseif ( ! empty( $matched_cat->category_name ) ) {
			$category_label = $matched_cat->category_name;
		}
	} elseif ( '' !== $slug_key ) {
		$parts = explode( '-', $slug_key );
		if ( count( $parts ) > 1 ) {
			array_shift( $parts );
			$category_label = ucwords( implode( ' ', $parts ) );
		} else {
			$category_label = ucwords( str_replace( '-', ' ', $slug_key ) );
		}
	}

	return array(
		'veh_type_label' => $veh_type_label,
		'category_label' => $category_label,
	);
}

/**
 * নতুন গাড়ি চেক-ইন ডাটা সেভ হ্যান্ডলার (একই পেজে রেখে ইনস্ট্যান্ট প্রিন্ট ট্রিগারসহ)।
 *
 * @param string $table_logs লগ টেবিল নাম।
 * @param string $table_cats ক্যাটাগরি টেবিল নাম।
 * @return int নতুন ইনসার্ট হওয়া লগ আইডি (ব্যর্থ হলে ০)।
 */
function ifs_parking_save_vehicle_entry( $table_logs, $table_cats ) {
	static $processed_id = null;
	if ( null !== $processed_id ) {
		return $processed_id;
	}

	$processed_id = 0;

	if (
		! isset( $_POST['ifs_vehicle_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ifs_vehicle_nonce'] ) ), 'ifs_save_vehicle' ) ||
		! current_user_can( 'read' )
	) {
		return 0;
	}

	global $wpdb;

	$div_val     = isset( $_POST['veh_div'] ) ? sanitize_text_field( wp_unslash( $_POST['veh_div'] ) ) : '';
	$brta_letter = isset( $_POST['veh_brta_letter'] ) ? sanitize_text_field( wp_unslash( $_POST['veh_brta_letter'] ) ) : '';
	$num_val     = isset( $_POST['veh_num'] ) ? sanitize_text_field( wp_unslash( $_POST['veh_num'] ) ) : '';

	$cat_id_selected = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
	$billing_type    = isset( $_POST['selected_rate_type'] ) ? sanitize_key( wp_unslash( $_POST['selected_rate_type'] ) ) : 'hourly';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$selected_cat = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_cats}` WHERE id = %d", $cat_id_selected ) );

	if ( ! $selected_cat ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$selected_cat = $wpdb->get_row( "SELECT * FROM `{$table_cats}` ORDER BY id ASC LIMIT 1" );
	}

	$selected_radio_veh = isset( $_POST['quick_veh_radio'] ) ? sanitize_text_field( wp_unslash( $_POST['quick_veh_radio'] ) ) : '';
	$is_cycle           = ( 'Cycle' === $selected_radio_veh ) || ( $selected_cat && 'cycle' === strtolower( (string) $selected_cat->vehicle_type ) );

	if ( $is_cycle ) {
		$div_val        = '';
		$brta_letter    = '';
		$vehicle_number = trim( preg_replace( '/\s+/', ' ', 'সাইকেল ' . $num_val ) );
	} else {
		$vehicle_number = trim( preg_replace( '/\s+/', ' ', $div_val . ' ' . $brta_letter . ' ' . $num_val ) );
	}

	$fee_rate = 0.00;
	if ( $selected_cat ) {
		$two_hr_val = isset( $selected_cat->two_hour_rate ) ? (float) $selected_cat->two_hour_rate : 0.00;
		if ( 'daily' === $billing_type ) {
			$fee_rate = (float) $selected_cat->daily_rate;
		} elseif ( 'monthly' === $billing_type ) {
			$fee_rate = (float) $selected_cat->monthly_rate;
		} else {
			$fee_rate = ( $two_hr_val > 0 ) ? $two_hr_val : (float) $selected_cat->hourly_rate;
		}
	}

	$parking_type = $selected_cat ? $selected_cat->category_slug : 'bike-general';
	$driver_phone = isset( $_POST['driver_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['driver_phone'] ) ) : '';
	$gym_emp_id   = isset( $_POST['gym_employee_id'] ) ? sanitize_text_field( wp_unslash( $_POST['gym_employee_id'] ) ) : '';

	$bd_tz       = ifs_parking_bd_timezone();
	$now_utc     = time();
	$month_name  = strtoupper( wp_date( 'M', $now_utc, $bd_tz ) );
	$day_num     = strtoupper( wp_date( 'd', $now_utc, $bd_tz ) );
	$prefix_base = 'NPMS-' . $month_name . '-' . $day_num . '-';
	$today_date  = wp_date( 'Y-m-d', $now_utc, $bd_tz );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$count_today = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM `{$table_logs}` WHERE entry_time >= %s AND entry_time <= %s",
			$today_date . ' 00:00:00',
			$today_date . ' 23:59:59'
		)
	);

	$sequence_num = str_pad( (string) ( $count_today + 1 ), 4, '0', STR_PAD_LEFT );
	$reference_id = $prefix_base . $sequence_num;

	if ( ! empty( $gym_emp_id ) ) {
		$reference_id .= ' (ID: ' . $gym_emp_id . ')';
	} elseif ( $selected_cat ) {
		$reference_id .= ' [' . $selected_cat->department . ']';
	}

	if ( ! empty( $num_val ) ) {
		$entry_mysql = wp_date( 'Y-m-d H:i:s', $now_utc, $bd_tz );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert(
			$table_logs,
			array(
				'vehicle_number'    => $vehicle_number,
				'parking_type'      => $parking_type,
				'slot_number'       => '',
				'reference_id'      => $reference_id,
				'driver_name'       => '',
				'driver_phone'      => $driver_phone,
				'nid_path'          => '',
				'membership_expiry' => null,
				'entry_time'        => $entry_mysql,
				'fee'               => $fee_rate,
				'status'            => 'Parked',
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s' )
		);

		if ( false !== $inserted ) {
			$processed_id = (int) $wpdb->insert_id;
			echo '<div class="ifs-toast-banner ifs-toast-success" role="status">
					<span class="ifs-toast-icon">&#10003;</span>
					<div>
						<strong>' . esc_html__( 'চেক-ইন সম্পন্ন হয়েছে!', 'ifs-parking' ) . '</strong>
						<span>' . esc_html(
							sprintf(
								/* translators: %s: গাড়ির নম্বর */
								__( 'গাড়ি (%s) এন্ট্রি হয়েছে এবং স্লিপ প্রিন্টের জন্য প্রস্তুত।', 'ifs-parking' ),
								$vehicle_number
							)
						) . '</span>
					</div>
				  </div>';
			return $processed_id;
		}
	} else {
		echo '<div class="ifs-toast-banner ifs-toast-error" role="alert">
				<span class="ifs-toast-icon">!</span>
				<div>
					<strong>' . esc_html__( 'নম্বর প্রয়োজন', 'ifs-parking' ) . '</strong>
					<span>' . esc_html__( 'দয়া করে গাড়ির রেজিস্ট্রেশন নম্বর লিখুন।', 'ifs-parking' ) . '</span>
				</div>
			  </div>';
	}

	return 0;
}

/**
 * ক্যাটাগরি ম্যাচ করে ২ ঘণ্টা ও অতিরিক্ত ঘণ্টা রেট বের করার হেল্পার।
 *
 * @param array  $categories_map ক্যাটাগরি স্লাগ অনুযায়ী ম্যাপ।
 * @param string $parking_type   লগের parking_type।
 * @param float  $base_rate      লগের fee।
 * @param int    $diff_secs      পার্কে থাকার সময় (সেকেন্ড)।
 * @return float
 */
function ifs_parking_calculate_slab_or_extra_fee( $categories_map, $parking_type, $base_rate, $diff_secs ) {
	$cat_lower = strtolower( (string) $parking_type );

	if ( false !== strpos( $cat_lower, 'monthly' ) ) {
		return round( $base_rate, 2 );
	}

	if ( false !== strpos( $cat_lower, 'daily' ) ) {
		return round( max( 1, (int) ceil( $diff_secs / 86400 ) ) * $base_rate, 2 );
	}

	$matched_cat = isset( $categories_map[ $cat_lower ] ) ? $categories_map[ $cat_lower ] : null;
	$two_hr_rate = ( $matched_cat && isset( $matched_cat->two_hour_rate ) && (float) $matched_cat->two_hour_rate > 0 )
		? (float) $matched_cat->two_hour_rate
		: $base_rate;
	$extra_hr_rate = ( $matched_cat && isset( $matched_cat->extra_hour_rate ) )
		? (float) $matched_cat->extra_hour_rate
		: 0.00;

	if ( $diff_secs <= 7200 ) {
		return round( $two_hr_rate, 2 );
	}

	if ( $extra_hr_rate > 0 ) {
		$extra_secs  = max( 0, $diff_secs - 7200 );
		$extra_hours = (int) ceil( $extra_secs / 3600 );
		return round( $two_hr_rate + ( $extra_hours * $extra_hr_rate ), 2 );
	}

	$slabs = (int) ceil( $diff_secs / 7200 );
	return round( $slabs * $two_hr_rate, 2 );
}

/**
 * অল-ইন-ওয়ান মিনিমাল চেক-ইন ফর্ম + ইনস্ট্যান্ট প্রিন্ট + রিসেন্ট লিস্ট রেন্ডারার।
 *
 * @param string $table_cats      ক্যাটাগরি টেবিল নাম।
 * @param int    $log_id          লগ আইডি।
 * @param string $action          অ্যাকশন।
 * @param string $site_title      সাইট টাইটেল।
 * @param string $system_phone    ফোন নম্বর।
 * @param string $currency_symbol কারেন্সি প্রতীক।
 * @return void
 */
function ifs_parking_render_vehicle_form( $table_cats, $log_id, $action, $site_title, $system_phone, $currency_symbol ) {
	global $wpdb;

	$table_logs         = $wpdb->prefix . 'ifs_parking_logs';
	$new_log_id         = 0;
	$just_released_item = null;
	$bd_tz              = ifs_parking_bd_timezone();

	// ক্যাটাগরি ও ডিপার্টমেন্ট লোড।
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$all_categories = $wpdb->get_results( "SELECT * FROM `{$table_cats}` ORDER BY id ASC" );

	$categories_by_slug = array();
	if ( ! empty( $all_categories ) ) {
		foreach ( $all_categories as $c_item ) {
			if ( ! empty( $c_item->category_slug ) ) {
				$slug_lc = strtolower( (string) $c_item->category_slug );
				if ( ! isset( $categories_by_slug[ $slug_lc ] ) || (float) ( $c_item->two_hour_rate ?? 0 ) > 0 || (float) ( $c_item->extra_hour_rate ?? 0 ) > 0 ) {
					$categories_by_slug[ $slug_lc ] = $c_item;
				}
			}
		}
	}

	// ১. নতুন চেক-ইন সেভ (একই পেজে)।
	if ( isset( $_POST['ifs_vehicle_nonce'] ) ) {
		$new_log_id = ifs_parking_save_vehicle_entry( $table_logs, $table_cats );
	}

	// ২. একই পেজ থেকে রিলিজ / চেক-আউট হ্যান্ডলার (২ ঘণ্টা + অতিরিক্ত ঘণ্টা রেট অনুযায়ী অটো-হিসাবসহ)।
	if (
		isset( $_POST['ifs_quick_checkout_nonce'] ) &&
		wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ifs_quick_checkout_nonce'] ) ), 'ifs_quick_checkout' ) &&
		current_user_can( 'read' )
	) {
		$checkout_id = isset( $_POST['checkout_log_id'] ) ? absint( $_POST['checkout_log_id'] ) : 0;
		if ( $checkout_id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_logs}` WHERE id = %d", $checkout_id ) );
			if ( $item && 'Parked' === $item->status ) {
				$entry_ts  = ifs_parking_parse_local_timestamp( $item->entry_time );
				$now_utc   = time();
				$diff_secs = $entry_ts ? max( 0, $now_utc - $entry_ts ) : 0;
				$base_rate = (float) $item->fee;
				$final_fee = ifs_parking_calculate_slab_or_extra_fee( $categories_by_slug, $item->parking_type, $base_rate, $diff_secs );

				$exit_mysql = wp_date( 'Y-m-d H:i:s', $now_utc, $bd_tz );

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table_logs,
					array(
						'exit_time' => $exit_mysql,
						'fee'       => $final_fee,
						'status'    => 'Exited',
					),
					array( 'id' => $checkout_id ),
					array( '%s', '%f', '%s' ),
					array( '%d' )
				);

				$item->exit_time    = $exit_mysql;
				$item->fee          = $final_fee;
				$item->status       = 'Exited';
				$just_released_item = $item;

				$exit_bn_disp = ifs_parking_format_local_datetime( $exit_mysql, 'h:i A, j M' );

				echo '<div class="ifs-toast-banner ifs-toast-info" role="status">
						<span class="ifs-toast-icon">&#10003;</span>
						<div>
							<strong>' . esc_html__( 'গাড়ি রিলিজ সম্পন্ন!', 'ifs-parking' ) . '</strong>
							<span>' . esc_html(
								sprintf(
									/* translators: 1: গাড়ির নম্বর, 2: বের হওয়ার সময়, 3: মোট ফি */
									__( 'গাড়ি (%1$s) চেক-আউট হয়েছে (%2$s)। মোট বিল: %3$s', 'ifs-parking' ),
									$item->vehicle_number,
									$exit_bn_disp,
									$currency_symbol . ifs_parking_veh_bn_num( number_format( $final_fee, 2 ) )
								)
							) . '</span>
						</div>
					  </div>';
			}
		}
	}

	$departments = ! empty( $all_categories ) ? array_unique( wp_list_pluck( $all_categories, 'department' ) ) : array( 'General' );
	if ( in_array( 'General', $departments, true ) ) {
		$departments = array_diff( $departments, array( 'General' ) );
		array_unshift( $departments, 'General' );
	}
	$departments = array_values( $departments );

	// সদ্য চেক-ইন হওয়া গাড়িটির তথ্য।
	$just_checked_in = null;
	if ( $new_log_id > 0 ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$just_checked_in = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_logs}` WHERE id = %d", $new_log_id ) );
	}

	// আজকের সব এন্ট্রি আগে দেখানো হবে, এরপর পূর্ববর্তী এন্ট্রিগুলো প্যাজিনেশনের জন্য লোড হবে।
	$now_utc     = time();
	$today_date  = wp_date( 'Y-m-d', $now_utc, $bd_tz );
	$today_start = $today_date . ' 00:00:00';
	$today_end   = $today_date . ' 23:59:59';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$today_logs = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM `{$table_logs}` WHERE entry_time >= %s AND entry_time <= %s ORDER BY id DESC",
			$today_start,
			$today_end
		)
	);

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$older_logs = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM `{$table_logs}` WHERE entry_time < %s ORDER BY id DESC LIMIT 90",
			$today_start
		)
	);

	$recent_logs = array_merge(
		is_array( $today_logs ) ? $today_logs : array(),
		is_array( $older_logs ) ? $older_logs : array()
	);

	$today_count    = is_array( $today_logs ) ? count( $today_logs ) : 0;
	$active_count   = 0;
	$overdue_count  = 0;
	$released_count = 0;
	if ( ! empty( $recent_logs ) ) {
		foreach ( $recent_logs as $rl ) {
			if ( 'Parked' === $rl->status ) {
				$active_count++;
				$e_ts = ifs_parking_parse_local_timestamp( $rl->entry_time );
				if ( $e_ts && ( $now_utc - $e_ts ) > 7200 ) {
					$overdue_count++;
				}
			} else {
				$released_count++;
			}
		}
	}

	// সিরিয়াল: বাইক -> কার -> সিএনজি -> সাইকেল -> নোয়াহ -> হাইয়েস -> ট্রাক
	$veh_labels_bd = array(
		'Bike'  => __( 'বাইক', 'ifs-parking' ),
		'Car'   => __( 'কার', 'ifs-parking' ),
		'CNG'   => __( 'সিএনজি', 'ifs-parking' ),
		'Cycle' => __( 'সাইকেল', 'ifs-parking' ),
		'Noah'  => __( 'নোয়াহ', 'ifs-parking' ),
		'Hiace' => __( 'হাইয়েস', 'ifs-parking' ),
		'Truck' => __( 'ট্রাক', 'ifs-parking' ),
	);

	$raw_unique_vehs = ! empty( $all_categories ) ? array_unique( wp_list_pluck( $all_categories, 'vehicle_type' ) ) : array_keys( $veh_labels_bd );
	$preferred_order = array_keys( $veh_labels_bd );
	$ordered_vehs    = array();

	foreach ( $preferred_order as $p_veh ) {
		if ( in_array( $p_veh, $raw_unique_vehs, true ) ) {
			$ordered_vehs[] = $p_veh;
		}
	}
	foreach ( $raw_unique_vehs as $r_veh ) {
		if ( ! in_array( $r_veh, $ordered_vehs, true ) ) {
			$ordered_vehs[] = $r_veh;
		}
	}
	if ( empty( $ordered_vehs ) ) {
		$ordered_vehs = $preferred_order;
	}

	// ক্রম: আগে Bike, তারপর Car, তারপর CNG, এরপর Others।
	$brta_letters_bd = array(
		// Bike
		'হ' => __( 'হ (৮০-১২৫ সিসি বাইক)', 'ifs-parking' ),
		'ল' => __( 'ল (১৩৫-২০০ সিসি বাইক)', 'ifs-parking' ),
		// Car
		'ক' => __( 'ক (৮০০ সিসি কার)', 'ifs-parking' ),
		'খ' => __( 'খ (১০০০-১৩০০ কার)', 'ifs-parking' ),
		'গ' => __( 'গ (১৫০০-১৮০০ কার)', 'ifs-parking' ),
		// CNG
		'দ' => __( 'দ (সিএনজি প্রাইভেট)', 'ifs-parking' ),
		'থ' => __( 'থ (সিএনজি ভাড়ায়)', 'ifs-parking' ),
		// Others
		'ঘ' => __( 'ঘ (জীপগাড়ি)', 'ifs-parking' ),
		'চ' => __( 'চ (মাইক্রোবাস/নোয়াহ)', 'ifs-parking' ),
		'ছ' => __( 'ছ (লেগুনা/ভাড়ায়)', 'ifs-parking' ),
		'ট' => __( 'ট (ট্রাক বড়)', 'ifs-parking' ),
		'ঠ' => __( 'ঠ (ডাবল কেবিন)', 'ifs-parking' ),
		'ড' => __( 'ড (ট্রাক মাঝারী)', 'ifs-parking' ),
		'ন' => __( 'ন (পিকআপ ছোট)', 'ifs-parking' ),
		'ম' => __( 'ম (ডেলিভারি ভ্যান)', 'ifs-parking' ),
		''  => __( 'প্রযোজ্য নয়', 'ifs-parking' ),
	);

	$metro_options = array(
		'সিলেট মেট্রো'     => __( 'সিলেট মেট্রো', 'ifs-parking' ),
		'ঢাকা মেট্রো'      => __( 'ঢাকা মেট্রো', 'ifs-parking' ),
		'চট্ট মেট্রো'      => __( 'চট্ট মেট্রো', 'ifs-parking' ),
		'রাজশাহী মেট্রো'   => __( 'রাজশাহী মেট্রো', 'ifs-parking' ),
		'খুলনা মেট্রো'     => __( 'খুলনা মেট্রো', 'ifs-parking' ),
		'বরিশাল মেট্রো'    => __( 'বরিশাল মেট্রো', 'ifs-parking' ),
		'রংপুর মেট্রো'     => __( 'রংপুর মেট্রো', 'ifs-parking' ),
		'ময়মনসিংহ মেট্রো' => __( 'ময়মনসিংহ মেট্রো', 'ifs-parking' ),
		'সিলেট'            => __( 'সিলেট (জেলা)', 'ifs-parking' ),
		'অন্যান্য'         => __( 'অন্যান্য জেলা', 'ifs-parking' ),
		''                 => __( 'প্রযোজ্য নয়', 'ifs-parking' ),
	);

	$allowed_svg = array(
		'svg'    => array(
			'viewbox'     => true,
			'aria-hidden' => true,
		),
		'path'   => array( 'd' => true ),
		'circle' => array(
			'cx' => true,
			'cy' => true,
			'r'  => true,
		),
	);
	?>
	<style>
		/* প্রিমিয়াম মোবাইল-ফার্স্ট অ্যাপ UI/UX ডিজাইন সিস্টেম */
		.ifs-unified-app {
			max-width: 860px;
			margin: 0 auto;
			font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			box-sizing: border-box;
			color: #0f172a;
		}
		.ifs-unified-app * {
			box-sizing: border-box;
		}

		/* টোস্ট নোটিফিকেশন ব্যানার */
		.ifs-toast-banner {
			display: flex;
			align-items: center;
			gap: 12px;
			padding: 12px 16px;
			border-radius: 14px;
			margin-bottom: 14px;
			box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
			animation: ifsSlideDown 0.25s ease;
		}
		.ifs-toast-success {
			background: #ecfdf5;
			border: 1px solid #a7f3d0;
			color: #065f46;
		}
		.ifs-toast-info {
			background: #f0f9ff;
			border: 1px solid #bae6fd;
			color: #0369a1;
		}
		.ifs-toast-error {
			background: #fef2f2;
			border: 1px solid #fecaca;
			color: #991b1b;
		}
		.ifs-toast-icon {
			width: 30px;
			height: 30px;
			border-radius: 50%;
			background: currentColor;
			color: #ffffff;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			font-weight: 800;
			font-size: 14px;
			flex-shrink: 0;
			-webkit-text-fill-color: #ffffff;
		}
		.ifs-toast-banner strong {
			display: block;
			font-size: 14.5px;
			line-height: 1.3;
		}
		.ifs-toast-banner span {
			font-size: 13px;
			opacity: 0.92;
		}
		@keyframes ifsSlideDown {
			from { opacity: 0; transform: translateY(-6px); }
			to { opacity: 1; transform: translateY(0); }
		}

		/* মেইন কার্ড কন্টেইনার */
		.ifs-minimal-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 18px;
			padding: 20px;
			margin-bottom: 16px;
			box-shadow: 0 6px 24px rgba(15, 23, 42, 0.045);
		}
		.ifs-card-top-bar {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 10px;
			margin-bottom: 16px;
			padding-bottom: 12px;
			border-bottom: 1px solid #f1f5f9;
			flex-wrap: wrap;
		}
		.ifs-card-heading {
			margin: 0;
			font-size: 17px;
			font-weight: 700;
			color: #0f172a;
			display: flex;
			align-items: center;
			gap: 8px;
		}
		.ifs-live-plate-badge {
			background: #f8fafc;
			border: 1.5px solid #cbd5e1;
			color: #0f172a;
			padding: 6px 14px;
			border-radius: 10px;
			font-size: 13.5px;
			font-weight: 700;
			letter-spacing: 0.2px;
			transition: border-color 0.2s ease, background-color 0.2s ease;
		}
		.ifs-sec-label {
			font-size: 12.5px;
			font-weight: 700;
			color: #475569;
			margin: 0 0 8px 0;
			display: block;
		}

		/* কুইক গাড়ির বাটন গ্রিড */
		.ifs-quick-veh-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(92px, 1fr));
			gap: 8px;
			margin-bottom: 16px;
		}
		.ifs-qveh-pill {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			gap: 5px;
			padding: 11px 6px;
			border: 1.5px solid #e2e8f0;
			border-radius: 12px;
			background: #f8fafc;
			font-weight: 700;
			font-size: 13.5px;
			color: #334155;
			cursor: pointer;
			transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
			text-align: center;
			user-select: none;
			-webkit-tap-highlight-color: transparent;
		}
		.ifs-qveh-pill svg {
			width: 22px;
			height: 22px;
			fill: #64748b;
			transition: fill 0.18s ease;
		}
		.ifs-qveh-pill input {
			display: none;
		}
		.ifs-qveh-pill:hover {
			border-color: #93c5fd;
			background: #f0f9ff;
		}
		.ifs-qveh-pill:has(input:checked) {
			background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
			border-color: #0284c7;
			color: #ffffff;
			transform: translateY(-1px);
			box-shadow: 0 6px 14px rgba(2, 132, 199, 0.25);
		}
		.ifs-qveh-pill:has(input:checked) svg {
			fill: #ffffff;
		}

		/* সব ইনপুট ও সিলেক্টের সমান উচ্চতা (Equal Height 48px) এবং নিখুঁত অ্যালাইনমেন্ট */
		.ifs-plate-row {
			display: grid;
			grid-template-columns: 1.05fr 1.2fr 1.45fr;
			gap: 10px;
			margin-bottom: 14px;
			align-items: stretch;
		}
		.ifs-meta-row {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 10px;
			margin-bottom: 14px;
			align-items: stretch;
		}
		.ifs-unified-app .ifs-m-input,
		.ifs-unified-app select.ifs-m-input,
		.ifs-unified-app input[type="text"].ifs-m-input,
		.ifs-unified-app input[type="tel"].ifs-m-input {
			width: 100% !important;
			max-width: none !important;
			height: 48px !important;
			min-height: 48px !important;
			max-height: 48px !important;
			line-height: normal !important;
			padding: 0 14px !important;
			margin: 0 !important;
			border: 1.5px solid #cbd5e1 !important;
			border-radius: 12px !important;
			font-family: inherit !important;
			font-size: 15px !important;
			font-weight: 600 !important;
			color: #0f172a !important;
			background-color: #ffffff !important;
			box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
			box-sizing: border-box !important;
			vertical-align: middle !important;
			transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease !important;
		}
		.ifs-unified-app select.ifs-m-input {
			appearance: none !important;
			-webkit-appearance: none !important;
			-moz-appearance: none !important;
			padding-top: 0 !important;
			padding-bottom: 0 !important;
			padding-left: 14px !important;
			padding-right: 40px !important;
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23334155' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
			background-repeat: no-repeat !important;
			background-position: right 13px center !important;
			background-size: 17px 17px !important;
			cursor: pointer !important;
			text-overflow: ellipsis !important;
			white-space: nowrap !important;
		}
		.ifs-unified-app select.ifs-m-input:hover,
		.ifs-unified-app .ifs-m-input:hover {
			border-color: #94a3b8 !important;
		}
		.ifs-unified-app .ifs-m-input:focus,
		.ifs-unified-app select.ifs-m-input:focus {
			border-color: #0284c7 !important;
			outline: none !important;
			background-color: #ffffff !important;
			box-shadow: 0 0 0 3.5px rgba(2, 132, 199, 0.15) !important;
		}
		.ifs-plate-num-input {
			font-weight: 700 !important;
			letter-spacing: 0.4px;
		}

		/* নির্ধারিত ফি স্ট্রিপ ও প্যাকেজ সিলেক্ট */
		.ifs-rate-strip {
			display: flex;
			align-items: center;
			justify-content: space-between;
			background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
			border: 1px solid #bae6fd;
			border-radius: 12px;
			padding: 10px 14px;
			margin-bottom: 14px;
			flex-wrap: wrap;
			gap: 10px;
		}
		.ifs-rate-subhint {
			display: block;
			font-size: 12px;
			color: #0369a1;
			font-weight: 600;
			margin-top: 2px;
		}
		.ifs-unified-app select.ifs-rate-select {
			appearance: none !important;
			-webkit-appearance: none !important;
			-moz-appearance: none !important;
			width: auto !important;
			min-width: 185px !important;
			height: 42px !important;
			min-height: 42px !important;
			max-height: 42px !important;
			line-height: normal !important;
			border-radius: 10px !important;
			border: 1.5px solid #38bdf8 !important;
			background-color: #ffffff !important;
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%230369a1' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
			background-repeat: no-repeat !important;
			background-position: right 11px center !important;
			background-size: 15px 15px !important;
			color: #0369a1 !important;
			padding: 0 36px 0 14px !important;
			margin: 0 !important;
			font-family: inherit !important;
			font-weight: 700 !important;
			font-size: 13.5px !important;
			box-shadow: 0 1px 3px rgba(2, 132, 199, 0.08) !important;
			cursor: pointer !important;
			transition: border-color 0.18s ease, box-shadow 0.18s ease !important;
		}
		.ifs-unified-app select.ifs-rate-select:focus {
			border-color: #0284c7 !important;
			outline: none !important;
			box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.16) !important;
		}

		/* প্রাইমারি চেক-ইন ও প্রিন্ট অ্যাকশন বাটন */
		.ifs-action-btn-grid {
			display: grid;
			grid-template-columns: 1fr;
			gap: 10px;
			align-items: stretch;
		}
		.ifs-action-btn-grid.has-print-btn {
			grid-template-columns: 1.35fr 1fr;
		}
		.ifs-btn-checkin-now {
			width: 100%;
			height: 50px;
			background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
			color: #ffffff;
			border: none;
			border-radius: 12px;
			font-size: 16px;
			font-weight: 700;
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			box-shadow: 0 6px 16px rgba(2, 132, 199, 0.28);
			transition: transform 0.12s ease, box-shadow 0.12s ease;
			-webkit-tap-highlight-color: transparent;
		}
		.ifs-btn-checkin-now:hover {
			box-shadow: 0 8px 20px rgba(2, 132, 199, 0.34);
		}
		.ifs-btn-checkin-now:active {
			transform: scale(0.98);
		}
		.ifs-btn-print-slip-main {
			width: 100%;
			height: 50px;
			background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
			color: #ffffff;
			border: none;
			border-radius: 12px;
			font-size: 14.5px;
			font-weight: 700;
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18);
			transition: transform 0.12s ease, background 0.15s ease;
			-webkit-tap-highlight-color: transparent;
		}
		.ifs-btn-print-slip-main:active {
			transform: scale(0.98);
		}

		/* রিসেন্ট লিস্ট ও ফিল্টার পিলস */
		.ifs-recent-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 10px;
			margin-bottom: 12px;
		}
		.ifs-recent-submeta {
			font-size: 12.5px;
			color: #64748b;
			margin-top: 3px;
		}
		.ifs-status-pills {
			display: flex;
			gap: 6px;
			flex-wrap: wrap;
		}
		.ifs-spill {
			border: 1px solid #e2e8f0;
			padding: 7px 12px;
			border-radius: 99px;
			font-size: 12.5px;
			font-weight: 700;
			cursor: pointer;
			background: #f8fafc;
			color: #475569;
			transition: all 0.15s ease;
			-webkit-tap-highlight-color: transparent;
		}
		.ifs-spill.is-active-all {
			background: #0284c7;
			border-color: #0284c7;
			color: #ffffff;
		}
		.ifs-spill.is-active-parked {
			background: #16a34a;
			border-color: #16a34a;
			color: #ffffff;
		}
		.ifs-spill.is-active-overdue {
			background: #d97706;
			border-color: #d97706;
			color: #ffffff;
		}
		.ifs-spill.is-active-released {
			background: #475569;
			border-color: #475569;
			color: #ffffff;
		}
		.ifs-search-wrap {
			position: relative;
		}
		.ifs-search-wrap svg {
			position: absolute;
			left: 13px;
			top: 50%;
			transform: translateY(-50%);
			width: 18px;
			height: 18px;
			fill: #94a3b8;
			pointer-events: none;
		}
		.ifs-unified-app .ifs-search-wrap input[type="text"].ifs-m-input {
			padding-left: 40px !important;
			background-color: #f8fafc !important;
		}
		.ifs-unified-app .ifs-search-wrap input[type="text"].ifs-m-input:focus {
			background-color: #ffffff !important;
		}

		/* রিসেন্ট কার্ড আইটেম */
		.ifs-recent-card-list {
			display: flex;
			flex-direction: column;
			gap: 10px;
			margin-top: 12px;
		}
		.ifs-recent-item {
			display: flex;
			justify-content: space-between;
			align-items: center;
			gap: 12px;
			padding: 13px 15px;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			background: #ffffff;
			box-shadow: 0 1px 4px rgba(15, 23, 42, 0.03);
			flex-wrap: wrap;
			transition: border-color 0.18s ease, box-shadow 0.18s ease;
		}
		.ifs-recent-item:hover {
			border-color: #cbd5e1;
			box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
		}
		.ifs-recent-item.is-overdue-card {
			border-color: #fcd34d;
			background: #fffbeb;
		}
		.ifs-badge-veh-type {
			font-size: 12px;
			font-weight: 700;
			padding: 2px 9px;
			border-radius: 6px;
			background: #e0f2fe;
			color: #0369a1;
		}
		.ifs-badge-cat-only {
			font-size: 12px;
			font-weight: 700;
			padding: 2px 9px;
			border-radius: 6px;
			background: #f1f5f9;
			color: #334155;
			border: 1px solid #e2e8f0;
		}
		.ifs-today-tag {
			font-size: 11px;
			font-weight: 700;
			padding: 2px 8px;
			border-radius: 99px;
			background: #eff6ff;
			color: #1d4ed8;
		}
		.ifs-recent-actions {
			display: flex;
			align-items: center;
			gap: 8px;
			flex-wrap: wrap;
		}
		.ifs-btn-mini-print {
			height: 40px;
			padding: 0 14px;
			border-radius: 10px;
			border: 1.5px solid #cbd5e1;
			background: #ffffff;
			color: #0f172a;
			font-weight: 700;
			font-size: 13px;
			cursor: pointer;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 5px;
			transition: background 0.15s ease, border-color 0.15s ease;
			-webkit-tap-highlight-color: transparent;
		}
		.ifs-btn-mini-print:hover {
			background: #f8fafc;
			border-color: #94a3b8;
		}
		.ifs-btn-mini-checkout {
			height: 40px;
			padding: 0 15px;
			border-radius: 10px;
			border: none;
			background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
			color: #ffffff;
			font-weight: 700;
			font-size: 13px;
			cursor: pointer;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 5px;
			box-shadow: 0 2px 8px rgba(245, 158, 11, 0.25);
			transition: transform 0.12s ease;
			-webkit-tap-highlight-color: transparent;
		}
		.ifs-btn-mini-checkout:active {
			transform: scale(0.98);
		}

		/* প্যাজিনেশন কন্ট্রোল */
		.ifs-pagination-bar {
			display: none;
			margin-top: 16px;
			padding-top: 14px;
			border-top: 1px solid #f1f5f9;
			align-items: center;
			justify-content: space-between;
			gap: 10px;
			flex-wrap: wrap;
		}
		.ifs-pagination-info {
			font-size: 13px;
			color: #475569;
			font-weight: 600;
		}
		.ifs-pagination-buttons {
			display: flex;
			align-items: center;
			gap: 6px;
			flex-wrap: wrap;
		}
		.ifs-page-btn {
			min-width: 36px;
			height: 36px;
			padding: 0 10px;
			border-radius: 9px;
			border: 1px solid #cbd5e1;
			background: #ffffff;
			color: #334155;
			font-size: 13px;
			font-weight: 700;
			cursor: pointer;
			transition: all 0.15s ease;
		}
		.ifs-page-btn:hover:not(:disabled) {
			border-color: #0284c7;
			color: #0284c7;
		}
		.ifs-page-btn.is-current {
			background: #0284c7;
			border-color: #0284c7;
			color: #ffffff;
		}
		.ifs-page-btn:disabled {
			opacity: 0.45;
			cursor: not-allowed;
		}

		/* সুপার মোবাইল-ফ্রেন্ডলি ব্রেকপয়েন্ট */
		@media screen and (max-width: 782px) {
			.ifs-minimal-card {
				padding: 14px 12px;
				border-radius: 16px;
			}
			.ifs-quick-veh-grid {
				grid-template-columns: repeat(3, 1fr);
				gap: 7px;
			}
			.ifs-plate-row,
			.ifs-meta-row,
			.ifs-action-btn-grid,
			.ifs-action-btn-grid.has-print-btn {
				grid-template-columns: 1fr;
				gap: 10px;
			}
			.ifs-unified-app .ifs-m-input,
			.ifs-unified-app select.ifs-m-input,
			.ifs-unified-app input[type="text"].ifs-m-input,
			.ifs-unified-app input[type="tel"].ifs-m-input {
				font-size: 16px !important;
				height: 50px !important;
				min-height: 50px !important;
				max-height: 50px !important;
				line-height: normal !important;
			}
			.ifs-unified-app select.ifs-rate-select {
				width: 100% !important;
				height: 44px !important;
				min-height: 44px !important;
				max-height: 44px !important;
			}
			.ifs-status-pills {
				width: 100%;
				display: grid;
				grid-template-columns: repeat(2, 1fr);
				gap: 6px;
			}
			.ifs-spill {
				text-align: center;
				padding: 8px 6px;
				font-size: 12px;
			}
			.ifs-recent-actions {
				width: 100%;
			}
			.ifs-btn-mini-print,
			.ifs-btn-mini-checkout {
				flex: 1;
				height: 44px;
			}
			.ifs-pagination-bar {
				flex-direction: column;
				align-items: stretch;
				text-align: center;
			}
			.ifs-pagination-buttons {
				justify-content: center;
			}
		}
	</style>

	<div class="ifs-unified-app">
		<!-- সেকশন ১: মিনিমাল ও দ্রুত এন্ট্রি ফর্ম -->
		<div class="ifs-minimal-card">
			<div class="ifs-card-top-bar">
				<h2 class="ifs-card-heading">
					<svg viewBox="0 0 24 24" style="width:21px;height:21px;fill:#0284c7;" aria-hidden="true"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z"/></svg>
					<?php esc_html_e( 'নতুন গাড়ি চেক-ইন', 'ifs-parking' ); ?>
				</h2>
				<div class="ifs-live-plate-badge" id="ifs-live-plate-preview">
					<?php esc_html_e( 'সিলেট মেট্রো হ', 'ifs-parking' ); ?>
				</div>
			</div>

			<span class="ifs-sec-label"><?php esc_html_e( '১. গাড়ির ধরণ নির্বাচন করুন (অটো বিআরটিএ বর্ণ)', 'ifs-parking' ); ?></span>

			<form method="post" action="" id="ifs-fast-checkin-form">
				<?php wp_nonce_field( 'ifs_save_vehicle', 'ifs_vehicle_nonce' ); ?>
				<input type="hidden" name="category_id" id="ifs-hidden-cat-id" value="">
				<input type="hidden" name="selected_rate_type" id="ifs-hidden-rate-type" value="hourly">

				<!-- কুইক গাড়ির বাটন (সিরিয়াল: বাইক, কার, সিএনজি, সাইকেল, নোয়াহ, হাইয়েস, ট্রাক) -->
				<div class="ifs-quick-veh-grid">
					<?php
					foreach ( $ordered_vehs as $v_idx => $v_item ) :
						$v_lbl = isset( $veh_labels_bd[ $v_item ] ) ? $veh_labels_bd[ $v_item ] : $v_item;
						$v_svg = ifs_parking_get_quick_veh_svg( $v_item );
						?>
						<label class="ifs-qveh-pill">
							<input type="radio" name="quick_veh_radio" class="ifs-quick-veh-selector" value="<?php echo esc_attr( $v_item ); ?>" <?php checked( 0 === $v_idx ); ?>>
							<?php echo wp_kses( $v_svg, $allowed_svg ); ?>
							<span><?php echo esc_html( $v_lbl ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>

				<!-- বিআরটিএ রেজিস্ট্রেশন নম্বর (সরাসরি নাম্বার কিবোর্ড ওপেন হবে) -->
				<span class="ifs-sec-label"><?php esc_html_e( '২. বিআরটিএ (BRTA) গাড়ি নম্বর লিখুন *', 'ifs-parking' ); ?></span>
				<div class="ifs-plate-row">
					<select name="veh_div" id="f-div" class="ifs-m-input">
						<?php foreach ( $metro_options as $m_val => $m_label ) : ?>
							<option value="<?php echo esc_attr( $m_val ); ?>"><?php echo esc_html( $m_label ); ?></option>
						<?php endforeach; ?>
					</select>

					<select name="veh_brta_letter" id="f-brta-letter" class="ifs-m-input">
						<?php foreach ( $brta_letters_bd as $b_key => $b_label ) : ?>
							<option value="<?php echo esc_attr( $b_key ); ?>"><?php echo esc_html( $b_label ); ?></option>
						<?php endforeach; ?>
					</select>

					<input type="tel" inputmode="numeric" pattern="[0-9০-৯\-]*" name="veh_num" id="f-num" class="ifs-m-input ifs-plate-num-input" required autocomplete="off" placeholder="<?php esc_attr_e( '১১-১২৩৪', 'ifs-parking' ); ?>">
				</div>

				<!-- ডিপার্টমেন্ট ও ঐচ্ছিক ফোন নম্বর -->
				<div class="ifs-meta-row">
					<div>
						<span class="ifs-sec-label"><?php esc_html_e( '৩. ডিপার্টমেন্ট / মেম্বারশিপ', 'ifs-parking' ); ?></span>
						<select id="ifs-dept-dropdown" class="ifs-m-input">
							<?php foreach ( $departments as $d_name ) : ?>
								<option value="<?php echo esc_attr( $d_name ); ?>"><?php echo esc_html( $d_name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div>
						<span class="ifs-sec-label"><?php esc_html_e( '৪. মোবাইল নম্বর (ঐচ্ছিক)', 'ifs-parking' ); ?></span>
						<input type="tel" inputmode="tel" name="driver_phone" id="f-phone" class="ifs-m-input" placeholder="<?php esc_attr_e( '০১৭XXXXXXXX', 'ifs-parking' ); ?>">
					</div>
				</div>

				<!-- কন্ডিশনাল আইডি (শুধু Gym বা Employee হলে দেখাবে) -->
				<div id="ifs-conditional-id-box" style="display: none; margin-bottom: 14px;">
					<input type="text" name="gym_employee_id" id="ifs-gym-emp-id-input" class="ifs-m-input" placeholder="<?php esc_attr_e( 'সদস্য / স্টাফ আইডি নম্বর লিখুন *', 'ifs-parking' ); ?>">
				</div>

				<!-- নির্ধারিত ২-ঘণ্টা স্ল্যাব ও অতিরিক্ত ঘণ্টা ফি স্ট্রিপ -->
				<div class="ifs-rate-strip">
					<div>
						<span style="font-size:13px; color:#0369a1; font-weight:600;"><?php esc_html_e( 'নির্ধারিত চার্জ (২ ঘণ্টা):', 'ifs-parking' ); ?></span>
						<strong id="ifs-live-rate-display" style="font-size:17px; color:#0f172a; margin-left:6px;">-</strong>
						<span class="ifs-rate-subhint" id="ifs-live-extra-rate-display"></span>
					</div>
					<select id="ifs-pkg-type-select" class="ifs-rate-select">
						<option value="hourly"><?php esc_html_e( '২ ঘণ্টা + অতিরিক্ত ঘণ্টা', 'ifs-parking' ); ?></option>
						<option value="daily"><?php esc_html_e( 'দৈনিক প্যাকেজ', 'ifs-parking' ); ?></option>
						<option value="monthly"><?php esc_html_e( 'মাসিক প্যাকেজ', 'ifs-parking' ); ?></option>
					</select>
				</div>

				<?php $show_print_after_checkin = ( null !== $just_checked_in ); ?>
				<div class="ifs-action-btn-grid <?php echo $show_print_after_checkin ? 'has-print-btn' : ''; ?>">
					<button type="submit" name="submit_vehicle" id="ifs-checkin-submit-btn" class="ifs-btn-checkin-now">
						<svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:currentColor;"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
						<?php esc_html_e( 'চেক-ইন সম্পন্ন করুন', 'ifs-parking' ); ?>
					</button>

					<?php if ( $show_print_after_checkin ) : ?>
						<?php
						$j_split = ifs_parking_split_veh_and_category( $just_checked_in->parking_type, $categories_by_slug, $veh_labels_bd );
						$j_time  = ifs_parking_format_local_datetime( $just_checked_in->entry_time, 'j M, Y - h:i A' );
						$j_exit  = ! empty( $just_checked_in->exit_time ) ? ifs_parking_format_local_datetime( $just_checked_in->exit_time, 'j M, Y - h:i A' ) : __( 'এখনও পার্কিংয়ে', 'ifs-parking' );
						$j_inv   = ! empty( $just_checked_in->reference_id ) ? strtok( $just_checked_in->reference_id, ' [' ) : 'NPMS-' . $just_checked_in->id;
						$j_phn   = ifs_parking_veh_bn_num( $just_checked_in->driver_phone );
						$j_fee   = $currency_symbol . ifs_parking_veh_bn_num( number_format( (float) $just_checked_in->fee, 2 ) );
						?>
						<button type="button"
								id="ifs-print-latest-btn"
								class="ifs-btn-print-slip-main"
								data-inv="<?php echo esc_attr( $j_inv ); ?>"
								data-veh="<?php echo esc_attr( $just_checked_in->vehicle_number ); ?>"
								data-veh-type="<?php echo esc_attr( $j_split['veh_type_label'] ); ?>"
								data-cat="<?php echo esc_attr( $j_split['category_label'] ); ?>"
								data-phone="<?php echo esc_attr( $j_phn ); ?>"
								data-time="<?php echo esc_attr( $j_time ); ?>"
								data-exit="<?php echo esc_attr( $j_exit ); ?>"
								data-fee="<?php echo esc_attr( $j_fee ); ?>"
								data-status="<?php esc_attr_e( 'পার্কিংয়ে সক্রিয়', 'ifs-parking' ); ?>">
							<svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor;"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
							<?php esc_html_e( 'স্লিপ প্রিন্ট (১-ক্লিক)', 'ifs-parking' ); ?>
						</button>
					<?php endif; ?>
				</div>
			</form>
		</div>

		<!-- সেকশন ২: একই পেজে লাইভ স্ট্যাটাস, ফিল্টার ও রিসেন্ট এন্ট্রি তালিকা -->
		<div class="ifs-minimal-card">
			<div class="ifs-recent-header">
				<div>
					<h3 style="margin:0; font-size:17px; font-weight:700; color:#0f172a;"><?php esc_html_e( 'সাম্প্রতিক এন্ট্রি ও লাইভ স্ট্যাটাস', 'ifs-parking' ); ?></h3>
					<div class="ifs-recent-submeta">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: আজকের এন্ট্রি সংখ্যা */
								__( 'আজকের এন্ট্রি: %sটি (আগে আজকের সব এন্ট্রি দেখানো হচ্ছে)', 'ifs-parking' ),
								ifs_parking_veh_bn_num( $today_count )
							)
						);
						?>
					</div>
				</div>

				<div class="ifs-status-pills">
					<button type="button" class="ifs-spill is-active-parked" data-filter="parked">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: পার্কিংয়ে থাকা গাড়ির সংখ্যা */
								__( 'পার্কিংয়ে (%s)', 'ifs-parking' ),
								ifs_parking_veh_bn_num( $active_count )
							)
						);
						?>
					</button>
					<button type="button" class="ifs-spill" data-filter="overdue">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: ওভারডিউ গাড়ির সংখ্যা */
								__( 'ওভারডিউ (%s)', 'ifs-parking' ),
								ifs_parking_veh_bn_num( $overdue_count )
							)
						);
						?>
					</button>
					<button type="button" class="ifs-spill" data-filter="released">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: রিলিজ হওয়া গাড়ির সংখ্যা */
								__( 'রিলিজ সম্পন্ন (%s)', 'ifs-parking' ),
								ifs_parking_veh_bn_num( $released_count )
							)
						);
						?>
					</button>
					<button type="button" class="ifs-spill" data-filter="all">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: মোট তালিকা সংখ্যা */
								__( 'সকল (%s)', 'ifs-parking' ),
								ifs_parking_veh_bn_num( count( $recent_logs ) )
							)
						);
						?>
					</button>
				</div>
			</div>

			<!-- ইনস্ট্যান্ট সার্চ বার -->
			<div class="ifs-search-wrap">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
				<input type="text" id="ifs-recent-search" class="ifs-m-input" placeholder="<?php esc_attr_e( 'গাড়ি নম্বর বা ফোন লিখে খুঁজুন...', 'ifs-parking' ); ?>">
			</div>

			<!-- রিসেন্ট এন্ট্রি কার্ড লিস্ট -->
			<div class="ifs-recent-card-list" id="ifs-recent-list-container">
				<?php if ( ! empty( $recent_logs ) ) : ?>
					<?php
					foreach ( $recent_logs as $item ) :
						$entry_ts   = ifs_parking_parse_local_timestamp( $item->entry_time );
						$exit_ts    = ifs_parking_parse_local_timestamp( $item->exit_time );
						$is_today   = ( $entry_ts && wp_date( 'Y-m-d', $entry_ts, $bd_tz ) === $today_date );
						$diff_secs  = $entry_ts ? max( 0, $now_utc - $entry_ts ) : 0;
						$is_parked  = ( 'Parked' === $item->status );
						$is_overdue = ( $is_parked && $diff_secs > 7200 );
						$base_rate  = (float) $item->fee;
						$live_fee   = $is_parked
							? ifs_parking_calculate_slab_or_extra_fee( $categories_by_slug, $item->parking_type, $base_rate, $diff_secs )
							: $base_rate;
						$dept_txt   = ! empty( $item->reference_id ) ? $item->reference_id : 'General';
						$inv_no     = strtok( $dept_txt, ' [' );
						$time_str   = $entry_ts ? ifs_parking_veh_bn_num( wp_date( 'j M, Y - h:i A', $entry_ts, $bd_tz ) ) : $item->entry_time;
						$time_short = $entry_ts ? ifs_parking_veh_bn_num( wp_date( 'h:i A, j M', $entry_ts, $bd_tz ) ) : $item->entry_time;
						$exit_str   = $exit_ts ? ifs_parking_veh_bn_num( wp_date( 'j M, Y - h:i A', $exit_ts, $bd_tz ) ) : __( 'এখনও পার্কিংয়ে', 'ifs-parking' );
						$exit_short = $exit_ts ? ifs_parking_veh_bn_num( wp_date( 'h:i A, j M', $exit_ts, $bd_tz ) ) : __( 'চলমান', 'ifs-parking' );
						$split_info = ifs_parking_split_veh_and_category( $item->parking_type, $categories_by_slug, $veh_labels_bd );
						$st_label   = $is_overdue ? __( 'ওভারডিউ (২+ ঘণ্টা)', 'ifs-parking' ) : ( $is_parked ? __( 'পার্কিংয়ে সক্রিয়', 'ifs-parking' ) : __( 'রিলিজ সম্পন্ন', 'ifs-parking' ) );
						?>
						<div class="ifs-recent-item <?php echo $is_overdue ? 'is-overdue-card' : ''; ?>"
							 data-parked="<?php echo $is_parked ? '1' : '0'; ?>"
							 data-overdue="<?php echo $is_overdue ? '1' : '0'; ?>"
							 data-released="<?php echo ! $is_parked ? '1' : '0'; ?>"
							 data-today="<?php echo $is_today ? '1' : '0'; ?>">
							<div>
								<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
									<strong style="font-size:16px; color:#0f172a;"><?php echo esc_html( $item->vehicle_number ); ?></strong>
									<span class="ifs-badge-veh-type">
										<?php echo esc_html( $split_info['veh_type_label'] ); ?>
									</span>
									<span class="ifs-badge-cat-only">
										<?php echo esc_html( $split_info['category_label'] ); ?>
									</span>
									<?php if ( $is_today ) : ?>
										<span class="ifs-today-tag"><?php esc_html_e( 'আজকের', 'ifs-parking' ); ?></span>
									<?php endif; ?>
									<?php if ( $is_overdue ) : ?>
										<span style="font-size:11px; font-weight:700; padding:2px 8px; border-radius:99px; background:#fef3c7; color:#b45309;">
											<?php esc_html_e( 'ওভারডিউ (২+ ঘণ্টা)', 'ifs-parking' ); ?>
										</span>
									<?php elseif ( ! $is_parked ) : ?>
										<span style="font-size:11px; font-weight:700; padding:2px 8px; border-radius:99px; background:#dcfce7; color:#15803d;">
											<?php esc_html_e( 'রিলিজ সম্পন্ন', 'ifs-parking' ); ?>
										</span>
									<?php endif; ?>
								</div>
								<div style="font-size:13px; color:#475569; margin-top:5px;">
									<?php esc_html_e( 'প্রবেশ:', 'ifs-parking' ); ?> <strong><?php echo esc_html( $time_short ); ?></strong>
									| <?php esc_html_e( 'বের:', 'ifs-parking' ); ?> <strong><?php echo esc_html( $exit_short ); ?></strong>
									<?php if ( ! empty( $item->driver_phone ) ) : ?>
										| <?php esc_html_e( 'ফোন:', 'ifs-parking' ); ?> <strong><?php echo esc_html( ifs_parking_veh_bn_num( $item->driver_phone ) ); ?></strong>
									<?php endif; ?>
									| <?php esc_html_e( 'বিল:', 'ifs-parking' ); ?>
									<strong style="color:#047857; font-size:14px;">
										<?php echo esc_html( $currency_symbol . ifs_parking_veh_bn_num( number_format( $live_fee, 2 ) ) ); ?>
									</strong>
								</div>
							</div>

							<div class="ifs-recent-actions">
								<button type="button" class="ifs-btn-mini-print ifs-trigger-slip-print"
										data-inv="<?php echo esc_attr( $inv_no ); ?>"
										data-veh="<?php echo esc_attr( $item->vehicle_number ); ?>"
										data-veh-type="<?php echo esc_attr( $split_info['veh_type_label'] ); ?>"
										data-cat="<?php echo esc_attr( $split_info['category_label'] ); ?>"
										data-phone="<?php echo esc_attr( ifs_parking_veh_bn_num( $item->driver_phone ) ); ?>"
										data-time="<?php echo esc_attr( $time_str ); ?>"
										data-exit="<?php echo esc_attr( $exit_str ); ?>"
										data-fee="<?php echo esc_attr( $currency_symbol . ifs_parking_veh_bn_num( number_format( $live_fee, 2 ) ) ); ?>"
										data-status="<?php echo esc_attr( $st_label ); ?>">
									<?php esc_html_e( 'প্রিন্ট', 'ifs-parking' ); ?>
								</button>

								<?php if ( $is_parked ) : ?>
									<form method="post" action="" style="margin:0; flex:1;">
										<?php wp_nonce_field( 'ifs_quick_checkout', 'ifs_quick_checkout_nonce' ); ?>
										<input type="hidden" name="checkout_log_id" value="<?php echo esc_attr( $item->id ); ?>">
										<button type="submit" class="ifs-btn-mini-checkout" style="width:100%;"
												onclick="return confirm('<?php echo esc_js( sprintf( __( 'গাড়ি (%1$s) রিলিজ করবেন? মোট বিল: %2$s', 'ifs-parking' ), $item->vehicle_number, $currency_symbol . ifs_parking_veh_bn_num( number_format( $live_fee, 2 ) ) ) ); ?>');">
											<?php esc_html_e( 'রিলিজ (Check-Out)', 'ifs-parking' ); ?>
										</button>
									</form>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p style="text-align:center; color:#64748b; padding:24px 0;"><?php esc_html_e( 'এখনও কোনো গাড়ি এন্ট্রি হয়নি।', 'ifs-parking' ); ?></p>
				<?php endif; ?>
			</div>

			<!-- প্যাজিনেশন বার (আজকের এন্ট্রি শেষ হলে বা মোট এন্ট্রি পেজ সাইজের বেশি হলে দেখাবে) -->
			<div class="ifs-pagination-bar" id="ifs-recent-pagination-bar">
				<div class="ifs-pagination-info" id="ifs-pagination-info"></div>
				<div class="ifs-pagination-buttons" id="ifs-pagination-buttons"></div>
			</div>
		</div>
	</div>

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		var categoriesData = <?php echo wp_json_encode( $all_categories ); ?>;
		var currencySymbol = <?php echo wp_json_encode( $currency_symbol ); ?>;
		var brandTitle     = <?php echo wp_json_encode( $site_title ); ?>;
		var phoneHelp      = <?php echo wp_json_encode( ifs_parking_veh_bn_num( $system_phone ) ); ?>;
		var i18nText       = {
			prev: <?php echo wp_json_encode( __( 'আগে', 'ifs-parking' ) ); ?>,
			next: <?php echo wp_json_encode( __( 'পরে', 'ifs-parking' ) ); ?>,
			pageOf: <?php echo wp_json_encode( __( 'পৃষ্ঠা %1$s / %2$s (মোট %3$sটি)', 'ifs-parking' ) ); ?>,
			activeStatus: <?php echo wp_json_encode( __( 'পার্কিংয়ে সক্রিয়', 'ifs-parking' ) ); ?>,
			stillParked: <?php echo wp_json_encode( __( 'এখনও পার্কিংয়ে', 'ifs-parking' ) ); ?>,
			helpLbl: <?php echo wp_json_encode( __( 'হেল্পলাইন:', 'ifs-parking' ) ); ?>,
			slipBadge: <?php echo wp_json_encode( __( 'পার্কিং এন্ট্রি টোকেন ও বিলিং স্লিপ', 'ifs-parking' ) ); ?>,
			vehLbl: <?php echo wp_json_encode( __( 'গাড়ির রেজিস্ট্রেশন নম্বর', 'ifs-parking' ) ); ?>,
			invLbl: <?php echo wp_json_encode( __( 'টোকেন / স্লিপ নং', 'ifs-parking' ) ); ?>,
			vehTypeLbl: <?php echo wp_json_encode( __( 'গাড়ির ধরণ', 'ifs-parking' ) ); ?>,
			catLbl: <?php echo wp_json_encode( __( 'ক্যাটাগরি', 'ifs-parking' ) ); ?>,
			timeLbl: <?php echo wp_json_encode( __( 'প্রবেশের সময়', 'ifs-parking' ) ); ?>,
			exitLbl: <?php echo wp_json_encode( __( 'বের হওয়ার সময়', 'ifs-parking' ) ); ?>,
			statusLbl: <?php echo wp_json_encode( __( 'বর্তমান অবস্থা', 'ifs-parking' ) ); ?>,
			phoneLbl: <?php echo wp_json_encode( __( 'মোবাইল নম্বর', 'ifs-parking' ) ); ?>,
			feeLbl: <?php echo wp_json_encode( __( 'নির্ধারিত / চলতি ফি', 'ifs-parking' ) ); ?>,
			extraHourPrefix: <?php echo wp_json_encode( __( 'অতিরিক্ত ঘণ্টা: ', 'ifs-parking' ) ); ?>,
			noteTxt: <?php echo wp_json_encode( __( 'বিঃদ্রঃ প্রথম ২ ঘণ্টা ও অতিরিক্ত ঘণ্টা অনুযায়ী পার্কিং চার্জ গণনা করা হয়। গাড়ি বের করার সময় স্লিপটি প্রদর্শন করুন।', 'ifs-parking' ) ); ?>,
			wishTxt: <?php echo wp_json_encode( __( 'নিরাপদ যাত্রা কামনা করছি!', 'ifs-parking' ) ); ?>,
			cyclePlaceholder: <?php echo wp_json_encode( __( 'সাইকেল নম্বর / টোকেন লিখুন', 'ifs-parking' ) ); ?>,
			platePlaceholder: <?php echo wp_json_encode( __( '১১-১২৩৪', 'ifs-parking' ) ); ?>
		};

		// বিআরটিএ অটো সিরিজ বর্ণ ম্যাপিং
		var brtaAutoLetterMap = {
			'Bike': 'হ',
			'Car': 'গ',
			'CNG': 'থ',
			'Cycle': '',
			'Noah': 'চ',
			'Hiace': 'চ',
			'Truck': 'ট'
		};

		var fDiv            = document.getElementById('f-div');
		var fBrtaLetter     = document.getElementById('f-brta-letter');
		var fNum            = document.getElementById('f-num');
		var livePlateBadge  = document.getElementById('ifs-live-plate-preview');
		var deptDropdown    = document.getElementById('ifs-dept-dropdown');
		var pkgTypeSelect   = document.getElementById('ifs-pkg-type-select');
		var hiddenCatId     = document.getElementById('ifs-hidden-cat-id');
		var hiddenRateType  = document.getElementById('ifs-hidden-rate-type');
		var rateDisplay     = document.getElementById('ifs-live-rate-display');
		var extraRateDisp   = document.getElementById('ifs-live-extra-rate-display');
		var condIdBox       = document.getElementById('ifs-conditional-id-box');
		var condIdInput     = document.getElementById('ifs-gym-emp-id-input');

		var bnMap = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
		function toBnDigits(str) {
			return String(str).replace(/[0-9]/g, function(w) { return bnMap[+w]; });
		}

		function getSelectedVeh() {
			var el = document.querySelector('.ifs-quick-veh-selector:checked');
			return el ? el.value : 'Bike';
		}

		function updateLivePlateBadge() {
			if (!livePlateBadge) return;
			var vehType = getSelectedVeh();
			var numTxt  = fNum ? toBnDigits(fNum.value.trim()) : '';

			if (vehType === 'Cycle') {
				livePlateBadge.textContent = numTxt ? ('সাইকেল ' + numTxt) : 'সাইকেল';
				return;
			}

			var divTxt = (fDiv && fDiv.style.display !== 'none') ? fDiv.value : '';
			var letTxt = (fBrtaLetter && fBrtaLetter.style.display !== 'none') ? fBrtaLetter.value : '';
			var full   = [divTxt, letTxt, numTxt].filter(Boolean).join(' ');
			livePlateBadge.textContent = full || '-';
		}

		function syncRateAndCategory() {
			var vehType  = getSelectedVeh();
			var deptName = deptDropdown ? deptDropdown.value : 'General';
			var rateType = pkgTypeSelect ? pkgTypeSelect.value : 'hourly';
			var plateRow = document.querySelector('.ifs-plate-row');

			if (vehType === 'Cycle') {
				if (fDiv) {
					fDiv.style.display = 'none';
					fDiv.value = '';
				}
				if (fBrtaLetter) {
					fBrtaLetter.style.display = 'none';
					fBrtaLetter.value = '';
				}
				if (plateRow) {
					plateRow.style.gridTemplateColumns = '1fr';
				}
				if (fNum) {
					fNum.setAttribute('placeholder', i18nText.cyclePlaceholder);
				}
			} else {
				if (fDiv) {
					fDiv.style.display = '';
					if (!fDiv.value) {
						fDiv.value = 'সিলেট মেট্রো';
					}
				}
				if (fBrtaLetter) {
					fBrtaLetter.style.display = '';
				}
				if (plateRow) {
					plateRow.style.gridTemplateColumns = '';
				}
				if (fNum) {
					fNum.setAttribute('placeholder', i18nText.platePlaceholder);
				}
			}

			if (condIdBox && condIdInput) {
				if (deptName === 'Gym' || deptName === 'Staff' || deptName === 'Employee') {
					condIdBox.style.display = 'block';
					condIdInput.setAttribute('required', 'required');
				} else {
					condIdBox.style.display = 'none';
					condIdInput.removeAttribute('required');
				}
			}

			var deptVehMatches = categoriesData.filter(function(c) {
				return c.department === deptName && c.vehicle_type === vehType;
			});

			var matched = deptVehMatches.find(function(c) {
				return parseFloat(c.two_hour_rate || 0) > 0 || parseFloat(c.extra_hour_rate || 0) > 0 || parseFloat(c.hourly_rate || 0) > 0 || parseFloat(c.daily_rate || 0) > 0 || parseFloat(c.monthly_rate || 0) > 0;
			}) || deptVehMatches[0] || categoriesData.find(function(c) {
				return c.vehicle_type === vehType && (parseFloat(c.two_hour_rate || 0) > 0 || parseFloat(c.extra_hour_rate || 0) > 0 || parseFloat(c.hourly_rate || 0) > 0);
			}) || categoriesData.find(function(c) {
				return c.vehicle_type === vehType;
			}) || categoriesData[0];

			if (matched) {
				hiddenCatId.value    = matched.id;
				hiddenRateType.value = rateType;
				var twoHrVal    = parseFloat(matched.two_hour_rate || 0);
				var extraHrVal  = parseFloat(matched.extra_hour_rate || 0);
				var hourlyVal   = parseFloat(matched.hourly_rate || 0);
				var primaryTwo  = twoHrVal > 0 ? twoHrVal : hourlyVal;
				var amount      = rateType === 'daily'
					? parseFloat(matched.daily_rate || 0)
					: (rateType === 'monthly' ? parseFloat(matched.monthly_rate || 0) : primaryTwo);

				if (rateDisplay) {
					rateDisplay.textContent = currencySymbol + toBnDigits(amount.toFixed(2));
				}
				if (extraRateDisp) {
					if (rateType === 'hourly') {
						extraRateDisp.textContent = i18nText.extraHourPrefix + currencySymbol + toBnDigits(extraHrVal.toFixed(2));
					} else {
						extraRateDisp.textContent = '';
					}
				}
			}
			updateLivePlateBadge();
		}

		document.querySelectorAll('.ifs-quick-veh-selector').forEach(function(radio) {
			radio.addEventListener('change', function() {
				var v = getSelectedVeh();
				if (v !== 'Cycle' && fBrtaLetter && Object.prototype.hasOwnProperty.call(brtaAutoLetterMap, v)) {
					fBrtaLetter.value = brtaAutoLetterMap[v];
				}
				syncRateAndCategory();
			});
		});

		if (deptDropdown) deptDropdown.addEventListener('change', syncRateAndCategory);
		if (pkgTypeSelect) pkgTypeSelect.addEventListener('change', syncRateAndCategory);
		if (fDiv) fDiv.addEventListener('change', updateLivePlateBadge);
		if (fBrtaLetter) fBrtaLetter.addEventListener('change', updateLivePlateBadge);
		if (fNum) fNum.addEventListener('input', updateLivePlateBadge);

		var initVeh = getSelectedVeh();
		if (initVeh !== 'Cycle' && fBrtaLetter && Object.prototype.hasOwnProperty.call(brtaAutoLetterMap, initVeh)) {
			fBrtaLetter.value = brtaAutoLetterMap[initVeh];
		}
		syncRateAndCategory();

		// প্রফেশনাল স্লিপ প্রিন্ট ফাংশন (প্রিন্ট প্রিভিউ ও রিয়েল প্রিন্টার উভয়ের জন্য অপ্টিমাইজড)
		function printSlipData(data) {
			var invText     = data.inv || '-';
			var vehText     = data.veh || '-';
			var vehTypeText = data.vehType || '-';
			var catText     = data.cat || '-';
			var timeText    = data.time || '-';
			var exitText    = data.exit || i18nText.stillParked;
			var statusText  = data.status || i18nText.activeStatus;
			var feeText     = data.fee || '-';
			var phoneText   = data.phone || '';

			var phoneRowHtml = phoneText
				? '<tr><td style="padding:5px 2px;border-bottom:1px dotted #cbd5e1;color:#475569;font-weight:600;">' + i18nText.phoneLbl + '</td><td style="padding:5px 2px;border-bottom:1px dotted #cbd5e1;color:#0f172a;font-weight:700;text-align:right;">' + phoneText + '</td></tr>'
				: '';

			var slipHtml =
				'<!DOCTYPE html><html><head><meta charset="utf-8"><title>' + brandTitle + '</title>' +
				'<style>@page{size:auto;margin:4mm;}body{margin:0 auto;padding:8px 6px;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#000000;background:#ffffff;max-width:300px;font-size:12.5px;line-height:1.45;-webkit-print-color-adjust:exact;print-color-adjust:exact;}</style>' +
				'</head><body>' +
				'<div style="text-align:center;border-bottom:1.5px dashed #000000;padding-bottom:8px;margin-bottom:8px;">' +
				'<div style="font-size:16px;font-weight:800;">' + brandTitle + '</div>' +
				'<div style="font-size:11.5px;color:#1e293b;margin-top:2px;">' + i18nText.helpLbl + ' ' + phoneHelp + '</div>' +
				'<div style="display:inline-block;margin-top:6px;padding:2px 10px;border:1px solid #000000;border-radius:4px;font-size:11px;font-weight:700;">' + i18nText.slipBadge + '</div>' +
				'</div>' +
				'<div style="text-align:center;border:1.5px solid #000000;border-radius:6px;padding:8px 6px;margin-bottom:8px;">' +
				'<div style="font-size:10.5px;font-weight:600;color:#334155;margin-bottom:2px;">' + i18nText.vehLbl + '</div>' +
				'<div style="font-size:16px;font-weight:800;">' + vehText + '</div>' +
				'</div>' +
				'<table style="width:100%;border-collapse:collapse;margin-bottom:8px;"><tbody>' +
				'<tr><td style="padding:4px 2px;border-bottom:1px dotted #94a3b8;font-weight:600;width:42%;">' + i18nText.invLbl + '</td><td style="padding:4px 2px;border-bottom:1px dotted #94a3b8;font-weight:700;text-align:right;">' + invText + '</td></tr>' +
				'<tr><td style="padding:4px 2px;border-bottom:1px dotted #94a3b8;font-weight:600;">' + i18nText.vehTypeLbl + '</td><td style="padding:4px 2px;border-bottom:1px dotted #94a3b8;font-weight:700;text-align:right;">' + vehTypeText + '</td></tr>' +
				'<tr><td style="padding:4px 2px;border-bottom:1px dotted #94a3b8;font-weight:600;">' + i18nText.catLbl + '</td><td style="padding:4px 2px;border-bottom:1px dotted #94a3b8;font-weight:700;text-align:right;">' + catText + '</td></tr>' +
				'<tr><td style="padding:4px 2px;border-bottom:1px dotted #94a3b8;font-weight:600;">' + i18nText.timeLbl + '</td><td style="padding:4px 2px;border-bottom:1px dotted #94a3b8;font-weight:700;text-align:right;">' + timeText + '</td></tr>' +
				'<tr><td style="padding:4px 2px;border-bottom:1px dotted #94a3b8;font-weight:600;">' + i18nText.exitLbl + '</td><td style="padding:4px 2px;border-bottom:1px dotted #94a3b8;font-weight:700;text-align:right;">' + exitText + '</td></tr>' +
				'<tr><td style="padding:4px 2px;border-bottom:1px dotted #94a3b8;font-weight:600;">' + i18nText.statusLbl + '</td><td style="padding:4px 2px;border-bottom:1px dotted #94a3b8;font-weight:700;text-align:right;">' + statusText + '</td></tr>' +
				phoneRowHtml +
				'</tbody></table>' +
				'<div style="border-top:1.5px dashed #000000;border-bottom:1.5px dashed #000000;padding:8px 2px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;">' +
				'<span style="font-size:12px;font-weight:700;">' + i18nText.feeLbl + '</span>' +
				'<strong style="font-size:15.5px;font-weight:800;">' + feeText + '</strong>' +
				'</div>' +
				'<div style="font-size:11px;border-left:3px solid #000000;padding:5px 8px;margin-bottom:8px;">' + i18nText.noteTxt + '</div>' +
				'<div style="text-align:center;font-size:11px;"><strong>' + i18nText.wishTxt + '</strong><br><span>' + brandTitle + '</span></div>' +
				'</body></html>';

			var printWin = window.open('', '_blank', 'width=360,height=600,scrollbars=yes');
			if (printWin && printWin.document) {
				printWin.document.open();
				printWin.document.write(slipHtml);
				printWin.document.close();
				printWin.focus();
				setTimeout(function() {
					printWin.print();
				}, 200);
				return;
			}

			var oldFrame = document.getElementById('ifs-print-sandbox-frame');
			if (oldFrame && oldFrame.parentNode) {
				oldFrame.parentNode.removeChild(oldFrame);
			}

			var iframe = document.createElement('iframe');
			iframe.id = 'ifs-print-sandbox-frame';
			iframe.style.position = 'fixed';
			iframe.style.left = '0';
			iframe.style.top = '0';
			iframe.style.width = '320px';
			iframe.style.height = '480px';
			iframe.style.border = '0';
			iframe.style.zIndex = '-1';
			document.body.appendChild(iframe);

			var frameWin = iframe.contentWindow;
			var doc      = frameWin ? frameWin.document : null;
			if (!doc) {
				window.print();
				return;
			}

			doc.open();
			doc.write(slipHtml);
			doc.close();

			setTimeout(function() {
				frameWin.focus();
				frameWin.print();
			}, 200);
		}
		document.querySelectorAll('.ifs-trigger-slip-print').forEach(function(btn) {
			btn.addEventListener('click', function(e) {
				e.preventDefault();
				printSlipData({
					inv: this.getAttribute('data-inv'),
					veh: this.getAttribute('data-veh'),
					vehType: this.getAttribute('data-veh-type'),
					cat: this.getAttribute('data-cat'),
					phone: this.getAttribute('data-phone'),
					time: this.getAttribute('data-time'),
					exit: this.getAttribute('data-exit'),
					fee: this.getAttribute('data-fee'),
					status: this.getAttribute('data-status')
				});
			});
		});

		var printLatestBtn = document.getElementById('ifs-print-latest-btn');
		if (printLatestBtn) {
			printLatestBtn.addEventListener('click', function(e) {
				e.preventDefault();
				printSlipData({
					inv: this.getAttribute('data-inv'),
					veh: this.getAttribute('data-veh'),
					vehType: this.getAttribute('data-veh-type'),
					cat: this.getAttribute('data-cat'),
					phone: this.getAttribute('data-phone'),
					time: this.getAttribute('data-time'),
					exit: this.getAttribute('data-exit'),
					fee: this.getAttribute('data-fee'),
					status: this.getAttribute('data-status')
				});
			});
		}

		// সদ্য চেক-ইন হয়ে থাকলে একই পেজ থেকেই অটোমেটিক প্রিন্ট ডায়ালগ ওপেন করা
		<?php if ( $just_checked_in ) : ?>
			<?php
			$j_split = ifs_parking_split_veh_and_category( $just_checked_in->parking_type, $categories_by_slug, $veh_labels_bd );
			$j_time  = ifs_parking_format_local_datetime( $just_checked_in->entry_time, 'j M, Y - h:i A' );
			$j_exit  = ! empty( $just_checked_in->exit_time ) ? ifs_parking_format_local_datetime( $just_checked_in->exit_time, 'j M, Y - h:i A' ) : __( 'এখনও পার্কিংয়ে', 'ifs-parking' );
			$j_inv   = ! empty( $just_checked_in->reference_id ) ? strtok( $just_checked_in->reference_id, ' [' ) : 'NPMS-' . $just_checked_in->id;
			?>
			setTimeout(function() {
				printSlipData({
					inv: <?php echo wp_json_encode( $j_inv ); ?>,
					veh: <?php echo wp_json_encode( $just_checked_in->vehicle_number ); ?>,
					vehType: <?php echo wp_json_encode( $j_split['veh_type_label'] ); ?>,
					cat: <?php echo wp_json_encode( $j_split['category_label'] ); ?>,
					phone: <?php echo wp_json_encode( ifs_parking_veh_bn_num( $just_checked_in->driver_phone ) ); ?>,
					time: <?php echo wp_json_encode( $j_time ); ?>,
					exit: <?php echo wp_json_encode( $j_exit ); ?>,
					fee: <?php echo wp_json_encode( $currency_symbol . ifs_parking_veh_bn_num( number_format( (float) $just_checked_in->fee, 2 ) ) ); ?>,
					status: <?php echo wp_json_encode( __( 'পার্কিংয়ে সক্রিয়', 'ifs-parking' ) ); ?>
				});
			}, 300);
		<?php endif; ?>

		// সদ্য রিলিজ (Check-Out) হয়ে থাকলে অটোমেটিক এক্সিট স্লিপ প্রিন্ট ডায়ালগ ওপেন করা
		<?php if ( $just_released_item ) : ?>
			<?php
			$r_split = ifs_parking_split_veh_and_category( $just_released_item->parking_type, $categories_by_slug, $veh_labels_bd );
			$r_time  = ifs_parking_format_local_datetime( $just_released_item->entry_time, 'j M, Y - h:i A' );
			$r_exit  = ifs_parking_format_local_datetime( $just_released_item->exit_time, 'j M, Y - h:i A' );
			$r_inv   = ! empty( $just_released_item->reference_id ) ? strtok( $just_released_item->reference_id, ' [' ) : 'NPMS-' . $just_released_item->id;
			?>
			setTimeout(function() {
				printSlipData({
					inv: <?php echo wp_json_encode( $r_inv ); ?>,
					veh: <?php echo wp_json_encode( $just_released_item->vehicle_number ); ?>,
					vehType: <?php echo wp_json_encode( $r_split['veh_type_label'] ); ?>,
					cat: <?php echo wp_json_encode( $r_split['category_label'] ); ?>,
					phone: <?php echo wp_json_encode( ifs_parking_veh_bn_num( $just_released_item->driver_phone ) ); ?>,
					time: <?php echo wp_json_encode( $r_time ); ?>,
					exit: <?php echo wp_json_encode( $r_exit ); ?>,
					fee: <?php echo wp_json_encode( $currency_symbol . ifs_parking_veh_bn_num( number_format( (float) $just_released_item->fee, 2 ) ) ); ?>,
					status: <?php echo wp_json_encode( __( 'রিলিজ সম্পন্ন', 'ifs-parking' ) ); ?>
				});
			}, 300);
		<?php endif; ?>

		// আজকের সব এন্ট্রি আগে দেখানো + প্যাজিনেশন সিস্টেম
		var currentFilter  = 'parked';
		var currentPage    = 1;
		var pageSize       = 10;
		var searchInput    = document.getElementById('ifs-recent-search');
		var paginationBar  = document.getElementById('ifs-recent-pagination-bar');
		var paginationInfo = document.getElementById('ifs-pagination-info');
		var paginationBtns = document.getElementById('ifs-pagination-buttons');

		function renderPaginationControls(totalItems, totalPages) {
			if (!paginationBar || !paginationInfo || !paginationBtns) return;

			if (totalPages <= 1) {
				paginationBar.style.display = 'none';
				paginationBtns.innerHTML = '';
				return;
			}

			paginationBar.style.display = 'flex';
			paginationInfo.textContent = i18nText.pageOf
				.replace('%1$s', toBnDigits(currentPage))
				.replace('%2$s', toBnDigits(totalPages))
				.replace('%3$s', toBnDigits(totalItems));

			paginationBtns.innerHTML = '';

			var prevBtn = document.createElement('button');
			prevBtn.type = 'button';
			prevBtn.className = 'ifs-page-btn';
			prevBtn.textContent = i18nText.prev;
			prevBtn.disabled = (currentPage <= 1);
			prevBtn.addEventListener('click', function() {
				if (currentPage > 1) {
					currentPage--;
					filterRecentCards(false);
				}
			});
			paginationBtns.appendChild(prevBtn);

			for (var p = 1; p <= totalPages; p++) {
				(function(pageNum) {
					var numBtn = document.createElement('button');
					numBtn.type = 'button';
					numBtn.className = 'ifs-page-btn' + (pageNum === currentPage ? ' is-current' : '');
					numBtn.textContent = toBnDigits(pageNum);
					numBtn.addEventListener('click', function() {
						currentPage = pageNum;
						filterRecentCards(false);
					});
					paginationBtns.appendChild(numBtn);
				})(p);
			}

			var nextBtn = document.createElement('button');
			nextBtn.type = 'button';
			nextBtn.className = 'ifs-page-btn';
			nextBtn.textContent = i18nText.next;
			nextBtn.disabled = (currentPage >= totalPages);
			nextBtn.addEventListener('click', function() {
				if (currentPage < totalPages) {
					currentPage++;
					filterRecentCards(false);
				}
			});
			paginationBtns.appendChild(nextBtn);
		}

		function filterRecentCards(resetPage) {
			if (resetPage) {
				currentPage = 1;
			}

			var q = searchInput ? searchInput.value.toLowerCase().trim() : '';
			var allCards = Array.prototype.slice.call(document.querySelectorAll('.ifs-recent-item'));

			var matchingCards = allCards.filter(function(card) {
				var isParked   = card.getAttribute('data-parked') === '1';
				var isOverdue  = card.getAttribute('data-overdue') === '1';
				var isReleased = card.getAttribute('data-released') === '1';
				var matchText  = (q === '' || card.textContent.toLowerCase().indexOf(q) !== -1);

				var matchTab = true;
				if (currentFilter === 'parked') matchTab = isParked;
				if (currentFilter === 'overdue') matchTab = isOverdue;
				if (currentFilter === 'released') matchTab = isReleased;

				return matchText && matchTab;
			});

			matchingCards.sort(function(a, b) {
				var aToday = a.getAttribute('data-today') === '1' ? 1 : 0;
				var bToday = b.getAttribute('data-today') === '1' ? 1 : 0;
				return bToday - aToday;
			});

			var todayMatchingCount = matchingCards.filter(function(c) {
				return c.getAttribute('data-today') === '1';
			}).length;

			var firstPageLimit = Math.max(pageSize, todayMatchingCount);
			var totalMatching  = matchingCards.length;

			var totalPages = 1;
			if (totalMatching > firstPageLimit) {
				totalPages = 1 + Math.ceil((totalMatching - firstPageLimit) / pageSize);
			}

			if (currentPage > totalPages) {
				currentPage = totalPages;
			}

			var startIdx = 0;
			var endIdx   = firstPageLimit;
			if (currentPage > 1) {
				startIdx = firstPageLimit + ((currentPage - 2) * pageSize);
				endIdx   = startIdx + pageSize;
			}

			allCards.forEach(function(card) {
				card.style.display = 'none';
			});

			matchingCards.forEach(function(card, idx) {
				if (idx >= startIdx && idx < endIdx) {
					card.style.display = 'flex';
				}
			});

			renderPaginationControls(totalMatching, totalPages);
		}

		document.querySelectorAll('.ifs-spill').forEach(function(pill) {
			pill.addEventListener('click', function() {
				currentFilter = this.getAttribute('data-filter') || 'all';
				document.querySelectorAll('.ifs-spill').forEach(function(p) {
					p.classList.remove('is-active-all', 'is-active-parked', 'is-active-overdue', 'is-active-released');
				});
				if (currentFilter === 'parked') this.classList.add('is-active-parked');
				else if (currentFilter === 'overdue') this.classList.add('is-active-overdue');
				else if (currentFilter === 'released') this.classList.add('is-active-released');
				else this.classList.add('is-active-all');

				filterRecentCards(true);
			});
		});

		if (searchInput) {
			searchInput.addEventListener('input', function() {
				filterRecentCards(true);
			});
		}

		filterRecentCards(true);
	});
	</script>
	<?php
}