const test = require( 'node:test' );
const assert = require( 'node:assert' );
const wireConsent = require( '../assets/js/barion-consent.js' );

/**
 * Wires the consent adapters against stub globals and records what they emit.
 * Window and document share one listener map — no event name is used on both.
 */
const harness = ( globals, cookie ) => {
	const listeners = {};
	const intervals = [];
	const addEventListener = ( name, handler ) => {
		listeners[ name ] = listeners[ name ] || [];
		listeners[ name ].push( handler );
	};
	// setTimeout runs inline so the delayed cookie re-read needs no fake timers.
	// setInterval is collected instead, so a test drives the probe with tick().
	const scope = Object.assign(
		{
			addEventListener,
			setTimeout: ( fn ) => fn(),
			setInterval: ( fn ) => intervals.push( fn ) - 1,
			clearInterval: ( id ) => {
				intervals[ id ] = null;
			},
		},
		globals
	);
	const doc = { addEventListener, cookie: cookie || '' };
	const granted = [];
	let detected = null;
	let alreadyGranted = null;

	wireConsent(
		scope,
		doc,
		( value ) => granted.push( value ),
		( names, held ) => {
			detected = names;
			alreadyGranted = held;
		}
	);

	const fire = ( name ) => ( listeners[ name ] || [] ).forEach( ( handler ) => handler( {} ) );

	return {
		scope,
		doc,
		granted,
		detected: () => detected,
		alreadyGranted: () => alreadyGranted,
		probing: () => intervals.some( Boolean ),
		fire,
		// Nothing is sent before the visitor touches the page, so every test of
		// a banner click has to start with the gesture that click really makes.
		act: () => fire( 'pointerdown' ),
		listens: ( name ) => Boolean( listeners[ name ] ),
		tick: ( times = 1 ) => {
			for ( let i = 0; i < times; i++ ) {
				intervals.forEach( ( fn ) => fn && fn() );
			}
		},
	};
};

const cookieYes = ( advertisement ) => ( {
	getCkyConsent: () => ( { categories: { advertisement } } ),
} );

// The WP Consent API only carries a real answer once a cookie banner has
// registered a consent type with it.
const wpConsentApi = ( marketing ) => ( {
	wp_consent_type: 'optin',
	wp_has_consent: () => marketing,
} );

// Barion rejects an integration whose grantConsent arrives at page load instead
// of on the accept click, because that is indistinguishable from a shop that
// never asks. bp.js stores the consent itself, so there is nothing to replay.
test( 'does not send consent that already stood when the page loaded', () => {
	assert.deepStrictEqual( harness( wpConsentApi( true ) ).granted, [] );
} );

test( 'does not send consent a banner replays at page load', () => {
	const h = harness( { Cookiebot: { consent: { marketing: true } } } );
	h.fire( 'CookiebotOnConsentReady' );
	assert.deepStrictEqual( h.granted, [] );
} );

test( 'sends consent when the visitor accepts on this page load', () => {
	const h = harness( wpConsentApi( false ) );
	h.act();
	h.scope.wp_has_consent = () => true;
	h.fire( 'wp_listen_for_consent_change' );
	assert.deepStrictEqual( h.granted, [ true ] );
} );

// The reported bug: with no banner registered, wp_has_consent() answers
// "granted" for everyone, so every page load sent grantConsent.
test( 'ignores the WP Consent API when no cookie banner registered a consent type', () => {
	const h = harness( { wp_has_consent: () => true } );
	h.tick( 20 );
	assert.deepStrictEqual( h.granted, [] );
	assert.deepStrictEqual( h.detected(), [] );
} );

test( 'reads the WP Consent API once a banner registers a consent type late', () => {
	const h = harness( { wp_has_consent: () => false } );
	h.scope.wp_consent_type = 'optin';
	h.tick();
	assert.deepStrictEqual( h.detected(), [ 'WP Consent API' ] );
} );

test( 'stays silent when the visitor has not answered the banner yet', () => {
	assert.deepStrictEqual( harness( wpConsentApi( false ) ).granted, [] );
} );

test( 'rejects when the WP Consent API change event reports withdrawal', () => {
	const h = harness( wpConsentApi( true ) );
	h.act();
	h.scope.wp_has_consent = () => false;
	h.fire( 'wp_listen_for_consent_change' );
	assert.deepStrictEqual( h.granted, [ false ] );
} );

// The regression behind the reported "grantConsent never arrives": a consent
// manager that finishes loading after this script ran left nothing listening.
test( 'grants when the WP Consent API loads after the adapters are wired', () => {
	const h = harness();
	h.act();
	h.scope.wp_consent_type = 'optin';
	h.scope.wp_has_consent = () => true;
	h.fire( 'wp_listen_for_consent_change' );
	assert.deepStrictEqual( h.granted, [ true ] );
} );

test( 'listens for WP Consent API changes even with no consent manager present', () => {
	assert.ok( harness().listens( 'wp_listen_for_consent_change' ) );
} );

test( 'grants on the CookieYes consent update event', () => {
	const h = harness( cookieYes( false ) );
	h.act();
	h.scope.getCkyConsent = cookieYes( true ).getCkyConsent;
	h.fire( 'cookieyes_consent_update' );
	assert.deepStrictEqual( h.granted, [ true ] );
} );

test( 'rejects when CookieYes reports advertising cookies declined', () => {
	const h = harness( cookieYes( true ) );
	h.act();
	h.scope.getCkyConsent = cookieYes( false ).getCkyConsent;
	h.fire( 'cookieyes_consent_update' );
	assert.deepStrictEqual( h.granted, [ false ] );
} );

