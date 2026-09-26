<?php
/**
 * Customer data on an order page, in the markup and heading size WooCommerce
 * uses for its additional fields.
 *
 * @package Extra_Checkout_Fields_For_Brazil/View
 *
 * @var array  $fields             Label and value pairs.
 * @var string $wrapper_attributes Attributes of the section.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<h2 class="wp-block-heading" style="font-size:24px"><?php esc_html_e( 'Customer data', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></h2>
	<div class="wc-block-order-confirmation-additional-fields">
		<dl class="wc-block-components-additional-fields-list">
			<?php foreach ( $fields as $field ) : ?>
				<dt><?php echo esc_html( $field['label'] ); ?></dt>
				<dd><?php echo esc_html( $field['value'] ); ?></dd>
			<?php endforeach; ?>
		</dl>
	</div>
</section>
