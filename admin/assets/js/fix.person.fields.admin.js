/**
 * Plugin settings.
 */
(function ( $ ) {
	'use strict';

	$(function () {

		function personTypeFields( current ) {
			$( '._billing_cpf_field' ).hide();
			$( '._billing_company_field' ).hide();
			$( '._billing_cnpj_field' ).hide();

			if ( '1' === current ) {
				$( '._billing_cpf_field' ).show();
			}

			if ( '2' === current ) {
				$( '._billing_company_field' ).show();
				$( '._billing_cnpj_field' ).show();
			}
		}
		personTypeFields( $( '#_billing_persontype' ).val() );

		$( '#_billing_persontype' ).on( 'change', function () {
			personTypeFields( $( this ).val() );
		});

	});

}(jQuery));
