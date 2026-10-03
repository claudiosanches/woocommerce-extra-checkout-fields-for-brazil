<?php
/**
 * Customer data in an HTML order email, in the markup WooCommerce uses for
 * its additional fields.
 *
 * @package Extra_Checkout_Fields_For_Brazil/View
 *
 * @var array $fields Label and value pairs.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h2><?php esc_html_e( 'Customer data', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></h2>
<ul class="additional-fields" style="margin-bottom: 40px;">
	<?php foreach ( $fields as $field ) : ?>
		<li><strong><?php echo esc_html( $field['label'] ); ?></strong>: <?php echo esc_html( $field['value'] ); ?></li>
	<?php endforeach; ?>
</ul>
