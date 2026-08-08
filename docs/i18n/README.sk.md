> 🌐 Toto je automatický preklad. Komunitné opravy sú vítané!

# Advanced Pixel for Barion

Integrácia Barion Pixel pre WooCommerce s kompletným sledovaním e-commerce udalostí, podporou súhlasu s cookies a kompatibilitou s WP Consent API.

<p align="center">
  <a href="../../README.md">English</a> |
  <a href="README.hu.md">Magyar</a> |
  <a href="README.cs.md">Čeština</a> |
  <strong>Slovenčina</strong> |
  <a href="README.de.md">Deutsch</a> |
  <a href="README.hr.md">Hrvatski</a> |
  <a href="README.ro.md">Română</a> |
  <a href="README.sl.md">Slovenščina</a> |
  <a href="README.sr.md">Srpski</a>
</p>

## Funkcie

- **Základný Barion Pixel**: Načíta sledovací skript Barion na celom webe (pageView sa spúšťa automaticky)
- **Úplné sledovanie udalostí**: Všetky povinné e-commerce udalosti podľa dokumentácie Barion
  - `contentView`: Spúšťa sa na stránkach produktov
  - `addToCart`: Spúšťa sa pri pridaní položiek do košíka (na strane klienta, funguje s ukladaním stránok do cache)
  - `initiateCheckout`: Spúšťa sa pri začatí pokladne
  - `purchase`: Spúšťa sa pri úspešnom dokončení objednávky (s prevenciou duplicít)
  - `setEncryptedEmail`: Posiela fakturačný e-mail do Barion pri nákupe (šifrovaný pomocou bp.js)
- **Integrácia WP Consent API**: Univerzálna podpora súhlasu s cookies — funguje s CookieYes, Complianz, Real Cookie Banner, GDPR Cookie Compliance, Cookie Notice a ďalšími
- **Záložná integrácia Cookie Law Info**: Priama integrácia pre weby používajúce CookieYes/Cookie Law Info
- **Panel nastavení administrátora**: Jednoduchá konfigurácia cez správu WordPress
- **Režim ladenia**: Zaznamenávanie do konzoly pre testovanie a vývoj
- **Detekcia dvojitého načítania bp.js**: Bezpečne koexistuje s inými pluginmi, ktoré načítavajú bp.js (napr. Barion Payment Gateway)

## Inštalácia

1. Nahraj priečinok `advanced-pixel-for-barion` do `/wp-content/plugins/`
2. Aktivuj plugin cez ponuku „Pluginy" vo WordPress
3. Prejdi na Nastavenia > Barion Pixel a nakonfiguruj plugin

## Konfigurácia

### Nastavenia administrátora

Prístup k stránke nastavení v **Nastavenia > Barion Pixel** v správe WordPress.

#### Pixel ID (povinné)
Zadaj svoje Barion Pixel ID (formát: `BP-0000000000-00`). Základný Pixel sa načíta na všetkých stránkach po jeho nastavení.

#### Povoliť úplné sledovanie Pixel
Prepnúť na povolenie/zakázanie sledovania e-commerce udalostí. Ak je zakázané, načíta sa iba základný Pixel (pageView na prevenciu podvodov).

#### Režim ladenia
Povolí zaznamenávanie všetkých udalostí Barion Pixel do konzoly prehliadača na účely testovania.

## Dokumentácia

Podrobná dokumentácia je dostupná v priečinku [`sk/`](sk/):

- [Referencia udalostí](sk/events-reference.md) — Všetky sledované udalosti, polia a typy údajov
- [Integrácia súhlasu s cookies](sk/cookie-consent.md) — WP Consent API, Cookie Law Info a manuálna integrácia
- [Kompatibilita](sk/compatibility.md) — WooCommerce, Barion Payment Gateway, pluginy na ukladanie do cache
- [Poznámky k testovaniu](sk/testing-notes.md) — Zvláštnosti bp.js, režim ladenia, kontrolný zoznam testovania

