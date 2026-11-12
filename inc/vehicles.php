<?php
/**
 * Vehicle Entry & Management Main Module.
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
 * মূল ভেহিকেল কনটেন্ট রেন্ডারার।
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
	$active_tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'all_vehicles';
	$action        = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	$log_id        = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$auto_print_id = isset( $_GET['auto_print'] ) ? absint( $_GET['auto_print'] ) : 0;
	$is_saved      = isset( $_GET['saved'] ) ? absint( $_GET['saved'] ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	// চেক-আউট (গাড়ি রিলিজ) হ্যান্ডলার।
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

				// সংশ্লিষ্ট স্লটটি খালি করা।
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

				echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
						<p class="ifs-notice-text">
							<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
							' . esc_html(
								sprintf(
									/* translators: %s: গাড়ির নম্বর */
									__( 'গাড়ি (%s) সফলভাবে রিলিজ সম্পন্ন হয়েছে!', 'ifs-parking' ),
									$log_item->vehicle_number
								)
							) . '
						</p>
					  </div>';
			}
		}

		$active_tab = 'all_vehicles';
		$action     = '';
	}

	// নতুন গাড়ি চেক-ইন ও সেভ হ্যান্ডলার।
	if (
		isset( $_POST['ifs_vehicle_nonce'] ) &&
		wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ifs_vehicle_nonce'] ) ), 'ifs_save_vehicle' ) &&
		current_user_can( 'read' )
	) {
		if ( file_exists( $inc_dir . 'vehicle-add-edit.php' ) ) {
			require_once $inc_dir . 'vehicle-add-edit.php';
			if ( function_exists( 'ifs_parking_save_vehicle_entry' ) ) {
				ifs_parking_save_vehicle_entry( $table_logs, $table_cats );
			}
		}
	}

	if ( $is_saved > 0 ) {
		echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
				<p class="ifs-notice-text">
					<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
					' . esc_html__( 'গাড়ি সফলভাবে চেক-ইন করা হয়েছে এবং রসিদ প্রিন্ট প্রস্তুত!', 'ifs-parking' ) . '
				</p>
			  </div>';
	}
	?>
	<style>
		/* ভেহিক্যাল মডিউল রেসপনসিভনেস ও প্রমিত বাংলা টাইপোগ্রাফি */
		.ifs-vehicles-wrapper {
			margin: 10px 15px 20px 0;
			box-sizing: border-box;
			font-family: 'Hind Siliguri', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
		}
		.ifs-vehicles-banner {
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 14px;
			margin-bottom: 20px;
		}

		/* ট্যাবলেট এবং মোবাইল ভিউপোর্ট রুলস */
		@media screen and (max-width: 782px) {
			.ifs-vehicles-wrapper {
				margin: 10px 5px 20px 0;
			}
			.ifs-vehicles-banner {
				flex-direction: column;
				align-items: stretch;
			}
			.ifs-btn-primary-action {
				width: 100%;
				justify-content: center;
				box-sizing: border-box;
				height: 44px;
			}
		}
	</style>

	<div class="wrap ifs-vehicles-wrapper">
		<!-- হেডার ব্যানার -->
		<div class="ifs-vehicles-banner">
			<div>
				<span class="ifs-banner-badge">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z"/></svg>
					<?php esc_html_e( 'এন্ট্রি ও চেক-ইন মডিউল', 'ifs-parking' ); ?>
				</span>
				<h1 class="ifs-banner-title"><?php esc_html_e( 'গাড়ি ও পার্কিং ব্যবস্থাপনা', 'ifs-parking' ); ?></h1>
				<p class="ifs-banner-subtitle"><?php esc_html_e( 'ডিপার্টমেন্ট ➔ গাড়ির ধরণ ➔ আলাদা প্যাকেজ ট্যাব ও ৩ কলাম বিলিং রেট।', 'ifs-parking' ); ?></p>
			</div>
			<?php if ( 'all_vehicles' === $active_tab && empty( $action ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-vehicles&tab=add_vehicle' ) ); ?>" class="ifs-btn-primary-action">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
					<?php esc_html_e( 'নতুন গাড়ি চেক-ইন', 'ifs-parking' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<!-- ট্যাব নেভিগেশন (vehicle-tab.php থেকে লোড) -->
		<?php
		if ( file_exists( $inc_dir . 'vehicle-tab.php' ) ) {
			require_once $inc_dir . 'vehicle-tab.php';
			if ( function_exists( 'ifs_parking_render_vehicles_tabs' ) ) {
				ifs_parking_render_vehicles_tabs( $active_tab, $action );
			}
		}
		?>

		<?php if ( 'view' === $action && $log_id > 0 ) : ?>
			<?php
			if ( file_exists( $inc_dir . 'vehicle-view.php' ) ) {
				require_once $inc_dir . 'vehicle-view.php';
				if ( function_exists( 'ifs_parking_render_vehicle_view' ) ) {
					ifs_parking_render_vehicle_view( $table_logs, $log_id, $currency_symbol );
				}
			}
			?>
		<?php elseif ( 'add_vehicle' === $active_tab || 'edit' === $action ) : ?>
			<?php
			if ( file_exists( $inc_dir . 'vehicle-add-edit.php' ) ) {
				require_once $inc_dir . 'vehicle-add-edit.php';
				if ( function_exists( 'ifs_parking_render_vehicle_form' ) ) {
					ifs_parking_render_vehicle_form( $table_cats, $log_id, $action, $site_title, $system_phone, $currency_symbol );
				}
			}
			?>
		<?php else : ?>
			<?php
			if ( file_exists( $inc_dir . 'vehicle-list.php' ) ) {
				require_once $inc_dir . 'vehicle-list.php';
				if ( function_exists( 'ifs_parking_render_vehicle_list' ) ) {
					ifs_parking_render_vehicle_list( $table_logs, $currency_symbol, $auto_print_id );
				}
			}
			?>
		<?php endif; ?>
	</div>
	<?php
}