<?php
/**
 * Live Parking Status Module (Every 2-Hour Slab Billing & Zero-Blink Live Monitor).
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
function ifs_parking_live_bn_num( $number ) {
	if ( function_exists( 'ifs_parking_to_bn_number' ) ) {
		return ifs_parking_to_bn_number( $number );
	}

	$en_digits = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	$bn_digits = array( '০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯' );

	return str_replace( $en_digits, $bn_digits, (string) $number );
}

/**
 * প্রতি ২ ঘণ্টা স্ল্যাব অনুযায়ী বিল হিসাব করার ফাংশন।
 * ২ ঘণ্টা পর্যন্ত = ১ স্ল্যাব (base_rate), ২ ঘণ্টা ১ মিনিট = ২ স্ল্যাব (base_rate * 2)।
 *
 * @param int    $diff_secs মোট পার্কিং সেকেন্ড।
 * @param float  $base_rate ২ ঘণ্টার নির্ধারিত রেট (বা দৈনিক/মাসিক রেট)।
 * @param string $cat_raw   প্যাকেজের ধরণ।
 * @return array
 */
function ifs_parking_calculate_2hr_slab_fee( $diff_secs, $base_rate, $cat_raw = 'hourly' ) {
	$diff_secs = max( 0, (int) $diff_secs );
	$base_rate = (float) $base_rate;
	$cat_lower = strtolower( (string) $cat_raw );

	if ( false !== strpos( $cat_lower, 'monthly' ) ) {
		return array(
			'slabs' => 1,
			'fee'   => round( $base_rate, 2 ),
		);
	}

	if ( false !== strpos( $cat_lower, 'daily' ) ) {
		$days = max( 1, (int) ceil( $diff_secs / 86400 ) );
		return array(
			'slabs' => $days,
			'fee'   => round( $days * $base_rate, 2 ),
		);
	}

	// প্রতি ২ ঘণ্টা (৭২০০ সেকেন্ড) পর পর স্ল্যাব বৃদ্ধি পাবে।
	// উদাহরণ: ১ সেকেন্ড থেকে ৭২০০ সেকেন্ড = ১ স্ল্যাব; ৭২০১ সেকেন্ড (২ ঘণ্টা ১ সেকেন্ড) = ২ স্ল্যাব।
	$slab_seconds = 7200;
	$slabs        = ( $diff_secs <= $slab_seconds ) ? 1 : (int) ceil( $diff_secs / $slab_seconds );

	return array(
		'slabs' => $slabs,
		'fee'   => round( $slabs * $base_rate, 2 ),
	);
}

/**
 * লাইভ পার্কিং স্ট্যাটাস পেজ রেন্ডারার।
 *
 * @return void
 */
function ifs_parking_render_live_status_page() {
	if ( function_exists( 'ifs_parking_render_layout_wrapper' ) ) {
		ifs_parking_render_layout_wrapper( 'ifs_parking_render_live_status_content' );
	} else {
		ifs_parking_render_live_status_content();
	}
}

/**
 * লাইভ স্ট্যাটাস টেবিলের সারিগুলো (Rows) রেন্ডার করার হেল্পার ফাংশন।
 *
 * @param array  $active_parked   পার্ক করা গাড়ির তালিকা।
 * @param string $currency_symbol কারেন্সি প্রতীক।
 * @return void
 */
