# Contributing

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
5. Do not bump the version number or edit the changelog — releases are tagged separately.

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

The quickest way is [WordPress Playground](https://playground.wordpress.net/). The repository
contains a blueprint that boots a WooCommerce store with sample products, a demo consent banner and
debug mode already on:

```sh
npx @wp-playground/cli server --blueprint=.wordpress-org/blueprints/blueprint.json
```

The blueprint installs the released version from WordPress.org. To test your working copy instead,
replace that `installPlugin` step with a local mount, or install the plugin into any WordPress site
and enable Debug Mode.

## License

By contributing you agree that your work is licensed under GPL-2.0-or-later, the same license as
the plugin.
