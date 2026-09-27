<?php
/**
 * Tests for phone numbers from Brazil and abroad.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

/**
 * Covers formatting, validation, normalization on save and the E.164 values
 * of the REST API.
 */
class PhoneTest extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		update_option( 'wcbcf_settings', array() );
	}

	/**
	 * Phones, the address they were typed for and how the store writes them.
	 *
	 * @return array
	 */
	public function format_provider() {
		return array(
			'mobile'                      => array( '11987654321', 'BR', '(11) 98765-4321', '+5511987654321' ),
			'landline'                    => array( '1133334444', 'BR', '(11) 3333-4444', '+551133334444' ),
			'pasted with +55'             => array( '+55 11 98765-4321', 'BR', '(11) 98765-4321', '+5511987654321' ),
			'55 without the plus'         => array( '5511987654321', 'BR', '(11) 98765-4321', '+5511987654321' ),
			'trunk zero'                  => array( '011 98765-4321', 'BR', '(11) 98765-4321', '+5511987654321' ),
			'no country'                  => array( '11987654321', '', '(11) 98765-4321', '+5511987654321' ),
			'Brazilian on a US address'   => array( '+55 11 98765-4321', 'US', '+55 (11) 98765-4321', '+5511987654321' ),
			'US number in Brazil'         => array( '+1 212 555 1234', 'BR', '+1 212 555 1234', '+12125551234' ),
			'US number, US address'       => array( '(212) 555-1234', 'US', '+1 (212) 555-1234', '+12125551234' ),
			'UK number with (0)'          => array( '+44 (0)20 7946 0958', 'GB', '+44 20 7946 0958', '+442079460958' ),
			'UK national number'          => array( '020 7946 0958', 'GB', '+44 20 7946 0958', '+442079460958' ),
			'Italy keeps its zero'        => array( '+39 06 1234 5678', 'IT', '+39 06 1234 5678', '+390612345678' ),
			'too short, left as typed'    => array( '119876543', 'BR', '119876543', '' ),
			'unknown code, left as typed' => array( '+999 12345678', 'BR', '+999 12345678', '' ),
			'mobile without its nine'     => array( '(11) 88765-4321', 'BR', '(11) 88765-4321', '' ),
		);
	}

	/**
	 * @dataProvider format_provider
	 *
	 * @param string $phone   Phone as typed.
	 * @param string $country Address country.
	 * @param string $stored  How the store writes it.
	 * @param string $e164    E.164, empty when incomplete.
	 */
	public function test_a_phone_is_written_for_its_address( $phone, $country, $stored, $e164 ) {
		$this->assertSame( $stored, Extra_Checkout_Fields_For_Brazil_Phone::format( $phone, $country ) );
		$this->assertSame( $e164, Extra_Checkout_Fields_For_Brazil_Phone::e164( $phone, $country ) );
	}

	public function test_the_store_can_keep_the_brazilian_code() {
		update_option( 'wcbcf_settings', array( 'phone_format' => 'international' ) );

		$this->assertSame( '+55 (11) 98765-4321', Extra_Checkout_Fields_For_Brazil_Phone::format( '11987654321', 'BR' ) );
	}

	public function test_a_number_keeps_the_country_it_was_typed_for() {
		$this->assertSame( '+55 (11) 98765-4321', Extra_Checkout_Fields_For_Brazil_Phone::format( '(11) 98765-4321', 'US', 'BR' ) );
	}

	public function test_woocommerce_validation_holds_numbers_to_their_country() {
		$this->assertTrue( WC_Validation::is_phone( '(11) 98765-4321', 'BR' ) );
		$this->assertFalse( WC_Validation::is_phone( '(11) 8765-432', 'BR' ) );
		$this->assertTrue( WC_Validation::is_phone( '+1 212 555 1234', 'BR' ) );
		$this->assertFalse( WC_Validation::is_phone( '+1 212', 'US' ) );

		// Without an address only a code gives the number a country.
		$this->assertTrue( WC_Validation::is_phone( '123' ) );
		$this->assertFalse( WC_Validation::is_phone( '+44 12' ) );
	}

	public function test_validation_can_be_turned_off() {
		add_filter( 'wcbcf_disable_checkout_validation', '__return_true' );

		$this->assertTrue( WC_Validation::is_phone( '(11) 8765-432', 'BR' ) );

		remove_filter( 'wcbcf_disable_checkout_validation', '__return_true' );
	}

	public function test_an_order_saves_its_phones_in_the_store_format() {
		$order = wc_create_order();
		$order->set_billing_country( 'BR' );
		$order->set_billing_phone( '+55 11 98765-4321' );
		$order->set_shipping_country( 'US' );
		$order->set_shipping_phone( '2125551234' );
		$order->update_meta_data( '_billing_cellphone', '5511912345678' );
		$order->save();

		$order = wc_get_order( $order->get_id() );

		$this->assertSame( '(11) 98765-4321', $order->get_billing_phone() );
		$this->assertSame( '+1 2125551234', $order->get_shipping_phone() );
		$this->assertSame( '(11) 91234-5678', $order->get_meta( '_billing_cellphone' ) );
	}

	public function test_an_unrelated_save_leaves_older_phones_alone() {
		$order = wc_create_order();
		$order->set_billing_country( 'BR' );
		$order->save();

		// As an older version stored it.
		update_option( 'wcbcf_settings', array( 'phone_format' => 'international' ) );
		$order->update_meta_data( '_billing_cellphone', '(11) 91234-5678' );
		$order->save_meta_data();

		$order = wc_get_order( $order->get_id() );
		$order->set_status( 'processing' );
		$order->save();

		$this->assertSame( '(11) 91234-5678', wc_get_order( $order->get_id() )->get_meta( '_billing_cellphone' ) );
	}

	public function test_a_new_country_keeps_the_phone_it_had() {
		$customer = new WC_Customer();
		$customer->set_billing_country( 'BR' );
		$customer->set_billing_phone( '11987654321' );
		$customer->set_email( 'phone@example.com' );
		$customer->save();

		$customer->set_billing_country( 'US' );
		$customer->save();

		$this->assertSame( '+55 (11) 98765-4321', ( new WC_Customer( $customer->get_id() ) )->get_billing_phone() );
	}

	public function test_the_rest_api_gives_every_phone_in_e164() {
		update_option( 'wcbcf_settings', array( 'person_type' => '1' ) );

		$order = wc_create_order();
		$order->set_billing_country( 'BR' );
		$order->set_billing_phone( '(11) 3333-4444' );
		$order->update_meta_data( '_billing_cellphone', '(11) 98765-4321' );
		$order->set_shipping_country( 'GB' );
		$order->set_shipping_phone( '+44 20 7946 0958' );
		$order->save();

		$response = ( new Extra_Checkout_Fields_For_Brazil_API() )->orders_response(
			new WP_REST_Response(
				array(
					'billing'  => array(),
					'shipping' => array(),
				)
			),
			$order
		);

		$this->assertSame( '+551133334444', $response->data['billing']['phone_e164'] );
		$this->assertSame( '+5511987654321', $response->data['billing']['cellphone_e164'] );
		$this->assertSame( '+442079460958', $response->data['shipping']['phone_e164'] );
	}

	public function test_the_scripts_get_the_codes_and_the_picker_when_offered() {
		$params = Extra_Checkout_Fields_For_Brazil_Phone::script_params( array() );

		$this->assertSame( '55', $params['codes']['BR'] );
		$this->assertSame( '1', $params['codes']['JM'] );
		$this->assertSame( 'no', $params['picker'] );
		$this->assertSame( array(), $params['countries'] );

		$params = Extra_Checkout_Fields_For_Brazil_Phone::script_params( array( 'phone_country_picker' => '1' ) );

		$this->assertSame( 'yes', $params['picker'] );
		$this->assertArrayHasKey( 'BR', $params['countries'] );
	}
}
