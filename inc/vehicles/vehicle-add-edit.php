<?php
/**
 * Vehicle Add/Edit Form & Save Handler.
 *
 * @package Ifs_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * নতুন গাড়ি চেক-ইন ডাটা সেভ হ্যান্ডলার
 *
 * @param string $table_logs লগ টেবিল নাম।
 * @param string $table_cats ক্যাটাগরি টেবিল নাম।
 */
function ifs_parking_save_vehicle_entry( $table_logs, $table_cats ) {
	global $wpdb;

	$div_val        = isset( $_POST['veh_div'] ) ? sanitize_text_field( wp_unslash( $_POST['veh_div'] ) ) : '';
	$char_val       = isset( $_POST['veh_char'] ) ? sanitize_text_field( wp_unslash( $_POST['veh_char'] ) ) : '';
	$num_val        = isset( $_POST['veh_num'] ) ? sanitize_text_field( wp_unslash( $_POST['veh_num'] ) ) : '';
	$vehicle_number = trim( $div_val . ' ' . $char_val . ' ' . $num_val );

	$cat_id_selected = isset( $_POST['category_id'] ) ? intval( $_POST['category_id'] ) : 0;
	$billing_type    = isset( $_POST['selected_rate_type'] ) ? sanitize_text_field( wp_unslash( $_POST['selected_rate_type'] ) ) : 'hourly';
	$selected_cat    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_cats WHERE id = %d", $cat_id_selected ) );

	$fee_rate = 0.00;
	if ( $selected_cat ) {
		if ( 'daily' === $billing_type ) {
			$fee_rate = floatval( $selected_cat->daily_rate );
		} elseif ( 'monthly' === $billing_type ) {
			$fee_rate = floatval( $selected_cat->monthly_rate );
		} else {
			$fee_rate = floatval( $selected_cat->hourly_rate );
		}
	}

	$parking_type   = $selected_cat ? $selected_cat->category_slug : 'general';
	$driver_name    = isset( $_POST['driver_name'] ) ? sanitize_text_field( wp_unslash( $_POST['driver_name'] ) ) : '';
	$driver_phone   = isset( $_POST['driver_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['driver_phone'] ) ) : '';
	$membership_exp = ! empty( $_POST['membership_expiry'] ) ? sanitize_text_field( wp_unslash( $_POST['membership_expiry'] ) ) : null;

	$nid_path = '';
	if ( ! empty( $_FILES['driver_nid']['name'] ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$uploaded = wp_handle_upload( $_FILES['driver_nid'], array( 'test_form' => false ) );
		if ( isset( $uploaded['url'] ) ) {
			$nid_path = $uploaded['url'];
		}
	}

	if ( ! empty( $num_val ) && $selected_cat ) {
		$wpdb->insert(
			$table_logs,
			array(
				'vehicle_number'    => $vehicle_number,
				'parking_type'      => $parking_type,
				'slot_number'       => '',
				'reference_id'      => $selected_cat->department,
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

		$new_insert_id = $wpdb->insert_id;
		wp_redirect( admin_url( 'admin.php?page=ifs-parking-vehicles&tab=all_vehicles&auto_print=' . $new_insert_id . '&saved=1' ) );
		exit;
	} else {
		echo '<div class="notice notice-error is-dismissible ifs-custom-notice-err">
				<p class="ifs-notice-err-text">
					<svg class="ifs-notice-svg-err" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
					' . esc_html__( 'দয়া করে গাড়ি নম্বর এবং প্যাকেজ/রেট সঠিকভাবে নির্বাচন করুন।', 'ifs-parking' ) . '
				</p>
			  </div>';
	}
}

/**
 * চেক-ইন ফরম ও রসিদ প্রিভিউ রেন্ডারার
 *
 * @param string $table_cats       ক্যাটাগরি টেবিল নাম।
 * @param int    $log_id           লগ আইডি।
 * @param string $action           অ্যাকশন।
 * @param string $site_title       সাইট টাইটেল।
 * @param string $system_phone     ফোন নম্বর।
 * @param string $currency_symbol  কারেন্সি প্রতীক।
 */
function ifs_parking_render_vehicle_form( $table_cats, $log_id, $action, $site_title, $system_phone, $currency_symbol ) {
	global $wpdb;
	$all_categories = $wpdb->get_results( "SELECT * FROM $table_cats ORDER BY id DESC" );
	$departments    = array_unique( wp_list_pluck( $all_categories, 'department' ) );

	$veh_labels_bd = array(
		'Car'   => __( 'কার (Car)', 'ifs-parking' ),
		'Noah'  => __( 'নোয়াহ (Noah)', 'ifs-parking' ),
		'Hiace' => __( 'হাইয়েস (Hiace)', 'ifs-parking' ),
		'CNG'   => __( 'সিএনজি (CNG)', 'ifs-parking' ),
		'Truck' => __( 'ট্রাক (Truck)', 'ifs-parking' ),
		'Bike'  => __( 'বাইক (Bike)', 'ifs-parking' ),
		'Cycle' => __( 'সাইকেল (Cycle)', 'ifs-parking' ),
	);
	?>
	<div class="ifs-entry-container">
		<!-- চেক-ইন ফরম -->
		<div class="ifs-card ifs-form-card">
			<h3 class="ifs-form-heading">
				<svg class="ifs-svg-primary" viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z"/></svg>
				<?php esc_html_e( 'নতুন গাড়ি এন্ট্রি ফরম', 'ifs-parking' ); ?>
			</h3>
			
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-vehicles&tab=add_vehicle' ) ); ?>" enctype="multipart/form-data" id="ifs-live-form">
				<?php wp_nonce_field( 'ifs_save_vehicle', 'ifs_vehicle_nonce' ); ?>

				<input type="hidden" name="category_id" id="ifs-hidden-cat-id" value="">
				<input type="hidden" name="selected_rate_type" id="ifs-hidden-rate-type" value="hourly">

				<!-- গাড়ি নম্বর প্লেট -->
				<div class="ifs-form-field-group">
					<label class="ifs-form-label"><?php esc_html_e( 'গাড়ি নম্বর প্লেট *', 'ifs-parking' ); ?></label>
					<div class="ifs-plate-grid">
						<select name="veh_div" id="f-div" class="ifs-veh-input" required>
							<option value=""><?php esc_html_e( 'বিভাগ', 'ifs-parking' ); ?></option>
							<option value="ঢাকা মেট্রো"><?php esc_html_e( 'ঢাকা মেট্রো', 'ifs-parking' ); ?></option>
							<option value="চট্ট মেট্রো"><?php esc_html_e( 'চট্ট মেট্রো', 'ifs-parking' ); ?></option>
							<option value="সিলেট মেট্রো"><?php esc_html_e( 'সিলেট মেট্রো', 'ifs-parking' ); ?></option>
							<option value="রাজশাহী মেট্রো"><?php esc_html_e( 'রাজশাহী মেট্রো', 'ifs-parking' ); ?></option>
							<option value="খুলনা মেট্রো"><?php esc_html_e( 'খুলনা মেট্রো', 'ifs-parking' ); ?></option>
							<option value="বরিশাল মেট্রো"><?php esc_html_e( 'বরিশাল মেট্রো', 'ifs-parking' ); ?></option>
							<option value="রংপুর মেট্রো"><?php esc_html_e( 'রংপুর মেট্রো', 'ifs-parking' ); ?></option>
						</select>
						<select name="veh_char" id="f-char" class="ifs-veh-input" required>
							<option value=""><?php esc_html_e( 'বর্ণ', 'ifs-parking' ); ?></option>
							<option value="ক">ক</option>
							<option value="খ">খ</option>
							<option value="গ">গ</option>
							<option value="ঘ">ঘ</option>
							<option value="চ">চ</option>
							<option value="ছ">ছ</option>
							<option value="ট">ট</option>
							<option value="হ">হ</option>
							<option value="ল">ল</option>
						</select>
						<input type="text" name="veh_num" id="f-num" class="ifs-veh-input" required placeholder="১১-১২৩৪">
					</div>
				</div>

				<!-- ১. ডিপার্টমেন্ট রেডিও গ্রুপ -->
				<div class="ifs-step-box">
					<h4 class="ifs-step-title"><span class="ifs-step-num">১</span> <?php esc_html_e( 'ডিপার্টমেন্ট নির্বাচন করুন *', 'ifs-parking' ); ?></h4>
					<div class="ifs-radio-pill-group">
						<?php if ( ! empty( $departments ) ) : ?>
							<?php foreach ( $departments as $d_idx => $dept_name ) : ?>
								<label class="ifs-radio-pill">
									<input type="radio" name="dept_radio" class="ifs-dept-selector" value="<?php echo esc_attr( $dept_name ); ?>" <?php echo 0 === $d_idx ? 'checked' : ''; ?>>
									<span class="ifs-pill-label">
										<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
										<?php echo esc_html( $dept_name ); ?>
									</span>
								</label>
							<?php endforeach; ?>
						<?php else : ?>
							<p class="ifs-txt-err"><?php esc_html_e( 'কোনো ডিপার্টমেন্ট পাওয়া যায়নি। ক্যাটাগরি তৈরি করুন।', 'ifs-parking' ); ?></p>
						<?php endif; ?>
					</div>
				</div>

				<!-- ২. গাড়ির ধরণ রেডিও গ্রুপ -->
				<div class="ifs-step-box">
					<h4 class="ifs-step-title"><span class="ifs-step-num">২</span> <?php esc_html_e( 'গাড়ির ধরণ নির্বাচন করুন *', 'ifs-parking' ); ?></h4>
					<div class="ifs-radio-pill-group" id="ifs-veh-types-container"></div>
				</div>

				<!-- ৩. প্যাকেজ ও রেট সিলেকশন -->
				<div class="ifs-step-box ifs-step-pkg">
					<div class="ifs-step-header">
						<h4 class="ifs-step-title ifs-step-title-no-m"><span class="ifs-step-num">৩</span> <?php esc_html_e( 'প্যাকেজ ও ৩ কলাম বিলিং সাইকেল (১টি নির্বাচন করুন) *', 'ifs-parking' ); ?></h4>
						<span class="ifs-pill-tag"><?php esc_html_e( '৩ কলাম ভিউ', 'ifs-parking' ); ?></span>
					</div>
					
					<div class="ifs-subtabs-nav" id="ifs-package-tabs-nav"></div>
					<div id="ifs-package-panes-container"></div>
				</div>

				<!-- চালক ও ফোন -->
				<div class="ifs-form-2col">
					<div>
						<label class="ifs-form-label-sm"><?php esc_html_e( 'চালকের নাম', 'ifs-parking' ); ?></label>
						<input type="text" name="driver_name" id="f-name" class="ifs-veh-input" placeholder="<?php esc_attr_e( 'পুরো নাম', 'ifs-parking' ); ?>">
					</div>
					<div>
						<label class="ifs-form-label-sm"><?php esc_html_e( 'চালকের ফোন', 'ifs-parking' ); ?></label>
						<input type="text" name="driver_phone" id="f-phone" class="ifs-veh-input" placeholder="০১XXXXXXXXX">
					</div>
				</div>

				<!-- মেম্বারশিপ মেয়াদ ও এনআইডি -->
				<div class="ifs-form-2col ifs-form-mb-large">
					<div>
						<label class="ifs-form-label-sm"><?php esc_html_e( 'মেম্বারশিপ মেয়াদ (প্রযোজ্য ক্ষেত্রে)', 'ifs-parking' ); ?></label>
						<input type="date" name="membership_expiry" class="ifs-veh-input">
					</div>
					<div>
						<label class="ifs-form-label-sm"><?php esc_html_e( 'NID আপলোড', 'ifs-parking' ); ?></label>
						<input type="file" name="driver_nid" accept="image/*,.pdf" class="ifs-file-input">
					</div>
				</div>

				<button type="submit" name="submit_vehicle" class="ifs-btn-submit">
					<svg class="ifs-svg-md" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
					<?php esc_html_e( 'গাড়ি চেক-ইন ও অটো রসিদ প্রিন্ট', 'ifs-parking' ); ?>
				</button>
			</form>
		</div>

		<!-- লাইভ রসিদ প্রিভিউ সাইডবার -->
		<div class="ifs-receipt-sidebar">
			<div class="ifs-no-print ifs-btn-print-wrapper">
				<button type="button" onclick="window.print()" class="ifs-btn-print-full">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
					<?php esc_html_e( 'অফিসিয়াল এন্ট্রি স্লিপ ও রসিদ প্রিন্ট', 'ifs-parking' ); ?>
				</button>
			</div>

			<div id="printable-receipt" class="ifs-card ifs-receipt-preview-card">
				<div class="ifs-receipt-head">
					<h3 class="ifs-receipt-main-title"><?php echo esc_html( $site_title ); ?></h3>
					<div class="ifs-receipt-phone-badge">
						<svg class="ifs-svg-xxs" viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
						<span><?php esc_html_e( 'হেল্পলাইন:', 'ifs-parking' ); ?> <?php echo esc_html( $system_phone ); ?></span>
					</div>
					<p class="ifs-receipt-type-tag"><?php esc_html_e( 'অফিসিয়াল এন্ট্রি স্লিপ ও রসিদ', 'ifs-parking' ); ?></p>
				</div>

				<div class="ifs-receipt-rows">
					<div class="ifs-receipt-row-item">
						<span class="ifs-rcpt-lbl"><?php esc_html_e( 'প্রবেশের সময়:', 'ifs-parking' ); ?></span>
						<span id="p-time" class="ifs-rcpt-val-bold"><?php echo esc_html( current_time( 'd M, Y - h:i A' ) ); ?></span>
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
						<span class="ifs-rcpt-lbl"><?php esc_html_e( 'ক্যাটাগরি / প্যাকেজ:', 'ifs-parking' ); ?></span>
						<span id="p-catname" class="ifs-rcpt-val-bold">-</span>
					</div>
					<div class="ifs-receipt-row-item">
						<span class="ifs-rcpt-lbl"><?php esc_html_e( 'গাড়ির ধরণ:', 'ifs-parking' ); ?></span>
						<span id="p-vehtype" class="ifs-rcpt-val-type">-</span>
					</div>

					<div class="ifs-receipt-highlight-box">
						<span class="ifs-rcpt-rate-lbl"><?php esc_html_e( 'নির্ধারিত ফি রেট:', 'ifs-parking' ); ?></span>
						<span id="p-rate" class="ifs-rcpt-rate-val">-</span>
					</div>

					<div class="ifs-receipt-row-item ifs-rcpt-mt">
						<span class="ifs-rcpt-lbl"><?php esc_html_e( 'চালকের নাম:', 'ifs-parking' ); ?></span>
						<span id="p-name" class="ifs-rcpt-val-txt">-</span>
					</div>
					<div class="ifs-receipt-row-item">
						<span class="ifs-rcpt-lbl"><?php esc_html_e( 'চালকের ফোন:', 'ifs-parking' ); ?></span>
						<span id="p-phone" class="ifs-rcpt-val-txt">-</span>
					</div>
				</div>

				<div class="ifs-receipt-rules">
					<strong class="ifs-rules-title"><?php esc_html_e( 'পার্কিং নির্দেশিকা ও নিয়মাবলী:', 'ifs-parking' ); ?></strong>
					<ol class="ifs-receipt-rules-list">
						<li><?php esc_html_e( 'গাড়ি পার্কিং থেকে বের করার সময় এই রসিদ স্লিপটি অবশ্যই প্রদর্শন করতে হবে।', 'ifs-parking' ); ?></li>
						<li><?php esc_html_e( 'পার্কিং প্রাঙ্গণে গাড়ির গতিসীমা সর্বোচ্চ ১০ কিমি/ঘণ্টা বজায় রাখুন।', 'ifs-parking' ); ?></li>
						<li><?php esc_html_e( 'গাড়ির ভেতরে কোনো মূল্যবান বা ঝুঁকিপূর্ণ সামগ্রী নিজ দায়িত্বে রাখবেন না।', 'ifs-parking' ); ?></li>
						<li><?php esc_html_e( 'স্লিপটি হারিয়ে গেলে অতিরিক্ত জরিমানা ও যাচাইকরণ প্রযোজ্য হবে।', 'ifs-parking' ); ?></li>
						<li><?php esc_html_e( 'জরুরি প্রয়োজনে উপরোক্ত হেল্পলাইনে বা নিয়ন্ত্রণ কক্ষে যোগাযোগ করুন।', 'ifs-parking' ); ?></li>
					</ol>
				</div>

				<div class="ifs-no-print ifs-receipt-foot-btn">
					<button type="button" onclick="window.print()" class="ifs-btn-print-dark">
						<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
						<?php esc_html_e( 'রসিদ প্রিন্ট করুন', 'ifs-parking' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
	<?php
}