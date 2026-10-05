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

// The latest refresh, so an earlier one answered late draws nothing.
let latest = 0;

/**
 * Draw every bar again for the cart as it is now, asking once for each kind.
 */
async function refresh(): Promise< void > {
	if ( ! params.url ) {
		return;
	}

	const current = ++latest;

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
				const html = await requests.get( key );

				if ( current === latest ) {
					// Markup printed by the plugin's own endpoint.
					holder.innerHTML = html || '';
				}
			} catch {}
		} )
	);
}

let timer = 0;

/**
 * Redraw once for a burst of cart events, as one change can send several.
 */
function schedule(): void {
	window.clearTimeout( timer );
	timer = window.setTimeout( refresh, 150 );
}

// The blocks' add to cart buttons, WooCommerce's classic AJAX buttons, and the
// classic cart and checkout redrawing their totals.
document.body.addEventListener( 'wc-blocks_added_to_cart', schedule );
document.body.addEventListener( 'wc-blocks_removed_from_cart', schedule );
// Changes made in the Cart and Checkout blocks, from sync.ts.
document.body.addEventListener( 'csbmw_goals_refresh', schedule );
// The Mini-Cart drawer, which announces a removed item only this way.
window.addEventListener( 'wc-blocks_store_sync_required', schedule );
window
	.jQuery?.( document.body )
	.on(
		'added_to_cart removed_from_cart updated_cart_totals updated_checkout',
		schedule
	);
