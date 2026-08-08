> 🌐 Toto je automatický překlad. Komunitní opravy jsou vítány!
>
> [English version](../../cookie-consent.md)

# Integrace souhlasu s cookies

## Přehled

Barion Pixel vyžaduje explicitní souhlas uživatele před sběrem marketingových dat (soulad s GDPR). Plugin musí volat `bp('consent', 'grantConsent')`, když uživatel souhlas přijme, a `bp('consent', 'rejectConsent')`, když ho odmítne. Obě události jsou povinné dle požadavků Barion.

Skript základního pixelu se vždy načte pro prevenci podvodů, ale žádná marketingová data nejsou sbírána, dokud není souhlas explicitně udělen nebo odmítnut.

**Důležité:** Váš banner cookies musí nabízet možnost jak přijetí, tak odmítnutí. „Cookie wall" (pouze přijetí) není v souladu s GDPR od roku 2020 a Barion ho odmítne.

Plugin podporuje čtyři úrovně integrace souhlasu, kontrolované v tomto pořadí:

1. **Zaznamenaný spouštěč** — signál cookie zachycený průvodcem nastavením; vyhrává pouze tehdy,
   když jsou zaznamenány obě hodnoty, přijetí i odmítnutí, protože je majitel obchodu nastavil
   záměrně. Napůl naučený spouštěč — zaznamenaná jen jedna hodnota — se zcela ignoruje a plugin
   přejde na další úroveň, protože Barion vyžaduje jak `grantConsent`, tak `rejectConsent`.
2. **WP Consent API** (doporučeno) — univerzální, funguje se všemi hlavními pluginy pro cookies
3. **Cookie Law Info** (záloha) — přímá integrace pro weby používající CookieYes/Cookie Law Info
4. **Ruční** — pro vlastní správce souhlasu nebo speciální případy

---

## Panel stavu

Nastavení › Barion Pixel se otevře s panelem stavu. Ten spustí všechny níže uvedené kontroly a
nejprve zobrazí nejhorší výsledek. Když vše projde, sbalí se do jednoho zeleného řádku.

Nejdůležitější kontrolou je **„No cookie banner plugin sets a consent type"** (žádný plugin s
bannerem cookies nenastavuje typ souhlasu). WP Consent API nahlásí souhlas pro každou kategorii,
když nic typ souhlasu nenastaví:

> If there's no consent management plugin to set it, it will return `false`. This will cause all
> consent categories to return `true`.

Web s aktivním WP Consent API, ale bez banneru cookies, proto uděluje souhlas Barion pro každého
návštěvníka, aniž by byl jakýkoli souhlas skutečně shromážděn. To porušuje GDPR i podmínky Barion.

Některé bannery nastavují typ souhlasu jen v prohlížeči, takže panel nejprve zobrazí varování a
nabídne tlačítko **Check in browser**. Tato kontrola načte skutečné hodnoty z vašeho frontendu ještě
před jakoukoli interakcí a podle toho zbarví řádek červeně nebo zeleně.

### Cookies Barion

`bp.js` nastavuje na vaší vlastní doméně tři cookies první strany. Ke každému názvu se za běhu
připojí hash vaší domény.

| Cookie | Doba platnosti | Účel |
|--------|-----------------|------|
| `ba_sid` | 30 minut | Seskupuje zobrazení stránek do jedné relace. Barion ji používá k prevenci podvodů. |
| `ba_vid` | 1,5 roku | Identifikuje vracejícího se návštěvníka pro marketingovou analytiku. |
| `BarionMarketingConsent` | 1,5 roku, odstraněna při odmítnutí návštěvníka | Zaznamenává volbu souhlasu. |

Když je aktivní plugin WP Consent API, plugin automaticky deklaruje všechny tři, takže se objeví ve
vašich zásadách používání cookies. Bez něj je nutné je přidat ručně.

## Průvodce nastavením

Pokud žádný zdroj souhlasu nefunguje, panel nabídne **Set up consent**. Průvodce otevře váš obchod
na nové kartě, vy tam přijmete souhlas ve svém vlastním banneru a plugin zaznamená, které cookie se
změnilo. Totéž zopakujete pro odmítnutí. Barion vyžaduje jak `grantConsent`, tak `rejectConsent`,
takže průvodce odmítne uložení, dokud nemá obě hodnoty.

Průvodce ukládá název cookie, přijatou a odmítnutou hodnotu a až pět názvů událostí. Nikdy
neukládá ani nespouští JavaScript, který byste dodali vy sami. Záznamník se načte pouze pro
přihlášeného administrátora, který přijde s platným nonce; návštěvníkovi se nikdy nenačte.

### Proč je kategorie souhlasu pevně daná

Plugin vždy žádá o kategorii `marketing` a nenabízí žádnou volbu. WP Consent API definuje pět
pevných kategorií a pluginy s bannery cookies mapují své vlastní kategorie na tyto v kódu. CookieYes
mapuje Advertisement na marketing, Analytics na statistics, Functional na preferences a Performance
na functional. Toto mapování nelze změnit.

Barion vyžaduje souhlas pro marketingové účely, takže `marketing` je jediná správná kategorie.
Výběr kategorie by umožnil spustit Barion na zaškrtnutí u statistik, což porušuje podmínky Barion.

---

## Úroveň 2: WP Consent API (doporučeno)

