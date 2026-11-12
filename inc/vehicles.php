<?php
/**
 * Vehicle Entry & Management Main Module (All-in-One Single Page Experience).
 *
 * @package IFS_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * গাড়ি ব্যবস্থাপনা পেজ রেন্ডারার।
 *
 * @return void
 */
function ifs_parking_render_vehicles_page() {
	if ( function_exists( 'ifs_parking_render_layout_wrapper' ) ) {
		ifs_parking_render_layout_wrapper( 'ifs_parking_render_vehicles_content' );
	} else {
		ifs_parking_render_vehicles_content();
	}
}

/**
 * মূল ভেহিকেল কনটেন্ট রেন্ডারার (সরাসরি একই পেজে চেক-ইন + ১-ক্লিক প্রিন্ট + রিসেন্ট এন্ট্রি)।
 *
 * @return void
 */
function ifs_parking_render_vehicles_content() {
	global $wpdb;

	$table_logs  = $wpdb->prefix . 'ifs_parking_logs';
	$table_cats  = $wpdb->prefix . 'ifs_parking_categories';
	$table_slots = $wpdb->prefix . 'ifs_parking_slots';
	$inc_dir     = plugin_dir_path( __FILE__ ) . 'vehicles/';

	// সেটিংস থেকে কারেন্সি ফেচ।
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

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$active_tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'add_vehicle';
	$action        = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	$log_id        = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$auto_print_id = isset( $_GET['auto_print'] ) ? absint( $_GET['auto_print'] ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	// চেক-আউট (গাড়ি রিলিজ) হ্যান্ডলার (GET লিংকের ফলব্যাক সাপোর্ট)।
	if ( 'checkout' === $action && $log_id > 0 && current_user_can( 'read' ) ) {
		check_admin_referer( 'ifs_checkout_vehicle_' . $log_id );

		if ( file_exists( $inc_dir . 'vehicle-delete.php' ) ) {
			require_once $inc_dir . 'vehicle-delete.php';
			if ( function_exists( 'ifs_parking_checkout_vehicle' ) ) {
				ifs_parking_checkout_vehicle( $table_logs, $log_id );
			}
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$log_item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_logs}` WHERE id = %d", $log_id ) );

			if ( $log_item && 'Parked' === $log_item->status ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table_logs,
					array(
						'exit_time' => current_time( 'mysql' ),
						'status'    => 'Exited',
					),
					array( 'id' => $log_id ),
					array( '%s', '%s' ),
					array( '%d' )
				);

				if ( ! empty( $log_item->slot_number ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->update(
						$table_slots,
						array( 'is_occupied' => 0 ),
						array( 'slot_name' => $log_item->slot_number ),
						array( '%d' ),
						array( '%s' )
					);
				}
			}
		}

		$active_tab = 'add_vehicle';
		$action     = '';
	}
	?>
	<style>
		.ifs-vehicles-wrapper {
			margin: 8px 12px 20px 0;
			box-sizing: border-box;
			font-family: 'Hind Siliguri', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
		}
		.ifs-top-quick-switch {
			max-width: 980px;
			margin: 0 auto 12px auto;
			display: flex;
			justify-content: flex-end;
			gap: 8px;
		}
		.ifs-mini-nav-link {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 7px 14px;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 700;
			text-decoration: none;
			background: #ffffff;
			color: #334155;
			border: 1px solid #cbd5e1;
		}
		.ifs-mini-nav-link.active {
			background: #0284c7;
			color: #ffffff;
			border-color: #0284c7;
		}
		@media screen and (max-width: 782px) {
			.ifs-vehicles-wrapper {
				margin: 6px 4px 20px 0;
			}
			.ifs-top-quick-switch {
				justify-content: stretch;
			}
			.ifs-mini-nav-link {
				flex: 1;
				justify-content: center;
				height: 40px;
			}
		}
	</style>

	<div class="wrap ifs-vehicles-wrapper">
		<!-- মিনিমাল টপ সুইচার (শুধু অ্যাডমিনের ফুল হিস্ট্রি দেখার জন্য, ডিফল্টভাবে চেক-ইন পেজ খোলা থাকবে) -->
		<?php if ( current_user_can( 'manage_options' ) ) : ?>
			<div class="ifs-top-quick-switch">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-vehicles&tab=add_vehicle' ) ); ?>"
				   class="ifs-mini-nav-link <?php echo ( 'add_vehicle' === $active_tab && empty( $action ) ) ? 'active' : ''; ?>">
					<?php esc_html_e( '+ চেক-ইন ও রিসেন্ট এন্ট্রি', 'ifs-parking' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-vehicles&tab=all_vehicles' ) ); ?>"
				   class="ifs-mini-nav-link <?php echo ( 'all_vehicles' === $active_tab && empty( $action ) ) ? 'active' : ''; ?>">
					<?php esc_html_e( 'সকল আর্কাইভ তালিকা', 'ifs-parking' ); ?>
				</a>
			</div>
		<?php endif; ?>

		<?php if ( 'view' === $action && $log_id > 0 ) : ?>
			<?php
			if ( file_exists( $inc_dir . 'vehicle-view.php' ) ) {
				require_once $inc_dir . 'vehicle-view.php';
				if ( function_exists( 'ifs_parking_render_vehicle_view' ) ) {
					ifs_parking_render_vehicle_view( $table_logs, $log_id, $currency_symbol );
				}
			}
			?>
		<?php elseif ( 'all_vehicles' === $active_tab && current_user_can( 'manage_options' ) ) : ?>
			<?php
			if ( file_exists( $inc_dir . 'vehicle-list.php' ) ) {
				require_once $inc_dir . 'vehicle-list.php';
				if ( function_exists( 'ifs_parking_render_vehicle_list' ) ) {
					ifs_parking_render_vehicle_list( $table_logs, $currency_symbol, $auto_print_id );
				}
			}
			?>
		<?php else : ?>
			<!-- ডিফল্ট: এক পেজেই চেক-ইন + ১-ক্লিক প্রিন্ট + রিসেন্ট এন্ট্রি ও রিলিজ -->
			<?php
			if ( file_exists( $inc_dir . 'vehicle-add-edit.php' ) ) {
				require_once $inc_dir . 'vehicle-add-edit.php';
				if ( function_exists( 'ifs_parking_render_vehicle_form' ) ) {
					ifs_parking_render_vehicle_form( $table_cats, $log_id, $action, $site_title, $system_phone, $currency_symbol );
				}
			}
			?>
		<?php endif; ?>
	</div>
	<?php
}