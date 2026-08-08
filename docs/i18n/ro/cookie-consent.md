> 🌐 Aceasta este o traducere automată. Corecțiile comunității sunt binevenite!
>
> [English version](../../cookie-consent.md)

# Integrare consimțământ cookie

Aici sursa de adevăr este pagina proprie a Barion:
[Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements).
Tot acolo găsești textul de bară de consimțământ recomandat de Barion și lista actuală a
partenerilor de publicitate. Citește-o înainte de lansarea în producție — conformitatea este
responsabilitatea comerciantului, nu a plugin-ului.

## Ce face plugin-ul

Scriptul pixelului de bază se încarcă întotdeauna, iar `pageView` se trimite întotdeauna. Barion
documentează acest lucru ca interes legitim: pixelul de bază există pentru prevenirea fraudei la
plată, iar datele colectate fără consimțământ de marketing sunt folosite doar în acest scop.

Peste asta, plugin-ul apelează `bp('consent', 'grantConsent')` când clientul acceptă cookie-urile
de marketing și `bp('consent', 'rejectConsent')` când le refuză. Barion le listează pe ambele ca
obligatorii. Bara ta trebuie deci să ofere o opțiune reală de refuz — la o bară care cunoaște doar
acceptul, plugin-ul nu are ce semnala.

Plugin-ul caută un manager de consimțământ în această ordine și se oprește la primul găsit:

1. **WP Consent API** (recomandat) — universal, funcționează cu toate plugin-urile mari de cookie-uri
2. **Cookie Law Info** (rezervă) — integrare directă pentru CookieYes / Cookie Law Info
3. **Manual** — pentru manageri de consimțământ proprii

---

## Nivelul 1: WP Consent API (recomandat)

