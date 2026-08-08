> 🌐 Aceasta este o traducere automată. Corecțiile comunității sunt binevenite!
>
> [English version](../../cookie-consent.md)

# Integrare consimțământ cookie

## Prezentare generală

Barion Pixel necesită consimțământul explicit al utilizatorului înainte de a colecta date de marketing (conformitate GDPR). Plugin-ul trebuie să apeleze `bp('consent', 'grantConsent')` când utilizatorul acceptă și `bp('consent', 'rejectConsent')` când utilizatorul refuză. Ambele evenimente sunt obligatorii conform cerințelor Barion.

Scriptul pixelului de bază se încarcă întotdeauna pentru prevenirea fraudei, dar nu se colectează date de marketing până când consimțământul nu este acordat sau refuzat în mod explicit.

**Important:** Bannerul tău de cookie-uri trebuie să ofere atât o opțiune de acceptare, cât și una de refuz. Un „zid de cookie-uri" (numai acceptare) nu este conform cu GDPR din 2020 și va fi respins de Barion.

Plugin-ul suportă patru niveluri de integrare a consimțământului, verificate în ordine:

1. **Declanșator înregistrat** — un semnal de cookie captat de asistentul de configurare; câștigă
   doar atunci când sunt înregistrate ambele semnale, atât cel de acceptare, cât și cel de refuz,
   pentru că proprietarul magazinului l-a setat în mod deliberat. Un declanșator „învățat pe
   jumătate" — cu un singur semnal înregistrat — este ignorat complet, iar plugin-ul trece la
   următorul nivel, pentru că Barion necesită atât `grantConsent`, cât și `rejectConsent`.
2. **WP Consent API** (recomandat) — universal, funcționează cu toate plugin-urile majore de cookie
3. **Cookie Law Info** (fallback) — integrare directă pentru site-urile care folosesc CookieYes/Cookie Law Info
4. **Manual** — pentru manageri de consimțământ personalizați sau cazuri speciale

---

## Panoul de stare

Setări › Barion Pixel se deschide cu un panou de stare. Acesta rulează fiecare verificare de mai
jos și afișează mai întâi cel mai grav rezultat. Când totul trece, se restrânge la o singură linie
verde.

Cea mai importantă verificare este **„No cookie banner plugin sets a consent type"** (niciun
plugin de banner de cookie nu setează un tip de consimțământ). WP Consent API raportează
consimțământ pentru fiecare categorie atunci când nimic nu setează un tip de consimțământ:

> If there's no consent management plugin to set it, it will return `false`. This will cause all
> consent categories to return `true`.

Un site cu WP Consent API activ, dar fără banner de cookie, acordă astfel consimțământul Barion
pentru fiecare vizitator, fără să fie colectat vreun consimțământ real. Acest lucru încalcă GDPR-ul
și termenii Barion.

Unele bannere setează tipul de consimțământ doar în browser, așa că panoul raportează mai întâi un
avertisment și oferă un buton **Check in browser**. Acea verificare citește valorile reale de pe
frontend-ul tău înainte de orice interacțiune și colorează linia în roșu sau verde în consecință.

### Cookie-urile Barion

`bp.js` setează trei cookie-uri proprii (first-party) pe domeniul tău. Fiecărui nume i se adaugă
la execuție un hash al domeniului tău.

| Cookie | Durată | Scop |
|--------|--------|------|
| `ba_sid` | 30 de minute | Grupează afișările de pagini într-o singură sesiune. Folosit de Barion pentru prevenirea fraudei. |
| `ba_vid` | 1,5 ani | Identifică un vizitator care revine, pentru analitica de marketing. |
| `BarionMarketingConsent` | 1,5 ani, eliminat când vizitatorul refuză | Înregistrează alegerea de consimțământ. |

Cu plugin-ul WP Consent API activ, plugin-ul declară automat toate cele trei, astfel încât apar în
politica ta de cookie-uri. Fără el, trebuie să le adaugi manual.

