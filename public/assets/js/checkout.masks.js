/* global wcbcf_public_params */
/**
 * Checkout fields mask.
 */
(function ( $ ) {
	'use strict';

	$(function () {

		if ( 'yes' === wcbcf_public_params.maskedinput ) {
			// CPF.
			$( '#billing_cpf, #credit-card-cpf' ).mask( '999.999.999-99' );

			// RG.
			$( '#billing_rg' ).mask( '99.999.999-9' );

			// CPNJ.
			$( '#billing_cnpj' ).mask( '99.999.999/9999-99' );

			// Phone.
			$( '#billing_phone' ).mask( '(99) 9999-9999' );

			// Cell phone.
			$( '#billing_cellphone, #credit-card-phone' ).focusout( function () {
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
		}

	});

}(jQuery));
