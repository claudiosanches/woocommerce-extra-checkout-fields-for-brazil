/**
 * Product page shipping calculator, and the CEP-only classic cart calculator.
 */

import { __ } from '@wordpress/i18n';
import { bindMask, formatCep } from '../shared/mask';
import {
	getJson,
	postcodeDigits,
	followPostcodeConsent,
	rememberPostcode,
	rememberedPostcode,
} from '../shared/postcode';
import type { PostcodeAddress } from '../shared/postcode';
import '../../scss/shipping/shipping.scss';

export interface ShippingParams {
	postcodeUrl?: string;
	estimateUrl?: string;
	findPostcodeUrl?: string;
	// Trusted SVG markup printed by the plugin.
	findPostcodeIcon?: string;
	postcodeOnly?: string;
	notices?: Partial< Record< NoticeType, string > >;
}

type NoticeType = 'error' | 'notice';

interface Rate {
	id: string;
	label: string;
	cost: string;
	delivery: string;
}

interface Estimate {
	address: PostcodeAddress;
	// Null until a variable product's options are chosen.
	rates: Rate[] | null;
}

declare global {
	interface Window {
		bmwShippingParams?: ShippingParams;
		jQuery?: ( target: unknown ) => {
			on: (
				events: string,
				handler: (
					event: unknown,
					variation?: { is_virtual?: boolean }
				) => void
			) => void;
		};
	}
}

const params: ShippingParams = window.bmwShippingParams || {};

const element = < K extends keyof HTMLElementTagNameMap >(
	tag: K,
	className: string,
	text = ''
): HTMLElementTagNameMap[ K ] => {
	const node = document.createElement( tag );

	node.className = className;
	node.textContent = text;

	return node;
};

/**
 * The add to cart form next to a calculator.
 *
 * @param root Calculator.
 * @return Form, when the page has one.
 */
function cartForm( root: HTMLElement ): HTMLFormElement | null {
	return (
		root.closest( '.product' )?.querySelector( 'form.cart' ) ||
		document.querySelector( 'form.cart' )
	);
}

/**
 * Variation and quantity chosen in the add to cart form.
 *
 * @param root Calculator.
 * @return Variation id, empty when none is chosen, and quantity.
 */
function chosenItem( root: HTMLElement ): {
	variationId: string;
	quantity: string;
} {
	const form = cartForm( root );
	const variation = form?.querySelector< HTMLInputElement >(
		'input[name="variation_id"]'
	);
	const quantity = form?.querySelector< HTMLInputElement >(
		'input[name="quantity"]'
	);

	return {
		variationId:
			variation?.value && '0' !== variation.value ? variation.value : '',
		quantity: quantity?.value || '1',
	};
}

/**
 * A WooCommerce notice, in the markup the theme prints its own in.
 *
 * @param type    Notice type.
 * @param message Message.
 * @return Notice nodes.
 */
function notice( type: NoticeType, message: string ): DocumentFragment {
	const template = document.createElement( 'template' );

	template.innerHTML = params.notices?.[ type ] || '<p>%s</p>';

	const walker = document.createTreeWalker(
		template.content,
		window.NodeFilter.SHOW_TEXT
	);

	for ( let node = walker.nextNode(); node; node = walker.nextNode() ) {
		if ( node.nodeValue?.includes( '%s' ) ) {
			node.nodeValue = node.nodeValue.replace( '%s', message );
		}
	}

	return template.content;
}

const QUOTES = 'csbmw_shipping_quotes';
const QUOTE_TTL = 30 * 60 * 1000;
const QUOTE_LIMIT = 30;

type Quotes = Record< string, { time: number; estimate: Estimate } >;

/**
 * Quotes kept for the browser tab, so revisiting a product or switching back
 * to an option asks the store nothing.
 *
 * @return Quotes still fresh, by key.
 */
function storedQuotes(): Quotes {
	try {
		const quotes = JSON.parse(
			window.sessionStorage.getItem( QUOTES ) || '{}'
		) as Quotes;
		const now = Date.now();

		return Object.fromEntries(
			Object.entries( quotes ).filter(
				( [ , quote ] ) => now - quote.time < QUOTE_TTL
			)
		);
	} catch {
		return {};
	}
}

