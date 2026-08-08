> 🌐 Ovo je automatski prevod. Ispravke zajednice su dobrodošle!
>
> [English version](../../testing-notes.md)

# Beleške o testiranju i poznate specifičnosti

## Specifičnosti validacije bp.js u realnom vremenu

Barionova skripta `bp.js` vrši validaciju podataka događaja na strani klijenta. U nekim slučajevima, pravila validacije se razlikuju od Barion API referentne dokumentacije. Ove specifičnosti su otkrivene tokom testiranja na staging okruženju.

### totalItemPrice: odbijen za contentView, obavezan za stavke u contents

- **contentView** (flat događaj): bp.js **odbija** `totalItemPrice` sa greškom `Invalid key totalItemPrice in contentView event`, iako API referenca navodi to polje kao obavezno.
- Stavke u **initiateCheckout** i **purchase** nizovima `contents`: bp.js **zahteva** `totalItemPrice` sa greškom `Mandatory key totalItemPrice is missing from contents event` ako se izostavi.

**Pravilo palca:** `totalItemPrice` nije validno za flat događaje, ali je obavezno unutar stavki niza `contents`.

### unit je obavezan u stavkama contents

bp.js zahteva `unit` u stavkama niza `contents` za `initiateCheckout` i `purchase`. Izostavljanje proizvodi: `Mandatory key unit is missing from contents event`.

### step je obavezan za događaje završetka porudžbine

Polje `step` je obavezno za `addToCart`, `initiateCheckout` i `purchase`. Barion dokumentacija preporučuje korišćenje `1` za jednostepene završetke porudžbine.

---

## Režim za otklanjanje grešaka

Omogući režim za otklanjanje grešaka u **Podešavanja > Barion Pixel** da se svi Barion Pixel događaji beležu u konzolu pregledača.

### Šta tražiti

Otvori konzolu pregledača (F12 > Konzola) i traži poruke sa prefiksom `[Barion Pixel]`:

```
[Barion Pixel] bp.js loaded by Advanced Pixel for Barion
[Barion Pixel] Base pixel initialized with ID: BP-xxxxxxxxxxxx-xx
[Barion Pixel] Consent auto-granted via WP Consent API
[Barion Pixel] Event: contentView { contentType: "Product", ... }
[Barion Pixel] Event: addToCart { contentType: "Product", ... }
[Barion Pixel] Event: initiateCheckout { contents: [...], ... }
[Barion Pixel] Event: purchase { contents: [...], ... }
[Barion Pixel] setEncryptedEmail sent
```

### Greške bp.js

bp.js beleži sopstvene greške validacije sa numeričkim prefiksom. Česte greške:

| Greška | Značenje | Rešenje |
|--------|---------|---------|
| `Mandatory key X is missing from Y event` | Obavezno polje se ne šalje | Proveri podatke događaja |
| `Invalid key X in Y event` | Šalje se polje koje bp.js ne očekuje | Ukloni polje |

---

## Lista za proveru testiranja

### Stranica proizvoda (contentView)

1. Idi na bilo koju stranicu pojedinačnog proizvoda
2. Otvori konzolu pregledača
3. Proveri da se pojavljuje `[Barion Pixel] Event: contentView`
4. Proveri da nema bp.js poruka o grešci zbog nedostajućih/nevalidnih polja
5. Proveri da polja uključuju: `contentType`, `currency`, `id`, `name`, `quantity`, `unit`, `unitPrice`

### Dodavanje u korpu (addToCart)

**Sa stranice prodavnice/arhive (AJAX):**

1. Idi na stranicu prodavnice
2. Otvori konzolu pregledača
3. Klikni "Dodaj u korpu" na bilo kom proizvodu
4. Proveri da se pojavljuje `[Barion Pixel] Event: addToCart`
5. Proveri da polja uključuju `totalItemPrice` i `step: 1`

**Sa stranice pojedinačnog proizvoda (slanje forme):**

1. Idi na stranicu pojedinačnog proizvoda
2. Otvori konzolu pregledača
3. Klikni "Dodaj u korpu"
4. Proveri da se `[Barion Pixel] Event: addToCart` aktivira pre nego što stranica pređe na drugu
5. Za varijabilne proizvode: najpre odaberi varijaciju i proveri da se koristi cena te varijacije

### Stranica za završetak porudžbine (initiateCheckout)

1. Dodaj stavke u korpu i idi na stranicu za završetak porudžbine
2. Otvori konzolu pregledača
3. Proveri da se pojavljuje `[Barion Pixel] Event: initiateCheckout`
4. Proveri da niz `contents` ima ispravne stavke sa `unit`, `unitPrice`, `totalItemPrice`
5. Proveri da je `revenue` međuzbir + porez (bez dostave)
6. Proveri da je prisutno `step: 1`

