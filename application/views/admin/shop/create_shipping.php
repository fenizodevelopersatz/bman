<?php $this->load->view('admin/Layout/common_style'); ?>

<link href="<?php echo base_url(); ?>/assets/admin/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet"
    type="text/css">
<link href="<?php echo base_url(); ?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.css" rel="stylesheet"
    type="text/css">
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet"
    type="text/css" />

<style>
    .h-md-40 {
        min-height: 42%;
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

<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
    data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
    data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
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
                                        <?php echo $title; ?>
                                    </h1>

                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                        <li class="breadcrumb-item text-muted">
                                            <a href="<?php echo base_url(); ?>" class="text-muted text-hover-primary">
                                                Network Management
                                            </a>
                                        </li>
                                        <li class="breadcrumb-item">
                                            <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                        </li>
                                        <li class="breadcrumb-item text-muted">
                                            <?php echo $title; ?>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!--end::Toolbar-->
                        <div id="kt_app_content" class="app-content  flex-column-fluid mt-10">

                            <div id="kt_app_content_container" class="app-container  container-xxl ">


                                <div class="card mb-5 mb-xl-10 ">
                                    <div class="card-header border-0 cursor-pointer p-3" role="button"
                                        data-bs-toggle="collapse" data-bs-target="#kt_account_addagent_form_details"
                                        aria-expanded="true" aria-controls="kt_account_addagent_form_details">
                                        <div class="card-title m-0">

                                            <div
                                                class="me-3 d-flex justify-content-between text-center align-items-center gap-4">
                                                <div
                                                    class="d-flex flex-center w-60px h-60px rounded-3 bg-light-danger bg-opacity-90">
                                                    <i class="ki-duotone ki-abstract-26 text-danger fs-3x"><span
                                                            class="path1"></span><span class="path2"></span></i>
                                                </div>
                                                <h3 class="fw-bold m-0"><?php echo $card_title ?? ''; ?></h3>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- INSEIDE FORM ENTER -->
                                    <div id="kt_account_settings_profile_details" class="collapse show">

                                        <?= form_open_multipart($action, [
                                            'class' => 'form-validate',
                                            'method' => 'post',
                                            'autocomplete' => 'off',
                                            'id' => 'kt_shipping_zone_form',
                                            'data-kt-redirect-url' => $redirect,
                                            'enctype' => 'multipart/form-data'
                                        ]) ?>

                                        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>"
                                            value="<?= $this->security->get_csrf_hash(); ?>" />

                                        <?php if (!empty($zone->id)): ?>
                                            <input type="hidden" name="id" value="<?= $zone->id ?>">
                                        <?php endif; ?>

                                        <div class="card-body border-top p-9">

                                            <!-- Pincode -->
                                            <div class="row mb-6 fv-row">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Pincode <span
                                                        class="text-danger">*</span></label>
                                                <div class="col-lg-8">
                                                    <input type="text" name="pincode"
                                                        class="form-control form-control-solid" required
                                                        value="<?= set_value('pincode', $zone->pincode ?? '') ?>"
                                                        <?= !empty($zone->id) ? 'readonly' : '' ?> />
                                                </div>
                                            </div>

                                            <!-- Zone Name -->
                                            <div class="row mb-6 fv-row">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Zone
                                                    Name</label>
                                                <div class="col-lg-8">
                                                    <input type="text" name="zone_name"
                                                        class="form-control form-control-solid"
                                                        value="<?= set_value('zone_name', $zone->zone_name ?? '') ?>" />
                                                </div>
                                            </div>

                                            <!-- Shipping Charge -->
                                            <div class="row mb-6 fv-row">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Shipping Charge
                                                    <span class="text-danger">*</span></label>
                                                <div class="col-lg-8">
                                                    <input type="number" step="0.01" name="shipping_charge"
                                                        class="form-control form-control-solid" required
                                                        value="<?= set_value('shipping_charge', $zone->shipping_charge ?? '0.00') ?>" />
                                                </div>
                                            </div>

                                            <!-- COD Available -->
                                            <div class="row mb-6 fv-row">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">COD
                                                    Available</label>
                                                <div class="col-lg-8">
                                                    <div
                                                        class="form-check form-switch form-check-custom form-check-solid">
                                                        <input class="form-check-input" type="checkbox" value="1"
                                                            name="cod_available" <?= isset($zone->cod_available) && $zone->cod_available ? 'checked' : '' ?>>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Status -->
                                            <div class="row mb-6 fv-row">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Status</label>
                                                <div class="col-lg-8">
                                                    <select name="status" class="form-control form-control-solid">
                                                        <option value="1" <?= (set_value('status', $zone->status ?? '') == 1) ? 'selected' : '' ?>>Active</option>
                                                        <option value="0" <?= (set_value('status', $zone->status ?? '') == 0) ? 'selected' : '' ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="card-footer d-flex justify-content-end py-6 px-9">
                                            <button type="submit" id="kt_shipping_zone_form_button"
                                                class="btn btn-primary">Save Shipping Zone</button>
                                        </div>

                                        <?= form_close(); ?>

                                    </div>
                                </div>
                            </div>

                            <!--begin::Footer-->
                            <?php $this->load->view('admin/Layout/admin_footer'); ?>

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

            <?php $this->load->view('admin/Layout/common_script'); ?>

            <script
                src="<?php echo base_url(); ?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.js"></script>
            <script src="<?php echo base_url(); ?>/assets/admin/js/widgets.bundle.js"></script>
            <script src="<?php echo base_url(); ?>/assets/admin/js/custom/widgets.js"></script>
            <script src="<?php echo base_url(); ?>/assets/admin/js/custom/apps/chat/chat.js"></script>
            <script src="<?php echo base_url(); ?>/assets/admin/js/custom/utilities/modals/upgrade-plan.js"></script>
            <script src="<?php echo base_url(); ?>/assets/admin/js/custom/utilities/modals/users-search.js"></script>
            <script src="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.js"></script>
            <script src="<?php echo base_url(); ?>assets/admin/plugins/custom/datatables/datatables.bundle.js"></script>
            <link href="<?php echo base_url(); ?>assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.css"
                rel="stylesheet" type="text/css" />
            <script
                src="<?php echo base_url(); ?>assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.js"></script>
            <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

            <script>
                const base_url = '<?php echo base_url(); ?>';
            </script>
            <script
                src="<?php echo base_url(); ?>/assets/admin/js/custom/authentication/sign-in/create-shipping.js?ver=2.9"></script>
            <script>
            </script>
</body>

</html>