## Asistentul de configurare

Dacă nicio sursă de consimțământ nu funcționează, panoul oferă **Set up consent**. Asistentul îți
deschide magazinul într-o filă nouă, tu accepți în propriul tău banner, iar plugin-ul înregistrează
ce cookie s-a schimbat. Repeți același lucru pentru refuz. Barion necesită atât `grantConsent`, cât
și `rejectConsent`, așa că asistentul refuză să salveze până când le are pe amândouă.

Asistentul stochează un nume de cookie, valorile de acceptare și refuz și până la cinci nume de
evenimente. Nu stochează și nu rulează niciodată JavaScript furnizat de tine. Recorder-ul se
încarcă doar pentru un administrator autentificat care ajunge cu un nonce valid; nu se încarcă
niciodată pentru un vizitator.

### De ce categoria de consimțământ este fixă

Plugin-ul solicită întotdeauna categoria `marketing` și nu oferă nicio alegere. WP Consent API
definește cinci categorii fixe, iar plugin-urile de banner de cookie își mapează propriile
categorii pe acestea în cod. CookieYes mapează Advertisement pe marketing, Analytics pe statistics,
Functional pe preferences și Performance pe functional. Nu poți schimba această mapare.

Barion necesită consimțământ pentru scopuri de marketing, așa că `marketing` este singura categorie
corectă. Un selector ar permite declanșarea Barion pe o casetă de bifat pentru statistici, ceea ce
încalcă termenii Barion.

---

## Nivelul 2: WP Consent API (Recomandat)

