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

ID nájdeš v Barion peňaženke v **Merchant Management > Details**. Každý obchod má vlastné ID a sandbox aj ostré prostredie vydávajú odlišné. ID začínajúce na `BPT` nie je Pixel ID a nebude fungovať.

#### Povoliť úplné sledovanie Pixel
Prepnúť na povolenie/zakázanie sledovania e-commerce udalostí. Ak je zakázané, načíta sa iba základný Pixel (pageView na prevenciu podvodov).

Barion vyžaduje úplnú implementáciu Pixela a vyhovujúcu lištu súhlasu, než obchod získa výhodnejšie podmienky Barion Smart Gateway alebo prístup k Barion Metrics. Tento plugin pokrýva implementačnú časť; schválenie je na Barione.

#### Režim ladenia
Povolí zaznamenávanie všetkých udalostí Barion Pixel do konzoly prehliadača na účely testovania.

## Dokumentácia

Podrobná dokumentácia je dostupná v priečinku [`sk/`](sk/):

- [Referencia udalostí](sk/events-reference.md) — Všetky sledované udalosti, polia a typy údajov
- [Integrácia súhlasu s cookies](sk/cookie-consent.md) — WP Consent API, Cookie Law Info a manuálna integrácia
- [Kompatibilita](sk/compatibility.md) — WooCommerce, Barion Payment Gateway, pluginy na ukladanie do cache
- [Poznámky k testovaniu](sk/testing-notes.md) — Zvláštnosti bp.js, režim ladenia, kontrolný zoznam testovania

Dokumentácia je tiež dostupná v [Magyar](hu/), [Čeština](cs/), [Deutsch](de/), [Hrvatski](hr/), [Română](ro/), [Slovenščina](sl/) a [Srpski](sr/).

### Dokumentácia Barionu

Vlastné príručky Barionu k nastaveniu pixela (v angličtine). Voľba **Enable Full Pixel Tracking** v tomto plugine zodpovedá plnému (Full) Barion Pixelu:

