/**
 * @jest-environment jsdom
 */

import {
	stripCountryFormats,
	stripCustomTokens,
} from '../../assets/js/shared/address-format';

describe( 'stripCustomTokens', () => {
	it( 'removes the tokens only the PHP formatter can replace', () => {
		expect(
			stripCustomTokens(
				'{name}\n{address_1}, {number}\n{address_2}\n{neighborhood}\n{city}'
			)
		).toBe( '{name}\n{address_1}, \n{address_2}\n\n{city}' );
	} );

	it( 'leaves a format without them alone', () => {
		const format = '{name}\n{address_1}\n{city}\n{country}';

		expect( stripCustomTokens( format ) ).toBe( format );
	} );
} );

describe( 'stripCountryFormats', () => {
	it( 'cleans every country that carries a token', () => {
		const countries = {
			BR: { format: '{address_1}, {number}\n{neighborhood}\n{city}' },
			US: { format: '{address_1}\n{city}' },
		};

		stripCountryFormats( countries );

		expect( countries.BR.format ).toBe( '{address_1}, \n\n{city}' );
		expect( countries.US.format ).toBe( '{address_1}\n{city}' );
	} );

	it( 'accepts a country with no format and no country data at all', () => {
		const countries = { BR: {} };

		expect( () => stripCountryFormats( countries ) ).not.toThrow();
		expect( () => stripCountryFormats( undefined ) ).not.toThrow();
	} );
} );
