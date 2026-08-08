# Design: Consent health panel and consent setup wizard

Date: 2026-08-08
Status: Approved
Plugin: Advanced Pixel for Barion

## Problem

Barion permits full pixel tracking only after the visitor consents to marketing use of their
data. The plugin already implements this through the WP Consent API, but the shop owner has no
way to see whether it works. Three failures are invisible today.

1. **Silent consent for everyone.** `assets/js/barion-pixel-base.js:75` calls
   `wp_has_consent('marketing')`. The WP Consent API returns `true` for every category when no
   consent management plugin sets the consent type:

   > The `consent_type` is a function that wraps a filter, `wp_get_consent_type`. If there's no
   > consent management plugin to set it, it will return `false`. This will cause all consent
   > categories to return `true`, allowing cookies and other types of tracking for all
   > categories.

   So a site that activates WP Consent API without a banner plugin sends `grantConsent` for
   every visitor, with no consent collected. This breaks GDPR and the Barion terms, and nothing
   reports it.

2. **No consent manager at all.** Sites that use a banner outside the WP Consent API and
   outside Cookie Law Info fall to the manual tier. Consent is then never granted, and full
   tracking silently collects nothing. Fixing it needs custom JavaScript today.

3. **Invalid configuration.** `sanitize_settings` (line 191) runs only `sanitize_text_field`, so
   a malformed Pixel ID saves without complaint.

## Solution

Add two things to Settings › Barion Pixel.

- A **health panel** at the top of the page. It runs a fixed list of checks and shows the worst
  result first. Each failing check carries its own action.
- A **consent wizard**, opened from the panel. It records the consent signal of any cookie
  banner by observation, so the shop owner needs no code.

Mockups: `docs/superpowers/specs/2026-08-08-consent-health-panel-mockup.html`.

### Decisions taken during design

| Decision | Reason |
|---|---|
| The consent category stays fixed at `marketing`. | The WP Consent API defines five fixed slugs. Banner plugins map their own categories onto those slugs in code; the site owner cannot change the map. A selector would only let the user fire Barion on a non-marketing checkbox, which breaks the Barion terms. The panel shows the category as a read-only row with an explanation. |
| No master kill switch. | Deactivating the plugin already does this. |
| Reachability is a button, never automatic. | The pixel runs in the visitor's browser, not on the server. A browser probe measures the real thing, adds no outbound server request, and needs no WordPress.org disclosure. It is easy to remove if it proves useless. |
| The recorder stores cookie names, cookie values and event names only. | No user-supplied JavaScript is stored or executed. This keeps the manual edit form safe by construction. |
| The wizard refuses to save until both accept and reject are recorded. | Barion requires `grantConsent` and `rejectConsent`. A half-taught trigger is worse than none. |

## Architecture

The plugin is one 564-line file today. The new work roughly triples it, so split it. Keep the
split minimal — three includes, no interfaces, no abstractions with one implementation.

```
advanced-pixel-for-barion.php          bootstrap: constants, HPOS declaration, includes, init
includes/class-wc-barion-pixel.php     existing tracking class, moved unchanged
includes/class-wc-barion-health.php    health checks
includes/class-wc-barion-admin.php     settings page, panel, wizard, AJAX endpoints
assets/js/barion-pixel-base.js         extended: learned-trigger evaluation
assets/js/barion-consent-recorder.js   new: front-end recorder, admin only
assets/js/barion-admin.js              new: panel and wizard behaviour
assets/css/barion-admin.css            new: panel and wizard styles
tests/health-test.php                  new: runnable check for the health rules
tests/trigger-test.mjs                 new: runnable check for the trigger matcher
```

`.distignore` gains `tests/` and `.superpowers/`, so neither reaches the WordPress.org build.

`WC_Barion_Pixel` keeps its current responsibilities. It gains nothing except the learned
trigger passed to the base script.

### Health checks

`WC_Barion_Health` splits into two parts so the rules are testable without WordPress.

- `gather_facts()` touches WordPress and returns a flat array of primitives (`pixel_id`,
  `woocommerce_active`, `consent_api_active`, `consent_type`, `cli_active`, `trigger`, and so
  on).
