const wpPlugin = require( '@wordpress/eslint-plugin' );

module.exports = [
	{
		ignores: [
			'**/*.min.js',
			'**/node_modules/**',
			'**/vendor/**',
			'wordpress/**',
			'assets/dist/**',
		],
	},
	...wpPlugin.configs.recommended,
	{
		rules: {
			// Function arguments follow Prettier's `trailingComma: 'es5'`.
			'comma-dangle': [
				'error',
				{
					arrays: 'always-multiline',
					objects: 'always-multiline',
					imports: 'always-multiline',
					exports: 'always-multiline',
					functions: 'ignore',
				},
			],
			camelcase: 'off',
			'jsdoc/require-jsdoc': [
				'error',
				{
					require: {
						FunctionDeclaration: true,
					},
				},
			],
		},
		settings: {
			jsdoc: {
				mode: 'typescript',
			},
			'import/resolver': {
				node: {
					extensions: [ '.js', '.jsx', '.ts', '.tsx' ],
				},
			},
			'import/core-modules': [
				'@wordpress/api-fetch',
				'@wordpress/dom-ready',
				'@wordpress/i18n',
			],
		},
	},
];
