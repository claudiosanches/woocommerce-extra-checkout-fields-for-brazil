<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Plugin admin class.
 */
class Extra_Checkout_Fields_For_Brazil_Admin {

	/**
	 * Slug of the plugin screen.
	 *
	 * @var string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin admin.
	 */
	public function __construct() {
		global $woocommerce;

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ), 59 );

		// Init plugin options form.
		add_action( 'admin_init', array( $this, 'plugin_settings' ) );

		// Custom shop_order details.
		add_filter( 'woocommerce_admin_billing_fields', array( $this, 'shop_order_billing_fields' ) );
		add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'shop_order_shipping_fields' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'order_data_after_billing_address' ) );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'order_data_after_shipping_address' ) );
		add_action( 'save_post', array( $this, 'save_shop_data_fields' ) );

		// Custom address format.
		if ( version_compare( $woocommerce->version, '2.0.6', '>=' ) ) {
			add_filter( 'woocommerce_customer_meta_fields', array( $this, 'user_edit_fields' ) );
		}

		$this->update();
	}

	/**
	 * Admin scripts
	 *
	 * @return void
	 */
	public function admin_scripts() {
		global $woocommerce, $post_type;

		if ( 'shop_order' == $post_type ) {

			// Get plugin settings.
			$settings = get_option( 'wcbcf_settings' );

			// Styles.
			wp_enqueue_style( 'woocommerce-extra-checkout-fields-for-brazil-admin', plugins_url( 'assets/css/admin/admin.css', plugin_dir_path( __FILE__ ) ), array(), Extra_Checkout_Fields_For_Brazil::VERSION );

			// Shop order.
			if ( version_compare( $woocommerce->version, '2.1', '>=' ) ) {
				$shop_order_js = plugins_url( 'assets/js/admin/shop-order.min.js', plugin_dir_path( __FILE__ ) );
			} else {
				$shop_order_js = plugins_url( 'assets/js/admin/shop-order.old.min.js', plugin_dir_path( __FILE__ ) );
			}

			wp_enqueue_script( 'woocommerce-extra-checkout-fields-for-brazil-shop-order', $shop_order_js, array( 'jquery' ), Extra_Checkout_Fields_For_Brazil::VERSION, true );

			// Localize strings.
			wp_localize_script(
				'woocommerce-extra-checkout-fields-for-brazil-shop-order',
				'wcbcf_writepanel_params',
				array(
					'load_message' => __( 'Load the customer extras data?', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'copy_message' => __( 'Also copy the data of number and neighborhood?', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'person_type'  => $settings['person_type']
				)
			);
		}

		if ( isset( $this->plugin_screen_hook_suffix ) && $this->plugin_screen_hook_suffix == get_current_screen()->id ) {
			wp_enqueue_script( 'woocommerce-extra-checkout-fields-for-brazil-admin', plugins_url( 'assets/js/admin/admin.min.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), Extra_Checkout_Fields_For_Brazil::VERSION );
		}
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @return void
	 */
	public function add_plugin_admin_menu() {
		$this->plugin_screen_hook_suffix = add_submenu_page(
			'woocommerce',
			__( 'Checkout Fields', 'woocommerce-extra-checkout-fields-for-brazil' ),
			__( 'Checkout Fields', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'manage_options',
			'woocommerce-extra-checkout-fields-for-brazil',
			array( $this, 'display_plugin_admin_page' )
		);
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @return void
	 */
	public function display_plugin_admin_page() {
		include_once 'views/html-admin-options.php';
	}

	/**
	 * Plugin settings form fields.
	 *
	 * @return void.
	 */
	public function plugin_settings() {
		$option = 'wcbcf_settings';

		// Set Custom Fields cection.
		add_settings_section(
			'options_section',
			__( 'Checkout Custom Fields:', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'section_options_callback' ),
			$option
		);

		// Person Type option.
		add_settings_field(
			'person_type',
			__( 'Display Person Type:', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'select_element_callback' ),
			$option,
			'options_section',
			array(
				'menu' => $option,
				'id' => 'person_type',
				'description' => __( 'Individuals enables CPF field and Legal Person enables CNPJ field.', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'options' => array(
					0 => __( 'None', 'woocommerce-extra-checkout-fields-for-brazil' ),
					1 => __( 'Individuals and Legal Person', 'woocommerce-extra-checkout-fields-for-brazil' ),
					2 => __( 'Individuals only', 'woocommerce-extra-checkout-fields-for-brazil' ),
					3 => __( 'Legal Person only', 'woocommerce-extra-checkout-fields-for-brazil' ),
				)
			)
		);

		// RG option.
		add_settings_field(
			'rg',
			__( 'Display RG:', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'options_section',
			array(
				'menu' => $option,
				'id' => 'rg',
				'label' => __( 'If checked show the RG field in billing options.', 'woocommerce-extra-checkout-fields-for-brazil' )
			)
		);

		// State Registration option.
		add_settings_field(
			'ie',
			__( 'Display State Registration:', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'options_section',
			array(
				'menu' => $option,
				'id' => 'ie',
				'label' => __( 'If checked show the State Registration field in billing options.', 'woocommerce-extra-checkout-fields-for-brazil' )
			)
		);

		// Birthdate and Sex option.
		add_settings_field(
			'birthdate_sex',
			__( 'Display Birthdate and Sex:', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'options_section',
			array(
				'menu' => $option,
				'id' => 'birthdate_sex',
				'label' => __( 'If checked show the Birthdate and Sex field in billing options.', 'woocommerce-extra-checkout-fields-for-brazil' )
			)
		);

		// Cell Phone option.
		add_settings_field(
			'cell_phone',
			__( 'Display Cell Phone:', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'options_section',
			array(
				'menu' => $option,
				'id' => 'cell_phone',
				'label' => __( 'If checked show the Cell Phone field in billing options.', 'woocommerce-extra-checkout-fields-for-brazil' )
			)
		);

		// Set Custom Fields cection.
		add_settings_section(
			'jquery_section',
			__( 'jQuery Options:', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'section_options_callback' ),
			$option
		);

		// Mail Check option.
		add_settings_field(
			'mailcheck',
			__( 'Enable Mail Check:', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'jquery_section',
			array(
				'menu' => $option,
				'id' => 'mailcheck',
				'label' => __( 'If checked informs typos in email to users.', 'woocommerce-extra-checkout-fields-for-brazil' )
			)
		);

		// Input Mask option.
		add_settings_field(
			'maskedinput',
			__( 'Enable Input Mask:', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'jquery_section',
			array(
				'menu' => $option,
				'id' => 'maskedinput',
				'label' => __( 'If checked create masks fill for in fields of CPF, CNPJ, Birthdate, Phone and Cell Phone.', 'woocommerce-extra-checkout-fields-for-brazil' )
			)
		);

		// Address Autocomplete option.
		add_settings_field(
			'addresscomplete',
			__( 'Enable Address Autocomplete:', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'jquery_section',
			array(
				'menu' => $option,
				'id' => 'addresscomplete',
				'label' => __( 'If checked automatically complete the address fields based on the zip code.', 'woocommerce-extra-checkout-fields-for-brazil' )
			)
		);

		// Set Custom Fields cection.
		add_settings_section(
			'validation_section',
			__( 'Validation:', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'section_options_callback' ),
			$option
		);

		// Validate CPF option.
		add_settings_field(
			'validate_cpf',
			__( 'Validate CPF:', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'validation_section',
			array(
				'menu' => $option,
				'id' => 'validate_cpf',
				'label' => __( 'Checks if the CPF is valid.', 'woocommerce-extra-checkout-fields-for-brazil' )
			)
		);

		// Validate CPF option.
		add_settings_field(
			'validate_cnpj',
			__( 'Validate CNPJ:', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'validation_section',
			array(
				'menu' => $option,
				'id' => 'validate_cnpj',
				'label' => __( 'Checks if the CNPJ is valid.', 'woocommerce-extra-checkout-fields-for-brazil' )
			)
		);

		// Register settings.
		register_setting( $option, $option, array( $this, 'validate_options' ) );
	}

	/**
	 * Section null fallback.
	 *
	 * @return void.
	 */
	public function section_options_callback() {

	}

	/**
	 * Checkbox element fallback.
	 *
	 * @return string Checkbox field.
	 */
	public function checkbox_element_callback( $args ) {
		$menu    = $args['menu'];
		$id      = $args['id'];
		$options = get_option( $menu );

		if ( isset( $options[ $id ] ) ) {
			$current = $options[ $id ];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '0';
		}

		$html = '<input type="checkbox" id="' . $id . '" name="' . $menu . '[' . $id . ']" value="1"' . checked( 1, $current, false ) . '/>';

		if ( isset( $args['label'] ) ) {
			$html .= ' <label for="' . $id . '">' . $args['label'] . '</label>';
		}

		if ( isset( $args['description'] ) ) {
			$html .= '<p class="description">' . $args['description'] . '</p>';
		}

		echo $html;
	}

	/**
	 * Select element fallback.
	 *
	 * @return string Select field.
	 */
	public function select_element_callback( $args ) {
		$menu    = $args['menu'];
		$id      = $args['id'];
		$options = get_option( $menu );

		if ( isset( $options[ $id ] ) ) {
			$current = $options[ $id ];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : 0;
		}

		$html = '<select id="' . $id . '" name="' . $menu . '[' . $id . ']">';
			foreach ( $args['options'] as $key => $value ) {
				$html .= sprintf( '<option value="%s" %s>%s</option>', $key, selected( $current, $key, false ), $value );
			}
		$html .= '</select>';

		if ( isset( $args['description'] ) ) {
			$html .= '<p class="description">' . $args['description'] . '</p>';
		}

		echo $html;
	}

	/**
	 * Valid options.
	 *
	 * @param  array $input options to valid.
	 *
	 * @return array        validated options.
	 */
	public function validate_options( $input ) {
		$output = array();

		// Loop through each of the incoming options.
		foreach ( $input as $key => $value ) {
			// Check to see if the current option has a value. If so, process it.
			if ( isset( $input[ $key ] ) ) {
				$output[ $key ] = woocommerce_clean( $input[ $key ] );
			}
		}

		return $output;
	}

	/**
	 * Custom shop order billing fields.
	 *
	 * @param  array $data Default order billing fields.
	 *
	 * @return array       Custom order billing fields.
	 */
	public function shop_order_billing_fields( $data ) {
		global $woocommerce;

		// Get plugin settings.
		$settings = get_option( 'wcbcf_settings' );

		$billing_data['first_name'] = array(
			'label' => __( 'First Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$billing_data['last_name'] = array(
			'label' => __( 'Last Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);

		if ( 0 != $settings['person_type'] ) {
			if ( 1 == $settings['person_type'] ) {
				$billing_data['persontype'] = array(
					'type'    => 'select',
					'label'   => __( 'Person type', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'options' => array(
						'0' => __( 'Select', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'1' => __( 'Individuals', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'2' => __( 'Legal Person', 'woocommerce-extra-checkout-fields-for-brazil' )
					)
				);
			}

			if ( 1 == $settings['person_type'] || 2 == $settings['person_type'] ) {
				$billing_data['cpf'] = array(
					'label' => __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
				);
				if ( isset( $settings['rg'] ) ) {
					$billing_data['rg'] = array(
						'label' => __( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ),
					);
				}
			}

			if ( 1 == $settings['person_type'] || 3 == $settings['person_type'] ) {
				$billing_data['company'] = array(
					'label' => __( 'Company Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
				);
				$billing_data['cnpj'] = array(
					'label' => __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
				);
				if ( isset( $settings['ie'] ) ) {
					$billing_data['ie'] = array(
						'label' => __( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ),
					);
				}
			}

		} else {
			$billing_data['company'] = array(
				'label' => __( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'show'  => false
			);
		}

		if ( isset( $settings['birthdate_sex'] ) ) {
			$billing_data['birthdate'] = array(
				'label' => __( 'Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' )
			);
			$billing_data['sex'] = array(
				'label' => __( 'Sex', 'woocommerce-extra-checkout-fields-for-brazil' )
			);
		}

		$billing_data['address_1'] = array(
			'label' => __( 'Address 1', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$billing_data['number'] = array(
			'label' => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$billing_data['address_2'] = array(
			'label' => __( 'Address 2', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$billing_data['neighborhood'] = array(
			'label' => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$billing_data['city'] = array(
			'label' => __( 'City', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$billing_data['state'] = array(
			'label' => __( 'State', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$billing_data['country'] = array(
			'label'   => __( 'Country', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'    => false,
			'type'    => 'select',
			'options' => array(
				'' => __( 'Select a country&hellip;', 'woocommerce-extra-checkout-fields-for-brazil' )
			) + $woocommerce->countries->get_allowed_countries()
		);
		$billing_data['postcode'] = array(
			'label' => __( 'Postcode', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);

		$billing_data['phone'] = array(
			'label' => __( 'Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
		);

		if ( isset( $settings['cell_phone'] ) ) {
			$billing_data['cellphone'] = array(
				'label' => __( 'Cell Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
			);
		}

		$billing_data['email'] = array(
			'label' => __( 'Email', 'woocommerce-extra-checkout-fields-for-brazil' ),
		);


		return apply_filters( 'wcbcf_admin_billing_fields', $billing_data );
	}

	/**
	 * Custom shop order shipping fields.
	 *
	 * @param  array $data Default order shipping fields.
	 *
	 * @return array       Custom order shipping fields.
	 */
	public function shop_order_shipping_fields( $data ) {
		global $woocommerce;

		$shipping_data['first_name'] = array(
			'label' => __( 'First Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$shipping_data['last_name'] = array(
			'label' => __( 'Last Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$shipping_data['company'] = array(
			'label' => __( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$shipping_data['address_1'] = array(
			'label' => __( 'Address 1', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$shipping_data['number'] = array(
			'label' => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$shipping_data['address_2'] = array(
			'label' => __( 'Address 2', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$shipping_data['neighborhood'] = array(
			'label' => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$shipping_data['city'] = array(
			'label' => __( 'City', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$shipping_data['state'] = array(
			'label' => __( 'State', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);
		$shipping_data['country'] = array(
			'label'   => __( 'Country', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'    => false,
			'type'    => 'select',
			'options' => array(
				'' => __( 'Select a country&hellip;', 'woocommerce-extra-checkout-fields-for-brazil' )
			) + $woocommerce->countries->get_allowed_countries()
		);
		$shipping_data['postcode'] = array(
			'label' => __( 'Postcode', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'show'  => false
		);

		return apply_filters( 'wcbcf_admin_shipping_fields', $shipping_data );
	}

	/**
	 * Custom billing admin fields.
	 *
	 * @param  object $order Order data.
	 *
	 * @return string        Custom information.
	 */
	public function order_data_after_billing_address( $order ) {
		global $woocommerce;

		// Get plugin settings.
		$settings = get_option( 'wcbcf_settings' );

		// Use nonce for verification.
		wp_nonce_field( basename( __FILE__ ), 'wcbcf_meta_fields' );

		$html = '<div class="wcbcf-address">';

		if ( ! $order->get_formatted_billing_address() ) {
			$html .= '<p class="none_set"><strong>' . __( 'Address', 'woocommerce-extra-checkout-fields-for-brazil' ) . ':</strong> ' . __( 'No billing address set.', 'woocommerce-extra-checkout-fields-for-brazil' ) . '</p>';
		} else {

			$html .= '<p><strong>' . __( 'Address', 'woocommerce-extra-checkout-fields-for-brazil' ) . ':</strong><br />';
			if ( version_compare( $woocommerce->version, '2.0.5', '<=' ) ) {
				$html .= $order->billing_first_name . ' ' . $order->billing_last_name . '<br />';
				$html .= $order->billing_address_1 . ', ' . $order->billing_number . '<br />';
				$html .= $order->billing_address_2 . '<br />';
				$html .= $order->billing_neighborhood . '<br />';
				$html .= $order->billing_city . '<br />';
				if ( $woocommerce->countries->states[ $order->billing_country ] ) {
					$html .= $woocommerce->countries->states[ $order->billing_country ][ $order->billing_state ] . '<br />';
				} else {
					$html .= $order->billing_state . '<br />';
				}

				$html .= $order->billing_postcode . '<br />';
				$html .= $woocommerce->countries->countries[$order->billing_country] . '<br />';

			} else {
				$html .= $order->get_formatted_billing_address();
			}

			$html .= '</p>';
		}

		$html .= '<h4>' . __( 'Customer data', 'woocommerce-extra-checkout-fields-for-brazil' ) . '</h4>';

		$html .= '<p>';

		if ( 0 != $settings['person_type'] ) {

			// Person type information.
			if ( ( 1 == $order->billing_persontype && 1 == $settings['person_type'] ) || 2 == $settings['person_type'] ) {
				$html .= '<strong>' . __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . $order->billing_cpf . '<br />';

				if ( isset( $settings['rg'] ) ) {
					$html .= '<strong>' . __( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . $order->billing_rg . '<br />';
				}
			}

			if ( ( 2 == $order->billing_persontype && 1 == $settings['person_type'] ) || 3 == $settings['person_type'] ) {
				$html .= '<strong>' . __( 'Company Name', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . $order->billing_company . '<br />';
				$html .= '<strong>' . __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . $order->billing_cnpj . '<br />';

				if ( isset( $settings['ie'] ) ) {
					$html .= '<strong>' . __( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . $order->billing_ie . '<br />';
				}
			}
		} else {
			$html .= '<strong>' . __( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . $order->billing_company . '<br />';
		}

		if ( isset( $settings['birthdate_sex'] ) ) {

			// Birthdate information.
			$html .= '<strong>' . __( 'Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . $order->billing_birthdate . '<br />';

			// Sex Information.
			$html .= '<strong>' . __( 'Sex', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . $order->billing_sex . '<br />';
		}

		$html .= '<strong>' . __( 'Phone', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . $order->billing_phone . '<br />';

		// Cell Phone Information.
		if ( isset( $settings['cell_phone'] ) ) {
			$html .= '<strong>' . __( 'Cell Phone', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . $order->billing_cellphone . '<br />';
		}

		$html .= '<strong>' . __( 'Email', 'woocommerce-extra-checkout-fields-for-brazil' ) . ': </strong>' . $order->billing_email . '<br />';

		$html .= '</p>';

		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1.12', '<=' ) ) {
			if ( $woocommerce->payment_gateways() ) {
				$payment_gateways = $woocommerce->payment_gateways->payment_gateways();

				$payment_method = ! empty( $order->payment_method ) ? $order->payment_method : '';

				if ( $payment_method ) {
					$html .= '<p><strong>' . __( 'Payment Method', 'woocommerce-extra-checkout-fields-for-brazil' ) . ':</strong> ' . ( isset( $payment_gateways[ $payment_method ] ) ? esc_html( $payment_gateways[ $payment_method ]->get_title() ) : esc_html( $payment_method ) ) . '</p>';
				}
			}
		}

		$html .= '</div>';

		echo $html;
	}

	/**
	 * Custom billing admin fields.
	 *
	 * @param  object $order Order data.
	 *
	 * @return string        Custom information.
	 */
	public function order_data_after_shipping_address( $order ) {
		global $post, $woocommerce;

		// Get plugin settings.
		$settings = get_option( 'wcbcf_settings' );

		$html = '<div class="wcbcf-address">';

		if ( ! $order->get_formatted_shipping_address() ) {
			$html .= '<p class="none_set"><strong>' . __( 'Address', 'woocommerce-extra-checkout-fields-for-brazil' ) . ':</strong> ' . __( 'No shipping address set.', 'woocommerce-extra-checkout-fields-for-brazil' ) . '</p>';
		} else {

			$html .= '<p><strong>' . __( 'Address', 'woocommerce-extra-checkout-fields-for-brazil' ) . ':</strong><br />';
			if ( version_compare( $woocommerce->version, '2.0.5', '<=' ) ) {
				$html .= $order->billing_first_name . ' ' . $order->billing_last_name . '<br />';
				$html .= $order->billing_address_1 . ', ' . $order->billing_number . '<br />';
				$html .= $order->billing_address_2 . '<br />';
				$html .= $order->billing_neighborhood . '<br />';
				$html .= $order->billing_city . '<br />';
				if ( $woocommerce->countries->states[ $order->billing_country ] ) {
					$html .= $woocommerce->countries->states[ $order->billing_country ][ $order->billing_state ] . '<br />';
				} else {
					$html .= $order->billing_state . '<br />';
				}

				$html .= $order->billing_postcode . '<br />';
				$html .= $woocommerce->countries->countries[$order->billing_country] . '<br />';
			} else {
				$html .= $order->get_formatted_shipping_address();
			}

			$html .= '</p>';
		}

		if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' == get_option( 'woocommerce_enable_order_comments', 'yes' ) ) && $post->post_excerpt ) {
			$html .= '<p><strong>' . __( 'Customer Note', 'woocommerce' ) . ':</strong><br />' . nl2br( esc_html( $post->post_excerpt ) ) . '</p>';
		}

		$html .= '</div>';

		echo $html;
	}

	/**
	 * Save custom fields.
	 *
	 * @param  int  $post_id Post ID.
	 *
	 * @return mixed
	 */
	public function save_shop_data_fields( $post_id ) {
		global $post_type;

		if ( 'shop_order' != $post_type ) {
			return $post_id;
		}

		// Verify nonce.
		if ( ! isset( $_POST['wcbcf_meta_fields'] ) || ! wp_verify_nonce( $_POST['wcbcf_meta_fields'], basename( __FILE__ ) ) ) {
			return $post_id;
		}

		// Verify if this is an auto save routine.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Verify current user.
		if ( ! current_user_can( 'edit_pages', $post_id ) ) {
			return $post_id;
		}

		// Get plugin settings.
		$settings = get_option( 'wcbcf_settings' );

		// Update options.
		update_post_meta( $post_id, '_billing_number', woocommerce_clean( $_POST['_billing_number'] ) );
		update_post_meta( $post_id, '_billing_neighborhood', woocommerce_clean( $_POST['_billing_neighborhood'] ) );
		update_post_meta( $post_id, '_shipping_number', woocommerce_clean( $_POST['_shipping_number'] ) );
		update_post_meta( $post_id, '_shipping_neighborhood', woocommerce_clean( $_POST['_shipping_neighborhood'] ) );

		if ( 0 != $settings['person_type'] ) {
			if ( 1 == $settings['person_type'] ) {
				update_post_meta( $post_id, '_billing_persontype', woocommerce_clean( $_POST['_billing_persontype'] ) );
			}

			if ( 1 == $settings['person_type'] || 2 == $settings['person_type'] ) {
				update_post_meta( $post_id, '_billing_cpf', woocommerce_clean( $_POST['_billing_cpf'] ) );

				if ( isset( $settings['rg'] ) ) {
					update_post_meta( $post_id, '_billing_rg', woocommerce_clean( $_POST['_billing_rg'] ) );
				}
			}

			if ( 1 == $settings['person_type'] || 3 == $settings['person_type'] ) {
				update_post_meta( $post_id, '_billing_cnpj', woocommerce_clean( $_POST['_billing_cnpj'] ) );

				if ( isset( $settings['ie'] ) ) {
					update_post_meta( $post_id, '_billing_ie', woocommerce_clean( $_POST['_billing_ie'] ) );
				}
			}
		}

		if ( isset( $settings['birthdate_sex'] ) ) {
			update_post_meta( $post_id, '_billing_birthdate', woocommerce_clean( $_POST['_billing_birthdate'] ) );
			update_post_meta( $post_id, '_billing_sex', woocommerce_clean( $_POST['_billing_sex'] ) );
		}

		if ( isset( $settings['cell_phone'] ) ) {
			update_post_meta( $post_id, '_billing_cellphone', woocommerce_clean( $_POST['_billing_cellphone'] ) );
		}
	}

	/**
	 * Custom user edit fields.
	 *
	 * @param  array $fields Default fields.
	 *
	 * @return array         Custom fields.
	 */
	public function user_edit_fields( $fields ) {
		unset( $fields );

		// Get plugin settings.
		$settings = get_option( 'wcbcf_settings' );

		// Billing fields.
		$fields['billing']['title'] = __( 'Customer Billing Address', 'woocommerce-extra-checkout-fields-for-brazil' );
		$fields['billing']['fields']['billing_first_name'] = array(
			'label' => __( 'First name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['billing']['fields']['billing_last_name'] = array(
			'label' => __( 'Last name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);

		if ( 0 != $settings['person_type'] ) {

			if ( 1 == $settings['person_type'] || 2 == $settings['person_type'] ) {
				$fields['billing']['fields']['billing_cpf'] = array(
					'label' => __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'description' => ''
				);

				if ( isset( $settings['rg'] ) ) {
					$fields['billing']['fields']['billing_rg'] = array(
						'label' => __( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'description' => ''
					);
				}
			}

			if ( 1 == $settings['person_type'] || 3 == $settings['person_type'] ) {
				$fields['billing']['fields']['billing_company'] = array(
					'label' => __( 'Company Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'description' => ''
				);
				$fields['billing']['fields']['billing_cnpj'] = array(
					'label' => __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'description' => ''
				);

				if ( isset( $settings['ie'] ) ) {
					$fields['billing']['fields']['billing_ie'] = array(
						'label' => __( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'description' => ''
					);
				}
			}
		} else {
			$fields['billing']['fields']['billing_company'] = array(
				'label' => __( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => ''
			);
		}

		if ( isset( $settings['birthdate_sex'] ) ) {
			$fields['billing']['fields']['billing_birthdate'] = array(
				'label' => __( 'Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => ''
			);
			$fields['billing']['fields']['billing_sex'] = array(
				'label' => __( 'Sex', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => ''
			);
		}

		$fields['billing']['fields']['billing_country'] = array(
			'label' => __( 'Country', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => __( '2 letter Country code', 'woocommerce-extra-checkout-fields-for-brazil' )
		);
		$fields['billing']['fields']['billing_postcode'] = array(
			'label' => __( 'Postcode', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['billing']['fields']['billing_address_1'] = array(
			'label' => __( 'Address 1', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['billing']['fields']['billing_number'] = array(
			'label' => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['billing']['fields']['billing_address_2'] = array(
			'label' => __( 'Address 2', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['billing']['fields']['billing_neighborhood'] = array(
			'label' => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['billing']['fields']['billing_city'] = array(
			'label' => __( 'City', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['billing']['fields']['billing_state'] = array(
			'label' => __( 'State', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => __( 'State code', 'woocommerce-extra-checkout-fields-for-brazil' )
		);
		$fields['billing']['fields']['billing_phone'] = array(
			'label' => __( 'Telephone', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);

		if ( isset( $settings['cell_phone'] ) ) {
			$fields['billing']['fields']['billing_cellphone'] = array(
				'label' => __( 'Cell Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => ''
			);
		}

		$fields['billing']['fields']['billing_email'] = array(
			'label' => __( 'Email', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);

		// Shipping fields.
		$fields['shipping']['title'] = __( 'Customer Shipping Address', 'woocommerce-extra-checkout-fields-for-brazil' );
		$fields['shipping']['fields']['shipping_first_name'] = array(
			'label' => __( 'First name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['shipping']['fields']['shipping_last_name'] = array(
			'label' => __( 'Last name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['shipping']['fields']['shipping_company'] = array(
			'label' => __( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['shipping']['fields']['shipping_country'] = array(
			'label' => __( 'Country', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => __( '2 letter Country code', 'woocommerce-extra-checkout-fields-for-brazil' )
		);
		$fields['shipping']['fields']['shipping_postcode'] = array(
			'label' => __( 'Postcode', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['shipping']['fields']['shipping_address_1'] = array(
			'label' => __( 'Address 1', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['shipping']['fields']['shipping_number'] = array(
			'label' => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['shipping']['fields']['shipping_address_2'] = array(
			'label' => __( 'Address 2', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['shipping']['fields']['shipping_neighborhood'] = array(
			'label' => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['shipping']['fields']['shipping_city'] = array(
			'label' => __( 'City', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => ''
		);
		$fields['shipping']['fields']['shipping_state'] = array(
			'label' => __( 'State', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'description' => __( 'State code', 'woocommerce-extra-checkout-fields-for-brazil' )
		);

		$new_fields = apply_filters( 'wcbcf_customer_meta_fields', $fields );

		return $new_fields;
	}

	/**
	 * Maybe install.
	 *
	 * @return void
	 */
	public function update() {
		$version = get_option( 'wcbcf_version', '0' );

		if ( version_compare( $version, Extra_Checkout_Fields_For_Brazil::VERSION, '<' ) ) {

			// Update to version 3.0.0.
			if ( version_compare( $version, '3.0.0', '<' ) ) {

				if ( isset( $options['person_type'] ) ) {
					$options['person_type'] = 1;
				} else {
					$options['person_type'] = 0;
				}

				update_option( 'wcbcf_settings', $options );
			}

			// Save plugin version.
			update_option( 'wcbcf_version', Extra_Checkout_Fields_For_Brazil::VERSION );
		}
	}
}

new Extra_Checkout_Fields_For_Brazil_Admin();
