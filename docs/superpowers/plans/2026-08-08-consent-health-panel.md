# Consent Health Panel and Consent Wizard Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a health panel and a consent setup wizard to Settings › Barion Pixel, so a shop owner can see whether Barion consent works and can teach the plugin any cookie banner without writing code.

**Architecture:** Split the single plugin file into a bootstrap plus three includes. Health rules live in a pure static function that takes a facts array and returns check rows, so they run under plain PHP with no WordPress. The consent trigger matcher lives in its own browser script that also exports itself to Node, so it runs under `node --test`. The wizard is a native `<dialog>`; a nonce-gated front-end recorder observes the banner and reports back to the admin tab with `postMessage`.

**Tech Stack:** PHP 7.2+, WordPress 5.0+, WooCommerce 5.0+, vanilla JavaScript (no build step, no framework), plain PHP assertions and `node --test` for checks.

Design spec: `docs/superpowers/specs/2026-08-08-consent-health-panel-design.md`
Mockup: `docs/superpowers/specs/2026-08-08-consent-health-panel-mockup.html`

## Global Constraints

- PHP 7.2 compatible. No arrow functions, no typed properties, no null coalescing assignment, no `match`. Array syntax stays `array()` to match the existing file.
- No new runtime dependency. `composer.json` keeps `wp-cli/i18n-command` as its only dev requirement.
- No build step. JavaScript ships as written and is loaded with `wp_enqueue_script`.
- Every user-facing string uses `__()`, `esc_html__()` or `esc_attr__()` with the text domain `advanced-pixel-for-barion`.
- Every admin write path checks `current_user_can('manage_options')` and verifies a nonce.
- Never store or execute user-supplied JavaScript. The trigger stores cookie names, cookie values and event names only.
- Existing option key stays `wc_barion_pixel_settings`. Existing keys `pixel_id`, `enable_full_tracking`, `debug_mode` keep their names and meanings.
- Existing public JavaScript API stays: `window.wcBarionGrantConsent()`, `window.wcBarionRejectConsent()`, the `wcBarionGrantConsent` / `wcBarionRejectConsent` DOM events, and the `wc_barion_pixel_footer_scripts` action.
- Class and function prefixes stay `WC_Barion_` and `wc_barion_` for PHP, `wcBarion` for JavaScript globals.
- The consent category is `marketing` and is not configurable.
- **Commits:** the maintainer approves commits. Before the first `git commit`, stage the work, show the diff and ask for a go-ahead. Once given, the remaining commit steps may run without asking again.

---

### Task 1: Split the plugin into a bootstrap and includes

Pure move. No behaviour changes. This exists so later tasks have somewhere to put code.

**Files:**
- Modify: `advanced-pixel-for-barion.php` (whole file)
- Create: `includes/class-wc-barion-pixel.php`
- Create: `includes/class-wc-barion-admin.php`
- Modify: `.distignore`

**Interfaces:**
- Consumes: nothing.
- Produces:
  - `WC_Barion_Pixel::get_instance()` — unchanged singleton, now in `includes/class-wc-barion-pixel.php`.
  - `WC_Barion_Pixel::get_options(): array` — new public accessor returning the settings array.
  - `WC_Barion_Admin::get_instance()` — new singleton holding every admin hook.
  - Constants `WC_BARION_PIXEL_VERSION`, `WC_BARION_PIXEL_PATH`, `WC_BARION_PIXEL_URL` stay in the bootstrap.

- [ ] **Step 1: Create `includes/class-wc-barion-pixel.php`**

Move the entire `class WC_Barion_Pixel { … }` block out of `advanced-pixel-for-barion.php` into the new file. Keep every method byte-identical except the four changes below.

Head of the new file:

```php
<?php
/**
 * Front-end Barion Pixel tracking.
 *
 * @package Advanced_Pixel_For_Barion
 */

if (!defined('ABSPATH')) {
    exit;
}
```

Change 1 — remove the two admin hooks from the constructor. Delete these lines:

```php
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
```

Change 2 — delete these methods from this class; they move to `WC_Barion_Admin` in Step 2:
`add_admin_menu`, `register_settings`, `sanitize_settings`, `render_settings_page`,
`render_section_description`, `render_pixel_id_field`, `render_enable_tracking_field`,
`render_debug_mode_field`.

Change 3 — make `is_full_tracking_enabled` and `is_debug_mode` public instead of private, so the
admin class can read them.

Change 4 — add this accessor at the end of the class:

```php
    /**
     * Get the plugin options.
     *
     * @return array The settings array.
     */
    public function get_options() {
        return $this->options;
    }
```

- [ ] **Step 2: Create `includes/class-wc-barion-admin.php`**

```php
<?php
/**
 * Admin settings screen for Advanced Pixel for Barion.
 *
 * @package Advanced_Pixel_For_Barion
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Barion_Admin {

    /**
     * Plugin instance
     *
     * @var WC_Barion_Admin|null
     */
    private static $instance = null;

    /**
     * Plugin options
     *
     * @var array
     */
    private $options;

    /**
     * Get plugin instance (singleton accessor)
     *
     * @return WC_Barion_Admin The admin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->options = get_option('wc_barion_pixel_settings', array(
            'pixel_id' => '',
            'enable_full_tracking' => true,
            'debug_mode' => false
        ));

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
}
```

Then paste the eight methods removed in Step 1 into this class, unchanged.

- [ ] **Step 3: Rewrite `advanced-pixel-for-barion.php` as a bootstrap**

Keep the plugin header comment and the three `define()` calls exactly as they are today. Replace
everything from `class WC_Barion_Pixel {` to the end of the file with:

```php
require_once WC_BARION_PIXEL_PATH . 'includes/class-wc-barion-pixel.php';
require_once WC_BARION_PIXEL_PATH . 'includes/class-wc-barion-admin.php';

// Declare HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Initialize plugin
function wc_barion_pixel_init() {
    WC_Barion_Pixel::get_instance();
    if (is_admin()) {
        WC_Barion_Admin::get_instance();
    }
}
add_action('plugins_loaded', 'wc_barion_pixel_init');
```

The WP Consent API registration filter must stay keyed to the main plugin file, so move this line
from the `WC_Barion_Pixel` constructor into the bootstrap, directly above `wc_barion_pixel_init`:

```php
// Register as WP Consent API compatible. Must run from the main plugin file so
// plugin_basename() resolves to the plugin the API knows about.
add_filter('wp_consent_api_registered_' . plugin_basename(__FILE__), '__return_true');
```

- [ ] **Step 4: Check the syntax of every PHP file**

Run: `php -l advanced-pixel-for-barion.php && php -l includes/class-wc-barion-pixel.php && php -l includes/class-wc-barion-admin.php`
Expected: `No syntax errors detected` three times.

- [ ] **Step 5: Add the build exclusions**

Append two lines to `.distignore`:

```
tests/
.superpowers/
```

- [ ] **Step 6: Verify nothing changed for a visitor**

On a WordPress site with the plugin active and a Pixel ID set, load any front-end page and
confirm `bp.js` still loads and `bp('init', 'addBarionPixelId', …)` still runs. Then open
Settings › Barion Pixel and confirm all three fields render and save.

- [ ] **Step 7: Commit**

```bash
git add advanced-pixel-for-barion.php includes/ .distignore
git commit -m "refactor: split plugin into bootstrap, tracking class and admin class"
```

---

### Task 2: Health rules as a pure function, with tests

The rules are the heart of the feature, and they are pure, so they get a real test first.

**Files:**
- Create: `includes/class-wc-barion-health.php`
- Create: `tests/health-test.php`

**Interfaces:**
- Consumes: nothing.
- Produces:
  - `WC_Barion_Health::evaluate( array $facts ): array` — returns an ordered list of check rows.
  - `WC_Barion_Health::overall_status( array $checks ): string` — returns `ok`, `warn` or `fail`.
  - Check row shape, all six keys always present:

    ```php
    array(
        'id'     => 'consent_type_set',
        'status' => 'fail',   // 'ok' | 'warn' | 'fail' | 'info'
        'label'  => '',       // one line, already translated
        'desc'   => '',       // may be an empty string
        'action' => null,     // null | array('type' => 'modal'|'button'|'link', 'label' => string, 'target' => string)
        'target' => '',       // row-level detail; only consent_source uses it, to name the live tier
    )
    ```
  - Facts array shape, all keys always present:

    ```php
    array(
        'pixel_id'           => '',    // string
        'woocommerce_active' => false, // bool
        'full_tracking'      => true,  // bool
        'consent_api_active' => false, // bool
        'consent_type'       => '',    // string: '' | 'optin' | 'optout'
        'cli_active'         => false, // bool
        'trigger'            => null,  // null | array('grant' => array|null, 'reject' => array|null)
        'gateway_pixel_id'   => '',    // string
        'browser_probe'      => null,  // null | array('consent_type' => string, 'has_consent' => bool)
        'reachability'       => null,  // null | array('ok' => bool)
    )
    ```

- [ ] **Step 1: Write the failing test**

Create `tests/health-test.php`:

