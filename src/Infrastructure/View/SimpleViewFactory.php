<?php
/**
 * Brazilian Market on WooCommerce
 *
 * Inspired in https://github.com/mwpd/basic-scaffold
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Infrastructure\View;

use ClaudioSanches\BrazilianMarket\Infrastructure\Service;
use ClaudioSanches\BrazilianMarket\Infrastructure\View;
use ClaudioSanches\BrazilianMarket\Infrastructure\ViewFactory;

/**
 * Factory to create the simplified view objects.
 */
final class SimpleViewFactory implements Service, ViewFactory {

	/**
	 * Create a new view object for a given relative path.
	 *
	 * @param string $relative_path Relative path to create the view for.
	 * @return View Instantiated view object.
	 */
	public function create( $relative_path ) {
		return new SimpleView( $relative_path, $this );
	}
}
