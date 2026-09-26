<?php
/**
 * Admin announcements view.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Admin/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="bmw-settings-panel bmw-settings-news">
	<h2><?php esc_html_e( 'Important announcements', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></h2>
	<ul>
		<li>
			<strong><?php esc_html_e( 'Version 5.0.0 released', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></strong>
			<span><?php esc_html_e( 'Block checkout support, shipping calculators that ask only for the CEP and address autofill from the CEP.', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></span>
		</li>
		<li>
			<strong><?php esc_html_e( 'Shipping by CEP', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></strong>
			<span><?php esc_html_e( 'Customers can quote shipping on product pages and in the cart with the CEP alone. Turn it on in the Shipping section.', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></span>
		</li>
		<li>
			<strong><?php esc_html_e( 'New integration guidance', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></strong>
			<span><?php esc_html_e( 'Existing checkout field meta keys remain available for gateways, ERPs and invoicing integrations.', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></span>
		</li>
		<li>
			<strong><?php esc_html_e( 'Keep WooCommerce updated', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></strong>
			<span><?php esc_html_e( 'Use the latest supported WooCommerce release for the best checkout experience.', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></span>
		</li>
	</ul>
</div>
