# Consent browser tests

`tests/consent.test.js` covers the adapters in isolation. These tests run the
whole thing in a real browser instead: WordPress, WooCommerce, the plugin as
enqueued, and a cookie banner the visitor actually clicks. They exist because
the reported failure — `grantConsent` never reaching Barion — depended on script
load order and on the visitor's history, neither of which a unit test reproduces.

The assertions live in the harness page itself, so it works both ways: `npm run
test:browser` drives it headless for CI, and opening the URLs in any browser
gives the same result on screen.

## Run them

```bash
npm install
npx playwright install chromium   # once
npm run test:browser
```

`run.mjs` boots Playground, opens both harness pages in headless Chromium, waits
for them to finish, prints every scenario and exits non-zero if any failed. This
is what CI runs — see `.github/workflows/test.yml`.

## Run them by hand

The assertions live in the harness page, so a browser and a server are enough:

```bash
npx @wp-playground/cli@latest server --port=9411 \
  --mount-dir "$PWD" "/wordpress/wp-content/plugins/advanced-pixel-for-barion" \
  --mount-dir "$PWD/tests/playground/mu-plugins" "/wordpress/wp-content/mu-plugins" \
  --blueprint=tests/playground/blueprint.json
```

Then open both pages and read the first line of each:

| Page | Covers |
| --- | --- |
| <http://127.0.0.1:9411/?barion-harness=1> | All five adapters, against stub consent managers |
| <http://127.0.0.1:9411/?barion-harness=real> | The real `wp-consent-api` plugin, driven through its own `wp_set_consent()` |

Each page loads every scenario in an iframe, clicks the banner, and compares the
`bp('consent', ...)` calls the page made against the expected ones. The JSON
below the summary line has the per-scenario detail and the plugin's debug log.

## What they guard

Barion wants `grantConsent` at the moment the visitor clicks accept, and rejects
an integration that sends it at page load. That rule is invisible to a unit test:
it depends on whether a gesture reached the page before the banner answered. The
`click: null` scenarios all expect `[]` for this reason — they load a page as a
returning visitor who already consented, and assert that nothing is sent.

Before changing the consent adapters, break them on purpose and check these fail.
Removing the gesture gate in `assets/js/barion-consent.js` should fail exactly
three scenarios: the two returning-visitor ones and `Real WPCA optin - returning,
allowed`.

## How the fixtures work

`00-bp-recorder.php` replaces `bp()` with a recorder before the plugin's scripts
run, so nothing is sent to `pixel.barion.com` and every call is observable in
`window.__bpCalls`.

`01-stub-cmp.php` emulates the consent managers. CookieYes and Cookiebot are
paid products served from their own CDN and cannot be installed here, so the
stubs reproduce the globals and events their real scripts expose. Select one
with `?cmp=`, and reproduce the awkward cases with `&late=1` (the manager is
defined 600 ms after `DOMContentLoaded`) and `&prior=1` (a returning visitor who
already answered, so no banner and no click).

`03-real-wpca.php` uses no stub. It sets `window.wp_consent_type` and calls the
real plugin's `wp_set_consent()`, which is exactly what a bridged cookie banner
does.

Worth knowing when reading the results: the real `wp-consent-api` dispatches
`wp_listen_for_consent_change` **only when the stored value changes**, and
`wp_has_consent()` returns `true` when no consent type is configured at all.
Both behaviours are visible in the `real` harness.

## Adding a scenario

Add an entry to `SCENARIOS` or `REAL` in `02-harness.php`. Set `expect` to the
consent calls the page should make, in order, and `[]` when it should stay
silent — staying silent before the visitor answers is a documented rule, not an
omission.
