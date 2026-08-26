# CI tooling research (primary sources)

Research date: 2026-08-23. All claims below cite the source that owns them:
official docs, the tools' own GitHub repositories, wordpress.org handbook pages,
or marketplace action pages. This document states facts only; it makes no
recommendations.

Repo context: single-file WordPress plugin (`advanced-pixel-for-barion.php`,
Requires PHP 7.4, WC tested up to 11.0), JS tests on `node:test`, a plain-PHP
subprocess test, composer with `dealerdirect/phpcodesniffer-composer-installer`
already whitelisted in `allow-plugins`, and only a tag-triggered deploy workflow
today.

---

## 1. PHPCS + WordPress Coding Standards (WPCS)

Source: <https://github.com/WordPress/WordPress-Coding-Standards>

- Latest release: **3.4.1** (2026-07-27). Before it: 3.4.0 (2026-07-16), 3.3.0
  (2025-11-25). Source: <https://github.com/WordPress/WordPress-Coding-Standards/releases>
- **3.4.1 is a security release.** The `WordPress.WP.EnqueuedResourceParameters`
  sniff, when run over untrusted PHP code (for example a CI pipeline that lints
  pull requests), could lead to arbitrary command execution on the scanning
  host. The `WordPress` and `WordPress-Extra` rulesets are affected;
  `WordPress-Core` and `WordPress-Docs` are not. Advisory:
  <https://github.com/WordPress/WordPress-Coding-Standards/security/advisories/GHSA-3pwp-g2mj-5p3v>
  Pin `>= 3.4.1`.
- Requirements (from `composer.json` at tag 3.4.1): PHP `>=7.2` with filter,
  libxml, tokenizer, xmlreader extensions; `squizlabs/php_codesniffer ^3.13.5`;
  `phpcsstandards/phpcsutils ^1.2.3`; `phpcsstandards/phpcsextra ^1.5.1`.
  **WPCS 3.4.1 does not allow PHP_CodeSniffer 4.x** (constraint is `^3.13.5`).
- Install (README, "Composer Project-based Installation"):

  ```bash
  composer config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
  composer require --dev wp-coding-standards/wpcs:"^3.0"
  ```

  This repo's `composer.json` already whitelists the installer plugin, so only
  the `require` step is new.
- Rulesets (README, "Standards subsets"):
  - `WordPress` — complete set, all sniffs.
  - `WordPress-Core` — WordPress core coding standards.
  - `WordPress-Docs` — inline documentation standards.
  - `WordPress-Extra` — recommended best practices; includes `WordPress-Core`.
- Custom ruleset: name it `phpcs.xml.dist` (or `.phpcs.xml`, `phpcs.xml`,
  `.phpcs.xml.dist`) and PHPCS finds it without `--standard`. WPCS ships an
  annotated sample:
  <https://github.com/WordPress/WordPress-Coding-Standards/blob/develop/phpcs.xml.dist.sample>
