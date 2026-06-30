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



                     <!-- INSEIDE FORM ENTER -->

                               <div id="kt_account_settings_profile_details" class="collapse show">

                               <form 
                               id="kt_account_meta_details_form" 
                               class="form w-100 fv-plugins-bootstrap5 fv-plugins-framework" 
                               method="post"
                               novalidate="novalidate" 
                               data-kt-redirect-url="<?php echo base_url(); ?>pagelink-settings" 
                               action="<?php echo base_url();?>update-pagelink-settings"
                               enctype="multipart/form-data"
                               >

                               <div class="card-body border-top p-9">


                                <div class="card card-flush py-4 mb-10">
    <div class="card-header">
        <div class="card-title">
            <h2 class="fw-bold">Whitepaper</h2>
        </div>
    </div>
    <div class="card-body pt-0">

        <!-- Whitepaper Status -->
        <div class="col-lg-6">
            <div class="row mb-6">
                <label class="col-lg-8 col-form-label fw-semibold fs-6">Whitepaper Status<span class="text-danger"> * </span></label>
                <div class="col-lg-4 fv-row">
                    <div class="input-group mb-5">
                        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                            <input class="form-check-input h-30px w-50px" name="whitepaper_status" type="checkbox" value="1"
                            <?php echo $sections['White Paper']->page_status ? 'checked' : ''; ?> 
                                id="whitepaper_status" />
                            <label class="form-check-label" for="direct_commission_status"></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

       

        <!-- Whitepaper Title -->
        <div class="row mb-6 whitepaper-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Title<span class="text-danger"> * </span></label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="text" name="whitepaper_title" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter whitepaper title" 
                        value="<?= isset($sections['White Paper']) ? htmlspecialchars($sections['White Paper']->page_title, ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
            </div>
        </div>

        <!-- Whitepaper Content -->
        <div class="row mb-6 whitepaper-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Content<span class="text-danger"> * </span></label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="text" name="whitepaper_content" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter whitepaper content" 
                        value="<?= isset($sections['White Paper']) ? htmlspecialchars($sections['White Paper']->page_content, ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
            </div>
        </div>

        <!-- Whitepaper File Upload -->
        <div class="row mb-6 whitepaper-toggle-target">
    <label class="col-lg-4 col-form-label fw-semibold fs-6">
        File <span class="text-danger"> * </span>
    </label>
    <div class="col-lg-8 fv-row">
        <div class="input-group mb-2">
            <span class="input-group-text border-transparent" id="basic-addon1">
                <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
            </span>
            <input type="file" name="whitepaper_image" 
                class="form-control form-control-lg form-control-solid" 
                value="<?= base_url('uploads/images/' . $sections['White Paper']->page_image); ?>"
                required>
        </div>

        <?php if (!empty($sections['White Paper']->page_image)): ?>
            <small class="form-text text-muted">
                <?= base_url('uploads/images/' . $sections['White Paper']->page_image); ?>
            </small>
        <?php else: ?>
            <small class="form-text text-muted">No image uploaded yet.</small>
        <?php endif; ?>
    </div>
</div>


        <!-- Additional Input (Initially Hidden) -->
        <div class="row mb-6" id="whitepaper_extra_input" style="display: none;">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">
                Document <span class="text-danger"> * </span>
            </label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="file" name="whitepaper_document" 
                        class="form-control form-control-lg form-control-solid" 
                        required>
                </div>
                <?php if (!empty($sections['White Paper']->page_document)): ?>
            <small class="form-text text-muted">
                <?= base_url('uploads/documents/' . $sections['White Paper']->page_document); ?>
            </small>
        <?php else: ?>
            <small class="form-text text-muted">No document uploaded yet.</small>
        <?php endif; ?>
            </div>
        </div>


    </div>
</div>


