> 🌐 Dies ist eine automatische Übersetzung. Korrekturen aus der Community sind willkommen!
>
> [English version](../../compatibility.md)

# Plugin-Kompatibilität

## WooCommerce

**Erforderlich für vollständiges Event-Tracking.** Das Basis-Pixel funktioniert ohne WooCommerce, aber alle E-Commerce-Events (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail) erfordern WooCommerce.

| Version | Status |
|---------|--------|
| WooCommerce 5.0+ | Unterstützt |
| WooCommerce 11.0 | Getestet |

### Cart- und Checkout-Block

Unterstützt seit 1.0.6. Die Blöcke lösen weder die klassischen PHP-Hooks noch die zuvor genutzten
DOM-Selektoren aus, deshalb liest das Plugin auf Block-Oberflächen die WooCommerce-Daten direkt:
den Store-API-Warenkorb für `addToCart` und den Datenspeicher `wc/store/cart` für die
Kassen-E-Mail.

**Bekannte Einschränkung.** Das `purchase`-Event läuft über `woocommerce_thankyou`, das im
Block-Template der Bestellbestätigung vom Block „Weitere Informationen“ ausgelöst wird. Entfernst
du diesen Block aus dem Template, endet das Purchase-Tracking stillschweigend. Lass ihn im
Template.

---

## Weitere Quellen des Basis-Pixels

Barion dokumentiert mehrere Wege, das Basis-Pixel auf eine Seite zu bekommen, und ein Shop kann
leicht mehrere davon gleichzeitig haben:

- das [Barion Payment Gateway](https://github.com/szelpe/woocommerce-barion) von szelpe und andere Barion-Gateway-Plugins, die ein optionales Pixel-ID-Feld haben
- ein [Google-Tag-Manager-Tag](https://docs.barion.com/Implementing_the_Barion_Pixel_base_code_through_the_Google_Tag_Manager)
- ein Snippet im Theme-Header

Das Plugin prüft `window.bp` und `window.BarionAnalyticsObject`, bevor es `bp.js` lädt. Sind beide
schon vorhanden, überspringt es das Laden des Skripts und sendet nur seinen eigenen `init`-Aufruf,
sodass das Pixel nie doppelt geladen wird. Im Debug-Modus erscheint dazu
`[Barion Pixel] bp.js already loaded by another plugin`.

**Empfehlung:** halte die Pixel-ID an einer Stelle. Wenn du auch ein Barion-Payment-Gateway
betreibst, konfiguriere die ID hier und lasse das Feld im Gateway leer; lädst du das Basis-Pixel
bereits über den Google Tag Manager, entferne dieses Tag. Zu vermeiden ist vor allem der Fall
zweier unterschiedlicher Pixel-IDs auf einer Seite — ein doppeltes Skript kann das Plugin
unterdrücken, eine doppelte Identität nicht.

Wenn auch im Barion Payment Gateway eine Pixel-ID konfiguriert ist, zeigt die Einstellungsseite
einen informativen Hinweis. Beide Plugins funktionieren so oder so weiter: jenes übernimmt die
Zahlungen, dieses das Tracking.

---

## Seiten-Caching-Plugins

Das Plugin ist vollständig mit Seiten-Caching kompatibel:

| Event | Implementierung | Auswirkung des Cachings |
|-------|-----------------|-------------------------|
| contentView | Serverseitig (Produktseite) | Produktseiten werden typischerweise nicht gecacht oder variieren je nach Produkt |
| addToCart | **Clientseitiges JavaScript** | Keine Caching-Probleme — JS wird im Browser ausgeführt |
| initiateCheckout | Serverseitig (Kassenseite) | Die Kasse wird nicht gecacht (enthält Benutzersitzungsdaten) |
| purchase | Serverseitig (Danke-Seite) | Danke-Seiten werden nicht gecacht (einmalig pro Bestellung) |

Das addToCart-Event wurde gezielt clientseitig implementiert (anstatt PHP-Sitzungen zu verwenden), um mit WordPress.com-Hosting und aggressiven Seiten-Caching-Setups zu funktionieren.

**Kompatibel mit:** WP Super Cache, W3 Total Cache, LiteSpeed Cache, WordPress.com-Hosting, Cloudflare und ähnlichen Caching-Lösungen.

---

## Cookie-Consent-Plugins

Das Plugin unterstützt alle Cookie-Consent-Plugins, die die [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) implementieren. Weitere Einzelheiten findest du unter [Cookie-Consent-Integration](cookie-consent.md).

**Automatisch unterstützt:**

- CookieYes (1,5 Mio.+ Installationen)
- Complianz (1 Mio.+ Installationen)
- Cookie Notice by dFactory (1 Mio.+ Installationen)
- GDPR Cookie Compliance by Moove (300.000+ Installationen)
- Real Cookie Banner (100.000+ Installationen)

**Direkte Fallback-Integration:**

- Cookie Law Info / CookieYes (funktioniert auch ohne WP Consent API)

---

## WordPress-Version

| Version | Status |
|---------|--------|
| WordPress 5.0+ | Erforderlich |
| WordPress 7.0 | Getestet |

## PHP-Version

| Version | Status |
|---------|--------|
| PHP 7.4+ | Erforderlich |
| PHP 8.x | Kompatibel |
