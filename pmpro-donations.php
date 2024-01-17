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
 * Filter the level cost text, which consists of all levels.
 *
 * @see pmpro_getLevelsCost
 *
 * @param string $r The level text.
 * @param array  $levels Array of level objects.
 * @param bool   $tags   Whether to include HTML tags or not (true or false).
 * @param bool   $short Whether the level text is shortened (true or false).
 *
 * @return string The level cost text.
 * @since TBD
 */
function pmprodon_pmpro_levels_cost_text($r, $levels, $tags, $short) {
	//Bail if PMPro is not active.
	if ( !function_exists( 'pmpro_is_checkout' ) ) {
		return $r;
	}
	//Bail if we're not on the checkout page.
	if ( !pmpro_is_checkout() ) {
		return $r;
	}

	global $pmpro_level;
	if(! pmprodon_is_donations_only( $pmpro_level->id )) {
		return $r;
	} else {
		$user = wp_get_current_user();
		$level = pmpro_getLevel( $user->membership_level );
		//If it's a donation only level, don't show the cost text.
		if ( $level && pmprodon_is_donations_only( $pmpro_level->id ) ) {
			return "";
		} else {
			return $r;
		}
	}
}

add_filter('pmpro_level_cost_text', 'pmprodon_pmpro_levels_cost_text', 10, 4);
