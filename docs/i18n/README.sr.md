> 🌐 Ovo je automatski prevod. Ispravke zajednice su dobrodošle!

# Advanced Pixel for Barion

Integracija Barion Pixel za WooCommerce sa potpunim praćenjem događaja e-trgovine, podrškom za saglasnost za kolačiće i kompatibilnošću sa WP Consent API.

<p align="center">
  <a href="../../README.md">English</a> |
  <a href="README.hu.md">Magyar</a> |
  <a href="README.cs.md">Čeština</a> |
  <a href="README.sk.md">Slovenčina</a> |
  <a href="README.de.md">Deutsch</a> |
  <a href="README.hr.md">Hrvatski</a> |
  <a href="README.ro.md">Română</a> |
  <a href="README.sl.md">Slovenščina</a> |
  <strong>Srpski</strong>
</p>

## Funkcionalnosti

- **Osnovni Barion Pixel**: Učitava skriptu za praćenje na celom sajtu (pageView se aktivira automatski)
- **Potpuno praćenje događaja**: Svi obavezni događaji e-trgovine prema Barion dokumentaciji
  - `contentView`: Aktivira se na stranicama proizvoda
  - `addToCart`: Aktivira se kada se stavke dodaju u korpu (na strani klijenta, radi sa keširanjem stranice)
  - `initiateCheckout`: Aktivira se kada počne završetak porudžbine
  - `purchase`: Aktivira se pri uspešnom završetku porudžbine (sa sprečavanjem duplikata)
  - `setEncryptedEmail`: Šalje adresu za naplatu Barionu pri kupovini (enkriptovano putem bp.js)
- **Integracija WP Consent API**: Univerzalna podrška za saglasnost za kolačiće — radi sa CookieYes, Complianz, Real Cookie Banner, GDPR Cookie Compliance, Cookie Notice i više
- **Rezervna integracija Cookie Law Info**: Direktna integracija za sajtove koji koriste CookieYes/Cookie Law Info
- **Panel za podešavanja u administraciji**: Jednostavna konfiguracija kroz WordPress administraciju
- **Režim za otklanjanje grešaka**: Beleženje u konzoli za testiranje i razvoj
- **Detekcija dvostrukog učitavanja bp.js**: Bezbedno koegzistira sa drugim dodacima koji učitavaju bp.js (npr. Barion Payment Gateway)

## Instalacija

1. Otpremi fasciklu `advanced-pixel-for-barion` u `/wp-content/plugins/`
2. Aktiviraj dodatak kroz meni 'Dodaci' u WordPress-u
3. Idi na Podešavanja > Barion Pixel da konfigurišeš

## Konfiguracija

### Podešavanja u administraciji

Pristupi stranici podešavanja na **Podešavanja > Barion Pixel** u WordPress administraciji.

#### ID piksela (obavezno)
Unesi svoj Barion Pixel ID (format: `BP-0000000000-00`). Osnovni piksel će se učitati na svim stranicama kada ovo budeš podesio.

#### Omogući potpuno praćenje piksela
Uključi/isključi praćenje događaja e-trgovine. Kada je onemogućeno, učitava se samo Osnovni piksel (pageView za sprečavanje prevara).

#### Režim za otklanjanje grešaka
Omogući da se svi Barion Pixel događaji beležu u konzolu pregledača radi testiranja.

## Dokumentacija

Detaljna dokumentacija je dostupna u fascikli [`sr/`](sr/):

- [Referenca događaja](sr/events-reference.md) — Svi praćeni događaji, polja i tipovi podataka
- [Integracija saglasnosti za kolačiće](sr/cookie-consent.md) — WP Consent API, Cookie Law Info i ručna integracija
- [Kompatibilnost](sr/compatibility.md) — WooCommerce, Barion Payment Gateway, dodaci za keširanje
- [Beleške o testiranju](sr/testing-notes.md) — bp.js specifičnosti, režim za otklanjanje grešaka, lista za proveru testiranja

