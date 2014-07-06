/* global wcbcf_writepanel_params */
/**
 * Plugin settings.
 */
(function ( $ ) {
	'use strict';

	$( function () {

		function personTypeFields( current ) {
			$( '._billing_cpf_field, ._billing_rg_field, ._billing_company_field, ._billing_cnpj_field, ._billing_ie_field' ).hide();

			if ( '1' === current ) {
				$( '._billing_cpf_field, ._billing_rg_field' ).show();
			}

			if ( '2' === current ) {
				$( '._billing_company_field, ._billing_cnpj_field, ._billing_ie_field' ).show();
			}
		}

		if ( '1' === wcbcf_writepanel_params.person_type ) {
			personTypeFields( $( '#_billing_persontype' ).val() );

			$( '#_billing_persontype' ).on( 'change', function () {
				personTypeFields( $( this ).val() );
			});
		}

	});

}( jQuery ) );
