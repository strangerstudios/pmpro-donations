<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
	IDEA
	* Add setting to edit level page to mark it as a "donation-only level".
	* Don't cancel any old orders or subscriptions when checking out for a donation-only level.
	* If an existing user checks out for a donation-only level, give them their old level back.
*/

/**
 * Before an existing member's donation-only checkout changes memberships, keep their memberships.
 *
 * State lives in $pmprodon_existing_member_flag (purchaser, level, and whether they already had it)
 * and is cleared after the checkout or when the next checkout starts.
 */
function pmprodon_pmpro_checkout_before_change_membership_level( $user_id, $morder ) {
	global $pmprodon_existing_member_flag, $pmpro_level;

	// Never carry state from an earlier checkout in this request into this one.
	pmprodon_reset_existing_member_checkout();

	// Check the user checking out, not the current user, and don't require the checkout page.
	// Offsite/async gateways (e.g. Stripe Checkout) complete the checkout in a webhook, where
	// there is no current user and pmpro_is_checkout() is false.
	$user_id  = (int) $user_id;
	$level_id = ! empty( $morder->membership_id ) ? (int) $morder->membership_id : ( empty( $pmpro_level->id ) ? 0 : (int) $pmpro_level->id );
	if ( $user_id < 1 || $level_id < 1 || ! pmprodon_is_donations_only( $level_id ) ) {
		return;
	}

	// Use the purchaser's active memberships, not an access check that other Add Ons may filter.
	$levels = pmpro_getMembershipLevelsForUser( $user_id );
	if ( empty( $levels ) ) {
		return;
	}

	$pmprodon_existing_member_flag = array(
		'user_id'            => $user_id,
		'level_id'           => $level_id,
		'already_had_target' => in_array( $level_id, array_map( 'intval', wp_list_pluck( $levels, 'id' ) ), true ),
	);
	add_filter( 'pmpro_cancel_previous_subscriptions', 'pmprodon_keep_existing_memberships' );
	add_filter( 'pmpro_deactivate_old_levels', 'pmprodon_keep_existing_memberships' );
}
add_action( 'pmpro_checkout_before_change_membership_level', 'pmprodon_pmpro_checkout_before_change_membership_level', 1, 2 );

/**
 * Don't cancel subscriptions or deactivate levels during an existing member's donation-only checkout.
 *
 * @since TBD
 *
 * @return bool
 */
function pmprodon_keep_existing_memberships() {
	return false;
}

/**
 * Remove this Add On's filters and state for an existing member's donation-only checkout.
 *
 * @since TBD
 */
function pmprodon_reset_existing_member_checkout() {
	global $pmprodon_existing_member_flag;
	remove_filter( 'pmpro_cancel_previous_subscriptions', 'pmprodon_keep_existing_memberships' );
	remove_filter( 'pmpro_deactivate_old_levels', 'pmprodon_keep_existing_memberships' );
	$pmprodon_existing_member_flag = null;
}

/**
 * Give existing users their old level back after checkout.
 *
 * @param int    $user_id The user who checked out.
 * @param object $morder  The checkout order.
 */
function pmprodon_pmpro_after_checkout( $user_id, $morder = null ) {
	global $wpdb, $pmprodon_existing_member_flag;

	if ( empty( $pmprodon_existing_member_flag ) || ! is_array( $pmprodon_existing_member_flag ) ) {
		return;
	}

	$checkout = $pmprodon_existing_member_flag;
	pmprodon_reset_existing_member_checkout();

	if ( (int) $user_id !== $checkout['user_id'] || ( ! empty( $morder->membership_id ) && (int) $morder->membership_id !== $checkout['level_id'] ) ) {
		return;
	}

	// If they already had this donation-only level, core replaced that membership with the new one. Keep it.
	if ( ! $checkout['already_had_target'] ) {
		// Remove last row added to members_users table.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $wpdb->pmpro_memberships_users WHERE user_id = %d AND membership_id = %d ORDER BY id DESC LIMIT 1",
				$checkout['user_id'],
				$checkout['level_id']
			)
		);
	}

	// Reset user.
	if ( function_exists( 'pmpro_clear_level_cache_for_user' ) ) {
		pmpro_clear_level_cache_for_user( $checkout['user_id'] );
	} else {
		global $all_membership_levels;
		unset( $all_membership_levels[ $checkout['user_id'] ] );
	}
	pmpro_set_current_user();
}
add_action( 'pmpro_after_checkout', 'pmprodon_pmpro_after_checkout', 10, 2 );

/**
 * Suppress the "Your current membership level of X will be removed when you complete your purchase."
 * warning on the checkout page when purchasing a donation-only level, since the old level is preserved.
 *
 * @since 2.4
 *
 * @param object $pmpro_level The level being checked out.
 */
function pmprodon_suppress_level_removal_warning( $pmpro_level ) {
	if ( ! empty( $pmpro_level ) && pmprodon_is_donations_only( $pmpro_level->id ) ) {
		add_filter( 'gettext', 'pmprodon_filter_level_removal_warning_text', 10, 3 );
	}
}
add_action( 'pmpro_checkout_preheader_after_get_level_at_checkout', 'pmprodon_suppress_level_removal_warning' );

/**
 * Return an empty string for the "level will be removed" warning text so it is not displayed.
 *
 * @since 2.4
 *
 * @param string $translation Translated text.
 * @param string $text        Original text.
 * @param string $domain      Text domain.
 * @return string
 */
function pmprodon_filter_level_removal_warning_text( $translation, $text, $domain ) {
	if ( 'paid-memberships-pro' === $domain && 'Your current membership level of %s will be removed when you complete your purchase.' === $text ) {
		return '';
	}
	return $translation;
}

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
