> 🌐 Toto je automatický překlad. Komunitní opravy jsou vítány!
>
> [English version](../../compatibility.md)

# Kompatibilita pluginů

## WooCommerce

**Vyžadováno pro kompletní sledování událostí.** Základní pixel funguje bez WooCommerce, ale všechny e-commerce události (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail) vyžadují WooCommerce.

| Verze | Stav |
|-------|------|
| WooCommerce 5.0+ | Podporováno |
| WooCommerce 11.0 | Testováno |

### Bloky Cart a Checkout

Podporováno od verze 1.0.6. Bloky nespouštějí ani klasické PHP hooky, ani DOM selektory, které
plugin používal dřív, takže na blokových plochách čte data WooCommerce přímo: košík ze Store API
pro `addToCart` a datové úložiště `wc/store/cart` pro e-mail v pokladně.

**Známé omezení.** Událost `purchase` běží přes `woocommerce_thankyou`, kterou v blokové šabloně
Order Confirmation vyvolává blok „Další informace“. Pokud tento blok ze šablony odstraníte,
sledování nákupů se tiše zastaví. Nechte ho v šabloně.

---

## Další zdroje základního pixelu

Barion dokumentuje několik způsobů, jak dostat základní pixel na stránku, a v jednom obchodě se
jich snadno sejde víc:

- [Barion Payment Gateway](https://github.com/szelpe/woocommerce-barion) od szelpe a další platební pluginy pro Barion, které mají volitelné pole pro ID Pixelu
- [tag v Google Tag Manageru](https://docs.barion.com/Implementing_the_Barion_Pixel_base_code_through_the_Google_Tag_Manager)
- úryvek vložený do hlavičky šablony

Plugin před načtením `bp.js` ověří `window.bp` a `window.BarionAnalyticsObject`. Pokud jsou obě už
k dispozici, načtení skriptu přeskočí a odešle jen vlastní volání `init`, takže se pixel nikdy
nenačte dvakrát. V režimu ladění to hlásí zpráva
`[Barion Pixel] bp.js already loaded by another plugin`.

**Doporučení:** držte ID Pixelu na jednom místě. Pokud provozujete i platební bránu Barionu,
nastavte ID zde a pole v bráně nechte prázdné; pokud základní pixel už načítáte přes Google Tag
Manager, ten tag odstraňte. Skutečně nežádoucí je případ dvou různých ID Pixelu na jedné stránce
— dvojí skript plugin potlačit umí, dvojí identitu ne.

Když má ID Pixelu nastavené i Barion Payment Gateway, stránka nastavení zobrazí informační
upozornění. Oba pluginy fungují dál tak jako tak: ten se stará o platby, tento o sledování.

---

## Pluginy pro ukládání stránek do mezipaměti

Plugin je plně kompatibilní s ukládáním stránek do mezipaměti:

| Událost | Implementace | Dopad mezipaměti |
|---------|-------------|------------------|
| contentView | Na straně serveru (stránka produktu) | Stránky produktů obvykle nejsou ukládány do mezipaměti, nebo se liší podle produktu |
| addToCart | **JavaScript na straně klienta** | Žádné problémy s mezipaměti — JS se spouští v prohlížeči |
| initiateCheckout | Na straně serveru (stránka pokladny) | Pokladna není ukládána do mezipaměti (obsahuje data uživatelské relace) |
| purchase | Na straně serveru (stránka s poděkováním) | Stránky s poděkováním nejsou ukládány do mezipaměti (unikátní pro každou objednávku) |

Událost addToCart byla záměrně implementována na straně klienta (místo použití PHP relací), aby fungovala s hostingem WordPress.com a agresivním ukládáním stránek do mezipaměti.

**Kompatibilní s:** WP Super Cache, W3 Total Cache, LiteSpeed Cache, hostingem WordPress.com, Cloudflare a podobnými řešeními pro ukládání do mezipaměti.

---

## Pluginy pro souhlas s cookies

Plugin podporuje všechny pluginy pro souhlas s cookies, které implementují [WP Consent API](https://wordpress.org/plugins/wp-consent-api/). Viz [Integrace souhlasu s cookies](cookie-consent.md) pro podrobnosti.

**Automaticky podporováno:**

- CookieYes (1,5 M+ instalací)
- Complianz (1 M+ instalací)
- Cookie Notice od dFactory (1 M+ instalací)
- GDPR Cookie Compliance od Moove (300 K+ instalací)
- Real Cookie Banner (100 K+ instalací)

**Přímá záložní integrace:**

- Cookie Law Info / CookieYes (funguje i bez WP Consent API)

---

## Verze WordPressu

| Verze | Stav |
|-------|------|
| WordPress 5.0+ | Vyžadováno |
| WordPress 7.0 | Testováno |

## Verze PHP

| Verze | Stav |
|-------|------|
| PHP 7.4+ | Vyžadováno |
| PHP 8.x | Kompatibilní |
