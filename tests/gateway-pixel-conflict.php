<?php
/**
 * Reproduction for the second base pixel the Barion Payment Gateway prints.
 *
 * That plugin echoes its own base pixel snippet from wp_head at priority
 * 999999 whenever its Pixel ID field is filled — whatever its own tracking
 * setting says, and even with the gateway itself switched off. The page then
 * runs bp.js twice and ends up with two iframes carrying id="barion_receiver".
 * The second copy posts into the first one before that iframe has read its
 * consent status, bp.js throws on the undefined status, and every event is
 * lost. Measured on a live shop: three throws and zero events on a product
 * page.
 *
 * The order is the reason this cannot be settled in the browser: the gateway
 * prints at priority 999999 and this plugin's head script at priority 9, so
 * the JavaScript guard never sees the other snippet. The fix is the filter
 * that plugin exposes for exactly this, so the check below is whether the
 * snippet still reaches the page.
 *
 * The snippet and its guard are copied from
 * pay-via-barion-for-woocommerce 3.10.2,
 * includes/class-wc-gateway-barion-pixel.php.
 *
 * Run: php tests/gateway-pixel-conflict.php
 */

$mode = $argv[1] ?? 'run';

// Each case needs a fresh process: the plugin class is a singleton.
if ('run' === $mode) {
    $failed = false;
    foreach (array('pixel-id-set', 'pixel-id-empty') as $case) {
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . $case, $status);
        $failed = $failed || 0 !== $status;
    }
    echo $failed ? "\nFAIL\n" : "\nPASS\n";
    exit($failed ? 1 : 0);
}

define('ABSPATH', __DIR__ . '/');

$GLOBALS['hooks'] = array();

// Priority matters here, unlike in the other PHP test: the whole bug is that
// the gateway prints after everything this plugin can put on the page.
function add_action($hook, $callback, $priority = 10) {
    $GLOBALS['hooks'][$hook][$priority][] = $callback;
}

function add_filter($hook, $callback, $priority = 10) { add_action($hook, $callback, $priority); }

function apply_filters($hook, $value) {
    if (!isset($GLOBALS['hooks'][$hook])) {
        return $value;
    }
    ksort($GLOBALS['hooks'][$hook]);
    foreach ($GLOBALS['hooks'][$hook] as $callbacks) {
        foreach ($callbacks as $callback) {
            $value = call_user_func($callback, $value);
        }
    }
    return $value;
}

function do_action($hook) {
    if (!isset($GLOBALS['hooks'][$hook])) {
        return;
    }
    ksort($GLOBALS['hooks'][$hook]);
    foreach ($GLOBALS['hooks'][$hook] as $callbacks) {
        foreach ($callbacks as $callback) {
            call_user_func($callback);
        }
    }
}

function __return_true() { return true; }

function plugin_dir_path($file) { return dirname($file) . '/'; }
function plugin_dir_url($file) { return 'https://example.test/wp-content/plugins/' . basename(dirname($file)) . '/'; }
function plugin_basename($file) { return basename(dirname($file)) . '/' . basename($file); }
function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES); }

$pixelId = 'pixel-id-set' === $mode ? 'BP-0000000000-00' : '';

function get_option($key, $default = false) {
    global $pixelId;
    if ('wc_barion_pixel_settings' === $key) {
        return array('pixel_id' => $pixelId, 'enable_full_tracking' => true, 'debug_mode' => false);
    }
    // The gateway keeps its Pixel ID even when the gateway itself is disabled
    // and its own tracking setting says no, which is the reported state.
    if ('woocommerce_barion_settings' === $key) {
        return array('enabled' => 'no', 'tracking_enabled' => 'no', 'barion_pixel_id' => 'BP-0000000000-00');
    }
    return $default;
}

// Copy of WC_Gateway_Barion_Pixel from the Barion Payment Gateway.
add_action('wp_head', function () {
    $settings = get_option('woocommerce_barion_settings');
    if (empty($settings['barion_pixel_id'])) {
        return;
    }
    if (apply_filters('woocommerce_barion_disable_tracking', false)) {
        return;
    }
    echo "<script>bp('init', 'addBarionPixelId', window['barion_pixel_id']);</script>";
}, 999999);

$failed = false;

function check($label, $ok) {
    global $failed;
    echo ($ok ? '  ok   ' : '  FAIL ') . $label . "\n";
    $failed = $failed || !$ok;
}

echo "$mode:\n";

require dirname(__DIR__) . '/advanced-pixel-for-barion.php';

do_action('plugins_loaded');

ob_start();
do_action('wp_head');
$head = ob_get_clean();

$gatewayPrinted = false !== strpos($head, 'addBarionPixelId');

if ('pixel-id-set' === $mode) {
    check('the gateway snippet is suppressed while this plugin loads the pixel', !$gatewayPrinted);
} else {
    // Without a Pixel ID this plugin loads no pixel at all, so taking the
    // gateway's away would leave the shop with no tracking whatsoever.
    check('the gateway snippet survives when this plugin has no Pixel ID', $gatewayPrinted);
}

exit($failed ? 1 : 0);
