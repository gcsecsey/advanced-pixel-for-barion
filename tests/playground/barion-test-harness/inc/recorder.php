<?php
/**
 * Records what the plugin sends, without sending it.
 *
 * Stands in for bp() so the Playground run never fetches pixel.barion.com and
 * every call is observable in window.__bpCalls, with the offset from page load
 * the live panel needs. Runs at wp_head priority 1, ahead of the enqueued head
 * scripts, so it is in place before the base script calls bp().
 *
 * Console output is captured too: the harness pages assert against the plugin's
 * debug log, and the live panel reads the detected consent managers from it.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_head',
	function () {
		?>
	<script>
	window.__bpCalls = [];
	window.__bpLog = [];
	window.__bpStart = ( window.performance && performance.now ) ? performance.now() : Date.now();
	// Milliseconds since this page started recording. The live panel times its
	// own gesture against the same origin these calls are stamped with.
	window.__bpNow = function () {
		return ( ( window.performance && performance.now ) ? performance.now() : Date.now() ) - window.__bpStart;
	};
	( function () {
		[ 'log', 'warn', 'error' ].forEach( function ( level ) {
			var original = console[ level ];
			console[ level ] = function () {
				window.__bpLog.push( level + ': ' + Array.prototype.map.call( arguments, function ( a ) {
					if ( a && 'object' === typeof a ) {
						try { return JSON.stringify( a ); } catch ( e ) { return String( a ); }
					}
					return String( a );
				} ).join( ' ' ) );
				original.apply( console, arguments );
			};
		} );

		// Defining both is what makes the base script skip loading bp.js.
		window.BarionAnalyticsObject = 'bp';
		window.bp = function () {
			window.__bpCalls.push( {
				args: Array.prototype.slice.call( arguments ),
				at: window.__bpNow()
			} );
			document.dispatchEvent( new CustomEvent( 'barion-bp-call' ) );
		};
	} )();
	</script>
		<?php
	},
	1
);
