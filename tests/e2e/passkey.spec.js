/**
 * Passkey registration, login redirects, counter persistence, clone detection and revocation.
 */
const { test, expect } = require( '@playwright/test' );
const {
	addAuthenticator,
	clickPasskeyLogin,
	createUser,
	deleteUser,
	isLoggedIn,
	passwordLogin,
	storedPasskeys,
} = require( './utils' );

const USER = 'e2e_passkey';
const PASSWORD = 'e2e-Passw0rd-123';

test.describe.serial( 'Passkey login', () => {
	let context;
	let page;
	let cdp;
	let authenticatorId;

	test.beforeAll( async ( { browser } ) => {
		createUser( USER, PASSWORD );

		context = await browser.newContext();
		page = await context.newPage();
		cdp = await context.newCDPSession( page );
		await cdp.send( 'WebAuthn.enable' );
		authenticatorId = await addAuthenticator( cdp );
	} );

	test.afterAll( async () => {
		await context?.close();
		deleteUser( USER );
	} );

	/**
	 * Replace the virtual authenticator with one holding the given credential.
	 *
	 * @param {Object} credential Credential exported with WebAuthn.getCredentials.
	 */
	async function swapAuthenticator( credential ) {
		await cdp.send( 'WebAuthn.removeVirtualAuthenticator', {
			authenticatorId,
		} );
		authenticatorId = await addAuthenticator( cdp );
		await cdp.send( 'WebAuthn.addCredential', {
			authenticatorId,
			credential,
		} );
	}

	/**
	 * The authenticator's only credential.
	 *
	 * @return {Promise<Object>} Credential.
	 */
	async function currentCredential() {
		const { credentials } = await cdp.send( 'WebAuthn.getCredentials', {
			authenticatorId,
		} );
		return credentials[ 0 ];
	}

	test( 'registers a passkey from the profile page', async () => {
		await passwordLogin( page, USER, PASSWORD );
		await page.goto( '/wp-admin/profile.php' );

		const registration = page.waitForResponse( ( response ) =>
			response.url().includes( 'register-response' )
		);
		await page.click( '.wp-register-new-passkey' );
		expect( ( await registration ).status() ).toBe( 200 );

		await expect(
			page.locator( '.wp-passkey-list-table button.delete' )
		).toHaveCount( 1 );
		expect( storedPasskeys( USER ) ).toHaveLength( 1 );
	} );

	test( 'blocks registering the same authenticator twice', async () => {
		await page.click( '.wp-register-new-passkey' );

		await expect(
			page.locator( '.wp-register-passkey--message' )
		).toContainText( 'already registered' );
		expect( storedPasskeys( USER ) ).toHaveLength( 1 );
	} );

	const redirectCases = [
		{
			name: 'javascript: URL is not executed',
			redirectTo: 'javascript:alert(document.domain)',
			expected: '/wp-admin/',
		},
		{
			name: 'external URL falls back to admin',
			redirectTo: 'https://evil.example/phish',
			expected: '/wp-admin/',
		},
		{
			name: 'protocol-relative URL falls back to admin',
			redirectTo: '//evil.example/phish',
			expected: '/wp-admin/',
		},
		{
			name: 'same-site URL is honoured',
			redirectTo: '{base}/wp-admin/users.php',
			expected: '/wp-admin/users.php',
		},
		{
			name: 'path-relative URL resolves against the site',
			redirectTo: 'wp-admin/profile.php',
			expected: '/wp-admin/profile.php',
		},
		{
			name: 'missing redirect_to goes to admin',
			redirectTo: null,
			expected: '/wp-admin/',
		},
	];

	for ( const { name, redirectTo, expected } of redirectCases ) {
		test( `passkey login redirect: ${ name }`, async ( { baseURL } ) => {
			await context.clearCookies();

			const dialogs = [];
			const onDialog = async ( dialog ) => {
				dialogs.push( dialog.message() );
				await dialog.dismiss();
			};
			const offsite = [];
			const onRequest = ( request ) => {
				if ( new URL( request.url() ).hostname === 'evil.example' ) {
					offsite.push( request.url() );
				}
			};
			page.on( 'dialog', onDialog );
			page.on( 'request', onRequest );

			const target =
				redirectTo === null
					? ''
					: `?redirect_to=${ encodeURIComponent(
							redirectTo.replace( '{base}', baseURL )
						) }`;
			await page.goto( `/wp-login.php${ target }` );

			const { status, body } = await clickPasskeyLogin( page );
			const expectedUrl = new URL( expected, baseURL ).href;

			expect( status ).toBe( 200 );
			expect( body.redirect_to ).toBe( expectedUrl );
			await page.waitForURL( expectedUrl, { timeout: 10_000 } );
			// Give a javascript: URL a chance to run if it was (wrongly) navigated to.
			await page.waitForTimeout( 500 );

			page.off( 'dialog', onDialog );
			page.off( 'request', onRequest );

			expect( dialogs ).toEqual( [] );
			expect( offsite ).toEqual( [] );
			expect( await isLoggedIn( context ) ).toBe( true );
		} );
	}

	test( 'persists the signature counter', async () => {
		const credential = await currentCredential();
		const [ stored ] = storedPasskeys( USER );

		expect( credential.signCount ).toBeGreaterThan( 0 );
		expect( stored.counter ).toBe( credential.signCount );
		// Extra data survives the counter update.
		expect( stored.extra.name ).toMatch( /^Generated on / );
	} );

	test( 'rejects a cloned authenticator with a lower counter', async () => {
		const credential = await currentCredential();
		await swapAuthenticator( { ...credential, signCount: 1 } );
		await context.clearCookies();
		await page.goto( '/wp-login.php' );

		const { status } = await clickPasskeyLogin( page );

		expect( status ).toBe( 400 );
		const error = page.locator( '#login_error' );
		await expect( error ).toHaveText( 'Invalid counter.' );
		// Rendered as text, not HTML.
		await expect( error.locator( '*' ) ).toHaveCount( 0 );
		expect( await isLoggedIn( context ) ).toBe( false );

		// Restore an authenticator that is ahead of the stored counter.
		await swapAuthenticator( {
			...credential,
			signCount: credential.signCount + 1,
		} );
	} );

	test( 'revoked passkey can no longer log in', async () => {
		const credential = await currentCredential();

		await passwordLogin( page, USER, PASSWORD );
		await page.goto( '/wp-admin/profile.php' );

		page.once( 'dialog', ( dialog ) => dialog.accept() );
		const revoke = page.waitForResponse( ( response ) =>
			response.url().includes( '/revoke' )
		);
		await page.click( '.wp-passkey-list-table button.delete' );
		expect( ( await revoke ).status() ).toBe( 200 );

		await expect(
			page.locator( '.wp-passkey-list-table button.delete' )
		).toHaveCount( 0 );
		expect( storedPasskeys( USER ) ).toHaveLength( 0 );

		await swapAuthenticator( {
			...credential,
			signCount: credential.signCount + 1,
		} );
		await context.clearCookies();
		await page.goto( '/wp-login.php' );

		const { status } = await clickPasskeyLogin( page );

		expect( status ).not.toBe( 200 );
		await expect( page.locator( '#login_error' ) ).toBeVisible();
		expect( await isLoggedIn( context ) ).toBe( false );
	} );
} );
