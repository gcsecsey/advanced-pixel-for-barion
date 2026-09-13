<?php
/**
 * The shop the event scenarios run against.
 *
 * Built in PHP on first use rather than as blueprint steps, so a local mount
 * and a Playground preview produce the same shop and no step can run in the
 * wrong order.
 *
 * Cart and checkout are forced back to the classic shortcodes. WooCommerce now
 * creates them with the Cart and Checkout blocks, and the plugin's server-side
 * events hang off classic template hooks — woocommerce_after_single_product and
 * woocommerce_thankyou. The block templates do not fire them dependably across
 * releases, so the classic pages are what makes these tests deterministic.
 * The block surfaces stay uncovered; that is a separate pass.
 */

defined( 'ABSPATH' ) || exit;

/**
 * True once WooCommerce is loaded far enough to build the fixture.
 */
function barion_harness_store_ready() {
	return class_exists( 'WooCommerce' ) && function_exists( 'wc_create_order' );
}

/**
 * One-time shop configuration.
 */
function barion_harness_setup_store() {
	if ( get_option( 'barion_harness_fixture' ) ) {
		return;
	}

	update_option( 'woocommerce_currency', 'EUR' );
	update_option( 'woocommerce_calc_taxes', 'no' );
	update_option( 'woocommerce_enable_ajax_add_to_cart', 'yes' );
	// Some payment method must be available or the checkout page renders an
	// error notice instead of the form, and initiateCheckout never queues.
	update_option(
		'woocommerce_cod_settings',
		array(
			'enabled'     => 'yes',
			'title'       => 'Cash on delivery',
			'description' => 'Pay on delivery.',
		)
	);

	foreach ( array(
		'cart'     => '<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->',
		'checkout' => '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->',
	) as $page => $content ) {
		$page_id = wc_get_page_id( $page );
		if ( $page_id > 0 ) {
			wp_update_post(
				array(
					'ID'           => $page_id,
					'post_content' => $content,
				)
			);
		}
	}

	update_option( 'barion_harness_fixture', 1 );
}

/**
 * The product every event scenario uses. Created once and reused.
 *
 * @return int Product ID, or 0 if WooCommerce is not ready.
 */
function barion_harness_product_id() {
	if ( ! barion_harness_store_ready() ) {
		return 0;
	}

	$id = (int) get_option( 'barion_harness_product', 0 );
	if ( $id && 'product' === get_post_type( $id ) ) {
		return $id;
	}

	$product = new WC_Product_Simple();
	$product->set_name( 'Harness Test Product' );
	$product->set_regular_price( '12.50' );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_virtual( true );
	$id = (int) $product->save();

	update_option( 'barion_harness_product', $id );

	return $id;
}

/**
 * A paid order, made fresh on every call: track_purchase() marks an order with
 * _wc_barion_tracked and skips it afterwards.
 *
 * @return WC_Order|null
 */
function barion_harness_new_order() {
	if ( ! barion_harness_store_ready() ) {
		return null;
	}

	$product = wc_get_product( barion_harness_product_id() );
	if ( ! $product ) {
		return null;
	}

	$order = wc_create_order();
	$order->add_product( $product, 2 );
	$order->set_billing_email( 'harness@example.com' );
	$order->set_billing_first_name( 'Harness' );
	$order->set_billing_last_name( 'Tester' );
	$order->calculate_totals();
	$order->set_status( 'processing' );
	$order->save();

	return $order;
}

/**
 * Two endpoints the scenarios drive.
 *
 * ?barion-cart=empty  empties the cart, so one scenario cannot decide the next.
 *                     The cart lives in a WooCommerce session keyed by a cookie,
 *                     so it has to be cleared server-side.
 * ?barion-order=new   makes an order and redirects to its thank-you page. A
 *                     redirect rather than a prebuilt link because building the
 *                     scenario list must stay free: the index page builds it
 *                     too, and every order costs a write.
 */
add_action(
	'wp_loaded',
	function () {
		if ( ! empty( $_GET['barion-cart'] ) && 'empty' === $_GET['barion-cart'] ) {
			if ( barion_harness_store_ready() && WC()->cart ) {
				WC()->cart->empty_cart();
			}
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo 'cart emptied';
			exit;
		}

		if ( empty( $_GET['barion-order'] ) || 'new' !== $_GET['barion-order'] ) {
			return;
		}

		$order = barion_harness_new_order();
		if ( ! $order ) {
			wp_die( 'WooCommerce is not ready, so no order could be made.' );
		}

		$url = $order->get_checkout_order_received_url();
		if ( ! empty( $_GET['barion-panel'] ) ) {
			$url = add_query_arg( 'barion-panel', 1, $url );
		}
		wp_safe_redirect( $url );
		exit;
	},
	20
);
