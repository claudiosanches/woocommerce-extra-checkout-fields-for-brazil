<?php
/**
 * Tests for Number and Neighborhood on the classic address forms.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

/**
 * Covers the fields and locale that show them on Brazilian addresses only.
 */
class AddressCountryTest extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		update_option( 'wcbcf_settings', array( 'neighborhood_required' => '1' ) );

		// The locale is built once per request.
		WC()->countries = new WC_Countries();
	}

	public function tear_down() {
		WC()->countries = new WC_Countries();
		parent::tear_down();
	}

	/**
	 * Address fields as WooCommerce hands them to the forms.
	 *
	 * @param string $country Country code.
	 * @param string $type    Address type prefix.
	 *
	 * @return array
	 */
	protected function fields( $country, $type ) {
		return WC()->countries->get_address_fields( $country, $type );
	}

	public function test_a_foreign_address_hides_both_fields() {
		foreach ( array( 'billing_', 'shipping_' ) as $type ) {
			$fields = $this->fields( 'US', $type );

			$this->assertTrue( $fields[ $type . 'number' ]['hidden'], $type );
			$this->assertTrue( $fields[ $type . 'neighborhood' ]['hidden'], $type );
		}
	}

	public function test_a_brazilian_address_asks_for_both() {
		foreach ( array( 'billing_', 'shipping_' ) as $type ) {
			$fields = $this->fields( 'BR', $type );

			$this->assertFalse( $fields[ $type . 'number' ]['hidden'], $type );
			$this->assertTrue( $fields[ $type . 'number' ]['required'], $type );
			$this->assertTrue( $fields[ $type . 'neighborhood' ]['required'], $type );
		}
	}

	public function test_the_locale_shows_them_in_brazil_only() {
		$locale = WC()->countries->get_country_locale();

		$this->assertTrue( $locale['default']['number']['hidden'] );
		$this->assertFalse( $locale['default']['number']['required'] );
		$this->assertTrue( $locale['default']['neighborhood']['hidden'] );

		$this->assertFalse( $locale['BR']['number']['hidden'] );
		$this->assertTrue( $locale['BR']['number']['required'] );
		$this->assertTrue( $locale['BR']['neighborhood']['required'] );

		// Sorted with the rest of the address, where My Account would
		// otherwise leave them after the whole form.
		$this->assertSame( 48, $locale['BR']['number']['priority'] );
		$this->assertSame( 49, $locale['default']['neighborhood']['priority'] );

		// The CEP first, since it fills in most of the rest, and after the
		// country, which the classic forms put at 40.
		$this->assertSame( 42, $locale['BR']['postcode']['priority'] );
		$this->assertSame( 44, $locale['BR']['address_1']['priority'] );
		$this->assertSame( 46, $locale['BR']['address_2']['priority'] );

		// After the name on the checkout block too, whatever the country.
		$this->assertSame( 40, $locale['BR']['country']['priority'] );
		$this->assertSame( 40, $locale['US']['country']['priority'] );
	}

	public function test_the_locale_reaches_both_forms() {
		$selectors = WC()->countries->get_country_locale_field_selectors();

		$this->assertStringContainsString( '#billing_number_field', $selectors['number'] );
		$this->assertStringContainsString( '#shipping_neighborhood_field', $selectors['neighborhood'] );
		$this->assertStringContainsString( '[id="csbmw/number_field"]', $selectors['number'] );
	}

	/**
	 * WooCommerce reapplies these on every country change, so the layout has
	 * to live in the locale.
	 *
	 * @return void
	 */
	public function test_brazil_pairs_the_address_rows() {
		$locale = WC()->countries->get_country_locale();

		$this->assertContains( 'form-row-first', $locale['BR']['number']['class'] );
		$this->assertContains( 'form-row-last', $locale['BR']['neighborhood']['class'] );
		$this->assertContains( 'form-row-wide', $locale['BR']['address_2']['class'] );

		update_option(
			'wcbcf_settings',
			array( 'fields_style' => 'wide' )
		);
		WC()->countries = new WC_Countries();

		$this->assertContains( 'form-row-wide', WC()->countries->get_country_locale()['BR']['number']['class'] );
	}

	/**
	 * What the No number option writes, per settings.
	 *
	 * @return array
	 */
	public function no_number_provider() {
		return array(
			'not offered'         => array( array( 'no_number_value' => 'N/A' ), '' ),
			'offered, no value'   => array( array( 'no_number' => '1' ), 'S/N' ),
			'offered, blank'      => array( array( 'no_number' => '1', 'no_number_value' => '  ' ), 'S/N' ),
			'offered, store sets' => array( array( 'no_number' => '1', 'no_number_value' => 'N/A' ), 'N/A' ),
		);
	}

	/**
	 * @dataProvider no_number_provider
	 *
	 * @param array  $settings Plugin settings.
	 * @param string $expected Value written.
	 */
	public function test_the_no_number_value( $settings, $expected ) {
		$this->assertSame( $expected, Extra_Checkout_Fields_For_Brazil::no_number_value( $settings ) );
	}

	/**
	 * Address numbers and whether a carrier takes them.
	 *
	 * @return array
	 */
	public function house_number_provider() {
		return array(
			'digits'                       => array( '1578', '', true ),
			'padded'                       => array( ' 42 ', '', true ),
			'range'                        => array( '1-12', '', false ),
			'letter'                       => array( '12A', '', false ),
			'no number, offered'           => array( 'S/N', 'S/N', true ),
			'no number, in lower case'     => array( 's/n', 'S/N', true ),
			'no number, not offered'       => array( 'S/N', '', false ),
			'another value than the store' => array( 'N/A', 'S/N', false ),
		);
	}

	/**
	 * @dataProvider house_number_provider
	 *
	 * @param string $number    Address number.
	 * @param string $no_number The No number value.
	 * @param bool   $expected  Whether it is accepted.
	 */
	public function test_an_address_number_is_digits_only( $number, $no_number, $expected ) {
		$this->assertSame( $expected, Extra_Checkout_Fields_For_Brazil_Validation::is_house_number( $number, $no_number ) );
	}

	public function test_the_block_checkout_refuses_a_number_with_letters() {
		$blocks = new Extra_Checkout_Fields_For_Brazil_Blocks();
		$field  = array(
			'id'    => 'csbmw/number',
			'label' => 'Number',
		);

		$this->assertTrue( $blocks->validate_field( '1578', $field ) );
		$this->assertInstanceOf( WP_Error::class, $blocks->validate_field( '1-12', $field ) );
	}

	public function test_the_classic_checkout_checks_each_brazilian_address() {
		$front  = new Extra_Checkout_Fields_For_Brazil_Front_End();
		$errors = new WP_Error();

		$front->valid_checkout_fields(
			array(
				'billing_country'           => 'BR',
				'billing_number'            => '12A',
				'ship_to_different_address' => 1,
				'shipping_country'          => 'US',
				'shipping_number'           => '12A',
			),
			$errors
		);

		$this->assertSame( array( 'billing_number_invalid' ), $errors->get_error_codes() );
	}

	/**
	 * Limit what the store sells and ships to.
	 *
	 * @param array $sells Countries the store sells to, or none for all.
	 * @param array $ships Countries it ships to, or none for those it sells to.
	 *
	 * @return void
	 */
	protected function limit_countries( $sells, $ships ) {
		update_option( 'woocommerce_allowed_countries', $sells ? 'specific' : 'all' );
		update_option( 'woocommerce_specific_allowed_countries', $sells );
		update_option( 'woocommerce_ship_to_countries', $ships ? 'specific' : '' );
		update_option( 'woocommerce_specific_ship_to_countries', $ships );
	}

	/**
	 * The classic markup of an address's country field.
	 *
	 * @param string $type billing or shipping.
	 *
	 * @return string
	 */
	protected function country_field( $type ) {
		return woocommerce_form_field(
			$type . '_country',
			array(
				'type'   => 'country',
				'return' => true,
			)
		);
	}

	public function test_hidden_country_keeps_brazil_for_the_scripts() {
		update_option( 'wcbcf_settings', array( 'country_field' => 'hidden' ) );
		$this->limit_countries( array( 'BR' ), array() );

		foreach ( array( 'billing', 'shipping' ) as $type ) {
			$field = $this->country_field( $type );

			$this->assertSame( 'hidden', Extra_Checkout_Fields_For_Brazil::country_field_mode( $type ), $type );
			$this->assertStringNotContainsString( '<select', $field, $type );
			$this->assertStringContainsString( 'type="hidden" class="country_to_state" name="' . $type . '_country"', $field, $type );
			$this->assertStringContainsString( 'value="BR"', $field, $type );
		}
	}

	public function test_country_field_is_left_alone_once_another_country_is_allowed() {
		update_option( 'wcbcf_settings', array( 'country_field' => 'hidden' ) );
		$this->limit_countries( array( 'BR', 'PT' ), array() );

		$this->assertSame( 'select', Extra_Checkout_Fields_For_Brazil::country_field_mode( 'billing' ) );
		$this->assertStringContainsString( '<select', $this->country_field( 'billing' ) );
	}

	public function test_each_address_follows_its_own_countries() {
		update_option( 'wcbcf_settings', array( 'country_field' => 'text' ) );
		$this->limit_countries( array(), array( 'BR' ) );

		$this->assertSame( 'select', Extra_Checkout_Fields_For_Brazil::country_field_mode( 'billing' ) );
		$this->assertSame( 'text', Extra_Checkout_Fields_For_Brazil::country_field_mode( 'shipping' ) );
	}
}
