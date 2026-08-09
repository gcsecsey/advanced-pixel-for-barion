> 🌐 Dies ist eine automatische Übersetzung. Korrekturen aus der Community sind willkommen!
>
> [English version](../../cookie-consent.md)

# Cookie-Consent-Integration

Maßgeblich ist hier Barions eigene Seite:
[Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements).
Dort stehen auch der von Barion empfohlene Banner-Text und die aktuelle Liste von Barions
Werbepartnern. Lies sie vor dem Livegang — die Compliance liegt beim Händler, nicht beim Plugin.

## Was das Plugin tut

Das Basis-Pixel-Skript wird immer geladen, und `pageView` wird immer ausgelöst. Barion
dokumentiert das als berechtigtes Interesse: das Basis-Pixel dient der Prävention von
Zahlungsbetrug, und ohne Marketing-Einwilligung erhobene Daten werden ausschließlich dafür
verwendet.

Darüber hinaus ruft das Plugin `bp('consent', 'grantConsent')` auf, wenn der Kunde
Marketing-Cookies akzeptiert, und `bp('consent', 'rejectConsent')`, wenn er ablehnt. Barion führt
beide als erforderlich. Dein Banner muss deshalb eine echte Ablehnoption bieten — bei einem
Banner, das nur Zustimmung kennt, hat das Plugin nichts zu melden.

Das Plugin sucht in dieser Reihenfolge nach einem Consent-Manager und hält beim ersten Treffer an:

1. **WP Consent API** (empfohlen) — universell, funktioniert mit allen großen Cookie-Plugins
2. **Cookie Law Info** (Fallback) — direkte Integration für CookieYes / Cookie Law Info
3. **Manuell** — für eigene Consent-Manager

---

## Stufe 1: WP Consent API (empfohlen)

Die [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) ist der WordPress-Standard,
um Einwilligungen zwischen Plugins weiterzugeben. Das Barion Pixel registriert sich in der
Kategorie `marketing`.

### Funktionsweise

Nach `DOMContentLoaded` prüft das Plugin, ob die Funktion `wp_has_consent()` existiert. Falls ja:

1. Liegt die `marketing`-Einwilligung bereits vor, wird `grantConsent` sofort ausgelöst.
2. Danach horcht das Plugin auf `wp_listen_for_consent_change` und sendet bei jeder Änderung `grantConsent` oder `rejectConsent`.

Beachte, was *nicht* in dieser Liste steht: bei einem Seitenaufruf ohne Marketing-Einwilligung
bleibt das Plugin still, statt `rejectConsent` zu senden. Solange der Kunde das Banner nicht
beantwortet hat, gibt es nichts zu melden — und die Antwort kommt über das Änderungs-Event.

### Unterstützte Cookie-Plugins

Jedes Plugin, das die WP Consent API implementiert, funktioniert automatisch:

| Plugin | Aktive Installationen | Hinweis |
|--------|-----------------------|---------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5 Mio.+ | WP Consent API integriert |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1 Mio.+ | Mitentwickler der WP Consent API |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1 Mio.+ | WP Consent API-kompatibel |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300.000+ | WP Consent API-kompatibel |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100.000+ | WP Consent API-kompatibel |

### Einrichtung

