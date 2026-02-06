# PHP 8.2, Node 22, and webauthn-lib 5.2.3 Upgrade Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Incrementally upgrade plugin requirements to PHP 8.2 and Node 22, then update webauthn-lib to 5.2.3 with PSR-20 Clock support.

**Architecture:** Two-phase incremental upgrade. Phase 1 updates version requirements in all configuration files without code changes. Phase 2 upgrades webauthn-lib and implements PSR-20 Clock requirement for validators.

**Tech Stack:** PHP 8.2, Composer, Node.js 22, npm, web-auth/webauthn-lib 5.2.3, GitHub Actions

---

## Phase 1: Update Version Requirements

### Task 1: Update PHP and Node Version Requirements

**Files:**
- Modify: `composer.json:14`
- Modify: `plugin.php:17`
- Modify: `.nvmrc:1`
- Modify: `package.json:3`

**Step 1: Update composer.json PHP requirement**

Change line 14 from:
```json
"php": ">=8.1",
```

To:
```json
"php": ">=8.2",
```

**Step 2: Update plugin.php PHP requirement**

Change line 17 from:
```php
 * Requires PHP: 8.1
```

To:
```php
 * Requires PHP: 8.2
```

**Step 3: Update .nvmrc Node version**

Change line 1 from:
```
18
```

To:
```
22
```

**Step 4: Update package.json version to 0.4.0**

Change line 3 from:
```json
"version": "0.3.9",
```

To:
```json
"version": "0.4.0",
```

**Step 5: Update plugin.php version to 0.4.0**

Change line 12 from:
```php
 * Version:      0.3.9
```

To:
```php
 * Version:      0.4.0
```

**Step 6: Run composer install to verify**

Run: `composer install`
Expected: No errors, dependencies install successfully

**Step 7: Run npm install to verify**

Run: `npm install`
Expected: No errors, dependencies install successfully

**Step 8: Commit version requirement updates**

```bash
git add composer.json plugin.php .nvmrc package.json
git commit -m "chore: Update minimum requirements to PHP 8.2 and Node 22

- Bump PHP requirement from 8.1 to 8.2
- Bump Node.js requirement from 18 to 22
- Bump plugin version to 0.4.0"
```

---

### Task 2: Update GitHub Actions Workflows for PHP 8.2

**Files:**
- Modify: `.github/workflows/unit-tests.yml:42,58,64`
- Modify: `.github/workflows/coding-standards.yml:21,42`

**Step 1: Update unit-tests.yml PHP version**

In `.github/workflows/unit-tests.yml`, change line 42 from:
```yaml
php-version: "8.1"
```

To:
```yaml
php-version: "8.2"
```

**Step 2: Update unit-tests.yml cache keys**

In `.github/workflows/unit-tests.yml`:

Change line 58 from:
```yaml
key: ${{ runner.os }}-composer-8.1-${{ hashFiles('composer.lock') }}
```

To:
```yaml
key: ${{ runner.os }}-composer-8.2-${{ hashFiles('composer.lock') }}
```

Change line 64 from:
```yaml
key: ${{ runner.os }}-composer-8.2-${{ hashFiles('composer.lock') }}
```

To:
```yaml
key: ${{ runner.os }}-docker-8.2-${{ hashFiles('composer.lock') }}
```

**Step 3: Update coding-standards.yml PHP version**

In `.github/workflows/coding-standards.yml`, change line 21 from:
```yaml
php-version: '8.1'
```

To:
```yaml
php-version: '8.2'
```

**Step 4: Update coding-standards.yml cache key**

In `.github/workflows/coding-standards.yml`, change line 42 from:
```yaml
key: ${{ runner.os }}-composer-8.1-${{ hashFiles('composer.lock') }}
```

To:
```yaml
key: ${{ runner.os }}-composer-8.2-${{ hashFiles('composer.lock') }}
```

**Step 5: Commit GitHub Actions updates**

