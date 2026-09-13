> 🌐 Ovo je automatski prijevod. Ispravci zajednice su dobrodošli!
>
> [English version](../../events-reference.md)

# Referenca Barion Pixel događaja

Mjerodavne za značenje pojedinog događaja i za svojstva koja on prihvaća su Barionove vlastite
stranice:

- [Barion Pixel event reference](https://docs.barion.com/Barion-pixel-event-reference) — svaki događaj, svako svojstvo i koja su obavezna
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel) — sami događaji
- [Barion Pixel FAQ](https://docs.barion.com/Frequently_Asked_Questions_about_the_Barion_Pixel) — odgovori na nezgodne slučajeve

Ova stranica opisuje samo što **ovaj dodatak** šalje i kada.

## Pregled

Dodatak ima dva načina rada:

- **Osnovni Pixel** (aktivan čim je postavljen Pixel ID): učitava `bp.js` i automatski šalje `pageView`. Barion ga traži radi sprječavanja prijevara i preduvjet je za korištenje Barion Smart Gatewaya uopće.
- **Potpuno praćenje** (neobavezno, prekidač u administraciji): dodaje e-trgovinske događaje. Barion Metrics ih treba, a potpuna implementacija Pixela zajedno s usklađenom trakom za pristanak je ono što trgovini otvara povoljnije uvjete Smart Gatewaya.

### Sažetak događaja

| Događaj | Način | Poziv bp() | Okidač |
|---------|-------|------------|--------|
| pageView | Osnovni | Automatski (bp.js) | Svako učitavanje stranice |
| grantConsent | Osnovni | `bp('consent', 'grantConsent')` | Marketinški pristanak prihvaćen |
| rejectConsent | Osnovni | `bp('consent', 'rejectConsent')` | Marketinški pristanak odbijen |
| contentView | Potpuni | `bp('track', 'contentView', data)` | Stranica proizvoda |
| addToCart | Potpuni | `bp('track', 'addToCart', data)` | Dodavanje u košaricu |
| initiateCheckout | Potpuni | `bp('track', 'initiateCheckout', data)` | Učitavanje stranice naplate |
| purchase | Potpuni | `bp('track', 'purchase', data)` | Stranica potvrde narudžbe |
| setEncryptedEmail | Potpuni | `bp('identity', 'setEncryptedEmail', hash)` | Stranica potvrde narudžbe i unos e-pošte na naplati |

---

## Polja stavke

`contentView` i svaki element polja `contents` koriste isti oblik:

| Polje | Tip | Vrijednost |
|-------|-----|------------|
| contentType | string | `'Product'` |
| currency | string | Valuta trgovine, kod `purchase` valuta narudžbe |
| id | string | ID proizvoda |
| name | string | Prikazani naziv proizvoda |
| quantity | int | Vidi pojedini događaj |
| unit | string | `'pcs'` |
| unitPrice | float | Vidi pojedini događaj |
| totalItemPrice | float | `unitPrice * quantity` |

Dvije iznimke od te tablice:

- **`contentView` ne šalje `totalItemPrice`.** bp.js ga odbija uz `Invalid key totalItemPrice in contentView event`, a ni Barionova referenca ga ne navodi kao svojstvo contentViewa. Unutar elemenata `contents` je pak obavezan — vidi [Napomene za testiranje](testing-notes.md).
- **`quantity` je kod `contentView` uvijek `1`**, jer kupac gleda jedan proizvod.

Dodatak ne šalje nijedno neobavezno svojstvo sadržaja (`brand`, `category`, `description`, `ean`,
`imageUrl`, `variant`) ni svojstvo `list`. U Barionovoj referenci svi su neobavezni.

**Varijabilni proizvodi.** `contentView` i `addToCart` sa stranice proizvoda javljaju nadređeni
proizvod, jer stranica govori o njemu. Retci košarice i narudžbe javljaju odabranu varijaciju, jer
nju WooCommerce stavlja u košaricu. Barion traži da stavka kroz sve događaje ima isti naziv i
identifikator, pa u trgovini građenoj na varijacijama isti proizvod može doći do Bariona pod dva
identiteta.

---

## Događaji osnovnog Pixela

### pageView

Šalje se automatski čim se `bp.js` učita. Osim Pixel ID-a nema se što podesiti.

### grantConsent / rejectConsent

Šalju se kada kupac prihvati ili odbije marketinške kolačiće. Barion oba navodi kao obavezna.
Rješavaju se automatski preko WP Consent API-ja ili Cookie Law Infoa, odnosno ručno preko
`window.wcBarionGrantConsent()` / `window.wcBarionRejectConsent()`.

Vidi [Integraciju pristanka na kolačiće](cookie-consent.md).

---

## Događaji potpunog praćenja

### contentView

**Okidač:** stranica proizvoda, hook `woocommerce_after_single_product`.

`unitPrice` je trenutna cijena proizvoda. Kod varijabilnog proizvoda to je cijena koju WooCommerce
prikazuje prije odabira varijacije.

---

### addToCart

**Okidač:** samo dodavanje u košaricu. Svi su putevi na strani klijenta, pa događaj preživi
predmemoriranje stranica. Ima ih tri, a koji se koristi ovisi o tome kako trgovina iscrtava svoje
gumbe:

1. **Klasično AJAX dodavanje u košaricu** (stranice trgovine i arhiva). Osluškuje WooCommerceov jQuery događaj `added_to_cart`. Gumb daje proizvod i količinu preko `data-product_id` i `data-quantity`. Cijenu **ne nosi** — WooCommerce ne prikazuje `data-product_price` — pa cijena dolazi iz stavke [Store API-ja](https://developer.woocommerce.com/docs/apis/store-api/) koju je dodavanje upravo stvorilo.
2. **Klasična stranica proizvoda.** Presreće slanje `form.cart`. Podaci o proizvodu ugrađeni su u podnožje; kod varijabilnog proizvoda `display_price` odabrane varijacije čita se iz WooCommerceovih jQuery podataka `product_variations`.
3. **Blokovske plohe** (gumbi bloka Product Collection, blok Cart). One rade na Interactivity API-ju i ne šalju ni jQuery događaj ni upotrebljive podatke, pa dodatak uspoređuje košaricu iz [Store API-ja](https://developer.woocommerce.com/docs/apis/store-api/) s posljednjim poznatim stanjem i javlja razliku. Promjena količine u bloku Cart ne pokreće `wc-blocks_added_to_cart`, pa se automatski izostavlja.

**Polja događaja:** gornja polja stavke te `step: 1`.

`quantity` je ono što je kupac stvarno dodao. `unitPrice` dolazi iz stavke Store API-ja i kod
klasičnog AJAX-a i kod blokovskih površina, a iz odabrane varijacije na stranici proizvoda — nikada
iz oznaka gumba, koje je ne nose.

---

### initiateCheckout

**Okidač:** učitavanje stranice naplate. Prepoznaje se preko `is_checkout()` uz izuzimanje krajnje
točke `order-received` — ne preko `woocommerce_before_checkout_form`, jer taj hook blok Checkout
nikada ne pokreće.

| Polje | Tip | Vrijednost |
|-------|-----|------------|
| contents | array | Jedna stavka po retku košarice |
| currency | string | Valuta trgovine |
| revenue | float | Međuzbroj košarice + porez |
| step | int | `1` |

Dostava je iz `revenue` namjerno izostavljena: na početku naplate kupac obično još nije odabrao
način dostave, pa WooCommerce nema što dodati.

---

### purchase

**Okidač:** stranica potvrde narudžbe, hook `woocommerce_thankyou`.

| Polje | Tip | Vrijednost |
|-------|-----|------------|
| contents | array | Jedna stavka po retku narudžbe |
| currency | string | Valuta narudžbe |
| revenue | float | Ukupan iznos narudžbe, s dostavom, porezom i popustima |
| step | int | `1` |

`unitPrice` je ovdje `(item_total + item_tax) / quantity`, pa odražava kupone i druge popuste. Zato
prihodi iz `purchase` i `initiateCheckout` nisu usporedivi redak po redak.

**Sprječavanje duplikata:** narudžba dobiva meta oznaku `_wc_barion_tracked`, pa ponovno učitavanje
stranice potvrde ne šalje drugi `purchase`.

**Poznato odstupanje.** Barion traži `purchase` kada je plaćanje doista uspjelo, a `purchase` sa
`step: -1` kada nije. Dodatak šalje `purchase` sa `step: 1` kad god kupac dođe na stranicu potvrde
narudžbe — što se kod izvanmrežnih načina plaćanja, poput bankovnog prijenosa ili pouzeća, događa
dok je narudžba još neplaćena. `step: -1` nikada ne šalje.

---

### setEncryptedEmail

**Poziv bp():** `bp('identity', 'setEncryptedEmail', hash)`

**Okidači:**

- Stranica potvrde narudžbe, ako narudžba ima adresu e-pošte za naplatu.
- Stranica naplate, jednom pri učitavanju za prijavljene kupce.
- Stranica naplate, kad god kupac unese drugu valjanu adresu za naplatu — iz polja `#billing_email` na klasičnoj naplati ili iz spremišta podataka blokova Cart i Checkout na blokovskoj naplati.

Adresa se pretvara u mala slova i u pregledniku hashira algoritmom SHA-1 (Web Crypto API) prije
nego stigne do `bp.js`. Barion umjesto obične adrese prihvaća unaprijed izračunati SHA-1 hash, a
prethodno hashiranje zaobilazi vlastiti regularni izraz bp.js-a koji odbija `+` u lokalnom dijelu i
TLD-ove duže od četiri slova. Vrijednost koja je već 40-znakovni heksadecimalni hash prosljeđuje se
nepromijenjena. Ako Web Crypto API nije dostupan — izvan HTTPS-a — šalje se obična adresa.

Vrijednosti koje nisu ni valjana adresa e-pošte (prema
[HTML5 specifikaciji](https://html.spec.whatwg.org/multipage/input.html#valid-e-mail-address)) ni
SHA-1 hash nikad se ne šalju, pa djelomično tipkanje na naplati ne dolazi do `bp.js`. Ponovljena
vrijednost ne radi ništa.

---

## Događaji koje dodatak ne šalje

Barionova referenca događaja navodi ih među **obaveznim** rukovateljima događaja. FAQ dodaje da
događaj kojemu u tvojoj trgovini ne odgovara nijedna korisnička namjera nije potrebno
implementirati — to pokriva neke od njih, ali ne sve.

| Događaj | Zašto ne |
|---------|----------|
| `initiatePurchase` | Ovdje suvišan. Barion traži `initiatePurchase` *ili* `purchase`; dodatak šalje `purchase` |
| `setEncryptedPhone` | Telefon za naplatu u WooCommerceu je neobavezan i u mnogim trgovinama ga nema |
| `search`, `categorySelection`, `addPaymentInfo`, `removeFromCart` | Primjenjivi na tipičnu WooCommerce trgovinu, ali još nisu implementirani |

Preporučeni rukovatelji — `customizeProduct`, `setUserProperties`, `signUp`, `clickPromo`,
`clickProduct`, `clickProductDetail`, `error` — kao ni `customEvent` također nisu implementirani.

Ako tvojoj trgovini neki od njih treba, osnovni pixel ostavlja `bp()` na objektu `window`, pa
`bp('track', 'search', { ... })` radi iz tvoje vlastite teme ili dodatka.
