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
		$this->assertSame( 55, $locale['BR']['number']['priority'] );
		$this->assertSame( 58, $locale['default']['neighborhood']['priority'] );
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
}