/**
 * Keep a quote in the browser, dropping the oldest past the limit.
 *
 * @param key      Quote key.
 * @param estimate Quote.
 */
function storeQuote( key: string, estimate: Estimate ): void {
	const quotes = storedQuotes();

	// The card shows no more than the neighborhood, city and state.
	quotes[ key ] = {
		time: Date.now(),
		estimate: {
			...estimate,
			address: { ...estimate.address, address: '', postcode: '' },
		},
	};

	const kept = Object.entries( quotes )
		.sort( ( a, b ) => b[ 1 ].time - a[ 1 ].time )
		.slice( 0, QUOTE_LIMIT );

	try {
		window.sessionStorage.setItem(
			QUOTES,
			JSON.stringify( Object.fromEntries( kept ) )
		);
	} catch {}
}

/**
 * WooCommerce's cookie that changes with the cart, which the free shipping
 * minimum depends on.
 *
 * @return Cart hash, or an empty string for an empty cart.
 */
function cartHash(): string {
	return (
		/(?:^|;\s*)woocommerce_cart_hash=([^;]*)/.exec(
			document.cookie
		)?.[ 1 ] || ''
	);
}

/**
 * Neighborhood, city and state, as the card shows the destination.
 *
 * @param address Address.
 * @return Place.
 */
function placeOf( address: PostcodeAddress ): string {
	return [ address.neighborhood, `${ address.city } - ${ address.state }` ]
		.filter( Boolean )
		.join( ', ' );
}

/**
 * Wire a product page calculator.
 *
 * @param root Calculator.
 */
