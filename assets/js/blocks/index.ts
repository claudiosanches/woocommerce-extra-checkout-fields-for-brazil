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
import { caretIndex, caretOffset, formatCep, formatters } from '../shared/mask';
import { createAutofill } from '../shared/postcode';
import { bindMailcheck } from '../shared/mailcheck';
import { bindIeExempt } from '../shared/ie-exempt';
import { stripCountryFormats } from '../shared/address-format';
import type { CountryFormats } from '../shared/address-format';
import '../../scss/blocks/blocks.scss';

interface BlocksParams {
	namespace?: string;
	maskedinput?: string;
	mailcheck?: string;
	suggestText?: string;
	ieExemptLabel?: string;
	postcodeAutofill?: string;
	postcodeUrl?: string;
}

interface CartAddressStore {
	setBillingAddress: ( address: Record< string, string > ) => void;
	setShippingAddress: ( address: Record< string, string > ) => void;
}

interface CustomerData {
	billingAddress?: Record< string, string >;
	shippingAddress?: Record< string, string >;
}

interface StoreSelectors {
	getCustomerData: () => CustomerData;
	getUseShippingAsBilling?: () => boolean;
}

declare global {
	interface Window {
		bmwBlocksParams?: BlocksParams;
		wcSettings?: { countryData?: CountryFormats };
		wp?: {
			data?: {
				dispatch: ( store: string ) => CartAddressStore;
				select: ( store: string ) => StoreSelectors;
			};
		};
	}
}

// The address card is formatted in the browser, before anything this script
// does on the page, so the formats are cleaned up as soon as it runs.
stripCountryFormats( window.wcSettings?.countryData );

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

type Group = 'billing' | 'shipping';

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
	const address = (): Record< string, string > => {
		const data = window.wp?.data
			?.select( 'wc/store/cart' )
			.getCustomerData();

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
				neighborhood: current[ neighborhood ] || '',
				city: current.city || '',
				state: current.state || '',
			};
		},
		write: ( values ) => {
			const store = window.wp?.data?.dispatch( 'wc/store/cart' );

			if ( ! store ) {
				return;
			}

			const { neighborhood: value, ...rest } = values;
			const update: Record< string, string > = {
				...rest,
				postcode: formatCep(
					inputById( `${ group }-postcode` )?.value
				),
			};

			if ( undefined !== value ) {
				update[ neighborhood ] = value;
			}

			if ( 'billing' === group ) {
				store.setBillingAddress( update );

				return;
			}

			store.setShippingAddress( update );

			if (
				window.wp?.data
					?.select( 'wc/store/checkout' )
					.getUseShippingAsBilling?.()
			) {
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

	bindIeExempt( input, {
		label: params.ieExemptLabel,
		write: writeControlled,
	} );
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

	if ( 'yes' === params.postcodeAutofill ) {
		document.addEventListener( 'input', handleAutofill );
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
