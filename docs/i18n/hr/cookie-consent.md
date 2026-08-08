> 🌐 Ovo je automatski prijevod. Ispravci zajednice su dobrodošli!
>
> [English version](../../cookie-consent.md)

# Integracija pristanka na kolačiće

## Pregled

Barion Pixel zahtijeva eksplicitni pristanak korisnika prije prikupljanja marketinških podataka (usklađenost s GDPR-om). Dodatak mora pozvati `bp('consent', 'grantConsent')` kada korisnik prihvati, i `bp('consent', 'rejectConsent')` kada korisnik odbije. Oba događaja su obvezna prema Barion zahtjevima.

Osnovna skripta pixela uvijek se učitava radi sprječavanja prijevare, ali marketinški podaci se ne prikupljaju dok se pristanak eksplicitno ne odobri ili odbije.

**Važno:** Tvoj banner za kolačiće mora nuditi i opciju prihvaćanja i opciju odbijanja. "Zid kolačića" (samo prihvaćanje) nije usklađen s GDPR-om od 2020. godine i Barion ga neće prihvatiti.

Dodatak podržava četiri razine integracije pristanka, provjeravane po redu:

1. **Zabilježeni okidač** — signal kolačića koji je uhvatio čarobnjak za postavljanje; pobjeđuje
   samo kada su zabilježena oba signala, prihvaćanje i odbijanje, jer ih je vlasnik trgovine
   namjerno postavio. Napola naučen okidač — zabilježen samo jedan signal — potpuno se
   zanemaruje, a dodatak prelazi na sljedeću razinu, jer Barion zahtijeva i `grantConsent` i
   `rejectConsent`.
2. **WP Consent API** (preporučeno) — univerzalan, radi sa svim glavnim dodacima za kolačiće
3. **Cookie Law Info** (rezervna opcija) — izravna integracija za stranice koje koriste CookieYes/Cookie Law Info
4. **Ručno** — za prilagođene upravitelje pristanka ili rubne slučajeve

---

## Ploča stanja

Postavke › Barion Pixel otvara se s pločom stanja. Ona pokreće sve provjere navedene niže i prvo
prikazuje najgori rezultat. Kada sve prođe, sažima se u jedan zeleni redak.

Najvažnija provjera je **"No cookie banner plugin sets a consent type"** (nijedan dodatak s
bannerom za kolačiće ne postavlja vrstu pristanka). WP Consent API prijavljuje pristanak za svaku
kategoriju kada ništa ne postavlja vrstu pristanka:

> If there's no consent management plugin to set it, it will return `false`. This will cause all
> consent categories to return `true`.

Stranica s aktivnim WP Consent API-jem, ali bez bannera za kolačiće stoga odobrava Barion pristanak
za svakog posjetitelja, bez ikakvog stvarno prikupljenog pristanka. To krši GDPR i Barion uvjete.

Neki banneri postavljaju vrstu pristanka samo u pregledniku, pa ploča prvo prijavljuje upozorenje i
nudi gumb **Check in browser**. Ta provjera čita stvarne vrijednosti s vašeg frontenda prije bilo
kakve interakcije te sukladno tome oboji redak crveno ili zeleno.

### Barionovi kolačići

`bp.js` postavlja tri kolačića prve strane na vašoj vlastitoj domeni. Svakom se nazivu za vrijeme
izvođenja dodaje hash vaše domene.

| Kolačić | Trajanje | Svrha |
|---------|----------|-------|
| `ba_sid` | 30 minuta | Grupira preglede stranica u jednu sesiju. Barion ga koristi za sprječavanje prijevare. |
| `ba_vid` | 1,5 godina | Identificira povratnog posjetitelja radi marketinške analitike. |
| `BarionMarketingConsent` | 1,5 godina, uklanja se kada posjetitelj odbije | Bilježi odabir pristanka. |

Uz aktivan dodatak WP Consent API, dodatak sva tri automatski prijavljuje, pa se pojavljuju u vašoj
politici kolačića. Bez njega ih je potrebno dodati ručno.

## Čarobnjak za postavljanje

