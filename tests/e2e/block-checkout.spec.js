const { test, expect } = require( '@playwright/test' );
const {
	ALL_FIELDS,
	VALID,
	goToBlockCheckout,
	orderIdFromUrl,
	orderMetaAll,
	setSettings,
} = require( './utils' );

const field = ( key ) => `#contact-csbmw-${ key }`;

/**
 * Fill in everything the checkout needs apart from the documents.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @return {Promise<void>}
 */
async function fillCommonFields( page ) {
	await page.fill( '#email', 'comprador@example.com' );
	await page.fill( field( 'birthdate' ), '01021990' );
	await page.selectOption( field( 'gender' ), 'female' );
	await page.fill( field( 'cellphone' ), '11987654321' );

	await page.fill( '#billing-first_name', 'Joao' );
	await page.fill( '#billing-last_name', 'da Silva' );
	await page.fill( '#billing-address_1', 'Avenida Paulista' );
	await page.fill( '#billing-csbmw-number', '1578' );
	await page.fill( '#billing-csbmw-neighborhood', 'Bela Vista' );
	await page.fill( '#billing-postcode', '01310100' );
	await page.fill( '#billing-city', 'Sao Paulo' );
	await page.selectOption( '#billing-state', 'SP' );
}

// Only the error banner; the page also carries an "added to cart" success one.
const ERROR_BANNER = '.wc-block-components-notice-banner.is-error';

/**
 * Submit the checkout and wait for either the confirmation or an error.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @return {Promise<void>}
 */
async function placeOrder( page ) {
	// Let the block push the last edits to the Store API before submitting.
	await page.waitForTimeout( 2000 );
	await page.click(
		'button.wc-block-components-checkout-place-order-button'
	);

	await Promise.race( [
		page.waitForURL( /order-received/, { timeout: 45_000 } ),
		page.locator( ERROR_BANNER ).first().waitFor( { timeout: 45_000 } ),
	] );
}

