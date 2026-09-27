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

import { __ } from '@wordpress/i18n';
import { dispatch, select, subscribe } from '@wordpress/data';
import { CART_STORE_KEY, CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import { getSetting } from '@woocommerce/settings';
import type { Formatter, MaskName } from '../shared/mask';
import { caretIndex, caretOffset, formatCep, formatters } from '../shared/mask';
import { createAutofill } from '../shared/postcode';
import { bindMailcheck } from '../shared/mailcheck';
import { bindIeExempt } from '../shared/ie-exempt';
import { bindNoNumber } from '../shared/no-number';
import { keepDigits } from '../shared/house-number';
import {
	bindPhonePicker,
	formatPhoneNumber,
	pickerFor,
	rebasePhone,
	rewritePhone,
} from '../shared/phone';
import type { PhoneParams } from '../shared/phone';
import { stripCountryFormats } from '../shared/address-format';
import type { CountryFormats } from '../shared/address-format';
import '../../scss/checkout/checkout.scss';

interface BlocksParams {
	namespace?: string;
	maskedinput?: string;
	mailcheck?: string;
	postcodeAutofill?: string;
	postcodeUrl?: string;
	noNumber?: string;
	phone?: PhoneParams;
}

interface CartAddressStore {
	setBillingAddress: ( address: Record< string, string > ) => void;
	setShippingAddress: ( address: Record< string, string > ) => void;
}

interface CustomerData {
	billingAddress?: Record< string, string >;
	shippingAddress?: Record< string, string >;
}

declare global {
	interface Window {
		bmwBlocksParams?: BlocksParams;
	}
}

// The address card is formatted in the browser, before anything this script
// does on the page, so the formats are cleaned up as soon as it runs.
stripCountryFormats(
	getSetting< CountryFormats | undefined >( 'countryData' )
);

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
};

