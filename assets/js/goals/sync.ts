/**
 * The Cart and Checkout blocks change the cart without a page event, so a
 * bar placed beside them would not hear of it. Ask the placed bars to redraw
 * whenever the blocks' cart changes what the goals depend on.
 */

import { select, subscribe } from '@wordpress/data';
import { CART_STORE_KEY } from '@woocommerce/block-data';

let last = '';
let timer = 0;
// Newer WooCommerce announces the blocks' cart changes itself, which the
// bars already hear.
let announced = false;

window.addEventListener( 'wc-blocks_store_sync_required', ( event ) => {
	if ( 'from_@wordpress/data' === ( event as CustomEvent ).detail?.type ) {
		announced = true;
	}
} );

subscribe( () => {
	const cart = select( CART_STORE_KEY );

	// Until then the cart holds placeholders.
	if ( ! cart.hasFinishedResolution( 'getCartData' ) ) {
		return;
	}

	const data = cart.getCartData();
	const signature = JSON.stringify( [
		data.totals,
		data.shippingAddress,
		data.coupons.map( ( coupon: { code: string } ) => coupon.code ),
	] );

	if ( signature === last ) {
		return;
	}

	// The page drew the bars for the cart it loaded with.
	const first = '' === last;

	last = signature;

	if ( first ) {
		return;
	}

	window.clearTimeout( timer );
	timer = window.setTimeout( () => {
		if ( ! announced ) {
			document.body.dispatchEvent(
				new window.Event( 'csbmw_goals_refresh' )
			);
		}
	}, 300 );
}, CART_STORE_KEY );
