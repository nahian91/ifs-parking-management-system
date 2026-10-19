<?php
/**
 * Categories & Rates Management Module.
 *
 * @package Ifs_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ক্যাটাগরি ও রেট পেজ রেন্ডারার
 */
function ifs_parking_render_categories_page() {
	ifs_parking_render_layout_wrapper( 'ifs_parking_render_categories_content' );
}

/**
 * মূল ক্যাটাগরি কনটেন্ট
 */
function ifs_parking_render_categories_content() {
	global $wpdb;
	$table_cats = $wpdb->prefix . 'ifs_parking_categories';

	// সেটিংস থেকে কারেন্সি ফেচ করা
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

	$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'all_categories';
	$action     = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	$cat_id     = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
	$dept_name  = isset( $_GET['dept'] ) ? sanitize_text_field( wp_unslash( $_GET['dept'] ) ) : '';
	$inc_dir    = plugin_dir_path( __FILE__ ) . 'categories/';

	// ১. সম্পূর্ণ ডিপার্টমেন্ট ডিলিট হ্যান্ডলার
	if ( 'delete_dept' === $action && ! empty( $dept_name ) ) {
		check_admin_referer( 'ifs_delete_dept_' . $dept_name );

		if ( file_exists( $inc_dir . 'categories-delete.php' ) ) {
			require_once $inc_dir . 'categories-delete.php';
			if ( function_exists( 'ifs_parking_delete_department' ) ) {
				ifs_parking_delete_department( $table_cats, $dept_name );
			}
		} else {
			$wpdb->delete(
				$table_cats,
				array( 'department' => $dept_name ),
				array( '%s' )
			);
			echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
					<p class="ifs-notice-text">
						<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
						' . esc_html( sprintf( __( 'ডিপার্টমেন্ট (%s) এবং এর সকল প্যাকেজ সফলভাবে মুছে ফেলা হয়েছে!', 'ifs-parking' ), $dept_name ) ) . '
					</p>
				  </div>';
		}
		$active_tab = 'all_categories';
		$action     = '';
	}

	// ২. একক ক্যাটাগরি ডিলিট হ্যান্ডলার (ফলব্যাক সাপোর্ট)
	if ( 'delete' === $action && $cat_id > 0 ) {
		check_admin_referer( 'ifs_delete_category_' . $cat_id );

		if ( file_exists( $inc_dir . 'categories-delete.php' ) ) {
			require_once $inc_dir . 'categories-delete.php';
			if ( function_exists( 'ifs_parking_delete_category' ) ) {
				ifs_parking_delete_category( $table_cats, $cat_id );
			}
		} else {
			$wpdb->delete(
				$table_cats,
				array( 'id' => $cat_id ),
				array( '%d' )
			);
			echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
					<p class="ifs-notice-text">
						<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
						' . esc_html__( 'ক্যাটাগরি সফলভাবে ডিলিট করা হয়েছে!', 'ifs-parking' ) . '
					</p>
				  </div>';
		}
		$active_tab = 'all_categories';
		$action     = '';
	}

	// ৩. ফর্ম সাবমিট ও সেভ হ্যান্ডলার (Add & Edit)
	if ( isset( $_POST['ifs_cat_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ifs_cat_nonce'] ) ), 'ifs_save_category' ) ) {
		$orig_dept = isset( $_POST['orig_dept'] ) ? sanitize_text_field( wp_unslash( $_POST['orig_dept'] ) ) : '';

		if ( file_exists( $inc_dir . 'categories-add-edit.php' ) ) {
			require_once $inc_dir . 'categories-add-edit.php';
			if ( function_exists( 'ifs_parking_save_category' ) ) {
				ifs_parking_save_category( $table_cats, $orig_dept );
			}
		}
		$active_tab = 'all_categories';
		$action     = '';
	}
	?>
	<div class="wrap ifs-categories-wrapper">
		<!-- প্রিমিয়াম মডার্ন হেডার ব্যানার -->
		<div class="ifs-categories-banner">
			<div>
				<div class="ifs-banner-tag-wrap">
					<span class="ifs-banner-badge">
						<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>
						<?php esc_html_e( 'রেট ও ট্যারিফ মডিউল', 'ifs-parking' ); ?>
					</span>
				</div>
				<h1 class="ifs-banner-title"><?php esc_html_e( 'ক্যাটাগরি ও রেট ব্যবস্থাপনা', 'ifs-parking' ); ?></h1>
				<p class="ifs-banner-subtitle"><?php esc_html_e( 'ডিপার্টমেন্ট ও গাড়ির ধরণ অনুযায়ী প্যাকেজ রেট কনফিগারেশন।', 'ifs-parking' ); ?></p>
			</div>
			<?php if ( 'all_categories' === $active_tab && empty( $action ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-categories&tab=add_category' ) ); ?>" class="ifs-btn-primary-action">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
					<?php esc_html_e( 'নতুন ক্যাটাগরি যোগ করুন', 'ifs-parking' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<!-- Tabs Include -->
		<?php
		if ( file_exists( $inc_dir . 'categories-tabs.php' ) ) {
			require_once $inc_dir . 'categories-tabs.php';
			if ( function_exists( 'ifs_parking_render_categories_tabs' ) ) {
				ifs_parking_render_categories_tabs( $active_tab, $action );
			}
		}
		?>

		<!-- ভিউ সেকশন (ডিপার্টমেন্টের ভিত্তিতে) -->
		<?php if ( 'view' === $action && ! empty( $dept_name ) ) : ?>
			<?php
			if ( file_exists( $inc_dir . 'categories-view.php' ) ) {
				require_once $inc_dir . 'categories-view.php';
				if ( function_exists( 'ifs_parking_render_category_view' ) ) {
					ifs_parking_render_category_view( $table_cats, $dept_name, $currency_symbol );
				}
			}
			?>

		<!-- অ্যাড ও এডিট সেকশন -->
		<?php elseif ( 'add_category' === $active_tab || 'edit' === $action ) : ?>
			<?php
			if ( file_exists( $inc_dir . 'categories-add-edit.php' ) ) {
				require_once $inc_dir . 'categories-add-edit.php';
				if ( function_exists( 'ifs_parking_render_category_form' ) ) {
					ifs_parking_render_category_form( $table_cats, $dept_name, $action, $currency_setting, $currency_symbol );
				}
			}
			?>

		<!-- মূল তালিকা ভিউ -->
		<?php else : ?>
			<?php
			if ( file_exists( $inc_dir . 'categories-list.php' ) ) {
				require_once $inc_dir . 'categories-list.php';
				if ( function_exists( 'ifs_parking_render_category_list' ) ) {
					ifs_parking_render_category_list( $table_cats, $currency_symbol );
				}
			}
			?>
		<?php endif; ?>
	</div>
	<?php
}