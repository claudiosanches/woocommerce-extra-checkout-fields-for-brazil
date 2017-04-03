<?php
/**
 * Extra checkout fields REST API integration.
 *
 * @package Extra_Checkout_Fields_For_Brazil/REST_API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extra_Checkout_Fields_For_Brazil_API class.
 */
class Extra_Checkout_Fields_For_Brazil_API {

	/**
	 * Initialize integrations.
	 */
	public function __construct() {
		// Legacy REST API.
		add_filter( 'woocommerce_api_order_response', array( $this, 'legacy_orders_response' ), 100, 4 );
		add_filter( 'woocommerce_api_customer_response', array( $this, 'legacy_customers_response' ), 100, 4 );

		// WP REST API.
		add_filter( 'woocommerce_rest_prepare_customer', array( $this, 'customers_response' ), 100, 2 );
		add_filter( 'woocommerce_rest_prepare_shop_order', array( $this, 'orders_v1_response' ), 100, 2 );
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this, 'orders_response' ), 100, 2 );
		add_filter( 'woocommerce_rest_customer_schema', array( $this, 'addresses_schema' ), 100 );
		add_filter( 'woocommerce_rest_shop_order_schema', array( $this, 'addresses_schema' ), 100 );
	}

	/**
	 * Format number.
	 *
	 * @param  string $string Number to format.
	 *
	 * @return string
	 */
	protected function format_number( $string ) {
		return str_replace( array( '.', '-', '/' ), '', $string );
	}

	/**
	 * Get formatted birthdate legacy.
	 *
	 * @param  string        $date   Date to format.
	 * @param  WC_API_Server $server Server instance.
	 *
	 * @return string
	 */
	protected function get_formatted_birthdate_legacy( $date, $server ) {
		$birthdate = explode( '/', $date );

		if ( isset( $birthdate[1] ) && ! empty( $birthdate[1] ) ) {
			return $server->format_datetime( $birthdate[1] . '/' . $birthdate[0] . '/' . $birthdate[2] );
		}

		return '';
	}

	/**
	 * Get formatted birthdate.
	 *
	 * @param  string $date Date for format.
	 *
	 * @return string
	 */
	protected function get_formatted_birthdate( $date ) {
		$birthdate = explode( '/', $date );

		if ( isset( $birthdate[1] ) && ! empty( $birthdate[1] ) ) {
			return sprintf( '%s-%s-%sT00:00:00', $birthdate[1], $birthdate[0], $birthdate[2] );
		}

		return '';
	}

	/**
	 * Get person type.
	 *
	 * @param  int $type Type.
	 *
	 * @return string
	 */
	protected function get_person_type( $type ) {
		$settings = get_option( 'wcbcf_settings' );

		switch ( intval( $settings['person_type'] ) ) {
			case 1 :
				$persontype = intval( $type ) === 2 ? 'J' : 'F';
				break;
			case 2 :
				$persontype = 'F';
				break;
			case 3 :
				$persontype = 'J';
				break;

			default :
				$persontype = '';
				break;
		}

		return $persontype;
	}

	/**
	 * Add extra fields in legacy order response.
	 *
	 * @param  array         $order_data Order response data..
	 * @param  WC_Order      $order      Order object.
	 * @param  array         $fields     Fields filter.
	 * @param  WC_API_Server $server     Server instance.
	 *
	 * @return array
	 */
	public function legacy_orders_response( $order_data, $order, $fields, $server ) {
		// WooCommerce 3.0 or later.
		if ( method_exists( $order, 'get_meta' ) ) {
			// Billing fields.
			$order_data['billing_address']['persontype']   = $this->get_person_type( $order->get_meta( '_billing_persontype' ) );
			$order_data['billing_address']['cpf']          = $this->format_number( $order->get_meta( '_billing_cpf' ) );
			$order_data['billing_address']['rg']           = $this->format_number( $order->get_meta( '_billing_rg' ) );
			$order_data['billing_address']['cnpj']         = $this->format_number( $order->get_meta( '_billing_cnpj' ) );
			$order_data['billing_address']['ie']           = $this->format_number( $order->get_meta( '_billing_ie' ) );
			$order_data['billing_address']['birthdate']    = $this->get_formatted_birthdate_legacy( $order->get_meta( '_billing_birthdate' ), $server );
			$order_data['billing_address']['sex']          = substr( $order->get_meta( '_billing_sex' ), 0, 1 );
			$order_data['billing_address']['number']       = $order->get_meta( '_billing_number' );
			$order_data['billing_address']['neighborhood'] = $order->get_meta( '_billing_neighborhood' );
			$order_data['billing_address']['cellphone']    = $order->get_meta( '_billing_cellphone' );

			// Shipping fields.
			$order_data['shipping_address']['number']       = $order->get_meta( '_shipping_number' );
			$order_data['shipping_address']['neighborhood'] = $order->get_meta( '_shipping_neighborhood' );

			// Customer fields.
			if ( 0 === intval( $order->customer_user ) && isset( $order_data['customer'] ) ) {
				// Customer billing fields.
				$order_data['customer']['billing_address']['persontype']   = $this->get_person_type( $order->get_meta( '_billing_persontype' ) );
				$order_data['customer']['billing_address']['cpf']          = $this->format_number( $order->get_meta( '_billing_cpf' ) );
				$order_data['customer']['billing_address']['rg']           = $this->format_number( $order->get_meta( '_billing_rg' ) );
				$order_data['customer']['billing_address']['cnpj']         = $this->format_number( $order->get_meta( '_billing_cnpj' ) );
				$order_data['customer']['billing_address']['ie']           = $this->format_number( $order->get_meta( '_billing_ie' ) );
				$order_data['customer']['billing_address']['birthdate']    = $this->get_formatted_birthdate_legacy( $order->get_meta( '_billing_birthdate' ), $server );
				$order_data['customer']['billing_address']['sex']          = substr( $order->get_meta( '_billing_sex' ), 0, 1 );
				$order_data['customer']['billing_address']['number']       = $order->get_meta( '_billing_number' );
				$order_data['customer']['billing_address']['neighborhood'] = $order->get_meta( '_billing_neighborhood' );
				$order_data['customer']['billing_address']['cellphone']    = $order->get_meta( '_billing_cellphone' );

				// Customer shipping fields.
				$order_data['customer']['shipping_address']['number']       = $order->get_meta( '_shipping_number' );
				$order_data['customer']['shipping_address']['neighborhood'] = $order->get_meta( '_shipping_neighborhood' );
			}
		} else {
			// Billing fields.
			$order_data['billing_address']['persontype']   = $this->get_person_type( $order->billing_persontype );
			$order_data['billing_address']['cpf']          = $this->format_number( $order->billing_cpf );
			$order_data['billing_address']['rg']           = $this->format_number( $order->billing_rg );
			$order_data['billing_address']['cnpj']         = $this->format_number( $order->billing_cnpj );
			$order_data['billing_address']['ie']           = $this->format_number( $order->billing_ie );
			$order_data['billing_address']['birthdate']    = $this->get_formatted_birthdate_legacy( $order->billing_birthdate, $server );
			$order_data['billing_address']['sex']          = substr( $order->billing_sex, 0, 1 );
			$order_data['billing_address']['number']       = $order->billing_number;
			$order_data['billing_address']['neighborhood'] = $order->billing_neighborhood;
			$order_data['billing_address']['cellphone']    = $order->billing_cellphone;

			// Shipping fields.
			$order_data['shipping_address']['number']       = $order->shipping_number;
			$order_data['shipping_address']['neighborhood'] = $order->shipping_neighborhood;

			// Customer fields.
			if ( 0 === intval( $order->customer_user ) && isset( $order_data['customer'] ) ) {
				// Customer billing fields.
				$order_data['customer']['billing_address']['persontype']   = $this->get_person_type( $order->billing_persontype );
				$order_data['customer']['billing_address']['cpf']          = $this->format_number( $order->billing_cpf );
				$order_data['customer']['billing_address']['rg']           = $this->format_number( $order->billing_rg );
				$order_data['customer']['billing_address']['cnpj']         = $this->format_number( $order->billing_cnpj );
				$order_data['customer']['billing_address']['ie']           = $this->format_number( $order->billing_ie );
				$order_data['customer']['billing_address']['birthdate']    = $this->get_formatted_birthdate_legacy( $order->billing_birthdate, $server );
				$order_data['customer']['billing_address']['sex']          = substr( $order->billing_sex, 0, 1 );
				$order_data['customer']['billing_address']['number']       = $order->billing_number;
				$order_data['customer']['billing_address']['neighborhood'] = $order->billing_neighborhood;
				$order_data['customer']['billing_address']['cellphone']    = $order->billing_cellphone;

				// Customer shipping fields.
				$order_data['customer']['shipping_address']['number']       = $order->shipping_number;
				$order_data['customer']['shipping_address']['neighborhood'] = $order->shipping_neighborhood;
			}
		} // End if().

		if ( $fields ) {
			$order_data = WC()->api->WC_API_Customers->filter_response_fields( $order_data, $order, $fields );
		}

		return $order_data;
	}

	/**
	 * Add extra fields in legacy customers response.
	 *
	 * @param  array         $customer_data Customer response data..
	 * @param  WC_Customer   $customer      Customer object.
	 * @param  array         $fields        Fields filter.
	 * @param  WC_API_Server $server        Server instance.
	 *
	 * @return array
	 */
	public function legacy_customers_response( $customer_data, $customer, $fields, $server ) {
		// WooCommerce 3.0 or later.
		if ( method_exists( $customer, 'get_meta' ) ) {
			// Billing fields.
			$customer_data['billing_address']['persontype']   = $this->get_person_type( $customer->get_meta( 'billing_persontype' ) );
			$customer_data['billing_address']['cpf']          = $this->format_number( $customer->get_meta( 'billing_cpf' ) );
			$customer_data['billing_address']['rg']           = $this->format_number( $customer->get_meta( 'billing_rg' ) );
			$customer_data['billing_address']['cnpj']         = $this->format_number( $customer->get_meta( 'billing_cnpj' ) );
			$customer_data['billing_address']['ie']           = $this->format_number( $customer->get_meta( 'billing_ie' ) );
			$customer_data['billing_address']['birthdate']    = $this->get_formatted_birthdate_legacy( $customer->get_meta( 'billing_birthdate' ), $server );
			$customer_data['billing_address']['sex']          = substr( $customer->get_meta( 'billing_sex' ), 0, 1 );
			$customer_data['billing_address']['number']       = $customer->get_meta( 'billing_number' );
			$customer_data['billing_address']['neighborhood'] = $customer->get_meta( 'billing_neighborhood' );
			$customer_data['billing_address']['cellphone']    = $customer->get_meta( 'billing_cellphone' );

			// Shipping fields.
			$customer_data['shipping_address']['number']       = $customer->get_meta( 'shipping_number' );
			$customer_data['shipping_address']['neighborhood'] = $customer->get_meta( 'shipping_neighborhood' );
		} else {
			// Billing fields.
			$customer_data['billing_address']['persontype']   = $this->get_person_type( $customer->billing_persontype );
			$customer_data['billing_address']['cpf']          = $this->format_number( $customer->billing_cpf );
			$customer_data['billing_address']['rg']           = $this->format_number( $customer->billing_rg );
			$customer_data['billing_address']['cnpj']         = $this->format_number( $customer->billing_cnpj );
			$customer_data['billing_address']['ie']           = $this->format_number( $customer->billing_ie );
			$customer_data['billing_address']['birthdate']    = $this->get_formatted_birthdate_legacy( $customer->billing_birthdate, $server );
			$customer_data['billing_address']['sex']          = substr( $customer->billing_sex, 0, 1 );
			$customer_data['billing_address']['number']       = $customer->billing_number;
			$customer_data['billing_address']['neighborhood'] = $customer->billing_neighborhood;
			$customer_data['billing_address']['cellphone']    = $customer->billing_cellphone;

			// Shipping fields.
			$customer_data['shipping_address']['number']       = $customer->shipping_number;
			$customer_data['shipping_address']['neighborhood'] = $customer->shipping_neighborhood;
		}

		if ( $fields ) {
			$customer_data = WC()->api->WC_API_Customers->filter_response_fields( $customer_data, $customer, $fields );
		}

		return $customer_data;
	}

	/**
	 * Add extra fields in customers response.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_User          $user     User object used to create response.
	 *
	 * @return WP_REST_Response
	 */
	public function customers_response( $response, $user ) {
		$customer = new WC_Customer( $user->ID );

		// WooCommerce 3.0 or later.
		if ( method_exists( $customer, 'get_meta' ) ) {
			// Billing fields.
			$response->data['billing']['number']       = $customer->get_meta( 'billing_number' );
			$response->data['billing']['neighborhood'] = $customer->get_meta( 'billing_neighborhood' );
			$response->data['billing']['persontype']   = $this->get_person_type( $customer->get_meta( 'billing_persontype' ) );
			$response->data['billing']['cpf']          = $this->format_number( $customer->get_meta( 'billing_cpf' ) );
			$response->data['billing']['rg']           = $this->format_number( $customer->get_meta( 'billing_rg' ) );
			$response->data['billing']['cnpj']         = $this->format_number( $customer->get_meta( 'billing_cnpj' ) );
			$response->data['billing']['ie']           = $this->format_number( $customer->get_meta( 'billing_ie' ) );
			$response->data['billing']['birthdate']    = $this->get_formatted_birthdate( $customer->get_meta( 'billing_birthdate' ) );
			$response->data['billing']['sex']          = substr( $customer->get_meta( 'billing_sex' ), 0, 1 );
			$response->data['billing']['cellphone']    = $customer->get_meta( 'billing_cellphone' );

			// Shipping fields.
			$response->data['shipping']['number']       = $customer->get_meta( 'shipping_number' );
			$response->data['shipping']['neighborhood'] = $customer->get_meta( 'shipping_neighborhood' );
		} else {
			// Billing fields.
			$response->data['billing']['number']       = $customer->billing_number;
			$response->data['billing']['neighborhood'] = $customer->billing_neighborhood;
			$response->data['billing']['persontype']   = $this->get_person_type( $customer->billing_persontype );
			$response->data['billing']['cpf']          = $this->format_number( $customer->billing_cpf );
			$response->data['billing']['rg']           = $this->format_number( $customer->billing_rg );
			$response->data['billing']['cnpj']         = $this->format_number( $customer->billing_cnpj );
			$response->data['billing']['ie']           = $this->format_number( $customer->billing_ie );
			$response->data['billing']['birthdate']    = $this->get_formatted_birthdate( $customer->billing_birthdate );
			$response->data['billing']['sex']          = substr( $customer->billing_sex, 0, 1 );
			$response->data['billing']['cellphone']    = $customer->billing_cellphone;

			// Shipping fields.
			$response->data['shipping']['number']       = $customer->shipping_number;
			$response->data['shipping']['neighborhood'] = $customer->shipping_neighborhood;
		}

		return $response;
	}

	/**
	 * Add extra fields in orders v1 response.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Post          $post     Post object.
	 *
	 * @return WP_REST_Response
	 */
	public function orders_v1_response( $response, $post ) {
		$order = wc_get_order( $post->ID );

		// WooCommerce 3.0 or later.
		if ( method_exists( $order, 'get_meta' ) ) {
			return $this->orders_response( $response, $order );
		} else {
			// Billing fields.
			$response->data['billing']['number']       = $order->billing_number;
			$response->data['billing']['neighborhood'] = $order->billing_neighborhood;
			$response->data['billing']['persontype']   = $this->get_person_type( $order->billing_persontype );
			$response->data['billing']['cpf']          = $this->format_number( $order->billing_cpf );
			$response->data['billing']['rg']           = $this->format_number( $order->billing_rg );
			$response->data['billing']['cnpj']         = $this->format_number( $order->billing_cnpj );
			$response->data['billing']['ie']           = $this->format_number( $order->billing_ie );
			$response->data['billing']['birthdate']    = $this->get_formatted_birthdate( $order->billing_birthdate );
			$response->data['billing']['sex']          = substr( $order->billing_sex, 0, 1 );
			$response->data['billing']['cellphone']    = $order->billing_cellphone;

			// Shipping fields.
			$response->data['shipping']['number']       = $order->shipping_number;
			$response->data['shipping']['neighborhood'] = $order->shipping_neighborhood;
		}

		return $response;
	}

	/**
	 * Add extra fields in orders response.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WC_Order         $order    Order object.
	 *
	 * @return WP_REST_Response
	 */
	public function orders_response( $response, $order ) {
		// Billing fields.
		$response->data['billing']['number']       = $order->get_meta( '_billing_number' );
		$response->data['billing']['neighborhood'] = $order->get_meta( '_billing_neighborhood' );
		$response->data['billing']['persontype']   = $this->get_person_type( $order->get_meta( '_billing_persontype' ) );
		$response->data['billing']['cpf']          = $this->format_number( $order->get_meta( '_billing_cpf' ) );
		$response->data['billing']['rg']           = $this->format_number( $order->get_meta( '_billing_rg' ) );
		$response->data['billing']['cnpj']         = $this->format_number( $order->get_meta( '_billing_cnpj' ) );
		$response->data['billing']['ie']           = $this->format_number( $order->get_meta( '_billing_ie' ) );
		$response->data['billing']['birthdate']    = $this->get_formatted_birthdate( $order->get_meta( '_billing_birthdate' ) );
		$response->data['billing']['sex']          = substr( $order->get_meta( '_billing_sex' ), 0, 1 );
		$response->data['billing']['cellphone']    = $order->get_meta( '_billing_cellphone' );

		// Shipping fields.
		$response->data['shipping']['number']       = $order->get_meta( '_shipping_number' );
		$response->data['shipping']['neighborhood'] = $order->get_meta( '_shipping_neighborhood' );

		return $response;
	}

	/**
	 * Addresses schena.
	 *
	 * @param  array $properties Default schema properties.
	 *
	 * @return array
	 */
	public function addresses_schema( $properties ) {
		$properties['billing']['properties']['number'] = array(
			'description' => __( 'Number.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$properties['billing']['properties']['neighborhood'] = array(
			'description' => __( 'Neighborhood.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$properties['billing']['properties']['persontype'] = array(
			'description' => __( 'Person type.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$properties['billing']['properties']['cpf'] = array(
			'description' => __( 'CPF.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$properties['billing']['properties']['rg'] = array(
			'description' => __( 'RG.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$properties['billing']['properties']['cnpj'] = array(
			'description' => __( 'CNPJ.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$properties['billing']['properties']['ie'] = array(
			'description' => __( 'IE.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$properties['billing']['properties']['birthdate'] = array(
			'description' => __( 'Birthdate.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$properties['billing']['properties']['sex'] = array(
			'description' => __( 'Gender.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$properties['billing']['properties']['cellphone'] = array(
			'description' => __( 'Cell Phone.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$properties['shipping']['properties']['number'] = array(
			'description' => __( 'Number.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$properties['shipping']['properties']['neighborhood'] = array(
			'description' => __( 'Neighborhood.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);

		return $properties;
	}
}

new Extra_Checkout_Fields_For_Brazil_API();
