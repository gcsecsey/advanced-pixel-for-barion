> 🌐 To je samodejni prevod. Popravki skupnosti so dobrodošli!
>
> [English version](../../cookie-consent.md)

# Integracija soglasja za piškotke

Merodajna je tu Barionova lastna stran:
[Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements).
Na njej je tudi besedilo pasice za piškotke, ki ga Barion priporoča, in aktualen seznam
Barionovih oglaševalskih partnerjev. Preberi jo pred zagonom v živo — skladnost je odgovornost
trgovca, ne vtičnika.

Barion navaja `grantConsent` tudi med dogodki, ki jih je
[treba implementirati](https://docs.barion.com/Implementing_the_Full_Barion_Pixel),
preden je integracija Full Pixel odobrena. Trgovina, ki ga nikoli ne pošlje, ni upravičena do
nižjih provizij, ne glede na to, kako popoln je preostanek integracije.

## Kaj vtičnik dela

Skripta osnovnega piksla se vedno naloži in `pageView` se vedno sproži. Barion to dokumentira
kot zakoniti interes: osnovni piksel obstaja zaradi preprečevanja plačilnih goljufij, podatki,
zbrani brez trženjskega soglasja, pa se uporabljajo samo za to.

Poleg tega vtičnik pokliče `bp('consent', 'grantConsent')`, ko kupec sprejme trženjske piškotke,
in `bp('consent', 'rejectConsent')`, ko jih zavrne. Barion navaja oboje kot obvezno. Tvoja
pasica mora zato ponujati resnično možnost zavrnitve — pri pasici samo z gumbom za sprejem
vtičnik nima česa sporočiti.

## Kako se soglasje zazna

Vtičnik ne izbere enega upravitelja soglasij. Hkrati se naroči na vsak signal soglasja, ki ga
pozna, in posreduje prvi resnični odgovor ter vsako naslednjo spremembo. Vrstni red nalaganja
ni pomemben: poslušalci so registrirani, preden kateri koli upravitelj soglasij obstaja, tako da
je zajeta tudi pasica, ki se pojavi pozneje. Vračajoči se obiskovalec ne vidi pasice in zato ne
sproži nobenega dogodka — zato vtičnik vsake pol sekunde tudi išče upravitelja soglasij, dokler
kateri ne odgovori, in odneha deset sekund po nalaganju strani.

Ti delujejo brez dodatnega vtičnika:

| Upravitelj soglasij | Bere se prek |
|---|---|
| [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) | `wp_has_consent('marketing')` in `wp_listen_for_consent_change`, vendar šele, ko pri njem pasica registrira vrsto soglasja |
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | `getCkyConsent()` in `cookieyes_consent_update` |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | `cmplz_has_consent('marketing')` in `cmplz_status_change` |
| [Cookiebot](https://wordpress.org/plugins/cookiebot/) | `Cookiebot.consent.marketing` in `CookiebotOnAccept` / `CookiebotOnDecline` / `CookiebotOnConsentReady` |
| Cookie Law Info 2.x, stara pasica | piškotek `cookielawinfo-checkbox-non-necessary`, znova prebran po kliku na pasico |
| Karkoli drugega | funkcije pokličeš sam — glej [Ročna integracija](#ročna-integracija) |

Za vse veljajo tri pravila:

- **Soglasje se pošlje, ko obiskovalec odgovori na pasici, nikoli ob nalaganju strani.** Barion pričakuje `grantConsent` v trenutku klika in zavrne integracijo, ki ga pošlje, preden se je obiskovalec česa dotaknil — z Barionove strani je to videti kot trgovina, ki nikoli ne vpraša. Vtičnik zato stanje soglasja ob nalaganju prebere, a ga obdrži zase, in pošlje samo tisto, kar obiskovalec odloči ob tem nalaganju strani.
- **Pred obiskovalčevim odgovorom se ne pošlje nič.** Ob nalaganju strani brez trženjskega soglasja vtičnik molči, namesto da bi pošiljal `rejectConsent`. Dokler pasica ni odgovorjena, ni česa sporočiti.
- **Pošiljajo se samo spremembe.** Ponovljeno enako stanje se ne pošlje dvakrat, kar je pomembno, ker lahko en klik prispe hkrati po dveh adapterjih.

Vračajoči se obiskovalec, ki je sprejel ob prejšnjem obisku, zato ne sproži ničesar — in to je
pravilno: bp.js shrani odgovor v svoj piškotek `BarionMarketingConsent`, Barion ga torej že ima.
Prav ponovno pošiljanje ob vsakem nalaganju strani je bilo tisto, zaradi česar je bila
integracija zavrnjena. Če želiš videti `grantConsent` v akciji, najprej pobriši piškotke, da
pasica znova vpraša.

## WP Consent API — še vedno priporočeno

[WP Consent API](https://wordpress.org/plugins/wp-consent-api/) je standard WordPressa za
posredovanje soglasja med vtičniki, Barion Pixel pa se registrira v njegovi kategoriji
`marketing`. To je **ločen vtičnik** — ni del WordPressa in ni del tvoje pasice za piškotke.
[Predlog za vključitev v jedro](https://make.wordpress.org/core/2024/12/04/lets-reconsider-adopting-the-wp-consent-api/)
je odprt, a ni sprejet.

Namesti ga, kadar tvoje pasice za piškotke ni v zgornji tabeli. Večina pasic podpira WP Consent
API, a le dokler je ta vtičnik aktiven: CookieYes na primer naloži svoj most samo, če obstaja
razred `WP_CONSENT_API`. Brez njega te pasice ne posredujejo ničesar in vtičnik se mora opreti
na neposredne integracije.

| Vtičnik | Aktivne namestitve |
|--------|----------------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5 mio.+ |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1 mio.+ |
| [Cookie Compliance by Hu-manity.co](https://wordpress.org/plugins/cookie-notice/) | 900 tis.+ |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300 tis.+ |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100 tis.+ |

---

## Ročna integracija

Za lastne upravitelje soglasij ali kadar nič od zgornjega ne velja.

### 1. način: JavaScript funkcije (priporočeno)

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

### 2. način: Lastni DOM dogodki

```javascript
// Grant consent
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Reject consent
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### 3. način: WordPress action hook

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

### Primer: OneTrust

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

Vtičnik posreduje soglasje. Ne more napisati tvojih pravilnikov niti nastaviti tvoje pasice,
Barion pa zahteva oboje. Iz
[Barionovih zahtev](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements):

- **Dodaj Barionove piškotke v svoj pravilnik o piškotkih.** `ba_vid`, `ba_vid.xxx`, `ba_sid` in `ba_sid.xxx` sodijo med nujne piškotke — služijo preprečevanju goljufij na podlagi Barionovega zakonitega interesa in ne potrebujejo soglasja. `BarionMarketingConsent.xxx` ter piškotki medijskih in oglaševalskih partnerjev sodijo med trženjske piškotke in soglasje potrebujejo.
- **Omeni Barion Pixel v svojem pravilniku o zasebnosti** in poveži Barionovo [obvestilo o zasebnosti](https://www.barion.com/en/privacy-notice/).
- **Omogoči kupcem, da kadar koli spremenijo ali prekličejo soglasje**, in jih znova vprašaj. Barion zahteva, da se pasica znova pojavi vsaj vsakih 13 mesecev, priporoča pa 30 dni.
- **Uporabi besedilo pasice, ki ga priporoča Barion**, kjer je le mogoče. Je na strani z zahtevami in pokriva deljenje podatkov s partnerji, ki ga Barion Pixel prinaša.

---

## Kako soglasje vpliva na piksel

| Stanje | Osnovni piksel (bp.js) | pageView | Zbiranje trženjskih podatkov |
|-------|--------------------|----------|--------------------------|
| Pred kakršnim koli dejanjem soglasja | Naložen | Se sproži (preprečevanje goljufij) | Ne |
| Po `grantConsent` | Naložen | Se sproži | Da |
| Po `rejectConsent` | Naložen | Se sproži (preprečevanje goljufij) | Ne |

---

## Testiranje

1. Vklopi **Debug Mode** v Nastavitve > Barion Pixel.
2. Odpri konzolo brskalnika (F12).
3. Poišči ta sporočila:

| Sporočilo | Pomen |
|---------|---------|
| `Consent manager detected: …` | Navedeni upravitelji so bili najdeni in povezani |
| `No consent manager detected…` | Nič ni bilo najdeno — funkcije pokliči sam |
| `Consent granted (grantConsent)` | `grantConsent` je prišel do bp.js |
| `Consent rejected (rejectConsent)` | `rejectConsent` je prišel do bp.js |

Vsa sporočila imajo predpono `[Barion Pixel]`.

4. Na svoji pasici preizkusi pot sprejema in pot zavrnitve.
5. Funkcije soglasja je varno klicati večkrat.

`No consent manager detected` se kot opozorilo pojavi tudi na strani z nastavitvami vtičnika,
kadar vtičnik WP Consent API ni aktiven, saj je to napaka, zaradi katere je integracija Full
Pixel zavrnjena.

Stran z nastavitvami nosi drugo opozorilo za past za tem: WP Consent API je aktiven, a pri njem
ni registrirana nobena pasica za piškotke. Sam po sebi API vsem odgovarja „dano“, ker je
nenastavljena vrsta soglasja njegov način, da pove, da ga ne poganja nobena pasica. Namestitev
poleg pasice, ki ga ne podpira, zato ničesar ne poveže — doseže samo to, da je videti, kot da je
vsak obiskovalec soglašal. V tem stanju ga vtičnik prezre.