- `evaluate( array $facts ): array` is pure. It maps facts to an ordered list of check results
  and never calls a WordPress function.

Each result is an array:

```php
array(
    'id'     => 'consent_type_set',
    'status' => 'fail',            // ok | warn | fail | info
    'label'  => 'No cookie banner plugin sets a consent type',
    'desc'   => '…',
    'action' => array( 'type' => 'modal', 'label' => 'Set up consent', 'target' => 'wizard' ),
)
```

The panel sorts `fail`, then `warn`, then `ok`, then `info`. The overall status is the worst
status present. When the overall status is `ok`, the panel renders collapsed to one line.

The checks:

| id | Status when it fails | What it detects |
|---|---|---|
| `pixel_id` | fail | Missing, or does not match `/^BP-\d{10}-\d{2}$/` |
| `consent_type_set` | warn, then fail after the browser probe | No consent type set — the silent-consent defect |
| `consent_source` | info | Which tier is live: WP Consent API, learned trigger, Cookie Law Info, or none |
| `consent_both_signals` | fail | A learned trigger exists for accept but not for reject |
| `woocommerce` | warn | WooCommerce inactive while full tracking is on |
| `full_tracking` | info | Full tracking on or off |
| `gateway_duplicate_id` | warn | Barion Payment Gateway holds a second Pixel ID |
| `category` | info | Read-only `marketing` row with the mapping explained |
| `cookies_declared` | info, when the WP Consent API is inactive | Whether Barion's cookies are declared to the cookie policy |
| `reachability` | info until run | Result of the browser probe |

**`consent_type_set` needs care.** `wp_get_consent_type()` reads a filter that most banner
plugins set in PHP, but the WP Consent API also permits a banner to set the consent type in the
browser only. A bare PHP check would therefore raise a false alarm on those sites. So:

- The PHP check reports `warn`, not `fail`, and its description states the caveat.
- The row carries a **Check in browser** button. It opens the shop in a probe tab, reads
  `wp_get_consent_type()` and `wp_has_consent('marketing')` before any interaction, and reports
  back. Consent granted with no interaction confirms the defect and upgrades the row to `fail`.
  A consent type found in the browser downgrades the row to `ok`.

Also fix `sanitize_settings`: reject a Pixel ID that fails the format, keep the previous value,
and raise a `add_settings_error` notice.

### Consent wizard

A native `<dialog>` element. No modal library, no React.

**Step 1 — choose the source.** The wizard lists what it found: WP Consent API with its
usability verdict, Cookie Law Info, and "teach the plugin my banner". It preselects the option
that will work.

**Step 2 — record.** The wizard opens the shop front end in a new tab with
`?apb_record_consent=<nonce>`. The plugin enqueues `barion-consent-recorder.js` on that request
only, and only when `current_user_can('manage_options')` and the nonce verifies. The recorder
never loads for a visitor.

The recorder:

1. Snapshots `document.cookie` at start.
2. Wraps `document.dispatchEvent` and `window.dispatchEvent` to collect event names. It runs in
   the head at priority 1, so it wraps them before the banner loads.
3. Polls cookies every 250 ms and reports each added or changed cookie.
4. Sends findings to the admin tab with `window.opener.postMessage`, same origin, origin checked
   on receipt.

The admin tab shows the findings live. The user records accept, then repeats for reject.

**Step 3 — confirm.** The wizard shows the derived trigger and lets the user edit the values by
hand before saving.

### Trigger data model

Stored inside the existing `wc_barion_pixel_settings` option.

```php
'consent_trigger' => array(
    'grant'  => array(
        'cookie'   => 'cookieyes-consent',
        'contains' => 'advertisement:yes',
        'events'   => array( 'cookieyes_consent_update' ),
    ),
    'reject' => array( /* same shape */ ),
    'recorded_at' => '2026-08-08T10:00:00+00:00',
),
```

Sanitization, applied on save and again on read:

