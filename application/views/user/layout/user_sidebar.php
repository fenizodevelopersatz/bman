<?php
$uri = $this->uri->uri_string();
$isLending = ($uri == 'user/lending');
$isTransfer = ($uri == 'user/tranfer');
$isSwap = ($uri == 'user/swap');
$isGenealogy = ($uri == 'user/genealogy');
$isProfit = ($uri == 'user/profit');
$isSupport = ($uri == 'user/support');
$isWallet = ($uri == 'user/wallet');
$isReferral = ($uri == 'user/referral');
$dex_wallet = ($uri == 'user/dex-wallet');
$isLendingHistory = (
    $uri === 'user/lending-history' ||
    ($this->uri->segment(1) === 'user' && $this->uri->segment(2) === 'info' && is_numeric($this->uri->segment(3)))
);
?>


<div id="kt_app_sidebar" class="app-sidebar  flex-column " data-kt-drawer="true" data-kt-drawer-name="app-sidebar"
    data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="275px"
    data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_toggle">

    <!--begin::Sidebar nav-->
    <div class="app-sidebar-wrapper py-8 py-lg-10" id="kt_app_sidebar_wrapper">
        <!--begin::Nav wrapper-->
        <div id="kt_app_sidebar_nav_wrapper" class="d-flex flex-column px-8 px-lg-10 hover-scroll-y"
            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
            data-kt-scroll-dependencies="{default: false, lg: '#kt_app_header'}"
            data-kt-scroll-wrappers="#kt_app_sidebar, #kt_app_sidebar_wrapper"
            data-kt-scroll-offset="{default: '10px', lg: '40px'}">

            <!--begin::Links-->
            <div class="mb-0">
                <!--begin::Title-->
                <h3 class="text-gray-800 fw-bold mb-8"> <?php echo lang('short_links'); ?></h3>
                <!--end::Title-->

                <!--begin::Row-->
                <div class="row g-5" data-kt-buttons="true" data-kt-buttons-target="[data-kt-button]">
                    <!--begin::Col-->
                    <div class="col-6">
                        <!--begin::Link-->
                        <a href="<?php echo base_url(); ?>user/lending"
                            class="btn btn-icon btn-outline btn-bg-light  btn-flex flex-column 
                                                flex-center w-100px h-100px border-gray-200 
                                                <?php echo $isLending ? 'active border-primary btn-active-light-primary' : ''; ?>" data-kt-button="true">
                            <!--begin::Icon-->
                            <span class="mb-2">
                                <i class="ki-duotone ki-chart fs-1  ">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <!--begin::Label-->
                            <span class="fs-7 fw-bold"> Lending</span>
                            <!--end::Label-->
                        </a>
                        <!--end::Link-->
                    </div>

                    <div class="col-6">
                        <!--begin::Link-->
                        <a href="<?php echo base_url(); ?>user/lending-history"
                            class="btn btn-icon btn-outline btn-bg-light  btn-flex flex-column 
                                                flex-center w-100px h-100px border-gray-200 
                                                 <?php echo $isLendingHistory ? 'active border-primary btn-active-light-primary' : ''; ?>" data-kt-button="true">
                            <!--begin::Icon-->
                            <span class="mb-2">
                                <i class="ki-duotone ki-notification-status fs-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </span>
                            <!--end::Icon-->
                            <!--begin::Label-->
                            <span class="fs-7 fw-bold"> My Lending</span>
                            <!--end::Label-->
                        </a>
                        <!--end::Link-->
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-6">
                        <!--begin::Link-->
                        <a href="<?php echo base_url(); ?>user/tranfer"
                            class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column 
                                                flex-center w-100px h-100px border-gray-200  <?php echo $isTransfer ? 'active  btn-active-light-primary' : ''; ?>"
                            data-kt-button="true">
                            <!--begin::Icon-->
                            <span class="mb-2">
                                <i class="ki-duotone ki-arrow-mix fs-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <!--begin::Label-->
                            <span class="fs-7 fw-bold"><?php echo lang('transfer'); ?></span>
                            <!--end::Label-->
                        </a>
                        <!--end::Link-->
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-6">
                        <!--begin::Link-->
                        <a href="<?php echo base_url(); ?>user/myorders"
                            class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column 
                                                 flex-center w-100px h-100px border-gray-200 <?php echo $isSwap ? 'active  btn-active-light-primary' : ''; ?>"
                            data-kt-button="true">
                            <!--begin::Icon-->
                            <span class="mb-2">
                                <i class="ki-duotone ki-arrows-circle fs-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <!--end::Icon-->
                            <!--begin::Label-->
                            <span class="fs-7 fw-bold"><?php echo lang('myorder'); ?></span>
                            <!--end::Label-->
                        </a>
                        <!--end::Link-->
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-6">
                        <!--begin::Link-->
                        <a href="<?php echo base_url(); ?>user/genealogy"
                            class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column 
                                                flex-center w-100px h-100px border-gray-200 <?php echo $isGenealogy ? 'active  btn-active-light-primary' : ''; ?>"
                            data-kt-button="true">
                            <!--begin::Icon-->
                            <span class="mb-2">
                                <i class="ki-duotone ki-people fs-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                    <span class="path5"></span>
                                </i>
                            </span>
                            <!--end::Icon-->

                            <!--begin::Label-->
                            <span class="fs-7 fw-bold"><?php echo lang('Genealogy'); ?></span>
                            <!--end::Label-->
                        </a>
                        <!--end::Link-->
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-6">
                        <!--begin::Link-->
                        <a href="<?php echo base_url(); ?>user/support"
                            class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column 
                                                 flex-center w-100px h-100px border-gray-200 <?php echo $isSupport ? 'active  btn-active-light-primary' : ''; ?>"
                            data-kt-button="true">
                            <!--begin::Icon-->
                            <span class="mb-2">
                                <i class="ki-duotone ki-support-24 fs-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                            </span>
                            <!--end::Icon-->

                            <!--begin::Label-->
                            <span class="fs-7 fw-bold"><?php echo lang('Support'); ?></span>
                            <!--end::Label-->
                        </a>
                        <!--end::Link-->
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-6">
                        <!--begin::Link-->
                        <a href="<?php echo base_url(); ?>user/profit"
                            class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary 
                                                btn-flex flex-column flex-center w-100px h-100px border-gray-200 <?php echo $isProfit ? 'active  btn-active-light-primary' : ''; ?>"
                            data-kt-button="true">
                            <!--begin::Icon-->
                            <span class="mb-2">
                                <i class="ki-outline ki-dollar fs-1"></i> </span>
                            <!--end::Icon-->

                            <!--begin::Label-->
                            <span class="fs-7 fw-bold"><?php echo lang('Profit'); ?> </span>
                            <!--end::Label-->
                        </a>
                        <!--end::Link-->
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-6">
                        <!--begin::Link-->
                        <a href="<?php echo base_url(); ?>user/wallet"
                            class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column 
                                                flex-center w-100px h-100px border-gray-200 <?php echo $isWallet ? 'active  btn-active-light-primary' : ''; ?>"
                            data-kt-button="true">
                            <!--begin::Icon-->
                            <span class="mb-2">
                                <i class="ki-duotone ki-wallet  fs-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </span>


                            <!--end::Icon-->

                            <!--begin::Label-->
                            <span class="fs-7 fw-bold"><?php echo lang('Wallet'); ?></span>
                            <!--end::Label-->
                        </a>
                        <!--end::Link-->
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-6">
                        <!--begin::Link-->
                        <a href="<?php echo base_url(); ?>user/referral"
                            class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary
                                                 btn-flex flex-column flex-center w-100px h-100px border-gray-200 <?php echo $isReferral ? 'active  btn-active-light-primary' : ''; ?>"
                            data-kt-button="true">
                            <!--begin::Icon-->
                            <span class="mb-2">
                                <i class="ki-duotone ki-profile-user fs-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </span>
                            <!--end::Icon-->

                            <!--begin::Label-->
                            <span class="fs-7 fw-bold"><?php echo lang('my_referral'); ?></span>
                            <!--end::Label-->
                        </a>
                        <!--end::Link-->
                    </div>
                    <!--end::Col-->

                    <!--begin::Col-->
                    <div class="col-6">
                        <!--begin::Link-->
                        <a href="<?php echo base_url(); ?>user/kyc"
                            class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column
                                                 flex-center w-100px h-100px border-gray-200 <?php echo $dex_wallet ? 'active  btn-active-light-primary' : ''; ?>"
                            data-kt-button="true">
                            <!--begin::Icon-->
                            <span class="mb-2">
                                <i class="ki-duotone ki-bitcoin fs-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <!--end::Icon-->
                            <!--begin::Label-->
                            <span class="fs-7 fw-bold">KYC </span>
                            <!--end::Label-->
                        </a>
                        <!--end::Link-->
                    </div>
                    <!--end::Col-->

                    <!--begin::Col-->

                    <!--end::Col-->
                </div>
                <!--end::Row-->
            </div>
            <!--end::Links-->


        </div>
        <!--end::Nav wrapper-->
    </div>
    <!--end::Sidebar nav-->
</div>