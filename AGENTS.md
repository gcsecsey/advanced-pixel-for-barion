# Advanced Pixel for Barion

Single-file WordPress plugin (`advanced-pixel-for-barion.php`) that sends Barion Pixel
e-commerce events from WooCommerce shops. Development happens on GitHub; wordpress.org
only mirrors tagged releases.

## Checks

CI (`.github/workflows/ci.yml`) runs on every PR. Run the same checks locally before
handing off: `composer lint`, `composer phpstan`, `node --test`, and `php tests/<file>.php`
per PHP test. `composer lint:fix` repairs most style findings. CI also fails on a stale
POT file — run `composer i18n:pot` after changing a translatable string.

Tests are dependency-free on purpose: `node:test` for JS, plain PHP scripts with
WordPress stubs in `tests/`. Keep them that way.

## Where to look

- `CONTRIBUTING.md` — PR rules (no version bumps, no changelog edits), translation workflow.
- `docs/events-reference.md` — which pixel event fires on which page, with payloads.
- `docs/testing-notes.md` — bp.js validation quirks and manual test plans; read before
  concluding that tracking is broken.
- `docs/cookie-consent.md` — consent integrations (WP Consent API, Cookie Law Info, manual).
- `docs/compatibility.md` — coexisting with the Barion Payment Gateway and other pixel sources.
- `docs/ci-tooling-research.md` — why each CI tool was chosen, with sources.

## Gotchas

- docs.barion.com returns HTTP 403 to non-browser clients; rely on the notes in `docs/`
  instead of fetching.
- bp.js logging "Testing message" instead of "Sending message" means the pixel awaits
  Barion's authorization, not that the plugin is broken (`docs/testing-notes.md`).
