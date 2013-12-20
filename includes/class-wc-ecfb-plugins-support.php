<?php
/**
 * WooCommerce Extra Checkout Fields for Brazil.
 *
 * Methods to make integrations with others plugins.
 *
 * @package   Extra_Checkout_Fields_For_Brazil_Plugins_Support
 * @author    Claudio Sanches <contato@claudiosmweb.com>
 * @license   GPL-2.0+
 * @copyright 2013 Claudio Sanches
 */

/**
 * Plugin main class.
 *
 * @package Extra_Checkout_Fields_For_Brazil_Plugins_Support
 * @author  Claudio Sanches <contato@claudiosmweb.com>
 */
class Extra_Checkout_Fields_For_Brazil_Plugins_Support {

	/**
	 * Instance of this class.
	 *
	 * @since 2.8.0
	 *
	 * @var   object
	 */
	protected static $instance = null;

	/**
	 * Initialize integrations.
	 */
	private function __construct() {
		add_filter( 'woocommerce_bcash_args', array( $this, 'bcash' ), 1, 2 );
		add_filter( 'woocommerce_moip_args', array( $this, 'moip' ), 1, 2 );
		add_filter( 'woocommerce_moip_holder_data', array( $this, 'moip_transparent_checkout' ), 1, 2 );
		add_filter( 'woocommerce_pagseguro_payment_xml', array( $this, 'pagseguro' ), 1, 2 );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since  2.8.0
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
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

	/**
	 * Custom PagSeguro arguments.
	 *
	 * @param  object $xml   PagSeguro SimpleXMLElement object.
	 * @param  object $order Order data.
	 *
	 * @return object        New arguments.
	 */
	public function pagseguro( $xml, $order ) {
		if ( isset( $order->billing_cpf ) ) {
			$documents = $xml->sender->addChild( 'documents' );
			$document = $documents->addChild( 'document' );
			$document->addChild( 'type', 'CPF' );
			$document->addChild( 'value', str_replace( array( '.', '-' ), '', $order->billing_cpf ) );
		}

		if ( isset( $xml->shipping->address ) ) {
			if ( $order->billing_number ) {
				$xml->shipping->address->addChild( 'number', $order->billing_number );
			}

			if ( $order->billing_neighborhood ) {
				$xml->shipping->address->addChild( 'district' )->addCData( $order->billing_neighborhood );
			}
		}

		return $xml;
	}
}
