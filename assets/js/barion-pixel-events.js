/**
 * Barion Pixel Events - E-commerce event tracking and addToCart.
 *
 * Expects wcBarionPixelEvents to be set via wp_localize_script with:
 *   - currency       (string)
 *   - debug          (boolean)
 *   - events         (array)  - Each: { name: string, data: object }
 *   - singleProduct  (object|null) - { id, name, price } for single product pages
 *   - email          (string|null) - billing email for setEncryptedEmail (thank-you page)
 *   - isCheckout     (boolean) - true on the checkout page (not order-received)
 *   - loggedInEmail  (string)  - logged-in user's email, for instant setEncryptedEmail on checkout
 */
(function () {
	var config = window.wcBarionPixelEvents || {};
	var currency = config.currency || 'HUF';
	var debug = !!config.debug;
	var events = config.events || [];
	var singleProduct = config.singleProduct || null;
	var email = config.email || null;
	var isCheckout = !!config.isCheckout;
	var loggedInEmail = config.loggedInEmail || '';

	var SHA1_HEX_RE = /^[a-f0-9]{40}$/;

	// bp.js validates plain emails with a very restrictive regex that rejects
	// common valid addresses (no `+` in the local part, TLDs limited to 2–4
	// letters). Pre-hashing the email with SHA-1 sidesteps that check — bp.js
	// accepts any 40-char hex string as-is.
	function sha1Hex(input) {
		if (!window.crypto || !window.crypto.subtle || typeof TextEncoder === 'undefined') {
			return Promise.reject(new Error('Web Crypto API not available'));
		}
		var bytes = new TextEncoder().encode(input);
		return window.crypto.subtle.digest('SHA-1', bytes).then(function (buffer) {
			var arr = new Uint8Array(buffer);
			var hex = '';
			for (var i = 0; i < arr.length; i++) {
				hex += ('00' + arr[i].toString(16)).slice(-2);
			}
			return hex;
		});
	}

	function sendToBp(value) {
		if (typeof bp === 'undefined') return;
		// Method name per https://docs.barion.com/Barion_Pixel_API_reference
		// ("bp('identity','setEncryptedEmail', '...')").
		bp('identity', 'setEncryptedEmail', value);
		if (debug) {
			console.log('[Barion Pixel] setEncryptedEmail sent');
		}
	}

	function sendEncryptedEmail(value) {
		if (typeof bp === 'undefined') return;
		if (SHA1_HEX_RE.test(value)) {
			sendToBp(value);
			return;
		}
		sha1Hex(value).then(sendToBp).catch(function () {
			// Web Crypto unavailable (e.g. non-HTTPS context). Fall back to the
			// plain email — bp.js will still accept it as long as it matches
			// the conservative format it requires.
			sendToBp(value);
		});
	}

	// Validate before sending so partial typing doesn't reach bp.js. We accept
	// any value bp.js would accept: a plain email (HTML5 spec regex,
	// https://html.spec.whatwg.org/multipage/input.html#valid-e-mail-address),
	// or a pre-computed SHA-1 hash.
	var EMAIL_RE = /^[a-z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/;
	var lastSentEmail = '';

	// Idempotent by design: a repeat value is a no-op. Both the classic change
	// listener and the block store subscription depend on that.
	function fireIfNew(raw) {
		var val = (raw || '').trim().toLowerCase();
		if (!EMAIL_RE.test(val) && !SHA1_HEX_RE.test(val)) return;
		if (val === lastSentEmail) return;
		lastSentEmail = val;
		sendEncryptedEmail(val);
	}

	// Fire queued events (contentView, initiateCheckout, purchase)
	for (var i = 0; i < events.length; i++) {
		if (typeof bp !== 'undefined') {
			bp('track', events[i].name, events[i].data);
			if (debug) {
				console.log('[Barion Pixel] Event: ' + events[i].name, events[i].data);
			}
		}
	}

	// setEncryptedEmail (from server-side context, e.g. thank-you page)
	if (email) {
		sendEncryptedEmail(email);
	}

	// --- setEncryptedEmail on checkout email entry ---
	// Per Barion docs, setEncryptedEmail must fire whenever the user provides their email
	// (during checkout), not only on the thank-you page.
	if (isCheckout) {
		// Only bind once per email field instance to avoid stacking listeners
		// on updated_checkout (which fires multiple times during normal use).
		function bindEmailField() {
			var field = document.querySelector('#billing_email');
			if (!field || field.dataset.wcBarionBound === '1') return;
			field.dataset.wcBarionBound = '1';
			field.addEventListener('change', function () {
				fireIfNew(field.value);
			});
		}

		function init() {
			// Logged-in user: fire once on load with their account email.
			if (loggedInEmail) {
				fireIfNew(loggedInEmail);
			}
			bindEmailField();
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', init);
		} else {
			init();
		}

		// WooCommerce may replace the email field on updated_checkout. The
		// data-attribute guard makes bindEmailField a no-op if the same field
		// is still mounted, and idempotently binds a fresh one if it's new.
		if (typeof jQuery !== 'undefined') {
			jQuery(document.body).on('updated_checkout', bindEmailField);
		}
	}

	// --- addToCart tracking ---

	function fireAddToCart(data) {
		if (typeof bp === 'undefined') return;
		bp('track', 'addToCart', data);
		if (debug) {
			console.log('[Barion Pixel] Event: addToCart', data);
		}
	}

	// AJAX add to cart (shop/archive pages)
	if (typeof jQuery !== 'undefined') {
		jQuery(document.body).on('added_to_cart', function (e, fragments, cartHash, $button) {
			if (!$button || !$button.length) return;
			var id = String($button.data('product_id') || '');
			var name =
				$button.data('product_name') ||
				$button.closest('.product').find('.woocommerce-loop-product__title').text() ||
				'';
			var price = parseFloat($button.data('product_price') || 0);
			var qty = parseInt($button.data('quantity') || 1, 10);

			fireAddToCart({
				contentType: 'Product',
				currency: currency,
				id: id,
				name: name,
				quantity: qty,
				unit: 'pcs',
				unitPrice: price,
				totalItemPrice: price * qty,
				step: 1,
			});
		});
	}

	// Single product page form submit
	if (singleProduct) {
		document.addEventListener('DOMContentLoaded', function () {
			var form = document.querySelector('form.cart');
			if (!form) return;
			form.addEventListener('submit', function () {
				var qtyInput = form.querySelector('input[name="quantity"]');
				var qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;

				var variationInput = form.querySelector('input[name="variation_id"]');
				var variationId = variationInput ? variationInput.value : '';
				var productData = {
					id: singleProduct.id,
					name: singleProduct.name,
					price: singleProduct.price,
				};

				if (variationId && typeof jQuery !== 'undefined') {
					var variationsForm = jQuery(form).data('product_variations');
					if (variationsForm) {
						for (var j = 0; j < variationsForm.length; j++) {
							if (String(variationsForm[j].variation_id) === String(variationId)) {
								productData.price =
									parseFloat(variationsForm[j].display_price) || productData.price;
								break;
							}
						}
					}
				}

				fireAddToCart({
					contentType: 'Product',
					currency: currency,
					id: productData.id,
					name: productData.name,
					quantity: qty,
					unit: 'pcs',
					unitPrice: productData.price,
					totalItemPrice: productData.price * qty,
					step: 1,
				});
			});
		});
	}

	// --- Block surfaces (product buttons, Cart block, Checkout block) ---
	// None of these run the classic PHP hooks or render #billing_email.
	//
	// Add to cart reads the Store API. The wc-blocks_added_to_cart event says the
	// cart changed but not what changed (its detail is only { preserveCartData }),
	// and product buttons run on the Interactivity API, which does not load the
	// wc/store/cart data store at all. The Store API is the one source available
	// on every block surface, and it returns the same item shape the data store
	// does, so wcBarionDiffCartItems works against either.
	//
	// The checkout email still comes from the data store, because it changes
	// keystroke by keystroke and polling an endpoint for that would be wasteful.
	var cartApiUrl = config.cartApiUrl || '';
	var knownItems = null;
	var baselineReady = null;
	var addInFlight = false;

	function fetchCartItems() {
		if (!cartApiUrl || typeof fetch !== 'function') {
			return Promise.reject(new Error('Store API unavailable'));
		}
		return fetch(cartApiUrl, {
			credentials: 'same-origin',
			headers: { Accept: 'application/json' },
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (cart) {
				return (cart && cart.items) || [];
			});
	}

	// Snapshot the cart before the customer can add anything, so the first add
	// has something to diff against.
	function ensureBaseline() {
		if (null !== knownItems) {
			return Promise.resolve();
		}
		if (!baselineReady) {
			baselineReady = fetchCartItems().then(
				function (items) {
					knownItems = items;
				},
				function () {
					knownItems = [];
				}
			);
		}
		return baselineReady;
	}

	function trackBlockAddToCart() {
		if (typeof wcBarionDiffCartItems !== 'function') {
			return;
		}
		addInFlight = true;
		ensureBaseline()
			.then(fetchCartItems)
			.then(function (items) {
				var added = wcBarionDiffCartItems(knownItems || [], items);
				knownItems = items;
				addInFlight = false;
				for (var i = 0; i < added.length; i++) {
					fireAddToCart({
						contentType: 'Product',
						currency: currency,
						id: added[i].id,
						name: added[i].name,
						quantity: added[i].quantity,
						unit: 'pcs',
						unitPrice: added[i].unitPrice,
						totalItemPrice: added[i].unitPrice * added[i].quantity,
						step: 1,
					});
				}
			})
			.catch(function () {
				addInFlight = false;
			});
	}

	function initBlockSurfaces() {
		var cartStore =
			window.wp && wp.data && typeof wp.data.select === 'function'
				? wp.data.select('wc/store/cart')
				: null;
		var hasCartStore = !!(cartStore && typeof cartStore.getCartData === 'function');
		var hasProductButtons = !!document.querySelector(
			'[data-wp-on--click="actions.addCartItem"], .wc-block-components-product-button__button'
		);

		if (!hasCartStore && !hasProductButtons) {
			return;
		}

		if (hasCartStore) {
			// Free baseline: no request needed where the store is already loaded.
			knownItems = (cartStore.getCartData() || {}).items || [];

			wp.data.subscribe(function () {
				var current = wp.data.select('wc/store/cart');
				if (!current) {
					return;
				}
				// Keep the baseline fresh across quantity edits in the Cart block,
				// but never while an add is being measured against it.
				if (!addInFlight) {
					knownItems = (current.getCartData() || {}).items || knownItems;
				}
				if (isCheckout && typeof current.getCustomerData === 'function') {
					var customer = current.getCustomerData() || {};
					var billing = customer.billingAddress || {};
					if (billing.email) {
						fireIfNew(billing.email);
					}
				}
			});
		} else {
			ensureBaseline();
		}

		// Quantity edits in the Cart block do not dispatch this, so they are
		// excluded without any extra gating.
		document.body.addEventListener('wc-blocks_added_to_cart', trackBlockAddToCart);

		if (debug) {
			console.log(
				'[Barion Pixel] Block surfaces detected (cart store: ' +
					hasCartStore +
					', product buttons: ' +
					hasProductButtons +
					')'
			);
		}
	}

	initBlockSurfaces();
})();
