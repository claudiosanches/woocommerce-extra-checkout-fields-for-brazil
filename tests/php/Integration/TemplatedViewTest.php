<?php
/**
 * Brazilian Market on WooCommerce
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Tests\Integration;

use ClaudioSanches\BrazilianMarket\Infrastructure\View\TemplatedView;
use ClaudioSanches\BrazilianMarket\Infrastructure\View\TemplatedViewFactory;
use ClaudioSanches\BrazilianMarket\Tests\ViewHelper;

/**
 * Templated View Test.
 */
final class TemplatedViewTest extends TestCase {

	/**
	 * Test if it loads partials across overrides.
	 *
	 * @return void
	 */
	public function test_it_loads_partials_across_overrides() {
		$partials = new TemplatedView(
			'partial-a',
			new TemplatedViewFactory( ViewHelper::LOCATIONS ),
			ViewHelper::LOCATIONS
		);

		$this->assertStringStartsWith(
			'partial A from plugin - partial B from parent theme - partial C from child theme - partial D from parent theme - partial E from plugin',
			$partials->render()
		);
	}
}