```bash
git add .github/workflows/unit-tests.yml .github/workflows/coding-standards.yml
git commit -m "ci: Update GitHub Actions to use PHP 8.2

- Update unit tests workflow to PHP 8.2
- Update coding standards workflow to PHP 8.2
- Fix cache key inconsistency in unit tests workflow"
```

---

### Task 3: Verify Phase 1 with Quality Gates

**Files:**
- None (verification only)

**Step 1: Run build**

Run: `npm run build`
Expected: Webpack builds successfully, outputs to assets/dist/

**Step 2: Run PHP linting**

Run: `npm run lint:php`
Expected: All files pass PHPCS checks

**Step 3: Run CSS linting**

Run: `npm run lint:css`
Expected: All SCSS files pass linting

**Step 4: Commit if any auto-fixes were applied**

If linters made changes:
```bash
git add .
git commit -m "style: Apply automated linting fixes"
```

If no changes, skip this step.

**Step 5: Tag Phase 1 completion**

```bash
git tag phase1-version-requirements
```

---

## Phase 2: Upgrade webauthn-lib to 5.2.3

### Task 4: Update webauthn-lib Dependency

**Files:**
- Modify: `composer.json:15`

**Step 1: Update webauthn-lib version in composer.json**

Change line 15 from:
```json
"web-auth/webauthn-lib": "^4.9.2"
```

To:
```json
"web-auth/webauthn-lib": "^5.2.3"
```

**Step 2: Run composer update**

Run: `composer update web-auth/webauthn-lib`
Expected: Composer updates webauthn-lib to 5.2.x, may show deprecation warnings

**Step 3: Verify PSR-20 Clock is available**

Run: `composer show psr/clock`
Expected: Shows psr/clock package is installed (should be dependency of webauthn-lib)

**Step 4: Commit composer.json and lock file**

```bash
git add composer.json composer.lock
git commit -m "deps: Upgrade web-auth/webauthn-lib to ^5.2.3

This is a major version upgrade with breaking changes:
- Requires PSR-20 Clock implementation
- Updated validator constructor signatures"
```

---

### Task 5: Implement PSR-20 Clock for Validators

**Files:**
- Modify: `inc/class-webauthn-server.php:11,224-228,280-284`

**Step 1: Add PSR Clock use statement**

After line 10 (`namespace BioAuth;`), add these use statements:
```php
use Psr\Clock\ClockInterface;
use DateTimeImmutable;
```

**Step 2: Create SystemClock class**

After the use statements and before the `Webauthn_Server` class (around line 51), add:
```php
/**
 * System Clock implementation for PSR-20.
 */
class System_Clock implements ClockInterface {
	/**
	 * Get current time.
	 *
	 * @return DateTimeImmutable
	 */
	public function now(): DateTimeImmutable {
		return new DateTimeImmutable();
	}
}
```

**Step 3: Update validate_attestation_response method**

Find the `AuthenticatorAttestationResponseValidator::create()` call (around line 224) and check its signature.

Current code:
```php
$authenticator_attestation_response_validator = AuthenticatorAttestationResponseValidator::create(
	$attestation_statement_support_manager,
	$public_key_credential_source_repository,
	null,
	ExtensionOutputCheckerHandler::create()
);
```

Update to include Clock instance. The exact position may vary based on v5 API:
```php
$authenticator_attestation_response_validator = AuthenticatorAttestationResponseValidator::create(
	$attestation_statement_support_manager,
	$public_key_credential_source_repository,
	null,
	ExtensionOutputCheckerHandler::create(),
	new System_Clock()
);
```

**Step 4: Update validate_assertion_response method**

Find the `AuthenticatorAssertionResponseValidator::create()` call (around line 280).

Current code:
```php
$authenticator_assertion_response_validator = AuthenticatorAssertionResponseValidator::create(
	$public_key_credential_source_repository, // The Credential Repository service.
	null,                                     // The token binding handler.
	ExtensionOutputCheckerHandler::create(),  // The extension output checker handler.
	$this->get_algorithm_manager()            // The COSE Algorithm Manager.
);
```

