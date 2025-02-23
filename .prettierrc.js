module.exports = {
	...require( '@wordpress/prettier-config' ),
	overrides: [
		{
			files: [ '*.css', '*.scss' ],
			options: {
				singleQuote: false,
			},
		},
		{
			files: [ '*.json' ],
			options: {
				singleQuote: false,
				tabWidth: 2,
				useTabs: false,
			},
		},
	],
};
