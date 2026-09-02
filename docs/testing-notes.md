# Testing Notes & Known Quirks

## Before you conclude the pixel is broken

### "Testing message" is not an error

Open the console on a page with the pixel and bp.js will report either
**"Testing message"** or **"Sending message"**. Barion
[documents the difference](https://docs.barion.com/Implementing_the_Base_Barion_Pixel):
a freshly implemented pixel is not yet authorized to send user data, so bp.js
logs "Testing message" and transmits only the event type. It switches to "Sending
message" once Barion authorizes the pixel.

Nothing in the plugin changes this. If your events look correct in the console but
Barion sees no data, the pixel most likely still needs authorizing on Barion's
side — a person at Barion reviews the implementation, so contact them once yours
is complete.

### The Pixel ID has to be the right one

- Find it in your Barion wallet under **Merchant Management > Details**. Every shop, meaning every POSKey, has its own Pixel ID.
- The format is `BP-` + ten characters + `-` + two digits. An ID that starts with `BPT` is not a Pixel ID and will not work.
- Sandbox and live have **different** Pixel IDs. A staging site pointed at a live ID pollutes real data; a live site pointed at a sandbox ID records nothing useful.

If you want a throwaway shop to test against, Barion's
[Creating a shop](https://docs.barion.com/Creating_a_shop) walks through the
sandbox, where shops are approved automatically.

---

## bp.js runtime validation quirks

bp.js validates event data in the browser, and in a few places its rules are
stricter or looser than the
[event reference](https://docs.barion.com/Barion-pixel-event-reference) suggests.
These were found during staging testing.

### totalItemPrice: rejected for contentView, required in contents items

- **contentView** (a flat event): bp.js **rejects** `totalItemPrice` with `Invalid key totalItemPrice in contentView event`. The reference agrees — it is not a contentView property.
- **initiateCheckout** and **purchase** `contents` items: bp.js **requires** it, with `Mandatory key totalItemPrice is missing from contents event` if omitted. The reference agrees here too.

Rule of thumb: `totalItemPrice` is invalid on flat events and required inside
`contents` items.

### unit is required in contents items

Omitting it produces `Mandatory key unit is missing from contents event`.

### step

The plugin sends `step: 1` for `addToCart`, `initiateCheckout` and `purchase`.
Barion documents `1` as the checkout initiation step, and asks for the highest
step number you use on `purchase` — also `1` in a single-step checkout. `step` is
optional for `addToCart`.

---

## Debug mode

Enable it in **Settings > Barion Pixel** to log every event to the browser console.

### What to look for

Open the console (F12 > Console) and look for `[Barion Pixel]` messages:

```
[Barion Pixel] bp.js loaded by Advanced Pixel for Barion
[Barion Pixel] Base pixel initialized with ID: BP-xxxxxxxxxx-xx
[Barion Pixel] Consent manager detected: WP Consent API
[Barion Pixel] Block surfaces detected (cart store: true, product buttons: false)
[Barion Pixel] Event: contentView { contentType: "Product", ... }
[Barion Pixel] Event: addToCart { contentType: "Product", ... }
[Barion Pixel] Event: initiateCheckout { contents: [...], ... }
[Barion Pixel] Event: purchase { contents: [...], ... }
[Barion Pixel] setEncryptedEmail sent
```

The consent messages are listed in full in
[Cookie Consent Integration](cookie-consent.md).

### bp.js errors

bp.js logs its own validation errors. The common ones:

| Error | Meaning | Fix |
|-------|---------|-----|
| `Mandatory key X is missing from Y event` | A required field is not being sent | Check the event data |
| `Invalid key X in Y event` | A field is being sent that bp.js does not expect | Remove the field |
| `Format of e-mail address or hash is invalid` | bp.js rejected the value passed to `setEncryptedEmail` | Since 1.0.3 the plugin pre-hashes the address, so this should no longer appear |

---

## Testing checklist

Run this on both a classic store and a block store — the two use entirely
different code paths for `addToCart`, `initiateCheckout` and `setEncryptedEmail`.

### Product page (contentView)

1. Open any single product page with the console open.
2. `[Barion Pixel] Event: contentView` appears.
3. No bp.js errors about missing or invalid keys.
4. Fields present: `contentType`, `currency`, `id`, `name`, `quantity`, `unit`, `unitPrice` — and no `totalItemPrice`.

### Add to cart (addToCart)

**Shop or archive page, classic AJAX button:**

1. Click "Add to cart" on the shop page.
2. `[Barion Pixel] Event: addToCart` appears, with `totalItemPrice` and `step: 1`.
3. `unitPrice` is the real price, not `0`. The button carries no price, so this comes from the Store API; a `0` means that request failed.

**Single product page, form submit:**

1. Click "Add to cart" and check the event fires before the page navigates.
2. For a variable product: select a variation first, then verify the variation's price was used.

**Block surfaces (Product Collection buttons, Cart block):**

1. `[Barion Pixel] Block surfaces detected …` appears on load.
2. Add a product from a Product Collection block — one `addToCart` fires with the right quantity.
3. Change a quantity in the Cart block — no `addToCart` fires.
4. On a store with a non-decimal currency such as HUF, check `unitPrice` is the real price and not a hundredth of it.

### Checkout page (initiateCheckout)

1. Add items to the cart and open checkout.
2. `[Barion Pixel] Event: initiateCheckout` appears.
3. `contents` items each carry `unit`, `unitPrice` and `totalItemPrice`.
4. `revenue` is subtotal + tax, without shipping.
5. `step: 1` is present.
6. Type a billing email. `setEncryptedEmail sent` appears once per distinct valid address — not on every keystroke, and not for partial input such as `x@y`.
7. Repeat on the Checkout block, where the email comes from the block data store rather than `#billing_email`.

### Order completion (purchase + setEncryptedEmail)

1. Complete a test order — "Bank transfer" is the easiest payment method for this.
2. `[Barion Pixel] Event: purchase` appears, with `revenue` matching the order total.
3. `setEncryptedEmail sent` appears.
4. Reload the order received page — `purchase` does **not** fire again.
5. `contents` items include `unit` and `totalItemPrice`.

### Consent integration

1. Clear all cookies. This matters — the check below only works on a visitor the banner still has to ask.
2. Load any page. `[Barion Pixel] Base pixel initialized` appears — the base pixel loads before any consent decision, by design.
3. Do not touch anything yet. No `grantConsent` may appear. Barion rejects an integration that sends consent at page load.
4. Accept cookies in your banner. `Consent granted (grantConsent)` appears now.
5. Reload. Nothing is sent this time, and the console says consent already stood when the page loaded. bp.js keeps the answer in its own cookie, so Barion already has it.
6. Withdraw consent and check `Consent rejected (rejectConsent)` appears.

---

## Common issues

### Events not firing

- **Pixel ID**: a valid ID has to be saved in Settings > Barion Pixel.
- **Full tracking**: e-commerce events need "Enable Full Pixel Tracking" checked.
- **WooCommerce**: full tracking needs WooCommerce active.
- **Console errors**: an unrelated JavaScript error can stop bp.js from loading.

### Double pixel loading

`[Barion Pixel] bp.js already loaded by another plugin` means something else — the
Barion Payment Gateway, a Google Tag Manager tag, a theme snippet — got there
first. This is harmless: the plugin skips the script load and still initializes
with your Pixel ID. See [Compatibility](compatibility.md).

### Consent not granting

This is the failure Barion rejects a Full Pixel integration for, so check it
first. With Debug Mode on, the console says which case you are in.

- `Consent manager detected: …` but no `grantConsent` after you accept — the manager was found but reports no marketing consent. Check that your banner's marketing or advertising category is the one you accepted.
- `Marketing consent already stood when this page loaded` — nothing is wrong. You are testing as a returning visitor. Clear your cookies and start again at step 1.
- `No consent manager detected` while the WP Consent API plugin is active — the API is installed but your cookie banner does not register with it, so it reports consent as granted for everyone and the plugin ignores it. The settings page says the same. Connect the banner to the API, or call the functions yourself.
- `No consent manager detected` — the plugin found nothing to read. This line appears ten seconds after the page loads, not immediately, because a consent manager served from a CDN can take that long to appear. CookieYes, Complianz, Cookiebot and legacy Cookie Law Info are read directly. For any other banner, install [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) or call `window.wcBarionGrantConsent()` from your banner's accept callback.
- Nothing at all in the console — the base script did not run. A consent plugin that blocks unknown scripts may have blocked it. Barion asks for the base pixel to load regardless of consent, so add it to your blocker's allow list.

The plugin stays silent on a page load where consent has not been given yet.
That is deliberate: `rejectConsent` means the visitor said no, not that they have
not answered.

### purchase fires on an unpaid order

Expected, and documented under
[purchase](events-reference.md#purchase). The plugin tracks the order received
page, which offline payment methods reach before the money arrives.
