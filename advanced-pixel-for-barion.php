<?php
/**
 * Plugin Name: Advanced Pixel for Barion
 * Plugin URI: https://github.com/gcsecsey/advanced-pixel-for-barion
 * Description: Barion Pixel integration for WooCommerce with full e-commerce event tracking, cookie consent support, and WP Consent API compatibility.
 * Author: Gergely Csecsey
 * Author URI: https://github.com/gcsecsey
 * Version: 1.0.8
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 11.0
 * Text Domain: advanced-pixel-for-barion
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Advanced_Pixel_For_Barion
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'WC_BARION_PIXEL_VERSION', '1.0.8' );
define( 'WC_BARION_PIXEL_PATH', plugin_dir_path( __FILE__ ) );
define( 'WC_BARION_PIXEL_URL', plugin_dir_url( __FILE__ ) );

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
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Load options.
		$this->options = get_option(
			'wc_barion_pixel_settings',
			array(
				'pixel_id'             => '',
				'enable_full_tracking' => true,
				'debug_mode'           => false,
			)
		);

		// Admin hooks.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Register as WP Consent API compatible.
		add_filter( 'wp_consent_api_registered_' . plugin_basename( __FILE__ ), '__return_true' );

		// Only load tracking if pixel ID is set.
		if ( ! empty( $this->options['pixel_id'] ) ) {
			// Enqueue scripts.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_base_script' ), 1 );
			add_action( 'wp_footer', array( $this, 'output_footer_action' ), 999 );
			// Every callback below calls WooCommerce functions, so none of them
			// may be registered on a site without WooCommerce.
			if ( $this->is_full_tracking_enabled() && class_exists( 'WooCommerce' ) ) {
				add_action( 'woocommerce_after_single_product', array( $this, 'track_content_view' ) );
				add_action( 'woocommerce_thankyou', array( $this, 'track_purchase' ), 10, 1 );
				add_action( 'woocommerce_thankyou', array( $this, 'track_set_encrypted_email' ), 10, 1 );
				// Priority must be < 20 so wp_print_footer_scripts (priority 20) prints the enqueued script.
				add_action( 'wp_footer', array( $this, 'enqueue_events_script' ), 5 );
			}
		}
	}

	/**
	 * Check if full tracking is enabled
	 *
	 * @return bool True if full tracking is enabled, false otherwise
	 */
	private function is_full_tracking_enabled() {
		return ! empty( $this->options['enable_full_tracking'] );
	}

	/**
	 * Check if debug mode is enabled
	 *
	 * @return bool True if debug mode is enabled, false otherwise
	 */
	private function is_debug_mode() {
		return ! empty( $this->options['debug_mode'] );
	}

	/**
	 * Add admin menu (WordPress admin_menu hook callback)
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Barion Pixel Settings', 'advanced-pixel-for-barion' ),
			__( 'Barion Pixel', 'advanced-pixel-for-barion' ),
			'manage_options',
			'advanced-pixel-for-barion',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings (WordPress admin_init hook callback)
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'wc_barion_pixel_group', 'wc_barion_pixel_settings', array( $this, 'sanitize_settings' ) );

		add_settings_section(
			'wc_barion_pixel_main_section',
			__( 'Barion Pixel Configuration', 'advanced-pixel-for-barion' ),
			array( $this, 'render_section_description' ),
			'advanced-pixel-for-barion'
		);

		add_settings_field(
			'pixel_id',
			__( 'Pixel ID', 'advanced-pixel-for-barion' ),
			array( $this, 'render_pixel_id_field' ),
			'advanced-pixel-for-barion',
			'wc_barion_pixel_main_section'
		);

		add_settings_field(
			'enable_full_tracking',
			__( 'Enable Full Pixel Tracking', 'advanced-pixel-for-barion' ),
			array( $this, 'render_enable_tracking_field' ),
			'advanced-pixel-for-barion',
			'wc_barion_pixel_main_section'
		);

		add_settings_field(
			'debug_mode',
			__( 'Debug Mode', 'advanced-pixel-for-barion' ),
			array( $this, 'render_debug_mode_field' ),
			'advanced-pixel-for-barion',
			'wc_barion_pixel_main_section'
		);
	}

	/**
	 * Sanitize settings input
	 *
	 * @param array $input The raw settings input from the form.
	 * @return array The sanitized settings
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		if ( isset( $input['pixel_id'] ) ) {
			$sanitized['pixel_id'] = sanitize_text_field( $input['pixel_id'] );
		}

		$sanitized['enable_full_tracking'] = isset( $input['enable_full_tracking'] ) ? true : false;
		$sanitized['debug_mode']           = isset( $input['debug_mode'] ) ? true : false;

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
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wc_barion_pixel_group' );
				do_settings_sections( 'advanced-pixel-for-barion' );
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
		echo '<p>' . esc_html__( 'Configure your Barion Pixel integration. The Base Pixel will be loaded on all pages when a Pixel ID is provided. Full tracking includes e-commerce events like product views, add to cart, checkout, and purchase.', 'advanced-pixel-for-barion' ) . '</p>';

		// Barion rejects a Full Pixel integration that never sends grantConsent,
		// and the merchant has no way to see that from here. Without the WP
		// Consent API only the banners the plugin knows by name are bridged.
		if ( ! function_exists( 'wp_has_consent' ) ) {
			echo '<div class="notice notice-warning inline"><p>';
			echo esc_html__( 'The WP Consent API plugin is not active. Consent is still forwarded automatically for CookieYes, Complianz, Cookiebot and Cookie Law Info.', 'advanced-pixel-for-barion' );
			echo ' ';
			echo esc_html__( 'With any other cookie banner you must call window.wcBarionGrantConsent() yourself. Barion does not approve a Full Pixel integration that never sends grantConsent.', 'advanced-pixel-for-barion' );
			echo ' <a href="https://wordpress.org/plugins/wp-consent-api/" target="_blank" rel="noopener noreferrer">';
			echo esc_html__( 'Install the WP Consent API plugin', 'advanced-pixel-for-barion' );
			echo '</a></p></div>';
		}

		$barion_settings = get_option( 'woocommerce_barion_settings', array() );
		if ( ! empty( $barion_settings['barion_pixel_id'] ) && ! empty( $this->options['pixel_id'] ) ) {
			echo '<p>' . esc_html__( 'The Barion Payment Gateway plugin also has a Pixel ID configured. Both plugins will work correctly together — the base pixel script will only be loaded once. You may remove the Pixel ID from the payment gateway settings to keep configuration in one place.', 'advanced-pixel-for-barion' ) . '</p>';
		}
	}

	/**
	 * Render pixel ID field
	 *
	 * @return void
	 */
	public function render_pixel_id_field() {
		$value = isset( $this->options['pixel_id'] ) ? $this->options['pixel_id'] : '';
		?>
		<input type="text"
				name="wc_barion_pixel_settings[pixel_id]"
				value="<?php echo esc_attr( $value ); ?>"
				class="regular-text"
				placeholder="BP-0000000000-00"
				required>
		<p class="description"><?php esc_html_e( 'Enter your Barion Pixel ID (e.g., BP-0000000000-00)', 'advanced-pixel-for-barion' ); ?></p>
		<?php
	}

	/**
	 * Render enable tracking field
	 *
	 * @return void
	 */
	public function render_enable_tracking_field() {
		$value = ! empty( $this->options['enable_full_tracking'] );
		?>
		<label>
			<input type="checkbox"
					name="wc_barion_pixel_settings[enable_full_tracking]"
					value="1"
					<?php checked( $value, true ); ?>>
			<?php esc_html_e( 'Enable full Barion Pixel event tracking (contentView, addToCart, initiateCheckout, purchase)', 'advanced-pixel-for-barion' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Base Pixel script will always be loaded. Enable this to track e-commerce events.', 'advanced-pixel-for-barion' ); ?></p>
		<?php
	}

	/**
	 * Render debug mode field
	 *
	 * @return void
	 */
	public function render_debug_mode_field() {
		$value = ! empty( $this->options['debug_mode'] );
		?>
		<label>
			<input type="checkbox"
					name="wc_barion_pixel_settings[debug_mode]"
					value="1"
					<?php checked( $value, true ); ?>>
			<?php esc_html_e( 'Enable debug mode (logs events to browser console)', 'advanced-pixel-for-barion' ); ?>
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
			'wc-barion-consent',
			WC_BARION_PIXEL_URL . 'assets/js/barion-consent.js',
			array(),
			WC_BARION_PIXEL_VERSION,
			false
		);
		wp_enqueue_script(
			'wc-barion-pixel-base',
			WC_BARION_PIXEL_URL . 'assets/js/barion-pixel-base.js',
			array( 'wc-barion-consent' ),
			WC_BARION_PIXEL_VERSION,
			false
		);
		wp_localize_script(
			'wc-barion-pixel-base',
			'wcBarionPixelBase',
			array(
				'pixelId' => $this->options['pixel_id'],
				'debug'   => $this->is_debug_mode(),
			)
		);
	}

	/**
	 * Fire the footer action for backwards compatibility (WordPress wp_footer hook callback)
	 *
	 * @return void
	 */
	public function output_footer_action() {
		do_action( 'wc_barion_pixel_footer_scripts' );
	}

	/**
	 * Track content view on product page (WooCommerce woocommerce_after_single_product hook callback)
	 * Implements minimal required fields per Barion documentation
	 *
	 * @return void
	 */
	public function track_content_view() {
		global $product;

		if ( ! is_product() || ! $product ) {
			return;
		}

		$price = (float) $product->get_price();

		// Required fields for contentView event per Barion Pixel API reference
		// Note: totalItemPrice is documented as required but bp.js rejects it for contentView.
		$content_data = array(
			'contentType' => 'Product',
			'currency'    => get_woocommerce_currency(),
			'id'          => (string) $product->get_id(),
			'name'        => $product->get_name(),
			'quantity'    => 1,
			'unit'        => 'pcs',
			'unitPrice'   => $price,
		);

		$this->queue_event( 'contentView', $content_data );
	}

	/**
	 * Enqueue events script with all collected data (WordPress wp_footer hook callback)
	 *
	 * Only hooked when WooCommerce is active, so WooCommerce functions are safe to call.
	 *
	 * @return void
	 */
	public function enqueue_events_script() {
		// Build single product data for addToCart tracking on product pages.
		$single_product = null;
		if ( is_product() ) {
			global $product;
			if ( $product ) {
				$single_product = array(
					'id'    => (string) $product->get_id(),
					'name'  => $product->get_name(),
					'price' => (float) $product->get_price(),
				);
			}
		}

		// Detect checkout (excluding the order-received endpoint) so JS can listen
		// for email field changes and fire setEncryptedEmail at email entry time,
		// as the Pixel API reference requires (https://docs.barion.com/Barion-Pixel-API-referencia).
		$is_checkout = is_checkout() && ! is_wc_endpoint_url( 'order-received' );

		// Pre-fill billing email for logged-in users so setEncryptedEmail fires immediately on checkout load.
		$logged_in_email = '';
		if ( $is_checkout && is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( ! empty( $user->user_email ) ) {
				$logged_in_email = strtolower( $user->user_email );
			}
		}

		if ( $is_checkout ) {
			$this->queue_initiate_checkout();
		}

		// Ask WooCommerce what this page actually loaded rather than guessing at
		// block names with has_block(). The Cart and Checkout blocks register this
		// handle in WooCommerce's src/Blocks/AssetsController.php.
		$has_block_store = wp_script_is( 'wc-blocks-data-store', 'enqueued' );

		// Add to cart happens on shop and archive pages, not only where an event
		// is already queued. Without this the addToCart listeners never load on
		// the pages that actually fire them.
		$can_add_to_cart = $has_block_store || is_woocommerce() || is_cart();

		// Only enqueue if there's something to do.
		if ( empty( $this->events ) && null === $single_product && null === $this->encrypted_email
			&& ! $is_checkout && ! $can_add_to_cart ) {
			return;
		}

		wp_enqueue_script(
			'wc-barion-cart-diff',
			WC_BARION_PIXEL_URL . 'assets/js/barion-cart-diff.js',
			array(),
			WC_BARION_PIXEL_VERSION,
			true
		);
		$deps = array( 'wc-barion-pixel-base', 'wc-barion-cart-diff' );

		// wp.data is needed only for the checkout email, which the Cart and
		// Checkout blocks expose through their data store. Add to cart reads the
		// Store API instead: product buttons run on the Interactivity API and
		// never load this store, so a page can have add-to-cart without it.
		if ( $has_block_store ) {
			$deps[] = 'wp-data';
			$deps[] = 'wc-blocks-data-store';
		}

		wp_enqueue_script(
			'wc-barion-pixel-events',
			WC_BARION_PIXEL_URL . 'assets/js/barion-pixel-events.js',
			$deps,
			WC_BARION_PIXEL_VERSION,
			true
		);
		wp_localize_script(
			'wc-barion-pixel-events',
			'wcBarionPixelEvents',
			array(
				'currency'      => get_woocommerce_currency(),
				'debug'         => $this->is_debug_mode(),
				'events'        => $this->events,
				'singleProduct' => $single_product,
				'email'         => $this->encrypted_email,
				'isCheckout'    => $is_checkout,
				'loggedInEmail' => $logged_in_email,
				// Built with rest_url() so it survives plain permalinks and a custom REST prefix.
				'cartApiUrl'    => rest_url( 'wc/store/v1/cart' ),
			)
		);
	}

	/**
	 * Queue the initiateCheckout event from the current cart.
	 * Called from enqueue_events_script() rather than from
	 * woocommerce_before_checkout_form, which the Checkout block never fires.
	 *
	 * @return void
	 */
	private function queue_initiate_checkout() {
		if ( ! WC()->cart ) {
			return;
		}

		$cart  = WC()->cart;
		$items = array();

		foreach ( $cart->get_cart() as $cart_item ) {
			$product    = $cart_item['data'];
			$qty        = (int) $cart_item['quantity'];
			$unit_price = (float) $product->get_price();
			$items[]    = array(
				'contentType'    => 'Product',
				'currency'       => get_woocommerce_currency(),
				'id'             => (string) $product->get_id(),
				'name'           => $product->get_name(),
				'quantity'       => $qty,
				'unit'           => 'pcs',
				'unitPrice'      => $unit_price,
				'totalItemPrice' => $unit_price * $qty,
			);
		}

		// Calculate revenue including tax
		// Note: Shipping is not included at this stage as it may not be calculated yet
		// The customer may still need to enter shipping details or select shipping method.
		$event_data = array(
			'contents' => $items,
			'currency' => get_woocommerce_currency(),
			'revenue'  => (float) ( $cart->get_cart_contents_total() + $cart->get_cart_contents_tax() ), // Subtotal + tax (no shipping).
			'step'     => 1,
		);

		$this->queue_event( 'initiateCheckout', $event_data );
	}

	/**
	 * Track purchase event on order completion (WooCommerce woocommerce_thankyou hook callback)
	 *
	 * @param int $order_id The order ID.
	 * @return void
	 */
	public function track_purchase( $order_id ) {
		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// Check if already tracked to prevent duplicate tracking.
		if ( $order->get_meta( '_wc_barion_tracked', true ) ) {
			return;
		}

		$items = array();

		foreach ( $order->get_items() as $item ) {
			// get_items() only returns line items, but the base type does not say so.
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}
			$product = $item->get_product();
			if ( $product ) {
				// Calculate actual unit price (including discounts and tax): (item total + tax) / quantity.
				$total_with_tax = (float) $item->get_total() + (float) $item->get_total_tax();
				$unit_price     = $item->get_quantity() > 0
					? $total_with_tax / $item->get_quantity()
					: 0;

				$qty     = (int) $item->get_quantity();
				$items[] = array(
					'contentType'    => 'Product',
					'currency'       => $order->get_currency(),
					'id'             => (string) $product->get_id(),
					'name'           => $item->get_name(),
					'quantity'       => $qty,
					'unit'           => 'pcs',
					'unitPrice'      => (float) $unit_price,
					'totalItemPrice' => (float) ( $unit_price * $qty ),
				);
			}
		}

		$event_data = array(
			'contents' => $items,
			'currency' => $order->get_currency(),
			'revenue'  => (float) $order->get_total(),
			'step'     => 1,
		);

		$this->queue_event( 'purchase', $event_data );

		// Mark as tracked.
		// '1' is what WordPress stores for true; update_meta_data() only accepts array|string.
		$order->update_meta_data( '_wc_barion_tracked', '1' );
		$order->save();
	}

	/**
	 * Collect encrypted email for Barion user identification (WooCommerce woocommerce_thankyou hook callback)
	 * Per Barion docs, bp.js handles SHA1 encryption when given a plain email address.
	 *
	 * @param int $order_id The order ID.
	 * @return void
	 */
	public function track_set_encrypted_email( $order_id ) {
		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$email = strtolower( $order->get_billing_email() );

		if ( empty( $email ) ) {
			return;
		}

		$this->encrypted_email = $email;
	}

	/**
	 * Queue an event to be output via localized script data
	 *
	 * @param string $event_name The name of the Barion Pixel event to track.
	 * @param array  $event_data The event data to send with the tracking call.
	 * @return void
	 */
	private function queue_event( $event_name, $event_data ) {
		$this->events[] = array(
			'name' => $event_name,
			'data' => $event_data,
		);
	}
}

// Declare HPOS compatibility.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Load the bundled translations. Without this, WordPress only searches
 * wp-content/languages/plugins, so the .mo files shipped in languages/ are
 * never found. Runs on init because load_plugin_textdomain() must not fire
 * before the locale is settled.
 */
function wc_barion_pixel_load_textdomain() {
	load_plugin_textdomain(
		'advanced-pixel-for-barion',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', 'wc_barion_pixel_load_textdomain' );

/**
 * Initialize the plugin.
 */
function wc_barion_pixel_init() {
	WC_Barion_Pixel::get_instance();
}
add_action( 'plugins_loaded', 'wc_barion_pixel_init' );
