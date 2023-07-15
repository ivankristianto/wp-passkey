import { browserSupportsWebAuthn, browserSupportsWebAuthnAutofill, startAuthentication } from '@simplewebauthn/browser';
import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';

/**
 * Authenticate Passkey.
 */
async function authenticate() {
	let asseResp;
	try {
		const response = await apiFetch( {
			path: '/wp-passkey/v1/signin-request',
			method: 'POST',
		} );

		asseResp = await startAuthentication( response );

		console.log( 'Anything > asseResp', asseResp ); // eslint-disable-line no-console
	} catch ( error ) {
		throw error;
	}

	// POST the response to the endpoint that calls.
	try {
		const response = await apiFetch( {
			path: '/wp-passkey/v1/signin-response',
			method: 'POST',
			data: asseResp,
		} );

		console.log( 'Anything > response', response ); // eslint-disable-line no-console
	} catch ( error ) {
		throw error;
	}
}

domReady( async () => {
	// If the browser doesn't support WebAuthn, don't do anything.
	if ( ! browserSupportsWebAuthn() ) {
		return;
	}

	const usernameField = document.getElementById( 'user_login' );
	// add autocomplete="webauthn" to the username field.
	if ( usernameField ) {
		usernameField.setAttribute( 'autocomplete', 'username webauthn' );
	}

	if ( browserSupportsWebAuthnAutofill() ) {
		await authenticate();
	}
} );
