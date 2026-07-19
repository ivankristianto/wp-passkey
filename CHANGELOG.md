# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
