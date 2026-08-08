> 🌐 Ovo je automatski prevod. Ispravke zajednice su dobrodošle!
>
> [English version](../../events-reference.md)

# Referenca Barion Pixel događaja

## Pregled

Dodatak podržava dva načina rada:

- **Osnovni piksel** (uvek aktivan kada je Pixel ID konfigurisan): Učitava `bp.js` i automatski aktivira `pageView` na svakoj stranici. Koristi se za sprečavanje prevara.
- **Potpuno praćenje** (opcionalno, uključuje se u administraciji): Dodaje praćenje događaja e-trgovine za marketinšku analitiku i niže provizije Bariona.

Barionova sopstvena referenca za ove događaje: [Barion Pixel API reference](https://docs.barion.com/Barion_Pixel_API_reference) i [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel) (na engleskom).

### Pregled događaja

| Događaj | Režim | bp() poziv | Okidač |
|---------|-------|-----------|--------|
| pageView | Osnovni | Automatski (bp.js) | Svako učitavanje stranice |
| grantConsent | Osnovni | `bp('consent', 'grantConsent')` | Saglasnost za kolačiće prihvaćena |
| rejectConsent | Osnovni | `bp('consent', 'rejectConsent')` | Saglasnost za kolačiće odbijena |
| contentView | Potpuni | `bp('track', 'contentView', data)` | Stranica pojedinačnog proizvoda |
| addToCart | Potpuni | `bp('track', 'addToCart', data)` | Radnja dodavanja u korpu |
| initiateCheckout | Potpuni | `bp('track', 'initiateCheckout', data)` | Učitavanje stranice za završetak porudžbine |
| purchase | Potpuni | `bp('track', 'purchase', data)` | Stranica zahvalnice |
| setEncryptedEmail | Potpuni | `bp('identity', 'setEncryptedEmail', hash)` | Stranica zahvalnice i unos e-pošte na naplati |

---

## Događaji osnovnog piksela

### pageView

Aktivira se automatski kada se učita `bp.js`. Nije potrebna konfiguracija osim postavljanja Pixel ID-a.

### grantConsent

Aktivira se kada korisnik prihvati marketinške kolačiće. Automatski se upravlja putem WP Consent API ili Cookie Law Info, ili ručno putem `window.wcBarionGrantConsent()`.

### rejectConsent

Aktivira se kada korisnik odbije marketinške kolačiće. Automatski se upravlja putem WP Consent API ili Cookie Law Info, ili ručno putem `window.wcBarionRejectConsent()`. I `grantConsent` i `rejectConsent` su obavezni prema zahtevima Bariona.

Pogledaj [Integracija saglasnosti za kolačiće](cookie-consent.md) za detalje.

---

## Događaji potpunog praćenja

### contentView

**Okidač:** Stranica pojedinačnog proizvoda (hook `woocommerce_after_single_product`)

**Poslata polja:**

| Polje | Tip | Vrednost |
|-------|-----|---------|
| contentType | string | `'Product'` |
| currency | string | Valuta prodavnice u WooCommerce (npr. `'HUF'`) |
| id | string | ID proizvoda |
| name | string | Prikazano ime proizvoda |
| quantity | int | `1` (uvek — pregled jednog proizvoda) |
| unit | string | `'pcs'` |
| unitPrice | float | Cena proizvoda |

> **Napomena:** `totalItemPrice` nije svojstvo događaja contentView. bp.js ga odbija u realnom vremenu sa porukom "Invalid key totalItemPrice in contentView event", a ni API referenca ga ne navodi za ovaj događaj. Umesto toga je obavezan unutar stavki niza `contents`.

---

### addToCart

**Okidač:** JavaScript na strani klijenta (aktivira se odmah pri radnji dodavanja u korpu)

**Implementacija:** Dva puta, oba upravljana na strani klijenta radi kompatibilnosti sa keširanjem stranica:

1. **AJAX dodavanje u korpu** (stranice prodavnice/arhive): Osluškuje WooCommerce jQuery događaj `added_to_cart`. Čita podatke o proizvodu iz atributa podataka dugmeta `<button>` (`data-product_id`, `data-product_name`, `data-product_price`, `data-quantity`).

2. **Slanje forme na stranici pojedinačnog proizvoda**: Presreće slanje forme `form.cart`. Podaci o proizvodu su ugrađeni kao JSON u podnožju. Za varijabilne proizvode, čita `display_price` odabrane varijacije iz WooCommerce jQuery podataka `product_variations`.

**Poslata polja:**

| Polje | Tip | Vrednost |
|-------|-----|---------|
| contentType | string | `'Product'` |
| currency | string | Valuta prodavnice |
| id | string | ID proizvoda |
| name | string | Ime proizvoda |
| quantity | int | Dodata količina |
| unit | string | `'pcs'` |
| unitPrice | float | Cena po jedinici |
| totalItemPrice | float | `unitPrice * quantity` |
| step | int | `1` |

---

### initiateCheckout

**Okidač:** Učitavanje stranice za završetak porudžbine (hook `woocommerce_before_checkout_form`)

**Poslata polja:**

| Polje | Tip | Vrednost |
|-------|-----|---------|
| contents | array | Niz stavki korpe (pogledaj ispod) |
| currency | string | Valuta prodavnice |
| revenue | float | Međuzbir korpe + porez (bez dostave — možda još nije izračunata) |
| step | int | `1` |

**Polja stavke u sadržaju:**

| Polje | Tip | Vrednost |
|-------|-----|---------|
| contentType | string | `'Product'` |
| currency | string | Valuta prodavnice |
| id | string | ID proizvoda |
| name | string | Ime proizvoda |
| quantity | int | Količina stavke |
| unit | string | `'pcs'` |
| unitPrice | float | Cena po jedinici |
| totalItemPrice | float | `unitPrice * quantity` |

---

### purchase

**Okidač:** Stranica zahvalnice (hook `woocommerce_thankyou`)

**Sprečavanje duplikata:** Koristi post meta `_wc_barion_tracked` da spreči aktiviranje pri ponovnom učitavanju stranice.

**Poslata polja:**

| Polje | Tip | Vrednost |
|-------|-----|---------|
| contents | array | Niz stavki porudžbine (pogledaj ispod) |
| currency | string | Valuta porudžbine |
| revenue | float | Ukupan iznos porudžbine (uključuje dostavu, porez, popuste) |
| step | int | `1` |

**Polja stavke u sadržaju:**

| Polje | Tip | Vrednost |
|-------|-----|---------|
| contentType | string | `'Product'` |
| currency | string | Valuta porudžbine |
| id | string | ID proizvoda |
| name | string | Ime stavke |
| quantity | int | Količina stavke |
| unit | string | `'pcs'` |
| unitPrice | float | `(item_total + item_tax) / quantity` (odražava popuste) |
| totalItemPrice | float | `unitPrice * quantity` |

**Napomena o prihodu:** Događaj `purchase` koristi ukupan iznos porudžbine (uključujući dostavu), dok `initiateCheckout` koristi samo međuzbir + porez (dostava možda nije izračunata na početku završetka porudžbine).

---

### setEncryptedEmail

**Okidač:** Stranica zahvalnice (hook `woocommerce_thankyou`) i stranica naplate — kod prijavljenih korisnika jednom pri učitavanju, a zatim svaki put kada kupac unese drugu važeću adresu e-pošte za naplatu.

**bp() poziv:** `bp('identity', 'setEncryptedEmail', hash)`

Adresa se pretvara u mala slova i hešira algoritmom SHA-1 u pregledaču (Web Crypto API) pre nego što stigne do `bp.js`. Barion API prihvata unapred izračunat SHA-1 heš umesto obične adrese, a prethodno heširanje zaobilazi sopstveni regularni izraz za e-poštu u `bp.js`, koji odbija `+` u lokalnom delu i TLD duže od četiri slova. Vrednost koja je već 40-cifreni heksadecimalni heš prosleđuje se nepromenjena; ako Web Crypto API nije dostupan (kontekst bez HTTPS-a), šalje se obična adresa.

Vrednosti koje nisu ni važeća adresa e-pošte (prema [HTML5 specifikaciji](https://html.spec.whatwg.org/multipage/input.html#valid-e-mail-address)) ni SHA-1 heš nikada se ne šalju, pa delimično kucanje na naplati ne stiže do `bp.js`.

Na stranici zahvalnice aktivira se samo kada porudžbina ima adresu e-pošte za naplatu.

---

## Događaji koji NISU implementirani

| Događaj | Razlog |
|---------|--------|
| `customEvent` | Nije potreban za standardno praćenje e-trgovine |
| `initiatePurchase` | Barionov spisak obaveznih događaja kaže: implementiraj `initiatePurchase` ILI `purchase` — mi koristimo `purchase` |
| `setEncryptedPhone` | Opcionalno; broj telefona nije pouzdano dostupan u svim tokovima WooCommerce |
| `search` | Opcionalno; nije deo obaveznog skupa događaja |
