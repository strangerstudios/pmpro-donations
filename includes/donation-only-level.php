<?php
/*
	IDEA
	* Add setting to edit level page to mark it as a "donation-only level".
	* Don't cancel any old orders or subscriptions when checking out for a donation-only level.
	* If an existing user checks out for a donation-only level, give them their old level back.
*/

/**
 * Set existing member flag before checkout.
 */
function pmprodon_pmpro_checkout_before_change_membership_level( $user_id, $morder ) {
	global $pmprodon_existing_member_flag, $pmpro_level;

	if ( pmpro_hasMembershipLevel()
	&& pmpro_is_checkout()
	&& ! empty( $pmpro_level )
	&& pmprodon_is_donations_only( $pmpro_level->id ) ) {
		add_filter( 'pmpro_cancel_previous_subscriptions', '__return_false' );
		add_filter( 'pmpro_deactivate_old_levels', '__return_false' );
		$pmprodon_existing_member_flag = true;
	}
}
add_action( 'pmpro_checkout_before_change_membership_level', 'pmprodon_pmpro_checkout_before_change_membership_level', 1, 2 );

/**
 * Give existing users their old level back after checkout.
 */
function pmprodon_pmpro_after_checkout( $user_id ) {
	global $wpdb, $pmprodon_existing_member_flag, $pmpro_level;

	if ( isset( $pmprodon_existing_member_flag ) ) {
		// Remove last row added to members_users table.
		$sqlQuery = "DELETE FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . esc_sql( $user_id ) . "' AND membership_id = '" . esc_sql( $pmpro_level->id ) . "' ORDER BY id DESC LIMIT 1";
		$wpdb->query( $sqlQuery );

		// Reset user.
		global $all_membership_levels;
		unset( $all_membership_levels[ $user_id ] );
		pmpro_set_current_user();
	}
}
add_action( 'pmpro_after_checkout', 'pmprodon_pmpro_after_checkout' );

/**
 * On the edit level page, we never want to prevent a user from selecting a donation-only level.
 *
 * @since 1.1.2
 *
 * @param bool $return
 * @param object $level
 * @return bool
 */
function pmprodon_pmpro_is_level_expiring_soon( $return, $level ) {
	if ( ! empty( $level->id ) && pmprodon_is_donations_only( $level->id ) ) {
		return true;
	}

	return $return;
}
add_filter( 'pmpro_is_level_expiring_soon', 'pmprodon_pmpro_is_level_expiring_soon', 10, 2 );
