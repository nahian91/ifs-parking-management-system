<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function ifs_parking_delete_category( $table_cats, $cat_id ) {
    global $wpdb;
    $wpdb->delete( $table_cats, array( 'id' => $cat_id ) );
    echo '<div class="notice notice-success is-dismissible" style="border-radius:10px; border-left: 4px solid #10b981; box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 20px;"><p>✨ ক্যাটাগরি সফলভাবে ডিলিট করা হয়েছে!</p></div>';
}