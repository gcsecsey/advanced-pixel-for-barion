<?php
/**
 * Plugin Name: Advanced Pixel for Barion
 * Plugin URI: https://github.com/gcsecsey/advanced-pixel-for-barion
 * Description: Barion Pixel integration for WooCommerce with full e-commerce event tracking, cookie consent support, and WP Consent API compatibility.
 * Author: Gergely Csecsey
 * Author URI: https://github.com/gcsecsey
 * Version: 1.0.3
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 7.2
 * WC requires at least: 5.0
 * WC tested up to: 9.6
 * Text Domain: advanced-pixel-for-barion
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_BARION_PIXEL_VERSION', '1.0.3');
define('WC_BARION_PIXEL_PATH', plugin_dir_path(__FILE__));
define('WC_BARION_PIXEL_URL', plugin_dir_url(__FILE__));

require_once WC_BARION_PIXEL_PATH . 'includes/class-wc-barion-pixel.php';
require_once WC_BARION_PIXEL_PATH . 'includes/class-wc-barion-health.php';
require_once WC_BARION_PIXEL_PATH . 'includes/class-wc-barion-admin.php';

// Declare HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Register as WP Consent API compatible. Must run from the main plugin file so
// plugin_basename() resolves to the plugin the API knows about.
add_filter('wp_consent_api_registered_' . plugin_basename(__FILE__), '__return_true');

// Initialize plugin
function wc_barion_pixel_init() {
    WC_Barion_Pixel::get_instance();
    if (is_admin()) {
        WC_Barion_Admin::get_instance();
    }
}
add_action('plugins_loaded', 'wc_barion_pixel_init');
