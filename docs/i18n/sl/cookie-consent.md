> 🌐 To je samodejni prevod. Popravki skupnosti so dobrodošli!
>
> [English version](../../cookie-consent.md)

# Integracija soglasja s piškotki

## Pregled

Barion Pixel zahteva izrecno soglasje uporabnika pred zbiranjem tržnih podatkov (skladnost z GDPR). Vtičnik mora poklicati `bp('consent', 'grantConsent')`, ko uporabnik sprejme, in `bp('consent', 'rejectConsent')`, ko zavrne. Oba dogodka sta obvezna po zahtevah Barion.

Skript osnovnega piksla se vedno naloži za preprečevanje goljufij, toda nobeni tržni podatki se ne zbirajo, dokler soglasje ni izrecno odobreno ali zavrnjeno.

**Pomembno:** Vaš pasici za piškotke mora ponuditi tako možnost sprejemanja kot zavrnitve. "Zid piškotkov" (samo sprejemanje) ni v skladu z GDPR od leta 2020 in ga bo Barion zavrnil.

Vtičnik podpira štiri ravni integracije soglasja, preverjene v naslednjem vrstnem redu:

1. **Zabeležen sprožilec** — signal piškotka, ki ga zazna čarovnik za nastavitev; zmaga samo
   takrat, ko sta zabeležena oba signala, sprejem in zavrnitev, ker ju je lastnik trgovine
   nastavil namerno. Napol naučen sprožilec — zabeležen le en signal — je popolnoma prezrt, vtičnik
   pa preide na naslednjo raven, ker Barion zahteva tako `grantConsent` kot `rejectConsent`.
2. **WP Consent API** (priporočeno) — univerzalno, deluje z vsemi večjimi vtičniki za piškotke
3. **Cookie Law Info** (nadomestno) — neposredna integracija za spletna mesta, ki uporabljajo CookieYes/Cookie Law Info
4. **Ročno** — za prilagojene upravljavce soglasja ali robne primere

---

## Plošča stanja

Nastavitve › Barion Pixel se odpre s ploščo stanja. Ta izvede vse spodaj navedene preverbe in
najprej prikaže najslabši rezultat. Ko vse uspe, se strne v eno samo zeleno vrstico.

Najpomembnejša preverba je **"No cookie banner plugin sets a consent type"** (noben vtičnik za
pasico piškotkov ne nastavi vrste soglasja). WP Consent API javi soglasje za vsako kategorijo,
kadar nič ne nastavi vrste soglasja:

> If there's no consent management plugin to set it, it will return `false`. This will cause all
> consent categories to return `true`.

Spletno mesto z aktivnim WP Consent API, a brez pasice za piškotke, zato odobri soglasje Barion za
vsakega obiskovalca, ne da bi bilo dejansko zbrano kakršno koli soglasje. To krši GDPR in pogoje
Bariona.

Nekatere pasice nastavijo vrsto soglasja samo v brskalniku, zato plošča najprej javi opozorilo in
ponudi gumb **Check in browser**. Ta preverba prebere dejanske vrednosti z vašega frontenda, še
preden pride do kakršne koli interakcije, in glede na to obarva vrstico rdeče ali zeleno.

### Barionovi piškotki

`bp.js` na vaši lastni domeni nastavi tri lastne (first-party) piškotke. Vsakemu imenu se ob
izvajanju doda hash vaše domene.

| Piškotek | Trajanje | Namen |
|----------|----------|-------|
| `ba_sid` | 30 minut | Združuje oglede strani v eno sejo. Barion ga uporablja za preprečevanje goljufij. |
| `ba_vid` | 1,5 leta | Identificira vračajočega se obiskovalca za tržno analitiko. |
| `BarionMarketingConsent` | 1,5 leta, odstranjen ob zavrnitvi obiskovalca | Beleži izbiro soglasja. |

Ob aktivnem vtičniku WP Consent API vtičnik samodejno prijavi vse tri, zato se pojavijo v vaši
politiki piškotkov. Brez njega jih je treba dodati ročno.

## Čarovnik za nastavitev

