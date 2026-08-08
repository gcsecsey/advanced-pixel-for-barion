> 🌐 Dies ist eine automatische Übersetzung. Korrekturen aus der Community sind willkommen!
>
> [English version](../../testing-notes.md)

# Testhinweise & Bekannte Eigenheiten

## Bevor du das Pixel für defekt hältst

### „Testing message“ ist kein Fehler

Öffne die Konsole auf einer Seite mit dem Pixel, und bp.js meldet entweder **„Testing message“**
oder **„Sending message“**. Barion
[dokumentiert den Unterschied](https://docs.barion.com/Implementing_the_Base_Barion_Pixel): ein
frisch eingebautes Pixel ist noch nicht berechtigt, Nutzerdaten zu senden, deshalb schreibt bp.js
„Testing message“ und überträgt nur den Event-Typ. Sobald Barion das Pixel freigibt, wechselt das
auf „Sending message“.

Daran ändert das Plugin nichts. Wenn deine Events in der Konsole korrekt aussehen, Barion aber
keine Daten sieht, wartet das Pixel höchstwahrscheinlich noch auf die Freigabe bei Barion — ein
Mensch prüft die Implementierung, wende dich also an Barion, sobald deine fertig ist.

### Es muss die richtige Pixel-ID sein

- Du findest sie in deiner Barion-Wallet unter **Merchant Management > Details**. Jeder Shop, also jeder POSKey, hat seine eigene Pixel-ID.
- Das Format ist `BP-` + zehn Zeichen + `-` + zwei Ziffern. Eine ID, die mit `BPT` beginnt, ist keine Pixel-ID und funktioniert nicht.
- Sandbox und Live vergeben **unterschiedliche** Pixel-IDs. Eine Staging-Seite mit Live-ID verschmutzt echte Daten; eine Live-Seite mit Sandbox-ID zeichnet nichts Brauchbares auf.

Wenn du einen Wegwerf-Shop zum Testen willst: Barions
[Creating a shop](https://docs.barion.com/Creating_a_shop) führt durch die Sandbox, wo Shops
automatisch freigegeben werden.

---

## Laufzeit-Validierungseigenheiten von bp.js

bp.js validiert Event-Daten im Browser, und an einigen Stellen sind seine Regeln strenger oder
lockerer, als die [Event-Referenz](https://docs.barion.com/Barion-pixel-event-reference) vermuten
lässt. Diese Punkte sind beim Testen auf Staging aufgefallen.

### totalItemPrice: bei contentView abgelehnt, in contents-Einträgen erforderlich

- **contentView** (ein flaches Event): bp.js **lehnt** `totalItemPrice` mit `Invalid key totalItemPrice in contentView event` ab. Die Referenz stimmt zu — es ist keine contentView-Eigenschaft.
- **initiateCheckout**- und **purchase**-`contents`-Einträge: bp.js **verlangt** es und meldet sonst `Mandatory key totalItemPrice is missing from contents event`. Auch hier stimmt die Referenz zu.

Faustregel: `totalItemPrice` ist bei flachen Events ungültig und in `contents`-Einträgen
erforderlich.

### unit ist in contents-Einträgen erforderlich

Fehlt es, kommt `Mandatory key unit is missing from contents event`.

### step

Das Plugin sendet `step: 1` für `addToCart`, `initiateCheckout` und `purchase`. Barion
dokumentiert `1` als den Schritt des Kassenbeginns und erwartet bei `purchase` die höchste von dir
verwendete Schrittnummer — bei einer einstufigen Kasse ebenfalls `1`. Für `addToCart` ist `step`
optional.

---

## Debug-Modus

Aktiviere ihn unter **Einstellungen > Barion Pixel**, um jedes Event in der Browser-Konsole zu
protokollieren.

### Worauf du achten solltest

Öffne die Konsole (F12 > Konsole) und suche nach `[Barion Pixel]`-Meldungen:

```
[Barion Pixel] bp.js loaded by Advanced Pixel for Barion
[Barion Pixel] Base pixel initialized with ID: BP-xxxxxxxxxx-xx
[Barion Pixel] Consent auto-granted via WP Consent API
[Barion Pixel] Block surfaces detected (cart store: true, product buttons: false)
[Barion Pixel] Event: contentView { contentType: "Product", ... }
[Barion Pixel] Event: addToCart { contentType: "Product", ... }
[Barion Pixel] Event: initiateCheckout { contents: [...], ... }
[Barion Pixel] Event: purchase { contents: [...], ... }
[Barion Pixel] setEncryptedEmail sent
```

Die Consent-Meldungen sind vollständig in der
[Cookie-Consent-Integration](cookie-consent.md) aufgeführt.

### bp.js-Fehler

bp.js protokolliert auch eigene Validierungsfehler. Die häufigsten:

| Fehler | Bedeutung | Lösung |
|--------|-----------|--------|
| `Mandatory key X is missing from Y event` | Ein erforderliches Feld wird nicht gesendet | Event-Daten prüfen |
| `Invalid key X in Y event` | Ein Feld wird gesendet, das bp.js nicht erwartet | Feld entfernen |
| `Format of e-mail address or hash is invalid` | bp.js hat den Wert für `setEncryptedEmail` abgelehnt | Seit 1.0.3 hasht das Plugin die Adresse vorab, das sollte also nicht mehr auftreten |

---

## Test-Checkliste

Führe sie sowohl in einem klassischen als auch in einem Block-Shop aus — beide nutzen für
`addToCart`, `initiateCheckout` und `setEncryptedEmail` völlig unterschiedliche Codepfade.

### Produktseite (contentView)

1. Öffne eine Produktseite mit geöffneter Konsole.
2. `[Barion Pixel] Event: contentView` erscheint.
3. Keine bp.js-Fehler zu fehlenden oder ungültigen Schlüsseln.
4. Vorhandene Felder: `contentType`, `currency`, `id`, `name`, `quantity`, `unit`, `unitPrice` — und kein `totalItemPrice`.

### In den Warenkorb (addToCart)

**Shop- oder Archivseite, klassischer AJAX-Button:**

1. Klicke auf der Shop-Seite auf „In den Warenkorb“.
2. `[Barion Pixel] Event: addToCart` erscheint, mit `totalItemPrice` und `step: 1`.

**Produktseite, Formular-Submit:**

1. Klicke auf „In den Warenkorb“ und prüfe, ob das Event vor dem Seitenwechsel ausgelöst wird.
2. Bei einem variablen Produkt: erst eine Variante wählen, dann prüfen, ob deren Preis verwendet wurde.

**Block-Oberflächen (Product-Collection-Buttons, Cart-Block):**

1. `[Barion Pixel] Block surfaces detected …` erscheint beim Laden.
2. Füge ein Produkt aus einem Product-Collection-Block hinzu — ein `addToCart` mit der richtigen Menge wird ausgelöst.
3. Ändere eine Menge im Cart-Block — kein `addToCart` wird ausgelöst.
4. Prüfe in einem Shop mit nicht-dezimaler Währung wie HUF, ob `unitPrice` der echte Preis ist und nicht ein Hundertstel davon.

### Kassenseite (initiateCheckout)

1. Lege Artikel in den Warenkorb und öffne die Kasse.
2. `[Barion Pixel] Event: initiateCheckout` erscheint.
3. Jeder `contents`-Eintrag trägt `unit`, `unitPrice` und `totalItemPrice`.
4. `revenue` ist Zwischensumme + Steuer, ohne Versand.
5. `step: 1` ist vorhanden.
6. Gib eine Rechnungs-E-Mail ein. `setEncryptedEmail sent` erscheint einmal pro gültiger Adresse — nicht bei jedem Tastendruck und nicht bei Teileingaben wie `x@y`.
7. Wiederhole das im Checkout-Block, wo die E-Mail aus dem Block-Datenspeicher statt aus `#billing_email` kommt.

### Bestellabschluss (purchase + setEncryptedEmail)

1. Schließe eine Testbestellung ab — „Überweisung“ ist dafür die einfachste Zahlungsart.
2. `[Barion Pixel] Event: purchase` erscheint, `revenue` entspricht der Bestellsumme.
3. `setEncryptedEmail sent` erscheint.
4. Lade die Bestellbestätigungsseite neu — `purchase` wird **nicht** erneut ausgelöst.
5. Die `contents`-Einträge enthalten `unit` und `totalItemPrice`.

### Consent-Integration

1. Lösche alle Cookies.
2. Lade eine beliebige Seite. `[Barion Pixel] Base pixel initialized` erscheint — das Basis-Pixel lädt bewusst vor jeder Consent-Entscheidung.
3. Akzeptiere Cookies in deinem Banner. `Consent granted (grantConsent)` erscheint.
4. Lade neu — die Einwilligung wird beim Laden erneut erteilt, ohne Banner.
5. Widerrufe die Einwilligung und prüfe, ob `Consent rejected (rejectConsent)` erscheint.

---

## Häufige Probleme

### Events werden nicht ausgelöst

- **Pixel-ID**: unter Einstellungen > Barion Pixel muss eine gültige ID gespeichert sein.
- **Vollständiges Tracking**: E-Commerce-Events brauchen ein Häkchen bei „Vollständiges Pixel-Tracking aktivieren“.
- **WooCommerce**: vollständiges Tracking braucht ein aktives WooCommerce.
- **Konsolenfehler**: ein unabhängiger JavaScript-Fehler kann das Laden von bp.js verhindern.

### Doppeltes Laden des Pixels

`[Barion Pixel] bp.js already loaded by another plugin` bedeutet, dass etwas anderes zuerst da war
— das Barion Payment Gateway, ein Google-Tag-Manager-Tag, ein Theme-Snippet. Das ist harmlos: das
Plugin überspringt das Laden des Skripts und initialisiert trotzdem mit deiner Pixel-ID. Siehe
[Kompatibilität](compatibility.md).

### Einwilligung wird nicht erteilt

- **WP Consent API**: das WP-Consent-API-Plugin muss installiert sein und dein Cookie-Plugin muss es unterstützen.
- **Cookie Law Info**: das Plugin muss aktiv und das globale `CLI` verfügbar sein.
- **Manuell**: rufe `window.wcBarionGrantConsent()` aus dem Callback deines Consent-Managers auf.

### purchase wird bei einer unbezahlten Bestellung ausgelöst

Erwartet, und unter [purchase](events-reference.md#purchase) dokumentiert. Das Plugin verfolgt die
Bestellbestätigungsseite, die Offline-Zahlarten erreichen, bevor das Geld eingeht.
