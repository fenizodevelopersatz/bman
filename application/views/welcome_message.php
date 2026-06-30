<?php $this->load->view('user/layout/common_style'); ?>

<body>

<!-- backtotop - start -->
<div class="xb-backtotop">
    <a href="#" class="scroll">
        <i class="far fa-arrow-up"></i>
    </a>
</div>
<!-- backtotop - end -->

<!-- preloader start -->
<div class="preloader">
    <div class="loader">
        <div class="line-scale">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>
</div>
<!-- preloader end -->

<div class="body_wrap">

    <!-- header start -->
   <?php $this->load->view('user/layout/common_header'); ?>
    <!-- header end -->

    <!-- main area start  -->
    <main>
        <!-- hero section start  -->
        <section class="hero bg_img pos-rel pt-120" data-background="<?php echo base_url();?>assets/user/img/bg/hero-bg1.svg">
            <div class="hero-shape">
                <div class="shape--1">
                    <img class="leftToRight" src="<?php echo base_url();?>assets/user/img/shape/hero-sp_01.svg" alt="">
                </div>
                <div class="shape--2">
                    <img class="topToBottom" src="<?php echo base_url();?>assets/user/img/shape/hero-sp_02.svg" alt="">
                </div>
                <div class="shape--3">
                    <img class="leftToRight" src="<?php echo base_url();?>assets/user/img/shape/hero-sp_04.svg" alt="">
                </div>
                <div class="shape--4">
                    <img class="topToBottom" src="<?php echo base_url();?>assets/user/img/shape/usdt.png" alt="">
                </div>
                <div class="shape--5">
                    <img class="topToBottom" src="<?php echo base_url();?>assets/user/img/shape/hero-sp_05.svg" alt="">
                </div>
                <div class="shape--6">
                    <img class="leftToRight" src="<?php echo base_url();?>assets/user/img/shape/hero-sp_06.svg" alt="">
                </div>
            </div>
            <div class="container">
                <div class="hero__content-wrap">

                <div class="section-title hero--sec-titlt wow fadeInUp" data-wow-duration=".7s">
                    <?php  echo cms_content('hero_sectoin'); ?>
                </div>
                    
                    <div class="hero__btn btns pt-50 wow fadeInUp" data-wow-duration=".7s" data-wow-delay="350ms">
                          <a class="them-btn" href="#">
                            <span class="btn_label" data-text="Get Started">Get Started</span>
                            <span class="btn_icon">
                                <svg width="15" height="14" viewbox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M14.434 0.999999C14.434 0.447714 13.9862 -8.61581e-07 13.434 -1.11446e-06L4.43396 -3.13672e-07C3.88168 -6.50847e-07 3.43396 0.447715 3.43396 0.999999C3.43396 1.55228 3.88168 2 4.43396 2L12.434 2L12.434 10C12.434 10.5523 12.8817 11 13.434 11C13.9862 11 14.434 10.5523 14.434 10L14.434 0.999999ZM2.14107 13.7071L14.1411 1.70711L12.7269 0.292893L0.726853 12.2929L2.14107 13.7071Z" fill="white"></path>
                                  </svg>
                            </span>
                          </a>
                        <a href="#" class="them-btn btn-transparent">
                            <span class="btn_label" data-text=" Whitepaper">Whitepaper</span>
                            <span class="btn_icon">
                                <svg width="15" height="14" viewbox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M14.434 0.999999C14.434 0.447714 13.9862 -8.61581e-07 13.434 -1.11446e-06L4.43396 -3.13672e-07C3.88168 -6.50847e-07 3.43396 0.447715 3.43396 0.999999C3.43396 1.55228 3.88168 2 4.43396 2L12.434 2L12.434 10C12.434 10.5523 12.8817 11 13.434 11C13.9862 11 14.434 10.5523 14.434 10L14.434 0.999999ZM2.14107 13.7071L14.1411 1.70711L12.7269 0.292893L0.726853 12.2929L2.14107 13.7071Z" fill="white"></path>
                                  </svg>
                            </span>
                        </a>
                    </div>
                </div>
                <div class="token-structure mt-145 wow fadeInUp" data-wow-duration=".7s" data-wow-delay="450ms">
                    <div class="row">
                        <div class="col-lg-5">
                            <div class="hero-token">
                                <h3 class="xb-item--title"><?php  echo cms_content('token_structure'); ?></h3>
                                <p class="xb-item--content">
                                <?php  echo cms_content('token_structure_dec'); ?>
                                </p>
                               
                                <div class="xb-item--accept">
                                    <h5 class="xb-item--acc-title">We accept :</h5>
                                    <ul class="xb-item--list ul_li">
                                        <li><img style="width:40px" src="<?php echo base_url();?>assets/user/img/icon/USDT.png" alt="">USDT</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                        <div class="hero-sale">
                            <div class="xb-item--sale_service ul_li_between">
                            <?php  echo cms_content('pre_sale_box_title'); ?>
                            </div>
                            <div class="xb-item--line ul_li_between">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                            <div class="xb-item--progress">
                                <div class="xb-item--pro-color"><span class="shape"></span></div>
                            </div>
                            <div class="xb-item--target ul_li_between">
                            <?php  echo cms_content('pre_sale_box_value'); ?>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
                <div class="hero-scroll pt-105">
                    <span>scroll to down</span>
                    <div class="scroll-down text-center">
                        <div class="chevron"></div>
                        <div class="chevron"></div>
                        <div class="chevron"></div>
                      </div>
                </div>
            </div>
        </section>
        <!-- hero section end  -->

        <!-- about section start-->
        <section class="about pos-rel pb-105 wow slideInLeft" data-wow-duration="1.3s" data-wow-delay="450ms" id="about" style="overflow: hidden;">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="about-wrap pt-140 wow fadeInLeft" data-wow-duration=".7s">

                          <?php  echo cms_content('vission_mission_title_content'); ?>

                          <?php  echo cms_content('vission_mission_list'); ?>
                            
                        </div>
                    </div>
                </div>
            </div>
            <div class="about-img bg_img">
                <img class="wow fadeInRight" data-wow-duration=".7s" data-wow-delay="200ms" src="<?php echo base_url();?>assets/user/img/about/about-img.png" alt="">
            </div>
        </section>
        <!-- about section end-->

        <!-- process start -->
        <section class="process z-3 pb-150 pt-35 wow slideInDown" data-wow-duration="1.3s" data-wow-delay="450ms">
            <div class="container pt-100">
                <div class="row justify-content-center mt-none-130">
                    <div class="col-xl-4 col-lg-6 process-col mt-130">
                        <div class="xb-process pos-rel">
                            <div class="xb-item--icon">
                                <img src="<?php echo base_url();?>assets/user/img/icon/process_icon1.svg" alt="">
                            </div>
                            <div class="xb-item--holder">
                             <?php  echo cms_content('register_title_content'); ?>
                            </div>
                            <div class="xb-item--shape">
                                <span>
                                    <svg width="410" height="274" viewbox="0 0 410 274" fill="none" xmlns="http://www.w3.org/2000/svg" preserveaspectratio="none">
                                        <path d="M302.5 0C220.1 55.6 135.5 23.1667 103.5 0L0 274H410L302.5 0Z" fill="url(#p_shape1)"></path>
                                        <defs>
                                            <radialgradient id="p_shape1" cx="0" cy="0" r="1" gradientunits="userSpaceOnUse" gradienttransform="translate(205 12) rotate(90) scale(611.5 749.061)">
                                                <stop offset="0" stop-color="#EBF7FD"></stop>
                                                <stop offset="0.09" stop-color="#9162FF"></stop>
                                                <stop offset="0.26792" stop-color="#1C30A8"></stop>
                                                <stop offset="0.474094" stop-color="#080B18"></stop>
                                            </radialgradient>
                                        </defs>
                                    </svg>                                    
                                </span>
                                
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-6 process-col mt-130">
                        <div class="xb-process pos-rel">
                            <div class="xb-item--icon">
                                <img src="<?php echo base_url();?>assets/user/img/icon/process_icon2.svg" alt="">
                            </div>
                            <div class="xb-item--holder">
                            <?php  echo cms_content('deposit_title_content'); ?>
                            </div>
                            <div class="xb-item--shape">
                                <span>
                                    <svg width="410" height="274" viewbox="0 0 410 274" fill="none" xmlns="http://www.w3.org/2000/svg" preserveaspectratio="none">
                                        <path d="M302.5 0C220.1 55.6 135.5 23.1667 103.5 0L0 274H410L302.5 0Z" fill="url(#p_shape2)"></path>
                                        <defs>
                                            <radialgradient id="p_shape2" cx="0" cy="0" r="1" gradientunits="userSpaceOnUse" gradienttransform="translate(205 12) rotate(90) scale(611.5 749.061)">
                                                <stop offset="0" stop-color="#EBF7FD"></stop>
                                                <stop offset="0.09" stop-color="#9162FF"></stop>
                                                <stop offset="0.26792" stop-color="#1C30A8"></stop>
                                                <stop offset="0.474094" stop-color="#080B18"></stop>
                                            </radialgradient>
                                        </defs>
                                    </svg>                                    
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-6 process-col mt-130">
                        <div class="xb-process pos-rel">
                            <div class="xb-item--icon">
                                <img src="<?php echo base_url();?>assets/user/img/icon/process_icon3.svg" alt="">
                            </div>
                            <div class="xb-item--holder">
                            <?php  echo cms_content('lend_title_content'); ?>
                            </div>
                            <div class="xb-item--shape">
                                <span>
                                    <svg width="410" height="274" viewbox="0 0 410 274" fill="none" xmlns="http://www.w3.org/2000/svg" preserveaspectratio="none">
                                        <path d="M302.5 0C220.1 55.6 135.5 23.1667 103.5 0L0 274H410L302.5 0Z" fill="url(#p_shape3)"></path>
                                        <defs>
                                            <radialgradient id="p_shape3" cx="0" cy="0" r="1" gradientunits="userSpaceOnUse" gradienttransform="translate(205 12) rotate(90) scale(611.5 749.061)">
                                                <stop offset="0" stop-color="#EBF7FD"></stop>
                                                <stop offset="0.09" stop-color="#9162FF"></stop>
                                                <stop offset="0.26792" stop-color="#1C30A8"></stop>
                                                <stop offset="0.474094" stop-color="#080B18"></stop>
                                            </radialgradient>
                                        </defs>
                                    </svg>                                    
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- process end -->

        <!-- token section start  -->
        <section id="project" class="token z-1 mt-70 pt-150 pb-150 bg_img pos-rel wow slideInRight" data-wow-duration="2.3s" data-wow-delay="450ms" data-background="<?php echo base_url();?>assets/user/img/bg/token-bg.png">
            <div class="container">
                <div class="section-title pb-55">
                <?php  echo cms_content('director_title'); ?>
                </div>
                <div class="token-wrap">
                    <div class="row mt-none-30 g-0">
                        <div class="col-xl-12 mt-30">
                            <div class="token-distribut prj-out">
                                <div class="row g-3">
                                  <?php  echo cms_content('director_list'); ?>

                                </div>
                            </div>
                        </div>
                    
                    </div>
                </div>
            </div>
            <div class="toke-shape">
                <div class="shape--one">
                    <img class="leftToRight" src="<?php echo base_url();?>assets/user/img/shape/token.svg" alt="">
                </div>
                <div class="shape--two">
                    <img class="topToBottom" src="<?php echo base_url();?>assets/user/img/shape/token1.svg" alt="">
                </div>
            </div>
        </section>
        <!-- token section end -->
        
        <!-- roadmap section start -->
        <section id="roadmap" class="roadmap pt-135 wow slideInDown" data-wow-duration="1.3s" data-wow-delay="450ms">
            <div class="section-title pb-50">
                <?php  echo cms_content('road_map_tilte'); ?>
            </div>
            <div class="roadmap-wrap">
                <div class="roadmap--top">
                    <?php  echo cms_content('road_map_list_2027'); ?>
                </div>
                <div class="roadmap--line"></div>
                <div class="roadmap--bottom position-relative" style="z-index: 99; position: relative;">
                    <?php  echo cms_content('road_map_list_2025_28'); ?>
                </div>
            </div>
        </section>
        <!-- roadmap section end -->

        <!-- feature section start -->
        <section id="features" class="feature pos-rel pt-175 mb-170 wow slideInLeft" data-wow-duration="1.3s" data-wow-delay="450ms">
            <div class="container">
                <div class="section-title pb-65">
                <?php  echo cms_content('features_title'); ?>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="feature-wrap ul_li">
                            <div class="xb-item--holder">
                              <?php  echo cms_content('mobile_content'); ?>
                            </div>
                            <div class="xb-item--feature-icon">
                                <img src="<?php echo base_url();?>assets/user/img/feature/fea-01.svg" alt="">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="feature-wrap ul_li">
                            <div class="xb-item--holder">
                                <?php  echo cms_content('security_content'); ?>
                            </div>
                            <div class="xb-item--feature-icon">
                                <img src="<?php echo base_url();?>assets/user/img/feature/fea-02.svg" alt="">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="feature-wrap ul_li">
                            <div class="xb-item--holder">
                            <?php  echo cms_content('transaction_content'); ?>
                            </div>
                            <div class="xb-item--feature-icon">
                                <img src="<?php echo base_url();?>assets/user/img/feature/fea-03.svg" alt="">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="feature-wrap ul_li">
                            <div class="xb-item--holder">
                            <?php  echo cms_content('protect_indentity'); ?>
                            </div>
                            <div class="xb-item--feature-icon">
                                <img src="<?php echo base_url();?>assets/user/img/feature/fea-04.svg" alt="">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="feature-crypto bg_img" id="download" data-background="<?php echo base_url();?>assets/user/img/bg/feature-bg.png">
                    <div class="row align-items-end">
                        <div class="col-lg-6">
                            <div class="mobile-crypto">
                                <div class="xb-item--sub-title">
                                    <span><svg width="16" height="20" viewbox="0 0 16 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M14.6448 8.99798C14.223 8.02696 13.6099 7.15543 12.8438 6.43787L12.2116 5.84453C12.1901 5.82499 12.1643 5.81106 12.1363 5.80409C12.1084 5.79704 12.0792 5.79721 12.0513 5.80443C12.0234 5.81165 11.9978 5.82584 11.9764 5.84555C11.9551 5.86535 11.9389 5.89016 11.9292 5.91786L11.6468 6.74682C11.4708 7.26683 11.1471 7.79797 10.6886 8.32018C10.6582 8.35349 10.6235 8.36241 10.5996 8.36462C10.5757 8.36683 10.5388 8.36241 10.5062 8.33132C10.4758 8.30464 10.4606 8.26462 10.4627 8.22459C10.5431 6.88685 10.1521 5.37789 9.29609 3.73562C8.58788 2.37114 7.60377 1.30668 6.3741 0.564432L5.47683 0.0244217C5.35957 -0.0466972 5.2096 0.0466409 5.21615 0.186644L5.26398 1.25334C5.29653 1.98225 5.21402 2.62671 5.01842 3.16227C4.77949 3.81784 4.43622 4.42675 3.99735 4.97343C3.69198 5.35333 3.34581 5.69703 2.96549 5.9979C2.04933 6.71827 1.3044 7.64137 0.786445 8.69796C0.269767 9.7638 0.000628769 10.9373 0 12.127C0 13.1759 0.202039 14.1914 0.601783 15.1492C0.987762 16.0714 1.54477 16.9083 2.24201 17.6137C2.94587 18.3248 3.76276 18.8849 4.67303 19.2738C5.61592 19.6782 6.61524 19.8826 7.64719 19.8826C8.67913 19.8826 9.67845 19.6782 10.6213 19.276C11.5293 18.8894 12.3551 18.3255 13.0523 17.6159C13.7563 16.9048 14.3081 16.0737 14.6925 15.1514C15.0917 14.1963 15.2965 13.1679 15.2944 12.1292C15.2944 11.0447 15.0771 9.99135 14.6448 8.99798Z" fill="#FF0000"></path>
                                      </svg>
                                      <?php  echo cms_content('apk_verision'); ?>
                                    </span>
                                </div>
                                <?php  echo cms_content('apk_info'); ?>
                                <ul class="xb-item--crypto-list">
                                     <?php  echo cms_content('apk_info_list'); ?>
                                </ul>
                                <div class="xb-item--crypto-btn">
                                    <a class="them-btn crp-btn" href="#!">
                                        <span class="btn_icon">
                                            <i class="fab fa-apple"></i>
                                        </span>
                                        <span class="btn_label" data-text="Apple iOS">Apple iOS</span>
                                      </a>
                                    <a class="them-btn crp-btn" href="#!">
                                        <span class="btn_icon"><svg width="21" height="14" viewbox="0 0 21 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M0.398804 12.1266C0.537847 10.5267 1.04394 9.05395 1.91712 7.70827C2.78967 6.3626 3.95204 5.29345 5.40423 4.50098L3.68942 1.63014C3.59672 1.49556 3.57354 1.35352 3.61989 1.204C3.66624 1.05447 3.76666 0.942338 3.92114 0.867577C4.04473 0.792815 4.18378 0.777861 4.33826 0.822713C4.49276 0.867577 4.61635 0.957281 4.70904 1.09186L6.42386 3.96269C7.75246 3.42441 9.14288 3.15528 10.5951 3.15528C12.0472 3.15528 13.4377 3.42441 14.7662 3.96269L16.4811 1.09186C16.5738 0.957281 16.6974 0.867577 16.8518 0.822713C17.0063 0.777861 17.1454 0.792815 17.269 0.867577C17.4235 0.942338 17.5239 1.05447 17.5702 1.204C17.6165 1.35352 17.5934 1.49556 17.5007 1.63014L15.7859 4.50098C17.238 5.29345 18.4007 6.3626 19.2739 7.70827C20.1464 9.05395 20.6523 10.5267 20.7913 12.1266V13.826H0.398804V12.1266ZM6.78336 9.3339C6.55904 9.55096 6.28467 9.6595 5.96025 9.6595C5.63581 9.6595 5.36175 9.55096 5.13805 9.3339C4.91374 9.1174 4.80158 8.85207 4.80158 8.53814C4.80158 8.22409 4.91374 7.95888 5.13805 7.74238C5.36175 7.5252 5.63581 7.41666 5.96025 7.41666C6.28467 7.41666 6.55904 7.5252 6.78336 7.74238C7.00706 7.95888 7.11891 8.22409 7.11891 8.53814C7.11891 8.85207 7.00706 9.1174 6.78336 9.3339ZM16.0527 9.3339C15.8283 9.55096 15.5539 9.6595 15.2296 9.6595C14.9051 9.6595 14.6311 9.55096 14.4074 9.3339C14.1831 9.1174 14.071 8.85207 14.071 8.53814C14.071 8.22409 14.1831 7.95888 14.4074 7.74238C14.6311 7.5252 14.9051 7.41666 15.2296 7.41666C15.5539 7.41666 15.8283 7.5252 16.0527 7.74238C16.2764 7.95888 16.3882 8.22409 16.3882 8.53814C16.3882 8.85207 16.2764 9.1174 16.0527 9.3339Z" fill="#080B18"></path>
                                          </svg></span>
                                        <span class="btn_label" data-text="Android">Android</span>
                                      </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="cry-mobile-img">
                                <img src="<?php echo base_url();?>assets/user/img/feature/fea-mobile.png" alt="">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="feature-shape align-items-center">
                <img src="<?php echo base_url();?>assets/user/img/feature/fea-color-sp.png" alt="">
            </div>
        </section>
        <!-- feature section end -->

        <!-- team & faq section start -->
        <div class="bg_img top-center pos-rel pb-145 wow slideInRight" data-wow-duration="1.3s" data-wow-delay="450ms" id="team" data-background="<?php echo base_url();?>assets/user/img/bg/team-bg.png">
             <!-- team section start -->
            <section class="team pt-140">
            <?php  echo cms_content('our_team_info'); ?>
            </section>
            <!-- team section end -->
            
            <!-- faq start -->
            <section class="faq pt-130 wow slideInDown" data-wow-duration="1.3s" data-wow-delay="450ms" id="faq">
                <div class="container">
                    <div class="section-title pb-55 wow fadeInUp" data-wow-duration=".7s">
                        <h1 class="title">Have Any Questions?</h1>
                    </div>
                    <div class="faq__blockchain wow fadeInUp" data-wow-duration=".7s" data-wow-delay="200ms">
                        <ul class="accordion_box clearfix">

                        <?php $faq_query = $this->db->query("SELECT * FROM faqs where status = '1' ")->result(); ?>

                        <?php $i=0; if(count($faq_query) > 0){  foreach($faq_query as $faq_row){ $i++; ?>

                            <li class="accordion block  <?php echo $i == "2" ? "active-block" : "" ?>">
                                <div class="acc-btn">
                                    <?php echo $faq_row->question; ?>
                                    <span class="arrow"><span></span></span>
                                </div>
                                <div class="acc_body <?php echo $i == "2" ? "current" : "" ?> ">
                                    <div class="content">
                                    <?php echo $faq_row->answer; ?>
                                    </div>
                                </div>
                            </li>

                            <?php } } ?>
                
                          
                        </ul>
                    </div>
                </div>
            </section>
            <!-- faq end -->
            <div class="team-shape">
                <div class="shape shape--1">
                    <img class="leftToRight" src="<?php echo base_url();?>assets/user/img/shape/team-sp_01.svg" alt="">
                </div>
                <div class="shape shape--2">
                    <img class="topToBottom" src="<?php echo base_url();?>assets/user/img/shape/team-sp_02.svg" alt="">
                </div>
                <div class="shape shape--3">
                    <img class="leftToRight" src="<?php echo base_url();?>assets/user/img/shape/team-sp_03.svg" alt="">
                </div>
                <div class="shape shape--4">
                    <img class="topToBottom" src="<?php echo base_url();?>assets/user/img/shape/team-sp_04.svg" alt="">
                </div>
            </div>
        </div>
        <!-- team & faq section end -->
<?php $this->load->view('user/layout/common_footer'); ?>    
 </main>
    <!-- main area end  -->
    
</div>

<!-- jquery include -->
<script src="<?php echo base_url();?>assets/user/js/jquery-3.7.1.min.js"></script>
<script src="<?php echo base_url();?>assets/user/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo base_url();?>assets/user/js/swiper.min.js"></script>
<script src="<?php echo base_url();?>assets/user/js/wow.min.js"></script>
<script src="<?php echo base_url();?>assets/user/js/appear.js"></script>
<script src="<?php echo base_url();?>assets/user/js/odometer.min.js"></script>
<script src="<?php echo base_url();?>assets/user/js/jquery.magnific-popup.min.js"></script>
<script src="<?php echo base_url();?>assets/user/js/easing.min.js"></script>
<script src="<?php echo base_url();?>assets/user/js/scrollspy.js"></script>
<script src="<?php echo base_url();?>assets/user/js/countdown.js"></script>
<script src="<?php echo base_url();?>assets/user/js/parallax-scroll.js"></script>
<script src="<?php echo base_url();?>assets/user/js/main.js"></script>

</body>

</html>
