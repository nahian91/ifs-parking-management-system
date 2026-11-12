<?php
/**
 * Vehicles Tab Navigation Template.
 *
 * @package IFS_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ট্যাব নেভিগেশন রেন্ডারার।
 *
 * @param string $active_tab বর্তমান ট্যাব।
 * @param string $action     বর্তমান অ্যাকশন।
 * @return void
 */
function ifs_parking_render_vehicles_tabs( $active_tab, $action ) {
	$all_url = admin_url( 'admin.php?page=ifs-parking-vehicles&tab=all_vehicles' );
	$add_url = admin_url( 'admin.php?page=ifs-parking-vehicles&tab=add_vehicle' );

	$is_all_active = ( 'all_vehicles' === $active_tab && empty( $action ) );
	$is_add_active = ( 'add_vehicle' === $active_tab || 'edit' === $action );
	$second_label  = ( 'edit' === $action )
		? __( 'গাড়ির তথ্য সম্পাদনা', 'ifs-parking' )
		: __( 'নতুন গাড়ি চেক-ইন', 'ifs-parking' );
	?>
	<style>
		/* ভেহিকেল ট্যাব নেভিগেশন রেসপনসিভনেস ও প্রমিত বাংলা টাইপোগ্রাফি */
		.ifs-tabs {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
			margin-bottom: 20px;
			font-family: 'Hind Siliguri', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
		}
		.ifs-tab-btn {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			text-decoration: none;
			box-sizing: border-box;
			white-space: nowrap;
			transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
		}

		/* ট্যাবলেট এবং মোবাইল ভিউপোর্ট রুলস */
		@media screen and (max-width: 782px) {
			.ifs-tabs {
				flex-direction: column;
				gap: 8px;
				margin-bottom: 16px;
			}
			.ifs-tab-btn {
				width: 100%;
				justify-content: center;
				height: 44px;
				padding: 10px 14px;
				white-space: normal;
				text-align: center;
			}
		}
	</style>

	<nav class="ifs-tabs" aria-label="<?php esc_attr_e( 'গাড়ি ব্যবস্থাপনা ট্যাব', 'ifs-parking' ); ?>">
		<a href="<?php echo esc_url( $all_url ); ?>"
		   class="ifs-tab-btn <?php echo $is_all_active ? 'active' : ''; ?>"
		   <?php echo $is_all_active ? 'aria-current="page"' : ''; ?>>
			<svg class="ifs-svg-sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
			<?php esc_html_e( 'সকল গাড়ির তালিকা', 'ifs-parking' ); ?>
		</a>

		<a href="<?php echo esc_url( $add_url ); ?>"
		   class="ifs-tab-btn <?php echo $is_add_active ? 'active' : ''; ?>"
		   <?php echo $is_add_active ? 'aria-current="page"' : ''; ?>>
			<?php if ( 'edit' === $action ) : ?>
				<svg class="ifs-svg-sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
			<?php else : ?>
				<svg class="ifs-svg-sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
			<?php endif; ?>
			<?php echo esc_html( $second_label ); ?>
		</a>
	</nav>
	<?php
}