- Key configurable properties shown in the official sample:
  - `<config name="minimum_wp_version" value="6.7"/>` — used by the sniffs that
    detect deprecated WP features. WPCS 3.4.0 set the default to 6.7 and knows
    functions deprecated up to WP 7.0.0 (release notes,
    <https://github.com/WordPress/WordPress-Coding-Standards/releases/tag/3.4.0>).
  - `WordPress.WP.I18n` `text_domain` property (array of allowed text domains).
  - `WordPress.NamingConventions.PrefixAllGlobals` `prefixes` property.
  - Full property list:
    <https://github.com/WordPress/WordPress-Coding-Standards/wiki/Customizable-sniff-properties>
- Minimal `phpcs.xml.dist` for this plugin, assembled from the official sample
  (values adapted to this repo):

  ```xml
  <?xml version="1.0"?>
  <ruleset name="Advanced Pixel for Barion">
      <file>.</file>
      <exclude-pattern>/vendor/*</exclude-pattern>
      <exclude-pattern>/node_modules/*</exclude-pattern>
      <arg name="basepath" value="."/>
      <arg name="parallel" value="8"/>
      <arg name="extensions" value="php"/>

      <rule ref="WordPress-Extra">
          <!-- Exclude sniffs rule-by-rule here after a `phpcs -s` run. -->
      </rule>

      <config name="minimum_wp_version" value="5.0"/>

      <rule ref="WordPress.WP.I18n">
          <properties>
              <property name="text_domain" type="array">
                  <element value="advanced-pixel-for-barion"/>
              </property>
          </properties>
      </rule>
  </ruleset>
  ```

### Adopting WPCS on an existing (PSR-ish) codebase

- The official sample's documented approach: include the whole ruleset, run
  `phpcs -s` to see sniff names, then `<exclude name="..."/>` the sniffs that
  do not suit the project. Per-file exclusions use
  `<exclude-pattern>` inside a `<rule>` block (both shown in
  `phpcs.xml.dist.sample`).
- **PHP_CodeSniffer has no native baseline feature.** A maintained third-party
  Composer plugin exists: `digitalrevolution/php-codesniffer-baseline`
  (v1.3.0, 2025-10-16; requires PHP >= 8.1, PHPCS `^3.6 || ^4.0`; 1.2M+
  installs). `vendor/bin/phpcs-baseline src` writes `phpcs.baseline.xml`; after
  that, phpcs runs skip the recorded violations automatically. Source:
  <https://packagist.org/packages/digitalrevolution/php-codesniffer-baseline>
  Note its PHP >= 8.1 floor is higher than this plugin's PHP 7.4 support, which
  matters only for the machine that runs the linter, not for the plugin
  runtime.
- Naming-convention note: the WPCS camelCase conflicts live mostly in
  `WordPress.NamingConventions.*` and `WordPress.Files.FileName` sniffs, which
  can be excluded ruleset-wide (mechanism per the sample above; the specific
  selection is a project decision, not documented by WPCS).

---

## 2. PHPCompatibility / PHPCompatibilityWP

Sources: <https://github.com/PHPCompatibility/PHPCompatibility>,
<https://github.com/PHPCompatibility/PHPCompatibilityWP>

- **PHPCompatibility releases:** latest stable is still **9.3.5 (2019-12-27)**.
  Pre-releases: **10.0.0-alpha1 (2025-10-21)** and **10.0.0-alpha2
  (2025-11-28)**. The 9.x line predates PHP 8, so it cannot detect
  PHP 8.x incompatibilities; PHP 8 sniff coverage lives in the 10.x line.
- The README (develop) documents the 10.x install as the supported path:

  ```bash
  composer config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
  composer require --dev phpcompatibility/php-compatibility:"^10.0.0@dev"
  ```

- Requirements per tag (from `composer.json` at each tag):
  - PHPCompatibility 10.0.0-alpha2: PHP >= 5.4, PHPCS `^3.13.3 || ^4.0`,
    PHPCSUtils `^1.1.2`. So it **co-installs with WPCS 3.4.1 on PHPCS 3.13.x**
    (WPCS does not allow PHPCS 4 yet; see section 1).
  - PHPCompatibilityWP: latest stable **2.1.8 (2025-10-18)** depends on
    `phpcompatibility/php-compatibility ^9.0` (no PHP 8 detection).
    Pre-release **3.0.0-alpha2 (2025-12-16)** depends on
    `phpcompatibility/php-compatibility ^10.0@dev` and
    `phpcompatibility-paragonie ^2.0@dev`. Its README installs it with
    `composer require --dev phpcompatibility/phpcompatibility-wp:"^3.0@dev"`.
- What PHPCompatibilityWP adds: it excludes PHPCompatibility sniffs for
  back-fills/poly-fills that WordPress itself provides, preventing false
  positives (README). It also documents WP core PHP floors: WP 6.6 → PHP
  7.2.24, WP 7.0 → PHP 7.4.0.
- **testVersion** (PHPCompatibility README): a range enables detection of both
  removed/deprecated features and too-new features. Command line:
  `--runtime-set testVersion 7.4-8.4` (bounded range) or `7.4-` (open-ended).
  In a ruleset: `<config name="testVersion" value="7.4-"/>`. A CLI
  `--runtime-set testVersion` overrules the ruleset value (PHPCS >= 3.3.0).
  Restrict to PHP files with `--extensions=php` (PHPCompatibilityWP README).
- Example from the PHPCompatibilityWP README:

  ```bash
  vendor/bin/phpcs -p . --standard=PHPCompatibilityWP --extensions=php --runtime-set testVersion 7.4-
  ```

Summary of the version situation: to check a 7.4–8.4 range today you need the
10.x alphas (`phpcompatibility-wp:^3.0@dev`), because the stable 9.3.5 +
PHPCompatibilityWP 2.1.8 pair has no PHP 8 sniffs.

---

## 3. Plugin Check (PCP) and plugin-check-action

Sources: <https://github.com/WordPress/plugin-check>,
<https://github.com/WordPress/plugin-check-action>

- Plugin Check is the WordPress.org tool that checks plugins against directory
  requirements and best practices. Latest release: **2.1.0 (2026-08-16)**.
  It runs static checks (PHPCS sniffs and custom logic) and runtime checks
  (executing hooks with the plugin active). Usable via WP Admin
  (Tools > Plugin Check) or WP-CLI: `wp plugin check <plugin>`; runtime checks
  under WP-CLI need `--require=./wp-content/plugins/plugin-check/cli.php`.
- **plugin-check-action** (official GitHub Action): latest **v1.1.9
  (2026-08-11)**. It runs Plugin Check inside a `wp-env` environment and posts
  results as file annotations. Marketplace/README:
  <https://github.com/WordPress/plugin-check-action>
- Inputs (from the README, defaults in parentheses): `repo-token`
  (`github.token`), `build-dir` (`./`), `checks` (all), `exclude-checks`,
  `ignore-codes` (individual error codes, e.g. `textdomain_mismatch` or
  `WordPress.Security.EscapeOutput.OutputNotEscaped`), `categories` (all),
  `exclude-files`, `exclude-directories`, `ignore-warnings` (false),
  `ignore-errors` (false), `include-experimental` (true), `wp-version`
  (`latest`, also accepts `trunk`), `severity`, `error-severity`,
  `warning-severity`, `include-low-severity-errors`,
  `include-low-severity-warnings`, `slug`, `strict` (false; treats everything
  as an error).
- Checks listed in the README: `i18n_usage`, `code_obfuscation`,
  `direct_db_queries`, `enqueued_scripts_in_footer`, `enqueued_scripts_size`,
  `enqueued_styles_scope`, `file_type`, `late_escaping`, `localhost`,
  `no_unfiltered_uploads`, `performant_wp_query_params`,
  `plugin_header_text_domain`, `plugin_readme`, `plugin_review_phpcs`,
  `plugin_updater`, `trademarks`. Categories: `accessibility`, `general`,
  `performance`, `plugin_repo`, `security`.
  So it covers readme (`plugin_readme`), i18n (`i18n_usage`,
  `plugin_header_text_domain`), security sniffs (`late_escaping`,
  `plugin_review_phpcs`, …), and plugin-header/trademark checks.
- Basic workflow from the README:

  ```yaml
  name: 'build-test'
  on:
    pull_request:
    push:
      branches: [main]

  jobs:
    test:
      runs-on: ubuntu-latest
      steps:
        - name: Checkout
          uses: actions/checkout@v3
        - name: Run plugin check
          uses: wordpress/plugin-check-action@v1
  ```

- The README's advanced example builds a dist zip first with
  `wp dist-archive` (which honors `.distignore`), unzips it, and points
  `build-dir` at the unzipped plugin — so the action checks exactly what would
  ship. It also notes that projects that already run PHPCS themselves may
  exclude the PHPCS-based checks via `exclude-checks`.
- The environment can be tuned through `.wp-env.json` /
  `.wp-env.override.json` (for example `WP_DEBUG_DISPLAY: false`).

---

## 4. PHPStan for WordPress

Sources: <https://github.com/szepeviktor/phpstan-wordpress>,
<https://github.com/php-stubs/wordpress-stubs>,
<https://github.com/php-stubs/woocommerce-stubs>, <https://phpstan.org>

- `szepeviktor/phpstan-wordpress`: latest **v2.0.3 (2025-10-10)**. Requires
  **PHPStan 2.0+**, PHP 7.4+. It loads `php-stubs/wordpress-stubs`, adds
  dynamic return types for WP functions, defines core constants, and reads
  `apply_filters()` docblocks to type filter returns. Install:
  `composer require --dev szepeviktor/phpstan-wordpress`; with
  `phpstan/extension-installer` no config include is needed, otherwise add
  `includes: [vendor/szepeviktor/phpstan-wordpress/extension.neon]`.
  The maintainer states in the README that he plans to stop contributing to
  the WordPress ecosystem without sponsorship (maintenance-risk signal for
  both this package and php-stubs).
- `php-stubs/wordpress-stubs`: latest **v7.0.0 (2026-05-21)** ("WordPress goes
  AI"); v6.9.1 (2026-02-03) before it. Pulled in by phpstan-wordpress.
- `php-stubs/woocommerce-stubs`: latest tag **v11.0.0** (matches WooCommerce
  11; this plugin's header says "WC tested up to 11.0"). README usage for
  PHPStan:

  ```yaml
  parameters:
      bootstrapFiles:
          - vendor/php-stubs/woocommerce-stubs/woocommerce-stubs.php
          #- vendor/php-stubs/woocommerce-stubs/woocommerce-packages-stubs.php
  ```

- Minimal `phpstan.neon.dist` for a plugin that conditionally uses WooCommerce
  (assembled from the two READMEs above; example config in
  <https://github.com/szepeviktor/phpstan-wordpress/blob/master/examples/phpstan.neon.dist>):

  ```neon
  includes:
      - vendor/szepeviktor/phpstan-wordpress/extension.neon
  parameters:
      level: 5
      paths:
          - advanced-pixel-for-barion.php
          - uninstall.php
      bootstrapFiles:
          - vendor/php-stubs/woocommerce-stubs/woocommerce-stubs.php
  ```

  The phpstan-wordpress README's own example uses `level: 5`.
- Levels and baseline (PHPStan docs): levels run 0–10; the default is 0 and
  the docs describe the levels as a mechanism for "incremental adoption"
  (<https://phpstan.org/user-guide/rule-levels>). For existing codebases, the
  baseline feature (`vendor/bin/phpstan analyse --generate-baseline`) writes
  `phpstan-baseline.neon`, which is then added to `includes:`; recorded errors
  are ignored while new errors still fail
  (<https://phpstan.org/user-guide/the-baseline>).

---

## 5. Running the existing tests in CI

### Node (`tests/cart-diff.test.js`)

Source: Node.js API docs, "Running tests from the command line" —
<https://nodejs.org/docs/latest-v24.x/api/test.html> (identical patterns in
<https://nodejs.org/docs/latest-v22.x/api/test.html>).

- Default matching for bare `node --test` includes `**/*.test.{cjs,mjs,js}`
  and `**/test/**/*.{cjs,mjs,js}` (plus `*-test`, `*_test`, `test-*`, `test.*`
  variants). `tests/cart-diff.test.js` matches both patterns, so **`node
  --test` with no arguments finds it**.
- Explicit globs are also supported as final arguments:
  `node --test "tests/*.test.js"` (docs recommend double quotes to prevent
  shell expansion).
- Node LTS status as of 2026-08 (<https://nodejs.org/en/about/previous-releases>):
  **v24 "Krypton"** and **v22 "Jod"** are the LTS lines; v26 is Current;
  v20 "Iron" reached end-of-life 2026-03-24. From Node 27 on, every major goes
  LTS after a 6-month Current phase.

### Plain PHP script test (`tests/no-woocommerce-fatal.php`)

- `php tests/no-woocommerce-fatal.php` needs nothing beyond a PHP binary; a
  GitHub Actions step fails on non-zero exit codes by default (standard shell
  behavior; no extra action needed).

### shivammathur/setup-php

Source: <https://github.com/shivammathur/setup-php>

- Latest release: **2.37.2 (2026-06-08)**; use `shivammathur/setup-php@v2`.
- Supports PHP 5.3–8.6 on GitHub-hosted runners. `ubuntu-latest` is Ubuntu
  24.04 (pre-installed PHP 8.3); the action installs any requested version.
- Matrix example (README "Matrix Setup", trimmed to Linux):

  ```yaml
  jobs:
    test:
      runs-on: ubuntu-latest
      strategy:
        matrix:
          php: ['7.4', '8.0', '8.1', '8.2', '8.3', '8.4']
      steps:
        - uses: actions/checkout@v4
        - uses: shivammathur/setup-php@v2
          with:
            php-version: ${{ matrix.php }}
            coverage: none
  ```

  `extensions` and `ini-values` inputs exist but are optional; `coverage:
  none` disables Xdebug/PCOV. (The README's own example uses 8.1–8.5; the 7.4–8.4
  list above is this plugin's support range, all within the action's 5.3–8.6
  span.)

---

## 6. E2E options (wp-env + Playwright, WordPress Playground)

### wp-env

Source: <https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/>

- `@wordpress/env` spins up a Dockerized WordPress (`wp-env start`, admin /
  password on :8888). `.wp-env.json` configures `core`, `plugins`, `themes`,
  `phpVersion`, `config`, `lifecycleScripts`. `wp-env run <container> <cmd>`
  gives access to wp-cli, composer, and phpunit inside the containers. An
  experimental Playground-backed (Docker-free) runtime exists.
- Real-world CI usage: WordPress/plugin-check's own `php-test.yml` drives
  wp-env with `WP_ENV_PHP_VERSION` and `WP_ENV_CORE` environment variables in
  a PHP 7.4–8.4 × WP matrix (see section 9).
- Playwright layer: `@wordpress/e2e-test-utils-playwright` provides `Admin`,
  `Editor`, `PageUtils`, `RequestUtils` fixtures for WP e2e tests; the package
  self-describes as v0.x, under active development, with possible breaking
  changes. Source:
  <https://developer.wordpress.org/block-editor/reference-guides/packages/packages-e2e-test-utils-playwright/>

### WordPress Playground in CI

Sources: <https://wordpress.github.io/wordpress-playground/developers/local-development/wp-playground-cli/>,
<https://wordpress.github.io/wordpress-playground/developers/limitations/>,
<https://wordpress.github.io/wordpress-playground/developers/architecture/wordpress-database/>,
<https://github.com/WordPress/playground-tools>

- `@wp-playground/cli` (Node >= 20.18): `npx @wp-playground/cli@latest server`
  (advanced mode, documented as suited to CI), `run-blueprint` (executes a
  blueprint without starting a server — "suitable for automated workflows"),
  `build-snapshot`. Options: `--blueprint=<path>`, `--mount=<host:vfs>`,
  `--wp=<version>`, `--php=<version>` (7.4–8.5, default 8.3), `--login`,
  `--port` (default 9400).
- Database: Playground runs SQLite behind the official SQLite Database
  Integration plugin, which rewrites MySQL queries; the docs state this layer
  passes **99% of the WordPress unit test suite**. There is no MySQL.
- Documented limitations: partial WP-CLI support ("doesn't support the full
  array of available commands", no definitive list), iframe quirks, PHP code
  executed via the API must require `wp-load.php` manually. The limitations
  page does not document WooCommerce-specific caveats.
- WooCommerce specifics: the Playground docs reference WooCommerce only as a
  blueprint resource example; the troubleshooting page covers generic
  `activatePlugin` failures (fatals, version mismatches, unexpected output)
  but nothing WooCommerce-specific. So a WooCommerce smoke test in Playground
  is not documented as unsupported, but it is also not documented as a
  supported pattern; the SQLite layer and partial WP-CLI support are the known
  risk areas. The repo's existing Playground blueprints
  (`.wordpress-org/`/preview blueprint) are evidence WooCommerce boots in
  Playground for this plugin's demo.
- Reference implementation: WordPress/plugin-check uses Playground for **PR
  preview** (build zip → publish artifact → "Open in WordPress Playground"
  button via `WordPress/action-wp-playground-pr-preview`), not for automated
  test assertions. Its automated PHP tests run in wp-env instead. Source:
  <https://github.com/WordPress/plugin-check> README.
- Note: plugin-check-action (section 3) already boots a full wp-env WordPress
  per run, which is itself a de-facto smoke test that the plugin loads.

---

## 7. readme.txt / i18n / build checks

- **Plugin Check covers readme validation** through its `plugin_readme` check
  (section 3). WordPress.org also hosts the interactive readme validator at
  <https://wordpress.org/plugins/developers/readme-validator/> (paste-in web
  tool; no official GitHub Action wraps it).
- **WP-CLI `i18n make-pot`** (<https://developer.wordpress.org/cli/commands/i18n/make-pot/>):
  `wp i18n make-pot <source> [<destination>]` with `--domain`, `--exclude`,
  `--skip-js`, `--skip-audit`, `--headers`. **The command has no built-in
  check/dry-run mode for stale POT files.** The only mechanism is to
  regenerate (this repo: `composer i18n:pot`) and compare, e.g.
  `git diff --exit-code languages/*.pot` — POT files carry a
  `POT-Creation-Date` header, so an exact-diff check needs that header
  excluded or fixed (repo-level concern; not covered by the docs).
- **.distignore / build verification**: there is no WordPress.org-official
  action. 10up (maintainer of the deploy action this repo already uses)
  provides:
  - `10up/action-wordpress-plugin-build-zip` — builds the zip exactly the way
    the deploy action would (honoring `.distignore`, falling back to
    `.gitattributes`) and uploads it as an artifact for pre-release testing
    (`retention-days` input). Source:
    <https://github.com/10up/action-wordpress-plugin-build-zip>
  - `10up/action-wordpress-plugin-deploy` itself has a `dry-run: true` input
    that skips the final SVN commit ("to debug prior to a non-dry-run
    commit"; no SVN secrets required) and a `generate-zip` input with a
    `zip-path` output. Source:
    <https://github.com/10up/action-wordpress-plugin-deploy>
  - The wp-cli equivalent is `wp dist-archive`
    (<https://github.com/wp-cli/dist-archive-command/>), which the
    plugin-check-action README uses in its advanced example.

---

## 8. AI code review on PRs

### anthropics/claude-code-action (official)

Source: <https://github.com/anthropics/claude-code-action> (+ docs/usage.md,
docs/setup.md, docs/solutions.md in that repo)

- Current major: **v1** (`anthropics/claude-code-action@v1`); patch releases
  land near-daily (v1.0.201 on 2026-08-23). v0.x inputs (`mode`,
  `direct_prompt`, `custom_instructions`, …) are deprecated; v1 uses `prompt`
  + `claude_args`.
- Mode detection: with a `prompt` input the action runs immediately
  (automation mode — used for auto PR review); without it, it waits for
  `@claude` mentions (interactive mode).
- **Auth options** (docs/setup.md):
  - `ANTHROPIC_API_KEY` secret (direct API, pay per token).
  - `CLAUDE_CODE_OAUTH_TOKEN` secret — the docs state: "Pro and Max users can
    generate this by running `claude setup-token` locally". This is the
    officially documented subscription-auth path for GitHub Actions.
  - Workload Identity Federation (OIDC exchange, no static secret), plus
    Bedrock / Vertex / Foundry.
- Quickstart: run `/install-github-app` inside Claude Code; installs the
  GitHub app and secrets (repo-admin required; direct-API users only).
- Automatic PR review pattern (docs/solutions.md, "Automatic PR Code
  Review") — the two-workflow convention is `claude.yml` (interactive
  @claude) + `claude-code-review.yml` (automatic):

  ```yaml
  name: Claude Auto Review
  on:
    pull_request:
      types: [opened, synchronize]
  jobs:
    review:
      runs-on: ubuntu-latest
      permissions:
        contents: read
        pull-requests: write
        id-token: write
      steps:
        - uses: actions/checkout@v6
          with:
            fetch-depth: 1
        - uses: anthropics/claude-code-action@v1
          with:
            anthropic_api_key: ${{ secrets.ANTHROPIC_API_KEY }}
            # or: claude_code_oauth_token: ${{ secrets.CLAUDE_CODE_OAUTH_TOKEN }}
            track_progress: true
            prompt: |
              REPO: ${{ github.repository }}
              PR NUMBER: ${{ github.event.pull_request.number }}
              Review this pull request for code quality, bugs, security, performance.
              Use inline comments for specific issues.
            claude_args: |
              --allowedTools "mcp__github_inline_comment__create_inline_comment,Bash(gh pr comment:*),Bash(gh pr diff:*),Bash(gh pr view:*)"
  ```

- Other relevant inputs: `use_sticky_comment` (one comment per PR),
  `include_fix_links` (default true), `trigger_phrase` (default `@claude`),
  `allowed_bots` (empty = no bots may trigger; on public repos `'*'` is
  flagged as risky in the README).
- Cost model: the action is MIT-licensed and runs on your own runner; the
  Anthropic API calls are billed to whichever auth you configure (API key =
  token billing; OAuth token = Pro/Max subscription usage). Sources: README
  ("Runs on Your Infrastructure: … Anthropic API calls go to your chosen
  provider") and setup.md quoted above.

### GitHub Copilot code review

Source: <https://docs.github.com/en/copilot/concepts/agents/code-review> and
<https://docs.github.com/en/copilot/using-github-copilot/code-review/using-copilot-code-review>

- Reviews PRs on request ("Request" next to Copilot under Reviewers) or
  automatically via ruleset configuration; reviews are comments, never
  approvals. Re-review on push is opt-in ("Review new pushes").
- **Not included in Copilot Free** ("users on the Copilot Free plan, which
  does not include Copilot code review"). Available to individuals on Pro /
  Pro+ (docs mention automatic reviews for these plans) and to Business /
  Enterprise orgs. Copilot Pro is free for verified open-source maintainers,
  students and teachers
  (<https://docs.github.com/en/copilot/about-github-copilot/plans-for-github-copilot>).
- Cost per review (docs' estimates): Lite reviews ≈ $0.05–$1 of AI credits;
  Balanced reviews ≈ $0.25–$5, varying with PR size.

### CodeRabbit

Source: <https://www.coderabbit.ai/pricing>

- **Free forever for public repositories**: "Sign up … install CodeRabbit on a
  public repository, and receive free reviews forever for public
  repositories." Paid Pro is $24/user/month (annual) with rate limits (5 PR
  reviews per developer per hour on Pro).

---

## 9. Reference workflows (real-world WP plugin CI)

### WordPress/plugin-check (the canonical WP.org tool's own CI)

Source: <https://github.com/WordPress/plugin-check/tree/trunk/.github/workflows>

- Workflow files: `php-lint.yml`, `php-test.yml`, `behat-test.yml`,
  `js-lint.yml`, `spell-check.yml`, `deploy.yml`, Playground PR-preview
  workflows, and others.
- `php-lint.yml`: triggers on PHP-related paths only; jobs run
  `actions/checkout@v7` → `shivammathur/setup-php@v2` →
  `composer validate` → `ramsey/composer-install` (pinned by SHA) →
  `composer lint` (PHPCS), `composer phpstan`, `composer phpmd`. Concurrency
  group cancels superseded runs.
- `php-test.yml`: PHPUnit inside wp-env, matrix of PHP `7.4/8.0/8.1/8.2`
  against WP `latest`, plus `7.4×WP 6.3` (oldest), `8.3×latest` (coverage) and
  `8.4×trunk` (experimental), wired through `WP_ENV_PHP_VERSION` /
  `WP_ENV_CORE`.

### 10up/distributor (10up flagship plugin)

Source: <https://github.com/10up/distributor/tree/develop/.github/workflows>

- Workflow files include `lint.yml`, `test.yml`, `cypress.yml` (e2e),
  `generate-zip.yml`, `release.yml`, `codeql-analysis.yml`,
  `dependency-review.yml`, `wordpress-version-checker.yml`.
- `lint.yml`: eslint with JSON report + `ataylorme/eslint-annotate-action`
  annotations, and a phpcs job that runs only on changed PHP files
  (`tj-actions/changed-files`), all actions pinned by commit SHA.

Both repos demonstrate the same shape: path-filtered lint job (PHPCS +
static analysis), matrixed test job, separate packaging/deploy workflows —
plus plugin-check-action (section 3) as the WP.org-conformance job.

---

## Comparison table: candidate PR checks

| Tool / check | What it catches | Setup cost | Runtime (typical) |
| --- | --- | --- | --- |
| PHPCS + WPCS 3.4.1 (`phpcs.xml.dist`) | WP coding-standard violations, escaping/sanitization/nonce sniffs, i18n text-domain misuse, deprecated WP functions (via `minimum_wp_version`) | composer require + ruleset file; needs sniff exclusions or the third-party baseline plugin for the existing PSR-ish code | Seconds (2 PHP files) |
| PHPCompatibilityWP 3.0@dev, `testVersion 7.4-` | Syntax/functions not available or removed across PHP 7.4–8.4+ | Same composer install; alpha-stability pin (`^3.0@dev`); stable 2.1.8 cannot see PHP 8 issues | Seconds |
| PHPStan 2 + phpstan-wordpress 2.0.3 + woocommerce-stubs v11 | Type errors, undefined WP/WC symbols, dead code (level-dependent); baseline supported natively | composer require + small neon file; pick level; maintainer-funding risk noted in README | Seconds–low minutes |
| plugin-check-action v1.1.9 | WP.org directory requirements: readme, plugin header, trademarks, i18n usage, security sniffs, file types, updater checks | One workflow step, zero config for defaults | Minutes (boots wp-env WordPress) |
| `node --test` (Node 22/24) | Existing `tests/*.test.js` cart-diff logic | `actions/setup-node` + one command; zero deps | Seconds |
| `php tests/*.php` matrix via setup-php v2 | Fatals on PHP 7.4–8.4 without WooCommerce (existing subprocess test) | One matrix job, `coverage: none`, no extensions | Seconds per PHP version |
| `wp i18n make-pot` + git diff | Stale POT vs source strings | Reuse existing `composer i18n:pot`; must handle `POT-Creation-Date` noise | Seconds |
| 10up build-zip action / deploy `dry-run` | `.distignore` mistakes, broken release packaging | One workflow step (already using 10up deploy) | ~1 minute |
| claude-code-action v1 (auto review) | Logic bugs, security issues, review-style feedback; not deterministic | GitHub app + one secret (`ANTHROPIC_API_KEY` or `CLAUDE_CODE_OAUTH_TOKEN` via `claude setup-token` for Pro/Max) + workflow | Minutes; token/subscription cost per PR |
| Copilot code review | Similar AI review, native GitHub UI | Enable in repo/ruleset; needs paid Copilot plan (not on Free; Pro free for OSS maintainers) | ~30 s; AI credits per review |
| CodeRabbit | Similar AI review + summaries | App install; free for public repos | Minutes |
| wp-env / Playground e2e | Real-browser smoke tests with WooCommerce | Highest: Docker (wp-env) or blueprint + assertions (Playground CLI); Playwright utils are v0.x | Multiple minutes |
