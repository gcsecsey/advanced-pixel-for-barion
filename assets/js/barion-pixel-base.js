/**
 * Barion Pixel Base - Pixel loader and consent integration.
 *
 * Expects wcBarionPixelBase to be set via wp_localize_script with:
 *   - pixelId (string)
 *   - debug   (boolean)
 */
(function () {
	var config = window.wcBarionPixelBase || {};
	var debug = !!config.debug;

	// Load bp.js if not already loaded by another plugin. window.bp on its own
	// settles that: the snippet Barion documents defines bp() but never sets
	// window.BarionAnalyticsObject, so asking for both loaded a second copy on
	// top of every plain snippet — a Google Tag Manager tag, a snippet in the
	// theme header, a payment gateway.
	if (typeof window.bp === 'undefined') {
		(function (b, a, r, i, o, n, p) {
			b['BarionAnalyticsObject'] = o;
			b[o] =
				b[o] ||
				function () {
					(b[o].q = b[o].q || []).push(arguments);
				};
			n = a.createElement(r);
			p = a.getElementsByTagName(r)[0];
			n.async = 1;
			n.src = i;
			p.parentNode.insertBefore(n, p);
		})(window, document, 'script', 'https://pixel.barion.com/bp.js', 'bp');
		if (debug) {
			console.log('[Barion Pixel] bp.js loaded by Advanced Pixel for Barion');
		}
	} else if (debug) {
		console.log('[Barion Pixel] bp.js already loaded by another plugin, skipping script load');
	}

	bp('init', 'addBarionPixelId', config.pixelId);
	if (debug) {
		console.log('[Barion Pixel] Base pixel initialized with ID: ' + config.pixelId);
	}

	// --- Public consent functions ---
	window.wcBarionGrantConsent = function () {
		if (typeof bp !== 'undefined') {
			bp('consent', 'grantConsent');
			if (debug) {
				console.log('[Barion Pixel] Consent granted (grantConsent)');
			}
		}
	};
	window.wcBarionRejectConsent = function () {
		if (typeof bp !== 'undefined') {
			bp('consent', 'rejectConsent');
			if (debug) {
				console.log('[Barion Pixel] Consent rejected (rejectConsent)');
			}
		}
	};

	// Custom DOM event support
	document.addEventListener('wcBarionGrantConsent', function () {
		window.wcBarionGrantConsent();
	});
	document.addEventListener('wcBarionRejectConsent', function () {
		window.wcBarionRejectConsent();
	});

	// Consent manager integration, in barion-consent.js. Started at
	// DOMContentLoaded because this script runs in <head>, before consent
	// plugins define their globals. The adapters make no further assumption
	// about load order: they listen unconditionally and keep probing for a
	// consent manager that arrives later.
	function wcBarionStartConsent() {
		if (typeof wcBarionWireConsent !== 'function') {
			return;
		}

		wcBarionWireConsent(
			window,
			document,
			function (granted) {
				if (granted) {
					window.wcBarionGrantConsent();
				} else {
					window.wcBarionRejectConsent();
				}
			},
			function (found, alreadyGranted) {
				if (!debug) {
					return;
				}
				if (!found.length) {
					console.warn(
						'[Barion Pixel] No consent manager detected. grantConsent is only sent if your banner calls window.wcBarionGrantConsent(). The WP Consent API plugin wires this up, but only for a cookie banner that registers with it.'
					);
					return;
				}

				console.log('[Barion Pixel] Consent manager detected: ' + found.join(', '));
				if (alreadyGranted) {
					console.log(
						'[Barion Pixel] Marketing consent already stood when this page loaded, so nothing was sent. Barion asks for grantConsent at the moment the visitor accepts the banner, and bp.js remembers the consent from then on. Clear your cookies to see it fire.'
					);
				}
			}
		);
	}

	// A second base pixel is not survivable. Two copies of bp.js leave the page
	// with two iframes under one id, and the copy that loses that race posts
	// its events into the other iframe before it has read the visitor's consent
	// status. bp.js throws there, so the shop reports nothing at all. A snippet
	// printed after this script is out of reach from here, so name it instead.
	function wcBarionCheckForSecondPixel() {
		var scripts;
		var loaders = 0;
		var i;

		if (!debug) {
			return;
		}

		scripts = document.getElementsByTagName('script');
		for (i = 0; i < scripts.length; i++) {
			if (scripts[i].src && scripts[i].src.indexOf('pixel.barion.com/bp.js') > -1) {
				loaders++;
			}
		}

		if (loaders > 1) {
			console.warn(
				'[Barion Pixel] Something else on this page loads a second copy of bp.js. Two copies break tracking: the events stop reaching Barion. Keep the Pixel ID here only, and clear it from your payment gateway, your Google Tag Manager tag or your theme header.'
			);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', wcBarionStartConsent);
		document.addEventListener('DOMContentLoaded', wcBarionCheckForSecondPixel);
	} else {
		wcBarionStartConsent();
		wcBarionCheckForSecondPixel();
	}
})();
