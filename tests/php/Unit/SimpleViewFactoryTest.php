<?php
/**
 * Brazilian Market on WooCommerce
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Tests\Unit;

use ClaudioSanches\BrazilianMarket\Infrastructure\View\SimpleViewFactory;
use ClaudioSanches\BrazilianMarket\Infrastructure\ViewFactory;

/**
 * Simple View Factory Test.
 */
final class SimpleViewFactoryTest extends TestCase {

	/**
	 * Test if it can be instantiated.
	 *
	 * @return void
	 */
	public function test_it_can_be_instantiated() {
		$factory = new SimpleViewFactory();

		$this->assertInstanceOf( SimpleViewFactory::class, $factory );
	}

	/**
	 * Test if it implements the interface.
	 *
	 * @return void
	 */
	public function test_it_implements_the_interface() {
		$factory = new SimpleViewFactory();

		$this->assertInstanceOf( ViewFactory::class, $factory );
	}
}
