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
	{
		// Jest reads the environment for a test file from this docblock tag.
		files: [ 'tests/js/**/*.js' ],
		rules: {
			'jsdoc/check-tag-names': [
				'error',
				{ definedTags: [ 'jest-environment' ] },
			],
		},
	},
];
