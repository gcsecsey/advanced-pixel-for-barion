> 🌐 Ovo je automatski prijevod. Ispravci zajednice su dobrodošli!
>
> [English version](../../compatibility.md)

# Kompatibilnost dodatka

## WooCommerce

**Obvezno za potpuno praćenje događaja.** Osnovni pixel radi bez WooCommercea, ali svi e-trgovinski događaji (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail) zahtijevaju WooCommerce.

| Verzija | Status |
|---------|--------|
| WooCommerce 5.0+ | Podržano |
| WooCommerce 11.0 | Testirano |

### Blokovi Cart i Checkout

Podržani od 1.0.6. Blokovi ne pokreću ni klasične PHP hookove ni DOM selektore koje je dodatak
koristio prije, pa na blokovskim plohama čita podatke WooCommercea izravno: košaricu iz Store
API-ja za `addToCart` i spremište podataka `wc/store/cart` za adresu e-pošte na naplati.

**Poznato ograničenje.** Događaj `purchase` ide preko `woocommerce_thankyou`, koji u blokovskom
predlošku Order Confirmation pokreće blok „Dodatne informacije“. Ako taj blok ukloniš iz
predloška, praćenje kupnji tiho prestaje. Ostavi ga u predlošku.

---

## Drugi izvori osnovnog pixela

Barion dokumentira nekoliko načina da osnovni pixel dođe na stranicu, a u jednoj se trgovini lako
skupi više njih:

- [Barion Payment Gateway](https://barion.com/en/plugins/) od Bariona i [pristupnik od szelpe](https://github.com/szelpe/woocommerce-barion), koji imaju neobavezno polje Pixel ID
- [oznaka u Google Tag Manageru](https://docs.barion.com/Implementing_the_Barion_Pixel_base_code_through_the_Google_Tag_Manager)
- isječak zalijepljen u zaglavlje teme

**Dva osnovna pixela na jednoj stranici ne pogoršavaju praćenje, nego ga prekidaju.** Svaka kopija
`bp.js` dodaje vlastiti iframe pod `id="barion_receiver"`, `getElementById()` vraća samo prvi, a
druga kopija šalje svoje događaje u taj prvi iframe prije nego što je dohvatio status pristanka
posjetitelja. `bp.js` ondje baca pogrešku
(`Cannot read properties of undefined (reading 'approvedBase')`) i događaj se nikad ne pošalje.
Izmjereno na živoj trgovini: tri pogreške i nula događaja na stranici proizvoda.

### Što dodatak poduzima

**Barion Payment Gateway.** Svoj pixel ispisuje iz `wp_head` s prioritetom 999999 kad god mu je
polje Pixel ID popunjeno — bez obzira na njegovu vlastitu postavku praćenja, pa čak i kad je sam
pristupnik isključen. To je nakon svega što ovaj dodatak može staviti u red, pa to nijedna provjera
u JavaScriptu ne može vidjeti. Ovaj dodatak zato primjenjuje pristupnikov vlastiti filtar
`woocommerce_barion_disable_tracking` i sam poslužuje osnovni pixel, ali samo dok je Pixel ID
postavljen ovdje. Taj filtar u pristupniku nema drugog korisnika, a pristupnik implementira osnovni
pixel i ništa više od toga, pa se ništa ne gubi. Stranica koja želi da pixel ostane na pristupniku
može ga ukloniti s `remove_filter()` i umjesto toga ovdje obrisati Pixel ID.

**Sve ostalo.** Prije učitavanja `bp.js` dodatak provjerava `window.bp`. Ako ga je neki drugi izvor
definirao prvi, preskače učitavanje skripte i šalje samo poziv `init`. U načinu za otklanjanje
pogrešaka to javlja poruka `[Barion Pixel] bp.js already loaded by another plugin`.

Isječak koji se izvodi *nakon* ovog dodatka — oznaka u Google Tag Manageru, isječak u zaglavlju
teme — izvan je dosega: učitat će `bp.js` ponovno, što god ovaj dodatak napravio. U načinu za
otklanjanje pogrešaka taj se slučaj prepozna nakon učitavanja stranice i dodatak upozorava da nešto
drugo učitava drugu kopiju `bp.js`.

**Preporuka:** drži Pixel ID na jednom mjestu, ovdje. Isprazni polje u pristupniku i ukloni
eventualnu oznaku u Google Tag Manageru ili isječak u temi. Ono što doista treba izbjeći su dva
različita Pixel ID-a na jednoj stranici — dvostruku skriptu dodatak može spriječiti, dvostruki
identitet ne.

Kada i Barion Payment Gateway ima podešen Pixel ID, stranica postavki to javlja. Oba dodatka
svejedno nastavljaju raditi: onaj obrađuje plaćanja, ovaj praćenje.

---

## Dodaci za predmemoriranje stranica

Dodatak je potpuno kompatibilan s predmemoriranjem stranica:

| Događaj | Implementacija | Utjecaj predmemoriranja |
|---------|---------------|------------------------|
| contentView | Na strani poslužitelja (stranica proizvoda) | Stranice proizvoda obično nisu predmemorirane ili variraju prema proizvodu |
| addToCart | **JavaScript na strani klijenta** | Nema problema s predmemoriranjem — JS se pokreće u pregledniku |
| initiateCheckout | Na strani poslužitelja (stranica naplate) | Naplata nije predmemorirana (sadrži podatke korisničke sesije) |
| purchase | Na strani poslužitelja (stranica zahvale) | Stranice zahvale nisu predmemorirane (jedinstvene po narudžbi) |

Događaj addToCart je posebno implementiran na strani klijenta (umjesto korištenja PHP sesija) kako bi radio s WordPress.com hostingom i agresivnim postavkama predmemoriranja stranica.

**Kompatibilno s:** WP Super Cache, W3 Total Cache, LiteSpeed Cache, WordPress.com hosting, Cloudflare i sličnim rješenjima za predmemoriranje.

---

## Dodaci za pristanak na kolačiće

Dodatak podržava sve dodatke za pristanak na kolačiće koji implementiraju [WP Consent API](https://wordpress.org/plugins/wp-consent-api/). Pogledaj [Integracija pristanka na kolačiće](cookie-consent.md) za detalje.

**Automatski podržano:**

- CookieYes (1,5M+ instalacija)
- Complianz (1M+ instalacija)
- Cookie Notice od dFactory (1M+ instalacija)
- GDPR Cookie Compliance od Moove (300K+ instalacija)
- Real Cookie Banner (100K+ instalacija)

**Izravna rezervna integracija:**

- Cookie Law Info / CookieYes (radi i bez WP Consent API-ja)

---

## Verzija WordPressa

| Verzija | Status |
|---------|--------|
| WordPress 5.0+ | Obvezno |
| WordPress 7.0 | Testirano |

## Verzija PHP-a

| Verzija | Status |
|---------|--------|
| PHP 7.4+ | Obvezno |
| PHP 8.x | Kompatibilno |
