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
import {
	CART_STORE_KEY,
	CHECKOUT_STORE_KEY,
	VALIDATION_STORE_KEY,
} from '@woocommerce/block-data';
import { getSetting } from '@woocommerce/settings';
import type { Formatter, MaskName } from '../shared/mask';
import { caretIndex, caretOffset, formatCep, formatters } from '../shared/mask';
import { createAutofill } from '../shared/postcode';
import { bindMailcheck } from '../shared/mailcheck';
import { bindIeExempt } from '../shared/ie-exempt';
import { bindNoNumber } from '../shared/no-number';
import { keepDigits } from '../shared/house-number';
import { obscureCpf, obscureRg } from '../shared/obscure';
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
const CUSTOMER_DETAILS_CARD_CLASS = 'wcbcf-customer-details-card';

/**
 * Where the customer details card stands: waiting for the form, deciding once
 * it renders, collapsed into the card, or open for the customer to edit.
 */
let customerDetails: 'waiting' | 'deciding' | 'collapsed' | 'editing' =
	'waiting';

const contactForm = (): Element | null | undefined =>
	document
		.getElementById( 'email' )
		?.closest( '.wc-block-components-address-form' );

// The cell phone sits beside the email, apart from the other details.
const isCustomerDetail = ( element: Element ): boolean =>
	new RegExp( `${ namespace }-(?!cellphone)` ).test( element.className );

/**
 * Whether a customer detail has a validation error.
 *
 * WooCommerce keeps a hidden error for each required field left empty or
 * invalid, and shows them all when the order is placed.
 *
 * @param visibleOnly Count only the errors on show.
 * @return Whether one is found.
 */
function hasCustomerDetailErrors( visibleOnly: boolean ): boolean {
	const errors: Record< string, { hidden?: boolean } > =
		select( VALIDATION_STORE_KEY ).getValidationErrors() || {};

	return Object.entries( errors ).some(
		( [ id, error ] ) =>
			id.startsWith( `contact_${ namespace }/` ) &&
			id !== `contact_${ namespace }/cellphone` &&
			! ( visibleOnly && error?.hidden )
	);
}

/**
 * The details as the card lists them, in the order of the form.
 *
 * @param form Contact form.
 * @return Primary and secondary lines.
 */
