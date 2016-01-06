/* global wcbcf_public_params */
/*jshint devel: true */
jQuery( function( $ ) {

	/**
	 * Frontend actions
	 */
	var wc_ecfb_frontned = {
		
		/**
		* Mask patterns for diverse countries.
		*/
		mask_patterns: {
			zip:{
				'BR': 'ddddd-ddd',
				'DK': 'dddd',
				'FR': 'ddddd',
				'DE': 'ddddd',
				'PT': 'dddd-ddd',
				'ES': 'ddddd',
				'CH': 'dddd',
				'GB': 'aadd daa',
				'US': 'ddddd-dddd'
			},
			phone:{
				'BR': [12, '+55 (dd) ddddd-ddd?d', '+55 (dd) dddd-dddd?d'],
				'FR': '+33 d dd dd dd dd',
				'DE': '+49 (ddd) dddddd?ddd',
				'PT': '+351 (ddd) dddd ddd?dd',
				'ES': [12, '+34 (dd) ddddd-ddd?dd', '+34 (dd) dddd-ddd?dd'],
				'CH': '+41 dd ddd dd dd?dd',
				'GB': [12, '+44 (dddd) dddd ddd?dd', '+44 (dddd) ddd ddd?dd'],
				'US': '+1 (ddd) ddddddd?dd'
			},
			date:{
				'BR': 'dd/dd/dddd',
				'DK': 'dddd-dd-dd',
				'FR': 'dd-dd-dddd',
				'DE': 'dd.dd.dddd',
				'PT': 'dd/dd/dddd',
				'ES': 'dd/dd/dddd',
				'CH': 'dd.dd.dddd',
				'GB': 'dd/dd/dddd',
				'US': 'dd/dd/dddd'
			},
		},
		
		/**
		 * Initialize frontend actions
		 */
		init: function() {
			document.__frontend_actions = this;
			$.mask.definitions['9'] = '';
			$.mask.definitions['d'] = '[0-9]';
			
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
			// UPDATE MASKS
			if ( 'yes' === wcbcf_public_params.maskedinput ) document.__frontend_actions.masks();
			
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
			// Country Code
			var countryCode = jQuery("#billing_country").val();
			
			// CPF.
			$( '#billing_cpf, #credit-card-cpf' ).unmask().mask( 'ddd.ddd.ddd-dd' );
			
			// CPNJ.
			$( '#billing_cnpj' ).unmask().mask( 'dd.ddd.ddd/dddd-dd' );
			
			// Cell phone.
			var scope = this;
			$( '#billing_phone, #billing_cellphone, #credit-card-phone' ).off( "focusout" ).focusout( function () {
				var phone, element;
				element = $( this );
				element.unmask();
				phone = element.val().replace( /\D/g, '' );
				
				element.mask( scope.getPhoneMask(jQuery("#billing_country").val(), phone.length) );
			}).trigger( 'focusout' );
			
			// Birth Date.
			$( '#billing_birthdate' ).unmask().mask( this.getDateMask(countryCode) );
			
			// Zip Code.
			$( '#billing_postcode' ).unmask().mask( this.getPostalCodeMask(countryCode) );
			$( '#shipping_postcode' ).unmask().mask( this.getPostalCodeMask(countryCode) );
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
		},
		
		getPhoneMask: function($countryCode, $phoneLength){
			var phoneLength = $phoneLength || 0;
			var mask = this.mask_patterns.phone[$countryCode];

			if(mask && mask.constructor === Array){
				if ( phoneLength > mask[0] ) mask = mask[1];
				else mask = mask[2];
			}
			
			return mask || '';
		},
		
		getDateMask: function($countryCode){
			var mask = this.mask_patterns.date[$countryCode];
			return mask || '';
		},
		
		getPostalCodeMask: function($countryCode){
			var mask = this.mask_patterns.zip[$countryCode];
			return mask || '';
		}
	};

	wc_ecfb_frontned.init();
});
