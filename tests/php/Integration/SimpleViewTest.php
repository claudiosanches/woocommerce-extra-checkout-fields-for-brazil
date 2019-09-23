<?php
/**
 * Brazilian Market on WooCommerce
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Tests\Integration;

use ClaudioSanches\BrazilianMarket\Infrastructure\View\SimpleView;
use ClaudioSanches\BrazilianMarket\Infrastructure\View\SimpleViewFactory;
use ClaudioSanches\BrazilianMarket\Tests\ViewHelper;

/**
 * Simple View Test.
 */
final class SimpleViewTest extends TestCase {

	/**
	 * Test if it loads partials across overrides.
	 *
	 * @return void
	 */
	public function test_it_loads_partials_across_overrides() {
		$view = new SimpleView(
			ViewHelper::VIEWS_FOLDER . 'static-view',
			new SimpleViewFactory()
		);

		$this->assertStringStartsWith(
			'<p>Rendering works.</p>',
			$view->render()
		);
	}
}
