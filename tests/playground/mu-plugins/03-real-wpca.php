<?php
/**
 * Plugin Name: Barion test real WP Consent API banner
 *
 * Drives the real wp-consent-api plugin instead of a stub. A CMP that bridges
 * into the WP Consent API does exactly this: it sets window.wp_consent_type
 * and calls wp_set_consent() when the visitor answers.
 *
 * Query args: ?real=1 &ctype=optin|optout|none
 */
add_action('wp_head', function () {
    if (empty($_GET['real'])) {
        return;
    }
    $ctype = isset($_GET['ctype']) ? sanitize_key($_GET['ctype']) : 'optin';
    if ('none' !== $ctype) {
        echo '<script>window.wp_consent_type = ' . wp_json_encode($ctype) . ';</script>';
    }
}, 1);

add_action('wp_footer', function () {
    if (empty($_GET['real'])) {
        return;
    }
    ?>
    <div id="stub-banner" style="position:fixed;bottom:0;left:0;right:0;background:#222;color:#fff;padding:16px;z-index:9999">
        Real WP Consent API
        <button id="stub-accept">Accept</button>
        <button id="stub-decline">Decline</button>
    </div>
    <script>
    document.getElementById( 'stub-accept' ).addEventListener( 'click', function () {
        window.wp_set_consent( 'marketing', 'allow' );
    } );
    document.getElementById( 'stub-decline' ).addEventListener( 'click', function () {
        window.wp_set_consent( 'marketing', 'deny' );
    } );
    </script>
    <?php
}, 99);
