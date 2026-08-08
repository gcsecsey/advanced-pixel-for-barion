> 🌐 Aceasta este o traducere automată. Corecțiile comunității sunt binevenite!
>
> [English version](../../compatibility.md)

# Compatibilitate plugin

## WooCommerce

**Necesar pentru urmărirea completă a evenimentelor.** Pixelul de bază funcționează și fără WooCommerce, dar toate evenimentele de e-commerce (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail) necesită WooCommerce.

| Versiune | Status |
|---------|--------|
| WooCommerce 5.0+ | Suportat |
| WooCommerce 11.0 | Testat |

### Blocurile Cart și Checkout

Suportate din 1.0.6. Blocurile nu declanșează nici hook-urile PHP clasice, nici selectorii DOM
folosiți anterior de plugin, așa că pe suprafețele cu blocuri acesta citește datele WooCommerce
direct: coșul din Store API pentru `addToCart` și depozitul de date `wc/store/cart` pentru e-mailul
de la finalizare.

**Limitare cunoscută.** Evenimentul `purchase` trece prin `woocommerce_thankyou`, declanșat în
șablonul cu blocuri Order Confirmation de blocul „Informații suplimentare”. Dacă elimini acest bloc
din șablon, urmărirea achizițiilor se oprește fără niciun semn. Păstrează-l în șablon.

---

## Alte surse ale pixelului de bază

Barion documentează mai multe moduri de a aduce pixelul de bază într-o pagină, iar un magazin poate
ajunge ușor cu mai multe dintre ele deodată:

- [Barion Payment Gateway](https://github.com/szelpe/woocommerce-barion) creat de szelpe și alte plugin-uri de plată Barion, care au un câmp opțional pentru ID Pixel
- un [tag Google Tag Manager](https://docs.barion.com/Implementing_the_Barion_Pixel_base_code_through_the_Google_Tag_Manager)
- un fragment lipit în header-ul temei

Plugin-ul verifică `window.bp` și `window.BarionAnalyticsObject` înainte să încarce `bp.js`. Dacă
ambele sunt deja acolo, omite încărcarea scriptului și trimite doar propriul apel `init`, astfel
încât pixelul nu se încarcă niciodată de două ori. În modul depanare, acest lucru apare ca
`[Barion Pixel] bp.js already loaded by another plugin`.

**Recomandare:** păstrează ID-ul Pixel într-un singur loc. Dacă folosești și un gateway de plată
Barion, configurează ID-ul aici și lasă câmpul gateway-ului gol; dacă încarci deja pixelul de bază
prin Google Tag Manager, elimină acel tag. Cazul de evitat cu adevărat este acela cu două ID-uri
Pixel diferite pe aceeași pagină — un script duplicat poate fi suprimat de plugin, o identitate
duplicată nu.

Când și Barion Payment Gateway are un ID Pixel configurat, pagina de setări afișează o notificare
informativă. Ambele plugin-uri funcționează oricum mai departe: acela gestionează plățile, acesta
urmărirea.

---

## Plugin-uri de cache pentru pagini

Plugin-ul este pe deplin compatibil cu cache-ul de pagini:

| Eveniment | Implementare | Impact cache |
|-------|---------------|----------------|
| contentView | Server (pagina de produs) | Paginile de produse nu sunt de obicei în cache, sau variază în funcție de produs |
| addToCart | **JavaScript pe partea clientului** | Fără probleme de cache — JS se execută în browser |
| initiateCheckout | Server (pagina de finalizare a comenzii) | Pagina de finalizare nu este în cache (conține date de sesiune ale utilizatorului) |
| purchase | Server (pagina de mulțumire) | Paginile de mulțumire nu sunt în cache (unice per comandă) |

Evenimentul addToCart a fost implementat specific pe partea clientului (în loc de sesiuni PHP) pentru a funcționa cu găzduirea WordPress.com și configurații agresive de cache pentru pagini.

**Compatibil cu:** WP Super Cache, W3 Total Cache, LiteSpeed Cache, găzduire WordPress.com, Cloudflare și soluții similare de cache.

---

## Plugin-uri de consimțământ cookie

Plugin-ul suportă toate plugin-urile de consimțământ cookie care implementează [WP Consent API](https://wordpress.org/plugins/wp-consent-api/). Vezi [Integrare consimțământ cookie](cookie-consent.md) pentru detalii.

**Suportate automat:**

- CookieYes (1,5M+ instalări)
- Complianz (1M+ instalări)
- Cookie Notice by dFactory (1M+ instalări)
- GDPR Cookie Compliance by Moove (300K+ instalări)
- Real Cookie Banner (100K+ instalări)

**Integrare fallback directă:**

- Cookie Law Info / CookieYes (funcționează și fără WP Consent API)

---

## Versiune WordPress

| Versiune | Status |
|---------|--------|
| WordPress 5.0+ | Necesar |
| WordPress 7.0 | Testat |

## Versiune PHP

| Versiune | Status |
|---------|--------|
| PHP 7.4+ | Necesar |
| PHP 8.x | Compatibil |
