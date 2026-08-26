> 🌐 Toto je automatický preklad. Komunitné opravy sú vítané!
>
> [English version](../../cookie-consent.md)

# Integrácia súhlasu s cookies

Rozhodujúca je tu vlastná stránka Barionu:
[Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements).
Nájdete na nej aj text cookie lišty, ktorý Barion odporúča, a aktuálny zoznam reklamných
partnerov Barionu. Prečítajte si ju pred spustením naostro — súlad s predpismi je
zodpovednosťou obchodníka, nie pluginu.

Barion uvádza `grantConsent` aj medzi udalosťami, ktoré
[musia byť implementované](https://docs.barion.com/Implementing_the_Full_Barion_Pixel),
než je integrácia Full Pixel schválená. Obchod, ktorý ju nikdy neodošle, nemá nárok na nižšie
poplatky, nech je zvyšok integrácie akokoľvek úplný.

## Čo plugin robí

Skript základného pixelu sa načíta vždy a `pageView` sa vždy odošle. Barion to dokumentuje ako
oprávnený záujem: základný pixel slúži na prevenciu platobných podvodov a údaje získané bez
marketingového súhlasu sa používajú výhradne na to.

Nad rámec toho plugin volá `bp('consent', 'grantConsent')`, keď zákazník prijme marketingové
cookies, a `bp('consent', 'rejectConsent')`, keď ich odmietne. Barion uvádza oboje ako povinné.
Vaša lišta preto musí ponúkať skutočnú možnosť odmietnutia — pri lište iba s tlačidlom prijatia
nemá plugin čo signalizovať.

## Ako sa súhlas rozpoznáva

Plugin si nevyberá jedného správcu súhlasu. Prihlasuje sa naraz ku každému signálu súhlasu,
ktorý pozná, a odovzdáva prvú skutočnú odpoveď aj každú ďalšiu zmenu. Na poradí načítania
nezáleží: poslucháči sú zaregistrovaní skôr, než akýkoľvek správca súhlasu existuje, takže sa
zachytí aj lišta, ktorá sa objaví neskôr. Vracajúci sa návštevník žiadnu lištu nevidí, a teda
nevyvolá vôbec žiadnu udalosť — preto plugin navyše každú pol sekundu hľadá správcu súhlasu,
kým niektorý neodpovie, a desať sekúnd po načítaní stránky to vzdá.

Tieto fungujú bez ďalšieho pluginu:

| Správca súhlasu | Číta sa cez |
|---|---|
| [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) | `wp_has_consent('marketing')` a `wp_listen_for_consent_change`, ale až keď uňho lišta zaregistruje typ súhlasu |
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | `getCkyConsent()` a `cookieyes_consent_update` |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | `cmplz_has_consent('marketing')` a `cmplz_status_change` |
| [Cookiebot](https://wordpress.org/plugins/cookiebot/) | `Cookiebot.consent.marketing` a `CookiebotOnAccept` / `CookiebotOnDecline` / `CookiebotOnConsentReady` |
| Cookie Law Info 2.x, staršia lišta | cookie `cookielawinfo-checkbox-non-necessary`, znovu prečítaná po kliknutí na lištu |
| Čokoľvek iné | funkcie voláte sami — pozri [Ručná integrácia](#ručná-integrácia) |

Na všetky sa vzťahujú tri pravidlá:

- **Súhlas sa odosiela, keď návštevník odpovie na lište, nikdy pri načítaní stránky.** Barion očakáva `grantConsent` v okamihu kliknutia a odmieta integráciu, ktorá ho odošle skôr, než sa návštevník čohokoľvek dotkol — z pohľadu Barionu to vyzerá ako obchod, ktorý sa nikdy nepýta. Plugin preto stav súhlasu pri načítaní prečíta, ale nechá si ho pre seba, a odosiela iba to, čo návštevník rozhodne pri tomto načítaní stránky.
- **Pred odpoveďou návštevníka sa neodosiela nič.** Pri načítaní stránky bez marketingového súhlasu plugin mlčí, namiesto toho aby posielal `rejectConsent`. Kým nie je lišta zodpovedaná, nie je čo hlásiť.
- **Odosielajú sa iba zmeny.** Opakovane rovnaký stav sa neodošle dvakrát, čo je dôležité, pretože jedno kliknutie môže doraziť dvoma adaptérmi súčasne.

Vracajúci sa návštevník, ktorý súhlasil pri skoršej návšteve, teda nevyvolá nič — a to je
správne: bp.js ukladá odpoveď do vlastnej cookie `BarionMarketingConsent`, Barion ju teda už má.
Práve opakované odosielanie pri každom načítaní stránky bolo tým, prečo bola integrácia
odmietnutá. Ak chcete vidieť `grantConsent` v akcii, najprv zmažte cookies, aby sa lišta
spýtala znovu.

## WP Consent API — stále odporúčané

[WP Consent API](https://wordpress.org/plugins/wp-consent-api/) je štandard WordPressu na
odovzdávanie súhlasu medzi pluginmi a Barion Pixel sa registruje v jeho kategórii `marketing`.
Je to **samostatný plugin** — nie je súčasťou WordPressu ani vašej cookie lišty.
[Návrh na začlenenie do jadra](https://make.wordpress.org/core/2024/12/04/lets-reconsider-adopting-the-wp-consent-api/)
je otvorený, ale nebol prijatý.

Nainštalujte ho, ak vaša cookie lišta nie je v tabuľke vyššie. Väčšina líšt WP Consent API
podporuje, ale len kým je tento plugin aktívny: CookieYes napríklad načíta svoj most len vtedy,
keď existuje trieda `WP_CONSENT_API`. Bez nej tieto lišty neodovzdajú nič a plugin sa musí
spoľahnúť na priame integrácie.

| Plugin | Aktívne inštalácie |
|--------|----------------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5 mil.+ |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1 mil.+ |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1 mil.+ |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300 tis.+ |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100 tis.+ |

---

## Ručná integrácia

Pre vlastných správcov súhlasu, alebo keď sa nič z vyššie uvedeného nehodí.

### Metóda 1: JavaScriptové funkcie (odporúčané)

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

### Metóda 2: Vlastné DOM udalosti

```javascript
// Grant consent
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Reject consent
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### Metóda 3: WordPress action hook

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

### Príklad: OneTrust

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

## Čo musíte zariadiť sami

Plugin súhlas odovzdáva. Nemôže napísať vaše zásady ani nastaviť vašu lištu, a Barion vyžaduje
oboje. Z [požiadaviek Barionu](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements):

- **Pridajte cookies Barionu do svojich zásad cookies.** `ba_vid`, `ba_vid.xxx`, `ba_sid` a `ba_sid.xxx` patria medzi nevyhnutné cookies — slúžia prevencii podvodov na základe oprávneného záujmu Barionu a súhlas nevyžadujú. `BarionMarketingConsent.xxx` a cookies mediálnych a reklamných partnerov patria medzi marketingové cookies a súhlas vyžadujú.
- **Spomeňte Barion Pixel vo svojich zásadách ochrany osobných údajov** a odkážte na [informácie o ochrane súkromia](https://www.barion.com/en/privacy-notice/) Barionu.
- **Umožnite zákazníkom kedykoľvek zmeniť alebo odvolať súhlas** a znovu sa ich spýtajte. Barion žiada, aby sa lišta objavila znovu najmenej raz za 13 mesiacov, a odporúča 30 dní.
- **Používajte text lišty odporúčaný Barionom**, kde sa dá. Je na stránke s požiadavkami a pokrýva zdieľanie údajov s partnermi, ktoré Barion Pixel obnáša.

---

## Ako súhlas ovplyvňuje pixel

| Stav | Základný pixel (bp.js) | pageView | Zber marketingových údajov |
|-------|--------------------|----------|--------------------------|
| Pred akoukoľvek akciou súhlasu | Načítané | Odosiela sa (prevencia podvodov) | Nie |
| Po `grantConsent` | Načítané | Odosiela sa | Áno |
| Po `rejectConsent` | Načítané | Odosiela sa (prevencia podvodov) | Nie |

---

## Testovanie

1. Zapnite **Debug Mode** v Nastavenia > Barion Pixel.
2. Otvorte konzolu prehliadača (F12).
3. Hľadajte tieto správy:

| Správa | Význam |
|---------|---------|
| `Consent manager detected: …` | Uvedení správcovia boli nájdení a zapojení |
| `No consent manager detected…` | Nič sa nenašlo — zavolajte funkcie sami |
| `Consent granted (grantConsent)` | `grantConsent` dorazil do bp.js |
| `Consent rejected (rejectConsent)` | `rejectConsent` dorazil do bp.js |

Všetky správy majú predponu `[Barion Pixel]`.

4. Otestujte na svojej lište cestu prijatia aj odmietnutia.
5. Funkcie súhlasu možno bezpečne volať opakovane.

`No consent manager detected` sa objavuje aj ako varovanie na stránke nastavení pluginu, keď
je plugin WP Consent API neaktívny, pretože práve kvôli tejto chybe býva integrácia Full Pixel
odmietnutá.

Stránka nastavení nesie druhé varovanie pre pascu, ktorá sa za tým skrýva: WP Consent API je
aktívne, ale žiadna cookie lišta sa uňho nezaregistrovala. Samo o sebe API odpovedá „udelené“
pre každého, pretože nenastavený typ súhlasu je jeho spôsob, ako povedať, že ho neriadi žiadna
lišta. Nainštalovať ho vedľa lišty, ktorá ho nepodporuje, preto nič neprepojí — len to spôsobí,
že každý návštevník vyzerá, akoby súhlasil. V tomto stave ho plugin ignoruje.
