<?php
/**
 * Categories Tab Navigation Template.
 *
 * @package Ifs_Parking
 */

// সরাসরি ফাইল অ্যাক্সেস বন্ধ করা
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ক্যাটাগরি ট্যাব সুইচার নেভিগেশন রেন্ডারার
 *
 * @param string $active_tab বর্তমান সক্রিয় ট্যাব স্ল্যাগ।
 * @param string $action     বর্তমান অ্যাকশন প্যারামিটার ('view', 'edit', বা ফাঁকা)।
 * @return void
 */
function ifs_parking_render_categories_tabs( $active_tab, $action ) {
	$all_cats_url = admin_url( 'admin.php?page=ifs-parking-categories&tab=all_categories' );
	$add_cat_url  = admin_url( 'admin.php?page=ifs-parking-categories&tab=add_category' );

	// ডিপার্টমেন্ট অথবা সিঙ্গেল আইডি প্যারামিটার ফেচ
	$dept_name = isset( $_GET['dept'] ) ? sanitize_text_field( wp_unslash( $_GET['dept'] ) ) : '';
	$cat_id    = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	?>
	<nav class="ifs-tabs-container">
		<!-- ট্যাব ১: সকল ক্যাটাগরি -->
		<a href="<?php echo esc_url( $all_cats_url ); ?>" class="ifs-tab-link <?php echo ( 'all_categories' === $active_tab && empty( $action ) ) ? 'active' : ''; ?>">
			<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>
			<?php esc_html_e( 'সকল ক্যাটাগরি', 'ifs-parking' ); ?>
		</a>

		<!-- ট্যাব ২: নতুন ক্যাটাগরি যোগ / সম্পাদনা -->
		<a href="<?php echo esc_url( $add_cat_url ); ?>" class="ifs-tab-link <?php echo ( 'add_category' === $active_tab || 'edit' === $action ) ? 'active' : ''; ?>">
			<?php if ( 'edit' === $action ) : ?>
				<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
				<?php
				if ( ! empty( $dept_name ) ) {
					echo esc_html( sprintf( __( 'সম্পাদনা: %s', 'ifs-parking' ), $dept_name ) );
				} else {
					esc_html_e( 'ক্যাটাগরি সম্পাদনা', 'ifs-parking' );
				}
				?>
			<?php else : ?>
				<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
				<?php esc_html_e( 'নতুন ক্যাটাগরি যোগ করুন', 'ifs-parking' ); ?>
			<?php endif; ?>
		</a>

		<!-- ট্যাব ৩: ক্যাটাগরি বিস্তারিত ভিউ (ডিপার্টমেন্ট অথবা আইডি ভিত্তিক) -->
		<?php if ( 'view' === $action && ( ! empty( $dept_name ) || $cat_id > 0 ) ) : ?>
			<?php
			$view_url = ! empty( $dept_name )
				? admin_url( 'admin.php?page=ifs-parking-categories&action=view&dept=' . urlencode( $dept_name ) )
				: admin_url( 'admin.php?page=ifs-parking-categories&action=view&id=' . $cat_id );
			?>
			<a href="<?php echo esc_url( $view_url ); ?>" class="ifs-tab-link active">
				<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
				<?php
				if ( ! empty( $dept_name ) ) {
					echo esc_html( sprintf( __( 'বিস্তারিত: %s', 'ifs-parking' ), $dept_name ) );
				} else {
					esc_html_e( 'ক্যাটাগরি বিস্তারিত', 'ifs-parking' );
				}
				?>
			</a>
		<?php endif; ?>
	</nav>
	<?php
}