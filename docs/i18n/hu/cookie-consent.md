> 🌐 Ez egy automatikus fordítás. Közösségi javítások szívesen fogadottak!
>
> [English version](../../cookie-consent.md)

# Cookie-hozzájárulás integráció

## Áttekintés

A Barion Pixel marketing adatok gyűjtése előtt explicit felhasználói hozzájárulást igényel (GDPR-megfelelőség). A bővítménynek meg kell hívnia a `bp('consent', 'grantConsent')` függvényt, amikor a felhasználó elfogadja, és a `bp('consent', 'rejectConsent')` függvényt, amikor elutasítja. Mindkét esemény kötelező a Barion követelményei szerint.

Az alap pixel szkript mindig betöltődik a csalás megelőzése érdekében, de marketing adatok csak akkor kerülnek gyűjtésre, ha a hozzájárulás explicit módon meg lett adva vagy elutasítva.

**Fontos:** A cookie bannernek mind elfogadási, mind elutasítási lehetőséget kell kínálnia. A "cookie fal" (csak elfogadás) 2020 óta nem felel meg a GDPR-nak, és a Barion visszautasítja.

A bővítmény a hozzájárulás-integráció négy szintjét támogatja, sorrendben ellenőrizve:

1. **Rögzített trigger** — a beállítási varázsló által rögzített cookie-jel; csak akkor nyer, ha
   mind az elfogadási, mind az elutasítási jel rögzítve van, mert a bolttulajdonos szándékosan
   állította be. A félig betanított trigger — amelynél csak az egyik jel van rögzítve — teljesen
   figyelmen kívül marad, és a bővítmény a következő szintre lép, mert a Barion mind a
   `grantConsent`, mind a `rejectConsent` hívást megköveteli.
2. **WP Consent API** (ajánlott) — univerzális, minden nagyobb cookie bővítménnyel működik
3. **Cookie Law Info** (tartalék) — közvetlen integráció CookieYes/Cookie Law Info-t használó oldalakhoz
4. **Manuális** — egyéni hozzájárulás kezelőkhöz vagy speciális esetekhez

---

## Az állapotpanel

A Beállítások › Barion Pixel az állapotpanellel nyílik meg. Ez lefuttatja az alábbi összes
ellenőrzést, és először a legrosszabb eredményt mutatja. Ha minden rendben van, egyetlen zöld
sorra záródik össze.

A legfontosabb ellenőrzés a **„No cookie banner plugin sets a consent type"** (egyetlen
cookie-banner bővítmény sem állít be hozzájárulás-típust). A WP Consent API minden kategóriára
hozzájárulást jelent, ha semmi nem állít be hozzájárulás-típust:

> If there's no consent management plugin to set it, it will return `false`. This will cause all
> consent categories to return `true`.

Egy olyan oldal, amelyen aktív a WP Consent API, de nincs cookie-banner, ezért minden látogatóra
megadja a Barion hozzájárulást, anélkül hogy bármilyen tényleges hozzájárulás történt volna. Ez
sérti a GDPR-t és a Barion feltételeit.

Néhány banner csak a böngészőben állítja be a hozzájárulás típusát, ezért a panel először
figyelmeztetést jelez, és felkínálja a **Check in browser** gombot. Ez az ellenőrzés bármilyen
interakció előtt beolvassa a valós értékeket a frontendről, és ennek megfelelően pirosra vagy
zöldre színezi a sort.

### A Barion cookie-jai

A `bp.js` három első féltől származó cookie-t állít be a saját domainjén. Minden névhez futásidőben
hozzáfűzi a domain hash-ét.

| Cookie | Élettartam | Cél |
|--------|------------|-----|
| `ba_sid` | 30 perc | Egy munkamenetbe csoportosítja az oldalmegtekintéseket. A Barion csalásmegelőzésre használja. |
| `ba_vid` | 1,5 év | Azonosítja a visszatérő látogatót a marketing-analitika céljából. |
| `BarionMarketingConsent` | 1,5 év, törlődik, ha a látogató elutasítja | Rögzíti a hozzájárulási döntést. |

Ha a WP Consent API bővítmény aktív, a bővítmény automatikusan bejelenti mindhármat, így azok
megjelennek a cookie-szabályzatodban. Enélkül kézzel kell hozzáadnod őket.

## A beállítási varázsló