Ako nijedan izvor pristanka ne radi, ploča nudi **Set up consent**. Čarobnjak otvara vašu trgovinu
u novoj kartici, vi prihvaćate u vlastitom banneru, a dodatak bilježi koji se kolačić promijenio.
Isto ponavljate za odbijanje. Barion zahtijeva i `grantConsent` i `rejectConsent`, pa čarobnjak
odbija spremiti dok ne prikupi oboje.

Čarobnjak pohranjuje naziv kolačića, prihvaćenu i odbijenu vrijednost te do pet naziva događaja.
Nikada ne pohranjuje niti pokreće JavaScript koji sami dostavite. Snimač se učitava samo za
prijavljenog administratora koji stigne s valjanim nonceom; posjetitelju se nikada ne učitava.

### Zašto je kategorija pristanka fiksna

Dodatak uvijek traži kategoriju `marketing` i ne nudi izbor. WP Consent API definira pet fiksnih
kategorija, a dodaci s bannerima za kolačiće u kodu mapiraju svoje vlastite kategorije na njih.
CookieYes mapira Advertisement na marketing, Analytics na statistics, Functional na preferences i
Performance na functional. Tu mapu ne možete promijeniti.

Barion zahtijeva pristanak u marketinške svrhe, pa je `marketing` jedina ispravna kategorija.
Izbornik bi omogućio pokretanje Bariona na potvrdnom okviru za statistiku, što krši Barion uvjete.

---

## Razina 2: WP Consent API (Preporučeno)

