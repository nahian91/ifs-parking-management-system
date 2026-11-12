<?php
/**
 * Staff & Operator Management Module.
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
function ifs_parking_staff_bn_num( $number ) {
	if ( function_exists( 'ifs_parking_to_bn_number' ) ) {
		return ifs_parking_to_bn_number( $number );
	}

	$en_digits = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	$bn_digits = array( '০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯' );

	return str_replace( $en_digits, $bn_digits, (string) $number );
}

/**
 * স্টাফ পেজ রেন্ডারার।
 *
 * @return void
 */
function ifs_parking_render_staff_page() {
	if ( function_exists( 'ifs_parking_render_layout_wrapper' ) ) {
		ifs_parking_render_layout_wrapper( 'ifs_parking_render_staff_content' );
	} else {
		ifs_parking_render_staff_content();
	}
}

/**
 * মূল স্টাফ কনটেন্ট রেন্ডারার।
 *
 * @return void
 */
function ifs_parking_render_staff_content() {
	global $wpdb;
	$table_staff = $wpdb->prefix . 'ifs_parking_staff';

	// টেবিল বিদ্যমান আছে কি না যাচাই করা।
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$has_staff_table = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_staff ) ) === $table_staff );

	// কারেন্সি সিম্বল ডায়নামিক ফেচ।
	$currency_setting = get_option( 'ifs_parking_currency', 'BDT (৳)' );
	$currency_symbol  = '৳';

	if ( false !== strpos( $currency_setting, '৳' ) ) {
		$currency_symbol = '৳';
	} elseif ( false !== strpos( $currency_setting, '€' ) ) {
		$currency_symbol = '€';
	} elseif ( false !== strpos( $currency_setting, '£' ) ) {
		$currency_symbol = '£';
	} elseif ( false !== strpos( $currency_setting, '﷼' ) ) {
		$currency_symbol = '﷼';
	} elseif ( false !== strpos( $currency_setting, '$' ) ) {
		$currency_symbol = '$';
	} else {
		$currency_symbol = $currency_setting;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'all_staff';
	$action     = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	$staff_id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	// ডিলিট হ্যান্ডলার (Nonce ও Capability ভেরিফিকেশনসহ)।
	if ( $has_staff_table && 'delete' === $action && $staff_id > 0 && current_user_can( 'read' ) ) {
		check_admin_referer( 'ifs_delete_staff_' . $staff_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$table_staff,
			array( 'id' => $staff_id ),
			array( '%d' )
		);

		echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
				<p class="ifs-notice-text">
					<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
					' . esc_html__( 'স্টাফ সফলভাবে ডিলিট করা হয়েছে!', 'ifs-parking' ) . '
				</p>
			  </div>';
		$active_tab = 'all_staff';
		$action     = '';
	}

	// ফর্ম সাবমিট ও সেভ হ্যান্ডলার (Add & Edit)।
	if (
		$has_staff_table &&
		isset( $_POST['ifs_staff_nonce'] ) &&
		wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ifs_staff_nonce'] ) ), 'ifs_save_staff' ) &&
		current_user_can( 'read' )
	) {
		$staff_name  = isset( $_POST['staff_name'] ) ? sanitize_text_field( wp_unslash( $_POST['staff_name'] ) ) : '';
		$email       = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone       = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$role        = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : 'Operator';
		$salary      = isset( $_POST['salary'] ) ? max( 0, (float) sanitize_text_field( wp_unslash( $_POST['salary'] ) ) ) : 0.00;
		$duty_slot   = isset( $_POST['duty_slot'] ) ? sanitize_text_field( wp_unslash( $_POST['duty_slot'] ) ) : '';
		$profile_img = isset( $_POST['profile_img'] ) ? esc_url_raw( wp_unslash( $_POST['profile_img'] ) ) : '';

		if ( ! empty( $staff_name ) && ! empty( $email ) ) {
			$data = array(
				'staff_name'  => $staff_name,
				'email'       => $email,
				'phone'       => $phone,
				'role'        => $role,
				'salary'      => $salary,
				'duty_slot'   => $duty_slot,
				'profile_img' => $profile_img,
			);

			$formats = array( '%s', '%s', '%s', '%s', '%f', '%s', '%s' );

			if ( $staff_id > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table_staff,
					$data,
					array( 'id' => $staff_id ),
					$formats,
					array( '%d' )
				);
				echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
						<p class="ifs-notice-text">
							<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
							' . esc_html__( 'স্টাফের তথ্য সফলভাবে আপডেট করা হয়েছে!', 'ifs-parking' ) . '
						</p>
					  </div>';
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert(
					$table_staff,
					$data,
					$formats
				);
				echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
						<p class="ifs-notice-text">
							<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
							' . esc_html__( 'নতুন স্টাফ সফলভাবে যুক্ত করা হয়েছে!', 'ifs-parking' ) . '
						</p>
					  </div>';
			}
			$active_tab = 'all_staff';
			$action     = '';
		}
	}

	wp_enqueue_media();

	$role_labels_bd = array(
		'Operator'       => __( 'অপারেটর (Operator)', 'ifs-parking' ),
		'Security Guard' => __( 'সিকিউরিটি গার্ড (Security Guard)', 'ifs-parking' ),
		'Manager'        => __( 'ম্যানেজার (Manager)', 'ifs-parking' ),
		'Admin'          => __( 'সিস্টেম অ্যাডমিন (Admin)', 'ifs-parking' ),
	);
	?>
	<style>
		/* স্টাফ মডিউল রেসপনসিভ অ্যাডাপ্টেশন ও প্রমিত বাংলা টাইপোগ্রাফি */
		.ifs-staff-wrapper {
			margin: 10px 15px 20px 0;
			box-sizing: border-box;
			font-family: 'Hind Siliguri', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
		}
		.ifs-staff-banner {
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 14px;
			margin-bottom: 20px;
		}
		.ifs-tabs {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
			margin-bottom: 18px;
		}
		.ifs-staff-grid {
			display: grid;
			grid-template-columns: repeat(2, 1fr);
			gap: 16px;
		}
		.ifs-staff-grid .ifs-full-col {
			grid-column: 1 / -1;
		}
		.ifs-media-upload-group {
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.ifs-media-upload-group .ifs-veh-input {
			flex: 1;
			margin-bottom: 0 !important;
		}
		.ifs-media-preview-box {
			margin-top: 10px;
		}
		.ifs-staff-avatar-preview {
			max-width: 90px;
			max-height: 90px;
			border-radius: 12px;
			border: 1px solid #e2e8f0;
			object-fit: cover;
			display: block;
		}
		.ifs-table-responsive {
			width: 100%;
			overflow-x: auto;
			-webkit-overflow-scrolling: touch;
		}
		.ifs-custom-data-table {
			min-width: 720px;
		}
		.ifs-staff-profile-header {
			display: flex;
			align-items: center;
			gap: 16px;
			margin-bottom: 20px;
			flex-wrap: wrap;
		}
		.ifs-view-footer {
			display: flex;
			align-items: center;
			gap: 12px;
			margin-top: 20px;
			flex-wrap: wrap;
		}
		.ifs-form-submit-row {
			margin-top: 20px;
			display: flex;
		}

		/* ট্যাবলেট এবং মোবাইল ভিউপোর্ট রুলস */
		@media screen and (max-width: 782px) {
			.ifs-staff-wrapper {
				margin: 10px 5px 20px 0;
			}
			.ifs-staff-banner {
				flex-direction: column;
				align-items: stretch;
			}
			.ifs-btn-primary-action {
				width: 100%;
				justify-content: center;
				box-sizing: border-box;
				height: 44px;
			}
			.ifs-tabs {
				flex-direction: column;
			}
			.ifs-tab-btn {
				width: 100%;
				justify-content: center;
				box-sizing: border-box;
				text-align: center;
			}
			.ifs-staff-grid {
				grid-template-columns: 1fr;
				gap: 14px;
			}
			.ifs-media-upload-group {
				flex-direction: column;
				align-items: stretch;
			}
			.ifs-btn-media-select {
				width: 100%;
				justify-content: center;
				height: 44px;
			}
			.ifs-form-submit-row {
				width: 100%;
			}
			.ifs-btn-submit {
				width: 100%;
				justify-content: center;
				height: 46px;
			}
			.ifs-veh-input,
			.ifs-step-box select {
				font-size: 16px !important;
				height: 44px;
			}
			.ifs-view-footer .ifs-btn-back,
			.ifs-view-footer .ifs-btn-primary-action {
				width: 100%;
				justify-content: center;
			}
			.ifs-staff-profile-table th,
			.ifs-staff-profile-table td {
				display: block;
				width: 100%;
				padding: 6px 0 !important;
			}
		}
	</style>

	<div class="wrap ifs-staff-wrapper">
		<!-- হেডার ব্যানার -->
		<div class="ifs-staff-banner">
			<div>
				<span class="ifs-banner-badge ifs-badge-blue">
					<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
					<?php esc_html_e( 'টিম ও রোল ম্যানেজমেন্ট', 'ifs-parking' ); ?>
				</span>
				<h1 class="ifs-banner-title"><?php esc_html_e( 'স্টাফ ও অপারেটর ব্যবস্থাপনা', 'ifs-parking' ); ?></h1>
				<p class="ifs-banner-subtitle"><?php esc_html_e( 'পার্কিং স্টাফ, সিকিউরিটি গার্ড এবং অপারেটরদের তথ্য ও ডিউটি স্লট ম্যানেজ করুন।', 'ifs-parking' ); ?></p>
			</div>
			<?php if ( 'all_staff' === $active_tab && empty( $action ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-staff&tab=add_staff' ) ); ?>" class="ifs-btn-primary-action">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
					<?php esc_html_e( 'নতুন স্টাফ যোগ করুন', 'ifs-parking' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<!-- ট্যাব নেভিগেশন -->
		<div class="ifs-tabs">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-staff&tab=all_staff' ) ); ?>" class="ifs-tab-btn <?php echo 'all_staff' === $active_tab && empty( $action ) ? 'active' : ''; ?>">
				<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>
				<?php esc_html_e( 'সকল স্টাফ তালিকা', 'ifs-parking' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-staff&tab=add_staff' ) ); ?>" class="ifs-tab-btn <?php echo 'add_staff' === $active_tab || 'edit' === $action ? 'active' : ''; ?>">
				<?php if ( 'edit' === $action ) : ?>
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
					<?php esc_html_e( 'স্টাফ তথ্য সম্পাদনা', 'ifs-parking' ); ?>
				<?php else : ?>
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
					<?php esc_html_e( 'নতুন স্টাফ যোগ করুন', 'ifs-parking' ); ?>
				<?php endif; ?>
			</a>
		</div>

		<?php if ( 'view' === $action && $staff_id > 0 && $has_staff_table ) : ?>
			<!-- ভিউ ডিটেইলস -->
			<?php
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$single = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_staff}` WHERE id = %d", $staff_id ) );
			?>
			<?php if ( $single ) : ?>
				<?php $single_role = isset( $role_labels_bd[ $single->role ] ) ? $role_labels_bd[ $single->role ] : $single->role; ?>
				<div class="ifs-card ifs-view-container ifs-staff-view-card">
					<div class="ifs-staff-profile-header">
						<?php if ( ! empty( $single->profile_img ) ) : ?>
							<img src="<?php echo esc_url( $single->profile_img ); ?>" class="ifs-staff-avatar-lg" alt="<?php echo esc_attr( $single->staff_name ); ?>" />
						<?php else : ?>
							<div class="ifs-staff-avatar-placeholder-lg"><?php echo esc_html( mb_substr( $single->staff_name, 0, 1 ) ); ?></div>
						<?php endif; ?>
						<div>
							<h3 class="ifs-staff-profile-name"><?php echo esc_html( $single->staff_name ); ?></h3>
							<span class="ifs-badge-dept"><?php echo esc_html( $single_role ); ?></span>
						</div>
					</div>

					<table class="form-table ifs-staff-profile-table">
						<tr>
							<th><?php esc_html_e( 'ইমেইল:', 'ifs-parking' ); ?></th>
							<td><strong><?php echo esc_html( $single->email ); ?></strong></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'ফোন নম্বর:', 'ifs-parking' ); ?></th>
							<td><strong><?php echo esc_html( ! empty( $single->phone ) ? ifs_parking_staff_bn_num( $single->phone ) : __( 'তথ্য নেই', 'ifs-parking' ) ); ?></strong></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'মাসিক বেতন:', 'ifs-parking' ); ?></th>
							<td>
								<strong class="ifs-txt-fee-bold">
									<?php echo esc_html( $currency_symbol . ifs_parking_staff_bn_num( number_format_i18n( (float) $single->salary, 2 ) ) ); ?>
								</strong>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'ডিউটি স্লট / গেট:', 'ifs-parking' ); ?></th>
							<td><strong class="ifs-txt-valid"><?php echo esc_html( ! empty( $single->duty_slot ) ? $single->duty_slot : __( 'নির্ধারিত নয়', 'ifs-parking' ) ); ?></strong></td>
						</tr>
					</table>

					<div class="ifs-view-footer">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-staff' ) ); ?>" class="ifs-btn-back">
							<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
							<?php esc_html_e( 'তালিকায় ফিরে যান', 'ifs-parking' ); ?>
						</a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-staff&action=edit&id=' . $single->id ) ); ?>" class="ifs-btn-primary-action">
							<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
							<?php esc_html_e( 'সম্পাদনা করুন', 'ifs-parking' ); ?>
						</a>
					</div>
				</div>
			<?php endif; ?>

		<?php elseif ( 'add_staff' === $active_tab || 'edit' === $action ) : ?>
			<!-- ফর্ম (Add / Edit) -->
			<?php
			$edit_data = null;
			if ( 'edit' === $action && $staff_id > 0 && $has_staff_table ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$edit_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_staff}` WHERE id = %d", $staff_id ) );
			}
			?>
			<div class="ifs-card ifs-form-card ifs-staff-form-card">
				<h3 class="ifs-form-heading">
					<svg class="ifs-svg-primary" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
					<?php echo $edit_data ? esc_html__( 'স্টাফের তথ্য সম্পাদনা করুন', 'ifs-parking' ) : esc_html__( 'নতুন স্টাফ রেজিস্ট্রেশন ফরম', 'ifs-parking' ); ?>
				</h3>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-staff' . ( $edit_data ? '&id=' . $edit_data->id : '' ) ) ); ?>">
					<?php wp_nonce_field( 'ifs_save_staff', 'ifs_staff_nonce' ); ?>

					<div class="ifs-staff-grid">
						<!-- স্টাফের নাম -->
						<div class="ifs-step-box">
							<label class="ifs-form-label" for="ifs-staff-name"><?php esc_html_e( 'স্টাফের পূর্ণ নাম *', 'ifs-parking' ); ?></label>
							<input type="text" id="ifs-staff-name" name="staff_name" required placeholder="<?php esc_attr_e( 'যেমন: মোহাম্মদ রহিম', 'ifs-parking' ); ?>" value="<?php echo $edit_data ? esc_attr( $edit_data->staff_name ) : ''; ?>" class="ifs-veh-input" />
						</div>

						<!-- ইমেইল -->
						<div class="ifs-step-box">
							<label class="ifs-form-label" for="ifs-staff-email"><?php esc_html_e( 'ইমেইল ঠিকানা *', 'ifs-parking' ); ?></label>
							<input type="email" id="ifs-staff-email" name="email" required placeholder="rahim@example.com" value="<?php echo $edit_data ? esc_attr( $edit_data->email ) : ''; ?>" class="ifs-veh-input" />
						</div>

						<!-- ফোন -->
						<div class="ifs-step-box">
							<label class="ifs-form-label" for="ifs-staff-phone"><?php esc_html_e( 'ফোন নম্বর', 'ifs-parking' ); ?></label>
							<input type="text" id="ifs-staff-phone" name="phone" placeholder="<?php esc_attr_e( '০১XXXXXXXXX', 'ifs-parking' ); ?>" value="<?php echo $edit_data ? esc_attr( $edit_data->phone ) : ''; ?>" class="ifs-veh-input" />
						</div>

						<!-- রোল -->
						<div class="ifs-step-box">
							<label class="ifs-form-label" for="ifs-staff-role"><?php esc_html_e( 'পদবী / রোল (Role)', 'ifs-parking' ); ?></label>
							<select id="ifs-staff-role" name="role" class="ifs-veh-input">
								<?php foreach ( $role_labels_bd as $role_val => $role_txt ) : ?>
									<option value="<?php echo esc_attr( $role_val ); ?>" <?php selected( $edit_data ? $edit_data->role : '', $role_val ); ?>>
										<?php echo esc_html( $role_txt ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<!-- স্যালারি -->
						<div class="ifs-step-box">
							<label class="ifs-form-label" for="ifs-staff-salary">
								<?php
								/* translators: %s: কারেন্সি প্রতীক */
								echo esc_html( sprintf( __( 'মাসিক বেতন (%s)', 'ifs-parking' ), $currency_symbol ) );
								?>
							</label>
							<input type="number" id="ifs-staff-salary" min="0" step="0.01" name="salary" placeholder="15000" value="<?php echo $edit_data ? esc_attr( $edit_data->salary ) : '0.00'; ?>" class="ifs-veh-input" />
						</div>

						<!-- ডিউটি স্লট/গেট -->
						<div class="ifs-step-box">
							<label class="ifs-form-label" for="ifs-staff-duty"><?php esc_html_e( 'ডিউটি স্লট / গেট', 'ifs-parking' ); ?></label>
							<input type="text" id="ifs-staff-duty" name="duty_slot" placeholder="<?php esc_attr_e( 'যেমন: গেট-০১ বা জোন এ', 'ifs-parking' ); ?>" value="<?php echo $edit_data ? esc_attr( $edit_data->duty_slot ) : ''; ?>" class="ifs-veh-input" />
						</div>

						<!-- প্রোফাইল ইমেজ আপলোড -->
						<div class="ifs-step-box ifs-full-col">
							<label class="ifs-form-label" for="ifs_staff_img_url"><?php esc_html_e( 'প্রোফাইল ছবি (Media Upload)', 'ifs-parking' ); ?></label>
							<div class="ifs-media-upload-group">
								<input type="text" id="ifs_staff_img_url" name="profile_img" value="<?php echo $edit_data ? esc_url( $edit_data->profile_img ) : ''; ?>" placeholder="<?php esc_attr_e( 'ছবির ইউআরএল...', 'ifs-parking' ); ?>" class="ifs-veh-input ifs-media-input" />
								<button type="button" id="ifs_upload_staff_img" class="ifs-btn-media-select">
									<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
									<?php esc_html_e( 'ছবি সিলেক্ট করুন', 'ifs-parking' ); ?>
								</button>
							</div>
							<div id="ifs_staff_img_preview" class="ifs-media-preview-box">
								<?php if ( $edit_data && ! empty( $edit_data->profile_img ) ) : ?>
									<img src="<?php echo esc_url( $edit_data->profile_img ); ?>" class="ifs-staff-avatar-preview" alt="<?php esc_attr_e( 'প্রোফাইল প্রিভিউ', 'ifs-parking' ); ?>" />
								<?php endif; ?>
							</div>
						</div>
					</div>

					<div class="ifs-form-submit-row">
						<button type="submit" class="ifs-btn-submit">
							<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
							<?php echo $edit_data ? esc_html__( 'স্টাফ আপডেট করুন', 'ifs-parking' ) : esc_html__( 'স্টাফ সংরক্ষণ করুন', 'ifs-parking' ); ?>
						</button>
					</div>
				</form>
			</div>

			<!-- ওয়ার্ডপ্রেস মিডিয়া আপলোডার স্ক্রিপ্ট -->
			<script>
			document.addEventListener('DOMContentLoaded', function() {
				var uploadBtn  = document.getElementById('ifs_upload_staff_img');
				var urlInput   = document.getElementById('ifs_staff_img_url');
				var previewBox = document.getElementById('ifs_staff_img_preview');

				if (uploadBtn && typeof wp !== 'undefined' && wp.media && !uploadBtn.dataset.ifsBound) {
					uploadBtn.dataset.ifsBound = '1';
					var mediaFrame;

					uploadBtn.addEventListener('click', function(e) {
						e.preventDefault();
						if (mediaFrame) {
							mediaFrame.open();
							return;
						}

						mediaFrame = wp.media({
							title: <?php echo wp_json_encode( __( 'স্টাফের প্রোফাইল ছবি নির্বাচন করুন', 'ifs-parking' ) ); ?>,
							button: {
								text: <?php echo wp_json_encode( __( 'ছবি ব্যবহার করুন', 'ifs-parking' ) ); ?>
							},
							library: { type: 'image' },
							multiple: false
						});

						mediaFrame.on('select', function() {
							var attachment = mediaFrame.state().get('selection').first().toJSON();
							if (attachment && attachment.url) {
								urlInput.value = attachment.url;
								previewBox.textContent = '';
								var img = document.createElement('img');
								img.src = attachment.url;
								img.className = 'ifs-staff-avatar-preview';
								img.alt = 'Preview';
								previewBox.appendChild(img);
							}
						});

						mediaFrame.open();
					});
				}
			});
			</script>

		<?php else : ?>
			<!-- স্টাফ তালিকা -->
			<div class="ifs-card ifs-table-container-card">
				<?php
				$staff_list = array();
				if ( $has_staff_table ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$staff_list = $wpdb->get_results( "SELECT * FROM `{$table_staff}` ORDER BY id DESC" );
				}
				?>

				<div class="ifs-table-responsive">
					<table class="wp-list-table widefat fixed striped ifs-custom-data-table">
						<thead>
							<tr>
								<th class="ifs-col-id">#</th>
								<th><?php esc_html_e( 'স্টাফের নাম', 'ifs-parking' ); ?></th>
								<th><?php esc_html_e( 'পদবী / রোল', 'ifs-parking' ); ?></th>
								<th><?php esc_html_e( 'ফোন ও ইমেইল', 'ifs-parking' ); ?></th>
								<th><?php esc_html_e( 'বেতন', 'ifs-parking' ); ?></th>
								<th><?php esc_html_e( 'ডিউটি স্লট', 'ifs-parking' ); ?></th>
								<th class="ifs-col-actions-lg"><?php esc_html_e( 'অ্যাকশন', 'ifs-parking' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( ! empty( $staff_list ) ) : ?>
								<?php
								foreach ( $staff_list as $stf ) :
									$view_url   = admin_url( 'admin.php?page=ifs-parking-staff&action=view&id=' . $stf->id );
									$edit_url   = admin_url( 'admin.php?page=ifs-parking-staff&action=edit&id=' . $stf->id );
									$delete_url = wp_nonce_url( admin_url( 'admin.php?page=ifs-parking-staff&action=delete&id=' . $stf->id ), 'ifs_delete_staff_' . $stf->id );
									$role_txt   = isset( $role_labels_bd[ $stf->role ] ) ? $role_labels_bd[ $stf->role ] : $stf->role;
									?>
									<tr>
										<td class="ifs-td-num">#<?php echo esc_html( ifs_parking_staff_bn_num( $stf->id ) ); ?></td>
										<td>
											<div class="ifs-staff-cell">
												<?php if ( ! empty( $stf->profile_img ) ) : ?>
													<img src="<?php echo esc_url( $stf->profile_img ); ?>" class="ifs-staff-avatar-sm" alt="<?php echo esc_attr( $stf->staff_name ); ?>" />
												<?php else : ?>
													<div class="ifs-staff-avatar-placeholder-sm"><?php echo esc_html( mb_substr( $stf->staff_name, 0, 1 ) ); ?></div>
												<?php endif; ?>
												<strong class="ifs-veh-strong"><?php echo esc_html( $stf->staff_name ); ?></strong>
											</div>
										</td>
										<td><span class="ifs-badge-dept"><?php echo esc_html( $role_txt ); ?></span></td>
										<td>
											<?php echo esc_html( ifs_parking_staff_bn_num( $stf->phone ) ); ?><br>
											<small class="ifs-td-time-dim"><?php echo esc_html( $stf->email ); ?></small>
										</td>
										<td>
											<strong class="ifs-txt-fee-bold">
												<?php echo esc_html( $currency_symbol . ifs_parking_staff_bn_num( number_format_i18n( (float) $stf->salary, 2 ) ) ); ?>
											</strong>
										</td>
										<td><span class="ifs-txt-valid"><?php echo esc_html( ! empty( $stf->duty_slot ) ? $stf->duty_slot : __( 'নির্ধারিত নয়', 'ifs-parking' ) ); ?></span></td>
										<td class="ifs-td-action-right">
											<div class="ifs-action-buttons-group">
												<a href="<?php echo esc_url( $view_url ); ?>" class="ifs-btn-action ifs-btn-view" title="<?php esc_attr_e( 'বিস্তারিত দেখুন', 'ifs-parking' ); ?>">
													<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
													<?php esc_html_e( 'ভিউ', 'ifs-parking' ); ?>
												</a>
												<a href="<?php echo esc_url( $edit_url ); ?>" class="ifs-btn-action ifs-btn-edit" title="<?php esc_attr_e( 'সম্পাদনা করুন', 'ifs-parking' ); ?>">
													<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
													<?php esc_html_e( 'এডিট', 'ifs-parking' ); ?>
												</a>
												<a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'আপনি কি নিশ্চিতভাবে এই স্টাফকে ডিলিট করতে চান?', 'ifs-parking' ) ); ?>');" class="ifs-btn-action ifs-btn-delete" title="<?php esc_attr_e( 'ডিলিট করুন', 'ifs-parking' ); ?>">
													<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
													<?php esc_html_e( 'ডিলিট', 'ifs-parking' ); ?>
												</a>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="7" class="ifs-empty-table-cell">
										<?php esc_html_e( 'কোনো স্টাফ বা অপারেটর পাওয়া যায়নি।', 'ifs-parking' ); ?>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php
}