> 🌐 Toto je automatický překlad. Komunitní opravy jsou vítány!

# Advanced Pixel for Barion

Integrace Barion Pixel pro WooCommerce s kompletním sledováním e-commerce událostí, podporou souhlasu s cookies a kompatibilitou s WP Consent API.

<p align="center">
  <a href="../../README.md">English</a> |
  <a href="README.hu.md">Magyar</a> |
  <strong>Čeština</strong> |
  <a href="README.sk.md">Slovenčina</a> |
  <a href="README.de.md">Deutsch</a> |
  <a href="README.hr.md">Hrvatski</a> |
  <a href="README.ro.md">Română</a> |
  <a href="README.sl.md">Slovenščina</a> |
  <a href="README.sr.md">Srpski</a>
</p>

## Funkce

- **Základní Barion Pixel**: Načítá sledovací skript Barion na celém webu (pageView se spouští automaticky)
- **Kompletní sledování událostí**: Všechny povinné e-commerce události dle dokumentace Barion
  - `contentView`: Spouští se na stránkách produktů
  - `addToCart`: Spouští se při přidání zboží do košíku (na straně klienta, funguje s ukládáním stránek do mezipaměti)
  - `initiateCheckout`: Spouští se při zahájení pokladny
  - `purchase`: Spouští se při úspěšném dokončení objednávky (s ochranou proti duplicitám)
  - `setEncryptedEmail`: Odesílá fakturační e-mail do Barion při nákupu (zašifrovaný pomocí bp.js)
- **Integrace WP Consent API**: Univerzální podpora souhlasu s cookies — funguje s CookieYes, Complianz, Real Cookie Banner, GDPR Cookie Compliance, Cookie Notice a dalšími
- **Záložní integrace Cookie Law Info**: Přímá integrace pro weby používající CookieYes/Cookie Law Info
- **Panel nastavení správce**: Snadná konfigurace přes administraci WordPress
- **Režim ladění**: Protokolování do konzole pro testování a vývoj
- **Detekce dvojitého načtení bp.js**: Bezpečně koexistuje s dalšími pluginy, které načítají bp.js (např. Barion Payment Gateway)

## Instalace

1. Nahrajte složku `advanced-pixel-for-barion` do `/wp-content/plugins/`
2. Aktivujte plugin přes nabídku „Pluginy" ve WordPressu
3. Přejděte do Nastavení > Barion Pixel a nakonfigurujte plugin

## Konfigurace

### Nastavení správce

Přejděte na stránku nastavení v **Nastavení > Barion Pixel** ve správě WordPressu.

#### ID Pixelu (povinné)
Zadejte své Barion Pixel ID (formát: `BP-0000000000-00`). Základní Pixel se načte na všech stránkách, jakmile je toto nastaveno.

ID najdete v Barion peněžence v **Merchant Management > Details**. Každý obchod má vlastní ID a sandbox i ostré prostředí vydávají různá. ID začínající na `BPT` není Pixel ID a nebude fungovat.

#### Povolit kompletní sledování Pixelem
Přepínač pro zapnutí/vypnutí sledování e-commerce událostí. Pokud je vypnuto, načte se pouze základní Pixel (pageView pro prevenci podvodů).

Barion vyžaduje kompletní implementaci Pixelu a vyhovující lištu souhlasu, než obchod získá výhodnější podmínky Barion Smart Gateway nebo přístup k Barion Metrics. Tento plugin pokrývá implementační část; schválení je na Barionu.

#### Režim ladění
Povolte, abyste zaznamenávali všechny události Barion Pixel do konzole prohlížeče pro testování.

## Dokumentace

Podrobná dokumentace je k dispozici ve složce [`cs/`](cs/):

- [Přehled událostí](cs/events-reference.md) — Všechny sledované události, pole a datové typy
- [Integrace souhlasu s cookies](cs/cookie-consent.md) — WP Consent API, Cookie Law Info a ruční integrace
- [Kompatibilita](cs/compatibility.md) — WooCommerce, Barion Payment Gateway, pluginy pro ukládání do mezipaměti
- [Poznámky k testování](cs/testing-notes.md) — Specifika bp.js, režim ladění, kontrolní seznam testování

Dokumentace je také dostupná v jazycích [Magyar](hu/), [Čeština](cs/), [Slovenčina](sk/), [Deutsch](de/), [Hrvatski](hr/), [Română](ro/), [Slovenščina](sl/) a [Srpski](sr/).

