<?php
/**
 * Checkout block integration.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the Brazilian fields as WooCommerce additional checkout fields.
 *
 * Visibility and requiredness are expressed as document object rules so the
 * checkout block resolves them itself, without the plugin scripting the DOM.
 */
class Extra_Checkout_Fields_For_Brazil_Blocks {

	/**
	 * Namespace every field id is registered under.
	 *
	 * @var string
	 */
	const FIELD_NAMESPACE = 'csbmw';

	/**
	 * Contact fields, in registration order.
	 *
	 * @var array
	 */
	const CONTACT_FIELDS = array( 'persontype', 'cpf', 'rg', 'cnpj', 'ie', 'birthdate', 'gender', 'cellphone' );

	/**
	 * Address fields, in registration order.
	 *
	 * @var array
	 */
	const ADDRESS_FIELDS = array( 'number', 'neighborhood' );

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		// Runs after Extra_Checkout_Fields_For_Brazil::load_plugin_textdomain(),
		// so labels and option labels are registered translated.
		add_action( 'init', array( $this, 'register_fields' ), 20 );
		add_action( 'woocommerce_checkout_validate_order_before_payment', array( $this, 'validate_company' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Build a namespaced field id.
	 *
	 * @param string $key Field key without the namespace.
	 *
	 * @return string
	 */
	public static function field_id( $key ) {
		return self::FIELD_NAMESPACE . '/' . $key;
	}

	/**
	 * Get the field key of a namespaced id, or an empty string when the id
	 * belongs to another extension.
	 *
	 * @param string $field_id Namespaced field id.
	 *
	 * @return string
	 */
	public static function field_key( $field_id ) {
		$prefix = self::FIELD_NAMESPACE . '/';

		if ( 0 !== strpos( $field_id, $prefix ) ) {
			return '';
		}

		return substr( $field_id, strlen( $prefix ) );
	}

	/**
	 * Whether the running WooCommerce evaluates document object rules.
	 *
	 * @return bool
	 */
	protected function supports_rules() {
		return class_exists( \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\Validation::class );
	}

	/**
	 * Get a plugin setting.
	 *
	 * @param string $key      Setting key.
	 * @param mixed  $fallback Value returned when the setting is unset.
	 *
	 * @return mixed
	 */
	protected function setting( $key, $fallback = null ) {
		return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $fallback;
	}

	/**
	 * Rule matching a given person type selection.
	 *
	 * @param string $value Person type value.
	 *
	 * @return array
	 */
	protected function rule_person_type_is( $value ) {
		return array(
			'type'       => 'object',
			'properties' => array(
				'customer' => array(
					'type'       => 'object',
					'properties' => array(
						'additional_fields' => array(
							'type'       => 'object',
							'properties' => array(
								self::field_id( 'persontype' ) => array( 'const' => (string) $value ),
							),
							'required'   => array( self::field_id( 'persontype' ) ),
						),
					),
					'required'   => array( 'additional_fields' ),
				),
			),
			'required'   => array( 'customer' ),
		);
	}

	/**
	 * Rule matching a Brazilian billing address.
	 *
	 * @return array
	 */
	protected function rule_billing_is_brazil() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'customer' => array(
					'type'       => 'object',
					'properties' => array(
						'billing_address' => array(
							'type'       => 'object',
							'properties' => array(
								'country' => array( 'const' => 'BR' ),
							),
							'required'   => array( 'country' ),
						),
					),
					'required'   => array( 'billing_address' ),
				),
			),
			'required'   => array( 'customer' ),
		);
	}

	/**
	 * Combine rules so all of them must match.
	 *
	 * @param array $rules Rules to combine.
	 *
	 * @return array|bool
	 */
	protected function all_of( $rules ) {
		$rules = array_values( array_filter( $rules ) );

		if ( empty( $rules ) ) {
			return true;
		}

		if ( 1 === count( $rules ) ) {
			return $rules[0];
		}

		return array( 'allOf' => $rules );
	}

	/**
	 * Negate a rule.
	 *
	 * @param array|bool $rule Rule to negate.
	 *
	 * @return array|bool
	 */
	protected function negate( $rule ) {
		if ( is_bool( $rule ) ) {
			return ! $rule;
		}

		return array( 'not' => $rule );
	}

	/**
	 * Build the required and hidden options for a field.
	 *
	 * `$visible_when` gates both visibility and requiredness. `$required_when`
	 * adds conditions that only make the field mandatory.
	 *
	 * @param array $visible_when  Rules that must match for the field to show.
	 * @param array $required_when Extra rules that must match for it to be required.
	 * @param bool  $required      Whether the field is required at all.
	 *
	 * @return array
	 */
	protected function conditions( $visible_when = array(), $required_when = array(), $required = true ) {
		if ( ! $this->supports_rules() ) {
			return array(
				'required' => $required,
				'hidden'   => false,
			);
		}

		$visible = $this->all_of( $visible_when );

		return array(
			'required' => $required ? $this->all_of( array_merge( $visible_when, $required_when ) ) : false,
			'hidden'   => true === $visible ? false : $this->negate( $visible ),
		);
	}

	/**
	 * Register every field the current settings ask for.
	 *
	 * @return void
	 */
	public function register_fields() {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}

		$this->settings = (array) get_option( 'wcbcf_settings', array() );

		$this->register_person_type_fields();
		$this->register_personal_fields();
		$this->register_address_fields();
	}

	/**
	 * Register the person type, document and company registration fields.
	 *
	 * @return void
	 */
	protected function register_person_type_fields() {
		$person_type = intval( $this->setting( 'person_type', 0 ) );

		if ( 0 === $person_type ) {
			return;
		}

		// Only a store accepting both person types needs the selector; with a
		// single type the matching documents are always visible.
		$asks_person_type = 1 === $person_type;
		$brazil_only      = null !== $this->setting( 'only_brazil' ) ? array( $this->rule_billing_is_brazil() ) : array();

		if ( $asks_person_type ) {
			$this->register_field(
				'persontype',
				array(
					'label'    => __( 'Person type', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'location' => 'contact',
					'type'     => 'select',
					'index'    => 5,
					'options'  => array(
						array(
							'label' => __( 'Individuals', 'woocommerce-extra-checkout-fields-for-brazil' ),
							'value' => '1',
						),
						array(
							'label' => __( 'Legal Person', 'woocommerce-extra-checkout-fields-for-brazil' ),
							'value' => '2',
						),
					),
				),
				$this->conditions( array(), $brazil_only )
			);
		}

		$individual = $asks_person_type ? array( $this->rule_person_type_is( '1' ) ) : array();
		$company    = $asks_person_type ? array( $this->rule_person_type_is( '2' ) ) : array();

		if ( 1 === $person_type || 2 === $person_type ) {
			$this->register_field(
				'cpf',
				array(
					'label'      => __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'location'   => 'contact',
					'index'      => 6,
					'attributes' => array(
						'maxLength' => '14',
						'title'     => __( 'Enter a valid CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
					),
				),
				$this->conditions( $individual, $brazil_only )
			);

			if ( null !== $this->setting( 'rg' ) ) {
				$this->register_field(
					'rg',
					array(
						'label'      => __( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'location'   => 'contact',
						'index'      => 7,
						'attributes' => array( 'maxLength' => '20' ),
					),
					$this->conditions( $individual, $brazil_only )
				);
			}
		}

		if ( 1 === $person_type || 3 === $person_type ) {
			$this->register_field(
				'cnpj',
				array(
					'label'      => __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'location'   => 'contact',
					'index'      => 8,
					'attributes' => array(
						'maxLength' => '18',
						'title'     => __( 'Enter a valid CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
					),
				),
				$this->conditions( $company, $brazil_only )
			);

			if ( null !== $this->setting( 'ie' ) ) {
				$this->register_field(
					'ie',
					array(
						'label'      => __( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'location'   => 'contact',
						'index'      => 9,
						'attributes' => array( 'maxLength' => '20' ),
					),
					$this->conditions( $company, $brazil_only )
				);
			}
		}
	}

	/**
	 * Untranslated gender labels, used to recognise values stored by a site
	 * that has since changed language.
	 *
	 * @var array
	 */
	const GENDER_SOURCE_LABELS = array(
		'prefer_not_to_say' => 'Prefer not to say',
		'female'            => 'Female',
		'male'              => 'Male',
		'other'             => 'Other',
	);

	/**
	 * Gender options, keyed by the value stored against the order.
	 *
	 * The keys are stable so a store that changes language does not invalidate
	 * values already submitted. Extra_Checkout_Fields_For_Brazil_Legacy_Sync
	 * turns them back into the labels the classic checkout has always stored.
	 *
	 * @return array
	 */
	public static function get_gender_options() {
		return array(
			'prefer_not_to_say' => __( 'Prefer not to say', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'female'            => __( 'Female', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'male'              => __( 'Male', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'other'             => __( 'Other', 'woocommerce-extra-checkout-fields-for-brazil' ),
		);
	}

	/**
	 * Register birthdate, gender and cell phone.
	 *
	 * @return void
	 */
	protected function register_personal_fields() {
		if ( null !== $this->setting( 'birthdate' ) ) {
			$this->register_field(
				'birthdate',
				array(
					'label'      => __( 'Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'location'   => 'contact',
					'index'      => 10,
					'attributes' => array(
						'maxLength' => '10',
						'title'     => __( 'Enter a valid birthdate as dd/mm/yyyy', 'woocommerce-extra-checkout-fields-for-brazil' ),
					),
				),
				array(
					'required' => true,
					'hidden'   => false,
				)
			);
		}

		if ( null !== $this->setting( 'gender' ) ) {
			$this->register_field(
				'gender',
				array(
					'label'    => __( 'Gender', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'location' => 'contact',
					'type'     => 'select',
					'index'    => 11,
					'options'  => array_map(
						function ( $value, $label ) {
							return array(
								'label' => $label,
								'value' => $value,
							);
						},
						array_keys( self::get_gender_options() ),
						array_values( self::get_gender_options() )
					),
				),
				array(
					'required' => true,
					'hidden'   => false,
				)
			);
		}

		$cell_phone = (string) $this->setting( 'cell_phone', '0' );

		if ( in_array( $cell_phone, array( '1', '2' ), true ) ) {
			$this->register_field(
				'cellphone',
				array(
					'label'      => __( 'Cell Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'location'   => 'contact',
					'index'      => 12,
					'attributes' => array( 'maxLength' => '15' ),
				),
				array(
					'required' => '2' === $cell_phone,
					'hidden'   => false,
				)
			);
		}
	}

	/**
	 * Register the Brazilian address fields.
	 *
	 * @return void
	 */
	protected function register_address_fields() {
		$this->register_field(
			'number',
			array(
				'label'      => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'location'   => 'address',
				'index'      => 41,
				'attributes' => array( 'maxLength' => '30' ),
			),
			array(
				'required' => true,
				'hidden'   => false,
			)
		);

		$this->register_field(
			'neighborhood',
			array(
				'label'    => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'location' => 'address',
				'index'    => 42,
			),
			array(
				'required' => '1' === (string) $this->setting( 'neighborhood_required', '0' ),
				'hidden'   => false,
			)
		);
	}

	/**
	 * Register a single field.
	 *
	 * @param string $key        Field key without the namespace.
	 * @param array  $options    Field options.
	 * @param array  $conditions Resolved `required` and `hidden` options.
	 *
	 * @return void
	 */
	protected function register_field( $key, $options, $conditions ) {
		$options = array_merge(
			$options,
			$conditions,
			array(
				'id'                => self::field_id( $key ),
				'sanitize_callback' => array( $this, 'sanitize_field' ),
				'validate_callback' => array( $this, 'validate_field' ),
			)
		);

		/* translators: %s: field label. */
		$options['optionalLabel'] = sprintf( __( '%s (optional)', 'woocommerce-extra-checkout-fields-for-brazil' ), $options['label'] );

		woocommerce_register_additional_checkout_field( $options );
	}

	/**
	 * Trim submitted values.
	 *
	 * @param mixed $value Submitted value.
	 * @param array $field Field definition.
	 *
	 * @return mixed
	 */
	public function sanitize_field( $value, $field ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return is_string( $value ) ? trim( $value ) : $value;
	}

	/**
	 * Validate a submitted value.
	 *
	 * WooCommerce resolves `$field['required']` against the document object
	 * before calling this, so conditional fields report the right errors.
	 *
	 * @param mixed $value Submitted value.
	 * @param array $field Field definition.
	 *
	 * @return true|WP_Error
	 */
	public function validate_field( $value, $field ) {
		$errors = new WP_Error();
		$value  = is_string( $value ) ? trim( $value ) : $value;
		$key    = self::field_key( isset( $field['id'] ) ? $field['id'] : '' );

		if ( ! empty( $field['required'] ) && ( '' === $value || null === $value ) ) {
			$errors->add(
				'woocommerce_required_checkout_field',
				sprintf(
					/* translators: %s: field label. */
					__( '%s is a required field.', 'woocommerce-extra-checkout-fields-for-brazil' ),
					$field['label']
				)
			);

			return $errors;
		}

		if ( '' === $value || null === $value ) {
			return true;
		}

		$settings = (array) get_option( 'wcbcf_settings', array() );

		if ( 'cpf' === $key && isset( $settings['validate_cpf'] ) && ! Extra_Checkout_Fields_For_Brazil_Formatting::is_cpf( $value ) ) {
			$errors->add( 'woocommerce_invalid_cpf', __( 'CPF is not valid.', 'woocommerce-extra-checkout-fields-for-brazil' ) );
		}

		if ( 'cnpj' === $key && isset( $settings['validate_cnpj'] ) && ! Extra_Checkout_Fields_For_Brazil_Formatting::is_cnpj( $value ) ) {
			$errors->add( 'woocommerce_invalid_cnpj', __( 'CNPJ is not valid.', 'woocommerce-extra-checkout-fields-for-brazil' ) );
		}

		if ( 'birthdate' === $key && ! Extra_Checkout_Fields_For_Brazil_Formatting::is_date( $value ) ) {
			$errors->add( 'woocommerce_invalid_birthdate', __( 'Birthdate is not valid. Use the dd/mm/yyyy format.', 'woocommerce-extra-checkout-fields-for-brazil' ) );
		}

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Require the billing company when the customer checks out as a legal person.
	 *
	 * Company is a core address field, so it cannot carry our conditional rules.
	 *
	 * @param WC_Order $order  Order being validated.
	 * @param WP_Error $errors Errors collected so far.
	 *
	 * @return void
	 */
	public function validate_company( $order, $errors ) {
		$settings    = (array) get_option( 'wcbcf_settings', array() );
		$person_type = isset( $settings['person_type'] ) ? intval( $settings['person_type'] ) : 0;

		if ( 0 === $person_type ) {
			return;
		}

		if ( isset( $settings['only_brazil'] ) && 'BR' !== $order->get_billing_country() ) {
			return;
		}

		$is_company = 3 === $person_type || '2' === (string) $order->get_meta( '_billing_persontype' );

		if ( ! $is_company || '' !== trim( (string) $order->get_billing_company() ) ) {
			return;
		}

		$errors->add(
			'woocommerce_required_billing_company',
			sprintf(
				/* translators: %s: field label. */
				__( '%s is a required field.', 'woocommerce-extra-checkout-fields-for-brazil' ),
				__( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' )
			)
		);
	}

	/**
	 * Enqueue the block checkout script on pages that render the checkout or cart.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! function_exists( 'has_block' ) ) {
			return;
		}

		if ( ! has_block( 'woocommerce/checkout' ) && ! has_block( 'woocommerce/cart' ) ) {
			return;
		}

		$settings = (array) get_option( 'wcbcf_settings', array() );

		Extra_Checkout_Fields_For_Brazil_Assets::enqueue( 'woocommerce-extra-checkout-fields-for-brazil-blocks', 'blocks' );

		wp_localize_script(
			'woocommerce-extra-checkout-fields-for-brazil-blocks',
			'bmwBlocksParams',
			array(
				'namespace'   => self::FIELD_NAMESPACE,
				'mailcheck'   => isset( $settings['mailcheck'] ) ? 'yes' : 'no',
				'maskedinput' => isset( $settings['maskedinput'] ) ? 'yes' : 'no',
				/* translators: %hint%: email hint */
				'suggestText' => __( 'Did you mean: %hint%?', 'woocommerce-extra-checkout-fields-for-brazil' ),
			)
		);
	}
}

new Extra_Checkout_Fields_For_Brazil_Blocks();
