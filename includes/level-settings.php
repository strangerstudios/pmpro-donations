<?php
/**
 * Add Min Price and Max Price Fields on the edit levels page
 */
function pmprodon_pmpro_membership_level_after_other_settings() {
	global $pmpro_currency_symbol;
	$level_id = intval( $_REQUEST['edit'] );
	$donfields       = pmprodon_get_level_settings( $level_id );			
	$donations              = ( ! isset( $donfields['donations'] ) ) ? 0 : $donfields['donations'];
	$donations_only         = ( ! isset( $donfields['donations_only'] ) ) ? 0 : $donfields['donations_only'];
	$allow_guest_donations  = ( ! isset( $donfields['allow_guest_donations'] ) ) ? 0 : $donfields['allow_guest_donations'];
	$min_price              = ( ! isset( $donfields['min_price'] ) ) ? '' : $donfields['min_price'];
	$max_price       = ( ! isset( $donfields['max_price'] ) ) ? '' : $donfields['max_price'];
	$donations_text  = ( ! isset( $donfields['text'] ) ) ? '' : $donfields['text'];
	$confirmation_message    = ( ! isset( $donfields['confirmation_message'] ) ) ? '' : $donfields['confirmation_message'];
	$dropdown_prices         = ( ! isset( $donfields['dropdown_prices'] ) ) ? '' : $donfields['dropdown_prices'];
	$display_mode            = ( ! isset( $donfields['display_mode'] ) ) ? 'dropdown' : $donfields['display_mode'];
	$donation_note_enabled   = ( ! isset( $donfields['donation_note_enabled'] ) ) ? 0 : $donfields['donation_note_enabled'];
	$donation_note_label     = ( ! isset( $donfields['donation_note_label'] ) ) ? '' : $donfields['donation_note_label'];
	$cover_fees_enabled      = ( ! isset( $donfields['cover_fees_enabled'] ) ) ? 0 : $donfields['cover_fees_enabled'];
	$cover_fees_percentage   = ( ! isset( $donfields['cover_fees_percentage'] ) ) ? '2.9' : $donfields['cover_fees_percentage'];
	$cover_fees_flat         = ( ! isset( $donfields['cover_fees_flat'] ) ) ? '0.30' : $donfields['cover_fees_flat'];
	$reminder_interval       = ( ! isset( $donfields['reminder_interval'] ) ) ? 'none' : $donfields['reminder_interval'];
	$email_template_override = ( ! isset( $donfields['email_template_override'] ) ) ? '' : $donfields['email_template_override'];
	if ( ! empty( $donations ) ) {
		$section_visibility = 'visible';
		$section_activated  = 'true';
	} else {
		$section_visibility = 'hidden';
		$section_activated  = 'false';
	}
?>

<div id="pmpro-donations" class="pmpro_section" data-visibility="<?php echo esc_attr( $section_visibility ); ?>" data-activated="<?php echo esc_attr( $section_activated ); ?>">
	<div class="pmpro_section_toggle">
		<button class="pmpro_section-toggle-button" type="button" aria-expanded="<?php echo $section_visibility === 'hidden' ? 'false' : 'true'; ?>">
			<span class="dashicons dashicons-arrow-<?php echo $section_visibility === 'hidden' ? 'down' : 'up'; ?>-alt2"></span>
			<?php esc_html_e( 'Donations Settings', 'pmpro-donations' ); ?>
		</button>
	</div>
	<div class="pmpro_section_inside" <?php echo $section_visibility === 'hidden' ? 'style="display: none"' : ''; ?>>
		<p><?php _e( 'If donations are enabled, users will be able to set an additional donation amount at checkout. That price will be added to any initial payment you set on this level. You can set the minimum and maxium amount allowed for gifts for this level.', 'pmpro-donations' ); ?></p>
		<table class="donations-settings-table">
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
				<tr id="allow_guest_donations_row" <?php if ( empty( $donations_only ) ) { ?>style="display: none;"<?php } ?>>
					<th scope="row" valign="top"><label for="allow_guest_donations"><?php esc_html_e( 'Allow Guest Donations:', 'pmpro-donations' ); ?></label></th>
					<td>
						<input type="checkbox" id="allow_guest_donations" name="allow_guest_donations" value="1" <?php checked( $allow_guest_donations, '1' ); ?> /> <label for="allow_guest_donations"><?php esc_html_e( 'Allow non-logged-in visitors to donate without creating an account.', 'pmpro-donations' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="donation_min_price"><?php _e( 'Min Amount:', 'pmpro-donations' ); ?></label></th>
					<td>
						<?php echo $pmpro_currency_symbol; ?><input type="text" id="donation_min_price" name="donation_min_price" value="<?php echo esc_attr( pmpro_filter_price_for_text_field( $min_price ) ) ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="donation_max_price"><?php _e( 'Max Amount:', 'pmpro-donations' ); ?></label></th>
					<td>
						<?php echo $pmpro_currency_symbol; ?><input type="text" id="donation_max_price" name="donation_max_price" value="<?php echo esc_attr( pmpro_filter_price_for_text_field( $max_price ) ) ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="dropdown_prices"><?php _e( 'Price Dropdown:', 'pmpro-donations' ); ?></label></th>
					<td>
						<input type="text" id="dropdown_prices" name="dropdown_prices" size="60" value="<?php echo esc_attr( $dropdown_prices ); ?>" /><br /><small><?php _e( "Enter numbers separated by commas to popuplate a dropdown with suggested prices. Include 'other' (all lowercase) in the list to allow users to enter their own amount.", 'pmpro-donations' ); ?></small>
					</td>
				</tr>
				<tr id="display_mode_row" <?php if ( empty( $dropdown_prices ) ) { ?>style="display: none;"<?php } ?>>
					<th scope="row" valign="top"><label><?php esc_html_e( 'Display Mode:', 'pmpro-donations' ); ?></label></th>
					<td>
						<label><input type="radio" name="display_mode" value="buttons" <?php checked( $display_mode, 'buttons' ); ?> /> <?php esc_html_e( 'Buttons', 'pmpro-donations' ); ?></label>
						&nbsp;&nbsp;
						<label><input type="radio" name="display_mode" value="dropdown" <?php checked( $display_mode, 'dropdown' ); ?> /> <?php esc_html_e( 'Dropdown', 'pmpro-donations' ); ?></label>
						<br /><small><?php esc_html_e( 'Choose how donation amounts are displayed at checkout. Buttons show large, clickable tiles. Dropdown shows a classic select menu.', 'pmpro-donations' ); ?></small>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="donations_text"><?php _e( 'Help Text:', 'pmpro-donations' ); ?></label></th>
					<td>
						<?php wp_editor( $donations_text, 'donations_text', array( 'textarea_rows' => 5 ) ); ?>
						<br /><small><?php _e( 'If not blank, this text will override the default text generated to explain the range of donation values accepted.', 'pmpro-donations' ); ?></small>
					</td>
				</tr>
        <tr>
          <th scope="row" valign="top"><label for="confirmation_message"><?php esc_html_e( 'Confirmation text:', 'pmpro-donations' ); ?></label></th>
          <td>
            <?php wp_editor( wp_kses_post( $confirmation_message ), 'confirmation_message', array( 'textarea_rows' => 5 ) ); ?>
            <br /><small><?php esc_html_e( 'If not blank, this text will be shown after the default confirmation text on the membership checkout confirmation page.', 'pmpro-donations' ); ?></small>
          </td>
        </tr>
				<tr>
					<th scope="row" valign="top"><label for="donation_note_enabled"><?php esc_html_e( 'Enable Donation Note:', 'pmpro-donations' ); ?></label></th>
					<td>
						<input type="checkbox" id="donation_note_enabled" name="donation_note_enabled" value="1" <?php checked( $donation_note_enabled, '1' ); ?> /> <label for="donation_note_enabled"><?php esc_html_e( 'Show a text field at checkout for donors to add a note.', 'pmpro-donations' ); ?></label>
					</td>
				</tr>
				<tr id="donation_note_label_row" <?php if ( empty( $donation_note_enabled ) ) { ?>style="display: none;"<?php } ?>>
					<th scope="row" valign="top"><label for="donation_note_label"><?php esc_html_e( 'Donation Note Label:', 'pmpro-donations' ); ?></label></th>
					<td>
						<input type="text" id="donation_note_label" name="donation_note_label" size="40" value="<?php echo esc_attr( $donation_note_label ); ?>" />
						<br /><small><?php esc_html_e( 'Custom label for the donation note field. Defaults to "Donation Note" if blank.', 'pmpro-donations' ); ?></small>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="cover_fees_enabled"><?php esc_html_e( 'Enable Cover Fees:', 'pmpro-donations' ); ?></label></th>
					<td>
						<input type="checkbox" id="cover_fees_enabled" name="cover_fees_enabled" value="1" <?php checked( $cover_fees_enabled, '1' ); ?> /> <label for="cover_fees_enabled"><?php esc_html_e( 'Show a checkbox at checkout for donors to cover processing fees.', 'pmpro-donations' ); ?></label>
					</td>
				</tr>
				<tr id="cover_fees_percentage_row" <?php if ( empty( $cover_fees_enabled ) ) { ?>style="display: none;"<?php } ?>>
					<th scope="row" valign="top"><label for="cover_fees_percentage"><?php esc_html_e( 'Fee Percentage:', 'pmpro-donations' ); ?></label></th>
					<td>
						<input type="text" id="cover_fees_percentage" name="cover_fees_percentage" size="10" value="<?php echo esc_attr( $cover_fees_percentage ); ?>" /> %
						<br /><small><?php esc_html_e( 'The payment gateway percentage fee (e.g. 2.9 for Stripe).', 'pmpro-donations' ); ?></small>
					</td>
				</tr>
				<tr id="cover_fees_flat_row" <?php if ( empty( $cover_fees_enabled ) ) { ?>style="display: none;"<?php } ?>>
					<th scope="row" valign="top"><label for="cover_fees_flat"><?php esc_html_e( 'Fee Flat Amount:', 'pmpro-donations' ); ?></label></th>
					<td>
						<?php echo $pmpro_currency_symbol; ?><input type="text" id="cover_fees_flat" name="cover_fees_flat" size="10" value="<?php echo esc_attr( $cover_fees_flat ); ?>" />
						<br /><small><?php esc_html_e( 'The payment gateway flat fee per transaction (e.g. 0.30 for Stripe).', 'pmpro-donations' ); ?></small>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="reminder_interval"><?php esc_html_e( 'Donation Reminder:', 'pmpro-donations' ); ?></label></th>
					<td>
						<select id="reminder_interval" name="reminder_interval">
							<option value="none" <?php selected( $reminder_interval, 'none' ); ?>><?php esc_html_e( 'None', 'pmpro-donations' ); ?></option>
							<option value="monthly" <?php selected( $reminder_interval, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'pmpro-donations' ); ?></option>
							<option value="quarterly" <?php selected( $reminder_interval, 'quarterly' ); ?>><?php esc_html_e( 'Quarterly', 'pmpro-donations' ); ?></option>
							<option value="annually" <?php selected( $reminder_interval, 'annually' ); ?>><?php esc_html_e( 'Annually', 'pmpro-donations' ); ?></option>
						</select>
						<br /><small><?php esc_html_e( 'Send periodic reminder emails to members encouraging them to donate again. Members can opt out from the email.', 'pmpro-donations' ); ?></small>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="email_template_override"><?php esc_html_e( 'Email Template Override:', 'pmpro-donations' ); ?></label></th>
					<td>
						<input type="text" id="email_template_override" name="email_template_override" size="40" value="<?php echo esc_attr( $email_template_override ); ?>" />
						<br /><small><?php esc_html_e( 'Enter a custom email template name to use for checkout emails on this level (e.g. "donation_checkout"). Leave blank for the default checkout email. Admin emails will use this name with "_admin" appended. Compatible with the Email Templates add-on.', 'pmpro-donations' ); ?></small>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<script>
	jQuery(document).ready(function($) {
		//toggle guest donations row based on donations_only checkbox
		const toggleGuestDonations = () => {
			if($('#donations_only').is(':checked')) {
				$('#allow_guest_donations_row').show();
			} else {
				$('#allow_guest_donations_row').hide();
			}
		};
		toggleGuestDonations();
		$('#donations_only').on('change', function() {
			toggleGuestDonations();
		});

		//toggle display mode row based on dropdown prices
		const toggleDisplayMode = () => {
			if($('#dropdown_prices').val().trim() !== '') {
				$('#display_mode_row').show();
			} else {
				$('#display_mode_row').hide();
			}
		};
		toggleDisplayMode();
		$('#dropdown_prices').on('input', function() {
			toggleDisplayMode();
		});

		//toggle donation note label row based on checkbox
		const toggleNoteLabel = () => {
			if($('#donation_note_enabled').is(':checked')) {
				$('#donation_note_label_row').show();
			} else {
				$('#donation_note_label_row').hide();
			}
		};
		toggleNoteLabel();
		$('#donation_note_enabled').on('change', function() {
			toggleNoteLabel();
		});

		//toggle cover fees fields based on checkbox
		const toggleCoverFees = () => {
			if($('#cover_fees_enabled').is(':checked')) {
				$('#cover_fees_percentage_row').show();
				$('#cover_fees_flat_row').show();
			} else {
				$('#cover_fees_percentage_row').hide();
				$('#cover_fees_flat_row').hide();
			}
		};
		toggleCoverFees();
		$('#cover_fees_enabled').on('change', function() {
			toggleCoverFees();
		});

		//toggle fields based on main donations checkbox
		const toggleDonFields = () => {
			const $donCheckBox = $('#donations');
			const $trs = $('.donations-settings-table tbody tr');
			if($donCheckBox.is(':checked')) {
				$trs.show();
				// Re-apply conditional sub-toggles so dependent rows
				// respect their own checkbox/field state.
				toggleGuestDonations();
				toggleDisplayMode();
				toggleNoteLabel();
				toggleCoverFees();
			} else {
				$trs.hide();
			}
			$donCheckBox.closest('tr').show();
		};
		toggleDonFields();
		$('#donations').on('change', function() {
			toggleDonFields();
		});
	});
</script>
<?php
}
add_action( 'pmpro_membership_level_before_content_settings', 'pmprodon_pmpro_membership_level_after_other_settings' );

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
	if ( ! empty( $_REQUEST['allow_guest_donations'] ) ) {
		$allow_guest_donations = 1;
	} else {
		$allow_guest_donations = 0;
	}
	$min_price	          = preg_replace( '[^0-9\.]', '', $_REQUEST['donation_min_price'] );
	$max_price	          = preg_replace( '[^0-9\.]', '', $_REQUEST['donation_max_price'] );
	$text	              = wp_kses_post( wp_unslash( $_REQUEST['donations_text'] ) );
	$confirmation_message = wp_kses_post( wp_unslash( $_REQUEST['confirmation_message'] ) );
	$dropdown_prices      = sanitize_text_field( $_REQUEST['dropdown_prices'] );
	$display_mode         = isset( $_REQUEST['display_mode'] ) && $_REQUEST['display_mode'] === 'buttons' ? 'buttons' : 'dropdown';

	if ( ! empty( $_REQUEST['donation_note_enabled'] ) ) {
		$donation_note_enabled = 1;
	} else {
		$donation_note_enabled = 0;
	}
	$donation_note_label = isset( $_REQUEST['donation_note_label'] ) ? sanitize_text_field( $_REQUEST['donation_note_label'] ) : '';

	if ( ! empty( $_REQUEST['cover_fees_enabled'] ) ) {
		$cover_fees_enabled = 1;
	} else {
		$cover_fees_enabled = 0;
	}
	$cover_fees_percentage = isset( $_REQUEST['cover_fees_percentage'] ) ? sanitize_text_field( $_REQUEST['cover_fees_percentage'] ) : '2.9';
	$cover_fees_flat       = isset( $_REQUEST['cover_fees_flat'] ) ? sanitize_text_field( $_REQUEST['cover_fees_flat'] ) : '0.30';

	$valid_intervals    = array( 'none', 'monthly', 'quarterly', 'annually' );
	$reminder_interval  = isset( $_REQUEST['reminder_interval'] ) ? sanitize_text_field( $_REQUEST['reminder_interval'] ) : 'none';
	if ( ! in_array( $reminder_interval, $valid_intervals, true ) ) {
		$reminder_interval = 'none';
	}

	$email_template_override = isset( $_REQUEST['email_template_override'] ) ? sanitize_text_field( $_REQUEST['email_template_override'] ) : '';

	update_option(
		'pmprodon_' . $level_id, array(
			'donations'                => $donations,
			'donations_only'           => $donations_only,
			'allow_guest_donations'    => $allow_guest_donations,
			'min_price'                => $min_price,
			'max_price'                => $max_price,
			'dropdown_prices'          => $dropdown_prices,
			'display_mode'             => $display_mode,
			'text'                     => $text,
			'confirmation_message'     => $confirmation_message,
			'donation_note_enabled'    => $donation_note_enabled,
			'donation_note_label'      => $donation_note_label,
			'cover_fees_enabled'       => $cover_fees_enabled,
			'cover_fees_percentage'    => $cover_fees_percentage,
			'cover_fees_flat'          => $cover_fees_flat,
			'reminder_interval'        => $reminder_interval,
			'email_template_override'  => $email_template_override,
		)
	);
}
add_action( 'pmpro_save_membership_level', 'pmprodon_pmpro_save_membership_level' );
