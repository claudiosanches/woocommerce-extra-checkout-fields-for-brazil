/**
 * Editor preview of the product shipping calculator block, rendered by PHP
 * from the same view as the page, and inert so its form cannot be sent.
 */

import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';

function Edit( { attributes }: { attributes: Record< string, unknown > } ) {
	return (
		<div { ...useBlockProps() }>
			<Disabled>
				<ServerSideRender
					block={ metadata.name }
					attributes={ attributes }
				/>
			</Disabled>
		</div>
	);
}

// JSON imports widen literals such as the alignments to plain strings.
registerBlockType( metadata as BlockConfiguration, {
	edit: Edit,
	save: () => null,
} );