[WP Consent API](https://wordpress.org/plugins/wp-consent-api/) je WordPress standard za komunikaciju o pristanku. Podržavaju ga svi glavni dodaci za pristanak na kolačiće.

### Kako radi

Dodatak provjerava JavaScript funkciju `wp_has_consent()` za vrijeme izvođenja. Ako je WP Consent API dostupan:

1. Pri učitavanju stranice provjerava je li `marketing` pristanak odobren ili odbijen
2. Poziva `bp('consent', 'grantConsent')` ako je marketinški pristanak odobren
3. Poziva `bp('consent', 'rejectConsent')` ako marketinški pristanak nije odobren
4. Sluša događaj `wp_listen_for_consent_change` za ažuriranja pristanka u stvarnom vremenu — odobrava ili odbija prema tome

### Podržani dodaci za kolačiće

Svaki dodatak koji implementira WP Consent API radit će automatski:

| Dodatak | Aktivne instalacije | Napomene |
|---------|---------------------|---------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5M+ | WP Consent API ugrađen |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1M+ | Sukreator WP Consent API-ja |
| [Cookie Notice od dFactory](https://wordpress.org/plugins/cookie-notice/) | 1M+ | Kompatibilan s WP Consent API-jem |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300K+ | Kompatibilan s WP Consent API-jem |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100K+ | Kompatibilan s WP Consent API-jem |

### Postavljanje

1. Instaliraj i aktiviraj dodatak [WP Consent API](https://wordpress.org/plugins/wp-consent-api/)
2. Instaliraj i konfiguriraj željeni dodatak za pristanak na kolačiće (vidi tablicu iznad)
3. Instaliraj i konfiguriraj Advanced Pixel for Barion
4. Nije potrebna dodatna konfiguracija — pristanak se obrađuje automatski

### Kategorija pristanka

Barion Pixel je registriran u kategoriji pristanka `marketing` u WP Consent API-ju. Ovo je standardna kategorija za piksele za praćenje koji se koriste za retargeting i analitiku.

---

## Razina 3: Cookie Law Info (Rezervna opcija)

Ako WP Consent API nije dostupan, dodatak se vraća na izravnu integraciju s dodatkom [Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) / CookieYes.

### Kako radi

1. Provjerava globalni JavaScript objekt `CLI`
2. Čita kolačić `cookielawinfo-checkbox-non-necessary`; ako je njegova vrijednost točno `yes`, odmah odobrava pristanak
3. Inače ne poduzima ništa dok posjetitelj ne stupi u interakciju s bannerom
4. Sluša klikove na bilo koji element koji odgovara `.cli_action_button`
5. 100 milisekundi nakon klika ponovno čita isti kolačić i sukladno tome odobrava ili odbija pristanak

### Postavljanje

Nije potrebna konfiguracija. Instaliraj oba dodatka i integracija radi automatski.

---

## Razina 4: Ručna integracija

Za prilagođene upravitelje pristanka ili okruženja gdje niti WP Consent API niti Cookie Law Info nisu dostupni.

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

### Metoda 2: Prilagođeni DOM događaji

```javascript
// Odobri pristanak
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Odbij pristanak
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### Metoda 3: WordPress action hook

```php
// U svom dodatku za upravljanje pristankom ili temi
add_action('wc_barion_pixel_footer_scripts', 'my_barion_consent_handler');

function my_barion_consent_handler() {
    ?>
    <script>
    // Ovdje dodaj svoju prilagođenu logiku pristanka
    </script>
    <?php
}
```

### Primjeri za specifične upravitelje pristanka

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

## Kako pristanak utječe na pixel

| Stanje | Osnovni pixel (bp.js) | pageView | Prikupljanje marketinških podataka |
|--------|----------------------|----------|-----------------------------------|
| Prije bilo koje radnje pristanka | Učitan | Pokreće se (sprječavanje prijevare) | Nema prikupljanja podataka |
| Nakon `grantConsent` | Učitan | Pokreće se | Omogućeno puno prikupljanje podataka |
| Nakon `rejectConsent` | Učitan | Pokreće se (sprječavanje prijevare) | Nema prikupljanja marketinških podataka |

Osnovni pixel uvijek se učitava za Barionovo sprječavanje prijevare. Pozivi `grantConsent` / `rejectConsent` kontroliraju prikupljaju li se marketinški podaci.

---

## Testiranje

1. Omogući **Način rada za otklanjanje pogrešaka** u Postavke > Barion Pixel
2. Otvori konzolu preglednika (F12)
3. Traži poruke u konzoli vezane za pristanak:
   - `[Barion Pixel] bp.js loaded by Advanced Pixel for Barion` — ovaj je dodatak učitao bp.js
   - `[Barion Pixel] bp.js already loaded by another plugin, skipping script load` — drugi dodatak (npr. Barion Payment Gateway) već je učitao bp.js
   - `[Barion Pixel] Base pixel initialized with ID: <id>` — osnovni pixel radi s vašim Pixel ID-om
   - `[Barion Pixel] Consent granted (grantConsent)` — pristanak je odobren (bilo koja razina)
   - `[Barion Pixel] Consent rejected (rejectConsent)` — pristanak je odbijen (bilo koja razina)
   - `[Barion Pixel] Consent auto-granted via WP Consent API` — Razina 2, pristanak je već bio dan pri učitavanju stranice
   - `[Barion Pixel] Consent granted via WP Consent API change event` — Razina 2, korisnik je prihvatio u banneru
   - `[Barion Pixel] Consent rejected via WP Consent API change event` — Razina 2, korisnik je odbio u banneru
   - `[Barion Pixel] Consent granted via the recorded cookie trigger` — Razina 1, prihvaćeno
   - `[Barion Pixel] Consent rejected via the recorded cookie trigger` — Razina 1, odbijeno
   - `[Barion Pixel] Cookie Law Info detected, initial non-necessary cookie: <value>` — Razina 3, vrijednost kolačića pročitana pri učitavanju stranice
   - `[Barion Pixel] Cookie Law Info button clicked, non-necessary cookie: <value>` — Razina 3, vrijednost kolačića pročitana nakon klika u banneru
   - `[Barion Pixel] No consent manager detected. Call window.wcBarionGrantConsent() or window.wcBarionRejectConsent() manually.` — Razina 4 (ručni način)

   Namjerno ne postoji poruka kada pristanak nedostaje pri prvom učitavanju putem WP Consent
   API-ja — dodatak bilježi samo kada djeluje, a ne kada šuti.
4. Testiraj tokove prihvaćanja i odbijanja na svom banneru za kolačiće
5. Funkcije pristanka sigurno je pozivati više puta (idempotentne su)
