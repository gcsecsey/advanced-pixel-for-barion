> 🌐 Aceasta este o traducere automată. Corecturile din partea comunității sunt binevenite!
>
> [English version](../../../CONTRIBUTING.md)

# Contribuții

Dezvoltarea are loc aici, pe GitHub. Plugin-ul este publicat și pe
[WordPress.org](https://wordpress.org/plugins/advanced-pixel-for-barion/), dar acea copie este doar
o oglindă a unei versiuni etichetate — te rugăm să deschizi issue-uri și pull request-uri în acest
depozit.

Contribuțiile sunt binevenite, iar cele mai utile sunt traducerile și cazurile speciale din
WooCommerce. Pot testa doar un set limitat de teme, procesatoare de plăți și plugin-uri de
consimțământ, așa că raportările din utilizarea reală sunt valoroase.

## Raportarea unei erori

Deschide un [issue pe GitHub](https://github.com/gcsecsey/advanced-pixel-for-barion/issues). Te
rugăm să incluzi:

- versiunile de WordPress, WooCommerce, PHP și plugin
- ce plugin de consimțământ folosești, dacă folosești vreunul
- evenimentul care se comportă greșit (`pageView`, `contentView`, `addToCart`, `initiateCheckout`,
  `purchase`, `setEncryptedEmail`)
- ieșirea din consola browserului cu **modul depanare** activat (Setări → Barion Pixel).
  Plugin-ul își prefixează mesajele cu `[Barion Pixel]`.

Nu lipi în issue ID-ul tău real de Pixel sau adresa de e-mail a unui client.

## Pull request-uri

1. Pornește dintr-o ramură bazată pe `main`.
2. Păstrează modificarea concentrată. O remediere sau o funcționalitate per pull request.
3. Respectă stilul de cod existent: standardele de codare WordPress, escapează toate ieșirile,
   sanitizează toate intrările și prefixează globalele noi cu `wc_barion_pixel_`.
4. Descrie cum ai testat modificarea. `docs/testing-notes.md` enumeră particularitățile bp.js de
   care te poți lovi ușor.
5. Rulează verificările pe care le rulează și CI: `composer install`, apoi `composer lint` (PHPCS
   cu standardele de codare WordPress și compatibilitate PHP 7.4+), `composer phpstan`,
   `node --test` și `php tests/<fișier>.php` pentru fiecare test PHP. `composer lint:fix` repară
   majoritatea problemelor de stil.
6. Rulează suita de browser pentru consimțământ: `npm install`, `npx playwright install --with-deps chromium`,
   apoi `npm run test:browser`. Pornește WordPress în Playground și verifică faptul că
   consimțământul ajunge la Barion la clicul pe acceptare și niciodată la încărcarea paginii — vezi
   [`tests/playground/README.md`](../../../tests/playground/README.md).
7. Nu crește numărul de versiune și nu edita jurnalul de modificări — lansările sunt etichetate
   separat.

## Traduceri

Plugin-ul își livrează propriile traduceri în [`languages/`](../../../languages/). Pentru a adăuga
sau corecta o limbă:

1. Copiază `languages/advanced-pixel-for-barion.pot` în
   `languages/advanced-pixel-for-barion-<locale>.po` (de exemplu `hu_HU`, `de_DE`, `hr`).
2. Tradu șirurile. Poedit sau orice editor PO funcționează.
3. Regenerează fișierele binare și include în commit atât `.po`, cât și `.mo`:

   ```sh
   composer i18n:mo
   ```

Dacă ai modificat un șir traductibil în sursa PHP, regenerează mai întâi șablonul cu
`composer i18n:build`.

## Testarea modificării

Cea mai rapidă cale este [WordPress Playground](https://playground.wordpress.net/). Depozitul
conține un blueprint care pornește un magazin WooCommerce cu produse demonstrative, o bară de
consimțământ demo și modul depanare deja activ:

```sh
npx @wp-playground/cli server --blueprint=.wordpress-org/blueprints/blueprint.json
```

Blueprint-ul instalează versiunea publicată pe WordPress.org. Pentru a testa copia ta de lucru,
înlocuiește pasul `installPlugin` cu o montare locală, sau instalează plugin-ul pe orice site
WordPress și activează modul depanare.

## Licență

Contribuind, ești de acord ca munca ta să fie licențiată sub GPL-2.0-or-later, aceeași licență ca a
plugin-ului.
