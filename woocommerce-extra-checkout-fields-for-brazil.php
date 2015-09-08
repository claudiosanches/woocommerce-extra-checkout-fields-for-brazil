<?php
/**
 * Plugin Name: WooCommerce Extra Checkout Fields for Brazil
 * Plugin URI: https://github.com/claudiosmweb/woocommerce-extra-checkout-fields-for-brazil
 * Description: Adiciona novos campos para Pessoa Física ou Jurídica, Data de Nascimento, Sexo, Número, Bairro e Celular. Além de máscaras em campos, aviso de e-mail incorreto e auto preenchimento dos campos de endereço pelo CEP.
 * Version: 3.4.1
 * Author: Claudio Sanches
 * Author URI: http://claudiosmweb.com/
 * Text Domain: woocommerce-extra-checkout-fields-for-brazil
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
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
	const VERSION = '3.4.1';

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
		if ( null == self::$instance ) {
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
		include_once 'includes/class-wc-ecfb-formatting.php';
		include_once 'includes/class-wc-ecfb-front-end.php';
		include_once 'includes/class-wc-ecfb-plugins-support.php';
		include_once 'includes/class-wc-ecfb-api.php';
	}

	/**
	 * Admin includes.
	 */
	private function admin_includes() {
		include_once 'includes/admin/class-wc-ecfb-admin.php';
		include_once 'includes/admin/class-wc-ecfb-settings.php';
		include_once 'includes/admin/class-wc-ecfb-order.php';
		include_once 'includes/admin/class-wc-ecfb-customer.php';
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
