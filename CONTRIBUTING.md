# Contributing

<p align="center">
  <strong>English</strong> |
  <a href="docs/i18n/hu/contributing.md">Magyar</a> |
  <a href="docs/i18n/cs/contributing.md">Čeština</a> |
  <a href="docs/i18n/sk/contributing.md">Slovenčina</a> |
  <a href="docs/i18n/de/contributing.md">Deutsch</a> |
  <a href="docs/i18n/hr/contributing.md">Hrvatski</a> |
  <a href="docs/i18n/ro/contributing.md">Română</a> |
  <a href="docs/i18n/sl/contributing.md">Slovenščina</a> |
  <a href="docs/i18n/sr/contributing.md">Srpski</a>
</p>

Development happens here on GitHub. The plugin is also published on
[WordPress.org](https://wordpress.org/plugins/advanced-pixel-for-barion/), but that copy is only a
mirror of a tagged release — please open issues and pull requests against this repository.

Contributions are welcome, and translations and WooCommerce edge cases are the most useful ones.
I can only test against a limited set of themes, payment gateways and consent plugins, so
real-world reports are valuable.

## Reporting a bug

Open a [GitHub issue](https://github.com/gcsecsey/advanced-pixel-for-barion/issues). Please include:

- WordPress, WooCommerce, PHP and plugin versions
- Which consent plugin you use, if any
- The event that misbehaves (`pageView`, `contentView`, `addToCart`, `initiateCheckout`,
  `purchase`, `setEncryptedEmail`)
- The browser console output with **Debug Mode** enabled (Settings → Barion Pixel). The plugin
  prefixes its messages with `[Barion Pixel]`.

Do not paste your real Pixel ID or a customer's email address into an issue.

## Pull requests

1. Branch off `main`.
2. Keep the change focused. One fix or one feature per pull request.
3. Follow the existing code style: WordPress coding standards, escape all output, sanitize all
   input, and prefix new globals with `wc_barion_pixel_`.
4. Describe how you tested the change. `docs/testing-notes.md` lists the bp.js quirks that are easy
   to trip over.
5. Run the checks CI will run: `composer install`, then `composer lint` (PHPCS with the WordPress
   Coding Standards and PHP 7.4+ compatibility), `composer phpstan`, `node --test`, and
   `php tests/<file>.php` for each PHP test. `composer lint:fix` repairs most style issues.
6. Run the browser suite: `npm install`, `npx playwright install --with-deps chromium`, then
   `npm run test:browser`. It boots WordPress in Playground and checks two things a unit test
   cannot reach: that consent reaches Barion on the accept click and never at page load, and that
   every e-commerce payload carries the keys bp.js demands — see
   [`tests/playground/README.md`](tests/playground/README.md).
7. Do not bump the version number or edit the changelog — releases are tagged separately.

## Translations

The plugin ships its own translations in [`languages/`](languages/). To add or correct a language:

1. Copy `languages/advanced-pixel-for-barion.pot` to
   `languages/advanced-pixel-for-barion-<locale>.po` (for example `hu_HU`, `de_DE`, `hr`).
2. Translate the strings. Poedit or any PO editor works.
3. Regenerate the binary files and commit both `.po` and `.mo`:

   ```sh
   composer i18n:mo
   ```

If you changed a translatable string in the PHP source, regenerate the template first with
`composer i18n:build`.

## Testing your change

Open your pull request and use the **Preview in WordPress Playground** button on it. That boots a
WooCommerce store running your branch, with the test harness installed and debug mode on, and lands
on an index of every scenario. Add `?barion-panel=1` to any shop page for a live panel showing each
pixel call and when it fired.

A pull request from a fork gets no button, because the workflow may not write to it. Run the same
harness locally instead — see [`tests/playground/README.md`](tests/playground/README.md), which is
also how to do this without opening a pull request at all.

For the released version rather than a branch:

```sh
npx @wp-playground/cli server --blueprint=.wordpress-org/blueprints/blueprint.json
```

## License

By contributing you agree that your work is licensed under GPL-2.0-or-later, the same license as
the plugin.
