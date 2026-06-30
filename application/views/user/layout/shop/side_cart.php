<!-- Search Sidebar -->
		<div class="mn-side-search-overlay"></div>
		

		<!-- Cart sidebar Start -->
		<div class="mn-side-cart-overlay"></div>
		<div id="mn-side-cart" class="mn-side-cart">
			<div class="mn-cart-inner">
				<div class="mn-cart-top">
					<div class="mn-cart-title">
						<span class="cart_title">My Cart</span>
						<a href="javascript:void(0)" class="mn-cart-close">
							<i class="ri-close-line"></i>
						</a>
					</div>
					<ul class="mn-cart-pro-items">
						<li class="cart-sidebar-list">
							<a href="product-detail.html" class="mn-pro-img"><img  src="<?php echo base_url(); ?>assets/shop/img/product/11.jpg"
									alt="product"></a>
							<div class="mn-pro-content">
								<a href="product-detail.html" class="cart-pro-title">Smart watch</a>
								<span class="cart-price"><span>$255.00</span> x 1</span>
								<div class="qty-plus-minus">
									<input class="qty-input" type="text" name="mn-qtybtn" value="1">
								</div>
								<a href="javascript:void(0)" class="cart-remove-item">×</a>
							</div>
						</li>
					</ul>
				</div>
				<div class="mn-cart-bottom">
					<div class="cart-sub-total">
						<table class="table cart-table">
							<tbody>
								<tr>
									<td class="text-left">Sub-Total :</td>
									<td class="text-right">$417.00</td>
								</tr>
								<tr>
									<td class="text-left">VAT (20%) :</td>
									<td class="text-right">$83.40</td>
								</tr>
								<tr>
									<td class="text-left">Total :</td>
									<td class="text-right primary-color">$500.40</td>
								</tr>
							</tbody>
						</table>
					</div>
					<div class="cart_btn">
						<a href="<?php echo base_url();?>user/shop/get_cart_page" class="mn-btn-1"><span>Cart<i class="ri-arrow-right-s-line"></i></span></a>
						<a href="<?php echo base_url();?>user/shop/get_checkout_page" class="mn-btn-2"><span>Checkout<i
									class="ri-arrow-right-s-line"></i></span></a>
					</div>
				</div>
			</div>
		</div>

		<!-- Wishlist sidebar Start -->
	<div class="mn-side-wishlist-overlay"></div>
		<div id="mn-side-wishlist" class="mn-side-wishlist">
			<div class="mn-wishlist-inner">
				<div class="mn-wishlist-top">
					<div class="mn-wishlist-title">
						<span class="wishlist_title">My Wishlist</span>
						<a href="javascript:void(0)" class="mn-wishlist-close">
							<i class="ri-close-line"></i>
						</a>
					</div>
					<ul class="mn-wishlist-pro-items">
					</ul>
				</div>
				<div class="mn-wishlist-bottom">
					<div class="wishlist_btn" style="display:none;">
						<a href="wishlist.html" class="mn-btn-1"><span>View Wishlist<i
									class="ri-arrow-right-s-line"></i></span></a>
					</div>
				</div>
			</div>
		</div>