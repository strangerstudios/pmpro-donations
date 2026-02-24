<?php
/**
 * Update donation amount if a dropdown value is used
 */
function pmprodon_init_dropdown_values() {

	if ( ! empty( $_REQUEST['donation_dropdown'] ) && $_REQUEST['donation_dropdown'] != 'other' ) {
		$_REQUEST['donation'] = sanitize_text_field( $_REQUEST['donation_dropdown'] );
	}

	if ( ! empty( $_GET['donation_dropdown'] ) && $_GET['donation_dropdown'] != 'other' ) {
		$_GET['donation'] = sanitize_text_field( $_GET['donation_dropdown'] );
	}

	if ( ! empty( $_POST['donation_dropdown'] ) && $_POST['donation_dropdown'] != 'other' ) {
		$_POST['donation'] = sanitize_text_field( $_POST['donation_dropdown'] );
	}
}
add_action( 'pmpro_checkout_preheader_before_get_level_at_checkout', 'pmprodon_init_dropdown_values', 1 );

/**
 * Render the guest/account checkout toggle for donation-only levels
 * that allow guest donations.
 *
 * Only shown to non-logged-in users on levels with both donations_only
 * and allow_guest_donations enabled. Logged-in users see the standard
 * checkout flow.
 *
 * @since 2.3
 */
function pmprodon_render_guest_checkout_toggle() {
	global $pmpro_level;

	// Only show for non-logged-in users.
	if ( is_user_logged_in() ) {
		return;
	}

	// Only show for donation-only levels with guest donations enabled.
	if ( empty( $pmpro_level->id ) ) {
		return;
	}

	$settings = pmprodon_get_level_settings( $pmpro_level->id );
	if ( empty( $settings['donations'] ) || empty( $settings['donations_only'] ) || empty( $settings['allow_guest_donations'] ) ) {
		return;
	}

	// Block guest checkout for recurring levels.
	if ( ! empty( $pmpro_level->billing_amount ) && (float) $pmpro_level->billing_amount > 0 ) {
		return;
	}

	$guest_selected = ! empty( $_REQUEST['pmprodon_guest'] ) && $_REQUEST['pmprodon_guest'] === '1';
	?>
	<fieldset id="pmpro_form_fieldset-guest-checkout" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_form_fieldset-guest-checkout' ) ); ?>">
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
				<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
					<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Checkout Method', 'pmpro-donations' ); ?></h2>
				</legend>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field' ) ); ?>">
						<label>
							<input type="radio" name="pmprodon_guest" value="1" <?php checked( $guest_selected ); ?> />
							<?php esc_html_e( 'Donate as Guest', 'pmpro-donations' ); ?>
						</label>
						<br />
						<label>
							<input type="radio" name="pmprodon_guest" value="0" <?php checked( ! $guest_selected ); ?> />
							<?php esc_html_e( 'Create an Account', 'pmpro-donations' ); ?>
						</label>
					</div>
				</div>
			</div>
		</div>
	</fieldset>
	<script>
		jQuery(document).ready(function($) {
			function pmprodon_toggleGuestFields() {
				var isGuest = $('input[name="pmprodon_guest"]:checked').val() === '1';
				if (isGuest) {
					// Hide username and password fields.
					$('#pmpro_user_fields-username, #pmpro_user_fields-password').closest('.<?php echo esc_js( pmpro_get_element_class( 'pmpro_form_field' ) ); ?>').hide();
					// Also hide by ID patterns used in PMPro.
					$('#username, #password, #password2, #password2_confirm').closest('div').hide();
					$('#username').closest('.' + '<?php echo esc_js( pmpro_get_element_class( 'pmpro_form_field' ) ); ?>').hide();
					$('#password').closest('.' + '<?php echo esc_js( pmpro_get_element_class( 'pmpro_form_field' ) ); ?>').hide();
					$('#password2').closest('.' + '<?php echo esc_js( pmpro_get_element_class( 'pmpro_form_field' ) ); ?>').hide();
					// Set placeholder values for hidden fields.
					if ($('#username').length && !$('#username').val()) {
						$('#username').val('guest_donor_' + Math.random().toString(36).substring(2, 10));
					}
					if ($('#password').length && !$('#password').val()) {
						var guestPwd = Math.random().toString(36).substring(2, 22) + Math.random().toString(36).substring(2, 22);
						$('#password').val(guestPwd);
						$('#password2, #password2_confirm').val(guestPwd);
					}
				} else {
					// Show username and password fields.
					$('#pmpro_user_fields-username, #pmpro_user_fields-password').closest('.<?php echo esc_js( pmpro_get_element_class( 'pmpro_form_field' ) ); ?>').show();
					$('#username').closest('.' + '<?php echo esc_js( pmpro_get_element_class( 'pmpro_form_field' ) ); ?>').show();
					$('#password').closest('.' + '<?php echo esc_js( pmpro_get_element_class( 'pmpro_form_field' ) ); ?>').show();
					$('#password2').closest('.' + '<?php echo esc_js( pmpro_get_element_class( 'pmpro_form_field' ) ); ?>').show();
					$('#username, #password, #password2, #password2_confirm').closest('div').show();
					// Clear auto-generated values if user switches back.
					if ($('#username').val().indexOf('guest_donor_') === 0) {
						$('#username').val('');
					}
					if ($('#password').val().length > 30) {
						$('#password').val('');
						$('#password2, #password2_confirm').val('');
					}
				}
			}

			$('input[name="pmprodon_guest"]').on('change', function() {
				pmprodon_toggleGuestFields();
			});

			// Run on page load.
			pmprodon_toggleGuestFields();
		});
	</script>
	<?php
}
add_action( 'pmpro_checkout_after_pricing_fields', 'pmprodon_render_guest_checkout_toggle' );

/**
 * Show form at checkout.
 */
