> 🌐 Ovo je automatski prevod. Ispravke zajednice su dobrodošle!
>
> [English version](../../cookie-consent.md)

# Integracija saglasnosti za kolačiće

## Pregled

Barion Pixel zahteva eksplicitnu saglasnost korisnika pre prikupljanja marketinških podataka (usklađenost sa GDPR). Dodatak mora pozvati `bp('consent', 'grantConsent')` kada korisnik prihvati, i `bp('consent', 'rejectConsent')` kada korisnik odbije. Oba događaja su obavezna prema zahtevima Bariona.

Skripta osnovnog piksela se uvek učitava radi sprečavanja prevara, ali marketinški podaci se ne prikupljaju dok saglasnost ne bude eksplicitno odobrena ili odbijena.

**Važno:** Tvoj baner sa kolačićima mora nuditi i opciju prihvatanja i opciju odbijanja. "Zid kolačića" (samo prihvatanje) nije u skladu sa GDPR od 2020. godine i Barion će ga odbiti.

Dodatak podržava četiri nivoa integracije saglasnosti, koji se proveravaju sledećim redom:

1. **Zabeleženi okidač** — signal kolačića koji je uhvatio čarobnjak za podešavanje; pobeđuje samo
   kada su zabeležena oba signala, i prihvatanje i odbijanje, jer ih je vlasnik prodavnice
   namerno podesio. Napola naučen okidač — zabeležen samo jedan signal — potpuno se zanemaruje, a
   dodatak prelazi na sledeći nivo, jer Barion zahteva i `grantConsent` i `rejectConsent`.
2. **WP Consent API** (preporučeno) — univerzalno, radi sa svim glavnim dodacima za kolačiće
3. **Cookie Law Info** (rezervna opcija) — direktna integracija za sajtove koji koriste CookieYes/Cookie Law Info
4. **Ručno** — za prilagođene menadžere saglasnosti ili posebne slučajeve

---

## Tabla stanja

Podešavanja › Barion Pixel se otvara sa tablom stanja. Ona pokreće sve provere navedene ispod i
prvo prikazuje najgori rezultat. Kada sve prođe, sažima se u jedan zeleni red.

Najvažnija provera je **"No cookie banner plugin sets a consent type"** (nijedan dodatak sa
banerom za kolačiće ne podešava vrstu saglasnosti). WP Consent API prijavljuje saglasnost za svaku
kategoriju kada ništa ne podešava vrstu saglasnosti:

> If there's no consent management plugin to set it, it will return `false`. This will cause all
> consent categories to return `true`.

Sajt sa aktivnim WP Consent API, ali bez banera za kolačiće, stoga odobrava Barion saglasnost za
svakog posetioca, bez ikakve stvarno prikupljene saglasnosti. To krši GDPR i Barion uslove.

Neki baneri podešavaju vrstu saglasnosti samo u pregledaču, pa tabla prvo prijavljuje upozorenje i
nudi dugme **Check in browser**. Ta provera čita stvarne vrednosti sa vašeg frontenda pre bilo
kakve interakcije i shodno tome oboji red crveno ili zeleno.

### Barion kolačići

`bp.js` podešava tri kolačića prve strane na vašem sopstvenom domenu. Svakom nazivu se u toku rada
dodaje heš vašeg domena.

| Kolačić | Trajanje | Svrha |
|---------|----------|-------|
| `ba_sid` | 30 minuta | Grupiše preglede stranica u jednu sesiju. Barion ga koristi za sprečavanje prevara. |
| `ba_vid` | 1,5 godina | Identifikuje posetioca koji se vraća radi marketinške analitike. |
| `BarionMarketingConsent` | 1,5 godina, uklanja se kada posetilac odbije | Beleži izbor saglasnosti. |

Uz aktivan dodatak WP Consent API, dodatak automatski prijavljuje sva tri, pa se pojavljuju u vašoj
politici kolačića. Bez njega ih je potrebno dodati ručno.

## Čarobnjak za podešavanje

