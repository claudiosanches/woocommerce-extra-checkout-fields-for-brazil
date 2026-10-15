/**
 * @jest-environment jsdom
 */

import {
	bindPhone,
	bindPhonePicker,
	formatPhoneNumber,
	isPhoneNumber,
	rebasePhone,
} from '../../assets/js/shared/phone';

const PARAMS = {
	codes: { BR: '55', US: '1', CA: '1', GB: '44', IT: '39', PT: '351' },
	primary: { 1: 'US', 44: 'GB' },
	style: 'national',
	picker: 'yes',
	countries: {
		BR: 'Brazil',
		CA: 'Canada',
		IT: 'Italy',
		PT: 'Portugal',
		GB: 'United Kingdom',
		US: 'United States',
	},
};

const INTERNATIONAL = { ...PARAMS, style: 'international' };

describe( 'formatPhoneNumber', () => {
	it.each( [
		[ '11987654321', 'BR', '(11) 98765-4321' ],
		[ '1133334444', 'BR', '(11) 3333-4444' ],
		[ '+55 11 98765-4321', 'BR', '(11) 98765-4321' ],
		[ '5511987654321', 'BR', '(11) 98765-4321' ],
		[ '011987654321', 'BR', '(11) 98765-4321' ],
		[ '', 'BR', '' ],
		[ '11987654321', '', '(11) 98765-4321' ],
	] )( 'writes %s on a Brazilian address as %s', ( value, country, out ) => {
		expect( formatPhoneNumber( value, country, PARAMS ) ).toBe( out );
	} );

	it( 'keeps the code while it is being typed', () => {
		expect( formatPhoneNumber( '+', 'BR', PARAMS ) ).toBe( '+' );
		expect( formatPhoneNumber( '+5', 'BR', PARAMS ) ).toBe( '+5' );
		expect( formatPhoneNumber( '+55', 'BR', PARAMS ) ).toBe( '+55' );
		expect( formatPhoneNumber( '+351', 'BR', PARAMS ) ).toBe( '+351' );
	} );

	it( 'writes the code for the store that asks for it', () => {
		expect( formatPhoneNumber( '11987654321', 'BR', INTERNATIONAL ) ).toBe(
			'+55 (11) 98765-4321'
		);
	} );

	it( 'keeps a Brazilian number on a foreign address recognisable', () => {
		expect( formatPhoneNumber( '+5511987654321', 'US', PARAMS ) ).toBe(
			'+55 (11) 98765-4321'
		);
	} );

	it.each( [
		[ '+12125551234', 'BR', '+1 2125551234' ],
		[ '+1 212 555 1234', 'BR', '+1 212 555 1234' ],
		[ '(212) 555-1234', 'US', '+1 (212) 555-1234' ],
		[ '2', 'US', '+1 2' ],
		[ '+44 (0)20 7946 0958', 'GB', '+44 20 7946 0958' ],
		[ '020 7946 0958', 'GB', '+44 20 7946 0958' ],
		[ '+39 06 1234 5678', 'IT', '+39 06 1234 5678' ],
		[ '+44 20 ', 'BR', '+44 20 ' ],
		[ '+1 2125551234567890', 'BR', '+1 21255512345678' ],
	] )( 'writes %s for %s as %s', ( value, country, out ) => {
		expect( formatPhoneNumber( value, country, PARAMS ) ).toBe( out );
	} );
} );

describe( 'rebasePhone', () => {
	it( 'keeps the previous country for a number without a code', () => {
		expect( rebasePhone( '(11) 98765-4321', 'BR', 'US', PARAMS ) ).toBe(
			'+55 (11) 98765-4321'
		);
	} );

	it( 'drops the code again back in Brazil', () => {
		expect( rebasePhone( '+55 (11) 98765-4321', 'US', 'BR', PARAMS ) ).toBe(
			'(11) 98765-4321'
		);
	} );
} );

