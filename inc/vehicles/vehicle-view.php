<?php
/**
 * Vehicle Details View Template.
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
function ifs_parking_view_bn_num( $number ) {
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
function ifs_parking_view_format_duration_bn( $seconds ) {
	$secs  = max( 0, (int) $seconds );
	$hours = (int) floor( $secs / 3600 );
	$mins  = (int) floor( ( $secs % 3600 ) / 60 );

	$parts = array();
	if ( $hours > 0 ) {
		$parts[] = sprintf(
			/* translators: %s: ঘণ্টার সংখ্যা */
			__( '%s ঘণ্টা', 'ifs-parking' ),
			ifs_parking_view_bn_num( $hours )
		);
	}
	if ( $mins > 0 || empty( $parts ) ) {
		$parts[] = sprintf(
			/* translators: %s: মিনিটের সংখ্যা */
			__( '%s মিনিট', 'ifs-parking' ),
			ifs_parking_view_bn_num( $mins )
		);
	}

	return implode( ' ', $parts );
}

/**
 * একক গাড়ির বিস্তারিত বিবরণ রেন্ডারার।
 *
 * @param string $table_logs      লগ টেবিল নাম।
 * @param int    $log_id          লগ আইডি।
 * @param string $currency_symbol কারেন্সি প্রতীক।
 * @return void
 */
