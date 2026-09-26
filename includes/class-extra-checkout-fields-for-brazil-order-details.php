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
	 * Block that shows the customer data on the order confirmation.
	 *
	 * @var string
	 */
	const BLOCK = 'csbmw/order-customer-data';

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_filter( 'woocommerce_filter_fields_for_order_confirmation', array( $this, 'hide_contact_fields' ), 10, 2 );
		add_filter( 'render_block_woocommerce/order-confirmation-additional-fields-wrapper', array( $this, 'drop_empty_additional_fields' ) );

		// Before WooCommerce's order confirmation blocks add theirs.
		add_action( 'enqueue_block_editor_assets', array( $this, 'hide_contact_fields_from_editor' ), 5 );

		// Classic thank you page and My Account.
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
	 * Keep this plugin's contact fields out of the editor preview of the order
	 * confirmation's additional information.
	 *
	 * The preview lists the contact fields WooCommerce publishes to the
	 * editor, without the filter the order confirmation applies. WooCommerce
	 * keeps the first value registered under a key, so the list without them
	 * is registered first.
	 *
	 * @return void
	 */
	public function hide_contact_fields_from_editor() {
		if ( ! class_exists( Automattic\WooCommerce\Blocks\Package::class ) ) {
			return;
		}

		$container = Automattic\WooCommerce\Blocks\Package::container();
		$fields    = $container->get( Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class )->get_fields_for_location( 'contact' );

		$container->get( Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::class )->add(
			'additionalContactFields',
			array_filter(
				$fields,
				static function ( $id ) {
					return ! in_array( Extra_Checkout_Fields_For_Brazil_Blocks::field_key( $id ), Extra_Checkout_Fields_For_Brazil_Blocks::CONTACT_FIELDS, true );
				},
				ARRAY_FILTER_USE_KEY
			)
		);
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
	 * Register the order confirmation block.
	 *
	 * Block hooks place it after the order totals, so it shows in the order
	 * confirmation template and can be moved or removed there.
	 *
	 * @return void
	 */
	public function register_block() {
		register_block_type(
			dirname( CSBMW_PLUGIN_FILE ) . '/build/blocks/order-customer-data',
			array(
				'render_callback' => array( $this, 'render_block' ),
			)
		);
	}

	/**
	 * Render the order confirmation block.
	 *
	 * @return string
	 */
	public function render_block() {
		// The editor previews the block with sample values, as the template
		// being edited has no order.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_theme_options' ) ) {
			return self::render_fields( self::sample_fields(), 'class="csbmw-order-customer-data"' );
		}

		$order = wc_get_order( absint( get_query_var( 'order-received' ) ) );

		if ( ! $order instanceof WC_Order || ! self::can_view_order() ) {
			return '';
		}

		return self::render_fields( self::get_fields( $order ), get_block_wrapper_attributes( array( 'class' => 'csbmw-order-customer-data' ) ) );
	}

	/**
	 * Whether the visitor may see the order the confirmation page is for.
	 *
	 * WooCommerce decides it for its own order confirmation blocks: a valid
	 * order key, then the order's customer, or a guest within the grace period
	 * or after verifying the email. Its wrapper blocks print their content
	 * only when allowed, so one is asked instead of repeating those rules.
	 *
	 * @return bool
	 */
	protected static function can_view_order() {
		return '' !== render_block(
			array(
				'blockName'    => 'woocommerce/order-confirmation-totals-wrapper',
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHTML'    => '1',
				'innerContent' => array( '1' ),
			)
		);
	}

	/**
	 * Values the editor preview shows.
	 *
	 * @return array
	 */
	protected static function sample_fields() {
		return array(
			array(
				'label' => __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'value' => '123.456.789-09',
			),
			array(
				'label' => __( 'Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'value' => '01/02/1990',
			),
			array(
				'label' => __( 'Cell Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'value' => '(11) 91234-5678',
			),
		);
	}

	/**
	 * Markup of the customer data section.
	 *
	 * @param array  $fields             Label and value pairs.
	 * @param string $wrapper_attributes Attributes of the section.
	 *
	 * @return string
	 */
	protected static function render_fields( $fields, $wrapper_attributes ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- The view prints it.
		if ( ! $fields ) {
			return '';
		}

		ob_start();
		require __DIR__ . '/views/html-order-customer-data.php';

		return ob_get_clean();
	}

	/**
	 * Print the customer data on an order page.
	 *
	 * An order confirmation template that has the block, or had it removed
	 * in the Site Editor, leaves it to the block. One the block could not be
	 * hooked into gets the section here.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return void
	 */
	public function order_details( $order ) {
		if ( ! $order instanceof WC_Order || ( is_order_received_page() && self::template_has_block() ) ) {
			return;
		}

		echo self::render_fields( self::get_fields( $order ), 'class="wc-block-order-confirmation-additional-fields-wrapper csbmw-order-customer-data"' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the view.
	}

	/**
	 * Whether the current block template has the block or had it removed,
	 * template parts included.
	 *
	 * @return bool
	 */
	protected static function template_has_block() {
		global $_wp_current_template_content;

		if ( ! wp_is_block_theme() || empty( $_wp_current_template_content ) ) {
			return false;
		}

		return self::blocks_contain( parse_blocks( $_wp_current_template_content ) );
	}

	/**
	 * Whether a block list has the block or had it removed.
	 *
	 * A hooked block removed in the Site Editor stays named in the metadata
	 * of the block it was hooked to, so it is not inserted again.
	 *
	 * @param array $blocks Parsed blocks.
	 *
	 * @return bool
	 */
	protected static function blocks_contain( $blocks ) {
		foreach ( $blocks as $block ) {
			$ignored = isset( $block['attrs']['metadata']['ignoredHookedBlocks'] ) ? (array) $block['attrs']['metadata']['ignoredHookedBlocks'] : array();

			if ( self::BLOCK === $block['blockName'] || in_array( self::BLOCK, $ignored, true ) ) {
				return true;
			}

			if ( 'core/template-part' === $block['blockName'] && ! empty( $block['attrs']['slug'] ) ) {
				$theme = isset( $block['attrs']['theme'] ) ? $block['attrs']['theme'] : get_stylesheet();
				$part  = get_block_template( $theme . '//' . $block['attrs']['slug'], 'wp_template_part' );

				if ( $part && self::blocks_contain( parse_blocks( $part->content ) ) ) {
					return true;
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && self::blocks_contain( $block['innerBlocks'] ) ) {
				return true;
			}
		}

		return false;
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
