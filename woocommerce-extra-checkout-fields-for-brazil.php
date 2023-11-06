<?php
/**
 * Plugin Name: Brazilian Market on WooCommerce
 * Plugin URI: https://github.com/claudiosanches/woocommerce-extra-checkout-fields-for-brazil
 * Description: Adds new checkout fields, field masks and other things necessary to properly work with WooCommerce on Brazil.
 * Author: Claudio Sanches
 * Author URI: https://claudiosanches.com
 * Version: 4.0.0
 * Requires at least: 4.0
 * Requires PHP: 5.6
 * License: GPLv2 or later
 * Text Domain: woocommerce-extra-checkout-fields-for-brazil
 * Domain Path: /languages
 * WC requires at least: 5.0
 * WC tested up to: 8.2
 *
 * Brazilian Market on WooCommerce is free software: you can
 * redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation,
 * either version 2 of the License, or any later version.
 *
 * Brazilian Market on WooCommerce is distributed in the hope
 * that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Brazilian Market on WooCommerce. If not, see
 * <https://www.gnu.org/licenses/gpl-2.0.txt>.
 *
 * @package Extra_Checkout_Fields_For_Brazil
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Plugin file.
 */
if ( ! defined( 'CSBMW_PLUGIN_FILE' ) ) {
	define( 'CSBMW_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-extra-checkout-fields-for-brazil.php';

	/**
	 * Initialize the plugin.
	 */
	add_action( 'plugins_loaded', array( 'Extra_Checkout_Fields_For_Brazil', 'get_instance' ) );
}
