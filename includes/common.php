<?php
/**
 * Function to get donation and original price out of an order.
 * 
 * @param object $order The order object.
 * @since 2.0
 * return array The price components.
 */
function pmprodon_get_price_components( $order ) {
	$r = array(
		'price'    => $order->total,
		'donation' => '',
	);

	//Set single to true to bring a single donation amount. Shouldn't be more than one.
	$donation = get_pmpro_membership_order_meta( $order->id, 'donation_amount', true );
	if ( ! empty( $donation ) ) {
		$donation = floatval( $donation );
		$r['donation'] = $donation;
		if ( $donation > 0 ) {
			$r['price'] = $order->total - $donation;
		}
	} else {
		// Check if we have data stored in the order notes.
		$donation      = floatval( pmpro_getMatches( '/' . __( 'Donation', 'pmpro-donations' ) . '\: ([0-9\.]+)/', $order->notes, true ) );
		$r['donation'] = $donation;
		if ( $donation > 0 ) {
			$r['price'] = $order->total - $donation;

            // Save the donation amount to the order meta and remove it from the notes.
            update_pmpro_membership_order_meta( $order->id, 'donation_amount', $donation );
            $order->notes = preg_replace( '/' . __( 'Donation', 'pmpro-donations' ) . '\: ([0-9\.]+)/', '', $order->notes );
            $order->saveOrder();
		}
	}

	// filter added .2
	$r = apply_filters( 'pmpro_donations_get_price_components', $r, $order );

	return $r;
}

/**
 * Deprecated name for pmprodon_get_price_components.
 */
function pmprodon_getPriceComponents( $order ) {
	return pmprodon_get_price_components( $order );
}

/**
 * Get donation settings for level.
 */
function pmprodon_get_level_settings( $level_id ) {
	$default_settings = array(
		'donations'              => 0,
		'donations_only'         => 0,
		'allow_guest_donations'  => 0,
		'min_price'              => '',
		'max_price'              => '',
		'dropdown_prices'        => '',
		'display_mode'           => 'dropdown',
		'text'                   => '',
		'confirmation_message'   => '',
		'donation_note_enabled'  => 0,
		'donation_note_label'    => '',
		'cover_fees_enabled'     => 0,
		'cover_fees_percentage'  => '2.9',
		'cover_fees_flat'        => '0.30',
		'reminder_interval'           => 'none',
		'email_template_override'     => '',
	);
	
	if ( $level_id > 0 ) {
		$settings = get_option( 'pmprodon_' . $level_id, $default_settings );
	}

	$settings = ( ! empty( $settings ) && is_array( $settings ) ) ? array_merge( $default_settings, $settings ) : $default_settings;
	
	return $settings;
}

/**
 * Check if a level is a donations-only level
 */
function pmprodon_is_donations_only( $level_id ) {
	$settings = pmprodon_get_level_settings( $level_id );
	return $settings['donations'] && $settings['donations_only'];
}

/**
 * Calculate the cover fee amount for a given donation.
 *
 * Uses the pass-through formula so the organization receives the
 * full intended donation after the gateway deducts its fees:
 *   fee = ( donation + flat ) / ( 1 - percentage / 100 ) - donation
 *
 * @since 2.3
 *
 * @param float $donation   The donation amount.
 * @param float $percentage The gateway fee percentage (e.g. 2.9).
 * @param float $flat       The gateway flat fee (e.g. 0.30).
 * @return float The calculated cover fee, rounded to 2 decimal places.
 */
function pmprodon_calculate_cover_fee( $donation, $percentage, $flat ) {
	$donation   = (float) $donation;
	$percentage = (float) $percentage;
	$flat       = (float) $flat;

	if ( $donation <= 0 || $percentage >= 100 ) {
		return 0.00;
	}

	$fee = ( $donation + $flat ) / ( 1 - $percentage / 100 ) - $donation;

	return round( $fee, 2 );
}

/**
 * Check if the current checkout is a guest donation checkout.
 *
 * Returns true when the user is not logged in, the level allows guest
 * donations, the level is donation-only, and the guest flag is set
 * in the request.
 *
 * @since 2.3
 *
 * @return bool Whether this is a guest donation checkout.
 */
function pmprodon_is_guest_checkout() {
	// Logged-in users never use guest checkout.
	if ( is_user_logged_in() ) {
		return false;
	}

	// Check for the guest flag in the request.
	if ( empty( $_REQUEST['pmprodon_guest'] ) || $_REQUEST['pmprodon_guest'] !== '1' ) {
		return false;
	}

	// Get the level at checkout.
	if ( ! function_exists( 'pmpro_getLevelAtCheckout' ) ) {
		return false;
	}

	$level = pmpro_getLevelAtCheckout();
	if ( empty( $level->id ) ) {
		return false;
	}

	// Level must be donation-only with guest donations enabled.
	$settings = pmprodon_get_level_settings( $level->id );
	if ( empty( $settings['donations'] ) || empty( $settings['donations_only'] ) || empty( $settings['allow_guest_donations'] ) ) {
		return false;
	}

	return true;
}

/**
 * Generate a unique confirmation key for guest donor order access.
 *
 * @since 2.3
 *
 * @return string A 32-character random alphanumeric string.
 */
function pmprodon_generate_confirmation_key() {
	return wp_generate_password( 32, false );
}