[WP Consent API](https://wordpress.org/plugins/wp-consent-api/) este standardul WordPress pentru
transmiterea consimțământului între plugin-uri. Barion Pixel se înregistrează în categoria
`marketing`.

### Cum funcționează

După `DOMContentLoaded`, plugin-ul verifică dacă există funcția `wp_has_consent()`. Dacă există:

1. Dacă acordul `marketing` este deja dat, `grantConsent` se trimite imediat.
2. De atunci încolo, plugin-ul ascultă `wp_listen_for_consent_change` și trimite `grantConsent` sau `rejectConsent` la fiecare schimbare.

Observă ce *nu* apare în listă: la o încărcare de pagină fără consimțământ de marketing, plugin-ul
tace, în loc să trimită `rejectConsent`. Cât timp clientul nu a răspuns barei, nu e nimic de
raportat — iar răspunsul sosește prin evenimentul de schimbare.

### Plugin-uri de cookie-uri suportate

Funcționează automat orice plugin care implementează WP Consent API:

| Plugin | Instalări active | Notă |
|--------|------------------|------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5M+ | WP Consent API integrat |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1M+ | Co-creator al WP Consent API |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1M+ | Compatibil cu WP Consent API |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300K+ | Compatibil cu WP Consent API |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100K+ | Compatibil cu WP Consent API |

### Configurare

1. Instalează și activează [WP Consent API](https://wordpress.org/plugins/wp-consent-api/).
2. Instalează și configurează plugin-ul tău de consimțământ pentru cookie-uri.
3. Instalează și configurează Advanced Pixel for Barion.

Nimic în plus — consimțământul este tratat automat.

---

## Nivelul 2: Cookie Law Info (rezervă)

Se folosește când WP Consent API nu este disponibil, dar
[Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) / CookieYes este.

### Cum funcționează

1. Plugin-ul verifică obiectul global `CLI` și `allowedCategories` al acestuia.
2. Dacă cookie-ul `cookielawinfo-checkbox-non-necessary` are deja valoarea `yes` — un vizitator revenit care a acceptat — `grantConsent` se trimite imediat.
3. Sunt urmărite clicurile pe elementele `.cli_action_button` din bară. La scurt timp după clic, plugin-ul recitește cookie-ul și trimite `grantConsent` sau `rejectConsent` în consecință.

### Configurare

Niciuna. Instalează ambele plugin-uri și funcționează.

---

## Nivelul 3: Integrare manuală

Pentru manageri de consimțământ proprii sau acolo unde nimic din cele de mai sus nu se aplică.

### Metoda 1: Funcții JavaScript (recomandat)

```javascript
// Când utilizatorul acceptă cookie-urile de marketing
function onMarketingConsentGranted() {
    if (typeof window.wcBarionGrantConsent === 'function') {
        window.wcBarionGrantConsent();
    }
}

// Când utilizatorul refuză cookie-urile de marketing
function onMarketingConsentRejected() {
    if (typeof window.wcBarionRejectConsent === 'function') {
        window.wcBarionRejectConsent();
    }
}
```

### Metoda 2: Evenimente DOM proprii

```javascript
// Acordarea consimțământului
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Refuzul consimțământului
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### Metoda 3: Hook de acțiune WordPress

```php
// În plugin-ul tău de management al consimțământului sau în temă
add_action('wc_barion_pixel_footer_scripts', 'my_barion_consent_handler');

function my_barion_consent_handler() {
    ?>
    <script>
    // Aici vine logica ta de consimțământ
    </script>
    <?php
}
```

### Exemple pentru manageri de consimțământ specifici

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

## Ce trebuie să faci tu

Plugin-ul transmite consimțământul mai departe. Politicile nu ți le scrie și bara nu ți-o
configurează, iar Barion cere ambele. Din
[cerințele Barion](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements):

- **Adaugă cookie-urile Barion în politica ta de cookie-uri.** `ba_vid`, `ba_vid.xxx`, `ba_sid` și `ba_sid.xxx` intră la cookie-urile esențiale — servesc prevenirii fraudei pe baza interesului legitim al Barion și nu necesită consimțământ. `BarionMarketingConsent.xxx` și cookie-urile partenerilor media și de publicitate intră la cookie-urile de marketing și necesită consimțământ.
- **Menționează Barion Pixel în politica ta de confidențialitate** și pune un link către [nota de confidențialitate](https://www.barion.com/en/privacy-notice/) a Barion.
- **Permite clienților să își modifice sau retragă consimțământul oricând** și întreabă-i din nou. Barion cere ca bara să reapară cel puțin o dată la 13 luni și recomandă 30 de zile.
- **Folosește textul de bară recomandat de Barion** unde poți. Se află pe pagina de cerințe și acoperă partajarea de date cu partenerii pe care o presupune Barion Pixel.

---

## Cum afectează consimțământul pixelul

| Stare | Pixel de bază (bp.js) | pageView | Colectare date de marketing |
|-------|-----------------------|----------|-----------------------------|
| Înainte de orice decizie de consimțământ | Încărcat | Se trimite (prevenirea fraudei) | Nu |
| După `grantConsent` | Încărcat | Se trimite | Da |
| După `rejectConsent` | Încărcat | Se trimite (prevenirea fraudei) | Nu |

---

## Testare

1. Activează **modul depanare** în Setări > Barion Pixel.
2. Deschide consola browserului (F12).
3. Urmărește aceste mesaje:

| Mesaj | Semnificație |
|-------|--------------|
| `Consent auto-granted via WP Consent API` | Nivelul 1, consimțământul exista deja la încărcare |
| `Consent granted via WP Consent API change event` | Nivelul 1, clientul tocmai a acceptat |
| `Consent rejected via WP Consent API change event` | Nivelul 1, clientul tocmai a refuzat |
| `Cookie Law Info detected, initial non-necessary cookie: …` | A preluat nivelul 2, cu valoarea cookie-ului citită |
| `Cookie Law Info button clicked, non-necessary cookie: …` | Nivelul 2, clientul a folosit bara |
| `No consent manager detected…` | Nivelul 3 — nu s-a găsit nimic, apelează funcțiile singur |
| `Consent granted (grantConsent)` | `grantConsent` a ajuns la bp.js (orice nivel) |
| `Consent rejected (rejectConsent)` | `rejectConsent` a ajuns la bp.js (orice nivel) |

Toate mesajele au prefixul `[Barion Pixel]`.

4. Testează pe bara ta atât calea de acceptare, cât și pe cea de refuz.
5. Funcțiile de consimțământ pot fi apelate repetat în siguranță.