Ako nijedan izvor saglasnosti ne radi, tabla nudi **Set up consent**. Čarobnjak otvara vašu
prodavnicu u novoj kartici, vi prihvatate u sopstvenom baneru, a dodatak beleži koji se kolačić
promenio. Isto ponavljate za odbijanje. Barion zahteva i `grantConsent` i `rejectConsent`, pa
čarobnjak odbija da sačuva dok ne dobije oba.

Čarobnjak čuva naziv kolačića, prihvaćenu i odbijenu vrednost i do pet naziva događaja. Nikada ne
čuva niti pokreće JavaScript koji sami dostavite. Snimač se učitava samo za prijavljenog
administratora koji stigne sa važećim nonce-om; posetiocu se nikada ne učitava.

### Zašto je kategorija saglasnosti fiksna

Dodatak uvek traži kategoriju `marketing` i ne nudi izbor. WP Consent API definiše pet fiksnih
kategorija, a dodaci sa banerima za kolačiće u kodu mapiraju svoje sopstvene kategorije na njih.
CookieYes mapira Advertisement na marketing, Analytics na statistics, Functional na preferences i
Performance na functional. Tu mapu ne možete promeniti.

Barion zahteva saglasnost u marketinške svrhe, pa je `marketing` jedina ispravna kategorija. Izbor
bi omogućio pokretanje Bariona na polju za potvrdu statistike, što krši Barion uslove.

---

## Nivo 2: WP Consent API (preporučeno)

