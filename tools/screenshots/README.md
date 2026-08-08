# Screenshot harness

Produces the `screenshot-*.png` files in `.wordpress-org/` for the WordPress.org
listing. Not shipped with the plugin — `tools/` is in `.distignore`.

## Why it works this way

The interesting part of this plugin is the event stream, and the only place a
merchant can see that stream is the browser console. Barion has no event
dashboard: verification is reading the console for bp.js's `Testing message`
(pixel implemented, not yet authorised to send user data) or `Sending message`
(Barion has approved it).

Playwright screenshots capture the page viewport only, never the DevTools panel.
So scene 1 — the settings page, which has no console content — is captured
automatically, and the rest are captured by hand from a real browser window.

## Setup

```bash
npm install --prefix tools/screenshots
npx playwright install chromium        # first run only
```

## 1. Start the demo store

From the plugin root, so the working copy is what gets tested:

```bash
npx @wp-playground/cli@latest server \
  --blueprint=tools/screenshots/blueprint.json \
  --mount="$PWD:/wordpress/wp-content/plugins/advanced-pixel-for-barion" \
  --port=9400
```

Boot takes a couple of minutes — it installs WooCommerce and WP Consent API and
imports the sample products. Wait for `Ready!`.

Use `--mount`, not `--auto-mount`: auto-mount names the plugin directory after
the checkout folder, which in a Conductor worktree is a random name.

## 2. Verify the events actually fire

```bash
npm run --prefix tools/screenshots check
```

Runs every scene headless and prints the console lines it saw, plus
`out/console.json` and page-only PNGs in `out/`. If a scene reports
`(nothing Barion-related seen)`, fix that before capturing — don't discover it
while framing a shot.

## 3. Capture

```bash
npm run --prefix tools/screenshots capture
```

Chromium opens with DevTools already on. One-time setup, before pressing Enter
on the first scene:

- Dock DevTools to the **bottom**. Chrome may default to docking right, which
  cuts the page to roughly 845px wide and crops the product grid.
- Drag the DevTools divider down so the console takes about a third of the
  window. The page needs the rest.
- Console tab only. Raise the console font size if the text won't be legible
  when the image is scaled down on the listing page.
- Type `[Barion Pixel]` into the console **filter** box. Without it every shot
  carries two `Failed to execute 'postMessage'` lines that look like plugin
  errors but aren't — see "bp.js on a non-standard port" below.
- Turn on **Preserve log**, or scene 3 loses its `addToCart` line: adding to
  cart submits a form, and the reload clears the console.
- Position the window and leave it there, so all five shots crop alike.

The script drives to each state and waits. Capture the **whole window**
including DevTools with `Cmd+Shift+4` then `Space`, then press Enter to move on.

| Scene | Save as | Shows |
|---|---|---|
| 1 | `screenshot-5.png` | Cookie banner, consent not yet given |
| 2 | `screenshot-1.png` | Settings page — **already written by `check`; skip it** |
| 3 | `screenshot-2.png` | Product page: `contentView`, then `addToCart` |
| 4 | `screenshot-3.png` | Checkout: `initiateCheckout`, `setEncryptedEmail` |
| 5 | `screenshot-4.png` | Order received: `purchase` with contents and revenue |

Scene order is not shot order — consent has to be captured before it's granted,
and the purchase scene has to run last because it empties the cart.

On scene 5, expand the logged `purchase` object in the console before capturing.
The `contents` array and the revenue fields are the point of that shot.

Save into `.wordpress-org/` as `screenshot-N.png`. The numbering has to match the
captions under `== Screenshots ==` in `readme.txt`.

## bp.js on a non-standard port

bp.js loads and runs here — `window.bp` is defined and it fetches
`pixel-status/<pixel-id>` — but it never logs its own `Testing message` /
`Sending message` line, and it emits two `postMessage` origin errors instead.

It builds its iframe handshake origin from the host without the port
(`barion.html?s=http://127.0.0.1`), which can't match a page served from
`http://127.0.0.1:9400`. This is bp.js assuming port 80/443, not a plugin fault,
and it does not happen on a real store.

The practical consequence: these screenshots show the plugin's own
`[Barion Pixel]` debug output, not Barion's Testing/Sending line. The
Testing-versus-Sending distinction belongs in the readme FAQ as text.

## Publishing

Images in `/assets/` on the plugin SVN are not version-scoped, so they appear as
soon as they sync. Captions are not: WordPress.org reads `readme.txt` from the
directory named by `Stable tag`, so caption and FAQ changes only go live with a
new tagged release.
