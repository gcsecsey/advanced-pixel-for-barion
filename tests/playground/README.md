# Playground browser tests

`tests/consent.test.js` and `tests/cart-diff.test.js` cover the JavaScript in
isolation. These tests run the whole thing in a real browser instead: WordPress,
WooCommerce, the plugin as enqueued, a cookie banner the visitor actually
clicks, and a shop with a real product and a real order.

They exist because two rules cannot be reached from a unit test:

- **Consent timing.** `grantConsent` must reach Barion when the visitor clicks
  accept, and never at page load. That depends on script load order and on
  whether a gesture reached the page before the banner answered.
- **Payload validity.** The keys bp.js demands, and the values it accepts
  without complaint, depend on what WooCommerce actually renders on the page.
  `totalItemPrice` is rejected on `contentView` and required inside `contents`
  (see `docs/testing-notes.md`), and a price read from the wrong place is a
  number bp.js accepts and Barion misreads.

The assertions live in the harness pages, so it works both ways: `npm run
test:browser` drives them headless for CI, and opening the URLs in any browser
gives the same verdict on screen.

## Run them

```bash
npm install
npx playwright install --with-deps chromium   # once
npm run test:browser
```

`run.mjs` boots Playground, opens every harness page in headless Chromium, waits
for each to finish, prints every scenario and exits non-zero if any failed. This
is what CI runs — see the `browser` job in `.github/workflows/ci.yml`.

## Run them by hand

```bash
npx @wp-playground/cli@latest server --port=9411 \
  --mount-dir "$PWD" "/wordpress/wp-content/plugins/advanced-pixel-for-barion" \
  --mount-dir "$PWD/tests/playground/barion-test-harness" "/wordpress/wp-content/plugins/barion-test-harness" \
  --blueprint=tests/playground/blueprint.json
```

Then open <http://127.0.0.1:9411/?barion-harness=index>, which links everything:

| Page | Covers |
| --- | --- |
| `?barion-harness=1` | All five consent adapters, against stub consent managers |
| `?barion-harness=real` | The real `wp-consent-api` plugin, driven through its own `wp_set_consent()` |
| `?barion-harness=events` | `contentView`, `addToCart`, `initiateCheckout`, `purchase`, `setEncryptedEmail` payloads |

Add `?barion-panel=1` to any shop page for a live panel listing each `bp()` call
with its offset from page load. A consent call that arrived before you touched
the page is shown in red — that is the failure Barion rejects. The panel exists
because a Playground preview cannot open devtools: the site runs in an iframe on
another origin.

## Preview a pull request

`.github/workflows/playground-preview.yml` puts a **Preview in WordPress
Playground** button on every pull request. It reuses `blueprint.json` for the
site setup and swaps the local mounts for two `git:directory` steps pointing at
the pull request's own branch, so the preview runs the code under review. The
button lands on `?barion-harness=index`.

Fork pull requests get no button. `GITHUB_TOKEN` is read-only there, and the
alternative — `pull_request_target` — would preview the base branch instead.

You can boot that exact blueprint locally, against any pushed branch, which is
how the workflow was verified before it ever ran:

```bash
jq -c --arg url "$(git remote get-url origin)" --arg ref "$(git branch --show-current)" '
  .steps = ([ .steps[] | select( .step != "activatePlugin" ) ] + [
    { step: "installPlugin", pluginData: { resource: "git:directory", url: $url, ref: $ref, path: "/" }, options: { activate: true } },
    { step: "installPlugin", pluginData: { resource: "git:directory", url: $url, ref: $ref, path: "tests/playground/barion-test-harness" }, options: { activate: true } }
  ])' tests/playground/blueprint.json > /tmp/preview.json

npx @wp-playground/cli server --port=9412 --blueprint=/tmp/preview.json
```

The branch has to be pushed — `git:directory` fetches it over HTTPS, it does not
read your working copy.

## What they guard

Barion wants `grantConsent` at the moment the visitor clicks accept, and rejects
an integration that sends it at page load. The `click: null` scenarios all expect
`[]` for this reason — they load a page as a returning visitor who already
consented, and assert that nothing is sent.

