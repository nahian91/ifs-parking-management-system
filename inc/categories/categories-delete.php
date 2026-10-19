<?php
/**
 * Category & Department Delete Handlers.
 *
 * @package Ifs_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * একক ক্যাটাগরি ডিলিট হ্যান্ডলার
 *
 * @param string $table_cats ক্যাটাগরি টেবিল নাম।
 * @param int    $cat_id     ক্যাটাগরি আইডি।
 */
function ifs_parking_delete_category( $table_cats, $cat_id ) {
    global $wpdb;

    $cat_id = intval( $cat_id );

    if ( $cat_id > 0 ) {
        $wpdb->delete(
            $table_cats,
            array( 'id' => $cat_id ),
            array( '%d' )
        );
    }
}

/**
 * সম্পূর্ণ ডিপার্টমেন্ট ও এর অন্তর্ভুক্ত সকল প্যাকেজ ডিলিট হ্যান্ডলার
 *
 * @param string $table_cats ক্যাটাগরি টেবিল নাম।
 * @param string $dept_name  ডিপার্টমেন্ট নাম।
 */
function ifs_parking_delete_department( $table_cats, $dept_name ) {
    global $wpdb;

    $dept_name = sanitize_text_field( $dept_name );

    if ( ! empty( $dept_name ) ) {
        $wpdb->delete(
            $table_cats,
            array( 'department' => $dept_name ),
            array( '%s' )
        );
    }
}