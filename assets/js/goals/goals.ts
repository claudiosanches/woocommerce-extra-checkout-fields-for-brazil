/**
 * Product page spending goals, refreshed when a product is added to the cart
 * without a page load.
 */

import { getJson } from '../shared/postcode';
import '../../scss/goals/goals.scss';

interface GoalsParams {
	url?: string;
}

declare global {
	interface Window {
		bmwGoalsParams?: GoalsParams;
	}
}

const params: GoalsParams = window.bmwGoalsParams || {};

/**
 * Draw the bar again for the cart as it is now.
 */
async function refresh(): Promise< void > {
	const holders = document.querySelectorAll< HTMLElement >(
		'.csbmw-goals-product'
	);

	if ( ! holders.length || ! params.url ) {
		return;
	}

	try {
		const { html } = await getJson< { html: string } >( params.url, {} );

		// Markup printed by the plugin's own endpoint.
		holders.forEach( ( holder ) => ( holder.innerHTML = html ) );
	} catch {}
}

// The blocks' add to cart buttons, and WooCommerce's classic AJAX buttons.
document.body.addEventListener( 'wc-blocks_added_to_cart', refresh );
document.body.addEventListener( 'wc-blocks_removed_from_cart', refresh );
window
	.jQuery?.( document.body )
	.on( 'added_to_cart removed_from_cart', refresh );
