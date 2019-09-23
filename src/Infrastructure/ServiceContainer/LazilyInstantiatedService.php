<?php
/**
 * Brazilian Market on WooCommerce
 *
 * Inspired in https://github.com/mwpd/basic-scaffold
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Infrastructure\ServiceContainer;

use ClaudioSanches\BrazilianMarket\Exception\InvalidService;
use ClaudioSanches\BrazilianMarket\Infrastructure\Service;

/**
 * A service that only gets properly instantiated when it is actually being
 * retrieved from the container.
 */
final class LazilyInstantiatedService implements Service {

	/**
	 * Instantiation.
	 *
	 * @var callable
	 */
	private $instantiation;

	/**
	 * Instantiate a LazilyInstantiatedService object.
	 *
	 * @param callable $instantiation Instantiation callable to use.
	 */
	public function __construct( $instantiation ) {
		$this->instantiation = $instantiation;
	}

	/**
	 * Do the actual service instantiation and return the real service.
	 *
	 * @throws InvalidService If the service could not be properly instantiated.
	 * @return Service Properly instantiated service.
	 */
	public function instantiate() {
		$service = ( $this->instantiation )();

		if ( ! $service instanceof Service ) {
			throw InvalidService::from_service( $service );
		}

		return $service;
	}
}