1. Installiere und aktiviere die [WP Consent API](https://wordpress.org/plugins/wp-consent-api/).
2. Installiere und konfiguriere dein Cookie-Consent-Plugin.
3. Installiere und konfiguriere Advanced Pixel for Barion.

Mehr ist nicht nötig — die Einwilligung wird automatisch verarbeitet.

---

## Stufe 2: Cookie Law Info (Fallback)

Kommt zum Zug, wenn die WP Consent API nicht verfügbar ist, wohl aber
[Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) / CookieYes.

### Funktionsweise

1. Das Plugin prüft das globale Objekt `CLI` und dessen `allowedCategories`.
2. Steht das Cookie `cookielawinfo-checkbox-non-necessary` bereits auf `yes` — ein wiederkehrender Besucher, der zugestimmt hat —, wird `grantConsent` sofort ausgelöst.
3. Klicks auf die `.cli_action_button`-Elemente des Banners werden beobachtet. Kurz nach einem Klick liest das Plugin das Cookie erneut und sendet entsprechend `grantConsent` oder `rejectConsent`.

### Einrichtung

Keine. Beide Plugins installieren, fertig.

---

## Stufe 3: Manuelle Integration

Für eigene Consent-Manager oder wenn keine der obigen Varianten greift.

### Methode 1: JavaScript-Funktionen (empfohlen)

```javascript
// Wenn der Benutzer Marketing-Cookies akzeptiert
function onMarketingConsentGranted() {
    if (typeof window.wcBarionGrantConsent === 'function') {
        window.wcBarionGrantConsent();
    }
}

// Wenn der Benutzer Marketing-Cookies ablehnt
function onMarketingConsentRejected() {
    if (typeof window.wcBarionRejectConsent === 'function') {
        window.wcBarionRejectConsent();
    }
}
```

### Methode 2: Eigene DOM-Events

```javascript
// Einwilligung erteilen
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Einwilligung ablehnen
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### Methode 3: WordPress-Action-Hook

```php
// In deinem Consent-Manager-Plugin oder Theme
add_action('wc_barion_pixel_footer_scripts', 'my_barion_consent_handler');

function my_barion_consent_handler() {
    ?>
    <script>
    // Hier deine eigene Consent-Logik
    </script>
    <?php
}
```

### Beispiele für bestimmte Consent-Manager

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

## Was du weiterhin selbst erledigen musst

Das Plugin leitet die Einwilligung weiter. Deine Richtlinien schreibt es nicht und dein Banner
konfiguriert es nicht — Barion verlangt beides. Aus
[Barions Anforderungen](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements):

- **Nimm die Barion-Cookies in deine Cookie-Richtlinie auf.** `ba_vid`, `ba_vid.xxx`, `ba_sid` und `ba_sid.xxx` gehören zu den notwendigen Cookies — sie dienen der Betrugsprävention auf Basis von Barions berechtigtem Interesse und benötigen keine Einwilligung. `BarionMarketingConsent.xxx` sowie die Cookies der Medien- und Werbepartner gehören zu den Marketing-Cookies und benötigen eine Einwilligung.
- **Erwähne das Barion Pixel in deiner Datenschutzerklärung** und verlinke Barions [Datenschutzhinweis](https://www.barion.com/de/datenschutzhinweis/).
- **Ermögliche es Kunden, ihre Einwilligung jederzeit zu ändern oder zu widerrufen**, und frage erneut nach. Barion verlangt, dass das Banner mindestens alle 13 Monate wieder erscheint, und empfiehlt 30 Tage.
- **Verwende Barions empfohlenen Banner-Text**, wo es geht. Er steht auf der Anforderungsseite und deckt die Datenweitergabe an Partner ab, die das Barion Pixel mit sich bringt.

---

## Wie sich die Einwilligung auf das Pixel auswirkt

| Zustand | Basis-Pixel (bp.js) | pageView | Marketing-Datenerhebung |
|---------|---------------------|----------|-------------------------|
| Vor jeder Consent-Entscheidung | Geladen | Wird ausgelöst (Betrugsprävention) | Nein |
| Nach `grantConsent` | Geladen | Wird ausgelöst | Ja |
| Nach `rejectConsent` | Geladen | Wird ausgelöst (Betrugsprävention) | Nein |

---

## Testen

1. Aktiviere den **Debug-Modus** unter Einstellungen > Barion Pixel.
2. Öffne die Browser-Konsole (F12).
3. Achte auf diese Meldungen:

| Meldung | Bedeutung |
|---------|-----------|
| `Consent auto-granted via WP Consent API` | Stufe 1, Einwilligung lag beim Laden bereits vor |
| `Consent granted via WP Consent API change event` | Stufe 1, Kunde hat soeben zugestimmt |
| `Consent rejected via WP Consent API change event` | Stufe 1, Kunde hat soeben abgelehnt |
| `Cookie Law Info detected, initial non-necessary cookie: …` | Stufe 2 hat übernommen, mit dem gelesenen Cookie-Wert |
| `Cookie Law Info button clicked, non-necessary cookie: …` | Stufe 2, Kunde hat das Banner benutzt |
| `No consent manager detected…` | Stufe 3 — nichts gefunden, rufe die Funktionen selbst auf |
| `Consent granted (grantConsent)` | `grantConsent` hat bp.js erreicht (jede Stufe) |
| `Consent rejected (rejectConsent)` | `rejectConsent` hat bp.js erreicht (jede Stufe) |

Alle Meldungen tragen das Präfix `[Barion Pixel]`.

4. Teste sowohl den Zustimmungs- als auch den Ablehnpfad deines Banners.
5. Die Consent-Funktionen können gefahrlos mehrfach aufgerufen werden.
