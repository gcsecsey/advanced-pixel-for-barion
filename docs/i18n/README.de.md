> 🌐 Dies ist eine automatische Übersetzung. Korrekturen aus der Community sind willkommen!

# Advanced Pixel for Barion

Barion Pixel-Integration für WooCommerce mit vollständigem E-Commerce-Event-Tracking, Cookie-Consent-Unterstützung und WP Consent API-Kompatibilität.

<p align="center">
  <a href="../../README.md">English</a> |
  <a href="README.hu.md">Magyar</a> |
  <a href="README.cs.md">Čeština</a> |
  <a href="README.sk.md">Slovenčina</a> |
  <strong>Deutsch</strong> |
  <a href="README.hr.md">Hrvatski</a> |
  <a href="README.ro.md">Română</a> |
  <a href="README.sl.md">Slovenščina</a> |
  <a href="README.sr.md">Srpski</a>
</p>

## Funktionen

- **Basis-Barion-Pixel**: Lädt das Barion-Tracking-Skript websiteweit (pageView wird automatisch ausgelöst)
- **Vollständiges Event-Tracking**: Alle obligatorischen E-Commerce-Events gemäß Barion-Dokumentation
  - `contentView`: Wird auf Produktseiten ausgelöst
  - `addToCart`: Wird ausgelöst, wenn Artikel in den Warenkorb gelegt werden (clientseitig, funktioniert mit Seiten-Caching)
  - `initiateCheckout`: Wird beim Start des Bestellvorgangs ausgelöst
  - `purchase`: Wird bei erfolgreichem Bestellabschluss ausgelöst (mit Duplikatverhinderung)
  - `setEncryptedEmail`: Sendet die Rechnungs-E-Mail-Adresse bei einem Kauf an Barion (verschlüsselt durch bp.js)
- **WP Consent API-Integration**: Universelle Cookie-Consent-Unterstützung — funktioniert mit CookieYes, Complianz, Real Cookie Banner, GDPR Cookie Compliance, Cookie Notice und mehr
- **Cookie Law Info-Fallback**: Direkte Integration für Websites, die CookieYes/Cookie Law Info verwenden
- **Admin-Einstellungsseite**: Einfache Konfiguration über das WordPress-Admin-Panel
- **Debug-Modus**: Konsolenprotokollierung zum Testen und Entwickeln
- **bp.js Doppellade-Erkennung**: Koexistiert sicher mit anderen Plugins, die bp.js laden (z. B. Barion Payment Gateway)

## Installation

1. Lade den Ordner `advanced-pixel-for-barion` nach `/wp-content/plugins/` hoch
2. Aktiviere das Plugin über das Menü „Plugins" in WordPress
3. Navigiere zu Einstellungen > Barion Pixel zur Konfiguration

## Konfiguration

### Admin-Einstellungen

Rufe die Einstellungsseite unter **Einstellungen > Barion Pixel** im WordPress-Admin auf.

#### Pixel-ID (Erforderlich)
Gib deine Barion Pixel-ID ein (Format: `BP-0000000000-00`). Das Basis-Pixel wird auf allen Seiten geladen, sobald dies eingestellt ist.

Die ID findest du in deiner Barion-Wallet unter **Merchant Management > Details**. Jeder Shop hat eine eigene, und Sandbox und Live-Umgebung vergeben unterschiedliche IDs. Eine ID, die mit `BPT` beginnt, ist keine Pixel-ID und funktioniert nicht.

#### Vollständiges Pixel-Tracking aktivieren
Umschalten, um das E-Commerce-Event-Tracking zu aktivieren/deaktivieren. Wenn deaktiviert, wird nur das Basis-Pixel geladen (pageView zur Betrugsprävention).

Barion verlangt eine vollständige Pixel-Implementierung samt regelkonformem Consent-Banner, bevor ein Shop bessere Konditionen für das Barion Smart Gateway oder Zugang zu Barion Metrics erhält. Dieses Plugin deckt die Implementierung ab; die Freigabe erteilt Barion.

#### Debug-Modus
Aktivieren, um alle Barion Pixel-Events für Testzwecke in der Browser-Konsole zu protokollieren.

