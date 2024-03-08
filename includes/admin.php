<?php

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

add_filter( 'pmpro_orders_csv_extra_columns', 'pmprodon_add_donation_column_to_export_orders_csv', 10, 1 );

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

/**
 * Add donation amount field to the orders page.
 *
 * @param Object $order The order object.
 * @return void
 * @since TBD
 */
function pmprodon_add_donation_field_to_orders_page( $order ) {
	
	//get donation amount
    $price_components = pmprodon_get_price_components( $order );
	$donation = empty( $price_components['donation'] ) ? '' : $price_components['donation'];
	?>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row" valign="top"><label for="donation"><?php _e( 'Donation Amount', 'pmpro-don' ); ?>:</label></th>
				<td>
					<input type="text" id="donation_amount" name="donation_amount" size="20" value="<?php echo esc_attr( pmpro_filter_price_for_text_field( $donation ) ); ?>" />
					<p class="description"><?php _e( 'Enter the donation amount for this order.', 'pmpro-don' ); ?></p>
				</td>
			</tr>
		</tbody>
	</table>
	<?php

}

add_action( 'pmpro_after_order_settings', 'pmprodon_add_donation_field_to_orders_page'  );

/**
 * Save donation amount to order meta on pmpro_updated_order action execution.
 * Only run on the admin edit order page.
 *
 * @param Object $order The order object.
 * @return void
 * @since TBD
 */
function pmprodon_save_donation_amount( $order ) {
	if ( isset( $_REQUEST['donation_amount'] ) && is_admin() && 'pmpro-orders' === $_REQUEST['page'] ) {
        $float_amount = is_numeric( $_REQUEST['donation_amount'] ) ? floatval( $_REQUEST['donation_amount'] ) : '';
		update_pmpro_membership_order_meta( $order->id, 'donation_amount', $float_amount );
	}
}

add_action( 'pmpro_updated_order', 'pmprodon_save_donation_amount', 10, 1 );