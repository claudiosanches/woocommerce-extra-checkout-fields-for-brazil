<?php
/**
 * Brazilian Market on WooCommerce
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Tests\Fixture;

/**
 * Dummy Class With Named Arguments.
 */
final class DummyClassWithNamedArguments {

	/**
	 * Argument A.
	 *
	 * @var int
	 */
	private $argument_a;

	/**
	 * Argument B.
	 *
	 * @var string
	 */
	private $argument_b;

	/**
	 * Class constructor.
	 *
	 * @param int    $argument_a Argument A.
	 * @param string $argument_b Argument B.
	 */
	public function __construct( $argument_a, $argument_b = 'Mr Meeseeks' ) {
		$this->argument_a = $argument_a;
		$this->argument_b = $argument_b;
	}

	/**
	 * Get argument A.
	 *
	 * @return int
	 */
	public function get_argument_a() {
		return $this->argument_a;
	}

	/**
	 * Get argument B.
	 *
	 * @return string
	 */
	public function get_argument_b() {
		return $this->argument_b;
	}
}
