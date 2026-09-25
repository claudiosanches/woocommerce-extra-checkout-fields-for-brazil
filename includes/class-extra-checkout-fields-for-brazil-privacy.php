<?php
/**
 * What the shipping calculators keep about a visitor, and consent to it.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extra_Checkout_Fields_For_Brazil_Privacy class.
 */
class Extra_Checkout_Fields_For_Brazil_Privacy {

	/**
	 * Cookie holding the CEP the visitor last used.
	 *
	 * Written by the browser; see rememberPostcode() in assets/js/shared/postcode.ts.
	 *
	 * @var string
	 */
	const POSTCODE_COOKIE = 'csbmw_postcode';

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		// The scripts ask the WP Consent API before keeping the CEP past the
		// browser session.
		add_filter( 'wp_consent_api_registered_' . plugin_basename( CSBMW_PLUGIN_FILE ), '__return_true' );
		add_action( 'init', array( $this, 'describe_cookie' ) );
		add_action( 'admin_init', array( $this, 'privacy_policy_content' ) );
		add_action( 'wp_logout', array( $this, 'forget_postcode' ) );
	}

	/**
	 * Describe the CEP cookie to consent banners that list cookies.
	 *
	 * @return void
	 */
	public function describe_cookie() {
		if ( ! function_exists( 'wp_add_cookie_info' ) ) {
			return;
		}

		wp_add_cookie_info(
			self::POSTCODE_COOKIE,
			__( 'Brazilian Market on WooCommerce', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'preferences',
			__( 'Session, or 30 days with consent to preferences', 'woocommerce-extra-checkout-fields-for-brazil' ),
			__( 'Remembers the CEP used to calculate shipping.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			__( 'CEP', 'woocommerce-extra-checkout-fields-for-brazil' )
		);
	}

	/**
	 * Suggest text for the site's privacy policy.
	 *
	 * @return void
	 */
	public function privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		ob_start();
		include __DIR__ . '/admin/views/html-privacy-policy.php';

		wp_add_privacy_policy_content(
			__( 'Brazilian Market on WooCommerce', 'woocommerce-extra-checkout-fields-for-brazil' ),
			wp_kses_post( (string) ob_get_clean() )
		);
	}

	/**
	 * Forget the CEP when a customer logs out, as on a shared computer.
	 *
	 * @return void
	 */
	public function forget_postcode() {
		if ( headers_sent() || ! isset( $_COOKIE[ self::POSTCODE_COOKIE ] ) ) {
			return;
		}

		// Same path and host as the browser wrote it with.
		setcookie(
			self::POSTCODE_COOKIE,
			'',
			array(
				'expires'  => time() - YEAR_IN_SECONDS,
				'path'     => '/',
				'secure'   => is_ssl(),
				'samesite' => 'Lax',
			)
		);

		unset( $_COOKIE[ self::POSTCODE_COOKIE ] );
	}
}

new Extra_Checkout_Fields_For_Brazil_Privacy();
