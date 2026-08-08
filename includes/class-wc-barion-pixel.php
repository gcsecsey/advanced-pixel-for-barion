<?php
/**
 * Front-end Barion Pixel tracking.
 *
 * @package Advanced_Pixel_For_Barion
 */

if (!defined('ABSPATH')) {
    exit;
}

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
                // Priority must be < 20 so wp_print_footer_scripts (priority 20) prints the enqueued script.
                add_action('wp_footer', array($this, 'enqueue_events_script'), 5);
            }
        }
    }

    /**
     * Check if full tracking is enabled
     *
     * @return bool True if full tracking is enabled, false otherwise
     */
    public function is_full_tracking_enabled() {
        return !empty($this->options['enable_full_tracking']);
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool True if debug mode is enabled, false otherwise
     */
    public function is_debug_mode() {
        return !empty($this->options['debug_mode']);
    }

    /**
     * Enqueue base pixel script (WordPress wp_enqueue_scripts hook callback)
     *
     * @return void
     */
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

    /**
     * Clean a stored consent trigger.
     *
     * Deliberately stricter than sanitize() in barion-consent-trigger.js: this
     * side guards what enters the database, so it also runs sanitize_text_field()
     * on the value. The cookie-name pattern, the event-name pattern, the
     * 256-character cap and the 5-entry cap match the JavaScript rules.
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

    /**
     * Fire the footer action for backwards compatibility (WordPress wp_footer hook callback)
     *
     * @return void
     */
    public function output_footer_action() {
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

        // Detect checkout (excluding the order-received endpoint) so JS can listen
        // for email field changes and fire setEncryptedEmail at email entry time,
        // as required by https://docs.barion.com/Barion-Pixel-API-referencia
        $is_checkout = function_exists('is_checkout') && is_checkout()
            && !(function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received'));

        // Pre-fill billing email for logged-in users so setEncryptedEmail fires immediately on checkout load
        $logged_in_email = '';
        if ($is_checkout && is_user_logged_in()) {
            $user = wp_get_current_user();
            if ($user && !empty($user->user_email)) {
                $logged_in_email = strtolower($user->user_email);
            }
        }

        // Only enqueue if there's something to do
        if (empty($this->events) && null === $single_product && null === $this->encrypted_email && !$is_checkout) {
            return;
        }

        wp_enqueue_script(
            'wc-barion-pixel-events',
            WC_BARION_PIXEL_URL . 'assets/js/barion-pixel-events.js',
            array('wc-barion-pixel-base'),
            WC_BARION_PIXEL_VERSION,
            true
        );
        wp_localize_script('wc-barion-pixel-events', 'wcBarionPixelEvents', array(
            'currency'       => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'HUF',
            'debug'          => $this->is_debug_mode(),
            'events'         => $this->events,
            'singleProduct'  => $single_product,
            'email'          => $this->encrypted_email,
            'isCheckout'     => $is_checkout,
            'loggedInEmail'  => $logged_in_email,
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
        if ($order->get_meta('_wc_barion_tracked', true)) {
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
        $order->update_meta_data('_wc_barion_tracked', true);
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

    /**
     * Get the plugin options.
     *
     * @return array The settings array.
     */
    public function get_options() {
        return $this->options;
    }

}
