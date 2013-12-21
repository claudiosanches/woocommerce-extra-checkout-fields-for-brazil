/**
 * Plugin settings.
 */
(function ( $ ) {
	'use strict';

	$(function () {

		function personTypeSwitch() {
			var target = $( '.wrap form table:eq(2), .wrap form h3:eq(2)' );

			if ( $( '#person_type' ).is( ':checked' ) ) {
				target.show();
			} else {
				target.hide();
			}
		}

		personTypeSwitch();

		$( '#person_type' ).on( 'click', function () {
			personTypeSwitch();
		});

	});

}(jQuery));
