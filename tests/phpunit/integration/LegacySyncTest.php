<?php
/**
 * Tests for the block/legacy meta mirroring.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

/**
 * Extra_Checkout_Fields_For_Brazil_Legacy_Sync tests.
 */
class LegacySyncTest extends WP_UnitTestCase {

	/**
	 * Sync instance under test.
	 *
	 * @var Extra_Checkout_Fields_For_Brazil_Legacy_Sync
	 */
	protected $sync;

	public function set_up() {
		parent::set_up();

		update_option(
			'wcbcf_settings',
			array(
				'person_type' => 1,
				'rg'          => 1,
				'ie'          => 1,
				'birthdate'   => 1,
				'gender'      => 1,
				'cell_phone'  => '2',
			)
		);

		$this->sync = new Extra_Checkout_Fields_For_Brazil_Legacy_Sync();
	}

	public function test_orders_use_underscore_prefixed_keys() {
		$order = new WC_Order();

		$this->assertSame(
			'_billing_cpf',
			Extra_Checkout_Fields_For_Brazil_Legacy_Sync::get_legacy_key( 'cpf', 'other', $order )
		);
		$this->assertSame(
			'_shipping_number',
			Extra_Checkout_Fields_For_Brazil_Legacy_Sync::get_legacy_key( 'number', 'shipping', $order )
		);
	}

	public function test_customers_use_bare_keys() {
		$customer = new WC_Customer();

		$this->assertSame(
			'billing_cpf',
			Extra_Checkout_Fields_For_Brazil_Legacy_Sync::get_legacy_key( 'cpf', 'other', $customer )
		);
		$this->assertSame(
			'shipping_neighborhood',
			Extra_Checkout_Fields_For_Brazil_Legacy_Sync::get_legacy_key( 'neighborhood', 'shipping', $customer )
		);
	}

	public function test_block_keys_are_grouped_by_location() {
		$this->assertSame(
			'_wc_other/csbmw/cpf',
			Extra_Checkout_Fields_For_Brazil_Legacy_Sync::get_block_key( 'cpf', 'other' )
		);
		$this->assertSame(
			'_wc_billing/csbmw/number',
			Extra_Checkout_Fields_For_Brazil_Legacy_Sync::get_block_key( 'number', 'billing' )
		);
		$this->assertSame(
			'_wc_shipping/csbmw/number',
			Extra_Checkout_Fields_For_Brazil_Legacy_Sync::get_block_key( 'number', 'shipping' )
		);
	}

	public function test_saving_a_block_field_writes_the_legacy_meta() {
		$order = new WC_Order();

		$this->sync->write_legacy_meta( 'csbmw/cpf', '111.444.777-35', 'other', $order );
		$this->sync->write_legacy_meta( 'csbmw/number', '1578', 'billing', $order );
		$this->sync->write_legacy_meta( 'csbmw/number', '99', 'shipping', $order );

		$this->assertSame( '111.444.777-35', $order->get_meta( '_billing_cpf' ) );
		$this->assertSame( '1578', $order->get_meta( '_billing_number' ) );
		$this->assertSame( '99', $order->get_meta( '_shipping_number' ) );
	}

	public function test_fields_from_other_extensions_are_left_alone() {
		$order = new WC_Order();

		$this->sync->write_legacy_meta( 'other-plugin/cpf', 'value', 'other', $order );

		$this->assertSame( '', $order->get_meta( '_billing_cpf' ) );
	}

	public function test_gender_is_stored_as_a_label_in_the_legacy_meta() {
		$order = new WC_Order();

		$this->sync->write_legacy_meta( 'csbmw/gender', 'female', 'other', $order );

		$this->assertSame( 'Female', $order->get_meta( '_billing_gender' ) );
	}

	public function test_gender_labels_map_back_to_their_stable_value() {
		$this->assertSame(
			'female',
			Extra_Checkout_Fields_For_Brazil_Legacy_Sync::to_block_value( 'gender', 'Female' )
		);
		$this->assertSame(
			'female',
			Extra_Checkout_Fields_For_Brazil_Legacy_Sync::to_block_value( 'gender', 'female' )
		);
		$this->assertSame(
			'',
			Extra_Checkout_Fields_For_Brazil_Legacy_Sync::to_block_value( 'gender', 'Nonsense' )
		);
	}

	public function test_legacy_meta_seeds_a_block_field_with_no_value() {
		$customer = new WC_Customer();
		$customer->update_meta_data( 'billing_cpf', '123.456.789-09' );

		$this->assertSame(
			'123.456.789-09',
			apply_filters( 'woocommerce_get_default_value_for_csbmw/cpf', null, 'other', $customer )
		);
	}

	public function test_seeding_is_skipped_when_there_is_no_legacy_value() {
		$customer = new WC_Customer();

		$this->assertNull(
			apply_filters( 'woocommerce_get_default_value_for_csbmw/cpf', null, 'other', $customer )
		);
	}

	public function test_an_admin_save_copies_the_legacy_meta_into_the_block_meta() {
		$order = new WC_Order();
		$order->update_meta_data( '_wc_other/csbmw/cpf', '111.444.777-35' );
		$order->update_meta_data( '_wc_billing/csbmw/number', '1578' );
		$order->save();

		// What the admin order screen would have written.
		$order->update_meta_data( '_billing_cpf', '123.456.789-09' );
		$order->update_meta_data( '_billing_number', '2000' );
		$order->save();

		$this->sync->write_block_meta( $order->get_id() );

		$saved = wc_get_order( $order->get_id() );
		$this->assertSame( '123.456.789-09', $saved->get_meta( '_wc_other/csbmw/cpf' ) );
		$this->assertSame( '2000', $saved->get_meta( '_wc_billing/csbmw/number' ) );
	}

	public function test_an_admin_save_does_not_invent_block_meta() {
		$order = new WC_Order();
		$order->update_meta_data( '_billing_cpf', '123.456.789-09' );
		$order->save();

		$this->sync->write_block_meta( $order->get_id() );

		$saved = wc_get_order( $order->get_id() );
		$this->assertSame( '', $saved->get_meta( '_wc_other/csbmw/cpf' ) );
	}

	public function test_registered_fields_are_removed_from_the_admin_order_screen() {
		$fields = array(
			'first_name'   => array( 'label' => 'First name' ),
			'csbmw/cpf'    => array(
				'id'    => '_wc_other/csbmw/cpf',
				'label' => 'CPF',
			),
			// array_splice drops the string key of address fields.
			0              => array(
				'id'    => '_wc_billing/csbmw/number',
				'label' => 'Number',
			),
			'other/field'  => array(
				'id'    => '_wc_other/other/field',
				'label' => 'Someone else',
			),
		);

		$filtered = $this->sync->remove_duplicated_admin_fields( $fields );

		$this->assertSame( array( 'first_name', 'other/field' ), array_keys( $filtered ) );
	}
}
