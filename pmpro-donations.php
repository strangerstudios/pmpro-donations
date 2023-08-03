<?php
/*
Plugin Name: Paid Memberships Pro - Donations
Plugin URI: https://www.paidmembershipspro.com/add-ons/donations-add-on/
Description: Allow customers to set an additional donation amount at checkout.
Version: 1.1.1
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
 * Enqueue CSS assets.
 *
 * @since TBD
 */
function pmpro_donations_css_assets() {
	wp_register_style( 'pmpro-donations-css', plugins_url( 'css/pmpro-donations.css', __FILE__ ) );
	wp_enqueue_style( 'pmpro-donations-css' );
}
add_action( 'wp_enqueue_scripts', 'pmpro_donations_css_assets' );