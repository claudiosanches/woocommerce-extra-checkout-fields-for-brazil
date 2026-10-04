/**
 * Spending goals bars on product pages and in blocks or shortcodes, drawn
 * again when the cart changes without a page load.
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
 * Draw every bar again for the cart as it is now, asking once for each kind.
 */
async function refresh(): Promise< void > {
	if ( ! params.url ) {
		return;
	}

	const holders = Array.from(
		document.querySelectorAll< HTMLElement >( '.csbmw-goals-holder' )
	);
	const requests = new Map< string, Promise< string > >();

	await Promise.all(
		holders.map( async ( holder ) => {
			const query = {
				style: holder.dataset.goalsStyle || '',
				placed: holder.classList.contains( 'is-placed' ) ? '1' : '',
			};
			const key = `${ query.style }|${ query.placed }`;

			if ( ! requests.has( key ) ) {
				requests.set(
					key,
					getJson< { html: string } >( params.url || '', query ).then(
						( data ) => data.html
					)
				);
			}

			try {
				// Markup printed by the plugin's own endpoint.
				holder.innerHTML = ( await requests.get( key ) ) || '';
			} catch {}
		} )
	);
}

// The blocks' add to cart buttons, WooCommerce's classic AJAX buttons, and the
// classic cart and checkout redrawing their totals.
document.body.addEventListener( 'wc-blocks_added_to_cart', refresh );
document.body.addEventListener( 'wc-blocks_removed_from_cart', refresh );
window
	.jQuery?.( document.body )
	.on(
		'added_to_cart removed_from_cart updated_cart_totals updated_checkout',
		refresh
	);
