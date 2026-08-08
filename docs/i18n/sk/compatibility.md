> 🌐 Toto je automatický preklad. Komunitné opravy sú vítané!
>
> [English version](../../compatibility.md)

# Kompatibilita pluginu

## WooCommerce

**Vyžaduje sa pre úplné sledovanie udalostí.** Základný pixel funguje bez WooCommerce, ale všetky e-commerce udalosti (contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail) vyžadujú WooCommerce.

| Verzia | Stav |
|--------|------|
| WooCommerce 5.0+ | Podporovaná |
| WooCommerce 11.0 | Testovaná |

### Bloky Cart a Checkout

Podporované od verzie 1.0.6. Bloky nespúšťajú ani klasické PHP hooky, ani DOM selektory, ktoré
plugin používal predtým, takže na blokových plochách číta údaje WooCommerce priamo: košík zo Store
API pre `addToCart` a dátové úložisko `wc/store/cart` pre e-mail v pokladni.

**Známe obmedzenie.** Udalosť `purchase` beží cez `woocommerce_thankyou`, ktorú v blokovej šablóne
Order Confirmation vyvoláva blok „Ďalšie informácie“. Ak tento blok zo šablóny odstrániš,
sledovanie nákupov sa ticho zastaví. Nechaj ho v šablóne.

---

## Ďalšie zdroje základného pixela

Barion dokumentuje niekoľko spôsobov, ako dostať základný pixel na stránku, a v jednom obchode sa
ich ľahko zíde viac:

- [Barion Payment Gateway](https://github.com/szelpe/woocommerce-barion) od szelpe a ďalšie platobné pluginy pre Barion, ktoré majú voliteľné pole Pixel ID
- [tag v Google Tag Manageri](https://docs.barion.com/Implementing_the_Barion_Pixel_base_code_through_the_Google_Tag_Manager)
- útržok vložený do hlavičky šablóny

Plugin pred načítaním `bp.js` overí `window.bp` a `window.BarionAnalyticsObject`. Ak sú obe už k
dispozícii, načítanie skriptu preskočí a odošle len vlastné volanie `init`, takže sa pixel nikdy
nenačíta dvakrát. V režime ladenia to hlási správa
`[Barion Pixel] bp.js already loaded by another plugin`.

**Odporúčanie:** drž Pixel ID na jednom mieste. Ak prevádzkuješ aj platobnú bránu Barionu, nastav
ID tu a pole v bráne nechaj prázdne; ak základný pixel už načítavaš cez Google Tag Manager, ten tag
odstráň. Naozaj nežiaduci je prípad dvoch rôznych Pixel ID na jednej stránke — dvojitý skript
plugin potlačiť vie, dvojitú identitu nie.

Keď má Pixel ID nastavené aj Barion Payment Gateway, stránka nastavení zobrazí informatívne
oznámenie. Oba pluginy fungujú ďalej tak či tak: ten sa stará o platby, tento o sledovanie.

---

## Pluginy na ukladanie stránok do cache

Plugin je plne kompatibilný s ukladaním stránok do cache:

| Udalosť | Implementácia | Vplyv cacheovania |
|---------|--------------|-------------------|
| contentView | Na strane servera (stránka produktu) | Stránky produktov sa zvyčajne neukladajú do cache alebo sa líšia podľa produktu |
| addToCart | **JavaScript na strane klienta** | Žiadne problémy s cacheovaním — JS sa spustí v prehliadači |
| initiateCheckout | Na strane servera (stránka pokladne) | Pokladňa nie je uložená v cache (obsahuje údaje o relácii používateľa) |
| purchase | Na strane servera (stránka ďakovania) | Stránky ďakovania nie sú uložené v cache (jedinečné pre každú objednávku) |

Udalosť addToCart bola špecificky implementovaná na strane klienta (namiesto použitia PHP relácií), aby fungovala s hostingom WordPress.com a agresívnymi nastaveniami ukladania stránok do cache.

**Kompatibilné s:** WP Super Cache, W3 Total Cache, LiteSpeed Cache, hostingom WordPress.com, Cloudflare a podobnými riešeniami na ukladanie do cache.

---

## Pluginy na súhlas s cookies

Plugin podporuje všetky pluginy na súhlas s cookies, ktoré implementujú [WP Consent API](https://wordpress.org/plugins/wp-consent-api/). Podrobnosti nájdeš v [Integrácia súhlasu s cookies](cookie-consent.md).

**Automaticky podporované:**

- CookieYes (1,5M+ inštalácií)
- Complianz (1M+ inštalácií)
- Cookie Notice od dFactory (1M+ inštalácií)
- GDPR Cookie Compliance od Moove (300K+ inštalácií)
- Real Cookie Banner (100K+ inštalácií)

**Priama záložná integrácia:**

- Cookie Law Info / CookieYes (funguje aj bez WP Consent API)

---

## Verzia WordPress

| Verzia | Stav |
|--------|------|
| WordPress 5.0+ | Vyžadovaná |
| WordPress 7.0 | Testovaná |

## Verzia PHP

| Verzia | Stav |
|--------|------|
| PHP 7.4+ | Vyžadovaná |
| PHP 8.x | Kompatibilná |
