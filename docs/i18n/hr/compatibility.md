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

- [Barion Payment Gateway](https://github.com/szelpe/woocommerce-barion) od szelpe i drugi Barionovi platni dodaci koji imaju neobavezno polje Pixel ID
- [oznaka u Google Tag Manageru](https://docs.barion.com/Implementing_the_Barion_Pixel_base_code_through_the_Google_Tag_Manager)
- isječak zalijepljen u zaglavlje teme

Dodatak prije učitavanja `bp.js` provjerava `window.bp` i `window.BarionAnalyticsObject`. Ako su
oba već tu, preskače učitavanje skripte i šalje samo vlastiti poziv `init`, pa se pixel nikad ne
učita dvaput. U načinu za otklanjanje pogrešaka to javlja poruka
`[Barion Pixel] bp.js already loaded by another plugin`.

**Preporuka:** drži Pixel ID na jednom mjestu. Ako koristiš i Barionov platni pristupnik, postavi
ID ovdje i ostavi njegovo polje praznim; ako osnovni pixel već učitavaš preko Google Tag Managera,
ukloni tu oznaku. Ono što doista treba izbjeći su dva različita Pixel ID-a na jednoj stranici —
dvostruku skriptu dodatak može spriječiti, dvostruki identitet ne.

Kada i Barion Payment Gateway ima podešen Pixel ID, stranica postavki prikazuje informativnu
obavijest. Oba dodatka svejedno nastavljaju raditi: onaj obrađuje plaćanja, ovaj praćenje.

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
