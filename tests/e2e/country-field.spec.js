const { test, expect } = require( '@playwright/test' );
const {
	goToBlockCheckout,
	goToClassicCheckout,
	logIn,
	orderIdFromUrl,
	seedLegacyCustomer,
	setSettings,
	shipEverywhere,
	shipOnlyToBrazil,
	waitForClassicCheckoutIdle,
	wpCli,
} = require( './utils' );

const COUNTRY_ROW = ( group ) =>
	`#${ group }-fields .wc-block-components-address-form__country`;

/**
 * Countries an order was placed with.
 *
 * @param {number} orderId Order ID.
 * @return {string} Billing and shipping country, comma separated.
 */
function orderCountries( orderId ) {
	return wpCli( [
		'eval',
		`$o = wc_get_order( ${ orderId } ); echo $o->get_billing_country() . ',' . $o->get_shipping_country();`,
	] );
}

/**
 * Put the physical product in the cart and open the block checkout.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @return {Promise<void>}
 */
async function goToShippedBlockCheckout( page ) {
	const productId = wpCli( [ 'option', 'get', 'csbmw_e2e_shipped_product' ] );

	await page.goto( `/?add-to-cart=${ productId }`, {
		waitUntil: 'domcontentloaded',
	} );
	await page.goto( '/checkout/', { waitUntil: 'domcontentloaded' } );
	await page.waitForSelector( '#shipping-first_name', { state: 'attached' } );
	await page.waitForLoadState( 'networkidle' );
}

/**
 * Countries the block checkout holds for each address.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @return {Promise<string[]>} Billing and shipping country.
 */
function cartCountries( page ) {
	return page.evaluate( () => {
		const data = window.wp.data.select( 'wc/store/cart' ).getCustomerData();

		return [ data.billingAddress.country, data.shippingAddress.country ];
	} );
}

