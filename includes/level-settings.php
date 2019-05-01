<?php
/**
 * Add Min Price and Max Price Fields on the edit levels page
 */
function pmprodon_pmpro_membership_level_after_other_settings() {
	global $pmpro_currency_symbol;
	$level_id = intval( $_REQUEST['edit'] );
	if ( $level_id > 0 ) {
		$donfields       = get_option(
			'pmprodon_' . $level_id, array(
				'donations'       => 0,
				'min_price'       => '',
				'max_price'       => '',
				'dropdown_prices' => '',
				'text'            => '',
			)
		);
		$donations       = ( ! isset( $donfields['donations'] ) ) ? 0 : $donfields['donations'];
		$min_price       = ( ! isset( $donfields['min_price'] ) ) ? '' : $donfields['min_price'];
		$max_price       = ( ! isset( $donfields['max_price'] ) ) ? '' : $donfields['max_price'];
		$donations_text  = ( ! isset( $donfields['text'] ) ) ? '' : $donfields['text'];
		$dropdown_prices = ( ! isset( $donfields['dropdown_prices'] ) ) ? '' : $donfields['dropdown_prices'];
	} else {
		$donations       = 0;
		$min_price       = '';
		$max_price       = '';
		$donations_text  = '';
		$dropdown_prices = '';
	}
?>
<h3 class="topborder"><?php _e( 'Donations', 'pmpro-donations' ); ?></h3>
<p><?php _e( 'If donations are enabled, users will be able to set an additional donation amount at checkout. That price will be added to any initial payment you set on this level. You can set the minimum and maxium amount allowed for gifts for this level.', 'pmpro-donations' ); ?></p>
<table>
<tbody class="form-table">
	<tr>
		<th scope="row" valign="top"><label for="donations"><?php _e( 'Enable:', 'pmpro-donations' ); ?></label></th>
		<td>
			<input type="checkbox" id="donations" name="donations" value="1" <?php checked( $donations, '1' ); ?> /> <label for="donations"><?php _e( 'Enable Donations', 'pmpro-donations' ); ?></label>
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top"><label for="donation_min_price"><?php _e( 'Min Amount:', 'pmpro-donations' ); ?></label></th>
		<td>
			<?php echo $pmpro_currency_symbol; ?><input type="text" id="donation_min_price" name="donation_min_price" value="<?php echo esc_attr( $min_price ); ?>" />
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top"><label for="donation_max_price"><?php _e( 'Max Amount:', 'pmpro-donations' ); ?></label></th>
		<td>
			<?php echo $pmpro_currency_symbol; ?><input type="text" id="donation_max_price" name="donation_max_price" value="<?php echo esc_attr( $max_price ); ?>" />
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top"><label for="dropdown_prices"><?php _e( 'Price Dropdown:', 'pmpro-donations' ); ?></label></th>
		<td>
			<input type="text" id="dropdown_prices" name="dropdown_prices" size="60" value="<?php echo esc_attr( $dropdown_prices ); ?>" /><br /><small><?php _e( "Enter numbers separated by commas to popuplate a dropdown with suggested prices. Include 'other' (all lowercase) in the list to allow users to enter their own amount.", 'pmpro-donations' ); ?></small>
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top"><label for="donations_text"><?php _e( 'Help Text:', 'pmpro-donations' ); ?></label></th>
		<td>
			<textarea id="donations_text" name="donations_text" rows="5" cols="60"><?php echo esc_textarea( $donations_text ); ?></textarea>
			<br /><small><?php _e( 'If not blank, this text will override the default text generated to explain the range of donation values accepted.', 'pmpro-donations' ); ?></small>
		</td>
	</tr>
</tbody>
</table>
<?php
}
add_action( 'pmpro_membership_level_after_other_settings', 'pmprodon_pmpro_membership_level_after_other_settings' );

/**
 * Save level cost text when the level is saved/added
 */
function pmprodon_pmpro_save_membership_level( $level_id ) {
	if ( ! empty( $_REQUEST['donations'] ) ) {
		$donations = 1;
	} else {
		$donations = 0;
	}
	$min_price       = preg_replace( '[^0-9\.]', '', $_REQUEST['donation_min_price'] );
	$max_price       = preg_replace( '[^0-9\.]', '', $_REQUEST['donation_max_price'] );
	$text            = $_REQUEST['donations_text'];
	$dropdown_prices = $_REQUEST['dropdown_prices'];

	update_option(
		'pmprodon_' . $level_id, array(
			'donations'       => $donations,
			'min_price'       => $min_price,
			'max_price'       => $max_price,
			'text'            => $text,
			'dropdown_prices' => $dropdown_prices,
		)
	);
}
add_action( 'pmpro_save_membership_level', 'pmprodon_pmpro_save_membership_level' );