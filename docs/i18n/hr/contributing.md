> 🌐 Ovo je automatski prijevod. Ispravci zajednice su dobrodošli!
>
> [English version](../../../CONTRIBUTING.md)

# Doprinosi

Razvoj se odvija ovdje na GitHubu. Dodatak je objavljen i na
[WordPress.org](https://wordpress.org/plugins/advanced-pixel-for-barion/), ali ta je kopija samo
zrcalo označenog izdanja — prijave i pull requestove otvori u ovom repozitoriju.

Doprinosi su dobrodošli, a najkorisniji su prijevodi i rubni slučajevi u WooCommerceu. Sam mogu
testirati samo ograničen broj tema, platnih pristupnika i dodataka za pristanak, pa su izvještaji
iz stvarne upotrebe vrijedni.

## Prijava greške

Otvori [GitHub prijavu](https://github.com/gcsecsey/advanced-pixel-for-barion/issues). Molim,
navedi:

- verzije WordPressa, WooCommercea, PHP-a i dodatka
- koji dodatak za pristanak koristiš, ako ga koristiš
- događaj koji se pogrešno ponaša (`pageView`, `contentView`, `addToCart`, `initiateCheckout`,
  `purchase`, `setEncryptedEmail`)
- ispis konzole preglednika s uključenim **načinom za otklanjanje pogrešaka** (Postavke → Barion
  Pixel). Dodatak svoje poruke označava prefiksom `[Barion Pixel]`.

Nemoj u prijavu zalijepiti svoj stvarni Pixel ID ni adresu e-pošte kupca.

## Pull requestovi

1. Granaj se iz `main`.
2. Neka promjena bude usredotočena. Jedan ispravak ili jedna značajka po pull requestu.
3. Slijedi postojeći stil koda: WordPressovi standardi kodiranja, escapiranje svih izlaza,
   sanitizacija svih ulaza i prefiks `wc_barion_pixel_` za nove globalne simbole.
4. Opiši kako si testirao promjenu. `docs/testing-notes.md` navodi posebnosti bp.js-a na kojima se
   lako spotaknuti.
5. Pokreni provjere koje pokreće i CI: `composer install`, zatim `composer lint` (PHPCS s
   WordPressovim standardima kodiranja i kompatibilnošću s PHP 7.4+), `composer phpstan`,
   `node --test` i `php tests/<datoteka>.php` za svaki PHP test. `composer lint:fix` popravlja
   većinu stilskih nalaza.
6. Pokreni preglednički komplet za pristanak: `npm install`, `npx playwright install chromium`,
   zatim `npm run test:browser`. Pokreće WordPress u Playgroundu i provjerava da pristanak stiže
   Barionu pri kliku na prihvaćanje, a nikada pri učitavanju stranice — vidi
   [`tests/playground/README.md`](../../../tests/playground/README.md).
7. Nemoj podizati broj verzije ni uređivati dnevnik promjena — izdanja se označavaju zasebno.

## Prijevodi

Dodatak isporučuje vlastite prijevode u mapi [`languages/`](../../../languages/). Za dodavanje ili
ispravak jezika:

1. Kopiraj `languages/advanced-pixel-for-barion.pot` u
   `languages/advanced-pixel-for-barion-<locale>.po` (na primjer `hu_HU`, `de_DE`, `hr`).
2. Prevedi nizove. Poedit ili bilo koji PO uređivač obavlja posao.
3. Ponovno izgradi binarne datoteke i commitaj i `.po` i `.mo`:

   ```sh
   composer i18n:mo
   ```

Ako si u PHP izvoru promijenio prevodivi niz, prvo regeneriraj predložak naredbom
`composer i18n:build`.

## Testiranje promjene

Najbrži je put [WordPress Playground](https://playground.wordpress.net/). Repozitorij sadrži
blueprint koji pokreće WooCommerce trgovinu s primjerima proizvoda, demo trakom za pristanak i već
uključenim načinom za otklanjanje pogrešaka:

```sh
npx @wp-playground/cli server --blueprint=.wordpress-org/blueprints/blueprint.json
```

Blueprint instalira objavljenu verziju s WordPress.orga. Za testiranje vlastite radne kopije
zamijeni korak `installPlugin` lokalnim montiranjem, ili instaliraj dodatak na bilo koju WordPress
stranicu i uključi način za otklanjanje pogrešaka.

## Licenca

Doprinosom pristaješ da tvoj rad bude licenciran pod GPL-2.0-or-later, istom licencom kao i
dodatak.
