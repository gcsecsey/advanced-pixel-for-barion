> 🌐 Aceasta este o traducere automată. Corecțiile comunității sunt binevenite!

# Advanced Pixel for Barion

Integrare Barion Pixel pentru WooCommerce cu urmărirea completă a evenimentelor de e-commerce, suport pentru consimțământul cookie-urilor și compatibilitate cu WP Consent API.

<p align="center">
  <a href="../../README.md">English</a> |
  <a href="README.hu.md">Magyar</a> |
  <a href="README.cs.md">Čeština</a> |
  <a href="README.sk.md">Slovenčina</a> |
  <a href="README.de.md">Deutsch</a> |
  <a href="README.hr.md">Hrvatski</a> |
  <strong>Română</strong> |
  <a href="README.sl.md">Slovenščina</a> |
  <a href="README.sr.md">Srpski</a>
</p>

## Funcționalități

- **Barion Pixel de bază**: Încarcă scriptul de urmărire Barion pe întreg site-ul (pageView se declanșează automat)
- **Urmărire completă a evenimentelor**: Toate evenimentele de e-commerce obligatorii conform documentației Barion
  - `contentView`: Declanșat pe paginile de produse
  - `addToCart`: Declanșat când produsele sunt adăugate în coș (pe partea clientului, funcționează cu cache-ul de pagini)
  - `initiateCheckout`: Declanșat când începe procesul de finalizare a comenzii
  - `purchase`: Declanșat la finalizarea cu succes a unei comenzi (cu prevenirea dublurilor)
  - `setEncryptedEmail`: Trimite adresa de e-mail de facturare către Barion la achiziție (criptat de bp.js)
- **Integrare WP Consent API**: Suport universal pentru consimțământul cookie-urilor — funcționează cu CookieYes, Complianz, Real Cookie Banner, GDPR Cookie Compliance, Cookie Notice și altele
- **Fallback Cookie Law Info**: Integrare directă pentru site-urile care folosesc CookieYes/Cookie Law Info
- **Panou de setări în administrare**: Configurare ușoară prin panoul de administrare WordPress
- **Mod depanare**: Jurnalizare în consolă pentru testare și dezvoltare
- **Detectarea dublei încărcări bp.js**: Coexistă în siguranță cu alte plugin-uri care încarcă bp.js (de ex., Barion Payment Gateway)

## Instalare

1. Încarcă dosarul `advanced-pixel-for-barion` în `/wp-content/plugins/`
2. Activează plugin-ul prin meniul „Plugin-uri" din WordPress
3. Navighează la Setări > Barion Pixel pentru configurare

## Configurare

### Setări în administrare

Accesează pagina de setări la **Setări > Barion Pixel** în panoul de administrare WordPress.

#### ID Pixel (Obligatoriu)
Introdu ID-ul tău Barion Pixel (format: `BP-0000000000-00`). Pixelul de bază va fi încărcat pe toate paginile odată ce acesta este configurat.

#### Activează urmărirea completă cu Pixel
Activează/dezactivează urmărirea evenimentelor de e-commerce. Când este dezactivat, se încarcă doar Pixelul de bază (pageView pentru prevenirea fraudei).

#### Mod depanare
Activează pentru a înregistra toate evenimentele Barion Pixel în consola browserului pentru testare.

## Documentație

Documentație detaliată este disponibilă în dosarul [`ro/`](ro/):

- [Referință evenimente](ro/events-reference.md) — Toate evenimentele urmărite, câmpurile și tipurile de date
- [Integrare consimțământ cookie](ro/cookie-consent.md) — WP Consent API, Cookie Law Info și integrare manuală
- [Compatibilitate](ro/compatibility.md) — WooCommerce, Barion Payment Gateway, plugin-uri de cache
- [Note de testare](ro/testing-notes.md) — Particularități bp.js, mod depanare, listă de verificare pentru testare

