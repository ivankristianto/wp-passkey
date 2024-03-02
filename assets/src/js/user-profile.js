import { browserSupportsWebAuthn, startRegistration } from '@simplewebauthn/browser';
import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';
import { __ } from '@wordpress/i18n';

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

/**
 * Passkey Registration Handler.
 */
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
				registerMessage.innerText = __(
					'Error: Authenticator was probably already registered by you',
					'biometric-authentication',
				);
			} else {
				registerMessage.innerText = `Error: ${ error.message }`;
			}
			registerMessage.classList.add( 'error' );
		}
	} );
} );

/**
 * Revoke Passkey.
 *
 * @param {Event} event The event.
 */
async function revokePasskey( event ) {
	event.preventDefault();

	if (
		// eslint-disable-next-line no-alert
		! window.confirm(
			__( 'Are you sure you want to revoke this passkey? This action cannot be undone.', 'biometric-authentication' ),
		)
	) {
		return;
	}

	const revokeButton = event.target;
	const fingerprint = revokeButton.dataset.id;

	try {
		const response = await apiFetch( {
			path: '/wp-passkey/v1/revoke',
			method: 'POST',
			data: {
				fingerprint,
			},
		} );

		if ( response.status === 'success' ) {
			window.location.reload();
		}
	} catch ( error ) {
		throw error;
	}
}

/**
 * Passkey Revoke handler.
 */
domReady( () => {
	const revokeButtons = document.querySelectorAll( '.wp-passkey-list-table button.delete' );

	if ( ! revokeButtons ) {
		return;
	}

	revokeButtons.forEach( revokeButton => {
		revokeButton.addEventListener( 'click', revokePasskey );
	} );
} );