// Core fields only get a Brazilian mask while the address is Brazilian.
const BRAZIL_ONLY_MASKS: Record< string, MaskName > = {
	'billing-postcode': 'cep',
	'shipping-postcode': 'cep',
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

type Group = 'billing' | 'shipping';

const phoneParams: PhoneParams = params.phone || {};

// The cell phone has no address of its own and follows the billing one.
const PHONES: Record< string, Group > = {
	'billing-phone': 'billing',
	'shipping-phone': 'shipping',
	[ field( 'contact', 'cellphone' ) ]: 'billing',
};

/**
 * Country of an address, as the cart holds it.
 *
 * @param group Address group.
 * @return Country code.
 */
function addressCountry( group: Group ): string {
	const data: CustomerData = select( CART_STORE_KEY ).getCustomerData();

	return (
		( 'shipping' === group ? data?.shippingAddress : data?.billingAddress )
			?.country || ''
	);
}

/**
 * Format a phone as it is typed, before React reads the event.
 *
 * @param event Input event.
 */
function handlePhoneInput( event: Event ): void {
	const input = event.target;

	if ( ! ( input instanceof window.HTMLInputElement ) ) {
		return;
	}

	const group = PHONES[ input.id ];

	if ( group ) {
		rewritePhone(
			input,
			formatPhoneNumber(
				input.value,
				addressCountry( group ),
				phoneParams
			),
			( target, value ) => NATIVE_VALUE_SETTER.call( target, value )
		);
	}
}

function setupPhonePickers(): void {
	Object.entries( PHONES ).forEach( ( [ id, group ] ) => {
		const input = inputById( id );

		if ( ! input ) {
			// React leaves the picker behind when it unmounts the field.
			document
				.querySelectorAll( `.wcbcf-phone-code[data-bmw-for="${ id }"]` )
				.forEach( ( element ) => element.remove() );

			return;
		}

		if ( ! pickerFor( input ) ) {
			delete input.dataset.bmwPhonePicker;
		}

		bindPhonePicker( input, {
			country: () => addressCountry( group ),
			params: phoneParams,
			write: writeControlled,
		} );
	} );
}

const countries: Partial< Record< Group, string > > = {};

/**
 * Carry the phones over when an address changes country, so a number typed
 * without a code keeps the country it was typed for.
 */
function followAddressCountries(): void {
	( [ 'billing', 'shipping' ] as Group[] ).forEach( ( group ) => {
		const previous = countries[ group ];
		const next = addressCountry( group );

		countries[ group ] = next;

		if ( undefined === previous || previous === next ) {
			return;
		}

		// Written once the form has rendered the new country. The address
		// form sends its whole address with each change, so a phone written
		// now would carry the previous country back with it.
		window.setTimeout( () => rebasePhones( group, previous, next ) );
	} );
}

/**
 * Carry an address's phones over to its new country.
 *
 * @param group    Address group.
 * @param previous Previous country.
 * @param next     New country.
 */
function rebasePhones( group: Group, previous: string, next: string ): void {
	Object.entries( PHONES ).forEach( ( [ id, phoneGroup ] ) => {
		const input = inputById( id );

		if ( ! input || phoneGroup !== group ) {
			return;
		}

		if ( 'yes' === params.maskedinput ) {
			const value = rebasePhone(
				input.value,
				previous,
				next,
				phoneParams
			);

			if ( value !== input.value ) {
				writeControlled( input, value );
			}
		}

		bindPhonePicker( input, {
			country: () => addressCountry( group ),
			params: phoneParams,
			write: writeControlled,
		} )();
	} );
}

const autofills: Partial< Record< Group, () => void > > = {};

/**
 * Address autofill for one address form.
 *
 * The values go through the cart store rather than the inputs, since the
 * state is a select and the neighborhood an additional field. Writing to the
 * store skips the form's own change handler, which is what copies shipping
 * into billing while "Use same address for billing" is ticked, so that copy
 * is made here too.
 *
 * @param group Address group.
 * @return Call whenever the CEP may have changed.
 */
function autofillFor( group: Group ): () => void {
	const neighborhood = `${ namespace }/neighborhood`;
	const number = `${ namespace }/number`;
	const address = (): Record< string, string > => {
		const data: CustomerData = select( CART_STORE_KEY ).getCustomerData();

		return (
			( 'shipping' === group
				? data?.shippingAddress
				: data?.billingAddress ) || {}
		);
	};

	return createAutofill( {
		url: params.postcodeUrl || '',
		postcode: () => {
			const id = `${ group }-postcode`;

			return 'BR' === countryFor( id )
				? inputById( id )?.value || ''
				: '';
		},
		read: () => {
			const current = address();

			return {
				address_1: current.address_1 || '',
				address_2: current.address_2 || '',
				number: current[ number ] || '',
				neighborhood: current[ neighborhood ] || '',
				city: current.city || '',
				state: current.state || '',
			};
		},
		write: ( values ) => {
			const store = dispatch( CART_STORE_KEY ) as CartAddressStore;

			const {
				neighborhood: neighborhoodValue,
				number: numberValue,
				...rest
			} = values;
			const update: Record< string, string > = {
				...rest,
				postcode: formatCep(
					inputById( `${ group }-postcode` )?.value
				),
			};

			if ( undefined !== neighborhoodValue ) {
				update[ neighborhood ] = neighborhoodValue;
			}

			if ( undefined !== numberValue ) {
				update[ number ] = numberValue;
			}

			if ( 'billing' === group ) {
				store.setBillingAddress( update );

				return;
			}

			store.setShippingAddress( update );

			if ( select( CHECKOUT_STORE_KEY ).getUseShippingAsBilling() ) {
				store.setBillingAddress( { ...address(), ...update } );
			}
		},
	} );
}

/**
 * Fill an address from its CEP once the CEP is complete.
 *
 * @param event Input event.
 */
function handleAutofill( event: Event ): void {
	const input = event.target;

	if ( ! ( input instanceof window.HTMLInputElement ) ) {
		return;
	}

	const group = /^(billing|shipping)-postcode$/.exec( input.id )?.[ 1 ] as
		| Group
		| undefined;

	if ( ! group ) {
		return;
	}

	autofills[ group ] ??= autofillFor( group );
	autofills[ group ]();
}

function setupIeExempt(): void {
	const id = field( 'contact', 'ie' );
	const input = inputById( id );

	if ( ! input ) {
		// Switching person type unmounts the field, and React leaves the
		// checkbox this script added behind.
		document
			.querySelectorAll( `.wcbcf-ie-exempt[data-bmw-for="${ id }"]` )
			.forEach( ( element ) => element.remove() );

		return;
	}

	bindIeExempt( input, { write: writeControlled } );
}

function setupNoNumber(): void {
	( [ 'billing', 'shipping' ] as Group[] ).forEach( ( group ) => {
		const id = field( group, 'number' );
		const input = inputById( id );

		if ( ! input ) {
			// Another country unmounts the field, leaving the toggle behind.
			document
				.querySelectorAll( `.wcbcf-no-number[data-bmw-for="${ id }"]` )
				.forEach( ( element ) => element.remove() );

			return;
		}

		input.inputMode = 'numeric';
		bindNoNumber( input, params.noNumber, { write: writeControlled } );
	} );
}

/**
 * Take digits only in the Number fields, before React reads the event.
 *
 * @param event Input event.
 */
function handleNumberInput( event: Event ): void {
	const input = event.target;

	if (
		input instanceof window.HTMLInputElement &&
		( field( 'billing', 'number' ) === input.id ||
			field( 'shipping', 'number' ) === input.id )
	) {
		keepDigits( input, params.noNumber, ( target, value ) =>
			NATIVE_VALUE_SETTER.call( target, value )
		);
	}
}

const CUSTOMER_DETAILS_CLASS = 'wcbcf-customer-details-title';

/**
 * Head the documents and personal details in the contact step, which
 * WooCommerce offers extensions as the only place for them.
 *
 * WooCommerce has no heading of its own to give here, so one is placed
 * before the first of them, and moved or dropped as they come and go.
 */
function setupCustomerDetails(): void {
	const form = document
		.getElementById( 'email' )
		?.closest( '.wc-block-components-address-form' );

	if ( ! form ) {
		return;
	}

	const first = Array.from( form.children ).find(
		( child ) =>
			! child.classList.contains( CUSTOMER_DETAILS_CLASS ) &&
			/csbmw-(?!cellphone)/.test( child.className )
	);
	let heading = form.querySelector( `:scope > .${ CUSTOMER_DETAILS_CLASS }` );

	if ( ! first ) {
		heading?.remove();

		return;
	}

	if ( heading?.nextElementSibling === first ) {
		return;
	}

	if ( ! heading ) {
		// Drawn as WooCommerce's own step titles.
		heading = document.createElement( 'h2' );
		heading.className = `wc-block-components-title wc-block-components-checkout-step__title ${ CUSTOMER_DETAILS_CLASS }`;
		heading.textContent = __(
			'Customer details',
			'woocommerce-extra-checkout-fields-for-brazil'
		);
	}

	form.insertBefore( heading, first );
}

function setupMailcheck(): void {
	if ( 'yes' !== params.mailcheck ) {
		return;
	}

	const email = inputById( 'email' );

	if ( email && ! email.dataset.bmwMailcheck ) {
		email.dataset.bmwMailcheck = '1';
		bindMailcheck( email );
	}
}

function init(): void {
	if ( 'yes' === params.maskedinput ) {
		document.addEventListener( 'input', handleInput, true );
		document.addEventListener( 'input', handlePhoneInput, true );
	}

	followAddressCountries();
	subscribe( followAddressCountries, CART_STORE_KEY );

	if ( 'yes' === params.postcodeAutofill ) {
		document.addEventListener( 'input', handleAutofill );
	}

	document.addEventListener( 'input', handleNumberInput, true );

	setupIeExempt();
	setupNoNumber();
	setupPhonePickers();
	setupCustomerDetails();
	setupMailcheck();

	// The contact block mounts after the first paint and can remount, so keep
	// watching rather than binding once.
	new window.MutationObserver( () => {
		setupIeExempt();
		setupNoNumber();
		setupPhonePickers();
		setupCustomerDetails();
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
