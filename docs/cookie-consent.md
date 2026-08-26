# Cookie Consent Integration

Barion's own page is the source of truth here:
[Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements).
It also carries the cookie banner text Barion recommends, and the current list of
Barion's advertising partners. Read it before you go live — compliance is the
merchant's responsibility, not the plugin's.

Barion also lists `grantConsent` among the events that
[must be implemented](https://docs.barion.com/Implementing_the_Full_Barion_Pixel)
before a Full Pixel integration is approved. A shop that never sends it does not
qualify for the lower fees, however complete the rest of the integration is.

## What the plugin does

The base pixel script always loads, and `pageView` always fires. Barion documents
this as legitimate interest: the base pixel exists for payment fraud prevention,
and data collected without marketing consent is only used for that.

On top of that, the plugin calls `bp('consent', 'grantConsent')` when the customer
accepts marketing cookies and `bp('consent', 'rejectConsent')` when they decline.
Barion lists both as required. Your banner therefore has to offer a real reject
option — with an accept-only banner the plugin has nothing to signal.

## How consent is detected

The plugin does not choose one consent manager. It subscribes to every consent
signal it knows, all at once, and forwards the first real answer and every change
after it. Load order does not matter: the listeners are registered before any
consent manager exists, so a banner that appears late is still caught. A
returning visitor sees no banner and so raises no event at all, which is why the
plugin also looks for a consent manager every half second until one answers, and
gives up ten seconds after the page loads.

These work with no extra plugin:

| Consent manager | Read through |
|---|---|
| [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) | `wp_has_consent('marketing')` and `wp_listen_for_consent_change`, but only once a banner registers a consent type with it |
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | `getCkyConsent()` and `cookieyes_consent_update` |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | `cmplz_has_consent('marketing')` and `cmplz_status_change` |
| [Cookiebot](https://wordpress.org/plugins/cookiebot/) | `Cookiebot.consent.marketing` and `CookiebotOnAccept` / `CookiebotOnDecline` / `CookiebotOnConsentReady` |
| Cookie Law Info 2.x, legacy banner | the `cookielawinfo-checkbox-non-necessary` cookie, re-read after a banner click |
| Anything else | you call the functions yourself — see [Manual integration](#manual-integration) |

Three rules apply to all of them:

- **Consent is sent when the visitor answers the banner, never at page load.** Barion asks for `grantConsent` at the moment of the click, and rejects an integration that sends it before the visitor has touched anything — from Barion's side that looks like a shop which never asks. The plugin therefore reads the consent state on load but keeps it to itself, and only sends what the visitor decides on this page load.
- **Nothing is sent before the visitor answers.** On a page load with no marketing consent the plugin stays silent rather than sending `rejectConsent`. There is nothing to report until the banner is answered.
- **Only changes are sent.** A repeated identical state is not sent twice, which matters because one click can arrive through two adapters at once.

A returning visitor who accepted on an earlier visit therefore triggers nothing,
and that is correct: bp.js stores the answer in its own `BarionMarketingConsent`
cookie, so Barion already has it. Re-sending it on every page load is what got
the integration rejected in the first place. To watch `grantConsent` fire, clear
your cookies first so the banner asks again.

## WP Consent API — still recommended

The [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) is the
WordPress standard for passing consent between plugins, and the Barion Pixel
registers under its `marketing` category. It is a **separate plugin** — not part
of WordPress, and not part of your cookie banner. A
[proposal to move it into core](https://make.wordpress.org/core/2024/12/04/lets-reconsider-adopting-the-wp-consent-api/)
is open but not merged.

Install it when your cookie banner is not in the table above. Most banners
support the WP Consent API, but only while that plugin is active: CookieYes, for
example, loads its bridge only when the `WP_CONSENT_API` class exists. Without it
those banners forward nothing, and the plugin has to fall back on the direct
integrations.

| Plugin | Active installs |
|--------|----------------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1.5M+ |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1M+ |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1M+ |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300K+ |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100K+ |

---

## Manual integration

For custom consent managers, or where none of the above applies.

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

### Example: OneTrust

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
| `Consent manager detected: …` | The named managers were found and wired |
| `No consent manager detected…` | Nothing was found — call the functions yourself |
| `Consent granted (grantConsent)` | `grantConsent` reached bp.js |
| `Consent rejected (rejectConsent)` | `rejectConsent` reached bp.js |

All messages are prefixed with `[Barion Pixel]`.

4. Test both the accept and the reject path on your banner.
5. The consent functions are safe to call repeatedly.

`No consent manager detected` also appears as a warning on the plugin's settings
page when the WP Consent API plugin is inactive, since this is the failure that
gets a Full Pixel integration rejected.

The settings page carries a second warning for the trap behind that one: the WP
Consent API active with no cookie banner registered against it. On its own the
API answers "granted" for everybody, because an unset consent type is how it says
that no banner is driving it. Installing it next to a banner that does not
support it therefore does not connect anything — it only makes every visitor look
like they consented. The plugin ignores it in that state.
