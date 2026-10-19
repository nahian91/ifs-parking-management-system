<?php
/**
 * Vehicle Details View Template.
 *
 * @package Ifs_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * একক গাড়ির বিস্তারিত ভিউ রেন্ডারার
 *
 * @param string $table_logs      লগ টেবিল নাম।
 * @param int    $log_id          লগ আইডি।
 * @param string $currency_symbol কারেন্সি প্রতীক।
 */
function ifs_parking_render_vehicle_view( $table_logs, $log_id, $currency_symbol ) {
	global $wpdb;
	$log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_logs WHERE id = %d", $log_id ) );

	if ( ! $log ) {
		echo '<div class="notice notice-error ifs-custom-notice-err"><p>' . esc_html__( 'রেকর্ড পাওয়া যায়নি।', 'ifs-parking' ) . '</p></div>';
		return;
	}
	?>
	<div class="ifs-card ifs-view-container">
		<div class="ifs-view-header">
			<div>
				<h3 class="ifs-view-title"><?php esc_html_e( 'পার্কিং লগ বিস্তারিত বিবরণ', 'ifs-parking' ); ?></h3>
				<p class="ifs-card-subtitle">ID #<?php echo esc_html( $log->id ); ?> - <?php echo esc_html( $log->vehicle_number ); ?></p>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-vehicles' ) ); ?>" class="ifs-btn-back">
				<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
				<?php esc_html_e( 'তালিকায় ফিরে যান', 'ifs-parking' ); ?>
			</a>
		</div>

		<div class="ifs-view-grid">
			<div class="ifs-info-tile">
				<span class="ifs-tile-label"><?php esc_html_e( 'গাড়ি নম্বর', 'ifs-parking' ); ?></span>
				<strong class="ifs-tile-value"><?php echo esc_html( $log->vehicle_number ); ?></strong>
			</div>
			<div class="ifs-info-tile">
				<span class="ifs-tile-label"><?php esc_html_e( 'স্ট্যাটাস', 'ifs-parking' ); ?></span>
				<span class="ifs-tile-value"><?php echo esc_html( $log->status ); ?></span>
			</div>
			<div class="ifs-info-tile">
				<span class="ifs-tile-label"><?php esc_html_e( 'চালক', 'ifs-parking' ); ?></span>
				<span class="ifs-tile-value"><?php echo esc_html( ! empty( $log->driver_name ) ? $log->driver_name : 'N/A' ); ?></span>
			</div>
			<div class="ifs-info-tile">
				<span class="ifs-tile-label"><?php esc_html_e( 'প্রযুক্ত ফি', 'ifs-parking' ); ?></span>
				<span class="ifs-tile-value"><?php echo esc_html( $currency_symbol . number_format( (float) $log->fee, 2 ) ); ?></span>
			</div>
		</div>
	</div>
	<?php
}