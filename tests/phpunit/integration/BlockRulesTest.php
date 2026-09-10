<?php
/**
 * Tests that the document object rules resolve to the right field state.
 *
 * Everywhere else asserts that a rule was registered. These assert what the
 * rule actually decides, which is what the checkout block acts on.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\DocumentObject;
use Automattic\WooCommerce\Blocks\Package;

/**
 * Rule evaluation tests.
 */
class BlockRulesTest extends WP_UnitTestCase {

	/**
	 * WooCommerce's field controller.
	 *
	 * @var CheckoutFields
	 */
	protected $controller;

	public function set_up() {
		parent::set_up();
		$this->controller = Package::container()->get( CheckoutFields::class );
	}

	/**
	 * Register the plugin's fields for the given settings.
	 *
	 * @param array $settings Plugin settings.
	 *
	 * @return void
	 */
	protected function register( array $settings ) {
		update_option( 'wcbcf_settings', $settings );

		foreach ( array_keys( $this->controller->get_additional_fields() ) as $id ) {
			if ( '' !== Extra_Checkout_Fields_For_Brazil_Blocks::field_key( $id ) ) {
				__internal_woocommerce_blocks_deregister_checkout_field( $id );
			}
		}

		( new Extra_Checkout_Fields_For_Brazil_Blocks() )->register_fields();
	}

	/**
	 * Build the document object the block would evaluate rules against.
	 *
	 * @param string $person_type Selected person type, or an empty string.
	 * @param string $country     Billing country.
	 *
	 * @return DocumentObject
	 */
	protected function document( $person_type, $country = 'BR' ) {
		$additional_fields = array();

		if ( '' !== $person_type ) {
			$additional_fields[ Extra_Checkout_Fields_For_Brazil_Blocks::field_id( 'persontype' ) ] = $person_type;
		}

		$document = new DocumentObject(
			array(
				'customer' => array(
					'additional_fields' => $additional_fields,
					'billing_address'   => array( 'country' => $country ),
				),
			)
		);
		$document->set_context( 'contact' );

		return $document;
	}

	/**
	 * Assert the visible and required state of a field.
	 *
	 * @param string         $key      Field key without the namespace.
	 * @param bool           $visible  Whether the field should render.
	 * @param bool           $required Whether the field should be mandatory.
	 * @param DocumentObject $document Document object to evaluate against.
	 *
	 * @return void
	 */
	protected function assertFieldState( $key, $visible, $required, $document ) {
		$id = Extra_Checkout_Fields_For_Brazil_Blocks::field_id( $key );

		$this->assertSame(
			! $visible,
			$this->controller->is_hidden_field( $id, $document ),
			"$key visibility"
		);
		$this->assertSame(
			$required,
			$this->controller->is_required_field( $id, $document ),
			"$key requiredness"
		);
	}

	public function test_no_documents_are_shown_before_a_person_type_is_picked() {
		$this->register(
			array(
				'person_type' => 1,
				'rg'          => 1,
				'ie'          => 1,
			)
		);

		$document = $this->document( '' );

		$this->assertFieldState( 'cpf', false, false, $document );
		$this->assertFieldState( 'rg', false, false, $document );
		$this->assertFieldState( 'cnpj', false, false, $document );
		$this->assertFieldState( 'ie', false, false, $document );
		$this->assertFieldState( 'persontype', true, true, $document );
	}

	public function test_picking_individuals_shows_only_the_individual_documents() {
		$this->register(
			array(
				'person_type' => 1,
				'rg'          => 1,
				'ie'          => 1,
			)
		);

		$document = $this->document( '1' );

		$this->assertFieldState( 'cpf', true, true, $document );
		$this->assertFieldState( 'rg', true, true, $document );
		$this->assertFieldState( 'cnpj', false, false, $document );
		$this->assertFieldState( 'ie', false, false, $document );
	}

	public function test_picking_legal_person_shows_only_the_company_documents() {
		$this->register(
			array(
				'person_type' => 1,
				'rg'          => 1,
				'ie'          => 1,
			)
		);

		$document = $this->document( '2' );

		$this->assertFieldState( 'cnpj', true, true, $document );
		$this->assertFieldState( 'ie', true, true, $document );
		$this->assertFieldState( 'cpf', false, false, $document );
		$this->assertFieldState( 'rg', false, false, $document );
	}

	public function test_only_brazil_keeps_documents_visible_but_optional_abroad() {
		$this->register(
			array(
				'person_type' => 1,
				'only_brazil' => 1,
				'rg'          => 1,
			)
		);

		$this->assertFieldState( 'cpf', true, true, $this->document( '1', 'BR' ) );

		// Still shown, so a Brazilian buying from abroad can fill it in, but
		// never blocking, which is what valid_checkout_fields() has always done.
		$this->assertFieldState( 'cpf', true, false, $this->document( '1', 'US' ) );
		$this->assertFieldState( 'rg', true, false, $this->document( '1', 'US' ) );
	}

	public function test_only_brazil_applies_without_a_person_type_selector() {
		$this->register(
			array(
				'person_type' => 2,
				'only_brazil' => 1,
			)
		);

		$this->assertFieldState( 'cpf', true, true, $this->document( '', 'BR' ) );
		$this->assertFieldState( 'cpf', true, false, $this->document( '', 'US' ) );
	}

	public function test_a_single_person_type_shows_its_documents_unconditionally() {
		$this->register( array( 'person_type' => 3 ) );

		$this->assertFieldState( 'cnpj', true, true, $this->document( '', 'BR' ) );
		$this->assertFieldState( 'cnpj', true, true, $this->document( '', 'US' ) );
	}

	public function test_fields_outside_the_person_type_are_always_required() {
		$this->register(
			array(
				'person_type' => 1,
				'only_brazil' => 1,
				'birthdate'   => 1,
				'gender'      => 1,
				'cell_phone'  => '2',
			)
		);

		foreach ( array( 'birthdate', 'gender', 'cellphone' ) as $key ) {
			$this->assertFieldState( $key, true, true, $this->document( '1', 'US' ) );
		}
	}
}
