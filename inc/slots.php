<?php
/**
 * Slots Management Module.
 *
 * @package Ifs_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * স্লট পেজ রেন্ডারার
 */
function ifs_parking_render_slots_page() {
	ifs_parking_render_layout_wrapper( 'ifs_parking_render_slots_content' );
}

/**
 * ক্যাটাগরি থেকে ইউনিক গাড়ির নাম ফেচ করার ফাংশন
 *
 * @return array ইউনিক গাড়ির তালিকা (যেমন: ['Bike', 'Car', 'Truck'])
 */
function ifs_parking_get_dynamic_vehicle_types() {
	global $wpdb;
	$table_cats = $wpdb->prefix . 'ifs_parking_categories';

	$types = $wpdb->get_col( "SELECT DISTINCT vehicle_type FROM $table_cats WHERE vehicle_type IS NOT NULL AND vehicle_type != '' ORDER BY vehicle_type ASC" );

	if ( empty( $types ) ) {
		return array( 'Car', 'Bike', 'Truck' ); // ক্যাটাগরি কনফিগার না থাকলে নিরাপদ ডিফল্ট
	}

	return $types;
}

/**
 * গাড়ির নামের ওপর ভিত্তি করে ডায়নামিক SVG আইকন রিটার্ন
 *
 * @param string $type গাড়ির নাম।
 * @return string SVG মার্কআপ
 */
function ifs_parking_get_vehicle_icon_svg( $type ) {
	$type_lower = strtolower( trim( $type ) );

	if ( false !== strpos( $type_lower, 'bike' ) || false !== strpos( $type_lower, 'motorcycle' ) ) {
		return '<svg viewBox="0 0 24 24"><path d="M5 20.5c-2.48 0-4.5-2.02-4.5-4.5S2.52 11.5 5 11.5c2.09 0 3.84 1.43 4.34 3.38l2.79-1.86-1.5-3.02H9V8h3.33l1.5 3.02 2.76-1.84c-.37-.62-.59-1.34-.59-2.18 0-2.21 1.79-4 4-4s4 1.79 4 4-1.79 4-4 4c-.75 0-1.45-.21-2.05-.57l-2.47 1.65 1.83 3.66c.22-.04.45-.06.69-.06 2.48 0 4.5 2.02 4.5 4.5s-2.02 4.5-4.5 4.5-4.5-2.02-4.5-4.5c0-.98.32-1.89.86-2.63L11.7 18.5H8.77c-.45 1.18-1.57 2-2.87 2l-.9-.01zM5 13.5c-1.38 0-2.5 1.12-2.5 2.5s1.12 2.5 2.5 2.5 2.5-1.12 2.5-2.5-1.12-2.5-2.5-2.5zm14 0c-1.38 0-2.5 1.12-2.5 2.5s1.12 2.5 2.5 2.5 2.5-1.12 2.5-2.5-1.12-2.5-2.5-2.5z"/></svg>';
	} elseif ( false !== strpos( $type_lower, 'truck' ) ) {
		return '<svg viewBox="0 0 24 24"><path d="M18 1c-2.76 0-5 2.24-5 5v1H4c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h1c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h1c1.1 0 2-.9 2-2v-5.5L20.5 7H18V6c0-1.66 1.34-3 3-3s3 1.34 3 3v1h-2v2h3c.55 0 1-.45 1-1V6c0-2.76-2.24-5-5-5h-3zM8 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM17 12V9h3l2 3h-5z"/></svg>';
	} elseif ( false !== strpos( $type_lower, 'cycle' ) ) {
		return '<svg viewBox="0 0 24 24"><path d="M15.5 5.5c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zM5 12c-2.8 0-5 2.2-5 5s2.2 5 5 5 5-2.2 5-5-2.2-5-5-5zm0 8.5c-1.9 0-3.5-1.6-3.5-3.5s1.6-3.5 3.5-3.5 3.5 1.6 3.5 3.5-1.6 3.5-3.5 3.5zm7.8-6.5l-1.9-3.2c-.4-.6-1-1-1.7-1H6.4l.6 2h2.2l1.2 2-3.1 5.3 1.7 1 3.2-5.4 2.8 2.8v3.5h2v-4.3l-3.2-3.2.8-2.5 1.4 1.4c.4.4.9.6 1.4.6h3v-2h-2.6l-2-2c-.3-.3-.7-.5-1.2-.5-.3 0-.6.1-.8.2zm6.2-2c-2.8 0-5 2.2-5 5s2.2 5 5 5 5-2.2 5-5-2.2-5-5-5zm0 8.5c-1.9 0-3.5-1.6-3.5-3.5s1.6-3.5 3.5-3.5 3.5 1.6 3.5 3.5-1.6 3.5-3.5 3.5z"/></svg>';
	} elseif ( false !== strpos( $type_lower, 'cng' ) ) {
		return '<svg viewBox="0 0 24 24"><path d="M20 15V8c0-1.1-.9-2-2-2h-3l-2-3H9L7 6H4c-1.1 0-2 .9-2 2v7c0 .55.45 1 1 1h1c0 1.66 1.34 3 3 3s3-1.34 3-3h4c0 1.66 1.34 3 3 3s3-1.34 3-3h1c.55 0 1-.45 1-1zM7 16.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm10 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM4 13V8h14v5H4z"/></svg>';
	} elseif ( false !== strpos( $type_lower, 'noah' ) || false !== strpos( $type_lower, 'hiace' ) || false !== strpos( $type_lower, 'micro' ) || false !== strpos( $type_lower, 'van' ) ) {
		return '<svg viewBox="0 0 24 24"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm12 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm1.5-7.5H16V9h2.2l1.3 2z"/></svg>';
	}

	// ডিফল্ট কার আইকন
	return '<svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.85 7h10.29l1.04 3H5.81l1.04-3zM19 17H5v-4.66l.12-.34h13.76l.12.34V17z"/><circle cx="7.5" cy="14.5" r="1.5"/><circle cx="16.5" cy="14.5" r="1.5"/></svg>';
}

