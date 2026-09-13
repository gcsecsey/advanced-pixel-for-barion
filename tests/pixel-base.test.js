const test = require( 'node:test' );
const assert = require( 'node:assert' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );

const SOURCE = fs.readFileSync(
	path.join( __dirname, '../assets/js/barion-pixel-base.js' ),
	'utf8'
);

/**
 * Runs the base script against a fake window and document.
 *
 * The file is an IIFE with nothing to require, and what it decides — whether to
 * fetch a second copy of bp.js — only shows in the script tags it leaves
 * behind. So it runs in a vm context whose document collects them.
 *
 * @param {object} options.bpAlreadyThere Global bp() another pixel source left.
 * @param {boolean} options.debug         Debug mode, as the plugin passes it.
 */
const load = ( options = {} ) => {
	const scripts = [];
	const listeners = {};
	const warnings = [];
	const logs = [];

	const firstScript = { src: '', parentNode: null };
	const head = {
		insertBefore: ( node ) => scripts.unshift( node ),
	};
	firstScript.parentNode = head;
	scripts.push( firstScript );

	const document = {
		readyState: 'loading',
		createElement: () => ( { src: '', async: false } ),
		getElementsByTagName: ( name ) => ( 'script' === name ? scripts : [] ),
		addEventListener: ( name, handler ) => {
			listeners[ name ] = listeners[ name ] || [];
			listeners[ name ].push( handler );
		},
	};

	const context = {
		document,
		console: {
			log: ( ...args ) => logs.push( args.join( ' ' ) ),
			warn: ( ...args ) => warnings.push( args.join( ' ' ) ),
		},
		wcBarionPixelBase: { pixelId: 'BP-0000000000-00', debug: !! options.debug },
	};
	context.window = context;

	const bpCalls = [];
	if ( options.bpAlreadyThere ) {
		// What the plain Barion snippet leaves behind: a bp() queue stub and
		// nothing else. It never sets window.BarionAnalyticsObject.
		context.bp = ( ...args ) => bpCalls.push( args );
	}

	vm.createContext( context );
	vm.runInContext( SOURCE, context );

	const originalBp = context.bp;
	context.bp = ( ...args ) => {
		bpCalls.push( args );
		return originalBp && originalBp( ...args );
	};

	return {
		context,
		bpCalls,
		warnings,
		logs,
		loaders: () =>
			scripts.filter(
				( node ) => node.src && node.src.indexOf( 'pixel.barion.com/bp.js' ) > -1
			).length,
		// The gateway snippet runs later in the page than this script, so the
		// second loader can only appear after it has already decided.
		addLateLoader: () =>
			scripts.push( { src: 'https://pixel.barion.com/bp.js' } ),
		fire: ( name ) =>
			( listeners[ name ] || [] ).forEach( ( handler ) => handler() ),
	};
};

test( 'loads bp.js when no other pixel source is on the page', () => {
	assert.strictEqual( load().loaders(), 1 );
} );

test( 'does not load bp.js again when a plain Barion snippet already defined bp()', () => {
	// The regression: the guard used to also require window.BarionAnalyticsObject,
	// which the plain snippet — a Google Tag Manager tag, a snippet in the
	// theme header, the payment gateway — never sets. So the page loaded bp.js
	// twice.
	assert.strictEqual( load( { bpAlreadyThere: true } ).loaders(), 0 );
} );

test( 'still sends its own init when another pixel source loaded bp.js', () => {
	const run = load( { bpAlreadyThere: true } );
	assert.deepStrictEqual( run.bpCalls, [
		[ 'init', 'addBarionPixelId', 'BP-0000000000-00' ],
	] );
} );

test( 'warns in debug mode when a second base pixel loads after this one', () => {
	const run = load( { debug: true } );
	run.addLateLoader();
	run.fire( 'DOMContentLoaded' );
	assert.match( run.warnings.join( '\n' ), /second copy of bp\.js/ );
} );

test( 'says nothing when it is the only base pixel on the page', () => {
	const run = load( { debug: true } );
	run.fire( 'DOMContentLoaded' );
	assert.strictEqual(
		run.warnings.filter( ( line ) => /bp\.js/.test( line ) ).length,
		0
	);
} );

test( 'stays quiet about a duplicate outside debug mode', () => {
	const run = load();
	run.addLateLoader();
	run.fire( 'DOMContentLoaded' );
	assert.deepStrictEqual( run.warnings, [] );
} );
