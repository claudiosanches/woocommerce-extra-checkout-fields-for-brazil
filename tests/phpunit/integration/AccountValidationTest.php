<?php
/**
 * Tests for the My Account address form validation.
 *
 * WooCommerce reports these through notices rather than the WP_Error the
 * checkout uses, so the codes the checkout tests read are not available here.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

/**
 * Covers valid_save_address_fields.
 */
class AccountValidationTest extends WP_UnitTestCase {

	/**
	 * A CPF and a CNPJ whose check digits are valid.
	 */
	const VALID_CPF  = '111.444.777-35';
	const VALID_CNPJ = '11.222.333/0001-81';

	/**
	 * Front end instance under test.
	 *
	 * @var Extra_Checkout_Fields_For_Brazil_Front_End
	 */
	protected $front_end;

	public function set_up() {
		parent::set_up();

		WC()->initialize_session();
		wc_clear_notices();

		$this->front_end = $this->hooked_front_end();
	}

	public function tear_down() {
		wc_clear_notices();
		parent::tear_down();
	}

	/**
	 * The instance the plugin registered, rather than a second one whose
	 * constructor would hook everything all over again.
	 *
	 * @return Extra_Checkout_Fields_For_Brazil_Front_End
	 */
	protected function hooked_front_end() {
		foreach ( $GLOBALS['wp_filter']['woocommerce_after_save_address_validation'] as $hooks ) {
			foreach ( $hooks as $hook ) {
				if ( is_array( $hook['function'] ) && $hook['function'][0] instanceof Extra_Checkout_Fields_For_Brazil_Front_End ) {
					return $hook['function'][0];
				}
			}
		}

		$this->fail( 'The account address validation is not hooked on woocommerce_after_save_address_validation.' );
	}

	/**
	 * Run the validation and hand back the errors it reported.
	 *
	 * @param array  $settings     Plugin settings.
	 * @param array  $values       Values WooCommerce filled the customer with.
	 * @param string $address_type Address being saved.
	 *
	 * @return array
	 */
	protected function notices( array $settings, array $values, $address_type = 'billing' ) {
		update_option( 'wcbcf_settings', $settings );

		$values   = array_merge( array( 'billing_country' => 'BR' ), $values );
		$customer = new WC_Customer();
		$customer->set_billing_country( $values['billing_country'] );
		unset( $values['billing_country'] );

		foreach ( $values as $key => $value ) {
			$customer->update_meta_data( $key, $value );
		}

		// WooCommerce hands over the field list it rendered, not the values.
		$this->front_end->valid_save_address_fields( $customer->get_id(), $address_type, array(), $customer );

		return wp_list_pluck( wc_get_notices( 'error' ), 'notice' );
	}

	public function test_the_hook_carries_the_customer_the_values_are_read_from() {
		$accepted = 0;

		foreach ( $GLOBALS['wp_filter']['woocommerce_after_save_address_validation'] as $hooks ) {
			foreach ( $hooks as $hook ) {
				if ( is_array( $hook['function'] ) && $hook['function'][0] instanceof Extra_Checkout_Fields_For_Brazil_Front_End ) {
					$accepted = $hook['accepted_args'];
				}
			}
		}

		$this->assertSame( 4, $accepted );
	}

	public function test_nothing_is_reported_without_a_customer() {
		update_option( 'wcbcf_settings', $this->full_settings() );

		$this->front_end->valid_save_address_fields( 1, 'billing', array() );

		$this->assertSame( array(), wc_get_notices( 'error' ) );
	}

	/**
	 * The settings a store asking for both person types runs with.
	 *
	 * @return array
	 */
	protected function full_settings() {
		return array(
			'person_type'   => 1,
			'birthdate'     => 1,
			'validate_cpf'  => 1,
			'validate_cnpj' => 1,
		);
	}

	/**
	 * A complete individual submission, so a test only has to break one field.
	 *
	 * @param array $overrides Fields to replace.
	 *
	 * @return array
	 */
	protected function individual( array $overrides = array() ) {
		return array_merge(
			array(
				'billing_persontype' => '1',
				'billing_cpf'        => self::VALID_CPF,
				'billing_birthdate'  => '01/01/1990',
			),
			$overrides
		);
	}

	public function test_valid_address_reports_nothing() {
		$this->assertSame( array(), $this->notices( $this->full_settings(), $this->individual() ) );
	}

	public function test_invalid_cpf_is_rejected() {
		$notices = $this->notices(
			$this->full_settings(),
			$this->individual( array( 'billing_cpf' => '111.111.111-11' ) )
		);

		$this->assertCount( 1, $notices );
		$this->assertStringContainsString( 'CPF', $notices[0] );
	}

	public function test_invalid_cnpj_is_rejected() {
		$notices = $this->notices(
			$this->full_settings(),
			array(
				'billing_persontype' => '2',
				'billing_cnpj'       => '11.222.333/0001-00',
			)
		);

		$this->assertCount( 1, $notices );
		$this->assertStringContainsString( 'CNPJ', $notices[0] );
	}

	public function test_impossible_birthdate_is_rejected() {
		$notices = $this->notices(
			$this->full_settings(),
			$this->individual( array( 'billing_birthdate' => '31/02/1990' ) )
		);

		$this->assertCount( 1, $notices );
		$this->assertStringContainsString( 'Birthdate', $notices[0] );
	}

	public function test_documents_of_the_other_person_type_are_left_alone() {
		$notices = $this->notices(
			$this->full_settings(),
			$this->individual( array( 'billing_cnpj' => '11.222.333/0001-00' ) )
		);

		$this->assertSame( array(), $notices );
	}

	public function test_a_store_that_does_not_validate_accepts_anything() {
		$notices = $this->notices(
			array(
				'person_type' => 1,
				'birthdate'   => 1,
			),
			$this->individual( array( 'billing_cpf' => '111.111.111-11' ) )
		);

		$this->assertSame( array(), $notices );
	}

	public function test_a_store_without_person_types_only_checks_the_birthdate() {
		$notices = $this->notices(
			array(
				'person_type'  => 0,
				'birthdate'    => 1,
				'validate_cpf' => 1,
			),
			$this->individual(
				array(
					'billing_cpf'       => '111.111.111-11',
					'billing_birthdate' => '31/02/1990',
				)
			)
		);

		$this->assertCount( 1, $notices );
		$this->assertStringContainsString( 'Birthdate', $notices[0] );
	}

	public function test_an_address_outside_brazil_is_skipped_when_the_store_asks_for_it() {
		$settings                = $this->full_settings();
		$settings['only_brazil'] = 1;

		$notices = $this->notices(
			$settings,
			$this->individual(
				array(
					'billing_country' => 'US',
					'billing_cpf'     => '111.111.111-11',
				)
			)
		);

		$this->assertSame( array(), $notices );
	}

	public function test_the_shipping_address_carries_no_documents() {
		$notices = $this->notices(
			$this->full_settings(),
			$this->individual( array( 'billing_cpf' => '111.111.111-11' ) ),
			'shipping'
		);

		$this->assertSame( array(), $notices );
	}

	public function test_validation_can_be_disabled() {
		add_filter( 'wcbcf_disable_checkout_validation', '__return_true' );

		$notices = $this->notices(
			$this->full_settings(),
			$this->individual( array( 'billing_cpf' => '111.111.111-11' ) )
		);

		remove_filter( 'wcbcf_disable_checkout_validation', '__return_true' );

		$this->assertSame( array(), $notices );
	}
}