function ifs_parking_render_live_table_rows( $active_parked, $currency_symbol = '৳' ) {
	$now_ts = current_time( 'timestamp' );

	if ( ! empty( $active_parked ) ) :
		foreach ( $active_parked as $item ) :
			$entry_ts    = strtotime( $item->entry_time );
			$entry_iso   = $entry_ts ? wp_date( 'c', $entry_ts ) : '';
			$diff_secs   = $entry_ts ? max( 0, $now_ts - $entry_ts ) : 0;
			$is_overdue  = ( $diff_secs > 7200 ); // ২ ঘণ্টার বেশি হলে ওভারডিউ।
			$base_rate   = (float) $item->fee;
			$calc        = ifs_parking_calculate_2hr_slab_fee( $diff_secs, $base_rate, $item->parking_type );
			$dept_label  = ! empty( $item->reference_id ) ? $item->reference_id : __( 'সাধারণ', 'ifs-parking' );
			$driver_name = ! empty( $item->driver_name ) ? $item->driver_name : __( 'তথ্য নেই', 'ifs-parking' );
			$entry_short = $entry_ts ? ifs_parking_live_bn_num( date_i18n( 'j M, h:i A', $entry_ts ) ) : $item->entry_time;
			$entry_full  = $entry_ts ? ifs_parking_live_bn_num( date_i18n( 'j M, Y - h:i A', $entry_ts ) ) : $item->entry_time;
			?>
			<tr class="ifs-live-row" data-status="<?php echo $is_overdue ? 'overdue' : 'active'; ?>">
				<td>
					<strong class="ifs-veh-num"><?php echo esc_html( $item->vehicle_number ); ?></strong>
					<?php if ( $is_overdue ) : ?>
						<br><span style="display:inline-block;margin-top:3px;font-size:11px;font-weight:700;color:#b45309;background:#fef3c7;padding:1px 7px;border-radius:99px;">
							<?php esc_html_e( 'ওভারডিউ (২+ ঘণ্টা)', 'ifs-parking' ); ?>
						</span>
					<?php endif; ?>
				</td>
				<td>
					<span class="ifs-badge-dept-pill">
						<?php echo esc_html( $dept_label ); ?>
					</span>
				</td>
				<td class="ifs-td-cat">
					<?php echo esc_html( ucwords( str_replace( '-', ' - ', (string) $item->parking_type ) ) ); ?>
				</td>
				<td>
					<strong class="ifs-driver-name"><?php echo esc_html( $driver_name ); ?></strong>
					<?php if ( ! empty( $item->driver_phone ) ) : ?>
						<br><small class="ifs-driver-phone"><?php echo esc_html( ifs_parking_live_bn_num( $item->driver_phone ) ); ?></small>
					<?php endif; ?>
				</td>
				<td class="ifs-td-time">
					<?php echo esc_html( $entry_short ); ?>
				</td>
				<!-- লাইভ কাউন্টার-আপ ও লাইভ বিল সেল -->
				<td>
					<span class="ifs-duration-pill">
						<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
						<span class="ifs-live-counter"
							  data-entry="<?php echo esc_attr( $entry_iso ); ?>"
							  data-rate="<?php echo esc_attr( $base_rate ); ?>"
							  data-cat-raw="<?php echo esc_attr( strtolower( (string) $item->parking_type ) ); ?>">
							<?php esc_html_e( 'গণনা হচ্ছে...', 'ifs-parking' ); ?>
						</span>
					</span>
					<div style="margin-top: 4px; font-size: 12.5px; font-weight: 700; color: #047857;">
						<?php esc_html_e( 'চলতি বিল:', 'ifs-parking' ); ?>
						<span class="ifs-live-fee-badge">
							<?php echo esc_html( $currency_symbol . ifs_parking_live_bn_num( number_format( $calc['fee'], 2 ) ) ); ?>
						</span>
						<small style="color:#64748b;font-weight:500;">
							(<?php echo esc_html( ifs_parking_live_bn_num( $calc['slabs'] ) ); ?> <?php esc_html_e( 'স্ল্যাব', 'ifs-parking' ); ?>)
						</small>
					</div>
				</td>
				<!-- রিলিজ বাটন -->
				<td class="ifs-td-action">
					<button type="button" class="ifs-btn-release ifs-trigger-release"
							data-id="<?php echo esc_attr( $item->id ); ?>"
							data-veh="<?php echo esc_attr( $item->vehicle_number ); ?>"
							data-dept="<?php echo esc_attr( $dept_label ); ?>"
							data-cat="<?php echo esc_attr( ucwords( str_replace( '-', ' - ', (string) $item->parking_type ) ) ); ?>"
							data-cat-raw="<?php echo esc_attr( strtolower( (string) $item->parking_type ) ); ?>"
							data-driver="<?php echo esc_attr( $driver_name ); ?>"
							data-phone="<?php echo esc_attr( ifs_parking_live_bn_num( $item->driver_phone ) ); ?>"
							data-entry="<?php echo esc_attr( $entry_iso ); ?>"
							data-entry-formatted="<?php echo esc_attr( $entry_full ); ?>"
							data-rate="<?php echo esc_attr( $base_rate ); ?>">
						<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
						<?php esc_html_e( 'চেক-আউট / রিলিজ', 'ifs-parking' ); ?>
					</button>
				</td>
			</tr>
			<?php
		endforeach;
	else :
		?>
		<tr id="no-records-row">
			<td colspan="7" class="ifs-empty-table-cell">
				<div class="ifs-empty-icon-box">
					<svg class="ifs-svg-large" viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
				</div>
				<strong class="ifs-empty-title"><?php esc_html_e( 'বর্তমানে কোনো গাড়ি পার্কিংয়ে নেই!', 'ifs-parking' ); ?></strong>
				<p class="ifs-empty-desc"><?php esc_html_e( 'নতুন কোনো গাড়ি প্রবেশ করলে তা স্বয়ংক্রিয়ভাবে এখানে তালিকাভুক্ত হবে।', 'ifs-parking' ); ?></p>
			</td>
		</tr>
		<?php
	endif;
}

/**
 * ব্যাকগ্রাউন্ড সাইলেন্ট রিফ্রেশের জন্য AJAX হ্যান্ডলার (কোনো পেজ ব্লিঙ্ক ছাড়া)।
 *
 * @return void
 */
