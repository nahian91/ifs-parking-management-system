<?php
/**
 * Categories Add & Edit Template (Repeater Support for Edit).
 *
 * @package Ifs_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ডিপার্টমেন্টের সকল রিপিটার রো সংরক্ষণ ও আপডেট হ্যান্ডলার
 */
function ifs_parking_save_category( $table_cats, $orig_dept = '' ) {
	global $wpdb;

	$department     = isset( $_POST['department'] ) ? sanitize_text_field( wp_unslash( $_POST['department'] ) ) : 'General';
	$customer_types = isset( $_POST['customer_type'] ) && is_array( $_POST['customer_type'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['customer_type'] ) ) : array();
	$vehicle_types  = isset( $_POST['vehicle_type'] ) && is_array( $_POST['vehicle_type'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['vehicle_type'] ) ) : array();
	$hourly_rates   = isset( $_POST['hourly_rate'] ) && is_array( $_POST['hourly_rate'] ) ? array_map( 'floatval', wp_unslash( $_POST['hourly_rate'] ) ) : array();
	$daily_rates    = isset( $_POST['daily_rate'] ) && is_array( $_POST['daily_rate'] ) ? array_map( 'floatval', wp_unslash( $_POST['daily_rate'] ) ) : array();
	$monthly_rates  = isset( $_POST['monthly_rate'] ) && is_array( $_POST['monthly_rate'] ) ? array_map( 'floatval', wp_unslash( $_POST['monthly_rate'] ) ) : array();
	$free_hours_arr = isset( $_POST['free_hours'] ) && is_array( $_POST['free_hours'] ) ? array_map( 'intval', wp_unslash( $_POST['free_hours'] ) ) : array();

	if ( ! empty( $customer_types ) ) {
		// এডিট মোড হলে আগের ডিপার্টমেন্টের রেকর্ড রিপ্লেস করা
		if ( ! empty( $orig_dept ) ) {
			$wpdb->delete( $table_cats, array( 'department' => $orig_dept ), array( '%s' ) );
		}

		foreach ( $customer_types as $index => $customer_type ) {
			$vehicle_type = isset( $vehicle_types[ $index ] ) ? $vehicle_types[ $index ] : 'Car';
			$cat_name     = trim( $vehicle_type . ' - ' . $customer_type );
			$cat_slug     = sanitize_title( $cat_name );

			if ( ! empty( $customer_type ) ) {
				$wpdb->insert(
					$table_cats,
					array(
						'department'    => $department,
						'category_slug' => $cat_slug,
						'category_name' => $cat_name,
						'vehicle_type'  => $vehicle_type,
						'customer_type' => $customer_type,
						'hourly_rate'   => isset( $hourly_rates[ $index ] ) ? $hourly_rates[ $index ] : 0.00,
						'daily_rate'    => isset( $daily_rates[ $index ] ) ? $daily_rates[ $index ] : 0.00,
						'monthly_rate'  => isset( $monthly_rates[ $index ] ) ? $monthly_rates[ $index ] : 0.00,
						'free_hours'    => isset( $free_hours_arr[ $index ] ) ? $free_hours_arr[ $index ] : 0,
					),
					array( '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%d' )
				);
			}
		}

		echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
				<p class="ifs-notice-text">
					<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
					' . esc_html__( 'ডিপার্টমেন্ট এবং সকল প্যাকেজ রো সফলভাবে সংরক্ষিত হয়েছে!', 'ifs-parking' ) . '
				</p>
			  </div>';
	}
}

/**
 * ক্যাটাগরি ফর্ম রেন্ডারার
 */
