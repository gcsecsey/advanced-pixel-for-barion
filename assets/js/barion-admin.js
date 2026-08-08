/**
 * Admin behaviour for the Barion Pixel health panel and consent wizard.
 */
(function () {
	var cfg = window.wcBarionAdmin || {};
	var panel = document.getElementById('apb-panel');
	var dialog = document.getElementById('apb-wizard');
	var toggle = document.getElementById('apb-toggle');

	var state = { step: 1, side: 'grant', recorded: { grant: null, reject: null }, tab: null, lastPayload: null };
	var recorderTimer = null;

	if (toggle && panel) {
		toggle.addEventListener('click', function () {
			panel.classList.toggle('is-collapsed');
		});
	}

	function post(body, done) {
		var params = new URLSearchParams(body);
		params.set('nonce', cfg.nonce);
		fetch(cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: params.toString(),
		})
			.then(function (r) { return r.json(); })
			.then(done)
			.catch(function () { done({ success: false }); });
	}

	// --- Panel actions ---
	document.addEventListener('click', function (event) {
		var button = event.target.closest('[data-apb-action]');
		if (!button) {
			return;
		}
		var action = button.getAttribute('data-apb-action');
		if ('wizard' === action) {
			openWizard();
		} else if ('probe' === action) {
			runConsentProbe(button);
		} else if ('reachability' === action) {
			runReachability(button);
		}
	});

	function openWizard() {
		state.step = 1;
		state.side = 'grant';
		state.recorded = { grant: null, reject: null };
		state.lastPayload = null;
		if (recorderTimer) {
			clearTimeout(recorderTimer);
			recorderTimer = null;
		}
		showStep(1);
		dialog.showModal();
	}

	if (dialog) {
		dialog.addEventListener('click', function (event) {
			if (event.target.closest('[data-apb-close]')) {
				dialog.close();
			}
			var choice = event.target.closest('.apb-choice');
			if (choice && !choice.classList.contains('is-disabled')) {
				dialog.querySelectorAll('.apb-choice').forEach(function (el) {
					el.classList.remove('is-selected');
				});
				choice.classList.add('is-selected');
			}
		});

		dialog.querySelector('[data-apb-back]').addEventListener('click', function () {
			showStep(Math.max(1, state.step - 1));
		});

		dialog.querySelector('[data-apb-next]').addEventListener('click', onNext);
	}

	function showStep(step) {
		state.step = step;
		dialog.querySelectorAll('.apb-step').forEach(function (el) {
			el.classList.toggle('is-active', el.getAttribute('data-step') === String(step));
		});
		dialog.querySelectorAll('[data-dot]').forEach(function (el) {
			el.classList.toggle('is-on', el.getAttribute('data-dot') === String(step));
		});
		var next = dialog.querySelector('[data-apb-next]');
		if (2 === step) {
			next.textContent = cfg.strings.openShop;
			dialog.querySelector('[data-apb-record-intro]').textContent =
				'grant' === state.side ? cfg.strings.recordAccept : cfg.strings.recordReject;
			document.getElementById('apb-recorder-log').textContent = cfg.strings.waiting;
		} else if (3 === step) {
			next.textContent = cfg.strings.save;
		} else {
			next.textContent = cfg.strings.next;
		}
	}

	function armRecorderTimeout() {
		if (recorderTimer) {
			clearTimeout(recorderTimer);
		}
		recorderTimer = setTimeout(function () {
			document.getElementById('apb-recorder-log').textContent = cfg.strings.recorderSilent;
		}, 20000);
	}

	function onNext() {
		if (1 === state.step) {
			var selected = dialog.querySelector('.apb-choice.is-selected');
			var value = selected ? selected.getAttribute('data-choice') : 'learn';
			if ('learn' !== value) {
				dialog.close();
				return;
			}
			state.side = 'grant';
			showStep(2);
			return;
		}

		if (2 === state.step) {
			state.tab = window.open(cfg.recordUrl, 'apb-recorder');
			armRecorderTimeout();
			return;
		}

		saveTrigger();
	}

	// --- Recorder messages ---
	window.addEventListener('message', function (event) {
		if (event.origin !== window.location.origin) {
			return;
		}
		var data = event.data;
		if (!data || 'apb-recorder' !== data.source) {
			return;
		}

		// The recorder re-sends the same baseline diff every 250ms, so an unchanged
		// payload is a repeat, not a new signal. Without this guard the grant reading
		// is immediately stored again as the reject reading.
		var fingerprint = JSON.stringify({ cookies: data.cookies, events: data.events });
		if (fingerprint === state.lastPayload) {
			return;
		}
		state.lastPayload = fingerprint;

		if (recorderTimer) {
			clearTimeout(recorderTimer);
			recorderTimer = null;
		}

		var log = document.getElementById('apb-recorder-log');
		if (!data.cookies.length) {
			log.textContent = cfg.strings.noChange;
			return;
		}

		var lines = data.cookies.map(function (c) {
			return 'cookie ' + c.name + ' = ' + c.value;
		});
		if (data.events.length) {
			lines.push('events ' + data.events.join(', '));
		}
		log.textContent = lines.join('\n');

		// The longest changed value is the most specific candidate.
		var best = data.cookies.slice().sort(function (a, b) {
			return b.value.length - a.value.length;
		})[0];

		state.recorded[state.side] = {
			cookie: best.name,
			contains: decodeURIComponent(best.value),
			events: data.events.slice(0, 5),
		};

		if ('grant' === state.side) {
			state.side = 'reject';
			showStep(2);
		} else {
			fillStep3();
			showStep(3);
		}
	});

	function fillStep3() {
		document.getElementById('apb-grant-cookie').value = state.recorded.grant.cookie;
		document.getElementById('apb-grant-contains').value = state.recorded.grant.contains;
		document.getElementById('apb-reject-contains').value = state.recorded.reject.contains;
	}

	function saveTrigger() {
		var cookie = document.getElementById('apb-grant-cookie').value;
		var notice = document.getElementById('apb-wizard-notice');
		var payload = {
			grant: {
				cookie: cookie,
				contains: document.getElementById('apb-grant-contains').value,
				events: state.recorded.grant ? state.recorded.grant.events : [],
			},
			reject: {
				cookie: cookie,
				contains: document.getElementById('apb-reject-contains').value,
				events: state.recorded.reject ? state.recorded.reject.events : [],
			},
		};

		if (!payload.grant.contains || !payload.reject.contains) {
			notice.textContent = cfg.strings.needBoth;
			return;
		}

		post({ action: 'apb_save_trigger', trigger: JSON.stringify(payload) }, function (res) {
			if (res && res.success) {
				notice.textContent = cfg.strings.saved;
				window.location.reload();
			} else {
				notice.textContent = res && res.data ? res.data.message : cfg.strings.needBoth;
			}
		});
	}

	// --- Browser checks ---
	function runConsentProbe(button) {
		button.disabled = true;
		button.textContent = cfg.strings.testing;

		var frame = document.createElement('iframe');
		var settled = false;

		function save(type, granted) {
			if (settled) {
				return;
			}
			settled = true;
			post({
				action: 'apb_save_probe',
				kind: 'consent',
				consent_type: type,
				has_consent: granted ? 'true' : 'false',
			}, function () {
				frame.remove();
				window.location.reload();
			});
		}

		// Never save a result we did not actually measure: an unfinished check
		// would otherwise be stored as "no consent granted", which reads as healthy.
		function abandon() {
			if (settled) {
				return;
			}
			settled = true;
			frame.remove();
			button.disabled = false;
			button.textContent = cfg.strings.probeFailed;
		}

		frame.style.display = 'none';
		frame.src = cfg.recordUrl;
		frame.addEventListener('load', function () {
			var win = frame.contentWindow;
			var type = '';
			var granted = false;
			try {
				type = 'function' === typeof win.wp_get_consent_type ? String(win.wp_get_consent_type() || '') : '';
				granted = 'function' === typeof win.wp_has_consent ? !!win.wp_has_consent('marketing') : false;
			} catch (e) {
				type = '';
			}
			save(type, granted);
		});
		frame.addEventListener('error', abandon);
		setTimeout(abandon, 8000);
		document.body.appendChild(frame);
	}

	function runReachability(button) {
		button.disabled = true;
		button.textContent = cfg.strings.testing;

		var script = document.createElement('script');
		var settled = false;

		function finish(ok) {
			if (settled) {
				return;
			}
			settled = true;
			script.remove();
			post({ action: 'apb_save_probe', kind: 'reachability', ok: ok ? 'true' : 'false' }, function () {
				window.location.reload();
			});
		}

		script.src = 'https://pixel.barion.com/bp.js';
		script.addEventListener('load', function () { finish(true); });
		script.addEventListener('error', function () { finish(false); });
		setTimeout(function () { finish(false); }, 8000);
		document.body.appendChild(script);
	}
})();
