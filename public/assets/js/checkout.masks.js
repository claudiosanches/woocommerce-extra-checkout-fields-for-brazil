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
			$( '#billing_rg' ).keydown(function (e) {
				if ( $.inArray( e.keyCode, [46, 8, 13] ) !== -1 || ( e.keyCode === 65 && e.ctrlKey === true ) || ( e.keyCode >= 35 && e.keyCode <= 39 ) ) {
					return;
				}
				if ( ( e.shiftKey || ( e.keyCode < 48 || e.keyCode > 57 ) ) && ( e.keyCode < 96 || e.keyCode > 105 ) ) {
					e.preventDefault();
				}
			});

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
		}

	});

}(jQuery));
