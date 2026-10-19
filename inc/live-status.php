<?php
/**
 * Live Parking Status Module.
 *
 * @package Ifs_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * লাইভ পার্কিং স্ট্যাটাস পেজ রেন্ডারার
 */
function ifs_parking_render_live_status_page() {
	ifs_parking_render_layout_wrapper( 'ifs_parking_render_live_status_content' );
}

/**
 * লাইভ স্ট্যাটাসের মূল কনটেন্ট রেন্ডারার
 */
function ifs_parking_render_live_status_content() {
	global $wpdb;

	$table_logs  = $wpdb->prefix . 'ifs_parking_logs';
	$table_slots = $wpdb->prefix . 'ifs_parking_slots';

	// কারেন্সি সেটিং ফেচ
	$currency_setting = get_option( 'ifs_parking_currency', 'BDT (৳)' );
	$currency_symbol  = '$';
	if ( false !== strpos( $currency_setting, '৳' ) ) {
		$currency_symbol = '৳';
	} elseif ( false !== strpos( $currency_setting, '€' ) ) {
		$currency_symbol = '€';
	} elseif ( false !== strpos( $currency_setting, '£' ) ) {
		$currency_symbol = '£';
	} elseif ( false !== strpos( $currency_setting, '﷼' ) ) {
		$currency_symbol = '﷼';
	} elseif ( false !== strpos( $currency_setting, '$' ) ) {
		$currency_symbol = '$';
	} else {
		$currency_symbol = $currency_setting;
	}

	$site_title   = get_option( 'ifs_parking_site_title', 'IFS PARKING SYSTEM' );
	$system_phone = get_option( 'ifs_parking_contact_phone', '+৮৮০ ১৭০০-০০০০০০' );

	// ফাইনাল রিলিজ সম্পন্ন করার পোস্ট হ্যান্ডলার
	if ( isset( $_POST['ifs_confirm_release_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ifs_confirm_release_nonce'] ) ), 'ifs_confirm_release' ) ) {
		$rel_id         = isset( $_POST['rel_log_id'] ) ? intval( $_POST['rel_log_id'] ) : 0;
		$calculated_fee = isset( $_POST['final_fee'] ) ? floatval( $_POST['final_fee'] ) : 0.00;

		$log_item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_logs WHERE id = %d", $rel_id ) );
		if ( $log_item && 'Parked' === $log_item->status ) {
			$wpdb->update(
				$table_logs,
				array(
					'exit_time' => current_time( 'mysql' ),
					'fee'       => $calculated_fee,
					'status'    => 'Exited',
				),
				array( 'id' => $rel_id ),
				array( '%s', '%f', '%s' ),
				array( '%d' )
			);

			if ( ! empty( $log_item->slot_number ) ) {
				$wpdb->update(
					$table_slots,
					array( 'is_occupied' => 0 ),
					array( 'slot_name' => $log_item->slot_number ),
					array( '%d' ),
					array( '%s' )
				);
			}

			echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
					<p class="ifs-notice-text">
						<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
						' . esc_html( sprintf( __( 'গাড়ি (%s) সফলভাবে রিলিজ ও পেমেন্ট রেকর্ড সম্পন্ন হয়েছে!', 'ifs-parking' ), $log_item->vehicle_number ) ) . '
					</p>
				  </div>';
		}
	}

	// শুধুমাত্র 'Parked' থাকা গাড়িগুলো লোড
	$active_parked = $wpdb->get_results( "SELECT * FROM $table_logs WHERE status = 'Parked' ORDER BY entry_time DESC" );
	$total_parked  = count( $active_parked );
	?>
	<div class="wrap ifs-live-status-wrapper">
		<!-- প্রিমিয়াম লাইভ ব্যানার -->
		<div class="ifs-live-banner">
			<div class="ifs-banner-info">
				<span class="ifs-live-header-badge">
					<span class="ifs-live-dot"></span>
					<?php esc_html_e( 'রিয়েল-টাইম লাইভ মনিটর', 'ifs-parking' ); ?>
				</span>
				<h1 class="ifs-banner-title"><?php esc_html_e( 'লাইভ পার্কিং স্ট্যাটাস', 'ifs-parking' ); ?></h1>
				<p class="ifs-banner-subtitle"><?php esc_html_e( 'গাড়ি পার্কিং সময়কাল প্রতি সেকেন্ডে লাইভ ট্র্যাকিং এবং রিলিজ বাটন ক্লিকে অটো স্লিপ ক্যালকুলেশন।', 'ifs-parking' ); ?></p>
			</div>

			<div class="ifs-banner-actions">
				<div class="ifs-auto-refresh-badge" title="<?php esc_attr_e( 'প্রতি ৬০ সেকেন্ডে স্বয়ংক্রিয়ভাবে ব্যাকগ্রাউন্ড ডেটা রিলোড হবে', 'ifs-parking' ); ?>">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg>
					<?php esc_html_e( 'অটো-রিফ্রেশ:', 'ifs-parking' ); ?> <span id="ifs-refresh-timer">60s</span>
				</div>

				<button type="button" onclick="window.location.reload();" class="ifs-btn-refresh">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
					<?php esc_html_e( 'রিফ্রেশ', 'ifs-parking' ); ?>
				</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-vehicles&tab=add_vehicle' ) ); ?>" class="ifs-btn-new-entry">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
					<?php esc_html_e( 'নতুন এন্ট্রি', 'ifs-parking' ); ?>
				</a>
			</div>
		</div>

		<!-- মূল লাইভ টেবিল কার্ড -->
		<div class="ifs-card ifs-live-table-card">
			<div class="ifs-table-toolbar">
				<div>
					<h3 class="ifs-table-heading">
						<svg class="ifs-svg-primary" viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
						<?php esc_html_e( 'পার্কিংয়ে অবস্থানরত গাড়ি', 'ifs-parking' ); ?>
						<span class="ifs-counter-badge"><?php echo esc_html( $total_parked ); ?> <?php esc_html_e( 'টি', 'ifs-parking' ); ?></span>
					</h3>
				</div>

				<!-- লাইভ ফিল্টার সার্চ বক্স -->
				<div class="ifs-search-container">
					<svg class="ifs-search-icon" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
					<input type="text" id="ifs-search-input" class="ifs-live-search" placeholder="<?php esc_attr_e( 'গাড়ি নম্বর বা চালকের নাম খুঁজুন...', 'ifs-parking' ); ?>">
				</div>
			</div>

			<div class="ifs-table-responsive">
				<table class="wp-list-table widefat fixed striped ifs-custom-live-table" id="ifs-live-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'গাড়ি নম্বর', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'ডিপার্টমেন্ট', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'ক্যাটাগরি', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'চালক ও ফোন', 'ifs-parking' ); ?></th>
							<th><?php esc_html_e( 'প্রবেশের সময়', 'ifs-parking' ); ?></th>
							<th class="ifs-th-duration"><?php esc_html_e( 'পার্কিং সময়কাল (লাইভ)', 'ifs-parking' ); ?></th>
							<th class="ifs-th-action"><?php esc_html_e( 'অ্যাকশন', 'ifs-parking' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $active_parked ) ) : ?>
							<?php foreach ( $active_parked as $item ) : 
								$entry_iso = date( 'c', strtotime( $item->entry_time ) );
								$rate_fee  = floatval( $item->fee );
							?>
								<tr>
									<td>
										<strong class="ifs-veh-num"><?php echo esc_html( $item->vehicle_number ); ?></strong>
									</td>
									<td>
										<span class="ifs-badge-dept-pill">
											<?php echo esc_html( ! empty( $item->reference_id ) ? $item->reference_id : 'General' ); ?>
										</span>
									</td>
									<td class="ifs-td-cat">
										<?php echo esc_html( ucfirst( $item->parking_type ) ); ?>
									</td>
									<td>
										<strong class="ifs-driver-name"><?php echo esc_html( ! empty( $item->driver_name ) ? $item->driver_name : __( 'তথ্য নেই', 'ifs-parking' ) ); ?></strong><br>
										<small class="ifs-driver-phone"><?php echo esc_html( $item->driver_phone ); ?></small>
									</td>
									<td class="ifs-td-time">
										<?php echo esc_html( date_i18n( 'd M, h:i A', strtotime( $item->entry_time ) ) ); ?>
									</td>
									<!-- লাইভ কাউন্টার-আপ সেল -->
									<td>
										<span class="ifs-duration-pill">
											<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
											<span class="ifs-live-counter" data-entry="<?php echo esc_attr( $entry_iso ); ?>"><?php esc_html_e( 'গণনা হচ্ছে...', 'ifs-parking' ); ?></span>
										</span>
									</td>
									<!-- রিলিজ বাটন -->
									<td class="ifs-td-action">
										<button type="button" class="ifs-btn-release ifs-trigger-release"
												data-id="<?php echo esc_attr( $item->id ); ?>"
												data-veh="<?php echo esc_attr( $item->vehicle_number ); ?>"
												data-dept="<?php echo esc_attr( ! empty( $item->reference_id ) ? $item->reference_id : 'General' ); ?>"
												data-cat="<?php echo esc_attr( ucfirst( $item->parking_type ) ); ?>"
												data-driver="<?php echo esc_attr( ! empty( $item->driver_name ) ? $item->driver_name : __( 'তথ্য নেই', 'ifs-parking' ) ); ?>"
												data-phone="<?php echo esc_attr( $item->driver_phone ); ?>"
												data-entry="<?php echo esc_attr( $entry_iso ); ?>"
												data-entry-formatted="<?php echo esc_attr( date_i18n( 'd M, Y - h:i A', strtotime( $item->entry_time ) ) ); ?>"
												data-rate="<?php echo esc_attr( $rate_fee ); ?>">
											<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
											<?php esc_html_e( 'রিলিজ', 'ifs-parking' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr id="no-records-row">
								<td colspan="7" class="ifs-empty-table-cell">
									<div class="ifs-empty-icon-box">
										<svg class="ifs-svg-large" viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
									</div>
									<strong class="ifs-empty-title"><?php esc_html_e( 'বর্তমানে কোনো গাড়ি পার্কিংয়ে নেই!', 'ifs-parking' ); ?></strong>
									<p class="ifs-empty-desc"><?php esc_html_e( 'নতুন কোনো গাড়ি প্রবেশ করলে তা স্বয়ংক্রিয়ভাবে এখানে তালিকাভুক্ত হবে।', 'ifs-parking' ); ?></p>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<!-- রিলিজ ও অটো-ক্যালকুলেশন প্রিন্ট মডাল -->
	<div id="ifs-release-modal" class="ifs-modal-backdrop">
		<div class="ifs-modal-card">
			<!-- প্রিন্ট স্লিপ এরিয়া -->
			<div id="ifs-printable-exit-slip" class="ifs-receipt-wrapper">
				<div class="ifs-receipt-header">
					<h3 class="ifs-receipt-title"><?php echo esc_html( $site_title ); ?></h3>
					<div class="ifs-receipt-phone">
						<svg class="ifs-svg-xxs" viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
						<?php esc_html_e( 'হেল্পলাইন:', 'ifs-parking' ); ?> <?php echo esc_html( $system_phone ); ?>
					</div>
					<p class="ifs-receipt-subtitle"><?php esc_html_e( 'এক্সিট স্লিপ ও বিলিং রসিদ', 'ifs-parking' ); ?></p>
				</div>

				<div class="ifs-receipt-body">
					<div class="ifs-receipt-row">
						<span class="ifs-receipt-lbl"><?php esc_html_e( 'গাড়ি নম্বর:', 'ifs-parking' ); ?></span>
						<strong id="m-veh" class="ifs-receipt-val-highlight">-</strong>
					</div>
					<div class="ifs-receipt-row">
						<span class="ifs-receipt-lbl"><?php esc_html_e( 'ডিপার্টমেন্ট / ক্যাটাগরি:', 'ifs-parking' ); ?></span>
						<span id="m-dept-cat" class="ifs-receipt-val-bold">-</span>
					</div>
					<div class="ifs-receipt-row">
						<span class="ifs-receipt-lbl"><?php esc_html_e( 'প্রবেশের সময়:', 'ifs-parking' ); ?></span>
						<span id="m-entry">-</span>
					</div>
					<div class="ifs-receipt-row">
						<span class="ifs-receipt-lbl"><?php esc_html_e( 'রিলিজের সময়:', 'ifs-parking' ); ?></span>
						<span id="m-exit" class="ifs-receipt-val-bold">-</span>
					</div>
					<div class="ifs-receipt-row ifs-receipt-highlight-amber">
						<span class="ifs-receipt-lbl-amber"><?php esc_html_e( 'মোট সময়কাল:', 'ifs-parking' ); ?></span>
						<strong id="m-duration" class="ifs-receipt-val-amber">-</strong>
					</div>
					<div class="ifs-receipt-row ifs-receipt-highlight-green">
						<span class="ifs-receipt-lbl-green"><?php esc_html_e( 'মোট প্রদেয় ফি:', 'ifs-parking' ); ?></span>
						<strong id="m-total-fee" class="ifs-receipt-val-green">-</strong>
					</div>
					<div class="ifs-receipt-row">
						<span class="ifs-receipt-lbl"><?php esc_html_e( 'চালক ও ফোন:', 'ifs-parking' ); ?></span>
						<span id="m-driver">-</span>
					</div>
				</div>

				<div class="ifs-receipt-footer">
					<p class="ifs-receipt-thankyou"><?php esc_html_e( 'আমাদের পার্কিং সুবিধা ব্যবহার করার জন্য ধন্যবাদ!', 'ifs-parking' ); ?></p>
				</div>
			</div>

			<!-- মডাল ফুটার বাটনসমূহ -->
			<div class="ifs-no-print ifs-modal-actions">
				<button type="button" id="ifs-manual-print-btn" class="ifs-btn-print">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
					<?php esc_html_e( 'রসিদ প্রিন্ট করুন', 'ifs-parking' ); ?>
				</button>

				<div class="ifs-btn-group-end">
					<button type="button" id="ifs-close-modal-btn" class="ifs-btn-cancel"><?php esc_html_e( 'বাতিল', 'ifs-parking' ); ?></button>

					<form method="post" action="" class="ifs-form-inline">
						<?php wp_nonce_field( 'ifs_confirm_release', 'ifs_confirm_release_nonce' ); ?>
						<input type="hidden" name="rel_log_id" id="ifs-form-rel-id" value="">
						<input type="hidden" name="final_fee" id="ifs-form-final-fee" value="">
						<button type="submit" class="ifs-btn-confirm-release">
							<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
							<?php esc_html_e( 'রিলিজ নিশ্চিত করুন', 'ifs-parking' ); ?>
						</button>
					</form>
				</div>
			</div>
		</div>
	</div>
	<?php
}