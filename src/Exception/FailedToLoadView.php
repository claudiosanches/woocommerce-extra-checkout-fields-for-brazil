<?php
/**
 * Brazilian Market on WooCommerce
 *
 * Inspired in https://github.com/mwpd/basic-scaffold
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Exception;

use RuntimeException;

/**
 * Exception for when fails to load a view.
 */
class FailedToLoadView extends RuntimeException implements ExceptionThrowable {

	/**
	 * Create a new instance of the exception if the view file itself created
	 * an exception.
	 *
	 * @param string     $uri       URI of the file that is not accessible or
	 *                              not readable.
	 * @param \Exception $exception Exception that was thrown by the view file.
	 * @return static
	 */
	public static function from_view_exception( $uri, $exception ) {
		$message = \sprintf(
			'Could not load the View URI "%1$s". Reason: "%2$s".',
			$uri,
			$exception->getMessage()
		);

		return new static( $message, (int) $exception->getCode(), $exception );
	}
}
