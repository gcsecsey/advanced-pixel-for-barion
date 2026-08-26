> 🌐 Ovo je automatski prijevod. Ispravci zajednice su dobrodošli!
>
> [English version](../../cookie-consent.md)

# Integracija privole za kolačiće

Ovdje je mjerodavna Barionova vlastita stranica:
[Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements).
Na njoj je i tekst trake za kolačiće koji Barion preporučuje te aktualan popis Barionovih
oglašivačkih partnera. Pročitaj je prije puštanja u rad — usklađenost je odgovornost trgovca, ne
dodatka.

Barion navodi `grantConsent` i među događajima koje je
[obavezno implementirati](https://docs.barion.com/Implementing_the_Full_Barion_Pixel)
prije nego što se integracija Full Pixel odobri. Trgovina koja ga nikada ne pošalje nema pravo
na niže naknade, koliko god ostatak integracije bio potpun.

## Što dodatak radi

Skripta osnovnog piksela uvijek se učitava i `pageView` se uvijek pokreće. Barion to
dokumentira kao legitimni interes: osnovni piksel postoji radi sprječavanja prijevara pri
plaćanju, a podaci prikupljeni bez marketinške privole koriste se isključivo za to.

Povrh toga, dodatak poziva `bp('consent', 'grantConsent')` kada kupac prihvati marketinške
kolačiće i `bp('consent', 'rejectConsent')` kada ih odbije. Barion oboje navodi kao obavezno.
Tvoja traka zato mora nuditi stvarnu mogućnost odbijanja — kod trake samo s gumbom za
prihvaćanje dodatak nema što signalizirati.

## Kako se privola prepoznaje

Dodatak ne bira jednog upravitelja privola. Istodobno se pretplaćuje na svaki signal privole
koji poznaje i prosljeđuje prvi stvarni odgovor te svaku sljedeću promjenu. Redoslijed
učitavanja nije bitan: slušatelji su registrirani prije nego što ijedan upravitelj privola
postoji, pa se hvata i traka koja se pojavi kasnije. Posjetitelj koji se vraća ne vidi traku i
stoga ne pokreće nikakav događaj — zato dodatak dodatno svakih pola sekunde traži upravitelja
privola dok neki ne odgovori, a odustaje deset sekundi nakon učitavanja stranice.

Ovi rade bez dodatnog dodatka:

| Upravitelj privola | Čita se preko |
|---|---|
| [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) | `wp_has_consent('marketing')` i `wp_listen_for_consent_change`, ali tek kada kod njega traka registrira vrstu privole |
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | `getCkyConsent()` i `cookieyes_consent_update` |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | `cmplz_has_consent('marketing')` i `cmplz_status_change` |
| [Cookiebot](https://wordpress.org/plugins/cookiebot/) | `Cookiebot.consent.marketing` i `CookiebotOnAccept` / `CookiebotOnDecline` / `CookiebotOnConsentReady` |
| Cookie Law Info 2.x, stara traka | kolačić `cookielawinfo-checkbox-non-necessary`, ponovno pročitan nakon klika na traku |
| Bilo što drugo | funkcije pozivaš sam — vidi [Ručna integracija](#ručna-integracija) |

Na sve se primjenjuju tri pravila:

- **Privola se šalje kada posjetitelj odgovori na traci, nikada pri učitavanju stranice.** Barion očekuje `grantConsent` u trenutku klika i odbija integraciju koja ga pošalje prije nego što je posjetitelj išta dotaknuo — s Barionove strane to izgleda kao trgovina koja nikada ne pita. Dodatak zato pri učitavanju pročita stanje privole, ali ga zadrži za sebe i šalje samo ono što posjetitelj odluči pri tom učitavanju stranice.
- **Prije posjetiteljeva odgovora ne šalje se ništa.** Pri učitavanju stranice bez marketinške privole dodatak šuti umjesto da šalje `rejectConsent`. Dok se na traku ne odgovori, nema se što prijaviti.
- **Šalju se samo promjene.** Ponovljeno isto stanje ne šalje se dvaput, što je važno jer jedan klik može stići kroz dva adaptera istodobno.

Posjetitelj koji se vraća, a prihvatio je pri ranijem posjetu, stoga ne pokreće ništa — i to je
ispravno: bp.js sprema odgovor u vlastiti kolačić `BarionMarketingConsent`, pa ga Barion već
ima. Upravo je ponovno slanje pri svakom učitavanju stranice bilo ono zbog čega je integracija
odbijena. Želiš li vidjeti kako se `grantConsent` pokreće, prvo obriši kolačiće da traka
ponovno pita.

## WP Consent API — i dalje preporučeno

[WP Consent API](https://wordpress.org/plugins/wp-consent-api/) je WordPressov standard za
prosljeđivanje privole među dodacima, a Barion Pixel se registrira u njegovoj kategoriji
`marketing`. To je **zaseban dodatak** — nije dio WordPressa ni dio tvoje trake za kolačiće.
[Prijedlog za uključivanje u jezgru](https://make.wordpress.org/core/2024/12/04/lets-reconsider-adopting-the-wp-consent-api/)
otvoren je, ali nije prihvaćen.

Instaliraj ga kada tvoje trake za kolačiće nema u gornjoj tablici. Većina traka podržava WP
Consent API, ali samo dok je taj dodatak aktivan: CookieYes, primjerice, učitava svoj most samo
ako postoji klasa `WP_CONSENT_API`. Bez njega te trake ne prosljeđuju ništa i dodatak se mora
osloniti na izravne integracije.

| Dodatak | Aktivne instalacije |
|--------|----------------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5 mil.+ |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1 mil.+ |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1 mil.+ |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300 tis.+ |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100 tis.+ |

---

## Ručna integracija

Za vlastite upravitelje privola ili kada ništa od navedenog ne vrijedi.

### 1. način: JavaScript funkcije (preporučeno)

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

### 2. način: Vlastiti DOM događaji

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

### Primjer: OneTrust

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

## Što moraš srediti sam

Dodatak prosljeđuje privolu. Ne može napisati tvoje politike ni podesiti tvoju traku, a Barion
zahtijeva oboje. Iz
[Barionovih zahtjeva](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements):

- **Dodaj Barionove kolačiće u svoju politiku kolačića.** `ba_vid`, `ba_vid.xxx`, `ba_sid` i `ba_sid.xxx` spadaju među nužne kolačiće — služe sprječavanju prijevara na temelju Barionova legitimnog interesa i ne traže privolu. `BarionMarketingConsent.xxx` te kolačići medijskih i oglašivačkih partnera spadaju među marketinške kolačiće i privolu traže.
- **Spomeni Barion Pixel u svojoj politici privatnosti** i poveži Barionovu [obavijest o privatnosti](https://www.barion.com/en/privacy-notice/).
- **Omogući kupcima da bilo kada promijene ili povuku privolu** i pitaj ih ponovno. Barion traži da se traka ponovno pojavi barem svakih 13 mjeseci, a preporučuje 30 dana.
- **Koristi tekst trake koji Barion preporučuje** gdje god možeš. Nalazi se na stranici sa zahtjevima i pokriva dijeljenje podataka s partnerima koje Barion Pixel podrazumijeva.

---

## Kako privola utječe na piksel

| Stanje | Osnovni piksel (bp.js) | pageView | Prikupljanje marketinških podataka |
|-------|--------------------|----------|--------------------------|
| Prije bilo kakve radnje privole | Učitan | Pokreće se (sprječavanje prijevara) | Ne |
| Nakon `grantConsent` | Učitan | Pokreće se | Da |
| Nakon `rejectConsent` | Učitan | Pokreće se (sprječavanje prijevara) | Ne |

---

## Testiranje

1. Uključi **Debug Mode** u Postavke > Barion Pixel.
2. Otvori konzolu preglednika (F12).
3. Potraži ove poruke:

| Poruka | Značenje |
|---------|---------|
| `Consent manager detected: …` | Navedeni upravitelji pronađeni su i povezani |
| `No consent manager detected…` | Ništa nije pronađeno — funkcije pozovi sam |
| `Consent granted (grantConsent)` | `grantConsent` je stigao do bp.js |
| `Consent rejected (rejectConsent)` | `rejectConsent` je stigao do bp.js |

Sve poruke imaju prefiks `[Barion Pixel]`.

4. Na svojoj traci testiraj i put prihvaćanja i put odbijanja.
5. Funkcije privole sigurno je pozivati više puta.

`No consent manager detected` pojavljuje se i kao upozorenje na stranici postavki dodatka kada
dodatak WP Consent API nije aktivan, jer je to greška zbog koje se integracija Full Pixel
odbija.

Stranica postavki nosi drugo upozorenje za zamku iza toga: WP Consent API je aktivan, ali kod
njega se nije registrirala nijedna traka za kolačiće. Sam po sebi API svima odgovara „dano“, jer
nepostavljena vrsta privole njegov je način da kaže da ga ne pokreće nijedna traka.
Instalirati ga uz traku koja ga ne podržava zato ništa ne povezuje — samo čini da svaki
posjetitelj izgleda kao da je pristao. U tom stanju dodatak ga zanemaruje.
