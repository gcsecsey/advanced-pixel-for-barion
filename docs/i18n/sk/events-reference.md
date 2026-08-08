> 🌐 Toto je automatický preklad. Komunitné opravy sú vítané!
>
> [English version](../../events-reference.md)

# Referencia udalostí Barion Pixel

Záväzným zdrojom toho, čo ktorá udalosť znamená a aké vlastnosti prijíma, sú vlastné stránky
Barionu:

- [Barion Pixel event reference](https://docs.barion.com/Barion-pixel-event-reference) — každá udalosť, každá vlastnosť a to, ktoré sú povinné
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel) — samotné udalosti
- [Barion Pixel FAQ](https://docs.barion.com/Frequently_Asked_Questions_about_the_Barion_Pixel) — odpovede na sporné prípady

Táto stránka popisuje len to, čo posiela **tento plugin** a kedy.

## Prehľad

Plugin má dva prevádzkové režimy:

- **Základný Pixel** (aktívny, len čo je nastavené Pixel ID): načíta `bp.js` a automaticky odošle udalosť `pageView`. Barion ho vyžaduje kvôli prevencii podvodov a je podmienkou používania Barion Smart Gateway ako takého.
- **Úplné sledovanie** (voliteľné, prepínač v administrácii): pridáva e-commerce udalosti. Barion Metrics ich potrebuje a úplná implementácia Pixela spolu s vyhovujúcou lištou súhlasu je to, čo obchodu otvára výhodnejšie podmienky Smart Gateway.

### Súhrn udalostí

| Udalosť | Režim | Volanie bp() | Spúšťač |
|---------|-------|--------------|---------|
| pageView | Základný | Automaticky (bp.js) | Každé načítanie stránky |
| grantConsent | Základný | `bp('consent', 'grantConsent')` | Marketingový súhlas udelený |
| rejectConsent | Základný | `bp('consent', 'rejectConsent')` | Marketingový súhlas odmietnutý |
| contentView | Úplný | `bp('track', 'contentView', data)` | Stránka produktu |
| addToCart | Úplný | `bp('track', 'addToCart', data)` | Pridanie do košíka |
| initiateCheckout | Úplný | `bp('track', 'initiateCheckout', data)` | Načítanie stránky pokladne |
| purchase | Úplný | `bp('track', 'purchase', data)` | Stránka potvrdenia objednávky |
| setEncryptedEmail | Úplný | `bp('identity', 'setEncryptedEmail', hash)` | Stránka potvrdenia objednávky a zadanie e-mailu v pokladni |

---

## Polia položky

`contentView` aj každý prvok poľa `contents` používajú rovnakú štruktúru:

| Pole | Typ | Hodnota |
|------|-----|---------|
| contentType | string | `'Product'` |
| currency | string | Mena obchodu, pri `purchase` mena objednávky |
| id | string | ID produktu |
| name | string | Zobrazovaný názov produktu |
| quantity | int | Pozri konkrétnu udalosť |
| unit | string | `'pcs'` |
| unitPrice | float | Pozri konkrétnu udalosť |
| totalItemPrice | float | `unitPrice * quantity` |

Dve výnimky z tejto tabuľky:

- **`contentView` neposiela `totalItemPrice`.** bp.js ho odmieta chybou `Invalid key totalItemPrice in contentView event` a referencia Barionu ho medzi vlastnosťami contentView tiež neuvádza. Vnútri prvkov `contents` je naopak povinné — pozri [Poznámky k testovaniu](testing-notes.md).
- **`quantity` je pri `contentView` vždy `1`**, pretože zákazník si prezerá jeden produkt.

Plugin neposiela žiadne voliteľné vlastnosti obsahu (`brand`, `category`, `description`, `ean`,
`imageUrl`, `variant`) ani vlastnosť `list`. V referencii Barionu sú všetky voliteľné.

**Variabilné produkty.** `contentView` a `addToCart` zo stránky produktu hlásia nadradený produkt,
pretože o ňom tá stránka je. Riadky košíka a objednávky hlásia zvolenú variantu, pretože tú dáva
WooCommerce do košíka. Barion požaduje, aby položka mala vo všetkých udalostiach rovnaký názov aj
identifikátor, takže v obchode postavenom na variantoch sa ten istý produkt môže k Barionu dostať
pod dvoma identitami.

---

## Udalosti základného Pixela

### pageView

Odošle sa automaticky, len čo sa načíta `bp.js`. Okrem Pixel ID nie je čo nastavovať.

### grantConsent / rejectConsent

Odosielajú sa, keď zákazník prijme alebo odmietne marketingové cookies. Barion uvádza obe ako
povinné. Riešia sa automaticky cez WP Consent API alebo Cookie Law Info, prípadne ručne cez
`window.wcBarionGrantConsent()` / `window.wcBarionRejectConsent()`.

Pozri [Integrácia súhlasu s cookies](cookie-consent.md).

---

## Udalosti úplného sledovania

### contentView

**Spúšťač:** stránka produktu, hook `woocommerce_after_single_product`.

`unitPrice` je aktuálna cena produktu. Pri variabilnom produkte je to cena, ktorú WooCommerce
zobrazuje pred výberom variantu.

---

### addToCart

**Spúšťač:** samotné pridanie do košíka. Všetky cesty sú na strane klienta, aby udalosť prežila
ukladanie stránok do cache. Cesty sú tri a záleží na tom, ako obchod vykresľuje svoje tlačidlá:

1. **Klasické AJAX pridanie do košíka** (stránky obchodu a výpisov). Počúva jQuery udalosť `added_to_cart` z WooCommerce a číta atribúty tlačidla `data-product_id`, `data-product_name`, `data-product_price` a `data-quantity`.
2. **Klasická stránka produktu.** Zachytí odoslanie `form.cart`. Údaje produktu sú vložené v pätičke; pri variabilnom produkte sa `display_price` zvoleného variantu číta z jQuery údajov `product_variations` WooCommerce.
3. **Blokové plochy** (tlačidlá bloku Product Collection, blok Cart). Tie bežia na Interactivity API a neodosielajú ani jQuery udalosť, ani použiteľné údaje, takže plugin porovná košík zo [Store API](https://developer.woocommerce.com/docs/apis/store-api/) s posledným známym stavom a nahlási rozdiel. Zmena množstva v bloku Cart udalosť `wc-blocks_added_to_cart` nespúšťa, takže sa automaticky nezapočíta.

**Polia udalosti:** vyššie uvedené polia položky plus `step: 1`.

`quantity` je to, čo zákazník naozaj pridal. `unitPrice` pochádza podľa cesty z údajov tlačidla,
zo zvoleného variantu alebo z položky Store API.

---

### initiateCheckout

**Spúšťač:** načítanie stránky pokladne. Rozpoznáva sa cez `is_checkout()` s vylúčením koncového
bodu `order-received` — nie cez `woocommerce_before_checkout_form`, pretože ten blok Checkout
nikdy nespustí.

| Pole | Typ | Hodnota |
|------|-----|---------|
| contents | array | Jedna položka na riadok košíka |
| currency | string | Mena obchodu |
| revenue | float | Medzisúčet košíka + daň |
| step | int | `1` |

Doprava je z `revenue` zámerne vynechaná: na začiatku pokladne si zákazník zvyčajne ešte nevybral
spôsob dopravy, takže WooCommerce nemá čo pridať.

---

### purchase

**Spúšťač:** stránka potvrdenia objednávky, hook `woocommerce_thankyou`.

| Pole | Typ | Hodnota |
|------|-----|---------|
| contents | array | Jedna položka na riadok objednávky |
| currency | string | Mena objednávky |
| revenue | float | Celková suma objednávky vrátane dopravy, dane a zliav |
| step | int | `1` |

`unitPrice` je tu `(item_total + item_tax) / quantity`, takže odráža kupóny aj ďalšie zľavy. Preto
nie sú tržby z `purchase` a `initiateCheckout` porovnateľné riadok po riadku.

**Prevencia duplicít:** objednávka dostane meta príznak `_wc_barion_tracked`, takže opätovné
načítanie stránky potvrdenia neodošle druhú udalosť `purchase`.

**Známa odchýlka.** Barion očakáva `purchase` vtedy, keď platba naozaj prebehla, a `purchase` so
`step: -1`, keď zlyhala. Plugin odosiela `purchase` so `step: 1` vždy, keď zákazník príde na
stránku potvrdenia objednávky — pri offline metódach ako bankový prevod alebo dobierka teda vo
chvíli, keď je objednávka ešte nezaplatená. Hodnotu `step: -1` neodosiela nikdy.

---

### setEncryptedEmail

**Volanie bp():** `bp('identity', 'setEncryptedEmail', hash)`

**Spúšťače:**

- Stránka potvrdenia objednávky, ak má objednávka fakturačný e-mail.
- Stránka pokladne, raz pri načítaní pri prihlásených zákazníkoch.
- Stránka pokladne, kedykoľvek zákazník zadá iný platný fakturačný e-mail — z poľa `#billing_email` pri klasickej pokladni alebo z dátového úložiska blokov Cart a Checkout pri blokovej pokladni.

Adresa sa prevedie na malé písmená a v prehliadači zahashuje algoritmom SHA-1 (Web Crypto API),
než sa dostane do `bp.js`. Barion namiesto obyčajnej adresy prijíma predpočítaný SHA-1 hash a
predbežné hashovanie obchádza vlastný e-mailový regulárny výraz bp.js, ktorý odmieta `+` v
lokálnej časti a TLD dlhšie než štyri písmená. Hodnota, ktorá už je 40-znakovým hexadecimálnym
hashom, prejde bez zmeny. Ak Web Crypto API nie je k dispozícii — mimo HTTPS —, odošle sa obyčajná
adresa.

Hodnoty, ktoré nie sú ani platným e-mailom (podľa
[špecifikácie HTML5](https://html.spec.whatwg.org/multipage/input.html#valid-e-mail-address)), ani
SHA-1 hashom, sa neodosielajú nikdy, takže čiastočné písanie v pokladni sa do `bp.js` nedostane.
Zopakovaná hodnota nespraví nič.

---

## Udalosti, ktoré plugin neposiela

Referencia udalostí Barionu ich uvádza medzi **povinnými** obsluhami udalostí. FAQ dodáva, že
udalosť, ktorej vo vašom obchode nezodpovedá žiadny používateľský zámer, nie je potrebné
implementovať — to pokrýva niektoré z nich, ale nie všetky.

| Udalosť | Prečo nie |
|---------|-----------|
| `initiatePurchase` | Tu zbytočná. Barion požaduje `initiatePurchase` *alebo* `purchase`; plugin posiela `purchase` |
| `setEncryptedPhone` | Fakturačný telefón je vo WooCommerce voliteľný a v mnohých obchodoch chýba |
| `search`, `categorySelection`, `addPaymentInfo`, `removeFromCart` | Pre typický obchod na WooCommerce použiteľné, ale zatiaľ neimplementované |

Odporúčané obsluhy — `customizeProduct`, `setUserProperties`, `signUp`, `clickPromo`,
`clickProduct`, `clickProductDetail`, `error` — a `customEvent` implementované tiež nie sú.

Ak váš obchod niektorú z nich potrebuje, základný pixel ponecháva `bp()` na objekte `window`,
takže `bp('track', 'search', { ... })` funguje aj z vlastnej šablóny alebo pluginu.
