=== Advanced Pixel for Barion ===
Contributors: mrdarkside
Tags: barion, pixel, woocommerce, tracking, e-commerce
Requires at least: 5.0
Tested up to: 7.0
Stable tag: 1.0.7
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Barion Pixel integration for WooCommerce with full e-commerce event tracking, cookie consent support, and WP Consent API compatibility.

== Description ==

Advanced Pixel for Barion adds Barion Pixel tracking to your WooCommerce store. It supports two modes:

**Base Pixel** (always active): Loads the Barion tracking script on all pages. Fires `pageView` automatically for fraud prevention and basic analytics.

**Full Tracking** (optional): Tracks all mandatory e-commerce events per the Barion Pixel documentation:

* **contentView** - Product page views
* **addToCart** - Add to cart actions (client-side, compatible with page caching)
* **initiateCheckout** - Checkout page views
* **purchase** - Completed orders with full revenue tracking
* **setEncryptedEmail** - Encrypted billing email for user identification

Full Tracking is also one of the conditions of Barion's discounted "Advanced" gateway package. You have to request that package from Barion, and Barion reviews your pixel implementation before granting it — installing this plugin does not change your fees on its own. See the FAQ below.

= Cookie Consent =

The plugin integrates with the [WP Consent API](https://wordpress.org/plugins/wp-consent-api/), supporting all major cookie consent plugins:

* CookieYes
* Complianz
* Real Cookie Banner
* GDPR Cookie Compliance (Moove)
* Cookie Notice by dFactory

Direct fallback integration with Cookie Law Info is also included.

= Supported Languages =

* English (default)
* Hungarian (hu_HU)
* Czech (cs_CZ)
* Slovak (sk_SK)
* German (de_DE)
* Croatian (hr)
* Romanian (ro_RO)
* Slovenian (sl_SI)
* Serbian (sr_RS)

= Privacy =

This plugin loads the Barion Pixel script (bp.js) from pixel.barion.com on all frontend pages. Page view and e-commerce event data is sent to Barion's servers. On purchase completion, the billing email address is passed to bp.js which encrypts it with SHA1 before transmission — no plaintext email leaves your server.

= Key Features =

* Base Pixel with automatic pageView tracking
* Full e-commerce event tracking with all required fields
* WP Consent API integration for universal cookie consent support
* Client-side add-to-cart tracking (compatible with page caching)
* Variable product support (tracks variation prices)
* Duplicate purchase prevention
* Debug mode with console logging
* Detects other plugins loading bp.js to avoid double-loading

== Installation ==

1. Upload the `advanced-pixel-for-barion` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Barion Pixel and enter your Barion Pixel ID
4. Optionally enable or disable Full Pixel Tracking

= Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* WooCommerce 5.0 or higher (for full event tracking)

= Optional =

* [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) for universal cookie consent support
* Any WP Consent API compatible cookie plugin (CookieYes, Complianz, etc.)

== Frequently Asked Questions ==

= Do I need WooCommerce? =

The Base Pixel (pageView) works without WooCommerce. Full event tracking (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail) requires WooCommerce.

= I already use the Barion Payment Gateway plugin. Will this conflict? =

No. The [Barion Payment Gateway](https://github.com/szelpe/woocommerce-barion) is a payment processor only — it doesn't implement Barion Pixel event tracking. Both plugins coexist perfectly. If both have a Pixel ID configured, Advanced Pixel for Barion detects that bp.js is already loaded and skips re-loading it.

= Which cookie consent plugins are supported? =

All plugins that implement the WP Consent API standard: CookieYes, Complianz, Real Cookie Banner, GDPR Cookie Compliance (Moove), Cookie Notice by dFactory, and others. Direct fallback integration with Cookie Law Info is also built in.

= What is the difference between Base Pixel and Full Tracking? =

**Base Pixel** loads bp.js and fires pageView on every page. This is used by Barion for fraud prevention and basic analytics. **Full Tracking** adds e-commerce events (product views, add to cart, checkout, purchase) that enable marketing analytics. Full Tracking is also a condition of Barion's discounted Advanced package — see the question about fees below.

= Can I use only the Base Pixel? =

Yes. Just uncheck "Enable Full Pixel Tracking" in the settings. The base pixel will still load and fire pageView events.

= How do I check that my events reach Barion? =

Barion has no page that lists your events. The Barion admin gives you your Pixel ID (Barion Wallet > Merchant Management > Details), but it does not show incoming events.

You check the events in your browser instead. Enable Debug Mode in Settings > Barion Pixel, then open the browser console (F12). Every event the plugin sends appears with a `[Barion Pixel]` prefix.

Barion's own bp.js script also writes to the same console, and its message tells you your approval state:

* `Testing message` — your pixel works, but Barion has not authorised it yet. Barion receives only the type of the event, not user data. Every new implementation starts here.
* `Sending message` — Barion reviewed and approved your implementation. Barion now receives the full data.

A person at Barion performs this approval. Contact Barion when your implementation is complete.

= Will this plugin lower my Barion transaction fees? =

Not on its own. Barion offers a discounted "Advanced" package for the Barion Smart Gateway, and a complete Full Pixel implementation with consent management is one of its conditions.

Two things are important. First, you have to **request** the package from Barion — it is not applied automatically when you install a pixel. Second, Barion examines your implementation before granting it, and commercial conditions apply as well, for example your average cart size and your card mix.

For the current conditions, see the [Barion Smart Gateway page](https://www.barion.com/en/business/barion-smart-gateway/).

= How does the plugin handle page caching? =

The addToCart event uses client-side JavaScript instead of PHP sessions, so it works correctly with all page caching setups (WP Super Cache, W3 Total Cache, LiteSpeed, WordPress.com hosting, etc.). Other events fire on dynamic pages that are not cached.

== Screenshots ==

1. Settings page — enter your Barion Pixel ID, then enable Full Pixel Tracking and Debug Mode.
2. Product page — Debug Mode logs `contentView` when the page loads, and `addToCart` when the customer adds the product to the cart.
3. Checkout page — `initiateCheckout` carries the cart contents and the revenue, and `setEncryptedEmail` sends the SHA-1 hashed billing email.
4. Order received page — `purchase` reports the completed order with its contents and revenue.
5. Events wait for consent. The plugin reads the WP Consent API, so any compatible cookie banner controls the tracking.

== Changelog ==

= 1.0.7 =
* Fix: a fatal error on any site that runs the plugin without WooCommerce, once a Pixel ID was saved and Full Tracking was on. The footer event script called `is_product()`, a function that only exists while WooCommerce is loaded, so the page died with `Call to undefined function is_product()`. The WooCommerce event hooks are now registered only when WooCommerce is active. The base pixel still loads without WooCommerce, as documented. This dates back to 1.0.0.

= 1.0.6 =
* Fix: `initiateCheckout` and `setEncryptedEmail` never fired on the WooCommerce Checkout block, which has been the default for new stores since WooCommerce 8.3. The plugin only listened for the classic checkout's PHP hooks and its `#billing_email` field, and the block has neither. It now reads the Cart and Checkout blocks' data store. Classic checkout behaviour is unchanged.
* Fix: `addToCart` never fired on shop or category pages, on any store. The events script was only loaded on pages that already had an event queued, which no archive page does, so the add-to-cart listeners were never present where customers actually add to cart. This affected classic stores too, and dates back to 1.0.1.
* Fix: `addToCart` now also works with the block product buttons used by the Product Collection block. These run on the Interactivity API and fire neither the classic jQuery event nor the block data store, so cart contents are now read from the WooCommerce Store API.

= 1.0.5 =
* Fix: the bundled Hungarian, Czech, Slovak, German, Croatian, Romanian, Slovenian and Serbian translations never loaded, so the settings screen stayed in English. WordPress only searches `wp-content/languages/plugins` unless a plugin registers its own directory, and the plugin never did. It now registers `languages/` on `init`.

= 1.0.4 =
* Compatibility: tested against WordPress 7.0 and WooCommerce 11.0.
* `Requires PHP` raised from 7.2 to 7.4. WordPress 7.0 dropped support for PHP 7.2 and 7.3, so 7.2 was no longer a version the plugin could run on.
* No functional changes.

= 1.0.3 =
* Fix: `setEncryptedEmail` was firing multiple times on a single checkout page load (the `change` + `blur` pair plus the `updated_checkout` rebind caused duplicates).
* Fix: bp.js rejected partial values (e.g. `x@y`) with error 12 (`Format of e-mail address or hash is invalid in setEncryptedEmail`). The email is now validated against the HTML5 spec for valid email addresses before being sent; pre-computed SHA-1 hashes are also accepted, matching the Barion Pixel API reference.
* Fix: emails containing `+` in the local part (e.g. `alice+tag@example.com`), or with TLDs longer than four letters (e.g. `.museum`, `.online`), were rejected by bp.js with `Format of e-mail address or hash is invalid`. The plugin now SHA-1 hashes the email client-side (via the Web Crypto API) before passing it to bp.js, which bypasses bp.js's restrictive internal email regex. The Barion Pixel API explicitly supports pre-computed SHA-1 hashes.
* Fix: aligned the bp.js call with the Barion documentation — `bp('identity', 'setEncryptedEmail', ...)` (previously `'identify'`).
* setEncryptedEmail now fires once for logged-in users on checkout load, and once per distinct, valid email entered into the billing field (no more `blur` handler, idempotent rebinding via a data attribute).

= 1.0.1 =
* Fix: events script (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail) was never printed because it was enqueued after `wp_print_footer_scripts` had already run.
* New: `setEncryptedEmail` now also fires when the customer enters their email on the checkout page (and on checkout load for logged-in users), as required by the Barion Pixel API reference.
* Fix: cookie consent auto-detection (WP Consent API and Cookie Law Info) now runs after `DOMContentLoaded`, so it can see globals defined by consent plugins that load later in the page.

= 1.0.0 =
* Initial release
* Base Barion Pixel (pageView) implementation
* Full event tracking: contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail
* WP Consent API integration (supports CookieYes, Complianz, Real Cookie Banner, and others)
* Cookie Law Info fallback integration
* Admin settings panel with debug mode
* Client-side addToCart tracking (compatible with page caching)
* Variable product support (tracks variation prices)
* Duplicate purchase prevention
* bp.js double-load detection

== Upgrade Notice ==

= 1.0.7 =
Fixes a fatal error on sites that run the plugin without WooCommerce. Stores that have WooCommerce active are unaffected.

= 1.0.6 =
Important fix for every store. The addToCart event never fired on shop or category pages. Checkout and email events were also missing on the block checkout. All of them are sent now.

= 1.0.5 =
Fixes the bundled translations, which never loaded. If you run WordPress in Hungarian, Czech, Slovak, German, Croatian, Romanian, Slovenian or Serbian, the plugin's settings screen is now translated.

= 1.0.3 =
Stops duplicate `setEncryptedEmail` events on checkout, and fixes the `Format of e-mail address or hash is invalid` error that 1.0.1 could produce — including for emails that contain `+` or use an extended TLD, which bp.js's internal regex rejected. The plugin now pre-hashes the email with SHA-1 before sending.

= 1.0.1 =
Critical fix: pixel events (including setEncryptedEmail) were never sent in 1.0.0 due to a script enqueueing timing bug. All users should upgrade.

= 1.0.0 =
Initial release.
