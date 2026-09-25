/**
 * Address lookup by CEP, through the plugin's WC AJAX endpoint.
 */

export interface PostcodeAddress {
	postcode: string;
	address: string;
	neighborhood: string;
	city: string;
	state: string;
}

interface Response< T > {
	success: boolean;
	data: T | { message?: string };
}

const lookups = new Map< string, Promise< PostcodeAddress | null > >();

// Mirrored by Extra_Checkout_Fields_For_Brazil_Privacy::POSTCODE_COOKIE.
const POSTCODE_COOKIE = 'csbmw_postcode';
const REMEMBER_FOR = 30 * 24 * 60 * 60;

declare global {
	interface Window {
		// Provided by the WP Consent API plugin, when a consent banner uses it.
		wp_has_consent?: ( category: string ) => boolean;
	}
}

/**
 * Whether the visitor agreed to have preferences remembered.
 *
 * @return False without a consent plugin, since nobody was asked.
 */
function remembersPreferences(): boolean {
	return (
		'function' === typeof window.wp_has_consent &&
		window.wp_has_consent( 'preferences' )
	);
}

/**
 * Write the CEP cookie.
 *
 * @param value  Cookie value.
 * @param maxAge Seconds to keep it, or undefined to drop it with the browser
 *               session.
 */
function writePostcodeCookie( value: string, maxAge?: number ): void {
	const parts = [
		`${ POSTCODE_COOKIE }=${ value }`,
		'path=/',
		'SameSite=Lax',
	];

	if ( 'https:' === window.location.protocol ) {
		parts.push( 'Secure' );
	}

	if ( undefined !== maxAge ) {
		parts.push( `max-age=${ maxAge }` );
	}

	document.cookie = parts.join( '; ' );
}

/**
 * Keep the customer's CEP for the product pages they visit next.
 *
 * Until the browser closes, or for thirty days when the visitor agreed to
 * have preferences remembered.
 *
 * @param value CEP, or an empty string to forget it.
 */
export function rememberPostcode( value: string ): void {
	const digits = postcodeDigits( value );

	if ( ! digits ) {
		writePostcodeCookie( '', 0 );

		return;
	}

	writePostcodeCookie(
		digits,
		remembersPreferences() ? REMEMBER_FOR : undefined
	);
}

/**
 * The CEP the customer last used on this site.
 *
 * @return Eight digits, or an empty string.
 */
export function rememberedPostcode(): string {
	const match = new RegExp( `(?:^|;\\s*)${ POSTCODE_COOKIE }=(\\d{8})` ).exec(
		document.cookie
	);

	return match?.[ 1 ] || '';
}

/**
 * Follow the visitor's consent to preferences.
 *
 * The cookie is rewritten to match the current choice, since consent may
 * have changed on a page without this script. Withdrawing it later forgets
 * the CEP at once.
 *
 * @param onWithdraw Clears anything else the caller keeps.
 */
export function followPostcodeConsent( onWithdraw: () => void ): void {
	const digits = rememberedPostcode();

	if ( digits && 'function' === typeof window.wp_has_consent ) {
		rememberPostcode( digits );
	}

	document.addEventListener( 'wp_listen_for_consent_change', ( event ) => {
		const changed =
			( event as CustomEvent< Record< string, string > > ).detail || {};

		if ( 'deny' === changed.preferences ) {
			rememberPostcode( '' );
			onWithdraw();
		} else if ( 'allow' === changed.preferences ) {
			rememberPostcode( rememberedPostcode() );
		}
	} );
}

/**
 * Digits of a complete CEP, or an empty string.
 *
 * @param value CEP as typed.
 * @return Eight digits, or an empty string.
 */
export function postcodeDigits( value: string | null | undefined ): string {
	const digits = String( value ?? '' ).replace( /\D/g, '' );

	return 8 === digits.length ? digits : '';
}

/**
 * Send a GET request to a WC AJAX endpoint.
 *
 * @param url    Endpoint URL.
 * @param params Query arguments.
 * @return Response data.
 * @throws Error carrying the server's message, or an empty one when the
 *         request itself failed, so the caller shows its own text instead of
 *         the browser's.
 */
