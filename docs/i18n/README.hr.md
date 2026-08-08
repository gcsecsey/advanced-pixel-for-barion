> 🌐 Ovo je automatski prijevod. Ispravci zajednice su dobrodošli!

# Advanced Pixel for Barion

Integracija Barion Pixela za WooCommerce s potpunim praćenjem e-trgovinskih događaja, podrškom za pristanak na kolačiće i kompatibilnošću s WP Consent API-jem.

<p align="center">
  <a href="../../README.md">English</a> |
  <a href="README.hu.md">Magyar</a> |
  <a href="README.cs.md">Čeština</a> |
  <a href="README.sk.md">Slovenčina</a> |
  <a href="README.de.md">Deutsch</a> |
  <strong>Hrvatski</strong> |
  <a href="README.ro.md">Română</a> |
  <a href="README.sl.md">Slovenščina</a> |
  <a href="README.sr.md">Srpski</a>
</p>

## Značajke

- **Osnovni Barion Pixel**: Učitava Barion skriptu za praćenje na cijelom webu (pageView se pokreće automatski)
- **Potpuno praćenje događaja**: Svi obvezni e-trgovinski događaji prema Barion dokumentaciji
  - `contentView`: Pokreće se na stranicama proizvoda
  - `addToCart`: Pokreće se kada se stavke dodaju u košaricu (na strani klijenta, radi s predmemoriranjem stranica)
  - `initiateCheckout`: Pokreće se kada počne naplata
  - `purchase`: Pokreće se pri uspješnom završetku narudžbe (s prevencijom dupliciranja)
  - `setEncryptedEmail`: Šalje e-mail za naplatu Barionu pri kupnji (šifrira bp.js)
- **WP Consent API integracija**: Univerzalna podrška za pristanak na kolačiće — radi s CookieYes, Complianz, Real Cookie Banner, GDPR Cookie Compliance, Cookie Notice i drugima
- **Cookie Law Info rezervna opcija**: Izravna integracija za stranice koje koriste CookieYes/Cookie Law Info
- **Upravljačka ploča administratora**: Jednostavna konfiguracija putem WordPress administratorskog sučelja
- **Način rada za otklanjanje pogrešaka**: Bilježenje u konzolu za testiranje i razvoj
- **bp.js detekcija dvostrukog učitavanja**: Sigurno supostoji s drugim dodacima koji učitavaju bp.js (npr. Barion Payment Gateway)

## Instalacija

1. Otpremi mapu `advanced-pixel-for-barion` u `/wp-content/plugins/`
2. Aktiviraj dodatak putem izbornika 'Dodaci' u WordPressu
3. Idi na Postavke > Barion Pixel za konfiguraciju

## Konfiguracija

### Postavke administratora

Pristupi stranici postavki na **Postavke > Barion Pixel** u WordPress administratorskom sučelju.

#### Pixel ID (Obvezno)
Unesi svoj Barion Pixel ID (format: `BP-0000000000-00`). Osnovni Pixel učitat će se na svim stranicama kada ovo postaviš.

#### Omogući potpuno praćenje Pixelom
Uključi/isključi praćenje e-trgovinskih događaja. Kada je isključeno, učitava se samo Osnovni Pixel (pageView za sprječavanje prijevare).

#### Način rada za otklanjanje pogrešaka
Omogući za bilježenje svih Barion Pixel događaja u konzolu preglednika radi testiranja.

## Dokumentacija

Detaljna dokumentacija dostupna je u mapi [`hr/`](hr/):

- [Referenca događaja](hr/events-reference.md) — Svi praćeni događaji, polja i vrste podataka
- [Integracija pristanka na kolačiće](hr/cookie-consent.md) — WP Consent API, Cookie Law Info i ručna integracija
- [Kompatibilnost](hr/compatibility.md) — WooCommerce, Barion Payment Gateway, dodaci za predmemoriranje
- [Napomene za testiranje](hr/testing-notes.md) — Posebnosti bp.js, način otklanjanja pogrešaka, kontrolni popis testiranja

