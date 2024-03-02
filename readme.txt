=== Biometric Authentication ===
Contributors: ivankristianto
Requires at least: 6.1
Tags: identity, authentication, biometric, passwordless, login, security
Tested up to: 6.2.2
Requires PHP: 8.1
Stable tag: 0.3.1
License: GPL v2 or later

Provides biometric authentication for WordPress

== Description ==
Passkeys are a safer and easier alternative to passwords. With passkeys, users can sign in to apps and websites with a biometric sensor (such as a fingerprint or facial recognition), PIN, or pattern, freeing them from having to remember and manage passwords.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/biometric-authentication` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. To create your first passkey, you can go to your Admin > Profile screen.
4. Now you can login with your passkey.

== Frequently Asked Questions ==

To be added later, in the meantime file your question through [GitHub Issue](https://github.com/ivankristianto/wp-passkey/issues)

== Changelog ==

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
