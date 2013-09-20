<?php
/**
 * Plugin Name: WooCommerce Extra Checkout Fields for Brazil
 * Plugin URI: https://github.com/claudiosmweb/woocommerce-extra-checkout-fields-for-brazil
 * Description: Adiciona novos campos para Pessoa Física ou Jurídica, Data de Nascimento, Sexo, Número, Bairro e Celular. Além de máscaras em campos, aviso de e-mail incorreto e auto preenchimento dos campos de endereço pelo CEP.
 * Author: claudiosanches
 * Author URI: http://claudiosmweb.com/
 * Version: 2.7.0
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

        // New checkout fields.
        add_filter( 'woocommerce_billing_fields', array( &$this, 'checkout_billing_fields' ) );
        add_filter( 'woocommerce_shipping_fields', array( &$this, 'checkout_shipping_fields' ) );

        // Valid checkout fields.
        add_action( 'woocommerce_checkout_process', array( &$this, 'valid_checkout_fields' ) );

        // Load scripts in front-end.
        add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ), 999 );

        // Add menu.
        add_action( 'admin_menu', array( &$this, 'menu' ) );

        // Init plugin options form.
        add_action( 'admin_init', array( &$this, 'plugin_settings' ) );

        // Load custom order data.
        add_filter( 'woocommerce_load_order_data', array( &$this, 'load_order_data' ) );

        // Custom shop_order details.
        add_filter( 'woocommerce_admin_billing_fields', array( &$this, 'admin_billing_fields' ) );
        add_filter( 'woocommerce_admin_shipping_fields', array( &$this, 'admin_shipping_fields' ) );
        add_filter( 'woocommerce_found_customer_details', array( &$this, 'custom_customer_details_ajax' ) );
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( &$this, 'custom_admin_billing_fields' ) );
        add_action( 'woocommerce_admin_order_data_after_shipping_address', array( &$this, 'custom_admin_shipping_fields' ) );
        add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );
        add_action( 'save_post', array( &$this, 'save_custom_fields' ) );

        // Custom address format.
        if ( version_compare( WOOCOMMERCE_VERSION, '2.0.6', '>=' ) ) {
            add_filter( 'woocommerce_localisation_address_formats', array( &$this, 'localisation_address_formats' ) );
            add_filter( 'woocommerce_customer_meta_fields', array( &$this, 'user_edit_fields' ) );
            add_filter( 'woocommerce_formatted_address_replacements', array( &$this, 'formatted_address_replacements' ), 1, 2 );
            add_filter( 'woocommerce_order_formatted_billing_address', array( &$this, 'order_formatted_billing_address' ), 1, 2 );
            add_filter( 'woocommerce_order_formatted_shipping_address', array( &$this, 'order_formatted_shipping_address' ), 1, 2 );
            add_filter( 'woocommerce_user_column_billing_address', array( &$this, 'user_column_billing_address' ), 1, 2 );
            add_filter( 'woocommerce_user_column_shipping_address', array( &$this, 'user_column_shipping_address' ), 1, 2 );
            add_filter( 'woocommerce_my_account_my_address_formatted_address', array( &$this, 'my_account_my_address_formatted_address' ), 1, 3 );
        }

        // Gateways addons.
        add_filter( 'woocommerce_bcash_args', array( &$this, 'bcash_args' ), 1, 2 );
        add_filter( 'woocommerce_moip_args', array( &$this, 'moip_args' ), 1, 2 );
        add_filter( 'woocommerce_moip_holder_data', array( &$this, 'moip_transparent_checkout_args' ), 1, 2 );
        add_filter( 'woocommerce_pagseguro_payment_xml', array( &$this, 'pagseguro_args' ), 1, 2 );

        // Actions links.
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( &$this, 'action_links' ) );
    }

    /**
     * Enqueue plugin scripts.
     *
     * @return void
     */
    public function enqueue_scripts() {

        // Load scripts only in checkout.
        if ( is_checkout() || is_page( woocommerce_get_page_id( 'edit_address' ) ) ) {

            // Get plugin settings.
            $settings = get_option( 'wcbcf_settings' );

            // Call jQuery.
            wp_enqueue_script( 'jquery' );

            // Fix checkout fields.
            wp_enqueue_script( 'fix-checkout-fields', plugins_url( 'js/jquery.fix.checkout.fields.js', __FILE__ ), array(), null, true );
            wp_localize_script(
                'fix-checkout-fields',
                'wcbcf_fix_params',
                array(
                    'state'  => __( 'State', 'wcbcf' ),
                    'required'  => __( 'required', 'wcbcf' )
                )
            );

            // Call Mailcheck.
            if ( isset( $settings['mailcheck'] ) )
                wp_enqueue_script( 'mailcheck', plugins_url( 'js/jquery.mailcheck.min.js', __FILE__ ), array(), null, true );

            // Call Maskedinput.
            if ( isset( $settings['maskedinput'] ) )
                wp_enqueue_script( 'maskedinput', plugins_url( 'js/jquery.maskedinput.min.js', __FILE__ ), array(), null, true );

            // Call Adress Autocomplete
            if ( isset( $settings['addresscomplete'] ) )
                wp_enqueue_script( 'addresscomplete', plugins_url( 'js/jquery.address.autocomplete.js', __FILE__ ), array(), null, true );

            // Call Person Fields fix.
            if ( isset( $settings['person_type'] ) )
                wp_enqueue_script( 'fix-person-fields', plugins_url( 'js/jquery.fix.person.fields.js', __FILE__ ), array(), null, true );
        }
    }

    /**
     * Admin Enqueue scripts.
     *
     * @return void
     */
    public function admin_enqueue_scripts() {
        global $post_type;

        if ( 'shop_order' == $post_type ) {

            // Styles.
            wp_enqueue_style( 'wcbcf-admin-styles', plugins_url( 'css/admin.css', __FILE__ ), array(), null );

            // Shop order.
            wp_enqueue_script( 'wcbcf-shop-order', plugins_url( 'js/jquery.fix.person.fields.admin.js', __FILE__ ), array( 'jquery' ), null, true );

            // Write panels.
            wp_enqueue_script( 'wcbcf-write-panels', plugins_url( 'js/jquery.write-panels.js', __FILE__ ), array( 'jquery' ), null, true );

            // Localize strings.
            wp_localize_script(
                'wcbcf-write-panels',
                'wcbcf_writepanel_params',
                array(
                    'load_message' => __( 'Load the customer extras data?', 'wcbcf' ),
                    'copy_message' => __( 'Also copy the data of number and neighborhood?', 'wcbcf' )
                )
            );
        }

        if ( isset( $_GET['page'] ) && 'wcbcf' == $_GET['page'] )
            wp_enqueue_script( 'wcbcf-admin', plugins_url( 'js/jquery.admin.js', __FILE__ ), array( 'jquery' ), null, true );
    }

    /**
     * Adds custom settings url in plugins page.
     *
     * @param  array $links Default links.
     *
     * @return array        Default links and settings link.
     */
    public function action_links( $links ) {

        $settings = array(
            'settings' => sprintf(
                '<a href="%s">%s</a>',
                admin_url( 'admin.php?page=wcbcf' ),
                __( 'Settings', 'wcbcf' )
            )
        );

        return array_merge( $settings, $links );
    }

    /**
     * Set default settings.
     *
     * @return void.
     */
    public function default_settings() {

        $default = array(
            'person_type'     => 1,
            'birthdate_sex'   => 1,
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
     * Add menu.
     *
     * @return void.
     */
    public function menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Brazilian Checkout Fields', 'wcbcf' ),
            __( 'Brazilian Checkout Fields', 'wcbcf' ),
            'manage_options',
            'wcbcf',
            array( $this, 'settings_page' )
        );
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
        if ( false == get_option( $option ) )
            $this->default_settings();

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
            array( &$this, 'checkbox_element_callback' ),
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
            array( &$this, 'checkbox_element_callback' ),
            $option,
            'options_section',
            array(
                'menu' => $option,
                'id' => 'birthdate_sex',
                'label' => __( 'If checked show the Birthdate and Sex field in billing options.', 'wcbcf' )
            )
        );

        // Cell Phone option.
        add_settings_field(
            'cell_phone',
            __( 'Display Cell Phone:', 'wcbcf' ),
            array( &$this, 'checkbox_element_callback' ),
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
            array( &$this, 'checkbox_element_callback' ),
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
            array( &$this, 'checkbox_element_callback' ),
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
            array( &$this, 'checkbox_element_callback' ),
            $option,
            'jquery_section',
            array(
                'menu' => $option,
                'id' => 'addresscomplete',
                'label' => __( 'If checked automatically complete the address fields based on the zip code.', 'wcbcf' )
            )
        );

        // Set Custom Fields cection.
        add_settings_section(
            'validation_section',
            __( 'Validation:', 'wcbcf' ),
            array( &$this, 'section_options_callback' ),
            $option
        );

        // Validate CPF option.
        add_settings_field(
            'validate_cpf',
            __( 'Validate CPF:', 'wcbcf' ),
            array( &$this, 'checkbox_element_callback' ),
            $option,
            'validation_section',
            array(
                'menu' => $option,
                'id' => 'validate_cpf',
                'label' => __( 'Checks if the CPF is valid.', 'wcbcf' )
            )
        );

        // Validate CPF option.
        add_settings_field(
            'validate_cnpj',
            __( 'Validate CNPJ:', 'wcbcf' ),
            array( &$this, 'checkbox_element_callback' ),
            $option,
            'validation_section',
            array(
                'menu' => $option,
                'id' => 'validate_cnpj',
                'label' => __( 'Checks if the CNPJ is valid.', 'wcbcf' )
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

        if ( isset( $options[$id] ) )
            $current = $options[$id];
        else
            $current = isset( $args['default'] ) ? $args['default'] : '0';

        $html = '<input type="checkbox" id="' . $id . '" name="' . $menu . '[' . $id . ']" value="1"' . checked( 1, $current, false ) . '/>';

        if ( isset( $args['label'] ) )
            $html .= ' <label for="' . $id . '">' . $args['label'] . '</label>';

        if ( isset( $args['description'] ) )
            $html .= '<p class="description">' . $args['description'] . '</p>';

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
        // Create our array for storing the validated options.
        $output = array();

        // Loop through each of the incoming options.
        foreach ( $input as $key => $value ) {
            // Check to see if the current option has a value. If so, process it.
            if ( isset( $input[ $key ] ) )
                $output[ $key ] = woocommerce_clean( $input[ $key ] );

        }

        // Return the array processing any additional functions filtered by this action.
        return apply_filters( 'wcbcf_validate_input', $output, $input );
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
            'label'       => __( 'First Name', 'wcbcf' ),
            'placeholder' => _x( 'First Name', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-first' ),
            'required'    => true
        );

        // Billing Last Name.
        $new_fields['billing_last_name'] = array(
            'label'       => __( 'Last Name', 'wcbcf' ),
            'placeholder' => _x( 'Last Name', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-last' ),
            'clear'       => true,
            'required'    => true
        );

        if ( isset( $settings['person_type'] ) ) {

            // Billing Person Type.
            $new_fields['billing_persontype'] = array(
                'type'     => 'select',
                'label'    => __( 'Person type', 'wcbcf' ),
                'class'    => array( 'form-row-wide' ),
                'required' => true,
                'options'  => array(
                    '0' => __( 'Select', 'wcbcf' ),
                    '1' => __( 'Individuals', 'wcbcf' ),
                    '2' => __( 'Legal Person', 'wcbcf' )
                )
            );

            // Billing CPF.
            $new_fields['billing_cpf'] = array(
                'label'       => __( 'CPF', 'wcbcf' ),
                'placeholder' => _x( 'CPF', 'placeholder', 'wcbcf' ),
                'class'       => array( 'form-row-wide' ),
                'required'    => false
            );

            // Billing Company.
            $new_fields['billing_company'] = array(
                'label'       => __( 'Company Name', 'wcbcf' ),
                'placeholder' => _x( 'Company Name', 'placeholder', 'wcbcf' ),
                'class'       => array( 'form-row-wide' ),
                'required'    => false
            );

            // Billing CNPJ.
            $new_fields['billing_cnpj'] = array(
                'label'       => __( 'CNPJ', 'wcbcf' ),
                'placeholder' => _x( 'CNPJ', 'placeholder', 'wcbcf' ),
                'class'       => array( 'form-row-wide' ),
                'required'    => false
            );

        } else {
            // Billing Company.
            $new_fields['billing_company'] = array(
                'label'       => __( 'Company', 'wcbcf' ),
                'placeholder' => _x( 'Company', 'placeholder', 'wcbcf' ),
                'class'       => array( 'form-row-wide' ),
                'required'    => false
            );
        }

        if ( isset( $settings['birthdate_sex'] ) ) {

            // Billing Birthdate.
            $new_fields['billing_birthdate'] = array(
                'label'       => __( 'Birthdate', 'wcbcf' ),
                'placeholder' => _x( 'Birthdate', 'placeholder', 'wcbcf' ),
                'class'       => array( 'form-row-first' ),
                'clear'       => false,
                'required'    => true
            );

            // Billing Sex.
            $new_fields['billing_sex'] = array(
                'type'        => 'select',
                'label'       => __( 'Sex', 'wcbcf' ),
                'class'       => array( 'form-row-last' ),
                'clear'       => true,
                'required'    => true,
                'options'     => array(
                    '0'                     => __( 'Select', 'wcbcf' ),
                    __( 'Female', 'wcbcf' ) => __( 'Female', 'wcbcf' ),
                    __( 'Male', 'wcbcf' )   => __( 'Male', 'wcbcf' )
                )
            );

        }

        // Billing Country.
        $new_fields['billing_country'] = array(
            'type'        => 'country',
            'label'       => __( 'Country', 'wcbcf' ),
            'placeholder' => _x( 'Country', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-first', 'update_totals_on_change', 'address-field' ),
            'clear'       => false,
            'required'    => true,
        );

        // Billing Post Code.
        $new_fields['billing_postcode'] = array(
            'label'       => __( 'Post Code', 'wcbcf' ),
            'placeholder' => _x( 'Post Code', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-last', 'update_totals_on_change', 'address-field' ),
            'clear'       => true,
            'required'    => true
        );

        // Billing Anddress 01.
        $new_fields['billing_address_1'] = array(
            'label'       => __( 'Address', 'wcbcf' ),
            'placeholder' => _x( 'Address', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-first', 'address-field' ),
            'required'    => true
        );

        // Billing Number.
        $new_fields['billing_number'] = array(
            'label'       => __( 'Number', 'wcbcf' ),
            'placeholder' => _x( 'Number', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-last', 'address-field' ),
            'clear'       => true,
            'required'    => true
        );

        // Billing Anddress 02.
        $new_fields['billing_address_2'] = array(
            'label'       => __( 'Address line 2', 'wcbcf' ),
            'placeholder' => _x( 'Address line 2', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-first', 'address-field' )
        );

        // Billing Neighborhood.
        $new_fields['billing_neighborhood'] = array(
            'label'       => __( 'Neighborhood', 'wcbcf' ),
            'placeholder' => _x( 'Neighborhood', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-last', 'address-field' ),
            'clear'       => true,
        );

        // Billing City.
        $new_fields['billing_city'] = array(
            'label'       => __( 'City', 'wcbcf' ),
            'placeholder' => _x( 'City', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-first', 'address-field' ),
            'required'    => true
        );

        // Billing State.
        $new_fields['billing_state'] = array(
            'type'        => 'state',
            'label'       => __( 'State', 'wcbcf' ),
            'placeholder' => _x( 'State', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-last', 'address-field' ),
            'clear'       => true,
            'required'    => true
        );

        if ( isset( $settings['cell_phone'] ) ) {

            // Billing Phone.
            $new_fields['billing_phone'] = array(
                'label'       => __( 'Phone', 'wcbcf' ),
                'placeholder' => _x( 'Phone', 'placeholder', 'wcbcf' ),
                'class'       => array( 'form-row-first' ),
                'required'    => true
            );

            // Billing Cell Phone.
            $new_fields['billing_cellphone'] = array(
                'label'       => __( 'Cell Phone', 'wcbcf' ),
                'placeholder' => _x( 'Cell Phone', 'placeholder', 'wcbcf' ),
                'class'       => array( 'form-row-last' ),
                'clear'       => true
            );

            // Billing Email.
            $new_fields['billing_email'] = array(
                'label'       => __( 'Email', 'wcbcf' ),
                'placeholder' => _x( 'Email', 'placeholder', 'wcbcf' ),
                'class'       => array( 'form-row-wide' ),
                'validate'    => array( 'email' ),
                'clear'       => true,
                'required'    => true
            );

        } else {

            // Billing Phone.
            $new_fields['billing_phone'] = array(
                'label'       => __( 'Phone', 'wcbcf' ),
                'placeholder' => _x( 'Phone', 'placeholder', 'wcbcf' ),
                'class'       => array( 'form-row-wide' ),
                'required'    => true
            );

            // Billing Email.
            $new_fields['billing_email'] = array(
                'label'       => __( 'Email', 'wcbcf' ),
                'placeholder' => _x( 'Email', 'placeholder', 'wcbcf' ),
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
            'label'       => __( 'First Name', 'wcbcf' ),
            'placeholder' => _x( 'First Name', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-first' ),
            'required'    => true
        );

        // Shipping Last Name.
        $new_fields['shipping_last_name'] = array(
            'label'       => __( 'Last Name', 'wcbcf' ),
            'placeholder' => _x( 'Last Name', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-last' ),
            'clear'       => true,
            'required'    => true
        );

        // Shipping Company.
        $new_fields['shipping_company'] = array(
            'label'       => __( 'Company', 'wcbcf' ),
            'placeholder' => _x( 'Company', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-wide' )
        );

        // Shipping Country.
        $new_fields['shipping_country'] = array(
            'type'        => 'country',
            'label'       => __( 'Country', 'wcbcf' ),
            'placeholder' => _x( 'Country', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-first', 'update_totals_on_change', 'address-field' ),
            'required'    => true
        );

        // Shipping Post Code.
        $new_fields['shipping_postcode'] = array(
            'label'       => __( 'Post Code', 'wcbcf' ),
            'placeholder' => _x( 'Post Code', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-last', 'update_totals_on_change', 'address-field' ),
            'clear'       => true,
            'required'    => true
        );

        // Shipping Anddress 01.
        $new_fields['shipping_address_1'] = array(
            'label'       => __( 'Address', 'wcbcf' ),
            'placeholder' => _x( 'Address', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-first', 'address-field' ),
            'required'    => true
        );

        // Shipping Number.
        $new_fields['shipping_number'] = array(
            'label'       => __( 'Number', 'wcbcf' ),
            'placeholder' => _x( 'Number', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-last', 'address-field' ),
            'clear'       => true,
            'required'    => true
        );

        // Shipping Anddress 02.
        $new_fields['shipping_address_2'] = array(
            'label'       => __( 'Address line 2', 'wcbcf' ),
            'placeholder' => _x( 'Address line 2', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-first', 'address-field' )
        );

        // Shipping Neighborhood.
        $new_fields['shipping_neighborhood'] = array(
            'label'       => __( 'Neighborhood', 'wcbcf' ),
            'placeholder' => _x( 'Neighborhood', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-last', 'address-field' ),
            'clear'       => true
        );

        // Shipping City.
        $new_fields['shipping_city'] = array(
            'label'       => __( 'City', 'wcbcf' ),
            'placeholder' => _x( 'City', 'placeholder', 'wcbcf' ),
            'class'       => array( 'form-row-first', 'address-field' ),
            'required'    => true
        );

        // Shipping State.
        $new_fields['shipping_state'] = array(
            'type'        => 'state',
            'label'       => __( 'State', 'wcbcf' ),
            'placeholder' => _x( 'State', 'placeholder', 'wcbcf' ),
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
     * @param string $message Error message.
     *
     * @return string         Displays the error message.
     */
    protected function add_error( $message ) {
        global $woocommerce;

        if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) )
            wc_add_error( $message );
        else
            $woocommerce->add_error( $message );
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
                if ( empty( $_POST['billing_cpf'] ) )
                    $this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'CPF', 'wcbcf' ), __( 'is a required field', 'wcbcf' ) ) );

                if ( isset( $settings['validate_cpf'] ) && ! empty( $_POST['billing_cpf'] ) && ! $this->is_cpf( $_POST['billing_cpf'] ) )
                    $this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'CPF', 'wcbcf' ), __( 'is not valid', 'wcbcf' ) ) );
            }

            // Check Company and CPNJ.
            if ( 2 == $_POST['billing_persontype'] ) {
                if ( empty( $_POST['billing_company'] ) )
                    $this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'Company', 'wcbcf' ), __( 'is a required field', 'wcbcf' ) ) );

                if ( empty( $_POST['billing_cnpj'] ) )
                    $this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'CNPJ', 'wcbcf' ), __( 'is a required field', 'wcbcf' ) ) );

                if ( isset( $settings['validate_cnpj'] ) && ! empty( $_POST['billing_cnpj'] ) && ! $this->is_cnpj( $_POST['billing_cnpj'] ) )
                    $this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'CNPJ', 'wcbcf' ), __( 'is not valid', 'wcbcf' ) ) );
            }
        }
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
        $data['billing_cnpj']          = '';
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
     * Custom billing admin edit fields.
     *
     * @param  array $data Default WC_Order data.
     *
     * @return array       Custom WC_Order data.
     */
    public function admin_billing_fields( $data ) {
        global $woocommerce;

        // Get plugin settings.
        $settings = get_option( 'wcbcf_settings' );

        $billing_data['first_name'] = array(
            'label' => __( 'First Name', 'wcbcf' ),
            'show'  => false
        );
        $billing_data['last_name'] = array(
            'label' => __( 'Last Name', 'wcbcf' ),
            'show'  => false
        );

        if ( isset( $settings['person_type'] ) ) {
            $billing_data['persontype'] = array(
                'type'    => 'select',
                'label'   => __( 'Person type', 'wcbcf' ),
                'options' => array(
                    '0' => __( 'Select', 'wcbcf' ),
                    '1' => __( 'Individuals', 'wcbcf' ),
                    '2' => __( 'Legal Person', 'wcbcf' )
                )
            );
            $billing_data['cpf'] = array(
                'label' => __( 'CPF', 'wcbcf' ),
            );
            $billing_data['company'] = array(
                'label' => __( 'Company Name', 'wcbcf' ),
            );
            $billing_data['cnpj'] = array(
                'label' => __( 'CNPJ', 'wcbcf' ),
            );
        } else {
            $billing_data['company'] = array(
                'label' => __( 'Company', 'wcbcf' ),
                'show'  => false
            );
        }

        if ( isset( $settings['birthdate_sex'] ) ) {
            $billing_data['birthdate'] = array(
                'label' => __( 'Birthdate', 'wcbcf' )
            );
            $billing_data['sex'] = array(
                'label' => __( 'Sex', 'wcbcf' )
            );
        }

        $billing_data['address_1'] = array(
            'label' => __( 'Address 1', 'wcbcf' ),
            'show'  => false
        );
        $billing_data['number'] = array(
            'label' => __( 'Number', 'wcbcf' ),
            'show'  => false
        );
        $billing_data['address_2'] = array(
            'label' => __( 'Address 2', 'wcbcf' ),
            'show'  => false
        );
        $billing_data['neighborhood'] = array(
            'label' => __( 'Neighborhood', 'wcbcf' ),
            'show'  => false
        );
        $billing_data['city'] = array(
            'label' => __( 'City', 'wcbcf' ),
            'show'  => false
        );
        $billing_data['state'] = array(
            'label' => __( 'State', 'wcbcf' ),
            'show'  => false
        );
        $billing_data['country'] = array(
            'label'   => __( 'Country', 'wcbcf' ),
            'show'    => false,
            'type'    => 'select',
            'options' => array(
                '' => __( 'Select a country&hellip;', 'wcbcf' )
            ) + $woocommerce->countries->get_allowed_countries()
        );
        $billing_data['postcode'] = array(
            'label' => __( 'Postcode', 'wcbcf' ),
            'show'  => false
        );

        $billing_data['phone'] = array(
            'label' => __( 'Phone', 'wcbcf' ),
        );

        if ( isset( $settings['cell_phone'] ) ) {
            $billing_data['cellphone'] = array(
                'label' => __( 'Cell Phone', 'wcbcf' ),
            );
        }

        $billing_data['email'] = array(
            'label' => __( 'Email', 'wcbcf' ),
        );

        return apply_filters( 'wcbcf_admin_billing_fields', $billing_data );
    }

    /**
     * Custom shipping admin edit fields.
     *
     * @param  array $data Default WC_Order data.
     *
     * @return array       Custom WC_Order data.
     */
    public function admin_shipping_fields( $data ) {
        global $woocommerce;

        $shipping_data['first_name'] = array(
            'label' => __( 'First Name', 'wcbcf' ),
            'show'  => false
        );
        $shipping_data['last_name'] = array(
            'label' => __( 'Last Name', 'wcbcf' ),
            'show'  => false
        );
        $shipping_data['company'] = array(
            'label' => __( 'Company', 'wcbcf' ),
            'show'  => false
        );
        $shipping_data['address_1'] = array(
            'label' => __( 'Address 1', 'wcbcf' ),
            'show'  => false
        );
        $shipping_data['number'] = array(
            'label' => __( 'Number', 'wcbcf' ),
            'show'  => false
        );
        $shipping_data['address_2'] = array(
            'label' => __( 'Address 2', 'wcbcf' ),
            'show'  => false
        );
        $shipping_data['neighborhood'] = array(
            'label' => __( 'Neighborhood', 'wcbcf' ),
            'show'  => false
        );
        $shipping_data['city'] = array(
            'label' => __( 'City', 'wcbcf' ),
            'show'  => false
        );
        $shipping_data['state'] = array(
            'label' => __( 'State', 'wcbcf' ),
            'show'  => false
        );
        $shipping_data['country'] = array(
            'label'   => __( 'Country', 'wcbcf' ),
            'show'    => false,
            'type'    => 'select',
            'options' => array(
                '' => __( 'Select a country&hellip;', 'wcbcf' )
            ) + $woocommerce->countries->get_allowed_countries()
        );
        $shipping_data['postcode'] = array(
            'label' => __( 'Postcode', 'wcbcf' ),
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
    public function custom_admin_billing_fields( $order ) {
        global $woocommerce;

        // Get plugin settings.
        $settings = get_option( 'wcbcf_settings' );

        // Use nonce for verification.
        wp_nonce_field( basename( __FILE__ ), 'wcbcf_meta_fields' );

        $html = '<div class="wcbcf-address">';

        if ( ! $order->get_formatted_billing_address() ) {
            $html .= '<p class="none_set"><strong>' . __( 'Address', 'wcbcf' ) . ':</strong> ' . __( 'No billing address set.', 'wcbcf' ) . '</p>';
        } else {

            $html .= '<p><strong>' . __( 'Address', 'wcbcf' ) . ':</strong><br />';
            if ( version_compare( WOOCOMMERCE_VERSION, '2.0.5', '<=' ) ) {
                $html .= $order->billing_first_name . ' ' . $order->billing_last_name . '<br />';
                $html .= $order->billing_address_1 . ', ' . $order->billing_number . '<br />';
                $html .= $order->billing_address_2 . '<br />';
                $html .= $order->billing_neighborhood . '<br />';
                $html .= $order->billing_city . '<br />';
                if ( $woocommerce->countries->states[$order->billing_country] )
                    $html .= $woocommerce->countries->states[$order->billing_country][$order->billing_state] . '<br />';
                else
                    $html .= $order->billing_state . '<br />';

                $html .= $order->billing_postcode . '<br />';
                $html .= $woocommerce->countries->countries[$order->billing_country] . '<br />';

            } else {
                $html .= $order->get_formatted_billing_address();
            }

            $html .= '</p>';
        }

        $html .= '<h4>' . __( 'Customer data', 'wcbcf' ) . '</h4>';

        $html .= '<p>';

        if ( isset( $settings['person_type'] ) ) {

            // Person type information.
            if ( 1 == $order->billing_persontype )
                $html .= '<strong>' . __( 'CPF', 'wcbcf' ) . ': </strong>' . $order->billing_cpf . '<br />';

            if ( 2 == $order->billing_persontype ) {
                $html .= '<strong>' . __( 'CNPJ', 'wcbcf' ) . ': </strong>' . $order->billing_cnpj . '<br />';
                $html .= '<strong>' . __( 'Company Name', 'wcbcf' ) . ': </strong>' . $order->billing_company . '<br />';
            }
        } else {
            $html .= '<strong>' . __( 'Company', 'wcbcf' ) . ': </strong>' . $order->billing_company . '<br />';
        }

        if ( isset( $settings['birthdate_sex'] ) ) {

            // Birthdate information.
            $html .= '<strong>' . __( 'Birthdate', 'wcbcf' ) . ': </strong>' . $order->billing_birthdate . '<br />';

            // Sex Information.
            $html .= '<strong>' . __( 'Sex', 'wcbcf' ) . ': </strong>' . $order->billing_sex . '<br />';
        }

        $html .= '<strong>' . __( 'Phone', 'wcbcf' ) . ': </strong>' . $order->billing_phone . '<br />';

        // Cell Phone Information.
        if ( isset( $settings['cell_phone'] ) )
            $html .= '<strong>' . __( 'Cell Phone', 'wcbcf' ) . ': </strong>' . $order->billing_cellphone . '<br />';

        $html .= '<strong>' . __( 'Email', 'wcbcf' ) . ': </strong>' . $order->billing_email . '<br />';

        $html .= '</p>';

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
    public function custom_admin_shipping_fields( $order ) {
        global $woocommerce;

        // Get plugin settings.
        $settings = get_option( 'wcbcf_settings' );

        $html = '<div class="wcbcf-address">';

        if ( ! $order->get_formatted_shipping_address() ) {
            $html .= '<p class="none_set"><strong>' . __( 'Address', 'wcbcf' ) . ':</strong> ' . __( 'No shipping address set.', 'wcbcf' ) . '</p>';
        } else {

            $html .= '<p><strong>' . __( 'Address', 'wcbcf' ) . ':</strong><br />';
                if ( version_compare( WOOCOMMERCE_VERSION, '2.0.5', '<=' ) ) {
                $html .= $order->billing_first_name . ' ' . $order->billing_last_name . '<br />';
                $html .= $order->billing_address_1 . ', ' . $order->billing_number . '<br />';
                $html .= $order->billing_address_2 . '<br />';
                $html .= $order->billing_neighborhood . '<br />';
                $html .= $order->billing_city . '<br />';
                if ( $woocommerce->countries->states[$order->billing_country] )
                    $html .= $woocommerce->countries->states[$order->billing_country][$order->billing_state] . '<br />';
                else
                    $html .= $order->billing_state . '<br />';

                $html .= $order->billing_postcode . '<br />';
                $html .= $woocommerce->countries->countries[$order->billing_country] . '<br />';
            } else {
                $html .= $order->get_formatted_shipping_address();
            }

            $html .= '</p>';
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
    public function save_custom_fields( $post_id ) {
        global $post_type;

        if ( 'shop_order' == $post_type ) {

            // Verify nonce.
            if ( ! isset( $_POST['wcbcf_meta_fields'] ) || ! wp_verify_nonce( $_POST['wcbcf_meta_fields'], basename( __FILE__ ) ) )
                return $post_id;

            // Verify if this is an auto save routine.
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
                return $post_id;

            // Verify current user.
            if ( ! current_user_can( 'edit_pages', $post_id ) )
                return $post_id;

            // Get plugin settings.
            $settings = get_option( 'wcbcf_settings' );

            // Update options.
            update_post_meta( $post_id, '_billing_number', woocommerce_clean( $_POST['_billing_number'] ) );
            update_post_meta( $post_id, '_billing_neighborhood', woocommerce_clean( $_POST['_billing_neighborhood'] ) );
            update_post_meta( $post_id, '_shipping_number', woocommerce_clean( $_POST['_shipping_number'] ) );
            update_post_meta( $post_id, '_shipping_neighborhood', woocommerce_clean( $_POST['_shipping_neighborhood'] ) );

            if ( isset( $settings['person_type'] ) ) {
                update_post_meta( $post_id, '_billing_persontype', woocommerce_clean( $_POST['_billing_persontype'] ) );
                update_post_meta( $post_id, '_billing_cpf', woocommerce_clean( $_POST['_billing_cpf'] ) );
                update_post_meta( $post_id, '_billing_cnpj', woocommerce_clean( $_POST['_billing_cnpj'] ) );
            }

            if ( isset( $settings['birthdate_sex'] ) ) {
                update_post_meta( $post_id, '_billing_birthdate', woocommerce_clean( $_POST['_billing_birthdate'] ) );
                update_post_meta( $post_id, '_billing_sex', woocommerce_clean( $_POST['_billing_sex'] ) );
            }

            if ( isset( $settings['cell_phone'] ) )
                update_post_meta( $post_id, '_billing_cellphone', woocommerce_clean( $_POST['_billing_cellphone'] ) );

        }

        return $post_id;
    }

    /**
     * Add custom fields in customer details ajax.
     *
     * @return void
     */
    function custom_customer_details_ajax( $customer_data ) {
        $user_id = (int) trim( stripslashes( $_POST['user_id'] ) );
        $type_to_load = esc_attr( trim( stripslashes( $_POST['type_to_load'] ) ) );

        $custom_data = array(
            $type_to_load . '_number' => get_user_meta( $user_id, $type_to_load . '_number', true ),
            $type_to_load . '_neighborhood' => get_user_meta( $user_id, $type_to_load . '_neighborhood', true ),
            $type_to_load . '_persontype' => get_user_meta( $user_id, $type_to_load . '_persontype', true ),
            $type_to_load . '_cpf' => get_user_meta( $user_id, $type_to_load . '_cpf', true ),
            $type_to_load . '_cnpj' => get_user_meta( $user_id, $type_to_load . '_cnpj', true ),
            $type_to_load . '_birthdate' => get_user_meta( $user_id, $type_to_load . '_birthdate', true ),
            $type_to_load . '_sex' => get_user_meta( $user_id, $type_to_load . '_sex', true ),
            $type_to_load . '_cellphone' => get_user_meta( $user_id, $type_to_load . '_cellphone', true )
        );

        return array_merge( $customer_data, $custom_data );
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
                'label' => __( 'Company Name', 'wcbcf' ),
                'description' => ''
            );
        } else {
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
        $fields['billing']['fields']['billing_number'] = array(
            'label' => __( 'Number', 'wcbcf' ),
            'description' => ''
        );
        $fields['billing']['fields']['billing_address_2'] = array(
            'label' => __( 'Address 2', 'wcbcf' ),
            'description' => ''
        );
        $fields['billing']['fields']['billing_neighborhood'] = array(
            'label' => __( 'Neighborhood', 'wcbcf' ),
            'description' => ''
        );
        $fields['billing']['fields']['billing_city'] = array(
            'label' => __( 'City', 'wcbcf' ),
            'description' => ''
        );
        $fields['billing']['fields']['billing_state'] = array(
            'label' => __( 'State', 'wcbcf' ),
            'description' => __( 'State code', 'wcbcf' )
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
        $fields['shipping']['fields']['shipping_number'] = array(
            'label' => __( 'Number', 'wcbcf' ),
            'description' => ''
        );
        $fields['shipping']['fields']['shipping_address_2'] = array(
            'label' => __( 'Address 2', 'wcbcf' ),
            'description' => ''
        );
        $fields['shipping']['fields']['shipping_neighborhood'] = array(
            'label' => __( 'Neighborhood', 'wcbcf' ),
            'description' => ''
        );
        $fields['shipping']['fields']['shipping_city'] = array(
            'label' => __( 'City', 'wcbcf' ),
            'description' => ''
        );
        $fields['shipping']['fields']['shipping_state'] = array(
            'label' => __( 'State', 'wcbcf' ),
            'description' => __( 'State code', 'wcbcf' )
        );

        $new_fields = apply_filters( 'wcbcf_customer_meta_fields', $fields );

        return $new_fields;
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
     * Custom Bcash arguments.
     *
     * @param  array $args   Bcash default arguments.
     * @param  object $order Order data.
     *
     * @return array         New arguments.
     */
    public function bcash_args( $args, $order ) {
        $args['numero'] = $order->billing_number;

        if ( isset( $order->billing_persontype ) ) {
            if ( 1 == $order->billing_persontype )
                $args['cpf'] = str_replace( array( '-', '.' ), '', $order->billing_cpf );

            if ( 2 == $order->billing_persontype ) {
                $args['cliente_cnpj']         = str_replace( array( '-', '.' ), '', $order->billing_cnpj );
                $args['cliente_razao_social'] = $order->billing_company;
            }
        }

        return $args;
    }

    /**
     * Custom Moip arguments.
     *
     * @param  array  $args  Moip default arguments.
     * @param  object $order Order data.
     *
     * @return array         New arguments.
     */
    public function moip_args( $args, $order ) {
        $args['pagador_numero'] = $order->billing_number;
        $args['pagador_bairro'] = $order->billing_neighborhood;

        return $args;
    }

    /**
     * Custom Moip Transparent Checkout arguments.
     *
     * @param  array  $args  Moip Transparent Checkout default arguments.
     * @param  object $order Order data.
     *
     * @return array         New arguments.
     */
    public function moip_transparent_checkout_args( $args, $order ) {

        if ( isset( $order->billing_cpf ) )
            $args['cpf'] = $order->billing_cpf;

        if ( isset( $order->billing_birthdate ) ) {
            $birthdate = explode( '/', $order->billing_birthdate );

            $args['birthdate_day']   = $birthdate[0];
            $args['birthdate_month'] = $birthdate[1];
            $args['birthdate_year']  = $birthdate[2];
        }

        return $args;
    }

    /**
     * Custom PagSeguro arguments.
     *
     * @param  object $xml   PagSeguro SimpleXMLElement object.
     * @param  object $order Order data.
     *
     * @return object        New arguments.
     */
    public function pagseguro_args( $xml, $order ) {
        if ( isset( $order->billing_cpf ) ) {
            $documents = $xml->sender->addChild( 'documents' );
            $document = $documents->addChild( 'document' );
            $document->addChild( 'type', 'CPF' );
            $document->addChild( 'value', str_replace( array( '.', '-' ), '', $order->billing_cpf ) );
        }

        if ( isset( $xml->shipping->address ) ) {
            if ( $order->billing_number )
                $xml->shipping->address->addChild( 'number', $order->billing_number );

            if ( $order->billing_neighborhood )
                $xml->shipping->address->addChild( 'district' )->addCData( $order->billing_neighborhood );
        }

        return $xml;
    }
}

/**
 * WooCommerce fallback notice.
 *
 * @return string Fallack notice.
 */
function wcbcf_fallback_notice() {
    echo '<div class="error"><p>' . sprintf( __( 'WooCommerce Extra Checkout Fields for Brazil depends on %s to work!', 'wcbcf' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>' ) . '</p></div>';
}

/**
 * Load plugin functions.
 */
function wcbcf_plugin() {
    load_plugin_textdomain( 'wcbcf', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    // Check if WooCommerce is active.
    if ( wcbcf_is_woocommerce_active() )
        $wcBrazilianCheckoutFields = new WC_BrazilianCheckoutFields();
    else
        add_action( 'admin_notices', 'wcbcf_fallback_notice' );
}

add_action( 'plugins_loaded', 'wcbcf_plugin', 0 );

/**
 * Checks if WooCommerce is active.
 *
 * @return bool true if WooCommerce is active, false otherwise.
 */
function wcbcf_is_woocommerce_active() {

    $active_plugins = (array) get_option( 'active_plugins', array() );

    if ( is_multisite() )
        $active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );

    return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
}