function pmprodon_pmpro_checkout_after_user_fields() {
	global $pmpro_currency_symbol, $pmpro_level, $gateway, $pmpro_review;

	// get variable pricing info
	$donfields = get_option( 'pmprodon_' . $pmpro_level->id );

	// no variable pricing? just return
	if ( empty( $donfields ) || empty( $donfields['donations'] ) ) {
		return;
	}

	// okay, now we're showing the form
	$min_price = $donfields['min_price'];
	$max_price = $donfields['max_price'];
	$dropdown_prices = $donfields['dropdown_prices'];
	$display_mode = isset( $donfields['display_mode'] ) ? $donfields['display_mode'] : 'dropdown';

	if ( isset( $_REQUEST['donation'] ) ) {
		$donation = preg_replace( '/[^0-9\.]/', '', $_REQUEST['donation'] );
	} elseif ( ! empty( $min_price ) ) {
		$donation = $min_price;
	} else {
		$donation = '';
	}

	?>
	<fieldset id="pmpro_form_fieldset-donation" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_form_fieldset-donation' ) ); ?>">
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
				<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
					<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Make a Gift', 'pmpro-donations' ); ?></h2>
				</legend>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-donation', 'pmpro_form_field-donation' ) ); ?>">
						<label for="donation" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'Donation Amount', 'pmpro-donations' ); ?></label>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields-inline' ) ); ?>">
							<?php
							// check for dropdown prices
							if ( ! empty( $dropdown_prices ) ) {
								// turn into an array
								$dropdown_prices = str_replace( ' ', '', $dropdown_prices );
								$dropdown_prices = explode( ',', $dropdown_prices );

								// check for other option
								$pmprodon_allow_other = array_search( 'other', $dropdown_prices );
								if ( $pmprodon_allow_other !== false ) {
									unset( $dropdown_prices[ $pmprodon_allow_other ] );
									$pmprodon_allow_other = true;
								}

								// sort numeric prices
								sort( $dropdown_prices );

								if ( $display_mode === 'buttons' ) {
									// Default to first price if no donation selected yet.
									if ( empty( $donation ) && ! empty( $dropdown_prices ) ) {
										$donation = $dropdown_prices[0];
									}
									// Determine which button is currently selected.
									$is_other_selected = ! empty( $donation ) && ! in_array( $donation, $dropdown_prices );
									?>
									<div class="pmprodon_buttons_grid" role="radiogroup" aria-label="<?php esc_attr_e( 'Donation Amount', 'pmpro-donations' ); ?>">
										<?php foreach ( $dropdown_prices as $price ) {
											$is_checked = ! $is_other_selected && $price == $donation;
											?>
											<label class="pmprodon_button <?php echo $is_checked ? 'pmprodon_button--active' : ''; ?>" <?php if ( $pmpro_review ) { ?>aria-disabled="true"<?php } ?>>
												<input type="radio" name="donation_dropdown" value="<?php echo esc_attr( $price ); ?>" <?php checked( $is_checked ); ?> <?php if ( $pmpro_review ) { ?>disabled="disabled"<?php } ?> class="pmprodon_button_input" />
												<span class="pmprodon_button_label"><?php echo esc_html( pmpro_formatPrice( (float) $price ) ); ?></span>
											</label>
										<?php } ?>
										<?php if ( $pmprodon_allow_other ) { ?>
											<label class="pmprodon_button pmprodon_button--other <?php echo $is_other_selected ? 'pmprodon_button--active' : ''; ?>" <?php if ( $pmpro_review ) { ?>aria-disabled="true"<?php } ?>>
												<input type="radio" name="donation_dropdown" value="other" <?php checked( $is_other_selected ); ?> <?php if ( $pmpro_review ) { ?>disabled="disabled"<?php } ?> class="pmprodon_button_input" />
												<span class="pmprodon_button_label"><?php esc_html_e( 'Other', 'pmpro-donations' ); ?></span>
											</label>
										<?php } ?>
									</div>
									<?php
								} else {
									// Classic dropdown mode.
									?>
									<select id="donation_dropdown" name="donation_dropdown" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-select pmpro_alter_price' ) ); ?>" <?php if ( $pmpro_review ) { ?>disabled="disabled"<?php } ?>>
										<?php
										foreach ( $dropdown_prices as $price ) {
											?>
											<option <?php selected( $price, $donation ); ?> value="<?php echo esc_attr( $price ); ?>"><?php echo esc_html( pmpro_formatPrice( (float) $price ) ); ?></option>
											<?php
										}
										if ( $pmprodon_allow_other ) {
											?>
											<option value="other" <?php selected( true, ! empty( $donation ) && ! in_array( $donation, $dropdown_prices ) ); ?>>
												<?php esc_html_e( 'Other', 'pmpro-donations' ) ?>
											</option>
										<?php } ?>
									</select>
									<?php
								}
							}
							?>
							<span id="pmprodon_donation_input" <?php if ( ! empty( $pmprodon_allow_other ) && ( empty( $_REQUEST['donation_dropdown'] ) || $_REQUEST['donation_dropdown'] != 'other' ) ) { ?>style="display: none;"<?php } ?>>
								<?php echo $pmpro_currency_symbol; ?> <input class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text pmpro_alter_price' ) ); ?>" autocomplete="off" type="text" id="donation" name="donation" size="10" value="<?php echo esc_attr( $donation ); ?>" <?php if ( $pmpro_review ) { ?>disabled="disabled"<?php } ?> />
								<?php if ( $pmpro_review ) { ?>
									<input type="hidden" name="donation" value="<?php echo esc_attr( $donation ); ?>" />
								<?php } ?>
							</span>
						</div> <!-- end pmpro_form_fields-inline -->
						<?php
						if ( empty( $pmpro_review ) ) {
							?>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_hint' ) ); ?>">
							<?php
							if ( ! empty( $donfields['text'] ) ) {
								echo wp_kses_post( wpautop( $donfields['text'] ) );
							} elseif ( ! empty( $donfields['min_price'] ) && empty( $donfields['max_price'] ) ) {
								echo '<p>' . esc_html( sprintf( __( 'Enter an amount %s or greater', 'pmpro-donations' ), pmpro_formatPrice( $donfields['min_price'] ) ) ) . '</p>';
							} elseif ( ! empty( $donfields['max_price'] ) && empty( $donfields['min_price'] ) ) {
								echo '<p>' . esc_html( sprintf( __( 'Enter an amount %s or less', 'pmpro-donations' ), pmpro_formatPrice( $donfields['max_price'] ) ) ) . '</p>';
							} elseif ( ! empty( $donfields['max_price'] ) && ! empty( $donfields['min_price'] ) ) {
								echo '<p>' . esc_html( sprintf( __( 'Enter an amount between %1$s and %2$s', 'pmpro-donations' ), pmpro_formatPrice( $donfields['min_price'] ), pmpro_formatPrice( $donfields['max_price'] ) ) ) . '</p>';
							}
							?>
							</div> <!-- end pmpro_form_hint -->
							<?php
						}
						?>
					</div> <!-- end pmpro_form_field-donation -->
					<?php
					// Donation note textarea.
					$donfields_full = pmprodon_get_level_settings( $pmpro_level->id );
					if ( ! empty( $donfields_full['donation_note_enabled'] ) ) {
						$donation_note_label = ! empty( $donfields_full['donation_note_label'] ) ? $donfields_full['donation_note_label'] : __( 'Donation Note', 'pmpro-donations' );
						$donation_note_value = isset( $_REQUEST['donation_note'] ) ? sanitize_textarea_field( $_REQUEST['donation_note'] ) : '';
						?>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-donation-note', 'pmpro_form_field-donation-note' ) ); ?>">
							<label for="donation_note" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php echo esc_html( $donation_note_label ); ?></label>
							<textarea id="donation_note" name="donation_note" rows="3" cols="50" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-textarea' ) ); ?>" <?php if ( $pmpro_review ) { ?>disabled="disabled"<?php } ?>><?php echo esc_textarea( $donation_note_value ); ?></textarea>
							<?php if ( $pmpro_review && ! empty( $donation_note_value ) ) { ?>
								<input type="hidden" name="donation_note" value="<?php echo esc_attr( $donation_note_value ); ?>" />
							<?php } ?>
						</div> <!-- end pmpro_form_field-donation-note -->
						<?php
					}

					// Cover fees checkbox.
					if ( ! empty( $donfields_full['cover_fees_enabled'] ) ) {
						$cover_fees_percentage = (float) $donfields_full['cover_fees_percentage'];
						$cover_fees_flat       = (float) $donfields_full['cover_fees_flat'];
						$initial_fee           = pmprodon_calculate_cover_fee( (float) $donation, $cover_fees_percentage, $cover_fees_flat );
						$cover_fees_checked    = ! empty( $_REQUEST['cover_fees'] ) ? true : false;
						?>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-cover-fees', 'pmpro_form_field-cover-fees' ) ); ?> pmprodon_cover_fees_field">
							<label for="cover_fees" class="pmprodon_cover_fees_label">
								<input type="checkbox" id="cover_fees" name="cover_fees" value="1" <?php checked( $cover_fees_checked ); ?> <?php if ( $pmpro_review ) { ?>disabled="disabled"<?php } ?> />
								<span id="pmprodon_cover_fees_text">
									<?php
									echo esc_html(
										sprintf(
											/* translators: %s: the calculated fee amount */
											__( 'Add %s to cover processing fees', 'pmpro-donations' ),
											pmpro_formatPrice( $initial_fee )
										)
									);
									?>
								</span>
							</label>
							<?php if ( $pmpro_review && $cover_fees_checked ) { ?>
								<input type="hidden" name="cover_fees" value="1" />
							<?php } ?>
						</div> <!-- end pmpro_form_field-cover-fees -->
						<?php
					}
					?>
				</div> <!-- end pmpro_form_fields -->
			</div> <!-- end pmpro_card_content -->
		</div> <!-- end pmpro_card -->
	</fieldset> <!-- end pmpro_form_fieldset-donation -->
	<script>
		//some vars for keeping track of whether or not we show billing
		var pmpro_gateway_billing = <?php if ( in_array( $gateway, array( 'paypalexpress', 'twocheckout' ) ) !== false ) { echo'false';	} else { echo 'true'; } ?>;
		var pmpro_pricing_billing = <?php if ( ! pmpro_isLevelFree( $pmpro_level ) ) { echo 'true';	} else { echo 'false'; } ?>;
		var pmpro_donation_billing = pmpro_pricing_billing;
		var pmprodon_display_mode = '<?php echo esc_js( $display_mode ); ?>';
		var pmprodon_cover_fees_enabled = <?php echo ! empty( $donfields_full['cover_fees_enabled'] ) ? 'true' : 'false'; ?>;
		var pmprodon_cover_fees_percentage = <?php echo (float) ( ! empty( $donfields_full['cover_fees_percentage'] ) ? $donfields_full['cover_fees_percentage'] : 0 ); ?>;
		var pmprodon_cover_fees_flat = <?php echo (float) ( ! empty( $donfields_full['cover_fees_flat'] ) ? $donfields_full['cover_fees_flat'] : 0 ); ?>;

		//this script will hide show billing fields based on the price set
		jQuery(document).ready(function() {
			if ( pmprodon_display_mode === 'buttons' ) {
				// Button mode: handle radio button changes.
				jQuery('.pmprodon_button_input').on('change', function() {
					var $label = jQuery(this).closest('.pmprodon_button');
					// Remove active class from all buttons.
					jQuery('.pmprodon_button').removeClass('pmprodon_button--active');
					// Add active class to selected button.
					$label.addClass('pmprodon_button--active');
					// Toggle the other input.
					pmprodon_toggleOther();
					// If not 'other', update the donation field with the selected value.
					if ( jQuery(this).val() !== 'other' ) {
						jQuery('#donation').val( jQuery(this).val() );
					}
					pmprodon_checkForFree();
				});
			} else {
				// Dropdown mode: bind to select change.
				jQuery('#donation_dropdown').change(function() {
					pmprodon_toggleOther();
					// If we changed to a non-other value, update the donation field.
					if ( jQuery( '#donation_dropdown' ).val() !== 'other' ) {
						jQuery( '#donation' ).val( jQuery( '#donation_dropdown' ).val() );
					}
					pmprodon_checkForFree();
				});
			}

			//bind check to price field
			var pmprodon_price_timer;
			jQuery('#donation').bind('keyup change', function() {
				pmprodon_price_timer = setTimeout(pmprodon_checkForFree, 500);
			});

			if(jQuery('input[name=gateway]'))
			{
				jQuery('input[name=gateway]').bind('click', function() {
					pmprodon_price_timer = setTimeout(pmprodon_checkForFree, 500);
				});
			}

			//update cover fees when checkbox is toggled
			jQuery('#cover_fees').on('change', function() {
				pmprodon_updateCoverFees();
			});

			//check when page loads too
			pmprodon_toggleOther();
			pmprodon_checkForFree();
		});

		function pmprodon_toggleOther() {
			var donation_dropdown_val;

			if ( pmprodon_display_mode === 'buttons' ) {
				// In button mode, get the checked radio value.
				var $checked = jQuery('.pmprodon_button_input:checked');
				if ( ! $checked.length ) {
					return;
				}
				donation_dropdown_val = $checked.val();
			} else {
				// In dropdown mode, get the select value.
				if ( ! jQuery('#donation_dropdown').length ) {
					return;
				}
				donation_dropdown_val = jQuery('#donation_dropdown').val();
			}

			if ( donation_dropdown_val == 'other' ) {
				jQuery('#pmprodon_donation_input').show();
			} else {
				jQuery('#pmprodon_donation_input').hide();
			}
		}

		function pmprodon_checkForFree() {
			var donation = parseFloat(jQuery('#donation').val());

			//does the gateway require billing?
			if(jQuery('input[name=gateway]').length) {
				var no_billing_gateways = ['paypalexpress', 'twocheckout', 'check', 'paypalstandard'];
				var gateway = jQuery('input[name=gateway]:checked').val();
				if(no_billing_gateways.indexOf(gateway) > -1)
					pmpro_gateway_billing = false;
				else
					pmpro_gateway_billing = true;
			}

			//is there a donation?
			if(donation || pmpro_pricing_billing)
				pmpro_donation_billing = true;
			else
				pmpro_donation_billing = false;

			//figure out if we should show the billing fields
			if(pmpro_gateway_billing && pmpro_donation_billing) {
				jQuery('#pmpro_billing_address_fields').show();
				jQuery('#pmpro_payment_information_fields').show();
				pmpro_require_billing = true;
			} else if ( 'check' !== gateway ) {
				jQuery('#pmpro_billing_address_fields').hide();
				jQuery('#pmpro_payment_information_fields').hide();
				pmpro_require_billing = false;
			}

			// Update cover fees when donation changes.
			pmprodon_updateCoverFees();
		}

		function pmprodon_updateCoverFees() {
			if ( ! pmprodon_cover_fees_enabled ) {
				return;
			}
			var $text = jQuery('#pmprodon_cover_fees_text');
			if ( ! $text.length ) {
				return;
			}
			var donation = parseFloat(jQuery('#donation').val()) || 0;
			var fee = 0;
			if ( donation > 0 && pmprodon_cover_fees_percentage < 100 ) {
				fee = ( donation + pmprodon_cover_fees_flat ) / ( 1 - pmprodon_cover_fees_percentage / 100 ) - donation;
				fee = Math.round( fee * 100 ) / 100;
			}
			// Format fee for display. Use the currency symbol from the page.
			var feeFormatted = fee.toFixed(2);
			<?php
			// Pass the currency symbol and position to JS for fee formatting.
			$currency_position = apply_filters( 'pmpro_currency_position', 'left' );
			?>
			var currencySymbol = '<?php echo esc_js( $pmpro_currency_symbol ); ?>';
			var currencyPosition = '<?php echo esc_js( $currency_position ); ?>';
			var priceStr;
			if ( currencyPosition === 'right' ) {
				priceStr = feeFormatted + currencySymbol;
			} else {
				priceStr = currencySymbol + feeFormatted;
			}
			/* translators: %s: the calculated fee amount */
			var template = '<?php echo esc_js( sprintf( __( 'Add %s to cover processing fees', 'pmpro-donations' ), '{FEE}' ) ); ?>';
			$text.text( template.replace( '{FEE}', priceStr ) );
		}
	</script>
	<?php
}
add_action( 'pmpro_checkout_after_user_fields', 'pmprodon_pmpro_checkout_after_user_fields' );

