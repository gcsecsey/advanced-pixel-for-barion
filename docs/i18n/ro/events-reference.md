> 🌐 Aceasta este o traducere automată. Corecțiile comunității sunt binevenite!
>
> [English version](../../events-reference.md)

# Referință evenimente Barion Pixel

Sursa de adevăr pentru ce înseamnă fiecare eveniment și ce proprietăți acceptă sunt paginile
proprii ale Barion:

- [Barion Pixel event reference](https://docs.barion.com/Barion-pixel-event-reference) — fiecare eveniment, fiecare proprietate și care dintre ele sunt obligatorii
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel) — evenimentele propriu-zise
- [Barion Pixel FAQ](https://docs.barion.com/Frequently_Asked_Questions_about_the_Barion_Pixel) — răspunsuri pentru cazurile dificile

Pagina de față descrie doar ce trimite **acest plugin** și când.

## Prezentare generală

Plugin-ul are două moduri de funcționare:

- **Pixelul de bază** (activ de îndată ce este setat un ID Pixel): încarcă `bp.js` și trimite automat `pageView`. Barion îl cere pentru prevenirea fraudei și este o precondiție pentru utilizarea Barion Smart Gateway în general.
- **Urmărire completă** (opțional, comutator în administrare): adaugă evenimentele de e-commerce. Barion Metrics are nevoie de ele, iar o implementare completă a Pixelului împreună cu o bară de consimțământ conformă este ceea ce califică un magazin pentru condiții mai bune la Smart Gateway.

### Sumarul evenimentelor

| Eveniment | Mod | Apel bp() | Declanșator |
|-----------|-----|-----------|-------------|
| pageView | Bază | Automat (bp.js) | Fiecare încărcare de pagină |
| grantConsent | Bază | `bp('consent', 'grantConsent')` | Consimțământ de marketing acceptat |
| rejectConsent | Bază | `bp('consent', 'rejectConsent')` | Consimțământ de marketing refuzat |
| contentView | Complet | `bp('track', 'contentView', data)` | Pagina de produs |
| addToCart | Complet | `bp('track', 'addToCart', data)` | Adăugare în coș |
| initiateCheckout | Complet | `bp('track', 'initiateCheckout', data)` | Încărcarea paginii de finalizare |
| purchase | Complet | `bp('track', 'purchase', data)` | Pagina de confirmare a comenzii |
| setEncryptedEmail | Complet | `bp('identity', 'setEncryptedEmail', hash)` | Pagina de confirmare și introducerea e-mailului la finalizare |

---

## Câmpurile unui articol

`contentView` și fiecare element al unui tablou `contents` folosesc aceeași structură:

| Câmp | Tip | Valoare |
|------|-----|---------|
| contentType | string | `'Product'` |
| currency | string | Moneda magazinului, la `purchase` moneda comenzii |
| id | string | ID-ul produsului |
| name | string | Numele afișat al produsului |
| quantity | int | Vezi evenimentul respectiv |
| unit | string | `'pcs'` |
| unitPrice | float | Vezi evenimentul respectiv |
| totalItemPrice | float | `unitPrice * quantity` |

Două excepții de la acest tabel:

- **`contentView` nu trimite `totalItemPrice`.** bp.js îl respinge cu `Invalid key totalItemPrice in contentView event`, iar referința Barion nu îl listează ca proprietate a contentView. În interiorul elementelor `contents` este însă obligatoriu — vezi [Notele de testare](testing-notes.md).
- **`quantity` este întotdeauna `1` la `contentView`**, pentru că vizitatorul se uită la un singur produs.

Plugin-ul nu trimite nicio proprietate opțională de conținut (`brand`, `category`, `description`,
`ean`, `imageUrl`, `variant`) și nici proprietatea `list`. În referința Barion, toate sunt
opționale.

**Produse variabile.** `contentView` și `addToCart` de pe pagina produsului raportează produsul
părinte, pentru că despre el este pagina. Liniile din coș și din comandă raportează variația aleasă,
pentru că pe aceasta o pune WooCommerce în coș. Barion cere ca un articol să aibă același nume și
identificator în toate evenimentele, așa că într-un magazin construit pe variații același produs
poate ajunge la Barion sub două identități.

---

## Evenimentele pixelului de bază

### pageView

Se trimite automat de îndată ce `bp.js` se încarcă. În afară de ID-ul Pixel nu ai ce configura.

### grantConsent / rejectConsent

Se trimit când clientul acceptă sau refuză cookie-urile de marketing. Barion le listează pe ambele
ca obligatorii. Sunt tratate automat prin WP Consent API sau Cookie Law Info, ori manual prin
`window.wcBarionGrantConsent()` / `window.wcBarionRejectConsent()`.

Vezi [Integrarea consimțământului pentru cookie-uri](cookie-consent.md).

---

## Evenimentele urmăririi complete

### contentView

**Declanșator:** pagina de produs, pe hook-ul `woocommerce_after_single_product`.

`unitPrice` este prețul curent al produsului. La un produs variabil, este prețul afișat de
WooCommerce înainte de alegerea unei variații.

---

### addToCart

**Declanșator:** acțiunea de adăugare în coș. Toate căile sunt pe partea clientului, deci
evenimentul supraviețuiește cache-ului de pagini. Sunt trei, iar cea folosită depinde de felul în
care magazinul își randează butoanele:

1. **Adăugare AJAX clasică în coș** (paginile de magazin și de arhivă). Ascultă evenimentul jQuery `added_to_cart` din WooCommerce. Butonul oferă produsul și cantitatea, prin `data-product_id` și `data-quantity`. **Nu** poartă un preț — WooCommerce nu redă niciun `data-product_price` — așa că prețul vine din linia [Store API](https://developer.woocommerce.com/docs/apis/store-api/) creată chiar de adăugare.
2. **Pagina de produs clasică.** Interceptează trimiterea `form.cart`. Datele produsului sunt încorporate în subsol; la un produs variabil, `display_price` al variației alese este citit din datele jQuery `product_variations` ale WooCommerce.
3. **Suprafețe cu blocuri** (butoanele blocului Product Collection, blocul Cart). Acestea rulează pe Interactivity API și nu emit nici evenimentul jQuery, nici date utile, așa că plugin-ul compară coșul din [Store API](https://developer.woocommerce.com/docs/apis/store-api/) cu ultima stare cunoscută și raportează diferența. Modificările de cantitate din blocul Cart nu declanșează `wc-blocks_added_to_cart`, deci sunt excluse automat.

**Câmpurile evenimentului:** câmpurile de articol de mai sus, plus `step: 1`.

`quantity` este ce a adăugat efectiv clientul. `unitPrice` provine din linia Store API atât la AJAX-ul
clasic, cât și la suprafețele de blocuri, și din variația aleasă pe pagina produsului — niciodată din
marcajul butonului, care nu îl conține.

---

### initiateCheckout

**Declanșator:** încărcarea paginii de finalizare. Este detectată cu `is_checkout()`, excluzând
endpoint-ul `order-received` — nu prin `woocommerce_before_checkout_form`, pe care blocul Checkout
nu îl declanșează niciodată.

| Câmp | Tip | Valoare |
|------|-----|---------|
| contents | array | Un element pentru fiecare linie din coș |
| currency | string | Moneda magazinului |
| revenue | float | Subtotalul coșului + taxa |
| step | int | `1` |

Livrarea este lăsată intenționat în afara `revenue`: la începutul finalizării, clientul de obicei
nu a ales încă o metodă de livrare, deci WooCommerce nu are ce adăuga.

---

### purchase

**Declanșator:** pagina de confirmare a comenzii, pe hook-ul `woocommerce_thankyou`.

| Câmp | Tip | Valoare |
|------|-----|---------|
| contents | array | Un element pentru fiecare linie din comandă |
| currency | string | Moneda comenzii |
| revenue | float | Totalul comenzii, inclusiv livrare, taxe și reduceri |
| step | int | `1` |

`unitPrice` este aici `(item_total + item_tax) / quantity`, deci reflectă cupoanele și celelalte
reduceri. De aceea veniturile din `purchase` și `initiateCheckout` nu sunt comparabile linie cu
linie.

**Prevenirea duplicatelor:** comanda primește meta `_wc_barion_tracked`, deci reîncărcarea paginii
de confirmare nu trimite un al doilea `purchase`.

**Abatere cunoscută.** Barion cere `purchase` atunci când plata a reușit efectiv și `purchase` cu
`step: -1` atunci când a eșuat. Plugin-ul trimite `purchase` cu `step: 1` ori de câte ori clientul
ajunge pe pagina de confirmare — ceea ce, la metodele offline precum transferul bancar sau ramburs,
se întâmplă cât timp comanda este încă neplătită. `step: -1` nu îl trimite niciodată.

---

### setEncryptedEmail

**Apel bp():** `bp('identity', 'setEncryptedEmail', hash)`

**Declanșatoare:**

- Pagina de confirmare a comenzii, dacă comanda are o adresă de e-mail de facturare.
- Pagina de finalizare, o dată la încărcare pentru clienții autentificați.
- Pagina de finalizare, ori de câte ori clientul introduce o altă adresă de facturare validă — din câmpul `#billing_email` la finalizarea clasică sau din depozitul de date al blocurilor Cart și Checkout la finalizarea cu blocuri.

Adresa este trecută în litere mici și hash-uită SHA-1 în browser (Web Crypto API) înainte să ajungă
la `bp.js`. Barion acceptă un hash SHA-1 precalculat în locul adresei simple, iar hash-uirea
prealabilă ocolește expresia regulată proprie a bp.js, care respinge `+` în partea locală și
TLD-urile mai lungi de patru litere. O valoare care este deja un hash hexazecimal de 40 de
caractere este transmisă neschimbată. Dacă Web Crypto API nu este disponibil — într-un context
non-HTTPS — se trimite adresa simplă.

Valorile care nu sunt nici e-mail valid (conform
[specificației HTML5](https://html.spec.whatwg.org/multipage/input.html#valid-e-mail-address)),
nici hash SHA-1 nu se trimit niciodată, deci tastarea parțială la finalizare nu ajunge la `bp.js`.
O valoare repetată nu face nimic.

---

## Evenimente pe care plugin-ul nu le trimite

Referința de evenimente a Barion le listează printre handlerele **obligatorii**. FAQ-ul adaugă că
un eveniment căruia nu îi corespunde nicio intenție a utilizatorului în magazinul tău nu trebuie
implementat — ceea ce acoperă unele dintre ele, dar nu pe toate.

| Eveniment | De ce nu |
|-----------|----------|
| `initiatePurchase` | Redundant aici. Barion cere `initiatePurchase` *sau* `purchase`; plugin-ul trimite `purchase` |
| `setEncryptedPhone` | Telefonul de facturare este opțional în WooCommerce și lipsește în multe magazine |
| `search`, `categorySelection`, `addPaymentInfo`, `removeFromCart` | Aplicabile unui magazin WooCommerce tipic, dar încă neimplementate |

Handlerele recomandate — `customizeProduct`, `setUserProperties`, `signUp`, `clickPromo`,
`clickProduct`, `clickProductDetail`, `error` — și `customEvent` nu sunt nici ele implementate.

Dacă magazinul tău are nevoie de vreunul, pixelul de bază lasă `bp()` pe `window`, așa că
`bp('track', 'search', { ... })` funcționează din propria ta temă sau plugin.
