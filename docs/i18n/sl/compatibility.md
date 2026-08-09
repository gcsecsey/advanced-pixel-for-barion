> 🌐 To je samodejni prevod. Popravki skupnosti so dobrodošli!
>
> [English version](../../compatibility.md)

# Združljivost vtičnika

## WooCommerce

**Potreben za popolno sledenje dogodkov.** Osnovni piksel deluje brez WooCommerce, toda vsi dogodki e-trgovine (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail) zahtevajo WooCommerce.

| Različica | Status |
|-----------|--------|
| WooCommerce 5.0+ | Podprto |
| WooCommerce 11.0 | Preizkušeno |

### Bloka Cart in Checkout

Podprta od 1.0.6. Bloka ne sprožita ne klasičnih hookov PHP ne selektorjev DOM, ki jih je vtičnik
uporabljal prej, zato na blokovnih površinah bere podatke WooCommerce neposredno: košarico iz
Store API za `addToCart` in podatkovno shrambo `wc/store/cart` za e-pošto na blagajni.

**Znana omejitev.** Dogodek `purchase` teče prek `woocommerce_thankyou`, ki ga v blokovni predlogi
Order Confirmation sproži blok „Dodatne informacije“. Če ta blok odstraniš iz predloge, sledenje
nakupom tiho preneha. Pusti ga v predlogi.

---

## Drugi viri osnovnega piksla

Barion dokumentira več načinov, kako osnovni piksel pride na stran, in v eni trgovini se jih zlahka
nabere več:

- [Barion Payment Gateway](https://github.com/szelpe/woocommerce-barion) avtorja szelpe in drugi Barionovi plačilni vtičniki, ki imajo izbirno polje za Pixel ID
- [oznaka v Google Tag Managerju](https://docs.barion.com/Implementing_the_Barion_Pixel_base_code_through_the_Google_Tag_Manager)
- izsek, prilepljen v glavo teme

Vtičnik pred nalaganjem `bp.js` preveri `window.bp` in `window.BarionAnalyticsObject`. Če sta oba
že tam, preskoči nalaganje skripte in pošlje samo lasten klic `init`, tako da se piksel nikoli ne
naloži dvakrat. V načinu za odpravljanje napak to javi sporočilo
`[Barion Pixel] bp.js already loaded by another plugin`.

**Priporočilo:** Pixel ID imej na enem mestu. Če uporabljaš tudi Barionov plačilni prehod, nastavi
ID tukaj in njegovo polje pusti prazno; če osnovni piksel že nalagaš prek Google Tag Managerja, to
oznako odstrani. Res se je treba izogniti dvema različnima Pixel ID-jema na eni strani — dvojno
skripto vtičnik lahko prepreči, dvojne identitete ne.

Ko ima Pixel ID nastavljen tudi Barion Payment Gateway, stran z nastavitvami prikaže informativno
obvestilo. Oba vtičnika tako ali tako delujeta naprej: tisti skrbi za plačila, ta za sledenje.

---

## Vtičniki za medpomnjenje strani

Vtičnik je popolnoma združljiv z medpomnjenjem strani:

| Dogodek | Implementacija | Vpliv medpomnjenja |
|---------|---------------|-------------------|
| contentView | Strežniška stran (stran izdelka) | Strani izdelkov navadno niso v predpomnilniku ali se razlikujejo glede na izdelek |
| addToCart | **JavaScript na strani odjemalca** | Brez težav z medpomnjenjem — JS se sproži v brskalniku |
| initiateCheckout | Strežniška stran (stran blagajne) | Blagajna ni v predpomnilniku (vsebuje podatke o seji uporabnika) |
| purchase | Strežniška stran (stran zahvale) | Strani zahvale niso v predpomnilniku (edinstvene za vsako naročilo) |

Dogodek addToCart je bil specifično implementiran na strani odjemalca (namesto z uporabo sej PHP) za delovanje z gostovanjem WordPress.com in agresivnimi nastavitvami medpomnjenja strani.

**Združljivo z:** WP Super Cache, W3 Total Cache, LiteSpeed Cache, gostovanjem WordPress.com, Cloudflare in podobnimi rešitvami za medpomnjenje.

---

## Vtičniki za soglasje s piškotki

Vtičnik podpira vse vtičnike za soglasje s piškotki, ki implementirajo [WP Consent API](https://wordpress.org/plugins/wp-consent-api/). Glejte [Integracija soglasja s piškotki](cookie-consent.md) za podrobnosti.

**Samodejno podprto:**

- CookieYes (1,5M+ namestitev)
- Complianz (1M+ namestitev)
- Cookie Notice by dFactory (1M+ namestitev)
- GDPR Cookie Compliance by Moove (300K+ namestitev)
- Real Cookie Banner (100K+ namestitev)

**Neposredna nadomestna integracija:**

- Cookie Law Info / CookieYes (deluje tudi brez WP Consent API)

---

## Različica WordPress

| Različica | Status |
|-----------|--------|
| WordPress 5.0+ | Potrebno |
| WordPress 7.0 | Preizkušeno |

## Različica PHP

| Različica | Status |
|-----------|--------|
| PHP 7.4+ | Potrebno |
| PHP 8.x | Združljivo |
