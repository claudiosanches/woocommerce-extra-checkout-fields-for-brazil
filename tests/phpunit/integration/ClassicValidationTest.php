<?php
/**
 * Tests for the classic checkout validation.
 *
 * The block checkout validates through WooCommerce's own field API, covered by
 * BlockRulesTest. This is the shortcode checkout, which reports through the
 * WP_Error WooCommerce passes to woocommerce_after_checkout_validation.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

/**
 * Covers valid_checkout_fields and maybe_ignore_company_required.
 */
class ClassicValidationTest extends WP_UnitTestCase {

	/**
	 * A CPF and a CNPJ whose check digits are valid.
	 */
	const VALID_CPF  = '111.444.777-35';
	const VALID_CNPJ = '11.222.333/0001-81';

	/**
	 * Front end instance under test.
	 *
	 * @var Extra_Checkout_Fields_For_Brazil_Front_End
	 */
	protected $front_end;

	public function set_up() {
		parent::set_up();
		$this->front_end = $this->hooked_front_end();
	}

	public function tear_down() {
		unset( $_POST['billing_cpf'], $_POST['billing_country'], $_POST['billing_persontype'] );
		parent::tear_down();
	}

	/**
	 * The instance the plugin registered, rather than a second one whose
	 * constructor would hook everything all over again.
	 *
	 * @return Extra_Checkout_Fields_For_Brazil_Front_End
	 */
	protected function hooked_front_end() {
		foreach ( $this->validation_callbacks() as $callback ) {
			if ( 'valid_checkout_fields' === $callback[1] ) {
				return $callback[0];
			}
		}

		$this->fail( 'The classic checkout validation is not hooked on woocommerce_after_checkout_validation.' );
	}

	/**
	 * The plugin's callbacks on the checkout validation hook, in running order.
	 *
	 * @return array
	 */
	protected function validation_callbacks() {
		$callbacks = array();

		foreach ( $GLOBALS['wp_filter']['woocommerce_after_checkout_validation'] as $hooks ) {
			foreach ( $hooks as $hook ) {
				if ( is_array( $hook['function'] ) && $hook['function'][0] instanceof Extra_Checkout_Fields_For_Brazil_Front_End ) {
					$callbacks[] = array( $hook['function'][0], $hook['function'][1], $hook['accepted_args'] );
				}
			}
		}

		return $callbacks;
	}

	/**
	 * Run the validation and hand back the codes it reported.
	 *
	 * @param array $settings Plugin settings.
	 * @param array $data     Checkout posted data.
	 *
	 * @return array
	 */
	protected function codes( array $settings, array $data ) {
		return $this->errors( $settings, $data )->get_error_codes();
	}

	/**
	 * Run the validation and hand back the WP_Error it filled in.
	 *
	 * @param array $settings Plugin settings.
	 * @param array $data     Checkout posted data.
	 *
	 * @return WP_Error
	 */
	protected function errors( array $settings, array $data ) {
		update_option( 'wcbcf_settings', $settings );

		$errors = new WP_Error();
		$this->front_end->valid_checkout_fields( array_merge( array( 'billing_country' => 'BR' ), $data ), $errors );

		return $errors;
	}

	/**
	 * A complete individual submission, so a test only has to break one field.
	 *
	 * @param array $overrides Fields to replace.
	 *
	 * @return array
	 */
	protected function individual( array $overrides = array() ) {
		return array_merge(
			array(
				'billing_persontype' => '1',
				'billing_cpf'        => self::VALID_CPF,
				'billing_rg'         => '123456789',
				'billing_birthdate'  => '01/01/1990',
			),
			$overrides
		);
	}

	/**
	 * A complete company submission.
	 *
	 * @param array $overrides Fields to replace.
	 *
	 * @return array
	 */
	protected function company( array $overrides = array() ) {
		return array_merge(
			array(
				'billing_persontype' => '2',
				'billing_company'    => 'Empresa Ltda',
				'billing_cnpj'       => self::VALID_CNPJ,
				'billing_ie'         => 'ISENTO',
			),
			$overrides
		);
	}

