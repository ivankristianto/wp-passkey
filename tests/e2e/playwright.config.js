/**
 * Playwright config for the passkey end-to-end suite.
 *
 * Runs against the wp-env development site with a CDP WebAuthn virtual authenticator.
 * Requires `npm run build` and `npm run server start` first.
 */
const { defineConfig } = require( '@playwright/test' );

module.exports = defineConfig( {
	testDir: __dirname,
	testMatch: '**/*.spec.js',
	outputDir: 'test-results',
	// Specs share one WordPress site, so run them one at a time.
	fullyParallel: false,
	workers: 1,
	forbidOnly: !! process.env.CI,
	retries: 0,
	timeout: 60_000,
	reporter: process.env.CI ? 'github' : 'list',
	use: {
		baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',
		// Real Google Chrome by default; set E2E_BROWSER_CHANNEL=chromium for Playwright's bundled build.
		channel: process.env.E2E_BROWSER_CHANNEL || 'chrome',
		headless: true,
		screenshot: 'only-on-failure',
		trace: 'retain-on-failure',
	},
} );
