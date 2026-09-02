> 🌐 Toto je automatický překlad. Komunitní opravy jsou vítány!
>
> [English version](../../testing-notes.md)

# Poznámky k testování a známé zvláštnosti

## Než usoudíte, že pixel nefunguje

### „Testing message“ není chyba

Otevřete konzoli na stránce s pixelem a bp.js napíše buď **„Testing message“**, nebo
**„Sending message“**. Barion
[rozdíl dokumentuje](https://docs.barion.com/Implementing_the_Base_Barion_Pixel): čerstvě
nasazený pixel ještě není oprávněn odesílat uživatelská data, takže bp.js píše „Testing message“
a přenáší pouze typ události. Jakmile Barion pixel schválí, přepne se na „Sending message“.

Plugin na tom nic nemění. Pokud vaše události v konzoli vypadají správně, ale Barion žádná data
nevidí, pixel nejspíš stále čeká na schválení na straně Barionu — implementaci prochází člověk,
takže se ozvěte, až budete hotoví.

### ID Pixelu musí být to správné

- Najdete jej v Barion peněžence v **Merchant Management > Details**. Každý obchod, tedy každý POSKey, má vlastní ID Pixelu.
- Formát je `BP-` + deset znaků + `-` + dvě číslice. ID, které začíná na `BPT`, není ID Pixelu a fungovat nebude.
- Sandbox a ostré prostředí vydávají **odlišná** ID Pixelu. Testovací web s ostrým ID znečišťuje reálná data; ostrý web se sandboxovým ID nezaznamená nic užitečného.

Pokud chcete obchod na jedno použití, stránka Barionu
[Creating a shop](https://docs.barion.com/Creating_a_shop) vás provede sandboxem, kde se obchody
schvalují automaticky.

---

## Zvláštnosti běhové kontroly v bp.js

bp.js kontroluje data událostí v prohlížeči a na několika místech jsou jeho pravidla přísnější
nebo volnější, než [reference událostí](https://docs.barion.com/Barion-pixel-event-reference)
naznačuje. Tyto případy vyplynuly z testování na staging prostředí.

### totalItemPrice: u contentView odmítáno, v prvcích contents povinné

- **contentView** (plochá událost): bp.js `totalItemPrice` **odmítá** s chybou `Invalid key totalItemPrice in contentView event`. Reference souhlasí — není to vlastnost contentView.
- Prvky `contents` událostí **initiateCheckout** a **purchase**: bp.js jej **vyžaduje**, jinak hlásí `Mandatory key totalItemPrice is missing from contents event`. I zde reference souhlasí.

Pravidlo palce: `totalItemPrice` je u plochých událostí neplatné a uvnitř prvků `contents` povinné.

### unit je v prvcích contents povinné

Při vynechání se objeví `Mandatory key unit is missing from contents event`.

### step

Plugin posílá `step: 1` u událostí `addToCart`, `initiateCheckout` a `purchase`. Barion
dokumentuje `1` jako krok zahájení pokladny a u `purchase` požaduje nejvyšší číslo kroku, které
používáte — u jednokrokové pokladny tedy také `1`. U `addToCart` je `step` volitelné.

---

## Režim ladění

Zapněte jej v **Nastavení > Barion Pixel**, aby se každá událost zapsala do konzole prohlížeče.

### Co hledat

Otevřete konzoli (F12 > Konzole) a hledejte zprávy `[Barion Pixel]`:

```
[Barion Pixel] bp.js loaded by Advanced Pixel for Barion
[Barion Pixel] Base pixel initialized with ID: BP-xxxxxxxxxx-xx
[Barion Pixel] Consent manager detected: WP Consent API
[Barion Pixel] Block surfaces detected (cart store: true, product buttons: false)
[Barion Pixel] Event: contentView { contentType: "Product", ... }
[Barion Pixel] Event: addToCart { contentType: "Product", ... }
[Barion Pixel] Event: initiateCheckout { contents: [...], ... }
[Barion Pixel] Event: purchase { contents: [...], ... }
[Barion Pixel] setEncryptedEmail sent
```

Úplný seznam zpráv o souhlasu najdete v
[Integraci souhlasu s cookies](cookie-consent.md).

### Chyby bp.js

bp.js zapisuje i vlastní chyby kontroly. Ty běžné:

| Chyba | Význam | Řešení |
|-------|--------|--------|
| `Mandatory key X is missing from Y event` | Povinné pole se neodesílá | Zkontrolujte data události |
| `Invalid key X in Y event` | Odesílá se pole, které bp.js nečeká | Pole odstraňte |
| `Format of e-mail address or hash is invalid` | bp.js odmítl hodnotu předanou do `setEncryptedEmail` | Od 1.0.3 plugin adresu předem hashuje, takže by se to už objevovat nemělo |

---

## Kontrolní seznam testování

Projděte jej v klasickém i blokovém obchodě — pro `addToCart`, `initiateCheckout` a
`setEncryptedEmail` používají zcela odlišné cesty kódu.

### Stránka produktu (contentView)

1. Otevřete stránku produktu s otevřenou konzolí.
2. Objeví se `[Barion Pixel] Event: contentView`.
3. Žádné chyby bp.js o chybějících nebo neplatných klíčích.
4. Přítomná pole: `contentType`, `currency`, `id`, `name`, `quantity`, `unit`, `unitPrice` — a žádné `totalItemPrice`.

### Přidání do košíku (addToCart)

**Stránka obchodu nebo výpisu, klasické AJAX tlačítko:**

1. Na stránce obchodu klikněte na „Do košíku“.
2. Objeví se `[Barion Pixel] Event: addToCart` s `totalItemPrice` a `step: 1`.
3. `unitPrice` je skutečná cena, ne `0`. Tlačítko cenu nenese, takže pochází ze Store API; `0` znamená, že tento požadavek selhal.

**Stránka produktu, odeslání formuláře:**

1. Klikněte na „Do košíku“ a ověřte, že se událost odešle dřív, než stránka přejde jinam.
2. U variabilního produktu: nejprve zvolte variantu a ověřte, že se použila její cena.

**Blokové plochy (tlačítka Product Collection, blok Cart):**

1. Při načtení se objeví `[Barion Pixel] Block surfaces detected …`.
2. Přidejte produkt z bloku Product Collection — odešle se jedna událost `addToCart` se správným množstvím.
3. Změňte množství v bloku Cart — žádná událost `addToCart` se neodešle.
4. V obchodě s nedesetinnou měnou, jako je HUF, ověřte, že `unitPrice` je skutečná cena, a ne její setina.

### Stránka pokladny (initiateCheckout)

1. Vložte položky do košíku a otevřete pokladnu.
2. Objeví se `[Barion Pixel] Event: initiateCheckout`.
3. Každý prvek `contents` nese `unit`, `unitPrice` a `totalItemPrice`.
4. `revenue` je mezisoučet + daň, bez dopravy.
5. `step: 1` je přítomen.
6. Zadejte fakturační e-mail. `setEncryptedEmail sent` se objeví jednou pro každou platnou adresu — ne při každém stisku klávesy a ne u částečného vstupu typu `x@y`.
7. Zopakujte v bloku Checkout, kde e-mail pochází z datového úložiště bloku, nikoli z `#billing_email`.

### Dokončení objednávky (purchase + setEncryptedEmail)

1. Dokončete testovací objednávku — nejjednodušší je platba „Bankovní převod“.
2. Objeví se `[Barion Pixel] Event: purchase` a `revenue` odpovídá celkové částce objednávky.
3. Objeví se `setEncryptedEmail sent`.
4. Znovu načtěte stránku potvrzení — `purchase` se **neodešle** znovu.
5. Prvky `contents` obsahují `unit` a `totalItemPrice`.

### Integrace souhlasu

1. Smažte všechny cookies. Na tom záleží — kontrola níže funguje jen u návštěvníka, kterého se lišta ještě musí zeptat.
2. Načtěte libovolnou stránku. Objeví se `[Barion Pixel] Base pixel initialized` — základní pixel se záměrně načítá před jakýmkoli rozhodnutím o souhlasu.
3. Zatím se ničeho nedotýkejte. Nesmí se objevit žádný `grantConsent`. Barion odmítá integraci, která posílá souhlas při načtení stránky.
4. Přijměte cookies v liště. Teprve teď se objeví `Consent granted (grantConsent)`.
5. Načtěte znovu. Tentokrát se neodešle nic a konzole uvede, že souhlas už při načtení stránky existoval. bp.js si odpověď drží ve vlastní cookie, Barion ji tedy už má.
6. Odvolejte souhlas a ověřte, že se objeví `Consent rejected (rejectConsent)`.

---

## Časté problémy

### Události se neodesílají

- **ID Pixelu**: v Nastavení > Barion Pixel musí být uloženo platné ID.
- **Kompletní sledování**: e-commerce události vyžadují zaškrtnuté „Povolit kompletní sledování Pixelem“.
- **WooCommerce**: kompletní sledování vyžaduje aktivní WooCommerce.
- **Chyby v konzoli**: i nesouvisející chyba JavaScriptu může zabránit načtení bp.js.

### Dvojí načtení pixelu

`[Barion Pixel] bp.js already loaded by another plugin` znamená, že něco jiného bylo první —
Barion Payment Gateway, tag v Google Tag Manageru, kód vložený do šablony. Je to neškodné: plugin
načtení skriptu přeskočí a stejně se inicializuje s vaším ID Pixelu. Viz
[Kompatibilita](compatibility.md).

### Souhlas se neuděluje

Právě kvůli této chybě Barion odmítá integraci Full Pixel, takže ji zkontrolujte jako první.
Se zapnutým Debug Mode vám konzole řekne, ve kterém případě jste.

- `Consent manager detected: …`, ale po přijetí žádný `grantConsent` — správce byl nalezen, ale nehlásí marketingový souhlas. Ověřte, že jste přijali právě marketingovou či reklamní kategorii své lišty.
- `Marketing consent already stood when this page loaded` — nic není špatně. Testujete jako vracející se návštěvník. Smažte cookies a začněte znovu od kroku 1.
- `No consent manager detected`, zatímco je plugin WP Consent API aktivní — API je nainstalované, ale vaše cookie lišta se u něj neregistruje, takže hlásí souhlas jako udělený pro každého a plugin je ignoruje. Stránka nastavení říká totéž. Propojte lištu s API, nebo funkce zavolejte sami.
- `No consent manager detected` — plugin nenašel nic ke čtení. Tento řádek se objeví deset sekund po načtení stránky, ne okamžitě, protože správce souhlasu servírovaný z CDN se může objevit až tak pozdě. CookieYes, Complianz, Cookiebot a starší Cookie Law Info se čtou přímo. U jakékoli jiné lišty nainstalujte [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) nebo zavolejte `window.wcBarionGrantConsent()` z callbacku přijetí své lišty.
- V konzoli vůbec nic — základní skript se nespustil. Mohl jej zablokovat plugin souhlasu, který blokuje neznámé skripty. Barion žádá, aby se základní pixel načetl bez ohledu na souhlas, přidejte jej tedy na seznam povolených ve svém blokátoru.

Při načtení stránky, kde souhlas ještě nebyl udělen, zůstává plugin zticha. Je to záměr:
`rejectConsent` znamená, že návštěvník řekl ne, ne že ještě neodpověděl.

### purchase se odešle u nezaplacené objednávky

Očekávané chování, popsané u [purchase](events-reference.md#purchase). Plugin sleduje stránku
potvrzení objednávky, na kterou offline platební metody dorazí dřív, než přijdou peníze.
