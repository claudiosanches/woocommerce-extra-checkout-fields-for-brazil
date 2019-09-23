<?php
/**
 * Brazilian Market on WooCommerce
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Tests\Integration;

use ClaudioSanches\BrazilianMarket\Infrastructure\View;
use ClaudioSanches\BrazilianMarket\Infrastructure\View\TemplatedView;
use ClaudioSanches\BrazilianMarket\Infrastructure\View\TemplatedViewFactory;
use ClaudioSanches\BrazilianMarket\Tests\ViewHelper;

/**
 * Templated View Factory Test.
 */
final class TemplatedViewFactoryTest extends TestCase {

	/**
	 * Test if it can create views.
	 *
	 * @return void
	 */
	public function test_it_can_create_views() {
		$factory = new TemplatedViewFactory( ViewHelper::LOCATIONS );

		$view = $factory->create( 'static-view' );
		$this->assertInstanceOf( TemplatedView::class, $view );
	}

	/**
	 * Test if created views implement the interface.
	 *
	 * @return void
	 */
	public function test_created_views_implement_the_interface() {
		$factory = new TemplatedViewFactory( ViewHelper::LOCATIONS );

		$view = $factory->create( 'static-view' );
		$this->assertInstanceOf( View::class, $view );
	}
}
