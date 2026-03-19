<?php
/**
 * Donations Report for the PMPro Reports dashboard.
 *
 * Provides a report widget, full report page with filtering,
 * and CSV export of donation data.
 *
 * @since 2.3
 */

/**
 * Register the Donations report with PMPro's report framework.
 *
 * @since 2.3
 *
 * @param array $reports Registered reports.
 * @return array Modified reports array.
 */
function pmpro_report_donations_register( $reports ) {
	$reports['donations'] = __( 'Donations', 'pmpro-donations' );
	return $reports;
}
add_filter( 'pmpro_registered_reports', 'pmpro_report_donations_register' );

/**
 * Register the AJAX handler for CSV export.
 *
 * @since 2.3
 */
add_action( 'wp_ajax_pmprodon_donations_csv', 'pmprodon_donations_csv_export' );

/**
 * Display the Donations report widget on the PMPro Reports dashboard.
 *
 * Shows donation count and total revenue for today, this month,
 * this year, and all time.
 *
 * @since 2.3
 */
function pmpro_report_donations_widget() {
	global $wpdb;

	// Build date boundaries.
	$today_start      = gmdate( 'Y-m-d 00:00:00', current_time( 'timestamp' ) );
	$month_start      = gmdate( 'Y-m-01 00:00:00', current_time( 'timestamp' ) );
	$year_start       = gmdate( 'Y-01-01 00:00:00', current_time( 'timestamp' ) );

	// Reusable base query parts.
	$base_from  = "FROM {$wpdb->pmpro_membership_orders} o";
	$base_join  = "INNER JOIN {$wpdb->pmpro_membership_ordermeta} om ON o.id = om.pmpro_membership_order_id";
	$base_where = "WHERE om.meta_key = 'donation_amount' AND CAST( om.meta_value AS DECIMAL(10,2) ) > 0 AND o.status = 'success'";

	// Today.
	$today_results = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT COUNT(*) AS donation_count, COALESCE( SUM( CAST( om.meta_value AS DECIMAL(10,2) ) ), 0 ) AS donation_total
			{$base_from} {$base_join} {$base_where} AND o.timestamp >= %s",
			$today_start
		)
	);

	// This month.
	$month_results = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT COUNT(*) AS donation_count, COALESCE( SUM( CAST( om.meta_value AS DECIMAL(10,2) ) ), 0 ) AS donation_total
			{$base_from} {$base_join} {$base_where} AND o.timestamp >= %s",
			$month_start
		)
	);

	// This year.
	$year_results = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT COUNT(*) AS donation_count, COALESCE( SUM( CAST( om.meta_value AS DECIMAL(10,2) ) ), 0 ) AS donation_total
			{$base_from} {$base_join} {$base_where} AND o.timestamp >= %s",
			$year_start
		)
	);

	// All time.
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- base parts contain no user input.
	$all_results = $wpdb->get_row(
		"SELECT COUNT(*) AS donation_count, COALESCE( SUM( CAST( om.meta_value AS DECIMAL(10,2) ) ), 0 ) AS donation_total
		{$base_from} {$base_join} {$base_where}"
	);

	?>
	<span id="pmpro_report_donations" class="pmpro_report-holder">
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Period', 'pmpro-donations' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Donations', 'pmpro-donations' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Revenue', 'pmpro-donations' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php esc_html_e( 'Today', 'pmpro-donations' ); ?></td>
					<td><?php echo esc_html( intval( $today_results->donation_count ) ); ?></td>
					<td><?php echo wp_kses_post( pmpro_formatPrice( $today_results->donation_total ) ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'This Month', 'pmpro-donations' ); ?></td>
					<td><?php echo esc_html( intval( $month_results->donation_count ) ); ?></td>
					<td><?php echo wp_kses_post( pmpro_formatPrice( $month_results->donation_total ) ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'This Year', 'pmpro-donations' ); ?></td>
					<td><?php echo esc_html( intval( $year_results->donation_count ) ); ?></td>
					<td><?php echo wp_kses_post( pmpro_formatPrice( $year_results->donation_total ) ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'All Time', 'pmpro-donations' ); ?></td>
					<td><?php echo esc_html( intval( $all_results->donation_count ) ); ?></td>
					<td><?php echo wp_kses_post( pmpro_formatPrice( $all_results->donation_total ) ); ?></td>
				</tr>
			</tbody>
		</table>
	</span>
	<?php
}

/**
 * Display the full Donations report page.
 *
 * Shows a month/year filter, summary stats, and a table of
 * individual donation entries. Includes a CSV export link.
 *
 * @since 2.3
 */