Če noben vir soglasja ne deluje, plošča ponudi **Set up consent**. Čarovnik odpre vašo trgovino v
novem zavihku, vi sprejmete v svoji lastni pasici, vtičnik pa zabeleži, kateri piškotek se je
spremenil. Enako ponovite za zavrnitev. Barion zahteva tako `grantConsent` kot `rejectConsent`,
zato čarovnik zavrne shranjevanje, dokler nima obeh.

Čarovnik shrani ime piškotka, sprejeto in zavrnjeno vrednost ter do pet imen dogodkov. Nikoli ne
shrani niti ne izvede JavaScripta, ki bi ga sami dostavili. Snemalnik se naloži samo za
prijavljenega administratorja, ki prispe z veljavnim nonce; obiskovalcu se nikoli ne naloži.

### Zakaj je kategorija soglasja fiksna

Vtičnik vedno zahteva kategorijo `marketing` in ne ponuja izbire. WP Consent API definira pet
fiksnih kategorij, vtičniki za pasice piškotkov pa svoje lastne kategorije v kodi preslikajo nanje.
CookieYes preslika Advertisement na marketing, Analytics na statistics, Functional na preferences
in Performance na functional. Te preslikave ni mogoče spremeniti.

Barion zahteva soglasje za tržne namene, zato je `marketing` edina pravilna kategorija. Izbirnik bi
omogočil sprožitev Bariona na potrditvenem polju za statistiko, kar krši pogoje Bariona.

---

## Raven 2: WP Consent API (priporočeno)

