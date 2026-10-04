/**
 * Editor preview of the Spending Goals block. The cart is the visitor's, so
 * the editor shows a sample bar drawn by the store, with the block's style
 * and colors.
 */

import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';
import * as blockEditor from '@wordpress/block-editor';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import type { ComponentType, CSSProperties } from 'react';
import { GoalsBar } from '../../goals/bar';
import type { Bar } from '../../goals/bar';
import metadata from './block.json';

// A type rather than an interface, which the block types' record
// constraint does not accept.
type Attributes = {
	goalsStyle: '' | 'milestones' | 'next';
	barColor: string;
	trackColor: string;
};

interface ColorSetting {
	label: string;
	colorValue: string;
	onColorChange: ( color?: string ) => void;
	isShownByDefault: boolean;
	resetAllFilter: () => Partial< Attributes >;
}

// The color controls core blocks put in the Color panel, which the block
// editor exports without types.
const {
	__experimentalColorGradientSettingsDropdown: ColorGradientSettingsDropdown,
	__experimentalUseMultipleOriginColorsAndGradients:
		useMultipleOriginColorsAndGradients,
} = blockEditor as unknown as {
	__experimentalColorGradientSettingsDropdown: ComponentType< {
		settings: ColorSetting[];
		panelId: string;
		__experimentalIsRenderedInSidebar: boolean;
	} >;
	__experimentalUseMultipleOriginColorsAndGradients: () => object;
};

interface GoalsEditor {
	style: 'milestones' | 'next';
	bars: Record< string, Bar >;
}

declare global {
	interface Window {
		bmwGoalsEditor?: GoalsEditor;
	}
}

function Edit( {
	attributes,
	setAttributes,
	clientId,
}: {
	attributes: Attributes;
	setAttributes: ( attributes: Partial< Attributes > ) => void;
	clientId: string;
} ) {
	const colorSettings = useMultipleOriginColorsAndGradients();
	const preview = window.bmwGoalsEditor;
	const style = attributes.goalsStyle || preview?.style || 'milestones';
	const colors: Record< string, string > = {};

	if ( attributes.barColor ) {
		colors[ '--csbmw-goals-bar' ] = attributes.barColor;
	}

	if ( attributes.trackColor ) {
		colors[ '--csbmw-goals-track' ] = attributes.trackColor;
	}

	const blockProps = useBlockProps( {
		className: 'csbmw-goals-holder is-placed',
		style: colors as CSSProperties,
	} );
	const bar = preview?.bars[ style ];

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Settings',
						'woocommerce-extra-checkout-fields-for-brazil'
					) }
				>
					<SelectControl
						label={ __(
							'Several goals',
							'woocommerce-extra-checkout-fields-for-brazil'
						) }
						value={ attributes.goalsStyle }
						options={ [
							{
								label: __(
									'As in the store settings',
									'woocommerce-extra-checkout-fields-for-brazil'
								),
								value: '',
							},
							{
								label: __(
									'One bar with every goal marked',
									'woocommerce-extra-checkout-fields-for-brazil'
								),
								value: 'milestones',
							},
							{
								label: __(
									'The next goal only',
									'woocommerce-extra-checkout-fields-for-brazil'
								),
								value: 'next',
							},
						] }
						onChange={ ( goalsStyle ) =>
							setAttributes( {
								goalsStyle:
									goalsStyle as Attributes[ 'goalsStyle' ],
							} )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<InspectorControls group="color">
				<ColorGradientSettingsDropdown
					settings={ [
						{
							label: __(
								'Bar',
								'woocommerce-extra-checkout-fields-for-brazil'
							),
							colorValue: attributes.barColor,
							onColorChange: ( color ) =>
								setAttributes( { barColor: color || '' } ),
							isShownByDefault: true,
							resetAllFilter: () => ( { barColor: '' } ),
						},
						{
							label: __(
								'Track',
								'woocommerce-extra-checkout-fields-for-brazil'
							),
							colorValue: attributes.trackColor,
							onColorChange: ( color ) =>
								setAttributes( { trackColor: color || '' } ),
							isShownByDefault: true,
							resetAllFilter: () => ( { trackColor: '' } ),
						},
					] }
					panelId={ clientId }
					__experimentalIsRenderedInSidebar
					{ ...colorSettings }
				/>
			</InspectorControls>
			<div { ...blockProps }>
				{ bar && <GoalsBar bar={ bar } place="block" /> }
			</div>
		</>
	);
}

// A bar filling toward a ring, as the block draws it.
const icon = (
	<svg viewBox="0 0 24 24">
		<rect x="2" y="10" width="20" height="4" rx="2" opacity="0.3" />
		<rect x="2" y="10" width="11" height="4" rx="2" />
		<circle
			cx="17"
			cy="12"
			r="3"
			fill="#fff"
			stroke="currentColor"
			strokeWidth="2"
		/>
	</svg>
);

// JSON imports widen literals such as the style values to plain strings.
registerBlockType( metadata as BlockConfiguration< Attributes >, {
	icon,
	edit: Edit,
	save: () => null,
} );
