<?php
/**
 * Loads the spending goals inner block ahead of the Cart and Checkout blocks,
 * which render the inner blocks registered by then.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Goals
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Extra_Checkout_Fields_For_Brazil_Goals_Integration class.
 */
class Extra_Checkout_Fields_For_Brazil_Goals_Integration implements IntegrationInterface {

	/**
	 * Handle of the inner block script.
	 *
	 * @var string
	 */
	const HANDLE = Extra_Checkout_Fields_For_Brazil_Goals::HANDLE . '-blocks';

	/**
	 * Integration name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'csbmw-goals';
	}

	/**
	 * Register the script.
	 *
	 * @return void
	 */
	public function initialize() {
		Extra_Checkout_Fields_For_Brazil_Assets::register_script( self::HANDLE, 'goals-blocks' );
		Extra_Checkout_Fields_For_Brazil_Assets::set_translations( self::HANDLE );
	}

	/**
	 * Scripts loaded with the blocks, while the bar shows in either.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return Extra_Checkout_Fields_For_Brazil_Goals::shows_in( 'cart' ) || Extra_Checkout_Fields_For_Brazil_Goals::shows_in( 'checkout' ) ? array( self::HANDLE ) : array();
	}

	/**
	 * Scripts loaded in the editor.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array();
	}

	/**
	 * Data passed to the scripts.
	 *
	 * @return array
	 */
	public function get_script_data() {
		return array();
	}
}
