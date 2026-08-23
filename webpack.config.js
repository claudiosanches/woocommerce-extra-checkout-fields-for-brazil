const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		frontend: path.resolve( __dirname, 'assets/js/frontend/frontend.js' ),
		blocks: path.resolve( __dirname, 'assets/js/blocks/index.js' ),
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
