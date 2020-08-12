<?php
/**
 * Update donation amount if a dropdown value is used
 */
function pmprodon_init_dropdown_values() {
	if ( function_exists( 'pmpro_start_session' ) ) {
		pmpro_start_session();
	}

	if ( ! empty( $_SESSION['donation_dropdown'] ) && $_SESSION['donation_dropdown'] != 'other' ) {
		$_SESSION['donation'] = $_SESSION['donation_dropdown'];
	}

	if ( ! empty( $_REQUEST['donation_dropdown'] ) && $_REQUEST['donation_dropdown'] != 'other' ) {
		$_REQUEST['donation'] = $_REQUEST['donation_dropdown'];
	}

	if ( ! empty( $_GET['donation_dropdown'] ) && $_GET['donation_dropdown'] != 'other' ) {
		$_GET['donation'] = $_GET['donation_dropdown'];
	}

	if ( ! empty( $_POST['donation_dropdown'] ) && $_POST['donation_dropdown'] != 'other' ) {
		$_POST['donation'] = $_POST['donation_dropdown'];
	}
}
add_action( 'init', 'pmprodon_init_dropdown_values', 1 );

/**
 * Show form at checkout.
 */
function pmprodon_pmpro_checkout_after_level_cost() {
	global $pmpro_currency_symbol, $pmpro_level, $gateway, $pmpro_review;

	if ( function_exists( 'pmpro_start_session' ) ) {
		pmpro_start_session();
	}

	// get variable pricing info
	$donfields = get_option( 'pmprodon_' . $pmpro_level->id );

	// no variable pricing? just return
	if ( empty( $donfields ) || empty( $donfields['donations'] ) ) {
		return;
	}

	// okay, now we're showing the form
	$min_price       = $donfields['min_price'];
	$max_price       = $donfields['max_price'];
	$dropdown_prices = $donfields['dropdown_prices'];

	if ( isset( $_REQUEST['donation'] ) ) {
		$donation = preg_replace( '[^0-9\.]', '', $_REQUEST['donation'] );
	} elseif ( isset( $_SESSION['donation'] ) ) {
		$donation = preg_replace( '[^0-9\.]', '', $_SESSION['donation'] );
	} elseif ( ! empty( $min_price ) ) {
		$donation = $min_price;
	} else {
		$donation = '';
	}
	
	?>
	<hr />
	<div id="pmpro_donations">
	<?php
	_e( 'Make a Gift', 'pmpro-donations' );
	
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
		<select id="donation_dropdown" name="donation_dropdown" <?php if ( $pmpro_review ) { ?>disabled="disabled"<?php } ?>>
			<?php
			foreach ( $dropdown_prices as $price ) {
				?>
				<option <?php selected( $price, $donation ); ?> value="<?php echo esc_attr( $price ); ?>"><?php echo pmpro_formatPrice( (double) $price ); ?></option>
				<?php
			}
			if ( $pmprodon_allow_other ) {
				?>
				<option value="other" <?php selected( true, ! empty( $donation ) && ! in_array( $donation, $dropdown_prices ) ); ?>>Other</option>
			<?php } ?>
		</select> &nbsp;
		<?php
	}
	?>

	<span id="pmprodon_donation_input" <?php if ( ! empty( $pmprodon_allow_other ) && ( empty( $_REQUEST['donation_dropdown'] ) || $_REQUEST['donation_dropdown'] != 'other' ) ) { ?>style="display: none;"<?php } ?>>
		<?php echo $pmpro_currency_symbol; ?> <input type="text" id="donation" name="donation" size="10" value="<?php echo esc_attr( $donation ); ?>" <?php if ( $pmpro_review ) { ?>disabled="disabled"<?php } ?> />
		<?php if ( $pmpro_review ) { ?>
			<input type="hidden" name="donation" value="<?php echo esc_attr( $donation ); ?>" />
		<?php } ?>
	</span>

	<?php
	if ( empty( $pmpro_review ) ) {
		?>
		<p class="pmpro_small">
		<?php
		if ( ! empty( $donfields['text'] ) ) {
			echo $donfields['text'];
		} elseif ( ! empty( $donfields['min_price'] ) && empty( $donfields['max_price'] ) ) {
			printf( __( 'Enter an amount %s or greater', 'pmpro-donations' ), pmpro_formatPrice( $donfields['min_price'] ) );
		} elseif ( ! empty( $donfields['max_price'] ) && empty( $donfields['min_price'] ) ) {
			printf( __( 'Enter an amount %s or less', 'pmpro-donations' ), pmpro_formatPrice( $donfields['max_price'] ) );
		} elseif ( ! empty( $donfields['max_price'] ) && ! empty( $donfields['min_price'] ) ) {
			printf( __( 'Enter an amount between %1$s and %2$s', 'pmpro-donations' ), pmpro_formatPrice( $donfields['min_price'] ), pmpro_formatPrice( $donfields['max_price'] ) );
		}
		?>
		</p>
		<?php
	}
	?>
