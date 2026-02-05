# Unit Tests with 80% Coverage - Design Document

**Date:** 2026-02-05
**Status:** Approved
**Goal:** Achieve 80%+ code coverage with maintainable unit tests focusing on testable business logic

## Testing Strategy

### Overall Approach

Create comprehensive tests for all 5 REST API endpoints, focusing on testable business logic while mocking WebAuthn library calls. This achieves 80%+ coverage by testing:

1. **REST API Endpoints** (primary focus)
   - `/register-request` - Test credential creation options generation
   - `/register-response` - Test credential validation and storage
   - `/signin-request` - Test assertion options and challenge storage
   - `/signin-response` - Test assertion validation and authentication
   - `/revoke` - Test credential deletion with proper authorization

2. **Source_Repository** (secondary)
   - Credential serialization/deserialization
   - Database operations (save, find, delete)
   - Base64url encoding consistency

### Test File Structure

```
tests/php/
├── test-rest-api.php (expand existing)
├── test-source-repository.php (new)
└── fixtures/
    └── WebauthnTestHelper.php (shared mocks & factories)
```

### Coverage Targets

- `rest-api.php`: 85% (all endpoints, happy path + errors)
- `class-source-repository.php`: 80% (CRUD operations, serialization)
- `class-webauthn-server.php`: 60% (wrapper methods, skip complex crypto)
- **Overall: 80%+**

## Mocking Strategy & Test Helpers

### WebauthnTestHelper Class

Create a helper class to provide mock factories and test data:

```php
class WebauthnTestHelper {
    // Mock credential creation
    public static function mock_credential_record($user_handle = 'testuser') {
        return CredentialRecord::create(
            random_bytes(32), // publicKeyCredentialId
            'public-key',
            [],
            'none',
            EmptyTrustPath::create(),
            Uuid::v4(),
            random_bytes(77), // credentialPublicKey (COSE format)
            $user_handle,
            0
        );
    }

    // Mock attestation validator
    public static function mock_attestation_validator($return_credential) {
        $mock = Mockery::mock(AuthenticatorAttestationResponseValidator::class);
        $mock->shouldReceive('check')->andReturn($return_credential);
        return $mock;
    }

    // Mock assertion validator
    public static function mock_assertion_validator($return_credential) {
        $mock = Mockery::mock(AuthenticatorAssertionResponseValidator::class);
        $mock->shouldReceive('check')->andReturn($return_credential);
        return $mock;
    }
}
```

### Dependencies

Add Mockery for mocking:
```json
"require-dev": {
    "mockery/mockery": "^1.5"
}
```

### Mocking Approach

- Mock WebAuthn library validators (not wrapper classes)
- Use dependency injection where needed (may need to refactor `Webauthn_Server`)
- Mock WordPress functions (`set_transient`, `get_transient`) for challenge storage

## REST API Endpoint Test Cases

### `/register-request` (authenticated)
- ✅ Happy path: Returns serialized credential creation options with challenge
- ❌ Unauthenticated user: Returns 401/403
- ❌ WebAuthn server throws exception: Returns 400 with error message

### `/register-response` (authenticated)
- ✅ Happy path: Valid attestation response saves credential, returns success
- ✅ Platform detection: Correctly identifies Android/iPhone/Mac/Windows/Linux
- ❌ Unauthenticated: Returns 401/403
- ❌ Empty request body: Returns 400
- ❌ Invalid attestation response: Returns 400 with validation error
- ❌ Database save fails: Returns 400 with error message

### `/signin-request` (public)
- ✅ Happy path: Returns assertion options with request_id and challenge stored in transient
- ❌ WebAuthn server throws exception: Returns 400

### `/signin-response` (public)
- ✅ Happy path: Valid assertion authenticates user, sets auth cookie, returns success
- ❌ Empty request body: Returns 400
- ❌ Invalid/missing request_id: Returns 400 invalid challenge
- ❌ Challenge expired (not in transient): Returns 400
- ❌ Invalid assertion response: Returns 400
- ❌ User not found: Returns 404

### `/revoke` (authenticated)
- ✅ Happy path: Deletes credential, returns success
- ❌ Unauthenticated: Returns 401/403
- ❌ Missing fingerprint param: Returns 400
- ❌ Credential not found: Returns 404
- ❌ Delete fails: Returns 400

**Total: ~23 test cases** covering all critical paths.

## Source_Repository Test Cases

### `findOneByCredentialId()`
- ✅ Happy path: Finds existing credential by binary ID
- ✅ Encoding consistency: Properly encodes credential ID for meta key lookup
- ❌ Credential not found: Returns null
- ❌ Invalid JSON in database: Handles gracefully

### `findAllForUserEntity()`
- ✅ Happy path: Returns array of credentials for user
- ✅ Empty result: Returns empty array when user has no credentials
- ❌ User not found: Throws exception

### `saveCredentialSource()`
- ✅ Happy path: Saves credential with base64url encoding
- ✅ Extra data: Stores platform, created timestamp, user agent
- ❌ User not found: Throws exception

### `deleteCredentialSource()`
- ✅ Happy path: Deletes credential successfully
- ❌ User not found: Throws exception
- ❌ Delete fails: Throws exception with message

### Serialization Methods
- ✅ `credential_to_array()`: Properly encodes all binary fields
- ✅ `array_to_credential()`: Properly decodes all fields
- ✅ Round-trip: Save and load preserves credential integrity

**Total: ~14 test cases** covering repository operations.

## Coverage Verification

After writing tests, generate coverage report:

```bash
wp-env run tests-cli --env-cwd=wp-content/plugins/wp-passkey \
  vendor/bin/phpunit --coverage-html coverage/
```

**Target:** 80%+ total coverage with REST API and Source_Repository fully covered.

## Implementation Notes

- Use minimal inline fixtures (create test data arrays in test methods)
- Mock at WebAuthn library level for realistic integration testing
- Focus on happy path + critical errors (auth failures, invalid data, database errors)
- Skip testing complex cryptographic operations (library's responsibility)
- Use Mockery for clean, readable mocks
