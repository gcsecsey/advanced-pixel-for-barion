> 🌐 Dies ist eine automatische Übersetzung. Korrekturen aus der Community sind willkommen!
>
> [English version](../../cookie-consent.md)

# Cookie-Consent-Integration

## Übersicht

Das Barion Pixel erfordert die ausdrückliche Einwilligung des Nutzers, bevor Marketing-Daten erfasst werden (DSGVO-Konformität). Das Plugin muss `bp('consent', 'grantConsent')` aufrufen, wenn der Nutzer zustimmt, und `bp('consent', 'rejectConsent')`, wenn der Nutzer ablehnt. Beide Events sind gemäß den Barion-Anforderungen obligatorisch.

Das Basis-Pixel-Skript wird immer zur Betrugsprävention geladen, aber es werden keine Marketing-Daten erfasst, bis die Einwilligung ausdrücklich erteilt oder verweigert wurde.

**Wichtig:** Dein Cookie-Banner muss sowohl eine Zustimmungs- als auch eine Ablehnungsoption anbieten. Eine „Cookie-Mauer" (nur Zustimmung) ist seit 2020 nicht DSGVO-konform und wird von Barion abgelehnt.

Das Plugin unterstützt vier Stufen der Consent-Integration, die der Reihe nach geprüft werden:

1. **Aufgezeichneter Trigger** — ein Cookie-Signal, das vom Einrichtungsassistenten erfasst wurde;
   er gewinnt nur, wenn sowohl das Zustimmungs- als auch das Ablehnungssignal aufgezeichnet sind,
   weil der Shop-Betreiber ihn bewusst festgelegt hat. Ein halb erlernter Trigger — nur ein Signal
   aufgezeichnet — wird vollständig ignoriert, und das Plugin fällt auf die nächste Stufe zurück,
   weil Barion sowohl `grantConsent` als auch `rejectConsent` benötigt.
2. **WP Consent API** (empfohlen) — universell, funktioniert mit allen wichtigen Cookie-Plugins
3. **Cookie Law Info** (Fallback) — direkte Integration für Websites, die CookieYes/Cookie Law Info verwenden
4. **Manuell** — für benutzerdefinierte Consent-Manager oder Sonderfälle

---

## Das Health Panel

Einstellungen › Barion Pixel öffnet sich mit einem Health Panel. Es führt alle unten aufgeführten
Prüfungen aus und zeigt zuerst das schlechteste Ergebnis. Wenn alles besteht, klappt es sich zu
einer einzigen grünen Zeile zusammen.

Die wichtigste Prüfung ist **„No cookie banner plugin sets a consent type"** (Kein
Cookie-Banner-Plugin setzt einen Consent-Typ). Die WP Consent API meldet Zustimmung für jede
Kategorie, wenn nichts einen Consent-Typ setzt:

> If there's no consent management plugin to set it, it will return `false`. This will cause all
> consent categories to return `true`.

Eine Website mit aktiver WP Consent API, aber ohne Cookie-Banner erteilt daher jedem Besucher die
Barion-Einwilligung, ohne dass irgendein Consent tatsächlich eingeholt wurde. Das verstößt gegen die
DSGVO und die Barion-Bedingungen.

Manche Banner setzen den Consent-Typ nur im Browser, daher meldet das Panel zunächst eine Warnung
und bietet eine Schaltfläche **Check in browser** an. Diese Prüfung liest die tatsächlichen Werte
von deinem Frontend, bevor überhaupt eine Interaktion stattfindet, und färbt die Zeile
entsprechend rot oder grün.

### Die Barion-Cookies

`bp.js` setzt drei First-Party-Cookies auf deiner eigenen Domain. Jedem Namen wird zur Laufzeit ein
Hash deiner Domain angehängt.

| Cookie | Dauer | Zweck |
|--------|-------|-------|
| `ba_sid` | 30 Minuten | Fasst Seitenaufrufe zu einer Sitzung zusammen. Wird von Barion zur Betrugsprävention genutzt. |
| `ba_vid` | 1,5 Jahre | Identifiziert einen wiederkehrenden Besucher für Marketing-Analysen. |
| `BarionMarketingConsent` | 1,5 Jahre, wird beim Ablehnen des Besuchers entfernt | Speichert die Consent-Entscheidung. |

Bei aktivem WP-Consent-API-Plugin deklariert das Plugin alle drei automatisch, sodass sie in deiner
Cookie-Richtlinie erscheinen. Ohne das Plugin musst du sie von Hand hinzufügen.

