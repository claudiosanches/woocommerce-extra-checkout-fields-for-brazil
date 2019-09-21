/* global wcbcf_public_params */
( function( $ ) {
	/**
	 * Frontend actions
	 */
	const BrazilianMarketFrontend = {

		/**
		 * Initialize frontend actions
		 */
		init() {
			if ( '0' === wcbcf_public_params.sort_state_country ) {
				$( document.body ).on( 'country_to_state_changing', this.country_to_state_changing );
			}

			if ( '0' !== wcbcf_public_params.person_type ) {
				this.person_type_fields();
			}

			if ( 'yes' === wcbcf_public_params.maskedinput ) {
				$( document.body ).on( 'change', '#billing_country', function() {
					if ( 'BR' === $( this ).val() ) {
						BrazilianMarketFrontend.maskBilling();
					} else {
						BrazilianMarketFrontend.unmaskBilling();
					}
				} );

				$( document.body ).on( 'change', '#shipping_country', function() {
					if ( 'BR' === $( this ).val() ) {
						BrazilianMarketFrontend.maskShipping();
					} else {
						BrazilianMarketFrontend.unmaskShipping();
					}
				} );

				this.maskGeneral();
			}

			if ( 'yes' === wcbcf_public_params.mailcheck ) {
				this.emailCheck();
			}

			// Check if select2 exists.
			if ( $().select2 ) {
				$( '.wc-ecfb-select' ).select2();
			}
			if ( $( '#billing_country' ).val() === 'BR' ) {
				BrazilianMarketFrontend.maskBilling();
			}
		},

		/**
		 * Country to state changing.
		 * Fix the fields order.
		 */
		country_to_state_changing() {
			// Billing.
			$( '#billing_state_field label' ).html( wcbcf_public_params.state + ' <abbr class="required" title="' + wcbcf_public_params.required + '">*</abbr>' );
			$( '#billing_postcode_field' ).insertAfter( '#billing_country_field' );

			// Shipping.
			if ( $( '#shipping_state_field' ).length ) {
				$( '#shipping_state_field label' ).html( wcbcf_public_params.state + ' <abbr class="required" title="' + wcbcf_public_params.required + '">*</abbr>' );
				$( '#shipping_postcode_field' ).insertAfter( '#shipping_country_field' );
			}
		},

		person_type_fields() {
			// Required fields.
			if ( 'no' === wcbcf_public_params.only_brazil ) {
				$( '.person-type-field label .required' ).remove();
				$( '.person-type-field' ).addClass( 'validate-required' );
				$( '.person-type-field label' ).append( ' <abbr class="required" title="' + wcbcf_public_params.required + '">*</abbr>' );
			} else {
				$( '#billing_country' ).on( 'change', function() {
					const current = $( this ).val();

					if ( 'BR' === current ) {
						$( '.person-type-field label .required' ).remove();
						$( '.person-type-field' ).addClass( 'validate-required' );
						$( '.person-type-field label' ).append( ' <abbr class="required" title="' + wcbcf_public_params.required + '">*</abbr>' );
					} else {
						$( '.person-type-field' ).removeClass( 'validate-required' );
						$( '.person-type-field label .required' ).remove();
					}
				} ).change();
			}

			if ( '1' === wcbcf_public_params.person_type ) {
				$( '#billing_persontype' ).on( 'change', function() {
					const current = $( this ).val();

					$( '#billing_cpf_field' ).hide();
					$( '#billing_rg_field' ).hide();
					$( '#billing_company_field' ).hide();
					$( '#billing_cnpj_field' ).hide();
					$( '#billing_ie_field' ).hide();

					if ( '1' === current ) {
						$( '#billing_cpf_field' ).show();
						$( '#billing_rg_field' ).show();
					}

					if ( '2' === current ) {
						$( '#billing_company_field' ).show();
						$( '#billing_cnpj_field' ).show();
						$( '#billing_ie_field' ).show();
					}
				} ).change();
			}
		},

		maskBilling() {
			BrazilianMarketFrontend.maskPhone( '#billing_phone, #billing_cellphone' );
			$( '#billing_birthdate' ).mask( '00/00/0000' );
			$( '#billing_postcode' ).mask( '00000-000' );
			$( '#billing_phone, #billing_cellphone, #billing_birthdate, #billing_postcode' ).attr( 'type', 'tel' );
		},

		unmaskBilling() {
			$( '#billing_phone, #billing_cellphone, #billing_birthdate, #billing_postcode' ).unmask().attr( 'type', 'text' );
		},

		maskShipping() {
			$( '#shipping_postcode' ).mask( '00000-000' ).attr( 'type', 'tel' );
		},

		unmaskShipping() {
			$( '#shipping_postcode' ).unmask().attr( 'type', 'text' );
		},

		maskGeneral() {
			$( '#billing_cpf, #credit-card-cpf' ).mask( '000.000.000-00' );
			$( '#billing_cnpj' ).mask( '00.000.000/0000-00' );
			BrazilianMarketFrontend.maskPhone( '#credit-card-phone' );
		},

		maskPhone( selector ) {
			const $element = $( selector ),
				MaskBehavior = function( val ) {
					return val.replace( /\D/g, '' ).length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
				},
				maskOptions = {
					onKeyPress( val, e, field, options ) {
						field.mask( MaskBehavior.apply( {}, arguments ), options );
					},
				};

			$element.mask( MaskBehavior, maskOptions );
		},

		emailCheck() {
			if ( $( '#wcbcf-mailsuggest' ).length < 1 ) {
				$( '#billing_email' ).after( '<div id="wcbcf-mailsuggest"></div>' );
			}

			$( '#billing_email' ).on( 'blur', function() {
				$( '#wcbcf-mailsuggest' ).html( '' );
				$( this ).mailcheck( {
					suggested( element, suggestion ) {
						$( '#wcbcf-mailsuggest' ).html( 'Você quis dizer: ' + suggestion.full + '?' );
					},
				} );
			} );

			$( '#wcbcf-mailsuggest' ).css( {
				color: '#c00',
				fontSize: 'small',
			} );
		},
	};

	BrazilianMarketFrontend.init();
}( window.jQuery ) );
