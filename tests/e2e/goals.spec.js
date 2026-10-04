const { test, expect } = require( '@playwright/test' );
const {
	ALL_FIELDS,
	setSettings,
	shipEverywhere,
	shipOnlyToBrazil,
	wpCli,
} = require( './utils' );

const SETTINGS = {
	...ALL_FIELDS,
	goals_cart: 1,
	goals_checkout: 1,
	goals_product: 1,
	goals_free_shipping: 1,
};

const FREE_SHIPPING = 'Add R$50.00 more to get free shipping.';
const ALL_REACHED = 'You got free shipping and 10% off.';

/**
 * Add free shipping from R$150 to the Brazil zone, and a 10% coupon from
 * R$300 marked as a goal.
 *
 * @return {{instance: number, coupon: number}} What to remove afterwards.
 */
function createGoals() {
	return JSON.parse(
		wpCli( [
			'eval',
			`$zone = WC_Shipping_Zones::get_zone_matching_package( array( 'destination' => array( 'country' => 'BR', 'state' => 'SP', 'postcode' => '01001000' ) ) );
			$instance = $zone->add_shipping_method( 'free_shipping' );
			update_option( "woocommerce_free_shipping_{$instance}_settings", array( 'title' => 'Frete grátis E2E', 'requires' => 'min_amount', 'min_amount' => '150', 'ignore_discounts' => 'no' ) );
			WC_Cache_Helper::get_transient_version( 'shipping', true );
			$coupon = new WC_Coupon();
			$coupon->set_code( 'metae2e' );
			$coupon->set_discount_type( 'percent' );
			$coupon->set_amount( 10 );
			$coupon->set_minimum_amount( 300 );
			$coupon->update_meta_data( '_csbmw_spending_goal', 'yes' );
			echo wp_json_encode( array( 'instance' => $instance, 'coupon' => $coupon->save(), 'zone' => $zone->get_id() ) );`,
		] )
	);
}

/**
 * Remove what createGoals() added.
 *
 * @param {{instance: number, coupon: number, zone: number}} goals IDs.
 */
function removeGoals( goals ) {
	wpCli( [
		'eval',
		`( new WC_Shipping_Zone( ${ goals.zone } ) )->delete_shipping_method( ${ goals.instance } );
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		wp_delete_post( ${ goals.coupon }, true );`,
	] );
}

/**
 * Put the physical product, at R$50, in the cart.
 *
 * @param {import('@playwright/test').Page} page     Page.
 * @param {number}                          quantity Quantity.
 */
async function addProduct( page, quantity ) {
	const productId = wpCli( [ 'option', 'get', 'csbmw_e2e_shipped_product' ] );

	await page.goto( `/?add-to-cart=${ productId }&quantity=${ quantity }`, {
		waitUntil: 'domcontentloaded',
	} );
}

