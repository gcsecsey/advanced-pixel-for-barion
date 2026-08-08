> 🌐 Ovo je automatski prijevod. Ispravci zajednice su dobrodošli!
>
> [English version](../../testing-notes.md)

# Napomene za testiranje i poznate posebnosti

## Prije nego zaključiš da pixel ne radi

### „Testing message“ nije pogreška

Otvori konzolu na stranici s pixelom i bp.js će javiti ili **„Testing message“** ili
**„Sending message“**. Barion
[dokumentira razliku](https://docs.barion.com/Implementing_the_Base_Barion_Pixel): svježe
postavljen pixel još nije ovlašten slati korisničke podatke, pa bp.js piše „Testing message“ i
prenosi samo vrstu događaja. Kada Barion ovlasti pixel, to se mijenja u „Sending message“.

Dodatak na to ne utječe. Ako tvoji događaji u konzoli izgledaju ispravno, a Barion ne vidi
podatke, pixel najvjerojatnije još čeka odobrenje na Barionovoj strani — implementaciju pregledava
čovjek, pa im se javi kada tvoja bude gotova.

### Pixel ID mora biti onaj pravi

- Nalazi se u tvojem Barion novčaniku pod **Merchant Management > Details**. Svaka trgovina, dakle svaki POSKey, ima svoj Pixel ID.
- Format je `BP-` + deset znakova + `-` + dvije znamenke. ID koji počinje s `BPT` nije Pixel ID i neće raditi.
- Sandbox i produkcija izdaju **različite** Pixel ID-ove. Testna stranica s produkcijskim ID-om zagađuje stvarne podatke; produkcijska stranica sa sandbox ID-om ne bilježi ništa korisno.

Ako želiš trgovinu za jednokratnu upotrebu, Barionova stranica
[Creating a shop](https://docs.barion.com/Creating_a_shop) vodi te kroz sandbox, gdje se trgovine
odobravaju automatski.

---

## Posebnosti provjere u bp.js tijekom izvođenja

bp.js provjerava podatke događaja u pregledniku, a na nekoliko mjesta su njegova pravila stroža ili
blaža nego što [referenca događaja](https://docs.barion.com/Barion-pixel-event-reference)
sugerira. Ovo je otkriveno tijekom testiranja na staging okruženju.

### totalItemPrice: odbijen kod contentViewa, obavezan u elementima contents

- **contentView** (ravni događaj): bp.js **odbija** `totalItemPrice` uz `Invalid key totalItemPrice in contentView event`. Referenca se slaže — nije svojstvo contentViewa.
- Elementi `contents` događaja **initiateCheckout** i **purchase**: bp.js ga **zahtijeva**, inače javlja `Mandatory key totalItemPrice is missing from contents event`. I ovdje se referenca slaže.

Pravilo palca: `totalItemPrice` je nevaljan na ravnim događajima i obavezan unutar elemenata
`contents`.

### unit je obavezan u elementima contents

Ako ga izostaviš, dobiješ `Mandatory key unit is missing from contents event`.

### step

Dodatak šalje `step: 1` za `addToCart`, `initiateCheckout` i `purchase`. Barion dokumentira `1` kao
korak početka naplate, a kod `purchase` traži najviši broj koraka koji koristiš — kod
jednokoračne naplate također `1`. Za `addToCart` je `step` neobavezan.

---

## Način za otklanjanje pogrešaka

Uključi ga u **Postavke > Barion Pixel** da se svaki događaj zapiše u konzolu preglednika.

### Što tražiti

Otvori konzolu (F12 > Konzola) i traži poruke `[Barion Pixel]`:

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

Potpun popis poruka o pristanku nalazi se u
[Integraciji pristanka na kolačiće](cookie-consent.md).

### Pogreške bp.js

bp.js zapisuje i vlastite pogreške provjere. Uobičajene:

| Pogreška | Značenje | Rješenje |
|----------|----------|----------|
| `Mandatory key X is missing from Y event` | Obavezno polje se ne šalje | Provjeri podatke događaja |
| `Invalid key X in Y event` | Šalje se polje koje bp.js ne očekuje | Ukloni polje |
| `Format of e-mail address or hash is invalid` | bp.js je odbio vrijednost proslijeđenu u `setEncryptedEmail` | Od 1.0.3 dodatak adresu unaprijed hashira, pa se ovo više ne bi trebalo pojavljivati |

---

## Kontrolni popis testiranja

Prođi ga i u klasičnoj i u blokovskoj trgovini — za `addToCart`, `initiateCheckout` i
`setEncryptedEmail` koriste posve različite putove koda.

### Stranica proizvoda (contentView)

1. Otvori stranicu proizvoda s otvorenom konzolom.
2. Pojavi se `[Barion Pixel] Event: contentView`.
3. Nema pogrešaka bp.js o nedostajućim ili nevaljanim ključevima.
4. Prisutna polja: `contentType`, `currency`, `id`, `name`, `quantity`, `unit`, `unitPrice` — i nema `totalItemPrice`.

### Dodavanje u košaricu (addToCart)

**Stranica trgovine ili arhive, klasičan AJAX gumb:**

1. Na stranici trgovine klikni „Dodaj u košaricu“.
2. Pojavi se `[Barion Pixel] Event: addToCart`, s `totalItemPrice` i `step: 1`.

**Stranica proizvoda, slanje obrasca:**

1. Klikni „Dodaj u košaricu“ i provjeri da se događaj šalje prije nego stranica ode dalje.
2. Kod varijabilnog proizvoda: prvo odaberi varijaciju, pa provjeri je li upotrijebljena njezina cijena.

**Blokovske plohe (gumbi Product Collection, blok Cart):**

1. Pri učitavanju se pojavi `[Barion Pixel] Block surfaces detected …`.
2. Dodaj proizvod iz bloka Product Collection — šalje se jedan `addToCart` s ispravnom količinom.
3. Promijeni količinu u bloku Cart — nijedan `addToCart` se ne šalje.
4. U trgovini s nedecimalnom valutom poput HUF-a provjeri je li `unitPrice` stvarna cijena, a ne njezin stoti dio.

### Stranica naplate (initiateCheckout)

1. Stavi artikle u košaricu i otvori naplatu.
2. Pojavi se `[Barion Pixel] Event: initiateCheckout`.
3. Svaki element `contents` nosi `unit`, `unitPrice` i `totalItemPrice`.
4. `revenue` je međuzbroj + porez, bez dostave.
5. `step: 1` je prisutan.
6. Upiši adresu e-pošte za naplatu. `setEncryptedEmail sent` pojavljuje se jednom po valjanoj adresi — ne pri svakom pritisku tipke i ne kod djelomičnog unosa poput `x@y`.
7. Ponovi na bloku Checkout, gdje e-pošta dolazi iz spremišta podataka bloka, a ne iz `#billing_email`.

### Završetak narudžbe (purchase + setEncryptedEmail)

1. Dovrši testnu narudžbu — „Bankovni prijenos“ je za to najjednostavniji način plaćanja.
2. Pojavi se `[Barion Pixel] Event: purchase`, a `revenue` odgovara ukupnom iznosu narudžbe.
3. Pojavi se `setEncryptedEmail sent`.
4. Ponovno učitaj stranicu potvrde — `purchase` se **ne** šalje ponovno.
5. Elementi `contents` sadrže `unit` i `totalItemPrice`.

### Integracija pristanka

1. Obriši sve kolačiće.
2. Učitaj bilo koju stranicu. Pojavi se `[Barion Pixel] Base pixel initialized` — osnovni pixel se namjerno učitava prije bilo kakve odluke o pristanku.
3. Prihvati kolačiće u svojoj traci. Pojavi se `Consent granted (grantConsent)`.
4. Ponovno učitaj — pristanak se pri učitavanju daje ponovno, bez trake.
5. Povuci pristanak i provjeri pojavljuje li se `Consent rejected (rejectConsent)`.

---

## Česti problemi

### Događaji se ne šalju

- **Pixel ID**: u Postavke > Barion Pixel mora biti spremljen valjan ID.
- **Potpuno praćenje**: e-trgovinski događaji traže označeno „Omogući potpuno praćenje Pixelom“.
- **WooCommerce**: potpuno praćenje traži aktivan WooCommerce.
- **Pogreške u konzoli**: i nepovezana JavaScript pogreška može spriječiti učitavanje bp.js.

### Dvostruko učitavanje pixela

`[Barion Pixel] bp.js already loaded by another plugin` znači da je nešto drugo stiglo prvo —
Barion Payment Gateway, oznaka u Google Tag Manageru, isječak u temi. To je bezopasno: dodatak
preskače učitavanje skripte i svejedno se inicijalizira s tvojim Pixel ID-om. Vidi
[Kompatibilnost](compatibility.md).

### Pristanak se ne daje

- **WP Consent API**: dodatak WP Consent API mora biti instaliran i tvoj dodatak za kolačiće mora ga podržavati.
- **Cookie Law Info**: dodatak mora biti aktivan i globalni `CLI` dostupan.
- **Ručno**: pozovi `window.wcBarionGrantConsent()` iz callbacka svojeg upravitelja pristanka.

### purchase se šalje za neplaćenu narudžbu

Očekivano, i dokumentirano pod [purchase](events-reference.md#purchase). Dodatak prati stranicu
potvrde narudžbe, do koje izvanmrežni načini plaćanja dolaze prije nego novac stigne.
