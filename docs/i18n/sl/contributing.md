> 🌐 To je samodejni prevod. Popravki skupnosti so dobrodošli!
>
> [English version](../../../CONTRIBUTING.md)

# Prispevki

Razvoj poteka tu na GitHubu. Vtičnik je objavljen tudi na
[WordPress.org](https://wordpress.org/plugins/advanced-pixel-for-barion/), vendar je tista kopija le
zrcalo označene izdaje — prijave in pull requeste odpri v tem repozitoriju.

Prispevki so dobrodošli, najkoristnejši pa so prevodi in robni primeri v WooCommerce. Sam lahko
preizkusim le omejen nabor tem, plačilnih prehodov in vtičnikov za soglasje, zato so poročila iz
prakse dragocena.

## Prijava napake

Odpri [prijavo na GitHubu](https://github.com/gcsecsey/advanced-pixel-for-barion/issues). Prosim,
navedi:

- različice WordPressa, WooCommerca, PHP-ja in vtičnika
- kateri vtičnik za soglasje uporabljaš, če ga sploh
- dogodek, ki se obnaša napačno (`pageView`, `contentView`, `addToCart`, `initiateCheckout`,
  `purchase`, `setEncryptedEmail`)
- izpis konzole brskalnika z vklopljenim **načinom za odpravljanje napak** (Nastavitve → Barion
  Pixel). Vtičnik svoja sporočila označuje s predpono `[Barion Pixel]`.

V prijavo ne prilepi svojega pravega Pixel ID-ja ali kupčevega e-poštnega naslova.

## Pull requesti

1. Vejo naredi iz `main`.
2. Naj bo sprememba osredotočena. En popravek ali ena funkcionalnost na pull request.
3. Sledi obstoječemu slogu kode: standardi kodiranja WordPress, ubežno kodiranje vseh izpisov,
   čiščenje vseh vnosov in predpona `wc_barion_pixel_` pri novih globalnih simbolih.
4. Opiši, kako si spremembo preizkusil. `docs/testing-notes.md` navaja posebnosti bp.js, na katerih
   se je lahko spotakniti.
5. Poženi preverjanja, ki jih poganja tudi CI: `composer install`, nato `composer lint` (PHPCS s
   standardi kodiranja WordPressa in združljivostjo s PHP 7.4+), `composer phpstan`, `node --test`
   in `php tests/<datoteka>.php` za vsak PHP test. `composer lint:fix` popravi večino slogovnih
   najdb.
6. Poženi brskalniški komplet za soglasje: `npm install`, `npx playwright install chromium`, nato
   `npm run test:browser`. Zažene WordPress v Playgroundu in preveri, da soglasje pride do Bariona
   ob kliku na sprejem in nikoli ob nalaganju strani — glej
   [`tests/playground/README.md`](../../../tests/playground/README.md).
7. Ne dvigaj številke različice in ne urejaj dnevnika sprememb — izdaje se označujejo posebej.

## Prevodi

Vtičnik svoje prevode prinaša v mapi [`languages/`](../../../languages/). Za dodajanje ali popravek
jezika:

1. Kopiraj `languages/advanced-pixel-for-barion.pot` v
   `languages/advanced-pixel-for-barion-<locale>.po` (na primer `hu_HU`, `de_DE`, `hr`).
2. Prevedi nize. Poedit ali katerikoli urejevalnik PO zadošča.
3. Znova ustvari binarne datoteke in v commit vključi tako `.po` kot `.mo`:

   ```sh
   composer i18n:mo
   ```

Če si v izvorni kodi PHP spremenil prevedljiv niz, najprej znova ustvari predlogo z ukazom
`composer i18n:build`.

## Preizkušanje spremembe

Najhitrejša pot je [WordPress Playground](https://playground.wordpress.net/). Repozitorij vsebuje
blueprint, ki zažene trgovino WooCommerce z vzorčnimi izdelki, demo pasico za soglasje in že
vklopljenim načinom za odpravljanje napak:

```sh
npx @wp-playground/cli server --blueprint=.wordpress-org/blueprints/blueprint.json
```

Blueprint namesti izdano različico z WordPress.org. Če želiš preizkusiti svojo delovno kopijo,
zamenjaj korak `installPlugin` z lokalnim priklopom ali pa vtičnik namesti na poljubno spletno mesto
WordPress in vklopi način za odpravljanje napak.

## Licenca

S prispevkom se strinjaš, da bo tvoje delo licencirano pod GPL-2.0-or-later, torej pod isto licenco
kot vtičnik.
