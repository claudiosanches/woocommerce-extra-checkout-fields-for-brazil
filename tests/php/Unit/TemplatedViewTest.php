<?php
/**
 * Brazilian Market on WooCommerce
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Tests\Unit;

use ClaudioSanches\BrazilianMarket\Infrastructure\View\TemplatedView;
use ClaudioSanches\BrazilianMarket\Infrastructure\View\TemplatedViewFactory;
use ClaudioSanches\BrazilianMarket\Infrastructure\ViewFactory;
use ClaudioSanches\BrazilianMarket\Tests\ViewHelper;

/**
 * Templated View Test.
 */
final class TemplatedViewTest extends TestCase {

	/**
	 * Test if it can be initialized.
	 *
	 * @return void
	 */
	public function test_it_can_be_initialized() {
		$view_factory_mock = $this->createMock( ViewFactory::class );
		$view              = new TemplatedView(
			'static-view',
			$view_factory_mock,
			ViewHelper::LOCATIONS
		);

		$this->assertInstanceOf( TemplatedView::class, $view );
	}

	/**
	 * Test if it can be rendered.
	 *
	 * @return void
	 */
	public function test_it_can_be_rendered() {
		$view_factory_mock = $this->createMock( ViewFactory::class );
		$view              = new TemplatedView(
			'static-view',
			$view_factory_mock,
			ViewHelper::LOCATIONS
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
		$view              = new TemplatedView(
			'dynamic-view',
			$view_factory_mock,
			ViewHelper::LOCATIONS
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
			->with( 'partial' )
			->willReturn(
				new TemplatedView(
					'partial',
					$view_factory_mock,
					ViewHelper::LOCATIONS
				)
			);

		$view = new TemplatedView(
			'view-with-partial',
			$view_factory_mock,
			ViewHelper::LOCATIONS
		);

		$this->assertStringStartsWith(
			'<p>Rendering works with partials: <span>42</span>.</p>',
			$view->render( [ 'some_value' => 42 ] )
		);
	}

	/**
	 * Test if it can be overridden in themes.
	 *
	 * @return void
	 */
	public function test_it_can_be_overridden_in_themes() {
		$view_factory_mock = $this->createMock( ViewFactory::class );
		$view_a            = new TemplatedView(
			'view-a',
			$view_factory_mock,
			ViewHelper::LOCATIONS
		);
		$view_b            = new TemplatedView(
			'view-b',
			$view_factory_mock,
			ViewHelper::LOCATIONS
		);
		$view_c            = new TemplatedView(
			'view-c',
			$view_factory_mock,
			ViewHelper::LOCATIONS
		);

		$this->assertStringStartsWith(
			'<p>View A comes from plugin.</p>',
			$view_a->render()
		);
		$this->assertStringStartsWith(
			'<p>View B comes from parent theme.</p>',
			$view_b->render()
		);
		$this->assertStringStartsWith(
			'<p>View C comes from child theme.</p>',
			$view_c->render()
		);
	}
}
