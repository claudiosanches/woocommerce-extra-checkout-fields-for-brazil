import '../../scss/admin/settings.scss';

// Rows each person type setting applies to. The names match the classes
// Extra_Checkout_Fields_For_Brazil_Settings puts on the rows.
const APPLIES_TO = {
	1: [ 'only-brazil', 'rg', 'ie', 'validate-cpf', 'validate-cnpj' ],
	2: [ 'only-brazil', 'rg', 'validate-cpf' ],
	3: [ 'only-brazil', 'ie', 'validate-cnpj' ],
};

// Every row the selection governs, so none is left behind by a setting that
// stops listing it.
const ROWS = [ ...new Set( Object.values( APPLIES_TO ).flat() ) ];

/**
 * Show only the settings that apply to the selected person type.
 */
jQuery( function ( $ ) {
	const rows = {};

	ROWS.forEach( ( name ) => {
		rows[ name ] = $( `.bmw-row-${ name }` );
	} );

	// The card holds nothing but the two document checks, so it goes when
	// neither of them applies.
	const validation = $( '.bmw-section-validation' );

	$( '#person_type' )
		.on( 'change', function () {
			const shown = APPLIES_TO[ $( this ).val() ] || [];

			ROWS.forEach( ( name ) =>
				rows[ name ].toggle( shown.includes( name ) )
			);

			validation.toggle(
				shown.includes( 'validate-cpf' ) ||
					shown.includes( 'validate-cnpj' )
			);
		} )
		.change();
} );
