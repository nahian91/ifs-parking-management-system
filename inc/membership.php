<?php
/**
 * Memberships & Subscriptions Management Module.
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
function ifs_parking_member_bn_num( $number ) {
	if ( function_exists( 'ifs_parking_to_bn_number' ) ) {
		return ifs_parking_to_bn_number( $number );
	}

	$en_digits = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	$bn_digits = array( '০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯' );

	return str_replace( $en_digits, $bn_digits, (string) $number );
}

/**
 * মেম্বারশিপ পেজ রেন্ডারার।
 *
 * @return void
 */
function ifs_parking_render_memberships_page() {
	if ( function_exists( 'ifs_parking_render_layout_wrapper' ) ) {
		ifs_parking_render_layout_wrapper( 'ifs_parking_render_memberships_content' );
	} else {
		ifs_parking_render_memberships_content();
	}
}

/**
 * মূল মেম্বারশিপ কনটেন্ট রেন্ডারার।
 *
 * @return void
 */
function ifs_parking_render_memberships_content() {
	global $wpdb;
	$table_members = $wpdb->prefix . 'ifs_parking_memberships';
	$table_cats    = $wpdb->prefix . 'ifs_parking_categories';

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'all_members';

	// টেবিল বিদ্যমান আছে কি না যাচাই করা।
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$has_members_table = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_members ) ) === $table_members );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$has_cats_table = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_cats ) ) === $table_cats );

	// কারেন্সি সিম্বল।
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

	// নতুন মেম্বারশিপ সেভ হ্যান্ডলার।
	if (
		$has_members_table &&
		isset( $_POST['ifs_save_member_nonce'] ) &&
		wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ifs_save_member_nonce'] ) ), 'ifs_save_member_action' ) &&
		current_user_can( 'read' )
	) {
		$member_name    = isset( $_POST['member_name'] ) ? sanitize_text_field( wp_unslash( $_POST['member_name'] ) ) : '';
		$member_phone   = isset( $_POST['member_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['member_phone'] ) ) : '';
		$vehicle_number = isset( $_POST['vehicle_number'] ) ? sanitize_text_field( wp_unslash( $_POST['vehicle_number'] ) ) : '';
		$vehicle_type   = isset( $_POST['vehicle_type'] ) ? sanitize_text_field( wp_unslash( $_POST['vehicle_type'] ) ) : 'Car';
		$plan_name      = isset( $_POST['plan_name'] ) ? sanitize_text_field( wp_unslash( $_POST['plan_name'] ) ) : __( '১ মাস (Monthly)', 'ifs-parking' );
		$start_date     = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : current_time( 'Y-m-d' );
		$expiry_date    = isset( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : current_time( 'Y-m-d' );
		$fee_paid       = isset( $_POST['fee_paid'] ) ? max( 0, (float) sanitize_text_field( wp_unslash( $_POST['fee_paid'] ) ) ) : 0.00;

		if ( ! empty( $member_name ) && ! empty( $vehicle_number ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $wpdb->insert(
				$table_members,
				array(
					'member_name'    => $member_name,
					'member_phone'   => $member_phone,
					'vehicle_number' => $vehicle_number,
					'vehicle_type'   => $vehicle_type,
					'plan_name'      => $plan_name,
					'start_date'     => $start_date,
					'expiry_date'    => $expiry_date,
					'fee_paid'       => $fee_paid,
					'status'         => 'Active',
					'created_at'     => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s' )
			);

			if ( false !== $inserted ) {
				echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
						<p class="ifs-notice-text">
							<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
							' . esc_html__( 'নতুন মেম্বারশিপ সফলভাবে তৈরি করা হয়েছে!', 'ifs-parking' ) . '
						</p>
					  </div>';
				$active_tab = 'all_members';
			}
		}
	}

	// ক্যাটাগরি থেকে ইউনিক গাড়ির তালিকা ও মাসিক রেট সংগ্রহ।
	$cat_rates_raw = array();
	if ( $has_cats_table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$cat_rates_raw = $wpdb->get_results( "SELECT vehicle_type, monthly_rate FROM `{$table_cats}` WHERE vehicle_type IS NOT NULL AND vehicle_type != '' ORDER BY id ASC" );
	}

	$vehicle_rates = array();
	if ( ! empty( $cat_rates_raw ) ) {
		foreach ( $cat_rates_raw as $c_rate ) {
			$v_name = trim( $c_rate->vehicle_type );
			if ( ! isset( $vehicle_rates[ $v_name ] ) || (float) $c_rate->monthly_rate > 0 ) {
				$vehicle_rates[ $v_name ] = (float) $c_rate->monthly_rate;
			}
		}
	}

	// ফলব্যাক যদি কোনো ক্যাটাগরি তৈরি না থাকে।
	if ( empty( $vehicle_rates ) ) {
		$vehicle_rates = array(
			'Car'   => 1500.00,
			'Bike'  => 500.00,
			'Truck' => 3000.00,
		);
	}

	$veh_labels_bd = array(
		'Car'   => __( 'কার (Car)', 'ifs-parking' ),
		'Noah'  => __( 'নোয়াহ (Noah)', 'ifs-parking' ),
		'Hiace' => __( 'হাইয়েস (Hiace)', 'ifs-parking' ),
		'CNG'   => __( 'সিএনজি (CNG)', 'ifs-parking' ),
		'Truck' => __( 'ট্রাক (Truck)', 'ifs-parking' ),
		'Bike'  => __( 'বাইক (Bike)', 'ifs-parking' ),
		'Cycle' => __( 'সাইকেল (Cycle)', 'ifs-parking' ),
	);

	$default_expiry = wp_date( 'Y-m-d', strtotime( '+30 days', current_time( 'timestamp' ) ) );
	?>
	<style>
		/* মেম্বারশিপ মডিউল রেসপনসিভ অ্যাডাপ্টেশন ও প্রমিত বাংলা টাইপোগ্রাফি */
		.ifs-memberships-wrapper {
			margin: 10px 15px 20px 0;
			box-sizing: border-box;
			font-family: 'Hind Siliguri', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
		}
		.ifs-memberships-banner {
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
		.ifs-form-2col {
			display: grid;
			grid-template-columns: repeat(2, 1fr);
			gap: 16px;
			margin-bottom: 16px;
		}
		.ifs-form-3col {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 16px;
			margin-bottom: 16px;
		}
		.ifs-form-field-group {
			margin-bottom: 20px;
		}
		.ifs-table-responsive {
			width: 100%;
			overflow-x: auto;
			-webkit-overflow-scrolling: touch;
		}
		.ifs-custom-data-table {
			min-width: 700px;
		}

		/* ট্যাবলেট এবং মোবাইল ভিউপোর্ট রুলস */
		@media screen and (max-width: 782px) {
			.ifs-memberships-wrapper {
				margin: 10px 5px 20px 0;
			}
			.ifs-memberships-banner {
				flex-direction: column;
				align-items: stretch;
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
			.ifs-form-2col,
			.ifs-form-3col {
				grid-template-columns: 1fr;
				gap: 14px;
				margin-bottom: 14px;
			}
			.ifs-veh-input {
				font-size: 16px !important;
				height: 44px;
				width: 100%;
				box-sizing: border-box;
			}
			.ifs-btn-submit {
				width: 100%;
				justify-content: center;
				height: 46px;
				box-sizing: border-box;
			}
		}
	</style>

	<div class="wrap ifs-memberships-wrapper">
		<!-- ব্যানার -->
		<div class="ifs-memberships-banner">
			<div>
				<span class="ifs-banner-badge ifs-badge-purple">
					<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
					<?php esc_html_e( 'সাবস্ক্রিপশন ও পাস', 'ifs-parking' ); ?>
				</span>
				<h1 class="ifs-banner-title"><?php esc_html_e( 'মেম্বারশিপ ব্যবস্থাপনা', 'ifs-parking' ); ?></h1>
				<p class="ifs-banner-subtitle"><?php esc_html_e( 'গ্রাহকদের মাসিক ও দীর্ঘমেয়াদী পার্কিং পাস এবং সাবস্ক্রিপশন মেয়াদ নিয়ন্ত্রণ করুন।', 'ifs-parking' ); ?></p>
			</div>
		</div>

		<!-- ট্যাব -->
		<div class="ifs-tabs">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-memberships&tab=all_members' ) ); ?>" class="ifs-tab-btn <?php echo 'all_members' === $active_tab ? 'active' : ''; ?>">
				<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
				<?php esc_html_e( 'সকল সদস্য তালিকা', 'ifs-parking' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-memberships&tab=add_member' ) ); ?>" class="ifs-tab-btn <?php echo 'add_member' === $active_tab ? 'active' : ''; ?>">
				<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
				<?php esc_html_e( 'নতুন মেম্বারশিপ যুক্ত করুন', 'ifs-parking' ); ?>
			</a>
		</div>

		<?php if ( 'add_member' === $active_tab ) : ?>
			<!-- ফর্ম -->
			<div class="ifs-card ifs-form-card ifs-member-form-card">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-memberships&tab=add_member' ) ); ?>">
					<?php wp_nonce_field( 'ifs_save_member_action', 'ifs_save_member_nonce' ); ?>

					<div class="ifs-form-2col">
						<div>
							<label class="ifs-form-label" for="ifs-member-name"><?php esc_html_e( 'সদস্যের নাম *', 'ifs-parking' ); ?></label>
							<input type="text" id="ifs-member-name" name="member_name" required placeholder="<?php esc_attr_e( 'পুরো নাম', 'ifs-parking' ); ?>" class="ifs-veh-input">
						</div>
						<div>
							<label class="ifs-form-label" for="ifs-member-phone"><?php esc_html_e( 'মোবাইল নম্বর *', 'ifs-parking' ); ?></label>
							<input type="text" id="ifs-member-phone" name="member_phone" required placeholder="<?php esc_attr_e( '০১XXXXXXXXX', 'ifs-parking' ); ?>" class="ifs-veh-input">
						</div>
					</div>

					<div class="ifs-form-2col">
						<div>
							<label class="ifs-form-label" for="ifs-member-veh"><?php esc_html_e( 'গাড়ি নম্বর *', 'ifs-parking' ); ?></label>
							<input type="text" id="ifs-member-veh" name="vehicle_number" required placeholder="<?php esc_attr_e( 'ঢাকা মেট্রো-গ ১২-৩৪৫৬', 'ifs-parking' ); ?>" class="ifs-veh-input">
						</div>
						<div>
							<label class="ifs-form-label" for="ifs-member-vehicle-type"><?php esc_html_e( 'গাড়ির ধরণ *', 'ifs-parking' ); ?></label>
							<select name="vehicle_type" id="ifs-member-vehicle-type" class="ifs-veh-input">
								<?php
								foreach ( $vehicle_rates as $vk => $rate ) :
									$v_label = isset( $veh_labels_bd[ $vk ] ) ? $veh_labels_bd[ $vk ] : $vk;
									?>
									<option value="<?php echo esc_attr( $vk ); ?>" data-monthly="<?php echo esc_attr( $rate ); ?>">
										<?php echo esc_html( $v_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<div class="ifs-form-3col">
						<div>
							<label class="ifs-form-label" for="ifs-plan-select"><?php esc_html_e( 'প্যাকেজের ধরণ', 'ifs-parking' ); ?></label>
							<select name="plan_name" id="ifs-plan-select" class="ifs-veh-input">
								<option value="১ মাস (Monthly)" data-multiplier="1"><?php esc_html_e( '১ মাস (Monthly)', 'ifs-parking' ); ?></option>
								<option value="৩ মাস (Quarterly)" data-multiplier="3"><?php esc_html_e( '৩ মাস (Quarterly)', 'ifs-parking' ); ?></option>
								<option value="৬ মাস (Half-Yearly)" data-multiplier="6"><?php esc_html_e( '৬ মাস (Half-Yearly)', 'ifs-parking' ); ?></option>
								<option value="১ বছর (Yearly)" data-multiplier="12"><?php esc_html_e( '১ বছর (Yearly)', 'ifs-parking' ); ?></option>
							</select>
						</div>
						<div>
							<label class="ifs-form-label" for="ifs-start-date"><?php esc_html_e( 'শুরুর তারিখ', 'ifs-parking' ); ?></label>
							<input type="date" name="start_date" id="ifs-start-date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required class="ifs-veh-input">
						</div>
						<div>
							<label class="ifs-form-label" for="ifs-expiry-date"><?php esc_html_e( 'মেয়াদ উত্তীর্ণের তারিখ', 'ifs-parking' ); ?></label>
							<input type="date" name="expiry_date" id="ifs-expiry-date" value="<?php echo esc_attr( $default_expiry ); ?>" required class="ifs-veh-input">
						</div>
					</div>

					<div class="ifs-form-field-group">
						<label class="ifs-form-label" for="ifs-member-fee-paid">
							<?php
							/* translators: %s: কারেন্সি প্রতীক */
							echo esc_html( sprintf( __( 'পরিশোধিত ফি (%s)', 'ifs-parking' ), $currency_symbol ) );
							?>
						</label>
						<input type="number" min="0" step="0.01" name="fee_paid" id="ifs-member-fee-paid" value="0.00" class="ifs-veh-input ifs-input-fee">
					</div>

					<button type="submit" class="ifs-btn-submit">
						<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
						<?php esc_html_e( 'মেম্বারশিপ ইস্যু করুন', 'ifs-parking' ); ?>
					</button>
				</form>
			</div>

			<!-- ক্যাটাগরি রেট ও মেয়াদ অটো ক্যালকুলেটর স্ক্রিপ্ট -->
			<script>
			document.addEventListener('DOMContentLoaded', function() {
				var vehSelect   = document.getElementById('ifs-member-vehicle-type');
				var planSelect  = document.getElementById('ifs-plan-select');
				var startInput  = document.getElementById('ifs-start-date');
				var expiryInput = document.getElementById('ifs-expiry-date');
				var feeInput    = document.getElementById('ifs-member-fee-paid');

				function updateAutoFeeAndExpiry() {
					if (!vehSelect || !planSelect || !feeInput) return;

					var selectedVehOption = vehSelect.options[vehSelect.selectedIndex];
					var monthlyRate       = parseFloat(selectedVehOption.getAttribute('data-monthly')) || 0;

					var selectedPlanOption = planSelect.options[planSelect.selectedIndex];
					var multiplier         = parseInt(selectedPlanOption.getAttribute('data-multiplier'), 10) || 1;

					feeInput.value = (monthlyRate * multiplier).toFixed(2);

					if (startInput && expiryInput && startInput.value) {
						var parts = startInput.value.split('-');
						if (parts.length === 3) {
							var d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
							d.setMonth(d.getMonth() + multiplier);
							var yyyy = d.getFullYear();
							var mm   = String(d.getMonth() + 1).padStart(2, '0');
							var dd   = String(d.getDate()).padStart(2, '0');
							expiryInput.value = yyyy + '-' + mm + '-' + dd;
						}
					}
				}

				if (vehSelect && planSelect) {
					vehSelect.addEventListener('change', updateAutoFeeAndExpiry);
					planSelect.addEventListener('change', updateAutoFeeAndExpiry);
					if (startInput) {
						startInput.addEventListener('change', updateAutoFeeAndExpiry);
					}
					updateAutoFeeAndExpiry();
				}
			});
			</script>
		<?php else : ?>
			<!-- তালিকা -->
			<?php
			$members = array();
			if ( $has_members_table ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$members = $wpdb->get_results( "SELECT * FROM `{$table_members}` ORDER BY id DESC" );
			}
			$today = current_time( 'Y-m-d' );
			?>
			<div class="ifs-card ifs-table-container-card">
				<div class="ifs-table-responsive">
					<table class="wp-list-table widefat fixed striped ifs-custom-data-table">
						<thead>
							<tr>
								<th class="ifs-col-id">#</th>
								<th><?php esc_html_e( 'সদস্যের নাম', 'ifs-parking' ); ?></th>
								<th><?php esc_html_e( 'গাড়ি ও ধরণ', 'ifs-parking' ); ?></th>
								<th><?php esc_html_e( 'প্যাকেজ', 'ifs-parking' ); ?></th>
								<th><?php esc_html_e( 'ফি', 'ifs-parking' ); ?></th>
								<th><?php esc_html_e( 'শুরু - মেয়াদ', 'ifs-parking' ); ?></th>
								<th class="ifs-col-status-fixed"><?php esc_html_e( 'স্ট্যাটাস', 'ifs-parking' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( ! empty( $members ) ) : ?>
								<?php
								foreach ( $members as $m ) :
									$is_expired = ( $m->expiry_date < $today );
									$v_label    = isset( $veh_labels_bd[ $m->vehicle_type ] ) ? $veh_labels_bd[ $m->vehicle_type ] : $m->vehicle_type;
									$start_ts   = strtotime( $m->start_date );
									$expiry_ts  = strtotime( $m->expiry_date );
									$start_fmt  = $start_ts ? ifs_parking_member_bn_num( date_i18n( 'j M, Y', $start_ts ) ) : ifs_parking_member_bn_num( $m->start_date );
									$expiry_fmt = $expiry_ts ? ifs_parking_member_bn_num( date_i18n( 'j M, Y', $expiry_ts ) ) : ifs_parking_member_bn_num( $m->expiry_date );
									?>
									<tr>
										<td class="ifs-td-num">#<?php echo esc_html( ifs_parking_member_bn_num( $m->id ) ); ?></td>
										<td>
											<strong class="ifs-veh-strong"><?php echo esc_html( $m->member_name ); ?></strong><br>
											<small class="ifs-td-time-dim"><?php echo esc_html( ifs_parking_member_bn_num( $m->member_phone ) ); ?></small>
										</td>
										<td>
											<strong class="ifs-veh-strong"><?php echo esc_html( $m->vehicle_number ); ?></strong><br>
											<span class="ifs-txt-veh-type"><?php echo esc_html( $v_label ); ?></span>
										</td>
										<td class="ifs-txt-semi"><?php echo esc_html( $m->plan_name ); ?></td>
										<td>
											<strong class="ifs-txt-fee-bold">
												<?php echo esc_html( $currency_symbol . ifs_parking_member_bn_num( number_format_i18n( (float) $m->fee_paid, 2 ) ) ); ?>
											</strong>
										</td>
										<td>
											<?php
											echo esc_html(
												sprintf(
													/* translators: %s: শুরুর তারিখ */
													__( '%s হতে', 'ifs-parking' ),
													$start_fmt
												)
											);
											?>
											<br>
											<strong class="<?php echo $is_expired ? 'ifs-txt-expired' : 'ifs-txt-valid'; ?>">
												<?php echo esc_html( $expiry_fmt ); ?>
											</strong>
										</td>
										<td>
											<?php if ( $is_expired ) : ?>
												<span class="ifs-status-pill ifs-status-expired">
													<svg class="ifs-dot-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg>
													<?php esc_html_e( 'মেয়াদোত্তীর্ণ', 'ifs-parking' ); ?>
												</span>
											<?php else : ?>
												<span class="ifs-status-pill ifs-status-active">
													<svg class="ifs-check-icon" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
													<?php esc_html_e( 'সক্রিয়', 'ifs-parking' ); ?>
												</span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="7" class="ifs-empty-table-cell">
										<?php esc_html_e( 'কোনো মেম্বারশিপ পাওয়া যায়নি।', 'ifs-parking' ); ?>
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