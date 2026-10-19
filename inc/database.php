<?php
/**
 * Database Table Setup (Schema Only).
 *
 * @package Ifs_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * প্লাগইন অ্যাক্টিভেশন ফাংশন (শুধুমাত্র টেবিল তৈরি করবে)
 */
function ifs_parking_activate() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	// ১. পার্কিং লগ টেবিল
	$table_logs = $wpdb->prefix . 'ifs_parking_logs';
	$sql_logs   = "CREATE TABLE $table_logs (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		vehicle_number varchar(100) NOT NULL,
		parking_type varchar(50) NOT NULL,
		slot_number varchar(50) DEFAULT '',
		reference_id varchar(100) DEFAULT '',
		driver_name varchar(150) DEFAULT '',
		driver_phone varchar(50) DEFAULT '',
		nid_path varchar(255) DEFAULT '',
		membership_expiry date DEFAULT NULL,
		entry_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
		exit_time datetime DEFAULT NULL,
		fee decimal(10,2) DEFAULT 0.00,
		status varchar(20) DEFAULT 'Parked',
		PRIMARY KEY  (id)
	) $charset_collate;";

	// ২. ক্যাটাগরি ও রেট টেবিল
	$table_cats = $wpdb->prefix . 'ifs_parking_categories';
	$sql_cats   = "CREATE TABLE $table_cats (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		department varchar(150) DEFAULT 'General',
		category_slug varchar(50) NOT NULL,
		category_name varchar(100) DEFAULT '',
		vehicle_type varchar(50) DEFAULT 'Car',
		customer_type varchar(100) DEFAULT 'General',
		hourly_rate decimal(10,2) DEFAULT 0.00,
		daily_rate decimal(10,2) DEFAULT 0.00,
		monthly_rate decimal(10,2) DEFAULT 0.00,
		free_hours int(11) DEFAULT 0,
		is_free tinyint(1) DEFAULT 0,
		PRIMARY KEY  (id)
	) $charset_collate;";

	// ৩. স্লট টেবিল
	$table_slots = $wpdb->prefix . 'ifs_parking_slots';
	$sql_slots   = "CREATE TABLE $table_slots (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		slot_name varchar(50) NOT NULL,
		slot_zone varchar(50) DEFAULT 'Main Zone',
		vehicle_type varchar(50) DEFAULT 'Car',
		is_occupied tinyint(1) DEFAULT 0,
		PRIMARY KEY  (id)
	) $charset_collate;";

	// ৪. স্টাফ ও অপারেটর টেবিল
	$table_staff = $wpdb->prefix . 'ifs_parking_staff';
	$sql_staff   = "CREATE TABLE $table_staff (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		staff_name varchar(150) NOT NULL,
		email varchar(100) NOT NULL,
		phone varchar(50) NOT NULL,
		role varchar(50) DEFAULT 'Operator',
		salary decimal(10,2) DEFAULT 0.00,
		duty_slot varchar(100) DEFAULT '',
		profile_img varchar(255) DEFAULT '',
		PRIMARY KEY  (id)
	) $charset_collate;";

    // ৫. মেম্বারশিপ ও সাবস্ক্রিপশন টেবিল
	$table_members = $wpdb->prefix . 'ifs_parking_memberships';
	$sql_members   = "CREATE TABLE $table_members (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		member_name varchar(150) NOT NULL,
		member_phone varchar(50) NOT NULL,
		vehicle_number varchar(100) NOT NULL,
		vehicle_type varchar(50) DEFAULT 'Car',
		plan_name varchar(100) NOT NULL,
		start_date date NOT NULL,
		expiry_date date NOT NULL,
		fee_paid decimal(10,2) DEFAULT 0.00,
		status varchar(20) DEFAULT 'Active',
		created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";


	// WordPress dbDelta ফাংশন কল
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql_logs );
	dbDelta( $sql_cats );
	dbDelta( $sql_slots );
	dbDelta( $sql_staff );
	dbDelta( $sql_members );

	// ডিফল্ট বেসিক অপশন সেটিংস
	if ( ! get_option( 'ifs_parking_currency' ) ) {
		add_option( 'ifs_parking_currency', 'BDT (৳)' );
	}
	if ( ! get_option( 'ifs_parking_prefix' ) ) {
		add_option( 'ifs_parking_prefix', 'IFS-' );
	}
	if ( ! get_option( 'ifs_parking_site_title' ) ) {
		add_option( 'ifs_parking_site_title', 'IFS Parking System' );
	}
}