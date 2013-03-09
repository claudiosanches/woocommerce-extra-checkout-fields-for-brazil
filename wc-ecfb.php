<?php
/**
 * Plugin Name: WooCommerce Extra Checkout Fields for Brazil
 * Plugin URI: http://www.claudiosmweb.com/
 * Description: Adiciona novos campos para Pessoa Física ou Juridíca, Data de Nascimento, Sexo, Bairro e Celular. Além de máscaras em campos, aviso de e-mail incorreto e auto preenchimento dos campos de endereço pelo CEP.
 * Author: claudiosanches
 * Author URI: http://www.claudiosmweb.com/
 * Version: 1.2.2
 * License: GPLv2 or later
 * Text Domain: wcbcf
 * Domain Path: /languages/
 */

/**
 * WC_BrazilianCheckoutFields class.
 */
class WC_BrazilianCheckoutFields {

    /**
     * Construct.
     */
    public function __construct() {

        $settings = get_option( 'wcbcf_settings' );

        // Load textdomain.
        add_action( 'plugins_loaded', array( &$this, 'languages' ), 0 );

        // New checkout fields.
        add_filter( 'woocommerce_checkout_fields', array( &$this, 'checkout_billing_fields' ) );
        add_filter( 'woocommerce_checkout_fields', array( &$this, 'checkout_shipping_fields' ) );

        // Valid checkout fields.
        add_action( 'woocommerce_checkout_process', array( &$this, 'valid_checkout_fields' ) );

        // Load scripts in front-end.
        add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );

        // Default options.
        register_activation_hook( __FILE__, array( &$this, 'default_settings' ) );

        // Add menu.
        add_action( 'admin_menu', array( &$this, 'menu' ) );

        // Init plugin options form.
        add_action( 'admin_init', array( &$this, 'plugin_settings' ) );

        // Load custom order data.
        add_filter( 'woocommerce_load_order_data', array( &$this, 'load_order_data' ) );

        if ( isset( $settings['neighborhood'] ) ) {

            // Admin order billing fields.
            add_filter( 'woocommerce_admin_billing_fields', array( &$this, 'admin_billing_fields' ) );

            // Admin order shipping fields.
            add_filter( 'woocommerce_admin_shipping_fields', array( &$this, 'admin_shipping_fields' ) );

            // Admin Custom order shipping fields.
            add_action( 'woocommerce_admin_order_data_after_shipping_address', array( &$this, 'custom_admin_shipping_fields' ) );

        }

