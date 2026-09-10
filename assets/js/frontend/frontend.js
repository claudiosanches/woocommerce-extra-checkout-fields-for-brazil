/* global bmwPublicParams */

import { bindMask } from '../shared/mask';
import { bindMailcheck } from '../shared/mailcheck';
import { bindIeExempt } from '../shared/ie-exempt';
import '../../scss/frontend/frontend.scss';

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

			bindIeExempt( document.getElementById( 'billing_ie' ), {
				label: bmwPublicParams.ie_exempt,
			} );

			if ( 'yes' === bmwPublicParams.mailcheck ) {
				bindMailcheck(
					document.getElementById( 'billing_email' ),
					bmwPublicParams.suggest_text
				);
			}

			if ( $().select2 ) {
				$( '.wc-ecfb-select' ).select2();
			}
		},

		personTypeFields() {
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
					$( '.person-type-field label .required' ).remove();
					$( '.person-type-field label' ).append(
						' <abbr class="required" title="' +
							bmwPublicParams.required +
							'">*</abbr>'
					);
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
						.change();
				}
			};

			if ( 'no' === bmwPublicParams.only_brazil ) {
				$( '.person-type-field label .required' ).remove();
				$( '.person-type-field label' ).append(
					' <abbr class="required" title="' +
						bmwPublicParams.required +
						'">*</abbr>'
				);

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
					.change();
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
