<?php 
$logo = site_settings('image','logo');
$dark_logo = site_settings('dark_logo','logo');
?>

<footer class="m-t-15 sb-hide">
  <div class="mn-footer">
    <div class="footer-container">
      <div class="footer-top p-tb-30">
        <div class="row m-minus-991">
          <!-- Brand / Short SEO blurb -->
          <div class="col-sm-12 col-lg-3 mn-footer-cat">
            <div class="mn-footer-widget mn-footer-company">
              <img src="<?php echo base_url();?>assets/images/<?php echo $logo; ?>" class="mn-footer-logo" alt="Fenizo MLM Software logo">
              <img src="<?php echo base_url();?>assets/images/<?php echo $logo; ?>" class="mn-footer-dark-logo" alt="Fenizo MLM Software logo dark">
              <p class="mn-footer-detail">
                Fenizo MLM Software helps you launch & scale Binary, Matrix, Unilevel, Generation and Gift/Donation plans with secure payouts and real-time dashboards.
              </p>
              <div class="mn-app-store">
                <a href="javascript:void(0)" class="app-img" aria-label="Get it on Android">
                  <img src="<?php echo base_url(); ?>assets/shop/img/footer/android.png" class="adroid" alt="Android app">
                </a>
                <a href="javascript:void(0)" class="app-img" aria-label="Download on the App Store">
                  <img src="<?php echo base_url(); ?>assets/shop/img/footer/apple.png" class="apple" alt="Apple app">
                </a>
              </div>
            </div>
          </div>

          <!-- BLOG (SEO targets) -->
          <div class="col-sm-12 col-lg-3 mn-footer-info">
            <div class="mn-footer-widget">
              <h4 class="mn-footer-heading">Blog</h4>
              <div class="mn-footer-links mn-footer-dropdown">
                <ul class="align-items-center">
                  <li class="mn-footer-link">
                    <a href="<?php echo base_url('matrix-mlm'); ?>">Matrix MLM: Structure & Payouts</a>
                  </li>
                  <li class="mn-footer-link">
                    <a href="<?php echo base_url('australia-mlm'); ?>">Australia MLM: Compliance & Software Guide</a>
                  </li>
                  <li class="mn-footer-link">
                    <a href="<?php echo base_url('gift-mlm'); ?>">MLM Gift (Donation) Plan: Pros & Cons</a>
                  </li>
                  <li class="mn-footer-link">
                    <a href="<?php echo base_url('top-6-mlm-plans'); ?>">Top 6 MLM Plans: Full Comparison</a>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- TOP 6 MLM PLANS -->
          <div class="col-sm-12 col-lg-3 mn-footer-service">
            <div class="mn-footer-widget">
              <h4 class="mn-footer-heading">Top 6 MLM Plans</h4>
              <div class="mn-footer-links mn-footer-dropdown">
                <ul class="align-items-center">
                  <li class="mn-footer-link"><a href="#">Binary Plan</a></li>
                  <li class="mn-footer-link"><a href="#">Matrix Plan</a></li>
                  <li class="mn-footer-link"><a href="#">Unilevel Plan</a></li>
                  <li class="mn-footer-link"><a href="#">Generation / Breakaway</a></li>
                  <li class="mn-footer-link"><a href="#">Board / Cycler Plan</a></li>
                  <li class="mn-footer-link"><a href="#">Gift / Donation Plan</a></li>
                </ul>
              </div>
            </div>
          </div>

          <!-- COMPANY (only About & Privacy as requested) -->
          <div class="col-sm-12 col-lg-3 mn-footer-account">

            <!-- Contact + Social (kept as-is; edit content/links as needed) -->
            <div class="mn-footer-contact m-t-20">
              <div class="mn-footer-widget">
                <h4 class="mn-footer-heading">Contact</h4>
                <div class="mn-footer-links mn-footer-dropdown">
                  <ul class="align-items-center">
                    <li class="mn-footer-link mn-foo-location">
                      <span class="mt-15px"><i class="ri-map-pin-line" aria-hidden="true"></i></span>
                      <p>plot no:77, Nehru St, Sathyamoorthy Nagar, State Bank Supervisors Colony, Madurai, Tamil Nadu 625016</p>
                    </li>
                    <li class="mn-footer-link mn-foo-call">
                      <span><i class="ri-whatsapp-line" aria-hidden="true"></i></span>
                      <a href="tel:+919443218385">+91 944 321 8385</a>
                    </li>
                    <li class="mn-footer-link mn-foo-mail">
                      <span><i class="ri-mail-line" aria-hidden="true"></i></span>
                      <a href="mailto:sales@fenizotechnologies.com">sales@fenizotechnologies.com</a>
                    </li>
                  </ul>
                </div>
              </div>
              <div class="mn-footer-social mt-6" style="margin-top:10px;">
                <div class="mn-footer-widget">
                  <div class="mn-footer-links mn-footer-dropdown">
                    <ul class="align-items-center">
                      <li class="mn-footer-link"><a href="https://www.facebook.com/profile.php?id=61576309678710" aria-label="Facebook"><i class="ri-facebook-fill" aria-hidden="true"></i></a></li>
                      <li class="mn-footer-link"><a href="https://twitter.com/FenizoTechs" aria-label="Twitter"><i class="ri-twitter-fill" aria-hidden="true"></i></a></li>
                      <li class="mn-footer-link"><a href="https://www.linkedin.com/company/fenizo-technologies-it/about/" aria-label="LinkedIn"><i class="ri-linkedin-fill" aria-hidden="true"></i></a></li>
                      <li class="mn-footer-link"><a href="https://www.instagram.com/fenizotech/" aria-label="Instagram"><i class="ri-instagram-line" aria-hidden="true"></i></a></li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

          </div><!-- /company + contact col -->
        </div>
      </div>

      <div class="footer-bottom">
        <div class="row">
          <div class="mn-bottom-info">
            <div class="footer-copy">
              <div class="footer-bottom-copy">
                <div class="mn-copy">
                  Copyright © <span id="copyright_year"></span>
                  <a class="site-name" href="https://www.fenizotechnologies.com/best-mlm-software">Fenizo MLM Software</a> all rights reserved.
                </div>
              </div>
            </div>
            <div class="footer-bottom-right">
              <div class="footer-bottom-payment d-flex justify-content-center">
                <div class="payment-link">
                  <img src="<?php echo base_url(); ?>assets/shop/img/footer/payment.png" alt="Payment methods">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</footer>

