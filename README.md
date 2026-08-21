<p align="center">
  <img src=".wordpress-org/banner-1544x500.png" alt="Advanced Pixel for Barion" width="100%">
</p>

# Advanced Pixel for Barion

Barion Pixel integration for WooCommerce with full e-commerce event tracking, cookie consent support, and WP Consent API compatibility.

<p align="center">
  <strong>English</strong> |
  <a href="docs/i18n/README.hu.md">Magyar</a> |
  <a href="docs/i18n/README.cs.md">Čeština</a> |
  <a href="docs/i18n/README.sk.md">Slovenčina</a> |
  <a href="docs/i18n/README.de.md">Deutsch</a> |
  <a href="docs/i18n/README.hr.md">Hrvatski</a> |
  <a href="docs/i18n/README.ro.md">Română</a> |
  <a href="docs/i18n/README.sl.md">Slovenščina</a> |
  <a href="docs/i18n/README.sr.md">Srpski</a>
</p>

## Features

- **Base Barion Pixel**: Loads the Barion tracking script site-wide (pageView fires automatically)
- **Full Event Tracking**: All mandatory e-commerce events per Barion documentation
  - `contentView`: Fired on product pages
  - `addToCart`: Fired when items are added to cart (client-side, works with page caching)
  - `initiateCheckout`: Fired when checkout begins
  - `purchase`: Fired on successful order completion (with duplicate prevention)
  - `setEncryptedEmail`: Sends billing email to Barion on purchase (encrypted by bp.js)
- **WP Consent API Integration**: Universal cookie consent support — works with CookieYes, Complianz, Real Cookie Banner, GDPR Cookie Compliance, Cookie Notice, and more
- **Cookie Law Info Fallback**: Direct integration for sites using CookieYes/Cookie Law Info
- **Admin Settings Panel**: Easy configuration through WordPress admin
- **Debug Mode**: Console logging for testing and development
- **bp.js Double-Load Detection**: Safely coexists with other plugins that load bp.js (e.g., Barion Payment Gateway)

## Installation

1. Upload the `advanced-pixel-for-barion` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Settings > Barion Pixel to configure

## Configuration

### Admin Settings

Access the settings page at **Settings > Barion Pixel** in WordPress admin.

#### Pixel ID (Required)
Enter your Barion Pixel ID (format: `BP-0000000000-00`). The Base Pixel will be loaded on all pages once this is set.

Find the ID in your Barion wallet under **Merchant Management > Details**. Each shop has its own, and the sandbox and live environments issue different ones. An ID beginning with `BPT` is not a Pixel ID and will not work.

#### Enable Full Pixel Tracking
Toggle to enable/disable e-commerce event tracking. When disabled, only the Base Pixel loads (pageView for fraud prevention).

Barion asks for a Full Pixel implementation plus a compliant consent banner before a shop qualifies for better Barion Smart Gateway terms or for Barion Metrics. Installing this plugin covers the implementation side; the approval is Barion's to give.

#### Debug Mode
Enable to log all Barion Pixel events to the browser console for testing.

## Documentation

Detailed documentation is available in the [`docs/`](docs/) folder:

- [Events Reference](docs/events-reference.md) — All tracked events, fields, and data types
- [Cookie Consent Integration](docs/cookie-consent.md) — WP Consent API, Cookie Law Info, and manual integration
- [Compatibility](docs/compatibility.md) — WooCommerce, Barion Payment Gateway, caching plugins
- [Testing Notes](docs/testing-notes.md) — bp.js quirks, debug mode, testing checklist

Documentation is also available in [Magyar](docs/i18n/hu/), [Čeština](docs/i18n/cs/), [Slovenčina](docs/i18n/sk/), [Deutsch](docs/i18n/de/), [Hrvatski](docs/i18n/hr/), [Română](docs/i18n/ro/), [Slovenščina](docs/i18n/sl/), and [Srpski](docs/i18n/sr/).

### Barion documentation

Barion's own guides for setting up the pixel. The plugin's **Enable Full Pixel Tracking** option corresponds to Barion's Full (advanced) Barion Pixel:

