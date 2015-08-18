<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * API integration.
 */
class Extra_Checkout_Fields_For_Brazil_API {

	/**
	 * Initialize integrations.
	 */
	public function __construct() {
		add_filter( 'woocommerce_api_order_response', array( $this, 'orders' ), 100, 4 );
		add_filter( 'woocommerce_api_customer_response', array( $this, 'customer' ), 100, 4 );
	}

	/**
	 * Format number.
	 *
	 * @param  string $string
	 *
	 * @return string
	 */
	protected function format_number( $string ) {
		return str_replace( array( '.', '-', '/' ), '', $string );
	}

	/**
	 * Get formatted birthdate.
	 *
	 * @param  string        $date
	 * @param  WC_API_Server $server
	 *
	 * @return string
	 */
	protected function get_formatted_birthdate( $date, $server ) {
		$birthdate = explode( '/', $date );

		if ( isset( $birthdate[1] ) && ! empty( $birthdate[1] ) ) {
			return $server->format_datetime( $birthdate[1] . '/' . $birthdate[0] . '/' . $birthdate[2] );
		}

		return '';
	}

	/**
	 * Get person type.
	 *
	 * @param  int $type
	 *
	 * @return string
	 */
	protected function get_person_type( $type ) {
		$settings = get_option( 'wcbcf_settings' );

		switch ( $settings['person_type'] ) {
			case 1:
				$persontype = ( 2 == $type ) ? 'J' : 'F';
				break;
			case 2:
				$persontype = 'F';
				break;
			case 3:
				$persontype = 'J';
				break;

			default:
				$persontype = '';
				break;
		}

		return $persontype;
	}

	/**
	 * Add extra fields in order API.
	 *
	 * @param  array         $order_data
	 * @param  WC_Order      $order
	 * @param  array         $fields
	 * @param  WC_API_Server $server
	 *
	 * @return array
	 */
	public function orders( $order_data, $order, $fields, $server ) {

		// Billing fields.
		$order_data['billing_address']['persontype']   = $this->get_person_type( $order->billing_persontype );
		$order_data['billing_address']['cpf']          = $this->format_number( $order->billing_cpf );
		$order_data['billing_address']['rg']           = $this->format_number( $order->billing_rg );
		$order_data['billing_address']['cnpj']         = $this->format_number( $order->billing_cnpj );
		$order_data['billing_address']['ie']           = $this->format_number( $order->billing_ie );
		$order_data['billing_address']['birthdate']    = $this->get_formatted_birthdate( $order->billing_birthdate, $server );
		$order_data['billing_address']['sex']          = substr( $order->billing_sex, 0, 1 );
		$order_data['billing_address']['number']       = $order->billing_number;
		$order_data['billing_address']['neighborhood'] = $order->billing_neighborhood;
		$order_data['billing_address']['cellphone']    = $order->billing_cellphone;

		// Shipping fields.
		$order_data['shipping_address']['number']       = $order->shipping_number;
		$order_data['shipping_address']['neighborhood'] = $order->shipping_neighborhood;

		// Customer fields.
		if ( 0 == $order->customer_user && isset( $order_data['customer'] ) ) {
			// Customer billing fields.
			$order_data['customer']['billing_address']['persontype']   = $this->get_person_type( $order->billing_persontype );
			$order_data['customer']['billing_address']['cpf']          = $this->format_number( $order->billing_cpf );
			$order_data['customer']['billing_address']['rg']           = $this->format_number( $order->billing_rg );
			$order_data['customer']['billing_address']['cnpj']         = $this->format_number( $order->billing_cnpj );
			$order_data['customer']['billing_address']['ie']           = $this->format_number( $order->billing_ie );
			$order_data['customer']['billing_address']['birthdate']    = $this->get_formatted_birthdate( $order->billing_birthdate, $server );
			$order_data['customer']['billing_address']['sex']          = substr( $order->billing_sex, 0, 1 );
			$order_data['customer']['billing_address']['number']       = $order->billing_number;
			$order_data['customer']['billing_address']['neighborhood'] = $order->billing_neighborhood;
			$order_data['customer']['billing_address']['cellphone']    = $order->billing_cellphone;

			// Customer shipping fields.
			$order_data['customer']['shipping_address']['number']       = $order->shipping_number;
			$order_data['customer']['shipping_address']['neighborhood'] = $order->shipping_neighborhood;
		}

		if ( $fields ) {
			$order_data = WC()->api->WC_API_Customers->filter_response_fields( $order_data, $order, $fields );
		}

		return $order_data;
	}

	/**
	 * Add extra fields in customer API.
	 *
	 * @param  array         $customer_data
	 * @param  WC_Order      $customer
	 * @param  array         $fields
	 * @param  WC_API_Server $server
	 *
	 * @return array
	 */
	public function customer( $customer_data, $customer, $fields, $server ) {
		// Billing fields.
		$customer_data['billing_address']['persontype']   = $this->get_person_type( $customer->billing_persontype );
		$customer_data['billing_address']['cpf']          = $this->format_number( $customer->billing_cpf );
		$customer_data['billing_address']['rg']           = $this->format_number( $customer->billing_rg );
		$customer_data['billing_address']['cnpj']         = $this->format_number( $customer->billing_cnpj );
		$customer_data['billing_address']['ie']           = $this->format_number( $customer->billing_ie );
		$customer_data['billing_address']['birthdate']    = $this->get_formatted_birthdate( $customer->billing_birthdate, $server );
		$customer_data['billing_address']['sex']          = substr( $customer->billing_sex, 0, 1 );
		$customer_data['billing_address']['number']       = $customer->billing_number;
		$customer_data['billing_address']['neighborhood'] = $customer->billing_neighborhood;
		$customer_data['billing_address']['cellphone']    = $customer->billing_cellphone;

		// Shipping fields.
		$customer_data['shipping_address']['number']       = $customer->shipping_number;
		$customer_data['shipping_address']['neighborhood'] = $customer->shipping_neighborhood;

		if ( $fields ) {
			$customer_data = WC()->api->WC_API_Customers->filter_response_fields( $customer_data, $customer, $fields );
		}

		return $customer_data;
	}
}

new Extra_Checkout_Fields_For_Brazil_API();
