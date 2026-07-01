<?php
$logo = site_settings('image', 'logo');
$dark_logo = site_settings('dark_logo', 'logo');
?>

<!-- Loader -->
<div id="mn-overlay">
	<div class="loader">
		<img src="<?php echo base_url(); ?>assets/shop/img/logo/loader.png" alt="loader">
		<span class="shape"></span>
	</div>
</div>

<!-- Sidebar -->
<div class="mn-sidebar-overlay"></div>

<style>
	@media only screen and (max-width: 991px) {
		.mn-header .mn-header-items .left-header {
			display: flex;
			align-items: center;
			gap: 12px;
		}

		.mn-header .mn-header-items .left-header .mn-toggle-menu .header-icon {
			color: #fff;
		}
	}
</style>

<header class="sb-hide">
	<div class="mn-header sb-hide">
		<div class="mn-header-items">
			<div class="left-header" style="background:#313b50;padding:5px;">
				<a href="javascript:void(0)" class="mn-toggle-menu" aria-label="Open menu">
					<span class="header-icon"><i class="ri-menu-2-line"></i></span>
				</a>
				<a href="<?php echo base_url(); ?>" class="logo"><img
						src="<?php echo base_url(); ?>assets/images/<?php echo $logo; ?>" alt="logo"></a>
			</div>

			<div class="right-header">
				<!-- Main Menu Start -->
				<div id="mn-main-menu-desk" class="d-none d-lg-block sticky-nav">
					<div class="nav-desk">
						<div class="row">
							<div class="col-md-12 align-self-center">
								<div class="mn-main-menu">
									<ul>
										<li class="non-drop">
											<a href="<?php echo base_url(); ?>">Home</a>
										</li>
										<li class="non-drop">
											<a href="<?php echo base_url(); ?>user/shop-list">Shop</a>
										</li>
									</ul>
									</li>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="mn-mobile-menu-overlay"></div>
				<div id="mn-mobile-menu" class="mn-mobile-menu">
					<div class="mn-menu-title">
						<span class="menu_title">My Menu</span>
						<button type="button" class="mn-close-menu">×</button>
					</div>
					<div class="mn-menu-inner">
						<div class="mn-menu-content">
							<ul>
								<li>
									<a href="<?php echo base_url(); ?>">Home</a>
								</li>
								<?php $this->session->userdata('user_userid') ?>
								<?php if ($this->session->userdata('user_userid')) { ?>
									<li>
										<a href="<?php echo base_url(); ?>user/main">Dashboard</a>
									</li>
								<?php } ?>
								<!-- <li>
									<a href="javascript:void(0)">Categories</a>
									<ul class="sub-menu">
										<li>
											<a href="javascript:void(0)">Classic</a>
											<ul class="sub-menu">
												<li><a href="shop-right-sidebar.html">Shop Right
														sidebar</a></li>
												<li><a href="shop-banner-right-sidebar.html">Banner
														Right sidebar</a></li>
												<li><a href="shop-full-width.html">Shop Full
														Width</a></li>
												<li><a href="shop-banner-full-width.html">Banner Full
														Width</a></li>
											</ul>
										</li>
										<li>
											<a href="javascript:void(0)">List</a>
											<ul class="sub-menu">
												<li><a href="shop-list-right-sidebar.html">Shop Right
														sidebar</a></li>
												<li><a href="shop-list-banner-right-sidebar.html">Banner
														Right sidebar</a></li>
												<li><a href="shop-list-full-width.html">Shop
														Full Width</a></li>
												<li><a href="shop-list-banner-full-width.html">Banner
														Full Width</a></li>
											</ul>
										</li>
									</ul>
								</li> -->
								<li>
									<a href="javascript:void(0)">Products</a>
									<ul class="sub-menu">
										<li><a href="<?php echo base_url(); ?>user/shop-list">product detail</a></li>
										<li><a href="<?php echo base_url(); ?>user/shop-list">Product accordion</a></li>
										<li><a href="<?php echo base_url(); ?>user/shop-list">Product full width</a>
										</li>
										<li><a href="<?php echo base_url(); ?>user/shop-list">accordion full width</a>
										</li>
									</ul>
								</li>
								<li>
									<a href="javascript:void(0)">Pages</a>
									<ul class="sub-menu">
										<li><a href="<?php echo base_url(); ?>">About Us</a></li>
										<li><a href="<?php echo base_url(); ?>">Contact Us</a></li>
										<li><a href="<?php echo base_url(); ?>">Cart</a></li>
										<li><a href="<?php echo base_url(); ?>">Checkout</a></li>
										<li><a href="<?php echo base_url(); ?>">Compare</a></li>
										<li><a href="<?php echo base_url(); ?>">Faq</a></li>
										<li><a href="<?php echo base_url('user/in'); ?>">Login</a></li>
										<li><a href="<?php echo base_url('user/re'); ?>">Register</a></li>
									</ul>
								</li>
								<li>
									<a href="javascript:void(0)">Blog</a>
									<ul class="sub-menu">
										<li><a href="<?php echo base_url('matrix-mlm'); ?>">Right Sidebar</a></li>
										<li><a href="<?php echo base_url('australia-mlm'); ?>">Full Width</a></li>
										<li><a href="<?php echo base_url('gift-mlm'); ?>">Detail Right Sidebar</a>
										</li>
										<li><a href="<?php echo base_url(); ?>">Detail Full Width</a></li>
									</ul>
								</li>
							</ul>
						</div>
						<div class="header-res-lan-curr">
							<!-- Social Start -->
							<div class="header-res-social">
								<div class="header-top-social">
									<ul class="mb-0">
										<li class="list-inline-item">
											<a href="#"><i class="ri-facebook-fill"></i></a>
										</li>
										<li class="list-inline-item">
											<a href="#"><i class="ri-twitter-fill"></i></a>
										</li>
										<li class="list-inline-item">
											<a href="#"><i class="ri-instagram-line"></i></a>
										</li>
										<li class="list-inline-item">
											<a href="#"><i class="ri-linkedin-fill"></i></a>
										</li>
									</ul>
								</div>
							</div>
							<!-- Social End -->
						</div>
					</div>
				</div>
				<!-- Main menu icons -->
				<div class="mn-tool-icons">
					<div class="mn-tool-user">
						<a href="javascript:void(0)" class="mn-main-user">
							<svg class="svg-icon" viewBox="0 0 1024 1024" version="1.1"
								xmlns="http://www.w3.org/2000/svg">
								<path
									d="M512.476 648.247c-170.169 0-308.118-136.411-308.118-304.681 0-168.271 137.949-304.681 308.118-304.681 170.169 0 308.119 136.411 308.119 304.681C820.594 511.837 682.645 648.247 512.476 648.247L512.476 648.247zM512.476 100.186c-135.713 0-246.12 109.178-246.12 243.381 0 134.202 110.407 243.381 246.12 243.381 135.719 0 246.126-109.179 246.126-243.381C758.602 209.364 648.195 100.186 512.476 100.186L512.476 100.186zM935.867 985.115l-26.164 0c-9.648 0-17.779-6.941-19.384-16.35-2.646-15.426-6.277-30.52-11.142-44.95-24.769-87.686-81.337-164.13-159.104-214.266-63.232 35.203-134.235 53.64-207.597 53.64-73.555 0-144.73-18.537-208.084-53.922-78 50.131-134.75 126.68-159.564 214.549 0 0-4.893 18.172-11.795 46.4-2.136 8.723-10.035 14.9-19.112 14.9L88.133 985.116c-9.415 0-16.693-8.214-15.47-17.452C91.698 824.084 181.099 702.474 305.51 637.615c58.682 40.472 129.996 64.267 206.966 64.267 76.799 0 147.968-23.684 206.584-63.991 124.123 64.932 213.281 186.403 232.277 329.772C952.56 976.901 945.287 985.115 935.867 985.115L935.867 985.115z">
								</path>
							</svg>
						</a>
						<ul class="sub-menu">
							<?php if (!$this->session->userdata('user_userid')): ?>
								<li><a href="<?= base_url(); ?>user/in">Login</a></li>
								<li><a href="<?= base_url(); ?>user/re">Register</a></li>
							<?php else: ?>
								<li><a href="<?= base_url(); ?>user/in">Dashboard</a></li>
							<?php endif; ?>
						</ul>

					</div>
					<div class="mn-tool-wish">
						<a href="javascript:void(0)" class="mn-main-wishlist mn-wishlist-toggle">
							<span class="label lbl-1">0</span>
							<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="512" height="512" x="0" y="0"
								viewBox="0 0 512 512" style="enable-background:new 0 0 512 512" xml:space="preserve">
								<g>
									<path
										d="M474.644 74.27C449.391 45.616 414.358 29.836 376 29.836c-53.948 0-88.103 32.22-107.255 59.25-4.969 7.014-9.196 14.047-12.745 20.665-3.549-6.618-7.775-13.651-12.745-20.665-19.152-27.03-53.307-59.25-107.255-59.25-38.358 0-73.391 15.781-98.645 44.435C13.267 101.605 0 138.213 0 177.351c0 42.603 16.633 82.228 52.345 124.7 31.917 37.96 77.834 77.088 131.005 122.397 19.813 16.884 40.302 34.344 62.115 53.429l.655.574c2.828 2.476 6.354 3.713 9.88 3.713s7.052-1.238 9.88-3.713l.655-.574c21.813-19.085 42.302-36.544 62.118-53.431 53.168-45.306 99.085-84.434 131.002-122.395C495.367 259.578 512 219.954 512 177.351c0-39.138-13.267-75.746-37.356-103.081zM309.193 401.614c-17.08 14.554-34.658 29.533-53.193 45.646-18.534-16.111-36.113-31.091-53.196-45.648C98.745 312.939 30 254.358 30 177.351c0-31.83 10.605-61.394 29.862-83.245C79.34 72.007 106.379 59.836 136 59.836c41.129 0 67.716 25.338 82.776 46.594 13.509 19.064 20.558 38.282 22.962 45.659a15 15 0 0 0 28.524 0c2.404-7.377 9.453-26.595 22.962-45.66 15.06-21.255 41.647-46.593 82.776-46.593 29.621 0 56.66 12.171 76.137 34.27C471.395 115.957 482 145.521 482 177.351c0 77.007-68.745 135.588-172.807 224.263z"
										fill="#000000" opacity="1" data-original="#000000"></path>
								</g>
							</svg>
						</a>
					</div>
					<div class="mn-tool-cart">
						<a href="javascript:void(0)" class="mn-main-cart mn-cart-toggle">
							<span class="label lbl-2">0</span>
							<svg class="svg-icon" viewBox="0 0 1024 1024" version="1.1"
								xmlns="http://www.w3.org/2000/svg">
								<path
									d="M351.552 831.424c-35.328 0-63.968 28.64-63.968 63.968 0 35.328 28.64 63.968 63.968 63.968 35.328 0 63.968-28.64 63.968-63.968C415.52 860.064 386.88 831.424 351.552 831.424L351.552 831.424 351.552 831.424zM799.296 831.424c-35.328 0-63.968 28.64-63.968 63.968 0 35.328 28.64 63.968 63.968 63.968 35.328 0 63.968-28.64 63.968-63.968C863.264 860.064 834.624 831.424 799.296 831.424L799.296 831.424 799.296 831.424zM862.752 799.456 343.264 799.456c-46.08 0-86.592-36.448-92.224-83.008L196.8 334.592 165.92 156.128c-1.92-15.584-16.128-28.288-29.984-28.288L95.2 127.84c-17.664 0-32-14.336-32-31.968 0-17.664 14.336-32 32-32l40.736 0c46.656 0 87.616 36.448 93.28 83.008l30.784 177.792 54.464 383.488c1.792 14.848 15.232 27.36 28.768 27.36l519.488 0c17.696 0 32 14.304 32 31.968S880.416 799.456 862.752 799.456L862.752 799.456zM383.232 671.52c-16.608 0-30.624-12.8-31.872-29.632-1.312-17.632 11.936-32.928 29.504-34.208l433.856-31.968c15.936-0.096 29.344-12.608 31.104-26.816l50.368-288.224c1.28-10.752-1.696-22.528-8.128-29.792-4.128-4.672-9.312-7.04-15.36-7.04L319.04 223.84c-17.664 0-32-14.336-32-31.968 0-17.664 14.336-31.968 32-31.968l553.728 0c24.448 0 46.88 10.144 63.232 28.608 18.688 21.088 27.264 50.784 23.52 81.568l-50.4 288.256c-5.44 44.832-45.92 81.28-92 81.28L385.6 671.424C384.8 671.488 384 671.52 383.232 671.52L383.232 671.52zM383.232 671.52">
								</path>
							</svg>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</header>