/**
 * Enable payment gateways for free levels that have donations enabled.
 *
 * If a free level has donations enabled with a min_price > 0 or
 * dropdown_prices containing numeric values > 0, mark the level
 * as non-free so that payment gateways render on checkout.
 *
 * @since 2.3
 *
 * @param bool   $is_free Whether the level is free.
 * @param object $level   The level object being checked.
 * @return bool Whether the level should be treated as free.
 */
function pmprodon_enable_payments_for_free_level_donations( $is_free, $level ) {
	// Only act on free levels during checkout.
	if ( ! $is_free || ! pmpro_is_checkout() ) {
		return $is_free;
	}

	// Get donation settings for this level.
	$donfields = pmprodon_get_level_settings( $level->id );

	// If donations are not enabled, leave as-is.
	if ( empty( $donfields['donations'] ) ) {
		return $is_free;
	}

	// Check if min_price requires a donation.
	if ( ! empty( $donfields['min_price'] ) && (float) $donfields['min_price'] > 0 ) {
		return false;
	}

	// Check if dropdown_prices contain numeric values > 0.
	if ( ! empty( $donfields['dropdown_prices'] ) ) {
		$prices = str_replace( ' ', '', $donfields['dropdown_prices'] );
		$prices = explode( ',', $prices );
		$has_numeric = false;
		foreach ( $prices as $price ) {
			if ( $price !== 'other' && (float) $price > 0 ) {
				$has_numeric = true;
				break;
			}
		}
		if ( $has_numeric ) {
			return false;
		}
	}

	return $is_free;
}
add_filter( 'pmpro_is_level_free', 'pmprodon_enable_payments_for_free_level_donations', 10, 2 );

