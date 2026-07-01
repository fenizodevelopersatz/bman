<?php 
 $sitelogo = site_settings('image','logo');
 $darksitelogo = site_settings('image','logo');
?>


<div class="app-header-logo d-flex flex-shrink-0 align-items-center justify-content-between justify-content-lg-center">
<button class="btn btn-icon btn-color-gray-600 btn-active-color-primary ms-n3 me-2 d-flex d-lg-none" id="kt_app_sidebar_toggle">
<i class="ki-outline ki-abstract-14 fs-2"></i> </button>
<a href="<?php echo base_url();?>">
<img alt="Logo" src="<?php echo base_url(); ?>assets/images/<?php echo $sitelogo; ?>" class="h-30px h-lg-40px theme-light-show"/>
<img alt="Logo" src="<?php echo base_url(); ?>assets/images/<?php echo $darksitelogo; ?>" class="h-30px h-lg-40px theme-dark-show"/>
</a>
</div>

<div id="kt_app_header_menu_wrapper" class="d-flex align-items-center w-100">
                                <!--begin::Header menu-->
                                <div class="app-header-menu app-header-mobile-drawer align-items-start align-items-lg-center w-100" data-kt-drawer="true" data-kt-drawer-name="app-header-menu" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="250px"
                                    data-kt-drawer-direction="end" data-kt-drawer-toggle="#kt_app_header_menu_toggle" data-kt-swapper="true" data-kt-swapper-mode="{default: 'append', lg: 'prepend'}" data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_menu_wrapper'}">
                                    <!--begin::Menu-->
                                    <div class="
                                            menu 
                                            menu-rounded  
                                            menu-column 
                                            menu-lg-row 
                                            menu-active-bg
                                            menu-state-primary
                                            menu-title-gray-700 
                                            menu-arrow-gray-500 
                                            menu-bullet-gray-500

                                            my-5 
                                            my-lg-0 
                                            align-items-stretch 
                                            fw-semibold
                                            px-2 
                                            px-lg-0 
                                        " id="#kt_header_menu" data-kt-menu="true">
                                        <a href="<?php echo base_url()?>user/main"
                                        data-kt-menu-placement="bottom-start" data-kt-menu-offset="-100,0" class="menu-item here show menu-here-bg menu-lg-down-accordion me-0 me-lg-2">
                                       <span class="menu-link"><span  class="menu-title" ><?php echo lang('dashbaord'); ?> 
                                        </span><span class="menu-arrow d-lg-none"></span></span>

                                        </a>


                                <a href="<?php echo base_url()?>user/shop-list"
                                data-kt-menu-placement="bottom-start" data-kt-menu-offset="-100,0" class="menu-item here show menu-here-bg menu-lg-down-accordion me-0 me-lg-2">
                               <span class="menu-link"><span  class="menu-title" ><?php echo lang('shop'); ?> 
                                </span><span class=" d-lg-none"></span></span>
                                </a>



                                </div>

                                    
                                     <div class="
                                            menu 
                                            menu-rounded  
                                            menu-column 
                                            menu-lg-row 
                                            menu-active-bg
                                            menu-state-primary
                                            menu-title-gray-700 
                                            menu-arrow-gray-500 
                                            menu-bullet-gray-500

                                            my-5 
                                            my-lg-0 
                                            align-items-stretch 
                                            fw-semibold
                                            px-2 
                                            px-lg-0 
                                        " id="#kt_header_menu" data-kt-menu="true">
                                   
                                      

                                    </div>


                                    <!--end::Menu-->
                                </div>
                                <!--end::Header menu-->
                            </div>
                            <!--end::Menu wrapper-->





                            <div class="app-navbar flex-shrink-0">


                            <?php 
                            
                                $userid = $this->session->userdata('user_userid');
                                $userinfo = $this->db->query("SELECT * FROM users where id = '".$userid."' ")->row();

                                $username = $userinfo->username;
                                $useremail = $userinfo->email;
                            ?>
                              

                                <!--begin::User menu-->
                                <div class="app-navbar-item ms-3 ms-lg-5" id="kt_header_user_menu_toggle">
                                    <!--begin::Menu wrapper-->
                                    <div class="cursor-pointer symbol symbol-35px symbol-md-40px" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                                        <img class="symbol symbol-circle symbol-35px symbol-md-40px" src="<?php echo base_url();?>assets/user/media/avatars/300-13.jpg" alt="user" />
                                    </div>

                                    <!--begin::User account menu-->
                                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <div class="menu-content d-flex align-items-center px-3">
                                                <!--begin::Avatar-->
                                                <div class="symbol symbol-50px me-5">
                                                    <img alt="Logo" src="<?php echo base_url();?>assets/user/media/avatars/300-13.jpg" />
                                                </div>
                                                <!--end::Avatar-->

                                                <!--begin::Username-->
                                                <div class="d-flex flex-column">
                                                    <div class="fw-bold d-flex align-items-center fs-5">
                                                        <?php echo $username; ?> <span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">Demo Version</span>
                                                    </div>

                                                                                <a href="#" class="fw-semibold text-muted text-hover-primary fs-7">
                                                <?php echo $useremail; ?></a>
                                                                            </div>
                                                <!--end::Username-->
                                            </div>
                                        </div>
                                        <!--end::Menu item-->

                                        <!--begin::Menu separator-->
                                        <div class="separator my-2"></div>
                                        <!--end::Menu separator-->

                                        <!--begin::Menu item-->
                                        <div class="menu-item px-5">
                                            <a href="<?php echo base_url();?>user/view-profile" class="menu-link px-5">
                                                <?php echo lang('my_profile'); ?> 
                                            </a>
                                                                            </div>
                                        <!--end::Menu item-->



                                        <!--begin::Menu item-->
                                        <div class="menu-item px-5">
                                            <a href="#" class="menu-link px-5 demo-block">
                                               <?php echo lang('change_password'); ?>  
                                            </a>
                                                                            </div>
                                        <!--end::Menu item-->

                                        <!--begin::Menu separator-->
                                        <div class="separator my-2"></div>
                                        <!--end::Menu separator-->

                                        <!--begin::Menu item-->
                                        <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="left-start" data-kt-menu-offset="-15px, 0">
                                            <a href="#" class="menu-link px-5">
                                                <span class="menu-title position-relative">
                                                              <?php echo lang('mode'); ?>      

                                            <span class="ms-5 position-absolute translate-middle-y top-50 end-0">
                                                <i class="ki-outline ki-night-day theme-light-show fs-2"></i>                        <i class="ki-outline ki-moon theme-dark-show fs-2"></i>                    </span>
                                                                        </span>
                                                                    </a>

                                            <!--begin::Menu-->
                                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px" data-kt-menu="true" data-kt-element="theme-mode-menu">
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3 my-0">
                                                    <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                                                                                            <span class="menu-icon" data-kt-element="icon">
                                                    <i class="ki-outline ki-night-day fs-2"></i>            </span>
                                                                                            <span class="menu-title">
                                                    
                                                     <?php echo lang('Light'); ?>   
                                                </span>
                                                    </a>
                                                </div>
                                                <!--end::Menu item-->

                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3 my-0">
                                                    <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                                                        <span class="menu-icon" data-kt-element="icon">
                                                            <i class="ki-outline ki-moon fs-2"></i>            </span>
                                                                                                    <span class="menu-title">
                                                            
                                                             <?php echo lang('Dark'); ?>   
                                                        </span>
                                                    </a>
                                                </div>
                                                <!--end::Menu item-->

                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3 my-0">
                                                    <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                                                                                            <span class="menu-icon" data-kt-element="icon">
                                                    <i class="ki-outline ki-screen fs-2"></i>            </span>
                                                                                            <span class="menu-title">
                                                    
                                                      <?php echo lang('System'); ?>   
                                                </span>
                                                    </a>
                                                </div>
                                                <!--end::Menu item-->
                                            </div>
                                            <!--end::Menu-->

                                        </div>
                                        <!--end::Menu item-->

                                        <!--begin::Menu item-->
                                        <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="left-start" data-kt-menu-offset="-15px, 0">
                                            <a href="#" class="menu-link px-5">
                                                <span class="menu-title position-relative">
                                                     <?php echo lang('Language'); ?>

                                                    <span class="fs-8 rounded bg-light px-3 py-2 position-absolute translate-middle-y top-50 end-0">
                                                        English <img class="w-15px h-15px rounded-1 ms-2" src="<?php echo base_url();?>assets/user/media/flags/united-states.svg" alt=""/>
                                                    </span>
                                                                                    </span>
                                                                                </a>

                                                                                <!--begin::Menu sub-->
                                                                                <div class="menu-sub menu-sub-dropdown w-175px py-4">
                                                                                    <!--begin::Menu item-->
                                                                                    <div class="menu-item px-3">
                                                                                          <a href="<?= base_url('switch_language/english'); ?>" class="menu-link d-flex px-5">
                                                                                            <span class="symbol symbol-20px me-4">
                                                                                                <img data-kt-element="lang-flag" class="rounded-1" src="<?= base_url(); ?>assets/user/media/flags/united-states.svg" alt=""/>
                                                                                            </span>
                                                                                            <span data-kt-element="lang-name">English</span>
                                                                                        </a>
                                                                                    </div>
                                                                                    <!--end::Menu item-->

                                                                                    <!--begin::Menu item-->
                                                                                    <div class="menu-item px-3">
                                                                                         <a href="<?= base_url('switch_language/spanish'); ?>" class="menu-link d-flex px-5">
                                                                                            <span class="symbol symbol-20px me-4">
                                                                                                <img data-kt-element="lang-flag" class="rounded-1" src="<?= base_url(); ?>assets/user/media/flags/spain.svg" alt=""/>
                                                                                            </span>
                                                                                            <span data-kt-element="lang-name">Spanish</span>
                                                                                        </a>
                                                                                    </div>

                                                                                       <div class="menu-item px-3">
                                                                                            <a href="<?= base_url('switch_language/german'); ?>" class="menu-link d-flex px-5">
                                                                                            <span class="symbol symbol-20px me-4">
                                                                                                <img data-kt-element="lang-flag" class="rounded-1" src="<?= base_url(); ?>assets/user/media/flags/germany.svg" alt=""/>
                                                                                            </span>
                                                                                            <span data-kt-element="lang-name">German</span>
                                                                                        </a>
                                                                                        </div>
                                                                                    <!--end::Menu item-->
                                                                                </div>
                                                                                <!--end::Menu sub-->
                                                                            </div>
                                                                            <!--end::Menu item-->
                                                                            <!--begin::Menu item-->
                                                                            <div class="menu-item px-5 my-1">
                                                                                <a href="<?php echo base_url();?>user/kyc" class="menu-link px-5 ">
                                                                                       My KYC   
                                                                                    </a>
                                                                            </div>
                                                                            <!--end::Menu item-->
                                                                            <!--begin::Menu item-->
                                                                            <div class="menu-item px-5">
                                                                                <a href="<?php echo base_url();?>user/logout" class="menu-link px-5">
                                                                                     <?php echo lang('sign_out'); ?>   
                                                                                </a>
                                                                            </div>
                                    </div>
                                </div>

                                <div class="app-navbar-item d-lg-none ms-2 me-n3" title="Show header menu">
                                    <div class="btn btn-icon btn-custom btn-active-color-primary btn-color-gray-700 w-35px h-35px w-md-40px h-md-40px" id="kt_app_header_menu_toggle">
                                        <i class="ki-outline ki-text-align-left fs-1"></i> </div>
                                </div>
                            </div>

                            <script>
                                document.querySelectorAll('.demo-block').forEach(function(element) {
                                    element.addEventListener('click', function(e) {
                                        e.preventDefault();

                                        Swal.fire({
                                            icon: 'info',
                                            title: 'Demo Version',
                                            text: 'This action is disabled in the demo version for security reasons.',
                                            confirmButtonText: 'Ok, got it!',
                                            customClass: {
                                                confirmButton: 'btn btn-primary'
                                            },
                                            buttonsStyling: false
                                        });
                                    });
                                });
                            </script>
