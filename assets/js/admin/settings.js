import '../../scss/admin/settings.scss';
import './settings-tabs';
import { watchStuck } from './sticky-save';

// Rows each person type setting applies to. The names match the classes
// Extra_Checkout_Fields_For_Brazil_Settings puts on the rows.
const APPLIES_TO = {
	1: [
		'only-brazil',
		'company',
		'rg',
		'ie',
		'validate-cpf',
		'validate-cnpj',
		'cnpj-lookup',
	],
	2: [ 'only-brazil', 'rg', 'validate-cpf' ],
	3: [ 'only-brazil', 'company', 'ie', 'validate-cnpj', 'cnpj-lookup' ],
};

// Every row the selection governs, so none is left behind by a setting that
// stops listing it.
const ROWS = [ ...new Set( Object.values( APPLIES_TO ).flat() ) ];

watchStuck( document.querySelector( '#bmw-settings .submit' ) );

/**
 * Show only the settings that apply to the choices made.
 */
jQuery( function ( $ ) {
	const rows = {};

	ROWS.forEach( ( name ) => {
		rows[ name ] = $( `.bmw-row-${ name }` );
	} );

	$( '#person_type' )
		.on( 'change', function () {
			const shown = APPLIES_TO[ $( this ).val() ] || [];

			ROWS.forEach( ( name ) =>
				rows[ name ].toggle( shown.includes( name ) )
			);
		} )
		.trigger( 'change' );

	$( '#no_number' )
		.on( 'change', function () {
			$( '.bmw-row-no-number-value' ).toggle( this.checked );
		} )
		.trigger( 'change' );

	$( '#postcode_only_calculator' )
		.on( 'change', function () {
			$( '.bmw-row-require-cart-postcode' ).toggle( this.checked );
		} )
		.trigger( 'change' );
} );