/**
 * Set price at checkout
 */
function pmprodon_pmpro_checkout_level( $level ) {

	if ( isset( $_REQUEST['donation'] ) ) {
		$donation = sanitize_text_field( preg_replace( '/[^0-9\.]/', '', trim( $_REQUEST['donation'] ) ) );
	} else {
		return $level;
	}

	if ( ! empty( $donation ) && $donation > 0 ) {
		// save initial payment amount
		global $pmprodon_original_initial_payment;
		$pmprodon_original_initial_payment = $level->initial_payment;

		// add donation
		$level->initial_payment = $level->initial_payment + $donation;
	}

	// Store the sanitized donation amount in a global so it is available
	// later in the checkout flow, even if $_REQUEST is cleared by the gateway
	// (e.g., Pay by Check). This fires at pmpro_checkout_level priority 99,
	// before pmpro_after_checkout where the value is saved to order meta.
	global $pmprodon_donation_amount;
	$pmprodon_donation_amount = $donation;

	// Calculate and add cover fee if the checkbox is checked.
	global $pmprodon_cover_fee_amount;
	$pmprodon_cover_fee_amount = 0;
	if ( ! empty( $_REQUEST['cover_fees'] ) ) {
		$donfields_settings = pmprodon_get_level_settings( $level->id );
		if ( ! empty( $donfields_settings['cover_fees_enabled'] ) ) {
			$fee_percentage = (float) $donfields_settings['cover_fees_percentage'];
			$fee_flat       = (float) $donfields_settings['cover_fees_flat'];
			$cover_fee      = pmprodon_calculate_cover_fee( (float) $donation, $fee_percentage, $fee_flat );
			if ( $cover_fee > 0 ) {
				$level->initial_payment   = $level->initial_payment + $cover_fee;
				$pmprodon_cover_fee_amount = $cover_fee;
			}
		}
	}

	// Store the donation note in a global for the same reason.
	global $pmprodon_donation_note;
	if ( isset( $_REQUEST['donation_note'] ) ) {
		$pmprodon_donation_note = sanitize_textarea_field( $_REQUEST['donation_note'] );
	}

	return $level;
}
add_filter( 'pmpro_checkout_level', 'pmprodon_pmpro_checkout_level', 99 );

/**
 * Check price is between min and max.
 */
function pmprodon_pmpro_registration_checks( $continue ) {
	// only bother if we are continuing already
	if ( $continue ) {
		global $pmpro_currency_symbol, $pmpro_msg, $pmpro_msgt;

		// was a donation passed in?
		if ( isset( $_REQUEST['donation'] ) ) {
			// get values
			$level = pmpro_getLevelAtCheckout();
			$donfields = get_option( 'pmprodon_' . $level->id );

			// make sure this level has variable pricing
			if ( empty( $donfields ) || empty( $donfields['donations'] ) ) {
				$pmpro_msg  = __( "Error: You tried to set the donation on a level that doesn't have donations. Please try again.", 'pmpro-donations' );
				$pmpro_msgt = 'pmpro_error';
			}

			// get price
			$donation = sanitize_text_field( preg_replace( '/[^0-9\.]/', '', $_REQUEST['donation'] ) );

			// check that the donation falls between the min and max
			if ( (float) $donation < 0 || ( ! empty( $donfields['min_price'] ) && (float) $donation < (float) $donfields['min_price'] ) ) {
				$pmpro_msg  = sprintf( __( 'The lowest accepted donation is %s. Please enter a new amount.', 'pmpro-donations' ), pmpro_formatPrice( $donfields['min_price'] ) );
				$pmpro_msgt = 'pmpro_error';
				$continue   = false;
			} elseif ( ! empty( $donfields['max_price'] ) && (float) $donation > (float) $donfields['max_price'] ) {
				$pmpro_msg = sprintf( __( 'The highest accepted donation is %s. Please enter a new amount.', 'pmpro-donations' ), pmpro_formatPrice( $donfields['max_price'] ) );

				$pmpro_msgt = 'pmpro_error';
				$continue   = false;
			}

			// Free levels with min_price > 0 require a donation amount.
			if ( $continue && ! empty( $donfields['min_price'] ) && (float) $donfields['min_price'] > 0 && intval( $level->initial_payment ) === 0 && (float) $donation <= 0 ) {
				$pmpro_msg  = sprintf( __( 'A donation of at least %s is required. Please enter a donation amount.', 'pmpro-donations' ), pmpro_formatPrice( $donfields['min_price'] ) );
				$pmpro_msgt = 'pmpro_error';
				$continue   = false;
			}

			// all good!
		}
	}

	return $continue;
}
add_filter( 'pmpro_registration_checks', 'pmprodon_pmpro_registration_checks' );

/**
 * Filter checkout page text for donation-only levels.
 *
 * Replaces membership-oriented language with donation-appropriate language
 * when the current checkout level is donation-only. This filter is hooked
 * on pmpro_checkout_before_form and unhooked on pmpro_checkout_after_form
 * to limit its scope to the checkout form rendering only.
 *
 * @since 2.3
 *
 * @param string $translated_text The translated text.
 * @param string $text            The original (untranslated) text.
 * @param string $domain          The text domain.
 * @return string The modified or original text.
 */
function pmprodon_filter_donation_only_checkout_text( $translated_text, $text, $domain ) {
	// Only filter PMPro core strings.
	if ( $domain !== 'paid-memberships-pro' ) {
		return $translated_text;
	}

	global $pmpro_level;

	// Check if this is a donation-only level.
	if ( empty( $pmpro_level ) || ! pmprodon_is_donations_only( $pmpro_level->id ) ) {
		return $translated_text;
	}

	// Map of PMPro core strings to donation-appropriate replacements.
	switch ( $text ) {
		case 'Membership Level':
			return __( 'Donation', 'pmpro-donations' );

		case 'You have selected the <strong>%s</strong> membership level.':
			/* translators: %s: the level name */
			return __( 'You are making a donation.', 'pmpro-donations' );

		case 'Submit and Check Out':
			return __( 'Donate Now', 'pmpro-donations' );

		case 'Submit and Confirm':
			return __( 'Donate Now', 'pmpro-donations' );

		case 'The price for membership is <strong>%s</strong> now':
			return '';

		case 'The price for membership is <strong>%s</strong> now and then <strong>%s per %s for %d more %s</strong>.':
			return '';

		case 'Membership expires after %d %s.':
			return '';

		case 'Change':
			return '';
	}

	return $translated_text;
}

/**
 * Hook the donation-only checkout text filter before the checkout form.
 *
 * @since 2.3
 */
function pmprodon_hook_donation_only_checkout_text() {
	global $pmpro_level;
	if ( ! empty( $pmpro_level ) && pmprodon_is_donations_only( $pmpro_level->id ) ) {
		add_filter( 'gettext', 'pmprodon_filter_donation_only_checkout_text', 10, 3 );
	}
}
add_action( 'pmpro_checkout_before_form', 'pmprodon_hook_donation_only_checkout_text' );

/**
 * Unhook the donation-only checkout text filter after the checkout form.
 *
 * @since 2.3
 */
function pmprodon_unhook_donation_only_checkout_text() {
	remove_filter( 'gettext', 'pmprodon_filter_donation_only_checkout_text', 10, 3 );
}
add_action( 'pmpro_checkout_after_form', 'pmprodon_unhook_donation_only_checkout_text' );

/**
 * Override level cost text on checkout page
 */
