<?php
/**
 * CNPJ registration lookup.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Cnpj
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extra_Checkout_Fields_For_Brazil_Cnpj class.
 *
 * Every free service reads Receita Federal's monthly open data, so a company
 * registered in the last weeks is not found anywhere yet. Not found is kept
 * apart from inactive for that reason.
 */
class Extra_Checkout_Fields_For_Brazil_Cnpj {

	/**
	 * Registered and active.
	 *
	 * @var string
	 */
	const ACTIVE = 'active';

	/**
	 * Registered as NULA, SUSPENSA, INAPTA or BAIXADA.
	 *
	 * @var string
	 */
	const INACTIVE = 'inactive';

	/**
	 * Not in the data of any service that answered.
	 *
	 * @var string
	 */
	const NOT_FOUND = 'not_found';

	/**
	 * No service answered.
	 *
	 * @var string
	 */
	const UNAVAILABLE = 'unavailable';

	/**
	 * Order meta holding the result for the order's CNPJ.
	 *
	 * @var string
	 */
	const ORDER_META = '_billing_cnpj_lookup';

	/**
	 * How long a registration is remembered.
	 *
	 * @var int
	 */
	const FOUND_TTL = DAY_IN_SECONDS;

	/**
	 * How long a CNPJ no service knows is remembered. Shorter, since the next
	 * monthly data may add it.
	 *
	 * @var int
	 */
	const NOT_FOUND_TTL = HOUR_IN_SECONDS;

