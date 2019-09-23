<?php
/**
 * Brazilian Market on WooCommerce
 *
 * Inspired in https://github.com/mwpd/basic-scaffold
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Exception;

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid

/**
 * This is a "marker interface" to mark all the exception that come with this
 * plugin with this one interface.
 */
interface ExceptionThrowable {

	/**
	 * Get message.
	 *
	 * @return string
	 */
	public function getMessage();

	/**
	 * Get code.
	 *
	 * @return int
	 */
	public function getCode();

	/**
	 * GEt file.
	 *
	 * @return string
	 */
	public function getFile();

	/**
	 * Get line.
	 *
	 * @return int
	 */
	public function getLine();

	/**
	 * Get trace.
	 *
	 * @return array
	 */
	public function getTrace();

	/**
	 * Get trance as string.
	 *
	 * @return string
	 */
	public function getTraceAsString();

	/**
	 * Get previous.
	 *
	 * @return ExceptionThrowable
	 */
	public function getPrevious();

	/**
	 * To string magic method.
	 *
	 * @return string
	 */
	public function __toString();
}