Dokumentacija je također dostupna na [Magyar](../hu/), [Čeština](../cs/), [Slovenčina](../sk/), [Deutsch](../de/), [Hrvatski](hr/), [Română](../ro/), [Slovenščina](../sl/) i [Srpski](../sr/).

### Barionova dokumentacija

Barionovi vlastiti vodiči za postavljanje Pixela (na engleskom). Opcija **Enable Full Pixel Tracking** u ovom dodatku odgovara punom (Full) Barion Pixelu:

- [Getting started with the Barion Pixel](https://docs.barion.com/Getting_started_with_the_Barion_Pixel)
- [Implementing the Base Barion Pixel](https://docs.barion.com/Implementing_the_Base_Barion_Pixel)
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel)
- [Implementing the Base and Full pixel in WooCommerce webshops](https://docs.barion.com/Implementing-the-barion-base-and-full-pixel-in-woocommerce-webshops)
- [Barion Pixel API reference](https://docs.barion.com/Barion_Pixel_API_reference)
- [Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements)

## Kompatibilnost

- **WooCommerce**: Obvezno za potpuno praćenje događaja (osnovni pixel radi i bez njega)
- **Barion Payment Gateway** ([woocommerce-barion](https://github.com/szelpe/woocommerce-barion)): Savršeno supostoji — taj dodatak obrađuje plaćanja, ovaj obrađuje praćenje Pixelom
- **Predmemoriranje stranica**: Potpuno kompatibilno (addToCart koristi JavaScript na strani klijenta)
- **Dodaci za kolačiće**: Svaki dodatak kompatibilan s WP Consent API-jem radi automatski

## Zahtjevi

- WordPress 5.0 ili noviji
- PHP 7.4 ili noviji
- WooCommerce 5.0+ (za potpuno praćenje događaja)
- Neobavezno: [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) za univerzalnu podršku pristanka na kolačiće

## Licenca

GPL-2.0-or-later — pogledaj [LICENSE](../../LICENSE) za detalje.

## Dnevnik promjena

### 1.0.3
- Ispravljeno: `setEncryptedEmail` slao se više puta tijekom jednog učitavanja stranice naplate
- Ispravljeno: bp.js je uz pogrešku `Format of e-mail address or hash is invalid` odbijao adrese e-pošte sa znakom `+` u lokalnom dijelu ili s TLD-om duljim od četiri slova (`.museum`, `.online`). Dodatak sada adresu hashira algoritmom SHA-1 u pregledniku prije nego je proslijedi bp.js-u — Barion Pixel API prihvaća unaprijed izračunati hash umjesto obične adrese
- Ispravljeno: djelomičan unos (na primjer `x@y`) više se ne prosljeđuje bp.js-u
- Ispravljeno: poziv je usklađen s Barionovom dokumentacijom — `bp('identity', 'setEncryptedEmail', ...)` (prije `'identify'`)

Verziju 1.0.2 zamijenila je 1.0.3 prije izdanja; njezini su ispravci navedeni gore.

### 1.0.1
- Ispravljeno: nijedan događaj Pixela nije se slao — skripta događaja stavljena je u red tek nakon što je `wp_print_footer_scripts` već izvršen
- Ispravljeno: automatsko prepoznavanje pristanka na kolačiće sada se izvodi nakon `DOMContentLoaded`, pa vidi i globalne varijable dodataka koji se učitavaju kasnije
- Novo: `setEncryptedEmail` sada se šalje i na stranici naplate — kod prijavljenih korisnika pri učitavanju te kada kupac unese valjanu adresu e-pošte za naplatu

### 1.0.0
- Inicijalno izdanje
- Implementacija osnovnog Barion Pixela (pageView)
- Potpuno praćenje događaja (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail)
- WP Consent API integracija
- Cookie Law Info rezervna integracija
- Upravljačka ploča administratora s načinom otklanjanja pogrešaka
- addToCart na strani klijenta (kompatibilno s predmemoriranjem stranica)
- Podrška za varijabilne proizvode
- Prevencija dupliciranja kupnji
- bp.js detekcija dvostrukog učitavanja
