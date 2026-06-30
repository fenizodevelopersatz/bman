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
                                                        Dashboard                         
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
                                            <h2 class="fs-2x fw-bold mb-0">Create a New Support Ticket</h2>
                                            <!--end::Title-->

                                            <!--begin::Description-->
                                            <p class="text-gray-500 fs-4 fw-semibold py-7">
                                                Need assistance? Click the button below to
                                                <br>submit a new support ticket.
                                            </p>
                                            <!--end::Description-->

                                            <!--begin::Action-->
                                            <a href="#" class="btn btn-primary fs-6 px-8 py-4" data-bs-toggle="modal" data-bs-target="#kt_modal_new_address">
                                                Create Ticket
                                            </a>
                                            <!--end::Action-->
                                        </div>
                                        <!--end::Heading-->

                                        <!--begin::Illustration-->
                                        <div class="text-center pb-15 px-5">
                                            <img src="<?php echo base_url();?>/assets/user/media/illustrations/sketchy-1/2.png" alt="" class="mw-100 h-200px h-sm-325px">
                                        </div>
                                        <!--end::Illustration-->
                                    </div>
                                    <!--end::Card body-->
                                </div>
                                <!--end::Card-->
                            </div>




        <div class="modal fade" id="kt_modal_new_address" tabindex="-1"  aria-modal="true" role="dialog">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Form-->
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_new_address_header">
                    <!--begin::Modal title-->
                    <h2>Add New Ticket</h2>
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

          <form method="post" id="uploadticketimage" 
                    enctype="multipart/form-data" 
                    data-kt-redirect-url="<?php echo base_url(); ?>user/support" 
                    action="<?php echo base_url();?>user/create-ticket">

                        <!--begin::Input group-->
                        <div class="row mb-5">
                            <div class="col-md-12 fv-row fv-plugins-icon-container fv-plugins-bootstrap5-row-invalid">
                                <label class="required fs-5 fw-semibold mb-2">Ticket Subject</label>
                                <input type="text" name="ticket_message" class="form-control form-control-solid" placeholder="">
                            </div>
                        </div>
                        <!--end::Input group-->


                         <!--begin::Input group-->
                        <div class="row mb-5">
                            <div class="col-md-12 fv-row fv-plugins-icon-container fv-plugins-bootstrap5-row-invalid">
                                <label class="required fs-5 fw-semibold mb-2">Ticket Discription</label>
                                <textarea class="form-control form-control-solid" name="ticket_discription"  placeholder=""  required></textarea>
                            </div>
                        </div>
                        <!--end::Input group-->


                         <!--begin::Input group-->
                        <div class="row mb-5">
                            <div class="col-lg-12 text-center">
                                    <label class="col-lg-6 col-form-label fw-semibold fs-6">Screenshort</label>   
                                        <div class>
                                        <div class="image-input image-input-outline" data-kt-image-input="true" 
                                        style="background-image: url('<?php echo base_url();?>/assets/admin/media/svg/avatars/blank.svg')">
                                            <div class="image-input-wrapper w-125px h-125px" 
                                            style="background-image: url('<?php echo base_url()."assets/images/".$footer_log; ?>')" alt="Logo">
                                        </div>
                                            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                                                <i class="ki-duotone ki-pencil fs-7"><span class="path1"></span><span class="path2"></span></i>
                                                <input type="file" id="fileInput" name="ticketimage" accept=".png, .jpg, .jpeg" >
                                                <input type="hidden" name="ticketimage_remove">
                                            </label>
                                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" 
                                            data-bs-toggle="tooltip" title="Cancel avatar">
                                                <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>                            
                                            </span>
                                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" 
                                            data-bs-toggle="tooltip" title="Remove avatar">
                                                <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>                            
                                            </span>
                                        </div>
                                        <div class="form-text"><?php echo lang('image_validation_label'); ?></div>
                                    </div>
                                </div>
                        </div>
                        <!--end::Input group-->
                     
                    </div>
                    <!--end::Scroll-->
                </div>
                <!--end::Modal body-->

                <!--begin::Modal footer-->
                <div class="modal-footer flex-center">
                    <!--begin::Button-->
                    <button type="reset" data-bs-dismiss="modal" class="btn btn-light me-3">
                        Discard
                    </button>
                    <!--end::Button-->

                    <!--begin::Button-->
                    <button type="submit" id="kt_modal_new_address_submit" class="btn btn-primary">
                        <span class="indicator-label">
                            Submit
                        </span>
                        <span class="indicator-progress">
                            Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                    <!--end::Button-->
                </div>
                <!--end::Modal footer-->
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
        <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/user-edit-support.js?ver=4.9"></script>
    </body>

    </html>