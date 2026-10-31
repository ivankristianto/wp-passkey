/**
 * Shared helpers for the passkey end-to-end suite.
 */
const path = require( 'path' );
const { execFileSync } = require( 'child_process' );

const REPO_DIR = path.resolve( __dirname, '../..' );
const WP_ENV_CONTAINER = process.env.WP_ENV_CONTAINER || 'cli';

const AUTHENTICATOR_OPTIONS = {
	protocol: 'ctap2',
	transport: 'internal',
	hasResidentKey: true,
	hasUserVerification: true,
	isUserVerified: true,
	automaticPresenceSimulation: true,
};

/**
 * Run a WP-CLI command in the wp-env container (no shell involved).
 *
 * @param {string[]} args WP-CLI arguments, e.g. [ 'user', 'list' ].
 * @return {string} Command stdout.
 */
function wp( args ) {
	return execFileSync(
		'npx',
		[ 'wp-env', 'run', WP_ENV_CONTAINER, 'wp', ...args ],
		{
			cwd: REPO_DIR,
			encoding: 'utf8',
			stdio: [ 'ignore', 'pipe', 'ignore' ],
		}
	);
}

/**
 * Extract the JSON payload from WP-CLI output.
 *
 * @param {string} output Command output.
 * @return {Object|Array<Object>} Parsed JSON.
 */
function parseJson( output ) {
	const start = output.search( /[\[{]/ );
	const end = Math.max(
		output.lastIndexOf( ']' ),
		output.lastIndexOf( '}' )
	);
	return JSON.parse( output.slice( start, end + 1 ) );
}

/**
 * Create a throwaway administrator, replacing any leftover from an aborted run.
 *
 * @param {string} login    User login.
 * @param {string} password Password.
 */
function createUser( login, password ) {
	deleteUser( login );
	wp( [
		'user',
		'create',
		login,
		`${ login }@example.com`,
		'--role=administrator',
		`--user_pass=${ password }`,
	] );
}

/**
 * Delete a user if it exists.
 *
 * @param {string} login User login.
 */
function deleteUser( login ) {
	try {
		wp( [ 'user', 'delete', login, '--yes' ] );
	} catch {
		// User didn't exist.
	}
}

/**
 * Stored passkey credentials for a user, decoded from user meta.
 *
 * @param {string} login User login.
 * @return {Object[]} Stored credential records.
 */
function storedPasskeys( login ) {
	const meta = parseJson(
		wp( [ 'user', 'meta', 'list', login, '--format=json' ] )
	);

	return meta
		.filter(
			( m ) =>
				m.meta_key.startsWith( 'wp_passkey_' ) &&
				m.meta_key !== 'wp_passkey_challenge'
		)
		.map( ( m ) => JSON.parse( m.meta_value ) );
}

/**
 * Minimal CBOR decoder (unsigned/negative ints, byte/text strings, maps), enough for COSE keys.
 *
 * @param {Buffer} buffer CBOR data.
 * @return {number|string|Buffer|Map<number|string, unknown>} Decoded value.
 */
function decodeCbor( buffer ) {
	let offset = 0;

	const readLength = ( info ) => {
		if ( info < 24 ) {
			return info;
		}
		const size = { 24: 1, 25: 2, 26: 4 }[ info ];
		const value = buffer.readUIntBE( offset, size );
		offset += size;
		return value;
	};

	const read = () => {
		const head = buffer[ offset++ ];
		const major = Math.floor( head / 32 );
		const length = readLength( head % 32 );

		switch ( major ) {
			case 0:
				return length;
			case 1:
				return -1 - length;
			case 2:
			case 3: {
				const bytes = buffer.subarray( offset, offset + length );
				offset += length;
				return major === 3 ? bytes.toString( 'utf8' ) : bytes;
			}
			case 5: {
				const map = new Map();
				for ( let i = 0; i < length; i++ ) {
					map.set( read(), read() );
				}
				return map;
			}
			default:
				throw new Error( `Unsupported CBOR major type ${ major }` );
		}
	};

	return read();
}

/**
 * Attach a fresh virtual authenticator to the page.
 *
 * @param {import('@playwright/test').CDPSession} cdp CDP session for the page.
 * @return {Promise<string>} Authenticator ID.
 */
async function addAuthenticator( cdp ) {
	const { authenticatorId } = await cdp.send(
		'WebAuthn.addVirtualAuthenticator',
		{ options: AUTHENTICATOR_OPTIONS }
	);
	return authenticatorId;
}

/**
 * Log in with username and password.
 *
 * @param {import('@playwright/test').Page} page     Page.
 * @param {string}                          login    User login.
 * @param {string}                          password Password.
 */
async function passwordLogin( page, login, password ) {
	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', login );
	await page.fill( '#user_pass', password );
	await Promise.all( [
		page.waitForURL( /\/wp-admin\// ),
		page.click( '#wp-submit' ),
	] );
}

/**
 * Click "Log in with Passkeys" and capture the signin-response.
 *
 * The body is captured through routing because a successful login navigates away
 * before the response body could be read.
 *
 * @param {import('@playwright/test').Page} page Page already on wp-login.php.
 * @return {Promise<{status: number, body: Object}>} The signin-response.
 */
async function clickPasskeyLogin( page ) {
	let resolveCapture;
	const captured = new Promise( ( resolve ) => ( resolveCapture = resolve ) );
	const matcher = ( url ) => url.href.includes( 'signin-response' );

	await page.route( matcher, async ( route ) => {
		const response = await route.fetch();
		const text = await response.text();
		resolveCapture( {
			status: response.status(),
			body: JSON.parse( text ),
		} );
		await route.fulfill( { response, body: text } );
	} );

	await page.click( '#login-via-passkeys' );
	const result = await captured;
	await page.unroute( matcher );

	return result;
}

/**
 * Whether the browser context holds a WordPress logged-in cookie.
 *
 * @param {import('@playwright/test').BrowserContext} context Browser context.
 * @return {Promise<boolean>} Logged in.
 */
async function isLoggedIn( context ) {
	return ( await context.cookies() ).some( ( cookie ) =>
		cookie.name.startsWith( 'wordpress_logged_in_' )
	);
}

module.exports = {
	addAuthenticator,
	clickPasskeyLogin,
	createUser,
	decodeCbor,
	deleteUser,
	isLoggedIn,
	passwordLogin,
	storedPasskeys,
};
