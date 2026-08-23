<?php
/**
 * Registers the plugin's fields for every settings combination and checks the
 * result against what the settings ask for.
 *
 * The settings interact, so this walks the whole cartesian product rather than
 * a handful of representative cases.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;

/**
 * Settings matrix test.
 */
class SettingsMatrixTest extends WP_UnitTestCase {

	/**
	 * WooCommerce's field controller.
	 *
	 * @var CheckoutFields
	 */
	protected $controller;

	/**
	 * Problems found while walking the matrix.
	 *
	 * @var array
	 */
	protected $problems = array();

	public function set_up() {
		parent::set_up();
		$this->controller = Package::container()->get( CheckoutFields::class );
	}

	/**
	 * The plugin's registered fields, keyed by id.
	 *
	 * @return array
	 */
	protected function registered_fields() {
		return array_filter(
			$this->controller->get_additional_fields(),
			static function ( $id ) {
				return '' !== Extra_Checkout_Fields_For_Brazil_Blocks::field_key( $id );
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Register the plugin's fields afresh for the given settings.
	 *
	 * @param array $settings Plugin settings.
	 *
	 * @return array Registered fields.
	 */
	protected function register( array $settings ) {
		update_option( 'wcbcf_settings', $settings );

		foreach ( array_keys( $this->registered_fields() ) as $id ) {
			__internal_woocommerce_blocks_deregister_checkout_field( $id );
		}

		( new Extra_Checkout_Fields_For_Brazil_Blocks() )->register_fields();

		return $this->registered_fields();
	}

	/**
	 * Record a problem against the combination being checked.
	 *
	 * @param string $label Combination description.
	 * @param string $why   What was wrong.
	 *
	 * @return void
	 */
	protected function problem( $label, $why ) {
		$this->problems[] = $label . ' :: ' . $why;
	}

	public function test_every_settings_combination_registers_the_right_fields() {
		$notices = array();
		add_action(
			'doing_it_wrong_run',
			static function ( $function_name, $message ) use ( &$notices ) {
				$notices[] = $function_name . ': ' . $message;
			},
			10,
			2
		);

		$combinations = 0;

		foreach ( array( 0, 1, 2, 3 ) as $person_type ) {
			foreach ( array( false, true ) as $only_brazil ) {
				foreach ( array( false, true ) as $rg ) {
					foreach ( array( false, true ) as $ie ) {
						foreach ( array( false, true ) as $birthdate ) {
							foreach ( array( false, true ) as $gender ) {
								foreach ( array( '-1', '0', '1', '2' ) as $cell_phone ) {
									foreach ( array( '0', '1' ) as $neighborhood_required ) {
										++$combinations;
										$this->check_combination(
											$person_type,
											$only_brazil,
											$rg,
											$ie,
											$birthdate,
											$gender,
											$cell_phone,
											$neighborhood_required
										);
									}
								}
							}
						}
					}
				}
			}
		}

		$this->assertSame( 1024, $combinations );
		$this->assertSame( array(), array_slice( array_unique( $this->problems ), 0, 20 ) );
		$this->assertSame( array(), array_unique( $notices ), 'WooCommerce rejected a field registration' );
	}

	/**
	 * Check one settings combination.
	 *
	 * @param int    $person_type           person_type setting.
	 * @param bool   $only_brazil           only_brazil setting.
	 * @param bool   $rg                    rg setting.
	 * @param bool   $ie                    ie setting.
	 * @param bool   $birthdate             birthdate setting.
	 * @param bool   $gender                gender setting.
	 * @param string $cell_phone            cell_phone setting.
	 * @param string $neighborhood_required neighborhood_required setting.
	 *
	 * @return void
	 */
	protected function check_combination( $person_type, $only_brazil, $rg, $ie, $birthdate, $gender, $cell_phone, $neighborhood_required ) {
		$settings = array(
			'person_type'           => $person_type,
			'cell_phone'            => $cell_phone,
			'neighborhood_required' => $neighborhood_required,
		);

		foreach ( compact( 'only_brazil', 'rg', 'ie', 'birthdate', 'gender' ) as $key => $on ) {
			if ( $on ) {
				$settings[ $key ] = 1;
			}
		}

		$fields = $this->register( $settings );
		$label  = wp_json_encode( $settings );
		$has    = static function ( $key ) use ( $fields ) {
			return isset( $fields[ Extra_Checkout_Fields_For_Brazil_Blocks::field_id( $key ) ] );
		};

		$individual = in_array( $person_type, array( 1, 2 ), true );
		$company    = in_array( $person_type, array( 1, 3 ), true );

		$expected = array(
			'persontype'   => 1 === $person_type,
			'cpf'          => $individual,
			'rg'           => $individual && $rg,
			'cnpj'         => $company,
			'ie'           => $company && $ie,
			'birthdate'    => $birthdate,
			'gender'       => $gender,
			'cellphone'    => in_array( $cell_phone, array( '1', '2' ), true ),
			'number'       => true,
			'neighborhood' => true,
		);

		foreach ( $expected as $key => $want ) {
			if ( $has( $key ) !== $want ) {
				$this->problem( $label, sprintf( '%s should%s be registered', $key, $want ? '' : ' not' ) );
			}
		}

		foreach ( $fields as $id => $field ) {
			if ( true === $field['hidden'] ) {
				$this->problem( $label, "$id registered as hidden" );
			}

			if ( ! is_callable( $field['validate_callback'] ) || ! is_callable( $field['sanitize_callback'] ) ) {
				$this->problem( $label, "$id is missing a callback" );
			}

			if ( 'text' === $field['type'] && ! isset( $field['attributes']['maxLength'] ) ) {
				$this->problem( $label, "$id has no maxLength" );
			}
		}

		// Documents are conditional only when the customer picks the person type.
		foreach ( array( 'cpf', 'rg', 'cnpj', 'ie' ) as $key ) {
			if ( ! $has( $key ) ) {
				continue;
			}

			$field = $fields[ Extra_Checkout_Fields_For_Brazil_Blocks::field_id( $key ) ];

			if ( 1 === $person_type ) {
				if ( ! is_array( $field['hidden'] ) || ! is_array( $field['required'] ) ) {
					$this->problem( $label, "$key should be conditional" );
				}
				continue;
			}

			if ( false !== $field['hidden'] ) {
				$this->problem( $label, "$key should never be hidden" );
			}

			if ( $only_brazil !== is_array( $field['required'] ) ) {
				$this->problem( $label, "$key requiredness should follow only_brazil" );
			}
		}

		foreach ( array( 'birthdate', 'gender' ) as $key ) {
			if ( $has( $key ) && true !== $fields[ Extra_Checkout_Fields_For_Brazil_Blocks::field_id( $key ) ]['required'] ) {
				$this->problem( $label, "$key should be required" );
			}
		}

		if ( true !== $fields['csbmw/number']['required'] ) {
			$this->problem( $label, 'number should be required' );
		}

		if ( $has( 'cellphone' ) && $fields['csbmw/cellphone']['required'] !== ( '2' === $cell_phone ) ) {
			$this->problem( $label, 'cellphone requiredness is wrong' );
		}

		if ( $fields['csbmw/neighborhood']['required'] !== ( '1' === $neighborhood_required ) ) {
			$this->problem( $label, 'neighborhood requiredness is wrong' );
		}

		// Number sits right after address line 1 and neighborhood after address
		// line 2, matching the order the classic checkout has always used.
		$core = $this->controller->get_core_fields();

		if ( $fields['csbmw/number']['index'] <= $core['address_1']['index'] ) {
			$this->problem( $label, 'number should come after address line 1' );
		}

		if ( $fields['csbmw/neighborhood']['index'] <= $core['address_2']['index'] ) {
			$this->problem( $label, 'neighborhood should come after address line 2' );
		}
	}
}
