<?php $this->load->view('admin/Layout/common_style'); ?>

<link href="<?php echo base_url(); ?>/assets/admin/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet"
    type="text/css">
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet"
    type="text/css" />

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
                                                        <i class="ki-duotone ki-magnifier fs-1 position-absolute ms-6"><span
                                                                class="path1"></span><span class="path2"></span></i>
                                                        <input type="text" data-kt-docs-table-filter="search"
                                                            class="form-control form-control-solid w-250px ps-15"
                                                            placeholder="Search requests" />
                                                    </div>

                                                </div>

                                                <table id="kt-contact-request-table"
                                                    class="table align-middle table-row-dashed fs-6 gy-5">
                                                    <thead>
                                                        <tr
                                                            class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                                            <th>ID</th>
                                                            <th>User</th>
                                                            <th>Email</th>
                                                            <th>Message</th>
                                                            <th>Attachment</th>
                                                            <th>Status</th>
                                                            <th>Submitted</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="text-gray-600 fw-semibold"></tbody>
                                                </table>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal -->
                                <div class="modal fade" id="kt_modal_contact_review" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered mw-800px">
                                        <div class="modal-content">

                                            <div class="modal-header pb-0 border-0 justify-content-end">
                                                <div class="btn btn-sm btn-icon" data-bs-dismiss="modal">
                                                    <i class="ki-outline ki-cross fs-1"></i>
                                                </div>
                                            </div>

                                            <div class="modal-body scroll-y mx-5 mx-xl-18 pt-0 pb-15">
                                                <div class="text-center mb-8">
                                                    <h1 class="d-flex justify-content-center align-items-center mb-3">
                                                        Contact Request</h1>
                                                    <div class="text-muted">Unlock the account, or mark this request
                                                        resolved / rejected.
                                                    </div>
                                                </div>

                                                <div class="mh-475px scroll-y me-n7 pe-7">
                                                    <div id="contact-review-content"></div>

                                                    <div class="separator my-6"></div>

                                                    <div class="row g-4">
                                                        <div class="col-md-12">
                                                            <label class="form-label fw-bold">Admin Note</label>
                                                            <input type="text" class="form-control"
                                                                id="contactDecisionNote" placeholder="Optional note">
                                                        </div>
                                                    </div>

                                                    <div class="d-flex justify-content-end gap-3 mt-6">
                                                        <button type="button" class="btn btn-light-danger"
                                                            id="btnRejectContact">
                                                            Close / Reject
                                                        </button>
                                                        <button type="button" class="btn btn-light-success"
                                                            id="btnResolveContact">
                                                            Mark Resolved
                                                        </button>
                                                        <button type="button" class="btn btn-primary"
                                                            id="btnUnlockContact">
                                                            Unlock Account
                                                        </button>
                                                    </div>

                                                    <input type="hidden" id="contactRowId" value="">
                                                </div>

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

    <script src="<?php echo base_url(); ?>assets/admin/plugins/custom/datatables/datatables.bundle.js"></script>

    <script>
        const base_url = '<?php echo base_url(); ?>';
    </script>

    <script
        src="<?php echo base_url(); ?>/assets/admin/js/custom/authentication/sign-in/contact-request-list.js?ver=1.0"></script>

</body>

</html>