Ha egyetlen hozzájárulási forrás sem működik, a panel felkínálja a **Set up consent** lehetőséget.
A varázsló új lapon megnyitja a boltodat, ott elfogadod a saját bannereden, a bővítmény pedig
rögzíti, melyik cookie változott meg. Ugyanezt megismétled elutasításra is. A Barion mind a
`grantConsent`, mind a `rejectConsent` hívást megköveteli, így a varázsló addig nem menti el a
beállítást, amíg mindkettő nincs meg.

A varázsló egy cookie-nevet, az elfogadott és elutasított értéket, valamint legfeljebb öt
eseménynevet tárol. Soha nem tárol vagy futtat olyan JavaScriptet, amelyet te adtál meg. A
rögzítő csak egy bejelentkezett, érvényes nonce-szal érkező adminisztrátor számára töltődik be;
látogató számára soha.

### Miért rögzített a hozzájárulási kategória

A bővítmény mindig a `marketing` kategóriát kéri, és nem kínál választást. A WP Consent API öt
rögzített kategóriát határoz meg, a cookie-banner bővítmények pedig kódban saját kategóriáikat
ezekre képezik le. A CookieYes az Advertisement-et a marketingre, az Analytics-et a statistics-re,
a Functional-t a preferences-re, a Performance-t pedig a functional-ra képezi le. Ezt a
leképezést nem tudod megváltoztatni.

A Barion hozzájárulást kér marketingcélokra, ezért a `marketing` az egyetlen helyes kategória. Egy
választó lehetővé tenné, hogy a Barion egy statisztikai jelölőnégyzeten süljön el, ami sérti a
Barion feltételeit.

---

## 2. szint: WP Consent API (ajánlott)

A [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) egy WordPress szabvány a hozzájárulás kommunikációjához. Minden nagyobb cookie hozzájárulás bővítmény támogatja.

### Működési elv

A bővítmény futásidőben ellenőrzi a `wp_has_consent()` JavaScript függvény jelenlétét. Ha a WP Consent API elérhető:

1. Az oldal betöltésekor ellenőrzi, hogy a `marketing` hozzájárulás meg van-e adva vagy elutasítva
2. Meghívja a `bp('consent', 'grantConsent')` függvényt, ha a marketing hozzájárulás meg van adva
3. Nem tesz semmit, ha a marketing hozzájárulás nincs megadva — az oldal betöltésekor nem küld `rejectConsent` hívást; azt csak akkor küldi el, miután a látogató válaszolt a sávban (lásd a 4. pontot)
4. Figyeli a `wp_listen_for_consent_change` eseményt a valós idejű hozzájárulás frissítésekhez — ennek megfelelően ad vagy utasít el hozzájárulást

### Támogatott cookie bővítmények

Bármely bővítmény, amely megvalósítja a WP Consent API-t, automatikusan működni fog:

| Bővítmény | Aktív telepítések | Megjegyzések |
|-----------|-------------------|--------------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5M+ | WP Consent API beépített |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1M+ | A WP Consent API társalkotója |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1M+ | WP Consent API kompatibilis |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300K+ | WP Consent API kompatibilis |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100K+ | WP Consent API kompatibilis |

### Beállítás

1. Telepítsd és aktiváld a [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) bővítményt
2. Telepítsd és konfiguráld a preferált cookie hozzájárulás bővítményedet (lásd a fenti táblázatot)
3. Telepítsd és konfiguráld a Advanced Pixel for Barion bővítményt
4. Nincs szükség további konfigurációra — a hozzájárulás kezelése automatikus

### Hozzájárulási kategória

A Barion Pixel a `marketing` hozzájárulási kategória alatt van regisztrálva a WP Consent API-ban. Ez a standard kategória az újracélzáshoz és analitikához használt követési pixelekhez.

---

## 3. szint: Cookie Law Info (tartalék)

Ha a WP Consent API nem érhető el, a bővítmény közvetlen integrációra vált a [Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) / CookieYes bővítménnyel.

### Működési elv

1. Ellenőrzi a `CLI` JavaScript globális objektum jelenlétét
2. Beolvassa a `cookielawinfo-checkbox-non-necessary` cookie-t; ha az értéke pontosan `yes`, azonnal megadja a hozzájárulást
3. Egyébként semmit nem tesz, amíg a látogató nem lép interakcióba a bannerrel
4. Figyeli a kattintásokat minden olyan elemen, amely illeszkedik a `.cli_action_button` szelektorra
5. Kattintás után 100 ezredmásodperccel újra beolvassa ugyanazt a cookie-t, és ennek megfelelően adja meg vagy utasítja el a hozzájárulást

