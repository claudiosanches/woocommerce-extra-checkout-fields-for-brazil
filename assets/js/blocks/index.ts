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

import type { Formatter, MaskName } from '../shared/mask';
import { caretIndex, caretOffset, formatters } from '../shared/mask';
import { bindMailcheck } from '../shared/mailcheck';
import { bindIeExempt } from '../shared/ie-exempt';
import '../../scss/blocks/blocks.scss';

interface BlocksParams {
	namespace?: string;
	maskedinput?: string;
	mailcheck?: string;
	suggestText?: string;
	ieExemptLabel?: string;
}

declare global {
	interface Window {
		bmwBlocksParams?: BlocksParams;
	}
}

// The DOM defines this accessor on every input, and going through it is what
// keeps React's value tracker from discarding the rewrite.
const NATIVE_VALUE_SETTER = Object.getOwnPropertyDescriptor(
	window.HTMLInputElement.prototype,
	'value'
)!.set!;

const params: BlocksParams = window.bmwBlocksParams || {};
const namespace = params.namespace || 'csbmw';

// Additional fields render with their id slashes turned into dashes.
const field = ( group: string, key: string ) =>
	`${ group }-${ namespace }-${ key }`;

const MASKS: Record< string, MaskName > = {
	[ field( 'contact', 'cpf' ) ]: 'cpf',
	[ field( 'contact', 'cnpj' ) ]: 'cnpj',
	[ field( 'contact', 'birthdate' ) ]: 'date',
	[ field( 'contact', 'cellphone' ) ]: 'phone',
};

// Core fields only get a Brazilian mask while the address is Brazilian.
const BRAZIL_ONLY_MASKS: Record< string, MaskName > = {
	'billing-postcode': 'cep',
	'shipping-postcode': 'cep',
	'billing-phone': 'phone',
	'shipping-phone': 'phone',
};

const inputById = ( id: string ): HTMLInputElement | null => {
	const element = document.getElementById( id );

	return element instanceof window.HTMLInputElement ? element : null;
};

/**
 * The country currently selected for the address an input belongs to.
 *
 * @param id Input id.
 * @return Country code, or an empty string when unknown.
 */
function countryFor( id: string ): string {
	const group = id.startsWith( 'shipping-' ) ? 'shipping' : 'billing';
	const country = document.getElementById( `${ group }-country` );

	// The block checkout renders the country field as a select or as a
	// combobox input, depending on the WooCommerce version.
	return country instanceof window.HTMLSelectElement ||
		country instanceof window.HTMLInputElement
		? country.value
		: '';
}

/**
 * Formatter that applies to an input, if any.
 *
 * @param input Input being edited.
 * @return Formatter.
 */
function formatterFor( input: HTMLInputElement ): Formatter | null {
	const id = input.id;
	const mask = MASKS[ id ];

	if ( mask ) {
		return formatters[ mask ];
	}

	const brazilOnly = BRAZIL_ONLY_MASKS[ id ];

	if ( brazilOnly && 'BR' === countryFor( id ) ) {
		return formatters[ brazilOnly ];
	}

	return null;
}

/**
 * Reformat an input in place, before React reads the event.
 *
 * @param event Input event.
 */
function handleInput( event: Event ): void {
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

/**
 * Write a value into a React-controlled input.
 *
 * Unlike a keystroke this change has no event of its own, so it has to be
 * announced. There is no race here: nothing else is typing.
 *
 * @param input Input to write to.
 * @param value Value to write.
 */
function writeControlled( input: HTMLInputElement, value: string ): void {
	NATIVE_VALUE_SETTER.call( input, value );
	input.dispatchEvent( new window.Event( 'input', { bubbles: true } ) );
}

function setupIeExempt(): void {
	const input = inputById( field( 'contact', 'ie' ) );

	if ( input ) {
		bindIeExempt( input, {
			label: params.ieExemptLabel,
			write: writeControlled,
		} );
	}
}

function setupMailcheck(): void {
	if ( 'yes' !== params.mailcheck ) {
		return;
	}

	const email = inputById( 'email' );

	if ( email && ! email.dataset.bmwMailcheck ) {
		email.dataset.bmwMailcheck = '1';
		bindMailcheck( email, params.suggestText );
	}
}

function init(): void {
	if ( 'yes' === params.maskedinput ) {
		document.addEventListener( 'input', handleInput, true );
	}

	setupIeExempt();
	setupMailcheck();

	// The contact block mounts after the first paint and can remount, so keep
	// watching rather than binding once.
	new window.MutationObserver( () => {
		setupIeExempt();
		setupMailcheck();
	} ).observe( document.body, {
		childList: true,
		subtree: true,
	} );
}

if ( 'loading' === document.readyState ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
