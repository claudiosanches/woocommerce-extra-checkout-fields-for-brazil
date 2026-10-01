const { seedLegacyCustomer, seedPostcodes, wpCli } = require( './utils' );

/**
 * Put the store into a state the specs can rely on: one purchasable product, a
 * payment method, a shortcode checkout page to compare against, and a customer
 * whose data only exists under the historic meta keys.
 *
 * @return {Promise<void>}
 */
module.exports = async function globalSetup() {
	// A cheap, always-in-stock, virtual product so no shipping is involved.
	const productId = wpCli( [
		'eval',
		`
		$existing = get_page_by_path( 'csbmw-e2e-product', OBJECT, 'product' );
		if ( $existing ) {
			echo $existing->ID;
			return;
		}
		$product = new WC_Product_Simple();
		$product->set_name( 'CSBMW E2E Product' );
		$product->set_slug( 'csbmw-e2e-product' );
		$product->set_regular_price( '10.00' );
		$product->set_virtual( true );
		$product->set_catalog_visibility( 'visible' );
		$product->set_status( 'publish' );
		echo $product->save();
		`,
	] );

	wpCli( [ 'option', 'update', 'csbmw_e2e_product', productId ] );

	// Cash on delivery, so an order can be placed without a gateway.
	wpCli( [
		'eval',
		`update_option( 'woocommerce_cod_settings', array(
			'enabled'            => 'yes',
			'title'              => 'Cash on delivery',
			'description'        => '',
			'instructions'       => '',
			'enable_for_methods' => array(),
			'enable_for_virtual' => 'yes',
		) );`,
	] );

	// A fresh WooCommerce install serves a "coming soon" page to everyone but
	// an administrator, which the customer specs would otherwise land on.
	wpCli( [ 'option', 'update', 'woocommerce_coming_soon', 'no' ] );

	// Sell to Brazil and default there, so the Brazilian rules apply.
	wpCli( [ 'option', 'update', 'woocommerce_default_country', 'BR:SP' ] );
	wpCli( [ 'option', 'update', 'woocommerce_allowed_countries', 'all' ] );
	wpCli( [ 'option', 'update', 'woocommerce_currency', 'BRL' ] );

	// A shortcode checkout to prove the classic path still works.
	const classicPage = wpCli( [
		'eval',
		`
		$existing = get_page_by_path( 'csbmw-classic-checkout' );
		if ( $existing ) {
			echo $existing->ID;
			return;
		}
		echo wp_insert_post( array(
			'post_title'   => 'CSBMW Classic Checkout',
			'post_name'    => 'csbmw-classic-checkout',
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_content' => '[woocommerce_checkout]',
		) );
		`,
	] );

	wpCli( [ 'option', 'update', 'csbmw_e2e_classic_page', classicPage ] );

	// A returning customer carrying only the historic meta, to prove the block
	// checkout prefills from it.
	seedLegacyCustomer();

	// A physical product and two Brazilian rates for the shipping calculators.
	const shippedId = wpCli( [
		'eval',
		`
		$existing = get_page_by_path( 'csbmw-e2e-shipped-product', OBJECT, 'product' );
		if ( $existing ) {
			echo $existing->ID;
			return;
		}
		$product = new WC_Product_Simple();
		$product->set_name( 'CSBMW E2E Shipped Product' );
		$product->set_slug( 'csbmw-e2e-shipped-product' );
		$product->set_regular_price( '50.00' );
		$product->set_weight( '1' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_status( 'publish' );
		echo $product->save();
		`,
	] );

	wpCli( [ 'option', 'update', 'csbmw_e2e_shipped_product', shippedId ] );

	wpCli( [
		'eval',
		`
		foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
			if ( 'CSBMW E2E Brasil' === $zone['zone_name'] ) {
				return;
			}
		}
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'CSBMW E2E Brasil' );
		$zone->add_location( 'BR', 'country' );
		$zone->save();
		foreach ( array( 'PAC E2E' => '12', 'SEDEX E2E' => '30' ) as $title => $cost ) {
			$instance = $zone->add_shipping_method( 'flat_rate' );
			update_option( 'woocommerce_flat_rate_' . $instance . '_settings', array(
				'title'      => $title,
				'cost'       => $cost,
				'tax_status' => 'none',
			) );
		}
		`,
	] );

	// A shortcode cart, which the classic calculator lives on.
	const classicCart = wpCli( [
		'eval',
		`
		$existing = get_page_by_path( 'csbmw-classic-cart' );
		if ( $existing ) {
			echo $existing->ID;
			return;
		}
		echo wp_insert_post( array(
			'post_title'   => 'CSBMW Classic Cart',
			'post_name'    => 'csbmw-classic-cart',
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_content' => '[woocommerce_cart]',
		) );
		`,
	] );

	wpCli( [ 'option', 'update', 'csbmw_e2e_classic_cart_page', classicCart ] );

	// The e2e store asks no CEP service, so every CEP a spec uses is seeded.
	seedPostcodes();
};
