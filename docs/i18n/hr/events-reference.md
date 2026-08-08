> 🌐 Ovo je automatski prijevod. Ispravci zajednice su dobrodošli!
>
> [English version](../../events-reference.md)

# Referenca Barion Pixel događaja

## Pregled

Dodatak podržava dva načina rada:

- **Osnovni Pixel** (uvijek aktivan kada je konfiguriran Pixel ID): Učitava `bp.js` i automatski pokreće `pageView` na svakoj stranici. Koristi se za sprječavanje prijevare.
- **Potpuno praćenje** (neobavezno, uključuje/isključuje se u administraciji): Dodaje praćenje e-trgovinskih događaja za marketinšku analitiku i niže Barion provizije.

Barionova vlastita referenca za ove događaje: [Barion Pixel API reference](https://docs.barion.com/Barion_Pixel_API_reference) i [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel) (na engleskom).

### Sažetak događaja

| Događaj | Način | bp() poziv | Okidač |
|---------|-------|-----------|--------|
| pageView | Osnovni | Automatski (bp.js) | Svako učitavanje stranice |
| grantConsent | Osnovni | `bp('consent', 'grantConsent')` | Pristanak na kolačiće prihvaćen |
| rejectConsent | Osnovni | `bp('consent', 'rejectConsent')` | Pristanak na kolačiće odbijen |
| contentView | Potpuni | `bp('track', 'contentView', data)` | Stranica jednog proizvoda |
| addToCart | Potpuni | `bp('track', 'addToCart', data)` | Radnja dodavanja u košaricu |
| initiateCheckout | Potpuni | `bp('track', 'initiateCheckout', data)` | Učitavanje stranice naplate |
| purchase | Potpuni | `bp('track', 'purchase', data)` | Stranica zahvale |
| setEncryptedEmail | Potpuni | `bp('identity', 'setEncryptedEmail', hash)` | Stranica zahvale i unos e-pošte na naplati |

---

## Događaji osnovnog Pixela

### pageView

Pokreće se automatski kada se učita `bp.js`. Nije potrebna konfiguracija osim postavljanja Pixel ID-a.

### grantConsent

Pokreće se kada korisnik prihvati marketinške kolačiće. Obrađuje se automatski putem WP Consent API-ja ili Cookie Law Info, ili ručno putem `window.wcBarionGrantConsent()`.

### rejectConsent

Pokreće se kada korisnik odbije marketinške kolačiće. Obrađuje se automatski putem WP Consent API-ja ili Cookie Law Info, ili ručno putem `window.wcBarionRejectConsent()`. I `grantConsent` i `rejectConsent` su obvezni prema Barion zahtjevima.

Pogledaj [Integracija pristanka na kolačiće](cookie-consent.md) za detalje.

---

## Događaji potpunog praćenja

### contentView

**Okidač:** Stranica jednog proizvoda (hook `woocommerce_after_single_product`)

**Poslana polja:**

| Polje | Vrsta | Vrijednost |
|-------|-------|-----------|
| contentType | string | `'Product'` |
| currency | string | Valuta WooCommerce trgovine (npr. `'HUF'`) |
| id | string | ID proizvoda |
| name | string | Naziv za prikaz proizvoda |
| quantity | int | `1` (uvijek — pregledava se jedan proizvod) |
| unit | string | `'pcs'` |
| unitPrice | float | Cijena proizvoda |

> **Napomena:** `totalItemPrice` nije svojstvo događaja contentView. bp.js ga odbija za vrijeme izvođenja s porukom "Invalid key totalItemPrice in contentView event", a API referenca ga za ovaj događaj također ne navodi. Obvezan je umjesto toga unutar stavki niza `contents`.

---

### addToCart

**Okidač:** JavaScript na strani klijenta (pokreće se odmah pri radnji dodavanja u košaricu)

**Implementacija:** Dva puta, oba obrađena na strani klijenta za rad s predmemoriranjem stranica:

1. **AJAX dodavanje u košaricu** (stranice trgovine/arhive): Sluša WooCommerce jQuery događaj `added_to_cart`. Čita podatke o proizvodu iz `<button>` data atributa (`data-product_id`, `data-product_name`, `data-product_price`, `data-quantity`).

2. **Slanje obrasca na stranici jednog proizvoda**: Presreće slanje `form.cart`. Podaci o proizvodu su ugrađeni kao JSON u podnožje. Za varijabilne proizvode, čita `display_price` odabrane varijacije iz WooCommerce jQuery podataka `product_variations`.

**Poslana polja:**

| Polje | Vrsta | Vrijednost |
|-------|-------|-----------|
| contentType | string | `'Product'` |
| currency | string | Valuta trgovine |
| id | string | ID proizvoda |
| name | string | Naziv proizvoda |
| quantity | int | Dodana količina |
| unit | string | `'pcs'` |
| unitPrice | float | Cijena po jedinici |
| totalItemPrice | float | `unitPrice * quantity` |
| step | int | `1` |

---

### initiateCheckout

**Okidač:** Učitavanje stranice naplate (hook `woocommerce_before_checkout_form`)

**Poslana polja:**

| Polje | Vrsta | Vrijednost |
|-------|-------|-----------|
| contents | array | Niz stavki košarice (vidi dolje) |
| currency | string | Valuta trgovine |
| revenue | float | Međuzbroj košarice + porez (bez dostave — možda još nije izračunata) |
| step | int | `1` |

**Polja stavki sadržaja:**

| Polje | Vrsta | Vrijednost |
|-------|-------|-----------|
| contentType | string | `'Product'` |
| currency | string | Valuta trgovine |
| id | string | ID proizvoda |
| name | string | Naziv proizvoda |
| quantity | int | Količina stavke |
| unit | string | `'pcs'` |
| unitPrice | float | Jedinična cijena |
| totalItemPrice | float | `unitPrice * quantity` |

---

### purchase

**Okidač:** Stranica zahvale (hook `woocommerce_thankyou`)

**Prevencija dupliciranja:** Koristi `_wc_barion_tracked` post meta za sprječavanje ponovnog pokretanja pri osvježavanju stranice.

**Poslana polja:**

| Polje | Vrsta | Vrijednost |
|-------|-------|-----------|
| contents | array | Niz stavki narudžbe (vidi dolje) |
| currency | string | Valuta narudžbe |
| revenue | float | Ukupni iznos narudžbe (uključuje dostavu, porez, popuste) |
| step | int | `1` |

**Polja stavki sadržaja:**

| Polje | Vrsta | Vrijednost |
|-------|-------|-----------|
| contentType | string | `'Product'` |
| currency | string | Valuta narudžbe |
| id | string | ID proizvoda |
| name | string | Naziv stavke |
| quantity | int | Količina stavke |
| unit | string | `'pcs'` |
| unitPrice | float | `(item_total + item_tax) / quantity` (odražava popuste) |
| totalItemPrice | float | `unitPrice * quantity` |

**Napomena o prihodu:** Događaj `purchase` koristi puni ukupni iznos narudžbe (uključujući dostavu), dok `initiateCheckout` koristi samo međuzbroj + porez (dostava možda nije izračunata na početku naplate).

---

### setEncryptedEmail

**Okidač:** Stranica zahvale (hook `woocommerce_thankyou`) i stranica naplate — kod prijavljenih korisnika jednom pri učitavanju, a zatim svaki put kada kupac unese drugu valjanu adresu e-pošte za naplatu.

**bp() poziv:** `bp('identity', 'setEncryptedEmail', hash)`

Adresa se pretvara u mala slova i hashira algoritmom SHA-1 u pregledniku (Web Crypto API) prije nego dođe do `bp.js`. Barion API prihvaća unaprijed izračunati SHA-1 hash umjesto obične adrese, a prethodno hashiranje zaobilazi vlastiti regularni izraz za e-poštu u `bp.js`, koji odbija `+` u lokalnom dijelu i TLD-ove dulje od četiri slova. Vrijednost koja je već 40-znamenkasti heksadecimalni hash prosljeđuje se nepromijenjena; ako Web Crypto API nije dostupan (kontekst bez HTTPS-a), šalje se obična adresa.

Vrijednosti koje nisu ni valjana adresa e-pošte (prema [HTML5 specifikaciji](https://html.spec.whatwg.org/multipage/input.html#valid-e-mail-address)) ni SHA-1 hash nikada se ne šalju, pa djelomično tipkanje na naplati ne dolazi do `bp.js`.

Na stranici zahvale pokreće se samo kada narudžba ima adresu e-pošte za naplatu.

---

## Neimplementirani događaji

| Događaj | Razlog |
|---------|--------|
| `customEvent` | Nije potreban za standardno praćenje e-trgovine |
| `initiatePurchase` | Barionov popis obveznih događaja kaže implementiraj `initiatePurchase` ILI `purchase` — koristimo `purchase` |
| `setEncryptedPhone` | Neobavezno; broj telefona nije pouzdano dostupan u svim WooCommerce tokovima |
| `search` | Neobavezno; nije dio obveznog skupa događaja |