	/**
	 * Every setting the validation reads, all on.
	 *
	 * @param array $overrides Settings to replace.
	 *
	 * @return array
	 */
	protected function all_settings( array $overrides = array() ) {
		return array_merge(
			array(
				'person_type'   => '1',
				'rg'            => '1',
				'ie'            => '1',
				'birthdate'     => '1',
				'validate_cpf'  => '1',
				'validate_cnpj' => '1',
			),
			$overrides
		);
	}

	/**
	 * The hook hands over the posted data and the error bag, and the callback
	 * that drops the company error has to run after the one that adds it.
	 */
	public function test_registers_both_callbacks_in_order_with_both_arguments() {
		$registered = array();

		foreach ( $this->validation_callbacks() as $callback ) {
			$registered[] = $callback[1];

			$this->assertSame( 2, $callback[2], $callback[1] . ' has to accept the data and the error bag.' );
		}

		$this->assertSame(
			array( 'valid_checkout_fields', 'maybe_ignore_company_required' ),
			$registered
		);
	}

	public function test_accepts_a_complete_individual_submission() {
		$this->assertSame( array(), $this->codes( $this->all_settings(), $this->individual() ) );
	}

	public function test_accepts_a_complete_company_submission() {
		$this->assertSame( array(), $this->codes( $this->all_settings(), $this->company() ) );
	}

	/**
	 * Nothing else can run if the argument is not the error bag, and older
	 * callers may still invoke this on woocommerce_checkout_process.
	 */
	public function test_ignores_anything_that_is_not_an_error_bag() {
		update_option( 'wcbcf_settings', $this->all_settings() );

		// A bag with no add() would be fatal without the guard.
		$this->assertNull( $this->front_end->valid_checkout_fields( array(), new stdClass() ) );
	}

	public function test_the_disable_filter_skips_every_check() {
		add_filter( 'wcbcf_disable_checkout_validation', '__return_true' );

		$codes = $this->codes( $this->all_settings(), array() );

		remove_filter( 'wcbcf_disable_checkout_validation', '__return_true' );

		$this->assertSame( array(), $codes );
	}

	/**
	 * Missing required fields, by settings and person type.
	 *
	 * @return array
	 */
	public function missing_field_provider() {
		return array(
			'person type'                => array(
				array(),
				array( 'billing_persontype_required' ),
			),
			'cpf'                        => array(
				$this->individual( array( 'billing_cpf' => '' ) ),
				array( 'billing_cpf_required' ),
			),
			'rg'                         => array(
				$this->individual( array( 'billing_rg' => '' ) ),
				array( 'billing_rg_required' ),
			),
			'company, cnpj and ie'       => array(
				$this->company(
					array(
						'billing_company' => '',
						'billing_cnpj'    => '',
						'billing_ie'      => '',
					)
				),
				array( 'billing_company_required', 'billing_cnpj_required', 'billing_ie_required' ),
			),
			'nothing for an individual'  => array(
				$this->individual(
					array(
						'billing_cpf' => '',
						'billing_rg'  => '',
					)
				),
				array( 'billing_cpf_required', 'billing_rg_required' ),
			),
			'a document made of spaces'  => array(
				$this->individual( array( 'billing_cpf' => '   ' ) ),
				array( 'billing_cpf_required' ),
			),
			'a company made of spaces'   => array(
				$this->company( array( 'billing_company' => '   ' ) ),
				array( 'billing_company_required' ),
			),
		);
	}

	/**
	 * @dataProvider missing_field_provider
	 *
	 * @param array $data     Checkout posted data.
	 * @param array $expected Error codes the validation should report.
	 */
	public function test_reports_missing_required_fields( array $data, array $expected ) {
		$this->assertSame( $expected, $this->codes( $this->all_settings(), $data ) );
	}

