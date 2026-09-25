/**
 * Editor preview of the product shipping calculator block.
 */

import { __ } from '@wordpress/i18n';

interface Globals {
	wp: {
		element: {
			createElement: (
				type: unknown,
				props?: unknown,
				...children: unknown[]
			) => unknown;
		};
		blocks: {
			registerBlockType: (
				name: string,
				settings: { edit: () => unknown; save: () => null }
			) => void;
		};
		blockEditor: {
			useBlockProps: (
				props: Record< string, string >
			) => Record< string, unknown >;
		};
	};
}

const { wp } = window as unknown as Window & Globals;
const { createElement: el } = wp.element;

function Edit() {
	const blockProps = wp.blockEditor.useBlockProps( {
		className: 'csbmw-shipping-calculator',
	} );

	return el(
		'div',
		blockProps,
		el(
			'div',
			{ className: 'csbmw-shipping-calculator-card' },
			el(
				'div',
				{ className: 'csbmw-shipping-calculator-form' },
				el(
					'span',
					{ className: 'csbmw-shipping-calculator-label' },
					__(
						'Calculate shipping and delivery time',
						'woocommerce-extra-checkout-fields-for-brazil'
					)
				),
				el(
					'div',
					{ className: 'csbmw-shipping-calculator-row' },
					el( 'input', {
						className: 'csbmw-shipping-calculator-input',
						type: 'text',
						placeholder: __(
							'Enter your CEP',
							'woocommerce-extra-checkout-fields-for-brazil'
						),
						disabled: true,
					} ),
					el(
						'button',
						{
							className:
								'csbmw-shipping-calculator-button wp-element-button',
							type: 'button',
							disabled: true,
						},
						__(
							'Get quote',
							'woocommerce-extra-checkout-fields-for-brazil'
						)
					)
				),
				el(
					'span',
					{ className: 'csbmw-shipping-calculator-find' },
					__(
						"I don't know my CEP",
						'woocommerce-extra-checkout-fields-for-brazil'
					)
				)
			)
		)
	);
}

wp.blocks.registerBlockType( 'csbmw/shipping-calculator', {
	edit: Edit,
	save: () => null,
} );
