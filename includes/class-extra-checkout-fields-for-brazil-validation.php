<?php
/**
 * Extra checkout fields validation methods.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Validation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extra_Checkout_Fields_For_Brazil_Validation class.
 */
class Extra_Checkout_Fields_For_Brazil_Validation {

	/**
	 * Checks if the CPF is valid.
	 *
	 * @param  string $cpf CPF to validate.
	 *
	 * @return bool
	 */
	public static function is_cpf( $cpf ) {
		$cpf = preg_replace( '/[^0-9]/', '', $cpf );

		if ( 11 !== strlen( $cpf ) || preg_match( '/^([0-9])\1+$/', $cpf ) ) {
			return false;
		}

		$digit = substr( $cpf, 0, 9 );

		for ( $j = 10; $j <= 11; $j++ ) {
			$sum = 0;

			for ( $i = 0; $i < $j - 1; $i++ ) {
				$sum += ( $j - $i ) * intval( $digit[ $i ] );
			}

			$summod11        = $sum % 11;
			$digit[ $j - 1 ] = $summod11 < 2 ? 0 : 11 - $summod11;
		}

		return intval( $digit[9] ) === intval( $cpf[9] ) && intval( $digit[10] ) === intval( $cpf[10] );
	}

	/**
	 * Checks if the CNPJ is valid.
	 *
	 * Accepts both the numeric CNPJ and the alphanumeric format Receita Federal
	 * adopted in 2026, where the first twelve characters may be letters. Each
	 * character contributes its ASCII code minus 48; for digits that is the
	 * digit itself, so the two formats share one algorithm.
	 *
	 * @param  string $cnpj CNPJ to validate.
	 *
	 * @return bool
	 */
	public static function is_cnpj( $cnpj ) {
		$cnpj = preg_replace( '/[^A-Z0-9]/', '', strtoupper( (string) $cnpj ) );

		if ( ! preg_match( '/^[A-Z0-9]{12}[0-9]{2}$/', $cnpj ) || preg_match( '/^([A-Z0-9])\1+$/', $cnpj ) ) {
			return false;
		}

		$check_digit = function ( $value, $weights ) {
			$sum = 0;

			for ( $i = 0, $length = strlen( $value ); $i < $length; $i++ ) {
				$sum += ( ord( $value[ $i ] ) - 48 ) * $weights[ $i ];
			}

			$remainder = $sum % 11;

			return $remainder < 2 ? 0 : 11 - $remainder;
		};

		$base   = substr( $cnpj, 0, 12 );
		$first  = $check_digit( $base, array( 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ) );
		$second = $check_digit( $base . $first, array( 6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ) );

		return substr( $cnpj, 12 ) === $first . $second;
	}

	/**
	 * Checks if a date is a real calendar date in the dd/mm/yyyy format.
	 *
	 * @param  string $date Date to validate.
	 *
	 * @return bool
	 */
	public static function is_date( $date ) {
		if ( ! preg_match( '/^(\d{2})\/(\d{2})\/(\d{4})$/', (string) $date, $matches ) ) {
			return false;
		}

		return checkdate( intval( $matches[2] ), intval( $matches[1] ), intval( $matches[3] ) );
	}
}
