<?php
/**
 * Brazilian Market on WooCommerce
 *
 * Inspired in https://github.com/mwpd/basic-scaffold
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Infrastructure\View;

use ClaudioSanches\BrazilianMarket\Exception\InvalidPath;
use ClaudioSanches\BrazilianMarket\Infrastructure\ViewFactory;

/**
 * A templated variation of the simplified view object.
 *
 * It has an ordered list of locations and traverses these until it finds a
 * matching view.
 */
final class TemplatedView extends SimpleView {

	/**
	 * List of locations.
	 *
	 * @var array<string>
	 */
	private $locations;

	/**
	 * Instantiate a TemplatedView object.
	 *
	 * @param string      $path         Path to the view file to render.
	 * @param ViewFactory $view_factory View factory instance to use.
	 * @param array       $locations    Optional. Array of locations to use.
	 */
	public function __construct(
		$path,
		ViewFactory $view_factory,
		$locations = []
	) {
		$this->locations = array_map( [ $this, 'ensure_trailing_slash' ], $locations );
		parent::__construct( $path, $view_factory );
	}

	/**
	 * Add a location to the templated view.
	 *
	 * @param string $location Location to add.
	 * @return self Modified templated view.
	 */
	public function add_location( $location ) {
		$this->locations[] = $this->ensure_trailing_slash( $location );

		return $this;
	}

	/**
	 * Validate a path.
	 *
	 * @throws InvalidPath If an invalid path was passed into the View.
	 * @param string $path Path to validate.
	 * @return string Validated Path.
	 */
	protected function validate( $path ) {
		$path = $this->check_extension( $path, static::VIEW_EXTENSION );

		foreach ( $this->get_locations( $path ) as $location ) {
			if ( \is_readable( $location ) ) {
				return $location;
			}
		}

		if ( ! \is_readable( $path ) ) {
			throw InvalidPath::from_path( $path );
		}

		return $path;
	}

	/**
	 * Get the possible locations for the view.
	 *
	 * @param string $path Path of the view to get the locations for.
	 * @return array Array of possible locations.
	 */
	private function get_locations( $path ) {
		return array_map(
			function( $location ) use ( $path ) {
				return "{$location}{$path}";
			},
			$this->locations
		);
	}
}
