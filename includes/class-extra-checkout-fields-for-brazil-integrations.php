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
		add_filter( 'woocommerce_bcash_args', array( $this, 'bcash' ), 1, 2 );
		add_filter( 'woocommerce_moip_args', array( $this, 'moip' ), 1, 2 );
		add_filter( 'woocommerce_moip_holder_data', array( $this, 'moip_transparent_checkout' ), 1, 2 );
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
		// WooCommerce 3.0 or later.
		if ( method_exists( $customer, 'get_meta' ) ) {
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
		} else {
			$args['numero'] = $order->billing_number;

			if ( isset( $order->billing_persontype ) ) {
				if ( 1 === intval( $order->billing_persontype ) ) {
					$args['cpf'] = str_replace( array( '-', '.' ), '', $order->billing_cpf );
				}

				if ( 2 === intval( $order->billing_persontype ) ) {
					$args['cliente_cnpj']         = str_replace( array( '-', '.' ), '', $order->billing_cnpj );
					$args['cliente_razao_social'] = $order->billing_company;
				}
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
		// WooCommerce 3.0 or later.
		if ( method_exists( $customer, 'get_meta' ) ) {
			$args['pagador_numero'] = $order->get_meta( '_billing_number' );
			$args['pagador_bairro'] = $order->get_meta( '_billing_neighborhood' );
		} else {
			$args['pagador_numero'] = $order->billing_number;
			$args['pagador_bairro'] = $order->billing_neighborhood;
		}

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
		// WooCommerce 3.0 or later.
		if ( method_exists( $customer, 'get_meta' ) ) {
			if ( '' !== $order->get_meta( '_billing_cpf' ) ) {
				$args['cpf'] = $order->get_meta( '_billing_cpf' );
			}

			if ( '' !== $order->get_meta( '_billing_birthdate' ) ) {
				$birthdate = explode( '/', $order->get_meta( '_billing_birthdate' ) );

				$args['birthdate_day']   = $birthdate[0];
				$args['birthdate_month'] = $birthdate[1];
				$args['birthdate_year']  = $birthdate[2];
			}
		} else {
			if ( isset( $order->billing_cpf ) ) {
				$args['cpf'] = $order->billing_cpf;
			}

			if ( isset( $order->billing_birthdate ) ) {
				$birthdate = explode( '/', $order->billing_birthdate );

				$args['birthdate_day']   = $birthdate[0];
				$args['birthdate_month'] = $birthdate[1];
				$args['birthdate_year']  = $birthdate[2];
			}
		}

		return $args;
	}
}

new Extra_Checkout_Fields_For_Brazil_Integrations();