Update to include Clock instance:
```php
$authenticator_assertion_response_validator = AuthenticatorAssertionResponseValidator::create(
	$public_key_credential_source_repository,
	null,
	ExtensionOutputCheckerHandler::create(),
	$this->get_algorithm_manager(),
	new System_Clock()
);
```

**Step 5: Run PHP linting**

Run: `composer lint`
Expected: All files pass PHPCS checks

**Step 6: Auto-fix any code style issues**

Run: `composer format`
Expected: Auto-fixes applied if needed

**Step 7: Commit Clock implementation**

```bash
git add inc/class-webauthn-server.php
git commit -m "feat: Add PSR-20 Clock implementation for webauthn-lib 5.x

- Implement System_Clock class for ClockInterface
- Update AuthenticatorAttestationResponseValidator with Clock
- Update AuthenticatorAssertionResponseValidator with Clock

This is required for webauthn-lib 5.x compatibility."
```

---

### Task 6: Verify Constructor Signatures Match webauthn-lib 5.x

**Files:**
- Modify: `inc/class-webauthn-server.php` (if needed)

**Step 1: Check vendor source for exact constructor signatures**

Read the actual constructor from vendor:
```bash
grep -A 20 "public static function create" vendor/web-auth/webauthn-lib/src/AuthenticatorAttestationResponseValidator.php | head -25
```

Expected: See the exact parameter list

**Step 2: Check assertion validator constructor**

```bash
grep -A 20 "public static function create" vendor/web-auth/webauthn-lib/src/AuthenticatorAssertionResponseValidator.php | head -25
```

Expected: See the exact parameter list

**Step 3: Adjust parameter order/types if needed**

If the grep shows different parameter order or additional required parameters, update the calls in `inc/class-webauthn-server.php` accordingly.

Document what was found:
```bash
echo "Attestation validator signature: [paste here]" >> docs/plans/webauthn-lib-5-findings.txt
echo "Assertion validator signature: [paste here]" >> docs/plans/webauthn-lib-5-findings.txt
git add docs/plans/webauthn-lib-5-findings.txt
git commit -m "docs: Document webauthn-lib 5.x constructor signatures"
```

**Step 4: If adjustments needed, commit them**

Only if Step 3 required changes:
```bash
git add inc/class-webauthn-server.php
git commit -m "fix: Adjust validator constructor calls to match webauthn-lib 5.x API"
```

---

### Task 7: Run Quality Gates for Phase 2

**Files:**
- None (verification only)

**Step 1: Clean build**

Run: `npm run clean && npm run build`
Expected: Clean build succeeds

**Step 2: Run all linters**

Run: `npm run lint:php && npm run lint:css`
Expected: All linting passes (skip JS lint due to worktree conflict)

**Step 3: Document JS lint workaround**

Create note about ESLint worktree issue:
```bash
echo "Note: JS linting skipped in worktree due to ESLint plugin conflict with parent directory. Will verify in main branch before merge." > docs/plans/lint-workaround.txt
git add docs/plans/lint-workaround.txt
git commit -m "docs: Note ESLint worktree limitation"
```

**Step 4: Tag Phase 2 completion**

```bash
git tag phase2-webauthn-upgrade
```

---

## Final Verification and Documentation

### Task 8: Create Comprehensive Changelog

