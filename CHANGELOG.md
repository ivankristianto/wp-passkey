# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.5.0] - 2026-10-31

### Security
- Post-login redirect is validated on the server (`wp_validate_redirect()` after the `login_redirect` filter), fixing a DOM XSS / open redirect via `?redirect_to=javascript:...`
- Signature counter is saved after each login, so a cloned authenticator with a lower counter is rejected
- Challenges use `random_bytes(32)`; registration challenges live in a 5-minute transient and are single-use
- Registering a credential ID that already exists returns 409
- Login errors are rendered with `textContent`
- Updated web-auth/cose-lib to 4.8.2 (EdDSA signature verification) and web-auth/webauthn-lib to 5.3.9 (full-origin check, GHSA-f7pm-6hr8-7ggm)
- Updated wp-coding-standards/wpcs to ^3.4.1 (CVE-2026-45293)
- Updated @wordpress/scripts to ^37.0.0 and @wordpress/env to ^12.0.0, resolving most npm audit advisories
- Added npm overrides for patched transitive dependencies: qs ^6.17.1, serialize-javascript ^7.1.4 (copy-webpack-plugin), js-yaml ^5.5.0 and smol-toml ^1.9.1 (markdownlint-cli)

### Changed
- Updated @simplewebauthn/browser from ^13.1.0 to ^14.0.0
- Switched `startAuthentication()`/`startRegistration()` to the `{ optionsJSON }` call signature, removing the deprecated positional-argument warning
- Updated web-auth/webauthn-lib to ^5.3.9 and resolved Composer dependencies to stable releases only
- Updated phpstan to ^2.3, szepeviktor/phpstan-wordpress to ^2.0, wp-phpunit to ^7.1 and yoast/phpunit-polyfills to ^4.0
- Migrated ESLint configuration to flat config (`eslint.config.js`) for ESLint 10
- `comma-dangle` no longer requires trailing commas in multi-line function arguments, matching Prettier's `trailingComma: 'es5'`
- Updated archiver to ^8.0.0 in the release script
- **BREAKING:** Node.js development requirement increased from 22 to 24 (`.nvmrc`)
- Devcontainer now uses PHP 8.2, Node 24 and nvm 0.40.8
- Plugin header and readme.txt: "Requires at least" 6.2 (matches the documented minimum) and "Tested up to" 7.1
- Replaced the non-standard Ed256/Ed512 algorithms with standard EdDSA (-8), offered only when the sodium extension is loaded

### Removed
- @babel/eslint-parser dev dependency (no longer used by @wordpress/eslint-plugin)
- npm overrides for fast-xml-parser and webpack-dev-server (the latter forced v5 under @wordpress/scripts, which now requires v6)

### Technical
- Pinned Composer platform to PHP 8.2
- CI workflows now set up Node 24 from `.nvmrc` and install with plain `npm ci`
- Unit tests now run on a PHP 8.2 and 8.4 matrix (wp-env via `WP_ENV_PHP_VERSION`)
- Updated GitHub Actions: checkout v7, setup-node v7, cache v6, paths-filter v4, setup-php 2.40.0; replaced deprecated `::set-output`
- Moved the README changelog into CHANGELOG.md and corrected stale architecture notes in CLAUDE.md
- `wp-scripts lint-js` runs its bundled ESLint 10; the top-level `eslint` package resolves to 9.x because eslint-plugin-import, eslint-plugin-jsx-a11y and eslint-plugin-react do not yet declare ESLint 10 support
- Added PHPUnit security tests with an ES256 software authenticator, and a Playwright e2e suite (`npm run test-e2e`) using Chrome's WebAuthn virtual authenticator

## [0.4.0] - 2026-02-05

### Changed
- **BREAKING:** Minimum PHP requirement increased from 8.1 to 8.2
- **BREAKING:** Minimum Node.js requirement increased from 18 to 22
- Updated web-auth/webauthn-lib from ^4.9.2 to ^5.2.3

### Added
- PSR-20 Clock implementation (System_Clock) for webauthn-lib 5.x compatibility

### Technical
- Updated GitHub Actions workflows to use PHP 8.2
- Validators now use Clock instances for time-based operations
- All existing passkeys remain compatible (no data migration required)

### Migration Guide
To update from 0.3.x:
1. Ensure your server runs PHP 8.2 or higher
2. Update the plugin through WordPress admin
3. Test passkey registration and authentication
4. Existing passkeys will continue to work without changes

## [0.3.9]

- Security: Fix npm vulnerabilities - added overrides for fast-xml-parser and webpack-dev-server
- Reduced vulnerabilities from 33 to 9 (all remaining are dev-time eslint related)

## [0.3.8]

- Update plugin description

## [0.3.1]

- Update plugin name
- Update dependencies
- Fix code standards

## [0.2.1]

- Fix release GitHub action.

## [0.2.0]

- Allow user to revoke passkey.
- Add name & created date as extra data for passkey entity.
- Fix name override when signing in with passkey.

## [0.1.0]

- Initial release.