	/**
	 * Fields the validation only looks at when their setting is on.
	 *
	 * @return array
	 */
	public function optional_field_provider() {
		return array(
			'rg'        => array( 'rg', $this->individual( array( 'billing_rg' => '' ) ) ),
			'ie'        => array( 'ie', $this->company( array( 'billing_ie' => '' ) ) ),
			'birthdate' => array( 'birthdate', $this->individual( array( 'billing_birthdate' => '31/02/1990' ) ) ),
		);
	}

	/**
	 * @dataProvider optional_field_provider
	 *
	 * @param string $setting Setting that gates the check.
	 * @param array  $data    Checkout posted data failing that check.
	 */
	public function test_a_field_that_is_turned_off_is_not_checked( $setting, array $data ) {
		$settings = $this->all_settings();
		unset( $settings[ $setting ] );

		$this->assertSame( array(), $this->codes( $settings, $data ) );
		$this->assertNotSame( array(), $this->codes( $this->all_settings(), $data ) );
	}

	/**
	 * Documents that should fail their check digits.
	 *
	 * @return array
	 */
	public function invalid_document_provider() {
		return array(
			'cpf'  => array(
				$this->individual( array( 'billing_cpf' => '111.444.777-00' ) ),
				'billing_cpf_invalid',
			),
			'cnpj' => array(
				$this->company( array( 'billing_cnpj' => '11.222.333/0001-00' ) ),
				'billing_cnpj_invalid',
			),
		);
	}

	/**
	 * @dataProvider invalid_document_provider
	 *
	 * @param array  $data     Checkout posted data.
	 * @param string $expected Error code the validation should report.
	 */
	public function test_rejects_a_document_that_fails_its_check_digits( array $data, $expected ) {
		$this->assertSame( array( $expected ), $this->codes( $this->all_settings(), $data ) );
	}

	/**
	 * @dataProvider invalid_document_provider
	 *
	 * @param array  $data    Checkout posted data.
	 * @param string $code    Error code the validation reports when checking.
	 */
	public function test_check_digits_are_only_verified_when_asked( array $data, $code ) {
		$setting  = 'billing_cpf_invalid' === $code ? 'validate_cpf' : 'validate_cnpj';
		$settings = $this->all_settings();
		unset( $settings[ $setting ] );

		$this->assertSame( array(), $this->codes( $settings, $data ) );
	}

	public function test_accepts_the_alphanumeric_cnpj() {
		$data = $this->company( array( 'billing_cnpj' => '12.ABC.345/01DE-35' ) );

		$this->assertSame( array(), $this->codes( $this->all_settings(), $data ) );
	}

	public function test_rejects_a_birthdate_that_is_not_a_real_date() {
		$data = $this->individual( array( 'billing_birthdate' => '31/02/1990' ) );

		$this->assertSame( array( 'billing_birthdate_invalid' ), $this->codes( $this->all_settings(), $data ) );
	}

	/**
	 * The birthdate does not belong to a person type, so it is checked before
	 * the person type rules can return early.
	 */
	public function test_the_birthdate_is_checked_even_when_person_type_is_off() {
		$settings = $this->all_settings( array( 'person_type' => '0' ) );
		$data     = array( 'billing_birthdate' => '31/02/1990' );

		$this->assertSame( array( 'billing_birthdate_invalid' ), $this->codes( $settings, $data ) );
	}

	public function test_the_birthdate_is_checked_outside_brazil() {
		$settings = $this->all_settings( array( 'only_brazil' => '1' ) );
		$data     = array(
			'billing_country'   => 'PT',
			'billing_birthdate' => '31/02/1990',
		);

		$this->assertSame( array( 'billing_birthdate_invalid' ), $this->codes( $settings, $data ) );
	}

