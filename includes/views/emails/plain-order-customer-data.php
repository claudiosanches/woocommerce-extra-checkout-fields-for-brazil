<?php
/**
 * Customer data in a plain text order email.
 *
 * @package Extra_Checkout_Fields_For_Brazil/View
 *
 * @var array $fields Label and value pairs.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "\n" . esc_html( wc_strtoupper( __( 'Customer data', 'woocommerce-extra-checkout-fields-for-brazil' ) ) ) . "\n\n";

foreach ( $fields as $field ) {
	echo esc_html( $field['label'] ) . ': ' . esc_html( $field['value'] ) . "\n";
}
