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
                                        <li class="breadcrumb-item text-muted">
                                            <?php echo $title; ?>
                                        </li>
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
                                                <h3 class="anchor fw-bold">
                                                    <?php echo $card_tilte; ?>
                                                </h3>
                                            </div>

                                            <div class="card-body pt-9 pb-9">

                                                <div class="d-flex flex-stack mb-5">
                                                    <div class="d-flex align-items-center position-relative my-1">
                                                        <i class="ki-duotone ki-magnifier fs-1 position-absolute ms-6"><span
                                                                class="path1"></span><span class="path2"></span></i>
                                                        <input type="text" data-kt-docs-table-filter="search"
                                                            class="form-control form-control-solid w-250px ps-15"
                                                            placeholder="Search Videos" />
                                                    </div>

                                                    <a href="<?php echo base_url('admin/earning-videos/add'); ?>"
                                                        class="btn btn-light-primary">
                                                        <i class="ki-duotone ki-chart-pie-3 fs-2"><span
                                                                class="path1"></span><span class="path2"></span><span
                                                                class="path3"></span></i>
                                                        Add Video
                                                    </a>

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

                                                <table id="kt-earning-videos-table"
                                                    class="table align-middle table-row-dashed fs-6 gy-5">
                                                    <thead>
                                                        <tr
                                                            class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                                            <th>S/No</th>
                                                            <th>Video Title + URL</th>
                                                            <th>Reward</th>
                                                            <th>Duration</th>
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

    <script>const base_url = '<?php echo base_url(); ?>';</script>
    <script
        src="<?php echo base_url(); ?>/assets/admin/js/custom/authentication/sign-in/earning-videos-list.js?ver=1.0"></script>

</body>

</html>