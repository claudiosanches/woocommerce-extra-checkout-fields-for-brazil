<?php
/**
 * Extra checkout fields integrations.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Integrations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extra_Checkout_Fields_For_Brazil_Integrations class.
 */
class Extra_Checkout_Fields_For_Brazil_Integrations {

	/**
	 * Initialize integrations.
	 */
	public function __construct() {
		// Check if Flux Checkout for WooCommerce is active.
		if ( defined( 'FLUX_PLUGIN_VERSION' ) ) {
			add_filter( 'woocommerce_billing_fields', array( $this, 'flux_billing_fields' ), 100 );
			add_filter( 'woocommerce_shipping_fields', array( $this, 'flux_shipping_fields' ), 100 );
		}

		add_filter( 'woocommerce_bcash_args', array( $this, 'bcash' ), 1, 2 );
		add_filter( 'woocommerce_moip_args', array( $this, 'moip' ), 1, 2 );
		add_filter( 'woocommerce_moip_holder_data', array( $this, 'moip_transparent_checkout' ), 1, 2 );
	}

	/**
	 * Custom Flux Checkout for WooCommerce billing fields.
	 *
	 * @param  array $fields Checkout fields.
	 *
	 * @return array         New fields.
	 */
	public function flux_billing_fields( $fields ) {
		// Set correct priority and form-row-wide class.
		$fields['billing_number']['class']          = array( 'form-row-wide', 'address-field' );
		$fields['billing_number']['priority']       = 65;
		$fields['billing_neighborhood']['class']    = array( 'form-row-wide', 'address-field' );
		$fields['billing_neighborhood']['priority'] = 80;
		$fields['billing_cellphone']['class']       = array( 'form-row-wide' );

		// Remove tel type to avoid a phone icon.
		if ( isset( $fields['billing_cpf'] ) ) {
			$fields['billing_cpf']['type'] = 'text';
		}
		if ( isset( $fields['billing_cnpj'] ) ) {
			$fields['billing_cnpj']['type'] = 'text';
		}

		return $fields;
	}

	/**
	 * Custom Flux Checkout for WooCommerce shipping fields.
	 *
	 * @param  array $fields Checkout fields.
	 *
	 * @return array         New fields.
	 */
	public function flux_shipping_fields( $fields ) {
		// Set correct priority and form-row-wide class.
		$fields['shipping_number']['class']          = array( 'form-row-wide', 'address-field' );
		$fields['shipping_number']['priority']       = 140;
		$fields['shipping_neighborhood']['class']    = array( 'form-row-wide', 'address-field' );
		$fields['shipping_neighborhood']['priority'] = 160;

		return $fields;
	}

	/**
	 * Custom Bcash arguments.
	 *
	 * @param  array  $args   Bcash default arguments.
	 * @param  object $order  Order data.
	 *
	 * @return array          New arguments.
	 */
	public function bcash( $args, $order ) {
		$args['numero'] = $order->get_meta( '_billing_number' );
		$person_type    = intval( $order->get_meta( '_billing_persontype' ) );

		if ( $person_type ) {
			if ( 1 === $person_type ) {
				$args['cpf'] = str_replace( array( '-', '.' ), '', $order->get_meta( '_billing_cpf' ) );
			}

			if ( 2 === $person_type ) {
				$args['cliente_cnpj']         = str_replace( array( '-', '.' ), '', $order->get_meta( '_billing_cnpj' ) );
				$args['cliente_razao_social'] = $order->get_billing_company();
			}
		}

		return $args;
	}

	/**
	 * Custom Moip arguments.
	 *
	 * @param  array  $args  Moip default arguments.
	 * @param  object $order Order data.
	 *
	 * @return array         New arguments.
	 */
	public function moip( $args, $order ) {
		$args['pagador_numero'] = $order->get_meta( '_billing_number' );
		$args['pagador_bairro'] = $order->get_meta( '_billing_neighborhood' );

		return $args;
	}

	/**
	 * Custom Moip Transparent Checkout arguments.
	 *
	 * @param  array  $args  Moip Transparent Checkout default arguments.
	 * @param  object $order Order data.
	 *
	 * @return array         New arguments.
	 */
	public function moip_transparent_checkout( $args, $order ) {
		if ( '' !== $order->get_meta( '_billing_cpf' ) ) {
			$args['cpf'] = $order->get_meta( '_billing_cpf' );
		}

		if ( '' !== $order->get_meta( '_billing_birthdate' ) ) {
			$birthdate = explode( '/', $order->get_meta( '_billing_birthdate' ) );

			$args['birthdate_day']   = $birthdate[0];
			$args['birthdate_month'] = $birthdate[1];
			$args['birthdate_year']  = $birthdate[2];
		}

		return $args;
	}
}

new Extra_Checkout_Fields_For_Brazil_Integrations();
