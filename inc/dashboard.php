<?php
/**
 * Admin Dashboard Page Renderer.
 *
 * @package Ifs_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ড্যাশবোর্ড পেজ রেন্ডার ফাংশন
 */
function ifs_parking_render_dashboard_page() {
	ifs_parking_render_layout_wrapper( 'ifs_parking_render_dashboard_content' );
}

/**
 * ড্যাশবোর্ডের মূল কনটেন্ট রেন্ডারার
 */
function ifs_parking_render_dashboard_content() {
	global $wpdb;

	$table_logs  = $wpdb->prefix . 'ifs_parking_logs';
	$table_slots = $wpdb->prefix . 'ifs_parking_slots';

	// কারেন্সি সেটিং ফেচ
	$currency_setting = get_option( 'ifs_parking_currency', 'BDT (৳)' );
	$currency_symbol  = '$';
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

	$current_user = wp_get_current_user();
	$user_name    = ! empty( $current_user->display_name ) ? $current_user->display_name : __( 'অ্যাডমিনিস্ট্রেটর', 'ifs-parking' );

	$today         = current_time( 'Y-m-d' );
	$today_income  = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(fee) FROM $table_logs WHERE DATE(exit_time) = %s AND status = 'Exited'", $today ) );
	$today_entries = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_logs WHERE DATE(entry_time) = %s", $today ) );
	$active_cars   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_logs WHERE status = 'Parked'" );
	$total_slots   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_slots" );
	$free_slots    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_slots WHERE is_occupied = 0" );

	// ভেহিক্যাল টাইপ ডেটা কনফিগারেশন
	$vehicle_data = array(
		'Car'   => array(
			'label' => __( 'কার (Car)', 'ifs-parking' ),
			'icon'  => '<svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.85 7h10.29l1.04 3H5.81l1.04-3zM19 17H5v-4.66l.12-.34h13.76l.12.34V17z"/><circle cx="7.5" cy="14.5" r="1.5"/><circle cx="16.5" cy="14.5" r="1.5"/></svg>',
		),
		'Noah'  => array(
			'label' => __( 'নোয়াহ (Noah)', 'ifs-parking' ),
			'icon'  => '<svg viewBox="0 0 24 24"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm12 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm1.5-7.5H16V9h2.2l1.3 2z"/></svg>',
		),
		'Hiace' => array(
			'label' => __( 'হাইয়েস (Hiace)', 'ifs-parking' ),
			'icon'  => '<svg viewBox="0 0 24 24"><path d="M4 16c0 .88.39 1.67 1 2.22V20c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h8v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1.78c.61-.55 1-1.34 1-2.22V6c0-2.67-2.33-4-7-4s-7 1.33-7 4v10zm3.5 1c-.83 0-1.5-.67-1.5-1.5S6.67 14 7.5 14s1.5.67 1.5 1.5S8.33 17 7.5 17zm9 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM6 11V6h12v5H6z"/></svg>',
		),
		'CNG'   => array(
			'label' => __( 'সিএনজি (CNG)', 'ifs-parking' ),
			'icon'  => '<svg viewBox="0 0 24 24"><path d="M20 15V8c0-1.1-.9-2-2-2h-3l-2-3H9L7 6H4c-1.1 0-2 .9-2 2v7c0 .55.45 1 1 1h1c0 1.66 1.34 3 3 3s3-1.34 3-3h4c0 1.66 1.34 3 3 3s3-1.34 3-3h1c.55 0 1-.45 1-1zM7 16.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm10 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM4 13V8h14v5H4z"/></svg>',
		),
		'Truck' => array(
			'label' => __( 'ট্রাক (Truck)', 'ifs-parking' ),
			'icon'  => '<svg viewBox="0 0 24 24"><path d="M18 1c-2.76 0-5 2.24-5 5v1H4c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h1c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h1c1.1 0 2-.9 2-2v-5.5L20.5 7H18V6c0-1.66 1.34-3 3-3s3 1.34 3 3v1h-2v2h3c.55 0 1-.45 1-1V6c0-2.76-2.24-5-5-5h-3zM8 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM17 12V9h3l2 3h-5z"/></svg>',
		),
		'Bike'  => array(
			'label' => __( 'বাইক (Bike)', 'ifs-parking' ),
			'icon'  => '<svg viewBox="0 0 24 24"><path d="M5 20.5c-2.48 0-4.5-2.02-4.5-4.5S2.52 11.5 5 11.5c2.09 0 3.84 1.43 4.34 3.38l2.79-1.86-1.5-3.02H9V8h3.33l1.5 3.02 2.76-1.84c-.37-.62-.59-1.34-.59-2.18 0-2.21 1.79-4 4-4s4 1.79 4 4-1.79 4-4 4c-.75 0-1.45-.21-2.05-.57l-2.47 1.65 1.83 3.66c.22-.04.45-.06.69-.06 2.48 0 4.5 2.02 4.5 4.5s-2.02 4.5-4.5 4.5-4.5-2.02-4.5-4.5c0-.98.32-1.89.86-2.63L11.7 18.5H8.77c-.45 1.18-1.57 2-2.87 2l-.9-.01zM5 13.5c-1.38 0-2.5 1.12-2.5 2.5s1.12 2.5 2.5 2.5 2.5-1.12 2.5-2.5-1.12-2.5-2.5-2.5zm14 0c-1.38 0-2.5 1.12-2.5 2.5s1.12 2.5 2.5 2.5 2.5-1.12 2.5-2.5-1.12-2.5-2.5-2.5z"/></svg>',
		),
		'Cycle' => array(
			'label' => __( 'সাইকেল (Cycle)', 'ifs-parking' ),
			'icon'  => '<svg viewBox="0 0 24 24"><path d="M15.5 5.5c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zM5 12c-2.8 0-5 2.2-5 5s2.2 5 5 5 5-2.2 5-5-2.2-5-5-5zm0 8.5c-1.9 0-3.5-1.6-3.5-3.5s1.6-3.5 3.5-3.5 3.5 1.6 3.5 3.5-1.6 3.5-3.5 3.5zm7.8-6.5l-1.9-3.2c-.4-.6-1-1-1.7-1H6.4l.6 2h2.2l1.2 2-3.1 5.3 1.7 1 3.2-5.4 2.8 2.8v3.5h2v-4.3l-3.2-3.2.8-2.5 1.4 1.4c.4.4.9.6 1.4.6h3v-2h-2.6l-2-2c-.3-.3-.7-.5-1.2-.5-.3 0-.6.1-.8.2zm6.2-2c-2.8 0-5 2.2-5 5s2.2 5 5 5 5-2.2 5-5-2.2-5-5-5zm0 8.5c-1.9 0-3.5-1.6-3.5-3.5s1.6-3.5 3.5-3.5 3.5 1.6 3.5 3.5-1.6 3.5-3.5 3.5z"/></svg>',
		),
	);

	// অপ্টিমাইজড সিঙ্গেল গ্রুপ কুয়েরি
	$slot_stats_raw = $wpdb->get_results(
		"SELECT vehicle_type,
				COUNT(*) as total,
				SUM(CASE WHEN is_occupied = 1 THEN 1 ELSE 0 END) as booked,
				SUM(CASE WHEN is_occupied = 0 THEN 1 ELSE 0 END) as free
		 FROM $table_slots 
		 GROUP BY vehicle_type"
	);

	$slot_stats = array();
	if ( ! empty( $slot_stats_raw ) ) {
		foreach ( $slot_stats_raw as $stat ) {
			$slot_stats[ $stat->vehicle_type ] = $stat;
		}
	}

	$recent_logs = $wpdb->get_results( "SELECT * FROM $table_logs ORDER BY id DESC LIMIT 5" );
	?>
	<div class="wrap ifs-dashboard-wrapper">
		<!-- হেডার ব্যানার -->
		<div class="ifs-dashboard-banner">
			<div class="ifs-banner-left">
				<span class="ifs-badge-live">
					<svg class="ifs-icon-sm" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8zm-3-11c-.83 0-1.5-.67-1.5-1.5S11.17 2 12 2s1.5.67 1.5 1.5S12.83 5 12 5z"/></svg>
					<?php esc_html_e( 'লাইভ সিস্টেম ওভারভিউ', 'ifs-parking' ); ?>
				</span>
				<h1 class="ifs-banner-title">
					<?php echo esc_html( sprintf( __( 'স্বাগতম, %s!', 'ifs-parking' ), $user_name ) ); ?>
				</h1>
				<p class="ifs-banner-subtitle">
					<?php esc_html_e( 'আপনার পার্কিং সিস্টেমের আজকের আয়, পার্কিং স্থিতি ও লাইভ পরিসংখ্যান।', 'ifs-parking' ); ?>
				</p>
			</div>

			<!-- বাংলাদেশ লাইভ ক্লক উইজেট -->
			<div class="ifs-live-clock-card">
				<div class="ifs-clock-icon-pulse">
					<svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
				</div>
				<div>
					<div class="ifs-clock-time" id="ifs-live-time">--:--:-- --</div>
					<div class="ifs-clock-date">
						<span class="ifs-clock-live-tag">BST</span>
						<span id="ifs-live-date"><?php esc_html_e( 'লোড হচ্ছে...', 'ifs-parking' ); ?></span>
					</div>
				</div>
			</div>
		</div>

		<!-- ফার্স্ট-ইউজ কুইক অ্যাকশন লিংকস কার্ড -->
		<div class="ifs-card ifs-content-card ifs-quick-launch-card">
			<div class="ifs-card-header">
				<h3 class="ifs-card-title">
					<svg class="ifs-icon-brand" viewBox="0 0 24 24"><path d="M12 2l-5.5 9h11z"/><circle cx="17.5" cy="17.5" r="4.5"/><path d="M3 13.5h8v8H3z"/></svg>
					<?php esc_html_e( 'কুইক লঞ্চ ও ফার্স্ট-ইউজ গাইডলাইন', 'ifs-parking' ); ?>
				</h3>
			</div>
			<div class="ifs-quick-links-grid">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-vehicles&tab=add_vehicle' ) ); ?>" class="ifs-quick-link-box">
					<div class="ifs-ql-icon ifs-ql-blue">
						<svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
					</div>
					<div>
						<strong><?php esc_html_e( 'নতুন গাড়ি চেক-ইন', 'ifs-parking' ); ?></strong>
						<p><?php esc_html_e( 'গাড়ি এন্ট্রি ও রসিদ প্রিন্ট করুন', 'ifs-parking' ); ?></p>
					</div>
				</a>

				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-slots' ) ); ?>" class="ifs-quick-link-box">
					<div class="ifs-ql-icon ifs-ql-green">
						<svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 10h2v7H7zm4-3h2v10h-2zm4 5h2v5h-2z"/></svg>
					</div>
					<div>
						<strong><?php esc_html_e( 'স্লট ম্যানেজমেন্ট', 'ifs-parking' ); ?></strong>
						<p><?php esc_html_e( 'গাড়ির ধরন অনুযায়ী স্লট সংখ্যা বাড়ান', 'ifs-parking' ); ?></p>
					</div>
				</a>

				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-categories&tab=add_category' ) ); ?>" class="ifs-quick-link-box">
					<div class="ifs-ql-icon ifs-ql-amber">
						<svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
					</div>
					<div>
						<strong><?php esc_html_e( 'রেট ও ক্যাটাগরি', 'ifs-parking' ); ?></strong>
						<p><?php esc_html_e( 'ঘণ্টা, দৈনিক ও মাসিক রেট সেট করুন', 'ifs-parking' ); ?></p>
					</div>
				</a>

				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-settings' ) ); ?>" class="ifs-quick-link-box">
					<div class="ifs-ql-icon ifs-ql-purple">
						<svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36.25c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c-.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
					</div>
					<div>
						<strong><?php esc_html_e( 'সিস্টেম সেটিংস', 'ifs-parking' ); ?></strong>
						<p><?php esc_html_e( 'লোগো, কারেন্সি ও ঠিকানা পরিবর্তন করুন', 'ifs-parking' ); ?></p>
					</div>
				</a>
			</div>
		</div>

		<!-- ৪টি প্রধান KPI কার্ড -->
		<div class="ifs-kpi-grid">
			<!-- আজকের আয় -->
			<div class="ifs-dash-kpi ifs-kpi-blue">
				<div>
					<span class="ifs-kpi-label"><?php esc_html_e( 'আজকের মোট আয়', 'ifs-parking' ); ?></span>
					<h2 class="ifs-kpi-val"><?php echo esc_html( $currency_symbol . number_format( $today_income, 2 ) ); ?></h2>
				</div>
				<div class="ifs-kpi-icon ifs-kpi-icon-blue">
					<svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
				</div>
			</div>

			<!-- আজ প্রবেশকৃত গাড়ি -->
			<div class="ifs-dash-kpi ifs-kpi-green">
				<div>
					<span class="ifs-kpi-label"><?php esc_html_e( 'আজ প্রবেশকৃত গাড়ি', 'ifs-parking' ); ?></span>
					<h2 class="ifs-kpi-val"><?php echo esc_html( $today_entries ); ?> <span class="ifs-unit"><?php esc_html_e( 'টি', 'ifs-parking' ); ?></span></h2>
				</div>
				<div class="ifs-kpi-icon ifs-kpi-icon-green">
					<svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.85 7h10.29l1.04 3H5.81l1.04-3zM19 17H5v-4.66l.12-.34h13.76l.12.34V17z"/><circle cx="7.5" cy="14.5" r="1.5"/><circle cx="16.5" cy="14.5" r="1.5"/></svg>
				</div>
			</div>

			<!-- বর্তমানে পার্ক করা -->
			<div class="ifs-dash-kpi ifs-kpi-amber">
				<div>
					<span class="ifs-kpi-label"><?php esc_html_e( 'বর্তমানে পার্কিংয়ে আছে', 'ifs-parking' ); ?></span>
					<h2 class="ifs-kpi-val"><?php echo esc_html( $active_cars ); ?> <span class="ifs-unit"><?php esc_html_e( 'টি', 'ifs-parking' ); ?></span></h2>
				</div>
				<div class="ifs-kpi-icon ifs-kpi-icon-amber">
					<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
				</div>
			</div>

			<!-- স্লট ক্যাপাসিটি -->
			<div class="ifs-dash-kpi ifs-kpi-purple">
				<div>
					<span class="ifs-kpi-label"><?php esc_html_e( 'খালি / মোট স্লট', 'ifs-parking' ); ?></span>
					<h2 class="ifs-kpi-val"><?php echo esc_html( $free_slots ); ?> <span class="ifs-unit-alt">/ <?php echo esc_html( $total_slots ); ?></span></h2>
				</div>
				<div class="ifs-kpi-icon ifs-kpi-icon-purple">
					<svg viewBox="0 0 24 24"><path d="M4 11h5V5H4v6zm0 7h5v-6H4v6zm6 0h5v-6h-5v6zm6 0h5v-6h-5v6zm-6-7h5V5h-5v6zm6-6v6h5V5h-5z"/></svg>
				</div>
			</div>
		</div>

		<!-- গাড়ি ধরণভিত্তিক স্লট ক্যাপাসিটি -->
		<div class="ifs-card ifs-content-card">
			<div class="ifs-card-header">
				<h3 class="ifs-card-title">
					<svg class="ifs-icon-brand" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 10h2v7H7zm4-3h2v10h-2zm4 5h2v5h-2z"/></svg>
					<?php esc_html_e( 'গাড়ি ধরণভিত্তিক স্লট ক্যাপাসিটি', 'ifs-parking' ); ?>
				</h3>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-slots' ) ); ?>" class="ifs-link-action">
					<?php esc_html_e( 'স্লট ম্যানেজ করুন', 'ifs-parking' ); ?>
					<svg class="ifs-icon-xs" viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
				</a>
			</div>

			<div class="ifs-veh-grid">
				<?php 
				foreach ( $vehicle_data as $v_key => $v_info ) :
					$v_stat   = isset( $slot_stats[ $v_key ] ) ? $slot_stats[ $v_key ] : null;
					$v_total  = $v_stat ? (int) $v_stat->total : 0;
					$v_free   = $v_stat ? (int) $v_stat->free : 0;
					$v_booked = $v_stat ? (int) $v_stat->booked : 0;
					$pct      = $v_total > 0 ? (int) round( ( $v_booked / $v_total ) * 100 ) : 0;
					$bar_class= $pct > 80 ? 'ifs-progress-fill-red' : 'ifs-progress-fill-blue';
				?>
					<div class="ifs-veh-pill">
						<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
							<span style="font-size: 13px; font-weight: 700; color: #1e293b;"><?php echo esc_html( $v_info['label'] ); ?></span>
							<div class="ifs-veh-icon-box">
								<?php echo $v_info['icon']; ?>
							</div>
						</div>
						<div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;">
							<strong style="font-size: 16px; color: #0f172a;"><?php echo esc_html( $v_total ); ?> <?php esc_html_e( 'স্লট', 'ifs-parking' ); ?></strong>
							<small style="color: #16a34a; font-weight: 700;"><?php esc_html_e( 'খালি:', 'ifs-parking' ); ?> <?php echo esc_html( $v_free ); ?></small>
						</div>
						<div style="width: 100%; height: 6px; background: #e2e8f0; border-radius: 10px; overflow: hidden;">
							<div style="width: <?php echo esc_attr( $pct ); ?>%; height: 100%; background: <?php echo $pct > 80 ? '#ef4444' : '#0284c7'; ?>; border-radius: 10px;"></div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- সাম্প্রতিক ৫টি পার্কিং এন্ট্রি -->
		<div class="ifs-card ifs-content-card">
			<div class="ifs-card-header">
				<h3 class="ifs-card-title">
					<svg class="ifs-icon-brand" viewBox="0 0 24 24"><path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
					<?php esc_html_e( 'সাম্প্রতিক পার্কিং ট্র্যাকিং', 'ifs-parking' ); ?>
				</h3>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-vehicles' ) ); ?>" class="ifs-link-action">
					<?php esc_html_e( 'সবগুলো দেখুন', 'ifs-parking' ); ?>
					<svg class="ifs-icon-xs" viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
				</a>
			</div>

			<div class="ifs-table-responsive">
				<table class="wp-list-table widefat fixed striped ifs-custom-table">
					<thead>
						<tr>
							<th class="ifs-col-id"><?php esc_html_e( 'আইডি', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'গাড়ি নম্বর', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'ডিপার্টমেন্ট', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'ক্যাটাগরি', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'চালক', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'প্রবেশের সময়', 'ifs-parking' ); ?></th>
							<th class="ifs-col-status"><?php esc_html_e( 'স্ট্যাটাস', 'ifs-parking' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $recent_logs ) ) : ?>
							<?php foreach ( $recent_logs as $r_log ) : ?>
								<tr>
									<td class="ifs-td-id">#<?php echo esc_html( $r_log->id ); ?></td>
									<td><strong><?php echo esc_html( $r_log->vehicle_number ); ?></strong></td>
									<td><span class="ifs-badge-dept"><?php echo esc_html( ! empty( $r_log->reference_id ) ? $r_log->reference_id : 'General' ); ?></span></td>
									<td><?php echo esc_html( ucfirst( $r_log->parking_type ) ); ?></td>
									<td class="ifs-td-muted"><?php echo esc_html( ! empty( $r_log->driver_name ) ? $r_log->driver_name : __( 'তথ্য নেই', 'ifs-parking' ) ); ?></td>
									<td class="ifs-td-time"><?php echo esc_html( $r_log->entry_time ); ?></td>
									<td class="ifs-td-status">
										<?php if ( 'Parked' === $r_log->status ) : ?>
											<span class="ifs-status-pill ifs-status-parked">
												<svg class="ifs-dot-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg>
												<?php esc_html_e( 'পার্ক করা', 'ifs-parking' ); ?>
											</span>
										<?php else : ?>
											<span class="ifs-status-pill ifs-status-exited">
												<svg class="ifs-check-icon" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
												<?php esc_html_e( 'বের হয়েছে', 'ifs-parking' ); ?>
											</span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr><td colspan="7" class="ifs-empty-cell"><?php esc_html_e( 'কোনো সাম্প্রতিক পার্কিং রেকর্ড নেই।', 'ifs-parking' ); ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php
}