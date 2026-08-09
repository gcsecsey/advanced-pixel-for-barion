# Plugin Compatibility

## WooCommerce

**Required for full event tracking.** The base pixel works without WooCommerce, but all e-commerce events (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail) require WooCommerce.

| Version | Status |
|---------|--------|
| WooCommerce 5.0+ | Supported |
| WooCommerce 11.0 | Tested |

### Cart and Checkout blocks

Supported since 1.0.6. The blocks fire neither the classic PHP hooks nor the DOM
selectors the plugin used before, so on block surfaces it reads WooCommerce data
directly: the Store API cart for `addToCart`, and the `wc/store/cart` data store
for the checkout email.

**Known limitation.** The `purchase` event runs through `woocommerce_thankyou`,
which the block Order Confirmation template fires from its "Additional
Information" block. Removing that block from the template silently stops
purchase tracking. Keep it in the template.

---

## Other sources of the base pixel

Barion documents several ways to get the base pixel onto a page, and a store can
easily end up with more than one of them:

- the [Barion Payment Gateway](https://github.com/szelpe/woocommerce-barion) by szelpe, and other Barion gateway plugins, which have an optional Pixel ID field
- a [Google Tag Manager tag](https://docs.barion.com/Implementing_the_Barion_Pixel_base_code_through_the_Google_Tag_Manager)
- a snippet pasted into the theme header

The plugin checks for `window.bp` and `window.BarionAnalyticsObject` before
loading `bp.js`. If both are already there it skips the script load and only sends
its own `init` call, so the pixel is never loaded twice. In debug mode this logs
`[Barion Pixel] bp.js already loaded by another plugin`.

**Recommendation:** keep the Pixel ID in one place. If you also run a Barion
payment gateway, configure the ID here and leave the gateway's field empty; if you
already load the base pixel through Google Tag Manager, remove that tag. Two
different Pixel IDs on one page is the case worth avoiding — the plugin can
suppress a duplicate script, but not a duplicate identity.

When the Barion Payment Gateway also has a Pixel ID configured, the settings page
shows an informational notice. Both plugins keep working either way: that one
handles payments, this one handles tracking.

---

## Page Caching Plugins

The plugin is fully compatible with page caching:

| Event | Implementation | Caching impact |
|-------|---------------|----------------|
| contentView | Server-side (product page) | Product pages are typically not cached, or vary by product |
| addToCart | **Client-side JavaScript** | No caching issues — JS fires in the browser |
| initiateCheckout | Server-side (checkout page) | Checkout is not cached (contains user session data) |
| purchase | Server-side (thank-you page) | Thank-you pages are not cached (unique per order) |

The addToCart event was specifically implemented client-side (rather than using PHP sessions) to work with WordPress.com hosting and aggressive page caching setups.

**Compatible with:** WP Super Cache, W3 Total Cache, LiteSpeed Cache, WordPress.com hosting, Cloudflare, and similar caching solutions.

---

## Cookie Consent Plugins

The plugin supports all cookie consent plugins that implement the [WP Consent API](https://wordpress.org/plugins/wp-consent-api/). See [Cookie Consent Integration](cookie-consent.md) for details.

**Automatically supported:**

- CookieYes (1.5M+ installs)
- Complianz (1M+ installs)
- Cookie Notice by dFactory (1M+ installs)
- GDPR Cookie Compliance by Moove (300K+ installs)
- Real Cookie Banner (100K+ installs)

**Direct fallback integration:**

- Cookie Law Info / CookieYes (works without WP Consent API too)

---

## WordPress Version

| Version | Status |
|---------|--------|
| WordPress 5.0+ | Required |
| WordPress 7.0 | Tested |

## PHP Version

| Version | Status |
|---------|--------|
| PHP 7.4+ | Required |
| PHP 8.x | Compatible |
