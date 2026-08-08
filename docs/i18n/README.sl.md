> 🌐 To je samodejni prevod. Popravki skupnosti so dobrodošli!

# Advanced Pixel for Barion

Integracija Barion Pixel za WooCommerce s popolnim sledenjem dogodkov e-trgovine, podporo za soglasje s piškotki in združljivostjo z WP Consent API.

<p align="center">
  <a href="../../README.md">English</a> |
  <a href="README.hu.md">Magyar</a> |
  <a href="README.cs.md">Čeština</a> |
  <a href="README.sk.md">Slovenčina</a> |
  <a href="README.de.md">Deutsch</a> |
  <a href="README.hr.md">Hrvatski</a> |
  <a href="README.ro.md">Română</a> |
  <strong>Slovenščina</strong> |
  <a href="README.sr.md">Srpski</a>
</p>

## Funkcionalnosti

- **Osnovni Barion Pixel**: Naloži skript za sledenje Barion po celotnem spletnem mestu (pageView se sproži samodejno)
- **Popolno sledenje dogodkov**: Vsi obvezni dogodki e-trgovine po dokumentaciji Barion
  - `contentView`: Sproži se na straneh posameznih izdelkov
  - `addToCart`: Sproži se, ko so izdelki dodani v košarico (na strani odjemalca, deluje z medpomnjenjem strani)
  - `initiateCheckout`: Sproži se, ko se začne nakup
  - `purchase`: Sproži se ob uspešnem zaključku naročila (s preprečevanjem podvajanja)
  - `setEncryptedEmail`: Pošlje e-poštni naslov za zaračunavanje Barionu ob nakupu (šifrira bp.js)
- **Integracija WP Consent API**: Univerzalna podpora za soglasje s piškotki — deluje s CookieYes, Complianz, Real Cookie Banner, GDPR Cookie Compliance, Cookie Notice in drugimi
- **Nadomestna integracija Cookie Law Info**: Neposredna integracija za spletna mesta, ki uporabljajo CookieYes/Cookie Law Info
- **Skrbniška plošča z nastavitvami**: Enostavna konfiguracija prek skrbniškega vmesnika WordPress
- **Način za odpravljanje napak**: Beleženje v konzolo za testiranje in razvoj
- **Zaznavanje dvojnega nalaganja bp.js**: Varno sobivanje z drugimi vtičniki, ki nalagajo bp.js (npr. Barion Payment Gateway)

## Namestitev

1. Naloži mapo `advanced-pixel-for-barion` v `/wp-content/plugins/`
2. Aktiviraj vtičnik prek menija 'Vtičniki' v WordPress
3. Pojdi na Nastavitve > Barion Pixel za konfiguracijo

## Konfiguracija

### Skrbniške nastavitve

Do strani z nastavitvami dostopi pri **Nastavitve > Barion Pixel** v skrbniškem vmesniku WordPress.

#### ID piksla (obvezno)
Vnesi svoj Barion Pixel ID (oblika: `BP-0000000000-00`). Osnovni piksel se bo naložil na vseh straneh, ko je to nastavljeno.

#### Omogoči popolno sledenje piksla
Preklopi za omogočanje/onemogočanje sledenja dogodkov e-trgovine. Ko je onemogočeno, se naloži samo osnovni piksel (pageView za preprečevanje goljufij).

#### Način za odpravljanje napak
Omogoči za beleženje vseh dogodkov Barion Pixel v konzolo brskalnika za testiranje.

## Dokumentacija

Podrobna dokumentacija je na voljo v mapi [`sl/`](sl/):

- [Referenca dogodkov](sl/events-reference.md) — Vsi sledeni dogodki, polja in tipi podatkov
- [Integracija soglasja s piškotki](sl/cookie-consent.md) — WP Consent API, Cookie Law Info in ročna integracija
- [Združljivost](sl/compatibility.md) — WooCommerce, Barion Payment Gateway, vtičniki za medpomnjenje
- [Opombe za testiranje](sl/testing-notes.md) — Posebnosti bp.js, način za odpravljanje napak, kontrolni seznam testiranja

