<?php
/**
 * Brazilian Market on WooCommerce
 *
 * Inspired in https://github.com/mwpd/basic-scaffold
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Infrastructure\Injector;

use LogicException;

/**
 * The injection chain is similar to a trace, keeping track of what we have done
 * so far and at what depth within the auto-wiring we currently are.
 *
 * It is used to detect circular dependencies, and can also be dumped for
 * debugging information.
 */
final class InjectionChain {

	/**
	 * Chain.
	 *
	 * @var array<array>
	 */
	private $chain = [];

	/**
	 * Resolutions.
	 *
	 * @var array<array>
	 */
	private $resolutions = [];

	/**
	 * Add class to injection chain.
	 *
	 * @param string $class Class to add to injection chain.
	 * @return self Modified injection chain.
	 */
	public function add_to_chain( $class ) {
		$this->chain[] = $class;

		return $this;
	}

	/**
	 * Add resolution for circular reference detection.
	 *
	 * @param string $resolution Resolution to add.
	 * @return self Modified injection chain.
	 */
	public function add_resolution( $resolution ) {
		$this->resolutions[ $resolution ] = true;

		return $this;
	}

	/**
	 * Get the last class that was pushed to the injection chain.
	 *
	 * @throws LogicException If no chain is injected.
	 * @return string Last class pushed to the injection chain.
	 */
	public function get_class() {
		if ( empty( $this->chain ) ) {
			throw new LogicException(
				'Access to injection chain before any resolution was made.'
			);
		}

		return \end( $this->chain ) ?: '';
	}

	/**
	 * Get the injection chain.
	 *
	 * @return array Chain of injections.
	 */
	public function get_chain() {
		return \array_reverse( $this->chain );
	}

	/**
	 * Check whether the injection chain already has a given resolution.
	 *
	 * @param string $resolution Resolution to check for.
	 * @return bool Whether the resolution was found.
	 */
	public function has_resolution( $resolution ) {
		return \array_key_exists( $resolution, $this->resolutions );
	}
}