Before changing the consent adapters, break them on purpose and check these fail.
Two guards in `assets/js/barion-consent.js` hold that rule up, and **neither
alone** produces a page-load send — removing just one changes nothing, which is
the point of having both:

1. the `acted` gesture gate in `report()`, and
2. the first-answer suppression in `observe()`, which records an adapter's
   opening answer instead of sending it.

Defeat both — drop the `if ( ! acted )` return, and make the `granted` branch of
`observe()` call `report( granted )` instead of assigning `reported` — and
exactly three scenarios fail, each showing the send that gets integrations
rejected:

```
FAIL  CookieYes - returning, accepted -> ["grantConsent"]
FAIL  Late CMP, returning visitor, no click -> ["grantConsent"]
FAIL  Real WPCA optin - returning, allowed -> ["grantConsent"]
```

Breaking `observe()` on its own is worth knowing too: recording *every* first
answer, not just a granted one, fails all five decline scenarios instead. No
adapter can tell an undecided visitor from one who refused, so suppressing a
first "no" is what makes `rejectConsent` fire at all.

The same applies to payloads, and it was checked the same way. Adding
`totalItemPrice` to the `contentView` data in `advanced-pixel-for-barion.php`
fails exactly the two scenarios that load a product page:

```
FAIL  Product page sends contentView without totalItemPrice
      contentView must not carry totalItemPrice
FAIL  Single product form sends addToCart
      contentView must not carry totalItemPrice
```

Setting `$unit_price = 0` in `queue_initiate_checkout()` fails one scenario, and
no key check would have noticed:

```
FAIL  Checkout page sends initiateCheckout
      initiateCheckout contents[0] has a zero or missing unitPrice, totalItemPrice
```

That second check is not decoration. It is how the archive add-to-cart bug was
found: WooCommerce renders no `data-product_price` on its loop button, so the
plugin reported every add from a shop or category page as worth 0. Every key was
present, so only a value check could see it.

## How the fixtures work

The harness is a plugin, not a set of mu-plugins, because a Playground preview
installs it with one `git:directory` step and that step installs one plugin
directory. Load order is unaffected: every fixture hangs off a hook priority.

| File | Role |
| --- | --- |
| `inc/recorder.php` | Stands in for `bp()` so nothing reaches `pixel.barion.com`, and captures the console. |
| `inc/stub-cmp.php` | Emulates the consent managers. Select one with `?cmp=`, and reproduce the awkward cases with `&late=1` and `&prior=1`. |
| `inc/real-wpca.php` | No stub. Sets `window.wp_consent_type` and calls the real plugin's `wp_set_consent()`, which is what a bridged banner does. |
| `inc/store.php` | Builds the shop: product, order, classic cart and checkout pages, Cash on Delivery. |
| `inc/scenarios.php` | Every scenario, as data. |
| `inc/runner.php` | The harness pages: loads each scenario in an iframe, acts on it, asserts. |
| `inc/panel.php` | The live event timeline. |

Two details worth knowing when reading results:

- The real `wp-consent-api` dispatches `wp_listen_for_consent_change` **only when
  the stored value changes**, and `wp_has_consent()` returns `true` when no
  consent type is configured at all. Both are visible in the `real` harness.
- The store fixture forces the classic `[woocommerce_cart]` and
  `[woocommerce_checkout]` pages. The plugin's server-side events hang off
  classic template hooks, and the block templates do not fire them dependably
  across WooCommerce releases. The block surfaces in `barion-pixel-events.js`
  are therefore **not** covered yet.

## Adding a scenario

Add an entry to the matching function in `inc/scenarios.php`.

For consent, set `expect` to the consent calls the page should make, in order,
and `[]` when it should stay silent — staying silent before the visitor answers
is a documented rule, not an omission.

For events, set `expect` to the tracked event names in order, and `keys` to the
payload rules: `require` for keys that must be present, `forbid` for keys that
must not be, and `positive` for money that must not come through as 0. Use
`contents` to apply the same rules to each item inside a `contents` array.
