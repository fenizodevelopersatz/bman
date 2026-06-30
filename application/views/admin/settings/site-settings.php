<!DOCTYPE html>
<html lang="en">

<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?php echo base_url(); ?>assets/admin/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet"
    type="text/css" />

<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
    data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
    data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page  flex-column flex-column-fluid " id="kt_app_page">

            <!--  Header   -->
            <?php
            $this->load->view('admin/Layout/admin_topbar');
            ?>

            <!--begin::Wrapper-->
            <div class="app-wrapper  flex-column flex-row-fluid " id="kt_app_wrapper">

                <?php $this->load->view('admin/Layout/admin_sidebar'); ?>

                <!--begin::Main-->
                <div class="app-main flex-column flex-row-fluid " id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">

                        <!--begin::Toolbar-->
                        <div id="kt_app_toolbar" class="app-toolbar  py-3 py-lg-6 ">
                            <div id="kt_app_toolbar_container" class="app-container  container-xxl d-flex flex-stack ">
                                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3 ">

                                    <h1
                                        class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                                        <?php echo lang('site_settings'); ?>
                                    </h1>

                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                        <li class="breadcrumb-item text-muted">
                                            <a href="<?php echo base_url(); ?>" class="text-muted text-hover-primary">
                                                <?php echo lang('settings'); ?>
                                            </a>
                                        </li>
                                        <li class="breadcrumb-item">
                                            <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                        </li>
                                        <li class="breadcrumb-item text-muted">
                                            <?php echo lang('site_settings'); ?>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>


                        <div id="kt_app_content" class="app-content  flex-column-fluid ">
                            <div id="kt_app_content_container" class="app-container  container-xxl ">

                                <div class="row gy-5 g-xl-10">



                                    <!-- INSEIDE FORM ENTER -->
                                    <div class="col-xl-4">
                                        <div class="card mb-5 mb-xl-10">
                                            <div class="card-header border-0 cursor-pointer p-3" role="button"
                                                data-bs-toggle="collapse" data-bs-target="#kt_account_profile_details"
                                                aria-expanded="true" aria-controls="kt_account_profile_details">
                                                <div class="card-title m-0">

                                                    <div
                                                        class="me-3 d-flex justify-content-between text-center align-items-center gap-4">
                                                        <div
                                                            class="d-flex flex-center w-60px h-60px rounded-3 bg-light-info bg-opacity-90">
                                                            <i class="ki-duotone ki-setting-2 text-info fs-3x">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </div>
                                                        <h3 class="fw-bold m-0">
                                                            <?php echo lang('site_settings_logo_title'); ?></h3>
                                                    </div>

                                                </div>
                                            </div>

                                            <div id="kt_account_settings_profile_details" class="collapse show">

                                                <form id="kt_account_profile_details_form"
                                                    class="form w-100 fv-plugins-bootstrap5 fv-plugins-framework"
                                                    method="post" novalidate="novalidate"
                                                    data-kt-redirect-url="<?php echo base_url(); ?>site-settings"
                                                    action="<?php echo base_url(); ?>site-settings-image"
                                                    enctype="multipart/form-data">

                                                    <div class="card-body border-top p-9">

                                                        <div class="row mb-6">
                                                            <div class="col-lg-12 text-center">
                                                                <label
                                                                    class="col-lg-6 col-form-label fw-semibold fs-6"><?php echo lang('header_white_logo'); ?></label>
                                                                <div class>
                                                                    <div class="image-input image-input-outline"
                                                                        data-kt-image-input="true"
                                                                        style="background-image: url('<?php echo base_url(); ?>/assets/admin/media/svg/avatars/blank.svg')">
                                                                        <div class="image-input-wrapper w-125px h-125px"
                                                                            style="background-image: url('<?php echo base_url() . "assets/images/" . $logo_image; ?>')">
                                                                        </div>
                                                                        <label
                                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                                            data-kt-image-input-action="change"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Change avatar">
                                                                            <i class="ki-duotone ki-pencil fs-7"><span
                                                                                    class="path1"></span><span
                                                                                    class="path2"></span></i>
                                                                            <input type="file" name="header_log"
                                                                                accept=".png, .jpg, .jpeg" value=""
                                                                                required>
                                                                            <input type="hidden"
                                                                                name="header_log_remove">
                                                                        </label>
                                                                        <span
                                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                                            data-kt-image-input-action="cancel"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Cancel avatar">
                                                                            <i class="ki-duotone ki-cross fs-2"><span
                                                                                    class="path1"></span><span
                                                                                    class="path2"></span></i>
                                                                        </span>
                                                                        <span
                                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                                            data-kt-image-input-action="remove"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Remove avatar">
                                                                            <i class="ki-duotone ki-cross fs-2"><span
                                                                                    class="path1"></span><span
                                                                                    class="path2"></span></i>
                                                                        </span>
                                                                    </div>
                                                                    <div class="form-text">
                                                                        <?php echo lang('image_validation_label'); ?>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-lg-12 text-center">
                                                                <label
                                                                    class="col-lg-6 col-form-label fw-semibold fs-6">Footer
                                                                    White Logo</label>
                                                                <div class>
                                                                    <div class="image-input image-input-outline"
                                                                        data-kt-image-input="true"
                                                                        style="background-image: url('<?php echo base_url(); ?>/assets/admin/media/svg/avatars/blank.svg')">
                                                                        <div class="image-input-wrapper w-125px h-125px"
                                                                            style="background-image: url('<?php echo base_url() . "assets/images/" . $footer_log; ?>')"
                                                                            alt="Logo">
                                                                        </div>
                                                                        <label
                                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                                            data-kt-image-input-action="change"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Change avatar">
                                                                            <i class="ki-duotone ki-pencil fs-7"><span
                                                                                    class="path1"></span><span
                                                                                    class="path2"></span></i>
                                                                            <input type="file" name="footer_log"
                                                                                accept=".png, .jpg, .jpeg" required>
                                                                            <input type="hidden"
                                                                                name="footer_log_remove">
                                                                        </label>
                                                                        <span
                                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                                            data-kt-image-input-action="cancel"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Cancel avatar">
                                                                            <i class="ki-duotone ki-cross fs-2"><span
                                                                                    class="path1"></span><span
                                                                                    class="path2"></span></i>
                                                                        </span>
                                                                        <span
                                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                                            data-kt-image-input-action="remove"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Remove avatar">
                                                                            <i class="ki-duotone ki-cross fs-2"><span
                                                                                    class="path1"></span><span
                                                                                    class="path2"></span></i>
                                                                        </span>
                                                                    </div>
                                                                    <div class="form-text">
                                                                        <?php echo lang('image_validation_label'); ?>
                                                                    </div>
                                                                </div>
                                                            </div>


                                                            <div class="col-lg-12 text-center">
                                                                <label
                                                                    class="col-lg-6 col-form-label fw-semibold fs-6">Header
                                                                    Dark Logo</label>
                                                                <div class>
                                                                    <div class="image-input image-input-outline"
                                                                        data-kt-image-input="true"
                                                                        style="background-image: url('<?php echo base_url(); ?>/assets/admin/media/svg/avatars/blank.svg')">
                                                                        <div class="image-input-wrapper w-125px h-125px"
                                                                            style="background-image: url('<?php echo base_url() . "assets/images/" . $logo_dark_image; ?>')">
                                                                        </div>
                                                                        <label
                                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                                            data-kt-image-input-action="change"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Change avatar">
                                                                            <i class="ki-duotone ki-pencil fs-7"><span
                                                                                    class="path1"></span><span
                                                                                    class="path2"></span></i>
                                                                            <input type="file" name="header_dark_log"
                                                                                accept=".png, .jpg, .jpeg" value=""
                                                                                required>
                                                                            <input type="hidden"
                                                                                name="header_dark_log_remove">
                                                                        </label>
                                                                        <span
                                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                                            data-kt-image-input-action="cancel"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Cancel avatar">
                                                                            <i class="ki-duotone ki-cross fs-2"><span
                                                                                    class="path1"></span><span
                                                                                    class="path2"></span></i>
                                                                        </span>
                                                                        <span
                                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                                            data-kt-image-input-action="remove"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Remove avatar">
                                                                            <i class="ki-duotone ki-cross fs-2"><span
                                                                                    class="path1"></span><span
                                                                                    class="path2"></span></i>
                                                                        </span>
                                                                    </div>
                                                                    <div class="form-text">
                                                                        <?php echo lang('image_validation_label'); ?>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-lg-12 text-center">
                                                                <label
                                                                    class="col-lg-6 col-form-label fw-semibold fs-6">Footer
                                                                    Dark Logo</label>
                                                                <div class>
                                                                    <div class="image-input image-input-outline"
                                                                        data-kt-image-input="true"
                                                                        style="background-image: url('<?php echo base_url(); ?>/assets/admin/media/svg/avatars/blank.svg')">
                                                                        <div class="image-input-wrapper w-125px h-125px"
                                                                            style="background-image: url('<?php echo base_url() . "assets/images/" . $footer_dark_log; ?>')"
                                                                            alt="Logo">
                                                                        </div>
                                                                        <label
                                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                                            data-kt-image-input-action="change"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Change avatar">
                                                                            <i class="ki-duotone ki-pencil fs-7"><span
                                                                                    class="path1"></span><span
                                                                                    class="path2"></span></i>
                                                                            <input type="file" name="footer_dark_log"
                                                                                accept=".png, .jpg, .jpeg" required>
                                                                            <input type="hidden"
                                                                                name="footer_log_dark_remove">
                                                                        </label>
                                                                        <span
                                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                                            data-kt-image-input-action="cancel"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Cancel avatar">
                                                                            <i class="ki-duotone ki-cross fs-2"><span
                                                                                    class="path1"></span><span
                                                                                    class="path2"></span></i>
                                                                        </span>
                                                                        <span
                                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                                            data-kt-image-input-action="remove"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Remove avatar">
                                                                            <i class="ki-duotone ki-cross fs-2"><span
                                                                                    class="path1"></span><span
                                                                                    class="path2"></span></i>
                                                                        </span>
                                                                    </div>
                                                                    <div class="form-text">
                                                                        <?php echo lang('image_validation_label'); ?>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-lg-12 text-center">
                                                                <label
                                                                    class="col-lg-4 col-form-label fw-semibold fs-6"><?php echo lang('fav_img'); ?></label>
                                                                <div class>
                                                                    <div class="image-input image-input-outline"
                                                                        data-kt-image-input="true"
                                                                        style="background-image: url('<?php echo base_url(); ?>/assets/admin/media/svg/avatars/blank.svg')">
                                                                        <div class="image-input-wrapper w-125px h-125px"
                                                                            style="background-image: url('<?php echo base_url() . "assets/images/" . $fav_img; ?>')"
                                                                            alt="fav">
                                                                        </div>
                                                                        <label
                                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                                            data-kt-image-input-action="change"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Change avatar">
                                                                            <i class="ki-duotone ki-pencil fs-7"><span
                                                                                    class="path1"></span><span
                                                                                    class="path2"></span></i>
                                                                            <input type="file" name="fav_logo" value=""
                                                                                accept=".png, .jpg, .jpeg" required>
                                                                            <input type="hidden" name="fav_logo_remove">
                                                                        </label>
                                                                        <span
                                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                                            data-kt-image-input-action="cancel"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Cancel avatar">
                                                                            <i class="ki-duotone ki-cross fs-2"><span
                                                                                    class="path1"></span><span
                                                                                    class="path2"></span></i>
                                                                        </span>
                                                                        <span
                                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                                            data-kt-image-input-action="remove"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Remove avatar">
                                                                            <i class="ki-duotone ki-cross fs-2"><span
                                                                                    class="path1"></span><span
                                                                                    class="path2"></span></i>
                                                                        </span>
                                                                    </div>
                                                                    <div class="form-text">
                                                                        <?php echo lang('image_validation_label'); ?>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-lg-12 text-center">
                                                                <label
                                                                    class="col-lg-4 col-form-label fw-semibold fs-6"><?php echo lang('o_g_image'); ?></label>
                                                                <div class>
                                                                    <div class="image-input image-input-outline"
                                                                        data-kt-image-input="true"
                                                                        style="background-image: url('<?php echo base_url(); ?>/assets/admin/media/svg/avatars/blank.svg')">
                                                                        <div class="image-input-wrapper w-125px h-125px"
                                                                            style="background-image: url('<?php echo base_url() . "assets/images/" . $og_img; ?>')">
                                                                        </div>
                                                                        <label
                                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                                            data-kt-image-input-action="change"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Change avatar">
                                                                            <i class="ki-duotone ki-pencil fs-7"><span
                                                                                    class="path1"></span><span
                                                                                    class="path2"></span></i>
                                                                            <input type="file" name="og_img" value=""
                                                                                accept=".png, .jpg, .jpeg" required>
                                                                            <input type="hidden" name="og_img_remove">
                                                                        </label>
                                                                        <span
                                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                                            data-kt-image-input-action="cancel"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Cancel avatar">
                                                                            <i class="ki-duotone ki-cross fs-2"><span
                                                                                    class="path1"></span><span
                                                                                    class="path2"></span></i>
                                                                        </span>
                                                                        <span
                                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                                            data-kt-image-input-action="remove"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Remove avatar">
                                                                            <i class="ki-duotone ki-cross fs-2"><span
                                                                                    class="path1"></span><span
                                                                                    class="path2"></span></i>
                                                                        </span>
                                                                    </div>
                                                                    <div class="form-text">
                                                                        <?php echo lang('image_validation_label'); ?>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        </div>

                                                    </div>
                                                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                                                        <button type="submit" class="btn btn-primary"
                                                            id="kt_account_profile_details_submit"><?php echo lang('save_changes'); ?></button>
                                                    </div>
                                                </form>

                                            </div>

                                        </div>
                                    </div>
                                    <!-- INSEIDE FORM ENTER -->


                                    <!-- INSEIDE FORM ENTER -->
                                    <div class="col-xl-8">

                                        <div class="card mb-5 mb-xl-10 ">
                                            <div class="card-header border-0 cursor-pointer p-3" role="button"
                                                data-bs-toggle="collapse" data-bs-target="#kt_account_meta_details_form"
                                                aria-expanded="true" aria-controls="kt_account_meta_details_form">
                                                <div class="card-title m-0">

                                                    <div
                                                        class="me-3 d-flex justify-content-between text-center align-items-center gap-4">
                                                        <div
                                                            class="d-flex flex-center w-60px h-60px rounded-3 bg-light-danger bg-opacity-90">
                                                            <i class="ki-duotone ki-abstract-26 text-danger fs-3x"><span
                                                                    class="path1"></span><span class="path2"></span></i>
                                                        </div>
                                                        <h3 class="fw-bold m-0">Meta Details</h3>
                                                    </div>

                                                </div>
                                            </div>

                                            <div id="kt_account_settings_profile_details" class="collapse show">

                                                <form id="kt_account_meta_details_form"
                                                    class="form w-100 fv-plugins-bootstrap5 fv-plugins-framework"
                                                    method="post" novalidate="novalidate"
                                                    data-kt-redirect-url="<?php echo base_url(); ?>site-settings"
                                                    action="<?php echo base_url(); ?>site-settings-meta"
                                                    enctype="multipart/form-data">

                                                    <div class="card-body border-top p-9">

                                                        <div class="row mb-6">
                                                            <label
                                                                class="col-lg-4 col-form-label fw-semibold fs-6">Company
                                                                Name <span class="text-danger"> * </span></label>
                                                            <div class="col-lg-8 fv-row">
                                                                <div class="input-group mb-5">
                                                                    <span class="input-group-text border-transparent "
                                                                        id="basic-addon1"><i
                                                                            class="fa-solid fa-note-sticky "></i></span>
                                                                    <input type="text" name="site_name"
                                                                        class="form-control form-control-lg form-control-solid"
                                                                        placeholder="Company Name"
                                                                        value="<?php echo $site_name; ?>" required>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row mb-6">
                                                            <label class="col-lg-4 col-form-label fw-semibold fs-6">Site
                                                                URL <span class="text-danger"> * </span></label>
                                                            <div class="col-lg-8 fv-row">
                                                                <div class="input-group mb-5">
                                                                    <span class="input-group-text border-transparent "
                                                                        id="basic-addon1"><i
                                                                            class="fa-regular fa-star"></i></span>
                                                                    <input type="text" name="site_url"
                                                                        class="form-control form-control-lg form-control-solid"
                                                                        placeholder="Website URL"
                                                                        value="<?php echo $site_url; ?>" required>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row mb-6">
                                                            <label class="col-lg-4 col-form-label fw-semibold fs-6">
                                                                Meta Title <span class="text-danger"> * </span> </label>
                                                            <div class="col-lg-8 fv-row">
                                                                <div class="input-group mb-5">
                                                                    <span class="input-group-text border-transparent "
                                                                        id="basic-addon1"><i
                                                                            class="fa-solid fa-pen-nib"></i></span>
                                                                    <input type="text" name="site_title"
                                                                        class="form-control form-control-lg form-control-solid"
                                                                        placeholder="Meta Title"
                                                                        value="<?php echo $site_title; ?>" required>
                                                                </div>
                                                            </div>
                                                        </div>


                                                        <div class="row mb-6">
                                                            <label class="col-lg-4 col-form-label fw-semibold fs-6">
                                                                Meta Keyword </label>
                                                            <div class="col-lg-8 fv-row">
                                                                <textarea
                                                                    class="form-control form-control-lg form-control-solid mb-3"
                                                                    rows="1" name="meta_keyword" data-kt-element="input"
                                                                    placeholder="Enter Keyword"><?php echo $site_metakeyword; ?></textarea>
                                                            </div>
                                                        </div>



                                                        <div class="row mb-6">
                                                            <label class="col-lg-4 col-form-label fw-semibold fs-6">
                                                                Meta Description </label>
                                                            <div class="col-lg-8 fv-row">
                                                                <textarea
                                                                    class="form-control form-control-lg form-control-solid mb-3"
                                                                    rows="1" name="meta_discription"
                                                                    data-kt-element="input"
                                                                    placeholder="Enter Discription"><?php echo $site_metadescription; ?></textarea>
                                                            </div>
                                                        </div>

                                                        <div class="row mb-6">
                                                            <label class="col-lg-4 col-form-label fw-semibold fs-6">
                                                                Copyright Text </label>
                                                            <div class="col-lg-8 fv-row">
                                                                <input type="text"
                                                                    class="form-control form-control-lg form-control-solid"
                                                                    name="site_copyright"
                                                                    value="<?php echo isset($site_copyright) ? html_escape($site_copyright) : ''; ?>"
                                                                    placeholder="Copyright &amp; design by @ThemeAdapt - 2026">
                                                                <div class="form-text">Shown in the footer of the landing / home page.</div>
                                                            </div>
                                                        </div>

                                                    </div>
                                                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                                                        <button type="submit" class="btn btn-primary"
                                                            id="kt_account_meta_details_submit">Save Changes</button>
                                                    </div>
                                                </form>

                                            </div>

                                        </div>



                                        <div class="card mb-5 mb-xl-10 ">
                                            <div class="card-header border-0 cursor-pointer p-3" role="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#kt_account_contact_details_form" aria-expanded="true"
                                                aria-controls="kt_account_contact_details_form">
                                                <div class="card-title m-0">

                                                    <div
                                                        class="me-3 d-flex justify-content-between text-center align-items-center gap-4">
                                                        <div
                                                            class="d-flex flex-center w-60px h-60px rounded-3 bg-light-warning bg-opacity-90">
                                                            <i class="ki-duotone ki-abstract-25 text-warning fs-3x"><span
                                                                    class="path1"></span><span class="path2"></span></i>
                                                        </div>
                                                        <h3 class="fw-bold m-0">Contact Details</h3>
                                                    </div>

                                                </div>
                                            </div>

                                            <div id="kt_account_settings_profile_details" class="collapse show">

                                                <form id="kt_account_contact_details_form"
                                                    class="form w-100 fv-plugins-bootstrap5 fv-plugins-framework"
                                                    method="post" novalidate="novalidate"
                                                    data-kt-redirect-url="<?php echo base_url(); ?>site-settings"
                                                    action="<?php echo base_url(); ?>site-settings-contact"
                                                    enctype="multipart/form-data">

                                                    <div class="card-body border-top p-9">

                                                        <div class="row mb-6">
                                                            <label
                                                                class="col-lg-4 col-form-label fw-semibold fs-6">Company
                                                                E-mail <span class="text-danger"> * </span></label>
                                                            <div class="col-lg-8 fv-row">
                                                                <div class="input-group mb-5">
                                                                    <span class="input-group-text border-transparent "
                                                                        id="basic-addon1"><i
                                                                            class="fa-solid fa-note-sticky "></i></span>
                                                                    <input type="text" name="contact_email"
                                                                        class="form-control form-control-lg form-control-solid"
                                                                        placeholder="Contact Email"
                                                                        value="<?php echo $contact_email; ?>" required>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row mb-6">
                                                            <label
                                                                class="col-lg-4 col-form-label fw-semibold fs-6">Company
                                                                Contact Number <span class="text-danger"> *
                                                                </span></label>
                                                            <div class="col-lg-8 fv-row">
                                                                <div class="input-group mb-5">
                                                                    <span class="input-group-text border-transparent "
                                                                        id="basic-addon1"><i
                                                                            class="fa-regular fa-star"></i></span>
                                                                    <input type="text" name="contact_number"
                                                                        class="form-control form-control-lg form-control-solid"
                                                                        placeholder="Contact Phone"
                                                                        value="<?php echo $contact_number; ?>" required>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row mb-6">
                                                            <label
                                                                class="col-lg-4 col-form-label fw-semibold fs-6">Company
                                                                Address <span class="text-danger"> * </span></label>
                                                            <div class="col-lg-8 fv-row">
                                                                <div class="input-group mb-5">
                                                                    <span class="input-group-text border-transparent "
                                                                        id="basic-addon1"><i
                                                                            class="fa-solid fa-note-sticky "></i></span>
                                                                    <textarea type="text" name="company_address"
                                                                        class="form-control form-control-lg form-control-solid"
                                                                        placeholder="Company Address"
                                                                        value="<?php echo $company_address; ?>"
                                                                        required><?php echo $company_address; ?></textarea>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>
                                                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                                                        <button type="submit" class="btn btn-primary"
                                                            id="kt_account_contact_details_submit">Save Changes</button>
                                                    </div>
                                                </form>

                                            </div>

                                        </div>


                                        <!-- /************************** WEBSITE COSTAMIZATION DIV *************************/ -->


                                        <div class="card mb-5 mb-xl-10 ">
                                            <div class="card-header border-0 cursor-pointer p-3" role="button"
                                                data-bs-toggle="collapse" data-bs-target="#website_meta_details_form"
                                                aria-expanded="true" aria-controls="website_meta_details_form">
                                                <div class="card-title m-0">

                                                    <div
                                                        class="me-3 d-flex justify-content-between text-center align-items-center gap-4">
                                                        <div
                                                            class="d-flex flex-center w-60px h-60px rounded-3 bg-light-danger bg-opacity-90">
                                                            <i class="ki-duotone ki-abstract-26 text-danger fs-3x"><span
                                                                    class="path1"></span><span class="path2"></span></i>
                                                        </div>
                                                        <h3 class="fw-bold m-0">Website Config Details</h3>
                                                    </div>

                                                </div>
                                            </div>

                                            <div id="kt_account_settings_profile_details" class="collapse show">

                                                <form id="website_meta_details_form"
                                                    class="form w-100 fv-plugins-bootstrap5 fv-plugins-framework"
                                                    method="post" novalidate="novalidate"
                                                    data-kt-redirect-url="<?php echo base_url(); ?>site-settings"
                                                    action="<?php echo base_url(); ?>site-settings-config"
                                                    enctype="multipart/form-data">

                                                    <div class="card-body border-top p-9 ">

                                                        <div class="row">

                                                            <div class="col-lg-6">
                                                                <div class="row mb-6">
                                                                    <label
                                                                        class="col-lg-8 col-form-label fw-semibold fs-6">Landing
                                                                        Page Status <span class="text-danger"> *
                                                                        </span></label>
                                                                    <div class="col-lg-4 fv-row">
                                                                        <div class="input-group mb-5">
                                                                            <div
                                                                                class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                                                                <input
                                                                                    class="form-check-input  h-30px w-50px"
                                                                                    type="checkbox" value="1"
                                                                                    name="landing_id" <?php echo $landing_status ? "checked" : ""; ?>
                                                                                    id="landing_id" />
                                                                                <label class="form-check-label"
                                                                                    for="landing_id">
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- <div class="col-lg-6">
                                                                    <div class="row mb-6">
                                                                    <label class="col-lg-8 col-form-label fw-semibold fs-6">User KYC Status <span class="text-danger"> * </span></label>
                                                                    <div class="col-lg-4 fv-row">
                                                                    <div class="input-group mb-5">
                                                                    <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                                                    <input class="form-check-input  h-30px w-50px" type="checkbox" value="" name="kyc_status"
                                                                    <?php echo $kyc_status ? "checked" : ""; ?> 
                                                                    id="kyc_status"/>
                                                                    <label class="form-check-label" for="kyc_status">
                                                                    </label>
                                                                    </div>
                                                                    </div>
                                                                    </div>
                                                                    </div>
                                                                    </div> -->

                                                            <div class="col-lg-6">
                                                                <div class="row mb-6">
                                                                    <label
                                                                        class="col-lg-8 col-form-label fw-semibold fs-6">Register
                                                                        Email Verify <span class="text-danger"> *
                                                                        </span></label>
                                                                    <div class="col-lg-4 fv-row">
                                                                        <div class="input-group mb-5">
                                                                            <div
                                                                                class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                                                                <input
                                                                                    class="form-check-input  h-30px w-50px"
                                                                                    type="checkbox" value="1"
                                                                                    name="email_verify" <?php echo $email_verify ? "checked" : ""; ?>
                                                                                    id="email_verify" />
                                                                                <label class="form-check-label"
                                                                                    for="email_verify">
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>


                                                            <div class="col-lg-6">
                                                                <div class="row mb-6">
                                                                    <label
                                                                        class="col-lg-8 col-form-label fw-semibold fs-6">Use
                                                                        Captcha<span class="text-danger"> *
                                                                        </span></label>
                                                                    <div class="col-lg-4 fv-row">
                                                                        <div class="input-group mb-5">
                                                                            <div
                                                                                class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                                                                <input
                                                                                    class="form-check-input  h-30px w-50px"
                                                                                    type="checkbox" value="1"
                                                                                    name="use_captcha" <?php echo $use_captcha ? "checked" : ""; ?>
                                                                                    id="use_captcha" />
                                                                                <label class="form-check-label"
                                                                                    for="use_captcha">
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>



                                                            <div class="col-lg-6">
                                                                <div class="row mb-6">
                                                                    <label
                                                                        class="col-lg-8 col-form-label fw-semibold fs-6">Allow
                                                                        Registration<span class="text-danger"> *
                                                                        </span></label>
                                                                    <div class="col-lg-4 fv-row">
                                                                        <div class="input-group mb-5">
                                                                            <div
                                                                                class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                                                                <input
                                                                                    class="form-check-input  h-30px w-50px"
                                                                                    type="checkbox" value="1"
                                                                                    name="register_status" <?php echo $register_status ? "checked" : ""; ?>
                                                                                    id="register_status" />
                                                                                <label class="form-check-label"
                                                                                    for="register_status">
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-lg-6">
                                                                <div class="row mb-6">
                                                                    <label
                                                                        class="col-lg-8 col-form-label fw-semibold fs-6">Allow
                                                                        Login<span class="text-danger"> *
                                                                        </span></label>
                                                                    <div class="col-lg-4 fv-row">
                                                                        <div class="input-group mb-5">
                                                                            <div
                                                                                class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                                                                <input
                                                                                    class="form-check-input  h-30px w-50px"
                                                                                    type="checkbox" value="1"
                                                                                    name="allow_login" <?php echo $allow_login ? "checked" : ""; ?>
                                                                                    id="allow_login" />
                                                                                <label class="form-check-label"
                                                                                    for="allow_login">
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-lg-6">
                                                                <div class="row mb-6">
                                                                    <label
                                                                        class="col-lg-8 col-form-label fw-semibold fs-6">Unique
                                                                        Mobile<span class="text-danger"> *
                                                                        </span></label>
                                                                    <div class="col-lg-4 fv-row">
                                                                        <div class="input-group mb-5">
                                                                            <div
                                                                                class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                                                                <input
                                                                                    class="form-check-input  h-30px w-50px"
                                                                                    type="checkbox" value="1"
                                                                                    name="unique_mobile" <?php echo $unique_mobile ? "checked" : ""; ?>
                                                                                    id="unique_mobile" />
                                                                                <label class="form-check-label"
                                                                                    for="unique_mobile">
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-lg-6">
                                                                <div class="row mb-6">
                                                                    <label
                                                                        class="col-lg-8 col-form-label fw-semibold fs-6">Unique
                                                                        Email<span class="text-danger"> *
                                                                        </span></label>
                                                                    <div class="col-lg-4 fv-row">
                                                                        <div class="input-group mb-5">
                                                                            <div
                                                                                class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                                                                <input
                                                                                    class="form-check-input  h-30px w-50px"
                                                                                    type="checkbox" value="1"
                                                                                    name="unique_email" <?php echo $unique_email ? "checked" : ""; ?>
                                                                                    id="unique_email" />
                                                                                <label class="form-check-label"
                                                                                    for="unique_email">
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- <div class="col-lg-6">
                                <div class="row mb-6">
                                <label class="col-lg-8 col-form-label fw-semibold fs-6">Only Allow Invited Users<span class="text-danger"> * </span></label>
                                <div class="col-lg-4 fv-row">
                                <div class="input-group mb-5">
                                <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                <input class="form-check-input  h-30px w-50px" type="checkbox" value="" name="allow_referral_only"
                                <?php echo $allow_referral_only ? "checked" : ""; ?> 
                                id="allow_referral_only"/>
                                <label class="form-check-label" for="allow_referral_only">
                                </label>
                                </div>
                                </div>
                                </div>
                                </div>
                                </div>
                                 -->


                                                        </div>


                                                    </div>
                                                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                                                        <button type="submit" class="btn btn-primary"
                                                            id="kt_account_config_details_submit">Save Changes</button>
                                                    </div>
                                                </form>

                                            </div>

                                        </div>


                                    </div>
                                    <!-- INSEIDE FORM ENTER -->



                                </div>


                            </div>
                            <?php $this->load->view('admin/Layout/admin_footer'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
                <i class="ki-duotone ki-arrow-up">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </div>

            <?php $this->load->view('admin/Layout/common_script'); ?>
            <script
                src="<?php echo base_url(); ?>/assets/admin/js/custom/authentication/sign-in/site-settings.js"></script>

</body>

</html>