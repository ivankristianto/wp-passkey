# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin that implements WebAuthn/Passkey authentication, allowing users to log in using biometric authentication (fingerprint, face ID) instead of traditional passwords. The plugin uses the `web-auth/webauthn-lib` PHP library and `@simplewebauthn/browser` JavaScript library.

## Development Environment

### Local Development Setup

The project uses `@wordpress/env` for local development with Docker:

```bash
# Install dependencies
composer install
npm install

# Build assets
npm run build

# Start WordPress environment
npm run server start
```

The site runs at http://localhost:8888 once started.

### Development Mode (Hot Reload)

```bash
npm run dev
```

This runs `@wordpress/scripts` (Webpack with hot module replacement) for files in `assets/src/`.

## Commands

### Testing

```bash
# Run PHP unit tests
npm run test-unit-php

# Run single test file
wp-env run tests-cli --env-cwd=wp-content/plugins/wp-passkey vendor/bin/phpunit tests/php/test-rest-api.php

# Run specific test method
wp-env run tests-cli --env-cwd=wp-content/plugins/wp-passkey vendor/bin/phpunit --filter test_method_name
```

### Linting

```bash
# Run all linters (JS, CSS, PHP)
npm run lint

# Run individual linters
npm run lint:js    # JavaScript linting
npm run lint:css   # SCSS linting
npm run lint:php   # PHP CodeSniffer

# Auto-fix PHP code style issues
composer format
```

### WP-CLI

```bash
# Run WP-CLI commands
npm run cli wp <commands>

# Examples
npm run cli wp option get siteurl
npm run cli wp user list
npm run cli wp plugin list
```

### Build and Release

```bash
# Clean and build for production
npm run build

# Create release package
npm run release
```

## Architecture

### Plugin Initialization Flow

1. `plugin.php` - Entry point that requires Composer autoloader and includes all PHP files
2. `inc/namespace.php` - `bootstrap()` function initializes all subsystems
3. Three main subsystems bootstrap in order:
   - `Rest_API\bootstrap()` - Registers REST API endpoints
   - `User_Profile\bootstrap()` - Adds passkey management to user profile
   - `Login\bootstrap()` - Adds passkey login functionality to login page

### Core Components

**Webauthn_Server** (`inc/class-webauthn-server.php`)
- Central class that wraps the web-auth/webauthn-lib library
- Handles creation and validation of WebAuthn credentials
- Methods: `generatePublicKeyCredentialCreationOptions()`, `generatePublicKeyCredentialRequestOptions()`, `loadAndCheckAttestationResponse()`, `loadAndCheckAssertionResponse()`

**Source_Repository** (`inc/class-source-repository.php`)
- Implements `PublicKeyCredentialSourceRepository` interface from webauthn-lib
- Stores passkey credentials in WordPress user meta with prefix `wp_passkey_`
- Handles credential storage, retrieval, and revocation
- Methods: `findOneByCredentialId()`, `findAllForUserEntity()`, `saveCredentialSource()`

**REST API** (`inc/rest-api.php`)
- Namespace: `wp-passkey/v1`
- Endpoints:
  - `/register-request` - Initiates passkey registration (authenticated users only)
  - `/register-response` - Completes passkey registration
  - `/authenticate-request` - Initiates passkey authentication (public)
  - `/authenticate-response` - Completes passkey authentication and logs in user

**Frontend Integration** (`inc/login.php` and `inc/user-profile.php`)
- Enqueues JavaScript bundles built from `assets/src/js/`
- Passes REST API endpoints and WordPress nonces to JavaScript via `wp_localize_script()`
- JavaScript uses `@simplewebauthn/browser` to communicate with authenticators

### Data Storage

Passkeys are stored as WordPress user meta:
- Meta key format: `wp_passkey_{credential_id}`
- Each passkey includes: credential data, device name, creation date
- User entity handle is the user's ID encoded in base64url

### Asset Build System

Built with `@wordpress/scripts` (Webpack-based):
- Source: `assets/src/js/` and `assets/src/scss/`
- Output: `assets/dist/`
- Entry points: `login.js` and `user-profile.js`

## Coding Standards

- PHP: WordPress Coding Standards (WordPress-Extra) with Yoda conditions disabled
- Minimum PHP version: 8.2
- Minimum WordPress version: 6.2
- Uses PHP strict types (`declare(strict_types=1)`)
- Namespace: `BioAuth` with sub-namespaces for modules

## Quality Gates

Before claiming any work is complete, committing code, or creating pull requests, you MUST verify:

### 1. Build Verification
```bash
npm run build
```
- Must complete without errors
- Verify dist files are generated in `assets/dist/`

### 2. Linting
```bash
npm run lint
```
- JavaScript linting must pass (`npm run lint:js`)
- CSS/SCSS linting must pass (`npm run lint:css`)
- PHP linting must pass (`npm run lint:php` / `composer lint`)

### 3. Unit Tests
```bash
npm run test-unit-php
```
- All PHP unit tests must pass
- No skipped or failing tests

**CRITICAL:** Never claim "it's working" or "tests pass" without running these commands and verifying the actual output. Evidence before assertions always.

## Learnings: update-webauthn-lib Branch

### ✅ DO's

- ✅ **Use systematic-debugging skill for bugs** - Forces root cause investigation before fixes
- ✅ **Read vendor library source code** - Confirms deprecated methods vs valid helpers (check `vendor/web-auth/webauthn-lib/src/`)
- ✅ **Search for ALL deprecated patterns** - Not just the one reported (use `grep '->get[A-Z]'` to find all getter methods)
- ✅ **Get exact sniff codes with `-s` flag** - Required for correct phpcs ignores (e.g., `WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase`)
- ✅ **Use `composer lint` before committing** - Catches formatting issues early

### ❌ DON'Ts

- ❌ **Don't fix only the reported issue** - Audit entire codebase for similar patterns (webauthn-lib 4.7.0 deprecated many getter methods)
- ❌ **Don't assume phpcs ignore format** - Wrong sniff code = ignored comment fails
- ❌ **Don't guess sniff codes** - Use `./vendor/bin/phpcs -s` to get exact code
- ❌ **Don't commit without running lint** - Multiple re-commits waste time and clutter git history
- ❌ **Don't be lazy with verification** - Always run lint and verify output before claiming done
