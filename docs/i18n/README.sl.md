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

ID najdeš v svoji denarnici Barion pod **Merchant Management > Details**. Vsaka trgovina ima svojega, peskovnik in produkcijsko okolje pa izdata različna. ID, ki se začne z `BPT`, ni Pixel ID in ne bo deloval.

#### Omogoči popolno sledenje piksla
Preklopi za omogočanje/onemogočanje sledenja dogodkov e-trgovine. Ko je onemogočeno, se naloži samo osnovni piksel (pageView za preprečevanje goljufij).

Barion zahteva popolno implementacijo piksla in skladno pasico za soglasje, preden trgovina dobi ugodnejše pogoje za Barion Smart Gateway ali dostop do Barion Metrics. Ta vtičnik pokriva implementacijo; odobritev je v rokah Bariona.

#### Način za odpravljanje napak
Omogoči za beleženje vseh dogodkov Barion Pixel v konzolo brskalnika za testiranje.

## Dokumentacija

Podrobna dokumentacija je na voljo v mapi [`sl/`](sl/):

- [Referenca dogodkov](sl/events-reference.md) — Vsi sledeni dogodki, polja in tipi podatkov
- [Integracija soglasja s piškotki](sl/cookie-consent.md) — WP Consent API, Cookie Law Info in ročna integracija
- [Združljivost](sl/compatibility.md) — WooCommerce, Barion Payment Gateway, vtičniki za medpomnjenje
- [Opombe za testiranje](sl/testing-notes.md) — Posebnosti bp.js, način za odpravljanje napak, kontrolni seznam testiranja

Dokumentacija je na voljo tudi v jezikih [Magyar](hu/), [Čeština](cs/), [Slovenčina](sk/), [Deutsch](de/), [Hrvatski](hr/), [Română](ro/), [Slovenščina](sl/) in [Srpski](sr/).

### Dokumentacija Barion

Barionova lastna navodila za nastavitev piksla (v angleščini). Možnost **Enable Full Pixel Tracking** v tem vtičniku ustreza polnemu (Full) Barion Pixel:

