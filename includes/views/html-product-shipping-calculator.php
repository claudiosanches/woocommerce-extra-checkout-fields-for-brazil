<?php
/**
 * Product page shipping calculator.
 *
 * @package Extra_Checkout_Fields_For_Brazil/View
 *
 * @var string $wrapper_attributes Attributes of the wrapper element.
 * @var int    $product_id         Product ID.
 * @var bool   $variable           Whether options must be chosen first.
 * @var string $postcode           CEP to quote on load, if known.
 * @var string $prefix             Prefix for element ids.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-product-id="<?php echo esc_attr( $product_id ); ?>" data-variable="<?php echo $variable ? '1' : '0'; ?>" data-postcode="<?php echo esc_attr( $postcode ); ?>">
	<div class="csbmw-shipping-calculator-card">
		<div class="csbmw-shipping-calculator-empty">
			<?php
			$form_id    = $prefix . '-postcode';
			$form_label = __( 'Calculate shipping and delivery time', 'woocommerce-extra-checkout-fields-for-brazil' );
			require __DIR__ . '/html-postcode-form.php';
			?>
		</div>
		<div class="csbmw-shipping-calculator-summary" hidden>
			<button class="csbmw-shipping-calculator-destination" type="button" aria-haspopup="dialog" aria-describedby="<?php echo esc_attr( $prefix ); ?>-change">
				<?php echo Extra_Checkout_Fields_For_Brazil_Shipping::icon( 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="csbmw-shipping-calculator-place"></span>
				<span class="screen-reader-text" id="<?php echo esc_attr( $prefix ); ?>-change"><?php esc_html_e( 'Change CEP', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></span>
				<?php echo Extra_Checkout_Fields_For_Brazil_Shipping::icon( 'arrow-path' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<div class="csbmw-shipping-calculator-results" aria-live="polite"></div>
		</div>
	</div>
	<dialog class="csbmw-shipping-calculator-dialog" aria-labelledby="<?php echo esc_attr( $prefix ); ?>-title">
		<div class="csbmw-shipping-calculator-dialog-header">
			<h2 class="csbmw-shipping-calculator-dialog-title" id="<?php echo esc_attr( $prefix ); ?>-title"><?php esc_html_e( 'Enter a CEP to see the shipping options', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></h2>
			<button class="csbmw-shipping-calculator-close" type="button" aria-label="<?php esc_attr_e( 'Close', 'woocommerce-extra-checkout-fields-for-brazil' ); ?>"><?php echo Extra_Checkout_Fields_For_Brazil_Shipping::icon( 'x-mark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
		</div>
		<?php
		$form_id    = $prefix . '-dialog-postcode';
		$form_label = __( 'CEP', 'woocommerce-extra-checkout-fields-for-brazil' );
		require __DIR__ . '/html-postcode-form.php';
		?>
	</dialog>
	<template class="csbmw-shipping-calculator-rate-template">
		<li class="csbmw-shipping-calculator-rate">
			<span class="csbmw-shipping-calculator-rate-label">
				<strong class="csbmw-shipping-calculator-rate-name"></strong>
				<span class="csbmw-shipping-calculator-rate-delivery"></span>
			</span>
			<strong class="csbmw-shipping-calculator-rate-cost"></strong>
		</li>
	</template>
</div>
