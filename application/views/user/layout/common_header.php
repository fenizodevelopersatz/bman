<?php 
$logo = site_settings('image','logo');
$dark_logo = site_settings('dark_logo','logo');
?>

<style>
.xb-item--cryptos-btn.ul_li {
  display: none;
}
@media (max-width: 767px) {
  .xb-item--cryptos-btn.ul_li {
    display: flex;
  }
}

.btn-downloads {
    width: 142px !important;
    font-size: 16px;
    font-weight: 600;
    color: #ebf7fd;
    border: 1px solid #41445c;
    height: 50px;
    width: 102px;
    border-radius: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    -webkit-transition: 0.3s;
    -o-transition: 0.3s;
    transition: 0.3s;
    margin-left: 9px;
}
.login-btn i {
    color: var(--color-primary);
    padding-right: 6px;
    -webkit-transition: 0.3s;
    -o-transition: 0.3s;
    transition: 0.3s;
}
</style>

  <!--begin::Header-->
	<div class="landing-header" 
    data-kt-sticky="true"
	data-kt-sticky-name="landing-header"
	data-kt-sticky-offset="{default: '200px', lg: '300px'}">
    
    <!--begin::Container-->
    <div class="container">
        <!--begin::Wrapper-->
        <div class="d-flex align-items-center justify-content-between">
            <!--begin::Logo-->
            <div class="d-flex align-items-center flex-equal">
                <!--begin::Mobile menu toggle-->
                <button class="btn btn-icon btn-active-color-primary me-3 d-flex d-lg-none" id="kt_landing_menu_toggle">
                    <i class="ki-outline ki-abstract-14 fs-2hx"></i>                </button>
                <!--end::Mobile menu toggle-->

                <!--begin::Logo image-->
                <a href="landing.html">
                    <img alt="Logo" src="<?php echo base_url();?>assets/images/<?php echo $logo; ?>" class="logo-default h-25px h-lg-30px"/>
                    <img alt="Logo" src="<?php echo base_url();?>assets/images/<?php echo $logo; ?>" class="logo-sticky h-20px h-lg-25px"/>
                </a>
                <!--end::Logo image-->
            </div>
            <!--end::Logo-->

            <!--begin::Menu wrapper-->
            <div class="d-lg-block" id="kt_header_nav_wrapper">
                <div 
                    class="d-lg-block p-5 p-lg-0"

                    data-kt-drawer="true"
                    data-kt-drawer-name="landing-menu"
                    data-kt-drawer-activate="{default: true, lg: false}"
                    data-kt-drawer-overlay="true"
                    data-kt-drawer-width="200px"
                    data-kt-drawer-direction="start"
                    data-kt-drawer-toggle="#kt_landing_menu_toggle"
                    
                    data-kt-swapper="true"
                    data-kt-swapper-mode="prepend"
                    data-kt-swapper-parent="{default: '#kt_body', lg: '#kt_header_nav_wrapper'}">

                    <!--begin::Menu-->
                    <div class="menu menu-column flex-nowrap menu-rounded menu-lg-row menu-title-gray-600 menu-state-title-primary nav nav-flush fs-5 fw-semibold" id="kt_landing_menu">
                                                <!--begin::Menu item-->
                        <div class="menu-item">
                            <!--begin::Menu link-->
                            <a class="menu-link nav-link active py-3 px-4 px-xxl-6" href="#kt_body" data-kt-scroll-toggle="true" data-kt-drawer-dismiss="true">
                                Home                            </a>
                            <!--end::Menu link-->
                        </div>
                        <!--end::Menu item-->
                                                <!--begin::Menu item-->
                        <div class="menu-item">
                            <!--begin::Menu link-->
                            <a class="menu-link nav-link  py-3 px-4 px-xxl-6" href="#how-it-works" data-kt-scroll-toggle="true" data-kt-drawer-dismiss="true">
                                How it Works                            </a>
                            <!--end::Menu link-->
                        </div>
                        <!--end::Menu item-->
                                                <!--begin::Menu item-->
                        <div class="menu-item">
                            <!--begin::Menu link-->
                            <a class="menu-link nav-link  py-3 px-4 px-xxl-6" href="#achievements" data-kt-scroll-toggle="true" data-kt-drawer-dismiss="true">
                                Achievements                            </a>
                            <!--end::Menu link-->
                        </div>
                        <!--end::Menu item-->
                                   
                                            </div>
                    <!--end::Menu-->
                </div>
            </div>
            <!--end::Menu wrapper-->

            <!--begin::Toolbar-->
            <div class="flex-equal text-end ms-1">
                <a href="<?php echo base_url();?>user/in" class="btn btn-success">Get Start</a>
            </div>
            <!--end::Toolbar-->
        </div>
        <!--end::Wrapper-->
    </div>
    <!--end::Container-->
</div>
<!--end::Header-->    
