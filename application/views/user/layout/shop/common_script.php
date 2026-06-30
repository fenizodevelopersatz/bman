<!-- Vendor Custom -->
<script src="<?php echo base_url(); ?>assets/shop/js/vendor/jquery-3.7.1.min.js"></script>
<script src="<?php echo base_url(); ?>assets/shop/js/vendor/bootstrap.bundle.min.js"></script>
<script src="<?php echo base_url(); ?>assets/shop/js/vendor/owl.carousel.min.js"></script>
<script src="<?php echo base_url(); ?>assets/shop/js/vendor/slick.min.js"></script>
<script src="<?php echo base_url(); ?>assets/shop/js/vendor/swiper-bundle.min.js"></script>
<script src="<?php echo base_url(); ?>assets/shop/js/vendor/countdownTimer.js"></script>
<script src="<?php echo base_url(); ?>assets/shop/js/vendor/infiniteslidev2.js"></script>
<script src="<?php echo base_url(); ?>assets/shop/js/vendor/nouislider.js"></script>
<script src="<?php echo base_url(); ?>assets/shop/js/vendor/smoothscroll.min.js"></script>
<script src="<?php echo base_url(); ?>assets/shop/js/vendor/jquery.zoom.min.js"></script>

<!-- Main Custom -->
<script src="<?php echo base_url(); ?>assets/shop/js/main.js"></script>

