<?php
/**
 * Database Table Setup & Default Seed Schema.
 *
 * @package IFS_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * প্লাগইন অ্যাক্টিভেশন হুক কলব্যাক: ডেটাবেজ টেবিল তৈরি ও আপডেট।
 *
 * @since 1.0.0
 * @return void
 */
function ifs_parking_activate() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	// ১. পার্কিং লগস টেবিল (Parking Logs Table)।
	$table_logs = $wpdb->prefix . 'ifs_parking_logs';
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
		PRIMARY KEY  (id),
		KEY status (status),
		KEY entry_time (entry_time),
		KEY vehicle_number (vehicle_number)
	) {$charset_collate};";

	// ২. ক্যাটাগরি ও ট্যারিফ টেবিল (Categories & Tariffs Table)।
	$table_cats = $wpdb->prefix . 'ifs_parking_categories';
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
		PRIMARY KEY  (id),
		KEY department (department),
		KEY vehicle_type (vehicle_type)
	) {$charset_collate};";

	// ৩. পার্কিং স্লটস টেবিল (Parking Slots Table)।
	$table_slots = $wpdb->prefix . 'ifs_parking_slots';
	$sql_slots   = "CREATE TABLE {$table_slots} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		slot_name varchar(50) NOT NULL,
		slot_zone varchar(50) DEFAULT 'Main Zone',
		vehicle_type varchar(50) DEFAULT 'Car',
		is_occupied tinyint(1) DEFAULT 0,
		PRIMARY KEY  (id),
		KEY vehicle_type (vehicle_type),
		KEY is_occupied (is_occupied)
	) {$charset_collate};";

	// ৪. স্টাফ ও অপারেটর টেবিল (Staff & Operators Table)।
	$table_staff = $wpdb->prefix . 'ifs_parking_staff';
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

	// ৫. মেম্বারশিপ ও সাবস্ক্রিপশন টেবিল (Memberships & Subscriptions Table)।
	$table_members = $wpdb->prefix . 'ifs_parking_memberships';
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
		PRIMARY KEY  (id),
		KEY expiry_date (expiry_date),
		KEY status (status)
	) {$charset_collate};";

	// নিরাপদভাবে টেবিল তৈরি করতে dbDelta রান করা।
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql_logs );
	dbDelta( $sql_cats );
	dbDelta( $sql_slots );
	dbDelta( $sql_staff );
	dbDelta( $sql_members );

	// ডিফল্ট অপশন সেটিংস সংরক্ষণ।
	if ( false === get_option( 'ifs_parking_currency' ) ) {
		add_option( 'ifs_parking_currency', 'BDT (৳)' );
	}
	if ( false === get_option( 'ifs_parking_prefix' ) ) {
		add_option( 'ifs_parking_prefix', 'IFS-' );
	}
	if ( false === get_option( 'ifs_parking_site_title' ) ) {
		add_option( 'ifs_parking_site_title', __( 'আইএফএস পার্কিং সিস্টেম', 'ifs-parking' ) );
	}
	if ( false === get_option( 'ifs_parking_contact_phone' ) ) {
		add_option( 'ifs_parking_contact_phone', '+৮৮০ ১৭০০-০০০০০০' );
	}

	// ক্যাটাগরি টেবিল সম্পূর্ণ ফাঁকা থাকলে প্রাথমিক ডিফল্ট রেট (প্রতি ২ ঘণ্টা স্ল্যাব) যুক্ত করা।
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$existing_cats = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table_cats}`" );
	if ( 0 === $existing_cats ) {
		$default_categories = array(
			array(
				'department'    => 'General',
				'category_slug' => 'car-general',
				'category_name' => 'Car - General',
				'vehicle_type'  => 'Car',
				'customer_type' => 'General',
				'hourly_rate'   => 30.00,
				'daily_rate'    => 300.00,
				'monthly_rate'  => 3000.00,
				'free_hours'    => 0,
				'is_free'       => 0,
			),
			array(
				'department'    => 'General',
				'category_slug' => 'noah-general',
				'category_name' => 'Noah - General',
				'vehicle_type'  => 'Noah',
				'customer_type' => 'General',
				'hourly_rate'   => 40.00,
				'daily_rate'    => 400.00,
				'monthly_rate'  => 4000.00,
				'free_hours'    => 0,
				'is_free'       => 0,
			),
			array(
				'department'    => 'General',
				'category_slug' => 'bike-general',
				'category_name' => 'Bike - General',
				'vehicle_type'  => 'Bike',
				'customer_type' => 'General',
				'hourly_rate'   => 20.00,
				'daily_rate'    => 100.00,
				'monthly_rate'  => 1000.00,
				'free_hours'    => 0,
				'is_free'       => 0,
			),
			array(
				'department'    => 'General',
				'category_slug' => 'cng-general',
				'category_name' => 'CNG - General',
				'vehicle_type'  => 'CNG',
				'customer_type' => 'General',
				'hourly_rate'   => 25.00,
				'daily_rate'    => 150.00,
				'monthly_rate'  => 1500.00,
				'free_hours'    => 0,
				'is_free'       => 0,
			),
			array(
				'department'    => 'General',
				'category_slug' => 'truck-general',
				'category_name' => 'Truck - General',
				'vehicle_type'  => 'Truck',
				'customer_type' => 'General',
				'hourly_rate'   => 60.00,
				'daily_rate'    => 600.00,
				'monthly_rate'  => 6000.00,
				'free_hours'    => 0,
				'is_free'       => 0,
			),
		);

		foreach ( $default_categories as $cat_row ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$table_cats,
				$cat_row,
				array( '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%d', '%d' )
			);
		}
	}

	update_option( 'ifs_parking_db_version', defined( 'IFS_PARKING_VERSION' ) ? IFS_PARKING_VERSION : '1.1.0' );
}

/**
 * প্লাগইন ডিঅ্যাক্টিভেশন হুক কলব্যাক।
 *
 * @since 1.0.0
 * @return void
 */
function ifs_parking_deactivate() {
	// ডিঅ্যাক্টিভেশনের সময় ইউজারের সংরক্ষিত ডেটাবেজ ডেটা নিরাপদ রাখা হয়েছে।
	delete_transient( 'ifs_parking_cache' );
}