function pmprodon_pmpro_level_cost_text( $text, $level ) {
	global $pmprodon_original_initial_payment;
	if ( ! empty( $pmprodon_original_initial_payment ) ) {
		$olevel = clone $level;
		$olevel->initial_payment = $pmprodon_original_initial_payment;
		remove_filter( 'pmpro_level_cost_text', 'pmprodon_pmpro_level_cost_text', 10, 2);
		$text = pmpro_getLevelCost( $olevel );
		add_filter( 'pmpro_level_cost_text', 'pmprodon_pmpro_level_cost_text', 10, 2);
	}

	return $text;
}

/**
 * We only want pmprodon_pmpro_level_cost_text to run for the level cost on the checkout form.
 *
 * This means we want to hook on pmpro_checkout_before_form and unhook on pmpro_checkout_after_level_cost.
 */
function pmprodon_hook_pmpro_level_cost_text() {
	add_filter( 'pmpro_level_cost_text', 'pmprodon_pmpro_level_cost_text', 10, 2 );
}
add_action( 'pmpro_checkout_before_form', 'pmprodon_hook_pmpro_level_cost_text' );
function pmprodon_unhook_pmpro_level_cost_text() {
	remove_filter( 'pmpro_level_cost_text', 'pmprodon_pmpro_level_cost_text', 10, 2 );
}
add_action( 'pmpro_checkout_after_level_cost', 'pmprodon_unhook_pmpro_level_cost_text' );


/**
 * Save donation amount to order notes.
 *
 * @deprecated 2.0
 */
function pmprodon_pmpro_checkout_order( $order ) {
	_deprecated_function( __FUNCTION__, '2.0' );
	if ( ! empty( $_REQUEST['donation'] ) ) {
		$donation = sanitize_text_field( preg_replace( '/[^0-9\.]/', '', $_REQUEST['donation'] ) );
	} else {
		return $order;
	}

	if ( empty( $order->notes ) ) {
		$order->notes = '';
	}

	if ( ! empty( $donation ) && strpos( $order->notes, __( 'Donation', 'pmpro-donations' ) ) === false ) {
		$order->notes .= __( 'Donation', 'pmpro-donations' ) . ': ' . $donation . "\n";
	}
	return $order;
}

/**
 * Show order components on confirmation and invoice pages.
 */
function pmprodon_pmpro_invoice_bullets_bottom( $order ) {
	$components = pmprodon_get_price_components( $order );
	if ( ! empty( $components['donation'] ) ) {
		$bullets = array(
			'membership_cost' => '<strong>' . __( 'Membership Cost', 'pmpro-donations' ) . ": </strong> " . pmpro_formatPrice( $components['price'] ),
			'donation'        => '<strong>' . __( 'Donation', 'pmpro-donations' ) . ": </strong>" . pmpro_formatPrice( $components['donation'] )
		);

		// Add cover fee if present.
		$fee_covered = get_pmpro_membership_order_meta( $order->id, 'donation_fee_covered', true );
		if ( ! empty( $fee_covered ) && (float) $fee_covered > 0 ) {
			$bullets['fee_covered'] = '<strong>' . esc_html__( 'Processing Fee Covered', 'pmpro-donations' ) . ': </strong>' . pmpro_formatPrice( $fee_covered );
		}

		// Add donation note if present.
		$donation_note = get_pmpro_membership_order_meta( $order->id, 'donation_note', true );
		if ( ! empty( $donation_note ) ) {
			$bullets['donation_note'] = '<strong>' . esc_html__( 'Donation Note', 'pmpro-donations' ) . ': </strong>' . esc_html( $donation_note );
		}

		$bullets = apply_filters( 'pmpro_donations_invoice_bullets', $bullets, $order );
		foreach ( $bullets as $bullet ) {
			echo '<li class="' . esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ) . '">' . wp_kses_post( $bullet ) . '</li>';
		}
	}
}
add_filter( 'pmpro_invoice_bullets_bottom', 'pmprodon_pmpro_invoice_bullets_bottom' );

/**
 * Add donation-related email template variables.
 *
 * Provides !!donation!!, !!donation_note!!, and !!donation_fee_covered!!
 * email template variables. For donation-only levels, also overrides the
 * email subject to use donation-appropriate language.
 *
 * @since 2.0
 * @since 2.3 Override email subject for donation-only level checkouts.
 *
 * @param array  $data  The email template data.
 * @param object $email The email object.
 * @return array The modified email template data.
 */
function pmprodon_pmpro_email_data( $data, $email ) {
	$order_id = empty( $email->data['order_id'] ) ? false : $email->data['order_id'];
	if ( ! empty( $order_id ) ) {
		$order      = new MemberOrder( $order_id );
		$components = pmprodon_get_price_components( $order );

		if ( ! empty( $components['donation'] ) ) {
			$data['donation'] = pmpro_formatPrice( $components['donation'] );
		} else {
			$data['donation'] = pmpro_formatPrice( 0 );
		}

		// Add donation note email variable.
		$donation_note = get_pmpro_membership_order_meta( $order->id, 'donation_note', true );
		$data['donation_note'] = ! empty( $donation_note ) ? esc_html( $donation_note ) : '';

		// Add cover fee email variable.
		$fee_covered = get_pmpro_membership_order_meta( $order->id, 'donation_fee_covered', true );
		$data['donation_fee_covered'] = ! empty( $fee_covered ) && (float) $fee_covered > 0 ? pmpro_formatPrice( $fee_covered ) : '';

		// Override subject for donation-only level checkout emails.
		if ( strpos( $email->template, 'checkout' ) !== false && ! empty( $order->membership_id ) && pmprodon_is_donations_only( $order->membership_id ) ) {
			if ( strpos( $email->template, 'admin' ) !== false ) {
				$data['subject'] = sprintf(
					/* translators: %s: the site name */
					__( 'Donation received at %s', 'pmpro-donations' ),
					get_bloginfo( 'name' )
				);
			} else {
				$data['subject'] = __( 'Thank you for your donation', 'pmpro-donations' );
			}
			$email->subject = $data['subject'];
		}
	}
	return $data;
}
add_filter( 'pmpro_email_data', 'pmprodon_pmpro_email_data', 10, 2 );

/**
 * Filter checkout confirmation emails for donation levels.
 *
 * For all donation levels: injects donation amount, cover fee, and
 * donation note before the Invoice section (when not using template variables).
 *
 * For donation-only levels: replaces membership language in the email body
 * with donation-appropriate language (e.g. "membership" → "donation",
 * "your membership account is now active" → "your donation has been received").
 *
 * When a level has an email_template_override setting, the email template
 * name is swapped before any other processing. Admin emails get the
 * override name with '_admin' appended. This is compatible with the
 * pmpro-email-templates add-on for full template editing.
 *
 * @since 2.0
 * @since 2.3 Replace membership language for donation-only level emails.
 * @since 2.3 Add per-level email template override support.
 *
 * @param object $email The email object.
 * @return object The modified email object.
 */
