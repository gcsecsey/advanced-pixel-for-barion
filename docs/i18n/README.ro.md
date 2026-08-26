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

Găsești ID-ul în portofelul tău Barion, la **Merchant Management > Details**. Fiecare magazin are ID-ul lui, iar mediile sandbox și live emit ID-uri diferite. Un ID care începe cu `BPT` nu este un ID de Pixel și nu va funcționa.

#### Activează urmărirea completă cu Pixel
Activează/dezactivează urmărirea evenimentelor de e-commerce. Când este dezactivat, se încarcă doar Pixelul de bază (pageView pentru prevenirea fraudei).

Barion cere o implementare completă a Pixelului și o bară de consimțământ conformă înainte ca un magazin să obțină condiții mai bune la Barion Smart Gateway sau acces la Barion Metrics. Acest plugin acoperă partea de implementare; aprobarea rămâne la Barion.

#### Mod depanare
Activează pentru a înregistra toate evenimentele Barion Pixel în consola browserului pentru testare.

## Documentație

Documentație detaliată este disponibilă în dosarul [`ro/`](ro/):

- [Referință evenimente](ro/events-reference.md) — Toate evenimentele urmărite, câmpurile și tipurile de date
- [Integrare consimțământ cookie](ro/cookie-consent.md) — WP Consent API, Cookie Law Info și integrare manuală
- [Compatibilitate](ro/compatibility.md) — WooCommerce, Barion Payment Gateway, plugin-uri de cache
- [Note de testare](ro/testing-notes.md) — Particularități bp.js, mod depanare, listă de verificare pentru testare

Documentația este disponibilă și în [Magyar](hu/), [Čeština](cs/), [Slovenčina](sk/), [Deutsch](de/), [Hrvatski](hr/), [Română](ro/), [Slovenščina](sl/) și [Srpski](sr/).

### Documentația Barion

Ghidurile proprii ale Barion pentru configurarea pixelului (în limba engleză). Opțiunea **Enable Full Pixel Tracking** din acest plugin corespunde pixelului Barion complet (Full):

