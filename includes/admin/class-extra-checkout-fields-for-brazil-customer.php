<?php
/**
 * Extra checkout fields customer admin.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Admin/Customer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extra_Checkout_Fields_For_Brazil_Customer class.
 */
class Extra_Checkout_Fields_For_Brazil_Customer {

	/**
	 * Initialize the customer actions.
	 */
	public function __construct() {
		add_filter( 'woocommerce_customer_meta_fields', array( $this, 'customer_meta_fields' ) );
		add_filter( 'woocommerce_user_column_billing_address', array( $this, 'user_column_billing_address' ), 1, 2 );
		add_filter( 'woocommerce_user_column_shipping_address', array( $this, 'user_column_shipping_address' ), 1, 2 );
	}

	/**
	 * Custom user edit fields.
	 *
	 * @param  array $fields Default fields.
	 *
	 * @return array         Custom fields.
	 */
	public function customer_meta_fields( $fields ) {
		// Get plugin settings.
		$settings    = get_option( 'wcbcf_settings' );
		$person_type = intval( $settings['person_type'] );

		// Billing fields.
		$new_fields['billing']['title'] = __( 'Customer Billing Address', 'woocommerce-extra-checkout-fields-for-brazil' );
		$new_fields['billing']['fields']['billing_first_name'] = $fields['billing']['fields']['billing_first_name'];
		$new_fields['billing']['fields']['billing_last_name']  = $fields['billing']['fields']['billing_last_name'];

		if ( 0 !== $person_type ) {

			if ( 1 === $person_type || 2 === $person_type ) {
				$new_fields['billing']['fields']['billing_cpf'] = array(
					'label'       => __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'description' => '',
				);

				if ( isset( $settings['rg'] ) ) {
					$new_fields['billing']['fields']['billing_rg'] = array(
						'label'       => __( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'description' => '',
					);
				}
			}

			if ( 1 === $person_type || 3 === $person_type ) {
				$new_fields['billing']['fields']['billing_company'] = $fields['billing']['fields']['billing_company'];
				$new_fields['billing']['fields']['billing_cnpj'] = array(
					'label'       => __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'description' => '',
				);

				if ( isset( $settings['ie'] ) ) {
					$new_fields['billing']['fields']['billing_ie'] = array(
						'label'       => __( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'description' => '',
					);
				}
			}
		} else {
			$new_fields['billing']['fields']['billing_company'] = $fields['billing']['fields']['billing_company'];
		}

		if ( isset( $settings['birthdate_sex'] ) ) {
			$new_fields['billing']['fields']['billing_birthdate'] = array(
				'label'       => __( 'Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => '',
			);
			$new_fields['billing']['fields']['billing_sex'] = array(
				'label'       => __( 'Sex', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => '',
			);
		}

		$new_fields['billing']['fields']['billing_address_1'] = $fields['billing']['fields']['billing_address_1'];
		$new_fields['billing']['fields']['billing_number'] = array(
			'label'       => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => '',
		);
		$new_fields['billing']['fields']['billing_address_2'] = $fields['billing']['fields']['billing_address_2'];
		$new_fields['billing']['fields']['billing_neighborhood'] = array(
			'label'       => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => '',
		);
		$new_fields['billing']['fields']['billing_city']     = $fields['billing']['fields']['billing_city'];
		$new_fields['billing']['fields']['billing_postcode'] = $fields['billing']['fields']['billing_postcode'];
		$new_fields['billing']['fields']['billing_country']  = $fields['billing']['fields']['billing_country'];
		$new_fields['billing']['fields']['billing_state']    = $fields['billing']['fields']['billing_state'];
		$new_fields['billing']['fields']['billing_phone']    = $fields['billing']['fields']['billing_phone'];

		if ( isset( $settings['cell_phone'] ) ) {
			$new_fields['billing']['fields']['billing_cellphone'] = array(
				'label'       => __( 'Cell Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => '',
			);
		}

		$new_fields['billing']['fields']['billing_email'] = $fields['billing']['fields']['billing_email'];

		// Shipping fields.
		$new_fields['shipping']['title'] = __( 'Customer Shipping Address', 'woocommerce-extra-checkout-fields-for-brazil' );
		$new_fields['shipping']['fields']['shipping_first_name'] = $fields['shipping']['fields']['shipping_first_name'];
		$new_fields['shipping']['fields']['shipping_last_name']  = $fields['shipping']['fields']['shipping_last_name'];
		$new_fields['shipping']['fields']['shipping_company']    = $fields['shipping']['fields']['shipping_company'];
		$new_fields['shipping']['fields']['shipping_address_1']  = $fields['shipping']['fields']['shipping_address_1'];
		$new_fields['shipping']['fields']['shipping_number'] = array(
			'label'       => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => '',
		);
		$new_fields['shipping']['fields']['shipping_address_2']  = $fields['shipping']['fields']['shipping_address_2'];
		$new_fields['shipping']['fields']['shipping_neighborhood'] = array(
			'label'       => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => '',
		);
		$new_fields['shipping']['fields']['shipping_city']     = $fields['shipping']['fields']['shipping_city'];
		$new_fields['shipping']['fields']['shipping_postcode'] = $fields['shipping']['fields']['shipping_postcode'];
		$new_fields['shipping']['fields']['shipping_country']  = $fields['shipping']['fields']['shipping_country'];
		$new_fields['shipping']['fields']['shipping_state']    = $fields['shipping']['fields']['shipping_state'];

		$new_fields = apply_filters( 'wcbcf_customer_meta_fields', $new_fields );

		return $new_fields;
	}

	/**
	 * Custom user column billing address information.
	 *
	 * @param  array $address Default address.
	 * @param  int   $user_id User id.
	 *
	 * @return array          New address format.
	 */
	public function user_column_billing_address( $address, $user_id ) {
		$address['number']       = get_user_meta( $user_id, 'billing_number', true );
		$address['neighborhood'] = get_user_meta( $user_id, 'billing_neighborhood', true );

		return $address;
	}

	/**
	 * Custom user column shipping address information.
	 *
	 * @param  array $address Default address.
	 * @param  int   $user_id User id.
	 *
	 * @return array          New address format.
	 */
	public function user_column_shipping_address( $address, $user_id ) {
		$address['number']       = get_user_meta( $user_id, 'shipping_number', true );
		$address['neighborhood'] = get_user_meta( $user_id, 'shipping_neighborhood', true );

		return $address;
	}
}

new Extra_Checkout_Fields_For_Brazil_Customer();
