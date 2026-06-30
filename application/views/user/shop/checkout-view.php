<!DOCTYPE html>
<html lang="en" dir="ltr">

<?php $this->load->view('user/layout/shop/common_style'); ?>
<?php $this->load->view('user/shop/checkout-style'); ?>


<style>
    /* Accordion container */
.checkout-detail {
    border: 1px solid #e2e2e2;
    border-radius: 12px;
    background: #fff;
    padding: 20px;
    margin-top: 20px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

/* Accordion Item */
.accordion-item {
    border: none;
    border-bottom: 1px solid #eee;
    margin-bottom: 5px;
    padding: 10px 0;
}

/* Header (radio + label) */
.accordion-button {
    display: flex;
    align-items: center;
    background-color: #f9f9f9;
    font-weight: 500;
    color: #333;
    padding: 15px 20px;
    border-radius: 8px;
    transition: background 0.2s ease;
}

.accordion-button:hover {
    background-color: #f1f1f1;
}

.form-check-input {
    margin-right: 10px;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    border: 2px solid #999;
    transition: all 0.2s ease;
}

.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}

/* Body */
.accordion-body {
    padding: 15px 20px;
    background: #fcfcfc;
    border-radius: 0 0 8px 8px;
    font-size: 14px;
    color: #444;
}

/* Highlight first visible block */
.accordion-collapse.show .accordion-body {
    background: #eef9f1;
}

/* Payment type title formatting */
.form-check-label {
    font-weight: 500;
    font-size: 16px;
    display: flex;
    align-items: center;
}

/* Mode info */
.accordion-body strong {
    color: #555;
}

