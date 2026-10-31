/**
 * EdDSA (Ed25519, COSE -8) passkeys end to end.
 *
 * The browser is steered to Ed25519 by filtering the offered algorithms client-side;
 * the server still validates -8 against its own allowed list.
 */
const { test, expect } = require( '@playwright/test' );
const {
	addAuthenticator,
	clickPasskeyLogin,
	createUser,
	decodeCbor,
	deleteUser,
	isLoggedIn,
	passwordLogin,
	storedPasskeys,
} = require( './utils' );

const USER = 'e2e_eddsa';
const PASSWORD = 'e2e-Passw0rd-123';
const COSE_ALG_EDDSA = -8;

test.describe.serial( 'EdDSA passkeys', () => {
	let context;
	let page;

	test.beforeAll( async ( { browser } ) => {
		createUser( USER, PASSWORD );

		context = await browser.newContext();
		page = await context.newPage();
		const cdp = await context.newCDPSession( page );
		await cdp.send( 'WebAuthn.enable' );
		await addAuthenticator( cdp );
	} );

	test.afterAll( async () => {
		await context?.close();
		deleteUser( USER );
	} );

	test( 'registers an Ed25519 credential', async () => {
		await passwordLogin( page, USER, PASSWORD );
		await page.goto( '/wp-admin/profile.php' );

		let offered = [];
		await page.route(
			( url ) => url.href.includes( 'register-request' ),
			async ( route ) => {
				const response = await route.fetch();
				const json = await response.json();
				offered = json.pubKeyCredParams.map( ( param ) => param.alg );
				json.pubKeyCredParams = json.pubKeyCredParams.filter(
					( param ) => param.alg === COSE_ALG_EDDSA
				);
				await route.fulfill( { response, json } );
			}
		);

		const registration = page.waitForResponse( ( response ) =>
			response.url().includes( 'register-response' )
		);
		await page.click( '.wp-register-new-passkey' );
		expect( ( await registration ).status() ).toBe( 200 );

		// Standard EdDSA is offered; the non-standard Ed256/Ed512 identifiers are not.
		expect( offered ).toContain( COSE_ALG_EDDSA );
		expect( offered ).not.toContain( -260 );
		expect( offered ).not.toContain( -261 );

		const [ stored ] = storedPasskeys( USER );
		const coseKey = decodeCbor(
			Buffer.from( stored.credentialPublicKey, 'base64url' )
		);
		expect( coseKey.get( 1 ) ).toBe( 1 ); // kty: OKP.
		expect( coseKey.get( 3 ) ).toBe( COSE_ALG_EDDSA ); // alg: EdDSA.
	} );

	test( 'logs in with the Ed25519 passkey', async ( { baseURL } ) => {
		await context.clearCookies();
		await page.goto( '/wp-login.php' );

		const { status } = await clickPasskeyLogin( page );

		expect( status ).toBe( 200 );
		await page.waitForURL( new URL( '/wp-admin/', baseURL ).href );
		expect( await isLoggedIn( context ) ).toBe( true );
	} );

	test( 'rejects a forged Ed25519 signature', async () => {
		await context.clearCookies();
		await page.goto( '/wp-login.php' );

		// Replace the real signature with 64 zero bytes (the shape cose-lib once accepted).
		const matcher = ( url ) => url.href.includes( 'signin-response' );
		await page.route( matcher, async ( route ) => {
			const body = route.request().postDataJSON();
			body.asseResp.response.signature =
				Buffer.alloc( 64 ).toString( 'base64url' );
			await route.continue( { postData: JSON.stringify( body ) } );
		} );

		const signin = page.waitForResponse( ( response ) =>
			response.url().includes( 'signin-response' )
		);
		await page.click( '#login-via-passkeys' );
		expect( ( await signin ).status() ).toBe( 400 );
		await page.unroute( matcher );

		await expect( page.locator( '#login_error' ) ).toHaveText(
			'Invalid signature.'
		);
		expect( await isLoggedIn( context ) ).toBe( false );
	} );
} );
