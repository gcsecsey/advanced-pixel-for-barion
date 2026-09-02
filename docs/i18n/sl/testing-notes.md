> 🌐 To je samodejni prevod. Popravki skupnosti so dobrodošli!
>
> [English version](../../testing-notes.md)

# Opombe za testiranje in znane posebnosti

## Preden sklepaš, da piksel ne deluje

### „Testing message“ ni napaka

Odpri konzolo na strani s pikslom in bp.js bo javil bodisi **„Testing message“** bodisi
**„Sending message“**. Barion
[razliko dokumentira](https://docs.barion.com/Implementing_the_Base_Barion_Pixel): sveže vgrajen
piksel še ni pooblaščen za pošiljanje uporabniških podatkov, zato bp.js piše „Testing message“ in
prenaša samo vrsto dogodka. Ko Barion piksel odobri, se to spremeni v „Sending message“.

Vtičnik na to ne vpliva. Če tvoji dogodki v konzoli izgledajo pravilno, Barion pa podatkov ne vidi,
piksel najverjetneje še čaka na odobritev na Barionovi strani — implementacijo pregleda človek,
zato se jim javi, ko bo tvoja končana.

### Pixel ID mora biti pravi

- Najdeš ga v svoji denarnici Barion pod **Merchant Management > Details**. Vsaka trgovina, torej vsak POSKey, ima svoj Pixel ID.
- Oblika je `BP-` + deset znakov + `-` + dve številki. ID, ki se začne z `BPT`, ni Pixel ID in ne bo deloval.
- Peskovnik in produkcija izdata **različna** Pixel ID-ja. Testna stran s produkcijskim ID-jem onesnaži prave podatke; produkcijska stran s peskovniškim ID-jem ne zabeleži nič uporabnega.

Če želiš trgovino za enkratno uporabo, te Barionova stran
[Creating a shop](https://docs.barion.com/Creating_a_shop) vodi skozi peskovnik, kjer se trgovine
odobrijo samodejno.

---

## Posebnosti preverjanja v bp.js med izvajanjem

bp.js preverja podatke dogodkov v brskalniku, na nekaj mestih pa so njegova pravila strožja ali
ohlapnejša, kot bi sklepali po
[referenci dogodkov](https://docs.barion.com/Barion-pixel-event-reference). To se je pokazalo pri
testiranju na okolju staging.

### totalItemPrice: pri contentView zavrnjen, v elementih contents obvezen

- **contentView** (raven dogodek): bp.js `totalItemPrice` **zavrne** z `Invalid key totalItemPrice in contentView event`. Referenca se strinja — ni lastnost contentView.
- Elementi `contents` dogodkov **initiateCheckout** in **purchase**: bp.js ga **zahteva**, sicer javi `Mandatory key totalItemPrice is missing from contents event`. Tudi tu se referenca strinja.

Pravilo palca: `totalItemPrice` je pri ravnih dogodkih neveljaven in znotraj elementov `contents`
obvezen.

### unit je v elementih contents obvezen

Če ga izpustiš, dobiš `Mandatory key unit is missing from contents event`.

### step

Vtičnik pošilja `step: 1` za `addToCart`, `initiateCheckout` in `purchase`. Barion dokumentira `1`
kot korak začetka blagajne, pri `purchase` pa zahteva najvišjo številko koraka, ki jo uporabljaš —
pri enokoračni blagajni prav tako `1`. Za `addToCart` je `step` izbiren.

---

## Način za odpravljanje napak

Vklopi ga v **Nastavitve > Barion Pixel**, da se vsak dogodek zapiše v konzolo brskalnika.

### Kaj iskati

Odpri konzolo (F12 > Konzola) in išči sporočila `[Barion Pixel]`:

```
[Barion Pixel] bp.js loaded by Advanced Pixel for Barion
[Barion Pixel] Base pixel initialized with ID: BP-xxxxxxxxxx-xx
[Barion Pixel] Consent manager detected: WP Consent API
[Barion Pixel] Block surfaces detected (cart store: true, product buttons: false)
[Barion Pixel] Event: contentView { contentType: "Product", ... }
[Barion Pixel] Event: addToCart { contentType: "Product", ... }
[Barion Pixel] Event: initiateCheckout { contents: [...], ... }
[Barion Pixel] Event: purchase { contents: [...], ... }
[Barion Pixel] setEncryptedEmail sent
```

Celoten seznam sporočil o soglasju je v
[Integraciji soglasja s piškotki](cookie-consent.md).

### Napake bp.js

bp.js zapisuje tudi lastne napake preverjanja. Pogoste:

| Napaka | Pomen | Rešitev |
|--------|-------|---------|
| `Mandatory key X is missing from Y event` | Obvezno polje se ne pošilja | Preveri podatke dogodka |
| `Invalid key X in Y event` | Pošilja se polje, ki ga bp.js ne pričakuje | Odstrani polje |
| `Format of e-mail address or hash is invalid` | bp.js je zavrnil vrednost, poslano v `setEncryptedEmail` | Od 1.0.3 vtičnik naslov vnaprej zgosti, zato se to ne bi smelo več pojavljati |

---

## Kontrolni seznam testiranja

Opravi ga v klasični in v blokovni trgovini — za `addToCart`, `initiateCheckout` in
`setEncryptedEmail` uporabljata povsem različni poti kode.

### Stran izdelka (contentView)

1. Odpri stran izdelka z odprto konzolo.
2. Pojavi se `[Barion Pixel] Event: contentView`.
3. Nobene napake bp.js o manjkajočih ali neveljavnih ključih.
4. Prisotna polja: `contentType`, `currency`, `id`, `name`, `quantity`, `unit`, `unitPrice` — in nobenega `totalItemPrice`.

### Dodajanje v košarico (addToCart)

**Stran trgovine ali arhiva, klasičen gumb AJAX:**

1. Na strani trgovine klikni „Dodaj v košarico“.
2. Pojavi se `[Barion Pixel] Event: addToCart`, s `totalItemPrice` in `step: 1`.
3. `unitPrice` ustreza ceni izdelka. Gumb cene ne nosi, zato pride iz Store API. Manjkajoč dogodek `addToCart` pomeni, da poizvedba ni uspela; `0` ni znak napake, saj brezplačen izdelek res nič ne stane.

**Stran izdelka, pošiljanje obrazca:**

1. Klikni „Dodaj v košarico“ in preveri, da se dogodek pošlje, preden stran odide naprej.
2. Pri spremenljivem izdelku: najprej izberi različico, nato preveri, ali je bila uporabljena njena cena.

**Blokovne površine (gumbi Product Collection, blok Cart):**

1. Ob nalaganju se pojavi `[Barion Pixel] Block surfaces detected …`.
2. Dodaj izdelek iz bloka Product Collection — pošlje se en `addToCart` s pravilno količino.
3. Spremeni količino v bloku Cart — noben `addToCart` se ne pošlje.
4. V trgovini z nedecimalno valuto, kot je HUF, preveri, ali je `unitPrice` prava cena in ne njena stotinka.

### Stran blagajne (initiateCheckout)

1. Daj izdelke v košarico in odpri blagajno.
2. Pojavi se `[Barion Pixel] Event: initiateCheckout`.
3. Vsak element `contents` nosi `unit`, `unitPrice` in `totalItemPrice`.
4. `revenue` je vmesni seštevek + davek, brez dostave.
5. `step: 1` je prisoten.
6. Vnesi e-poštni naslov za račun. `setEncryptedEmail sent` se pojavi enkrat na veljaven naslov — ne ob vsakem pritisku tipke in ne pri delnem vnosu, kot je `x@y`.
7. Ponovi na bloku Checkout, kjer e-pošta prihaja iz podatkovne shrambe bloka in ne iz `#billing_email`.

### Zaključek naročila (purchase + setEncryptedEmail)

1. Dokončaj testno naročilo — „Bančno nakazilo“ je za to najpreprostejši način plačila.
2. Pojavi se `[Barion Pixel] Event: purchase`, `revenue` pa ustreza skupnemu znesku naročila.
3. Pojavi se `setEncryptedEmail sent`.
4. Znova naloži stran potrditve — `purchase` se **ne** pošlje znova.
5. Elementi `contents` vsebujejo `unit` in `totalItemPrice`.

### Integracija soglasja

1. Izbriši vse piškotke. To je pomembno — spodnje preverjanje deluje le pri obiskovalcu, ki ga mora pasica šele vprašati.
2. Naloži poljubno stran. Pojavi se `[Barion Pixel] Base pixel initialized` — osnovni piksel se namenoma naloži pred kakršno koli odločitvijo o soglasju.
3. Zaenkrat se ničesar ne dotikaj. Nobenega `grantConsent` se ne sme pojaviti. Barion zavrne integracijo, ki pošlje soglasje ob nalaganju strani.
4. V svoji pasici sprejmi piškotke. Šele zdaj se pojavi `Consent granted (grantConsent)`.
5. Znova naloži. Tokrat se ne pošlje nič, konzola pa pove, da je soglasje ob nalaganju strani že obstajalo. bp.js hrani odgovor v svojem piškotku, Barion ga torej že ima.
6. Prekliči soglasje in preveri, ali se pojavi `Consent rejected (rejectConsent)`.

---

## Pogoste težave

### Dogodki se ne pošiljajo

- **Pixel ID**: v Nastavitve > Barion Pixel mora biti shranjen veljaven ID.
- **Popolno sledenje**: dogodki e-trgovine zahtevajo obkljukano „Omogoči popolno sledenje piksla“.
- **WooCommerce**: popolno sledenje zahteva aktiven WooCommerce.
- **Napake v konzoli**: tudi nepovezana napaka JavaScript lahko prepreči nalaganje bp.js.

### Dvojno nalaganje piksla

`[Barion Pixel] bp.js already loaded by another plugin` pomeni, da je bilo nekaj drugega prej —
Barion Payment Gateway, oznaka v Google Tag Managerju, izsek v temi. To je neškodljivo: vtičnik
preskoči nalaganje skripte in se vseeno inicializira s tvojim Pixel ID-jem. Glej
[Združljivost](compatibility.md).

### Soglasje se ne podeli

Prav zaradi te napake Barion zavrne integracijo Full Pixel, zato jo preveri prvo. Z vklopljenim
Debug Mode ti konzola pove, v katerem primeru si.

- `Consent manager detected: …`, a po sprejemu ni `grantConsent` — upravitelj je bil najden, a ne javlja trženjskega soglasja. Preveri, ali si sprejel prav trženjsko ali oglaševalsko kategorijo svoje pasice.
- `Marketing consent already stood when this page loaded` — nič ni narobe. Testiraš kot vračajoči se obiskovalec. Pobriši piškotke in začni znova pri koraku 1.
- `No consent manager detected`, medtem ko je vtičnik WP Consent API aktiven — API je nameščen, a se tvoja pasica za piškotke pri njem ne registrira, zato javlja soglasje kot dano za vse, vtičnik pa ga prezre. Stran z nastavitvami pove isto. Poveži pasico z API-jem ali funkcije pokliči sam.
- `No consent manager detected` — vtičnik ni našel ničesar za branje. Ta vrstica se pojavi deset sekund po nalaganju strani, ne takoj, ker lahko upravitelj soglasij, ki se streže s CDN-a, toliko zamuja. CookieYes, Complianz, Cookiebot in stari Cookie Law Info se berejo neposredno. Za katero koli drugo pasico namesti [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) ali pokliči `window.wcBarionGrantConsent()` iz povratnega klica za sprejem svoje pasice.
- V konzoli prav nič — osnovna skripta se ni izvedla. Morda jo je blokiral vtičnik za soglasje, ki blokira neznane skripte. Barion zahteva, da se osnovni piksel naloži ne glede na soglasje, zato ga dodaj na seznam dovoljenih v svojem blokatorju.

Ob nalaganju strani, kjer soglasje še ni bilo dano, vtičnik molči. To je namerno:
`rejectConsent` pomeni, da je obiskovalec rekel ne, ne pa da še ni odgovoril.

### purchase se pošlje pri neplačanem naročilu

Pričakovano in dokumentirano pod [purchase](events-reference.md#purchase). Vtičnik sledi strani
potrditve naročila, do katere načini plačila brez povezave pridejo, preden prispe denar.
