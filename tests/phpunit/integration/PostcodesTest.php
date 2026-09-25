<?php
/**
 * Address lookup by CEP.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

/**
 * Postcodes test.
 */
class PostcodesTest extends WP_UnitTestCase {

	/**
	 * URLs requested, in order.
	 *
	 * @var string[]
	 */
	protected $requests = array();

	/**
	 * Responses keyed by host, as status and body.
	 *
	 * @var array
	 */
	protected $responses = array();

	public function set_up() {
		parent::set_up();

		Extra_Checkout_Fields_For_Brazil_Postcodes::maybe_install();

		global $wpdb;
		$wpdb->query( 'DELETE FROM ' . Extra_Checkout_Fields_For_Brazil_Postcodes::table() ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared

		$this->requests  = array();
		$this->responses = array();

		add_filter( 'pre_http_request', array( $this, 'mock_http' ), 10, 3 );
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
			return new WP_Error( 'http_request_failed', 'Offline' );
		}

		list( $code, $body ) = $this->responses[ $host ];

		return array(
			'headers'  => array(),
			'body'     => wp_json_encode( $body ),
			'response' => array(
				'code'    => $code,
				'message' => '',
			),
			'cookies'  => array(),
		);
	}

	public function test_viacep_answer_is_normalized_and_stored() {
		$this->responses['viacep.com.br'] = array(
			200,
			array(
				'cep'        => '01001-000',
				'logradouro' => 'Praça da Sé',
				'bairro'     => 'Sé',
				'localidade' => 'São Paulo',
				'uf'         => 'SP',
			),
		);

		$expected = array(
			'postcode'     => '01001000',
			'address'      => 'Praça da Sé',
			'neighborhood' => 'Sé',
			'city'         => 'São Paulo',
			'state'        => 'SP',
		);

		$this->assertSame( $expected, Extra_Checkout_Fields_For_Brazil_Postcodes::get_address( '01001-000' ) );

		// The second lookup is served by the table.
		$this->assertSame( $expected, Extra_Checkout_Fields_For_Brazil_Postcodes::get_address( '01001000' ) );
		$this->assertCount( 1, $this->requests );
	}

	public function test_falls_back_to_brasilapi_when_viacep_fails() {
		$this->responses['brasilapi.com.br'] = array(
			200,
			array(
				'cep'          => '20040020',
				'street'       => 'Praça Pio X',
				'neighborhood' => 'Centro',
				'city'         => 'Rio de Janeiro',
				'state'        => 'RJ',
			),
		);

		$address = Extra_Checkout_Fields_For_Brazil_Postcodes::get_address( '20040-020' );

		$this->assertSame( 'RJ', $address['state'] );
		$this->assertSame( 'Rio de Janeiro', $address['city'] );
		$this->assertCount( 2, $this->requests );
	}

	public function test_unknown_postcode_is_remembered() {
		$this->responses['viacep.com.br']    = array( 200, array( 'erro' => 'true' ) );
		$this->responses['brasilapi.com.br'] = array( 404, array( 'message' => 'CEP não encontrado.' ) );

		$this->assertNull( Extra_Checkout_Fields_For_Brazil_Postcodes::get_address( '00000000' ) );
		$this->assertNull( Extra_Checkout_Fields_For_Brazil_Postcodes::get_address( '00000000' ) );
		$this->assertCount( 2, $this->requests );
	}

	public function test_failed_services_are_asked_again() {
		$this->assertNull( Extra_Checkout_Fields_For_Brazil_Postcodes::get_address( '01001000' ) );
		$this->assertNull( Extra_Checkout_Fields_For_Brazil_Postcodes::get_address( '01001000' ) );
		$this->assertCount( 4, $this->requests );
	}

	public function test_address_without_a_brazilian_state_is_discarded() {
		$this->responses['viacep.com.br'] = array(
			200,
			array(
				'localidade' => 'São Paulo',
				'uf'         => 'XX',
			),
		);

		$this->assertNull( Extra_Checkout_Fields_For_Brazil_Postcodes::get_address( '01001000' ) );
	}

	public function test_incomplete_postcode_is_not_looked_up() {
		$this->assertNull( Extra_Checkout_Fields_For_Brazil_Postcodes::get_address( '0100' ) );
		$this->assertSame( array(), $this->requests );
	}
}
