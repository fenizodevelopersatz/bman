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
								<h2 class="mn-breadcrumb-title">Shop Page</h2>
							</div>
							<div class="col-md-6 col-sm-12">
								<!-- mn-breadcrumb-list start -->
								<ul class="mn-breadcrumb-list">
									<li class="mn-breadcrumb-item"><a href="index.html">Home</a></li>
									<li class="mn-breadcrumb-item active">Shop Page</li>
								</ul>
								<!-- mn-breadcrumb-list end -->
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row sb-hide">
				<div class="col-xxl-12">
					<!-- Shop section -->
					<section class="mn-shop padding-tb-30">
						<!-- Shop Banners Start -->
						<div class="m-b-30">
							<div class="row">
								<div class="col-md-6">
									<div class="mn-ofr-banners">
										<div class="mn-bnr-body">
											<div class="mn-bnr-img">
												<span class="lbl">70% Off</span>
												<img src="<?php echo base_url();?>assets/shop/img/banner/5.jpg" alt="banner">
											</div>
											<div class="mn-bnr-detail">
												<h5>Best men's fashion sale</h5>
												<p>Stylish Design of clothes.</p>
												<a href="shop-right-sidebar.html" class="mn-btn-2"><span>Shop
														Now</span></a>
											</div>
										</div>
									</div>
								</div>
								<div class="col-md-6">
									<div class="mn-ofr-banners m-t-767">
										<div class="mn-bnr-body">
											<div class="mn-bnr-img">
												<span class="lbl">50% Off</span>
												<img src="<?php echo base_url();?>assets/shop/img/banner/6.jpg" alt="banner">
											</div>
											<div class="mn-bnr-detail">
												<h5>Trending women's sale</h5>
												<p>Trending desings of clothes.</p>
												<a href="shop-right-sidebar.html" class="mn-btn-2"><span>Shop
														Now</span></a>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">

                            <div class="mn-shop-sidebar col-lg-3 col-md-12 m-t-991">
								<div id="shop_sidebar">
									<div class="mn-sidebar-wrap">
										<!-- Sidebar Filters Block -->
										<div class="mn-sidebar-block drop">
											<div class="mn-sb-title">
												<h3 class="mn-sidebar-title">Filters</h3>
											</div>

                                            <div class="mt-3 mb-3">
                                                <?php foreach ($categories as $parent): ?>

                                                    <?php
                                                    $filtered_children = array_filter($parent['children'], function($child) {
                                                        return $child['product_count'] > 0;
                                                    });
                                                    ?>

                                                    <?php if ($parent['product_count'] > 0 || count($filtered_children) > 0): ?>
                                                        <div class="mn-sb-block-content">

                                                            <ul>
                                                                <li>
                                                                    <a href="javascript:void(0)" class="mn-sidebar-block-item main <?= count($filtered_children) ? 'drop toggle-sub' : '' ?>">
                                                                        <?= htmlspecialchars($parent['name']) ?>
                                                                        <?php if (count($filtered_children)): ?>
                                                                        <?php else: ?>
                                                                            <span style="float:right;"><input type="checkbox" class="filter-checkbox category-filter"
																						data-type="category"
																						data-label="<?= htmlspecialchars($parent['name']) ?>"
																						value="<?= $parent['id'] ?>"><?= $parent['product_count'] ?></span>
                                                                        <?php endif; ?>
                                                                    </a>

                                                                    <?php if (count($filtered_children)): ?>
                                                                        <ul class="subcategory-list" style="display: none; padding-left: 15px;">
                                                                            <?php foreach ($filtered_children as $child): ?>
                                                                                <li>
																					
                                                                                    <a href="javascript:void(0)" data-href="<?= base_url('shop/category/' . $child['slug']) ?>">
                                                                                        <?= htmlspecialchars($child['name']) ?> ( <?= $child['product_count'] ?> )
                                                                                        <span style="float:right;"><input type="checkbox" class="filter-checkbox category-filter"
																						data-type="category"
																						data-label="<?= htmlspecialchars($child['name']) ?>"
																						value="<?= $child['id'] ?>"  style="float:right;"></span>
                                                                                    </a>
                                                                                </li>
                                                                            <?php endforeach; ?>
                                                                        </ul>
                                                                    <?php endif; ?>
                                                                </li>
                                                            </ul>

                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>

                                            <!-- Sidebar Price Block -->
                                                <div class="mn-sidebar-block">
                                                    <div class="mn-sb-title">
                                                        <h3 class="mn-sidebar-title">Price</h3>
                                                    </div>
                                                    <div class="mn-sb-block-content mn-price-range-slider es-price-slider">
                                                        <div class="mn-price-filter">
                                                            <div class="mn-price-input">
                                                                <label class="filter__label">
                                                                    From <input type="text" class="filter__input" id="price-min">
                                                                </label>
                                                                <span class="mn-price-divider"></span>
                                                                <label class="filter__label">
                                                                    To <input type="text" class="filter__input" id="price-max">
                                                                </label>
                                                            </div>
                                                            <div id="mn-sliderPrice" class="filter__slider-price" data-min="<?php echo $price_min; ?>"
                                                                data-max="<?php echo $price_max; ?>" data-step="10"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                            <!-- Brands -->
                                            <div class="mn-sidebar-block">
                                                <div class="mn-sb-title"><h3 class="mn-sidebar-title">Brand</h3></div>
                                                <div class="mn-sb-block-content">
                                                    <ul>
                                                        <?php foreach ($brands as $brand): ?>
															<?php if($brand->product_count > 0){ ?>
                                                            <li>
                                                                <div class="mn-sidebar-block-item">
																	<input type="checkbox" name="brand[]" class="filter-checkbox brand-filter" value="<?= $brand->id ?>" data-type="brand" data-label="<?= $brand->name ?>">
                                                                    <a href="javascript:void(0)"><span><?= $brand->name ?></span></a>
                                                                    <span class="checked"></span>
                                                                </div>
                                                            </li>
															<?php } ?>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            </div>

                                            <!-- Sizes -->
                                            <div class="mn-sidebar-block">
                                                <div class="mn-sb-title"><h3 class="mn-sidebar-title">Size</h3></div>
                                                <div class="mn-sb-block-content">
                                                    <ul>
                                                        <?php foreach ($sizes as $size): ?>
                                                            <li>
                                                                <div class="mn-sidebar-block-item">
																	<input type="checkbox" name="size[]" class="filter-checkbox size-filter" value="<?= $size ?>" data-type="size" data-label="<?= strtoupper($size) ?>">
                                                                    <a href="#"><?= strtoupper($size) ?> - Size</a>
                                                                    <span class="checked"></span>
                                                                </div>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            </div>

										</div>
										
									</div>
								</div>
							</div>

							<div class="mn-shop-rightside col-lg-9 col-md-12">
								<!-- Shop Top Start -->
								<div class="mn-pro-list-top d-flex">
									<div class="col-md-6 mn-grid-list">
										<div class="mn-gl-btn">
											<button class="grid-btn btn-grid active">
												<i class="ri-gallery-view-2"></i>
											</button>
											<button class="grid-btn btn-list">
												<i class="ri-list-check-2"></i>
											</button>
										</div>
									</div>
									<div class="col-md-6 mn-sort-select">
									<div class="mn-select-inner">
											<select name="mn-select" id="mn-select">
												<option selected disabled>Sort by</option>
												<option value="name_asc">Name, A to Z</option>
												<option value="name_desc">Name, Z to A</option>
												<option value="price_asc">Price, low to high</option>
												<option value="price_desc">Price, high to low</option>
											</select>
										</div>
									</div>
								</div>
								<!-- Shop Top End -->

								<div class="mn-select-bar d-flex" id="active-filters">
								</div>


								<!-- Shop content Start -->
								<div class="shop-pro-content">
                                    <div class="shop-pro-inner">
                                        <?php $this->load->view('user/shop/product_list', ['products' => $products]); ?>
                                    </div>
                                </div>
								<!--Shop content End -->

							</div>
							<!-- Sidebar Area Start -->
							
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
											<img class="img-responsive" src="<?php echo base_url();?>assets/shop/img/product/1.jpg"
												alt="product-img-1">
											<img class="img-responsive" src="<?php echo base_url();?>assets/shop/img/product/2.jpg"
												alt="product-img-1">
											<img class="img-responsive" src="<?php echo base_url();?>assets/shop/img/product/3.jpg"
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
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
        <script>
        const base_url = '<?php echo base_url();?>';
        </script>
        <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/user-shop-filter.js?ver=8.9"></script>

</body>


</html>