<?php
/**
 * Plugin Name: WooCommerce Extra Checkout Fields for Brazil
 * Plugin URI:  https://github.com/claudiosanches/woocommerce-extra-checkout-fields-for-brazil
 * Description: Adds new checkout fields, field masks and other things necessary to properly work with WooCommerce on Brazil.
 * Author:      Claudio Sanches
 * Author URI:  https://claudiosanches.com
 * Version:     3.6.0
 * License:     GPLv2 or later
 * Text Domain: woocommerce-extra-checkout-fields-for-brazil
 * Domain Path: /languages
 *
 * WooCommerce Extra Checkout Fields for Brazil is free software: you can
 * redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation,
 * either version 2 of the License, or any later version.
 *
 * WooCommerce Extra Checkout Fields for Brazil is distributed in the hope
 * that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WooCommerce Extra Checkout Fields for Brazil. If not, see
 * <https://www.gnu.org/licenses/gpl-2.0.txt>.
 *
 * @package Extra_Checkout_Fields_For_Brazil
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) :

/**
 * Plugin main class.
 */
class Extra_Checkout_Fields_For_Brazil {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '3.6.0';

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin.
	 *
	 */
	private function __construct() {
		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		if ( class_exists( 'WooCommerce' ) ) {
			if ( is_admin() ) {
				$this->admin_includes();
			}

			$this->includes();
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'woocommerce_fallback_notice' ) );
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Get assets url.
	 *
	 * @return string
	 */
	public static function get_assets_url() {
		return plugins_url( 'assets/', __FILE__ );
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-extra-checkout-fields-for-brazil' );

		load_textdomain( 'woocommerce-extra-checkout-fields-for-brazil', trailingslashit( WP_LANG_DIR ) . 'woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil-' . $locale . '.mo' );
		load_plugin_textdomain( 'woocommerce-extra-checkout-fields-for-brazil', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Includes.
	 */
	private function includes() {
		include_once dirname( __FILE__ ) . '/includes/class-extra-checkout-fields-for-brazil-formatting.php';
		include_once dirname( __FILE__ ) . '/includes/class-extra-checkout-fields-for-brazil-front-end.php';
		include_once dirname( __FILE__ ) . '/includes/class-extra-checkout-fields-for-brazil-integrations.php';
		include_once dirname( __FILE__ ) . '/includes/class-extra-checkout-fields-for-brazil-api.php';
	}

	/**
	 * Admin includes.
	 */
	private function admin_includes() {
		include_once dirname( __FILE__ ) . '/includes/admin/class-extra-checkout-fields-for-brazil-admin.php';
		include_once dirname( __FILE__ ) . '/includes/admin/class-extra-checkout-fields-for-brazil-settings.php';
		include_once dirname( __FILE__ ) . '/includes/admin/class-extra-checkout-fields-for-brazil-order.php';
		include_once dirname( __FILE__ ) . '/includes/admin/class-extra-checkout-fields-for-brazil-customer.php';
	}

	/**
	 * Action links.
	 *
	 * @param  array $links Default plugin links.
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links   = array();
		$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=woocommerce-extra-checkout-fields-for-brazil' ) ) . '">' . __( 'Settings', 'woocommerce-extra-checkout-fields-for-brazil' ) . '</a>';

		return array_merge( $plugin_links, $links );
	}

	/**
	 * WooCommerce fallback notice.
	 *
	 * @return string Fallack notice.
	 */
	public function woocommerce_fallback_notice() {
		echo '<div class="error"><p>' . sprintf( __( 'WooCommerce Extra Checkout Fields for Brazil depends on %s to work!', 'woocommerce-extra-checkout-fields-for-brazil' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">' . __( 'WooCommerce', 'woocommerce-extra-checkout-fields-for-brazil' ) . '</a>' ) . '</p></div>';
	}
}

/**
 * Initialize the plugin.
 */
add_action( 'plugins_loaded', array( 'Extra_Checkout_Fields_For_Brazil', 'get_instance' ) );

endif;
