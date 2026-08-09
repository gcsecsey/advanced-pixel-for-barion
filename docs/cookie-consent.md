# Cookie Consent Integration

Barion's own page is the source of truth here:
[Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements).
It also carries the cookie banner text Barion recommends, and the current list of
Barion's advertising partners. Read it before you go live — compliance is the
merchant's responsibility, not the plugin's.

## What the plugin does

The base pixel script always loads, and `pageView` always fires. Barion documents
this as legitimate interest: the base pixel exists for payment fraud prevention,
and data collected without marketing consent is only used for that.

On top of that, the plugin calls `bp('consent', 'grantConsent')` when the customer
accepts marketing cookies and `bp('consent', 'rejectConsent')` when they decline.
Barion lists both as required. Your banner therefore has to offer a real reject
option — with an accept-only banner the plugin has nothing to signal.

The plugin looks for a consent manager in this order, and stops at the first one
it finds:

1. **WP Consent API** (recommended) — universal, works with all major cookie plugins
2. **Cookie Law Info** (fallback) — direct integration for CookieYes / Cookie Law Info
3. **Manual** — for custom consent managers

---

## Tier 1: WP Consent API (recommended)

The [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) is a WordPress
standard for passing consent between plugins. The Barion Pixel registers under its
`marketing` category.

### How it works

After `DOMContentLoaded` the plugin checks for the `wp_has_consent()` function. If
it exists:

1. If `marketing` consent is already granted, `grantConsent` fires immediately.
2. From then on the plugin listens for `wp_listen_for_consent_change` and fires `grantConsent` or `rejectConsent` on every change.

Note what is *not* in that list: on a page load where marketing consent is absent,
the plugin stays silent rather than sending `rejectConsent`. Before the customer
has answered the banner there is nothing to report, and the answer arrives through
the change event.

### Supported cookie plugins

Any plugin that implements the WP Consent API works automatically:

| Plugin | Active installs | Notes |
|--------|----------------|-------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1.5M+ | WP Consent API built-in |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1M+ | Co-creator of WP Consent API |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1M+ | WP Consent API compatible |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300K+ | WP Consent API compatible |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100K+ | WP Consent API compatible |

### Setup

1. Install and activate [WP Consent API](https://wordpress.org/plugins/wp-consent-api/).
2. Install and configure your cookie consent plugin.
3. Install and configure Advanced Pixel for Barion.

Nothing else to do — consent is handled automatically.

---

## Tier 2: Cookie Law Info (fallback)

Used when the WP Consent API is not available but
[Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) / CookieYes is.

### How it works

1. The plugin checks for the `CLI` global and its `allowedCategories`.
2. If the `cookielawinfo-checkbox-non-necessary` cookie is already `yes` — a returning visitor who accepted — `grantConsent` fires immediately.
3. Clicks on the banner's `.cli_action_button` elements are watched. Shortly after a click the plugin reads that cookie again and fires `grantConsent` or `rejectConsent` accordingly.

### Setup

None. Install both plugins and it works.

---

## Tier 3: Manual integration

For custom consent managers, or where neither of the above applies.

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

## What you still have to do yourself

The plugin forwards consent. It cannot write your policies or configure your
banner, and Barion requires both. From
[Barion's requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements):

- **Add the Barion cookies to your cookie policy.** `ba_vid`, `ba_vid.xxx`, `ba_sid` and `ba_sid.xxx` belong with your essential cookies — they serve fraud prevention on Barion's legitimate interest and need no consent. `BarionMarketingConsent.xxx` and the media and advertiser partner cookies belong with your marketing cookies and do need consent.
- **Mention the Barion Pixel in your privacy policy**, and link Barion's [privacy notice](https://www.barion.com/en/privacy-notice/).
- **Let customers change or withdraw consent at any time**, and re-prompt them. Barion asks for the banner to reappear at least every 13 months, and recommends 30 days.
- **Use Barion's recommended banner wording** where you can. It is on the requirements page, and it covers the partner data sharing that the Barion Pixel implies.

---

## How consent affects the pixel

| State | Base pixel (bp.js) | pageView | Marketing data collection |
|-------|--------------------|----------|--------------------------|
| Before any consent action | Loaded | Fires (fraud prevention) | No |
| After `grantConsent` | Loaded | Fires | Yes |
| After `rejectConsent` | Loaded | Fires (fraud prevention) | No |

---

## Testing

1. Enable **Debug Mode** in Settings > Barion Pixel.
2. Open the browser console (F12).
3. Look for these messages:

| Message | Meaning |
|---------|---------|
| `Consent auto-granted via WP Consent API` | Tier 1, consent was already granted on load |
| `Consent granted via WP Consent API change event` | Tier 1, customer accepted just now |
| `Consent rejected via WP Consent API change event` | Tier 1, customer declined just now |
| `Cookie Law Info detected, initial non-necessary cookie: …` | Tier 2 took over, with the cookie value it read |
| `Cookie Law Info button clicked, non-necessary cookie: …` | Tier 2, customer used the banner |
| `No consent manager detected…` | Tier 3 — nothing was found, call the functions yourself |
| `Consent granted (grantConsent)` | `grantConsent` reached bp.js (any tier) |
| `Consent rejected (rejectConsent)` | `rejectConsent` reached bp.js (any tier) |

All messages are prefixed with `[Barion Pixel]`.

4. Test both the accept and the reject path on your banner.
5. The consent functions are safe to call repeatedly.
