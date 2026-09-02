<?php
/**
 * Every scenario the harness pages drive.
 *
 * Kept apart from the runner because two consumers need them: the runner, which
 * asserts, and the index page, which links each one for a human to drive by
 * hand in a Playground preview.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Keys bp.js accepts on a flat event such as contentView. A contents item is
 * this list plus totalItemPrice, which bp.js rejects on one and demands on the
 * other — see docs/testing-notes.md.
 */
const BARION_HARNESS_FLAT_KEYS = array( 'contentType', 'currency', 'id', 'name', 'quantity', 'unit', 'unitPrice' );


/**
 * The harness pages. One registry so a new page is one edit, not four.
 *
 * @return array Mode => title and the function returning its scenarios.
 */
function barion_harness_pages() {
	return array(
		'1'      => array(
			'title'     => 'Consent — stub consent managers',
			'scenarios' => 'barion_harness_consent_scenarios',
		),
		'real'   => array(
			'title'     => 'Consent — real wp-consent-api plugin',
			'scenarios' => 'barion_harness_real_scenarios',
		),
		'events' => array(
			'title'     => 'E-commerce payloads',
			'scenarios' => 'barion_harness_event_scenarios',
		),
	);
}

/**
 * A stub consent manager scenario.
 *
 * @param string $name   What it proves.
 * @param string $query  Query args selecting the stub and its state.
 * @param string $click  accept, decline, accept2, both, or null for no click.
 * @param array  $expect Consent calls the page should make, in order.
 * @param string $cookie  wp_consent_marketing value to set first, or null.
 * @return array
 */
function barion_harness_consent( $name, $query, $click, $expect, $cookie = null ) {
	return array(
		'name'   => $name,
		'kind'   => 'consent',
		'url'    => add_query_arg( wp_parse_args( $query ), home_url( '/' ) ),
		// A late CMP defines its globals 600ms after DOMContentLoaded, so the
		// banner is not there to click yet.
		'late'   => false !== strpos( $query, 'late=1' ),
		'click'  => $click,
		'cookie' => $cookie,
		'expect' => $expect,
	);
}

/**
 * Consent scenarios against the stub consent managers.
 *
 * A scenario with click null expects nothing throughout: consent that already
 * stood when the page loaded is a decision from an earlier visit, and Barion
 * rejects an integration that replays it instead of sending grantConsent on the
 * click.
 *
 * @return array
 */
function barion_harness_consent_scenarios() {
	$grant  = array( 'grantConsent' );
	$reject = array( 'rejectConsent' );

	return array(
		barion_harness_consent( 'CookieYes - accept', 'cmp=cookieyes', 'accept', $grant ),
		barion_harness_consent( 'CookieYes - decline', 'cmp=cookieyes', 'decline', $reject ),
		barion_harness_consent( 'CookieYes - no answer yet', 'cmp=cookieyes', null, array() ),
		barion_harness_consent( 'CookieYes - returning, accepted', 'cmp=cookieyes&prior=1', null, array() ),
		barion_harness_consent( 'CookieYes - loads late, then accept', 'cmp=cookieyes&late=1', 'accept', $grant ),
		barion_harness_consent( 'CookieYes - accept twice', 'cmp=cookieyes', 'accept2', $grant ),
		barion_harness_consent( 'CookieYes - accept then decline', 'cmp=cookieyes', 'both', array( 'grantConsent', 'rejectConsent' ) ),
		barion_harness_consent( 'Cookiebot - accept', 'cmp=cookiebot', 'accept', $grant ),
		barion_harness_consent( 'Cookiebot - decline', 'cmp=cookiebot', 'decline', $reject ),
		barion_harness_consent( 'Complianz - accept', 'cmp=complianz', 'accept', $grant ),
		barion_harness_consent( 'Complianz - decline', 'cmp=complianz', 'decline', $reject ),
		barion_harness_consent( 'WP Consent API - accept', 'cmp=wpca', 'accept', $grant ),
		barion_harness_consent( 'WP Consent API - loads late, accept', 'cmp=wpca&late=1', 'accept', $grant ),
		barion_harness_consent( 'Cookie Law Info legacy - accept', 'cmp=cli', 'accept', $grant ),
		barion_harness_consent( 'Cookie Law Info legacy - decline', 'cmp=cli', 'decline', $reject ),
		barion_harness_consent( 'No consent manager', 'cmp=none', null, array() ),
		barion_harness_consent( 'Late CMP, returning visitor, no click', 'cmp=cookieyes&late=1&prior=1', null, array() ),
	);
}

