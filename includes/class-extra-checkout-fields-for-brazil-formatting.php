<?php
/**
 * Extra checkout fields formatting methods.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Formatting
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extra_Checkout_Fields_For_Brazil_Formatting class.
 */
class Extra_Checkout_Fields_For_Brazil_Formatting {

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
	 * @param  string $cnpj CNPJ to validate.
	 *
	 * @return bool
	 */
	public static function is_cnpj( $cnpj ) {
		$pesos = array( 6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 );

		$cnpj = strtoupper( preg_replace( '/[\.\/-]/', '', $cnpj ) );

		if ( 14 !== strlen( $cnpj ) ) {
			return false;
		}

		if ( ! preg_match( '/^[A-Z0-9]{12}\d{2}$/', $cnpj ) ) {
			return false;
		}

		if ( '00000000000000' === $cnpj ) {
			return false;
		}

		if ( preg_match( '/^\d{14}$/', $cnpj ) && preg_match( '/^(\d)\1{13}$/', $cnpj ) ) {
			return false;
		}

		$soma1 = 0;
		$soma2 = 0;

		for ( $i = 0; $i < 12; $i++ ) {
			$valor  = ord( $cnpj[ $i ] ) - 48;
			$soma1 += $valor * $pesos[ $i + 1 ];
			$soma2 += $valor * $pesos[ $i ];
		}

		$dv1 = ( $soma1 % 11 < 2 ) ? 0 : 11 - ( $soma1 % 11 );

		$soma2 += $dv1 * $pesos[12];

		$dv2 = ( $soma2 % 11 < 2 ) ? 0 : 11 - ( $soma2 % 11 );

		return $cnpj[12] === (string) $dv1 && $cnpj[13] === (string) $dv2;
	}
}