test.describe( 'Block checkout', () => {
	test.beforeEach( async () => {
		setSettings( ALL_FIELDS );
	} );

	test( 'shows the documents of the selected person type only', async ( {
		page,
	} ) => {
		await goToBlockCheckout( page );

		// Nothing is assumed before the customer chooses.
		await expect( page.locator( field( 'persontype' ) ) ).toBeVisible();
		await expect( page.locator( field( 'cpf' ) ) ).toBeHidden();
		await expect( page.locator( field( 'cnpj' ) ) ).toBeHidden();

		await page.selectOption( field( 'persontype' ), '1' );
		await expect( page.locator( field( 'cpf' ) ) ).toBeVisible();
		await expect( page.locator( field( 'rg' ) ) ).toBeVisible();
		await expect( page.locator( field( 'cnpj' ) ) ).toBeHidden();
		await expect( page.locator( field( 'ie' ) ) ).toBeHidden();

		await page.selectOption( field( 'persontype' ), '2' );
		await expect( page.locator( field( 'cnpj' ) ) ).toBeVisible();
		await expect( page.locator( field( 'ie' ) ) ).toBeVisible();
		await expect( page.locator( field( 'cpf' ) ) ).toBeHidden();
		await expect( page.locator( field( 'rg' ) ) ).toBeHidden();
	} );

	test( 'masks the Brazilian fields as they are typed', async ( {
		page,
	} ) => {
		await goToBlockCheckout( page );
		await page.selectOption( field( 'persontype' ), '1' );

		// Changing the person type re-renders the block and pushes to the
		// Store API. A keystroke landing while that render commits is dropped
		// by React, so let it settle before typing.
		await page.waitForTimeout( 1500 );

		const cases = [
			[ field( 'cpf' ), '11144477735', '111.444.777-35' ],
			[ field( 'birthdate' ), '01021990', '01/02/1990' ],
			[ field( 'cellphone' ), '11987654321', '(11) 98765-4321' ],
			[ '#billing-postcode', '01310100', '01310-100' ],
			[ '#billing-phone', '1133334444', '(11) 3333-4444' ],
		];

		for ( const [ selector, typed, expected ] of cases ) {
			await page.click( selector );
			await page.type( selector, typed, { delay: 15 } );
			await expect( page.locator( selector ) ).toHaveValue( expected );
		}

		await page.selectOption( field( 'persontype' ), '2' );
		await page.waitForTimeout( 1500 );
		await page.click( field( 'cnpj' ) );
		await page.type( field( 'cnpj' ), '11222333000181', { delay: 15 } );
		await expect( page.locator( field( 'cnpj' ) ) ).toHaveValue(
			VALID.cnpj
		);
	} );

	test( 'suggests a correction for a misspelled email domain', async ( {
		page,
	} ) => {
		await goToBlockCheckout( page );

		await page.fill( '#email', 'alguem@gmial.com' );
		await page.click( '#billing-city' );

		await expect( page.locator( '.wcbcf-mailsuggest' ) ).toContainText(
			'alguem@gmail.com'
		);
	} );

	test( 'refuses an order whose CPF fails its check digits', async ( {
		page,
	} ) => {
		await goToBlockCheckout( page );
		await page.selectOption( field( 'persontype' ), '1' );
		await fillCommonFields( page );
		await page.fill( field( 'cpf' ), '111.444.777-00' );
		await page.fill( field( 'rg' ), '123456789' );

		await placeOrder( page );

		await expect( page.locator( ERROR_BANNER ) ).toContainText(
			'CPF is not valid'
		);
		expect( orderIdFromUrl( page.url() ) ).toBeNull();
	} );

	test( 'stores an individual order under both meta families', async ( {
		page,
	} ) => {
		await goToBlockCheckout( page );
		await page.selectOption( field( 'persontype' ), '1' );
		await fillCommonFields( page );
		await page.fill( field( 'cpf' ), VALID.cpf );
		await page.fill( field( 'rg' ), '123456789' );

		await placeOrder( page );

		const orderId = orderIdFromUrl( page.url() );
		expect( orderId ).not.toBeNull();

		const meta = orderMetaAll( orderId, [
			'_billing_persontype',
			'_billing_cpf',
			'_wc_other/csbmw/cpf',
			'_billing_rg',
			'_wc_other/csbmw/rg',
			'_billing_birthdate',
			'_billing_gender',
			'_wc_other/csbmw/gender',
			'_billing_cellphone',
			'_billing_number',
			'_wc_billing/csbmw/number',
			'_billing_neighborhood',
			'_wc_billing/csbmw/neighborhood',
		] );

		expect( meta ).toMatchObject( {
			_billing_persontype: '1',
			_billing_cpf: VALID.cpf,
			'_wc_other/csbmw/cpf': VALID.cpf,
			_billing_rg: '123456789',
			'_wc_other/csbmw/rg': '123456789',
			_billing_birthdate: '01/02/1990',
			_billing_cellphone: '(11) 98765-4321',
			_billing_number: '1578',
			'_wc_billing/csbmw/number': '1578',
			_billing_neighborhood: 'Bela Vista',
			'_wc_billing/csbmw/neighborhood': 'Bela Vista',
		} );

		// Gender is a stable key in the block store and a label in the historic one.
		expect( meta[ '_wc_other/csbmw/gender' ] ).toBe( 'female' );
		expect( meta._billing_gender ).toBe( 'Female' );
	} );

	test( 'requires a company from a legal person', async ( { page } ) => {
		await goToBlockCheckout( page );
		await page.selectOption( field( 'persontype' ), '2' );
		await fillCommonFields( page );
		await page.fill( field( 'cnpj' ), VALID.cnpj );
		await page.fill( field( 'ie' ), '110042490114' );

		await placeOrder( page );

		await expect( page.locator( ERROR_BANNER ) ).toContainText( 'Company' );
		expect( orderIdFromUrl( page.url() ) ).toBeNull();

		await page.fill( '#billing-company', 'Acme Comercio Ltda' );
		await placeOrder( page );

		const orderId = orderIdFromUrl( page.url() );
		expect( orderId ).not.toBeNull();

		expect(
			orderMetaAll( orderId, [ '_billing_cnpj', '_billing_ie' ] )
		).toEqual( {
			_billing_cnpj: VALID.cnpj,
			_billing_ie: '110042490114',
		} );
	} );

	test( 'accepts an alphanumeric CNPJ typed in lower case', async ( {
		page,
	} ) => {
		await goToBlockCheckout( page );
		await page.selectOption( field( 'persontype' ), '2' );
		await fillCommonFields( page );
		await page.fill( '#billing-company', 'Acme Comercio Ltda' );
		await page.fill( field( 'ie' ), '110042490114' );

		// The 2026 format allows letters in the first twelve characters. The
		// mask has to upper case them as they are typed, since the check
		// digits are derived from the upper case ASCII values.
		await page.click( field( 'cnpj' ) );
		await page.type( field( 'cnpj' ), '12abc34501de35', { delay: 15 } );
		await expect( page.locator( field( 'cnpj' ) ) ).toHaveValue(
			VALID.cnpjAlphanumeric
		);

		await placeOrder( page );

		const orderId = orderIdFromUrl( page.url() );
		expect( orderId ).not.toBeNull();

		expect(
			orderMetaAll( orderId, [ '_billing_cnpj', '_wc_other/csbmw/cnpj' ] )
		).toEqual( {
			_billing_cnpj: VALID.cnpjAlphanumeric,
			'_wc_other/csbmw/cnpj': VALID.cnpjAlphanumeric,
		} );
	} );

	test( 'rejects an alphanumeric CNPJ with wrong check digits', async ( {
		page,
	} ) => {
		await goToBlockCheckout( page );
		await page.selectOption( field( 'persontype' ), '2' );
		await fillCommonFields( page );
		await page.fill( '#billing-company', 'Acme Comercio Ltda' );
		await page.fill( field( 'ie' ), '110042490114' );
		await page.fill( field( 'cnpj' ), '12.ABC.345/01DE-34' );

		await placeOrder( page );

		await expect( page.locator( ERROR_BANNER ) ).toContainText( 'CNPJ' );
		expect( orderIdFromUrl( page.url() ) ).toBeNull();
	} );

	test( 'fills the State Registration with ISENTO from the exempt box', async ( {
		page,
	} ) => {
		await goToBlockCheckout( page );
		await page.selectOption( field( 'persontype' ), '2' );
		await page.waitForTimeout( 1500 );
		await fillCommonFields( page );
		await page.fill( '#billing-company', 'Acme Comercio Ltda' );
		await page.fill( field( 'cnpj' ), VALID.cnpj );

		const exempt = page.locator( '.wcbcf-ie-exempt-input' );
		await expect( exempt ).toBeVisible();

		await exempt.check();
		await expect( page.locator( field( 'ie' ) ) ).toHaveValue( 'ISENTO' );

		await placeOrder( page );

		const orderId = orderIdFromUrl( page.url() );
		expect( orderId ).not.toBeNull();

		// The exemption is only ever a value, so nothing downstream has to
		// know the checkbox exists.
		expect(
			orderMetaAll( orderId, [ '_billing_ie', '_wc_other/csbmw/ie' ] )
		).toEqual( {
			_billing_ie: 'ISENTO',
			'_wc_other/csbmw/ie': 'ISENTO',
		} );
	} );

	test( 'clears the State Registration when the exempt box is unticked', async ( {
		page,
	} ) => {
		await goToBlockCheckout( page );
		await page.selectOption( field( 'persontype' ), '2' );
		await page.waitForTimeout( 1500 );

		const exempt = page.locator( '.wcbcf-ie-exempt-input' );

		await exempt.check();
		await expect( page.locator( field( 'ie' ) ) ).toHaveValue( 'ISENTO' );

		await exempt.uncheck();
		await expect( page.locator( field( 'ie' ) ) ).toHaveValue( '' );
		await expect( page.locator( field( 'ie' ) ) ).toBeEditable();
	} );

	test( 'drops the documents of the person type the customer left behind', async ( {
		page,
	} ) => {
		await goToBlockCheckout( page );

		// Fill the company documents, then change your mind.
		await page.selectOption( field( 'persontype' ), '2' );
		await page.fill( field( 'cnpj' ), VALID.cnpj );
		await page.fill( field( 'ie' ), '110042490114' );
		await page.fill( '#billing-company', 'Abandonada Ltda' );

		await page.selectOption( field( 'persontype' ), '1' );
		await fillCommonFields( page );
		await page.fill( field( 'cpf' ), VALID.cpf );
		await page.fill( field( 'rg' ), '123456789' );

		await placeOrder( page );

		const orderId = orderIdFromUrl( page.url() );
		expect( orderId ).not.toBeNull();

		const meta = orderMetaAll( orderId, [
			'_billing_cpf',
			'_billing_cnpj',
			'_billing_ie',
			'_wc_other/csbmw/cnpj',
		] );

		expect( meta._billing_cpf ).toBe( VALID.cpf );
		expect( meta._billing_cnpj ).toBe( '' );
		expect( meta._billing_ie ).toBe( '' );
		expect( meta[ '_wc_other/csbmw/cnpj' ] ).toBe( '' );
	} );
} );