h4{
        font-size: 1rem !important;
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
					

               
<section class="checkout-section-2 section-b-space">
        <div class="container-fluid-lg">
            <div class="row g-sm-4 g-3">
                <div class="col-lg-8">
                    <div class="left-sidebar-checkout">
                        <div class="checkout-detail-box">
                            <ul>
                                <li>
                                    <div class="checkout-icon">
                                    <i class="fa fa-address-card" aria-hidden="true"></i>
                                    </div>
                                    <div class="checkout-box">
                                        <div class="checkout-title">
                                            <h4>Delivery Address</h4>
                                        </div>

                                        <div class="checkout-detail">
                                            <div class="row g-4">
                                                <?php if ($address_info): ?>
                                                    <div class="col-xxl-6 col-lg-12 col-md-6">
                                                        <div class="delivery-address-box">
                                                            <div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio" name="jack" id="flexRadioDefault1" checked="checked">
                                                                </div>

                                                                <div class="label">
                                                                    <label><?= ucfirst($address_info->address_type) ?></label>
                                                                </div>

                                                                <ul class="delivery-address-detail">
                                                                    <li>
                                                                        <h4 class="fw-500"><?= $address_info->first_name . ' ' . $address_info->last_name ?></h4>
                                                                    </li>
                                                                    <li>
                                                                        <p class="text-content">
                                                                            <span class="text-title">Address: </span><?= $address_info->address . ", " . $address_info->city ?>
                                                                        </p>
                                                                    </li>
                                                                    <li>
                                                                        <h6 class="text-content"><span class="text-title">Pin Code: </span><?= $address_info->postal_code ?></h6>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="col-md-12 mb-3">
                                                        <button class="btn btn-primary" onclick="document.getElementById('addAddressForm').style.display='block'">
                                                            Add Address
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Add Address Form (initially hidden) -->
                                        <div id="addAddressForm" style="display: <?= $address_info ? 'none' : 'block' ?>;" >
                                         
                                        <form id="addressForm" class="row gy-3 needs-validation" novalidate>

                                        <!-- ── Name row ─────────────────────── -->
                                            <div class="col-md-6">
                                                <label class="form-label" for="firstname">First Name *</label>
                                                <input type="text"
                                                    id="firstname"
                                                    name="firstname"
                                                    class="form-control"
                                                    placeholder="First Name"
                                                    required>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="lastname">Last Name *</label>
                                                <input type="text"
                                                    id="lastname"
                                                    name="lastname"
                                                    class="form-control"
                                                    placeholder="Last Name"
                                                    required>
                                            </div>

                                            <!-- ── Address row ──────────────────── -->
                                            <div class="col-12">
                                                <label class="form-label" for="address">Address *</label>
                                                <input type="text"
                                                    id="address"
                                                    name="address"
                                                    class="form-control"
                                                    placeholder="Flat no. / Street / Area"
                                                    required>
                                            </div>

                                            <!-- ── City / State ─────────────────── -->
                                            <div class="col-md-6">
                                                <label class="form-label" for="city">City *</label>
                                                <input type="text"
                                                    id="city"
                                                    name="city"
                                                    class="form-control"
                                                    placeholder="City"
                                                    required>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="state">State *</label>
                                                <input type="text"
                                                    id="state"
                                                    name="state"
                                                    class="form-control"
                                                    placeholder="State"
                                                    required>
                                            </div>

                                            <!-- ── Zip / Country ────────────────── -->
                                            <div class="col-md-6">
                                                <label class="form-label" for="postalcode">Postal Code *</label>
                                                <input type="text"
                                                    id="postalcode"
                                                    name="postalcode"
                                                    class="form-control"
                                                    placeholder="ZIP / PIN"
                                                    required>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="country">Country *</label>
                                                <input type="text"
                                                    id="country"
                                                    name="country"
                                                    class="form-control"
                                                    placeholder="Country"
                                                    required>
                                            </div>

                                            <!-- ── Address Type ─────────────────── -->
                                            <div class="col-12">
                                                <label class="form-label" for="address_type">Address Type *</label>
                                                <select name="address_type"
                                                        id="address_type"
                                                        class="form-select"
                                                        required>
                                                    <option selected disabled value="">Choose...</option>
                                                    <option value="home">Home</option>
                                                    <option value="work">Work</option>
                                                    <option value="apartment">Apartment</option>
                                                </select>
                                            </div>

                                            <!-- ── Submit ───────────────────────── -->
                                            <div class="col-12">
                                                <button class="btn btn-success w-100" type="submit">
                                                    <i class="ri-save-line me-1"></i> Save Address
                                                </button>
                                            </div>
                                        </form>


                                        </div>


                                </li>

                                <li>
                                    <div class="checkout-icon">
                                    <i class="fa-solid fa-truck ms-2 mt-3"></i>
                                    </div>
                                    <div class="checkout-box">
                                        <div class="checkout-title">
                                            <h4>Payment Option</h4>
                                        </div>

                                    <div class="checkout-detail">
                                        <div class="accordion accordion-flush custom-accordion" id="accordionFlushExample">
                                            <?php
                                            $i = 1;
                                            foreach ($payment_gateways as $gateway):
                                                $gateway_id = strtolower($gateway->gateway);
                                                $checked = $i === 1 ? 'checked' : '';
                                                $show = $i === 1 ? 'show' : '';
                                            ?>
                                            <div class="accordion-item">
                                                <div class="accordion-header" id="heading<?= $i ?>">
                                                    <div class="accordion-button collapsed" data-bs-toggle="collapse"
                                                        data-bs-target="#collapse<?= $i ?>">
                                                        <div class="custom-form-check form-check mb-0">
                                                            <label class="form-check-label" for="<?= $gateway_id ?>">
                                                                <input class="form-check-input mt-0 payment-radio" type="radio" name="payment_gateway"
                                                                    id="<?= $gateway_id ?>" value="<?= $gateway_id ?>" <?= $checked ?>> 
                                                                <?= ucfirst(str_replace('_', ' ', $gateway->gateway)) ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="collapse<?= $i ?>" class="accordion-collapse collapse <?= $show ?>"
                                                    data-bs-parent="#accordionFlushExample">
                                                    <div class="accordion-body">
                                                        <?php if ($gateway_id == 'cash_on'): ?>
                                                           <p class="cod-review"> Your Account Balance id : <?php echo site_wallet_balance($user_id); ?> Pay using your account balance. May be limited in some regions.</p>
                                                        <?php elseif ($gateway_id == 'stripe'): ?>
                                                            <p class="cod-review">Pay securely via credit/debit card powered by Stripe.</p>
                                                            <p>Mode: <strong><?= ucfirst($gateway->mode) ?></strong></p>
                                                        <?php elseif ($gateway_id == 'paypal'): ?>
                                                            <p class="cod-review">Pay using PayPal account or linked cards.</p>
                                                            <p>Mode: <strong><?= ucfirst($gateway->mode) ?></strong></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php $i++; endforeach; ?>
                                        </div>
                                    </div>



                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                   <div class="right-side-summery-box">
                        <div class="summery-box-2">
                            <div class="summery-header">
                                <h3 style="font-size:20px;">Order Summery</h3>
                            </div>

                            <ul class="summery-contain">
                            <?php if (!empty($cart_items)): ?>
                            <?php foreach ($cart_items as $item): ?>
                            <?php
                            $price = ($item->offer_status && $item->offer_price > 0) ? $item->offer_price : $item->price;
                            $subtotals = $price * $item->quantity;
                            ?>
                            <li>
                            <img src="<?= base_url('assets/images/' . $item->product_image) ?>" class="img-fluid blur-up lazyloaded checkout-image" alt="">
                            <h4>  <?= htmlspecialchars($item->name) ?><span>X <?= $item->quantity ?></span></h4>
                            <h4 class="price"><?php echo currency_info()->currency_symbol; ?><?= number_format($subtotals, 2) ?></h4>
                            </li>
                            <?php endforeach; ?>
                            <?php endif; ?>

                                                            </ul>

                            <ul class="summery-total">
                                <li>
                                    <h4>Subtotal</h4>
                                    <h4 class="price"><?php echo currency_info()->currency_symbol; ?> <?= number_format($subtotal, 2) ?></h4>
                                </li>
                                <li>
                                    <h4>Shipping</h4>
                                    <h4 class="price"><?php echo currency_info()->currency_symbol; ?> <?= number_format($delivery_charge, 2) ?></h4>
                                </li>

                                <li>
                                    <h4>VAT (20%)</h4>
                                    <h4 class="price"><?php echo currency_info()->currency_symbol; ?> <?= number_format($vat, 2) ?></h4>
                                </li>

                                <li>
                                    <h4>Coupon/Code</h4>
                                    <h4 class="price"><?php echo currency_info()->currency_symbol; ?> <?= number_format($discount, 2) ?></h4>
                                </li>

                                <li class="list-total">
                                    <h4>Total (USD)</h4>
                                    <h4 class="price"><?php echo currency_info()->currency_symbol; ?> <?= number_format($total, 2) ?></h4>
                                </li>
                            </ul>
                        </div>

                        <div class="checkout-offer">
                            <div class="offer-title">
                                <div class="offer-icon">
                                    <img src="https://themes.pixelstrap.com/fastkart/assets/images/inner-page/offer.svg" class="img-fluid" alt="">
                                </div>
                                <div class="offer-name">
                                    <h6>Available Offers</h6>
                                </div>
                            </div>

                            <ul class="offer-detail">
                                <li>
                                    <p>Combo: BB Royal Almond/Badam Californian, Extra Bold 100 gm...</p>
                                </li>
                                <li>
                                    <p>combo: Royal Cashew Californian, Extra Bold 100 gm + BB Royal Honey 500 gm</p>
                                </li>
                            </ul>
                        </div>

                        <form id="orderForm" method="post" action="<?= base_url('user/shop/save_order') ?>">
                        <input type="hidden" name="shipping_id" value="<?php echo $address_info->id;?>">
                        <input type="hidden" name="payment_method" id="selectedPaymentMethod" value="stripe">
                        <button type="submit" class="btn theme-bg-color text-white btn-md w-100 mt-4 fw-bold btn btn-success">Place Order</button>
                        </form>

                        
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

		<!-- Search Sidebar -->
		<div class="mn-side-search-overlay"></div>
		<?php $this->load->view('user/layout/shop/side_cart');?>
		
	</main>


    <?php $this->load->view('user/layout/shop/common_script'); ?>

    <script>
    function toggleForm() {
        document.getElementById('addAddressForm').style.display = 'block';
    }

     $('#addressForm').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: '<?= base_url("user/shop/save_address") ?>',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.status === true) {
                    $('#addressMessage').html('<div class="alert alert-success">' + response.message + '</div>');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    $('#addressMessage').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function () {
                $('#addressMessage').html('<div class="alert alert-danger">Something went wrong.</div>');
            }
        });
    });




document.addEventListener("DOMContentLoaded", function () {
    const paymentRadios = document.querySelectorAll('.payment-radio');
    const paymentInput = document.getElementById('selectedPaymentMethod');
    const orderForm = document.getElementById('orderForm');

    const paypalBtnContainer = document.getElementById('paypal-button-container');
    const stripePayForm = document.getElementById('stripe-payment-form');

    paymentRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            paymentInput.value = this.value;
            console.log(this.value);
            if (this.value === 'paypal') {
                paypalBtnContainer.style.display = 'block';
                stripePayForm.style.display = 'none';
            } else if (this.value === 'stripe') {
                stripePayForm.style.display = 'block';
                paypalBtnContainer.style.display = 'none';
            } else {
                paypalBtnContainer.style.display = 'none';
                stripePayForm.style.display = 'none';
            }
        });
    });
});


    </script>


</body>


</html>