/**
 * Consent scenarios against the real wp-consent-api plugin.
 *
 * @return array
 */
function barion_harness_real_scenarios() {
	$optin  = 'real=1&ctype=optin';
	$none   = 'real=1&ctype=none';
	$grant  = array( 'grantConsent' );
	$reject = array( 'rejectConsent' );

	return array(
		barion_harness_consent( 'Real WPCA optin - accept', $optin, 'accept', $grant ),
		barion_harness_consent( 'Real WPCA optin - decline', $optin, 'decline', $reject ),
		barion_harness_consent( 'Real WPCA optin - no answer yet', $optin, null, array() ),
		barion_harness_consent( 'Real WPCA optin - returning, allowed', $optin, null, array(), 'allow' ),
		barion_harness_consent( 'Real WPCA optin - returning, denied', $optin, null, array(), 'deny' ),
		barion_harness_consent( 'Real WPCA optin - deny then allow', $optin, 'accept', $grant, 'deny' ),
		barion_harness_consent( 'Real WPCA, no consent type set', $none, null, array() ),
		barion_harness_consent( 'Real WPCA, no consent type set, clicked', $none, 'accept', array() ),
	);
}

/**
 * E-commerce event scenarios.
 *
 * @return array
 */
function barion_harness_event_scenarios() {
	if ( ! barion_harness_store_ready() ) {
		return array();
	}

	barion_harness_setup_store();

	$product_id = barion_harness_product_id();
	$product    = get_permalink( $product_id );

	$prices     = array( 'unitPrice', 'totalItemPrice' );
	$item_keys  = array_merge( BARION_HARNESS_FLAT_KEYS, array( 'totalItemPrice' ) );

	$flat_item     = array( 'require' => BARION_HARNESS_FLAT_KEYS, 'forbid' => array( 'totalItemPrice' ), 'positive' => array( 'unitPrice' ) );
	$cart_item     = array( 'require' => array_merge( $item_keys, array( 'step' ) ), 'positive' => $prices );
	$basket_totals = array( 'require' => array( 'contents', 'currency', 'revenue', 'step' ), 'positive' => array( 'revenue' ) );
	$basket_item   = array( 'require' => $item_keys, 'positive' => $prices );

	return array(
		array(
			'name'   => 'Product page sends contentView without totalItemPrice',
			'kind'   => 'event',
			'url'    => $product,
			'expect' => array( 'contentView' ),
			'keys'   => array( 'contentView' => $flat_item ),
		),
		array(
			'name'   => 'Single product form sends addToCart',
			'kind'   => 'event',
			'url'    => $product,
			// A dispatched submit runs the plugin's listener without navigating,
			// so the recorded calls survive to be read. A real submit would
			// replace the document and take window.__bpCalls with it.
			'act'    => 'submit-form',
			'expect' => array( 'contentView', 'addToCart' ),
			'keys'   => array( 'contentView' => $flat_item, 'addToCart' => $cart_item ),
		),
		array(
			'name'   => 'Shop archive AJAX button sends addToCart',
			'kind'   => 'event',
			'url'    => get_permalink( wc_get_page_id( 'shop' ) ),
			'act'    => 'ajax-add',
			'expect' => array( 'addToCart' ),
			'keys'   => array( 'addToCart' => $cart_item ),
		),
		array(
			'name'     => 'Checkout page sends initiateCheckout',
			'kind'     => 'event',
			'prime'    => array( add_query_arg( 'add-to-cart', $product_id, $product ) ),
			'url'      => wc_get_checkout_url(),
			'expect'   => array( 'initiateCheckout' ),
			'keys'     => array( 'initiateCheckout' => $basket_totals ),
			'contents' => array( 'initiateCheckout' => $basket_item ),
		),
		array(
			'name'     => 'Thank you page sends purchase and setEncryptedEmail',
			'kind'     => 'event',
			// The order is made when this link is opened, not when the list is
			// built: track_purchase() fires once per order, and the index page
			// builds this list too.
			'url'      => add_query_arg( 'barion-order', 'new', home_url( '/' ) ),
			'expect'   => array( 'purchase' ),
			'identity' => true,
			'keys'     => array( 'purchase' => $basket_totals ),
			'contents' => array( 'purchase' => $basket_item ),
		),
	);
}
