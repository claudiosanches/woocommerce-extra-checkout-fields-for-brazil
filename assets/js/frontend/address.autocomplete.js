/* global wcbcf_public_params */
( function ( $ ) {
	'use strict';

	$( function () {

		/**
		 * Address Autocomplete.
		 *
		 * @param  {string} field Field name.
		 *
		 * @return {void}         Autocomplete fields.
		 */
		function addressAutoComplete( field ) {
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
							$( '#' + field + '_state option[value="' + address.estado + '"]' ).attr( 'selected', true ).change();

							// Chosen support.
							$( '#' + field + '_state' ).trigger( 'liszt:updated' );
						}
					});
				}
			}
		}

		/**
		 * Autocomplete the Address on change the *_postcode input.
		 *
		 * @param  {string} field Field name.
		 *
		 * @return {void}         Autocomplete fields.
		 */
		function addressAutoCompleteOnChange( field ) {
			$( '#' + field + '_postcode' ).on( 'blur', function () {
				addressAutoComplete( field );
			});
		}

		if ( 'yes' === wcbcf_public_params.addresscomplete ) {
			// Auto complete billing address.
			addressAutoComplete( 'billing' );
			addressAutoCompleteOnChange( 'billing' );

			// Auto complete shipping address.
			addressAutoComplete( 'shipping' );
			addressAutoCompleteOnChange( 'shipping' );
		}

	});

}( jQuery ) );
