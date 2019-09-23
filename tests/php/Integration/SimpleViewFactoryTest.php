<?php
/**
 * Brazilian Market on WooCommerce
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Tests\Integration;

use ClaudioSanches\BrazilianMarket\Infrastructure\View;
use ClaudioSanches\BrazilianMarket\Infrastructure\View\SimpleView;
use ClaudioSanches\BrazilianMarket\Infrastructure\View\SimpleViewFactory;
use ClaudioSanches\BrazilianMarket\Tests\ViewHelper;

/**
 * Simple View Factory Test.
 */
final class SimpleViewFactoryTest extends TestCase {

	/**
	 * Test if it can create views.
	 *
	 * @return void
	 */
	public function test_it_can_create_views() {
		$factory = new SimpleViewFactory();

		$view = $factory->create( ViewHelper::VIEWS_FOLDER . 'static-view' );
		$this->assertInstanceOf( SimpleView::class, $view );
	}

	/**
	 * Test if created views implement the interface.
	 *
	 * @return void
	 */
	public function test_created_views_implement_the_interface() {
		$factory = new SimpleViewFactory();

		$view = $factory->create( ViewHelper::VIEWS_FOLDER . 'static-view' );
		$this->assertInstanceOf( View::class, $view );
	}
}
