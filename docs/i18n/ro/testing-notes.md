> 🌐 Aceasta este o traducere automată. Corecțiile comunității sunt binevenite!
>
> [English version](../../testing-notes.md)

# Note de testare și particularități cunoscute

## Înainte să tragi concluzia că pixelul e stricat

### „Testing message” nu este o eroare

Deschide consola pe o pagină cu pixelul și bp.js va raporta fie **„Testing message”**, fie
**„Sending message”**. Barion
[documentează diferența](https://docs.barion.com/Implementing_the_Base_Barion_Pixel): un pixel
proaspăt implementat nu este încă autorizat să trimită date despre utilizatori, deci bp.js scrie
„Testing message” și transmite doar tipul evenimentului. Când Barion autorizează pixelul, mesajul
devine „Sending message”.

Plugin-ul nu schimbă asta. Dacă evenimentele tale arată corect în consolă, dar Barion nu vede date,
cel mai probabil pixelul încă așteaptă autorizarea de partea Barion — implementarea este verificată
de un om, deci contactează-i când a ta este gata.

### ID-ul Pixel trebuie să fie cel potrivit

- Îl găsești în portofelul tău Barion, la **Merchant Management > Details**. Fiecare magazin, adică fiecare POSKey, are propriul ID Pixel.
- Formatul este `BP-` + zece caractere + `-` + două cifre. Un ID care începe cu `BPT` nu este un ID Pixel și nu va funcționa.
- Sandbox și live emit ID-uri Pixel **diferite**. Un site de test cu ID de producție poluează datele reale; un site de producție cu ID de sandbox nu înregistrează nimic util.

Dacă vrei un magazin de unică folosință pentru teste, pagina Barion
[Creating a shop](https://docs.barion.com/Creating_a_shop) te ghidează prin sandbox, unde
magazinele sunt aprobate automat.

---

## Particularități ale validării bp.js în execuție

bp.js validează datele evenimentelor în browser, iar în câteva locuri regulile lui sunt mai stricte
sau mai permisive decât sugerează
[referința de evenimente](https://docs.barion.com/Barion-pixel-event-reference). Acestea au ieșit
la iveală în timpul testării pe staging.

### totalItemPrice: respins la contentView, obligatoriu în elementele contents

- **contentView** (eveniment simplu): bp.js **respinge** `totalItemPrice` cu `Invalid key totalItemPrice in contentView event`. Referința este de acord — nu este o proprietate contentView.
- Elementele `contents` ale evenimentelor **initiateCheckout** și **purchase**: bp.js îl **cere**, altfel raportează `Mandatory key totalItemPrice is missing from contents event`. Și aici referința este de acord.

Regulă practică: `totalItemPrice` este invalid pe evenimentele simple și obligatoriu în interiorul
elementelor `contents`.

### unit este obligatoriu în elementele contents

Dacă îl omiți, apare `Mandatory key unit is missing from contents event`.

### step

Plugin-ul trimite `step: 1` pentru `addToCart`, `initiateCheckout` și `purchase`. Barion
documentează `1` ca pas de început al finalizării și cere la `purchase` cel mai mare număr de pas
pe care îl folosești — tot `1` într-o finalizare cu un singur pas. Pentru `addToCart`, `step` este
opțional.

---

## Modul depanare

Activează-l în **Setări > Barion Pixel** pentru a înregistra fiecare eveniment în consola
browserului.

### Ce să urmărești

Deschide consola (F12 > Consolă) și caută mesajele `[Barion Pixel]`:

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

Lista completă a mesajelor legate de consimțământ se află în
[Integrarea consimțământului pentru cookie-uri](cookie-consent.md).

### Erori bp.js

bp.js își înregistrează și propriile erori de validare. Cele obișnuite:

| Eroare | Semnificație | Rezolvare |
|--------|--------------|-----------|
| `Mandatory key X is missing from Y event` | Un câmp obligatoriu nu este trimis | Verifică datele evenimentului |
| `Invalid key X in Y event` | Se trimite un câmp pe care bp.js nu îl așteaptă | Elimină câmpul |
| `Format of e-mail address or hash is invalid` | bp.js a respins valoarea trimisă către `setEncryptedEmail` | Din 1.0.3 plugin-ul hash-uiește adresa în prealabil, deci nu ar mai trebui să apară |

---

## Listă de verificare pentru testare

Parcurge-o atât într-un magazin clasic, cât și într-unul cu blocuri — cele două folosesc căi de cod
complet diferite pentru `addToCart`, `initiateCheckout` și `setEncryptedEmail`.

### Pagina de produs (contentView)

1. Deschide o pagină de produs cu consola deschisă.
2. Apare `[Barion Pixel] Event: contentView`.
3. Nicio eroare bp.js despre chei lipsă sau invalide.
4. Câmpuri prezente: `contentType`, `currency`, `id`, `name`, `quantity`, `unit`, `unitPrice` — și niciun `totalItemPrice`.

### Adăugare în coș (addToCart)

**Pagina de magazin sau de arhivă, buton AJAX clasic:**

1. Pe pagina magazinului, apasă „Adaugă în coș”.
2. Apare `[Barion Pixel] Event: addToCart`, cu `totalItemPrice` și `step: 1`.
3. `unitPrice` este prețul real, nu `0`. Butonul nu poartă un preț, deci acesta vine din Store API; un `0` înseamnă că acea cerere a eșuat.

**Pagina de produs, trimiterea formularului:**

1. Apasă „Adaugă în coș” și verifică dacă evenimentul se trimite înainte ca pagina să navigheze.
2. La un produs variabil: alege întâi o variație, apoi verifică dacă s-a folosit prețul ei.

**Suprafețe cu blocuri (butoane Product Collection, blocul Cart):**

1. La încărcare apare `[Barion Pixel] Block surfaces detected …`.
2. Adaugă un produs dintr-un bloc Product Collection — se trimite un `addToCart` cu cantitatea corectă.
3. Modifică o cantitate în blocul Cart — nu se trimite niciun `addToCart`.
4. Într-un magazin cu monedă fără zecimale, precum HUF, verifică dacă `unitPrice` este prețul real și nu a suta parte din el.

### Pagina de finalizare (initiateCheckout)

1. Pune articole în coș și deschide finalizarea comenzii.
2. Apare `[Barion Pixel] Event: initiateCheckout`.
3. Fiecare element `contents` are `unit`, `unitPrice` și `totalItemPrice`.
4. `revenue` este subtotal + taxa, fără livrare.
5. `step: 1` este prezent.
6. Introdu un e-mail de facturare. `setEncryptedEmail sent` apare o dată pentru fiecare adresă validă — nu la fiecare tastă și nu pentru intrări parțiale precum `x@y`.
7. Repetă pe blocul Checkout, unde e-mailul vine din depozitul de date al blocului, nu din `#billing_email`.

### Finalizarea comenzii (purchase + setEncryptedEmail)

1. Finalizează o comandă de test — „Transfer bancar” este cea mai simplă metodă de plată pentru asta.
2. Apare `[Barion Pixel] Event: purchase`, iar `revenue` corespunde totalului comenzii.
3. Apare `setEncryptedEmail sent`.
4. Reîncarcă pagina de confirmare — `purchase` **nu** se mai trimite o dată.
5. Elementele `contents` conțin `unit` și `totalItemPrice`.

### Integrarea consimțământului

1. Șterge toate cookie-urile. Contează — verificarea de mai jos funcționează doar la un vizitator pe care bara trebuie încă să îl întrebe.
2. Încarcă orice pagină. Apare `[Barion Pixel] Base pixel initialized` — pixelul de bază se încarcă intenționat înainte de orice decizie de consimțământ.
3. Deocamdată nu atinge nimic. Nu are voie să apară niciun `grantConsent`. Barion respinge o integrare care trimite consimțământul la încărcarea paginii.
4. Acceptă cookie-urile în bara ta. Abia acum apare `Consent granted (grantConsent)`.
5. Reîncarcă. De data asta nu se trimite nimic, iar consola spune că consimțământul exista deja la încărcarea paginii. bp.js păstrează răspunsul în propriul cookie, deci Barion îl are deja.
6. Retrage consimțământul și verifică dacă apare `Consent rejected (rejectConsent)`.

---

## Probleme frecvente

### Evenimentele nu se trimit

- **ID Pixel**: în Setări > Barion Pixel trebuie salvat un ID valid.
- **Urmărire completă**: evenimentele de e-commerce cer bifarea „Activează urmărirea completă cu Pixel”.
- **WooCommerce**: urmărirea completă cere WooCommerce activ.
- **Erori în consolă**: și o eroare JavaScript fără legătură poate împiedica încărcarea bp.js.

### Încărcare dublă a pixelului

`[Barion Pixel] bp.js already loaded by another plugin` înseamnă că altceva a ajuns primul — Barion
Payment Gateway, un tag Google Tag Manager, un fragment din temă. Este inofensiv: plugin-ul omite
încărcarea scriptului și se inițializează oricum cu ID-ul tău Pixel. Vezi
[Compatibilitate](compatibility.md).

### Consimțământul nu se acordă

Tocmai din cauza acestei erori Barion respinge o integrare Full Pixel, așa că verific-o prima.
Cu Debug Mode pornit, consola îți spune în ce caz ești.

- `Consent manager detected: …`, dar după acceptare niciun `grantConsent` — managerul a fost găsit, dar nu raportează consimțământ de marketing. Verifică dacă ai acceptat chiar categoria de marketing sau publicitate a barei tale.
- `Marketing consent already stood when this page loaded` — nu e nimic stricat. Testezi ca vizitator care revine. Șterge cookie-urile și ia-o de la pasul 1.
- `No consent manager detected` în timp ce plugin-ul WP Consent API este activ — API-ul este instalat, dar bara ta de cookie-uri nu se înregistrează la el, așa că raportează consimțământul ca acordat pentru toată lumea, iar plugin-ul îl ignoră. Pagina de setări spune același lucru. Conectează bara la API sau apelează tu funcțiile.
- `No consent manager detected` — plugin-ul nu a găsit nimic de citit. Linia apare la zece secunde după încărcarea paginii, nu imediat, pentru că un manager de consimțământ servit de pe un CDN poate întârzia atât. CookieYes, Complianz, Cookiebot și vechiul Cookie Law Info sunt citite direct. Pentru orice altă bară, instalează [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) sau apelează `window.wcBarionGrantConsent()` din callback-ul de acceptare al barei tale.
- Absolut nimic în consolă — scriptul de bază nu a rulat. Poate l-a blocat un plugin de consimțământ care blochează scripturile necunoscute. Barion cere ca pixelul de bază să se încarce indiferent de consimțământ, deci adaugă-l în lista de permise a blocatorului tău.

La o încărcare de pagină unde consimțământul nu a fost încă acordat, plugin-ul tace. Este
intenționat: `rejectConsent` înseamnă că vizitatorul a spus nu, nu că încă nu a răspuns.

### purchase se trimite pentru o comandă neplătită

Este de așteptat și documentat la [purchase](events-reference.md#purchase). Plugin-ul urmărește
pagina de confirmare a comenzii, la care metodele de plată offline ajung înainte să sosească banii.
