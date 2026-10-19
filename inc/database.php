<?php
/**
 * Database Table Setup (Schema Only).
 *
 * @package DevnahianPMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin activation hook callback to create and update database tables.
 *
 * @since 1.0.0
 * @return void
 */
function devnahian_pms_activate() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	// 1. Parking Logs Table.
	$table_logs = $wpdb->prefix . 'devnahian_pms_logs';
	$sql_logs   = "CREATE TABLE {$table_logs} (
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
	) {$charset_collate};";

	// 2. Categories & Tariffs Table.
	$table_cats = $wpdb->prefix . 'devnahian_pms_categories';
	$sql_cats   = "CREATE TABLE {$table_cats} (
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
	) {$charset_collate};";

	// 3. Parking Slots Table.
	$table_slots = $wpdb->prefix . 'devnahian_pms_slots';
	$sql_slots   = "CREATE TABLE {$table_slots} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		slot_name varchar(50) NOT NULL,
		slot_zone varchar(50) DEFAULT 'Main Zone',
		vehicle_type varchar(50) DEFAULT 'Car',
		is_occupied tinyint(1) DEFAULT 0,
		PRIMARY KEY  (id)
	) {$charset_collate};";

	// 4. Staff & Operators Table.
	$table_staff = $wpdb->prefix . 'devnahian_pms_staff';
	$sql_staff   = "CREATE TABLE {$table_staff} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		staff_name varchar(150) NOT NULL,
		email varchar(100) NOT NULL,
		phone varchar(50) NOT NULL,
		role varchar(50) DEFAULT 'Operator',
		salary decimal(10,2) DEFAULT 0.00,
		duty_slot varchar(100) DEFAULT '',
		profile_img varchar(255) DEFAULT '',
		PRIMARY KEY  (id)
	) {$charset_collate};";

	// 5. Memberships & Subscriptions Table.
	$table_members = $wpdb->prefix . 'devnahian_pms_memberships';
	$sql_members   = "CREATE TABLE {$table_members} (
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
	) {$charset_collate};";

	// Execute dbDelta to create tables safely.
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql_logs );
	dbDelta( $sql_cats );
	dbDelta( $sql_slots );
	dbDelta( $sql_staff );
	dbDelta( $sql_members );

	// Default option settings.
	if ( ! get_option( 'devnahian_pms_currency' ) ) {
		add_option( 'devnahian_pms_currency', 'BDT (৳)' );
	}
	if ( ! get_option( 'devnahian_pms_prefix' ) ) {
		add_option( 'devnahian_pms_prefix', 'NPMS-' );
	}
	if ( ! get_option( 'devnahian_pms_site_title' ) ) {
		add_option( 'devnahian_pms_site_title', 'Devnahian PMS' );
	}
}