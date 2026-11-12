<?php
/**
 * Vehicle Add/Edit Form & Save Handler (BRTA Auto-Series & Short Mobile Form).
 *
 * @package IFS_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ইংরেজি সংখ্যাকে বাংলা সংখ্যায় রূপান্তর করার হেল্পার ফাংশন।
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
 * নতুন গাড়ি চেক-ইন বা সম্পাদনা ডাটা সেভ হ্যান্ডলার।
 *
 * @param string $table_logs লগ টেবিল নাম।
 * @param string $table_cats ক্যাটাগরি টেবিল নাম।
 * @return void
 */
function ifs_parking_save_vehicle_entry( $table_logs, $table_cats ) {
	static $already_processed = false;
	if ( $already_processed ) {
		return;
	}

	if (
		! isset( $_POST['ifs_vehicle_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ifs_vehicle_nonce'] ) ), 'ifs_save_vehicle' ) ||
		! current_user_can( 'read' )
	) {
		return;
	}

	$already_processed = true;
	global $wpdb;

	$edit_id        = isset( $_POST['edit_log_id'] ) ? absint( $_POST['edit_log_id'] ) : 0;
	$div_val        = isset( $_POST['veh_div'] ) ? sanitize_text_field( wp_unslash( $_POST['veh_div'] ) ) : '';
	$brta_letter    = isset( $_POST['veh_brta_letter'] ) ? sanitize_text_field( wp_unslash( $_POST['veh_brta_letter'] ) ) : '';
	$num_val        = isset( $_POST['veh_num'] ) ? sanitize_text_field( wp_unslash( $_POST['veh_num'] ) ) : '';
	$vehicle_number = trim( preg_replace( '/\s+/', ' ', $div_val . ' ' . $brta_letter . ' ' . $num_val ) );

	$cat_id_selected = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
	$billing_type    = isset( $_POST['selected_rate_type'] ) ? sanitize_key( wp_unslash( $_POST['selected_rate_type'] ) ) : 'hourly';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$selected_cat = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_cats}` WHERE id = %d", $cat_id_selected ) );

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

	$parking_type   = $selected_cat ? $selected_cat->category_slug : 'general';
	$driver_name    = isset( $_POST['driver_name'] ) ? sanitize_text_field( wp_unslash( $_POST['driver_name'] ) ) : '';
	$driver_phone   = isset( $_POST['driver_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['driver_phone'] ) ) : '';
	$membership_exp = ! empty( $_POST['membership_expiry'] ) ? sanitize_text_field( wp_unslash( $_POST['membership_expiry'] ) ) : null;
	$gym_emp_id     = isset( $_POST['gym_employee_id'] ) ? sanitize_text_field( wp_unslash( $_POST['gym_employee_id'] ) ) : '';

	$now_ts      = current_time( 'timestamp' );
	$month_name  = strtoupper( wp_date( 'M', $now_ts ) );
	$day_num     = strtoupper( wp_date( 'd', $now_ts ) );
	$prefix_base = 'NPMS-' . $month_name . '-' . $day_num . '-';
	$today_date  = current_time( 'Y-m-d' );
	$today_start = $today_date . ' 00:00:00';
	$today_end   = $today_date . ' 23:59:59';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$count_today = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM `{$table_logs}` WHERE entry_time >= %s AND entry_time <= %s",
			$today_start,
			$today_end
		)
	);

	$sequence_num = str_pad( (string) ( $count_today + 1 ), 4, '0', STR_PAD_LEFT );
	$reference_id = $prefix_base . $sequence_num;

	if ( ! empty( $gym_emp_id ) ) {
		$reference_id .= ' (ID: ' . $gym_emp_id . ')';
	} elseif ( $selected_cat ) {
		$reference_id .= ' [' . $selected_cat->department . ']';
	}

	$nid_path = isset( $_POST['existing_nid_path'] ) ? esc_url_raw( wp_unslash( $_POST['existing_nid_path'] ) ) : '';
	if ( ! empty( $_FILES['driver_nid']['name'] ) && isset( $_FILES['driver_nid']['error'] ) && UPLOAD_ERR_OK === $_FILES['driver_nid']['error'] ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$upload_overrides = array(
			'test_form' => false,
			'mimes'     => array(
				'jpg|jpeg|jpe' => 'image/jpeg',
				'png'          => 'image/png',
				'pdf'          => 'application/pdf',
			),
		);
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$uploaded = wp_handle_upload( $_FILES['driver_nid'], $upload_overrides );
		if ( isset( $uploaded['url'] ) ) {
			$nid_path = esc_url_raw( $uploaded['url'] );
		}
	}

	if ( ! empty( $num_val ) && $selected_cat ) {
		if ( $edit_id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$updated = $wpdb->update(
				$table_logs,
				array(
					'vehicle_number'    => $vehicle_number,
					'parking_type'      => $parking_type,
					'driver_name'       => $driver_name,
					'driver_phone'      => $driver_phone,
					'nid_path'          => $nid_path,
					'membership_expiry' => $membership_exp,
					'fee'               => $fee_rate,
				),
				array( 'id' => $edit_id ),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%f' ),
				array( '%d' )
			);

			if ( false !== $updated ) {
				$redirect_url = admin_url( 'admin.php?page=ifs-parking-vehicles&tab=all_vehicles&saved=1' );
				if ( ! headers_sent() ) {
					wp_safe_redirect( $redirect_url );
					exit;
				}
				echo '<script>window.location.href="' . esc_url( $redirect_url ) . '";</script>';
				exit;
			}
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $wpdb->insert(
				$table_logs,
				array(
					'vehicle_number'    => $vehicle_number,
					'parking_type'      => $parking_type,
					'slot_number'       => '',
					'reference_id'      => $reference_id,
					'driver_name'       => $driver_name,
					'driver_phone'      => $driver_phone,
					'nid_path'          => $nid_path,
					'membership_expiry' => $membership_exp,
					'entry_time'        => current_time( 'mysql' ),
					'fee'               => $fee_rate,
					'status'            => 'Parked',
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s' )
			);

			if ( false !== $inserted ) {
				$new_insert_id = $wpdb->insert_id;
				$redirect_url  = admin_url( 'admin.php?page=ifs-parking-vehicles&tab=all_vehicles&auto_print=' . $new_insert_id . '&saved=1' );

				if ( ! headers_sent() ) {
					wp_safe_redirect( $redirect_url );
					exit;
				}
				echo '<script>window.location.href="' . esc_url( $redirect_url ) . '";</script>';
				exit;
			}
		}
	} else {
		echo '<div class="notice notice-error is-dismissible ifs-custom-notice-err" style="border-radius:10px; border-left: 4px solid #ef4444; margin-bottom: 20px; padding: 12px 15px; background: #fff;">
				<p class="ifs-notice-err-text" style="margin:0; font-weight:600; color:#991b1b;">
					' . esc_html__( 'দয়া করে গাড়ি নম্বর এবং প্যাকেজ/রেট সঠিকভাবে নির্বাচন করুন।', 'ifs-parking' ) . '
				</p>
			  </div>';
	}
}

/**
 * সুপার-শর্ট বাংলা চেক-ইন ফরম ও রসিদ প্রিভিউ রেন্ডারার।
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
	if ( isset( $_POST['ifs_vehicle_nonce'] ) ) {
		ifs_parking_save_vehicle_entry( $table_logs, $table_cats );
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$all_categories = $wpdb->get_results( "SELECT * FROM `{$table_cats}` ORDER BY id DESC" );
	$departments    = array();
	if ( ! empty( $all_categories ) ) {
		$departments = array_unique( wp_list_pluck( $all_categories, 'department' ) );
	}
	if ( in_array( 'General', $departments, true ) ) {
		$departments = array_diff( $departments, array( 'General' ) );
		array_unshift( $departments, 'General' );
	}
	$departments = array_values( $departments );

	$now_ts      = current_time( 'timestamp' );
	$month_name  = strtoupper( wp_date( 'M', $now_ts ) );
	$day_num     = strtoupper( wp_date( 'd', $now_ts ) );
	$today_date  = current_time( 'Y-m-d' );
	$today_start = $today_date . ' 00:00:00';
	$today_end   = $today_date . ' 23:59:59';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$count_today = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM `{$table_logs}` WHERE entry_time >= %s AND entry_time <= %s",
			$today_start,
			$today_end
		)
	);

	$preview_invoice = 'NPMS-' . $month_name . '-' . $day_num . '-' . str_pad( (string) ( $count_today + 1 ), 4, '0', STR_PAD_LEFT );

	$veh_labels_bd = array(
		'Car'   => __( 'কার (প্রাইভেট কার)', 'ifs-parking' ),
		'Noah'  => __( 'নোয়াহ (মাইক্রোবাস)', 'ifs-parking' ),
		'Hiace' => __( 'হাইয়েস (মাইক্রোবাস)', 'ifs-parking' ),
		'CNG'   => __( 'সিএনজি (অটোরিকশা)', 'ifs-parking' ),
		'Truck' => __( 'ট্রাক / পিকআপ', 'ifs-parking' ),
		'Bike'  => __( 'বাইক (মোটরসাইকেল)', 'ifs-parking' ),
		'Cycle' => __( 'সাইকেল', 'ifs-parking' ),
	);

	// বিআরটিএ (BRTA) প্রধান সিরিজ বর্ণসমূহ (গাড়ির ধরণ অনুযায়ী অটো-সিলেক্ট হবে)।
	$brta_letters_bd = array(
		''  => __( 'বর্ণ নেই', 'ifs-parking' ),
		'ক' => __( 'ক (৮০০ সিসি প্রাইভেটকার)', 'ifs-parking' ),
		'খ' => __( 'খ (১০০০-১৩০০ সিসি প্রাইভেটকার)', 'ifs-parking' ),
		'গ' => __( 'গ (১৫০০-১৮০০ সিসি প্রাইভেটকার)', 'ifs-parking' ),
		'ঘ' => __( 'ঘ (জীপগাড়ি)', 'ifs-parking' ),
		'চ' => __( 'চ (মাইক্রোবাস - নোয়াহ/হাইয়েস)', 'ifs-parking' ),
		'ছ' => __( 'ছ (মাইক্রোবাস / লেগুনা ভাড়ায়)', 'ifs-parking' ),
		'ট' => __( 'ট (ট্রাক - বড়)', 'ifs-parking' ),
		'ঠ' => __( 'ঠ (ডাবল কেবিন পিকআপ)', 'ifs-parking' ),
		'ড' => __( 'ড (ট্রাক - মাঝারী)', 'ifs-parking' ),
		'ন' => __( 'ন (পিকআপ - ছোট)', 'ifs-parking' ),
		'ম' => __( 'ম (পিকআপ - ডেলিভারি)', 'ifs-parking' ),
		'দ' => __( 'দ (সিএনজি - প্রাইভেট)', 'ifs-parking' ),
		'থ' => __( 'থ (সিএনজি - ভাড়ায় চালিত)', 'ifs-parking' ),
		'হ' => __( 'হ (৮০-১২৫ সিসি মোটরবাইক)', 'ifs-parking' ),
		'ল' => __( 'ল (১৩৫-২০০ সিসি মোটরবাইক)', 'ifs-parking' ),
	);

	// সিলেট ও ঢাকা সবার উপরে রাখা হয়েছে।
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
	?>
	<style>
		.ifs-entry-container {
			display: grid;
			grid-template-columns: 1fr 370px;
			gap: 20px;
			align-items: start;
			font-family: 'Hind Siliguri', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
		}
		.ifs-plate-grid-3 {
			display: grid;
			grid-template-columns: 1.1fr 1.15fr 1.4fr;
			gap: 8px;
		}
		.ifs-radio-pill-group {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
		}
		#ifs-package-panes-container {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 10px;
			margin-top: 10px;
		}
		.ifs-pkg-card-pane {
			background: #ffffff;
			border: 1.5px solid #cbd5e1;
			padding: 12px;
			border-radius: 10px;
			cursor: pointer;
			display: flex;
			flex-direction: column;
			gap: 4px;
			transition: all 0.2s ease;
		}
		.ifs-pkg-card-pane:has(input:checked) {
			border-color: #0284c7;
			background: #f0f9ff;
			box-shadow: 0 2px 8px rgba(2, 132, 199, 0.12);
		}
		.ifs-optional-toggle-btn {
			background: #f1f5f9;
			border: 1px dashed #94a3b8;
			color: #334155;
			padding: 10px 14px;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 600;
			cursor: pointer;
			width: 100%;
			text-align: left;
			display: flex;
			align-items: center;
			justify-content: space-between;
			margin-bottom: 14px;
		}
		.ifs-form-2col {
			display: grid;
			grid-template-columns: repeat(2, 1fr);
			gap: 12px;
			margin-bottom: 14px;
		}
		@media print {
			body * { visibility: hidden; }
			#printable-receipt, #printable-receipt * { visibility: visible; }
			#printable-receipt { position: fixed; left: 0; top: 0; width: 100%; box-shadow: none !important; border: none !important; }
			.ifs-no-print { display: none !important; }
		}
		@media screen and (max-width: 1024px) {
			.ifs-entry-container { grid-template-columns: 1fr; }
		}
		@media screen and (max-width: 782px) {
			.ifs-plate-grid-3,
			#ifs-package-panes-container,
			.ifs-form-2col {
				grid-template-columns: 1fr;
			}
			.ifs-veh-input,
			.ifs-file-input,
			.ifs-step-box select {
				font-size: 16px !important;
				height: 44px;
				width: 100%;
				box-sizing: border-box;
			}
			.ifs-btn-submit,
			.ifs-btn-print-full,
			.ifs-btn-print-dark {
				width: 100%;
				justify-content: center;
				height: 48px;
			}
		}
	</style>

	<div class="ifs-entry-container">
		<!-- সুপার-শর্ট চেক-ইন ফরম -->
		<div class="ifs-card ifs-form-card">
			<h3 class="ifs-form-heading">
				<svg class="ifs-svg-primary" viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z"/></svg>
				<?php esc_html_e( 'দ্রুত গাড়ি চেক-ইন ফরম (বিআরটিএ স্ট্যান্ডার্ড)', 'ifs-parking' ); ?>
			</h3>

			<form method="post" action="" enctype="multipart/form-data" id="ifs-live-form">
				<?php wp_nonce_field( 'ifs_save_vehicle', 'ifs_vehicle_nonce' ); ?>
				<input type="hidden" name="category_id" id="ifs-hidden-cat-id" value="">
				<input type="hidden" name="selected_rate_type" id="ifs-hidden-rate-type" value="hourly">

				<!-- ধাপ ১: গাড়ির ধরণ (আগে সিলেক্ট করলে অটো BRTA বর্ণ নিয়ে নেবে) -->
				<div class="ifs-step-box">
					<h4 class="ifs-step-title">
						<span class="ifs-step-num"><?php echo esc_html( ifs_parking_veh_bn_num( 1 ) ); ?></span>
						<?php esc_html_e( 'গাড়ির ধরণ নির্বাচন করুন (অটো বিআরটিএ বর্ণ সেট হবে) *', 'ifs-parking' ); ?>
					</h4>
					<div class="ifs-radio-pill-group" id="ifs-quick-veh-pills">
						<?php
						$unique_all_vehs = ! empty( $all_categories ) ? array_unique( wp_list_pluck( $all_categories, 'vehicle_type' ) ) : array( 'Car', 'Noah', 'Bike', 'CNG', 'Truck' );
						foreach ( $unique_all_vehs as $v_idx => $v_type_item ) :
							$v_label = isset( $veh_labels_bd[ $v_type_item ] ) ? $veh_labels_bd[ $v_type_item ] : $v_type_item;
							?>
							<label class="ifs-radio-pill">
								<input type="radio" name="quick_veh_radio" class="ifs-quick-veh-selector" value="<?php echo esc_attr( $v_type_item ); ?>" <?php checked( 0 === $v_idx ); ?>>
								<span class="ifs-pill-label"><?php echo esc_html( $v_label ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- ধাপ ২: বিআরটিএ (BRTA) শর্ট রেজিস্ট্রেশন নম্বর -->
				<div class="ifs-form-field-group">
					<label class="ifs-form-label" for="f-num"><?php esc_html_e( 'বিআরটিএ (BRTA) গাড়ি নম্বর *', 'ifs-parking' ); ?></label>
					<div class="ifs-plate-grid-3">
						<select name="veh_div" id="f-div" class="ifs-veh-input" required>
							<?php foreach ( $metro_options as $m_val => $m_label ) : ?>
								<option value="<?php echo esc_attr( $m_val ); ?>"><?php echo esc_html( $m_label ); ?></option>
							<?php endforeach; ?>
						</select>

						<select name="veh_brta_letter" id="f-brta-letter" class="ifs-veh-input" title="<?php esc_attr_e( 'গাড়ির ধরণ অনুযায়ী অটো বর্ণ', 'ifs-parking' ); ?>">
							<?php foreach ( $brta_letters_bd as $b_key => $b_label ) : ?>
								<option value="<?php echo esc_attr( $b_key ); ?>"><?php echo esc_html( $b_label ); ?></option>
							<?php endforeach; ?>
						</select>

						<input type="text" name="veh_num" id="f-num" class="ifs-veh-input" required placeholder="<?php esc_attr_e( 'শুধু নম্বর লিখুন (যেমন: ১১-১২৩৪)', 'ifs-parking' ); ?>">
					</div>
				</div>

				<!-- ধাপ ৩: ডিপার্টমেন্ট ও মেম্বারশিপ ধরণ -->
				<div class="ifs-step-box">
					<h4 class="ifs-step-title">
						<span class="ifs-step-num"><?php echo esc_html( ifs_parking_veh_bn_num( 2 ) ); ?></span>
						<?php esc_html_e( 'ডিপার্টমেন্ট / মেম্বারশিপ নির্বাচন করুন *', 'ifs-parking' ); ?>
					</h4>
					<div class="ifs-radio-pill-group">
						<?php foreach ( $departments as $d_idx => $dept_name ) : ?>
							<label class="ifs-radio-pill">
								<input type="radio" name="dept_radio" class="ifs-dept-selector" value="<?php echo esc_attr( $dept_name ); ?>" <?php checked( 0 === $d_idx ); ?>>
								<span class="ifs-pill-label"><?php echo esc_html( $dept_name ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>

					<div id="ifs-conditional-id-box" style="margin-top: 12px; display: none;">
						<label class="ifs-form-label-sm" id="ifs-id-label" for="ifs-gym-emp-id-input"><?php esc_html_e( 'আইডি নম্বর *', 'ifs-parking' ); ?></label>
						<input type="text" name="gym_employee_id" id="ifs-gym-emp-id-input" class="ifs-veh-input" placeholder="<?php esc_attr_e( 'আইডি বা কোড লিখুন', 'ifs-parking' ); ?>">
					</div>
				</div>

				<!-- ধাপ ৪: প্যাকেজ ও ২-ঘণ্টা স্ল্যাব রেট -->
				<div class="ifs-step-box ifs-step-pkg">
					<div class="ifs-step-header">
						<h4 class="ifs-step-title ifs-step-title-no-m">
							<span class="ifs-step-num"><?php echo esc_html( ifs_parking_veh_bn_num( 3 ) ); ?></span>
							<?php esc_html_e( 'প্যাকেজ ও বিলিং রেট (প্রতি ২ ঘণ্টা স্ল্যাব) *', 'ifs-parking' ); ?>
						</h4>
					</div>
					<div id="ifs-package-panes-container"></div>
				</div>

				<!-- ঐচ্ছিক তথ্য (Name / Phone / NID - Collapsed by default for ultra-fast entry) -->
				<button type="button" id="ifs-toggle-optional-btn" class="ifs-optional-toggle-btn">
					<span>+ <?php esc_html_e( 'চালকের নাম, ফোন ও এনআইডি (ঐচ্ছিক - প্রয়োজন হলে ক্লিক করুন)', 'ifs-parking' ); ?></span>
					<span>&#9662;</span>
				</button>

				<div id="ifs-optional-fields-box" style="display: none;">
					<div class="ifs-form-2col">
						<div>
							<label class="ifs-form-label-sm" for="f-name"><?php esc_html_e( 'চালকের নাম (ঐচ্ছিক)', 'ifs-parking' ); ?></label>
							<input type="text" name="driver_name" id="f-name" class="ifs-veh-input" placeholder="<?php esc_attr_e( 'পুরো নাম', 'ifs-parking' ); ?>">
						</div>
						<div>
							<label class="ifs-form-label-sm" for="f-phone"><?php esc_html_e( 'চালকের ফোন (ঐচ্ছিক)', 'ifs-parking' ); ?></label>
							<input type="text" name="driver_phone" id="f-phone" class="ifs-veh-input" placeholder="<?php esc_attr_e( '০১XXXXXXXXX', 'ifs-parking' ); ?>">
						</div>
					</div>
					<div class="ifs-form-2col">
						<div>
							<label class="ifs-form-label-sm" for="f-membership"><?php esc_html_e( 'মেম্বারশিপ মেয়াদ (ঐচ্ছিক)', 'ifs-parking' ); ?></label>
							<input type="date" name="membership_expiry" id="f-membership" class="ifs-veh-input">
						</div>
						<div>
							<label class="ifs-form-label-sm" for="f-nid"><?php esc_html_e( 'এনআইডি (NID) আপলোড (ঐচ্ছিক)', 'ifs-parking' ); ?></label>
							<input type="file" name="driver_nid" id="f-nid" accept="image/jpeg,image/png,.pdf" class="ifs-file-input">
						</div>
					</div>
				</div>

				<button type="submit" name="submit_vehicle" class="ifs-btn-submit">
					<svg class="ifs-svg-md" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
					<?php esc_html_e( 'চেক-ইন সম্পন্ন ও রসিদ প্রিন্ট', 'ifs-parking' ); ?>
				</button>
			</form>
		</div>

		<!-- লাইভ বাংলা রসিদ প্রিভিউ সাইডবার -->
		<div class="ifs-receipt-sidebar">
			<div id="printable-receipt" class="ifs-card ifs-receipt-preview-card">
				<div class="ifs-receipt-head">
					<h3 class="ifs-receipt-main-title"><?php echo esc_html( $site_title ); ?></h3>
					<div class="ifs-receipt-phone-badge">
						<span><?php esc_html_e( 'হেল্পলাইন:', 'ifs-parking' ); ?> <?php echo esc_html( ifs_parking_veh_bn_num( $system_phone ) ); ?></span>
					</div>
					<p class="ifs-receipt-type-tag"><?php esc_html_e( 'অফিসিয়াল পার্কিং এন্ট্রি স্লিপ', 'ifs-parking' ); ?></p>
				</div>

				<div class="ifs-receipt-rows">
					<div class="ifs-receipt-row-item">
						<span class="ifs-rcpt-lbl"><?php esc_html_e( 'ইনভয়েস নং:', 'ifs-parking' ); ?></span>
						<span id="p-invoice" class="ifs-rcpt-val-bold"><?php echo esc_html( $preview_invoice ); ?></span>
					</div>
					<div class="ifs-receipt-row-item">
						<span class="ifs-rcpt-lbl"><?php esc_html_e( 'প্রবেশের সময়:', 'ifs-parking' ); ?></span>
						<span id="p-time" class="ifs-rcpt-val-bold"><?php echo esc_html( ifs_parking_veh_bn_num( date_i18n( 'j M, Y - h:i A', $now_ts ) ) ); ?></span>
					</div>
					<div class="ifs-receipt-row-item">
						<span class="ifs-rcpt-lbl"><?php esc_html_e( 'গাড়ি নম্বর:', 'ifs-parking' ); ?></span>
						<span id="p-veh" class="ifs-rcpt-val-veh">-</span>
					</div>
					<div class="ifs-receipt-row-item">
						<span class="ifs-rcpt-lbl"><?php esc_html_e( 'ডিপার্টমেন্ট:', 'ifs-parking' ); ?></span>
						<span id="p-dept" class="ifs-rcpt-val-dept">-</span>
					</div>
					<div class="ifs-receipt-row-item">
						<span class="ifs-rcpt-lbl"><?php esc_html_e( 'গাড়ির ধরণ:', 'ifs-parking' ); ?></span>
						<span id="p-vehtype" class="ifs-rcpt-val-type">-</span>
					</div>
					<div class="ifs-receipt-highlight-box">
						<span class="ifs-rcpt-rate-lbl"><?php esc_html_e( 'নির্ধারিত রেট (প্রতি ২ ঘণ্টা):', 'ifs-parking' ); ?></span>
						<span id="p-rate" class="ifs-rcpt-rate-val">-</span>
					</div>
				</div>

				<div class="ifs-no-print ifs-receipt-foot-btn" style="margin-top: 14px;">
					<button type="button" onclick="window.print()" class="ifs-btn-print-dark">
						<?php esc_html_e( 'রসিদ প্রিন্ট করুন', 'ifs-parking' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		var categoriesData = <?php echo wp_json_encode( $all_categories ); ?>;
		var vehLabelsBD    = <?php echo wp_json_encode( $veh_labels_bd ); ?>;
		var currencySymbol = <?php echo wp_json_encode( $currency_symbol ); ?>;

		// বিআরটিএ (BRTA) স্ট্যান্ডার্ড অনুযায়ী গাড়ির ধরণ -> অটো সিরিজ বর্ণ ম্যাপিং
		var brtaAutoLetterMap = {
			'Car': 'গ',
			'Noah': 'চ',
			'Hiace': 'চ',
			'CNG': 'থ',
			'Truck': 'ট',
			'Bike': 'ল',
			'Cycle': ''
		};

		var fDiv        = document.getElementById('f-div');
		var fBrtaLetter = document.getElementById('f-brta-letter');
		var fNum        = document.getElementById('f-num');
		var pkgContainer   = document.getElementById('ifs-package-panes-container');
		var hiddenCatId    = document.getElementById('ifs-hidden-cat-id');
		var hiddenRateType = document.getElementById('ifs-hidden-rate-type');
		var conditionalBox = document.getElementById('ifs-conditional-id-box');
		var idLabel        = document.getElementById('ifs-id-label');
		var idInput        = document.getElementById('ifs-gym-emp-id-input');

		var bnMap = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
		function toBnDigits(str) {
			return String(str).replace(/[0-9]/g, function(w) { return bnMap[+w]; });
		}

		// ঐচ্ছিক ফিল্ড টগল (Name/Phone/NID)
		var optBtn = document.getElementById('ifs-toggle-optional-btn');
		var optBox = document.getElementById('ifs-optional-fields-box');
		if (optBtn && optBox) {
			optBtn.addEventListener('click', function() {
				optBox.style.display = (optBox.style.display === 'none') ? 'block' : 'none';
			});
		}

		function getSelectedVehType() {
			var checked = document.querySelector('.ifs-quick-veh-selector:checked');
			return checked ? checked.value : 'Car';
		}

		function getSelectedDepartment() {
			var checked = document.querySelector('.ifs-dept-selector:checked');
			return checked ? checked.value : 'General';
		}

		function syncBrtaLetterByVehicle() {
			var vType = getSelectedVehType();
			if (fBrtaLetter && Object.prototype.hasOwnProperty.call(brtaAutoLetterMap, vType)) {
				fBrtaLetter.value = brtaAutoLetterMap[vType];
			}
			updatePackages();
		}

		function updatePackages() {
			var currentDept = getSelectedDepartment();
			var currentVeh  = getSelectedVehType();

			if (currentDept === 'Gym' || currentDept === 'Staff' || currentDept === 'Employee') {
				conditionalBox.style.display = 'block';
				idLabel.textContent = currentDept + ' আইডি নম্বর *';
				idInput.setAttribute('required', 'required');
			} else {
				conditionalBox.style.display = 'none';
				idInput.removeAttribute('required');
				idInput.value = '';
			}

			pkgContainer.innerHTML = '';

			var matchingCat = categoriesData.find(function(cat) {
				return cat.department === currentDept && cat.vehicle_type === currentVeh;
			}) || categoriesData.find(function(cat) {
				return cat.vehicle_type === currentVeh;
			});

			if (!matchingCat) {
				hiddenCatId.value = '';
				updateReceiptPreview();
				return;
			}

			hiddenCatId.value = matchingCat.id;

			var options = [
				{ type: 'hourly', label: 'প্রতি ২ ঘণ্টা স্ল্যাব', rate: matchingCat.hourly_rate },
				{ type: 'daily', label: 'দৈনিক প্যাকেজ', rate: matchingCat.daily_rate },
				{ type: 'monthly', label: 'মাসিক প্যাকেজ', rate: matchingCat.monthly_rate }
			];

			options.forEach(function(opt, idx) {
				var pane = document.createElement('label');
				pane.className = 'ifs-pkg-card-pane';

				var radio = document.createElement('input');
				radio.type = 'radio';
				radio.name = 'pkg_radio';
				radio.className = 'ifs-pkg-selector';
				radio.value = opt.type;
				if (idx === 0) radio.checked = true;

				var titleSpan = document.createElement('strong');
				titleSpan.style.fontSize = '13px';
				titleSpan.textContent = opt.label;

				var rateSpan = document.createElement('span');
				rateSpan.style.cssText = 'color: #0284c7; font-weight: 700; font-size: 14px;';
				rateSpan.textContent = currencySymbol + toBnDigits(parseFloat(opt.rate).toFixed(2));

				pane.appendChild(radio);
				pane.appendChild(titleSpan);
				pane.appendChild(rateSpan);
				pkgContainer.appendChild(pane);
			});

			updateReceiptPreview();
		}

		function updateReceiptPreview() {
			var div    = fDiv ? fDiv.value : '';
			var letter = fBrtaLetter ? fBrtaLetter.value : '';
			var num    = fNum ? toBnDigits(fNum.value.trim()) : '';
			var fullVeh = [div, letter, num].filter(Boolean).join(' ');

			document.getElementById('p-veh').textContent     = fullVeh || '-';
			document.getElementById('p-dept').textContent    = getSelectedDepartment();
			document.getElementById('p-vehtype').textContent = vehLabelsBD[getSelectedVehType()] || getSelectedVehType();

			var selectedPkg = document.querySelector('.ifs-pkg-selector:checked');
			var rateType    = selectedPkg ? selectedPkg.value : 'hourly';
			hiddenRateType.value = rateType;

			var currentCat = categoriesData.find(function(c) { return String(c.id) === String(hiddenCatId.value); });
			if (currentCat) {
				var rateVal = rateType === 'daily' ? currentCat.daily_rate : (rateType === 'monthly' ? currentCat.monthly_rate : currentCat.hourly_rate);
				document.getElementById('p-rate').textContent = currencySymbol + toBnDigits(parseFloat(rateVal).toFixed(2));
			}
		}

		document.querySelectorAll('.ifs-quick-veh-selector').forEach(function(r) {
			r.addEventListener('change', syncBrtaLetterByVehicle);
		});
		document.querySelectorAll('.ifs-dept-selector').forEach(function(r) {
			r.addEventListener('change', updatePackages);
		});
		document.addEventListener('change', function(e) {
			if (e.target.classList.contains('ifs-pkg-selector')) updateReceiptPreview();
		});
		[fDiv, fBrtaLetter, fNum].forEach(function(el) {
			if (el) el.addEventListener('input', updateReceiptPreview);
		});

		syncBrtaLetterByVehicle();
	});
	</script>
	<?php
}