function pmpro_report_donations_page() {
	// Sanitize filter inputs.
	$month = isset( $_REQUEST['month'] ) ? intval( $_REQUEST['month'] ) : 0;
	$year  = isset( $_REQUEST['year'] ) ? intval( $_REQUEST['year'] ) : 0;

	// Get filtered donation data.
	$donations = pmprodon_get_donations_report_data( $month, $year );

	// Calculate summary stats.
	$total_amount = 0;
	$count        = count( $donations );
	foreach ( $donations as $donation ) {
		$total_amount += (float) $donation->donation_amount;
	}
	$average = $count > 0 ? $total_amount / $count : 0;

	// Build the CSV export URL.
	$csv_url = wp_nonce_url(
		admin_url( 'admin-ajax.php?action=pmprodon_donations_csv&month=' . intval( $month ) . '&year=' . intval( $year ) ),
		'pmprodon_donations_csv',
		'pmprodon_nonce'
	);

	?>
	<h1><?php esc_html_e( 'Donations Report', 'pmpro-donations' ); ?></h1>

	<form method="get" action="">
		<input type="hidden" name="page" value="pmpro-reports" />
		<input type="hidden" name="report" value="donations" />
		<?php
		// Month filter.
		?>
		<select name="month">
			<option value="0"><?php esc_html_e( 'All Months', 'pmpro-donations' ); ?></option>
			<?php
			for ( $i = 1; $i <= 12; $i++ ) {
				$month_label = wp_date( 'F', mktime( 0, 0, 0, $i, 1, 2000 ) );
				printf(
					'<option value="%d" %s>%s</option>',
					intval( $i ),
					selected( $month, $i, false ),
					esc_html( $month_label )
				);
			}
			?>
		</select>
		<?php
		// Year filter — show years from the earliest donation to the current year.
		$current_year = intval( gmdate( 'Y', current_time( 'timestamp' ) ) );
		$earliest_year = pmprodon_get_earliest_donation_year();
		?>
		<select name="year">
			<option value="0"><?php esc_html_e( 'All Years', 'pmpro-donations' ); ?></option>
			<?php
			for ( $y = $current_year; $y >= $earliest_year; $y-- ) {
				printf(
					'<option value="%d" %s>%d</option>',
					intval( $y ),
					selected( $year, $y, false ),
					intval( $y )
				);
			}
			?>
		</select>
		<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'pmpro-donations' ); ?>" />
		<a href="<?php echo esc_url( $csv_url ); ?>" class="button button-secondary" target="_blank">
			<?php esc_html_e( 'Export CSV', 'pmpro-donations' ); ?>
		</a>
	</form>

	<h2><?php esc_html_e( 'Summary', 'pmpro-donations' ); ?></h2>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Total Donations', 'pmpro-donations' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Count', 'pmpro-donations' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Average', 'pmpro-donations' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php echo wp_kses_post( pmpro_formatPrice( $total_amount ) ); ?></td>
				<td><?php echo esc_html( intval( $count ) ); ?></td>
				<td><?php echo wp_kses_post( pmpro_formatPrice( $average ) ); ?></td>
			</tr>
		</tbody>
	</table>

	<h2><?php esc_html_e( 'Donation Entries', 'pmpro-donations' ); ?></h2>
	<?php if ( empty( $donations ) ) { ?>
		<p><?php esc_html_e( 'No donations found for the selected period.', 'pmpro-donations' ); ?></p>
	<?php } else { ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Order ID', 'pmpro-donations' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Member', 'pmpro-donations' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Email', 'pmpro-donations' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Level', 'pmpro-donations' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Donation Amount', 'pmpro-donations' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Date', 'pmpro-donations' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $donations as $entry ) { ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-orders&order=' . intval( $entry->order_id ) ) ); ?>">
								<?php echo esc_html( intval( $entry->order_id ) ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $entry->display_name ); ?></td>
						<td><?php echo esc_html( $entry->user_email ); ?></td>
						<td><?php echo esc_html( $entry->level_name ); ?></td>
						<td><?php echo wp_kses_post( pmpro_formatPrice( $entry->donation_amount ) ); ?></td>
						<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $entry->order_date ) ) ); ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	<?php } ?>
	<?php
}

/**
 * Get the earliest year that has a donation order.
 *
 * Used to populate the year filter dropdown. Falls back to the
 * current year if no donations exist.
 *
 * @since 2.3
 *
 * @return int The earliest year with a donation.
 */
function pmprodon_get_earliest_donation_year() {
	global $wpdb;

	$earliest = $wpdb->get_var(
		"SELECT YEAR( MIN( o.timestamp ) )
		FROM {$wpdb->pmpro_membership_orders} o
		INNER JOIN {$wpdb->pmpro_membership_ordermeta} om ON o.id = om.pmpro_membership_order_id
		WHERE om.meta_key = 'donation_amount'
			AND CAST( om.meta_value AS DECIMAL(10,2) ) > 0
			AND o.status = 'success'"
	);

	if ( empty( $earliest ) ) {
		return intval( gmdate( 'Y', current_time( 'timestamp' ) ) );
	}

	return intval( $earliest );
}

