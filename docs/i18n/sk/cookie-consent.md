> 🌐 Toto je automatický preklad. Komunitné opravy sú vítané!
>
> [English version](../../cookie-consent.md)

# Integrácia súhlasu s cookies

## Prehľad

Barion Pixel vyžaduje výslovný súhlas používateľa pred zhromažďovaním marketingových údajov (súlad s GDPR). Plugin musí zavolať `bp('consent', 'grantConsent')`, keď používateľ prijme, a `bp('consent', 'rejectConsent')`, keď odmietne. Oba príkazy sú povinné podľa požiadaviek Barion.

Základný pixelový skript sa načíta vždy kvôli prevencii podvodov, ale žiadne marketingové údaje sa nezhromažďujú, kým nie je súhlas výslovne udelený alebo odmietnutý.

**Dôležité:** Tvoj banner na cookies musí ponúkať možnosť prijať aj odmietnuť. „Cookie wall" (iba prijatie) nie je v súlade s GDPR od roku 2020 a Barion ho odmietne.

Plugin podporuje štyri úrovne integrácie súhlasu, kontrolované v tomto poradí:

1. **Zaznamenaný spúšťač** — signál cookie zachytený sprievodcom nastavením; vyhráva iba vtedy,
   keď sú zaznamenané obe hodnoty, prijatie aj odmietnutie, pretože ich majiteľ obchodu nastavil
   zámerne. Napoly naučený spúšťač — zaznamenaná len jedna hodnota — sa úplne ignoruje a plugin
   prejde na ďalšiu úroveň, pretože Barion vyžaduje `grantConsent` aj `rejectConsent`.
2. **WP Consent API** (odporúčané) — univerzálne, funguje so všetkými hlavnými pluginmi na cookies
3. **Cookie Law Info** (záložné) — priama integrácia pre weby používajúce CookieYes/Cookie Law Info
4. **Manuálne** — pre vlastné správcovia súhlasu alebo okrajové prípady

---

## Panel stavu

Nastavenia › Barion Pixel sa otvorí s panelom stavu. Ten spustí všetky nižšie uvedené kontroly a
najprv zobrazí najhorší výsledok. Keď všetko prejde, zbalí sa do jedného zeleného riadku.

Najdôležitejšou kontrolou je **„No cookie banner plugin sets a consent type"** (žiadny plugin s
bannerom na cookies nenastavuje typ súhlasu). WP Consent API nahlási súhlas pre každú kategóriu,
keď nič nenastaví typ súhlasu:

> If there's no consent management plugin to set it, it will return `false`. This will cause all
> consent categories to return `true`.

Web s aktívnym WP Consent API, ale bez banneru na cookies, tak udeľuje súhlas Barion pre každého
návštevníka, bez toho, aby bol akýkoľvek súhlas skutočne získaný. To porušuje GDPR aj podmienky
Barion.

Niektoré bannery nastavujú typ súhlasu iba v prehliadači, takže panel najprv nahlási varovanie a
ponúkne tlačidlo **Check in browser**. Táto kontrola načíta skutočné hodnoty z vášho frontendu ešte
pred akoukoľvek interakciou a podľa toho zafarbí riadok načerveno alebo nazeleno.

### Cookies Barion

`bp.js` nastavuje na vašej vlastnej doméne tri cookies prvej strany. Ku každému názvu sa za behu
pripojí hash vašej domény.

| Cookie | Doba platnosti | Účel |
|--------|-----------------|------|
| `ba_sid` | 30 minút | Zoskupuje zobrazenia stránok do jednej relácie. Barion ju používa na prevenciu podvodov. |
| `ba_vid` | 1,5 roka | Identifikuje vracajúceho sa návštevníka pre marketingovú analytiku. |
| `BarionMarketingConsent` | 1,5 roka, odstránená pri odmietnutí návštevníka | Zaznamenáva voľbu súhlasu. |

Keď je aktívny plugin WP Consent API, plugin automaticky deklaruje všetky tri, takže sa objavia vo
vašich zásadách používania cookies. Bez neho je potrebné pridať ich ručne.

## Sprievodca nastavením

Ak nefunguje žiadny zdroj súhlasu, panel ponúkne **Set up consent**. Sprievodca otvorí váš obchod
na novej karte, vy tam prijmete súhlas vo svojom vlastnom banneri a plugin zaznamená, ktoré cookie
sa zmenilo. To isté zopakujete pre odmietnutie. Barion vyžaduje `grantConsent` aj `rejectConsent`,
takže sprievodca odmietne uloženie, kým nemá obe hodnoty.

