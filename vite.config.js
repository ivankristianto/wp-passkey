// vite.config.js
import create_config from '@kucrut/vite-for-wp';
// eslint-disable-next-line import/no-unresolved
import { wp_globals } from '@kucrut/vite-for-wp/utils';
import external_globals from 'rollup-plugin-external-globals';

export default create_config(
	{
		'user-profile': 'assets/src/js/user-profile.js',
		'login': 'assets/src/js/login.js',
	},
	'assets/dist',
	{
		plugins: [ external_globals( { ...wp_globals() } ) ],
	},
);
