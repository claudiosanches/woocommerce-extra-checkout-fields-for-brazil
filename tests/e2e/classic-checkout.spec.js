const { test, expect } = require( '@playwright/test' );
const {
	ALL_FIELDS,
	VALID,
	goToClassicCheckout,
	orderIdFromUrl,
	orderMetaAll,
	setSettings,
	waitForClassicCheckoutIdle,
} = require( './utils' );

/**
 * Fill everything the shortcode checkout needs apart from the documents.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @return {Promise<void>}
 */
async function fillCommonFields( page ) {
	await page.fill( '#billing_first_name', 'Joao' );
	await page.fill( '#billing_last_name', 'da Silva' );
	await page.fill( '#billing_address_1', 'Avenida Paulista' );
	await page.fill( '#billing_number', '1578' );
	await page.fill( '#billing_neighborhood', 'Bela Vista' );
	await page.fill( '#billing_postcode', '01310100' );
	await page.fill( '#billing_city', 'Sao Paulo' );
	await page.selectOption( '#billing_state', 'SP' );
	await page.fill( '#billing_email', 'classico@example.com' );
	await page.fill( '#billing_phone', '1133334444' );
	await page.fill( '#billing_birthdate', '01/02/1990' );
	await page.selectOption( '#billing_gender', { index: 1 } );
	await page.fill( '#billing_cellphone', '11987654321' );
}