<div class="card card-flush py-4 mb-10">
    <div class="card-header">
        <div class="card-title">
            <h2 class="fw-bold">Project</h2>
        </div>
    </div>
    <div class="card-body pt-0">

        <!-- project Status -->
        <div class="col-lg-6">
            <div class="row mb-6">
                <label class="col-lg-8 col-form-label fw-semibold fs-6">Project Status<span class="text-danger"> * </span></label>
                <div class="col-lg-4 fv-row">
                    <div class="input-group mb-5">
                        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                            <input class="form-check-input h-30px w-50px" name="project_status" type="checkbox" value="1"
                            <?php echo $sections['Project']->page_status ? 'checked' : ''; ?> 
                                id="project_status" />
                            <label class="form-check-label" for="direct_commission_status"></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Whitepaper Title -->
        <div class="row mb-6 project-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Title<span class="text-danger"> * </span></label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="text" name="project_title" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter whitepaper title" 
                        value="<?= isset($sections['Project']) ? htmlspecialchars($sections['Project']->page_title, ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
            </div>
        </div>

        <!-- Whitepaper Content -->
        <div class="row mb-6 project-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Content<span class="text-danger"> * </span></label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="text" name="project_content" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter whitepaper content" 
                        value="<?= isset($sections['Project']) ? htmlspecialchars($sections['Project']->page_content, ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
            </div>
        </div>

        <!-- Whitepaper File Upload -->
        <div class="row mb-6 project-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">
                File <span class="text-danger"> * </span>
            </label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="file" name="project_image" 
                        class="form-control form-control-lg form-control-solid" 
                        required>
                </div>
                <?php if (!empty($sections['Project']->page_image)): ?>
            <small class="form-text text-muted">
                <?= base_url('uploads/images/' . $sections['Project']->page_image); ?>
            </small>
        <?php else: ?>
            <small class="form-text text-muted">No image uploaded yet.</small>
        <?php endif; ?>
            </div>
        </div>
        <!-- Additional Input (Initially Hidden) -->
        <div class="row mb-6" id="project_extra_input" style="display: none;">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">
                Document <span class="text-danger"> * </span>
            </label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="file" name="project_document" 
                        class="form-control form-control-lg form-control-solid" 
                        required>
                </div>
                <?php if (!empty($sections['Project']->page_document)): ?>
            <small class="form-text text-muted">
                <?= base_url('uploads/documents/' . $sections['Project']->page_document); ?>
            </small>
        <?php else: ?>
            <small class="form-text text-muted">No document uploaded yet.</small>
        <?php endif; ?>
            </div>
        </div>

    </div>
</div>
  <input type='hidden' name="test_field" value="1" />