	/**
	 * Results of this request, including the unavailable ones, which are not
	 * cached across requests.
	 *
	 * @var array
	 */
	protected static $results = array();

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		// After the unused documents are cleared, so an individual's order
		// records nothing.
		add_action( 'woocommerce_checkout_create_order', array( $this, 'record' ), 30 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'record' ), 30 );
	}

	/**
	 * Lookup mode: off, active (refuse inactive CNPJs) or strict (accept only
	 * CNPJs confirmed active).
	 *
	 * @param array|null $settings Plugin settings.
	 *
	 * @return string
	 */
	public static function mode( $settings = null ) {
		$settings = null === $settings ? (array) get_option( 'wcbcf_settings', array() ) : $settings;
		$mode     = isset( $settings['cnpj_lookup'] ) ? (string) $settings['cnpj_lookup'] : '';

		return in_array( $mode, array( 'active', 'strict' ), true ) ? $mode : 'off';
	}

	/**
	 * Keep the letters and digits of a CNPJ, in capitals.
	 *
	 * @param string $cnpj CNPJ.
	 *
	 * @return string
	 */
	public static function sanitize( $cnpj ) {
		return preg_replace( '/[^A-Z0-9]/', '', strtoupper( (string) $cnpj ) );
	}

	/**
	 * Registration of a CNPJ.
	 *
	 * @param string $cnpj CNPJ, formatted or not.
	 *
	 * @return array Keys cnpj, status, situation (ATIVA, BAIXADA and so on,
	 *               empty unless found), provider and checked_at (UTC, ISO 8601).
	 */
	public static function lookup( $cnpj ) {
		$cnpj = self::sanitize( $cnpj );

		if ( isset( self::$results[ $cnpj ] ) ) {
			return self::$results[ $cnpj ];
		}

		// A CNPJ whose check digits fail cannot be registered.
		if ( ! Extra_Checkout_Fields_For_Brazil_Validation::is_cnpj( $cnpj ) ) {
			$result = self::result( $cnpj, self::NOT_FOUND );
		} else {
			$result = get_transient( 'csbmw_cnpj_' . $cnpj );

			if ( ! is_array( $result ) ) {
				$result = self::fetch( $cnpj );
			}
		}

		/**
		 * Filter the registration found for a CNPJ.
		 *
		 * @since 5.0.0
		 *
		 * @param array  $result Result, see lookup().
		 * @param string $cnpj   CNPJ, letters and digits only.
		 */
		$result = apply_filters( 'csbmw_cnpj_lookup', $result, $cnpj );

		self::$results[ $cnpj ] = $result;

		return $result;
	}

	/**
	 * Forget what this request looked up.
	 *
	 * @return void
	 */
	public static function reset() {
		self::$results = array();
	}

	/**
	 * Ask each service in turn and cache the first answer.
	 *
	 * @param string $cnpj CNPJ, letters and digits only.
	 *
	 * @return array
	 */
	protected static function fetch( $cnpj ) {
		$not_found = null;

		foreach ( self::services() as $service => $callback ) {
			$situation = call_user_func( $callback, $cnpj );

			// Services update on different days, so one that lacks the CNPJ
			// does not stop the next from being asked.
			if ( false === $situation ) {
				$not_found = $not_found ? $not_found : $service;
				continue;
			}

			if ( ! is_string( $situation ) || '' === $situation ) {
				continue;
			}

			$situation = self::normalize_situation( $situation );
			$result    = self::result( $cnpj, 'ATIVA' === $situation ? self::ACTIVE : self::INACTIVE, $situation, $service );

			set_transient( 'csbmw_cnpj_' . $cnpj, $result, self::FOUND_TTL );

			return $result;
		}

		if ( $not_found ) {
			$result = self::result( $cnpj, self::NOT_FOUND, '', $not_found );

			set_transient( 'csbmw_cnpj_' . $cnpj, $result, self::NOT_FOUND_TTL );

			return $result;
		}

		return self::result( $cnpj, self::UNAVAILABLE );
	}

	/**
	 * Build a result.
	 *
	 * @param string $cnpj      CNPJ.
	 * @param string $status    Status.
	 * @param string $situation Registration situation.
	 * @param string $provider  Service that answered.
	 *
	 * @return array
	 */
	protected static function result( $cnpj, $status, $situation = '', $provider = '' ) {
		return array(
			'cnpj'       => $cnpj,
			'status'     => $status,
			'situation'  => $situation,
			'provider'   => $provider,
			'checked_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
		);
	}

	/**
	 * Put a situation in Receita Federal's spelling.
	 *
	 * @param string $situation Situation, as a service wrote it.
	 *
	 * @return string
	 */
	protected static function normalize_situation( $situation ) {
		return remove_accents( strtoupper( trim( $situation ) ) );
	}

	/**
	 * Services to ask, in order.
	 *
	 * Each returns the registration situation, false when it knows the CNPJ
	 * is not registered, or null when it could not answer.
	 *
	 * @return callable[]
	 */
	protected static function services() {
		/**
		 * Filter the services asked for a CNPJ registration.
		 *
		 * @since 5.0.0
		 *
		 * @param callable[] $services Callbacks keyed by service name.
		 */
		return apply_filters(
			'csbmw_cnpj_services',
			array(
				'brasilapi' => array( __CLASS__, 'fetch_brasilapi' ),
				'opencnpj'  => array( __CLASS__, 'fetch_opencnpj' ),
			)
		);
	}

	/**
	 * Ask BrasilAPI.
	 *
	 * @param string $cnpj CNPJ.
	 *
	 * @return string|false|null
	 */
	public static function fetch_brasilapi( $cnpj ) {
		$data = self::get_json( 'https://brasilapi.com.br/api/cnpj/v1/' . $cnpj, $code );

		if ( 404 === $code ) {
			return false;
		}

		return isset( $data['descricao_situacao_cadastral'] ) && is_string( $data['descricao_situacao_cadastral'] ) ? $data['descricao_situacao_cadastral'] : null;
	}

	/**
	 * Ask OpenCNPJ.
	 *
	 * @param string $cnpj CNPJ.
	 *
	 * @return string|false|null
	 */
	public static function fetch_opencnpj( $cnpj ) {
		$data = self::get_json( 'https://api.opencnpj.org/' . $cnpj, $code );

		if ( 404 === $code ) {
			return false;
		}

		return isset( $data['situacao_cadastral'] ) && is_string( $data['situacao_cadastral'] ) ? $data['situacao_cadastral'] : null;
	}

	/**
	 * Decode a JSON response.
	 *
	 * @param string $url  URL.
	 * @param int    $code Set to the response status, 0 when the request failed.
	 *
	 * @return array|null Null unless the status is 200 with a JSON object.
	 */
	protected static function get_json( $url, &$code = 0 ) {
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout' => 5,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);

		$code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		return is_array( $data ) ? $data : null;
	}

	/**
	 * Why a CNPJ is refused under the lookup mode, if it is.
	 *
	 * @param string     $cnpj     CNPJ as submitted.
	 * @param array|null $settings Plugin settings.
	 *
	 * @return string Message without the field label, such as "is not active
	 *                at Receita Federal", or empty when accepted.
	 */
	public static function refusal( $cnpj, $settings = null ) {
		$mode = self::mode( $settings );

		if ( 'off' === $mode || '' === self::sanitize( $cnpj ) ) {
			return '';
		}

		$status = self::lookup( $cnpj )['status'];

		if ( self::INACTIVE === $status ) {
			return __( 'is not active at Receita Federal', 'woocommerce-extra-checkout-fields-for-brazil' );
		}

		if ( 'strict' !== $mode ) {
			return '';
		}

		if ( self::NOT_FOUND === $status ) {
			return __( 'was not found at Receita Federal', 'woocommerce-extra-checkout-fields-for-brazil' );
		}

		if ( self::UNAVAILABLE === $status ) {
			return __( 'could not be checked right now. Try again in a few minutes', 'woocommerce-extra-checkout-fields-for-brazil' );
		}

		return '';
	}

	/**
	 * Keep the result for the CNPJ an order is placed with.
	 *
	 * The checkout validation has looked it up already in this request.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return void
	 */
	public function record( $order ) {
		if ( ! $order instanceof WC_Order || 'off' === self::mode() ) {
			return;
		}

		$cnpj = self::sanitize( $order->get_meta( '_billing_cnpj' ) );

		if ( '' === $cnpj ) {
			$order->delete_meta_data( self::ORDER_META );
			return;
		}

		$order->update_meta_data( self::ORDER_META, self::lookup( $cnpj ) );
	}

	/**
	 * Result recorded for an order's current CNPJ.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array|null Null when none was, or the CNPJ changed since.
	 */
	public static function order_result( $order ) {
		$result = $order->get_meta( self::ORDER_META );

		if ( ! is_array( $result ) || ! isset( $result['cnpj'], $result['status'] ) ) {
			return null;
		}

		return self::sanitize( $order->get_meta( '_billing_cnpj' ) ) === $result['cnpj'] ? $result : null;
	}

	/**
	 * Describe a result for the order screen.
	 *
	 * @param array $result Result.
	 *
	 * @return string
	 */
	public static function describe( $result ) {
		$services = array(
			'brasilapi' => 'BrasilAPI',
			'opencnpj'  => 'OpenCNPJ',
		);
		$provider = isset( $services[ $result['provider'] ] ) ? $services[ $result['provider'] ] : $result['provider'];
		$date     = empty( $result['checked_at'] ) ? '' : wp_date( get_option( 'date_format' ), strtotime( $result['checked_at'] ) );

		switch ( $result['status'] ) {
			case self::ACTIVE:
			case self::INACTIVE:
				/* translators: 1: registration situation, such as ATIVA or BAIXADA, 2: service, 3: date. */
				return sprintf( __( '%1$s at Receita Federal, checked through %2$s on %3$s', 'woocommerce-extra-checkout-fields-for-brazil' ), $result['situation'], $provider, $date );
			case self::NOT_FOUND:
				/* translators: %s: date. */
				return sprintf( __( 'Not found at Receita Federal, checked on %s', 'woocommerce-extra-checkout-fields-for-brazil' ), $date );
			default:
				/* translators: %s: date. */
				return sprintf( __( 'Not checked, no service answered on %s', 'woocommerce-extra-checkout-fields-for-brazil' ), $date );
		}
	}
}

new Extra_Checkout_Fields_For_Brazil_Cnpj();
