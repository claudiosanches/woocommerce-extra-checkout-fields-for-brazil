<?php
/**
 * Admin help message view.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Admin/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( apply_filters( 'wcbcf_help_message', true ) ) : ?>
	<div class="updated woocommerce-message inline">
		<p><?php echo esc_html( sprintf( __( 'Help us keep the %s plugin free making a donation or rate &#9733;&#9733;&#9733;&#9733;&#9733; on WordPress.org. Thank you in advance!', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'Brazilian Market on WooCommerce', 'woocommerce-extra-checkout-fields-for-brazil' ) ) ); ?></p>
		<p><a href="https://claudiosanches.com/doacoes/" target="_blank" class="button button-primary"><?php esc_html_e( 'Make a donation', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></a> <a href="https://wordpress.org/support/plugin/woocommerce-extra-checkout-fields-for-brazil/reviews/?filter=5#new-post" target="_blank" class="button button-secondary"><?php esc_html_e( 'Make a review', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></a></p>
	</div>
<?php endif;
