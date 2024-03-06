<?php
/*
Plugin Name: Paid Memberships Pro - Donations
Plugin URI: https://www.paidmembershipspro.com/add-ons/donations-add-on/
Description: Allow customers to set an additional donation amount at checkout.
Version: 1.1.3
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com/
Text Domain: pmpro-donations
Domain Path: /languages
*/

// Definitions
define( 'PMPRODON_DIR', dirname( __FILE__ ) );
define( 'PMPRODON_BASENAME', plugin_basename( __FILE__ ) );

// Includes
require_once( PMPRODON_DIR . '/includes/common.php' );
require_once( PMPRODON_DIR . '/includes/checkout.php' );
require_once( PMPRODON_DIR . '/includes/donation-only-level.php' );
require_once( PMPRODON_DIR . '/includes/level-settings.php' );

/**
 * Load the languages folder for translations.
 */
function pmprodon_load_textdomain(){
	load_plugin_textdomain( 'pmpro-donations', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'plugins_loaded', 'pmprodon_load_textdomain' );

/**
 * Function to add links to the plugin row meta
 */
function pmprodon_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'pmpro-donations.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/add-ons/donations-add-on/' ) . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/support/' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-donations' ) ) . '">' . __( 'Support', 'pmpro-donations' ) . '</a>',
		);
		$links     = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmprodon_plugin_row_meta', 10, 2 );



/**
 *  Function to add custom confirmation message.
 *
 * @param string $message The confirmation message.
 * @param object $invoice The MemberOrder object.
 * @return string $message The confirmation message.
 * @since TBD
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


	$settings = pmprodon_get_level_settings( $level_id );
	//Bail if not a donation level or donations are not enabled.
	if( ! $settings['donations'] ) {
		return $message;
	}

	$components = pmprodon_get_price_components( $invoice );
	//Bail if no donation amount.
	if (  empty( $components['donation'] ) ) {
		return $message;
	}

	$message_to_replace = '<p>' . wp_kses_post( $settings['confirmation_message'] ) . '</p>';
	if( strpos( $message, '!!donation_message!!' ) ) {
		$message = str_replace( '!!donation_message!!', $message_to_replace, $message );
	} else {
		$message .= $message_to_replace;
	}

	return $message;
}

/**
 * Function to add custom confirmation message.
 */
add_filter( 'pmpro_confirmation_message', 'pmprodon_pmpro_confirmation_message', 10, 2 );