- [Getting started with the Barion Pixel](https://docs.barion.com/Getting_started_with_the_Barion_Pixel)
- [Implementing the Base Barion Pixel](https://docs.barion.com/Implementing_the_Base_Barion_Pixel)
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel)
- [Implementing the Base and Full pixel in WooCommerce webshops](https://docs.barion.com/Implementing-the-barion-base-and-full-pixel-in-woocommerce-webshops)
- [Barion Pixel event reference](https://docs.barion.com/Barion-pixel-event-reference)
- [Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements)
- [Barion Pixel FAQ](https://docs.barion.com/Frequently_Asked_Questions_about_the_Barion_Pixel)

## Compatibility

- **WooCommerce**: Required for full event tracking (base pixel works without it)
- **Barion Payment Gateway** ([woocommerce-barion](https://github.com/szelpe/woocommerce-barion)): Coexists perfectly — that plugin handles payments, this one handles pixel tracking
- **Page caching**: Fully compatible (addToCart uses client-side JS)
- **Cookie plugins**: Any WP Consent API compatible plugin works automatically

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- WooCommerce 5.0+ (for full event tracking)
- Optional: [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) for universal cookie consent support

## Contributing

Bug reports, pull requests and translations are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md).

## License

GPL-2.0-or-later — see [LICENSE](LICENSE) for details.

## Changelog

### 1.0.7
- Fixed: a fatal error on any site that runs the plugin without WooCommerce, once a Pixel ID was saved and Full Tracking was on. The footer event script called `is_product()`, a function that only exists while WooCommerce is loaded, so the page died with `Call to undefined function is_product()`. The WooCommerce event hooks are now registered only when WooCommerce is active; the base pixel still loads without it, as documented. This dates back to 1.0.0

### 1.0.6
- Fixed: `initiateCheckout` and `setEncryptedEmail` never fired on the WooCommerce Checkout block, which has been the default for new stores since WooCommerce 8.3. The plugin only listened for the classic checkout's PHP hooks and its `#billing_email` field, and the block has neither. It now reads the Cart and Checkout blocks' data store; classic checkout behaviour is unchanged
- Fixed: `addToCart` never fired on shop or category pages, on any store. The events script was only loaded on pages that already had an event queued, which no archive page does, so the add-to-cart listeners were never present where customers actually add to cart. This dates back to 1.0.1
- Fixed: `addToCart` now also works with the block product buttons used by the Product Collection block. These run on the Interactivity API and fire neither the classic jQuery event nor the block data store, so cart contents are read from the WooCommerce Store API

### 1.0.5
- Fixed: the bundled Hungarian, Czech, Slovak, German, Croatian, Romanian, Slovenian and Serbian translations never loaded, so the settings screen stayed in English. WordPress only searches `wp-content/languages/plugins` unless a plugin registers its own directory, and the plugin never did. It now registers `languages/` on `init`

### 1.0.4
- Compatibility: tested against WordPress 7.0 and WooCommerce 11.0
- Changed: `Requires PHP` raised from 7.2 to 7.4. WordPress 7.0 dropped support for PHP 7.2 and 7.3, so 7.2 was no longer a version the plugin could run on

### 1.0.3
- Fixed: `setEncryptedEmail` fired several times on a single checkout page load
- Fixed: bp.js rejected emails with `+` in the local part, or with a TLD longer than four letters (`.museum`, `.online`), with `Format of e-mail address or hash is invalid`. The plugin now SHA-1 hashes the email in the browser before passing it to bp.js — the Barion Pixel API accepts a pre-computed hash in place of a plain address
- Fixed: partial input (for example `x@y`) is no longer forwarded to bp.js
- Fixed: call `bp('identity', 'setEncryptedEmail', ...)` as the Barion documentation specifies (was `'identify'`)

Version 1.0.2 was superseded by 1.0.3 before release; its fixes are listed above.

### 1.0.1
- Fixed: no pixel events were sent at all — the events script was enqueued after `wp_print_footer_scripts` had already run
- Fixed: cookie consent auto-detection now runs after `DOMContentLoaded`, so it can see globals set by consent plugins that load late
- Added: `setEncryptedEmail` also fires on the checkout page — on load for logged-in users, and when a customer enters a valid billing email

### 1.0.0
- Initial release
- Base Barion Pixel (pageView) implementation
- Full event tracking (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail)
- WP Consent API integration
- Cookie Law Info fallback integration
- Admin settings panel with debug mode
- Client-side addToCart (compatible with page caching)
- Variable product support
- Duplicate purchase prevention
- bp.js double-load detection
