/**
 * CEP-only shipping calculator for the cart block.
 *
 * WooCommerce 10 removed the calculator from the cart block, so this one is
 * added to the order summary through the checkout SlotFill. It is built from
 * the markup of WooCommerce's own coupon form and shipping options, so it
 * takes their styles.
 */

import { __ } from '@wordpress/i18n';
import { formatCep } from '../shared/mask';
import {
	describeAddress,
	lookupPostcode,
	postcodeDigits,
	rememberPostcode,
} from '../shared/postcode';
import type { PostcodeAddress } from '../shared/postcode';
import type { ShippingParams } from './shipping';

type Address = Record< string, string >;

interface CartRate {
	rate_id: string;
	name: string;
	price: string;
	taxes: string;
	selected: boolean;
	currency_code: string;
	currency_symbol: string;
	currency_minor_unit: number;
	currency_decimal_separator: string;
	currency_thousand_separator: string;
	currency_prefix: string;
	currency_suffix: string;
}

interface CartPackage {
	package_id: number | string;
	shipping_rates: CartRate[];
}

interface Cart {
	cartNeedsShipping: boolean;
	billingAddress: Address;
	shippingAddress: Address;
	shippingRates: CartPackage[];
}

interface CartStore {
	setBillingAddress: ( address: Address ) => void;
	setShippingAddress: ( address: Address ) => void;
	selectShippingRate: (
		rateId: string,
		packageId: number | string
	) => Promise< unknown >;
}

interface Globals {
	wp: {
		element: {
			createElement: (
				type: unknown,
				props?: unknown,
				...children: unknown[]
			) => unknown;
			useState: < T >(
				initial: T | ( () => T )
			) => [ T, ( value: T ) => void ];
		};
		data: {
			useDispatch: ( store: string ) => CartStore;
			select: ( store: string ) => {
				isCustomerDataUpdating: () => boolean;
			};
			subscribe: ( listener: () => void, store?: string ) => () => void;
		};
		plugins: {
			registerPlugin: (
				name: string,
				settings: { render: () => unknown; scope: string }
			) => void;
		};
	};
	wc: {
		blocksCheckout: { ExperimentalOrderMeta: unknown };
		priceFormat: {
			formatPrice: ( value: number, currency: unknown ) => string;
			getCurrencyFromPriceResponse: ( rate: CartRate ) => unknown;
		};
		wcSettings: {
			getSetting: < T >( name: string, fallback: T ) => T;
		};
	};
}

const { wp, wc } = window as unknown as Globals;
const { createElement: el, useState } = wp.element;

// WooCommerce checks that a script reading its globals declared them as
// dependencies, which it can only tell while the script first runs.
const { ExperimentalOrderMeta } = wc.blocksCheckout;
const { formatPrice, getCurrencyFromPriceResponse } = wc.priceFormat;
const includeTaxes = wc.wcSettings.getSetting(
	'displayCartPricesIncludingTax',
	false
);

const params: ShippingParams = window.bmwShippingParams || {};
const CART_STORE = 'wc/store/cart';
const NEIGHBORHOOD = 'csbmw/neighborhood';
const NUMBER = 'csbmw/number';
const INPUT_ID = 'csbmw-cart-postcode';
const ERROR_ID = 'csbmw-cart-postcode-error';

/**
 * Rate price as the cart shows its other prices.
 *
 * @param rate Shipping rate.
 * @return Formatted price.
 */
function ratePrice( rate: CartRate ): string {
	let price = parseInt( rate.price, 10 );

	if ( includeTaxes ) {
		price += parseInt( rate.taxes, 10 );
	}

	return price > 0
		? formatPrice( price, getCurrencyFromPriceResponse( rate ) )
		: __( 'Free', 'woocommerce-extra-checkout-fields-for-brazil' );
}

/**
 * An address moved to the place a CEP belongs to.
 *
 * A different CEP means a different street, so the street, complement,
 * number and neighborhood are replaced along with it.
 *
 * @param current Address the customer has.
 * @param found   Address found by CEP.
 * @return Address to save.
 */
function moveTo( current: Address, found: PostcodeAddress ): Address {
	const address: Address = {
		...current,
		country: 'BR',
		state: found.state,
		city: found.city,
		postcode: formatCep( found.postcode ),
	};

	if ( postcodeDigits( current.postcode ) !== found.postcode ) {
		address.address_1 = found.address;
		address.address_2 = '';
		address[ NEIGHBORHOOD ] = found.neighborhood;
		address[ NUMBER ] = '';
	}

	return address;
}

