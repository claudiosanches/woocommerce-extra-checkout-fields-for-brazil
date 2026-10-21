const { test, expect } = require( '@playwright/test' );
const {
	ALL_FIELDS,
	goToBlockCheckout,
	goToClassicCheckout,
	setSettings,
	waitForClassicCheckoutIdle,
} = require( './utils' );

/**
 * Settings with the cell phone option set to one of its four values.
 *
 * @param {string} cellPhone Value of the cell_phone setting.
 * @return {Object} Settings to store.
 */
const withCellPhone = ( cellPhone ) => ( {
	...ALL_FIELDS,
	cell_phone: cellPhone,
} );

const blockCellPhone = '#contact-csbmw-cellphone';

/**
 * The rendered label of a block field.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @param {string}                          id   Input id, without the hash.
 * @return {import('@playwright/test').Locator} The label element.
 */
const blockLabel = ( page, id ) => page.locator( `label[for="${ id }"]` );

test.describe( 'Cell Phone setting on the block checkout', () => {
	test( 'renames the phone field when set to relabel', async ( { page } ) => {
		setSettings( withCellPhone( '-1' ) );
		await goToBlockCheckout( page );

		// The block spells the optional wording out rather than appending it,
		// so the renamed field needs both halves of the label.
		await expect( blockLabel( page, 'billing-phone' ) ).toHaveText(
			'Cell Phone (optional)'
		);

		// The relabel replaces the phone field rather than adding to it.
		await expect( page.locator( blockCellPhone ) ).toHaveCount( 0 );
	} );

	test( 'adds an optional cell phone field when set to optional', async ( {
		page,
	} ) => {
		setSettings( withCellPhone( '1' ) );
		await goToBlockCheckout( page );

		await expect( page.locator( blockCellPhone ) ).toBeVisible();
		await expect(
			blockLabel( page, 'contact-csbmw-cellphone' )
		).toContainText( 'optional' );

		// The core phone field keeps its own name here.
		await expect( blockLabel( page, 'billing-phone' ) ).not.toContainText(
			'Cell Phone'
		);
	} );

	test( 'adds a required cell phone field when set to required', async ( {
		page,
	} ) => {
		setSettings( withCellPhone( '2' ) );
		await goToBlockCheckout( page );

		await expect( page.locator( blockCellPhone ) ).toBeVisible();
		await expect(
			blockLabel( page, 'contact-csbmw-cellphone' )
		).not.toContainText( 'optional' );
	} );

	test( 'adds nothing when disabled', async ( { page } ) => {
		setSettings( withCellPhone( '0' ) );
		await goToBlockCheckout( page );

		await expect( page.locator( blockCellPhone ) ).toHaveCount( 0 );
		await expect( blockLabel( page, 'billing-phone' ) ).not.toContainText(
			'Cell Phone'
		);
	} );

	test( 'gives the classic checkout the same label', async ( { page } ) => {
		setSettings( withCellPhone( '-1' ) );
		await goToClassicCheckout( page );

		const label = page.locator( 'label[for="billing_phone"]' );

		await expect( label ).toContainText( 'Cell Phone' );
		await expect( page.locator( '#billing_cellphone' ) ).toHaveCount( 0 );

		// WooCommerce rewrites every label from the country locale whenever the
		// address changes, a second or so after the form settles. That is what
		// used to put "Phone" back over the one PHP had rendered, so the label
		// has to be read again once the rewrite has had its chance, and it has
		// to hold outside Brazil too.
		for ( const country of [ 'PT', 'BR' ] ) {
			await page.selectOption( '#billing_country', country );
			await waitForClassicCheckoutIdle( page );

			// Read once rather than retrying, so a label that was right and
			// then got overwritten still fails.
			expect( await label.textContent() ).toContain( 'Cell Phone' );
		}
	} );
} );
