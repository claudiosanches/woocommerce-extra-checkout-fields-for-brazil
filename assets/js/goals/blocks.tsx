/**
 * Spending goals in the order summary of the Cart and Checkout blocks, drawn
 * from the data the plugin adds to the Store API cart.
 */

import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { ExperimentalOrderMeta } from '@woocommerce/blocks-checkout';
import type { CSSProperties } from 'react';

interface Milestone {
	label: string;
	amount: string;
	position: number;
	reached: boolean;
}

interface Bar {
	style: string;
	message: string;
	progress: number;
	milestones: Milestone[];
}

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

// React types know no custom properties.
const cssVar = ( name: string, value: number ) =>
	( { [ name ]: value } ) as CSSProperties;

const reachedClass = ( milestone: Milestone ) =>
	milestone.reached ? ' is-reached' : '';

function GoalsBar( { bar, place }: { bar: Bar; place: string } ) {
	return (
		<div
			className={ `csbmw-goals csbmw-goals-style-${ bar.style }` }
			data-place={ place }
		>
			<p className="csbmw-goals-message">{ bar.message }</p>
			<div
				className="csbmw-goals-bar"
				role="progressbar"
				aria-label={ __(
					'Spending goals',
					'woocommerce-extra-checkout-fields-for-brazil'
				) }
				aria-valuemin={ 0 }
				aria-valuemax={ 100 }
				aria-valuenow={ Math.round( bar.progress ) }
				aria-valuetext={ bar.message }
			>
				<span
					className="csbmw-goals-fill"
					style={ cssVar( '--csbmw-progress', bar.progress ) }
				/>
				{ bar.milestones.map( ( milestone, index ) => (
					<span
						key={ index }
						className={ `csbmw-goals-mark${ reachedClass(
							milestone
						) }` }
						style={ cssVar(
							'--csbmw-position',
							milestone.position
						) }
					/>
				) ) }
			</div>
			{ bar.milestones.length > 0 && (
				<ul className="csbmw-goals-milestones">
					{ bar.milestones.map( ( milestone, index ) => (
						<li
							key={ index }
							className={ `csbmw-goals-milestone${ reachedClass(
								milestone
							) }` }
						>
							<span className="csbmw-goals-label">
								{ milestone.label }
							</span>
							<span className="csbmw-goals-amount">
								{ milestone.amount }
							</span>
						</li>
					) ) }
				</ul>
			) }
		</div>
	);
}

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
