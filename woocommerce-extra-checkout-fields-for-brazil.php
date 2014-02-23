<?php
/**
 * WooCommerce Extra Checkout Fields for Brazil.
 *
 * @package   Extra_Checkout_Fields_For_Brazil_Admin
 * @author    Claudio Sanches <contato@claudiosmweb.com>
 * @license   GPL-2.0+
 * @copyright 2013 Claudio Sanches
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Extra Checkout Fields for Brazil
 * Plugin URI:        https://github.com/claudiosmweb/woocommerce-extra-checkout-fields-for-brazil
 * Description:       Adiciona novos campos para Pessoa Física ou Jurídica, Data de Nascimento, Sexo, Número, Bairro e Celular. Além de máscaras em campos, aviso de e-mail incorreto e auto preenchimento dos campos de endereço pelo CEP.
 * Version:           2.9.1
 * Author:            claudiosanches
 * Author URI:        http://claudiosmweb.com/
 * Text Domain:       woocommerce-extra-checkout-fields-for-brazil
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/claudiosmweb/woocommerce-extra-checkout-fields-for-brazil
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Main class.
 */
require_once( plugin_dir_path( __FILE__ ) . 'public/class-wc-ecfb.php' );

/**
 * Activation and deactivation methods.
 */
register_activation_hook( __FILE__, array( 'Extra_Checkout_Fields_For_Brazil', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Extra_Checkout_Fields_For_Brazil', 'deactivate' ) );

/**
 * Initialize the plugin.
 */
add_action( 'plugins_loaded', array( 'Extra_Checkout_Fields_For_Brazil', 'get_instance' ) );

/**
 * Plugins support.
 */
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-wc-ecfb-plugins-support.php' );
add_action( 'plugins_loaded', array( 'Extra_Checkout_Fields_For_Brazil_Plugins_Support', 'get_instance' ) );

/**
 * Administration functions.
 */
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-wc-ecfb-admin.php' );
	add_action( 'plugins_loaded', array( 'Extra_Checkout_Fields_For_Brazil_Admin', 'get_instance' ) );
}
