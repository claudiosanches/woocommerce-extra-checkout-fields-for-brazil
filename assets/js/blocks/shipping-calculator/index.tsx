/**
 * Editor preview of the product shipping calculator block, rendered by PHP
 * from the same view as the page, and inert so its form cannot be sent.
 */

import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Disabled, PanelBody, SelectControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';

// A type rather than an interface, which the block types' record
// constraint does not accept.
type Attributes = {
	changePostcodeIn: 'dialog' | 'block';
};

function Edit( {
	attributes,
	setAttributes,
}: {
	attributes: Attributes;
	setAttributes: ( attributes: Partial< Attributes > ) => void;
} ) {
	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Settings',
						'woocommerce-extra-checkout-fields-for-brazil'
					) }
				>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __(
							'Change CEP in',
							'woocommerce-extra-checkout-fields-for-brazil'
						) }
						value={ attributes.changePostcodeIn }
						options={ [
							{
								label: __(
									'A dialog',
									'woocommerce-extra-checkout-fields-for-brazil'
								),
								value: 'dialog',
							},
							{
								label: __(
									'The block itself',
									'woocommerce-extra-checkout-fields-for-brazil'
								),
								value: 'block',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( {
								changePostcodeIn:
									value as Attributes[ 'changePostcodeIn' ],
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender
					block={ metadata.name }
					attributes={ { ...attributes } }
				/>
			</Disabled>
		</div>
	);
}

// The truck the calculator shows beside the destination, from Heroicons. The
// editor fills block icons, so the outline sets no fill on its path.
const icon = (
	<svg
		viewBox="0 0 24 24"
		fill="none"
		stroke="currentColor"
		strokeWidth={ 1.5 }
	>
		<path
			fill="none"
			strokeLinecap="round"
			strokeLinejoin="round"
			d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"
		/>
	</svg>
);

// JSON imports widen literals such as the alignments to plain strings.
registerBlockType( metadata as BlockConfiguration< Attributes >, {
	icon,
	edit: Edit,
	save: () => null,
} );
