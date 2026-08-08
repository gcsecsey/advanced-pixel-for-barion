> 🌐 Toto je automatický preklad. Komunitné opravy sú vítané!
>
> [English version](../../cookie-consent.md)

# Integrácia súhlasu s cookies

Záväzná je tu vlastná stránka Barionu:
[Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements).
Nájdeš na nej aj text lišty súhlasu odporúčaný Barionom a aktuálny zoznam reklamných partnerov
Barionu. Prečítaj si ju pred spustením do ostrej prevádzky — za súlad s predpismi zodpovedá
obchodník, nie plugin.

## Čo robí plugin

Skript základného pixela sa načíta vždy a `pageView` sa odošle vždy. Barion to dokumentuje ako
oprávnený záujem: základný pixel slúži prevencii platobných podvodov a údaje získané bez
marketingového súhlasu sa používajú len na tento účel.

Nad rámec toho plugin volá `bp('consent', 'grantConsent')`, keď zákazník prijme marketingové
cookies, a `bp('consent', 'rejectConsent')`, keď ich odmietne. Barion uvádza obe ako povinné. Tvoja
lišta preto musí ponúkať skutočnú možnosť odmietnutia — pri lište, ktorá pozná len súhlas, nemá
plugin čo hlásiť.

Plugin hľadá správcu súhlasu v tomto poradí a zastaví sa pri prvom nájdenom:

1. **WP Consent API** (odporúčané) — univerzálne, funguje so všetkými hlavnými cookie pluginmi
2. **Cookie Law Info** (záloha) — priama integrácia pre CookieYes / Cookie Law Info
3. **Ručne** — pre vlastných správcov súhlasu

---

## Úroveň 1: WP Consent API (odporúčané)

