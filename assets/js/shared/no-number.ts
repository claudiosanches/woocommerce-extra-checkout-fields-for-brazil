/**
 * "No number" toggle for the address Number field.
 *
 * An address without a house number is written with a conventional value,
 * S/N unless the store sets another.
 */

import { __ } from '@wordpress/i18n';
import { bindValueToggle } from './value-toggle';

/**
 * Add a No number toggle inside a Number input.
 *
 * @param input         Number input.
 * @param value         What the toggle writes, empty when the store does not offer it.
 * @param options       Options.
 * @param options.write Writes a value into the input.
 * @return Removes the toggle.
 */
export function bindNoNumber(
	input: HTMLInputElement | null | undefined,
	value: string | undefined,
	{
		write,
	}: { write?: ( input: HTMLInputElement, value: string ) => void } = {}
): () => void {
	if ( ! value ) {
		return () => {};
	}

	return bindValueToggle( input, {
		value,
		name: 'no-number',
		label: __(
			'No number',
			'woocommerce-extra-checkout-fields-for-brazil'
		),
		text: __( 'No number', 'woocommerce-extra-checkout-fields-for-brazil' ),
		write,
	} );
}
