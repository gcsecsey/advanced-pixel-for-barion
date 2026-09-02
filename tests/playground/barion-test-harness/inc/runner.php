<?php
/**
 * The harness pages.
 *
 * Each page loads every scenario in a same-origin iframe, acts on it, and
 * compares what the page sent to bp() against what it should have sent. The
 * assertions live here rather than in the Node runner so the same page serves
 * both consumers: run.mjs reads window.__results, and a human opening the URL
 * in a Playground preview reads the same verdict on screen. A preview cannot
 * open devtools, because the site runs inside an iframe on another origin.
 *
 * The pages themselves are listed in barion_harness_pages(); ?barion-harness=index
 * links all of them for a human.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Which harness page was asked for.
 *
 * @return string Empty when this is not a harness request.
 */
function barion_harness_mode() {
	$mode = isset( $_GET['barion-harness'] ) ? sanitize_key( wp_unslash( $_GET['barion-harness'] ) ) : '';
	// strval because PHP turns the numeric '1' page key into an int, which a
	// strict in_array would never match against the string from the query.
	$modes = array_merge( array_map( 'strval', array_keys( barion_harness_pages() ) ), array( 'index' ) );

	return in_array( $mode, $modes, true ) ? $mode : '';
}

add_action(
	'template_redirect',
	function () {
		$mode = barion_harness_mode();
		if ( ! $mode ) {
			return;
		}

		header( 'Content-Type: text/html; charset=utf-8' );

		if ( 'index' === $mode ) {
			barion_harness_render_index();
		} else {
			barion_harness_render_runner( $mode );
		}
		exit;
	}
);

/**
 * Shared page chrome. Kept minimal on purpose: this page must not depend on the
 * theme, which the scenarios themselves are busy exercising.
 *
 * @param string $title Page title.
 */
