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

	const unbind = bindIeExempt( input );

	return { checkbox: control(), input, unbind };
};

// A real checkbox on the block checkout, a button on the classic one.
const control = () =>
	document.querySelector( '.wcbcf-ie-exempt input, button.wcbcf-ie-exempt' );

const isChecked = ( checkbox ) =>
	'INPUT' === checkbox.tagName
		? checkbox.checked
		: 'true' === checkbox.getAttribute( 'aria-checked' );

const toggle = ( checkbox, checked ) => {
	if ( isChecked( checkbox ) !== checked ) {
		checkbox.click();
	}
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

		expect( isChecked( checkbox ) ).toBe( true );
		expect( input.readOnly ).toBe( true );
	} );

	it( 'leaves a real registration alone', () => {
		const { checkbox, input } = setup( '110042490114' );

		expect( isChecked( checkbox ) ).toBe( false );
		expect( input.readOnly ).toBe( false );
	} );

	it( 'ticks the box when the marker is typed by hand', () => {
		const { checkbox, input } = setup();

		input.value = 'isento';
		input.dispatchEvent( new window.Event( 'input' ) );

		expect( isChecked( checkbox ) ).toBe( true );
	} );

	it( 'unticks the box when the value is edited away', () => {
		const { checkbox, input } = setup( 'ISENTO' );

		// Editing is only possible once the field is writable again.
		toggle( checkbox, false );
		input.value = '110042490114';
		input.dispatchEvent( new window.Event( 'input' ) );

		expect( isChecked( checkbox ) ).toBe( false );
	} );

	it( 'writes through the callback when one is given', () => {
		document.body.innerHTML = '<input id="ie" type="text" />';

		const input = document.getElementById( 'ie' );
		const write = jest.fn();

		bindIeExempt( input, { write } );
		toggle( control(), true );

		expect( write ).toHaveBeenCalledWith( input, EXEMPT_VALUE );
	} );

	it( 'binds only once per input', () => {
		const { input } = setup();

		bindIeExempt( input );

		expect( document.querySelectorAll( '.wcbcf-ie-exempt' ) ).toHaveLength(
			1
		);
	} );

	it( 'sits inside the field on the classic checkout', () => {
		document.body.innerHTML =
			'<p class="form-row validate-required" id="billing_ie_field">' +
			'<span class="woocommerce-input-wrapper"><input id="ie" type="text" /></span></p>';

		bindIeExempt( document.getElementById( 'ie' ) );

		const button = document.querySelector( '.wcbcf-ie-exempt' );

		expect( button.parentElement.className ).toBe(
			'woocommerce-input-wrapper'
		);

		// A checkbox input in the row would be validated as a field of its
		// own, and a button in the form must never submit it.
		expect( button.tagName ).toBe( 'BUTTON' );
		expect( button.type ).toBe( 'button' );
		expect( button.getAttribute( 'role' ) ).toBe( 'checkbox' );
	} );

	it( 'sits inside the field on the block checkout', () => {
		document.body.innerHTML =
			'<div class="wc-block-components-text-input"><input id="ie" type="text" /><label for="ie">IE</label></div>';

		bindIeExempt( document.getElementById( 'ie' ) );

		const wrapper = document.getElementById( 'ie' ).nextElementSibling;

		// WooCommerce's own checkbox, so its styles draw it.
		expect( wrapper.className ).toBe(
			'wc-block-components-checkbox wcbcf-ie-exempt'
		);
		expect(
			wrapper
				.querySelector( 'input' )
				.classList.contains( 'wc-block-components-checkbox__input' )
		).toBe( true );
	} );

	it( 'is named for screen readers', () => {
		const { checkbox } = setup();

		expect( checkbox.getAttribute( 'aria-label' ) ).toBe(
			'Exempt from State Registration'
		);
	} );

	it( 'removes a toggle a re-render left behind', () => {
		document.body.innerHTML =
			'<div class="wc-block-components-text-input">' +
			'<input id="ie" type="text" />' +
			'<button class="wcbcf-ie-exempt" data-bmw-for="ie"></button></div>';

		bindIeExempt( document.getElementById( 'ie' ) );

		expect( document.querySelectorAll( '.wcbcf-ie-exempt' ) ).toHaveLength(
			1
		);
	} );

	it( 'marks the checkbox with the field it belongs to', () => {
		setup();

		expect(
			document.querySelector( '.wcbcf-ie-exempt' ).dataset.bmwFor
		).toBe( 'ie' );
	} );

	it( 'removes the checkbox when unbound', () => {
		const { unbind } = setup();

		unbind();

		expect( document.querySelector( '.wcbcf-ie-exempt' ) ).toBeNull();
	} );
} );
