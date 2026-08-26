/**
 * Barion Pixel consent adapters.
 *
 * Subscribes to every consent signal on the page instead of picking the first
 * one that answers. Load order is not knowable from here — a consent manager
 * that finishes loading after this script ran used to leave nothing listening,
 * so grantConsent never fired at all, on any page load.
 *
 * Every listener is therefore registered unconditionally and reads its global
 * at event time. Barion's own consent calls are idempotent, but one click can
 * arrive through two adapters at once (CookieYes also bridges into the WP
 * Consent API), so only transitions are reported — and only those the visitor
 * makes on this page load, never a state the banner replays at load.
 *
 * Loaded as a plain script in the browser (declares one global) and required
 * directly by tests/consent.test.js in Node.
 *
 * @param {object}   scope     Window-like object holding the consent globals.
 * @param {object}   doc       Document-like object, for document-level events.
 * @param {function} onConsent Receives true to grant, false to reject.
 * @param {function} onDetect  Receives the names of the consent managers found
 *                             and whether marketing consent already stood when
 *                             the page loaded, once one of them answers or the
 *                             probe gives up. The list is empty when none ever
 *                             appeared.
 */
function wcBarionWireConsent( scope, doc, onConsent, onDetect ) {
	// read() returns null when its consent manager is not on the page.
	var adapters = [
		{
			name: 'WP Consent API',
			target: doc,
			events: [ 'wp_listen_for_consent_change' ],
			read: function () {
				if ( typeof scope.wp_has_consent !== 'function' ) {
					return null;
				}
				// With no consent type registered, wp_has_consent() answers
				// "granted" for every visitor — its own way of saying that no
				// cookie banner drives it. Reading that as a real answer
				// reports consent nobody gave, so the API counts as absent.
				var consentType = typeof scope.wp_consent_type !== 'undefined'
					? scope.wp_consent_type
					: scope.wp_fallback_consent_type;
				if ( ! consentType ) {
					return null;
				}
				return Boolean( scope.wp_has_consent( 'marketing' ) );
			}
		},
		{
			name: 'CookieYes',
			target: doc,
			events: [ 'cookieyes_consent_update' ],
			read: function () {
				if ( typeof scope.getCkyConsent !== 'function' ) {
					return null;
				}
				var consent = scope.getCkyConsent();
				return Boolean( consent && consent.categories && consent.categories.advertisement );
			}
		},
		{
			name: 'Complianz',
			target: doc,
			events: [ 'cmplz_status_change' ],
			read: function () {
				if ( typeof scope.cmplz_has_consent !== 'function' ) {
					return null;
				}
				return Boolean( scope.cmplz_has_consent( 'marketing' ) );
			}
		},
		{
			// CookiebotOnConsentReady covers a returning visitor, whose banner
			// never raises accept or decline.
			name: 'Cookiebot',
			target: scope,
			events: [ 'CookiebotOnAccept', 'CookiebotOnDecline', 'CookiebotOnConsentReady' ],
			read: function () {
				if ( ! scope.Cookiebot || ! scope.Cookiebot.consent ) {
					return null;
				}
				return Boolean( scope.Cookiebot.consent.marketing );
			}
		},
		{
			// Sites that upgraded from Cookie Law Info 2.x and kept the old
			// banner. It raises no consent event, so clicks are delegated on
			// document and the cookie is re-read once the banner has written it.
			name: 'Cookie Law Info (legacy)',
			target: doc,
			events: [ 'click' ],
			delay: 100,
			read: function () {
				if ( ! scope.CLI || ! scope.CLI.allowedCategories ) {
					return null;
				}
				return /(?:^|;\s*)cookielawinfo-checkbox-non-necessary=yes/.test( doc.cookie || '' );
			}
		}
	];

	var found = [];
	var reported = null;
	var acted = false;

	// Barion wants grantConsent at the moment the visitor clicks accept, and
	// rejects an integration that sends it at page load. Consent that already
	// stands on load is an earlier visit replayed — Cookiebot raises
	// CookiebotOnConsentReady every time — and bp.js keeps that answer in its
	// own cookie anyway, so the state is recorded but nothing is sent until the
	// visitor touches the page. Capture phase, and before the adapters below,
	// so the gesture is on record by the time the banner's handler answers.
	// click is listed because a banner using element.click() raises no pointer
	// event at all.
	[ 'pointerdown', 'keydown', 'touchstart', 'click' ].forEach( function ( eventName ) {
		doc.addEventListener( eventName, function () {
			acted = true;
		}, true );
	} );

	function report( granted ) {
		if ( granted === reported ) {
			return;
		}
		reported = granted;

		if ( ! acted ) {
			return;
		}

		onConsent( granted );
	}

	adapters.forEach( function ( adapter ) {
		adapter.events.forEach( function ( eventName ) {
			adapter.target.addEventListener( eventName, function () {
				if ( null === adapter.read() ) {
					return;
				}

				if ( ! adapter.delay ) {
					report( adapter.read() );
					return;
				}

				scope.setTimeout( function () {
					var settled = adapter.read();
					if ( null !== settled ) {
						report( settled );
					}
				}, adapter.delay );
			} );
		} );
	} );

	// Names every consent manager that is on the page now and reports the ones
	// that hold consent. Returns false while none of them has answered.
	function probe() {
		adapters.forEach( function ( adapter ) {
			var granted = adapter.read();
			if ( null === granted ) {
				return;
			}

			if ( -1 === found.indexOf( adapter.name ) ) {
				found.push( adapter.name );
			}

			// Before the visitor answers the banner there is nothing to report,
			// so a missing consent stays silent rather than sending
			// rejectConsent.
			if ( granted ) {
				report( true );
			}
		} );

		return found.length > 0;
	}

	function detected() {
		if ( onDetect ) {
			onDetect( found, true === reported );
		}
	}

	if ( probe() ) {
		detected();
		return;
	}

	// A returning visitor raises no consent event, and a manager served from a
	// CDN can define its globals long after this script ran, so a single probe
	// reads nothing and the page sends no consent at all. Keep looking instead.
	// Ceiling: 10 seconds. A manager slower than that needs a manual
	// wcBarionGrantConsent() call from its own callback.
	var attemptsLeft = 20;
	var timer = scope.setInterval( function () {
		if ( probe() || --attemptsLeft <= 0 ) {
			scope.clearInterval( timer );
			detected();
		}
	}, 500 );
}

if ( typeof module !== 'undefined' && module.exports ) {
	module.exports = wcBarionWireConsent;
}
