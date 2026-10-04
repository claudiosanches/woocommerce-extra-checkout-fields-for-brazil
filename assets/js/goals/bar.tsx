/**
 * The spending goals bar, as the plugin's PHP view draws it, for the Cart and
 * Checkout blocks and the block editor.
 */

import { __ } from '@wordpress/i18n';
import type { CSSProperties } from 'react';

export interface Milestone {
	label: string;
	amount: string;
	position: number;
	reached: boolean;
}

export interface Bar {
	style: string;
	message: string;
	// The message in pieces, rewards marked strong.
	parts: { text: string; strong: boolean }[];
	progress: number;
	milestones: Milestone[];
	colors: { bar: string; track: string };
}

// React types know no custom properties.
const cssVars = ( vars: Record< string, string | number > ) =>
	vars as CSSProperties;

const reachedClass = ( milestone: Milestone ) =>
	milestone.reached ? ' is-reached' : '';

export function GoalsBar( { bar, place }: { bar: Bar; place: string } ) {
	const colors: Record< string, string > = {};

	Object.entries( bar.colors ).forEach( ( [ part, color ] ) => {
		if ( color ) {
			colors[ `--csbmw-goals-default-${ part }` ] = color;
		}
	} );

	return (
		<div
			className={ `csbmw-goals csbmw-goals-style-${ bar.style }` }
			data-place={ place }
			style={ cssVars( colors ) }
		>
			<p className="csbmw-goals-message">
				{ bar.parts.map( ( part, index ) =>
					part.strong ? (
						<strong key={ index }>{ part.text }</strong>
					) : (
						part.text
					)
				) }
			</p>
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
					style={ cssVars( { '--csbmw-progress': bar.progress } ) }
				/>
				{ bar.milestones.map( ( milestone, index ) => (
					<span
						key={ index }
						className={ `csbmw-goals-mark${ reachedClass(
							milestone
						) }` }
						style={ cssVars( {
							'--csbmw-position': milestone.position,
						} ) }
					/>
				) ) }
			</div>
		</div>
	);
}
