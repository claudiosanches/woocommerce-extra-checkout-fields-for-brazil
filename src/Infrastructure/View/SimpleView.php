<?php
/**
 * Brazilian Market on WooCommerce
 *
 * Inspired in https://github.com/mwpd/basic-scaffold
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Infrastructure\View;

use ClaudioSanches\BrazilianMarket\Exception\ExceptionThrowable;
use ClaudioSanches\BrazilianMarket\Exception\FailedToLoadView;
use ClaudioSanches\BrazilianMarket\Exception\InvalidContextProperty;
use ClaudioSanches\BrazilianMarket\Exception\InvalidPath;
use ClaudioSanches\BrazilianMarket\Infrastructure\View;
use ClaudioSanches\BrazilianMarket\Infrastructure\ViewFactory;

/**
 * A simplified implementation of a renderable view object.
 */
class SimpleView implements View {

	/**
	 * Extension to use for view files.
	 */
	protected const VIEW_EXTENSION = 'php';

	/**
	 * Path to the view file to render.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Internal storage for passed-in context.
	 *
	 * @var array
	 */
	protected $context = [];

	/**
	 * ViewFactory instance.
	 *
	 * @var ViewFactory
	 */
	protected $view_factory;

	/**
	 * Instantiate a SimpleView object.
	 *
	 * @throws InvalidPath If an invalid Path was passed into the View.
	 * @param string      $path         Path to the view file to render.
	 * @param ViewFactory $view_factory View factory instance to use.
	 */
	public function __construct( $path, ViewFactory $view_factory ) {
		$this->path         = $this->validate( $path );
		$this->view_factory = $view_factory;
	}

	/**
	 * Render the current view with a given context.
	 *
	 * @throws FailedToLoadView If the View path could not be loaded.
	 * @param array $context Context in which to render.
	 * @return string Rendered HTML.
	 */
	public function render( $context = [] ) {
		// Add entire context as array to the current instance to pass onto
		// partial views.
		$this->context = $context;

		// Save current buffering level so we can backtrack in case of an error.
		// This is needed because the view itself might also add an unknown
		// number of output buffering levels.
		$buffer_level = \ob_get_level();
		\ob_start();

		try {
			include $this->path;
		} catch ( \Exception $exception ) {
			// Remove whatever levels were added up until now.
			while ( \ob_get_level() > $buffer_level ) {
				\ob_end_clean();
			}
			throw FailedToLoadView::from_view_exception(
				$this->path,
				$exception
			);
		}

		return \ob_get_clean() ?: '';
	}

	/**
	 * Render a partial view.
	 *
	 * This can be used from within a currently rendered view, to include
	 * nested partials.
	 *
	 * The passed-in context is optional, and will fall back to the parent's
	 * context if omitted.
	 *
	 * @param string     $path    Path of the partial to render.
	 * @param array|null $context Context in which to render the partial.
	 *
	 * @return string Rendered HTML.
	 * @throws InvalidPath If the provided path was not valid.
	 * @throws FailedToLoadView If the view could not be loaded.
	 */
	public function render_partial( $path, $context = null ) {
		return $this->view_factory->create( $path )->render( $context ?: $this->context );
	}

	/**
	 * Return the raw value of a context property.
	 *
	 * By default, properties are automatically escaped when accessing them
	 * within the view. This method allows direct access to the raw value
	 * instead to bypass this automatic escaping.
	 *
	 * @throws InvalidContextProperty If have invalid context property.
	 * @param string $property Property for which to return the raw value.
	 * @return mixed Raw context property value.
	 */
	public function raw( $property ) {
		if ( array_key_exists( $property, $this->context ) ) {
			return $this->context[ $property ];
		}

		/*
		 * We only throw an exception here if we are in debugging mode, as we
		 * don't want to take the server down when trying to render a missing
		 * property.
		 */
		if ( $this->is_debug_mode() ) {
			throw InvalidContextProperty::from_property( $property );
		}

		return null;
	}

	/**
	 * Validate a path.
	 *
	 * @throws InvalidPath If an invalid path was passed into the View.
	 * @param string $path Path to validate.
	 * @return string Validated path.
	 */
	protected function validate( $path ) {
		$path = $this->check_extension( $path, static::VIEW_EXTENSION );
		$path = $this->ensure_trailing_slash( \dirname( __DIR__, 3 ) ) . $path;

		if ( ! \is_readable( $path ) ) {
			throw InvalidPath::from_path( $path );
		}

		return $path;
	}

	/**
	 * Check that the path has the correct extension.
	 *
	 * Optionally adds the extension if none was detected.
	 *
	 * @param string $path      Path to check the extension of.
	 * @param string $extension Extension to use.
	 * @return string Path with correct extension.
	 */
	protected function check_extension( $path, $extension ) {
		$detected_extension = \pathinfo( $path, PATHINFO_EXTENSION );

		if ( $extension !== $detected_extension ) {
			$path .= '.' . $extension;
		}

		return $path;
	}

	/**
	 * Ensure the path has a trailing slash.
	 *
	 * @param string $path Path to maybe add a trailing slash.
	 * @return string Path with trailing slash.
	 */
	protected function ensure_trailing_slash( $path ) {
		return \rtrim( $path, '/\\' ) . '/';
	}

	/**
	 * Use magic getter to provide automatic escaping by default.
	 *
	 * Use the raw() method to skip automatic escaping.
	 *
	 * @throws InvalidContextProperty If have invalid context property.
	 * @param string $property Property to get.
	 * @return mixed
	 */
	public function __get( $property ) {
		if ( array_key_exists( $property, $this->context ) ) {
			$value = $this->context[ $property ];
			try {
				return $this->escape( (string) $this->context[ $property ] );
			} catch ( ExceptionThrowable $exception ) {
				// Value could not be converted to string, so just return as-is.
				return $value;
			}
		}

		/*
		 * We only throw an exception here if we are in debugging mode, as we
		 * don't want to take the server down when trying to render a missing
		 * property.
		 */
		if ( $this->is_debug_mode() ) {
			throw InvalidContextProperty::from_property( $property );
		}

		return null;
	}

	/**
	 * Escape a value for output.
	 *
	 * @param mixed $value Value to escape.
	 * @return string Escaped value.
	 */
	protected function escape( $value ) {
		return htmlspecialchars( (string) $value, ENT_COMPAT, 'UTF-8' );
	}

	/**
	 * Check whether debugging mode is enabled.
	 *
	 * @return bool Whether debugging mode is enabled.
	 */
	protected function is_debug_mode() {
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}
}
