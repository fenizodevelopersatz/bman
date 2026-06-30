<?php $this->load->view('user/layout/common_style'); ?>

    <body id="kt_body" data-bs-spy="scroll" data-bs-target="#kt_landing_menu" class="bg-body position-relative app-blank">

        <div class="d-flex flex-column flex-root" id="kt_app_root">
            <!--begin::Header Section-->
            <div class="mb-0" id="home">
                <!--begin::Wrapper-->
                <div class="bgi-no-repeat bgi-size-contain bgi-position-x-center bgi-position-y-bottom landing-dark-bg" style="background-image: url(<?php echo base_url();?>assets/user/media/svg/illustrations/landing.svg)">

                    <?php $this->load->view('user/layout/common_header'); ?>

                        <!--begin::Landing hero-->
                        <div class="d-flex flex-column flex-center w-100 min-h-350px min-h-lg-500px px-9">
                            <!--begin::Heading-->
                            <div class="text-center mb-5 mb-lg-10 py-10 py-lg-20">
                                <h1 class="text-white lh-base fw-bold fs-2x fs-lg-3x mb-15">
            Empower Your Business Growth <br/>
            with 
            <span style="background: linear-gradient(to right, #12CE5D 0%, #FFD80C 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                <span id="kt_landing_hero_text"></span>
            </span>
        </h1>

                                <a href="<?php echo base_url();?>user/in" class="btn btn-primary">Get Started Today</a>
                            </div>

                            <div class="d-flex flex-center flex-wrap position-relative px-5">

                            </div>






                        </div>
                        <!--end::Wrapper-->

                        <!--begin::Curve bottom-->
                        <div class="landing-curve landing-dark-color mb-10 mb-lg-20">
                            <svg viewBox="15 12 1470 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0 11C3.93573 11.3356 7.85984 11.6689 11.7725 12H1488.16C1492.1 11.6689 1496.04 11.3356 1500 11V12H1488.16C913.668 60.3476 586.282 60.6117 11.7725 12H0V11Z" fill="currentColor"></path>
                            </svg>
                        </div>
                        <!--end::Curve bottom-->
                </div>
                <!--end::Header Section-->

                <!--begin::How It Works Section-->
                <div class="mb-n10 mb-lg-n20 z-index-2">
                    <div class="container">
                        <!--begin::Heading-->
                        <div class="text-center mb-17">
                            <h3 class="fs-2hx text-gray-900 mb-5" id="how-it-works" data-kt-scroll-offset="{default: 100, lg: 150}">How Fenizo MLM Works</h3>
                            <div class="fs-5 text-muted fw-bold">
                                Streamline your direct-selling business with Fenizo MLM Software
                                <br/> and experience fully automated plan management.
                            </div>
                        </div>
                        <!--end::Heading-->

                        <div class="row w-100 gy-10 mb-md-20">
                            <!-- Step 1 -->
                            <div class="col-md-4 px-5">
                                <div class="text-center mb-10 mb-md-0">
                                    <img src="<?php echo base_url();?>assets/user/media/illustrations/sketchy-1/2.png" class="mh-125px mb-9" alt="" />
                                    <div class="d-flex flex-center mb-5">
                                        <span class="badge badge-circle badge-light-success fw-bold p-5 me-3 fs-3">1</span>
                                        <div class="fs-5 fs-lg-3 fw-bold text-gray-900">
                                            Register & Onboard
                                        </div>
                                    </div>
                                    <div class="fw-semibold fs-6 fs-lg-4 text-muted">
                                        Easily onboard users, manage KYC, and assign plans
                                        <br/> with a smooth, automated registration process.
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2 -->
                            <div class="col-md-4 px-5">
                                <div class="text-center mb-10 mb-md-0">
                                    <img src="<?php echo base_url();?>assets/user/media/illustrations/sketchy-1/8.png" class="mh-125px mb-9" alt="" />
                                    <div class="d-flex flex-center mb-5">
                                        <span class="badge badge-circle badge-light-success fw-bold p-5 me-3 fs-3">2</span>
                                        <div class="fs-5 fs-lg-3 fw-bold text-gray-900">
                                            Set Up MLM Plans
                                        </div>
                                    </div>
                                    <div class="fw-semibold fs-6 fs-lg-4 text-muted">
                                        Choose from Binary, Unilevel, Matrix and more.
                                        <br/> Configure commission rules and hierarchies.
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3 -->
                            <div class="col-md-4 px-5">
                                <div class="text-center mb-10 mb-md-0">
                                    <img src="<?php echo base_url();?>assets/user/media/illustrations/sketchy-1/12.png" class="mh-125px mb-9" alt="" />
                                    <div class="d-flex flex-center mb-5">
                                        <span class="badge badge-circle badge-light-success fw-bold p-5 me-3 fs-3">3</span>
                                        <div class="fs-5 fs-lg-3 fw-bold text-gray-900">
                                            Track Growth & Earnings
                                        </div>
                                    </div>
                                    <div class="fw-semibold fs-6 fs-lg-4 text-muted">
                                        Monitor user activity, downlines, and commissions
                                        <br/> in real-time through a powerful dashboard.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Slider section remains same -->
                        <div class="tns tns-default">
                            <div data-tns="true" data-tns-loop="true" data-tns-swipe-angle="false" data-tns-speed="2000"
                             data-tns-autoplay="true" data-tns-autoplay-timeout="18000" data-tns-controls="true" data-tns-nav="false" 
                             data-tns-items="1" data-tns-center="false" data-tns-dots="false"
                            data-tns-prev-button="#kt_team_slider_prev1" data-tns-next-button="#kt_team_slider_next1">

                                <div class="text-center px-5 pt-5 pt-lg-10 px-lg-10">
                                    <img src="<?php echo base_url();?>assets/user/media/preview/dashboard.png" class="card-rounded shadow mh-lg-650px mw-100" alt="" />
                                </div>
                                <div class="text-center px-5 pt-5 pt-lg-10 px-lg-10">
                                    <img src="<?php echo base_url();?>assets/user/media/preview/multi-language.png" class="card-rounded shadow mh-lg-650px mw-100" alt="" />
                                </div>
                                <div class="text-center px-5 pt-5 pt-lg-10 px-lg-10">
                                    <img src="<?php echo base_url();?>assets/user/media/preview/package.png" class="card-rounded shadow mh-lg-650px mw-100" alt="" />
                                </div>
                                <div class="text-center px-5 pt-5 pt-lg-10 px-lg-10">
                                    <img src="<?php echo base_url();?>assets/user/media/preview/regsiter.png" class="card-rounded shadow mh-lg-650px mw-100" alt="" />
                                </div>

                                  <div class="text-center px-5 pt-5 pt-lg-10 px-lg-10">
                                    <img src="<?php echo base_url();?>assets/user/media/preview/referral.png" class="card-rounded shadow mh-lg-650px mw-100" alt="" />
                                </div>
                                <div class="text-center px-5 pt-5 pt-lg-10 px-lg-10">
                                    <img src="<?php echo base_url();?>assets/user/media/preview/support.png" class="card-rounded shadow mh-lg-650px mw-100" alt="" />
                                </div>
                                <div class="text-center px-5 pt-5 pt-lg-10 px-lg-10">
                                    <img src="<?php echo base_url();?>assets/user/media/preview/validation.png" class="card-rounded shadow mh-lg-650px mw-100" alt="" />
                                </div>

                            </div>

                            <button class="btn btn-icon btn-active-color-primary" id="kt_team_slider_prev1">
                                <i class="ki-outline ki-left fs-2x"></i>
                            </button>
                            <button class="btn btn-icon btn-active-color-primary" id="kt_team_slider_next1">
                                <i class="ki-outline ki-right fs-2x"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!--end::How It Works Section-->


                <!--begin::Statistics Section-->
                <div class="mt-sm-n10">
                    <!--begin::Curve top-->
                    <div class="landing-curve landing-dark-color ">
                        <svg viewBox="15 -1 1470 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 48C4.93573 47.6644 8.85984 47.3311 12.7725 47H1489.16C1493.1 47.3311 1497.04 47.6644 1501 48V47H1489.16C914.668 -1.34764 587.282 -1.61174 12.7725 47H1V48Z" fill="currentColor"></path>
                        </svg>
                    </div>
                    <!--end::Curve top-->

                    <!--begin::Wrapper-->
                    <div class="pb-15 pt-18 landing-dark-bg">
                        <!--begin::Container-->
                        <div class="container">
                            <!--begin::Heading-->
                            <div class="text-center mt-15 mb-18" id="achievements" data-kt-scroll-offset="{default: 100, lg: 150}">
                                <!--begin::Title-->
                                <h3 class="fs-2hx text-white fw-bold mb-5">Empowering MLM Success</h3>
                                <!--end::Title-->

                                <!--begin::Description-->
                                <div class="fs-5 text-gray-700 fw-bold">
                                    At <a href="https://fenizotechnologies.com" class="text-primary fw-bold">Fenizo Technologies</a>, our advanced MLM software helps you streamline operations,
                                    <br/> save costs, and manage your business from one powerful dashboard.
                                </div>
                                <!--end::Description-->
                            </div>
                            <!--end::Heading-->

                            <!--begin::Statistics-->
                            <div class="d-flex flex-center">
                                <!--begin::Items-->
                                <div class="d-flex flex-wrap flex-center justify-content-lg-between mb-15 mx-auto w-xl-900px">

                                    <!--begin::Item-->
                                    <div class="d-flex flex-column flex-center h-200px w-200px h-lg-250px w-lg-250px m-3 bgi-no-repeat bgi-position-center bgi-size-contain" style="background-image: url('<?php echo base_url();?>assets/user/media/svg/misc/octagon.svg')">
                                        <i class="ki-outline ki-element-11 fs-2tx text-white mb-3"></i>
                                        <div class="mb-0">
                                            <div class="fs-lg-2hx fs-2x fw-bold text-white d-flex flex-center">
                                                <div class="min-w-70px" data-kt-countup="true" data-kt-countup-value="1400" data-kt-countup-suffix="+">0</div>
                                            </div>
                                            <span class="text-gray-600 fw-semibold fs-5 lh-0">Clients Worldwide</span>
                                        </div>
                                    </div>
                                    <!--end::Item-->

                                    <!--begin::Item-->
                                    <div class="d-flex flex-column flex-center h-200px w-200px h-lg-250px w-lg-250px m-3 bgi-no-repeat bgi-position-center bgi-size-contain" style="background-image: url('<?php echo base_url();?>assets/user/media/svg/misc/octagon.svg')">
                                        <i class="ki-outline ki-chart-pie-4 fs-2tx text-white mb-3"></i>
                                        <div class="mb-0">
                                            <div class="fs-lg-2hx fs-2x fw-bold text-white d-flex flex-center">
                                                <div class="min-w-70px" data-kt-countup="true" data-kt-countup-value="50" data-kt-countup-suffix="K+">0</div>
                                            </div>
                                            <span class="text-gray-600 fw-semibold fs-5 lh-0">Monthly Reports Generated</span>
                                        </div>
                                    </div>
                                    <!--end::Item-->

                                    <!--begin::Item-->
                                    <div class="d-flex flex-column flex-center h-200px w-200px h-lg-250px w-lg-250px m-3 bgi-no-repeat bgi-position-center bgi-size-contain" style="background-image: url('<?php echo base_url();?>assets/user/media/svg/misc/octagon.svg')">
                                        <i class="ki-outline ki-basket fs-2tx text-white mb-3"></i>
                                        <div class="mb-0">
                                            <div class="fs-lg-2hx fs-2x fw-bold text-white d-flex flex-center">
                                                <div class="min-w-70px" data-kt-countup="true" data-kt-countup-value="10" data-kt-countup-suffix="M+">0</div>
                                            </div>
                                            <span class="text-gray-600 fw-semibold fs-5 lh-0">Secure Transactions Processed</span>
                                        </div>
                                    </div>
                                    <!--end::Item-->

                                </div>
                                <!--end::Items-->
                            </div>
                            <!--end::Statistics-->

                            <!--begin::Testimonial-->
                            <div class="fs-2 fw-semibold text-muted text-center mb-3">
                                <span class="fs-1 lh-1 text-gray-700">“</span> We build with passion so your MLM business
                                <br/><span class="text-gray-700 me-1">scales with precision and trust</span>
                                <span class="fs-1 lh-1 text-gray-700">“</span>
                            </div>
                            <!--end::Testimonial-->

                            <!--begin::Author-->
                            <div class="fs-2 fw-semibold text-muted text-center">
                                <a href="https://fenizotechnologies.com" class="link-primary fs-4 fw-bold">Fenizo Technologies,</a>
                                <span class="fs-4 fw-bold text-gray-600">MLM Software Experts</span>
                            </div>
                            <!--end::Author-->
                        </div>
                        <!--end::Container-->
                    </div>

                    <!--end::Wrapper-->

                    <!--begin::Curve bottom-->

                    <!--end::Curve bottom-->
                </div>
                <!--end::Statistics Section-->




            </div>
            <!--end::Tab pane-->
        </div>
        <!--end::Tabs content-->
        </div>
        <!--end::Card body-->
        </div>
        <!--end::Card-->
        </div>
        <!--end::Container-->
        </div>
        <!--end::Projects Section-->


        <!--begin::Pricing Section-->
        <div class="mt-sm-n20">
            <!--begin::Curve top-->
            <div class="landing-curve landing-dark-color ">
                <svg viewBox="15 -1 1470 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 48C4.93573 47.6644 8.85984 47.3311 12.7725 47H1489.16C1493.1 47.3311 1497.04 47.6644 1501 48V47H1489.16C914.668 -1.34764 587.282 -1.61174 12.7725 47H1V48Z" fill="currentColor"></path>
                </svg>
            </div>
            <!--end::Curve top-->



            <!--begin::Curve bottom-->
            <div class="landing-curve landing-dark-color ">
                <svg viewBox="15 12 1470 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0 11C3.93573 11.3356 7.85984 11.6689 11.7725 12H1488.16C1492.1 11.6689 1496.04 11.3356 1500 11V12H1488.16C913.668 60.3476 586.282 60.6117 11.7725 12H0V11Z" fill="currentColor"></path>
                </svg>
            </div>
            <!--end::Curve bottom-->
        </div>
        <!--end::Pricing Section-->



        <!--begin::Testimonials Section-->
        <div class="mt-20 mb-n20 position-relative z-index-2">
        <div class="container text-center">
            <h3 class="fs-2hx text-gray-900 mb-5" id="clients">What Our Clients Say</h3>
            <p class="fs-5 text-muted fw-bold mb-10">
            Fenizo MLM Software by Fenizo Technologies helps businesses grow faster and smarter.
            </p>

            <div class="row g-lg-10 mb-15">
            <div class="col-lg-4">
                <div class="p-4 border rounded shadow-sm">
                <div class="mb-3 text-warning">
                    ★★★★★
                </div>
                <p class="text-gray-700 fs-5 mb-4">
                    "Fenizo MLM Software transformed our business management. User-friendly and powerful!"
                </p>
                <div class="d-flex align-items-center">
                    <img src="https://randomuser.me/api/portraits/men/45.jpg" alt="John Doe" class="rounded-circle me-3" width="50" height="50" />
                    <div class="text-start">
                    <strong>John Doe</strong><br />
                    CEO, GrowthCorp
                    </div>
                </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="p-4 border rounded shadow-sm">
                <div class="mb-3 text-warning">
                    ★★★★★
                </div>
                <p class="text-gray-700 fs-5 mb-4">
                    "Reliable and efficient MLM software. Support from Fenizo Technologies is top-notch."
                </p>
                <div class="d-flex align-items-center">
                    <img src="https://randomuser.me/api/portraits/women/65.jpg" alt="Jane Smith" class="rounded-circle me-3" width="50" height="50" />
                    <div class="text-start">
                    <strong>Jane Smith</strong><br />
                    Marketing Head, BizRise
                    </div>
                </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="p-4 border rounded shadow-sm">
                <div class="mb-3 text-warning">
                    ★★★★★
                </div>
                <p class="text-gray-700 fs-5 mb-4">
                    "Fenizo MLM Software simplified our network management and boosted sales significantly."
                </p>
                <div class="d-flex align-items-center">
                    <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Steve Brown" class="rounded-circle me-3" width="50" height="50" />
                    <div class="text-start">
                    <strong>Steve Brown</strong><br />
                    Operations Manager, NetSales
                    </div>
                </div>
                </div>
            </div>
            </div>

            <div class="d-flex flex-stack flex-wrap flex-md-nowrap card-rounded shadow p-8 p-lg-12 mb-n5 mb-lg-n13" style="background: linear-gradient(90deg, #20AA3E 0%, #03A588 100%);">
                <div class="my-2 me-5">
                    <div class="fs-1 fs-lg-2qx fw-bold text-white mb-2">
                        Start With Fenizo MLM Software Today,
                        <span class="fw-normal">Boost Your Network Marketing Business!</span>
                    </div>

                    <div class="fs-6 fs-lg-5 text-white fw-semibold opacity-75">
                        Join thousands of satisfied clients using Fenizo MLM Software by Fenizo Technologies to grow and manage their business efficiently.
                    </div>
                </div>

                <a href="https://www.fenizotechnologies.com/best-mlm-software" target="_blank" class="btn btn-lg btn-outline border-2 btn-outline-white flex-shrink-0 my-2">
                    Explore Fenizo MLM Software
                </a>
            </div>

        </div>
        </div>


        <?php  $this->load->view('user/layout/common_footer');?>


            <?php $this->load->view('user/layout/common_script'); ?>
                <script src="<?php echo base_url();?>assets/user/plugins/custom/fslightbox/fslightbox.bundle.js"></script>
                <script src="<?php echo base_url();?>assets/user/plugins/custom/typedjs/typedjs.bundle.js"></script>
                <script src="<?php echo base_url();?>assets/user/js/custom/landing.js"></script>
                <script src="<?php echo base_url();?>assets/user/js/custom/pages/pricing/general.js"></script>

                <script>
                    const text = "Fenizo MLM Software";
                    const target = document.getElementById("kt_landing_hero_text");
    
                    let index = 0;
    
                    function typeEffect() {
                        if (index < text.length) {
                            target.innerHTML += text.charAt(index);
                            index++;
                            setTimeout(typeEffect, 100); // typing speed in ms
                        }
                    }
    
                    window.onload = typeEffect;
                </script>


    </body>

    </html>