<div class="card card-flush py-4 mb-10">
    <div class="card-header">
        <div class="card-title">
            <h2 class="fw-bold">Roadmap</h2>
        </div>
    </div>
    <div class="card-body pt-0">

        <!-- Roadmap Status -->
        <div class="col-lg-6">
            <div class="row mb-6">
                <label class="col-lg-8 col-form-label fw-semibold fs-6">Roadmap Status<span class="text-danger"> * </span></label>
                <div class="col-lg-4 fv-row">
                    <div class="input-group mb-5">
                        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                            <input class="form-check-input h-30px w-50px" name="roadmap_status" type="checkbox" value="1"
                            <?php echo $sections['Roadmap']->page_status ? 'checked' : ''; ?> 
                                id="roadmap_status" />
                            <label class="form-check-label" for="roadmap_status"></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Whitepaper Title -->
        <div class="row mb-6 roadmap-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Title<span class="text-danger"> * </span></label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="text" name="roadmap_title" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter whitepaper title" 
                        value="<?= isset($sections['Roadmap']) ? htmlspecialchars($sections['Roadmap']->page_title, ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
            </div>
        </div>

        <!-- Whitepaper Content -->
        <div class="row mb-6 roadmap-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Content<span class="text-danger"> * </span></label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="text" name="roadmap_content" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter whitepaper content" 
                        value="<?= isset($sections['Roadmap']) ? htmlspecialchars($sections['Roadmap']->page_content, ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
            </div>
        </div>

        <!-- Whitepaper File Upload -->
        <div class="row mb-6 roadmap-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">
                File <span class="text-danger"> * </span>
            </label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="file" name="roadmap_image" 
                        class="form-control form-control-lg form-control-solid" 
                        required>
                </div>
                <?php if (!empty($sections['Roadmap']->page_image)): ?>
            <small class="form-text text-muted">
                <?= base_url('uploads/images/' . $sections['Roadmap']->page_image); ?>
            </small>
        <?php else: ?>
            <small class="form-text text-muted">No image uploaded yet.</small>
        <?php endif; ?>
            </div>
        </div>

         <!-- Additional Input (Initially Hidden) -->
         <div class="row mb-6" id="roadmap_extra_input" style="display: none;">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">
                Document <span class="text-danger"> * </span>
            </label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="file" name="roadmap_document" 
                        class="form-control form-control-lg form-control-solid" 
                        required>
                </div>
                <?php if (!empty($sections['Roadmap']->page_document)): ?>
            <small class="form-text text-muted">
                <?= base_url('uploads/documents/' . $sections['Roadmap']->page_document); ?>
            </small>
        <?php else: ?>
            <small class="form-text text-muted">No document uploaded yet.</small>
        <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<div class="card card-flush py-4 mb-10">
    <div class="card-header">
        <div class="card-title">
            <h2 class="fw-bold">AI Robotics</h2>
        </div>
    </div>
    <div class="card-body pt-0">

        <!-- Whitepaper Status -->
        <div class="col-lg-6">
            <div class="row mb-6">
                <label class="col-lg-8 col-form-label fw-semibold fs-6">AI Robotics Status<span class="text-danger"> * </span></label>
                <div class="col-lg-4 fv-row">
                    <div class="input-group mb-5">
                        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                            <input class="form-check-input h-30px w-50px" name="airobotics_status" type="checkbox" value="1"
                            <?php echo $sections['ai robotics']->page_status ? 'checked' : ''; ?> 
                                id="airobotics_status" />
                            <label class="form-check-label" for="direct_commission_status"></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Whitepaper Title -->
        <div class="row mb-6 airobotics-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Title<span class="text-danger"> * </span></label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="text" name="airobotics_title" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter whitepaper title" 
                        value="<?= isset($sections['ai robotics']) ? htmlspecialchars($sections['ai robotics']->page_title, ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
            </div>
        </div>

        <!-- Whitepaper Content -->
        <div class="row mb-6 airobotics-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Content<span class="text-danger"> * </span></label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="text" name="airobotics_content" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter whitepaper content" 
                        value="<?= isset($sections['ai robotics']) ? htmlspecialchars($sections['ai robotics']->page_content, ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
            </div>
        </div>

        <!-- Whitepaper File Upload -->
        <div class="row mb-6 airobotics-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">
                File <span class="text-danger"> * </span>
            </label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="file" name="airobotics_image" 
                        class="form-control form-control-lg form-control-solid" 
                        required>
                </div>
                <?php if (!empty($sections['ai robotics']->page_image)): ?>
            <small class="form-text text-muted">
                <?= base_url('uploads/images/' . $sections['ai robotics']->page_image); ?>
            </small>
        <?php else: ?>
            <small class="form-text text-muted">No image uploaded yet.</small>
        <?php endif; ?>
            </div>
        </div>

         <!-- Additional Input (Initially Hidden) -->
         <div class="row mb-6" id="airobotics_extra_input" style="display: none;">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">
                Document <span class="text-danger"> * </span>
            </label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="file" name="airobotics_document" 
                        class="form-control form-control-lg form-control-solid" 
                        required>
                </div>
                <?php if (!empty($sections['ai robotics']->page_document)): ?>
            <small class="form-text text-muted">
                <?= base_url('uploads/documents/' . $sections['ai robotics']->page_document); ?>
            </small>
        <?php else: ?>
            <small class="form-text text-muted">No document uploaded yet.</small>
        <?php endif; ?>
            </div>
        </div>

    </div>
