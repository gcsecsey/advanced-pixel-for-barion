<?php
/**
 * Health rules for Advanced Pixel for Barion.
 *
 * evaluate() is pure: it reads only the facts array and calls no WordPress
 * function, so tests/health-test.php can run it under plain PHP.
 *
 * @package Advanced_Pixel_For_Barion
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Barion_Health {

    /**
     * Valid Barion Pixel ID format.
     */
    const PIXEL_ID_PATTERN = '/^BP-\d{10}-\d{2}$/';

    /**
     * Turn a facts array into an ordered list of check rows.
     *
     * @param array $facts See the plan for the required keys.
     * @return array List of check rows, worst status first.
     */
    public static function evaluate($facts) {
        $checks = array();

        $checks[] = self::check_pixel_id($facts);

        $source = self::resolve_source($facts);
        $checks[] = self::check_consent_source($facts, $source);

        if (!empty($facts['consent_api_active'])) {
            $checks[] = self::check_consent_type($facts);
        }

        $both = self::check_both_signals($facts);
        if (null !== $both) {
            $checks[] = $both;
        }

        $checks[] = self::check_woocommerce($facts);
        $checks[] = self::check_full_tracking($facts);

        $duplicate = self::check_gateway_duplicate($facts);
        if (null !== $duplicate) {
            $checks[] = $duplicate;
        }

        $checks[] = self::check_cookies_declared($facts);
        $checks[] = self::check_category();
        $checks[] = self::check_reachability($facts);

        return self::sort_checks($checks);
    }

    /**
     * Reduce a list of check rows to a single overall status.
     *
     * @param array $checks Check rows from evaluate().
     * @return string One of 'ok', 'warn', 'fail'.
     */
    public static function overall_status($checks) {
        $worst = 'ok';
        foreach ($checks as $check) {
            if ('fail' === $check['status']) {
                return 'fail';
            }
            if ('warn' === $check['status']) {
                $worst = 'warn';
            }
        }
        return $worst;
    }

    /**
     * Read WordPress state into a facts array for evaluate().
     *
     * This is the only method in this class that touches WordPress.
     *
     * @param array $options The wc_barion_pixel_settings option.
     * @return array Facts array.
     */
    public static function gather_facts($options) {
        $gateway = get_option('woocommerce_barion_settings', array());
        $probe   = get_option('wc_barion_pixel_probe', array());

        return array(
            'pixel_id'           => isset($options['pixel_id']) ? $options['pixel_id'] : '',
            'woocommerce_active' => class_exists('WooCommerce'),
            'full_tracking'      => !empty($options['enable_full_tracking']),
            'consent_api_active' => function_exists('wp_has_consent'),
            'consent_type'       => function_exists('wp_get_consent_type') ? (string) wp_get_consent_type() : '',
            'cli_active'         => defined('CLI_PLUGIN_PATH') || defined('CLI_PLUGIN_FILENAME') || class_exists('Cookie_Law_Info'),
            'trigger'            => isset($options['consent_trigger']) ? $options['consent_trigger'] : null,
            'gateway_pixel_id'   => isset($gateway['barion_pixel_id']) ? $gateway['barion_pixel_id'] : '',
            'browser_probe'      => isset($probe['consent']) ? $probe['consent'] : null,
            'reachability'       => isset($probe['reachability']) ? $probe['reachability'] : null,
        );
    }

    /**
     * Decide which consent tier is live. Mirrors the order in barion-pixel-base.js.
     *
     * @param array $facts Facts array.
     * @return string One of 'learned', 'wp-consent-api', 'cookie-law-info', 'none'.
     */
    private static function resolve_source($facts) {
        if (!empty($facts['trigger']['grant']) && !empty($facts['trigger']['reject'])) {
            return 'learned';
        }
        if (!empty($facts['consent_api_active']) && '' !== $facts['consent_type']) {
            return 'wp-consent-api';
        }
        if (!empty($facts['cli_active'])) {
            return 'cookie-law-info';
        }
        return 'none';
    }

    private static function row($id, $status, $label, $desc, $action = null, $target = '') {
        return array(
            'id'     => $id,
            'status' => $status,
            'label'  => $label,
            'desc'   => $desc,
            'action' => $action,
            'target' => $target,
        );
    }

    private static function check_pixel_id($facts) {
        if ('' === trim($facts['pixel_id'])) {
            return self::row(
                'pixel_id',
                'fail',
                __('Pixel ID is missing', 'advanced-pixel-for-barion'),
                __('Nothing is tracked until you enter your Barion Pixel ID.', 'advanced-pixel-for-barion')
            );
        }
        if (!preg_match(self::PIXEL_ID_PATTERN, $facts['pixel_id'])) {
            return self::row(
                'pixel_id',
                'fail',
                __('Pixel ID has the wrong format', 'advanced-pixel-for-barion'),
                __('The ID must look like BP-0000000000-00. Barion ignores anything else.', 'advanced-pixel-for-barion')
            );
        }
        return self::row(
            'pixel_id',
            'ok',
            __('Pixel ID', 'advanced-pixel-for-barion'),
            $facts['pixel_id']
        );
    }

    private static function check_consent_source($facts, $source) {
        if ('none' === $source) {
            return self::row(
                'consent_source',
                'fail',
                __('No consent source is set up', 'advanced-pixel-for-barion'),
                __('Barion never receives consent, so full tracking collects no marketing data. Teach the plugin your cookie banner.', 'advanced-pixel-for-barion'),
                array(
                    'type'   => 'modal',
                    'label'  => __('Set up consent', 'advanced-pixel-for-barion'),
                    'target' => 'wizard',
                ),
                $source
            );
        }

        $labels = array(
            'learned'         => __('Consent comes from your cookie banner, recorded by the wizard', 'advanced-pixel-for-barion'),
            'wp-consent-api'  => __('Consent comes from the WP Consent API', 'advanced-pixel-for-barion'),
            'cookie-law-info' => __('Consent comes from Cookie Law Info', 'advanced-pixel-for-barion'),
        );

        return self::row(
            'consent_source',
            'info',
            $labels[$source],
            __('Barion receives grantConsent and rejectConsent from this source.', 'advanced-pixel-for-barion'),
            array(
                'type'   => 'modal',
                'label'  => __('Change', 'advanced-pixel-for-barion'),
                'target' => 'wizard',
            ),
            $source
        );
    }

    private static function check_consent_type($facts) {
        if ('' !== $facts['consent_type']) {
            return self::row(
                'consent_type_set',
                'ok',
                __('A cookie banner plugin sets the consent type', 'advanced-pixel-for-barion'),
                __('Visitor choices reach the plugin correctly.', 'advanced-pixel-for-barion')
            );
        }

        $probe = $facts['browser_probe'];

        if (null === $probe) {
            return self::row(
                'consent_type_set',
                'warn',
                __('No cookie banner plugin sets a consent type', 'advanced-pixel-for-barion'),
                __('When nothing sets a consent type, the WP Consent API reports consent for every visitor, and the pixel sends grantConsent without any consent. Some banners set the consent type in the browser only, so check there before you act.', 'advanced-pixel-for-barion'),
                array(
                    'type'   => 'button',
                    'label'  => __('Check in browser', 'advanced-pixel-for-barion'),
                    'target' => 'probe',
                )
            );
        }

        if ('' !== $probe['consent_type']) {
            return self::row(
                'consent_type_set',
                'ok',
                __('Your cookie banner sets the consent type in the browser', 'advanced-pixel-for-barion'),
                __('Nothing is set on the server, but the browser check found a consent type. Visitor choices reach the plugin correctly.', 'advanced-pixel-for-barion')
            );
        }

        if (!empty($probe['has_consent'])) {
            return self::row(
                'consent_type_set',
                'fail',
                __('Every visitor is counted as consenting', 'advanced-pixel-for-barion'),
                __('The browser check found marketing consent granted before anyone touched a banner. The pixel sends grantConsent for every visitor. This breaks the GDPR and the Barion terms. Install a cookie banner plugin that supports the WP Consent API, or teach the plugin your banner.', 'advanced-pixel-for-barion'),
                array(
                    'type'   => 'modal',
                    'label'  => __('Set up consent', 'advanced-pixel-for-barion'),
                    'target' => 'wizard',
                )
            );
        }

        return self::row(
            'consent_type_set',
            'ok',
            __('Consent is not granted automatically', 'advanced-pixel-for-barion'),
            __('The browser check found no consent before any banner interaction.', 'advanced-pixel-for-barion')
        );
    }

    private static function check_both_signals($facts) {
        if (empty($facts['trigger'])) {
            return null;
        }

        $has_grant  = !empty($facts['trigger']['grant']);
        $has_reject = !empty($facts['trigger']['reject']);

        if ($has_grant && $has_reject) {
            return self::row(
                'consent_both_signals',
                'ok',
                __('Accept and reject are both recorded', 'advanced-pixel-for-barion'),
                __('Barion requires both grantConsent and rejectConsent.', 'advanced-pixel-for-barion')
            );
        }

        $missing = $has_grant
            ? __('The reject signal is missing.', 'advanced-pixel-for-barion')
            : __('The accept signal is missing.', 'advanced-pixel-for-barion');

        return self::row(
            'consent_both_signals',
            'fail',
            __('Only one consent signal is recorded', 'advanced-pixel-for-barion'),
            $missing . ' ' . __('Barion requires both grantConsent and rejectConsent. Record the missing one.', 'advanced-pixel-for-barion'),
            array(
                'type'   => 'modal',
                'label'  => __('Finish setup', 'advanced-pixel-for-barion'),
                'target' => 'wizard',
            )
        );
    }

    private static function check_woocommerce($facts) {
        if (!empty($facts['woocommerce_active'])) {
            return self::row(
                'woocommerce',
                'ok',
                __('WooCommerce is active', 'advanced-pixel-for-barion'),
                ''
            );
        }
        if (empty($facts['full_tracking'])) {
            return self::row(
                'woocommerce',
                'info',
                __('WooCommerce is not active', 'advanced-pixel-for-barion'),
                __('Full tracking is off, so nothing depends on WooCommerce.', 'advanced-pixel-for-barion')
            );
        }
        return self::row(
            'woocommerce',
            'warn',
            __('WooCommerce is not active', 'advanced-pixel-for-barion'),
            __('Full tracking is on, but the e-commerce events need WooCommerce. Only the base pixel runs.', 'advanced-pixel-for-barion')
        );
    }

    private static function check_full_tracking($facts) {
        if (!empty($facts['full_tracking'])) {
            return self::row(
                'full_tracking',
                'info',
                __('Full tracking is on', 'advanced-pixel-for-barion'),
                __('contentView, addToCart, initiateCheckout, purchase, setEncryptedEmail', 'advanced-pixel-for-barion')
            );
        }
        return self::row(
            'full_tracking',
            'info',
            __('Full tracking is off', 'advanced-pixel-for-barion'),
            __('Only the base pixel runs. It sends pageView for fraud prevention.', 'advanced-pixel-for-barion')
        );
    }

    private static function check_gateway_duplicate($facts) {
        if ('' === trim($facts['gateway_pixel_id']) || '' === trim($facts['pixel_id'])) {
            return null;
        }
        return self::row(
            'gateway_duplicate_id',
            'warn',
            __('Barion Payment Gateway holds a second Pixel ID', 'advanced-pixel-for-barion'),
            __('Both plugins work together and the base script loads only once. You may remove the ID from the gateway to keep one source of truth.', 'advanced-pixel-for-barion')
        );
    }

    private static function check_cookies_declared($facts) {
        if (!empty($facts['consent_api_active'])) {
            return self::row(
                'cookies_declared',
                'ok',
                __('Barion cookies are declared', 'advanced-pixel-for-barion'),
                __('They appear in your cookie policy through the WP Consent API.', 'advanced-pixel-for-barion')
            );
        }
        return self::row(
            'cookies_declared',
            'info',
            __('Barion cookies are not declared', 'advanced-pixel-for-barion'),
            __('Barion sets ba_sid, ba_vid and BarionMarketingConsent on your domain. Without the WP Consent API plugin they cannot be added to your cookie policy automatically, so add them by hand.', 'advanced-pixel-for-barion')
        );
    }

    private static function check_category() {
        return self::row(
            'category',
            'info',
            __('Consent category: marketing', 'advanced-pixel-for-barion'),
            __('Fixed, and not configurable. Barion requires consent for marketing purposes, so marketing is the only correct category. Your cookie banner maps its own advertising category onto it.', 'advanced-pixel-for-barion')
        );
    }

    private static function check_reachability($facts) {
        if (null === $facts['reachability']) {
            return self::row(
                'reachability',
                'info',
                __('Barion pixel script reachable', 'advanced-pixel-for-barion'),
                __('Loads bp.js from this browser. Nothing is sent to your server.', 'advanced-pixel-for-barion'),
                array(
                    'type'   => 'button',
                    'label'  => __('Test', 'advanced-pixel-for-barion'),
                    'target' => 'reachability',
                )
            );
        }
        if (!empty($facts['reachability']['ok'])) {
            return self::row(
                'reachability',
                'ok',
                __('Barion pixel script is reachable', 'advanced-pixel-for-barion'),
                __('bp.js loaded from this browser.', 'advanced-pixel-for-barion')
            );
        }
        return self::row(
            'reachability',
            'warn',
            __('Barion pixel script did not load', 'advanced-pixel-for-barion'),
            __('bp.js did not load in this browser. An ad blocker or a network rule may block it. Your visitors may still reach it.', 'advanced-pixel-for-barion'),
            array(
                'type'   => 'button',
                'label'  => __('Test again', 'advanced-pixel-for-barion'),
                'target' => 'reachability',
            )
        );
    }

    /**
     * Sort rows worst first, keeping the original order inside each status.
     *
     * @param array $checks Check rows.
     * @return array Sorted rows.
     */
    private static function sort_checks($checks) {
        $rank = array('fail' => 0, 'warn' => 1, 'ok' => 2, 'info' => 3);
        $buckets = array(array(), array(), array(), array());
        foreach ($checks as $check) {
            $buckets[$rank[$check['status']]][] = $check;
        }
        return array_merge($buckets[0], $buckets[1], $buckets[2], $buckets[3]);
    }
}
