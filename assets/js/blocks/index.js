/**
 * Masking and email suggestions for the checkout block.
 *
 * The block checkout renders React-controlled inputs, so a reformatted value
 * has to go in through the native setter and be announced with an `input`
 * event. Assigning `input.value` alone would be reverted on the next render.
 */

import { caretIndex, caretOffset, formatters } from '../shared/mask';
import { bindMailcheck } from '../shared/mailcheck';
import '../../scss/blocks/blocks.scss';

const NATIVE_VALUE_SETTER = Object.getOwnPropertyDescriptor(
	window.HTMLInputElement.prototype,
	'value'
).set;

const params = window.bmwBlocksParams || {};
const namespace = params.namespace || 'csbmw';

// Additional fields render with their id slashes turned into dashes.
const field = ( group, key ) => `${ group }-${ namespace }-${ key }`;

const MASKS = {
	[ field( 'contact', 'cpf' ) ]: 'cpf',
	[ field( 'contact', 'cnpj' ) ]: 'cnpj',
	[ field( 'contact', 'birthdate' ) ]: 'date',
	[ field( 'contact', 'cellphone' ) ]: 'phone',
};

// Core fields only get a Brazilian mask while the address is Brazilian.
const BRAZIL_ONLY_MASKS = {
	'billing-postcode': 'cep',
	'shipping-postcode': 'cep',
	'billing-phone': 'phone',
	'shipping-phone': 'phone',
};

let applying = false;

/**
 * The country currently selected for the address an input belongs to.
 *
 * @param {string} id Input id.
 * @return {string} Country code, or an empty string when unknown.
 */
function countryFor( id ) {
	const group = id.startsWith( 'shipping-' ) ? 'shipping' : 'billing';
	const select = document.getElementById( `${ group }-country` );

	return select ? select.value : '';
}

/**
 * Formatter that applies to an input, if any.
 *
 * @param {HTMLInputElement} input Input being edited.
 * @return {Function|null} Formatter.
 */
function formatterFor( input ) {
	const id = input.id;

	if ( MASKS[ id ] ) {
		return formatters[ MASKS[ id ] ];
	}

	if ( BRAZIL_ONLY_MASKS[ id ] && 'BR' === countryFor( id ) ) {
		return formatters[ BRAZIL_ONLY_MASKS[ id ] ];
	}

	return null;
}

/**
 * Reformat an input and let React know its value changed.
 *
 * @param {InputEvent} event Input event.
 */
function handleInput( event ) {
	if ( applying ) {
		return;
	}

	const input = event.target;

	if ( ! ( input instanceof window.HTMLInputElement ) ) {
		return;
	}

	const format = formatterFor( input );

	if ( ! format ) {
		return;
	}

	const value = input.value;
	const nextValue = format( value );

	if ( value === nextValue ) {
		return;
	}

	const index = caretIndex( value, input.selectionStart || 0 );

	applying = true;
	NATIVE_VALUE_SETTER.call( input, nextValue );
	input.dispatchEvent( new window.Event( 'input', { bubbles: true } ) );
	applying = false;

	// React restores the caret to the end after re-rendering, so put it back
	// once the render has flushed.
	window.requestAnimationFrame( () => {
		if ( input.ownerDocument.activeElement !== input ) {
			return;
		}

		const offset = caretOffset( input.value, index );

		try {
			input.setSelectionRange( offset, offset );
		} catch {
			// Selection is unavailable for this input type.
		}
	} );
}

function setupMailcheck() {
	const email = document.getElementById( 'email' );

	if ( email && ! email.dataset.bmwMailcheck ) {
		email.dataset.bmwMailcheck = '1';
		bindMailcheck( email, params.suggestText );
	}
}

function init() {
	if ( 'yes' === params.maskedinput ) {
		document.addEventListener( 'input', handleInput, true );
	}

	if ( 'yes' === params.mailcheck ) {
		setupMailcheck();

		// The contact block mounts after the first paint and can remount, so
		// keep watching for the email input rather than binding once.
		new window.MutationObserver( setupMailcheck ).observe( document.body, {
			childList: true,
			subtree: true,
		} );
	}
}

if ( 'loading' === document.readyState ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