function pmprodon_pmpro_email_filter( $email ) {
	// Only update confirmation emails.
	if ( strpos( $email->template, 'checkout' ) === false ) {
		return $email;
	}

	$order_id = ( empty( $email->data ) || empty( $email->data['order_id'] ) ) ? false : $email->data['order_id'];
	if ( empty( $order_id ) ) {
		return $email;
	}

	$order = new MemberOrder( $order_id );

	// Swap the email template if the level has a custom override.
	if ( ! empty( $order->membership_id ) ) {
		$level_settings = pmprodon_get_level_settings( $order->membership_id );
		if ( ! empty( $level_settings['email_template_override'] ) ) {
			$override = sanitize_text_field( $level_settings['email_template_override'] );
			if ( strpos( $email->template, 'admin' ) !== false ) {
				$email->template = $override . '_admin';
			} else {
				$email->template = $override;
			}
		}
	}

	$components = pmprodon_get_price_components( $order );

	// Inject donation info before Invoice section (when not using !!donation!! variable).
	if ( ! empty( $components['donation'] ) && strpos( $email->body, '!!donation!!' ) === false ) {
		$donation_info = __( 'Donation Amount:', 'pmpro-donations' ) . ' ' . pmpro_formatPrice( $components['donation'] );

		// Append cover fee if present and not already using !!donation_fee_covered!! variable.
		if ( strpos( $email->body, '!!donation_fee_covered!!' ) === false ) {
			$fee_covered = get_pmpro_membership_order_meta( $order->id, 'donation_fee_covered', true );
			if ( ! empty( $fee_covered ) && (float) $fee_covered > 0 ) {
				$donation_info .= '</p><p>' . esc_html__( 'Processing Fee Covered:', 'pmpro-donations' ) . ' ' . pmpro_formatPrice( $fee_covered );
			}
		}

		// Append donation note if present and not already using !!donation_note!! variable.
		if ( strpos( $email->body, '!!donation_note!!' ) === false ) {
			$donation_note = get_pmpro_membership_order_meta( $order->id, 'donation_note', true );
			if ( ! empty( $donation_note ) ) {
				$donation_info .= '</p><p>' . esc_html__( 'Donation Note:', 'pmpro-donations' ) . ' ' . esc_html( $donation_note );
			}
		}

		$email->body = preg_replace( '/\<p\>\s*' . __( 'Invoice', 'pmpro-donations' ) . '/', '<p>' . $donation_info . '</p><p>' . __( 'Invoice', 'pmpro-donations' ), $email->body );
	}

	// Replace membership language with donation language for donation-only levels.
	if ( ! empty( $order->membership_id ) && pmprodon_is_donations_only( $order->membership_id ) ) {
		// Replace common membership phrases with donation equivalents.
		// Use __() on search keys so replacements work on translated PMPro sites.
		$replacements = array(
			__( 'Your membership account is now active.', 'paid-memberships-pro' )  => __( 'Your donation has been received.', 'pmpro-donations' ),
			__( 'Thank you for your membership', 'paid-memberships-pro' )           => __( 'Thank you for your donation', 'pmpro-donations' ),
			__( 'membership level has been changed', 'paid-memberships-pro' )       => __( 'donation has been processed', 'pmpro-donations' ),
			__( 'has changed their membership level', 'paid-memberships-pro' )      => __( 'has made a donation', 'pmpro-donations' ),
			__( 'your membership confirmation', 'paid-memberships-pro' )            => __( 'your donation confirmation', 'pmpro-donations' ),
			__( 'Your membership confirmation', 'paid-memberships-pro' )            => __( 'Your donation confirmation', 'pmpro-donations' ),
			__( 'Membership Level:', 'paid-memberships-pro' )                       => __( 'Donation:', 'pmpro-donations' ),
		);

		foreach ( $replacements as $search => $replace ) {
			$email->body = str_replace( $search, $replace, $email->body );
		}
	}

	return $email;
}
add_filter( 'pmpro_email_filter', 'pmprodon_pmpro_email_filter', 10, 2 );

/**
 * If checking out for a level with donations, use SSL even if it's free
 *
 * @since .4
 */
function pmprodon_pmpro_checkout_preheader() {
	global $besecure;

	$level = pmpro_getLevelAtCheckout();
	if ( ! is_admin() && ! empty( $level->id ) ) {
		$donfields = get_option(
			'pmprodon_' . intval( $level->id ), array(
				'donations'       => 0,
				'min_price'       => '',
				'max_price'       => '',
				'dropdown_prices' => '',
				'text'            => '',
			)
		);

		if ( ! empty( $donfields ) && ! empty( $donfields['donations'] ) ) {
			$besecure = get_option( 'pmpro_use_ssl' );
		}
	}
}
add_action( 'pmpro_checkout_preheader', 'pmprodon_pmpro_checkout_preheader' );

/**
 * Fix issue where incorrect donation amount is charged when using PayPal Express.
 *
 * @since 1.1.3
 */
function pmprodon_ppe_add_donation_to_request() {
	// Check if the "review" or "confirm" request variables are set.
	if ( empty( $_REQUEST['review'] ) && empty( $_REQUEST['confirm'] ) ) {
		return;
	}

	// Check if we have a PPE token that we are reviewing.
	if ( empty( $_REQUEST['token'] ) ) {
		return;
	}
	$token = sanitize_text_field( $_REQUEST['token'] );

	// Make sure that the MemberOrder class is loaded.
	if ( ! class_exists( 'MemberOrder' ) ) {
		return;
	}

	// Check if we have an order with this token.
	$order = new MemberOrder();
	$order->getMemberOrderByPayPalToken( $token );
	if ( empty( $order->id ) ) {
		return;
	}

	// Make sure that this order is in token status.
	if ( $order->status !== 'token' ) {
		return;
	}

	// Get the donation information for this order.
	$donation = pmprodon_get_price_components( $order );

	// If there is a donation amount on the order but not yet in $_REQUEST, add it.
	if ( ! empty( $donation['donation'] ) && empty( $_REQUEST['donation'] ) ) {
		$_REQUEST['donation'] = $donation['donation'];
	}

	// Restore the donation note from order meta for PayPal Express.
	$donation_note = get_pmpro_membership_order_meta( $order->id, 'donation_note', true );
	if ( ! empty( $donation_note ) && empty( $_REQUEST['donation_note'] ) ) {
		$_REQUEST['donation_note'] = $donation_note;
	}

	// Restore the cover fees flag from order meta for PayPal Express.
	$fee_covered = get_pmpro_membership_order_meta( $order->id, 'donation_fee_covered', true );
	if ( ! empty( $fee_covered ) && (float) $fee_covered > 0 && empty( $_REQUEST['cover_fees'] ) ) {
		$_REQUEST['cover_fees'] = '1';
	}
}
add_action( 'pmpro_checkout_preheader_before_get_level_at_checkout', 'pmprodon_ppe_add_donation_to_request' );

/**
 * Add donation amount to order meta.
 *
 * Reads from the global $pmprodon_donation_amount set during
 * pmprodon_pmpro_checkout_level() as the primary source. Falls back
 * to $_REQUEST['donation'] for edge cases or non-standard checkout flows.
 *
 * @since 2.0
 * @since 2.3 Use global donation amount to fix Pay by Check gateway (Issue #86).
 *            Apply sanitization to prevent whitespace-corrupted values (Issue #89).
 *
 * @param int    $user_id The user ID.
 * @param object $order   The order object.
 */
function pmprodon_store_donation_amount_in_order_meta( $user_id, $order ) {
	global $pmprodon_donation_amount;

	// Primary source: global set during pmpro_checkout_level (already sanitized).
	if ( isset( $pmprodon_donation_amount ) ) {
		$donation = $pmprodon_donation_amount;
	} elseif ( isset( $_REQUEST['donation'] ) ) {
		// Fallback: read from $_REQUEST with full sanitization.
		$donation = sanitize_text_field( preg_replace( '/[^0-9\.]/', '', trim( $_REQUEST['donation'] ) ) );
	} else {
		return;
	}

	update_pmpro_membership_order_meta( $order->id, 'donation_amount', $donation );

	// Track the last donation date for reminder emails.
	if ( (float) $donation > 0 && $user_id > 0 ) {
		update_user_meta( $user_id, 'pmprodon_last_donation_date', current_time( 'timestamp' ) );
	}

	// Store the cover fee amount in order meta.
	global $pmprodon_cover_fee_amount;
	if ( ! empty( $pmprodon_cover_fee_amount ) && $pmprodon_cover_fee_amount > 0 ) {
		update_pmpro_membership_order_meta( $order->id, 'donation_fee_covered', $pmprodon_cover_fee_amount );
	}

	// Store the donation note in order meta.
	global $pmprodon_donation_note;
	if ( isset( $pmprodon_donation_note ) ) {
		$donation_note = $pmprodon_donation_note;
	} elseif ( isset( $_REQUEST['donation_note'] ) ) {
		$donation_note = sanitize_textarea_field( $_REQUEST['donation_note'] );
	} else {
		$donation_note = '';
	}
	if ( ! empty( $donation_note ) ) {
		update_pmpro_membership_order_meta( $order->id, 'donation_note', $donation_note );
	}
}
add_action( 'pmpro_after_checkout', 'pmprodon_store_donation_amount_in_order_meta', 10, 2 );

