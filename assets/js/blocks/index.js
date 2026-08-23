/**
 * Masking and email suggestions for the checkout block.
 *
 * The block checkout renders React-controlled inputs, so a reformatted value
 * has to go in through the native setter. The rewrite happens in the capture
 * phase of the original `input` event, which is the only point where React
 * has not read the value yet: it picks up the formatted string when the same
 * event reaches its own handler. Dispatching a second event instead would
 * queue a render holding the pre-keystroke value, and a keystroke landing
 * before that render committed would be reverted with it.
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
 * Reformat an input in place, before React reads the event.
 *
 * @param {InputEvent} event Input event.
 */
function handleInput( event ) {
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

	NATIVE_VALUE_SETTER.call( input, nextValue );

	const offset = caretOffset( nextValue, index );

	try {
		input.setSelectionRange( offset, offset );
	} catch {
		// Selection is unavailable for this input type.
	}
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
