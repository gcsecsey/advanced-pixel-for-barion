/**
 * Barion consent trigger — pure matching logic for a recorded cookie banner signal.
 *
 * Loaded in the browser as window.wcBarionConsentTrigger, and required by
 * tests/trigger-test.mjs under Node. It must stay free of DOM access so both work.
 */
(function (root, factory) {
	var api = factory();
	root.wcBarionConsentTrigger = api;
	if (typeof module === 'object' && module.exports) {
		module.exports = api;
	}
})(typeof self !== 'undefined' ? self : this, function () {
	var COOKIE_NAME = /^[A-Za-z0-9_\-.]{1,128}$/;
	var EVENT_NAME = /^[A-Za-z0-9_\-:.]{1,128}$/;
	var MAX_EVENTS = 5;
	var MAX_CONTAINS = 256;

	function escapeRegExp(text) {
		return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	}

	/**
	 * Clean a trigger, or return null when it breaks a rule.
	 * A trigger is dropped whole rather than partly repaired, except for event
	 * names, which are filtered — a bad event name must not discard a good cookie rule.
	 */
	function sanitize(trigger) {
		if (!trigger || typeof trigger !== 'object') {
			return null;
		}
		var cookie = typeof trigger.cookie === 'string' ? trigger.cookie : '';
		if (!COOKIE_NAME.test(cookie)) {
			return null;
		}

		var contains = typeof trigger.contains === 'string' ? trigger.contains : '';
		if (contains.length > MAX_CONTAINS) {
			contains = contains.slice(0, MAX_CONTAINS);
		}

		var events = [];
		if (Object.prototype.toString.call(trigger.events) === '[object Array]') {
			for (var i = 0; i < trigger.events.length && events.length < MAX_EVENTS; i++) {
				if (typeof trigger.events[i] === 'string' && EVENT_NAME.test(trigger.events[i])) {
					events.push(trigger.events[i]);
				}
			}
		}

		return { cookie: cookie, contains: contains, events: events };
	}

	function readCookie(cookieString, name) {
		var match = String(cookieString).match(
			new RegExp('(?:^|;\\s*)' + escapeRegExp(name) + '=([^;]*)')
		);
		if (!match) {
			return null;
		}
		try {
			return decodeURIComponent(match[1]);
		} catch (e) {
			return match[1];
		}
	}

	function matches(cookieString, trigger) {
		var clean = sanitize(trigger);
		if (!clean) {
			return false;
		}
		var value = readCookie(cookieString, clean.cookie);
		if (null === value) {
			return false;
		}
		if ('' === clean.contains) {
			return true;
		}
		return value.indexOf(clean.contains) !== -1;
	}

	/**
	 * Decide the consent state. Both matching is ambiguous, so it counts as none.
	 */
	function evaluate(cookieString, config) {
		if (!config) {
			return 'none';
		}
		var granted = matches(cookieString, config.grant);
		var rejected = matches(cookieString, config.reject);
		if (granted && !rejected) {
			return 'grant';
		}
		if (rejected && !granted) {
			return 'reject';
		}
		return 'none';
	}

	/**
	 * Every event name across both triggers, deduplicated.
	 */
	function eventNames(config) {
		var names = [];
		var sides = ['grant', 'reject'];
		for (var s = 0; s < sides.length; s++) {
			var clean = config ? sanitize(config[sides[s]]) : null;
			if (!clean) {
				continue;
			}
			for (var i = 0; i < clean.events.length; i++) {
				if (names.indexOf(clean.events[i]) === -1) {
					names.push(clean.events[i]);
				}
			}
		}
		return names;
	}

	return {
		sanitize: sanitize,
		matches: matches,
		evaluate: evaluate,
		eventNames: eventNames,
	};
});
