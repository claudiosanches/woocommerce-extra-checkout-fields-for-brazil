/**
 * Editor preview of the product shipping calculator block.
 */

import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import metadata from './block.json';

function Edit() {
	return (
		<div { ...useBlockProps( { className: 'csbmw-shipping-calculator' } ) }>
			<div className="csbmw-shipping-calculator-card">
				<div className="csbmw-shipping-calculator-form">
					<span className="csbmw-shipping-calculator-label">
						{ __(
							'Calculate shipping and delivery time',
							'woocommerce-extra-checkout-fields-for-brazil'
						) }
					</span>
					<div className="csbmw-shipping-calculator-row">
						<input
							className="csbmw-shipping-calculator-input"
							type="text"
							placeholder={ __(
								'Enter your CEP',
								'woocommerce-extra-checkout-fields-for-brazil'
							) }
							disabled
						/>
						<button
							className="csbmw-shipping-calculator-button wp-element-button"
							type="button"
							disabled
						>
							{ __(
								'Get quote',
								'woocommerce-extra-checkout-fields-for-brazil'
							) }
						</button>
					</div>
					<span className="csbmw-shipping-calculator-find">
						{ __(
							"I don't know my CEP",
							'woocommerce-extra-checkout-fields-for-brazil'
						) }
					</span>
				</div>
			</div>
		</div>
	);
}

// JSON imports widen literals such as the alignments to plain strings.
registerBlockType( metadata as BlockConfiguration, {
	edit: Edit,
	save: () => null,
} );
