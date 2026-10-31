import {
	browserSupportsWebAuthn,
	startAuthentication,
} from '@simplewebauthn/browser';
import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';

import '../scss/login.scss';

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
		asseResp = await startAuthentication( { optionsJSON: options } );
	} catch ( error ) {
		throw error;
	}

	// POST the response to the endpoint that calls.
	try {
		// Pass the requested redirect to the server, which validates it.
		const urlParams = new URLSearchParams( window.location.search );

		const response = await apiFetch( {
			path: '/wp-passkey/v1/signin-response',
			method: 'POST',
			data: {
				request_id: requestId,
				asseResp,
				redirect_to: urlParams.get( 'redirect_to' ) || '',
			},
		} );

		if (
			response.status !== 'verified' ||
			typeof response.redirect_to !== 'string'
		) {
			throw new Error( 'Passkey authentication failed.' );
		}

		// Only navigate to the server-validated redirect URL.
		window.location.assign( response.redirect_to );
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
	errorElement.textContent = message;

	// Add error element before login form.
	loginForm.parentNode.insertBefore( errorElement, loginForm );

	loginForm.classList.add( 'shake' );
}

domReady( async () => {
	// If the browser doesn't support WebAuthn, don't do anything.
	if ( ! browserSupportsWebAuthn() ) {
		return;
	}

	const loginButton = document.getElementById( 'login-via-passkeys' );

	if ( ! loginButton ) {
		return;
	}

	loginButton.addEventListener( 'click', async ( event ) => {
		event.preventDefault();

		try {
			await authenticate();
		} catch ( error ) {
			showError( error.message );
		}
	} );
} );
