/**
 * Barion consent recorder — observes a cookie banner and reports what it changes.
 *
 * Runs only for a logged-in administrator who opened the page from the setup
 * wizard, never for a visitor. It reads cookies and event names; it never
 * captures page content, form input or personal data.
 */
(function () {
	var opener = window.opener;
	if (!opener) {
		return;
	}

	var origin = window.location.origin;
	var seenEvents = [];
	var baseline = {};

	function parseCookies() {
		var out = {};
		var parts = document.cookie ? document.cookie.split(';') : [];
		for (var i = 0; i < parts.length; i++) {
			var pair = parts[i].split('=');
			var name = pair.shift().trim();
			if (name) {
				out[name] = pair.join('=');
			}
		}
		return out;
	}

	baseline = parseCookies();

	// Wrap dispatchEvent before the banner loads, so its custom events are seen.
	function wrap(target) {
		var original = target.dispatchEvent;
		target.dispatchEvent = function (event) {
			if (event && event.type && seenEvents.indexOf(event.type) === -1) {
				seenEvents.push(event.type);
			}
			return original.apply(this, arguments);
		};
	}
	wrap(document);
	wrap(window);

	function report() {
		var now = parseCookies();
		var changed = [];
		for (var name in now) {
			if (!Object.prototype.hasOwnProperty.call(now, name)) {
				continue;
			}
			if (baseline[name] !== now[name]) {
				changed.push({ name: name, value: now[name] });
			}
		}

		opener.postMessage(
			{
				source: 'apb-recorder',
				cookies: changed,
				events: seenEvents.slice(0, 40),
			},
			origin
		);
	}

	var polls = 0;
	var timer = setInterval(function () {
		polls++;
		report();
		if (polls >= 480) {
			clearInterval(timer);
		}
	}, 250);

	report();

	var banner = document.createElement('div');
	banner.setAttribute('style',
		'position:fixed;z-index:2147483647;left:0;right:0;top:0;padding:10px 14px;' +
		'background:#2271b1;color:#fff;font:14px -apple-system,system-ui,sans-serif;text-align:center');
	banner.textContent = 'Barion Pixel is recording your cookie banner. Make your choice in the banner, then return to the settings tab.';
	document.addEventListener('DOMContentLoaded', function () {
		document.body.appendChild(banner);
	});
})();
