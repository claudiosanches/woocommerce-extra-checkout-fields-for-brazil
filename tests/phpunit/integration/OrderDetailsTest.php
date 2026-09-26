<?php
/**
 * Customer data on the order pages and emails.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

/**
 * Order details test.
 */
class OrderDetailsTest extends WP_UnitTestCase {

	/**
	 * Class under test.
	 *
	 * @var Extra_Checkout_Fields_For_Brazil_Order_Details
	 */
	protected $details;

	/**
	 * Order with an individual's data.
	 *
	 * @var WC_Order
	 */
	protected $order;

	public function set_up() {
		parent::set_up();

		$this->details = new Extra_Checkout_Fields_For_Brazil_Order_Details();

		$this->order = wc_create_order();
		$this->order->update_meta_data( '_billing_persontype', '1' );
		$this->order->update_meta_data( '_billing_cpf', '529.982.247-25' );
		$this->order->update_meta_data( '_billing_rg', '' );
		$this->order->update_meta_data( '_billing_gender', 'female' );
		$this->order->update_meta_data( '_billing_cellphone', '(11) 98765-4321' );
		$this->order->save();
	}

	/**
	 * Only this plugin's contact fields leave WooCommerce's section.
	 */
	public function test_hides_only_this_plugins_contact_fields() {
		$this->assertFalse( $this->details->hide_contact_fields( true, array( 'id' => 'csbmw/cpf' ) ) );
		$this->assertTrue( $this->details->hide_contact_fields( true, array( 'id' => 'other/how-did-you-hear' ) ) );
		$this->assertFalse( $this->details->hide_contact_fields( false, array( 'id' => 'other/hidden' ) ) );
	}

	/**
	 * The confirmation's additional information goes with its last field.
	 */
	public function test_drops_an_empty_additional_information_section() {
		$heading = '<div class="wp-block-woocommerce-order-confirmation-additional-fields-wrapper"><h2>Additional information</h2>';

		$this->assertSame( '', $this->details->drop_empty_additional_fields( $heading . '</div>' ) );

		$filled = $heading . '<dl class="wc-block-components-additional-fields-list"><dt>Gift</dt><dd>Yes</dd></dl></div>';

		$this->assertSame( $filled, $this->details->drop_empty_additional_fields( $filled ) );
	}

	/**
	 * Empty values are skipped and the gender is shown by its label.
	 */
	public function test_lists_the_filled_values() {
		$this->assertSame(
			array(
				array(
					'label' => 'CPF',
					'value' => '529.982.247-25',
				),
				array(
					'label' => 'Gender',
					'value' => 'Female',
				),
				array(
					'label' => 'Cell Phone',
					'value' => '(11) 98765-4321',
				),
			),
			Extra_Checkout_Fields_For_Brazil_Order_Details::get_fields( $this->order )
		);
	}

	/**
	 * The order page and both email formats get the section.
	 */
	public function test_prints_the_section() {
		ob_start();
		$this->details->order_details( $this->order );
		$page = ob_get_clean();

		$this->assertStringContainsString( '<h2>Customer data</h2>', $page );
		$this->assertStringContainsString( '<dt>CPF</dt>', $page );

		ob_start();
		$this->details->email_details( $this->order, false, true );
		$plain = ob_get_clean();

		$this->assertStringContainsString( 'CUSTOMER DATA', $plain );
		$this->assertStringContainsString( 'CPF: 529.982.247-25', $plain );

		ob_start();
		$this->details->email_details( $this->order, false, false );
		$this->assertStringContainsString( '<strong>Cell Phone</strong>', ob_get_clean() );
	}

	/**
	 * An order without any of the data prints nothing.
	 */
	public function test_prints_nothing_without_data() {
		ob_start();
		$this->details->order_details( wc_create_order() );

		$this->assertSame( '', ob_get_clean() );
	}
}
