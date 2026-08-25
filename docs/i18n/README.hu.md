> 🌐 Ez egy automatikus fordítás. Közösségi javítások szívesen fogadottak!

# Advanced Pixel for Barion

Barion Pixel integráció WooCommerce-hez teljes e-kereskedelmi eseménykövetéssel, cookie-hozzájárulás támogatással és WP Consent API kompatibilitással.

<p align="center">
  <a href="../../README.md">English</a> |
  <strong>Magyar</strong> |
  <a href="README.cs.md">Čeština</a> |
  <a href="README.sk.md">Slovenčina</a> |
  <a href="README.de.md">Deutsch</a> |
  <a href="README.hr.md">Hrvatski</a> |
  <a href="README.ro.md">Română</a> |
  <a href="README.sl.md">Slovenščina</a> |
  <a href="README.sr.md">Srpski</a>
</p>

## Funkciók

- **Alap Barion Pixel**: Betölti a Barion követési szkriptet az egész webhelyen (pageView automatikusan aktiválódik)
- **Teljes eseménykövetés**: Minden kötelező e-kereskedelmi esemény a Barion dokumentációja szerint
  - `contentView`: Termékoldalakon aktiválódik
  - `addToCart`: Kosárba helyezéskor aktiválódik (kliensoldalon, oldal gyorsítótárazással kompatibilis)
  - `initiateCheckout`: A pénztár megkezdésekor aktiválódik
  - `purchase`: Sikeres rendelés teljesítésekor aktiválódik (duplikáció-védelemmel)
  - `setEncryptedEmail`: Elküld a számlázási e-mail-t a Barionnak vásárláskor (a bp.js titkosítja)
- **WP Consent API integráció**: Univerzális cookie-hozzájárulás támogatás — működik CookieYes, Complianz, Real Cookie Banner, GDPR Cookie Compliance, Cookie Notice és más bővítményekkel
- **Cookie Law Info tartalék**: Közvetlen integráció CookieYes/Cookie Law Info-t használó oldalakhoz
- **Adminisztrációs beállítások panel**: Egyszerű konfiguráció a WordPress adminisztrációs felületen keresztül
- **Hibakeresési mód**: Konzolnaplózás teszteléshez és fejlesztéshez
- **bp.js dupla betöltés észlelése**: Biztonságosan együttműködik más bővítményekkel, amelyek betöltik a bp.js fájlt (pl. Barion Payment Gateway)

## Telepítés

1. Töltsd fel a `advanced-pixel-for-barion` mappát a `/wp-content/plugins/` könyvtárba
2. Aktiváld a bővítményt a WordPress 'Bővítmények' menüjén keresztül
3. Navigálj a Beállítások > Barion Pixel menüponthoz a konfiguráláshoz

## Konfiguráció

### Adminisztrációs beállítások

A beállítások oldalt a WordPress adminisztrációs felületen a **Beállítások > Barion Pixel** menüpont alatt éred el.

#### Pixel azonosító (kötelező)

Add meg a Barion Pixel azonosítódat (formátum: `BP-0000000000-00`). Az Alap Pixel minden oldalon betöltődik, amint ez be van állítva.

Az azonosítót a Barion tárcádban, a **Merchant Management > Details** oldalon találod. Minden boltnak saját azonosítója van, és a sandbox, illetve az éles környezet külön azonosítót ad ki. A `BPT` kezdetű azonosító nem Pixel azonosító, azzal nem fog működni.

#### Teljes Pixel követés engedélyezése

Kapcsold be/ki az e-kereskedelmi eseménykövetést. Kikapcsolt állapotban csak az Alap Pixel töltődik be (pageView a csalás megelőzéséhez).

A Barion teljes Pixel implementációt és megfelelő hozzájárulási sávot vár el ahhoz, hogy egy bolt kedvezőbb Barion Smart Gateway feltételeket vagy Barion Metrics hozzáférést kapjon. Ez a bővítmény az implementációt fedi le; a jóváhagyás a Barion döntése.

#### Hibakeresési mód

Engedélyezd, hogy az összes Barion Pixel esemény naplózódjon a böngésző konzolba tesztelés céljából.

## Dokumentáció

