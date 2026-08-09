> 🌐 Toto je automatický preklad. Komunitné opravy sú vítané!
>
> [English version](../../testing-notes.md)

# Poznámky k testovaniu a známe zvláštnosti

## Kým usúdiš, že pixel nefunguje

### „Testing message“ nie je chyba

Otvor konzolu na stránke s pixelom a bp.js napíše buď **„Testing message“**, alebo
**„Sending message“**. Barion
[rozdiel dokumentuje](https://docs.barion.com/Implementing_the_Base_Barion_Pixel): čerstvo
nasadený pixel ešte nie je oprávnený odosielať používateľské údaje, takže bp.js píše „Testing
message“ a prenáša len typ udalosti. Len čo Barion pixel schváli, prepne sa na „Sending message“.

Plugin na tom nič nemení. Ak tvoje udalosti v konzole vyzerajú správne, ale Barion žiadne údaje
nevidí, pixel zrejme stále čaká na schválenie na strane Barionu — implementáciu prechádza človek,
takže sa ozvi, keď budeš hotový.

### Pixel ID musí byť to správne

- Nájdeš ho v Barion peňaženke v **Merchant Management > Details**. Každý obchod, teda každý POSKey, má vlastné Pixel ID.
- Formát je `BP-` + desať znakov + `-` + dve číslice. ID, ktoré začína na `BPT`, nie je Pixel ID a fungovať nebude.
- Sandbox a ostré prostredie vydávajú **odlišné** Pixel ID. Testovacia stránka s ostrým ID znečisťuje reálne údaje; ostrá stránka so sandboxovým ID nezaznamená nič užitočné.

Ak chceš obchod na jedno použitie, stránka Barionu
[Creating a shop](https://docs.barion.com/Creating_a_shop) ťa prevedie sandboxom, kde sa obchody
schvaľujú automaticky.

---

## Zvláštnosti behovej kontroly v bp.js

bp.js kontroluje údaje udalostí v prehliadači a na niekoľkých miestach sú jeho pravidlá prísnejšie
alebo voľnejšie, než [referencia udalostí](https://docs.barion.com/Barion-pixel-event-reference)
naznačuje. Tieto prípady vyplynuli z testovania na staging prostredí.

### totalItemPrice: pri contentView odmietané, v prvkoch contents povinné

- **contentView** (plochá udalosť): bp.js `totalItemPrice` **odmieta** chybou `Invalid key totalItemPrice in contentView event`. Referencia súhlasí — nie je to vlastnosť contentView.
- Prvky `contents` udalostí **initiateCheckout** a **purchase**: bp.js ho **vyžaduje**, inak hlási `Mandatory key totalItemPrice is missing from contents event`. Aj tu referencia súhlasí.

Pravidlo palca: `totalItemPrice` je pri plochých udalostiach neplatné a vnútri prvkov `contents`
povinné.

### unit je v prvkoch contents povinné

Pri vynechaní sa objaví `Mandatory key unit is missing from contents event`.

### step

Plugin posiela `step: 1` pri udalostiach `addToCart`, `initiateCheckout` a `purchase`. Barion
dokumentuje `1` ako krok začiatku pokladne a pri `purchase` požaduje najvyššie číslo kroku, ktoré
používaš — pri jednokrokovej pokladni teda tiež `1`. Pri `addToCart` je `step` voliteľný.

---

## Režim ladenia

Zapni ho v **Nastavenia > Barion Pixel**, aby sa každá udalosť zapísala do konzoly prehliadača.

### Čo hľadať

Otvor konzolu (F12 > Konzola) a hľadaj správy `[Barion Pixel]`:

```
[Barion Pixel] bp.js loaded by Advanced Pixel for Barion
[Barion Pixel] Base pixel initialized with ID: BP-xxxxxxxxxx-xx
[Barion Pixel] Consent auto-granted via WP Consent API
[Barion Pixel] Block surfaces detected (cart store: true, product buttons: false)
[Barion Pixel] Event: contentView { contentType: "Product", ... }
[Barion Pixel] Event: addToCart { contentType: "Product", ... }
[Barion Pixel] Event: initiateCheckout { contents: [...], ... }
[Barion Pixel] Event: purchase { contents: [...], ... }
[Barion Pixel] setEncryptedEmail sent
```

Úplný zoznam správ o súhlase nájdeš v
[Integrácii súhlasu s cookies](cookie-consent.md).

### Chyby bp.js

bp.js zapisuje aj vlastné chyby kontroly. Tie bežné:

| Chyba | Význam | Riešenie |
|-------|--------|----------|
| `Mandatory key X is missing from Y event` | Povinné pole sa neodosiela | Skontroluj údaje udalosti |
| `Invalid key X in Y event` | Odosiela sa pole, ktoré bp.js nečaká | Pole odstráň |
| `Format of e-mail address or hash is invalid` | bp.js odmietol hodnotu odovzdanú do `setEncryptedEmail` | Od 1.0.3 plugin adresu vopred hashuje, takže by sa to už objavovať nemalo |

---

## Kontrolný zoznam testovania

Prejdi ho v klasickom aj v blokovom obchode — pre `addToCart`, `initiateCheckout` a
`setEncryptedEmail` používajú úplne odlišné cesty kódu.

### Stránka produktu (contentView)

1. Otvor stránku produktu s otvorenou konzolou.
2. Objaví sa `[Barion Pixel] Event: contentView`.
3. Žiadne chyby bp.js o chýbajúcich alebo neplatných kľúčoch.
4. Prítomné polia: `contentType`, `currency`, `id`, `name`, `quantity`, `unit`, `unitPrice` — a žiadne `totalItemPrice`.

### Pridanie do košíka (addToCart)

**Stránka obchodu alebo výpisu, klasické AJAX tlačidlo:**

1. Na stránke obchodu klikni na „Do košíka“.
2. Objaví sa `[Barion Pixel] Event: addToCart` s `totalItemPrice` a `step: 1`.

**Stránka produktu, odoslanie formulára:**

1. Klikni na „Do košíka“ a over, že sa udalosť odošle skôr, než stránka prejde inam.
2. Pri variabilnom produkte: najprv zvoľ variant a over, že sa použila jeho cena.

**Blokové plochy (tlačidlá Product Collection, blok Cart):**

1. Pri načítaní sa objaví `[Barion Pixel] Block surfaces detected …`.
2. Pridaj produkt z bloku Product Collection — odošle sa jedna udalosť `addToCart` so správnym množstvom.
3. Zmeň množstvo v bloku Cart — žiadna udalosť `addToCart` sa neodošle.
4. V obchode s nedesatinnou menou, ako je HUF, over, že `unitPrice` je skutočná cena, a nie jej stotina.

### Stránka pokladne (initiateCheckout)

1. Vlož položky do košíka a otvor pokladňu.
2. Objaví sa `[Barion Pixel] Event: initiateCheckout`.
3. Každý prvok `contents` nesie `unit`, `unitPrice` a `totalItemPrice`.
4. `revenue` je medzisúčet + daň, bez dopravy.
5. `step: 1` je prítomný.
6. Zadaj fakturačný e-mail. `setEncryptedEmail sent` sa objaví raz pre každú platnú adresu — nie pri každom stlačení klávesy a nie pri čiastočnom vstupe typu `x@y`.
7. Zopakuj v bloku Checkout, kde e-mail pochádza z dátového úložiska bloku, nie z `#billing_email`.

### Dokončenie objednávky (purchase + setEncryptedEmail)

1. Dokonči testovaciu objednávku — najjednoduchšia je platba „Bankový prevod“.
2. Objaví sa `[Barion Pixel] Event: purchase` a `revenue` zodpovedá celkovej sume objednávky.
3. Objaví sa `setEncryptedEmail sent`.
4. Znovu načítaj stránku potvrdenia — `purchase` sa **neodošle** znovu.
5. Prvky `contents` obsahujú `unit` a `totalItemPrice`.

### Integrácia súhlasu

1. Vymaž všetky cookies.
2. Načítaj ľubovoľnú stránku. Objaví sa `[Barion Pixel] Base pixel initialized` — základný pixel sa zámerne načítava pred akýmkoľvek rozhodnutím o súhlase.
3. Prijmi cookies v lište. Objaví sa `Consent granted (grantConsent)`.
4. Načítaj znovu — súhlas sa udelí pri načítaní, bez lišty.
5. Odvolaj súhlas a over, že sa objaví `Consent rejected (rejectConsent)`.

---

## Časté problémy

### Udalosti sa neodosielajú

- **Pixel ID**: v Nastavenia > Barion Pixel musí byť uložené platné ID.
- **Úplné sledovanie**: e-commerce udalosti vyžadujú zaškrtnuté „Povoliť úplné sledovanie Pixel“.
- **WooCommerce**: úplné sledovanie vyžaduje aktívny WooCommerce.
- **Chyby v konzole**: aj nesúvisiaca chyba JavaScriptu môže zabrániť načítaniu bp.js.

### Dvojité načítanie pixela

`[Barion Pixel] bp.js already loaded by another plugin` znamená, že niečo iné bolo prvé — Barion
Payment Gateway, tag v Google Tag Manageri, kód vložený do šablóny. Je to neškodné: plugin
načítanie skriptu preskočí a aj tak sa inicializuje s tvojím Pixel ID. Pozri
[Kompatibilita](compatibility.md).

### Súhlas sa neudeľuje

- **WP Consent API**: plugin WP Consent API musí byť nainštalovaný a tvoj cookie plugin ho musí podporovať.
- **Cookie Law Info**: plugin musí byť aktívny a globálny objekt `CLI` dostupný.
- **Ručne**: zavolaj `window.wcBarionGrantConsent()` z callbacku svojho správcu súhlasu.

### purchase sa odošle pri nezaplatenej objednávke

Očakávané správanie, popísané pri [purchase](events-reference.md#purchase). Plugin sleduje stránku
potvrdenia objednávky, na ktorú offline platobné metódy prídu skôr, než dorazia peniaze.