	public function test_person_type_off_skips_the_document_checks() {
		$settings = $this->all_settings( array( 'person_type' => '0' ) );

		$this->assertSame( array(), $this->codes( $settings, array() ) );
	}

	public function test_only_brazil_skips_the_document_checks_abroad() {
		$settings = $this->all_settings( array( 'only_brazil' => '1' ) );

		$this->assertSame( array(), $this->codes( $settings, array( 'billing_country' => 'PT' ) ) );
	}

	public function test_documents_are_still_checked_abroad_when_only_brazil_is_off() {
		$this->assertSame(
			array( 'billing_persontype_required' ),
			$this->codes( $this->all_settings(), array( 'billing_country' => 'PT' ) )
		);
	}

	/**
	 * WooCommerce always posts a country, so an empty one means a request that
	 * skipped the address form, which its own required checks reject.
	 */
	public function test_an_empty_country_counts_as_outside_brazil() {
		$settings = $this->all_settings( array( 'only_brazil' => '1' ) );

		$this->assertSame( array(), $this->codes( $settings, array( 'billing_country' => '' ) ) );
	}

	/**
	 * Individuals only settings, where there is no person type to pick.
	 */
	public function test_person_type_two_checks_the_cpf_without_a_person_type() {
		$settings = $this->all_settings( array( 'person_type' => '2' ) );
		$data     = array( 'billing_cpf' => '111.444.777-00' );

		$this->assertSame( array( 'billing_cpf_invalid', 'billing_rg_required' ), $this->codes( $settings, $data ) );
	}

	/**
	 * Companies only settings.
	 */
	public function test_person_type_three_checks_the_cnpj_without_a_person_type() {
		$settings = $this->all_settings( array( 'person_type' => '3' ) );
		$data     = array(
			'billing_company' => 'Empresa Ltda',
			'billing_cnpj'    => self::VALID_CNPJ,
			'billing_ie'      => 'ISENTO',
		);

		$this->assertSame( array(), $this->codes( $settings, $data ) );
		$this->assertSame(
			array( 'billing_company_required', 'billing_cnpj_required', 'billing_ie_required' ),
			$this->codes( $settings, array() )
		);
	}

	public function test_an_individual_is_not_asked_for_company_documents() {
		$codes = $this->codes( $this->all_settings(), $this->individual() );

		$this->assertNotContains( 'billing_cnpj_required', $codes );
		$this->assertNotContains( 'billing_company_required', $codes );
	}

	public function test_a_company_is_not_asked_for_individual_documents() {
		$codes = $this->codes( $this->all_settings(), $this->company() );

		$this->assertNotContains( 'billing_cpf_required', $codes );
		$this->assertNotContains( 'billing_rg_required', $codes );
	}

	/**
	 * The id is what WooCommerce reads to highlight the offending input, so a
	 * missing one leaves the customer hunting for the field.
	 *
	 * @return array
	 */
	public function error_id_provider() {
		return array(
			'person type' => array( array(), 'billing_persontype_required', 'billing_persontype' ),
			'cpf'         => array( $this->individual( array( 'billing_cpf' => '' ) ), 'billing_cpf_required', 'billing_cpf' ),
			'cpf digits'  => array( $this->individual( array( 'billing_cpf' => '111.444.777-00' ) ), 'billing_cpf_invalid', 'billing_cpf' ),
			'rg'          => array( $this->individual( array( 'billing_rg' => '' ) ), 'billing_rg_required', 'billing_rg' ),
			'birthdate'   => array( $this->individual( array( 'billing_birthdate' => '31/02/1990' ) ), 'billing_birthdate_invalid', 'billing_birthdate' ),
			'company'     => array( $this->company( array( 'billing_company' => '' ) ), 'billing_company_required', 'billing_company' ),
			'cnpj'        => array( $this->company( array( 'billing_cnpj' => '' ) ), 'billing_cnpj_required', 'billing_cnpj' ),
			'cnpj digits' => array( $this->company( array( 'billing_cnpj' => '11.222.333/0001-00' ) ), 'billing_cnpj_invalid', 'billing_cnpj' ),
			'ie'          => array( $this->company( array( 'billing_ie' => '' ) ), 'billing_ie_required', 'billing_ie' ),
		);
	}

