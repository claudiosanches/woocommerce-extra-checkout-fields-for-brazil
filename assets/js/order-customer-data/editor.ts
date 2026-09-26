/**
 * Editor preview of the order customer data block, rendered by PHP with
 * sample values, as the template being edited has no order.
 */

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
				settings: {
					edit: ( props: {
						attributes: Record< string, unknown >;
					} ) => unknown;
					save: () => null;
				}
			) => void;
		};
		blockEditor: {
			useBlockProps: () => Record< string, unknown >;
		};
		serverSideRender: unknown;
	};
}

const NAME = 'csbmw/order-customer-data';
const { wp } = window as unknown as Globals;
const { createElement: el } = wp.element;

wp.blocks.registerBlockType( NAME, {
	edit: ( { attributes } ) =>
		el(
			'div',
			wp.blockEditor.useBlockProps(),
			el( wp.serverSideRender, { block: NAME, attributes } )
		),
	save: () => null,
} );
