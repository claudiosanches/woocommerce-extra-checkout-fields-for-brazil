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
		include dirname( __FILE__ ) . '/views/html-settings-page.php';
	}

	/**
	 * Plugin settings form fields.
	 */
	public function plugin_settings() {
		$option = 'wcbcf_settings';

		// Set Custom Fields section.
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
				'id'    => 'only_brazil',
				'label' => __( 'If checked the Individuals and Legal Person options will be mandatory only in Brazil.', 'woocommerce-extra-checkout-fields-for-brazil' ),
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
				'menu'  => $option,
				'id'    => 'rg',
				'label' => __( 'If checked show the RG field in billing options.', 'woocommerce-extra-checkout-fields-for-brazil' ),
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
				'menu'  => $option,
				'id'    => 'ie',
				'label' => __( 'If checked show the State Registration field in billing options.', 'woocommerce-extra-checkout-fields-for-brazil' ),
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
				'menu'  => $option,
				'id'    => 'birthdate_sex',
				'label' => __( 'If checked show the Birthdate and Sex field in billing options.', 'woocommerce-extra-checkout-fields-for-brazil' ),
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
				'menu'  => $option,
				'id'    => 'cell_phone',
				'label' => __( 'If checked show the Cell Phone field in billing options.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Set Custom Fields section.
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
				'menu'  => $option,
				'id'    => 'mailcheck',
				'label' => __( 'If checked informs typos in email to users.', 'woocommerce-extra-checkout-fields-for-brazil' ),
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
				'menu'  => $option,
				'id'    => 'maskedinput',
				'label' => __( 'If checked create masks fill for in fields of CPF, CNPJ, Birthdate, Phone and Cell Phone.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Set Custom Fields section.
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
				'menu'  => $option,
				'id'    => 'validate_cpf',
				'label' => __( 'Checks if the CPF is valid.', 'woocommerce-extra-checkout-fields-for-brazil' ),
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
				'menu'  => $option,
				'id'    => 'validate_cnpj',
				'label' => __( 'Checks if the CNPJ is valid.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Register settings.
		register_setting( $option, $option, array( $this, 'validate_options' ) );
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

		include dirname( __FILE__ ) . '/views/html-checkbox-field.php';
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

		include dirname( __FILE__ ) . '/views/html-select-field.php';
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
