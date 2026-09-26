/**
 * Editor preview of the order customer data block, rendered by PHP with
 * sample values, as the template being edited has no order.
 */

import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from '../../../includes/blocks/order-customer-data/block.json';

function Edit( { attributes }: { attributes: Record< string, unknown > } ) {
	return (
		<div { ...useBlockProps() }>
			<ServerSideRender
				block={ metadata.name }
				attributes={ attributes }
			/>
		</div>
	);
}

// JSON imports widen literals such as the alignments to plain strings.
registerBlockType( metadata as BlockConfiguration, {
	edit: Edit,
	save: () => null,
} );
