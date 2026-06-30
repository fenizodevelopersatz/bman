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
            <!--begin::Stepper-->
            <div class="stepper stepper-links d-flex flex-column pt-15" id="kt_create_account_stepper" data-kt-stepper="true">
                <!--begin::Nav-->
                <div class="stepper-nav mb-5">
                    <!--begin::Step 1-->
                    <div class="stepper-item " data-kt-stepper-element="nav">
                        <h3 class="stepper-title">
                        Lending <?php echo lang('packages'); ?> 
                    </h3>
                    </div>
                    <!--end::Step 1-->

                    <!--begin::Step 2-->
                    <div class="stepper-item" data-kt-stepper-element="nav">
                        <h3 class="stepper-title">
                       <?php echo lang('wallet_information'); ?> 
                    </h3>
                    </div>
                    <!--end::Step 2-->

                    <!--begin::Step 3-->
                    <div class="stepper-item" data-kt-stepper-element="nav">
                        <h3 class="stepper-title">
                           <?php echo lang('Progressing'); ?> 
                    </h3>
                    </div>
                    <!--end::Step 3-->

                    <!--begin::Step 5-->
                    <div class="stepper-item current" data-kt-stepper-element="nav">
                        <h3 class="stepper-title">
                       <?php echo lang('Completed'); ?> 
                    </h3>
                    </div>
                    <!--end::Step 5-->
                </div>
                <!--end::Nav-->

                <!--begin::Form-->
                <form class="mx-auto mw-600px w-100 pt-15 pb-10 fv-plugins-bootstrap5 fv-plugins-framework" action="<?php echo $action; ?>" novalidate="novalidate" id="kt_create_account_form">
                    <!--begin::Step 1-->
                    <div  data-kt-stepper-element="content">
                        <!--begin::Wrapper-->
                        <div class="w-100">
                            <!--begin::Input group-->
                            <div class="pt-5">
                        <h2 class="fw-bold text-gray-900 mb-5"> <?php echo lang('Choose_a'); ?>  Lending <?php echo lang('packages'); ?> </h2>
                        <div class="fv-row">
                        <div class="row">
                            <?php foreach($package as $pack): ?>
                                <div class="col-lg-6">
                                    <!--begin::Package Option-->
                                    <input type="radio" class="btn-check" name="package_id" value="<?= $pack->id ?>" id="package_<?= $pack->id ?>" required data-fv-field="package_id">
                                    <label class="btn btn-outline btn-outline-dashed btn-active-light-success p-5 d-flex align-items-center mb-6" for="package_<?= $pack->id ?>">
                                        <i class="ki-outline ki-gift fs-2x me-4"></i>
                                        <div class="d-block text-start">
                                            <span class="text-gray-900 fw-bold fs-4 mb-1 d-block"><?= $pack->package_name ?></span>
                                            <span class="text-muted fw-semibold fs-6 mb-2"><?php echo lang('daily_roi'); ?> : <?= $pack->roi ?>% for <?= $pack->days_duration ?> days</span>
                                            <br>
                                             <span class="text-muted fw-semibold fs-6 mb-2"> <?php echo lang('minimum'); ?>  : <?= currency_format($pack->minimum) ?>  </span>
                                             <br>
                                             <span class="text-muted fw-semibold fs-6 mb-2"> <?php echo lang('maximum'); ?>  : <?= currency_format($pack->maximum) ?>  </span>
                                        </div>
                                    </label>
                                    <!--end::Package Option-->
                                </div>
                            <?php endforeach; ?>
                        </div>
                        </div>
                      </div>
                            <!--end::Input group-->
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::Step 1-->

                            <!--begin::Step 2-->
                            <div data-kt-stepper-element="content">


                            <!--begin::Input group-->
                            <div class="mb-10 fv-row fv-plugins-icon-container">

                            
                                <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6 mb-10">
                                    <i class="ki-outline ki-information fs-2tx text-warning me-4"></i>      
                                    <div class="d-flex flex-stack flex-grow-1">
                                        <div class="fw-semibold">
                                            <h4 class="text-gray-900 fw-bold">Selected <?php echo lang('packages'); ?></h4>
                                            <div id="selected_package_details" class="fs-6 text-gray-700">
                                                Please select a package to view details here.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                          
                                <div class="row g-5">
                                    <!--begin::Payment Option - Crypto Wallet (USDT)-->
                                    <div class="col-lg-6">
                                        <input type="radio" class="btn-check" name="payment_option" value="paypal" id="payment_crypto" required disabled>
                                        <label class="btn btn-outline btn-outline-dashed btn-active-light-success p-5 d-flex align-items-center mb-6" for="payment_crypto">
                                            <i class="ki-outline ki-bitcoin fs-2x me-4"></i>
                                            <div class="d-block text-start">
                                                <span class="text-gray-900 fw-bold fs-4 mb-1 d-block"><?php echo lang('paypal_wallet_title'); ?></span>
                                                <span class="text-muted fw-semibold fs-6 d-block mb-6"><?php echo lang('paypal_wallet_subtext'); ?></span>
                                            </div>
                                        </label>
                                    </div>
                                    <!--end::Payment Option-->

                                        <!--begin::Payment Option - Crypto Wallet (USDT)-->
                                    <div class="col-lg-6">
                                        <input type="radio" class="btn-check" name="payment_option" value="stripe" id="payment_stripe" required>
                                        <label class="btn btn-outline btn-outline-dashed btn-active-light-success p-5 d-flex align-items-center mb-6" for="payment_stripe">
                                            <i class="ki-outline ki-bitcoin fs-2x me-4"></i>
                                            <div class="d-block text-start">
                                                <span class="text-gray-900 fw-bold fs-4 mb-1 d-block"><?php echo lang('stripe_wallet_title'); ?></span>
                                                <span class="text-muted fw-semibold fs-6 d-block mb-6"><?php echo lang('stripe_wallet_subtext'); ?></span>
                                            </div>
                                        </label>
                                    </div>
                                    <!--end::Payment Option-->

                                    <!--begin::Payment Option - My Wallet-->
                                    <div class="col-lg-6">
                                        <input type="radio" class="btn-check" name="payment_option" value="wallet" id="payment_wallet">
                                        <label class="btn btn-outline btn-outline-dashed btn-active-light-success p-5 d-flex align-items-center mb-6" for="payment_wallet">
                                            <i class="ki-outline ki-wallet fs-2x me-4"></i>
                                            <div class="d-block text-start">
                                             <span class="text-gray-900 fw-bold fs-4 mb-1 d-block"><?php echo lang('my_wallet_title'); ?></span>
                                            <span class="text-muted fw-semibold fs-6 d-block mb-1"><?php echo lang('my_wallet_subtext'); ?></span>
                                            <span class="badge badge-light-info fw-bold fs-6"><?php echo sprintf(lang('my_wallet_balance'), $balance); ?></span>
                                            </div>
                                        </label>
                                    </div>
                                    <!--end::Payment Option-->
                                </div>

                                  <div class="fv-row mb-10 fv-plugins-icon-container">
                                    <label class="form-label required"><?php echo lang('enter_lending_amount'); ?></label>
                                    <input name="lending_amount" class="form-control form-control-lg form-control-solid" value="">
                                    <div class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback"></div>
                                </div>


                                <div class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback"></div>
                            </div>
                            <!--end::Input group-->

                        </div>
                        <!--end::Step 2-->


                    <!--begin::Step 3-->
                    <div data-kt-stepper-element="content">
                       <!--begin::Step 3-->
                        <div class="w-100">
                            <h2 class="fw-bold text-gray-900 mb-5">Review & Confirm</h2>

                            <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6 mb-10">
                                <i class="ki-outline ki-eye fs-2tx text-primary me-4"></i>
                                <div class="d-flex flex-column flex-grow-1">
                                    <div class="fw-semibold mb-2">
                                        <h4 class="text-gray-900 fw-bold">Your Selection Summary</h4>
                                    </div>
                                    <div id="summary_section" class="fs-6 text-gray-700">
                                        Loading summary...
                                    </div>
                                </div>
                            </div>
                    </div>
                    <!--end::Step 3-->

                        

                    </div>
                    <!--end::Step 3-->


                    <!--begin::Step 5-->
                    <div  class="current" data-kt-stepper-element="content">

                     <div class="w-100">
                    <div class="text-center pb-15 px-5">
                    <img src="<?php echo base_url();?>/assets/user/media/illustrations/sketchy-1/7.png" alt="" class="mw-100 h-200px h-sm-325px">
                    </div>
                    </div>
                    </div>
                    <!--end::Step 5-->

                    <!--begin::Actions-->
                    <div class="d-flex flex-stack pt-15">
                        <!--begin::Wrapper-->
                        <div class="mr-2">
                            <button type="button" class="btn btn-lg btn-light-primary me-3" data-kt-stepper-action="previous">
                                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Back
                            </button>
                        </div>
                        <!--end::Wrapper-->

                        <!--begin::Wrapper-->
                        <div>
                            <button type="button" class="btn btn-lg btn-primary me-3" data-kt-stepper-action="submit">
                                <span class="indicator-label">
                                Submit
                                <i class="ki-outline ki-arrow-right fs-3 ms-2 me-0"></i>                            </span>
                                <span class="indicator-progress">
                                Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>

                            <a href="<?php echo base_url();?>user/lending-history" class="btn btn-lg btn-primary">
                                View History
                             <i class="ki-outline ki-arrow-right fs-4 ms-1 me-0"></i> 
                            </a>
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::Actions-->
                </form>
                <!--end::Form-->
            </div>
            <!--end::Stepper-->
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->

</div>


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
        </script>

            <script src="<?php echo base_url();?>/assets/user/js/custom/utilities/modals/create-account.js"></script>

    </body>

    </html>