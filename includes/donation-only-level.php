<?php
/*
	IDEA
	* Add setting to edit level page to mark it as a "donation-only level".
	* Don't cancel any old orders or subscriptions when checking out for a donation-only level.
	* If an existing user checks out for a donation-only level, give them their old level back.
*/

/**
 * Set existing member flag before checkout.
 * Store user's previous levels and prevent cancellation when checking out for a donation-only level.
 *
 * @since 2.3
 *
 * @param int    $user_id The user ID.
 * @param object $morder  The membership order object.
 */
function pmprodon_pmpro_checkout_before_change_membership_level( $user_id, $morder ) {
	global $pmprodon_existing_member_flag, $pmpro_level;

	// Skip level preservation for guest donors — they have no existing levels.
	$is_guest = get_user_meta( $user_id, 'pmprodon_is_guest_donor', true );
	if ( ! empty( $is_guest ) ) {
		return;
	}

	if ( pmpro_hasMembershipLevel() && pmpro_is_checkout() && ! empty( $pmpro_level ) && pmprodon_is_donations_only( $pmpro_level->id ) ) {
		// Store the user's current level info before it gets changed.
		$current_levels = pmpro_getMembershipLevelsForUser( $user_id );
		if ( ! empty( $current_levels ) ) {
			update_user_meta( $user_id, 'pmprodon_previous_levels', $current_levels );
		}

		add_filter( 'pmpro_cancel_previous_subscriptions', '__return_false' );
		add_filter( 'pmpro_deactivate_old_levels', '__return_false' );
		$pmprodon_existing_member_flag = true;
	}
}
add_action( 'pmpro_checkout_before_change_membership_level', 'pmprodon_pmpro_checkout_before_change_membership_level', 1, 2 );

/**
 * Give existing users their old level back after checkout.
 * Uses PMPro API to detect donation-only level assignment, then restores
 * the correct previous level respecting level groups.
 *
 * For non-members (new users with no prior levels), cancels the donation-only
 * level so it does not persist on the account page.
 *
 * @since 2.3
 *
 * @param int $user_id The user ID.
 */
function pmprodon_pmpro_after_checkout( $user_id ) {
	global $pmprodon_existing_member_flag;

	// Skip level restoration for guest donors — they have no previous levels.
	$is_guest = get_user_meta( $user_id, 'pmprodon_is_guest_donor', true );
	if ( ! empty( $is_guest ) ) {
		// Guest donors should not retain a donation-only level either.
		pmprodon_cancel_donation_only_level_for_user( $user_id );
		return;
	}

	// Handle existing members — restore their previous level.
	if ( isset( $pmprodon_existing_member_flag ) ) {
		// Get user's current levels.
		$current_levels = pmpro_getMembershipLevelsForUser( $user_id );

		// Check if they now have a donation-only level.
		$donation_level_id  = null;
		$has_donation_level = false;

		foreach ( $current_levels as $level ) {
			if ( pmprodon_is_donations_only( $level->id ) ) {
				$donation_level_id  = $level->id;
				$has_donation_level = true;
				break;
			}
		}

		// If user has a donation-only level, restore their previous level.
		if ( $has_donation_level ) {
			// Get their previous levels that we stored.
			$previous_levels = get_user_meta( $user_id, 'pmprodon_previous_levels', true );

			if ( ! empty( $previous_levels ) ) {
				// Determine which level we should restore.
				$level_to_restore = null;

				// Find a level from the same group as the donation level.
				$donation_group_id = null;
				if ( function_exists( 'pmpro_get_group_id_for_level' ) ) {
					$donation_group_id = pmpro_get_group_id_for_level( $donation_level_id );
				}

				if ( $donation_group_id ) {
					// Find a previous level in the same group.
					foreach ( $previous_levels as $prev_level ) {
						$prev_level_group_id = pmpro_get_group_id_for_level( $prev_level->id );
						if ( $prev_level_group_id === $donation_group_id ) {
							$level_to_restore = $prev_level;
							break;
						}
					}
				}

				// If no level found in the same group, use the first previous level.
				if ( ! $level_to_restore ) {
					$level_to_restore = $previous_levels[0];
				}

				// Cancel the donation-only level properly.
				// Suppress cancellation emails during level swap.
				add_filter( 'pmpro_send_cancel_admin_email', '__return_false' );
				add_filter( 'pmpro_email_filter', 'pmprodon_suppress_cancellation_email', 1 );

				pmpro_cancelMembershipLevel( $donation_level_id, $user_id, 'inactive' );

					$current_levels = pmpro_getMembershipLevelsForUser( $user_id );
					if ( ! pmprodon_user_has_level( $current_levels, $level_to_restore->id ) ) {
						// Create a custom level array for restoration.
						$custom_level = array(
							'user_id'         => $user_id,
							'membership_id'   => $level_to_restore->id,
							'code_id'         => $level_to_restore->code_id,
							'initial_payment' => $level_to_restore->initial_payment,
							'billing_amount'  => $level_to_restore->billing_amount,
							'cycle_number'    => $level_to_restore->cycle_number,
							'cycle_period'    => $level_to_restore->cycle_period,
							'billing_limit'   => $level_to_restore->billing_limit,
							'trial_amount'    => $level_to_restore->trial_amount,
							'trial_limit'     => $level_to_restore->trial_limit,
							'startdate'       => $level_to_restore->startdate,
							'enddate'         => $level_to_restore->enddate,
						);

						// Restore the original level.
						$restored = pmpro_changeMembershipLevel( $custom_level, $user_id );
						if ( ! $restored ) {
							error_log( sprintf( 'PMPro Donations: Failed to restore level %d for user %d after donation-only checkout.', $level_to_restore->id, $user_id ) );
						}
					}

				// Re-enable cancellation emails.
				remove_filter( 'pmpro_send_cancel_admin_email', '__return_false' );
				remove_filter( 'pmpro_email_filter', 'pmprodon_suppress_cancellation_email', 1 );
			}
		}

		// Always clean up stored meta when existing member flag was set.
		delete_user_meta( $user_id, 'pmprodon_previous_levels' );

		// Reset user.
		global $all_membership_levels;
		unset( $all_membership_levels[ $user_id ] );
		pmpro_set_current_user();

		return;
	}

	// Handle non-members — cancel the donation-only level so it does not
	// persist on the account page. This user had no prior membership.
	pmprodon_cancel_donation_only_level_for_user( $user_id );
}
add_action( 'pmpro_after_checkout', 'pmprodon_pmpro_after_checkout' );

