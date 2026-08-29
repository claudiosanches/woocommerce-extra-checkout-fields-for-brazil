const { test, expect } = require( '@playwright/test' );
const { ALL_FIELDS, logIn, setSettings } = require( './utils' );

const ADMIN = { user: 'admin', pass: 'password' };

const SETTINGS =
	'/wp-admin/admin.php?page=woocommerce-extra-checkout-fields-for-brazil';

const row = ( page, name ) => page.locator( `.bmw-row-${ name }` );

test.describe( 'Settings screen', () => {
	test.beforeEach( async ( { page } ) => {
		setSettings( ALL_FIELDS );
		await logIn( page, ADMIN.user, ADMIN.pass );
		await page.goto( SETTINGS );
	} );

	test( 'shows only the options the person type applies to', async ( {
		page,
	} ) => {
		await page.selectOption( '#person_type', '0' );
		await expect( row( page, 'only-brazil' ) ).toBeHidden();
		await expect( row( page, 'rg' ) ).toBeHidden();
		await expect( row( page, 'ie' ) ).toBeHidden();
		await expect( page.locator( '.bmw-section-validation' ) ).toBeHidden();

		await page.selectOption( '#person_type', '1' );
		await expect( row( page, 'only-brazil' ) ).toBeVisible();
		await expect( row( page, 'rg' ) ).toBeVisible();
		await expect( row( page, 'ie' ) ).toBeVisible();
		await expect( row( page, 'validate-cpf' ) ).toBeVisible();
		await expect( row( page, 'validate-cnpj' ) ).toBeVisible();

		// Individuals have no CNPJ to check, and legal persons no CPF.
		await page.selectOption( '#person_type', '2' );
		await expect( row( page, 'rg' ) ).toBeVisible();
		await expect( row( page, 'ie' ) ).toBeHidden();
		await expect( row( page, 'validate-cpf' ) ).toBeVisible();
		await expect( row( page, 'validate-cnpj' ) ).toBeHidden();

		await page.selectOption( '#person_type', '3' );
		await expect( row( page, 'rg' ) ).toBeHidden();
		await expect( row( page, 'ie' ) ).toBeVisible();
		await expect( row( page, 'validate-cpf' ) ).toBeHidden();
		await expect( row( page, 'validate-cnpj' ) ).toBeVisible();
	} );

	test( 'leaves the unrelated options alone', async ( { page } ) => {
		// The switch inputs are visually hidden, so the rows are what to look
		// at. Every one of these belongs to a setting the person type has no
		// say over.
		for ( const personType of [ '0', '1', '2', '3' ] ) {
			await page.selectOption( '#person_type', personType );

			await expect( page.locator( '.bmw-section-jquery' ) ).toBeVisible();
			await expect(
				page.locator( 'label[for="mailcheck"]' )
			).toBeVisible();
			await expect(
				page.locator( 'label[for="maskedinput"]' )
			).toBeVisible();
			await expect(
				page.locator( 'label[for="birthdate"]' )
			).toBeVisible();
			await expect( page.locator( '#fields_style' ) ).toBeVisible();
		}
	} );

	test( 'keeps a shown row whole', async ( { page } ) => {
		await page.selectOption( '#person_type', '1' );

		// A row that applies is shown with its heading, not stripped of it.
		await expect( row( page, 'rg' ).locator( 'h3' ) ).toBeVisible();
		await expect( row( page, 'rg' ).locator( 'h3' ) ).toHaveText(
			'Display RG'
		);
	} );

	test( 'labels every select', async ( { page } ) => {
		for ( const id of [ 'person_type', 'cell_phone', 'fields_style' ] ) {
			await expect( page.locator( `label[for="${ id }"]` ) ).toHaveCount(
				1
			);
		}
	} );
} );
