> 🌐 Ovo je automatski prevod. Ispravke zajednice su dobrodošle!
>
> [English version](../../testing-notes.md)

# Beleške o testiranju i poznate specifičnosti

## Pre nego što zaključiš da piksel ne radi

### „Testing message“ nije greška

Otvori konzolu na stranici sa pikselom i bp.js će javiti ili **„Testing message“** ili
**„Sending message“**. Barion
[dokumentuje razliku](https://docs.barion.com/Implementing_the_Base_Barion_Pixel): sveže postavljen
piksel još nije ovlašćen da šalje korisničke podatke, pa bp.js piše „Testing message“ i prenosi
samo vrstu događaja. Kada Barion ovlasti piksel, to prelazi u „Sending message“.

Dodatak na to ne utiče. Ako tvoji događaji u konzoli izgledaju ispravno, a Barion ne vidi podatke,
piksel najverovatnije još čeka odobrenje na Barionovoj strani — implementaciju pregleda čovek, pa
im se javi kada tvoja bude gotova.

### Pixel ID mora biti onaj pravi

- Nalazi se u tvom Barion novčaniku pod **Merchant Management > Details**. Svaka prodavnica, dakle svaki POSKey, ima svoj Pixel ID.
- Format je `BP-` + deset znakova + `-` + dve cifre. ID koji počinje sa `BPT` nije Pixel ID i neće raditi.
- Sandbox i produkcija izdaju **različite** Pixel ID-ove. Test sajt sa produkcionim ID-om zagađuje stvarne podatke; produkcioni sajt sa sandbox ID-om ne beleži ništa korisno.

Ako želiš prodavnicu za jednokratnu upotrebu, Barionova stranica
[Creating a shop](https://docs.barion.com/Creating_a_shop) vodi te kroz sandbox, gde se prodavnice
odobravaju automatski.

---

## Specifičnosti provere u bp.js tokom izvršavanja

bp.js proverava podatke događaja u pregledaču, a na nekoliko mesta su njegova pravila stroža ili
blaža nego što [referenca događaja](https://docs.barion.com/Barion-pixel-event-reference)
sugeriše. Ovo je otkriveno tokom testiranja na staging okruženju.

### totalItemPrice: odbijen kod contentViewa, obavezan u elementima contents

- **contentView** (ravan događaj): bp.js **odbija** `totalItemPrice` uz `Invalid key totalItemPrice in contentView event`. Referenca se slaže — nije svojstvo contentViewa.
- Elementi `contents` događaja **initiateCheckout** i **purchase**: bp.js ga **zahteva**, inače javlja `Mandatory key totalItemPrice is missing from contents event`. I ovde se referenca slaže.

Pravilo palca: `totalItemPrice` je nevažeći na ravnim događajima i obavezan unutar elemenata
`contents`.

### unit je obavezan u elementima contents

Ako ga izostaviš, dobijaš `Mandatory key unit is missing from contents event`.

### step

Dodatak šalje `step: 1` za `addToCart`, `initiateCheckout` i `purchase`. Barion dokumentuje `1` kao
korak početka naplate, a kod `purchase` traži najviši broj koraka koji koristiš — kod jednokoračne
naplate takođe `1`. Za `addToCart` je `step` opcion.

---

## Režim za otklanjanje grešaka

Uključi ga u **Podešavanja > Barion Pixel** da se svaki događaj upiše u konzolu pregledača.

### Šta tražiti

Otvori konzolu (F12 > Konzola) i traži poruke `[Barion Pixel]`:

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

Potpun spisak poruka o saglasnosti nalazi se u
[Integraciji saglasnosti za kolačiće](cookie-consent.md).

### Greške bp.js

bp.js upisuje i sopstvene greške provere. Uobičajene:

| Greška | Značenje | Rešenje |
|--------|----------|---------|
| `Mandatory key X is missing from Y event` | Obavezno polje se ne šalje | Proveri podatke događaja |
| `Invalid key X in Y event` | Šalje se polje koje bp.js ne očekuje | Ukloni polje |
| `Format of e-mail address or hash is invalid` | bp.js je odbio vrednost prosleđenu u `setEncryptedEmail` | Od 1.0.3 dodatak adresu unapred hešira, pa ovo više ne bi trebalo da se pojavljuje |

---

## Lista za proveru testiranja

Prođi je i u klasičnoj i u blokovskoj prodavnici — za `addToCart`, `initiateCheckout` i
`setEncryptedEmail` koriste sasvim različite puteve koda.

### Stranica proizvoda (contentView)

1. Otvori stranicu proizvoda sa otvorenom konzolom.
2. Pojavi se `[Barion Pixel] Event: contentView`.
3. Nema grešaka bp.js o nedostajućim ili nevažećim ključevima.
4. Prisutna polja: `contentType`, `currency`, `id`, `name`, `quantity`, `unit`, `unitPrice` — i nema `totalItemPrice`.

### Dodavanje u korpu (addToCart)

**Stranica prodavnice ili arhive, klasično AJAX dugme:**

1. Na stranici prodavnice klikni „Dodaj u korpu“.
2. Pojavi se `[Barion Pixel] Event: addToCart`, sa `totalItemPrice` i `step: 1`.

**Stranica proizvoda, slanje forme:**

1. Klikni „Dodaj u korpu“ i proveri da se događaj šalje pre nego što stranica ode dalje.
2. Kod varijabilnog proizvoda: prvo izaberi varijaciju, pa proveri da li je upotrebljena njena cena.

**Blokovske površine (dugmići Product Collection, blok Cart):**

1. Pri učitavanju se pojavi `[Barion Pixel] Block surfaces detected …`.
2. Dodaj proizvod iz bloka Product Collection — šalje se jedan `addToCart` sa ispravnom količinom.
3. Promeni količinu u bloku Cart — nijedan `addToCart` se ne šalje.
4. U prodavnici sa nedecimalnom valutom proveri da li je `unitPrice` stvarna cena, a ne njen stoti deo.

### Stranica naplate (initiateCheckout)

1. Stavi artikle u korpu i otvori naplatu.
2. Pojavi se `[Barion Pixel] Event: initiateCheckout`.
3. Svaki element `contents` nosi `unit`, `unitPrice` i `totalItemPrice`.
4. `revenue` je međuzbir + porez, bez dostave.
5. `step: 1` je prisutan.
6. Upiši imejl adresu za naplatu. `setEncryptedEmail sent` pojavljuje se jednom po važećoj adresi — ne pri svakom pritisku tastera i ne kod delimičnog unosa poput `x@y`.
7. Ponovi na bloku Checkout, gde imejl dolazi iz skladišta podataka bloka, a ne iz `#billing_email`.

### Završetak porudžbine (purchase + setEncryptedEmail)

1. Dovrši test porudžbinu — „Bankovni prenos“ je za to najjednostavniji način plaćanja.
2. Pojavi se `[Barion Pixel] Event: purchase`, a `revenue` odgovara ukupnom iznosu porudžbine.
3. Pojavi se `setEncryptedEmail sent`.
4. Ponovo učitaj stranicu potvrde — `purchase` se **ne** šalje ponovo.
5. Elementi `contents` sadrže `unit` i `totalItemPrice`.

### Integracija saglasnosti

1. Obriši sve kolačiće. To je važno — provera ispod radi samo kod posetioca kojeg traka tek treba da pita.
2. Učitaj bilo koju stranicu. Pojavi se `[Barion Pixel] Base pixel initialized` — osnovni piksel se namerno učitava pre bilo kakve odluke o saglasnosti.
3. Zasad ništa ne diraj. Ne sme da se pojavi nikakav `grantConsent`. Barion odbija integraciju koja šalje saglasnost pri učitavanju stranice.
4. Prihvati kolačiće u svojoj traci. Tek sada se pojavi `Consent granted (grantConsent)`.
5. Ponovo učitaj. Ovaj put se ne šalje ništa, a konzola javlja da je saglasnost već postojala pri učitavanju stranice. bp.js čuva odgovor u sopstvenom kolačiću, pa ga Barion već ima.
6. Povuci saglasnost i proveri da li se pojavljuje `Consent rejected (rejectConsent)`.

---

## Česti problemi

### Događaji se ne šalju

- **Pixel ID**: u Podešavanja > Barion Pixel mora biti sačuvan važeći ID.
- **Potpuno praćenje**: događaji e-trgovine traže označeno „Omogući potpuno praćenje piksela“.
- **WooCommerce**: potpuno praćenje traži aktivan WooCommerce.
- **Greške u konzoli**: i nepovezana JavaScript greška može sprečiti učitavanje bp.js.

### Dvostruko učitavanje piksela

`[Barion Pixel] bp.js already loaded by another plugin` znači da je nešto drugo stiglo prvo —
Barion Payment Gateway, oznaka u Google Tag Manageru, isečak u temi. To je bezopasno: dodatak
preskače učitavanje skripte i svejedno se inicijalizuje sa tvojim Pixel ID-om. Vidi
[Kompatibilnost](compatibility.md).

### Saglasnost se ne daje

Upravo zbog ove greške Barion odbija integraciju Full Pixel, pa je proveri prvu. Sa uključenim
Debug Mode konzola ti kaže u kom si slučaju.

- `Consent manager detected: …`, ali nakon prihvatanja nema `grantConsent` — upravljač je pronađen, ali ne javlja marketinšku saglasnost. Proveri da li si prihvatio baš marketinšku ili oglasnu kategoriju svoje trake.
- `Marketing consent already stood when this page loaded` — ništa nije u kvaru. Testiraš kao posetilac koji se vraća. Obriši kolačiće i počni ponovo od koraka 1.
- `No consent manager detected` dok je dodatak WP Consent API aktivan — API je instaliran, ali se tvoja traka za kolačiće kod njega ne registruje, pa on javlja saglasnost kao datu za sve, a dodatak ga zanemaruje. Stranica podešavanja kaže isto. Poveži traku sa API-jem ili funkcije pozovi sam.
- `No consent manager detected` — dodatak nije našao šta da čita. Ovaj red pojavljuje se deset sekundi nakon učitavanja stranice, ne odmah, jer upravljač saglasnosti koji se servira sa CDN-a može toliko da kasni. CookieYes, Complianz, Cookiebot i stari Cookie Law Info čitaju se direktno. Za bilo koju drugu traku instaliraj [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) ili pozovi `window.wcBarionGrantConsent()` iz callbacka prihvatanja svoje trake.
- U konzoli baš ništa — osnovna skripta se nije pokrenula. Mogao ju je blokirati dodatak za saglasnost koji blokira nepoznate skripte. Barion traži da se osnovni piksel učita bez obzira na saglasnost, pa ga dodaj na spisak dozvoljenih u svom blokatoru.

Pri učitavanju stranice na kojoj saglasnost još nije data dodatak ćuti. To je namerno:
`rejectConsent` znači da je posetilac rekao ne, a ne da još nije odgovorio.

### purchase se šalje za neplaćenu porudžbinu

Očekivano, i dokumentovano pod [purchase](events-reference.md#purchase). Dodatak prati stranicu
potvrde porudžbine, do koje oflajn načini plaćanja dolaze pre nego što novac stigne.
