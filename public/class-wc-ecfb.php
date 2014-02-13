<?php
/**
 * WooCommerce Extra Checkout Fields for Brazil.
 *
 * @package   Extra_Checkout_Fields_For_Brazil
 * @author    Claudio Sanches <contato@claudiosmweb.com>
 * @license   GPL-2.0+
 * @copyright 2013 Claudio Sanches
 */

/**
 * Plugin main class.
 *
 * @package Extra_Checkout_Fields_For_Brazil
 * @author  Claudio Sanches <contato@claudiosmweb.com>
 */
class Extra_Checkout_Fields_For_Brazil {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since 2.9.0
	 *
	 * @var   string
	 */
	const VERSION = '2.9.0';

	/**
	 * Plugin slug.
	 *
	 * @since 2.8.0
	 *
	 * @var   string
	 */
	protected static $plugin_slug = 'woocommerce-extra-checkout-fields-for-brasil';

	/**
	 * Instance of this class.
	 *
	 * @since 2.8.0
	 *
	 * @var   object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since 2.9.0
	 */
	private function __construct() {
		global $woocommerce;

		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added.
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		if ( self::has_woocommerce_active() ) {
			// Load custom order data.
			add_filter( 'woocommerce_load_order_data', array( $this, 'load_order_data' ) );

			// Load public-facing style sheet and JavaScript.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			// New checkout fields.
			add_filter( 'woocommerce_billing_fields', array( $this, 'checkout_billing_fields' ) );
			add_filter( 'woocommerce_shipping_fields', array( $this, 'checkout_shipping_fields' ) );

			// Valid checkout fields.
			add_action( 'woocommerce_checkout_process', array( $this, 'valid_checkout_fields' ) );

			// Found customers details ajax.
			add_filter( 'woocommerce_found_customer_details', array( $this, 'customer_details_ajax' ) );

			// Custom address format.
			if ( version_compare( $woocommerce->version, '2.0.6', '>=' ) ) {
				add_filter( 'woocommerce_localisation_address_formats', array( $this, 'localisation_address_formats' ) );
				add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'formatted_address_replacements' ), 1, 2 );
				add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'order_formatted_billing_address' ), 1, 2 );
				add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'order_formatted_shipping_address' ), 1, 2 );
				add_filter( 'woocommerce_user_column_billing_address', array( $this, 'user_column_billing_address' ), 1, 2 );
				add_filter( 'woocommerce_user_column_shipping_address', array( $this, 'user_column_shipping_address' ), 1, 2 );
				add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'my_account_my_address_formatted_address' ), 1, 3 );
			}
		}
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since  2.8.0
	 *
	 * @return Plugin slug variable.
	 */
	public static function get_plugin_slug() {
		return self::$plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since  2.8.0
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Checks if WooCommerce is active.
	 *
	 * @since  2.9.0
	 *
	 * @return bool true if WooCommerce is active, false otherwise.
	 */
	public static function has_woocommerce_active() {
		if ( class_exists( 'WooCommerce' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since  2.8.0
	 *
	 * @param  boolean $network_wide True if WPMU superadmin uses
	 *                               "Network Activate" action, false if
	 *                               WPMU is disabled or plugin is
	 *                               activated on an individual blog.
	 *
	 * @return void
	 */
	public static function activate( $network_wide ) {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();
			} else {
				self::single_activate();
			}
		} else {
			self::single_activate();
		}
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since  2.8.0
	 *
	 * @param  boolean $network_wide True if WPMU superadmin uses
	 *                               "Network Deactivate" action, false if
	 *                               WPMU is disabled or plugin is
	 *                               deactivated on an individual blog.
	 *
	 * @return void
	 */
	public static function deactivate( $network_wide ) {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::single_deactivate();
				}

				restore_current_blog();
			} else {
				self::single_deactivate();
			}
		} else {
			self::single_deactivate();
		}
	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since  2.8.0
	 *
	 * @param  int $blog_id ID of the new blog.
	 *
	 * @return void
	 */
	public function activate_new_site( $blog_id ) {
		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();
	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since  2.8.0
	 *
	 * @return array|false The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {
		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );
	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since 2.8.0
	 */
	private static function single_activate() {
		$default = array(
			'person_type'     => 1,
			'ie'              => 0,
			'rg'              => 0,
			'birthdate_sex'   => 0,
			'cell_phone'      => 1,
			'mailcheck'       => 1,
			'maskedinput'     => 1,
			'addresscomplete' => 1,
			'validate_cpf'    => 1,
			'validate_cnpj'   => 1
		);

		add_option( 'wcbcf_settings', $default );
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since 2.8.0
	 */
	private static function single_deactivate() {
		delete_option( 'wcbcf_settings' );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since  2.8.0
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		$domain = self::$plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );
	}

	/**
	 * Load order custom data.
	 *
	 * @param  array $data Default WC_Order data.
	 *
	 * @return array       Custom WC_Order data.
	 */
	public function load_order_data( $data ) {

		// Billing
		$data['billing_persontype']    = '';
		$data['billing_cpf']           = '';
		$data['billing_rg']            = '';
		$data['billing_cnpj']          = '';
		$data['billing_ie']            = '';
		$data['billing_birthdate']     = '';
		$data['billing_sex']           = '';
		$data['billing_number']        = '';
		$data['billing_neighborhood']  = '';
		$data['billing_cellphone']     = '';

		// Shipping
		$data['shipping_number']       = '';
		$data['shipping_neighborhood'] = '';

		return $data;
	}

	/**
	 * Register and enqueues public-facing style sheet and JavaScript files.
	 *
	 * @since    2.8.0
	 */
	public function enqueue_scripts() {
		// Load scripts only in checkout.
		if ( is_checkout() || is_account_page() ) {

			// Get plugin settings.
			$settings = get_option( 'wcbcf_settings' );

			// Call jQuery.
			wp_enqueue_script( 'jquery' );

			// Fix checkout fields.
			wp_enqueue_script( self::$plugin_slug . '-public', plugins_url( 'assets/js/public.min.js', __FILE__ ), array( 'jquery' ), self::VERSION, true );
			wp_localize_script(
				self::$plugin_slug . '-public',
				'wcbcf_public_params',
				array(
					'state'           => __( 'State', self::$plugin_slug ),
					'required'        => __( 'required', self::$plugin_slug ),
					'mailcheck'       => isset( $settings['mailcheck'] ) ? 'yes' : 'no',
					'maskedinput'     => isset( $settings['maskedinput'] ) ? 'yes' : 'no',
					'addresscomplete' => isset( $settings['addresscomplete'] ) ? 'yes' : 'no',
					'person_type'     => isset( $settings['person_type'] ) ? 'yes' : 'no'
				)
			);
		}
	}

	/**
	 * New checkout billing fields
	 *
	 * @param  array $fields Default fields.
	 *
	 * @return array         New fields.
	 */
	public function checkout_billing_fields( $fields ) {

		$new_fields = array();

		// Get plugin settings.
		$settings = get_option( 'wcbcf_settings' );

		// Billing First Name.
		$new_fields['billing_first_name'] = array(
			'label'       => __( 'First Name', self::$plugin_slug ),
			'placeholder' => _x( 'First Name', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-first' ),
			'required'    => true
		);

		// Billing Last Name.
		$new_fields['billing_last_name'] = array(
			'label'       => __( 'Last Name', self::$plugin_slug ),
			'placeholder' => _x( 'Last Name', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-last' ),
			'clear'       => true,
			'required'    => true
		);

		if ( isset( $settings['person_type'] ) ) {

			// Billing Person Type.
			$new_fields['billing_persontype'] = array(
				'type'     => 'select',
				'label'    => __( 'Person type', self::$plugin_slug ),
				'class'    => array( 'form-row-wide' ),
				'required' => true,
				'options'  => array(
					'0' => __( 'Select', self::$plugin_slug ),
					'1' => __( 'Individuals', self::$plugin_slug ),
					'2' => __( 'Legal Person', self::$plugin_slug )
				)
			);

			if ( isset( $settings['rg'] ) ) {
				// Billing CPF.
				$new_fields['billing_cpf'] = array(
					'label'       => __( 'CPF', self::$plugin_slug ),
					'placeholder' => _x( 'CPF', 'placeholder', self::$plugin_slug ),
					'class'       => array( 'form-row-first' ),
					'required'    => false
				);

				// Billing RG.
				$new_fields['billing_rg'] = array(
					'label'       => __( 'RG', self::$plugin_slug ),
					'placeholder' => _x( 'RG', 'placeholder', self::$plugin_slug ),
					'class'       => array( 'form-row-last' ),
					'required'    => false
				);
			} else {
				// Billing CPF.
				$new_fields['billing_cpf'] = array(
					'label'       => __( 'CPF', self::$plugin_slug ),
					'placeholder' => _x( 'CPF', 'placeholder', self::$plugin_slug ),
					'class'       => array( 'form-row-wide' ),
					'required'    => false
				);
			}

			// Billing Company.
			$new_fields['billing_company'] = array(
				'label'       => __( 'Company Name', self::$plugin_slug ),
				'placeholder' => _x( 'Company Name', 'placeholder', self::$plugin_slug ),
				'class'       => array( 'form-row-wide' ),
				'required'    => false
			);

			// Billing State Registration.
			if ( isset( $settings['ie'] ) ) {
				// Billing CNPJ.
				$new_fields['billing_cnpj'] = array(
					'label'       => __( 'CNPJ', self::$plugin_slug ),
					'placeholder' => _x( 'CNPJ', 'placeholder', self::$plugin_slug ),
					'class'       => array( 'form-row-first' ),
					'required'    => false
				);

				$new_fields['billing_ie'] = array(
					'label'       => __( 'State Registration', self::$plugin_slug ),
					'placeholder' => _x( 'State Registration', 'placeholder', self::$plugin_slug ),
					'class'       => array( 'form-row-last' ),
					'required'    => false
				);
			} else {
				// Billing CNPJ.
				$new_fields['billing_cnpj'] = array(
					'label'       => __( 'CNPJ', self::$plugin_slug ),
					'placeholder' => _x( 'CNPJ', 'placeholder', self::$plugin_slug ),
					'class'       => array( 'form-row-wide' ),
					'required'    => false
				);
			}

		} else {
			// Billing Company.
			$new_fields['billing_company'] = array(
				'label'       => __( 'Company', self::$plugin_slug ),
				'placeholder' => _x( 'Company', 'placeholder', self::$plugin_slug ),
				'class'       => array( 'form-row-wide' ),
				'required'    => false
			);
		}

		if ( isset( $settings['birthdate_sex'] ) ) {

			// Billing Birthdate.
			$new_fields['billing_birthdate'] = array(
				'label'       => __( 'Birthdate', self::$plugin_slug ),
				'placeholder' => _x( 'Birthdate', 'placeholder', self::$plugin_slug ),
				'class'       => array( 'form-row-first' ),
				'clear'       => false,
				'required'    => true
			);

			// Billing Sex.
			$new_fields['billing_sex'] = array(
				'type'        => 'select',
				'label'       => __( 'Sex', self::$plugin_slug ),
				'class'       => array( 'form-row-last' ),
				'clear'       => true,
				'required'    => true,
				'options'     => array(
					'0'                     => __( 'Select', self::$plugin_slug ),
					__( 'Female', self::$plugin_slug ) => __( 'Female', self::$plugin_slug ),
					__( 'Male', self::$plugin_slug )   => __( 'Male', self::$plugin_slug )
				)
			);

		}

		// Billing Country.
		$new_fields['billing_country'] = array(
			'type'        => 'country',
			'label'       => __( 'Country', self::$plugin_slug ),
			'placeholder' => _x( 'Country', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-first', 'update_totals_on_change', 'address-field' ),
			'clear'       => false,
			'required'    => true,
		);

		// Billing Post Code.
		$new_fields['billing_postcode'] = array(
			'label'       => __( 'Post Code', self::$plugin_slug ),
			'placeholder' => _x( 'Post Code', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-last', 'update_totals_on_change', 'address-field' ),
			'clear'       => true,
			'required'    => true
		);

		// Billing Anddress 01.
		$new_fields['billing_address_1'] = array(
			'label'       => __( 'Address', self::$plugin_slug ),
			'placeholder' => _x( 'Address', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-first', 'address-field' ),
			'required'    => true
		);

		// Billing Number.
		$new_fields['billing_number'] = array(
			'label'       => __( 'Number', self::$plugin_slug ),
			'placeholder' => _x( 'Number', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-last', 'address-field' ),
			'clear'       => true,
			'required'    => true
		);

		// Billing Anddress 02.
		$new_fields['billing_address_2'] = array(
			'label'       => __( 'Address line 2', self::$plugin_slug ),
			'placeholder' => _x( 'Address line 2', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-first', 'address-field' )
		);

		// Billing Neighborhood.
		$new_fields['billing_neighborhood'] = array(
			'label'       => __( 'Neighborhood', self::$plugin_slug ),
			'placeholder' => _x( 'Neighborhood', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-last', 'address-field' ),
			'clear'       => true,
		);

		// Billing City.
		$new_fields['billing_city'] = array(
			'label'       => __( 'City', self::$plugin_slug ),
			'placeholder' => _x( 'City', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-first', 'address-field' ),
			'required'    => true
		);

		// Billing State.
		$new_fields['billing_state'] = array(
			'type'        => 'state',
			'label'       => __( 'State', self::$plugin_slug ),
			'placeholder' => _x( 'State', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-last', 'address-field' ),
			'clear'       => true,
			'required'    => true
		);

		if ( isset( $settings['cell_phone'] ) ) {

			// Billing Phone.
			$new_fields['billing_phone'] = array(
				'label'       => __( 'Phone', self::$plugin_slug ),
				'placeholder' => _x( 'Phone', 'placeholder', self::$plugin_slug ),
				'class'       => array( 'form-row-first' ),
				'required'    => true
			);

			// Billing Cell Phone.
			$new_fields['billing_cellphone'] = array(
				'label'       => __( 'Cell Phone', self::$plugin_slug ),
				'placeholder' => _x( 'Cell Phone', 'placeholder', self::$plugin_slug ),
				'class'       => array( 'form-row-last' ),
				'clear'       => true
			);

			// Billing Email.
			$new_fields['billing_email'] = array(
				'label'       => __( 'Email', self::$plugin_slug ),
				'placeholder' => _x( 'Email', 'placeholder', self::$plugin_slug ),
				'class'       => array( 'form-row-wide' ),
				'validate'    => array( 'email' ),
				'clear'       => true,
				'required'    => true
			);

		} else {

			// Billing Phone.
			$new_fields['billing_phone'] = array(
				'label'       => __( 'Phone', self::$plugin_slug ),
				'placeholder' => _x( 'Phone', 'placeholder', self::$plugin_slug ),
				'class'       => array( 'form-row-wide' ),
				'required'    => true
			);

			// Billing Email.
			$new_fields['billing_email'] = array(
				'label'       => __( 'Email', self::$plugin_slug ),
				'placeholder' => _x( 'Email', 'placeholder', self::$plugin_slug ),
				'class'       => array( 'form-row-wide' ),
				'required'    => true
			);

		}

		return apply_filters( 'wcbcf_billing_fields', $new_fields );
	}

	/**
	 * New checkout shipping fields
	 *
	 * @param  array $fields Default fields.
	 *
	 * @return array         New fields.
	 */
	public function checkout_shipping_fields( $fields ) {

		$new_fields = array();

		// Get plugin settings.
		$settings = get_option( 'wcbcf_settings' );

		// Shipping First Name.
		$new_fields['shipping_first_name'] = array(
			'label'       => __( 'First Name', self::$plugin_slug ),
			'placeholder' => _x( 'First Name', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-first' ),
			'required'    => true
		);

		// Shipping Last Name.
		$new_fields['shipping_last_name'] = array(
			'label'       => __( 'Last Name', self::$plugin_slug ),
			'placeholder' => _x( 'Last Name', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-last' ),
			'clear'       => true,
			'required'    => true
		);

		// Shipping Company.
		$new_fields['shipping_company'] = array(
			'label'       => __( 'Company', self::$plugin_slug ),
			'placeholder' => _x( 'Company', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-wide' )
		);

		// Shipping Country.
		$new_fields['shipping_country'] = array(
			'type'        => 'country',
			'label'       => __( 'Country', self::$plugin_slug ),
			'placeholder' => _x( 'Country', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-first', 'update_totals_on_change', 'address-field' ),
			'required'    => true
		);

		// Shipping Post Code.
		$new_fields['shipping_postcode'] = array(
			'label'       => __( 'Post Code', self::$plugin_slug ),
			'placeholder' => _x( 'Post Code', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-last', 'update_totals_on_change', 'address-field' ),
			'clear'       => true,
			'required'    => true
		);

		// Shipping Anddress 01.
		$new_fields['shipping_address_1'] = array(
			'label'       => __( 'Address', self::$plugin_slug ),
			'placeholder' => _x( 'Address', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-first', 'address-field' ),
			'required'    => true
		);

		// Shipping Number.
		$new_fields['shipping_number'] = array(
			'label'       => __( 'Number', self::$plugin_slug ),
			'placeholder' => _x( 'Number', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-last', 'address-field' ),
			'clear'       => true,
			'required'    => true
		);

		// Shipping Anddress 02.
		$new_fields['shipping_address_2'] = array(
			'label'       => __( 'Address line 2', self::$plugin_slug ),
			'placeholder' => _x( 'Address line 2', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-first', 'address-field' )
		);

		// Shipping Neighborhood.
		$new_fields['shipping_neighborhood'] = array(
			'label'       => __( 'Neighborhood', self::$plugin_slug ),
			'placeholder' => _x( 'Neighborhood', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-last', 'address-field' ),
			'clear'       => true
		);

		// Shipping City.
		$new_fields['shipping_city'] = array(
			'label'       => __( 'City', self::$plugin_slug ),
			'placeholder' => _x( 'City', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-first', 'address-field' ),
			'required'    => true
		);

		// Shipping State.
		$new_fields['shipping_state'] = array(
			'type'        => 'state',
			'label'       => __( 'State', self::$plugin_slug ),
			'placeholder' => _x( 'State', 'placeholder', self::$plugin_slug ),
			'class'       => array( 'form-row-last', 'address-field' ),
			'clear'       => true,
			'required'    => true
		);

		return apply_filters( 'wcbcf_shipping_fields', $new_fields );
	}

	/**
	 * Checks if the CPF is valid.
	 *
	 * @param  string $cpf
	 *
	 * @return bool
	 */
	protected function is_cpf( $cpf ) {
		$cpf = preg_replace( '/[^0-9]/', '', $cpf );

		if ( 11 != strlen( $cpf ) || preg_match( '/^([0-9])\1+$/', $cpf ) ) {
			return false;
		}

		$digit = substr( $cpf, 0, 9 );

		for ( $j = 10; $j <= 11; $j++ ) {
			$sum = 0;

			for( $i = 0; $i< $j-1; $i++ ) {
				$sum += ( $j - $i ) * ( (int) $digit[ $i ] );
			}

			$summod11 = $sum % 11;
			$digit[ $j - 1 ] = $summod11 < 2 ? 0 : 11 - $summod11;
		}

		return $digit[9] == ( (int) $cpf[9] ) && $digit[10] == ( (int) $cpf[10] );
	}

	/**
	 * Checks if the CNPJ is valid.
	 *
	 * @param  string $cnpj
	 *
	 * @return bool
	 */
	protected function is_cnpj( $cnpj ) {
		$cnpj = sprintf( '%014s', preg_replace( '{\D}', '', $cnpj ) );

		if ( 14 != ( strlen( $cnpj ) ) || ( 0 == intval( substr( $cnpj, -4 ) ) ) ) {
			return false;
		}

		for ( $t = 11; $t < 13; ) {
			for ( $d = 0, $p = 2, $c = $t; $c >= 0; $c--, ( $p < 9 ) ? $p++ : $p = 2 ) {
				$d += $cnpj[ $c ] * $p;
			}

			if ( $cnpj[ ++$t ] != ( $d = ( ( 10 * $d ) % 11 ) % 10 ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Add error message in checkout.
	 *
	 * @since  2.9.0
	 *
	 * @param string $message Error message.
	 *
	 * @return string         Displays the error message.
	 */
	protected function add_error( $message ) {
		global $woocommerce;

		if ( version_compare( $woocommerce->version, '2.1', '>=' ) ) {
			wc_add_notice( $message, 'error' );
		} else {
			$woocommerce->add_error( $message );
		}
	}

	/**
	 * Valid checkout fields.
	 *
	 * @return string Displays the error message.
	 */
	public function valid_checkout_fields() {

		// Get plugin settings.
		$settings = get_option( 'wcbcf_settings' );

		if ( isset( $settings['person_type'] ) ) {

			// Check CPF.
			if ( 1 == $_POST['billing_persontype'] ) {
				if ( empty( $_POST['billing_cpf'] ) ) {
					$this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'CPF', self::$plugin_slug ), __( 'is a required field', self::$plugin_slug ) ) );
				}

				if ( isset( $settings['validate_cpf'] ) && ! empty( $_POST['billing_cpf'] ) && ! $this->is_cpf( $_POST['billing_cpf'] ) ) {
					$this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'CPF', self::$plugin_slug ), __( 'is not valid', self::$plugin_slug ) ) );
				}

				if ( isset( $settings['rg'] ) && empty( $_POST['billing_rg'] ) ) {
					$this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'RG', self::$plugin_slug ), __( 'is a required field', self::$plugin_slug ) ) );
				}
			}

			// Check Company and CPNJ.
			if ( 2 == $_POST['billing_persontype'] ) {
				if ( empty( $_POST['billing_company'] ) ) {
					$this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'Company', self::$plugin_slug ), __( 'is a required field', self::$plugin_slug ) ) );
				}

				if ( empty( $_POST['billing_cnpj'] ) ) {
					$this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'CNPJ', self::$plugin_slug ), __( 'is a required field', self::$plugin_slug ) ) );
				}

				if ( isset( $settings['validate_cnpj'] ) && ! empty( $_POST['billing_cnpj'] ) && ! $this->is_cnpj( $_POST['billing_cnpj'] ) ) {
					$this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'CNPJ', self::$plugin_slug ), __( 'is not valid', self::$plugin_slug ) ) );
				}

				if ( isset( $settings['ie'] ) && empty( $_POST['billing_ie'] ) ) {
					$this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'State Registration', self::$plugin_slug ), __( 'is a required field', self::$plugin_slug ) ) );
				}
			}
		}
	}

	/**
	 * Custom country address formats.
	 *
	 * @param  array $formats Defaul formats.
	 *
	 * @return array          New BR format.
	 */
	function localisation_address_formats( $formats ) {
		$formats['BR'] = "{name}\n{address_1}, {number}\n{address_2}\n{neighborhood}\n{city}\n{state}\n{postcode}\n{country}";

		return $formats;
	}

	/**
	 * Custom country address format.
	 *
	 * @param  array $replacements Default replacements.
	 * @param  array $args         Arguments to replace.
	 *
	 * @return array               New replacements.
	 */
	function formatted_address_replacements( $replacements, $args ) {
		extract( $args );

		$replacements['{number}']       = $number;
		$replacements['{neighborhood}'] = $neighborhood;

		return $replacements;
	}

	/**
	 * Custom order formatted billing address.
	 *
	 * @param  array $address Default address.
	 * @param  object $order  Order data.
	 *
	 * @return array          New address format.
	 */
	function order_formatted_billing_address( $address, $order ) {
		$address['number']       = $order->billing_number;
		$address['neighborhood'] = $order->billing_neighborhood;

		return $address;
	}

	/**
	 * Custom order formatted shipping address.
	 *
	 * @param  array $address Default address.
	 * @param  object $order  Order data.
	 *
	 * @return array          New address format.
	 */
	function order_formatted_shipping_address( $address, $order ) {
		$address['number']       = $order->shipping_number;
		$address['neighborhood'] = $order->shipping_neighborhood;

		return $address;
	}

	/**
	 * Custom user column billing address information.
	 *
	 * @param  array $address Default address.
	 * @param  int $user_id   User id.
	 *
	 * @return array          New address format.
	 */
	function user_column_billing_address( $address, $user_id ) {
		$address['number']       = get_user_meta( $user_id, 'billing_number', true );
		$address['neighborhood'] = get_user_meta( $user_id, 'billing_neighborhood', true );

		return $address;
	}

	/**
	 * Custom user column shipping address information.
	 *
	 * @param  array $address Default address.
	 * @param  int $user_id   User id.
	 *
	 * @return array          New address format.
	 */
	function user_column_shipping_address( $address, $user_id ) {
		$address['number']       = get_user_meta( $user_id, 'shipping_number', true );
		$address['neighborhood'] = get_user_meta( $user_id, 'shipping_neighborhood', true );

		return $address;
	}

	/**
	 * Custom my address formatted address.
	 *
	 * @param  array $address   Default address.
	 * @param  int $customer_id Customer ID.
	 * @param  string $name     Field name (billing or shipping).
	 *
	 * @return array            New address format.
	 */
	function my_account_my_address_formatted_address( $address, $customer_id, $name ) {
		$address['number']       = get_user_meta( $customer_id, $name . '_number', true );
		$address['neighborhood'] = get_user_meta( $customer_id, $name . '_neighborhood', true );

		return $address;
	}

	/**
	 * Add custom fields in customer details ajax.
	 *
	 * @since  2.9.0
	 *
	 * @return void
	 */
	public function customer_details_ajax( $customer_data ) {
		$user_id = (int) trim( stripslashes( $_POST['user_id'] ) );
		$type_to_load = esc_attr( trim( stripslashes( $_POST['type_to_load'] ) ) );

		$custom_data = array(
			$type_to_load . '_number' => get_user_meta( $user_id, $type_to_load . '_number', true ),
			$type_to_load . '_neighborhood' => get_user_meta( $user_id, $type_to_load . '_neighborhood', true ),
			$type_to_load . '_persontype' => get_user_meta( $user_id, $type_to_load . '_persontype', true ),
			$type_to_load . '_cpf' => get_user_meta( $user_id, $type_to_load . '_cpf', true ),
			$type_to_load . '_rg' => get_user_meta( $user_id, $type_to_load . '_rg', true ),
			$type_to_load . '_cnpj' => get_user_meta( $user_id, $type_to_load . '_cnpj', true ),
			$type_to_load . '_ie' => get_user_meta( $user_id, $type_to_load . '_ie', true ),
			$type_to_load . '_birthdate' => get_user_meta( $user_id, $type_to_load . '_birthdate', true ),
			$type_to_load . '_sex' => get_user_meta( $user_id, $type_to_load . '_sex', true ),
			$type_to_load . '_cellphone' => get_user_meta( $user_id, $type_to_load . '_cellphone', true )
		);

		return array_merge( $customer_data, $custom_data );
	}
}
