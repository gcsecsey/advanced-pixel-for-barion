> 🌐 Toto je automatický překlad. Komunitní opravy jsou vítány!
>
> [English version](../../cookie-consent.md)

# Integrace souhlasu s cookies

Závazná je zde vlastní stránka Barionu:
[Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements).
Najdete na ní i text lišty souhlasu doporučený Barionem a aktuální seznam reklamních partnerů
Barionu. Přečtěte si ji před spuštěním do ostrého provozu — za soulad s předpisy odpovídá
obchodník, nikoli plugin.

## Co dělá plugin

Skript základního pixelu se načte vždy a `pageView` se odešle vždy. Barion to dokumentuje jako
oprávněný zájem: základní pixel slouží prevenci platebních podvodů a data získaná bez
marketingového souhlasu se používají jen k tomuto účelu.

Nad rámec toho plugin volá `bp('consent', 'grantConsent')`, když zákazník přijme marketingové
cookies, a `bp('consent', 'rejectConsent')`, když je odmítne. Barion uvádí obě jako povinné. Vaše
lišta proto musí nabízet skutečnou možnost odmítnutí — u lišty, která zná jen souhlas, nemá plugin
co hlásit.

Plugin hledá správce souhlasu v tomto pořadí a zastaví se u prvního nalezeného:

1. **WP Consent API** (doporučeno) — univerzální, funguje se všemi hlavními cookie pluginy
2. **Cookie Law Info** (záloha) — přímá integrace pro CookieYes / Cookie Law Info
3. **Ručně** — pro vlastní správce souhlasu

---

## Úroveň 1: WP Consent API (doporučeno)

[WP Consent API](https://wordpress.org/plugins/wp-consent-api/) je standard WordPressu pro předávání
souhlasu mezi pluginy. Barion Pixel se registruje v kategorii `marketing`.

### Jak to funguje

Po události `DOMContentLoaded` plugin ověří, zda existuje funkce `wp_has_consent()`. Pokud ano:

1. Je-li souhlas `marketing` už udělen, `grantConsent` se odešle okamžitě.
2. Od té chvíle plugin naslouchá události `wp_listen_for_consent_change` a při každé změně odesílá `grantConsent` nebo `rejectConsent`.

Všimněte si, co v seznamu *není*: při načtení stránky, kde marketingový souhlas chybí, plugin
mlčí, místo aby odeslal `rejectConsent`. Dokud zákazník na lištu neodpověděl, není co hlásit — a
odpověď dorazí přes událost změny.

### Podporované cookie pluginy

Automaticky funguje každý plugin, který implementuje WP Consent API:

| Plugin | Aktivní instalace | Poznámka |
|--------|-------------------|----------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5M+ | WP Consent API vestavěno |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1M+ | Spoluautor WP Consent API |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1M+ | Kompatibilní s WP Consent API |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300K+ | Kompatibilní s WP Consent API |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100K+ | Kompatibilní s WP Consent API |

### Nastavení

1. Nainstalujte a aktivujte [WP Consent API](https://wordpress.org/plugins/wp-consent-api/).
2. Nainstalujte a nastavte svůj cookie plugin.
3. Nainstalujte a nastavte Advanced Pixel for Barion.

Nic dalšího není potřeba — souhlas se řeší automaticky.

---

## Úroveň 2: Cookie Law Info (záloha)

Použije se, když WP Consent API k dispozici není, ale
[Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) / CookieYes ano.

### Jak to funguje

1. Plugin ověří globální objekt `CLI` a jeho `allowedCategories`.
2. Pokud má cookie `cookielawinfo-checkbox-non-necessary` už hodnotu `yes` — vracející se návštěvník, který souhlasil —, `grantConsent` se odešle okamžitě.
3. Plugin sleduje kliknutí na prvky `.cli_action_button` v liště. Krátce po kliknutí cookie znovu přečte a podle toho odešle `grantConsent` nebo `rejectConsent`.

### Nastavení

Žádné. Nainstalujte oba pluginy a funguje to.

---

## Úroveň 3: Ruční integrace

Pro vlastní správce souhlasu nebo tam, kde nic z výše uvedeného neplatí.

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
// Ve vašem pluginu pro správu souhlasu nebo v šabloně
add_action('wc_barion_pixel_footer_scripts', 'my_barion_consent_handler');

function my_barion_consent_handler() {
    ?>
    <script>
    // Sem přijde vaše vlastní logika souhlasu
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

## Co musíte zařídit sami

Plugin souhlas předává dál. Vaše zásady za vás nenapíše a lištu nenastaví, přičemž Barion
vyžaduje obojí. Z [požadavků Barionu](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements):

- **Přidejte cookies Barionu do svých zásad cookies.** `ba_vid`, `ba_vid.xxx`, `ba_sid` a `ba_sid.xxx` patří mezi nezbytné cookies — slouží prevenci podvodů na základě oprávněného zájmu Barionu a souhlas nevyžadují. `BarionMarketingConsent.xxx` a cookies mediálních a reklamních partnerů patří mezi marketingové cookies a souhlas vyžadují.
- **Zmiňte Barion Pixel ve svých zásadách ochrany osobních údajů** a odkažte na [oznámení o ochraně soukromí](https://www.barion.com/en/privacy-notice/) Barionu.
- **Umožněte zákazníkům kdykoli změnit nebo odvolat souhlas** a znovu se jich zeptejte. Barion požaduje, aby se lišta objevila znovu nejméně jednou za 13 měsíců, a doporučuje 30 dní.
- **Použijte text lišty doporučený Barionem**, kde to jde. Najdete jej na stránce s požadavky a pokrývá i sdílení dat s partnery, které s Barion Pixelem souvisí.

---

## Jak souhlas ovlivňuje pixel

| Stav | Základní pixel (bp.js) | pageView | Sběr marketingových dat |
|------|------------------------|----------|-------------------------|
| Před jakýmkoli rozhodnutím o souhlasu | Načteno | Odesílá se (prevence podvodů) | Ne |
| Po `grantConsent` | Načteno | Odesílá se | Ano |
| Po `rejectConsent` | Načteno | Odesílá se (prevence podvodů) | Ne |

---

## Testování

1. Zapněte **režim ladění** v Nastavení > Barion Pixel.
2. Otevřete konzoli prohlížeče (F12).
3. Sledujte tyto zprávy:

| Zpráva | Význam |
|--------|--------|
| `Consent auto-granted via WP Consent API` | Úroveň 1, souhlas byl při načtení už udělen |
| `Consent granted via WP Consent API change event` | Úroveň 1, zákazník právě souhlasil |
| `Consent rejected via WP Consent API change event` | Úroveň 1, zákazník právě odmítl |
| `Cookie Law Info detected, initial non-necessary cookie: …` | Převzala úroveň 2, s přečtenou hodnotou cookie |
| `Cookie Law Info button clicked, non-necessary cookie: …` | Úroveň 2, zákazník použil lištu |
| `No consent manager detected…` | Úroveň 3 — nic se nenašlo, funkce zavolejte sami |
| `Consent granted (grantConsent)` | `grantConsent` dorazil do bp.js (jakákoli úroveň) |
| `Consent rejected (rejectConsent)` | `rejectConsent` dorazil do bp.js (jakákoli úroveň) |

Všechny zprávy mají předponu `[Barion Pixel]`.

4. Otestujte na své liště cestu souhlasu i odmítnutí.
5. Funkce souhlasu lze bezpečně volat opakovaně.
