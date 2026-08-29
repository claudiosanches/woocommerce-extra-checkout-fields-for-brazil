<?php
/**
 * Tests for the checkout block field registration.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;

/**
 * Extra_Checkout_Fields_For_Brazil_Blocks tests.
 */
class BlocksFieldsTest extends WP_UnitTestCase {

	/**
	 * Register the plugin fields for a given settings array and return them.
	 *
	 * @param array $settings Plugin settings.
	 *
	 * @return array Registered fields keyed by id.
	 */
	protected function register_with( array $settings ) {
		update_option( 'wcbcf_settings', $settings );

		$controller = Package::container()->get( CheckoutFields::class );

		foreach ( array_keys( $controller->get_additional_fields() ) as $field_id ) {
			if ( '' !== Extra_Checkout_Fields_For_Brazil_Blocks::field_key( $field_id ) ) {
				__internal_woocommerce_blocks_deregister_checkout_field( $field_id );
			}
		}

		( new Extra_Checkout_Fields_For_Brazil_Blocks() )->register_fields();

		return array_filter(
			$controller->get_additional_fields(),
			static function ( $field_id ) {
				return '' !== Extra_Checkout_Fields_For_Brazil_Blocks::field_key( $field_id );
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	public function test_registers_nothing_extra_when_person_type_is_disabled() {
		$fields = $this->register_with( array( 'person_type' => 0 ) );

		$this->assertSame(
			array( 'csbmw/number', 'csbmw/neighborhood' ),
			array_keys( $fields )
		);
	}

	public function test_registers_both_document_sets_for_person_type_one() {
		$fields = $this->register_with(
			array(
				'person_type' => 1,
				'rg'          => 1,
				'ie'          => 1,
			)
		);

		foreach ( array( 'persontype', 'cpf', 'rg', 'cnpj', 'ie' ) as $key ) {
			$this->assertArrayHasKey( Extra_Checkout_Fields_For_Brazil_Blocks::field_id( $key ), $fields );
		}
	}

	public function test_registers_only_cpf_for_individuals() {
		$fields = $this->register_with( array( 'person_type' => 2 ) );

		$this->assertArrayHasKey( 'csbmw/cpf', $fields );
		$this->assertArrayNotHasKey( 'csbmw/cnpj', $fields );
		$this->assertArrayNotHasKey( 'csbmw/persontype', $fields );
	}

	public function test_registers_only_cnpj_for_legal_persons() {
		$fields = $this->register_with( array( 'person_type' => 3 ) );

		$this->assertArrayHasKey( 'csbmw/cnpj', $fields );
		$this->assertArrayNotHasKey( 'csbmw/cpf', $fields );
		$this->assertArrayNotHasKey( 'csbmw/persontype', $fields );
	}

	public function test_optional_fields_follow_their_settings() {
		$fields = $this->register_with( array( 'person_type' => 2 ) );

		$this->assertArrayNotHasKey( 'csbmw/rg', $fields );
		$this->assertArrayNotHasKey( 'csbmw/birthdate', $fields );
		$this->assertArrayNotHasKey( 'csbmw/gender', $fields );
		$this->assertArrayNotHasKey( 'csbmw/cellphone', $fields );

		$fields = $this->register_with(
			array(
				'person_type' => 2,
				'rg'          => 1,
				'birthdate'   => 1,
				'gender'      => 1,
				'cell_phone'  => '2',
			)
		);

		$this->assertArrayHasKey( 'csbmw/rg', $fields );
		$this->assertArrayHasKey( 'csbmw/birthdate', $fields );
		$this->assertArrayHasKey( 'csbmw/gender', $fields );
		$this->assertTrue( $fields['csbmw/cellphone']['required'] );
	}

	public function test_cell_phone_is_optional_when_set_to_one() {
		$fields = $this->register_with(
			array(
				'person_type' => 0,
				'cell_phone'  => '1',
			)
		);

		$this->assertFalse( $fields['csbmw/cellphone']['required'] );
	}

	public function test_documents_are_conditional_when_both_person_types_are_accepted() {
		$fields = $this->register_with( array( 'person_type' => 1 ) );

		// A rule rather than a boolean means the block resolves it per request.
		$this->assertIsArray( $fields['csbmw/cpf']['required'] );
		$this->assertIsArray( $fields['csbmw/cpf']['hidden'] );
		$this->assertIsArray( $fields['csbmw/cnpj']['hidden'] );
	}

	public function test_documents_are_unconditional_for_a_single_person_type() {
		$fields = $this->register_with( array( 'person_type' => 2 ) );

		$this->assertTrue( $fields['csbmw/cpf']['required'] );
		$this->assertFalse( $fields['csbmw/cpf']['hidden'] );
	}

	public function test_neighborhood_requiredness_follows_its_setting() {
		$fields = $this->register_with( array( 'person_type' => 0 ) );
		$this->assertFalse( $fields['csbmw/neighborhood']['required'] );

		$fields = $this->register_with(
			array(
				'person_type'           => 0,
				'neighborhood_required' => '1',
			)
		);
		$this->assertTrue( $fields['csbmw/neighborhood']['required'] );
	}

	public function test_validate_field_reports_missing_required_values() {
		update_option( 'wcbcf_settings', array( 'person_type' => 2 ) );

		$blocks = new Extra_Checkout_Fields_For_Brazil_Blocks();
		$result = $blocks->validate_field(
			'',
			array(
				'id'       => 'csbmw/cpf',
				'label'    => 'CPF',
				'required' => true,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_required_checkout_field', $result->get_error_code() );
	}

	public function test_validate_field_allows_empty_optional_values() {
		update_option( 'wcbcf_settings', array( 'person_type' => 2 ) );

		$blocks = new Extra_Checkout_Fields_For_Brazil_Blocks();

		$this->assertTrue(
			$blocks->validate_field(
				'',
				array(
					'id'       => 'csbmw/cpf',
					'label'    => 'CPF',
					'required' => false,
				)
			)
		);
	}

	public function test_validate_field_rejects_malformed_documents() {
		update_option(
			'wcbcf_settings',
			array(
				'person_type'   => 1,
				'validate_cpf'  => 1,
				'validate_cnpj' => 1,
			)
		);

		$blocks = new Extra_Checkout_Fields_For_Brazil_Blocks();

		$cases = array(
			'csbmw/cpf'       => array( '111.444.777-00', 'woocommerce_invalid_cpf' ),
			'csbmw/cnpj'      => array( '11.222.333/0001-00', 'woocommerce_invalid_cnpj' ),
			'csbmw/birthdate' => array( '31/02/1990', 'woocommerce_invalid_birthdate' ),
		);

		foreach ( $cases as $field_id => $case ) {
			list( $value, $code ) = $case;

			$result = $blocks->validate_field(
				$value,
				array(
					'id'       => $field_id,
					'label'    => $field_id,
					'required' => true,
				)
			);

			$this->assertWPError( $result, $field_id );
			$this->assertSame( $code, $result->get_error_code(), $field_id );
		}
	}

	public function test_validate_field_skips_document_checks_when_validation_is_off() {
		update_option( 'wcbcf_settings', array( 'person_type' => 1 ) );

		$blocks = new Extra_Checkout_Fields_For_Brazil_Blocks();

		$this->assertTrue(
			$blocks->validate_field(
				'111.444.777-00',
				array(
					'id'       => 'csbmw/cpf',
					'label'    => 'CPF',
					'required' => true,
				)
			)
		);
	}
}
