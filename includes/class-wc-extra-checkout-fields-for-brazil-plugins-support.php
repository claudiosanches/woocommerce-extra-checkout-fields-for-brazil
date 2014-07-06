<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Methods to make integrations with others plugins.
 */
class Extra_Checkout_Fields_For_Brazil_Plugins_Support {

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
	 * @param  array $args   Bcash default arguments.
	 * @param  object $order Order data.
	 *
	 * @return array         New arguments.
	 */
	public function bcash( $args, $order ) {
		$args['numero'] = $order->billing_number;

		if ( isset( $order->billing_persontype ) ) {
			if ( 1 == $order->billing_persontype ) {
				$args['cpf'] = str_replace( array( '-', '.' ), '', $order->billing_cpf );
			}

			if ( 2 == $order->billing_persontype ) {
				$args['cliente_cnpj']         = str_replace( array( '-', '.' ), '', $order->billing_cnpj );
				$args['cliente_razao_social'] = $order->billing_company;
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
		$args['pagador_numero'] = $order->billing_number;
		$args['pagador_bairro'] = $order->billing_neighborhood;

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

		if ( isset( $order->billing_cpf ) ) {
			$args['cpf'] = $order->billing_cpf;
		}

		if ( isset( $order->billing_birthdate ) ) {
			$birthdate = explode( '/', $order->billing_birthdate );

			$args['birthdate_day']   = $birthdate[0];
			$args['birthdate_month'] = $birthdate[1];
			$args['birthdate_year']  = $birthdate[2];
		}

		return $args;
	}
}

new Extra_Checkout_Fields_For_Brazil_Plugins_Support();
