/**
 * Partly hidden documents, for showing them back to the customer.
 */

const ALPHANUMERIC = /[a-z0-9]/i;

/**
 * Hide the characters of a document outside a range, keeping its punctuation.
 *
 * @param value Document as stored.
 * @param from  First character left visible, counting letters and digits.
 * @param to    Character the visible range stops before.
 * @return Document with the hidden characters as asterisks.
 */
function obscure( value: string, from: number, to: number ): string {
	let index = -1;

	return Array.from( value, ( character ) => {
		if ( ! ALPHANUMERIC.test( character ) ) {
			return character;
		}

		index++;

		return index >= from && index < to ? character : '*';
	} ).join( '' );
}

/**
 * A CPF as it is usually shown in public, such as ***.444.777-**.
 *
 * @param value CPF.
 * @return CPF showing its middle six digits.
 */
export function obscureCpf( value: string ): string {
	return obscure( value, 3, 9 );
}

/**
 * An RG hidden as a CPF is: the leading digits and the check digit.
 *
 * RGs have no national format, so a short one is hidden whole.
 *
 * @param value RG.
 * @return RG showing its middle.
 */
export function obscureRg( value: string ): string {
	const length = Array.from( value ).filter( ( character ) =>
		ALPHANUMERIC.test( character )
	).length;

	return length < 5
		? obscure( value, 0, 0 )
		: obscure( value, 2, length - 1 );
}
