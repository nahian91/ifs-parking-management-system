<?php
/**
 * Plugin Name:       IFS Parking Management System (Admin Panel Only)
 * Plugin URI:        https://example.com/ifs-parking
 * Description:       আব্দুল্লাহ নাইয়ান-এর জন্য তৈরি এক্সক্লুসিভ এডমিন প্যানেল এবং সেপারেট ডাটাবেজ সংবলিত পার্কিং সিস্টেম।
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Abdullah Nahian
 * Author URI:        https://example.com
 * Text Domain:       ifs-parking
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// প্লাগইন কনস্ট্যান্ট
define( 'IFS_PARKING_VERSION', '1.0.0' );
define( 'IFS_PARKING_PATH', plugin_dir_path( __FILE__ ) );
define( 'IFS_PARKING_URL', plugin_dir_url( __FILE__ ) );

// মডিউল ডিরেক্টরি পাথ
$ifs_inc = IFS_PARKING_PATH . 'inc/';

// কোর ও ডেটাবেজ মডিউল
require_once $ifs_inc . 'database.php';
require_once $ifs_inc . 'admin-menu.php';

// ফিচার ও পেজ মডিউলসমূহ
require_once $ifs_inc . 'dashboard.php';
require_once $ifs_inc . 'live-status.php';
require_once $ifs_inc . 'vehicles.php';
require_once $ifs_inc . 'slots.php';
require_once $ifs_inc . 'categories.php';
require_once $ifs_inc . 'membership.php';
require_once $ifs_inc . 'reports.php';
require_once $ifs_inc . 'staff.php';
require_once $ifs_inc . 'settings.php';

/**
 * প্লাগইন অ্যাক্টিভেশন ও ডিঅ্যাক্টিভেশন
 */
function ifs_parking_plugin_activation() {
    if ( function_exists( 'ifs_parking_activate' ) ) {
        ifs_parking_activate();
    }
}
register_activation_hook( __FILE__, 'ifs_parking_plugin_activation' );

function ifs_parking_plugin_deactivation() {
    if ( function_exists( 'ifs_parking_deactivate' ) ) {
        ifs_parking_deactivate();
    }
}
register_deactivation_hook( __FILE__, 'ifs_parking_plugin_deactivation' );

/**
 * এডমিন স্ক্রিপ্ট ও স্টাইল এনকিউ
 */
