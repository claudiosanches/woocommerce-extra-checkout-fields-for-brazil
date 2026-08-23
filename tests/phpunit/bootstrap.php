<?php
/**
 * PHPUnit bootstrap.
 *
 * The unit suite runs without WordPress. The integration suite needs the
 * WordPress test library and WooCommerce, which wp-env provides.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

define( 'CSBMW_PLUGIN_DIR', dirname( __DIR__, 2 ) );

$csbmw_wp_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $csbmw_wp_tests_dir ) {
	$csbmw_wp_tests_dir = '/wordpress-phpunit';
}

// Integration suite: boot WordPress with WooCommerce and this plugin active.
if ( file_exists( $csbmw_wp_tests_dir . '/includes/functions.php' ) ) {
	require_once $csbmw_wp_tests_dir . '/includes/functions.php';

	tests_add_filter(
		'muplugins_loaded',
		static function () {
			require_once WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
			require_once CSBMW_PLUGIN_DIR . '/woocommerce-extra-checkout-fields-for-brazil.php';
		}
	);

	require $csbmw_wp_tests_dir . '/includes/bootstrap.php';

	// WooCommerce needs its tables before any order or customer can be saved.
	if ( ! function_exists( 'wc_get_order' ) ) {
		echo 'WooCommerce was not loaded. Is it installed in the test environment?' . PHP_EOL;
		exit( 1 );
	}

	WC_Install::install();

	return;
}

// Unit suite: load only the classes that do not touch WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', CSBMW_PLUGIN_DIR . '/' );
}

require_once CSBMW_PLUGIN_DIR . '/includes/class-extra-checkout-fields-for-brazil-formatting.php';