[WP Consent API](https://wordpress.org/plugins/wp-consent-api/) je WordPress standard za komunikacijo soglasja. Podpirajo ga vsi večji vtičniki za soglasje s piškotki.

### Kako deluje

Vtičnik preveri prisotnost funkcije `wp_has_consent()` JavaScript ob zagonu. Če je WP Consent API na voljo:

1. Ob nalaganju strani preveri, ali je soglasje za `marketing` odobreno ali zavrnjeno
2. Pokliče `bp('consent', 'grantConsent')`, če je tržno soglasje odobreno
3. Če tržno soglasje ni odobreno, ne stori nič — `rejectConsent` se ob nalaganju strani ne pošlje; pošlje se šele, ko obiskovalec odgovori v pasici (glej točko 4)
4. Posluša za dogodek `wp_listen_for_consent_change` za posodobitve soglasja v realnem času — ustrezno odobri ali zavrne

### Podprti vtičniki za piškotke

Vsak vtičnik, ki implementira WP Consent API, bo deloval samodejno:

| Vtičnik | Aktivne namestitve | Opombe |
|---------|-------------------|--------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5M+ | WP Consent API vgrajen |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1M+ | Soustvarjalec WP Consent API |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1M+ | Združljivo z WP Consent API |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300K+ | Združljivo z WP Consent API |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100K+ | Združljivo z WP Consent API |

### Nastavitev

1. Namesti in aktiviraj vtičnik [WP Consent API](https://wordpress.org/plugins/wp-consent-api/)
2. Namesti in konfiguriraj želeni vtičnik za soglasje s piškotki (glejte tabelo zgoraj)
3. Namesti in konfiguriraj Advanced Pixel for Barion
4. Nobena dodatna konfiguracija ni potrebna — soglasje se obravnava samodejno

### Kategorija soglasja

Barion Pixel je registriran v kategoriji soglasja `marketing` v WP Consent API. To je standardna kategorija za sledilne piksle, ki se uporabljajo za retargeting in analitiko.

---

## Raven 3: Cookie Law Info (nadomestno)

Če WP Consent API ni na voljo, vtičnik preklopi na neposredno integracijo z vtičnikom [Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) / CookieYes.

### Kako deluje

1. Preveri prisotnost globalnega objekta `CLI` JavaScript
2. Prebere piškotek `cookielawinfo-checkbox-non-necessary`; če je njegova vrednost natanko `yes`, takoj odobri soglasje
3. Sicer ne stori ničesar, dokler obiskovalec ne interagira s pasico
4. Posluša klike na kateri koli element, ki ustreza `.cli_action_button`
5. 100 milisekund po kliku znova prebere isti piškotek in glede na to odobri ali zavrne soglasje

### Nastavitev

Nobena konfiguracija ni potrebna. Namestite oba vtičnika in integracija deluje samodejno.

---

## Raven 4: Ročna integracija

Za prilagojene upravljavce soglasja ali okolja, kjer nista na voljo niti WP Consent API niti Cookie Law Info.

### Metoda 1: Funkcije JavaScript (priporočeno)

```javascript
// Ko uporabnik sprejme tržne piškotke
function onMarketingConsentGranted() {
    if (typeof window.wcBarionGrantConsent === 'function') {
        window.wcBarionGrantConsent();
    }
}

// Ko uporabnik zavrne tržne piškotke
function onMarketingConsentRejected() {
    if (typeof window.wcBarionRejectConsent === 'function') {
        window.wcBarionRejectConsent();
    }
}
```

### Metoda 2: Prilagojeni dogodki DOM

```javascript
// Odobri soglasje
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Zavrni soglasje
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### Metoda 3: WordPress akcijski kavelj

```php
// V vašem vtičniku za upravljanje soglasja ali temi
add_action('wc_barion_pixel_footer_scripts', 'my_barion_consent_handler');

function my_barion_consent_handler() {
    ?>
    <script>
    // Vaša prilagojena logika soglasja tukaj
    </script>
    <?php
}
```

### Primeri za specifične upravljavce soglasja

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

## Kako soglasje vpliva na piksel

| Stanje | Osnovni piksel (bp.js) | pageView | Zbiranje tržnih podatkov |
|--------|----------------------|----------|--------------------------|
| Pred kakršno koli akcijo soglasja | Naložen | Sproži se (preprečevanje goljufij) | Nobeni podatki se ne zbirajo |
| Po `grantConsent` | Naložen | Sproži se | Zbiranje vseh podatkov omogočeno |
| Po `rejectConsent` | Naložen | Sproži se (preprečevanje goljufij) | Nobeni tržni podatki se ne zbirajo |

Osnovni piksel se vedno naloži za Barionovo preprečevanje goljufij. Klici `grantConsent` / `rejectConsent` nadzirajo, ali se zbirajo tržni podatki.

---

## Testiranje

1. Omogočite **Način za odpravljanje napak** v Nastavitve > Barion Pixel
2. Odprite konzolo brskalnika (F12)
3. Poiščite sporočila dnevnika, povezana s soglasjem:
   - `[Barion Pixel] bp.js loaded by Advanced Pixel for Barion` — ta vtičnik je naložil bp.js
   - `[Barion Pixel] bp.js already loaded by another plugin, skipping script load` — drug vtičnik (npr. Barion Payment Gateway) je že naložil bp.js
   - `[Barion Pixel] Base pixel initialized with ID: <id>` — osnovni piksel deluje z vašim Pixel ID
   - `[Barion Pixel] Consent granted (grantConsent)` — soglasje odobreno (katera koli raven)
   - `[Barion Pixel] Consent rejected (rejectConsent)` — soglasje zavrnjeno (katera koli raven)
   - `[Barion Pixel] Consent auto-granted via WP Consent API` — Raven 2, soglasje je bilo dano že ob nalaganju strani
   - `[Barion Pixel] Consent granted via WP Consent API change event` — Raven 2, uporabnik je sprejel v pasici
   - `[Barion Pixel] Consent rejected via WP Consent API change event` — Raven 2, uporabnik je zavrnil v pasici
   - `[Barion Pixel] Consent granted via the recorded cookie trigger` — Raven 1, sprejeto
   - `[Barion Pixel] Consent rejected via the recorded cookie trigger` — Raven 1, zavrnjeno
   - `[Barion Pixel] Cookie Law Info detected, initial non-necessary cookie: <value>` — Raven 3, vrednost piškotka, prebrana ob nalaganju strani
   - `[Barion Pixel] Cookie Law Info button clicked, non-necessary cookie: <value>` — Raven 3, vrednost piškotka, prebrana po kliku v pasici
   - `[Barion Pixel] No consent manager detected. Call window.wcBarionGrantConsent() or window.wcBarionRejectConsent() manually.` — Raven 4 (ročni način)

   Namerno ni sporočila, kadar soglasja ob prvem nalaganju prek WP Consent API ni — vtičnik
   beleži samo, kadar ukrepa, ne kadar molči.
4. Preizkusite tako tok sprejemanja kot zavrnitve na vaši pasici za piškotke
5. Funkcije soglasja je varno klicati večkrat (idempotentne)
