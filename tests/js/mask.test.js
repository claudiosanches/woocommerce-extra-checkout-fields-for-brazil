import {
	bindMask,
	caretIndex,
	caretOffset,
	formatCep,
	formatCnpj,
	formatCpf,
	formatDate,
	formatPhone,
	maskInput,
} from '../../assets/js/shared/mask';

describe( 'formatCpf', () => {
	it.each( [
		[ '', '' ],
		[ '111', '111' ],
		[ '1114', '111.4' ],
		[ '11144477735', '111.444.777-35' ],
		[ '111.444.777-35', '111.444.777-35' ],
		[ 'abc11144477735xyz', '111.444.777-35' ],
		[ '111444777352222', '111.444.777-35' ],
	] )( 'formats %p as %p', ( input, expected ) => {
		expect( formatCpf( input ) ).toBe( expected );
	} );
} );

describe( 'formatCnpj', () => {
	it.each( [
		[ '11222333000181', '11.222.333/0001-81' ],
		[ '12ABC34501DE35', '12.ABC.345/01DE-35' ],
		[ '12abc34501de35', '12.ABC.345/01DE-35' ],
		[ '11.222.333/0001-81', '11.222.333/0001-81' ],
		[ '112', '11.2' ],
	] )( 'formats %p as %p', ( input, expected ) => {
		expect( formatCnpj( input ) ).toBe( expected );
	} );
} );

describe( 'formatCep', () => {
	it.each( [
		[ '01310100', '01310-100' ],
		[ '01310', '01310' ],
		[ '013101009999', '01310-100' ],
	] )( 'formats %p as %p', ( input, expected ) => {
		expect( formatCep( input ) ).toBe( expected );
	} );
} );

describe( 'formatPhone', () => {
	it( 'uses the eight digit layout for landlines', () => {
		expect( formatPhone( '1133334444' ) ).toBe( '(11) 3333-4444' );
	} );

	it( 'uses the nine digit layout for mobiles', () => {
		expect( formatPhone( '11987654321' ) ).toBe( '(11) 98765-4321' );
	} );

	it( 'formats progressively while typing', () => {
		expect( formatPhone( '11' ) ).toBe( '11' );
		expect( formatPhone( '113' ) ).toBe( '(11) 3' );
	} );
} );

describe( 'formatDate', () => {
	it.each( [
		[ '01021990', '01/02/1990' ],
		[ '0102', '01/02' ],
		[ '010219901234', '01/02/1990' ],
	] )( 'formats %p as %p', ( input, expected ) => {
		expect( formatDate( input ) ).toBe( expected );
	} );
} );

describe( 'nullish input', () => {
	it.each( [ formatCep, formatCnpj, formatCpf, formatDate, formatPhone ] )(
		'returns an empty string',
		( format ) => {
			expect( format( undefined ) ).toBe( '' );
			expect( format( null ) ).toBe( '' );
		}
	);
} );

describe( 'caret tracking', () => {
	it( 'counts only significant characters before the caret', () => {
		expect( caretIndex( '111.444.777-35', 8 ) ).toBe( 6 );
		expect( caretIndex( '111.444', 0 ) ).toBe( 0 );
	} );

	it( 'finds the offset that follows a number of significant characters', () => {
		expect( caretOffset( '111.444.777-35', 6 ) ).toBe( 7 );
		expect( caretOffset( '111.444.777-35', 99 ) ).toBe( 14 );
	} );

	it( 'round-trips an offset through a reformat', () => {
		const before = '11144477735';
		const index = caretIndex( before, 5 );

		expect( caretOffset( formatCpf( before ), index ) ).toBe( 6 );
	} );
} );

describe( 'maskInput', () => {
	it( 'rewrites the value in place', () => {
		const input = document.createElement( 'input' );
		input.value = '11144477735';

		maskInput( input, formatCpf );

		expect( input.value ).toBe( '111.444.777-35' );
	} );

	it( 'leaves an already formatted value untouched', () => {
		const input = document.createElement( 'input' );
		input.value = '111.444.777-35';

		maskInput( input, formatCpf );

		expect( input.value ).toBe( '111.444.777-35' );
	} );

	it( 'tolerates a missing input', () => {
		expect( () => maskInput( null, formatCpf ) ).not.toThrow();
	} );
} );

describe( 'bindMask', () => {
	it( 'formats the value already present and every later edit', () => {
		const input = document.createElement( 'input' );
		document.body.appendChild( input );
		input.value = '01310100';

		bindMask( input, 'cep' );
		expect( input.value ).toBe( '01310-100' );

		input.value = '20040020';
		input.dispatchEvent( new Event( 'input' ) );
		expect( input.value ).toBe( '20040-020' );
	} );

	it( 'stops formatting once detached', () => {
		const input = document.createElement( 'input' );
		document.body.appendChild( input );

		const unbind = bindMask( input, 'cep' );
		unbind();

		input.value = '20040020';
		input.dispatchEvent( new Event( 'input' ) );
		expect( input.value ).toBe( '20040020' );
	} );

	it( 'ignores an unknown formatter name', () => {
		const input = document.createElement( 'input' );
		input.value = 'raw';

		bindMask( input, 'nope' );

		expect( input.value ).toBe( 'raw' );
	} );
} );