- [Getting started with the Barion Pixel](https://docs.barion.com/Getting_started_with_the_Barion_Pixel)
- [Implementing the Base Barion Pixel](https://docs.barion.com/Implementing_the_Base_Barion_Pixel)
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel)
- [Implementing the Base and Full pixel in WooCommerce webshops](https://docs.barion.com/Implementing-the-barion-base-and-full-pixel-in-woocommerce-webshops)
- [Barion Pixel event reference](https://docs.barion.com/Barion-pixel-event-reference)
- [Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements)
- [Barion Pixel FAQ](https://docs.barion.com/Frequently_Asked_Questions_about_the_Barion_Pixel)

## Kompatibilita

- **WooCommerce**: Vyžaduje sa pre úplné sledovanie udalostí (základný pixel funguje aj bez neho)
- **Barion Payment Gateway** ([woocommerce-barion](https://github.com/szelpe/woocommerce-barion)): Dokonale koexistuje — tento plugin spracúva platby, tento sleduje pixel
- **Ukladanie stránok do cache**: Plne kompatibilné (addToCart používa JavaScript na strane klienta)
- **Pluginy na cookies**: Automaticky funguje každý plugin kompatibilný s WP Consent API

## Požiadavky

- WordPress 5.0 alebo vyšší
- PHP 7.4 alebo vyšší
- WooCommerce 5.0+ (pre úplné sledovanie udalostí)
- Voliteľné: [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) pre univerzálnu podporu súhlasu s cookies

## Prispievanie

Hlásenia chýb, pull requesty aj preklady sú vítané — pozri [pokyny pre prispievateľov](sk/contributing.md).

## Licencia

GPL-2.0-or-later — podrobnosti nájdeš v [LICENSE](../../LICENSE).

## Zoznam zmien

### 1.0.9
- Opravené: `grantConsent` sa odosielal pri načítaní stránky, a nie vo chvíli, keď návštevník prijal cookie lištu. Práve za to Barion odmieta integráciu Full Pixel: obchod, ktorý hlási súhlas skôr, než ktokoľvek odpovedal, vyzerá rovnako ako ten, ktorý sa nikdy nepýta. Súhlas sa teraz odosiela len za rozhodnutie, ktoré návštevník urobí pri tom načítaní stránky. Vracajúci sa návštevník nevyvolá nič, pretože bp.js si jeho odpoveď drží vo vlastnej cookie a Barion ju už má
- Opravené: pri aktívnom plugine WP Consent API, u ktorého sa však nezaregistrovala žiadna cookie lišta, bol každý návštevník hlásený ako ten, kto marketingový súhlas udelil. Nenastavený typ súhlasu je spôsob, akým toto API hovorí, že ho neriadi žiadna lišta, plugin to však čítal ako skutočnú odpoveď. V tomto stave ho teraz ignoruje
- Nové: stránka nastavení varuje, keď je WP Consent API aktívne, ale nezaregistrovala sa uňho žiadna cookie lišta. Nainštalovať ho vedľa lišty, ktorá ho nepodporuje, nič neprepojí, a doteraz to nikto nepovedal

### 1.0.8
- Opravené: `grantConsent` sa nikdy neodoslal na weboch bez samostatného pluginu WP Consent API, takže Barion neschválil integráciu Full Pixel. Detekcia súhlasu skúšala tri zdroje po sebe a zastavila sa pri prvom náleze, pričom posledný z nich neregistroval žiadny poslucháč. CookieYes, Complianz, Cookiebot a staršia lišta Cookie Law Info sa teraz čítajú priamo, bez ďalšieho pluginu
- Opravené: `grantConsent` chýbal aj u vracajúcich sa návštevníkov, ktorí už na lištu odpovedali, a na každom webe, ktorého správca súhlasu sa načítal až po stránke. Plugin teraz hľadá správcu súhlasu desať sekúnd po načítaní stránky, namiesto jedinej kontroly
- Nové: stránka nastavení upozorní, keď nie je dostupný žiadny správca súhlasu, takže je chybné nastavenie vidieť skôr, než Barion integráciu odmietne

### 1.0.7
- Opravené: fatálna chyba na každom webe, ktorý plugin používal bez WooCommerce, ak bolo vyplnené Pixel ID a zapnuté plné sledovanie. Skript udalostí v pätičke volal funkciu `is_product()`, ktorá existuje iba pri načítanom WooCommerce, takže stránka spadla s chybou `Call to undefined function is_product()`. Hooky udalostí WooCommerce sa teraz registrujú len vtedy, keď je WooCommerce aktívny; základný pixel sa podľa dokumentácie načíta aj bez neho. Chyba pochádza z verzie 1.0.0
- Opravené: poznámka o Pixel ID nastavenom aj v plugine Barion Payment Gateway sa vo všetkých jazykoch zobrazovala anglicky. Text bol v skoršom vydaní preformulovaný, ale preklady sa neaktualizovali

### 1.0.6
- Opravené: `initiateCheckout` a `setEncryptedEmail` sa nikdy neodoslali na bloku Checkout vo WooCommerce, ktorý je od WooCommerce 8.3 predvolený pre nové obchody. Plugin počúval iba PHP hooky klasickej pokladne a jej pole `#billing_email`, a blok nemá ani jedno. Teraz číta dátové úložisko blokov Cart a Checkout; správanie klasickej pokladne sa nemení
- Opravené: `addToCart` sa nikdy neodoslal na stránkach obchodu ani kategórií, a to v žiadnom obchode. Skript udalostí sa načítaval len na stránkach, kde už nejaká udalosť čakala vo fronte, čo pri výpisoch nikdy neplatí, takže poslucháče pridania do košíka chýbali práve tam, kde zákazníci do košíka pridávajú. Chyba pochádza z verzie 1.0.1
- Opravené: `addToCart` teraz funguje aj s blokovými tlačidlami produktov, ktoré používa blok Product Collection. Tie bežia na Interactivity API a nespúšťajú ani klasickú jQuery udalosť, ani dátové úložisko blokov, takže sa obsah košíka číta z WooCommerce Store API

### 1.0.5
- Opravené: pribalené preklady (maďarský, český, slovenský, nemecký, chorvátsky, rumunský, slovinský a srbský) sa nikdy nenačítali, takže obrazovka nastavení zostala v angličtine. WordPress prehľadáva iba `wp-content/languages/plugins`, kým plugin nezaregistruje vlastný adresár, čo tento plugin nerobil. Teraz registruje `languages/` v akcii `init`

### 1.0.4
- Kompatibilita: otestované s WordPress 7.0 a WooCommerce 11.0
- Zmenené: `Requires PHP` zvýšené zo 7.2 na 7.4. WordPress 7.0 ukončil podporu PHP 7.2 a 7.3, takže na 7.2 už plugin nemohol bežať

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
