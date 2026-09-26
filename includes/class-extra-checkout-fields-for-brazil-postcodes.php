<?php
/**
 * Address lookup by CEP.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Postcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extra_Checkout_Fields_For_Brazil_Postcodes class.
 *
 * Addresses are kept in the table WooCommerce Correios uses for its own
 * autofill, with the same schema, so both plugins share one cache.
 */
class Extra_Checkout_Fields_For_Brazil_Postcodes {

	/**
	 * Table name, without the prefix.
	 *
	 * @var string
	 */
	const TABLE = 'correios_postcodes';

	/**
	 * Option holding the table version this plugin installed.
	 *
	 * @var string
	 */
	const DB_VERSION_OPTION = 'wcbcf_postcodes_db_version';

	/**
	 * Table version.
	 *
	 * @var string
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * WC AJAX endpoint.
	 *
	 * @var string
	 */
	const AJAX_ENDPOINT = 'csbmw_postcode_address';

	/**
	 * How long a CEP no service knows is remembered.
	 *
	 * @var int
	 */
	const NOT_FOUND_TTL = DAY_IN_SECONDS;

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		add_action( 'wc_ajax_' . self::AJAX_ENDPOINT, array( $this, 'ajax_get_address' ) );
	}

	/**
	 * Keep only the digits of a CEP.
	 *
	 * @param string $postcode CEP.
	 *
	 * @return string
	 */
	public static function sanitize( $postcode ) {
		return preg_replace( '/\D/', '', (string) $postcode );
	}

	/**
	 * Full table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;

		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Create the table when neither plugin has.
	 *
	 * @return void
	 */
	public static function maybe_install() {
		if ( self::DB_VERSION === get_option( self::DB_VERSION_OPTION ) ) {
			return;
		}

		global $wpdb;

		$table = self::table();

		// Same definition as WooCommerce Correios, so dbDelta() finds nothing
		// to change whichever plugin runs it second.
		$sql = "CREATE TABLE $table (
			ID bigint(20) NOT NULL auto_increment,
			postcode char(8) NOT NULL,
			address longtext NULL,
			city longtext NULL,
			neighborhood longtext NULL,
			state char(2) NULL,
			last_query datetime NULL,
			PRIMARY KEY  (ID),
			KEY postcode (postcode)
		) {$wpdb->get_charset_collate()};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql );

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Address for a CEP.
	 *
	 * @param string $postcode CEP, with or without the hyphen.
	 *
	 * @return array|null Keys postcode, address, neighborhood, city and state,
	 *                    or null when the CEP is unknown.
	 */
	public static function get_address( $postcode ) {
		$postcode = self::sanitize( $postcode );

		if ( 8 !== strlen( $postcode ) ) {
			return null;
		}

		self::maybe_install();

		$address = self::get_stored( $postcode );

		if ( null === $address && ! get_transient( 'csbmw_postcode_unknown_' . $postcode ) ) {
			$address = self::fetch( $postcode );
		}

		/**
		 * Filter the address found for a CEP.
		 *
		 * @since 5.0.0
		 *
		 * @param array|null $address  Address, or null when not found.
		 * @param string     $postcode CEP digits.
		 */
		return apply_filters( 'csbmw_postcode_address', $address, $postcode );
	}

	/**
	 * Address already in the table.
	 *
	 * @param string $postcode CEP digits.
	 *
	 * @return array|null
	 */
	protected static function get_stored( $postcode ) {
		global $wpdb;

		$table = self::table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE postcode = %s", $postcode ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $row ? self::normalize( $row ) : null;
	}

	/**
	 * Ask each service in turn and store the first answer.
	 *
	 * @param string $postcode CEP digits.
	 *
	 * @return array|null
	 */
	protected static function fetch( $postcode ) {
		$unknown = false;

		foreach ( self::services() as $service => $callback ) {
			$result = call_user_func( $callback, $postcode );

			if ( false === $result ) {
				$unknown = true;
				continue;
			}

			$address = is_array( $result ) ? self::normalize( $result ) : null;

			if ( null === $address ) {
				continue;
			}

			// WooCommerce Correios stores what it fetches itself.
			if ( 'correios' !== $service ) {
				self::store( $address );
			}

			return $address;
		}

		// Only a service that answered can say the CEP does not exist; one
		// that failed says nothing, so the next request tries again.
		if ( $unknown ) {
			set_transient( 'csbmw_postcode_unknown_' . $postcode, 1, self::NOT_FOUND_TTL );
		}

		return null;
	}

	/**
	 * Services to ask, in order.
	 *
	 * Each returns an address array, false when it knows the CEP does not
	 * exist, or null when it could not answer.
	 *
	 * @return callable[]
	 */
	protected static function services() {
		$services = array();

		// Correios answers only through CWS, which needs the store's contract.
		if ( class_exists( 'WC_Correios' ) && function_exists( 'wc_correios_get_address_by_postcode' ) && apply_filters( 'woocommerce_correios_cws_is_enabled', false ) ) {
			$services['correios'] = array( __CLASS__, 'fetch_correios' );
		}

		$services['viacep']    = array( __CLASS__, 'fetch_viacep' );
		$services['brasilapi'] = array( __CLASS__, 'fetch_brasilapi' );

		/**
		 * Filter the services asked for an address the table does not have.
		 *
		 * @since 5.0.0
		 *
		 * @param callable[] $services Callbacks keyed by service name.
		 */
		return apply_filters( 'csbmw_postcode_services', $services );
	}

	/**
	 * Ask WooCommerce Correios.
	 *
	 * @param string $postcode CEP digits.
	 *
	 * @return array|null
	 */
	public static function fetch_correios( $postcode ) {
		$address = wc_correios_get_address_by_postcode( $postcode );

		return $address ? (array) $address : null;
	}

	/**
	 * Ask ViaCEP.
	 *
	 * @param string $postcode CEP digits.
	 *
	 * @return array|false|null
	 */
	public static function fetch_viacep( $postcode ) {
		$data = self::get_json( 'https://viacep.com.br/ws/' . $postcode . '/json/' );

		if ( null === $data ) {
			return null;
		}

		if ( ! empty( $data['erro'] ) ) {
			return false;
		}

		return array(
			'postcode'     => $postcode,
			'address'      => isset( $data['logradouro'] ) ? $data['logradouro'] : '',
			'neighborhood' => isset( $data['bairro'] ) ? $data['bairro'] : '',
			'city'         => isset( $data['localidade'] ) ? $data['localidade'] : '',
			'state'        => isset( $data['uf'] ) ? $data['uf'] : '',
		);
	}

	/**
	 * Ask BrasilAPI.
	 *
	 * @param string $postcode CEP digits.
	 *
	 * @return array|false|null
	 */
	public static function fetch_brasilapi( $postcode ) {
		$data = self::get_json( 'https://brasilapi.com.br/api/cep/v2/' . $postcode, array( 404 ) );

		if ( null === $data ) {
			return null;
		}

		if ( empty( $data['cep'] ) ) {
			return false;
		}

		return array(
			'postcode'     => $postcode,
			'address'      => isset( $data['street'] ) ? $data['street'] : '',
			'neighborhood' => isset( $data['neighborhood'] ) ? $data['neighborhood'] : '',
			'city'         => isset( $data['city'] ) ? $data['city'] : '',
			'state'        => isset( $data['state'] ) ? $data['state'] : '',
		);
	}

	/**
	 * Decode a JSON response.
	 *
	 * @param string $url             URL.
	 * @param int[]  $answered_codes  Error statuses that still carry an answer.
	 *
	 * @return array|null Null when the request failed.
	 */
	protected static function get_json( $url, $answered_codes = array() ) {
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout' => 5,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code && ! in_array( $code, $answered_codes, true ) ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		return is_array( $data ) ? $data : null;
	}

	/**
	 * Keep the fields every caller relies on, or discard an unusable address.
	 *
	 * @param array $address Address from the table or a service.
	 *
	 * @return array|null
	 */
	protected static function normalize( $address ) {
		$state = strtoupper( isset( $address['state'] ) ? (string) $address['state'] : '' );
		$city  = isset( $address['city'] ) ? trim( (string) $address['city'] ) : '';

		if ( '' === $city || ! array_key_exists( $state, WC()->countries->get_states( 'BR' ) ) ) {
			return null;
		}

		return array(
			'postcode'     => self::sanitize( $address['postcode'] ),
			'address'      => isset( $address['address'] ) ? trim( (string) $address['address'] ) : '',
			'neighborhood' => isset( $address['neighborhood'] ) ? trim( (string) $address['neighborhood'] ) : '',
			'city'         => $city,
			'state'        => $state,
		);
	}

	/**
	 * Save an address to the table.
	 *
	 * @param array $address Normalized address.
	 *
	 * @return void
	 */
	protected static function store( $address ) {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			self::table(),
			array(
				'postcode'     => $address['postcode'],
				'address'      => $address['address'],
				'city'         => $address['city'],
				'neighborhood' => $address['neighborhood'],
				'state'        => $address['state'],
				'last_query'   => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * WC AJAX endpoint returning the address for a CEP.
	 *
	 * @return void
	 */
	public function ajax_get_address() {
		$settings = (array) get_option( 'wcbcf_settings', array() );

		// Only the autofill and the cart block calculator ask from the browser.
		if ( empty( $settings['postcode_autofill'] ) && ! Extra_Checkout_Fields_For_Brazil_Shipping::is_postcode_only() ) {
			wp_send_json_error( array( 'message' => __( 'CEP lookup is disabled.', 'woocommerce-extra-checkout-fields-for-brazil' ) ), 403 );
		}

		$postcode = isset( $_GET['postcode'] ) ? self::sanitize( sanitize_text_field( wp_unslash( $_GET['postcode'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// A CEP the customer mistyped is an answer, not a failed request, so it
		// does not get an error status for the browser to log.
		if ( 8 !== strlen( $postcode ) ) {
			wp_send_json_error( array( 'message' => __( 'Enter a valid CEP.', 'woocommerce-extra-checkout-fields-for-brazil' ) ) );
		}

		$address = self::get_address( $postcode );

		if ( null === $address ) {
			wp_send_json_error( array( 'message' => __( 'CEP not found.', 'woocommerce-extra-checkout-fields-for-brazil' ) ) );
		}

		wp_send_json_success( $address );
	}
}

new Extra_Checkout_Fields_For_Brazil_Postcodes();