- `cookie` must match `/^[A-Za-z0-9_\-.]{1,128}$/`.
- `contains` passes `sanitize_text_field` and is capped at 256 characters.
- each event name must match `/^[A-Za-z0-9_\-:.]{1,128}$/`; the list is capped at 5 entries.
- A trigger that fails sanitization is dropped whole, not partially repaired.

### Front-end evaluation

`barion-pixel-base.js` gains the learned trigger as the first tier, because an explicit setting
by the shop owner must beat auto-detection. The order becomes:

1. Learned trigger, if one is stored and valid
2. WP Consent API
3. Cookie Law Info
4. Manual

The matcher is a pure function: given the cookie string and a trigger, return `grant`, `reject`
or `none`. It runs on load, on each recorded event name, and on a poll every 500 ms for the
first 30 seconds. The poll covers banners that set a cookie without dispatching any event; it
stops after 30 seconds so no page carries a permanent timer.

`grantConsent` and `rejectConsent` stay idempotent, as they are today.

## Testing

The repository has no test framework, and adding PHPUnit with the WordPress test suite is
disproportionate. Both new pieces of non-trivial logic are pure, so each gets one dependency-free
runnable check.

- `tests/health-test.php` feeds fact arrays to `WC_Barion_Health::evaluate()` and asserts the
  resulting statuses. Run with `php tests/health-test.php`. Exit code is non-zero on failure.
  It covers at least: the silent-consent case, a healthy WP Consent API site, an accept-only
  trigger, and a malformed Pixel ID.
- `tests/trigger-test.mjs` covers the cookie matcher with `node --test`. It covers a match, a
  near-miss value, a missing cookie, and a rejected malformed trigger.

Manual verification stays necessary for the recorder and the wizard, because both depend on a
third-party banner. `docs/testing-notes.md` gains a checklist for them.

## Documentation and translation

New user-facing strings use `__()` with the `advanced-pixel-for-barion` text domain. Run
`composer i18n:build` after the strings settle.

Content updates:

- `docs/cookie-consent.md` — the health panel, the wizard, the silent-consent trap, and why the
  category is fixed.
- `docs/testing-notes.md` — the manual checklist.
- `readme.txt` and `README.md` — the feature in the description and the changelog.
- The eight translations under `docs/i18n/` mirror the two changed docs.

## Out of scope

- A consent category selector. Rejected during design; the reason is recorded above.
- A master kill switch. Rejected during design.
- Any check of Barion's two calendar rules — consent revocable, banner repeated within 13
  months. The plugin cannot observe either from WordPress. `docs/cookie-consent.md` states them
  instead.
- Shipping adapters for named third-party managers such as Cookiebot or OneTrust. The recorder
  covers them without the maintenance cost of five third-party APIs.

## Resolved: cookie declaration

The mockup shows a **Declare cookies** action driven by `wp_add_cookie_info()`. The open question
was whether that function applies, since it declares cookies *the plugin itself sets*, and Barion's
`bp.js` was assumed to set its cookies from `pixel.barion.com`. Reading the live script at
`https://pixel.barion.com/bp.js` showed that assumption was wrong: `bp.js` sets its cookies with
`"." + document.domain`, so they are first-party cookies on the shop's own domain, not on
`pixel.barion.com`. `wp_add_cookie_info()` also explicitly supports declaring third-party services
— it takes a `$domain` parameter and its own docblock uses "Google Maps" as the example.

Each cookie name is a stable prefix plus a murmurHash3 of the site domain, computed in the
browser at runtime, so the plugin declares only the prefixes and explains the suffix in the
description text.

| Prefix | Lifetime | Purpose |
|---|---|---|
| `ba_sid` | 30 minutes | Session grouping, used for fraud prevention |
| `ba_vid` | 1.5 years | Returning-visitor ID for marketing analytics |
| `BarionMarketingConsent` | 1.5 years (deleted on reject) | Records the visitor's marketing consent choice |

`WC_Barion_Pixel::declare_cookies()` registers these three cookies with `wp_add_cookie_info()`
when the WP Consent API is active. The `cookies_declared` health row reflects whether that
declaration can take effect.