## Der Einrichtungsassistent

Wenn keine Consent-Quelle funktioniert, bietet das Panel **Set up consent** an. Der Assistent
öffnet deinen Shop in einem neuen Tab, du stimmst dort in deinem eigenen Banner zu, und das Plugin
zeichnet auf, welches Cookie sich geändert hat. Das Gleiche wiederholst du für die Ablehnung. Barion
benötigt sowohl `grantConsent` als auch `rejectConsent`, daher verweigert der Assistent das
Speichern, bis beide vorliegen.

Der Assistent speichert einen Cookie-Namen, die akzeptierten und abgelehnten Werte sowie bis zu
fünf Event-Namen. Er speichert oder führt niemals von dir bereitgestelltes JavaScript aus. Der
Recorder lädt nur für einen angemeldeten Administrator, der mit einem gültigen Nonce ankommt; für
einen Besucher lädt er nie.

### Warum die Consent-Kategorie fest ist

Das Plugin fragt immer die Kategorie `marketing` ab und bietet keine Wahlmöglichkeit. Die WP
Consent API definiert fünf feste Kategorien, und Cookie-Banner-Plugins bilden ihre eigenen
Kategorien im Code auf diese ab. CookieYes ordnet Advertisement der Kategorie marketing zu,
Analytics der Kategorie statistics, Functional der Kategorie preferences und Performance der
Kategorie functional. Diese Zuordnung lässt sich nicht ändern.

Barion benötigt eine Einwilligung für Marketingzwecke, daher ist `marketing` die einzig korrekte
Kategorie. Eine Auswahlmöglichkeit würde es erlauben, Barion über eine Statistik-Checkbox
auszulösen, was gegen die Barion-Bedingungen verstößt.

---

## Stufe 2: WP Consent API (Empfohlen)

Die [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) ist ein WordPress-Standard für die Consent-Kommunikation. Sie wird von allen wichtigen Cookie-Consent-Plugins unterstützt.

### Funktionsweise

Das Plugin prüft zur Laufzeit, ob die JavaScript-Funktion `wp_has_consent()` verfügbar ist. Wenn die WP Consent API verfügbar ist:

1. Beim Seitenaufruf wird geprüft, ob die `marketing`-Einwilligung erteilt oder verweigert wurde
2. `bp('consent', 'grantConsent')` wird aufgerufen, wenn die Marketing-Einwilligung erteilt wurde
3. `bp('consent', 'rejectConsent')` wird aufgerufen, wenn die Marketing-Einwilligung nicht erteilt wurde
4. Lauscht auf das `wp_listen_for_consent_change`-Event für Echtzeit-Consent-Aktualisierungen — erteilt oder verweigert entsprechend

### Unterstützte Cookie-Plugins

Jedes Plugin, das die WP Consent API implementiert, funktioniert automatisch:

