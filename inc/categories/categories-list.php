<?php
/**
 * Categories List Table Template (Grouped by Department).
 *
 * @package Ifs_Parking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ifs_parking_render_category_list( $table_cats, $currency_symbol ) {
	global $wpdb;

	$categories = $wpdb->get_results( "SELECT * FROM $table_cats ORDER BY department ASC, id ASC" );

	$grouped_cats = array();
	if ( ! empty( $categories ) ) {
		foreach ( $categories as $cat ) {
			$dept_key = ! empty( $cat->department ) ? trim( $cat->department ) : 'General';
			$grouped_cats[ $dept_key ][] = $cat;
		}
	}
	?>
	<div class="ifs-card ifs-table-container-card">
		<div class="ifs-list-header">
			<h3 class="ifs-list-title">
				<svg class="ifs-svg-primary" viewBox="0 0 24 24"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>
				<?php esc_html_e( 'সকল ক্যাটাগরি ও ডিপার্টমেন্ট তালিকা', 'ifs-parking' ); ?>
			</h3>
			<span class="ifs-counter-badge-alt">
				<?php echo esc_html( sprintf( __( 'মোট ডিপার্টমেন্ট: %d টি', 'ifs-parking' ), count( $grouped_cats ) ) ); ?>
			</span>
		</div>

		<div class="ifs-table-responsive">
			<table class="wp-list-table widefat fixed striped ifs-custom-data-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ডিপার্টমেন্ট', 'ifs-parking' ); ?></th>
						<th><?php esc_html_e( 'প্যাকেজ সংখ্যা', 'ifs-parking' ); ?></th>
						<th><?php esc_html_e( 'অন্তর্ভুক্ত গাড়ির ধরণসমূহ', 'ifs-parking' ); ?></th>
						<th><?php esc_html_e( 'রেট সারসংক্ষেপ', 'ifs-parking' ); ?></th>
						<th class="ifs-col-actions-lg"><?php esc_html_e( 'অ্যাকশন', 'ifs-parking' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $grouped_cats ) ) : ?>
						<?php foreach ( $grouped_cats as $dept_name => $dept_items ) : ?>
							<?php
							$view_url   = admin_url( 'admin.php?page=ifs-parking-categories&action=view&dept=' . urlencode( $dept_name ) );
							$edit_url   = admin_url( 'admin.php?page=ifs-parking-categories&action=edit&dept=' . urlencode( $dept_name ) );
							$delete_url = wp_nonce_url( admin_url( 'admin.php?page=ifs-parking-categories&action=delete_dept&dept=' . urlencode( $dept_name ) ), 'ifs_delete_dept_' . $dept_name );

							$unique_vehs = array_unique( wp_list_pluck( $dept_items, 'vehicle_type' ) );
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
										<?php echo esc_html( sprintf( __( '%d টি প্যাকেজ', 'ifs-parking' ), count( $dept_items ) ) ); ?>
									</span>
								</td>
								<td>
									<span class="ifs-td-muted">
										<?php echo esc_html( implode( ', ', $unique_vehs ) ); ?>
									</span>
								</td>
								<td>
									<small class="ifs-rate-summary-tag">
										<?php echo esc_html( count( $dept_items ) . ' টি কনফিগারেশন সক্রিয়' ); ?>
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
										<a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'আপনি কি নিশ্চিতভাবে এই সম্পূর্ণ ডিপার্টমেন্ট এবং এর সকল প্যাকেজ মুছে ফেলতে চান?', 'ifs-parking' ) ); ?>');" class="ifs-btn-action ifs-btn-delete" title="<?php esc_attr_e( 'ডিলিট করুন', 'ifs-parking' ); ?>">
											<svg class="ifs-svg-xs" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
											<?php esc_html_e( 'ডিলিট', 'ifs-parking' ); ?>
										</a>
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
	<?php
}