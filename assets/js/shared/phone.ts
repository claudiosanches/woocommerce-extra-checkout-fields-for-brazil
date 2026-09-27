/**
 * Phone numbers from Brazil and abroad.
 *
 * A number without a plus belongs to the country of its address. Brazilian
 * numbers on a Brazilian address follow the store's format; every other number
 * is written with its calling code, which is suggested from the address as the
 * customer types. Mirrors Extra_Checkout_Fields_For_Brazil_Phone.
 */

import { __ } from '@wordpress/i18n';
import { formatPhone as formatBrazilian } from './mask';

export interface PhoneParams {
	/** Calling code of each country, without the plus. */
	codes?: Record< string, string >;
	/** Country shown for a code several countries share. */
	primary?: Record< string, string >;
	/** How Brazilian numbers are written on a Brazilian address. */
	style?: string;
	/** Whether the country code picker is offered. */
	picker?: string;
	/** Country names for the picker. */
	countries?: Record< string, string >;
}

export type PhoneWriter = ( input: HTMLInputElement, value: string ) => void;

const BRAZIL = '55';

// Codes whose numbers keep the leading zero, which elsewhere is the trunk
// prefix dialled only within the country.
const KEEP_ZERO = [ '39', '378', '379', '225', '242' ];

// E.164 caps a number at fifteen digits, calling code included.
const MAX_DIGITS = 15;

const isBrazil = ( country: string ): boolean =>
	'' === country || 'BR' === country;

/**
 * Calling code of a country, with Brazil for an address without one.
 *
 * @param country Country code.
 * @param params  Phone parameters.
 * @return Calling code, or an empty string when unknown.
 */
export function countryCode( country: string, params: PhoneParams ): string {
	return isBrazil( country ) ? BRAZIL : params.codes?.[ country ] ?? '';
}

/**
 * Calling code a string of digits starts with.
 *
 * @param digits Digits after the plus.
 * @param params Phone parameters.
 * @return Calling code, or an empty string while it is incomplete.
 */
function codeOf( digits: string, params: PhoneParams ): string {
	const codes = new Set( Object.values( params.codes ?? {} ) );

	for ( let length = 1; length <= 3; length++ ) {
		const code = digits.slice( 0, length );

		if ( code.length === length && codes.has( code ) ) {
			return code;
		}
	}

	return '';
}

const stripTrunk = ( national: string, code: string ): string =>
	KEEP_ZERO.includes( code ) ? national : national.replace( /^0+/, '' );

/**
 * Split a phone into its calling code and national digits.
 *
 * @param value   Phone as typed.
 * @param country Country of the address.
 * @param params  Phone parameters.
 * @return Code and national digits, or null when empty.
 */
export function parsePhone(
	value: string | null | undefined,
	country: string,
	params: PhoneParams
): { code: string; national: string } | null {
	const phone = String( value ?? '' ).trim();
	const digits = phone.replace( /\D/g, '' );

	if ( ! digits ) {
		return null;
	}

	const plus = phone.startsWith( '+' );
	const code = plus
		? codeOf( digits, params )
		: countryCode( country, params );
	let national = stripTrunk(
		plus ? digits.slice( code.length ) : digits,
		code
	);

	// Brazil's code typed without the plus. No national number is longer
	// than eleven digits.
	if (
		BRAZIL === code &&
		national.length > 11 &&
		national.startsWith( BRAZIL )
	) {
		national = national.slice( 2 ).replace( /^0+/, '' );
	}

	return { code, national };
}

/**
 * Whether a phone is a complete number.
 *
 * @param value   Phone as typed.
 * @param country Country of the address.
 * @param params  Phone parameters.
 * @return True when complete.
 */
export function isPhoneNumber(
	value: string | null | undefined,
	country: string,
	params: PhoneParams
): boolean {
	const parsed = parsePhone( value, country, params );

	if ( ! parsed?.code ) {
		return false;
	}

	if ( BRAZIL === parsed.code ) {
		return /^[1-9]{2}9?\d{8}$/.test( parsed.national );
	}

	const length = parsed.code.length + parsed.national.length;

	return length >= 8 && length <= MAX_DIGITS;
}

/**
 * The national part of a foreign phone, keeping the customer's spacing.
 *
 * @param value Phone as typed.
 * @param code  Its calling code.
 * @return National part.
 */
