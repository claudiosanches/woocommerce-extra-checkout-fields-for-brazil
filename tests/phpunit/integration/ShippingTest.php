<?php
/**
 * Shipping calculators that ask only for the CEP.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

/**
 * Shipping test.
 */
class ShippingTest extends WP_UnitTestCase {

	/**
	 * Class under test.
	 *
	 * @var Extra_Checkout_Fields_For_Brazil_Shipping
	 */
	protected $shipping;

	public function set_up() {
		parent::set_up();

		$this->shipping = new Extra_Checkout_Fields_For_Brazil_Shipping();

		update_option( 'wcbcf_settings', array( 'postcode_only_calculator' => '1' ) );
		$this->ship_only_to( array( 'BR' ) );

		Extra_Checkout_Fields_For_Brazil_Postcodes::maybe_install();

		global $wpdb;
		$wpdb->query( 'DELETE FROM ' . Extra_Checkout_Fields_For_Brazil_Postcodes::table() ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			Extra_Checkout_Fields_For_Brazil_Postcodes::table(),
			array(
				'postcode'     => '20040020',
				'address'      => 'Praça Pio X',
				'neighborhood' => 'Centro',
				'city'         => 'Rio de Janeiro',
				'state'        => 'RJ',
			)
		);

		// Nothing outside the table is reachable.
		add_filter( 'pre_http_request', static fn() => new WP_Error( 'http_request_failed', 'Offline' ) );
	}

	/**
	 * Restrict the store to the given countries.
	 *
	 * @param string[] $countries Country codes.
	 */
	protected function ship_only_to( array $countries ) {
		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', $countries );
		update_option( 'woocommerce_ship_to_countries', '' );
	}

	/**
	 * A published simple product.
	 *
	 * @param array $props Product props.
	 *
	 * @return WC_Product_Simple
	 */
	protected function simple_product( $props = array() ) {
		$product = new WC_Product_Simple();
		$product->set_props( array_merge( array( 'regular_price' => '10' ), $props ) );
		$product->save();

		return $product;
	}

	/**
	 * A variable product with a Fisico and a Digital variation.
	 *
	 * @param bool $virtual Whether both variations are virtual.
	 *
	 * @return array The product and its variations by option.
	 */
	protected function variable_product( $virtual = false ) {
		$product = new WC_Product_Variable();

		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Tipo' );
		$attribute->set_options( array( 'Fisico', 'Digital' ) );
		$attribute->set_visible( true );
		$attribute->set_variation( true );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$variations = array();

		foreach ( array( 'Fisico', 'Digital' ) as $option ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $product->get_id() );
			$variation->set_attributes( array( 'tipo' => $option ) );
			$variation->set_regular_price( '10' );
			$variation->set_virtual( $virtual );
			$variation->save();

			$variations[ $option ] = $variation;
		}

		WC_Product_Variable::sync( $product->get_id() );