Részletes dokumentáció elérhető a [`hu/`](hu/) mappában:

- [Eseményreferencia](hu/events-reference.md) — Minden követett esemény, mező és adattípus
- [Cookie-hozzájárulás integráció](hu/cookie-consent.md) — WP Consent API, Cookie Law Info és manuális integráció
- [Kompatibilitás](hu/compatibility.md) — WooCommerce, Barion Payment Gateway, gyorsítótárazó bővítmények
- [Tesztelési megjegyzések](hu/testing-notes.md) — bp.js sajátosságok, hibakeresési mód, tesztelési ellenőrzőlista

A dokumentáció elérhető [Magyar](hu/), [Čeština](cs/), [Slovenčina](sk/), [Deutsch](de/), [Hrvatski](hr/), [Română](ro/), [Slovenščina](sl/) és [Srpski](sr/) nyelven is.

### Barion dokumentáció

A Barion saját útmutatói a pixel beállításához. A bővítmény **Teljes Pixel követés engedélyezése** beállítása a Barion Teljes (Full) Barion Pixeljének felel meg:

- [Áttekintés a Barion Pixel működéséről](https://docs.barion.com/Attekintes-a-Barion-Pixel-mukodeserol)
- [Az Alap (Base) Barion Pixel implementációja](https://docs.barion.com/Az-Alap-%28Base%29-Barion-Pixel-implementacioja)
- [A Teljes (Full) Barion Pixel implementációja](https://docs.barion.com/A-Teljes-%28Full%29-Barion-Pixel-implementacioja)
- [Az Alap és a Teljes Barion Pixel implementációja WooCommerce platformon](https://docs.barion.com/Az-Alap-%28Base%29-es-a-Teljes-%28Full%29-Barion-Pixel-implementacioja-Woocommerce-e-kereskedelmi-platformon)
- [Barion Pixel API referencia](https://docs.barion.com/Barion-Pixel-API-referencia)
- [Barion Pixel hozzájáruláskezelési követelmények](https://docs.barion.com/Barion-Pixel-hozzajarulaskezelesi_kovetelmenyek)
- [Barion Pixel GYIK](https://docs.barion.com/Frequently_Asked_Questions_about_the_Barion_Pixel) (angolul)

## Kompatibilitás

- **WooCommerce**: A teljes eseménykövetéshez szükséges (az alap pixel nélküle is működik)
- **Barion Payment Gateway** ([woocommerce-barion](https://github.com/szelpe/woocommerce-barion)): Tökéletesen együttműködik — az a bővítmény a fizetéseket kezeli, ez a pixel követést
- **Oldal gyorsítótárazás**: Teljesen kompatibilis (az addToCart kliensoldalú JS-t használ)
- **Cookie bővítmények**: Bármely WP Consent API kompatibilis bővítmény automatikusan működik

## Követelmények

- WordPress 5.0 vagy újabb
- PHP 7.4 vagy újabb
- WooCommerce 5.0+ (a teljes eseménykövetéshez)
- Opcionális: [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) az univerzális cookie-hozzájárulás támogatáshoz

## Közreműködés

Hibajelentéseket, pull requesteket és fordításokat szívesen fogadunk — lásd a [közreműködési útmutatót](hu/contributing.md).

## Licenc

GPL-2.0-or-later — részletekért lásd a [LICENSE](../../LICENSE) fájlt.

## Változásnapló

### 1.0.7
- Javítva: végzetes hiba minden olyan oldalon, amely WooCommerce nélkül futtatta a bővítményt, ha be volt állítva Pixel ID és be volt kapcsolva a teljes követés. A lábléc eseményszkriptje az `is_product()` függvényt hívta, amely csak betöltött WooCommerce mellett létezik, így az oldal `Call to undefined function is_product()` hibával leállt. A WooCommerce eseménykampói mostantól csak akkor kerülnek regisztrálásra, ha a WooCommerce aktív; az alap pixel a dokumentáció szerint továbbra is betöltődik nélküle. A hiba az 1.0.0 óta állt fenn
- Javítva: a Barion Payment Gateway bővítményben is beállított Pixel azonosítóról szóló üzenet minden nyelven angolul jelent meg. A szöveget egy korábbi kiadás átfogalmazta, a fordítások viszont nem követték

### 1.0.6
- Javítva: az `initiateCheckout` és a `setEncryptedEmail` soha nem indult el a WooCommerce Checkout blokkon, amely a WooCommerce 8.3 óta az új boltok alapértelmezése. A bővítmény csak a klasszikus pénztár PHP hookjaira és a `#billing_email` mezőjére figyelt, a blokknak viszont egyik sincs. Mostantól a Cart és a Checkout blokk adattárát olvassa; a klasszikus pénztár működése változatlan
- Javítva: az `addToCart` soha nem indult el a bolt- és kategóriaoldalakon, egyetlen boltban sem. Az eseményszkript csak azokon az oldalakon töltődött be, ahol már várakozott esemény a sorban — archívumoldalon ez soha nem teljesül —, így a kosárba helyezést figyelő kód épp ott hiányzott, ahol a vásárlók ténylegesen kosárba tesznek. A hiba az 1.0.1 óta állt fenn
- Javítva: az `addToCart` mostantól a Product Collection blokk termékgombjaival is működik. Ezek az Interactivity API-n futnak, és sem a klasszikus jQuery eseményt, sem a blokk adattárát nem indítják el, ezért a kosár tartalmát a WooCommerce Store API-ból olvassa a bővítmény

### 1.0.5
- Javítva: a csomagolt magyar, cseh, szlovák, német, horvát, román, szlovén és szerb fordítások soha nem töltődtek be, így a beállítási képernyő angol maradt. A WordPress csak a `wp-content/languages/plugins` mappában keres, hacsak a bővítmény nem regisztrálja a sajátját — ez eddig kimaradt. Mostantól az `init` eseményben regisztrálja a `languages/` mappát

### 1.0.4
- Kompatibilitás: tesztelve WordPress 7.0 és WooCommerce 11.0 alatt
- Változás: a `Requires PHP` 7.2-ről 7.4-re emelve. A WordPress 7.0 megszüntette a PHP 7.2 és 7.3 támogatását, így a 7.2 már nem olyan verzió, amelyen a bővítmény futhat

### 1.0.3
- Javítva: a `setEncryptedEmail` többször is elindult egyetlen pénztároldal-betöltés során
- Javítva: a bp.js `Format of e-mail address or hash is invalid` hibával utasította el azokat az e-mail-címeket, amelyek `+` jelet tartalmaznak a helyi részben, vagy négy betűnél hosszabb TLD-vel rendelkeznek (`.museum`, `.online`). A bővítmény most SHA-1 kivonatot készít az e-mail-címről a böngészőben, mielőtt átadná a bp.js-nek — a Barion Pixel API a sima cím helyett elfogadja az előre kiszámított kivonatot
- Javítva: a részleges beírás (például `x@y`) többé nem jut el a bp.js-hez
- Javítva: a hívás a Barion dokumentációja szerint `bp('identity', 'setEncryptedEmail', ...)` (korábban `'identify'` volt)

Az 1.0.2 verziót az 1.0.3 még a kiadás előtt felváltotta; javításai a fenti listában szerepelnek.

### 1.0.1
- Javítva: egyetlen pixel esemény sem lett elküldve — az események szkriptje azután került sorba, hogy a `wp_print_footer_scripts` már lefutott
- Javítva: a cookie-hozzájárulás automatikus felismerése most a `DOMContentLoaded` után fut, így látja a későn betöltődő hozzájárulás-kezelő bővítmények globális változóit is
- Új: a `setEncryptedEmail` a pénztároldalon is elindul — bejelentkezett felhasználóknál betöltéskor, illetve amikor a vásárló érvényes számlázási e-mail-címet ad meg

### 1.0.0
- Első kiadás
- Alap Barion Pixel (pageView) implementáció
- Teljes eseménykövetés (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail)
- WP Consent API integráció
- Cookie Law Info tartalék integráció
- Adminisztrációs beállítások panel hibakeresési móddal
- Kliensoldalú addToCart (kompatibilis az oldal gyorsítótárazással)
- Változó termék támogatás
- Dupla vásárlás megelőzés
- bp.js dupla betöltés észlelése
