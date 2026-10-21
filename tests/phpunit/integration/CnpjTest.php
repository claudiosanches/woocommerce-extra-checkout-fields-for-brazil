<?php
/**
 * CNPJ registration lookup.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

/**
 * Covers the lookup, its cache, the refusal under each mode and where the
 * result ends up.
 */
class CnpjTest extends WP_UnitTestCase {

	/**
	 * A CNPJ whose check digits are valid.
	 */
	const CNPJ = '11.222.333/0001-81';

	/**
	 * The same CNPJ as the services are asked for it.
	 */
	const DIGITS = '11222333000181';

	/**
	 * URLs requested, in order.
	 *
	 * @var string[]
	 */
	protected $requests = array();

	/**
	 * Responses keyed by host, as status and body. A body that is not an
	 * array is sent as it is, and a missing host fails the request.
	 *
	 * @var array
	 */
	protected $responses = array();

	public function set_up() {
		parent::set_up();

		$this->requests  = array();
		$this->responses = array();

		Extra_Checkout_Fields_For_Brazil_Cnpj::reset();
		delete_transient( 'csbmw_cnpj_' . self::DIGITS );

		add_filter( 'pre_http_request', array( $this, 'mock_http' ), 10, 3 );
	}

	public function tear_down() {
		Extra_Checkout_Fields_For_Brazil_Cnpj::reset();
		parent::tear_down();
	}

	/**
	 * Answer outgoing requests from $this->responses.
	 *
	 * @param false|array $preempt Preempted response.
	 * @param array       $args    Request arguments.
	 * @param string      $url     URL.
	 *
	 * @return array|WP_Error
	 */
	public function mock_http( $preempt, $args, $url ) {
		$this->requests[] = $url;
		$host             = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! isset( $this->responses[ $host ] ) ) {
			return new WP_Error( 'http_request_failed', 'Operation timed out' );
		}

		list( $code, $body ) = $this->responses[ $host ];

