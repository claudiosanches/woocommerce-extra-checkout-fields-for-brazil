<?php
/**
 * Extra checkout fields order admin.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Admin/Order
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extra_Checkout_Fields_For_Brazil_Order class.
 */
class Extra_Checkout_Fields_For_Brazil_Order {

	/**
	 * Initialize the order actions.
	 */
	public function __construct() {
		add_filter( 'woocommerce_admin_billing_fields', array( $this, 'shop_order_billing_fields' ) );
		add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'shop_order_shipping_fields' ) );
		add_filter( 'woocommerce_ajax_get_customer_details', array( $this, 'ajax_get_customer_details' ), 10, 2 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'order_data_after_billing_address' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_custom_shop_data' ) );
	}

	/**
	 * Custom shop order billing fields.
	 *
	 * @param  array $data Default order billing fields.
	 *
	 * @return array       Custom order billing fields.
	 */
	public function shop_order_billing_fields( $data ) {
		// Get plugin settings.
		$settings    = get_option( 'wcbcf_settings' );
		$person_type = intval( $settings['person_type'] );

		$billing_data['first_name'] = $data['first_name'];
		$billing_data['last_name']  = $data['last_name'];

		if ( 0 !== $person_type ) {
			if ( 1 === $person_type ) {
				$billing_data['persontype'] = array(
					'type'    => 'select',
					'label'   => __( 'Person type', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'show'    => false,
					'options' => array(
						'0' => __( 'Select', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'1' => __( 'Individuals', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'2' => __( 'Legal Person', 'woocommerce-extra-checkout-fields-for-brazil' ),
					),
				);
			}

			if ( 1 === $person_type || 2 === $person_type ) {
				$billing_data['cpf'] = array(
					'label' => __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'show'  => false,
				);
				if ( isset( $settings['rg'] ) ) {
					$billing_data['rg'] = array(
						'label' => __( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'show'  => false,
					);
				}
			}

			if ( 1 === $person_type || 3 === $person_type ) {
				$billing_data['company'] = array(
					'label' => __( 'Company Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'show'  => false,
				);
				$billing_data['cnpj']    = array(
					'label' => __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'show'  => false,
				);
				if ( isset( $settings['ie'] ) ) {
					$billing_data['ie'] = array(
						'label' => __( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'show'  => false,
					);
				}
			}
		} else {
			$billing_data['company'] = array(
				'label' => __( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'show'  => false,
			);
		}

		if ( isset( $settings['birthdate'] ) ) {
			$billing_data['birthdate'] = array(
				'label' => __( 'Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'show'  => false,
			);
		}

		if ( isset( $settings['gender'] ) ) {
			$billing_data['gender'] = array(
				'label' => __( 'Gender', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'show'  => false,
			);
		}

		$billing_data['address_1']     = $data['address_1'];
		$billing_data['number']        = array(
			'label' => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false,
		);
		$billing_data['address_2']     = $data['address_2'];
		$billing_data['neighborhood']  = array(
			'label' => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false,
		);
		$billing_data['city']          = $data['city'];
		$billing_data['state']         = $data['state'];
		$billing_data['country']       = $data['country'];
		$billing_data['postcode']      = $data['postcode'];
		$billing_data['phone']         = $data['phone'];
		$billing_data['phone']['show'] = false;

		if ( isset( $settings['cell_phone'] ) ) {
			$billing_data['cellphone'] = array(
				'label' => __( 'Cell Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'show'  => false,
			);
		}

		$billing_data['email']         = $data['email'];
		$billing_data['email']['show'] = false;

		return apply_filters( 'wcbcf_admin_billing_fields', $billing_data );
	}

	/**
	 * Custom shop order shipping fields.
	 *
	 * @param  array $data Default order shipping fields.
	 *
	 * @return array       Custom order shipping fields.
	 */
	public function shop_order_shipping_fields( $data ) {
		$shipping_data['first_name']   = $data['first_name'];
		$shipping_data['last_name']    = $data['last_name'];
		$shipping_data['company']      = $data['company'];
		$shipping_data['address_1']    = $data['address_1'];
		$shipping_data['number']       = array(
			'label' => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false,
		);
		$shipping_data['address_2']    = $data['address_2'];
		$shipping_data['neighborhood'] = array(
			'label' => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false,
		);
		$shipping_data['city']         = $data['city'];
		$shipping_data['state']        = $data['state'];
		$shipping_data['country']      = $data['country'];
		$shipping_data['postcode']     = $data['postcode'];

		return apply_filters( 'wcbcf_admin_shipping_fields', $shipping_data );
	}

	/**
	 * Add custom fields in customer details ajax.
	 *
	 * @param  array $data Customer data.
	 * @return array
	 */
	public function customer_details_ajax( $data ) {
		if ( empty( $_POST['user_id'] ) || empty( $_POST['type_to_load'] ) ) {
			return $data;
		}

		$user_id      = absint( wp_unslash( $_POST['user_id'] ) );
		$type_to_load = sanitize_text_field( wp_unslash( $_POST['type_to_load'] ) );

		$custom_data = array(
			$type_to_load . '_number'       => get_user_meta( $user_id, $type_to_load . '_number', true ),
			$type_to_load . '_neighborhood' => get_user_meta( $user_id, $type_to_load . '_neighborhood', true ),
			$type_to_load . '_persontype'   => get_user_meta( $user_id, $type_to_load . '_persontype', true ),
			$type_to_load . '_cpf'          => get_user_meta( $user_id, $type_to_load . '_cpf', true ),
			$type_to_load . '_rg'           => get_user_meta( $user_id, $type_to_load . '_rg', true ),
			$type_to_load . '_cnpj'         => get_user_meta( $user_id, $type_to_load . '_cnpj', true ),
			$type_to_load . '_ie'           => get_user_meta( $user_id, $type_to_load . '_ie', true ),
			$type_to_load . '_birthdate'    => get_user_meta( $user_id, $type_to_load . '_birthdate', true ),
			$type_to_load . '_gender'       => get_user_meta( $user_id, $type_to_load . '_gender', true ),
			$type_to_load . '_cellphone'    => get_user_meta( $user_id, $type_to_load . '_cellphone', true ),
		);

		return array_merge( $data, $custom_data );
	}

	/**
	 * Get customer details.
	 *
	 * @param  array       $data     Customer data.
	 * @param  WC_Customer $customer Customer instance.
	 *
	 * @return array
	 */
	public function ajax_get_customer_details( $data, $customer ) {
		$data['billing']['number']        = $customer->get_meta( 'billing_number' );
		$data['billing']['neighborhood']  = $customer->get_meta( 'billing_neighborhood' );
		$data['billing']['persontype']    = $customer->get_meta( 'billing_persontype' );
		$data['billing']['cpf']           = $customer->get_meta( 'billing_cpf' );
		$data['billing']['rg']            = $customer->get_meta( 'billing_rg' );
		$data['billing']['cnpj']          = $customer->get_meta( 'billing_cnpj' );
		$data['billing']['ie']            = $customer->get_meta( 'billing_ie' );
		$data['billing']['birthdate']     = $customer->get_meta( 'billing_birthdate' );
		$data['billing']['gender']        = $customer->get_meta( 'billing_gender' );
		$data['billing']['cellphone']     = $customer->get_meta( 'billing_cellphone' );
		$data['shipping']['number']       = $customer->get_meta( 'shipping_number' );
		$data['shipping']['neighborhood'] = $customer->get_meta( 'shipping_neighborhood' );

		return $data;
	}

	/**
	 * Custom billing admin fields.
	 *
	 * @param object $order Order data.
	 */
	public function order_data_after_billing_address( $order ) {
		// Get plugin settings.
		$settings      = get_option( 'wcbcf_settings' );
		$person_type   = intval( $settings['person_type'] );
		$phone_label   = __( 'Phone', 'woocommerce-extra-checkout-fields-for-brazil' );
		$has_cellphone = '' !== $order->get_meta( '_billing_cellphone' );

		// Make sure that order don't have a phone, so we don't change the label of old orders.
		if ( '-1' === wc_get_var( $settings['cell_phone'], '0' ) && ! $has_cellphone ) {
			$phone_label = __( 'Cell Phone', 'woocommerce-extra-checkout-fields-for-brazil' );
		}

		include dirname( __FILE__ ) . '/views/html-order-billing-data.php';
	}

	/**
	 * Save custom shop data fields.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_custom_shop_data( $post_id ) {
		// Get plugin settings.
		$settings    = get_option( 'wcbcf_settings' );
		$person_type = intval( $settings['person_type'] );
		$order       = wc_get_order( $post_id );

		if ( isset( $_POST['_billing_number'] ) ) {
			$order->update_meta_data( '_billing_number', sanitize_text_field( wp_unslash( $_POST['_billing_number'] ) ) );
		}
		if ( isset( $_POST['_billing_neighborhood'] ) ) {
			$order->update_meta_data( '_billing_neighborhood', sanitize_text_field( wp_unslash( $_POST['_billing_neighborhood'] ) ) );
		}
		if ( isset( $_POST['_shipping_number'] ) ) {
			$order->update_meta_data( '_shipping_number', sanitize_text_field( wp_unslash( $_POST['_shipping_number'] ) ) );
		}
		if ( isset( $_POST['_shipping_neighborhood'] ) ) {
			$order->update_meta_data( '_shipping_neighborhood', sanitize_text_field( wp_unslash( $_POST['_shipping_neighborhood'] ) ) );
		}

		if ( 0 !== $person_type ) {
			if ( 1 === $person_type && isset( $_POST['_billing_persontype'] ) ) {
				$order->update_meta_data( '_billing_persontype', sanitize_text_field( wp_unslash( $_POST['_billing_persontype'] ) ) );
			}

			if ( 1 === $person_type || 2 === $person_type ) {
				if ( isset( $_POST['_billing_cpf'] ) ) {
					$order->update_meta_data( '_billing_cpf', sanitize_text_field( wp_unslash( $_POST['_billing_cpf'] ) ) );
				}

				if ( isset( $settings['rg'] ) && isset( $_POST['_billing_rg'] ) ) {
					$order->update_meta_data( '_billing_rg', sanitize_text_field( wp_unslash( $_POST['_billing_rg'] ) ) );
				}
			}

			if ( 1 === $person_type || 3 === $person_type ) {
				if ( isset( $_POST['_billing_cnpj'] ) ) {
					$order->update_meta_data( '_billing_cnpj', sanitize_text_field( wp_unslash( $_POST['_billing_cnpj'] ) ) );
				}

				if ( isset( $settings['ie'] ) && isset( $_POST['_billing_ie'] ) ) {
					$order->update_meta_data( '_billing_ie', sanitize_text_field( wp_unslash( $_POST['_billing_ie'] ) ) );
				}
			}
		}

		if ( isset( $settings['birthdate'] ) ) {
			if ( isset( $_POST['_billing_birthdate'] ) ) {
				$order->update_meta_data( '_billing_birthdate', sanitize_text_field( wp_unslash( $_POST['_billing_birthdate'] ) ) );
			}
		}

		if ( isset( $settings['gender'] ) ) {
			if ( isset( $_POST['_billing_gender'] ) ) {
				$order->update_meta_data( '_billing_gender', sanitize_text_field( wp_unslash( $_POST['_billing_gender'] ) ) );
			}
		}

		if ( isset( $settings['cell_phone'] ) && isset( $_POST['_billing_cellphone'] ) ) {
			$order->update_meta_data( '_billing_cellphone', sanitize_text_field( wp_unslash( $_POST['_billing_cellphone'] ) ) );
		}
	}
}

new Extra_Checkout_Fields_For_Brazil_Order();
