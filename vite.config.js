/* eslint-disable import/no-unresolved */
// vite.config.js

import { defineConfig } from 'vite';
import create_config from '@kucrut/vite-for-wp';
import { wp_globals } from '@kucrut/vite-for-wp/utils';
import external_globals from 'rollup-plugin-external-globals';
import dev_externals from 'vite-plugin-external';

const entries = {
	'user-profile': 'assets/src/js/user-profile.js',
	'login': 'assets/src/js/login.js',
};

const outputDir = 'assets/dist';

export default defineConfig( ( { command } ) => {
	// Dev build.
	if ( command === 'serve' ) {
		return create_config( entries, outputDir, {
			plugins: [ dev_externals( { externals: wp_globals() } ) ],
		} );
	}

	// Production build.
	return create_config( entries, outputDir, {
		plugins: [
			external_globals( {
				...wp_globals(),
			} ),
		],
		build: {
			rollupOptions: {
				external: [ ...Object.keys( wp_globals() ) ],
				output: {
					globals: wp_globals(),
				},
			},
		},
	} );
} );