test.describe( 'Country field on a Brazil-only store', () => {
	test.beforeEach( () => {
		shipOnlyToBrazil();

		// No default location, so WooCommerce leaves a guest without a country.
		wpCli( [ 'option', 'update', 'woocommerce_default_country', 'US:CA' ] );
	} );

	test.afterEach( () => {
		wpCli( [ 'option', 'update', 'woocommerce_default_country', 'BR:SP' ] );
		wpCli( [ 'option', 'update', 'woocommerce_ship_to_countries', '' ] );
		shipEverywhere();
	} );

	test( 'hides the country on the block checkout and still orders from Brazil', async ( {
		page,
	} ) => {
		setSettings( { country_field: 'hidden' } );
		await goToBlockCheckout( page );

		await expect( page.locator( COUNTRY_ROW( 'billing' ) ) ).toBeHidden();
		await expect
			.poll( async () => ( await cartCountries( page ) )[ 0 ] )
			.toBe( 'BR' );
		await expect( page.locator( '#billing-csbmw-number' ) ).toBeVisible();

		await page.fill( '#email', 'pais@example.com' );
		await page.fill( '#billing-first_name', 'Joao' );
		await page.fill( '#billing-last_name', 'da Silva' );
		await page.fill( '#billing-address_1', 'Avenida Paulista' );
		await page.fill( '#billing-csbmw-number', '1578' );
		await page.fill( '#billing-postcode', '01310100' );
		await page.fill( '#billing-city', 'Sao Paulo' );
		await page.selectOption( '#billing-state', 'SP' );

		// Let the block push the last edits to the Store API before submitting.
		await page.waitForTimeout( 2000 );
		await page.click(
			'button.wc-block-components-checkout-place-order-button'
		);
		await page.waitForURL( /order-received/, { timeout: 45_000 } );

		expect( orderCountries( orderIdFromUrl( page.url() ) ) ).toMatch(
			/^BR,/
		);
	} );

	test( 'shows Brazil as text on the block checkout', async ( { page } ) => {
		setSettings( { country_field: 'text' } );
		await goToShippedBlockCheckout( page );

		const row = page.locator( COUNTRY_ROW( 'shipping' ) );

		await expect( row ).toBeVisible();
		await expect( row ).toHaveCSS( 'pointer-events', 'none' );
		await expect( page.locator( '#shipping-country' ) ).toHaveValue( 'BR' );
		await expect(
			row.locator( '.wc-blocks-components-select__expand' )
		).toBeHidden();
		await expect( page.locator( '#shipping-csbmw-number' ) ).toBeVisible();
	} );

	test( 'decides each block checkout address on its own', async ( {
		page,
	} ) => {
		setSettings( { country_field: 'hidden' } );

		// Sells everywhere, ships only to Brazil.
		shipEverywhere();
		wpCli( [
			'option',
			'update',
			'woocommerce_ship_to_countries',
			'specific',
		] );
		wpCli( [
			'option',
			'update',
			'woocommerce_specific_ship_to_countries',
			'["BR"]',
			'--format=json',
		] );

		await goToShippedBlockCheckout( page );

		await expect( page.locator( COUNTRY_ROW( 'shipping' ) ) ).toBeHidden();
		await expect
			.poll( async () => ( await cartCountries( page ) )[ 1 ] )
			.toBe( 'BR' );

		await page
			.getByLabel( 'Use same address for billing' )
			.setChecked( false );
		await expect( page.locator( COUNTRY_ROW( 'billing' ) ) ).toBeVisible();
	} );

	test( 'leaves the select to WooCommerce once another country is allowed', async ( {
		page,
	} ) => {
		setSettings( { country_field: 'hidden' } );
		shipEverywhere();
		await goToBlockCheckout( page );

		await expect( page.locator( COUNTRY_ROW( 'billing' ) ) ).toBeVisible();
		await expect(
			page.locator( '#billing-country option[value="US"]' )
		).toHaveCount( 1 );
	} );

	test( 'hides the country on the classic checkout and still orders from Brazil', async ( {
		page,
	} ) => {
		setSettings( { country_field: 'hidden' } );
		await goToClassicCheckout( page );

		await expect( page.locator( '#billing_country_field' ) ).toHaveCount(
			0
		);
		await expect( page.locator( '#billing_country' ) ).toHaveValue( 'BR' );

		// The Brazilian locale still applies without the field.
		await expect( page.locator( '#billing_number' ) ).toBeVisible();
		await expect( page.locator( '#billing_state' ) ).toHaveJSProperty(
			'tagName',
			'SELECT'
		);

		await page.fill( '#billing_first_name', 'Joao' );
		await page.fill( '#billing_last_name', 'da Silva' );
		await page.fill( '#billing_postcode', '01310100' );
		await page.fill( '#billing_address_1', 'Avenida Paulista' );
		await page.fill( '#billing_number', '1578' );
		await page.fill( '#billing_city', 'Sao Paulo' );
		await page.selectOption( '#billing_state', 'SP' );
		await page.fill( '#billing_email', 'pais-classico@example.com' );
		await waitForClassicCheckoutIdle( page );
		await page.click( '#place_order' );
		await page.waitForURL( /order-received/, { timeout: 45_000 } );

		expect( orderCountries( orderIdFromUrl( page.url() ) ) ).toMatch(
			/^BR,/
		);
	} );

	test( 'hides the country in My Account and keeps Brazil', async ( {
		page,
	} ) => {
		setSettings( { country_field: 'hidden' } );
		seedLegacyCustomer();
		await logIn( page, 'csbmw_legacy', 'csbmw-e2e-password' );
		await page.goto( '/my-account/edit-address/billing/', {
			waitUntil: 'domcontentloaded',
		} );

		await expect( page.locator( '#billing_country_field' ) ).toHaveCount(
			0
		);
		await page.click( 'button[name="save_address"]' );
		await expect(
			page.getByText( 'Address changed successfully.' )
		).toBeVisible();

		expect(
			wpCli( [
				'eval',
				"echo ( new WC_Customer( get_user_by( 'login', 'csbmw_legacy' )->ID ) )->get_billing_country();",
			] )
		).toBe( 'BR' );
	} );
} );
