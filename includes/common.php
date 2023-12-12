<?php
/**
 * Function to get donation and original price out of an order.
 * 
 * @param object $order The order object.
 * @since TBD
 * return array The price components.
 */
function pmprodon_get_price_components( $order ) {
	$r = array(
		'price'    => $order->total,
		'donation' => '',
	);

	global $wpdb;
	$table_name = $wpdb->pmpro_membership_ordermeta;
	$donation = 0;
	// check for donation in order meta table.
	if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name ) {
		//Set single to true to bring a single donation amount. Shouldn't be more than one.
		$donation = esc_html( get_pmpro_membership_order_meta( $order->id, 'donation_amount', $single = true ) );
	// check for donation in order notes for older versions of PMPro.
	} else if ( empty( $donation ) && isset( $order->notes ) && ! empty( $order->notes ) 
		&& strpos( $order->notes, __( 'Donation', 'pmpro-donations' ) ) !== false ) {
		$donation = pmpro_getMatches( '/' . __( 'Donation', 'pmpro-donations' ) . '\: ([0-9\.]+)/', $order->notes, true );
	}

	$r['donation'] = $donation;
	if ( $donation > 0 ) {
		$r['price'] = $order->total - $donation;
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
		'donations'       => 0,
		'donations_only'  => 0,
		'min_price'       => '',
		'max_price'       => '',
		'dropdown_prices' => '',
		'text'            => '',
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
	if ( $settings['donations'] && $settings['donations_only'] ) {
		return true;
	} else {
		return false;
	}
}