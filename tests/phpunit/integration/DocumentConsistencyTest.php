<?php
/**
 * Tests for the rules that keep an order's documents consistent.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

/**
 * Covers clear_unused_documents, length capping and birthdate normalisation.
 */
class DocumentConsistencyTest extends WP_UnitTestCase {

	/**
	 * Sync instance under test.
	 *
	 * @var Extra_Checkout_Fields_For_Brazil_Legacy_Sync
	 */
	protected $sync;

	public function set_up() {
		parent::set_up();
		$this->sync = new Extra_Checkout_Fields_For_Brazil_Legacy_Sync();
	}

	/**
	 * Build an order carrying every document, in both meta families.
	 *
	 * @param string $person_type Submitted person type.
	 *
	 * @return WC_Order
	 */
	protected function order_with_all_documents( $person_type ) {
		$order = new WC_Order();
		$order->update_meta_data( '_billing_persontype', $person_type );

		foreach ( array( 'cpf', 'rg', 'cnpj', 'ie' ) as $key ) {
			$order->update_meta_data( '_billing_' . $key, 'value-' . $key );
			$order->update_meta_data( '_wc_other/csbmw/' . $key, 'value-' . $key );
		}

		return $order;
	}

	/**
	 * Documents an order should still carry, per settings and person type.
	 *
	 * @return array
	 */
	public function person_type_provider() {
		return array(
			'asks, individual chosen'   => array( 1, '1', array( 'cpf', 'rg' ), array( 'cnpj', 'ie' ) ),
			'asks, legal person chosen' => array( 1, '2', array( 'cnpj', 'ie' ), array( 'cpf', 'rg' ) ),
			'individuals only'          => array( 2, '', array( 'cpf', 'rg' ), array( 'cnpj', 'ie' ) ),
			'legal persons only'        => array( 3, '', array( 'cnpj', 'ie' ), array( 'cpf', 'rg' ) ),
		);
	}

	/**
	 * @dataProvider person_type_provider
	 *
	 * @param int    $setting     person_type setting.
	 * @param string $submitted   Submitted person type.
	 * @param array  $kept        Documents that must survive.
	 * @param array  $cleared     Documents that must be emptied.
	 */
	public function test_documents_of_the_other_person_type_are_cleared( $setting, $submitted, $kept, $cleared ) {
		update_option( 'wcbcf_settings', array( 'person_type' => $setting ) );

		$order = $this->order_with_all_documents( $submitted );
		$this->sync->clear_unused_documents( $order );

		foreach ( $kept as $key ) {
			$this->assertSame( 'value-' . $key, $order->get_meta( '_billing_' . $key ), $key );
		}

		foreach ( $cleared as $key ) {
			$this->assertSame( '', $order->get_meta( '_billing_' . $key ), $key );
			$this->assertSame( '', $order->get_meta( '_wc_other/csbmw/' . $key ), $key );
		}
	}

	public function test_nothing_is_cleared_when_documents_are_switched_off() {
		update_option( 'wcbcf_settings', array( 'person_type' => 0 ) );

		$order = $this->order_with_all_documents( '1' );
		$this->sync->clear_unused_documents( $order );

		$this->assertSame( 'value-cnpj', $order->get_meta( '_billing_cnpj' ) );
	}

	public function test_nothing_is_cleared_when_no_person_type_was_submitted() {
		update_option( 'wcbcf_settings', array( 'person_type' => 1 ) );

		$order = $this->order_with_all_documents( '' );
		$this->sync->clear_unused_documents( $order );

		$this->assertSame( 'value-cpf', $order->get_meta( '_billing_cpf' ) );
		$this->assertSame( 'value-cnpj', $order->get_meta( '_billing_cnpj' ) );
	}

	public function test_values_are_capped_to_the_length_of_their_field() {
		$blocks = new Extra_Checkout_Fields_For_Brazil_Blocks();

		foreach ( Extra_Checkout_Fields_For_Brazil_Blocks::MAX_LENGTHS as $key => $max ) {
			$value = $blocks->sanitize_field(
				str_repeat( 'A', $max + 100 ),
				array( 'id' => Extra_Checkout_Fields_For_Brazil_Blocks::field_id( $key ) )
			);

			$this->assertSame( $max, mb_strlen( $value ), $key );
		}
	}

	public function test_capping_does_not_split_a_multibyte_character() {
		$blocks = new Extra_Checkout_Fields_For_Brazil_Blocks();
		$value  = $blocks->sanitize_field(
			str_repeat( 'ção', 100 ),
			array( 'id' => 'csbmw/neighborhood' )
		);

		$this->assertSame( 100, mb_strlen( $value ) );
		$this->assertSame( $value, wp_check_invalid_utf8( $value ) );
	}

	public function test_sanitizing_trims_and_leaves_non_strings_alone() {
		$blocks = new Extra_Checkout_Fields_For_Brazil_Blocks();

		$this->assertSame( 'x', $blocks->sanitize_field( '  x  ', array( 'id' => 'csbmw/rg' ) ) );
		$this->assertNull( $blocks->sanitize_field( null, array( 'id' => 'csbmw/rg' ) ) );
	}

	/**
	 * Birthdates the classic checkout may have stored, and what the block gets.
	 *
	 * @return array
	 */
	public function birthdate_provider() {
		return array(
			'already correct' => array( '01/02/1990', '01/02/1990' ),
			'unpadded'        => array( '1/1/1980', '01/01/1980' ),
			'iso'             => array( '1980-01-01', '01/01/1980' ),
			'dashes'          => array( '01-02-1990', '01/02/1990' ),
			'impossible date' => array( '31/02/1990', '' ),
			'free text'       => array( 'ontem', '' ),
			'empty'           => array( '', '' ),
		);
	}

