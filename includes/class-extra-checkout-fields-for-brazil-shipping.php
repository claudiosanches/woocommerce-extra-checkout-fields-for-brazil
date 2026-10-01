<?php
/**
 * Shipping calculators that ask only for the CEP.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extra_Checkout_Fields_For_Brazil_Shipping class.
 */
class Extra_Checkout_Fields_For_Brazil_Shipping {

	/**
	 * WC AJAX endpoint estimating shipping for a product.
	 *
	 * @var string
	 */
	const ESTIMATE_ENDPOINT = 'csbmw_shipping_estimate';

	/**
	 * Script and style handle of the shipping calculators.
	 *
	 * @var string
	 */
	const HANDLE = 'woocommerce-extra-checkout-fields-for-brazil-shipping';

	/**
	 * Correios page for customers who do not know their CEP.
	 *
	 * @var string
	 */
	const FIND_POSTCODE_URL = 'https://buscacepinter.correios.com.br/app/endereco/index.php';

	/**
	 * Session key of the remembered CEP last written to the customer.
	 *
	 * @var string
	 */
	const APPLIED_POSTCODE = 'csbmw_applied_postcode';

	/**
	 * Shortcode printing the product calculator.
	 *
	 * @var string
	 */
	const SHORTCODE = 'csbmw_shipping_calculator';

	/**
	 * Hook and priority of each classic product page placement.
	 *
	 * @var array
	 */
	const PLACEMENTS = array(
		'after_add_to_cart'  => array( 'woocommerce_after_add_to_cart_form', 10 ),
		'before_add_to_cart' => array( 'woocommerce_before_add_to_cart_form', 10 ),
		'after_price'        => array( 'woocommerce_single_product_summary', 15 ),
		'after_summary'      => array( 'woocommerce_after_single_product_summary', 5 ),
	);

	/**
	 * Calculators placed by a block or shortcode on this page, as variation
	 * IDs by product ID.
	 *
	 * @var array
	 */
	protected $placed = array();

	/**
	 * Products the setting printed a calculator for on this page.
	 *
	 * @var int[]
	 */
	protected $automatic = array();

	/**
	 * Address the classic calculator found, and the CEP it replaces.
	 *
	 * @var array|null
	 */
	protected $calculated = null;

	/**
	 * Icons the product page calculator draws, as SVG path data.
	 *
	 * Outline icons from Heroicons (https://heroicons.com), MIT license,
	 * copyright Tailwind Labs, Inc.
	 *
	 * @var array
	 */
	const ICONS = array(
		'arrow-path'                => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99',
		'truck'                     => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
		'arrow-top-right-on-square' => 'M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25',
	);

