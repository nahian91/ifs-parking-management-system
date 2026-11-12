<?php
/**
 * Plugin Name:       IFS Parking Management System
 * Plugin URI:        https://devnahian.com/ifs-parking
 * Description:       আব্দুল্লাহ নাইয়ান-এর জন্য তৈরি এক্সক্লুসিভ এডমিন প্যানেল এবং সেপারেট ডাটাবেজ সংবলিত পার্কিং সিস্টেম।
 * Version:           1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Abdullah Nahian
 * Author URI:        https://devnahian.com
 * Text Domain:       ifs-parking
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package IFS_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// প্লাগইন কনস্ট্যান্টসমূহ।
define( 'IFS_PARKING_VERSION', '1.1.0' );
define( 'IFS_PARKING_FILE', __FILE__ );
define( 'IFS_PARKING_PATH', plugin_dir_path( __FILE__ ) );
define( 'IFS_PARKING_URL', plugin_dir_url( __FILE__ ) );

/**
 * বাংলা ও অন্যান্য ভাষার অনুবাদ (Text Domain) লোড করা।
 *
 * @return void
 */
function ifs_parking_load_textdomain() {
	load_plugin_textdomain(
		'ifs-parking',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'plugins_loaded', 'ifs_parking_load_textdomain' );

/**
 * প্লাগইনের প্রয়োজনীয় মডিউল ফাইলগুলো নিরাপদভাবে লোড করা।
 *
 * @return void
 */
function ifs_parking_include_modules() {
	$inc_dir = IFS_PARKING_PATH . 'inc/';

	$modules = array(
		// কোর ও ডেটাবেজ মডিউল।
		'database.php',
		'admin-menu.php',
		// ফিচার ও পেজ মডিউলসমূহ।
		'dashboard.php',
		'live-status.php',
		'vehicles.php',
		'slots.php',
		'categories.php',
		'membership.php',
		'reports.php',
		'staff.php',
		'settings.php',
	);

	foreach ( $modules as $module ) {
		$file_path = $inc_dir . $module;
		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		}
	}
}
ifs_parking_include_modules();

/**
 * প্লাগইন অ্যাক্টিভেশন হুক।
 *
 * @return void
 */
