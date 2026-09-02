<?php
/**
 * Live event timeline.
 *
 * ?barion-panel=1 draws a panel on any shop page listing each bp() call with
 * its offset from page load, and marks any consent call that arrived before the
 * visitor touched the page. That is the rule Barion rejected the integration
 * over, and this makes it legible without a console — which a Playground
 * preview cannot open, because the site runs in an iframe on another origin.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_footer',
	function () {
		if ( empty( $_GET['barion-panel'] ) ) {
			return;
		}
		?>
	<style>
	#barion-panel { position: fixed; top: 8px; right: 8px; width: 380px; max-height: 70vh; overflow: auto;
		background: #11151c; color: #dfe4ec; font: 12px/1.45 ui-monospace, Menlo, monospace;
		border-radius: 6px; padding: 10px 12px; z-index: 2147483647; box-shadow: 0 4px 18px rgba(0,0,0,.35); }
	#barion-panel h4 { margin: 0 0 6px; font-size: 12px; color: #8fb8ff; text-transform: uppercase; letter-spacing: .06em; }
	#barion-panel .meta { color: #9aa4b2; margin-bottom: 8px; }
	#barion-panel .call { padding: 3px 0; border-top: 1px solid #232a35; word-break: break-word; }
	#barion-panel .at { color: #6ee7a8; }
	#barion-panel .early { color: #ff8f8f; }
	#barion-panel .none { color: #9aa4b2; }
	</style>
	<div id="barion-panel"><h4>Barion pixel — live</h4><div class="meta" id="barion-panel-meta">waiting…</div><div id="barion-panel-calls"></div></div>
	<script>
	( function () {
		var panel = document.getElementById( 'barion-panel-calls' );
		var meta = document.getElementById( 'barion-panel-meta' );
		var gestureAt = null;

		// The same gesture list the plugin gates consent on. element.click()
		// raises no pointer event, so plain 'click' has to be here too.
		[ 'click', 'pointerdown', 'keydown', 'touchstart' ].forEach( function ( type ) {
			document.addEventListener( type, function () {
				if ( null === gestureAt ) {
					gestureAt = window.__bpNow();
					render();
				}
			}, true );
		} );

		function summary() {
			var log = window.__bpLog || [];
			var found = 'no consent manager detected';
			for ( var i = 0; i < log.length; i++ ) {
				var hit = log[ i ].match( /Consent manager detected: (.+)$/ );
				if ( hit ) { found = 'consent manager: ' + hit[ 1 ]; }
			}
			var gesture = null === gestureAt ? 'no gesture yet' : 'first gesture at ' + Math.round( gestureAt ) + ' ms';
			return found + ' — ' + gesture;
		}

		function describe( call ) {
			var a = call.args;
			var head = a[ 0 ] + ' ' + ( a[ 1 ] || '' );
			if ( typeof a[ 2 ] === 'undefined' ) { return head; }
			if ( a[ 2 ] && 'object' === typeof a[ 2 ] ) {
				try { return head + ' ' + JSON.stringify( a[ 2 ] ); } catch ( e ) { return head; }
			}
			return head + ' ' + a[ 2 ];
		}

		function render() {
			meta.textContent = summary();
			var list = window.__bpCalls || [];
			panel.innerHTML = '';
			if ( ! list.length ) {
				var empty = document.createElement( 'div' );
				empty.className = 'none';
				empty.textContent = 'nothing sent yet';
				panel.appendChild( empty );
				return;
			}
			list.forEach( function ( call ) {
				var row = document.createElement( 'div' );
				row.className = 'call';
				// A consent call before the first gesture is the exact failure
				// this harness exists to catch, so it is called out in red.
				var early = 'consent' === call.args[ 0 ] && ( null === gestureAt || call.at < gestureAt );
				row.innerHTML = '<span class="' + ( early ? 'early' : 'at' ) + '">' +
					Math.round( call.at ) + ' ms</span> ';
				row.appendChild( document.createTextNode( describe( call ) ) );
				if ( early ) {
					row.appendChild( document.createTextNode( '  ← before any click' ) );
				}
				panel.appendChild( row );
			} );
		}

		render();
		document.addEventListener( 'barion-bp-call', render );
	} )();
	</script>
		<?php
	},
	200
);
