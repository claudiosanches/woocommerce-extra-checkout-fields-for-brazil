<?php
/**
 * CEP field with its button and the Correios search link.
 *
 * @package Extra_Checkout_Fields_For_Brazil/View
 *
 * @var string $form_id    Input id.
 * @var string $form_label Visible label.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$button_class = wc_wp_theme_get_element_class_name( 'button' );
?>
<form class="csbmw-shipping-calculator-form" novalidate>
	<label class="csbmw-shipping-calculator-label" for="<?php echo esc_attr( $form_id ); ?>"><?php echo esc_html( $form_label ); ?></label>
	<div class="csbmw-shipping-calculator-row">
		<input class="csbmw-shipping-calculator-input input-text" type="text" inputmode="numeric" autocomplete="postal-code" id="<?php echo esc_attr( $form_id ); ?>" name="postcode" placeholder="<?php esc_attr_e( 'Enter your CEP', 'woocommerce-extra-checkout-fields-for-brazil' ); ?>" aria-describedby="<?php echo esc_attr( $form_id ); ?>-error" />
		<button class="csbmw-shipping-calculator-button button<?php echo esc_attr( $button_class ? ' ' . $button_class : '' ); ?>" type="submit"><?php esc_html_e( 'Get quote', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></button>
	</div>
	<a class="csbmw-shipping-calculator-find" href="<?php echo esc_url( Extra_Checkout_Fields_For_Brazil_Shipping::find_postcode_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'I don\'t know my CEP', 'woocommerce-extra-checkout-fields-for-brazil' ); ?><?php echo Extra_Checkout_Fields_For_Brazil_Shipping::icon( 'arrow-top-right-on-square' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
	<div class="csbmw-shipping-calculator-error" id="<?php echo esc_attr( $form_id ); ?>-error" aria-live="polite"></div>
</form>
