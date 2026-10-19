<?php
/**
 * Memberships & Subscriptions Management Module.
 *
 * @package Ifs_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * মেম্বারশিপ পেজ রেন্ডারার
 */
function ifs_parking_render_memberships_page() {
	ifs_parking_render_layout_wrapper( 'ifs_parking_render_memberships_content' );
}

/**
 * মূল মেম্বারশিপ কনটেন্ট
 */
function ifs_parking_render_memberships_content() {
	global $wpdb;
	$table_members = $wpdb->prefix . 'ifs_parking_memberships';
	$table_cats    = $wpdb->prefix . 'ifs_parking_categories';
	$active_tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'all_members';

	// কারেন্সি সিম্বল
	$currency_setting = get_option( 'ifs_parking_currency', 'BDT (৳)' );
	$currency_symbol  = false !== strpos( $currency_setting, '৳' ) ? '৳' : '$';

	// নতুন মেম্বারশিপ সেভ হ্যান্ডলার
	if ( isset( $_POST['ifs_save_member_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ifs_save_member_nonce'] ) ), 'ifs_save_member_action' ) ) {
		$member_name    = isset( $_POST['member_name'] ) ? sanitize_text_field( wp_unslash( $_POST['member_name'] ) ) : '';
		$member_phone   = isset( $_POST['member_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['member_phone'] ) ) : '';
		$vehicle_number = isset( $_POST['vehicle_number'] ) ? sanitize_text_field( wp_unslash( $_POST['vehicle_number'] ) ) : '';
		$vehicle_type   = isset( $_POST['vehicle_type'] ) ? sanitize_text_field( wp_unslash( $_POST['vehicle_type'] ) ) : 'Car';
		$plan_name      = isset( $_POST['plan_name'] ) ? sanitize_text_field( wp_unslash( $_POST['plan_name'] ) ) : '1-month';
		$start_date     = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : current_time( 'Y-m-d' );
		$expiry_date    = isset( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : current_time( 'Y-m-d' );
		$fee_paid       = isset( $_POST['fee_paid'] ) ? floatval( wp_unslash( $_POST['fee_paid'] ) ) : 0.00;

		if ( ! empty( $member_name ) && ! empty( $vehicle_number ) ) {
			$wpdb->insert(
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

			echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
					<p class="ifs-notice-text">
						<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
						' . esc_html__( 'নতুন মেম্বারশিপ সফলভাবে তৈরি করা হয়েছে!', 'ifs-parking' ) . '
					</p>
				  </div>';
			$active_tab = 'all_members';
		}
	}

	// ক্যাটাগরি থেকে ইউনিক গাড়ির তালিকা ও মাসিক রেট সংগ্রহ
	$cat_rates_raw = $wpdb->get_results( "SELECT vehicle_type, monthly_rate FROM $table_cats WHERE vehicle_type IS NOT NULL AND vehicle_type != '' ORDER BY id ASC" );
	$vehicle_rates = array();
	foreach ( $cat_rates_raw as $c_rate ) {
		$v_name = trim( $c_rate->vehicle_type );
		if ( ! isset( $vehicle_rates[ $v_name ] ) || floatval( $c_rate->monthly_rate ) > 0 ) {
			$vehicle_rates[ $v_name ] = floatval( $c_rate->monthly_rate );
		}
	}

	// ফলব্যাক যদি কোনো ক্যাটাগরি তৈরি না থাকে
	if ( empty( $vehicle_rates ) ) {
		$vehicle_rates = array(
			'Car'   => 1500.00,
			'Bike'  => 500.00,
			'Truck' => 3000.00,
		);
	}
	?>
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
							<label class="ifs-form-label"><?php esc_html_e( 'সদস্যের নাম *', 'ifs-parking' ); ?></label>
							<input type="text" name="member_name" required placeholder="<?php esc_attr_e( 'পুরো নাম', 'ifs-parking' ); ?>" class="ifs-veh-input">
						</div>
						<div>
							<label class="ifs-form-label"><?php esc_html_e( 'মোবাইল নম্বর *', 'ifs-parking' ); ?></label>
							<input type="text" name="member_phone" required placeholder="০১XXXXXXXXX" class="ifs-veh-input">
						</div>
					</div>

					<div class="ifs-form-2col">
						<div>
							<label class="ifs-form-label"><?php esc_html_e( 'গাড়ি নম্বর *', 'ifs-parking' ); ?></label>
							<input type="text" name="vehicle_number" required placeholder="ঢাকা মেট্রো-গ ১২-৩৪৫৬" class="ifs-veh-input">
						</div>
						<div>
							<label class="ifs-form-label"><?php esc_html_e( 'গাড়ির ধরণ *', 'ifs-parking' ); ?></label>
							<select name="vehicle_type" id="ifs-member-vehicle-type" class="ifs-veh-input">
								<?php foreach ( $vehicle_rates as $vk => $rate ) : ?>
									<option value="<?php echo esc_attr( $vk ); ?>" data-monthly="<?php echo esc_attr( $rate ); ?>">
										<?php echo esc_html( $vk ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<div class="ifs-form-3col">
						<div>
							<label class="ifs-form-label"><?php esc_html_e( 'প্যাকেজের ধরণ', 'ifs-parking' ); ?></label>
							<select name="plan_name" id="ifs-plan-select" class="ifs-veh-input">
								<option value="১ মাস (Monthly)" data-multiplier="1"><?php esc_html_e( '১ মাস (Monthly)', 'ifs-parking' ); ?></option>
								<option value="৩ মাস (Quarterly)" data-multiplier="3"><?php esc_html_e( '৩ মাস (Quarterly)', 'ifs-parking' ); ?></option>
								<option value="৬ মাস (Half-Yearly)" data-multiplier="6"><?php esc_html_e( '৬ মাস (Half-Yearly)', 'ifs-parking' ); ?></option>
								<option value="১ বছর (Yearly)" data-multiplier="12"><?php esc_html_e( '১ বছর (Yearly)', 'ifs-parking' ); ?></option>
							</select>
						</div>
						<div>
							<label class="ifs-form-label"><?php esc_html_e( 'শুরুর তারিখ', 'ifs-parking' ); ?></label>
							<input type="date" name="start_date" id="ifs-start-date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required class="ifs-veh-input">
						</div>
						<div>
							<label class="ifs-form-label"><?php esc_html_e( 'মেয়াদ উত্তীর্ণের তারিখ', 'ifs-parking' ); ?></label>
							<input type="date" name="expiry_date" id="ifs-expiry-date" value="<?php echo esc_attr( date( 'Y-m-d', strtotime( '+30 days' ) ) ); ?>" required class="ifs-veh-input">
						</div>
					</div>

					<div class="ifs-form-field-group">
						<label class="ifs-form-label">
							<?php echo esc_html( sprintf( __( 'পরিশোধিত ফি (%s)', 'ifs-parking' ), $currency_symbol ) ); ?>
						</label>
						<input type="number" step="0.01" name="fee_paid" id="ifs-member-fee-paid" value="0.00" class="ifs-veh-input ifs-input-fee">
					</div>

					<button type="submit" class="ifs-btn-submit">
						<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
						<?php esc_html_e( 'মেম্বারশিপ ইস্যু করুন', 'ifs-parking' ); ?>
					</button>
				</form>
			</div>

			<!-- ক্যাটাগরি রেট অটো ক্যালকুলেটর স্ক্রিপ্ট -->
			<script>
			document.addEventListener('DOMContentLoaded', function () {
				const vehSelect = document.getElementById('ifs-member-vehicle-type');
				const planSelect = document.getElementById('ifs-plan-select');
				const feeInput = document.getElementById('ifs-member-fee-paid');

				function updateAutoFee() {
					if (!vehSelect || !planSelect || !feeInput) return;

					const selectedVehOption = vehSelect.options[vehSelect.selectedIndex];
					const monthlyRate = parseFloat(selectedVehOption.getAttribute('data-monthly')) || 0;

					const selectedPlanOption = planSelect.options[planSelect.selectedIndex];
					const multiplier = parseInt(selectedPlanOption.getAttribute('data-multiplier'), 10) || 1;

					const totalCalculated = (monthlyRate * multiplier).toFixed(2);
					feeInput.value = totalCalculated;
				}

				if (vehSelect && planSelect) {
					vehSelect.addEventListener('change', updateAutoFee);
					planSelect.addEventListener('change', updateAutoFee);
					updateAutoFee(); // ইনিশিয়াল পেজ লোডে ক্যালকুলেট করা
				}
			});
			</script>
		<?php else : ?>
			<!-- তালিকা -->
			<?php 
			$members = $wpdb->get_results( "SELECT * FROM $table_members ORDER BY id DESC" ); 
			$today   = current_time( 'Y-m-d' );
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
								<?php foreach ( $members as $m ) : 
									$is_expired = ( $m->expiry_date < $today );
								?>
									<tr>
										<td class="ifs-td-num">#<?php echo esc_html( $m->id ); ?></td>
										<td>
											<strong class="ifs-veh-strong"><?php echo esc_html( $m->member_name ); ?></strong><br>
											<small class="ifs-td-time-dim"><?php echo esc_html( $m->member_phone ); ?></small>
										</td>
										<td>
											<strong class="ifs-veh-strong"><?php echo esc_html( $m->vehicle_number ); ?></strong><br>
											<span class="ifs-txt-veh-type"><?php echo esc_html( $m->vehicle_type ); ?></span>
										</td>
										<td class="ifs-txt-semi"><?php echo esc_html( $m->plan_name ); ?></td>
										<td><strong class="ifs-txt-fee-bold"><?php echo esc_html( $currency_symbol . number_format( (float) $m->fee_paid, 2 ) ); ?></strong></td>
										<td>
											<?php echo esc_html( $m->start_date ); ?> হতে<br>
											<strong class="<?php echo $is_expired ? 'ifs-txt-expired' : 'ifs-txt-valid'; ?>">
												<?php echo esc_html( $m->expiry_date ); ?>
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