function bindProductCalculator( root: HTMLElement ): void {
	const empty = root.querySelector< HTMLElement >(
		'.csbmw-shipping-calculator-empty'
	);
	const summary = root.querySelector< HTMLElement >(
		'.csbmw-shipping-calculator-summary'
	);
	const place = root.querySelector< HTMLElement >(
		'.csbmw-shipping-calculator-place'
	);
	const destination = root.querySelector< HTMLButtonElement >(
		'.csbmw-shipping-calculator-destination'
	);
	const results = root.querySelector< HTMLElement >(
		'.csbmw-shipping-calculator-results'
	);
	// Absent when the block changes the CEP in the card itself.
	const dialog = root.querySelector< HTMLDialogElement >(
		'.csbmw-shipping-calculator-dialog'
	);
	const inline = 'block' === root.dataset.changePostcodeIn;
	const rateTemplate = root.querySelector< HTMLTemplateElement >(
		'.csbmw-shipping-calculator-rate-template'
	);

	if (
		! empty ||
		! summary ||
		! place ||
		! destination ||
		! results ||
		( ! dialog && ! inline ) ||
		! rateTemplate ||
		root.dataset.bmwBound
	) {
		return;
	}

	root.dataset.bmwBound = '1';

	let postcode =
		rememberedPostcode() || postcodeDigits( root.dataset.postcode );

	// Only the latest request may show its answer.
	let request = 0;

	const message = ( text: string ) => {
		results.replaceChildren(
			element( 'p', 'csbmw-shipping-calculator-message', text )
		);
	};

	const render = ( estimate: Estimate ) => {
		empty.hidden = true;
		summary.hidden = false;
		place.textContent = placeOf( estimate.address );
		results.setAttribute( 'aria-busy', 'false' );

		if ( null === estimate.rates ) {
			message(
				__(
					'Choose the product options first.',
					'woocommerce-extra-checkout-fields-for-brazil'
				)
			);

			return;
		}

		if ( ! estimate.rates.length ) {
			results.replaceChildren(
				notice(
					'notice',
					__(
						'No shipping options for this CEP.',
						'woocommerce-extra-checkout-fields-for-brazil'
					)
				)
			);

			return;
		}

		const list = element( 'ul', 'csbmw-shipping-calculator-rates' );

		estimate.rates.forEach( ( rate ) => {
			const row = rateTemplate.content.cloneNode(
				true
			) as DocumentFragment;
			const fill = ( selector: string, text: string ) => {
				const node = row.querySelector( selector );

				if ( node ) {
					node.textContent = text;
				}
			};

			fill( '.csbmw-shipping-calculator-rate-name', rate.label );
			fill( '.csbmw-shipping-calculator-rate-delivery', rate.delivery );
			fill( '.csbmw-shipping-calculator-rate-cost', rate.cost );
			list.append( row );
		} );

		results.replaceChildren( list );
	};

	/**
	 * Show an error under a CEP form.
	 *
	 * @param form Form.
	 * @param text Message, or an empty string to clear it.
	 */
	const formError = ( form: HTMLFormElement, text: string ) => {
		form
			.querySelector( '.csbmw-shipping-calculator-error' )
			?.replaceChildren( ...( text ? [ notice( 'error', text ) ] : [] ) );
		form
			.querySelector( 'input[name="postcode"]' )
			?.setAttribute( 'aria-invalid', text ? 'true' : 'false' );
	};

	/**
	 * Quote the product for a CEP.
	 *
	 * @param digits CEP digits.
	 * @param form   Form the customer submitted, if any. Errors show there;
	 *               without one the quote runs quietly.
	 * @return Whether the quote succeeded.
	 */
	const quote = async (
		digits: string,
		form: HTMLFormElement | null = null
	): Promise< boolean > => {
		const current = ++request;
		const { variationId, quantity } = chosenItem( root );
		const key = [
			root.dataset.productId,
			variationId,
			quantity,
			digits,
			cartHash(),
		].join( '|' );
		const cached = storedQuotes()[ key ];

		if ( cached ) {
			postcode = digits;
			render( cached.estimate );

			return true;
		}

		const button = form?.querySelector< HTMLButtonElement >(
			'button[type="submit"]'
		);

		// WooCommerce's own spinner for a busy button.
		button?.classList.add( 'loading' );
		button?.setAttribute( 'disabled', '' );

		if ( ! summary.hidden ) {
			results.setAttribute( 'aria-busy', 'true' );
		}

		let estimate: Estimate | null = null;
		let error = '';

		try {
			estimate = await getJson< Estimate >( params.estimateUrl || '', {
				product_id: root.dataset.productId || '',
				variation_id: variationId,
				quantity,
				postcode: digits,
			} );
		} catch ( failure ) {
			error =
				( failure instanceof Error && failure.message ) ||
				__(
					'Could not calculate shipping. Try again.',
					'woocommerce-extra-checkout-fields-for-brazil'
				);
		}

		button?.classList.remove( 'loading' );
		button?.removeAttribute( 'disabled' );

		if ( current !== request ) {
			return false;
		}

		results.setAttribute( 'aria-busy', 'false' );

		if ( ! estimate ) {
			if ( form ) {
				formError( form, error );
			} else {
				// A remembered CEP that no longer quotes starts over.
				rememberPostcode( '' );
				postcode = '';
				empty.hidden = false;
				summary.hidden = true;
			}

			return false;
		}

		postcode = digits;
		rememberPostcode( digits );
		storeQuote( key, estimate );
		render( estimate );

		return true;
	};

	root.querySelectorAll< HTMLFormElement >(
		'.csbmw-shipping-calculator-form'
	).forEach( ( form ) => {
		const input = form.querySelector< HTMLInputElement >(
			'input[name="postcode"]'
		);

		if ( ! input ) {
			return;
		}

		bindMask( input, 'cep' );

		form.addEventListener( 'submit', async ( event ) => {
			event.preventDefault();

			const digits = postcodeDigits( input.value );

			if ( ! digits ) {
				formError(
					form,
					__(
						'Enter a valid CEP.',
						'woocommerce-extra-checkout-fields-for-brazil'
					)
				);
				input.focus();

				return;
			}

			formError( form, '' );

			if ( ( await quote( digits, form ) ) && dialog?.open ) {
				dialog.close();
			}
		} );
	} );

	const changer = dialog || empty;

	destination.addEventListener( 'click', () => {
		const input = changer.querySelector< HTMLInputElement >(
			'input[name="postcode"]'
		);
		const form = changer.querySelector( 'form' );

		if ( input ) {
			input.value = formatCep( postcode );
		}

		if ( form ) {
			formError( form, '' );
		}

		if ( dialog ) {
			dialog.showModal();
		} else {
			// The card's own form takes the summary's place until a quote
			// brings it back.
			summary.hidden = true;
			empty.hidden = false;
			input?.focus();
		}

		input?.select();
	} );

	if ( dialog ) {
		dialog
			.querySelector( '.csbmw-shipping-calculator-close' )
			?.addEventListener( 'click', () => dialog.close() );

		// A click on the backdrop lands on the dialog itself.
		dialog.addEventListener( 'click', ( event ) => {
			if ( event.target === dialog ) {
				dialog.close();
			}
		} );

		dialog.addEventListener( 'close', () => destination.focus() );
	} else {
		// Escape goes back to the quote the customer had.
		empty.addEventListener( 'keydown', ( event ) => {
			if ( 'Escape' === event.key && postcode && place.textContent ) {
				empty.hidden = true;
				summary.hidden = false;
				destination.focus();
			}
		} );
	}

	// Quote again whenever the options or quantity change.
	let timer = 0;
	const requote = () => {
		window.clearTimeout( timer );

		if ( postcode ) {
			timer = window.setTimeout( () => quote( postcode ), 300 );
		}
	};
	const cart = cartForm( root );

	cart?.addEventListener( 'change', ( event ) => {
		if ( ( event.target as HTMLInputElement ).name === 'quantity' ) {
			requote();
		}
	} );

	// A virtual variation has nothing to ship, so the calculator steps aside
	// while one is chosen.
	window.jQuery?.( cart ).on( 'found_variation', ( event, variation ) => {
		root.hidden = !! variation?.is_virtual;

		if ( ! root.hidden ) {
			requote();
		}
	} );
	window.jQuery?.( cart ).on( 'reset_data', () => {
		root.hidden = false;
		requote();
	} );

	if ( postcode ) {
		quote( postcode );
	}
}