<script>
  // dynamic year
  (function() {
    var el = document.getElementById('copyright_year');
    if (el) el.textContent = new Date().getFullYear();
  })();
</script>

<!-- Toast stays unchanged -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
  <div id="toastNotify" class="toast toast-custom align-items-center border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex align-items-center">
      <div class="toast-icon px-3">
        <i class="fa-solid fa-circle-check text-white fs-5"></i>
      </div>
      <div class="toast-body text-white" id="toast-message">Message here</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>


<div class="mn-footer-nav">
			<ul>
				<li>
					<a href="<?php echo base_url();?>user/shop-list" class="mn-main-search mn-search-toggle">
						<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="512" height="512" x="0" y="0"
							viewBox="0 0 612.01 612.01" style="enable-background:new 0 0 512 512" xml:space="preserve">
							<g>
								<path
									d="M606.209 578.714 448.198 423.228C489.576 378.272 515 318.817 515 253.393 514.98 113.439 399.704 0 257.493 0S.006 113.439.006 253.393s115.276 253.393 257.487 253.393c61.445 0 117.801-21.253 162.068-56.586l158.624 156.099c7.729 7.614 20.277 7.614 28.006 0a19.291 19.291 0 0 0 .018-27.585zM257.493 467.8c-120.326 0-217.869-95.993-217.869-214.407S137.167 38.986 257.493 38.986c120.327 0 217.869 95.993 217.869 214.407S377.82 467.8 257.493 467.8z"
									fill="#000000" opacity="1" data-original="#000000"></path>
							</g>
						</svg>
					</a>
				</li>
				<li>
					<a href="<?= base_url(); ?>user/in" class="mn-main-user">
						<svg class="svg-icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg">
							<path
								d="M512.476 648.247c-170.169 0-308.118-136.411-308.118-304.681 0-168.271 137.949-304.681 308.118-304.681 170.169 0 308.119 136.411 308.119 304.681C820.594 511.837 682.645 648.247 512.476 648.247L512.476 648.247zM512.476 100.186c-135.713 0-246.12 109.178-246.12 243.381 0 134.202 110.407 243.381 246.12 243.381 135.719 0 246.126-109.179 246.126-243.381C758.602 209.364 648.195 100.186 512.476 100.186L512.476 100.186zM935.867 985.115l-26.164 0c-9.648 0-17.779-6.941-19.384-16.35-2.646-15.426-6.277-30.52-11.142-44.95-24.769-87.686-81.337-164.13-159.104-214.266-63.232 35.203-134.235 53.64-207.597 53.64-73.555 0-144.73-18.537-208.084-53.922-78 50.131-134.75 126.68-159.564 214.549 0 0-4.893 18.172-11.795 46.4-2.136 8.723-10.035 14.9-19.112 14.9L88.133 985.116c-9.415 0-16.693-8.214-15.47-17.452C91.698 824.084 181.099 702.474 305.51 637.615c58.682 40.472 129.996 64.267 206.966 64.267 76.799 0 147.968-23.684 206.584-63.991 124.123 64.932 213.281 186.403 232.277 329.772C952.56 976.901 945.287 985.115 935.867 985.115L935.867 985.115z">
							</path>
						</svg>
					</a>
				</li>
				<li>
					<a href="<?= base_url(); ?>" class="">
						<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="512" height="512" x="0" y="0"
							viewBox="0 0 24 24" style="enable-background:new 0 0 512 512" xml:space="preserve" class="">
							<g>
								<path
									d="M19 22.42H5c-2.07 0-3.75-1.68-3.75-3.75v-8.44c0-1.22.6-2.37 1.6-3.07l7-4.9c1.29-.9 3.02-.9 4.3 0l7 4.9c1 .7 1.6 1.85 1.6 3.07v8.44c0 2.07-1.68 3.75-3.75 3.75zM12 3.08c-.45 0-.9.14-1.29.41l-7 4.9c-.6.42-.96 1.11-.96 1.84v8.44c0 1.24 1.01 2.25 2.25 2.25h14c1.24 0 2.25-1.01 2.25-2.25v-8.44c0-.73-.36-1.42-.96-1.84l-7-4.9c-.39-.27-.84-.4-1.29-.4z"
									fill="#000000" opacity="1" data-original="#000000"></path>
								<path
									d="M16 22.42H8c-.41 0-.75-.34-.75-.75v-5c0-2.62 2.13-4.75 4.75-4.75s4.75 2.13 4.75 4.75v5c0 .41-.34.75-.75.75zm-7.25-1.5h6.5v-4.25c0-1.79-1.46-3.25-3.25-3.25s-3.25 1.46-3.25 3.25z"
									fill="#000000" opacity="1" data-original="#000000"></path>
							</g>
						</svg>
					</a>
				</li>
				<li>
					<a href="javascript:void(0)" class="mn-main-wishlist mn-wishlist-toggle">
						<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="512" height="512" x="0" y="0"
							viewBox="0 0 512 512" style="enable-background:new 0 0 512 512" xml:space="preserve">
							<g>
								<path
									d="M474.644 74.27C449.391 45.616 414.358 29.836 376 29.836c-53.948 0-88.103 32.22-107.255 59.25-4.969 7.014-9.196 14.047-12.745 20.665-3.549-6.618-7.775-13.651-12.745-20.665-19.152-27.03-53.307-59.25-107.255-59.25-38.358 0-73.391 15.781-98.645 44.435C13.267 101.605 0 138.213 0 177.351c0 42.603 16.633 82.228 52.345 124.7 31.917 37.96 77.834 77.088 131.005 122.397 19.813 16.884 40.302 34.344 62.115 53.429l.655.574c2.828 2.476 6.354 3.713 9.88 3.713s7.052-1.238 9.88-3.713l.655-.574c21.813-19.085 42.302-36.544 62.118-53.431 53.168-45.306 99.085-84.434 131.002-122.395C495.367 259.578 512 219.954 512 177.351c0-39.138-13.267-75.746-37.356-103.081zM309.193 401.614c-17.08 14.554-34.658 29.533-53.193 45.646-18.534-16.111-36.113-31.091-53.196-45.648C98.745 312.939 30 254.358 30 177.351c0-31.83 10.605-61.394 29.862-83.245C79.34 72.007 106.379 59.836 136 59.836c41.129 0 67.716 25.338 82.776 46.594 13.509 19.064 20.558 38.282 22.962 45.659a15 15 0 0 0 28.524 0c2.404-7.377 9.453-26.595 22.962-45.66 15.06-21.255 41.647-46.593 82.776-46.593 29.621 0 56.66 12.171 76.137 34.27C471.395 115.957 482 145.521 482 177.351c0 77.007-68.745 135.588-172.807 224.263z"
									fill="#000000" opacity="1" data-original="#000000"></path>
							</g>
						</svg>
					</a>
				</li>
				<li>
					<a href="javascript:void(0)" class="mn-main-cart mn-cart-toggle">
						<svg class="svg-icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg">
							<path
								d="M351.552 831.424c-35.328 0-63.968 28.64-63.968 63.968 0 35.328 28.64 63.968 63.968 63.968 35.328 0 63.968-28.64 63.968-63.968C415.52 860.064 386.88 831.424 351.552 831.424L351.552 831.424 351.552 831.424zM799.296 831.424c-35.328 0-63.968 28.64-63.968 63.968 0 35.328 28.64 63.968 63.968 63.968 35.328 0 63.968-28.64 63.968-63.968C863.264 860.064 834.624 831.424 799.296 831.424L799.296 831.424 799.296 831.424zM862.752 799.456 343.264 799.456c-46.08 0-86.592-36.448-92.224-83.008L196.8 334.592 165.92 156.128c-1.92-15.584-16.128-28.288-29.984-28.288L95.2 127.84c-17.664 0-32-14.336-32-31.968 0-17.664 14.336-32 32-32l40.736 0c46.656 0 87.616 36.448 93.28 83.008l30.784 177.792 54.464 383.488c1.792 14.848 15.232 27.36 28.768 27.36l519.488 0c17.696 0 32 14.304 32 31.968S880.416 799.456 862.752 799.456L862.752 799.456zM383.232 671.52c-16.608 0-30.624-12.8-31.872-29.632-1.312-17.632 11.936-32.928 29.504-34.208l433.856-31.968c15.936-0.096 29.344-12.608 31.104-26.816l50.368-288.224c1.28-10.752-1.696-22.528-8.128-29.792-4.128-4.672-9.312-7.04-15.36-7.04L319.04 223.84c-17.664 0-32-14.336-32-31.968 0-17.664 14.336-31.968 32-31.968l553.728 0c24.448 0 46.88 10.144 63.232 28.608 18.688 21.088 27.264 50.784 23.52 81.568l-50.4 288.256c-5.44 44.832-45.92 81.28-92 81.28L385.6 671.424C384.8 671.488 384 671.52 383.232 671.52L383.232 671.52zM383.232 671.52">
							</path>
						</svg>
					</a>
				</li>
			</ul>
		</div>