## Dokumentation

Detaillierte Dokumentation ist im Ordner [`de/`](de/) verfügbar:

- [Events-Referenz](de/events-reference.md) — Alle erfassten Events, Felder und Datentypen
- [Cookie-Consent-Integration](de/cookie-consent.md) — WP Consent API, Cookie Law Info und manuelle Integration
- [Kompatibilität](de/compatibility.md) — WooCommerce, Barion Payment Gateway, Caching-Plugins
- [Testhinweise](de/testing-notes.md) — bp.js-Eigenheiten, Debug-Modus, Test-Checkliste

Die Dokumentation ist auch verfügbar auf [Magyar](hu/), [Čeština](cs/), [Slovenčina](sk/), [Deutsch](de/), [Hrvatski](hr/), [Română](ro/), [Slovenščina](sl/) und [Srpski](sr/).

### Barion-Dokumentation

Barions eigene Anleitungen zur Einrichtung des Pixels (auf Englisch). Die Option **Enable Full Pixel Tracking** in diesem Plugin entspricht dem vollständigen (Full) Barion Pixel:

- [Getting started with the Barion Pixel](https://docs.barion.com/Getting_started_with_the_Barion_Pixel)
- [Implementing the Base Barion Pixel](https://docs.barion.com/Implementing_the_Base_Barion_Pixel)
- [Implementing the Full Barion Pixel](https://docs.barion.com/Implementing_the_Full_Barion_Pixel)
- [Implementing the Base and Full pixel in WooCommerce webshops](https://docs.barion.com/Implementing-the-barion-base-and-full-pixel-in-woocommerce-webshops)
- [Barion Pixel event reference](https://docs.barion.com/Barion-pixel-event-reference)
- [Barion Pixel consent management requirements](https://docs.barion.com/Barion_Pixel_Consent_Management_requirements)
- [Barion Pixel FAQ](https://docs.barion.com/Frequently_Asked_Questions_about_the_Barion_Pixel)

## Kompatibilität

- **WooCommerce**: Erforderlich für vollständiges Event-Tracking (Basis-Pixel funktioniert auch ohne)
- **Barion Payment Gateway** ([woocommerce-barion](https://github.com/szelpe/woocommerce-barion)): Funktioniert problemlos zusammen — dieses Plugin verarbeitet Zahlungen, das andere verwaltet das Pixel-Tracking
- **Seiten-Caching**: Vollständig kompatibel (addToCart verwendet clientseitiges JS)
- **Cookie-Plugins**: Jedes mit der WP Consent API kompatible Plugin funktioniert automatisch

## Anforderungen

- WordPress 5.0 oder höher
- PHP 7.4 oder höher
- WooCommerce 5.0+ (für vollständiges Event-Tracking)
- Optional: [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) für universelle Cookie-Consent-Unterstützung

## Mitwirken

Fehlerberichte, Pull Requests und Übersetzungen sind willkommen — siehe [Beitragsleitfaden](de/contributing.md).

## Lizenz

GPL-2.0-or-later — siehe [LICENSE](../../LICENSE) für Details.

## Änderungsprotokoll

### 1.0.7
- Behoben: ein schwerwiegender Fehler auf jeder Website, die das Plugin ohne WooCommerce betrieb, sobald eine Pixel-ID gespeichert und das vollständige Tracking aktiviert war. Das Ereignisskript im Footer rief `is_product()` auf, eine Funktion, die es nur bei geladenem WooCommerce gibt, sodass die Seite mit `Call to undefined function is_product()` abbrach. Die WooCommerce-Ereignis-Hooks werden jetzt nur noch registriert, wenn WooCommerce aktiv ist; das Basis-Pixel lädt wie dokumentiert auch ohne WooCommerce. Der Fehler besteht seit 1.0.0
- Behoben: der Hinweis auf eine ebenfalls im Barion-Payment-Gateway-Plugin gesetzte Pixel-ID erschien in allen Sprachen auf Englisch. Der Text wurde in einer früheren Version neu formuliert, die Übersetzungen wurden nie nachgezogen

### 1.0.6
- Behoben: `initiateCheckout` und `setEncryptedEmail` wurden im WooCommerce-Checkout-Block nie ausgelöst, der seit WooCommerce 8.3 die Voreinstellung für neue Shops ist. Das Plugin horchte nur auf die PHP-Hooks der klassischen Kasse und deren Feld `#billing_email`, und der Block hat beides nicht. Es liest jetzt den Datenspeicher der Cart- und Checkout-Blöcke; das Verhalten der klassischen Kasse bleibt unverändert
- Behoben: `addToCart` wurde auf Shop- und Kategorieseiten nie ausgelöst, in keinem Shop. Das Event-Skript wurde nur auf Seiten geladen, auf denen bereits ein Event in der Warteschlange stand — auf Archivseiten nie. Die Add-to-Cart-Listener fehlten also genau dort, wo Kunden tatsächlich in den Warenkorb legen. Der Fehler stammt aus 1.0.1
- Behoben: `addToCart` funktioniert jetzt auch mit den Produkt-Buttons des Product-Collection-Blocks. Diese laufen über die Interactivity API und lösen weder das klassische jQuery-Event noch den Block-Datenspeicher aus, deshalb wird der Warenkorbinhalt über die WooCommerce Store API gelesen

### 1.0.5
- Behoben: die mitgelieferten Übersetzungen (Ungarisch, Tschechisch, Slowakisch, Deutsch, Kroatisch, Rumänisch, Slowenisch und Serbisch) wurden nie geladen, die Einstellungsseite blieb englisch. WordPress durchsucht nur `wp-content/languages/plugins`, solange ein Plugin kein eigenes Verzeichnis registriert — und genau das fehlte. Jetzt wird `languages/` beim `init` registriert

### 1.0.4
- Kompatibilität: getestet mit WordPress 7.0 und WooCommerce 11.0
- Geändert: `Requires PHP` von 7.2 auf 7.4 angehoben. WordPress 7.0 hat die Unterstützung für PHP 7.2 und 7.3 eingestellt, damit war 7.2 keine Version mehr, auf der das Plugin laufen konnte

### 1.0.3
- Behoben: `setEncryptedEmail` wurde bei einem einzigen Aufruf der Kassenseite mehrfach gesendet
- Behoben: bp.js lehnte E-Mail-Adressen mit `+` im lokalen Teil oder mit einer TLD von mehr als vier Buchstaben (`.museum`, `.online`) mit `Format of e-mail address or hash is invalid` ab. Das Plugin bildet die Adresse jetzt im Browser als SHA-1-Hash, bevor sie an bp.js übergeben wird — die Barion-Pixel-API akzeptiert einen vorberechneten Hash anstelle der Klartextadresse
- Behoben: unvollständige Eingaben (zum Beispiel `x@y`) werden nicht mehr an bp.js weitergegeben
- Behoben: Aufruf gemäß Barion-Dokumentation als `bp('identity', 'setEncryptedEmail', ...)` (zuvor `'identify'`)

Version 1.0.2 wurde vor der Veröffentlichung durch 1.0.3 ersetzt; ihre Korrekturen sind oben aufgeführt.

### 1.0.1
- Behoben: es wurden überhaupt keine Pixel-Events gesendet — das Event-Skript wurde eingereiht, nachdem `wp_print_footer_scripts` bereits ausgeführt war
- Behoben: die automatische Consent-Erkennung läuft jetzt nach `DOMContentLoaded` und sieht damit auch Globals von spät ladenden Consent-Plugins
- Neu: `setEncryptedEmail` wird auch auf der Kassenseite gesendet — bei angemeldeten Benutzern beim Laden und sobald eine gültige Rechnungs-E-Mail eingegeben wird

### 1.0.0
- Erstveröffentlichung
- Basis-Barion-Pixel (pageView) Implementierung
- Vollständiges Event-Tracking (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail)
- WP Consent API-Integration
- Cookie Law Info-Fallback-Integration
- Admin-Einstellungsseite mit Debug-Modus
- Clientseitiges addToCart (kompatibel mit Seiten-Caching)
- Unterstützung für variable Produkte
- Duplikat-Kaufverhinderung
- bp.js Doppellade-Erkennung
