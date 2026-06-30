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
                                                        <?php echo lang('dashboard'); ?>                         
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
                                            <h2 class="fs-2x fw-bold mb-0"> <?php echo lang('swap_title'); ?></h2>
                                            <!--end::Title-->

                                            <!--begin::Description-->
                                            <p class="text-gray-500 fs-4 fw-semibold py-7">
                                                <?php echo lang('swap_desc'); ?>
                                            </p>
                                            <!--end::Description-->

                                            <!--begin::Action-->
                                            <a href="#" class="btn btn-primary fs-6 px-8 py-4" data-bs-toggle="modal" data-bs-target="#kt_modal_new_address">
                                                <?php echo lang('swap_model'); ?>
                                            </a>
                                            <!--end::Action-->
                                        </div>
                                        <!--end::Heading-->

                                        <!--begin::Illustration-->
                                        <div class="text-center pb-15 px-5">
                                            <img src="<?php echo base_url();?>/assets/user/media/illustrations/sketchy-1/3.png" alt="" class="mw-100 h-200px h-sm-325px">
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
                    <!--begin::Modal title-->
                    <h2><?php echo lang('swap_title'); ?></h2>
                    <!--end::Modal title-->

                    <!--begin::Close-->
                    <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i> </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->

                <!--begin::Modal body-->
                <div class="modal-body py-10 px-lg-17">
                    <!--begin::Scroll-->
                    <div class="scroll-y me-n7 pe-7" id="kt_modal_new_address_scroll" 
                    data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_new_address_header" data-kt-scroll-wrappers="#kt_modal_new_address_scroll"
                    data-kt-scroll-offset="300px" style="max-height: 315px;">

             <?= form_open($action, ['class' => 'form-validate', 'method' => 'post', 'autocomplete' => 'off', 'id' => 'kt_account_meta_details_form',"data-kt-redirect-url"=> $redirect_url]) ?>
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>" />  
                                      
                        
                               <div class="row mb-5">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6"><?php echo lang('your_balance_info'); ?><span class="text-danger"> * </span></label>
                                <div class="col-lg-8 fv-row">

                                <div class="row">
                                <div class="col-lg-6 mb-3">
                                <div class="border border-gray-300 border-dashed rounded col-lg-12 py-3 px-4 mb-3">
                                <div class="d-flex align-items-center">
                                <div class="fs-2 fw-bold counted" id="main_balance" data-kt-initialized="1">0</div>
                                </div>
                                <div class="d-flex flex-stack">
                                <div class="fw-semibold fs-6 text-success"><?php echo lang('main_balance'); ?></div>
                                </div>
                                </div>
                                </div>

                                <div class="col-lg-6 mb-3">
                                <div class="border border-gray-300 border-dashed rounded col-lg-12 py-3 px-4 mb-3">
                                <div class="d-flex align-items-center">
                                <div class="fs-2 fw-bold counted" id="token_balance" data-kt-initialized="1">0</div>
                                </div>
                                <div class="d-flex flex-stack">
                                <div class="fw-semibold fs-6 text-success"><?php echo lang('token_balance'); ?></div>
                                </div>
                                </div>
                                </div>

                         

                                </div>
                                </div>

                                <div class="row mb-6 d-none">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6"> <?php echo lang('select_sender_member'); ?> <span class="text-danger"> * </span></label>
                                <div class="col-lg-8 fv-row">
                                <div class="input-group mb-5">
                                    <select class="form-select form-select-solid" 
                                    data-control="select2" data-close-on-select="false" id="memberSelect" name="selected_members"
                                    data-placeholder="Select a sender member" data-allow-clear="true">
                                    <option value="<?php echo $user_id;?>" selected></option>
                                    </select>
                                </div>
                                </div>
                                </div>


                                <div class="row mb-6 mb-3 d-none">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">  <?php echo lang('select_payment_type'); ?> <span class="text-danger"> * </span></label>
                                <div class="col-lg-8 fv-row">
                                <div class="input-group mb-5">
                                    <select class="form-select form-select-solid" 
                                    data-control="select2" data-close-on-select="false"  name="selected_coin"
                                    data-placeholder="Select a payment type" data-allow-clear="true">
                                    <option></option>
                                     <option value="currency"><?php echo $default_currency; ?> - ( Main Currency ) </option>
                                     <option value="token" selected><?php echo $default_token; ?> - ( Main Token ) </option>
                                    </select>
                                </div>
                                </div>
                                </div>

                                <div class="row mb-6 mb-3">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">  <?php echo lang('amount'); ?> ( <?php echo $value_text; ?> )<span class="text-danger"> * </span></label>
                                <div class="col-lg-8 fv-row">
                                <input class="form-control form-control-lg form-control-solid mb-3" 
                                name="bonus_amount" id="bonus_amount" 
                                placeholder="Enter Amount ( <?php echo $default_token; ?> )" ./>
                                </div>
                                </div>
                                
                                <div class="row mb-6 mb-3">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6"><?php echo lang('received_amount'); ?>  ( <?php echo $default_currency; ?> ) <span class="text-danger"> * </span></label>
                                <div class="col-lg-8 fv-row">
                                <input class="form-control form-control-lg form-control-solid mb-3" 
                                name="received_amount" id="received_amount" 
                                placeholder="Received Amount  ( <?php echo $default_currency; ?> )" disabled/>
                                </div>
                                </div>


                            <div class="col-md-12">
                            <div class="form-group"><button type="submit" id="kt_account_meta_details_submit"
                            class="btn btn-lg btn-primary"><?php echo lang('submit'); ?></button>
                            </div>
                            </div>

                        </form>
            <!--end::Form-->
        </div>
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
        </script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/user-internel-swap.js?ver=2.9"></script>

    </body>

    </html>