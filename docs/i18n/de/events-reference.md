> 🌐 Dies ist eine automatische Übersetzung. Korrekturen aus der Community sind willkommen!
>
> [English version](../../events-reference.md)

# Barion Pixel Events-Referenz

Maßgeblich dafür, was ein Event bedeutet und welche Eigenschaften es akzeptiert, sind Barions
eigene Seiten:

- [Barion Pixel event reference](https://docs.barion.com/Barion-pixel-event-reference) — jedes Event, jede Eigenschaft und welche davon erforderlich sind
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel) — die Events selbst
- [Barion Pixel FAQ](https://docs.barion.com/Frequently_Asked_Questions_about_the_Barion_Pixel) — Antworten auf die kniffligen Fälle

Diese Seite beschreibt nur, was **dieses Plugin** sendet und wann.

## Überblick

Das Plugin hat zwei Betriebsmodi:

- **Basis-Pixel** (aktiv, sobald eine Pixel-ID gesetzt ist): lädt `bp.js` und löst `pageView` automatisch aus. Barion verlangt es zur Betrugsprävention, und es ist Voraussetzung für die Nutzung des Barion Smart Gateway überhaupt.
- **Vollständiges Tracking** (optional, im Admin umschaltbar): ergänzt die E-Commerce-Events. Barion Metrics benötigt sie, und eine vollständige Pixel-Implementierung zusammen mit einem regelkonformen Consent-Banner ist es, was einen Shop für bessere Smart-Gateway-Konditionen qualifiziert.

### Event-Übersicht

| Event | Modus | bp()-Aufruf | Auslöser |
|-------|-------|-------------|----------|
| pageView | Basis | Automatisch (bp.js) | Jeder Seitenaufruf |
| grantConsent | Basis | `bp('consent', 'grantConsent')` | Marketing-Einwilligung erteilt |
| rejectConsent | Basis | `bp('consent', 'rejectConsent')` | Marketing-Einwilligung abgelehnt |
| contentView | Voll | `bp('track', 'contentView', data)` | Produktseite |
| addToCart | Voll | `bp('track', 'addToCart', data)` | In-den-Warenkorb-Aktion |
| initiateCheckout | Voll | `bp('track', 'initiateCheckout', data)` | Aufruf der Kassenseite |
| purchase | Voll | `bp('track', 'purchase', data)` | Bestellbestätigungsseite |
| setEncryptedEmail | Voll | `bp('identity', 'setEncryptedEmail', hash)` | Bestellbestätigungsseite und E-Mail-Eingabe an der Kasse |

---

## Artikelfelder

`contentView` und jeder Eintrag eines `contents`-Arrays verwenden dieselbe Struktur:

| Feld | Typ | Wert |
|------|-----|------|
| contentType | string | `'Product'` |
| currency | string | Shop-Währung, bei `purchase` die Bestellwährung |
| id | string | Produkt-ID |
| name | string | Angezeigter Produktname |
| quantity | int | Siehe das jeweilige Event |
| unit | string | `'pcs'` |
| unitPrice | float | Siehe das jeweilige Event |
| totalItemPrice | float | `unitPrice * quantity` |

Zwei Ausnahmen zu dieser Tabelle:

- **`contentView` sendet kein `totalItemPrice`.** bp.js lehnt es mit `Invalid key totalItemPrice in contentView event` ab, und Barions Referenz führt es ebenfalls nicht als contentView-Eigenschaft. Innerhalb von `contents`-Einträgen ist es dagegen erforderlich — siehe [Testhinweise](testing-notes.md).
- **`quantity` ist bei `contentView` immer `1`**, weil der Kunde ein einzelnes Produkt betrachtet.

Das Plugin sendet keine optionalen Content-Eigenschaften (`brand`, `category`, `description`,
`ean`, `imageUrl`, `variant`) und keine `list`-Eigenschaft. In Barions Referenz sind sie alle
optional.

**Variable Produkte.** `contentView` und das `addToCart` der Produktseite melden das
Elternprodukt, denn darum geht es auf der Seite. Warenkorb- und Bestellzeilen melden die gewählte
Variante, denn die legt WooCommerce in den Warenkorb. Barion verlangt, dass ein Artikel über alle
Events hinweg gleich benannt und identifiziert wird — in einem Shop, der stark auf Varianten
setzt, kann dasselbe Produkt Barion also unter zwei Identitäten erreichen.

---

## Basis-Pixel-Events

### pageView

Wird automatisch ausgelöst, sobald `bp.js` geladen ist. Außer der Pixel-ID ist nichts zu
konfigurieren.

### grantConsent / rejectConsent

Werden ausgelöst, wenn der Kunde Marketing-Cookies akzeptiert oder ablehnt. Barion führt beide
als erforderlich. Sie laufen automatisch über die WP Consent API oder Cookie Law Info, oder
manuell über `window.wcBarionGrantConsent()` / `window.wcBarionRejectConsent()`.

Siehe [Cookie-Consent-Integration](cookie-consent.md).

---

## Events des vollständigen Trackings

### contentView

**Auslöser:** Produktseite, am Hook `woocommerce_after_single_product`.

`unitPrice` ist der aktuelle Produktpreis. Bei einem variablen Produkt ist das der Preis, den
WooCommerce vor der Auswahl einer Variante anzeigt.

---

### addToCart

**Auslöser:** die In-den-Warenkorb-Aktion selbst. Alle Wege sind clientseitig, damit das Event
Seiten-Caching übersteht. Es gibt drei, und welcher greift, hängt davon ab, wie der Shop seine
Buttons rendert:

1. **Klassisches AJAX-In-den-Warenkorb** (Shop- und Archivseiten). Horcht auf WooCommerces jQuery-Event `added_to_cart`. Der Button liefert Produkt und Menge über `data-product_id` und `data-quantity`. Einen Preis trägt er **nicht** — WooCommerce rendert kein `data-product_price` — deshalb kommt der Preis aus der [Store-API](https://developer.woocommerce.com/docs/apis/store-api/)-Zeile, die das Hinzufügen gerade erzeugt hat.
2. **Klassische Produktseite.** Fängt den Submit von `form.cart` ab. Die Produktdaten stecken im Footer; bei einem variablen Produkt wird der `display_price` der gewählten Variante aus WooCommerces jQuery-Daten `product_variations` gelesen.
3. **Block-Oberflächen** (Product-Collection-Buttons, Cart-Block). Diese laufen über die Interactivity API und liefern weder das jQuery-Event noch brauchbare Daten, deshalb vergleicht das Plugin den Warenkorb der [Store API](https://developer.woocommerce.com/docs/apis/store-api/) mit dem zuletzt bekannten Stand und meldet die Differenz. Mengenänderungen im Cart-Block lösen `wc-blocks_added_to_cart` nicht aus und bleiben damit automatisch außen vor.

**Event-Felder:** die Artikelfelder von oben, plus `step: 1`.

`quantity` ist das, was der Kunde tatsächlich hinzugefügt hat. `unitPrice` stammt sowohl beim
klassischen AJAX als auch bei den Block-Oberflächen aus der Store-API-Zeile und auf der
Produktseite aus der gewählten Variante — nie aus dem Button-Markup, das ihn nicht enthält.

---

### initiateCheckout

**Auslöser:** Aufruf der Kassenseite. Erkannt über `is_checkout()` unter Ausschluss des Endpunkts
`order-received` — nicht über `woocommerce_before_checkout_form`, denn diesen Hook löst der
Checkout-Block nie aus.

| Feld | Typ | Wert |
|------|-----|------|
| contents | array | Ein Eintrag pro Warenkorbzeile |
| currency | string | Shop-Währung |
| revenue | float | Zwischensumme + Steuer |
| step | int | `1` |

Der Versand bleibt bewusst außerhalb von `revenue`: zu Beginn der Kasse hat der Kunde meist noch
keine Versandart gewählt, WooCommerce hat also nichts hinzuzufügen.

---

### purchase

**Auslöser:** die Bestellbestätigungsseite, am Hook `woocommerce_thankyou`.

| Feld | Typ | Wert |
|------|-----|------|
| contents | array | Ein Eintrag pro Bestellzeile |
| currency | string | Bestellwährung |
| revenue | float | Bestellsumme inklusive Versand, Steuern und Rabatten |
| step | int | `1` |

`unitPrice` ist hier `(item_total + item_tax) / quantity` und bildet damit Gutscheine und andere
Rabatte ab. Deshalb sind die Umsätze von `purchase` und `initiateCheckout` nicht Zeile für Zeile
vergleichbar.

**Duplikatschutz:** die Bestellung erhält das Meta-Feld `_wc_barion_tracked`, sodass ein Neuladen
der Bestellbestätigungsseite kein zweites `purchase` sendet.

**Bekannte Abweichung.** Barion erwartet `purchase`, wenn die Zahlung tatsächlich erfolgreich
war, und `purchase` mit `step: -1`, wenn sie fehlgeschlagen ist. Das Plugin sendet `purchase` mit
`step: 1`, sobald der Kunde die Bestellbestätigungsseite erreicht — bei Offline-Zahlarten wie
Überweisung oder Nachnahme also, während die Bestellung noch unbezahlt ist. `step: -1` sendet es
nie.

---

### setEncryptedEmail

**bp()-Aufruf:** `bp('identity', 'setEncryptedEmail', hash)`

**Auslöser:**

- Bestellbestätigungsseite, wenn die Bestellung eine Rechnungs-E-Mail hat.
- Kassenseite, einmal beim Laden für angemeldete Kunden.
- Kassenseite, sobald der Kunde eine andere gültige Rechnungs-E-Mail eingibt — aus dem Feld `#billing_email` bei der klassischen Kasse, oder aus dem Datenspeicher der Cart- und Checkout-Blöcke beim Block-Checkout.

Die Adresse wird kleingeschrieben und im Browser per SHA-1 gehasht (Web Crypto API), bevor sie
`bp.js` erreicht. Barion akzeptiert einen vorberechneten SHA-1-Hash anstelle der Klartextadresse,
und das Vorab-Hashing umgeht bp.js' eigene E-Mail-Regex, die `+` im lokalen Teil und TLDs mit mehr
als vier Buchstaben ablehnt. Ein Wert, der bereits ein 40-stelliger Hex-Hash ist, wird unverändert
durchgereicht. Ist die Web Crypto API nicht verfügbar — etwa in einem Nicht-HTTPS-Kontext —, wird
stattdessen die Klartextadresse gesendet.

Werte, die weder eine gültige E-Mail-Adresse (gemäß
[HTML5-Spezifikation](https://html.spec.whatwg.org/multipage/input.html#valid-e-mail-address))
noch ein SHA-1-Hash sind, werden nie gesendet; teilweise Eingaben an der Kasse erreichen `bp.js`
also nicht. Wiederholte Werte sind wirkungslos.

---

## Events, die das Plugin nicht sendet

Barions Event-Referenz führt diese unter den **erforderlichen** Event-Handlern. Die FAQ ergänzt,
dass ein Event, dem in deinem Shop keine Nutzerabsicht entspricht, nicht implementiert werden
muss — das deckt einige davon ab, aber nicht alle.

| Event | Warum nicht |
|-------|-------------|
| `initiatePurchase` | Hier überflüssig. Barion verlangt `initiatePurchase` *oder* `purchase`; das Plugin sendet `purchase` |
| `setEncryptedPhone` | Die Rechnungstelefonnummer ist in WooCommerce optional und in vielen Shops nicht vorhanden |
| `search`, `categorySelection`, `addPaymentInfo`, `removeFromCart` | In einem typischen WooCommerce-Shop anwendbar, aber noch nicht implementiert |

Die empfohlenen Handler — `customizeProduct`, `setUserProperties`, `signUp`, `clickPromo`,
`clickProduct`, `clickProductDetail`, `error` — und `customEvent` sind ebenfalls nicht
implementiert.

Wenn dein Shop eines davon braucht: das Basis-Pixel legt `bp()` auf `window` ab, sodass
`bp('track', 'search', { ... })` aus deinem eigenen Theme- oder Plugin-Code funktioniert.
