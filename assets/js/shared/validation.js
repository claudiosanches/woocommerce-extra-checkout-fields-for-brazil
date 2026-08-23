/**
 * Client-side validation for Brazilian documents. Mirrors the checks in
 * Extra_Checkout_Fields_For_Brazil_Formatting.
 */

const sanitize = ( value ) =>
	String( value ?? '' )
		.toUpperCase()
		.replace( /[^A-Z0-9]/g, '' );

// A document made of a single repeated character passes the check digits but is
// never issued.
const isRepeated = ( value ) => /^([A-Z0-9])\1+$/.test( value );

export function isCpf( value ) {
	const cpf = sanitize( value );

	if ( ! /^\d{11}$/.test( cpf ) || isRepeated( cpf ) ) {
		return false;
	}

	const checkDigit = ( slice, weight ) => {
		let total = 0;

		for ( let i = 0; i < slice.length; i++ ) {
			total += Number( slice.charAt( i ) ) * ( weight - i );
		}

		const remainder = ( total * 10 ) % 11;

		return remainder >= 10 ? 0 : remainder;
	};

	return (
		checkDigit( cpf.substring( 0, 9 ), 10 ) === Number( cpf.charAt( 9 ) ) &&
		checkDigit( cpf.substring( 0, 10 ), 11 ) === Number( cpf.charAt( 10 ) )
	);
}

export function isCnpj( value ) {
	const cnpj = sanitize( value );

	if ( ! /^[A-Z0-9]{12}\d{2}$/.test( cnpj ) || isRepeated( cnpj ) ) {
		return false;
	}

	// Letters carry the value of their ASCII code minus 48, per the 2026
	// alphanumeric CNPJ specification.
	const checkDigit = ( slice, weights ) => {
		let total = 0;

		for ( let i = 0; i < slice.length; i++ ) {
			total += ( slice.charCodeAt( i ) - 48 ) * weights[ i ];
		}

		const remainder = total % 11;

		return remainder < 2 ? 0 : 11 - remainder;
	};

	const base = cnpj.substring( 0, 12 );
	const first = checkDigit( base, [ 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ] );
	const second = checkDigit(
		base + String( first ),
		[ 6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ]
	);

	return `${ first }${ second }` === cnpj.substring( 12 );
}

export function isPhone( value ) {
	return /^\d{10,11}$/.test( sanitize( value ) );
}

export function isCellphone( value ) {
	return /^\d{11}$/.test( sanitize( value ) );
}

export function isPostcode( value ) {
	return /^\d{8}$/.test( sanitize( value ) );
}

export function isDate( value ) {
	const match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec( String( value ?? '' ) );

	if ( ! match ) {
		return false;
	}

	const [ , day, month, year ] = match.map( Number );
	const date = new Date( year, month - 1, day );

	return (
		date.getFullYear() === year &&
		date.getMonth() === month - 1 &&
		date.getDate() === day
	);
}