</div>
                                </div>

                                <div class="card card-flush py-4 mb-10">
    <div class="card-header">
        <div class="card-title">
            <h2 class="fw-bold">E-commerce</h2>
        </div>
    </div>
    <div class="card-body pt-0">

        <!-- Whitepaper Status -->
        <div class="col-lg-6">
            <div class="row mb-6">
                <label class="col-lg-8 col-form-label fw-semibold fs-6">E-commerce Status<span class="text-danger"> * </span></label>
                <div class="col-lg-4 fv-row">
                    <div class="input-group mb-5">
                        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                            <input class="form-check-input h-30px w-50px" name="ecommerce_status" type="checkbox" value="1"
                            <?php echo $sections['E-commerce']->page_status ? 'checked' : ''; ?>                                 id="ecommerce_status" />
                            <label class="form-check-label" for="direct_commission_status"></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        

        <!-- Whitepaper Title -->
        <div class="row mb-6 e-commerce-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Title<span class="text-danger"> * </span></label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="text" name="ecommerce_title" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter whitepaper title" 
                        value="<?= isset($sections['E-commerce']) ? htmlspecialchars($sections['E-commerce']->page_title, ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
            </div>
        </div>

        <!-- Whitepaper Content -->
        <div class="row mb-6 e-commerce-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Content<span class="text-danger"> * </span></label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="text" name="ecommerce_content" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter whitepaper content" 
                        value="<?= isset($sections['E-commerce']) ? htmlspecialchars($sections['E-commerce']->page_content, ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
            </div>
        </div>

        <!-- Whitepaper File Upload -->
        <div class="row mb-6 e-commerce-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">
                File <span class="text-danger"> * </span>
            </label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="file" name="ecommerce_image" 
                        class="form-control form-control-lg form-control-solid" 
                        required>
                </div>
                <?php if (!empty($sections['E-commerce']->page_image)): ?>
            <small class="form-text text-muted">
                <?= base_url('uploads/images/' . $sections['E-commerce']->page_image); ?>
            </small>
        <?php else: ?>
            <small class="form-text text-muted">No image uploaded yet.</small>
        <?php endif; ?>
            </div>
        </div>

        <!-- Additional Input (Initially Hidden) -->
        <div class="row mb-6" id="e-commerce_extra_input" style="display: none;">
    <label class="col-lg-4 col-form-label fw-semibold fs-6">
        Document <span class="text-danger"> * </span>
    </label>
    <div class="col-lg-8 fv-row">
        <div class="input-group mb-2">
            <span class="input-group-text border-transparent" id="basic-addon1">
                <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
            </span>
            <input type="file" name="ecommerce_document" 
                class="form-control form-control-lg form-control-solid" 
                required>
        </div>

        <!-- Show current document path -->
        <?php if (!empty($sections['E-commerce']->page_document)): ?>
            <small class="form-text text-muted">
                Current: <?= base_url('uploads/documents/' . $sections['E-commerce']->page_document); ?>
            </small>
        <?php else: ?>
            <small class="form-text text-muted">No document uploaded yet.</small>
        <?php endif; ?>
    </div>
</div>

    </div>
</div>

