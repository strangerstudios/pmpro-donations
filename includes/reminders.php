<?php
/**
 * Donation Reminder Emails.
 *
 * Handles scheduling and sending periodic donation reminder emails
 * to members on levels with reminders enabled, and provides an
 * opt-out mechanism via secure tokenized URLs.
 *
 * @since 2.3
 */

/**
 * Map reminder interval names to their duration in seconds.
 *
 * @since 2.3
 *
 * @param string $interval The interval name (monthly, quarterly, annually).
 * @return int Number of seconds for the interval, or 0 if invalid.
 */
function pmprodon_get_interval_seconds( $interval ) {
	$intervals = array(
		'monthly'   => 30 * DAY_IN_SECONDS,
		'quarterly' => 90 * DAY_IN_SECONDS,
		'annually'  => 365 * DAY_IN_SECONDS,
	);

	return isset( $intervals[ $interval ] ) ? $intervals[ $interval ] : 0;
}

/**
 * Generate a secure opt-out token for a user.
 *
 * Uses wp_hash() with the user ID and a static salt to produce
 * an unforgeable, deterministic token.
 *
 * @since 2.3
 *
 * @param int $user_id The user ID.
 * @return string The opt-out token.
 */
function pmprodon_get_optout_token( $user_id ) {
	return wp_hash( 'pmprodon_optout_' . intval( $user_id ) );
}

/**
 * Build the opt-out URL for a user.
 *
 * @since 2.3
 *
 * @param int $user_id The user ID.
 * @return string The full opt-out URL.
 */
function pmprodon_get_optout_url( $user_id ) {
	return add_query_arg(
		array(
			'pmprodon_optout' => '1',
			'uid'             => intval( $user_id ),
			'token'           => pmprodon_get_optout_token( $user_id ),
		),
		home_url()
	);
}

/**
 * Handle the opt-out request on init.
 *
 * Validates the token, sets the opt-out user meta, and redirects
 * to the home page with a success message stored in a transient.
 *
 * @since 2.3
 */
function pmprodon_handle_optout() {
	if ( empty( $_GET['pmprodon_optout'] ) || $_GET['pmprodon_optout'] !== '1' ) {
		return;
	}

	$user_id = isset( $_GET['uid'] ) ? intval( $_GET['uid'] ) : 0;
	$token   = isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '';

	if ( $user_id <= 0 || empty( $token ) ) {
		return;
	}

	// Validate the token.
	$expected_token = pmprodon_get_optout_token( $user_id );
	if ( ! hash_equals( $expected_token, $token ) ) {
		return;
	}

	// Verify the user exists.
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return;
	}

	// Set opt-out.
	update_user_meta( $user_id, 'pmprodon_reminder_optout', 1 );

	// Store a transient for a success message on redirect.
	set_transient( 'pmprodon_optout_success_' . $user_id, true, 60 );

	// Redirect to home with a clean URL.
	wp_safe_redirect( add_query_arg( 'pmprodon_opted_out', '1', home_url() ) );
	exit;
}
add_action( 'init', 'pmprodon_handle_optout' );

/**
 * Display a notice after successful opt-out.
 *
 * @since 2.3
 */
function pmprodon_optout_notice() {
	if ( empty( $_GET['pmprodon_opted_out'] ) ) {
		return;
	}
	?>
	<div style="max-width: 600px; margin: 40px auto; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; text-align: center;">
		<p style="font-size: 16px; color: #1e1e1e;">
			<?php esc_html_e( 'You have been successfully unsubscribed from donation reminder emails.', 'pmpro-donations' ); ?>
		</p>
	</div>
	<?php
}
add_action( 'wp_footer', 'pmprodon_optout_notice' );

/**
 * Cron callback: process donation reminder emails.
 *
 * Queries all membership levels with a reminder interval configured,
 * finds members due for a reminder, and sends the email. Skips
 * opted-out users and guest donors.
 *
 * @since 2.3
 */
