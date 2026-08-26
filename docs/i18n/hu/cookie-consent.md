> 🌐 Ez egy automatikus fordítás. Közösségi javítások szívesen fogadottak!
>
> [English version](../../cookie-consent.md)

# Cookie-hozzájárulás integráció

Itt a Barion saját oldala az irányadó:
[Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements).
Ugyanitt található a Barion által ajánlott cookie-sáv szövege és a Barion hirdetési
partnereinek aktuális listája. Éles indulás előtt olvasd el — a megfelelés a kereskedő
felelőssége, nem a bővítményé.

A Barion a `grantConsent` eseményt is felsorolja azok között, amelyeket
[kötelező megvalósítani](https://docs.barion.com/Implementing_the_Full_Barion_Pixel)
a Teljes Pixel integráció jóváhagyása előtt. Az a bolt, amelyik soha nem küldi el, nem
jogosult az alacsonyabb díjakra, akármilyen teljes is az integráció többi része.

## Mit csinál a bővítmény

Az alap pixel szkript mindig betöltődik, és a `pageView` mindig elsül. A Barion ezt
jogos érdekként dokumentálja: az alap pixel a fizetési csalás megelőzését szolgálja, és
a marketing-hozzájárulás nélkül gyűjtött adatot csak erre használják.

Ezen felül a bővítmény meghívja a `bp('consent', 'grantConsent')` függvényt, amikor a
vásárló elfogadja a marketing cookie-kat, és a `bp('consent', 'rejectConsent')`
függvényt, amikor elutasítja. A Barion mindkettőt kötelezőnek sorolja fel. A sávodnak
ezért valódi elutasítási lehetőséget kell kínálnia — csak elfogadás gombbal a
bővítménynek nincs mit jeleznie.

## Hogyan ismeri fel a hozzájárulást

A bővítmény nem választ ki egyetlen hozzájárulás-kezelőt. Egyszerre feliratkozik minden
ismert hozzájárulási jelzésre, és továbbítja az első valódi választ, majd minden ezt
követő változást. A betöltési sorrend nem számít: a figyelők azelőtt regisztrálódnak,
hogy bármelyik hozzájárulás-kezelő létezne, így a később megjelenő sáv is elérhető. A
visszatérő látogató nem lát sávot, ezért semmilyen eseményt nem vált ki — emiatt a
bővítmény félmásodpercenként keres hozzájárulás-kezelőt is, amíg egyik nem válaszol, és
az oldal betöltése után tíz másodperccel feladja.

Ezek külön bővítmény nélkül működnek:

| Hozzájárulás-kezelő | Amin keresztül olvassa |
|---|---|
| [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) | `wp_has_consent('marketing')` és `wp_listen_for_consent_change`, de csak akkor, ha egy sáv hozzájárulási típust regisztrált nála |
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | `getCkyConsent()` és `cookieyes_consent_update` |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | `cmplz_has_consent('marketing')` és `cmplz_status_change` |
| [Cookiebot](https://wordpress.org/plugins/cookiebot/) | `Cookiebot.consent.marketing` és `CookiebotOnAccept` / `CookiebotOnDecline` / `CookiebotOnConsentReady` |
| Cookie Law Info 2.x, régi sáv | a `cookielawinfo-checkbox-non-necessary` cookie, a sávra kattintás után újraolvasva |
| Bármi más | te hívod meg a függvényeket — lásd [Kézi integráció](#kézi-integráció) |

Mindegyikre három szabály vonatkozik:

- **A hozzájárulás akkor megy el, amikor a látogató válaszol a sávon, soha nem az oldal betöltésekor.** A Barion a `grantConsent` eseményt a kattintás pillanatában várja, és elutasítja azt az integrációt, amelyik azelőtt küldi el, hogy a látogató bármihez hozzáért volna — a Barion oldaláról ez úgy néz ki, mint egy bolt, amelyik soha nem kérdez. A bővítmény ezért betöltéskor beolvassa a hozzájárulás állapotát, de megtartja magának, és csak azt küldi el, amit a látogató ezen az oldalbetöltésen eldönt.
- **A látogató válasza előtt semmi nem megy el.** Olyan oldalbetöltésnél, ahol nincs marketing-hozzájárulás, a bővítmény csendben marad, nem küld `rejectConsent` eseményt. Amíg a sávra nem érkezik válasz, nincs mit jelenteni.
- **Csak a változások mennek el.** Az ismételt azonos állapot nem megy el kétszer, ami azért számít, mert egyetlen kattintás egyszerre két adapteren is megérkezhet.

Az a visszatérő látogató tehát, aki egy korábbi látogatáskor elfogadta, semmit nem vált
ki — és ez így helyes: a bp.js a saját `BarionMarketingConsent` cookie-jában tárolja a
választ, tehát a Barionnak már megvan. Az újraküldés minden oldalbetöltéskor éppen az
volt, ami miatt az integrációt elutasították. Ha látni akarod elsülni a `grantConsent`
eseményt, előbb töröld a cookie-kat, hogy a sáv újra kérdezzen.

## WP Consent API — továbbra is ajánlott

A [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) a WordPress szabványa
a hozzájárulás bővítmények közötti továbbítására, és a Barion Pixel a `marketing`
kategóriájában regisztrál. Ez egy **külön bővítmény** — nem része a WordPressnek, és nem
része a cookie-sávodnak. A
[core-ba emelésére vonatkozó javaslat](https://make.wordpress.org/core/2024/12/04/lets-reconsider-adopting-the-wp-consent-api/)
nyitott, de nincs elfogadva.

Akkor telepítsd, ha a cookie-sávod nincs a fenti táblázatban. A legtöbb sáv támogatja a
WP Consent API-t, de csak amíg az a bővítmény aktív: a CookieYes például csak akkor
tölti be a hidat, ha a `WP_CONSENT_API` osztály létezik. Nélküle ezek a sávok semmit nem
továbbítanak, és a bővítménynek a közvetlen integrációkra kell hagyatkoznia.

| Bővítmény | Aktív telepítések |
|--------|----------------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5M+ |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1M+ |
| [Cookie Compliance by Hu-manity.co](https://wordpress.org/plugins/cookie-notice/) | 900E+ |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300K+ |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100K+ |

---

## Kézi integráció

Egyedi hozzájárulás-kezelőkhöz, vagy ha a fentiek közül egyik sem érvényes.

### 1. módszer: JavaScript függvények (ajánlott)

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

### 2. módszer: Egyedi DOM események

```javascript
// Grant consent
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Reject consent
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### 3. módszer: WordPress action hook

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

### Példa: OneTrust

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

A bővítmény továbbítja a hozzájárulást. A szabályzataidat nem tudja megírni, a sávodat
nem tudja beállítani, a Barion viszont mindkettőt megköveteli. A
[Barion követelményeiből](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements):

- **Vedd fel a Barion cookie-kat a cookie-szabályzatodba.** A `ba_vid`, `ba_vid.xxx`, `ba_sid` és `ba_sid.xxx` az elengedhetetlen cookie-k közé tartozik — a Barion jogos érdeke alapján a csalásmegelőzést szolgálják, és nem igényelnek hozzájárulást. A `BarionMarketingConsent.xxx` és a média- és hirdetési partnerek cookie-jai a marketing cookie-k közé tartoznak, és igényelnek hozzájárulást.
- **Említsd meg a Barion Pixelt az adatkezelési tájékoztatódban**, és hivatkozz a Barion [adatvédelmi tájékoztatójára](https://www.barion.com/en/privacy-notice/).
- **Tedd lehetővé, hogy a vásárlók bármikor módosítsák vagy visszavonják a hozzájárulásukat**, és kérdezz rá újra. A Barion azt kéri, hogy a sáv legalább 13 havonta jelenjen meg újra, és 30 napot ajánl.
- **Használd a Barion által ajánlott sávszöveget**, ahol csak tudod. A követelményoldalon található, és lefedi azt a partneri adatmegosztást, amit a Barion Pixel jelent.

---

## Hogyan hat a hozzájárulás a pixelre

| Állapot | Alap pixel (bp.js) | pageView | Marketing adatgyűjtés |
|-------|--------------------|----------|--------------------------|
| Bármilyen hozzájárulási művelet előtt | Betöltve | Elsül (csalásmegelőzés) | Nem |
| `grantConsent` után | Betöltve | Elsül | Igen |
| `rejectConsent` után | Betöltve | Elsül (csalásmegelőzés) | Nem |

---

## Tesztelés

1. Kapcsold be a **Debug Mode** beállítást a Beállítások > Barion Pixel oldalon.
2. Nyisd meg a böngésző konzolját (F12).
3. Keresd ezeket az üzeneteket:

| Üzenet | Jelentés |
|---------|---------|
| `Consent manager detected: …` | A megnevezett kezelőket megtalálta és bekötötte |
| `No consent manager detected…` | Nem talált semmit — hívd meg te a függvényeket |
| `Consent granted (grantConsent)` | A `grantConsent` eljutott a bp.js-hez |
| `Consent rejected (rejectConsent)` | A `rejectConsent` eljutott a bp.js-hez |

Minden üzenet `[Barion Pixel]` előtaggal jelenik meg.

4. Teszteld a sávodon az elfogadási és az elutasítási utat is.
5. A hozzájárulási függvények többször is nyugodtan hívhatók.

A `No consent manager detected` figyelmeztetésként a bővítmény beállítási oldalán is
megjelenik, ha a WP Consent API bővítmény inaktív, mivel ez az a hiba, ami miatt a
Teljes Pixel integrációt elutasítják.

A beállítási oldal egy második figyelmeztetést is tartalmaz az emögött rejlő csapdára: a
WP Consent API aktív, de egyetlen cookie-sáv sem regisztrált nála. Önmagában az API
mindenkire „megadva” választ ad, mert a be nem állított hozzájárulási típussal éppen azt
mondja, hogy nincs mögötte sáv. Ha olyan sáv mellé telepíted, amelyik nem támogatja,
ezzel semmit nem kötsz össze — csak azt éred el, hogy minden látogató úgy néz ki, mintha
hozzájárult volna. A bővítmény ilyen állapotban figyelmen kívül hagyja.
