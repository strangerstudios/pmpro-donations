<?php
/**
 * Function to get donation and original price out of an order.
 */
function pmprodon_getPriceComponents( $order ) {
	$r = array(
		'price'    => $order->total,
		'donation' => '',
	);

	if ( isset( $order->notes ) && ! empty( $order->notes ) && strpos( $order->notes, __( 'Donation', 'pmpro-donations' ) ) !== false ) {
		$donation      = pmpro_getMatches( '/' . __( 'Donation', 'pmpro-donations' ) . '\: ([0-9\.]+)/', $order->notes, true );
		$r['donation'] = $donation;
		if ( $donation > 0 ) {
			$r['price'] = $order->total - $donation;
		}
	}

	// filter added .2
	$r = apply_filters( 'pmpro_donations_get_price_components', $r, $order );

	return $r;
}