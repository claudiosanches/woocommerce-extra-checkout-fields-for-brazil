<?php
/**
 * Extra checkout fields frontend actions.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extra_Checkout_Fields_For_Brazil_Front_End class.
 */
class Extra_Checkout_Fields_For_Brazil_Front_End {

	/**
	 * Initialize the front-end actions.
	 */
	public function __construct() {
		// Load public-facing scripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'woocommerce_after_edit_account_address_form', array( $this, 'load_scripts' ) );
		add_action( 'woocommerce_after_checkout_form', array( $this, 'load_scripts' ) );

		// New checkout fields.
		add_filter( 'woocommerce_billing_fields', array( $this, 'checkout_billing_fields' ), 10 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'checkout_shipping_fields' ), 10 );
		add_filter( 'woocommerce_get_country_locale', array( $this, 'address_fields_priority' ), 10 );

		// Valid checkout fields.
		add_action( 'woocommerce_checkout_process', array( $this, 'valid_checkout_fields' ), 10 );

		// Custom address format.
		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'localisation_address_formats' ) );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'formatted_address_replacements' ), 1, 2 );
		add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'order_formatted_billing_address' ), 1, 2 );
		add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'order_formatted_shipping_address' ), 1, 2 );
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'my_account_my_address_formatted_address' ), 1, 3 );

		// Orders.
		add_filter( 'woocommerce_get_order_address', array( $this, 'order_address' ), 10, 3 );
	}

	/**
	 * Register scripts.
	 */
	public function enqueue_scripts() {
        $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        wp_register_style( 'woocommerce-extra-checkout-fields-for-brazil-front', plugins_url( 'assets/css/frontend/frontend.css', plugin_dir_path( __FILE__ ) ), array(), Extra_Checkout_Fields_For_Brazil::VERSION, 'all' );

		wp_register_script( 'jquery-mask', plugins_url( 'assets/js/jquery.mask/jquery.mask' . $suffix . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), '1.14.10', true );

		wp_register_script( 'mailcheck', plugins_url( 'assets/js/mailcheck/mailcheck' . $suffix . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), '1.1.1', true );

		wp_register_script( 'woocommerce-extra-checkout-fields-for-brazil-front', plugins_url( 'assets/js/frontend/frontend' . $suffix . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery', 'jquery-mask', 'mailcheck' ), Extra_Checkout_Fields_For_Brazil::VERSION, true );

		$settings = get_option( 'wcbcf_settings' );
		$autofill = isset( $settings['addresscomplete'] ) ? 'yes' : 'no';
		wp_localize_script(
			'woocommerce-extra-checkout-fields-for-brazil-front',
			'wcbcf_public_params',
			array(
				'state'              => esc_js( __( 'State', 'woocommerce-extra-checkout-fields-for-brazil' ) ),
				'required'           => esc_js( __( 'required', 'woocommerce-extra-checkout-fields-for-brazil' ) ),
				'mailcheck'          => isset( $settings['mailcheck'] ) ? 'yes' : 'no',
				'maskedinput'        => isset( $settings['maskedinput'] ) ? 'yes' : 'no',
				'addresscomplete'    => apply_filters( 'woocommerce_correios_enable_autofill_addresses', false ) ? false : $autofill,
				'person_type'        => absint( $settings['person_type'] ),
				'only_brazil'        => isset( $settings['only_brazil'] ) ? 'yes' : 'no',
				'sort_state_country' => version_compare( WC_VERSION, '3.0', '>=' ),
			)
		);
	}

	/**
	 * Load scripts.
	 */
	public function load_scripts() {
		wp_enqueue_script( 'woocommerce-extra-checkout-fields-for-brazil-front' );
		wp_enqueue_style( 'woocommerce-extra-checkout-fields-for-brazil-front' );
	}

	/**
	 * New checkout billing fields.
	 *
	 * @param  array $fields Default fields.
	 *
	 * @return array
	 */
	public function checkout_billing_fields( $fields ) {
		$new_fields = array();

		// Get plugin settings.
		$settings    = get_option( 'wcbcf_settings' );
		$person_type = intval( $settings['person_type'] );

		if ( isset( $fields['billing_first_name'] ) ) {
			$new_fields['billing_first_name'] = $fields['billing_first_name'];
			$new_fields['billing_first_name']['class'] = array( 'form-row-first' );
		}

		if ( isset( $fields['billing_last_name'] ) ) {
			$new_fields['billing_last_name'] = $fields['billing_last_name'];
			$new_fields['billing_last_name']['class'] = array( 'form-row-last' );
		}

		if ( 0 !== $person_type ) {
			if ( 1 === $person_type ) {
				$new_fields['billing_persontype'] = array(
					'type'        => 'select',
					'label'       => __( 'Person type', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'class'       => array( 'form-row-wide', 'person-type-field' ),
					'input_class' => array( 'wc-ecfb-select' ),
					'required'    => false,
					'options'     => array(
						'1' => __( 'Individuals', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'2' => __( 'Legal Person', 'woocommerce-extra-checkout-fields-for-brazil' ),
					),
					'priority'    => 22,
				);
			}

			if ( 1 === $person_type || 2 === $person_type ) {
				if ( isset( $settings['rg'] ) ) {
					$new_fields['billing_cpf'] = array(
						'label'    => __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'class'    => array( 'form-row-first', 'person-type-field' ),
						'required' => false,
						'type'     => 'tel',
						'priority' => 23,
					);

					$new_fields['billing_rg'] = array(
						'label'    => __( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'class'    => array( 'form-row-last', 'person-type-field' ),
						'required' => false,
						'priority' => 24,
					);
				} else {
					$new_fields['billing_cpf'] = array(
						'label'    => __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'class'    => array( 'form-row-wide', 'person-type-field' ),
						'required' => false,
						'type'     => 'tel',
						'priority' => 23,
					);
				}
			}

			if ( 1 === $person_type || 3 === $person_type ) {
				if ( isset( $fields['billing_company'] ) ) {
					$new_fields['billing_company'] = $fields['billing_company'];
					$new_fields['billing_company']['class'] = array( 'form-row-wide' );
					$new_fields['billing_company']['clear'] = true;
					$new_fields['billing_company']['priority'] = 25;
				}

				if ( isset( $settings['ie'] ) ) {
					$new_fields['billing_cnpj'] = array(
						'label'    => __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'class'    => array( 'form-row-first', 'person-type-field' ),
						'required' => false,
						'type'     => 'tel',
						'priority' => 26,
					);

					$new_fields['billing_ie'] = array(
						'label'    => __( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'class'    => array( 'form-row-last', 'person-type-field' ),
						'required' => false,
						'priority' => 27,
					);
				} else {
					$new_fields['billing_cnpj'] = array(
						'label'    => __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'class'    => array( 'form-row-wide', 'person-type-field' ),
						'required' => false,
						'type'     => 'tel',
						'priority' => 26,
					);
				}
			}
		} else {
			if ( isset( $fields['billing_company'] ) ) {
				$new_fields['billing_company'] = $fields['billing_company'];
				$new_fields['billing_company']['class'] = array( 'form-row-wide' );
				$new_fields['billing_company']['clear'] = true;
			}
		} // End if().

		if ( isset( $settings['birthdate_sex'] ) ) {
			$new_fields['billing_birthdate'] = array(
				'label'    => __( 'Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'    => array( 'form-row-first' ),
				'clear'    => false,
				'required' => true,
				'priority' => 31,
			);

			$new_fields['billing_sex'] = array(
				'type'        => 'select',
				'label'       => __( 'Sex', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-last' ),
				'input_class' => array( 'wc-ecfb-select' ),
				'clear'       => true,
				'required'    => true,
				'options'     => array(
					'' => __( 'Select', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'Female', 'woocommerce-extra-checkout-fields-for-brazil' ) => __( 'Female', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'Male', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Male', 'woocommerce-extra-checkout-fields-for-brazil' ),
				),
				'priority'    => 32,
			);
		}

		if ( isset( $fields['billing_country'] ) ) {
			$new_fields['billing_country'] = $fields['billing_country'];
			$new_fields['billing_country']['class'] = array( 'form-row-wide', 'address-field', 'update_totals_on_change' );
		}

		if ( isset( $fields['billing_postcode'] ) ) {
			$new_fields['billing_postcode'] = $fields['billing_postcode'];
			$new_fields['billing_postcode']['class'] = array( 'form-row-first', 'address-field' );
			$new_fields['billing_postcode']['priority'] = 45;
		}

		if ( isset( $fields['billing_address_1'] ) ) {
			$new_fields['billing_address_1'] = $fields['billing_address_1'];
			$new_fields['billing_address_1']['class'] = array( 'form-row-last', 'address-field' );
		}

		$new_fields['billing_number'] = array(
			'label'    => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'    => array( 'form-row-first', 'address-field' ),
			'clear'    => true,
			'required' => true,
			'priority' => 55,
		);

		if ( isset( $fields['billing_address_2'] ) ) {
			$new_fields['billing_address_2'] = $fields['billing_address_2'];
			$new_fields['billing_address_2']['label'] = __( 'Address line 2', 'woocommerce-extra-checkout-fields-for-brazil' );
			$new_fields['billing_address_2']['class'] = array( 'form-row-last', 'address-field' );
		}

		$new_fields['billing_neighborhood'] = array(
			'label'    => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'    => array( 'form-row-first', 'address-field' ),
			'clear'    => true,
			'priority' => 65,
		);

		if ( isset( $fields['billing_city'] ) ) {
			$new_fields['billing_city'] = $fields['billing_city'];
			$new_fields['billing_city']['class'] = array( 'form-row-last', 'address-field' );
		}

		if ( isset( $fields['billing_state'] ) ) {
			$new_fields['billing_state'] = $fields['billing_state'];
			$new_fields['billing_state']['class'] = array( 'form-row-wide', 'address-field' );
			$new_fields['billing_state']['clear'] = true;
		}

		if ( isset( $settings['cell_phone'] ) ) {
			if ( isset( $fields['billing_phone'] ) ) {
				$new_fields['billing_phone'] = $fields['billing_phone'];
				$new_fields['billing_phone']['class'] = array( 'form-row-first' );
				$new_fields['billing_phone']['clear'] = false;
			}

			$new_fields['billing_cellphone'] = array(
				'label'    => __( 'Cell Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'    => array( 'form-row-last' ),
				'clear'    => true,
				'priority' => 105,
			);

			if ( isset( $fields['billing_email'] ) ) {
				$new_fields['billing_email'] = $fields['billing_email'];
				$new_fields['billing_email']['class'] = array( 'form-row-wide' );
				$new_fields['billing_email']['clear'] = true;
				$new_fields['billing_email']['type'] = 'email';
			}
		} else {
			if ( isset( $fields['billing_phone'] ) ) {
				$new_fields['billing_phone'] = $fields['billing_phone'];
				$new_fields['billing_phone']['class'] = array( 'form-row-wide' );
				$new_fields['billing_phone']['clear'] = true;
			}

			if ( isset( $fields['billing_email'] ) ) {
				$new_fields['billing_email'] = $fields['billing_email'];
				$new_fields['billing_email']['class'] = array( 'form-row-wide' );
				$new_fields['billing_email']['clear'] = true;
				$new_fields['billing_email']['type'] = 'email';
			}
		}

		return apply_filters( 'wcbcf_billing_fields', $new_fields );
	}

	/**
	 * New checkout shipping fields
	 *
	 * @param  array $fields Default fields.
	 *
	 * @return array
	 */
	public function checkout_shipping_fields( $fields ) {
		$new_fields = array();

		if ( isset( $fields['shipping_first_name'] ) ) {
			$new_fields['shipping_first_name'] = $fields['shipping_first_name'];
			$new_fields['shipping_first_name']['class'] = array( 'form-row-first' );
		}

		if ( isset( $fields['shipping_last_name'] ) ) {
			$new_fields['shipping_last_name'] = $fields['shipping_last_name'];
			$new_fields['shipping_last_name']['class'] = array( 'form-row-last' );
		}

		if ( isset( $fields['shipping_company'] ) ) {
			$new_fields['shipping_company'] = $fields['shipping_company'];
			$new_fields['shipping_company']['class'] = array( 'form-row-wide' );
			$new_fields['shipping_company']['clear'] = true;
		}

		if ( isset( $fields['shipping_country'] ) ) {
			$new_fields['shipping_country'] = $fields['shipping_country'];
			$new_fields['shipping_country']['class'] = array( 'form-row-wide', 'address-field', 'update_totals_on_change' );
		}

		if ( isset( $fields['shipping_postcode'] ) ) {
			$new_fields['shipping_postcode'] = $fields['shipping_postcode'];
			$new_fields['shipping_postcode']['class'] = array( 'form-row-first', 'address-field' );
			$new_fields['shipping_postcode']['priority'] = 45;
		}

		if ( isset( $fields['shipping_address_1'] ) ) {
			$new_fields['shipping_address_1'] = $fields['shipping_address_1'];
			$new_fields['shipping_address_1']['class'] = array( 'form-row-last', 'address-field' );
		}

		$new_fields['shipping_number'] = array(
			'label'    => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'    => array( 'form-row-first', 'address-field' ),
			'clear'    => true,
			'required' => true,
			'priority' => 55,
		);

		if ( isset( $fields['shipping_address_2'] ) ) {
			$new_fields['shipping_address_2'] = $fields['shipping_address_2'];
			$new_fields['shipping_address_2']['label'] = __( 'Address line 2', 'woocommerce-extra-checkout-fields-for-brazil' );
			$new_fields['shipping_address_2']['class'] = array( 'form-row-last', 'address-field' );
		}

		$new_fields['shipping_neighborhood'] = array(
			'label' => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class' => array( 'form-row-first', 'address-field' ),
			'clear' => true,
			'priority' => 65,
		);

		if ( isset( $fields['shipping_city'] ) ) {
			$new_fields['shipping_city'] = $fields['shipping_city'];
			$new_fields['shipping_city']['class'] = array( 'form-row-last', 'address-field' );
		}

		if ( isset( $fields['shipping_state'] ) ) {
			$new_fields['shipping_state'] = $fields['shipping_state'];
			$new_fields['shipping_state']['class'] = array( 'form-row-wide', 'address-field' );
			$new_fields['shipping_state']['clear'] = true;
		}

		return apply_filters( 'wcbcf_shipping_fields', $new_fields );
	}

	/**
	 * Update address fields priority.
	 *
	 * @param  array $locales Default WooCommerce locales.
	 * @return array
	 */
	public function address_fields_priority( $locales ) {
		$locales['BR'] = array(
			'postcode' => array(
				'priority' => 45,
			),
		);

		return $locales;
	}

	/**
	 * Valid checkout fields.
	 *
	 * @return string Displays the error message.
	 */
	public function valid_checkout_fields() {
		if ( apply_filters( 'wcbcf_disable_checkout_validation', false ) ) {
			return;
		}

		// Get plugin settings.
		$settings           = get_option( 'wcbcf_settings' );
		$person_type        = intval( $settings['person_type'] );
		$only_brazil        = isset( $settings['only_brazil'] ) ? true : false;
		$billing_persontype = isset( $_POST['billing_persontype'] ) ? intval( wp_unslash( $_POST['billing_persontype'] ) ) : 0;

		if ( $only_brazil && 'BR' !== wp_unslash( $_POST['billing_country'] ) || 0 === $person_type ) {
			return;
		}

		if ( 0 === $billing_persontype && 1 === $person_type ) {
			wc_add_notice( sprintf( '<strong>%s</strong> %s.', __( 'Person type', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is a required field', 'woocommerce-extra-checkout-fields-for-brazil' ) ), 'error' );
		} else {

			// Check CPF.
			if ( ( 1 === $person_type && 1 === $billing_persontype ) || 2 === $person_type ) {
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
			if ( ( 1 === $person_type && 2 === $billing_persontype ) || 3 === $person_type ) {
				if ( empty( $_POST['billing_company'] ) ) {
					wc_add_notice( sprintf( '<strong>%s</strong> %s.', __( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is a required field', 'woocommerce-extra-checkout-fields-for-brazil' ) ), 'error' );
				}

				if ( empty( $_POST['billing_cnpj'] ) ) {
					wc_add_notice( sprintf( '<strong>%s</strong> %s.', __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is a required field', 'woocommerce-extra-checkout-fields-for-brazil' ) ), 'error' );
				}

				if ( isset( $settings['validate_cnpj'] ) && ! empty( $_POST['billing_cnpj'] ) && ! Extra_Checkout_Fields_For_Brazil_Formatting::is_cnpj( wp_unslash( $_POST['billing_cnpj'] ) ) ) {
					wc_add_notice( sprintf( '<strong>%s</strong> %s.', __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is not valid', 'woocommerce-extra-checkout-fields-for-brazil' ) ), 'error' );
				}

				if ( isset( $settings['ie'] ) && empty( $_POST['billing_ie'] ) ) {
					wc_add_notice( sprintf( '<strong>%s</strong> %s.', __( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is a required field', 'woocommerce-extra-checkout-fields-for-brazil' ) ), 'error' );
				}
			}
		} // End if().
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
		$args = wp_parse_args( $args, array(
			'number'       => '',
			'neighborhood' => '',
		) );

		$replacements['{number}']       = $args['number'];
		$replacements['{neighborhood}'] = $args['neighborhood'];

		return $replacements;
	}

	/**
	 * Custom order formatted billing address.
	 *
	 * @param  array  $address Default address.
	 * @param  object $order   Order data.
	 *
	 * @return array           New address format.
	 */
	public function order_formatted_billing_address( $address, $order ) {
		// WooCommerce 3.0 or later.
		if ( method_exists( $order, 'get_meta' ) ) {
			$address['number']       = $order->get_meta( '_billing_number' );
			$address['neighborhood'] = $order->get_meta( '_billing_neighborhood' );
		} else {
			$address['number']       = $order->billing_number;
			$address['neighborhood'] = $order->billing_neighborhood;
		}

		return $address;
	}

	/**
	 * Custom order formatted shipping address.
	 *
	 * @param  array  $address Default address.
	 * @param  object $order   Order data.
	 *
	 * @return array           New address format.
	 */
	public function order_formatted_shipping_address( $address, $order ) {
		if ( ! is_array( $address ) ) {
			return $address;
		}

		// WooCommerce 3.0 or later.
		if ( method_exists( $order, 'get_meta' ) ) {
			$address['number']       = $order->get_meta( '_shipping_number' );
			$address['neighborhood'] = $order->get_meta( '_shipping_neighborhood' );
		} else {
			$address['number']       = $order->shipping_number;
			$address['neighborhood'] = $order->shipping_neighborhood;
		}

		return $address;
	}

	/**
	 * Custom my address formatted address.
	 *
	 * @param  array  $address     Default address.
	 * @param  int    $customer_id Customer ID.
	 * @param  string $name        Field name (billing or shipping).
	 *
	 * @return array               New address format.
	 */
	public function my_account_my_address_formatted_address( $address, $customer_id, $name ) {
		$address['number']       = get_user_meta( $customer_id, $name . '_number', true );
		$address['neighborhood'] = get_user_meta( $customer_id, $name . '_neighborhood', true );

		return $address;
	}

	/**
	 * Order address.
	 *
	 * @param  array    $address Address data.
	 * @param  string   $type    Address type.
	 * @param  WC_Order $order   Order object.
	 * @return array
	 */
	public function order_address( $address, $type, $order ) {
		$number       = $type . '_number';
		$neighborhood = $type . '_neighborhood';

		// WooCommerce 3.0 or later.
		if ( method_exists( $order, 'get_meta' ) ) {
			$address['number']       = $order->get_meta( '_' . $number );
			$address['neighborhood'] = $order->get_meta( '_' . $neighborhood );
		} else {
			$address['number']       = $order->$number;
			$address['neighborhood'] = $order->$neighborhood;
		}

		return $address;
	}
}

new Extra_Checkout_Fields_For_Brazil_Front_End();