function barion_harness_head( $title ) {
	?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title><?php echo esc_html( $title ); ?></title>
<style>
 body { font: 14px/1.5 -apple-system, system-ui, sans-serif; margin: 24px; max-width: 1100px; }
 h1 { font-size: 20px; }
 .sum { font-size: 18px; font-weight: 600; padding: 8px 12px; border-radius: 4px; display: inline-block; }
 .sum.ok { background: #e6f4ea; color: #12622b; }
 .sum.bad { background: #fce8e6; color: #a50e0e; }
 .row { padding: 6px 10px; border-left: 4px solid #ccc; margin: 4px 0; background: #fafafa; }
 .row.ok { border-color: #34a853; }
 .row.bad { border-color: #ea4335; background: #fff5f5; }
 .why { color: #a50e0e; margin-left: 12px; }
 pre { background: #f6f8fa; padding: 12px; overflow-x: auto; font-size: 12px; }
 iframe { width: 980px; height: 220px; border: 1px solid #ddd; }
 a { color: #1a56db; }
</style>
</head><body>
<h1><?php echo esc_html( $title ); ?></h1>
	<?php
}

/**
 * The human entry point. Lists the assertion pages and every scenario as a
 * link, so a reviewer can drive a banner or a checkout by hand and watch the
 * live panel report what was sent and when.
 */
function barion_harness_render_index() {
	barion_harness_head( 'Barion pixel test harness' );
	echo '<p>This site runs the plugin in real WordPress with real WooCommerce. Every page below either asserts a rule or lets you watch one happen.</p>';

	echo '<h2>Assertion pages</h2><ul>';
	foreach ( barion_harness_pages() as $mode => $page ) {
		printf(
			'<li><a href="%s">%s</a></li>',
			esc_url( home_url( '/?barion-harness=' . $mode ) ),
			esc_html( $page['title'] )
		);
	}
	echo '</ul>';

	echo '<h2>Drive a scenario by hand</h2>';
	echo '<p>Each link opens the shop with the live panel. Watch when <code>grantConsent</code> appears: it must be on your click, never at page load.</p>';

	foreach ( barion_harness_pages() as $page ) {
		$scenarios = call_user_func( $page['scenarios'] );
		if ( ! $scenarios ) {
			continue;
		}
		printf( '<h3>%s</h3><ul>', esc_html( $page['title'] ) );
		foreach ( $scenarios as $scenario ) {
			printf(
				'<li><a href="%s">%s</a></li>',
				esc_url( add_query_arg( 'barion-panel', 1, $scenario['url'] ) ),
				esc_html( $scenario['name'] )
			);
		}
		echo '</ul>';
	}

	if ( ! barion_harness_store_ready() ) {
		echo '<p><strong>WooCommerce is not active, so the shop scenarios are unavailable.</strong></p>';
	}

	echo '</body></html>';
}

/**
 * An assertion page.
 *
 * @param string $mode A key of barion_harness_pages().
 */
function barion_harness_render_runner( $mode ) {
	$page      = barion_harness_pages()[ $mode ];
	$scenarios = call_user_func( $page['scenarios'] );

	barion_harness_head( $page['title'] );
	?>
<div id="sum" class="sum">running…</div>
<div id="rows"></div>
<details><summary>Raw results</summary><pre id="raw"></pre></details>
<script>
var SCENARIOS = <?php echo wp_json_encode( array_values( $scenarios ) ); ?>;
var CART_RESET = <?php echo wp_json_encode( add_query_arg( 'barion-cart', 'empty', home_url( '/' ) ) ); ?>;

function wait( ms ) {
	return new Promise( function ( r ) { window.setTimeout( r, ms ); } );
}

// Polls a condition rather than sleeping a fixed time. The ceiling is what the
// sleep used to be, so a slow CI runner is no worse off, but a warm one stops
// paying for the slack — most of these conditions are met in tens of
// milliseconds.
function until( test, timeout ) {
	var deadline = Date.now() + timeout;
	return new Promise( function ( resolve ) {
		( function poll() {
			if ( test() ) { resolve( true ); return; }
			if ( Date.now() > deadline ) { resolve( false ); return; }
			window.setTimeout( poll, 25 );
		} )();
	} );
}

function loadFrame( url ) {
	return new Promise( function ( resolve ) {
		var frame = document.createElement( 'iframe' );
		frame.src = url;
		frame.addEventListener( 'load', function () { resolve( frame ); } );
		document.body.appendChild( frame );
	} );
}

function calls( win ) {
	return ( win.__bpCalls || [] ).map( function ( c ) { return c.args; } );
}

function pixelLog( win ) {
	return ( win.__bpLog || [] ).filter( function ( l ) {
		return l.indexOf( '[Barion Pixel]' ) > -1;
	} );
}

// Absence cannot be polled for. A scenario that expects nothing has to sit
// through the whole window a wrong call would have arrived in, so these are the
// one place a fixed sleep is right. Do not shorten them to speed the suite up:
// at 500ms the returning-visitor scenarios stopped failing when the gesture
// gate in barion-consent.js was removed, which is the regression they exist to
// catch.
var SETTLE_MS = 800;
var LATE_CMP_MS = 1800;

// Waits for the expected number of calls, then a moment longer so an extra,
// unwanted call is still caught.
async function settle( count, read, timeout ) {
	if ( ! count ) {
		await wait( SETTLE_MS );
		return;
	}
	await until( function () { return read().length >= count; }, timeout );
	await wait( 150 );
}

// --- consent ------------------------------------------------------------

function consentCalls( win ) {
	return calls( win )
		.filter( function ( c ) { return 'consent' === c[ 0 ]; } )
		.map( function ( c ) { return c[ 1 ]; } );
}

async function runConsent( s ) {
	document.cookie = 'wp_consent_marketing=;path=/;max-age=0';
	if ( s.cookie ) { document.cookie = 'wp_consent_marketing=' + s.cookie + ';path=/'; }
	// The legacy adapter reads a real cookie; clear it so one scenario cannot
	// decide the next one.
	document.cookie = 'cookielawinfo-checkbox-non-necessary=;path=/;max-age=0';

	var frame = await loadFrame( s.url );
	var win = frame.contentWindow;
	var doc = frame.contentDocument;
	var read = function () { return consentCalls( win ); };

	if ( s.click ) {
		// The banner markup is in the initial HTML, so its presence proves
		// nothing. What arrives late is the consent manager, and the click has
		// to come after the plugin has *found* it: a click the plugin has not
		// seen a manager for is indistinguishable from an earlier visit's
		// consent, and the plugin is right to stay silent. Its debug line is
		// the only signal for that, so the ceiling below is the real fallback
		// if debug mode is ever off.
		await until( function () {
			if ( ! doc.getElementById( 'stub-accept' ) ) { return false; }
			return ! s.late || ( win.__bpLog || [] ).some( function ( l ) {
				return l.indexOf( 'Consent manager detected' ) > -1;
			} );
		}, s.late ? 2500 : 1000 );

		var press = function ( id ) { var b = doc.getElementById( id ); if ( b ) { b.click(); } };
		if ( 'accept' === s.click ) { press( 'stub-accept' ); }
		if ( 'decline' === s.click ) { press( 'stub-decline' ); }
		if ( 'accept2' === s.click ) { press( 'stub-accept' ); await wait( 250 ); press( 'stub-accept' ); }
		if ( 'both' === s.click ) { press( 'stub-accept' ); await wait( 250 ); press( 'stub-decline' ); }

		await settle( s.expect.length, read, 2000 );
	} else {
		await wait( s.late ? LATE_CMP_MS : SETTLE_MS );
	}

	var got = read();
	var pass = JSON.stringify( got ) === JSON.stringify( s.expect );
	frame.remove();
	return {
		name: s.name,
		got: got,
		pass: pass,
		why: pass ? '' : 'expected ' + JSON.stringify( s.expect ) + ', got ' + JSON.stringify( got ),
		log: pixelLog( win )
	};
}

// --- e-commerce events --------------------------------------------------

function missingKeys( data, required ) {
	return ( required || [] ).filter( function ( k ) {
		return ! data || typeof data[ k ] === 'undefined';
	} );
}

function presentKeys( data, forbidden ) {
	return ( forbidden || [] ).filter( function ( k ) {
		return data && typeof data[ k ] !== 'undefined';
	} );
}

// A price of 0 satisfies every key check and is still wrong. Barion reads these
// as money, so a zero is a silent misreport rather than a rejected payload.
function nonPositive( data, keys ) {
	return ( keys || [] ).filter( function ( k ) {
		return ! ( Number( data && data[ k ] ) > 0 );
	} );
}

function checkSpec( data, spec, label ) {
	var problems = [];
	var missing = missingKeys( data, spec.require );
	var forbidden = presentKeys( data, spec.forbid );
	var zero = nonPositive( data, spec.positive );
	if ( missing.length ) { problems.push( label + ' is missing ' + missing.join( ', ' ) ); }
	if ( forbidden.length ) { problems.push( label + ' must not carry ' + forbidden.join( ', ' ) ); }
	if ( zero.length ) { problems.push( label + ' has a zero or missing ' + zero.join( ', ' ) ); }
	return problems;
}

function checkPayloads( tracked, s ) {
	var problems = [];
	( s.expect || [] ).forEach( function ( name ) {
		var hit = tracked.filter( function ( t ) { return t.name === name; } )[ 0 ];
		if ( ! hit ) { return; }

		if ( ( s.keys || {} )[ name ] ) {
			problems = problems.concat( checkSpec( hit.data, s.keys[ name ], name ) );
		}

		var itemSpec = ( s.contents || {} )[ name ];
		if ( itemSpec ) {
			var items = ( hit.data && hit.data.contents ) || [];
			if ( ! items.length ) {
				problems.push( name + ' has no contents items' );
			}
			items.forEach( function ( item, i ) {
				problems = problems.concat( checkSpec( item, itemSpec, name + ' contents[' + i + ']' ) );
			} );
		}
	} );
	return problems;
}

function tracked( win ) {
	return calls( win )
		.filter( function ( c ) { return 'track' === c[ 0 ]; } )
		.map( function ( c ) { return { name: c[ 1 ], data: c[ 2 ] }; } );
}

async function runEvent( s ) {
	// The cart is a server-side session, so it has to be cleared server-side.
	await fetch( CART_RESET, { credentials: 'same-origin' } );

	for ( var p = 0; p < ( s.prime || [] ).length; p++ ) {
		var primed = await loadFrame( s.prime[ p ] );
		primed.remove();
	}

	var frame = await loadFrame( s.url );
	var win = frame.contentWindow;
	var doc = frame.contentDocument;
	var read = function () { return tracked( win ); };

	if ( 'submit-form' === s.act ) {
		await until( function () { return doc.querySelector( 'form.cart' ); }, 2000 );
		var form = doc.querySelector( 'form.cart' );
		if ( form ) {
			// Runs the plugin's listener without navigating away, which would
			// take window.__bpCalls with it.
			form.dispatchEvent( new Event( 'submit', { bubbles: true, cancelable: true } ) );
		}
	}
	if ( 'ajax-add' === s.act ) {
		await until( function () { return doc.querySelector( 'a.ajax_add_to_cart' ); }, 2000 );
		var button = doc.querySelector( 'a.ajax_add_to_cart' );
		if ( button ) { button.click(); }
	}

	// The add-to-cart paths wait on WooCommerce's own AJAX and then a Store API
	// read, so they need a much higher ceiling than a queued page-load event.
	await settle( s.expect.length, read, s.act ? 8000 : 3000 );

	var names = read().map( function ( t ) { return t.name; } );
	var problems = [];
	if ( JSON.stringify( names ) !== JSON.stringify( s.expect ) ) {
		problems.push( 'expected ' + JSON.stringify( s.expect ) + ', got ' + JSON.stringify( names ) );
	}
	problems = problems.concat( checkPayloads( read(), s ) );

	if ( s.identity ) {
		// Hashed with Web Crypto, so it resolves a tick after the tracked events.
		var sent = function () {
			return calls( win ).some( function ( c ) {
				return 'identity' === c[ 0 ] && 'setEncryptedEmail' === c[ 1 ] && c[ 2 ];
			} );
		};
		await until( sent, 2000 );
		if ( ! sent() ) { problems.push( 'setEncryptedEmail was not sent' ); }
	}

	frame.remove();
	return {
		name: s.name,
		got: names,
		pass: 0 === problems.length,
		why: problems.join( '; ' ),
		log: pixelLog( win )
	};
}

// --- driver -------------------------------------------------------------

async function run() {
	var results = [];
	var rows = document.getElementById( 'rows' );

	for ( var i = 0; i < SCENARIOS.length; i++ ) {
		var s = SCENARIOS[ i ];
		var r = 'event' === s.kind ? await runEvent( s ) : await runConsent( s );
		results.push( r );

		var row = document.createElement( 'div' );
		row.className = 'row ' + ( r.pass ? 'ok' : 'bad' );
		row.textContent = ( r.pass ? '✓ ' : '✗ ' ) + r.name + ' → ' + JSON.stringify( r.got );
		if ( ! r.pass ) {
			var why = document.createElement( 'div' );
			why.className = 'why';
			why.textContent = r.why;
			row.appendChild( why );
		}
		rows.appendChild( row );
	}

	window.__results = results;
	var passed = results.filter( function ( r ) { return r.pass; } ).length;
	var sum = document.getElementById( 'sum' );
	sum.className = 'sum ' + ( passed === results.length ? 'ok' : 'bad' );
	sum.textContent = passed + '/' + results.length + ' passed';
	document.getElementById( 'raw' ).textContent = JSON.stringify( results, null, 2 );
	window.__done = true;
}

if ( ! SCENARIOS.length ) {
	window.__results = [];
	window.__done = true;
	document.getElementById( 'sum' ).textContent = 'no scenarios — is WooCommerce active?';
} else {
	run();
}
</script>
</body></html>
	<?php
}
