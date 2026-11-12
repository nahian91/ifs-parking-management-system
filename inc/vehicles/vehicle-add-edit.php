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

	$div_val        = isset( $_POST['veh_div'] ) ? sanitize_text_field( wp_unslash( $_POST['veh_div'] ) ) : 'সিলেট মেট্রো';
	$brta_letter    = isset( $_POST['veh_brta_letter'] ) ? sanitize_text_field( wp_unslash( $_POST['veh_brta_letter'] ) ) : '';
	$num_val        = isset( $_POST['veh_num'] ) ? sanitize_text_field( wp_unslash( $_POST['veh_num'] ) ) : '';
	$vehicle_number = trim( preg_replace( '/\s+/', ' ', $div_val . ' ' . $brta_letter . ' ' . $num_val ) );

	$cat_id_selected = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
	$billing_type    = isset( $_POST['selected_rate_type'] ) ? sanitize_key( wp_unslash( $_POST['selected_rate_type'] ) ) : 'hourly';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$selected_cat = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_cats}` WHERE id = %d", $cat_id_selected ) );

	if ( ! $selected_cat ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$selected_cat = $wpdb->get_row( "SELECT * FROM `{$table_cats}` ORDER BY id ASC LIMIT 1" );
	}

	$fee_rate = 0.00;
	if ( $selected_cat ) {
		if ( 'daily' === $billing_type ) {
			$fee_rate = (float) $selected_cat->daily_rate;
		} elseif ( 'monthly' === $billing_type ) {
			$fee_rate = (float) $selected_cat->monthly_rate;
		} else {
			$fee_rate = (float) $selected_cat->hourly_rate;
		}
	}

	$parking_type = $selected_cat ? $selected_cat->category_slug : 'car-general';
	$driver_phone = isset( $_POST['driver_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['driver_phone'] ) ) : '';
	$gym_emp_id   = isset( $_POST['gym_employee_id'] ) ? sanitize_text_field( wp_unslash( $_POST['gym_employee_id'] ) ) : '';

	$now_ts      = current_time( 'timestamp' );
	$month_name  = strtoupper( wp_date( 'M', $now_ts ) );
	$day_num     = strtoupper( wp_date( 'd', $now_ts ) );
	$prefix_base = 'NPMS-' . $month_name . '-' . $day_num . '-';
	$today_date  = current_time( 'Y-m-d' );

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
				'entry_time'        => current_time( 'mysql' ),
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

	$table_logs = $wpdb->prefix . 'ifs_parking_logs';
	$new_log_id = 0;

	// ১. নতুন চেক-ইন সেভ (একই পেজে)।
	if ( isset( $_POST['ifs_vehicle_nonce'] ) ) {
		$new_log_id = ifs_parking_save_vehicle_entry( $table_logs, $table_cats );
	}

	// ২. একই পেজ থেকে রিলিজ / চেক-আউট হ্যান্ডলার (প্রতি ২ ঘণ্টা স্ল্যাব অটো-হিসাবসহ)।
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
				$entry_ts  = strtotime( $item->entry_time );
				$now_ts    = current_time( 'timestamp' );
				$diff_secs = $entry_ts ? max( 0, $now_ts - $entry_ts ) : 0;
				$base_rate = (float) $item->fee;
				$cat_lower = strtolower( (string) $item->parking_type );

				if ( false !== strpos( $cat_lower, 'monthly' ) ) {
					$final_fee = $base_rate;
				} elseif ( false !== strpos( $cat_lower, 'daily' ) ) {
					$final_fee = max( 1, (int) ceil( $diff_secs / 86400 ) ) * $base_rate;
				} else {
					// প্রতি ২ ঘণ্টা (৭২০০ সেকেন্ড) স্ল্যাব অনুযায়ী চার্জ।
					$slabs     = ( $diff_secs <= 7200 ) ? 1 : (int) ceil( $diff_secs / 7200 );
					$final_fee = round( $slabs * $base_rate, 2 );
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table_logs,
					array(
						'exit_time' => current_time( 'mysql' ),
						'fee'       => $final_fee,
						'status'    => 'Exited',
					),
					array( 'id' => $checkout_id ),
					array( '%s', '%f', '%s' ),
					array( '%d' )
				);

				echo '<div class="ifs-toast-banner ifs-toast-info" role="status">
						<span class="ifs-toast-icon">&#10003;</span>
						<div>
							<strong>' . esc_html__( 'গাড়ি রিলিজ সম্পন্ন!', 'ifs-parking' ) . '</strong>
							<span>' . esc_html(
								sprintf(
									/* translators: 1: গাড়ির নম্বর, 2: মোট ফি */
									__( 'গাড়ি (%1$s) চেক-আউট হয়েছে। মোট বিল: %2$s', 'ifs-parking' ),
									$item->vehicle_number,
									$currency_symbol . ifs_parking_veh_bn_num( number_format( $final_fee, 2 ) )
								)
							) . '</span>
						</div>
					  </div>';
			}
		}
	}

	// ক্যাটাগরি ও ডিপার্টমেন্ট লোড।
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$all_categories = $wpdb->get_results( "SELECT * FROM `{$table_cats}` ORDER BY id ASC" );
	$departments    = ! empty( $all_categories ) ? array_unique( wp_list_pluck( $all_categories, 'department' ) ) : array( 'General' );
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

	// সাম্প্রতিক গাড়ির তালিকা (Recent Entries - সর্বশেষ ৩০টি)।
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$recent_logs = $wpdb->get_results( "SELECT * FROM `{$table_logs}` ORDER BY id DESC LIMIT 30" );

	$now_ts        = current_time( 'timestamp' );
	$active_count  = 0;
	$overdue_count = 0;
	if ( ! empty( $recent_logs ) ) {
		foreach ( $recent_logs as $rl ) {
			if ( 'Parked' === $rl->status ) {
				$active_count++;
				$e_ts = strtotime( $rl->entry_time );
				if ( $e_ts && ( $now_ts - $e_ts ) > 7200 ) {
					$overdue_count++;
				}
			}
		}
	}

	$veh_labels_bd = array(
		'Car'   => __( 'কার', 'ifs-parking' ),
		'Noah'  => __( 'নোয়াহ', 'ifs-parking' ),
		'Hiace' => __( 'হাইয়েস', 'ifs-parking' ),
		'CNG'   => __( 'সিএনজি', 'ifs-parking' ),
		'Truck' => __( 'ট্রাক', 'ifs-parking' ),
		'Bike'  => __( 'বাইক', 'ifs-parking' ),
		'Cycle' => __( 'সাইকেল', 'ifs-parking' ),
	);

	$brta_letters_bd = array(
		''  => __( 'বর্ণ নেই', 'ifs-parking' ),
		'ক' => __( 'ক (৮০০ সিসি কার)', 'ifs-parking' ),
		'খ' => __( 'খ (১০০০-১৩০০ কার)', 'ifs-parking' ),
		'গ' => __( 'গ (১৫০০-১৮০০ কার)', 'ifs-parking' ),
		'ঘ' => __( 'ঘ (জীপগাড়ি)', 'ifs-parking' ),
		'চ' => __( 'চ (মাইক্রোবাস/নোয়াহ)', 'ifs-parking' ),
		'ছ' => __( 'ছ (লেগুনা/ভাড়ায়)', 'ifs-parking' ),
		'ট' => __( 'ট (ট্রাক বড়)', 'ifs-parking' ),
		'ঠ' => __( 'ঠ (ডাবল কেবিন)', 'ifs-parking' ),
		'ড' => __( 'ড (ট্রাক মাঝারী)', 'ifs-parking' ),
		'ন' => __( 'ন (পিকআপ ছোট)', 'ifs-parking' ),
		'ম' => __( 'ম (ডেলিভারি ভ্যান)', 'ifs-parking' ),
		'দ' => __( 'দ (সিএনজি প্রাইভেট)', 'ifs-parking' ),
		'থ' => __( 'থ (সিএনজি ভাড়ায়)', 'ifs-parking' ),
		'হ' => __( 'হ (৮০-১২৫ সিসি বাইক)', 'ifs-parking' ),
		'ল' => __( 'ল (১৩৫-২০০ সিসি বাইক)', 'ifs-parking' ),
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
			font-family: 'Hind Siliguri', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
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
			padding: 18px;
			margin-bottom: 16px;
			box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
		}
		.ifs-card-top-bar {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 10px;
			margin-bottom: 14px;
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
			padding: 5px 12px;
			border-radius: 9px;
			font-size: 13.5px;
			font-weight: 700;
			letter-spacing: 0.2px;
		}
		.ifs-sec-label {
			font-size: 12.5px;
			font-weight: 700;
			color: #475569;
			margin: 0 0 8px 0;
			display: block;
		}

		/* কুইক গাড়ির বাটন গ্রিড (Tactile App Tiles) */
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

		/* মিনিমাল ইনপুট গ্রিড */
		.ifs-plate-row {
			display: grid;
			grid-template-columns: 1.05fr 1.2fr 1.45fr;
			gap: 8px;
			margin-bottom: 14px;
		}
		.ifs-meta-row {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 10px;
			margin-bottom: 14px;
		}
		.ifs-m-input {
			width: 100%;
			height: 46px;
			padding: 0 12px;
			border: 1.5px solid #cbd5e1;
			border-radius: 11px;
			font-size: 15px;
			font-weight: 500;
			color: #0f172a;
			background: #ffffff;
			transition: border-color 0.18s ease, box-shadow 0.18s ease;
		}
		.ifs-m-input:focus {
			border-color: #0284c7;
			outline: none;
			box-shadow: 0 0 0 3.5px rgba(2, 132, 199, 0.14);
		}
		.ifs-plate-num-input {
			font-weight: 700;
			letter-spacing: 0.4px;
		}

		/* নির্ধারিত ফি স্ট্রিপ */
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
			gap: 8px;
		}
		.ifs-rate-select {
			height: 36px;
			border-radius: 9px;
			border: 1px solid #7dd3fc;
			background: #ffffff;
			color: #0369a1;
			padding: 0 10px;
			font-weight: 700;
			font-size: 13px;
			cursor: pointer;
		}

		/* প্রাইমারি ও প্রিন্ট অ্যাকশন বাটন */
		.ifs-action-btn-grid {
			display: grid;
			grid-template-columns: 1.4fr 1fr;
			gap: 10px;
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
		.ifs-btn-checkin-now:active {
			transform: scale(0.98);
		}
		.ifs-btn-print-slip-main {
			width: 100%;
			height: 50px;
			background: #0f172a;
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
		.ifs-status-pills {
			display: flex;
			gap: 6px;
			flex-wrap: wrap;
		}
		.ifs-spill {
			border: 1px solid #e2e8f0;
			padding: 7px 13px;
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
		.ifs-search-wrap .ifs-m-input {
			padding-left: 38px;
			background: #f8fafc;
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
			transition: border-color 0.18s ease;
		}
		.ifs-recent-item:hover {
			border-color: #cbd5e1;
		}
		.ifs-recent-item.is-overdue-card {
			border-color: #fcd34d;
			background: #fffbeb;
		}
		.ifs-recent-actions {
			display: flex;
			align-items: center;
			gap: 8px;
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
			-webkit-tap-highlight-color: transparent;
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
			-webkit-tap-highlight-color: transparent;
		}

		/* ইনস্ট্যান্ট প্রিন্ট স্লিপ (শুধুমাত্র প্রিন্টের সময় দৃশ্যমান হবে) */
		#ifs-instant-print-sheet {
			display: none;
		}
		@media print {
			body * { visibility: hidden !important; }
			#ifs-instant-print-sheet,
			#ifs-instant-print-sheet * {
				visibility: visible !important;
			}
			#ifs-instant-print-sheet {
				display: block !important;
				position: fixed;
				left: 0;
				top: 0;
				width: 100%;
				max-width: 340px;
				padding: 14px;
				background: #ffffff;
				color: #000000;
				font-family: 'Hind Siliguri', sans-serif;
				font-size: 13px;
			}
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
			.ifs-action-btn-grid {
				grid-template-columns: 1fr;
				gap: 10px;
			}
			.ifs-m-input {
				font-size: 16px !important;
				height: 48px;
			}
			.ifs-status-pills {
				width: 100%;
				display: grid;
				grid-template-columns: repeat(3, 1fr);
				gap: 6px;
			}
			.ifs-spill {
				text-align: center;
				padding: 8px 4px;
				font-size: 11.5px;
			}
			.ifs-recent-actions {
				width: 100%;
			}
			.ifs-btn-mini-print,
			.ifs-btn-mini-checkout {
				flex: 1;
				height: 44px;
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
					<?php esc_html_e( 'সিলেট মেট্রো গ', 'ifs-parking' ); ?>
				</div>
			</div>

			<span class="ifs-sec-label"><?php esc_html_e( '১. গাড়ির ধরণ নির্বাচন করুন (অটো বিআরটিএ বর্ণ)', 'ifs-parking' ); ?></span>

			<form method="post" action="" id="ifs-fast-checkin-form">
				<?php wp_nonce_field( 'ifs_save_vehicle', 'ifs_vehicle_nonce' ); ?>
				<input type="hidden" name="category_id" id="ifs-hidden-cat-id" value="">
				<input type="hidden" name="selected_rate_type" id="ifs-hidden-rate-type" value="hourly">

				<!-- কুইক গাড়ির বাটন (আইকনসহ) -->
				<div class="ifs-quick-veh-grid">
					<?php
					$unique_vehs = ! empty( $all_categories ) ? array_unique( wp_list_pluck( $all_categories, 'vehicle_type' ) ) : array( 'Car', 'Noah', 'Bike', 'CNG', 'Truck' );
					foreach ( $unique_vehs as $v_idx => $v_item ) :
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

				<!-- বিআরটিএ রেজিস্ট্রেশন নম্বর -->
				<span class="ifs-sec-label"><?php esc_html_e( '২. বিআরটিএ (BRTA) গাড়ি নম্বর লিখুন *', 'ifs-parking' ); ?></span>
				<div class="ifs-plate-row">
					<select name="veh_div" id="f-div" class="ifs-m-input" required>
						<?php foreach ( $metro_options as $m_val => $m_label ) : ?>
							<option value="<?php echo esc_attr( $m_val ); ?>"><?php echo esc_html( $m_label ); ?></option>
						<?php endforeach; ?>
					</select>

					<select name="veh_brta_letter" id="f-brta-letter" class="ifs-m-input">
						<?php foreach ( $brta_letters_bd as $b_key => $b_label ) : ?>
							<option value="<?php echo esc_attr( $b_key ); ?>"><?php echo esc_html( $b_label ); ?></option>
						<?php endforeach; ?>
					</select>

					<input type="text" name="veh_num" id="f-num" class="ifs-m-input ifs-plate-num-input" required autocomplete="off" placeholder="<?php esc_attr_e( 'নম্বর লিখুন (যেমন: ১১-১২৩৪)', 'ifs-parking' ); ?>">
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
						<input type="tel" name="driver_phone" id="f-phone" class="ifs-m-input" placeholder="<?php esc_attr_e( '০১XXXXXXXXX (ঐচ্ছিক)', 'ifs-parking' ); ?>">
					</div>
				</div>

				<!-- কন্ডিশনাল আইডি (শুধু Gym বা Employee হলে দেখাবে) -->
				<div id="ifs-conditional-id-box" style="display: none; margin-bottom: 14px;">
					<input type="text" name="gym_employee_id" id="ifs-gym-emp-id-input" class="ifs-m-input" placeholder="<?php esc_attr_e( 'সদস্য / স্টাফ আইডি নম্বর লিখুন *', 'ifs-parking' ); ?>">
				</div>

				<!-- নির্ধারিত ২-ঘণ্টা স্ল্যাব ফি স্ট্রিপ -->
				<div class="ifs-rate-strip">
					<div>
						<span style="font-size:13px; color:#0369a1; font-weight:600;"><?php esc_html_e( 'নির্ধারিত চার্জ (প্রতি ২ ঘণ্টা স্ল্যাব):', 'ifs-parking' ); ?></span>
						<strong id="ifs-live-rate-display" style="font-size:17px; color:#0f172a; margin-left:6px;">-</strong>
					</div>
					<select id="ifs-pkg-type-select" class="ifs-rate-select">
						<option value="hourly"><?php esc_html_e( 'প্রতি ২ ঘণ্টা স্ল্যাব', 'ifs-parking' ); ?></option>
						<option value="daily"><?php esc_html_e( 'দৈনিক প্যাকেজ', 'ifs-parking' ); ?></option>
						<option value="monthly"><?php esc_html_e( 'মাসিক প্যাকেজ', 'ifs-parking' ); ?></option>
					</select>
				</div>

				<div class="ifs-action-btn-grid">
					<button type="submit" name="submit_vehicle" class="ifs-btn-checkin-now">
						<svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:currentColor;"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
						<?php esc_html_e( 'চেক-ইন সম্পন্ন করুন', 'ifs-parking' ); ?>
					</button>

					<button type="button" id="ifs-print-latest-btn" class="ifs-btn-print-slip-main">
						<svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor;"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
						<?php esc_html_e( 'স্লিপ প্রিন্ট (১-ক্লিক)', 'ifs-parking' ); ?>
					</button>
				</div>
			</form>
		</div>

		<!-- সেকশন ২: একই পেজে লাইভ স্ট্যাটাস, ফিল্টার ও রিসেন্ট এন্ট্রি তালিকা -->
		<div class="ifs-minimal-card">
			<div class="ifs-recent-header">
				<div>
					<h3 style="margin:0; font-size:17px; font-weight:700; color:#0f172a;"><?php esc_html_e( 'সাম্প্রতিক এন্ট্রি ও লাইভ স্ট্যাটাস', 'ifs-parking' ); ?></h3>
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
						$entry_ts   = strtotime( $item->entry_time );
						$diff_secs  = $entry_ts ? max( 0, $now_ts - $entry_ts ) : 0;
						$is_parked  = ( 'Parked' === $item->status );
						$is_overdue = ( $is_parked && $diff_secs > 7200 );
						$base_rate  = (float) $item->fee;
						$slabs      = ( $diff_secs <= 7200 ) ? 1 : (int) ceil( $diff_secs / 7200 );
						$live_fee   = $is_parked ? round( $slabs * $base_rate, 2 ) : $base_rate;
						$dept_txt   = ! empty( $item->reference_id ) ? $item->reference_id : 'General';
						$inv_no     = strtok( $dept_txt, ' [' );
						$time_str   = $entry_ts ? ifs_parking_veh_bn_num( date_i18n( 'h:i A, j M', $entry_ts ) ) : $item->entry_time;
						$cat_clean  = ucwords( str_replace( '-', ' - ', (string) $item->parking_type ) );
						?>
						<div class="ifs-recent-item <?php echo $is_overdue ? 'is-overdue-card' : ''; ?>"
							 data-parked="<?php echo $is_parked ? '1' : '0'; ?>"
							 data-overdue="<?php echo $is_overdue ? '1' : '0'; ?>">
							<div>
								<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
									<strong style="font-size:16px; color:#0f172a;"><?php echo esc_html( $item->vehicle_number ); ?></strong>
									<span style="font-size:12px; font-weight:700; padding:2px 9px; border-radius:6px; background:#e0f2fe; color:#0369a1;">
										<?php echo esc_html( $cat_clean ); ?>
									</span>
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
									<?php esc_html_e( 'প্রবেশ:', 'ifs-parking' ); ?> <strong><?php echo esc_html( $time_str ); ?></strong>
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
										data-cat="<?php echo esc_attr( $cat_clean ); ?>"
										data-phone="<?php echo esc_attr( ifs_parking_veh_bn_num( $item->driver_phone ) ); ?>"
										data-time="<?php echo esc_attr( $time_str ); ?>"
										data-fee="<?php echo esc_attr( $currency_symbol . ifs_parking_veh_bn_num( number_format( $live_fee, 2 ) ) ); ?>">
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
		</div>
	</div>

	<!-- ১-ক্লিক থার্মাল প্রিন্ট রসিদ টেমপ্লেট -->
	<div id="ifs-instant-print-sheet">
		<div style="text-align:center; border-bottom:1px dashed #000; padding-bottom:8px; margin-bottom:8px;">
			<strong style="font-size:16px; display:block;"><?php echo esc_html( $site_title ); ?></strong>
			<span style="font-size:12px;"><?php esc_html_e( 'হেল্পলাইন:', 'ifs-parking' ); ?> <?php echo esc_html( ifs_parking_veh_bn_num( $system_phone ) ); ?></span><br>
			<strong style="font-size:12px;"><?php esc_html_e( 'পার্কিং টোকেন / রসিদ', 'ifs-parking' ); ?></strong>
		</div>
		<div style="line-height:1.7;">
			<div><?php esc_html_e( 'স্লিপ নং:', 'ifs-parking' ); ?> <strong id="sp-inv">-</strong></div>
			<div><?php esc_html_e( 'গাড়ি নম্বর:', 'ifs-parking' ); ?> <strong id="sp-veh" style="font-size:15px;">-</strong></div>
			<div><?php esc_html_e( 'ধরণ:', 'ifs-parking' ); ?> <strong id="sp-cat">-</strong></div>
			<div><?php esc_html_e( 'প্রবেশের সময়:', 'ifs-parking' ); ?> <strong id="sp-time">-</strong></div>
			<div id="sp-phone-row" style="display:none;"><?php esc_html_e( 'মোবাইল:', 'ifs-parking' ); ?> <strong id="sp-phone">-</strong></div>
			<div style="border-top:1px dashed #000; margin-top:6px; padding-top:6px; font-size:14px;">
				<?php esc_html_e( 'নির্ধারিত ফি (প্রতি ২ ঘণ্টা):', 'ifs-parking' ); ?> <strong id="sp-fee">-</strong>
			</div>
		</div>
		<div style="text-align:center; border-top:1px dashed #000; margin-top:10px; padding-top:6px; font-size:11px;">
			<?php esc_html_e( 'ধন্যবাদ! টোকেনটি সংরক্ষণ করুন।', 'ifs-parking' ); ?>
		</div>
	</div>

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		var categoriesData = <?php echo wp_json_encode( $all_categories ); ?>;
		var currencySymbol = <?php echo wp_json_encode( $currency_symbol ); ?>;

		// বিআরটিএ অটো সিরিজ বর্ণ ম্যাপিং
		var brtaAutoLetterMap = {
			'Car': 'গ',
			'Noah': 'চ',
			'Hiace': 'চ',
			'CNG': 'থ',
			'Truck': 'ট',
			'Bike': 'ল',
			'Cycle': ''
		};

		var fDiv           = document.getElementById('f-div');
		var fBrtaLetter    = document.getElementById('f-brta-letter');
		var fNum           = document.getElementById('f-num');
		var livePlateBadge = document.getElementById('ifs-live-plate-preview');
		var deptDropdown   = document.getElementById('ifs-dept-dropdown');
		var pkgTypeSelect  = document.getElementById('ifs-pkg-type-select');
		var hiddenCatId    = document.getElementById('ifs-hidden-cat-id');
		var hiddenRateType = document.getElementById('ifs-hidden-rate-type');
		var rateDisplay    = document.getElementById('ifs-live-rate-display');
		var condIdBox      = document.getElementById('ifs-conditional-id-box');
		var condIdInput    = document.getElementById('ifs-gym-emp-id-input');

		var bnMap = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
		function toBnDigits(str) {
			return String(str).replace(/[0-9]/g, function(w) { return bnMap[+w]; });
		}

		function updateLivePlateBadge() {
			if (!livePlateBadge) return;
			var divTxt = fDiv ? fDiv.value : '';
			var letTxt = fBrtaLetter ? fBrtaLetter.value : '';
			var numTxt = fNum ? toBnDigits(fNum.value.trim()) : '';
			var full   = [divTxt, letTxt, numTxt].filter(Boolean).join(' ');
			livePlateBadge.textContent = full || '-';
		}

		function getSelectedVeh() {
			var el = document.querySelector('.ifs-quick-veh-selector:checked');
			return el ? el.value : 'Car';
		}

		function syncRateAndCategory() {
			var vehType  = getSelectedVeh();
			var deptName = deptDropdown ? deptDropdown.value : 'General';
			var rateType = pkgTypeSelect ? pkgTypeSelect.value : 'hourly';

			if (condIdBox && condIdInput) {
				if (deptName === 'Gym' || deptName === 'Staff' || deptName === 'Employee') {
					condIdBox.style.display = 'block';
					condIdInput.setAttribute('required', 'required');
				} else {
					condIdBox.style.display = 'none';
					condIdInput.removeAttribute('required');
				}
			}

			var matched = categoriesData.find(function(c) {
				return c.department === deptName && c.vehicle_type === vehType;
			}) || categoriesData.find(function(c) {
				return c.vehicle_type === vehType;
			}) || categoriesData[0];

			if (matched) {
				hiddenCatId.value    = matched.id;
				hiddenRateType.value = rateType;
				var amount = rateType === 'daily' ? matched.daily_rate : (rateType === 'monthly' ? matched.monthly_rate : matched.hourly_rate);
				if (rateDisplay) {
					rateDisplay.textContent = currencySymbol + toBnDigits(parseFloat(amount).toFixed(2));
				}
			}
			updateLivePlateBadge();
		}

		document.querySelectorAll('.ifs-quick-veh-selector').forEach(function(radio) {
			radio.addEventListener('change', function() {
				var v = getSelectedVeh();
				if (fBrtaLetter && Object.prototype.hasOwnProperty.call(brtaAutoLetterMap, v)) {
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

		// ইনিশিয়াল লোডে BRTA বর্ণ ও রেট সেট করা
		var initVeh = getSelectedVeh();
		if (fBrtaLetter && Object.prototype.hasOwnProperty.call(brtaAutoLetterMap, initVeh)) {
			fBrtaLetter.value = brtaAutoLetterMap[initVeh];
		}
		syncRateAndCategory();

		// স্লিপ প্রিন্ট ফাংশন (১-ক্লিকে থার্মাল প্রিন্ট)
		function printSlipData(data) {
			document.getElementById('sp-inv').textContent   = data.inv || '-';
			document.getElementById('sp-veh').textContent   = data.veh || '-';
			document.getElementById('sp-cat').textContent   = data.cat || '-';
			document.getElementById('sp-time').textContent  = data.time || '-';
			document.getElementById('sp-fee').textContent   = data.fee || '-';

			var phoneRow = document.getElementById('sp-phone-row');
			var phoneVal = document.getElementById('sp-phone');
			if (data.phone) {
				phoneVal.textContent = data.phone;
				phoneRow.style.display = 'block';
			} else {
				phoneRow.style.display = 'none';
			}
			window.print();
		}

		document.querySelectorAll('.ifs-trigger-slip-print').forEach(function(btn) {
			btn.addEventListener('click', function() {
				printSlipData({
					inv: this.getAttribute('data-inv'),
					veh: this.getAttribute('data-veh'),
					cat: this.getAttribute('data-cat'),
					phone: this.getAttribute('data-phone'),
					time: this.getAttribute('data-time'),
					fee: this.getAttribute('data-fee')
				});
			});
		});

		var printLatestBtn = document.getElementById('ifs-print-latest-btn');
		if (printLatestBtn) {
			printLatestBtn.addEventListener('click', function() {
				var firstSlipBtn = document.querySelector('.ifs-trigger-slip-print');
				if (firstSlipBtn) {
					firstSlipBtn.click();
				}
			});
		}

		// সদ্য চেক-ইন হয়ে থাকলে একই পেজ থেকেই অটোমেটিক প্রিন্ট ডায়ালগ ওপেন করা
		<?php if ( $just_checked_in ) : ?>
			<?php
			$j_ts   = strtotime( $just_checked_in->entry_time );
			$j_time = $j_ts ? ifs_parking_veh_bn_num( date_i18n( 'h:i A, j M', $j_ts ) ) : $just_checked_in->entry_time;
			$j_inv  = ! empty( $just_checked_in->reference_id ) ? strtok( $just_checked_in->reference_id, ' [' ) : 'NPMS-' . $just_checked_in->id;
			?>
			setTimeout(function() {
				printSlipData({
					inv: <?php echo wp_json_encode( $j_inv ); ?>,
					veh: <?php echo wp_json_encode( $just_checked_in->vehicle_number ); ?>,
					cat: <?php echo wp_json_encode( ucwords( str_replace( '-', ' - ', (string) $just_checked_in->parking_type ) ) ); ?>,
					phone: <?php echo wp_json_encode( ifs_parking_veh_bn_num( $just_checked_in->driver_phone ) ); ?>,
					time: <?php echo wp_json_encode( $j_time ); ?>,
					fee: <?php echo wp_json_encode( $currency_symbol . ifs_parking_veh_bn_num( number_format( (float) $just_checked_in->fee, 2 ) ) ); ?>
				});
			}, 300);
		<?php endif; ?>

		// রিসেন্ট লিস্ট ইনস্ট্যান্ট ফিল্টার (Parked / Overdue / All) ও সার্চ
		var currentFilter = 'parked';
		var searchInput   = document.getElementById('ifs-recent-search');

		function filterRecentCards() {
			var q = searchInput ? searchInput.value.toLowerCase().trim() : '';
			document.querySelectorAll('.ifs-recent-item').forEach(function(card) {
				var isParked  = card.getAttribute('data-parked') === '1';
				var isOverdue = card.getAttribute('data-overdue') === '1';
				var matchText = (q === '' || card.textContent.toLowerCase().indexOf(q) !== -1);

				var matchTab = true;
				if (currentFilter === 'parked') matchTab = isParked;
				if (currentFilter === 'overdue') matchTab = isOverdue;

				card.style.display = (matchText && matchTab) ? 'flex' : 'none';
			});
		}

		document.querySelectorAll('.ifs-spill').forEach(function(pill) {
			pill.addEventListener('click', function() {
				currentFilter = this.getAttribute('data-filter') || 'all';
				document.querySelectorAll('.ifs-spill').forEach(function(p) {
					p.classList.remove('is-active-all', 'is-active-parked', 'is-active-overdue');
				});
				if (currentFilter === 'parked') this.classList.add('is-active-parked');
				else if (currentFilter === 'overdue') this.classList.add('is-active-overdue');
				else this.classList.add('is-active-all');

				filterRecentCards();
			});
		});

		if (searchInput) {
			searchInput.addEventListener('input', filterRecentCards);
		}
		filterRecentCards();
	});
	</script>
	<?php
}