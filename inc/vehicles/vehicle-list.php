<?php
/**
 * Vehicles List Table Template.
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
function ifs_parking_list_bn_num( $number ) {
	if ( function_exists( 'ifs_parking_to_bn_number' ) ) {
		return ifs_parking_to_bn_number( $number );
	}

	$en_digits = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	$bn_digits = array( '০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯' );

	return str_replace( $en_digits, $bn_digits, (string) $number );
}

/**
 * সেকেন্ডকে বাংলা "X ঘণ্টা Y মিনিট" ফরম্যাটে রূপান্তর করার হেল্পার।
 *
 * @param int $seconds মোট সেকেন্ড।
 * @return string
 */
function ifs_parking_list_format_duration_bn( $seconds ) {
	$secs  = max( 0, (int) $seconds );
	$hours = (int) floor( $secs / 3600 );
	$mins  = (int) floor( ( $secs % 3600 ) / 60 );

	$parts = array();
	if ( $hours > 0 ) {
		$parts[] = sprintf(
			/* translators: %s: ঘণ্টার সংখ্যা */
			__( '%s ঘণ্টা', 'ifs-parking' ),
			ifs_parking_list_bn_num( $hours )
		);
	}
	if ( $mins > 0 || empty( $parts ) ) {
		$parts[] = sprintf(
			/* translators: %s: মিনিটের সংখ্যা */
			__( '%s মিনিট', 'ifs-parking' ),
			ifs_parking_list_bn_num( $mins )
		);
	}

	return implode( ' ', $parts );
}

/**
 * সকল গাড়ির তালিকা টেবিল রেন্ডারার।
 *
 * @param string $table_logs      লগ টেবিল নাম।
 * @param string $currency_symbol কারেন্সি প্রতীক।
 * @param int    $auto_print_id   অটো প্রিন্ট আইডি।
 * @return void
 */