```php
<?php
/**
 * Dependency-free checks for the health rules.
 *
 * Run: php tests/health-test.php
 */

// The health class guards on ABSPATH and uses the translation helpers.
// Stub both so the rules run without WordPress.
define('ABSPATH', __DIR__);

if (!function_exists('__')) {
    function __($text, $domain = null) {
        return $text;
    }
}

require_once __DIR__ . '/../includes/class-wc-barion-health.php';

$failures = 0;

function apb_assert($condition, $message) {
    global $failures;
    if ($condition) {
        echo "  ok   $message\n";
    } else {
        $failures++;
        echo "  FAIL $message\n";
    }
}

function apb_facts($overrides = array()) {
    return array_merge(array(
        'pixel_id'           => 'BP-1234567890-01',
        'woocommerce_active' => true,
        'full_tracking'      => true,
        'consent_api_active' => true,
        'consent_type'       => 'optin',
        'cli_active'         => false,
        'trigger'            => null,
        'gateway_pixel_id'   => '',
        'browser_probe'      => null,
        'reachability'       => null,
    ), $overrides);
}

function apb_check($checks, $id) {
    foreach ($checks as $check) {
        if ($check['id'] === $id) {
            return $check;
        }
    }
    return null;
}

echo "A healthy WP Consent API site\n";
$checks = WC_Barion_Health::evaluate(apb_facts());
apb_assert('ok' === apb_check($checks, 'pixel_id')['status'], 'pixel_id is ok');
apb_assert('ok' === apb_check($checks, 'consent_type_set')['status'], 'consent_type_set is ok');
apb_assert('ok' === WC_Barion_Health::overall_status($checks), 'overall is ok');

echo "Silent consent: consent API active, no banner sets a consent type\n";
$checks = WC_Barion_Health::evaluate(apb_facts(array('consent_type' => '')));
apb_assert('warn' === apb_check($checks, 'consent_type_set')['status'], 'consent_type_set warns before the probe');
apb_assert(null !== apb_check($checks, 'consent_type_set')['action'], 'consent_type_set offers an action');

echo "Silent consent confirmed by the browser probe\n";
$checks = WC_Barion_Health::evaluate(apb_facts(array(
    'consent_type'  => '',
    'browser_probe' => array('consent_type' => '', 'has_consent' => true),
)));
apb_assert('fail' === apb_check($checks, 'consent_type_set')['status'], 'confirmed silent consent fails');
apb_assert('fail' === WC_Barion_Health::overall_status($checks), 'overall is fail');

echo "Browser probe clears a client-side-only banner\n";
$checks = WC_Barion_Health::evaluate(apb_facts(array(
    'consent_type'  => '',
    'browser_probe' => array('consent_type' => 'optin', 'has_consent' => false),
)));
apb_assert('ok' === apb_check($checks, 'consent_type_set')['status'], 'client-side consent type is ok');

echo "No consent source at all\n";
$checks = WC_Barion_Health::evaluate(apb_facts(array(
    'consent_api_active' => false,
    'consent_type'       => '',
)));
apb_assert('fail' === apb_check($checks, 'consent_source')['status'], 'no source fails');
apb_assert(null === apb_check($checks, 'consent_type_set'), 'consent_type_set is absent without the consent API');

echo "A learned trigger with accept but no reject\n";
$checks = WC_Barion_Health::evaluate(apb_facts(array(
    'consent_api_active' => false,
    'consent_type'       => '',
    'trigger'            => array(
        'grant'  => array('cookie' => 'cky-consent', 'contains' => 'yes', 'events' => array()),
        'reject' => null,
    ),
)));
apb_assert('fail' === apb_check($checks, 'consent_both_signals')['status'], 'a half-taught trigger fails');
apb_assert('none' === apb_check($checks, 'consent_source')['target'], 'a half-taught trigger is not a usable source');

echo "A complete learned trigger\n";
$checks = WC_Barion_Health::evaluate(apb_facts(array(
    'consent_api_active' => false,
    'consent_type'       => '',
    'trigger'            => array(
        'grant'  => array('cookie' => 'cky-consent', 'contains' => 'ad:yes', 'events' => array()),
        'reject' => array('cookie' => 'cky-consent', 'contains' => 'ad:no', 'events' => array()),
    ),
)));
apb_assert('info' === apb_check($checks, 'consent_source')['status'], 'a complete trigger is a valid source');
apb_assert('ok' === WC_Barion_Health::overall_status($checks), 'overall is ok');

echo "A malformed Pixel ID\n";
$checks = WC_Barion_Health::evaluate(apb_facts(array('pixel_id' => 'BP-123')));
apb_assert('fail' === apb_check($checks, 'pixel_id')['status'], 'a malformed pixel id fails');

echo "A missing Pixel ID\n";
$checks = WC_Barion_Health::evaluate(apb_facts(array('pixel_id' => '')));
apb_assert('fail' === apb_check($checks, 'pixel_id')['status'], 'a missing pixel id fails');

echo "WooCommerce inactive while full tracking is on\n";
$checks = WC_Barion_Health::evaluate(apb_facts(array('woocommerce_active' => false)));
apb_assert('warn' === apb_check($checks, 'woocommerce')['status'], 'missing WooCommerce warns');

echo "The payment gateway holds a second Pixel ID\n";
$checks = WC_Barion_Health::evaluate(apb_facts(array('gateway_pixel_id' => 'BP-9999999999-99')));
apb_assert('warn' === apb_check($checks, 'gateway_duplicate_id')['status'], 'a duplicate id warns');

echo "Rows are ordered worst first\n";
$checks = WC_Barion_Health::evaluate(apb_facts(array('pixel_id' => '', 'gateway_pixel_id' => 'BP-9999999999-99')));
$order = array();
foreach ($checks as $check) {
    $order[] = $check['status'];
}
$rank = array('fail' => 0, 'warn' => 1, 'ok' => 2, 'info' => 3);
$sorted = true;
for ($i = 1; $i < count($order); $i++) {
    if ($rank[$order[$i - 1]] > $rank[$order[$i]]) {
        $sorted = false;
    }
}
apb_assert($sorted, 'rows are sorted fail, warn, ok, info');

echo "\n";
if ($failures > 0) {
    echo "$failures check(s) failed\n";
    exit(1);
}
echo "All checks passed\n";
exit(0);
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php tests/health-test.php`
Expected: FAIL — `Failed opening required '…/includes/class-wc-barion-health.php'`.

- [ ] **Step 3: Write `includes/class-wc-barion-health.php`**

