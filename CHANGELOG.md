# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Security
- Updated wp-coding-standards/wpcs to ^3.4.1 (CVE-2026-45293)
- Updated @wordpress/scripts to ^37.0.0 and @wordpress/env to ^12.0.0, resolving most npm audit advisories

### Changed
- Updated @simplewebauthn/browser from ^13.1.0 to ^14.0.0 and switched to the `{ optionsJSON }` call signature
- Updated web-auth/webauthn-lib to ^5.3.9 and resolved Composer dependencies to stable releases only
- Updated phpstan to ^2.3, szepeviktor/phpstan-wordpress to ^2.0, wp-phpunit to ^7.1 and yoast/phpunit-polyfills to ^4.0
- Migrated ESLint configuration to flat config (`eslint.config.js`) for ESLint 10
- Updated archiver to ^8.0.0 in the release script

### Technical
- Pinned Composer platform to PHP 8.2
- CI workflows now set up Node from `.nvmrc` and install with plain `npm ci`

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
