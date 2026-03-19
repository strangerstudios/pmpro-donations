<?php

/**
 * Add donation column to the export csv orders
 *
 * @param Array CSV document columns.
 * @return Array CSV document columns.
 * @since 2.0
 */
function pmprodon_add_donation_column_to_export_orders_csv( $columns ){
	$columns["donation"]            = "pmprodon_extra_order_column_donation";
	$columns["donation_fee_covered"] = "pmprodon_extra_order_column_donation_fee_covered";
	$columns["donation_note"]       = "pmprodon_extra_order_column_donation_note";

	return $columns;
}

add_filter( 'pmpro_orders_csv_extra_columns', 'pmprodon_add_donation_column_to_export_orders_csv', 10, 1 );

/**
 * Add donation column to the export csv orders
 *
 * @param Object $order The order object.
 * @return String The donation amount.
 * @since 2.0
 */
function pmprodon_extra_order_column_donation( $order ){
	$r = pmprodon_get_price_components( $order );
	return $r['donation'];
}

/**
 * CSV column callback for the cover fee amount.
 *
 * @since 2.3
 *
 * @param object $order The order object.
 * @return string The cover fee amount.
 */
function pmprodon_extra_order_column_donation_fee_covered( $order ) {
	$fee = get_pmpro_membership_order_meta( $order->id, 'donation_fee_covered', true );
	return ! empty( $fee ) ? $fee : '';
}

/**
 * CSV column callback for the donation note.
 *
 * @since 2.3
 *
 * @param object $order The order object.
 * @return string The donation note.
 */
function pmprodon_extra_order_column_donation_note( $order ) {
	return get_pmpro_membership_order_meta( $order->id, 'donation_note', true );
}

/**
 * Add donation amount field to the orders page.
 *
 * Wraps the fields in a container div that is shown or hidden via
 * inline JavaScript based on whether the selected level has donations
 * enabled.
 *
 * @since 2.0
 * @since 2.3 Added wrapper div and JS toggle for level-based visibility.
 *
 * @param object $order The order object.
 * @return void
 */
function pmprodon_add_donation_field_to_orders_page( $order ) {

	// Get donation amount.
	$price_components = pmprodon_get_price_components( $order );
	$donation = empty( $price_components['donation'] ) ? '' : $price_components['donation'];

	// Get donation note.
	$donation_note = get_pmpro_membership_order_meta( $order->id, 'donation_note', true );

	// Get cover fee amount.
	$fee_covered = get_pmpro_membership_order_meta( $order->id, 'donation_fee_covered', true );

	// Build a JS array of level IDs that have donations enabled.
	$donation_level_ids = pmprodon_get_donation_enabled_level_ids();
	?>
	<div id="pmprodon_admin_donation_fields">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top"><label for="donation_amount"><?php esc_html_e( 'Donation Amount', 'pmpro-donations' ); ?>:</label></th>
					<td>
						<input type="text" id="donation_amount" name="donation_amount" size="20" value="<?php echo esc_attr( pmpro_filter_price_for_text_field( $donation ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Enter the donation amount for this order.', 'pmpro-donations' ); ?></p>
					</td>
				</tr>
				<?php if ( ! empty( $fee_covered ) && (float) $fee_covered > 0 ) { ?>
				<tr>
					<th scope="row" valign="top"><label><?php esc_html_e( 'Processing Fee Covered', 'pmpro-donations' ); ?>:</label></th>
					<td>
						<p><strong><?php echo esc_html( pmpro_formatPrice( $fee_covered ) ); ?></strong></p>
						<p class="description"><?php esc_html_e( 'The donor opted to cover processing fees for this order.', 'pmpro-donations' ); ?></p>
					</td>
				</tr>
				<?php } ?>
				<tr>
					<th scope="row" valign="top"><label for="donation_note"><?php esc_html_e( 'Donation Note', 'pmpro-donations' ); ?>:</label></th>
					<td>
						<textarea id="donation_note" name="donation_note" rows="3" cols="50"><?php echo esc_textarea( $donation_note ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Enter the donation note for this order.', 'pmpro-donations' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	<script type="text/javascript">
		jQuery( document ).ready( function( $ ) {
			var pmprodonLevelIds = <?php echo wp_json_encode( $donation_level_ids ); ?>;
			var $fields = $( '#pmprodon_admin_donation_fields' );
			var $levelSelect = $( '#membership_id' );

			function pmprodonToggleDonationFields() {
				var selectedLevel = parseInt( $levelSelect.val(), 10 );
				if ( pmprodonLevelIds.indexOf( selectedLevel ) !== -1 ) {
					$fields.show();
				} else {
					$fields.hide();
				}
			}

			if ( $levelSelect.length ) {
				$levelSelect.on( 'change', pmprodonToggleDonationFields );
				pmprodonToggleDonationFields();
			}
		} );
	</script>
	<?php
}

add_action( 'pmpro_after_order_settings', 'pmprodon_add_donation_field_to_orders_page' );

/**
 * Save donation amount to order meta on pmpro_updated_order action execution.
 * Only run on the admin edit order page.
 *
 * @param Object $order The order object.
 * @return void
 * @since 2.0
 */
function pmprodon_save_donation_amount( $order ) {
	if ( ! is_admin() || ! isset( $_REQUEST['page'] ) || 'pmpro-orders' !== $_REQUEST['page'] ) {
		return;
	}

	if ( isset( $_REQUEST['donation_amount'] ) ) {
		$raw_amount   = trim( sanitize_text_field( $_REQUEST['donation_amount'] ) );
		$float_amount = is_numeric( $raw_amount ) ? floatval( $raw_amount ) : '';
		update_pmpro_membership_order_meta( $order->id, 'donation_amount', $float_amount );
	}

	if ( isset( $_REQUEST['donation_note'] ) ) {
		$donation_note = sanitize_textarea_field( $_REQUEST['donation_note'] );
		update_pmpro_membership_order_meta( $order->id, 'donation_note', $donation_note );
	}
}

add_action( 'pmpro_updated_order', 'pmprodon_save_donation_amount', 10, 1 );

/**
 * Get an array of level IDs that have donations enabled.
 *
 * Used by admin JS to determine which levels should show
 * the donation fields on the order and Add Member pages.
 *
 * @since 2.3
 *
 * @return int[] Array of level IDs with donations enabled.
 */
function pmprodon_get_donation_enabled_level_ids() {
	$donation_level_ids = array();

	if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
		return $donation_level_ids;
	}

	$levels = pmpro_getAllLevels( true, true );
	if ( ! empty( $levels ) ) {
		foreach ( $levels as $level ) {
			$settings = pmprodon_get_level_settings( $level->id );
			if ( ! empty( $settings['donations'] ) ) {
				$donation_level_ids[] = (int) $level->id;
			}
		}
	}

	return $donation_level_ids;
}