export async function getJson< T >(
	url: string,
	params: Record< string, string >
): Promise< T > {
	const target = new window.URL( url, window.location.href );

	Object.entries( params ).forEach( ( [ key, value ] ) =>
		target.searchParams.set( key, value )
	);

	let body: Response< T >;

	try {
		const response = await window.fetch( target.toString(), {
			credentials: 'same-origin',
		} );

		body = ( await response.json() ) as Response< T >;
	} catch {
		throw new Error( '' );
	}

	if ( ! body.success ) {
		const data = body.data as { message?: string };

		throw new Error( data?.message || '' );
	}

	return body.data as T;
}

/**
 * Address for a CEP, asked once per page.
 *
 * @param url      Endpoint URL.
 * @param postcode CEP, with or without the hyphen.
 * @return Address, or null when the CEP is invalid or unknown.
 */
export function lookupPostcode(
	url: string,
	postcode: string
): Promise< PostcodeAddress | null > {
	const digits = postcodeDigits( postcode );

	if ( ! digits || ! url ) {
		return Promise.resolve( null );
	}

	let lookup = lookups.get( digits );

	if ( ! lookup ) {
		lookup = getJson< PostcodeAddress >( url, { postcode: digits } ).catch(
			() => {
				// A failure is not cached, so the customer can retry.
				lookups.delete( digits );

				return null;
			}
		);
		lookups.set( digits, lookup );
	}

	return lookup;
}

/**
 * One line describing an address.
 *
 * @param address Address.
 * @return Street, neighborhood, city and state, skipping the empty ones.
 */
export function describeAddress( address: PostcodeAddress ): string {
	const place = `${ address.city } - ${ address.state }`;

	return [ address.address, address.neighborhood, place ]
		.filter( Boolean )
		.join( ', ' );
}

export type AutofillKey = 'address_1' | 'neighborhood' | 'city' | 'state';

export type AutofillValues = Record< AutofillKey, string >;

const AUTOFILL_KEYS: AutofillKey[] = [
	'address_1',
	'neighborhood',
	'city',
	'state',
];

/**
 * Values to write into an address form once a lookup answers.
 *
 * A field the customer edited while the lookup ran is left alone. A field the
 * address has no value for, as with a CEP covering a whole city, is cleared
 * only when it still holds what an earlier lookup put there.
 *
 * @param address Address found.
 * @param current Form values now.
 * @param started Form values when the lookup started.
 * @param filled  Values earlier lookups wrote.
 * @return Values to write, keyed by field.
 */
export function planAutofill(
	address: PostcodeAddress,
	current: AutofillValues,
	started: AutofillValues,
	filled: Partial< AutofillValues >
): Partial< AutofillValues > {
	const found: AutofillValues = {
		address_1: address.address,
		neighborhood: address.neighborhood,
		city: address.city,
		state: address.state,
	};
	const plan: Partial< AutofillValues > = {};

	AUTOFILL_KEYS.forEach( ( key ) => {
		if ( current[ key ] !== started[ key ] ) {
			return;
		}

		if ( found[ key ] ) {
			plan[ key ] = found[ key ];
		} else if ( current[ key ] && current[ key ] === filled[ key ] ) {
			plan[ key ] = '';
		}
	} );

	return plan;
}

interface AutofillOptions {
	url: string;
	postcode: () => string;
	read: () => AutofillValues;
	write: ( values: Partial< AutofillValues > ) => void;
}

/**
 * Fill an address form from its CEP once the CEP is complete.
 *
 * @param options          Form bindings.
 * @param options.url      Lookup endpoint.
 * @param options.postcode Reads the CEP field.
 * @param options.read     Reads the address fields.
 * @param options.write    Writes address fields.
 * @return Call whenever the CEP may have changed.
 */
export function createAutofill( {
	url,
	postcode,
	read,
	write,
}: AutofillOptions ): () => void {
	let lookedUp = '';
	let filled: Partial< AutofillValues > = {};

	return () => {
		const digits = postcodeDigits( postcode() );

		if ( ! digits || digits === lookedUp ) {
			return;
		}

		lookedUp = digits;

		const started = read();

		lookupPostcode( url, digits ).then( ( address ) => {
			// A newer CEP was typed, or this one was edited, while it ran.
			if (
				lookedUp !== digits ||
				postcodeDigits( postcode() ) !== digits
			) {
				return;
			}

			if ( ! address ) {
				// Unknown or failed: let the same CEP be asked again.
				lookedUp = '';

				return;
			}

			const plan = planAutofill( address, read(), started, filled );

			filled = { ...filled, ...plan };
			write( plan );
			rememberPostcode( digits );
		} );
	};
}
