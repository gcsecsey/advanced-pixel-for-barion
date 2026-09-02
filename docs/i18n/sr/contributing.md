> 🌐 Ovo je automatski prevod. Ispravke zajednice su dobrodošle!
>
> [English version](../../../CONTRIBUTING.md)

# Doprinosi

Razvoj se odvija ovde na GitHubu. Dodatak je objavljen i na
[WordPress.org](https://wordpress.org/plugins/advanced-pixel-for-barion/), ali je ta kopija samo
ogledalo označenog izdanja — prijave i pull request-ove otvori u ovom repozitorijumu.

Doprinosi su dobrodošli, a najkorisniji su prevodi i granični slučajevi u WooCommerce-u. Sam mogu da
testiram samo ograničen broj tema, platnih prolaza i dodataka za saglasnost, pa su izveštaji iz
stvarne upotrebe dragoceni.

## Prijava greške

Otvori [GitHub prijavu](https://github.com/gcsecsey/advanced-pixel-for-barion/issues). Molim,
navedi:

- verzije WordPress-a, WooCommerce-a, PHP-a i dodatka
- koji dodatak za saglasnost koristiš, ako ga koristiš
- događaj koji se pogrešno ponaša (`pageView`, `contentView`, `addToCart`, `initiateCheckout`,
  `purchase`, `setEncryptedEmail`)
- ispis konzole pregledača sa uključenim **režimom za otklanjanje grešaka** (Podešavanja → Barion
  Pixel). Dodatak svoje poruke označava prefiksom `[Barion Pixel]`.

Nemoj u prijavu da nalepiš svoj stvarni Pixel ID ni imejl adresu kupca.

## Pull request-ovi

1. Granaj se iz `main`.
2. Neka izmena bude usredsređena. Jedna ispravka ili jedna funkcija po pull request-u.
3. Prati postojeći stil koda: WordPress standardi kodiranja, escape-ovanje svih izlaza,
   sanitizacija svih ulaza i prefiks `wc_barion_pixel_` za nove globalne simbole.
4. Opiši kako si testirao izmenu. `docs/testing-notes.md` navodi specifičnosti bp.js-a o koje je
   lako se spotaći.
5. Pokreni provere koje pokreće i CI: `composer install`, zatim `composer lint` (PHPCS sa
   WordPressovim standardima kodiranja i kompatibilnošću sa PHP 7.4+), `composer phpstan`,
   `node --test` i `php tests/<datoteka>.php` za svaki PHP test. `composer lint:fix` popravlja
   većinu stilskih nalaza.
6. Pokreni pregledački komplet: `npm install`, `npx playwright install --with-deps chromium`,
   zatim `npm run test:browser`. Pokreće WordPress u Playgroundu i proverava dve stvari do kojih
   jedinični test ne dopire: da saglasnost stiže Barionu pri kliku na prihvatanje, a nikada pri
   učitavanju stranice, i da svaki e-commerce podatkovni paket nosi ključeve koje bp.js zahteva —
   vidi [`tests/playground/README.md`](../../../tests/playground/README.md).
7. Nemoj da podižeš broj verzije niti da uređuješ evidenciju promena — izdanja se označavaju
   zasebno.

## Prevodi

Dodatak isporučuje sopstvene prevode u fascikli [`languages/`](../../../languages/). Za dodavanje
ili ispravku jezika:

1. Kopiraj `languages/advanced-pixel-for-barion.pot` u
   `languages/advanced-pixel-for-barion-<locale>.po` (na primer `hu_HU`, `de_DE`, `hr`).
2. Prevedi niske. Poedit ili bilo koji PO uređivač obavlja posao.
3. Ponovo izgradi binarne datoteke i u commit uključi i `.po` i `.mo`:

   ```sh
   composer i18n:mo
   ```

Ako si u PHP izvoru promenio prevodivu nisku, prvo regeneriši šablon komandom
`composer i18n:build`.

## Testiranje izmene

Otvori pull request i upotrebi na njemu dugme **Preview in WordPress Playground**. Pokreće
WooCommerce prodavnicu sa tvojom granom, instaliranim test kompletom i uključenim režimom za
otklanjanje grešaka, i otvara se na spisku svih scenarija. Dodaj `?barion-panel=1` bilo kojoj
stranici prodavnice za živu tablu koja prikazuje svaki poziv piksela i kada se dogodio.

Isto lokalno: vidi [`tests/playground/README.md`](../../../tests/playground/README.md).

Za izdatu verziju umesto grane:

```sh
npx @wp-playground/cli server --blueprint=.wordpress-org/blueprints/blueprint.json
```

## Licenca

Doprinosom prihvataš da tvoj rad bude licenciran pod GPL-2.0-or-later, istom licencom kao i
dodatak.
