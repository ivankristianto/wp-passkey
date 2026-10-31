=== Biometric Authentication ===
Contributors: ivankristianto
Requires at least: 6.2
Tags: authentication, biometric, passwordless, passkey, security
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 0.5.1
License: GPLv2 or later

Passkeys are a safer and easier alternative to passwords. Simply use your fingerprint or face ID to log in with ease.

== Description ==

This innovative plugin introduces passkey login to your WordPress experience. No more struggling to remember complex passwords.
Simply use your fingerprint, face ID, or a secure PIN to log in with ease. You can still use your username and password to login to your site as fallback.

=== Enhanced Security, Frictionless Access: ===

1. Effortless Login: Unlock the power of passkeys for a smooth and secure login experience.
2. Superior Security: Passkeys offer enhanced protection against breaches compared to traditional passwords.
3. Convenience at Your Fingertips: Enjoy the freedom of logging in with your biometrics or a secure PIN.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/biometric-authentication` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. To create your first passkey, you can go to your Admin > Profile screen.
4. Now you can login with your passkey.

== GitHub Repository ==

You can find the source code of this plugin on [GitHub](https://github.com/ivankristianto/wp-passkey/)

== Frequently Asked Questions ==

To be added later, in the meantime file your question through [GitHub Issue](https://github.com/ivankristianto/wp-passkey/issues)

== Changelog ==

### 0.5.1

- Fix the WordPress.org package: keep symfony/polyfill-uuid below 1.43, whose PHP 8.5-only file fails WordPress.org's PHP checks
- Includes all 0.5.0 changes below (0.5.0 was not published to WordPress.org)

### 0.5.0

- Security: Validate the post-login redirect on the server to prevent XSS and open redirects via `redirect_to`
- Security: Save the passkey signature counter after each login so cloned authenticators are rejected
- Security: Use cryptographically secure challenges; registration challenges now expire after 5 minutes and are single-use
- Security: Reject registering a passkey that is already registered
- Security: Update web-auth/webauthn-lib to 5.3.9 and web-auth/cose-lib to 4.8.2 (full-origin checks, EdDSA signature verification)
- Replace non-standard Ed256/Ed512 algorithms with standard EdDSA, enabled only when the sodium extension is available
- Show login errors as plain text

### 0.4.0

- Minimum PHP requirement increased to 8.2
- Updated web-auth/webauthn-lib to 5.x
- Security hardening for passkey validation and revocation

### 0.3.9

- Security: Fix npm vulnerabilities

### 0.3.8

- Update plugin description

### 0.3.1

- Update plugin name
- Update dependencies
- Fix code standards

### 0.2.1

- Fix release GitHub action.

### 0.2.0

- Allow user to revoke passkey.
- Add name & created date as extra data for passkey entity.
- Fix name override when signing in with passkey.

### 0.1.0

- Initial release.
