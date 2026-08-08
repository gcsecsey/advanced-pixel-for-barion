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
5. Nezvyšuj číslo verzie a neupravuj changelog — vydania sa označujú zvlášť.

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

Najrýchlejšia cesta je [WordPress Playground](https://playground.wordpress.net/). Repozitár
obsahuje blueprint, ktorý naštartuje WooCommerce obchod s ukážkovými produktmi, demo lištou súhlasu
a už zapnutým režimom ladenia:

```sh
npx @wp-playground/cli server --blueprint=.wordpress-org/blueprints/blueprint.json
```

Blueprint inštaluje vydanú verziu z WordPress.org. Ak chceš testovať svoju pracovnú kópiu, nahraď
krok `installPlugin` lokálnym pripojením, alebo plugin nainštaluj na ľubovoľnú stránku WordPress a
zapni režim ladenia.

## Licencia

Prispením súhlasíš s tým, že tvoja práca bude licencovaná pod GPL-2.0-or-later, teda rovnakou
licenciou ako plugin.
