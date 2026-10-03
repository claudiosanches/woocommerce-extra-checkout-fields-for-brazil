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

		// Fluid Checkout's support for this plugin was written for 4.x.
		add_action( 'init', array( $this, 'fluid_checkout' ), 100 );
		add_filter( 'fc_checkout_field_args', array( $this, 'fluid_checkout_fields' ), 120 );
		add_filter( 'fc_checkout_validation_brazilian_documents_script_settings', array( $this, 'fluid_checkout_documents' ), 20 );
	}

	/**
	 * Keep this plugin's checkout script and address layout under Fluid
	 * Checkout.
	 *
	 * Fluid Checkout swaps the script for its copy of the 4.x one, which
	 * depends on a mask library this plugin no longer registers, so neither
	 * runs. Its field positions and widths break the address order.
	 *
	 * @return void
	 */
	public function fluid_checkout() {
		if ( ! class_exists( 'FluidCheckout_WooCommerceExtraCheckoutFieldsForBrazil' ) ) {
			return;
		}

		$compat = FluidCheckout_WooCommerceExtraCheckoutFieldsForBrazil::instance();

		remove_action( 'wp_enqueue_scripts', array( $compat, 'replace_wcbcf_script' ), 20 );
		remove_filter( 'fc_checkout_field_args', array( $compat, 'change_checkout_field_args' ), 110 );
		remove_filter( 'woocommerce_default_address_fields', array( $compat, 'change_default_locale_field_args' ), 110 );
	}

	/**
	 * Adjust what Fluid Checkout changes in the checkout fields.
	 *
	 * @param array $fields Field arguments Fluid Checkout merges into the
	 *                      checkout fields, by field key.
	 *
	 * @return array
	 */
	public function fluid_checkout_fields( $fields ) {
		// Company belongs beside the CNPJ while legal persons alone are asked
		// for it, not among the contact fields.
		if ( Extra_Checkout_Fields_For_Brazil::has_dynamic_company() ) {
			unset( $fields['billing_company'] );
		}

		// Its valid mark would sit over the Exempt and No number toggles.
		foreach ( array( 'billing_ie', 'billing_number', 'shipping_number' ) as $key ) {
			$fields[ $key ]['class'] = array_merge( isset( $fields[ $key ]['class'] ) ? (array) $fields[ $key ]['class'] : array(), array( 'fc-no-validation-icon' ) );
		}

		return $fields;
	}

	/**
	 * Leave the CPF and CNPJ checks to this plugin under Fluid Checkout.
	 *
	 * Its own checks refuse alphanumeric CNPJs and any empty document field
	 * on show, even an optional one.
	 *
	 * @param array $settings Fluid Checkout's document validation settings.
	 *
	 * @return array
	 */
	public function fluid_checkout_documents( $settings ) {
		$settings['validateCPF']  = 'no';
		$settings['validateCNPJ'] = 'no';

		return $settings;
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
