/**
 * @jest-environment jsdom
 */

import { bindMailcheck } from '../../assets/js/shared/mailcheck';

const suggestFor = ( email ) => {
	document.body.innerHTML = '<div><input id="email" type="email" /></div>';

	const input = document.getElementById( 'email' );
	input.value = email;

	bindMailcheck( input );
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

	it( 'goes after the block checkout field, and reuses its place', () => {
		document.body.innerHTML =
			'<div class="wc-block-components-text-input"><input id="email" type="email" /><label>Email</label></div>';

		const input = document.getElementById( 'email' );
		const wrapper = input.parentElement;

		bindMailcheck( input );
		input.value = 'alguem@gmail.con';
		input.dispatchEvent( new window.Event( 'blur' ) );
		input.value = 'alguem@hotmail.con';
		input.dispatchEvent( new window.Event( 'blur' ) );

		expect( wrapper.nextElementSibling.textContent ).toBe(
			'Did you mean: alguem@hotmail.com?'
		);
		expect(
			document.querySelectorAll( '.wcbcf-mailsuggest' )
		).toHaveLength( 1 );
	} );
} );
