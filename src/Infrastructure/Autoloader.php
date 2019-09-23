<?php
/**
 * Brazilian Market on WooCommerce
 *
 * Inspired in https://github.com/mwpd/basic-scaffold
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Infrastructure;

use Exception;

/**
 * Bundled fallback autoloader.
 *
 * Ideally, you would opt to fully embrace Composer and not need this at all.
 *
 * WordPress being far from ideal, though, it makes sense to include this for
 * the average plugin.
 */
final class Autoloader {

	private const ROOT        = 'root';
	private const BASE_DIR    = 'base_dir';
	private const PREFIX      = 'prefix';
	private const SUFFIX      = 'suffix';
	private const LOWERCASE   = 'lowercase';
	private const UNDERSCORES = 'underscores';

	private const DEFAULT_PREFIX = '';
	private const DEFAULT_SUFFIX = '.php';

	private const AUTOLOAD_METHOD = 'autoload';

	/**
	 * Array containing the registered namespace structures.
	 *
	 * @var array<array>
	 */
	private $namespaces = [];

	/**
	 * Destructor for the Autoloader class.
	 *
	 * The destructor automatically unregisters the autoload callback function
	 * with the SPL autoload system.
	 *
	 * @return void
	 */
	public function __destruct() {
		$this->unregister();
	}

	/**
	 * Registers the autoload callback with the SPL autoload system.
	 *
	 * @return void
	 * @throws Exception If the autoloader could not be registered.
	 */
	public function register() {
		\spl_autoload_register( [ $this, self::AUTOLOAD_METHOD ] );
	}

	/**
	 * Unregisters the autoload callback with the SPL autoload system.
	 *
	 * @return void
	 */
	public function unregister() {
		\spl_autoload_unregister( [ $this, self::AUTOLOAD_METHOD ] );
	}

	/**
	 * Add a specific namespace structure with our custom autoloader.
	 *
	 * @param string  $root        Root namespace name.
	 * @param string  $base_dir    Directory containing the class files.
	 * @param string  $prefix      Optional. Prefix to be added before the
	 *                             class. Defaults to an empty string.
	 * @param string  $suffix      Optional. Suffix to be added after the
	 *                             class. Defaults to '.php'.
	 * @param boolean $lowercase   Optional. Whether the class should be
	 *                             changed to lowercase. Defaults to false.
	 * @param boolean $underscores Optional. Whether the underscores should be
	 *                             changed to hyphens. Defaults to false.
	 *
	 * @return self
	 */
	public function add_namespace(
		$root,
		$base_dir,
		$prefix = self::DEFAULT_PREFIX,
		$suffix = self::DEFAULT_SUFFIX,
		$lowercase = false,
		$underscores = false
	) {
		$this->namespaces[] = [
			self::ROOT        => $this->normalize_root( $root ),
			self::BASE_DIR    => $this->ensure_trailing_slash( $base_dir ),
			self::PREFIX      => $prefix,
			self::SUFFIX      => $suffix,
			self::LOWERCASE   => $lowercase,
			self::UNDERSCORES => $underscores,
		];

		return $this;
	}

	/**
	 * The autoload function that gets registered with the SPL Autoloader
	 * system.
	 *
	 * @param string $class The class that got requested by the spl_autoloader.
	 */
	public function autoload( $class ) {

		// Iterate over namespaces to find a match.
		foreach ( $this->namespaces as $namespace ) {

			// Move on if the object does not belong to the current namespace.
			if ( 0 !== \strpos( $class, (string) $namespace[ self::ROOT ] ) ) {
				continue;
			}

			// Remove namespace root level to correspond with root filesystem.
			$filename = \str_replace(
				(string) $namespace[ self::ROOT ],
				'',
				$class
			);

			// Remove a leading backslash from the class name.
			$filename = $this->remove_leading_backslash( $filename );

			// Replace the namespace separator "\" by the system-dependent
			// directory separator.
			$filename = \str_replace(
				'\\',
				DIRECTORY_SEPARATOR,
				$filename
			);

			// Change to lower case if requested.
			if ( true === $namespace[ self::LOWERCASE ] ) {
				$filename = \strtolower( $filename );
			}

			// Change underscores into hyphens if requested.
			if ( true === $namespace[ self::UNDERSCORES ] ) {
				$filename = \str_replace( '_', '-', $filename );
			}

			// Add base_dir, prefix and suffix.
			$filepath = $namespace[ self::BASE_DIR ] . $namespace[ self::PREFIX ] . $filename . $namespace[ self::SUFFIX ];

			// Require the file if it exists and is readable.
			if ( \is_readable( $filepath ) ) {
				require $filepath;
			}
		}
	}

	/**
	 * Normalize a namespace root.
	 *
	 * @param string $root Namespace root that needs to be normalized.
	 * @return string Normalized namespace root.
	 */
	private function normalize_root( $root ) {
		$root = $this->remove_leading_backslash( $root );
		$root = $this->ensure_trailing_backslash( $root );

		return $root;
	}

	/**
	 * Remove a leading backslash from a namespace.
	 *
	 * @param string $namespace Namespace to remove the leading backslash from.
	 * @return string Modified namespace.
	 */
	private function remove_leading_backslash( $namespace ) {
		return \ltrim( $namespace, '\\' );
	}

	/**
	 * Make sure a namespace ends with a trailing backslash.
	 *
	 * @param string $namespace Namespace to check the trailing backslash of.
	 *
	 * @return string Modified namespace.
	 */
	private function ensure_trailing_backslash( $namespace ) {
		return \rtrim( $namespace, '\\' ) . '\\';
	}

	/**
	 * Make sure a path ends with a trailing slash.
	 *
	 * @param string $path Path to check the trailing slash of.
	 *
	 * @return string Modified path.
	 */
	private function ensure_trailing_slash( $path ) {
		return \rtrim( $path, '/' ) . '/';
	}
}
