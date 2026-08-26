> 🌐 Toto je automatický překlad. Komunitní opravy jsou vítány!
>
> [English version](../../cookie-consent.md)

# Integrace souhlasu s cookies

Rozhodující je zde vlastní stránka Barionu:
[Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements).
Najdete na ní i text cookie lišty, který Barion doporučuje, a aktuální seznam reklamních
partnerů Barionu. Přečtěte si ji před spuštěním naostro — soulad s předpisy je odpovědností
obchodníka, ne pluginu.

Barion uvádí `grantConsent` také mezi událostmi, které
[musí být implementovány](https://docs.barion.com/Implementing_the_Full_Barion_Pixel),
než je integrace Full Pixel schválena. Obchod, který ji nikdy neodešle, nemá nárok na nižší
poplatky, ať je zbytek integrace jakkoli úplný.

## Co plugin dělá

Skript základního pixelu se načte vždy a `pageView` se vždy odešle. Barion to dokumentuje jako
oprávněný zájem: základní pixel slouží k prevenci platebních podvodů a data získaná bez
marketingového souhlasu se používají výhradně k tomu.

Nad rámec toho plugin volá `bp('consent', 'grantConsent')`, když zákazník přijme marketingové
cookies, a `bp('consent', 'rejectConsent')`, když je odmítne. Barion uvádí obojí jako povinné.
Vaše lišta proto musí nabízet skutečnou možnost odmítnutí — u lišty pouze s tlačítkem přijetí
nemá plugin co signalizovat.

## Jak se souhlas rozpoznává

Plugin si nevybírá jednoho správce souhlasu. Přihlašuje se najednou ke každému signálu souhlasu,
který zná, a předává první skutečnou odpověď i každou další změnu. Na pořadí načtení nezáleží:
posluchači jsou zaregistrováni dřív, než jakýkoli správce souhlasu existuje, takže se zachytí i
lišta, která se objeví později. Vracející se návštěvník žádnou lištu nevidí, a tedy nevyvolá
vůbec žádnou událost — proto plugin navíc každou půlsekundu hledá správce souhlasu, dokud
některý neodpoví, a deset sekund po načtení stránky to vzdá.

Tyto fungují bez dalšího pluginu:

| Správce souhlasu | Čte se přes |
|---|---|
| [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) | `wp_has_consent('marketing')` a `wp_listen_for_consent_change`, ale až když u něj lišta zaregistruje typ souhlasu |
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | `getCkyConsent()` a `cookieyes_consent_update` |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | `cmplz_has_consent('marketing')` a `cmplz_status_change` |
| [Cookiebot](https://wordpress.org/plugins/cookiebot/) | `Cookiebot.consent.marketing` a `CookiebotOnAccept` / `CookiebotOnDecline` / `CookiebotOnConsentReady` |
| Cookie Law Info 2.x, starší lišta | cookie `cookielawinfo-checkbox-non-necessary`, znovu přečtená po kliknutí na lištu |
| Cokoli jiného | funkce voláte sami — viz [Ruční integrace](#ruční-integrace) |

Na všechny se vztahují tři pravidla:

- **Souhlas se odesílá, když návštěvník odpoví na liště, nikdy při načtení stránky.** Barion očekává `grantConsent` v okamžiku kliknutí a odmítá integraci, která jej odešle dřív, než se návštěvník čehokoli dotkl — z pohledu Barionu to vypadá jako obchod, který se nikdy neptá. Plugin proto stav souhlasu při načtení přečte, ale nechá si jej pro sebe, a odesílá pouze to, co návštěvník rozhodne při tomto načtení stránky.
- **Před odpovědí návštěvníka se neodesílá nic.** Při načtení stránky bez marketingového souhlasu plugin mlčí, místo aby posílal `rejectConsent`. Dokud není lišta zodpovězena, není co hlásit.
- **Odesílají se pouze změny.** Opakovaně stejný stav se neodešle dvakrát, což je důležité, protože jedno kliknutí může dorazit dvěma adaptéry současně.

Vracející se návštěvník, který souhlasil při dřívější návštěvě, tedy nevyvolá nic — a to je
správně: bp.js ukládá odpověď do vlastní cookie `BarionMarketingConsent`, Barion ji tedy už má.
Právě opakované odesílání při každém načtení stránky bylo tím, proč byla integrace odmítnuta.
Chcete-li vidět `grantConsent` v akci, nejprve smažte cookies, aby se lišta zeptala znovu.

## WP Consent API — stále doporučeno

[WP Consent API](https://wordpress.org/plugins/wp-consent-api/) je standard WordPressu pro
předávání souhlasu mezi pluginy a Barion Pixel se registruje v jeho kategorii `marketing`. Je to
**samostatný plugin** — není součástí WordPressu ani vaší cookie lišty.
[Návrh na začlenění do jádra](https://make.wordpress.org/core/2024/12/04/lets-reconsider-adopting-the-wp-consent-api/)
je otevřený, ale nebyl přijat.

Nainstalujte jej, pokud vaše cookie lišta není v tabulce výše. Většina lišt WP Consent API
podporuje, ale jen dokud je tento plugin aktivní: CookieYes například načte svůj most jen tehdy,
když existuje třída `WP_CONSENT_API`. Bez ní tyto lišty nepředají nic a plugin se musí spolehnout
na přímé integrace.

| Plugin | Aktivní instalace |
|--------|----------------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5 mil.+ |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1 mil.+ |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1 mil.+ |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300 tis.+ |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100 tis.+ |

---

## Ruční integrace

Pro vlastní správce souhlasu, nebo když se nic z výše uvedeného nehodí.

### Metoda 1: JavaScriptové funkce (doporučeno)

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

### Metoda 2: Vlastní DOM události

```javascript
// Grant consent
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Reject consent
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### Metoda 3: WordPress action hook

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

### Příklad: OneTrust

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

## Co musíte zařídit sami

Plugin souhlas předává. Nemůže napsat vaše zásady ani nastavit vaši lištu, a Barion vyžaduje
obojí. Z [požadavků Barionu](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements):

- **Přidejte cookies Barionu do svých zásad cookies.** `ba_vid`, `ba_vid.xxx`, `ba_sid` a `ba_sid.xxx` patří mezi nezbytné cookies — slouží prevenci podvodů na základě oprávněného zájmu Barionu a souhlas nevyžadují. `BarionMarketingConsent.xxx` a cookies mediálních a reklamních partnerů patří mezi marketingové cookies a souhlas vyžadují.
- **Zmiňte Barion Pixel ve svých zásadách ochrany osobních údajů** a odkažte na [informace o ochraně soukromí](https://www.barion.com/en/privacy-notice/) Barionu.
- **Umožněte zákazníkům kdykoli změnit nebo odvolat souhlas** a znovu se jich zeptejte. Barion žádá, aby se lišta objevila znovu nejméně jednou za 13 měsíců, a doporučuje 30 dní.
- **Používejte text lišty doporučený Barionem**, kde to jde. Je na stránce s požadavky a pokrývá sdílení dat s partnery, které Barion Pixel obnáší.

---

## Jak souhlas ovlivňuje pixel

| Stav | Základní pixel (bp.js) | pageView | Sběr marketingových dat |
|-------|--------------------|----------|--------------------------|
| Před jakoukoli akcí souhlasu | Načteno | Odesílá se (prevence podvodů) | Ne |
| Po `grantConsent` | Načteno | Odesílá se | Ano |
| Po `rejectConsent` | Načteno | Odesílá se (prevence podvodů) | Ne |

---

## Testování

1. Zapněte **Debug Mode** v Nastavení > Barion Pixel.
2. Otevřete konzoli prohlížeče (F12).
3. Hledejte tyto zprávy:

| Zpráva | Význam |
|---------|---------|
| `Consent manager detected: …` | Uvedení správci byli nalezeni a zapojeni |
| `No consent manager detected…` | Nic nenalezeno — zavolejte funkce sami |
| `Consent granted (grantConsent)` | `grantConsent` dorazil do bp.js |
| `Consent rejected (rejectConsent)` | `rejectConsent` dorazil do bp.js |

Všechny zprávy mají předponu `[Barion Pixel]`.

4. Otestujte na své liště cestu přijetí i odmítnutí.
5. Funkce souhlasu lze bezpečně volat opakovaně.

`No consent manager detected` se objevuje také jako varování na stránce nastavení pluginu,
když je plugin WP Consent API neaktivní, protože právě kvůli této chybě bývá integrace Full
Pixel odmítnuta.

Stránka nastavení nese druhé varování pro past, která se za tím skrývá: WP Consent API je
aktivní, ale žádná cookie lišta se u něj nezaregistrovala. Samo o sobě API odpovídá „uděleno“
pro každého, protože nenastavený typ souhlasu je jeho způsob, jak říct, že jej neřídí žádná
lišta. Nainstalovat je vedle lišty, která je nepodporuje, proto nic nepropojí — jen to způsobí,
že každý návštěvník vypadá, jako by souhlasil. V tomto stavu je plugin ignoruje.
