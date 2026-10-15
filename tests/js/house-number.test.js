/**
 * @jest-environment jsdom
 */

import {
	bindHouseNumber,
	formatHouseNumber,
} from '../../assets/js/shared/house-number';

describe( 'formatHouseNumber', () => {
	it.each( [
		[ '1578', '1578' ],
		[ '1-12', '112' ],
		[ '12A', '12' ],
		[ ' 42 ', '42' ],
		[ 'S/N', '' ],
		[ '', '' ],
		[ null, '' ],
	] )( 'keeps the digits of %s', ( value, expected ) => {
		expect( formatHouseNumber( value ) ).toBe( expected );
	} );

	it( 'keeps the No number value the store offers', () => {
		expect( formatHouseNumber( 'S/N', 'S/N' ) ).toBe( 'S/N' );
		expect( formatHouseNumber( 'N/A', 'S/N' ) ).toBe( '' );
	} );
} );

describe( 'bindHouseNumber', () => {
	const setup = ( value = '' ) => {
		document.body.innerHTML = '<input id="number" type="text" />';

		const input = document.getElementById( 'number' );
		input.value = value;

		return { input, unbind: bindHouseNumber( input, 'S/N' ) };
	};

	const type = ( input, value ) => {
		input.value = value;
		input.dispatchEvent( new window.Event( 'input' ) );
	};

	it( 'drops what is not a digit as it is typed', () => {
		const { input } = setup();

		type( input, '12-A' );

		expect( input.value ).toBe( '12' );
		expect( input.inputMode ).toBe( 'numeric' );
	} );

	it( 'leaves a saved value for the server to judge', () => {
		const { input } = setup( '1-12' );

		expect( input.value ).toBe( '1-12' );
	} );

	it( 'stops when unbound', () => {
		const { input, unbind } = setup();

		unbind();
		type( input, '12A' );

		expect( input.value ).toBe( '12A' );
	} );
} );
