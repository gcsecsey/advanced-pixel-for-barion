# Cookie Consent Integration

## Overview

The Barion Pixel requires explicit user consent before collecting marketing data (GDPR compliance). The plugin must call `bp('consent', 'grantConsent')` when the user accepts, and `bp('consent', 'rejectConsent')` when the user declines. Both events are mandatory per Barion's requirements.

The base pixel script always loads for fraud prevention, but no marketing data is collected until consent is explicitly granted or rejected.

**Important:** Your cookie banner must offer both an accept and a reject option. A "cookie wall" (accept-only) is not GDPR compliant since 2020 and will be rejected by Barion.

The plugin supports four tiers of consent integration, checked in order:

1. **Recorded trigger** — a cookie signal captured by the setup wizard; it wins only when both
   the accept and the reject signal are recorded, because the shop owner set it deliberately. A
   half-taught trigger — only one signal recorded — is ignored entirely, and the plugin falls
   through to the next tier, because Barion requires both `grantConsent` and `rejectConsent`.
2. **WP Consent API** (recommended) — universal, works with all major cookie plugins
3. **Cookie Law Info** (fallback) — direct integration for sites using CookieYes/Cookie Law Info
4. **Manual** — for custom consent managers or edge cases

---

## The health panel

Settings › Barion Pixel opens with a health panel. It runs every check below and shows the worst
result first. When everything passes, it collapses to one green line.

The most important check is **"No cookie banner plugin sets a consent type"**. The WP Consent API
reports consent for every category when nothing sets a consent type:

> If there's no consent management plugin to set it, it will return `false`. This will cause all
> consent categories to return `true`.

A site with the WP Consent API active but no cookie banner therefore grants Barion consent for
every visitor, with no consent collected. That breaks the GDPR and the Barion terms.

Some banners set the consent type in the browser only, so the panel first reports a warning and
offers a **Check in browser** button. That check reads the real values from your front end before
any interaction, and turns the row red or green accordingly.

### The Barion cookies

`bp.js` sets three first-party cookies on your own domain. Each name gets a hash of your domain
appended at runtime.

| Cookie | Duration | Purpose |
|--------|----------|---------|
| `ba_sid` | 30 minutes | Groups page views into one session. Used by Barion for fraud prevention. |
| `ba_vid` | 1.5 years | Identifies a returning visitor for marketing analytics. |
| `BarionMarketingConsent` | 1.5 years, removed when the visitor rejects | Records the consent choice. |

With the WP Consent API plugin active, the plugin declares all three automatically, so they appear
in your cookie policy. Without it, add them by hand.

## The setup wizard

If no consent source works, the panel offers **Set up consent**. The wizard opens your shop in a
new tab, you accept in your own banner, and the plugin records which cookie changed. You repeat
for reject. Barion requires both `grantConsent` and `rejectConsent`, so the wizard refuses to save
until it has both.

The wizard stores a cookie name, the accepted and rejected values, and up to five event names. It
never stores or runs JavaScript that you supply. The recorder loads only for a logged-in
administrator who arrives with a valid nonce; it never loads for a visitor.

### Why the consent category is fixed

The plugin always asks for the `marketing` category and offers no choice. The WP Consent API
defines five fixed categories, and cookie banner plugins map their own categories onto them in
code. CookieYes maps Advertisement to marketing, Analytics to statistics, Functional to
preferences, and Performance to functional. You cannot change that map.

Barion requires consent for marketing purposes, so `marketing` is the only correct category. A
selector would let you fire Barion on a statistics checkbox, which breaks the Barion terms.

---

## Tier 2: WP Consent API (Recommended)

The [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) is a WordPress standard for consent communication. It's supported by all major cookie consent plugins.

### How it works

The plugin checks for the `wp_has_consent()` JavaScript function at runtime. If the WP Consent API is available:

1. On page load, checks if `marketing` consent is granted or rejected
2. Calls `bp('consent', 'grantConsent')` if marketing consent is granted
3. Calls `bp('consent', 'rejectConsent')` if marketing consent is not granted
4. Listens for the `wp_listen_for_consent_change` event for real-time consent updates — grants or rejects accordingly

### Supported cookie plugins

Any plugin that implements the WP Consent API will work automatically:

