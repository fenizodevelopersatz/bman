<!DOCTYPE html>
<html lang="en" dir="ltr">

<?php $this->load->view('user/layout/shop/common_style'); ?>
<?php $this->load->view('user/shop/checkout-style'); ?>
<?php $this->load->view('user/shop/success-style'); ?>

<style>
.summery-box .summery-contain {
    padding: calc(11px + 5*(100vw - 320px)/1600) calc(11px + 11*(100vw - 320px)/1600);
    border-bottom: 1px solid #ececec;
}
.summery-box .summery-contain li h4 {
    font-size: 15px;
    color: #4a5568;
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
								<h2 class="mn-breadcrumb-title">Checkout Page</h2>
							</div>
							<div class="col-md-6 col-sm-12">
								<!-- mn-breadcrumb-list start -->
								<ul class="mn-breadcrumb-list">
									<li class="mn-breadcrumb-item"><a href="<?php echo base_url();?>">Home</a></li>
									<li class="mn-breadcrumb-item active">Checkout Page</li>
								</ul>
								<!-- mn-breadcrumb-list end -->
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row sb-hide">
				<div class="col-xxl-12">
					
    <section class="breadcrumb-section pt-0">
        <div class="container-fluid-lg">
            <div class="row">
                <div class="col-12">
                    <div class="breadcrumb-contain breadcrumb-order" style="display:flex;justify-content:center;">
                        <div class="order-box">
                            <div class="order-image">
                                <div class="checkmark">
                                    <svg class="star" height="19" viewBox="0 0 19 19" width="19" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8.296.747c.532-.972 1.393-.973 1.925 0l2.665 4.872 4.876 2.66c.974.532.975 1.393 0 1.926l-4.875 2.666-2.664 4.876c-.53.972-1.39.973-1.924 0l-2.664-4.876L.76 10.206c-.972-.532-.973-1.393 0-1.925l4.872-2.66L8.296.746z">
                                        </path>
                                    </svg>
                                    <svg class="star" height="19" viewBox="0 0 19 19" width="19" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8.296.747c.532-.972 1.393-.973 1.925 0l2.665 4.872 4.876 2.66c.974.532.975 1.393 0 1.926l-4.875 2.666-2.664 4.876c-.53.972-1.39.973-1.924 0l-2.664-4.876L.76 10.206c-.972-.532-.973-1.393 0-1.925l4.872-2.66L8.296.746z">
                                        </path>
                                    </svg>
                                    <svg class="star" height="19" viewBox="0 0 19 19" width="19" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8.296.747c.532-.972 1.393-.973 1.925 0l2.665 4.872 4.876 2.66c.974.532.975 1.393 0 1.926l-4.875 2.666-2.664 4.876c-.53.972-1.39.973-1.924 0l-2.664-4.876L.76 10.206c-.972-.532-.973-1.393 0-1.925l4.872-2.66L8.296.746z">
                                        </path>
                                    </svg>
                                    <svg class="star" height="19" viewBox="0 0 19 19" width="19" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8.296.747c.532-.972 1.393-.973 1.925 0l2.665 4.872 4.876 2.66c.974.532.975 1.393 0 1.926l-4.875 2.666-2.664 4.876c-.53.972-1.39.973-1.924 0l-2.664-4.876L.76 10.206c-.972-.532-.973-1.393 0-1.925l4.872-2.66L8.296.746z">
                                        </path>
                                    </svg>
                                    <svg class="star" height="19" viewBox="0 0 19 19" width="19" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8.296.747c.532-.972 1.393-.973 1.925 0l2.665 4.872 4.876 2.66c.974.532.975 1.393 0 1.926l-4.875 2.666-2.664 4.876c-.53.972-1.39.973-1.924 0l-2.664-4.876L.76 10.206c-.972-.532-.973-1.393 0-1.925l4.872-2.66L8.296.746z">
                                        </path>
                                    </svg>
                                    <svg class="star" height="19" viewBox="0 0 19 19" width="19" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8.296.747c.532-.972 1.393-.973 1.925 0l2.665 4.872 4.876 2.66c.974.532.975 1.393 0 1.926l-4.875 2.666-2.664 4.876c-.53.972-1.39.973-1.924 0l-2.664-4.876L.76 10.206c-.972-.532-.973-1.393 0-1.925l4.872-2.66L8.296.746z">
                                        </path>
                                    </svg>
                                    <svg class="checkmark__check" height="36" viewBox="0 0 48 36" width="48" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M47.248 3.9L43.906.667a2.428 2.428 0 0 0-3.344 0l-23.63 23.09-9.554-9.338a2.432 2.432 0 0 0-3.345 0L.692 17.654a2.236 2.236 0 0 0 .002 3.233l14.567 14.175c.926.894 2.42.894 3.342.01L47.248 7.128c.922-.89.922-2.34 0-3.23">
                                        </path>
                                    </svg>
                                    <svg class="checkmark__background" height="115" viewBox="0 0 120 115" width="120" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M107.332 72.938c-1.798 5.557 4.564 15.334 1.21 19.96-3.387 4.674-14.646 1.605-19.298 5.003-4.61 3.368-5.163 15.074-10.695 16.878-5.344 1.743-12.628-7.35-18.545-7.35-5.922 0-13.206 9.088-18.543 7.345-5.538-1.804-6.09-13.515-10.696-16.877-4.657-3.398-15.91-.334-19.297-5.002-3.356-4.627 3.006-14.404 1.208-19.962C10.93 67.576 0 63.442 0 57.5c0-5.943 10.93-10.076 12.668-15.438 1.798-5.557-4.564-15.334-1.21-19.96 3.387-4.674 14.646-1.605 19.298-5.003C35.366 13.73 35.92 2.025 41.45.22c5.344-1.743 12.628 7.35 18.545 7.35 5.922 0 13.206-9.088 18.543-7.345 5.538 1.804 6.09 13.515 10.696 16.877 4.657 3.398 15.91.334 19.297 5.002 3.356 4.627-3.006 14.404-1.208 19.962C109.07 47.424 120 51.562 120 57.5c0 5.943-10.93 10.076-12.668 15.438z">
                                        </path>
                                    </svg>
                                </div>
                            </div>

                            <div class="order-contain">
                                <h3 class="theme-color">Order Success</h3>
                                <h5 class="text-content">Payment Is Successfully And Your Order Is On The Way</h5>
                                <h6>Transaction ID: <?= $order->id ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <section class="cart-section section-b-space" style="margin-top:50px;">
        <div class="container-fluid-lg">
            <div class="row g-sm-4 g-3">

              <div class="col-xxl-9 col-lg-8">
                    <div class="card shadow-sm border rounded-3">

                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>

                      <div class="card-body p-3" style="font-size: 15px; color: #4a5568;">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Product</th>
                                        <th scope="col" class="text-center">Price</th>
                                        <th scope="col" class="text-center">Qty</th>
                                        <th scope="col" class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="<?= base_url('assets/images/' . $item->product_image) ?>" 
                                                        alt="<?= $item->name ?>" 
                                                        class="img-thumbnail" 
                                                        style="width: 60px; height: 60px; object-fit: cover;">
                                                    <div>
                                                        <h6 class="mb-0 fw-semibold"><?= $item->name ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="text-success fw-semibold"><?php echo currency_info()->currency_symbol; ?> <?= number_format($item->price, 2) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary"><?= $item->quantity ?></span>
                                            </td>
                                            <td class="text-end">
                                                <strong><?php echo currency_info()->currency_symbol; ?> <?= number_format($item->price * $item->quantity, 2) ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                    </div>
                    </div>
                        <div class="col-lg-12 mt-5 ">
                        <div class="mn-cart-update-bottom d-flex justify-content-between" style="">
                        <a href="<?php echo base_url();?>user/shop-list">Continue Shopping</a>
                        <a href="<?= base_url('user/shop/invoice/' . $order->id) ?>" target="_blank" class="">
                           Print Invoice
                        </a>

                        </div>
                        </div>
                </div>


                <div class="col-xxl-3 col-lg-4">
                    <div class="row g-4">
                        <div class="col-lg-12 col-sm-6">
                            <div class="summery-box">
                                <div class="summery-header">
                                    <h3>Payment Details</h3>
                                    <h5 class="ms-auto theme-color">(1 Items)</h5>
                                </div>

                               <ul class="summery-contain">
                                <li>
                                    <h4>Subtotal</h4>
                                    <h4 class="price"><?php echo currency_info()->currency_symbol; ?> <?= number_format($order->total_amount / 1.2, 2) ?></h4>
                                </li>
                                <li>
                                    <h4>VAT (20%)</h4>
                                    <h4 class="price text-danger"><?php echo currency_info()->currency_symbol; ?> <?= number_format($order->total_amount * 0.2 / 1.2, 2) ?></h4>
                                </li>
                                <li>
                                    <h4>Shipping</h4>
                                    <h4 class="price"><?php echo currency_info()->currency_symbol; ?> 0.00</h4>
                                </li>
                            </ul>

                            <ul class="summery-total">
                                <li class="list-total">
                                    <h4>Total</h4>
                                    <h4 class="price"><?php echo currency_info()->currency_symbol; ?> <?= number_format($order->total_amount, 2) ?></h4>
                                </li>
                            </ul>
                               
                            </div>
                        </div>

                        <div class="col-lg-12 col-sm-6">
                            <div class="summery-box">
                                <div class="summery-header d-block">
                                    <h3>Shipping Address</h3>
                                </div>
                                <ul class="summery-contain pb-0 border-bottom-0">

               

                                <?php if ($shipping): ?>
                                <li class="d-block pt-0">
                                <p class="text-content">
                                <?= $shipping->firstname . ' ' . $shipping->lastname ?>                                       
                                </p>
                                </li>
                                <li class="d-block pt-0">
                                <p class="text-content">
                                <?= $shipping->address . ', ' . $shipping->city . ' ' . $shipping->postalcode ?>                                     
                                </p>
                                </li>
                                 <li class="d-block pt-0">
                                <p class="text-content">
                                <?= $shipping->state . ', ' . $shipping->country ?>                                   
                                </p>
                                </li>
                                <?php endif; ?>

                                </ul>
                             
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="summery-box">
                                <div class="summery-header d-block">
                                    <h3>Payment Method</h3>
                                </div>

                                <ul class="summery-contain pb-0 border-bottom-0">
                                    <li class="d-block pt-0">
                                        <p class="text-content">
                                            <?= $order->payment_status == 'paid' ? 'Paid successfully via ' . ucfirst($payment_get) : 'Pay on Delivery (COD)' ?>
                                        </p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


				</div>
			</div>
		</div>


		<!-- Footer -->
        <?php $this->load->view('user/layout/shop/common_footer'); ?>

        <script src="https://js.stripe.com/v3/"></script>
        <script src="https://www.paypal.com/sdk/js?client-id=AQqQS4UMgPR8d9FAEOE671D-IVhOotUmVT8kjjG_k7C-BNBYkWqDH1sdYecWk3rvoSuziLSHPcyMbYd3&currency=USD"></script>


		<!-- Footer Nav For Mobile -->

		<!-- Quick view Modal -->
		<div class="modal fade quickview-modal" id="quickview_modal" tabindex="-1" role="dialog">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content">
					<button type="button" class="qty-close" data-bs-dismiss="modal" aria-label="Close"
						title="Close"></button>
					<div class="modal-body">
						<div class="row mb-minus-24">
							<div class="col-md-5 col-sm-12 col-xs-12 mb-24">
								<div class="single-pro-img single-pro-img-no-sidebar">
									<div class="single-product-scroll">
										<div class="single-slide-quickview owl-carousel">
											<img class="img-responsive" src="assets/img/product/1.jpg"
												alt="product-img-1">
											<img class="img-responsive" src="assets/img/product/2.jpg"
												alt="product-img-1">
											<img class="img-responsive" src="assets/img/product/3.jpg"
												alt="product-img-1">
										</div>
									</div>
								</div>
							</div>
							<div class="col-md-7 col-sm-12 col-xs-12 mb-24">
								<div class="quickview-pro-content">
									<h5 class="mn-quick-title">
										<a href="product-detail.html">Best cotton fabric women's half sleeve
											T-shirt white color.</a>
									</h5>
									<div class="mn-pro-rating">
										<i class="ri-star-fill"></i>
										<i class="ri-star-fill"></i>
										<i class="ri-star-fill"></i>
										<i class="ri-star-fill"></i>
										<i class="ri-star-fill grey"></i>
									</div>
									<div class="mn-quickview-desc">Lorem Ipsum is simply dummy text of the printing and
										typesetting industry. Lorem Ipsum has been the industry's standard dummy text
										ever
										since the 1900s.</div>
									<div class="mn-quickview-price">
										<span class="new-price">$50.00</span>
										<span class="old-price">$62.00</span>
									</div>
									<div class="mn-pro-variations">
										<ul>
											<li class="active">
												<a href="javascript:void(0)" class="mn-opt-sz"
													data-tooltip="Small">s</a>
											</li>
											<li>
												<a href="javascript:void(0)" class="mn-opt-sz"
													data-tooltip="Medium">m</a>
											</li>
											<li>
												<a href="javascript:void(0)" class="mn-opt-sz"
													data-tooltip="Large">l</a>
											</li>
											<li>
												<a href="javascript:void(0)" class="mn-opt-sz"
													data-tooltip="Extra Large">xl</a>
											</li>
										</ul>
									</div>
									<div class="mn-quickview-qty">
										<div class="qty-plus-minus">
											<input class="qty-input" type="text" name="mn-qtybtn" value="1">
										</div>
										<div class="mn-quickview-cart">
											<a href="cart.html" class="mn-btn-1">
												<span><i class="ri-shopping-bag-line"></i>Add To Cart</span>
											</a>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="mn-side-search-overlay"></div>
		<?php $this->load->view('user/layout/shop/side_cart');?>
		
	</main>


    <?php $this->load->view('user/layout/shop/common_script'); ?>

</body>


</html>