<div class="card card-flush py-4 mb-10">
    <div class="card-header">
        <div class="card-title">
            <h2 class="fw-bold">Games</h2>
        </div>
    </div>
    <div class="card-body pt-0">

        <!-- Whitepaper Status -->
        <div class="col-lg-6">
            <div class="row mb-6">
                <label class="col-lg-8 col-form-label fw-semibold fs-6">Games Status<span class="text-danger"> * </span></label>
                <div class="col-lg-4 fv-row">
                    <div class="input-group mb-5">
                        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                            <input class="form-check-input h-30px w-50px" name="games_status" type="checkbox" value="1"
                            <?php echo $sections['Games']->page_status ? 'checked' : ''; ?>                                 id="games_status" />
                            <label class="form-check-label" for="direct_commission_status"></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Whitepaper Title -->
        <div class="row mb-6 games-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Title<span class="text-danger"> * </span></label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="text" name="games_title" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter whitepaper title" 
                        value="<?= isset($sections['Games']) ? htmlspecialchars($sections['Games']->page_title, ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
            </div>
        </div>

        <!-- Whitepaper Content -->
        <div class="row mb-6 games-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Content<span class="text-danger"> * </span></label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="text" name="games_content" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter whitepaper content" 
                        value="<?= isset($sections['Games']) ? htmlspecialchars($sections['Games']->page_content, ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
            </div>
        </div>

        <!-- Whitepaper File Upload -->
        <div class="row mb-6 games-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">
                File <span class="text-danger"> * </span>
            </label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="file" name="games_image" 
                        class="form-control form-control-lg form-control-solid" 
                        required>
                </div>
                <?php if (!empty($sections['Games']->page_image)): ?>
            <small class="form-text text-muted">
                <?= base_url('uploads/images/' . $sections['Games']->page_image); ?>
            </small>
        <?php else: ?>
            <small class="form-text text-muted">No image uploaded yet.</small>
        <?php endif; ?>
            </div>
        </div>

         <!-- Additional Input (Initially Hidden) -->
         <div class="row mb-6" id="games_extra_input" style="display: none;">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">
                Document <span class="text-danger"> * </span>
            </label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="file" name="games_document" 
                        class="form-control form-control-lg form-control-solid" 
                        required>
                </div>
                <?php if (!empty($sections['Games']->page_document)): ?>
            <small class="form-text text-muted">
                <?= base_url('uploads/documents/' . $sections['Games']->page_document); ?>
            </small>
        <?php else: ?>
            <small class="form-text text-muted">No document uploaded yet.</small>
        <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<div class="card card-flush py-4 mb-10">
    <div class="card-header">
        <div class="card-title">
            <h2 class="fw-bold">Education</h2>
        </div>
    </div>
    <div class="card-body pt-0">

        <!-- Whitepaper Status -->
        <div class="col-lg-6">
            <div class="row mb-6">
                <label class="col-lg-8 col-form-label fw-semibold fs-6">Education Status<span class="text-danger"> * </span></label>
                <div class="col-lg-4 fv-row">
                    <div class="input-group mb-5">
                        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                            <input class="form-check-input h-30px w-50px" name="education_status" type="checkbox" value="1"
                            <?php echo $sections['education']->page_status ? 'checked' : ''; ?>                                 id="education_status" />
                            <label class="form-check-label" for="direct_commission_status"></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Whitepaper Title -->
        <div class="row mb-6 education-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Title<span class="text-danger"> * </span></label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="text" name="education_title" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter whitepaper title" 
                        value="<?= isset($sections['education']) ? htmlspecialchars($sections['education']->page_title, ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
            </div>
        </div>

        <!-- Whitepaper Content -->
        <div class="row mb-6 education-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">Content<span class="text-danger"> * </span></label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="text" name="education_content" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter whitepaper content" 
                        value="<?= isset($sections['education']) ? htmlspecialchars($sections['education']->page_content, ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
            </div>
        </div>

        <!-- Whitepaper File Upload -->
        <div class="row mb-6 education-toggle-target">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">
                File <span class="text-danger"> * </span>
            </label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="file" name="education_image" 
                        class="form-control form-control-lg form-control-solid" 
                        required>
                </div>
                <?php if (!empty($sections['education']->page_image)): ?>
            <small class="form-text text-muted">
                <?= base_url('uploads/images/' . $sections['education']->page_image); ?>
            </small>
        <?php else: ?>
            <small class="form-text text-muted">No image uploaded yet.</small>
        <?php endif; ?>
            </div>
        </div>

        <!-- Additional Input (Initially Hidden) -->
        <div class="row mb-6" id="education_extra_input" style="display: none;">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">
                Document <span class="text-danger"> * </span>
            </label>
            <div class="col-lg-8 fv-row">
                <div class="input-group mb-5">
                    <span class="input-group-text border-transparent" id="basic-addon1">
                        <i class="ki-duotone ki-pencil"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <input type="file" name="education_document" 
                        class="form-control form-control-lg form-control-solid" 
                        required>
                </div>
                <?php if (!empty($sections['education']->page_document)): ?>
            <small class="form-text text-muted">
                <?= base_url('uploads/documents/' . $sections['education']->page_document); ?>
            </small>
        <?php else: ?>
            <small class="form-text text-muted">No document uploaded yet.</small>
        <?php endif; ?>
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
            <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/pagelink-settings.js?ver=2.9"></script>


            <script>
            </script>
    </body>

    </html>