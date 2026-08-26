<?php
/**
 * Plugin Name: Barion consent test harness
 *
 * Renders at /?barion-harness=1. Loads each scenario in a same-origin iframe,
 * clicks the stub banner, and collects the bp() calls the page made.
 */
add_action('template_redirect', function () {
    if (empty($_GET['barion-harness'])) {
        return;
    }
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Barion consent harness</title></head>
<body>
<h1>Barion consent harness</h1>
<pre id="out">running...</pre>
<script>
var SCENARIOS = [
    { name: 'CookieYes - accept',                 query: 'cmp=cookieyes',            click: 'accept', expect: [ 'grantConsent' ] },
    { name: 'CookieYes - decline',                query: 'cmp=cookieyes',            click: 'decline', expect: [ 'rejectConsent' ] },
    { name: 'CookieYes - no answer yet',          query: 'cmp=cookieyes',            click: null,      expect: [] },
    { name: 'CookieYes - returning, accepted',    query: 'cmp=cookieyes&prior=1',    click: null,      expect: [ 'grantConsent' ] },
    { name: 'CookieYes - loads late, then accept', query: 'cmp=cookieyes&late=1',    click: 'accept',  expect: [ 'grantConsent' ] },
    { name: 'CookieYes - accept twice',           query: 'cmp=cookieyes',            click: 'accept2', expect: [ 'grantConsent' ] },
    { name: 'CookieYes - accept then decline',    query: 'cmp=cookieyes',            click: 'both',    expect: [ 'grantConsent', 'rejectConsent' ] },
    { name: 'Cookiebot - accept',                 query: 'cmp=cookiebot',            click: 'accept',  expect: [ 'grantConsent' ] },
    { name: 'Cookiebot - decline',                query: 'cmp=cookiebot',            click: 'decline', expect: [ 'rejectConsent' ] },
    { name: 'Complianz - accept',                 query: 'cmp=complianz',            click: 'accept',  expect: [ 'grantConsent' ] },
    { name: 'Complianz - decline',                query: 'cmp=complianz',            click: 'decline', expect: [ 'rejectConsent' ] },
    { name: 'WP Consent API - accept',            query: 'cmp=wpca',                 click: 'accept',  expect: [ 'grantConsent' ] },
    { name: 'WP Consent API - loads late, accept', query: 'cmp=wpca&late=1',         click: 'accept',  expect: [ 'grantConsent' ] },
    { name: 'Cookie Law Info legacy - accept',    query: 'cmp=cli',                  click: 'accept',  expect: [ 'grantConsent' ] },
    { name: 'Cookie Law Info legacy - decline',   query: 'cmp=cli',                  click: 'decline', expect: [ 'rejectConsent' ] },
    { name: 'No consent manager',                 query: 'cmp=none',                 click: null,      expect: [] },
    { name: 'Late CMP, returning visitor, no click', query: 'cmp=cookieyes&late=1&prior=1', click: null, expect: [ 'grantConsent' ] }
];


var REAL = [
    { name: 'Real WPCA optin - accept',            query: 'real=1&ctype=optin',  cookie: null,    click: 'accept',  expect: [ 'grantConsent' ] },
    { name: 'Real WPCA optin - decline',           query: 'real=1&ctype=optin',  cookie: null,    click: 'decline', expect: [ 'rejectConsent' ] },
    { name: 'Real WPCA optin - no answer yet',     query: 'real=1&ctype=optin',  cookie: null,    click: null,      expect: [] },
    { name: 'Real WPCA optin - returning, allowed', query: 'real=1&ctype=optin', cookie: 'allow', click: null,      expect: [ 'grantConsent' ] },
    { name: 'Real WPCA optin - returning, denied',  query: 'real=1&ctype=optin', cookie: 'deny',  click: null,      expect: [] },
    { name: 'Real WPCA optin - deny then allow',   query: 'real=1&ctype=optin',  cookie: 'deny',  click: 'accept',  expect: [ 'grantConsent' ] },
    { name: 'Real WPCA, no consent type set',      query: 'real=1&ctype=none',   cookie: null,    click: null,      expect: [ 'grantConsent' ] }
];

function wait( ms ) {
    return new Promise( function ( r ) { window.setTimeout( r, ms ); } );
}

function loadFrame( url ) {
    return new Promise( function ( resolve ) {
        var frame = document.createElement( 'iframe' );
        frame.style.width = '900px';
        frame.style.height = '200px';
        frame.src = url;
        frame.addEventListener( 'load', function () { resolve( frame ); } );
        document.body.appendChild( frame );
    } );
}

function consentCalls( win ) {
    return ( win.__bpCalls || [] )
        .filter( function ( call ) { return 'consent' === call[ 0 ]; } )
        .map( function ( call ) { return call[ 1 ]; } );
}

async function run() {
    var results = [];
    var list = 'real' === new URLSearchParams( location.search ).get( 'barion-harness' ) ? REAL : SCENARIOS;
    for ( var i = 0; i < list.length; i++ ) {
        var s = list[ i ];
        document.cookie = 'wp_consent_marketing=;path=/;max-age=0';
        if ( s.cookie ) { document.cookie = 'wp_consent_marketing=' + s.cookie + ';path=/'; }
        // The legacy adapter reads a real cookie; clear it so one scenario
        // cannot decide the next one.
        document.cookie = 'cookielawinfo-checkbox-non-necessary=;path=/;max-age=0';

        var frame = await loadFrame( '/?' + s.query + '&harness_run=' + i );
        var win = frame.contentWindow;
        var doc = frame.contentDocument;
        await wait( s.query.indexOf( 'late=1' ) > -1 ? 1400 : 400 );

        function press( id ) { var b = doc.getElementById( id ); if ( b ) { b.click(); } }
        if ( 'accept' === s.click ) { press( 'stub-accept' ); }
        if ( 'decline' === s.click ) { press( 'stub-decline' ); }
        if ( 'accept2' === s.click ) { press( 'stub-accept' ); await wait( 250 ); press( 'stub-accept' ); }
        if ( 'both' === s.click ) { press( 'stub-accept' ); await wait( 250 ); press( 'stub-decline' ); }
        await wait( 400 );

        var got = consentCalls( win );
        var init = ( win.__bpCalls || [] ).some( function ( c ) { return 'init' === c[ 0 ]; } );
        results.push( {
            name: s.name,
            expected: s.expect,
            got: got,
            pass: JSON.stringify( got ) === JSON.stringify( s.expect ),
            pixelInit: init,
            log: ( win.__bpLog || [] ).filter( function ( l ) { return l.indexOf( 'Barion Pixel' ) > -1; } )
        } );
        frame.remove();
    }
    window.__results = results;
    var passed = results.filter( function ( r ) { return r.pass; } ).length;
    document.getElementById( 'out' ).textContent =
        passed + '/' + results.length + ' passed\n\n' + JSON.stringify( results, null, 2 );
    window.__done = true;
}
run();
</script>
</body></html>
    <?php
    exit;
});
