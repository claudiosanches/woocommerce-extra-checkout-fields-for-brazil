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
	 * Every combination of the settings that decide which fields exist.
	 *
	 * The settings interact, so the whole cartesian product is walked rather
	 * than a handful of representative cases. Each combination arrives as its
	 * own test case, so a failure names the settings that produced it.
	 *
	 * @return iterable
	 */
	public static function combination_provider() {
		$axes = array(
			'person_type'           => array( 0, 1, 2, 3 ),
			'only_brazil'           => array( false, true ),
			'rg'                    => array( false, true ),
			'ie'                    => array( false, true ),
			'birthdate'             => array( false, true ),
			'gender'                => array( false, true ),
			'cell_phone'            => array( '-1', '0', '1', '2' ),
			'neighborhood_required' => array( '0', '1' ),
		);

		foreach ( self::cartesian( $axes ) as $combination ) {
			yield self::describe( $combination ) => array( $combination );
		}
	}

	/**
	 * Cartesian product of a set of named axes.
	 *
	 * @param array $axes Values each key can take.
	 *
	 * @return array
	 */
	protected static function cartesian( array $axes ) {
		$rows = array( array() );

		foreach ( $axes as $key => $values ) {
			$expanded = array();

			foreach ( $rows as $row ) {
				foreach ( $values as $value ) {
					$expanded[] = array_merge( $row, array( $key => $value ) );
				}
			}

			$rows = $expanded;
		}

		return $rows;
	}

	/**
	 * Readable name for a combination, used as the data set name.
	 *
	 * @param array $combination Settings combination.
	 *
	 * @return string
	 */
	protected static function describe( array $combination ) {
		$parts = array();

		foreach ( $combination as $key => $value ) {
			if ( is_bool( $value ) ) {
				if ( $value ) {
					$parts[] = $key;
				}

				continue;
			}

			$parts[] = $key . '=' . $value;
		}

		return implode( ' ', $parts );
	}

	/**
	 * Turn a combination into the option the plugin reads.
	 *
	 * The plugin treats these settings as checkboxes, so the ones that are off
	 * are absent rather than falsy.
	 *
	 * @param array $combination Settings combination.
	 *
	 * @return array
	 */
	protected static function settings_for( array $combination ) {
		$settings = array(
			'person_type'           => $combination['person_type'],
			'cell_phone'            => $combination['cell_phone'],
			'neighborhood_required' => $combination['neighborhood_required'],
		);

		foreach ( array( 'only_brazil', 'rg', 'ie', 'birthdate', 'gender' ) as $key ) {
			if ( $combination[ $key ] ) {
				$settings[ $key ] = 1;
			}
		}

		return $settings;
	}

	/**
	 * Register the plugin's fields for one settings combination and check the
	 * result against what those settings ask for.
	 *
	 * @dataProvider combination_provider
	 *
	 * @param array $combination Settings combination.
	 *
	 * @return void
	 */
	public function test_registers_the_fields_the_settings_ask_for( array $combination ) {
		$notices = array();
		add_action(
			'doing_it_wrong_run',
			static function ( $function_name, $message ) use ( &$notices ) {
				$notices[] = $function_name . ': ' . $message;
			},
			10,
			2
		);

		$this->check_combination( $combination );

		$this->assertSame( array(), array_unique( $notices ), 'WooCommerce rejected a field registration' );
	}

	/**
	 * Check one settings combination.
	 *
	 * @param array $combination Settings combination.
	 *
	 * @return void
	 */
	protected function check_combination( array $combination ) {
		$person_type           = $combination['person_type'];
		$rg                    = $combination['rg'];
		$ie                    = $combination['ie'];
		$birthdate             = $combination['birthdate'];
		$gender                = $combination['gender'];
		$cell_phone            = $combination['cell_phone'];
		$neighborhood_required = $combination['neighborhood_required'];

		$fields = $this->register( self::settings_for( $combination ) );
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
			$this->assertSame( $want, $has( $key ), sprintf( '%s should%s be registered', $key, $want ? '' : ' not' ) );
		}

		foreach ( $fields as $id => $field ) {
			$this->assertNotTrue( $field['hidden'], "$id registered as hidden" );
			$this->assertIsCallable( $field['validate_callback'], "$id is missing a validate callback" );
			$this->assertIsCallable( $field['sanitize_callback'], "$id is missing a sanitize callback" );

			if ( 'text' === $field['type'] ) {
				$this->assertArrayHasKey( 'maxLength', $field['attributes'], "$id has no maxLength" );
			}
		}

		// Documents are conditional only when the customer picks the person type.
		foreach ( array( 'cpf', 'rg', 'cnpj', 'ie' ) as $key ) {
			if ( ! $has( $key ) ) {
				continue;
			}

			$field = $fields[ Extra_Checkout_Fields_For_Brazil_Blocks::field_id( $key ) ];

			if ( 1 === $person_type ) {
				$this->assertIsArray( $field['hidden'], "$key should be conditional" );
				$this->assertIsArray( $field['required'], "$key should be conditional" );
				continue;
			}

			$this->assertFalse( $field['hidden'], "$key should never be hidden" );
			$this->assertSame( $combination['only_brazil'], is_array( $field['required'] ), "$key requiredness should follow only_brazil" );
		}

		foreach ( array( 'birthdate', 'gender' ) as $key ) {
			if ( $has( $key ) ) {
				$this->assertTrue( $fields[ Extra_Checkout_Fields_For_Brazil_Blocks::field_id( $key ) ]['required'], "$key should be required" );
			}
		}

		$this->assertTrue( $fields['csbmw/number']['required'], 'number should be required' );

		if ( $has( 'cellphone' ) ) {
			$this->assertSame( '2' === $cell_phone, $fields['csbmw/cellphone']['required'], 'cellphone requiredness is wrong' );
		}

		$this->assertSame( '1' === $neighborhood_required, $fields['csbmw/neighborhood']['required'], 'neighborhood requiredness is wrong' );

		// Number sits right after address line 1 and neighborhood after address
		// line 2, matching the order the classic checkout has always used.
		$core = $this->controller->get_core_fields();

		$this->assertGreaterThan( $core['address_1']['index'], $fields['csbmw/number']['index'], 'number should come after address line 1' );
		$this->assertGreaterThan( $core['address_2']['index'], $fields['csbmw/neighborhood']['index'], 'neighborhood should come after address line 2' );
	}
}
