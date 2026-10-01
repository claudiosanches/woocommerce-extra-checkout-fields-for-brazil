<?php
/**
 * Extra checkout fields main class.
 *
 * @package Extra_Checkout_Fields_For_Brazil
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Plugin main class.
 */
class Extra_Checkout_Fields_For_Brazil {

	/**
	* Plugin version.
	*
	* @var string
	*/
	const VERSION = '5.0.0';

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin.
	 */
	private function __construct() {
		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		if ( class_exists( 'WooCommerce' ) ) {
			add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );

			if ( is_admin() ) {
				$this->admin_includes();
			}

			$this->includes();
			add_filter( 'plugin_action_links_' . plugin_basename( CSBMW_PLUGIN_FILE ), array( $this, 'plugin_action_links' ) );
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * What the Number field holds for an address without a number, unless the
	 * store sets its own.
	 *
	 * @var string
	 */
	const NO_NUMBER_VALUE = 'S/N';

	/**
	 * What the No number option writes, or nothing when it is not offered.
	 *
	 * @param array|null $settings Plugin settings, read when not given.
	 *
	 * @return string
	 */
	public static function no_number_value( $settings = null ) {
		$settings = null === $settings ? (array) get_option( 'wcbcf_settings', array() ) : $settings;

		if ( empty( $settings['no_number'] ) ) {
			return '';
		}

		$value = isset( $settings['no_number_value'] ) ? trim( (string) $settings['no_number_value'] ) : '';

		return '' === $value ? self::NO_NUMBER_VALUE : $value;
	}

	/**
	 * Whether a field is off, optional or required.
	 *
	 * Used for RG, State Registration, Birthdate and Gender. These were
	 * checkboxes that made the field required, so a stored 1 means required.
	 *
	 * @param string     $key      Setting key.
	 * @param array|null $settings Plugin settings, read when not given.
	 *
	 * @return string disabled, optional or required.
	 */
	public static function field_mode( $key, $settings = null ) {
		$settings = null === $settings ? (array) get_option( 'wcbcf_settings', array() ) : $settings;
		$value    = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';

		if ( 'optional' === $value ) {
			return 'optional';
		}

		return in_array( $value, array( '', '0' ), true ) ? 'disabled' : 'required';
	}

	/**
	 * Whether Brazil is the only country an address can take. Billing follows
	 * the countries the store sells to, and shipping those it ships to.
	 *
	 * @param string $type billing or shipping.
	 *
	 * @return bool
	 */
	public static function is_brazil_only( $type ) {
		$countries = 'shipping' === $type ? WC()->countries->get_shipping_countries() : WC()->countries->get_allowed_countries();

		return array( 'BR' ) === array_keys( $countries );
	}

	/**
	 * How an address shows its country. The setting only applies while the
	 * address can take no country but Brazil.
	 *
	 * @param string     $type     billing or shipping.
	 * @param array|null $settings Plugin settings, read when not given.
	 *
	 * @return string select, text or hidden.
	 */
	public static function country_field_mode( $type, $settings = null ) {
		$settings = null === $settings ? (array) get_option( 'wcbcf_settings', array() ) : $settings;
		$value    = isset( $settings['country_field'] ) ? $settings['country_field'] : '';

		if ( ! in_array( $value, array( 'text', 'hidden' ), true ) || ! self::is_brazil_only( $type ) ) {
			return 'select';
		}

		return $value;
	}

	/**
	 * Whether the company is asked of legal persons only, against WooCommerce's
	 * own Company setting.
	 *
	 * @param array|null $settings Plugin settings, read when not given.
	 *
	 * @return bool
	 */
	public static function has_dynamic_company( $settings = null ) {
		$settings    = null === $settings ? (array) get_option( 'wcbcf_settings', array() ) : $settings;
		$person_type = isset( $settings['person_type'] ) ? intval( $settings['person_type'] ) : 0;

		if ( 1 !== $person_type && 3 !== $person_type ) {
			return false;
		}

		return 'woocommerce' !== ( isset( $settings['company'] ) ? $settings['company'] : 'dynamic' );
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		// Try to use the plugins own translation, only available for pt_BR.
		$locale = apply_filters( 'plugin_locale', determine_locale(), 'woocommerce-extra-checkout-fields-for-brazil' );

		if ( 'pt_BR' === $locale ) {
			unload_textdomain( 'woocommerce-extra-checkout-fields-for-brazil' );
			load_textdomain(
				'woocommerce-extra-checkout-fields-for-brazil',
				plugin_dir_path( CSBMW_PLUGIN_FILE ) . '/languages/woocommerce-extra-checkout-fields-for-brazil-' . $locale . '.mo'
			);
		}

		// Load regular translation from WordPress.
		load_plugin_textdomain(
			'woocommerce-extra-checkout-fields-for-brazil',
			false,
			dirname( plugin_basename( CSBMW_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Declare compatibility with the WooCommerce features this plugin supports.
	 */
	public function declare_compatibility() {
		if ( ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			return;
		}

		$basename = plugin_basename( CSBMW_PLUGIN_FILE );

		foreach ( array( 'custom_order_tables', 'cart_checkout_blocks' ) as $feature ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( $feature, $basename, true );
		}
	}

	/**
	 * Includes.
	 */
	private function includes() {
		include_once dirname( CSBMW_PLUGIN_FILE ) . '/includes/class-extra-checkout-fields-for-brazil-assets.php';
		include_once dirname( CSBMW_PLUGIN_FILE ) . '/includes/class-extra-checkout-fields-for-brazil-validation.php';
		include_once dirname( CSBMW_PLUGIN_FILE ) . '/includes/class-extra-checkout-fields-for-brazil-cnpj.php';
		include_once dirname( CSBMW_PLUGIN_FILE ) . '/includes/class-extra-checkout-fields-for-brazil-postcodes.php';
		include_once dirname( CSBMW_PLUGIN_FILE ) . '/includes/class-extra-checkout-fields-for-brazil-blocks.php';
		include_once dirname( CSBMW_PLUGIN_FILE ) . '/includes/class-extra-checkout-fields-for-brazil-legacy-sync.php';
		include_once dirname( CSBMW_PLUGIN_FILE ) . '/includes/class-extra-checkout-fields-for-brazil-front-end.php';
		include_once dirname( CSBMW_PLUGIN_FILE ) . '/includes/class-extra-checkout-fields-for-brazil-phone.php';
		include_once dirname( CSBMW_PLUGIN_FILE ) . '/includes/class-extra-checkout-fields-for-brazil-order-details.php';
		include_once dirname( CSBMW_PLUGIN_FILE ) . '/includes/class-extra-checkout-fields-for-brazil-shipping.php';
		include_once dirname( CSBMW_PLUGIN_FILE ) . '/includes/class-extra-checkout-fields-for-brazil-privacy.php';
		include_once dirname( CSBMW_PLUGIN_FILE ) . '/includes/class-extra-checkout-fields-for-brazil-integrations.php';
		include_once dirname( CSBMW_PLUGIN_FILE ) . '/includes/class-extra-checkout-fields-for-brazil-api.php';
	}

	/**
	 * Admin includes.
	 */
	private function admin_includes() {
		include_once dirname( CSBMW_PLUGIN_FILE ) . '/includes/admin/class-extra-checkout-fields-for-brazil-admin.php';
		include_once dirname( CSBMW_PLUGIN_FILE ) . '/includes/admin/class-extra-checkout-fields-for-brazil-settings.php';
		include_once dirname( CSBMW_PLUGIN_FILE ) . '/includes/admin/class-extra-checkout-fields-for-brazil-order.php';
		include_once dirname( CSBMW_PLUGIN_FILE ) . '/includes/admin/class-extra-checkout-fields-for-brazil-customer.php';
	}

	/**
	 * Action links.
	 *
	 * @param  array $links Default plugin links.
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links   = array();
		$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=woocommerce-extra-checkout-fields-for-brazil' ) ) . '">' . __( 'Settings', 'woocommerce-extra-checkout-fields-for-brazil' ) . '</a>';
		$plugin_links[] = '<a href="https://apoia.se/claudiosanches?utm_source=plugin-bmw" target="_blank" rel="noopener noreferrer">' . __( 'Premium Support', 'woocommerce-extra-checkout-fields-for-brazil' ) . '</a>';
		$plugin_links[] = '<a href="https://apoia.se/claudiosanches?utm_source=plugin-bmw" target="_blank" rel="noopener noreferrer">' . __( 'Contribute', 'woocommerce-extra-checkout-fields-for-brazil' ) . '</a>';

		return array_merge( $plugin_links, $links );
	}
}
