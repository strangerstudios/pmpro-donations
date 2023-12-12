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
 * Add donation amount to order meta.
 *
 * @param object The order object.
 * @since TBD
 */
function pmprodon_store_donation_amount_in_order_meta( $order ) {
	if ( isset( $_REQUEST['donation'] ) ) {
		update_pmpro_membership_order_meta( $order->id, 'donation_amount', sanitize_text_field( $_REQUEST['donation'] ) );
	}
}

add_action( 'pmpro_added_order','pmprodon_store_donation_amount_in_order_meta', 10, 1 );

/**
 * Add donation column to the export csv orders
 *
 * @param Array CSV document columns.
 * @return Array CSV document columns.
 * @since TBD
 */
function pmprodon_add_donation_column_to_export_orders_csv( $columns ){
	$columns["donation"] = "pmprodon_extra_order_column_donation";

	return $columns;
}

add_filter("pmpro_orders_csv_extra_columns", "pmprodon_add_donation_column_to_export_orders_csv", 10, 1);

/**
 * Add donation column to the export csv orders
 *
 * @param Object $order The order object.
 * @return String The donation amount.
 * @since TBD
 */
function pmprodon_extra_order_column_donation( $order ){
	$r = pmprodon_get_price_components( $order );
	return $r['donation'];
}