const { test, expect } = require( '@playwright/test' );
const {
	ALL_FIELDS,
	VALID,
	goToBlockCheckout,
	orderMetaAll,
	setSettings,
	wpCli,
} = require( './utils' );

/**
 * Open a checkout session and return a helper that posts to the Store API
 * directly, with the real cookies and nonce but none of the block UI.
 *
 * This is the path a crafted request takes, so it is where "the browser would
 * never send that" stops being an argument.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @return {Promise<Object>} Helpers bound to the session.
 */
async function apiSession( page ) {
	let nonce = '';

	page.on( 'request', ( request ) => {
		const header = request.headers().nonce;

		if ( header ) {
			nonce = header;
		}
	} );

	await goToBlockCheckout( page );

	// Nudge the block into a write so the nonce header appears.
	await page.fill( '#billing-city', 'Sao Paulo' );
	await expect.poll( () => nonce, { timeout: 30_000 } ).not.toBe( '' );

	const post = ( url, payload ) =>
		page.evaluate(
			async ( [ endpoint, body, storeNonce ] ) => {
				const response = await fetch( endpoint, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						Nonce: storeNonce,
					},
					credentials: 'same-origin',
					body: JSON.stringify( body ),
				} );

				return { status: response.status, body: await response.json() };
			},
			[ url, payload, nonce ]
		);

	const productId = wpCli( [ 'option', 'get', 'csbmw_e2e_product' ] );

	return {
		post,
		// Placing an order empties the cart, so refill before each attempt.
		refill: () =>
			post( '/wp-json/wc/store/v1/cart/add-item', {
				id: Number( productId ),
				quantity: 1,
			} ),
	};
}

const ADDRESS = {
	first_name: 'Joao',
	last_name: 'da Silva',
	company: '',
	address_1: 'Avenida Paulista',
	address_2: '',
	city: 'Sao Paulo',
	state: 'SP',
	postcode: '01310-100',
	country: 'BR',
	email: 'api@example.com',
	phone: '(11) 3333-4444',
	'csbmw/number': '1578',
	'csbmw/neighborhood': 'Bela Vista',
};

const CONTACT = {
	'csbmw/persontype': '1',
	'csbmw/cpf': VALID.cpf,
	'csbmw/rg': '123456789',
	'csbmw/birthdate': '01/02/1990',
	'csbmw/gender': 'female',
	'csbmw/cellphone': '(11) 98765-4321',
};

/**
 * Attempt a checkout with the given overrides.
 *
 * @param {Object} session API session.
 * @param {Object} contact Contact field overrides.
 * @param {Object} address Address overrides.
 * @return {Promise<Object>} Status, order id and error messages.
 */
async function attempt( session, contact = {}, address = {} ) {
	await session.refill();

	const result = await session.post( '/wp-json/wc/store/v1/checkout', {
		billing_address: { ...ADDRESS, ...address },
		shipping_address: { ...ADDRESS, ...address },
		payment_method: 'cod',
		additional_fields: { ...CONTACT, ...contact },
		customer_note: '',
	} );

	const messages = [ result.body.message || '' ].concat(
		Object.values( result.body.data?.params || {} )
	);

	return {
		accepted: result.status < 400,
		orderId: result.body.order_id || null,
		message: messages.join( ' ' ).replace( /<[^>]+>/g, '' ),
	};
}