/**
 * মূল স্লট কনটেন্ট রেন্ডারার
 */
function ifs_parking_render_slots_content() {
	global $wpdb;
	$table_slots = $wpdb->prefix . 'ifs_parking_slots';

	// স্লট সংখ্যা আপডেট হ্যান্ডলার
	if ( isset( $_POST['ifs_update_slots_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ifs_update_slots_nonce'] ) ), 'ifs_update_slots_action' ) ) {
		$target_vehicle = isset( $_POST['target_vehicle'] ) ? sanitize_text_field( wp_unslash( $_POST['target_vehicle'] ) ) : '';
		$new_total      = isset( $_POST['total_slots'] ) ? intval( $_POST['total_slots'] ) : 0;

		if ( ! empty( $target_vehicle ) && $new_total >= 0 ) {
			$current_slots = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_slots WHERE vehicle_type = %s ORDER BY id ASC", $target_vehicle ) );
			$current_count = count( $current_slots );

			if ( $new_total > $current_count ) {
				// নতুন স্লট সংখ্যা বাড়ানো
				$to_add = $new_total - $current_count;
				for ( $i = 1; $i <= $to_add; $i++ ) {
					$slot_num = $current_count + $i;
					$wpdb->insert(
						$table_slots,
						array(
							'slot_name'    => strtoupper( $target_vehicle ) . '-' . str_pad( (string) $slot_num, 2, '0', STR_PAD_LEFT ),
							'slot_zone'    => 'Main Zone',
							'vehicle_type' => $target_vehicle,
							'is_occupied'  => 0,
						),
						array( '%s', '%s', '%s', '%d' )
					);
				}
				echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
						<p class="ifs-notice-text">
							<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
							' . esc_html( sprintf( __( '%s এর স্লট সংখ্যা বৃদ্ধি করে মোট %d টি করা হয়েছে!', 'ifs-parking' ), $target_vehicle, $new_total ) ) . '
						</p>
					  </div>';
			} elseif ( $new_total < $current_count ) {
				// স্লট কমানো (শুধুমাত্র খালি স্লট ডিলিট হবে)
				$to_remove       = $current_count - $new_total;
				$removable_slots = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM $table_slots WHERE vehicle_type = %s AND is_occupied = 0 ORDER BY id DESC LIMIT %d", $target_vehicle, $to_remove ) );

				foreach ( $removable_slots as $r_slot ) {
					$wpdb->delete(
						$table_slots,
						array( 'id' => $r_slot->id ),
						array( '%d' )
					);
				}
				echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
						<p class="ifs-notice-text">
							<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
							' . esc_html( sprintf( __( '%s এর স্লট সংখ্যা সফলভাবে আপডেট করা হয়েছে!', 'ifs-parking' ), $target_vehicle ) ) . '
						</p>
					  </div>';
			}
		}
	}

	// ক্যাটাগরি ডাটাবেজ থেকে ইউনিক গাড়ির তালিকা সংগ্রহ
	$unique_vehicles = ifs_parking_get_dynamic_vehicle_types();

	// অপ্টিমাইজড সিঙ্গেল গ্রুপ কুয়েরি
	$slot_stats_raw = $wpdb->get_results(
		"SELECT vehicle_type,
				COUNT(*) as total,
				SUM(CASE WHEN is_occupied = 1 THEN 1 ELSE 0 END) as booked,
				SUM(CASE WHEN is_occupied = 0 THEN 1 ELSE 0 END) as free
		 FROM $table_slots 
		 GROUP BY vehicle_type"
	);

	$stats_map = array();
	if ( ! empty( $slot_stats_raw ) ) {
		foreach ( $slot_stats_raw as $s ) {
			$stats_map[ $s->vehicle_type ] = $s;
		}
	}
	?>
	<div class="wrap ifs-slots-wrapper">
		<!-- স্লিম প্রিমিয়াম হেডার -->
		<div class="ifs-slots-banner">
			<div>
				<h1 class="ifs-banner-title">
					<svg class="ifs-svg-primary-inline" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 10h2v7H7zm4-3h2v10h-2zm4 5h2v5h-2z"/></svg>
					<?php esc_html_e( 'গাড়ি ও স্লট ক্যাপাসিটি ব্যবস্থাপনা', 'ifs-parking' ); ?>
				</h1>
				<p class="ifs-banner-subtitle"><?php esc_html_e( 'ক্যাটাগরি ভিত্তিক ইউনিক গাড়ির ধরণগুলোর জন্য স্লট সংখ্যা নির্ধারণ ও তাৎক্ষণিক আপডেট করুন।', 'ifs-parking' ); ?></p>
			</div>
		</div>

		<!-- সিঙ্গেল টেবিল কন্টেইনার -->
		<div class="ifs-card ifs-slots-card">
			<div class="ifs-table-responsive">
				<table class="wp-list-table widefat fixed striped ifs-custom-slots-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'গাড়ির নাম (Vehicle Type)', 'ifs-parking' ); ?></th>
							<th class="ifs-col-total-input"><?php esc_html_e( 'মোট স্লট সংখ্যা', 'ifs-parking' ); ?></th>
							<th class="ifs-col-status-count"><?php esc_html_e( 'বুকড', 'ifs-parking' ); ?></th>
							<th class="ifs-col-status-count"><?php esc_html_e( 'খালি', 'ifs-parking' ); ?></th>
							<th class="ifs-col-action-btn"><?php esc_html_e( 'অ্যাকশন', 'ifs-parking' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php 
						foreach ( $unique_vehicles as $car_name ) :
							$car_name     = trim( $car_name );
							$stats        = isset( $stats_map[ $car_name ] ) ? $stats_map[ $car_name ] : null;
							$total_count  = $stats ? (int) $stats->total : 0;
							$booked_count = $stats ? (int) $stats->booked : 0;
							$free_count   = $stats ? (int) $stats->free : 0;
							$form_id      = 'ifs-form-slot-' . esc_attr( sanitize_title( $car_name ) );
							$vehicle_svg  = ifs_parking_get_vehicle_icon_svg( $car_name );
						?>
							<tr>
								<td>
									<div class="ifs-veh-cell">
										<div class="ifs-veh-icon-pill">
											<?php echo $vehicle_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</div>
										<strong class="ifs-badge-veh">
											<?php echo esc_html( $car_name ); ?>
										</strong>
									</div>
								</td>

								<!-- ফর্ম ইনপুট ফিল্ড (ভ্যালিড HTML5 অ্যাট্রিবিউট ব্যবহার) -->
								<td>
									<form id="<?php echo esc_attr( $form_id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-slots' ) ); ?>">
										<?php wp_nonce_field( 'ifs_update_slots_action', 'ifs_update_slots_nonce' ); ?>
										<input type="hidden" name="target_vehicle" value="<?php echo esc_attr( $car_name ); ?>">
									</form>
									<input type="number" form="<?php echo esc_attr( $form_id ); ?>" name="total_slots" min="0" max="1000" value="<?php echo esc_attr( $total_count ); ?>" required class="ifs-input-slot-num">
								</td>

								<td>
									<span class="ifs-badge-status-booked">
										<svg class="ifs-svg-xxs" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg>
										<?php echo esc_html( $booked_count ); ?>
									</span>
								</td>

								<td>
									<span class="ifs-badge-status-free">
										<svg class="ifs-svg-xxs" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg>
										<?php echo esc_html( $free_count ); ?>
									</span>
								</td>

								<td class="ifs-td-action-btn">
									<button type="submit" form="<?php echo esc_attr( $form_id ); ?>" class="ifs-btn-update-slot">
										<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
										<?php esc_html_e( 'আপডেট', 'ifs-parking' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php
}