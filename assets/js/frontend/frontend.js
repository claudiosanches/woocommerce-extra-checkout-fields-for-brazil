/* global wcbcf_public_params */
/*jshint devel: true */
jQuery( function( $ ) {

	/**
	 * Frontend actions
	 */
	var wc_ecfb_frontned = {

		/**
		 * Initialize frontend actions
		 */
		init: function() {
			$( document.body ).on( 'country_to_state_changing', this.country_to_state_changing );

			if ( '0' !== wcbcf_public_params.person_type ) {
				this.person_type_fields();
			}

			if ( 'yes' === wcbcf_public_params.maskedinput ) {
				this.masks();
			}

			if ( 'yes' === wcbcf_public_params.mailcheck ) {
				this.emailCheck();
			}

			if ( 'yes' === wcbcf_public_params.addresscomplete ) {
				// Auto complete billing address.
				this.addressAutoComplete( 'billing' );
				this.addressAutoCompleteOnChange( 'billing' );

				// Auto complete shipping address.
				this.addressAutoComplete( 'shipping' );
				this.addressAutoCompleteOnChange( 'shipping' );
			}
		},

		/**
		 * Country to state changing.
		 * Fix the fields order.
		 */
		country_to_state_changing: function() {
			// Billing.
			$( '#billing_state_field label' ).html( wcbcf_public_params.state + ' <abbr class="required" title="' + wcbcf_public_params.required + '">*</abbr>' );
			$( '#billing_postcode_field' ).insertAfter( '#billing_country_field' );

			// Shipping.
			if ( $( '#shipping_state_field' ).length ) {
				$( '#shipping_state_field label' ).html( wcbcf_public_params.state + ' <abbr class="required" title="' + wcbcf_public_params.required + '">*</abbr>' );
				$( '#shipping_postcode_field' ).insertAfter( '#shipping_country_field' );
			}
		},

		person_type_fields: function() {
			// Required fields.
			if ( 'no' === wcbcf_public_params.only_brazil ) {
				$( '.person-type-field' ).addClass( 'validate-required' );
				$( '.person-type-field label' ).append( ' <abbr class="required" title="' + wcbcf_public_params.required + '">*</abbr>' );
			} else {
				$( '#billing_country' ).on( 'change', function () {
					var current = $( this ).val();

					if ( 'BR' === current ) {
						$( '.person-type-field' ).addClass( 'validate-required' );
						$( '.person-type-field label' ).append( ' <abbr class="required" title="' + wcbcf_public_params.required + '">*</abbr>' );
					} else {
						$( '.person-type-field' ).removeClass( 'validate-required' );
						$( '.person-type-field label abbr' ).remove();
					}
				}).change();
			}

			if ( '1' === wcbcf_public_params.person_type ) {
				$( '#billing_persontype' ).on( 'change', function () {
					var current = $( this ).val();

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
				}).change();
			}
		},

		masks: function() {
			// CPF.
			$( '#billing_cpf, #credit-card-cpf' ).mask( '999.999.999-99' );

			// CPNJ.
			$( '#billing_cnpj' ).mask( '99.999.999/9999-99' );

			// Cell phone.
			$( '#billing_phone, #billing_cellphone, #credit-card-phone' ).focusout( function () {
				var phone, element;
				element = $( this );
				element.unmask();
				phone = element.val().replace( /\D/g, '' );

				if ( phone.length > 10 ) {
					element.mask( '(99) 99999-999?9' );
				} else {
					element.mask( '(99) 9999-9999?9' );
				}
			}).trigger( 'focusout' );

			// Birth Date.
			$( '#billing_birthdate' ).mask( '99/99/9999' );

			// Zip Code.
			$( '#billing_postcode' ).mask( '99999-999' );
			$( '#shipping_postcode' ).mask( '99999-999' );
		},

		emailCheck: function() {
			if ( $( '#wcbcf-mailsuggest' ).length < 1 ) {
				$( '#billing_email' ).after( '<div id="wcbcf-mailsuggest"></div>' );
			}

			$( '#billing_email' ).on( 'blur', function () {
				$( '#wcbcf-mailsuggest' ).html( '' );
				$( this ).mailcheck({
					suggested: function( element, suggestion ) {
						$( '#wcbcf-mailsuggest' ).html( 'Você quis dizer: ' + suggestion.full + '?' );
					}
				});
			});

			$( '#wcbcf-mailsuggest' ).css({
				color: '#c00',
				fontSize: 'small'
			});
		},

		addressAutoComplete: function( field ) {
			// Checks with *_postcode field exist.
			if ( $( '#' + field + '_postcode' ).length ) {

				// Valid CEP.
				var cep       = $( '#' + field + '_postcode' ).val().replace( '.', '' ).replace( '-', '' ),
					country   = $( '#' + field + '_country' ).val(),
					address_1 = $( '#' + field + '_address_1' ).val();

				// Check country is BR.
				if ( cep !== '' && 8 === cep.length && 'BR' === country && 0 === address_1.length ) {

					// Gets the address.
					$.ajax({
						type: 'GET',
						url: '//correiosapi.apphb.com/cep/' + cep,
						dataType: 'jsonp',
						crossDomain: true,
						contentType: 'application/json',
						success: function ( address ) {

							// Address.
							if ( '' !== address.tipoDeLogradouro ) {
								$( '#' + field + '_address_1' ).val( address.tipoDeLogradouro + ' ' + address.logradouro ).change();
							} else {
								$( '#' + field + '_address_1' ).val( address.logradouro ).change();
							}

							// Neighborhood.
							$( '#' + field + '_neighborhood' ).val( address.bairro ).change();

							// City.
							$( '#' + field + '_city' ).val( address.cidade ).change();

							// State.
							$( '#' + field + '_state option:selected' ).attr( 'selected', false ).change();
							$( '#' + field + '_state option[value="' + address.estado + '"]' ).attr( 'selected', 'selected' ).change();

							// Chosen support.
							$( '#' + field + '_state' ).trigger( 'liszt:updated' ).trigger( 'chosen:updated' );
						}
					});
				}
			}
		},

		addressAutoCompleteOnChange: function( field ) {
			$( '#' + field + '_postcode' ).on( 'blur', function () {
				wc_ecfb_frontned.addressAutoComplete( field );
			});
		}
	};

	wc_ecfb_frontned.init();
});
