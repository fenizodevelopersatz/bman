<?php $this->load->view('user/layout/user_style');?>
    <!--end::Head-->

    <!--begin::Body-->

    <body id="kt_app_body" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

        <!--begin::App-->
        <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
            <div class="app-page  flex-column flex-column-fluid " id="kt_app_page">

                <div id="kt_app_header" class="app-header " data-kt-sticky="true" data-kt-sticky-activate-="true" data-kt-sticky-name="app-header-sticky" data-kt-sticky-offset="{default: '200px', lg: '300px'}">
                    <div class="app-container  container-xxl d-flex align-items-stretch justify-content-between " id="kt_app_header_container">
                        <div class="app-header-wrapper d-flex flex-grow-1 align-items-stretch justify-content-between" id="kt_app_header_wrapper">
                            <?php $this->load->view('user/layout/user_header'); ?>
                        </div>
                    </div>
                </div>
                <!--end::Header-->

                <!--begin::Wrapper-->
                <div class="app-wrapper  flex-column flex-row-fluid " id="kt_app_wrapper">

                    <!--begin::Wrapper container-->
                    <div class="app-container  container-xxl d-flex flex-row-fluid ">

                        <!--begin::Sidebar-->
                      <?php $this->load->view('user/layout/user_sidebar');?>
                        <!--end::Sidebar-->


                        <!--begin::Main-->
                        <div class="app-main flex-column flex-row-fluid " id="kt_app_main">
                            <!--begin::Content wrapper-->
                            <div class="d-flex flex-column flex-column-fluid">

                                <!--begin::Toolbar-->
                                <div id="kt_app_toolbar" class="app-toolbar  d-flex pb-3 pb-lg-5 ">

                                    <!--begin::Toolbar container-->
                                    <div class="d-flex flex-stack flex-row-fluid">
                                        <!--begin::Toolbar container-->
                                        <div class="d-flex flex-column flex-row-fluid">
                                            <!--begin::Toolbar wrapper-->

                                            <!--begin::Page title-->
                                            <div class="page-title d-flex align-items-center me-3">
                                                <!--begin::Title-->
                                                <h1 class="page-heading d-flex flex-column justify-content-center text-gray-900 fw-bold fs-lg-2x gap-2">
                                                     <span> <?php echo $title; ?></span>

                                                        <!--begin::Description-->
                                                <span class="page-desc text-gray-600 fs-base fw-semibold">
                                                  <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                                    <li class="breadcrumb-item text-muted">
                                                        <a href="<?php echo base_url();?>" class="text-muted text-hover-primary">
                                                        <?php echo lang('dashbaord'); ?>                           
                                                        </a>
                                                    </li>
                                                    <li class="breadcrumb-item">
                                                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                                    </li>
                                                    <li class="breadcrumb-item text-muted">
                                                    <?php echo $title; ?> </li>
                                                </ul>      
                                                </span>
                                                <!--end::Description-->
                                                </h1>
                                                                                    <!--end::Title-->
                                            </div>
                                            <!--end::Page title-->

                                        </div>
                                        <!--end::Toolbar container-->
                                    </div>
                                    <!--end::Toolbar container-->
                                </div>
                                <!--end::Toolbar-->



                                <div id="kt_app_content" class="app-content  flex-column-fluid ">

                                <!--begin::Card-->
                                <div class="card">
                                    <!--begin::Card body-->
                                    <div class="card-body">
                                        <!--begin::Heading-->
                                        <div class="card-px text-center pt-15 pb-15">
                                         <!--begin::Title-->
                                           <h2 class="fs-2x fw-bold mb-0"><?php echo lang('wallet_address'); ?></h2>

                                            <!--begin::Description-->
                                            <p class="text-gray-500 fs-4 fw-semibold py-7">
                                                <?php echo lang('wallet_description_line1'); ?><br>
                                                <?php echo lang('wallet_description_line2'); ?>
                                            </p>
                                            <!--end::Description-->

                                            <!--begin::Action-->
                                            <a href="#" class="btn btn-success fs-6 px-8 py-4" data-bs-toggle="modal" data-bs-target="#kt_modal_new_address">
                                                <i class="ki-duotone ki-arrow-down">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i> <?php echo lang('wallet_receive'); ?>
                                            </a>

                                            <a href="#" class="btn btn-danger fs-6 px-8 py-4" id="google-signin-btn">
                                                <i class="ki-duotone ki-send">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i> <?php echo lang('wallet_send'); ?>
                                            </a>

                                            <a href="#" class="btn btn-info fs-6 px-8 py-4" id="google-signin-btn1">
                                                <i class="ki-duotone ki-arrow-up-down">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i> <?php echo lang('wallet_history'); ?>
                                            </a>

                                            <!--end::Action-->
                                        </div>
                                        <!--end::Heading-->

                                        <!--begin::Illustration-->
                                        <div class="text-center pb-15 px-5">
                                        
                                    <div class="row">
                                        <div class="col-md-6">
                                            <!--begin::Card widget 11-->
                                            <div class="card card-flush h-xl-100" style="background-color: #F6E5CA">
                                                <!--begin::Header-->
                                                <div class="card-header flex-nowrap pt-5">
                                                    <!--begin::Title-->
                                                    <h3 class="card-title align-items-start flex-column">            
                                                    <span class="card-label fw-bold fs-4 text-gray-800">Binance</span>
                                                    <span class="mt-1 fw-semibold fs-7" style="color: ">669.88 USD for 1 BNB</span>
                                                </h3>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Header-->

                                                <!--begin::Body-->
                                                <div class="card-body text-center pt-5">
                                                    <!--begin::Image-->
                                                    <img src="<?php echo base_url();?>assets/user/media/svg/coins/binance.svg" class="h-75px mb-5" alt="">
                                                    <!--end::Image-->

                                                    <!--begin::Section-->
                                                    <div class="text-start">
                                                        <span class="d-block fw-bold fs-1 text-gray-800">0.00 BNB</span>
                                                        <span class="mt-1 fw-semibold fs-3" style="color: ">0.00 USD</span>
                                                    </div>
                                                    <!--end::Section-->
                                                </div>
                                                <!--end::Body-->
                                            </div>
                                            <!--end::Card widget 11-->
                                        </div>

                                        <div class="col-md-6">
                                            <!--begin::Card widget 11-->
                                            <div class="card card-flush h-xl-100" style="background-color: #F6E5CA">
                                                <!--begin::Header-->
                                                <div class="card-header flex-nowrap pt-5">
                                                    <!--begin::Title-->
                                                    <h3 class="card-title align-items-start flex-column">            
                                                    <span class="card-label fw-bold fs-4 text-gray-800">USDT</span>
                                                    <span class="mt-1 fw-semibold fs-7" style="color: ">0.99 USD for 1 USDT</span>
                                                </h3>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Header-->

                                                <!--begin::Body-->
                                                <div class="card-body text-center pt-5">
                                                    <!--begin::Image-->
                                                    <img src="<?php echo base_url();?>assets/user/media/svg/coins/usdt.svg" class="h-75px mb-5" alt="">
                                                    <!--end::Image-->

                                                    <!--begin::Section-->
                                                    <div class="text-start">
                                                        <span class="d-block fw-bold fs-1 text-gray-800">0.00 USDT</span>
                                                        <span class="mt-1 fw-semibold fs-3" style="color: ">0.00 USDT</span>
                                                    </div>
                                                    <!--end::Section-->
                                                </div>
                                                <!--end::Body-->
                                            </div>
                                            <!--end::Card widget 11-->
                                        </div>
                                    </div>




                                     <div class="col-xl-12 mt-10">

                                            <?php $this->load->view('user/dashboard/coin_websocket'); ?>
                                            
                                        </div>

                                        </div>
                                        <!--end::Illustration-->
                                    </div>



                                    


                                    <!--end::Card body-->
                                </div>

                                
                                <!--end::Card-->
                            </div>



                            

        <div class="modal fade" id="kt_modal_new_address" tabindex="-1"  aria-modal="true" role="dialog">
            <!--begin::Modal dialog-->
            <div class="modal-dialog modal-dialog-centered mw-850px">
                <!--begin::Modal content-->
                <div class="modal-content">
                    <!--begin::Form-->
                        <!--begin::Modal header-->
                      <div class="modal-header" id="kt_modal_new_address_header">
                            <h2><?php echo lang('wallet_info_title'); ?></h2>
                            <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                                <i class="ki-outline ki-cross fs-1"></i>
                            </div>
                        </div>
                        <!--end::Modal header-->

                        <div class="modal-body scroll-y pt-0 pb-15">
                            <!--begin::Wrapper-->
                            <div class="mw-lg-600px mx-auto">
                                
                                <!-- Scan Wallet -->
                                <div class="mb-10 mt-10 text-center">
                                    <h4 class="fs-5 fw-semibold text-gray-800"><?php echo lang('scan_wallet_address'); ?></h4>
                                    <div class="d-flex justify-content-center">
                                        <img src="<?php echo $cryptos_img; ?>" />
                                    </div>
                                </div>

                                <!-- Wallet Address -->
                                <div class="mb-10 mt-10">
                                    <h4 class="fs-5 fw-semibold text-gray-800"><?php echo lang('wallet_address_label'); ?></h4>
                                    <div class="d-flex">
                                        <input id="right_link_input" type="text" class="form-control form-control-solid me-3 flex-grow-1" value="<?php echo $wallet_address; ?>" readonly>
                                        <button id="right_copy_btn" class="btn btn-light fw-bold flex-shrink-0" data-target="#right_link_input"><?php echo lang('copy_link'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                        <!--end::Wrapper-->               
            </div>
                
         </div>





   
                                    </div>

                                </div>


                            </div>
                    </div>
                    <!--end::Wrapper-->

            </div>
                                 
                                </div>
                                <!--end::Content-->

                            </div>
                            <!--end::Content wrapper-->

                                <!--begin::Footer-->
                                <?php $this->load->view('user/layout/user_footer');?>
                                <!--end::Footer-->
                        </div>
                        <!--end:::Main-->


                    </div>
                    <!--end::Wrapper container-->
                </div>
                <!--end::Wrapper-->


            </div>
            <!--end::Page-->
        </div>
        <!--end::App-->

        <!--end::Engage modals-->
        <!--begin::Scrolltop-->
        <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
            <i class="ki-outline ki-arrow-up"></i></div>
        <!--end::Scrolltop-->

        <!--begin::Javascript-->
        <?php $this->load->view('user/layout/user_script');?>
        <script>
        const base_url = '<?php echo base_url();?>';
        const agent_id = '<?php echo $user_id;?>';


            document.addEventListener("DOMContentLoaded", function () {
                const copyButtons = document.querySelectorAll("button[data-target]");

                copyButtons.forEach(btn => {
                    btn.addEventListener("click", function () {
                        const inputSelector = btn.getAttribute("data-target");
                        const input = document.querySelector(inputSelector);

                        if (input) {
                            input.select(); // highlight
                            input.setSelectionRange(0, 99999); // mobile support

                            // Copy to clipboard
                            navigator.clipboard.writeText(input.value).then(() => {
                                btn.innerText = "Copied!";
                                setTimeout(() => {
                                    btn.innerText = "Copy Link";
                                }, 1500);
                            });
                        }
                    });
                });
            });

        </script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/user-internel-swap.js?ver=2.9"></script>


<script>
document.getElementById('google-signin-btn').addEventListener('click', function(e) {
    e.preventDefault(); 

    Swal.fire({
        icon: 'info',
        title: 'Demo Version',
        text: 'Dex wallet Send to another wallet is not available in the demo version.',
        confirmButtonText: 'Ok, got it!',
        customClass: {
            confirmButton: 'btn btn-primary'
        },
        buttonsStyling: false
    });
});
document.getElementById('google-signin-btn1').addEventListener('click', function(e) {
    e.preventDefault(); 

    Swal.fire({
        icon: 'info',
        title: 'Demo Version',
        text: 'Dex wallet History is not available in the demo version.',
        confirmButtonText: 'Ok, got it!',
        customClass: {
            confirmButton: 'btn btn-primary'
        },
        buttonsStyling: false
    });
});
</script>

    </body>

    </html>