/**
 * Turn the classic cart calculator's postcode field into a CEP field.
 *
 * WooCommerce replaces the cart totals, calculator included, after every
 * update, so this runs again each time.
 */
function enhanceCartCalculator(): void {
	const input = document.getElementById( 'calc_shipping_postcode' );
	const row = document.getElementById( 'calc_shipping_postcode_field' );

	if (
		'yes' !== params.postcodeOnly ||
		! ( input instanceof window.HTMLInputElement ) ||
		! row ||
		input.dataset.bmwBound
	) {
		return;
	}

	input.dataset.bmwBound = '1';
	input.setAttribute( 'inputmode', 'numeric' );
	input.setAttribute( 'autocomplete', 'postal-code' );
	input.setAttribute( 'placeholder', '00000-000' );
	bindMask( input, 'cep' );

	input.form?.addEventListener( 'submit', () =>
		rememberPostcode( input.value )
	);

	const label = row.querySelector( 'label' );

	if ( label ) {
		label.textContent = __(
			'CEP',
			'woocommerce-extra-checkout-fields-for-brazil'
		);
	}

	const link = element(
		'a',
		'csbmw-shipping-calculator-find',
		__(
			"I don't know my CEP",
			'woocommerce-extra-checkout-fields-for-brazil'
		)
	);

	link.href = params.findPostcodeUrl || '';
	link.target = '_blank';
	link.rel = 'noopener noreferrer';
	link.insertAdjacentHTML( 'beforeend', params.findPostcodeIcon || '' );
	row.append( link );
}

function init(): void {
	document
		.querySelectorAll< HTMLElement >( '.csbmw-shipping-calculator' )
		.forEach( bindProductCalculator );

	enhanceCartCalculator();

	followPostcodeConsent( () => {
		try {
			window.sessionStorage.removeItem( QUOTES );
		} catch {}
	} );

	window
		.jQuery?.( document.body )
		.on(
			'updated_cart_totals updated_shipping_method',
			enhanceCartCalculator
		);
}

if ( 'loading' === document.readyState ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
