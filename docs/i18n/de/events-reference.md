> 🌐 Dies ist eine automatische Übersetzung. Korrekturen aus der Community sind willkommen!
>
> [English version](../../events-reference.md)

# Barion Pixel Events-Referenz

## Übersicht

Das Plugin unterstützt zwei Betriebsmodi:

- **Basis-Pixel** (immer aktiv, wenn eine Pixel-ID konfiguriert ist): Lädt `bp.js` und löst `pageView` automatisch auf jeder Seite aus. Wird zur Betrugsprävention verwendet.
- **Vollständiges Tracking** (optional, Umschalten im Admin): Fügt E-Commerce-Event-Tracking für Marketing-Analysen und niedrigere Barion-Provisionsraten hinzu.

Barions eigene Referenz zu diesen Events: [Barion Pixel API reference](https://docs.barion.com/Barion_Pixel_API_reference) und [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel) (auf Englisch).

### Event-Übersicht

| Event | Modus | bp()-Aufruf | Auslöser |
|-------|-------|-------------|----------|
| pageView | Basis | Automatisch (bp.js) | Jeder Seitenaufruf |
| grantConsent | Basis | `bp('consent', 'grantConsent')` | Cookie-Einwilligung akzeptiert |
| rejectConsent | Basis | `bp('consent', 'rejectConsent')` | Cookie-Einwilligung abgelehnt |
| contentView | Vollständig | `bp('track', 'contentView', data)` | Einzelne Produktseite |
| addToCart | Vollständig | `bp('track', 'addToCart', data)` | In-den-Warenkorb-Aktion |
| initiateCheckout | Vollständig | `bp('track', 'initiateCheckout', data)` | Kassenseite wird geladen |
| purchase | Vollständig | `bp('track', 'purchase', data)` | Danke-Seite |
| setEncryptedEmail | Vollständig | `bp('identity', 'setEncryptedEmail', hash)` | Danke-Seite und E-Mail-Eingabe an der Kasse |

---

## Basis-Pixel-Events

### pageView

Wird automatisch ausgelöst, wenn `bp.js` geladen wird. Keine Konfiguration erforderlich außer der Angabe der Pixel-ID.

### grantConsent

Wird ausgelöst, wenn der Nutzer Marketing-Cookies akzeptiert. Wird automatisch über die WP Consent API oder Cookie Law Info verarbeitet oder manuell über `window.wcBarionGrantConsent()`.

### rejectConsent

Wird ausgelöst, wenn der Nutzer Marketing-Cookies ablehnt. Wird automatisch über die WP Consent API oder Cookie Law Info verarbeitet oder manuell über `window.wcBarionRejectConsent()`. Sowohl `grantConsent` als auch `rejectConsent` sind gemäß den Barion-Anforderungen obligatorisch.

Weitere Einzelheiten unter [Cookie-Consent-Integration](cookie-consent.md).

---

## Events für vollständiges Tracking

### contentView

**Auslöser:** Einzelne Produktseite (Hook `woocommerce_after_single_product`)

**Gesendete Felder:**

| Feld | Typ | Wert |
|------|-----|------|
| contentType | string | `'Product'` |
| currency | string | WooCommerce-Shop-Währung (z. B. `'HUF'`) |
| id | string | Produkt-ID |
| name | string | Anzeigename des Produkts |
| quantity | int | `1` (immer — es wird ein Produkt angesehen) |
| unit | string | `'pcs'` |
| unitPrice | float | Produktpreis |

> **Hinweis:** `totalItemPrice` ist keine contentView-Eigenschaft. bp.js lehnt es zur Laufzeit mit „Invalid key totalItemPrice in contentView event" ab, und die API-Referenz führt es für dieses Event ebenfalls nicht auf. Erforderlich ist es stattdessen innerhalb der `contents`-Array-Artikel.

---

### addToCart

**Auslöser:** Clientseitiges JavaScript (wird unmittelbar bei der In-den-Warenkorb-Aktion ausgelöst)

**Implementierung:** Zwei Pfade, beide clientseitig verarbeitet, um mit Seiten-Caching zu funktionieren:

1. **AJAX-In-den-Warenkorb** (Shop-/Archivseiten): Lauscht auf das WooCommerce-jQuery-Event `added_to_cart`. Liest Produktdaten aus den `<button>`-Datenattributen (`data-product_id`, `data-product_name`, `data-product_price`, `data-quantity`).

2. **Formularabsenden auf der Einzelproduktseite**: Fängt das Absenden von `form.cart` ab. Produktdaten sind als JSON im Footer eingebettet. Bei variablen Produkten wird der `display_price` der ausgewählten Variante aus den WooCommerce-jQuery-`product_variations`-Daten ausgelesen.

**Gesendete Felder:**

| Feld | Typ | Wert |
|------|-----|------|
| contentType | string | `'Product'` |
| currency | string | Shop-Währung |
| id | string | Produkt-ID |
| name | string | Produktname |
| quantity | int | Hinzugefügte Menge |
| unit | string | `'pcs'` |
| unitPrice | float | Preis pro Einheit |
| totalItemPrice | float | `unitPrice * quantity` |
| step | int | `1` |

---

### initiateCheckout

**Auslöser:** Kassenseite wird geladen (Hook `woocommerce_before_checkout_form`)

**Gesendete Felder:**

| Feld | Typ | Wert |
|------|-----|------|
| contents | array | Array von Warenkorb-Artikeln (siehe unten) |
| currency | string | Shop-Währung |
| revenue | float | Warenkorb-Zwischensumme + Steuer (Versand ausgeschlossen — möglicherweise noch nicht berechnet) |
| step | int | `1` |

**Felder der contents-Artikel:**

| Feld | Typ | Wert |
|------|-----|------|
| contentType | string | `'Product'` |
| currency | string | Shop-Währung |
| id | string | Produkt-ID |
| name | string | Produktname |
| quantity | int | Artikelmenge |
| unit | string | `'pcs'` |
| unitPrice | float | Stückpreis |
| totalItemPrice | float | `unitPrice * quantity` |

---

### purchase

**Auslöser:** Danke-Seite (Hook `woocommerce_thankyou`)

**Duplikatverhinderung:** Verwendet `_wc_barion_tracked` Post-Meta, um das erneute Auslösen beim Neuladen der Seite zu verhindern.

**Gesendete Felder:**

| Feld | Typ | Wert |
|------|-----|------|
| contents | array | Array von Bestellartikeln (siehe unten) |
| currency | string | Bestellwährung |
| revenue | float | Bestellsumme (inkl. Versand, Steuer, Rabatte) |
| step | int | `1` |

**Felder der contents-Artikel:**

| Feld | Typ | Wert |
|------|-----|------|
| contentType | string | `'Product'` |
| currency | string | Bestellwährung |
| id | string | Produkt-ID |
| name | string | Artikelname |
| quantity | int | Artikelmenge |
| unit | string | `'pcs'` |
| unitPrice | float | `(item_total + item_tax) / quantity` (spiegelt Rabatte wider) |
| totalItemPrice | float | `unitPrice * quantity` |

**Hinweis zu revenue:** Das `purchase`-Event verwendet die vollständige Bestellsumme (inkl. Versand), während `initiateCheckout` nur Zwischensumme + Steuer verwendet (Versand ist zum Kassenbeginn möglicherweise noch nicht berechnet).

---

### setEncryptedEmail

**Auslöser:** Danke-Seite (Hook `woocommerce_thankyou`) und die Kassenseite — bei angemeldeten Benutzern einmal beim Laden, danach jedes Mal, wenn der Kunde eine andere gültige Rechnungs-E-Mail-Adresse eingibt.

**bp()-Aufruf:** `bp('identity', 'setEncryptedEmail', hash)`

Die Adresse wird in Kleinbuchstaben umgewandelt und im Browser per SHA-1 gehasht (Web Crypto API), bevor sie `bp.js` erreicht. Die Barion-API akzeptiert einen vorberechneten SHA-1-Hash anstelle der Klartextadresse, und das Vorab-Hashing umgeht den eigenen E-Mail-Regex von `bp.js`, der `+` im lokalen Teil und TLDs mit mehr als vier Buchstaben ablehnt. Ein Wert, der bereits ein 40-stelliger Hex-Hash ist, wird unverändert durchgereicht; ist die Web Crypto API nicht verfügbar (Kontext ohne HTTPS), wird die Klartextadresse gesendet.

Werte, die weder eine gültige E-Mail-Adresse (gemäß [HTML5-Spezifikation](https://html.spec.whatwg.org/multipage/input.html#valid-e-mail-address)) noch ein SHA-1-Hash sind, werden nie gesendet — Teileingaben an der Kasse erreichen `bp.js` also nicht.

Auf der Danke-Seite wird nur ausgelöst, wenn die Bestellung eine Rechnungs-E-Mail-Adresse hat.

---

## Nicht implementierte Events

| Event | Grund |
|-------|-------|
| `customEvent` | Nicht erforderlich für Standard-E-Commerce-Tracking |
| `initiatePurchase` | Barions Liste der Pflicht-Events besagt: entweder `initiatePurchase` ODER `purchase` implementieren — wir verwenden `purchase` |
| `setEncryptedPhone` | Optional; Telefonnummern sind nicht in allen WooCommerce-Abläufen zuverlässig verfügbar |
| `search` | Optional; nicht Teil des obligatorischen Event-Sets |
