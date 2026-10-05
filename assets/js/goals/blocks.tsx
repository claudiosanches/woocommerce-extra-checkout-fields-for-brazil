/**
 * Spending goals at the top of the order summary of the Cart and Checkout
 * blocks, drawn from the data the plugin adds to the Store API cart. The
 * plugin prints where the inner block goes, after the summary's title.
 */

import { useSelect } from '@wordpress/data';
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';
import { CART_STORE_KEY } from '@woocommerce/block-data';
import { GoalsBar } from './bar';
import type { Bar } from './bar';

interface CartSelectors {
	getCartData: () => {
		extensions: Record< string, { bar?: Bar | null } >;
	};
}

function Goals( { place }: { place?: string } ) {
	const bar = useSelect(
		( select ) =>
			( select( CART_STORE_KEY ) as CartSelectors ).getCartData()
				.extensions[ 'csbmw-goals' ]?.bar,
		[]
	);

	if ( ! place || ! bar ) {
		return null;
	}

	return (
		<div
			className="wc-block-components-totals-wrapper csbmw-goals-summary"
			aria-live="polite"
		>
			<GoalsBar bar={ bar } place={ place } />
		</div>
	);
}

registerCheckoutBlock( {
	metadata: {
		name: 'csbmw/spending-goals-summary',
		parent: [
			'woocommerce/cart-order-summary-block',
			'woocommerce/checkout-order-summary-block',
		],
	},
	component: Goals,
} );
