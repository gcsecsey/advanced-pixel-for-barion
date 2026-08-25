<?php
/**
 * Plugin Name: Barion test stub consent managers
 *
 * Emulates the CMPs the plugin adapts to. CookieYes and Cookiebot need paid
 * accounts and a remote CDN script, so they cannot be installed in Playground;
 * these stubs reproduce the globals and events their real scripts expose.
 *
 * Query args: ?cmp=cookieyes|cookiebot|complianz|wpca|cli|none
 *             &late=1   define the CMP 600ms after DOMContentLoaded
 *             &prior=1  visitor already accepted before this page load
 */
// The stub scenarios cover sites WITHOUT the WP Consent API plugin, which is
// the reported failure. Left enqueued it would answer first, and on a site with
// no consent type configured wp_has_consent() returns true, so the page would
// grant consent before the visitor touched the banner.
// wp_print_scripts fires for the head and the footer, just before either is
// printed, so this also catches a dependant enqueued after wp_enqueue_scripts.
add_action('wp_print_scripts', function () {
    if (empty($_GET['cmp'])) {
        return;
    }
    foreach (array('wp-consent-api', 'wp-consent-api-integration') as $handle) {
        wp_dequeue_script($handle);
        wp_deregister_script($handle);
    }
}, 1);

add_action('wp_footer', function () {
    $cmp   = isset($_GET['cmp']) ? sanitize_key($_GET['cmp']) : 'none';
    $late  = !empty($_GET['late']);
    $prior = !empty($_GET['prior']);
    if ('none' === $cmp) {
        return;
    }
    ?>
    <div id="stub-banner" style="position:fixed;bottom:0;left:0;right:0;background:#222;color:#fff;padding:16px;z-index:9999">
        Stub CMP: <?php echo esc_html($cmp); ?>
        <button id="stub-accept">Accept</button>
        <button id="stub-decline">Decline</button>
    </div>
    <script>
    ( function () {
        var cmp = <?php echo wp_json_encode($cmp); ?>;
        var prior = <?php echo $prior ? 'true' : 'false'; ?>;
        var late = <?php echo $late ? 'true' : 'false'; ?>;
        var state = prior;

        var managers = {
            cookieyes: {
                define: function () {
                    window.getCkyConsent = function () {
                        return { categories: { necessary: true, advertisement: state } };
                    };
                },
                fire: function () {
                    document.dispatchEvent( new CustomEvent( 'cookieyes_consent_update', {
                        detail: { accepted: state ? [ 'advertisement' ] : [] }
                    } ) );
                }
            },
            cookiebot: {
                define: function () {
                    window.Cookiebot = { consent: { marketing: state } };
                },
                fire: function () {
                    window.dispatchEvent( new Event( state ? 'CookiebotOnAccept' : 'CookiebotOnDecline' ) );
                }
            },
            complianz: {
                define: function () {
                    window.cmplz_has_consent = function ( category ) {
                        return 'marketing' === category ? state : true;
                    };
                },
                fire: function () {
                    document.dispatchEvent( new CustomEvent( 'cmplz_status_change', {
                        detail: { category: 'marketing' }
                    } ) );
                }
            },
            wpca: {
                define: function () {
                    window.wp_has_consent = function ( category ) {
                        return 'marketing' === category ? state : true;
                    };
                },
                fire: function () {
                    document.dispatchEvent( new CustomEvent( 'wp_listen_for_consent_change', {
                        detail: { marketing: state ? 'allow' : 'deny' }
                    } ) );
                }
            },
            cli: {
                define: function () {
                    window.CLI = { allowedCategories: [] };
                    if ( prior ) {
                        document.cookie = 'cookielawinfo-checkbox-non-necessary=yes;path=/';
                    }
                },
                // The legacy banner raises no event; it writes the cookie and
                // the adapter re-reads it after its own click delegation.
                fire: function () {
                    document.cookie = 'cookielawinfo-checkbox-non-necessary=' +
                        ( state ? 'yes' : 'no' ) + ';path=/';
                }
            }
        };

        var manager = managers[ cmp ];
        function install() {
            manager.define();
            window.__stubReady = true;
        }

        if ( late ) {
            window.addEventListener( 'DOMContentLoaded', function () {
                window.setTimeout( install, 600 );
            } );
        } else {
            install();
        }

        function answer( granted ) {
            state = granted;
            manager.define();
            manager.fire();
        }
        document.getElementById( 'stub-accept' ).addEventListener( 'click', function () { answer( true ); } );
        document.getElementById( 'stub-decline' ).addEventListener( 'click', function () { answer( false ); } );
    } )();
    </script>
    <?php
}, 99);
