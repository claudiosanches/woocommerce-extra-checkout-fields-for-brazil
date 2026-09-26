const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		classic: path.resolve( __dirname, 'assets/js/classic/index.js' ),
		checkout: path.resolve( __dirname, 'assets/js/checkout/index.ts' ),
		// Beside the block.json the build copies from the source folder.
		'blocks/order-customer-data/index': path.resolve(
			__dirname,
			'assets/js/blocks/order-customer-data/index.tsx'
		),
		shipping: path.resolve( __dirname, 'assets/js/shipping/shipping.ts' ),
		'shipping-cart': path.resolve(
			__dirname,
			'assets/js/shipping/cart.ts'
		),
		'shipping-editor': path.resolve(
			__dirname,
			'assets/js/shipping/editor.ts'
		),
		'admin-order': path.resolve( __dirname, 'assets/js/admin/order.js' ),
		'admin-settings': path.resolve(
			__dirname,
			'assets/js/admin/settings.js'
		),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
	},
};