| Plugin | Aktive Installationen | Hinweise |
|--------|----------------------|----------|
| [CookieYes](https://wordpress.org/plugins/cookie-law-info/) | 1,5 Mio.+ | WP Consent API integriert |
| [Complianz](https://wordpress.org/plugins/complianz-gdpr/) | 1 Mio.+ | Mitentwickler der WP Consent API |
| [Cookie Notice by dFactory](https://wordpress.org/plugins/cookie-notice/) | 1 Mio.+ | WP Consent API kompatibel |
| [GDPR Cookie Compliance (Moove)](https://wordpress.org/plugins/gdpr-cookie-compliance/) | 300.000+ | WP Consent API kompatibel |
| [Real Cookie Banner](https://wordpress.org/plugins/real-cookie-banner/) | 100.000+ | WP Consent API kompatibel |

### Einrichtung

1. Installiere und aktiviere das Plugin [WP Consent API](https://wordpress.org/plugins/wp-consent-api/)
2. Installiere und konfiguriere dein bevorzugtes Cookie-Consent-Plugin (siehe Tabelle oben)
3. Installiere und konfiguriere Advanced Pixel for Barion
4. Keine weitere Konfiguration erforderlich — die Einwilligung wird automatisch verarbeitet

### Consent-Kategorie

Das Barion Pixel ist in der WP Consent API unter der Consent-Kategorie `marketing` registriert. Dies ist die Standardkategorie für Tracking-Pixel, die für Retargeting und Analysen verwendet werden.

---

## Stufe 3: Cookie Law Info (Fallback)

Wenn die WP Consent API nicht verfügbar ist, greift das Plugin auf die direkte Integration mit dem Plugin [Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) / CookieYes zurück.

### Funktionsweise

1. Prüft, ob das globale JavaScript-Objekt `CLI` vorhanden ist
2. Wenn Cookies bereits akzeptiert wurden (wiederkehrender Besucher), wird die Einwilligung sofort erteilt
3. Wenn Cookies nicht akzeptiert wurden, wird die Einwilligung sofort verweigert
4. Lauscht auf das `cli_user_preference_set`-Event, wenn der Nutzer mit dem Cookie-Banner interagiert
5. Erteilt oder verweigert basierend auf dem Cookie-Wert `cookielawinfo-checkbox-necessary`

### Einrichtung

Keine Konfiguration erforderlich. Installiere beide Plugins und die Integration funktioniert automatisch.

---

## Stufe 4: Manuelle Integration

Für benutzerdefinierte Consent-Manager oder Umgebungen, in denen weder die WP Consent API noch Cookie Law Info verfügbar ist.

### Methode 1: JavaScript-Funktionen (empfohlen)

```javascript
// Wenn der Nutzer Marketing-Cookies akzeptiert
function onMarketingConsentGranted() {
    if (typeof window.wcBarionGrantConsent === 'function') {
        window.wcBarionGrantConsent();
    }
}

// Wenn der Nutzer Marketing-Cookies ablehnt
function onMarketingConsentRejected() {
    if (typeof window.wcBarionRejectConsent === 'function') {
        window.wcBarionRejectConsent();
    }
}
```

### Methode 2: Benutzerdefinierte DOM-Events

```javascript
// Einwilligung erteilen
document.dispatchEvent(new Event('wcBarionGrantConsent'));

// Einwilligung verweigern
document.dispatchEvent(new Event('wcBarionRejectConsent'));
```

### Methode 3: WordPress-Action-Hook

```php
// In deinem Consent-Manager-Plugin oder Theme
add_action('wc_barion_pixel_footer_scripts', 'my_barion_consent_handler');

function my_barion_consent_handler() {
    ?>
    <script>
    // Deine benutzerdefinierte Consent-Logik hier
    </script>
    <?php
}
```

### Beispiele für spezifische Consent-Manager

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

## Auswirkung der Einwilligung auf das Pixel

| Zustand | Basis-Pixel (bp.js) | pageView | Marketing-Datenerfassung |
|---------|---------------------|----------|--------------------------|
| Vor jeder Consent-Aktion | Geladen | Wird ausgelöst (Betrugsprävention) | Keine Daten erfasst |
| Nach `grantConsent` | Geladen | Wird ausgelöst | Vollständige Datenerfassung aktiviert |
| Nach `rejectConsent` | Geladen | Wird ausgelöst (Betrugsprävention) | Keine Marketing-Daten erfasst |

Das Basis-Pixel wird immer für Barions Betrugsprävention geladen. Die Aufrufe `grantConsent` / `rejectConsent` steuern, ob Marketing-Daten erfasst werden.

---

## Testen

1. Aktiviere den **Debug-Modus** unter Einstellungen > Barion Pixel
2. Öffne die Browser-Konsole (F12)
3. Achte auf consent-bezogene Protokollmeldungen:
   - `[Barion Pixel] Consent granted via the recorded cookie trigger` — Stufe 1, zugestimmt
   - `[Barion Pixel] Consent rejected via the recorded cookie trigger` — Stufe 1, abgelehnt
   - `[Barion Pixel] Consent auto-granted via WP Consent API` — Stufe 2, Nutzer hat zugestimmt
   - `[Barion Pixel] Consent auto-rejected via WP Consent API` — Stufe 2, Nutzer hat abgelehnt
   - `[Barion Pixel] Consent auto-granted via Cookie Law Info` — Stufe 3, Nutzer hat zugestimmt
   - `[Barion Pixel] Consent auto-rejected via Cookie Law Info` — Stufe 3, Nutzer hat abgelehnt
   - `[Barion Pixel] No consent manager detected...` — Stufe 4 (manueller Modus)
   - `[Barion Pixel] Consent granted (grantConsent)` — Einwilligung wurde erteilt (beliebige Stufe)
   - `[Barion Pixel] Consent rejected (rejectConsent)` — Einwilligung wurde verweigert (beliebige Stufe)
4. Teste sowohl den Zustimmungs- als auch den Ablehnungsablauf an deinem Cookie-Banner
5. Die Consent-Funktionen können mehrfach aufgerufen werden (idempotent)