function ifs_parking_ajax_fetch_live_status() {
	check_ajax_referer( 'ifs_parking_admin_nonce', 'nonce' );

	if ( ! current_user_can( 'read' ) ) {
		wp_send_json_error( array( 'message' => __( 'অনুমতি নেই।', 'ifs-parking' ) ) );
	}

	global $wpdb;
	$table_logs    = $wpdb->prefix . 'ifs_parking_logs';
	$active_parked = array();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_logs ) ) === $table_logs ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$active_parked = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table_logs}` WHERE status = %s ORDER BY entry_time DESC",
				'Parked'
			)
		);
	}

	$currency_setting = get_option( 'ifs_parking_currency', 'BDT (৳)' );
	$currency_symbol  = false !== strpos( $currency_setting, '৳' ) ? '৳' : '$';

	$now_ts        = current_time( 'timestamp' );
	$overdue_count = 0;
	foreach ( $active_parked as $p ) {
		$e_ts = strtotime( $p->entry_time );
		if ( $e_ts && ( $now_ts - $e_ts ) > 7200 ) {
			$overdue_count++;
		}
	}

	ob_start();
	ifs_parking_render_live_table_rows( $active_parked, $currency_symbol );
	$rows_html = ob_get_clean();

	wp_send_json_success(
		array(
			'html'       => $rows_html,
			'count_bn'   => ifs_parking_live_bn_num( count( $active_parked ) ),
			'overdue_bn' => ifs_parking_live_bn_num( $overdue_count ),
		)
	);
}
add_action( 'wp_ajax_ifs_parking_fetch_live_status', 'ifs_parking_ajax_fetch_live_status' );

/**
 * লাইভ স্ট্যাটাসের মূল কনটেন্ট রেন্ডারার।
 *
 * @return void
 */
function ifs_parking_render_live_status_content() {
	global $wpdb;

	$table_logs  = $wpdb->prefix . 'ifs_parking_logs';
	$table_slots = $wpdb->prefix . 'ifs_parking_slots';

	// কারেন্সি সেটিং ফেচ।
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

	$site_title   = get_option( 'ifs_parking_site_title', __( 'নূরজাহান পার্কিং সিস্টেম', 'ifs-parking' ) );
	$system_phone = get_option( 'ifs_parking_contact_phone', '+৮৮০ ১৭০০-০০০০০০' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$has_logs_table = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_logs ) ) === $table_logs );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$has_slots_table = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_slots ) ) === $table_slots );

	// ফাইনাল রিলিজ সম্পন্ন করার পোস্ট হ্যান্ডলার (সার্ভার-সাইড ২-ঘণ্টা স্ল্যাব ভেরিফিকেশনসহ)।
	if (
		$has_logs_table &&
		isset( $_POST['ifs_confirm_release_nonce'] ) &&
		wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ifs_confirm_release_nonce'] ) ), 'ifs_confirm_release' ) &&
		current_user_can( 'read' )
	) {
		$rel_id = isset( $_POST['rel_log_id'] ) ? absint( $_POST['rel_log_id'] ) : 0;

		if ( $rel_id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$log_item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_logs}` WHERE id = %d", $rel_id ) );

			if ( $log_item && 'Parked' === $log_item->status ) {
				// সার্ভার-সাইড থেকেও সঠিক ২-ঘণ্টা স্ল্যাব বিল হিসাব করা যাতে কোনো গরমিল না হয়।
				$entry_ts       = strtotime( $log_item->entry_time );
				$now_ts         = current_time( 'timestamp' );
				$diff_secs      = $entry_ts ? max( 0, $now_ts - $entry_ts ) : 0;
				$server_calc    = ifs_parking_calculate_2hr_slab_fee( $diff_secs, (float) $log_item->fee, $log_item->parking_type );
				$calculated_fee = $server_calc['fee'];

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table_logs,
					array(
						'exit_time' => current_time( 'mysql' ),
						'fee'       => $calculated_fee,
						'status'    => 'Exited',
					),
					array( 'id' => $rel_id ),
					array( '%s', '%f', '%s' ),
					array( '%d' )
				);

				if ( $has_slots_table && ! empty( $log_item->slot_number ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->update(
						$table_slots,
						array( 'is_occupied' => 0 ),
						array( 'slot_name' => $log_item->slot_number ),
						array( '%d' ),
						array( '%s' )
					);
				}

				echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
						<p class="ifs-notice-text">
							<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
							' . esc_html(
								sprintf(
									/* translators: 1: গাড়ির নম্বর, 2: মোট ফি */
									__( 'গাড়ি (%1$s) সফলভাবে রিলিজ সম্পন্ন হয়েছে! মোট বিল: %2$s', 'ifs-parking' ),
									$log_item->vehicle_number,
									$currency_symbol . ifs_parking_live_bn_num( number_format( $calculated_fee, 2 ) )
								)
							) . '
						</p>
					  </div>';
			}
		}
	}

	// শুধুমাত্র 'Parked' থাকা গাড়িগুলো লোড।
	$active_parked = array();
	if ( $has_logs_table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$active_parked = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table_logs}` WHERE status = %s ORDER BY entry_time DESC",
				'Parked'
			)
		);
	}

	$now_ts        = current_time( 'timestamp' );
	$overdue_count = 0;
	foreach ( $active_parked as $p_item ) {
		$e_ts = strtotime( $p_item->entry_time );
		if ( $e_ts && ( $now_ts - $e_ts ) > 7200 ) {
			$overdue_count++;
		}
	}
	$total_parked = count( $active_parked );
	?>
	<style>
		/* লাইভ স্ট্যাটাস জিরো-ব্লিঙ্ক স্মুথ টাইমার, ফিল্টার ট্যাব ও বাংলা টাইপোগ্রাফি */
		.ifs-live-status-wrapper {
			margin: 10px 15px 20px 0;
			box-sizing: border-box;
			font-family: 'Hind Siliguri', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
		}
		.ifs-live-banner {
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 16px;
			margin-bottom: 20px;
		}
		.ifs-banner-actions {
			display: flex;
			align-items: center;
			gap: 10px;
			flex-wrap: wrap;
		}
		.ifs-auto-refresh-badge,
		.ifs-duration-pill,
		.ifs-live-counter,
		.ifs-live-fee-badge,
		#ifs-refresh-timer {
			animation: none !important;
			opacity: 1 !important;
			font-variant-numeric: tabular-nums;
			-webkit-font-smoothing: antialiased;
		}
		.ifs-auto-refresh-badge {
			position: relative;
			overflow: hidden;
		}
		.ifs-refresh-progress-bar {
			position: absolute;
			bottom: 0;
			left: 0;
			height: 3px;
			width: 100%;
			background: #0284c7;
			transform-origin: left center;
			will-change: transform;
		}
		/* ইনস্ট্যান্ট ফিল্টার পিলস (Active / Overdue) */
		.ifs-filter-pills {
			display: flex;
			align-items: center;
			gap: 8px;
			flex-wrap: wrap;
		}
		.ifs-filter-pill-btn {
			border: 1.5px solid #cbd5e1;
			background: #ffffff;
			color: #334155;
			font-size: 13px;
			font-weight: 600;
			padding: 6px 12px;
			border-radius: 99px;
			cursor: pointer;
			transition: all 0.18s ease;
		}
		.ifs-filter-pill-btn.active-all {
			background: #0284c7;
			border-color: #0284c7;
			color: #ffffff;
		}
		.ifs-filter-pill-btn.active-parked {
			background: #16a34a;
			border-color: #16a34a;
			color: #ffffff;
		}
		.ifs-filter-pill-btn.active-overdue {
			background: #d97706;
			border-color: #d97706;
			color: #ffffff;
		}
		.ifs-table-toolbar {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 16px 20px;
			border-bottom: 1px solid #e2e8f0;
			flex-wrap: wrap;
			gap: 14px;
		}
		.ifs-search-container {
			position: relative;
			width: 280px;
		}
		.ifs-table-responsive {
			width: 100%;
			overflow-x: auto;
			-webkit-overflow-scrolling: touch;
		}
		.ifs-custom-live-table {
			min-width: 840px;
		}
		.ifs-modal-backdrop {
			display: none;
			position: fixed;
			top: 0;
			left: 0;
			width: 100vw;
			height: 100vh;
			background: rgba(15, 23, 42, 0.65);
			backdrop-filter: blur(4px);
			z-index: 999999;
			align-items: center;
			justify-content: center;
			padding: 16px;
			box-sizing: border-box;
			opacity: 0;
			transition: opacity 0.22s ease;
		}
		.ifs-modal-backdrop.ifs-modal-open {
			opacity: 1;
		}
		.ifs-modal-card {
			background: #ffffff;
			border-radius: 14px;
			max-width: 440px;
			width: 100%;
			max-height: 90vh;
			overflow-y: auto;
			-webkit-overflow-scrolling: touch;
			box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
			padding: 20px;
			box-sizing: border-box;
			transform: translateY(10px) scale(0.98);
			transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
		}
		.ifs-modal-backdrop.ifs-modal-open .ifs-modal-card {
			transform: translateY(0) scale(1);
		}
		.ifs-modal-actions {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-top: 18px;
			gap: 10px;
			flex-wrap: wrap;
		}
		.ifs-btn-group-end {
			display: flex;
			gap: 8px;
			align-items: center;
		}

		@media print {
			body * {
				visibility: hidden;
			}
			#ifs-printable-exit-slip,
			#ifs-printable-exit-slip * {
				visibility: visible;
			}
			#ifs-printable-exit-slip {
				position: fixed;
				left: 0;
				top: 0;
				width: 100%;
				padding: 16px;
				background: #ffffff;
			}
		}

		@media screen and (max-width: 782px) {
			.ifs-live-status-wrapper {
				margin: 10px 5px 20px 0;
			}
			.ifs-live-banner {
				flex-direction: column;
				align-items: stretch;
			}
			.ifs-banner-actions {
				width: 100%;
				flex-direction: column;
				align-items: stretch;
			}
			.ifs-auto-refresh-badge,
			.ifs-btn-refresh,
			.ifs-btn-new-entry {
				width: 100%;
				justify-content: center;
				height: 44px;
				box-sizing: border-box;
			}
			.ifs-table-toolbar {
				flex-direction: column;
				align-items: stretch;
				padding: 14px;
			}
			.ifs-search-container {
				width: 100%;
			}
			.ifs-live-search {
				height: 44px;
				font-size: 16px !important;
				box-sizing: border-box;
			}
			.ifs-modal-card {
				padding: 16px 14px;
			}
			.ifs-modal-actions {
				flex-direction: column;
				align-items: stretch;
			}
			.ifs-modal-actions .ifs-btn-print {
				width: 100%;
				justify-content: center;
				height: 44px;
				margin-bottom: 6px;
			}
			.ifs-btn-group-end {
				width: 100%;
				flex-direction: column;
				gap: 8px;
			}
			.ifs-btn-group-end .ifs-btn-cancel,
			.ifs-btn-group-end .ifs-form-inline,
			.ifs-btn-group-end .ifs-btn-confirm-release {
				width: 100%;
				justify-content: center;
				height: 44px;
				box-sizing: border-box;
			}
		}
	</style>

	<div class="wrap ifs-live-status-wrapper">
		<!-- প্রিমিয়াম লাইভ ব্যানার -->
		<div class="ifs-live-banner">
			<div class="ifs-banner-info">
				<span class="ifs-live-header-badge">
					<span class="ifs-live-dot"></span>
					<?php esc_html_e( 'রিয়েল-টাইম লাইভ মনিটর (প্রতি ২ ঘণ্টা স্ল্যাব)', 'ifs-parking' ); ?>
				</span>
				<h1 class="ifs-banner-title"><?php esc_html_e( 'লাইভ পার্কিং স্ট্যাটাস', 'ifs-parking' ); ?></h1>
				<p class="ifs-banner-subtitle"><?php esc_html_e( 'প্রতি ২ ঘণ্টা পর পর স্বয়ংক্রিয়ভাবে পরবর্তী স্ল্যাবের বিল আপডেট হবে।', 'ifs-parking' ); ?></p>
			</div>

			<div class="ifs-banner-actions">
				<div class="ifs-auto-refresh-badge">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg>
					<?php esc_html_e( 'অটো-রিফ্রেশ:', 'ifs-parking' ); ?>
					<span id="ifs-refresh-timer"><?php echo esc_html( ifs_parking_live_bn_num( '60' ) ); ?> <?php esc_html_e( 'সেকেন্ড', 'ifs-parking' ); ?></span>
					<span class="ifs-refresh-progress-bar" id="ifs-refresh-progress"></span>
				</div>

				<button type="button" id="ifs-manual-sync-btn" class="ifs-btn-refresh">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
					<?php esc_html_e( 'রিফ্রেশ', 'ifs-parking' ); ?>
				</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-vehicles&tab=add_vehicle' ) ); ?>" class="ifs-btn-new-entry">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
					<?php esc_html_e( 'নতুন এন্ট্রি', 'ifs-parking' ); ?>
				</a>
			</div>
		</div>

		<!-- মূল লাইভ টেবিল কার্ড -->
		<div class="ifs-card ifs-live-table-card">
			<div class="ifs-table-toolbar">
				<div class="ifs-filter-pills">
					<button type="button" class="ifs-filter-pill-btn active-all" data-filter="all">
						<?php esc_html_e( 'সকল তালিকা', 'ifs-parking' ); ?>
						(<span id="ifs-total-parked-count"><?php echo esc_html( ifs_parking_live_bn_num( $total_parked ) ); ?></span>)
					</button>
					<button type="button" class="ifs-filter-pill-btn" data-filter="active">
						<?php esc_html_e( 'নির্ধারিত সময়ে (২ ঘণ্টার মধ্যে)', 'ifs-parking' ); ?>
					</button>
					<button type="button" class="ifs-filter-pill-btn" data-filter="overdue">
						<?php esc_html_e( 'ওভারডিউ (২+ ঘণ্টা)', 'ifs-parking' ); ?>
						(<span id="ifs-overdue-count"><?php echo esc_html( ifs_parking_live_bn_num( $overdue_count ) ); ?></span>)
					</button>
				</div>

				<!-- লাইভ ফিল্টার সার্চ বক্স -->
				<div class="ifs-search-container">
					<svg class="ifs-search-icon" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
					<input type="text" id="ifs-search-input" class="ifs-live-search" placeholder="<?php esc_attr_e( 'গাড়ি নম্বর বা ডিপার্টমেন্ট খুঁজুন...', 'ifs-parking' ); ?>">
				</div>
			</div>

			<div class="ifs-table-responsive">
				<table class="wp-list-table widefat fixed striped ifs-custom-live-table" id="ifs-live-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'গাড়ি নম্বর', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'ডিপার্টমেন্ট', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'ক্যাটাগরি', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'চালক ও ফোন', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'প্রবেশের সময়', 'ifs-parking' ); ?></th>
							<th class="ifs-th-duration"><?php esc_html_e( 'সময়কাল ও চলতি বিল (লাইভ)', 'ifs-parking' ); ?></th>
							<th class="ifs-th-action"><?php esc_html_e( 'অ্যাকশন', 'ifs-parking' ); ?></th>
						</tr>
					</thead>
					<tbody id="ifs-live-tbody">
						<?php ifs_parking_render_live_table_rows( $active_parked, $currency_symbol ); ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<!-- রিলিজ ও অটো-ক্যালকুলেশন প্রিন্ট মডাল -->
	<div id="ifs-release-modal" class="ifs-modal-backdrop">
		<div class="ifs-modal-card">
			<div id="ifs-printable-exit-slip" class="ifs-receipt-wrapper">
				<div class="ifs-receipt-header">
					<h3 class="ifs-receipt-title"><?php echo esc_html( $site_title ); ?></h3>
					<div class="ifs-receipt-phone">
						<svg class="ifs-svg-xxs" viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
						<?php esc_html_e( 'হেল্পলাইন:', 'ifs-parking' ); ?> <?php echo esc_html( ifs_parking_live_bn_num( $system_phone ) ); ?>
					</div>
					<p class="ifs-receipt-subtitle"><?php esc_html_e( 'এক্সিট স্লিপ ও বিলিং রসিদ', 'ifs-parking' ); ?></p>
				</div>

				<div class="ifs-receipt-body">
					<div class="ifs-receipt-row">
						<span class="ifs-receipt-lbl"><?php esc_html_e( 'গাড়ি নম্বর:', 'ifs-parking' ); ?></span>
						<strong id="m-veh" class="ifs-receipt-val-highlight">-</strong>
					</div>
					<div class="ifs-receipt-row">
						<span class="ifs-receipt-lbl"><?php esc_html_e( 'ডিপার্টমেন্ট / ক্যাটাগরি:', 'ifs-parking' ); ?></span>
						<span id="m-dept-cat" class="ifs-receipt-val-bold">-</span>
					</div>
					<div class="ifs-receipt-row">
						<span class="ifs-receipt-lbl"><?php esc_html_e( 'প্রবেশের সময়:', 'ifs-parking' ); ?></span>
						<span id="m-entry">-</span>
					</div>
					<div class="ifs-receipt-row">
						<span class="ifs-receipt-lbl"><?php esc_html_e( 'রিলিজের সময়:', 'ifs-parking' ); ?></span>
						<span id="m-exit" class="ifs-receipt-val-bold">-</span>
					</div>
					<div class="ifs-receipt-row ifs-receipt-highlight-amber">
						<span class="ifs-receipt-lbl-amber"><?php esc_html_e( 'মোট সময়কাল:', 'ifs-parking' ); ?></span>
						<strong id="m-duration" class="ifs-receipt-val-amber">-</strong>
					</div>
					<div class="ifs-receipt-row ifs-receipt-highlight-green">
						<span class="ifs-receipt-lbl-green"><?php esc_html_e( 'মোট প্রদেয় ফি (২ ঘণ্টা স্ল্যাব):', 'ifs-parking' ); ?></span>
						<strong id="m-total-fee" class="ifs-receipt-val-green">-</strong>
					</div>
					<div class="ifs-receipt-row">
						<span class="ifs-receipt-lbl"><?php esc_html_e( 'চালক ও ফোন:', 'ifs-parking' ); ?></span>
						<span id="m-driver">-</span>
					</div>
				</div>

				<div class="ifs-receipt-footer">
					<p class="ifs-receipt-thankyou"><?php esc_html_e( 'আমাদের পার্কিং সুবিধা ব্যবহার করার জন্য ধন্যবাদ!', 'ifs-parking' ); ?></p>
				</div>
			</div>

			<div class="ifs-no-print ifs-modal-actions">
				<button type="button" id="ifs-manual-print-btn" class="ifs-btn-print">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
					<?php esc_html_e( 'রসিদ প্রিন্ট করুন', 'ifs-parking' ); ?>
				</button>

				<div class="ifs-btn-group-end">
					<button type="button" id="ifs-close-modal-btn" class="ifs-btn-cancel"><?php esc_html_e( 'বাতিল', 'ifs-parking' ); ?></button>

					<form method="post" action="" class="ifs-form-inline">
						<?php wp_nonce_field( 'ifs_confirm_release', 'ifs_confirm_release_nonce' ); ?>
						<input type="hidden" name="rel_log_id" id="ifs-form-rel-id" value="">
						<input type="hidden" name="final_fee" id="ifs-form-final-fee" value="">
						<button type="submit" class="ifs-btn-confirm-release">
							<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
							<?php esc_html_e( 'রিলিজ নিশ্চিত করুন', 'ifs-parking' ); ?>
						</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- জিরো-ব্লিঙ্ক আল্ট্রা-স্মুথ ২-ঘণ্টা স্ল্যাব লাইভ কাউন্টার ও সাইলেন্ট রিফ্রেশ স্ক্রিপ্ট -->
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		var currency     = <?php echo wp_json_encode( $currency_symbol ); ?>;
		var ajaxUrl      = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
		var ajaxNonce    = <?php echo wp_json_encode( wp_create_nonce( 'ifs_parking_admin_nonce' ) ); ?>;
		var activeFilter = 'all';

		var i18nLabels = {
			hour: <?php echo wp_json_encode( __( 'ঘণ্টা', 'ifs-parking' ) ); ?>,
			minute: <?php echo wp_json_encode( __( 'মিনিট', 'ifs-parking' ) ); ?>,
			second: <?php echo wp_json_encode( __( 'সেকেন্ড', 'ifs-parking' ) ); ?>,
			slab: <?php echo wp_json_encode( __( 'স্ল্যাব', 'ifs-parking' ) ); ?>,
			general: <?php echo wp_json_encode( __( 'সাধারণ', 'ifs-parking' ) ); ?>
		};

		var bnMap = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
		function toBnDigits(str) {
			return String(str).replace(/[0-9]/g, function(w) {
				return bnMap[+w];
			});
		}

		function padZero(num) {
			return num < 10 ? '0' + num : String(num);
		}

		function formatDuration(seconds) {
			var hrs  = Math.floor(seconds / 3600);
			var mins = Math.floor((seconds % 3600) / 60);
			var secs = seconds % 60;
			return (hrs > 0 ? toBnDigits(padZero(hrs)) + ' ' + i18nLabels.hour + ' ' : '') +
				   toBnDigits(padZero(mins)) + ' ' + i18nLabels.minute + ' ' +
				   toBnDigits(padZero(secs)) + ' ' + i18nLabels.second;
		}

		// প্রতি ২ ঘণ্টা (৭২০০ সেকেন্ড) স্ল্যাব অনুযায়ী লাইভ ফি হিসাব
		function calculate2HrSlabFee(diffSecs, catRaw, rate) {
			if (catRaw.indexOf('monthly') !== -1) {
				return { slabs: 1, fee: rate };
			}
			if (catRaw.indexOf('daily') !== -1) {
				var days = Math.max(1, Math.ceil(diffSecs / 86400));
				return { slabs: days, fee: days * rate };
			}
			var slabSecs = 7200; // ২ ঘণ্টা = ৭২০০ সেকেন্ড
			var slabs    = (diffSecs <= slabSecs) ? 1 : Math.ceil(diffSecs / slabSecs);
			return { slabs: slabs, fee: slabs * rate };
		}

		var counterNodes = [];
		function indexCounterNodes() {
			counterNodes = Array.prototype.slice.call(document.querySelectorAll('.ifs-live-counter')).map(function(el) {
				var iso    = el.getAttribute('data-entry');
				var row    = el.closest('tr');
				var feeEl  = row ? row.querySelector('.ifs-live-fee-badge') : null;
				return {
					el: el,
					feeEl: feeEl,
					entryMs: iso ? new Date(iso).getTime() : 0,
					rate: parseFloat(el.getAttribute('data-rate')) || 0,
					catRaw: (el.getAttribute('data-cat-raw') || 'hourly').toLowerCase(),
					lastText: el.textContent,
					lastSlabs: -1
				};
			});
		}
		indexCounterNodes();

		var activeModalData     = null;
		var REFRESH_DURATION_MS = 60000;
		var cycleStartPerf      = performance.now();
		var timerEl             = document.getElementById('ifs-refresh-timer');
		var progressEl          = document.getElementById('ifs-refresh-progress');
		var lastCountdownSec    = -1;
		var isFetching          = false;

		function applyFilters() {
			var searchInput = document.getElementById('ifs-search-input');
			var q = searchInput ? searchInput.value.toLowerCase().trim() : '';

			document.querySelectorAll('#ifs-live-table tbody tr.ifs-live-row').forEach(function(row) {
				var text      = row.textContent.toLowerCase();
				var rowStatus = row.getAttribute('data-status') || 'active';

				var matchSearch = (q === '' || text.indexOf(q) !== -1);
				var matchStatus = (activeFilter === 'all' || activeFilter === rowStatus);

				row.style.display = (matchSearch && matchStatus) ? '' : 'none';
			});
		}

		// ফিল্টার বাটন হ্যান্ডলার (All / Active / Overdue)
		document.querySelectorAll('.ifs-filter-pill-btn').forEach(function(btn) {
			btn.addEventListener('click', function() {
				activeFilter = this.getAttribute('data-filter') || 'all';
				document.querySelectorAll('.ifs-filter-pill-btn').forEach(function(b) {
					b.classList.remove('active-all', 'active-parked', 'active-overdue');
				});
				if (activeFilter === 'active') {
					this.classList.add('active-parked');
				} else if (activeFilter === 'overdue') {
					this.classList.add('active-overdue');
				} else {
					this.classList.add('active-all');
				}
				applyFilters();
			});
		});

		function silentRefreshTable() {
			if (isFetching) return;
			isFetching = true;

			var formData = new FormData();
			formData.append('action', 'ifs_parking_fetch_live_status');
			formData.append('nonce', ajaxNonce);

			fetch(ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData
			})
			.then(function(res) { return res.json(); })
			.then(function(response) {
				if (response && response.success && response.data) {
					var tbody     = document.getElementById('ifs-live-tbody');
					var countEl   = document.getElementById('ifs-total-parked-count');
					var overdueEl = document.getElementById('ifs-overdue-count');
					if (tbody) {
						tbody.innerHTML = response.data.html;
						indexCounterNodes();
						applyFilters();
					}
					if (countEl) countEl.textContent = response.data.count_bn;
					if (overdueEl) overdueEl.textContent = response.data.overdue_bn;
				}
			})
			.catch(function() {})
			.finally(function() {
				isFetching     = false;
				cycleStartPerf = performance.now();
			});
		}

		function smoothTicker(nowPerf) {
			var nowEpoch = Date.now();

			for (var i = 0; i < counterNodes.length; i++) {
				var item = counterNodes[i];
				if (item.entryMs > 0) {
					var diffSecs  = Math.max(0, Math.floor((nowEpoch - item.entryMs) / 1000));
					var formatted = formatDuration(diffSecs);
					if (formatted !== item.lastText) {
						item.el.textContent = formatted;
						item.lastText = formatted;
					}

					var slabInfo = calculate2HrSlabFee(diffSecs, item.catRaw, item.rate);
					if (item.feeEl && slabInfo.slabs !== item.lastSlabs) {
						item.lastSlabs = slabInfo.slabs;
						item.feeEl.textContent = currency + toBnDigits(slabInfo.fee.toFixed(2));
					}
				}
			}

			if (activeModalData) {
				var modalDiffSecs = Math.max(0, Math.floor((nowEpoch - activeModalData.entryMs) / 1000));
				var modalDurText  = formatDuration(modalDiffSecs);
				if (modalDurText !== activeModalData.lastDurText) {
					activeModalData.lastDurText = modalDurText;
					var slabCalc   = calculate2HrSlabFee(modalDiffSecs, activeModalData.catRaw, activeModalData.rate);
					var totalFeeEn = slabCalc.fee.toFixed(2);

					document.getElementById('m-exit').textContent      = toBnDigits(new Date(nowEpoch).toLocaleString('bn-BD'));
					document.getElementById('m-duration').textContent  = modalDurText + ' (' + toBnDigits(slabCalc.slabs) + ' ' + i18nLabels.slab + ')';
					document.getElementById('m-total-fee').textContent = currency + toBnDigits(totalFeeEn);
					document.getElementById('ifs-form-final-fee').value = totalFeeEn;
				}
			}

			var elapsedMs   = nowPerf - cycleStartPerf;
			var remainingMs = Math.max(0, REFRESH_DURATION_MS - elapsedMs);

			if (progressEl) {
				var ratio = remainingMs / REFRESH_DURATION_MS;
				progressEl.style.transform = 'scaleX(' + ratio.toFixed(4) + ')';
			}

			var remainingSec = Math.ceil(remainingMs / 1000);
			if (timerEl && remainingSec !== lastCountdownSec) {
				lastCountdownSec = remainingSec;
				timerEl.textContent = toBnDigits(padZero(remainingSec)) + ' ' + i18nLabels.second;
			}

			if (remainingMs <= 0 && !isFetching) {
				silentRefreshTable();
			}

			requestAnimationFrame(smoothTicker);
		}

		requestAnimationFrame(smoothTicker);

		var manualSyncBtn = document.getElementById('ifs-manual-sync-btn');
		if (manualSyncBtn) {
			manualSyncBtn.addEventListener('click', silentRefreshTable);
		}

		var searchInput = document.getElementById('ifs-search-input');
		if (searchInput) {
			searchInput.addEventListener('input', applyFilters);
		}

		var modal    = document.getElementById('ifs-release-modal');
		var closeBtn = document.getElementById('ifs-close-modal-btn');
		var printBtn = document.getElementById('ifs-manual-print-btn');

		function openModal() {
			modal.style.display = 'flex';
			requestAnimationFrame(function() {
				modal.classList.add('ifs-modal-open');
			});
		}

		function closeModal() {
			activeModalData = null;
			modal.classList.remove('ifs-modal-open');
			setTimeout(function() {
				modal.style.display = 'none';
			}, 220);
		}

		document.addEventListener('click', function(e) {
			var btn = e.target.closest('.ifs-trigger-release');
			if (!btn) return;

			var logId    = btn.getAttribute('data-id');
			var veh      = btn.getAttribute('data-veh');
			var dept     = btn.getAttribute('data-dept');
			var cat      = btn.getAttribute('data-cat');
			var catRaw   = (btn.getAttribute('data-cat-raw') || 'hourly').toLowerCase();
			var driver   = btn.getAttribute('data-driver');
			var phone    = btn.getAttribute('data-phone');
			var entryIso = btn.getAttribute('data-entry');
			var entryFmt = btn.getAttribute('data-entry-formatted');
			var rate     = parseFloat(btn.getAttribute('data-rate')) || 0;
			var entryMs  = new Date(entryIso).getTime();

			activeModalData = {
				entryMs: entryMs,
				catRaw: catRaw,
				rate: rate,
				lastDurText: ''
			};

			var diffSecs   = Math.max(0, Math.floor((Date.now() - entryMs) / 1000));
			var slabCalc   = calculate2HrSlabFee(diffSecs, catRaw, rate);
			var totalFeeEn = slabCalc.fee.toFixed(2);

			document.getElementById('m-veh').textContent        = veh || '-';
			document.getElementById('m-dept-cat').textContent   = (dept ? dept : i18nLabels.general) + ' / ' + (cat ? cat : '-');
			document.getElementById('m-entry').textContent      = entryFmt || '-';
			document.getElementById('m-exit').textContent       = toBnDigits(new Date().toLocaleString('bn-BD'));
			document.getElementById('m-duration').textContent   = formatDuration(diffSecs) + ' (' + toBnDigits(slabCalc.slabs) + ' ' + i18nLabels.slab + ')';
			document.getElementById('m-total-fee').textContent  = currency + toBnDigits(totalFeeEn);
			document.getElementById('m-driver').textContent     = (driver ? driver : '-') + (phone ? ' (' + phone + ')' : '');

			document.getElementById('ifs-form-rel-id').value    = logId;
			document.getElementById('ifs-form-final-fee').value = totalFeeEn;

			openModal();
		});

		if (closeBtn) closeBtn.addEventListener('click', closeModal);
		if (printBtn) printBtn.addEventListener('click', function() { window.print(); });
		window.addEventListener('click', function(e) {
			if (e.target === modal) closeModal();
		});
	});
	</script>
	<?php
}