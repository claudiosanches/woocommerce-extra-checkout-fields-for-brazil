/*jshint devel: true */
(function ( $ ) {
	'use strict';

	$(function () {
		$( '#person_type' ).on( 'change', function () {
			var onlyBrazil   = $( '.wrap form .form-table:eq(0) tr:eq(1)' ),
				rg           = $( '.wrap form .form-table:eq(0) tr:eq(2)' ),
				ie           = $( '.wrap form .form-table:eq(0) tr:eq(3)' ),
				validate     = $( '.wrap form h3:eq(2), .wrap form .form-table:eq(2)' ),
				validateCPF  = $( '.wrap form .form-table:eq(2) tr:eq(0)' ),
				validateCNPJ = $( '.wrap form .form-table:eq(2) tr:eq(1)' ),
				selected     = $( this ).val();

			onlyBrazil.hide();
			rg.hide();
			ie.hide();
			validate.hide();
			validateCPF.hide();
			validateCNPJ.hide();

			if ( '1' === selected ) {
				onlyBrazil.show();
				rg.show();
				ie.show();
				validate.show();
				validateCPF.show();
				validateCNPJ.show();
			} else if ( '2' === selected ) {
				onlyBrazil.show();
				rg.show();
				validate.show();
				validateCPF.show();
			} else if ( '3' === selected ) {
				onlyBrazil.show();
				ie.show();
				validate.show();
				validateCNPJ.show();
			}
		}).change();
	});

}(jQuery));