```php
<?php
/**
 * Health rules for Advanced Pixel for Barion.
 *
 * evaluate() is pure: it reads only the facts array and calls no WordPress
 * function, so tests/health-test.php can run it under plain PHP.
 *
 * @package Advanced_Pixel_For_Barion
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Barion_Health {

    /**
     * Valid Barion Pixel ID format.
     */
    const PIXEL_ID_PATTERN = '/^BP-\d{10}-\d{2}$/';

    /**
     * Turn a facts array into an ordered list of check rows.
     *
     * @param array $facts See the plan for the required keys.
     * @return array List of check rows, worst status first.
     */
    public static function evaluate($facts) {
        $checks = array();

        $checks[] = self::check_pixel_id($facts);

        $source = self::resolve_source($facts);
        $checks[] = self::check_consent_source($facts, $source);

        if (!empty($facts['consent_api_active'])) {
            $checks[] = self::check_consent_type($facts);
        }

        $both = self::check_both_signals($facts);
        if (null !== $both) {
            $checks[] = $both;
        }

        $checks[] = self::check_woocommerce($facts);
        $checks[] = self::check_full_tracking($facts);

        $duplicate = self::check_gateway_duplicate($facts);
        if (null !== $duplicate) {
            $checks[] = $duplicate;
        }

        $checks[] = self::check_category();
        $checks[] = self::check_reachability($facts);

        return self::sort_checks($checks);
    }

    /**
     * Reduce a list of check rows to a single overall status.
     *
     * @param array $checks Check rows from evaluate().
     * @return string One of 'ok', 'warn', 'fail'.
     */
    public static function overall_status($checks) {
        $worst = 'ok';
        foreach ($checks as $check) {
            if ('fail' === $check['status']) {
                return 'fail';
            }
            if ('warn' === $check['status']) {
                $worst = 'warn';
            }
        }
        return $worst;
    }

    /**
     * Decide which consent tier is live. Mirrors the order in barion-pixel-base.js.
     *
     * @param array $facts Facts array.
     * @return string One of 'learned', 'wp-consent-api', 'cookie-law-info', 'none'.
     */
    private static function resolve_source($facts) {
        if (!empty($facts['trigger']['grant']) && !empty($facts['trigger']['reject'])) {
            return 'learned';
        }
        if (!empty($facts['consent_api_active']) && '' !== $facts['consent_type']) {
            return 'wp-consent-api';
        }
        if (!empty($facts['cli_active'])) {
            return 'cookie-law-info';
        }
        return 'none';
    }

    private static function row($id, $status, $label, $desc, $action = null, $target = '') {
        return array(
            'id'     => $id,
            'status' => $status,
            'label'  => $label,
            'desc'   => $desc,
            'action' => $action,
            'target' => $target,
        );
    }

    private static function check_pixel_id($facts) {
        if ('' === trim($facts['pixel_id'])) {
            return self::row(
                'pixel_id',
                'fail',
                __('Pixel ID is missing', 'advanced-pixel-for-barion'),
                __('Nothing is tracked until you enter your Barion Pixel ID.', 'advanced-pixel-for-barion')
            );
        }
        if (!preg_match(self::PIXEL_ID_PATTERN, $facts['pixel_id'])) {
            return self::row(
                'pixel_id',
                'fail',
                __('Pixel ID has the wrong format', 'advanced-pixel-for-barion'),
                __('The ID must look like BP-0000000000-00. Barion ignores anything else.', 'advanced-pixel-for-barion')
            );
        }
        return self::row(
            'pixel_id',
            'ok',
            __('Pixel ID', 'advanced-pixel-for-barion'),
            $facts['pixel_id']
        );
    }

    private static function check_consent_source($facts, $source) {
        if ('none' === $source) {
            return self::row(
                'consent_source',
                'fail',
                __('No consent source is set up', 'advanced-pixel-for-barion'),
                __('Barion never receives consent, so full tracking collects no marketing data. Teach the plugin your cookie banner.', 'advanced-pixel-for-barion'),
                array(
                    'type'   => 'modal',
                    'label'  => __('Set up consent', 'advanced-pixel-for-barion'),
                    'target' => 'wizard',
                ),
                $source
            );
        }

        $labels = array(
            'learned'         => __('Consent comes from your cookie banner, recorded by the wizard', 'advanced-pixel-for-barion'),
            'wp-consent-api'  => __('Consent comes from the WP Consent API', 'advanced-pixel-for-barion'),
            'cookie-law-info' => __('Consent comes from Cookie Law Info', 'advanced-pixel-for-barion'),
        );

        return self::row(
            'consent_source',
            'info',
            $labels[$source],
            __('Barion receives grantConsent and rejectConsent from this source.', 'advanced-pixel-for-barion'),
            array(
                'type'   => 'modal',
                'label'  => __('Change', 'advanced-pixel-for-barion'),
                'target' => 'wizard',
            ),
            $source
        );
    }

    private static function check_consent_type($facts) {
        if ('' !== $facts['consent_type']) {
            return self::row(
                'consent_type_set',
                'ok',
                __('A cookie banner plugin sets the consent type', 'advanced-pixel-for-barion'),
                __('Visitor choices reach the plugin correctly.', 'advanced-pixel-for-barion')
            );
        }

        $probe = $facts['browser_probe'];

        if (null === $probe) {
            return self::row(
                'consent_type_set',
                'warn',
                __('No cookie banner plugin sets a consent type', 'advanced-pixel-for-barion'),
                __('When nothing sets a consent type, the WP Consent API reports consent for every visitor, and the pixel sends grantConsent without any consent. Some banners set the consent type in the browser only, so check there before you act.', 'advanced-pixel-for-barion'),
                array(
                    'type'   => 'button',
                    'label'  => __('Check in browser', 'advanced-pixel-for-barion'),
                    'target' => 'probe',
                )
            );
        }

        if ('' !== $probe['consent_type']) {
            return self::row(
                'consent_type_set',
                'ok',
                __('Your cookie banner sets the consent type in the browser', 'advanced-pixel-for-barion'),
                __('Nothing is set on the server, but the browser check found a consent type. Visitor choices reach the plugin correctly.', 'advanced-pixel-for-barion')
            );
        }

        if (!empty($probe['has_consent'])) {
            return self::row(
                'consent_type_set',
                'fail',
                __('Every visitor is counted as consenting', 'advanced-pixel-for-barion'),
                __('The browser check found marketing consent granted before anyone touched a banner. The pixel sends grantConsent for every visitor. This breaks the GDPR and the Barion terms. Install a cookie banner plugin that supports the WP Consent API, or teach the plugin your banner.', 'advanced-pixel-for-barion'),
                array(
                    'type'   => 'modal',
                    'label'  => __('Set up consent', 'advanced-pixel-for-barion'),
                    'target' => 'wizard',
                )
            );
        }

        return self::row(
            'consent_type_set',
            'ok',
            __('Consent is not granted automatically', 'advanced-pixel-for-barion'),
            __('The browser check found no consent before any banner interaction.', 'advanced-pixel-for-barion')
        );
    }

    private static function check_both_signals($facts) {
        if (empty($facts['trigger'])) {
            return null;
        }

        $has_grant  = !empty($facts['trigger']['grant']);
        $has_reject = !empty($facts['trigger']['reject']);

        if ($has_grant && $has_reject) {
            return self::row(
                'consent_both_signals',
                'ok',
                __('Accept and reject are both recorded', 'advanced-pixel-for-barion'),
                __('Barion requires both grantConsent and rejectConsent.', 'advanced-pixel-for-barion')
            );
        }

        $missing = $has_grant
            ? __('The reject signal is missing.', 'advanced-pixel-for-barion')
            : __('The accept signal is missing.', 'advanced-pixel-for-barion');

        return self::row(
            'consent_both_signals',
            'fail',
            __('Only one consent signal is recorded', 'advanced-pixel-for-barion'),
            $missing . ' ' . __('Barion requires both grantConsent and rejectConsent. Record the missing one.', 'advanced-pixel-for-barion'),
            array(
                'type'   => 'modal',
                'label'  => __('Finish setup', 'advanced-pixel-for-barion'),
                'target' => 'wizard',
            )
        );
    }

    private static function check_woocommerce($facts) {
        if (!empty($facts['woocommerce_active'])) {
            return self::row(
                'woocommerce',
                'ok',
                __('WooCommerce is active', 'advanced-pixel-for-barion'),
                ''
            );
        }
        if (empty($facts['full_tracking'])) {
            return self::row(
                'woocommerce',
                'info',
                __('WooCommerce is not active', 'advanced-pixel-for-barion'),
                __('Full tracking is off, so nothing depends on WooCommerce.', 'advanced-pixel-for-barion')
            );
        }
        return self::row(
            'woocommerce',
            'warn',
            __('WooCommerce is not active', 'advanced-pixel-for-barion'),
            __('Full tracking is on, but the e-commerce events need WooCommerce. Only the base pixel runs.', 'advanced-pixel-for-barion')
        );
    }

    private static function check_full_tracking($facts) {
        if (!empty($facts['full_tracking'])) {
            return self::row(
                'full_tracking',
                'info',
                __('Full tracking is on', 'advanced-pixel-for-barion'),
                __('contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail', 'advanced-pixel-for-barion')
            );
        }
        return self::row(
            'full_tracking',
            'info',
            __('Full tracking is off', 'advanced-pixel-for-barion'),
            __('Only the base pixel runs. It sends pageView for fraud prevention.', 'advanced-pixel-for-barion')
        );
    }

    private static function check_gateway_duplicate($facts) {
        if ('' === trim($facts['gateway_pixel_id']) || '' === trim($facts['pixel_id'])) {
            return null;
        }
        return self::row(
            'gateway_duplicate_id',
            'warn',
            __('Barion Payment Gateway holds a second Pixel ID', 'advanced-pixel-for-barion'),
            __('Both plugins work together and the base script loads only once. You may remove the ID from the gateway to keep one source of truth.', 'advanced-pixel-for-barion')
        );
    }

    private static function check_category() {
        return self::row(
            'category',
            'info',
            __('Consent category: marketing', 'advanced-pixel-for-barion'),
            __('Fixed, and not configurable. Barion requires consent for marketing purposes, so marketing is the only correct category. Your cookie banner maps its own advertising category onto it.', 'advanced-pixel-for-barion')
        );
    }

    private static function check_reachability($facts) {
        if (null === $facts['reachability']) {
            return self::row(
                'reachability',
                'info',
                __('Barion pixel script reachable', 'advanced-pixel-for-barion'),
                __('Loads bp.js from this browser. Nothing is sent to your server.', 'advanced-pixel-for-barion'),
                array(
                    'type'   => 'button',
                    'label'  => __('Test', 'advanced-pixel-for-barion'),
                    'target' => 'reachability',
                )
            );
        }
        if (!empty($facts['reachability']['ok'])) {
            return self::row(
                'reachability',
                'ok',
                __('Barion pixel script is reachable', 'advanced-pixel-for-barion'),
                __('bp.js loaded from this browser.', 'advanced-pixel-for-barion')
            );
        }
        return self::row(
            'reachability',
            'warn',
            __('Barion pixel script did not load', 'advanced-pixel-for-barion'),
            __('bp.js did not load in this browser. An ad blocker or a network rule may block it. Your visitors may still reach it.', 'advanced-pixel-for-barion'),
            array(
                'type'   => 'button',
                'label'  => __('Test again', 'advanced-pixel-for-barion'),
                'target' => 'reachability',
            )
        );
    }

    /**
     * Sort rows worst first, keeping the original order inside each status.
     *
     * @param array $checks Check rows.
     * @return array Sorted rows.
     */
    private static function sort_checks($checks) {
        $rank = array('fail' => 0, 'warn' => 1, 'ok' => 2, 'info' => 3);
        $buckets = array(array(), array(), array(), array());
        foreach ($checks as $check) {
            $buckets[$rank[$check['status']]][] = $check;
        }
        return array_merge($buckets[0], $buckets[1], $buckets[2], $buckets[3]);
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `php tests/health-test.php`
Expected: PASS — `All checks passed`, exit code 0.

- [ ] **Step 5: Commit**

```bash
git add includes/class-wc-barion-health.php tests/health-test.php
git commit -m "feat: add pure health rules with dependency-free checks"
```

---

### Task 3: Gather the facts and validate the Pixel ID

**Files:**
- Modify: `includes/class-wc-barion-health.php` (append one method)
- Modify: `includes/class-wc-barion-admin.php` (`sanitize_settings`)

**Interfaces:**
- Consumes: `WC_Barion_Health::evaluate()`, `WC_Barion_Health::PIXEL_ID_PATTERN`.
- Produces: `WC_Barion_Health::gather_facts( array $options ): array` — builds the facts array from WordPress state. `$options` is the `wc_barion_pixel_settings` array.

- [ ] **Step 1: Append `gather_facts()` to `WC_Barion_Health`**

Insert this method directly after `overall_status()`:

```php
    /**
     * Read WordPress state into a facts array for evaluate().
     *
     * This is the only method in this class that touches WordPress.
     *
     * @param array $options The wc_barion_pixel_settings option.
     * @return array Facts array.
     */
    public static function gather_facts($options) {
        $gateway = get_option('woocommerce_barion_settings', array());
        $probe   = get_option('wc_barion_pixel_probe', array());

        return array(
            'pixel_id'           => isset($options['pixel_id']) ? $options['pixel_id'] : '',
            'woocommerce_active' => class_exists('WooCommerce'),
            'full_tracking'      => !empty($options['enable_full_tracking']),
            'consent_api_active' => function_exists('wp_has_consent'),
            'consent_type'       => function_exists('wp_get_consent_type') ? (string) wp_get_consent_type() : '',
            'cli_active'         => defined('CLI_PLUGIN_FILE') || function_exists('cookielawinfo_init'),
            'trigger'            => isset($options['consent_trigger']) ? $options['consent_trigger'] : null,
            'gateway_pixel_id'   => isset($gateway['barion_pixel_id']) ? $gateway['barion_pixel_id'] : '',
            'browser_probe'      => isset($probe['consent']) ? $probe['consent'] : null,
            'reachability'       => isset($probe['reachability']) ? $probe['reachability'] : null,
        );
    }
