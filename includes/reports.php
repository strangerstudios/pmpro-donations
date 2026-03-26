<?php
// Donation report.

/**
 * Enqueue Google Charts corechart library on the donations report page.
 */
function pmprodon_report_donations_enqueue_scripts() {
	if ( ! isset( $_REQUEST['page'], $_REQUEST['report'] ) || 'pmpro-reports' !== $_REQUEST['page'] || 'donations' !== $_REQUEST['report'] ) {
		return;
	}
	if ( ! wp_script_is( 'corechart', 'enqueued' ) ) {
		wp_enqueue_script( 'corechart', PMPRO_URL . '/js/corechart.js' );
	}
}
add_action( 'admin_enqueue_scripts', 'pmprodon_report_donations_enqueue_scripts' );

/**
 * Register the donations report with PMPro.
 *
 * @param array $reports Existing PMPro reports.
 * @return array Modified array with our custom report added.
 */
function pmprodon_register_donations_report( $reports ) {
	$reports['donations'] = __( 'Donations Received', 'pmpro-donations' );
	return $reports;
}
add_filter( 'pmpro_registered_reports', 'pmprodon_register_donations_report' );

/**
 * Get donation count and total for a named period.
 *
 * @param string $period One of 'today', 'this month', 'this year', 'all time'.
 * @return object Object with ->count and ->total properties.
 */
