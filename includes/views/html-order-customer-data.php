<?php
/**
 * Customer data on an order page, in the markup WooCommerce uses for its
 * additional fields.
 *
 * @package Extra_Checkout_Fields_For_Brazil/View
 *
 * @var array $fields Label and value pairs.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="wc-block-order-confirmation-additional-fields-wrapper csbmw-order-customer-data">
	<h2><?php esc_html_e( 'Customer data', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></h2>
	<dl class="wc-block-components-additional-fields-list">
		<?php foreach ( $fields as $field ) : ?>
			<dt><?php echo esc_html( $field['label'] ); ?></dt>
			<dd><?php echo esc_html( $field['value'] ); ?></dd>
		<?php endforeach; ?>
	</dl>
</section>
