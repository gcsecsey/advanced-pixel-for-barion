> 🌐 To je samodejni prevod. Popravki skupnosti so dobrodošli!
>
> [English version](../../events-reference.md)

# Referenca dogodkov Barion Pixel

Za to, kaj posamezen dogodek pomeni in katere lastnosti sprejema, so merodajne Barionove lastne
strani:

- [Barion Pixel event reference](https://docs.barion.com/Barion-pixel-event-reference) — vsak dogodek, vsaka lastnost in katere so obvezne
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel) — dogodki sami
- [Barion Pixel FAQ](https://docs.barion.com/Frequently_Asked_Questions_about_the_Barion_Pixel) — odgovori na zagatne primere

Ta stran opisuje samo to, kaj pošilja **ta vtičnik** in kdaj.

## Pregled

Vtičnik ima dva načina delovanja:

- **Osnovni piksel** (aktiven, takoj ko je nastavljen Pixel ID): naloži `bp.js` in samodejno pošlje `pageView`. Barion ga zahteva za preprečevanje goljufij in je pogoj za uporabo Barion Smart Gateway kot takega.
- **Popolno sledenje** (izbirno, stikalo v skrbniškem vmesniku): doda dogodke e-trgovine. Barion Metrics jih potrebuje, popolna implementacija piksla skupaj s skladno pasico za soglasje pa je tisto, kar trgovini odpre ugodnejše pogoje Smart Gateway.

### Povzetek dogodkov

| Dogodek | Način | Klic bp() | Sprožilec |
|---------|-------|-----------|-----------|
| pageView | Osnovni | Samodejno (bp.js) | Vsako nalaganje strani |
| grantConsent | Osnovni | `bp('consent', 'grantConsent')` | Trženjsko soglasje sprejeto |
| rejectConsent | Osnovni | `bp('consent', 'rejectConsent')` | Trženjsko soglasje zavrnjeno |
| contentView | Popolni | `bp('track', 'contentView', data)` | Stran izdelka |
| addToCart | Popolni | `bp('track', 'addToCart', data)` | Dodajanje v košarico |
| initiateCheckout | Popolni | `bp('track', 'initiateCheckout', data)` | Nalaganje strani blagajne |
| purchase | Popolni | `bp('track', 'purchase', data)` | Stran potrditve naročila |
| setEncryptedEmail | Popolni | `bp('identity', 'setEncryptedEmail', hash)` | Stran potrditve naročila in vnos e-pošte na blagajni |

---

## Polja postavke

`contentView` in vsak element polja `contents` uporabljata isto obliko:

| Polje | Tip | Vrednost |
|-------|-----|----------|
| contentType | string | `'Product'` |
| currency | string | Valuta trgovine, pri `purchase` valuta naročila |
| id | string | ID izdelka |
| name | string | Prikazano ime izdelka |
| quantity | int | Glej posamezen dogodek |
| unit | string | `'pcs'` |
| unitPrice | float | Glej posamezen dogodek |
| totalItemPrice | float | `unitPrice * quantity` |

Dve izjemi od te tabele:

- **`contentView` ne pošlje `totalItemPrice`.** bp.js ga zavrne z `Invalid key totalItemPrice in contentView event`, tudi Barionova referenca ga ne navaja med lastnostmi contentView. Znotraj elementov `contents` pa je obvezen — glej [Opombe za testiranje](testing-notes.md).
- **`quantity` je pri `contentView` vedno `1`**, ker kupec gleda en izdelek.

Vtičnik ne pošilja nobene izbirne lastnosti vsebine (`brand`, `category`, `description`, `ean`,
`imageUrl`, `variant`) niti lastnosti `list`. V Barionovi referenci so vse izbirne.

**Spremenljivi izdelki.** `contentView` in `addToCart` s strani izdelka javljata nadrejeni izdelek,
saj stran govori o njem. Vrstice košarice in naročila javljajo izbrano različico, saj to
WooCommerce da v košarico. Barion zahteva, da ima postavka v vseh dogodkih enako ime in
identifikator, zato lahko v trgovini, zgrajeni na različicah, isti izdelek pride do Bariona pod
dvema identitetama.

---

## Dogodki osnovnega piksla

### pageView

Pošlje se samodejno, takoj ko se `bp.js` naloži. Razen Pixel ID-ja ni kaj nastavljati.

### grantConsent / rejectConsent

Pošljeta se, ko kupec sprejme ali zavrne trženjske piškotke. Barion oba navaja kot obvezna.
Samodejno se rešujeta prek WP Consent API ali Cookie Law Info, ročno pa prek
`window.wcBarionGrantConsent()` / `window.wcBarionRejectConsent()`.

Glej [Integracijo soglasja s piškotki](cookie-consent.md).

---

## Dogodki popolnega sledenja

### contentView

**Sprožilec:** stran izdelka, hook `woocommerce_after_single_product`.

`unitPrice` je trenutna cena izdelka. Pri spremenljivem izdelku je to cena, ki jo WooCommerce
prikaže pred izbiro različice.

---

### addToCart

**Sprožilec:** samo dejanje dodajanja v košarico. Vse poti so na strani odjemalca, zato dogodek
preživi medpomnjenje strani. Poti so tri, katera se uporabi pa je odvisno od tega, kako trgovina
izriše svoje gumbe:

1. **Klasično AJAX dodajanje v košarico** (strani trgovine in arhivov). Posluša WooCommercov dogodek jQuery `added_to_cart`. Gumb da izdelek in količino prek `data-product_id` in `data-quantity`. Cene **ne nosi** — WooCommerce ne izriše nobenega `data-product_price` — zato cena pride iz postavke [Store API](https://developer.woocommerce.com/docs/apis/store-api/), ki jo je dodajanje pravkar ustvarilo.
2. **Klasična stran izdelka.** Prestreže pošiljanje `form.cart`. Podatki o izdelku so vgrajeni v nogo; pri spremenljivem izdelku se `display_price` izbrane različice prebere iz WooCommercovih podatkov jQuery `product_variations`.
3. **Blokovne površine** (gumbi bloka Product Collection, blok Cart). Te tečejo na Interactivity API in ne pošljejo ne dogodka jQuery ne uporabnih podatkov, zato vtičnik primerja košarico iz [Store API](https://developer.woocommerce.com/docs/apis/store-api/) z zadnjim znanim stanjem in javi razliko. Sprememba količine v bloku Cart ne sproži `wc-blocks_added_to_cart`, zato je samodejno izvzeta.

**Polja dogodka:** zgornja polja postavke in `step: 1`.

`quantity` je tisto, kar je kupec dejansko dodal. `unitPrice` pride iz postavke Store API pri
klasičnem AJAX-u in pri blokovnih površinah ter iz izbrane različice na strani izdelka — nikoli iz
oznak gumba, ki je ne nosijo.

---

### initiateCheckout

**Sprožilec:** nalaganje strani blagajne. Zazna se prek `is_checkout()` z izključitvijo končne
točke `order-received` — ne prek `woocommerce_before_checkout_form`, saj tega hooka blok Checkout
nikoli ne sproži.

| Polje | Tip | Vrednost |
|-------|-----|----------|
| contents | array | Ena postavka na vrstico košarice |
| currency | string | Valuta trgovine |
| revenue | float | Vmesni seštevek košarice + davek |
| step | int | `1` |

Dostava je iz `revenue` namenoma izpuščena: na začetku blagajne kupec običajno še ni izbral načina
dostave, zato WooCommerce nima česa dodati.

---

### purchase

**Sprožilec:** stran potrditve naročila, hook `woocommerce_thankyou`.

| Polje | Tip | Vrednost |
|-------|-----|----------|
| contents | array | Ena postavka na vrstico naročila |
| currency | string | Valuta naročila |
| revenue | float | Skupni znesek naročila, z dostavo, davkom in popusti |
| step | int | `1` |

`unitPrice` je tu `(item_total + item_tax) / quantity`, zato odraža kupone in druge popuste. Zato
prihodka iz `purchase` in `initiateCheckout` nista primerljiva vrstico za vrstico.

**Preprečevanje podvojitev:** naročilo dobi meta oznako `_wc_barion_tracked`, zato ponovno
nalaganje strani potrditve ne pošlje drugega `purchase`.

**Znano odstopanje.** Barion pričakuje `purchase` takrat, ko je plačilo res uspelo, in `purchase`
s `step: -1`, ko je spodletelo. Vtičnik pošlje `purchase` s `step: 1` vsakič, ko kupec pride na
stran potrditve naročila — pri načinih brez povezave, kot sta bančno nakazilo ali plačilo po
povzetju, torej medtem ko je naročilo še neplačano. Vrednosti `step: -1` ne pošlje nikoli.

---

### setEncryptedEmail

**Klic bp():** `bp('identity', 'setEncryptedEmail', hash)`

**Sprožilci:**

- Stran potrditve naročila, če ima naročilo e-poštni naslov za račun.
- Stran blagajne, enkrat ob nalaganju za prijavljene kupce.
- Stran blagajne, kadar koli kupec vnese drug veljaven e-poštni naslov za račun — iz polja `#billing_email` pri klasični blagajni ali iz podatkovne shrambe blokov Cart in Checkout pri blokovni blagajni.

Naslov se pretvori v male črke in v brskalniku zgosti z algoritmom SHA-1 (Web Crypto API), preden
pride do `bp.js`. Barion namesto navadnega naslova sprejme vnaprej izračunano zgoščeno vrednost
SHA-1, predhodno zgoščevanje pa obide lasten regularni izraz bp.js, ki zavrača `+` v lokalnem delu
in TLD-je, daljše od štirih črk. Vrednost, ki je že 40-znakovna šestnajstiška zgoščena vrednost, se
posreduje nespremenjena. Če Web Crypto API ni na voljo — zunaj HTTPS — se pošlje navaden naslov.

Vrednosti, ki niso ne veljaven e-poštni naslov (po
[specifikaciji HTML5](https://html.spec.whatwg.org/multipage/input.html#valid-e-mail-address)) ne
zgoščena vrednost SHA-1, se ne pošljejo nikoli, zato delno tipkanje na blagajni ne pride do
`bp.js`. Ponovljena vrednost ne naredi ničesar.

---

## Dogodki, ki jih vtičnik ne pošilja

Barionova referenca dogodkov jih navaja med **obveznimi** upravljalci dogodkov. FAQ dodaja, da
dogodka, ki mu v tvoji trgovini ne ustreza nobena uporabnikova namera, ni treba implementirati —
to pokrije nekatere med njimi, ne pa vseh.

| Dogodek | Zakaj ne |
|---------|----------|
| `initiatePurchase` | Tu odveč. Barion zahteva `initiatePurchase` *ali* `purchase`; vtičnik pošilja `purchase` |
| `setEncryptedPhone` | Telefon za račun je v WooCommerce izbiren in ga v mnogih trgovinah ni |
| `search`, `categorySelection`, `addPaymentInfo`, `removeFromCart` | Za tipično trgovino WooCommerce uporabni, a še niso implementirani |

Priporočeni upravljalci — `customizeProduct`, `setUserProperties`, `signUp`, `clickPromo`,
`clickProduct`, `clickProductDetail`, `error` — in `customEvent` prav tako niso implementirani.

Če tvoja trgovina katerega od njih potrebuje, osnovni piksel pusti `bp()` na objektu `window`, zato
`bp('track', 'search', { ... })` deluje iz tvoje lastne teme ali vtičnika.
