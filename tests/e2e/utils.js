const { execFileSync } = require( 'child_process' );
const path = require( 'path' );

const ROOT = path.join( __dirname, '..', '..' );

/**
 * Run WP-CLI inside the wp-env container.
 *
 * Used to set the store up and to read back what a checkout actually stored,
 * which is the part the browser cannot show.
 *
 * @param {string[]} args WP-CLI arguments.
 * @return {string} Trimmed stdout.
 */
function wpCli( args ) {
	const output = execFileSync(
		'npx',
		[ 'wp-env', 'run', 'cli', 'wp', ...args ],
		{ cwd: ROOT, encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'pipe' ] }
	);

	return output
		.split( '\n' )
		.filter(
			( line ) => ! line.startsWith( 'ℹ' ) && ! line.startsWith( '✔' )
		)
		.join( '\n' )
		.trim();
}

/**
 * Read one meta value from an order.
 *
 * @param {number} orderId Order ID.
 * @param {string} key     Meta key.
 * @return {string} The stored value.
 */
function orderMeta( orderId, key ) {
	return wpCli( [
		'eval',
		`$o = wc_get_order( ${ orderId } ); echo $o ? (string) $o->get_meta( '${ key }' ) : '';`,
	] );
}

/**
 * Read several meta values from an order at once.
 *
 * @param {number}   orderId Order ID.
 * @param {string[]} keys    Meta keys.
 * @return {Object<string,string>} Values keyed by meta key.
 */
function orderMetaAll( orderId, keys ) {
	const php = `$o = wc_get_order( ${ orderId } ); $out = array(); foreach ( ${ phpArray(
		keys
	) } as $k ) { $out[ $k ] = $o ? (string) $o->get_meta( $k ) : ''; } echo wp_json_encode( $out );`;

	return JSON.parse( wpCli( [ 'eval', php ] ) );
}

/**
 * Render a JS array of strings as PHP source.
 *
 * @param {string[]} values Values.
 * @return {string} PHP array literal.
 */
function phpArray( values ) {
	return `array(${ values.map( ( v ) => `'${ v }'` ).join( ',' ) })`;
}

/**
 * Replace the plugin settings.
 *
 * @param {Object} settings Settings to store.
 * @return {void}
 */
function setSettings( settings ) {
	wpCli( [
		'eval',
		`update_option( 'wcbcf_settings', json_decode( '${ JSON.stringify(
			settings
		) }', true ) );`,
	] );
}

/** Settings with every field switched on, which most specs use. */
const ALL_FIELDS = {
	person_type: 1,
	only_brazil: 1,
	rg: 1,
	ie: 1,
	birthdate: 1,
	gender: 1,
	cell_phone: '2',
	neighborhood_required: '1',
	mailcheck: 1,
	maskedinput: 1,
	validate_cpf: 1,
	validate_cnpj: 1,
};

/** A CPF and CNPJ whose check digits are valid. */
const VALID = {
	cpf: '111.444.777-35',
	cnpj: '11.222.333/0001-81',
	cnpjAlphanumeric: '12.ABC.345/01DE-35',
};

/**
 * Put the test product in the cart and open the block checkout.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @return {Promise<void>}
 */
async function goToBlockCheckout( page ) {
	const productId = wpCli( [ 'option', 'get', 'csbmw_e2e_product' ] );

	await page.goto( `/?add-to-cart=${ productId }`, {
		waitUntil: 'domcontentloaded',
	} );
	await page.goto( '/checkout/', { waitUntil: 'domcontentloaded' } );
	await page.waitForSelector( '#billing-first_name', { state: 'attached' } );
	await page.waitForSelector( '#email' );
}

/**
 * Put the test product in the cart and open the shortcode checkout.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @return {Promise<void>}
 */
async function goToClassicCheckout( page ) {
	const productId = wpCli( [ 'option', 'get', 'csbmw_e2e_product' ] );
	const pageId = wpCli( [ 'option', 'get', 'csbmw_e2e_classic_page' ] );

	await page.goto( `/?add-to-cart=${ productId }`, {
		waitUntil: 'domcontentloaded',
	} );
	await page.goto( `/?page_id=${ pageId }`, {
		waitUntil: 'domcontentloaded',
	} );
	await page.waitForSelector( '#billing_first_name' );
	await waitForClassicCheckoutIdle( page );
}

/**
 * Wait until the shortcode checkout is not mid-refresh.
 *
 * WooCommerce re-renders the form over AJAX whenever the address changes, which
 * discards anything typed while it is in flight.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @return {Promise<void>}
 */
async function waitForClassicCheckoutIdle( page ) {
	await page.waitForFunction(
		() => ! document.querySelector( '.blockUI.blockOverlay' ),
		undefined,
		{ timeout: 30_000 }
	);
}

/**
 * The order id in an order-received URL, or null.
 *
 * @param {string} url Current URL.
 * @return {number|null} Order ID.
 */
function orderIdFromUrl( url ) {
	const match = /order-received\/(\d+)/.exec( url );

	return match ? Number( match[ 1 ] ) : null;
}

/**
 * Reset the returning customer to data that exists only under the historic
 * meta keys, as a store upgrading from 4.0.2 would have it.
 *
 * @return {void}
 */
function seedLegacyCustomer() {
	wpCli( [
		'eval',
		`
		$user = get_user_by( 'login', 'csbmw_legacy' );
		$id   = $user ? $user->ID : wp_create_user( 'csbmw_legacy', 'csbmw-e2e-password', 'csbmw-legacy@example.com' );
		$customer = new WC_Customer( $id );
		$customer->set_billing_country( 'BR' );
		$customer->set_billing_first_name( 'Antiga' );
		$customer->set_billing_last_name( 'Cliente' );
		$customer->set_billing_address_1( 'Rua Velha' );
		$customer->set_billing_city( 'Sao Paulo' );
		$customer->set_billing_state( 'SP' );
		$customer->set_billing_postcode( '01000-000' );
		$customer->set_billing_phone( '(11) 3333-4444' );
		foreach ( array(
			'billing_persontype'            => '1',
			'billing_cpf'                   => '123.456.789-09',
			'billing_rg'                    => '998877',
			'billing_birthdate'             => '1/1/1980',
			'billing_gender'                => 'Female',
			'billing_cellphone'             => '(11) 91111-2222',
			'billing_number'       => '42',
			'billing_neighborhood' => 'Centro',
		) as $key => $value ) {
			$customer->update_meta_data( $key, $value );
		}

		// Drop anything the block checkout stored, so only the historic keys
		// remain, and clear the session, which WooCommerce keys by user id and
		// would otherwise serve the previous spec's values.
		foreach ( $customer->get_meta_data() as $meta ) {
			if ( 0 === strpos( $meta->key, '_wc_billing/csbmw/' )
				|| 0 === strpos( $meta->key, '_wc_shipping/csbmw/' )
				|| 0 === strpos( $meta->key, '_wc_other/csbmw/' ) ) {
				$customer->delete_meta_data( $meta->key );
			}
		}

		$customer->save();

		$sessions = new WC_Session_Handler();
		$sessions->delete_session( $id );
		`,
	] );
}

module.exports = {
	ALL_FIELDS,
	seedLegacyCustomer,
	VALID,
	goToBlockCheckout,
	goToClassicCheckout,
	waitForClassicCheckoutIdle,
	orderIdFromUrl,
	orderMeta,
	orderMetaAll,
	setSettings,
	wpCli,
};
