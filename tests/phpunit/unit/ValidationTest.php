<?php
/**
 * Tests for the Brazilian document validators.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

use PHPUnit\Framework\TestCase;

/**
 * Extra_Checkout_Fields_For_Brazil_Validation tests.
 */
class ValidationTest extends TestCase {

	/**
	 * Valid CPFs, formatted and unformatted.
	 *
	 * @return array
	 */
	public function valid_cpf_provider() {
		return array(
			'formatted'   => array( '111.444.777-35' ),
			'unformatted' => array( '11144477735' ),
			'other'       => array( '123.456.789-09' ),
		);
	}

	/**
	 * @dataProvider valid_cpf_provider
	 *
	 * @param string $cpf CPF to check.
	 */
	public function test_accepts_valid_cpf( $cpf ) {
		$this->assertTrue( Extra_Checkout_Fields_For_Brazil_Validation::is_cpf( $cpf ) );
	}

	/**
	 * Invalid CPFs.
	 *
	 * @return array
	 */
	public function invalid_cpf_provider() {
		return array(
			'wrong check digits' => array( '111.444.777-00' ),
			'repeated digits'    => array( '111.111.111-11' ),
			'too short'          => array( '1114447773' ),
			'too long'           => array( '111444777351' ),
			'letters'            => array( 'abc.def.ghi-jk' ),
			'empty'              => array( '' ),
		);
	}

	/**
	 * @dataProvider invalid_cpf_provider
	 *
	 * @param string $cpf CPF to check.
	 */
	public function test_rejects_invalid_cpf( $cpf ) {
		$this->assertFalse( Extra_Checkout_Fields_For_Brazil_Validation::is_cpf( $cpf ) );
	}

	/**
	 * Valid CNPJs, including the alphanumeric format.
	 *
	 * @return array
	 */
	public function valid_cnpj_provider() {
		return array(
			'formatted'    => array( '11.222.333/0001-81' ),
			'unformatted'  => array( '11222333000181' ),
			'other'        => array( '11.444.777/0001-61' ),
			'alphanumeric' => array( '12.ABC.345/01DE-35' ),
		);
	}

	/**
	 * @dataProvider valid_cnpj_provider
	 *
	 * @param string $cnpj CNPJ to check.
	 */
	public function test_accepts_valid_cnpj( $cnpj ) {
		$this->assertTrue( Extra_Checkout_Fields_For_Brazil_Validation::is_cnpj( $cnpj ) );
	}

	/**
	 * Invalid CNPJs.
	 *
	 * @return array
	 */
	public function invalid_cnpj_provider() {
		return array(
			'wrong check digits'      => array( '11.222.333/0001-00' ),
			'repeated characters'     => array( '11.111.111/1111-11' ),
			'too short'               => array( '1122233300018' ),
			'letters in check digits' => array( '12.ABC.345/01DE-3X' ),
			'empty'                   => array( '' ),
		);
	}

	/**
	 * @dataProvider invalid_cnpj_provider
	 *
	 * @param string $cnpj CNPJ to check.
	 */
	public function test_rejects_invalid_cnpj( $cnpj ) {
		$this->assertFalse( Extra_Checkout_Fields_For_Brazil_Validation::is_cnpj( $cnpj ) );
	}

	/**
	 * Dates and whether they are valid.
	 *
	 * @return array
	 */
	public function date_provider() {
		return array(
			'valid'               => array( '01/02/1990', true ),
			'leap day'            => array( '29/02/2024', true ),
			'non leap day'        => array( '29/02/2023', false ),
			'day out of range'    => array( '31/04/1990', false ),
			'month out of range'  => array( '01/13/1990', false ),
			'american order'      => array( '02/28/1990', false ),
			'unpadded'            => array( '1/2/1990', false ),
			'dashes'              => array( '01-02-1990', false ),
			'two digit year'      => array( '01/02/90', false ),
			'empty'               => array( '', false ),
		);
	}

	/**
	 * @dataProvider date_provider
	 *
	 * @param string $date     Date to check.
	 * @param bool   $expected Expected result.
	 */
	public function test_validates_dates( $date, $expected ) {
		$this->assertSame( $expected, Extra_Checkout_Fields_For_Brazil_Validation::is_date( $date ) );
	}
}
