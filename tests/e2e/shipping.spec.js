const { test, expect } = require( '@playwright/test' );
const {
	ALL_FIELDS,
	POSTCODES,
	UNKNOWN_POSTCODE,
	goToClassicCheckout,
	logIn,
	setSettings,
	shipEverywhere,
	shipOnlyToBrazil,
	wpCli,
} = require( './utils' );

const SETTINGS = {
	...ALL_FIELDS,
	postcode_autofill: 1,
	postcode_only_calculator: 1,
	product_shipping_calculator: 'after_add_to_cart',
};

const ADMIN = { user: 'admin', pass: 'password' };

/**
 * Put the physical product in the cart.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @return {Promise<void>}
 */
async function addShippedProduct( page ) {
	const productId = wpCli( [ 'option', 'get', 'csbmw_e2e_shipped_product' ] );

	await page.goto( `/?add-to-cart=${ productId }`, {
		waitUntil: 'domcontentloaded',
	} );
}

/**
 * Create a variable product with a physical and a virtual variation.
 *
 * @return {{id: string, fisico: number, digital: number}} IDs.
 */
function createMixedProduct() {
	return JSON.parse(
		wpCli( [
			'eval',
			`$p = new WC_Product_Variable();
			$p->set_name( 'CSBMW E2E Mixed Product' );
			$a = new WC_Product_Attribute();
			$a->set_name( 'Tipo' );
			$a->set_options( array( 'Fisico', 'Digital' ) );
			$a->set_visible( true );
			$a->set_variation( true );
			$p->set_attributes( array( $a ) );
			$p->set_status( 'publish' );
			$ids = array( 'id' => (string) $p->save() );
			foreach ( array( 'Fisico' => false, 'Digital' => true ) as $o => $v ) {
				$x = new WC_Product_Variation();
				$x->set_parent_id( $ids['id'] );
				$x->set_attributes( array( 'tipo' => $o ) );
				$x->set_regular_price( '50' );
				$x->set_virtual( $v );
				$ids[ strtolower( $o ) ] = $x->save();
			}
			WC_Product_Variable::sync( $ids['id'] );
			echo wp_json_encode( $ids );`,
		] )
	);
}

/**
 * Publish a page.
 *
 * @param {string} content Page content.
 * @return {string} Page ID.
 */
function createPage( content ) {
	return wpCli( [
		'post',
		'create',
		'--post_type=page',
		'--post_status=publish',
		'--post_title=CSBMW E2E Landing',
		`--post_content=${ content }`,
		'--porcelain',
	] );
}

/**
 * Open the physical product's page.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @return {Promise<import('@playwright/test').Locator>} The calculator.
 */
async function openProduct( page ) {
	const productId = wpCli( [ 'option', 'get', 'csbmw_e2e_shipped_product' ] );

	await page.goto( `/?p=${ productId }`, { waitUntil: 'domcontentloaded' } );

	return page.locator( '.csbmw-shipping-calculator' );
}

/**
 * Make the shortcode cart page the store's cart while a callback runs. The
 * calculator posts to the cart page, and the checkout sends customers back
 * to it.
 *
 * @param {Function} callback Receives the page ID.
 * @return {Promise<void>}
 */
async function withClassicCart( callback ) {
	const cartPage = wpCli( [ 'option', 'get', 'woocommerce_cart_page_id' ] );
	const classicCart = wpCli( [
		'option',
		'get',
		'csbmw_e2e_classic_cart_page',
	] );

	wpCli( [ 'option', 'update', 'woocommerce_cart_page_id', classicCart ] );

	try {
		await callback( classicCart );
	} finally {
		wpCli( [ 'option', 'update', 'woocommerce_cart_page_id', cartPage ] );
	}
}

/**
 * Click the cart block's checkout button.
 *
 * WooCommerce hides it on a wide screen while its place is below the fold.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @return {Promise<void>}
 */
async function proceedToCheckout( page ) {
	await page.locator( '.wc-block-cart__submit' ).scrollIntoViewIfNeeded();
	await page.getByRole( 'link', { name: 'Proceed to Checkout' } ).click();
}

