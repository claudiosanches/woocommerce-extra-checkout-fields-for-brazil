<?php
/**
 * Tests for the fields the order screen renders.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

/**
 * Covers shop_order_billing_fields.
 */
class OrderScreenFieldsTest extends WP_UnitTestCase {

	/**
	 * Admin order instance under test.
	 *
	 * @var Extra_Checkout_Fields_For_Brazil_Order
	 */
	protected $screen;

	public function set_up() {
		parent::set_up();

		update_option(
			'wcbcf_settings',
			array(
				'person_type' => 1,
				'rg'          => 1,
				'ie'          => 1,
				'birthdate'   => 1,
				'gender'      => 1,
				'cell_phone'  => '2',
			)
		);

		// The admin classes only load behind is_admin().
		include_once CSBMW_PLUGIN_DIR . '/includes/admin/class-extra-checkout-fields-for-brazil-order.php';

		$this->screen = new Extra_Checkout_Fields_For_Brazil_Order();
	}

	/**
	 * The fields the screen renders for the billing address.
	 *
	 * @return array
	 */
	protected function billing_fields() {
		return $this->screen->shop_order_billing_fields(
			array(
				'first_name' => array( 'label' => 'First name' ),
				'last_name'  => array( 'label' => 'Last name' ),
				'company'    => array( 'label' => 'Company' ),
				'address_1'  => array( 'label' => 'Address line 1' ),
				'address_2'  => array( 'label' => 'Address line 2' ),
				'city'       => array( 'label' => 'City' ),
				'postcode'   => array( 'label' => 'Postcode' ),
				'country'    => array( 'label' => 'Country' ),
				'state'      => array( 'label' => 'State' ),
				'email'      => array( 'label' => 'Email' ),
				'phone'      => array( 'label' => 'Phone' ),
			)
		);
	}

	/**
	 * Both checkouts offer a fixed list, and the block field only accepts what
	 * is on it, so anything else typed here was dropped from the block copy.
	 *
	 * @return void
	 */
	public function test_gender_is_chosen_from_the_same_list_as_the_checkouts() {
		$fields = $this->billing_fields();

		$this->assertSame( 'select', $fields['gender']['type'] );

		foreach ( Extra_Checkout_Fields_For_Brazil_Blocks::get_gender_options() as $label ) {
			$this->assertArrayHasKey( $label, $fields['gender']['options'], $label );
		}
	}

	public function test_gender_can_be_left_unset() {
		$fields = $this->billing_fields();

		$this->assertArrayHasKey( '', $fields['gender']['options'] );
	}

	public function test_the_documents_are_rendered() {
		$fields = $this->billing_fields();

		foreach ( array( 'persontype', 'cpf', 'rg', 'cnpj', 'ie', 'birthdate', 'cellphone', 'number', 'neighborhood' ) as $key ) {
			$this->assertArrayHasKey( $key, $fields, $key );
		}
	}
}
