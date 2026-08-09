> 🌐 To je samodejni prevod. Popravki skupnosti so dobrodošli!
>
> [English version](../../cookie-consent.md)

# Integracija soglasja s piškotki

Tu je merodajna Barionova lastna stran:
[Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements).
Na njej sta tudi besedilo pasice za soglasje, ki ga Barion priporoča, in aktualen seznam Barionovih
oglaševalskih partnerjev. Preberi jo pred zagonom v živo — za skladnost odgovarja trgovec, ne
vtičnik.

## Kaj vtičnik naredi

Skripta osnovnega piksla se vedno naloži in `pageView` se vedno pošlje. Barion to dokumentira kot
zakoniti interes: osnovni piksel je namenjen preprečevanju plačilnih goljufij, podatki, zbrani brez
trženjskega soglasja, pa se uporabljajo samo za to.

Poleg tega vtičnik pokliče `bp('consent', 'grantConsent')`, ko kupec sprejme trženjske piškotke, in
`bp('consent', 'rejectConsent')`, ko jih zavrne. Barion oba navaja kot obvezna. Tvoja pasica mora
zato ponujati resnično možnost zavrnitve — pri pasici, ki pozna samo sprejem, vtičnik nima česa
sporočiti.

Vtičnik išče upravitelja soglasja v tem vrstnem redu in se ustavi pri prvem najdenem:

1. **WP Consent API** (priporočeno) — univerzalno, deluje z vsemi večjimi vtičniki za piškotke
2. **Cookie Law Info** (rezerva) — neposredna integracija za CookieYes / Cookie Law Info
3. **Ročno** — za lastne upravitelje soglasja

---

## Raven 1: WP Consent API (priporočeno)

