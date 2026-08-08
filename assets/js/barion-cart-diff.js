/**
 * Barion Pixel cart diff.
 *
 * WooCommerce's wc-blocks_added_to_cart event carries only { preserveCartData },
 * so what was added has to be derived by comparing cart snapshots taken from the
 * wc/store/cart data store.
 *
 * Loaded as a plain script in the browser (declares one global) and required
 * directly by tests/cart-diff.test.js in Node.
 */
function wcBarionDiffCartItems( previousItems, nextItems ) {
	var previous = previousItems || [];
	var next = nextItems || [];
	var quantityByKey = {};
	var added = [];
	var i;

	for ( i = 0; i < previous.length; i++ ) {
		quantityByKey[ previous[ i ].key ] = previous[ i ].quantity;
	}

	for ( i = 0; i < next.length; i++ ) {
		var line = next[ i ];
		var before = quantityByKey[ line.key ] || 0;
		var delta = line.quantity - before;

		if ( delta > 0 ) {
			added.push( {
				id: String( line.id ),
				name: line.name,
				quantity: delta,
				unitPrice: wcBarionUnitPrice( line.prices )
			} );
		}
	}

	return added;
}

/**
 * Store API money values are minor-unit integers in a string. The exponent is
 * per store: HUF is 0, EUR is 2. Assuming 2 would report every HUF price at a
 * hundredth of its real value.
 */
function wcBarionUnitPrice( prices ) {
	if ( ! prices || typeof prices.price === 'undefined' ) {
		return 0;
	}

	var exponent = typeof prices.currency_minor_unit === 'number' ? prices.currency_minor_unit : 2;

	return parseInt( prices.price, 10 ) / Math.pow( 10, exponent );
}

if ( typeof module !== 'undefined' && module.exports ) {
	module.exports = wcBarionDiffCartItems;
}
