<?php
/**
 * Plugin Name: Advanced Pixel for Barion
 * Plugin URI: https://github.com/gcsecsey/advanced-pixel-for-barion
 * Description: Barion Pixel integration for WooCommerce with full e-commerce event tracking, cookie consent support, and WP Consent API compatibility.
 * Author: Gergely Csecsey
 * Author URI: https://github.com/gcsecsey
 * Version: 1.0.0
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 7.2
 * WC requires at least: 5.0
 * WC tested up to: 9.6
 * Text Domain: advanced-pixel-for-barion
 * Requires Plugins: woocommerce
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ABPW_VERSION', '1.0.0');
define('ABPW_PATH', plugin_dir_path(__FILE__));
define('ABPW_URL', plugin_dir_url(__FILE__));

/**
 * Main Barion Pixel Plugin Class
 */
class ABPW_Plugin {

    /**
     * Plugin instance
     *
     * @var ABPW_Plugin|null
     */
    private static $instance = null;

    /**
     * Plugin options
     *
     * @var array
     */
    private $options;

    /**
     * Queued events to output via localized script data
     *
     * @var array
     */
    private $events = array();

    /**
     * Billing email for setEncryptedEmail (set during woocommerce_thankyou)
     *
     * @var string|null
     */
    private $encrypted_email = null;

