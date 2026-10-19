<?php
/**
 * Categories View Template (Same Layout as Add/Edit).
 *
 * @package Ifs_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ডিপার্টমেন্ট ভিত্তিক ক্যাটাগরি ভিউ রেন্ডারার
 */
function ifs_parking_render_category_view( $table_cats, $dept_name, $currency_symbol ) {
	global $wpdb;

	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_cats WHERE department = %s ORDER BY id ASC", $dept_name ) );

	if ( empty( $rows ) ) {
		echo '<div class="notice notice-error ifs-custom-notice-err"><p>' . esc_html__( 'কোনো তথ্য পাওয়া যায়নি।', 'ifs-parking' ) . '</p></div>';
		return;
	}

	$edit_url = admin_url( 'admin.php?page=ifs-parking-categories&action=edit&dept=' . urlencode( $dept_name ) );
	?>
	<div class="ifs-card ifs-form-card">
		<!-- হেডার অংশ -->
		<div class="ifs-card-header-bordered">
			<div>
				<h3 class="ifs-card-title-lg">
					<svg class="ifs-svg-primary" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
					<?php echo esc_html( sprintf( __( 'ডিপার্টমেন্ট বিস্তারিত: %s', 'ifs-parking' ), $dept_name ) ); ?>
				</h3>
				<p class="ifs-card-subtitle"><?php esc_html_e( 'এই ডিপার্টমেন্টের অধীনে সকল সক্রিয় রেট ও প্যাকেজ বিবরণী।', 'ifs-parking' ); ?></p>
			</div>
			<div class="ifs-actions-row">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-categories' ) ); ?>" class="ifs-btn-back">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
					<?php esc_html_e( 'তালিকায় ফিরে যান', 'ifs-parking' ); ?>
				</a>
				<a href="<?php echo esc_url( $edit_url ); ?>" class="ifs-btn-primary-action">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
					<?php esc_html_e( 'সম্পাদনা করুন', 'ifs-parking' ); ?>
				</a>
			</div>
		</div>

		<!-- ডিপার্টমেন্ট কার্ড ভিউ -->
		<div class="ifs-dept-box">
			<label class="ifs-field-label ifs-field-label-dept">
				<svg viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
				<?php esc_html_e( 'ডিপার্টমেন্টের নাম', 'ifs-parking' ); ?>
			</label>
			<strong class="ifs-dept-name-display"><?php echo esc_html( $dept_name ); ?></strong>
		</div>

		<div class="ifs-section-subhead">
			<h4 class="ifs-subhead-title">
				<svg class="ifs-svg-primary" viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
				<?php echo esc_html( sprintf( __( 'সংযুক্ত প্যাকেজ ও রেট তালিকা (%d টি)', 'ifs-parking' ), count( $rows ) ) ); ?>
			</h4>
		</div>

		<!-- রিপিটার লেআউটের অনুরূপ ভিউ রো -->
		<div id="ifs-repeater-container">
			<?php foreach ( $rows as $idx => $r ) : ?>
				<div class="ifs-repeater-row ifs-view-row-mode">
					<div>
						<label class="ifs-field-label"><?php esc_html_e( 'গ্রাহকের ধরণ', 'ifs-parking' ); ?></label>
						<span class="ifs-view-field-val"><?php echo esc_html( $r->customer_type ); ?></span>
					</div>

					<div>
						<label class="ifs-field-label"><?php esc_html_e( 'গাড়ির ধরণ', 'ifs-parking' ); ?></label>
						<span class="ifs-badge-dept"><?php echo esc_html( $r->vehicle_type ); ?></span>
					</div>

					<div>
						<label class="ifs-field-label"><?php esc_html_e( 'ঘণ্টা প্রতি', 'ifs-parking' ); ?></label>
						<strong class="ifs-rate-val-green"><?php echo esc_html( $currency_symbol . number_format( (float) $r->hourly_rate, 2 ) ); ?></strong>
					</div>

					<div>
						<label class="ifs-field-label"><?php esc_html_e( 'দৈনিক ফি', 'ifs-parking' ); ?></label>
						<strong class="ifs-rate-val-blue"><?php echo esc_html( $currency_symbol . number_format( (float) $r->daily_rate, 2 ) ); ?></strong>
					</div>

					<div>
						<label class="ifs-field-label"><?php esc_html_e( 'মাসিক ফি', 'ifs-parking' ); ?></label>
						<strong class="ifs-rate-val-slate"><?php echo esc_html( $currency_symbol . number_format( (float) $r->monthly_rate, 2 ) ); ?></strong>
					</div>

					<div>
						<label class="ifs-field-label"><?php esc_html_e( 'ফ্রি পার্কিং', 'ifs-parking' ); ?></label>
						<span class="ifs-view-field-val"><?php echo esc_html( intval( $r->free_hours ) . ' ঘণ্টা' ); ?></span>
					</div>

					<div class="ifs-repeater-action-col">
						<span class="ifs-badge-status-free">✓</span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}