<?php
/**
 * Phone numbers from Brazil and abroad.
 *
 * Mirrors assets/js/shared/phone.ts, which formats the same way as customers
 * type.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Phone
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extra_Checkout_Fields_For_Brazil_Phone class.
 */
class Extra_Checkout_Fields_For_Brazil_Phone {

	/**
	 * Brazil's calling code.
	 *
	 * @var string
	 */
	const BRAZIL = '55';

	/**
	 * Calling codes whose numbers keep their leading zero after the code.
	 * Elsewhere that zero is the trunk prefix, dialled only within the country.
	 *
	 * @var array
	 */
	const KEEP_ZERO = array( '39', '378', '379', '225', '242' );

	/**
	 * Country shown for a calling code several countries share.
	 *
	 * @var array
	 */
	const PRIMARY = array(
		'1'  => 'US',
		'7'  => 'RU',
		'39' => 'IT',
		'44' => 'GB',
		'47' => 'NO',
		'61' => 'AU',
	);

	/**
	 * Calling codes by country.
	 *
	 * @var array|null
	 */
	protected static $codes = null;

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		add_filter( 'woocommerce_validate_phone', array( $this, 'validate' ), 10, 3 );
		add_action( 'woocommerce_before_order_object_save', array( $this, 'normalize_order' ) );
		add_action( 'woocommerce_before_customer_object_save', array( $this, 'normalize_customer' ) );
	}

	/**
	 * How Brazilian numbers are written.
	 *
	 * @param array|null $settings Plugin settings, read when not given.
	 *
	 * @return string national or international.
	 */
	public static function style( $settings = null ) {
		$settings = null === $settings ? (array) get_option( 'wcbcf_settings', array() ) : $settings;

		return isset( $settings['phone_format'] ) && 'international' === $settings['phone_format'] ? 'international' : 'national';
	}

	/**
	 * Calling code of each country, without the plus.
	 *
	 * @return array
	 */
	public static function calling_codes() {
		if ( null === self::$codes ) {
			self::$codes = array();

			foreach ( array_keys( WC()->countries->get_countries() ) as $country ) {
				$code = ltrim( (string) WC()->countries->get_country_calling_code( $country ), '+' );

				if ( '' === $code ) {
					continue;
				}

				// The North American plan writes the area code as part of the
				// national number, so +1 is the code for all of it.
				self::$codes[ $country ] = '1' === $code[0] ? '1' : $code;
			}
		}

		return self::$codes;
	}

	/**
	 * What the scripts need to format phones.
	 *
	 * @param array $settings Plugin settings.
	 *
	 * @return array
	 */
	public static function script_params( $settings ) {
		$picker    = ! empty( $settings['phone_country_picker'] );
		$countries = array();

		if ( $picker ) {
			foreach ( WC()->countries->get_countries() as $country => $name ) {
				$countries[ $country ] = html_entity_decode( $name, ENT_QUOTES, 'UTF-8' );
			}
		}

		return array(
			'codes'     => self::calling_codes(),
			'primary'   => self::PRIMARY,
			'style'     => self::style( $settings ),
			'picker'    => $picker ? 'yes' : 'no',
			'countries' => $countries,
		);
	}

	/**
	 * Calling code of a country, with Brazil for an address without one.
	 *
	 * @param string $country Country code.
	 *
	 * @return string
	 */
	protected static function country_code( $country ) {
		if ( Extra_Checkout_Fields_For_Brazil_Front_End::is_brazil( $country ) ) {
			return self::BRAZIL;
		}

		$codes = self::calling_codes();

		return isset( $codes[ $country ] ) ? $codes[ $country ] : '';
	}

	/**
	 * Calling code a string of digits starts with.
	 *
	 * @param string $digits Digits after the plus.
	 *
	 * @return string
	 */
	protected static function code_of( $digits ) {
		$codes = array_flip( self::calling_codes() );

		for ( $length = 1; $length <= 3; $length++ ) {
			$code = substr( $digits, 0, $length );

			if ( isset( $codes[ $code ] ) ) {
				return (string) $code;
			}
		}

		return '';
	}

	/**
	 * Split a phone into its calling code and national digits.
	 *
	 * A number without a plus belongs to the country of its address.
	 *
	 * @param string $phone   Phone as typed.
	 * @param string $country Country of the address.
	 *
	 * @return array|null Code and national digits, or null when empty.
	 */
	public static function parse( $phone, $country = '' ) {
		$phone  = trim( (string) $phone );
		$digits = (string) preg_replace( '/\D/', '', $phone );

		if ( '' === $digits ) {
			return null;
		}

		if ( '+' === substr( $phone, 0, 1 ) ) {
			$code     = self::code_of( $digits );
			$national = substr( $digits, strlen( $code ) );
		} else {
			$code     = self::country_code( $country );
			$national = $digits;
		}

		if ( ! in_array( $code, self::KEEP_ZERO, true ) ) {
			$national = ltrim( $national, '0' );
		}

		// Brazil's code typed without the plus. No national number is longer
		// than eleven digits.
		if ( self::BRAZIL === $code && 11 < strlen( $national ) && 0 === strpos( $national, self::BRAZIL ) ) {
			$national = ltrim( substr( $national, 2 ), '0' );
		}

		return array(
			'code'     => $code,
			'national' => $national,
		);
	}

	/**
	 * Whether a phone is a complete number.
	 *
	 * Brazilian numbers take a DDD and eight or nine digits. Elsewhere only
	 * the E.164 length is checked.
	 *
	 * @param string $phone   Phone as typed.
	 * @param string $country Country of the address.
	 *
	 * @return bool
	 */
	public static function is_valid( $phone, $country = '' ) {
		$parsed = self::parse( $phone, $country );

		if ( null === $parsed || '' === $parsed['code'] ) {
			return false;
		}

		if ( self::BRAZIL === $parsed['code'] ) {
			return (bool) preg_match( '/^[1-9]{2}9?\d{8}$/', $parsed['national'] );
		}

		$length = strlen( $parsed['code'] . $parsed['national'] );

		return 8 <= $length && 15 >= $length;
	}

	/**
	 * A phone in E.164, such as +5511987654321.
	 *
	 * @param string $phone   Phone as stored.
	 * @param string $country Country of the address.
	 *
	 * @return string Empty when the phone is not a complete number.
	 */
	public static function e164( $phone, $country = '' ) {
		if ( ! self::is_valid( $phone, $country ) ) {
			return '';
		}

		$parsed = self::parse( $phone, $country );

		return '+' . $parsed['code'] . $parsed['national'];
	}

	/**
	 * A phone the way this store writes it.
	 *
	 * Brazilian numbers on a Brazilian address follow the store's format and
	 * every other number keeps its calling code. Anything that is not a
	 * complete number is left as typed.
	 *
	 * @param string      $phone   Phone as typed.
	 * @param string      $country Country of the address.
	 * @param string|null $from    Country the phone was typed for, when the address has changed since.
	 * @param string|null $style   Brazilian format, read from the settings when not given.
	 *
	 * @return string
	 */
	public static function format( $phone, $country = '', $from = null, $style = null ) {
		$from = null === $from ? $country : $from;

		if ( ! self::is_valid( $phone, $from ) ) {
			return trim( (string) $phone );
		}

		$parsed = self::parse( $phone, $from );

		if ( self::BRAZIL === $parsed['code'] ) {
			$local = preg_replace( '/^(\d{2})(\d+)(\d{4})$/', '($1) $2-$3', $parsed['national'] );
			$style = null === $style ? self::style() : $style;

			return 'national' === $style && Extra_Checkout_Fields_For_Brazil_Front_End::is_brazil( $country ) ? $local : '+' . self::BRAZIL . ' ' . $local;
		}

		return '+' . $parsed['code'] . ' ' . self::rest( $phone, $parsed['code'] );
	}

	/**
	 * The national part of a foreign phone, with the customer's own spacing.
	 *
	 * @param string $phone Phone as typed.
	 * @param string $code  Its calling code.
	 *
	 * @return string
	 */
	protected static function rest( $phone, $code ) {
		$rest = trim( (string) $phone );

		if ( '+' === substr( $rest, 0, 1 ) ) {
			$rest = (string) preg_replace( '/^\+\D*' . implode( '\D*', str_split( $code ) ) . '/', '', $rest );
		}

		// A trunk prefix written as (0), as in +44 (0)20.
		$rest = str_replace( '(0)', '', (string) preg_replace( '/[^\d\s().-]/', '', $rest ) );

		if ( ! in_array( $code, self::KEEP_ZERO, true ) ) {
			$rest = (string) preg_replace( '/^([\s.-]*\(?)0+/', '$1', $rest );
		}

		return trim( (string) preg_replace( '/\s+/', ' ', $rest ), ' .-' );
	}

	/**
	 * Hold a phone to a complete number for its address.
	 *
	 * WooCommerce only checks the characters, and passes the address country
	 * where it has one. A number without a plus and without a country is left
	 * to that check.
	 *
	 * @param bool        $valid   Whether WooCommerce accepted it.
	 * @param string      $phone   Phone as typed.
	 * @param string|null $country Country of the address.
	 *
	 * @return bool
	 */
	public function validate( $valid, $phone, $country = null ) {
		if ( ! $valid || apply_filters( 'wcbcf_disable_checkout_validation', false ) ) {
			return $valid;
		}

		$plus = '+' === substr( trim( (string) $phone ), 0, 1 );

		if ( ! $plus && ( null === $country || '' === self::country_code( (string) $country ) ) ) {
			return $valid;
		}

		return null === self::parse( $phone ) || self::is_valid( $phone, (string) $country );
	}

	/**
	 * Write the phones of an order being saved in the store's format.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return void
	 */
	public function normalize_order( $order ) {
		$this->normalize( $order, array( '_billing_cellphone', '_wc_other/csbmw/cellphone' ) );
	}

	/**
	 * Write the phones of a customer being saved in the store's format.
	 *
	 * @param WC_Customer $customer Customer.
	 *
	 * @return void
	 */
	public function normalize_customer( $customer ) {
		$this->normalize( $customer, array( 'billing_cellphone', '_wc_other/csbmw/cellphone' ) );
	}

	/**
	 * Format the phones that changed, or whose address changed country.
	 *
	 * Only what this save touches is rewritten, so an unrelated update leaves
	 * older numbers alone.
	 *
	 * @param WC_Order|WC_Customer $item            Order or customer.
	 * @param array                $cellphone_keys  Meta keys holding the cell phone.
	 *
	 * @return void
	 */
	protected function normalize( $item, $cellphone_keys ) {
		$changes = $item->get_changes();
		$data    = $item->get_data();

		foreach ( array( 'billing', 'shipping' ) as $type ) {
			$country = (string) call_user_func( array( $item, "get_{$type}_country" ) );
			$from    = isset( $data[ $type ]['country'] ) ? (string) $data[ $type ]['country'] : $country;
			$moved   = isset( $changes[ $type ]['country'] );
			$phone   = (string) call_user_func( array( $item, "get_{$type}_phone" ) );

			if ( '' !== $phone && ( isset( $changes[ $type ]['phone'] ) || $moved ) ) {
				call_user_func( array( $item, "set_{$type}_phone" ), self::format( $phone, $country, isset( $changes[ $type ]['phone'] ) ? $country : $from ) );
			}

			if ( 'billing' !== $type ) {
				continue;
			}

			foreach ( $cellphone_keys as $key ) {
				$typed = self::changed_meta( $item, $key );

				if ( null !== $typed || $moved ) {
					$cellphone = null !== $typed ? $typed : (string) $item->get_meta( $key );

					if ( '' !== $cellphone ) {
						$item->update_meta_data( $key, self::format( $cellphone, $country, null !== $typed ? $country : $from ) );
					}
				}
			}
		}
	}

	/**
	 * Value of a meta entry added or changed since the object was read.
	 *
	 * @param WC_Data $item Object.
	 * @param string  $key  Meta key.
	 *
	 * @return string|null
	 */
	protected static function changed_meta( $item, $key ) {
		foreach ( $item->get_meta_data() as $meta ) {
			if ( $key === $meta->key && is_scalar( $meta->value ) && ( empty( $meta->id ) || $meta->get_changes() ) ) {
				return (string) $meta->value;
			}
		}

		return null;
	}
}

new Extra_Checkout_Fields_For_Brazil_Phone();
