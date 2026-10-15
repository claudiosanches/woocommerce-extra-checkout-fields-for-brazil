<?php
/**
 * Tests for the Brazilian address format.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

/**
 * Covers localisation_address_formats and what it renders.
 */
class AddressFormatTest extends WP_UnitTestCase {

	/**
	 * The format the plugin registers for Brazil.
	 *
	 * @return string
	 */
	protected function brazilian_format() {
		$formats = WC()->countries->get_address_formats();

		return $formats['BR'];
	}

	public function test_the_format_carries_the_fields_woocommerce_has_no_token_for() {
		$format = $this->brazilian_format();

		$this->assertStringContainsString( '{number}', $format );
		$this->assertStringContainsString( '{neighborhood}', $format );
	}

	/**
	 * The format replaces WooCommerce's default, which carries the company, so
	 * leaving it out hid the company name of every legal person order.
	 *
	 * @return void
	 */
	public function test_the_format_keeps_the_company() {
		$this->assertStringContainsString( '{company}', $this->brazilian_format() );
	}

	public function test_an_address_renders_the_company_the_number_and_the_neighborhood() {
		$order = new WC_Order();
		$order->set_billing_first_name( 'Ana' );
		$order->set_billing_last_name( 'Silva' );
		$order->set_billing_company( 'Empresa Ltda' );
		$order->set_billing_address_1( 'Avenida Paulista' );
		$order->set_billing_city( 'Sao Paulo' );
		$order->set_billing_state( 'SP' );
		$order->set_billing_postcode( '01310-100' );
		$order->set_billing_country( 'BR' );
		$order->update_meta_data( '_billing_number', '1578' );
		$order->update_meta_data( '_billing_neighborhood', 'Bela Vista' );
		$order->save();

		$address = $order->get_formatted_billing_address();

		$this->assertStringContainsString( 'Empresa Ltda', $address );
		$this->assertStringContainsString( 'Avenida Paulista, 1578', $address );
		$this->assertStringContainsString( 'Bela Vista', $address );
	}

	public function test_an_address_without_a_company_has_no_empty_line() {
		$order = new WC_Order();
		$order->set_billing_first_name( 'Ana' );
		$order->set_billing_last_name( 'Silva' );
		$order->set_billing_address_1( 'Avenida Paulista' );
		$order->set_billing_city( 'Sao Paulo' );
		$order->set_billing_state( 'SP' );
		$order->set_billing_postcode( '01310-100' );
		$order->set_billing_country( 'BR' );
		$order->update_meta_data( '_billing_number', '1578' );
		$order->save();

		$this->assertStringNotContainsString( '<br/><br/>', $order->get_formatted_billing_address() );
	}
}
