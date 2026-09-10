/**
 * @jest-environment jsdom
 */

import {
	EXEMPT_VALUE,
	bindIeExempt,
	isExempt,
} from '../../assets/js/shared/ie-exempt';

const setup = ( value = '' ) => {
	document.body.innerHTML = '<input id="ie" type="text" />';

	const input = document.getElementById( 'ie' );
	input.value = value;

	const unbind = bindIeExempt( input, { label: 'Exempt' } );
	const checkbox = document.querySelector( '.wcbcf-ie-exempt-input' );

	return { checkbox, input, unbind };
};

const toggle = ( checkbox, checked ) => {
	checkbox.checked = checked;
	checkbox.dispatchEvent( new window.Event( 'change' ) );
};

describe( 'isExempt', () => {
	it.each( [ 'ISENTO', 'isento', ' Isento ' ] )(
		'accepts %s whatever the case',
		( value ) => {
			expect( isExempt( value ) ).toBe( true );
		}
	);

	it.each( [ '', '  ', '110042490114', 'ISENTOS', null, undefined ] )(
		'rejects %s',
		( value ) => {
			expect( isExempt( value ) ).toBe( false );
		}
	);
} );

describe( 'bindIeExempt', () => {
	it( 'writes the exemption marker when checked', () => {
		const { checkbox, input } = setup();

		toggle( checkbox, true );

		expect( input.value ).toBe( EXEMPT_VALUE );
		expect( input.readOnly ).toBe( true );
	} );

	it( 'clears the field when unchecked', () => {
		const { checkbox, input } = setup();

		toggle( checkbox, true );
		toggle( checkbox, false );

		expect( input.value ).toBe( '' );
		expect( input.readOnly ).toBe( false );
	} );

	it( 'starts checked for a value carried over from a previous order', () => {
		const { checkbox, input } = setup( 'ISENTO' );

		expect( checkbox.checked ).toBe( true );
		expect( input.readOnly ).toBe( true );
	} );

	it( 'leaves a real registration alone', () => {
		const { checkbox, input } = setup( '110042490114' );

		expect( checkbox.checked ).toBe( false );
		expect( input.readOnly ).toBe( false );
	} );

	it( 'ticks the box when the marker is typed by hand', () => {
		const { checkbox, input } = setup();

		input.value = 'isento';
		input.dispatchEvent( new window.Event( 'input' ) );

		expect( checkbox.checked ).toBe( true );
	} );

	it( 'unticks the box when the value is edited away', () => {
		const { checkbox, input } = setup( 'ISENTO' );

		// Editing is only possible once the field is writable again.
		toggle( checkbox, false );
		input.value = '110042490114';
		input.dispatchEvent( new window.Event( 'input' ) );

		expect( checkbox.checked ).toBe( false );
	} );

	it( 'writes through the callback when one is given', () => {
		document.body.innerHTML = '<input id="ie" type="text" />';

		const input = document.getElementById( 'ie' );
		const write = jest.fn();

		bindIeExempt( input, { label: 'Exempt', write } );
		toggle( document.querySelector( '.wcbcf-ie-exempt-input' ), true );

		expect( write ).toHaveBeenCalledWith( input, EXEMPT_VALUE );
	} );

	it( 'binds only once per input', () => {
		const { input } = setup();

		bindIeExempt( input, { label: 'Exempt' } );

		expect( document.querySelectorAll( '.wcbcf-ie-exempt' ) ).toHaveLength(
			1
		);
	} );

	it( 'removes the checkbox when unbound', () => {
		const { unbind } = setup();

		unbind();

		expect( document.querySelector( '.wcbcf-ie-exempt' ) ).toBeNull();
	} );
} );
