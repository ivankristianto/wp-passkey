import { browserSupportsWebAuthn, startRegistration } from '@simplewebauthn/browser';
import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';

import '../scss/user-profile.scss';

/**
 * Create Passkey Registration.
 */
async function createRegistration() {
	let attResp;

	try {
		const response = await apiFetch( {
			path: '/wp-passkey/v1/register-request',
			method: 'POST',
		} );

		attResp = await startRegistration( response );
	} catch ( error ) {
		throw error;
	}

	// POST the response to the endpoint that calls.
	try {
		const response = await apiFetch( {
			path: '/wp-passkey/v1/register-response',
			method: 'POST',
			data: attResp,
		} );

		if ( response.status === 'verified' ) {
			window.location.reload();
		}
	} catch ( error ) {
		throw error;
	}
}

domReady( () => {
	const registerButton = document.querySelector( '.wp-register-new-passkey' );
	const registerMessage = document.querySelector( '.wp-register-passkey--message' );

	if ( ! registerButton || ! registerMessage ) {
		return;
	}

	// Hide register button if browser doesn't support WebAuthn.
	if ( ! browserSupportsWebAuthn() ) {
		registerButton.style.display = 'none';
		return;
	}

	registerButton.addEventListener( 'click', async () => {
		try {
			await createRegistration();
		} catch ( error ) {
			// Some basic error handling
			if ( error.name === 'InvalidStateError' ) {
				registerMessage.innerText = 'Error: Authenticator was probably already registered by you';
			} else {
				registerMessage.innerText = `Error: ${ error.message }`;
			}
			registerMessage.classList.add( 'error' );
		}
	} );
} );
