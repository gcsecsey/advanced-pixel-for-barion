<?php
/**
 * Plugin Name: Barion test recorder
 *
 * Stands in for bp.js so the Playground run never fetches pixel.barion.com and
 * every bp() call is observable. Runs at wp_head priority 1, ahead of the
 * enqueued head scripts, so it is in place before the base script calls bp().
 */
// A PHP notice or fatal must be visible in the page, not swallowed into a log
// nobody reads. This is a throwaway Playground site, never a real one.
@ini_set('display_errors', '1');
error_reporting(E_ALL);

add_action('wp_head', function () {
    ?>
    <script>
    window.__bpCalls = [];
    window.__bpLog = [];
    window.BarionAnalyticsObject = 'bp';
    window.bp = function () {
        window.__bpCalls.push( Array.prototype.slice.call( arguments ) );
    };
    ( function () {
        [ 'log', 'warn', 'error' ].forEach( function ( level ) {
            var original = console[ level ];
            console[ level ] = function () {
                window.__bpLog.push( level + ': ' + Array.prototype.join.call( arguments, ' ' ) );
                original.apply( console, arguments );
            };
        } );
    } )();
    </script>
    <?php
}, 1);
