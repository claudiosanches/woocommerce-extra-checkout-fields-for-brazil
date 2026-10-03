/**
 * Editor preview of the product shipping calculator block, rendered by PHP
 * from the same view as the page, and inert so its form cannot be sent. The
 * product comes from the block's context unless one is chosen.
 */

import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	ComboboxControl,
	Disabled,
	Flex,
	Notice,
	PanelBody,
	SelectControl,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { decodeEntities } from '@wordpress/html-entities';
import { addQueryArgs } from '@wordpress/url';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';

// A type rather than an interface, which the block types' record
// constraint does not accept.
type Attributes = {
	productId: number;
	variationId: number;
	changePostcodeIn: 'dialog' | 'block';
};

// The Store API lists only what customers can see.
interface StoreProduct {
	id: number;
	name: string;
	type: string;
	// A variation's attributes, such as "Size: Large".
	variation?: string;
}

const PRODUCTS = '/wc/store/v1/products';

// Where the block quotes the product being shown.
const PRODUCT_CONTEXTS = [ 'product', 'wp_template', 'wp_template_part' ];

/**
 * Fetch from the REST API, waiting for the path to settle.
 *
 * @param path  Path, empty to fetch nothing.
 * @param delay Milliseconds to wait, for paths that change while typing.
 * @return Response, null until it arrives or when it fails.
 */
function useFetch< T >( path: string, delay = 0 ): T | null {
	const [ data, setData ] = useState< T | null >( null );

	useEffect( () => {
		let current = true;

		if ( ! path ) {
			setData( null );

			return;
		}

		const timer = window.setTimeout( () => {
			apiFetch< T >( { path } )
				.then( ( response ) => current && setData( response ) )
				.catch( () => current && setData( null ) );
		}, delay );

		return () => {
			current = false;
			window.clearTimeout( timer );
		};
	}, [ path, delay ] );

	return data;
}

function Edit( {
	attributes,
	setAttributes,
	context,
}: {
	attributes: Attributes;
	setAttributes: ( attributes: Partial< Attributes > ) => void;
	context: { postType?: string };
} ) {
	const [ search, setSearch ] = useState( '' );
	const results = useFetch< StoreProduct[] >(
		addQueryArgs( PRODUCTS, { search, per_page: 20 } ),
		300
	);
	const selected = useFetch< StoreProduct >(
		attributes.productId ? `${ PRODUCTS }/${ attributes.productId }` : ''
	);
	const variations = useFetch< StoreProduct[] >(
		'variable' === selected?.type
			? addQueryArgs( PRODUCTS, {
					type: 'variation',
					parent: selected.id,
					per_page: 100,
			  } )
			: ''
	);
	const products = [
		...( selected && ! results?.some( ( { id } ) => id === selected.id )
			? [ selected ]
			: [] ),
		...( results || [] ),
	];

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Settings',
						'woocommerce-extra-checkout-fields-for-brazil'
					) }
				>
					<Flex direction="column" align="stretch" gap={ 4 }>
						<ComboboxControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __(
								'Product',
								'woocommerce-extra-checkout-fields-for-brazil'
							) }
							help={ __(
								'Leave empty to quote the product being shown.',
								'woocommerce-extra-checkout-fields-for-brazil'
							) }
							value={
								attributes.productId
									? String( attributes.productId )
									: null
							}
							options={ products.map( ( product ) => ( {
								label: decodeEntities( product.name ),
								value: String( product.id ),
							} ) ) }
							onFilterValueChange={ setSearch }
							onChange={ ( value ) =>
								setAttributes( {
									productId: value ? Number( value ) : 0,
									variationId: 0,
								} )
							}
						/>
						{ !! variations?.length && (
							<SelectControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __(
									'Variation',
									'woocommerce-extra-checkout-fields-for-brazil'
								) }
								help={ __(
									'Away from the product page, choose one to quote.',
									'woocommerce-extra-checkout-fields-for-brazil'
								) }
								value={ String( attributes.variationId ) }
								options={ [
									{
										label: __(
											'Chosen by the customer',
											'woocommerce-extra-checkout-fields-for-brazil'
										),
										value: '0',
									},
									...variations.map( ( variation ) => ( {
										label: decodeEntities(
											variation.variation ||
												variation.name
										),
										value: String( variation.id ),
									} ) ),
								] }
								onChange={ ( value ) =>
									setAttributes( {
										variationId: Number( value ),
									} )
								}
							/>
						) }
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
					</Flex>
				</PanelBody>
			</InspectorControls>
			{ ! attributes.productId &&
				!! context.postType &&
				! PRODUCT_CONTEXTS.includes( context.postType ) && (
					<Notice status="warning" isDismissible={ false }>
						{ __(
							'Choose the product to quote in the block settings.',
							'woocommerce-extra-checkout-fields-for-brazil'
						) }
					</Notice>
				) }
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
