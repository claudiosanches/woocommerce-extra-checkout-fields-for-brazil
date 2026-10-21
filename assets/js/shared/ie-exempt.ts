/**
 * "Exempt" toggle for the State Registration field.
 *
 * Companies without a state registration have to send the literal word
 * ISENTO on the invoice.
 */

import { __ } from '@wordpress/i18n';
import { bindValueToggle, isValue } from './value-toggle';

export const EXEMPT_VALUE = 'ISENTO';

/**
 * Whether a value is the exemption marker, whatever case it was typed in.
 *
 * @param value Field value.
 * @return True when the value marks an exemption.
 */
export const isExempt = ( value: string | null | undefined ): boolean =>
	isValue( value, EXEMPT_VALUE );

/**
 * Add an exemption toggle inside a State Registration input.
 *
 * @param input         State Registration input.
 * @param options       Options.
 * @param options.write Writes a value into the input.
 * @return Removes the toggle.
 */
export function bindIeExempt(
	input: HTMLInputElement | null | undefined,
	{
		write,
	}: { write?: ( input: HTMLInputElement, value: string ) => void } = {}
): () => void {
	return bindValueToggle( input, {
		value: EXEMPT_VALUE,
		name: 'ie-exempt',
		label: __(
			'Exempt from State Registration',
			'woocommerce-extra-checkout-fields-for-brazil'
		),
		text: __( 'Exempt', 'woocommerce-extra-checkout-fields-for-brazil' ),
		write,
	} );
}