```

`wc_barion_pixel_probe` holds the results of the two browser checks. Task 8 writes it.

- [ ] **Step 2: Validate the Pixel ID in `sanitize_settings`**

In `includes/class-wc-barion-admin.php`, replace the whole `sanitize_settings` method with:

```php
    /**
     * Sanitize settings input
     *
     * @param array $input The raw settings input from the form
     * @return array The sanitized settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        if (isset($input['pixel_id'])) {
            $pixel_id = sanitize_text_field($input['pixel_id']);

            // Keep the stored ID rather than overwrite a working pixel with a typo.
            if ('' !== $pixel_id && !preg_match(WC_Barion_Health::PIXEL_ID_PATTERN, $pixel_id)) {
                add_settings_error(
                    'wc_barion_pixel_settings',
                    'pixel_id_format',
                    __('The Pixel ID must look like BP-0000000000-00. Your previous ID was kept.', 'advanced-pixel-for-barion'),
                    'error'
                );
                $pixel_id = isset($this->options['pixel_id']) ? $this->options['pixel_id'] : '';
            }

            $sanitized['pixel_id'] = $pixel_id;
        }

        $sanitized['enable_full_tracking'] = isset($input['enable_full_tracking']) ? true : false;
        $sanitized['debug_mode'] = isset($input['debug_mode']) ? true : false;

        // The wizard owns this key; the settings form never posts it.
        if (isset($this->options['consent_trigger'])) {
            $sanitized['consent_trigger'] = $this->options['consent_trigger'];
        }

        return $sanitized;
    }
```

Add the include for the health class to the bootstrap, above the admin class include:

```php
require_once WC_BARION_PIXEL_PATH . 'includes/class-wc-barion-health.php';
```

- [ ] **Step 3: Verify the validation by hand**

1. Open Settings › Barion Pixel and save a valid ID such as `BP-1234567890-01`. It saves.
2. Change it to `BP-123` and save. An error notice appears, and the field shows `BP-1234567890-01` again.
3. Clear the field and save. It saves empty, with no error.

- [ ] **Step 4: Run the health test again to confirm nothing regressed**

Run: `php tests/health-test.php`
Expected: PASS — `All checks passed`.

- [ ] **Step 5: Commit**

```bash
git add includes/
git commit -m "feat: gather health facts from WordPress and validate the Pixel ID"
```

---

### Task 4: Render the health panel

**Files:**
- Modify: `includes/class-wc-barion-admin.php`
- Create: `assets/css/barion-admin.css`

**Interfaces:**
- Consumes: `WC_Barion_Health::gather_facts()`, `WC_Barion_Health::evaluate()`, `WC_Barion_Health::overall_status()`.
- Produces:
  - `WC_Barion_Admin::render_health_panel(): void` — echoes the panel.
  - `WC_Barion_Admin::enqueue_admin_assets( string $hook ): void` — hooked to `admin_enqueue_scripts`.
  - CSS class names used by Task 8's JavaScript: `.apb-panel`, `.apb-panel-head`, `.apb-rows`, `.apb-row`, `.apb-action`.

- [ ] **Step 1: Create `assets/css/barion-admin.css`**

```css
/* Health panel */
.apb-panel { background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin: 16px 0; }
.apb-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px; border-bottom: 1px solid #dcdcde; }
.apb-panel.is-ok .apb-panel-head { border-left: 4px solid #00a32a; }
.apb-panel.is-warn .apb-panel-head { border-left: 4px solid #dba617; }
.apb-panel.is-fail .apb-panel-head { border-left: 4px solid #d63638; }
.apb-panel.is-ok.is-collapsed .apb-panel-head { border-bottom: 0; }
.apb-status { display: flex; align-items: center; gap: 9px; font-size: 14px; font-weight: 600; }
.apb-dot { width: 10px; height: 10px; border-radius: 50%; flex: 0 0 auto; }
.apb-dot.is-ok { background: #00a32a; }
.apb-dot.is-warn { background: #dba617; }
.apb-dot.is-fail { background: #d63638; }
.apb-dot.is-info { background: #a7aaad; }
.apb-rows { margin: 0; }
.apb-panel.is-collapsed .apb-rows { display: none; }
.apb-row { display: flex; align-items: flex-start; gap: 10px; padding: 11px 16px; border-bottom: 1px solid #f0f0f1; }
.apb-row:last-child { border-bottom: 0; }
.apb-ico { flex: 0 0 auto; width: 18px; text-align: center; font-weight: 700; line-height: 20px; }
.apb-ico.is-ok { color: #00a32a; }
.apb-ico.is-warn { color: #dba617; }
.apb-ico.is-fail { color: #d63638; }
.apb-ico.is-info { color: #a7aaad; }
.apb-body { flex: 1 1 auto; min-width: 0; }
.apb-label { font-weight: 600; }
.apb-desc { color: #50575e; margin-top: 3px; }
.apb-action { flex: 0 0 auto; }
.apb-mono { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 12px; background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 3px; padding: 1px 5px; }

/* Wizard dialog */
.apb-dialog { border: 0; border-radius: 4px; padding: 0; max-width: 560px; width: 92%; box-shadow: 0 4px 24px rgba(0,0,0,.3); }
.apb-dialog::backdrop { background: rgba(0,0,0,.45); }
.apb-dialog-head { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border-bottom: 1px solid #dcdcde; }
.apb-dialog-head h2 { margin: 0; font-size: 15px; }
.apb-dialog-body { padding: 18px; }
.apb-dialog-foot { display: flex; justify-content: space-between; align-items: center; padding: 12px 18px; border-top: 1px solid #dcdcde; background: #f6f7f7; }
.apb-steps { display: flex; gap: 6px; align-items: center; font-size: 12px; color: #787c82; }
.apb-steps span { padding: 2px 9px; border-radius: 10px; background: #f0f0f1; }
.apb-steps span.is-on { background: #2271b1; color: #fff; }
.apb-choice { display: flex; gap: 9px; align-items: flex-start; border: 1px solid #dcdcde; border-radius: 4px; padding: 11px 13px; margin-bottom: 8px; cursor: pointer; }
.apb-choice.is-selected { border-color: #2271b1; background: #f0f6fc; box-shadow: 0 0 0 1px #2271b1; }
.apb-choice.is-disabled { opacity: .55; cursor: not-allowed; }
.apb-recorder { background: #f6f7f7; border: 1px dashed #c3c4c7; border-radius: 4px; padding: 13px; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 12px; }
.apb-step { display: none; }
.apb-step.is-active { display: block; }
.apb-hidden { display: none; }
```

- [ ] **Step 2: Add the panel renderer and the asset loader to `WC_Barion_Admin`**

Add to the constructor, after the two existing `add_action` calls:

```php
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
```

Add these two methods to the class:

```php
    /**
     * Load the panel and wizard assets on our settings page only.
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_admin_assets($hook) {
        if ('settings_page_advanced-pixel-for-barion' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'wc-barion-admin',
            WC_BARION_PIXEL_URL . 'assets/css/barion-admin.css',
            array(),
            WC_BARION_PIXEL_VERSION
        );
    }

    /**
     * Render the health panel above the settings form.
     *
     * @return void
     */
    public function render_health_panel() {
        $facts   = WC_Barion_Health::gather_facts($this->options);
        $checks  = WC_Barion_Health::evaluate($facts);
        $overall = WC_Barion_Health::overall_status($checks);

        $headline = array(
            'ok'   => __('Barion Pixel is healthy', 'advanced-pixel-for-barion'),
            'warn' => __('Barion Pixel needs attention', 'advanced-pixel-for-barion'),
            'fail' => __('Action needed', 'advanced-pixel-for-barion'),
        );

        $icons = array('ok' => '&#10003;', 'warn' => '!', 'fail' => '&#10007;', 'info' => '&#8226;');

        $collapsed = ('ok' === $overall) ? ' is-collapsed' : '';
        ?>
        <div class="apb-panel is-<?php echo esc_attr($overall); ?><?php echo esc_attr($collapsed); ?>" id="apb-panel">
            <div class="apb-panel-head">
                <div class="apb-status">
                    <span class="apb-dot is-<?php echo esc_attr($overall); ?>"></span>
                    <?php echo esc_html($headline[$overall]); ?>
                </div>
                <div>
                    <button type="button" class="button-link" id="apb-toggle">
                        <?php
                        printf(
                            /* translators: %d: number of health checks. */
                            esc_html__('Show %d checks', 'advanced-pixel-for-barion'),
                            count($checks)
                        );
                        ?>
                    </button>
                </div>
            </div>
            <div class="apb-rows">
                <?php foreach ($checks as $check) : ?>
                    <div class="apb-row" data-check="<?php echo esc_attr($check['id']); ?>">
                        <span class="apb-ico is-<?php echo esc_attr($check['status']); ?>">
                            <?php echo wp_kses_post($icons[$check['status']]); ?>
                        </span>
                        <span class="apb-body">
                            <span class="apb-label"><?php echo esc_html($check['label']); ?></span>
                            <?php if ('' !== $check['desc']) : ?>
                                <div class="apb-desc"><?php echo esc_html($check['desc']); ?></div>
                            <?php endif; ?>
                        </span>
                        <?php if (null !== $check['action']) : ?>
                            <span class="apb-action">
                                <button type="button"
                                        class="button<?php echo ('fail' === $check['status']) ? ' button-primary' : ''; ?>"
                                        data-apb-action="<?php echo esc_attr($check['action']['target']); ?>">
                                    <?php echo esc_html($check['action']['label']); ?>
                                </button>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
```

- [ ] **Step 3: Call the panel from the settings page**

In `render_settings_page`, insert the panel call between the `<h1>` and the `<form>`:

```php
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php $this->render_health_panel(); ?>
            <form method="post" action="options.php">
```

- [ ] **Step 4: Verify the panel by hand**

1. Clear the Pixel ID. Open Settings › Barion Pixel. The panel is red, expanded, and the missing Pixel ID row is first.
2. Set a valid Pixel ID with WooCommerce and a working banner active. The panel turns green and collapses to one line.
3. Compare with `docs/superpowers/specs/2026-08-08-consent-health-panel-mockup.html`.

- [ ] **Step 5: Commit**

```bash
git add includes/class-wc-barion-admin.php assets/css/barion-admin.css
git commit -m "feat: render the Barion Pixel health panel"
```

---

### Task 5: Consent trigger matcher, with tests

**Files:**
- Create: `assets/js/barion-consent-trigger.js`
- Create: `tests/trigger-test.mjs`

**Interfaces:**
- Consumes: nothing.
- Produces `window.wcBarionConsentTrigger`, also exported as a CommonJS module for the tests:
  - `sanitize(trigger): object|null` — returns a cleaned trigger, or `null` if it breaks a rule.
  - `matches(cookieString, trigger): boolean`
  - `evaluate(cookieString, config): 'grant'|'reject'|'none'` — `config` is `{grant, reject}`.
  - `eventNames(config): string[]` — every event name across both triggers, deduplicated. Task 6 uses it to attach listeners.

- [ ] **Step 1: Write the failing test**

Create `tests/trigger-test.mjs`:

```javascript
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const trigger = require('../assets/js/barion-consent-trigger.js');

const GRANT = { cookie: 'cky-consent', contains: 'ad:yes', events: [] };
const REJECT = { cookie: 'cky-consent', contains: 'ad:no', events: [] };

test('matches a cookie that contains the value', () => {
	assert.equal(trigger.matches('a=1; cky-consent=stats:yes|ad:yes; b=2', GRANT), true);
});

test('does not match a different value in the same cookie', () => {
	assert.equal(trigger.matches('cky-consent=stats:yes|ad:no', GRANT), false);
});

test('does not match a missing cookie', () => {
	assert.equal(trigger.matches('other=1', GRANT), false);
});

test('does not match a cookie whose name is a suffix of another', () => {
	assert.equal(trigger.matches('xcky-consent=ad:yes', GRANT), false);
});

test('matches on presence alone when contains is empty', () => {
	assert.equal(trigger.matches('flag=anything', { cookie: 'flag', contains: '', events: [] }), true);
});

test('decodes percent-encoded cookie values', () => {
	assert.equal(trigger.matches('cky-consent=ad%3Ayes', GRANT), true);
});

test('evaluate returns grant when only the grant trigger matches', () => {
	assert.equal(trigger.evaluate('cky-consent=ad:yes', { grant: GRANT, reject: REJECT }), 'grant');
});

test('evaluate returns reject when only the reject trigger matches', () => {
	assert.equal(trigger.evaluate('cky-consent=ad:no', { grant: GRANT, reject: REJECT }), 'reject');
});

test('evaluate returns none when neither matches', () => {
	assert.equal(trigger.evaluate('other=1', { grant: GRANT, reject: REJECT }), 'none');
});

test('evaluate returns none when both match, because that is ambiguous', () => {
	const both = { cookie: 'c', contains: 'x', events: [] };
	assert.equal(trigger.evaluate('c=x', { grant: both, reject: both }), 'none');
});

test('sanitize accepts a well formed trigger', () => {
	assert.deepEqual(trigger.sanitize({ cookie: 'cky-consent', contains: 'ad:yes', events: ['cky_update'] }), {
		cookie: 'cky-consent',
		contains: 'ad:yes',
		events: ['cky_update'],
	});
});

test('sanitize rejects a cookie name with illegal characters', () => {
	assert.equal(trigger.sanitize({ cookie: 'bad name;', contains: '', events: [] }), null);
});

test('sanitize rejects a missing cookie name', () => {
	assert.equal(trigger.sanitize({ contains: 'x', events: [] }), null);
});

test('sanitize drops event names with illegal characters', () => {
	assert.deepEqual(trigger.sanitize({ cookie: 'c', contains: '', events: ['ok_one', 'bad name'] }), {
		cookie: 'c',
		contains: '',
		events: ['ok_one'],
	});
});

test('sanitize caps the event list at five entries', () => {
	const many = ['a', 'b', 'c', 'd', 'e', 'f', 'g'];
	assert.equal(trigger.sanitize({ cookie: 'c', contains: '', events: many }).events.length, 5);
});

test('sanitize caps the contains value at 256 characters', () => {
	const long = 'x'.repeat(300);
	assert.equal(trigger.sanitize({ cookie: 'c', contains: long, events: [] }).contains.length, 256);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `node --test tests/trigger-test.mjs`
Expected: FAIL — cannot find `../assets/js/barion-consent-trigger.js`.

- [ ] **Step 3: Write `assets/js/barion-consent-trigger.js`**

```javascript
/**
 * Barion consent trigger — pure matching logic for a recorded cookie banner signal.
 *
 * Loaded in the browser as window.wcBarionConsentTrigger, and required by
 * tests/trigger-test.mjs under Node. It must stay free of DOM access so both work.
 */
(function (root, factory) {
	var api = factory();
	root.wcBarionConsentTrigger = api;
	if (typeof module === 'object' && module.exports) {
		module.exports = api;
	}
})(typeof self !== 'undefined' ? self : this, function () {
	var COOKIE_NAME = /^[A-Za-z0-9_\-.]{1,128}$/;
	var EVENT_NAME = /^[A-Za-z0-9_\-:.]{1,128}$/;
	var MAX_EVENTS = 5;
	var MAX_CONTAINS = 256;

	function escapeRegExp(text) {
		return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	}

	/**
	 * Clean a trigger, or return null when it breaks a rule.
	 * A trigger is dropped whole rather than partly repaired, except for event
	 * names, which are filtered — a bad event name must not discard a good cookie rule.
	 */
	function sanitize(trigger) {
		if (!trigger || typeof trigger !== 'object') {
			return null;
		}
		var cookie = typeof trigger.cookie === 'string' ? trigger.cookie : '';
		if (!COOKIE_NAME.test(cookie)) {
			return null;
		}

		var contains = typeof trigger.contains === 'string' ? trigger.contains : '';
		if (contains.length > MAX_CONTAINS) {
			contains = contains.slice(0, MAX_CONTAINS);
		}

		var events = [];
		if (Object.prototype.toString.call(trigger.events) === '[object Array]') {
			for (var i = 0; i < trigger.events.length && events.length < MAX_EVENTS; i++) {
				if (typeof trigger.events[i] === 'string' && EVENT_NAME.test(trigger.events[i])) {
					events.push(trigger.events[i]);
				}
			}
		}

		return { cookie: cookie, contains: contains, events: events };
	}

	function readCookie(cookieString, name) {
		var match = String(cookieString).match(
			new RegExp('(?:^|;\\s*)' + escapeRegExp(name) + '=([^;]*)')
		);
		if (!match) {
			return null;
		}
		try {
			return decodeURIComponent(match[1]);
		} catch (e) {
			return match[1];
		}
	}

	function matches(cookieString, trigger) {
		var clean = sanitize(trigger);
		if (!clean) {
			return false;
		}
		var value = readCookie(cookieString, clean.cookie);
		if (null === value) {
			return false;
		}
		if ('' === clean.contains) {
			return true;
		}
		return value.indexOf(clean.contains) !== -1;
	}

	/**
	 * Decide the consent state. Both matching is ambiguous, so it counts as none.
	 */
	function evaluate(cookieString, config) {
		if (!config) {
			return 'none';
		}
		var granted = matches(cookieString, config.grant);
		var rejected = matches(cookieString, config.reject);
		if (granted && !rejected) {
			return 'grant';
		}
		if (rejected && !granted) {
			return 'reject';
		}
		return 'none';
	}

	/**
	 * Every event name across both triggers, deduplicated.
	 */
	function eventNames(config) {
		var names = [];
		var sides = ['grant', 'reject'];
		for (var s = 0; s < sides.length; s++) {
			var clean = config ? sanitize(config[sides[s]]) : null;
			if (!clean) {
				continue;
			}
			for (var i = 0; i < clean.events.length; i++) {
				if (names.indexOf(clean.events[i]) === -1) {
					names.push(clean.events[i]);
				}
			}
		}
		return names;
	}

	return {
		sanitize: sanitize,
		matches: matches,
		evaluate: evaluate,
		eventNames: eventNames,
	};
});
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `node --test tests/trigger-test.mjs`
Expected: PASS — 16 tests pass.

- [ ] **Step 5: Commit**

```bash
git add assets/js/barion-consent-trigger.js tests/trigger-test.mjs
git commit -m "feat: add the consent trigger matcher with tests"
```

---

### Task 6: Store the trigger and use it on the front end

**Files:**
- Modify: `includes/class-wc-barion-pixel.php`
- Modify: `assets/js/barion-pixel-base.js:65-141`

**Interfaces:**
- Consumes: `window.wcBarionConsentTrigger` from Task 5.
- Produces:
  - `wcBarionPixelBase.trigger` — the localized trigger config, or `null`.
  - `WC_Barion_Pixel::sanitize_trigger( $trigger ): array|null` — public static, mirrors the JavaScript rules so the option can never hold anything else.

- [ ] **Step 1: Add the PHP sanitizer to `WC_Barion_Pixel`**

```php
    /**
     * Clean a stored consent trigger. Mirrors sanitize() in barion-consent-trigger.js.
     *
     * @param mixed $trigger The raw trigger from the option or a request.
     * @return array|null The cleaned trigger, or null when it breaks a rule.
     */
    public static function sanitize_trigger($trigger) {
        if (!is_array($trigger) || empty($trigger['cookie'])) {
            return null;
        }

        $cookie = (string) $trigger['cookie'];
        if (!preg_match('/^[A-Za-z0-9_\-.]{1,128}$/', $cookie)) {
            return null;
        }

        $contains = isset($trigger['contains']) ? sanitize_text_field((string) $trigger['contains']) : '';
        if (strlen($contains) > 256) {
            $contains = substr($contains, 0, 256);
        }

        $events = array();
        if (isset($trigger['events']) && is_array($trigger['events'])) {
            foreach ($trigger['events'] as $event) {
                if (count($events) >= 5) {
                    break;
                }
                if (is_string($event) && preg_match('/^[A-Za-z0-9_\-:.]{1,128}$/', $event)) {
                    $events[] = $event;
                }
            }
        }

        return array(
            'cookie'   => $cookie,
            'contains' => $contains,
            'events'   => $events,
        );
    }

    /**
     * Get the stored consent trigger pair, or null when it is absent or incomplete.
     *
     * Barion requires both grantConsent and rejectConsent, so a half-taught
     * trigger is treated as no trigger at all.
     *
     * @return array|null Array with 'grant' and 'reject' keys, or null.
     */
    private function get_consent_trigger() {
        if (empty($this->options['consent_trigger'])) {
            return null;
        }

        $stored = $this->options['consent_trigger'];
        $grant  = isset($stored['grant']) ? self::sanitize_trigger($stored['grant']) : null;
        $reject = isset($stored['reject']) ? self::sanitize_trigger($stored['reject']) : null;

        if (null === $grant || null === $reject) {
            return null;
        }

        return array('grant' => $grant, 'reject' => $reject);
    }
```

- [ ] **Step 2: Enqueue the matcher and pass the trigger**

Replace the whole `enqueue_base_script` method with:

```php
    public function enqueue_base_script() {
        wp_enqueue_script(
            'wc-barion-consent-trigger',
            WC_BARION_PIXEL_URL . 'assets/js/barion-consent-trigger.js',
            array(),
            WC_BARION_PIXEL_VERSION,
            false
        );

        wp_enqueue_script(
            'wc-barion-pixel-base',
            WC_BARION_PIXEL_URL . 'assets/js/barion-pixel-base.js',
            array('wc-barion-consent-trigger'),
            WC_BARION_PIXEL_VERSION,
            false
        );

        wp_localize_script('wc-barion-pixel-base', 'wcBarionPixelBase', array(
            'pixelId' => $this->options['pixel_id'],
            'debug'   => $this->is_debug_mode(),
            'trigger' => $this->get_consent_trigger(),
        ));
    }
```

- [ ] **Step 3: Add the learned trigger as tier 1 in `barion-pixel-base.js`**

Insert this function directly above `function wcBarionDetectConsent()`:

```javascript
	// Tier 0: a trigger recorded by the admin wizard. An explicit setting by the
	// shop owner beats every auto-detected consent manager.
	function wcBarionApplyLearnedTrigger() {
		var api = window.wcBarionConsentTrigger;
		var config = config_trigger();

		if (!api || !config) {
			return false;
		}

		var last = null;

		function check() {
			var state = api.evaluate(document.cookie, config);
			if (state === last || 'none' === state) {
				return;
			}
			last = state;
			if ('grant' === state) {
				window.wcBarionGrantConsent();
			} else {
				window.wcBarionRejectConsent();
			}
			if (debug) {
				console.log('[Barion Pixel] Consent ' + state + 'ed via the recorded cookie trigger');
			}
		}

		check();

		var names = api.eventNames(config);
		for (var i = 0; i < names.length; i++) {
			document.addEventListener(names[i], check);
			window.addEventListener(names[i], check);
		}

		// Some banners set their cookie without dispatching any event, so poll
		// briefly as well. The timer stops after 30 seconds; no page keeps it forever.
		var polls = 0;
		var timer = setInterval(function () {
			polls++;
			check();
			if (polls >= 60) {
				clearInterval(timer);
			}
		}, 500);

		return true;
	}

	function config_trigger() {
		var t = config.trigger;
		if (!t || !t.grant || !t.reject) {
			return null;
		}
		return t;
	}
```

Then add this as the first statement inside `wcBarionDetectConsent()`, above the WP Consent API block:

```javascript
		// --- Tier 0: a trigger recorded by the admin wizard ---
		if (wcBarionApplyLearnedTrigger()) {
			return;
		}
```

- [ ] **Step 4: Run both test suites**

Run: `php tests/health-test.php && node --test tests/trigger-test.mjs`
Expected: both PASS.

- [ ] **Step 5: Verify on the front end by hand**

1. With no trigger stored, load a page. Consent behaves exactly as before this task.
2. Set the option by hand in the database:

```bash
wp option patch update wc_barion_pixel_settings consent_trigger --format=json \
  '{"grant":{"cookie":"apb_demo","contains":"yes","events":[]},"reject":{"cookie":"apb_demo","contains":"no","events":[]}}'
```

3. Turn on Debug Mode. Load a page, run `document.cookie = 'apb_demo=yes'` in the console, and
   wait up to half a second. The console logs `Consent granted via the recorded cookie trigger`.
4. Run `document.cookie = 'apb_demo=no'`. The console logs the reject line.

- [ ] **Step 6: Commit**

```bash
git add includes/class-wc-barion-pixel.php assets/js/barion-pixel-base.js
git commit -m "feat: apply a recorded consent trigger as the first consent tier"
```

---

### Task 7: The front-end consent recorder

**Files:**
- Create: `assets/js/barion-consent-recorder.js`
- Modify: `includes/class-wc-barion-admin.php`

**Interfaces:**
- Consumes: nothing.
- Produces:
  - Query parameter `apb_record_consent=<nonce>` on any front-end URL enqueues the recorder for an administrator.
  - `postMessage` payload to the opener: `{ source: 'apb-recorder', cookies: [{name, value}], events: [string] }`.
  - Nonce action name: `apb_record_consent`.

- [ ] **Step 1: Create `assets/js/barion-consent-recorder.js`**

```javascript
/**
 * Barion consent recorder — observes a cookie banner and reports what it changes.
 *
 * Runs only for a logged-in administrator who opened the page from the setup
 * wizard, never for a visitor. It reads cookies and event names; it never
 * captures page content, form input or personal data.
 */
(function () {
	var opener = window.opener;
	if (!opener) {
		return;
	}

	var origin = window.location.origin;
	var seenEvents = [];
	var baseline = {};

	function parseCookies() {
		var out = {};
		var parts = document.cookie ? document.cookie.split(';') : [];
		for (var i = 0; i < parts.length; i++) {
			var pair = parts[i].split('=');
			var name = pair.shift().trim();
			if (name) {
				out[name] = pair.join('=');
			}
		}
		return out;
	}

	baseline = parseCookies();

	// Wrap dispatchEvent before the banner loads, so its custom events are seen.
	function wrap(target) {
		var original = target.dispatchEvent;
		target.dispatchEvent = function (event) {
			if (event && event.type && seenEvents.indexOf(event.type) === -1) {
				seenEvents.push(event.type);
			}
			return original.apply(this, arguments);
		};
	}
	wrap(document);
	wrap(window);

	function report() {
		var now = parseCookies();
		var changed = [];
		for (var name in now) {
			if (!Object.prototype.hasOwnProperty.call(now, name)) {
				continue;
			}
			if (baseline[name] !== now[name]) {
				changed.push({ name: name, value: now[name] });
			}
		}

		opener.postMessage(
			{
				source: 'apb-recorder',
				cookies: changed,
				events: seenEvents.slice(0, 40),
			},
			origin
		);
	}

	var polls = 0;
	var timer = setInterval(function () {
		polls++;
		report();
		if (polls >= 480) {
			clearInterval(timer);
		}
	}, 250);

	report();

	var banner = document.createElement('div');
	banner.setAttribute('style',
		'position:fixed;z-index:2147483647;left:0;right:0;top:0;padding:10px 14px;' +
		'background:#2271b1;color:#fff;font:14px -apple-system,system-ui,sans-serif;text-align:center');
	banner.textContent = 'Barion Pixel is recording your cookie banner. Make your choice in the banner, then return to the settings tab.';
	document.addEventListener('DOMContentLoaded', function () {
		document.body.appendChild(banner);
	});
})();
```

The recorder polls for two minutes (480 × 250 ms) and then stops.

- [ ] **Step 2: Enqueue the recorder, gated by capability and nonce**

Add to the `WC_Barion_Admin` constructor:

```php
        add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_recorder'), 1);
```

Add this method to `WC_Barion_Admin`:

```php
    /**
     * Load the consent recorder on the front end, for an administrator who came
     * from the setup wizard. It never loads for a visitor.
     *
     * @return void
     */
    public function maybe_enqueue_recorder() {
        if (!isset($_GET['apb_record_consent'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_GET['apb_record_consent']));
        if (!wp_verify_nonce($nonce, 'apb_record_consent')) {
            return;
        }

        wp_enqueue_script(
            'wc-barion-consent-recorder',
            WC_BARION_PIXEL_URL . 'assets/js/barion-consent-recorder.js',
            array(),
            WC_BARION_PIXEL_VERSION,
            false
        );
    }
```

`WC_Barion_Admin` is instantiated inside `is_admin()` today, and `wp_enqueue_scripts` is a front-end
hook, so change the bootstrap to always instantiate it:

```php
function wc_barion_pixel_init() {
    WC_Barion_Pixel::get_instance();
    WC_Barion_Admin::get_instance();
}
```

`add_admin_menu` and `register_settings` are on admin-only hooks, so they stay dormant on the
front end.

- [ ] **Step 3: Verify the gate by hand**

1. Log out. Open `https://example.test/?apb_record_consent=anything`. View source and confirm
   `barion-consent-recorder.js` is absent.
2. Log in as an administrator and repeat with an invalid nonce. Confirm it is still absent.
3. Task 8 covers the valid-nonce path, because the wizard mints the nonce.

- [ ] **Step 4: Commit**

```bash
git add assets/js/barion-consent-recorder.js includes/class-wc-barion-admin.php advanced-pixel-for-barion.php
git commit -m "feat: add the nonce-gated front-end consent recorder"
```

---

### Task 8: The wizard, the browser probe and the save endpoint

**Files:**
- Modify: `includes/class-wc-barion-admin.php`
- Create: `assets/js/barion-admin.js`

**Interfaces:**
- Consumes: `WC_Barion_Admin::render_health_panel()`, the recorder `postMessage` payload, `WC_Barion_Pixel::sanitize_trigger()`.
- Produces:
  - `wp_ajax_apb_save_trigger` — saves the trigger pair into `wc_barion_pixel_settings['consent_trigger']`.
  - `wp_ajax_apb_save_probe` — saves browser results into the `wc_barion_pixel_probe` option, under `consent` or `reachability`.
  - `wcBarionAdmin` localized object: `{ ajaxUrl, nonce, recordUrl, strings }`.

- [ ] **Step 1: Add the two AJAX handlers**

Add to the `WC_Barion_Admin` constructor:

```php
        add_action('wp_ajax_apb_save_trigger', array($this, 'ajax_save_trigger'));
        add_action('wp_ajax_apb_save_probe', array($this, 'ajax_save_probe'));
```

Add these methods:

```php
    /**
     * Save the recorded consent trigger pair.
     *
     * @return void
     */
    public function ajax_save_trigger() {
        check_ajax_referer('apb_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'advanced-pixel-for-barion')), 403);
        }

        $raw = isset($_POST['trigger']) ? wp_unslash($_POST['trigger']) : '';
        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            wp_send_json_error(array('message' => __('The trigger could not be read.', 'advanced-pixel-for-barion')), 400);
        }

        $grant  = isset($decoded['grant']) ? WC_Barion_Pixel::sanitize_trigger($decoded['grant']) : null;
        $reject = isset($decoded['reject']) ? WC_Barion_Pixel::sanitize_trigger($decoded['reject']) : null;

        // Barion requires both signals, so refuse to store half a trigger.
        if (null === $grant || null === $reject) {
            wp_send_json_error(array(
                'message' => __('Record both the accept signal and the reject signal before saving.', 'advanced-pixel-for-barion'),
            ), 400);
        }

        $options = get_option('wc_barion_pixel_settings', array());
        $options['consent_trigger'] = array(
            'grant'       => $grant,
            'reject'      => $reject,
            'recorded_at' => gmdate('c'),
        );
        update_option('wc_barion_pixel_settings', $options);

        wp_send_json_success(array('message' => __('Consent setup saved.', 'advanced-pixel-for-barion')));
    }

    /**
     * Save a browser check result.
     *
     * @return void
     */
    public function ajax_save_probe() {
        check_ajax_referer('apb_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'advanced-pixel-for-barion')), 403);
        }

        $kind = isset($_POST['kind']) ? sanitize_key(wp_unslash($_POST['kind'])) : '';
        $probe = get_option('wc_barion_pixel_probe', array());

        if ('consent' === $kind) {
            $probe['consent'] = array(
                'consent_type' => isset($_POST['consent_type']) ? sanitize_text_field(wp_unslash($_POST['consent_type'])) : '',
                'has_consent'  => isset($_POST['has_consent']) && 'true' === $_POST['has_consent'],
            );
        } elseif ('reachability' === $kind) {
            $probe['reachability'] = array(
                'ok' => isset($_POST['ok']) && 'true' === $_POST['ok'],
            );
        } else {
            wp_send_json_error(array('message' => __('Unknown check.', 'advanced-pixel-for-barion')), 400);
        }

        update_option('wc_barion_pixel_probe', $probe, false);
        wp_send_json_success();
    }
```

Add the probe option to the uninstall cleanup. `uninstall.php` currently removes
`wc_barion_pixel_settings`; add `delete_option('wc_barion_pixel_probe');` beside it.

- [ ] **Step 2: Render the wizard dialog**

Add this method to `WC_Barion_Admin`, and call `$this->render_wizard();` from
`render_settings_page()` directly after `$this->render_health_panel();`:

```php
    /**
     * Render the consent setup wizard dialog.
     *
     * @return void
     */
    public function render_wizard() {
        $facts = WC_Barion_Health::gather_facts($this->options);
        $api_usable = $facts['consent_api_active'] && '' !== $facts['consent_type'];
        ?>
        <dialog class="apb-dialog" id="apb-wizard">
            <div class="apb-dialog-head">
                <h2><?php esc_html_e('Set up consent', 'advanced-pixel-for-barion'); ?></h2>
                <button type="button" class="button-link" data-apb-close>&times;</button>
            </div>

            <div class="apb-dialog-body">
                <div class="apb-step is-active" data-step="1">
                    <p><?php esc_html_e('Barion may receive marketing data only after the visitor agrees. The plugin needs a reliable signal for accept and for reject.', 'advanced-pixel-for-barion'); ?></p>

                    <label class="apb-choice is-selected" data-choice="learn">
                        <input type="radio" name="apb_source" value="learn" checked>
                        <span>
                            <strong><?php esc_html_e('Teach the plugin my banner', 'advanced-pixel-for-barion'); ?></strong>
                            <div class="apb-desc"><?php esc_html_e('You open your shop, accept, then reject. The plugin records what changes. This works with any cookie banner.', 'advanced-pixel-for-barion'); ?></div>
                        </span>
                    </label>

                    <label class="apb-choice<?php echo $api_usable ? '' : ' is-disabled'; ?>" data-choice="api">
                        <input type="radio" name="apb_source" value="api" <?php disabled(!$api_usable); ?>>
                        <span>
                            <strong><?php esc_html_e('Use the WP Consent API', 'advanced-pixel-for-barion'); ?></strong>
                            <div class="apb-desc">
                                <?php
                                echo $api_usable
                                    ? esc_html__('A cookie banner plugin sets a consent type. Nothing more is needed.', 'advanced-pixel-for-barion')
                                    : esc_html__('Not usable: no cookie banner plugin sets a consent type.', 'advanced-pixel-for-barion');
                                ?>
                            </div>
                        </span>
                    </label>

                    <label class="apb-choice" data-choice="none">
                        <input type="radio" name="apb_source" value="none">
                        <span>
                            <strong><?php esc_html_e('Do nothing', 'advanced-pixel-for-barion'); ?></strong>
                            <div class="apb-desc"><?php esc_html_e('Consent is never granted. Full tracking collects no marketing data.', 'advanced-pixel-for-barion'); ?></div>
                        </span>
                    </label>
                </div>

                <div class="apb-step" data-step="2">
                    <p data-apb-record-intro></p>
                    <div class="apb-recorder" id="apb-recorder-log"></div>
                    <p class="apb-desc"><?php esc_html_e('The recorder watches cookie changes and event names only. It runs for you alone and never for your visitors.', 'advanced-pixel-for-barion'); ?></p>
                </div>

                <div class="apb-step" data-step="3">
                    <p><?php esc_html_e('Grant consent to Barion when this is true:', 'advanced-pixel-for-barion'); ?></p>
                    <p>
                        <label><?php esc_html_e('Cookie name', 'advanced-pixel-for-barion'); ?><br>
                        <input type="text" class="regular-text" id="apb-grant-cookie"></label>
                    </p>
                    <p>
                        <label><?php esc_html_e('Accepted value contains', 'advanced-pixel-for-barion'); ?><br>
                        <input type="text" class="regular-text" id="apb-grant-contains"></label>
                    </p>
                    <p>
                        <label><?php esc_html_e('Rejected value contains', 'advanced-pixel-for-barion'); ?><br>
                        <input type="text" class="regular-text" id="apb-reject-contains"></label>
                    </p>
                    <div id="apb-wizard-notice"></div>
                </div>
            </div>

            <div class="apb-dialog-foot">
                <span class="apb-steps">
                    <span class="is-on" data-dot="1">1</span><span data-dot="2">2</span><span data-dot="3">3</span>
                </span>
                <span>
                    <button type="button" class="button" data-apb-back><?php esc_html_e('Back', 'advanced-pixel-for-barion'); ?></button>
                    <button type="button" class="button button-primary" data-apb-next><?php esc_html_e('Next', 'advanced-pixel-for-barion'); ?></button>
                </span>
            </div>
        </dialog>
        <?php
    }
```

- [ ] **Step 3: Localize the admin script**

Extend `enqueue_admin_assets()` with:

```php
        wp_enqueue_script(
            'wc-barion-admin',
            WC_BARION_PIXEL_URL . 'assets/js/barion-admin.js',
            array(),
            WC_BARION_PIXEL_VERSION,
            true
        );

        wp_localize_script('wc-barion-admin', 'wcBarionAdmin', array(
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('apb_admin'),
            'recordUrl' => add_query_arg(
                'apb_record_consent',
                wp_create_nonce('apb_record_consent'),
                home_url('/')
            ),
            'strings'   => array(
                'recordAccept' => __('Your shop opens in a new tab. Accept cookies in your banner there, then return here.', 'advanced-pixel-for-barion'),
                'recordReject' => __('Now reject cookies in that tab. Clear your cookies first if the banner does not appear again.', 'advanced-pixel-for-barion'),
                'waiting'      => __('Recording. Waiting for the banner.', 'advanced-pixel-for-barion'),
                'openShop'     => __('Open my shop', 'advanced-pixel-for-barion'),
                'next'         => __('Next', 'advanced-pixel-for-barion'),
                'save'         => __('Save', 'advanced-pixel-for-barion'),
                'saved'        => __('Consent setup saved. Reloading.', 'advanced-pixel-for-barion'),
                'needBoth'     => __('Record both accept and reject before saving.', 'advanced-pixel-for-barion'),
                'noChange'     => __('No cookie changed. Make a choice in the banner on the other tab.', 'advanced-pixel-for-barion'),
                'testing'      => __('Testing…', 'advanced-pixel-for-barion'),
            ),
        ));
```

- [ ] **Step 4: Create `assets/js/barion-admin.js`**

```javascript
/**
 * Admin behaviour for the Barion Pixel health panel and consent wizard.
 */
(function () {
	var cfg = window.wcBarionAdmin || {};
	var panel = document.getElementById('apb-panel');
	var dialog = document.getElementById('apb-wizard');
	var toggle = document.getElementById('apb-toggle');

	var state = { step: 1, side: 'grant', recorded: { grant: null, reject: null }, tab: null };

	if (toggle && panel) {
		toggle.addEventListener('click', function () {
			panel.classList.toggle('is-collapsed');
		});
	}

	function post(body, done) {
		var params = new URLSearchParams(body);
		params.set('nonce', cfg.nonce);
		fetch(cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: params.toString(),
		})
			.then(function (r) { return r.json(); })
			.then(done)
			.catch(function () { done({ success: false }); });
	}

	// --- Panel actions ---
	document.addEventListener('click', function (event) {
		var button = event.target.closest('[data-apb-action]');
		if (!button) {
			return;
		}
		var action = button.getAttribute('data-apb-action');
		if ('wizard' === action) {
			openWizard();
		} else if ('probe' === action) {
			runConsentProbe(button);
		} else if ('reachability' === action) {
			runReachability(button);
		}
	});

	function openWizard() {
		state.step = 1;
		state.side = 'grant';
		state.recorded = { grant: null, reject: null };
		showStep(1);
		dialog.showModal();
	}

	if (dialog) {
		dialog.addEventListener('click', function (event) {
			if (event.target.closest('[data-apb-close]')) {
				dialog.close();
			}
			var choice = event.target.closest('.apb-choice');
			if (choice && !choice.classList.contains('is-disabled')) {
				dialog.querySelectorAll('.apb-choice').forEach(function (el) {
					el.classList.remove('is-selected');
				});
				choice.classList.add('is-selected');
			}
		});

		dialog.querySelector('[data-apb-back]').addEventListener('click', function () {
			showStep(Math.max(1, state.step - 1));
		});

		dialog.querySelector('[data-apb-next]').addEventListener('click', onNext);
	}

	function showStep(step) {
		state.step = step;
		dialog.querySelectorAll('.apb-step').forEach(function (el) {
			el.classList.toggle('is-active', el.getAttribute('data-step') === String(step));
		});
		dialog.querySelectorAll('[data-dot]').forEach(function (el) {
			el.classList.toggle('is-on', el.getAttribute('data-dot') === String(step));
		});
		var next = dialog.querySelector('[data-apb-next]');
		if (2 === step) {
			next.textContent = cfg.strings.openShop;
			dialog.querySelector('[data-apb-record-intro]').textContent =
				'grant' === state.side ? cfg.strings.recordAccept : cfg.strings.recordReject;
			document.getElementById('apb-recorder-log').textContent = cfg.strings.waiting;
		} else if (3 === step) {
			next.textContent = cfg.strings.save;
		} else {
			next.textContent = cfg.strings.next;
		}
	}

	function onNext() {
		if (1 === state.step) {
			var selected = dialog.querySelector('.apb-choice.is-selected');
			var value = selected ? selected.getAttribute('data-choice') : 'learn';
			if ('learn' !== value) {
				dialog.close();
				return;
			}
			state.side = 'grant';
			showStep(2);
			return;
		}

		if (2 === state.step) {
			state.tab = window.open(cfg.recordUrl, 'apb-recorder');
			return;
		}

		saveTrigger();
	}

	// --- Recorder messages ---
	window.addEventListener('message', function (event) {
		if (event.origin !== window.location.origin) {
			return;
		}
		var data = event.data;
		if (!data || 'apb-recorder' !== data.source) {
			return;
		}

		var log = document.getElementById('apb-recorder-log');
		if (!data.cookies.length) {
			log.textContent = cfg.strings.noChange;
			return;
		}

		var lines = data.cookies.map(function (c) {
			return 'cookie ' + c.name + ' = ' + c.value;
		});
		if (data.events.length) {
			lines.push('events ' + data.events.join(', '));
		}
		log.textContent = lines.join('\n');

		// The longest changed value is the most specific candidate.
		var best = data.cookies.slice().sort(function (a, b) {
			return b.value.length - a.value.length;
		})[0];

		state.recorded[state.side] = {
			cookie: best.name,
			contains: decodeURIComponent(best.value),
			events: data.events.slice(0, 5),
		};

		if ('grant' === state.side) {
			state.side = 'reject';
			showStep(2);
		} else {
			fillStep3();
			showStep(3);
		}
	});

	function fillStep3() {
		document.getElementById('apb-grant-cookie').value = state.recorded.grant.cookie;
		document.getElementById('apb-grant-contains').value = state.recorded.grant.contains;
		document.getElementById('apb-reject-contains').value = state.recorded.reject.contains;
	}

	function saveTrigger() {
		var cookie = document.getElementById('apb-grant-cookie').value;
		var notice = document.getElementById('apb-wizard-notice');
		var payload = {
			grant: {
				cookie: cookie,
				contains: document.getElementById('apb-grant-contains').value,
				events: state.recorded.grant ? state.recorded.grant.events : [],
			},
			reject: {
				cookie: cookie,
				contains: document.getElementById('apb-reject-contains').value,
				events: state.recorded.reject ? state.recorded.reject.events : [],
			},
		};

		if (!payload.grant.contains || !payload.reject.contains) {
			notice.textContent = cfg.strings.needBoth;
			return;
		}

		post({ action: 'apb_save_trigger', trigger: JSON.stringify(payload) }, function (res) {
			if (res && res.success) {
				notice.textContent = cfg.strings.saved;
				window.location.reload();
			} else {
				notice.textContent = res && res.data ? res.data.message : cfg.strings.needBoth;
			}
		});
	}

	// --- Browser checks ---
	function runConsentProbe(button) {
		button.disabled = true;
		button.textContent = cfg.strings.testing;

		var frame = document.createElement('iframe');
		frame.style.display = 'none';
		frame.src = cfg.recordUrl;
		frame.addEventListener('load', function () {
			var win = frame.contentWindow;
			var type = '';
			var granted = false;
			try {
				type = 'function' === typeof win.wp_get_consent_type ? String(win.wp_get_consent_type() || '') : '';
				granted = 'function' === typeof win.wp_has_consent ? !!win.wp_has_consent('marketing') : false;
			} catch (e) {
				type = '';
			}
			post({
				action: 'apb_save_probe',
				kind: 'consent',
				consent_type: type,
				has_consent: granted ? 'true' : 'false',
			}, function () {
				frame.remove();
				window.location.reload();
			});
		});
		document.body.appendChild(frame);
	}

	function runReachability(button) {
		button.disabled = true;
		button.textContent = cfg.strings.testing;

		var script = document.createElement('script');
		var settled = false;

		function finish(ok) {
			if (settled) {
				return;
			}
			settled = true;
			script.remove();
			post({ action: 'apb_save_probe', kind: 'reachability', ok: ok ? 'true' : 'false' }, function () {
				window.location.reload();
			});
		}

		script.src = 'https://pixel.barion.com/bp.js';
		script.addEventListener('load', function () { finish(true); });
		script.addEventListener('error', function () { finish(false); });
		setTimeout(function () { finish(false); }, 8000);
		document.body.appendChild(script);
	}
})();
```

The consent probe loads the front end in a hidden same-origin iframe, so it can read
`wp_get_consent_type()` and `wp_has_consent()` exactly as a visitor's browser sees them. It reuses
`recordUrl` because that URL is already nonce-gated, and it reads before any interaction.

- [ ] **Step 5: Verify the wizard end to end**

1. Deactivate every cookie banner plugin, keep WP Consent API active. The panel shows the amber
   "No cookie banner plugin sets a consent type" row.
2. Press **Check in browser**. The page reloads and the row turns red with "Every visitor is
   counted as consenting".
3. Activate a banner plugin such as CookieYes. Press **Set up consent**, keep "Teach the plugin my
   banner", press Next, then **Open my shop**.
4. Accept in the banner on the new tab. Return to the settings tab. The log shows the changed
   cookie, and the wizard asks for reject.
5. Clear your cookies on that tab, reload it, and reject. The wizard moves to step 3 with the
   fields filled.
6. Press Save. The page reloads. The panel shows "Consent comes from your cookie banner, recorded
   by the wizard".
7. Load the front end with Debug Mode on. Accept in the banner. The console logs
   `Consent granted via the recorded cookie trigger`.
8. Press **Test** on the reachability row. It turns green.

- [ ] **Step 6: Run both test suites**

Run: `php tests/health-test.php && node --test tests/trigger-test.mjs`
Expected: both PASS.

- [ ] **Step 7: Commit**

```bash
git add includes/class-wc-barion-admin.php assets/js/barion-admin.js uninstall.php
git commit -m "feat: add the consent setup wizard and the browser checks"
```

---

### Task 9: Resolve the cookie declaration open item

The spec leaves one question open: `wp_add_cookie_info()` declares cookies that the plugin itself
sets, and `bp.js` sets Barion's cookies from `pixel.barion.com`. Settle it, then implement one of
two outcomes.

**Files:**
- Modify: `includes/class-wc-barion-health.php`
- Modify: `includes/class-wc-barion-pixel.php` (outcome A only)

**Interfaces:**
- Consumes: `WC_Barion_Health::evaluate()`.
- Produces: a `cookies_declared` check row, in one of the two shapes below.

- [ ] **Step 1: Establish the facts**

1. Read the `wp_add_cookie_info()` docblock in the installed WP Consent API plugin, at
   `wp-content/plugins/wp-consent-api/inc/api-functions.php`.
2. Load a front-end page with the Barion pixel active. In the browser, list the cookies set on
   your own domain and note which ones `bp.js` creates.
3. Decide: if `bp.js` sets first-party cookies on the shop's own domain, take outcome A. If every
   Barion cookie belongs to `pixel.barion.com`, take outcome B.

- [ ] **Step 2A: Outcome A — declare the cookies**

Add to the `WC_Barion_Pixel` constructor, inside the `if (!empty($this->options['pixel_id']))` block:

```php
            add_action('wp_loaded', array($this, 'declare_cookies'));
```

Add this method, with one `wp_add_cookie_info()` call per cookie found in Step 1:

```php
    /**
     * Declare the Barion cookies to the WP Consent API, so they appear in the
     * site's cookie policy.
     *
     * @return void
     */
    public function declare_cookies() {
        if (!function_exists('wp_add_cookie_info')) {
            return;
        }

        wp_add_cookie_info(
            'COOKIE_NAME_FROM_STEP_1',
            'Barion Pixel',
            'marketing',
            __('2 years', 'advanced-pixel-for-barion'),
            __('Identifies the visitor for Barion marketing analytics.', 'advanced-pixel-for-barion')
        );
    }
```

Then add this row to `WC_Barion_Health::evaluate()`, directly above `check_category()`:

```php
        $checks[] = self::row(
            'cookies_declared',
            'ok',
            __('Barion cookies are declared', 'advanced-pixel-for-barion'),
            __('They appear in your cookie policy through the WP Consent API.', 'advanced-pixel-for-barion')
        );
```

- [ ] **Step 2B: Outcome B — inform instead**

Add this row to `WC_Barion_Health::evaluate()`, directly above `check_category()`:

```php
        $checks[] = self::row(
            'cookies_declared',
            'info',
            __('Barion sets its own cookies', 'advanced-pixel-for-barion'),
            __('Barion places them from pixel.barion.com, so this plugin cannot declare them for you. Add them to your cookie policy by hand.', 'advanced-pixel-for-barion'),
            array(
                'type'   => 'link',
                'label'  => __('Barion cookie notice', 'advanced-pixel-for-barion'),
                'target' => 'https://www.barion.com/en/cookie-notice/',
            )
        );
```

The panel renders `action.type === 'link'` as a button today. Change the action markup in
`render_health_panel()` to emit an anchor for that type:

```php
                        <?php if (null !== $check['action']) : ?>
                            <span class="apb-action">
                                <?php if ('link' === $check['action']['type']) : ?>
                                    <a class="button" href="<?php echo esc_url($check['action']['target']); ?>"
                                       target="_blank" rel="noopener noreferrer">
                                        <?php echo esc_html($check['action']['label']); ?>
                                    </a>
                                <?php else : ?>
                                    <button type="button"
                                            class="button<?php echo ('fail' === $check['status']) ? ' button-primary' : ''; ?>"
                                            data-apb-action="<?php echo esc_attr($check['action']['target']); ?>">
                                        <?php echo esc_html($check['action']['label']); ?>
                                    </button>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
```

- [ ] **Step 3: Extend the health test for the chosen outcome**

Append to `tests/health-test.php`, before the final summary block:

```php
echo "The cookie declaration row is always present\n";
$checks = WC_Barion_Health::evaluate(apb_facts());
apb_assert(null !== apb_check($checks, 'cookies_declared'), 'cookies_declared row exists');
```

- [ ] **Step 4: Run the tests**

Run: `php tests/health-test.php`
Expected: PASS.

- [ ] **Step 5: Record the decision in the spec**

Replace the "Open item" section of
`docs/superpowers/specs/2026-08-08-consent-health-panel-design.md` with the outcome you chose and
the evidence from Step 1.

- [ ] **Step 6: Commit**

```bash
git add includes/ tests/ docs/superpowers/specs/
git commit -m "feat: settle the Barion cookie declaration question"
```

---

### Task 10: Documentation and translations

**Files:**
- Modify: `docs/cookie-consent.md`
- Modify: `docs/testing-notes.md`
- Modify: `README.md`
- Modify: `readme.txt`
- Modify: `docs/i18n/{cs,de,hr,hu,ro,sk,sl,sr}/cookie-consent.md`
- Modify: `docs/i18n/{cs,de,hr,hu,ro,sk,sl,sr}/testing-notes.md`
- Modify: `languages/advanced-pixel-for-barion.pot` (generated)

**Interfaces:**
- Consumes: everything built in Tasks 1 to 9.
- Produces: no code.

- [ ] **Step 1: Rewrite the tier list in `docs/cookie-consent.md`**

The plugin now has four tiers, not three. Replace the list at lines 11 to 16 with:

```markdown
The plugin supports four tiers of consent integration, checked in order:

1. **Recorded trigger** — a cookie signal captured by the setup wizard; it wins when present,
   because the shop owner set it deliberately
2. **WP Consent API** (recommended) — universal, works with all major cookie plugins
3. **Cookie Law Info** (fallback) — direct integration for sites using CookieYes/Cookie Law Info
4. **Manual** — for custom consent managers or edge cases
```

- [ ] **Step 2: Add two sections to `docs/cookie-consent.md`**

Add after the Tier 1 section:

```markdown
## The health panel

Settings › Barion Pixel opens with a health panel. It runs every check below and shows the worst
result first. When everything passes, it collapses to one green line.

The most important check is **"No cookie banner plugin sets a consent type"**. The WP Consent API
reports consent for every category when nothing sets a consent type:

> If there's no consent management plugin to set it, it will return `false`. This will cause all
> consent categories to return `true`.

A site with the WP Consent API active but no cookie banner therefore grants Barion consent for
every visitor, with no consent collected. That breaks the GDPR and the Barion terms.

Some banners set the consent type in the browser only, so the panel first reports a warning and
offers a **Check in browser** button. That check reads the real values from your front end before
any interaction, and turns the row red or green accordingly.

## The setup wizard

If no consent source works, the panel offers **Set up consent**. The wizard opens your shop in a
new tab, you accept in your own banner, and the plugin records which cookie changed. You repeat
for reject. Barion requires both `grantConsent` and `rejectConsent`, so the wizard refuses to save
until it has both.

The wizard stores a cookie name, the accepted and rejected values, and up to five event names. It
never stores or runs JavaScript that you supply. The recorder loads only for a logged-in
administrator who arrives with a valid nonce; it never loads for a visitor.

### Why the consent category is fixed

The plugin always asks for the `marketing` category and offers no choice. The WP Consent API
defines five fixed categories, and cookie banner plugins map their own categories onto them in
code. CookieYes maps Advertisement to marketing, Analytics to statistics, Functional to
preferences, and Performance to functional. You cannot change that map.

Barion requires consent for marketing purposes, so `marketing` is the only correct category. A
selector would let you fire Barion on a statistics checkbox, which breaks the Barion terms.
```

- [ ] **Step 3: Add the manual checklist to `docs/testing-notes.md`**

```markdown
## Health panel and consent wizard

The recorder and the wizard depend on a third-party cookie banner, so they need manual checks.

1. **Silent consent.** Activate WP Consent API and deactivate every cookie banner plugin. The
   panel shows an amber "No cookie banner plugin sets a consent type" row. Press **Check in
   browser**. The row turns red.
2. **Recorder gate.** Log out and open `/?apb_record_consent=anything`. Confirm
   `barion-consent-recorder.js` is absent from the page source. Repeat as an administrator with
   an invalid nonce; it must still be absent.
3. **Record accept.** Activate a cookie banner. Press **Set up consent**, then **Open my shop**.
   Accept in the banner. The wizard log shows the changed cookie.
4. **Record reject.** Clear cookies on that tab, reload, and reject. The wizard reaches step 3
   with the fields filled.
5. **Half a trigger.** Try to save with the reject value empty. The wizard refuses.
6. **Front end.** With Debug Mode on, accept in the banner. The console logs
   `Consent granted via the recorded cookie trigger`. Reject, and it logs the reject line.
7. **Reachability.** Press **Test**. With an ad blocker on, it reports a warning.
```

- [ ] **Step 4: Update `README.md` and `readme.txt`**

Add to the feature list in both files:

```markdown
- **Health Panel**: Checks your Pixel ID, WooCommerce, and the whole consent setup in one place —
  including the silent-consent trap that grants Barion consent for every visitor
- **Consent Setup Wizard**: Records the accept and reject signals of any cookie banner by
  observation, so no code is needed
```

Add a `readme.txt` changelog entry under a new `= 1.1.0 =` heading, and raise the version in the
plugin header and in `WC_BARION_PIXEL_VERSION` to `1.1.0`.

- [ ] **Step 5: Mirror the two documents into the eight translations**

For each language directory under `docs/i18n/`, apply the same structural changes to
`cookie-consent.md` and `testing-notes.md`, translated. Keep every code sample, cookie name,
function name and option key in English.

- [ ] **Step 6: Rebuild the translation template**

Run: `composer i18n:build`
Expected: `languages/advanced-pixel-for-barion.pot` gains the new strings, and the `.mo` files
rebuild without error.

- [ ] **Step 7: Run every check one last time**

Run: `php -l advanced-pixel-for-barion.php && php -l includes/class-wc-barion-health.php && php -l includes/class-wc-barion-admin.php && php -l includes/class-wc-barion-pixel.php && php tests/health-test.php && node --test tests/trigger-test.mjs`
Expected: no syntax errors, and both suites PASS.

- [ ] **Step 8: Commit**

```bash
git add docs/ README.md readme.txt languages/ advanced-pixel-for-barion.php
git commit -m "docs: document the health panel and consent wizard in all languages"
```