test.describe( 'Spending goals', () => {
	let goals;

	test.beforeAll( () => {
		shipOnlyToBrazil();
		goals = createGoals();
	} );

	test.afterAll( () => {
		if ( goals ) {
			removeGoals( goals );
		}
		shipEverywhere();
	} );

	test.beforeEach( () => {
		setSettings( SETTINGS );
	} );

	test( 'counts toward each goal in the cart block and applies the coupon', async ( {
		page,
	} ) => {
		await addProduct( page, 2 );
		await page.goto( '/cart/', { waitUntil: 'domcontentloaded' } );

		const bar = page.locator( '.csbmw-goals' );

		await expect( bar.locator( '.csbmw-goals-message' ) ).toHaveText(
			FREE_SHIPPING
		);
		await expect( bar.locator( '.csbmw-goals-milestone' ) ).toHaveText( [
			/free shipping\s*R\$150\.00/i,
			/10% off\s*R\$300\.00/,
		] );
		await expect( bar.getByRole( 'progressbar' ) ).toHaveAttribute(
			'aria-valuenow',
			'33'
		);

		await addProduct( page, 4 );
		await page.goto( '/cart/', { waitUntil: 'domcontentloaded' } );

		await expect( bar.locator( '.csbmw-goals-message' ) ).toHaveText(
			ALL_REACHED
		);
		await expect(
			page.locator( '.wc-block-components-totals-discount' )
		).toContainText( 'metae2e' );
	} );

	test( 'shows in the checkout block', async ( { page } ) => {
		await addProduct( page, 2 );
		await page.goto( '/checkout/', { waitUntil: 'domcontentloaded' } );

		await expect( page.locator( '.csbmw-goals-message' ) ).toHaveText(
			FREE_SHIPPING
		);
	} );

	test( 'follows the classic cart and keeps a removed coupon off', async ( {
		page,
	} ) => {
		const classicCart = wpCli( [
			'option',
			'get',
			'csbmw_e2e_classic_cart_page',
		] );

		await addProduct( page, 2 );
		await page.goto( `/?page_id=${ classicCart }`, {
			waitUntil: 'domcontentloaded',
		} );

		const message = page.locator( '.csbmw-goals-message' );

		await expect( message ).toHaveText( FREE_SHIPPING );

		await page.locator( 'input.qty' ).first().fill( '6' );
		await page.locator( 'button[name="update_cart"]' ).click();
		await expect( message ).toHaveText( ALL_REACHED );
		await expect( page.locator( '.cart-discount' ) ).toContainText(
			'metae2e'
		);

		await page.locator( '.woocommerce-remove-coupon' ).click();
		await expect( page.locator( '.cart-discount' ) ).toHaveCount( 0 );
		await page.reload( { waitUntil: 'domcontentloaded' } );
		await expect( page.locator( '.cart-discount' ) ).toHaveCount( 0 );
		await expect( message ).toHaveText( 'You got free shipping.' );
	} );

	test( 'shows in the classic checkout', async ( { page } ) => {
		const classicCheckout = wpCli( [
			'option',
			'get',
			'csbmw_e2e_classic_page',
		] );

		await addProduct( page, 2 );
		await page.goto( `/?page_id=${ classicCheckout }`, {
			waitUntil: 'domcontentloaded',
		} );

		await expect(
			page.locator( '.csbmw-goals-row .csbmw-goals-message' )
		).toHaveText( FREE_SHIPPING );
	} );

	test( 'follows the cart on a product page', async ( { page } ) => {
		const productId = wpCli( [
			'option',
			'get',
			'csbmw_e2e_shipped_product',
		] );

		await page.goto( `/?p=${ productId }`, {
			waitUntil: 'domcontentloaded',
		} );

		const message = page.locator(
			'.csbmw-goals-product .csbmw-goals-message'
		);

		await expect( message ).toHaveText(
			'Add R$150.00 more to get free shipping.'
		);

		// A product added without a page load, as the blocks' buttons do.
		await page.evaluate( async ( id ) => {
			const body = new window.FormData();

			body.append( 'product_id', id );
			body.append( 'quantity', '2' );
			await window.fetch( '/?wc-ajax=add_to_cart', {
				method: 'POST',
				body,
			} );
			document.body.dispatchEvent(
				new window.Event( 'wc-blocks_added_to_cart' )
			);
		}, productId );

		await expect( message ).toHaveText( FREE_SHIPPING );
	} );

	test( 'shows only where it is turned on', async ( { page } ) => {
		setSettings( { ...SETTINGS, goals_cart: 0, goals_free_shipping: 0 } );
		await addProduct( page, 2 );
		await page.goto( '/cart/', { waitUntil: 'domcontentloaded' } );
		await expect( page.locator( '.wc-block-cart__submit' ) ).toBeVisible();
		await expect( page.locator( '.csbmw-goals' ) ).toHaveCount( 0 );

		await page.goto( '/checkout/', { waitUntil: 'domcontentloaded' } );
		await expect( page.locator( '.csbmw-goals-message' ) ).toHaveText(
			'Add R$200.00 more to get 10% off.'
		);
	} );
} );