function ifs_parking_plugin_activation() {
	if ( function_exists( 'ifs_parking_activate' ) ) {
		ifs_parking_activate();
	}
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ifs_parking_plugin_activation' );

/**
 * প্লাগইন ডিঅ্যাক্টিভেশন হুক।
 *
 * @return void
 */
function ifs_parking_plugin_deactivation() {
	if ( function_exists( 'ifs_parking_deactivate' ) ) {
		ifs_parking_deactivate();
	}
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'ifs_parking_plugin_deactivation' );

/**
 * এডমিন স্ক্রিপ্ট, বাংলা ফন্ট ও স্টাইল এনকিউ।
 *
 * @param string $hook কারেন্ট এডমিন পেজ হুক।
 * @return void
 */
function ifs_parking_admin_enqueue_assets( $hook ) {
	if ( false === strpos( $hook, 'ifs-parking' ) ) {
		return;
	}

	// প্রমিত বাংলা ফন্ট (Hind Siliguri) এবং ইংরেজি ফন্ট (Plus Jakarta Sans)।
	wp_enqueue_style(
		'ifs-parking-fonts',
		'https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap',
		array(),
		IFS_PARKING_VERSION
	);

	wp_enqueue_style(
		'ifs-parking-admin-css',
		IFS_PARKING_URL . 'assets/css/admin.css',
		array( 'ifs-parking-fonts' ),
		IFS_PARKING_VERSION,
		'all'
	);

	wp_enqueue_script(
		'ifs-parking-admin-js',
		IFS_PARKING_URL . 'assets/js/admin.js',
		array( 'jquery' ),
		IFS_PARKING_VERSION,
		true
	);

	global $wpdb;
	$table_cats     = $wpdb->prefix . 'ifs_parking_categories';
	$all_categories = array();

	// টেবিল ডেটাবেজে বিদ্যমান থাকলে ক্যাটাগরি লোড করা।
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_cats ) ) === $table_cats ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$all_categories = $wpdb->get_results( "SELECT * FROM `{$table_cats}` ORDER BY id ASC" );
	}

	$currency_setting = get_option( 'ifs_parking_currency', 'BDT (৳)' );
	$currency_symbol  = '৳';

	if ( false !== strpos( $currency_setting, '৳' ) ) {
		$currency_symbol = '৳';
	} elseif ( false !== strpos( $currency_setting, '€' ) ) {
		$currency_symbol = '€';
	} elseif ( false !== strpos( $currency_setting, '£' ) ) {
		$currency_symbol = '£';
	} elseif ( false !== strpos( $currency_setting, '$' ) ) {
		$currency_symbol = '$';
	}

	wp_localize_script(
		'ifs-parking-admin-js',
		'ifsParkingData',
		array(
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'ifs_parking_admin_nonce' ),
			'currency'   => $currency_symbol,
			'locale'     => get_locale(),
			'categories' => ! empty( $all_categories ) ? $all_categories : array(),
			'i18n'       => array(
				'confirmDelete' => __( 'আপনি কি নিশ্চিতভাবে এটি মুছে ফেলতে চান?', 'ifs-parking' ),
				'processing'    => __( 'অপেক্ষা করুন...', 'ifs-parking' ),
				'errorOccurred' => __( 'একটি ত্রুটি ঘটেছে। অনুগ্রহ করে আবার চেষ্টা করুন।', 'ifs-parking' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'ifs_parking_admin_enqueue_assets' );

/**
 * ফ্রন্টএন্ড ব্লক: সাধারণ ভিজিটর ফ্রন্ট পেজে ঢুকলে সরাসরি লগইন স্ক্রিনে রিডাইরেক্ট হবে।
 *
 * @return void
 */
function ifs_parking_disable_frontend_redirect() {
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return;
	}

	$pagenow = isset( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : '';

	if ( ! is_user_logged_in() && ! is_admin() && 'wp-login.php' !== $pagenow ) {
		wp_safe_redirect( wp_login_url() );
		exit;
	} elseif ( is_user_logged_in() && ! is_admin() ) {
		wp_safe_redirect( admin_url( 'admin.php?page=ifs-parking-system' ) );
		exit;
	}
}
add_action( 'template_redirect', 'ifs_parking_disable_frontend_redirect' );

/**
 * লগইনের পর সরাসরি প্লাগইন ড্যাশবোর্ডে রিডাইরেক্ট।
 *
 * @param string           $redirect_to ডিফল্ট রিডাইরেক্ট ইউআরএল।
 * @param string           $request     রিকোয়েস্ট ইউআরএল।
 * @param WP_User|WP_Error $user        ইউজার অবজেক্ট।
 * @return string
 */
function ifs_parking_login_redirect( $redirect_to, $request, $user ) {
	if ( isset( $user->roles ) && is_array( $user->roles ) ) {
		return admin_url( 'admin.php?page=ifs-parking-system' );
	}
	return $redirect_to;
}
add_filter( 'login_redirect', 'ifs_parking_login_redirect', 10, 3 );

/**
 * ওয়ার্ডপ্রেসের ডিফল্ট লোগো লিংক পরিবর্তন।
 *
 * @return string
 */
function ifs_parking_login_logo_url() {
	return admin_url( 'admin.php?page=ifs-parking-system' );
}
add_filter( 'login_headerurl', 'ifs_parking_login_logo_url' );

/**
 * ওয়ার্ডপ্রেসের ডিফল্ট লোগো টেক্সট পরিবর্তন।
 *
 * @return string
 */
function ifs_parking_login_logo_url_title() {
	return get_option( 'ifs_parking_site_title', __( 'আইএফএস পার্কিং সিস্টেম', 'ifs-parking' ) );
}
add_filter( 'login_headertext', 'ifs_parking_login_logo_url_title' );

/**
 * লগইন পেজে মোবাইল ভিউপোর্ট মেটা ট্যাগ যুক্ত করা।
 *
 * @return void
 */
function ifs_parking_login_viewport_meta() {
	echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />' . "\n";
}
add_action( 'login_head', 'ifs_parking_login_viewport_meta' );

/**
 * সুপার মোবাইল ফ্রেন্ডলি ও রেসপন্সিভ লগইন ডিজাইন (Light Theme + Bangla Font)।
 *
 * @return void
 */
function ifs_parking_clean_default_login_styles() {
	$site_title = get_option( 'ifs_parking_site_title', __( 'আইএফএস পার্কিং সিস্টেম', 'ifs-parking' ) );
	$site_logo  = get_option( 'ifs_parking_site_logo', '' );

	wp_enqueue_style(
		'ifs-parking-login-fonts',
		'https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap',
		array(),
		IFS_PARKING_VERSION
	);
	?>
	<style type="text/css">
		/* বডি ও ব্যাকগ্রাউন্ড অপ্টিমাইজেশন */
		body.login {
			background: #f1f5f9 !important;
			font-family: 'Hind Siliguri', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
			color: #0f172a !important;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			min-height: 100vh;
			margin: 0;
			padding: 16px !important;
			box-sizing: border-box;
			overflow-x: hidden;
		}

		#login {
			width: 100% !important;
			max-width: 380px !important;
			padding: 0 !important;
			margin: auto !important;
			box-sizing: border-box;
		}

		/* লোগো ও হেডার টাইটেল মোবাইল ফ্রেন্ডলি সাইজিং */
		#login h1 {
			padding: 0 !important;
			margin: 0 0 10px 0 !important;
		}

		#login h1 a {
			<?php if ( ! empty( $site_logo ) ) : ?>
				background-image: url('<?php echo esc_url( $site_logo ); ?>') !important;
				background-size: contain !important;
				background-repeat: no-repeat !important;
				background-position: center !important;
				width: 100% !important;
				height: 60px !important;
			<?php else : ?>
				background: none !important;
				text-indent: 0 !important;
				width: 100% !important;
				height: auto !important;
				line-height: normal !important;
				text-align: center;
			<?php endif; ?>
		}

		<?php if ( empty( $site_logo ) ) : ?>
		#login h1 a::after {
			content: '<?php echo esc_js( $site_title ); ?>';
			display: block;
			font-size: 22px;
			font-weight: 700;
			color: #0f172a;
			text-align: center;
			padding-bottom: 5px;
			word-break: break-word;
		}
		<?php endif; ?>

		/* ফর্ম কার্ড ডিজাইন */
		body.login form {
			background: #ffffff !important;
			border: 1px solid #e2e8f0 !important;
			border-radius: 14px !important;
			padding: 24px 20px !important;
			box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.06) !important;
			margin-top: 10px !important;
			margin-left: 0 !important;
			margin-right: 0 !important;
			width: 100% !important;
			box-sizing: border-box !important;
		}

		body.login label {
			font-size: 14px !important;
			font-weight: 600 !important;
			color: #334155 !important;
			margin-bottom: 6px !important;
			display: block !important;
		}

		/* ইনপুট ফিল্ড (iOS জুম রোধ করতে ফন্ট সাইজ কমপক্ষে 16px রাখা হয়েছে) */
		body.login input[type="text"],
		body.login input[type="password"] {
			width: 100% !important;
			height: 48px !important;
			background: #f8fafc !important;
			border: 1.5px solid #cbd5e1 !important;
			border-radius: 8px !important;
			padding: 0 14px !important;
			font-size: 16px !important;
			color: #0f172a !important;
			box-shadow: none !important;
			margin-bottom: 16px !important;
			box-sizing: border-box !important;
			transition: all 0.2s ease !important;
			-webkit-appearance: none;
		}

		body.login input[type="text"]:focus,
		body.login input[type="password"]:focus {
			background: #ffffff !important;
			border-color: #0284c7 !important;
			box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12) !important;
			outline: none !important;
		}

		/* রিমেম্বার মি চেকবক্স এবং লেআউট */
		body.login .forgetmenot {
			margin-bottom: 16px !important;
			float: none !important;
			display: flex;
			align-items: center;
		}

		body.login .forgetmenot input[type="checkbox"] {
			width: 18px;
			height: 18px;
			margin-right: 8px;
		}

		body.login .forgetmenot label {
			font-size: 13px !important;
			color: #64748b !important;
			font-weight: 500 !important;
			margin-bottom: 0 !important;
		}

		/* লগইন বাটন */
		body.login input[type="submit"] {
			background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
			border: none !important;
			border-radius: 8px !important;
			height: 48px !important;
			font-family: 'Hind Siliguri', 'Plus Jakarta Sans', sans-serif !important;
			font-size: 16px !important;
			font-weight: 600 !important;
			color: #ffffff !important;
			box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25) !important;
			cursor: pointer !important;
			width: 100% !important;
			transition: all 0.2s ease !important;
			text-shadow: none !important;
			float: none !important;
			display: block;
		}

		body.login input[type="submit"]:active {
			transform: scale(0.98) !important;
		}

		/* এরর ও নোটিস কন্টেইনার */
		body.login #login_error,
		body.login .message,
		body.login .notice {
			border-radius: 8px !important;
			border-left: 4px solid #ef4444 !important;
			background: #ffffff !important;
			color: #dc2626 !important;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04) !important;
			font-size: 13px !important;
			margin-bottom: 16px !important;
			margin-left: 0 !important;
			margin-right: 0 !important;
			padding: 12px !important;
			box-sizing: border-box;
		}

		body.login .message {
			border-left-color: #0284c7 !important;
			color: #0369a1 !important;
		}

		/* নেভিগেশন ও ব্যাক টু সাইট লিংক */
		#nav,
		#backtoblog {
			text-align: center !important;
			padding: 0 !important;
			margin-top: 16px !important;
		}

		#nav a,
		#backtoblog a {
			font-size: 13px !important;
			color: #64748b !important;
			text-decoration: none !important;
			padding: 4px;
		}

		#nav a:hover,
		#backtoblog a:hover {
			color: #0284c7 !important;
		}

		/* অতিরিক্ত ছোট পর্দার জন্য */
		@media screen and (max-width: 480px) {
			body.login {
				padding: 12px !important;
			}
			body.login form {
				padding: 20px 16px !important;
				border-radius: 12px !important;
			}
		}
	</style>
	<?php
}
add_action( 'login_enqueue_scripts', 'ifs_parking_clean_default_login_styles' );