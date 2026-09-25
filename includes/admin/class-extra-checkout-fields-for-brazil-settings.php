<?php
/**
 * Extra checkout fields admin settings.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Admin/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extra_Checkout_Fields_For_Brazil_Settings class.
 */
class Extra_Checkout_Fields_For_Brazil_Settings {

	/**
	 * Initialize the settings.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'settings_menu' ), 59 );
		add_action( 'admin_init', array( $this, 'plugin_settings' ) );
		add_action( 'admin_post_csbmw_ship_only_to_brazil', array( $this, 'ship_only_to_brazil' ) );
	}

	/**
	 * Add the settings page.
	 */
	public function settings_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Checkout Fields', 'woocommerce-extra-checkout-fields-for-brazil' ),
			__( 'Checkout Fields', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'manage_options',
			'woocommerce-extra-checkout-fields-for-brazil',
			array( $this, 'html_settings_page' )
		);
	}

	/**
	 * Render the settings page for this plugin.
	 */
	public function html_settings_page() {
		include __DIR__ . '/views/html-settings-page.php';
	}

	/**
	 * Plugin settings form fields.
	 */
	public function plugin_settings() {
		$option = 'wcbcf_settings';

		// Set General Options section.
		add_settings_section(
			'options_section',
			__( 'Custom Fields', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'section_options_callback' ),
			$option,
			$this->section_args( 'bmw-section-fields' )
		);

		// Person Type option.
		add_settings_field(
			'person_type',
			__( 'Enable Person Type', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'select_element_callback' ),
			$option,
			'options_section',
			array(
				'menu'        => $option,
				'id'          => 'person_type',
				'title'       => __( 'Enable Person Type', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => __( 'Individuals enables CPF field and Legal Person enables CNPJ field.', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'options'     => array(
					0 => __( 'None', 'woocommerce-extra-checkout-fields-for-brazil' ),
					1 => __( 'Individuals and Legal Person', 'woocommerce-extra-checkout-fields-for-brazil' ),
					2 => __( 'Individuals Only', 'woocommerce-extra-checkout-fields-for-brazil' ),
					3 => __( 'Legal Person Only', 'woocommerce-extra-checkout-fields-for-brazil' ),
				),
			)
		);

		// Person Type is Required option.
		add_settings_field(
			'only_brazil',
			__( 'Person Type is required only in Brazil?', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'options_section',
			array(
				'menu'  => $option,
				'class' => 'bmw-row-only-brazil',
				'id'    => 'only_brazil',
				'title' => __( 'Person Type is required only in Brazil?', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'If checked the Individuals and Legal Person options will be mandatory only in Brazil.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// RG option.
		add_settings_field(
			'rg',
			__( 'Display RG', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'options_section',
			array(
				'menu'  => $option,
				'class' => 'bmw-row-rg',
				'id'    => 'rg',
				'title' => __( 'Display RG', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'If checked show the RG field in billing options.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// State Registration option.
		add_settings_field(
			'ie',
			__( 'Display State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'options_section',
			array(
				'menu'  => $option,
				'class' => 'bmw-row-ie',
				'id'    => 'ie',
				'title' => __( 'Display State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'If checked show the State Registration field in billing options.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Birth Date option.
		add_settings_field(
			'birthdate',
			__( 'Display Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'options_section',
			array(
				'menu'  => $option,
				'id'    => 'birthdate',
				'title' => __( 'Display Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'If checked show the birthdate field in billing options.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Gender option.
		add_settings_field(
			'gender',
			__( 'Display Gender', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'options_section',
			array(
				'menu'  => $option,
				'id'    => 'gender',
				'title' => __( 'Display Gender', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'If checked, show the gender field in billing options.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Cell Phone option.
		add_settings_field(
			'cell_phone',
			__( 'Display Cell Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'select_element_callback' ),
			$option,
			'options_section',
			array(
				'menu'        => $option,
				'id'          => 'cell_phone',
				'title'       => __( 'Display Cell Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => __( 'Enables the cell phone field on the billing form.', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'options'     => array(
					1  => __( 'Show the Cell Phone field as optional', 'woocommerce-extra-checkout-fields-for-brazil' ),
					2  => __( 'Show the Cell Phone field as required', 'woocommerce-extra-checkout-fields-for-brazil' ),
					-1 => __( 'Change the label of the Phone field to "Cell Phone"', 'woocommerce-extra-checkout-fields-for-brazil' ),
					0  => __( 'Disable', 'woocommerce-extra-checkout-fields-for-brazil' ),
				),
			)
		);

		// Neighborhood is required option.
		add_settings_field(
			'neighborhood_required',
			__( 'Display neighborhood as required', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'options_section',
			array(
				'menu'  => $option,
				'id'    => 'neighborhood_required',
				'title' => __( 'Display neighborhood as required', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'If checked, the neighborhood field will be a required field.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Set Shipping section.
		add_settings_section(
			'shipping_section',
			__( 'Shipping', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'shipping_section_callback' ),
			$option,
			$this->section_args( 'bmw-section-shipping' )
		);

		// Address autofill option.
		add_settings_field(
			'postcode_autofill',
			__( 'Fill the address from the CEP', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'shipping_section',
			array(
				'menu'  => $option,
				'id'    => 'postcode_autofill',
				'title' => __( 'Fill the address from the CEP', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'If checked, the street, neighborhood, city and state are filled once the customer enters a CEP at checkout or in My Account.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// CEP-only cart calculator option.
		add_settings_field(
			'postcode_only_calculator',
			__( 'Ask only for the CEP in the cart', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'shipping_section',
			array(
				'menu'  => $option,
				'id'    => 'postcode_only_calculator',
				'title' => __( 'Ask only for the CEP in the cart', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'If checked, the cart shipping calculator asks only for the CEP and fills the state and city from it. The cart block gets a calculator of its own.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Product page calculator option.
		add_settings_field(
			'product_shipping_calculator',
			__( 'Shipping calculator on product pages', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'shipping_section',
			array(
				'menu'        => $option,
				'id'          => 'product_shipping_calculator',
				'title'       => __( 'Shipping calculator on product pages', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label'       => __( 'If checked, the shipping calculator shows below the add to cart button.', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => __( 'Block themes can place the Shipping Calculator block in the product template instead, which then takes its place.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Set Design section.
		add_settings_section(
			'design_section',
			__( 'Design', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'section_options_callback' ),
			$option,
			$this->section_args( 'bmw-section-design' )
		);

		// Fields Style option.
		add_settings_field(
			'fields_style',
			__( 'Fields Style', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'select_element_callback' ),
			$option,
			'design_section',
			array(
				'menu'        => $option,
				'id'          => 'fields_style',
				'title'       => __( 'Fields Style', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => __( 'Choose the style of the fields. Note: Use Default if you are having problems with how the fields are displayed.', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'options'     => array(
					'wide'         => __( 'Default (full-width fields)', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'side_by_side' => __( 'Side-by-side fields', 'woocommerce-extra-checkout-fields-for-brazil' ),
				),
			)
		);

		// Set jQuery section.
		add_settings_section(
			'jquery_section',
			__( 'jQuery Options', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'section_options_callback' ),
			$option,
			$this->section_args( 'bmw-section-jquery' )
		);

		// Mail Check option.
		add_settings_field(
			'mailcheck',
			__( 'Enable Mail Check', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'jquery_section',
			array(
				'menu'  => $option,
				'id'    => 'mailcheck',
				'title' => __( 'Enable Mail Check', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'If checked informs typos in email to users.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Input Mask option.
		add_settings_field(
			'maskedinput',
			__( 'Enable Input Mask', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'jquery_section',
			array(
				'menu'  => $option,
				'id'    => 'maskedinput',
				'title' => __( 'Enable Input Mask', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'If checked create masks fill for in fields of CPF, CNPJ, Birthdate, Phone and Cell Phone.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Set Custom Fields section.
		add_settings_section(
			'validation_section',
			__( 'Validation', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'section_options_callback' ),
			$option,
			$this->section_args( 'bmw-section-validation' )
		);

		// Validate CPF option.
		add_settings_field(
			'validate_cpf',
			__( 'Validate CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'validation_section',
			array(
				'menu'  => $option,
				'class' => 'bmw-row-validate-cpf',
				'id'    => 'validate_cpf',
				'title' => __( 'Validate CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'Checks if the CPF is valid.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Validate CPF option.
		add_settings_field(
			'validate_cnpj',
			__( 'Validate CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'validation_section',
			array(
				'menu'  => $option,
				'class' => 'bmw-row-validate-cnpj',
				'id'    => 'validate_cnpj',
				'title' => __( 'Validate CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'Checks if the CNPJ is valid.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Register settings.
		register_setting( $option, $option, array( $this, 'validate_options' ) );
	}

	/**
	 * Wrapper markup for a settings section.
	 *
	 * The %s in before_section is replaced with section_class, which is what
	 * gives each card a hook of its own.
	 *
	 * @param string $section_class Class identifying the section.
	 *
	 * @return array
	 */
	protected function section_args( $section_class ) {
		return array(
			'before_section' => '<div class="bmw-settings-card %s">',
			'after_section'  => '</div>',
			'section_class'  => 'bmw-settings-section ' . $section_class,
		);
	}

	/**
	 * Explain what the CEP calculators need, and offer to set it up.
	 */
	public function shipping_section_callback() {
		if ( Extra_Checkout_Fields_For_Brazil_Shipping::is_brazil_only() ) {
			return;
		}

		$url = wp_nonce_url( admin_url( 'admin-post.php?action=csbmw_ship_only_to_brazil' ), 'csbmw_ship_only_to_brazil' );

		include __DIR__ . '/views/html-shipping-notice.php';
	}

	/**
	 * Restrict the store to Brazil, from the button in the shipping section.
	 */
	public function ship_only_to_brazil() {
		check_admin_referer( 'csbmw_ship_only_to_brazil' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to change the store settings.', 'woocommerce-extra-checkout-fields-for-brazil' ), 403 );
		}

		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'BR' ) );
		update_option( 'woocommerce_ship_to_countries', '' );

		add_settings_error( 'wcbcf_settings', 'csbmw_ship_only_to_brazil', __( 'The store now sells and ships only to Brazil.', 'woocommerce-extra-checkout-fields-for-brazil' ), 'success' );
		set_transient( 'settings_errors', get_settings_errors(), 30 );

		wp_safe_redirect( admin_url( 'admin.php?page=woocommerce-extra-checkout-fields-for-brazil&settings-updated=true' ) );
		exit;
	}

	/**
	 * Section null fallback.
	 */
	public function section_options_callback() {
	}

	/**
	 * Checkbox element fallback.
	 *
	 * @param array $args Callback arguments.
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

		$current = intval( $current );

		include __DIR__ . '/views/html-checkbox-field.php';
	}

	/**
	 * Radio element fallback.
	 *
	 * @param array $args Callback arguments.
	 */
	public function radio_element_callback( $args ) {
		$menu    = $args['menu'];
		$id      = $args['id'];
		$options = get_option( $menu );

		if ( isset( $options[ $id ] ) ) {
			$current = $options[ $id ];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : 0;
		}

		$current = intval( $current );

		include __DIR__ . '/views/html-radio-field.php';
	}

	/**
	 * Select element fallback.
	 *
	 * @param array $args Callback arguments.
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

		include __DIR__ . '/views/html-select-field.php';
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
				$output[ $key ] = sanitize_text_field( $input[ $key ] );
			}
		}

		return $output;
	}
}

new Extra_Checkout_Fields_For_Brazil_Settings();
