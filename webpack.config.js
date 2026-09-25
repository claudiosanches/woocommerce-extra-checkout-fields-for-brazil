const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		frontend: path.resolve( __dirname, 'assets/js/frontend/frontend.js' ),
		blocks: path.resolve( __dirname, 'assets/js/blocks/index.ts' ),
		shipping: path.resolve( __dirname, 'assets/js/shipping/shipping.ts' ),
		'shipping-cart': path.resolve( __dirname, 'assets/js/shipping/cart.ts' ),
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