[WP Consent API](https://wordpress.org/plugins/wp-consent-api/) je štandard WordPressu na
odovzdávanie súhlasu medzi pluginmi. Barion Pixel sa registruje v kategórii `marketing`.

### Ako to funguje

Po udalosti `DOMContentLoaded` plugin overí, či existuje funkcia `wp_has_consent()`. Ak áno:

1. Ak je súhlas `marketing` už udelený, `grantConsent` sa odošle okamžite.
2. Odvtedy plugin počúva udalosť `wp_listen_for_consent_change` a pri každej zmene odosiela `grantConsent` alebo `rejectConsent`.

Všimni si, čo v zozname *nie je*: pri načítaní stránky, kde marketingový súhlas chýba, plugin
mlčí, namiesto toho aby odoslal `rejectConsent`. Kým zákazník na lištu neodpovedal, nie je čo
hlásiť — a odpoveď príde cez udalosť zmeny.

### Podporované cookie pluginy

Automaticky funguje každý plugin, ktorý implementuje WP Consent API:

| Plugin | Aktívne inštalácie | Poznámka |
|--------|--------------------|----------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5M+ | WP Consent API zabudované |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1M+ | Spoluautor WP Consent API |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1M+ | Kompatibilné s WP Consent API |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300K+ | Kompatibilné s WP Consent API |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100K+ | Kompatibilné s WP Consent API |

### Nastavenie

1. Nainštaluj a aktivuj [WP Consent API](https://wordpress.org/plugins/wp-consent-api/).
2. Nainštaluj a nastav svoj cookie plugin.
3. Nainštaluj a nastav Advanced Pixel for Barion.

Nič ďalšie netreba — súhlas sa rieši automaticky.

---

## Úroveň 2: Cookie Law Info (záloha)

Použije sa, keď WP Consent API nie je k dispozícii, ale
[Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) / CookieYes áno.

### Ako to funguje

1. Plugin overí globálny objekt `CLI` a jeho `allowedCategories`.
2. Ak má cookie `cookielawinfo-checkbox-non-necessary` už hodnotu `yes` — vracajúci sa návštevník, ktorý súhlasil —, `grantConsent` sa odošle okamžite.
3. Plugin sleduje kliknutia na prvky `.cli_action_button` v lište. Krátko po kliknutí cookie znovu prečíta a podľa toho odošle `grantConsent` alebo `rejectConsent`.

### Nastavenie

Žiadne. Nainštaluj oba pluginy a funguje to.

---

## Úroveň 3: Ručná integrácia

Pre vlastných správcov súhlasu alebo tam, kde nič z uvedeného neplatí.

### Metóda 1: JavaScriptové funkcie (odporúčané)

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

### Metóda 2: Vlastné DOM udalosti

```javascript
// Udelenie súhlasu
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Odmietnutie súhlasu
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### Metóda 3: WordPress action hook

```php
// V tvojom plugine na správu súhlasu alebo v šablóne
add_action('wc_barion_pixel_footer_scripts', 'my_barion_consent_handler');

function my_barion_consent_handler() {
    ?>
    <script>
    // Sem príde tvoja vlastná logika súhlasu
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

## Čo musíš zariadiť sám

Plugin súhlas posúva ďalej. Tvoje zásady za teba nenapíše a lištu nenastaví, pričom Barion
vyžaduje oboje. Z
[požiadaviek Barionu](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements):

- **Pridaj cookies Barionu do svojich zásad cookies.** `ba_vid`, `ba_vid.xxx`, `ba_sid` a `ba_sid.xxx` patria medzi nevyhnutné cookies — slúžia prevencii podvodov na základe oprávneného záujmu Barionu a súhlas nevyžadujú. `BarionMarketingConsent.xxx` a cookies mediálnych a reklamných partnerov patria medzi marketingové cookies a súhlas vyžadujú.
- **Spomeň Barion Pixel vo svojich zásadách ochrany osobných údajov** a odkáž na [oznámenie o ochrane súkromia](https://www.barion.com/en/privacy-notice/) Barionu.
- **Umožni zákazníkom kedykoľvek zmeniť alebo odvolať súhlas** a znovu sa ich opýtaj. Barion požaduje, aby sa lišta objavila znovu aspoň raz za 13 mesiacov, a odporúča 30 dní.
- **Použi text lišty odporúčaný Barionom**, kde sa dá. Nájdeš ho na stránke s požiadavkami a pokrýva aj zdieľanie údajov s partnermi, ktoré s Barion Pixelom súvisí.

---

## Ako súhlas ovplyvňuje pixel

| Stav | Základný pixel (bp.js) | pageView | Zber marketingových údajov |
|------|------------------------|----------|---------------------------|
| Pred akýmkoľvek rozhodnutím o súhlase | Načítaný | Odosiela sa (prevencia podvodov) | Nie |
| Po `grantConsent` | Načítaný | Odosiela sa | Áno |
| Po `rejectConsent` | Načítaný | Odosiela sa (prevencia podvodov) | Nie |

---

## Testovanie

1. Zapni **režim ladenia** v Nastavenia > Barion Pixel.
2. Otvor konzolu prehliadača (F12).
3. Sleduj tieto správy:

| Správa | Význam |
|--------|--------|
| `Consent auto-granted via WP Consent API` | Úroveň 1, súhlas bol pri načítaní už udelený |
| `Consent granted via WP Consent API change event` | Úroveň 1, zákazník práve súhlasil |
| `Consent rejected via WP Consent API change event` | Úroveň 1, zákazník práve odmietol |
| `Cookie Law Info detected, initial non-necessary cookie: …` | Prevzala úroveň 2, s prečítanou hodnotou cookie |
| `Cookie Law Info button clicked, non-necessary cookie: …` | Úroveň 2, zákazník použil lištu |
| `No consent manager detected…` | Úroveň 3 — nič sa nenašlo, funkcie zavolaj sám |
| `Consent granted (grantConsent)` | `grantConsent` dorazil do bp.js (ktorákoľvek úroveň) |
| `Consent rejected (rejectConsent)` | `rejectConsent` dorazil do bp.js (ktorákoľvek úroveň) |

Všetky správy majú predponu `[Barion Pixel]`.

4. Otestuj na svojej lište cestu súhlasu aj odmietnutia.
5. Funkcie súhlasu možno bezpečne volať opakovane.
