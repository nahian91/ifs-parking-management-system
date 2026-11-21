<?php
/**
 * Easy & Flexible Reports & Analytics Module (Clean Print/PDF Window & Reliable Excel Export).
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
function ifs_parking_reports_bn_num( $number ) {
	if ( function_exists( 'ifs_parking_to_bn_number' ) ) {
		return ifs_parking_to_bn_number( $number );
	}

	$en_digits = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	$bn_digits = array( '০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯' );

	return str_replace( $en_digits, $bn_digits, (string) $number );
}

/**
 * রিপোর্ট ও অ্যানালিটিক্স পেজ রেন্ডারার।
 *
 * @return void
 */
function ifs_parking_render_reports_page() {
	if ( function_exists( 'ifs_parking_render_layout_wrapper' ) ) {
		ifs_parking_render_layout_wrapper( 'ifs_parking_render_reports_content' );
	} else {
		ifs_parking_render_reports_content();
	}
}

/**
 * সহজ ও ফ্লেক্সিবল রিপোর্ট কনটেন্ট রেন্ডারার।
 *
 * @return void
 */
function ifs_parking_render_reports_content() {
	global $wpdb;
	$table_logs = $wpdb->prefix . 'ifs_parking_logs';
	$table_cats = $wpdb->prefix . 'ifs_parking_categories';

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

	$site_title   = get_option( 'ifs_parking_site_title', __( 'আইএফএস পার্কিং সিস্টেম', 'ifs-parking' ) );
	$system_phone = get_option( 'ifs_parking_contact_phone', '+৮৮০ ১৭০০-০০০০০০' );

	// সহজ ও আলাদা তারিখ + সময় ফিল্টার।
	$today_str = current_time( 'Y-m-d' );

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$start_date  = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : $today_str;
	$end_date    = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : $today_str;
	$start_time  = isset( $_GET['start_time'] ) ? sanitize_text_field( wp_unslash( $_GET['start_time'] ) ) : '00:00';
	$end_time    = isset( $_GET['end_time'] ) ? sanitize_text_field( wp_unslash( $_GET['end_time'] ) ) : '23:59';
	$filter_veh  = isset( $_GET['veh_type'] ) ? sanitize_text_field( wp_unslash( $_GET['veh_type'] ) ) : '';
	$filter_dept = isset( $_GET['dept'] ) ? sanitize_text_field( wp_unslash( $_GET['dept'] ) ) : '';
	$filter_stat = isset( $_GET['log_status'] ) ? sanitize_text_field( wp_unslash( $_GET['log_status'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
		$start_date = $today_str;
	}
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
		$end_date = $today_str;
	}
	if ( ! preg_match( '/^\d{2}:\d{2}$/', $start_time ) ) {
		$start_time = '00:00';
	}
	if ( ! preg_match( '/^\d{2}:\d{2}$/', $end_time ) ) {
		$end_time = '23:59';
	}

	if ( strtotime( $end_date ) < strtotime( $start_date ) ) {
		$end_date = $start_date;
	}

	$start_time_sql = $start_time . ':00';
	$end_time_sql   = $end_time . ':59';
	$is_overnight   = ( strtotime( $start_time_sql ) > strtotime( $end_time_sql ) );

	// ফিল্টার ড্রপডাউনের জন্য ক্যাটাগরি ও ডিপার্টমেন্ট ফেচ।
	$veh_types_list = array();
	$depts_list     = array();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_cats ) ) === $table_cats ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$cats_meta      = $wpdb->get_results( "SELECT DISTINCT vehicle_type, department FROM `{$table_cats}`" );
		$veh_types_list = array_values( array_unique( array_filter( wp_list_pluck( $cats_meta, 'vehicle_type' ) ) ) );
		$depts_list     = array_values( array_unique( array_filter( wp_list_pluck( $cats_meta, 'department' ) ) ) );
	}

	$summary         = array();
	$hourly_rows     = array();
	$daily_rows      = array();
	$detailed_logs   = array();
	$total_veh_count = 0;
	$total_rev_sum   = 0.0;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_logs ) ) === $table_logs ) {
		$where_clauses = array(
			'DATE(entry_time) >= %s',
			'DATE(entry_time) <= %s',
		);
		$params        = array( $start_date, $end_date );

		if ( $is_overnight ) {
			$where_clauses[] = '(TIME(entry_time) >= %s OR TIME(entry_time) <= %s)';
			$params[]        = $start_time_sql;
			$params[]        = $end_time_sql;
		} else {
			$where_clauses[] = 'TIME(entry_time) >= %s';
			$where_clauses[] = 'TIME(entry_time) <= %s';
			$params[]        = $start_time_sql;
			$params[]        = $end_time_sql;
		}

		if ( ! empty( $filter_veh ) ) {
			$where_clauses[] = 'parking_type LIKE %s';
			$params[]        = '%' . $wpdb->esc_like( strtolower( $filter_veh ) ) . '%';
		}

		if ( ! empty( $filter_dept ) ) {
			$where_clauses[] = 'reference_id LIKE %s';
			$params[]        = '%' . $wpdb->esc_like( $filter_dept ) . '%';
		}

		if ( ! empty( $filter_stat ) && in_array( $filter_stat, array( 'Parked', 'Exited' ), true ) ) {
			$where_clauses[] = 'status = %s';
			$params[]        = $filter_stat;
		}

		$where_sql = implode( ' AND ', $where_clauses );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$summary_query = $wpdb->prepare(
			"SELECT parking_type,
					COUNT(*) as total_vehicles,
					SUM(CASE WHEN status = 'Exited' THEN fee ELSE 0 END) as total_revenue,
					SUM(CASE WHEN status = 'Parked' THEN 1 ELSE 0 END) as active_parked,
					SUM(CASE WHEN status = 'Exited' THEN 1 ELSE 0 END) as total_exited
			 FROM `{$table_logs}`
			 WHERE {$where_sql}
			 GROUP BY parking_type",
			$params
		);

		$hourly_query = $wpdb->prepare(
			"SELECT DATE_FORMAT(entry_time, '%%Y-%%m-%%d %%H:00:00') as hour_bucket,
					COUNT(*) as total_vehicles,
					SUM(CASE WHEN status = 'Parked' THEN 1 ELSE 0 END) as active_parked,
					SUM(CASE WHEN status = 'Exited' THEN 1 ELSE 0 END) as total_exited,
					SUM(CASE WHEN status = 'Exited' THEN fee ELSE 0 END) as total_revenue
			 FROM `{$table_logs}`
			 WHERE {$where_sql}
			 GROUP BY hour_bucket
			 ORDER BY hour_bucket ASC",
			$params
		);

		$daily_query = $wpdb->prepare(
			"SELECT DATE(entry_time) as log_date,
					COUNT(*) as total_vehicles,
					SUM(CASE WHEN status = 'Parked' THEN 1 ELSE 0 END) as active_parked,
					SUM(CASE WHEN status = 'Exited' THEN 1 ELSE 0 END) as total_exited,
					SUM(CASE WHEN status = 'Exited' THEN fee ELSE 0 END) as total_revenue
			 FROM `{$table_logs}`
			 WHERE {$where_sql}
			 GROUP BY DATE(entry_time)
			 ORDER BY log_date DESC",
			$params
		);

		$logs_query = $wpdb->prepare(
			"SELECT * FROM `{$table_logs}` WHERE {$where_sql} ORDER BY entry_time DESC LIMIT 500",
			$params
		);

		$total_veh_query = $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$table_logs}` WHERE {$where_sql}",
			$params
		);

		$total_rev_query = $wpdb->prepare(
			"SELECT SUM(fee) FROM `{$table_logs}` WHERE {$where_sql} AND status = 'Exited'",
			$params
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$summary         = $wpdb->get_results( $summary_query );
		$hourly_rows     = $wpdb->get_results( $hourly_query );
		$daily_rows      = $wpdb->get_results( $daily_query );
		$detailed_logs   = $wpdb->get_results( $logs_query );
		$total_veh_count = (int) $wpdb->get_var( $total_veh_query );
		$total_rev_sum   = (float) $wpdb->get_var( $total_rev_query );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	}

	$date_range_label = ifs_parking_reports_bn_num( date_i18n( 'j M, Y', strtotime( $start_date ) ) . ' - ' . date_i18n( 'j M, Y', strtotime( $end_date ) ) );
	$time_range_label = ifs_parking_reports_bn_num( gmdate( 'g:i A', strtotime( $start_time_sql ) ) . ' - ' . gmdate( 'g:i A', strtotime( $end_time_sql ) ) );
	?>
	<style>
		.ifs-reports-wrapper {
			margin: 10px 15px 20px 0;
			box-sizing: border-box;
			font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
		}
		.ifs-reports-banner {
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 14px;
			margin-bottom: 16px;
		}
		.ifs-banner-actions {
			display: flex;
			align-items: center;
			gap: 10px;
			flex-wrap: wrap;
		}
		.ifs-btn-excel-export {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 7px;
			height: 44px;
			padding: 0 18px;
			background: #15803d;
			color: #ffffff;
			border: none;
			border-radius: 10px;
			font-size: 14px;
			font-weight: 700;
			cursor: pointer;
			box-shadow: 0 2px 8px rgba(21, 128, 61, 0.22);
		}
		.ifs-btn-excel-export svg {
			width: 17px;
			height: 17px;
			fill: currentColor;
		}
		.ifs-primary-filter-row {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 12px;
			margin-bottom: 12px;
		}
		.ifs-advanced-toggle-row {
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 10px;
			padding-top: 10px;
			border-top: 1px dashed #e2e8f0;
		}
		.ifs-adv-toggle-btn {
			background: #f8fafc;
			border: 1px solid #cbd5e1;
			color: #334155;
			padding: 7px 14px;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 700;
			cursor: pointer;
		}
		.ifs-adv-panel {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 12px;
			margin-top: 12px;
			padding-top: 12px;
			border-top: 1px solid #f1f5f9;
		}
		.ifs-filter-buttons {
			display: flex;
			align-items: center;
			gap: 8px;
		}
		.ifs-kpi-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
			gap: 14px;
			margin-bottom: 18px;
		}
		.ifs-report-tabs {
			display: flex;
			gap: 8px;
			flex-wrap: wrap;
			margin-bottom: 14px;
		}
		.ifs-rep-tab {
			padding: 9px 16px;
			border-radius: 10px;
			border: 1.5px solid #cbd5e1;
			background: #ffffff;
			color: #334155;
			font-size: 13.5px;
			font-weight: 700;
			cursor: pointer;
		}
		.ifs-rep-tab.active {
			background: #0284c7;
			border-color: #0284c7;
			color: #ffffff;
		}
		.ifs-table-responsive {
			width: 100%;
			overflow-x: auto;
			-webkit-overflow-scrolling: touch;
		}
		.ifs-custom-data-table {
			min-width: 660px;
		}

		@media screen and (max-width: 1024px) {
			.ifs-primary-filter-row {
				grid-template-columns: repeat(2, 1fr);
			}
			.ifs-adv-panel {
				grid-template-columns: repeat(2, 1fr);
			}
		}

		@media screen and (max-width: 782px) {
			.ifs-reports-wrapper {
				margin: 10px 5px 20px 0;
			}
			.ifs-reports-banner {
				flex-direction: column;
				align-items: stretch;
			}
			.ifs-banner-actions {
				width: 100%;
				flex-direction: column;
			}
			.ifs-btn-print-report,
			.ifs-btn-excel-export {
				width: 100%;
				justify-content: center;
				height: 46px;
			}
			.ifs-primary-filter-row,
			.ifs-adv-panel {
				grid-template-columns: 1fr;
			}
			.ifs-advanced-toggle-row {
				flex-direction: column;
				align-items: stretch;
			}
			.ifs-filter-buttons {
				flex-direction: column;
				width: 100%;
			}
			.ifs-btn-filter-submit,
			.ifs-btn-filter-reset {
				width: 100%;
				justify-content: center;
				height: 46px;
			}
			.ifs-veh-input {
				font-size: 16px !important;
				height: 46px;
				width: 100%;
				box-sizing: border-box;
			}
		}
	</style>

	<div class="wrap ifs-reports-wrapper">
		<!-- হেডার ব্যানার -->
		<div class="ifs-reports-banner">
			<div>
				<span class="ifs-banner-badge ifs-badge-blue">
					<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
					<?php esc_html_e( 'সহজ ও ফ্লেক্সিবল রিপোর্ট জেনারেটর', 'ifs-parking' ); ?>
				</span>
				<h1 class="ifs-banner-title"><?php esc_html_e( 'রিপোর্ট ও অ্যানালিটিক্স', 'ifs-parking' ); ?></h1>
				<p class="ifs-banner-subtitle"><?php esc_html_e( 'যেকোনো তারিখ এবং যেকোনো সময় (দিন বা রাতের শিফট) সিলেক্ট করে ঘণ্টাভিত্তিক ও দিনভিত্তিক রিপোর্ট দেখুন।', 'ifs-parking' ); ?></p>
			</div>
			<div class="ifs-banner-actions">
				<button type="button" id="ifs-export-excel-btn" class="ifs-btn-excel-export">
					<svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
					<?php esc_html_e( 'Excel ডাউনলোড (.xls)', 'ifs-parking' ); ?>
				</button>
				<button type="button" id="ifs-clean-pdf-print-btn" class="ifs-btn-print-report">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
					<?php esc_html_e( 'PDF / প্রিন্ট করুন', 'ifs-parking' ); ?>
				</button>
			</div>
		</div>

		<!-- সহজ ও ফ্লেক্সিবল ফিল্টার কার্ড -->
		<div class="ifs-card ifs-filter-card">
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="ifs-filter-form">
				<input type="hidden" name="page" value="ifs-parking-reports">

				<div class="ifs-primary-filter-row">
					<div>
						<label class="ifs-form-label-sm" for="ifs-start-date"><?php esc_html_e( '১. শুরুর তারিখ (From Date)', 'ifs-parking' ); ?></label>
						<input type="date" id="ifs-start-date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>" class="ifs-veh-input">
					</div>
					<div>
						<label class="ifs-form-label-sm" for="ifs-end-date"><?php esc_html_e( '২. শেষের তারিখ (To Date)', 'ifs-parking' ); ?></label>
						<input type="date" id="ifs-end-date" name="end_date" value="<?php echo esc_attr( $end_date ); ?>" class="ifs-veh-input">
					</div>
					<div>
						<label class="ifs-form-label-sm" for="ifs-start-time"><?php esc_html_e( '৩. শুরুর সময় (Start Time)', 'ifs-parking' ); ?></label>
						<input type="time" id="ifs-start-time" name="start_time" value="<?php echo esc_attr( $start_time ); ?>" class="ifs-veh-input">
					</div>
					<div>
						<label class="ifs-form-label-sm" for="ifs-end-time"><?php esc_html_e( '৪. শেষের সময় (End Time)', 'ifs-parking' ); ?></label>
						<input type="time" id="ifs-end-time" name="end_time" value="<?php echo esc_attr( $end_time ); ?>" class="ifs-veh-input">
					</div>
				</div>

				<div class="ifs-advanced-toggle-row">
					<button type="button" id="ifs-toggle-adv-filter" class="ifs-adv-toggle-btn">
						+ <?php esc_html_e( 'আরও ফিল্টার (গাড়ির ধরণ / ডিপার্টমেন্ট / স্ট্যাটাস)', 'ifs-parking' ); ?>
					</button>

					<div class="ifs-filter-buttons">
						<button type="submit" class="ifs-btn-filter-submit">
							<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/></svg>
							<?php esc_html_e( 'রিপোর্ট দেখুন', 'ifs-parking' ); ?>
						</button>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-reports' ) ); ?>" class="ifs-btn-filter-reset">
							<?php esc_html_e( 'রিসেট', 'ifs-parking' ); ?>
						</a>
					</div>
				</div>

				<?php $has_adv = ( ! empty( $filter_veh ) || ! empty( $filter_dept ) || ! empty( $filter_stat ) ); ?>
				<div id="ifs-adv-filter-panel" class="ifs-adv-panel" style="display: <?php echo $has_adv ? 'grid' : 'none'; ?>;">
					<div>
						<label class="ifs-form-label-sm" for="ifs-filter-veh"><?php esc_html_e( 'গাড়ির ধরণ', 'ifs-parking' ); ?></label>
						<select id="ifs-filter-veh" name="veh_type" class="ifs-veh-input">
							<option value=""><?php esc_html_e( 'সকল গাড়ি', 'ifs-parking' ); ?></option>
							<?php foreach ( $veh_types_list as $vt ) : ?>
								<option value="<?php echo esc_attr( $vt ); ?>" <?php selected( $filter_veh, $vt ); ?>>
									<?php echo esc_html( $vt ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div>
						<label class="ifs-form-label-sm" for="ifs-filter-dept"><?php esc_html_e( 'ডিপার্টমেন্ট', 'ifs-parking' ); ?></label>
						<select id="ifs-filter-dept" name="dept" class="ifs-veh-input">
							<option value=""><?php esc_html_e( 'সকল ডিপার্টমেন্ট', 'ifs-parking' ); ?></option>
							<?php foreach ( $depts_list as $dp ) : ?>
								<option value="<?php echo esc_attr( $dp ); ?>" <?php selected( $filter_dept, $dp ); ?>>
									<?php echo esc_html( $dp ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div>
						<label class="ifs-form-label-sm" for="ifs-filter-stat"><?php esc_html_e( 'স্ট্যাটাস', 'ifs-parking' ); ?></label>
						<select id="ifs-filter-stat" name="log_status" class="ifs-veh-input">
							<option value=""><?php esc_html_e( 'সকল স্ট্যাটাস', 'ifs-parking' ); ?></option>
							<option value="Exited" <?php selected( $filter_stat, 'Exited' ); ?>><?php esc_html_e( 'রিলিজ সম্পন্ন (Exited)', 'ifs-parking' ); ?></option>
							<option value="Parked" <?php selected( $filter_stat, 'Parked' ); ?>><?php esc_html_e( 'পার্কিংয়ে আছে (Parked)', 'ifs-parking' ); ?></option>
						</select>
					</div>
				</div>
			</form>
		</div>

		<!-- KPI সামারি কার্ডস -->
		<div class="ifs-kpi-grid">
			<div class="ifs-dash-kpi ifs-kpi-blue">
				<div>
					<span class="ifs-kpi-label">
						<?php esc_html_e( 'মোট গাড়ি', 'ifs-parking' ); ?>
						(<?php echo esc_html( $time_range_label ); ?>)
					</span>
					<h2 class="ifs-kpi-val">
						<?php echo esc_html( ifs_parking_reports_bn_num( number_format_i18n( $total_veh_count ) ) ); ?>
						<span class="ifs-unit"><?php esc_html_e( 'টি', 'ifs-parking' ); ?></span>
					</h2>
				</div>
				<div class="ifs-kpi-icon ifs-kpi-icon-blue">
					<svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.85 7h10.29l1.04 3H5.81l1.04-3zM19 17H5v-4.66l.12-.34h13.76l.12.34V17z"/><circle cx="7.5" cy="14.5" r="1.5"/><circle cx="16.5" cy="14.5" r="1.5"/></svg>
				</div>
			</div>

			<div class="ifs-dash-kpi ifs-kpi-green">
				<div>
					<span class="ifs-kpi-label">
						<?php esc_html_e( 'মোট আয়', 'ifs-parking' ); ?>
						(<?php echo esc_html( $date_range_label ); ?>)
					</span>
					<h2 class="ifs-kpi-val ifs-txt-fee-accent">
						<?php echo esc_html( $currency_symbol . ifs_parking_reports_bn_num( number_format_i18n( $total_rev_sum, 2 ) ) ); ?>
					</h2>
				</div>
				<div class="ifs-kpi-icon ifs-kpi-icon-green">
					<svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
				</div>
			</div>
		</div>

		<!-- রিপোর্ট ভিউ ট্যাব -->
		<div class="ifs-report-tabs">
			<button type="button" class="ifs-rep-tab active" data-target="sec-hourly"><?php esc_html_e( 'ঘণ্টাভিত্তিক (Hour-by-Hour)', 'ifs-parking' ); ?></button>
			<button type="button" class="ifs-rep-tab" data-target="sec-daily"><?php esc_html_e( 'দিনভিত্তিক (Day-by-Day)', 'ifs-parking' ); ?></button>
			<button type="button" class="ifs-rep-tab" data-target="sec-category"><?php esc_html_e( 'ক্যাটাগরি সামারি', 'ifs-parking' ); ?></button>
			<button type="button" class="ifs-rep-tab" data-target="sec-logs"><?php esc_html_e( 'বিস্তারিত এন্ট্রি লগ', 'ifs-parking' ); ?></button>
		</div>

		<!-- ১. ঘণ্টাভিত্তিক রিপোর্ট (Hour-by-Hour) -->
		<div id="sec-hourly" class="ifs-card ifs-content-card ifs-rep-section">
			<div class="ifs-card-header">
				<h3 class="ifs-card-title">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: তারিখের রেঞ্জ, 2: সময়ের রেঞ্জ */
							__( 'ঘণ্টাভিত্তিক আয় ও গাড়ি বিবরণী (%1$s | %2$s)', 'ifs-parking' ),
							$date_range_label,
							$time_range_label
						)
					);
					?>
				</h3>
			</div>

			<div class="ifs-table-responsive">
				<table class="wp-list-table widefat fixed striped ifs-custom-data-table ifs-exportable-table" data-title="<?php esc_attr_e( 'ঘণ্টাভিত্তিক রিপোর্ট (Hour-by-Hour)', 'ifs-parking' ); ?>">
					<thead>
						<tr>
							<th><?php esc_html_e( 'তারিখ ও ঘণ্টা স্লট', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'মোট গাড়ি', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'পার্কিংয়ে আছে', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'রিলিজ সম্পন্ন', 'ifs-parking' ); ?></th>
							<th class="ifs-td-action-right"><?php esc_html_e( 'সংগৃহীত আয়', 'ifs-parking' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $hourly_rows ) ) : ?>
							<?php
							foreach ( $hourly_rows as $h_row ) :
								$b_ts       = strtotime( $h_row->hour_bucket );
								$slot_label = $b_ts ? ifs_parking_reports_bn_num( date_i18n( 'j M, Y | g:00 A - g:59 A', $b_ts ) ) : $h_row->hour_bucket;
								?>
								<tr>
									<td><strong><?php echo esc_html( $slot_label ); ?></strong></td>
									<td><?php echo esc_html( ifs_parking_reports_bn_num( (int) $h_row->total_vehicles ) ); ?> <?php esc_html_e( 'টি', 'ifs-parking' ); ?></td>
									<td><?php echo esc_html( ifs_parking_reports_bn_num( (int) $h_row->active_parked ) ); ?> <?php esc_html_e( 'টি', 'ifs-parking' ); ?></td>
									<td><?php echo esc_html( ifs_parking_reports_bn_num( (int) $h_row->total_exited ) ); ?> <?php esc_html_e( 'টি', 'ifs-parking' ); ?></td>
									<td class="ifs-td-action-right">
										<strong class="ifs-txt-fee-bold">
											<?php echo esc_html( $currency_symbol . ifs_parking_reports_bn_num( number_format_i18n( (float) $h_row->total_revenue, 2 ) ) ); ?>
										</strong>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="5" class="ifs-empty-table-cell"><?php esc_html_e( 'নির্বাচিত সময়ে কোনো ঘণ্টাভিত্তিক ডাটা পাওয়া যায়নি।', 'ifs-parking' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- ২. দিনভিত্তিক রিপোর্ট (Day-by-Day) -->
		<div id="sec-daily" class="ifs-card ifs-content-card ifs-rep-section" style="display:none;">
			<div class="ifs-card-header">
				<h3 class="ifs-card-title"><?php esc_html_e( 'দিনভিত্তিক আয় ও গাড়ি বিবরণী (Day-by-Day)', 'ifs-parking' ); ?></h3>
			</div>

			<div class="ifs-table-responsive">
				<table class="wp-list-table widefat fixed striped ifs-custom-data-table ifs-exportable-table" data-title="<?php esc_attr_e( 'দিনভিত্তিক রিপোর্ট (Day-by-Day)', 'ifs-parking' ); ?>">
					<thead>
						<tr>
							<th><?php esc_html_e( 'তারিখ', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'মোট গাড়ি', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'পার্কিংয়ে আছে', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'রিলিজ সম্পন্ন', 'ifs-parking' ); ?></th>
							<th class="ifs-td-action-right"><?php esc_html_e( 'মোট সংগৃহীত আয়', 'ifs-parking' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $daily_rows ) ) : ?>
							<?php foreach ( $daily_rows as $d_row ) : ?>
								<tr>
									<td><strong><?php echo esc_html( ifs_parking_reports_bn_num( date_i18n( 'j M, Y', strtotime( $d_row->log_date ) ) ) ); ?></strong></td>
									<td><?php echo esc_html( ifs_parking_reports_bn_num( (int) $d_row->total_vehicles ) ); ?> <?php esc_html_e( 'টি', 'ifs-parking' ); ?></td>
									<td><?php echo esc_html( ifs_parking_reports_bn_num( (int) $d_row->active_parked ) ); ?> <?php esc_html_e( 'টি', 'ifs-parking' ); ?></td>
									<td><?php echo esc_html( ifs_parking_reports_bn_num( (int) $d_row->total_exited ) ); ?> <?php esc_html_e( 'টি', 'ifs-parking' ); ?></td>
									<td class="ifs-td-action-right">
										<strong class="ifs-txt-fee-bold">
											<?php echo esc_html( $currency_symbol . ifs_parking_reports_bn_num( number_format_i18n( (float) $d_row->total_revenue, 2 ) ) ); ?>
										</strong>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="5" class="ifs-empty-table-cell"><?php esc_html_e( 'নির্বাচিত তারিখে কোনো ডাটা পাওয়া যায়নি।', 'ifs-parking' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- ৩. ক্যাটাগরিভিত্তিক রিপোর্ট -->
		<div id="sec-category" class="ifs-card ifs-content-card ifs-rep-section" style="display:none;">
			<div class="ifs-card-header">
				<h3 class="ifs-card-title"><?php esc_html_e( 'ক্যাটাগরি অনুযায়ী গাড়ি ও আয় বিবরণী', 'ifs-parking' ); ?></h3>
			</div>

			<div class="ifs-table-responsive">
				<table class="wp-list-table widefat fixed striped ifs-custom-data-table ifs-exportable-table" data-title="<?php esc_attr_e( 'ক্যাটাগরিভিত্তিক সামারি', 'ifs-parking' ); ?>">
					<thead>
						<tr>
							<th><?php esc_html_e( 'পার্কিং ক্যাটাগরি', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'মোট গাড়ি সংখ্যা', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'পার্কিংয়ে অবস্থানরত', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'রিলিজ সম্পন্ন', 'ifs-parking' ); ?></th>
							<th class="ifs-td-action-right"><?php esc_html_e( 'মোট সংগৃহীত আয়', 'ifs-parking' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $summary ) ) : ?>
							<?php foreach ( $summary as $row ) : ?>
								<tr>
									<td><span class="ifs-badge-dept"><?php echo esc_html( ucwords( str_replace( '-', ' - ', (string) $row->parking_type ) ) ); ?></span></td>
									<td class="ifs-txt-semi"><?php echo esc_html( ifs_parking_reports_bn_num( number_format_i18n( (int) $row->total_vehicles ) ) ); ?> <?php esc_html_e( 'টি গাড়ি', 'ifs-parking' ); ?></td>
									<td><?php echo esc_html( ifs_parking_reports_bn_num( (int) $row->active_parked ) ); ?> <?php esc_html_e( 'টি', 'ifs-parking' ); ?></td>
									<td><?php echo esc_html( ifs_parking_reports_bn_num( (int) $row->total_exited ) ); ?> <?php esc_html_e( 'টি', 'ifs-parking' ); ?></td>
									<td class="ifs-td-action-right">
										<strong class="ifs-txt-fee-bold ifs-txt-lg">
											<?php echo esc_html( $currency_symbol . ifs_parking_reports_bn_num( number_format_i18n( (float) $row->total_revenue, 2 ) ) ); ?>
										</strong>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="5" class="ifs-empty-table-cell"><?php esc_html_e( 'নির্বাচিত সময়ের জন্য কোনো ডাটা পাওয়া যায়নি।', 'ifs-parking' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- ৪. বিস্তারিত এন্ট্রি লগ -->
		<div id="sec-logs" class="ifs-card ifs-content-card ifs-rep-section" style="display:none;">
			<div class="ifs-card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
				<h3 class="ifs-card-title"><?php esc_html_e( 'বিস্তারিত গাড়ি এন্ট্রি ও বিল লগ', 'ifs-parking' ); ?></h3>
				<input type="text" id="ifs-rep-log-search" class="ifs-veh-input ifs-log-search-box" style="max-width:260px;" placeholder="<?php esc_attr_e( 'গাড়ি নম্বর বা ফোন খুঁজুন...', 'ifs-parking' ); ?>">
			</div>

			<div class="ifs-table-responsive">
				<table id="ifs-rep-logs-table" class="wp-list-table widefat fixed striped ifs-custom-data-table ifs-exportable-table" data-title="<?php esc_attr_e( 'বিস্তারিত এন্ট্রি লগ', 'ifs-parking' ); ?>">
					<thead>
						<tr>
							<th><?php esc_html_e( 'গাড়ি নম্বর', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'ক্যাটাগরি', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'প্রবেশের সময়', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'রিলিজের সময়', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'স্ট্যাটাস', 'ifs-parking' ); ?></th>
							<th class="ifs-td-action-right"><?php esc_html_e( 'বিল', 'ifs-parking' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $detailed_logs ) ) : ?>
							<?php
							foreach ( $detailed_logs as $lg ) :
								$in_fmt  = ifs_parking_reports_bn_num( date_i18n( 'j M, g:i A', strtotime( $lg->entry_time ) ) );
								$out_fmt = ! empty( $lg->exit_time ) ? ifs_parking_reports_bn_num( date_i18n( 'j M, g:i A', strtotime( $lg->exit_time ) ) ) : '-';
								?>
								<tr>
									<td><strong><?php echo esc_html( $lg->vehicle_number ); ?></strong></td>
									<td><?php echo esc_html( ucwords( str_replace( '-', ' - ', (string) $lg->parking_type ) ) ); ?></td>
									<td><?php echo esc_html( $in_fmt ); ?></td>
									<td><?php echo esc_html( $out_fmt ); ?></td>
									<td><?php echo 'Exited' === $lg->status ? esc_html__( 'রিলিজ সম্পন্ন', 'ifs-parking' ) : esc_html__( 'পার্কিংয়ে আছে', 'ifs-parking' ); ?></td>
									<td class="ifs-td-action-right">
										<strong><?php echo esc_html( $currency_symbol . ifs_parking_reports_bn_num( number_format_i18n( (float) $lg->fee, 2 ) ) ); ?></strong>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="6" class="ifs-empty-table-cell"><?php esc_html_e( 'কোনো বিস্তারিত লগ পাওয়া যায়নি।', 'ifs-parking' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		// অ্যাডভান্সড ফিল্টার টগল
		var advBtn   = document.getElementById('ifs-toggle-adv-filter');
		var advPanel = document.getElementById('ifs-adv-filter-panel');
		if (advBtn && advPanel) {
			advBtn.addEventListener('click', function() {
				advPanel.style.display = (advPanel.style.display === 'none') ? 'grid' : 'none';
			});
		}

		// রিপোর্ট ট্যাব সুইচার
		var tabs     = document.querySelectorAll('.ifs-rep-tab');
		var sections = document.querySelectorAll('.ifs-rep-section');

		tabs.forEach(function(tab) {
			tab.addEventListener('click', function() {
				var targetId = this.getAttribute('data-target');
				tabs.forEach(function(t) { t.classList.remove('active'); });
				this.classList.add('active');

				sections.forEach(function(sec) {
					sec.style.display = (sec.id === targetId) ? 'block' : 'none';
				});
			});
		});

		// বিস্তারিত লগ টেবিলে ইনস্ট্যান্ট সার্চ
		var logSearch = document.getElementById('ifs-rep-log-search');
		if (logSearch) {
			logSearch.addEventListener('input', function() {
				var q = this.value.toLowerCase().trim();
				document.querySelectorAll('#ifs-rep-logs-table tbody tr').forEach(function(tr) {
					tr.style.display = (q === '' || tr.textContent.toLowerCase().indexOf(q) !== -1) ? '' : 'none';
				});
			});
		}

		// ক্লিন রিপোর্ট HTML তৈরির হেল্পার (PDF ও Excel উভয়ের জন্য)
		function buildCleanReportMarkup(isForExcel) {
			var siteTitle  = <?php echo wp_json_encode( $site_title ); ?>;
			var phoneText  = <?php echo wp_json_encode( __( 'হেল্পলাইন:', 'ifs-parking' ) . ' ' . ifs_parking_reports_bn_num( $system_phone ) ); ?>;
			var periodText = <?php echo wp_json_encode( __( 'তারিখ:', 'ifs-parking' ) . ' ' . $date_range_label . ' | ' . __( 'সময়:', 'ifs-parking' ) . ' ' . $time_range_label ); ?>;
			var totalVeh   = <?php echo wp_json_encode( __( 'মোট গাড়ি:', 'ifs-parking' ) . ' ' . ifs_parking_reports_bn_num( number_format_i18n( $total_veh_count ) ) . ' ' . __( 'টি', 'ifs-parking' ) ); ?>;
			var totalRev   = <?php echo wp_json_encode( __( 'মোট আয়:', 'ifs-parking' ) . ' ' . $currency_symbol . ifs_parking_reports_bn_num( number_format_i18n( $total_rev_sum, 2 ) ) ); ?>;

			var out = [];
			if (isForExcel) {
				out.push('<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">');
				out.push('<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body>');
			} else {
				out.push('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' + siteTitle + ' - Report</title>');
				out.push('<style>');
				out.push('body{font-family:system-ui,-apple-system,sans-serif;color:#0f172a;padding:20px;margin:0;font-size:13px;}');
				out.push('.hdr{text-align:center;border-bottom:2px solid #0f172a;padding-bottom:10px;margin-bottom:16px;}');
				out.push('.hdr h2{margin:0 0 4px 0;font-size:20px;} .hdr p{margin:2px 0;color:#334155;}');
				out.push('.kpi{display:flex;justify-content:space-around;background:#f8fafc;border:1px solid #cbd5e1;padding:10px;border-radius:8px;margin-bottom:18px;font-weight:700;font-size:14px;}');
				out.push('h3{margin:18px 0 8px 0;font-size:15px;color:#0369a1;border-bottom:1px solid #e2e8f0;padding-bottom:4px;}');
				out.push('table{width:100%;border-collapse:collapse;margin-bottom:14px;page-break-inside:auto;}');
				out.push('tr{page-break-inside:avoid;page-break-after:auto;}');
				out.push('th,td{border:1px solid #cbd5e1;padding:7px 9px;text-align:left;}');
				out.push('th{background:#f1f5f9;font-weight:700;}');
				out.push('th:last-child,td:last-child{text-align:right;}');
				out.push('</style></head><body>');
			}

			out.push('<div class="hdr"><h2>' + siteTitle + '</h2><p>' + phoneText + '</p><p><strong>' + periodText + '</strong></p></div>');
			out.push('<div class="kpi"><span>' + totalVeh + '</span> &nbsp;&nbsp;|&nbsp;&nbsp; <span>' + totalRev + '</span></div>');

			document.querySelectorAll('.ifs-exportable-table').forEach(function(tbl) {
				var secTitle = tbl.getAttribute('data-title') || 'Report';
				out.push('<h3>' + secTitle + '</h3>');
				out.push('<table border="1" cellspacing="0" cellpadding="6">');
				tbl.querySelectorAll('tr').forEach(function(tr) {
					if (tr.style.display === 'none') return;
					out.push('<tr>');
					tr.querySelectorAll('th, td').forEach(function(cell) {
						var tag = cell.tagName.toLowerCase();
						var txt = cell.textContent.replace(/\s+/g, ' ').trim();
						out.push('<' + tag + '>' + txt + '</' + tag + '>');
					});
					out.push('</tr>');
				});
				out.push('</table>');
			});

			out.push('</body></html>');
			return out.join('');
		}

		// ১. ক্লিন PDF / প্রিন্ট হ্যান্ডলার (Dedicated A4 Print Window)
		var pdfPrintBtn = document.getElementById('ifs-clean-pdf-print-btn');
		if (pdfPrintBtn) {
			pdfPrintBtn.addEventListener('click', function() {
				var printMarkup = buildCleanReportMarkup(false);
				var printWin    = window.open('', '_blank', 'width=960,height=720');
				if (printWin) {
					printWin.document.open();
					printWin.document.write(printMarkup);
					printWin.document.close();
					printWin.focus();
					setTimeout(function() {
						printWin.print();
					}, 350);
				} else {
					window.print();
				}
			});
		}

		// ২. ১-ক্লিক Excel (.xls) এক্সপোর্ট হ্যান্ডলার
		var excelBtn = document.getElementById('ifs-export-excel-btn');
		if (excelBtn) {
			excelBtn.addEventListener('click', function() {
				var fileName    = 'Parking_Report_<?php echo esc_js( $start_date . '_to_' . $end_date ); ?>.xls';
				var excelMarkup = buildCleanReportMarkup(true);
				var blob        = new Blob(['\ufeff', excelMarkup], {
					type: 'application/vnd.ms-excel;charset=utf-8'
				});
				var url  = URL.createObjectURL(blob);
				var link = document.createElement('a');
				link.href     = url;
				link.download = fileName;
				document.body.appendChild(link);
				link.click();
				setTimeout(function() {
					document.body.removeChild(link);
					URL.revokeObjectURL(url);
				}, 200);
			});
		}
	});
	</script>
	<?php
}