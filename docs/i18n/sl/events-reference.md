> 🌐 To je samodejni prevod. Popravki skupnosti so dobrodošli!
>
> [English version](../../events-reference.md)

# Referenca dogodkov Barion Pixel

## Pregled

Vtičnik podpira dva načina delovanja:

- **Osnovni piksel** (vedno aktiven, ko je konfiguriran ID piksla): Naloži `bp.js` in samodejno sproži `pageView` na vsaki strani. Uporablja se za preprečevanje goljufij.
- **Popolno sledenje** (izbirno, preklop v skrbniškem vmesniku): Dodaja sledenje dogodkov e-trgovine za tržno analitiko in nižje provizije Barion.

Barionova lastna referenca za te dogodke: [Barion Pixel API reference](https://docs.barion.com/Barion_Pixel_API_reference) in [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel) (v angleščini).

### Povzetek dogodkov

| Dogodek | Način | Klic bp() | Sprožilec |
|---------|-------|-----------|-----------|
| pageView | Osnovni | Samodejno (bp.js) | Vsako nalaganje strani |
| grantConsent | Osnovni | `bp('consent', 'grantConsent')` | Soglasje s piškotki sprejeto |
| rejectConsent | Osnovni | `bp('consent', 'rejectConsent')` | Soglasje s piškotki zavrnjeno |
| contentView | Polni | `bp('track', 'contentView', data)` | Stran posameznega izdelka |
| addToCart | Polni | `bp('track', 'addToCart', data)` | Akcija dodajanja v košarico |
| initiateCheckout | Polni | `bp('track', 'initiateCheckout', data)` | Nalaganje strani blagajne |
| purchase | Polni | `bp('track', 'purchase', data)` | Stran zahvale |
| setEncryptedEmail | Polni | `bp('identity', 'setEncryptedEmail', hash)` | Stran zahvale in vnos e-pošte na blagajni |

---

## Dogodki osnovnega piksla

### pageView

Sproži se samodejno, ko se naloži `bp.js`. Ni potrebna nobena konfiguracija razen nastavitve ID piksla.

### grantConsent

Sproži se, ko uporabnik sprejme tržne piškotke. Obravnavano samodejno prek WP Consent API ali Cookie Law Info ali ročno prek `window.wcBarionGrantConsent()`.

### rejectConsent

Sproži se, ko uporabnik zavrne tržne piškotke. Obravnavano samodejno prek WP Consent API ali Cookie Law Info ali ročno prek `window.wcBarionRejectConsent()`. Oba `grantConsent` in `rejectConsent` sta obvezna po zahtevah Barion.

Glejte [Integracija soglasja s piškotki](cookie-consent.md) za podrobnosti.

---

## Dogodki popolnega sledenja

### contentView

**Sprožilec:** Stran posameznega izdelka (kavelj `woocommerce_after_single_product`)

**Poslana polja:**

| Polje | Tip | Vrednost |
|-------|-----|---------|
| contentType | string | `'Product'` |
| currency | string | Valuta trgovine WooCommerce (npr. `'HUF'`) |
| id | string | ID izdelka |
| name | string | Prikazno ime izdelka |
| quantity | int | `1` (vedno — ogled enega izdelka) |
| unit | string | `'pcs'` |
| unitPrice | float | Cena izdelka |

> **Opomba:** `totalItemPrice` ni lastnost dogodka contentView. bp.js ga ob izvajanju zavrne z napako "Invalid key totalItemPrice in contentView event", prav tako ga referenca API za ta dogodek ne navaja. Namesto tega je obvezen znotraj postavk niza `contents`.

---

### addToCart

**Sprožilec:** JavaScript na strani odjemalca (sproži se takoj ob akciji dodajanja v košarico)

**Implementacija:** Dve poti, obe obravnavani na strani odjemalca za delovanje z medpomnjenjem strani:

1. **AJAX dodajanje v košarico** (strani trgovine/arhiva): Posluša za WooCommerce jQuery dogodek `added_to_cart`. Prebere podatke o izdelku iz atributov `<button>` data (`data-product_id`, `data-product_name`, `data-product_price`, `data-quantity`).

2. **Oddaja obrazca na strani posameznega izdelka**: Prestrezanje oddaje `form.cart`. Podatki o izdelku so vgrajeni kot JSON v nogi. Za spremenljive izdelke prebere `display_price` izbrane variacije iz podatkov WooCommerce jQuery `product_variations`.

**Poslana polja:**

| Polje | Tip | Vrednost |
|-------|-----|---------|
| contentType | string | `'Product'` |
| currency | string | Valuta trgovine |
| id | string | ID izdelka |
| name | string | Ime izdelka |
| quantity | int | Dodana količina |
| unit | string | `'pcs'` |
| unitPrice | float | Cena na enoto |
| totalItemPrice | float | `unitPrice * quantity` |
| step | int | `1` |

---

### initiateCheckout

**Sprožilec:** Nalaganje strani blagajne (kavelj `woocommerce_before_checkout_form`)

**Poslana polja:**

| Polje | Tip | Vrednost |
|-------|-----|---------|
| contents | array | Niz postavk košarice (glejte spodaj) |
| currency | string | Valuta trgovine |
| revenue | float | Vmesna vsota košarice + davek (brez poštnine — morda še ni izračunana) |
| step | int | `1` |

**Polja postavke vsebine:**

| Polje | Tip | Vrednost |
|-------|-----|---------|
| contentType | string | `'Product'` |
| currency | string | Valuta trgovine |
| id | string | ID izdelka |
| name | string | Ime izdelka |
| quantity | int | Količina postavke |
| unit | string | `'pcs'` |
| unitPrice | float | Cena enote |
| totalItemPrice | float | `unitPrice * quantity` |

---

### purchase

**Sprožilec:** Stran zahvale (kavelj `woocommerce_thankyou`)

**Preprečevanje podvajanja:** Uporablja post meta `_wc_barion_tracked` za preprečitev sprožitve ob ponovnem nalaganju strani.

**Poslana polja:**

| Polje | Tip | Vrednost |
|-------|-----|---------|
| contents | array | Niz postavk naročila (glejte spodaj) |
| currency | string | Valuta naročila |
| revenue | float | Skupaj naročilo (vključuje poštnino, davek, popuste) |
| step | int | `1` |

**Polja postavke vsebine:**

| Polje | Tip | Vrednost |
|-------|-----|---------|
| contentType | string | `'Product'` |
| currency | string | Valuta naročila |
| id | string | ID izdelka |
| name | string | Ime postavke |
| quantity | int | Količina postavke |
| unit | string | `'pcs'` |
| unitPrice | float | `(item_total + item_tax) / quantity` (odraža popuste) |
| totalItemPrice | float | `unitPrice * quantity` |

**Opomba o prihodku:** Dogodek `purchase` uporablja celotno vsoto naročila (vključno s poštnino), medtem ko `initiateCheckout` uporablja samo vmesno vsoto + davek (poštnina morda ni izračunana ob začetku blagajne).

---

### setEncryptedEmail

**Sprožilec:** Stran zahvale (kavelj `woocommerce_thankyou`) in stran blagajne — pri prijavljenih uporabnikih enkrat ob nalaganju, nato vsakič, ko kupec vnese drug veljaven e-poštni naslov za račun.

**Klic bp():** `bp('identity', 'setEncryptedEmail', hash)`

Naslov se pretvori v male črke in se v brskalniku zgosti z algoritmom SHA-1 (Web Crypto API), preden doseže `bp.js`. Barion API namesto navadnega naslova sprejme vnaprej izračunano zgoščeno vrednost SHA-1, predhodno zgoščevanje pa se izogne lastnemu regularnemu izrazu za e-pošto v `bp.js`, ki zavrača `+` v lokalnem delu in TLD, daljše od štirih črk. Vrednost, ki je že 40-znakovna šestnajstiška zgoščena vrednost, se posreduje nespremenjena; če Web Crypto API ni na voljo (okolje brez HTTPS), se pošlje navaden naslov.

Vrednosti, ki niso niti veljaven e-poštni naslov (po [specifikaciji HTML5](https://html.spec.whatwg.org/multipage/input.html#valid-e-mail-address)) niti zgoščena vrednost SHA-1, se nikoli ne pošljejo, zato delno tipkanje na blagajni ne doseže `bp.js`.

Na strani zahvale se sproži samo, ko ima naročilo e-poštni naslov za zaračunavanje.

---

## Neimplementirani dogodki

| Dogodek | Razlog |
|---------|--------|
| `customEvent` | Ni potreben za standardno sledenje e-trgovine |
| `initiatePurchase` | Barionov seznam obveznih dogodkov pravi, da implementirajte `initiatePurchase` ALI `purchase` — mi uporabljamo `purchase` |
| `setEncryptedPhone` | Izbirno; telefonska številka ni zanesljivo na voljo v vseh tokih WooCommerce |
| `search` | Izbirno; ni del obveznega nabora dogodkov |
