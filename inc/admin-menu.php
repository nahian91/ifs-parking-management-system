<?php
/**
 * Admin Menu & Standalone Dashboard Layout Wrapper.
 *
 * @package Ifs_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ওয়ার্ডপ্রেস এডমিন মেনু ও সাবমেনু নিবন্ধন
 */
function ifs_parking_admin_menu() {
	$capability = 'manage_options';

	// মূল মেনু পেজ (ড্যাশবোর্ড)
	add_menu_page(
		__( 'IFS Parking', 'ifs-parking' ),
		__( 'IFS পার্কিং', 'ifs-parking' ),
		$capability,
		'ifs-parking-system',
		'ifs_parking_render_dashboard_page',
		'dashicons-car',
		6
	);

	// সাবমেনু পেজসমূহ
	add_submenu_page(
		'ifs-parking-system',
		__( 'ড্যাশবোর্ড', 'ifs-parking' ),
		__( 'ড্যাশবোর্ড', 'ifs-parking' ),
		$capability,
		'ifs-parking-system',
		'ifs_parking_render_dashboard_page'
	);

	add_submenu_page(
		'ifs-parking-system',
		__( 'লাইভ স্ট্যাটাস', 'ifs-parking' ),
		__( 'লাইভ স্ট্যাটাস', 'ifs-parking' ),
		$capability,
		'ifs-parking-live-status',
		'ifs_parking_render_live_status_page'
	);

	add_submenu_page(
		'ifs-parking-system',
		__( 'গাড়ি ব্যবস্থাপনা', 'ifs-parking' ),
		__( 'গাড়িসমূহ', 'ifs-parking' ),
		$capability,
		'ifs-parking-vehicles',
		'ifs_parking_render_vehicles_page'
	);

	add_submenu_page(
		'ifs-parking-system',
		__( 'পার্কিং স্লট', 'ifs-parking' ),
		__( 'স্লট ম্যানেজমেন্ট', 'ifs-parking' ),
		$capability,
		'ifs-parking-slots',
		'ifs_parking_render_slots_page'
	);

	add_submenu_page(
		'ifs-parking-system',
		__( 'ক্যাটাগরি ব্যবস্থাপনা', 'ifs-parking' ),
		__( 'ক্যাটাগরি', 'ifs-parking' ),
		$capability,
		'ifs-parking-categories',
		'ifs_parking_render_categories_page'
	);

	add_submenu_page(
		'ifs-parking-system',
		__( 'মেম্বারশিপ', 'ifs-parking' ),
		__( 'মেম্বারশিপ', 'ifs-parking' ),
		$capability,
		'ifs-parking-memberships',
		'ifs_parking_render_memberships_page'
	);

	add_submenu_page(
		'ifs-parking-system',
		__( 'রিপোর্ট ও অ্যানালিটিক্স', 'ifs-parking' ),
		__( 'রিপোর্ট', 'ifs-parking' ),
		$capability,
		'ifs-parking-reports',
		'ifs_parking_render_reports_page'
	);

	add_submenu_page(
		'ifs-parking-system',
		__( 'স্টাফ তালিকা', 'ifs-parking' ),
		__( 'স্টাফ', 'ifs-parking' ),
		$capability,
		'ifs-parking-staff',
		'ifs_parking_render_staff_page'
	);

	add_submenu_page(
		'ifs-parking-system',
		__( 'সিস্টেম সেটিংস', 'ifs-parking' ),
		__( 'সেটিংস', 'ifs-parking' ),
		$capability,
		'ifs-parking-settings',
		'ifs_parking_render_settings_page'
	);
}
add_action( 'admin_menu', 'ifs_parking_admin_menu' );

/**
 * কাস্টম ফুল-স্ক্রিন অ্যাপ লেআউট র‍্যাপার
 *
 * @param callable $content_callback পেজের ভেতরের কনটেন্ট রেন্ডার করার ফাংশন।
 */
