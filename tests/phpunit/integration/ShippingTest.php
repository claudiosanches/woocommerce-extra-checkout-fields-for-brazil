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
		$product = new WC_Product_Variable();
		$product->set_regular_price( '10' );

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
			$variation->set_virtual( true );
			$variation->save();

			$variations[ $option ] = $variation;
		}

		WC_Product_Variable::sync( $product->get_id() );
		$product = wc_get_product( $product->get_id() );

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

		update_option(
			'wcbcf_settings',
			array(
				'postcode_only_calculator'    => '1',
				'product_shipping_calculator' => '1',
			)
		);

		$product = new WC_Product_Simple();
		$product->set_regular_price( '10' );
		$product->save();

		$_wp_current_template_content = '<!-- wp:woocommerce/add-to-cart-form /--><!-- wp:group --><div class="wp-block-group"><!-- wp:csbmw/shipping-calculator /--></div><!-- /wp:group -->';

		ob_start();
		$this->shipping->classic_product_calculator();
		$this->assertSame( '', ob_get_clean() );

		$_wp_current_template_content = '<!-- wp:woocommerce/add-to-cart-form /-->';

		ob_start();
		$this->shipping->classic_product_calculator();
		$this->assertStringContainsString( 'csbmw-shipping-calculator', ob_get_clean() );

		$_wp_current_template_content = null;
	}
}
