/**
 * @jest-environment jsdom
 */

import { bindMailcheck } from '../../assets/js/shared/mailcheck';

const suggestFor = ( email ) => {
	document.body.innerHTML = '<div><input id="email" type="email" /></div>';

	const input = document.getElementById( 'email' );
	input.value = email;

	bindMailcheck( input, 'Did you mean: %hint%?' );
	input.dispatchEvent( new window.Event( 'blur' ) );

	return document.querySelector( '.wcbcf-mailsuggest' ).textContent;
};

describe( 'bindMailcheck', () => {
	it.each( [
		'alguem@uol.com.br',
		'alguem@terra.com.br',
		'alguem@bol.com.br',
	] )( 'leaves %s alone', ( email ) => {
		expect( suggestFor( email ) ).toBe( '' );
	} );

	it( 'suggests a Brazilian domain that lost its country code', () => {
		expect( suggestFor( 'alguem@uol.com.b' ) ).toBe(
			'Did you mean: alguem@uol.com.br?'
		);
	} );

	it( 'still catches an ordinary typo', () => {
		expect( suggestFor( 'alguem@gmail.con' ) ).toBe(
			'Did you mean: alguem@gmail.com?'
		);
	} );
} );