</div>
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
add_action( 'pmpro_checkout_after_level_cost', 'pmprodon_pmpro_checkout_after_level_cost' );

/**
 * Set price at checkout
 */
function pmprodon_pmpro_checkout_level( $level ) {

	if ( isset( $_REQUEST['donation'] ) ) {
		$donation = preg_replace( '[^0-9\.]', '', $_REQUEST['donation'] );
	} elseif ( isset( $_SESSION['donation'] ) ) {
		$donation = preg_replace( '[^0-9\.]', '', $_SESSION['donation'] );
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
			$level_id  = intval( $_REQUEST['level'] );
			$donfields = get_option( 'pmprodon_' . $level_id );

			// make sure this level has variable pricing
			if ( empty( $donfields ) || empty( $donfields['donations'] ) ) {
				$pmpro_msg  = __( "Error: You tried to set the donation on a level that doesn't have donations. Please try again.", 'pmpro-donations' );
				$pmpro_msgt = 'pmpro_error';
			}

			// get price
			$donation = preg_replace( '[^0-9\.]', '', $_REQUEST['donation'] );

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
	global $pmpro_pages, $pmprodon_original_initial_payment, $pmprodon_text_level_cost_updated;
	if ( is_page( $pmpro_pages['checkout'] ) && ! empty( $pmprodon_original_initial_payment ) && empty( $pmprodon_text_level_cost_updated ) ) {
		$olevel                           = $level;
		$olevel->initial_payment          = $pmprodon_original_initial_payment;
		$pmprodon_text_level_cost_updated = true;   // to prevent loops
		$text                             = pmpro_getLevelCost( $olevel );
	}

	return $text;
}
add_filter( 'pmpro_level_cost_text', 'pmprodon_pmpro_level_cost_text', 10, 2 );

/**
 * Save donation amount to order notes.
 */
function pmprodon_pmpro_checkout_order( $order ) {
	if ( ! empty( $_REQUEST['donation'] ) ) {
		$donation = preg_replace( '[^0-9\.]', '', $_REQUEST['donation'] );
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
add_filter( 'pmpro_checkout_order', 'pmprodon_pmpro_checkout_order' );

/**
 * Show order components on confirmation and invoice pages.
 */
function pmprodon_pmpro_invoice_bullets_bottom( $order ) {
	$components = pmprodon_get_price_components( $order );
	if ( ! empty( $components['donation'] ) ) {
	?>
	<li><strong><?php _e( 'Membership Cost', 'pmpro-donations' ); ?>: </strong> <?php echo pmpro_formatPrice( $components['price'] ); ?></li>
	<li><strong><?php _e( 'Donation', 'pmpro-donations' ); ?>: </strong> <?php echo pmpro_formatPrice( $components['donation'] ); ?></li>
	<?php
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
 * Save donation amount into a session variable for PayPal Express.
 */
function pmprodon_pmpro_paypalexpress_session_vars() {
	if ( function_exists( 'pmpro_start_session' ) ) {
		pmpro_start_session();
	}

	// save our added fields in session while the user goes off to PayPal
	if ( isset( $_REQUEST['donation_dropdown'] ) ) {
		$_SESSION['donation_dropdown'] = $_REQUEST['donation_dropdown'];
	}
	if ( isset( $_REQUEST['donation'] ) ) {
		$_SESSION['donation'] = $_REQUEST['donation'];
	}
}
add_action( 'pmpro_paypalexpress_session_vars', 'pmprodon_pmpro_paypalexpress_session_vars' );

/**
 * If checking out for a level with donations, use SSL even if it's free
 *
 * @since .4
 */
function pmprodon_pmpro_checkout_preheader() {
	global $besecure;

	if ( ! is_admin() && ! empty( $_REQUEST['level'] ) ) {
		$level_id = intval( $_REQUEST['level'] );

		$donfields = get_option(
			'pmprodon_' . $level_id, array(
				'donations'       => 0,
				'min_price'       => '',
				'max_price'       => '',
				'dropdown_prices' => '',
				'text'            => '',
			)
		);

		if ( ! empty( $donfields ) && ! empty( $donfields['donations'] ) ) {
			$besecure = pmpro_getOption( 'use_ssl' );
		}
	}
}
add_action( 'pmpro_checkout_preheader', 'pmprodon_pmpro_checkout_preheader' );