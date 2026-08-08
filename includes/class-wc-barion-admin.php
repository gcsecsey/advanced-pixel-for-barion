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
        add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_recorder'), 1);
        add_action('wp_ajax_apb_save_trigger', array($this, 'ajax_save_trigger'));
        add_action('wp_ajax_apb_save_probe', array($this, 'ajax_save_probe'));
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
            <?php $this->render_wizard(); ?>
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

        wp_enqueue_script(
            'wc-barion-admin',
            WC_BARION_PIXEL_URL . 'assets/js/barion-admin.js',
            array(),
            WC_BARION_PIXEL_VERSION,
            true
        );

        wp_localize_script('wc-barion-admin', 'wcBarionAdmin', array(
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('apb_admin'),
            'recordUrl' => add_query_arg(
                'apb_record_consent',
                wp_create_nonce('apb_record_consent'),
                home_url('/')
            ),
            'strings'   => array(
                'recordAccept' => __('Your shop opens in a new tab. Accept cookies in your banner there, then return here.', 'advanced-pixel-for-barion'),
                'recordReject' => __('Now reject cookies in that tab. Clear your cookies first if the banner does not appear again.', 'advanced-pixel-for-barion'),
                'waiting'      => __('Recording. Waiting for the banner.', 'advanced-pixel-for-barion'),
                'openShop'     => __('Open my shop', 'advanced-pixel-for-barion'),
                'next'         => __('Next', 'advanced-pixel-for-barion'),
                'save'         => __('Save', 'advanced-pixel-for-barion'),
                'saved'        => __('Consent setup saved. Reloading.', 'advanced-pixel-for-barion'),
                'needBoth'     => __('Record both accept and reject before saving.', 'advanced-pixel-for-barion'),
                'noChange'     => __('No cookie changed. Make a choice in the banner on the other tab.', 'advanced-pixel-for-barion'),
                'testing'      => __('Testing…', 'advanced-pixel-for-barion'),
                'probeFailed'  => __('The check did not finish. Try again.', 'advanced-pixel-for-barion'),
                'recorderSilent' => __('No signal from your shop. The recording link may have expired. Close this, then press Set up consent again.', 'advanced-pixel-for-barion'),
                /* translators: %d: number of health checks. The script replaces %d with the number. */
                'hideChecks'   => __('Hide %d checks', 'advanced-pixel-for-barion'),
            ),
        ));
    }

    /**
     * Load the consent recorder on the front end, for an administrator who came
     * from the setup wizard. It never loads for a visitor.
     *
     * @return void
     */
    public function maybe_enqueue_recorder() {
        if (!isset($_GET['apb_record_consent'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_GET['apb_record_consent']));
        if (!wp_verify_nonce($nonce, 'apb_record_consent')) {
            return;
        }

        wp_enqueue_script(
            'wc-barion-consent-recorder',
            WC_BARION_PIXEL_URL . 'assets/js/barion-consent-recorder.js',
            array(),
            WC_BARION_PIXEL_VERSION,
            false
        );

        wp_localize_script('wc-barion-consent-recorder', 'wcBarionRecorder', array(
            'banner' => __('Barion Pixel is recording your cookie banner. Make your choice in the banner, then return to the settings tab.', 'advanced-pixel-for-barion'),
        ));
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

    /**
     * Save the recorded consent trigger pair.
     *
     * @return void
     */
    public function ajax_save_trigger() {
        check_ajax_referer('apb_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'advanced-pixel-for-barion')), 403);
        }

        $raw = isset($_POST['trigger']) ? wp_unslash($_POST['trigger']) : '';
        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            wp_send_json_error(array('message' => __('The trigger could not be read.', 'advanced-pixel-for-barion')), 400);
        }

        $grant  = isset($decoded['grant']) ? WC_Barion_Pixel::sanitize_trigger($decoded['grant']) : null;
        $reject = isset($decoded['reject']) ? WC_Barion_Pixel::sanitize_trigger($decoded['reject']) : null;

        // Barion requires both signals, so refuse to store half a trigger.
        if (null === $grant || null === $reject) {
            wp_send_json_error(array(
                'message' => __('Record both the accept signal and the reject signal before saving.', 'advanced-pixel-for-barion'),
            ), 400);
        }

        $options = get_option('wc_barion_pixel_settings', array());
        $options['consent_trigger'] = array(
            'grant'       => $grant,
            'reject'      => $reject,
            'recorded_at' => gmdate('c'),
        );
        update_option('wc_barion_pixel_settings', $options);

        wp_send_json_success(array('message' => __('Consent setup saved.', 'advanced-pixel-for-barion')));
    }

    /**
     * Save a browser check result.
     *
     * @return void
     */
    public function ajax_save_probe() {
        check_ajax_referer('apb_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'advanced-pixel-for-barion')), 403);
        }

        $kind = isset($_POST['kind']) ? sanitize_key(wp_unslash($_POST['kind'])) : '';
        $probe = get_option('wc_barion_pixel_probe', array());

        if ('consent' === $kind) {
            $probe['consent'] = array(
                'consent_type' => isset($_POST['consent_type']) ? sanitize_text_field(wp_unslash($_POST['consent_type'])) : '',
                'has_consent'  => isset($_POST['has_consent']) && 'true' === $_POST['has_consent'],
            );
        } elseif ('reachability' === $kind) {
            $probe['reachability'] = array(
                'ok' => isset($_POST['ok']) && 'true' === $_POST['ok'],
            );
        } else {
            wp_send_json_error(array('message' => __('Unknown check.', 'advanced-pixel-for-barion')), 400);
        }

        update_option('wc_barion_pixel_probe', $probe, false);
        wp_send_json_success();
    }

    /**
     * Render the consent setup wizard dialog.
     *
     * @return void
     */
    public function render_wizard() {
        $facts = WC_Barion_Health::gather_facts($this->options);
        $api_usable = $facts['consent_api_active'] && '' !== $facts['consent_type'];
        ?>
        <dialog class="apb-dialog" id="apb-wizard">
            <div class="apb-dialog-head">
                <h2><?php esc_html_e('Set up consent', 'advanced-pixel-for-barion'); ?></h2>
                <button type="button" class="button-link" data-apb-close>&times;</button>
            </div>

            <div class="apb-dialog-body">
                <div class="apb-step is-active" data-step="1">
                    <p><?php esc_html_e('Barion may receive marketing data only after the visitor agrees. The plugin needs a reliable signal for accept and for reject.', 'advanced-pixel-for-barion'); ?></p>

                    <label class="apb-choice is-selected" data-choice="learn">
                        <input type="radio" name="apb_source" value="learn" checked>
                        <span>
                            <strong><?php esc_html_e('Teach the plugin my banner', 'advanced-pixel-for-barion'); ?></strong>
                            <div class="apb-desc"><?php esc_html_e('You open your shop, accept, then reject. The plugin records what changes. This works with any cookie banner.', 'advanced-pixel-for-barion'); ?></div>
                        </span>
                    </label>

                    <label class="apb-choice<?php echo $api_usable ? '' : ' is-disabled'; ?>" data-choice="api">
                        <input type="radio" name="apb_source" value="api" <?php disabled(!$api_usable); ?>>
                        <span>
                            <strong><?php esc_html_e('Use the WP Consent API', 'advanced-pixel-for-barion'); ?></strong>
                            <div class="apb-desc">
                                <?php
                                echo $api_usable
                                    ? esc_html__('A cookie banner plugin sets a consent type. Nothing more is needed.', 'advanced-pixel-for-barion')
                                    : esc_html__('Not usable: no cookie banner plugin sets a consent type.', 'advanced-pixel-for-barion');
                                ?>
                            </div>
                        </span>
                    </label>

                    <label class="apb-choice" data-choice="none">
                        <input type="radio" name="apb_source" value="none">
                        <span>
                            <strong><?php esc_html_e('Do nothing', 'advanced-pixel-for-barion'); ?></strong>
                            <div class="apb-desc"><?php esc_html_e('Consent is never granted. Full tracking collects no marketing data.', 'advanced-pixel-for-barion'); ?></div>
                        </span>
                    </label>
                </div>

                <div class="apb-step" data-step="2">
                    <p data-apb-record-intro></p>
                    <div class="apb-recorder" id="apb-recorder-log"></div>
                    <p class="apb-desc"><?php esc_html_e('The recorder watches cookie changes and event names only. It runs for you alone and never for your visitors.', 'advanced-pixel-for-barion'); ?></p>
                </div>

                <div class="apb-step" data-step="3">
                    <p><?php esc_html_e('Grant consent to Barion when this is true:', 'advanced-pixel-for-barion'); ?></p>
                    <p>
                        <label><?php esc_html_e('Cookie name', 'advanced-pixel-for-barion'); ?><br>
                        <input type="text" class="regular-text" id="apb-grant-cookie"></label>
                    </p>
                    <p>
                        <label><?php esc_html_e('Accepted value contains', 'advanced-pixel-for-barion'); ?><br>
                        <input type="text" class="regular-text" id="apb-grant-contains"></label>
                    </p>
                    <p>
                        <label><?php esc_html_e('Rejected value contains', 'advanced-pixel-for-barion'); ?><br>
                        <input type="text" class="regular-text" id="apb-reject-contains"></label>
                    </p>
                    <div id="apb-wizard-notice"></div>
                </div>
            </div>

            <div class="apb-dialog-foot">
                <span class="apb-steps">
                    <span class="is-on" data-dot="1">1</span><span data-dot="2">2</span><span data-dot="3">3</span>
                </span>
                <span>
                    <button type="button" class="button" data-apb-back><?php esc_html_e('Back', 'advanced-pixel-for-barion'); ?></button>
                    <button type="button" class="button button-primary" data-apb-next><?php esc_html_e('Next', 'advanced-pixel-for-barion'); ?></button>
                </span>
            </div>
        </dialog>
        <?php
    }

}