describe( 'isPhoneNumber', () => {
	it.each( [
		[ '(11) 98765-4321', 'BR', true ],
		[ '(11) 3333-4444', 'BR', true ],
		[ '(11) 8765-432', 'BR', false ],
		[ '(11) 88765-4321', 'BR', false ],
		[ '+1 212 555 1234', 'BR', true ],
		[ '+44 123', 'BR', false ],
		[ '+999 12345678', 'BR', false ],
		[ '', 'BR', false ],
	] )( 'judges %s on %s', ( value, country, valid ) => {
		expect( isPhoneNumber( value, country, PARAMS ) ).toBe( valid );
	} );
} );

describe( 'bindPhone', () => {
	it( 'suggests the code of a foreign address with the caret kept', () => {
		document.body.innerHTML = '<input id="phone" />';
		const input = document.getElementById( 'phone' );
		bindPhone( input, () => 'US', PARAMS );
		input.focus();

		input.value = '21';
		input.setSelectionRange( 2, 2 );
		input.dispatchEvent( new Event( 'input' ) );

		expect( input.value ).toBe( '+1 21' );
		expect( input.selectionStart ).toBe( 5 );
		expect( input.type ).toBe( 'tel' );
	} );
} );

describe( 'bindPhonePicker', () => {
	const setup = ( value, country = 'BR' ) => {
		document.body.innerHTML = `<span class="woocommerce-input-wrapper"><input id="phone" value="${ value }" /></span>`;
		const input = document.getElementById( 'phone' );
		const sync = bindPhonePicker( input, {
			country: () => country,
			params: PARAMS,
		} );

		return {
			input,
			sync,
			select: document.querySelector( '.wcbcf-phone-code-select' ),
			label: document.querySelector( '.wcbcf-phone-code-label' ),
		};
	};

	it( 'shows the address country for an empty field', () => {
		const { select, label } = setup( '', 'PT' );

		expect( select.value ).toBe( 'PT' );
		expect( label.textContent ).toBe( '🇵🇹' );
		expect( select.getAttribute( 'aria-label' ) ).toBe( 'Country code' );
	} );

	it( 'follows the code typed into the field', () => {
		const { input, select } = setup( '' );

		input.value = '+44 20';
		input.dispatchEvent( new Event( 'input' ) );

		expect( select.value ).toBe( 'GB' );
	} );

	it( 'prefers the address country among those sharing a code', () => {
		const { select } = setup( '+1 416 555 0100', 'CA' );

		expect( select.value ).toBe( 'CA' );
	} );

	it( 'rewrites the code when another country is picked', () => {
		const { input, select } = setup( '(11) 98765-4321' );

		select.value = 'US';
		select.dispatchEvent( new Event( 'change' ) );

		expect( input.value ).toBe( '+1 11987654321' );
	} );

	it( 'starts an empty field with the picked code', () => {
		const { input, select } = setup( '' );

		select.value = 'GB';
		select.dispatchEvent( new Event( 'change' ) );
		expect( input.value ).toBe( '+44' );

		select.value = 'BR';
		select.dispatchEvent( new Event( 'change' ) );
		expect( input.value ).toBe( '' );
	} );

	it( 'is left out unless the store offers it', () => {
		document.body.innerHTML = '<input id="phone" />';
		bindPhonePicker( document.getElementById( 'phone' ), {
			country: () => 'BR',
			params: { ...PARAMS, picker: 'no' },
		} );

		expect( document.querySelector( '.wcbcf-phone-code' ) ).toBeNull();
	} );
} );

describe( 'the picker list', () => {
	it( 'starts with Brazil', () => {
		document.body.innerHTML = '<input id="phone" />';
		bindPhonePicker( document.getElementById( 'phone' ), {
			country: () => 'US',
			params: PARAMS,
		} );

		const options = document.querySelectorAll(
			'.wcbcf-phone-code-select option'
		);

		expect( options[ 0 ].value ).toBe( 'BR' );
		expect( options[ 1 ].value ).toBe( 'CA' );
	} );
} );
