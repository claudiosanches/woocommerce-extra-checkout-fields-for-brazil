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
			__( 'Brazilian Market', 'woocommerce-extra-checkout-fields-for-brazil' ),
			__( 'Brazilian Market', 'woocommerce-extra-checkout-fields-for-brazil' ),
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

		// Documents section.
		add_settings_section(
			'documents_section',
			__( 'Documents', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'section_options_callback' ),
			$option,
			$this->section_args( 'bmw-section-documents', 'fields' )
		);

		// Person Type option.
		add_settings_field(
			'person_type',
			__( 'Person type', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'select_element_callback' ),
			$option,
			'documents_section',
			array(
				'menu'        => $option,
				'id'          => 'person_type',
				'title'       => __( 'Person type', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => __( 'Individuals are asked for a CPF, and legal persons for a CNPJ.', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'options'     => array(
					0 => __( 'None, ask for no documents', 'woocommerce-extra-checkout-fields-for-brazil' ),
					1 => __( 'Individuals and legal persons', 'woocommerce-extra-checkout-fields-for-brazil' ),
					2 => __( 'Individuals only', 'woocommerce-extra-checkout-fields-for-brazil' ),
					3 => __( 'Legal persons only', 'woocommerce-extra-checkout-fields-for-brazil' ),
				),
			)
		);

		// Documents only in Brazil option.
		add_settings_field(
			'only_brazil',
			__( 'Ask for documents only in Brazil', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'documents_section',
			array(
				'menu'  => $option,
				'class' => 'bmw-row-only-brazil',
				'id'    => 'only_brazil',
				'title' => __( 'Ask for documents only in Brazil', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'Customers with a billing address in another country skip the person type and the documents.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Company option.
		add_settings_field(
			'company',
			__( 'Company name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'select_element_callback' ),
			$option,
			'documents_section',
			array(
				'menu'        => $option,
				'class'       => 'bmw-row-company',
				'id'          => 'company',
				'title'       => __( 'Company name', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'default'     => 'dynamic',
				'description' => $this->company_description(),
				'options'     => array(
					'dynamic'     => __( 'Ask legal persons only, as required', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'woocommerce' => __( 'Follow the WooCommerce setting', 'woocommerce-extra-checkout-fields-for-brazil' ),
				),
			)
		);

		// RG option.
		add_settings_field(
			'rg',
			__( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'field_mode_callback' ),
			$option,
			'documents_section',
			array(
				'menu'        => $option,
				'class'       => 'bmw-row-rg',
				'id'          => 'rg',
				'title'       => __( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => __( 'Identity card number, asked of individuals.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// State Registration option.
		add_settings_field(
			'ie',
			__( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'field_mode_callback' ),
			$option,
			'documents_section',
			array(
				'menu'        => $option,
				'class'       => 'bmw-row-ie',
				'id'          => 'ie',
				'title'       => __( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => __( 'Asked of legal persons. Companies without one tick Exempt to fill in ISENTO.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Validate CPF option.
		add_settings_field(
			'validate_cpf',
			__( 'Check the CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'documents_section',
			array(
				'menu'  => $option,
				'class' => 'bmw-row-validate-cpf',
				'id'    => 'validate_cpf',
				'title' => __( 'Check the CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'Rejects a CPF whose check digits do not match, which catches typos and made-up numbers.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Validate CNPJ option.
		add_settings_field(
			'validate_cnpj',
			__( 'Check the CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'documents_section',
			array(
				'menu'  => $option,
				'class' => 'bmw-row-validate-cnpj',
				'id'    => 'validate_cnpj',
				'title' => __( 'Check the CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'Rejects a CNPJ whose check digits do not match, which catches typos and made-up numbers.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Customer details section.
		add_settings_section(
			'details_section',
			__( 'Customer details', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'section_options_callback' ),
			$option,
			$this->section_args( 'bmw-section-details', 'fields' )
		);

		// Birth Date option.
		add_settings_field(
			'birthdate',
			__( 'Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'field_mode_callback' ),
			$option,
			'details_section',
			array(
				'menu'        => $option,
				'id'          => 'birthdate',
				'title'       => __( 'Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => __( 'Asked of every customer, and refused unless it is a real date.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Gender option.
		add_settings_field(
			'gender',
			__( 'Gender', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'field_mode_callback' ),
			$option,
			'details_section',
			array(
				'menu'        => $option,
				'id'          => 'gender',
				'title'       => __( 'Gender', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => __( 'Asked of every customer, picked from a list.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Cell Phone option.
		add_settings_field(
			'cell_phone',
			__( 'Cell phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'select_element_callback' ),
			$option,
			'details_section',
			array(
				'menu'        => $option,
				'id'          => 'cell_phone',
				'title'       => __( 'Cell phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => __( 'A cell phone field of its own on the billing form, or the Phone field renamed.', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'options'     => array(
					1  => __( 'Separate field, optional', 'woocommerce-extra-checkout-fields-for-brazil' ),
					2  => __( 'Separate field, required', 'woocommerce-extra-checkout-fields-for-brazil' ),
					-1 => __( 'Rename the Phone field to Cell phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
					0  => __( 'Disabled', 'woocommerce-extra-checkout-fields-for-brazil' ),
				),
			)
		);

		// Address section.
		add_settings_section(
			'address_section',
			__( 'Address', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'section_options_callback' ),
			$option,
			$this->section_args( 'bmw-section-address', 'fields' )
		);

		// Neighborhood is required option.
		add_settings_field(
			'neighborhood_required',
			__( 'Require the neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'address_section',
			array(
				'menu'  => $option,
				'id'    => 'neighborhood_required',
				'title' => __( 'Require the neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'Makes Neighborhood a required field on billing and shipping addresses.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		add_settings_field(
			'no_number',
			__( 'Offer a No number option', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'address_section',
			array(
				'menu'  => $option,
				'id'    => 'no_number',
				'title' => __( 'Offer a No number option', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'Adds a No number checkbox inside the Number field, for addresses without a house number.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		add_settings_field(
			'no_number_value',
			__( 'No number value', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'text_element_callback' ),
			$option,
			'address_section',
			array(
				'menu'        => $option,
				'id'          => 'no_number_value',
				'title'       => __( 'No number value', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'default'     => Extra_Checkout_Fields_For_Brazil::NO_NUMBER_VALUE,
				'description' => __( 'What the Number field holds when No number is ticked, such as S/N or N/A.', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => 'bmw-row-no-number-value',
			)
		);

		// Layout section.
		add_settings_section(
			'layout_section',
			__( 'Layout', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'section_options_callback' ),
			$option,
			$this->section_args( 'bmw-section-layout', 'fields' )
		);

		// Fields Style option.
		add_settings_field(
			'fields_style',
			__( 'Field layout', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'select_element_callback' ),
			$option,
			'layout_section',
			array(
				'menu'        => $option,
				'id'          => 'fields_style',
				'title'       => __( 'Field layout', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'default'     => 'side_by_side',
				'description' => $this->fields_style_description(),
				'options'     => array(
					'side_by_side' => __( 'Side by side, two fields per row', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'wide'         => __( 'Full width, one field per row', 'woocommerce-extra-checkout-fields-for-brazil' ),
				),
			)
		);

		// Set Shipping section.
		add_settings_section(
			'shipping_section',
			__( 'Shipping', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'shipping_section_callback' ),
			$option,
			$this->section_args( 'bmw-section-shipping', 'shipping' )
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
				'label' => __( 'Fills in the street, neighborhood, city and state once the customer enters a CEP, at checkout and in My Account.', 'woocommerce-extra-checkout-fields-for-brazil' ),
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
				'label' => __( 'The cart shipping calculator asks only for the CEP and fills in the state and city from it. The cart block gets a calculator of its own.', 'woocommerce-extra-checkout-fields-for-brazil' ),
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
				'label'       => __( 'Shows the shipping calculator below the add to cart button.', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => __( 'Block themes can place the Shipping Calculator block in the product template instead, which then takes its place.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Input helpers section.
		add_settings_section(
			'helpers_section',
			__( 'Input helpers', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'section_options_callback' ),
			$option,
			$this->section_args( 'bmw-section-helpers', 'features' )
		);

		// Mail Check option.
		add_settings_field(
			'mailcheck',
			__( 'Suggest email corrections', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'helpers_section',
			array(
				'menu'  => $option,
				'id'    => 'mailcheck',
				'title' => __( 'Suggest email corrections', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'Offers a fix when the email domain looks mistyped, such as gmail.con.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Input Mask option.
		add_settings_field(
			'maskedinput',
			__( 'Format fields while typing', 'woocommerce-extra-checkout-fields-for-brazil' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'helpers_section',
			array(
				'menu'  => $option,
				'id'    => 'maskedinput',
				'title' => __( 'Format fields while typing', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'label' => __( 'Adds masks to the CPF, CNPJ, CEP, birthdate, phone and cell phone fields.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);

		// Register settings.
		register_setting( $option, $option, array( $this, 'validate_options' ) );
	}

	/**
	 * Settings page tabs, keyed by the name the sections are marked with.
	 *
	 * @return array
	 */
	public function get_tabs() {
		return array(
			'fields'   => __( 'Fields', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'features' => __( 'Features', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'shipping' => __( 'Shipping', 'woocommerce-extra-checkout-fields-for-brazil' ),
		);
	}

	/**
	 * Wrapper markup for a settings section.
	 *
	 * The %s in before_section is replaced with section_class, which is what
	 * gives each card a hook of its own.
	 *
	 * @param string $section_class Class identifying the section.
	 * @param string $tab           Tab the section is shown in.
	 *
	 * @return array
	 */
	protected function section_args( $section_class, $tab ) {
		return array(
			'before_section' => '<div class="bmw-settings-card %s" data-bmw-tab="' . esc_attr( $tab ) . '">',
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

		wp_safe_redirect( admin_url( 'admin.php?page=woocommerce-extra-checkout-fields-for-brazil&tab=shipping&settings-updated=true' ) );
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
	 * Select between off, optional and required for a field.
	 *
	 * @param array $args Callback arguments.
	 */
	public function field_mode_callback( $args ) {
		$menu    = $args['menu'];
		$id      = $args['id'];
		$current = Extra_Checkout_Fields_For_Brazil::field_mode( $id, (array) get_option( $menu, array() ) );
		$current = 'disabled' === $current ? '' : $current;

		$args['options'] = array(
			''         => __( 'Disabled', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'optional' => __( 'Optional', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'required' => __( 'Required', 'woocommerce-extra-checkout-fields-for-brazil' ),
		);

		include __DIR__ . '/views/html-select-field.php';
	}

	/**
	 * Describe the company setting along with what WooCommerce has it set to.
	 *
	 * @return string
	 */
	protected function company_description() {
		$labels  = array(
			'hidden'   => __( 'hidden', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'optional' => __( 'optional', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'required' => __( 'required', 'woocommerce-extra-checkout-fields-for-brazil' ),
		);
		$current = class_exists( \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::class ) ? \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::get_company_field_visibility() : 'optional';

		return sprintf(
			/* translators: %s: hidden, optional or required. */
			__( 'Asking legal persons only makes the field required for them and hides it from individuals. Otherwise WooCommerce\'s setting applies, currently %s, which is changed in the checkout page editor or in the Customizer.', 'woocommerce-extra-checkout-fields-for-brazil' ),
			isset( $labels[ $current ] ) ? $labels[ $current ] : $current
		);
	}

	/**
	 * Describe the field layout along with which forms it reaches.
	 *
	 * @return string
	 */
	protected function fields_style_description() {
		$description = __( 'How the classic checkout and the My Account address forms arrange the fields. Pick full width if they look broken in your theme.', 'woocommerce-extra-checkout-fields-for-brazil' );

		if ( class_exists( \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::class ) && \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::is_checkout_block_default() ) {
			$description .= ' ' . __( 'Your checkout page uses the Checkout block, which arranges its own fields, so this changes only the My Account address forms.', 'woocommerce-extra-checkout-fields-for-brazil' );
		}

		return $description;
	}

	/**
	 * Text element fallback.
	 *
	 * @param array $args Callback arguments.
	 */
	public function text_element_callback( $args ) {
		$menu    = $args['menu'];
		$id      = $args['id'];
		$options = (array) get_option( $menu, array() );
		$current = isset( $options[ $id ] ) && '' !== $options[ $id ] ? $options[ $id ] : ( isset( $args['default'] ) ? $args['default'] : '' );

		include __DIR__ . '/views/html-text-field.php';
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

		// Older installs stored values that match no option, which the browser
		// would show as the first one whatever the plugin does with them.
		if ( isset( $args['default'] ) && ! array_key_exists( $current, $args['options'] ) ) {
			$current = $args['default'];
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