function pmprodon_get_donations_for_period( $period ) {
	global $wpdb;

	$sqlQuery = "SELECT COUNT(*) as count, COALESCE(SUM(om.meta_value), 0) as total
		FROM $wpdb->pmpro_membership_ordermeta om
		JOIN $wpdb->pmpro_membership_orders o ON om.pmpro_membership_order_id = o.id
		WHERE om.meta_key = 'donation_amount' AND om.meta_value > 0
		AND o.status NOT IN('refunded', 'review', 'token', 'error')";
	
	if ( 'today' === $period ) {
		$sqlQuery .= " AND DATE(o.timestamp) = CURDATE()";
	} elseif ( 'this month' === $period ) {
		$sqlQuery .= " AND MONTH(o.timestamp) = MONTH(NOW()) AND YEAR(o.timestamp) = YEAR(NOW())";
	} elseif ( 'this year' === $period ) {
		$sqlQuery .= " AND YEAR(o.timestamp) = YEAR(NOW())";
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No user input is interpolated; values are hardcoded or sanitized above.
	$row = $wpdb->get_row(
		$sqlQuery
	);

	return $row ? $row : (object) array( 'count' => 0, 'total' => 0 );
}

/**
 * Get donation data grouped by day, month, or year for charting.
 *
 * @param array $args {
 *   @type string $report_unit 'DAY', 'MONTH', or 'YEAR'.
 *   @type string $startdate   Start date (Y-m-d).
 *   @type string $enddate     End date (Y-m-d or Y-m-d H:i:s).
 * }
 * @return array Array of objects with date, count, and value properties.
 */
function pmprodon_get_donations_chart_data( $args ) {
	global $wpdb;

	$report_unit = ! empty( $args['report_unit'] ) ? $args['report_unit'] : 'MONTH';
	$startdate   = ! empty( $args['startdate'] ) ? $args['startdate'] : '';
	$enddate     = ! empty( $args['enddate'] ) ? $args['enddate'] : '';

	// Match the sales report: calculate a timezone offset so date grouping respects
	// the site's local timezone rather than the DB's UTC-stored timestamps.
	$tz_offset = $startdate ? strtotime( $startdate ) - strtotime( get_gmt_from_date( $startdate . ' 00:00:00' ) ) : 0;

	if ( 'DAY' === $report_unit ) {
		$date_expr = "DATE( DATE_ADD( o.timestamp, INTERVAL " . esc_sql( $tz_offset ) . " SECOND ) )";
	} elseif ( 'MONTH' === $report_unit ) {
		$date_expr = "DATE_FORMAT( DATE_ADD( o.timestamp, INTERVAL " . esc_sql( $tz_offset ) . " SECOND ), '%Y-%m' )";
	} else {
		$date_expr = "YEAR( DATE_ADD( o.timestamp, INTERVAL " . esc_sql( $tz_offset ) . " SECOND ) )";
	}

	$where = "WHERE om.meta_key = 'donation_amount' AND om.meta_value > 0
		AND o.status NOT IN('refunded', 'review', 'token', 'error')";

	if ( $startdate ) {
		$where .= $wpdb->prepare( " AND o.timestamp >= DATE_ADD( %s, INTERVAL -%d SECOND )", $startdate, $tz_offset );
	}
	if ( $enddate ) {
		$end    = strlen( $enddate ) === 10 ? $enddate . ' 23:59:59' : $enddate;
		$where .= $wpdb->prepare( " AND o.timestamp <= DATE_ADD( %s, INTERVAL -%d SECOND )", $end, $tz_offset );
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return $wpdb->get_results(
		"SELECT {$date_expr} as date, COUNT(*) as count, SUM(om.meta_value) as value
		FROM $wpdb->pmpro_membership_ordermeta om
		JOIN $wpdb->pmpro_membership_orders o ON om.pmpro_membership_order_id = o.id
		{$where}
		GROUP BY {$date_expr}
		ORDER BY {$date_expr}"
	);
}

/**
 * Resolve a period + month/year/custom dates into a startdate and enddate string pair.
 *
 * @param string $period            Period key (daily, monthly, annual, 7days, 30days, 12months, custom).
 * @param int    $month             Month number (1–12).
 * @param int    $year              Four-digit year.
 * @param string $custom_start_date Custom start date (Y-m-d), used only when period is 'custom'.
 * @param string $custom_end_date   Custom end date (Y-m-d), used only when period is 'custom'.
 * @return array { startdate: string, enddate: string }
 */
function pmprodon_resolve_date_range( $period, $month, $year, $custom_start_date = '', $custom_end_date = '' ) {
	if ( 'daily' === $period ) {
		$startdate = $year . '-' . str_pad( $month, 2, '0', STR_PAD_LEFT ) . '-01';
		$enddate   = $year . '-' . str_pad( $month, 2, '0', STR_PAD_LEFT ) . '-' . date_i18n( 't', strtotime( $startdate ) );
	} elseif ( 'monthly' === $period ) {
		$startdate = $year . '-01-01';
		$enddate   = $year . '-12-' . date_i18n( 't', strtotime( $year . '-12-01' ) );
	} elseif ( '7days' === $period || '30days' === $period ) {
		$timeframe = ( '7days' === $period ) ? 7 : 30;
		$startdate = date( 'Y-m-d', strtotime( current_time( 'mysql' ) . ' -' . $timeframe . ' DAY' ) );
		$enddate   = current_time( 'mysql' );
	} elseif ( '12months' === $period ) {
		$startdate = date( 'Y-m-01', strtotime( current_time( 'mysql' ) . ' -12 month' ) );
		$enddate   = date( 'Y-m-t', strtotime( current_time( 'mysql' ) . ' -1 month' ) );
	} elseif ( 'custom' === $period ) {
		$startdate = $custom_start_date;
		$enddate   = $custom_end_date;
	} else {
		// Annual / all time.
		$startdate = '1970-01-01';
		$enddate   = current_time( 'mysql' );
	}

	return array( 'startdate' => $startdate, 'enddate' => $enddate );
}

/**
 * Fetch all individual donations within a date range including member details.
 *
 * @param string|null $startdate Start date (Y-m-d or Y-m-d H:i:s), or null for no lower bound.
 * @param string|null $enddate   End date (Y-m-d or Y-m-d H:i:s), or null for no upper bound.
 * @return array Array of donation objects with member details.
 */
function pmprodon_get_donations( $startdate = null, $enddate = null ) {
	global $wpdb;

	$where = "WHERE om.meta_key = 'donation_amount' AND om.meta_value > 0
		AND o.status NOT IN('refunded', 'review', 'token', 'error')";

	if ( $startdate ) {
		$where .= $wpdb->prepare( ' AND o.timestamp >= %s', $startdate );
	}
	if ( $enddate ) {
		$end    = strlen( $enddate ) === 10 ? $enddate . ' 23:59:59' : $enddate;
		$where .= $wpdb->prepare( ' AND o.timestamp <= %s', $end );
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return $wpdb->get_results(
		"SELECT om.meta_value, o.timestamp, o.user_id, u.user_login,
		        um1.meta_value as first_name, um2.meta_value as last_name
		FROM {$wpdb->prefix}pmpro_membership_ordermeta om
		JOIN {$wpdb->prefix}pmpro_membership_orders o ON om.pmpro_membership_order_id = o.id
		LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID
		LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
		LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
		{$where}
		ORDER BY o.timestamp DESC"
	);
}

/**
 * Handle CSV export of individual donation entries.
 */
function pmprodon_donations_csv_export() {
	if ( ! isset( $_GET['report'] ) || 'donations' !== $_GET['report'] || ! isset( $_GET['export'] ) || 'csv' !== $_GET['export'] ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_memberships_menu' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'pmpro-donations' ) );
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'pmprodon_export_csv' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'pmpro-donations' ) );
	}

	$period           = isset( $_GET['period'] ) ? sanitize_text_field( $_GET['period'] ) : 'annual';
	$month            = isset( $_GET['month'] ) ? intval( $_GET['month'] ) : (int) date_i18n( 'n', current_time( 'timestamp' ) );
	$year             = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : (int) date_i18n( 'Y', current_time( 'timestamp' ) );
	$custom_start     = isset( $_GET['custom_start_date'] ) ? sanitize_text_field( $_GET['custom_start_date'] ) : '';
	$custom_end       = isset( $_GET['custom_end_date'] ) ? sanitize_text_field( $_GET['custom_end_date'] ) : '';

	$date_range = pmprodon_resolve_date_range( $period, $month, $year, $custom_start, $custom_end );
	$donations  = pmprodon_get_donations( $date_range['startdate'], $date_range['enddate'] );

	header( 'Content-Type: text/csv' );
	header( 'Content-Disposition: attachment; filename="pmpro-donations-' . date( 'Y-m-d' ) . '.csv"' );

	$output = fopen( 'php://output', 'w' );

	fputcsv(
		$output,
		array(
			__( 'Member ID', 'pmpro-donations' ),
			__( 'Username', 'pmpro-donations' ),
			__( 'First Name', 'pmpro-donations' ),
			__( 'Last Name', 'pmpro-donations' ),
			__( 'Amount', 'pmpro-donations' ),
			__( 'Date', 'pmpro-donations' ),
		)
	);

	foreach ( $donations as $donation ) {
		fputcsv(
			$output,
			array(
				$donation->user_id,
				$donation->user_login,
				$donation->first_name,
				$donation->last_name,
				$donation->meta_value,
				$donation->timestamp,
			)
		);
	}

	fclose( $output );
	exit();
}
add_action( 'admin_init', 'pmprodon_donations_csv_export' );

/**
 * Get the top donation amounts paid for a given period, keyed by amount.
 *
 * Returns an array like [ 25.00 => [ 'total' => 4 ], 10.00 => [ 'total' => 7 ] ]
 * sorted descending by amount.
 *
 * @param string   $period One of 'today', 'this month', 'this year', 'all time'.
 * @param int|null $count  Maximum number of amounts to return.
 * @return array
 */
function pmprodon_get_donation_amounts_paid( $period, $count = null ) {
	global $wpdb;

	if ( 'today' === $period ) {
		$startdate = date_i18n( 'Y-m-d', current_time( 'timestamp' ) );
	} elseif ( 'this month' === $period ) {
		$startdate = date_i18n( 'Y-m', current_time( 'timestamp' ) ) . '-01';
	} elseif ( 'this year' === $period ) {
		$startdate = date_i18n( 'Y', current_time( 'timestamp' ) ) . '-01-01';
	} else {
		$startdate = '1970-01-01';
	}

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ROUND(om.meta_value, 8) as amount, COUNT(*) as num
			FROM {$wpdb->prefix}pmpro_membership_ordermeta om
			JOIN {$wpdb->prefix}pmpro_membership_orders o ON om.pmpro_membership_order_id = o.id
			WHERE om.meta_key = 'donation_amount' AND om.meta_value > 0
			AND o.status NOT IN('refunded', 'review', 'token', 'error')
			AND o.timestamp >= %s
			GROUP BY amount
			ORDER BY num DESC",
			$startdate
		)
	);

	if ( ! empty( $count ) ) {
		$rows = array_slice( $rows, 0, $count, true );
	}

	$amounts = array();
	foreach ( $rows as $row ) {
		$amounts[ $row->amount ] = array( 'total' => $row->num );
	}

	krsort( $amounts );

	return $amounts;
}

