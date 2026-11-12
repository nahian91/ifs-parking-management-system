<?php
/**
 * Categories View Template (Same Layout as Add/Edit).
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
function ifs_parking_cat_view_bn_num( $number ) {
	if ( function_exists( 'ifs_parking_to_bn_number' ) ) {
		return ifs_parking_to_bn_number( $number );
	}

	$en_digits = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	$bn_digits = array( '০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯' );

	return str_replace( $en_digits, $bn_digits, (string) $number );
}

/**
 * ডিপার্টমেন্ট ভিত্তিক ক্যাটাগরি ভিউ রেন্ডারার।
 *
 * @param string $table_cats      ক্যাটাগরি টেবিল নাম।
 * @param string $dept_name       ডিপার্টমেন্টের নাম।
 * @param string $currency_symbol কারেন্সি প্রতীক।
 * @return void
 */
function ifs_parking_render_category_view( $table_cats, $dept_name, $currency_symbol ) {
	global $wpdb;

	$rows = array();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_cats ) ) === $table_cats ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table_cats}` WHERE department = %s ORDER BY id ASC", $dept_name ) );
	}

	if ( empty( $rows ) ) {
		echo '<div class="notice notice-error ifs-custom-notice-err"><p>' . esc_html__( 'কোনো তথ্য পাওয়া যায়নি।', 'ifs-parking' ) . '</p></div>';
		return;
	}

	$edit_url = add_query_arg(
		array(
			'page'   => 'ifs-parking-categories',
			'action' => 'edit',
			'dept'   => rawurlencode( $dept_name ),
		),
		admin_url( 'admin.php' )
	);

	$veh_labels_bd = array(
		'Car'   => __( 'কার (Car)', 'ifs-parking' ),
		'Noah'  => __( 'নোয়াহ (Noah)', 'ifs-parking' ),
		'Hiace' => __( 'হাইয়েস (Hiace)', 'ifs-parking' ),
		'CNG'   => __( 'সিএনজি (CNG)', 'ifs-parking' ),
		'Truck' => __( 'ট্রাক (Truck)', 'ifs-parking' ),
		'Bike'  => __( 'বাইক (Bike)', 'ifs-parking' ),
		'Cycle' => __( 'সাইকেল (Cycle)', 'ifs-parking' ),
	);
	?>
	<style>
		/* ক্যাটাগরি ভিউ রেসপনসিভনেস ও প্রমিত বাংলা টাইপোগ্রাফি */
		.ifs-form-card {
			font-family: 'Hind Siliguri', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
		}
		.ifs-card-header-bordered {
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 12px;
			margin-bottom: 20px;
		}
		.ifs-actions-row {
			display: flex;
			align-items: center;
			gap: 10px;
			flex-wrap: wrap;
		}
		.ifs-repeater-row.ifs-view-row-mode {
			display: grid;
			grid-template-columns: 1.5fr 1.3fr repeat(4, 1fr) 46px;
			gap: 12px;
			align-items: center;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 16px;
			margin-bottom: 14px;
		}
		.ifs-repeater-action-col {
			display: flex;
			align-items: center;
			justify-content: center;
		}

		/* ট্যাবলেট এবং মিডিয়াম স্ক্রিন */
		@media screen and (max-width: 1024px) {
			.ifs-repeater-row.ifs-view-row-mode {
				grid-template-columns: repeat(2, 1fr);
				gap: 12px;
			}
			.ifs-repeater-row.ifs-view-row-mode .ifs-repeater-action-col {
				grid-column: 1 / -1;
				justify-content: flex-end;
			}
		}

		/* মোবাইল স্ক্রিন রুলস */
		@media screen and (max-width: 782px) {
			.ifs-card-header-bordered {
				flex-direction: column;
				align-items: stretch;
			}
			.ifs-actions-row {
				flex-direction: column;
				width: 100%;
				gap: 8px;
			}
			.ifs-actions-row .ifs-btn-back,
			.ifs-actions-row .ifs-btn-primary-action {
				width: 100%;
				justify-content: center;
				height: 44px;
				box-sizing: border-box;
			}
			.ifs-repeater-row.ifs-view-row-mode {
				grid-template-columns: 1fr;
				gap: 12px;
				padding: 14px;
			}
			.ifs-repeater-row.ifs-view-row-mode .ifs-repeater-action-col {
				display: none;
			}
		}
	</style>

	<div class="ifs-card ifs-form-card">
		<!-- হেডার অংশ -->
		<div class="ifs-card-header-bordered">
			<div>
				<h3 class="ifs-card-title-lg">
					<svg class="ifs-svg-primary" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: ডিপার্টমেন্টের নাম */
							__( 'ডিপার্টমেন্ট বিস্তারিত: %s', 'ifs-parking' ),
							$dept_name
						)
					);
					?>
				</h3>
				<p class="ifs-card-subtitle"><?php esc_html_e( 'এই ডিপার্টমেন্টের অধীনে সকল সক্রিয় রেট ও প্যাকেজ বিবরণী।', 'ifs-parking' ); ?></p>
			</div>
			<div class="ifs-actions-row">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-parking-categories' ) ); ?>" class="ifs-btn-back">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
					<?php esc_html_e( 'তালিকায় ফিরে যান', 'ifs-parking' ); ?>
				</a>
				<a href="<?php echo esc_url( $edit_url ); ?>" class="ifs-btn-primary-action">
					<svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
					<?php esc_html_e( 'সম্পাদনা করুন', 'ifs-parking' ); ?>
				</a>
			</div>
		</div>

		<!-- ডিপার্টমেন্ট কার্ড ভিউ -->
		<div class="ifs-dept-box">
			<label class="ifs-field-label ifs-field-label-dept">
				<svg viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
				<?php esc_html_e( 'ডিপার্টমেন্টের নাম', 'ifs-parking' ); ?>
			</label>
			<strong class="ifs-dept-name-display"><?php echo esc_html( $dept_name ); ?></strong>
		</div>

		<div class="ifs-section-subhead">
			<h4 class="ifs-subhead-title">
				<svg class="ifs-svg-primary" viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: প্যাকেজ সংখ্যা */
						__( 'সংযুক্ত প্যাকেজ ও রেট তালিকা (%s টি)', 'ifs-parking' ),
						ifs_parking_cat_view_bn_num( count( $rows ) )
					)
				);
				?>
			</h4>
		</div>

		<!-- রিপিটার লেআউটের অনুরূপ ভিউ রো -->
		<div id="ifs-repeater-container">
			<?php
			foreach ( $rows as $r ) :
				$veh_label  = isset( $veh_labels_bd[ $r->vehicle_type ] ) ? $veh_labels_bd[ $r->vehicle_type ] : $r->vehicle_type;
				$free_hours = absint( $r->free_hours );
				?>
				<div class="ifs-repeater-row ifs-view-row-mode">
					<div>
						<label class="ifs-field-label"><?php esc_html_e( 'গ্রাহকের ধরণ', 'ifs-parking' ); ?></label>
						<span class="ifs-view-field-val"><?php echo esc_html( $r->customer_type ); ?></span>
					</div>

					<div>
						<label class="ifs-field-label"><?php esc_html_e( 'গাড়ির ধরণ', 'ifs-parking' ); ?></label>
						<span class="ifs-badge-dept"><?php echo esc_html( $veh_label ); ?></span>
					</div>

					<div>
						<label class="ifs-field-label"><?php esc_html_e( 'ঘণ্টা প্রতি', 'ifs-parking' ); ?></label>
						<strong class="ifs-rate-val-green">
							<?php echo esc_html( $currency_symbol . ifs_parking_cat_view_bn_num( number_format_i18n( (float) $r->hourly_rate, 2 ) ) ); ?>
						</strong>
					</div>

					<div>
						<label class="ifs-field-label"><?php esc_html_e( 'দৈনিক ফি', 'ifs-parking' ); ?></label>
						<strong class="ifs-rate-val-blue">
							<?php echo esc_html( $currency_symbol . ifs_parking_cat_view_bn_num( number_format_i18n( (float) $r->daily_rate, 2 ) ) ); ?>
						</strong>
					</div>

					<div>
						<label class="ifs-field-label"><?php esc_html_e( 'মাসিক ফি', 'ifs-parking' ); ?></label>
						<strong class="ifs-rate-val-slate">
							<?php echo esc_html( $currency_symbol . ifs_parking_cat_view_bn_num( number_format_i18n( (float) $r->monthly_rate, 2 ) ) ); ?>
						</strong>
					</div>

					<div>
						<label class="ifs-field-label"><?php esc_html_e( 'ফ্রি পার্কিং', 'ifs-parking' ); ?></label>
						<span class="ifs-view-field-val">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: ফ্রি ঘণ্টার সংখ্যা */
									__( '%s ঘণ্টা', 'ifs-parking' ),
									ifs_parking_cat_view_bn_num( $free_hours )
								)
							);
							?>
						</span>
					</div>

					<div class="ifs-repeater-action-col">
						<span class="ifs-badge-status-free" title="<?php esc_attr_e( 'সক্রিয়', 'ifs-parking' ); ?>">&#10003;</span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}