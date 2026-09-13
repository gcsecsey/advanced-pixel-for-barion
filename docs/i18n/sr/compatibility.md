> 🌐 Ovo je automatski prevod. Ispravke zajednice su dobrodošle!
>
> [English version](../../compatibility.md)

# Kompatibilnost dodatka

## WooCommerce

**Potreban za potpuno praćenje događaja.** Osnovni piksel radi bez WooCommerce-a, ali svi događaji e-trgovine (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail) zahtevaju WooCommerce.

| Verzija | Status |
|---------|--------|
| WooCommerce 5.0+ | Podržano |
| WooCommerce 11.0 | Testirano |

### Blokovi Cart i Checkout

Podržani od 1.0.6. Blokovi ne pokreću ni klasične PHP hookove ni DOM selektore koje je dodatak
ranije koristio, pa na blokovskim površinama čita podatke WooCommerce-a direktno: korpu iz Store
API-ja za `addToCart` i skladište podataka `wc/store/cart` za imejl na naplati.

**Poznato ograničenje.** Događaj `purchase` ide preko `woocommerce_thankyou`, koji u blokovskom
šablonu Order Confirmation pokreće blok „Dodatne informacije“. Ako taj blok ukloniš iz šablona,
praćenje kupovina tiho prestaje. Ostavi ga u šablonu.

---

## Drugi izvori osnovnog piksela

Barion dokumentuje nekoliko načina da osnovni piksel dođe na stranicu, a u jednoj prodavnici lako
se skupi više njih:

- [Barion Payment Gateway](https://barion.com/en/plugins/) od Bariona i [prolaz od szelpe](https://github.com/szelpe/woocommerce-barion), koji imaju opciono polje Pixel ID
- [oznaka u Google Tag Manageru](https://docs.barion.com/Implementing_the_Barion_Pixel_base_code_through_the_Google_Tag_Manager)
- isečak zalepljen u zaglavlje teme

**Dva osnovna piksela na jednoj stranici ne pogoršavaju praćenje, već ga prekidaju.** Svaka kopija
`bp.js` dodaje sopstveni iframe pod `id="barion_receiver"`, `getElementById()` vraća samo prvi, a
druga kopija šalje svoje događaje u taj prvi iframe pre nego što je preuzeo status saglasnosti
posetioca. `bp.js` tu baca grešku
(`Cannot read properties of undefined (reading 'approvedBase')`) i događaj se nikad ne pošalje.
Izmereno na živoj prodavnici: tri greške i nula događaja na stranici proizvoda.

### Šta dodatak preduzima

**Barion Payment Gateway.** Svoj piksel ispisuje iz `wp_head` sa prioritetom 999999 kad god mu je
polje Pixel ID popunjeno — bez obzira na njegovo sopstveno podešavanje praćenja, pa i kada je sam
prolaz isključen. To je posle svega što ovaj dodatak može da stavi u red, pa to nijedna provera u
JavaScriptu ne može da vidi. Ovaj dodatak zato primenjuje prolazov sopstveni filter
`woocommerce_barion_disable_tracking` i sam opslužuje osnovni piksel, ali samo dok je Pixel ID
podešen ovde. Taj filter u prolazu nema drugog korisnika, a prolaz implementira osnovni piksel i
ništa više od toga, pa se ništa ne gubi. Sajt koji želi da piksel ostane na prolazu može da ga
ukloni sa `remove_filter()` i da umesto toga ovde obriše Pixel ID.

**Sve ostalo.** Pre učitavanja `bp.js` dodatak proverava `window.bp`. Ako ga je neki drugi izvor
definisao prvi, preskače učitavanje skripte i šalje samo poziv `init`. U režimu za otklanjanje
grešaka to javlja poruka `[Barion Pixel] bp.js already loaded by another plugin`.

Isečak koji se izvršava *posle* ovog dodatka — oznaka u Google Tag Manageru, isečak u zaglavlju
teme — van domašaja je: učitaće `bp.js` ponovo, šta god ovaj dodatak uradio. U režimu za
otklanjanje grešaka taj slučaj se prepozna posle učitavanja stranice i dodatak upozorava da nešto
drugo učitava drugu kopiju `bp.js`.

**Preporuka:** drži Pixel ID na jednom mestu, ovde. Isprazni polje u prolazu i ukloni eventualnu
oznaku u Google Tag Manageru ili isečak u temi. Ono što zaista treba izbeći su dva različita Pixel
ID-a na jednoj stranici — dvostruku skriptu dodatak može sprečiti, dvostruki identitet ne.

Kada i Barion Payment Gateway ima podešen Pixel ID, stranica podešavanja to javlja. Oba dodatka
svejedno nastavljaju da rade: onaj upravlja plaćanjima, ovaj praćenjem.

---

## Dodaci za keširanje stranica

Dodatak je potpuno kompatibilan sa keširanjem stranica:

| Događaj | Implementacija | Uticaj keširanja |
|---------|---------------|-----------------|
| contentView | Na strani servera (stranica proizvoda) | Stranice proizvoda se obično ne keširaju ili variraju po proizvodu |
| addToCart | **JavaScript na strani klijenta** | Bez problema sa keširanjem — JS se izvršava u pregledaču |
| initiateCheckout | Na strani servera (stranica za završetak porudžbine) | Završetak porudžbine se ne kešira (sadrži podatke korisničke sesije) |
| purchase | Na strani servera (stranica zahvalnice) | Stranice zahvalnice se ne keširaju (jedinstvene po porudžbini) |

Događaj addToCart je specifično implementiran na strani klijenta (umesto korišćenja PHP sesija) da bi radio sa WordPress.com hostingom i agresivnim podešavanjima keširanja stranica.

**Kompatibilno sa:** WP Super Cache, W3 Total Cache, LiteSpeed Cache, WordPress.com hosting, Cloudflare i sličnim rešenjima za keširanje.

---

## Dodaci za saglasnost za kolačiće

Dodatak podržava sve dodatke za saglasnost za kolačiće koji implementiraju [WP Consent API](https://wordpress.org/plugins/wp-consent-api/). Pogledaj [Integracija saglasnosti za kolačiće](cookie-consent.md) za detalje.

**Automatski podržano:**

- CookieYes (1,5M+ instalacija)
- Complianz (1M+ instalacija)
- Cookie Notice by dFactory (1M+ instalacija)
- GDPR Cookie Compliance by Moove (300K+ instalacija)
- Real Cookie Banner (100K+ instalacija)

**Direktna rezervna integracija:**

- Cookie Law Info / CookieYes (radi i bez WP Consent API)

---

## Verzija WordPress-a

| Verzija | Status |
|---------|--------|
| WordPress 5.0+ | Potrebno |
| WordPress 7.0 | Testirano |

## Verzija PHP-a

| Verzija | Status |
|---------|--------|
| PHP 7.4+ | Potrebno |
| PHP 8.x | Kompatibilno |