[WP Consent API](https://wordpress.org/plugins/wp-consent-api/) je standard WordPressa za
posredovanje soglasja med vtičniki. Barion Pixel se registrira v kategoriji `marketing`.

### Kako deluje

Po dogodku `DOMContentLoaded` vtičnik preveri, ali obstaja funkcija `wp_has_consent()`. Če obstaja:

1. Če je soglasje `marketing` že dano, se `grantConsent` pošlje takoj.
2. Od tedaj vtičnik posluša `wp_listen_for_consent_change` in ob vsaki spremembi pošlje `grantConsent` ali `rejectConsent`.

Bodi pozoren, česa na seznamu *ni*: ob nalaganju strani, kjer trženjskega soglasja ni, vtičnik
molči, namesto da bi poslal `rejectConsent`. Dokler kupec na pasico ni odgovoril, ni česa
sporočiti — odgovor pa pride prek dogodka spremembe.

### Podprti vtičniki za piškotke

Samodejno deluje vsak vtičnik, ki implementira WP Consent API:

| Vtičnik | Aktivnih namestitev | Opomba |
|---------|---------------------|--------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5M+ | WP Consent API vgrajen |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1M+ | Soavtor WP Consent API |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1M+ | Združljiv z WP Consent API |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300K+ | Združljiv z WP Consent API |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100K+ | Združljiv z WP Consent API |

### Nastavitev

1. Namesti in aktiviraj [WP Consent API](https://wordpress.org/plugins/wp-consent-api/).
2. Namesti in nastavi svoj vtičnik za soglasje s piškotki.
3. Namesti in nastavi Advanced Pixel for Barion.

Nič drugega — soglasje se obravnava samodejno.

---

## Raven 2: Cookie Law Info (rezerva)

Uporabi se, ko WP Consent API ni na voljo,
[Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) / CookieYes pa je.

### Kako deluje

1. Vtičnik preveri globalni objekt `CLI` in njegov `allowedCategories`.
2. Če ima piškotek `cookielawinfo-checkbox-non-necessary` že vrednost `yes` — vračajoči se obiskovalec, ki je sprejel — se `grantConsent` pošlje takoj.
3. Spremljajo se kliki na elemente `.cli_action_button` v pasici. Kmalu po kliku vtičnik znova prebere piškotek in glede na to pošlje `grantConsent` ali `rejectConsent`.

### Nastavitev

Nobene. Namesti oba vtičnika in deluje.

---

## Raven 3: Ročna integracija

Za lastne upravitelje soglasja ali tam, kjer nič od zgornjega ne velja.

### Način 1: Funkcije JavaScript (priporočeno)

```javascript
// Ko uporabnik sprejme trženjske piškotke
function onMarketingConsentGranted() {
    if (typeof window.wcBarionGrantConsent === 'function') {
        window.wcBarionGrantConsent();
    }
}

// Ko uporabnik zavrne trženjske piškotke
function onMarketingConsentRejected() {
    if (typeof window.wcBarionRejectConsent === 'function') {
        window.wcBarionRejectConsent();
    }
}
```

### Način 2: Lastni dogodki DOM

```javascript
// Podelitev soglasja
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Zavrnitev soglasja
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### Način 3: WordPressov action hook

```php
// V tvojem vtičniku za upravljanje soglasja ali v temi
add_action('wc_barion_pixel_footer_scripts', 'my_barion_consent_handler');

function my_barion_consent_handler() {
    ?>
    <script>
    // Sem gre tvoja lastna logika soglasja
    </script>
    <?php
}
```

### Primeri za posamezne upravitelje soglasja

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

## Kaj moraš urediti sam

Vtičnik soglasje posreduje naprej. Tvojih pravilnikov ne bo napisal in pasice ne bo nastavil,
Barion pa zahteva oboje. Iz
[Barionovih zahtev](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements):

- **Dodaj Barionove piškotke v svoj pravilnik o piškotkih.** `ba_vid`, `ba_vid.xxx`, `ba_sid` in `ba_sid.xxx` sodijo med nujne piškotke — služijo preprečevanju goljufij na podlagi Barionovega zakonitega interesa in ne zahtevajo soglasja. `BarionMarketingConsent.xxx` ter piškotki medijskih in oglaševalskih partnerjev sodijo med trženjske piškotke in soglasje zahtevajo.
- **Omeni Barion Pixel v svojem pravilniku o zasebnosti** in poveži Barionovo [obvestilo o zasebnosti](https://www.barion.com/en/privacy-notice/).
- **Kupcem omogoči, da soglasje kadar koli spremenijo ali prekličejo**, in jih znova vprašaj. Barion zahteva, da se pasica znova pojavi vsaj vsakih 13 mesecev, priporoča pa 30 dni.
- **Uporabi besedilo pasice, ki ga priporoča Barion**, kjer se le da. Na strani z zahtevami je in pokriva tudi deljenje podatkov s partnerji, ki ga Barion Pixel prinaša.

---

## Kako soglasje vpliva na piksel

| Stanje | Osnovni piksel (bp.js) | pageView | Zbiranje trženjskih podatkov |
|--------|------------------------|----------|------------------------------|
| Pred kakršno koli odločitvijo o soglasju | Naložen | Se pošlje (preprečevanje goljufij) | Ne |
| Po `grantConsent` | Naložen | Se pošlje | Da |
| Po `rejectConsent` | Naložen | Se pošlje (preprečevanje goljufij) | Ne |

---

## Testiranje

1. Vklopi **način za odpravljanje napak** v Nastavitve > Barion Pixel.
2. Odpri konzolo brskalnika (F12).
3. Spremljaj ta sporočila:

| Sporočilo | Pomen |
|-----------|-------|
| `Consent auto-granted via WP Consent API` | Raven 1, soglasje je ob nalaganju že obstajalo |
| `Consent granted via WP Consent API change event` | Raven 1, kupec je pravkar sprejel |
| `Consent rejected via WP Consent API change event` | Raven 1, kupec je pravkar zavrnil |
| `Cookie Law Info detected, initial non-necessary cookie: …` | Prevzela je raven 2, s prebrano vrednostjo piškotka |
| `Cookie Law Info button clicked, non-necessary cookie: …` | Raven 2, kupec je uporabil pasico |
| `No consent manager detected…` | Raven 3 — nič ni bilo najdeno, funkcije pokliči sam |
| `Consent granted (grantConsent)` | `grantConsent` je prišel do bp.js (katera koli raven) |
| `Consent rejected (rejectConsent)` | `rejectConsent` je prišel do bp.js (katera koli raven) |

Vsa sporočila imajo predpono `[Barion Pixel]`.

4. Na svoji pasici preizkusi pot sprejema in pot zavrnitve.
5. Funkcije soglasja je varno klicati večkrat.