		return array(
			'headers'  => array(),
			'body'     => is_array( $body ) ? wp_json_encode( $body ) : (string) $body,
			'response' => array(
				'code'    => $code,
				'message' => '',
			),
			'cookies'  => array(),
		);
	}

	/**
	 * BrasilAPI's answer for a registration.
	 *
	 * @param string $situation Situation.
	 *
	 * @return array
	 */
	protected function brasilapi( $situation ) {
		return array(
			200,
			array(
				'cnpj'                         => self::DIGITS,
				'situacao_cadastral'           => 'ATIVA' === $situation ? 2 : 8,
				'descricao_situacao_cadastral' => $situation,
			),
		);
	}

	/**
	 * Look a CNPJ up afresh, as the next request would.
	 *
	 * @param string $cnpj CNPJ.
	 *
	 * @return array
	 */
	protected function lookup( $cnpj = self::CNPJ ) {
		Extra_Checkout_Fields_For_Brazil_Cnpj::reset();

		return Extra_Checkout_Fields_For_Brazil_Cnpj::lookup( $cnpj );
	}

	public function test_an_active_registration_is_found_and_cached() {
		$this->responses['brasilapi.com.br'] = $this->brasilapi( 'ATIVA' );

		$result = $this->lookup();

		$this->assertSame( self::DIGITS, $result['cnpj'] );
		$this->assertSame( 'active', $result['status'] );
		$this->assertSame( 'ATIVA', $result['situation'] );
		$this->assertSame( 'brasilapi', $result['provider'] );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $result['checked_at'] );
		$this->assertSame( array( 'https://brasilapi.com.br/api/cnpj/v1/' . self::DIGITS ), $this->requests );

		$this->assertSame( $result, $this->lookup( self::DIGITS ) );
		$this->assertCount( 1, $this->requests );
	}

	public function test_a_closed_company_is_inactive() {
		$this->responses['brasilapi.com.br'] = $this->brasilapi( 'BAIXADA' );

		$result = $this->lookup();

		$this->assertSame( 'inactive', $result['status'] );
		$this->assertSame( 'BAIXADA', $result['situation'] );
	}

	public function test_opencnpj_answers_when_brasilapi_is_rate_limited() {
		$this->responses['brasilapi.com.br'] = array( 429, 'Too many requests' );
		$this->responses['api.opencnpj.org'] = array(
			200,
			array(
				'cnpj'               => self::DIGITS,
				'situacao_cadastral' => 'Inapta',
			),
		);

		$result = $this->lookup();

		$this->assertSame( 'inactive', $result['status'] );
		$this->assertSame( 'INAPTA', $result['situation'] );
		$this->assertSame( 'opencnpj', $result['provider'] );
		$this->assertSame( 'https://api.opencnpj.org/' . self::DIGITS, $this->requests[1] );
	}

	public function test_a_malformed_answer_is_no_answer() {
		$this->responses['brasilapi.com.br'] = array( 200, '<html>Bad gateway</html>' );
		$this->responses['api.opencnpj.org'] = array( 200, array( 'cnpj' => self::DIGITS ) );

		$this->assertSame( 'unavailable', $this->lookup()['status'] );
	}

	public function test_a_service_missing_the_cnpj_does_not_stop_the_next() {
		$this->responses['brasilapi.com.br'] = array( 404, array( 'type' => 'not_found' ) );
		$this->responses['api.opencnpj.org'] = array(
			200,
			array(
				'cnpj'               => self::DIGITS,
				'situacao_cadastral' => 'Ativa',
			),
		);

		$result = $this->lookup();

		$this->assertSame( 'active', $result['status'] );
		$this->assertSame( 'opencnpj', $result['provider'] );
	}

	public function test_a_cnpj_neither_service_has_is_not_found_for_an_hour() {
		$this->responses['brasilapi.com.br'] = array( 404, array( 'type' => 'not_found' ) );
		$this->responses['api.opencnpj.org'] = array( 404, array( 'error' => 'not found' ) );

		$result = $this->lookup();

		$this->assertSame( 'not_found', $result['status'] );
		$this->assertSame( 'brasilapi', $result['provider'] );

		$expires = (int) get_option( '_transient_timeout_csbmw_cnpj_' . self::DIGITS );
		$this->assertEqualsWithDelta( time() + HOUR_IN_SECONDS, $expires, 5 );
	}

	public function test_an_unanswered_lookup_is_not_cached() {
		$this->assertSame( 'unavailable', $this->lookup()['status'] );
		$this->assertCount( 2, $this->requests );
		$this->assertFalse( get_transient( 'csbmw_cnpj_' . self::DIGITS ) );

		$this->responses['brasilapi.com.br'] = $this->brasilapi( 'ATIVA' );

		$this->assertSame( 'active', $this->lookup()['status'] );
	}

	public function test_the_same_request_asks_once_even_when_no_service_answers() {
		Extra_Checkout_Fields_For_Brazil_Cnpj::lookup( self::CNPJ );
		Extra_Checkout_Fields_For_Brazil_Cnpj::lookup( self::CNPJ );

		$this->assertCount( 2, $this->requests );
	}

	public function test_an_expired_registration_is_asked_again() {
		$this->responses['brasilapi.com.br'] = $this->brasilapi( 'ATIVA' );
		$this->lookup();

		update_option( '_transient_timeout_csbmw_cnpj_' . self::DIGITS, time() - 1 );
		$this->responses['brasilapi.com.br'] = $this->brasilapi( 'SUSPENSA' );

		$this->assertSame( 'SUSPENSA', $this->lookup()['situation'] );
		$this->assertCount( 2, $this->requests );
	}

	public function test_a_cnpj_with_wrong_check_digits_is_not_asked_about() {
		$result = $this->lookup( '11.222.333/0001-00' );

		$this->assertSame( 'not_found', $result['status'] );
		$this->assertSame( array(), $this->requests );
	}

	public function test_an_alphanumeric_cnpj_is_asked_in_capitals() {
		$this->responses['brasilapi.com.br'] = $this->brasilapi( 'ATIVA' );

		$result = $this->lookup( '12.abc.345/01de-35' );

		$this->assertSame( '12ABC34501DE35', $result['cnpj'] );
		$this->assertSame( array( 'https://brasilapi.com.br/api/cnpj/v1/12ABC34501DE35' ), $this->requests );
		delete_transient( 'csbmw_cnpj_12ABC34501DE35' );
	}

	public function test_nothing_is_looked_up_while_off() {
		$this->assertSame( '', Extra_Checkout_Fields_For_Brazil_Cnpj::refusal( self::CNPJ, array() ) );
		$this->assertSame( '', Extra_Checkout_Fields_For_Brazil_Cnpj::refusal( self::CNPJ, array( 'cnpj_lookup' => 'unknown' ) ) );
		$this->assertSame( array(), $this->requests );
	}

	/**
	 * Why a CNPJ with a given status is refused under a mode.
	 *
	 * @param string $mode   Lookup mode.
	 * @param string $status Status the lookup gives.
	 *
	 * @return string
	 */
	protected function refusal( $mode, $status ) {
		$filter = static function ( $result ) use ( $status ) {
			$result['status'] = $status;

			return $result;
		};

		add_filter( 'csbmw_cnpj_lookup', $filter );
		Extra_Checkout_Fields_For_Brazil_Cnpj::reset();
		$refusal = Extra_Checkout_Fields_For_Brazil_Cnpj::refusal( self::CNPJ, array( 'cnpj_lookup' => $mode ) );
		remove_filter( 'csbmw_cnpj_lookup', $filter );

		return $refusal;
	}

	public function test_refusing_inactive_cnpjs_accepts_what_could_not_be_checked() {
		$this->assertSame( '', $this->refusal( 'active', 'active' ) );
		$this->assertSame( 'is not active at Receita Federal', $this->refusal( 'active', 'inactive' ) );
		$this->assertSame( '', $this->refusal( 'active', 'not_found' ) );
		$this->assertSame( '', $this->refusal( 'active', 'unavailable' ) );
	}

	public function test_strict_mode_accepts_only_cnpjs_found_active() {
		$this->assertSame( '', $this->refusal( 'strict', 'active' ) );
		$this->assertSame( 'is not active at Receita Federal', $this->refusal( 'strict', 'inactive' ) );
		$this->assertSame( 'was not found at Receita Federal', $this->refusal( 'strict', 'not_found' ) );
		$this->assertSame( 'could not be checked right now. Try again in a few minutes', $this->refusal( 'strict', 'unavailable' ) );
	}

	/**
	 * Settings of a store that asks for both person types and refuses
	 * inactive CNPJs.
	 *
	 * @return array
	 */
	protected function settings() {
		return array(
			'person_type'   => 1,
			'validate_cnpj' => 1,
			'cnpj_lookup'   => 'active',
		);
	}

	/**
	 * The front end instance hooked on a validation hook.
	 *
	 * @param string $hook Hook.
	 *
	 * @return Extra_Checkout_Fields_For_Brazil_Front_End
	 */
	protected function front_end( $hook ) {
		foreach ( $GLOBALS['wp_filter'][ $hook ] as $hooks ) {
			foreach ( $hooks as $callback ) {
				if ( is_array( $callback['function'] ) && $callback['function'][0] instanceof Extra_Checkout_Fields_For_Brazil_Front_End ) {
					return $callback['function'][0];
				}
			}
		}

		$this->fail( 'Nothing is hooked on ' . $hook . '.' );
	}

	public function test_the_classic_checkout_refuses_an_inactive_cnpj() {
		update_option( 'wcbcf_settings', $this->settings() );
		$this->responses['brasilapi.com.br'] = $this->brasilapi( 'BAIXADA' );

		$errors = new WP_Error();
		$this->front_end( 'woocommerce_after_checkout_validation' )->valid_checkout_fields(
			array(
				'billing_country'    => 'BR',
				'billing_persontype' => '2',
				'billing_company'    => 'ACME',
				'billing_cnpj'       => self::CNPJ,
			),
			$errors
		);

		$this->assertSame( array( 'billing_cnpj_refused' ), $errors->get_error_codes() );
		$this->assertSame( '<strong>CNPJ</strong> is not active at Receita Federal.', $errors->get_error_message() );
		$this->assertSame( array( 'id' => 'billing_cnpj' ), $errors->get_error_data() );
	}

	public function test_a_cnpj_with_wrong_check_digits_is_only_reported_invalid() {
		update_option( 'wcbcf_settings', array_merge( $this->settings(), array( 'cnpj_lookup' => 'strict' ) ) );

		$errors = new WP_Error();
		$this->front_end( 'woocommerce_after_checkout_validation' )->valid_checkout_fields(
			array(
				'billing_country'    => 'BR',
				'billing_persontype' => '2',
				'billing_company'    => 'ACME',
				'billing_cnpj'       => '11.222.333/0001-00',
			),
			$errors
		);

		$this->assertSame( array( 'billing_cnpj_invalid' ), $errors->get_error_codes() );
	}

	public function test_an_individual_is_not_looked_up() {
		update_option( 'wcbcf_settings', $this->settings() );

		$errors = new WP_Error();
		$this->front_end( 'woocommerce_after_checkout_validation' )->valid_checkout_fields(
			array(
				'billing_country'    => 'BR',
				'billing_persontype' => '1',
				'billing_cpf'        => '111.444.777-35',
				'billing_cnpj'       => self::CNPJ,
			),
			$errors
		);

		$this->assertSame( array(), $this->requests );
	}

	public function test_my_account_refuses_an_inactive_cnpj() {
		WC()->initialize_session();
		wc_clear_notices();
		update_option( 'wcbcf_settings', $this->settings() );
		$this->responses['brasilapi.com.br'] = $this->brasilapi( 'SUSPENSA' );

		$customer = new WC_Customer();
		$customer->set_billing_country( 'BR' );
		$customer->update_meta_data( 'billing_persontype', '2' );
		$customer->update_meta_data( 'billing_cnpj', self::CNPJ );

		$this->front_end( 'woocommerce_after_save_address_validation' )->valid_save_address_fields( 0, 'billing', array(), $customer );

		$this->assertSame( array( '<strong>CNPJ</strong> is not active at Receita Federal.' ), wp_list_pluck( wc_get_notices( 'error' ), 'notice' ) );
		wc_clear_notices();
	}

	public function test_the_block_checkout_refuses_an_inactive_cnpj() {
		update_option( 'wcbcf_settings', $this->settings() );
		$this->responses['brasilapi.com.br'] = $this->brasilapi( 'NULA' );

		$result = ( new Extra_Checkout_Fields_For_Brazil_Blocks() )->validate_field(
			self::CNPJ,
			array(
				'id'       => 'csbmw/cnpj',
				'label'    => 'CNPJ',
				'required' => true,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_refused_cnpj', $result->get_error_code() );
		$this->assertSame( 'CNPJ is not active at Receita Federal.', $result->get_error_message() );
	}

	public function test_the_order_keeps_the_result_for_its_cnpj() {
		update_option( 'wcbcf_settings', $this->settings() );
		$this->responses['brasilapi.com.br'] = $this->brasilapi( 'ATIVA' );

		$order = wc_create_order();
		$order->update_meta_data( '_billing_cnpj', self::CNPJ );
		do_action( 'woocommerce_checkout_create_order', $order, array() );
		$order->save();

		$order  = wc_get_order( $order->get_id() );
		$result = Extra_Checkout_Fields_For_Brazil_Cnpj::order_result( $order );

		$this->assertSame( 'active', $result['status'] );
		$this->assertSame( self::DIGITS, $result['cnpj'] );

		$response = ( new Extra_Checkout_Fields_For_Brazil_API() )->orders_response(
			new WP_REST_Response(
				array(
					'billing'  => array(),
					'shipping' => array(),
				)
			),
			$order
		);

		$this->assertSame(
			array(
				'status'     => 'active',
				'situation'  => 'ATIVA',
				'provider'   => 'brasilapi',
				'checked_at' => $result['checked_at'],
			),
			$response->data['billing']['cnpj_lookup']
		);

		// Edited on the order screen, the CNPJ is no longer the one checked.
		$order->update_meta_data( '_billing_cnpj', '19.131.243/0001-97' );

		$this->assertNull( Extra_Checkout_Fields_For_Brazil_Cnpj::order_result( $order ) );
	}

	public function test_the_block_checkout_order_keeps_the_result() {
		update_option( 'wcbcf_settings', $this->settings() );
		$this->responses['brasilapi.com.br'] = $this->brasilapi( 'ATIVA' );

		$order = wc_create_order();
		$order->update_meta_data( '_billing_cnpj', self::CNPJ );
		do_action( 'woocommerce_store_api_checkout_update_order_from_request', $order, new WP_REST_Request() );

		$this->assertSame( 'active', Extra_Checkout_Fields_For_Brazil_Cnpj::order_result( $order )['status'] );
	}

	public function test_nothing_is_recorded_while_off() {
		$order = wc_create_order();
		$order->update_meta_data( '_billing_cnpj', self::CNPJ );
		do_action( 'woocommerce_checkout_create_order', $order, array() );

		$this->assertSame( '', $order->get_meta( Extra_Checkout_Fields_For_Brazil_Cnpj::ORDER_META ) );
		$this->assertSame( array(), $this->requests );
	}

	public function test_the_order_screen_describes_the_result() {
		$this->assertSame(
			'BAIXADA at Receita Federal, checked through OpenCNPJ on September 27, 2026',
			Extra_Checkout_Fields_For_Brazil_Cnpj::describe(
				array(
					'status'     => 'inactive',
					'situation'  => 'BAIXADA',
					'provider'   => 'opencnpj',
					'checked_at' => '2026-09-27T12:00:00Z',
				)
			)
		);
	}
}
