> 🌐 Ez egy automatikus fordítás. Közösségi javítások szívesen fogadottak!
>
> [English version](../../compatibility.md)

# Bővítmény kompatibilitás

## WooCommerce

**A teljes eseménykövetéshez szükséges.** Az alap pixel WooCommerce nélkül is működik, de minden e-kereskedelmi esemény (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail) WooCommerce-t igényel.

| Verzió | Állapot |
|--------|---------|
| WooCommerce 5.0+ | Támogatott |
| WooCommerce 11.0 | Tesztelve |

### Cart és Checkout blokk

Az 1.0.6 óta támogatott. A blokkok sem a klasszikus PHP hookokat, sem a bővítmény korábbi
DOM-szelektorait nem indítják el, ezért blokkfelületeken közvetlenül a WooCommerce adataiból
dolgozik: az `addToCart` eseményhez a Store API kosarából, a pénztár e-mail-címéhez a
`wc/store/cart` adattárból.

**Ismert korlát.** A `purchase` esemény a `woocommerce_thankyou` hookon fut, amelyet a blokkos
Order Confirmation sablonban a „További információk” blokk vált ki. Ha ezt a blokkot kiveszed a
sablonból, a vásárláskövetés csendben leáll. Hagyd benne a sablonban.

---

## Az alap pixel egyéb forrásai

A Barion több módot dokumentál az alap pixel oldalra juttatására, és egy boltban könnyen
összejöhet ezekből több is:

- a Barion saját [Barion Payment Gateway](https://barion.com/en/plugins/) bővítménye és a [szelpe által fejlesztett átjáró](https://github.com/szelpe/woocommerce-barion), amelyekben van opcionális Pixel azonosító mező
- egy [Google Tag Manager tag](https://docs.barion.com/Implementing_the_Barion_Pixel_base_code_through_the_Google_Tag_Manager)
- a sablon fejlécébe illesztett kódrészlet

**Két alap pixel egy oldalon nem rontja a követést, hanem megszünteti.** A `bp.js` minden példánya
saját iframe-et fűz be `id="barion_receiver"` néven, a `getElementById()` csak az elsőt adja
vissza, és a második példány abba az első iframe-be küldi az eseményeit, mielőtt az lekérte volna a
látogató hozzájárulási állapotát. A `bp.js` ott hibára fut
(`Cannot read properties of undefined (reading 'approvedBase')`), és az esemény soha nem megy el.
Élő boltban mérve: három hiba és nulla esemény egy termékoldalon.

### Mit tesz ez ellen a bővítmény

**A Barion Payment Gateway.** A pixelét a `wp_head` hookból, 999999-es prioritással írja ki,
valahányszor a Pixel azonosító mezője ki van töltve — függetlenül a saját követési beállításától,
és akkor is, ha maga az átjáró ki van kapcsolva. Ez minden után történik, amit ez a bővítmény be
tud sorolni, így semmilyen JavaScript ellenőrzés nem láthatja. Ezért ez a bővítmény alkalmazza az
átjáró saját `woocommerce_barion_disable_tracking` szűrőjét, és maga szolgálja ki az alap pixelt,
de csak addig, amíg itt be van állítva Pixel azonosító. Annak a szűrőnek nincs más felhasználója az
átjáróban, és az átjáró az alap pixelt valósítja meg, azon túl semmit, így semmi nem vész el. Ha
egy oldal azt szeretné, hogy a pixel az átjárónál maradjon, `remove_filter()` hívással kiveheti a
szűrőt, és inkább itt törölje a Pixel azonosítót.

**Minden más.** A `bp.js` betöltése előtt a bővítmény megnézi a `window.bp` értékét. Ha bármelyik
másik forrás már definiálta, kihagyja a szkript betöltését, és csak az `init` hívást küldi el.
Hibakeresési módban ezt a `[Barion Pixel] bp.js already loaded by another plugin` üzenet jelzi.

Az a kódrészlet, amelyik *ez után* a bővítmény után fut — egy Google Tag Manager tag, egy
kódrészlet a sablon fejlécében —, elérhetetlen: bármit tesz ez a bővítmény, újra betölti a
`bp.js`-t. Hibakeresési módban ez az eset az oldal betöltése után derül ki, és a bővítmény
figyelmeztet, hogy valami más egy második `bp.js` példányt tölt be.

**Javaslat:** a Pixel azonosítót tartsd egy helyen, itt. Töröld az átjáró mezőjét, és vedd ki a
Google Tag Manager taget vagy a sablonba illesztett kódrészletet. Az igazán kerülendő eset a két
különböző Pixel azonosító egy oldalon — a bővítmény a dupla szkriptet el tudja kerülni, a dupla
identitást nem.

Ha a Barion Payment Gateway bővítményben is be van állítva Pixel azonosító, a beállítási oldal
jelzi ezt. Mindkét bővítmény tovább működik: az a fizetéseket kezeli, ez a követést.

---

## Oldal gyorsítótárazó bővítmények

A bővítmény teljesen kompatibilis az oldal gyorsítótárazással:

| Esemény | Megvalósítás | Gyorsítótárazás hatása |
|---------|--------------|------------------------|
| contentView | Szerveroldali (termékoldal) | A termékoldalak jellemzően nem kerülnek gyorsítótárba, vagy termékenként eltérőek |
| addToCart | **Kliensoldalú JavaScript** | Nincsenek gyorsítótárazási problémák — a JS a böngészőben aktiválódik |
| initiateCheckout | Szerveroldali (pénztár oldal) | A pénztár nem kerül gyorsítótárba (felhasználói munkamenet adatokat tartalmaz) |
| purchase | Szerveroldali (köszönő oldal) | A köszönő oldalak nem kerülnek gyorsítótárba (rendelésenként egyediek) |

Az addToCart esemény kifejezetten kliensoldalon lett megvalósítva (PHP munkamenetek helyett), hogy működjön WordPress.com tárhelyen és agresszív oldal gyorsítótárazási beállítások esetén is.

**Kompatibilis:** WP Super Cache, W3 Total Cache, LiteSpeed Cache, WordPress.com tárhely, Cloudflare és hasonló gyorsítótárazási megoldásokkal.

---

## Cookie hozzájárulás bővítmények

A bővítmény támogatja az összes cookie hozzájárulás bővítményt, amelyek megvalósítják a [WP Consent API](https://wordpress.org/plugins/wp-consent-api/)-t. Részletekért lásd a [Cookie-hozzájárulás integráció](cookie-consent.md) dokumentumot.

**Automatikusan támogatott:**

- CookieYes (1,5M+ telepítés)
- Complianz (1M+ telepítés)
- Cookie Notice by dFactory (1M+ telepítés)
- GDPR Cookie Compliance by Moove (300K+ telepítés)
- Real Cookie Banner (100K+ telepítés)

**Közvetlen tartalék integráció:**

- Cookie Law Info / CookieYes (WP Consent API nélkül is működik)

---

## WordPress verzió

| Verzió | Állapot |
|--------|---------|
| WordPress 5.0+ | Szükséges |
| WordPress 7.0 | Tesztelve |

## PHP verzió

| Verzió | Állapot |
|--------|---------|
| PHP 7.4+ | Szükséges |
| PHP 8.x | Kompatibilis |