/**
 * Donations Report widget for the PMPro Reports dashboard.
 *
 * Mirrors the Sales and Revenue widget: period rows with expandable
 * sub-rows breaking down donations by amount.
 */
function pmpro_report_donations_widget() {
	global $wpdb, $pmpro_reports;

	$periods = array(
		'today'      => __( 'Today', 'pmpro-donations' ),
		'this month' => __( 'This Month', 'pmpro-donations' ),
		'this year'  => __( 'This Year', 'pmpro-donations' ),
		'all time'   => __( 'All Time', 'pmpro-donations' ),
	);
?>
<style>
#pmpro_report_donations .wp-list-table tbody:nth-child(odd) th,
#pmpro_report_donations .wp-list-table tbody:nth-child(odd) td { background-color: #fff; }
#pmpro_report_donations .wp-list-table tbody:nth-child(even) th,
#pmpro_report_donations .wp-list-table tbody:nth-child(even) td { background-color: var(--pmpro--color--blue-lightest); }
</style>
<span id="pmpro_report_donations" class="pmpro_report-holder">
	<table class="wp-list-table widefat fixed">
	<thead>
		<tr>
			<th scope="col"><?php esc_html_e( 'Period', 'pmpro-donations' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Donations', 'pmpro-donations' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Amount', 'pmpro-donations' ); ?></th>
		</tr>
	</thead>
	<?php
		foreach ( $periods as $period_key => $period_label ) {
			$count            = 0;
			$max_amounts      = apply_filters( 'pmprodon_widget_max_donation_amounts', 5 );
			$amounts          = pmprodon_get_donation_amounts_paid( $period_key, $max_amounts );
			$data             = pmprodon_get_donations_for_period( $period_key );
			?>
			<tbody>
				<tr class="pmpro_report_tr">
					<td>
						<?php if ( ! empty( $amounts ) ) { ?>
							<button aria-label="<?php echo esc_attr( sprintf( __( 'Toggle donations by amount for %s', 'pmpro-donations' ), $period_label ) ); ?>" class="pmpro_report_th pmpro_report_th_closed"><?php echo esc_html( $period_label ); ?></button>
						<?php } else { ?>
							<?php echo esc_html( $period_label ); ?>
						<?php } ?>
					</td>
					<td><?php echo esc_html( number_format_i18n( intval( $data->count ) ) ); ?></td>
					<td><?php echo pmpro_escape_price( pmpro_formatPrice( $data->total ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
				</tr>
				<?php
				foreach ( $amounts as $amount => $quantity ) {
					if ( $count++ >= $max_amounts ) {
						break;
					}
				?>
					<tr class="pmpro_report_tr_sub" style="display: none;">
						<td aria-label="<?php echo esc_attr( sprintf( __( 'Donations of %s for %s', 'pmpro-donations' ), pmpro_formatPrice( $amount ), $period_label ) ); ?>">- <?php echo pmpro_escape_price( pmpro_formatPrice( $amount ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
						<td><?php echo esc_html( number_format_i18n( $quantity['total'] ) ); ?></td>
						<td><?php echo pmpro_escape_price( pmpro_formatPrice( $amount * $quantity['total'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					</tr>
				<?php } ?>
			</tbody>
			<?php
		}
	?>
	</table>
	<?php if ( function_exists( 'pmpro_report_donations_page' ) ) { ?>
		<p class="pmpro_report-button">
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-reports&report=donations' ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'View the full %s report', 'pmpro-donations' ), $pmpro_reports['donations'] ) ); ?>"><?php esc_html_e( 'Details', 'pmpro-donations' ); ?></a>
		</p>
	<?php } ?>
</span>
<?php
}

/**
 * Donations Report page with chart and data table.
 */
function pmpro_report_donations_page() {
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_memberships_menu' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'pmpro-donations' ) );
	}

	// Get form values.
	$period   = isset( $_REQUEST['period'] ) ? sanitize_text_field( $_REQUEST['period'] ) : 'monthly';
	$thisyear = (int) date_i18n( 'Y', current_time( 'timestamp' ) );
	$month    = isset( $_REQUEST['month'] ) ? intval( $_REQUEST['month'] ) : (int) date_i18n( 'n', current_time( 'timestamp' ) );
	$year     = isset( $_REQUEST['year'] ) ? intval( $_REQUEST['year'] ) : $thisyear;

	// Resolve date range via shared helper (also used by CSV export).
	$custom_start = isset( $_REQUEST['custom_start_date'] ) ? sanitize_text_field( $_REQUEST['custom_start_date'] ) : '';
	$custom_end   = isset( $_REQUEST['custom_end_date'] ) ? sanitize_text_field( $_REQUEST['custom_end_date'] ) : '';
	$date_range   = pmprodon_resolve_date_range( $period, $month, $year, $custom_start, $custom_end );
	$startdate    = $date_range['startdate'];
	$enddate      = $date_range['enddate'];

	// Chart-specific display settings per period.
	if ( 'daily' === $period ) {
		$report_unit         = 'DAY';
		$axis_date_format    = 'd';
		$tooltip_date_format = get_option( 'date_format' );
	} elseif ( in_array( $period, array( 'monthly', '12months' ), true ) ) {
		$report_unit         = 'MONTH';
		$axis_date_format    = 'M';
		$tooltip_date_format = 'F Y';
	} elseif ( in_array( $period, array( '7days', '30days', 'custom' ), true ) ) {
		$report_unit         = 'DAY';
		$axis_date_format    = 'd';
		$tooltip_date_format = get_option( 'date_format' );
	} else {
		// Annual (all time).
		$period              = 'annual';
		$report_unit         = 'YEAR';
		$axis_date_format    = 'Y';
		$tooltip_date_format = 'Y';
	}

	// Fetch and key the raw chart data by date.
	$raw = pmprodon_get_donations_chart_data( array(
		'report_unit' => $report_unit,
		'startdate'   => $startdate,
		'enddate'     => $enddate,
	) );
	$dates = array_combine( wp_list_pluck( $raw, 'date' ), $raw );

	// Fill gaps, accumulate totals for averaging.
	$tabledata       = array();
	$total_in_period = 0;
	$units_in_period = 0;

	if ( 'DAY' === $report_unit ) {
		$loop = strtotime( $startdate );
		$end  = strtotime( $enddate );
		while ( $loop <= $end ) {
			$d = date( 'Y-m-d', $loop );
			if ( ! isset( $dates[ $d ] ) ) {
				$dates[ $d ] = (object) array( 'date' => $d, 'count' => 0, 'value' => 0 );
			}
			if ( $d <= date( 'Y-m-d' ) ) {
				$total_in_period += $dates[ $d ]->value;
				$units_in_period++;
			}
			$tabledata[ $d ] = (object) array( 'date' => $d, 'count' => $dates[ $d ]->count, 'total' => $dates[ $d ]->value );
			$loop = strtotime( '+1 day', $loop );
		}
	} elseif ( 'MONTH' === $report_unit ) {
		$loop = strtotime( $startdate );
		$end  = strtotime( $enddate );
		while ( $loop <= $end ) {
			$d = date( 'Y-m', $loop );
			if ( ! isset( $dates[ $d ] ) ) {
				$dates[ $d ] = (object) array( 'date' => $d, 'count' => 0, 'value' => 0 );
			}
			if ( $d <= date( 'Y-m' ) ) {
				$total_in_period += $dates[ $d ]->value;
				$units_in_period++;
			}
			$tabledata[ $d ] = (object) array( 'date' => $d, 'count' => $dates[ $d ]->count, 'total' => $dates[ $d ]->value );
			$loop = strtotime( '+1 month', $loop );
		}
	} else {
		// YEAR.
		$start_year = ! empty( $dates ) ? (int) min( array_keys( $dates ) ) : $thisyear;
		$end_year   = $thisyear;
		for ( $y = $start_year; $y <= $end_year; $y++ ) {
			if ( ! isset( $dates[ $y ] ) ) {
				$dates[ $y ] = (object) array( 'date' => $y, 'count' => 0, 'value' => 0 );
			}
			$total_in_period += $dates[ $y ]->value;
			$units_in_period++;
			$tabledata[ $y ] = (object) array( 'date' => $y, 'count' => $dates[ $y ]->count, 'total' => $dates[ $y ]->value );
		}
	}

	ksort( $dates );

	$average = ( $units_in_period > 0 ) ? $total_in_period / $units_in_period : 0;

	// Build Google Chart data.
	$google_chart_column_labels = array(
		__( 'Donation Amount', 'pmpro-donations' ),
		__( 'Average Amount', 'pmpro-donations' ),
	);
	$google_chart_series_styles = array(
		array( 'color' => '#31825D' ),
		array(
			'type'                => 'line',
			'color'               => '#B00000',
			'enableInteractivity' => false,
			'lineDashStyle'       => array( 4, 1 ),
		),
	);
	$google_chart_row_data = array();
	foreach ( $dates as $date => $data ) {
		$axis_label    = is_numeric( $date ) ? (string) $date : date_i18n( $axis_date_format, strtotime( $date ) );
		$tooltip_label = is_numeric( $date ) ? (string) $date : date_i18n( $tooltip_date_format, strtotime( $date ) );
		$tooltip  = '<div style="padding:15px; font-size: 14px; line-height: 20px; color: #000000;">';
		$tooltip .= '<strong>' . esc_html( $tooltip_label ) . '</strong><br/>';
		$tooltip .= '<ul style="margin-bottom: 0px;">';
		$tooltip .= '<li>' . sprintf( esc_html__( 'Donations: %d', 'pmpro-donations' ), intval( $data->count ) ) . '</li>';
		$tooltip .= '<li>' . sprintf( esc_html__( 'Amount: %s', 'pmpro-donations' ), pmpro_formatPrice( $data->value ) ) . '</li>';
		$tooltip .= '</ul></div>';
		$google_chart_row_data[ $date ] = array(
			'date'    => $axis_label,
			'tooltip' => $tooltip,
			'data'    => array( floatval( $data->value ), $average ),
		);
	}

	// Build chart title.
	if ( 'daily' === $period && ! empty( $month ) ) {
		$report_date = date_i18n( 'F', mktime( 0, 0, 0, $month, 2 ) ) . ' ' . $year;
	} elseif ( 'monthly' === $period ) {
		$report_date = $year;
	} elseif ( 'annual' === $period ) {
		$report_date = __( 'All Time', 'pmpro-donations' );
	} else {
		$report_date = '';
	}

	$period_labels = array(
		'daily'    => __( 'Daily', 'pmpro-donations' ),
		'monthly'  => __( 'Monthly', 'pmpro-donations' ),
		'annual'   => __( 'Annual', 'pmpro-donations' ),
		'7days'    => __( 'Last 7 Days', 'pmpro-donations' ),
		'30days'   => __( 'Last 30 Days', 'pmpro-donations' ),
		'12months' => __( 'Last 12 Months', 'pmpro-donations' ),
		'custom'   => __( 'Custom', 'pmpro-donations' ),
	);
	$period_label = isset( $period_labels[ $period ] ) ? $period_labels[ $period ] : ucwords( $period );

	if ( $report_date ) {
		$chart_title = sprintf( __( '%1$s Donations for %2$s', 'pmpro-donations' ), $period_label, $report_date );
	} else {
		$chart_title = sprintf( __( '%s Donations', 'pmpro-donations' ), $period_label );
	}

	// CSV export URL — passes all active filter params so the export matches the current view.
	$csv_args = array(
		'export'            => 'csv',
		'period'            => $period,
		'month'             => $month,
		'year'              => $year,
		'custom_start_date' => 'custom' === $period ? $startdate : '',
		'custom_end_date'   => 'custom' === $period ? substr( $enddate, 0, 10 ) : '',
	);
	$csv_url = wp_nonce_url( add_query_arg( $csv_args ), 'pmprodon_export_csv' );
	?>
	<form id="posts-filter" method="get" action="">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Donations Received', 'pmpro-donations' ); ?></h1>
		<a target="_blank" href="<?php echo esc_url( $csv_url ); ?>" class="page-title-action"><?php esc_html_e( 'Export to CSV', 'pmpro-donations' ); ?></a>

		<div class="pmpro_report-filters">
			<h3><?php esc_html_e( 'Customize Report', 'pmpro-donations' ); ?></h3>
			<div class="tablenav top">
				<span class="pmpro_report-filter-text"><?php esc_html_e( 'Show', 'pmpro-donations' ); ?></span>
				<label for="pmprodon-period" class="screen-reader-text"><?php esc_html_e( 'Select report time period', 'pmpro-donations' ); ?></label>
				<select id="pmprodon-period" name="period">
					<option value="daily"    <?php selected( $period, 'daily' ); ?>><?php esc_html_e( 'Daily', 'pmpro-donations' ); ?></option>
					<option value="monthly"  <?php selected( $period, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'pmpro-donations' ); ?></option>
					<option value="annual"   <?php selected( $period, 'annual' ); ?>><?php esc_html_e( 'Annual', 'pmpro-donations' ); ?></option>
					<option value="7days"    <?php selected( $period, '7days' ); ?>><?php esc_html_e( 'Last 7 Days', 'pmpro-donations' ); ?></option>
					<option value="30days"   <?php selected( $period, '30days' ); ?>><?php esc_html_e( 'Last 30 Days', 'pmpro-donations' ); ?></option>
					<option value="12months" <?php selected( $period, '12months' ); ?>><?php esc_html_e( 'Last 12 Months', 'pmpro-donations' ); ?></option>
					<option value="custom"   <?php selected( $period, 'custom' ); ?>><?php esc_html_e( 'Custom Range', 'pmpro-donations' ); ?></option>
				</select>

				<span id="pmprodon-for-label" class="pmpro_report-filter-text"><?php esc_html_e( 'for', 'pmpro-donations' ); ?></span>

				<label for="pmprodon-month" class="screen-reader-text"><?php esc_html_e( 'Select report month', 'pmpro-donations' ); ?></label>
				<select id="pmprodon-month" name="month">
					<?php for ( $i = 1; $i <= 12; $i++ ) { ?>
						<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $month, $i ); ?>><?php echo esc_html( date_i18n( 'F', mktime( 0, 0, 0, $i, 2 ) ) ); ?></option>
					<?php } ?>
				</select>

				<label for="pmprodon-year" class="screen-reader-text"><?php esc_html_e( 'Select report year', 'pmpro-donations' ); ?></label>
				<select id="pmprodon-year" name="year">
					<?php for ( $i = $thisyear; $i > 2007; $i-- ) { ?>
						<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $year, $i ); ?>><?php echo esc_html( $i ); ?></option>
					<?php } ?>
				</select>

				<span class="pmpro_report-filter-text pmprodon-custom"><?php esc_html_e( 'from', 'pmpro-donations' ); ?></span>
				<label for="pmprodon-custom-start" class="screen-reader-text"><?php esc_html_e( 'Start date', 'pmpro-donations' ); ?></label>
				<input type="date" id="pmprodon-custom-start" name="custom_start_date" class="pmprodon-custom" value="<?php echo 'custom' === $period ? esc_attr( $startdate ) : ''; ?>" />
				<span class="pmpro_report-filter-text pmprodon-custom"><?php esc_html_e( 'to', 'pmpro-donations' ); ?></span>
				<label for="pmprodon-custom-end" class="screen-reader-text"><?php esc_html_e( 'End date', 'pmpro-donations' ); ?></label>
				<input type="date" id="pmprodon-custom-end" name="custom_end_date" class="pmprodon-custom" value="<?php echo 'custom' === $period ? esc_attr( substr( $enddate, 0, 10 ) ) : ''; ?>" />
			</div>
			<input type="hidden" name="page" value="pmpro-reports" />
			<input type="hidden" name="report" value="donations" />
			<input type="submit" class="button button-primary action" value="<?php esc_attr_e( 'Generate Report', 'pmpro-donations' ); ?>" />
		</div>

		<div class="pmpro_chart_area">
			<div id="chart_div"></div>
			<div class="pmpro_chart_description">
				<p style="text-align: center;"><em><?php esc_html_e( 'Average line calculated using data prior to current day, month, or year.', 'pmpro-donations' ); ?></em></p>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#pmprodon-period').on('change', function() {
				pmprodon_showPeriodFields();
			});
			pmprodon_showPeriodFields();
		});

		function pmprodon_showPeriodFields() {
			var period = jQuery('#pmprodon-period').val();
			if ( period === 'daily' ) {
				jQuery('#pmprodon-for-label').show();
				jQuery('#pmprodon-month').show().prop('disabled', false);
				jQuery('#pmprodon-year').show().prop('disabled', false);
				jQuery('.pmprodon-custom').hide();
				jQuery('#pmprodon-custom-start, #pmprodon-custom-end').prop('disabled', true);
			} else if ( period === 'monthly' ) {
				jQuery('#pmprodon-for-label').show();
				jQuery('#pmprodon-month').hide().prop('disabled', true);
				jQuery('#pmprodon-year').show().prop('disabled', false);
				jQuery('.pmprodon-custom').hide();
				jQuery('#pmprodon-custom-start, #pmprodon-custom-end').prop('disabled', true);
			} else if ( period === 'custom' ) {
				jQuery('#pmprodon-for-label').hide();
				jQuery('#pmprodon-month').hide().prop('disabled', true);
				jQuery('#pmprodon-year').hide().prop('disabled', true);
				jQuery('.pmprodon-custom').show();
				jQuery('#pmprodon-custom-start, #pmprodon-custom-end').prop('disabled', false);
			} else {
				jQuery('#pmprodon-for-label').hide();
				jQuery('#pmprodon-month').hide().prop('disabled', true);
				jQuery('#pmprodon-year').hide().prop('disabled', true);
				jQuery('.pmprodon-custom').hide();
				jQuery('#pmprodon-custom-start, #pmprodon-custom-end').prop('disabled', true);
			}
		}

		google.charts.load('current', {'packages': ['corechart']});
		google.charts.setOnLoadCallback(pmprodon_drawChart);

		function pmprodon_drawChart() {
			var dataTable = new google.visualization.DataTable();

			dataTable.addColumn('string', <?php echo wp_json_encode( esc_html( $report_unit ) ); ?>);
			dataTable.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});

			<?php foreach ( $google_chart_column_labels as $label ) { ?>
				dataTable.addColumn('number', <?php echo wp_json_encode( esc_html( $label ) ); ?>);
			<?php } ?>

			dataTable.addRows([
				<?php foreach ( $google_chart_row_data as $row ) { ?>
					[
						<?php echo wp_json_encode( esc_html( $row['date'] ) ); ?>,
						<?php echo wp_json_encode( wp_kses( $row['tooltip'], 'post' ) ); ?>,
						<?php echo esc_html( implode( ',', $row['data'] ) ) . ','; ?>
					],
				<?php } ?>
			]);

			var options = {
				title: <?php echo wp_json_encode( esc_html( $chart_title ) ); ?>,
				titlePosition: 'top',
				titleTextStyle: { color: '#555555' },
				legend: { position: 'bottom' },
				chartArea: { width: '90%' },
				focusTarget: 'category',
				tooltip: { isHtml: true },
				hAxis: {
					textStyle: { color: '#555555', fontSize: '12', italic: false }
				},
				vAxis: {
					textStyle: { color: '#555555', fontSize: '12', italic: false }
				},
				seriesType: 'bars',
				series: <?php echo wp_json_encode( $google_chart_series_styles ); ?>,
			};

			var chart = new google.visualization.ColumnChart( document.getElementById('chart_div') );
			var view  = new google.visualization.DataView( dataTable );
			chart.draw( view, options );
		}
		</script>
	</form>

	<div class="pmpro_table_area">
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'pmpro-donations' ); ?></th>
					<th><?php esc_html_e( 'Donations', 'pmpro-donations' ); ?></th>
					<th><?php esc_html_e( 'Amount', 'pmpro-donations' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $tabledata as $row ) {
					$row_date = is_numeric( $row->date ) ? $row->date : date_i18n( $tooltip_date_format, strtotime( $row->date ) );
					?>
					<tr>
						<th scope="row"><?php echo esc_html( $row_date ); ?></th>
						<td><?php echo esc_html( number_format_i18n( intval( $row->count ) ) ); ?></td>
						<td><?php echo pmpro_escape_price( pmpro_formatPrice( $row->total ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					</tr>
				<?php } ?>
			</tbody>
			<tfoot>
				<tr>
					<th scope="row"><?php esc_html_e( 'Total', 'pmpro-donations' ); ?></th>
					<th><?php echo esc_html( number_format_i18n( array_sum( wp_list_pluck( $tabledata, 'count' ) ) ) ); ?></th>
					<th><?php echo pmpro_escape_price( pmpro_formatPrice( array_sum( wp_list_pluck( $tabledata, 'total' ) ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></th>
				</tr>
			</tfoot>
		</table>
	</div>
	<?php
}
