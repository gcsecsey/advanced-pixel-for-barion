> 🌐 Ez egy automatikus fordítás. Közösségi javítások szívesen fogadottak!
>
> [English version](../../../CONTRIBUTING.md)

# Közreműködés

A fejlesztés itt, a GitHubon zajlik. A bővítmény a
[WordPress.org](https://wordpress.org/plugins/advanced-pixel-for-barion/) oldalon is elérhető, de
az a példány csak egy címkézett kiadás tükre — a hibajegyeket és a pull requesteket ebbe a
tárolóba nyisd.

Minden közreműködést szívesen fogadunk, a legtöbbet a fordítások és a WooCommerce-ből adódó
különleges esetek segítenek. Csak korlátozott számú sablont, fizetési átjárót és
hozzájárulás-kezelő bővítményt tudok tesztelni, ezért a valós tapasztalatok sokat érnek.

## Hibabejelentés

Nyiss egy [GitHub hibajegyet](https://github.com/gcsecsey/advanced-pixel-for-barion/issues). Kérlek,
add meg ezeket:

- a WordPress, a WooCommerce, a PHP és a bővítmény verziója
- melyik hozzájárulás-kezelő bővítményt használod, ha használsz ilyet
- melyik esemény hibás (`pageView`, `contentView`, `addToCart`, `initiateCheckout`,
  `purchase`, `setEncryptedEmail`)
- a böngészőkonzol kimenete bekapcsolt **Hibakeresési móddal** (Beállítások → Barion Pixel). A
  bővítmény `[Barion Pixel]` előtaggal írja az üzeneteit.

Ne másold be a valódi Pixel azonosítódat vagy egy vásárló e-mail-címét a hibajegybe.

## Pull requestek

1. A `main` ágból indulj ki.
2. Tartsd fókuszáltan a változtatást. Egy pull request egy hibajavítást vagy egy funkciót
   tartalmazzon.
3. Kövesd a meglévő kódstílust: WordPress kódolási szabványok, minden kimenet escape-elése, minden
   bemenet sanitizálása, és az új globálisok `wc_barion_pixel_` előtaggal.
4. Írd le, hogyan tesztelted a változtatást. A `docs/testing-notes.md` felsorolja azokat a bp.js
   sajátosságokat, amelyekbe könnyű belefutni.
5. Futtasd le azokat az ellenőrzéseket, amiket a CI is futtat: `composer install`, majd
   `composer lint` (PHPCS a WordPress kódolási szabványokkal és PHP 7.4+ kompatibilitással),
   `composer phpstan`, `node --test`, és `php tests/<fájl>.php` minden PHP teszthez.
   A `composer lint:fix` a stílushibák többségét megjavítja.
6. Futtasd le a hozzájárulás böngészős készletét: `npm install`, `npx playwright install --with-deps chromium`,
   majd `npm run test:browser`. Ez WordPresst indít a Playgroundban, és ellenőrzi, hogy a
   hozzájárulás az elfogadás gombra kattintáskor jut el a Barionhoz, és soha nem oldalbetöltéskor —
   lásd [`tests/playground/README.md`](../../../tests/playground/README.md).
7. Ne emeld a verziószámot, és ne szerkeszd a változásnaplót — a kiadásokat külön címkézzük.

## Fordítások

A bővítmény a saját fordításait a [`languages/`](../../../languages/) mappában szállítja. Új nyelv
hozzáadásához vagy egy meglévő javításához:

1. Másold a `languages/advanced-pixel-for-barion.pot` fájlt
   `languages/advanced-pixel-for-barion-<locale>.po` néven (például `hu_HU`, `de_DE`, `hr`).
2. Fordítsd le a szövegeket. A Poedit vagy bármelyik PO-szerkesztő megfelel.
3. Készítsd el a bináris fájlokat, és véglegesítsd a `.po` és a `.mo` fájlt is:

   ```sh
   composer i18n:mo
   ```

Ha a PHP forrásban módosítottál egy fordítható szöveget, előbb a `composer i18n:build` paranccsal
generáld újra a sablont.

## A változtatás tesztelése

A leggyorsabb út a [WordPress Playground](https://playground.wordpress.net/). A tárolóban van egy
blueprint, amely mintatermékekkel, bemutató hozzájárulási sávval és bekapcsolt hibakeresési móddal
indít egy WooCommerce boltot:

```sh
npx @wp-playground/cli server --blueprint=.wordpress-org/blueprints/blueprint.json
```

A blueprint a WordPress.org-on kiadott verziót telepíti. A saját munkapéldányod teszteléséhez
cseréld le az `installPlugin` lépést helyi csatolásra, vagy telepítsd a bővítményt bármelyik
WordPress oldalra, és kapcsold be a Hibakeresési módot.

## Licenc

A közreműködéssel elfogadod, hogy a munkád a bővítménnyel azonos GPL-2.0-or-later licenc alatt
kerül közzétételre.