Dokumentacija je na voljo tudi v jezikih [Magyar](../hu/), [Čeština](../cs/), [Slovenčina](../sk/), [Deutsch](../de/), [Hrvatski](../hr/), [Română](../ro/), [Slovenščina](../sl/) in [Srpski](../sr/).

### Dokumentacija Barion

Barionova lastna navodila za nastavitev piksla (v angleščini). Možnost **Enable Full Pixel Tracking** v tem vtičniku ustreza polnemu (Full) Barion Pixel:

- [Getting started with the Barion Pixel](https://docs.barion.com/Getting_started_with_the_Barion_Pixel)
- [Implementing the Base Barion Pixel](https://docs.barion.com/Implementing_the_Base_Barion_Pixel)
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel)
- [Implementing the Base and Full pixel in WooCommerce webshops](https://docs.barion.com/Implementing-the-barion-base-and-full-pixel-in-woocommerce-webshops)
- [Barion Pixel API reference](https://docs.barion.com/Barion_Pixel_API_reference)
- [Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements)

## Združljivost

- **WooCommerce**: Potreben za popolno sledenje dogodkov (osnovni piksel deluje brez njega)
- **Barion Payment Gateway** ([woocommerce-barion](https://github.com/szelpe/woocommerce-barion)): Brezkonfliktno sobivanje — ta vtičnik obravnava plačila, ta pa sledenje piksla
- **Medpomnjenje strani**: Popolnoma združljivo (addToCart uporablja JavaScript na strani odjemalca)
- **Vtičniki za piškotke**: Vsi vtičniki, združljivi z WP Consent API, delujejo samodejno

## Zahteve

- WordPress 5.0 ali novejši
- PHP 7.2 ali novejši
- WooCommerce 5.0+ (za popolno sledenje dogodkov)
- Izbirno: [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) za univerzalno podporo soglasja s piškotki

## Licenca

GPL-2.0-or-later — glejte [LICENSE](../../LICENSE) za podrobnosti.

## Dnevnik sprememb

### 1.0.3
- Popravljeno: `setEncryptedEmail` se je ob enem samem nalaganju strani blagajne poslal večkrat
- Popravljeno: bp.js je z napako `Format of e-mail address or hash is invalid` zavrnil e-poštne naslove z znakom `+` v lokalnem delu ali s TLD, daljšo od štirih črk (`.museum`, `.online`). Vtičnik zdaj naslov v brskalniku zgosti z algoritmom SHA-1, preden ga preda bp.js — Barion Pixel API namesto navadnega naslova sprejme vnaprej izračunano zgoščeno vrednost
- Popravljeno: delni vnos (na primer `x@y`) se ne pošilja več v bp.js
- Popravljeno: klic je usklajen z dokumentacijo Barion — `bp('identity', 'setEncryptedEmail', ...)` (prej `'identify'`)

Različico 1.0.2 je pred izdajo nadomestila 1.0.3; njeni popravki so navedeni zgoraj.

### 1.0.1
- Popravljeno: noben dogodek piksla se ni poslal — skripta dogodkov je bila uvrščena v vrsto šele potem, ko se je `wp_print_footer_scripts` že izvedel
- Popravljeno: samodejno zaznavanje soglasja s piškotki se zdaj izvede po `DOMContentLoaded`, zato vidi tudi globalne spremenljivke vtičnikov, ki se naložijo pozneje
- Novo: `setEncryptedEmail` se zdaj pošlje tudi na strani blagajne — pri prijavljenih uporabnikih ob nalaganju in ko kupec vnese veljaven e-poštni naslov za račun

### 1.0.0
- Začetna izdaja
- Implementacija osnovnega Barion Pixel (pageView)
- Popolno sledenje dogodkov (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail)
- Integracija WP Consent API
- Nadomestna integracija Cookie Law Info
- Skrbniška plošča z nastavitvami in načinom za odpravljanje napak
- addToCart na strani odjemalca (združljivo z medpomnjenjem strani)
- Podpora za spremenljive izdelke
- Preprečevanje podvajanja nakupov
- Zaznavanje dvojnega nalaganja bp.js
