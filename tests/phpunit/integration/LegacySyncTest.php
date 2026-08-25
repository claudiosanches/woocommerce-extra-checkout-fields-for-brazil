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

	/**
	 * Ids another extension could register inside this plugin's namespace. The
	 * key becomes a meta key, so anything unrecognised has to be refused.
	 *
	 * @return array
	 */
	public function foreign_namespaced_id_provider() {
		return array(
			'unknown key'      => array( 'csbmw/anything' ),
			'traversal'        => array( 'csbmw/../../evil' ),
			'nested namespace' => array( 'csbmw/acme/cpf' ),
			'empty key'        => array( 'csbmw/' ),
			'near miss'        => array( 'csbmw/cpf2' ),
		);
	}

	/**
	 * @dataProvider foreign_namespaced_id_provider
	 *
	 * @param string $field_id Field id to attempt.
	 */
	public function test_unknown_keys_in_our_namespace_write_nothing( $field_id ) {
		$order  = new WC_Order();
		$before = count( $order->get_meta_data() );

		$this->sync->write_legacy_meta( $field_id, 'value', 'other', $order );

		$this->assertCount( $before, $order->get_meta_data(), $field_id );
	}

	public function test_the_order_hooks_are_wired_up() {
		$sync = new Extra_Checkout_Fields_For_Brazil_Legacy_Sync();

		$hooks = array(
			'woocommerce_set_additional_field_value'                  => 'write_legacy_meta',
			'woocommerce_process_shop_order_meta'                     => 'write_block_meta',
			'woocommerce_checkout_create_order'                       => 'clear_unused_documents',
			'woocommerce_store_api_checkout_update_order_from_request' => 'clear_unused_documents',
			'woocommerce_billing_fields'                              => 'remove_duplicated_account_fields',
			'woocommerce_admin_billing_fields'                        => 'remove_duplicated_admin_fields',
			'woocommerce_filter_fields_for_order_confirmation'        => 'hide_address_fields_from_confirmation',
		);

		foreach ( $hooks as $hook => $method ) {
			$this->assertNotFalse(
				has_filter( $hook, array( $sync, $method ) ),
				"$method is not hooked to $hook"
			);
		}
	}

	public function test_the_historic_fields_survive_on_the_checkout() {
		$fields = WC()->countries->get_address_fields( 'BR', 'billing_' );

		$this->assertArrayHasKey( 'billing_number', $fields );
		$this->assertArrayHasKey( 'billing_neighborhood', $fields );
	}

	public function test_the_historic_fields_are_gone_from_the_account_field_list() {
		global $wp;

		// WC_Form_Handler::save_address() validates and saves from this same
		// list, so removing them from the form alone would break the save.
		$wp->query_vars['edit-address'] = 'billing';
		$fields                         = WC()->countries->get_address_fields( 'BR', 'billing_' );
		unset( $wp->query_vars['edit-address'] );

		$this->assertArrayNotHasKey( 'billing_number', $fields );
		$this->assertArrayNotHasKey( 'billing_neighborhood', $fields );
		$this->assertArrayHasKey( 'billing_cpf', $fields, 'Only the duplicated pair should go' );
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

	/**
	 * Build a customer holding both copies of a field.
	 *
	 * @param string $legacy_key Historic meta key.
	 * @param string $legacy     Historic value.
	 * @param string $block_key  Block meta key.
	 * @param string $block      Block value.
	 *
	 * @return WC_Customer
	 */
	protected function customer_with( $legacy_key, $legacy, $block_key, $block ) {
		$customer = new WC_Customer( $this->factory->user->create( array( 'role' => 'customer' ) ) );
		$customer->update_meta_data( $legacy_key, $legacy );
		$customer->update_meta_data( $block_key, $block );
		$customer->save();

		return $customer;
	}

	/**
	 * The account form edits the historic fields, and WooCommerce only falls
	 * back to them while the block meta is empty, so a correction made there
	 * has to reach the block copy.
	 *
	 * @return void
	 */
	public function test_an_edited_document_reaches_the_block_meta() {
		$customer = $this->customer_with( 'billing_cpf', '111.444.777-35', '_wc_other/csbmw/cpf', '123.456.789-09' );

		$this->sync->write_customer_block_meta( $customer->get_id(), 'billing' );

		$stored = new WC_Customer( $customer->get_id() );

		$this->assertSame( '111.444.777-35', $stored->get_meta( '_wc_other/csbmw/cpf' ) );
	}

	/**
	 * The two stores keep gender in different vocabularies, a translated label
	 * against a stable key, so the value has to be converted on the way across.
	 *
	 * @return void
	 */
	public function test_an_edited_gender_is_converted_to_the_block_vocabulary() {
		$customer = $this->customer_with( 'billing_gender', 'Male', '_wc_other/csbmw/gender', 'female' );

		$this->sync->write_customer_block_meta( $customer->get_id(), 'billing' );

		$stored = new WC_Customer( $customer->get_id() );

		$this->assertSame( 'male', $stored->get_meta( '_wc_other/csbmw/gender' ) );
	}

	/**
	 * A customer who has never checked out through the block checkout has no
	 * block meta, and a saved address must not invent any.
	 *
	 * @return void
	 */
	public function test_a_customer_without_block_meta_does_not_gain_any() {
		$customer = new WC_Customer( $this->factory->user->create( array( 'role' => 'customer' ) ) );
		$customer->update_meta_data( 'billing_cpf', '111.444.777-35' );
		$customer->save();

		$this->sync->write_customer_block_meta( $customer->get_id(), 'billing' );

		$stored = new WC_Customer( $customer->get_id() );

		$this->assertFalse( $stored->meta_exists( '_wc_other/csbmw/cpf' ) );
	}

	/**
	 * Saving the shipping form must not touch the contact fields, which only
	 * ever appear on the billing one.
	 *
	 * @return void
	 */
	public function test_saving_shipping_leaves_the_contact_fields_alone() {
		$customer = $this->customer_with( 'billing_cpf', '111.444.777-35', '_wc_other/csbmw/cpf', '123.456.789-09' );
		$customer->update_meta_data( 'shipping_number', '99' );
		$customer->update_meta_data( '_wc_shipping/csbmw/number', '11' );
		$customer->save();

		$this->sync->write_customer_block_meta( $customer->get_id(), 'shipping' );

		$stored = new WC_Customer( $customer->get_id() );

		$this->assertSame( '99', $stored->get_meta( '_wc_shipping/csbmw/number' ) );
		$this->assertSame( '123.456.789-09', $stored->get_meta( '_wc_other/csbmw/cpf' ) );
	}
}
