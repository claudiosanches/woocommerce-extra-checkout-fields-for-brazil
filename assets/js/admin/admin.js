/*jshint devel: true */
(function ( $ ) {
	'use strict';

	$(function () {

		function personTypeSwitch( option ) {
			var rg            = $( '.wrap form .form-table:eq(0) tr:eq(1)' ),
				ie            = $( '.wrap form .form-table:eq(0) tr:eq(2)' ),
				validate      = $( '.wrap form h3:eq(2), .wrap form .form-table:eq(2)' ),
				validate_cpf  = $( '.wrap form .form-table:eq(2) tr:eq(0)' ),
				validate_cnpj = $( '.wrap form .form-table:eq(2) tr:eq(1)' );

			rg.hide();
			ie.hide();
			validate.hide();
			validate_cpf.hide();
			validate_cnpj.hide();

			if ( '1' === option ) {
				rg.show();
				ie.show();
				validate.show();
				validate_cpf.show();
				validate_cnpj.show();
			} else if ( '2' === option ) {
				rg.show();
				validate.show();
				validate_cpf.show();
			} else if ( '3' === option ) {
				ie.show();
				validate.show();
				validate_cnpj.show();
			}
		}

		personTypeSwitch( $( '#person_type' ).val() );

		$( '#person_type' ).on( 'change', function () {
			personTypeSwitch( $( this ).val() );
		});

	});

}(jQuery));