function ifs_parking_render_vehicle_list( $table_logs, $currency_symbol, $auto_print_id ) {
	global $wpdb;

	$bd_tz             = new DateTimeZone( 'Asia/Dhaka' );
	$overdue_threshold = 7800; // ২ ঘণ্টা ১০ মিনিট (2 * 3600 + 10 * 60 = 7800 সেকেন্ড)।
	$now_utc           = time();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$has_logs_table = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_logs ) ) === $table_logs );

	// ডিলিট অ্যাকশন হ্যান্ডলার (action=delete_vehicle&id=...).
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$req_action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	$req_id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( $has_logs_table && 'delete_vehicle' === $req_action && $req_id > 0 && current_user_can( 'manage_options' ) ) {
		check_admin_referer( 'ifs_delete_vehicle_' . $req_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$del_item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_logs}` WHERE id = %d", $req_id ) );

		if ( $del_item ) {
			$table_slots = $wpdb->prefix . 'ifs_parking_slots';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( 'Parked' === $del_item->status && ! empty( $del_item->slot_number ) && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_slots ) ) === $table_slots ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table_slots,
					array( 'is_occupied' => 0 ),
					array( 'slot_name' => $del_item->slot_number ),
					array( '%d' ),
					array( '%s' )
				);
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$deleted = $wpdb->delete(
				$table_logs,
				array( 'id' => $req_id ),
				array( '%d' )
			);

			if ( false !== $deleted ) {
				echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 14px 0; border-radius: 8px;">
						<p><strong>' . esc_html(
							sprintf(
								/* translators: %s: গাড়ির নম্বর */
								__( 'গাড়ি (%s)-এর পার্কিং রেকর্ড সফলভাবে মুছে ফেলা হয়েছে!', 'ifs-parking' ),
								$del_item->vehicle_number
							)
						) . '</strong></p>
					  </div>';
			}
		}
	}

	$logs = array();
	if ( $has_logs_table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$logs = $wpdb->get_results( "SELECT * FROM `{$table_logs}` ORDER BY id DESC LIMIT 100" );
	}

	// ক্যাটাগরি রেট ম্যাপ লোড (২ ঘণ্টা ১০ মিনিট ধাপভিত্তিক চার্জের জন্য)।
	$table_cats         = $wpdb->prefix . 'ifs_parking_categories';
	$categories_by_slug = array();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_cats ) ) === $table_cats ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$all_cats = $wpdb->get_results( "SELECT * FROM `{$table_cats}` ORDER BY id ASC" );
		if ( ! empty( $all_cats ) ) {
			foreach ( $all_cats as $c_item ) {
				if ( ! empty( $c_item->category_slug ) ) {
					$slug_lc  = strtolower( (string) $c_item->category_slug );
					$two_hr_v = isset( $c_item->two_hour_rate ) ? (float) $c_item->two_hour_rate : 0.00;
					$hr_v     = isset( $c_item->hourly_rate ) ? (float) $c_item->hourly_rate : 0.00;
					if ( ! isset( $categories_by_slug[ $slug_lc ] ) || $two_hr_v > 0 || $hr_v > 0 ) {
						$categories_by_slug[ $slug_lc ] = $c_item;
					}
				}
			}
		}
	}

	$site_title   = get_option( 'ifs_parking_site_title', __( 'আইএফএস পার্কিং সিস্টেম', 'ifs-parking' ) );
	$system_phone = get_option( 'ifs_parking_contact_phone', '+৮৮০ ১৭০০-০০০০০০' );

	// ক্রম: বাইক -> কার -> সিএনজি -> সাইকেল -> নোয়াহ -> হাইয়েস -> ট্রাক
	$veh_labels_bd = array(
		'Bike'  => __( 'বাইক (Bike)', 'ifs-parking' ),
		'Car'   => __( 'কার (Car)', 'ifs-parking' ),
		'CNG'   => __( 'সিএনজি (CNG)', 'ifs-parking' ),
		'Cycle' => __( 'সাইকেল (Cycle)', 'ifs-parking' ),
		'Noah'  => __( 'নোয়াহ (Noah)', 'ifs-parking' ),
		'Hiace' => __( 'হাইয়েস (Hiace)', 'ifs-parking' ),
		'Truck' => __( 'ট্রাক (Truck)', 'ifs-parking' ),
	);
	?>
	<style>
		/* ভেহিক্যাল লিস্ট রেসপনসিভনেস ও সিস্টেম টাইপোগ্রাফি */
		.ifs-table-container-card {
			box-sizing: border-box;
			font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
		}
		.ifs-veh-list-header {
			padding: 15px 20px;
			border-bottom: 1px solid #e2e8f0;
			display: flex;
			justify-content: space-between;
			align-items: center;
			background: #f8fafc;
			border-top-left-radius: 10px;
			border-top-right-radius: 10px;
			flex-wrap: wrap;
			gap: 12px;
		}
		.ifs-search-wrapper {
			position: relative;
			width: 280px;
		}
		.ifs-table-responsive {
			width: 100%;
			overflow-x: auto;
			-webkit-overflow-scrolling: touch;
		}
		.ifs-custom-data-table {
			min-width: 820px;
		}

		/* প্রিন্ট স্লিপের জন্য সিঙ্গেল-পেজ অপ্টিমাইজড স্টাইল */
		#printable-receipt {
			display: none;
		}
		@media print {
			@page {
				size: auto;
				margin: 5mm;
			}
			html,
			body {
				height: auto !important;
				min-height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				background: #ffffff !important;
			}
			#wpadminbar,
			#adminmenumain,
			#adminmenuwrap,
			#adminmenuback,
			#wpfooter,
			.ifs-mobile-topbar,
			.ifs-sidebar-col,
			.ifs-top-quick-switch,
			.ifs-table-container-card,
			.notice {
				display: none !important;
			}
			#wpwrap,
			#wpcontent,
			#wpbody,
			#wpbody-content,
			.wrap,
			.ifs-app-layout,
			.ifs-content-col {
				margin: 0 !important;
				padding: 0 !important;
				min-height: 0 !important;
				height: auto !important;
				display: block !important;
				width: 100% !important;
			}
			#printable-receipt {
				display: block !important;
				position: static !important;
				width: 100% !important;
				max-width: 340px !important;
				margin: 0 auto !important;
				padding: 12px !important;
				background: #ffffff !important;
				page-break-inside: avoid !important;
				break-inside: avoid !important;
			}
		}

		/* ট্যাবলেট এবং মোবাইল ভিউপোর্ট রুলস */
		@media screen and (max-width: 782px) {
			.ifs-veh-list-header {
				flex-direction: column;
				align-items: stretch;
				padding: 14px;
			}
			.ifs-search-wrapper {
				width: 100%;
			}
			#ifs-vehicle-search {
				height: 44px;
				font-size: 16px !important;
				box-sizing: border-box;
			}
			.ifs-actions-row {
				flex-wrap: wrap;
				gap: 6px !important;
			}
			.ifs-actions-row a,
			.ifs-actions-row button {
				padding: 6px 10px !important;
				font-size: 12px !important;
			}
		}
	</style>

	<div class="ifs-card ifs-table-container-card">
		<!-- লাইভ সার্চ ইনপুট হেডার -->
		<div class="ifs-veh-list-header">
			<h3 style="margin: 0; font-size: 16px; color: #1e293b; display: flex; align-items: center; gap: 8px;">
				<svg class="ifs-svg-primary" viewBox="0 0 24 24" style="width:18px;height:18px;fill:#0284c7;"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
				<?php esc_html_e( 'পার্কিং এন্ট্রি ও ইনভয়েস তালিকা', 'ifs-parking' ); ?>
			</h3>
			<div class="ifs-search-wrapper">
				<input type="text" id="ifs-vehicle-search" placeholder="<?php esc_attr_e( 'ইনভয়েস বা গাড়ি নম্বর দিয়ে খুঁজুন...', 'ifs-parking' ); ?>" style="width: 100%; padding: 8px 12px 8px 34px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; background: #fff;">
				<svg viewBox="0 0 24 24" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; fill: #94a3b8;"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
			</div>
		</div>

		<div class="ifs-table-responsive">
			<table class="wp-list-table widefat fixed striped ifs-custom-data-table" id="ifs-vehicles-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ইনভয়েস নং', 'ifs-parking' ); ?></th>
						<th><?php esc_html_e( 'গাড়ি নম্বর', 'ifs-parking' ); ?></th>
						<th><?php esc_html_e( 'ডিপার্টমেন্ট', 'ifs-parking' ); ?></th>
						<th><?php esc_html_e( 'ক্যাটাগরি', 'ifs-parking' ); ?></th>
						<th><?php esc_html_e( 'প্রযোজ্য ফি', 'ifs-parking' ); ?></th>
						<th><?php esc_html_e( 'প্রবেশের সময়', 'ifs-parking' ); ?></th>
						<th class="ifs-col-status-fixed"><?php esc_html_e( 'স্ট্যাটাস', 'ifs-parking' ); ?></th>
						<th class="ifs-col-action-fixed" style="width: 220px;"><?php esc_html_e( 'অ্যাকশন', 'ifs-parking' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $logs ) ) : ?>
						<?php
						foreach ( $logs as $log ) :
							$checkout_url = wp_nonce_url( admin_url( 'admin.php?page=ifs-parking-vehicles&tab=all_vehicles&action=checkout&id=' . $log->id ), 'ifs_checkout_vehicle_' . $log->id );
							$delete_url   = wp_nonce_url( admin_url( 'admin.php?page=ifs-parking-vehicles&tab=all_vehicles&action=delete_vehicle&id=' . $log->id ), 'ifs_delete_vehicle_' . $log->id );
							$view_url     = admin_url( 'admin.php?page=ifs-parking-vehicles&action=view&id=' . $log->id );

							$invoice_num = ! empty( $log->reference_id ) ? strtok( $log->reference_id, ' [' ) : 'NPMS-' . str_pad( (string) $log->id, 4, '0', STR_PAD_LEFT );

							// ডিপার্টমেন্ট আলাদা করা।
							$dept_val = __( 'সাধারণ', 'ifs-parking' );
							if ( ! empty( $log->reference_id ) && preg_match( '/\[(.*?)\]/', $log->reference_id, $matches ) ) {
								$dept_val = $matches[1];
							} elseif ( ! empty( $log->reference_id ) && false !== strpos( $log->reference_id, '[' ) ) {
								$parts    = explode( '[', $log->reference_id );
								$dept_val = rtrim( trim( $parts[1] ), ']' );
							}

							// গাড়ির ধরণ আলাদা করা।
							$raw_type = (string) $log->parking_type;
							if ( false !== strpos( $raw_type, '-' ) ) {
								$type_parts  = explode( '-', $raw_type );
								$parking_key = trim( ucfirst( $type_parts[0] ) );
							} else {
								$parking_key = ucfirst( trim( $raw_type ) );
							}
							$parking_type = isset( $veh_labels_bd[ $parking_key ] ) ? $veh_labels_bd[ $parking_key ] : $parking_key;

							$driver_name  = ! empty( $log->driver_name ) ? $log->driver_name : __( 'তথ্য নেই', 'ifs-parking' );
							$driver_phone = ! empty( $log->driver_phone ) ? ifs_parking_list_bn_num( $log->driver_phone ) : '';

							// সময় ও ২ ঘণ্টা ১০ মিনিট (৭৮০০ সেকেন্ড) ধাপভিত্তিক বিল হিসাব।
							$entry_ts       = 0;
							$exit_ts        = 0;
							$formatted_time = $log->entry_time;
							$formatted_exit = __( 'এখনও পার্কিংয়ে', 'ifs-parking' );

							if ( ! empty( $log->entry_time ) ) {
								try {
									$dt_entry       = new DateTimeImmutable( $log->entry_time, $bd_tz );
									$entry_ts       = $dt_entry->getTimestamp();
									$formatted_time = ifs_parking_list_bn_num( wp_date( 'j M, Y - h:i A', $entry_ts, $bd_tz ) );
								} catch ( Exception $e ) {
									$entry_ts       = strtotime( $log->entry_time ) ? (int) strtotime( $log->entry_time ) : 0;
									$formatted_time = $log->entry_time;
								}
							}

							if ( ! empty( $log->exit_time ) && '0000-00-00 00:00:00' !== $log->exit_time ) {
								try {
									$dt_exit        = new DateTimeImmutable( $log->exit_time, $bd_tz );
									$exit_ts        = $dt_exit->getTimestamp();
									$formatted_exit = ifs_parking_list_bn_num( wp_date( 'j M, Y - h:i A', $exit_ts, $bd_tz ) );
								} catch ( Exception $e ) {
									$exit_ts        = strtotime( $log->exit_time ) ? (int) strtotime( $log->exit_time ) : 0;
									$formatted_exit = $log->exit_time;
								}
							}

							$is_parked  = ( 'Parked' === $log->status );
							$end_ts     = $is_parked ? $now_utc : ( $exit_ts ? $exit_ts : $now_utc );
							$diff_secs  = $entry_ts ? max( 0, $end_ts - $entry_ts ) : 0;
							$stay_bn    = ifs_parking_list_format_duration_bn( $diff_secs );
							$is_overdue = ( $diff_secs > $overdue_threshold );
							$ovd_bn     = $is_overdue ? ifs_parking_list_format_duration_bn( $diff_secs - $overdue_threshold ) : __( 'নেই', 'ifs-parking' );

							$slug_lc     = strtolower( trim( $raw_type ) );
							$matched_cat = isset( $categories_by_slug[ $slug_lc ] ) ? $categories_by_slug[ $slug_lc ] : null;
							$base_rate   = (float) $log->fee;
							$step_rate   = ( $matched_cat && isset( $matched_cat->two_hour_rate ) && (float) $matched_cat->two_hour_rate > 0 )
								? (float) $matched_cat->two_hour_rate
								: ( ( $matched_cat && isset( $matched_cat->hourly_rate ) && (float) $matched_cat->hourly_rate > 0 ) ? (float) $matched_cat->hourly_rate : $base_rate );

							if ( $is_parked ) {
								if ( false !== strpos( $slug_lc, 'monthly' ) ) {
									$computed_fee = round( $base_rate, 2 );
								} elseif ( false !== strpos( $slug_lc, 'daily' ) ) {
									$computed_fee = round( max( 1, (int) ceil( $diff_secs / 86400 ) ) * $base_rate, 2 );
								} else {
									$tier         = ( $diff_secs <= $overdue_threshold ) ? 1 : (int) ceil( $diff_secs / $overdue_threshold );
									$computed_fee = round( $tier * max( 0.00, $step_rate ), 2 );
								}
							} else {
								$computed_fee = round( $base_rate, 2 );
							}

							$base_fee_val    = ( $step_rate > 0 ) ? round( $step_rate, 2 ) : $computed_fee;
							$overdue_fee_val = $is_overdue ? max( 0.00, round( $computed_fee - $base_fee_val, 2 ) ) : 0.00;

							$formatted_fee      = $currency_symbol . ifs_parking_list_bn_num( number_format_i18n( $computed_fee, 2 ) );
							$formatted_base_fee = $currency_symbol . ifs_parking_list_bn_num( number_format_i18n( $base_fee_val, 2 ) );
							$formatted_ovd_fee  = $currency_symbol . ifs_parking_list_bn_num( number_format_i18n( $overdue_fee_val, 2 ) );

							if ( $is_parked && $is_overdue ) {
								$status_print_label = __( 'ওভারডিউ (পার্ক করা আছে)', 'ifs-parking' );
							} elseif ( $is_parked ) {
								$status_print_label = __( 'পার্ক করা আছে', 'ifs-parking' );
							} elseif ( $is_overdue ) {
								$status_print_label = __( 'ওভারডিউ (বের হয়েছে)', 'ifs-parking' );
							} else {
								$status_print_label = __( 'বের হয়েছে', 'ifs-parking' );
							}
							?>
							<tr class="ifs-vehicle-row">
								<td>
									<span class="ifs-searchable-invoice" style="font-weight: 700; color: #0284c7; background: #e0f2fe; padding: 3px 8px; border-radius: 4px; font-size: 11.5px;">
										<?php echo esc_html( $invoice_num ); ?>
									</span>
								</td>
								<td>
									<strong class="ifs-veh-strong ifs-searchable-veh"><?php echo esc_html( $log->vehicle_number ); ?></strong>
								</td>
								<td>
									<span class="ifs-badge-dept">
										<?php echo esc_html( $dept_val ); ?>
									</span>
								</td>
								<td>
									<span class="ifs-txt-semi" style="font-weight: 600; color: #0f172a;"><?php echo esc_html( $parking_type ); ?></span>
									<?php if ( ! empty( $log->membership_expiry ) ) : ?>
										<br><small class="ifs-txt-exp">
											<?php esc_html_e( 'মেয়াদ:', 'ifs-parking' ); ?>
											<?php echo esc_html( ifs_parking_list_bn_num( $log->membership_expiry ) ); ?>
										</small>
									<?php endif; ?>
								</td>
								<td>
									<span class="ifs-txt-fee-bold">
										<?php echo esc_html( $formatted_fee ); ?>
									</span>
								</td>
								<td class="ifs-td-time-dim">
									<?php echo esc_html( $formatted_time ); ?>
								</td>
								<td>
									<?php if ( 'Parked' === $log->status ) : ?>
										<span class="ifs-status-pill ifs-status-parked">
											<svg class="ifs-dot-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg>
											<?php echo esc_html( $is_overdue ? __( 'ওভারডিউ', 'ifs-parking' ) : __( 'পার্ক করা আছে', 'ifs-parking' ) ); ?>
										</span>
									<?php else : ?>
										<span class="ifs-status-pill ifs-status-exited">
											<svg class="ifs-check-icon" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
											<?php echo esc_html( $is_overdue ? __( 'ওভারডিউ (বের হয়েছে)', 'ifs-parking' ) : __( 'বের হয়েছে', 'ifs-parking' ) ); ?>
										</span>
									<?php endif; ?>
								</td>
								<td class="ifs-td-action-right">
									<div class="ifs-actions-row" style="display: flex; gap: 4px; align-items: center; flex-wrap: wrap;">
										<!-- ভিউ বাটন -->
										<a href="<?php echo esc_url( $view_url ); ?>" class="ifs-btn-action ifs-btn-view" title="<?php esc_attr_e( 'বিস্তারিত দেখুন', 'ifs-parking' ); ?>" style="padding: 4px 8px; background: #e0f2fe; color: #0284c7; border-radius: 4px; text-decoration: none; font-weight: 600; font-size: 11px;">
											<?php esc_html_e( 'ভিউ', 'ifs-parking' ); ?>
										</a>

										<!-- রসিদ প্রিন্ট বাটন -->
										<button type="button" class="ifs-tbl-print-btn ifs-instant-print"
												data-id="<?php echo esc_attr( $log->id ); ?>"
												data-invoice="<?php echo esc_attr( $invoice_num ); ?>"
												data-veh="<?php echo esc_attr( $log->vehicle_number ); ?>"
												data-dept="<?php echo esc_attr( $dept_val ); ?>"
												data-cat="<?php echo esc_attr( $parking_type ); ?>"
												data-base-fee="<?php echo esc_attr( $formatted_base_fee ); ?>"
												data-overdue-fee="<?php echo esc_attr( $formatted_ovd_fee ); ?>"
												data-fee="<?php echo esc_attr( $formatted_fee ); ?>"
												data-unit-fee="<?php echo esc_attr( $base_fee_val ); ?>"
												data-stay="<?php echo esc_attr( $stay_bn ); ?>"
												data-overdue-dur="<?php echo esc_attr( $ovd_bn ); ?>"
												data-status-label="<?php echo esc_attr( $status_print_label ); ?>"
												data-driver="<?php echo esc_attr( $driver_name ); ?>"
												data-phone="<?php echo esc_attr( $driver_phone ); ?>"
												data-time="<?php echo esc_attr( $formatted_time ); ?>"
												data-exit="<?php echo esc_attr( $formatted_exit ); ?>"
												title="<?php esc_attr_e( 'রসিদ প্রিন্ট করুন', 'ifs-parking' ); ?>">
											<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
											<?php esc_html_e( 'রসিদ', 'ifs-parking' ); ?>
										</button>

										<?php if ( 'Parked' === $log->status ) : ?>
											<a href="<?php echo esc_url( $checkout_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'আপনি কি নিশ্চিত যে গাড়িটি চেক-আউট/রিলিজ করতে চান?', 'ifs-parking' ) ); ?>');" class="ifs-btn-checkout">
												<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
												<?php esc_html_e( 'রিলিজ', 'ifs-parking' ); ?>
											</a>
										<?php else : ?>
											<span class="ifs-txt-completed"><?php esc_html_e( 'সম্পন্ন', 'ifs-parking' ); ?></span>
										<?php endif; ?>

										<!-- ডিলিট বাটন -->
										<?php if ( current_user_can( 'manage_options' ) ) : ?>
											<a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'আপনি কি নিশ্চিতভাবে এই পার্কিং রেকর্ডটি মুছে ফেলতে চান?', 'ifs-parking' ) ); ?>');" class="ifs-btn-action ifs-btn-delete" title="<?php esc_attr_e( 'ডিলিট করুন', 'ifs-parking' ); ?>" style="padding: 4px 8px; background: #fee2e2; color: #b91c1c; border-radius: 4px; text-decoration: none; font-weight: 600; font-size: 11px;">
												<?php esc_html_e( 'ডিলিট', 'ifs-parking' ); ?>
											</a>
										<?php endif; ?>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="8" class="ifs-empty-table-cell"><?php esc_html_e( 'কোনো পার্কিং রেকর্ড পাওয়া যায়নি।', 'ifs-parking' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- লাইভ সার্চ ও ইনস্ট্যান্ট/অটো প্রিন্ট জাভাস্ক্রিপ্ট -->
	<script type="text/javascript">
	document.addEventListener('DOMContentLoaded', function() {
		var autoPrintId    = <?php echo absint( $auto_print_id ); ?>;
		var currencySymbol = <?php echo wp_json_encode( $currency_symbol ); ?>;
		var noDataText     = <?php echo wp_json_encode( __( 'তথ্য নেই', 'ifs-parking' ) ); ?>;
		var noneText       = <?php echo wp_json_encode( __( 'নেই', 'ifs-parking' ) ); ?>;

		var bnDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
		function toBnDigits(str) {
			return String(str).replace(/[0-9]/g, function(d) { return bnDigits[+d]; });
		}

		function parseAmount(raw) {
			if (!raw) return 0;
			var map = { '০': '0', '১': '1', '২': '2', '৩': '3', '৪': '4', '৫': '5', '৬': '6', '৭': '7', '৮': '8', '৯': '9' };
			var en  = String(raw).replace(/[০-৯]/g, function(ch) { return map[ch] || ch; });
			var num = parseFloat(en.replace(/[^0-9.]/g, ''));
			return isNaN(num) ? 0 : num;
		}

		// ১. লাইভ সার্চ হ্যান্ডলার
		var searchInput = document.getElementById('ifs-vehicle-search');
		if (searchInput) {
			searchInput.addEventListener('input', function() {
				var filter = this.value.toLowerCase().trim();
				var rows   = document.querySelectorAll('.ifs-vehicle-row');

				rows.forEach(function(row) {
					var invoiceText = row.querySelector('.ifs-searchable-invoice').textContent.toLowerCase();
					var vehText     = row.querySelector('.ifs-searchable-veh').textContent.toLowerCase();

					if (invoiceText.includes(filter) || vehText.includes(filter)) {
						row.style.display = '';
					} else {
						row.style.display = 'none';
					}
				});
			});
		}

		// প্রিন্ট স্লিপ পপুলেট ও প্রিন্ট ফাংশন
		function triggerReceiptPrint(btn) {
			var invoice    = btn.getAttribute('data-invoice');
			var veh        = btn.getAttribute('data-veh');
			var dept       = btn.getAttribute('data-dept');
			var cat        = btn.getAttribute('data-cat');
			var fee        = btn.getAttribute('data-fee');
			var baseFee    = btn.getAttribute('data-base-fee');
			var overdueFee = btn.getAttribute('data-overdue-fee');
			var unitFee    = parseAmount(btn.getAttribute('data-unit-fee')) || parseAmount(baseFee) || parseAmount(fee);
			var stay       = btn.getAttribute('data-stay');
			var overdueDur = btn.getAttribute('data-overdue-dur');
			var statusLbl  = btn.getAttribute('data-status-label');
			var driver     = btn.getAttribute('data-driver');
			var phone      = btn.getAttribute('data-phone');
			var time       = btn.getAttribute('data-time');
			var exitTime   = btn.getAttribute('data-exit');

			var baseNum    = parseAmount(baseFee);
			var overdueNum = parseAmount(overdueFee);
			var totalNum   = parseAmount(fee);
			if (totalNum <= 0 && (baseNum > 0 || overdueNum > 0)) {
				totalNum = baseNum + overdueNum;
				fee      = currencySymbol + toBnDigits(totalNum.toFixed(2));
			}

			var tInvoice    = document.getElementById('t-invoice');
			var tTime       = document.getElementById('t-time');
			var tExit       = document.getElementById('t-exit');
			var tStay       = document.getElementById('t-stay');
			var tOverdueDur = document.getElementById('t-overdue-dur');
			var tStatus     = document.getElementById('t-status');
			var tVeh        = document.getElementById('t-veh');
			var tDept       = document.getElementById('t-dept');
			var tCat        = document.getElementById('t-cat');
			var tBaseFee    = document.getElementById('t-base-fee');
			var tOverdueFee = document.getElementById('t-overdue-fee');
			var tFee        = document.getElementById('t-fee');
			var tDriver     = document.getElementById('t-driver');
			var tPhone      = document.getElementById('t-phone');
			var tNote       = document.getElementById('t-slab-note');

			if (tInvoice)    tInvoice.textContent    = invoice || '-';
			if (tTime)       tTime.textContent       = time || '-';
			if (tExit)       tExit.textContent       = exitTime || '-';
			if (tStay)       tStay.textContent       = stay || '-';
			if (tOverdueDur) tOverdueDur.textContent = overdueDur || noneText;
			if (tStatus)     tStatus.textContent     = statusLbl || '-';
			if (tVeh)        tVeh.textContent        = veh || '-';
			if (tDept)       tDept.textContent       = dept || '-';
			if (tCat)        tCat.textContent        = cat || '-';
			if (tBaseFee)    tBaseFee.textContent    = baseFee || (currencySymbol + toBnDigits(baseNum.toFixed(2)));
			if (tOverdueFee) tOverdueFee.textContent = overdueFee || (currencySymbol + toBnDigits(overdueNum.toFixed(2)));
			if (tFee)        tFee.textContent        = fee || '-';
			if (tDriver)     tDriver.textContent     = driver || noDataText;
			if (tPhone)      tPhone.textContent      = phone || '-';

			if (tNote) {
				if (unitFee > 0) {
					var p1 = currencySymbol + toBnDigits(unitFee.toFixed(2));
					var p2 = currencySymbol + toBnDigits((unitFee * 2).toFixed(2));
					var p3 = currencySymbol + toBnDigits((unitFee * 3).toFixed(2));
					tNote.textContent = 'প্রথম ২ ঘণ্টা ১০ মিনিট (১০ মিঃ গ্রেসসহ) ১ম ধাপের চার্জ ' + p1 + '। পরবর্তী প্রতি ২ ঘণ্টা ১০ মিনিট পরপর ' + p1 + ' করে যুক্ত হবে (' + p1 + ' → ' + p2 + ' → ' + p3 + ')। ওভারডিউ হলে রসিদে ওভারডিউ সময় ও ওভারডিউ ফি যুক্ত থাকে।';
				} else {
					tNote.textContent = 'প্রথম ২ ঘণ্টা ১০ মিনিট (১০ মিঃ গ্রেসসহ) ১ম ধাপের চার্জ বর্তমান রেট অনুযায়ী প্রযোজ্য এবং পরবর্তী প্রতি ২ ঘণ্টা ১০ মিনিট পরপর বর্তমান রেট যুক্ত হবে।';
				}
			}

			window.print();
		}

		// ২. ইনস্ট্যান্ট প্রিন্ট হ্যান্ডলার
		document.querySelectorAll('.ifs-instant-print').forEach(function(btn) {
			btn.addEventListener('click', function() {
				triggerReceiptPrint(this);
			});
		});

		// ৩. নতুন চেক-ইন শেষে অটো-প্রিন্ট হ্যান্ডলার
		if (autoPrintId > 0) {
			var targetBtn = document.querySelector('.ifs-instant-print[data-id="' + autoPrintId + '"]');
			if (targetBtn) {
				setTimeout(function() {
					triggerReceiptPrint(targetBtn);
					if (window.history && window.history.replaceState) {
						var cleanUrl = window.location.href.replace(/(&|\?)(auto_print|action|id|_wpnonce)=[^&]*/g, '');
						window.history.replaceState({}, document.title, cleanUrl);
					}
				}, 350);
			}
		}

		// ডিলিট বা চেকআউট শেষে URL থেকে অপ্রয়োজনীয় প্যারামিটার পরিষ্কার করা যাতে পেজ রিফ্রেশে পুনরায় অ্যাকশন না চলে
		if (window.history && window.history.replaceState && window.location.search.indexOf('action=delete_vehicle') !== -1) {
			var cleanedDeleteUrl = window.location.href.replace(/(&|\?)(action|id|_wpnonce)=[^&]*/g, '');
			window.history.replaceState({}, document.title, cleanedDeleteUrl);
		}
	});
	</script>

	<!-- ডেডিকেটেড প্রিন্ট কন্টেইনার -->
	<div id="printable-receipt" class="ifs-dedicated-print-box">
		<div class="ifs-dedicated-print-head">
			<h3 class="ifs-receipt-main-title"><?php echo esc_html( $site_title ); ?></h3>
			<p class="ifs-print-phone"><?php esc_html_e( 'হেল্পলাইন:', 'ifs-parking' ); ?> <?php echo esc_html( ifs_parking_list_bn_num( $system_phone ) ); ?></p>
			<p class="ifs-print-title-tag"><?php esc_html_e( 'অফিসিয়াল এন্ট্রি স্লিপ ও রসিদ', 'ifs-parking' ); ?></p>
		</div>

		<div class="ifs-receipt-rows">
			<div class="ifs-receipt-row-item" style="background: #f8fafc; padding: 6px 10px; border-radius: 6px; border: 1px dashed #cbd5e1; margin-bottom: 4px;">
				<span class="ifs-rcpt-lbl" style="font-weight: 700; color: #0284c7;"><?php esc_html_e( 'ইনভয়েস নং:', 'ifs-parking' ); ?></span>
				<span id="t-invoice" class="ifs-rcpt-val-bold" style="font-weight: 800; color: #0f172a;">-</span>
			</div>
			<div class="ifs-receipt-row-item">
				<span><?php esc_html_e( 'প্রবেশের সময়:', 'ifs-parking' ); ?></span>
				<span id="t-time" class="ifs-rcpt-val-bold">-</span>
			</div>
			<div class="ifs-receipt-row-item">
				<span><?php esc_html_e( 'বের হওয়ার সময়:', 'ifs-parking' ); ?></span>
				<span id="t-exit" class="ifs-rcpt-val-bold">-</span>
			</div>
			<div class="ifs-receipt-row-item">
				<span><?php esc_html_e( 'মোট অবস্থান সময়:', 'ifs-parking' ); ?></span>
				<span id="t-stay" class="ifs-rcpt-val-bold">-</span>
			</div>
			<div class="ifs-receipt-row-item">
				<span><?php esc_html_e( 'ওভারডিউ সময়:', 'ifs-parking' ); ?></span>
				<span id="t-overdue-dur" class="ifs-rcpt-val-bold">-</span>
			</div>
			<div class="ifs-receipt-row-item">
				<span><?php esc_html_e( 'বর্তমান অবস্থা:', 'ifs-parking' ); ?></span>
				<span id="t-status" class="ifs-rcpt-val-bold">-</span>
			</div>
			<div class="ifs-receipt-row-item">
				<span><?php esc_html_e( 'গাড়ি নম্বর:', 'ifs-parking' ); ?></span>
				<strong id="t-veh" class="ifs-print-veh-strong">-</strong>
			</div>
			<div class="ifs-receipt-row-item">
				<span><?php esc_html_e( 'ডিপার্টমেন্ট:', 'ifs-parking' ); ?></span>
				<span id="t-dept" class="ifs-rcpt-val-bold">-</span>
			</div>
			<div class="ifs-receipt-row-item">
				<span><?php esc_html_e( 'ক্যাটাগরি:', 'ifs-parking' ); ?></span>
				<span id="t-cat" class="ifs-rcpt-val-bold">-</span>
			</div>
			<div class="ifs-receipt-row-item">
				<span><?php esc_html_e( 'প্রথম ধাপ (২.১০ ঘণ্টা) ফি:', 'ifs-parking' ); ?></span>
				<span id="t-base-fee" class="ifs-rcpt-val-bold">-</span>
			</div>
			<div class="ifs-receipt-row-item">
				<span><?php esc_html_e( 'ওভারডিউ ফি:', 'ifs-parking' ); ?></span>
				<span id="t-overdue-fee" class="ifs-rcpt-val-bold">-</span>
			</div>
			<div class="ifs-receipt-row-item ifs-print-fee-dashed">
				<span class="ifs-rcpt-val-bold"><?php esc_html_e( 'সর্বমোট বিল:', 'ifs-parking' ); ?></span>
				<strong id="t-fee" class="ifs-print-fee-strong">-</strong>
			</div>
			<div class="ifs-receipt-row-item">
				<span><?php esc_html_e( 'চালকের নাম:', 'ifs-parking' ); ?></span>
				<span id="t-driver">-</span>
			</div>
			<div class="ifs-receipt-row-item">
				<span><?php esc_html_e( 'চালকের ফোন:', 'ifs-parking' ); ?></span>
				<span id="t-phone">-</span>
			</div>
		</div>

		<div class="ifs-print-rules-box">
			<p id="t-slab-note" style="margin: 0 0 8px 0; font-size: 11.5px; font-weight: 600;"></p>
			<strong class="ifs-print-rules-label"><?php esc_html_e( 'পার্কিং নির্দেশিকা ও নিয়মাবলী:', 'ifs-parking' ); ?></strong>
			<ol class="ifs-receipt-rules-list ifs-print-rules-ol">
				<li><?php esc_html_e( 'গাড়ি পার্কিং থেকে বের করার সময় এই রসিদ স্লিপটি অবশ্যই প্রদর্শন করতে হবে।', 'ifs-parking' ); ?></li>
				<li><?php esc_html_e( 'পার্কিং প্রাঙ্গণে গাড়ির গতিসীমা সর্বোচ্চ ১০ কিমি/ঘণ্টা বজায় রাখুন।', 'ifs-parking' ); ?></li>
				<li><?php esc_html_e( 'গাড়ির ভেতরে কোনো মূল্যবান বা ঝুঁকিপূর্ণ সামগ্রী নিজ দায়িত্বে রাখবেন না।', 'ifs-parking' ); ?></li>
				<li><?php esc_html_e( 'স্লিপটি হারিয়ে গেলে অতিরিক্ত জরিমানা ও যাচাইকরণ প্রযোজ্য হবে।', 'ifs-parking' ); ?></li>
				<li><?php esc_html_e( 'জরুরি প্রয়োজনে উপরোক্ত হেল্পলাইনে বা নিয়ন্ত্রণ কক্ষে যোগাযোগ করুন।', 'ifs-parking' ); ?></li>
			</ol>
		</div>
	</div>
	<?php
}