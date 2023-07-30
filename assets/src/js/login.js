import { browserSupportsWebAuthn, browserSupportsWebAuthnAutofill, startAuthentication } from '@simplewebauthn/browser';
import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';

/**
 * Authenticate Passkey.
 */
async function authenticate() {
	let asseResp;
	let requestId;
	try {
		const response = await apiFetch( {
			path: '/wp-passkey/v1/signin-request',
			method: 'POST',
		} );

		const { options, request_id } = response;

		requestId = request_id;
		asseResp = await startAuthentication( options );
	} catch ( error ) {
		throw error;
	}

	// POST the response to the endpoint that calls.
	try {
		const response = await apiFetch( {
			path: '/wp-passkey/v1/signin-response',
			method: 'POST',
			data: {
				request_id: requestId,
				asseResp,
			},
		} );

		if ( response.status !== 'verified' ) {
			throw new Error( 'Passkey authentication failed.' );
		}

		// Get redirect_to from query string.
		const urlParams = new URLSearchParams( window.location.search );
		const redirect_to = urlParams.get( 'redirect_to' ) || '/wp-admin';

		// Redirect to redirect url or wp-admin as default.
		window.location.href = redirect_to;
	} catch ( error ) {
		throw error;
	}
}

/**
 * Show error message.
 *
 * @param {string} message Error message.
 */
function showError( message ) {
	const loginForm = document.getElementById( 'loginform' );

	// Create Error element if not exists.
	const errorElement = document.createElement( 'div' );
	errorElement.id = 'login_error';
	errorElement.className = 'notice notice-error';
	errorElement.innerHTML = message;

	// Add error element before login form.
	loginForm.parentNode.insertBefore( errorElement, loginForm );

	loginForm.classList.add( 'shake' );
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
		try {
			await authenticate();
		} catch ( error ) {
			showError( error.message );
		}
	}
} );
