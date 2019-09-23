<?php
/**
 * Brazilian Market on WooCommerce
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Tests\Fixture;

/**
 * Dummy Class With Dependency.
 */
final class DummyClassWithDependency implements DummyInterface {

	/**
	 * DummyClass instance.
	 *
	 * @var DummyClass
	 */
	private $dummy;

	/**
	 * Class constructor.
	 *
	 * @param DummyClass $dummy DummyClass instance.
	 */
	public function __construct( DummyClass $dummy ) {
		$this->dummy = $dummy;
	}

	/**
	 * Get DummyClass instance.
	 *
	 * @return DummyClass
	 */
	public function get_dummy() {
		return $this->dummy;
	}
}