function pmprodon_process_donation_reminders() {
	global $wpdb;

	// Bail early if PMPro is not active.
	if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
		return;
	}

	$all_levels = pmpro_getAllLevels( true, true );
	if ( empty( $all_levels ) ) {
		return;
	}

	// Collect levels with reminders enabled.
	$reminder_levels = array();
	foreach ( $all_levels as $level ) {
		$settings = pmprodon_get_level_settings( $level->id );
		if ( empty( $settings['donations'] ) ) {
			continue;
		}
		if ( empty( $settings['reminder_interval'] ) || $settings['reminder_interval'] === 'none' ) {
			continue;
		}
		$interval_seconds = pmprodon_get_interval_seconds( $settings['reminder_interval'] );
		if ( $interval_seconds <= 0 ) {
			continue;
		}
		$reminder_levels[ $level->id ] = array(
			'level'            => $level,
			'interval_seconds' => $interval_seconds,
		);
	}

	if ( empty( $reminder_levels ) ) {
		return;
	}

	$now = current_time( 'timestamp' );

	// Process each level.
	foreach ( $reminder_levels as $level_id => $level_data ) {
		$interval_seconds = $level_data['interval_seconds'];
		$level            = $level_data['level'];

		// Process active members on this level in deterministic batches.
		$last_user_id = 0;
		$batch_size   = 500;
		do {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$members = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DISTINCT mu.user_id
					FROM {$wpdb->pmpro_memberships_users} mu
					WHERE mu.membership_id = %d
					AND mu.status = 'active'
					AND mu.user_id > %d
					ORDER BY mu.user_id ASC
					LIMIT %d",
					$level_id,
					$last_user_id,
					$batch_size
				)
			);

			if ( empty( $members ) ) {
				break;
			}

			foreach ( $members as $member ) {
				$user_id = intval( $member->user_id );
				$last_user_id = max( $last_user_id, $user_id );

				// Skip guest donors.
				$is_guest = get_user_meta( $user_id, 'pmprodon_is_guest_donor', true );
				if ( ! empty( $is_guest ) ) {
					continue;
				}

				// Skip opted-out users.
				$opted_out = get_user_meta( $user_id, 'pmprodon_reminder_optout', true );
				if ( ! empty( $opted_out ) ) {
					continue;
				}

				// Check last donation date.
				$last_donation = get_user_meta( $user_id, 'pmprodon_last_donation_date', true );
				if ( empty( $last_donation ) ) {
					// No donation date recorded — skip, since we only remind past donors.
					continue;
				}

				$time_since_donation = $now - intval( $last_donation );
				if ( $time_since_donation < $interval_seconds ) {
					// Not yet due for a reminder.
					continue;
				}

				// Check last reminder sent timestamp.
				$last_reminder = get_user_meta( $user_id, 'pmprodon_last_reminder_sent', true );
				if ( ! empty( $last_reminder ) ) {
					$time_since_reminder = $now - intval( $last_reminder );
					if ( $time_since_reminder < $interval_seconds ) {
						// Already sent a reminder within this interval.
						continue;
					}
				}

				// Send the reminder email.
				$sent = pmprodon_send_reminder_email( $user_id, $level );
				if ( $sent ) {
					update_user_meta( $user_id, 'pmprodon_last_reminder_sent', $now );
				}
			}
		} while ( count( $members ) === $batch_size );
	}
}
add_action( 'pmprodon_donation_reminders_cron', 'pmprodon_process_donation_reminders' );

/**
 * Send a donation reminder email to a user.
 *
 * Uses PMPro's PMProEmail class to send the email, following
 * the same patterns used for checkout emails.
 *
 * @since 2.3
 *
 * @param int    $user_id The user ID.
 * @param object $level   The membership level object.
 * @return bool Whether the email was sent successfully.
 */