function customerDetailsSummary( form: Element ): {
	primary: string;
	secondary: string;
} {
	const values: Record< string, string > = {};
	const key = new RegExp( `${ namespace }-([a-z]+)` );

	Array.from( form.children )
		.filter( isCustomerDetail )
		.forEach( ( row ) => {
			const name = key.exec( row.className )?.[ 1 ];
			const control = row.querySelector( 'select, input' );

			if (
				! name ||
				! (
					control instanceof window.HTMLSelectElement ||
					control instanceof window.HTMLInputElement
				) ||
				! control.value
			) {
				return;
			}

			values[ name ] =
				control instanceof window.HTMLSelectElement
					? control.selectedOptions[ 0 ]?.text || ''
					: control.value;
		} );

	const labelled = ( label: string, value?: string ) =>
		value ? `${ label } ${ value }` : '';

	// Headed by whom the details belong to, as an address card is by the
	// name: the company once there is a CNPJ.
	const data: CustomerData = select( CART_STORE_KEY ).getCustomerData();
	const billing = data?.billingAddress || {};
	const name = [ billing.first_name, billing.last_name ]
		.filter( Boolean )
		.join( ' ' );
	const company = values.company || billing.company || '';

	return {
		primary: ( values.cnpj && company ) || name,
		secondary: [
			labelled(
				__( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
				values.cpf && obscureCpf( values.cpf )
			),
			labelled(
				__( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ),
				values.rg && obscureRg( values.rg )
			),
			labelled(
				__( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
				values.cnpj
			),
			labelled(
				__(
					'State Registration',
					'woocommerce-extra-checkout-fields-for-brazil'
				),
				values.ie
			),
			values.birthdate,
			values.gender,
		]
			.filter( Boolean )
			.join( ', ' ),
	};
}

/**
 * Open the customer details for editing, for the rest of the page.
 *
 * @param focus Move the focus to the first of them.
 */
function openCustomerDetails( focus: boolean ): void {
	const card = document.querySelector( `.${ CUSTOMER_DETAILS_CARD_CLASS }` );
	const form = card?.parentElement;

	customerDetails = 'editing';
	card?.remove();

	if ( ! focus ) {
		return;
	}

	Array.from( form?.children || [] )
		.find( isCustomerDetail )
		?.querySelector< HTMLElement >( 'select, input' )
		?.focus();
}

/**
 * Build the card standing for the customer details, drawn as WooCommerce's
 * address cards.
 *
 * @return Card.
 */
function createCustomerDetailsCard(): HTMLElement {
	const card = document.createElement( 'div' );
	const summary = document.createElement( 'div' );
	const edit = document.createElement( 'button' );

	card.className = `wc-block-components-address-card ${ CUSTOMER_DETAILS_CARD_CLASS }`;
	summary.className = `${ CUSTOMER_DETAILS_CARD_CLASS }-summary`;
	( [ 'primary', 'secondary' ] as const ).forEach( ( line ) => {
		const span = document.createElement( 'span' );

		span.className = `${ CUSTOMER_DETAILS_CARD_CLASS }-${ line }`;
		summary.append( span );
	} );

	edit.type = 'button';
	edit.className = 'wc-block-components-address-card__edit';
	edit.setAttribute( 'aria-controls', 'contact' );
	edit.setAttribute( 'aria-expanded', 'false' );
	edit.setAttribute(
		'aria-label',
		__(
			'Edit customer details',
			'woocommerce-extra-checkout-fields-for-brazil'
		)
	);
	edit.textContent = __(
		'Edit',
		'woocommerce-extra-checkout-fields-for-brazil'
	);
	edit.addEventListener( 'click', () => openCustomerDetails( true ) );

	card.append( summary, edit );

	return card;
}

/**
 * Write the summary into the card, leaving unchanged lines alone so the
 * observer calling this is not woken again.
 *
 * @param card Card.
 * @param form Contact form.
 */
function fillCustomerDetailsCard( card: Element, form: Element ): void {
	Object.entries( customerDetailsSummary( form ) ).forEach(
		( [ line, text ] ) => {
			const span = card.querySelector(
				`.${ CUSTOMER_DETAILS_CARD_CLASS }-${ line }`
			);

			if ( span && span.textContent !== text ) {
				span.textContent = text;
			}
		}
	);
}

/**
 * Collapse details a returning customer already filled in, as WooCommerce
 * collapses a complete address.
 *
 * Decided once, after the form has rendered and set its validation errors.
 * A customer starting from an empty form keeps it open.
 */
function decideCustomerDetails(): void {
	customerDetails = 'deciding';

	window.requestAnimationFrame( () =>
		window.setTimeout( () => {
			const form = contactForm();
			const filled = Array.from( form?.children || [] )
				.filter( isCustomerDetail )
				.some(
					( row ) =>
						!! row.querySelector< HTMLInputElement >(
							'select, input'
						)?.value
				);

			customerDetails =
				filled && ! hasCustomerDetailErrors( false )
					? 'collapsed'
					: 'editing';
			setupCustomerDetails();
		} )
	);
}

/**
 * Head the documents and personal details in the contact step, which
 * WooCommerce offers extensions as the only place for them.
 *
 * WooCommerce has no heading of its own to give here, so one is placed
 * before the first of them, and moved or dropped as they come and go. The
 * card summing them up goes between the two.
 */
function setupCustomerDetails(): void {
	const form = contactForm();

	if ( ! form ) {
		return;
	}

	const first = Array.from( form.children ).find( isCustomerDetail );
	let heading = form.querySelector( `:scope > .${ CUSTOMER_DETAILS_CLASS }` );
	let card = form.querySelector(
		`:scope > .${ CUSTOMER_DETAILS_CARD_CLASS }`
	);

	if ( ! first ) {
		heading?.remove();
		card?.remove();

		return;
	}

	if ( 'waiting' === customerDetails ) {
		decideCustomerDetails();
	}

	if ( 'collapsed' === customerDetails ) {
		card ??= createCustomerDetailsCard();
		fillCustomerDetailsCard( card, form );

		if ( card.nextElementSibling !== first ) {
			form.insertBefore( card, first );
		}
	}

	const next = card || first;

	if ( heading?.nextElementSibling === next ) {
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

	form.insertBefore( heading, next );
}

/**
 * Open the collapsed details once one of them shows an error, or once the
 * order is refused, since the server's reasons come as a notice naming no
 * field. The focus stays on the notice WooCommerce moves it to.
 */
function followCustomerDetailErrors(): void {
	if (
		'collapsed' === customerDetails &&
		( hasCustomerDetailErrors( true ) ||
			select( CHECKOUT_STORE_KEY ).hasError() )
	) {
		openCustomerDetails( false );
	}
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
	subscribe( followCustomerDetailErrors, VALIDATION_STORE_KEY );
	subscribe( followCustomerDetailErrors, CHECKOUT_STORE_KEY );

	// The card is headed by the billing name, typed in another step.
	subscribe( setupCustomerDetails, CART_STORE_KEY );

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
