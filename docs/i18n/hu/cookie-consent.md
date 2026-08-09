> 🌐 Ez egy automatikus fordítás. Közösségi javítások szívesen fogadottak!
>
> [English version](../../cookie-consent.md)

# Cookie-hozzájárulás integráció

Itt a Barion saját oldala az irányadó:
[Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements).
Ugyanitt található a Barion által ajánlott cookie-sáv szövege és a Barion hirdetési
partnereinek aktuális listája. Éles indulás előtt olvasd el — a megfelelés a kereskedő
felelőssége, nem a bővítményé.

## Mit csinál a bővítmény

Az alap pixel szkript mindig betöltődik, és a `pageView` mindig elindul. A Barion ezt jogos
érdekként dokumentálja: az alap pixel a fizetési csalások megelőzését szolgálja, és a marketing
hozzájárulás nélkül gyűjtött adatot csak erre használják.

Ezen felül a bővítmény meghívja a `bp('consent', 'grantConsent')` függvényt, ha a vásárló
elfogadja a marketing cookie-kat, és a `bp('consent', 'rejectConsent')` függvényt, ha
elutasítja. A Barion mindkettőt kötelezőnek sorolja fel. A sávodnak ezért valódi elutasítási
lehetőséget kell kínálnia — csak elfogadást engedő sávnál a bővítménynek nincs mit jeleznie.

A bővítmény ebben a sorrendben keres hozzájárulás-kezelőt, és az elsőnél megáll:

1. **WP Consent API** (ajánlott) — univerzális, minden nagyobb cookie-bővítménnyel működik
2. **Cookie Law Info** (tartalék) — közvetlen integráció a CookieYes / Cookie Law Info bővítményhez
3. **Kézi** — egyedi hozzájárulás-kezelőkhöz

---

## 1. szint: WP Consent API (ajánlott)

A [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) a WordPress szabványa a
hozzájárulás bővítmények közötti továbbítására. A Barion Pixel a `marketing` kategóriában
regisztrál.

### Hogyan működik

A `DOMContentLoaded` esemény után a bővítmény megnézi, létezik-e a `wp_has_consent()` függvény.
Ha igen:

1. Ha a `marketing` hozzájárulás már megvan, a `grantConsent` azonnal elindul.
2. Ezután a bővítmény a `wp_listen_for_consent_change` eseményre figyel, és minden változásnál elküldi a `grantConsent` vagy a `rejectConsent` eseményt.

Vedd észre, mi *nincs* a listában: olyan oldalbetöltésnél, ahol nincs marketing hozzájárulás, a
bővítmény hallgat, nem küld `rejectConsent` eseményt. Amíg a vásárló nem válaszolt a sávra,
nincs mit jelenteni, a válasz pedig a változás eseményen keresztül érkezik meg.

### Támogatott cookie-bővítmények

Minden bővítmény működik, amely megvalósítja a WP Consent API-t:

| Bővítmény | Aktív telepítés | Megjegyzés |
|-----------|-----------------|------------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5M+ | Beépített WP Consent API |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1M+ | A WP Consent API társalkotója |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1M+ | WP Consent API kompatibilis |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300K+ | WP Consent API kompatibilis |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100K+ | WP Consent API kompatibilis |

### Beállítás

1. Telepítsd és aktiváld a [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) bővítményt.
2. Telepítsd és állítsd be a cookie-hozzájárulás bővítményedet.
3. Telepítsd és állítsd be az Advanced Pixel for Barion bővítményt.

Más teendő nincs — a hozzájárulás kezelése automatikus.

---

## 2. szint: Cookie Law Info (tartalék)

Akkor lép működésbe, ha a WP Consent API nem érhető el, de a
[Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) / CookieYes igen.

### Hogyan működik

1. A bővítmény megnézi a `CLI` globális objektumot és annak `allowedCategories` mezőjét.
2. Ha a `cookielawinfo-checkbox-non-necessary` cookie értéke már `yes` — visszatérő vásárló, aki korábban elfogadta —, a `grantConsent` azonnal elindul.
3. Figyeli a sáv `.cli_action_button` elemeire adott kattintásokat. A kattintás után röviddel újra beolvassa a cookie-t, és ennek megfelelően küldi a `grantConsent` vagy a `rejectConsent` eseményt.

### Beállítás

Nincs teendő. Telepítsd mindkét bővítményt, és működik.

---

## 3. szint: Kézi integráció

Egyedi hozzájárulás-kezelőkhöz, vagy ha a fentiek egyike sem alkalmazható.

