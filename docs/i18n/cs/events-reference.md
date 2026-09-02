> 🌐 Toto je automatický překlad. Komunitní opravy jsou vítány!
>
> [English version](../../events-reference.md)

# Přehled událostí Barion Pixel

Závazným zdrojem toho, co která událost znamená a jaké vlastnosti přijímá, jsou vlastní stránky
Barionu:

- [Barion Pixel event reference](https://docs.barion.com/Barion-pixel-event-reference) — každá událost, každá vlastnost a to, které jsou povinné
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel) — samotné události
- [Barion Pixel FAQ](https://docs.barion.com/Frequently_Asked_Questions_about_the_Barion_Pixel) — odpovědi na sporné případy

Tato stránka popisuje pouze to, co posílá **tento plugin** a kdy.

## Přehled

Plugin má dva provozní režimy:

- **Základní Pixel** (aktivní, jakmile je nastaveno ID Pixelu): načte `bp.js` a automaticky odešle událost `pageView`. Barion jej vyžaduje kvůli prevenci podvodů a je podmínkou pro používání Barion Smart Gateway jako takové.
- **Kompletní sledování** (volitelné, přepínač ve správě): přidává e-commerce události. Barion Metrics je potřebuje a kompletní implementace Pixelu spolu s vyhovující lištou souhlasu je to, co obchodu otevírá výhodnější podmínky Smart Gateway.

### Souhrn událostí

| Událost | Režim | Volání bp() | Spouštěč |
|---------|-------|-------------|----------|
| pageView | Základní | Automaticky (bp.js) | Každé načtení stránky |
| grantConsent | Základní | `bp('consent', 'grantConsent')` | Marketingový souhlas udělen |
| rejectConsent | Základní | `bp('consent', 'rejectConsent')` | Marketingový souhlas odmítnut |
| contentView | Kompletní | `bp('track', 'contentView', data)` | Stránka produktu |
| addToCart | Kompletní | `bp('track', 'addToCart', data)` | Přidání do košíku |
| initiateCheckout | Kompletní | `bp('track', 'initiateCheckout', data)` | Načtení stránky pokladny |
| purchase | Kompletní | `bp('track', 'purchase', data)` | Stránka potvrzení objednávky |
| setEncryptedEmail | Kompletní | `bp('identity', 'setEncryptedEmail', hash)` | Stránka potvrzení objednávky a zadání e-mailu v pokladně |

---

## Pole položky

`contentView` i každý prvek pole `contents` používají stejnou strukturu:

| Pole | Typ | Hodnota |
|------|-----|---------|
| contentType | string | `'Product'` |
| currency | string | Měna obchodu, u `purchase` měna objednávky |
| id | string | ID produktu |
| name | string | Zobrazovaný název produktu |
| quantity | int | Viz konkrétní událost |
| unit | string | `'pcs'` |
| unitPrice | float | Viz konkrétní událost |
| totalItemPrice | float | `unitPrice * quantity` |

Dvě výjimky z této tabulky:

- **`contentView` neposílá `totalItemPrice`.** bp.js jej odmítá s chybou `Invalid key totalItemPrice in contentView event` a reference Barionu jej mezi vlastnostmi contentView také neuvádí. Uvnitř prvků `contents` je naopak povinné — viz [Poznámky k testování](testing-notes.md).
- **`quantity` je u `contentView` vždy `1`**, protože zákazník si prohlíží jeden produkt.

Plugin neposílá žádné volitelné vlastnosti obsahu (`brand`, `category`, `description`, `ean`,
`imageUrl`, `variant`) ani vlastnost `list`. V referenci Barionu jsou všechny volitelné.

**Variabilní produkty.** `contentView` a `addToCart` ze stránky produktu hlásí nadřazený produkt,
protože o něm ta stránka je. Řádky košíku a objednávky hlásí zvolenou variantu, protože tu dává
WooCommerce do košíku. Barion požaduje, aby položka měla ve všech událostech stejný název i
identifikátor, takže v obchodě postaveném na variantách se týž produkt může k Barionu dostat pod
dvěma identitami.

---

## Události základního Pixelu

### pageView

Odešle se automaticky, jakmile se načte `bp.js`. Kromě ID Pixelu není co nastavovat.

### grantConsent / rejectConsent

Odesílají se, když zákazník přijme nebo odmítne marketingové cookies. Barion uvádí obě jako
povinné. Řeší se automaticky přes WP Consent API nebo Cookie Law Info, případně ručně přes
`window.wcBarionGrantConsent()` / `window.wcBarionRejectConsent()`.

Viz [Integrace souhlasu s cookies](cookie-consent.md).

---

## Události kompletního sledování

### contentView

**Spouštěč:** stránka produktu, hook `woocommerce_after_single_product`.

`unitPrice` je aktuální cena produktu. U variabilního produktu je to cena, kterou WooCommerce
zobrazuje před výběrem varianty.

---

### addToCart

**Spouštěč:** samotné přidání do košíku. Všechny cesty jsou na straně klienta, aby událost
přežila ukládání stránek do mezipaměti. Cesty jsou tři a záleží na tom, jak obchod vykresluje
svá tlačítka:

1. **Klasické AJAX přidání do košíku** (stránky obchodu a výpisů). Naslouchá jQuery události `added_to_cart` z WooCommerce. Tlačítko dává produkt a množství přes `data-product_id` a `data-quantity`. Cenu **nenese** — WooCommerce žádné `data-product_price` nevykresluje — takže cena pochází z položky [Store API](https://developer.woocommerce.com/docs/apis/store-api/), kterou přidání právě vytvořilo.
2. **Klasická stránka produktu.** Zachytí odeslání `form.cart`. Data produktu jsou vložena v patičce; u variabilního produktu se `display_price` zvolené varianty čte z jQuery dat `product_variations` WooCommerce.
3. **Blokové plochy** (tlačítka bloku Product Collection, blok Cart). Ty běží na Interactivity API a neodesílají ani jQuery událost, ani použitelná data, takže plugin porovná košík ze [Store API](https://developer.woocommerce.com/docs/apis/store-api/) s posledním známým stavem a nahlásí rozdíl. Změna množství v bloku Cart událost `wc-blocks_added_to_cart` nespouští, takže se automaticky nezapočítá.

**Pole události:** výše uvedená pole položky plus `step: 1`.

`quantity` je to, co zákazník skutečně přidal. `unitPrice` pochází z položky Store API u klasického
AJAXu i u blokových ploch a ze zvolené varianty na stránce produktu — nikdy ze značek tlačítka,
které cenu nenesou.

---

### initiateCheckout

**Spouštěč:** načtení stránky pokladny. Rozpoznává se přes `is_checkout()` s vyloučením koncového
bodu `order-received` — nikoli přes `woocommerce_before_checkout_form`, protože ten blok Checkout
nikdy nespustí.

| Pole | Typ | Hodnota |
|------|-----|---------|
| contents | array | Jedna položka na řádek košíku |
| currency | string | Měna obchodu |
| revenue | float | Mezisoučet košíku + daň |
| step | int | `1` |

Doprava je z `revenue` záměrně vynechána: na začátku pokladny si zákazník obvykle ještě nevybral
způsob dopravy, takže WooCommerce nemá co přidat.

---

### purchase

**Spouštěč:** stránka potvrzení objednávky, hook `woocommerce_thankyou`.

| Pole | Typ | Hodnota |
|------|-----|---------|
| contents | array | Jedna položka na řádek objednávky |
| currency | string | Měna objednávky |
| revenue | float | Celková částka objednávky včetně dopravy, daně a slev |
| step | int | `1` |

`unitPrice` je zde `(item_total + item_tax) / quantity`, takže odráží kupony i další slevy. Proto
nejsou tržby z `purchase` a `initiateCheckout` porovnatelné řádek po řádku.

**Prevence duplicit:** objednávka dostane meta příznak `_wc_barion_tracked`, takže opětovné
načtení stránky potvrzení neodešle druhou událost `purchase`.

**Známá odchylka.** Barion očekává `purchase` tehdy, když platba skutečně proběhla, a `purchase`
s `step: -1`, když selhala. Plugin odesílá `purchase` se `step: 1` pokaždé, když zákazník dorazí
na stránku potvrzení objednávky — u offline metod, jako je bankovní převod nebo dobírka, tedy ve
chvíli, kdy je objednávka ještě nezaplacená. Hodnotu `step: -1` neodesílá nikdy.

---

### setEncryptedEmail

**Volání bp():** `bp('identity', 'setEncryptedEmail', hash)`

**Spouštěče:**

- Stránka potvrzení objednávky, pokud má objednávka fakturační e-mail.
- Stránka pokladny, jednou při načtení u přihlášených zákazníků.
- Stránka pokladny, kdykoli zákazník zadá jiný platný fakturační e-mail — z pole `#billing_email` u klasické pokladny nebo z datového úložiště bloků Cart a Checkout u blokové pokladny.

Adresa se převede na malá písmena a v prohlížeči zahashuje algoritmem SHA-1 (Web Crypto API), než
se dostane do `bp.js`. Barion místo prosté adresy přijímá předpočítaný SHA-1 hash a předběžné
hashování obchází vlastní e-mailový regulární výraz bp.js, který odmítá `+` v lokální části a TLD
delší než čtyři písmena. Hodnota, která už je 40znakovým hexadecimálním hashem, projde beze změny.
Pokud Web Crypto API není k dispozici — mimo HTTPS —, odešle se prostá adresa.

Hodnoty, které nejsou ani platným e-mailem (podle
[specifikace HTML5](https://html.spec.whatwg.org/multipage/input.html#valid-e-mail-address)), ani
SHA-1 hashem, se neodesílají nikdy, takže částečné psaní v pokladně se do `bp.js` nedostane.
Opakovaná hodnota nic neudělá.

---

## Události, které plugin neposílá

Reference událostí Barionu je uvádí mezi **povinnými** obsluhami událostí. FAQ dodává, že událost,
které ve vašem obchodě neodpovídá žádný uživatelský záměr, není potřeba implementovat — to
pokrývá některé z nich, ale ne všechny.

| Událost | Proč ne |
|---------|---------|
| `initiatePurchase` | Zde nadbytečná. Barion požaduje `initiatePurchase` *nebo* `purchase`; plugin posílá `purchase` |
| `setEncryptedPhone` | Fakturační telefon je ve WooCommerce volitelný a v mnoha obchodech chybí |
| `search`, `categorySelection`, `addPaymentInfo`, `removeFromCart` | Pro typický obchod na WooCommerce použitelné, ale zatím neimplementované |

Doporučené obsluhy — `customizeProduct`, `setUserProperties`, `signUp`, `clickPromo`,
`clickProduct`, `clickProductDetail`, `error` — a `customEvent` také implementované nejsou.

Pokud váš obchod některou z nich potřebuje, základní pixel ponechává `bp()` na objektu `window`,
takže `bp('track', 'search', { ... })` funguje i z vaší vlastní šablony nebo pluginu.