Dokumentacija je takođe dostupna na [Magyar](../hu/), [Čeština](../cs/), [Slovenčina](../sk/), [Deutsch](../de/), [Hrvatski](../hr/), [Română](../ro/), [Slovenščina](../sl/), i [Srpski](sr/).

### Barionova dokumentacija

Barionovi sopstveni vodiči za podešavanje piksela (na engleskom). Opcija **Enable Full Pixel Tracking** u ovom dodatku odgovara punom (Full) Barion Pixelu:

- [Getting started with the Barion Pixel](https://docs.barion.com/Getting_started_with_the_Barion_Pixel)
- [Implementing the Base Barion Pixel](https://docs.barion.com/Implementing_the_Base_Barion_Pixel)
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel)
- [Implementing the Base and Full pixel in WooCommerce webshops](https://docs.barion.com/Implementing-the-barion-base-and-full-pixel-in-woocommerce-webshops)
- [Barion Pixel API reference](https://docs.barion.com/Barion_Pixel_API_reference)
- [Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements)

## Kompatibilnost

- **WooCommerce**: Potreban za potpuno praćenje događaja (osnovni piksel radi bez njega)
- **Barion Payment Gateway** ([woocommerce-barion](https://github.com/szelpe/woocommerce-barion)): Savršeno koegzistira — taj dodatak upravlja plaćanjima, ovaj upravlja praćenjem pikselom
- **Keširanje stranica**: Potpuno kompatibilno (addToCart koristi JavaScript na strani klijenta)
- **Dodaci za kolačiće**: Bilo koji dodatak kompatibilan sa WP Consent API radi automatski

## Zahtevi

- WordPress 5.0 ili noviji
- PHP 7.2 ili noviji
- WooCommerce 5.0+ (za potpuno praćenje događaja)
- Opcionalno: [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) za univerzalnu podršku saglasnosti za kolačiće

## Licenca

GPL-2.0-or-later — pogledaj [LICENSE](../../LICENSE) za detalje.

## Evidencija promena

### 1.0.3
- Ispravljeno: `setEncryptedEmail` slao se više puta tokom jednog učitavanja stranice naplate
- Ispravljeno: bp.js je uz grešku `Format of e-mail address or hash is invalid` odbijao adrese e-pošte sa znakom `+` u lokalnom delu ili sa TLD dužim od četiri slova (`.museum`, `.online`). Dodatak sada adresu hešira algoritmom SHA-1 u pregledaču pre nego što je prosledi bp.js-u — Barion Pixel API prihvata unapred izračunat heš umesto obične adrese
- Ispravljeno: delimičan unos (na primer `x@y`) više se ne prosleđuje bp.js-u
- Ispravljeno: poziv je usklađen sa Barionovom dokumentacijom — `bp('identity', 'setEncryptedEmail', ...)` (ranije `'identify'`)

Verziju 1.0.2 je pre izdanja zamenila 1.0.3; njene ispravke su navedene gore.

### 1.0.1
- Ispravljeno: nijedan događaj piksela nije se slao — skripta događaja stavljena je u red tek nakon što je `wp_print_footer_scripts` već izvršen
- Ispravljeno: automatsko prepoznavanje saglasnosti za kolačiće sada se izvršava nakon `DOMContentLoaded`, pa vidi i globalne promenljive dodataka koji se učitavaju kasnije
- Novo: `setEncryptedEmail` sada se šalje i na stranici naplate — kod prijavljenih korisnika pri učitavanju i kada kupac unese važeću adresu e-pošte za naplatu

### 1.0.0
- Početno izdanje
- Implementacija osnovnog Barion Pixel (pageView)
- Potpuno praćenje događaja (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail)
- Integracija WP Consent API
- Rezervna integracija Cookie Law Info
- Panel za podešavanja u administraciji sa režimom za otklanjanje grešaka
- addToCart na strani klijenta (kompatibilno sa keširanjem stranice)
- Podrška za varijabilne proizvode
- Sprečavanje duplih kupovina
- Detekcija dvostrukog učitavanja bp.js
