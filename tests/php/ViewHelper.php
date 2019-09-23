<?php
/**
 * Brazilian Market on WooCommerce
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Tests;

/**
 * View Helper.
 */
interface ViewHelper {

	public const VIEWS_FOLDER        = 'tests/php/Fixture/views/';
	public const CHILD_THEME_FOLDER  = self::VIEWS_FOLDER . 'child_theme';
	public const PARENT_THEME_FOLDER = self::VIEWS_FOLDER . 'parent_theme';
	public const PLUGIN_FOLDER       = self::VIEWS_FOLDER . 'plugin';
	public const LOCATIONS           = [
		self::CHILD_THEME_FOLDER,
		self::PARENT_THEME_FOLDER,
		self::PLUGIN_FOLDER,
	];
}
