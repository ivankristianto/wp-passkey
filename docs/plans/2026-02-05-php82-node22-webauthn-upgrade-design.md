# Design: Incremental Upgrade to PHP 8.2, Node 22, and webauthn-lib 5.2.3

**Date:** 2026-02-05
**Version:** 0.4.0
**Author:** Claude Code (Brainstorming Session)

## Overview and Goals

**Objective:** Upgrade the wp-passkey plugin to support PHP 8.2 and Node 22, then migrate to webauthn-lib 5.2.3, while maintaining functionality for existing users.

**Version:** 0.4.0 (continuing 0.x series)

**Strategy:** Incremental updates in two phases:
1. **Phase 1:** Update version requirements (PHP 8.2, Node 22) across all configuration files
2. **Phase 2:** Upgrade webauthn-lib to 5.2.3 and address compatibility issues

**Safety Mechanism:** WordPress will automatically prevent installation on incompatible PHP versions via the "Requires PHP" metadata in plugin.php and readme.txt.

**Why Incremental?**
- Easier to isolate issues (environment vs. library changes)
- Can verify environment compatibility before tackling library breaking changes
- Smaller, more reviewable commits
- Safer rollback if issues arise

**Testing Approach:** After each phase:
- Run automated checks: `composer install`, `npm install`, `npm run build`, `npm run lint`, `npm run test-unit-php`
- Manual verification: Start local environment, test passkey registration and login
- Verify existing stored credentials still work (backward compatibility check)

## Phase 1: Update Version Requirements

### Files to Update

**1. composer.json**
```json
"require": {
  "php": ">=8.2",
  "web-auth/webauthn-lib": "^4.9.2"
}
```

**2. plugin.php**
```php
* Requires PHP: 8.2
```

**3. .nvmrc**
```
22
```

**4. .github/workflows/unit-tests.yml**
- Update `php-version: "8.2"` (line 42)
- Update cache key to reference 8.2 (lines 58, 64)
- Add Node.js setup with version 22

**5. .github/workflows/coding-standards.yml**
- Update `php-version: '8.2'` (line 21)
- Update cache key to reference 8.2 (line 42)
- Add Node.js setup with version 22

**6. README.md** (if exists)
- Update requirements section to mention PHP 8.2 and Node 22

### Testing Phase 1

**Automated:**
```bash
composer install
npm install
npm run build
npm run lint
npm run test-unit-php
```

**Manual:**
```bash
npm run server start
# Test in browser:
# 1. Register a new passkey
# 2. Log out
# 3. Log in with the passkey
# 4. Verify existing passkeys still work
```

**Expected Outcome:** All tests pass, no code changes needed, only version requirements updated.

## Phase 2: Upgrade webauthn-lib to 5.2.3

### Breaking Changes Analysis

