import {
	isCellphone,
	isCnpj,
	isCpf,
	isDate,
	isPhone,
	isPostcode,
} from '../../assets/js/shared/validation';

describe( 'isCpf', () => {
	it.each( [ '111.444.777-35', '11144477735', '123.456.789-09' ] )(
		'accepts %p',
		( value ) => expect( isCpf( value ) ).toBe( true )
	);

	it.each( [
		'111.444.777-00',
		'111.111.111-11',
		'1114447773',
		'111444777351',
		'',
		undefined,
	] )( 'rejects %p', ( value ) => expect( isCpf( value ) ).toBe( false ) );
} );

describe( 'isCnpj', () => {
	it.each( [
		'11.222.333/0001-81',
		'11222333000181',
		'11.444.777/0001-61',
		'12.ABC.345/01DE-35',
	] )( 'accepts %p', ( value ) => expect( isCnpj( value ) ).toBe( true ) );

	it.each( [
		'11.222.333/0001-00',
		'11.111.111/1111-11',
		'1122233300018',
		'12.ABC.345/01DE-3X',
		'',
		undefined,
	] )( 'rejects %p', ( value ) => expect( isCnpj( value ) ).toBe( false ) );
} );

describe( 'contact validators', () => {
	it( 'accepts ten and eleven digit phone numbers', () => {
		expect( isPhone( '(11) 3333-4444' ) ).toBe( true );
		expect( isPhone( '(11) 98765-4321' ) ).toBe( true );
		expect( isPhone( '113333444' ) ).toBe( false );
	} );

	it( 'requires eleven digits for a cell phone', () => {
		expect( isCellphone( '(11) 98765-4321' ) ).toBe( true );
		expect( isCellphone( '(11) 3333-4444' ) ).toBe( false );
	} );

	it( 'requires eight digits for a postcode', () => {
		expect( isPostcode( '01310-100' ) ).toBe( true );
		expect( isPostcode( '0131010' ) ).toBe( false );
	} );
} );

describe( 'isDate', () => {
	it.each( [
		[ '01/02/1990', true ],
		[ '29/02/2024', true ],
		[ '29/02/2023', false ],
		[ '31/04/1990', false ],
		[ '02/28/1990', false ],
		[ '1/2/1990', false ],
		[ '', false ],
	] )( 'validates %p as %p', ( value, expected ) =>
		expect( isDate( value ) ).toBe( expected )
	);
} );
