> 🌐 Aceasta este o traducere automată. Corecțiile comunității sunt binevenite!
>
> [English version](../../cookie-consent.md)

# Integrare consimțământ cookie

Aici sursa de adevăr este pagina proprie a Barion:
[Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements).
Tot acolo găsești textul de bară de consimțământ recomandat de Barion și lista actuală a
partenerilor de publicitate. Citește-o înainte de lansarea în producție — conformitatea este
responsabilitatea comerciantului, nu a plugin-ului.

Barion listează `grantConsent` și printre evenimentele care
[trebuie implementate](https://docs.barion.com/Implementing_the_Full_Barion_Pixel)
înainte ca o integrare Full Pixel să fie aprobată. Un magazin care nu îl trimite niciodată nu
este eligibil pentru comisioanele reduse, oricât de complet ar fi restul integrării.

## Ce face plugin-ul

Scriptul pixelului de bază se încarcă întotdeauna, iar `pageView` se declanșează întotdeauna.
Barion documentează asta drept interes legitim: pixelul de bază există pentru prevenirea fraudei
la plată, iar datele colectate fără consimțământ de marketing sunt folosite doar în acest scop.

Peste asta, plugin-ul apelează `bp('consent', 'grantConsent')` când clientul acceptă cookie-urile
de marketing și `bp('consent', 'rejectConsent')` când le refuză. Barion le listează pe amândouă
ca obligatorii. Bara ta trebuie deci să ofere o opțiune reală de refuz — cu o bară doar cu
„Accept”, plugin-ul nu are ce semnala.

## Cum este detectat consimțământul

Plugin-ul nu alege un singur manager de consimțământ. Se abonează simultan la fiecare semnal de
consimțământ pe care îl cunoaște și transmite primul răspuns real și fiecare schimbare
ulterioară. Ordinea de încărcare nu contează: ascultătorii sunt înregistrați înainte să existe
vreun manager de consimțământ, așa că o bară care apare mai târziu este tot prinsă. Un vizitator
care revine nu vede nicio bară și deci nu declanșează niciun eveniment — de aceea plugin-ul
caută în plus un manager de consimțământ la fiecare jumătate de secundă până răspunde unul, și
renunță la zece secunde după încărcarea paginii.

Acestea funcționează fără plugin suplimentar:

| Manager de consimțământ | Citit prin |
|---|---|
| [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) | `wp_has_consent('marketing')` și `wp_listen_for_consent_change`, dar abia după ce o bară înregistrează la el un tip de consimțământ |
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | `getCkyConsent()` și `cookieyes_consent_update` |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | `cmplz_has_consent('marketing')` și `cmplz_status_change` |
| [Cookiebot](https://wordpress.org/plugins/cookiebot/) | `Cookiebot.consent.marketing` și `CookiebotOnAccept` / `CookiebotOnDecline` / `CookiebotOnConsentReady` |
| Cookie Law Info 2.x, bară veche | cookie-ul `cookielawinfo-checkbox-non-necessary`, recitit după un clic pe bară |
| Orice altceva | apelezi tu funcțiile — vezi [Integrare manuală](#integrare-manuală) |

Pentru toate se aplică trei reguli:

- **Consimțământul se trimite când vizitatorul răspunde pe bară, niciodată la încărcarea paginii.** Barion așteaptă `grantConsent` în momentul clicului și respinge o integrare care îl trimite înainte ca vizitatorul să fi atins ceva — din partea Barion asta arată ca un magazin care nu întreabă niciodată. Plugin-ul citește deci starea consimțământului la încărcare, dar o păstrează pentru sine și trimite doar ce decide vizitatorul la această încărcare de pagină.
- **Înainte de răspunsul vizitatorului nu se trimite nimic.** La o încărcare de pagină fără consimțământ de marketing, plugin-ul tace în loc să trimită `rejectConsent`. Până când bara nu primește răspuns, nu e nimic de raportat.
- **Se trimit doar schimbările.** O stare repetată identic nu se trimite de două ori, ceea ce contează pentru că un singur clic poate ajunge prin două adaptoare deodată.

Un vizitator care revine și care a acceptat la o vizită anterioară nu declanșează deci nimic —
și așa este corect: bp.js păstrează răspunsul în propriul cookie `BarionMarketingConsent`, deci
Barion îl are deja. Tocmai retrimiterea la fiecare încărcare de pagină a dus la respingerea
integrării. Ca să vezi `grantConsent` declanșându-se, șterge întâi cookie-urile, ca bara să
întrebe din nou.

## WP Consent API — în continuare recomandat

[WP Consent API](https://wordpress.org/plugins/wp-consent-api/) este standardul WordPress pentru
transmiterea consimțământului între plugin-uri, iar Barion Pixel se înregistrează la categoria
sa `marketing`. Este un **plugin separat** — nu face parte din WordPress și nici din bara ta de
cookie-uri. O
[propunere de includere în nucleu](https://make.wordpress.org/core/2024/12/04/lets-reconsider-adopting-the-wp-consent-api/)
este deschisă, dar nu a fost adoptată.

Instalează-l când bara ta de cookie-uri nu este în tabelul de mai sus. Majoritatea barelor
suportă WP Consent API, dar doar cât timp acel plugin este activ: CookieYes, de exemplu, își
încarcă puntea doar dacă există clasa `WP_CONSENT_API`. Fără el, acele bare nu transmit nimic,
iar plugin-ul trebuie să se bazeze pe integrările directe.

| Plugin | Instalări active |
|--------|----------------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5 mil.+ |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1 mil.+ |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1 mil.+ |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300 mii+ |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100 mii+ |

---

## Integrare manuală

Pentru manageri de consimțământ personalizați sau când nimic din cele de mai sus nu se aplică.

### Metoda 1: Funcții JavaScript (recomandat)

```javascript
// When user accepts marketing cookies
function onMarketingConsentGranted() {
    if (typeof window.wcBarionGrantConsent === 'function') {
        window.wcBarionGrantConsent();
    }
}

// When user rejects marketing cookies
function onMarketingConsentRejected() {
    if (typeof window.wcBarionRejectConsent === 'function') {
        window.wcBarionRejectConsent();
    }
}
```

### Metoda 2: Evenimente DOM personalizate

```javascript
// Grant consent
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Reject consent
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### Metoda 3: Hook de acțiune WordPress

```php
// In your consent manager plugin or theme
add_action('wc_barion_pixel_footer_scripts', 'my_barion_consent_handler');

function my_barion_consent_handler() {
    ?>
    <script>
    // Your custom consent logic here
    </script>
    <?php
}
```

### Exemplu: OneTrust

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

## Ce trebuie să rezolvi singur

Plugin-ul transmite consimțământul. Nu îți poate scrie politicile și nici configura bara, iar
Barion cere ambele. Din
[cerințele Barion](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements):

- **Adaugă cookie-urile Barion în politica ta de cookie-uri.** `ba_vid`, `ba_vid.xxx`, `ba_sid` și `ba_sid.xxx` fac parte dintre cookie-urile esențiale — servesc prevenirii fraudei pe baza interesului legitim al Barion și nu necesită consimțământ. `BarionMarketingConsent.xxx` și cookie-urile partenerilor media și de publicitate fac parte dintre cookie-urile de marketing și necesită consimțământ.
- **Menționează Barion Pixel în politica ta de confidențialitate** și pune link către [nota de confidențialitate](https://www.barion.com/en/privacy-notice/) a Barion.
- **Permite clienților să schimbe sau să retragă consimțământul oricând** și întreabă-i din nou. Barion cere ca bara să reapară cel puțin o dată la 13 luni și recomandă 30 de zile.
- **Folosește textul de bară recomandat de Barion** unde poți. Se află pe pagina cu cerințe și acoperă partajarea de date cu partenerii pe care o presupune Barion Pixel.

---

## Cum afectează consimțământul pixelul

| Stare | Pixel de bază (bp.js) | pageView | Colectare date de marketing |
|-------|--------------------|----------|--------------------------|
| Înainte de orice acțiune de consimțământ | Încărcat | Se declanșează (prevenirea fraudei) | Nu |
| După `grantConsent` | Încărcat | Se declanșează | Da |
| După `rejectConsent` | Încărcat | Se declanșează (prevenirea fraudei) | Nu |

---

## Testare

1. Activează **Debug Mode** în Setări > Barion Pixel.
2. Deschide consola browserului (F12).
3. Caută aceste mesaje:

| Mesaj | Semnificație |
|---------|---------|
| `Consent manager detected: …` | Managerii numiți au fost găsiți și conectați |
| `No consent manager detected…` | Nu s-a găsit nimic — apelează tu funcțiile |
| `Consent granted (grantConsent)` | `grantConsent` a ajuns la bp.js |
| `Consent rejected (rejectConsent)` | `rejectConsent` a ajuns la bp.js |

Toate mesajele au prefixul `[Barion Pixel]`.

4. Testează pe bara ta atât calea de acceptare, cât și cea de refuz.
5. Funcțiile de consimțământ pot fi apelate în siguranță de mai multe ori.

`No consent manager detected` apare și ca avertisment pe pagina de setări a plugin-ului atunci
când plugin-ul WP Consent API este inactiv, pentru că aceasta este eroarea din cauza căreia o
integrare Full Pixel este respinsă.

Pagina de setări are un al doilea avertisment pentru capcana din spatele acesteia: WP Consent
API este activ, dar nicio bară de cookie-uri nu s-a înregistrat la el. De unul singur, API-ul
răspunde „acordat” pentru toată lumea, pentru că un tip de consimțământ nesetat este felul lui
de a spune că nu îl conduce nicio bară. Instalarea lui lângă o bară care nu îl suportă nu
conectează deci nimic — face doar ca fiecare vizitator să pară că a consimțit. În această stare,
plugin-ul îl ignoră.