<script>


	$(document).ready(function () {

		const base_url = '<?php echo base_url(); ?>'

		function Wishlistcount() {
			$.ajax({
				url: base_url + "user/shop/wishlist-count",
				type: "GET",
				dataType: "json",
				success: function (res) {
					$(".mn-main-wishlist .lbl-1").text(res.count);
				},
				error: function () {
					$(".mn-main-wishlist .lbl-1").text("0");
				}
			});
		}

		$(document).on('click', '.add-to-wishlist', function () {
			const productId = $(this).data('product');
			const icon = $(this).find('i');
			icon.toggleClass('active-heart');

			$.post('<?= base_url("user/shop/add_to_wishlist") ?>', { product_id: productId }, function (res) {
				const response = JSON.parse(res);
				loadWishlistSidebar();
				showToast('Added to Wishlist');
			});
		});

		$(document).on('click', '.add-to-cart', function () {
			const btn = $(this);
			const productId = btn.data('product');
			const quantity = btn.closest('.mn-single-qty').find('.qty-input').val();

			const icon = $(this).find('i');
			icon.addClass('active-cart');

			$.post('<?= base_url("user/shop/add_to_cart") ?>', {
				product_id: productId,
				quantity: quantity
			}, function (res) {
				const response = JSON.parse(res);
				updateCartCount();
				loadCartItems();
				showToast('Added to Cart');
			});
		});



		$(document).on('click', '.wishlist-remove-item', function () {
			let id = $(this).data('id');
			$.post(base_url + 'user/shop/remove-wishlist-item', { id: id }, function (res) {
				loadWishlistSidebar();
				showToast('Removed from Wishlist', 'error');
			});
		});

		$(document).on('click', '.cart-remove-item', function () {
			const cart_id = $(this).data('id');
			$.ajax({
				url: '<?= base_url("user/shop/remove_item") ?>',
				method: 'POST',
				data: { cart_id },
				success: function () {
					updateCartCount();
					loadCartItems();
					showToast('Removed from Card', 'error');
				}
			});
		});


		function loadWishlistSidebar() {
			$.ajax({
				url: base_url + 'user/shop/ajax-get-wishlist',
				type: 'GET',
				dataType: 'json',
				success: function (res) {
					if (res.status) {
						$('.mn-wishlist-pro-items').html(res.html);
					} else {
						$('.mn-wishlist-pro-items').html('<li>No items in wishlist</li>');
					}
				},
				error: function () {
					$('.mn-wishlist-pro-items').html('<li>Error loading wishlist</li>');
				}
			});
			Wishlistcount();
		}

		function loadCartItems() {
			$.ajax({
				url: '<?= base_url("user/shop/get_cart_items") ?>',
				type: 'GET',
				dataType: 'json',
				success: function (response) {
					if (response.status === 'success') {
						let cartHtml = '';
						response.items.forEach(item => {
							cartHtml += `
							<li class="cart-sidebar-list">
								<a href="product-detail.html" class="mn-pro-img">
									<img src="<?= base_url(); ?>assets/images/${item.product_image}" alt="product">
								</a>
								<div class="mn-pro-content">
									<a href="product-detail.html" class="cart-pro-title">${item.name}</a>
									<span class="cart-price"><span>$${item.final_price.toFixed(2)}</span> x ${item.quantity}</span>
									<div class="qty-plus-minus">
										<input class="qty-input" type="text" value="${item.quantity}" readonly>
									</div>
									<a href="javascript:void(0)" class="cart-remove-item" data-id="${item.cart_id}">×</a>
								</div>
							</li>`;
						});

						$('.mn-cart-pro-items').html(cartHtml);
						$('.cart-table').html(`
						<tr><td class="text-left">Sub-Total :</td><td class="text-right">$${response.summary.subtotal.toFixed(2)}</td></tr>
						<tr><td class="text-left">VAT (20%) :</td><td class="text-right">$${response.summary.vat.toFixed(2)}</td></tr>
						<tr><td class="text-left">Total :</td><td class="text-right primary-color">$${response.summary.total.toFixed(2)}</td></tr>
					`);
					} else {
						$('.mn-cart-pro-items').html('<li>No items in cart</li>');
					}
				}
			});
		}

		function updateCartCount() {
			$.ajax({
				url: '<?= base_url("user/shop/get_cart_count") ?>',
				method: 'GET',
				dataType: 'json',
				success: function (response) {
					if (response.status === 'success') {
						$('.mn-main-cart .label.lbl-2').text(response.count);
					} else {
						$('.mn-main-cart .label.lbl-2').text(0);
					}
				}
			});
		}


		loadCartItems();
		updateCartCount();
		loadWishlistSidebar();



		$('.mn-wishlist-toggle').on("click", function (e) {
			e.preventDefault();
			$(".mn-side-wishlist-overlay").fadeIn();
			$('.mn-side-wishlist').addClass("mn-open-wishlist");
		});
		$('.mn-side-wishlist-overlay, .mn-wishlist-close').on("click", function (e) {
			e.preventDefault();
			$(".mn-side-wishlist-overlay").fadeOut();
			$('.mn-side-wishlist').removeClass("mn-open-wishlist");
		});
		$(".wishlist-remove-item").on("click", function (e) {
			$(this).parents(".wishlist-sidebar-list").remove();
			var wishlist_product_count = $(".wishlist-sidebar-list").length;
			if (wishlist_product_count == 0) {
				$('.mn-wishlist-pro-items').html('<p class="mn-wishlist-msg">Your Wishlist is empty!</p>');
			}
		});

		$('.mn-wishlist').on("click", function () {
			if ($(this).hasClass("active")) {
				$(this).removeClass("active");
			} else {
				$(this).addClass("active");
			}


		});

		function showToast(message, type = 'success') {
			const toastEl = $('#toastNotify');
			const iconEl = toastEl.find('.toast-icon i');
			const toastBody = $('#toast-message');

			toastEl.removeClass('bg-success bg-danger bg-warning bg-info text-white text-dark');
			iconEl.removeClass().addClass('fa-solid fs-5');

			switch (type) {
				case 'success':
					toastEl.addClass('bg-success text-white');
					iconEl.addClass('fa-circle-check text-white');
					break;
				case 'error':
					toastEl.addClass('bg-danger text-white');
					iconEl.addClass('fa-circle-xmark text-white');
					break;
				case 'warning':
					toastEl.addClass('bg-warning text-dark');
					iconEl.addClass('fa-triangle-exclamation text-dark');
					break;
				case 'info':
					toastEl.addClass('bg-info text-dark');
					iconEl.addClass('fa-circle-info text-dark');
					break;
			}

			toastBody.text(message);
			const toast = new bootstrap.Toast(toastEl[0]);
			toast.show();
		}



	});



	function updateCartItem(productId, quantity) {
		$.post("<?= base_url('user/shop/update_cart_qty') ?>", {
			product_id: productId,
			quantity: quantity
		}, function (res) {
			location.reload();
		});
	}

	function removeCartItem(productId) {
		$.post("<?= base_url('user/shop/remove_from_cart') ?>", {
			product_id: productId
		}, function (res) {
			location.reload();
		});
	}

	$(document).on('click', '.qty-btn', function () {
		const row = $(this).closest('.mn-cart-product');
		const input = row.find('.cart-plus-minus');
		let qty = parseInt(input.val()) || 1;

		if ($(this).hasClass('plus')) qty++;
		if ($(this).hasClass('minus') && qty > 1) qty--;

		input.val(qty);
		updateCartItem(row.data('product-id'), qty);
	});

	$(document).on('click', '.cart-remove', function () {
		const productId = $(this).closest('.mn-cart-product').data('product-id');
		removeCartItem(productId);
	});


</script>