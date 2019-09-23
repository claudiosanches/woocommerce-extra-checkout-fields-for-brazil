<?php
/**
 * Brazilian Market on WooCommerce
 *
 * Inspired in https://github.com/mwpd/basic-scaffold
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Exception;

use InvalidArgumentException;

/**
 * Exception for context property.
 */
class InvalidContextProperty extends InvalidArgumentException implements ExceptionThrowable {

	/**
	 * Create a new instance of the exception for a context property that is
	 * not recognized.
	 *
	 * @param string $property Name of the context property that was not
	 *                         recognized.
	 * @return static
	 */
	public static function from_property( $property ) {
		$message = \sprintf(
			'The property "%s" could not be found within the context of the currently rendered view.',
			$property
		);

		return new static( $message );
	}
}
