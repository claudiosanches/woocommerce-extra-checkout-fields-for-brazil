<?php
/**
 * Brazilian Market on WooCommerce
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Tests\Unit;

use ClaudioSanches\BrazilianMarket\Infrastructure\View\SimpleView;
use ClaudioSanches\BrazilianMarket\Infrastructure\ViewFactory;
use ClaudioSanches\BrazilianMarket\Tests\ViewHelper;

/**
 * Simple View Test.
 */
final class SimpleViewTest extends TestCase {

	/**
	 * Test if it can be initialized.
	 *
	 * @return void
	 */
	public function test_it_can_be_initialized() {
		$view_factory_mock = $this->createMock( ViewFactory::class );
		$view              = new SimpleView(
			ViewHelper::VIEWS_FOLDER . 'static-view',
			$view_factory_mock
		);

		$this->assertInstanceOf( SimpleView::class, $view );
	}

	/**
	 * Test if it can be rendered.
	 *
	 * @return void
	 */
	public function test_it_can_be_rendered() {
		$view_factory_mock = $this->createMock( ViewFactory::class );
		$view              = new SimpleView(
			ViewHelper::VIEWS_FOLDER . 'static-view',
			$view_factory_mock
		);

		$this->assertStringStartsWith(
			'<p>Rendering works.</p>',
			$view->render()
		);
	}

	/**
	 * Test if it can provide rendering context.
	 *
	 * @return void
	 */
	public function test_it_can_provide_rendering_context() {
		$view_factory_mock = $this->createMock( ViewFactory::class );
		$view              = new SimpleView(
			ViewHelper::VIEWS_FOLDER . 'dynamic-view',
			$view_factory_mock
		);

		$this->assertStringStartsWith(
			'<p>Rendering works with context: 42.</p>',
			$view->render( [ 'some_value' => 42 ] )
		);
	}

	/**
	 * Test if it can render partials.
	 *
	 * @return void
	 */
	public function test_it_can_render_partials() {
		$view_factory_mock = $this->createMock( ViewFactory::class );
		$view_factory_mock
			->expects( $this->once() )
			->method( 'create' )
			->with( ViewHelper::VIEWS_FOLDER . 'partial' )
			->willReturn(
				new SimpleView(
					ViewHelper::VIEWS_FOLDER . 'partial',
					$view_factory_mock
				)
			);

		$view = new SimpleView(
			ViewHelper::VIEWS_FOLDER . 'view-with-partial',
			$view_factory_mock
		);

		$this->assertStringStartsWith(
			'<p>Rendering works with partials: <span>42</span>.</p>',
			$view->render( [ 'some_value' => 42 ] )
		);
	}
}
