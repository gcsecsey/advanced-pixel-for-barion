<?php
/**
 * Plugin Name: Barion Pixel for WooCommerce
 * Plugin URI: https://github.com/gcsecsey/woocommerce-barion-pixel
 * Description: Barion Pixel integration for WooCommerce with full e-commerce event tracking, cookie consent support, and WP Consent API compatibility.
 * Author: Gergely Csecsey
 * Author URI: https://github.com/gcsecsey
 * Version: 1.0.0
 * Requires at least: 5.0
 * Tested up to: 6.7
 * Requires PHP: 7.2
 * WC requires at least: 5.0
 * WC tested up to: 9.6
 * Text Domain: woocommerce-barion-pixel
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_BARION_PIXEL_VERSION', '1.0.0');
define('WC_BARION_PIXEL_PATH', plugin_dir_path(__FILE__));
define('WC_BARION_PIXEL_URL', plugin_dir_url(__FILE__));

/**
 * Main Barion Pixel Plugin Class
 */
class WC_Barion_Pixel {

    /**
     * Plugin instance
     *
     * @var WC_Barion_Pixel|null
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
     * @return WC_Barion_Pixel The plugin instance
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
        $this->options = get_option('wc_barion_pixel_settings', array(
            'pixel_id' => '',
            'enable_full_tracking' => true,
            'debug_mode' => false
        ));

        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'check_barion_gateway_pixel'));

        // Register as WP Consent API compatible
        add_filter('wp_consent_api_registered_' . plugin_basename(__FILE__), '__return_true');

        // Only load tracking if pixel ID is set
        if (!empty($this->options['pixel_id'])) {
            // Frontend hooks
            add_action('wp_head', array($this, 'output_base_pixel'), 1);
            add_action('wp_footer', array($this, 'output_footer_scripts'), 999);
            // WooCommerce event hooks (only if full tracking is enabled)
            if ($this->is_full_tracking_enabled()) {
                add_action('woocommerce_after_single_product', array($this, 'track_content_view'));
                add_action('wp_footer', array($this, 'output_add_to_cart_script'), 999);
                add_action('woocommerce_before_checkout_form', array($this, 'track_initiate_checkout'));
                add_action('woocommerce_thankyou', array($this, 'track_purchase'), 10, 1);
                add_action('woocommerce_thankyou', array($this, 'track_set_encrypted_email'), 10, 1);
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
            __('Barion Pixel Settings', 'woocommerce-barion-pixel'),
            __('Barion Pixel', 'woocommerce-barion-pixel'),
            'manage_options',
            'woocommerce-barion-pixel',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register plugin settings (WordPress admin_init hook callback)
     *
     * @return void
     */
    public function register_settings() {
        register_setting('wc_barion_pixel_group', 'wc_barion_pixel_settings', array($this, 'sanitize_settings'));

        add_settings_section(
            'wc_barion_pixel_main_section',
            __('Barion Pixel Configuration', 'woocommerce-barion-pixel'),
            array($this, 'render_section_description'),
            'woocommerce-barion-pixel'
        );

        add_settings_field(
            'pixel_id',
            __('Pixel ID', 'woocommerce-barion-pixel'),
            array($this, 'render_pixel_id_field'),
            'woocommerce-barion-pixel',
            'wc_barion_pixel_main_section'
        );

        add_settings_field(
            'enable_full_tracking',
            __('Enable Full Pixel Tracking', 'woocommerce-barion-pixel'),
            array($this, 'render_enable_tracking_field'),
            'woocommerce-barion-pixel',
            'wc_barion_pixel_main_section'
        );

        add_settings_field(
            'debug_mode',
            __('Debug Mode', 'woocommerce-barion-pixel'),
            array($this, 'render_debug_mode_field'),
            'woocommerce-barion-pixel',
            'wc_barion_pixel_main_section'
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
                settings_fields('wc_barion_pixel_group');
                do_settings_sections('woocommerce-barion-pixel');
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
        echo '<p>' . esc_html__('Configure your Barion Pixel integration. The Base Pixel will be loaded on all pages when a Pixel ID is provided. Full tracking includes e-commerce events like product views, add to cart, checkout, and purchase.', 'woocommerce-barion-pixel') . '</p>';
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
               name="wc_barion_pixel_settings[pixel_id]"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="BP-0000000000-00"
               required>
        <p class="description"><?php esc_html_e('Enter your Barion Pixel ID (e.g., BP-0000000000-00)', 'woocommerce-barion-pixel'); ?></p>
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
                   name="wc_barion_pixel_settings[enable_full_tracking]"
                   value="1"
                   <?php checked($value, true); ?>>
            <?php esc_html_e('Enable full Barion Pixel event tracking (contentView, addToCart, initiateCheckout, purchase)', 'woocommerce-barion-pixel'); ?>
        </label>
        <p class="description"><?php esc_html_e('Base Pixel script will always be loaded. Enable this to track e-commerce events.', 'woocommerce-barion-pixel'); ?></p>
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
                   name="wc_barion_pixel_settings[debug_mode]"
                   value="1"
                   <?php checked($value, true); ?>>
            <?php esc_html_e('Enable debug mode (logs events to browser console)', 'woocommerce-barion-pixel'); ?>
        </label>
        <?php
    }

    /**
     * Output Base Barion Pixel script in head (WordPress wp_head hook callback)
     *
     * @return void
     */
    public function output_base_pixel() {
        $pixel_id = $this->options['pixel_id'];
        ?>
<!-- Barion Pixel Base Code -->
<script>
if (typeof window.bp === 'undefined' || !window.BarionAnalyticsObject) {
    (function(b,a,r,i,o,n,p){b['BarionAnalyticsObject']=o;b[o]=b[o]||function(){
    (b[o].q=b[o].q||[]).push(arguments)};n=a.createElement(r);p=a.getElementsByTagName(r)[0];
    n.async=1;n.src=i;p.parentNode.insertBefore(n,p)})(window,document,'script',
    'https://pixel.barion.com/bp.js','bp');
    <?php if ($this->is_debug_mode()): ?>
    console.log('[Barion Pixel] bp.js loaded by Barion Pixel for WooCommerce');
    <?php endif; ?>
} <?php if ($this->is_debug_mode()): ?>else {
    console.log('[Barion Pixel] bp.js already loaded by another plugin, skipping script load');
}<?php endif; ?>

bp('init', 'addBarionPixelId', '<?php echo esc_js($pixel_id); ?>');
<?php if ($this->is_debug_mode()): ?>
console.log('[Barion Pixel] Base pixel initialized with ID: <?php echo esc_js($pixel_id); ?>');
<?php endif; ?>
</script>
<!-- End Barion Pixel Base Code -->
        <?php
    }

    /**
     * Output footer scripts for consent support (WordPress wp_footer hook callback)
     * Three-tier consent integration:
     *   Tier 1: WP Consent API (supports CookieYes, Complianz, Real Cookie Banner, etc.)
     *   Tier 2: Cookie Law Info direct integration (fallback)
     *   Tier 3: Manual integration (JS function, DOM event, WP action hook)
     *
     * @return void
     */
    public function output_footer_scripts() {
        ?>
<script>
// Public functions for consent managers to grant/reject Barion consent
window.wcBarionGrantConsent = function() {
    if (typeof bp !== 'undefined') {
        bp('consent', 'grantConsent');
        <?php if ($this->is_debug_mode()): ?>
        console.log('[Barion Pixel] Consent granted (grantConsent)');
        <?php endif; ?>
    }
};
window.wcBarionRejectConsent = function() {
    if (typeof bp !== 'undefined') {
        bp('consent', 'rejectConsent');
        <?php if ($this->is_debug_mode()): ?>
        console.log('[Barion Pixel] Consent rejected (rejectConsent)');
        <?php endif; ?>
    }
};

// Custom DOM event support
document.addEventListener('wcBarionGrantConsent', function() {
    window.wcBarionGrantConsent();
});
document.addEventListener('wcBarionRejectConsent', function() {
    window.wcBarionRejectConsent();
});

// --- Tier 1: WP Consent API integration ---
// Supports all cookie plugins implementing WP Consent API:
// CookieYes, Complianz, Real Cookie Banner, GDPR Cookie Compliance, Cookie Notice, etc.
if (typeof wp_has_consent === 'function') {
    // On initial load, only grant if the user previously accepted.
    // Don't fire rejectConsent — the user may not have interacted with the banner yet.
    if (wp_has_consent('marketing')) {
        window.wcBarionGrantConsent();
        <?php if ($this->is_debug_mode()): ?>
        console.log('[Barion Pixel] Consent auto-granted via WP Consent API');
        <?php endif; ?>
    }
    // The change event fires when the user explicitly accepts or rejects.
    document.addEventListener('wp_listen_for_consent_change', function() {
        if (wp_has_consent('marketing')) {
            window.wcBarionGrantConsent();
            <?php if ($this->is_debug_mode()): ?>
            console.log('[Barion Pixel] Consent granted via WP Consent API change event');
            <?php endif; ?>
        } else {
            window.wcBarionRejectConsent();
            <?php if ($this->is_debug_mode()): ?>
            console.log('[Barion Pixel] Consent rejected via WP Consent API change event');
            <?php endif; ?>
        }
    });
}
// --- Tier 2: Cookie Law Info / CookieYes direct integration (fallback) ---
// Note: cookielawinfo-checkbox-necessary is always 'yes' (essential cookies can't be rejected).
// For marketing consent we check cookielawinfo-checkbox-non-necessary instead.
// Detection: CLI.allowedCategories is present across CookieYes versions (CLI.ACCEPT_COOKIE_EXPIRE may not exist).
else if (typeof CLI !== 'undefined' && CLI.allowedCategories) {
    function wcBarionGetCliCookie(name) {
        var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : '';
    }
    function wcBarionCheckCliConsent() {
        if (wcBarionGetCliCookie('cookielawinfo-checkbox-non-necessary') === 'yes') {
            window.wcBarionGrantConsent();
        } else {
            window.wcBarionRejectConsent();
        }
    }
    // On initial load, only grant consent if the user previously accepted.
    // Don't fire rejectConsent yet — the user may not have interacted with the banner.
    if (wcBarionGetCliCookie('cookielawinfo-checkbox-non-necessary') === 'yes') {
        window.wcBarionGrantConsent();
    }
    <?php if ($this->is_debug_mode()): ?>
    console.log('[Barion Pixel] Cookie Law Info detected, initial non-necessary cookie:', wcBarionGetCliCookie('cookielawinfo-checkbox-non-necessary'));
    <?php endif; ?>
    // Listen for clicks on CookieYes accept/reject buttons and re-check cookies after a short delay.
    // The cli_user_preference_set event is unreliable across CookieYes versions.
    document.querySelectorAll('.cli_action_button').forEach(function(btn) {
        btn.addEventListener('click', function() {
            setTimeout(function() {
                wcBarionCheckCliConsent();
                <?php if ($this->is_debug_mode()): ?>
                console.log('[Barion Pixel] Cookie Law Info button clicked, non-necessary cookie:', wcBarionGetCliCookie('cookielawinfo-checkbox-non-necessary'));
                <?php endif; ?>
            }, 100);
        });
    });
}
// --- Tier 3: Manual integration ---
// No automatic handler — call window.wcBarionGrantConsent() / wcBarionRejectConsent()
// or dispatch wcBarionGrantConsent / wcBarionRejectConsent events
<?php if ($this->is_debug_mode()): ?>
else {
    console.log('[Barion Pixel] No consent manager detected. Call window.wcBarionGrantConsent() or window.wcBarionRejectConsent() manually.');
}
<?php endif; ?>
</script>
        <?php
        do_action('wc_barion_pixel_footer_scripts');
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

        $this->output_event('contentView', $content_data);
    }

    /**
     * Output client-side addToCart tracking script (WordPress wp_footer hook callback)
     * Uses WooCommerce product data attributes and AJAX events to track on the client side,
     * avoiding server-side session issues with page caching.
     *
     * @return void
     */
    public function output_add_to_cart_script() {
        $currency = get_woocommerce_currency();
        $debug = $this->is_debug_mode();

        // On single product pages, embed product data for the form-based add to cart
        $single_product_json = 'null';
        if (is_product()) {
            global $product;
            if ($product) {
                $single_product_json = wp_json_encode(array(
                    'id' => (string) $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => (float) $product->get_price(),
                ));
            }
        }
        ?>
<script>
(function() {
    var currency = <?php echo wp_json_encode($currency); ?>;
    var debug = <?php echo $debug ? 'true' : 'false'; ?>;
    var singleProduct = <?php echo $single_product_json; ?>;

    function fireAddToCart(data) {
        if (typeof bp === 'undefined') return;
        bp('track', 'addToCart', data);
        if (debug) console.log('[Barion Pixel] Event: addToCart', data);
    }

    // AJAX add to cart (shop/archive pages) — WooCommerce triggers 'added_to_cart' on jQuery body
    if (typeof jQuery !== 'undefined') {
        jQuery(document.body).on('added_to_cart', function(e, fragments, cartHash, $button) {
            // $button is the clicked .add_to_cart_button element with data attributes
            if (!$button || !$button.length) return;
            var id = String($button.data('product_id') || '');
            var name = $button.data('product_name') || $button.closest('.product').find('.woocommerce-loop-product__title').text() || '';
            var price = parseFloat($button.data('product_price') || 0);
            var qty = parseInt($button.data('quantity') || 1, 10);

            fireAddToCart({
                contentType: 'Product',
                currency: currency,
                id: id,
                name: name,
                quantity: qty,
                unit: 'pcs',
                unitPrice: price,
                totalItemPrice: price * qty,
                step: 1
            });
        });
    }

    // Single product page form submit — intercept before the form posts
    if (singleProduct) {
        document.addEventListener('DOMContentLoaded', function() {
            var form = document.querySelector('form.cart');
            if (!form) return;
            form.addEventListener('submit', function() {
                var qtyInput = form.querySelector('input[name="quantity"]');
                var qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;

                // Check for variation selection
                var variationInput = form.querySelector('input[name="variation_id"]');
                var variationId = variationInput ? variationInput.value : '';
                var productData = Object.assign({}, singleProduct);

                // If a variation is selected, try to get its price from WooCommerce's JS data
                if (variationId && typeof jQuery !== 'undefined') {
                    var variationsForm = jQuery(form).data('product_variations');
                    if (variationsForm) {
                        for (var i = 0; i < variationsForm.length; i++) {
                            if (String(variationsForm[i].variation_id) === String(variationId)) {
                                productData.price = parseFloat(variationsForm[i].display_price) || productData.price;
                                if (variationsForm[i].variation_description) {
                                    // Keep parent name; Barion identifies by id
                                }
                                break;
                            }
                        }
                    }
                }

                fireAddToCart({
                    contentType: 'Product',
                    currency: currency,
                    id: productData.id,
                    name: productData.name,
                    quantity: qty,
                    unit: 'pcs',
                    unitPrice: productData.price,
                    totalItemPrice: productData.price * qty,
                    step: 1
                });
            });
        });
    }
})();
</script>
        <?php
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

        $this->output_event('initiateCheckout', $event_data);
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
        if (get_post_meta($order_id, '_wc_barion_tracked', true)) {
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

        $this->output_event('purchase', $event_data);

        // Mark as tracked
        update_post_meta($order_id, '_wc_barion_tracked', true);
    }

    /**
     * Send encrypted email to Barion for user identification (WooCommerce woocommerce_thankyou hook callback)
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

        ?>
<script>
if (typeof bp !== 'undefined') {
    bp('identify', 'setEncryptedEmail', '<?php echo esc_js($email); ?>');
    <?php if ($this->is_debug_mode()): ?>
    console.log('[Barion Pixel] setEncryptedEmail sent');
    <?php endif; ?>
}
</script>
        <?php
    }

    /**
     * Check if Barion Payment Gateway also has a Pixel ID configured (WordPress admin_init hook callback)
     * Shows an informational admin notice if both plugins have pixel IDs to help avoid redundancy.
     *
     * @return void
     */
    public function check_barion_gateway_pixel() {
        $barion_settings = get_option('woocommerce_barion_settings', array());
        if (!empty($barion_settings['barion_pixel_id']) && !empty($this->options['pixel_id'])) {
            add_action('admin_notices', function () {
                ?>
                <div class="notice notice-info is-dismissible">
                    <p>
                        <?php esc_html_e(
                            'Barion Pixel for WooCommerce: The Barion Payment Gateway plugin also has a Pixel ID configured. Both plugins will work correctly together — the base pixel script will only be loaded once. You may remove the Pixel ID from the payment gateway settings to keep configuration in one place.',
                            'woocommerce-barion-pixel'
                        ); ?>
                    </p>
                </div>
                <?php
            });
        }
    }

    /**
     * Output event script (internal helper method, not part of public API)
     *
     * @param string $event_name The name of the Barion Pixel event to track
     * @param array $event_data The event data to send with the tracking call
     * @return void
     */
    private function output_event($event_name, $event_data) {
        ?>
<script>
if (typeof bp !== 'undefined') {
    bp('track', '<?php echo esc_js($event_name); ?>', <?php echo wp_json_encode($event_data); ?>);
    <?php if ($this->is_debug_mode()): ?>
    console.log('[Barion Pixel] Event: <?php echo esc_js($event_name); ?>', <?php echo wp_json_encode($event_data); ?>);
    <?php endif; ?>
}
</script>
        <?php
    }

}

// Initialize plugin
function wc_barion_pixel_init() {
    WC_Barion_Pixel::get_instance();
}
add_action('plugins_loaded', 'wc_barion_pixel_init');