Documentația este disponibilă și în [Magyar](../i18n/hu/), [Čeština](../i18n/cs/), [Slovenčina](../i18n/sk/), [Deutsch](../i18n/de/), [Hrvatski](../i18n/hr/), [Română](../i18n/ro/), [Slovenščina](../i18n/sl/) și [Srpski](../i18n/sr/).

### Documentația Barion

Ghidurile proprii ale Barion pentru configurarea pixelului (în limba engleză). Opțiunea **Enable Full Pixel Tracking** din acest plugin corespunde pixelului Barion complet (Full):

- [Getting started with the Barion Pixel](https://docs.barion.com/Getting_started_with_the_Barion_Pixel)
- [Implementing the Base Barion Pixel](https://docs.barion.com/Implementing_the_Base_Barion_Pixel)
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel)
- [Implementing the Base and Full pixel in WooCommerce webshops](https://docs.barion.com/Implementing-the-barion-base-and-full-pixel-in-woocommerce-webshops)
- [Barion Pixel API reference](https://docs.barion.com/Barion_Pixel_API_reference)
- [Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements)

## Compatibilitate

- **WooCommerce**: Necesar pentru urmărirea completă a evenimentelor (pixelul de bază funcționează și fără el)
- **Barion Payment Gateway** ([woocommerce-barion](https://github.com/szelpe/woocommerce-barion)): Coexistă perfect — acel plugin gestionează plățile, acesta gestionează urmărirea cu pixel
- **Cache de pagini**: Complet compatibil (addToCart folosește JavaScript pe partea clientului)
- **Plugin-uri de cookie**: Orice plugin compatibil cu WP Consent API funcționează automat

## Cerințe

- WordPress 5.0 sau superior
- PHP 7.4 sau superior
- WooCommerce 5.0+ (pentru urmărirea completă a evenimentelor)
- Opțional: [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) pentru suport universal de consimțământ cookie

## Licență

GPL-2.0-or-later — vezi [LICENSE](../../LICENSE) pentru detalii.

## Jurnal de modificări

### 1.0.3
- Remediat: `setEncryptedEmail` era trimis de mai multe ori la o singură încărcare a paginii de finalizare a comenzii
- Remediat: bp.js respingea adresele de e-mail cu `+` în partea locală sau cu un TLD mai lung de patru litere (`.museum`, `.online`), cu eroarea `Format of e-mail address or hash is invalid`. Plugin-ul calculează acum hash-ul SHA-1 al adresei în browser înainte de a o transmite către bp.js — API-ul Barion Pixel acceptă un hash precalculat în locul adresei simple
- Remediat: valorile parțiale (de exemplu `x@y`) nu mai sunt trimise către bp.js
- Remediat: apelul respectă documentația Barion — `bp('identity', 'setEncryptedEmail', ...)` (anterior `'identify'`)

Versiunea 1.0.2 a fost înlocuită de 1.0.3 înainte de lansare; remedierile ei sunt listate mai sus.

### 1.0.1
- Remediat: niciun eveniment pixel nu era trimis — scriptul de evenimente era pus în coadă după ce `wp_print_footer_scripts` rulase deja
- Remediat: detectarea automată a consimțământului pentru cookie-uri rulează acum după `DOMContentLoaded`, astfel încât vede și variabilele globale ale plugin-urilor care se încarcă târziu
- Nou: `setEncryptedEmail` se trimite și pe pagina de finalizare a comenzii — la încărcare pentru utilizatorii autentificați și când clientul introduce o adresă de facturare validă

### 1.0.0
- Lansare inițială
- Implementarea Barion Pixel de bază (pageView)
- Urmărire completă a evenimentelor (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail)
- Integrare WP Consent API
- Integrare fallback Cookie Law Info
- Panou de setări în administrare cu mod depanare
- addToCart pe partea clientului (compatibil cu cache-ul de pagini)
- Suport pentru produse variabile
- Prevenirea dublei achiziții
- Detectarea dublei încărcări bp.js