[WP Consent API](https://wordpress.org/plugins/wp-consent-api/) je WordPress standard za komunikaciju saglasnosti. Podržan je od strane svih glavnih dodataka za saglasnost za kolačiće.

### Kako funkcioniše

Dodatak proverava JavaScript funkciju `wp_has_consent()` u realnom vremenu. Ako je WP Consent API dostupan:

1. Pri učitavanju stranice, proverava da li je odobrena ili odbijena `marketing` saglasnost
2. Poziva `bp('consent', 'grantConsent')` ako je marketinška saglasnost odobrena
3. Poziva `bp('consent', 'rejectConsent')` ako marketinška saglasnost nije odobrena
4. Osluškuje događaj `wp_listen_for_consent_change` radi ažuriranja saglasnosti u realnom vremenu — odobrava ili odbija u skladu sa tim

### Podržani dodaci za kolačiće

Bilo koji dodatak koji implementira WP Consent API radiće automatski:

| Dodatak | Aktivne instalacije | Napomene |
|---------|---------------------|---------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5M+ | WP Consent API ugrađen |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1M+ | Sukreator WP Consent API |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1M+ | Kompatibilan sa WP Consent API |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300K+ | Kompatibilan sa WP Consent API |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100K+ | Kompatibilan sa WP Consent API |

### Podešavanje

1. Instaliraj i aktiviraj dodatak [WP Consent API](https://wordpress.org/plugins/wp-consent-api/)
2. Instaliraj i podesi preferirani dodatak za saglasnost za kolačiće (pogledaj tabelu iznad)
3. Instaliraj i podesi Advanced Pixel for Barion
4. Nije potrebna dodatna konfiguracija — saglasnost se automatski upravlja

### Kategorija saglasnosti

Barion Pixel je registrovan u kategoriji saglasnosti `marketing` u WP Consent API. Ovo je standardna kategorija za piksele za praćenje koji se koriste za retargeting i analitiku.

---

## Nivo 3: Cookie Law Info (rezervna opcija)

Ako WP Consent API nije dostupan, dodatak prelazi na direktnu integraciju sa [Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) / CookieYes dodatkom.

### Kako funkcioniše

1. Proverava globalni JavaScript objekat `CLI`
2. Čita kolačić `cookielawinfo-checkbox-non-necessary`; ako je njegova vrednost tačno `yes`, odmah odobrava saglasnost
3. U suprotnom ne preduzima ništa dok posetilac ne stupi u interakciju sa banerom
4. Osluškuje klikove na bilo koji element koji odgovara `.cli_action_button`
5. 100 milisekundi nakon klika ponovo čita isti kolačić i shodno tome odobrava ili odbija saglasnost

### Podešavanje

Nije potrebna konfiguracija. Instaliraj oba dodatka i integracija radi automatski.

---

## Nivo 4: Ručna integracija

Za prilagođene menadžere saglasnosti ili okruženja gde ni WP Consent API ni Cookie Law Info nisu dostupni.

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

### Metod 2: Prilagođeni DOM događaji

```javascript
// Odobri saglasnost
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Odbij saglasnost
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### Metod 3: WordPress action hook

```php
// U tvom dodatku za menadžer saglasnosti ili temi
add_action('wc_barion_pixel_footer_scripts', 'my_barion_consent_handler');

function my_barion_consent_handler() {
    ?>
    <script>
    // Tvoja prilagođena logika saglasnosti ovde
    </script>
    <?php
}
```

### Primeri za specifične menadžere saglasnosti

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

## Kako saglasnost utiče na piksel

| Stanje | Osnovni piksel (bp.js) | pageView | Prikupljanje marketinških podataka |
|--------|------------------------|----------|-----------------------------------|
| Pre bilo koje radnje saglasnosti | Učitan | Aktivira se (sprečavanje prevara) | Podaci se ne prikupljaju |
| Nakon `grantConsent` | Učitan | Aktivira se | Puno prikupljanje podataka omogućeno |
| Nakon `rejectConsent` | Učitan | Aktivira se (sprečavanje prevara) | Marketinški podaci se ne prikupljaju |

Osnovni piksel se uvek učitava radi Barionove prevencije prevara. Pozivi `grantConsent` / `rejectConsent` kontrolišu da li se prikupljaju marketinški podaci.

---

## Testiranje

1. Omogući **Režim za otklanjanje grešaka** u Podešavanja > Barion Pixel
2. Otvori konzolu pregledača (F12)
3. Traži poruke u konzoli vezane za saglasnost:
   - `[Barion Pixel] bp.js loaded by Advanced Pixel for Barion` — ovaj dodatak je učitao bp.js
   - `[Barion Pixel] bp.js already loaded by another plugin, skipping script load` — drugi dodatak (npr. Barion Payment Gateway) je već učitao bp.js
   - `[Barion Pixel] Base pixel initialized with ID: <id>` — osnovni piksel radi sa vašim Pixel ID-om
   - `[Barion Pixel] Consent granted (grantConsent)` — saglasnost je odobrena (bilo koji nivo)
   - `[Barion Pixel] Consent rejected (rejectConsent)` — saglasnost je odbijena (bilo koji nivo)
   - `[Barion Pixel] Consent auto-granted via WP Consent API` — Nivo 2, saglasnost je već data prilikom učitavanja stranice
   - `[Barion Pixel] Consent granted via WP Consent API change event` — Nivo 2, korisnik je prihvatio u baneru
   - `[Barion Pixel] Consent rejected via WP Consent API change event` — Nivo 2, korisnik je odbio u baneru
   - `[Barion Pixel] Consent granted via the recorded cookie trigger` — Nivo 1, prihvaćeno
   - `[Barion Pixel] Consent rejected via the recorded cookie trigger` — Nivo 1, odbijeno
   - `[Barion Pixel] Cookie Law Info detected, initial non-necessary cookie: <value>` — Nivo 3, vrednost kolačića pročitana prilikom učitavanja stranice
   - `[Barion Pixel] Cookie Law Info button clicked, non-necessary cookie: <value>` — Nivo 3, vrednost kolačića pročitana nakon klika u baneru
   - `[Barion Pixel] No consent manager detected. Call window.wcBarionGrantConsent() or window.wcBarionRejectConsent() manually.` — Nivo 4 (ručni režim)

   Namerno ne postoji poruka kada saglasnost nedostaje prilikom prvog učitavanja preko WP
   Consent API-ja — dodatak beleži samo kada deluje, a ne kada ćuti.
4. Testiraj oba toka — prihvatanje i odbijanje — na svom baneru za kolačiće
5. Funkcije saglasnosti je bezbedno pozivati više puta (idempotentne)
