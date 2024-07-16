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
							// check for dropdown
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

								// show dropdown
								sort( $dropdown_prices );
								?>
								<select id="donation_dropdown" name="donation_dropdown" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-select' ) ); ?>" <?php if ( $pmpro_review ) { ?>disabled="disabled"<?php } ?> class="<?php echo esc_attr( pmpro_get_element_class( 'select pmpro_alter_price' ) ); ?>" >
									<?php
									foreach ( $dropdown_prices as $price ) {
										?>
										<option <?php selected( $price, $donation ); ?> value="<?php echo esc_attr( $price ); ?>"><?php echo esc_html( pmpro_formatPrice( (double) $price ) ); ?></option>
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
				</div> <!-- end pmpro_form_fields -->
			</div> <!-- end pmpro_card_content -->
		</div> <!-- end pmpro_card -->
	</fieldset> <!-- end pmpro_form_fieldset-donation -->
	<script>
		//some vars for keeping track of whether or not we show billing
		var pmpro_gateway_billing = <?php if ( in_array( $gateway, array( 'paypalexpress', 'twocheckout' ) ) !== false ) { echo'false';	} else { echo 'true'; } ?>;
		var pmpro_pricing_billing = <?php if ( ! pmpro_isLevelFree( $pmpro_level ) ) { echo 'true';	} else { echo 'false'; } ?>;
		var pmpro_donation_billing = pmpro_pricing_billing;

		//this script will hide show billing fields based on the price set
		jQuery(document).ready(function() {
			//bind other field toggle to dropdown change
			jQuery('#donation_dropdown').change(function() {
				pmprodon_toggleOther();
				// If we changed to a non-other value, update the donation field.
				if ( jQuery( '#donation_dropdown' ).val() !== 'other' ) {
					jQuery( '#donation' ).val( jQuery( '#donation_dropdown' ).val() );
				}
				pmprodon_checkForFree();
			});

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

			//check when page loads too
			pmprodon_toggleOther();
			pmprodon_checkForFree();
		});

		function pmprodon_toggleOther() {
			//make sure there is a dropdown to check
			if(!jQuery('#donation_dropdown').length)
				return;

			//get val
			var donation_dropdown = jQuery('#donation_dropdown').val();

			if(donation_dropdown == 'other')
				jQuery('#pmprodon_donation_input').show();
			else
				jQuery('#pmprodon_donation_input').hide();
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
		}
	</script>
	<?php
}
add_action( 'pmpro_checkout_after_user_fields', 'pmprodon_pmpro_checkout_after_user_fields' );

/**
 * Set price at checkout
 */
function pmprodon_pmpro_checkout_level( $level ) {

	if ( isset( $_REQUEST['donation'] ) ) {
		$donation = sanitize_text_field( preg_replace( '/[^0-9\.]/', '', $_REQUEST['donation'] ) );
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
			if ( (double) $donation < 0 || ( ! empty( $donfields['min_price'] ) && (double) $donation < (double) $donfields['min_price'] ) ) {
				$pmpro_msg  = sprintf( __( 'The lowest accepted donation is %s. Please enter a new amount.', 'pmpro-donations' ), pmpro_formatPrice( $donfields['min_price'] ) );
				$pmpro_msgt = 'pmpro_error';
				$continue   = false;
			} elseif ( ! empty( $donfields['max_price'] ) && (double) $donation > (double) $donfields['max_price'] ) {
				$pmpro_msg = sprintf( __( 'The highest accepted donation is %s. Please enter a new amount.', 'pmpro-donations' ), pmpro_formatPrice( $donfields['max_price'] ) );

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
		$bullets = apply_filters( 'pmpro_donations_invoice_bullets', $bullets, $order );
		foreach ( $bullets as $bullet ) {
			echo '<li class="' . esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ) . '">' . wp_kses_post( $bullet ) . '</li>';
		}
	}
}
add_filter( 'pmpro_invoice_bullets_bottom', 'pmprodon_pmpro_invoice_bullets_bottom' );

