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

	// Load bp.js if not already loaded by another plugin
	if (typeof window.bp === 'undefined' || !window.BarionAnalyticsObject) {
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

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', wcBarionStartConsent);
	} else {
		wcBarionStartConsent();
	}
})();
