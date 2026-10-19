<?php
/**
 * Vehicles Tab Navigation Template.
 *
 * @package Ifs_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ট্যাব নেভিগেশন রেন্ডারার
 *
 * @param string $active_tab বর্তমান ট্যাব।
 * @param string $action     বর্তমান অ্যাকশন।
 */
function ifs_parking_render_vehicles_tabs( $active_tab, $action ) {
	$all_url = admin_url( 'admin.php?page=ifs-parking-vehicles&tab=all_vehicles' );
	$add_url = admin_url( 'admin.php?page=ifs-parking-vehicles&tab=add_vehicle' );
	?>
	<div class="ifs-tabs">
		<a href="<?php echo esc_url( $all_url ); ?>" class="ifs-tab-btn <?php echo 'all_vehicles' === $active_tab && empty( $action ) ? 'active' : ''; ?>">
			<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
			<?php esc_html_e( 'সকল গাড়ি তালিকা', 'ifs-parking' ); ?>
		</a>
		<a href="<?php echo esc_url( $add_url ); ?>" class="ifs-tab-btn <?php echo 'add_vehicle' === $active_tab || 'edit' === $action ? 'active' : ''; ?>">
			<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
			<?php esc_html_e( 'নতুন গাড়ি চেক-ইন', 'ifs-parking' ); ?>
		</a>
	</div>
	<?php
}