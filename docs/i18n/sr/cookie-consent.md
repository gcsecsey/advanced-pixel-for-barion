> 🌐 Ovo je automatski prevod. Ispravke zajednice su dobrodošle!
>
> [English version](../../cookie-consent.md)

# Integracija saglasnosti za kolačiće

Ovde je merodavna Barionova sopstvena stranica:
[Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements).
Na njoj je i tekst trake za saglasnost koji Barion preporučuje, kao i aktuelan spisak Barionovih
oglašivačkih partnera. Pročitaj je pre puštanja u rad — usklađenost je odgovornost trgovca, ne
dodatka.

Barion navodi `grantConsent` i među događajima koje je
[obavezno implementirati](https://docs.barion.com/Implementing_the_Full_Barion_Pixel)
pre nego što se integracija Full Pixel odobri. Prodavnica koja ga nikada ne pošalje nema pravo
na niže naknade, koliko god ostatak integracije bio potpun.

## Šta dodatak radi

Skripta osnovnog piksela uvek se učitava i `pageView` se uvek pokreće. Barion to dokumentuje kao
legitimni interes: osnovni piksel postoji radi sprečavanja prevara pri plaćanju, a podaci
prikupljeni bez marketinške saglasnosti koriste se isključivo za to.

Povrh toga, dodatak poziva `bp('consent', 'grantConsent')` kada kupac prihvati marketinške
kolačiće i `bp('consent', 'rejectConsent')` kada ih odbije. Barion oboje navodi kao obavezno.
Tvoja traka zato mora nuditi stvarnu mogućnost odbijanja — kod trake samo sa dugmetom za
prihvatanje dodatak nema šta da signalizira.

## Kako se saglasnost prepoznaje

Dodatak ne bira jednog upravljača saglasnosti. Istovremeno se pretplaćuje na svaki signal
saglasnosti koji poznaje i prosleđuje prvi stvarni odgovor i svaku sledeću promenu. Redosled
učitavanja nije bitan: slušaoci su registrovani pre nego što ijedan upravljač saglasnosti
postoji, pa se hvata i traka koja se pojavi kasnije. Posetilac koji se vraća ne vidi traku i
zato ne pokreće nikakav događaj — zato dodatak dodatno svakih pola sekunde traži upravljača
saglasnosti dok neki ne odgovori, a odustaje deset sekundi nakon učitavanja stranice.

Ovi rade bez dodatnog dodatka:

| Upravljač saglasnosti | Čita se preko |
|---|---|
| [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) | `wp_has_consent('marketing')` i `wp_listen_for_consent_change`, ali tek kada kod njega traka registruje vrstu saglasnosti |
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | `getCkyConsent()` i `cookieyes_consent_update` |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | `cmplz_has_consent('marketing')` i `cmplz_status_change` |
| [Cookiebot](https://wordpress.org/plugins/cookiebot/) | `Cookiebot.consent.marketing` i `CookiebotOnAccept` / `CookiebotOnDecline` / `CookiebotOnConsentReady` |
| Cookie Law Info 2.x, stara traka | kolačić `cookielawinfo-checkbox-non-necessary`, ponovo pročitan nakon klika na traku |
| Bilo šta drugo | funkcije pozivaš sam — vidi [Ručna integracija](#ručna-integracija) |

Na sve se primenjuju tri pravila:

- **Saglasnost se šalje kada posetilac odgovori na traci, nikada pri učitavanju stranice.** Barion očekuje `grantConsent` u trenutku klika i odbija integraciju koja ga pošalje pre nego što je posetilac išta dotakao — sa Barionove strane to izgleda kao prodavnica koja nikada ne pita. Dodatak zato pri učitavanju pročita stanje saglasnosti, ali ga zadrži za sebe i šalje samo ono što posetilac odluči pri tom učitavanju stranice.
- **Pre posetiočevog odgovora ne šalje se ništa.** Pri učitavanju stranice bez marketinške saglasnosti dodatak ćuti umesto da šalje `rejectConsent`. Dok se na traku ne odgovori, nema se šta prijaviti.
- **Šalju se samo promene.** Ponovljeno isto stanje ne šalje se dvaput, što je važno jer jedan klik može stići kroz dva adaptera istovremeno.

Posetilac koji se vraća, a prihvatio je pri ranijoj poseti, zato ne pokreće ništa — i to je
ispravno: bp.js čuva odgovor u sopstvenom kolačiću `BarionMarketingConsent`, pa ga Barion već
ima. Upravo je ponovno slanje pri svakom učitavanju stranice bilo ono zbog čega je integracija
odbijena. Želiš li da vidiš kako se `grantConsent` pokreće, prvo obriši kolačiće da bi traka
ponovo pitala.

## WP Consent API — i dalje preporučeno

[WP Consent API](https://wordpress.org/plugins/wp-consent-api/) je WordPressov standard za
prosleđivanje saglasnosti između dodataka, a Barion Pixel se registruje u njegovoj kategoriji
`marketing`. To je **zaseban dodatak** — nije deo WordPressa ni deo tvoje trake za kolačiće.
[Predlog za uključivanje u jezgro](https://make.wordpress.org/core/2024/12/04/lets-reconsider-adopting-the-wp-consent-api/)
otvoren je, ali nije prihvaćen.

Instaliraj ga kada tvoje trake za kolačiće nema u gornjoj tabeli. Većina traka podržava WP
Consent API, ali samo dok je taj dodatak aktivan: CookieYes, na primer, učitava svoj most samo
ako postoji klasa `WP_CONSENT_API`. Bez njega te trake ne prosleđuju ništa i dodatak mora da se
osloni na direktne integracije.

| Dodatak | Aktivne instalacije |
|--------|----------------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5 mil.+ |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1 mil.+ |
| [Cookie Compliance by Hu-manity.co](https://wordpress.org/plugins/cookie-notice/) | 900 hilj.+ |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300 hilj.+ |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100 hilj.+ |

---

## Ručna integracija

Za sopstvene upravljače saglasnosti ili kada ništa od navedenog ne važi.

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

### 2. način: Sopstveni DOM događaji

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

## Šta moraš da središ sam

Dodatak prosleđuje saglasnost. Ne može da napiše tvoje politike ni da podesi tvoju traku, a
Barion zahteva oboje. Iz
[Barionovih zahteva](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements):

- **Dodaj Barionove kolačiće u svoju politiku kolačića.** `ba_vid`, `ba_vid.xxx`, `ba_sid` i `ba_sid.xxx` spadaju u neophodne kolačiće — služe sprečavanju prevara na osnovu Barionovog legitimnog interesa i ne traže saglasnost. `BarionMarketingConsent.xxx` i kolačići medijskih i oglašivačkih partnera spadaju u marketinške kolačiće i saglasnost traže.
- **Pomeni Barion Pixel u svojoj politici privatnosti** i poveži Barionovo [obaveštenje o privatnosti](https://www.barion.com/en/privacy-notice/).
- **Omogući kupcima da bilo kada promene ili povuku saglasnost** i pitaj ih ponovo. Barion traži da se traka ponovo pojavi najmanje svakih 13 meseci, a preporučuje 30 dana.
- **Koristi tekst trake koji Barion preporučuje** gde god možeš. Nalazi se na stranici sa zahtevima i pokriva deljenje podataka sa partnerima koje Barion Pixel podrazumeva.

---

## Kako saglasnost utiče na piksel

| Stanje | Osnovni piksel (bp.js) | pageView | Prikupljanje marketinških podataka |
|-------|--------------------|----------|--------------------------|
| Pre bilo kakve radnje saglasnosti | Učitan | Pokreće se (sprečavanje prevara) | Ne |
| Nakon `grantConsent` | Učitan | Pokreće se | Da |
| Nakon `rejectConsent` | Učitan | Pokreće se (sprečavanje prevara) | Ne |

---

## Testiranje

1. Uključi **Debug Mode** u Podešavanja > Barion Pixel.
2. Otvori konzolu pregledača (F12).
3. Potraži ove poruke:

| Poruka | Značenje |
|---------|---------|
| `Consent manager detected: …` | Navedeni upravljači su pronađeni i povezani |
| `No consent manager detected…` | Ništa nije pronađeno — funkcije pozovi sam |
| `Consent granted (grantConsent)` | `grantConsent` je stigao do bp.js |
| `Consent rejected (rejectConsent)` | `rejectConsent` je stigao do bp.js |

Sve poruke imaju prefiks `[Barion Pixel]`.

4. Na svojoj traci testiraj i put prihvatanja i put odbijanja.
5. Funkcije saglasnosti bezbedno je pozivati više puta.

`No consent manager detected` pojavljuje se i kao upozorenje na stranici podešavanja dodatka
kada dodatak WP Consent API nije aktivan, jer je to greška zbog koje se integracija Full Pixel
odbija.

Stranica podešavanja nosi drugo upozorenje za zamku iza toga: WP Consent API je aktivan, ali kod
njega se nije registrovala nijedna traka za kolačiće. Sam po sebi API svima odgovara „dato“, jer
nepodešena vrsta saglasnosti njegov je način da kaže da ga ne pokreće nijedna traka.
Instalirati ga uz traku koja ga ne podržava zato ništa ne povezuje — samo čini da svaki
posetilac izgleda kao da je pristao. U tom stanju dodatak ga zanemaruje.
