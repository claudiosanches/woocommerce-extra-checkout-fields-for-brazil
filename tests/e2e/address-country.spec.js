const { test, expect } = require( '@playwright/test' );
const {
	ALL_FIELDS,
	goToBlockCheckout,
	goToClassicCheckout,
	logIn,
	orderIdFromUrl,
	orderMetaAll,
	seedLegacyCustomer,
	setSettings,
	waitForClassicCheckoutIdle,
	wpCli,
} = require( './utils' );

const CUSTOMER = { user: 'csbmw_legacy', pass: 'csbmw-e2e-password' };

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
	await page.waitForSelector( '#shipping-postcode' );
	await page.waitForLoadState( 'networkidle' );
}

test.describe( 'Number and Neighborhood by country', () => {
	test.beforeEach( async () => {
		setSettings( ALL_FIELDS );
	} );

	test( 'follow each block checkout address on its own', async ( {
		page,
	} ) => {
		await goToShippedBlockCheckout( page );

		await expect( page.locator( '#shipping-csbmw-number' ) ).toBeVisible();
		await expect(
			page.locator( '#shipping-csbmw-neighborhood' )
		).toBeVisible();

		// The CEP sits beside the street it fills in.
		await page.setViewportSize( { width: 1280, height: 900 } );
		const top = async ( id ) =>
			( await page.locator( id ).boundingBox() ).y;
		expect( await top( '#shipping-address_1' ) ).toBe(
			await top( '#shipping-postcode' )
		);

		await page
			.getByLabel( 'Use same address for billing' )
			.setChecked( false );
		await page.selectOption( '#billing-country', 'US' );

		await expect( page.locator( '#billing-csbmw-number' ) ).toBeHidden();
		await expect(
			page.locator( '#billing-csbmw-neighborhood' )
		).toBeHidden();
		await expect( page.locator( '#shipping-csbmw-number' ) ).toBeVisible();

		await page.selectOption( '#billing-country', 'BR' );
		await expect( page.locator( '#billing-csbmw-number' ) ).toBeVisible();
	} );

	test( 'are not asked of a foreign address on the block checkout', async ( {
		page,
	} ) => {
		await goToBlockCheckout( page );

		await page.fill( '#email', 'foreign@example.com' );
		await page.fill( '#contact-csbmw-birthdate', '01021990' );
		await page.selectOption( '#contact-csbmw-gender', 'female' );
		await page.fill( '#contact-csbmw-cellphone', '11987654321' );
		await page.selectOption( '#billing-country', 'US' );
		await page.fill( '#billing-first_name', 'Jane' );
		await page.fill( '#billing-last_name', 'Doe' );
		await page.fill( '#billing-address_1', '1 Main Street' );
		await page.fill( '#billing-city', 'Beverly Hills' );
		await page.selectOption( '#billing-state', 'CA' );
		await page.fill( '#billing-postcode', '90210' );

		await page.waitForTimeout( 2000 );
		await page.click(
			'button.wc-block-components-checkout-place-order-button'
		);
		await page.waitForURL( /order-received/, { timeout: 45_000 } );

		const meta = orderMetaAll( orderIdFromUrl( page.url() ), [
			'_billing_number',
			'_billing_neighborhood',
		] );

		expect( meta ).toEqual( {
			_billing_number: '',
			_billing_neighborhood: '',
		} );
	} );

	test( 'are not asked of a foreign address on the classic checkout', async ( {
		page,
	} ) => {
		await goToClassicCheckout( page );

		await expect( page.locator( '#billing_number_field' ) ).toBeVisible();

		// A Brazilian address typed first, then abandoned.
		await page.fill( '#billing_number', '1578' );
		await page.selectOption( '#billing_country', 'US' );

		await expect( page.locator( '#billing_number_field' ) ).toBeHidden();
		await expect(
			page.locator( '#billing_neighborhood_field' )
		).toBeHidden();

		await page.fill( '#billing_first_name', 'Jane' );
		await page.fill( '#billing_last_name', 'Doe' );
		await page.fill( '#billing_address_1', '1 Main Street' );
		await page.fill( '#billing_city', 'Beverly Hills' );
		await page.selectOption( '#billing_state', 'CA' );
		await page.fill( '#billing_postcode', '90210' );
		await page.fill( '#billing_email', 'foreign@example.com' );
		await page.fill( '#billing_birthdate', '01/02/1990' );
		await page.selectOption( '#billing_gender', { index: 1 } );
		await page.fill( '#billing_cellphone', '11987654321' );

		await waitForClassicCheckoutIdle( page );
		await page.click( '#place_order' );
		await page.waitForURL( /order-received/, { timeout: 45_000 } );

		expect(
			orderMetaAll( orderIdFromUrl( page.url() ), [ '_billing_number' ] )
		).toEqual( { _billing_number: '' } );
	} );

	test( 'come back with a Brazilian address, laid out in pairs', async ( {
		page,
	} ) => {
		setSettings( { ...ALL_FIELDS, no_number: 1 } );
		await page.setViewportSize( { width: 1280, height: 900 } );
		await goToClassicCheckout( page );

		const noNumber = page.locator( 'button.wcbcf-no-number' ).first();
		await noNumber.click();

		await page.selectOption( '#billing_country', 'US' );
		await page.selectOption( '#billing_country', 'BR' );

		// WooCommerce emptied the hidden field, and the toggle follows.
		await expect( page.locator( '#billing_number' ) ).toHaveValue( '' );
		await expect( noNumber ).toHaveAttribute( 'aria-checked', 'false' );
		await expect( page.locator( '#billing_number' ) ).toBeEditable();

		const top = async ( key ) =>
			( await page.locator( `#billing_${ key }_field` ).boundingBox() ).y;

		await expect( page.locator( '#billing_number_field' ) ).toBeVisible();
		await expect(
			page.locator( '#billing_number_field label .required' )
		).toBeVisible();

		for ( const [ first, last ] of [
			[ 'postcode', 'address_1' ],
			[ 'number', 'neighborhood' ],
			[ 'city', 'state' ],
			[ 'phone', 'cellphone' ],
		] ) {
			expect( await top( last ), `${ first } and ${ last }` ).toBe(
				await top( first )
			);
		}

		// The second address line has a row of its own under the street.
		expect( await top( 'address_2' ) ).toBeGreaterThan(
			await top( 'address_1' )
		);
		expect( await top( 'number' ) ).toBeGreaterThan(
			await top( 'address_2' )
		);
	} );

	test( 'are dropped from a foreign address in My Account', async ( {
		page,
	} ) => {
		seedLegacyCustomer();
		await logIn( page, CUSTOMER.user, CUSTOMER.pass );
		await page.goto( '/my-account/edit-address/billing/', {
			waitUntil: 'domcontentloaded',
		} );

		const number = page.locator( '[id="csbmw/number"]' );
		await expect( number ).toBeVisible();

		await page.selectOption( '#billing_country', 'US' );
		await expect( number ).toBeHidden();

		await page.fill( '#billing_city', 'Beverly Hills' );
		await page.selectOption( '#billing_state', 'CA' );
		await page.fill( '#billing_postcode', '90210' );
		await page.selectOption( '#billing_gender', { index: 1 } );
		await page.click( 'button[name="save_address"]' );

		await expect(
			page.getByText( 'Address changed successfully' )
		).toBeVisible();
		expect(
			wpCli( [
				'eval',
				`$u = get_user_by( 'login', 'csbmw_legacy' ); echo ( new WC_Customer( $u->ID ) )->get_billing_country();`,
			] )
		).toBe( 'US' );
	} );

	test( 'takes digits only in Number', async ( { page } ) => {
		await goToClassicCheckout( page );
		await page.locator( '#billing_number' ).pressSequentially( '1-12A' );
		await expect( page.locator( '#billing_number' ) ).toHaveValue( '112' );

		await goToBlockCheckout( page );
		await page
			.locator( '#billing-csbmw-number' )
			.pressSequentially( '1-12A' );
		await expect( page.locator( '#billing-csbmw-number' ) ).toHaveValue(
			'112'
		);
		await expect( page.locator( '#billing-csbmw-number' ) ).toHaveAttribute(
			'inputmode',
			'numeric'
		);
	} );
} );
