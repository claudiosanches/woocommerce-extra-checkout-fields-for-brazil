<?php
/**
 * Brazilian Market on WooCommerce
 *
 * Inspired in https://github.com/mwpd/basic-scaffold
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Infrastructure;

/**
 * Something that can be rendered.
 *
 * This can be used for views, obviously, but could just as well be used for
 * other concepts like blocks or shortcodes, value objects, ...
 */
interface Renderable {

	/**
	 * Render the renderable.
	 *
	 * @param array $context Optional. Contextual information to use while
	 *                       rendering. Defaults to an empty array.
	 * @return string Rendered result.
	 */
	public function render( $context = [] );
}
