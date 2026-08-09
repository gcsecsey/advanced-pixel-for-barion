> 🌐 Ez egy automatikus fordítás. Közösségi javítások szívesen fogadottak!
>
> [English version](../../testing-notes.md)

# Tesztelési megjegyzések és ismert sajátosságok

## Mielőtt arra jutnál, hogy a pixel hibás

### A „Testing message” nem hibaüzenet

Nyisd meg a konzolt egy olyan oldalon, ahol fut a pixel, és a bp.js vagy **„Testing message”**,
vagy **„Sending message”** üzenetet ír ki. A Barion
[dokumentálja a különbséget](https://docs.barion.com/Implementing_the_Base_Barion_Pixel): a
frissen bekötött pixel még nincs feljogosítva felhasználói adatok küldésére, ezért a bp.js
„Testing message” üzenetet ír, és csak az esemény típusát továbbítja. Amint a Barion
jóváhagyja a pixelt, ez „Sending message” üzenetre vált.

Ezen a bővítmény nem változtat. Ha az események helyesnek látszanak a konzolban, a Barion mégsem
lát adatot, akkor a pixel nagy valószínűséggel még jóváhagyásra vár a Barion oldalán — a
implementációt ember nézi át, ezért vedd fel velük a kapcsolatot, ha kész vagy.

### A Pixel azonosítónak a megfelelőnek kell lennie

- A Barion tárcádban, a **Merchant Management > Details** oldalon találod. Minden boltnak, azaz minden POSKey-nek saját Pixel azonosítója van.
- A formátum: `BP-` + tíz karakter + `-` + két számjegy. A `BPT` kezdetű azonosító nem Pixel azonosító, azzal nem fog működni.
- A sandbox és az éles környezet **eltérő** Pixel azonosítót ad ki. Az éles azonosítóra állított teszt oldal beszennyezi a valós adatokat; a sandbox azonosítóra állított éles oldal pedig semmi hasznosat nem rögzít.

Ha eldobható boltot szeretnél teszteléshez, a Barion
[Creating a shop](https://docs.barion.com/Creating_a_shop) oldala végigvezet a sandboxon, ahol a
boltokat automatikusan jóváhagyják.

---

## A bp.js futásidejű ellenőrzési sajátosságai

A bp.js a böngészőben ellenőrzi az eseményadatokat, és néhány ponton szigorúbb vagy engedékenyebb
a szabálya, mint amit az
[eseményreferencia](https://docs.barion.com/Barion-pixel-event-reference) sugall. Ezek teszt
környezetben derültek ki.

### totalItemPrice: contentView-nál tiltott, contents elemekben kötelező

- **contentView** (egyszerű esemény): a bp.js **elutasítja** a `totalItemPrice` mezőt `Invalid key totalItemPrice in contentView event` hibával. A referencia egyetért — nem contentView tulajdonság.
- **initiateCheckout** és **purchase** `contents` elemek: a bp.js **megköveteli**, elhagyása esetén `Mandatory key totalItemPrice is missing from contents event` hibát ad. A referencia itt is egyetért.

Ökölszabály: a `totalItemPrice` az egyszerű eseményeknél érvénytelen, a `contents` elemeken belül
kötelező.

### A unit kötelező a contents elemekben

Elhagyása esetén: `Mandatory key unit is missing from contents event`.

### step

A bővítmény `step: 1` értéket küld az `addToCart`, `initiateCheckout` és `purchase` eseményeknél.
A Barion az `1` értéket dokumentálja a pénztár kezdő lépéseként, és a `purchase` eseménynél a
használt legmagasabb lépésszámot kéri — egylépéses pénztárnál ez szintén `1`. Az `addToCart`
eseménynél a `step` opcionális.

---

## Hibakeresési mód

Kapcsold be a **Beállítások > Barion Pixel** oldalon, hogy minden esemény a böngészőkonzolba
kerüljön.

### Mit keress

Nyisd meg a konzolt (F12 > Konzol), és keresd a `[Barion Pixel]` üzeneteket:

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

A hozzájárulással kapcsolatos üzenetek teljes listája a
[Cookie-hozzájárulás integrációban](cookie-consent.md) található.

### bp.js hibák

A bp.js a saját ellenőrzési hibáit is naplózza. A gyakoriak:

| Hiba | Jelentés | Megoldás |
|------|----------|----------|
| `Mandatory key X is missing from Y event` | Egy kötelező mező nem megy el | Ellenőrizd az esemény adatait |
| `Invalid key X in Y event` | Olyan mező megy el, amelyet a bp.js nem vár | Vedd ki a mezőt |
| `Format of e-mail address or hash is invalid` | A bp.js elutasította a `setEncryptedEmail` értékét | Az 1.0.3 óta a bővítmény előre kivonatolja a címet, így ennek nem szabad előfordulnia |

---

## Tesztelési ellenőrzőlista

Futtasd le klasszikus és blokkos boltban is — a kettő teljesen eltérő kódutat használ az
`addToCart`, az `initiateCheckout` és a `setEncryptedEmail` eseményhez.

### Termékoldal (contentView)

1. Nyiss meg egy termékoldalt nyitott konzollal.
2. Megjelenik a `[Barion Pixel] Event: contentView` üzenet.
3. Nincs bp.js hiba hiányzó vagy érvénytelen kulcsról.
4. Jelen lévő mezők: `contentType`, `currency`, `id`, `name`, `quantity`, `unit`, `unitPrice` — és nincs `totalItemPrice`.

### Kosárba helyezés (addToCart)

**Bolt- vagy archívumoldal, klasszikus AJAX gomb:**

1. Kattints a „Kosárba” gombra a bolt oldalán.
2. Megjelenik a `[Barion Pixel] Event: addToCart` üzenet `totalItemPrice` és `step: 1` értékkel.

**Termékoldal, űrlapbeküldés:**

1. Kattints a „Kosárba” gombra, és ellenőrizd, hogy az esemény az oldal elhagyása előtt elindul.
2. Változó terméknél: előbb válassz variációt, majd ellenőrizd, hogy a variáció ára ment el.

**Blokkfelületek (Product Collection gombok, Cart blokk):**

1. Betöltéskor megjelenik a `[Barion Pixel] Block surfaces detected …` üzenet.
2. Adj hozzá terméket egy Product Collection blokkból — egy `addToCart` indul el a helyes mennyiséggel.
3. Módosítsd a mennyiséget a Cart blokkban — nem indul `addToCart`.
4. Nem tizedes pénznemű boltban, például HUF esetén ellenőrizd, hogy a `unitPrice` a valódi ár, nem annak a századrésze.

### Pénztároldal (initiateCheckout)

1. Tegyél termékeket a kosárba, és nyisd meg a pénztárt.
2. Megjelenik a `[Barion Pixel] Event: initiateCheckout` üzenet.
3. A `contents` elemek mindegyike tartalmaz `unit`, `unitPrice` és `totalItemPrice` mezőt.
4. A `revenue` a nettó összeg + adó, szállítás nélkül.
5. A `step: 1` jelen van.
6. Írj be egy számlázási e-mail-címet. A `setEncryptedEmail sent` üzenet érvényes címenként egyszer jelenik meg — nem minden leütésre, és részleges bevitelnél, például `x@y` esetén sem.
7. Ismételd meg a Checkout blokkon, ahol az e-mail-cím a blokk adattárából jön, nem a `#billing_email` mezőből.

### Rendelés lezárása (purchase + setEncryptedEmail)

1. Adj le tesztrendelést — az „Átutalás” a legegyszerűbb fizetési mód ehhez.
2. Megjelenik a `[Barion Pixel] Event: purchase` üzenet, a `revenue` egyezik a rendelés végösszegével.
3. Megjelenik a `setEncryptedEmail sent` üzenet.
4. Töltsd újra a visszaigazoló oldalt — a `purchase` **nem** indul el újra.
5. A `contents` elemek tartalmaznak `unit` és `totalItemPrice` mezőt.

### Hozzájárulás integráció

1. Töröld az összes cookie-t.
2. Tölts be egy oldalt. Megjelenik a `[Barion Pixel] Base pixel initialized` üzenet — az alap pixel szándékosan minden hozzájárulási döntés előtt betöltődik.
3. Fogadd el a cookie-kat a sávban. Megjelenik a `Consent granted (grantConsent)` üzenet.
4. Töltsd újra — a hozzájárulás sáv nélkül, betöltéskor újra megadódik.
5. Vond vissza a hozzájárulást, és ellenőrizd, hogy megjelenik a `Consent rejected (rejectConsent)` üzenet.

---

## Gyakori problémák

### Nem indulnak el az események

- **Pixel azonosító**: érvényes azonosítót kell menteni a Beállítások > Barion Pixel oldalon.
- **Teljes követés**: az e-kereskedelmi eseményekhez be kell jelölni a „Teljes Pixel követés engedélyezése” opciót.
- **WooCommerce**: a teljes követéshez aktív WooCommerce kell.
- **Konzolhibák**: egy független JavaScript hiba is megakadályozhatja a bp.js betöltését.

### Dupla pixel betöltés

A `[Barion Pixel] bp.js already loaded by another plugin` azt jelenti, hogy valami más — a Barion
Payment Gateway, egy Google Tag Manager tag, egy sablonba illesztett kódrészlet — megelőzte.
Ez ártalmatlan: a bővítmény kihagyja a szkript betöltését, és a te Pixel azonosítóddal
inicializál. Lásd a [Kompatibilitást](compatibility.md).

### Nem adódik meg a hozzájárulás

- **WP Consent API**: a WP Consent API bővítménynek telepítve kell lennie, és a cookie-bővítményednek támogatnia kell.
- **Cookie Law Info**: a bővítménynek aktívnak kell lennie, és a `CLI` globálisnak elérhetőnek.
- **Kézi**: hívd a `window.wcBarionGrantConsent()` függvényt a hozzájárulás-kezelőd visszahívásából.

### A purchase fizetetlen rendelésnél is elindul

Várt viselkedés, lásd a [purchase](events-reference.md#purchase) szakaszt. A bővítmény a
visszaigazoló oldalt követi, amelyet az offline fizetési módok a pénz beérkezése előtt érnek el.