/**
 * The cart's destination, when it is a complete Brazilian address.
 *
 * @param address Shipping address.
 * @return One line describing it, or an empty string.
 */
function destination( address: Address ): string {
	if ( 'BR' !== address.country || ! postcodeDigits( address.postcode ) ) {
		return '';
	}

	return describeAddress( {
		postcode: address.postcode || '',
		address: address.address_1 || '',
		neighborhood: address[ NEIGHBORHOOD ] || '',
		city: address.city || '',
		state: address.state || '',
	} );
}

/**
 * Wait for WooCommerce to send the customer's address to the store.
 *
 * The cart store sends address changes itself, shortly after they are made.
 *
 * @return Resolves once the update finishes, or after ten seconds.
 */
function addressSaved(): Promise< void > {
	return new Promise( ( resolve ) => {
		let started = false;
		let timer = 0;

		const unsubscribe = wp.data.subscribe( () => {
			const updating = wp.data
				.select( CART_STORE )
				.isCustomerDataUpdating();

			if ( updating ) {
				started = true;
			} else if ( started ) {
				done();
			}
		}, CART_STORE );

		function done() {
			unsubscribe();
			window.clearTimeout( timer );
			resolve();
		}

		timer = window.setTimeout( done, 10000 );
	} );
}

function Rates( { cart }: { cart: Cart } ) {
	const { selectShippingRate } = wp.data.useDispatch( CART_STORE );

	return el(
		'fieldset',
		{ className: 'csbmw-shipping-calculator-rates' },
		el(
			'legend',
			{ className: 'screen-reader-text' },
			__(
				'Shipping options',
				'woocommerce-extra-checkout-fields-for-brazil'
			)
		),
		cart.shippingRates.map( ( pack ) =>
			el(
				'div',
				{
					key: pack.package_id,
					className: 'wc-block-components-radio-control',
				},
				pack.shipping_rates.map( ( rate ) => {
					const id = `csbmw-rate-${ pack.package_id }-${ rate.rate_id }`;

					return el(
						'label',
						{
							key: rate.rate_id,
							htmlFor: id,
							className:
								'wc-block-components-radio-control__option' +
								( rate.selected
									? ' wc-block-components-radio-control__option-checked'
									: '' ),
						},
						el( 'input', {
							id,
							className:
								'wc-block-components-radio-control__input',
							type: 'radio',
							name: `csbmw-rate-${ pack.package_id }`,
							value: rate.rate_id,
							checked: rate.selected,
							'aria-describedby': `${ id }__secondary-label`,
							onChange: () =>
								selectShippingRate(
									rate.rate_id,
									pack.package_id
								),
						} ),
						el(
							'div',
							{
								className:
									'wc-block-components-radio-control__option-layout',
							},
							el(
								'div',
								{
									className:
										'wc-block-components-radio-control__label-group',
								},
								el(
									'span',
									{
										className:
											'wc-block-components-radio-control__label',
									},
									rate.name
								),
								el(
									'span',
									{
										id: `${ id }__secondary-label`,
										className:
											'wc-block-components-radio-control__secondary-label',
									},
									el(
										'span',
										{
											className:
												'wc-block-formatted-money-amount wc-block-components-formatted-money-amount',
										},
										ratePrice( rate )
									)
								)
							)
						)
					);
				} )
			)
		)
	);
}