function ifs_parking_render_category_form( $table_cats, $orig_dept, $action, $currency_setting, $currency_symbol ) {
	global $wpdb;

	$edit_rows = array();
	if ( 'edit' === $action && ! empty( $orig_dept ) ) {
		$edit_rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_cats WHERE department = %s ORDER BY id ASC", $orig_dept ) );
	}

	$current_dept_name = ! empty( $orig_dept ) ? $orig_dept : 'General';
	$is_edit           = ( 'edit' === $action && ! empty( $edit_rows ) );
	?>
	<div class="ifs-card ifs-form-card">
		<div class="ifs-card-header-bordered">
			<div>
				<h3 class="ifs-card-title-lg">
					<svg class="ifs-svg-primary" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
					<?php echo $is_edit ? esc_html__( 'ডিপার্টমেন্ট ও প্যাকেজসমূহ সম্পাদনা করুন', 'ifs-parking' ) : esc_html__( 'নতুন ডিপার্টমেন্ট ও প্যাকেজ যোগ করুন', 'ifs-parking' ); ?>
				</h3>
				<p class="ifs-card-subtitle"><?php esc_html_e( 'একই ডিপার্টমেন্টের অধীনে একাধিক গাড়ি ও গ্রাহকের ধরণের জন্য রেট কনফিগার করুন।', 'ifs-parking' ); ?></p>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-categories' ) ); ?>" class="ifs-btn-back">
				<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
				<?php esc_html_e( 'তালিকায় ফিরে যান', 'ifs-parking' ); ?>
			</a>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-categories' ) ); ?>">
			<?php wp_nonce_field( 'ifs_save_category', 'ifs_cat_nonce' ); ?>
			<?php if ( $is_edit ) : ?>
				<input type="hidden" name="orig_dept" value="<?php echo esc_attr( $orig_dept ); ?>">
			<?php endif; ?>

			<!-- ডিপার্টমেন্ট বক্স -->
			<div class="ifs-dept-box">
				<label class="ifs-field-label ifs-field-label-dept">
					<svg viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
					<?php esc_html_e( 'ডিপার্টমেন্টের নাম (Department)', 'ifs-parking' ); ?> <span class="ifs-required">*</span>
				</label>
				<input type="text" name="department" required placeholder="<?php esc_attr_e( 'যেমন: সিকিউরিটি / অপারেশনস', 'ifs-parking' ); ?>" value="<?php echo esc_attr( $current_dept_name ); ?>" class="ifs-input-field">
			</div>

			<div class="ifs-section-subhead">
				<h4 class="ifs-subhead-title">
					<svg class="ifs-svg-primary" viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
					<?php esc_html_e( 'প্যাকেজ ও রেট কনফিগারেশন (রিপিটার রো)', 'ifs-parking' ); ?>
				</h4>
				<span class="ifs-badge-currency">
					<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
					<?php echo esc_html( $currency_setting ); ?>
				</span>
			</div>

			<!-- রিপিটার রো কন্টেইনার -->
			<div id="ifs-repeater-container">
				<?php
				$rows_to_render = ! empty( $edit_rows ) ? $edit_rows : array( (object) array( 'customer_type' => 'General', 'vehicle_type' => 'Car', 'hourly_rate' => '0.00', 'daily_rate' => '0.00', 'monthly_rate' => '0.00', 'free_hours' => 0 ) );

				foreach ( $rows_to_render as $r ) :
					$v_type = isset( $r->vehicle_type ) ? $r->vehicle_type : 'Car';
				?>
					<div class="ifs-repeater-row">
						<div>
							<label class="ifs-field-label">
								<svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
								<?php esc_html_e( 'গ্রাহকের ধরণ *', 'ifs-parking' ); ?>
							</label>
							<input type="text" name="customer_type[]" required placeholder="যেমন: সাধারণ / ভিআইপি" value="<?php echo esc_attr( isset( $r->customer_type ) ? $r->customer_type : 'General' ); ?>" class="ifs-input-field">
						</div>

						<div>
							<label class="ifs-field-label">
								<svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
								<?php esc_html_e( 'গাড়ির ধরণ *', 'ifs-parking' ); ?>
							</label>
							<select name="vehicle_type[]" class="ifs-input-field">
								<option value="Car" <?php selected( $v_type, 'Car' ); ?>>কার (Car)</option>
								<option value="Noah" <?php selected( $v_type, 'Noah' ); ?>>নোয়াহ (Noah)</option>
								<option value="Hiace" <?php selected( $v_type, 'Hiace' ); ?>>হাইয়েস (Hiace)</option>
								<option value="CNG" <?php selected( $v_type, 'CNG' ); ?>>সিএনজি (CNG)</option>
								<option value="Truck" <?php selected( $v_type, 'Truck' ); ?>>ট্রাক (Truck)</option>
								<option value="Bike" <?php selected( $v_type, 'Bike' ); ?>>বাইক (Bike)</option>
								<option value="Cycle" <?php selected( $v_type, 'Cycle' ); ?>>সাইকেল (Cycle)</option>
							</select>
						</div>

						<div>
							<label class="ifs-field-label">
								<svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
								<?php echo esc_html( sprintf( __( 'ঘণ্টা (%s)', 'ifs-parking' ), $currency_symbol ) ); ?>
							</label>
							<input type="number" step="0.01" name="hourly_rate[]" value="<?php echo esc_attr( isset( $r->hourly_rate ) ? $r->hourly_rate : '0.00' ); ?>" class="ifs-input-field">
						</div>

						<div>
							<label class="ifs-field-label">
								<svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
								<?php echo esc_html( sprintf( __( 'দৈনিক (%s)', 'ifs-parking' ), $currency_symbol ) ); ?>
							</label>
							<input type="number" step="0.01" name="daily_rate[]" value="<?php echo esc_attr( isset( $r->daily_rate ) ? $r->daily_rate : '0.00' ); ?>" class="ifs-input-field">
						</div>

						<div>
							<label class="ifs-field-label">
								<svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
								<?php echo esc_html( sprintf( __( 'মাসিক (%s)', 'ifs-parking' ), $currency_symbol ) ); ?>
							</label>
							<input type="number" step="0.01" name="monthly_rate[]" value="<?php echo esc_attr( isset( $r->monthly_rate ) ? $r->monthly_rate : '0.00' ); ?>" class="ifs-input-field">
						</div>

						<div>
							<label class="ifs-field-label">
								<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
								<?php esc_html_e( 'ফ্রি ঘণ্টা', 'ifs-parking' ); ?>
							</label>
							<input type="number" name="free_hours[]" value="<?php echo esc_attr( isset( $r->free_hours ) ? $r->free_hours : '0' ); ?>" class="ifs-input-field">
						</div>

						<div class="ifs-repeater-action-col">
							<button type="button" class="ifs-btn-remove ifs-remove-row" title="<?php esc_attr_e( 'রো মুছে ফেলুন', 'ifs-parking' ); ?>">
								<svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
							</button>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<button type="button" id="ifs-add-row" class="ifs-btn-add">
				<svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
				<?php esc_html_e( 'আরেকটি প্যাকেজ রো যোগ করুন', 'ifs-parking' ); ?>
			</button>

			<div class="ifs-form-submit-row">
				<button type="submit" name="submit_cat" class="ifs-btn-submit-cat">
					<svg class="ifs-svg-md" viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
					<?php echo $is_edit ? esc_html__( 'সকল পরিবর্তন সংরক্ষণ করুন', 'ifs-parking' ) : esc_html__( 'সকল প্যাকেজ সংরক্ষণ করুন', 'ifs-parking' ); ?>
				</button>
			</div>
		</form>
	</div>
	<?php
}