test.describe( 'Classic checkout', () => {
	test.beforeEach( async () => {
		setSettings( ALL_FIELDS );
	} );

	test( 'toggles the document rows with the person type', async ( {
		page,
	} ) => {
		await goToClassicCheckout( page );

		await page.selectOption( '#billing_persontype', '1' );
		await expect( page.locator( '#billing_cpf_field' ) ).toBeVisible();
		await expect( page.locator( '#billing_rg_field' ) ).toBeVisible();
		await expect( page.locator( '#billing_cnpj_field' ) ).toBeHidden();

		await page.selectOption( '#billing_persontype', '2' );
		await expect( page.locator( '#billing_cnpj_field' ) ).toBeVisible();
		await expect( page.locator( '#billing_ie_field' ) ).toBeVisible();
		await expect( page.locator( '#billing_cpf_field' ) ).toBeHidden();

		// WooCommerce hides company by default; a legal person needs it back.
		await expect( page.locator( '#billing_company_field' ) ).toBeVisible();
	} );

	test( 'masks the Brazilian fields as they are typed', async ( {
		page,
	} ) => {
		await goToClassicCheckout( page );
		await page.selectOption( '#billing_persontype', '1' );

		const cases = [
			[ '#billing_cpf', '11144477735', '111.444.777-35' ],
			[ '#billing_postcode', '01310100', '01310-100' ],
			[ '#billing_birthdate', '01021990', '01/02/1990' ],
			[ '#billing_cellphone', '11987654321', '(11) 98765-4321' ],
		];

		for ( const [ selector, typed, expected ] of cases ) {
			await waitForClassicCheckoutIdle( page );
			await page.click( selector );
			await page.type( selector, typed, { delay: 15 } );
			await expect( page.locator( selector ) ).toHaveValue( expected );
		}
	} );

	test( 'suggests a correction for a misspelled email domain', async ( {
		page,
	} ) => {
		await goToClassicCheckout( page );

		await page.fill( '#billing_email', 'alguem@gmial.com' );
		await page.click( '#billing_city' );

		await expect( page.locator( '.wcbcf-mailsuggest' ) ).toContainText(
			'alguem@gmail.com'
		);
	} );

	test( 'refuses a birthdate that is not a real date', async ( { page } ) => {
		await goToClassicCheckout( page );
		await page.selectOption( '#billing_persontype', '1' );
		await page.fill( '#billing_cpf', VALID.cpf );
		await page.fill( '#billing_rg', '123456789' );
		await fillCommonFields( page );
		await waitForClassicCheckoutIdle( page );

		// The mask keeps the shape, so only the calendar can reject this one.
		await page.fill( '#billing_birthdate', '31/02/1990' );

		await page.click( '#place_order' );
		await expect(
			page.locator(
				'.woocommerce-error, .wc-block-components-notice-banner.is-error'
			)
		).toContainText( 'Birthdate', { timeout: 30_000 } );
		expect( orderIdFromUrl( page.url() ) ).toBeNull();
	} );

	test( 'points the error at the field that failed', async ( { page } ) => {
		await goToClassicCheckout( page );
		await page.selectOption( '#billing_persontype', '1' );
		await page.fill( '#billing_rg', '123456789' );
		await fillCommonFields( page );
		await waitForClassicCheckoutIdle( page );

		// Valid shape, wrong check digits, so only the server can reject it.
		await page.fill( '#billing_cpf', '111.444.777-00' );

		await page.click( '#place_order' );

		// WooCommerce puts the field id on the notice so it can point at it.
		const error = page.locator(
			'.woocommerce-error li[data-id="billing_cpf"], .wc-block-components-notice-banner.is-error[data-id="billing_cpf"]'
		);

		await expect( error ).toContainText( 'CPF', { timeout: 30_000 } );
		expect( orderIdFromUrl( page.url() ) ).toBeNull();
	} );

	test( 'fills the State Registration with ISENTO from the exempt box', async ( {
		page,
	} ) => {
		await goToClassicCheckout( page );
		await page.selectOption( '#billing_persontype', '2' );
		await page.fill( '#billing_cnpj', '11222333000181' );
		await page.fill( '#billing_company', 'Isenta Ltda' );
		await fillCommonFields( page );
		await waitForClassicCheckoutIdle( page );

		await page.locator( '.wcbcf-ie-exempt-input' ).check();
		await expect( page.locator( '#billing_ie' ) ).toHaveValue( 'ISENTO' );

		await page.click( '#place_order' );
		await page.waitForURL( /order-received/, { timeout: 45_000 } );

		const orderId = orderIdFromUrl( page.url() );
		expect( orderId ).not.toBeNull();

		expect( orderMetaAll( orderId, [ '_billing_ie' ] )._billing_ie ).toBe(
			'ISENTO'
		);
	} );

	test( 'drops the documents of the person type the customer left behind', async ( {
		page,
	} ) => {
		await goToClassicCheckout( page );

		// The shortcode checkout only hides these rows, so they are still
		// submitted after the customer changes their mind.
		await page.selectOption( '#billing_persontype', '2' );
		await page.fill( '#billing_cnpj', '11222333000181' );
		await page.fill( '#billing_ie', '110042490114' );
		await page.fill( '#billing_company', 'Abandonada Ltda' );

		await page.selectOption( '#billing_persontype', '1' );
		await page.fill( '#billing_cpf', VALID.cpf );
		await page.fill( '#billing_rg', '123456789' );
		await fillCommonFields( page );

		await expect( page.locator( '#billing_cnpj' ) ).toHaveValue(
			VALID.cnpj,
			{ timeout: 5000 }
		);

		await page.click( '#place_order' );
		await page.waitForURL( /order-received/, { timeout: 45_000 } );

		const orderId = orderIdFromUrl( page.url() );
		expect( orderId ).not.toBeNull();

		const meta = orderMetaAll( orderId, [
			'_billing_persontype',
			'_billing_cpf',
			'_billing_cnpj',
			'_billing_ie',
			'_billing_number',
			'_billing_neighborhood',
		] );

		expect( meta._billing_persontype ).toBe( '1' );
		expect( meta._billing_cpf ).toBe( VALID.cpf );
		expect( meta._billing_number ).toBe( '1578' );
		expect( meta._billing_neighborhood ).toBe( 'Bela Vista' );
		expect( meta._billing_cnpj ).toBe( '' );
		expect( meta._billing_ie ).toBe( '' );
	} );
} );
