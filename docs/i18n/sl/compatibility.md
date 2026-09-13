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

- [Barion Payment Gateway](https://barion.com/en/plugins/) podjetja Barion in [prehod avtorja szelpe](https://github.com/szelpe/woocommerce-barion), ki imata izbirno polje za Pixel ID
- [oznaka v Google Tag Managerju](https://docs.barion.com/Implementing_the_Barion_Pixel_base_code_through_the_Google_Tag_Manager)
- izsek, prilepljen v glavo teme

**Dva osnovna piksla na eni strani sledenja ne poslabšata, ampak ga končata.** Vsaka kopija `bp.js`
doda svoj iframe pod `id="barion_receiver"`, `getElementById()` vrne samo prvega, druga kopija pa
svoje dogodke pošilja prav v ta prvi iframe, preden je ta prevzel status soglasja obiskovalca.
`bp.js` tam vrže napako (`Cannot read properties of undefined (reading 'approvedBase')`) in dogodek
ni nikoli poslan. Izmerjeno v živi trgovini: tri napake in nič dogodkov na strani izdelka.

### Kaj vtičnik stori glede tega

**Barion Payment Gateway.** Svoj piksel izpiše iz `wp_head` s prioriteto 999999, kadar koli je
njegovo polje Pixel ID izpolnjeno — ne glede na njegovo lastno nastavitev sledenja in tudi ob
izklopljenem prehodu. To je za vsem, kar ta vtičnik lahko uvrsti v vrsto, zato tega nobeno
preverjanje v JavaScriptu ne more videti. Ta vtičnik zato uporabi prehodov lastni filter
`woocommerce_barion_disable_tracking` in osnovni piksel postreže sam, vendar le, dokler je Pixel ID
nastavljen tukaj. Ta filter v prehodu nima drugega uporabnika, prehod pa implementira osnovni
piksel in nič več od tega, zato se nič ne izgubi. Stran, ki želi piksel pustiti prehodu, ga lahko
odstrani z `remove_filter()` in namesto tega tukaj izbriše Pixel ID.

**Vse drugo.** Pred nalaganjem `bp.js` vtičnik preveri `window.bp`. Če ga je kateri koli drug vir
določil prej, preskoči nalaganje skripte in pošlje samo klic `init`. V načinu za odpravljanje napak
to javi sporočilo `[Barion Pixel] bp.js already loaded by another plugin`.

Izsek, ki se izvede *za* tem vtičnikom — oznaka v Google Tag Managerju, izsek v glavi teme — je
izven dosega: `bp.js` naloži znova, ne glede na to, kaj ta vtičnik naredi. V načinu za odpravljanje
napak se ta primer pokaže po nalaganju strani in vtičnik opozori, da nekaj drugega nalaga drugo
kopijo `bp.js`.

**Priporočilo:** Pixel ID imej na enem mestu, tukaj. Izprazni polje v prehodu in odstrani morebitno
oznako v Google Tag Managerju ali izsek v temi. Res se je treba izogniti dvema različnima Pixel
ID-jema na eni strani — dvojno skripto vtičnik lahko prepreči, dvojne identitete ne.

Ko ima Pixel ID nastavljen tudi Barion Payment Gateway, stran z nastavitvami na to opozori. Oba
vtičnika tako ali tako delujeta naprej: tisti skrbi za plačila, ta za sledenje.

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
