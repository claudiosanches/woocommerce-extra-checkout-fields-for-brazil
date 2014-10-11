<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Customer class.
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
		unset( $fields );

		// Get plugin settings.
		$settings = get_option( 'wcbcf_settings' );

		// Billing fields.
		$fields['billing']['title'] = __( 'Customer Billing Address', 'woocommerce-extra-checkout-fields-for-brazil' );
		$fields['billing']['fields']['billing_first_name'] = array(
			'label' => __( 'First name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['billing']['fields']['billing_last_name'] = array(
			'label' => __( 'Last name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);

		if ( 0 != $settings['person_type'] ) {

			if ( 1 == $settings['person_type'] || 2 == $settings['person_type'] ) {
				$fields['billing']['fields']['billing_cpf'] = array(
					'label' => __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'description' => ''
				);

				if ( isset( $settings['rg'] ) ) {
					$fields['billing']['fields']['billing_rg'] = array(
						'label' => __( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'description' => ''
					);
				}
			}

			if ( 1 == $settings['person_type'] || 3 == $settings['person_type'] ) {
				$fields['billing']['fields']['billing_company'] = array(
					'label' => __( 'Company Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'description' => ''
				);
				$fields['billing']['fields']['billing_cnpj'] = array(
					'label' => __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'description' => ''
				);

				if ( isset( $settings['ie'] ) ) {
					$fields['billing']['fields']['billing_ie'] = array(
						'label' => __( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'description' => ''
					);
				}
			}
		} else {
			$fields['billing']['fields']['billing_company'] = array(
				'label' => __( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => ''
			);
		}

		if ( isset( $settings['birthdate_sex'] ) ) {
			$fields['billing']['fields']['billing_birthdate'] = array(
				'label' => __( 'Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => ''
			);
			$fields['billing']['fields']['billing_sex'] = array(
				'label' => __( 'Sex', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => ''
			);
		}

		$fields['billing']['fields']['billing_country'] = array(
			'label' => __( 'Country', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => __( '2 letter Country code', 'woocommerce-extra-checkout-fields-for-brazil' )
		);
		$fields['billing']['fields']['billing_postcode'] = array(
			'label' => __( 'Postcode', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['billing']['fields']['billing_address_1'] = array(
			'label' => __( 'Address 1', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['billing']['fields']['billing_number'] = array(
			'label' => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['billing']['fields']['billing_address_2'] = array(
			'label' => __( 'Address 2', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['billing']['fields']['billing_neighborhood'] = array(
			'label' => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['billing']['fields']['billing_city'] = array(
			'label' => __( 'City', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['billing']['fields']['billing_state'] = array(
			'label' => __( 'State', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => __( 'State code', 'woocommerce-extra-checkout-fields-for-brazil' )
		);
		$fields['billing']['fields']['billing_phone'] = array(
			'label' => __( 'Telephone', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);

		if ( isset( $settings['cell_phone'] ) ) {
			$fields['billing']['fields']['billing_cellphone'] = array(
				'label' => __( 'Cell Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => ''
			);
		}

		$fields['billing']['fields']['billing_email'] = array(
			'label' => __( 'Email', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);

		// Shipping fields.
		$fields['shipping']['title'] = __( 'Customer Shipping Address', 'woocommerce-extra-checkout-fields-for-brazil' );
		$fields['shipping']['fields']['shipping_first_name'] = array(
			'label' => __( 'First name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['shipping']['fields']['shipping_last_name'] = array(
			'label' => __( 'Last name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['shipping']['fields']['shipping_company'] = array(
			'label' => __( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['shipping']['fields']['shipping_country'] = array(
			'label' => __( 'Country', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => __( '2 letter Country code', 'woocommerce-extra-checkout-fields-for-brazil' )
		);
		$fields['shipping']['fields']['shipping_postcode'] = array(
			'label' => __( 'Postcode', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['shipping']['fields']['shipping_address_1'] = array(
			'label' => __( 'Address 1', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['shipping']['fields']['shipping_number'] = array(
			'label' => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['shipping']['fields']['shipping_address_2'] = array(
			'label' => __( 'Address 2', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['shipping']['fields']['shipping_neighborhood'] = array(
			'label' => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['shipping']['fields']['shipping_city'] = array(
			'label' => __( 'City', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['shipping']['fields']['shipping_state'] = array(
			'label' => __( 'State', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => __( 'State code', 'woocommerce-extra-checkout-fields-for-brazil' )
		);

		$new_fields = apply_filters( 'wcbcf_customer_meta_fields', $fields );

		return $new_fields;
	}

	/**
	 * Custom user column billing address information.
	 *
	 * @param  array $address Default address.
	 * @param  int $user_id   User id.
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
	 * @param  int $user_id   User id.
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