Dokumentácia je tiež dostupná v [Magyar](../hu/), [Čeština](../cs/), [Deutsch](../de/), [Hrvatski](../hr/), [Română](../ro/), [Slovenščina](../sl/) a [Srpski](../sr/).

### Dokumentácia Barionu

Vlastné príručky Barionu k nastaveniu pixela (v angličtine). Voľba **Enable Full Pixel Tracking** v tomto plugine zodpovedá plnému (Full) Barion Pixelu:

- [Getting started with the Barion Pixel](https://docs.barion.com/Getting_started_with_the_Barion_Pixel)
- [Implementing the Base Barion Pixel](https://docs.barion.com/Implementing_the_Base_Barion_Pixel)
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel)
- [Implementing the Base and Full pixel in WooCommerce webshops](https://docs.barion.com/Implementing-the-barion-base-and-full-pixel-in-woocommerce-webshops)
- [Barion Pixel API reference](https://docs.barion.com/Barion_Pixel_API_reference)
- [Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements)

## Kompatibilita

- **WooCommerce**: Vyžaduje sa pre úplné sledovanie udalostí (základný pixel funguje aj bez neho)
- **Barion Payment Gateway** ([woocommerce-barion](https://github.com/szelpe/woocommerce-barion)): Dokonale koexistuje — tento plugin spracúva platby, tento sleduje pixel
- **Ukladanie stránok do cache**: Plne kompatibilné (addToCart používa JavaScript na strane klienta)
- **Pluginy na cookies**: Automaticky funguje každý plugin kompatibilný s WP Consent API

## Požiadavky

- WordPress 5.0 alebo vyšší
- PHP 7.2 alebo vyšší
- WooCommerce 5.0+ (pre úplné sledovanie udalostí)
- Voliteľné: [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) pre univerzálnu podporu súhlasu s cookies

## Licencia

GPL-2.0-or-later — podrobnosti nájdeš v [LICENSE](../../LICENSE).

## Zoznam zmien

### 1.0.3
- Opravené: `setEncryptedEmail` sa pri jednom načítaní stránky pokladne odoslal viackrát
- Opravené: bp.js odmietal e-maily so znakom `+` v lokálnej časti alebo s TLD dlhšou než štyri písmená (`.museum`, `.online`) chybou `Format of e-mail address or hash is invalid`. Plugin teraz e-mail zahashuje algoritmom SHA-1 priamo v prehliadači, než ho odovzdá bp.js — Barion Pixel API predpočítaný hash namiesto obyčajnej adresy prijíma
- Opravené: čiastočne zadaná hodnota (napríklad `x@y`) sa už do bp.js neposiela
- Opravené: volanie zodpovedá dokumentácii Barionu — `bp('identity', 'setEncryptedEmail', ...)` (predtým `'identify'`)

Verziu 1.0.2 pred vydaním nahradila verzia 1.0.3; jej opravy sú uvedené vyššie.

### 1.0.1
- Opravené: neodosielali sa žiadne udalosti pixela — skript udalostí bol zaradený až po tom, čo `wp_print_footer_scripts` už prebehol
- Opravené: automatická detekcia súhlasu s cookies teraz beží až po `DOMContentLoaded`, takže vidí aj globálne premenné pluginov pre súhlas, ktoré sa načítavajú neskôr
- Nové: `setEncryptedEmail` sa teraz odosiela aj na stránke pokladne — pri prihlásených používateľoch pri načítaní a pri zadaní platného fakturačného e-mailu

### 1.0.0
- Prvé vydanie
- Implementácia základného Barion Pixel (pageView)
- Úplné sledovanie udalostí (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail)
- Integrácia WP Consent API
- Záložná integrácia Cookie Law Info
- Panel nastavení administrátora s režimom ladenia
- addToCart na strane klienta (kompatibilné s ukladaním stránok do cache)
- Podpora variabilných produktov
- Prevencia duplicitných nákupov
- Detekcia dvojitého načítania bp.js