Sprievodca ukladá názov cookie, prijatú a odmietnutú hodnotu a až päť názvov udalostí. Nikdy
neukladá ani nespúšťa JavaScript, ktorý by ste dodali vy sami. Záznamník sa načíta iba pre
prihláseného administrátora, ktorý príde s platným nonce; návštevníkovi sa nikdy nenačíta.

### Prečo je kategória súhlasu pevne daná

Plugin vždy žiada o kategóriu `marketing` a neponúka žiadnu voľbu. WP Consent API definuje päť
pevných kategórií a pluginy s bannermi na cookies mapujú svoje vlastné kategórie na tieto v kóde.
CookieYes mapuje Advertisement na marketing, Analytics na statistics, Functional na preferences a
Performance na functional. Toto mapovanie nemožno zmeniť.

Barion vyžaduje súhlas na marketingové účely, takže `marketing` je jediná správna kategória. Výber
kategórie by umožnil spustiť Barion na zaškrtnutí pri štatistikách, čo porušuje podmienky Barion.

---

## Úroveň 2: WP Consent API (odporúčané)

[WP Consent API](https://wordpress.org/plugins/wp-consent-api/) je štandard WordPress pre komunikáciu o súhlase. Podporujú ho všetky hlavné pluginy na súhlas s cookies.

### Ako to funguje

Plugin kontroluje funkciu `wp_has_consent()` v JavaScripte za behu. Ak je WP Consent API dostupné:

1. Pri načítaní stránky skontroluje, či je súhlas pre `marketing` udelený alebo odmietnutý
2. Zavolá `bp('consent', 'grantConsent')`, ak je marketingový súhlas udelený
3. Ak marketingový súhlas nie je udelený, neurobí nič — `rejectConsent` sa pri načítaní stránky neodosiela; odošle sa až potom, ako návštevník odpovie v cookie lište (pozri bod 4)
4. Počúva udalosť `wp_listen_for_consent_change` pre aktualizácie súhlasu v reálnom čase — udelí alebo odmietne podľa toho

### Podporované pluginy na cookies

Automaticky bude fungovať každý plugin, ktorý implementuje WP Consent API:

| Plugin | Aktívne inštalácie | Poznámky |
|--------|-------------------|---------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5M+ | WP Consent API vstavaný |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1M+ | Spoluzakladateľ WP Consent API |
| [Cookie Notice od dFactory](https://wordpress.org/plugins/cookie-notice/) | 1M+ | Kompatibilný s WP Consent API |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300K+ | Kompatibilný s WP Consent API |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100K+ | Kompatibilný s WP Consent API |

### Nastavenie

1. Nainštaluj a aktivuj plugin [WP Consent API](https://wordpress.org/plugins/wp-consent-api/)
2. Nainštaluj a nakonfiguruj preferovaný plugin na súhlas s cookies (pozri tabuľku vyššie)
3. Nainštaluj a nakonfiguruj Advanced Pixel for Barion
4. Nie je potrebná žiadna ďalšia konfigurácia — súhlas sa spracúva automaticky

### Kategória súhlasu

Barion Pixel je zaregistrovaný v kategórii súhlasu `marketing` vo WP Consent API. Ide o štandardnú kategóriu pre sledovacie pixely používané na retargeting a analýzu.

---

## Úroveň 3: Cookie Law Info (záložné)

Ak WP Consent API nie je dostupné, plugin sa vráti k priamej integrácii s pluginom [Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) / CookieYes.

### Ako to funguje

1. Skontroluje globálny objekt `CLI` v JavaScripte
2. Prečíta cookie `cookielawinfo-checkbox-non-necessary`; ak je jeho hodnota presne `yes`, okamžite udelí súhlas
3. Inak nič nerobí, kým návštevník neinteraguje s bannerom
4. Počúva kliknutia na ľubovoľný prvok zodpovedajúci `.cli_action_button`
5. 100 milisekúnd po kliknutí znovu prečíta rovnaké cookie a podľa toho udelí alebo odmietne súhlas

### Nastavenie

Nie je potrebná žiadna konfigurácia. Nainštaluj oba pluginy a integrácia bude fungovať automaticky.

---

## Úroveň 4: Manuálna integrácia

Pre vlastných správcov súhlasu alebo prostredia, kde nie je k dispozícii ani WP Consent API ani Cookie Law Info.

### Metóda 1: Funkcie JavaScriptu (odporúčané)

```javascript
// Keď používateľ prijme marketingové cookies
function onMarketingConsentGranted() {
    if (typeof window.wcBarionGrantConsent === 'function') {
        window.wcBarionGrantConsent();
    }
}

// Keď používateľ odmietne marketingové cookies
function onMarketingConsentRejected() {
    if (typeof window.wcBarionRejectConsent === 'function') {
        window.wcBarionRejectConsent();
    }
}
```

### Metóda 2: Vlastné udalosti DOM

```javascript
// Udeliť súhlas
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Odmietnuť súhlas
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### Metóda 3: WordPress action hook

```php
// V plugine správcu súhlasu alebo téme
add_action('wc_barion_pixel_footer_scripts', 'my_barion_consent_handler');

function my_barion_consent_handler() {
    ?>
    <script>
    // Tu umiesti svoju vlastnú logiku súhlasu
    </script>
    <?php
}
```

### Príklady pre konkrétnych správcov súhlasu

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

## Ako súhlas ovplyvňuje pixel

| Stav | Základný pixel (bp.js) | pageView | Zhromažďovanie marketingových údajov |
|------|------------------------|----------|--------------------------------------|
| Pred akoukoľvek akciou súhlasu | Načítaný | Spúšťa sa (prevencia podvodov) | Žiadne údaje sa nezhromažďujú |
| Po `grantConsent` | Načítaný | Spúšťa sa | Plné zhromažďovanie údajov povolené |
| Po `rejectConsent` | Načítaný | Spúšťa sa (prevencia podvodov) | Žiadne marketingové údaje sa nezhromažďujú |

Základný pixel sa načíta vždy kvôli prevencii podvodov Barion. Volania `grantConsent` / `rejectConsent` určujú, či sa zhromažďujú marketingové údaje.

---

## Testovanie

1. Povoľ **Režim ladenia** v Nastavenia > Barion Pixel
2. Otvor konzolu prehliadača (F12)
3. Hľadaj správy týkajúce sa súhlasu v protokole:
   - `[Barion Pixel] bp.js loaded by Advanced Pixel for Barion` — tento plugin načítal bp.js
   - `[Barion Pixel] bp.js already loaded by another plugin, skipping script load` — iný plugin (napr. Barion Payment Gateway) už načítal bp.js
   - `[Barion Pixel] Base pixel initialized with ID: <id>` — základný pixel beží s tvojím Pixel ID
   - `[Barion Pixel] Consent granted (grantConsent)` — súhlas bol udelený (akákoľvek úroveň)
   - `[Barion Pixel] Consent rejected (rejectConsent)` — súhlas bol odmietnutý (akákoľvek úroveň)
   - `[Barion Pixel] Consent auto-granted via WP Consent API` — Úroveň 2, súhlas bol udelený už pri načítaní stránky
   - `[Barion Pixel] Consent granted via WP Consent API change event` — Úroveň 2, používateľ prijal v banneri
   - `[Barion Pixel] Consent rejected via WP Consent API change event` — Úroveň 2, používateľ odmietol v banneri
   - `[Barion Pixel] Consent granted via the recorded cookie trigger` — Úroveň 1, prijaté
   - `[Barion Pixel] Consent rejected via the recorded cookie trigger` — Úroveň 1, odmietnuté
   - `[Barion Pixel] Cookie Law Info detected, initial non-necessary cookie: <value>` — Úroveň 3, hodnota cookie prečítaná pri načítaní stránky
   - `[Barion Pixel] Cookie Law Info button clicked, non-necessary cookie: <value>` — Úroveň 3, hodnota cookie prečítaná po kliknutí v banneri
   - `[Barion Pixel] No consent manager detected. Call window.wcBarionGrantConsent() or window.wcBarionRejectConsent() manually.` — Úroveň 4 (manuálny režim)

   Zámerne neexistuje žiadna správa, keď pri prvom načítaní chýba súhlas cez WP Consent API —
   plugin zaznamenáva iba to, keď koná, nie keď mlčí.
4. Otestuj toky prijatia aj odmietnutia na svojom banneri na cookies
5. Funkcie súhlasu je bezpečné volať viackrát (idempotentné)