/**
 * Filter the confirmation page message for donation levels.
 *
 * For donation-only levels, replaces the default PMPro confirmation
 * message with a donation-specific thank-you message. The custom
 * confirmation_message level setting is appended after.
 *
 * For regular donation levels (donation + membership), appends the
 * custom confirmation_message after the default PMPro message.
 *
 * @since 2.0
 * @since 2.3 Replace entire message for donation-only levels.
 *
 * @param string $message The confirmation message.
 * @param object $invoice The MemberOrder object.
 * @return string The modified confirmation message.
 */
function pmprodon_pmpro_confirmation_message( $message, $invoice ) {
	// Get the level ID from the MemberOrder object.
	if ( $invoice ) {
		$level_id = $invoice->membership_id;
	} elseif ( isset( $_REQUEST['pmpro_level'] ) ) {
		$level_id = intval( $_REQUEST['pmpro_level'] );
	} elseif ( isset( $_REQUEST['level'] ) ) {
		$level_id = intval( $_REQUEST['level'] );
	} else {
		return $message;
	}

	// Bail if donations are not enabled for this level.
	$settings = pmprodon_get_level_settings( $level_id );
	if ( ! $settings['donations'] ) {
		return $message;
	}

	// For donation-only levels, replace the entire confirmation message.
	if ( pmprodon_is_donations_only( $level_id ) ) {
		$donation_message = '';

		// Build the donation thank-you message.
		if ( $invoice ) {
			$components = pmprodon_get_price_components( $invoice );
			if ( ! empty( $components['donation'] ) ) {
				$donation_message = '<p>' . sprintf(
					/* translators: %s: the formatted donation amount */
					esc_html__( 'Thank you for your donation of %s.', 'pmpro-donations' ),
					pmpro_formatPrice( $components['donation'] )
				) . '</p>';
			} else {
				$donation_message = '<p>' . esc_html__( 'Thank you for your donation.', 'pmpro-donations' ) . '</p>';
			}
		} else {
			$donation_message = '<p>' . esc_html__( 'Thank you for your donation.', 'pmpro-donations' ) . '</p>';
		}

		// Append the custom confirmation_message setting if configured.
		if ( ! empty( $settings['confirmation_message'] ) ) {
			$donation_message .= wpautop( wp_kses_post( $settings['confirmation_message'] ) );
		}

		return $donation_message;
	}

	// For regular donation levels, append the custom confirmation_message.
	if ( empty( $settings['confirmation_message'] ) ) {
		return $message;
	}

	// Bail if no donation amount.
	$components = pmprodon_get_price_components( $invoice );
	if ( empty( $components['donation'] ) ) {
		return $message;
	}

	return $message . wpautop( wp_kses_post( $settings['confirmation_message'] ) );
}
add_filter( 'pmpro_confirmation_message', 'pmprodon_pmpro_confirmation_message', 10, 2 );

/**
 * Bypass PMPro registration checks for guest donor checkouts.
 *
 * When a guest checkout is in progress, the username and password fields
 * are auto-generated by JavaScript. This filter skips PMPro's standard
 * username/password validation so that auto-generated credentials are accepted.
 *
 * @since 2.3
 *
 * @param bool $continue Whether registration checks should continue.
 * @return bool
 */
function pmprodon_guest_registration_checks( $continue ) {
	if ( ! $continue ) {
		return $continue;
	}

	if ( ! pmprodon_is_guest_checkout() ) {
		return $continue;
	}

	// For guest checkout, validate that we have an email address.
	if ( empty( $_REQUEST['bemail'] ) ) {
		global $pmpro_msg, $pmpro_msgt;
		$pmpro_msg  = __( 'Please enter your email address.', 'pmpro-donations' );
		$pmpro_msgt = 'pmpro_error';
		return false;
	}

	// Validate email format.
	$email = sanitize_email( $_REQUEST['bemail'] );
	if ( ! is_email( $email ) ) {
		global $pmpro_msg, $pmpro_msgt;
		$pmpro_msg  = __( 'Please enter a valid email address.', 'pmpro-donations' );
		$pmpro_msgt = 'pmpro_error';
		return false;
	}

	// Check if the email belongs to an existing user.
	$existing_user = get_user_by( 'email', $email );
	if ( $existing_user ) {
		// Allow if the existing user is already a guest donor (repeat donation).
		$is_existing_guest = get_user_meta( $existing_user->ID, 'pmprodon_is_guest_donor', true );
		if ( empty( $is_existing_guest ) ) {
			global $pmpro_msg, $pmpro_msgt;
			$pmpro_msg  = __( 'An account with this email address already exists. Please log in to donate, or use a different email address.', 'pmpro-donations' );
			$pmpro_msgt = 'pmpro_error';
			return false;
		}
	}

	return $continue;
}
add_filter( 'pmpro_registration_checks', 'pmprodon_guest_registration_checks', 5 );

/**
 * Block guest checkout for levels with recurring billing.
 *
 * Runs at pmpro_registration_checks to prevent guest donors from
 * checking out for subscription-based levels.
 *
 * @since 2.3
 *
 * @param bool $continue Whether registration checks should continue.
 * @return bool
 */
function pmprodon_block_guest_recurring( $continue ) {
	if ( ! $continue ) {
		return $continue;
	}

	// Only check for guest checkout attempts.
	if ( is_user_logged_in() || empty( $_REQUEST['pmprodon_guest'] ) || $_REQUEST['pmprodon_guest'] !== '1' ) {
		return $continue;
	}

	$level = pmpro_getLevelAtCheckout();
	if ( ! empty( $level->billing_amount ) && (float) $level->billing_amount > 0 ) {
		global $pmpro_msg, $pmpro_msgt;
		$pmpro_msg  = __( 'Guest checkout is only available for one-time donations. Please create an account for recurring donations.', 'pmpro-donations' );
		$pmpro_msgt = 'pmpro_error';
		return false;
	}

	return $continue;
}
add_filter( 'pmpro_registration_checks', 'pmprodon_block_guest_recurring', 4 );

/**
 * Generate guest donor credentials before user creation.
 *
 * Intercepts the checkout process for guest donors by setting a random
 * username and password in $_REQUEST if not already set. This ensures
 * PMPro's user creation flow works normally with non-loginable credentials.
 *
 * @since 2.3
 */
function pmprodon_prepare_guest_user_data() {
	if ( ! pmprodon_is_guest_checkout() ) {
		return;
	}

	// Store a global flag so we know this is a guest checkout.
	global $pmprodon_is_guest_checkout;
	$pmprodon_is_guest_checkout = true;

	// Generate a unique username if not already auto-generated by JS.
	if ( empty( $_REQUEST['username'] ) || strpos( $_REQUEST['username'], 'guest_donor_' ) !== 0 ) {
		$_REQUEST['username'] = 'guest_donor_' . substr( md5( uniqid( wp_rand(), true ) ), 0, 12 );
	}

	// Generate a strong random password.
	if ( empty( $_REQUEST['password'] ) || strlen( $_REQUEST['password'] ) < 20 ) {
		$random_password = wp_generate_password( 32, true, true );
		$_REQUEST['password']  = $random_password;
		$_REQUEST['password2'] = $random_password;
	}
}
add_action( 'pmpro_checkout_preheader', 'pmprodon_prepare_guest_user_data', 1 );

/**
 * Configure a newly registered user as a guest donor.
 *
 * Sets the `pmprodon_is_guest_donor` user meta flag, removes all roles
 * (preventing login), and stores the email for reference.
 *
 * @since 2.3
 *
 * @param int $user_id The newly registered user ID.
 */
function pmprodon_setup_guest_donor_user( $user_id ) {
	global $pmprodon_is_guest_checkout;

	if ( empty( $pmprodon_is_guest_checkout ) ) {
		return;
	}

	// Mark user as a guest donor.
	update_user_meta( $user_id, 'pmprodon_is_guest_donor', 1 );

	// Remove all roles so the user cannot log in.
	$user = new WP_User( $user_id );
	$user->set_role( '' );
}
add_action( 'user_register', 'pmprodon_setup_guest_donor_user' );