From the [migration guide](https://webauthn-doc.spomky-labs.com/v4.9/migration/from-v3.x-to-v4.0-1):

**Changes that affect us:**
1. **PSR-20 Clock requirement** - Validators now require a Clock implementation
2. **Method signature changes** - Constructor signatures may have changed

**Changes that DON'T affect us:**
- ❌ PSR-17/PSR-18 removal (we don't use HTTP clients)
- ❌ Database migration (we store JSON in WordPress meta, not Doctrine array columns)
- ❌ Android SafetyNet removal (we don't use it)
- ❌ Serializer/Normalizer removal (we don't use it)

### Implementation Changes

**1. Add PSR-20 Clock Dependency**

Update `composer.json`:
```json
"require": {
  "php": ">=8.2",
  "web-auth/webauthn-lib": "^5.2.3",
  "psr/clock": "^1.0"
}
```

**2. Update Webauthn_Server Class**

File: `inc/class-webauthn-server.php`

Add Clock implementation at the top:
```php
use Psr\Clock\ClockInterface;

class SystemClock implements ClockInterface {
    public function now(): \DateTimeImmutable {
        return new \DateTimeImmutable();
    }
}
```

Update `validate_attestation_response()` method (around line 224):
```php
$authenticator_attestation_response_validator = AuthenticatorAttestationResponseValidator::create(
    $attestation_statement_support_manager,
    $public_key_credential_source_repository,
    null,
    ExtensionOutputCheckerHandler::create(),
    new SystemClock() // Add Clock instance
);
```

Update `validate_assertion_response()` method (around line 280):
```php
$authenticator_assertion_response_validator = AuthenticatorAssertionResponseValidator::create(
    $public_key_credential_source_repository,
    null,
    ExtensionOutputCheckerHandler::create(),
    $this->get_algorithm_manager(),
    new SystemClock() // Add Clock instance
);
```

**Note:** If the constructor signatures don't match, we'll need to check the webauthn-lib 5.x documentation for the exact parameter order.

### Testing Phase 2

**Automated:**
```bash
composer install
npm install
npm run build
npm run lint
npm run test-unit-php
```

**Manual:**
```bash
npm run server start
# Critical tests:
# 1. Register a NEW passkey (tests attestation validator)
# 2. Log in with the NEW passkey (tests assertion validator)
# 3. Log in with an OLD passkey (tests backward compatibility)
# 4. Test revocation/deletion of passkeys
```

**Backward Compatibility Check:**
Existing credentials stored in WordPress user meta should continue working because:
- Storage format (JSON serialization) hasn't changed
- `PublicKeyCredentialSource::createFromArray()` should remain compatible
- Only validator internals changed, not the credential format

## Error Handling and Rollback Plan

### If Phase 1 Fails
- Revert version number changes
- Issue: Likely environment incompatibility
- Action: Check local PHP/Node versions, update CI runner images

### If Phase 2 Fails

**Common Issues:**
1. **Constructor signature mismatch**
   - Check webauthn-lib 5.x API documentation
   - Verify parameter order and types
   - May need to add/remove parameters

2. **Clock implementation issues**
   - Verify PSR-20 Clock interface implementation
   - Check if `DateTimeImmutable` is acceptable

3. **Stored credentials incompatible**
   - Unlikely but possible
   - Would need data migration script
   - Fallback: Keep webauthn-lib 4.x, only update PHP/Node

**Rollback:**
```bash
git revert <commit-hash>
composer install
npm install
```

## Version Bump and Release

**Plugin Version:** 0.4.0

**Files to Update:**
- `plugin.php` - Update `Version: 0.4.0`
- `package.json` - Update `"version": "0.4.0"`

**Changelog Entry:**
```
## [0.4.0] - 2026-02-05

### Changed
- Minimum PHP requirement increased to 8.2
- Minimum Node.js requirement increased to 22
- Updated web-auth/webauthn-lib to 5.2.3

### Technical
- Added PSR-20 Clock implementation for WebAuthn validators
- Updated GitHub Actions workflows for PHP 8.2 and Node 22
```

## Success Criteria

**Phase 1 Complete When:**
- ✅ All version requirements updated in all files
- ✅ CI/CD pipelines use PHP 8.2 and Node 22
- ✅ `composer install` and `npm install` succeed
- ✅ All linters pass
- ✅ All unit tests pass
- ✅ Manual testing confirms passkeys work

**Phase 2 Complete When:**
- ✅ webauthn-lib 5.2.3 installed
- ✅ PSR-20 Clock implementation added
- ✅ Validators updated with Clock instances
- ✅ All linters pass
- ✅ All unit tests pass
- ✅ Manual testing confirms:
  - New passkey registration works
  - New passkey login works
  - Existing passkeys still work
  - Passkey revocation works

**Release Ready When:**
- ✅ Both phases complete
- ✅ Version bumped to 0.4.0
- ✅ CHANGELOG.md updated
- ✅ README.md updated (if applicable)
- ✅ All quality gates pass (build, lint, test)
- ✅ Git worktree merged or PR created

## References

- [webauthn-lib 5.x Migration Guide](https://webauthn-doc.spomky-labs.com/v4.9/migration/from-v3.x-to-v4.0-1)
- [webauthn-lib Releases](https://github.com/web-auth/webauthn-framework/releases)
- [web-auth/webauthn-lib on Packagist](https://packagist.org/packages/web-auth/webauthn-lib)
- [PSR-20: Clock Interface](https://www.php-fig.org/psr/psr-20/)
