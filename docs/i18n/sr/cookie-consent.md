> 🌐 Ovo je automatski prevod. Ispravke zajednice su dobrodošle!
>
> [English version](../../cookie-consent.md)

# Integracija saglasnosti za kolačiće

Ovde je merodavna Barionova sopstvena stranica:
[Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements).
Na njoj je i tekst trake za saglasnost koji Barion preporučuje, kao i aktuelan spisak Barionovih
oglašivačkih partnera. Pročitaj je pre puštanja u rad — usklađenost je odgovornost trgovca, ne
dodatka.

## Šta dodatak radi

Skripta osnovnog piksela uvek se učitava, a `pageView` se uvek šalje. Barion to dokumentuje kao
legitiman interes: osnovni piksel služi sprečavanju prevara u plaćanju, a podaci prikupljeni bez
marketinške saglasnosti koriste se samo u tu svrhu.

Povrh toga, dodatak poziva `bp('consent', 'grantConsent')` kada kupac prihvati marketinške
kolačiće i `bp('consent', 'rejectConsent')` kada ih odbije. Barion oba navodi kao obavezna. Tvoja
traka zato mora nuditi stvarnu mogućnost odbijanja — kod trake koja poznaje samo prihvatanje
dodatak nema šta da javi.

Dodatak traži upravljača saglasnošću ovim redom i staje kod prvog pronađenog:

1. **WP Consent API** (preporučeno) — univerzalno, radi sa svim većim dodacima za kolačiće
2. **Cookie Law Info** (rezerva) — direktna integracija za CookieYes / Cookie Law Info
3. **Ručno** — za sopstvene upravljače saglasnošću

---

## Nivo 1: WP Consent API (preporučeno)

