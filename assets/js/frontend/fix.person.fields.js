/* global wcbcf_public_params */
/**
 * Fix person fields.
 */
(function ( $ ) {
	'use strict';

	$(function () {

		/**
		 * Hide and show cpf and cnpj fields
		 *
		 * @param  {string}
		 *
		 * @return {void}
		 */
		function personTypeFields( current ) {
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
		}

		if ( '0' !== wcbcf_public_params.person_type ) {
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
				personTypeFields( $( '#billing_persontype' ).val() );

				$( '#billing_persontype' ).on( 'change', function () {
					var current = $( this ).val();

					personTypeFields( current );
				});
			}
		}

	});

}( jQuery ) );
