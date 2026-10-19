<?php
/**
 * Settings Management Module.
 *
 * @package Ifs_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * সেটিংস পেজ রেন্ডারার
 */
function ifs_parking_render_settings_page() {
	ifs_parking_render_layout_wrapper( 'ifs_parking_render_settings_content' );
}

/**
 * মূল সেটিংস কনটেন্ট
 */
function ifs_parking_render_settings_content() {
	// সেটিংস সেভ হ্যান্ডলার
	if ( isset( $_POST['ifs_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ifs_settings_nonce'] ) ), 'ifs_save_settings' ) ) {
		$site_title     = isset( $_POST['site_title'] ) ? sanitize_text_field( wp_unslash( $_POST['site_title'] ) ) : 'IFS Parking System';
		$logo_url       = isset( $_POST['logo_url'] ) ? esc_url_raw( wp_unslash( $_POST['logo_url'] ) ) : '';
		$currency       = isset( $_POST['currency'] ) ? sanitize_text_field( wp_unslash( $_POST['currency'] ) ) : 'BDT (৳)';
		$ticket_prefix  = isset( $_POST['ticket_prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['ticket_prefix'] ) ) : 'IFS-';
		$tax_rate       = isset( $_POST['tax_rate'] ) ? floatval( wp_unslash( $_POST['tax_rate'] ) ) : 0.00;
		$company_addr   = isset( $_POST['company_address'] ) ? sanitize_text_field( wp_unslash( $_POST['company_address'] ) ) : '';
		$company_phone  = isset( $_POST['company_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['company_phone'] ) ) : '';

		update_option( 'ifs_parking_site_title', $site_title );
		update_option( 'ifs_parking_site_logo', $logo_url ); // সাইডবারের সাথে মিলিয়ে কি ফিক্স
		update_option( 'ifs_parking_currency', $currency );
		update_option( 'ifs_parking_prefix', $ticket_prefix );
		update_option( 'ifs_parking_tax_rate', $tax_rate );
		update_option( 'ifs_parking_address', $company_addr );
		update_option( 'ifs_parking_contact_phone', $company_phone ); // স্লিপের সাথে মিলিয়ে কি ফিক্স

		echo '<div class="notice notice-success is-dismissible ifs-custom-notice">
				<p class="ifs-notice-text">
					<svg class="ifs-notice-svg" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
					' . esc_html__( 'সিস্টেম সেটিংস সফলভাবে আপডেট করা হয়েছে!', 'ifs-parking' ) . '
				</p>
			  </div>';
	}

	// সংরক্ষিত অপশনসমূহ লোড করা
	$site_title    = get_option( 'ifs_parking_site_title', 'IFS Parking System' );
	$logo_url      = get_option( 'ifs_parking_site_logo', '' );
	$currency      = get_option( 'ifs_parking_currency', 'BDT (৳)' );
	$ticket_prefix = get_option( 'ifs_parking_prefix', 'IFS-' );
	$tax_rate      = get_option( 'ifs_parking_tax_rate', '0.00' );
	$address       = get_option( 'ifs_parking_address', '' );
	$phone         = get_option( 'ifs_parking_contact_phone', '' );

	wp_enqueue_media();
	?>
	<div class="wrap ifs-settings-wrapper">
		<!-- হেডার ব্যানার -->
		<div class="ifs-settings-banner">
			<div>
				<span class="ifs-banner-badge ifs-badge-blue">
					<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36.25c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
					<?php esc_html_e( 'সিস্টেম কনফিগারেশন', 'ifs-parking' ); ?>
				</span>
				<h1 class="ifs-banner-title"><?php esc_html_e( 'সিস্টেম সেটিংস', 'ifs-parking' ); ?></h1>
				<p class="ifs-banner-subtitle"><?php esc_html_e( 'পার্কিং সিস্টেমের জেনারেল কনফিগারেশন, ব্র্যান্ড লোগো ও কন্টাক্ট ইনফো কনফিগার করুন।', 'ifs-parking' ); ?></p>
			</div>
		</div>

		<!-- সেটিংস ফর্ম কার্ড -->
		<div class="ifs-card ifs-form-card ifs-settings-form-card">
			<form method="post" action="">
				<?php wp_nonce_field( 'ifs_save_settings', 'ifs_settings_nonce' ); ?>

				<div class="ifs-settings-grid">
					<!-- Site Title -->
					<div class="ifs-step-box">
						<label class="ifs-form-label"><?php esc_html_e( 'সিস্টেম বা সাইট টাইটেল', 'ifs-parking' ); ?></label>
						<input type="text" name="site_title" value="<?php echo esc_attr( $site_title ); ?>" required class="ifs-veh-input" />
						<p class="ifs-form-hint"><?php esc_html_e( 'রসিদ ও ড্যাশবোর্ডে প্রদর্শিত প্রাতিষ্ঠানিক নাম।', 'ifs-parking' ); ?></p>
					</div>

					<!-- Currency -->
					<div class="ifs-step-box">
						<label class="ifs-form-label"><?php esc_html_e( 'কারেন্সি (Currency)', 'ifs-parking' ); ?></label>
						<select name="currency" class="ifs-veh-input">
							<option value="BDT (৳)" <?php selected( $currency, 'BDT (৳)' ); ?>>BDT (৳) - <?php esc_html_e( 'বাংলাদেশি টাকা', 'ifs-parking' ); ?></option>
							<option value="USD ($)" <?php selected( $currency, 'USD ($)' ); ?>>USD ($) - <?php esc_html_e( 'মার্কিন ডলার', 'ifs-parking' ); ?></option>
							<option value="EUR (€)" <?php selected( $currency, 'EUR (€)' ); ?>>EUR (€) - <?php esc_html_e( 'ইউরো', 'ifs-parking' ); ?></option>
							<option value="GBP (£)" <?php selected( $currency, 'GBP (£)' ); ?>>GBP (£) - <?php esc_html_e( 'ব্রিটিশ পাউন্ড', 'ifs-parking' ); ?></option>
							<option value="SAR (﷼)" <?php selected( $currency, 'SAR (﷼)' ); ?>>SAR (﷼) - <?php esc_html_e( 'সৌদি রিয়াল', 'ifs-parking' ); ?></option>
						</select>
						<p class="ifs-form-hint"><?php esc_html_e( 'হিসাব ও টিকিটের ফি দেখানোর কারেন্সি।', 'ifs-parking' ); ?></p>
					</div>

					<!-- Ticket Prefix -->
					<div class="ifs-step-box">
						<label class="ifs-form-label"><?php esc_html_e( 'টিকিট প্রিফিক্স (Prefix)', 'ifs-parking' ); ?></label>
						<input type="text" name="ticket_prefix" value="<?php echo esc_attr( $ticket_prefix ); ?>" placeholder="<?php esc_attr_e( 'যেমন: IFS-', 'ifs-parking' ); ?>" class="ifs-veh-input" />
						<p class="ifs-form-hint"><?php esc_html_e( 'ইনভয়েসের পূর্বে যুক্ত থাকবে (যেমন: IFS-101)।', 'ifs-parking' ); ?></p>
					</div>

					<!-- Tax Rate -->
					<div class="ifs-step-box">
						<label class="ifs-form-label"><?php esc_html_e( 'ভ্যাট / ট্যাক্স রেট (%)', 'ifs-parking' ); ?></label>
						<input type="number" step="0.01" name="tax_rate" value="<?php echo esc_attr( $tax_rate ); ?>" class="ifs-veh-input" />
						<p class="ifs-form-hint"><?php esc_html_e( 'পার্কিং ফির সাথে অতিরিক্ত ভ্যাট পার্সেন্টেজ।', 'ifs-parking' ); ?></p>
					</div>

					<!-- Phone -->
					<div class="ifs-step-box">
						<label class="ifs-form-label"><?php esc_html_e( 'যোগাযোগের নম্বর (Phone)', 'ifs-parking' ); ?></label>
						<input type="text" name="company_phone" value="<?php echo esc_attr( $phone ); ?>" placeholder="০১৭xxxxxxxx" class="ifs-veh-input" />
						<p class="ifs-form-hint"><?php esc_html_e( 'রসিদে প্রিন্ট করার জন্য হেল্পলাইন নম্বর।', 'ifs-parking' ); ?></p>
					</div>

					<!-- Address -->
					<div class="ifs-step-box">
						<label class="ifs-form-label"><?php esc_html_e( 'অফিস ঠিকানা (Address)', 'ifs-parking' ); ?></label>
						<input type="text" name="company_address" value="<?php echo esc_attr( $address ); ?>" placeholder="<?php esc_attr_e( 'গুলশান-২, ঢাকা', 'ifs-parking' ); ?>" class="ifs-veh-input" />
						<p class="ifs-form-hint"><?php esc_html_e( 'পার্কিং এরিয়ার লোকেশন বা ঠিকানা।', 'ifs-parking' ); ?></p>
					</div>

					<!-- Logo Media Uploader (Full Width) -->
					<div class="ifs-step-box ifs-full-col">
						<label class="ifs-form-label"><?php esc_html_e( 'কোম্পানি লোগো (Media Upload)', 'ifs-parking' ); ?></label>
						<div class="ifs-media-upload-group">
							<input type="text" id="ifs_parking_logo_url" name="logo_url" value="<?php echo esc_url( $logo_url ); ?>" placeholder="<?php esc_attr_e( 'লোগো ইমেজ সিলেক্ট করুন...', 'ifs-parking' ); ?>" class="ifs-veh-input ifs-media-input" />
							<button type="button" id="ifs_upload_logo_btn" class="ifs-btn-media-select">
								<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
								<?php esc_html_e( 'লোগো আপলোড', 'ifs-parking' ); ?>
							</button>
						</div>
						<div id="ifs_logo_preview" class="ifs-media-preview-box">
							<?php if ( ! empty( $logo_url ) ) : ?>
								<div class="ifs-logo-preview-card">
									<img src="<?php echo esc_url( $logo_url ); ?>" class="ifs-brand-logo-preview" alt="Site Logo" />
								</div>
							<?php endif; ?>
						</div>
						<p class="ifs-form-hint"><?php esc_html_e( 'সাইডবার ও প্রিন্টেবল রসিদে ব্যবহারের জন্য লোগোর সরাসরি লিংক।', 'ifs-parking' ); ?></p>
					</div>
				</div>

				<div class="ifs-form-submit-row">
					<button type="submit" name="submit_settings" class="ifs-btn-submit">
						<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
						<?php esc_html_e( 'সেটিংস সংরক্ষণ করুন', 'ifs-parking' ); ?>
					</button>
				</div>
			</form>
		</div>
	</div>
	<?php
}