function ifs_parking_admin_enqueue_assets( $hook ) {
    if ( false === strpos( $hook, 'ifs-parking' ) ) {
        return;
    }

    wp_enqueue_style(
        'ifs-parking-admin-css',
        IFS_PARKING_URL . 'assets/css/admin.css',
        array(),
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
    $table_cats        = $wpdb->prefix . 'ifs_parking_categories';
    $all_categories    = $wpdb->get_results( "SELECT * FROM {$table_cats} ORDER BY id ASC" );
    $currency_setting  = get_option( 'ifs_parking_currency', 'BDT (৳)' );
    $currency_symbol   = false !== strpos( $currency_setting, '৳' ) ? '৳' : ( false !== strpos( $currency_setting, '€' ) ? '€' : ( false !== strpos( $currency_setting, '£' ) ? '£' : '$' ) );

    wp_localize_script(
        'ifs-parking-admin-js',
        'ifsParkingData',
        array(
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'ifs_parking_admin_nonce' ),
            'currency'   => $currency_symbol,
            'categories' => ! empty( $all_categories ) ? $all_categories : array(),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'ifs_parking_admin_enqueue_assets' );

/**
 * ফ্রন্টএন্ড ব্লক: সাধারণ ভিজিটর ফ্রন্ট পেজে ঢুকলে সরাসরি লগইন স্ক্রিনে রিডাইরেক্ট হবে
 */
function ifs_parking_disable_frontend_redirect() {
    if ( ! is_user_logged_in() && ! is_admin() && 'wp-login.php' !== $GLOBALS['pagenow'] ) {
        wp_safe_redirect( wp_login_url() );
        exit;
    } elseif ( is_user_logged_in() && ! is_admin() ) {
        wp_safe_redirect( admin_url( 'admin.php?page=ifs-parking-system' ) );
        exit;
    }
}
add_action( 'template_redirect', 'ifs_parking_disable_frontend_redirect' );

/**
 * লগইনের পর সরাসরি প্লাগইন ড্যাশবোর্ডে রিডাইরেক্ট
 */
function ifs_parking_login_redirect( $redirect_to, $request, $user ) {
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
        return admin_url( 'admin.php?page=ifs-parking-system' );
    }
    return $redirect_to;
}
add_filter( 'login_redirect', 'ifs_parking_login_redirect', 10, 3 );

/**
 * ওয়ার্ডপ্রেসের ডিফল্ট লোগো লিংক ও টেক্সট পরিবর্তন
 */
function ifs_parking_login_logo_url() {
    return admin_url( 'admin.php?page=ifs-parking-system' );
}
add_filter( 'login_headerurl', 'ifs_parking_login_logo_url' );

function ifs_parking_login_logo_url_title() {
    return get_option( 'ifs_parking_site_title', 'IFS Parking System' );
}
add_filter( 'login_headertext', 'ifs_parking_login_logo_url_title' );

/**
 * প্রফেশনাল ও ক্লিন ডিফল্ট ওয়ার্ডপ্রেস লগইন ডিজাইন (Light Theme) - Settings থেকে লোগো ও নাম ফেচ করা
 */
function ifs_parking_clean_default_login_styles() {
    $site_title = get_option( 'ifs_parking_site_title', 'IFS Parking System' );
    $site_logo  = get_option( 'ifs_parking_site_logo', '' );
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style type="text/css">
        /* বডি ও ব্যাকগ্রাউন্ড */
        body.login {
            background: #f1f5f9 !important;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
            color: #0f172a !important;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        #login {
            width: 100% !important;
            max-width: 400px !important;
            padding: 0 !important;
            margin: auto !important;
        }

        /* লোগো ও হেডার টাইটেল */
        #login h1 a {
            <?php if ( ! empty( $site_logo ) ) : ?>
                background-image: url('<?php echo esc_url( $site_logo ); ?>') !important;
                background-size: contain !important;
                background-repeat: no-repeat !important;
                background-position: center !important;
                width: 100% !important;
                height: 70px !important;
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
            font-weight: 800;
            color: #0f172a;
            text-align: center;
            padding-bottom: 10px;
        }
        <?php endif; ?>

        /* ফর্ম কার্ড ডিজাইন */
        body.login form {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px !important;
            padding: 32px 28px !important;
            box-shadow: 0 15px 35px -5px rgba(15, 23, 42, 0.08) !important;
            margin-top: 16px !important;
        }

        body.login label {
            font-size: 13px !important;
            font-weight: 600 !important;
            color: #334155 !important;
            margin-bottom: 6px !important;
            display: block !important;
        }

        /* ইনপুট ফিল্ড */
        body.login input[type="text"],
        body.login input[type="password"] {
            width: 100% !important;
            height: 44px !important;
            background: #f8fafc !important;
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 8px !important;
            padding: 0 14px !important;
            font-size: 14px !important;
            color: #0f172a !important;
            box-shadow: none !important;
            margin-bottom: 16px !important;
            box-sizing: border-box !important;
            transition: all 0.2s ease !important;
        }

        body.login input[type="text"]:focus,
        body.login input[type="password"]:focus {
            background: #ffffff !important;
            border-color: #0284c7 !important;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12) !important;
            outline: none !important;
        }

        /* রিমেম্বার মি */
        body.login .forgetmenot {
            margin-bottom: 16px !important;
        }

        body.login .forgetmenot label {
            font-size: 12.5px !important;
            color: #64748b !important;
            font-weight: 500 !important;
        }

        /* লগইন বাটন */
        body.login input[type="submit"] {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
            border: none !important;
            border-radius: 8px !important;
            height: 44px !important;
            font-size: 14px !important;
            font-weight: 700 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25) !important;
            cursor: pointer !important;
            width: 100% !important;
            transition: all 0.2s ease !important;
            text-shadow: none !important;
        }

        body.login input[type="submit"]:hover {
            opacity: 0.95 !important;
            transform: translateY(-1px) !important;
        }

        /* এরর ও নোটিস */
        body.login #login_error,
        body.login .message,
        body.login .notice {
            border-radius: 8px !important;
            border-left: 4px solid #ef4444 !important;
            background: #ffffff !important;
            color: #dc2626 !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04) !important;
            font-size: 12.5px !important;
            margin-bottom: 16px !important;
        }

        body.login .message {
            border-left-color: #0284c7 !important;
            color: #0369a1 !important;
        }

        /* নেভিগেশন ও ব্যাক টু সাইট লিংক */
        #nav, #backtoblog {
            text-align: center !important;
            padding: 0 !important;
            margin-top: 16px !important;
        }

        #nav a, #backtoblog a {
            font-size: 12px !important;
            color: #64748b !important;
            text-decoration: none !important;
        }

        #nav a:hover, #backtoblog a:hover {
            color: #0284c7 !important;
        }
    </style>
    <?php
}
add_action( 'login_enqueue_scripts', 'ifs_parking_clean_default_login_styles' );