/**
 * Spending goals in the order summary of the Cart and Checkout blocks, drawn
 * from the data the plugin adds to the Store API cart.
 */

import { registerPlugin } from '@wordpress/plugins';
import { ExperimentalOrderMeta } from '@woocommerce/blocks-checkout';
import { GoalsBar } from './bar';
import type { Bar } from './bar';

interface GoalsBlocksParams {
	places?: string[];
}

declare global {
	interface Window {
		bmwGoalsBlocksParams?: GoalsBlocksParams;
	}
}

const params: GoalsBlocksParams = window.bmwGoalsBlocksParams || {};

const PLACES: Record< string, string > = {
	'woocommerce/cart': 'cart',
	'woocommerce/checkout': 'checkout',
};

function Goals( {
	extensions,
	context,
}: {
	extensions?: Record< string, { bar?: Bar | null } >;
	context?: string;
} ) {
	const place = PLACES[ context || '' ];
	const bar = extensions?.[ 'csbmw-goals' ]?.bar;

	if ( ! place || ! bar || ! params.places?.includes( place ) ) {
		return null;
	}

	return <GoalsBar bar={ bar } place={ place } />;
}

registerPlugin( 'csbmw-spending-goals', {
	render: () => (
		<ExperimentalOrderMeta>
			<Goals />
		</ExperimentalOrderMeta>
	),
	scope: 'woocommerce-checkout',
} );
