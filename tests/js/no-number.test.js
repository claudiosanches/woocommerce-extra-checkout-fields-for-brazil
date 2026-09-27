/**
 * @jest-environment jsdom
 */

import { bindNoNumber } from '../../assets/js/shared/no-number';

const setup = ( noNumber, value = '' ) => {
	document.body.innerHTML =
		'<p class="form-row"><input id="billing_number" type="text" /></p>';

	const input = document.getElementById( 'billing_number' );
	input.value = value;

	return { input, unbind: bindNoNumber( input, noNumber ) };
};

const toggle = () => document.querySelector( 'button.wcbcf-no-number' );

describe( 'bindNoNumber', () => {
	it( 'adds nothing when the store does not offer it', () => {
		setup( '' );

		expect( toggle() ).toBeNull();
	} );

	it.each( [ 'S/N', 'N/A' ] )( 'writes %s when ticked', ( noNumber ) => {
		const { input } = setup( noNumber );

		toggle().click();

		expect( input.value ).toBe( noNumber );
		expect( input.readOnly ).toBe( true );

		toggle().click();

		expect( input.value ).toBe( '' );
		expect( input.readOnly ).toBe( false );
	} );

	it( 'shows a saved value as ticked, whatever its case', () => {
		setup( 'S/N', 's/n' );

		expect( toggle().getAttribute( 'aria-checked' ) ).toBe( 'true' );
	} );

	it( 'leaves a real number alone', () => {
		const { input } = setup( 'S/N', '1578' );

		expect( toggle().getAttribute( 'aria-checked' ) ).toBe( 'false' );
		expect( input.readOnly ).toBe( false );
	} );
} );