        // Admin Custom order billing fields.
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( &$this, 'custom_admin_billing_fields' ) );

		// Save custom billing & shipping fields from admin.
		add_action( 'save_post', array( &$this,'save_custom_fields' ) );

        // User edit custom fields.
        add_filter( 'woocommerce_customer_meta_fields', array( &$this, 'user_edit_fields' ) );

        // Gateways addons.
        add_filter( 'woocommerce_bcash_args', array( &$this, 'bcash_args' ) );
        add_filter( 'woocommerce_moip_args', array( &$this, 'moip_args' ) );
    }

    /**
     * Load translations.
     */
    public function languages() {
        load_plugin_textdomain( 'wcbcf', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Enqueue plugin scripts.
     *
     * @return void
     */
    public function enqueue_scripts() {

        // Load scripts only in checkout.
        if ( is_checkout() ) {

            // Get plugin settings.
            $settings = get_option( 'wcbcf_settings' );

            // Call jQuery.
            wp_enqueue_script( 'jquery' );

            // Call Mailcheck.
            if ( isset( $settings['mailcheck'] ) ) {
                wp_register_script( 'mailcheck', plugins_url( 'js/jquery.mailcheck.min.js' , __FILE__ ), array(), null, true );
                wp_enqueue_script( 'mailcheck' );
            }

            // Call Maskedinput.
            if ( isset( $settings['maskedinput'] ) ) {
                wp_register_script( 'maskedinput', plugins_url( 'js/jquery.maskedinput.min.js' , __FILE__ ), array(), null, true );
                wp_enqueue_script( 'maskedinput' );
            }

            // Call Adress Autocomplete
            if ( isset( $settings['addresscomplete'] ) ) {
                wp_register_script( 'addresscomplete', plugins_url( 'js/jquery.address.autocomplete.js' , __FILE__ ), array(), null, true );
                wp_enqueue_script( 'addresscomplete' );
            }

            // Call Person Fields fix.
            if ( isset( $settings['person_type'] ) ) {
                wp_register_script( 'fix-person-fields', plugins_url( 'js/jquery.fix.person.fields.js' , __FILE__ ), array(), null, true );
                wp_enqueue_script( 'fix-person-fields' );
            }
        }
    }

    /**
     * Set default settings.
     *
     * @return void.
     */
    public function default_settings() {

        $default = array(
            'person_type'     => '1',
            'birthdate_sex'   => '1',
            'neighborhood'    => '1',
            'cell_phone'      => '1',
            'mailcheck'       => '1',
            'maskedinput'     => '1',
            'addresscomplete' => '1'
        );

        add_option( 'wcbcf_settings', $default );
    }

    /**
     * Add menu.
     *
     * @return void.
     */
    public function menu() {
        add_submenu_page( 'woocommerce', __( 'Brazilian Checkout Fields', 'wcbcf' ), __( 'Brazilian Checkout Fields', 'wcbcf' ), 'manage_options', 'wcbcf', array( $this, 'settings_page' ) );
    }

    /**
     * Built the options page.
     */
    public function settings_page() {
        ?>

            <div class="wrap">
                <div class="icon32" id="icon-options-general"><br /></div>
                <h2><?php _e( 'Brazilian Checkout Fields', 'wcbcf' ); ?></h2>

                <?php settings_errors(); ?>

                <form method="post" action="options.php">

                    <?php
                        settings_fields( 'wcbcf_settings' );
                        do_settings_sections( 'wcbcf_settings' );

                        submit_button();
                    ?>

                </form>

            </div>

        <?php
    }

    /**
     * Plugin settings form fields.
     *
     * @return void.
     */
    public function plugin_settings() {
        $option = 'wcbcf_settings';

        // Create option in wp_options.
        if ( false == get_option( $option ) ) {
            add_option( $option );
        }

        // Set Custom Fields cection.
        add_settings_section(
            'options_section',
            __( 'Checkout Custom Fields:', 'wcbcf' ),
            array( &$this, 'section_options_callback' ),
            $option
        );

        // Person Type option.
        add_settings_field(
            'person_type',
            __( 'Display Person Type:', 'wcbcf' ),
            array( &$this , 'checkbox_element_callback' ),
            $option,
            'options_section',
            array(
                'menu' => $option,
                'id' => 'person_type',
                'label' => __( 'If checked show the Person Type option and CPF, Company and CNJP fields in billing options.', 'wcbcf' )
            )
        );

        // Birthdate and Sex option.
        add_settings_field(
            'birthdate_sex',
            __( 'Display Birthdate and Sex:', 'wcbcf' ),
            array( &$this , 'checkbox_element_callback' ),
            $option,
            'options_section',
            array(
                'menu' => $option,
                'id' => 'birthdate_sex',
                'label' => __( 'If checked show the Birthdate and Sex field in billing options.', 'wcbcf' )
            )
        );

        // Neighborhood option.
        add_settings_field(
            'neighborhood',
            __( 'Display Neighborhood:', 'wcbcf' ),
            array( &$this , 'checkbox_element_callback' ),
            $option,
            'options_section',
            array(
                'menu' => $option,
                'id' => 'neighborhood',
                'label' => __( 'If checked show the Neighborhood field in billing and shipping options.', 'wcbcf' )
            )
        );

        // Cell Phone option.
        add_settings_field(
            'cell_phone',
            __( 'Display Cell Phone:', 'wcbcf' ),
            array( &$this , 'checkbox_element_callback' ),
            $option,
            'options_section',
            array(
                'menu' => $option,
                'id' => 'cell_phone',
                'label' => __( 'If checked show the Cell Phone field in billing options.', 'wcbcf' )
            )
        );

        // Set Custom Fields cection.
        add_settings_section(
            'jquery_section',
            __( 'jQuery Options:', 'wcbcf' ),
            array( &$this, 'section_options_callback' ),
            $option
        );

        // Mail Check option.
        add_settings_field(
            'mailcheck',
            __( 'Enable Mail Check:', 'wcbcf' ),
            array( &$this , 'checkbox_element_callback' ),
            $option,
            'jquery_section',
            array(
                'menu' => $option,
                'id' => 'mailcheck',
                'label' => __( 'If checked informs typos in email to users.', 'wcbcf' )
            )
        );

        // Input Mask option.
        add_settings_field(
            'maskedinput',
            __( 'Enable Input Mask:', 'wcbcf' ),
            array( &$this , 'checkbox_element_callback' ),
            $option,
            'jquery_section',
            array(
                'menu' => $option,
                'id' => 'maskedinput',
                'label' => __( 'If checked create masks fill for in fields of CPF, CNPJ, Birthdate, Phone and Cell Phone.', 'wcbcf' )
            )
        );

        // Address Autocomplete option.
        add_settings_field(
            'addresscomplete',
            __( 'Enable Address Autocomplete:', 'wcbcf' ),
            array( &$this , 'checkbox_element_callback' ),
            $option,
            'jquery_section',
            array(
                'menu' => $option,
                'id' => 'addresscomplete',
                'label' => __( 'If checked automatically complete the address fields based on the zip code.', 'wcbcf' )
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
        $menu = $args['menu'];
        $id = $args['id'];

        $options = get_option( $menu );

        if ( isset( $options[$id] ) ) {
            $current = $options[$id];
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
     * Valid options.
     *
     * @param  array $input options to valid.
     * @return array        validated options.
     */
    public function validate_options( $input ) {
        // Create our array for storing the validated options.
        $output = array();

        // Loop through each of the incoming options.
        foreach ( $input as $key => $value ) {

            // Check to see if the current option has a value. If so, process it.
            if ( isset( $input[$key] ) ) {

                // Strip all HTML and PHP tags and properly handle quoted strings.
                $output[$key] = strip_tags( stripslashes( $input[$key] ) );
            }
        }

        // Return the array processing any additional functions filtered by this action.
        return apply_filters( 'wcbcf_validate_input', $output, $input );
    }

    /**
     * New checkout billing fields
     * @param  array $fields Default fields.
     * @return array         New fields.
     */
    public function checkout_billing_fields( $fields ) {
        // Remove default fields.
        unset( $fields['billing'] );

        // Get plugin settings.
        $settings = get_option( 'wcbcf_settings' );

        // Billing First Name.
        $fields['billing']['billing_first_name'] = array(
            'label'       => __( 'First Name', 'wcbcf' ),
            'placeholder' => _x( 'First Name', 'placeholder', 'wcbcf' ),
            'required'    => true,
            'class'       => array( 'form-row-first' ),
        );

        // Billing Last Name.
        $fields['billing']['billing_last_name'] = array(
            'label'       => __( 'Last Name', 'wcbcf' ),
            'placeholder' => _x( 'Last Name', 'placeholder', 'wcbcf' ),
            'required'    => true,
            'class'       => array( 'form-row-last' ),
            'clear'       => true
        );


        if ( isset( $settings['person_type'] ) ) {

            // Billing Person Type.
            $fields['billing']['billing_persontype'] = array(
                'type'        => 'select',
                'label'       => __( 'Person type', 'wcbcf' ),
                'required'    => true,
                'clear'       => true,
                'options'     => array(
                    '0'               => __( 'Select', 'wcbcf' ),
                    '1'               => __( 'Individuals', 'wcbcf' ),
                    '2'               => __( 'Legal Person', 'wcbcf' )
                )
            );

            // Billing CPF.
            $fields['billing']['billing_cpf'] = array(
                'label'       => __( 'CPF', 'wcbcf' ),
                'placeholder' => _x( 'CPF', 'placeholder', 'wcbcf' ),
                'required'    => false,
                'clear'       => true
            );

            // Billing Company.
            $fields['billing']['billing_company'] = array(
                'label'       => __( 'Company Name', 'wcbcf' ),
                'placeholder' => _x( 'Company Name', 'placeholder', 'wcbcf' ),
                'clear'       => true
            );

            // Billing CNPJ.
            $fields['billing']['billing_cnpj'] = array(
                'label'       => __( 'CNPJ', 'wcbcf' ),
                'placeholder' => _x( 'CNPJ', 'placeholder', 'wcbcf' ),
                'clear'       => true
            );

        } else {
            // Billing Company.
            $fields['billing']['billing_company'] = array(
                'label'       => __( 'Company Name', 'wcbcf' ),
                'placeholder' => _x( 'Company Name', 'placeholder', 'wcbcf' ),
                'clear'       => true
            );
        }

        if ( isset( $settings['birthdate_sex'] ) ) {

            // Billing Birthdate.
            $fields['billing']['billing_birthdate'] = array(
                'label'       => __( 'Birthdate', 'wcbcf' ),
                'placeholder' => _x( 'Birthdate', 'placeholder', 'wcbcf' ),
                'required'    => true,
                'class'       => array( 'form-row-first' ),
                'clear'       => false
            );

            // Billing Sex.
            $fields['billing']['billing_sex'] = array(
                'type'        => 'select',
                'label'       => __( 'Sex', 'wcbcf' ),
                'required'    => true,
                'class'       => array( 'form-row-last' ),
                'clear'       => true,
                'options'     => array(
                    '0'                     => __( 'Select', 'wcbcf' ),
                    __( 'Female', 'wcbcf' ) => __( 'Female', 'wcbcf' ),
                    __( 'Male', 'wcbcf' )   => __( 'Male', 'wcbcf' )
                )
            );

        }

        // Billing Country.
        $fields['billing']['billing_country'] = array(
            'type'        => 'country',
            'label'       => __( 'Country', 'wcbcf' ),
            'placeholder' => _x( 'Country', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-first', 'update_totals_on_change', 'country_select' ),
            'required'    => true,
        );

        // Billing Post Code.
        $fields['billing']['billing_postcode'] = array(
            'label'       => __( 'Post Code', 'wcbcf' ),
            'placeholder' => _x( 'Post Code', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-last', 'update_totals_on_change' ),
            'required'    => true,
            'clear'       => true
        );

        // Billing Anddress 01.
        $fields['billing']['billing_address_1'] = array(
            'label'       => __( 'Address', 'wcbcf' ),
            'placeholder' => _x( 'Address', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-first' ),
            'required'    => true
        );

        // Billing Anddress 02.
        $fields['billing']['billing_address_2'] = array(
            'label'       => __( 'Address line 2', 'wcbcf' ),
            'placeholder' => _x( 'Address line 2 (optional)', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-last' ),
            'label_class' => array( 'hidden' ),
            'clear'    => true,
        );

        if ( isset( $settings['neighborhood'] ) ) {

            // Billing Neighborhood.
            $fields['billing']['billing_neighborhood'] = array(
                'label'       => __( 'Neighborhood', 'wcbcf' ),
                'placeholder' => _x( 'Neighborhood', 'placeholder', 'wcbcf' ),
                'clear'       => true
            );

        }

        // Billing City.
        $fields['billing']['billing_city'] = array(
            'label'       => __( 'City', 'wcbcf' ),
            'placeholder' => _x( 'City', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-first' ),
            'required'    => true
        );

        // Billing State.
        $fields['billing']['billing_state'] = array(
            'type'        => 'state',
            'label'       => __( 'State', 'wcbcf' ),
            'placeholder' => _x( 'State', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-last', 'update_totals_on_change' ),
            'required'    => true,
            'clear'       => true
        );

        if ( isset( $settings['cell_phone'] ) ) {

            // Billing Phone.
            $fields['billing']['billing_phone'] = array(
                'label'       => __( 'Phone', 'wcbcf' ),
                'placeholder' => _x( 'Phone', 'placeholder', 'wcbcf' ),
                'class'       => array( 'form-row-first' ),
                'required'    => true
            );

            // Billing Cell Phone.
            $fields['billing']['billing_cellphone'] = array(
                'label'       => __( 'Cell Phone', 'wcbcf' ),
                'placeholder' => _x( 'Cell Phone (optional)', 'placeholder', 'wcbcf' ),
                'class'       => array( 'form-row-last' ),
                'clear'       => true
            );

            // Billing Email.
            $fields['billing']['billing_email'] = array(
                'label'       => __( 'Email', 'wcbcf' ),
                'placeholder' => _x( 'Email', 'placeholder', 'wcbcf' ),
                'required'    => true,
                'clear'       => true
            );

        } else {

            // Billing Phone.
            $fields['billing']['billing_phone'] = array(
                'label'       => __( 'Phone', 'wcbcf' ),
                'placeholder' => _x( 'Phone', 'placeholder', 'wcbcf' ),
                'required'    => true,
                'clear'       => true
            );


            // Billing Email.
            $fields['billing']['billing_email'] = array(
                'label'       => __( 'Email', 'wcbcf' ),
                'placeholder' => _x( 'Email', 'placeholder', 'wcbcf' ),
                'required'    => true,
                'clear'       => true
            );

        }

        return apply_filters( 'wcbcf_billing_fields', $fields );
    }

    /**
     * New checkout shipping fields
     * @param  array $fields Default fields.
     * @return array         New fields.
     */
    public function checkout_shipping_fields( $fields ) {
        // Remove default fields.
        unset( $fields['shipping'] );

        // Get plugin settings.
        $settings = get_option( 'wcbcf_settings' );

        // Shipping First Name.
        $fields['shipping']['shipping_first_name'] = array(
            'label'       => __( 'First Name', 'wcbcf' ),
            'placeholder' => _x( 'First Name', 'placeholder', 'wcbcf' ),
            'required'    => true,
            'class'       => array( 'form-row-first' ),
        );

        // Shipping Last Name.
        $fields['shipping']['shipping_last_name'] = array(
            'label'       => __( 'Last Name', 'wcbcf' ),
            'placeholder' => _x( 'Last Name', 'placeholder', 'wcbcf' ),
            'required'    => true,
            'class'       => array( 'form-row-last' ),
            'clear'       => true
        );

        // Shipping Company.
        $fields['shipping']['shipping_company'] = array(
            'label'       => __( 'Company Name', 'wcbcf' ),
            'placeholder' => _x( 'Company Name (optional)', 'placeholder', 'wcbcf' ),
            'clear'       => true
        );

        // Shipping Country.
        $fields['shipping']['shipping_country'] = array(
            'type'        => 'country',
            'label'       => __( 'Country', 'wcbcf' ),
            'placeholder' => _x( 'Country', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-first', 'update_totals_on_change', 'country_select' ),
            'required'    => true,
        );

        // Shipping Post Code.
        $fields['shipping']['shipping_postcode'] = array(
            'label'       => __( 'Post Code', 'wcbcf' ),
            'placeholder' => _x( 'Post Code', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-last', 'update_totals_on_change' ),
            'required'    => true,
            'clear'       => true
        );

        // Shipping Anddress 01.
        $fields['shipping']['shipping_address_1'] = array(
            'label'       => __( 'Address', 'wcbcf' ),
            'placeholder' => _x( 'Address', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-first' ),
            'required'    => true
        );

        // Shipping Anddress 02.
        $fields['shipping']['shipping_address_2'] = array(
            'label'       => __( 'Address line 2', 'wcbcf' ),
            'placeholder' => _x( 'Address line 2  (optional)', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-last' ),
            'label_class' => array( 'hidden' ),
            'clear'    => true,
        );

        if ( isset( $settings['neighborhood'] ) ) {

            // Shipping Neighborhood.
            $fields['shipping']['shipping_neighborhood'] = array(
                'label'       => __( 'Neighborhood', 'wcbcf' ),
                'placeholder' => _x( 'Neighborhood (optional)', 'placeholder', 'wcbcf' ),
                'clear'       => true
            );

        }

        // Shipping City.
        $fields['shipping']['shipping_city'] = array(
            'label'       => __( 'City', 'wcbcf' ),
            'placeholder' => _x( 'City', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-first' ),
            'required'    => true
        );

        // Shipping State.
        $fields['shipping']['shipping_state'] = array(
            'type'        => 'state',
            'label'       => __( 'State', 'wcbcf' ),
            'placeholder' => _x( 'State', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-last', 'update_totals_on_change' ),
            'required'    => true,
            'clear'       => true
        );

        return apply_filters( 'wcbcf_shipping_fields', $fields );
    }

    /**
     * Valid checkout fields.
     *
     * @return void
     */
    public function valid_checkout_fields() {
        global $woocommerce;

        // Get plugin settings.
        $settings = get_option( 'wcbcf_settings' );

        if ( isset( $settings['person_type'] ) ) {

            // Check CEP.
            if ( $_POST['billing_persontype'] == 1 && !$_POST['billing_cpf'] ) {
                $woocommerce->add_error( __( '<strong>CPF</strong> is a required field.', 'wcbcf' ) );
            }

            // Check Company.
            if ( $_POST['billing_persontype'] == 2 && !$_POST['billing_company'] ) {
                $woocommerce->add_error( __( '<strong>Company</strong> is a required field.', 'wcbcf' ) );
            }

            // Check CPNJ.
            if ( $_POST['billing_persontype'] == 2 && !$_POST['billing_cnpj'] ) {
                $woocommerce->add_error( __( '<strong>CNPJ</strong> is a required field.', 'wcbcf' ) );
            }

        }
    }

    /**
     * Load order custom data.
     *
     * @param  array $data Default WC_Order data.
     * @return array       Custom WC_Order data.
     */
    public function load_order_data( $data ) {

        // Billing
        $data['billing_persontype']    = '';
        $data['billing_cpf']           = '';
        $data['billing_cnpj']          = '';
        $data['billing_birthdate']     = '';
        $data['billing_sex']           = '';
        $data['billing_neighborhood']  = '';
        $data['billing_cellphone']     = '';

        // Shipping
        $data['shipping_neighborhood'] = '';

        return $data;
    }

    /**
     * Custom billing admin edit fields.
     *
     * @param  array $fields Default WC_Order data.
     * @return array         Custom WC_Order data.
     */
    public function admin_billing_fields( $fields ) {

        $fields['neighborhood'] = array(
            'label' => __( 'Neighborhood', 'wcbcf' ),
            'show'  => false
        );

        return $fields;
    }

    /**
     * Custom shipping admin edit fields.
     *
     * @param  array $fields Default WC_Order data.
     * @return array         Custom WC_Order data.
     */
    public function admin_shipping_fields( $fields ) {

        $fields['neighborhood'] = array(
            'label' => __( 'Neighborhood', 'wcbcf' ),
            'show'  => false
        );

        return $fields;
    }

    /**
     * Custom billing admin fields.
     *
     * @return string Custom information.
     */
    public function custom_admin_billing_fields() {
        global $post;

        // Get plugin settings.
        $settings = get_option( 'wcbcf_settings' );

        $html = '<div class="wcbcf-custom-fields">';
            $html .= '<h2>' . __( 'Extra information', 'wcbcf' ) . ':</h2>';
            $html .= '<p>';

                if ( isset( $settings['person_type'] ) ) {

                    // Person type information.
                    $person_type = get_post_meta( $post->ID, '_billing_persontype', true );
                    if ( $person_type == 1 ) {
                        $html .= '<strong>' . __( 'CPF', 'wcbcf' ) . ': </strong>' . get_post_meta( $post->ID, '_billing_cpf', true ) . '<br />';
                    }
                    if ( $person_type == 2 ) {
                        $html .= '<strong>' . __( 'CNPJ', 'wcbcf' ) . ': </strong>' . get_post_meta( $post->ID, '_billing_cnpj', true ) . '<br />';
                        $html .= '<strong>' . __( 'Company Name', 'wcbcf' ) . ': </strong>' . get_post_meta( $post->ID, '_billing_company', true ) . '<br />';
                    }

                }

                if ( isset( $settings['birthdate_sex'] ) ) {

                    // Birthdate information.
                    $html .= '<strong>' . __( 'Birthdate', 'wcbcf' ) . ': </strong>' . get_post_meta( $post->ID, '_billing_birthdate', true ) . '<br />';

                    // Sex Information.
                    $html .= '<strong>' . __( 'Sex', 'wcbcf' ) . ': </strong>' . get_post_meta( $post->ID, '_billing_sex', true ) . '<br />';
                }

                if ( isset( $settings['neighborhood'] ) ) {

                    // Neighborhood Information.
                    $html .= '<strong>' . __( 'Neighborhood', 'wcbcf' ) . ': </strong>' . get_post_meta( $post->ID, '_billing_neighborhood', true ) . '<br />';

                }

                if ( isset( $settings['cell_phone'] ) ) {

                    // Cell Phone Information.
                    $html .= '<strong>' . __( 'Cell Phone', 'wcbcf' ) . ': </strong>' . get_post_meta( $post->ID, '_billing_cellphone', true ) . '<br />';

                }

            $html .= '</p>';
        $html .= '</div>';

        echo $html;
    }

    /**
     * Custom billing admin fields.
     *
     * @return string Custom information.
     */
    public function custom_admin_shipping_fields() {
        global $post;

        $html = '<div class="wcbcf-custom-fields">';
            $html .= '<h2>' . __( 'Extra information', 'wcbcf' ) . ':</h2>';
            $html .= '<p>';

                // Neighborhood Information.
                $html .= '<strong>' . __( 'Neighborhood', 'wcbcf' ) . ': </strong>' . get_post_meta( $post->ID, '_billing_neighborhood', true ) . '<br />';

            $html .= '</p>';
        $html .= '</div>';

        echo $html;
    }

    /**
     * Save custom fields.
     *
     * @param  array $post_id Post ID.
     * @return void
     */

	public function save_custom_fields($post_id) {
		global $post_type;
		if( $post_type == 'shop_order' ) {
			update_post_meta( $post_id, '_billing_neighborhood', stripslashes( $_POST['_billing_neighborhood'] ));
			update_post_meta( $post_id, '_shipping_neighborhood', stripslashes( $_POST['_shipping_neighborhood'] ));
		}
		return;
	}

    /**
     * Custom user edit fields.
     *
     * @param  array $fields Default fields.
     * @return array         Custom fields.
     */
    public function user_edit_fields( $fields ) {
        unset( $fields );

        // Get plugin settings.
        $settings = get_option( 'wcbcf_settings' );

        // Billing fields.
        $fields['billing']['title'] = __( 'Customer Billing Address', 'wcbcf' );
        $fields['billing']['fields']['billing_first_name'] = array(
            'label' => __( 'First name', 'wcbcf' ),
            'description' => ''
        );
        $fields['billing']['fields']['billing_last_name'] = array(
            'label' => __( 'Last name', 'wcbcf' ),
            'description' => ''
        );

        if ( isset( $settings['person_type'] ) ) {

            $fields['billing']['fields']['billing_cpf'] = array(
                'label' => __( 'CPF', 'wcbcf' ),
                'description' => ''
            );
            $fields['billing']['fields']['billing_cnpj'] = array(
                'label' => __( 'CNPJ', 'wcbcf' ),
                'description' => ''
            );
            $fields['billing']['fields']['billing_company'] = array(
                'label' => __( 'Company', 'wcbcf' ),
                'description' => ''
            );

        }

        if ( isset( $settings['birthdate_sex'] ) ) {

            $fields['billing']['fields']['billing_birthdate'] = array(
                'label' => __( 'Birthdate', 'wcbcf' ),
                'description' => ''
            );
            $fields['billing']['fields']['billing_sex'] = array(
                'label' => __( 'Sex', 'wcbcf' ),
                'description' => ''
            );

        }

        $fields['billing']['fields']['billing_country'] = array(
            'label' => __( 'Country', 'wcbcf' ),
            'description' => __( '2 letter Country code', 'wcbcf' )
        );
        $fields['billing']['fields']['billing_postcode'] = array(
            'label' => __( 'Postcode', 'wcbcf' ),
            'description' => ''
        );
        $fields['billing']['fields']['billing_address_1'] = array(
            'label' => __( 'Address 1', 'wcbcf' ),
            'description' => ''
        );
        $fields['billing']['fields']['billing_address_2'] = array(
            'label' => __( 'Address 2', 'wcbcf' ),
            'description' => ''
        );

        if ( isset( $settings['neighborhood'] ) ) {

            $fields['billing']['fields']['billing_neighborhood'] = array(
                'label' => __( 'Neighborhood', 'wcbcf' ),
                'description' => ''
            );

        }

        $fields['billing']['fields']['billing_city'] = array(
            'label' => __( 'City', 'wcbcf' ),
            'description' => ''
        );
        $fields['billing']['fields']['billing_state'] = array(
            'label' => __( 'State/County', 'wcbcf' ),
            'description' => __( 'Country or state code', 'wcbcf' )
        );
        $fields['billing']['fields']['billing_phone'] = array(
            'label' => __( 'Telephone', 'wcbcf' ),
            'description' => ''
        );

        if ( isset( $settings['cell_phone'] ) ) {

            $fields['billing']['fields']['billing_cellphone'] = array(
                'label' => __( 'Cell Phone', 'wcbcf' ),
                'description' => ''
            );

        }

        $fields['billing']['fields']['billing_email'] = array(
            'label' => __( 'Email', 'wcbcf' ),
            'description' => ''
        );

        // Shipping fields.
        $fields['shipping']['title'] = __( 'Customer Shipping Address', 'wcbcf' );
        $fields['shipping']['fields']['shipping_first_name'] = array(
            'label' => __( 'First name', 'wcbcf' ),
            'description' => ''
        );
        $fields['shipping']['fields']['shipping_last_name'] = array(
            'label' => __( 'Last name', 'wcbcf' ),
            'description' => ''
        );
        $fields['shipping']['fields']['shipping_company'] = array(
            'label' => __( 'Company', 'wcbcf' ),
            'description' => ''
        );
        $fields['shipping']['fields']['shipping_country'] = array(
            'label' => __( 'Country', 'wcbcf' ),
            'description' => __( '2 letter Country code', 'wcbcf' )
        );
        $fields['shipping']['fields']['shipping_postcode'] = array(
            'label' => __( 'Postcode', 'wcbcf' ),
            'description' => ''
        );
        $fields['shipping']['fields']['shipping_address_1'] = array(
            'label' => __( 'Address 1', 'wcbcf' ),
            'description' => ''
        );
        $fields['shipping']['fields']['shipping_address_2'] = array(
            'label' => __( 'Address 2', 'wcbcf' ),
            'description' => ''
        );

        if ( isset( $settings['neighborhood'] ) ) {

            $fields['shipping']['fields']['shipping_neighborhood'] = array(
                'label' => __( 'Neighborhood', 'wcbcf' ),
                'description' => ''
            );

        }

        $fields['shipping']['fields']['shipping_city'] = array(
            'label' => __( 'City', 'wcbcf' ),
            'description' => ''
        );
        $fields['shipping']['fields']['shipping_state'] = array(
            'label' => __( 'State/County', 'wcbcf' ),
            'description' => __( 'Country or state code', 'wcbcf' )
        );

        $new_fields = apply_filters( 'wcbcf_customer_meta_fields', $fields );

        return $new_fields;
    }

    /**
     * Custom MoIP arguments.
     *
     * @param  array $args MoIP default arguments.
     * @return array       New arguments.
     */
    public function moip_args( $args ) {

        $settings = get_option( 'wcbcf_settings' );

        if ( isset( $settings['neighborhood'] ) ) {

            $order_id = esc_attr( $_REQUEST['order'] );
            $order = new WC_Order( $order_id );

            $args['pagador_bairro'] = $order->billing_neighborhood;

        }

        return $args;
    }

    /**
     * Custom Bcash arguments.
     *
     * @param  array $args Bcash default arguments.
     * @return array       New arguments.
     */
    public function bcash_args( $args ) {

        $settings = get_option( 'wcbcf_settings' );

        if ( isset( $settings['person_type'] ) ) {

            $order_id = esc_attr( $_REQUEST['order'] );
            $order = new WC_Order( $order_id );

            if ( 1 == $order->billing_persontype ) {
                $cpf = str_replace( array( '-', '.' ), '', $order->billing_cpf );
                $args['cpf'] = $cpf;
            }

            if ( 2 == $order->billing_persontype ) {
                $cnpj = str_replace( array( '-', '.' ), '', $order->billing_cnpj );
                $args['cliente_cnpj'] = $cnpj;
                $args['cliente_razao_social'] = $order->billing_company;
            }
        }

        return $args;
    }

}

/**
 * WooCommerce fallback notice.
 *
 * @return string Fallack notice.
 */
function wcbcf_fallback_notice() {
    $message = '<div class="error">';
        $message .= '<p>' . sprintf( __( 'WooCommerce Brazilian Checkout Fields depends on <a href="%s">WooCommerce</a> to work!' , 'wcbcf' ), 'http://wordpress.org/extend/plugins/woocommerce/' ) . '</p>';
    $message .= '</div>';

    echo $message;
}

/**
 * Check if WooCommerce is active.
 *
 * Ref: http://wcdocs.woothemes.com/codex/extending/create-a-plugin/.
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    $wcBrazilianCheckoutFields = new WC_BrazilianCheckoutFields();
} else {
    add_action( 'admin_notices', 'wcbcf_fallback_notice' );
}
