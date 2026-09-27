const { test, expect } = require( '@playwright/test' );
const {
	ALL_FIELDS,
	goToBlockCheckout,
	goToClassicCheckout,
	orderIdFromUrl,
	orderMetaAll,
	setSettings,
	waitForClassicCheckoutIdle,
} = require( './utils' );

test.describe( 'Phone country codes', () => {
	test.beforeEach( async () => {
		setSettings( ALL_FIELDS );
	} );

	test( 'reads a pasted +55 as a Brazilian number', async ( { page } ) => {
		await goToBlockCheckout( page );

		const cellphone = page.locator( '#contact-csbmw-cellphone' );
		await cellphone.fill( '' );
		await cellphone.pressSequentially( '+55 11 98765-4321' );
		await expect( cellphone ).toHaveValue( '(11) 98765-4321' );

		await goToClassicCheckout( page );

		const phone = page.locator( '#billing_phone' );
		await phone.fill( '' );
		await phone.pressSequentially( '5511912345678' );
		await expect( phone ).toHaveValue( '(11) 91234-5678' );
	} );

	test( 'keeps a Brazilian number and suggests a foreign code on a foreign block checkout order', async ( {
		page,
	} ) => {
		await goToBlockCheckout( page );

		await page.fill( '#email', 'phones@example.com' );
		await page.fill( '#contact-csbmw-birthdate', '01021990' );
		await page.selectOption( '#contact-csbmw-gender', 'female' );
		await page.locator( '#contact-csbmw-cellphone' ).fill( '' );
		await page
			.locator( '#contact-csbmw-cellphone' )
			.pressSequentially( '11987654321' );
		await page.selectOption( '#billing-country', 'US' );

		// Typed for Brazil, so it keeps Brazil's code.
		await expect( page.locator( '#contact-csbmw-cellphone' ) ).toHaveValue(
			'+55 (11) 98765-4321'
		);

		await page.fill( '#billing-first_name', 'Jane' );
		await page.fill( '#billing-last_name', 'Doe' );
		await page.fill( '#billing-address_1', '1 Main Street' );
		await page.fill( '#billing-city', 'Beverly Hills' );
		await page.selectOption( '#billing-state', 'CA' );
		await page.fill( '#billing-postcode', '90210' );
		await page.locator( '#billing-phone' ).fill( '' );
		await page
			.locator( '#billing-phone' )
			.pressSequentially( '2125551234' );
		await expect( page.locator( '#billing-phone' ) ).toHaveValue(
			'+1 2125551234'
		);

		await page.waitForTimeout( 2000 );
		await page.click(
			'button.wc-block-components-checkout-place-order-button'
		);
		await page.waitForURL( /order-received/, { timeout: 45_000 } );

		expect(
			orderMetaAll( orderIdFromUrl( page.url() ), [
				'_billing_phone',
				'_billing_cellphone',
			] )
		).toEqual( {
			_billing_phone: '+1 2125551234',
			_billing_cellphone: '+55 (11) 98765-4321',
		} );
	} );

	test( 'carries a classic phone over to a new country', async ( {
		page,
	} ) => {
		await goToClassicCheckout( page );
		await page.selectOption( '#billing_country', 'BR' );
		await waitForClassicCheckoutIdle( page );

		const phone = page.locator( '#billing_phone' );
		await phone.fill( '' );
		await phone.pressSequentially( '11987654321' );
		await page.selectOption( '#billing_country', 'PT' );
		await expect( phone ).toHaveValue( '+55 (11) 98765-4321' );

		await page.selectOption( '#billing_country', 'BR' );
		await expect( phone ).toHaveValue( '(11) 98765-4321' );
	} );

	test( 'picks a country code from the list', async ( { page } ) => {
		setSettings( { ...ALL_FIELDS, phone_country_picker: 1 } );
		await goToBlockCheckout( page );

		const cellphone = page.locator( '#contact-csbmw-cellphone' );
		const picker = page.locator(
			'.wcbcf-phone-code[data-bmw-for="contact-csbmw-cellphone"] select'
		);

		await expect( picker ).toHaveValue( 'BR' );
		await expect( picker.locator( 'option' ).first() ).toHaveAttribute(
			'value',
			'BR'
		);

		await cellphone.fill( '' );
		await picker.selectOption( 'GB' );
		await expect( cellphone ).toHaveValue( '+44' );
		await cellphone.pressSequentially( '2079460958' );
		await expect( cellphone ).toHaveValue( '+44 2079460958' );

		await goToClassicCheckout( page );
		await page.selectOption( '#billing_country', 'BR' );
		await waitForClassicCheckoutIdle( page );

		const phone = page.locator( '#billing_phone' );
		await phone.fill( '' );
		await phone.pressSequentially( '11987654321' );
		await page
			.locator( '.wcbcf-phone-code[data-bmw-for="billing_phone"] select' )
			.selectOption( 'US' );
		await expect( phone ).toHaveValue( '+1 11987654321' );
	} );
} );