function ifs_parking_render_layout_wrapper( $content_callback ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'আপনার এই পেজে প্রবেশের অনুমতি নেই।', 'ifs-parking' ) );
	}

	$current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'ifs-parking-system';
	$site_title   = get_option( 'ifs_parking_site_title', 'IFS Parking System' );
	$site_logo    = get_option( 'ifs_parking_site_logo', '' );
	?>
	<!-- মোবাইল হেডার ও বার্গার মেনু বার -->
	<div class="ifs-mobile-topbar">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-system' ) ); ?>" class="ifs-mobile-brand">
			<?php if ( ! empty( $site_logo ) ) : ?>
				<img src="<?php echo esc_url( $site_logo ); ?>" alt="<?php echo esc_attr( $site_title ); ?>" class="ifs-mobile-logo-img">
			<?php else : ?>
				<div class="ifs-mobile-icon-fallback">
					<svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z"/></svg>
				</div>
			<?php endif; ?>
			<span><?php echo esc_html( $site_title ); ?></span>
		</a>
		<button type="button" id="ifs-mobile-menu-toggle" class="ifs-mobile-menu-btn" aria-label="Toggle Menu">
			<svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
		</button>
	</div>

	<div class="ifs-app-layout">
		<!-- কাস্টম সাইডবার -->
		<aside class="ifs-sidebar-col" id="ifs-sidebar-element">
			<div class="ifs-sidebar-header">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-system' ) ); ?>" class="ifs-brand-wrap">
					<?php if ( ! empty( $site_logo ) ) : ?>
						<img src="<?php echo esc_url( $site_logo ); ?>" alt="<?php echo esc_attr( $site_title ); ?>" class="ifs-brand-logo-img">
					<?php else : ?>
						<div class="ifs-brand-icon-fallback">
							<svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.85 7h10.29l1.04 3H5.81l1.04-3zM19 17H5v-4.66l.12-.34h13.76l.12.34V17z"/><circle cx="7.5" cy="14.5" r="1.5"/><circle cx="16.5" cy="14.5" r="1.5"/></svg>
						</div>
					<?php endif; ?>
					<h2 class="ifs-brand-title"><?php echo esc_html( $site_title ); ?></h2>
				</a>
			</div>

			<nav>
				<ul>
					<!-- ড্যাশবোর্ড -->
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-system' ) ); ?>" class="<?php echo 'ifs-parking-system' === $current_page ? 'active' : ''; ?>">
							<svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg> 
							<?php esc_html_e( 'ড্যাশবোর্ড', 'ifs-parking' ); ?>
						</a>
					</li>

					<!-- লাইভ স্ট্যাটাস -->
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-live-status' ) ); ?>" class="<?php echo 'ifs-parking-live-status' === $current_page ? 'active' : ''; ?>">
							<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8zm-3-11c-.83 0-1.5-.67-1.5-1.5S11.17 2 12 2s1.5.67 1.5 1.5S12.83 5 12 5z"/><circle cx="12" cy="12" r="3"/><path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zm0 8c-1.65 0-3-1.35-3-3s1.35-3 3-3 3 1.35 3 3-1.35 3-3 3z"/></svg> 
							<?php esc_html_e( 'লাইভ স্ট্যাটাস', 'ifs-parking' ); ?>
						</a>
					</li>

					<!-- গাড়িসমূহ -->
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-vehicles' ) ); ?>" class="<?php echo 'ifs-parking-vehicles' === $current_page ? 'active' : ''; ?>">
							<svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.85 7h10.29l1.04 3H5.81l1.04-3z"/></svg> 
							<?php esc_html_e( 'গাড়িসমূহ', 'ifs-parking' ); ?>
						</a>
					</li>

					<!-- স্লট ম্যানেজমেন্ট -->
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-slots' ) ); ?>" class="<?php echo 'ifs-parking-slots' === $current_page ? 'active' : ''; ?>">
							<svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 10h2v7H7zm4-3h2v10h-2zm4 5h2v5h-2z"/></svg> 
							<?php esc_html_e( 'স্লট ম্যানেজমেন্ট', 'ifs-parking' ); ?>
						</a>
					</li>

					<!-- ক্যাটাগরি -->
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-categories' ) ); ?>" class="<?php echo 'ifs-parking-categories' === $current_page ? 'active' : ''; ?>">
							<svg viewBox="0 0 24 24"><path d="M12 2l-5.5 9h11z"/><circle cx="17.5" cy="17.5" r="4.5"/><path d="M3 13.5h8v8H3z"/></svg> 
							<?php esc_html_e( 'ক্যাটাগরি', 'ifs-parking' ); ?>
						</a>
					</li>

					<!-- মেম্বারশিপ -->
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-memberships' ) ); ?>" class="<?php echo 'ifs-parking-memberships' === $current_page ? 'active' : ''; ?>">
							<svg viewBox="0 0 24 24"><path d="M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4V8h5.08L7 10.83 8.62 12 11 8.76l1-1.36 1 1.36L15.38 12 17 10.83 14.92 8H20v6z"/></svg> 
							<?php esc_html_e( 'মেম্বারশিপ', 'ifs-parking' ); ?>
						</a>
					</li>

					<!-- রিপোর্ট -->
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-reports' ) ); ?>" class="<?php echo 'ifs-parking-reports' === $current_page ? 'active' : ''; ?>">
							<svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg> 
							<?php esc_html_e( 'রিপোর্ট', 'ifs-parking' ); ?>
						</a>
					</li>

					<!-- স্টাফ -->
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-staff' ) ); ?>" class="<?php echo 'ifs-parking-staff' === $current_page ? 'active' : ''; ?>">
							<svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg> 
							<?php esc_html_e( 'স্টাফ', 'ifs-parking' ); ?>
						</a>
					</li>

					<!-- সেটিংস -->
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-settings' ) ); ?>" class="<?php echo 'ifs-parking-settings' === $current_page ? 'active' : ''; ?>">
							<svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36.25c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c-.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg> 
							<?php esc_html_e( 'সেটিংস', 'ifs-parking' ); ?>
						</a>
					</li>

					<li><hr style="border-color: rgba(255,255,255,0.06); margin: 20px 24px;"></li>

					<!-- সাইন আউট -->
					<li>
						<a href="<?php echo esc_url( wp_logout_url( admin_url() ) ); ?>" class="ifs-signout-link">
							<svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg> 
							<?php esc_html_e( 'Sign Out', 'ifs-parking' ); ?>
						</a>
					</li>
				</ul>
			</nav>
		</aside>

		<!-- মূল কনটেন্ট এরিয়া -->
		<main class="ifs-content-col">
			<?php
			if ( is_callable( $content_callback ) ) {
				call_user_func( $content_callback );
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'কনটেন্ট কলব্যাক ফাংশন পাওয়া যায়নি।', 'ifs-parking' ) . '</p></div>';
			}
			?>
		</main>
	</div>

	<!-- মোবাইল মেনু টগল জাভাস্ক্রিপ্ট -->
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		const toggleBtn = document.getElementById('ifs-mobile-menu-toggle');
		const sidebar = document.getElementById('ifs-sidebar-element');

		if (toggleBtn && sidebar) {
			toggleBtn.addEventListener('click', function() {
				sidebar.classList.toggle('mobile-open');
			});

			// মেনু আইটেমে ক্লিক করলে মোবাইল ভিউতে মেনু অটো বন্ধ হবে
			sidebar.querySelectorAll('a').forEach(link => {
				link.addEventListener('click', function() {
					if (window.innerWidth <= 1024) {
						sidebar.classList.remove('mobile-open');
					}
				});
			});
		}
	});
	</script>
	<?php
}