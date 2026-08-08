> 🌐 Ovo je automatski prevod. Ispravke zajednice su dobrodošle!
>
> [English version](../../events-reference.md)

# Referenca Barion Pixel događaja

Merodavne za značenje pojedinog događaja i za svojstva koja on prihvata su Barionove sopstvene
stranice:

- [Barion Pixel event reference](https://docs.barion.com/Barion-pixel-event-reference) — svaki događaj, svako svojstvo i koja su obavezna
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel) — sami događaji
- [Barion Pixel FAQ](https://docs.barion.com/Frequently_Asked_Questions_about_the_Barion_Pixel) — odgovori na nezgodne slučajeve

Ova stranica opisuje samo šta **ovaj dodatak** šalje i kada.

## Pregled

Dodatak ima dva režima rada:

- **Osnovni piksel** (aktivan čim je postavljen Pixel ID): učitava `bp.js` i automatski šalje `pageView`. Barion ga traži radi sprečavanja prevara i preduslov je za korišćenje Barion Smart Gatewaya uopšte.
- **Potpuno praćenje** (opciono, prekidač u administraciji): dodaje događaje e-trgovine. Barion Metrics ih zahteva, a potpuna implementacija piksela zajedno sa usklađenom trakom za saglasnost je ono što prodavnici otvara povoljnije uslove Smart Gatewaya.

### Sažetak događaja

| Događaj | Režim | Poziv bp() | Okidač |
|---------|-------|------------|--------|
| pageView | Osnovni | Automatski (bp.js) | Svako učitavanje stranice |
| grantConsent | Osnovni | `bp('consent', 'grantConsent')` | Marketinška saglasnost prihvaćena |
| rejectConsent | Osnovni | `bp('consent', 'rejectConsent')` | Marketinška saglasnost odbijena |
| contentView | Potpuni | `bp('track', 'contentView', data)` | Stranica proizvoda |
| addToCart | Potpuni | `bp('track', 'addToCart', data)` | Dodavanje u korpu |
| initiateCheckout | Potpuni | `bp('track', 'initiateCheckout', data)` | Učitavanje stranice naplate |
| purchase | Potpuni | `bp('track', 'purchase', data)` | Stranica potvrde porudžbine |
| setEncryptedEmail | Potpuni | `bp('identity', 'setEncryptedEmail', hash)` | Stranica potvrde porudžbine i unos imejla na naplati |

---

## Polja stavke

`contentView` i svaki element niza `contents` koriste isti oblik:

| Polje | Tip | Vrednost |
|-------|-----|----------|
| contentType | string | `'Product'` |
| currency | string | Valuta prodavnice, kod `purchase` valuta porudžbine |
| id | string | ID proizvoda |
| name | string | Prikazani naziv proizvoda |
| quantity | int | Vidi pojedini događaj |
| unit | string | `'pcs'` |
| unitPrice | float | Vidi pojedini događaj |
| totalItemPrice | float | `unitPrice * quantity` |

Dva izuzetka od te tabele:

- **`contentView` ne šalje `totalItemPrice`.** bp.js ga odbija uz `Invalid key totalItemPrice in contentView event`, a ni Barionova referenca ga ne navodi kao svojstvo contentViewa. Unutar elemenata `contents` je pak obavezan — vidi [Beleške o testiranju](testing-notes.md).
- **`quantity` je kod `contentView` uvek `1`**, jer kupac gleda jedan proizvod.

Dodatak ne šalje nijedno opciono svojstvo sadržaja (`brand`, `category`, `description`, `ean`,
`imageUrl`, `variant`) ni svojstvo `list`. U Barionovoj referenci sva su opciona.

**Varijabilni proizvodi.** `contentView` i `addToCart` sa stranice proizvoda javljaju nadređeni
proizvod, jer stranica govori o njemu. Redovi korpe i porudžbine javljaju izabranu varijaciju, jer
nju WooCommerce stavlja u korpu. Barion traži da stavka kroz sve događaje ima isti naziv i
identifikator, pa u prodavnici građenoj na varijacijama isti proizvod može doći do Bariona pod dva
identiteta.

---

## Događaji osnovnog piksela

### pageView

Šalje se automatski čim se `bp.js` učita. Osim Pixel ID-a nema šta da se podesi.

### grantConsent / rejectConsent

Šalju se kada kupac prihvati ili odbije marketinške kolačiće. Barion oba navodi kao obavezna.
Rešavaju se automatski preko WP Consent API-ja ili Cookie Law Infoa, odnosno ručno preko
`window.wcBarionGrantConsent()` / `window.wcBarionRejectConsent()`.

Vidi [Integraciju saglasnosti za kolačiće](cookie-consent.md).

---

## Događaji potpunog praćenja

### contentView

**Okidač:** stranica proizvoda, hook `woocommerce_after_single_product`.

`unitPrice` je trenutna cena proizvoda. Kod varijabilnog proizvoda to je cena koju WooCommerce
prikazuje pre izbora varijacije.

---

### addToCart

**Okidač:** samo dodavanje u korpu. Svi putevi su na strani klijenta, pa događaj preživi keširanje
stranica. Ima ih tri, a koji se koristi zavisi od toga kako prodavnica iscrtava svoje dugmiće:

1. **Klasično AJAX dodavanje u korpu** (stranice prodavnice i arhiva). Osluškuje WooCommerceov jQuery događaj `added_to_cart` i čita atribute dugmeta `data-product_id`, `data-product_name`, `data-product_price` i `data-quantity`.
2. **Klasična stranica proizvoda.** Presreće slanje `form.cart`. Podaci o proizvodu ugrađeni su u podnožje; kod varijabilnog proizvoda `display_price` izabrane varijacije čita se iz WooCommerceovih jQuery podataka `product_variations`.
3. **Blokovske površine** (dugmići bloka Product Collection, blok Cart). One rade na Interactivity API-ju i ne šalju ni jQuery događaj ni upotrebljive podatke, pa dodatak upoređuje korpu iz [Store API-ja](https://developer.woocommerce.com/docs/apis/store-api/) sa poslednjim poznatim stanjem i javlja razliku. Promena količine u bloku Cart ne pokreće `wc-blocks_added_to_cart`, pa se automatski izostavlja.

**Polja događaja:** gornja polja stavke i `step: 1`.

`quantity` je ono što je kupac zaista dodao. `unitPrice` dolazi, zavisno od puta, iz podataka
dugmeta, iz izabrane varijacije ili iz stavke Store API-ja.

---

### initiateCheckout

**Okidač:** učitavanje stranice naplate. Prepoznaje se preko `is_checkout()` uz izuzimanje krajnje
tačke `order-received` — ne preko `woocommerce_before_checkout_form`, jer taj hook blok Checkout
nikada ne pokreće.

| Polje | Tip | Vrednost |
|-------|-----|----------|
| contents | array | Jedna stavka po redu korpe |
| currency | string | Valuta prodavnice |
| revenue | float | Međuzbir korpe + porez |
| step | int | `1` |

Dostava je iz `revenue` namerno izostavljena: na početku naplate kupac obično još nije izabrao
način dostave, pa WooCommerce nema šta da doda.

---

### purchase

**Okidač:** stranica potvrde porudžbine, hook `woocommerce_thankyou`.

| Polje | Tip | Vrednost |
|-------|-----|----------|
| contents | array | Jedna stavka po redu porudžbine |
| currency | string | Valuta porudžbine |
| revenue | float | Ukupan iznos porudžbine, sa dostavom, porezom i popustima |
| step | int | `1` |

`unitPrice` je ovde `(item_total + item_tax) / quantity`, pa odražava kupone i druge popuste. Zato
prihodi iz `purchase` i `initiateCheckout` nisu uporedivi red po red.

**Sprečavanje duplikata:** porudžbina dobija meta oznaku `_wc_barion_tracked`, pa ponovno
učitavanje stranice potvrde ne šalje drugi `purchase`.

**Poznato odstupanje.** Barion traži `purchase` kada je plaćanje zaista uspelo, a `purchase` sa
`step: -1` kada nije. Dodatak šalje `purchase` sa `step: 1` kad god kupac dođe na stranicu potvrde
porudžbine — što se kod oflajn načina plaćanja, poput bankovnog prenosa ili pouzeća, dešava dok je
porudžbina još neplaćena. `step: -1` nikada ne šalje.

---

### setEncryptedEmail

**Poziv bp():** `bp('identity', 'setEncryptedEmail', hash)`

**Okidači:**

- Stranica potvrde porudžbine, ako porudžbina ima imejl adresu za naplatu.
- Stranica naplate, jednom pri učitavanju za prijavljene kupce.
- Stranica naplate, kad god kupac unese drugu važeću adresu za naplatu — iz polja `#billing_email` na klasičnoj naplati ili iz skladišta podataka blokova Cart i Checkout na blokovskoj naplati.

Adresa se pretvara u mala slova i u pregledaču hešira algoritmom SHA-1 (Web Crypto API) pre nego
što stigne do `bp.js`. Barion umesto obične adrese prihvata unapred izračunat SHA-1 heš, a
prethodno heširanje zaobilazi sopstveni regularni izraz bp.js-a koji odbija `+` u lokalnom delu i
TLD-ove duže od četiri slova. Vrednost koja je već 40-znakovni heksadecimalni heš prosleđuje se
nepromenjena. Ako Web Crypto API nije dostupan — van HTTPS-a — šalje se obična adresa.

Vrednosti koje nisu ni važeća imejl adresa (prema
[HTML5 specifikaciji](https://html.spec.whatwg.org/multipage/input.html#valid-e-mail-address)) ni
SHA-1 heš nikad se ne šalju, pa delimično kucanje na naplati ne dolazi do `bp.js`. Ponovljena
vrednost ne radi ništa.

---

## Događaji koje dodatak ne šalje

Barionova referenca događaja navodi ih među **obaveznim** rukovaocima događaja. FAQ dodaje da
događaj kome u tvojoj prodavnici ne odgovara nijedna korisnička namera nije potrebno
implementirati — to pokriva neke od njih, ali ne sve.

| Događaj | Zašto ne |
|---------|----------|
| `initiatePurchase` | Ovde suvišan. Barion traži `initiatePurchase` *ili* `purchase`; dodatak šalje `purchase` |
| `setEncryptedPhone` | Telefon za naplatu je u WooCommerce-u opcion i u mnogim prodavnicama ga nema |
| `search`, `categorySelection`, `addPaymentInfo`, `removeFromCart` | Primenljivi na tipičnu WooCommerce prodavnicu, ali još nisu implementirani |

Preporučeni rukovaoci — `customizeProduct`, `setUserProperties`, `signUp`, `clickPromo`,
`clickProduct`, `clickProductDetail`, `error` — kao ni `customEvent` takođe nisu implementirani.

Ako tvojoj prodavnici neki od njih treba, osnovni piksel ostavlja `bp()` na objektu `window`, pa
`bp('track', 'search', { ... })` radi iz tvoje sopstvene teme ili dodatka.
