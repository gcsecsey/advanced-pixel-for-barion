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

- the [Barion Payment Gateway](https://barion.com/en/plugins/) by Barion, and the [gateway by szelpe](https://github.com/szelpe/woocommerce-barion), which have an optional Pixel ID field
- a [Google Tag Manager tag](https://docs.barion.com/Implementing_the_Barion_Pixel_base_code_through_the_Google_Tag_Manager)
- a snippet pasted into the theme header

**Two base pixels on one page do not degrade tracking, they end it.** Each copy of
`bp.js` appends its own iframe under `id="barion_receiver"`, `getElementById()`
returns only the first, and the second copy posts its events into that first
iframe before it has fetched the visitor's consent status. `bp.js` throws there
(`Cannot read properties of undefined (reading 'approvedBase')`) and the event is
never sent. Measured on a live shop: three throws and zero events on a product
page.

### What the plugin does about it

**The Barion Payment Gateway.** Its pixel prints from `wp_head` at priority
999999 whenever its Pixel ID field is filled — whatever its own tracking setting
says, and even with the gateway itself switched off. That is after everything
this plugin can enqueue, so no JavaScript check can see it. This plugin therefore
applies the gateway's own `woocommerce_barion_disable_tracking` filter and serves
the Base Pixel itself, but only while a Pixel ID is configured here. That filter
has no other consumer in the gateway, and the gateway implements the Base Pixel
and nothing beyond it, so nothing is lost. A site that wants the gateway to keep
the pixel can `remove_filter()` it and clear the Pixel ID here instead.

**Everything else.** Before loading `bp.js` the plugin checks `window.bp`. If any
other source defined it first, the script load is skipped and only the `init`
call goes out. In debug mode this logs `[Barion Pixel] bp.js already loaded by
another plugin`.

A snippet that runs *after* this plugin — a Google Tag Manager tag, a snippet in
the theme header — is out of reach: it loads `bp.js` again whatever this plugin
does. Debug mode catches that case after the page loads and warns that something
else loads a second copy of `bp.js`.

**Recommendation:** keep the Pixel ID in one place, here. Clear the gateway's
field and remove any Google Tag Manager tag or theme snippet. Two different Pixel
IDs on one page is the case worth avoiding — the plugin can suppress a duplicate
script, but not a duplicate identity.

When the Barion Payment Gateway also has a Pixel ID configured, the settings page
says so. Both plugins keep working either way: that one handles payments, this
one handles tracking.

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
