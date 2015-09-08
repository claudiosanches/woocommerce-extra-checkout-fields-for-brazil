<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shop order class.
 */
class Extra_Checkout_Fields_For_Brazil_Order {

	/**
	 * Initialize the order actions.
	 */
	public function __construct() {
		add_filter( 'woocommerce_admin_billing_fields', array( $this, 'shop_order_billing_fields' ) );
		add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'shop_order_shipping_fields' ) );
		add_filter( 'woocommerce_found_customer_details', array( $this, 'customer_details_ajax' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'order_data_after_billing_address' ) );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'order_data_after_shipping_address' ) );
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
		$settings = get_option( 'wcbcf_settings' );

		$billing_data['first_name'] = array(
			'label' => __( 'First Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$billing_data['last_name'] = array(
			'label' => __( 'Last Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);

		if ( 0 != $settings['person_type'] ) {
			if ( 1 == $settings['person_type'] ) {
				$billing_data['persontype'] = array(
					'type'    => 'select',
					'label'   => __( 'Person type', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'options' => array(
						'0' => __( 'Select', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'1' => __( 'Individuals', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'2' => __( 'Legal Person', 'woocommerce-extra-checkout-fields-for-brazil' )
					)
				);
			}

			if ( 1 == $settings['person_type'] || 2 == $settings['person_type'] ) {
				$billing_data['cpf'] = array(
					'label' => __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
				);
				if ( isset( $settings['rg'] ) ) {
					$billing_data['rg'] = array(
						'label' => __( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ),
					);
				}
			}

			if ( 1 == $settings['person_type'] || 3 == $settings['person_type'] ) {
				$billing_data['company'] = array(
					'label' => __( 'Company Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
				);
				$billing_data['cnpj'] = array(
					'label' => __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
				);
				if ( isset( $settings['ie'] ) ) {
					$billing_data['ie'] = array(
						'label' => __( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ),
					);
				}
			}

		} else {
			$billing_data['company'] = array(
				'label' => __( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'show'  => false
			);
		}

		if ( isset( $settings['birthdate_sex'] ) ) {
			$billing_data['birthdate'] = array(
				'label' => __( 'Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' )
			);
			$billing_data['sex'] = array(
				'label' => __( 'Sex', 'woocommerce-extra-checkout-fields-for-brazil' )
			);
		}

		$billing_data['address_1'] = array(
			'label' => __( 'Address 1', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$billing_data['number'] = array(
			'label' => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$billing_data['address_2'] = array(
			'label' => __( 'Address 2', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$billing_data['neighborhood'] = array(
			'label' => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$billing_data['city'] = array(
			'label' => __( 'City', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$billing_data['state'] = array(
			'label' => __( 'State', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$billing_data['country'] = array(
			'label'   => __( 'Country', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'    => false,
			'type'    => 'select',
			'options' => array(
				'' => __( 'Select a country&hellip;', 'woocommerce-extra-checkout-fields-for-brazil' )
			) + WC()->countries->get_allowed_countries()
		);
		$billing_data['postcode'] = array(
			'label' => __( 'Postcode', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);

		$billing_data['phone'] = array(
			'label' => __( 'Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
		);

		if ( isset( $settings['cell_phone'] ) ) {
			$billing_data['cellphone'] = array(
				'label' => __( 'Cell Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
			);
		}

		$billing_data['email'] = array(
			'label' => __( 'Email', 'woocommerce-extra-checkout-fields-for-brazil' ),
		);


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
		$shipping_data['first_name'] = array(
			'label' => __( 'First Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$shipping_data['last_name'] = array(
			'label' => __( 'Last Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$shipping_data['company'] = array(
			'label' => __( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$shipping_data['address_1'] = array(
			'label' => __( 'Address 1', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$shipping_data['number'] = array(
			'label' => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$shipping_data['address_2'] = array(
			'label' => __( 'Address 2', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$shipping_data['neighborhood'] = array(
			'label' => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$shipping_data['city'] = array(
			'label' => __( 'City', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$shipping_data['state'] = array(
			'label' => __( 'State', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$shipping_data['country'] = array(
			'label'   => __( 'Country', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'    => false,
			'type'    => 'select',
			'options' => array(
				'' => __( 'Select a country&hellip;', 'woocommerce-extra-checkout-fields-for-brazil' )
			) + WC()->countries->get_allowed_countries()
		);
		$shipping_data['postcode'] = array(
			'label' => __( 'Postcode', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);

		return apply_filters( 'wcbcf_admin_shipping_fields', $shipping_data );
	}

	/**
	 * Add custom fields in customer details ajax.
	 */
	public function customer_details_ajax( $customer_data ) {
		$user_id      = (int) trim( stripslashes( $_POST['user_id'] ) );
		$type_to_load = esc_attr( trim( stripslashes( $_POST['type_to_load'] ) ) );

		$custom_data = array(
			$type_to_load . '_number'       => get_user_meta( $user_id, $type_to_load . '_number', true ),
			$type_to_load . '_neighborhood' => get_user_meta( $user_id, $type_to_load . '_neighborhood', true ),
			$type_to_load . '_persontype'   => get_user_meta( $user_id, $type_to_load . '_persontype', true ),
			$type_to_load . '_cpf'          => get_user_meta( $user_id, $type_to_load . '_cpf', true ),
			$type_to_load . '_rg'           => get_user_meta( $user_id, $type_to_load . '_rg', true ),
			$type_to_load . '_cnpj'         => get_user_meta( $user_id, $type_to_load . '_cnpj', true ),
			$type_to_load . '_ie'           => get_user_meta( $user_id, $type_to_load . '_ie', true ),
			$type_to_load . '_birthdate'    => get_user_meta( $user_id, $type_to_load . '_birthdate', true ),
			$type_to_load . '_sex'          => get_user_meta( $user_id, $type_to_load . '_sex', true ),
			$type_to_load . '_cellphone'    => get_user_meta( $user_id, $type_to_load . '_cellphone', true )
		);

		return array_merge( $customer_data, $custom_data );
	}

	/**
	 * Custom billing admin fields.
	 *
	 * @param  object $order Order data.
	 *
	 * @return string        Custom information.
	 */
	public function order_data_after_billing_address( $order ) {
		// Get plugin settings.
		$settings = get_option( 'wcbcf_settings' );

		$html = '<div class="clear"></div>';
		$html .= '<div class="wcbcf-address">';

		if ( ! $order->get_formatted_billing_address() ) {
			$html .= '<p class="none_set"><strong>' . __( 'Address', 'woocommerce-extra-checkout-fields-for-brazil' ) . ':</strong> ' . __( 'No billing address set.', 'woocommerce-extra-checkout-fields-for-brazil' ) . '</p>';
		} else {

			$html .= '<p><strong>' . __( 'Address', 'woocommerce-extra-checkout-fields-for-brazil' ) . ':</strong><br />';
				$html .= $order->get_formatted_billing_address();
			$html .= '</p>';
		}

		$html .= '<h4>' . __( 'Customer data', 'woocommerce-extra-checkout-fields-for-brazil' ) . '</h4>';

		$html .= '<p>';

		if ( 0 != $settings['person_type'] ) {

			// Person type information.
			if ( ( 1 == $order->billing_persontype && 1 == $settings['person_type'] ) || 2 == $settings['person_type'] ) {
				$html .= '<strong>' . __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . esc_html( $order->billing_cpf ) . '<br />';

				if ( isset( $settings['rg'] ) ) {
					$html .= '<strong>' . __( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . esc_html( $order->billing_rg ) . '<br />';
				}
			}

			if ( ( 2 == $order->billing_persontype && 1 == $settings['person_type'] ) || 3 == $settings['person_type'] ) {
				$html .= '<strong>' . __( 'Company Name', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . esc_html( $order->billing_company ) . '<br />';
				$html .= '<strong>' . __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . esc_html( $order->billing_cnpj ) . '<br />';

				if ( isset( $settings['ie'] ) ) {
					$html .= '<strong>' . __( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . esc_html( $order->billing_ie ) . '<br />';
				}
			}
		} else {
			$html .= '<strong>' . __( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . esc_html( $order->billing_company ) . '<br />';
		}

		if ( isset( $settings['birthdate_sex'] ) ) {

			// Birthdate information.
			$html .= '<strong>' . __( 'Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . esc_html( $order->billing_birthdate ) . '<br />';

			// Sex Information.
			$html .= '<strong>' . __( 'Sex', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . esc_html( $order->billing_sex ) . '<br />';
		}

		$html .= '<strong>' . __( 'Phone', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . esc_html( $order->billing_phone ) . '<br />';

		// Cell Phone Information.
		if ( ! empty( $order->billing_cellphone ) ) {
			$html .= '<strong>' . __( 'Cell Phone', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . esc_html( $order->billing_cellphone ) . '<br />';
		}

		$html .= '<strong>' . __( 'Email', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . make_clickable( esc_html( $order->billing_email ) ) . '<br />';

		$html .= '</p>';

		$html .= '</div>';

		echo $html;
	}

	/**
	 * Custom billing admin fields.
	 *
	 * @param  object $order Order data.
	 *
	 * @return string        Custom information.
	 */
	public function order_data_after_shipping_address( $order ) {
		global $post;

		// Get plugin settings.
		$settings = get_option( 'wcbcf_settings' );

		$html = '<div class="clear"></div>';
		$html .= '<div class="wcbcf-address">';

		if ( ! $order->get_formatted_shipping_address() ) {
			$html .= '<p class="none_set"><strong>' . __( 'Address', 'woocommerce-extra-checkout-fields-for-brazil' ) . ':</strong> ' . __( 'No shipping address set.', 'woocommerce-extra-checkout-fields-for-brazil' ) . '</p>';
		} else {

			$html .= '<p><strong>' . __( 'Address', 'woocommerce-extra-checkout-fields-for-brazil' ) . ':</strong><br />';
				$html .= $order->get_formatted_shipping_address();
			$html .= '</p>';
		}

		if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' == get_option( 'woocommerce_enable_order_comments', 'yes' ) ) && $post->post_excerpt ) {
			$html .= '<p><strong>' . __( 'Customer Note', 'woocommerce-extra-checkout-fields-for-brazil' ) . ':</strong><br />' . nl2br( esc_html( $post->post_excerpt ) ) . '</p>';
		}

		$html .= '</div>';

		echo $html;
	}

	/**
	 * Save custom shop data fields.
	 *
	 * @param  int  $post_id Post ID.
	 *
	 * @return mixed
	 */
	public function save_custom_shop_data( $post_id ) {
		// Get plugin settings.
		$settings = get_option( 'wcbcf_settings' );

		// Update options.
		update_post_meta( $post_id, '_billing_number', woocommerce_clean( $_POST['_billing_number'] ) );
		update_post_meta( $post_id, '_billing_neighborhood', woocommerce_clean( $_POST['_billing_neighborhood'] ) );
		update_post_meta( $post_id, '_shipping_number', woocommerce_clean( $_POST['_shipping_number'] ) );
		update_post_meta( $post_id, '_shipping_neighborhood', woocommerce_clean( $_POST['_shipping_neighborhood'] ) );

		if ( 0 != $settings['person_type'] ) {
			if ( 1 == $settings['person_type'] ) {
				update_post_meta( $post_id, '_billing_persontype', woocommerce_clean( $_POST['_billing_persontype'] ) );
			}

			if ( 1 == $settings['person_type'] || 2 == $settings['person_type'] ) {
				update_post_meta( $post_id, '_billing_cpf', woocommerce_clean( $_POST['_billing_cpf'] ) );

				if ( isset( $settings['rg'] ) ) {
					update_post_meta( $post_id, '_billing_rg', woocommerce_clean( $_POST['_billing_rg'] ) );
				}
			}

			if ( 1 == $settings['person_type'] || 3 == $settings['person_type'] ) {
				update_post_meta( $post_id, '_billing_cnpj', woocommerce_clean( $_POST['_billing_cnpj'] ) );

				if ( isset( $settings['ie'] ) ) {
					update_post_meta( $post_id, '_billing_ie', woocommerce_clean( $_POST['_billing_ie'] ) );
				}
			}
		}

		if ( isset( $settings['birthdate_sex'] ) ) {
			update_post_meta( $post_id, '_billing_birthdate', woocommerce_clean( $_POST['_billing_birthdate'] ) );
			update_post_meta( $post_id, '_billing_sex', woocommerce_clean( $_POST['_billing_sex'] ) );
		}

		if ( isset( $settings['cell_phone'] ) ) {
			update_post_meta( $post_id, '_billing_cellphone', woocommerce_clean( $_POST['_billing_cellphone'] ) );
		}
	}
}

new Extra_Checkout_Fields_For_Brazil_Order();
