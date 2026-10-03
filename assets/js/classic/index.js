/* global bmwPublicParams */

import { __ } from '@wordpress/i18n';
import { bindMask } from '../shared/mask';
import { bindMailcheck } from '../shared/mailcheck';
import { bindIeExempt } from '../shared/ie-exempt';
import { bindNoNumber } from '../shared/no-number';
import { bindHouseNumber } from '../shared/house-number';
import { createAutofill } from '../shared/postcode';
import { bindPhone, bindPhonePicker, rebasePhone } from '../shared/phone';
import '../../scss/classic/classic.scss';

/**
 * Classic (shortcode) checkout and account address form.
 */
jQuery( function ( $ ) {
	const unbinders = new WeakMap();
	const bound = new WeakMap();

	/**
	 * Bind a feature to an element once, however often the fields are bound.
	 *
	 * @param {?HTMLElement}                   element Element.
	 * @param {string}                         feature Feature name.
	 * @param {(element: HTMLElement) => void} bind    Binds the feature.
	 */
	const once = ( element, feature, bind ) => {
		const features = element && ( bound.get( element ) || new Set() );

		if ( ! features || features.has( feature ) ) {
			return;
		}

		bound.set( element, features.add( feature ) );
		bind( element );
	};

	const mask = ( selector, format ) => {
		document.querySelectorAll( selector ).forEach( ( input ) => {
			if ( unbinders.has( input ) ) {
				return;
			}

			unbinders.set( input, bindMask( input, format ) );
			input.setAttribute( 'type', 'tel' );
		} );
	};

	const unmask = ( selector ) => {
		document.querySelectorAll( selector ).forEach( ( input ) => {
			const unbind = unbinders.get( input );

			if ( ! unbind ) {
				return;
			}

			unbind();
			unbinders.delete( input );
			input.setAttribute( 'type', 'text' );
		} );
	};

	const BILLING_MASKED = '#billing_birthdate, #billing_postcode';

	const bmwFrontEnd = {
		init() {
			this.listen();
			this.bind();

			// Checkouts such as Fluid Checkout render the address sections
			// again when they refresh, and the new fields arrive unbound.
			$( document.body ).on( 'updated_checkout', () => this.bind() );
		},

		/**
		 * Listen on the document, which outlives any field.
		 */
		listen() {
			if ( '0' !== bmwPublicParams.person_type ) {
				this.applyPersonType = this.personTypeFields();
			}

			if ( 'yes' === bmwPublicParams.maskedinput ) {
				$( document.body ).on(
					'change',
					'#billing_country',
					function () {
						if ( 'BR' === $( this ).val() ) {
							bmwFrontEnd.maskBilling();
						} else {
							bmwFrontEnd.unmaskBilling();
						}
					}
				);

				$( document.body ).on(
					'change',
					'#shipping_country',
					function () {
						if ( 'BR' === $( this ).val() ) {
							bmwFrontEnd.maskShipping();
						} else {
							bmwFrontEnd.unmaskShipping();
						}
					}
				);
			}

			this.binders = [
				this.phones( 'billing', [
					'billing_phone',
					'billing_cellphone',
				] ),
				this.phones( 'shipping', [ 'shipping_phone' ] ),
				this.houseNumber( 'billing' ),
				this.houseNumber( 'shipping' ),
			];

			if ( 'yes' === bmwPublicParams.postcode_autofill ) {
				this.autofill( 'billing' );
				this.autofill( 'shipping' );
			}
		},

		/**
		 * Bind the fields on the page that are not bound yet.
		 */
		bind() {
			if ( this.applyPersonType ) {
				once(
					document.querySelector( '.person-type-field' ),
					'person-type',
					this.applyPersonType
				);
			}

			if ( 'yes' === bmwPublicParams.maskedinput ) {
				if ( 'BR' === $( '#billing_country' ).val() ) {
					this.maskBilling();
				}

				if ( 'BR' === $( '#shipping_country' ).val() ) {
					this.maskShipping();
				}

				this.maskGeneral();
			}

			this.binders.forEach( ( bindFields ) => bindFields() );
			once( document.getElementById( 'billing_ie' ), 'ie', bindIeExempt );

			if ( 'yes' === bmwPublicParams.mailcheck ) {
				once(
					document.getElementById( 'billing_email' ),
					'mailcheck',
					bindMailcheck
				);
			}

			if ( $().select2 ) {
				$( '.wc-ecfb-select' )
					.not( '.select2-hidden-accessible' )
					.select2();
			}
		},

		/**
		 * Format an address's phones for its country, and offer the country
		 * code picker.
		 *
		 * @param {string}   group Address group, billing or shipping.
		 * @param {string[]} ids   Phone input ids.
		 * @return {() => void} Binds the phones on the page.
		 */
		phones( group, ids ) {
			const params = bmwPublicParams.phone || {};
			const masked = 'yes' === bmwPublicParams.maskedinput;
			const country = () => $( `#${ group }_country` ).val() || '';
			const inputs = () =>
				ids
					.map( ( id ) => document.getElementById( id ) )
					.filter( Boolean );
			const syncs = new WeakMap();
			let previous = country();

			$( document.body ).on( 'change', `#${ group }_country`, () => {
				const next = country();

				if ( masked && next !== previous ) {
					inputs().forEach( ( input ) => {
						input.value = rebasePhone(
							input.value,
							previous,
							next,
							params
						);
					} );
				}

				previous = next;
				inputs().forEach( ( input ) => syncs.get( input )?.() );
			} );

			return () =>
				inputs().forEach( ( input ) =>
					once( input, 'phone', () => {
						if ( masked ) {
							bindPhone( input, country, params );
						}

						syncs.set(
							input,
							bindPhonePicker( input, { country, params } )
						);
					} )
				);
		},

		/**
		 * Take digits only in an address's Number field, and offer No number.
		 *
		 * @param {string} group Address group, billing or shipping.
		 * @return {() => void} Binds the Number field on the page.
		 */
		houseNumber( group ) {
			// My Account renders Number as the block checkout's field.
			const field = () =>
				document.getElementById( `${ group }_number` ) ||
				document.querySelector(
					`[name="_wc_${ group }/csbmw/number"]`
				);
			const toggles = new WeakMap();

			// WooCommerce empties the field when another country hides it,
			// without an event the toggle would hear, so it starts over.
			$( document.body ).on( 'country_to_state_changing', () => {
				setTimeout( () => {
					const input = field();
					const unbind = input && toggles.get( input );

					if ( unbind ) {
						unbind();
						toggles.set(
							input,
							bindNoNumber( input, bmwPublicParams.no_number )
						);
					}
				} );
			} );

			return () =>
				once( field(), 'number', ( input ) => {
					bindHouseNumber( input, bmwPublicParams.no_number );
					toggles.set(
						input,
						bindNoNumber( input, bmwPublicParams.no_number )
					);
				} );
		},

		/**
		 * Fill an address from its CEP once the CEP is complete.
		 *
		 * @param {string} group Address group, billing or shipping.
		 */
		autofill( group ) {
			// My Account renders the number and neighborhood as the block
			// checkout's additional fields, under their own names.
			const fields = {
				address_1: `#${ group }_address_1`,
				address_2: `#${ group }_address_2`,
				number: `#${ group }_number, [name="_wc_${ group }/csbmw/number"]`,
				neighborhood: `#${ group }_neighborhood, [name="_wc_${ group }/csbmw/neighborhood"]`,
				city: `#${ group }_city`,
				state: `#${ group }_state`,
			};

			const fill = createAutofill( {
				url: bmwPublicParams.postcode_url,
				postcode: () =>
					'BR' === $( `#${ group }_country` ).val()
						? $( `#${ group }_postcode` ).val()
						: '',
				read: () =>
					Object.fromEntries(
						Object.entries( fields ).map( ( [ key, selector ] ) => [
							key,
							$( selector ).val() || '',
						] )
					),
				write: ( values ) => {
					Object.entries( values ).forEach( ( [ key, value ] ) => {
						const field = $( fields[ key ] ).val( value );

						// The change event updates select2 and the checkout
						// totals; validate clears a required field's error.
						field.trigger(
							'state' === key ? 'change' : 'validate'
						);
					} );
				},
			} );

			$( document.body ).on(
				'input change',
				`#${ group }_postcode`,
				fill
			);

			// A saved CEP with no street yet, as the cart calculator leaves it.
			if ( ! $( fields.address_1 ).val() ) {
				fill();
			}
		},

		personTypeFields() {
			/**
			 * Mark the rows the person type requires, as WooCommerce marks its
			 * own required fields.
			 */
			const markPersonTypeRequired = function () {
				$( '.person-type-field label .required' ).remove();
				$( '.person-type-required label' ).append(
					' ',
					$( '<abbr class="required">*</abbr>' ).attr(
						'title',
						__(
							'required',
							'woocommerce-extra-checkout-fields-for-brazil'
						)
					)
				);
			};

			// Company only takes part while the store asks legal persons for
			// it, which is when it carries the person type class.
			const ROWS = {
				1: '#billing_cpf_field, #billing_rg_field',
				2: '#billing_company_field, #billing_cnpj_field, #billing_ie_field',
			};

			/**
			 * Control person type fields
			 *
			 * @param {string}  personType
			 * @param {boolean} checkCountry
			 */
			const handleFields = function ( personType, checkCountry = false ) {
				let country = 'BR';

				if ( checkCountry ) {
					country = $( '#billing_country' ).val();
				}

				$( '.person-type-field' )
					.hide()
					.removeClass(
						'validate-required is-active woocommerce-validated'
					);
				$( '#billing_persontype_field' ).show().addClass( 'is-active' );

				const rows = $( ROWS[ personType ] || [] )
					.filter( '.person-type-field' )
					.show()
					.addClass( 'is-active' );

				if ( 'BR' === country ) {
					rows.filter( '.person-type-required' ).addClass(
						'validate-required woocommerce-validated'
					);
					markPersonTypeRequired();
				}
			};

			const onlyBrazil = 'no' !== bmwPublicParams.only_brazil;
			const choosable = '1' === bmwPublicParams.person_type;

			const applyChoice = () =>
				handleFields( $( '#billing_persontype' ).val(), onlyBrazil );

			const applyCountry = () => {
				if ( 'BR' !== $( '#billing_country' ).val() ) {
					$( '.person-type-field' ).removeClass(
						'validate-required is-active woocommerce-validated'
					);
					$( '.person-type-field label .required' ).remove();
					return;
				}

				// person_type 2 means individuals and 3 means legal person, so
				// offsetting by one gives what #billing_persontype would hold.
				const personType = choosable
					? $( '#billing_persontype' ).val()
					: String( bmwPublicParams.person_type - 1 );

				handleFields( personType );
			};

			if ( choosable ) {
				$( document.body ).on(
					'change',
					'#billing_persontype',
					applyChoice
				);
			}

			if ( onlyBrazil ) {
				$( document.body ).on(
					'change',
					'#billing_country',
					applyCountry
				);
			}

			// Called directly, as a change on the country would refresh the
			// checkout.
			return () => {
				if ( onlyBrazil ) {
					$( '.person-type-field' ).removeClass(
						'validate-required is-active woocommerce-validated'
					);
					$( '.person-type-field label .required' ).remove();
				} else {
					markPersonTypeRequired();
				}

				if ( choosable ) {
					applyChoice();
				}

				if ( onlyBrazil ) {
					applyCountry();
				}
			};
		},

		maskBilling() {
			mask( '#billing_birthdate', 'date' );
			mask( '#billing_postcode', 'cep' );
		},

		unmaskBilling() {
			unmask( BILLING_MASKED );
		},

		maskShipping() {
			mask( '#shipping_postcode', 'cep' );
		},

		unmaskShipping() {
			unmask( '#shipping_postcode' );
		},

		maskGeneral() {
			mask( '#billing_cpf, #credit-card-cpf', 'cpf' );
			mask( '#billing_cnpj', 'cnpj' );
			mask( '#credit-card-phone', 'phone' );
		},
	};

	bmwFrontEnd.init();
} );
