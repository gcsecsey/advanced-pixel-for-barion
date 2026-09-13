<?php
/**
 * The base pixel snippet a Barion payment gateway prints.
 *
 * Copy of the snippet from pay-via-barion-for-woocommerce 3.10.2,
 * includes/class-wc-gateway-barion-pixel.php. It goes out from wp_head at
 * priority 999999 — after every script the plugin can enqueue — whenever its
 * Pixel ID field is filled, and only its own filter stops it. The real gateway
 * needs a live POS key to install, so the snippet and its guard are reproduced
 * here.
 *
 * Query args: ?gateway=1   put the gateway snippet on the page
 *             &nofilter=1  take the plugin's off switch away again, which shows
 *                          the snippet really does init a second pixel
 */

defined( 'ABSPATH' ) || exit;

// Priority 99, because the plugin adds that filter from its constructor on
// plugins_loaded at the default priority.
add_action(
	'plugins_loaded',
	function () {
		if ( ! empty( $_GET['nofilter'] ) ) {
			remove_filter( 'woocommerce_barion_disable_tracking', '__return_true' );
		}
	},
	99
);

add_action(
	'wp_head',
	function () {
		if ( empty( $_GET['gateway'] ) ) {
			return;
		}
		if ( apply_filters( 'woocommerce_barion_disable_tracking', false ) ) {
			return;
		}
		?>
	<script>
	window['barion_pixel_id'] = 'BP-0000000000-00';
	bp( 'init', 'addBarionPixelId', window['barion_pixel_id'] );
	</script>
		<?php
	},
	999999
);
