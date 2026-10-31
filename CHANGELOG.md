# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Security
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
- Devcontainer now uses PHP 8.2 and Node 22

### Removed
- @babel/eslint-parser dev dependency (no longer used by @wordpress/eslint-plugin)
- npm overrides for fast-xml-parser and webpack-dev-server (the latter forced v5 under @wordpress/scripts, which now requires v6)

### Technical
- Pinned Composer platform to PHP 8.2
- CI workflows now set up Node 22 and install with plain `npm ci`
- `wp-scripts lint-js` runs its bundled ESLint 10; the top-level `eslint` package resolves to 9.x because eslint-plugin-import, eslint-plugin-jsx-a11y and eslint-plugin-react do not yet declare ESLint 10 support

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
