<!DOCTYPE html>
<html lang="en" dir="ltr">

<?php $this->load->view('user/layout/shop/common_style'); ?>
<style>
.mn-cat-card ul li img {
    width: 100px;         /* Adjust width */
    height: 100px;        /* Fixed height */
    object-fit: cover;    /* Ensures image fills space without distortion */
    border-radius: 8px;   /* Optional: rounded corners */
    box-shadow: 0 2px 8px rgba(0,0,0,0.1); /* Optional: slight shadow */
}
.mn-price-old {
    text-decoration: line-through;
    color: #999;
    margin-left: 10px;
    font-size: 14px;
}
.mn-blog-carousel .blog-img {
    width: 100%;
    height: 220px;
    overflow: hidden;
    border-radius: 8px;
}

.mn-blog-carousel .blog-img img {
     width: 100% !important;
    height: 220px !important;
    object-fit: cover;
    transition: all 0.3s ease;
}
.mn-blog-carousel .blog-img img:hover {
    transform: scale(1.05);
}
.mn-cart-content .table-content table tbody > tr td .cart-qty-plus-minus
{
	overflow:inherit !important;
}
</style>

<body data-mn-mode="light">

	<main class="wrapper sb-default">

		<!-- Header -->
		<?php $this->load->view('user/layout/shop/common_header'); ?>

		<!-- Main Content -->
	<div class="mn-main-content sb-hide">
			<div class="mn-breadcrumb m-b-30">
				<div class="row">
					<div class="col-12">
						<div class="row gi_breadcrumb_inner">
							<div class="col-md-6 col-sm-12">
								<h2 class="mn-breadcrumb-title">Cart Page</h2>
							</div>
							<div class="col-md-6 col-sm-12">
								<!-- mn-breadcrumb-list start -->
								<ul class="mn-breadcrumb-list">
									<li class="mn-breadcrumb-item"><a href="<?php echo base_url();?>">Home</a></li>
									<li class="mn-breadcrumb-item active">Cart Page</li>
								</ul>
								<!-- mn-breadcrumb-list end -->
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row sb-hide">
					<div class="mn-cart-leftside col-lg-8 col-md-12">
						<!-- cart content Start -->
						<div class="mn-cart-content">
							<div class="mn-cart-inner cart_list">
								<div class="row">
									<form action="#">
										<div class="table-content cart-table-content">
											<table>
												<thead>
													<tr>
														<th>Product</th>
														<th>Price</th>
														<th style="text-align: center;">Quantity</th>
														<th>Total</th>
														<th></th>
													</tr>
												</thead>
												<tbody>
												<tbody>
                                                        <?php if (!empty($cart_items)): ?>
                                                            <?php foreach ($cart_items as $item): ?>
                                                                <?php
                                                                    $price = ($item->offer_status && $item->offer_price > 0) ? $item->offer_price : $item->price;
                                                                    $subtotal = $price * $item->quantity;
                                                                ?>
                                                                <tr class="mn-cart-product" data-product-id="<?= $item->id ?>">
                                                                    <td class="mn-cart-pro-name">
                                                                        <a href="<?= base_url('product/' . $item->id) ?>">
                                                                            <img class="mn-cart-pro-img" src="<?= base_url('assets/images/' . $item->product_image) ?>" alt="">
                                                                            <?= htmlspecialchars($item->name) ?>
                                                                        </a>
                                                                    </td>
                                                                    <td class="mn-cart-pro-price">
                                                                        <span class="amount"><?php echo currency_info()->currency_symbol; ?><?= number_format($price, 2) ?></span>
                                                                    </td>
                                                                    <td class="mn-cart-pro-qty text-center">
                                                                        <div class="cart-qty-plus-minus d-flex justify-content-center align-items-center">
                                                                            <button type="button" class="btn qty-btn minus btn-danger">-</button>
                                                                            <input class="cart-plus-minus form-control text-center" type="text" value="<?= $item->quantity ?>" />
                                                                            <button type="button" class="qty-btn plus btn btn-success">+</button>
                                                                        </div>
                                                                    </td>
                                                                    <td class="mn-cart-pro-subtotal"><?php echo currency_info()->currency_symbol; ?><?= number_format($subtotal, 2) ?></td>
                                                                    <td class="mn-cart-pro-remove">
                                                                        <a href="javascript:void(0)" class="cart-remove"><i class="ri-delete-bin-line"></i></a>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <tr>
                                                                <td colspan="5" class="text-center">Your cart is empty.</td>
                                                            </tr>
                                                        <?php endif; ?>
                                                        </tbody>

												</tbody>
											</table>
										</div>
											<div class="col-lg-12">
												<div class="mn-cart-update-bottom">
													<a href="<?php echo base_url();?>user/shop-list">Continue Shopping</a>
													<a href="<?php echo base_url();?>user/shop/get_checkout_page" class="mn-btn-2" style="color:white !important;text-decoration:none"><span>Checkout<i
									class="ri-arrow-right-s-line"></i></span></a>
												</div>
											</div>
									</form>
								</div>
							</div>
						</div>
						<!--cart content End -->
					</div>
					<!-- Sidebar Area Start -->
					<div class="mn-cart-rightside col-lg-4 col-md-12 m-t-991">
						<div class="mn-sidebar-wrap">
							<!-- Sidebar Summary Block -->
							<div class="mn-sidebar-block">
								<div class="mn-sb-title">
									<h3 class="mn-sidebar-title">Summary</h3>
								</div>
								<div class="mn-sb-block-content">
									<!-- <div class="mn-cart-form">
										<p>Enter your destination to get a shipping estimate</p>
										<form action="#" method="post">
											<span class="mn-cart-wrap">
												<label>Country *</label>
												<span class="mn-cart-select-inner">
													<select name="gi_cart_country" id="mn-cart-select-country"
														class="mn-cart-select">
														<option selected="" disabled="">United States</option>
														<option value="1">Country 1</option>
														<option value="2">Country 2</option>
														<option value="3">Country 3</option>
														<option value="4">Country 4</option>
														<option value="5">Country 5</option>
													</select>
												</span>
											</span>
											<span class="mn-cart-wrap">
												<label>State/Province</label>
												<span class="mn-cart-select-inner">
													<select name="gi_cart_state" id="mn-cart-select-state"
														class="mn-cart-select">
														<option selected="" disabled="">Please Select a region,
															state
														</option>
														<option value="1">Region/State 1</option>
														<option value="2">Region/State 2</option>
														<option value="3">Region/State 3</option>
														<option value="4">Region/State 4</option>
														<option value="5">Region/State 5</option>
													</select>
												</span>
											</span>
											<span class="mn-cart-wrap">
												<label>Zip/Postal Code</label>
												<input type="text" name="postalcode" placeholder="Zip/Postal Code">
											</span>
										</form>
									</div> -->
								</div>

								<div class="mn-sb-block-content">
									<div class="mn-cart-summary-bottom">
										<div class="mn-cart-summary">
                                        <div>
                                            <span>Sub-Total:</span>
                                            <span><?php echo currency_info()->currency_symbol; ?><?= number_format($subtotal, 2) ?></span>
                                            </div>
                                            <div>
                                            <span>Delivery Charges:</span>
                                            <span><?php echo currency_info()->currency_symbol; ?><?= number_format($delivery_charge, 2) ?></span>
                                            </div>
                                            <div>
                                            <span>VAT (20%):</span>
                                            <span><?php echo currency_info()->currency_symbol; ?><?= number_format($vat, 2) ?></span>
                                            </div>
                                            <div>
                                            <span>Discount:</span>
                                            <span>-<?php echo currency_info()->currency_symbol; ?><?= number_format($discount, 2) ?></span>
                                            </div>
                                            <div class="mn-cart-summary-total">
                                            <span>Total:</span>
                                            <span><?php echo currency_info()->currency_symbol; ?><?= number_format($total, 2) ?></span>
                                            </div>

									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Footer -->
		
        <?php $this->load->view('user/layout/shop/common_footer'); ?>

		<!-- Search Sidebar -->
		<div class="mn-side-search-overlay"></div>
		<?php $this->load->view('user/layout/shop/side_cart');?>

	</main>


      <?php $this->load->view('user/layout/shop/common_script'); ?>
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
        <script>
        const base_url = '<?php echo base_url();?>';
        </script>
        <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/user-shop-filter.js?ver=8.9"></script>
<script>
    document.getElementById("checkoutBtn").addEventListener("click", function () {
        window.location.href = "<?php echo base_url(); ?>user/checkout";
    });
</script>
</body>


</html>