	/**
	 * @dataProvider error_id_provider
	 *
	 * @param array  $data Checkout posted data.
	 * @param string $code Error code the validation reports.
	 * @param string $id   Input the error should point at.
	 */
	public function test_every_error_points_at_its_field( array $data, $code, $id ) {
		$errors = $this->errors( $this->all_settings(), $data );

		$this->assertSame( array( 'id' => $id ), $errors->get_error_data( $code ) );
	}

	/**
	 * @dataProvider error_id_provider
	 *
	 * @param array  $data Checkout posted data.
	 * @param string $code Error code the validation reports.
	 */
	public function test_every_error_carries_the_field_name( array $data, $code ) {
		$errors = $this->errors( $this->all_settings(), $data );

		$this->assertMatchesRegularExpression( '#^<strong>.+</strong> .+\.$#', $errors->get_error_message( $code ) );
	}

	/**
	 * WooCommerce validates the checkout before this runs, so the bag usually
	 * already holds its errors.
	 */
	public function test_keeps_the_errors_already_in_the_bag() {
		update_option( 'wcbcf_settings', $this->all_settings() );

		$errors = new WP_Error( 'billing_email_required', 'Email is a required field.' );
		$this->front_end->valid_checkout_fields( array( 'billing_country' => 'BR' ), $errors );

		$this->assertSame(
			array( 'billing_email_required', 'billing_persontype_required' ),
			$errors->get_error_codes()
		);
	}

	/**
	 * A caller that hands over a partial data array still gets the request
	 * validated, which is what the fallback to the raw request is for.
	 */
	public function test_falls_back_to_the_request_when_the_data_is_missing_a_field() {
		$_POST['billing_cpf'] = '111.444.777-00';

		$codes = $this->codes(
			$this->all_settings(),
			array(
				'billing_persontype' => '1',
				'billing_rg'         => '123456789',
			)
		);

		$this->assertSame( array( 'billing_cpf_invalid' ), $codes );
	}

	public function test_the_data_wins_over_the_request() {
		$_POST['billing_cpf'] = '111.444.777-00';

		$this->assertSame( array(), $this->codes( $this->all_settings(), $this->individual() ) );
	}

	public function test_the_company_error_is_dropped_for_an_individual() {
		$errors = new WP_Error( 'billing_company_required', 'Company is a required field.' );

		$this->front_end->maybe_ignore_company_required( array( 'billing_persontype' => '1' ), $errors );

		$this->assertSame( array(), $errors->get_error_codes() );
	}

	public function test_the_company_error_is_kept_for_a_company() {
		$errors = new WP_Error( 'billing_company_required', 'Company is a required field.' );

		$this->front_end->maybe_ignore_company_required( array( 'billing_persontype' => '2' ), $errors );

		$this->assertSame( array( 'billing_company_required' ), $errors->get_error_codes() );
	}

	/**
	 * Both callbacks sit on the same hook, and the one that drops the company
	 * error has to run after the one that can add it.
	 */
	public function test_the_company_error_is_dropped_after_the_documents_are_checked() {
		update_option( 'wcbcf_settings', $this->all_settings( array( 'person_type' => '3' ) ) );

		$errors = new WP_Error();
		$data   = array(
			'billing_country'    => 'BR',
			'billing_persontype' => '1',
			'billing_cnpj'       => self::VALID_CNPJ,
			'billing_ie'         => 'ISENTO',
		);

		do_action( 'woocommerce_after_checkout_validation', $data, $errors );

		$this->assertSame( array(), $errors->get_error_codes() );
	}
}
