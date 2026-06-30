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
                                                Settings
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
                                                <h3 class="fw-bold m-0"><?php echo $card_title; ?></h3>
                                            </div>

                                        </div>
                                    </div>


                                    <div id="kt_account_addagent_form_details" class="collapse show">
                                        <div class="card-body border-top p-9">

                                            <?= form_open($action, ['class' => 'form-validate', 'method' => 'post', 'autocomplete' => 'off', 'id' => 'kt_account_meta_details_form', "data-kt-redirect-url" => $redirect]) ?>
                                            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>"
                                                value="<?= $this->security->get_csrf_hash(); ?>" />


                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Rank Name <span
                                                        class="text-danger"> * </span></label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="input-group mb-5">
                                                        <span class="input-group-text border-transparent "
                                                            id="basic-addon1"><i
                                                                class="fa-solid fa-note-sticky "></i></span>
                                                        <input type="text" name="rank_name"
                                                            class="form-control form-control-lg form-control-solid"
                                                            placeholder="Enter Rank Name"
                                                            value="<?php echo $rank_name; ?>" required>
                                                    </div>
                                                </div>
                                            </div>


                                            <!-- <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Rank Left Leg Total Investment  <span class="text-danger"> * </span></label>
                        <div class="col-lg-8 fv-row">
                        <div class="input-group mb-5">
                        <span class="input-group-text border-transparent " id="basic-addon1"><i class="fa-solid fa-note-sticky "></i></span>
                        <input type="text" name="left_leg_investment" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter Left Leg Value" 
                        value="<?php echo $left_leg_investment; ?>" required>
                        </div>
                        </div>
                        </div>

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Rank Right Leg Total Investment<span class="text-danger"> * </span></label>
                        <div class="col-lg-8 fv-row">
                        <div class="input-group mb-5">
                        <span class="input-group-text border-transparent " id="basic-addon1"><i class="fa-solid fa-note-sticky "></i></span>
                        <input type="text" name="right_leg_investment" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter Right Leg Value" 
                        value="<?php echo $right_leg_investment; ?>" required>
                        </div>
                        </div>
                        </div> -->

                                            <?php if ($rank_id == "1") { ?>

                                                <div class="row mb-6">
                                                    <label class="col-lg-4 col-form-label fw-semibold fs-6">Rank Eligible
                                                        Amount<span class="text-danger"> * </span></label>
                                                    <div class="col-lg-8 fv-row">
                                                        <div class="input-group mb-5">
                                                            <span class="input-group-text border-transparent "
                                                                id="basic-addon1"><i
                                                                    class="fa-solid fa-note-sticky "></i></span>
                                                            <input type="text" name="rank_eligibel_amt"
                                                                class="form-control form-control-lg form-control-solid"
                                                                placeholder="Enter Eligible Amount"
                                                                value="<?php echo $rank_eligibel_amt ?? ''; ?>" required>
                                                        </div>
                                                    </div>
                                                </div>

                                            <?php } ?>


                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Rank Bonus <span
                                                        class="text-danger"> * </span></label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="input-group mb-5">
                                                        <span class="input-group-text border-transparent "
                                                            id="basic-addon1"><?php echo $currency_info->currency_symbol; ?></span>
                                                        <input type="text" name="rank_bonus"
                                                            class="form-control form-control-lg form-control-solid"
                                                            placeholder="Enter Rank Bonus"
                                                            value="<?php echo currency_format_no_symbol($rank_bonus ?? ''); ?>"
                                                            required>
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="col-lg-6">
                                                <div class="row mb-6">
                                                    <label class="col-lg-8 col-form-label fw-semibold fs-6">Rank Bonus
                                                        (%) or Fiat<span class="text-danger"> * </span></label>
                                                    <div class="col-lg-4 fv-row">
                                                        <div class="input-group mb-5">
                                                            <div
                                                                class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                                                <select name="rank_bonus_type" class="form-select"
                                                                    data-control="select2"
                                                                    data-placeholder="Select an option">
                                                                    <option value="1" <?php echo isset($rank_bonus_type) && $rank_bonus_type == "1" ? "selected" : ""; ?>>
                                                                        Percentage (%) </option>
                                                                    <option value="0" <?php echo isset($rank_bonus_type) && $rank_bonus_type == "0" ? "selected" : ""; ?>>
                                                                        Fiat
                                                                        (<?php echo $currency_info->currency_symbol; ?>)
                                                                    </option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <input type="hidden" name="currency_id" value="<?php echo $rank_id; ?>" />

                                            <div class="col-md-12">
                                                <div class="form-group"><button type="submit"
                                                        id="kt_account_meta_details_submit"
                                                        class="btn btn-lg btn-primary">Save Informations</button>
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

    <script src="<?php echo base_url(); ?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.js"></script>
    <script src="<?php echo base_url(); ?>/assets/admin/js/widgets.bundle.js"></script>
    <script src="<?php echo base_url(); ?>/assets/admin/js/custom/widgets.js"></script>
    <script src="<?php echo base_url(); ?>/assets/admin/js/custom/apps/chat/chat.js"></script>
    <script src="<?php echo base_url(); ?>/assets/admin/js/custom/utilities/modals/upgrade-plan.js"></script>
    <script src="<?php echo base_url(); ?>/assets/admin/js/custom/utilities/modals/users-search.js"></script>
    <script src="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.js"></script>
    <script src="<?php echo base_url(); ?>assets/admin/plugins/custom/datatables/datatables.bundle.js"></script>
    <link href="<?php echo base_url(); ?>assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.css"
        rel="stylesheet" type="text/css" />
    <script src="<?php echo base_url(); ?>assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.js"></script>

    <script>
        const base_url = '<?php echo base_url(); ?>';
        const rank_id = '<?php echo $rank_id; ?>';
    </script>
    <script
        src="<?php echo base_url(); ?>/assets/admin/js/custom/authentication/sign-in/rank-edit-settings.js?ver=2.9"></script>


    <script>
    </script>
</body>

</html>