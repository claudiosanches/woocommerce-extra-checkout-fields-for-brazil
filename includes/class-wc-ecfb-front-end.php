<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Front-end actions.
 */
class Extra_Checkout_Fields_For_Brazil_Front_End {

	/**
	 * Initialize the front-end actions.
	 */
	public function __construct() {
		// Load custom order data.
		add_filter( 'woocommerce_load_order_data', array( $this, 'load_order_data' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// New checkout fields.
		add_filter( 'woocommerce_billing_fields', array( $this, 'checkout_billing_fields' ) );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'checkout_shipping_fields' ) );

		// Valid checkout fields.
		add_action( 'woocommerce_checkout_process', array( $this, 'valid_checkout_fields' ) );

		// Custom address format.
		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'localisation_address_formats' ) );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'formatted_address_replacements' ), 1, 2 );
		add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'order_formatted_billing_address' ), 1, 2 );
		add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'order_formatted_shipping_address' ), 1, 2 );
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'my_account_my_address_formatted_address' ), 1, 3 );
	}

	/**
	 * Load order custom data.
	 *
	 * @param  array $data Default WC_Order data.
	 *
	 * @return array       Custom WC_Order data.
	 */
	public function load_order_data( $data ) {

		// Billing
		$data['billing_persontype']    = '';
		$data['billing_cpf']           = '';
		$data['billing_rg']            = '';
		$data['billing_cnpj']          = '';
		$data['billing_ie']            = '';
		$data['billing_birthdate']     = '';
		$data['billing_sex']           = '';
		$data['billing_number']        = '';
		$data['billing_neighborhood']  = '';
		$data['billing_cellphone']     = '';

		// Shipping
		$data['shipping_number']       = '';
		$data['shipping_neighborhood'] = '';

		return $data;
	}

	/**
	 * Register and enqueues public-facing style sheet and JavaScript files.
	 */
	public function enqueue_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'jquery-maskedinput', plugins_url( 'assets/js/jquery-maskedinput/jquery.maskedinput' . $suffix . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), '1.4.1', true );

		wp_register_script( 'mailcheck', plugins_url( 'assets/js/mailcheck/mailcheck' . $suffix . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), '1.1.1', true );

		// Load scripts only in checkout.
		if ( is_checkout() || is_account_page() ) {

			// Get plugin settings.
			$settings = get_option( 'wcbcf_settings' );

			// Fix checkout fields.
			wp_enqueue_script( 'woocommerce-extra-checkout-fields-for-brazil-front', plugins_url( 'assets/js/frontend/frontend' . $suffix . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery', 'jquery-maskedinput', 'mailcheck' ), Extra_Checkout_Fields_For_Brazil::VERSION, true );
			wp_localize_script(
				'woocommerce-extra-checkout-fields-for-brazil-front',
				'wcbcf_public_params',
				array(
					'state'           => esc_js( __( 'State', 'woocommerce-extra-checkout-fields-for-brazil' ) ),
					'required'        => esc_js( __( 'required', 'woocommerce-extra-checkout-fields-for-brazil' ) ),
					'mailcheck'       => isset( $settings['mailcheck'] ) ? 'yes' : 'no',
					'maskedinput'     => isset( $settings['maskedinput'] ) ? 'yes' : 'no',
					'addresscomplete' => isset( $settings['addresscomplete'] ) ? 'yes' : 'no',
					'person_type'     => absint( $settings['person_type'] ),
					'only_brazil'     => isset( $settings['only_brazil'] ) ? 'yes' : 'no'
				)
			);
		}
	}

	/**
	 * New checkout billing fields.
	 *
	 * @param  array $fields Default fields.
	 *
	 * @return array         New fields.
	 */
	public function checkout_billing_fields( $fields ) {

		$new_fields = array();

		// Get plugin settings.
		$settings = get_option( 'wcbcf_settings' );

		// Billing First Name.
		$new_fields['billing_first_name'] = array(
			'label'       => __( 'First Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'First Name', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first' ),
			'required'    => true
		);

		// Billing Last Name.
		$new_fields['billing_last_name'] = array(
			'label'       => __( 'Last Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Last Name', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last' ),
			'clear'       => true,
			'required'    => true
		);

		if ( 0 != $settings['person_type'] ) {

			// Billing Person Type.
			if ( 1 == $settings['person_type'] ) {
				$new_fields['billing_persontype'] = array(
					'type'     => 'select',
					'label'    => __( 'Person type', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'class'    => array( 'form-row-wide', 'person-type-field' ),
					'required' => false,
					'options'  => array(
						'0' => __( 'Select', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'1' => __( 'Individuals', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'2' => __( 'Legal Person', 'woocommerce-extra-checkout-fields-for-brazil' )
					)
				);
			}

			if ( 1 == $settings['person_type'] || 2 == $settings['person_type'] ) {
				if ( isset( $settings['rg'] ) ) {
					// Billing CPF.
					$new_fields['billing_cpf'] = array(
						'label'       => __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'placeholder' => _x( 'CPF', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'class'       => array( 'form-row-first', 'person-type-field' ),
						'required'    => false
					);

					// Billing RG.
					$new_fields['billing_rg'] = array(
						'label'       => __( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'placeholder' => _x( 'RG', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'class'       => array( 'form-row-last', 'person-type-field' ),
						'required'    => false
					);
				} else {
					// Billing CPF.
					$new_fields['billing_cpf'] = array(
						'label'       => __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'placeholder' => _x( 'CPF', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'class'       => array( 'form-row-wide', 'person-type-field' ),
						'required'    => false
					);
				}
			}

			if ( 1 == $settings['person_type'] || 3 == $settings['person_type'] ) {
				// Billing Company.
				$new_fields['billing_company'] = array(
					'label'       => __( 'Company Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'placeholder' => _x( 'Company Name', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'class'       => array( 'form-row-wide', 'person-type-field' ),
					'required'    => false
				);

				// Billing State Registration.
				if ( isset( $settings['ie'] ) ) {
					// Billing CNPJ.
					$new_fields['billing_cnpj'] = array(
						'label'       => __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'placeholder' => _x( 'CNPJ', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'class'       => array( 'form-row-first', 'person-type-field' ),
						'required'    => false
					);

					$new_fields['billing_ie'] = array(
						'label'       => __( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'placeholder' => _x( 'State Registration', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'class'       => array( 'form-row-last', 'person-type-field' ),
						'required'    => false
					);
				} else {
					// Billing CNPJ.
					$new_fields['billing_cnpj'] = array(
						'label'       => __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'placeholder' => _x( 'CNPJ', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'class'       => array( 'form-row-wide', 'person-type-field' ),
						'required'    => false
					);
				}
			}

		} else {
			// Billing Company.
			$new_fields['billing_company'] = array(
				'label'       => __( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'placeholder' => _x( 'Company', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-wide' ),
				'required'    => false
			);
		}

		if ( isset( $settings['birthdate_sex'] ) ) {

			// Billing Birthdate.
			$new_fields['billing_birthdate'] = array(
				'label'       => __( 'Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'placeholder' => _x( 'Birthdate', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-first' ),
				'clear'       => false,
				'required'    => true
			);

			// Billing Sex.
			$new_fields['billing_sex'] = array(
				'type'        => 'select',
				'label'       => __( 'Sex', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-last' ),
				'clear'       => true,
				'required'    => true,
				'options'     => array(
					'0'                     => __( 'Select', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'Female', 'woocommerce-extra-checkout-fields-for-brazil' ) => __( 'Female', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'Male', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Male', 'woocommerce-extra-checkout-fields-for-brazil' )
				)
			);

		}

		// Billing Country.
		$new_fields['billing_country'] = array(
			'type'        => 'country',
			'label'       => __( 'Country', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Country', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first', 'update_totals_on_change', 'address-field' ),
			'clear'       => false,
			'required'    => true,
		);

		// Billing Post Code.
		$new_fields['billing_postcode'] = array(
			'label'       => __( 'Post Code', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Post Code', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last', 'update_totals_on_change', 'address-field' ),
			'clear'       => true,
			'required'    => true
		);

		// Billing Anddress 01.
		$new_fields['billing_address_1'] = array(
			'label'       => __( 'Address', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Address', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first', 'address-field' ),
			'required'    => true
		);

		// Billing Number.
		$new_fields['billing_number'] = array(
			'label'       => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Number', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last', 'address-field' ),
			'clear'       => true,
			'required'    => true
		);

		// Billing Anddress 02.
		$new_fields['billing_address_2'] = array(
			'label'       => __( 'Address line 2', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Address line 2', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first', 'address-field' )
		);

		// Billing Neighborhood.
		$new_fields['billing_neighborhood'] = array(
			'label'       => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Neighborhood', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last', 'address-field' ),
			'clear'       => true,
		);

		// Billing City.
		$new_fields['billing_city'] = array(
			'label'       => __( 'City', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'City', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first', 'address-field' ),
			'required'    => true
		);

		// Billing State.
		$new_fields['billing_state'] = array(
			'type'        => 'state',
			'label'       => __( 'State', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'State', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last', 'address-field' ),
			'clear'       => true,
			'required'    => true
		);

		if ( isset( $settings['cell_phone'] ) ) {

			// Billing Phone.
			$new_fields['billing_phone'] = array(
				'label'       => __( 'Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'placeholder' => _x( 'Phone', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-first' ),
				'required'    => true
			);

			// Billing Cell Phone.
			$new_fields['billing_cellphone'] = array(
				'label'       => __( 'Cell Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'placeholder' => _x( 'Cell Phone', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-last' ),
				'clear'       => true
			);

			// Billing Email.
			$new_fields['billing_email'] = array(
				'label'       => __( 'Email', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'placeholder' => _x( 'Email', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-wide' ),
				'validate'    => array( 'email' ),
				'clear'       => true,
				'required'    => true
			);

		} else {

			// Billing Phone.
			$new_fields['billing_phone'] = array(
				'label'       => __( 'Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'placeholder' => _x( 'Phone', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-wide' ),
				'required'    => true
			);

			// Billing Email.
			$new_fields['billing_email'] = array(
				'label'       => __( 'Email', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'placeholder' => _x( 'Email', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-wide' ),
				'required'    => true
			);

		}

		return apply_filters( 'wcbcf_billing_fields', $new_fields );
	}

	/**
	 * New checkout shipping fields
	 *
	 * @param  array $fields Default fields.
	 *
	 * @return array         New fields.
	 */
	public function checkout_shipping_fields( $fields ) {

		$new_fields = array();

		// Get plugin settings.
		$settings = get_option( 'wcbcf_settings' );

		// Shipping First Name.
		$new_fields['shipping_first_name'] = array(
			'label'       => __( 'First Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'First Name', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first' ),
			'required'    => true
		);

		// Shipping Last Name.
		$new_fields['shipping_last_name'] = array(
			'label'       => __( 'Last Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Last Name', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last' ),
			'clear'       => true,
			'required'    => true
		);

		// Shipping Company.
		$new_fields['shipping_company'] = array(
			'label'       => __( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Company', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-wide' )
		);

		// Shipping Country.
		$new_fields['shipping_country'] = array(
			'type'        => 'country',
			'label'       => __( 'Country', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Country', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first', 'update_totals_on_change', 'address-field' ),
			'required'    => true
		);

		// Shipping Post Code.
		$new_fields['shipping_postcode'] = array(
			'label'       => __( 'Post Code', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Post Code', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last', 'update_totals_on_change', 'address-field' ),
			'clear'       => true,
			'required'    => true
		);

		// Shipping Anddress 01.
		$new_fields['shipping_address_1'] = array(
			'label'       => __( 'Address', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Address', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first', 'address-field' ),
			'required'    => true
		);

		// Shipping Number.
		$new_fields['shipping_number'] = array(
			'label'       => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Number', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last', 'address-field' ),
			'clear'       => true,
			'required'    => true
		);

		// Shipping Anddress 02.
		$new_fields['shipping_address_2'] = array(
			'label'       => __( 'Address line 2', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Address line 2', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first', 'address-field' )
		);

		// Shipping Neighborhood.
		$new_fields['shipping_neighborhood'] = array(
			'label'       => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Neighborhood', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last', 'address-field' ),
			'clear'       => true
		);

		// Shipping City.
		$new_fields['shipping_city'] = array(
			'label'       => __( 'City', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'City', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first', 'address-field' ),
			'required'    => true
		);

		// Shipping State.
		$new_fields['shipping_state'] = array(
			'type'        => 'state',
			'label'       => __( 'State', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'State', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last', 'address-field' ),
			'clear'       => true,
			'required'    => true
		);

		return apply_filters( 'wcbcf_shipping_fields', $new_fields );
	}

	/**
	 * Valid checkout fields.
	 *
	 * @return string Displays the error message.
	 */
	public function valid_checkout_fields() {

		// Get plugin settings.
		$settings           = get_option( 'wcbcf_settings' );
		$only_brazil        = isset( $settings['only_brazil'] ) ? true : false;
		$billing_persontype = isset( $_POST['billing_persontype'] ) ? $_POST['billing_persontype'] : 0;

		if ( $only_brazil && 'BR' != $_POST['billing_country'] || 0 == $settings['person_type'] ) {
			return;
		}

		if ( 0 == $billing_persontype && 1 == $settings['person_type'] ) {
			wc_add_notice( sprintf( '<strong>%s</strong> %s.', __( 'Person type', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is a required field', 'woocommerce-extra-checkout-fields-for-brazil' ) ), 'error' );
		} else {

			// Check CPF.
			if ( ( 1 == $settings['person_type'] && 1 == $billing_persontype ) || 2 == $settings['person_type'] ) {
				if ( empty( $_POST['billing_cpf'] ) ) {
					wc_add_notice( sprintf( '<strong>%s</strong> %s.', __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is a required field', 'woocommerce-extra-checkout-fields-for-brazil' ) ), 'error' );
				}

				if ( isset( $settings['validate_cpf'] ) && ! empty( $_POST['billing_cpf'] ) && ! Extra_Checkout_Fields_For_Brazil_Formatting::is_cpf( $_POST['billing_cpf'] ) ) {
					wc_add_notice( sprintf( '<strong>%s</strong> %s.', __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is not valid', 'woocommerce-extra-checkout-fields-for-brazil' ) ), 'error' );
				}

				if ( isset( $settings['rg'] ) && empty( $_POST['billing_rg'] ) ) {
					wc_add_notice( sprintf( '<strong>%s</strong> %s.', __( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is a required field', 'woocommerce-extra-checkout-fields-for-brazil' ) ), 'error' );
				}
			}

			// Check Company and CPNJ.
			if ( ( 1 == $settings['person_type'] && 2 == $billing_persontype ) || 3 == $settings['person_type'] ) {
				if ( empty( $_POST['billing_company'] ) ) {
					wc_add_notice( sprintf( '<strong>%s</strong> %s.', __( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is a required field', 'woocommerce-extra-checkout-fields-for-brazil' ) ), 'error' );
				}

				if ( empty( $_POST['billing_cnpj'] ) ) {
					wc_add_notice( sprintf( '<strong>%s</strong> %s.', __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is a required field', 'woocommerce-extra-checkout-fields-for-brazil' ) ), 'error' );
				}

				if ( isset( $settings['validate_cnpj'] ) && ! empty( $_POST['billing_cnpj'] ) && ! Extra_Checkout_Fields_For_Brazil_Formatting::is_cnpj( $_POST['billing_cnpj'] ) ) {
					wc_add_notice( sprintf( '<strong>%s</strong> %s.', __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is not valid', 'woocommerce-extra-checkout-fields-for-brazil' ) ), 'error' );
				}

				if ( isset( $settings['ie'] ) && empty( $_POST['billing_ie'] ) ) {
					wc_add_notice( sprintf( '<strong>%s</strong> %s.', __( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is a required field', 'woocommerce-extra-checkout-fields-for-brazil' ) ), 'error' );
				}
			}
		}
	}

	/**
	 * Custom country address formats.
	 *
	 * @param  array $formats Defaul formats.
	 *
	 * @return array          New BR format.
	 */
	public function localisation_address_formats( $formats ) {
		$formats['BR'] = "{name}\n{address_1}, {number}\n{address_2}\n{neighborhood}\n{city}\n{state}\n{postcode}\n{country}";

		return $formats;
	}

	/**
	 * Custom country address format.
	 *
	 * @param  array $replacements Default replacements.
	 * @param  array $args         Arguments to replace.
	 *
	 * @return array               New replacements.
	 */
	public function formatted_address_replacements( $replacements, $args ) {
		extract( $args );

		$replacements['{number}']       = $number;
		$replacements['{neighborhood}'] = $neighborhood;

		return $replacements;
	}

	/**
	 * Custom order formatted billing address.
	 *
	 * @param  array $address Default address.
	 * @param  object $order  Order data.
	 *
	 * @return array          New address format.
	 */
	public function order_formatted_billing_address( $address, $order ) {
		$address['number']       = $order->billing_number;
		$address['neighborhood'] = $order->billing_neighborhood;

		return $address;
	}

	/**
	 * Custom order formatted shipping address.
	 *
	 * @param  array $address Default address.
	 * @param  object $order  Order data.
	 *
	 * @return array          New address format.
	 */
	public function order_formatted_shipping_address( $address, $order ) {
		$address['number']       = $order->shipping_number;
		$address['neighborhood'] = $order->shipping_neighborhood;

		return $address;
	}

	/**
	 * Custom my address formatted address.
	 *
	 * @param  array $address   Default address.
	 * @param  int $customer_id Customer ID.
	 * @param  string $name     Field name (billing or shipping).
	 *
	 * @return array            New address format.
	 */
	public function my_account_my_address_formatted_address( $address, $customer_id, $name ) {
		$address['number']       = get_user_meta( $customer_id, $name . '_number', true );
		$address['neighborhood'] = get_user_meta( $customer_id, $name . '_neighborhood', true );

		return $address;
	}
}

new Extra_Checkout_Fields_For_Brazil_Front_End();
