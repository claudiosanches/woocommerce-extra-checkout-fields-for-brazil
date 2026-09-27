<?php
/**
 * Tests for what this plugin changes in Fluid Checkout's settings.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

/**
 * Covers the Fluid Checkout filters in the integrations class.
 */
class FluidCheckoutTest extends WP_UnitTestCase {

	/**
	 * Company keeps this plugin's place while legal persons alone are asked
	 * for it.
	 */
	public function test_leaves_a_dynamic_company_alone() {
		update_option( 'wcbcf_settings', array( 'person_type' => 1 ) );

		$fields = apply_filters( 'fc_checkout_field_args', array( 'billing_company' => array( 'priority' => 40 ) ) );

		$this->assertArrayNotHasKey( 'billing_company', $fields );
	}

	/**
	 * Company follows Fluid Checkout when the store uses WooCommerce's
	 * setting.
	 */
	public function test_keeps_a_woocommerce_company() {
		update_option(
			'wcbcf_settings',
			array(
				'person_type' => 1,
				'company'     => 'woocommerce',
			)
		);

		$fields = apply_filters( 'fc_checkout_field_args', array( 'billing_company' => array( 'priority' => 40 ) ) );

		$this->assertSame( 40, $fields['billing_company']['priority'] );
	}

	/**
	 * The valid mark stays off the fields with a toggle inside, keeping
	 * their classes.
	 */
	public function test_hides_the_valid_mark_behind_toggles() {
		$fields = apply_filters( 'fc_checkout_field_args', array( 'billing_ie' => array( 'class' => array( 'form-row-last' ) ) ) );

		$this->assertSame( array( 'form-row-last', 'fc-no-validation-icon' ), $fields['billing_ie']['class'] );
		$this->assertSame( array( 'fc-no-validation-icon' ), $fields['billing_number']['class'] );
		$this->assertSame( array( 'fc-no-validation-icon' ), $fields['shipping_number']['class'] );
	}

	/**
	 * Fluid Checkout's own CPF and CNPJ checks are turned off.
	 */
	public function test_turns_off_its_document_checks() {
		$settings = apply_filters(
			'fc_checkout_validation_brazilian_documents_script_settings',
			array(
				'validateCPF'  => 'yes',
				'validateCNPJ' => 'yes',
			)
		);

		$this->assertSame( 'no', $settings['validateCPF'] );
		$this->assertSame( 'no', $settings['validateCNPJ'] );
	}
}