| Plugin | Active installs | Notes |
|--------|----------------|-------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1.5M+ | WP Consent API built-in |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1M+ | Co-creator of WP Consent API |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1M+ | WP Consent API compatible |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300K+ | WP Consent API compatible |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100K+ | WP Consent API compatible |

### Setup

1. Install and activate the [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) plugin
2. Install and configure your preferred cookie consent plugin (see table above)
3. Install and configure Advanced Pixel for Barion
4. No additional configuration needed — consent is handled automatically

### Consent category

The Barion Pixel is registered under the `marketing` consent category in the WP Consent API. This is the standard category for tracking pixels used for retargeting and analytics.

---

## Tier 3: Cookie Law Info (Fallback)

If the WP Consent API is not available, the plugin falls back to direct integration with the [Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) / CookieYes plugin.

### How it works

1. Checks for the `CLI` JavaScript global object
2. If cookies are already accepted (returning visitor), grants consent immediately
3. If cookies are not accepted, rejects consent immediately
4. Listens for the `cli_user_preference_set` event when the user interacts with the cookie banner
5. Grants or rejects based on the `cookielawinfo-checkbox-necessary` cookie value

### Setup

No configuration needed. Install both plugins and the integration works automatically.

---

## Tier 4: Manual Integration

For custom consent managers or environments where neither WP Consent API nor Cookie Law Info is available.

### Method 1: JavaScript functions (recommended)

```javascript
// When user accepts marketing cookies
function onMarketingConsentGranted() {
    if (typeof window.wcBarionGrantConsent === 'function') {
        window.wcBarionGrantConsent();
    }
}

// When user rejects marketing cookies
function onMarketingConsentRejected() {
    if (typeof window.wcBarionRejectConsent === 'function') {
        window.wcBarionRejectConsent();
    }
}
```

### Method 2: Custom DOM events

```javascript
// Grant consent
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Reject consent
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### Method 3: WordPress action hook

```php
// In your consent manager plugin or theme
add_action('wc_barion_pixel_footer_scripts', 'my_barion_consent_handler');

function my_barion_consent_handler() {
    ?>
    <script>
    // Your custom consent logic here
    </script>
    <?php
}
```

### Examples for specific consent managers

**Cookiebot:**
```javascript
window.addEventListener('CookiebotOnAccept', function() {
    if (Cookiebot.consent.marketing) {
        window.wcBarionGrantConsent();
    } else {
        window.wcBarionRejectConsent();
    }
});
window.addEventListener('CookiebotOnDecline', function() {
    window.wcBarionRejectConsent();
});
```

**OneTrust:**
```javascript
function OptanonWrapper() {
    if (OnetrustActiveGroups.includes('C0004')) {
        window.wcBarionGrantConsent();
    } else {
        window.wcBarionRejectConsent();
    }
}
```

---

## How consent affects the pixel

| State | Base pixel (bp.js) | pageView | Marketing data collection |
|-------|--------------------|----------|--------------------------|
| Before any consent action | Loaded | Fires (fraud prevention) | No data collected |
| After `grantConsent` | Loaded | Fires | Full data collection enabled |
| After `rejectConsent` | Loaded | Fires (fraud prevention) | No marketing data collected |

The base pixel always loads for Barion's fraud prevention. The `grantConsent` / `rejectConsent` calls control whether marketing data is collected.

---

## Testing

1. Enable **Debug Mode** in Settings > Barion Pixel
2. Open the browser console (F12)
3. Look for consent-related log messages:
   - `[Barion Pixel] Consent granted via the recorded cookie trigger` — Tier 1, accepted
   - `[Barion Pixel] Consent rejected via the recorded cookie trigger` — Tier 1, rejected
   - `[Barion Pixel] Consent auto-granted via WP Consent API` — Tier 2, user accepted
   - `[Barion Pixel] Consent auto-rejected via WP Consent API` — Tier 2, user declined
   - `[Barion Pixel] Consent auto-granted via Cookie Law Info` — Tier 3, user accepted
   - `[Barion Pixel] Consent auto-rejected via Cookie Law Info` — Tier 3, user declined
   - `[Barion Pixel] No consent manager detected...` — Tier 4 (manual mode)
   - `[Barion Pixel] Consent granted (grantConsent)` — consent was granted (any tier)
   - `[Barion Pixel] Consent rejected (rejectConsent)` — consent was rejected (any tier)
4. Test both accept and reject flows on your cookie banner
5. The consent functions are safe to call multiple times (idempotent)