function Calculator( { cart }: { cart: Cart } ) {
	// Follows the cart until the customer types, since the cart can finish
	// loading after the calculator first renders.
	const [ typed, setTyped ] = useState< string | null >( null );
	const [ focused, setFocused ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ busy, setBusy ] = useState( false );
	const { setBillingAddress, setShippingAddress } =
		wp.data.useDispatch( CART_STORE );

	const saved =
		'BR' === cart.shippingAddress.country
			? formatCep( cart.shippingAddress.postcode )
			: '';
	const postcode = typed ?? saved;
	const place = destination( cart.shippingAddress );
	const hasRates = cart.shippingRates.some(
		( pack ) => pack.shipping_rates.length
	);

	const submit = async ( event: Event ) => {
		event.preventDefault();

		if ( busy ) {
			return;
		}

		if ( ! postcodeDigits( postcode ) ) {
			setError(
				__(
					'Enter a valid CEP.',
					'woocommerce-extra-checkout-fields-for-brazil'
				)
			);

			return;
		}

		setBusy( true );
		setError( '' );

		const found = await lookupPostcode(
			params.postcodeUrl || '',
			postcode
		);

		if ( ! found ) {
			setBusy( false );
			setError(
				__(
					'CEP not found. Check the number and try again.',
					'woocommerce-extra-checkout-fields-for-brazil'
				)
			);

			return;
		}

		const saving = addressSaved();

		setShippingAddress( moveTo( cart.shippingAddress, found ) );

		// As WooCommerce's own calculator does, until the customer has
		// started a billing address of their own.
		if ( ! cart.billingAddress.first_name ) {
			setBillingAddress( moveTo( cart.billingAddress, found ) );
		}

		await saving;
		rememberPostcode( found.postcode );
		setTyped( null );
		setBusy( false );
	};

	return el(
		'div',
		{
			className:
				'csbmw-shipping-calculator csbmw-cart-shipping-calculator',
			'aria-busy': busy,
		},
		el(
			'span',
			{ className: 'csbmw-shipping-calculator-label' },
			__(
				'Calculate shipping',
				'woocommerce-extra-checkout-fields-for-brazil'
			)
		),
		el(
			'form',
			{
				className:
					'wc-block-components-totals-coupon__form csbmw-shipping-calculator-form',
				noValidate: true,
				onSubmit: submit,
			},
			el(
				'div',
				{
					className:
						'wc-block-components-text-input wc-block-components-totals-coupon__input' +
						( focused || postcode ? ' is-active' : '' ) +
						( error ? ' has-error' : '' ),
				},
				el( 'input', {
					id: INPUT_ID,
					type: 'text',
					inputMode: 'numeric',
					autoComplete: 'postal-code',
					value: postcode,
					'aria-invalid': error ? 'true' : 'false',
					'aria-describedby': error ? ERROR_ID : undefined,
					onFocus: () => setFocused( true ),
					onBlur: () => setFocused( false ),
					onChange: ( event: { target: HTMLInputElement } ) => {
						setTyped( formatCep( event.target.value ) );
						setError( '' );
					},
				} ),
				el(
					'label',
					{ htmlFor: INPUT_ID },
					__( 'CEP', 'woocommerce-extra-checkout-fields-for-brazil' )
				)
			),
			el(
				'button',
				{
					className:
						'wc-block-components-button wp-element-button wc-block-components-totals-coupon__button contained',
					type: 'submit',
					disabled: busy,
				},
				busy &&
					el( 'span', {
						className: 'wc-block-components-spinner',
						'aria-hidden': true,
					} ),
				el(
					'span',
					{ className: 'wc-block-components-button__text' },
					__(
						'Calculate',
						'woocommerce-extra-checkout-fields-for-brazil'
					)
				)
			)
		),
		error &&
			el(
				'div',
				{
					className: 'wc-block-components-validation-error',
					role: 'alert',
				},
				el( 'p', { id: ERROR_ID }, el( 'span', null, error ) )
			),
		el(
			'a',
			{
				className: 'csbmw-shipping-calculator-find',
				href: params.findPostcodeUrl,
				target: '_blank',
				rel: 'noopener noreferrer',
			},
			__(
				"I don't know my CEP",
				'woocommerce-extra-checkout-fields-for-brazil'
			),
			el( 'span', {
				className: 'csbmw-shipping-calculator-find-icon',
				dangerouslySetInnerHTML: {
					__html: params.findPostcodeIcon || '',
				},
			} )
		),
		el(
			'div',
			{
				className: 'csbmw-shipping-calculator-results',
				'aria-live': 'polite',
			},
			place &&
				el(
					'p',
					{ className: 'csbmw-shipping-calculator-address' },
					place
				),
			place &&
				( hasRates
					? el( Rates, { cart } )
					: el(
							'p',
							{ className: 'csbmw-shipping-calculator-message' },
							__(
								'No shipping options for this CEP.',
								'woocommerce-extra-checkout-fields-for-brazil'
							)
					  ) )
		)
	);
}

// The order summary slot also renders on the checkout.
function CartOnly( { cart, context }: { cart?: Cart; context?: string } ) {
	return 'woocommerce/cart' === context && cart?.cartNeedsShipping
		? el( Calculator, { cart } )
		: null;
}

wp.plugins.registerPlugin( 'csbmw-cart-shipping-calculator', {
	render: () => el( ExperimentalOrderMeta as string, null, el( CartOnly ) ),
	scope: 'woocommerce-checkout',
} );