/** A CEP the table lacks, which the offline lookup leaves unanswered. */
const UNANSWERED_POSTCODE = '30130010';

test.describe( 'Shipping calculators', () => {
	test.beforeAll( () => {
		shipOnlyToBrazil();
	} );

	test.afterAll( () => {
		shipEverywhere();
	} );

	test.beforeEach( () => {
		setSettings( SETTINGS );
	} );

	test( 'quotes the product on its page from the CEP alone', async ( {
		page,
	} ) => {
		const calculator = await openProduct( page );
		const input = calculator.locator(
			'.csbmw-shipping-calculator-empty input[name="postcode"]'
		);

		await expect(
			calculator
				.locator( '.csbmw-shipping-calculator-empty' )
				.getByRole( 'link', { name: "I don't know my CEP" } )
		).toHaveAttribute( 'href', /buscacepinter\.correios\.com\.br/ );

		await input.pressSequentially( POSTCODES.saoPaulo.postcode );
		await expect( input ).toHaveValue( '01001-000' );

		await calculator
			.locator( '.csbmw-shipping-calculator-empty' )
			.getByRole( 'button', { name: 'Get quote' } )
			.click();

		const destination = calculator.locator(
			'.csbmw-shipping-calculator-destination'
		);
		const results = calculator.locator(
			'.csbmw-shipping-calculator-results'
		);

		await expect( destination ).toContainText(
			'Centro E2E, São Paulo - SP'
		);
		await expect( results ).toContainText( 'PAC E2E' );
		await expect( results ).toContainText( 'SEDEX E2E' );
		await expect( results ).toContainText( /30[.,]00/ );

		// The CEP is remembered, so the next visit quotes on its own.
		await page.reload();
		await expect( destination ).toContainText( 'São Paulo - SP' );
		await expect( results ).toContainText( 'SEDEX E2E' );

		// Changing it happens in a dialog.
		await destination.click();

		const dialog = calculator.locator( 'dialog' );

		await expect( dialog ).toBeVisible();
		await dialog.locator( 'input[name="postcode"]' ).fill( '' );
		await dialog
			.locator( 'input[name="postcode"]' )
			.pressSequentially( POSTCODES.rio.postcode );
		await dialog.getByRole( 'button', { name: 'Get quote' } ).click();

		await expect( dialog ).toBeHidden();
		await expect( destination ).toContainText( 'Rio de Janeiro - RJ' );
	} );

	test( 'says when a CEP is unknown or incomplete', async ( { page } ) => {
		const calculator = await openProduct( page );
		const empty = calculator.locator( '.csbmw-shipping-calculator-empty' );
		const input = empty.locator( 'input[name="postcode"]' );
		const button = empty.getByRole( 'button', { name: 'Get quote' } );
		const error = empty.locator( '.csbmw-shipping-calculator-error' );

		await input.pressSequentially( '0100' );
		await button.click();
		await expect( error.getByRole( 'alert' ) ).toContainText(
			'Enter a valid CEP.'
		);
		await expect( input ).toHaveAttribute( 'aria-invalid', 'true' );

		await input.fill( '' );
		await input.pressSequentially( UNKNOWN_POSTCODE );
		await button.click();
		await expect( error ).toContainText( 'CEP not found' );
	} );

	test( 'leaves the product page alone when the store ships abroad', async ( {
		page,
	} ) => {
		shipEverywhere();

		try {
			const calculator = await openProduct( page );

			await expect( page.locator( 'form.cart' ) ).toBeVisible();
			await expect( calculator ).toHaveCount( 0 );
		} finally {
			shipOnlyToBrazil();
		}
	} );

	test( 'adds a CEP calculator to the cart block', async ( { page } ) => {
		await addShippedProduct( page );
		await page.goto( '/cart/', { waitUntil: 'domcontentloaded' } );

		const calculator = page.locator( '.csbmw-cart-shipping-calculator' );

		await expect(
			calculator.locator( '.csbmw-shipping-calculator-find svg' )
		).toHaveCount( 1 );

		await calculator
			.getByLabel( 'CEP' )
			.pressSequentially( POSTCODES.rio.postcode );
		await calculator.getByRole( 'button', { name: 'Calculate' } ).click();

		await expect( calculator ).toContainText(
			'Avenida E2E Pio X, Centro E2E, Rio de Janeiro - RJ'
		);

		await calculator.getByRole( 'radio', { name: /SEDEX E2E/ } ).check();

		const shipping = page.locator( '.wc-block-components-totals-shipping' );

		await expect( shipping ).toContainText( 'SEDEX E2E' );
		await expect( shipping ).toContainText( /30[.,]00/ );
	} );

	test( 'guides the CEP in the cart block', async ( { page } ) => {
		await addShippedProduct( page );
		await page.goto( '/cart/', { waitUntil: 'domcontentloaded' } );

		const calculator = page.locator( '.csbmw-cart-shipping-calculator' );
		const input = calculator.getByLabel( 'CEP' );
		const button = calculator.getByRole( 'button', { name: 'Calculate' } );
		const error = calculator.getByRole( 'alert' );

		await expect( calculator ).toContainText(
			'Enter your CEP to see the shipping options for your address.'
		);

		await button.click();
		await expect( error ).toHaveText( 'Enter your CEP.' );

		await input.pressSequentially( '3013' );
		await input.blur();
		await expect( error ).toHaveText( 'A CEP has 8 digits.' );

		await input.fill( '' );
		await input.pressSequentially( UNKNOWN_POSTCODE );
		await button.click();
		await expect( error ).toHaveText(
			'CEP not found. Check the number and try again.'
		);

		await input.fill( '' );
		await input.pressSequentially( UNANSWERED_POSTCODE );
		await button.click();
		await expect( calculator ).toContainText(
			'The address for this CEP could not be looked up right now, so shipping is quoted for Minas Gerais.'
		);
		await expect(
			page.locator( '.wc-block-components-totals-shipping' )
		).toContainText( 'PAC E2E' );
	} );

	test( 'keeps the customer in the cart until a CEP is given, when required', async ( {
		page,
	} ) => {
		setSettings( { ...SETTINGS, require_cart_postcode: 1 } );
		await addShippedProduct( page );
		await page.goto( '/cart/', { waitUntil: 'domcontentloaded' } );

		const calculator = page.locator( '.csbmw-cart-shipping-calculator' );
		const required =
			'Enter your CEP to calculate shipping before checkout.';

		await proceedToCheckout( page );
		await expect( calculator.getByRole( 'alert' ) ).toHaveText( required );
		await expect( calculator.getByLabel( 'CEP' ) ).toBeFocused();
		await expect( page ).toHaveURL( /\/cart\/$/ );

		// The server holds back a customer who goes around the button.
		await page.goto( '/checkout/', { waitUntil: 'domcontentloaded' } );
		await expect( page ).toHaveURL(
			/\/cart\/\?csbmw_postcode_required=1$/
		);
		await expect( page.getByText( required ) ).toBeVisible();

		await calculator
			.getByLabel( 'CEP' )
			.pressSequentially( POSTCODES.rio.postcode );
		await calculator.getByRole( 'button', { name: 'Calculate' } ).click();
		await expect( calculator ).toContainText( 'Rio de Janeiro - RJ' );

		await proceedToCheckout( page );
		await expect( page ).toHaveURL( /\/checkout\/$/ );
	} );

	test( 'keeps the customer in the classic cart until a CEP is given, when required', async ( {
		page,
	} ) => {
		setSettings( { ...SETTINGS, require_cart_postcode: 1 } );

		await withClassicCart( async ( classicCart ) => {
			await addShippedProduct( page );
			await page.goto( `/?page_id=${ classicCart }`, {
				waitUntil: 'domcontentloaded',
			} );

			await page.locator( '.checkout-button' ).click();
			await expect(
				page.locator( '.shipping-calculator-form' )
			).toContainText(
				'Enter your CEP to calculate shipping before checkout.'
			);
			await expect(
				page.locator( '#calc_shipping_postcode' )
			).toBeFocused();
			await expect( page ).not.toHaveURL( /checkout/ );
		} );
	} );

	test( 'lets a cart with nothing to ship through without a CEP', async ( {
		page,
	} ) => {
		setSettings( { ...SETTINGS, require_cart_postcode: 1 } );

		const productId = wpCli( [ 'option', 'get', 'csbmw_e2e_product' ] );

		await page.goto( `/?add-to-cart=${ productId }`, {
			waitUntil: 'domcontentloaded',
		} );
		await page.goto( '/checkout/', { waitUntil: 'domcontentloaded' } );
		await expect( page ).toHaveURL( /\/checkout\/$/ );
	} );

	test( 'quotes the cart for the CEP entered on the product page', async ( {
		page,
	} ) => {
		const calculator = await openProduct( page );
		const empty = calculator.locator( '.csbmw-shipping-calculator-empty' );

		await empty
			.locator( 'input[name="postcode"]' )
			.pressSequentially( POSTCODES.rio.postcode );
		await empty.getByRole( 'button', { name: 'Get quote' } ).click();
		await expect(
			calculator.locator( '.csbmw-shipping-calculator-destination' )
		).toContainText( 'Rio de Janeiro - RJ' );

		await addShippedProduct( page );
		await page.goto( '/cart/', { waitUntil: 'domcontentloaded' } );

		await expect(
			page.locator( '.csbmw-cart-shipping-calculator' )
		).toContainText( 'Avenida E2E Pio X, Centro E2E, Rio de Janeiro - RJ' );
		await expect(
			page.locator( '.wc-block-components-totals-shipping' )
		).toContainText( 'PAC E2E' );

		// A CEP changed on the product page afterwards follows too.
		await openProduct( page );
		await calculator
			.locator( '.csbmw-shipping-calculator-destination' )
			.click();

		const dialog = calculator.locator( 'dialog' );

		await dialog.locator( 'input[name="postcode"]' ).fill( '' );
		await dialog
			.locator( 'input[name="postcode"]' )
			.pressSequentially( POSTCODES.saoPaulo.postcode );
		await dialog.getByRole( 'button', { name: 'Get quote' } ).click();
		await expect(
			calculator.locator( '.csbmw-shipping-calculator-destination' )
		).toContainText( 'São Paulo - SP' );

		await page.goto( '/cart/', { waitUntil: 'domcontentloaded' } );

		await expect(
			page.locator( '.csbmw-cart-shipping-calculator' )
		).toContainText( 'Rua E2E da Sé, Centro E2E, São Paulo - SP' );
	} );

	test( 'asks only for the CEP in the classic cart', async ( { page } ) => {
		await withClassicCart( async ( classicCart ) => {
			await addShippedProduct( page );
			await page.goto( `/?page_id=${ classicCart }`, {
				waitUntil: 'domcontentloaded',
			} );

			// Open from the start, since the customer has no CEP yet.
			const form = page.locator( '.shipping-calculator-form' );

			await expect( form ).toBeVisible();
			await expect(
				form.locator( '.csbmw-shipping-calculator-prompt' )
			).toHaveText(
				'Enter your CEP to see the shipping options for your address.'
			);
			await expect(
				form.locator( '#calc_shipping_country' )
			).toHaveCount( 0 );
			await expect( form.locator( '#calc_shipping_state' ) ).toHaveCount(
				0
			);
			await expect( form.locator( '#calc_shipping_city' ) ).toHaveCount(
				0
			);
			await expect(
				form.locator( '#calc_shipping_postcode_field label' )
			).toHaveText( 'CEP' );
			await expect(
				form.locator( '.csbmw-shipping-calculator-find svg' )
			).toHaveCount( 1 );

			await form
				.locator( '#calc_shipping_postcode' )
				.pressSequentially( POSTCODES.rio.postcode );
			await form.getByRole( 'button', { name: 'Update' } ).click();

			await expect(
				page.locator( '.woocommerce-shipping-destination' )
			).toContainText( 'Rio de Janeiro' );
			await expect(
				page.locator( '.woocommerce-shipping-totals' )
			).toContainText( 'SEDEX E2E' );
		} );
	} );

	test( 'checks the CEP in the classic cart and quotes its state while the lookup is down', async ( {
		page,
	} ) => {
		await withClassicCart( async ( classicCart ) => {
			await addShippedProduct( page );
			await page.goto( `/?page_id=${ classicCart }`, {
				waitUntil: 'domcontentloaded',
			} );

			const form = page.locator( '.shipping-calculator-form' );
			const input = form.locator( '#calc_shipping_postcode' );
			const error = form.locator( '.csbmw-shipping-calculator-error' );

			await input.pressSequentially( '3013' );
			await input.blur();
			await expect( error ).toContainText( 'A CEP has 8 digits.' );
			await expect( input ).toHaveAttribute( 'aria-invalid', 'true' );

			// Nothing is sent for a CEP too short to look up.
			let posted = false;

			page.on( 'request', ( request ) => {
				posted ||= 'POST' === request.method();
			} );
			await form.getByRole( 'button', { name: 'Update' } ).click();
			await expect( error ).toContainText( 'A CEP has 8 digits.' );
			expect( posted ).toBe( false );

			await input.fill( '' );
			await input.pressSequentially( UNANSWERED_POSTCODE );
			await expect( error ).toBeEmpty();
			await form.getByRole( 'button', { name: 'Update' } ).click();

			await expect(
				page.getByText(
					'The address for this CEP could not be looked up right now, so shipping is quoted for Minas Gerais.'
				)
			).toBeVisible();
			await expect(
				page.locator( '.woocommerce-shipping-totals' )
			).toContainText( 'PAC E2E' );
		} );
	} );

	test( 'fills the block checkout address from the CEP', async ( {
		page,
	} ) => {
		await addShippedProduct( page );
		await page.goto( '/checkout/', { waitUntil: 'domcontentloaded' } );

		// Typing before the checkout settles loses keystrokes.
		await page.waitForSelector( '#shipping-postcode' );
		await page.waitForLoadState( 'networkidle' );

		// The filled address is saved to the server, and its answer resets
		// the form, so the next CEP is typed after it.
		const saved = page.waitForResponse( ( response ) =>
			response.url().includes( '/wc/store/v1/batch' )
		);

		await page
			.locator( '#shipping-postcode' )
			.pressSequentially( POSTCODES.saoPaulo.postcode );

		await expect( page.locator( '#shipping-address_1' ) ).toHaveValue(
			'Rua E2E da Sé'
		);
		await expect(
			page.locator( '#shipping-csbmw-neighborhood' )
		).toHaveValue( 'Centro E2E' );
		await expect( page.locator( '#shipping-city' ) ).toHaveValue(
			'São Paulo'
		);
		await expect( page.locator( '#shipping-state' ) ).toHaveValue( 'SP' );

		// "Use same address for billing" is ticked, so billing follows.
		await expect
			.poll( () =>
				page.evaluate(
					() =>
						window.wp.data
							.select( 'wc/store/cart' )
							.getCustomerData().billingAddress.address_1
				)
			)
			.toBe( 'Rua E2E da Sé' );

		// Another CEP takes the number of the old street with it.
		await saved;
		await page.locator( '#shipping-csbmw-number' ).fill( '100' );
		await page.locator( '#shipping-postcode' ).fill( '' );
		await page
			.locator( '#shipping-postcode' )
			.pressSequentially( POSTCODES.rio.postcode );

		await expect( page.locator( '#shipping-address_1' ) ).toHaveValue(
			'Avenida E2E Pio X'
		);
		await expect( page.locator( '#shipping-csbmw-number' ) ).toHaveValue(
			''
		);
	} );

	test( 'fills the classic checkout address from the CEP', async ( {
		page,
	} ) => {
		await goToClassicCheckout( page );

		await page.fill( '#billing_postcode', '' );
		await page
			.locator( '#billing_postcode' )
			.pressSequentially( POSTCODES.rio.postcode );

		await expect( page.locator( '#billing_address_1' ) ).toHaveValue(
			'Avenida E2E Pio X'
		);
		await expect( page.locator( '#billing_neighborhood' ) ).toHaveValue(
			'Centro E2E'
		);
		await expect( page.locator( '#billing_city' ) ).toHaveValue(
			'Rio de Janeiro'
		);
		await expect( page.locator( '#billing_state' ) ).toHaveValue( 'RJ' );

		// Another CEP takes the number of the old street with it.
		await page.fill( '#billing_number', '100' );
		await page.fill( '#billing_postcode', '' );
		await page
			.locator( '#billing_postcode' )
			.pressSequentially( POSTCODES.saoPaulo.postcode );

		await expect( page.locator( '#billing_address_1' ) ).toHaveValue(
			'Rua E2E da Sé'
		);
		await expect( page.locator( '#billing_number' ) ).toHaveValue( '' );
	} );

	test( 'changes the CEP in the block itself when set to', async ( {
		page,
	} ) => {
		// A product template holding the block, as a store saves it from the
		// Site Editor.
		const template = wpCli( [
			'eval',
			`$t = get_block_template( 'woocommerce/woocommerce//single-product' );
			$c = str_replace( '<!-- wp:woocommerce/add-to-cart-form /-->', '<!-- wp:woocommerce/add-to-cart-form /--><!-- wp:csbmw/shipping-calculator {"changePostcodeIn":"block"} /-->', $t->content );
			$id = wp_insert_post( array( 'post_type' => 'wp_template', 'post_status' => 'publish', 'post_name' => 'single-product', 'post_title' => 'Single Product', 'post_content' => $c ) );
			wp_set_post_terms( $id, get_stylesheet(), 'wp_theme' );
			echo $id;`,
		] );

		try {
			const calculator = await openProduct( page );
			const empty = calculator.locator(
				'.csbmw-shipping-calculator-empty'
			);
			const input = empty.locator( 'input[name="postcode"]' );
			const destination = calculator.locator(
				'.csbmw-shipping-calculator-destination'
			);

			await expect( calculator ).toHaveCount( 1 );
			await expect( calculator.locator( 'dialog' ) ).toHaveCount( 0 );

			await input.pressSequentially( POSTCODES.saoPaulo.postcode );
			await empty.getByRole( 'button', { name: 'Get quote' } ).click();
			await expect( destination ).toContainText( 'São Paulo - SP' );

			// The card's own form comes back, holding the current CEP.
			await calculator
				.getByRole( 'button', { name: 'Change CEP' } )
				.click();
			await expect( empty ).toBeVisible();
			await expect( destination ).toBeHidden();
			await expect( input ).toHaveValue( '01001-000' );

			// Escape returns to the quote.
			await input.press( 'Escape' );
			await expect( destination ).toBeVisible();

			await calculator
				.getByRole( 'button', { name: 'Change CEP' } )
				.click();
			await input.fill( '' );
			await input.pressSequentially( POSTCODES.rio.postcode );
			await empty.getByRole( 'button', { name: 'Get quote' } ).click();
			await expect( destination ).toContainText( 'Rio de Janeiro - RJ' );
			await expect( empty ).toBeHidden();
		} finally {
			wpCli( [ 'post', 'delete', template, '--force' ] );
		}
	} );

	test( 'steps aside while a virtual variation is chosen', async ( {
		page,
	} ) => {
		const productId = createMixedProduct().id;

		try {
			await page.goto( `/?p=${ productId }`, {
				waitUntil: 'domcontentloaded',
			} );

			const calculator = page.locator( '.csbmw-shipping-calculator' );
			const options = page.locator( 'select[name="attribute_tipo"]' );

			await expect( calculator ).toBeVisible();

			await options.selectOption( 'Digital' );
			await expect( calculator ).toBeHidden();

			await options.selectOption( 'Fisico' );
			await expect( calculator ).toBeVisible();
		} finally {
			wpCli( [ 'post', 'delete', productId, '--force' ] );
		}
	} );

	test( 'quotes a chosen product away from its page', async ( { page } ) => {
		const mixed = createMixedProduct();
		const pageId = createPage(
			`[csbmw_shipping_calculator id="${ mixed.fisico }"][csbmw_shipping_calculator id="${ mixed.id }"]`
		);

		try {
			await page.goto( `/?page_id=${ pageId }`, {
				waitUntil: 'domcontentloaded',
			} );

			const calculators = page.locator( '.csbmw-shipping-calculator' );
			const quote = async ( calculator ) => {
				await calculator
					.locator( '.csbmw-shipping-calculator-empty input' )
					.pressSequentially( POSTCODES.saoPaulo.postcode );
				await calculator
					.getByRole( 'button', { name: 'Get quote' } )
					.click();
			};

			await expect( calculators ).toHaveCount( 2 );

			// The variation is quoted as it is.
			await quote( calculators.nth( 0 ) );
			await expect(
				calculators
					.nth( 0 )
					.locator( '.csbmw-shipping-calculator-results' )
			).toContainText( 'SEDEX E2E' );

			// Without the options to choose, it points to the product page.
			await quote( calculators.nth( 1 ) );
			await expect(
				calculators.nth( 1 ).getByRole( 'link', {
					name: 'Choose the product options on its page.',
				} )
			).toHaveAttribute( 'href', /.+/ );
		} finally {
			wpCli( [ 'post', 'delete', pageId, '--force' ] );
			wpCli( [ 'post', 'delete', mixed.id, '--force' ] );
		}
	} );

	test( 'keeps one calculator where one is placed for an embedded product', async ( {
		page,
	} ) => {
		const mixed = createMixedProduct();
		const pageId = createPage(
			`[product_page id="${ mixed.id }"][csbmw_shipping_calculator id="${ mixed.id }"]`
		);

		try {
			await page.goto( `/?page_id=${ pageId }`, {
				waitUntil: 'domcontentloaded',
			} );

			const calculator = page.locator( '.csbmw-shipping-calculator' );

			// The setting printed one below the embedded form first.
			await expect( calculator ).toHaveCount( 1 );
			await expect( calculator ).not.toHaveAttribute(
				'data-automatic',
				'1'
			);

			await calculator
				.locator( '.csbmw-shipping-calculator-empty input' )
				.pressSequentially( POSTCODES.saoPaulo.postcode );
			await calculator
				.getByRole( 'button', { name: 'Get quote' } )
				.click();
			await expect( calculator ).toContainText(
				'Choose the product options first.'
			);

			// The embedded form's choice is the one quoted.
			await page
				.locator( 'select[name="attribute_tipo"]' )
				.selectOption( 'Fisico' );
			await expect(
				calculator.locator( '.csbmw-shipping-calculator-results' )
			).toContainText( 'SEDEX E2E' );
		} finally {
			wpCli( [ 'post', 'delete', pageId, '--force' ] );
			wpCli( [ 'post', 'delete', mixed.id, '--force' ] );
		}
	} );

	test( 'offers to restrict the store to Brazil', async ( { page } ) => {
		shipEverywhere();

		await logIn( page, ADMIN.user, ADMIN.pass );
		await page.goto(
			'/wp-admin/admin.php?page=woocommerce-extra-checkout-fields-for-brazil&tab=shipping'
		);

		const section = page.locator( '.bmw-section-shipping' );

		await section
			.getByRole( 'link', { name: 'Sell and ship only to Brazil' } )
			.click();

		await expect( page.locator( '.notice-success' ) ).toContainText(
			'The store now sells and ships only to Brazil.'
		);
		await expect( section.locator( '.bmw-settings-notice' ) ).toHaveCount(
			0
		);
		await expect(
			page.getByRole( 'tab', { name: 'Shipping' } )
		).toHaveAttribute( 'aria-selected', 'true' );
		expect(
			wpCli( [ 'option', 'get', 'woocommerce_allowed_countries' ] )
		).toBe( 'specific' );
	} );
} );