function pmprodon_send_reminder_email( $user_id, $level ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}

	// Build the donation checkout URL.
	if ( function_exists( 'pmpro_url' ) ) {
		$checkout_url = pmpro_url( 'checkout', '?level=' . intval( $level->id ) );
	} else {
		$checkout_url = home_url();
	}

	$optout_url = pmprodon_get_optout_url( $user_id );

	// Get the site name.
	$sitename = get_bloginfo( 'name' );

	// Build the display name.
	$display_name = $user->display_name;
	if ( empty( $display_name ) ) {
		$display_name = $user->user_login;
	}

	// Use PMPro's email class if available.
	if ( class_exists( 'PMProEmail' ) ) {
		$email = new PMProEmail();

		$email->email    = $user->user_email;
		$email->subject  = sprintf(
			/* translators: %s: site name */
			__( 'Donation Reminder from %s', 'pmpro-donations' ),
			$sitename
		);
		$email->template = 'pmprodon_donation_reminder';

		// Build email body.
		$body  = '<p>' . sprintf(
			/* translators: %s: member display name */
			esc_html__( 'Hi %s,', 'pmpro-donations' ),
			esc_html( $display_name )
		) . '</p>';
		$body .= '<p>' . sprintf(
			/* translators: %s: level name */
			esc_html__( 'Thank you for your past donations to %s. We wanted to reach out and let you know that your support makes a real difference.', 'pmpro-donations' ),
			esc_html( $level->name )
		) . '</p>';
		$body .= '<p>' . sprintf(
			/* translators: %s: donation checkout URL */
			__( 'If you would like to make another donation, you can do so here: %s', 'pmpro-donations' ),
			'<a href="' . esc_url( $checkout_url ) . '">' . esc_html( $checkout_url ) . '</a>'
		) . '</p>';
		$body .= '<p>' . esc_html__( 'Thank you for your generosity!', 'pmpro-donations' ) . '</p>';
		$body .= '<hr />';
		$body .= '<p><small>' . sprintf(
			/* translators: %s: opt-out URL */
			__( 'If you no longer wish to receive donation reminders, you can %s.', 'pmpro-donations' ),
			'<a href="' . esc_url( $optout_url ) . '">' . esc_html__( 'unsubscribe here', 'pmpro-donations' ) . '</a>'
		) . '</small></p>';

		$email->body = $body;

		// Set email data for PMPro template variables.
		$email->data = array(
			'subject'        => $email->subject,
			'name'           => $display_name,
			'display_name'   => $display_name,
			'user_login'     => $user->user_login,
			'sitename'       => $sitename,
			'siteemail'      => get_bloginfo( 'admin_email' ),
			'checkout_link'  => $checkout_url,
			'optout_link'    => $optout_url,
			'login_link'     => wp_login_url(),
			'enddate'        => '',
			'membership_id'  => $level->id,
			'membership_level_name' => $level->name,
		);

		return $email->sendEmail();
	}

	// Fallback: use wp_mail directly if PMProEmail is not available.
	$subject = sprintf(
		/* translators: %s: site name */
		__( 'Donation Reminder from %s', 'pmpro-donations' ),
		$sitename
	);

	$message  = sprintf(
		/* translators: %s: member display name */
		__( 'Hi %s,', 'pmpro-donations' ),
		$display_name
	) . "\n\n";
	$message .= sprintf(
		/* translators: %s: level name */
		__( 'Thank you for your past donations to %s. We wanted to reach out and let you know that your support makes a real difference.', 'pmpro-donations' ),
		$level->name
	) . "\n\n";
	$message .= sprintf(
		/* translators: %s: donation checkout URL */
		__( 'Make another donation: %s', 'pmpro-donations' ),
		$checkout_url
	) . "\n\n";
	$message .= __( 'Thank you for your generosity!', 'pmpro-donations' ) . "\n\n";
	$message .= '---' . "\n";
	$message .= sprintf(
		/* translators: %s: opt-out URL */
		__( 'Unsubscribe from donation reminders: %s', 'pmpro-donations' ),
		$optout_url
	);

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

	return wp_mail( $user->user_email, $subject, $message, $headers );
}

/**
 * Schedule the daily donation reminders cron event.
 *
 * @since 2.3
 */
function pmprodon_schedule_reminder_cron() {
	if ( ! wp_next_scheduled( 'pmprodon_donation_reminders_cron' ) ) {
		wp_schedule_event( time(), 'daily', 'pmprodon_donation_reminders_cron' );
	}
}

/**
 * Clear the donation reminders cron event.
 *
 * @since 2.3
 */
function pmprodon_clear_reminder_cron() {
	$timestamp = wp_next_scheduled( 'pmprodon_donation_reminders_cron' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'pmprodon_donation_reminders_cron' );
	}
}
