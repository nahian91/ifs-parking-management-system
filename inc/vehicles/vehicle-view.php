<?php
/**
 * Vehicle Details View Template.
 *
 * @package Ifs_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * একক গাড়ির বিস্তারিত বিবরণ রেন্ডারার
 *
 * @param string $table_logs      লগ টেবিল নাম।
 * @param int    $log_id          লগ আইডি।
 * @param string $currency_symbol কারেন্সি প্রতীক।
 */
function ifs_parking_render_vehicle_view( $table_logs, $log_id, $currency_symbol ) {
    global $wpdb;
    
    $log_id = intval( $log_id );
    $vehicle = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_logs WHERE id = %d", $log_id ) );

    if ( ! $vehicle ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'কোনো গাড়ি বা লগ রেকর্ড পাওয়া যায়নি!', 'ifs-parking' ) . '</p></div>';
        return;
    }

    $back_url = admin_url( 'admin.php?page=ifs-parking-vehicles&tab=all_vehicles' );
    $invoice_num = ! empty( $vehicle->reference_id ) ? strtok( $vehicle->reference_id, ' [' ) : 'NPMS-' . str_pad( $vehicle->id, 4, '0', STR_PAD_LEFT );
    ?>
    <div class="ifs-card" style="max-width: 800px; margin: 20px auto; padding: 30px; background: #fff; border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 20px; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                🚗 <?php esc_html_e( 'গাড়ির বিস্তারিত বিবরণ', 'ifs-parking' ); ?> (#<?php echo esc_html( $vehicle->id ); ?>)
            </h3>
            <a href="<?php echo esc_url( $back_url ); ?>" style="background: #e2e8f0; color: #334155; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">
                ← <?php esc_html_e( 'তালিকায় ফিরে যান', 'ifs-parking' ); ?>
            </a>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px; color: #334155;">
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <strong><?php esc_html_e( 'ইনভয়েস নং:', 'ifs-parking' ); ?></strong><br>
                <span style="color: #0284c7; font-weight: 700;"><?php echo esc_html( $invoice_num ); ?></span>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <strong><?php esc_html_e( 'গাড়ি নম্বর:', 'ifs-parking' ); ?></strong><br>
                <span style="font-size: 16px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $vehicle->vehicle_number ); ?></span>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <strong><?php esc_html_e( 'ডিপার্টমেন্ট / রেফারেন্স:', 'ifs-parking' ); ?></strong><br>
                <?php echo esc_html( ! empty( $vehicle->reference_id ) ? $vehicle->reference_id : 'General' ); ?>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <strong><?php esc_html_e( 'ক্যাটাগরি / ধরণ:', 'ifs-parking' ); ?></strong><br>
                <?php echo esc_html( ucfirst( $vehicle->parking_type ) ); ?>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <strong><?php esc_html_e( 'নির্ধারিত ফি:', 'ifs-parking' ); ?></strong><br>
                <span style="color: #10b981; font-weight: 700;"><?php echo esc_html( $currency_symbol . number_format( (float) $vehicle->fee, 2 ) ); ?></span>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <strong><?php esc_html_e( 'স্ট্যাটাস:', 'ifs-parking' ); ?></strong><br>
                <?php if ( 'Parked' === $vehicle->status ) : ?>
                    <span style="color: #d97706; font-weight: 600;"><?php esc_html_e( 'পার্ক করা আছে', 'ifs-parking' ); ?></span>
                <?php else : ?>
                    <span style="color: #059669; font-weight: 600;"><?php esc_html_e( 'বের হয়ে গেছে (Completed)', 'ifs-parking' ); ?></span>
                <?php endif; ?>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <strong><?php esc_html_e( 'চালকের নাম:', 'ifs-parking' ); ?></strong><br>
                <?php echo esc_html( ! empty( $vehicle->driver_name ) ? $vehicle->driver_name : 'তথ্য নেই' ); ?>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <strong><?php esc_html_e( 'চালকের ফোন:', 'ifs-parking' ); ?></strong><br>
                <?php echo esc_html( ! empty( $vehicle->driver_phone ) ? $vehicle->driver_phone : 'তথ্য নেই' ); ?>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; grid-column: span 2;">
                <strong><?php esc_html_e( 'প্রবেশের সময়:', 'ifs-parking' ); ?></strong><br>
                <?php echo esc_html( $vehicle->entry_time ); ?>
            </div>
        </div>
    </div>
    <?php
}