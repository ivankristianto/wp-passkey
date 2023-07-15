import { browserSupportsWebAuthn, startRegistration } from '@simplewebauthn/browser';
import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';

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

		console.log( 'Anything > response', response ); // eslint-disable-line no-console

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

		console.log( 'Anything > response', response ); // eslint-disable-line no-console
	} catch ( error ) {
		throw error;
	}
}

domReady( () => {
	const registerButton = document.querySelector( '.wp-register-passkey' );
	const registerMessage = document.querySelector( '.wp-register-passkey--message' );

	if ( ! registerButton || ! registerMessage ) {
		return;
	}

	// Hide register button if browser doesn't support WebAuthn.
	if ( ! browserSupportsWebAuthn() ) {
		registerButton.style.display = 'none';
		return;
	}

	registerButton.addEventListener( 'click', () => {
		try {
			createRegistration();
		} catch ( error ) {
			// Some basic error handling
			if ( error.name === 'InvalidStateError' ) {
				registerMessage.innerText = 'Error: Authenticator was probably already registered by user';
			} else {
				registerMessage.innerText = error;
			}
		}
	} );
} );
