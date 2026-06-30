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

    .mini-muted {
        font-size: 12px;
        color: #7e8299;
    }
</style>

<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
    data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
    data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">

            <?php $this->load->view('admin/Layout/admin_topbar'); ?>

            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                <?php $this->load->view('admin/Layout/admin_sidebar'); ?>

                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">

                        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                                    <h1
                                        class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                                        <?php echo $title; ?>
                                    </h1>
                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                        <li class="breadcrumb-item text-muted">
                                            <a href="<?php echo base_url(); ?>"
                                                class="text-muted text-hover-primary">Earnings Settings</a>
                                        </li>
                                        <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span>
                                        </li>
                                        <li class="breadcrumb-item text-muted"><?php echo $title; ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div id="kt_app_content" class="app-content flex-column-fluid mt-10">
                            <div id="kt_app_content_container" class="app-container container-xxl">

                                <?php $this->load->view('notification'); ?>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="card mb-5 mb-xxl-8">

                                            <div class="card-header mb-4 border-transparent">
                                                <h3 class="anchor fw-bold"><?php echo $card_tilte; ?></h3>
                                            </div>

                                            <div class="card-body pt-9 pb-9">

                                                <div class="d-flex flex-stack mb-5">
                                                    <div class="d-flex align-items-center position-relative my-1">
                                                        <i class="ki-duotone ki-magnifier fs-1 position-absolute ms-6">
                                                            <span class="path1"></span><span class="path2"></span>
                                                        </i>
                                                        <input type="text" data-kt-docs-table-filter="search"
                                                            class="form-control form-control-solid w-250px ps-15"
                                                            placeholder="Search Methods" />
                                                    </div>

                                                    <div class="d-flex justify-content-end"
                                                        data-kt-docs-table-toolbar="base">
                                                        <button type="button" class="btn btn-light-primary"
                                                            data-kt-menu-trigger="click"
                                                            data-kt-menu-placement="bottom-end">
                                                            <i class="ki-duotone ki-exit-down fs-2"><span
                                                                    class="path1"></span><span class="path2"></span></i>
                                                            Export Report
                                                        </button>

                                                        <div id="kt_datatable_example_export_menu"
                                                            class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                                                            data-kt-menu="true">
                                                            <div class="menu-item px-3"><a href="#"
                                                                    class="menu-link px-3" data-kt-export="copy">Copy to
                                                                    clipboard</a></div>
                                                            <div class="menu-item px-3"><a href="#"
                                                                    class="menu-link px-3" data-kt-export="excel">Export
                                                                    as Excel</a></div>
                                                            <div class="menu-item px-3"><a href="#"
                                                                    class="menu-link px-3" data-kt-export="csv">Export
                                                                    as CSV</a></div>
                                                            <div class="menu-item px-3"><a href="#"
                                                                    class="menu-link px-3" data-kt-export="pdf">Export
                                                                    as PDF</a></div>
                                                            <div id="kt_datatable_example_buttons" class="d-none"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <table id="kt-earning-methods-table"
                                                    class="table align-middle table-row-dashed fs-6 gy-5">
                                                    <thead>
                                                        <tr
                                                            class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                                            <th>#</th>
                                                            <th>Method</th>
                                                            <th>Reward</th>
                                                            <th>Daily Target</th>
                                                            <th>Est. Time</th>
                                                            <th>Button</th>
                                                            <th>Sort</th>
                                                            <th>Status</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="text-gray-600 fw-semibold"></tbody>
                                                </table>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ✅ EDIT MODAL -->
                                <div class="modal fade" id="kt_modal_edit_method" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered mw-900px">
                                        <div class="modal-content">
                                            <div class="modal-header pb-0 border-0 justify-content-end">
                                                <div class="btn btn-sm btn-icon" data-bs-dismiss="modal">
                                                    <i class="ki-outline ki-cross fs-1"></i>
                                                </div>
                                            </div>

                                            <div class="modal-body scroll-y mx-5 mx-xl-15 pt-0 pb-15">
                                                <div class="text-center mb-10">
                                                    <h2 class="mb-3">Update Reward Settings</h2>
                                                    <div class="mini-muted">Update Watch Ads / Watch Videos card
                                                        settings</div>
                                                </div>

                                                <form id="kt_method_edit_form" autocomplete="off">
                                                    <input type="hidden" id="method_id" value="">
                                                    <input type="hidden"
                                                        name="<?php echo $this->security->get_csrf_token_name(); ?>"
                                                        value="<?php echo $this->security->get_csrf_hash(); ?>" />

                                                    <div class="row g-5">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Title *</label>
                                                            <input type="text" name="title" id="m_title"
                                                                class="form-control form-control-solid" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Subtitle *</label>
                                                            <input type="text" name="subtitle" id="m_subtitle"
                                                                class="form-control form-control-solid" required>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Icon (class)</label>
                                                            <input type="text" name="icon" id="m_icon"
                                                                class="form-control form-control-solid"
                                                                placeholder="ph-television">
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Progress Color</label>
                                                            <input type="text" name="progress_color"
                                                                id="m_progress_color"
                                                                class="form-control form-control-solid"
                                                                placeholder="#d63384">
                                                        </div>

                                                        <div class="col-md-4">
                                                            <label class="form-label fw-semibold">Badge Text *</label>
                                                            <input type="text" name="badge_text" id="m_badge_text"
                                                                class="form-control form-control-solid" required>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label fw-semibold">Badge BG *</label>
                                                            <input type="text" name="badge_bg" id="m_badge_bg"
                                                                class="form-control form-control-solid"
                                                                placeholder="#fff7ed" required>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label fw-semibold">Badge Color *</label>
                                                            <input type="text" name="badge_color" id="m_badge_color"
                                                                class="form-control form-control-solid"
                                                                placeholder="#ea580c" required>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Button Text *</label>
                                                            <input type="text" name="btn_text" id="m_btn_text"
                                                                class="form-control form-control-solid" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Button Gradient
                                                                *</label>
                                                            <input type="text" name="btn_gradient" id="m_btn_gradient"
                                                                class="form-control form-control-solid"
                                                                placeholder="linear-gradient(135deg, #d63384 0%, #a01a5d 100%)"
                                                                required>
                                                        </div>

                                                        <div class="col-md-4">
                                                            <label class="form-label fw-semibold">Daily Target *</label>
                                                            <input type="number" min="0" name="daily_target"
                                                                id="m_daily_target"
                                                                class="form-control form-control-solid" required>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label fw-semibold">Reward USD *</label>
                                                            <input type="number" step="0.01" min="0" name="reward_usd"
                                                                id="m_reward_usd"
                                                                class="form-control form-control-solid" required>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label fw-semibold">Est Time Label
                                                                *</label>
                                                            <input type="text" name="est_time_label"
                                                                id="m_est_time_label"
                                                                class="form-control form-control-solid"
                                                                placeholder="30 Sec / 3 Mins" required>
                                                        </div>

                                                        <div class="col-md-4">
                                                            <label class="form-label fw-semibold">Sort Order *</label>
                                                            <input type="number" min="1" name="sort_order"
                                                                id="m_sort_order"
                                                                class="form-control form-control-solid" required>
                                                        </div>

                                                        <div class="col-md-4 d-flex align-items-center">
                                                            <div
                                                                class="form-check form-switch form-check-custom form-check-success form-check-solid mt-6">
                                                                <input class="form-check-input h-30px w-50px"
                                                                    type="checkbox" name="is_active" id="m_is_active"
                                                                    value="1">
                                                                <label
                                                                    class="form-check-label fw-semibold ms-3">Active</label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex justify-content-end mt-10">
                                                        <button type="button" class="btn btn-light me-3"
                                                            data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary"
                                                            id="btn_method_save">Update</button>
                                                    </div>

                                                </form>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- /modal -->

                            </div>
                        </div>

                    </div>

                    <?php $this->load->view('admin/Layout/admin_footer'); ?>
                </div>
            </div>

        </div>
    </div>

    <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
        <i class="ki-duotone ki-arrow-up"><span class="path1"></span><span class="path2"></span></i>
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

    <script>
        const base_url = '<?php echo base_url(); ?>';
    </script>

    <script
        src="<?php echo base_url(); ?>/assets/admin/js/custom/authentication/sign-in/earning-methods-list.js?ver=1.0"></script>

</body>

</html>