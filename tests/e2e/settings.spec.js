const { test, expect } = require( '@playwright/test' );
const { ALL_FIELDS, logIn, setSettings } = require( './utils' );

const ADMIN = { user: 'admin', pass: 'password' };

const SETTINGS =
	'/wp-admin/admin.php?page=woocommerce-extra-checkout-fields-for-brazil';

const row = ( page, name ) => page.locator( `.bmw-row-${ name }` );

const tab = ( page, name ) => page.getByRole( 'tab', { name } );

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
		await expect( row( page, 'company' ) ).toBeHidden();
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
		await expect( row( page, 'company' ) ).toBeHidden();
		await expect( row( page, 'rg' ) ).toBeVisible();
		await expect( row( page, 'ie' ) ).toBeHidden();
		await expect( row( page, 'validate-cpf' ) ).toBeVisible();
		await expect( row( page, 'validate-cnpj' ) ).toBeHidden();

		await page.selectOption( '#person_type', '3' );
		await expect( row( page, 'company' ) ).toBeVisible();
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
			await tab( page, 'Fields' ).click();
			await page.selectOption( '#person_type', personType );

			await expect(
				page.locator( 'label[for="birthdate"]' )
			).toBeVisible();
			await expect( page.locator( '#fields_style' ) ).toBeVisible();

			await tab( page, 'Features' ).click();
			await expect(
				page.locator( '.bmw-section-helpers' )
			).toBeVisible();
			await expect(
				page.locator( 'label[for="mailcheck"]' )
			).toBeVisible();
			await expect(
				page.locator( 'label[for="maskedinput"]' )
			).toBeVisible();
		}
	} );

	test( 'shows one tab at a time', async ( { page } ) => {
		await expect( tab( page, 'Fields' ) ).toHaveAttribute(
			'aria-selected',
			'true'
		);
		await expect( page.locator( '.bmw-section-fields' ) ).toBeVisible();
		await expect( page.locator( '.bmw-section-shipping' ) ).toBeHidden();

		await tab( page, 'Shipping' ).click();

		await expect( page.locator( '.bmw-section-shipping' ) ).toBeVisible();
		await expect( page.locator( '.bmw-section-fields' ) ).toBeHidden();
		await expect( page.locator( '.bmw-section-validation' ) ).toBeHidden();
		await expect( page ).toHaveURL( /[?&]tab=shipping/ );

		// A reload comes back to the same tab.
		await page.reload();
		await expect( page.locator( '.bmw-section-shipping' ) ).toBeVisible();
		await expect( page.locator( '.bmw-section-fields' ) ).toBeHidden();
	} );

	test( 'keeps the tab hidden when the person type shows its card', async ( {
		page,
	} ) => {
		await tab( page, 'Features' ).click();
		await page.evaluate( () =>
			window.jQuery( '#person_type' ).val( '1' ).trigger( 'change' )
		);

		await expect( page.locator( '.bmw-section-validation' ) ).toBeHidden();
	} );

	test( 'saves every tab and comes back to the open one', async ( {
		page,
	} ) => {
		await page.selectOption( '#rg', 'optional' );
		await tab( page, 'Features' ).click();
		await page.locator( 'label[for="mailcheck"]' ).click();
		await page.click( '#submit' );

		await expect( page ).toHaveURL( /[?&]tab=features/ );
		await expect( page.locator( '.notice-success' ) ).toBeVisible();
		await expect( page.locator( '#mailcheck' ) ).not.toBeChecked();
		await expect( page.locator( '#rg' ) ).toHaveValue( 'optional' );
	} );

	test( 'keeps a shown row whole', async ( { page } ) => {
		await page.selectOption( '#person_type', '1' );

		// A row that applies is shown with its heading, not stripped of it.
		await expect( row( page, 'rg' ).locator( 'h3' ) ).toBeVisible();
		await expect( row( page, 'rg' ).locator( 'h3' ) ).toHaveText( 'RG' );
	} );

	test( 'labels every select', async ( { page } ) => {
		for ( const id of [
			'person_type',
			'company',
			'rg',
			'ie',
			'birthdate',
			'gender',
			'cell_phone',
			'fields_style',
		] ) {
			await expect( page.locator( `label[for="${ id }"]` ) ).toHaveCount(
				1
			);
		}
	} );

	test( 'saves a field as optional', async ( { page } ) => {
		// The fields were checkboxes, and a ticked one meant required.
		await expect( page.locator( '#rg' ) ).toHaveValue( 'required' );

		await page.selectOption( '#rg', 'optional' );
		await page.selectOption( '#gender', '' );
		await page.click( '#submit' );

		await expect( page.locator( '#rg' ) ).toHaveValue( 'optional' );
		await expect( page.locator( '#gender' ) ).toHaveValue( '' );
		await expect( page.locator( '#company' ) ).toHaveValue( 'dynamic' );
	} );
} );
