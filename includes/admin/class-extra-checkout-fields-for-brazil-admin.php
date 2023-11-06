<?php
/**
 * Extra checkout fields admin.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extra_Checkout_Fields_For_Brazil_Admin class.
 */
class Extra_Checkout_Fields_For_Brazil_Admin {

	/**
	 * Initialize the plugin admin.
	 */
	public function __construct() {
		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		$this->maybe_install();
	}

	/**
	 * Admin scripts
	 */
	public function admin_scripts() {
		$screen = get_current_screen();
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( 'shop_order' === $screen->id ) {
			// Get plugin settings.
			$settings = get_option( 'wcbcf_settings' );

			// Styles.
			wp_enqueue_style( 'woocommerce-extra-checkout-fields-for-brazil-admin', Extra_Checkout_Fields_For_Brazil::get_assets_url() . 'css/admin/admin.css', array(), Extra_Checkout_Fields_For_Brazil::VERSION );

			// Shop order.
			wp_enqueue_script( 'woocommerce-extra-checkout-fields-for-brazil-shop-order', Extra_Checkout_Fields_For_Brazil::get_assets_url() . 'js/admin/shop-order' . $suffix . '.js', array( 'jquery' ), Extra_Checkout_Fields_For_Brazil::VERSION, true );

			// Localize strings.
			wp_localize_script(
				'woocommerce-extra-checkout-fields-for-brazil-shop-order',
				'bmwShopOrderParams',
				array(
					'load_message' => esc_js( __( 'Load the customer extras data?', 'woocommerce-extra-checkout-fields-for-brazil' ) ),
					'copy_message' => esc_js( __( 'Also copy the data of number and neighborhood?', 'woocommerce-extra-checkout-fields-for-brazil' ) ),
					'person_type'  => absint( $settings['person_type'] ),
				)
			);
		}

		if ( 'woocommerce_page_woocommerce-extra-checkout-fields-for-brazil' === $screen->id ) {
			wp_enqueue_style( 'woocommerce-extra-checkout-fields-for-brazil-settings', Extra_Checkout_Fields_For_Brazil::get_assets_url() . 'css/admin/settings.css', array(), Extra_Checkout_Fields_For_Brazil::VERSION );
			wp_enqueue_script( 'woocommerce-extra-checkout-fields-for-brazil-admin', Extra_Checkout_Fields_For_Brazil::get_assets_url() . 'js/admin/admin' . $suffix . '.js', array( 'jquery' ), Extra_Checkout_Fields_For_Brazil::VERSION, true );
		}
	}

	/**
	 * Maybe install.
	 */
	public function maybe_install() {
		$version = get_option( 'wcbcf_version' );

		if ( $version ) {
			if ( version_compare( $version, Extra_Checkout_Fields_For_Brazil::VERSION, '<' ) ) {
				$options = get_option( 'wcbcf_settings' );

				// Update to version 3.0.0.
				if ( version_compare( $version, '3.0.0', '<' ) ) {
					if ( isset( $options['person_type'] ) ) {
						$options['person_type'] = 1;
					} else {
						$options['person_type'] = 0;
					}
				}

				// Update to version 4.0.0.
				if ( version_compare( $version, '4.0.0', '<' ) ) {
					$options['cell_phone']   = -1;
					$options['fields_style'] = 'side_by_side';

					// Migrate old fields.
					if ( isset( $options['birthdate_sex'] ) ) {
						$options['birthdate'] = 1;
						$options['gender']    = 1;
					}

					// Update database.
					$this->update_database_to_400();
				}

				update_option( 'wcbcf_settings', $options );
				update_option( 'wcbcf_version', Extra_Checkout_Fields_For_Brazil::VERSION );
			}
		} else {
			$default = array(
				'person_type'   => 1,
				'cell_phone'    => -1,
				'fields_style'  => 0,
				'mailcheck'     => 1,
				'maskedinput'   => 1,
				'validate_cpf'  => 1,
				'validate_cnpj' => 1,
			);

			add_option( 'wcbcf_settings', $default );
			add_option( 'wcbcf_version', Extra_Checkout_Fields_For_Brazil::VERSION );
		}
	}

	/**
	 * Update database to 4.0.0.
	 */
	private function update_database_to_400() {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"UPDATE {$wpdb->postmeta}
			SET meta_key = '_billing_gender'
			WHERE meta_key = '_billing_sex'",
		);
		$wpdb->query(
			"UPDATE {$wpdb->usermeta}
			SET meta_key = 'billing_gender'
			WHERE meta_key = 'billing_sex'",
		);

		// Check if custom order meta table exists.
		$wc_orders_meta = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wc_orders_meta'" );

		if ( ! is_null( $wc_orders_meta ) ) {
			$wpdb->query(
				"UPDATE {$wpdb->prefix}wc_orders_meta
				SET meta_key = '_billing_gender'
				WHERE meta_key = '_billing_sex'",
			);
		}

		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}

new Extra_Checkout_Fields_For_Brazil_Admin();
