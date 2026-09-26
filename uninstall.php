<?php
/**
 * Uninstall file.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Uninstall
 */

// If uninstall not called from WordPress exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

delete_option( 'wcbcf_settings' );
delete_option( 'wcbcf_version' );

// The CEP table is shared with WooCommerce Correios, so it stays.
delete_option( 'wcbcf_postcodes_db_version' );
