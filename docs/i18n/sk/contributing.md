> 🌐 Toto je automatický preklad. Opravy od komunity sú vítané!
>
> [English version](../../../CONTRIBUTING.md)

# Prispievanie

Vývoj prebieha tu na GitHube. Plugin je publikovaný aj na
[WordPress.org](https://wordpress.org/plugins/advanced-pixel-for-barion/), ale tá kópia je len
zrkadlo označeného vydania — issues a pull requesty zakladaj prosím v tomto repozitári.

Príspevky sú vítané, najužitočnejšie sú preklady a okrajové prípady vo WooCommerce. Sám dokážem
testovať len obmedzenú sadu šablón, platobných brán a pluginov pre súhlas, takže hlásenia z praxe
majú veľkú cenu.

## Hlásenie chyby

Založ [issue na GitHube](https://github.com/gcsecsey/advanced-pixel-for-barion/issues). Uveď
prosím:

- verzie WordPressu, WooCommerce, PHP a pluginu
- ktorý plugin pre súhlas s cookies používaš, ak nejaký
- udalosť, ktorá sa správa zle (`pageView`, `contentView`, `addToCart`, `initiateCheckout`,
  `purchase`, `setEncryptedEmail`)
- výstup konzoly prehliadača so zapnutým **režimom ladenia** (Nastavenia → Barion Pixel). Plugin
  svoje správy uvádza predponou `[Barion Pixel]`.

Nevkladaj do issue svoje skutočné Pixel ID ani e-mailovú adresu zákazníka.

## Pull requesty

1. Vychádzaj z vetvy `main`.
2. Drž zmenu úzko zameranú. Jedna oprava alebo jedna funkcia na pull request.
3. Dodržuj existujúci štýl kódu: kódovacie štandardy WordPressu, escapovanie všetkého výstupu,
   sanitizácia všetkého vstupu a predpona `wc_barion_pixel_` pri nových globálnych symboloch.
4. Popíš, ako si zmenu otestoval. `docs/testing-notes.md` uvádza zvláštnosti bp.js, na ktorých je
   ľahké sa popáliť.
5. Spusti kontroly, ktoré spúšťa aj CI: `composer install`, potom `composer lint` (PHPCS so
   štandardmi WordPressu a kompatibilitou s PHP 7.4+), `composer phpstan`, `node --test` a
   `php tests/<súbor>.php` pre každý PHP test. `composer lint:fix` opraví väčšinu štýlových
   nálezov.
6. Spusti prehliadačovú sadu: `npm install`, `npx playwright install --with-deps chromium`, potom
   `npm run test:browser`. Naštartuje WordPress v Playgrounde a overí dve veci, na ktoré jednotkový
   test nedosiahne: že súhlas dorazí do Barionu pri kliknutí na prijatie a nikdy pri načítaní
   stránky, a že každé e-commerce dáta nesú kľúče, ktoré bp.js vyžaduje — pozri
   [`tests/playground/README.md`](../../../tests/playground/README.md).
7. Nezvyšuj číslo verzie a neupravuj changelog — vydania sa označujú zvlášť.

## Preklady

Plugin dodáva vlastné preklady v adresári [`languages/`](../../../languages/). Pridanie alebo
oprava jazyka:

1. Skopíruj `languages/advanced-pixel-for-barion.pot` na
   `languages/advanced-pixel-for-barion-<locale>.po` (napríklad `hu_HU`, `de_DE`, `hr`).
2. Prelož reťazce. Poedit alebo akýkoľvek PO editor postačí.
3. Vygeneruj binárne súbory a commitni `.po` aj `.mo`:

   ```sh
   composer i18n:mo
   ```

Ak si zmenil prekladaný reťazec v zdrojovom PHP kóde, vygeneruj najprv šablónu príkazom
`composer i18n:build`.

## Testovanie zmeny

Otvor pull request a použi na ňom tlačidlo **Preview in WordPress Playground**. Naštartuje
WooCommerce obchod s tvojou vetvou, nainštalovanou testovacou sadou a zapnutým režimom ladenia a
otvorí sa na prehľade všetkých scenárov. Pripoj `?barion-panel=1` k ľubovoľnej stránke obchodu a
uvidíš živý panel s každým volaním pixela a jeho časom.

Pull request z forku tlačidlo nedostane, pretože doň workflow nesmie zapisovať. Spusti tú istú sadu
lokálne — pozri [`tests/playground/README.md`](../../../tests/playground/README.md), čo je zároveň
spôsob, ako to urobiť bez otvorenia pull requestu.

Pre vydanú verziu namiesto vetvy:

```sh
npx @wp-playground/cli server --blueprint=.wordpress-org/blueprints/blueprint.json
```

## Licencia

Prispením súhlasíš s tým, že tvoja práca bude licencovaná pod GPL-2.0-or-later, teda rovnakou
licenciou ako plugin.
