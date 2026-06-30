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
								<h2 class="mn-breadcrumb-title">Product Page</h2>
							</div>
							<div class="col-md-6 col-sm-12">
								<!-- mn-breadcrumb-list start -->
								<ul class="mn-breadcrumb-list">
									<li class="mn-breadcrumb-item"><a href="index.html">Home</a></li>
									<li class="mn-breadcrumb-item active">Product Page</li>
								</ul>
								<!-- mn-breadcrumb-list end -->
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row sb-hide">
				<div class="col-xxl-12">
					<section class="mn-single-product">
						<div class="row">
							<div class="mn-pro-rightside mn-common-rightside col-lg-12 col-md-12 m-b-15">
								<!-- Single product content Start -->
								<div class="single-pro-block">
									<div class="single-pro-inner">
										<div class="row">
                                            <div class="single-pro-img">
                                                <div class="single-product-scroll">
                                                    <!-- Main Cover Images -->
                                                    <div class="single-product-cover">
                                                        <?php if (!empty($images)): ?>
                                                            <?php foreach ($images as $img): ?>
                                                                <div class="single-slide zoom-image-hover">
                                                                    <img class="img-responsive" src="<?= base_url('assets/images/' . $img['image']) ?>" alt="Product Image">
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <div class="single-slide zoom-image-hover">
                                                                <img class="img-responsive" src="<?= base_url('assets/images/default.jpg') ?>" alt="No Image">
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Thumbnail Navigation -->
                                                    <div class="single-nav-thumb">
                                                        <?php if (!empty($images)): ?>
                                                            <?php foreach ($images as $img): ?>
                                                                <div class="single-slide">
                                                                    <img class="img-responsive" src="<?= base_url('assets/images/' . $img['image']) ?>" alt="Thumb">
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

											<div class="single-pro-desc m-t-991">
												<div class="single-pro-content">
													<h5 class="mn-single-title"><?= $product['name'] ?></h5>
													<div class="mn-single-rating mn-pro-rating">
                                                        <?php
                                                            $rating = $product['avg_rating'] ?? 0;
                                                            for ($i = 1; $i <= 5; $i++) {
                                                                echo '<i class="ri-star-fill ' . ($i <= $rating ? '' : 'grey') . '"></i>';
                                                            }
                                                        ?>
                                                    </div>
                                                    <span class="mn-read-review">
                                                        |&nbsp;&nbsp;<a href="#mn-spt-nav-review"><?= $product['total_reviews'] ?? 0 ?> Ratings</a>
                                                    </span>


													<div class="mn-single-price-stoke">
                                                        <div class="mn-single-price">
                                                            <div class="final-price"> <?php echo currency_info()->currency_symbol; ?><?= $product['offer_price'] ?>
                                                                <?php if ($product['offer_price']): ?>
                                                                    <span class="price-des">
                                                                        -<?= $product['offer_percentage'] ?>%
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <?php if ($product['offer_price']): ?>
                                                                <div class="mrp"><span> <?php echo currency_info()->currency_symbol; ?> <?= $product['price'] ?></span></div>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div class="mn-single-stoke">
                                                            <span class="mn-single-sku">SKU#: <?= $product['sku'] ?></span>
                                                            <span class="mn-single-ps-title"><?= $product['stock'] > 0 ? 'IN STOCK' : 'OUT OF STOCK' ?></span>
                                                        </div>
                                                    </div>

                                                    <div class="mn-single-desc"><?= nl2br($product['description']) ?></div>

                                                    <div class="mn-single-list">
                                                        <ul>
                                                            <li><strong>Closure :</strong> <?= $meta['closure'] ?? 'N/A' ?></li>
                                                            <li><strong>Sole :</strong> <?= $meta['sole'] ?? 'PVC' ?></li>
                                                            <li><strong>Width :</strong> <?= $meta['width'] ?? 'Medium' ?></li>
                                                            <li><strong>Outer Material :</strong> <?= $meta['outer_material'] ?? 'Standard Quality' ?></li>
                                                        </ul>
                                                    </div>

													<?php if (!empty($available_sizes)): ?>
                                                    <div class="mn-pro-variation">
                                                        <div class="mn-pro-variation-inner mn-pro-variation-size m-b-24">
                                                            <span>Size</span>
                                                            <div class="mn-pro-variation-content">
                                                                <ul>
                                                                    <?php foreach ($available_sizes as $index => $size): ?>
                                                                        <li class="<?= $index == 0 ? 'active' : '' ?>">
                                                                            <span><?= strtolower($size) ?></span>
                                                                        </li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>

                                                    
													<!-- <div class="mn-single-qty">
														<div class="qty-plus-minus">
															<input class="qty-input" type="text" name="ms_qtybtn"
																value="1">
														</div>
														<div class="mn-btns">
															<div class="mn-single-cart">
																<button
																	class="btn btn-primary mn-btn-2 add-card-view"><span>Add
																		To
																		Cart</span></button>
															</div>
															<div class="mn-single-wishlist">
																<a href="javascript:void(0)"
																	class="mn-btn-group wishlist mn-wishlist"
																	title="Wishlist">
																	<i class="ri-heart-line"></i>
																</a>
															</div>
															<div class="mn-single-mn-compare">
																<a href="javascript:void(0)"
																	class="mn-btn-group mn-compare" title="Quick view">
																	<i class="ri-repeat-line"></i>
																</a>
															</div>
														</div>
													</div> -->

													<div class="mn-single-qty d-flex flex-column flex-md-row align-items-start gap-3">
													<div class="qty-plus-minus">
														<input class="qty-input form-control" type="text" name="ms_qtybtn" value="1" />
													</div>

													<div class="mn-btns d-flex flex-wrap gap-2 align-items-center">

														<?php  
														$wishlist_active = $whitelist ? 'active-heart' : '';
														?>

														<div class="mn-single-cart">
														<button class="btn btn-primary add-to-cart" data-product="<?= $product['id'] ?>">
															<i class="fa-solid fa-cart-plus me-1"></i> Add to Cart
														</button>
														</div>

														<div class="mn-single-wishlist">
														<a href="javascript:void(0)" class="btn btn-outline-warning add-to-wishlist" data-product="<?= $product['id'] ?>" title="Add to Wishlist">
															<i class="ri-heart-line  <?php echo $wishlist_active; ?>"></i>
														</a>
														</div>

													</div>
													</div>

												</div>
											</div>
										</div>
									</div>
								</div>
								<!--Single product content End -->
								<!-- Single product tab start -->
								<div class="mn-single-pro-tab">
									<div class="mn-single-pro-tab-wrapper">
										<div class="mn-single-pro-tab-nav">
											<ul class="nav nav-tabs" id="myTab" role="tablist">
												<li class="nav-item" role="presentation">
													<button class="nav-link active" id="details-tab"
														data-bs-toggle="tab" data-bs-target="#mn-spt-nav-details"
														type="button" role="tab" aria-controls="mn-spt-nav-details"
														aria-selected="true">Detail</button>
												</li>
												<li class="nav-item" role="presentation">
													<button class="nav-link" id="info-tab" data-bs-toggle="tab"
														data-bs-target="#mn-spt-nav-info" type="button" role="tab"
														aria-controls="mn-spt-nav-info"
														aria-selected="false">Specifications</button>
												</li>
												<li class="nav-item" role="presentation">
													<button class="nav-link" id="review-tab" data-bs-toggle="tab"
														data-bs-target="#mn-spt-nav-review" type="button" role="tab"
														aria-controls="mn-spt-nav-review"
														aria-selected="false">Reviews</button>
												</li>
											</ul>

										</div>
										<div class="tab-content  mn-single-pro-tab-content">
											<div id="mn-spt-nav-details" class="tab-pane fade show active">
												<div class="mn-single-pro-tab-desc">
                                                   <?= nl2br($product['description']) ?>
                                                </div>
											</div>
											<div id="mn-spt-nav-info" class="tab-pane fade">
												<div class="mn-single-pro-tab-moreinfo">
													<ul>
                                                        <li><span>Model</span> <?= $product['sku'] ?></li>
                                                        <li><span>Weight</span> <?= $product['weight'] ?> g</li>
                                                        <li><span>Dimensions</span> <?= $product['length'] ?> x <?= $product['width'] ?> x <?= $product['height'] ?> cm</li>
                                                        <?php if (!empty($meta)): ?>
                                                            <?php foreach ($meta as $key => $val): ?>
                                                                <li><span><?= ucfirst($key) ?></span> <?= $val ?></li>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </ul>
												</div>
											</div>
											<div id="mn-spt-nav-review" class="tab-pane fade">
												<div class="row">
													<div class="mn-t-review-wrapper mt-0">
                                                        <?php if (!empty($reviews)): ?>
                                                            <?php foreach ($reviews as $review): ?>
                                                                <div class="mn-t-review-item">
                                                                    <div class="mn-t-review-avtar">
                                                                        <img src="<?= base_url('assets/img/user/default.jpg') ?>" alt="user">
                                                                    </div>
                                                                    <div class="mn-t-review-content">
                                                                        <div class="mn-t-review-top">
                                                                            <div class="mn-t-review-name"><?= $review['user_name'] ?? 'Anonymous' ?></div>
                                                                            <div class="mn-t-review-rating mn-pro-rating">
                                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                                    <i class="ri-star-fill <?= $i <= $review['rating'] ? '' : 'grey' ?>"></i>
                                                                                <?php endfor; ?>
                                                                            </div>
                                                                        </div>
                                                                        <div class="mn-t-review-bottom">
                                                                            <p><?= htmlentities($review['review']) ?></p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <div class="mn-t-review-item text-center w-100">
                                                                <p>No reviews yet. Be the first to review this product!</p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<!-- Related Section -->
								<section class="mn-related-product m-t-30">
                                    <div class="mn-title">
                                        <h2>Related <span>Products</span></h2>
                                    </div>
                                    <div class="mn-related owl-carousel">
                                        <?php foreach ($related_products as $r): ?>
                                            <div class="mn-product-card">
                                                <div class="mn-product-img">
                                                    <div class="lbl"><span class="trending">trending</span></div>
                                                    <div class="mn-img">
                                                        <a href="<?= base_url('product/' . $r['slug']) ?>" class="image">
                                                            <img class="main-img" src="<?= base_url('assets/images/' . $r['product_image']) ?>" alt="<?= $r['name'] ?>">
                                                            <img class="hover-img" src="<?= base_url('assets/images/' . $r['product_image']) ?>" alt="<?= $r['name'] ?>">
                                                        </a>
                                                        <div class="mn-pro-loader"></div>
                                                        <div class="mn-options">
                                                            <ul>
                                                                <li><a href="javascript:void(0)" title="Quick View" data-bs-toggle="modal" data-bs-target="#quickview_modal"><i class="ri-eye-line"></i></a></li>
                                                                <li><a href="javascript:void(0)" title="Add To Cart" class="mn-add-cart"><i class="ri-shopping-cart-line"></i></a></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mn-product-detail">
                                                    <div class="cat">
                                                        <a href="#"><?= $r['category_name'] ?></a>
                                                        <!-- Optional: Sizes from product_meta -->
                                                    </div>
                                                    <h5><a href="<?= base_url('product/' . $r['slug']) ?>"><?= $r['name'] ?></a></h5>
                                                    <div class="mn-price">
                                                        <div class="mn-price-new"><?= $r['price'] ?></div>
                                                        <?php if ($r['offer_price']): ?>
                                                            <div class="mn-price-old"><?= $r['offer_price'] ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="mn-pro-option">
                                                        <!-- Optional: Color swatches -->
                                                        <a href="javascript:void(0);" class="mn-wishlist" title="Wishlist"><i class="ri-heart-line"></i></a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </section>

                                        
							</div>
						</div>
					</section>
				</div>
			</div>
		</div>


		<!-- Footer -->
		
        <?php $this->load->view('user/layout/shop/common_footer'); ?>

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

		<!-- Search Sidebar -->
		<div class="mn-side-search-overlay"></div>
		<?php $this->load->view('user/layout/shop/side_cart');?>
		
	</main>


    <?php $this->load->view('user/layout/shop/common_script'); ?>

</body>


</html>