import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const trigger = require('../assets/js/barion-consent-trigger.js');

const GRANT = { cookie: 'cky-consent', contains: 'ad:yes', events: [] };
const REJECT = { cookie: 'cky-consent', contains: 'ad:no', events: [] };

test('matches a cookie that contains the value', () => {
	assert.equal(trigger.matches('a=1; cky-consent=stats:yes|ad:yes; b=2', GRANT), true);
});

test('does not match a different value in the same cookie', () => {
	assert.equal(trigger.matches('cky-consent=stats:yes|ad:no', GRANT), false);
});

test('does not match a missing cookie', () => {
	assert.equal(trigger.matches('other=1', GRANT), false);
});

test('does not match a cookie whose name is a suffix of another', () => {
	assert.equal(trigger.matches('xcky-consent=ad:yes', GRANT), false);
});

test('matches on presence alone when contains is empty', () => {
	assert.equal(trigger.matches('flag=anything', { cookie: 'flag', contains: '', events: [] }), true);
});

test('decodes percent-encoded cookie values', () => {
	assert.equal(trigger.matches('cky-consent=ad%3Ayes', GRANT), true);
});

test('evaluate returns grant when only the grant trigger matches', () => {
	assert.equal(trigger.evaluate('cky-consent=ad:yes', { grant: GRANT, reject: REJECT }), 'grant');
});

test('evaluate returns reject when only the reject trigger matches', () => {
	assert.equal(trigger.evaluate('cky-consent=ad:no', { grant: GRANT, reject: REJECT }), 'reject');
});

test('evaluate returns none when neither matches', () => {
	assert.equal(trigger.evaluate('other=1', { grant: GRANT, reject: REJECT }), 'none');
});

test('evaluate returns none when both match, because that is ambiguous', () => {
	const both = { cookie: 'c', contains: 'x', events: [] };
	assert.equal(trigger.evaluate('c=x', { grant: both, reject: both }), 'none');
});

test('sanitize accepts a well formed trigger', () => {
	assert.deepEqual(trigger.sanitize({ cookie: 'cky-consent', contains: 'ad:yes', events: ['cky_update'] }), {
		cookie: 'cky-consent',
		contains: 'ad:yes',
		events: ['cky_update'],
	});
});

test('sanitize rejects a cookie name with illegal characters', () => {
	assert.equal(trigger.sanitize({ cookie: 'bad name;', contains: '', events: [] }), null);
});

test('sanitize rejects a missing cookie name', () => {
	assert.equal(trigger.sanitize({ contains: 'x', events: [] }), null);
});

test('sanitize drops event names with illegal characters', () => {
	assert.deepEqual(trigger.sanitize({ cookie: 'c', contains: '', events: ['ok_one', 'bad name'] }), {
		cookie: 'c',
		contains: '',
		events: ['ok_one'],
	});
});

test('sanitize caps the event list at five entries', () => {
	const many = ['a', 'b', 'c', 'd', 'e', 'f', 'g'];
	assert.equal(trigger.sanitize({ cookie: 'c', contains: '', events: many }).events.length, 5);
});

test('sanitize caps the contains value at 256 characters', () => {
	const long = 'x'.repeat(300);
	assert.equal(trigger.sanitize({ cookie: 'c', contains: long, events: [] }).contains.length, 256);
});

test('eventNames collects and deduplicates names across both triggers', () => {
	assert.deepEqual(
		trigger.eventNames({
			grant: { cookie: 'c', contains: 'yes', events: ['a', 'b'] },
			reject: { cookie: 'c', contains: 'no', events: ['b', 'c'] },
		}),
		['a', 'b', 'c']
	);
});

test('eventNames returns an empty list for a null config', () => {
	assert.deepEqual(trigger.eventNames(null), []);
});

test('eventNames never returns a name sanitize would reject', () => {
	assert.deepEqual(
		trigger.eventNames({
			grant: { cookie: 'c', contains: '', events: ['good_one', 'bad name'] },
			reject: { cookie: 'c', contains: '', events: [] },
		}),
		['good_one']
	);
});

test('eventNames skips a side whose trigger is invalid', () => {
	assert.deepEqual(
		trigger.eventNames({
			grant: { cookie: 'bad name;', contains: '', events: ['x'] },
			reject: { cookie: 'c', contains: '', events: ['y'] },
		}),
		['y']
	);
});

test('falls back to the raw value when the cookie value cannot be decoded', () => {
	// A lone % is not a valid escape sequence, so decodeURIComponent throws.
	assert.equal(trigger.matches('cky-consent=100%', { cookie: 'cky-consent', contains: '100%', events: [] }), true);
});

test('a value that cannot be decoded does not produce a false grant', () => {
	assert.equal(trigger.evaluate('cky-consent=100%', { grant: GRANT, reject: REJECT }), 'none');
});
