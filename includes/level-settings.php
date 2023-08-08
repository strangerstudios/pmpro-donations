<?php
/**
 * Add Min Price and Max Price Fields on the edit levels page
 */
function pmprodon_pmpro_membership_level_after_other_settings() {
	global $pmpro_currency_symbol;
	$level_id = intval( $_REQUEST['edit'] );
	$donfields       = pmprodon_get_level_settings( $level_id );			
	$donations       = ( ! isset( $donfields['donations'] ) ) ? 0 : $donfields['donations'];
	$donations_only  = ( ! isset( $donfields['donations_only'] ) ) ? 0 : $donfields['donations_only'];
	$min_price       = ( ! isset( $donfields['min_price'] ) ) ? '' : $donfields['min_price'];
	$max_price       = ( ! isset( $donfields['max_price'] ) ) ? '' : $donfields['max_price'];
	$donations_text  = ( ! isset( $donfields['text'] ) ) ? '' : $donfields['text'];
	$dropdown_prices = ( ! isset( $donfields['dropdown_prices'] ) ) ? '' : $donfields['dropdown_prices'];
	$donation_placeholder = ( ! isset( $donfields['donation_placeholder'] ) ) ? '' : $donfields['donation_placeholder'];
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
		<th scope="row" valign="top"><label for="donations_only"><?php _e( 'Donations-Only Level:', 'pmpro-donations' ); ?></label></th>
		<td>
			<input type="checkbox" id="donations_only" name="donations_only" value="1" <?php checked( $donations_only, '1' ); ?> /> <label for="donations_only"><?php _e( 'Check to have existing members NOT switched to this level at checkout.', 'pmpro-donations' ); ?></label>
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top"><label for="donation_min_price"><?php _e( 'Min Amount:', 'pmpro-donations' ); ?></label></th>
		<td>
			<?php echo $pmpro_currency_symbol; ?><input type="number" step="0.01" min="0.01" id="donation_min_price" name="donation_min_price" value="<?php echo esc_attr( $min_price ); ?>" />
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top"><label for="donation_max_price"><?php _e( 'Max Amount:', 'pmpro-donations' ); ?></label></th>
		<td>
			<?php echo $pmpro_currency_symbol; ?><input type="number" step="0.01" min="0.01" id="donation_max_price" name="donation_max_price" value="<?php echo esc_attr( $max_price ); ?>" />
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top"><label for="donation_placeholder"><?php _e( 'Placeholder:', 'pmpro-donations' ); ?></label></th>
		<td>
			<input type="text" id="donation_placeholder" name="donation_placeholder" value="<?php echo esc_attr( $donation_placeholder ); ?>" />
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
			<textarea id="donations_text" name="donations_text" rows="5" cols="60"><?php echo  wp_unslash( esc_textarea( $donations_text ) ); ?></textarea>
			<br /><small><?php _e( 'If not blank, this text will override the default text generated to explain the range of donation values accepted. Wrap text among HTML p tags if you need help text splitted into paragraphs.', 'pmpro-donations' ); ?></small>
		</td>
	</tr>
</tbody>
</table>
<script>
	jQuery(document).ready(function($) {
		/**
		 * Watch min and max amount fields and validate that max is greater than min.
		 *
		 * Since TBD
		 */
		$('input[type="number"]').on('change', ev => {
			const $td = $('#donation_max_price').closest('td');
			if( parseFloat($('#donation_min_price').val()) > parseFloat($('#donation_max_price').val()) ) {
				$('#donation_min_price').css('border-color', 'red');
				$('#donation_max_price').css('border-color', 'red');
				emptyMessage($td);
				$td.append($('<br/>'), $('<small/>').css('padding-left', '10px').text('<?php _e( 'Max Amount must be greater than Min Amount', 'pmpro-donations' ); ?>'));
				toggleSubmitButtonDisableAttribute($td, true);
			} else {
				$('#donation_min_price, #donation_max_price').removeAttr('style');
				emptyMessage($td);
				toggleSubmitButtonDisableAttribute($td, false);
			}
		});

		/**
		 * Empty the message in the given td.
		 *
		 * @since TBD
		 */
		const emptyMessage = $td => {
			$td.find('small').remove();
			$td.find('br').remove();
		}

		/**
		 * Toggle the disabled attribute on the level form submit  button accordingly
		 *
		 * @since TBD
		 */
		const toggleSubmitButtonDisableAttribute = ($td, val) => {
			$td.closest('form').find('input[type="submit"]').prop('disabled', () => val);
		}
	});
</script>
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
	if ( ! empty( $_REQUEST['donations_only'] ) ) {
		$donations_only = 1;
	} else {
		$donations_only = 0;
	}
	$min_price       = preg_replace( '[^0-9\.]', '', $_REQUEST['donation_min_price'] );
	//if min price is blank, set it to 1
	if( !$min_price ) {
		$min_price = 1;
	}
	$max_price       = preg_replace( '[^0-9\.]', '', $_REQUEST['donation_max_price'] );
	$text            = $_REQUEST['donations_text'];
	$dropdown_prices = $_REQUEST['dropdown_prices'];
	$donation_placeholder = $_REQUEST['donation_placeholder'];
	if( $min_price > $max_price ) {
		//this is validated in the front, if reach here there's a malicious user trying to break it , should we log it  ?
		return;
	}

	update_option(
		'pmprodon_' . $level_id, array(
			'donations'       => $donations,
			'donations_only'  => $donations_only,
			'min_price'       => $min_price,
			'max_price'       => $max_price,
			'text'            => $text,
			'dropdown_prices' => $dropdown_prices,
			'donation_placeholder' => $donation_placeholder,
		)
	);
}
add_action( 'pmpro_save_membership_level', 'pmprodon_pmpro_save_membership_level' );