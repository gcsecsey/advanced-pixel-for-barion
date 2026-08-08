const test = require( 'node:test' );
const assert = require( 'node:assert' );
const diff = require( '../assets/js/barion-cart-diff.js' );

// prices.price is a string of minor units; currency_minor_unit is the exponent.
const item = ( key, id, name, quantity, price, minorUnit = 2 ) => ( {
	key,
	id,
	name,
	quantity,
	prices: { price: String( price ), currency_minor_unit: minorUnit },
} );

test( 'reports a brand new line item at its full quantity', () => {
	const added = diff( [], [ item( 'k1', 10, 'Beanie', 2, 1500 ) ] );
	assert.deepStrictEqual( added, [
		{ id: '10', name: 'Beanie', quantity: 2, unitPrice: 15 },
	] );
} );

test( 'reports only the increase when a quantity goes up', () => {
	const before = [ item( 'k1', 10, 'Beanie', 1, 1500 ) ];
	const after = [ item( 'k1', 10, 'Beanie', 3, 1500 ) ];
	assert.deepStrictEqual( diff( before, after ), [
		{ id: '10', name: 'Beanie', quantity: 2, unitPrice: 15 },
	] );
} );

test( 'reports nothing when a line item is removed', () => {
	const before = [ item( 'k1', 10, 'Beanie', 1, 1500 ) ];
	assert.deepStrictEqual( diff( before, [] ), [] );
} );

test( 'reports nothing when a quantity goes down', () => {
	const before = [ item( 'k1', 10, 'Beanie', 3, 1500 ) ];
	const after = [ item( 'k1', 10, 'Beanie', 1, 1500 ) ];
	assert.deepStrictEqual( diff( before, after ), [] );
} );

test( 'treats two keys sharing one product id as separate lines', () => {
	const before = [ item( 'k1', 10, 'Tee, blue', 1, 1000 ) ];
	const after = [
		item( 'k1', 10, 'Tee, blue', 1, 1000 ),
		item( 'k2', 10, 'Tee, red', 1, 1000 ),
	];
	assert.deepStrictEqual( diff( before, after ), [
		{ id: '10', name: 'Tee, red', quantity: 1, unitPrice: 10 },
	] );
} );

test( 'does not divide a zero-exponent currency such as HUF', () => {
	const added = diff( [], [ item( 'k1', 10, 'Bögre', 1, 4990, 0 ) ] );
	assert.strictEqual( added[ 0 ].unitPrice, 4990 );
} );

test( 'treats a missing previous list as empty', () => {
	const added = diff( undefined, [ item( 'k1', 10, 'Beanie', 1, 1500 ) ] );
	assert.strictEqual( added.length, 1 );
} );