	/**
	 * @dataProvider birthdate_provider
	 *
	 * @param string $stored   Historic value.
	 * @param string $expected Value handed to the block.
	 */
	public function test_historic_birthdates_are_normalised( $stored, $expected ) {
		$this->assertSame(
			$expected,
			Extra_Checkout_Fields_For_Brazil_Legacy_Sync::to_block_value( 'birthdate', $stored )
		);
	}

	public function test_an_admin_save_updates_block_meta_that_was_stored_empty() {
		$order = new WC_Order();
		$order->update_meta_data( '_wc_other/csbmw/rg', '' );
		$order->save();

		$order->update_meta_data( '_billing_rg', '998877' );
		$order->save();

		$this->sync->write_block_meta( $order->get_id() );

		$this->assertSame( '998877', wc_get_order( $order->get_id() )->get_meta( '_wc_other/csbmw/rg' ) );
	}

	public function test_address_fields_are_kept_out_of_the_order_confirmation() {
		$hidden = $this->sync->hide_address_fields_from_confirmation( true, array( 'id' => 'csbmw/number' ) );
		$this->assertFalse( $hidden );

		$kept = $this->sync->hide_address_fields_from_confirmation( true, array( 'id' => 'csbmw/cpf' ) );
		$this->assertTrue( $kept );

		$other = $this->sync->hide_address_fields_from_confirmation( true, array( 'id' => 'other/number' ) );
		$this->assertTrue( $other );
	}

	public function test_historic_address_fields_are_dropped_only_on_the_account_page() {
		global $wp;

		$fields = array(
			'billing_number'       => array( 'label' => 'Number' ),
			'billing_neighborhood' => array( 'label' => 'Neighborhood' ),
			'billing_cpf'          => array( 'label' => 'CPF' ),
		);

		$this->assertSame(
			array_keys( $fields ),
			array_keys( $this->sync->remove_duplicated_account_fields( $fields ) ),
			'Checkout fields must be left alone'
		);

		$wp->query_vars['edit-address'] = 'billing';
		$filtered                       = $this->sync->remove_duplicated_account_fields( $fields );
		unset( $wp->query_vars['edit-address'] );

		$this->assertSame( array( 'billing_cpf' ), array_keys( $filtered ) );
	}

	public function test_a_third_party_field_is_not_stripped_from_the_order_screen() {
		$fields = array(
			'ours'  => array( 'id' => '_wc_other/csbmw/cpf' ),
			'their' => array( 'id' => '_wc_other/acme/csbmw/thing' ),
		);

		$this->assertSame(
			array( 'their' ),
			array_keys( $this->sync->remove_duplicated_admin_fields( $fields ) )
		);
	}

	public function test_company_is_never_required_by_the_address_field_list() {
		update_option( 'wcbcf_settings', array( 'person_type' => 3 ) );

		$front  = new Extra_Checkout_Fields_For_Brazil_Front_End();
		$fields = $front->restore_company_field( array() );

		$this->assertArrayHasKey( 'company', $fields );
		$this->assertFalse( $fields['company']['required'] );

		$locales = $front->address_fields_priority( array() );
		$this->assertFalse( $locales['BR']['company']['required'] );
		$this->assertFalse( $locales['BR']['company']['hidden'] );
	}

	/**
	 * Read what the classic checkout would prefill for a stored birthdate.
	 *
	 * `WC_Checkout` caches the logged in customer for the whole request, so the
	 * cache is cleared before each read.
	 *
	 * @param string $stored Value on the customer.
	 *
	 * @return string
	 */
	protected function prefilled_birthdate( $stored ) {
		$customer_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		update_user_meta( $customer_id, 'billing_birthdate', $stored );
		wp_set_current_user( $customer_id );

		$checkout = WC()->checkout();
		$cache    = new ReflectionProperty( WC_Checkout::class, 'logged_in_customer' );
		$cache->setAccessible( true );
		$cache->setValue( $checkout, null );

		return (string) $checkout->get_value( 'billing_birthdate' );
	}

	/**
	 * The classic checkout has to prefill a normalised birthdate.
	 *
	 * This goes through WooCommerce's own `get_value()` rather than calling the
	 * callback, because the filter it used to be attached to short circuits
	 * with `null` and the callback never saw a value. Asserting that a filter
	 * is registered would not have caught it.
	 *
	 * @return void
	 */
	public function test_the_classic_checkout_prefills_a_normalised_birthdate() {
		update_option( 'wcbcf_settings', array( 'person_type' => 1, 'birthdate' => 1 ) );
		new Extra_Checkout_Fields_For_Brazil_Front_End();

		$this->assertSame( '01/01/1980', $this->prefilled_birthdate( '1/1/1980' ) );
	}

	/**
	 * An unparseable birthdate is left alone rather than blanked.
	 *
	 * @return void
	 */
	public function test_an_unreadable_birthdate_survives_the_prefill() {
		update_option( 'wcbcf_settings', array( 'person_type' => 1, 'birthdate' => 1 ) );
		new Extra_Checkout_Fields_For_Brazil_Front_End();

		$this->assertSame( 'not a date', $this->prefilled_birthdate( 'not a date' ) );
	}
}
