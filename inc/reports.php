<?php
/**
 * Reports & Analytics Module.
 *
 * @package Ifs_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * রিপোর্ট ও অ্যানালিটিক্স পেজ রেন্ডারার
 */
function ifs_parking_render_reports_page() {
	ifs_parking_render_layout_wrapper( 'ifs_parking_render_reports_content' );
}

/**
 * মূল রিপোর্ট কনটেন্ট
 */
function ifs_parking_render_reports_content() {
	global $wpdb;
	$table_logs = $wpdb->prefix . 'ifs_parking_logs';

	// কারেন্সি সিম্বল ডায়নামিক ফেচ
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

	// ফিল্টার প্যারামিটার (তারিখ রেঞ্জ)
	$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
	$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';

	// কুয়েরির শর্ত তৈরি
	$where_clauses = array( '1=1' );
	$params        = array();

	if ( ! empty( $start_date ) ) {
		$where_clauses[] = 'DATE(entry_time) >= %s';
		$params[]        = $start_date;
	}
	if ( ! empty( $end_date ) ) {
		$where_clauses[] = 'DATE(entry_time) <= %s';
		$params[]        = $end_date;
	}

	$where_sql = implode( ' AND ', $where_clauses );

	// সামারি কুয়েরি (ক্যাটাগরি ভিত্তিক)
	if ( ! empty( $params ) ) {
		$summary_query = $wpdb->prepare(
			"SELECT parking_type, 
					COUNT(*) as total_vehicles, 
					SUM(fee) as total_revenue,
					SUM(CASE WHEN status = 'Parked' THEN 1 ELSE 0 END) as active_parked,
					SUM(CASE WHEN status = 'Exited' THEN 1 ELSE 0 END) as total_exited
			 FROM $table_logs 
			 WHERE $where_sql 
			 GROUP BY parking_type",
			$params
		);

		$total_veh_query = $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_logs WHERE $where_sql",
			$params
		);

		$total_rev_query = $wpdb->prepare(
			"SELECT SUM(fee) FROM $table_logs WHERE $where_sql AND status = 'Exited'",
			$params
		);
	} else {
		$summary_query   = "SELECT parking_type, 
								   COUNT(*) as total_vehicles, 
								   SUM(fee) as total_revenue,
								   SUM(CASE WHEN status = 'Parked' THEN 1 ELSE 0 END) as active_parked,
								   SUM(CASE WHEN status = 'Exited' THEN 1 ELSE 0 END) as total_exited
							FROM $table_logs 
							GROUP BY parking_type";
		$total_veh_query = "SELECT COUNT(*) FROM $table_logs";
		$total_rev_query = "SELECT SUM(fee) FROM $table_logs WHERE status = 'Exited'";
	}

	$summary         = $wpdb->get_results( $summary_query );
	$total_veh_count = (int) $wpdb->get_var( $total_veh_query );
	$total_rev_sum   = (float) $wpdb->get_var( $total_rev_query );
	?>
	<div class="wrap ifs-reports-wrapper">
		<!-- প্রিমিয়াম হেডার -->
		<div class="ifs-reports-banner">
			<div>
				<span class="ifs-banner-badge ifs-badge-blue">
					<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
					<?php esc_html_e( 'অ্যানালিটিক্স ও অডিট', 'ifs-parking' ); ?>
				</span>
				<h1 class="ifs-banner-title"><?php esc_html_e( 'রিপোর্ট ও অ্যানালিটিক্স', 'ifs-parking' ); ?></h1>
				<p class="ifs-banner-subtitle"><?php esc_html_e( 'পার্কিং সিস্টেমের মোট আয়, গাড়ি প্রবেশ ও ক্যাটাগরি ভিত্তিক বিশদ পরিসংখ্যান।', 'ifs-parking' ); ?></p>
			</div>
			<div class="ifs-banner-actions">
				<button type="button" onclick="window.print()" class="ifs-btn-print-report">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
					<?php esc_html_e( 'রিপোর্ট প্রিন্ট করুন', 'ifs-parking' ); ?>
				</button>
			</div>
		</div>

		<!-- তারিখ ফিল্টার বার -->
		<div class="ifs-card ifs-filter-card">
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="ifs-filter-form">
				<input type="hidden" name="page" value="ifs-parking-reports">
				
				<div class="ifs-filter-inputs">
					<div>
						<label class="ifs-form-label-sm"><?php esc_html_e( 'শুরুর তারিখ', 'ifs-parking' ); ?></label>
						<input type="date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>" class="ifs-veh-input">
					</div>
					<div>
						<label class="ifs-form-label-sm"><?php esc_html_e( 'শেষের তারিখ', 'ifs-parking' ); ?></label>
						<input type="date" name="end_date" value="<?php echo esc_attr( $end_date ); ?>" class="ifs-veh-input">
					</div>
					<div class="ifs-filter-buttons">
						<button type="submit" class="ifs-btn-filter-submit">
							<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/></svg>
							<?php esc_html_e( 'ফিল্টার প্রয়োগ', 'ifs-parking' ); ?>
						</button>
						<?php if ( ! empty( $start_date ) || ! empty( $end_date ) ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-reports' ) ); ?>" class="ifs-btn-filter-reset">
								<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg>
								<?php esc_html_e( 'রিসেট', 'ifs-parking' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</form>
		</div>

		<!-- KPI সামারি কার্ডস -->
		<div class="ifs-kpi-grid">
			<div class="ifs-dash-kpi ifs-kpi-blue">
				<div>
					<span class="ifs-kpi-label"><?php esc_html_e( 'মোট এন্ট্রি হওয়া গাড়ি', 'ifs-parking' ); ?></span>
					<h2 class="ifs-kpi-val"><?php echo esc_html( number_format( $total_veh_count ) ); ?> <span class="ifs-unit"><?php esc_html_e( 'টি', 'ifs-parking' ); ?></span></h2>
				</div>
				<div class="ifs-kpi-icon ifs-kpi-icon-blue">
					<svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.85 7h10.29l1.04 3H5.81l1.04-3zM19 17H5v-4.66l.12-.34h13.76l.12.34V17z"/><circle cx="7.5" cy="14.5" r="1.5"/><circle cx="16.5" cy="14.5" r="1.5"/></svg>
				</div>
			</div>

			<div class="ifs-dash-kpi ifs-kpi-green">
				<div>
					<span class="ifs-kpi-label"><?php esc_html_e( 'সর্বমোট সংগৃহীত আয়', 'ifs-parking' ); ?></span>
					<h2 class="ifs-kpi-val ifs-txt-fee-accent"><?php echo esc_html( $currency_symbol . number_format( $total_rev_sum, 2 ) ); ?></h2>
				</div>
				<div class="ifs-kpi-icon ifs-kpi-icon-green">
					<svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
				</div>
			</div>
		</div>

		<!-- মূল টেবিল কার্ড -->
		<div class="ifs-card ifs-content-card">
			<div class="ifs-card-header">
				<h3 class="ifs-card-title">
					<svg class="ifs-icon-brand" viewBox="0 0 24 24"><path d="M5 9.2h3V19H5zM10.6 5h2.8v14h-2.8zm5.6 8H19v6h-2.8z"/><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg>
					<?php esc_html_e( 'ক্যাটাগরি অনুযায়ী গাড়ি ও আয় বিবরণী', 'ifs-parking' ); ?>
				</h3>
			</div>
			
			<div class="ifs-table-responsive">
				<table class="wp-list-table widefat fixed striped ifs-custom-data-table">
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
									<td>
										<span class="ifs-badge-dept">
											<?php echo esc_html( ucfirst( $row->parking_type ) ); ?>
										</span>
									</td>
									<td class="ifs-txt-semi">
										<?php echo esc_html( number_format( $row->total_vehicles ) ); ?> <?php esc_html_e( 'টি গাড়ি', 'ifs-parking' ); ?>
									</td>
									<td>
										<span class="ifs-status-pill ifs-status-parked">
											<svg class="ifs-dot-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg>
											<?php echo esc_html( $row->active_parked ); ?> <?php esc_html_e( 'টি', 'ifs-parking' ); ?>
										</span>
									</td>
									<td>
										<span class="ifs-status-pill ifs-status-exited">
											<svg class="ifs-check-icon" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
											<?php echo esc_html( $row->total_exited ); ?> <?php esc_html_e( 'টি', 'ifs-parking' ); ?>
										</span>
									</td>
									<td class="ifs-td-action-right">
										<strong class="ifs-txt-fee-bold ifs-txt-lg">
											<?php echo esc_html( $currency_symbol . number_format( (float) $row->total_revenue, 2 ) ); ?>
										</strong>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="5" class="ifs-empty-table-cell">
									<?php esc_html_e( 'নির্বাচিত তারিখের জন্য কোনো রিপোর্ট ডাটা পাওয়া যায়নি।', 'ifs-parking' ); ?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php
}