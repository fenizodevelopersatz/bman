<?php $this->load->view('admin/Layout/common_style');?>

    <link href="<?php echo base_url();?>/assets/admin/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url();?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url();?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

    <style>
        .h-md-40{
            min-height:42%;
        }
        .verify-symbol {
        color: green;
        font-weight: bold;
        margin-left: 5px;
        }
        .verified {
        border-color: green;
        }
    </style>

    <body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

        <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
            <div class="app-page  flex-column flex-column-fluid " id="kt_app_page">

                <!--  Header   -->
                <?php 
                //************************** SIDE BAR ADMIN PANEL */
                $this->load->view('admin/Layout/admin_topbar');
                //************************** SIDE BAR ADMIN PANEL */
                ?>


                    <!--begin::Wrapper-->
                    <div class="app-wrapper  flex-column flex-row-fluid " id="kt_app_wrapper">

                        <?php $this->load->view('admin/Layout/admin_sidebar');?>

                            <!--begin::Main-->
                            <div class="app-main flex-column flex-row-fluid " id="kt_app_main">
                                <div class="d-flex flex-column flex-column-fluid">

                                    <!--begin::Toolbar-->
                                    <div id="kt_app_toolbar" class="app-toolbar  py-3 py-lg-6 ">
                                        <div id="kt_app_toolbar_container" class="app-container  container-xxl d-flex flex-stack ">
                                            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3 ">

                                                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                                                <?php echo $title; ?>
                                                </h1>

                                                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                                    <li class="breadcrumb-item text-muted">
                                                        <a href="<?php echo base_url();?>" class="text-muted text-hover-primary">
                                                        Settings                         
                                                        </a>
                                                    </li>
                                                    <li class="breadcrumb-item">
                                                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                                    </li>
                                                    <li class="breadcrumb-item text-muted">
                                                    <?php echo $title; ?> </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <!--end::Toolbar-->
                        <div id="kt_app_content" class="app-content  flex-column-fluid mt-10">

                        <div id="kt_app_content_container" class="app-container  container-xxl ">

                              <?php if($verify_type != '1') { $this->session->unset_userdata('sender_otp'); ?>


                            <div class="bg-body d-flex flex-column align-items-stretch flex-center rounded-4 w-md-600px p-20">
                                <div class="d-flex flex-center flex-column flex-column-fluid px-lg-10 pb-15 pb-lg-20">

                                    <!--begin::Form-->
                                    <form class="form w-100 mb-13" novalidate="novalidate" 
                                    action="<?php echo base_url();?>payment-verify"
                                    method="POST"
                                    id="kt_sing_in_two_factor_form">
                                        <!--begin::Icon-->
                                        <div class="text-center mb-10">
                                            <img alt="Logo" class="mh-125px" src="<?php echo base_url();?>/assets/admin/media/svg/misc/smartphone-2.svg">
                                        </div>
                                        <!--end::Icon-->

                                        <!--begin::Heading-->
                                        <div class="text-center mb-10">
                                            <!--begin::Title-->
                                            <h1 class="text-gray-900 mb-3">
                                                Two-Factor & Email Verification
                                            </h1>
                                            <!--end::Title-->

                                            <!--begin::Sub-title-->
                                            <div class="text-muted fw-semibold fs-5 mb-5">Enter the verification code we sent to</div>
                                            <!--end::Sub-title-->

                                            <!--begin::Mobile no-->
                                            <div class="fw-bold text-gray-900 fs-3"><?php echo $admin_mail; ?></div>
                                            <!--end::Mobile no-->
                                        </div>
                                        <!--end::Heading-->

                                        <div class="mb-10">
                                            <div class="fw-bold text-start text-gray-900 fs-6 mb-1 ms-1">Type your Two-Factor 6 digit security code</div>

                                            <div class="otp-container">
                                            <div class="d-flex flex-wrap flex-stack">
                                                <input type="text" name="twofa_code_1" 
                                                data-inputmask="'mask': '9', 'placeholder': ''"
                                                 maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code" value="" inputmode="text">
                                                <input type="text" name="twofa_code_2" data-inputmask="'mask': '9', 'placeholder': ''"
                                                 maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code" value="" inputmode="text">
                                                <input type="text" name="twofa_code_3" data-inputmask="'mask': '9', 'placeholder': ''" 
                                                maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code" value="" inputmode="text">
                                                <input type="text" name="twofa_code_4" data-inputmask="'mask': '9', 'placeholder': ''" 
                                                maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code" value="" inputmode="text">
                                                <input type="text" name="twofa_code_5" data-inputmask="'mask': '9', 'placeholder': ''" 
                                                maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code" value="" inputmode="text">
                                                <input type="text" name="twofa_code_6" data-inputmask="'mask': '9', 'placeholder': ''" 
                                                maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code" value="" inputmode="text">
                                            </div>
                                            <span class="otp-loader d-none">⏳ Verifying...</span>
                                            <p class="otp-message"></p>
                                            </div>

                                        </div>


                                         <div class="mb-10">
                                            <div class="fw-bold text-start text-gray-900 fs-6 mb-1 ms-1">Type your Email-OTP 6 digit security code</div>

                                            <div class="otp-container">
                                            <div class="d-flex flex-wrap flex-stack">
                                                <input type="text" name="email_code_1" data-inputmask="'mask': '9', 'placeholder': ''" maxlength="1" 
                                                class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code" value="" inputmode="text">
                                                <input type="text" name="email_code_2" data-inputmask="'mask': '9', 'placeholder': ''" 
                                                maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code" value="" inputmode="text">
                                                <input type="text" name="email_code_3" data-inputmask="'mask': '9', 'placeholder': ''" 
                                                maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code" value="" inputmode="text">
                                                <input type="text" name="email_code_4" data-inputmask="'mask': '9', 'placeholder': ''" 
                                                maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code" value="" inputmode="text">
                                                <input type="text" name="email_code_5" data-inputmask="'mask': '9', 'placeholder': ''" 
                                                maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code" value="" inputmode="text">
                                                <input type="text" name="email_code_6" data-inputmask="'mask': '9', 'placeholder': ''" 
                                                maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code" value="" inputmode="text">
                                            </div>
                                            <span class="otp-loader d-none">⏳ Verifying...</span>
                                            <p class="otp-message"></p>
                                            </div>
                                        </div>



                                        <!--begin::Submit-->
                                        <div class="d-flex flex-center">
                                            <button type="submit" id="kt_sing_in_two_factor_submit" 
                                            data-kt-redirect-url="<?php echo base_url();?>payment-edit/<?php echo $payment_id;?>" 
                                            disabled class="btn btn-lg btn-primary fw-bold">
                                                <span class="indicator-label">
                                                    Submit
                                                </span>
                                                <span class="indicator-progress">
                                                    Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                                </span>
                                            </button>
                                        </div>
                                        <!--end::Submit-->
                                        
                                    </form>
                                    <!--end::Form-->

                                </div>
                                <!--end::Wrapper-->

                            </div>


                       

                           <?php } else { $this->session->unset_userdata('verify_payment_page');  ?>



                    <div class="card mb-5 mb-xl-10 ">
                    <div class="card-header border-0 cursor-pointer p-3" 
                    role="button" data-bs-toggle="collapse" 
                    data-bs-target="#kt_account_addagent_form_details" 
                    aria-expanded="true" aria-controls="kt_account_addagent_form_details">
                    <div class="card-title m-0">

                    <div class="me-3 d-flex justify-content-between text-center align-items-center gap-4">
                    <div class="d-flex flex-center w-60px h-60px rounded-3 bg-light-danger bg-opacity-90">
                    <i class="ki-duotone ki-abstract-26 text-danger fs-3x"><span class="path1"></span><span class="path2"></span></i>               
                    </div>
                    <h3 class="fw-bold m-0"><?php echo $card_title; ?></h3>
                    </div>

                    </div>
                    </div>



                    <div id="kt_account_addagent_form_details" class="collapse show">
                    <div class="card-body border-top p-9">

                        <?php $action = ""; ?>
                      <?php $this->load->view('admin/Layout/common_style'); ?>
<link rel="stylesheet" href="<?php echo base_url('assets/admin/plugins/global/plugins.bundle.css'); ?>">

<body id="kt_app_body" class="app-default">
<?php $this->load->view('admin/Layout/admin_topbar'); ?>
<div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
<?php $this->load->view('admin/Layout/admin_sidebar'); ?>

<div class="app-main flex-column flex-row-fluid" id="kt_app_main">
  <div class="app-container container-xxl py-6">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title"><?php echo html_escape($card_title); ?></h3>
      </div>

      <div class="card-body p-9">
        <?php $action = site_url('payment-settings/edit/'.$payment->id); ?>
            <?= form_open($action, ['class'=>'form-validate', 'method'=>'post', 'autocomplete'=>'off']) ?>
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>"
                value="<?= $this->security->get_csrf_hash(); ?>" />

            <div class="row mb-6">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Gateway</label>
            <div class="col-lg-8 fv-row">
                <input type="text" class="form-control form-control-lg form-control-solid"
                    value="<?php echo strtoupper($payment->gateway); ?>" readonly>
            </div>
            </div>

            <div class="row mb-6">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Mode <span class="text-danger">*</span></label>
            <div class="col-lg-8 fv-row">
                <select name="mode" class="form-select form-select-lg form-select-solid w-200px" required>
                <?php $mode = $payment->mode ?: 'sandbox'; ?>
                <option value="sandbox" <?php echo $mode==='sandbox'?'selected':''; ?>>Sandbox</option>
                <option value="live"    <?php echo $mode==='live'?'selected':''; ?>>Live</option>
                <option value="none"    <?php echo $mode==='none'?'selected':''; ?>>None</option>
                </select>
            </div>
            </div>

            <div class="row mb-6">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Status</label>
            <div class="col-lg-8 fv-row">
                <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                <input class="form-check-input h-30px w-50px" type="checkbox" name="status" value="1"
                        <?php echo ((int)$payment->status===1)?'checked':''; ?> />
                </div>
            </div>
            </div>

            <!-- Stripe fields -->
            <?php if (strtolower($payment->gateway)==='stripe'): ?>
            <div class="alert alert-info py-2 px-3 mb-6">
            Leave keys blank to keep current values.
            </div>
            <div class="row mb-6">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Publishable Key</label>
            <div class="col-lg-8 fv-row">
                <input type="text" name="publishable_key" class="form-control form-control-lg form-control-solid"
                    placeholder="pk_live_... / pk_test_...">
            </div>
            </div>
            <div class="row mb-6">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Secret Key</label>
            <div class="col-lg-8 fv-row">
                <input type="text" name="secret_key" class="form-control form-control-lg form-control-solid"
                    placeholder="sk_live_... / sk_test_...">
            </div>
            </div>
            <?php endif; ?>

            <!-- PayPal fields -->
            <?php if (strtolower($payment->gateway)==='paypal'): ?>
            <div class="alert alert-info py-2 px-3 mb-6">
            Leave keys blank to keep current values.
            </div>
            <div class="row mb-6">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Client ID</label>
            <div class="col-lg-8 fv-row">
                <input type="text" name="client_id" class="form-control form-control-lg form-control-solid"
                    placeholder="PayPal Client ID">
            </div>
            </div>
            <div class="row mb-6">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Client Secret</label>
            <div class="col-lg-8 fv-row">
                <input type="text" name="client_secret" class="form-control form-control-lg form-control-solid"
                    placeholder="PayPal Client Secret">
            </div>
            </div>
            <?php endif; ?>

            <!-- Cash On Delivery (no fields) -->
            <?php if (strtolower($payment->gateway)==='cash_on'): ?>
            <div class="alert alert-secondary py-2 px-3 mb-6">
            Cash on delivery has no API keys. Set mode/status and save.
            </div>
            <?php endif; ?>

            <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary" id="saveBtn">Save Changes</button>
            </div>
            </form>
        </div>
        </div>
    </div>

    <?php $this->load->view('admin/Layout/admin_footer'); ?>
    <script src="<?php echo base_url('assets/admin/plugins/global/plugins.bundle.js');?>"></script>
    <script>
    // toggle status from list (if you wire it there)
    document.addEventListener('change', function(e){
    if (!e.target.classList.contains('js-toggle-status')) return;
    fetch('<?php echo site_url('payment-settings/toggle-status'); ?>', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'id='+encodeURIComponent(e.target.dataset.id)+'&status='+(e.target.checked?1:0)
    });
    });
    </script>
    </div>
    </div>
    </body>
    </html>


                    </div>
                    </div>

                    </div>
                    </div>


                    <?php } ?>


                    </div>
                    </div>
                                </div>

                                <!--begin::Footer-->
                                <?php $this->load->view('admin/Layout/admin_footer');?>

                            </div>
                    </div>
                    <!--end::Wrapper-->

            </div>
            <!--end::Page-->
        </div>
        <!--end::App-->

        <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
            <i class="ki-duotone ki-arrow-up">
              <span class="path1"></span>
              <span class="path2"></span>
              </i>
        </div>

        <?php $this->load->view('admin/Layout/common_script');?>

            <script src="<?php echo base_url();?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/widgets.bundle.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/widgets.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/apps/chat/chat.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/utilities/modals/upgrade-plan.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/utilities/modals/users-search.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/plugins/global/plugins.bundle.js"></script>
            <script src="<?php echo base_url();?>assets/admin/plugins/custom/datatables/datatables.bundle.js"></script>
            <link href="<?php echo base_url();?>assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.css" rel="stylesheet" type="text/css" />
            <script src="<?php echo base_url();?>assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.js"></script>

            <script>
                const base_url = '<?php echo base_url();?>';
            </script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/payment-edit-settings.js?ver=2.9"></script>


            <script>
            </script>
    </body>

    </html>