**Files:**
- Create: `CHANGELOG.md` (if doesn't exist) or Modify: `CHANGELOG.md`

**Step 1: Check if CHANGELOG.md exists**

Run: `ls -la CHANGELOG.md`

If exists, proceed to Step 2.
If not exists, create with this header:
```markdown
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
```

**Step 2: Add entry for version 0.4.0**

Add at the top of the changelog (after header):
```markdown
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
```

**Step 3: Commit changelog**

```bash
git add CHANGELOG.md
git commit -m "docs: Add changelog entry for version 0.4.0"
```

---

### Task 9: Update README with New Requirements

**Files:**
- Modify: `README.md` (if exists)

**Step 1: Check if README.md exists**

Run: `ls -la README.md`

**Step 2: Update requirements section**

Find the requirements section and update:
```markdown
## Requirements

- PHP 8.2 or higher
- WordPress 6.2 or higher
- Node.js 22 or higher (for development)
- Modern browser with WebAuthn support
```

**Step 3: Commit README updates**

```bash
git add README.md
git commit -m "docs: Update README with new PHP 8.2 and Node 22 requirements"
```

If README.md doesn't exist, skip this task.

---

### Task 10: Final Pre-Merge Verification

**Files:**
- None (verification only)

**Step 1: Review git log**

Run: `git log --oneline main..HEAD`
Expected: See all commits from both phases in a clean, logical order

**Step 2: Verify no uncommitted changes**

Run: `git status`
Expected: "nothing to commit, working tree clean"

**Step 3: Final quality gate check**

Run all in sequence:
```bash
npm run build && npm run lint:php && npm run lint:css
```
Expected: All pass without errors

**Step 4: Create completion summary**

```bash
echo "✅ Phase 1: Version requirements updated (PHP 8.2, Node 22)" > docs/plans/completion-summary.txt
echo "✅ Phase 2: webauthn-lib upgraded to 5.2.3 with PSR-20 Clock" >> docs/plans/completion-summary.txt
echo "✅ Version bumped to 0.4.0" >> docs/plans/completion-summary.txt
echo "✅ Documentation updated (CHANGELOG, README)" >> docs/plans/completion-summary.txt
echo "✅ All quality gates passing" >> docs/plans/completion-summary.txt
git add docs/plans/completion-summary.txt
git commit -m "docs: Add implementation completion summary"
```

---

## Manual Testing Checklist (Post-Implementation)

After all tasks complete, perform these manual tests:

**Environment Setup:**
```bash
npm run server start
# Wait for server to start at http://localhost:8888
```

**Test 1: Register New Passkey**
1. Log in to WordPress admin with password
2. Go to Users → Profile
3. Click "Register New Passkey"
4. Complete biometric authentication prompt
5. Verify passkey appears in list

**Test 2: Login with New Passkey**
1. Log out
2. On login page, click "Sign in with Passkey"
3. Complete biometric authentication
4. Verify successful login

**Test 3: Backward Compatibility (if old passkeys exist)**
1. Use a passkey created before upgrade
2. Verify it still works for login

**Test 4: Revoke Passkey**
1. Go to Users → Profile
2. Click revoke on a passkey
3. Verify it's removed from list
4. Verify revoked passkey can't be used to login

**Expected Results:** All tests pass without errors

---

## Success Criteria

**Phase 1 Complete:**
- ✅ All version requirements updated to PHP 8.2 and Node 22
- ✅ composer install and npm install succeed
- ✅ Linting passes
- ✅ Builds succeed

**Phase 2 Complete:**
- ✅ webauthn-lib 5.2.3 installed
- ✅ PSR-20 Clock implementation added
- ✅ Validator constructors updated
- ✅ All linting passes
- ✅ All builds succeed

**Final Verification:**
- ✅ CHANGELOG.md updated
- ✅ README.md updated (if exists)
- ✅ All quality gates passing
- ✅ Git history clean and logical
- ✅ Manual testing checklist passes

**Ready for:**
- Merge to main branch, OR
- Create pull request for review

---

## Notes

- ESLint workaround: JS linting must be verified after merging to main due to worktree plugin conflict
- Unit tests (npm run test-unit-php) require Docker environment and are not run during implementation
- Manual testing requires WordPress environment (npm run server start)
- Breaking change handled by WordPress "Requires PHP" metadata - users on PHP < 8.2 won't see update