    /**
     * Get plugin instance (singleton accessor)
     *
     * @return ABPW_Plugin The plugin instance
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
        // Load options
        $this->options = get_option('abpw_settings', array(
            'pixel_id' => '',
            'enable_full_tracking' => true,
            'debug_mode' => false
        ));

        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // Register as WP Consent API compatible
        add_filter('wp_consent_api_registered_' . plugin_basename(__FILE__), '__return_true');

        // Only load tracking if pixel ID is set
        if (!empty($this->options['pixel_id'])) {
            // Enqueue scripts
            add_action('wp_enqueue_scripts', array($this, 'enqueue_base_script'), 1);
            add_action('wp_footer', array($this, 'output_footer_action'), 999);
            // WooCommerce event hooks (only if full tracking is enabled)
            if ($this->is_full_tracking_enabled()) {
                add_action('woocommerce_after_single_product', array($this, 'track_content_view'));
                add_action('woocommerce_before_checkout_form', array($this, 'track_initiate_checkout'));
                add_action('woocommerce_thankyou', array($this, 'track_purchase'), 10, 1);
                add_action('woocommerce_thankyou', array($this, 'track_set_encrypted_email'), 10, 1);
                add_action('wp_footer', array($this, 'enqueue_events_script'), 998);
            }
        }
    }

    /**
     * Check if full tracking is enabled
     *
     * @return bool True if full tracking is enabled, false otherwise
     */
    private function is_full_tracking_enabled() {
        return !empty($this->options['enable_full_tracking']);
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool True if debug mode is enabled, false otherwise
     */
    private function is_debug_mode() {
        return !empty($this->options['debug_mode']);
    }

    /**
     * Add admin menu (WordPress admin_menu hook callback)
     *
     * @return void
     */
    public function add_admin_menu() {
        add_options_page(
            __('Barion Pixel Settings', 'advanced-pixel-for-barion'),
            __('Barion Pixel', 'advanced-pixel-for-barion'),
            'manage_options',
            'advanced-pixel-for-barion',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register plugin settings (WordPress admin_init hook callback)
     *
     * @return void
     */
    public function register_settings() {
        register_setting('abpw_settings_group', 'abpw_settings', array($this, 'sanitize_settings'));

        add_settings_section(
            'abpw_main_section',
            __('Barion Pixel Configuration', 'advanced-pixel-for-barion'),
            array($this, 'render_section_description'),
            'advanced-pixel-for-barion'
        );

        add_settings_field(
            'abpw_pixel_id',
            __('Pixel ID', 'advanced-pixel-for-barion'),
            array($this, 'render_pixel_id_field'),
            'advanced-pixel-for-barion',
            'abpw_main_section'
        );

        add_settings_field(
            'abpw_enable_tracking',
            __('Enable Full Pixel Tracking', 'advanced-pixel-for-barion'),
            array($this, 'render_enable_tracking_field'),
            'advanced-pixel-for-barion',
            'abpw_main_section'
        );

        add_settings_field(
            'abpw_debug_mode',
            __('Debug Mode', 'advanced-pixel-for-barion'),
            array($this, 'render_debug_mode_field'),
            'advanced-pixel-for-barion',
            'abpw_main_section'
        );
    }

    /**
     * Sanitize settings input
     *
     * @param array $input The raw settings input from the form
     * @return array The sanitized settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        if (isset($input['pixel_id'])) {
            $sanitized['pixel_id'] = sanitize_text_field($input['pixel_id']);
        }

        $sanitized['enable_full_tracking'] = isset($input['enable_full_tracking']) ? true : false;
        $sanitized['debug_mode'] = isset($input['debug_mode']) ? true : false;

        return $sanitized;
    }

    /**
     * Render settings page
     *
     * @return void
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('abpw_settings_group');
                do_settings_sections('advanced-pixel-for-barion');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render section description
     *
     * @return void
     */
    public function render_section_description() {
        echo '<p>' . esc_html__('Configure your Barion Pixel integration. The Base Pixel will be loaded on all pages when a Pixel ID is provided. Full tracking includes e-commerce events like product views, add to cart, checkout, and purchase.', 'advanced-pixel-for-barion') . '</p>';

        $barion_settings = get_option('woocommerce_barion_settings', array());
        if (!empty($barion_settings['barion_pixel_id']) && !empty($this->options['pixel_id'])) {
            echo '<p>' . esc_html__('The Barion Payment Gateway plugin also has a Pixel ID configured. Both plugins will work correctly together — the base pixel script will only be loaded once. You may remove the Pixel ID from the payment gateway settings to keep configuration in one place.', 'advanced-pixel-for-barion') . '</p>';
        }
    }

    /**
     * Render pixel ID field
     *
     * @return void
     */
    public function render_pixel_id_field() {
        $value = isset($this->options['pixel_id']) ? $this->options['pixel_id'] : '';
        ?>
        <input type="text"
               name="abpw_settings[pixel_id]"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="BP-0000000000-00"
               required>
        <p class="description"><?php esc_html_e('Enter your Barion Pixel ID (e.g., BP-0000000000-00)', 'advanced-pixel-for-barion'); ?></p>
        <?php
    }

    /**
     * Render enable tracking field
     *
     * @return void
     */
    public function render_enable_tracking_field() {
        $value = !empty($this->options['enable_full_tracking']);
        ?>
        <label>
            <input type="checkbox"
                   name="abpw_settings[enable_full_tracking]"
                   value="1"
                   <?php checked($value, true); ?>>
            <?php esc_html_e('Enable full Barion Pixel event tracking (contentView, addToCart, initiateCheckout, purchase)', 'advanced-pixel-for-barion'); ?>
        </label>
        <p class="description"><?php esc_html_e('Base Pixel script will always be loaded. Enable this to track e-commerce events.', 'advanced-pixel-for-barion'); ?></p>
        <?php
    }

    /**
     * Render debug mode field
     *
     * @return void
     */
    public function render_debug_mode_field() {
        $value = !empty($this->options['debug_mode']);
        ?>
        <label>
            <input type="checkbox"
                   name="abpw_settings[debug_mode]"
                   value="1"
                   <?php checked($value, true); ?>>
            <?php esc_html_e('Enable debug mode (logs events to browser console)', 'advanced-pixel-for-barion'); ?>
        </label>
        <?php
    }

    /**
     * Enqueue base pixel script (WordPress wp_enqueue_scripts hook callback)
     *
     * @return void
     */
    public function enqueue_base_script() {
        wp_enqueue_script(
            'abpw-base',
            ABPW_URL . 'assets/js/barion-pixel-base.js',
            array(),
            ABPW_VERSION,
            false
        );
        wp_localize_script('abpw-base', 'abpwBase', array(
            'pixelId' => $this->options['pixel_id'],
            'debug'   => $this->is_debug_mode(),
        ));
    }

    /**
     * Fire the footer action for backwards compatibility (WordPress wp_footer hook callback)
     *
     * @return void
     */
    public function output_footer_action() {
        do_action('abpw_footer_scripts');
    }

    /**
     * Track content view on product page (WooCommerce woocommerce_after_single_product hook callback)
     * Implements minimal required fields per Barion documentation
     *
     * @return void
     */
    public function track_content_view() {
        global $product;

        if (!is_product() || !$product) {
            return;
        }

        $price = (float) $product->get_price();

        // Required fields for contentView event per Barion Pixel API reference
        // Note: totalItemPrice is documented as required but bp.js rejects it for contentView
        $content_data = array(
            'contentType' => 'Product',
            'currency' => get_woocommerce_currency(),
            'id' => (string) $product->get_id(),
            'name' => $product->get_name(),
            'quantity' => 1,
            'unit' => 'pcs',
            'unitPrice' => $price
        );

        $this->queue_event('contentView', $content_data);
    }

    /**
     * Enqueue events script with all collected data (WordPress wp_footer hook callback)
     * Called at priority 998 to run before output_footer_action at 999.
     *
     * @return void
     */
    public function enqueue_events_script() {
        // Build single product data for addToCart tracking on product pages
        $single_product = null;
        if (is_product()) {
            global $product;
            if ($product) {
                $single_product = array(
                    'id'    => (string) $product->get_id(),
                    'name'  => $product->get_name(),
                    'price' => (float) $product->get_price(),
                );
            }
        }

        // Only enqueue if there's something to do
        if (empty($this->events) && null === $single_product && null === $this->encrypted_email) {
            return;
        }

        wp_enqueue_script(
            'abpw-events',
            ABPW_URL . 'assets/js/barion-pixel-events.js',
            array('abpw-base'),
            ABPW_VERSION,
            true
        );
        wp_localize_script('abpw-events', 'abpwEvents', array(
            'currency'      => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'HUF',
            'debug'         => $this->is_debug_mode(),
            'events'        => $this->events,
            'singleProduct' => $single_product,
            'email'         => $this->encrypted_email,
        ));
    }

    /**
     * Track initiate checkout event (WooCommerce woocommerce_before_checkout_form hook callback)
     *
     * @return void
     */
    public function track_initiate_checkout() {
        if (!WC()->cart) {
            return;
        }

        $cart = WC()->cart;
        $items = array();

        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $qty = (int) $cart_item['quantity'];
            $unit_price = (float) $product->get_price();
            $items[] = array(
                'contentType' => 'Product',
                'currency' => get_woocommerce_currency(),
                'id' => (string) $product->get_id(),
                'name' => $product->get_name(),
                'quantity' => $qty,
                'unit' => 'pcs',
                'unitPrice' => $unit_price,
                'totalItemPrice' => $unit_price * $qty
            );
        }

        // Calculate revenue including tax
        // Note: Shipping is not included at this stage as it may not be calculated yet
        // The customer may still need to enter shipping details or select shipping method
        $event_data = array(
            'contents' => $items,
            'currency' => get_woocommerce_currency(),
            'revenue' => (float) ($cart->get_cart_contents_total() + $cart->get_cart_contents_tax()), // Subtotal + tax (no shipping)
            'step' => 1
        );

        $this->queue_event('initiateCheckout', $event_data);
    }

    /**
     * Track purchase event on order completion (WooCommerce woocommerce_thankyou hook callback)
     *
     * @param int $order_id The order ID
     * @return void
     */
    public function track_purchase($order_id) {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        // Check if already tracked to prevent duplicate tracking
        if ($order->get_meta('_abpw_tracked', true)) {
            return;
        }

        $items = array();

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                // Calculate actual unit price (including discounts and tax): (item total + tax) / quantity
                $total_with_tax = $item->get_total() + $item->get_total_tax();
                $unit_price = $item->get_quantity() > 0
                    ? $total_with_tax / $item->get_quantity()
                    : 0;

                $qty = (int) $item->get_quantity();
                $items[] = array(
                    'contentType' => 'Product',
                    'currency' => $order->get_currency(),
                    'id' => (string) $product->get_id(),
                    'name' => $item->get_name(),
                    'quantity' => $qty,
                    'unit' => 'pcs',
                    'unitPrice' => (float) $unit_price,
                    'totalItemPrice' => (float) ($unit_price * $qty)
                );
            }
        }

        $event_data = array(
            'contents' => $items,
            'currency' => $order->get_currency(),
            'revenue' => (float) $order->get_total(),
            'step' => 1
        );

        $this->queue_event('purchase', $event_data);

        // Mark as tracked
        $order->update_meta_data('_abpw_tracked', true);
        $order->save();
    }

    /**
     * Collect encrypted email for Barion user identification (WooCommerce woocommerce_thankyou hook callback)
     * Per Barion docs, bp.js handles SHA1 encryption when given a plain email address.
     *
     * @param int $order_id The order ID
     * @return void
     */
    public function track_set_encrypted_email($order_id) {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        $email = strtolower($order->get_billing_email());

        if (empty($email)) {
            return;
        }

        $this->encrypted_email = $email;
    }

    /**
     * Queue an event to be output via localized script data
     *
     * @param string $event_name The name of the Barion Pixel event to track
     * @param array $event_data The event data to send with the tracking call
     * @return void
     */
    private function queue_event($event_name, $event_data) {
        $this->events[] = array(
            'name' => $event_name,
            'data' => $event_data,
        );
    }

}

// Declare HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Initialize plugin
function abpw_init() {
    ABPW_Plugin::get_instance();
}
add_action('plugins_loaded', 'abpw_init');
