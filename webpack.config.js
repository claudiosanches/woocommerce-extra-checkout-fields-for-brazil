const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		frontend: path.resolve( __dirname, 'assets/js/frontend/frontend.js' ),
		blocks: path.resolve( __dirname, 'assets/js/blocks/index.ts' ),
		// Beside the block.json the build copies from the source folder.
		'blocks/order-customer-data/index': path.resolve(
			__dirname,
			'assets/js/blocks/order-customer-data/index.tsx'
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
