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

- das [Barion Payment Gateway](https://barion.com/en/plugins/) von Barion und das [Gateway von szelpe](https://github.com/szelpe/woocommerce-barion), die ein optionales Pixel-ID-Feld haben
- ein [Google-Tag-Manager-Tag](https://docs.barion.com/Implementing_the_Barion_Pixel_base_code_through_the_Google_Tag_Manager)
- ein Snippet im Theme-Header

**Zwei Basis-Pixel auf einer Seite verschlechtern das Tracking nicht, sie beenden es.** Jede Kopie
von `bp.js` hängt ihr eigenes iframe mit `id="barion_receiver"` an, `getElementById()` liefert nur
das erste zurück, und die zweite Kopie schickt ihre Events in genau dieses erste iframe, bevor es
den Consent-Status des Besuchers geladen hat. `bp.js` wirft dort einen Fehler
(`Cannot read properties of undefined (reading 'approvedBase')`) und das Event wird nie gesendet.
In einem Live-Shop gemessen: drei Fehler und null Events auf einer Produktseite.

### Was das Plugin dagegen tut

**Das Barion Payment Gateway.** Sein Pixel wird aus `wp_head` mit Priorität 999999 ausgegeben,
sobald sein Pixel-ID-Feld gefüllt ist — unabhängig von seiner eigenen Tracking-Einstellung und
sogar bei abgeschaltetem Gateway. Das liegt hinter allem, was dieses Plugin einreihen kann, also
kann keine Prüfung in JavaScript es sehen. Dieses Plugin wendet deshalb den gatewayeigenen Filter
`woocommerce_barion_disable_tracking` an und liefert das Basis-Pixel selbst aus, allerdings nur
solange hier eine Pixel-ID konfiguriert ist. Dieser Filter hat im Gateway keinen weiteren
Verbraucher, und das Gateway implementiert das Basis-Pixel und nichts darüber hinaus, es geht also
nichts verloren. Eine Website, die das Pixel beim Gateway belassen will, kann ihn per
`remove_filter()` entfernen und stattdessen die Pixel-ID hier leeren.

**Alles andere.** Bevor es `bp.js` lädt, prüft das Plugin `window.bp`. Hat eine andere Quelle es
zuerst definiert, überspringt es das Laden des Skripts und sendet nur den `init`-Aufruf. Im
Debug-Modus erscheint dazu `[Barion Pixel] bp.js already loaded by another plugin`.

Ein Snippet, das *nach* diesem Plugin läuft — ein Google-Tag-Manager-Tag, ein Snippet im
Theme-Header —, ist außer Reichweite: Es lädt `bp.js` erneut, was dieses Plugin auch tut. Im
Debug-Modus fällt dieser Fall nach dem Laden der Seite auf, und das Plugin warnt, dass etwas
anderes eine zweite Kopie von `bp.js` lädt.

**Empfehlung:** halte die Pixel-ID an einer Stelle, hier. Leere das Feld im Gateway und entferne
ein etwaiges Google-Tag-Manager-Tag oder Theme-Snippet. Zu vermeiden ist vor allem der Fall zweier
unterschiedlicher Pixel-IDs auf einer Seite — ein doppeltes Skript kann das Plugin unterdrücken,
eine doppelte Identität nicht.

Wenn auch im Barion Payment Gateway eine Pixel-ID konfiguriert ist, weist die Einstellungsseite
darauf hin. Beide Plugins funktionieren so oder so weiter: jenes übernimmt die Zahlungen, dieses
das Tracking.

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
