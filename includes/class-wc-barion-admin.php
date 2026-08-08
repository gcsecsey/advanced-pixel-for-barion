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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
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
        register_setting('wc_barion_pixel_group', 'wc_barion_pixel_settings', array($this, 'sanitize_settings'));

        add_settings_section(
            'wc_barion_pixel_main_section',
            __('Barion Pixel Configuration', 'advanced-pixel-for-barion'),
            array($this, 'render_section_description'),
            'advanced-pixel-for-barion'
        );

        add_settings_field(
            'pixel_id',
            __('Pixel ID', 'advanced-pixel-for-barion'),
            array($this, 'render_pixel_id_field'),
            'advanced-pixel-for-barion',
            'wc_barion_pixel_main_section'
        );

        add_settings_field(
            'enable_full_tracking',
            __('Enable Full Pixel Tracking', 'advanced-pixel-for-barion'),
            array($this, 'render_enable_tracking_field'),
            'advanced-pixel-for-barion',
            'wc_barion_pixel_main_section'
        );

        add_settings_field(
            'debug_mode',
            __('Debug Mode', 'advanced-pixel-for-barion'),
            array($this, 'render_debug_mode_field'),
            'advanced-pixel-for-barion',
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

    /**
     * Render settings page
     *
     * @return void
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php settings_errors(); ?>
            <?php $this->render_health_panel(); ?>
            <form method="post" action="options.php">
                <?php
                settings_fields('wc_barion_pixel_group');
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
               name="wc_barion_pixel_settings[pixel_id]"
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
                   name="wc_barion_pixel_settings[enable_full_tracking]"
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
                   name="wc_barion_pixel_settings[debug_mode]"
                   value="1"
                   <?php checked($value, true); ?>>
            <?php esc_html_e('Enable debug mode (logs events to browser console)', 'advanced-pixel-for-barion'); ?>
        </label>
        <?php
    }

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

}
