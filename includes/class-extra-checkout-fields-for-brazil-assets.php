<?php
/**
 * Registration of the built assets.
 *
 * @package Extra_Checkout_Fields_For_Brazil
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extra_Checkout_Fields_For_Brazil_Assets class.
 */
class Extra_Checkout_Fields_For_Brazil_Assets {

	/**
	 * Build directory, relative to the plugin root.
	 *
	 * @var string
	 */
	const BUILD_DIR = 'build';

	/**
	 * Read the dependencies and version webpack generated for an entry point.
	 *
	 * @param string $entry Entry point name.
	 *
	 * @return array
	 */
	protected static function get_asset_meta( $entry ) {
		$path = plugin_dir_path( CSBMW_PLUGIN_FILE ) . self::BUILD_DIR . '/' . $entry . '.asset.php';

		if ( ! file_exists( $path ) ) {
			return array(
				'dependencies' => array(),
				'version'      => Extra_Checkout_Fields_For_Brazil::VERSION,
			);
		}

		return require $path;
	}

	/**
	 * Build the URL of a file inside the build directory.
	 *
	 * @param string $file File name.
	 *
	 * @return string
	 */
	protected static function get_url( $file ) {
		return plugins_url( self::BUILD_DIR . '/' . $file, CSBMW_PLUGIN_FILE );
	}

	/**
	 * Register a built script.
	 *
	 * @param string $handle       Script handle.
	 * @param string $entry        Entry point name.
	 * @param array  $extra_deps   Dependencies webpack cannot detect, such as jQuery.
	 *
	 * @return void
	 */
	public static function register_script( $handle, $entry, $extra_deps = array() ) {
		$meta = self::get_asset_meta( $entry );

		wp_register_script(
			$handle,
			self::get_url( $entry . '.js' ),
			array_merge( $meta['dependencies'], $extra_deps ),
			$meta['version'],
			true
		);
	}

	/**
	 * Register the stylesheet an entry point emitted, when it has one.
	 *
	 * @param string $handle Style handle.
	 * @param string $entry  Entry point name.
	 *
	 * @return void
	 */
	public static function register_style( $handle, $entry ) {
		$file = $entry . '.css';

		if ( ! file_exists( plugin_dir_path( CSBMW_PLUGIN_FILE ) . self::BUILD_DIR . '/' . $file ) ) {
			return;
		}

		$meta = self::get_asset_meta( $entry );

		wp_register_style( $handle, self::get_url( $file ), array(), $meta['version'] );
		wp_style_add_data( $handle, 'rtl', 'replace' );
	}

	/**
	 * Register and enqueue a script with its stylesheet.
	 *
	 * @param string $handle     Handle used for both the script and the style.
	 * @param string $entry      Entry point name.
	 * @param array  $extra_deps Dependencies webpack cannot detect.
	 *
	 * @return void
	 */
	public static function enqueue( $handle, $entry, $extra_deps = array() ) {
		self::register_script( $handle, $entry, $extra_deps );
		self::register_style( $handle, $entry );

		wp_enqueue_script( $handle );
		wp_enqueue_style( $handle );
	}
}
