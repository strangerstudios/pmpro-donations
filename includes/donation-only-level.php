<?php
/*
	IDEA

	* Add setting to edit level page to mark it as a "donation-only level".
	* Don't cancel any old orders or subscriptions when checking out for a donation-only level.
	* If an existing user checks out for a donation-only level, give them their old level back.
*/
/**
 * Set existing member flag before checkout.
 * Store user's previous levels and prevent cancellation when checking out for donation-only level.
 *
 * @param int    $user_id The user ID.
 * @param object $morder The membership order object.
 */
function pmprodon_pmpro_checkout_before_change_membership_level( $user_id, $morder ) {
	global $pmprodon_existing_member_flag, $pmpro_level;

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
 * Enhanced to properly handle level groups and restore the appropriate level.
 *
 * @param int $user_id The user ID.
 */
function pmprodon_pmpro_after_checkout( $user_id ) {
	global $wpdb, $pmprodon_existing_member_flag, $pmpro_level;

	if ( isset( $pmprodon_existing_member_flag ) ) {
		// Get user's current levels.
		$current_levels = pmpro_getMembershipLevelsForUser( $user_id );

		// Check if they now have a donation-only level.
		$donation_level_id = null;
		$has_donation_level = false;

		foreach ( $current_levels as $level ) {
			if ( pmprodon_is_donations_only( $level->id ) ) {
				$donation_level_id = $level->id;
				$has_donation_level = true;
				break;
			}
		}

		// If user has a donation-only level, restore their previous level
		if ( $has_donation_level ) {
			// Get their previous levels that we stored.
			$previous_levels = get_user_meta( $user_id, 'pmprodon_previous_levels', true );

			if ( ! empty( $previous_levels ) ) {
				// Determine which level we should restore.
				$level_to_restore = null;

				// Find a level from the same group as the donation level.
				// Get donation level's group if available.
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
				if ( ! $level_to_restore && ! empty( $previous_levels ) ) {
					$level_to_restore = $previous_levels[0];
				}

				if ( $level_to_restore ) {
					// Cancel the donation-only level properly
					pmpro_cancelMembershipLevel( $donation_level_id, $user_id, 'inactive' );

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
					pmpro_changeMembershipLevel( $custom_level, $user_id );

					// Clean up stored data.
					delete_user_meta( $user_id, 'pmprodon_previous_levels' );
				}

				// Reset user.
				global $all_membership_levels;
				unset( $all_membership_levels[ $user_id ] );
				pmpro_set_current_user();
			}
		}
	}
}
add_action( 'pmpro_after_checkout', 'pmprodon_pmpro_after_checkout' );

/**
 * On the edit level page, we never want to prevent a user from selecting a donation-only level.
 *
 * @since 1.1.2
 *
 * @param bool   $return Whether the level is expiring soon.
 * @param object $level The level object.
 * @return bool
 */
function pmprodon_pmpro_is_level_expiring_soon( $return, $level ) {
	if ( ! empty( $level->id ) && pmprodon_is_donations_only( $level->id ) ) {
		return true;
	}

	return $return;
}
add_filter( 'pmpro_is_level_expiring_soon', 'pmprodon_pmpro_is_level_expiring_soon', 10, 2 );