### 1. módszer: JavaScript függvények (ajánlott)

```javascript
// Amikor a felhasználó elfogadja a marketing cookie-kat
function onMarketingConsentGranted() {
    if (typeof window.wcBarionGrantConsent === 'function') {
        window.wcBarionGrantConsent();
    }
}

// Amikor a felhasználó elutasítja a marketing cookie-kat
function onMarketingConsentRejected() {
    if (typeof window.wcBarionRejectConsent === 'function') {
        window.wcBarionRejectConsent();
    }
}
```

### 2. módszer: Egyedi DOM események

```javascript
// Hozzájárulás megadása
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Hozzájárulás elutasítása
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### 3. módszer: WordPress action hook

```php
// A hozzájárulás-kezelő bővítményedben vagy a sablonodban
add_action('wc_barion_pixel_footer_scripts', 'my_barion_consent_handler');

function my_barion_consent_handler() {
    ?>
    <script>
    // Ide jön a saját hozzájárulási logikád
    </script>
    <?php
}
```

### Példák konkrét hozzájárulás-kezelőkhöz

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

## Amit neked kell elintézned

A bővítmény továbbítja a hozzájárulást. A szabályzataidat nem írja meg, és a sávodat nem
állítja be, a Barion viszont mindkettőt megköveteli. A
[Barion követelményeiből](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements):

- **Vedd fel a Barion cookie-kat a cookie-szabályzatodba.** A `ba_vid`, `ba_vid.xxx`, `ba_sid` és `ba_sid.xxx` a szükséges cookie-k közé tartozik — a csalásmegelőzést szolgálják a Barion jogos érdeke alapján, és nem igényelnek hozzájárulást. A `BarionMarketingConsent.xxx`, valamint a média- és hirdetési partnerek cookie-jai a marketing cookie-k közé tartoznak, és hozzájárulást igényelnek.
- **Említsd meg a Barion Pixelt az adatkezelési tájékoztatódban**, és hivatkozz a Barion [adatvédelmi tájékoztatójára](https://www.barion.com/hu/adatvedelmi-tajekoztato/).
- **Tedd lehetővé, hogy a vásárlók bármikor módosítsák vagy visszavonják a hozzájárulásukat**, és kérdezd meg őket újra. A Barion azt kéri, hogy a sáv legalább 13 havonta jelenjen meg újra, és 30 napot javasol.
- **Használd a Barion által ajánlott sávszöveget**, ahol lehet. A követelmények oldalán található, és lefedi a partneri adatmegosztást is, amivel a Barion Pixel jár.

---

## Hogyan hat a hozzájárulás a pixelre

| Állapot | Alap pixel (bp.js) | pageView | Marketing adatgyűjtés |
|---------|--------------------|----------|-----------------------|
| Bármilyen hozzájárulási döntés előtt | Betöltve | Elindul (csalásmegelőzés) | Nincs |
| `grantConsent` után | Betöltve | Elindul | Van |
| `rejectConsent` után | Betöltve | Elindul (csalásmegelőzés) | Nincs |

---

## Tesztelés

1. Kapcsold be a **Hibakeresési módot** a Beállítások > Barion Pixel oldalon.
2. Nyisd meg a böngészőkonzolt (F12).
3. Keresd ezeket az üzeneteket:

| Üzenet | Jelentés |
|--------|----------|
| `Consent auto-granted via WP Consent API` | 1. szint, a hozzájárulás betöltéskor már megvolt |
| `Consent granted via WP Consent API change event` | 1. szint, a vásárló most fogadta el |
| `Consent rejected via WP Consent API change event` | 1. szint, a vásárló most utasította el |
| `Cookie Law Info detected, initial non-necessary cookie: …` | A 2. szint vette át, a beolvasott cookie-értékkel |
| `Cookie Law Info button clicked, non-necessary cookie: …` | 2. szint, a vásárló használta a sávot |
| `No consent manager detected…` | 3. szint — a bővítmény nem talált semmit, hívd te a függvényeket |
| `Consent granted (grantConsent)` | A `grantConsent` eljutott a bp.js-hez (bármelyik szinten) |
| `Consent rejected (rejectConsent)` | A `rejectConsent` eljutott a bp.js-hez (bármelyik szinten) |

Minden üzenet `[Barion Pixel]` előtaggal jelenik meg.

4. Teszteld a sávodon az elfogadási és az elutasítási utat is.
5. A hozzájárulási függvények többször is nyugodtan hívhatók.