/**
 * Get donation report data with optional month/year filtering.
 *
 * Shared query function used by both the report page and CSV export.
 * Joins orders, order meta, users, and membership levels.
 *
 * @since 2.3
 *
 * @param int $month Month number (1-12) to filter by, or 0 for all months.
 * @param int $year  Year to filter by, or 0 for all years.
 * @return array Array of donation entry objects.
 */
function pmprodon_get_donations_report_data( $month = 0, $year = 0 ) {
	global $wpdb;

	$where_clauses = array(
		"om.meta_key = 'donation_amount'",
		"CAST( om.meta_value AS DECIMAL(10,2) ) > 0",
		"o.status = 'success'",
	);
	$prepare_args = array();

	if ( ! empty( $month ) ) {
		$where_clauses[] = 'MONTH( o.timestamp ) = %d';
		$prepare_args[]  = intval( $month );
	}

	if ( ! empty( $year ) ) {
		$where_clauses[] = 'YEAR( o.timestamp ) = %d';
		$prepare_args[]  = intval( $year );
	}

	$where = implode( ' AND ', $where_clauses );

	$query = "SELECT o.id AS order_id,
			o.user_id,
			o.membership_id,
			o.total AS order_total,
			o.timestamp AS order_date,
			CAST( om.meta_value AS DECIMAL(10,2) ) AS donation_amount,
			u.display_name,
			u.user_email,
			ml.name AS level_name
		FROM {$wpdb->pmpro_membership_orders} o
		INNER JOIN {$wpdb->pmpro_membership_ordermeta} om ON o.id = om.pmpro_membership_order_id
		LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID
		LEFT JOIN {$wpdb->pmpro_membership_levels} ml ON o.membership_id = ml.id
		WHERE {$where}
		ORDER BY o.timestamp DESC";

	if ( ! empty( $prepare_args ) ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is built from safe table names and static strings; only $prepare_args are user-derived and passed to prepare().
		$query = $wpdb->prepare( $query, $prepare_args );
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above when user args exist; unprepared path has no user input.
	$results = $wpdb->get_results( $query );

	return ! empty( $results ) ? $results : array();
}

/**
 * Handle the AJAX request to export donations as CSV.
 *
 * Verifies nonce and capability before generating the CSV file.
 *
 * @since 2.3
 */
function pmprodon_donations_csv_export() {
	// Capability check.
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_reports' ) ) {
		wp_die( esc_html__( 'You do not have permission to export this report.', 'pmpro-donations' ) );
	}

	// Nonce check.
	check_admin_referer( 'pmprodon_donations_csv', 'pmprodon_nonce' );

	// Get filter params.
	$month = isset( $_REQUEST['month'] ) ? intval( $_REQUEST['month'] ) : 0;
	$year  = isset( $_REQUEST['year'] ) ? intval( $_REQUEST['year'] ) : 0;

	// Get the data.
	$donations = pmprodon_get_donations_report_data( $month, $year );

	// Build filename.
	$filename_parts = array( 'donations-report' );
	if ( ! empty( $year ) ) {
		$filename_parts[] = intval( $year );
	}
	if ( ! empty( $month ) ) {
		$filename_parts[] = str_pad( intval( $month ), 2, '0', STR_PAD_LEFT );
	}
	$filename = implode( '-', $filename_parts ) . '.csv';

	// Set headers.
	header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ) );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Cache-Control: no-cache, no-store, must-revalidate' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	// Open output stream.
	$output = fopen( 'php://output', 'w' );

	// Write header row.
	fputcsv( $output, array(
		__( 'Order ID', 'pmpro-donations' ),
		__( 'User ID', 'pmpro-donations' ),
		__( 'Member Name', 'pmpro-donations' ),
		__( 'Email', 'pmpro-donations' ),
		__( 'Level', 'pmpro-donations' ),
		__( 'Donation Amount', 'pmpro-donations' ),
		__( 'Order Total', 'pmpro-donations' ),
		__( 'Date', 'pmpro-donations' ),
	) );

	// Write data rows.
	foreach ( $donations as $entry ) {
		fputcsv( $output, array(
			intval( $entry->order_id ),
			intval( $entry->user_id ),
			$entry->display_name,
			$entry->user_email,
			$entry->level_name,
			$entry->donation_amount,
			$entry->order_total,
			wp_date( 'Y-m-d', strtotime( $entry->order_date ) ),
		) );
	}

	fclose( $output );
	exit;
}
