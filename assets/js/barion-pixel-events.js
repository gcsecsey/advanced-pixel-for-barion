/**
 * Barion Pixel Events - E-commerce event tracking and addToCart.
 *
 * Expects abpwEvents to be set via wp_localize_script with:
 *   - currency      (string)
 *   - debug         (boolean)
 *   - events        (array)  - Each: { name: string, data: object }
 *   - singleProduct (object|null) - { id, name, price } for single product pages
 *   - email         (string|null) - billing email for setEncryptedEmail
 */
(function () {
	var config = window.abpwEvents || {};
	var currency = config.currency || 'HUF';
	var debug = !!config.debug;
	var events = config.events || [];
	var singleProduct = config.singleProduct || null;
	var email = config.email || null;

	// Fire queued events (contentView, initiateCheckout, purchase)
	for (var i = 0; i < events.length; i++) {
		if (typeof bp !== 'undefined') {
			bp('track', events[i].name, events[i].data);
			if (debug) {
				console.log('[Barion Pixel] Event: ' + events[i].name, events[i].data);
			}
		}
	}

	// setEncryptedEmail
	if (email && typeof bp !== 'undefined') {
		bp('identify', 'setEncryptedEmail', email);
		if (debug) {
			console.log('[Barion Pixel] setEncryptedEmail sent');
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
})();