/**
 * Cancel any donation-only level for a user.
 *
 * Used after checkout for non-members and guest donors so that the
 * donation-only level does not persist on the account page.
 *
 * @since 2.3
 *
 * @param int $user_id The user ID.
 */
function pmprodon_cancel_donation_only_level_for_user( $user_id ) {
	$current_levels = pmpro_getMembershipLevelsForUser( $user_id );
	if ( empty( $current_levels ) ) {
		return;
	}

	foreach ( $current_levels as $level ) {
		if ( pmprodon_is_donations_only( $level->id ) ) {
			add_filter( 'pmpro_send_cancel_admin_email', '__return_false' );
			add_filter( 'pmpro_email_filter', 'pmprodon_suppress_cancellation_email', 1 );
			pmpro_cancelMembershipLevel( $level->id, $user_id, 'inactive' );
			remove_filter( 'pmpro_send_cancel_admin_email', '__return_false' );
			remove_filter( 'pmpro_email_filter', 'pmprodon_suppress_cancellation_email', 1 );

			// Reset user cache.
			global $all_membership_levels;
			unset( $all_membership_levels[ $user_id ] );
			pmpro_set_current_user();
			break;
		}
	}
}

/**
 * Check whether a level ID is present in a user's current levels.
 *
 * @since 2.3
 *
 * @param array $levels   Array of level objects.
 * @param int   $level_id Level ID to check.
 * @return bool
 */
function pmprodon_user_has_level( $levels, $level_id ) {
	if ( empty( $levels ) ) {
		return false;
	}

	foreach ( $levels as $level ) {
		if ( ! empty( $level->id ) && intval( $level->id ) === intval( $level_id ) ) {
			return true;
		}
	}

	return false;
}

/**
 * On the edit level page, we never want to prevent a user from selecting a donation-only level.
 *
 * @since 1.1.2
 *
 * @param bool   $return Whether the level is expiring soon.
 * @param object $level  The level object.
 * @return bool
 */
function pmprodon_pmpro_is_level_expiring_soon( $return, $level ) {
	if ( ! empty( $level->id ) && pmprodon_is_donations_only( $level->id ) ) {
		return true;
	}

	return $return;
}
add_filter( 'pmpro_is_level_expiring_soon', 'pmprodon_pmpro_is_level_expiring_soon', 10, 2 );

/**
 * Filter the text that says a level will be removed at checkout.
 * Suppresses the misleading 'Your current membership level will be removed' message
 * when checking out for a donation-only level.
 *
 * @since 2.3
 *
 * @param string $translated_text The translated text.
 * @param string $text            The original text.
 * @param string $domain          The text domain.
 * @return string The modified or original text.
 */
function pmprodon_filter_checkout_level_change_text( $translated_text, $text, $domain ) {
	// Only proceed if we're on the checkout page.
	if ( ! pmpro_is_checkout() ) {
		return $translated_text;
	}

	// Target only the specific message about level removal.
	if ( $domain === 'paid-memberships-pro' &&
		$text === 'Your current membership level of %s will be removed when you complete your purchase.' ) {

		global $pmpro_level;

		// Check if this is a donation-only level.
		if ( ! empty( $pmpro_level ) && pmprodon_is_donations_only( $pmpro_level->id ) ) {
			return '';
		}
	}

	return $translated_text;
}
add_filter( 'gettext', 'pmprodon_filter_checkout_level_change_text', 10, 3 );

/**
 * Suppress cancellation emails during donation-only level swaps.
 *
 * Hooked temporarily at priority 1 on pmpro_email_filter to block
 * cancellation emails that would confuse donors during the
 * cancel-and-restore flow.
 *
 * @since 2.3
 *
 * @param object $email The email object.
 * @return object The email object, with send disabled if it's a cancellation email.
 */
function pmprodon_suppress_cancellation_email( $email ) {
	if ( strpos( $email->template, 'cancel' ) !== false ) {
		$email->send = false;
	}
	return $email;
}
