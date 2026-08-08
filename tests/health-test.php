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

echo "The cookie declaration row reflects whether the consent API is present\n";
$checks = WC_Barion_Health::evaluate(apb_facts());
apb_assert('ok' === apb_check($checks, 'cookies_declared')['status'], 'declared when the consent API is active');
$checks = WC_Barion_Health::evaluate(apb_facts(array('consent_api_active' => false, 'consent_type' => '')));
apb_assert('info' === apb_check($checks, 'cookies_declared')['status'], 'not declared without the consent API');

echo "\n";
if ($failures > 0) {
    echo "$failures check(s) failed\n";
    exit(1);
}
echo "All checks passed\n";
exit(0);
