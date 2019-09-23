<?php
/**
 * Brazilian Market on WooCommerce
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Tests\Unit;

use ClaudioSanches\BrazilianMarket\Infrastructure\View\TemplatedViewFactory;
use ClaudioSanches\BrazilianMarket\Infrastructure\ViewFactory;

/**
 * Templated View Factory Test.
 */
final class TemplatedViewFactoryTest extends TestCase {

	/**
	 * Test if it can be instantiated.
	 *
	 * @return void
	 */
	public function test_it_can_be_instantiated() {
		$factory = new TemplatedViewFactory();

		$this->assertInstanceOf( TemplatedViewFactory::class, $factory );
	}

	/**
	 * Test if it implements the interface.
	 *
	 * @return void
	 */
	public function test_it_implements_the_interface() {
		$factory = new TemplatedViewFactory();

		$this->assertInstanceOf( ViewFactory::class, $factory );
	}
}
