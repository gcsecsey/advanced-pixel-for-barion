<?php
/**
 * Reproduction for the fatal error on a site without WooCommerce:
 * "Call to undefined function is_product()" from enqueue_events_script().
 *
 * Also pins the documented contract (readme.txt, docs/compatibility.md):
 * the base pixel loads without WooCommerce, event tracking does not.
 *
 * Run: php tests/no-woocommerce-fatal.php
 */

$mode = isset($argv[1]) ? $argv[1] : 'run';

// Each case needs a fresh process: the plugin class is a singleton.
if ('run' === $mode) {
    $failed = 0;
    foreach (array('without-wc', 'with-wc') as $case) {
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($case), $status);
        $failed += (0 === $status) ? 0 : 1;
    }
    echo $failed ? "\nFAIL\n" : "\nPASS\n";
    exit($failed ? 1 : 0);
}

// --- Minimal WordPress stubs -------------------------------------------------

define('ABSPATH', __DIR__ . '/');

$GLOBALS['hooks'] = array();

function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
    $GLOBALS['hooks'][$hook][$priority][] = $callback;
}

function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
    add_action($hook, $callback, $priority, $accepted_args);
}

function do_action($hook) {
    if (empty($GLOBALS['hooks'][$hook])) {
        return;
    }
    $by_priority = $GLOBALS['hooks'][$hook];
    ksort($by_priority);
    foreach ($by_priority as $callbacks) {
        foreach ($callbacks as $callback) {
            call_user_func($callback);
        }
    }
}

function plugin_dir_path($file) { return dirname($file) . '/'; }
function plugin_dir_url($file) { return 'https://example.test/wp-content/plugins/' . basename(dirname($file)) . '/'; }
function plugin_basename($file) { return basename(dirname($file)) . '/' . basename($file); }

function get_option($key, $default = false) {
    if ('wc_barion_pixel_settings' === $key) {
        return array('pixel_id' => 'BP-0000000000-00', 'enable_full_tracking' => true, 'debug_mode' => false);
    }
    return $default;
}

// --- Helpers -----------------------------------------------------------------

function hook_has_method($hook, $method) {
    if (empty($GLOBALS['hooks'][$hook])) {
        return false;
    }
    foreach ($GLOBALS['hooks'][$hook] as $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback) && $method === $callback[1]) {
                return true;
            }
        }
    }
    return false;
}

$failures = array();

function check($label, $condition) {
    global $failures;
    if ($condition) {
        echo "  ok   $label\n";
        return;
    }
    echo "  FAIL $label\n";
    $failures[] = $label;
}

// --- Cases -------------------------------------------------------------------

if ('with-wc' === $mode) {
    class WooCommerce {}
}

echo "$mode:\n";

require dirname(__DIR__) . '/advanced-pixel-for-barion.php';

do_action('plugins_loaded');

if ('with-wc' === $mode) {
    check('events script is hooked when WooCommerce is active', hook_has_method('wp_footer', 'enqueue_events_script'));
} else {
    check('base pixel is still hooked without WooCommerce', hook_has_method('wp_enqueue_scripts', 'enqueue_base_script'));
    check('events script is not hooked without WooCommerce', !hook_has_method('wp_footer', 'enqueue_events_script'));

    $fatal = null;
    try {
        do_action('wp_footer');
    } catch (Throwable $e) {
        $fatal = $e->getMessage();
    }
    check('wp_footer does not fatal without WooCommerce' . ($fatal ? " ($fatal)" : ''), null === $fatal);
}

exit($failures ? 1 : 0);