- [Getting started with the Barion Pixel](https://docs.barion.com/Getting_started_with_the_Barion_Pixel)
- [Implementing the Base Barion Pixel](https://docs.barion.com/Implementing_the_Base_Barion_Pixel)
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel)
- [Implementing the Base and Full pixel in WooCommerce webshops](https://docs.barion.com/Implementing-the-barion-base-and-full-pixel-in-woocommerce-webshops)
- [Barion Pixel event reference](https://docs.barion.com/Barion-pixel-event-reference)
- [Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements)
- [Barion Pixel FAQ](https://docs.barion.com/Frequently_Asked_Questions_about_the_Barion_Pixel)

## Združljivost

- **WooCommerce**: Potreben za popolno sledenje dogodkov (osnovni piksel deluje brez njega)
- **Barion Payment Gateway** ([woocommerce-barion](https://github.com/szelpe/woocommerce-barion)): Brezkonfliktno sobivanje — ta vtičnik obravnava plačila, ta pa sledenje piksla
- **Medpomnjenje strani**: Popolnoma združljivo (addToCart uporablja JavaScript na strani odjemalca)
- **Vtičniki za piškotke**: Vsi vtičniki, združljivi z WP Consent API, delujejo samodejno

## Zahteve

- WordPress 5.0 ali novejši
- PHP 7.4 ali novejši
- WooCommerce 5.0+ (za popolno sledenje dogodkov)
- Izbirno: [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) za univerzalno podporo soglasja s piškotki

## Prispevki

Prijave napak, pull requesti in prevodi so dobrodošli — glej [vodnik za prispevke](sl/contributing.md).

## Licenca

GPL-2.0-or-later — glejte [LICENSE](../../LICENSE) za podrobnosti.

## Dnevnik sprememb

### 1.0.9
- Popravljeno: `grantConsent` se je pošiljal ob nalaganju strani, ne pa takrat, ko je obiskovalec sprejel pasico za piškotke. Prav zaradi tega Barion zavrne integracijo Full Pixel: trgovina, ki javi soglasje, preden je kdor koli odgovoril, je videti enako kot tista, ki nikoli ne vpraša. Soglasje se zdaj pošlje samo za odločitev, ki jo obiskovalec sprejme ob tem nalaganju strani. Vračajoči se obiskovalec ne sproži ničesar, ker bp.js hrani njegov odgovor v svojem piškotku in ga Barion že ima
- Popravljeno: ob aktivnem vtičniku WP Consent API, pri katerem se ni registrirala nobena pasica za piškotke, je bil vsak obiskovalec javljen, kot da je dal trženjsko soglasje. Nenastavljena vrsta soglasja je način, kako ta API pove, da ga ne poganja nobena pasica, vtičnik pa je to bral kot resničen odgovor. V tem stanju ga zdaj prezre
- Novo: stran z nastavitvami opozori, kadar je WP Consent API aktiven, a se pri njem ne registrira nobena pasica za piškotke. Namestitev poleg pasice, ki ga ne podpira, ničesar ne poveže, doslej pa tega ni povedalo nič

### 1.0.8
- Popravljeno: `grantConsent` se nikoli ni poslal na straneh brez ločenega vtičnika WP Consent API, zato Barion ni odobril integracije Full Pixel. Zaznavanje privolitve je zaporedoma preizkusilo tri vire in se ustavilo pri prvem najdenem, zadnji med njimi pa ni registriral nobenega poslušalca. CookieYes, Complianz, Cookiebot in stara pasica Cookie Law Info se zdaj berejo neposredno, brez dodatnega vtičnika
- Popravljeno: `grantConsent` je izostal tudi pri vračajočih se obiskovalcih, ki so na pasico že odgovorili, in na vsaki strani, katere upravitelj privolitev se je naložil po strani. Vtičnik zdaj išče upravitelja privolitev deset sekund po nalaganju strani, namesto enkratnega preverjanja
- Novo: stran z nastavitvami opozori, kadar noben upravitelj privolitev ni dosegljiv, tako da je napačna nastavitev vidna, preden Barion zavrne integracijo

### 1.0.7
- Popravljeno: usodna napaka na vsaki strani, ki je vtičnik uporabljala brez WooCommercea, če je bil Pixel ID shranjen in polno sledenje vklopljeno. Skripta dogodkov v nogi je klicala `is_product()`, funkcijo, ki obstaja le ob naloženem WooCommercu, zato se je stran sesula z `Call to undefined function is_product()`. Hooki dogodkov WooCommerce se zdaj registrirajo samo, kadar je WooCommerce dejaven; osnovni piksel se, kot je dokumentirano, naloži tudi brez njega. Napaka obstaja od različice 1.0.0
- Popravljeno: opomba o ID-ju piksla, nastavljenem tudi v vtičniku Barion Payment Gateway, se je v vseh jezikih prikazovala v angleščini. Besedilo je bilo v prejšnji izdaji preoblikovano, prevodi pa niso bili posodobljeni

### 1.0.6
- Popravljeno: `initiateCheckout` in `setEncryptedEmail` se na WooCommercovem bloku Checkout nista nikoli sprožila, čeprav je ta od WooCommerce 8.3 privzet za nove trgovine. Vtičnik je poslušal le PHP hooke klasične blagajne in njeno polje `#billing_email`, blok pa nima ne enega ne drugega. Zdaj bere podatkovno shrambo blokov Cart in Checkout; delovanje klasične blagajne ostaja enako
- Popravljeno: `addToCart` se ni nikoli sprožil na straneh trgovine ali kategorij, v nobeni trgovini. Skripta dogodkov se je nalagala samo na straneh, kjer je dogodek že čakal v vrsti, česar na arhivskih straneh ni nikoli, zato poslušalcev za dodajanje v košarico ni bilo prav tam, kjer kupci dodajajo v košarico. Napaka izvira iz 1.0.1
- Popravljeno: `addToCart` zdaj deluje tudi z blokovnimi gumbi izdelkov, ki jih uporablja blok Product Collection. Ti tečejo na Interactivity API in ne sprožijo ne klasičnega dogodka jQuery ne podatkovne shrambe blokov, zato se vsebina košarice bere iz WooCommerce Store API

### 1.0.5
- Popravljeno: priloženi prevodi (madžarski, češki, slovaški, nemški, hrvaški, romunski, slovenski in srbski) se niso nikoli naložili, zato je zaslon z nastavitvami ostal v angleščini. WordPress išče samo v `wp-content/languages/plugins`, dokler vtičnik ne registrira svoje mape, česar ta vtičnik ni počel. Zdaj registrira `languages/` ob `init`

### 1.0.4
- Združljivost: preizkušeno z WordPress 7.0 in WooCommerce 11.0
- Spremenjeno: `Requires PHP` dvignjen s 7.2 na 7.4. WordPress 7.0 je opustil podporo za PHP 7.2 in 7.3, zato 7.2 ni bila več različica, na kateri bi vtičnik lahko tekel

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