/**
 * Display donation fields on the Add Member admin page.
 *
 * Hooked to 'pmpro_add_member_fields' which fires on the
 * Memberships > Add Member admin page after the core fields.
 *
 * @since 2.3
 *
 * @return void
 */
function pmprodon_pmpro_add_member_fields() {
	$donation_level_ids = pmprodon_get_donation_enabled_level_ids();
	?>
	<div id="pmprodon_admin_donation_fields" class="pmprodon_add_member_fields">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top"><label for="donation_amount"><?php esc_html_e( 'Donation Amount', 'pmpro-donations' ); ?>:</label></th>
					<td>
						<input type="text" id="donation_amount" name="donation_amount" size="20" value="" />
						<p class="description"><?php esc_html_e( 'Enter the donation amount for this order.', 'pmpro-donations' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="donation_note"><?php esc_html_e( 'Donation Note', 'pmpro-donations' ); ?>:</label></th>
					<td>
						<textarea id="donation_note" name="donation_note" rows="3" cols="50"></textarea>
						<p class="description"><?php esc_html_e( 'Enter the donation note for this order.', 'pmpro-donations' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	<script type="text/javascript">
		jQuery( document ).ready( function( $ ) {
			var pmprodonLevelIds = <?php echo wp_json_encode( $donation_level_ids ); ?>;
			var $fields = $( '#pmprodon_admin_donation_fields' );
			var $levelSelect = $( '#membership_level_id, #membership_id' );

			function pmprodonToggleDonationFields() {
				var selectedLevel = parseInt( $levelSelect.val(), 10 );
				if ( pmprodonLevelIds.indexOf( selectedLevel ) !== -1 ) {
					$fields.show();
				} else {
					$fields.hide();
				}
			}

			if ( $levelSelect.length ) {
				$levelSelect.on( 'change', pmprodonToggleDonationFields );
				pmprodonToggleDonationFields();
			}
		} );
	</script>
	<?php
}

add_action( 'pmpro_add_member_fields', 'pmprodon_pmpro_add_member_fields' );

/**
 * Save donation amount and note when adding a member via the Add Member page.
 *
 * Hooked to 'pmpro_add_member_added' which fires after a member is
 * created on the Memberships > Add Member admin page.
 *
 * @since 2.3
 *
 * @param int    $user_id The user ID.
 * @param object $order   The order object created for the new member.
 * @return void
 */
function pmprodon_pmpro_add_member_added( $user_id, $order ) {
	if ( empty( $order ) || empty( $order->id ) ) {
		return;
	}

	if ( isset( $_REQUEST['donation_amount'] ) ) {
		$raw_amount   = trim( sanitize_text_field( $_REQUEST['donation_amount'] ) );
		$float_amount = is_numeric( $raw_amount ) ? floatval( $raw_amount ) : '';
		update_pmpro_membership_order_meta( $order->id, 'donation_amount', $float_amount );
	}

	if ( isset( $_REQUEST['donation_note'] ) ) {
		$donation_note = sanitize_textarea_field( $_REQUEST['donation_note'] );
		update_pmpro_membership_order_meta( $order->id, 'donation_note', $donation_note );
	}
}

add_action( 'pmpro_add_member_added', 'pmprodon_pmpro_add_member_added', 10, 2 );