> 🌐 Dies ist eine automatische Übersetzung. Korrekturen aus der Community sind willkommen!
>
> [English version](../../../CONTRIBUTING.md)

# Mitwirken

Die Entwicklung findet hier auf GitHub statt. Das Plugin wird auch auf
[WordPress.org](https://wordpress.org/plugins/advanced-pixel-for-barion/) veröffentlicht, aber diese
Kopie ist nur ein Spiegel eines getaggten Releases — bitte eröffne Issues und Pull Requests in
diesem Repository.

Beiträge sind willkommen; am nützlichsten sind Übersetzungen und WooCommerce-Sonderfälle. Ich kann
nur eine begrenzte Auswahl an Themes, Zahlungs-Gateways und Consent-Plugins testen, deshalb sind
Rückmeldungen aus der Praxis wertvoll.

## Einen Fehler melden

Eröffne ein [GitHub-Issue](https://github.com/gcsecsey/advanced-pixel-for-barion/issues). Bitte gib
an:

- WordPress-, WooCommerce-, PHP- und Plugin-Version
- welches Consent-Plugin du verwendest, falls überhaupt
- das Event, das sich falsch verhält (`pageView`, `contentView`, `addToCart`, `initiateCheckout`,
  `purchase`, `setEncryptedEmail`)
- die Ausgabe der Browser-Konsole mit aktiviertem **Debug-Modus** (Einstellungen → Barion Pixel).
  Das Plugin stellt seinen Meldungen `[Barion Pixel]` voran.

Füge weder deine echte Pixel-ID noch die E-Mail-Adresse eines Kunden in ein Issue ein.

## Pull Requests

1. Zweige von `main` ab.
2. Halte die Änderung fokussiert. Eine Korrektur oder ein Feature pro Pull Request.
3. Folge dem bestehenden Code-Stil: WordPress Coding Standards, jede Ausgabe escapen, jede Eingabe
   sanitizen und neue Globals mit `wc_barion_pixel_` präfixen.
4. Beschreibe, wie du die Änderung getestet hast. `docs/testing-notes.md` listet die
   bp.js-Eigenheiten auf, über die man leicht stolpert.
5. Erhöhe weder die Versionsnummer noch bearbeite das Änderungsprotokoll — Releases werden separat
   getaggt.

## Übersetzungen

Das Plugin liefert seine Übersetzungen in [`languages/`](../../../languages/) mit. Um eine Sprache
hinzuzufügen oder zu korrigieren:

1. Kopiere `languages/advanced-pixel-for-barion.pot` nach
   `languages/advanced-pixel-for-barion-<locale>.po` (zum Beispiel `hu_HU`, `de_DE`, `hr`).
2. Übersetze die Strings. Poedit oder ein beliebiger PO-Editor genügt.
3. Erzeuge die Binärdateien neu und committe sowohl `.po` als auch `.mo`:

   ```sh
   composer i18n:mo
   ```

Wenn du einen übersetzbaren String im PHP-Quelltext geändert hast, erzeuge zuerst die Vorlage mit
`composer i18n:build` neu.

## Deine Änderung testen

Am schnellsten geht es mit [WordPress Playground](https://playground.wordpress.net/). Das Repository
enthält einen Blueprint, der einen WooCommerce-Shop mit Beispielprodukten, einem Demo-Consent-Banner
und bereits aktiviertem Debug-Modus startet:

```sh
npx @wp-playground/cli server --blueprint=.wordpress-org/blueprints/blueprint.json
```

Der Blueprint installiert die veröffentlichte Version von WordPress.org. Um stattdessen deine
Arbeitskopie zu testen, ersetze den Schritt `installPlugin` durch ein lokales Mount, oder
installiere das Plugin in einer beliebigen WordPress-Installation und aktiviere den Debug-Modus.

## Lizenz

Mit deinem Beitrag stimmst du zu, dass deine Arbeit unter GPL-2.0-or-later lizenziert wird, also
unter derselben Lizenz wie das Plugin.
