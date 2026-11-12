<?php
/**
 * Categories List Table Template (Grouped by Department).
 *
 * @package IFS_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ইংরেজি সংখ্যাকে বাংলা সংখ্যায় রূপান্তর করার হেল্পার ফাংশন।
 *
 * @param string|int|float $number ইনপুট সংখ্যা বা স্ট্রিং।
 * @return string
 */
function ifs_parking_cat_list_bn_num( $number ) {
	if ( function_exists( 'ifs_parking_to_bn_number' ) ) {
		return ifs_parking_to_bn_number( $number );
	}

	$en_digits = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	$bn_digits = array( '০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯' );

	return str_replace( $en_digits, $bn_digits, (string) $number );
}

/**
 * ডিপার্টমেন্ট অনুযায়ী গ্রুপ করা ক্যাটাগরি তালিকা রেন্ডারার।
 *
 * @param string $table_cats      ক্যাটাগরি টেবিল নাম।
 * @param string $currency_symbol কারেন্সি প্রতীক।
 * @return void
 */
function ifs_parking_render_category_list( $table_cats, $currency_symbol ) {
	global $wpdb;

	$categories = array();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_cats ) ) === $table_cats ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$categories = $wpdb->get_results( "SELECT * FROM `{$table_cats}` ORDER BY department ASC, id ASC" );
	}

	$grouped_cats = array();
	if ( ! empty( $categories ) ) {
		foreach ( $categories as $cat ) {
			$dept_key                    = ! empty( $cat->department ) ? trim( $cat->department ) : 'General';
			$grouped_cats[ $dept_key ][] = $cat;
		}
	}

	$veh_labels_bd = array(
		'Car'   => __( 'কার', 'ifs-parking' ),
		'Noah'  => __( 'নোয়াহ', 'ifs-parking' ),
		'Hiace' => __( 'হাইয়েস', 'ifs-parking' ),
		'CNG'   => __( 'সিএনজি', 'ifs-parking' ),
		'Truck' => __( 'ট্রাক', 'ifs-parking' ),
		'Bike'  => __( 'বাইক', 'ifs-parking' ),
		'Cycle' => __( 'সাইকেল', 'ifs-parking' ),
	);

	// আনইউজড ভেরিয়েবল এড়াতে কারেন্সি ফিল্টার রেফারেন্স রাখা।
	$active_currency = ! empty( $currency_symbol ) ? $currency_symbol : '৳';
	?>
	<style>
		/* ক্যাটাগরি তালিকা রেসপনসিভনেস ও প্রমিত বাংলা টাইপোগ্রাফি */
		.ifs-table-container-card {
			font-family: 'Hind Siliguri', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
		}
		.ifs-list-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 12px;
			margin-bottom: 16px;
		}
		.ifs-table-responsive {
			width: 100%;
			overflow-x: auto;
			-webkit-overflow-scrolling: touch;
		}
		.ifs-custom-data-table {
			min-width: 680px;
		}
		.ifs-popup-overlay {
			position: fixed;
			top: 0;
			left: 0;
			width: 100vw;
			height: 100vh;
			background: rgba(15, 23, 42, 0.6);
			backdrop-filter: blur(4px);
			display: flex;
			align-items: center;
			justify-content: center;
			z-index: 999999;
			padding: 16px;
			box-sizing: border-box;
		}
		.ifs-popup-box {
			background: #ffffff;
			border-radius: 14px;
			padding: 24px;
			max-width: 420px;
			width: 100%;
			text-align: center;
			box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
			box-sizing: border-box;
		}

		/* ট্যাবলেট এবং মোবাইল ভিউপোর্ট রুলস */
		@media screen and (max-width: 782px) {
			.ifs-list-header {
				flex-direction: column;
				align-items: flex-start;
				gap: 8px;
			}
			.ifs-counter-badge-alt {
				align-self: flex-start;
			}
			.ifs-popup-box {
				padding: 20px 16px;
				border-radius: 12px;
			}
			.ifs-popup-box .ifs-modal-actions {
				flex-direction: column;
				gap: 8px !important;
			}
			.ifs-popup-box button,
			.ifs-popup-box a {
				width: 100%;
				box-sizing: border-box;
				text-align: center;
				height: 44px;
				display: flex !important;
				align-items: center;
				justify-content: center;
			}
		}
	</style>

	<div class="ifs-card ifs-table-container-card" data-currency="<?php echo esc_attr( $active_currency ); ?>">
		<div class="ifs-list-header">
			<h3 class="ifs-list-title">
				<svg class="ifs-svg-primary" viewBox="0 0 24 24"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>
				<?php esc_html_e( 'সকল ক্যাটাগরি ও ডিপার্টমেন্ট তালিকা', 'ifs-parking' ); ?>
			</h3>
			<span class="ifs-counter-badge-alt">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: মোট ডিপার্টমেন্ট সংখ্যা */
						__( 'মোট ডিপার্টমেন্ট: %s টি', 'ifs-parking' ),
						ifs_parking_cat_list_bn_num( count( $grouped_cats ) )
					)
				);
				?>
			</span>
		</div>

		<div class="ifs-table-responsive">
			<table class="wp-list-table widefat fixed striped ifs-custom-data-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ডিপার্টমেন্ট', 'ifs-parking' ); ?></th>
						<th><?php esc_html_e( 'প্যাকেজ সংখ্যা', 'ifs-parking' ); ?></th>
						<th><?php esc_html_e( 'অন্তর্ভুক্ত গাড়ির ধরণসমূহ', 'ifs-parking' ); ?></th>
						<th><?php esc_html_e( 'রেট সারসংক্ষেপ', 'ifs-parking' ); ?></th>
						<th class="ifs-col-actions-lg"><?php esc_html_e( 'অ্যাকশন', 'ifs-parking' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $grouped_cats ) ) : ?>
						<?php
						foreach ( $grouped_cats as $dept_name => $dept_items ) :
							$view_url = add_query_arg(
								array(
									'page'   => 'ifs-parking-categories',
									'action' => 'view',
									'dept'   => rawurlencode( $dept_name ),
								),
								admin_url( 'admin.php' )
							);
							$edit_url = add_query_arg(
								array(
									'page'   => 'ifs-parking-categories',
									'action' => 'edit',
									'dept'   => rawurlencode( $dept_name ),
								),
								admin_url( 'admin.php' )
							);
							$delete_url = wp_nonce_url(
								add_query_arg(
									array(
										'page'   => 'ifs-parking-categories',
										'action' => 'delete_dept',
										'dept'   => rawurlencode( $dept_name ),
									),
									admin_url( 'admin.php' )
								),
								'ifs_delete_dept_' . $dept_name
							);

							$unique_vehs    = array_unique( wp_list_pluck( $dept_items, 'vehicle_type' ) );
							$localized_vehs = array();
							foreach ( $unique_vehs as $v_item ) {
								$localized_vehs[] = isset( $veh_labels_bd[ $v_item ] ) ? $veh_labels_bd[ $v_item ] : $v_item;
							}
							$pkg_count_bn = ifs_parking_cat_list_bn_num( count( $dept_items ) );
							?>
							<tr>
								<td>
									<div class="ifs-dept-title-cell">
										<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
										<strong class="ifs-veh-strong"><?php echo esc_html( $dept_name ); ?></strong>
									</div>
								</td>
								<td>
									<span class="ifs-badge-dept">
										<?php
										echo esc_html(
											sprintf(
												/* translators: %s: প্যাকেজ সংখ্যা */
												__( '%s টি প্যাকেজ', 'ifs-parking' ),
												$pkg_count_bn
											)
										);
										?>
									</span>
								</td>
								<td>
									<span class="ifs-td-muted">
										<?php echo esc_html( implode( ', ', $localized_vehs ) ); ?>
									</span>
								</td>
								<td>
									<small class="ifs-rate-summary-tag">
										<?php
										echo esc_html(
											sprintf(
												/* translators: %s: সক্রিয় কনফিগারেশন সংখ্যা */
												__( '%s টি কনফিগারেশন সক্রিয়', 'ifs-parking' ),
												$pkg_count_bn
											)
										);
										?>
									</small>
								</td>
								<td class="ifs-td-action-right">
									<div class="ifs-action-buttons-group">
										<a href="<?php echo esc_url( $view_url ); ?>" class="ifs-btn-action ifs-btn-view" title="<?php esc_attr_e( 'বিস্তারিত দেখুন', 'ifs-parking' ); ?>">
											<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
											<?php esc_html_e( 'ভিউ', 'ifs-parking' ); ?>
										</a>
										<a href="<?php echo esc_url( $edit_url ); ?>" class="ifs-btn-action ifs-btn-edit" title="<?php esc_attr_e( 'সম্পাদনা করুন', 'ifs-parking' ); ?>">
											<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
											<?php esc_html_e( 'এডিট', 'ifs-parking' ); ?>
										</a>
										<button type="button" class="ifs-btn-action ifs-btn-delete ifs-trigger-delete-modal" data-delete-url="<?php echo esc_url( $delete_url ); ?>" data-dept-name="<?php echo esc_attr( $dept_name ); ?>" title="<?php esc_attr_e( 'ডিলিট করুন', 'ifs-parking' ); ?>">
											<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
											<?php esc_html_e( 'ডিলিট', 'ifs-parking' ); ?>
										</button>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="5" class="ifs-empty-table-cell"><?php esc_html_e( 'কোনো ক্যাটাগরি পাওয়া যায়নি।', 'ifs-parking' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- কাস্টম ডিলিট কনফার্মেশন পপআপ মডাল -->
	<div id="ifs-delete-confirm-modal" class="ifs-popup-overlay" style="display: none;">
		<div class="ifs-popup-box">
			<div style="width: 48px; height: 48px; margin: 0 auto 12px auto; border-radius: 50%; background: #fef2f2; display: flex; align-items: center; justify-content: center;">
				<svg viewBox="0 0 24 24" style="width: 26px; height: 26px; fill: #dc2626;"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
			</div>
			<h3 style="color: #b91c1c; margin: 0 0 8px 0; font-size: 18px; font-weight: 700;"><?php esc_html_e( 'নিশ্চিতকরণ বার্তা', 'ifs-parking' ); ?></h3>
			<p id="ifs-delete-modal-text" style="color: #475569; font-size: 13.5px; margin: 0 0 20px 0; line-height: 1.5;"></p>
			<div class="ifs-modal-actions" style="display: flex; gap: 10px; justify-content: center;">
				<button type="button" id="ifs-modal-cancel-btn" style="background: #e2e8f0; color: #334155; border: none; padding: 8px 18px; font-weight: 600; border-radius: 8px; cursor: pointer;"><?php esc_html_e( 'বাতিল', 'ifs-parking' ); ?></button>
				<a href="#" id="ifs-modal-confirm-btn" style="background: #ef4444; color: #fff; text-decoration: none; padding: 8px 18px; font-weight: 600; border-radius: 8px; display: inline-block;"><?php esc_html_e( 'হ্যাঁ, ডিলিট করুন', 'ifs-parking' ); ?></a>
			</div>
		</div>
	</div>

	<!-- ডিলিট মডালের জন্য XSS-সেফ জাভাস্ক্রিপ্ট -->
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		var deleteModal = document.getElementById('ifs-delete-confirm-modal');
		var modalText   = document.getElementById('ifs-delete-modal-text');
		var confirmBtn  = document.getElementById('ifs-modal-confirm-btn');
		var cancelBtn   = document.getElementById('ifs-modal-cancel-btn');

		var msgBefore = <?php echo wp_json_encode( __( 'আপনি কি নিশ্চিতভাবে ', 'ifs-parking' ) ); ?>;
		var msgAfter  = <?php echo wp_json_encode( __( ' ডিপার্টমেন্ট এবং এর সকল প্যাকেজ মুছে ফেলতে চান?', 'ifs-parking' ) ); ?>;

		document.querySelectorAll('.ifs-trigger-delete-modal').forEach(function(btn) {
			btn.addEventListener('click', function(e) {
				e.preventDefault();
				var deptName  = this.getAttribute('data-dept-name') || '';
				var deleteUrl = this.getAttribute('data-delete-url') || '#';

				modalText.textContent = '';
				modalText.appendChild(document.createTextNode(msgBefore));
				var strongEl = document.createElement('strong');
				strongEl.textContent = '"' + deptName + '"';
				modalText.appendChild(strongEl);
				modalText.appendChild(document.createTextNode(msgAfter));

				confirmBtn.setAttribute('href', deleteUrl);
				deleteModal.style.display = 'flex';
			});
		});

		if (cancelBtn) {
			cancelBtn.addEventListener('click', function() {
				deleteModal.style.display = 'none';
			});
		}

		window.addEventListener('click', function(e) {
			if (e.target === deleteModal) {
				deleteModal.style.display = 'none';
			}
		});
	});
	</script>
	<?php
}