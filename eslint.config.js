const defaultConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...defaultConfig,
	{
		ignores: [ 'build/**', 'node_modules/**', 'vendor/**', 'languages/**' ],
	},
	{
		languageOptions: {
			globals: {
				jQuery: 'readonly',
			},
		},
	},
];