### Završetak porudžbine (purchase + setEncryptedEmail)

1. Završi testnu porudžbinu (koristi metod plaćanja "Bankovna transfer" radi jednostavnog testiranja)
2. Na stranici zahvalnice, otvori konzolu pregledača
3. Proveri da se pojavljuje `[Barion Pixel] Event: purchase` sa `revenue` koji odgovara ukupnom iznosu porudžbine
4. Proveri da se pojavljuje `[Barion Pixel] setEncryptedEmail sent`
5. Osveži stranicu zahvalnice — proveri da se `purchase` NE aktivira ponovo (sprečavanje duplikata)
6. Proveri da stavke u `contents` uključuju `unit`, `totalItemPrice`

### Integracija saglasnosti

1. Obriši sve kolačiće
2. Idi na bilo koju stranicu
3. Proveri da se pojavljuje `[Barion Pixel] Base pixel initialized` (osnovni piksel se uvek učitava)
4. Prihvati kolačiće putem svog banera za kolačiće
5. Proveri da se pojavljuje `[Barion Pixel] Consent granted`
6. Ponovo učitaj stranicu — proveri da je saglasnost automatski odobrena pri učitavanju (povratni posetilac)

---

## Tabla stanja i čarobnjak za saglasnost

Snimač i čarobnjak zavise od banera za kolačiće treće strane, pa zahtevaju ručne provere.

1. **Tiha saglasnost.** Aktiviraj WP Consent API i deaktiviraj svaki dodatak sa banerom za
   kolačiće. Tabla prikazuje žuti red "No cookie banner plugin sets a consent type". Pritisni
   **Check in browser**. Red postaje crven.
2. **Prepreka snimača.** Odjavi se i otvori `/?apb_record_consent=anything`. Proveri da
   `barion-consent-recorder.js` nedostaje u izvornom kodu stranice. Ponovi kao administrator sa
   nevažećim nonce-om; i dalje mora nedostajati.
3. **Zabeleži prihvatanje.** Aktiviraj baner za kolačiće. Pritisni **Set up consent**, zatim
   **Open my shop**. Prihvati u baneru. Dnevnik čarobnjaka prikazuje promenjeni kolačić.
4. **Zabeleži odbijanje.** Obriši kolačiće na toj kartici, ponovo učitaj i odbij. Čarobnjak stiže
   do koraka 3 sa popunjenim poljima.
5. **Napola postavljen okidač.** Pokušaj da sačuvaš sa praznom vrednošću odbijanja. Čarobnjak to
   odbija.
6. **Frontend.** Sa uključenim režimom za otklanjanje grešaka, prihvati u baneru. Konzola beleži
   `Consent granted via the recorded cookie trigger`. Odbij, i beleži se odgovarajući red za
   odbijanje.
7. **Dostupnost.** Pritisni **Test**. Sa uključenim blokatorom oglasa, prijavljuje upozorenje.
8. **Dve različite vrednosti.** Nakon što zabeležiš prihvatanje pa odbijanje, otvori korak 3 i
   proveri da su prihvaćena i odbijena vrednost različite. Ako su identične, okidač ne može da
   radi, jer se dvosmisleno očitavanje tretira kao odsustvo saglasnosti.

---

## Česti problemi

### Događaji se ne aktiviraju

- **Proveri Pixel ID**: Osigurai da je validan Pixel ID konfigurisan u Podešavanja > Barion Pixel
- **Proveri potpuno praćenje**: Događaji zahtevaju da je označeno "Omogući potpuno praćenje piksela"
- **Proveri WooCommerce**: Potpuno praćenje zahteva da je WooCommerce aktivan
- **Proveri greške u konzoli**: Traži JavaScript greške koje bi mogle sprečiti učitavanje bp.js

### Dvostruko učitavanje piksela

Ako vidiš `[Barion Pixel] bp.js already loaded by another plugin`, drugi dodatak (verovatno Barion Payment Gateway) je već učitao bp.js. Ovo je bezopasno — dodatak preskače ponovno učitavanje i i dalje se inicijalizuje sa tvojim Pixel ID-om.

### Saglasnost se ne odobrava

- **WP Consent API**: Osiguraj da je instaliran WP Consent API dodatak i da ga tvoj dodatak za kolačiće podržava
- **Cookie Law Info**: Osiguraj da je dodatak aktivan i da je `CLI` global dostupan
- **Ručno**: Pozovi `window.wcBarionGrantConsent()` iz povratnog poziva svog menadžera saglasnosti