/**
 * Store confirmation key in order meta for guest donors.
 *
 * Generates a unique confirmation key and saves it to order meta so
 * that the guest donor can access their confirmation/invoice page
 * without logging in.
 *
 * @since 2.3
 *
 * @param int    $user_id The user ID.
 * @param object $order   The order object.
 */
function pmprodon_store_guest_confirmation_key( $user_id, $order ) {
	// Only store a key for guest donors.
	$is_guest = get_user_meta( $user_id, 'pmprodon_is_guest_donor', true );
	if ( empty( $is_guest ) ) {
		return;
	}

	$confirmation_key = pmprodon_generate_confirmation_key();
	update_pmpro_membership_order_meta( $order->id, 'pmprodon_confirmation_key', $confirmation_key );

	// Store the guest flag on the order meta for easy lookup.
	update_pmpro_membership_order_meta( $order->id, 'pmprodon_is_guest_order', 1 );

	// Store the key in a global so it can be used in the email.
	global $pmprodon_confirmation_key;
	$pmprodon_confirmation_key = $confirmation_key;
}
add_action( 'pmpro_after_checkout', 'pmprodon_store_guest_confirmation_key', 11, 2 );

/**
 * Filter checkout confirmation emails for guest donors.
 *
 * Removes login credentials (username, password, login URL) from the
 * checkout email body since guest donors don't have loginable accounts.
 * Adds a confirmation key URL for accessing the invoice.
 *
 * @since 2.3
 *
 * @param object $email The email object.
 * @return object The modified email object.
 */
function pmprodon_filter_guest_donor_email( $email ) {
	// Only filter checkout emails.
	if ( strpos( $email->template, 'checkout' ) === false ) {
		return $email;
	}

	// Check if this is a guest donor order.
	$order_id = ( empty( $email->data ) || empty( $email->data['order_id'] ) ) ? false : $email->data['order_id'];
	if ( empty( $order_id ) ) {
		return $email;
	}

	$is_guest_order = get_pmpro_membership_order_meta( $order_id, 'pmprodon_is_guest_order', true );
	if ( empty( $is_guest_order ) ) {
		return $email;
	}

	// Strip login-related content from the email body.
	// Remove lines containing username, password, and login URL.
	$patterns = array(
		'/^.*' . preg_quote( '!!username!!', '/' ) . '.*$/m',
		'/^.*' . preg_quote( '!!password!!', '/' ) . '.*$/m',
		'/^.*' . preg_quote( '!!login_url!!', '/' ) . '.*$/m',
		'/^.*' . preg_quote( '!!login_link!!', '/' ) . '.*$/m',
	);
	$email->body = preg_replace( $patterns, '', $email->body );

	// Clean up consecutive blank lines left after stripping.
	$email->body = preg_replace( '/(\r?\n){3,}/', "\n\n", $email->body );

	// Add confirmation key URL if available.
	$confirmation_key = get_pmpro_membership_order_meta( $order_id, 'pmprodon_confirmation_key', true );
	if ( ! empty( $confirmation_key ) ) {
		$confirmation_url = add_query_arg( 'pmprodon_key', $confirmation_key, pmpro_url( 'confirmation' ) );
		$confirmation_link = '<p>' . sprintf(
			/* translators: %s: the confirmation page URL */
			esc_html__( 'View your donation confirmation: %s', 'pmpro-donations' ),
			'<a href="' . esc_url( $confirmation_url ) . '">' . esc_html( $confirmation_url ) . '</a>'
		) . '</p>';

		// Insert the confirmation link before the Invoice section, or at the end.
		if ( strpos( $email->body, __( 'Invoice', 'pmpro-donations' ) ) !== false ) {
			$email->body = preg_replace(
				'/\<p\>\s*' . preg_quote( __( 'Invoice', 'pmpro-donations' ), '/' ) . '/',
				$confirmation_link . '<p>' . __( 'Invoice', 'pmpro-donations' ),
				$email->body
			);
		} else {
			$email->body .= $confirmation_link;
		}
	}

	return $email;
}
add_filter( 'pmpro_email_filter', 'pmprodon_filter_guest_donor_email', 5 );

/**
 * Handle confirmation key access for guest donors.
 *
 * Allows unauthenticated visitors to view their donation confirmation
 * page by passing a valid `pmprodon_key` query parameter. The key is
 * validated against order meta. If valid, the matching order is loaded
 * into the global context for the confirmation template.
 *
 * @since 2.3
 */
function pmprodon_handle_confirmation_key_access() {
	// Only act on the confirmation page.
	if ( ! function_exists( 'pmpro_is_page' ) || ! pmpro_is_page( 'confirmation' ) ) {
		return;
	}

	// Only needed for non-logged-in users.
	if ( is_user_logged_in() ) {
		return;
	}

	// Check for confirmation key.
	if ( empty( $_REQUEST['pmprodon_key'] ) ) {
		return;
	}

	$key = sanitize_text_field( $_REQUEST['pmprodon_key'] );
	if ( strlen( $key ) !== 32 ) {
		return;
	}

	// Look up the order with this confirmation key.
	global $wpdb;
	$order_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT pmpro_membership_order_id FROM {$wpdb->prefix}pmpro_membership_ordermeta WHERE meta_key = 'pmprodon_confirmation_key' AND meta_value = %s LIMIT 1",
			$key
		)
	);

	if ( empty( $order_id ) ) {
		return;
	}

	// Verify this is a guest order.
	$is_guest = get_pmpro_membership_order_meta( $order_id, 'pmprodon_is_guest_order', true );
	if ( empty( $is_guest ) ) {
		return;
	}

	// Load the order and set up the global for the confirmation template.
	$order = new MemberOrder( $order_id );
	if ( empty( $order->id ) ) {
		return;
	}

	// Set the current user temporarily so PMPro's confirmation page works.
	global $current_user, $pmpro_invoice;
	$pmpro_invoice = $order;

	// Temporarily log in as the guest user for this page view only.
	wp_set_current_user( $order->user_id );
}
add_action( 'template_redirect', 'pmprodon_handle_confirmation_key_access', 1 );

/**
 * Restore guest checkout state for PayPal Express redirect flow.
 *
 * When PayPal Express redirects back to the site, this function
 * restores the guest checkout flag from the order meta so the
 * checkout can complete as a guest donation.
 *
 * @since 2.3
 */
function pmprodon_ppe_restore_guest_state() {
	// Check if the "review" or "confirm" request variables are set.
	if ( empty( $_REQUEST['review'] ) && empty( $_REQUEST['confirm'] ) ) {
		return;
	}

	// Check if we have a PPE token that we are reviewing.
	if ( empty( $_REQUEST['token'] ) ) {
		return;
	}
	$token = sanitize_text_field( $_REQUEST['token'] );

	// Make sure that the MemberOrder class is loaded.
	if ( ! class_exists( 'MemberOrder' ) ) {
		return;
	}

	// Check if we have an order with this token.
	$order = new MemberOrder();
	$order->getMemberOrderByPayPalToken( $token );
	if ( empty( $order->id ) ) {
		return;
	}

	// Check if this is a guest order.
	$is_guest = get_pmpro_membership_order_meta( $order->id, 'pmprodon_is_guest_order', true );
	if ( empty( $is_guest ) ) {
		return;
	}

	// Restore the guest checkout flag.
	if ( empty( $_REQUEST['pmprodon_guest'] ) ) {
		$_REQUEST['pmprodon_guest'] = '1';
	}

	// Set the global flag.
	global $pmprodon_is_guest_checkout;
	$pmprodon_is_guest_checkout = true;
}
add_action( 'pmpro_checkout_preheader_before_get_level_at_checkout', 'pmprodon_ppe_restore_guest_state', 5 );