[WP Consent API](https://wordpress.org/plugins/wp-consent-api/) je WordPress standard za
prosleđivanje saglasnosti između dodataka. Barion Pixel se registruje u kategoriji `marketing`.

### Kako radi

Nakon `DOMContentLoaded` dodatak proverava da li postoji funkcija `wp_has_consent()`. Ako postoji:

1. Ako je saglasnost `marketing` već data, `grantConsent` se šalje odmah.
2. Od tada dodatak osluškuje `wp_listen_for_consent_change` i pri svakoj promeni šalje `grantConsent` ili `rejectConsent`.

Primeti šta na spisku *nije*: pri učitavanju stranice na kojoj marketinške saglasnosti nema,
dodatak ćuti umesto da pošalje `rejectConsent`. Dok kupac nije odgovorio na traku, nema šta da se
javi — a odgovor stiže preko događaja promene.

### Podržani dodaci za kolačiće

Automatski radi svaki dodatak koji implementira WP Consent API:

| Dodatak | Aktivnih instalacija | Napomena |
|---------|----------------------|----------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5M+ | WP Consent API ugrađen |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1M+ | Koautor WP Consent API-ja |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1M+ | Kompatibilan sa WP Consent API-jem |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300K+ | Kompatibilan sa WP Consent API-jem |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100K+ | Kompatibilan sa WP Consent API-jem |

### Podešavanje

1. Instaliraj i aktiviraj [WP Consent API](https://wordpress.org/plugins/wp-consent-api/).
2. Instaliraj i podesi svoj dodatak za saglasnost za kolačiće.
3. Instaliraj i podesi Advanced Pixel for Barion.

Ništa više — saglasnost se obrađuje automatski.

---

## Nivo 2: Cookie Law Info (rezerva)

Koristi se kada WP Consent API nije dostupan, a
[Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) / CookieYes jeste.

### Kako radi

1. Dodatak proverava globalni objekat `CLI` i njegov `allowedCategories`.
2. Ako kolačić `cookielawinfo-checkbox-non-necessary` već ima vrednost `yes` — posetilac koji se vraća i ranije je prihvatio — `grantConsent` se šalje odmah.
3. Prate se klikovi na elemente `.cli_action_button` u traci. Kratko posle klika dodatak ponovo čita kolačić i prema tome šalje `grantConsent` ili `rejectConsent`.

### Podešavanje

Nema ga. Instaliraj oba dodatka i radi.

---

## Nivo 3: Ručna integracija

Za sopstvene upravljače saglasnošću ili gde ništa od navedenog ne važi.

### Metod 1: JavaScript funkcije (preporučeno)

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

### Metod 2: Sopstveni DOM događaji

```javascript
// Davanje saglasnosti
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Odbijanje saglasnosti
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### Metod 3: WordPress action hook

```php
// U tvom dodatku za upravljanje saglasnošću ili u temi
add_action('wc_barion_pixel_footer_scripts', 'my_barion_consent_handler');

function my_barion_consent_handler() {
    ?>
    <script>
    // Ovde ide tvoja logika saglasnosti
    </script>
    <?php
}
```

### Primeri za pojedine upravljače saglasnošću

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

## Šta i dalje moraš sam

Dodatak prosleđuje saglasnost. Tvoje politike neće napisati ni traku podesiti, a Barion traži i
jedno i drugo. Iz
[Barionovih zahteva](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements):

- **Dodaj Barionove kolačiće u svoju politiku kolačića.** `ba_vid`, `ba_vid.xxx`, `ba_sid` i `ba_sid.xxx` idu među neophodne kolačiće — služe sprečavanju prevara na osnovu Barionovog legitimnog interesa i ne traže saglasnost. `BarionMarketingConsent.xxx` i kolačići medijskih i oglašivačkih partnera idu među marketinške kolačiće i traže saglasnost.
- **Pomeni Barion Pixel u svojoj politici privatnosti** i poveži Barionovo [obaveštenje o privatnosti](https://www.barion.com/en/privacy-notice/).
- **Omogući kupcima da u svakom trenutku promene ili povuku saglasnost** i pitaj ih ponovo. Barion traži da se traka ponovo pojavi barem svakih 13 meseci, a preporučuje 30 dana.
- **Koristi tekst trake koji Barion preporučuje**, gde god možeš. Nalazi se na stranici sa zahtevima i pokriva deljenje podataka sa partnerima koje Barion Pixel podrazumeva.

---

## Kako saglasnost utiče na piksel

| Stanje | Osnovni piksel (bp.js) | pageView | Prikupljanje marketinških podataka |
|--------|------------------------|----------|------------------------------------|
| Pre bilo kakve odluke o saglasnosti | Učitan | Šalje se (sprečavanje prevara) | Ne |
| Posle `grantConsent` | Učitan | Šalje se | Da |
| Posle `rejectConsent` | Učitan | Šalje se (sprečavanje prevara) | Ne |

---

## Testiranje

1. Uključi **režim za otklanjanje grešaka** u Podešavanja > Barion Pixel.
2. Otvori konzolu pregledača (F12).
3. Prati ove poruke:

| Poruka | Značenje |
|--------|----------|
| `Consent auto-granted via WP Consent API` | Nivo 1, saglasnost je pri učitavanju već postojala |
| `Consent granted via WP Consent API change event` | Nivo 1, kupac je upravo prihvatio |
| `Consent rejected via WP Consent API change event` | Nivo 1, kupac je upravo odbio |
| `Cookie Law Info detected, initial non-necessary cookie: …` | Preuzeo je nivo 2, sa pročitanom vrednošću kolačića |
| `Cookie Law Info button clicked, non-necessary cookie: …` | Nivo 2, kupac je koristio traku |
| `No consent manager detected…` | Nivo 3 — ništa nije pronađeno, funkcije pozovi sam |
| `Consent granted (grantConsent)` | `grantConsent` je stigao do bp.js (bilo koji nivo) |
| `Consent rejected (rejectConsent)` | `rejectConsent` je stigao do bp.js (bilo koji nivo) |

Sve poruke imaju prefiks `[Barion Pixel]`.

4. Testiraj na svojoj traci i put prihvatanja i put odbijanja.
5. Funkcije saglasnosti bezbedno se mogu pozivati više puta.
