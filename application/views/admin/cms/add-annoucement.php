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
        #ann-crop-stage {
            position: relative;
            width: 600px;
            max-width: 100%;
            height: 300px;
            background: #0b0b0f;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #ann-crop-box {
            position: absolute;
            border: 2px dashed #fff;
            box-shadow: 0 0 0 2000px rgba(0,0,0,.45);
            cursor: move;
            touch-action: none;
        }
        .ann-handle {
            position: absolute;
            right: -7px;
            bottom: -7px;
            width: 14px;
            height: 14px;
            background: #fff;
            border: 2px solid #6E56CF;
            border-radius: 50%;
            cursor: nwse-resize;
            touch-action: none;
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


                        <div id="kt_account_addagent_form_details" class="collapse show">
                        <div class="card-body border-top p-9">
                        
                        <?php $action = base_url()."announcement-add"; ?>
                        <?= form_open($action, ['class' => 'form-validate', 'method' => 'post', 'autocomplete' => 'off', 'id' => 'kt_account_meta_details_form',"data-kt-redirect-url"=> base_url()."announcement-cms"]) ?>
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>" />  
                                      
                 
                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Type<span class="text-danger"> * </span></label>
                        <div class="col-lg-8 fv-row">
                        <div class="d-flex gap-6">
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="announcement_type" value="text"
                                    <?php echo ($announcement_type !== 'image') ? 'checked' : ''; ?>>
                                <span class="form-check-label">Text (background color + text)</span>
                            </label>
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="announcement_type" value="image"
                                    <?php echo ($announcement_type === 'image') ? 'checked' : ''; ?>>
                                <span class="form-check-label">Image</span>
                            </label>
                        </div>
                        </div>
                        </div>

                        <div id="text-section">
                            <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">Announcement Text<span class="text-danger"> * </span></label>
                            <div class="col-lg-8 fv-row">
                            <div class="input-group mb-5">
                            <span class="input-group-text border-transparent " id="basic-addon1"><i class="fa-solid fa-note-sticky "></i></span>
                            <textarea name="announcement_content"
                            class="form-control form-control-lg form-control-solid"
                            placeholder="Enter Announcement Content"><?php echo htmlspecialchars($announcement_content); ?></textarea>
                            </div>
                            </div>
                            </div>

                            <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">Background Color</label>
                            <div class="col-lg-8 fv-row">
                                <input type="color" name="bg_color" class="form-control form-control-lg form-control-solid w-100px"
                                    value="<?php echo htmlspecialchars($bg_color); ?>">
                            </div>
                            </div>
                        </div>

                        <div id="image-section" style="display:none;" data-has-image="<?php echo !empty($image) ? '1' : '0'; ?>">
                            <?php if (!empty($image)): ?>
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Current Image</label>
                                <div class="col-lg-8 fv-row">
                                    <img src="<?php echo base_url($image); ?>" style="max-width:300px;border-radius:8px;border:1px solid #333;">
                                    <div class="text-muted fs-8 mt-1">Choose a new file below to replace it.</div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">Image<span class="text-danger"> * </span></label>
                            <div class="col-lg-8 fv-row">
                                <input type="file" id="ann-file-input" accept="image/png,image/jpeg,image/webp" class="form-control form-control-lg form-control-solid mb-2">
                                <div class="text-muted fs-8 mb-4">JPG, PNG or WEBP, max 3MB. Recommended aspect ratio 3:1 (e.g. 1200×400) — crop below.</div>

                                <div id="ann-cropper-wrap" style="display:none;">
                                    <div id="ann-crop-stage">
                                        <canvas id="ann-src-canvas"></canvas>
                                        <div id="ann-crop-box">
                                            <div class="ann-handle"></div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-3 mt-3">
                                        <label class="fs-8 text-muted mb-0">Zoom</label>
                                        <input type="range" id="ann-zoom" min="100" max="300" value="100" class="form-range" style="width:200px">
                                        <button type="button" class="btn btn-sm btn-primary ms-auto" id="ann-apply-crop">Apply Crop</button>
                                    </div>
                                </div>

                                <div id="ann-crop-preview-wrap" style="display:none;" class="mt-4">
                                    <label class="form-label fs-8">Cropped Preview</label><br>
                                    <img id="ann-crop-preview" style="max-width:400px;border-radius:8px;border:1px solid #333;">
                                </div>

                                <input type="hidden" name="cropped_image_data" id="ann-cropped-data">
                            </div>
                            </div>
                        </div>

                        <input type="hidden" name="announcement_id" value="<?php echo $announcement_id; ?>" />
                                                
                        <div class="col-md-12">
                        <div class="form-group"><button type="submit" id="kt_account_meta_details_submit"
                        class="btn btn-lg btn-primary">Save</button>
                        </div>
                        </div>

                    </div>
                    </div>

                    </div>
                    </div>




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
            <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/announcement-edit-settings.js?ver=2.9"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/announcement-cropper.js?ver=1.0"></script>

    </body>

    </html>