/**
 * @jest-environment jsdom
 */

import {
	EXEMPT_VALUE,
	bindIeExempt,
	isExempt,
	placeIeExempt,
} from '../../assets/js/shared/ie-exempt';

const setup = ( value = '' ) => {
	document.body.innerHTML = '<input id="ie" type="text" />';

	const input = document.getElementById( 'ie' );
	input.value = value;

	const unbind = bindIeExempt( input );
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

		bindIeExempt( input, { write } );
		toggle( document.querySelector( '.wcbcf-ie-exempt-input' ), true );

		expect( write ).toHaveBeenCalledWith( input, EXEMPT_VALUE );
	} );

	it( 'binds only once per input', () => {
		const { input } = setup();

		bindIeExempt( input );

		expect( document.querySelectorAll( '.wcbcf-ie-exempt' ) ).toHaveLength(
			1
		);
	} );

	it( 'goes after the row on the classic checkout', () => {
		document.body.innerHTML =
			'<div class="woocommerce-billing-fields__field-wrapper">' +
			'<p class="form-row" id="billing_ie_field"><input id="ie" type="text" /></p>' +
			'<p class="form-row" id="billing_city_field"></p>' +
			'</div>';

		bindIeExempt( document.getElementById( 'ie' ) );

		// Inside the row, WooCommerce reads the unticked box as an empty
		// required field and marks the whole row invalid.
		expect(
			document.querySelector( '#billing_ie_field .wcbcf-ie-exempt' )
		).toBeNull();
		expect(
			document.getElementById( 'billing_ie_field' ).nextElementSibling
				.className
		).toBe( 'wcbcf-ie-exempt' );
	} );

	it( 'follows the row WooCommerce re-sorted', () => {
		document.body.innerHTML =
			'<div class="woocommerce-billing-fields__field-wrapper">' +
			'<p class="form-row" id="billing_ie_field"><input id="ie" type="text" /></p>' +
			'<p class="form-row" id="billing_city_field"></p>' +
			'</div>';

		const input = document.getElementById( 'ie' );

		bindIeExempt( input );

		// WooCommerce re-appends every row it knows in locale order, which
		// leaves the checkbox where the row used to be.
		const fields = document.querySelector(
			'.woocommerce-billing-fields__field-wrapper'
		);

		fields.append( document.getElementById( 'billing_city_field' ) );
		fields.append( document.getElementById( 'billing_ie_field' ) );

		placeIeExempt( input );

		expect(
			document.getElementById( 'billing_ie_field' ).nextElementSibling
				.className
		).toBe( 'wcbcf-ie-exempt' );
		expect( document.querySelectorAll( '.wcbcf-ie-exempt' ) ).toHaveLength(
			1
		);
	} );

	it( 'goes after the field on the block checkout', () => {
		document.body.innerHTML =
			'<div class="wc-block-components-text-input"><input id="ie" type="text" /></div>';

		bindIeExempt( document.getElementById( 'ie' ) );

		const field = document.querySelector(
			'.wc-block-components-text-input'
		);

		expect( field.nextElementSibling.className ).toBe( 'wcbcf-ie-exempt' );
	} );

	it( 'replaces the checkbox a previous render left behind', () => {
		document.body.innerHTML =
			'<p class="form-row" id="billing_ie_field"><input id="ie" type="text" /></p>' +
			'<label class="wcbcf-ie-exempt"></label>';

		bindIeExempt( document.getElementById( 'ie' ) );

		expect( document.querySelectorAll( '.wcbcf-ie-exempt' ) ).toHaveLength(
			1
		);
	} );

	it( 'removes a checkbox a re-render left elsewhere on the page', () => {
		document.body.innerHTML =
			'<label class="wcbcf-ie-exempt" data-bmw-for="ie"></label>' +
			'<div class="wc-block-components-text-input">' +
			'<input id="ie" type="text" /></div>';

		bindIeExempt( document.getElementById( 'ie' ) );

		const wrappers = document.querySelectorAll( '.wcbcf-ie-exempt' );

		expect( wrappers ).toHaveLength( 1 );
		expect( wrappers[ 0 ].previousElementSibling.className ).toBe(
			'wc-block-components-text-input'
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
