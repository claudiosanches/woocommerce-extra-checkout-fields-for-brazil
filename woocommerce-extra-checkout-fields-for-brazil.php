<?php
/**
 * Plugin Name: Brazilian Market on WooCommerce
 * Plugin URI:  https://github.com/claudiosanches/woocommerce-extra-checkout-fields-for-brazil
 * Description: Adds new checkout fields, field masks and other things necessary to properly work with WooCommerce on Brazil.
 * Author:      Claudio Sanches
 * Author URI:  https://claudiosanches.com
 * Version:     4.0.0-dev
 * License:     GPL-3.0+
 * Text Domain: woocommerce-extra-checkout-fields-for-brazil
 * Domain Path: /languages
 *
 * Brazilian Market on WooCommerce is free software: you can
 * redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation,
 * either version 3 of the License, or any later version.
 *
 * Brazilian Market on WooCommerce is distributed in the hope
 * that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Brazilian Market on WooCommerce. If not, see
 * <https://www.gnu.org/licenses/gpl-3.0.txt>.
 *
 * @package ClaudioSanches\BrazilianMarket
 */

defined( 'ABSPATH' ) || exit;

if ( version_compare( PHP_VERSION, '5.6.0', '<' ) ) {
	/**
	 * Function compatible with PHP 5.2 to display a notice about unsupported
	 * php version.
	 *
	 * @return void
	 */
	function cs_brazilian_market_unsupported_php_version_notice() {
		$plugin_name = plugin_basename( __FILE__ );
		$disable_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'        => 'deactivate',
					'plugin'        => rawurlencode( $plugin_name ),
					'plugin_status' => 'all',
					'paged'         => '1',
					's'             => '',
				),
				admin_url( 'plugins.php' )
			),
			'deactivate-plugin_' . $plugin_name
		);

		echo '<div class="notice notice-error">';
		echo '<p>';
		/* translators: %s: wp docs */
		echo wp_kses_post( sprintf( __( 'The "Brazilian Market on WooCommerce" plugin requires at least PHP 5.6 to work (<a href="%s">learn how to update</a>), update the PHP version of your server or disable the plugin to disable this notification.', 'woocommerce-extra-checkout-fields-for-brazil' ), 'https://wordpress.org/support/update-php/' ) );
		echo '</p>';
		echo '<p><a href="' . esc_url( $disable_url ) . '" class="button-primary">' . esc_html__( 'Disable "Brazilian Market on WooCommerce" plugin', 'woocommerce-extra-checkout-fields-for-brazil' ) . '</a></p>';
		echo '</div>';
	}

	add_action( 'admin_notices', 'cs_brazilian_market_unsupported_php_version_notice' );
	return;
}
