#!/usr/bin/env node
/**
 * Headless runner for the consent browser tests.
 *
 * The assertions live in the harness page itself (mu-plugins/02-harness.php),
 * which is what makes it usable by hand as well: start the server, open the two
 * URLs, read the summary line. This file is the same thing without a human —
 * it boots Playground, opens both pages in headless Chromium, waits for the
 * harness to finish, and turns the result into an exit code for CI.
 */
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import { chromium } from 'playwright';

const REPO = path.resolve( path.dirname( fileURLToPath( import.meta.url ) ), '../..' );
const PORT = Number( process.env.BARION_TEST_PORT || 9411 );
const BASE = `http://127.0.0.1:${ PORT }`;

// The whole run takes about 70s on a warm developer machine. A CI runner
// downloads WordPress, WooCommerce and wp-consent-api first and works through
// the scenarios slower, so both ceilings are set well above that rather than
// close to it — a timeout here reads as a consent bug and is worth avoiding.
const BOOT_TIMEOUT_MS = 300000;
const HARNESS_TIMEOUT_MS = 300000;

const PAGES = [
	{ url: `${ BASE }/?barion-harness=1`, name: 'stub consent managers' },
	{ url: `${ BASE }/?barion-harness=real`, name: 'real wp-consent-api plugin' }
];

function startServer() {
	const child = spawn(
		process.execPath,
		[
			path.join( REPO, 'node_modules/@wp-playground/cli/wp-playground.js' ),
			'server',
			`--port=${ PORT }`,
			'--mount-dir', REPO, '/wordpress/wp-content/plugins/advanced-pixel-for-barion',
			'--mount-dir', path.join( REPO, 'tests/playground/mu-plugins' ), '/wordpress/wp-content/mu-plugins',
			`--blueprint=${ path.join( REPO, 'tests/playground/blueprint.json' ) }`
		],
		// Own process group, so the whole Playground tree dies with one kill.
		{ cwd: REPO, detached: true, stdio: [ 'ignore', 'pipe', 'pipe' ] }
	);

	let log = '';
	child.stdout.on( 'data', ( d ) => ( log += d ) );
	child.stderr.on( 'data', ( d ) => ( log += d ) );
	return { child, log: () => log };
}

// Waits for the CLI's own ready line rather than polling the port. Playground
// boots WordPress on the first request, which takes longer than a short
// request timeout allows; retrying past that timeout wedges one of its workers
// per attempt, and with enough attempts the server stops answering at all.
async function waitForServer( server ) {
	const deadline = Date.now() + BOOT_TIMEOUT_MS;
	while ( Date.now() < deadline ) {
		if ( server.child.exitCode !== null ) {
			throw new Error( `Playground exited early:\n${ server.log() }` );
		}
		if ( server.log().includes( 'WordPress is running on' ) ) {
			return;
		}
		await new Promise( ( r ) => setTimeout( r, 500 ) );
	}
	throw new Error( `Playground did not start within ${ BOOT_TIMEOUT_MS }ms:\n${ server.log() }` );
}

async function runPage( browser, { url, name } ) {
	// A fresh context per page: the harness clears its own cookies between
	// scenarios, but a stale consent cookie from the other page would decide
	// the first scenario before it starts.
	const context = await browser.newContext();
	const page = await context.newPage();
	// Generous: the first navigation is what actually boots WordPress.
	await page.goto( url, { timeout: HARNESS_TIMEOUT_MS } );
	await page.waitForFunction( () => window.__done === true, null, { timeout: HARNESS_TIMEOUT_MS } );
	const results = await page.evaluate( () => window.__results );
	await context.close();

	console.log( `\n${ name } — ${ url }` );
	for ( const r of results ) {
		console.log( `  ${ r.pass ? 'ok  ' : 'FAIL' }  ${ r.name } -> ${ JSON.stringify( r.got ) }` );
		if ( ! r.pass ) {
			console.log( `        expected ${ JSON.stringify( r.expected ) }` );
			for ( const line of r.log || [] ) {
				console.log( `        ${ line }` );
			}
		}
	}
	return results;
}

const server = startServer();
let failed = 0;
let total = 0;

try {
	await waitForServer( server );
	const browser = await chromium.launch();
	try {
		for ( const target of PAGES ) {
			const results = await runPage( browser, target );
			total += results.length;
			failed += results.filter( ( r ) => ! r.pass ).length;
		}
	} finally {
		await browser.close();
	}
} finally {
	try {
		process.kill( -server.child.pid );
	} catch {
		// Already gone.
	}
}

console.log( `\n${ total - failed }/${ total } scenarios passed` );
process.exit( failed ? 1 : 0 );
