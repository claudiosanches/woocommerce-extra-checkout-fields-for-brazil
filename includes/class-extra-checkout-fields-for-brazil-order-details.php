<?php
/**
 * Customer data on the order pages and emails the customer sees.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extra_Checkout_Fields_For_Brazil_Order_Details class.
 *
 * WooCommerce lists the block checkout's contact fields under "Additional
 * information", next to any other extension's fields. The documents, birthdate,
 * gender and cell phone are shown in a section of their own instead, before
 * the addresses, for orders from either checkout.
 */
class Extra_Checkout_Fields_For_Brazil_Order_Details {

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		add_filter( 'woocommerce_filter_fields_for_order_confirmation', array( $this, 'hide_contact_fields' ), 10, 2 );
		add_filter( 'render_block_woocommerce/order-confirmation-additional-fields-wrapper', array( $this, 'drop_empty_additional_fields' ) );

		// Classic thank you page, My Account and the order confirmation block,
		// whose totals fire the same hook after the order table.
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'order_details' ) );

		// Between the customer details and the addresses.
		add_action( 'woocommerce_email_customer_details', array( $this, 'email_details' ), 15, 3 );
	}

	/**
	 * Keep this plugin's contact fields out of WooCommerce's additional
	 * information, which the section here replaces.
	 *
	 * @param bool  $show  Whether WooCommerce would show the field.
	 * @param array $field Field definition.
	 *
	 * @return bool
	 */
	public function hide_contact_fields( $show, $field ) {
		$key = Extra_Checkout_Fields_For_Brazil_Blocks::field_key( isset( $field['id'] ) ? $field['id'] : '' );

		if ( in_array( $key, Extra_Checkout_Fields_For_Brazil_Blocks::CONTACT_FIELDS, true ) ) {
			return false;
		}

		return $show;
	}

	/**
	 * Drop the order confirmation's additional information when no field is
	 * left in it.
	 *
	 * The block decides to show its heading from the stored values, before
	 * the fields are filtered, so hiding them all left the heading alone.
	 *
	 * @param string $content Rendered block.
	 *
	 * @return string
	 */
	public function drop_empty_additional_fields( $content ) {
		return false === strpos( $content, 'wc-block-components-additional-fields-list' ) ? '' : $content;
	}

	/**
	 * Customer data of an order, as label and value pairs.
	 *
	 * The person type is left out, since the document shown already says it.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array
	 */
	public static function get_fields( $order ) {
		$labels = array(
			'cpf'       => __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'rg'        => __( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'cnpj'      => __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'ie'        => __( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'birthdate' => __( 'Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'gender'    => __( 'Gender', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'cellphone' => __( 'Cell Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
		);
		$fields = array();

		foreach ( $labels as $key => $label ) {
			$value = trim( (string) $order->get_meta( Extra_Checkout_Fields_For_Brazil_Legacy_Sync::get_legacy_key( $key, 'billing', $order ) ) );

			if ( 'gender' === $key ) {
				$value = self::gender_label( $value );
			}

			if ( '' !== $value ) {
				$fields[] = array(
					'label' => $label,
					'value' => $value,
				);
			}
		}

		/**
		 * Filter the customer data shown on the order pages and emails.
		 *
		 * @param array    $fields Label and value pairs.
		 * @param WC_Order $order  Order.
		 */
		return apply_filters( 'csbmw_order_customer_data', $fields, $order );
	}

	/**
	 * Gender in the store's language, whichever way it was stored.
	 *
	 * @param string $value Stored gender.
	 *
	 * @return string
	 */
	protected static function gender_label( $value ) {
		$options = Extra_Checkout_Fields_For_Brazil_Blocks::get_gender_options();
		$key     = Extra_Checkout_Fields_For_Brazil_Legacy_Sync::to_block_value( 'gender', $value );

		return isset( $options[ $key ] ) ? $options[ $key ] : $value;
	}

	/**
	 * Print the customer data on an order page.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return void
	 */
	public function order_details( $order ) {
		$fields = $order instanceof WC_Order ? self::get_fields( $order ) : array();

		if ( $fields ) {
			require __DIR__ . '/views/html-order-customer-data.php';
		}
	}

	/**
	 * Print the customer data in an order email.
	 *
	 * @param WC_Order $order         Order.
	 * @param bool     $sent_to_admin Whether the email goes to the store.
	 * @param bool     $plain_text    Whether the email is plain text.
	 *
	 * @return void
	 */
	public function email_details( $order, $sent_to_admin = false, $plain_text = false ) {
		$fields = $order instanceof WC_Order ? self::get_fields( $order ) : array();

		if ( ! $fields ) {
			return;
		}

		if ( $plain_text ) {
			require __DIR__ . '/views/emails/plain-order-customer-data.php';
		} else {
			require __DIR__ . '/views/emails/html-order-customer-data.php';
		}
	}
}

new Extra_Checkout_Fields_For_Brazil_Order_Details();
