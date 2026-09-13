# Barion Pixel Events Reference

Barion's own pages are the source of truth for what each event means and which
properties it accepts:

- [Barion Pixel event reference](https://docs.barion.com/Barion-pixel-event-reference) — every event, every property, and which ones are required
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel) — the events themselves
- [Barion Pixel FAQ](https://docs.barion.com/Frequently_Asked_Questions_about_the_Barion_Pixel) — how to answer the awkward cases

This page only describes what **this plugin** sends, and when.

## Overview

The plugin has two operating modes:

- **Base Pixel** (active as soon as a Pixel ID is set): loads `bp.js` and fires `pageView` automatically. Barion requires it for fraud prevention, and it is a precondition for using the Barion Smart Gateway at all.
- **Full Tracking** (optional, toggle in admin): adds the e-commerce events. Barion Metrics needs these, and a Full Pixel implementation combined with a compliant consent banner is what qualifies a shop for better Smart Gateway terms.

### Event summary

| Event | Mode | bp() call | Trigger |
|-------|------|-----------|---------|
| pageView | Base | Automatic (bp.js) | Every page load |
| grantConsent | Base | `bp('consent', 'grantConsent')` | Marketing consent accepted |
| rejectConsent | Base | `bp('consent', 'rejectConsent')` | Marketing consent rejected |
| contentView | Full | `bp('track', 'contentView', data)` | Single product page |
| addToCart | Full | `bp('track', 'addToCart', data)` | Add to cart action |
| initiateCheckout | Full | `bp('track', 'initiateCheckout', data)` | Checkout page load |
| purchase | Full | `bp('track', 'purchase', data)` | Order received page |
| setEncryptedEmail | Full | `bp('identity', 'setEncryptedEmail', hash)` | Order received page, and email entry on checkout |

---

## Item fields

`contentView` and every entry of a `contents` array use the same shape:

| Field | Type | Value |
|-------|------|-------|
| contentType | string | `'Product'` |
| currency | string | Store currency, or the order currency for `purchase` |
| id | string | Product ID |
| name | string | Product display name |
| quantity | int | See the event |
| unit | string | `'pcs'` |
| unitPrice | float | See the event |
| totalItemPrice | float | `unitPrice * quantity` |

Two exceptions to that table:

- **`contentView` sends no `totalItemPrice`.** bp.js rejects it with `Invalid key totalItemPrice in contentView event`, and Barion's reference does not list it as a `contentView` property either. It is required inside `contents` items, though — see [Testing Notes](testing-notes.md).
- **`quantity` is always `1` on `contentView`**, because the customer is looking at one product.

The plugin sends no optional content properties (`brand`, `category`, `description`,
`ean`, `imageUrl`, `variant`) and no `list` property. They are all optional in
Barion's reference.

**Variable products.** `contentView` and the single product page `addToCart`
report the parent product, because that is what the page is about. Cart and order
lines report the chosen variation, because that is what WooCommerce puts in the
cart. Barion asks for one item to be named and identified consistently across
events, so on a store built around variations the same product can reach Barion
under two identities.

---

## Base Pixel events

### pageView

Fires automatically when `bp.js` loads. Nothing to configure beyond the Pixel ID.

### grantConsent / rejectConsent

Fire when the customer accepts or rejects marketing cookies. Barion lists both as
required. They are handled automatically through the WP Consent API or Cookie Law
Info, or manually through `window.wcBarionGrantConsent()` /
`window.wcBarionRejectConsent()`.

See [Cookie Consent Integration](cookie-consent.md).

---

## Full Tracking events

### contentView

**Trigger:** single product page, on the `woocommerce_after_single_product` hook.

`unitPrice` is the product's current price. For a variable product this is the
price WooCommerce displays before a variation is chosen.

---

### addToCart

**Trigger:** the add-to-cart action itself. Every path is client-side so the event
survives page caching. There are three, and which one runs depends on how the
store renders its buttons:

1. **Classic AJAX add to cart** (shop and archive pages). Listens for WooCommerce's jQuery `added_to_cart` event. The button gives the product and the quantity, through `data-product_id` and `data-quantity`. It does **not** carry a price — WooCommerce renders no `data-product_price` — so the price comes from the [Store API](https://developer.woocommerce.com/docs/apis/store-api/) line the add just created.
2. **Classic single product page.** Intercepts the `form.cart` submit. Product data is embedded in the footer; for a variable product the selected variation's `display_price` is read from WooCommerce's jQuery `product_variations` data.
3. **Block surfaces** (Product Collection buttons, Cart block). These run on the Interactivity API and dispatch neither the jQuery event nor a useful payload, so the plugin diffs the [Store API](https://developer.woocommerce.com/docs/apis/store-api/) cart against its last known state and reports whatever was added. Quantity edits inside the Cart block do not fire `wc-blocks_added_to_cart`, so they are excluded automatically.

**Event fields:** the item fields above, plus `step: 1`.

`quantity` is what the customer actually added. `unitPrice` comes from the Store
API line on both classic AJAX and block surfaces, and from the selected variation
on the single product page — never from button markup, which does not carry it.

---

### initiateCheckout

**Trigger:** checkout page load. Detected with `is_checkout()` while excluding the
`order-received` endpoint, rather than through `woocommerce_before_checkout_form`
— the Checkout block never fires that hook.

| Field | Type | Value |
|-------|------|-------|
| contents | array | One item per cart line |
| currency | string | Store currency |
| revenue | float | Cart subtotal + tax |
| step | int | `1` |

Shipping is deliberately left out of `revenue`: at the start of checkout the
customer has usually not chosen a shipping method yet, so WooCommerce has nothing
to add.

---

### purchase

**Trigger:** the order received page, on the `woocommerce_thankyou` hook.

| Field | Type | Value |
|-------|------|-------|
| contents | array | One item per order line |
| currency | string | Order currency |
| revenue | float | Order total, including shipping, tax and discounts |
| step | int | `1` |

`unitPrice` here is `(item_total + item_tax) / quantity`, so it reflects coupons
and other discounts. This is why `purchase` revenue and `initiateCheckout`
revenue are not comparable line by line.

**Duplicate prevention:** the order gets a `_wc_barion_tracked` meta flag, so a
reload of the order received page does not send a second `purchase`.

**Known deviation.** Barion asks for `purchase` when payment has actually
succeeded, and for `purchase` with `step: -1` when payment has failed. The plugin
sends `purchase` with `step: 1` whenever the customer reaches the order received
page, which for offline methods such as bank transfer or cash on delivery happens
while the order is still unpaid. It never sends `step: -1`.

---

### setEncryptedEmail

**bp() call:** `bp('identity', 'setEncryptedEmail', hash)`

**Triggers:**

- Order received page, when the order has a billing email.
- Checkout page, once on load for logged-in customers.
- Checkout page, whenever the customer enters a different valid billing email — from the `#billing_email` field on classic checkout, or from the Cart and Checkout blocks' data store on block checkout.

The address is lowercased and SHA-1 hashed in the browser (Web Crypto API) before
it reaches `bp.js`. Barion accepts a pre-computed SHA-1 hash in place of a plain
address, and pre-hashing sidesteps `bp.js`'s own email regex, which rejects `+` in
the local part and TLDs longer than four letters. A value that is already a
40-character hex hash is passed through unchanged. If the Web Crypto API is
unavailable — a non-HTTPS context — the plain address is sent instead.

Values that are neither a valid email (per the
[HTML5 spec](https://html.spec.whatwg.org/multipage/input.html#valid-e-mail-address))
nor a SHA-1 hash are never sent, so partial typing on checkout does not reach
`bp.js`. Repeat values are a no-op.

---

## Events the plugin does not send

Barion's event reference lists these under **required** event handlers. Its FAQ
adds that an event which matches no user intent in your shop does not need to be
implemented — which covers some of them, but not all.

| Event | Why not |
|-------|---------|
| `initiatePurchase` | Redundant here. Barion asks for `initiatePurchase` *or* `purchase`; the plugin sends `purchase` |
| `setEncryptedPhone` | The billing phone is optional in WooCommerce and absent on many stores |
| `search`, `categorySelection`, `addPaymentInfo`, `removeFromCart` | Applicable to a typical WooCommerce store, but not implemented yet |

The recommended handlers — `customizeProduct`, `setUserProperties`, `signUp`,
`clickPromo`, `clickProduct`, `clickProductDetail`, `error` — and `customEvent`
are not implemented either.

If your shop needs one of these, the base pixel leaves `bp()` on `window`, so
`bp('track', 'search', { ... })` works from your own theme or plugin code.