function restOf( value: string, code: string ): string {
	let rest = value;

	if ( rest.startsWith( '+' ) ) {
		rest = rest.replace(
			new RegExp( `^\\+\\D*${ code.split( '' ).join( '\\D*' ) }` ),
			''
		);
	}

	// A trunk prefix written as (0), as in +44 (0)20.
	rest = rest.replace( /[^\d\s().-]/g, '' ).replace( '(0)', '' );

	if ( ! KEEP_ZERO.includes( code ) ) {
		rest = rest.replace( /^([\s.-]*\(?)0+/, '$1' );
	}

	// The end is left alone, or a space could never be typed.
	rest = rest.replace( /\s+/g, ' ' ).replace( /^[\s.-]+/, '' );

	let allowed = MAX_DIGITS - code.length;

	return rest.replace( /\d/g, ( digit ) => ( allowed-- > 0 ? digit : '' ) );
}

/**
 * Format a phone as it is typed.
 *
 * @param value   Phone as typed.
 * @param country Country of the address.
 * @param params  Phone parameters.
 * @return Formatted phone.
 */
export function formatPhoneNumber(
	value: string | null | undefined,
	country: string,
	params: PhoneParams
): string {
	const phone = String( value ?? '' ).replace( /^\s+/, '' );
	const plus = phone.startsWith( '+' );
	const digits = phone.replace( /\D/g, '' );

	if ( ! digits ) {
		return plus ? '+' : '';
	}

	const parsed = parsePhone( phone, country, params );

	// Still typing the calling code, or a country without one.
	if ( ! parsed?.code ) {
		return plus
			? `+${ digits.slice( 0, MAX_DIGITS ) }`
			: phone.replace( /[^\d\s().-]/g, '' );
	}

	const { code, national } = parsed;

	if ( BRAZIL === code ) {
		const local = formatBrazilian( national );

		if ( ! local ) {
			return plus ? `+${ BRAZIL }` : '';
		}

		return 'international' !== params.style && isBrazil( country )
			? local
			: `+${ BRAZIL } ${ local }`;
	}

	const rest = restOf( phone, code );

	return rest ? `+${ code } ${ rest }` : `+${ code }`;
}

/**
 * Carry a phone over to another address country.
 *
 * A number without a plus was typed for the previous country, so it keeps
 * that country's code rather than being read as a number of the new one.
 *
 * @param value  Phone.
 * @param from   Previous country.
 * @param to     New country.
 * @param params Phone parameters.
 * @return Phone for the new country.
 */
export function rebasePhone(
	value: string | null | undefined,
	from: string,
	to: string,
	params: PhoneParams
): string {
	const phone = String( value ?? '' ).trim();
	const code = countryCode( from, params );

	if ( ! phone || phone.startsWith( '+' ) || ! code ) {
		return formatPhoneNumber( phone, to, params );
	}

	return formatPhoneNumber( `+${ code } ${ phone }`, to, params );
}

const defaultWriter: PhoneWriter = ( input, value ) => {
	input.value = value;
};

/**
 * Rewrite a phone input, keeping the caret before the same digits.
 *
 * The calling code can be added or dropped at the start, so the caret is
 * counted from the end.
 *
 * @param input Phone input.
 * @param next  New value.
 * @param write Writes a value into the input.
 */
export function rewritePhone(
	input: HTMLInputElement,
	next: string,
	write: PhoneWriter = defaultWriter
): void {
	const value = input.value;

	if ( value === next ) {
		return;
	}

	let caret = value.length;

	try {
		caret = input.selectionStart ?? value.length;
	} catch {
		// Selection is unavailable for this input type.
	}

	const after = value.slice( caret ).replace( /\D/g, '' ).length;

	write( input, next );

	if ( input.ownerDocument.activeElement !== input ) {
		return;
	}

	let offset = next.length;
	let seen = 0;

	while ( offset > 0 && seen < after ) {
		if ( /\d/.test( next.charAt( offset - 1 ) ) ) {
			seen++;
		}
		offset--;
	}

	try {
		input.setSelectionRange( offset, offset );
	} catch {
		// Selection is unavailable for this input type.
	}
}

/**
 * Format a phone input as it is typed.
 *
 * @param input   Phone input.
 * @param country Country of its address.
 * @param params  Phone parameters.
 * @return Stops formatting.
 */
export function bindPhone(
	input: HTMLInputElement | null | undefined,
	country: () => string,
	params: PhoneParams
): () => void {
	if ( ! input ) {
		return () => {};
	}

	const handler = () =>
		rewritePhone(
			input,
			formatPhoneNumber( input.value, country(), params )
		);

	input.type = 'tel';
	input.addEventListener( 'input', handler );
	handler();

	return () => input.removeEventListener( 'input', handler );
}

const flag = ( country: string ): string =>
	String.fromCodePoint(
		...country
			.toUpperCase()
			.split( '' )
			.map( ( letter ) => 0x1f1e6 + letter.charCodeAt( 0 ) - 65 )
	);

/**
 * The country picker already added for an input.
 *
 * @param input Phone input.
 * @return The picker, or null.
 */
export const pickerFor = ( input: HTMLInputElement ): Element | null =>
	input.id
		? input.ownerDocument.querySelector(
				`.wcbcf-phone-code[data-bmw-for="${ input.id }"]`
		  )
		: null;

// Each bound input's sync, for callers binding again on every render.
const syncs = new WeakMap< HTMLInputElement, () => void >();

export interface PickerOptions {
	/** Country of the input's address. */
	country: () => string;
	/** Phone parameters. */
	params: PhoneParams;
	/** Writes a value into the input. */
	write?: PhoneWriter;
}

/**
 * Add a calling code picker inside a phone input.
 *
 * The phone itself stays the only value: the picker shows the country of the
 * code it holds, or of the address while it is empty, and picking a country
 * rewrites the code.
 *
 * @param input   Phone input.
 * @param options Picker options.
 * @return The picker's sync, to call when the value or address changes.
 */
export function bindPhonePicker(
	input: HTMLInputElement | null | undefined,
	options: PickerOptions
): () => void {
	const { params, country } = options;
	const write = options.write ?? defaultWriter;

	if ( ! input || 'yes' !== params.picker ) {
		return () => {};
	}

	if ( input.dataset.bmwPhonePicker ) {
		return syncs.get( input ) ?? ( () => {} );
	}

	input.dataset.bmwPhonePicker = '1';
	pickerFor( input )?.remove();

	const doc = input.ownerDocument;
	const codes = params.codes ?? {};
	const element = doc.createElement( 'span' );
	const label = doc.createElement( 'span' );
	const select = doc.createElement( 'select' );

	element.className = 'wcbcf-phone-code';
	label.className = 'wcbcf-phone-code-label';
	label.setAttribute( 'aria-hidden', 'true' );
	select.className = 'wcbcf-phone-code-select';
	select.setAttribute(
		'aria-label',
		__( 'Country code', 'woocommerce-extra-checkout-fields-for-brazil' )
	);

	if ( input.id ) {
		element.dataset.bmwFor = input.id;
	}

	// Brazil first, since nearly every customer picks it.
	Object.entries( params.countries ?? {} )
		.sort( ( [ a ], [ b ] ) => Number( 'BR' === b ) - Number( 'BR' === a ) )
		.forEach( ( [ key, name ] ) => {
			if ( codes[ key ] ) {
				select.add(
					new Option( `${ name } (+${ codes[ key ] })`, key )
				);
			}
		} );

	element.append( label, select );
	input.insertAdjacentElement( 'beforebegin', element );

	const show = ( picked: string ) => {
		select.value = picked;

		// The field shows the code itself whenever it holds one.
		label.textContent = flag( picked );

		// Lined up with the input, since the block checkout puts the field's
		// error below it in the same container.
		element.style.top = `${ input.offsetTop }px`;
		element.style.height = `${ input.offsetHeight }px`;
		element.style.fontSize =
			input.ownerDocument.defaultView?.getComputedStyle( input )
				.fontSize ?? '';

		// The list opens from the select's box, so it spans the whole field
		// like any other dropdown, and the flag opens it.
		if ( anchored ) {
			select.style.left = `${ input.offsetLeft - element.offsetLeft }px`;
			select.style.width = `${ input.offsetWidth }px`;
		}
	};

	// Without showPicker the select has to take the click itself, and its
	// list opens from the flag.
	const anchored =
		'function' ===
		typeof ( select as { showPicker?: () => void } ).showPicker;

	if ( anchored ) {
		element.classList.add( 'is-anchored' );
		element.addEventListener( 'click', () => {
			try {
				select.showPicker();
			} catch {
				select.focus();
			}
		} );
	}

	function sync() {
		const address = country() || 'BR';
		const code =
			parsePhone( input!.value, address, params )?.code ||
			countryCode( address, params );

		if ( ! code ) {
			show( address );

			return;
		}

		const candidates = [ select.value, address, params.primary?.[ code ] ];
		const picked =
			candidates.find(
				( candidate ) => candidate && codes[ candidate ] === code
			) ??
			Object.keys( codes ).find( ( key ) => codes[ key ] === code ) ??
			address;

		show( picked );
	}

	select.addEventListener( 'change', () => {
		const code = codes[ select.value ] ?? '';
		const address = country();
		const national = parsePhone( input.value, address, params )?.national;
		const next = national
			? formatPhoneNumber( `+${ code } ${ national }`, address, params )
			: formatPhoneNumber( `+${ code }`, address, params );

		// Brazil on a Brazilian address needs no code, and one would be
		// left alone in the field.
		const bare =
			`+${ BRAZIL }` === next &&
			'international' !== params.style &&
			isBrazil( address );

		write( input, '+' === next || bare ? '' : next );
		show( select.value );
		input.focus();
	} );

	input.addEventListener( 'input', () => sync() );
	syncs.set( input, sync );
	sync();

	return sync;
}
