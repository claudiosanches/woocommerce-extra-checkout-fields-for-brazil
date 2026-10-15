/**
 * The address Number field, which takes digits only.
 *
 * Carriers reject numbers such as 1-12 or 12A, so anything else typed or
 * pasted is dropped as it arrives. The No number value is the one exception.
 * A value already saved is left as it is for the server to reject, since
 * stripping it could turn it into another address.
 */

import { isValue } from './value-toggle';

export type ValueWriter = ( input: HTMLInputElement, value: string ) => void;

/**
 * A Number value with everything but digits removed.
 *
 * @param value    Typed value.
 * @param noNumber The No number value, empty when not offered.
 * @return Allowed value.
 */
export const formatHouseNumber = (
	value: string | null | undefined,
	noNumber = ''
): string =>
	noNumber && isValue( value, noNumber )
		? String( value )
		: String( value ?? '' ).replace( /\D/g, '' );

/**
 * Drop what a Number field does not take, keeping the caret after the same
 * digit.
 *
 * @param input    Number input.
 * @param noNumber The No number value, empty when not offered.
 * @param write    Writes a value into the input.
 */
export function keepDigits(
	input: HTMLInputElement,
	noNumber = '',
	write: ValueWriter = ( target, value ) => {
		target.value = value;
	}
): void {
	const value = input.value;
	const nextValue = formatHouseNumber( value, noNumber );

	if ( value === nextValue ) {
		return;
	}

	const caret = formatHouseNumber(
		value.slice( 0, input.selectionStart ?? value.length )
	).length;

	write( input, nextValue );

	try {
		input.setSelectionRange( caret, caret );
	} catch {
		// Selection is unavailable for this input type.
	}
}

/**
 * Take digits only in a Number input.
 *
 * @param input    Number input.
 * @param noNumber The No number value, empty when not offered.
 * @return Stops filtering.
 */
export function bindHouseNumber(
	input: HTMLInputElement | null | undefined,
	noNumber = ''
): () => void {
	if ( ! input ) {
		return () => {};
	}

	input.inputMode = 'numeric';

	const handler = () => keepDigits( input, noNumber );

	input.addEventListener( 'input', handler );

	return () => input.removeEventListener( 'input', handler );
}
