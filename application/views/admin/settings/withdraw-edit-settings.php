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

                        <div id="kt_account_settings_profile_details" class="collapse show">
                        <div class="d-flex flex-wrap flex-sm-nowrap" id="profile_ini">

                        <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
                        <!--begin::Nav item-->
                        <li class="nav-item mt-2 p-4">
                        <a class="nav-link text-active-primary ms-0 me-10 py-5 <?php echo $withdraw_page; ?>" 
                        href="<?php echo base_url();?>/withdraw-settings">
                        <?php echo currency_info()->coin_name; ?> -  Withdraw Settings                    
                        </a>
                        </li>
                        <!--end::Nav item-->
                        <!--begin::Nav item-->
                        <!-- <li class="nav-item mt-2">
                        <a class="nav-link text-active-primary ms-0 me-10 py-5 <?php echo $token_withdraw_page; ?>" 
                        href="<?php echo base_url();?>/token-withdraw-settings">
                        <?php echo $token_info->coin_name; ?> - Withdraw Settings                    
                        </a>
                        </li> -->
                        <!--end::Nav item-->
                        </ul>
                        </div>
                        </div>



                     <!-- INSEIDE FORM ENTER -->

                               <div id="kt_account_settings_profile_details" class="collapse show">

                               <form 
                               id="kt_account_meta_details_form" 
                               class="form w-100 fv-plugins-bootstrap5 fv-plugins-framework" 
                               method="post"
                               novalidate="novalidate" 
                               data-kt-redirect-url="<?php echo $redirect; ?>" 
                               action="<?php echo $action; ?>"
                               enctype="multipart/form-data"
                               >

                               <div class="card-body border-top p-9">


                                <div class="col-lg-6">
                                <div class="row mb-6">
                                <label class="col-lg-8 col-form-label fw-semibold fs-6">Withdraw Status<span class="text-danger"> * </span></label>
                                <div class="col-lg-4 fv-row">
                                <div class="input-group mb-5">
                                <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                <input class="form-check-input  h-30px w-50px" type="checkbox" value="1" name="withdraw_status" 
                                <?php echo $withdraw_status ? "checked": ""; ?> 
                                id="withdraw_status"/>
                                <label class="form-check-label" for="withdraw_status">
                                </label>
                                </div>
                                </div>
                                </div>
                                </div>
                                </div>
                            
                               <div class="row mb-6">
                               <label class="col-lg-4 col-form-label fw-semibold fs-6">Min Withdraw <span class="text-danger"> * </span></label>
                               <div class="col-lg-8 fv-row">
                               <div class="input-group mb-5">
                               <span class="input-group-text border-transparent " id="basic-addon1"><?php echo $currency_info->currency_symbol; ?></span>
                               <input type="text" name="min_withdraw" 
                               class="form-control form-control-lg form-control-solid" 
                               placeholder="Min Withdraw" 
                               value="<?php echo currency_format_no_symbol($min_withdraw); ?>" required>
                               </div>
                               </div>
                               </div>

                               <div class="row mb-6">
                               <label class="col-lg-4 col-form-label fw-semibold fs-6">Max Withdraw <span class="text-danger"> * </span></label>
                               <div class="col-lg-8 fv-row">
                               <div class="input-group mb-5">
                               <span class="input-group-text border-transparent " id="basic-addon1"><?php echo $currency_info->currency_symbol; ?></span>
                               <input type="text" name="max_withdraw" 
                               class="form-control form-control-lg form-control-solid" 
                               placeholder="Max Withdraw" 
                               value="<?php echo currency_format_no_symbol($max_withdraw); ?>" required>
                               </div>
                               </div>
                               </div>


                               <div class="row mb-6">
                               <label class="col-lg-4 col-form-label fw-semibold fs-6">Withdraw Fee <span class="text-danger"> * </span></label>
                               <div class="col-lg-8 fv-row">
                               <div class="input-group mb-5">
                               <span class="input-group-text border-transparent " id="basic-addon1"><?php echo $currency_info->currency_symbol; ?></span>
                               <input type="text" name="withdraw_fee" 
                               class="form-control form-control-lg form-control-solid" 
                               placeholder="Enter withdraw fee" 
                               value="<?php echo currency_format_no_symbol($withdraw_fee); ?>" required>
                               </div>
                               </div>
                               </div>

                               <div class="row mb-6">
                               <label class="col-lg-4 col-form-label fw-semibold fs-6">Withdraw Daily Limit <span class="text-danger"> * </span></label>
                               <div class="col-lg-8 fv-row">
                               <div class="input-group mb-5">
                               <span class="input-group-text border-transparent " id="basic-addon1"><?php echo $currency_info->currency_symbol; ?></span>
                               <input type="text" name="withdraw_daily_limit" 
                               class="form-control form-control-lg form-control-solid" 
                               placeholder="withdraw daily limit" 
                               value="<?php echo currency_format_no_symbol($withdraw_daily_limit); ?>" required>
                               </div>
                               <small class="text-danger"> Notes : if set 0 unlimit </samll>
                               </div>
                               </div>

                               <div class="row mb-6">
                               <label class="col-lg-4 col-form-label fw-semibold fs-6">Withdraw Monthly Limit <span class="text-danger"> * </span></label>
                               <div class="col-lg-8 fv-row">
                               <div class="input-group mb-5">
                               <span class="input-group-text border-transparent " id="basic-addon1"><?php echo $currency_info->currency_symbol; ?></span>
                               <input type="text" name="withdraw_monthly_limit" 
                               class="form-control form-control-lg form-control-solid" 
                               placeholder="Withdraw Monthly Limit" 
                               value="<?php echo currency_format_no_symbol($withdraw_monthly_limit); ?>" required>
                               </div>
                               <small class="text-danger"> Notes : if set 0 unlimit </samll>
                               </div>
                               </div>

                               
                                <div class="col-lg-6">
                                <div class="row mb-6">
                                <label class="col-lg-8 col-form-label fw-semibold fs-6">Withdraw Fee (%) or Fiat<span class="text-danger"> * </span></label>
                                <div class="col-lg-4 fv-row">
                                <div class="input-group mb-5">
                                <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                <select name="withdraw_amount_type"  class="form-select" data-control="select2" data-placeholder="Select an option">
                                <option value="1"    <?php echo $withdraw_amount_type == "1" ? "selected": ""; ?> >Percentage (%) </option>
                                <option value="0"    <?php echo $withdraw_amount_type =="0" ? "selected": ""; ?> > Fiat (<?php echo $currency_info->currency_symbol; ?>) </option>
                                </select>
                                </div>
                                </div>
                                </div>
                                </div>
                                </div>


                                <div class="col-lg-6" style="display:none"> 
                                <div class="row mb-6">
                                <label class="col-lg-8 col-form-label fw-semibold fs-6">Auto Withdraw<span class="text-danger"> * </span></label>
                                <div class="col-lg-4 fv-row">
                                <div class="input-group mb-5">
                                <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                <input class="form-check-input  h-30px w-50px" type="checkbox" value="1" name="auto_withdraw" 
                                <?php echo $auto_withdraw ? "checked": ""; ?> 
                                id="auto_withdraw"/>
                                <label class="form-check-label" for="auto_withdraw">
                                </label>
                                </div>
                                </div>
                                </div>
                                </div>
                                </div>

                                <div class="col-lg-6">
                                <div class="row mb-6">
                                <label class="col-lg-8 col-form-label fw-semibold fs-6">Notification To User<span class="text-danger"> * </span></label>
                                <div class="col-lg-4 fv-row">
                                <div class="input-group mb-5">
                                <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                <input class="form-check-input  h-30px w-50px" type="checkbox"  value="1" name="withdraw_notification_user" 
                                <?php echo $withdraw_notification_user ? "checked": ""; ?> 
                                id="withdraw_notification_user"/>
                                <label class="form-check-label" for="withdraw_notification_user">
                                </label>
                                </div>
                                </div>
                                </div>
                                </div>
                                </div>

                                <div class="col-lg-6">
                                <div class="row mb-6">
                                <label class="col-lg-8 col-form-label fw-semibold fs-6">Notification To Admin<span class="text-danger"> * </span></label>
                                <div class="col-lg-4 fv-row">
                                <div class="input-group mb-5">
                                <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                <input class="form-check-input  h-30px w-50px" type="checkbox"  value="1" name="withdraw_notification_admin" 
                                <?php echo $withdraw_notification_admin ? "checked": ""; ?> 
                                id="withdraw_notification_admin"/>
                                <label class="form-check-label" for="withdraw_notification_admin">
                                </label>
                                </div>
                                </div>
                                </div>
                                </div>
                                </div>


                               </div>
                               <div class="card-footer d-flex justify-content-end py-6 px-9">
                               <button type="submit" class="btn btn-primary" id="kt_account_meta_details_submit">Save Changes</button>
                               </div>
                               </form>

                              


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
            <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/withdraw-settings.js?ver=2.9"></script>


            <script>
            </script>
    </body>

    </html>