	/**
	 * The close icon WooCommerce's block dialogs use, from the WordPress
	 * icons package. Filled, where the others are outlines.
	 *
	 * @var string
	 */
	const CLOSE_ICON = 'm13.06 12 6.47-6.47-1.06-1.06L12 10.94 5.53 4.47 4.47 5.53 10.94 12l-6.47 6.47 1.06 1.06L12 13.06l6.47 6.47 1.06-1.06L13.06 12Z';

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'localize_script' ) );
		add_action( 'wc_ajax_' . self::ESTIMATE_ENDPOINT, array( $this, 'ajax_estimate' ) );
		add_filter( 'woocommerce_shipping_free_shipping_is_available', array( $this, 'free_shipping_for_estimate' ), 10, 3 );
		add_shortcode( self::SHORTCODE, array( $this, 'shortcode' ) );

		foreach ( self::PLACEMENTS as $placement ) {
			add_action( $placement[0], array( $this, 'classic_product_calculator' ), $placement[1] );
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_product_style' ) );

		// Classic cart.
		add_filter( 'woocommerce_shipping_calculator_enable_country', array( $this, 'calculator_field_enabled' ) );
		add_filter( 'woocommerce_shipping_calculator_enable_state', array( $this, 'calculator_field_enabled' ) );
		add_filter( 'woocommerce_shipping_calculator_enable_city', array( $this, 'calculator_field_enabled' ) );
		add_filter( 'woocommerce_cart_calculate_shipping_address', array( $this, 'calculator_address' ) );
		add_action( 'woocommerce_calculated_shipping', array( $this, 'remember_calculator_address' ) );
		add_filter( 'woocommerce_customer_allowed_session_meta_keys', array( $this, 'session_meta_keys' ) );
		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'apply_remembered_postcode' ) );
		add_action( 'woocommerce_before_shipping_calculator', array( $this, 'enqueue_calculator_script' ) );

		// Cart block.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_cart_block_calculator' ) );
	}

	/**
	 * Whether a setting is checked.
	 *
	 * @param string $key Setting key.
	 *
	 * @return bool
	 */
	protected static function setting( $key ) {
		$settings = (array) get_option( 'wcbcf_settings', array() );

		return ! empty( $settings[ $key ] );
	}

	/**
	 * Where classic product pages print the calculator, empty when they do not.
	 *
	 * @return string Key of PLACEMENTS.
	 */
	public static function placement() {
		$settings  = (array) get_option( 'wcbcf_settings', array() );
		$placement = isset( $settings['product_shipping_calculator'] ) ? (string) $settings['product_shipping_calculator'] : '';

		return isset( self::PLACEMENTS[ $placement ] ) ? $placement : '';
	}

	/**
	 * Whether WooCommerce ships to Brazil and nowhere else.
	 *
	 * @return bool
	 */
	public static function is_brazil_only() {
		return Extra_Checkout_Fields_For_Brazil::is_brazil_only( 'shipping' );
	}

	/**
	 * Whether the cart calculator asks only for the CEP.
	 *
	 * @return bool
	 */
	public static function is_postcode_only() {
		return self::setting( 'postcode_only_calculator' ) && self::is_brazil_only();
	}

	/**
	 * Correios page for customers who do not know their CEP.
	 *
	 * @return string
	 */
	public static function find_postcode_url() {
		/**
		 * Filter the link offered to customers who do not know their CEP.
		 *
		 * @since 5.0.0
		 *
		 * @param string $url URL.
		 */
		return apply_filters( 'csbmw_find_postcode_url', self::FIND_POSTCODE_URL );
	}

	/**
	 * Register the calculator scripts and block.
	 *
	 * @return void
	 */
	public function register_block() {
		Extra_Checkout_Fields_For_Brazil_Assets::register_script( self::HANDLE, 'shipping' );
		Extra_Checkout_Fields_For_Brazil_Assets::register_style( self::HANDLE, 'shipping' );
		Extra_Checkout_Fields_For_Brazil_Assets::set_translations( self::HANDLE );

		$block = register_block_type(
			dirname( CSBMW_PLUGIN_FILE ) . '/build/blocks/shipping-calculator',
			array(
				'render_callback' => array( $this, 'render_block' ),
			)
		);

		// WordPress registers the editor script from block.json, and looks for
		// its translations where WordPress.org installs them, not in the
		// plugin.
		if ( $block ) {
			foreach ( $block->editor_script_handles as $handle ) {
				Extra_Checkout_Fields_For_Brazil_Assets::set_translations( $handle );
			}
		}
	}

	/**
	 * Pass the calculator scripts their settings.
	 *
	 * Runs once the theme is known, since the notice markup depends on it.
	 *
	 * @return void
	 */
	public function localize_script() {
		wp_localize_script(
			self::HANDLE,
			'bmwShippingParams',
			array(
				'postcodeUrl'      => WC_AJAX::get_endpoint( Extra_Checkout_Fields_For_Brazil_Postcodes::AJAX_ENDPOINT ),
				'estimateUrl'      => WC_AJAX::get_endpoint( self::ESTIMATE_ENDPOINT ),
				'findPostcodeUrl'  => self::find_postcode_url(),
				'findPostcodeIcon' => self::icon( 'arrow-top-right-on-square' ),
				'postcodeOnly'     => self::is_postcode_only() ? 'yes' : 'no',
				'notices'          => array(
					'error'  => self::notice_template( 'error' ),
					'notice' => self::notice_template( 'notice' ),
				),
			)
		);
	}

	/**
	 * WooCommerce notice markup for the current theme, with %s for the message.
	 *
	 * @param string $type Notice type.
	 *
	 * @return string
	 */
	protected static function notice_template( $type ) {
		return (string) wc_print_notice( '%s', $type, array(), true );
	}

	/**
	 * CEP a logged-in customer ships to, to quote on the first visit.
	 *
	 * A guest's CEP is kept by the browser instead: a page cache would serve
	 * one guest's CEP to everyone else.
	 *
	 * @return string
	 */
	protected static function customer_postcode() {
		$customer = WC()->customer;

		if ( ! is_user_logged_in() || ! $customer instanceof WC_Customer || 'BR' !== $customer->get_shipping_country() ) {
			return '';
		}

		return Extra_Checkout_Fields_For_Brazil_Postcodes::sanitize( $customer->get_shipping_postcode() );
	}

	/**
	 * An inline SVG icon.
	 *
	 * @param string $name Key of ICONS.
	 *
	 * @return string
	 */
	public static function icon( $name ) {
		if ( 'close' === $name ) {
			return '<svg class="csbmw-shipping-calculator-icon" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="' . esc_attr( self::CLOSE_ICON ) . '" fill="currentColor"/></svg>';
		}

		return '<svg class="csbmw-shipping-calculator-icon" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="' . esc_attr( self::ICONS[ $name ] ) . '" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	}

	/**
	 * Write an address found by CEP to one of the customer's addresses.
	 *
	 * A different CEP means a different place, so the street, complement,
	 * number and neighborhood are replaced along with it. The same CEP only
	 * fills the ones still empty.
	 *
	 * @param WC_Customer $customer Customer.
	 * @param string      $type     Address type, billing or shipping.
	 * @param array       $found    Address found by CEP.
	 * @param string      $previous CEP the address held before.
	 *
	 * @return void
	 */
	public static function set_customer_address( $customer, $type, $found, $previous ) {
		$changed = Extra_Checkout_Fields_For_Brazil_Postcodes::sanitize( $previous ) !== $found['postcode'];

		// One by one, since set_{$type}_location() also empties the street.
		$location = array(
			'country'  => 'BR',
			'state'    => $found['state'],
			'postcode' => wc_format_postcode( $found['postcode'], 'BR' ),
			'city'     => $found['city'],
		);

		foreach ( $location as $key => $value ) {
			call_user_func( array( $customer, "set_{$type}_{$key}" ), $value );
		}

		$fields = array(
			'address_1'    => $found['address'],
			'address_2'    => '',
			'neighborhood' => $found['neighborhood'],
			'number'       => '',
		);

		foreach ( $fields as $key => $value ) {
			$meta    = in_array( $key, array( 'neighborhood', 'number' ), true );
			$current = $meta ? $customer->get_meta( Extra_Checkout_Fields_For_Brazil_Legacy_Sync::get_legacy_key( $key, $type, $customer ) ) : call_user_func( array( $customer, "get_{$type}_{$key}" ) );

			if ( ! $changed && '' !== (string) $current ) {
				continue;
			}

			if ( ! $meta ) {
				call_user_func( array( $customer, "set_{$type}_{$key}" ), $value );
				continue;
			}

			// Both checkouts read these, each from its own key.
			$customer->update_meta_data( Extra_Checkout_Fields_For_Brazil_Legacy_Sync::get_legacy_key( $key, $type, $customer ), $value );
			$customer->update_meta_data( Extra_Checkout_Fields_For_Brazil_Legacy_Sync::get_block_key( $key, $type ), $value );
		}
	}

	/**
	 * Render the product shipping calculator block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 *
	 * @return string
	 */
	public function render_block( $attributes, $content, $block ) {
		// The editor previews the empty calculator, as a template has no
		// product to quote.
		if ( wp_is_serving_rest_request() && current_user_can( 'edit_posts' ) ) {
			$wrapper_attributes = 'class="csbmw-shipping-calculator"';
			$product_id         = 0;
			$variation_id       = 0;
			$variable           = false;
			$product_url        = '';
			$automatic          = false;
			$postcode           = '';
			$prefix             = 'csbmw-shipping-preview';
			$inline             = false;

			ob_start();
			include __DIR__ . '/views/html-product-shipping-calculator.php';

			return (string) ob_get_clean();
		}

		if ( ! empty( $attributes['productId'] ) ) {
			$product_id   = absint( $attributes['productId'] );
			$variation_id = isset( $attributes['variationId'] ) ? absint( $attributes['variationId'] ) : 0;
		} else {
			$product_id   = isset( $block->context['postId'] ) ? $block->context['postId'] : get_the_ID();
			$variation_id = 0;
		}

		$product = wc_get_product( $variation_id ? $variation_id : $product_id );

		if ( ! $product || ( $variation_id && $product->get_parent_id() !== $product_id ) ) {
			return '';
		}

		$inline = isset( $attributes['changePostcodeIn'] ) && 'block' === $attributes['changePostcodeIn'];

		return $this->get_product_calculator( $product, get_block_wrapper_attributes( array( 'class' => 'csbmw-shipping-calculator' ) ), $inline );
	}

	/**
	 * Render the calculator shortcode.
	 *
	 * @param array|string $atts Shortcode attributes: id, a product or
	 *                           variation ID, the current product when left
	 *                           out, and change_postcode_in, dialog or block.
	 *
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'                 => 0,
				'change_postcode_in' => 'dialog',
			),
			$atts,
			self::SHORTCODE
		);

		$product = wc_get_product( absint( $atts['id'] ) ? absint( $atts['id'] ) : get_the_ID() );

		if ( ! $product ) {
			return '';
		}

		return $this->get_product_calculator( $product, 'class="csbmw-shipping-calculator"', 'block' === $atts['change_postcode_in'] );
	}

	/**
	 * Load the calculator styles in the head of the pages a classic hook or
	 * the shortcode prints it on.
	 *
	 * Printed mid-page, the calculator would leave its styles in the footer,
	 * after the theme's Additional CSS, overriding it.
	 *
	 * @return void
	 */
	public function enqueue_product_style() {
		if ( ! self::is_brazil_only() ) {
			return;
		}

		$post   = get_post();
		$placed = is_singular() && $post && ( has_shortcode( $post->post_content, self::SHORTCODE ) || has_shortcode( $post->post_excerpt, self::SHORTCODE ) );

		if ( $placed || ( is_product() && self::placement() ) ) {
			wp_enqueue_style( self::HANDLE );
		}
	}

	/**
	 * Print the calculator where the setting places it on a product page.
	 *
	 * @return void
	 */
	public function classic_product_calculator() {
		global $product;

		$placement = self::placement();

		if ( '' === $placement || current_action() !== self::PLACEMENTS[ $placement ][0] || ! $product instanceof WC_Product || self::is_placed( $product ) ) {
			return;
		}

		echo $this->get_product_calculator( $product, 'class="csbmw-shipping-calculator"', false, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Whether a block or shortcode places the product's calculator further
	 * down its page, in the block template or the product's descriptions.
	 *
	 * @param WC_Product $product Product.
	 *
	 * @return bool
	 */
	protected static function is_placed( $product ) {
		global $_wp_current_template_content;

		if ( wp_is_block_theme() && ! empty( $_wp_current_template_content ) && self::blocks_place( parse_blocks( $_wp_current_template_content ), $product->get_id() ) ) {
			return true;
		}

		foreach ( array( $product->get_description(), $product->get_short_description() ) as $content ) {
			if ( has_block( 'csbmw/shipping-calculator', $content ) && self::blocks_place( parse_blocks( $content ), $product->get_id() ) ) {
				return true;
			}

			if ( ! has_shortcode( $content, self::SHORTCODE ) ) {
				continue;
			}

			preg_match_all( '/' . get_shortcode_regex( array( self::SHORTCODE ) ) . '/', $content, $matches, PREG_SET_ORDER );

			foreach ( $matches as $match ) {
				$atts = shortcode_parse_atts( $match[3] );

				if ( self::is_for( is_array( $atts ) && isset( $atts['id'] ) ? $atts['id'] : 0, $product->get_id() ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Whether a calculator's product ID points to a product.
	 *
	 * @param int|string $id         Product or variation ID, empty for the
	 *                               current product.
	 * @param int        $product_id Product ID.
	 *
	 * @return bool
	 */
	protected static function is_for( $id, $product_id ) {
		$id = absint( $id );

		return ! $id || $id === $product_id || wp_get_post_parent_id( $id ) === $product_id;
	}

	/**
	 * Look for the product's calculator block, following template parts.
	 *
	 * Query loops are skipped, as a block there quotes each product listed.
	 *
	 * @param array $blocks     Parsed blocks.
	 * @param int   $product_id Product ID.
	 *
	 * @return bool
	 */
	protected static function blocks_place( $blocks, $product_id ) {
		foreach ( $blocks as $block ) {
			if ( in_array( $block['blockName'], array( 'core/query', 'woocommerce/product-collection' ), true ) ) {
				continue;
			}

			if ( 'csbmw/shipping-calculator' === $block['blockName'] && self::is_for( isset( $block['attrs']['productId'] ) ? $block['attrs']['productId'] : 0, $product_id ) ) {
				return true;
			}

			if ( 'core/template-part' === $block['blockName'] && ! empty( $block['attrs']['slug'] ) ) {
				$theme = isset( $block['attrs']['theme'] ) ? $block['attrs']['theme'] : get_stylesheet();
				$part  = get_block_template( $theme . '//' . $block['attrs']['slug'], 'wp_template_part' );

				if ( $part && self::blocks_place( parse_blocks( $part->content ), $product_id ) ) {
					return true;
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && self::blocks_place( $block['innerBlocks'], $product_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a product has anything to ship.
	 *
	 * A variable product does when one of its variations is not virtual.
	 *
	 * @param WC_Product $product Product.
	 *
	 * @return bool
	 */
	protected static function ships( $product ) {
		if ( ! $product->is_type( 'variable' ) ) {
			return $product->needs_shipping();
		}

		foreach ( $product->get_visible_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );

			if ( $variation && $variation->needs_shipping() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Product shipping calculator markup.
	 *
	 * @param WC_Product $product            Product, or a variation to quote
	 *                                       that one alone.
	 * @param string     $wrapper_attributes Attributes of the wrapper element.
	 * @param bool       $inline             Whether the CEP is changed in the
	 *                                       card instead of a dialog.
	 * @param bool       $automatic          Whether the setting places it,
	 *                                       rather than a block or shortcode.
	 *
	 * @return string
	 */
	public function get_product_calculator( $product, $wrapper_attributes = 'class="csbmw-shipping-calculator"', $inline = false, $automatic = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- The view prints them.
		$variation_id = $product->is_type( 'variation' ) ? $product->get_id() : 0;
		$product_id   = $variation_id ? $product->get_parent_id() : $product->get_id();

		if ( ! self::is_brazil_only() || 'publish' !== get_post_status( $product_id ) || ! self::ships( $product ) || ! $product->is_in_stock() ) {
			return '';
		}

		// One calculator per product and variation. The setting's gives way to
		// any placed for the product, and the script removes it when one comes
		// later on the page.
		if ( $automatic ? isset( $this->placed[ $product_id ] ) || in_array( $product_id, $this->automatic, true ) : isset( $this->placed[ $product_id ][ $variation_id ] ) ) {
			return '';
		}

		if ( $automatic ) {
			$this->automatic[] = $product_id;
		} else {
			$this->placed[ $product_id ][ $variation_id ] = true;
		}

		wp_enqueue_script( self::HANDLE );
		wp_enqueue_style( self::HANDLE );

		$prefix      = wp_unique_id( 'csbmw-shipping-' );
		$postcode    = self::customer_postcode();
		$variable    = $product->is_type( 'variable' );
		$product_url = get_permalink( $product_id );

		ob_start();
		include __DIR__ . '/views/html-product-shipping-calculator.php';

		return (string) ob_get_clean();
	}

	/**
	 * Hide every classic calculator field but the CEP.
	 *
	 * @param bool $enabled Whether the field shows.
	 *
	 * @return bool
	 */
	public function calculator_field_enabled( $enabled ) {
		return self::is_postcode_only() ? false : $enabled;
	}

	/**
	 * Fill the country, state and city the classic calculator no longer asks.
	 *
	 * WC_Shortcode_Cart::calculate_shipping() applies this filter inside a try
	 * block and shows an exception's message as an error notice.
	 *
	 * @param array $address Address posted by the calculator.
	 *
	 * @throws Exception When the CEP is missing or unknown.
	 *
	 * @return array
	 */
	public function calculator_address( $address ) {
		if ( ! self::is_postcode_only() ) {
			return $address;
		}

		if ( empty( $address['postcode'] ) ) {
			throw new Exception( esc_html__( 'Enter your CEP.', 'woocommerce-extra-checkout-fields-for-brazil' ) );
		}

		$found = Extra_Checkout_Fields_For_Brazil_Postcodes::get_address( $address['postcode'] );

		if ( null === $found ) {
			throw new Exception( esc_html__( 'CEP not found. Check the number and try again.', 'woocommerce-extra-checkout-fields-for-brazil' ) );
		}

		$this->calculated = array(
			'found'    => $found,
			'shipping' => WC()->customer->get_shipping_postcode(),
			'billing'  => WC()->customer->get_billing_postcode(),
		);

		$address['country']  = 'BR';
		$address['state']    = $found['state'];
		$address['city']     = $found['city'];
		$address['postcode'] = wc_format_postcode( $found['postcode'], 'BR' );

		return $address;
	}

	/**
	 * Keep the street and neighborhood the classic calculator found.
	 *
	 * WooCommerce saves only the country, state, city and CEP.
	 *
	 * @return void
	 */
	public function remember_calculator_address() {
		if ( null === $this->calculated ) {
			return;
		}

		self::save_customer_address( WC()->customer, $this->calculated['found'], $this->calculated['shipping'], $this->calculated['billing'] );
		$this->calculated = null;
	}

	/**
	 * Quote the cart for the CEP the customer last entered elsewhere, such as
	 * on a product page.
	 *
	 * Each CEP is applied once, so an address changed later in the cart or at
	 * checkout is not overwritten.
	 *
	 * @param WC_Cart $cart Cart.
	 *
	 * @return void
	 */
	public function apply_remembered_postcode( $cart ) {
		$cookie   = Extra_Checkout_Fields_For_Brazil_Privacy::POSTCODE_COOKIE;
		$postcode = isset( $_COOKIE[ $cookie ] ) ? Extra_Checkout_Fields_For_Brazil_Postcodes::sanitize( wp_unslash( $_COOKIE[ $cookie ] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reduced to digits.
		$customer = WC()->customer;

		if ( 8 !== strlen( $postcode ) || null === WC()->session || ! $customer instanceof WC_Customer || ! self::is_brazil_only() || ! $cart->needs_shipping() ) {
			return;
		}

		if ( WC()->session->get( self::APPLIED_POSTCODE ) === $postcode ) {
			return;
		}

		WC()->session->set( self::APPLIED_POSTCODE, $postcode );

		$found = Extra_Checkout_Fields_For_Brazil_Postcodes::get_address( $postcode );

		if ( null === $found ) {
			return;
		}

		self::save_customer_address( $customer, $found, $customer->get_shipping_postcode(), $customer->get_billing_postcode() );
		$customer->set_calculated_shipping( true );
	}

	/**
	 * Save an address found by CEP as the customer's shipping address, and as
	 * the billing address too while the customer has not given a name.
	 *
	 * @param WC_Customer $customer Customer.
	 * @param array       $found    Address found by CEP.
	 * @param string      $shipping CEP the shipping address held before.
	 * @param string      $billing  CEP the billing address held before.
	 *
	 * @return void
	 */
	protected static function save_customer_address( $customer, $found, $shipping, $billing ) {
		self::set_customer_address( $customer, 'shipping', $found, $shipping );

		if ( ! $customer->get_billing_first_name() ) {
			self::set_customer_address( $customer, 'billing', $found, $billing );
		}

		$customer->save();
	}

	/**
	 * Count the quoted product toward the free shipping minimum.
	 *
	 * Free shipping checks its minimum against the cart, which does not hold
	 * the product a product page is quoting. This repeats its check with the
	 * product added, as the order will have it.
	 *
	 * @param bool                      $available Whether free shipping applies.
	 * @param array                     $package   Shipping package.
	 * @param WC_Shipping_Free_Shipping $method    Free shipping method.
	 *
	 * @return bool
	 */
	public function free_shipping_for_estimate( $available, $package, $method ) {
		if ( empty( $package['csbmw_estimate'] ) || ! $method instanceof WC_Shipping_Free_Shipping ) {
			return $available;
		}

		$cart       = WC()->cart;
		$has_coupon = false;

		foreach ( $cart->get_coupons() as $coupon ) {
			if ( $coupon->is_valid() && $coupon->get_free_shipping() ) {
				$has_coupon = true;
				break;
			}
		}

		$total = $cart->get_displayed_subtotal() + $package['csbmw_estimate']['line_total'];

		if ( 'no' === $method->ignore_discounts ) {
			$total -= $cart->get_discount_total();

			if ( $cart->display_prices_including_tax() ) {
				$total -= $cart->get_discount_tax();
			}
		}

		$has_met_min_amount = round( $total, wc_get_price_decimals() ) >= (float) $method->min_amount;

		switch ( $method->requires ) {
			case 'min_amount':
				return $has_met_min_amount;
			case 'coupon':
				return $has_coupon;
			case 'both':
				return $has_met_min_amount && $has_coupon;
			case 'either':
				return $has_met_min_amount || $has_coupon;
			default:
				return true;
		}
	}

	/**
	 * Keep the historic number and neighborhood in a guest's session.
	 *
	 * The classic checkout prefills them from there, and WooCommerce drops
	 * session meta it was not told about.
	 *
	 * @param array $keys Meta keys kept in the session.
	 *
	 * @return array
	 */
	public function session_meta_keys( $keys ) {
		foreach ( array( 'billing', 'shipping' ) as $type ) {
			foreach ( array( 'number', 'neighborhood' ) as $key ) {
				$keys[] = $type . '_' . $key;
			}
		}

		return $keys;
	}

	/**
	 * Mask the classic calculator CEP and link to the Correios search.
	 *
	 * @return void
	 */
	public function enqueue_calculator_script() {
		if ( self::is_postcode_only() ) {
			wp_enqueue_script( self::HANDLE );
			wp_enqueue_style( self::HANDLE );
		}
	}

	/**
	 * Add the CEP calculator to the cart block, which has none of its own.
	 *
	 * @return void
	 */
	public function enqueue_cart_block_calculator() {
		if ( ! self::is_postcode_only() || 'yes' !== get_option( 'woocommerce_enable_shipping_calc' ) ) {
			return;
		}

		if ( ! has_block( 'woocommerce/cart' ) && ! ( is_cart() && wp_is_block_theme() ) ) {
			return;
		}

		Extra_Checkout_Fields_For_Brazil_Assets::register_script( self::HANDLE . '-cart', 'shipping-cart', array( self::HANDLE ) );
		Extra_Checkout_Fields_For_Brazil_Assets::set_translations( self::HANDLE . '-cart' );

		wp_enqueue_script( self::HANDLE . '-cart' );
		wp_enqueue_style( self::HANDLE );
	}

	/**
	 * WC AJAX endpoint listing the shipping rates for a product and CEP.
	 *
	 * @return void
	 */
	public function ajax_estimate() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$product_id   = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
		$variation_id = isset( $_GET['variation_id'] ) ? absint( $_GET['variation_id'] ) : 0;
		$quantity     = isset( $_GET['quantity'] ) ? (int) $_GET['quantity'] : 1;
		$postcode     = isset( $_GET['postcode'] ) ? sanitize_text_field( wp_unslash( $_GET['postcode'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$product = wc_get_product( $variation_id ? $variation_id : $product_id );

		// Errors are answers the customer reads, so none of them carries an
		// error status for the browser to log.
		if ( ! $product || ! self::is_brazil_only() || 'publish' !== get_post_status( $product_id ) || ( $variation_id && $product->get_parent_id() !== $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not calculate shipping. Try again.', 'woocommerce-extra-checkout-fields-for-brazil' ) ) );
		}

		if ( ! $product->needs_shipping() || ! $product->is_purchasable() || ! $product->is_in_stock() || $quantity < 1 ) {
			wp_send_json_error( array( 'message' => __( 'Could not calculate shipping. Try again.', 'woocommerce-extra-checkout-fields-for-brazil' ) ) );
		}

		if ( 8 !== strlen( Extra_Checkout_Fields_For_Brazil_Postcodes::sanitize( $postcode ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Enter a valid CEP.', 'woocommerce-extra-checkout-fields-for-brazil' ) ) );
		}

		$address = Extra_Checkout_Fields_For_Brazil_Postcodes::get_address( $postcode );

		if ( null === $address ) {
			wp_send_json_error( array( 'message' => __( 'CEP not found. Check the number and try again.', 'woocommerce-extra-checkout-fields-for-brazil' ) ) );
		}

		// Before the options are chosen there is nothing to quote, but the
		// customer still sees where the CEP points to.
		wp_send_json_success(
			array(
				'address' => $address,
				'rates'   => $product->is_type( 'variable' ) ? null : $this->get_cached_rates( $product, $quantity, $address ),
			)
		);
	}

	/**
	 * Shipping rates for a product, reused while nothing they depend on changes.
	 *
	 * Product pages quote on every visit once the CEP is known, and a carrier
	 * such as Correios is asked over the network each time.
	 *
	 * @param WC_Product $product  Product or variation.
	 * @param int        $quantity Quantity.
	 * @param array      $address  Address from the CEP.
	 *
	 * @return array
	 */
	public function get_cached_rates( $product, $quantity, $address ) {
		if ( null === WC()->session ) {
			wc_load_cart();
		}

		/**
		 * Filter how long a product page quote is reused, in seconds.
		 *
		 * @since 5.0.0
		 *
		 * @param int        $ttl     Seconds, 0 to calculate every time.
		 * @param WC_Product $product Product or variation.
		 */
		$ttl = (int) apply_filters( 'csbmw_shipping_estimate_cache_ttl', HOUR_IN_SECONDS, $product );

		if ( $ttl <= 0 ) {
			return $this->get_rates( $product, $quantity, $address );
		}

		$cart     = WC()->cart;
		$modified = $product->get_date_modified();
		$key      = 'csbmw_rates_' . md5(
			wp_json_encode(
				array(
					$product->get_id(),
					$modified ? $modified->getTimestamp() : 0,
					$quantity,
					$address['postcode'],
					get_current_user_id(),
					$cart->get_displayed_subtotal(),
					$cart->get_discount_total(),
					$cart->get_applied_coupons(),
					$cart->display_prices_including_tax(),
					get_woocommerce_currency(),
					determine_locale(),
					// Changes whenever a shipping zone or method is saved.
					WC_Cache_Helper::get_transient_version( 'shipping' ),
				)
			)
		);

		$rates = get_transient( $key );

		if ( ! is_array( $rates ) ) {
			$rates = $this->get_rates( $product, $quantity, $address );
			set_transient( $key, $rates, $ttl );
		}

		return $rates;
	}

	/**
	 * Shipping rates for a product on its own.
	 *
	 * @param WC_Product $product  Product or variation.
	 * @param int        $quantity Quantity.
	 * @param array      $address  Address from the CEP.
	 *
	 * @return array
	 */
	public function get_rates( $product, $quantity, $address ) {
		// WC_Shipping keeps the rates it calculates in the session.
		if ( null === WC()->session ) {
			wc_load_cart();
		}

		$total   = (float) wc_get_price_excluding_tax( $product, array( 'qty' => $quantity ) );
		$package = array(
			'contents'        => array(
				'csbmw_estimate' => array(
					'key'               => 'csbmw_estimate',
					'product_id'        => $product->get_parent_id() ? $product->get_parent_id() : $product->get_id(),
					'variation_id'      => $product->is_type( 'variation' ) ? $product->get_id() : 0,
					'variation'         => array(),
					'quantity'          => $quantity,
					'data'              => $product,
					'line_tax_data'     => array(
						'subtotal' => array(),
						'total'    => array(),
					),
					'line_subtotal'     => $total,
					'line_subtotal_tax' => 0,
					'line_total'        => $total,
					'line_tax'          => 0,
				),
			),
			'contents_cost'   => $total,
			'applied_coupons' => array(),
			'user'            => array( 'ID' => get_current_user_id() ),
			'destination'     => array(
				'country'   => 'BR',
				'state'     => $address['state'],
				'postcode'  => wc_format_postcode( $address['postcode'], 'BR' ),
				'city'      => $address['city'],
				'address'   => $address['address'],
				'address_1' => $address['address'],
				'address_2' => '',
			),
			'cart_subtotal'   => $total,
			// Also changes the package hash, so WooCommerce recalculates the
			// rates it keeps in the session when the cart changes.
			'csbmw_estimate'  => array(
				'line_total'    => WC()->cart->display_prices_including_tax() ? (float) wc_get_price_including_tax( $product, array( 'qty' => $quantity ) ) : $total,
				'cart_subtotal' => (float) WC()->cart->get_displayed_subtotal(),
				'cart_discount' => (float) WC()->cart->get_discount_total(),
			),
		);

		/**
		 * Filter the package a product page shipping estimate is calculated for.
		 *
		 * @since 5.0.0
		 *
		 * @param array      $package  Shipping package.
		 * @param WC_Product $product  Product or variation.
		 * @param int        $quantity Quantity.
		 */
		$package = apply_filters( 'csbmw_shipping_estimate_package', $package, $product, $quantity );

		// A key of its own keeps the cart's stored rates untouched.
		$package = WC()->shipping()->calculate_shipping_for_package( $package, 'csbmw_estimate' );

		if ( empty( $package['rates'] ) ) {
			return array();
		}

		$including_tax = 'incl' === get_option( 'woocommerce_tax_display_cart' );
		$rates         = array();

		foreach ( $package['rates'] as $rate ) {
			$cost = (float) $rate->get_cost();

			if ( $including_tax ) {
				$cost += array_sum( $rate->get_taxes() );
			}

			$meta = $rate->get_meta_data();
			$days = isset( $meta['_delivery_forecast'] ) ? absint( $meta['_delivery_forecast'] ) : 0;

			$rates[] = array(
				'id'       => $rate->get_id(),
				'label'    => wp_strip_all_tags( $rate->get_label() ),
				'cost'     => $cost > 0 ? html_entity_decode( wp_strip_all_tags( wc_price( $cost ) ), ENT_QUOTES, 'UTF-8' ) : __( 'Free', 'woocommerce-extra-checkout-fields-for-brazil' ),
				/* translators: %d: number of working days */
				'delivery' => $days ? sprintf( _n( 'Delivery within %d working day', 'Delivery within %d working days', $days, 'woocommerce-extra-checkout-fields-for-brazil' ), $days ) : '',
			);
		}

		return $rates;
	}
}

new Extra_Checkout_Fields_For_Brazil_Shipping();
