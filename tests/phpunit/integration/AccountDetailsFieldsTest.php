<?php
/**
 * Tests for what the account details form is allowed to ask for.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;

/**
 * Covers remove_documents_from_account_details.
 */
class AccountDetailsFieldsTest extends WP_UnitTestCase {

	/**
	 * Blocks instance under test.
	 *
	 * @var Extra_Checkout_Fields_For_Brazil_Blocks
	 */
	protected $blocks;

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

		$this->blocks = $this->hooked_blocks();

		$this->register_plugin_fields();
	}

	public function tear_down() {
		$this->on_account_details( false );
		$this->register_plugin_fields();

		parent::tear_down();
	}

	/**
	 * Register this plugin's fields from scratch.
	 *
	 * The registry is global and every test in the suite shares it, so what is
	 * there already goes first rather than being registered twice.
	 *
	 * @return void
	 */
	protected function register_plugin_fields() {
		$controller = Package::container()->get( CheckoutFields::class );

		foreach ( array_keys( $controller->get_additional_fields() ) as $field_id ) {
			if ( '' !== Extra_Checkout_Fields_For_Brazil_Blocks::field_key( $field_id ) ) {
				$controller->deregister_checkout_field( $field_id );
			}
		}

		$this->blocks->register_fields();
	}

	/**
	 * The instance the plugin registered, rather than a second one whose
	 * constructor would hook everything all over again.
	 *
	 * @return Extra_Checkout_Fields_For_Brazil_Blocks
	 */
	protected function hooked_blocks() {
		foreach ( $GLOBALS['wp_filter']['template_redirect'] as $hooks ) {
			foreach ( $hooks as $hook ) {
				if ( is_array( $hook['function'] )
					&& $hook['function'][0] instanceof Extra_Checkout_Fields_For_Brazil_Blocks
					&& 'remove_documents_from_account_details' === $hook['function'][1] ) {
					return $hook['function'][0];
				}
			}
		}

		$this->fail( 'Nothing drops the documents from the account details form.' );
	}

	/**
	 * Keys WooCommerce would render and validate in the contact location.
	 *
	 * @return array
	 */
	protected function contact_keys() {
		return array_keys( Package::container()->get( CheckoutFields::class )->get_fields_for_location( 'contact' ) );
	}

	/**
	 * Put the request on the account details endpoint, or take it off.
	 *
	 * @param bool $is_account_details Whether this is that form.
	 *
	 * @return void
	 */
	protected function on_account_details( $is_account_details ) {
		global $wp;

		$query_vars = WC()->query->get_query_vars();
		$endpoint   = $query_vars['edit-account'];

		if ( $is_account_details ) {
			$wp->query_vars[ $endpoint ] = '';

			return;
		}

		unset( $wp->query_vars[ $endpoint ] );
	}

	public function test_the_documents_are_registered_for_the_checkout() {
		$keys = $this->contact_keys();

		$this->assertContains( 'csbmw/persontype', $keys );
		$this->assertContains( 'csbmw/cpf', $keys );
	}

	/**
	 * The form hides a document it cannot evaluate and then requires it on
	 * submit, which nothing on the page can satisfy.
	 *
	 * @return void
	 */
	public function test_the_documents_are_dropped_on_the_account_details_form() {
		$this->on_account_details( true );
		$this->blocks->remove_documents_from_account_details();

		$keys = $this->contact_keys();

		foreach ( Extra_Checkout_Fields_For_Brazil_Blocks::PERSON_TYPE_FIELDS as $key ) {
			$this->assertNotContains( 'csbmw/' . $key, $keys, $key );
		}
	}

	public function test_the_fields_that_render_there_are_kept() {
		$this->on_account_details( true );
		$this->blocks->remove_documents_from_account_details();

		$keys = $this->contact_keys();

		$this->assertContains( 'csbmw/birthdate', $keys );
		$this->assertContains( 'csbmw/gender', $keys );
		$this->assertContains( 'csbmw/cellphone', $keys );
	}

	public function test_nothing_is_dropped_anywhere_else() {
		$this->on_account_details( false );
		$this->blocks->remove_documents_from_account_details();

		$this->assertContains( 'csbmw/cpf', $this->contact_keys() );
	}

	public function test_it_runs_before_woocommerce_saves_the_form() {
		$ours = null;

		foreach ( $GLOBALS['wp_filter']['template_redirect'] as $priority => $hooks ) {
			foreach ( $hooks as $hook ) {
				if ( is_array( $hook['function'] )
					&& $hook['function'][0] instanceof Extra_Checkout_Fields_For_Brazil_Blocks
					&& 'remove_documents_from_account_details' === $hook['function'][1] ) {
					$ours = $priority;
				}
			}
		}

		$this->assertNotNull( $ours );
		$this->assertLessThan( 10, $ours, 'WC_Form_Handler::save_account_details() runs at 10.' );
	}
}
