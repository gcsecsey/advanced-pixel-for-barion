> 🌐 Ovo je automatski prijevod. Ispravci zajednice su dobrodošli!
>
> [English version](../../cookie-consent.md)

# Integracija pristanka na kolačiće

Ovdje je mjerodavna Barionova vlastita stranica:
[Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements).
Na njoj je i tekst trake za pristanak koji Barion preporučuje te aktualan popis Barionovih
oglašivačkih partnera. Pročitaj je prije puštanja u rad — usklađenost je odgovornost trgovca, ne
dodatka.

## Što dodatak radi

Skripta osnovnog pixela uvijek se učitava, a `pageView` se uvijek šalje. Barion to dokumentira kao
legitimni interes: osnovni pixel služi sprječavanju prijevara u plaćanju, a podaci prikupljeni bez
marketinškog pristanka koriste se samo u tu svrhu.

Povrh toga, dodatak poziva `bp('consent', 'grantConsent')` kada kupac prihvati marketinške
kolačiće i `bp('consent', 'rejectConsent')` kada ih odbije. Barion oba navodi kao obavezna. Tvoja
traka zato mora nuditi stvarnu mogućnost odbijanja — kod trake koja poznaje samo prihvaćanje
dodatak nema što javiti.

Dodatak traži upravitelja pristanka ovim redom i staje kod prvog pronađenog:

1. **WP Consent API** (preporučeno) — univerzalno, radi sa svim većim dodacima za kolačiće
2. **Cookie Law Info** (rezerva) — izravna integracija za CookieYes / Cookie Law Info
3. **Ručno** — za vlastite upravitelje pristanka

---

## Razina 1: WP Consent API (preporučeno)

