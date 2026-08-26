> 🌐 Dies ist eine automatische Übersetzung. Korrekturen aus der Community sind willkommen!
>
> [English version](../../cookie-consent.md)

# Cookie-Consent-Integration

Maßgeblich ist hier Barions eigene Seite:
[Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements).
Dort stehen auch der von Barion empfohlene Banner-Text und die aktuelle Liste von Barions
Werbepartnern. Lies sie vor dem Livegang — die Compliance liegt beim Händler, nicht beim Plugin.

Barion führt `grantConsent` außerdem unter den Events auf, die
[implementiert sein müssen](https://docs.barion.com/Implementing_the_Full_Barion_Pixel),
bevor eine Full-Pixel-Integration freigegeben wird. Ein Shop, der es nie sendet, hat keinen
Anspruch auf die niedrigeren Gebühren, so vollständig der Rest der Integration auch sein mag.

## Was das Plugin tut

Das Basis-Pixel-Skript wird immer geladen, und `pageView` wird immer ausgelöst. Barion
dokumentiert das als berechtigtes Interesse: Das Basis-Pixel dient der Betrugsprävention bei
Zahlungen, und ohne Marketing-Einwilligung erhobene Daten werden ausschließlich dafür genutzt.

Darüber hinaus ruft das Plugin `bp('consent', 'grantConsent')` auf, wenn die Kundin oder der
Kunde Marketing-Cookies akzeptiert, und `bp('consent', 'rejectConsent')` bei Ablehnung. Barion
führt beides als erforderlich auf. Dein Banner muss deshalb eine echte Ablehnen-Option bieten —
bei einem Banner, das nur „Akzeptieren“ kennt, hat das Plugin nichts zu melden.

## Wie die Einwilligung erkannt wird

Das Plugin wählt kein einzelnes Consent-Management aus. Es abonniert gleichzeitig jedes ihm
bekannte Einwilligungssignal und leitet die erste echte Antwort sowie jede spätere Änderung
weiter. Die Ladereihenfolge spielt keine Rolle: Die Listener werden registriert, bevor
irgendein Consent-Management existiert, sodass auch ein spät erscheinendes Banner erfasst wird.
Eine wiederkehrende Besucherin sieht kein Banner und löst daher gar kein Event aus — deshalb
sucht das Plugin zusätzlich jede halbe Sekunde nach einem Consent-Management, bis eines
antwortet, und gibt zehn Sekunden nach dem Laden der Seite auf.

Diese funktionieren ohne zusätzliches Plugin:

| Consent-Management | Ausgelesen über |
|---|---|
| [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) | `wp_has_consent('marketing')` und `wp_listen_for_consent_change`, aber erst, wenn ein Banner dort einen Consent-Typ registriert hat |
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | `getCkyConsent()` und `cookieyes_consent_update` |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | `cmplz_has_consent('marketing')` und `cmplz_status_change` |
| [Cookiebot](https://wordpress.org/plugins/cookiebot/) | `Cookiebot.consent.marketing` und `CookiebotOnAccept` / `CookiebotOnDecline` / `CookiebotOnConsentReady` |
| Cookie Law Info 2.x, altes Banner | das Cookie `cookielawinfo-checkbox-non-necessary`, nach einem Banner-Klick erneut gelesen |
| Alles andere | du rufst die Funktionen selbst auf — siehe [Manuelle Integration](#manuelle-integration) |

Für alle gelten drei Regeln:

- **Die Einwilligung wird gesendet, wenn die Besucherin das Banner beantwortet, niemals beim Laden der Seite.** Barion erwartet `grantConsent` im Moment des Klicks und lehnt eine Integration ab, die es sendet, bevor jemand irgendetwas berührt hat — aus Barions Sicht sieht das aus wie ein Shop, der nie fragt. Das Plugin liest den Einwilligungsstatus deshalb beim Laden aus, behält ihn aber für sich und sendet nur das, was die Besucherin bei diesem Seitenaufruf entscheidet.
- **Vor der Antwort wird nichts gesendet.** Bei einem Seitenaufruf ohne Marketing-Einwilligung bleibt das Plugin still, statt `rejectConsent` zu senden. Solange das Banner nicht beantwortet ist, gibt es nichts zu melden.
- **Nur Änderungen werden gesendet.** Ein wiederholt gleicher Status wird nicht zweimal gesendet, was wichtig ist, weil ein einzelner Klick über zwei Adapter gleichzeitig ankommen kann.

Eine wiederkehrende Besucherin, die bei einem früheren Besuch zugestimmt hat, löst deshalb
nichts aus — und das ist richtig so: bp.js speichert die Antwort in seinem eigenen Cookie
`BarionMarketingConsent`, Barion hat sie also bereits. Genau das erneute Senden bei jedem
Seitenaufruf hat überhaupt erst zur Ablehnung der Integration geführt. Wenn du `grantConsent`
auslösen sehen willst, lösche zuerst deine Cookies, damit das Banner wieder fragt.

## WP Consent API — weiterhin empfohlen

Die [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) ist der
WordPress-Standard, um Einwilligungen zwischen Plugins weiterzugeben, und das Barion Pixel
registriert sich unter ihrer Kategorie `marketing`. Sie ist ein **eigenes Plugin** — nicht Teil
von WordPress und nicht Teil deines Cookie-Banners. Ein
[Vorschlag zur Aufnahme in den Core](https://make.wordpress.org/core/2024/12/04/lets-reconsider-adopting-the-wp-consent-api/)
ist offen, aber nicht umgesetzt.

Installiere sie, wenn dein Cookie-Banner nicht in der Tabelle oben steht. Die meisten Banner
unterstützen die WP Consent API, aber nur solange dieses Plugin aktiv ist: CookieYes lädt seine
Brücke zum Beispiel nur, wenn die Klasse `WP_CONSENT_API` existiert. Ohne sie leiten diese
Banner nichts weiter, und das Plugin muss auf die direkten Integrationen zurückfallen.

| Plugin | Aktive Installationen |
|--------|----------------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5 Mio.+ |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1 Mio.+ |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1 Mio.+ |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300 Tsd.+ |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100 Tsd.+ |

---

## Manuelle Integration

Für eigene Consent-Management-Lösungen oder wenn nichts davon zutrifft.

### Methode 1: JavaScript-Funktionen (empfohlen)

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

### Methode 2: Eigene DOM-Events

```javascript
// Grant consent
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Reject consent
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### Methode 3: WordPress-Action-Hook

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

### Beispiel: OneTrust

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

## Was du selbst erledigen musst

Das Plugin leitet die Einwilligung weiter. Es kann weder deine Richtlinien schreiben noch dein
Banner konfigurieren, und Barion verlangt beides. Aus
[Barions Anforderungen](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements):

- **Nimm die Barion-Cookies in deine Cookie-Richtlinie auf.** `ba_vid`, `ba_vid.xxx`, `ba_sid` und `ba_sid.xxx` gehören zu den notwendigen Cookies — sie dienen der Betrugsprävention auf Basis von Barions berechtigtem Interesse und brauchen keine Einwilligung. `BarionMarketingConsent.xxx` sowie die Cookies der Medien- und Werbepartner gehören zu den Marketing-Cookies und brauchen sehr wohl eine Einwilligung.
- **Erwähne das Barion Pixel in deiner Datenschutzerklärung** und verlinke Barions [Datenschutzhinweis](https://www.barion.com/en/privacy-notice/).
- **Lass Kundinnen und Kunden ihre Einwilligung jederzeit ändern oder widerrufen** und frage erneut nach. Barion verlangt, dass das Banner mindestens alle 13 Monate erneut erscheint, und empfiehlt 30 Tage.
- **Verwende Barions empfohlenen Banner-Text**, wo es geht. Er steht auf der Anforderungsseite und deckt die Datenweitergabe an Partner ab, die das Barion Pixel mit sich bringt.

---

## Wie sich die Einwilligung auf das Pixel auswirkt

| Status | Basis-Pixel (bp.js) | pageView | Marketing-Datenerhebung |
|-------|--------------------|----------|--------------------------|
| Vor jeder Einwilligungsaktion | Geladen | Wird ausgelöst (Betrugsprävention) | Nein |
| Nach `grantConsent` | Geladen | Wird ausgelöst | Ja |
| Nach `rejectConsent` | Geladen | Wird ausgelöst (Betrugsprävention) | Nein |

---

## Testen

1. Aktiviere **Debug Mode** unter Einstellungen > Barion Pixel.
2. Öffne die Browser-Konsole (F12).
3. Achte auf diese Meldungen:

| Meldung | Bedeutung |
|---------|---------|
| `Consent manager detected: …` | Die genannten Manager wurden gefunden und eingebunden |
| `No consent manager detected…` | Nichts gefunden — ruf die Funktionen selbst auf |
| `Consent granted (grantConsent)` | `grantConsent` hat bp.js erreicht |
| `Consent rejected (rejectConsent)` | `rejectConsent` hat bp.js erreicht |

Allen Meldungen ist `[Barion Pixel]` vorangestellt.

4. Teste an deinem Banner sowohl den Zustimmungs- als auch den Ablehnungsweg.
5. Die Consent-Funktionen dürfen gefahrlos mehrfach aufgerufen werden.

`No consent manager detected` erscheint auch als Warnung auf der Einstellungsseite des
Plugins, wenn das Plugin WP Consent API inaktiv ist, denn das ist der Fehler, wegen dem eine
Full-Pixel-Integration abgelehnt wird.

Die Einstellungsseite trägt eine zweite Warnung für die Falle dahinter: die WP Consent API ist
aktiv, aber kein Cookie-Banner hat sich dort registriert. Für sich allein antwortet die API für
jeden mit „erteilt“, denn ein nicht gesetzter Consent-Typ ist ihre Art zu sagen, dass sie von
keinem Banner gesteuert wird. Sie neben einem Banner zu installieren, das sie nicht
unterstützt, verbindet deshalb gar nichts — es lässt nur jede Besucherin so aussehen, als hätte
sie zugestimmt. In diesem Zustand ignoriert das Plugin sie.