		return array( wc_get_product( $product->get_id() ), $variations );
	}

	/**
	 * What the setting prints on a product page hook.
	 *
	 * @param string     $hook    Hook.
	 * @param WC_Product $product Product the page shows.
	 *
	 * @return string
	 */
	protected function print_on( $hook, $product ) {
		global $wp_current_filter;

		$GLOBALS['product']  = $product;
		$wp_current_filter[] = $hook;

		ob_start();
		$this->shipping->classic_product_calculator();
		$output = (string) ob_get_clean();

		array_pop( $wp_current_filter );

		return $output;
	}

	/**
	 * Set where the setting places the calculator.
	 *
	 * @param string $placement Key of PLACEMENTS, empty for none.
	 */
	protected function place_on_product_pages( $placement ) {
		update_option(
			'wcbcf_settings',
			array(
				'postcode_only_calculator'    => '1',
				'product_shipping_calculator' => $placement,
			)
		);
	}

	/**
	 * The calculators stay off unless Brazil is the only destination.
	 */
	public function test_postcode_only_needs_brazil_as_the_only_destination() {
		$this->assertTrue( Extra_Checkout_Fields_For_Brazil_Shipping::is_postcode_only() );

		$this->ship_only_to( array( 'BR', 'PT' ) );

		$this->assertFalse( Extra_Checkout_Fields_For_Brazil_Shipping::is_postcode_only() );
		$this->assertTrue( $this->shipping->calculator_field_enabled( true ) );
	}

	/**
	 * The classic calculator hides every field but the CEP.
	 */
	public function test_classic_calculator_hides_all_but_the_postcode() {
		$this->assertFalse( $this->shipping->calculator_field_enabled( true ) );
	}

	/**
	 * The fields the calculator no longer asks come from the CEP.
	 */
	public function test_calculator_address_is_filled_from_the_postcode() {
		$address = $this->shipping->calculator_address(
			array(
				'country'  => '',
				'state'    => '',
				'postcode' => '20040020',
				'city'     => '',
			)
		);

		$this->assertSame(
			array(
				'country'  => 'BR',
				'state'    => 'RJ',
				'postcode' => '20040-020',
				'city'     => 'Rio de Janeiro',
			),
			$address
		);
	}

	/**
	 * An unknown CEP is reported rather than calculated for.
	 */
	public function test_calculator_rejects_an_unknown_postcode() {
		add_filter(
			'csbmw_postcode_services',
			static fn() => array( 'unknown' => '__return_false' )
		);

		$this->expectException( Exception::class );

		$this->shipping->calculator_address(
			array(
				'country'  => '',
				'state'    => '',
				'postcode' => '01001000',
				'city'     => '',
			)
		);
	}

	/**
	 * An incomplete CEP is reported before anything is looked up.
	 */
	public function test_calculator_rejects_an_incomplete_postcode() {
		$this->expectExceptionMessage( 'A CEP has 8 digits.' );

		$this->shipping->calculator_address(
			array(
				'country'  => '',
				'state'    => '',
				'postcode' => '2004002',
				'city'     => '',
			)
		);
	}

	/**
	 * While no lookup service answers, the calculator quotes the CEP's state
	 * and says so.
	 */
	public function test_calculator_quotes_the_state_while_the_lookup_is_down() {
		if ( null === WC()->session ) {
			wc_load_cart();
		}

		wc_clear_notices();

		$address = $this->shipping->calculator_address(
			array(
				'country'  => '',
				'state'    => '',
				'postcode' => '30130010',
				'city'     => '',
			)
		);

		$this->assertSame(
			array(
				'country'  => 'BR',
				'state'    => 'MG',
				'postcode' => '30130-010',
				'city'     => '',
			),
			$address
		);
		$this->assertStringContainsString( 'quoted for Minas Gerais', wc_get_notices( 'notice' )[0]['notice'] );

		// The same CEP again keeps the city the customer has.
		WC()->customer->set_shipping_postcode( '30130-010' );
		WC()->customer->set_shipping_city( 'Belo Horizonte' );

		$address = $this->shipping->calculator_address(
			array(
				'country'  => '',
				'state'    => '',
				'postcode' => '30130010',
				'city'     => '',
			)
		);

		$this->assertSame( 'Belo Horizonte', $address['city'] );
		wc_clear_notices();
	}

	/**
	 * The CEP requirement needs the cart calculator, and holds back only a
	 * cart that ships without a CEP.
	 */
	public function test_checkout_needs_a_cart_postcode_when_required() {
		if ( null === WC()->session ) {
			wc_load_cart();
		}

		update_option( 'woocommerce_enable_shipping_calc', 'yes' );

		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Brasil' );
		$zone->add_location( 'BR', 'country' );
		$zone->save();
		$zone->add_shipping_method( 'flat_rate' );

		$shipped = new WC_Product_Simple();
		$shipped->set_regular_price( '10' );
		$shipped->save();

		$virtual = new WC_Product_Simple();
		$virtual->set_regular_price( '10' );
		$virtual->set_virtual( true );
		$virtual->save();

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $shipped->get_id() );
		WC()->customer->set_shipping_country( 'BR' );
		WC()->customer->set_shipping_postcode( '' );

		$this->assertFalse( Extra_Checkout_Fields_For_Brazil_Shipping::needs_cart_postcode() );

		update_option(
			'wcbcf_settings',
			array(
				'postcode_only_calculator' => '1',
				'require_cart_postcode'    => '1',
			)
		);

		$this->assertTrue( Extra_Checkout_Fields_For_Brazil_Shipping::needs_cart_postcode() );

		update_option( 'woocommerce_enable_shipping_calc', 'no' );
		$this->assertFalse( Extra_Checkout_Fields_For_Brazil_Shipping::needs_cart_postcode() );
		update_option( 'woocommerce_enable_shipping_calc', 'yes' );

		// Only the format counts, whatever the lookup says.
		WC()->customer->set_shipping_postcode( '0000000' );
		$this->assertTrue( Extra_Checkout_Fields_For_Brazil_Shipping::needs_cart_postcode() );
		WC()->customer->set_shipping_postcode( '00000-000' );
		$this->assertFalse( Extra_Checkout_Fields_For_Brazil_Shipping::needs_cart_postcode() );

		WC()->customer->set_shipping_postcode( '' );
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $virtual->get_id() );
		$this->assertFalse( Extra_Checkout_Fields_For_Brazil_Shipping::needs_cart_postcode() );

		WC()->cart->empty_cart();
	}

	/**
	 * The checkout is not prefetched while it may send the customer back.
	 */
	public function test_checkout_is_not_prefetched_while_a_postcode_is_required() {
		$checkout = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_name'   => 'finalizar',
				'post_status' => 'publish',
			)
		);
		update_option( 'woocommerce_checkout_page_id', $checkout );
		update_option( 'woocommerce_enable_shipping_calc', 'yes' );
		$this->set_permalink_structure( '/%postname%/' );

		$this->assertSame( array(), $this->shipping->exclude_checkout_from_prefetch( array() ) );

		update_option(
			'wcbcf_settings',
			array(
				'postcode_only_calculator' => '1',
				'require_cart_postcode'    => '1',
			)
		);

		$this->assertSame( array( '/finalizar', '/finalizar/*' ), $this->shipping->exclude_checkout_from_prefetch( array() ) );

		$this->set_permalink_structure( '/%postname%' );
		$this->assertSame( array( '/finalizar', '/finalizar/*' ), $this->shipping->exclude_checkout_from_prefetch( array() ) );

		// A link with a query is never prefetched.
		$this->set_permalink_structure( '' );
		$this->assertSame( array(), $this->shipping->exclude_checkout_from_prefetch( array() ) );
	}

	/**
	 * A product is quoted on its own, for its quantity.
	 */
	public function test_product_rates_cover_the_quantity() {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Brasil' );
		$zone->add_location( 'BR', 'country' );
		$zone->save();

		$instance = $zone->add_shipping_method( 'flat_rate' );
		update_option(
			'woocommerce_flat_rate_' . $instance . '_settings',
			array(
				'title'      => 'Flat rate',
				'cost'       => '5 * [qty]',
				'tax_status' => 'none',
			)
		);
		WC_Cache_Helper::get_transient_version( 'shipping', true );

		$product = new WC_Product_Simple();
		$product->set_regular_price( '10' );
		$product->save();

		$address = Extra_Checkout_Fields_For_Brazil_Postcodes::get_address( '20040020' );
		$rates   = $this->shipping->get_rates( $product, 3, $address );

		$this->assertCount( 1, $rates );
		$this->assertSame( 'Flat rate', $rates[0]['label'] );
		$this->assertStringContainsString( '15', $rates[0]['cost'] );
	}

	/**
	 * A new CEP moves the whole address, number and complement included.
	 */
	public function test_new_postcode_replaces_the_street() {
		$customer = new WC_Customer();
		$customer->set_shipping_address_1( 'Rua das Flores' );
		$customer->set_shipping_address_2( 'Apto 51' );
		$customer->update_meta_data( 'shipping_number', '901' );
		$customer->update_meta_data( 'shipping_neighborhood', 'Bela Vista' );

		$found = Extra_Checkout_Fields_For_Brazil_Postcodes::get_address( '20040020' );

		Extra_Checkout_Fields_For_Brazil_Shipping::set_customer_address( $customer, 'shipping', $found, '01310-100' );

		$this->assertSame( 'Praça Pio X', $customer->get_shipping_address_1() );
		$this->assertSame( '', $customer->get_shipping_address_2() );
		$this->assertSame( '', $customer->get_meta( 'shipping_number' ) );
		$this->assertSame( 'Centro', $customer->get_meta( 'shipping_neighborhood' ) );
		$this->assertSame( 'Centro', $customer->get_meta( '_wc_shipping/csbmw/neighborhood' ) );
		$this->assertSame( 'Rio de Janeiro', $customer->get_shipping_city() );
		$this->assertSame( '20040-020', $customer->get_shipping_postcode() );
	}

	/**
	 * The same CEP only fills what is still empty.
	 */
	public function test_same_postcode_keeps_the_street() {
		$customer = new WC_Customer();
		$customer->set_shipping_address_1( 'Rua Particular' );
		$customer->update_meta_data( 'shipping_number', '12' );

		$found = Extra_Checkout_Fields_For_Brazil_Postcodes::get_address( '20040020' );

		Extra_Checkout_Fields_For_Brazil_Shipping::set_customer_address( $customer, 'shipping', $found, '20040-020' );

		$this->assertSame( 'Rua Particular', $customer->get_shipping_address_1() );
		$this->assertSame( '12', $customer->get_meta( 'shipping_number' ) );
		$this->assertSame( 'Centro', $customer->get_meta( 'shipping_neighborhood' ) );
	}

	/**
	 * The CEP entered on a product page reaches the cart once.
	 */
	public function test_cart_takes_the_remembered_postcode_once() {
		if ( null === WC()->session ) {
			wc_load_cart();
		}

		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Brasil' );
		$zone->add_location( 'BR', 'country' );
		$zone->save();
		$zone->add_shipping_method( 'flat_rate' );

		$product = new WC_Product_Simple();
		$product->set_regular_price( '10' );
		$product->save();

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->session->set( Extra_Checkout_Fields_For_Brazil_Shipping::APPLIED_POSTCODE, null );

		$_COOKIE[ Extra_Checkout_Fields_For_Brazil_Privacy::POSTCODE_COOKIE ] = '20040020';

		$this->shipping->apply_remembered_postcode( WC()->cart );

		$this->assertSame( '20040-020', WC()->customer->get_shipping_postcode() );
		$this->assertSame( 'Praça Pio X', WC()->customer->get_shipping_address_1() );

		// A CEP typed at checkout afterwards stays.
		WC()->customer->set_shipping_postcode( '01001-000' );
		$this->shipping->apply_remembered_postcode( WC()->cart );

		$this->assertSame( '01001-000', WC()->customer->get_shipping_postcode() );

		unset( $_COOKIE[ Extra_Checkout_Fields_For_Brazil_Privacy::POSTCODE_COOKIE ] );
		WC()->cart->empty_cart();
	}

	/**
	 * A guest's session keeps the number and neighborhood the classic
	 * checkout reads.
	 */
	public function test_session_keeps_the_historic_address_meta() {
		$keys = apply_filters( 'woocommerce_customer_allowed_session_meta_keys', array(), new WC_Customer() );

		$this->assertContains( 'shipping_neighborhood', $keys );
		$this->assertContains( 'billing_number', $keys );
	}

	/**
	 * A product that cannot be bought gets no calculator.
	 */
	public function test_out_of_stock_product_has_no_calculator() {
		$product = new WC_Product_Simple();
		$product->set_regular_price( '10' );
		$product->set_stock_status( 'outofstock' );
		$product->save();

		$this->assertSame( '', $this->shipping->get_product_calculator( $product ) );

		$product->set_stock_status( 'instock' );
		$product->save();

		$this->assertStringContainsString( 'csbmw-shipping-calculator', $this->shipping->get_product_calculator( $product ) );
	}

	/**
	 * A variable product gets the calculator only when a variation ships.
	 */
	public function test_variable_product_needs_a_variation_that_ships() {
		list( $product, $variations ) = $this->variable_product( true );

		$this->assertSame( '', $this->shipping->get_product_calculator( $product ) );

		$variations['Fisico']->set_virtual( false );
		$variations['Fisico']->save();

		$this->assertStringContainsString( 'csbmw-shipping-calculator', ( new Extra_Checkout_Fields_For_Brazil_Shipping() )->get_product_calculator( $product ) );
	}

	/**
	 * Free shipping counts the quoted quantity toward its minimum.
	 */
	public function test_free_shipping_minimum_counts_the_quoted_product() {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Brasil' );
		$zone->add_location( 'BR', 'country' );
		$zone->save();

		$instance = $zone->add_shipping_method( 'free_shipping' );
		update_option(
			'woocommerce_free_shipping_' . $instance . '_settings',
			array(
				'title'            => 'Frete grátis',
				'requires'         => 'min_amount',
				'min_amount'       => '100',
				'ignore_discounts' => 'no',
			)
		);
		WC_Cache_Helper::get_transient_version( 'shipping', true );

		$product = new WC_Product_Simple();
		$product->set_regular_price( '40' );
		$product->save();

		$address = Extra_Checkout_Fields_For_Brazil_Postcodes::get_address( '20040020' );
		$labels  = static fn( $rates ) => wp_list_pluck( $rates, 'label' );

		$this->assertNotContains( 'Frete grátis', $labels( $this->shipping->get_rates( $product, 2, $address ) ) );
		$this->assertContains( 'Frete grátis', $labels( $this->shipping->get_rates( $product, 3, $address ) ) );
	}

	/**
	 * A quote is reused until a shipping zone or method changes.
	 */
	public function test_product_quote_is_reused() {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Brasil' );
		$zone->add_location( 'BR', 'country' );
		$zone->save();

		$instance = $zone->add_shipping_method( 'flat_rate' );
		$option   = 'woocommerce_flat_rate_' . $instance . '_settings';

		update_option(
			$option,
			array(
				'title' => 'Flat rate',
				'cost'  => '10',
			)
		);
		WC_Cache_Helper::get_transient_version( 'shipping', true );

		$product = new WC_Product_Simple();
		$product->set_regular_price( '10' );
		$product->save();

		$address = Extra_Checkout_Fields_For_Brazil_Postcodes::get_address( '20040020' );
		$first   = $this->shipping->get_cached_rates( $product, 1, $address );

		// Saving the method through WooCommerce bumps the shipping version;
		// writing the option directly does not, so the quote is reused.
		update_option(
			$option,
			array(
				'title' => 'Flat rate',
				'cost'  => '99',
			)
		);

		$this->assertSame( $first, $this->shipping->get_cached_rates( $product, 1, $address ) );

		WC_Cache_Helper::get_transient_version( 'shipping', true );

		$this->assertStringContainsString( '99', $this->shipping->get_cached_rates( $product, 1, $address )[0]['cost'] );
	}

	/**
	 * The plugin tells the WP Consent API it asks before remembering the CEP.
	 */
	public function test_registers_with_the_consent_api() {
		$this->assertTrue( apply_filters( 'wp_consent_api_registered_' . plugin_basename( CSBMW_PLUGIN_FILE ), false ) );
	}

	/**
	 * A block placed in the product template takes the classic hook's place.
	 */
	public function test_placed_block_replaces_the_classic_hook() {
		global $product, $_wp_current_template_content;

		if ( ! wp_get_theme( 'twentytwentyfive' )->exists() ) {
			$this->markTestSkipped( 'Needs the Twenty Twenty-Five block theme.' );
		}

		switch_theme( 'twentytwentyfive' );

		$this->place_on_product_pages( 'after_add_to_cart' );

		$product = $this->simple_product();
		$other   = $this->simple_product();
		$hook    = 'woocommerce_after_add_to_cart_form';

		$_wp_current_template_content = '<!-- wp:woocommerce/add-to-cart-form /--><!-- wp:group --><div class="wp-block-group"><!-- wp:csbmw/shipping-calculator /--></div><!-- /wp:group -->';
		$this->assertSame( '', $this->print_on( $hook, $product ) );

		$_wp_current_template_content = '<!-- wp:csbmw/shipping-calculator {"productId":' . $product->get_id() . '} /-->';
		$this->assertSame( '', $this->print_on( $hook, $product ) );

		// One for another product, or quoting each product of a list, leaves
		// the product page's own.
		$_wp_current_template_content = '<!-- wp:csbmw/shipping-calculator {"productId":' . $other->get_id() . '} /--><!-- wp:woocommerce/product-collection --><!-- wp:csbmw/shipping-calculator /--><!-- /wp:woocommerce/product-collection -->';
		$this->assertStringContainsString( 'data-automatic="1"', $this->print_on( $hook, $product ) );

		$_wp_current_template_content = null;
	}

	/**
	 * The setting prints the calculator on the hook it names, and only there.
	 */
	public function test_setting_places_the_calculator_on_its_hook() {
		foreach ( Extra_Checkout_Fields_For_Brazil_Shipping::PLACEMENTS as $placement ) {
			$this->assertSame( $placement[1], has_action( $placement[0], array( $this->shipping, 'classic_product_calculator' ) ) );
		}

		$product = $this->simple_product();

		$this->place_on_product_pages( '' );
		$this->assertSame( '', $this->print_on( 'woocommerce_after_add_to_cart_form', $product ) );

		$this->place_on_product_pages( 'after_price' );
		$this->assertSame( '', $this->print_on( 'woocommerce_after_add_to_cart_form', $product ) );
		$this->assertStringContainsString( 'data-automatic="1"', $this->print_on( 'woocommerce_single_product_summary', $product ) );

		// Once per page.
		$this->assertSame( '', $this->print_on( 'woocommerce_single_product_summary', $product ) );
	}

	/**
	 * A shortcode or block in the product's descriptions takes the setting's
	 * place.
	 */
	public function test_description_placing_the_calculator_replaces_the_setting() {
		$this->place_on_product_pages( 'after_add_to_cart' );

		$other = $this->simple_product();
		$hook  = 'woocommerce_after_add_to_cart_form';
		$cases = array(
			'shortcode'                 => array( 'description' => '[csbmw_shipping_calculator]' ),
			'block in short'            => array( 'short_description' => '<!-- wp:csbmw/shipping-calculator /-->' ),
			'shortcode for the product' => array( 'description' => '[csbmw_shipping_calculator id="%d"]' ),
		);

		foreach ( $cases as $name => $props ) {
			$product = $this->simple_product();
			$key     = key( $props );

			$product->set_props( array( $key => sprintf( $props[ $key ], $product->get_id() ) ) );
			$product->save();

			$this->assertSame( '', $this->print_on( $hook, $product ), $name );
		}

		$product = $this->simple_product( array( 'description' => '[csbmw_shipping_calculator id="' . $other->get_id() . '"]' ) );

		$this->assertStringContainsString( 'data-automatic="1"', $this->print_on( $hook, $product ) );
	}

	/**
	 * A calculator placed earlier on the page takes the setting's place, and
	 * one placed later is still printed, for the script to keep.
	 */
	public function test_placed_calculator_wins_over_the_setting() {
		$this->place_on_product_pages( 'after_add_to_cart' );

		$product = $this->simple_product();

		$this->assertStringNotContainsString( 'data-automatic', $this->shipping->shortcode( array( 'id' => $product->get_id() ) ) );
		$this->assertSame( '', $this->print_on( 'woocommerce_after_add_to_cart_form', $product ) );

		$later = $this->simple_product();

		$this->assertStringContainsString( 'data-automatic="1"', $this->print_on( 'woocommerce_after_add_to_cart_form', $later ) );
		$this->assertStringContainsString( 'data-product-id="' . $later->get_id() . '"', $this->shipping->shortcode( array( 'id' => $later->get_id() ) ) );
	}

	/**
	 * The shortcode quotes a product, or one of its variations alone.
	 */
	public function test_shortcode_quotes_a_product_or_a_variation() {
		list( $product, $variations ) = $this->variable_product();

		$this->assertSame( '', $this->shipping->shortcode( array( 'id' => 999999 ) ) );

		$output = $this->shipping->shortcode( array( 'id' => $product->get_id() ) );

		$this->assertStringContainsString( 'data-product-id="' . $product->get_id() . '" data-variation-id="0" data-variable="1"', $output );
		$this->assertStringContainsString( 'data-product-url="' . get_permalink( $product->get_id() ) . '"', $output );
		$this->assertStringContainsString( '<dialog', $output );

		$fisico = $this->shipping->shortcode(
			array(
				'id'                 => $variations['Fisico']->get_id(),
				'change_postcode_in' => 'block',
			)
		);

		$this->assertStringContainsString( 'data-product-id="' . $product->get_id() . '" data-variation-id="' . $variations['Fisico']->get_id() . '" data-variable="0"', $fisico );
		$this->assertStringContainsString( 'data-change-postcode-in="block"', $fisico );
		$this->assertStringNotContainsString( '<dialog', $fisico );

		// One each.
		$this->assertSame( '', $this->shipping->shortcode( array( 'id' => $product->get_id() ) ) );
		$this->assertSame( '', $this->shipping->shortcode( array( 'id' => $variations['Fisico']->get_id() ) ) );
		$this->assertStringContainsString( 'csbmw-shipping-calculator', $this->shipping->shortcode( array( 'id' => $variations['Digital']->get_id() ) ) );
	}

	/**
	 * A virtual variation or an unpublished product has nothing to quote.
	 */
	public function test_nothing_to_quote_prints_nothing() {
		list( $product, $variations ) = $this->variable_product();

		$variations['Digital']->set_virtual( true );
		$variations['Digital']->save();

		$this->assertSame( '', $this->shipping->shortcode( array( 'id' => $variations['Digital']->get_id() ) ) );
		$this->assertSame( '', $this->shipping->shortcode( array( 'id' => $this->simple_product( array( 'status' => 'draft' ) )->get_id() ) ) );
	}

	/**
	 * The block quotes the product chosen in it, or the one being shown.
	 */
	public function test_block_quotes_the_chosen_product() {
		list( $product, $variations ) = $this->variable_product();

		$other  = $this->simple_product();
		$render = static fn( $attrs ) => render_block(
			array(
				'blockName'    => 'csbmw/shipping-calculator',
				'attrs'        => $attrs,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);

		$this->assertStringContainsString(
			'data-variation-id="' . $variations['Fisico']->get_id() . '"',
			$render(
				array(
					'productId'   => $product->get_id(),
					'variationId' => $variations['Fisico']->get_id(),
				)
			)
		);

		// A variation of another product.
		$this->assertSame(
			'',
			$render(
				array(
					'productId'   => $other->get_id(),
					'variationId' => $variations['Digital']->get_id(),
				)
			)
		);

		$GLOBALS['post'] = get_post( $other->get_id() );
		setup_postdata( $GLOBALS['post'] );

		$this->assertStringContainsString( 'data-product-id="' . $other->get_id() . '"', $render( array() ) );

		wp_reset_postdata();
	}
}
