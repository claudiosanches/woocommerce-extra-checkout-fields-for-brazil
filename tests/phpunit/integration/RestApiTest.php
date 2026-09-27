<?php
/**
 * Tests for the fields added to the WooCommerce REST API.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

/**
 * Covers the values orders and customers return and what can be written.
 */
class RestApiTest extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		update_option( 'wcbcf_settings', array( 'person_type' => '1' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Order response with the plugin's fields.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array
	 */
	protected function order_response( $order ) {
		return ( new Extra_Checkout_Fields_For_Brazil_API() )->orders_response(
			new WP_REST_Response(
				array(
					'billing'  => array(),
					'shipping' => array(),
				)
			),
			$order
		)->data;
	}

	public function test_an_order_gives_its_documents_without_punctuation() {
		$order = wc_create_order();
		$order->update_meta_data( '_billing_persontype', '2' );
		$order->update_meta_data( '_billing_cnpj', '12.ABC.345/01DE-35' );
		$order->update_meta_data( '_billing_ie', '110.042.490.114' );
		$order->update_meta_data( '_billing_gender', 'Feminino' );
		$order->update_meta_data( '_billing_number', '123' );
		$order->update_meta_data( '_shipping_neighborhood', 'Centro' );
		$order->save();

		$data = $this->order_response( $order );

		$this->assertSame( 'J', $data['billing']['persontype'] );
		$this->assertSame( '12ABC34501DE35', $data['billing']['cnpj'] );
		$this->assertSame( '110042490114', $data['billing']['ie'] );
		$this->assertSame( 'F', $data['billing']['gender'] );
		$this->assertSame( '123', $data['billing']['number'] );
		$this->assertSame( 'Centro', $data['shipping']['neighborhood'] );
	}

	/**
	 * Birthdates as stored and as the REST API gives them.
	 *
	 * @return array
	 */
	public function birthdate_provider() {
		return array(
			'day first'        => array( '15/01/1990', '1990-01-15T00:00:00' ),
			'no leading zeros' => array( '5/1/1990', '1990-01-05T00:00:00' ),
			'ISO date'         => array( '1990-01-15', '1990-01-15T00:00:00' ),
			'unrecognisable'   => array( '15 Jan 1990', '' ),
			'empty'            => array( '', '' ),
		);
	}

	/**
	 * @dataProvider birthdate_provider
	 *
	 * @param string $stored   Stored birthdate.
	 * @param string $expected REST value.
	 */
	public function test_a_birthdate_is_given_year_first( $stored, $expected ) {
		$order = wc_create_order();
		$order->update_meta_data( '_billing_birthdate', $stored );
		$order->save();

		$this->assertSame( $expected, $this->order_response( $order )['billing']['birthdate'] );

		$customer = new WC_Customer();
		$customer->set_email( 'birthdate' . wp_rand() . '@example.com' );
		$customer->update_meta_data( 'billing_birthdate', $stored );
		$customer->save();

		$response = ( new Extra_Checkout_Fields_For_Brazil_API() )->customers_response(
			new WP_REST_Response(
				array(
					'billing'  => array(),
					'shipping' => array(),
				)
			),
			get_user_by( 'id', $customer->get_id() )
		);

		$this->assertSame( $expected, $response->data['billing']['birthdate'] );
	}

	public function test_the_person_type_is_empty_before_the_settings_are_saved() {
		delete_option( 'wcbcf_settings' );

		$order = wc_create_order();
		$order->update_meta_data( '_billing_persontype', '2' );
		$order->save();

		$this->assertSame( '', $this->order_response( $order )['billing']['persontype'] );
	}

	public function test_the_schema_marks_every_added_field_read_only() {
		$api     = new Extra_Checkout_Fields_For_Brazil_API();
		$schemas = array(
			'order'    => $api->orders_schema( $api->addresses_schema( array() ) ),
			'customer' => $api->addresses_schema( array() ),
		);

		foreach ( $schemas as $name => $schema ) {
			foreach ( array( 'billing', 'shipping' ) as $address ) {
				foreach ( $schema[ $address ]['properties'] as $key => $property ) {
					$this->assertTrue( ! empty( $property['readonly'] ), "$name $address $key" );
				}
			}
		}
	}

	public function test_an_order_is_written_through_its_meta_keys() {
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders' );
		$request->set_body_params(
			array(
				'billing'   => array( 'cpf' => '529.982.247-25' ),
				'meta_data' => array(
					array(
						'key'   => '_billing_rg',
						'value' => '12.345.678-9',
					),
				),
			)
		);

		$response = rest_do_request( $request );
		$order    = wc_get_order( $response->get_data()['id'] );

		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( '', $order->get_meta( '_billing_cpf' ) );
		$this->assertSame( '12.345.678-9', $order->get_meta( '_billing_rg' ) );
		$this->assertSame( '123456789', $response->get_data()['billing']['rg'] );
	}
}
