<?php
/**
 * Reproduction for the fatal error on a site without WooCommerce:
 * "Call to undefined function is_product()" from enqueue_events_script().
 *
 * Also pins the documented contract (readme.txt, docs/compatibility.md):
 * the base pixel loads without WooCommerce, event tracking does not.
 *
 * Hand-written stubs rather than a framework, because the repo has no PHP test
 * harness and one bug does not justify adding one. Move to WP-PHPUnit if this
 * outgrows the hook wiring.
 *
 * Run: php tests/no-woocommerce-fatal.php
 */

$mode = $argv[1] ?? 'run';

// Each case needs a fresh process: the plugin class is a singleton.
if ('run' === $mode) {
    $failed = false;
    foreach (array('without-wc', 'with-wc') as $case) {
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . $case, $status);
        $failed = $failed || 0 !== $status;
    }
    echo $failed ? "\nFAIL\n" : "\nPASS\n";
    exit($failed ? 1 : 0);
}

define('ABSPATH', __DIR__ . '/');

$GLOBALS['hooks'] = array();

// Priority is ignored: no check here depends on callback order.
function add_action($hook, $callback) { $GLOBALS['hooks'][$hook][] = $callback; }
function add_filter($hook, $callback) { add_action($hook, $callback); }

function has_action($hook, $callback) {
    return isset($GLOBALS['hooks'][$hook]) && in_array($callback, $GLOBALS['hooks'][$hook], true);
}

function do_action($hook) {
    foreach (isset($GLOBALS['hooks'][$hook]) ? $GLOBALS['hooks'][$hook] : array() as $callback) {
        call_user_func($callback);
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

$failed = false;

function check($label, $ok) {
    global $failed;
    echo ($ok ? '  ok   ' : '  FAIL ') . $label . "\n";
    $failed = $failed || !$ok;
}

if ('with-wc' === $mode) {
    class WooCommerce {}
}

echo "$mode:\n";

require dirname(__DIR__) . '/advanced-pixel-for-barion.php';

do_action('plugins_loaded');

$plugin = WC_Barion_Pixel::get_instance();

if ('with-wc' === $mode) {
    check('events script is hooked when WooCommerce is active', has_action('wp_footer', array($plugin, 'enqueue_events_script')));
} else {
    check('base pixel is still hooked without WooCommerce', has_action('wp_enqueue_scripts', array($plugin, 'enqueue_base_script')));
    check('events script is not hooked without WooCommerce', !has_action('wp_footer', array($plugin, 'enqueue_events_script')));

    $fatal = null;
    try {
        do_action('wp_footer');
    } catch (Throwable $e) {
        $fatal = $e->getMessage();
    }
    check('wp_footer does not fatal without WooCommerce' . ($fatal ? " ($fatal)" : ''), null === $fatal);
}

exit($failed ? 1 : 0);