### Beállítás

Nincs szükség konfigurációra. Telepítsd mindkét bővítményt, és az integráció automatikusan működik.

---

## 4. szint: Manuális integráció

Egyéni hozzájárulás kezelőkhöz vagy olyan környezetekhez, ahol sem a WP Consent API, sem a Cookie Law Info nem érhető el.

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

### 2. módszer: Egyéni DOM események

```javascript
// Hozzájárulás megadása
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Hozzájárulás elutasítása
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### 3. módszer: WordPress action hook

```php
// Az egyéni hozzájárulás kezelő bővítményben vagy témában
add_action('wc_barion_pixel_footer_scripts', 'my_barion_consent_handler');

function my_barion_consent_handler() {
    ?>
    <script>
    // Az egyéni hozzájárulás logikád ide kerül
    </script>
    <?php
}
```

### Példák konkrét hozzájárulás kezelőkhöz

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

## Hogyan befolyásolja a hozzájárulás a pixelt

| Állapot | Alap pixel (bp.js) | pageView | Marketing adatgyűjtés |
|---------|--------------------|-----------|-----------------------|
| Bármilyen hozzájárulási művelet előtt | Betöltve | Aktiválódik (csalás megelőzés) | Nem gyűjt adatot |
| `grantConsent` után | Betöltve | Aktiválódik | Teljes adatgyűjtés engedélyezve |
| `rejectConsent` után | Betöltve | Aktiválódik (csalás megelőzés) | Nem gyűjt marketing adatot |

Az alap pixel mindig betöltődik a Barion csalás megelőzési céljaira. A `grantConsent` / `rejectConsent` hívások szabályozzák, hogy kerül-e gyűjtésre marketing adat.

---

## Tesztelés

1. Engedélyezd a **Hibakeresési módot** a Beállítások > Barion Pixel menüpontban
2. Nyisd meg a böngésző konzolt (F12)
3. Keresd a hozzájárulással kapcsolatos naplóüzeneteket:
   - `[Barion Pixel] bp.js loaded by Advanced Pixel for Barion` — ez a bővítmény töltötte be a bp.js-t
   - `[Barion Pixel] bp.js already loaded by another plugin, skipping script load` — egy másik bővítmény (pl. a Barion Payment Gateway) már betöltötte a bp.js-t
   - `[Barion Pixel] Base pixel initialized with ID: <id>` — az alap pixel fut a Pixel ID-ddel
   - `[Barion Pixel] Consent granted (grantConsent)` — hozzájárulás megadva (bármely szint)
   - `[Barion Pixel] Consent rejected (rejectConsent)` — hozzájárulás elutasítva (bármely szint)
   - `[Barion Pixel] Consent auto-granted via WP Consent API` — 2. szint, a hozzájárulás már megvolt az oldal betöltésekor
   - `[Barion Pixel] Consent granted via WP Consent API change event` — 2. szint, a felhasználó elfogadta a bannerben
   - `[Barion Pixel] Consent rejected via WP Consent API change event` — 2. szint, a felhasználó elutasította a bannerben
   - `[Barion Pixel] Consent granted via the recorded cookie trigger` — 1. szint, elfogadva
   - `[Barion Pixel] Consent rejected via the recorded cookie trigger` — 1. szint, elutasítva
   - `[Barion Pixel] Cookie Law Info detected, initial non-necessary cookie: <value>` — 3. szint, az oldal betöltésekor beolvasott cookie-érték
   - `[Barion Pixel] Cookie Law Info button clicked, non-necessary cookie: <value>` — 3. szint, a bannerbeli kattintás után beolvasott cookie-érték
   - `[Barion Pixel] No consent manager detected. Call window.wcBarionGrantConsent() or window.wcBarionRejectConsent() manually.` — 4. szint (manuális mód)

   Szándékosan nincs üzenet, ha az első betöltéskor a WP Consent API-n keresztül nincs
   hozzájárulás — a bővítmény csak akkor naplóz, amikor tesz valamit, nem akkor, amikor
   hallgat.
4. Teszteld az elfogadási és elutasítási folyamatot is a cookie banneren
5. A hozzájárulás függvények biztonságosan hívhatók többször is (idempotens)
