> 🌐 Ez egy automatikus fordítás. Közösségi javítások szívesen fogadottak!
>
> [English version](../../events-reference.md)

# Barion Pixel eseményreferencia

Abban, hogy melyik esemény mit jelent és milyen tulajdonságokat fogad el, a Barion saját
oldalai az irányadók:

- [Barion Pixel event reference](https://docs.barion.com/Barion-pixel-event-reference) — minden esemény, minden tulajdonság, és hogy melyik kötelező
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel) — maguk az események
- [Barion Pixel GYIK](https://docs.barion.com/Frequently_Asked_Questions_about_the_Barion_Pixel) — a kényes esetekre adott válaszok

Ez az oldal csak azt írja le, hogy **ez a bővítmény** mit és mikor küld.

## Áttekintés

A bővítménynek két üzemmódja van:

- **Alap Pixel** (aktív, amint a Pixel azonosító be van állítva): betölti a `bp.js`-t, és automatikusan elküldi a `pageView` eseményt. A Barion csalásmegelőzéshez követeli meg, és a Barion Smart Gateway használatának is előfeltétele.
- **Teljes követés** (opcionális, adminban kapcsolható): hozzáadja az e-kereskedelmi eseményeket. A Barion Metricsnek ezekre van szüksége, és a teljes Pixel implementáció megfelelő hozzájárulási sávval együtt az, ami egy boltot kedvezőbb Smart Gateway feltételekre jogosít.

### Események összefoglalása

| Esemény | Mód | bp() hívás | Kiváltó |
|---------|-----|------------|---------|
| pageView | Alap | Automatikus (bp.js) | Minden oldalbetöltés |
| grantConsent | Alap | `bp('consent', 'grantConsent')` | Marketing hozzájárulás elfogadva |
| rejectConsent | Alap | `bp('consent', 'rejectConsent')` | Marketing hozzájárulás elutasítva |
| contentView | Teljes | `bp('track', 'contentView', data)` | Termékoldal |
| addToCart | Teljes | `bp('track', 'addToCart', data)` | Kosárba helyezés |
| initiateCheckout | Teljes | `bp('track', 'initiateCheckout', data)` | Pénztároldal betöltése |
| purchase | Teljes | `bp('track', 'purchase', data)` | Rendelés visszaigazoló oldal |
| setEncryptedEmail | Teljes | `bp('identity', 'setEncryptedEmail', hash)` | Rendelés visszaigazoló oldal, illetve e-mail megadása a pénztárban |

---

## Tételmezők

A `contentView` és a `contents` tömb minden eleme ugyanazt a szerkezetet használja:

| Mező | Típus | Érték |
|------|-------|-------|
| contentType | string | `'Product'` |
| currency | string | A bolt pénzneme, `purchase` esetén a rendelés pénzneme |
| id | string | Termékazonosító |
| name | string | A termék megjelenített neve |
| quantity | int | Lásd az adott eseménynél |
| unit | string | `'pcs'` |
| unitPrice | float | Lásd az adott eseménynél |
| totalItemPrice | float | `unitPrice * quantity` |

Két kivétel ez alól:

- **A `contentView` nem küld `totalItemPrice` mezőt.** A bp.js `Invalid key totalItemPrice in contentView event` hibával utasítja el, és a Barion referenciája sem sorolja fel a `contentView` tulajdonságai között. A `contents` elemeken belül viszont kötelező — lásd a [Tesztelési megjegyzéseket](testing-notes.md).
- **A `quantity` a `contentView` eseménynél mindig `1`**, mert a vásárló egyetlen terméket néz.

A bővítmény egyetlen opcionális tartalomtulajdonságot sem küld (`brand`, `category`,
`description`, `ean`, `imageUrl`, `variant`), és `list` tulajdonságot sem. A Barion
referenciájában mindegyik opcionális.

**Változó termékek.** A `contentView` és a termékoldali `addToCart` a szülőterméket jelenti,
mert az oldal arról szól. A kosár- és rendelési sorok a kiválasztott variációt jelentik, mert
a WooCommerce azt teszi a kosárba. A Barion azt kéri, hogy egy tétel neve és azonosítója
minden eseményben azonos legyen, így egy variációkra épülő boltban ugyanaz a termék két
identitással is eljuthat a Barionhoz.

---

## Alap Pixel események

### pageView

Automatikusan elindul, amint a `bp.js` betöltődik. A Pixel azonosítón kívül nincs mit beállítani.

### grantConsent / rejectConsent

Akkor indulnak el, amikor a vásárló elfogadja vagy elutasítja a marketing cookie-kat. A Barion
mindkettőt kötelezőnek sorolja fel. Automatikusan a WP Consent API-n vagy a Cookie Law Infón
keresztül futnak, illetve kézzel a `window.wcBarionGrantConsent()` /
`window.wcBarionRejectConsent()` hívásokkal.

Lásd a [Cookie-hozzájárulás integrációt](cookie-consent.md).

---

## Teljes követés eseményei

### contentView

**Kiváltó:** termékoldal, a `woocommerce_after_single_product` hookon.

A `unitPrice` a termék aktuális ára. Változó terméknél az az ár, amelyet a WooCommerce a
variáció kiválasztása előtt mutat.

---

### addToCart

**Kiváltó:** maga a kosárba helyezés. Minden útvonal kliensoldali, így az esemény túléli az
oldal gyorsítótárazását. Három útvonal van, és az fut, amelyik a bolt gombjaihoz illik:

1. **Klasszikus AJAX kosárba helyezés** (bolt- és archívumoldalak). A WooCommerce jQuery `added_to_cart` eseményére figyel. A gomb a terméket és a mennyiséget adja meg, a `data-product_id` és a `data-quantity` attribútummal. Árat **nem** hordoz — a WooCommerce nem ír ki `data-product_price` attribútumot —, ezért az ár a [Store API](https://developer.woocommerce.com/docs/apis/store-api/) most létrehozott kosártételéből származik.
2. **Klasszikus termékoldal.** Elkapja a `form.cart` beküldését. A termékadatok a láblécbe vannak beágyazva; változó terméknél a kiválasztott variáció `display_price` értékét a WooCommerce jQuery `product_variations` adatából olvassa.
3. **Blokkfelületek** (Product Collection gombok, Cart blokk). Ezek az Interactivity API-n futnak, és sem a jQuery eseményt, sem használható adatot nem küldenek, ezért a bővítmény a [Store API](https://developer.woocommerce.com/docs/apis/store-api/) kosarát hasonlítja össze a legutóbb ismert állapottal, és a különbséget jelenti. A Cart blokkban végzett mennyiségmódosítás nem indítja a `wc-blocks_added_to_cart` eseményt, így az automatikusan kimarad.

**Esemény mezői:** a fenti tételmezők, valamint `step: 1`.

A `quantity` az, amennyit a vásárló ténylegesen betett. A `unitPrice` a Store API tételéből
származik a klasszikus AJAX és a blokkos felületek esetén is, a termékoldalon pedig a kiválasztott
variációból — soha nem a gomb jelöléséből, amely nem tartalmazza.

---

### initiateCheckout

**Kiváltó:** a pénztároldal betöltése. Az `is_checkout()` alapján ismeri fel, az
`order-received` végpontot kizárva — nem a `woocommerce_before_checkout_form` hookkal, mert azt
a Checkout blokk soha nem futtatja.

| Mező | Típus | Érték |
|------|-------|-------|
| contents | array | Kosársoronként egy tétel |
| currency | string | A bolt pénzneme |
| revenue | float | Kosár nettó összeg + adó |
| step | int | `1` |

A szállítás szándékosan marad ki a `revenue` értékből: a pénztár elején a vásárló általában még
nem választott szállítási módot, így a WooCommerce-nek nincs mit hozzáadnia.

---

### purchase

**Kiváltó:** a rendelés visszaigazoló oldala, a `woocommerce_thankyou` hookon.

| Mező | Típus | Érték |
|------|-------|-------|
| contents | array | Rendelési soronként egy tétel |
| currency | string | A rendelés pénzneme |
| revenue | float | A rendelés végösszege, szállítással, adóval és kedvezményekkel |
| step | int | `1` |

A `unitPrice` itt `(item_total + item_tax) / quantity`, tehát tükrözi a kuponokat és az egyéb
kedvezményeket. Emiatt a `purchase` és az `initiateCheckout` bevétele soronként nem
összehasonlítható.

**Duplikáció-védelem:** a rendelés `_wc_barion_tracked` metaadatot kap, így a visszaigazoló
oldal újratöltése nem küld második `purchase` eseményt.

**Ismert eltérés.** A Barion akkor kéri a `purchase` eseményt, amikor a fizetés ténylegesen
sikeres volt, sikertelen fizetésnél pedig `step: -1` értékkel. A bővítmény minden esetben
`step: 1` értékkel küldi a `purchase` eseményt, amikor a vásárló eléri a visszaigazoló oldalt —
ami offline fizetési módoknál, például átutalásnál vagy utánvétnél még fizetetlen rendeléssel
történik. `step: -1` értéket soha nem küld.

---

### setEncryptedEmail

**bp() hívás:** `bp('identity', 'setEncryptedEmail', hash)`

**Kiváltók:**

- A rendelés visszaigazoló oldala, ha a rendeléshez tartozik számlázási e-mail-cím.
- A pénztároldal, egyszer betöltéskor, bejelentkezett vásárlóknál.
- A pénztároldal, valahányszor a vásárló másik érvényes számlázási e-mail-címet ad meg — klasszikus pénztárnál a `#billing_email` mezőből, blokkos pénztárnál a Cart és Checkout blokk adattárából.

A cím kisbetűssé alakítva és SHA-1 kivonatolva jut el a `bp.js`-hez (Web Crypto API). A Barion a
sima cím helyett elfogadja az előre kiszámított SHA-1 kivonatot, az előzetes kivonatolás pedig
megkerüli a `bp.js` saját e-mail-mintáját, amely elutasítja a helyi részben lévő `+` jelet és a
négy betűnél hosszabb TLD-ket. A már 40 karakteres hexadecimális kivonatot változatlanul
továbbadja. Ha a Web Crypto API nem érhető el — nem HTTPS környezetben —, a sima cím megy el.

Azok az értékek, amelyek sem érvényes e-mail-címek (a
[HTML5 specifikáció](https://html.spec.whatwg.org/multipage/input.html#valid-e-mail-address)
szerint), sem SHA-1 kivonatok, soha nem kerülnek elküldésre, így a pénztárban a részleges gépelés
nem jut el a `bp.js`-hez. Az ismételt érték nem csinál semmit.

---

## Amit a bővítmény nem küld

A Barion eseményreferenciája ezeket a **kötelező** eseménykezelők között sorolja fel. A GYIK
hozzáteszi, hogy azt az eseményt, amelynek a boltodban nincs megfelelő felhasználói szándéka,
nem kell megvalósítani — ez néhányat lefed közülük, de nem mindet.

| Esemény | Miért nem |
|---------|-----------|
| `initiatePurchase` | Itt felesleges. A Barion `initiatePurchase` *vagy* `purchase` eseményt kér; a bővítmény a `purchase`-t küldi |
| `setEncryptedPhone` | A számlázási telefonszám a WooCommerce-ben opcionális, és sok boltban hiányzik |
| `search`, `categorySelection`, `addPaymentInfo`, `removeFromCart` | Egy tipikus WooCommerce boltra alkalmazhatók, de még nincsenek megvalósítva |

Az ajánlott kezelők — `customizeProduct`, `setUserProperties`, `signUp`, `clickPromo`,
`clickProduct`, `clickProductDetail`, `error` — és a `customEvent` szintén nincsenek
megvalósítva.

Ha a boltodnak szüksége van valamelyikre, az alap pixel a `bp()` függvényt a `window` objektumon
hagyja, így a `bp('track', 'search', { ... })` hívás a saját sablon- vagy bővítménykódodból is
működik.
