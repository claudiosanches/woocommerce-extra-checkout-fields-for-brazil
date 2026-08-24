const { test, expect } = require( '@playwright/test' );
const {
	ALL_FIELDS,
	goToBlockCheckout,
	seedLegacyCustomer,
	setSettings,
	wpCli,
} = require( './utils' );

const CUSTOMER = { user: 'csbmw_legacy', pass: 'csbmw-e2e-password' };

/**
 * Read one meta value from the fixture customer.
 *
 * @param {string} key Meta key.
 * @return {string} Stored value.
 */
function customerMeta( key ) {
	return wpCli( [
		'eval',
		`$u = get_user_by( 'login', 'csbmw_legacy' ); $c = new WC_Customer( $u->ID ); echo (string) $c->get_meta( '${ key }' );`,
	] );
}

/**
 * Log the fixture customer in.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @return {Promise<void>}
 */
async function logIn( page ) {
	await page.goto( '/wp-login.php', { waitUntil: 'domcontentloaded' } );
	await page.fill( '#user_login', CUSTOMER.user );
	await page.fill( '#user_pass', CUSTOMER.pass );
	await page.click( '#wp-submit' );

	// Waiting for the load alone can resolve before the auth cookie is set,
	// and the next navigation is then bounced back to this form.
	await page.waitForURL(
		( url ) => ! url.pathname.includes( 'wp-login.php' ),
		{ timeout: 30_000 }
	);
}

test.describe( 'My account', () => {
	test.beforeEach( async () => {
		setSettings( ALL_FIELDS );
		// Specs here edit the customer, so each starts from the same data.
		seedLegacyCustomer();
	} );

	test( 'shows Number and Neighborhood once each', async ( { page } ) => {
		await logIn( page );
		await page.goto( '/my-account/edit-address/billing/', {
			waitUntil: 'domcontentloaded',
		} );

		// A session that did not take would land on the login form and leave
		// every count at zero, which reads as a passing duplication check.
		await expect(
			page.locator( 'button[name="save_address"]' )
		).toBeVisible();

		// WooCommerce renders its own copy of every registered address field,
		// so the historic pair has to stand down or both would show.
		for ( const key of [ 'number', 'neighborhood' ] ) {
			await expect(
				page.locator( `label[for="csbmw/${ key }"]` ),
				`one ${ key } label`
			).toHaveCount( 1 );
			await expect(
				page.locator( `label[for="billing_${ key }"]` ),
				`no historic ${ key } label`
			).toHaveCount( 0 );
			await expect( page.locator( `#billing_${ key }` ) ).toHaveCount(
				0
			);
		}

		// The documents are untouched; only the duplicated pair stands down.
		await expect( page.locator( '#billing_cpf' ) ).toHaveCount( 1 );
	} );

	test( 'does not mangle a historic birthdate when the address is saved', async ( {
		page,
	} ) => {
		await logIn( page );
		await page.goto( '/my-account/edit-address/billing/', {
			waitUntil: 'domcontentloaded',
		} );

		// Stored as 1/1/1980, which the date mask would otherwise reformat into
		// 11/19/80 and then save over the original.
		await expect( page.locator( '#billing_birthdate' ) ).toHaveValue(
			'01/01/1980'
		);

		await page.selectOption( '#billing_gender', { index: 1 } );
		await page.click( 'button[name="save_address"]' );
		await page.waitForURL( /my-account\/edit-address/, {
			timeout: 30_000,
		} );

		expect( customerMeta( 'billing_birthdate' ) ).toBe( '01/01/1980' );
	} );

	test( 'saving the address writes both meta families', async ( {
		page,
	} ) => {
		await logIn( page );
		await page.goto( '/my-account/edit-address/billing/', {
			waitUntil: 'domcontentloaded',
		} );

		await page.fill( '[id="csbmw/number"]', '777' );
		await page.fill( '[id="csbmw/neighborhood"]', 'Pinheiros' );
		// Gender is required here and the fixture stores an untranslated label.
		await page.selectOption( '#billing_gender', { index: 1 } );

		await page.click( 'button[name="save_address"]' );
		await page.waitForURL( /my-account\/edit-address/, {
			timeout: 30_000,
		} );

		expect( customerMeta( 'billing_number' ) ).toBe( '777' );
		expect( customerMeta( '_wc_billing/csbmw/number' ) ).toBe( '777' );
		expect( customerMeta( 'billing_neighborhood' ) ).toBe( 'Pinheiros' );
		expect( customerMeta( '_wc_billing/csbmw/neighborhood' ) ).toBe(
			'Pinheiros'
		);
	} );

	test( 'prefills the block checkout from the historic meta', async ( {
		page,
	} ) => {
		await logIn( page );
		await goToBlockCheckout( page );

		// The fixture customer's data was written under the historic keys only,
		// as a store upgrading from 4.0.2 would have it.
		await expect( page.locator( '#contact-csbmw-persontype' ) ).toHaveValue(
			'1'
		);
		await expect( page.locator( '#contact-csbmw-cpf' ) ).toHaveValue(
			'123.456.789-09'
		);
		await expect( page.locator( '#contact-csbmw-rg' ) ).toHaveValue(
			'998877'
		);
		await expect( page.locator( '#contact-csbmw-cellphone' ) ).toHaveValue(
			'(11) 91111-2222'
		);

		// A birthdate the classic checkout never format-checked is normalised
		// rather than prefilled in a shape the block would reject.
		await expect( page.locator( '#contact-csbmw-birthdate' ) ).toHaveValue(
			'01/01/1980'
		);

		// An untranslated gender label still resolves to its stable key.
		await expect( page.locator( '#contact-csbmw-gender' ) ).toHaveValue(
			'female'
		);
	} );
} );
