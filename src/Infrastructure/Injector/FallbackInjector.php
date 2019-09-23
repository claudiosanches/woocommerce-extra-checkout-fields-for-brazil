<?php
/**
 * Brazilian Market on WooCommerce
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Infrastructure\Injector;

use ClaudioSanches\BrazilianMarket\Infrastructure\Instantiator;

/**
 * Fallback injector.
 */
final class FallbackInjector implements Instantiator {

	/**
	 * Make an object instance out of an interface or class.
	 *
	 * @param string $class        Class to make an object instance out of.
	 * @param array  $dependencies Optional. Dependencies of the class.
	 * @return object Instantiated object.
	 */
	public function instantiate( $class, $dependencies = [] ) {
		return new $class( ...$dependencies );
	}
}