[WP Consent API](https://wordpress.org/plugins/wp-consent-api/) este un standard WordPress pentru comunicarea consimțământului. Este suportat de toate plugin-urile majore de consimțământ cookie.

### Cum funcționează

Plugin-ul verifică funcția JavaScript `wp_has_consent()` la execuție. Dacă WP Consent API este disponibil:

1. La încărcarea paginii, verifică dacă consimțământul `marketing` este acordat sau refuzat
2. Apelează `bp('consent', 'grantConsent')` dacă consimțământul de marketing este acordat
3. Apelează `bp('consent', 'rejectConsent')` dacă consimțământul de marketing nu este acordat
4. Ascultă evenimentul `wp_listen_for_consent_change` pentru actualizări de consimțământ în timp real — acordă sau refuză în consecință

### Plugin-uri de cookie suportate

Orice plugin care implementează WP Consent API va funcționa automat:

| Plugin | Instalări active | Note |
|--------|----------------|-------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5M+ | WP Consent API integrat |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1M+ | Co-creator al WP Consent API |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1M+ | Compatibil cu WP Consent API |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300K+ | Compatibil cu WP Consent API |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100K+ | Compatibil cu WP Consent API |

### Configurare

1. Instalează și activează plugin-ul [WP Consent API](https://wordpress.org/plugins/wp-consent-api/)
2. Instalează și configurează plugin-ul preferat de consimțământ cookie (vezi tabelul de mai sus)
3. Instalează și configurează Advanced Pixel for Barion
4. Nu este necesară configurare suplimentară — consimțământul este gestionat automat

### Categoria de consimțământ

Barion Pixel este înregistrat în categoria de consimțământ `marketing` din WP Consent API. Aceasta este categoria standard pentru pixelii de urmărire utilizați pentru retargeting și analiză.

---

## Nivelul 3: Cookie Law Info (Fallback)

Dacă WP Consent API nu este disponibil, plugin-ul trece la integrarea directă cu plugin-ul [Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) / CookieYes.

### Cum funcționează

1. Verifică obiectul global JavaScript `CLI`
2. Citește cookie-ul `cookielawinfo-checkbox-non-necessary`; dacă valoarea sa este exact `yes`, acordă consimțământul imediat
3. Altfel, nu face nimic până când vizitatorul interacționează cu bannerul
4. Ascultă click-urile pe orice element care se potrivește cu `.cli_action_button`
5. La 100 de milisecunde după un click, recitește același cookie și acordă sau refuză consimțământul în consecință

### Configurare

Nu este necesară nicio configurare. Instalează ambele plugin-uri și integrarea funcționează automat.

---

## Nivelul 4: Integrare manuală

Pentru manageri de consimțământ personalizați sau medii în care nici WP Consent API, nici Cookie Law Info nu sunt disponibile.

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

### Metoda 2: Evenimente DOM personalizate

```javascript
// Acordă consimțământul
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Refuză consimțământul
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### Metoda 3: Hook de acțiune WordPress

```php
// În plugin-ul tău de manager de consimțământ sau temă
add_action('wc_barion_pixel_footer_scripts', 'my_barion_consent_handler');

function my_barion_consent_handler() {
    ?>
    <script>
    // Logica ta personalizată de consimțământ aici
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

## Cum afectează consimțământul pixelul

| Stare | Pixel de bază (bp.js) | pageView | Colectarea datelor de marketing |
|-------|--------------------|----------|--------------------------|
| Înainte de orice acțiune de consimțământ | Încărcat | Se declanșează (prevenirea fraudei) | Nu se colectează date |
| După `grantConsent` | Încărcat | Se declanșează | Colectarea completă de date activată |
| După `rejectConsent` | Încărcat | Se declanșează (prevenirea fraudei) | Nu se colectează date de marketing |

Pixelul de bază se încarcă întotdeauna pentru prevenirea fraudei Barion. Apelurile `grantConsent` / `rejectConsent` controlează dacă se colectează date de marketing.

---

## Testare

1. Activează **Mod depanare** în Setări > Barion Pixel
2. Deschide consola browserului (F12)
3. Caută mesajele de jurnal legate de consimțământ:
   - `[Barion Pixel] bp.js loaded by Advanced Pixel for Barion` — acest plugin a încărcat bp.js
   - `[Barion Pixel] bp.js already loaded by another plugin, skipping script load` — un alt plugin (de ex. Barion Payment Gateway) a încărcat deja bp.js
   - `[Barion Pixel] Base pixel initialized with ID: <id>` — pixelul de bază rulează cu ID-ul tău Pixel
   - `[Barion Pixel] Consent granted (grantConsent)` — consimțământul a fost acordat (orice nivel)
   - `[Barion Pixel] Consent rejected (rejectConsent)` — consimțământul a fost refuzat (orice nivel)
   - `[Barion Pixel] Consent auto-granted via WP Consent API` — Nivelul 2, consimțământ deja acordat la încărcarea paginii
   - `[Barion Pixel] Consent granted via WP Consent API change event` — Nivelul 2, utilizatorul a acceptat în banner
   - `[Barion Pixel] Consent rejected via WP Consent API change event` — Nivelul 2, utilizatorul a refuzat în banner
   - `[Barion Pixel] Consent granted via the recorded cookie trigger` — Nivelul 1, acceptat
   - `[Barion Pixel] Consent rejected via the recorded cookie trigger` — Nivelul 1, refuzat
   - `[Barion Pixel] Cookie Law Info detected, initial non-necessary cookie: <value>` — Nivelul 3, valoarea cookie-ului citită la încărcarea paginii
   - `[Barion Pixel] Cookie Law Info button clicked, non-necessary cookie: <value>` — Nivelul 3, valoarea cookie-ului citită după un click în banner
   - `[Barion Pixel] No consent manager detected. Call window.wcBarionGrantConsent() or window.wcBarionRejectConsent() manually.` — Nivelul 4 (mod manual)

   Nu există în mod deliberat niciun mesaj atunci când consimțământul lipsește la prima
   încărcare prin WP Consent API — plugin-ul înregistrează doar atunci când acționează, nu
   atunci când tace.
4. Testează atât fluxul de acceptare, cât și cel de refuz pe bannerul tău de cookie
5. Funcțiile de consimțământ pot fi apelate de mai multe ori în siguranță (idempotente)
