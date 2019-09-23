<?php
/**
 * Brazilian Market on WooCommerce
 *
 * Inspired in https://github.com/mwpd/basic-scaffold
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Infrastructure\View;

use ClaudioSanches\BrazilianMarket\Infrastructure\Service;
use ClaudioSanches\BrazilianMarket\Infrastructure\View;
use ClaudioSanches\BrazilianMarket\Infrastructure\ViewFactory;

/**
 * A factory to create templated views.
 *
 * If you don't provide the optional locations array, it will default to (in
 * this exact order):
 *  1. child theme folder
 *  2. parent theme folder
 *  3. plugin folder
 */
final class TemplatedViewFactory implements Service, ViewFactory {

	/**
	 * List of locations.
	 *
	 * @var array<string>
	 */
	private $locations;

	/**
	 * Instantiate a TemplatedViewFactory object.
	 *
	 * @param array $locations Array of locations to use.
	 */
	public function __construct( $locations = [] ) {
		if ( empty( $locations ) ) {
			$locations = $this->get_default_locations();
		}

		$this->locations = $locations;
	}

	/**
	 * Create a new view object for a given relative path.
	 *
	 * @param string $relative_path Relative path to create the view for.
	 * @return View Instantiated view object.
	 */
	public function create( $relative_path ) {
		return new TemplatedView( $relative_path, $this, $this->locations );
	}

	/**
	 * Get the default locations for the templated view.
	 *
	 * Uses internal caching to avoid retrieving the paths multiple times across
	 * instantiations.
	 *
	 * @return array Array of default locations.
	 */
	private function get_default_locations() {
		static $default_locations = null;

		if ( null === $default_locations ) {
			// We wrap the WP functions here to not make the code directly rely
			// on WordPress being loaded here.
			// This makes the code more flexible and testing easier.
			$default_locations = ( function_exists( 'get_stylesheet_directory' ) && function_exists( 'get_template_directory' ) )
				? [
					\get_stylesheet_directory(),
					\get_template_directory(),
					\dirname( __DIR__, 3 ),
				]
				: [ \dirname( __DIR__, 3 ) ];
		}

		return $default_locations;
	}
}
