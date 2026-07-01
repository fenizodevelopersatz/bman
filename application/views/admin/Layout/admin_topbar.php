<?php
$sitelogo = site_settings('image', 'logo');
?>
<script>
    window.APP_CONFIG = window.APP_CONFIG || {};
    window.APP_CONFIG.DEMO = <?= (defined('DEMOVERSION') && DEMOVERSION) ? 'true' : 'false' ?>;
    function isDemoMode() { return !!(window.APP_CONFIG && window.APP_CONFIG.DEMO === true); }
    function demoBlockAlert() { Swal.fire({ icon: 'info', title: 'Demo Version', text: 'You Can not change record.', confirmButtonText: 'Ok, got it!', customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false }); }
    function stopIfDemo(e) { if (!isDemoMode()) return false; if (e && e.preventDefault) e.preventDefault(); demoBlockAlert(); return true; }
</script>
<style>
    .cke_notification_warning {
        display: none !important;
    }

    .cke_notifications {
        display: none !important;
    }
</style>
<div id="kt_app_header" class="app-header " data-kt-sticky="true" data-kt-sticky-activate="{default: true, lg: true}"
    data-kt-sticky-name="app-header-minimize" data-kt-sticky-offset="{default: '200px', lg: '0'}"
    data-kt-sticky-animation="false">

    <div class="app-container  container-fluid d-flex align-items-stretch justify-content-between "
        id="kt_app_header_container">

        <!--begin::Sidebar mobile toggle-->
        <div class="d-flex align-items-center d-lg-none ms-n3 me-1 me-md-2" title="Show sidebar menu">
            <div class="btn btn-icon btn-active-color-primary w-35px h-35px" id="kt_app_sidebar_mobile_toggle">
                <i class="ki-duotone ki-abstract-14 fs-2 fs-md-1"><span class="path1"></span><span
                        class="path2"></span></i>
            </div>
        </div>
        <!--end::Sidebar mobile toggle-->


        <!--begin::Mobile logo-->
        <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
            <a href="<?php echo base_url('admin'); ?>" class="d-lg-none">
                <img alt="Logo"
                    src="<?php echo base_url('assets/images/nexman_technologies_software_company_logo.jpg'); ?>"
                    class="h-30px">
            </a>
        </div>
        <!--end::Mobile logo-->


        <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1" id="kt_app_header_wrapper">
            <div class="
                            app-header-menu 
                            app-header-mobile-drawer 
                            align-items-stretch
                            " data-kt-drawer="true" data-kt-drawer-name="app-header-menu"
                data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true"
                data-kt-drawer-width="250px" data-kt-drawer-direction="end"
                data-kt-drawer-toggle="#kt_app_header_menu_toggle" data-kt-swapper="true"
                data-kt-swapper-mode="{default: 'append', lg: 'prepend'}"
                data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_wrapper'}">
                <div class="
                            menu 
                            menu-rounded  
                            menu-column 
                            menu-lg-row 
                            my-5 
                            my-lg-0 
                            align-items-stretch 
                            fw-semibold
                            px-2 px-lg-0
                            " id="kt_app_header_menu" data-kt-menu="true">
                </div>
            </div>


            <!--begin::Navbar-->
            <div class="app-navbar flex-shrink-0">


                <!--begin::Theme mode-->
                <div class="app-navbar-item ms-1 ms-md-4">

                    <!--begin::Menu toggle-->
                    <a href="#"
                        class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px"
                        data-kt-menu-trigger="{default:'click', lg: 'hover'}" data-kt-menu-attach="parent"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-duotone ki-night-day theme-light-show fs-1"><span class="path1"></span><span
                                class="path2"></span><span class="path3"></span><span class="path4"></span><span
                                class="path5"></span><span class="path6"></span><span class="path7"></span><span
                                class="path8"></span><span class="path9"></span><span class="path10"></span></i> <i
                            class="ki-duotone ki-moon theme-dark-show fs-1"><span class="path1"></span><span
                                class="path2"></span></i></a>
                    <!--begin::Menu toggle-->

                    <!--begin::Menu-->
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px"
                        data-kt-menu="true" data-kt-element="theme-mode-menu">
                        <!--begin::Menu item-->
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-duotone ki-night-day fs-2"><span class="path1"></span><span
                                            class="path2"></span><span class="path3"></span><span
                                            class="path4"></span><span class="path5"></span><span class="path6"></span>
                                        <span class="path7"></span><span class="path8"></span><span
                                            class="path9"></span><span class="path10"></span></i>
                                </span>
                                <span class="menu-title">
                                    Light
                                </span>
                            </a>
                        </div>
                        <!--end::Menu item-->

                        <!--begin::Menu item-->
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-duotone ki-moon fs-2"><span class="path1"></span><span
                                            class="path2"></span></i>
                                </span>
                                <span class="menu-title">
                                    Dark
                                </span>
                            </a>
                        </div>
                        <!--end::Menu item-->

                        <!--begin::Menu item-->
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-duotone ki-screen fs-2"><span class="path1"></span><span
                                            class="path2"></span><span class="path3"></span><span
                                            class="path4"></span></i>
                                </span>
                                <span class="menu-title">
                                    System
                                </span>
                            </a>
                        </div>
                        <!--end::Menu item-->
                    </div>
                    <!--end::Menu-->

                </div>
                <!--end::Theme mode-->

                <!--begin::User menu-->
                <div class="app-navbar-item ms-1 ms-md-4" id="kt_header_user_menu_toggle">
                    <!--begin::Menu wrapper-->
                    <div class="cursor-pointer symbol symbol-35px bg-info ps-3"
                        data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent"
                        data-kt-menu-placement="bottom-end">
                        <img src="<?php echo base_url(); ?>assets/images/<?php echo $sitelogo; ?>" class="rounded-3"
                            alt="user">
                    </div>

                    <!--begin::User account menu-->
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px"
                        data-kt-menu="true">
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <div class="menu-content d-flex align-items-center px-3">
                                <!--begin::Avatar-->
                                <div class="symbol symbol-50px me-5">
                                    <img alt="Logo"
                                        src="<?php echo base_url(); ?>assets/images/<?php echo $sitelogo; ?>">
                                </div>
                                <!--end::Avatar-->

                                <!--begin::Username-->
                                <div class="d-flex flex-column">
                                    <div class="fw-bold d-flex align-items-center fs-5">
                                        Admin <span
                                            class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">Online</span>
                                    </div>


                                </div>
                                <!--end::Username-->
                            </div>
                        </div>
                        <!--end::Menu item-->

                        <!--begin::Menu separator-->
                        <div class="separator my-2"></div>

                        <div class="menu-item px-5 my-1">
                            <a href="#" class="menu-link px-5 demo-block">
                                Profile
                            </a>
                        </div>
                        <div class="menu-item px-5">
                            <a href="#" class="menu-link px-5 demo-block">
                                Change Password
                            </a>
                        </div>

                        <div class="menu-item px-5">
                            <a href="<?php echo base_url(); ?>logout" class="menu-link px-5">
                                Logout
                            </a>
                        </div>

                    </div>
                    <!--end::User account menu-->

                    <!--end::Menu wrapper-->
                </div>
                <!--end::User menu-->

                <!--begin::Header menu toggle-->
                <div class="app-navbar-item d-lg-none ms-2 me-n2" title="Show header menu">
                    <div class="btn btn-flex btn-icon btn-active-color-primary w-30px h-30px"
                        id="kt_app_header_menu_toggle">
                        <i class="ki-duotone ki-element-4 fs-1"><span class="path1"></span><span
                                class="path2"></span></i>
                    </div>
                </div>
                <!--end::Header menu toggle-->

                <!--begin::Aside toggle-->
                <!--end::Header menu toggle-->
            </div>
            <!--end::Navbar-->
        </div>
        <!--end::Header wrapper-->
    </div>
    <!--end::Header container-->




</div>
<!--end::Header-->

<script>
    document.querySelectorAll('.demo-block').forEach(function (element) {
        element.addEventListener('click', function (e) {
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
<script>
    document.addEventListener("DOMContentLoaded", function () {
        setTimeout(function () {
            document.querySelectorAll('.cke_notification_warning').forEach(el => el.remove());
        }, 500);
    });
</script>