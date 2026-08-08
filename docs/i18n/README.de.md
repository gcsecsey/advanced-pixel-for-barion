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

#### Vollständiges Pixel-Tracking aktivieren
Umschalten, um das E-Commerce-Event-Tracking zu aktivieren/deaktivieren. Wenn deaktiviert, wird nur das Basis-Pixel geladen (pageView zur Betrugsprävention).

#### Debug-Modus
Aktivieren, um alle Barion Pixel-Events für Testzwecke in der Browser-Konsole zu protokollieren.

## Dokumentation

Detaillierte Dokumentation ist im Ordner [`de/`](de/) verfügbar:

- [Events-Referenz](de/events-reference.md) — Alle erfassten Events, Felder und Datentypen
- [Cookie-Consent-Integration](de/cookie-consent.md) — WP Consent API, Cookie Law Info und manuelle Integration
- [Kompatibilität](de/compatibility.md) — WooCommerce, Barion Payment Gateway, Caching-Plugins
- [Testhinweise](de/testing-notes.md) — bp.js-Eigenheiten, Debug-Modus, Test-Checkliste

Die Dokumentation ist auch verfügbar auf [Magyar](hu/), [Čeština](cs/), [Slovenčina](sk/), [Deutsch](de/), [Hrvatski](hr/), [Română](ro/), [Slovenščina](sl/) und [Srpski](sr/).

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

## Lizenz

GPL-2.0-or-later — siehe [LICENSE](../../LICENSE) für Details.

## Änderungsprotokoll

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
