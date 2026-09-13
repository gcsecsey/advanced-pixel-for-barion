> 🌐 Toto je automatický překlad. Opravy od komunity jsou vítány!
>
> [English version](../../../CONTRIBUTING.md)

# Přispívání

Vývoj probíhá zde na GitHubu. Plugin je publikován také na
[WordPress.org](https://wordpress.org/plugins/advanced-pixel-for-barion/), ale ta kopie je jen
zrcadlo označeného vydání — issues a pull requesty prosím zakládejte v tomto repozitáři.

Příspěvky jsou vítány, nejužitečnější jsou překlady a okrajové případy ve WooCommerce. Sám mohu
testovat jen omezenou sadu šablon, platebních bran a pluginů pro souhlas, takže hlášení z praxe
mají velkou cenu.

## Hlášení chyby

Založte [issue na GitHubu](https://github.com/gcsecsey/advanced-pixel-for-barion/issues). Uveďte
prosím:

- verze WordPressu, WooCommerce, PHP a pluginu
- který plugin pro souhlas s cookies používáte, pokud nějaký
- událost, která se chová špatně (`pageView`, `contentView`, `addToCart`, `initiateCheckout`,
  `purchase`, `setEncryptedEmail`)
- výstup konzole prohlížeče se zapnutým **režimem ladění** (Nastavení → Barion Pixel). Plugin
  své zprávy uvozuje předponou `[Barion Pixel]`.

Nevkládejte do issue své skutečné Pixel ID ani e-mailovou adresu zákazníka.

## Pull requesty

1. Vycházejte z větve `main`.
2. Držte změnu úzce zaměřenou. Jedna oprava nebo jedna funkce na pull request.
3. Dodržujte stávající styl kódu: kódovací standardy WordPressu, escapování veškerého výstupu,
   sanitizace veškerého vstupu a předpona `wc_barion_pixel_` u nových globálních symbolů.
4. Popište, jak jste změnu otestovali. `docs/testing-notes.md` uvádí zvláštnosti bp.js, na kterých
   je snadné se spálit.
5. Spusťte kontroly, které spouští i CI: `composer install`, pak `composer lint` (PHPCS se
   standardy WordPressu a kompatibilitou s PHP 7.4+), `composer phpstan`, `node --test` a
   `php tests/<soubor>.php` pro každý PHP test. `composer lint:fix` opraví většinu stylových
   nálezů.
6. Spusťte prohlížečovou sadu: `npm install`, `npx playwright install --with-deps chromium`, pak
   `npm run test:browser`. Nastartuje WordPress v Playgroundu a ověří dvě věci, na které jednotkový
   test nedosáhne: že souhlas dorazí do Barionu při kliknutí na přijetí a nikdy při načtení
   stránky, a že každá e-commerce data nesou klíče, které bp.js vyžaduje — viz
   [`tests/playground/README.md`](../../../tests/playground/README.md).
7. Nezvyšujte číslo verze a neupravujte changelog — vydání se označují zvlášť.

## Překlady

Plugin dodává vlastní překlady v adresáři [`languages/`](../../../languages/). Přidání nebo oprava
jazyka:

1. Zkopírujte `languages/advanced-pixel-for-barion.pot` na
   `languages/advanced-pixel-for-barion-<locale>.po` (například `hu_HU`, `de_DE`, `hr`).
2. Přeložte řetězce. Poedit nebo jakýkoli PO editor postačí.
3. Vygenerujte binární soubory a commitněte `.po` i `.mo`:

   ```sh
   composer i18n:mo
   ```

Pokud jste změnili překládaný řetězec ve zdrojovém PHP kódu, vygenerujte nejprve šablonu příkazem
`composer i18n:build`.

## Testování změny

Otevřete pull request a použijte na něm tlačítko **Preview in WordPress Playground**. Nastartuje
WooCommerce obchod s vaší větví, nainstalovanou testovací sadou a zapnutým režimem ladění a otevře
se na přehledu všech scénářů. Připojte `?barion-panel=1` k libovolné stránce obchodu a uvidíte živý
panel s každým voláním pixelu a jeho časem.

Pull request z forku tlačítko nedostane, protože do něj workflow nesmí zapisovat. Spusťte stejnou
sadu lokálně — viz [`tests/playground/README.md`](../../../tests/playground/README.md), což je také
způsob, jak to udělat bez otevření pull requestu.

Pro vydanou verzi místo větve:

```sh
npx @wp-playground/cli server --blueprint=.wordpress-org/blueprints/blueprint.json
```

## Licence

Přispěním souhlasíte s tím, že vaše práce bude licencována pod GPL-2.0-or-later, tedy stejnou
licencí jako plugin.