test( 'grants on the Complianz status change event', () => {
	const h = harness( { cmplz_has_consent: () => false } );
	h.act();
	h.scope.cmplz_has_consent = ( category ) => category === 'marketing';
	h.fire( 'cmplz_status_change' );
	assert.deepStrictEqual( h.granted, [ true ] );
} );

test( 'grants on the Cookiebot accept event', () => {
	const h = harness( { Cookiebot: { consent: { marketing: false } } } );
	h.act();
	h.scope.Cookiebot.consent.marketing = true;
	h.fire( 'CookiebotOnAccept' );
	assert.deepStrictEqual( h.granted, [ true ] );
} );

test( 'rejects on the Cookiebot decline event', () => {
	const h = harness( { Cookiebot: { consent: { marketing: true } } } );
	h.act();
	h.scope.Cookiebot.consent.marketing = false;
	h.fire( 'CookiebotOnDecline' );
	assert.deepStrictEqual( h.granted, [ false ] );
} );

// CookieYes bridges into the WP Consent API when both are installed, so one
// click can arrive twice. Barion only needs the transitions.
test( 'reports a repeated state only once', () => {
	const h = harness( { ...wpConsentApi( false ), ...cookieYes( false ) } );
	h.act();
	h.scope.wp_has_consent = () => true;
	h.scope.getCkyConsent = cookieYes( true ).getCkyConsent;
	h.fire( 'wp_listen_for_consent_change' );
	h.fire( 'cookieyes_consent_update' );
	assert.deepStrictEqual( h.granted, [ true ] );
} );

test( 'reports every real change of mind', () => {
	const h = harness( wpConsentApi( true ) );
	h.act();
	h.scope.wp_has_consent = () => false;
	h.fire( 'wp_listen_for_consent_change' );
	h.scope.wp_has_consent = () => true;
	h.fire( 'wp_listen_for_consent_change' );
	assert.deepStrictEqual( h.granted, [ false, true ] );
} );

// Sites that upgraded from Cookie Law Info 2.x and kept the old banner have no
// consent event to listen for, so the cookie is re-read after a banner click.
const legacyCli = { CLI: { allowedCategories: [] } };

test( 'grants when a legacy Cookie Law Info banner is accepted', () => {
	const h = harness( legacyCli );
	h.act();
	h.doc.cookie = 'cookielawinfo-checkbox-non-necessary=yes';
	h.fire( 'click' );
	assert.deepStrictEqual( h.granted, [ true ] );
} );

test( 'rejects when a legacy Cookie Law Info banner is declined', () => {
	const h = harness( legacyCli );
	h.act();
	h.doc.cookie = 'cookielawinfo-checkbox-non-necessary=no';
	h.fire( 'click' );
	assert.deepStrictEqual( h.granted, [ false ] );
} );

test( 'stays silent for a returning visitor who accepted legacy Cookie Law Info earlier', () => {
	const h = harness( legacyCli, 'other=1; cookielawinfo-checkbox-non-necessary=yes' );
	assert.deepStrictEqual( h.granted, [] );
} );

test( 'ignores clicks when no legacy banner is present', () => {
	const h = harness();
	h.doc.cookie = 'cookielawinfo-checkbox-non-necessary=yes';
	h.fire( 'click' );
	assert.deepStrictEqual( h.granted, [] );
} );

// A returning visitor raises no consent event, and a manager served from a CDN
// can define its globals after the page has finished loading. A single probe
// would never read it, so the manager would go unnoticed for the whole visit.
test( 'finds a consent manager that appears after the page has loaded', () => {
	const h = harness();
	Object.assign( h.scope, wpConsentApi( true ) );
	h.tick();
	assert.deepStrictEqual( h.detected(), [ 'WP Consent API' ] );
} );

test( 'does not send consent that the probe alone found', () => {
	const h = harness();
	Object.assign( h.scope, wpConsentApi( true ) );
	h.tick();
	assert.deepStrictEqual( h.granted, [] );
} );

test( 'stays silent when a late consent manager reports consent refused', () => {
	const h = harness();
	Object.assign( h.scope, wpConsentApi( false ) );
	h.tick();
	assert.deepStrictEqual( h.granted, [] );
} );

test( 'stops probing once a consent manager answers', () => {
	const h = harness();
	assert.ok( h.probing() );
	Object.assign( h.scope, wpConsentApi( false ) );
	h.tick();
	assert.ok( ! h.probing() );
} );

test( 'gives up probing when no consent manager ever appears', () => {
	const h = harness();
	h.tick( 20 );
	assert.ok( ! h.probing() );
} );

test( 'does not probe when a consent manager is present from the start', () => {
	assert.ok( ! harness( cookieYes( false ) ).probing() );
} );

test( 'names the consent managers it found', () => {
	assert.deepStrictEqual( harness( wpConsentApi( false ) ).detected(), [ 'WP Consent API' ] );
	assert.deepStrictEqual( harness( cookieYes( false ) ).detected(), [ 'CookieYes' ] );
} );

// Debug mode explains the silence with this, so a merchant testing as a
// returning visitor does not report the missing grantConsent as a bug.
test( 'reports whether consent already stood when the page loaded', () => {
	assert.strictEqual( harness( wpConsentApi( true ) ).alreadyGranted(), true );
	assert.strictEqual( harness( wpConsentApi( false ) ).alreadyGranted(), false );
} );

// Reporting "no consent manager" while one is still loading would send the
// merchant hunting for a problem that does not exist.
test( 'reports no consent manager only after the probe gives up', () => {
	const h = harness();
	assert.strictEqual( h.detected(), null );
	h.tick( 20 );
	assert.deepStrictEqual( h.detected(), [] );
} );
