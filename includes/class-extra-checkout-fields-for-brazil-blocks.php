<?php
/**
 * Extra checkout fields main class.
 *
 * @package Extra_Checkout_Fields_For_Brazil
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Plugin main class.
 */
class Extra_Checkout_Fields_For_Brazil_Blocks {

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'woocommerce_blocks_loaded', array( $this, 'register_blocks' ) );
		add_action(
			'__experimental_woocommerce_blocks_validate_additional_field',
			array( $this, 'validate_fields' ),
			10,
			3
		);
	}

	/**
	 * Register blocks.
	 *
	 * @return void
	 */
	public function register_blocks() {
		// Stop if the function is not available.
		if ( ! function_exists( '__experimental_woocommerce_blocks_register_checkout_field' ) ) {
			return;
		}

		$settings = get_option( 'wcbcf_settings' );

		$this->register_contact_fields( $settings );

		// Register address fields.
		$this->register_address_fields( $settings );
	}

	/**
	 * Validate birth date.
	 *
	 * @param string $date Date to validate.
	 *
	 * @return WP_Error|bool
	 */
	public function validate_birth_date( $date ) {
		if ( ! Extra_Checkout_Fields_For_Brazil_Formatting::is_valid_date( $date ) ) {
			return new WP_Error( 'invalid_date', __( 'Invalid birth date. Please provide a valid birth date in the dd/mm/yyyy format.', 'woocommerce-extra-checkout-fields-for-brazil' ) );
		}

		return true;
	}

	/**
	 * Validate fields.
	 *
	 * @param WP_Error $errors      WP Error.
	 * @param string   $field_key   Field key.
	 * @param string   $field_value Field value.
	 *
	 * @return WP_Error
	 */
	public function validate_fields( WP_Error $errors, $field_key, $field_value ) {
		$settings = get_option( 'wcbcf_settings' );

		// Validate CPF.
		if (
			'csbmw/cpf' === $field_key
			&& isset( $settings['validate_cpf'] )
			&& ! empty( $field_value )
		) {
			if ( ! Extra_Checkout_Fields_For_Brazil_Formatting::is_cpf( $field_value ) ) {
				$errors->add( 'invalid_cpf', __( 'Invalid CPF. Please provide a valid CPF.', 'woocommerce-extra-checkout-fields-for-brazil' ) );
			}
		}

		// Validate CNPJ.
		if (
			'csbmw/cnpj' === $field_key
			&& isset( $settings['validate_cnpj'] )
			&& ! empty( $field_value )
		) {
			if ( ! Extra_Checkout_Fields_For_Brazil_Formatting::is_cnpj( $field_value ) ) {
				$errors->add( 'invalid_cnpj', __( 'Invalid CNPJ. Please provide a valid CNPJ.', 'woocommerce-extra-checkout-fields-for-brazil' ) );
			}
		}

		return $errors;
	}

	/**
	 * Register billing person type fields.
	 *
	 * @param array $settings Settings.
	 *
	 * @return void
	 */
	private function register_billing_person_type_fields( $settings ) {
		$person_type = intval( $settings['person_type'] );

		if ( 0 === $person_type ) {
			return;
		}

		if ( 1 === $person_type ) {
			__experimental_woocommerce_blocks_register_checkout_field(
				array(
					'id'            => 'csbmw/persontype',
					'label'         => __( 'Person type', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'optionalLabel' => __( 'Person type (optional)', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'location'      => 'contact',
					'type'          => 'select',
					'required'      => true,
					'hidden'        => false,
					'index'         => 5,
					'options'       => array(
						array(
							'label' => __( 'Individuals', 'woocommerce-extra-checkout-fields-for-brazil' ),
							'value' => '1',
						),
						array(
							'label' => __( 'Legal Person', 'woocommerce-extra-checkout-fields-for-brazil' ),
							'value' => '2',
						),
					),
				),
			);
		}

		// For individuals.
		if ( 1 === $person_type || 2 === $person_type ) {
			__experimental_woocommerce_blocks_register_checkout_field(
				array(
					'id'            => 'csbmw/cpf',
					'label'         => __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'optionalLabel' => __( 'CPF (optional)', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'location'      => 'contact',
					'type'          => 'text',
					'required'      => true,
					'hidden'        => false,
					'index'         => 6,
					'attributes'    => array(
						'autocomplete' => 'cpf',
						'pattern'      => '\d{3}\.\d{3}\.\d{3}-\d{2}',
						'title'        => __( 'Enter a valid CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
					),
				)
			);

			if ( isset( $settings['rg'] ) ) {
				__experimental_woocommerce_blocks_register_checkout_field(
					array(
						'id'            => 'csbmw/rg',
						'label'         => __( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'optionalLabel' => __( 'RG (optional)', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'location'      => 'contact',
						'type'          => 'text',
						'required'      => false,
						'hidden'        => false,
						'index'         => 6,
						'attributes'    => array(
							'autocomplete' => 'rg',
							'pattern'      => '[0-9]{2}\.?[0-9]{3}\.?[0-9]{3}\-?[0-9]{1}',
							'title'        => __( 'Enter a valid RG', 'woocommerce-extra-checkout-fields-for-brazil' ),
						),
					)
				);
			}
		}

		// For legal person.
		if ( 1 === $person_type || 3 === $person_type ) {
			// Company name.
			__experimental_woocommerce_blocks_register_checkout_field(
				array(
					'id'            => 'csbmw/company-name',
					'label'         => __( 'Company name', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'optionalLabel' => __( 'Company name (optional)', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'location'      => 'contact',
					'type'          => 'text',
					'required'      => true,
					'hidden'        => false,
					'index'         => 6,
				)
			);

			// CNPJ.
			__experimental_woocommerce_blocks_register_checkout_field(
				array(
					'id'            => 'csbmw/cnpj',
					'label'         => __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'optionalLabel' => __( 'CNPJ (optional)', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'location'      => 'contact',
					'type'          => 'text',
					'required'      => true,
					'hidden'        => false,
					'index'         => 6,
					'attributes'    => array(
						'autocomplete' => 'cnpj',
						'pattern'      => '[0-9]{2}\.?[0-9]{3}\.?[0-9]{3}\/?[0-9]{4}\-?[0-9]{2}',
						'title'        => __( 'Enter a valid CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
					),
				)
			);

			// State registration.
			if ( isset( $settings['ie'] ) ) {
				__experimental_woocommerce_blocks_register_checkout_field(
					array(
						'id'            => 'csbmw/ie',
						'label'         => __( 'State registration', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'optionalLabel' => __( 'State registration (optional)', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'location'      => 'contact',
						'type'          => 'text',
						'required'      => false,
						'hidden'        => false,
						'index'         => 10,
					)
				);
			}
		}
	}

	/**
	 * Register contact fields.
	 *
	 * @param array $settings Settings.
	 *
	 * @return void
	 */
	private function register_contact_fields( $settings ) {
		// Register billing person type fields.
		$this->register_billing_person_type_fields( $settings );

		// Register billing birthday field.
		if ( isset( $settings['birthdate'] ) ) {
			__experimental_woocommerce_blocks_register_checkout_field(
				array(
					'id'            => 'csbmw/birthdate',
					'label'         => __( 'Birth Date', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'optionalLabel' => __( 'Birth Date (optional)', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'location'      => 'contact',
					'type'          => 'text',
					'required'      => true,
					'hidden'        => false,
					'index'         => 10,
					'attributes'    => array(
						'autocomplete' => 'bday',
						'pattern'      => '[0-3]{1}\d{1}/[0-1]{1}\d{1}/[1-2]{1}[0-9]{3}',
						'title'        => __( 'Enter a valid birth date as dd/mm/yyyy', 'woocommerce-extra-checkout-fields-for-brazil' ),
					),
					'validate_callback' => array( $this, 'validate_birth_date' ),
				)
			);
		}

		// Register billing gender field.
		if ( isset( $settings['gender'] ) ) {
			__experimental_woocommerce_blocks_register_checkout_field(
				array(
					'id'            => 'csbmw/gender',
					'label'         => __( 'Gender', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'optionalLabel' => __( 'Gender (optional)', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'location'      => 'contact',
					'type'          => 'select',
					'required'      => true,
					'hidden'        => false,
					'index'         => 10,
					'options'       => array(
						array(
							'label' => __( 'Prefer not to say', 'woocommerce-extra-checkout-fields-for-brazil' ),
							'value' => __( 'Prefer not to say', 'woocommerce-extra-checkout-fields-for-brazil' ),
						),
						array(
							'label' => __( 'Female', 'woocommerce-extra-checkout-fields-for-brazil' ),
							'value' => __( 'Female', 'woocommerce-extra-checkout-fields-for-brazil' ),
						),
						array(
							'label' => __( 'Male', 'woocommerce-extra-checkout-fields-for-brazil' ),
							'value' => __( 'Male', 'woocommerce-extra-checkout-fields-for-brazil' ),
						),
						array(
							'label' => __( 'Other', 'woocommerce-extra-checkout-fields-for-brazil' ),
							'value' => __( 'Other', 'woocommerce-extra-checkout-fields-for-brazil' ),
						),
					),
				)
			);
		}
	}

	/**
	 * Register address fields.
	 *
	 * @param array $settings Settings.
	 *
	 * @return void
	 */
	private function register_address_fields( $settings ) {
		// Register billing number field.
		__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'            => 'csbmw/number',
				'label'         => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'optionalLabel' => __( 'Number (optional)', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'location'      => 'address',
				'type'          => 'text',
				'required'      => true,
				'hidden'        => false,
				'index'         => 41,
			)
		);

		// Register billing neighborhood field.
		__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'            => 'csbmw/neighborhood',
				'label'         => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'optionalLabel' => __( 'Neighborhood (optional)', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'location'      => 'address',
				'type'          => 'text',
				'required'      => isset( $settings['neighborhood_required'] ) && '1' === $settings['neighborhood_required'],
				'hidden'        => false,
				'index'         => 42,
			)
		);

		// Register billing cellphone field.
		if (
			isset( $settings['cell_phone'] )
			&& in_array( wc_get_var( $settings['cell_phone'], '0' ), array( '1', '2' ), true )
		) {
			__experimental_woocommerce_blocks_register_checkout_field(
				array(
					'id'            => 'csbmw/cellphone',
					'label'         => __( 'Cellphone', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'optionalLabel' => __( 'Cellphone (optional)', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'location'      => 'address',
					'type'          => 'text',
					'required'      => '2' === $settings['cell_phone'],
					'hidden'        => false,
					'index'         => 89,
					'attributes'    => array(
						'autocomplete' => 'tel',
					),
				)
			);
		}
	}
}

new Extra_Checkout_Fields_For_Brazil_Blocks();