[WP Consent API](https://wordpress.org/plugins/wp-consent-api/) je standard WordPressu pro komunikaci souhlasu. Je podporován všemi hlavními pluginy pro souhlas s cookies.

### Jak to funguje

Plugin za běhu kontroluje přítomnost funkce `wp_has_consent()` v JavaScriptu. Pokud je WP Consent API k dispozici:

1. Při načtení stránky zkontroluje, zda je udělen nebo odmítnut souhlas s `marketing`
2. Volá `bp('consent', 'grantConsent')`, pokud je marketingový souhlas udělen
3. Volá `bp('consent', 'rejectConsent')`, pokud marketingový souhlas není udělen
4. Naslouchá události `wp_listen_for_consent_change` pro aktualizace souhlasu v reálném čase — podle toho udělí nebo odmítne souhlas

### Podporované pluginy pro cookies

Automaticky bude fungovat jakýkoli plugin, který implementuje WP Consent API:

| Plugin | Aktivní instalace | Poznámky |
|--------|------------------|---------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5 M+ | WP Consent API zabudované |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1 M+ | Spolutvůrce WP Consent API |
| [Cookie Notice od dFactory](https://wordpress.org/plugins/cookie-notice/) | 1 M+ | Kompatibilní s WP Consent API |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300 K+ | Kompatibilní s WP Consent API |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100 K+ | Kompatibilní s WP Consent API |

### Nastavení

1. Nainstalujte a aktivujte plugin [WP Consent API](https://wordpress.org/plugins/wp-consent-api/)
2. Nainstalujte a nakonfigurujte váš preferovaný plugin pro souhlas s cookies (viz tabulka výše)
3. Nainstalujte a nakonfigurujte Advanced Pixel for Barion
4. Žádná další konfigurace není potřeba — souhlas je zpracováván automaticky

### Kategorie souhlasu

Barion Pixel je registrován v kategorii souhlasu `marketing` ve WP Consent API. Toto je standardní kategorie pro sledovací pixely používané pro retargeting a analýzy.

---

## Úroveň 3: Cookie Law Info (záloha)

Pokud WP Consent API není k dispozici, plugin přejde na přímou integraci s pluginem [Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) / CookieYes.

### Jak to funguje

1. Zkontroluje přítomnost globálního JavaScriptového objektu `CLI`
2. Pokud jsou cookies již přijaty (vracející se návštěvník), souhlas je udělen okamžitě
3. Pokud cookies nejsou přijaty, souhlas je odmítnut okamžitě
4. Naslouchá události `cli_user_preference_set`, když uživatel interaguje s bannerem cookies
5. Udělí nebo odmítne souhlas na základě hodnoty cookie `cookielawinfo-checkbox-necessary`

### Nastavení

Není potřeba žádná konfigurace. Nainstalujte oba pluginy a integrace bude fungovat automaticky.

---

## Úroveň 4: Ruční integrace

Pro vlastní správce souhlasu nebo prostředí, kde není k dispozici ani WP Consent API, ani Cookie Law Info.

### Metoda 1: JavaScriptové funkce (doporučeno)

```javascript
// Když uživatel přijme marketingové cookies
function onMarketingConsentGranted() {
    if (typeof window.wcBarionGrantConsent === 'function') {
        window.wcBarionGrantConsent();
    }
}

// Když uživatel odmítne marketingové cookies
function onMarketingConsentRejected() {
    if (typeof window.wcBarionRejectConsent === 'function') {
        window.wcBarionRejectConsent();
    }
}
```

### Metoda 2: Vlastní DOM události

```javascript
// Udělení souhlasu
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Odmítnutí souhlasu
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### Metoda 3: WordPress action hook

```php
// Ve vašem pluginu správce souhlasu nebo šabloně
add_action('wc_barion_pixel_footer_scripts', 'my_barion_consent_handler');

function my_barion_consent_handler() {
    ?>
    <script>
    // Vaše vlastní logika souhlasu zde
    </script>
    <?php
}
```

### Příklady pro konkrétní správce souhlasu

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

## Vliv souhlasu na pixel

| Stav | Základní pixel (bp.js) | pageView | Sběr marketingových dat |
|------|------------------------|----------|------------------------|
| Před jakoukoli akcí souhlasu | Načten | Spouští se (prevence podvodů) | Žádná data nejsou sbírána |
| Po `grantConsent` | Načten | Spouští se | Plný sběr dat povolen |
| Po `rejectConsent` | Načten | Spouští se (prevence podvodů) | Žádná marketingová data nejsou sbírána |

Základní pixel se vždy načte pro prevenci podvodů ze strany Barion. Volání `grantConsent` / `rejectConsent` kontrolují, zda jsou marketingová data sbírána.

---

## Testování

1. Povolte **Režim ladění** v Nastavení > Barion Pixel
2. Otevřete konzoli prohlížeče (F12)
3. Hledejte zprávy protokolu týkající se souhlasu:
   - `[Barion Pixel] Consent granted via the recorded cookie trigger` — Úroveň 1, přijato
   - `[Barion Pixel] Consent rejected via the recorded cookie trigger` — Úroveň 1, odmítnuto
   - `[Barion Pixel] Consent auto-granted via WP Consent API` — Úroveň 2, uživatel přijal
   - `[Barion Pixel] Consent auto-rejected via WP Consent API` — Úroveň 2, uživatel odmítl
   - `[Barion Pixel] Consent auto-granted via Cookie Law Info` — Úroveň 3, uživatel přijal
   - `[Barion Pixel] Consent auto-rejected via Cookie Law Info` — Úroveň 3, uživatel odmítl
   - `[Barion Pixel] No consent manager detected...` — Úroveň 4 (ruční režim)
   - `[Barion Pixel] Consent granted (grantConsent)` — souhlas byl udělen (jakákoli úroveň)
   - `[Barion Pixel] Consent rejected (rejectConsent)` — souhlas byl odmítnut (jakákoli úroveň)
4. Otestujte tok přijetí i odmítnutí na vašem banneru cookies
5. Funkce souhlasu lze bezpečně volat vícekrát (jsou idempotentní)