function pmprodon_pmpro_email_data( $data, $email ) {
	$order_id = empty( $email->data['invoice_id'] ) ? false : $email->data['invoice_id'];
	if ( ! empty( $order_id ) ) {
		$order      = new MemberOrder( $order_id );
		$components = pmprodon_get_price_components( $order );

		if ( ! empty( $components['donation'] ) ) {
			$data['donation'] =  pmpro_formatPrice( $components['donation'] );
		} else {
			$data['donation'] =  pmpro_formatPrice( 0 );
		}
	}
	return $data;
}
add_filter( 'pmpro_email_data', 'pmprodon_pmpro_email_data', 10, 2 );

/**
 * Show order components in confirmation email.
 */
function pmprodon_pmpro_email_filter( $email ) {
	global $wpdb;

	// only update confirmation emails which aren't using !!donation!! email variable
	if ( strpos( $email->template, 'checkout' ) !== false && strpos( $email->body, '!!donation!!' ) === false ) {
		// get the user_id from the email
		$order_id = ( empty( $email->data ) || empty( $email->data['invoice_id'] ) ) ? false : $email->data['invoice_id'];
		if ( ! empty( $order_id ) ) {
			$order      = new MemberOrder( $order_id );
			$components = pmprodon_get_price_components( $order );

			// add to bottom of email
			if ( ! empty( $components['donation'] ) ) {
				$email->body = preg_replace( '/\<p\>\s*' . __( 'Invoice', 'pmpro-donations' ) . '/', '<p>' . __( 'Donation Amount:', 'pmpro-donations' ) . '' . pmpro_formatPrice( $components['donation'] ) . '</p><p>' . __( 'Invoice', 'pmpro-donations' ), $email->body );
			}
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
}
add_action( 'pmpro_checkout_preheader_before_get_level_at_checkout', 'pmprodon_ppe_add_donation_to_request' );

/**
 * Add donation amount to order meta.
 *
 * @since 2.0
 *
 * @param int   $user_id The user ID.
 * @param object The order object.
 */
function pmprodon_store_donation_amount_in_order_meta( $user_id, $order ) {
	if ( isset( $_REQUEST['donation'] ) ) {
		update_pmpro_membership_order_meta( $order->id, 'donation_amount', sanitize_text_field( $_REQUEST['donation'] ) );
	}
}
add_action( 'pmpro_after_checkout', 'pmprodon_store_donation_amount_in_order_meta', 10, 2 );

/**
 * Function to add the donation confirmation message to the confirmation page.
 *
 * Note: This does not modify the confirmation message in the email. This would
 * need to be implemented separately.
 *
 * @since 2.0
 *
 * @param string $message The confirmation message.
 * @param object $invoice The MemberOrder object.
 * @return string $message The confirmation message.
 */
function pmprodon_pmpro_confirmation_message( $message, $invoice ) {
	//Get the level ID from the MemberOrder object.
	if ( $invoice ) {
		$level_id = $invoice->membership_id;
	//If for some reason we can't find the level ID, try to get it from the URL.
	 } else if ( isset ( $_REQUEST['pmpro_level'] ) ) {
		$level_id = $_REQUEST['pmpro_level'];
	// Backwards compatibility for PMPro 2.x
	 }  else if ( isset ( $_REQUEST['level'] ) ) { 
		$level_id = $_REQUEST['level'];
	//Bail if we can't find the level ID.
	} else {
		return $message;
	}

	//Bail if not a donation level or donations are not enabled or there is no confirmation message.
	$settings = pmprodon_get_level_settings( $level_id );
	if( ! $settings['donations'] || empty( $settings['confirmation_message'] ) ) {
		return $message;
	}

	//Bail if no donation amount.
	$components = pmprodon_get_price_components( $invoice );
	if ( empty( $components['donation'] ) ) {
		return $message;
	}

	// Show the donation confirmation message.
	return $message . wpautop( wp_kses_post( $settings['confirmation_message'] ) );
}
add_filter( 'pmpro_confirmation_message', 'pmprodon_pmpro_confirmation_message', 10, 2 );
