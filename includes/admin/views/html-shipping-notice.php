<?php
/**
 * Notice explaining what the CEP calculators need.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Admin/View
 *
 * @var string $url Link that restricts the store to Brazil.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="notice notice-warning inline bmw-settings-notice">
	<p>
		<?php
		printf(
			/* translators: %s: link to the WooCommerce general settings */
			esc_html__( 'The shipping calculators ask only for the CEP, so they work when the store sells and ships only to Brazil. Change it in %s, or let the plugin do it.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=general' ) ) . '">' . esc_html__( 'WooCommerce > Settings > General', 'woocommerce-extra-checkout-fields-for-brazil' ) . '</a>'
		);
		?>
	</p>
	<p><a class="button" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Sell and ship only to Brazil', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></a></p>
</div>
