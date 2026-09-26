/* global bmwPublicParams */

import { __ } from '@wordpress/i18n';
import { bindMask } from '../shared/mask';
import { bindMailcheck } from '../shared/mailcheck';
import { bindIeExempt, placeIeExempt } from '../shared/ie-exempt';
import { createAutofill } from '../shared/postcode';
import '../../scss/classic/classic.scss';

/**
 * Classic (shortcode) checkout and account address form.
 */
jQuery( function ( $ ) {
	const unbinders = new Map();

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

	const BILLING_MASKED =
		'#billing_phone, #billing_cellphone, #billing_birthdate, #billing_postcode';

	const bmwFrontEnd = {
		init() {
			if ( '0' !== bmwPublicParams.person_type ) {
				this.personTypeFields();
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

				if ( 'BR' === $( '#billing_country' ).val() ) {
					this.maskBilling();
				}

				if ( 'BR' === $( '#shipping_country' ).val() ) {
					this.maskShipping();
				}

				this.maskGeneral();
			}

			bindIeExempt( document.getElementById( 'billing_ie' ) );

			// Changing the country re-appends every row WooCommerce knows, in
			// locale order, which leaves the checkbox behind at the top of the
			// form. The sorting runs on the same event, so this waits for it.
			$( document.body ).on(
				'country_to_state_changed updated_checkout',
				function () {
					window.setTimeout( function () {
						placeIeExempt(
							document.getElementById( 'billing_ie' )
						);
					}, 0 );
				}
			);

			if ( 'yes' === bmwPublicParams.mailcheck ) {
				bindMailcheck( document.getElementById( 'billing_email' ) );
			}

			if ( $().select2 ) {
				$( '.wc-ecfb-select' ).select2();
			}

			if ( 'yes' === bmwPublicParams.postcode_autofill ) {
				this.autofill( 'billing' );
				this.autofill( 'shipping' );
			}
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
			 * Mark the person type as required, as WooCommerce marks its own
			 * fields.
			 */
			const markPersonTypeRequired = function () {
				$( '.person-type-field label .required' ).remove();
				$( '.person-type-field label' ).append(
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

				if ( '1' === personType ) {
					if ( 'BR' === country ) {
						$( '#billing_cpf_field, #billing_rg_field' )
							.addClass(
								'validate-required is-active woocommerce-validated'
							)
							.show();
					} else {
						$( '#billing_cpf_field, #billing_rg_field' )
							.show()
							.addClass( 'is-active' );
					}
				}

				if ( '2' === personType ) {
					if ( 'BR' === country ) {
						$( '#billing_company_field label .optional' ).remove();
						$(
							'#billing_company_field, #billing_cnpj_field, #billing_ie_field'
						)
							.addClass(
								'validate-required is-active woocommerce-validated'
							)
							.show();
					} else {
						$(
							'#billing_company_field, #billing_cnpj_field, #billing_ie_field'
						)
							.addClass( 'is-active' )
							.show();
					}
				}

				if ( 'BR' === country ) {
					markPersonTypeRequired();
				}
			};

			/**
			 * Maybe run handle fields
			 *
			 * @param {boolean} checkCountry
			 */
			const maybeRunHandleFields = function ( checkCountry = false ) {
				if ( '1' === bmwPublicParams.person_type ) {
					$( '#billing_persontype' )
						.on( 'change', function () {
							handleFields( $( this ).val(), checkCountry );
						} )
						.trigger( 'change' );
				}
			};

			if ( 'no' === bmwPublicParams.only_brazil ) {
				markPersonTypeRequired();

				maybeRunHandleFields();
			} else {
				$( '.person-type-field' ).removeClass(
					'validate-required is-active woocommerce-validated'
				);
				$( '.person-type-field label .required' ).remove();
				maybeRunHandleFields( true );

				$( '#billing_country' )
					.on( 'change', function () {
						if ( 'BR' !== $( this ).val() ) {
							$( '.person-type-field' ).removeClass(
								'validate-required is-active woocommerce-validated'
							);
							$( '.person-type-field label .required' ).remove();
							return;
						}

						if ( '0' === bmwPublicParams.person_type ) {
							return;
						}

						// person_type 2 means individuals and 3 means legal
						// person, so offsetting by one gives what
						// #billing_persontype would hold.
						const personType =
							'1' === bmwPublicParams.person_type
								? $( '#billing_persontype' ).val()
								: String( bmwPublicParams.person_type - 1 );

						handleFields( personType );
					} )
					.trigger( 'change' );
			}
		},

		maskBilling() {
			mask( '#billing_phone, #billing_cellphone', 'phone' );
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
