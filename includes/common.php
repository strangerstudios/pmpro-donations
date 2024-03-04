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
	//Set single to true to bring a single donation amount. Shouldn't be more than one.
	$donation = esc_html( get_pmpro_membership_order_meta( $order->id, 'donation_amount', $single = true ) );


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

/**
 * Add donation amount to order meta.
 *
 * @param object The order object.
 * @since TBD
 */
function pmprodon_store_donation_amount_in_order_meta( $order ) {
	if ( isset( $_REQUEST['donation'] ) ) {
		update_pmpro_membership_order_meta( $order->id, 'donation_amount', sanitize_text_field( $_REQUEST['donation'] ) );
	}
}

add_action( 'pmpro_added_order','pmprodon_store_donation_amount_in_order_meta', 10, 1 );

/**
 * Add donation column to the export csv orders
 *
 * @param Array CSV document columns.
 * @return Array CSV document columns.
 * @since TBD
 */
function pmprodon_add_donation_column_to_export_orders_csv( $columns ){
	$columns["donation"] = "pmprodon_extra_order_column_donation";

	return $columns;
}

add_filter("pmpro_orders_csv_extra_columns", "pmprodon_add_donation_column_to_export_orders_csv", 10, 1);

/**
 * Add donation column to the export csv orders
 *
 * @param Object $order The order object.
 * @return String The donation amount.
 * @since TBD
 */
function pmprodon_extra_order_column_donation( $order ){
	$r = pmprodon_get_price_components( $order );
	return $r['donation'];
}