[WP Consent API](https://wordpress.org/plugins/wp-consent-api/) je WordPressov standard za
prosljeđivanje pristanka među dodacima. Barion Pixel registrira se u kategoriji `marketing`.

### Kako radi

Nakon `DOMContentLoaded` dodatak provjerava postoji li funkcija `wp_has_consent()`. Ako postoji:

1. Ako je pristanak `marketing` već dan, `grantConsent` se šalje odmah.
2. Od tada dodatak osluškuje `wp_listen_for_consent_change` i pri svakoj promjeni šalje `grantConsent` ili `rejectConsent`.

Primijeti što na popisu *nije*: pri učitavanju stranice na kojoj marketinškog pristanka nema,
dodatak šuti umjesto da pošalje `rejectConsent`. Dok kupac nije odgovorio na traku, nema se što
javiti — a odgovor stiže preko događaja promjene.

### Podržani dodaci za kolačiće

Automatski radi svaki dodatak koji implementira WP Consent API:

| Dodatak | Aktivnih instalacija | Napomena |
|---------|----------------------|----------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5M+ | WP Consent API ugrađen |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1M+ | Suautor WP Consent API-ja |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1M+ | Kompatibilan s WP Consent API-jem |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300K+ | Kompatibilan s WP Consent API-jem |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100K+ | Kompatibilan s WP Consent API-jem |

### Postavljanje

1. Instaliraj i aktiviraj [WP Consent API](https://wordpress.org/plugins/wp-consent-api/).
2. Instaliraj i podesi svoj dodatak za pristanak na kolačiće.
3. Instaliraj i podesi Advanced Pixel for Barion.

Ništa više — pristanak se obrađuje automatski.

---

## Razina 2: Cookie Law Info (rezerva)

Koristi se kada WP Consent API nije dostupan, a
[Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) / CookieYes jest.

### Kako radi

1. Dodatak provjerava globalni objekt `CLI` i njegov `allowedCategories`.
2. Ako kolačić `cookielawinfo-checkbox-non-necessary` već ima vrijednost `yes` — povratni posjetitelj koji je prihvatio — `grantConsent` se šalje odmah.
3. Prate se klikovi na elemente `.cli_action_button` u traci. Kratko nakon klika dodatak ponovno čita kolačić i prema tome šalje `grantConsent` ili `rejectConsent`.

### Postavljanje

Nema ga. Instaliraj oba dodatka i radi.

---

## Razina 3: Ručna integracija

Za vlastite upravitelje pristanka ili gdje ništa od navedenog ne vrijedi.

### Metoda 1: JavaScript funkcije (preporučeno)

```javascript
// Kada korisnik prihvati marketinške kolačiće
function onMarketingConsentGranted() {
    if (typeof window.wcBarionGrantConsent === 'function') {
        window.wcBarionGrantConsent();
    }
}

// Kada korisnik odbije marketinške kolačiće
function onMarketingConsentRejected() {
    if (typeof window.wcBarionRejectConsent === 'function') {
        window.wcBarionRejectConsent();
    }
}
```

### Metoda 2: Vlastiti DOM događaji

```javascript
// Davanje pristanka
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Odbijanje pristanka
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### Metoda 3: WordPress action hook

```php
// U tvojem dodatku za upravljanje pristankom ili u temi
add_action('wc_barion_pixel_footer_scripts', 'my_barion_consent_handler');

function my_barion_consent_handler() {
    ?>
    <script>
    // Ovdje ide tvoja logika pristanka
    </script>
    <?php
}
```

### Primjeri za pojedine upravitelje pristanka

**Cookiebot:**
```javascript
window.addEventListener('CookiebotOnAccept', function() {
    if (Cookiebot.consent.marketing) {
        window.wcBarionGrantConsent();
    } else {
        window.wcBarionRejectConsent();
    }
});
window.addEventListener('CookiebotOnDecline', function() {
    window.wcBarionRejectConsent();
});
```

**OneTrust:**
```javascript
function OptanonWrapper() {
    if (OnetrustActiveGroups.includes('C0004')) {
        window.wcBarionGrantConsent();
    } else {
        window.wcBarionRejectConsent();
    }
}
```

---

## Što i dalje moraš sam

Dodatak prosljeđuje pristanak. Tvoje politike neće napisati ni traku podesiti, a Barion traži i
jedno i drugo. Iz
[Barionovih zahtjeva](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements):

- **Dodaj Barionove kolačiće u svoju politiku kolačića.** `ba_vid`, `ba_vid.xxx`, `ba_sid` i `ba_sid.xxx` idu među nužne kolačiće — služe sprječavanju prijevara na temelju Barionova legitimnog interesa i ne traže pristanak. `BarionMarketingConsent.xxx` te kolačići medijskih i oglašivačkih partnera idu među marketinške kolačiće i traže pristanak.
- **Spomeni Barion Pixel u svojoj politici privatnosti** i poveži Barionovu [obavijest o privatnosti](https://www.barion.com/en/privacy-notice/).
- **Omogući kupcima da u svakom trenutku promijene ili povuku pristanak** i pitaj ih ponovno. Barion traži da se traka ponovno pojavi barem svakih 13 mjeseci, a preporučuje 30 dana.
- **Koristi tekst trake koji Barion preporučuje**, gdje god možeš. Nalazi se na stranici sa zahtjevima i pokriva dijeljenje podataka s partnerima koje Barion Pixel podrazumijeva.

---

## Kako pristanak utječe na pixel

| Stanje | Osnovni pixel (bp.js) | pageView | Prikupljanje marketinških podataka |
|--------|-----------------------|----------|------------------------------------|
| Prije bilo kakve odluke o pristanku | Učitan | Šalje se (sprječavanje prijevara) | Ne |
| Nakon `grantConsent` | Učitan | Šalje se | Da |
| Nakon `rejectConsent` | Učitan | Šalje se (sprječavanje prijevara) | Ne |

---

## Testiranje

1. Uključi **način za otklanjanje pogrešaka** u Postavke > Barion Pixel.
2. Otvori konzolu preglednika (F12).
3. Prati ove poruke:

| Poruka | Značenje |
|--------|----------|
| `Consent auto-granted via WP Consent API` | Razina 1, pristanak je pri učitavanju već postojao |
| `Consent granted via WP Consent API change event` | Razina 1, kupac je upravo prihvatio |
| `Consent rejected via WP Consent API change event` | Razina 1, kupac je upravo odbio |
| `Cookie Law Info detected, initial non-necessary cookie: …` | Preuzela je razina 2, s pročitanom vrijednošću kolačića |
| `Cookie Law Info button clicked, non-necessary cookie: …` | Razina 2, kupac je koristio traku |
| `No consent manager detected…` | Razina 3 — ništa nije pronađeno, funkcije pozovi sam |
| `Consent granted (grantConsent)` | `grantConsent` je stigao do bp.js (bilo koja razina) |
| `Consent rejected (rejectConsent)` | `rejectConsent` je stigao do bp.js (bilo koja razina) |

Sve poruke imaju prefiks `[Barion Pixel]`.

4. Testiraj na svojoj traci i put prihvaćanja i put odbijanja.
5. Funkcije pristanka sigurno se mogu pozivati više puta.
