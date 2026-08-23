/**
 * Dependency-free input masking for Brazilian document and contact fields.
 *
 * Symbols are only appended when a character follows them, which avoids the
 * caret getting stuck on the separator while erasing.
 */

const digitsOnly = ( value, limit ) =>
	String( value ?? '' )
		.replace( /\D/g, '' )
		.slice( 0, limit );

export const formatCep = ( value ) =>
	digitsOnly( value, 8 ).replace( /^(\d{5})(\d)/, '$1-$2' );

export const formatPhone = ( value ) => {
	const digits = digitsOnly( value, 11 );

	if ( digits.length <= 10 ) {
		return digits
			.replace( /^(\d{2})(\d)/, '($1) $2' )
			.replace( /(\d{4})(\d)/, '$1-$2' );
	}

	return digits.replace( /^(\d{2})(\d{5})(\d)/, '($1) $2-$3' );
};

export const formatCpf = ( value ) =>
	digitsOnly( value, 11 )
		.replace( /^(\d{3})(\d)/, '$1.$2' )
		.replace( /(\d{3})(\d)/, '$1.$2' )
		.replace( /(\d{3})(\d)/, '$1-$2' );

// The 2026 CNPJ format allows letters in the first twelve characters.
export const formatCnpj = ( value ) =>
	String( value ?? '' )
		.toUpperCase()
		.replace( /[^A-Z0-9]/g, '' )
		.slice( 0, 14 )
		.replace( /^([A-Z0-9]{2})([A-Z0-9])/, '$1.$2' )
		.replace( /([A-Z0-9]{3})([A-Z0-9])/, '$1.$2' )
		.replace( /([A-Z0-9]{3})([A-Z0-9])/, '$1/$2' )
		.replace( /([A-Z0-9]{4})([A-Z0-9])/, '$1-$2' );

export const formatDate = ( value ) =>
	digitsOnly( value, 8 )
		.replace( /^(\d{2})(\d)/, '$1/$2' )
		.replace( /^(\d{2}\/\d{2})(\d)/, '$1/$2' );

export const formatters = {
	cep: formatCep,
	cnpj: formatCnpj,
	cpf: formatCpf,
	date: formatDate,
	phone: formatPhone,
};

/**
 * How many significant characters sit before the caret.
 *
 * Separators move as a value is reformatted, so the caret is tracked by the
 * character it follows rather than by its offset.
 *
 * @param {string} value Current value.
 * @param {number} caret Caret offset.
 * @return {number} Count of significant characters before the caret.
 */
export function caretIndex( value, caret ) {
	return value.slice( 0, caret ).replace( /[^A-Z0-9]/gi, '' ).length;
}

/**
 * Offset that follows a given number of significant characters.
 *
 * @param {string} value Formatted value.
 * @param {number} index Count of significant characters.
 * @return {number} Caret offset.
 */
export function caretOffset( value, index ) {
	let offset = 0;
	let seen = 0;

	while ( offset < value.length && seen < index ) {
		if ( /[A-Z0-9]/i.test( value[ offset ] ) ) {
			seen++;
		}
		offset++;
	}

	return offset;
}

/**
 * Rewrite an input value through a formatter, keeping the caret on the same
 * character rather than on the same offset.
 *
 * @param {HTMLInputElement}          input  Input to rewrite.
 * @param {(value: string) => string} format Formatter to apply.
 */
export function maskInput( input, format ) {
	if ( ! input ) {
		return;
	}

	const value = input.value || '';
	const nextValue = format( value );

	if ( value === nextValue ) {
		return;
	}

	// Inputs such as type="email" throw when asked for a selection.
	let caret = 0;
	try {
		caret = input.selectionStart || 0;
	} catch {
		caret = 0;
	}

	const target = caretIndex( value, caret );

	input.value = nextValue;

	const position = caretOffset( nextValue, target );

	if ( input.ownerDocument.activeElement === input ) {
		try {
			input.setSelectionRange( position, position );
		} catch {
			// Selection is unavailable for this input type.
		}
	}
}

/**
 * Attach a mask to an input and apply it to the value already present.
 *
 * @param {HTMLInputElement}                   input  Input to mask.
 * @param {string|((value: string) => string)} format Formatter, or a key of `formatters`.
 * @return {() => void} Detaches the mask.
 */
export function bindMask( input, format ) {
	const formatter =
		typeof format === 'function' ? format : formatters[ format ];

	if ( ! input || ! formatter ) {
		return () => {};
	}

	const handler = () => maskInput( input, formatter );

	input.addEventListener( 'input', handler );
	input.addEventListener( 'change', handler );
	handler();

	return () => {
		input.removeEventListener( 'input', handler );
		input.removeEventListener( 'change', handler );
	};
}
