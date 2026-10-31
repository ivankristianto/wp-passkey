const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		'user-profile': './assets/src/js/user-profile.js',
		'login': './assets/src/js/login.js',
	},
	output: {
		filename: '[name].js',
		path: __dirname + '/assets/dist',
	},
};
