import { obscureCpf, obscureRg } from '../../assets/js/shared/obscure';

describe( 'obscureCpf', () => {
	it.each( [
		[ '111.444.777-35', '***.444.777-**' ],
		[ '11144477735', '***444777**' ],
	] )( 'shows the middle six digits of %s', ( value, expected ) => {
		expect( obscureCpf( value ) ).toBe( expected );
	} );
} );

describe( 'obscureRg', () => {
	it.each( [
		[ '27.527.879-7', '**.527.879-*' ],
		[ 'MG-12.345.678', '**-12.345.67*' ],
		[ '12.345.678-X', '**.345.678-*' ],
	] )(
		'hides the leading digits and the check digit of %s',
		( value, expected ) => {
			expect( obscureRg( value ) ).toBe( expected );
		}
	);

	it( 'hides a short RG whole', () => {
		expect( obscureRg( '1234' ) ).toBe( '****' );
	} );
} );
