/**
 * Keeps this plugin's address tokens out of the browser.
 *
 * The Brazilian address format carries `{number}` and `{neighborhood}`, which
 * only the PHP formatter knows how to replace. WooCommerce publishes the same
 * format to the cart and checkout blocks, where the address card is built from
 * a fixed list of core tokens, so both were printed to the customer as they are
 * written here.
 */

export const CUSTOM_TOKENS = [ '{number}', '{neighborhood}' ];

export interface CountryFormats {
	[ country: string ]: { format?: string };
}

/**
 * Remove this plugin's tokens from one address format.
 *
 * What is left behind is a trailing separator or an empty line, both of which
 * WooCommerce's formatter already drops.
 *
 * @param format Country address format.
 * @return The format with no custom token left in it.
 */
export function stripCustomTokens( format: string ): string {
	return CUSTOM_TOKENS.reduce(
		( result, token ) => result.split( token ).join( '' ),
		format
	);
}

/**
 * Strip the tokens from every country format WooCommerce published.
 *
 * @param countries Country data from `wcSettings`.
 */
export function stripCountryFormats( countries?: CountryFormats ): void {
	if ( ! countries ) {
		return;
	}

	Object.values( countries ).forEach( ( country ) => {
		if ( 'string' === typeof country?.format ) {
			country.format = stripCustomTokens( country.format );
		}
	} );
}