test.describe( 'Store API validation cannot be skipped', () => {
	test.beforeEach( async () => {
		setSettings( ALL_FIELDS );
	} );

	test( 'refuses malformed documents', async ( { page } ) => {
		const session = await apiSession( page );

		const cases = [
			[
				'CPF with bad check digits',
				{ 'csbmw/cpf': '111.444.777-00' },
				'CPF is not valid',
			],
			[
				'CPF of repeated digits',
				{ 'csbmw/cpf': '111.111.111-11' },
				'CPF is not valid',
			],
			[
				'CPF too short',
				{ 'csbmw/cpf': '1114447773' },
				'CPF is not valid',
			],
			[
				'CPF of letters',
				{ 'csbmw/cpf': 'abcdefghijk' },
				'CPF is not valid',
			],
			[
				'impossible birthdate',
				{ 'csbmw/birthdate': '31/02/1990' },
				'Birthdate is not valid',
			],
			[
				'birthdate in month/day order',
				{ 'csbmw/birthdate': '02/28/1990' },
				'Birthdate is not valid',
			],
			[
				'birthdate as free text',
				{ 'csbmw/birthdate': 'ontem' },
				'Birthdate is not valid',
			],
		];

		for ( const [ label, contact, expected ] of cases ) {
			const result = await attempt( session, contact );

			expect( result.accepted, label ).toBe( false );
			expect( result.message, label ).toContain( expected );
		}
	} );

	test( 'refuses a malformed CNPJ', async ( { page } ) => {
		const session = await apiSession( page );

		const result = await attempt(
			session,
			{
				'csbmw/persontype': '2',
				'csbmw/cnpj': '11.222.333/0001-00',
				'csbmw/ie': '110042490114',
			},
			{ company: 'Acme' }
		);

		expect( result.accepted ).toBe( false );
		expect( result.message ).toContain( 'CNPJ is not valid' );
	} );

	test( 'refuses missing required fields', async ( { page } ) => {
		const session = await apiSession( page );

		const cases = [
			[ 'CPF omitted', { 'csbmw/cpf': undefined } ],
			[ 'CPF empty', { 'csbmw/cpf': '' } ],
			[ 'CPF only whitespace', { 'csbmw/cpf': '   ' } ],
			[ 'person type omitted', { 'csbmw/persontype': '' } ],
			[ 'cellphone empty', { 'csbmw/cellphone': '' } ],
		];

		for ( const [ label, contact ] of cases ) {
			expect(
				( await attempt( session, contact ) ).accepted,
				label
			).toBe( false );
		}

		for ( const key of [ 'csbmw/number', 'csbmw/neighborhood' ] ) {
			const result = await attempt( session, {}, { [ key ]: '' } );
			expect( result.accepted, key ).toBe( false );
		}
	} );

	test( 'refuses values outside a select', async ( { page } ) => {
		const session = await apiSession( page );

		expect(
			( await attempt( session, { 'csbmw/gender': 'hacker' } ) ).accepted
		).toBe( false );
		expect(
			( await attempt( session, { 'csbmw/persontype': '9' } ) ).accepted
		).toBe( false );
	} );

	test( 'accepts a well formed order, formatted or not', async ( {
		page,
	} ) => {
		const session = await apiSession( page );

		expect( ( await attempt( session ) ).accepted ).toBe( true );
		expect(
			( await attempt( session, { 'csbmw/cpf': '11144477735' } ) )
				.accepted
		).toBe( true );
		expect(
			(
				await attempt(
					session,
					{
						'csbmw/persontype': '2',
						'csbmw/cnpj': VALID.cnpjAlphanumeric,
						'csbmw/ie': '110042490114',
					},
					{ company: 'Acme' }
				)
			).accepted
		).toBe( true );
	} );

	test( 'still requires a company from a legal person', async ( {
		page,
	} ) => {
		const session = await apiSession( page );

		for ( const company of [ '', '   ' ] ) {
			const result = await attempt(
				session,
				{
					'csbmw/persontype': '2',
					'csbmw/cnpj': VALID.cnpj,
					'csbmw/ie': '110042490114',
				},
				{ company }
			);

			expect( result.accepted, JSON.stringify( company ) ).toBe( false );
			expect( result.message ).toContain( 'Company' );
		}
	} );

	test( 'does not store documents smuggled into hidden fields', async ( {
		page,
	} ) => {
		const session = await apiSession( page );

		// The CNPJ is hidden for an individual, so it is never validated. It
		// must not survive onto the order either.
		const individual = await attempt( session, {
			'csbmw/persontype': '1',
			'csbmw/cnpj': '<script>alert(1)</script>',
			'csbmw/ie': 'JUNK-IE',
		} );

		expect( individual.accepted ).toBe( true );
		expect(
			orderMetaAll( individual.orderId, [
				'_billing_cnpj',
				'_billing_ie',
				'_wc_other/csbmw/cnpj',
			] )
		).toEqual( {
			_billing_cnpj: '',
			_billing_ie: '',
			'_wc_other/csbmw/cnpj': '',
		} );

		const company = await attempt(
			session,
			{
				'csbmw/persontype': '2',
				'csbmw/cnpj': VALID.cnpj,
				'csbmw/ie': '110042490114',
				'csbmw/cpf': '000.000.000-00',
				'csbmw/rg': 'JUNK-RG',
			},
			{ company: 'Acme' }
		);

		expect( company.accepted ).toBe( true );
		expect(
			orderMetaAll( company.orderId, [ '_billing_cpf', '_billing_rg' ] )
		).toEqual( { _billing_cpf: '', _billing_rg: '' } );
	} );

	test( 'caps an oversized value instead of storing it whole', async ( {
		page,
	} ) => {
		const session = await apiSession( page );

		const result = await attempt( session, {
			'csbmw/rg': 'A'.repeat( 5000 ),
		} );

		expect( result.accepted ).toBe( true );
		expect(
			orderMetaAll( result.orderId, [ '_billing_rg' ] )._billing_rg
		).toHaveLength( 20 );
	} );
} );