- [Getting started with the Barion Pixel](https://docs.barion.com/Getting_started_with_the_Barion_Pixel)
- [Implementing the Base Barion Pixel](https://docs.barion.com/Implementing_the_Base_Barion_Pixel)
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel)
- [Implementing the Base and Full pixel in WooCommerce webshops](https://docs.barion.com/Implementing-the-barion-base-and-full-pixel-in-woocommerce-webshops)
- [Barion Pixel event reference](https://docs.barion.com/Barion-pixel-event-reference)
- [Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements)
- [Barion Pixel FAQ](https://docs.barion.com/Frequently_Asked_Questions_about_the_Barion_Pixel)

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

## Contribuții

Rapoartele de erori, pull request-urile și traducerile sunt binevenite — vezi [ghidul de contribuție](ro/contributing.md).

## Licență

GPL-2.0-or-later — vezi [LICENSE](../../LICENSE) pentru detalii.

## Jurnal de modificări

### 1.0.9
- Remediat: `grantConsent` era trimis la încărcarea paginii, nu atunci când vizitatorul accepta bara de cookie-uri. Exact pentru asta respinge Barion o integrare Full Pixel: un magazin care raportează consimțământ înainte ca cineva să fi răspuns arată la fel ca unul care nu întreabă niciodată. Consimțământul se trimite acum doar pentru o decizie luată de vizitator la acea încărcare de pagină. Un vizitator care revine nu declanșează nimic, pentru că bp.js îi păstrează răspunsul în propriul cookie, iar Barion îl are deja
- Remediat: cu plugin-ul WP Consent API activ, dar fără nicio bară de cookie-uri înregistrată la el, fiecare vizitator era raportat ca având consimțământ de marketing. Un tip de consimțământ nesetat este felul în care acel API spune că nu îl conduce nicio bară, iar plugin-ul îl citea ca pe un răspuns real. În această stare, acum îl ignoră
- Nou: pagina de setări avertizează când WP Consent API este activ, dar nicio bară de cookie-uri nu se înregistrează la el. Instalarea lui lângă o bară care nu îl suportă nu conectează nimic, iar până acum nimic nu spunea asta

### 1.0.8
- Remediat: `grantConsent` nu a fost trimis niciodată pe site-urile fără plugin-ul separat WP Consent API, așa că Barion nu a aprobat integrarea Full Pixel. Detectarea consimțământului încerca trei surse pe rând și se oprea la prima potrivire, iar ultima dintre ele nu înregistra niciun ascultător. CookieYes, Complianz, Cookiebot și vechiul banner Cookie Law Info sunt acum citite direct, fără plugin suplimentar
- Remediat: `grantConsent` lipsea și pentru vizitatorii care reveneau și răspunseseră deja bannerului, precum și pe orice site al cărui manager de consimțământ se încărca după pagină. Plugin-ul caută acum un manager de consimțământ timp de zece secunde după încărcarea paginii, în loc de o singură verificare
- Nou: pagina de setări avertizează când niciun manager de consimțământ nu este accesibil, astfel încât o configurare greșită este vizibilă înainte ca Barion să refuze integrarea

### 1.0.7
- Remediat: o eroare fatală pe orice site care rula plugin-ul fără WooCommerce, dacă un Pixel ID era salvat, iar urmărirea completă era activată. Scriptul de evenimente din subsol apela `is_product()`, o funcție care există doar cât timp WooCommerce este încărcat, așa că pagina se oprea cu `Call to undefined function is_product()`. Hook-urile de evenimente WooCommerce sunt acum înregistrate doar când WooCommerce este activ; pixelul de bază se încarcă în continuare fără el, așa cum este documentat. Eroarea există începând cu 1.0.0
- Remediat: nota despre un ID Pixel setat și în plugin-ul Barion Payment Gateway apărea în engleză în toate limbile. Textul a fost reformulat într-o versiune anterioară, iar traducerile nu au fost niciodată actualizate

### 1.0.6
- Remediat: `initiateCheckout` și `setEncryptedEmail` nu se declanșau niciodată pe blocul Checkout din WooCommerce, care este implicit pentru magazinele noi începând cu WooCommerce 8.3. Plugin-ul asculta doar hook-urile PHP ale finalizării clasice și câmpul ei `#billing_email`, iar blocul nu are niciunul. Acum citește depozitul de date al blocurilor Cart și Checkout; comportamentul finalizării clasice rămâne neschimbat
- Remediat: `addToCart` nu se declanșa niciodată pe paginile de magazin sau de categorie, în niciun magazin. Scriptul de evenimente se încărca doar pe paginile care aveau deja un eveniment în coadă, ceea ce nu se întâmplă niciodată pe paginile de arhivă, așa că ascultătorii pentru adăugarea în coș lipseau exact acolo unde clienții adaugă în coș. Eroarea datează din 1.0.1
- Remediat: `addToCart` funcționează acum și cu butoanele de produs ale blocului Product Collection. Acestea rulează pe Interactivity API și nu declanșează nici evenimentul jQuery clasic, nici depozitul de date al blocurilor, așa că vom citi conținutul coșului din WooCommerce Store API

### 1.0.5
- Remediat: traducerile incluse (maghiară, cehă, slovacă, germană, croată, română, slovenă și sârbă) nu se încărcau niciodată, iar ecranul de setări rămânea în engleză. WordPress caută doar în `wp-content/languages/plugins` dacă plugin-ul nu își înregistrează propriul director, iar plugin-ul nu o făcea. Acum înregistrează `languages/` la `init`

### 1.0.4
- Compatibilitate: testat cu WordPress 7.0 și WooCommerce 11.0
- Modificat: `Requires PHP` ridicat de la 7.2 la 7.4. WordPress 7.0 a renunțat la suportul pentru PHP 7.2 și 7.3, deci 7.2 nu mai era o versiune pe care plugin-ul putea rula

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