### Dokumentace Barionu

Vlastní příručky Barionu k nastavení pixelu (v angličtině). Volba **Enable Full Pixel Tracking** v tomto pluginu odpovídá plnému (Full) Barion Pixelu:

- [Getting started with the Barion Pixel](https://docs.barion.com/Getting_started_with_the_Barion_Pixel)
- [Implementing the Base Barion Pixel](https://docs.barion.com/Implementing_the_Base_Barion_Pixel)
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel)
- [Implementing the Base and Full pixel in WooCommerce webshops](https://docs.barion.com/Implementing-the-barion-base-and-full-pixel-in-woocommerce-webshops)
- [Barion Pixel event reference](https://docs.barion.com/Barion-pixel-event-reference)
- [Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements)
- [Barion Pixel FAQ](https://docs.barion.com/Frequently_Asked_Questions_about_the_Barion_Pixel)

## Kompatibilita

- **WooCommerce**: Vyžadováno pro kompletní sledování událostí (základní pixel funguje i bez něj)
- **Barion Payment Gateway** ([woocommerce-barion](https://github.com/szelpe/woocommerce-barion)): Bezkonfliktní koexistence — tento plugin zpracovává platby, zatímco náš plugin se stará o sledování pixelem
- **Ukládání stránek do mezipaměti**: Plně kompatibilní (addToCart používá JavaScript na straně klienta)
- **Pluginy pro cookies**: Jakýkoli plugin kompatibilní s WP Consent API funguje automaticky

## Požadavky

- WordPress 5.0 nebo vyšší
- PHP 7.4 nebo vyšší
- WooCommerce 5.0+ (pro kompletní sledování událostí)
- Volitelné: [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) pro univerzální podporu souhlasu s cookies

## Přispívání

Hlášení chyb, pull requesty i překlady jsou vítány — viz [pokyny pro přispěvatele](cs/contributing.md).

## Licence

GPL-2.0-or-later — viz [LICENSE](../../LICENSE) pro podrobnosti.

## Historie změn

### 1.0.9
- Opraveno: `grantConsent` se odesílal při načtení stránky, a ne ve chvíli, kdy návštěvník přijal cookie lištu. Právě za to Barion odmítá integraci Full Pixel: obchod, který hlásí souhlas dřív, než kdokoli odpověděl, vypadá stejně jako ten, který se nikdy neptá. Souhlas se nyní odesílá jen za rozhodnutí, které návštěvník učiní při tom načtení stránky. Vracející se návštěvník nevyvolá nic, protože bp.js si jeho odpověď drží ve vlastní cookie a Barion ji už má
- Opraveno: při aktivním pluginu WP Consent API, u kterého se ale nezaregistrovala žádná cookie lišta, byl každý návštěvník hlášen jako ten, kdo marketingový souhlas udělil. Nenastavený typ souhlasu je způsob, jakým toto API říká, že jej neřídí žádná lišta, plugin to však četl jako skutečnou odpověď. V tomto stavu je nyní ignoruje
- Nové: stránka nastavení varuje, když je WP Consent API aktivní, ale nezaregistrovala se u něj žádná cookie lišta. Nainstalovat je vedle lišty, která je nepodporuje, nic nepropojí, a dosud to nikdo neřekl

### 1.0.8
- Opraveno: `grantConsent` se nikdy neodeslal na webech bez samostatného pluginu WP Consent API, takže Barion neschválil integraci Full Pixel. Detekce souhlasu zkoušela tři zdroje po sobě a zastavila se u prvního nálezu, přičemž poslední z nich neregistroval žádný posluchač. CookieYes, Complianz, Cookiebot a starší lišta Cookie Law Info se nyní čtou přímo, bez dalšího pluginu
- Opraveno: `grantConsent` chyběl také u vracejících se návštěvníků, kteří už na lištu odpověděli, a na každém webu, jehož správce souhlasu se načetl až po stránce. Plugin nyní hledá správce souhlasu deset sekund po načtení stránky, místo jediné kontroly
- Nové: stránka nastavení upozorní, když není dostupný žádný správce souhlasu, takže je chybné nastavení vidět dříve, než Barion integraci odmítne

### 1.0.7
- Opraveno: fatální chyba na každém webu, který plugin používal bez WooCommerce, pokud bylo vyplněno Pixel ID a zapnuté plné sledování. Skript událostí v patičce volal funkci `is_product()`, která existuje pouze při načteném WooCommerce, takže stránka spadla s chybou `Call to undefined function is_product()`. Hooky událostí WooCommerce se nyní registrují jen tehdy, když je WooCommerce aktivní; základní pixel se podle dokumentace načítá i bez něj. Chyba pochází z verze 1.0.0
- Opraveno: poznámka o Pixel ID nastaveném také v pluginu Barion Payment Gateway se ve všech jazycích zobrazovala anglicky. Text byl v dřívějším vydání přeformulován, ale překlady se neaktualizovaly

### 1.0.6
- Opraveno: `initiateCheckout` a `setEncryptedEmail` se nikdy neodeslaly na bloku Checkout ve WooCommerce, který je od WooCommerce 8.3 výchozí pro nové obchody. Plugin naslouchal pouze PHP hookům klasické pokladny a jejímu poli `#billing_email`, a blok nemá ani jedno. Nyní čte datové úložiště bloků Cart a Checkout; chování klasické pokladny se nemění
- Opraveno: `addToCart` se nikdy neodeslal na stránkách obchodu ani kategorií, a to v žádném obchodě. Skript událostí se načítal jen na stránkách, kde už nějaká událost čekala ve frontě, což u výpisů nikdy neplatí, takže posluchače přidání do košíku chyběly právě tam, kde zákazníci do košíku přidávají. Chyba pochází z verze 1.0.1
- Opraveno: `addToCart` nyní funguje i s blokovými tlačítky produktů, která používá blok Product Collection. Ta běží na Interactivity API a nespouštějí ani klasickou jQuery událost, ani datové úložiště bloků, takže se obsah košíku čte z WooCommerce Store API

### 1.0.5
- Opraveno: přibalené překlady (maďarský, český, slovenský, německý, chorvatský, rumunský, slovinský a srbský) se nikdy nenačetly, takže obrazovka nastavení zůstala v angličtině. WordPress prohledává pouze `wp-content/languages/plugins`, dokud plugin neregistruje vlastní adresář, což tento plugin nedělal. Nyní registruje `languages/` v akci `init`

### 1.0.4
- Kompatibilita: otestováno s WordPress 7.0 a WooCommerce 11.0
- Změněno: `Requires PHP` zvýšeno ze 7.2 na 7.4. WordPress 7.0 ukončil podporu PHP 7.2 a 7.3, takže na 7.2 už plugin nemohl běžet

### 1.0.3
- Opraveno: `setEncryptedEmail` se při jednom načtení stránky pokladny odeslal několikrát
- Opraveno: bp.js odmítal e-maily se znakem `+` v lokální části nebo s TLD delší než čtyři písmena (`.museum`, `.online`) chybou `Format of e-mail address or hash is invalid`. Plugin nyní e-mail hashuje algoritmem SHA-1 přímo v prohlížeči, než jej předá bp.js — Barion Pixel API předpočítaný hash místo prosté adresy přijímá
- Opraveno: částečně zadaná hodnota (například `x@y`) se už do bp.js neodesílá
- Opraveno: volání odpovídá dokumentaci Barionu — `bp('identity', 'setEncryptedEmail', ...)` (dříve `'identify'`)

Verze 1.0.2 byla před vydáním nahrazena verzí 1.0.3; její opravy jsou uvedeny výše.

### 1.0.1
- Opraveno: neodesílaly se žádné události pixelu — skript událostí byl zařazen až poté, co `wp_print_footer_scripts` už proběhl
- Opraveno: automatická detekce souhlasu s cookies nyní běží až po `DOMContentLoaded`, takže vidí i globální proměnné pluginů pro souhlas, které se načítají později
- Nové: `setEncryptedEmail` se nyní odesílá i na stránce pokladny — u přihlášených uživatelů při načtení a při zadání platného fakturačního e-mailu

### 1.0.0
- První vydání
- Implementace základního Barion Pixelu (pageView)
- Kompletní sledování událostí (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail)
- Integrace WP Consent API
- Záložní integrace Cookie Law Info
- Panel nastavení správce s režimem ladění
- addToCart na straně klienta (kompatibilní s ukládáním stránek do mezipaměti)
- Podpora variabilních produktů
- Ochrana proti duplicitním nákupům
- Detekce dvojitého načtení bp.js
