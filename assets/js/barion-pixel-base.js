/**
 * Barion Pixel Base - Pixel loader and consent integration.
 *
 * Expects abpwBase to be set via wp_localize_script with:
 *   - pixelId (string)
 *   - debug   (boolean)
 */
(function () {
	var config = window.abpwBase || {};
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
	window.abpwGrantConsent = function () {
		if (typeof bp !== 'undefined') {
			bp('consent', 'grantConsent');
			if (debug) {
				console.log('[Barion Pixel] Consent granted (grantConsent)');
			}
		}
	};
	window.abpwRejectConsent = function () {
		if (typeof bp !== 'undefined') {
			bp('consent', 'rejectConsent');
			if (debug) {
				console.log('[Barion Pixel] Consent rejected (rejectConsent)');
			}
		}
	};

	// Custom DOM event support
	document.addEventListener('abpwGrantConsent', function () {
		window.abpwGrantConsent();
	});
	document.addEventListener('abpwRejectConsent', function () {
		window.abpwRejectConsent();
	});

	// --- Tier 1: WP Consent API integration ---
	if (typeof wp_has_consent === 'function') {
		if (wp_has_consent('marketing')) {
			window.abpwGrantConsent();
			if (debug) {
				console.log('[Barion Pixel] Consent auto-granted via WP Consent API');
			}
		}
		document.addEventListener('wp_listen_for_consent_change', function () {
			if (wp_has_consent('marketing')) {
				window.abpwGrantConsent();
				if (debug) {
					console.log('[Barion Pixel] Consent granted via WP Consent API change event');
				}
			} else {
				window.abpwRejectConsent();
				if (debug) {
					console.log('[Barion Pixel] Consent rejected via WP Consent API change event');
				}
			}
		});
	}

	// --- Tier 2: Cookie Law Info / CookieYes direct integration (fallback) ---
	else if (typeof CLI !== 'undefined' && CLI.allowedCategories) {
		function abpwGetCliCookie(name) {
			var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
			return match ? decodeURIComponent(match[1]) : '';
		}
		function abpwCheckCliConsent() {
			if (abpwGetCliCookie('cookielawinfo-checkbox-non-necessary') === 'yes') {
				window.abpwGrantConsent();
			} else {
				window.abpwRejectConsent();
			}
		}
		if (abpwGetCliCookie('cookielawinfo-checkbox-non-necessary') === 'yes') {
			window.abpwGrantConsent();
		}
		if (debug) {
			console.log(
				'[Barion Pixel] Cookie Law Info detected, initial non-necessary cookie:',
				abpwGetCliCookie('cookielawinfo-checkbox-non-necessary')
			);
		}
		document.querySelectorAll('.cli_action_button').forEach(function (btn) {
			btn.addEventListener('click', function () {
				setTimeout(function () {
					abpwCheckCliConsent();
					if (debug) {
						console.log(
							'[Barion Pixel] Cookie Law Info button clicked, non-necessary cookie:',
							abpwGetCliCookie('cookielawinfo-checkbox-non-necessary')
						);
					}
				}, 100);
			});
		});
	}

	// --- Tier 3: Manual integration ---
	else if (debug) {
		console.log(
			'[Barion Pixel] No consent manager detected. Call window.abpwGrantConsent() or window.abpwRejectConsent() manually.'
		);
	}
})();
