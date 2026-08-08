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

#### Povolit kompletní sledování Pixelem
Přepínač pro zapnutí/vypnutí sledování e-commerce událostí. Pokud je vypnuto, načte se pouze základní Pixel (pageView pro prevenci podvodů).

#### Režim ladění
Povolte, abyste zaznamenávali všechny události Barion Pixel do konzole prohlížeče pro testování.

## Dokumentace

Podrobná dokumentace je k dispozici ve složce [`cs/`](cs/):

- [Přehled událostí](cs/events-reference.md) — Všechny sledované události, pole a datové typy
- [Integrace souhlasu s cookies](cs/cookie-consent.md) — WP Consent API, Cookie Law Info a ruční integrace
- [Kompatibilita](cs/compatibility.md) — WooCommerce, Barion Payment Gateway, pluginy pro ukládání do mezipaměti
- [Poznámky k testování](cs/testing-notes.md) — Specifika bp.js, režim ladění, kontrolní seznam testování

Dokumentace je také dostupná v jazycích [Magyar](../hu/), [Čeština](../cs/), [Slovenčina](../sk/), [Deutsch](../de/), [Hrvatski](../hr/), [Română](../ro/), [Slovenščina](../sl/) a [Srpski](../sr/).

### Dokumentace Barionu

Vlastní příručky Barionu k nastavení pixelu (v angličtině). Volba **Enable Full Pixel Tracking** v tomto pluginu odpovídá plnému (Full) Barion Pixelu:

- [Getting started with the Barion Pixel](https://docs.barion.com/Getting_started_with_the_Barion_Pixel)
- [Implementing the Base Barion Pixel](https://docs.barion.com/Implementing_the_Base_Barion_Pixel)
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel)
- [Implementing the Base and Full pixel in WooCommerce webshops](https://docs.barion.com/Implementing-the-barion-base-and-full-pixel-in-woocommerce-webshops)
- [Barion Pixel API reference](https://docs.barion.com/Barion_Pixel_API_reference)
- [Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements)

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

## Licence

GPL-2.0-or-later — viz [LICENSE](../../LICENSE) pro podrobnosti.

## Historie změn

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
