import terser from '@rollup/plugin-terser';

export default {
	input: 'assets/src/index.js',
	output: {
		file: 'assets/dist/lls.min.js',
		format: 'cjs',
		plugins: [ terser() ],
	},
};