function ifs_parking_render_vehicle_view( $table_logs, $log_id, $currency_symbol ) {
	global $wpdb;

	$log_id  = absint( $log_id );
	$vehicle = null;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $log_id > 0 && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_logs ) ) === $table_logs ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$vehicle = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_logs}` WHERE id = %d", $log_id ) );
	}

	if ( ! $vehicle ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'কোনো গাড়ি বা লগ রেকর্ড পাওয়া যায়নি!', 'ifs-parking' ) . '</p></div>';
		return;
	}

	$bd_tz             = new DateTimeZone( 'Asia/Dhaka' );
	$overdue_threshold = 7800; // ২ ঘণ্টা ১০ মিনিট (2 * 3600 + 10 * 60 = 7800 সেকেন্ড)।
	$now_utc           = time();

	$back_url    = admin_url( 'admin.php?page=ifs-parking-vehicles&tab=all_vehicles' );
	$edit_url    = admin_url( 'admin.php?page=ifs-parking-vehicles&action=edit&id=' . $vehicle->id );
	$invoice_num = ! empty( $vehicle->reference_id )
		? strtok( $vehicle->reference_id, ' [' )
		: 'NPMS-' . str_pad( (string) $vehicle->id, 4, '0', STR_PAD_LEFT );

	// সিরিয়াল: বাইক -> কার -> সিএনজি -> সাইকেল -> নোয়াহ -> হাইয়েস -> ট্রাক
	$veh_labels_bd = array(
		'Bike'  => __( 'বাইক (Bike)', 'ifs-parking' ),
		'Car'   => __( 'কার (Car)', 'ifs-parking' ),
		'CNG'   => __( 'সিএনজি (CNG)', 'ifs-parking' ),
		'Cycle' => __( 'সাইকেল (Cycle)', 'ifs-parking' ),
		'Noah'  => __( 'নোয়াহ (Noah)', 'ifs-parking' ),
		'Hiace' => __( 'হাইয়েস (Hiace)', 'ifs-parking' ),
		'Truck' => __( 'ট্রাক (Truck)', 'ifs-parking' ),
	);

	$raw_type   = (string) $vehicle->parking_type;
	$slug_lc    = strtolower( trim( $raw_type ) );
	$type_key   = false !== strpos( $raw_type, '-' ) ? trim( ucfirst( strtok( $raw_type, '-' ) ) ) : ucfirst( trim( $raw_type ) );
	$type_label = isset( $veh_labels_bd[ $type_key ] ) ? $veh_labels_bd[ $type_key ] : $type_key;

	// ক্যাটাগরি রেট লোড।
	$table_cats  = $wpdb->prefix . 'ifs_parking_categories';
	$matched_cat = null;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( '' !== $slug_lc && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_cats ) ) === $table_cats ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$matched_cat = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_cats}` WHERE LOWER(category_slug) = %s ORDER BY id ASC LIMIT 1", $slug_lc ) );
	}

	$entry_ts    = 0;
	$entry_label = $vehicle->entry_time;
	if ( ! empty( $vehicle->entry_time ) ) {
		try {
			$dt_entry    = new DateTimeImmutable( $vehicle->entry_time, $bd_tz );
			$entry_ts    = $dt_entry->getTimestamp();
			$entry_label = ifs_parking_view_bn_num( wp_date( 'j F, Y - h:i A', $entry_ts, $bd_tz ) );
		} catch ( Exception $e ) {
			$entry_ts    = strtotime( $vehicle->entry_time ) ? (int) strtotime( $vehicle->entry_time ) : 0;
			$entry_label = $vehicle->entry_time;
		}
	}

	$exit_ts    = 0;
	$exit_label = __( 'এখনও পার্কিংয়ে আছে', 'ifs-parking' );
	if ( ! empty( $vehicle->exit_time ) && '0000-00-00 00:00:00' !== $vehicle->exit_time ) {
		try {
			$dt_exit    = new DateTimeImmutable( $vehicle->exit_time, $bd_tz );
			$exit_ts    = $dt_exit->getTimestamp();
			$exit_label = ifs_parking_view_bn_num( wp_date( 'j F, Y - h:i A', $exit_ts, $bd_tz ) );
		} catch ( Exception $e ) {
			$exit_ts    = strtotime( $vehicle->exit_time ) ? (int) strtotime( $vehicle->exit_time ) : 0;
			$exit_label = $vehicle->exit_time;
		}
	}

	$is_parked  = ( 'Parked' === $vehicle->status );
	$end_ts     = $is_parked ? $now_utc : ( $exit_ts ? $exit_ts : $now_utc );
	$diff_secs  = $entry_ts ? max( 0, $end_ts - $entry_ts ) : 0;
	$stay_label = ifs_parking_view_format_duration_bn( $diff_secs );
	$is_overdue = ( $diff_secs > $overdue_threshold );
	$ovd_label  = $is_overdue ? ifs_parking_view_format_duration_bn( $diff_secs - $overdue_threshold ) : __( 'নেই', 'ifs-parking' );

	$base_rate = (float) $vehicle->fee;
	$step_rate = ( $matched_cat && isset( $matched_cat->two_hour_rate ) && (float) $matched_cat->two_hour_rate > 0 )
		? (float) $matched_cat->two_hour_rate
		: ( ( $matched_cat && isset( $matched_cat->hourly_rate ) && (float) $matched_cat->hourly_rate > 0 ) ? (float) $matched_cat->hourly_rate : $base_rate );

	if ( $is_parked ) {
		if ( false !== strpos( $slug_lc, 'monthly' ) ) {
			$total_fee = round( $base_rate, 2 );
		} elseif ( false !== strpos( $slug_lc, 'daily' ) ) {
			$total_fee = round( max( 1, (int) ceil( $diff_secs / 86400 ) ) * $base_rate, 2 );
		} else {
			$tier      = ( $diff_secs <= $overdue_threshold ) ? 1 : (int) ceil( $diff_secs / $overdue_threshold );
			$total_fee = round( $tier * max( 0.00, $step_rate ), 2 );
		}
	} else {
		$total_fee = round( $base_rate, 2 );
	}

	$base_step_fee = ( $step_rate > 0 ) ? round( $step_rate, 2 ) : $total_fee;
	$overdue_fee   = $is_overdue ? max( 0.00, round( $total_fee - $base_step_fee, 2 ) ) : 0.00;
	?>
	<style>
		/* গাড়ির বিস্তারিত ভিউ রেসপনসিভনেস ও প্রমিত বাংলা টাইপোগ্রাফি */
		.ifs-vehicle-view-card {
			max-width: 820px;
			margin: 20px auto;
			padding: 28px;
			background: #ffffff;
			border-radius: 14px;
			box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
			box-sizing: border-box;
			font-family: 'Hind Siliguri', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
		}
		.ifs-veh-view-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			border-bottom: 1px solid #e2e8f0;
			padding-bottom: 15px;
			margin-bottom: 20px;
			flex-wrap: wrap;
			gap: 12px;
		}
		.ifs-veh-view-actions {
			display: flex;
			gap: 8px;
			align-items: center;
			flex-wrap: wrap;
		}
		.ifs-veh-view-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 14px;
			font-size: 14px;
			color: #334155;
		}
		.ifs-veh-view-grid .ifs-full-span {
			grid-column: span 2;
		}
		.ifs-view-info-box {
			background: #f8fafc;
			padding: 12px 14px;
			border-radius: 8px;
			border: 1px solid #e2e8f0;
			line-height: 1.5;
		}
		.ifs-view-info-box strong {
			color: #475569;
			font-size: 12.5px;
			display: inline-block;
			margin-bottom: 3px;
		}
		.ifs-nid-preview-img {
			max-width: 280px;
			max-height: 180px;
			border-radius: 8px;
			border: 1px solid #cbd5e1;
			margin-top: 8px;
			display: block;
		}

		/* ট্যাবলেট এবং মোবাইল ভিউপোর্ট রুলস */
		@media screen and (max-width: 782px) {
			.ifs-vehicle-view-card {
				margin: 10px 5px 20px 0;
				padding: 18px 14px;
				border-radius: 12px;
			}
			.ifs-veh-view-header {
				flex-direction: column;
				align-items: stretch;
			}
			.ifs-veh-view-actions {
				flex-direction: column;
				width: 100%;
			}
			.ifs-veh-view-actions a {
				width: 100%;
				text-align: center;
				height: 44px;
				display: flex;
				align-items: center;
				justify-content: center;
				box-sizing: border-box;
			}
			.ifs-veh-view-grid {
				grid-template-columns: 1fr;
				gap: 10px;
			}
			.ifs-veh-view-grid .ifs-full-span {
				grid-column: span 1;
			}
		}
	</style>

	<div class="ifs-card ifs-vehicle-view-card">
		<div class="ifs-veh-view-header">
			<h3 style="margin: 0; font-size: 20px; color: #0f172a; display: flex; align-items: center; gap: 8px;">
				<svg viewBox="0 0 24 24" style="width:22px;height:22px;fill:#0284c7;"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
				<?php esc_html_e( 'গাড়ির বিস্তারিত বিবরণ', 'ifs-parking' ); ?>
				<span style="font-size: 14px; color: #64748b;">(#<?php echo esc_html( ifs_parking_view_bn_num( $vehicle->id ) ); ?>)</span>
			</h3>

			<div class="ifs-veh-view-actions">
				<a href="<?php echo esc_url( $edit_url ); ?>" style="background: #0284c7; color: #ffffff; padding: 7px 14px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">
					<?php esc_html_e( 'তথ্য সম্পাদনা', 'ifs-parking' ); ?>
				</a>
				<a href="<?php echo esc_url( $back_url ); ?>" style="background: #e2e8f0; color: #334155; padding: 7px 14px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">
					&larr; <?php esc_html_e( 'তালিকায় ফিরে যান', 'ifs-parking' ); ?>
				</a>
			</div>
		</div>

		<div class="ifs-veh-view-grid">
			<div class="ifs-view-info-box">
				<strong><?php esc_html_e( 'ইনভয়েস নং:', 'ifs-parking' ); ?></strong><br>
				<span style="color: #0284c7; font-weight: 700;"><?php echo esc_html( $invoice_num ); ?></span>
			</div>

			<div class="ifs-view-info-box">
				<strong><?php esc_html_e( 'গাড়ি নম্বর:', 'ifs-parking' ); ?></strong><br>
				<span style="font-size: 16px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $vehicle->vehicle_number ); ?></span>
			</div>

			<div class="ifs-view-info-box">
				<strong><?php esc_html_e( 'ডিপার্টমেন্ট / রেফারেন্স:', 'ifs-parking' ); ?></strong><br>
				<span><?php echo esc_html( ! empty( $vehicle->reference_id ) ? $vehicle->reference_id : __( 'সাধারণ', 'ifs-parking' ) ); ?></span>
			</div>

			<div class="ifs-view-info-box">
				<strong><?php esc_html_e( 'ক্যাটাগরি / গাড়ির ধরণ:', 'ifs-parking' ); ?></strong><br>
				<span><?php echo esc_html( $type_label ); ?></span>
			</div>

			<div class="ifs-view-info-box">
				<strong><?php esc_html_e( 'প্রথম ধাপ (২.১০ ঘণ্টা) ফি:', 'ifs-parking' ); ?></strong><br>
				<span style="color: #0369a1; font-weight: 700; font-size: 15px;">
					<?php echo esc_html( $currency_symbol . ifs_parking_view_bn_num( number_format_i18n( $base_step_fee, 2 ) ) ); ?>
				</span>
			</div>

			<div class="ifs-view-info-box">
				<strong><?php esc_html_e( 'ওভারডিউ ফি:', 'ifs-parking' ); ?></strong><br>
				<span style="color: <?php echo $overdue_fee > 0 ? '#b45309' : '#475569'; ?>; font-weight: 700; font-size: 15px;">
					<?php echo esc_html( $currency_symbol . ifs_parking_view_bn_num( number_format_i18n( $overdue_fee, 2 ) ) ); ?>
				</span>
			</div>

			<div class="ifs-view-info-box">
				<strong><?php esc_html_e( 'সর্বমোট বিল:', 'ifs-parking' ); ?></strong><br>
				<span style="color: #059669; font-weight: 800; font-size: 16px;">
					<?php echo esc_html( $currency_symbol . ifs_parking_view_bn_num( number_format_i18n( $total_fee, 2 ) ) ); ?>
				</span>
			</div>

			<div class="ifs-view-info-box">
				<strong><?php esc_html_e( 'পার্কিং স্ট্যাটাস:', 'ifs-parking' ); ?></strong><br>
				<?php if ( $is_parked ) : ?>
					<span style="color: <?php echo $is_overdue ? '#b45309' : '#d97706'; ?>; font-weight: 700;">
						<?php echo esc_html( $is_overdue ? __( 'ওভারডিউ (পার্ক করা আছে)', 'ifs-parking' ) : __( 'পার্ক করা আছে', 'ifs-parking' ) ); ?>
					</span>
				<?php else : ?>
					<span style="color: #059669; font-weight: 700;">
						<?php echo esc_html( $is_overdue ? __( 'ওভারডিউ (বের হয়ে গেছে)', 'ifs-parking' ) : __( 'বের হয়ে গেছে (সম্পন্ন)', 'ifs-parking' ) ); ?>
					</span>
				<?php endif; ?>
			</div>

			<div class="ifs-view-info-box">
				<strong><?php esc_html_e( 'চালকের নাম:', 'ifs-parking' ); ?></strong><br>
				<span><?php echo esc_html( ! empty( $vehicle->driver_name ) ? $vehicle->driver_name : __( 'তথ্য নেই', 'ifs-parking' ) ); ?></span>
			</div>

			<div class="ifs-view-info-box">
				<strong><?php esc_html_e( 'চালকের ফোন:', 'ifs-parking' ); ?></strong><br>
				<span><?php echo esc_html( ! empty( $vehicle->driver_phone ) ? ifs_parking_view_bn_num( $vehicle->driver_phone ) : __( 'তথ্য নেই', 'ifs-parking' ) ); ?></span>
			</div>

			<div class="ifs-view-info-box">
				<strong><?php esc_html_e( 'প্রবেশের সময়:', 'ifs-parking' ); ?></strong><br>
				<span><?php echo esc_html( $entry_label ); ?></span>
			</div>

			<div class="ifs-view-info-box">
				<strong><?php esc_html_e( 'বের হওয়ার সময়:', 'ifs-parking' ); ?></strong><br>
				<span><?php echo esc_html( $exit_label ); ?></span>
			</div>

			<div class="ifs-view-info-box">
				<strong><?php esc_html_e( 'মোট অবস্থান সময়:', 'ifs-parking' ); ?></strong><br>
				<span><?php echo esc_html( $stay_label ); ?></span>
			</div>

			<div class="ifs-view-info-box">
				<strong><?php esc_html_e( 'ওভারডিউ সময়:', 'ifs-parking' ); ?></strong><br>
				<span style="color: <?php echo $is_overdue ? '#b45309' : '#475569'; ?>; font-weight: <?php echo $is_overdue ? '700' : '400'; ?>;">
					<?php echo esc_html( $ovd_label ); ?>
				</span>
			</div>

			<?php if ( ! empty( $vehicle->slot_number ) || ! empty( $vehicle->membership_expiry ) ) : ?>
				<div class="ifs-view-info-box">
					<strong><?php esc_html_e( 'বরাদ্দকৃত স্লট নম্বর:', 'ifs-parking' ); ?></strong><br>
					<span><?php echo esc_html( ! empty( $vehicle->slot_number ) ? $vehicle->slot_number : __( 'নির্ধারিত নয়', 'ifs-parking' ) ); ?></span>
				</div>

				<div class="ifs-view-info-box">
					<strong><?php esc_html_e( 'মেম্বারশিপ মেয়াদ:', 'ifs-parking' ); ?></strong><br>
					<span><?php echo esc_html( ! empty( $vehicle->membership_expiry ) ? ifs_parking_view_bn_num( $vehicle->membership_expiry ) : __( 'প্রযোজ্য নয়', 'ifs-parking' ) ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $vehicle->nid_path ) ) : ?>
				<div class="ifs-view-info-box ifs-full-span">
					<strong><?php esc_html_e( 'সংযুক্ত এনআইডি (NID) ডকুমেন্ট:', 'ifs-parking' ); ?></strong><br>
					<?php
					$file_ext = strtolower( pathinfo( wp_parse_url( $vehicle->nid_path, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
					if ( 'pdf' === $file_ext ) :
						?>
						<a href="<?php echo esc_url( $vehicle->nid_path ); ?>" target="_blank" rel="noopener noreferrer" style="display: inline-block; margin-top: 6px; padding: 6px 12px; background: #e0f2fe; color: #0284c7; border-radius: 6px; text-decoration: none; font-weight: 600;">
							<?php esc_html_e( 'পিডিএফ (PDF) ডকুমেন্ট দেখুন', 'ifs-parking' ); ?>
						</a>
					<?php else : ?>
						<a href="<?php echo esc_url( $vehicle->nid_path ); ?>" target="_blank" rel="noopener noreferrer">
							<img src="<?php echo esc_url( $vehicle->nid_path ); ?>" alt="<?php esc_attr_e( 'চালকের এনআইডি', 'ifs-parking' ); ?>" class="ifs-nid-preview-img">
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}