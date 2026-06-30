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
                                        <?= $title; ?>
                                    </h1>
                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                        <li class="breadcrumb-item text-muted">
                                            <a href="<?= base_url(); ?>"
                                                class="text-muted text-hover-primary">Settings</a>
                                        </li>
                                        <li class="breadcrumb-item">
                                            <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                        </li>
                                        <li class="breadcrumb-item text-muted"><?= $title; ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div id="kt_app_content" class="app-content flex-column-fluid mt-10">
                            <div id="kt_app_content_container" class="app-container container-xxl">
                                <div class="card mb-5 mb-xl-10">
                                    <div class="card-header border-0 cursor-pointer p-3">
                                        <div class="card-title m-0">
                                            <div
                                                class="me-3 d-flex justify-content-between text-center align-items-center gap-4">
                                                <div
                                                    class="d-flex flex-center w-60px h-60px rounded-3 bg-light-warning bg-opacity-90">
                                                    <i class="ki-duotone ki-eye text-warning fs-3x"></i>
                                                </div>
                                                <h3 class="fw-bold m-0">Withdraw Request Review</h3>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="kt_withdraw_admin_details" class="collapse show">
                                        <div class="card-body border-top p-9">

                                            <?php $action = base_url("update-withdraw/" . $withdraw['id']); ?>
                                            <?= form_open($action, [
                                                'class' => 'form-validate',
                                                'method' => 'post',
                                                'autocomplete' => 'off',
                                                'id' => 'kt_withdraw_admin_form',
                                                'enctype' => 'multipart/form-data'
                                            ]) ?>

                                            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>"
                                                value="<?= $this->security->get_csrf_hash(); ?>" />

                                            <!-- Payment Details -->
                                            <?php if (!empty($withdraw['payment_details'])):
                                                $payment = $withdraw['payment_details']; ?>
                                                <div class="row mb-6">
                                                    <label class="col-lg-4 col-form-label fw-semibold fs-6">Bank
                                                        Details</label>
                                                    <div class="col-lg-8 fv-row">
                                                        <div class="border p-3 rounded bg-light">
                                                            <p><strong>Account Holder:</strong>
                                                                <?= htmlspecialchars($payment['account_holder']); ?></p>
                                                            <p><strong>Account Number:</strong>
                                                                <?= htmlspecialchars($payment['account_number']); ?></p>
                                                            <p><strong>Branch:</strong>
                                                                <?= htmlspecialchars($payment['branch']); ?></p>
                                                            <p><strong>IFSC Code:</strong>
                                                                <?= htmlspecialchars($payment['ifsc_code']); ?></p>
                                                            <p><strong>UPI ID:</strong>
                                                                <?= htmlspecialchars($payment['upi_id']); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- User Info -->
                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">User</label>
                                                <div class="col-lg-8 fv-row">
                                                    <input type="text"
                                                        class="form-control form-control-lg form-control-solid"
                                                        value="<?= htmlspecialchars($withdraw['user']['username']); ?>"
                                                        disabled>
                                                </div>
                                            </div>

                                            <!-- Email -->
                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Email</label>
                                                <div class="col-lg-8 fv-row">
                                                    <input type="text"
                                                        class="form-control form-control-lg form-control-solid"
                                                        value="<?= htmlspecialchars($withdraw['user']['email']); ?>"
                                                        disabled>
                                                </div>
                                            </div>

                                            <!-- Amount -->
                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Amount</label>
                                                <div class="col-lg-8 fv-row">
                                                    <input type="text"
                                                        class="form-control form-control-lg form-control-solid"
                                                        value="<?= number_format($withdraw['amount'], 2); ?>" disabled>
                                                </div>
                                            </div>

                                            <!-- Fee -->
                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Fee</label>
                                                <div class="col-lg-8 fv-row">
                                                    <input type="text"
                                                        class="form-control form-control-lg form-control-solid"
                                                        value="<?= number_format($withdraw['fee'], 2); ?>" disabled>
                                                </div>
                                            </div>

                                            <!-- Net Amount -->
                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Net
                                                    Amount</label>
                                                <div class="col-lg-8 fv-row">
                                                    <input type="text"
                                                        class="form-control form-control-lg form-control-solid"
                                                        value="<?= number_format($withdraw['net_amount'], 2); ?>"
                                                        disabled>
                                                </div>
                                            </div>

                                            <!-- Status -->
                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Status
                                                    <span>*</span></label>
                                                <div class="col-lg-8 fv-row">
                                                    <select class="form-select form-select-solid" name="status"
                                                        required>
                                                        <option value="pending"
                                                            <?= strtolower($withdraw['status']) == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="approved"
                                                            <?= strtolower($withdraw['status']) == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                        <option value="rejected"
                                                            <?= strtolower($withdraw['status']) == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Review -->
                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Review</label>
                                                <div class="col-lg-8 fv-row">
                                                    <textarea name="review"
                                                        class="form-control form-control-lg form-control-solid"
                                                        placeholder="Enter your review"><?= isset($withdraw['review']) ? $withdraw['review'] : '' ?></textarea>
                                                </div>
                                            </div>

                                            <!-- Upload Proof -->
                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Upload
                                                    Proof</label>
                                                <div class="col-lg-8 fv-row">
                                                    <input type="file" name="withdraw_proof"
                                                        class="form-control form-control-lg form-control-solid"
                                                        accept="image/*">
                                                    <?php if (!empty($withdraw['proof'])): ?>
                                                        <small class="text-muted">Current Proof: <a
                                                                href="<?= base_url('uploads/withdraw_proof/' . $withdraw['proof']) ?>"
                                                                target="_blank">View Image</a></small>
                                                    <?php endif; ?>
                                                    <small class="text-muted d-block">Accepted formats: jpg, png, jpeg
                                                        (optional)</small>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <button type="submit" id="kt_withdraw_submit"
                                                        class="btn btn-lg btn-success">Update Withdraw</button>
                                                </div>
                                            </div>

                                            <?= form_close(); ?>

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

    <script>
        "use strict";

        $(document).ready(function () {
            // ---------------------------
            // ✅ DEMO MODE HELPERS (optional)
            // ---------------------------
            function isDemoMode() {
                return !!(
                    (window.APP_CONFIG && window.APP_CONFIG.DEMO === true) ||
                    window.DEMOVERSION === true
                );
            }

            function demoBlockAlert(msg) {
                Swal.fire({
                    icon: "info",
                    title: "Demo Version",
                    text: msg || "You Can not change record.",
                    confirmButtonText: "Ok, got it!",
                    customClass: { confirmButton: "btn btn-primary" },
                    buttonsStyling: false,
                });
            }

            $("#kt_withdraw_admin_form").on("submit", function (e) {
                e.preventDefault();

                // ✅ DEMO MODE STOP
                if (isDemoMode()) {
                    demoBlockAlert("You Can not update withdraw in demo mode.");
                    return;
                }

                var $btn = $("#kt_withdraw_submit");
                var originalText = $btn.text();

                var formData = new FormData(this);

                $.ajax({
                    url: '<?= base_url("update-withdraw/" . $withdraw["id"]) ?>',
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: "json",
                    beforeSend: function () {
                        $btn.prop("disabled", true).text("Processing...");
                        $btn.attr("data-kt-indicator", "on"); // optional (if KT indicator exists)
                    },
                    success: function (response) {
                        if (response && response.status === "success") {
                            Swal.fire({
                                text: response.message || "Withdraw updated successfully!",
                                icon: "success",
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
                                customClass: { confirmButton: "btn btn-primary" },
                            }).then(() => {
                                window.location.href = "<?= base_url('withdraw-requests') ?>";
                            });
                        } else {
                            Swal.fire({
                                text: (response && response.message) || "Something went wrong!",
                                icon: "error",
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
                                customClass: { confirmButton: "btn btn-primary" },
                            });
                        }
                    },
                    error: function (xhr) {
                        Swal.fire({
                            text: "Error: " + (xhr.responseJSON?.message || xhr.responseText || "Request failed"),
                            icon: "error",
                            buttonsStyling: false,
                            confirmButtonText: "Ok, got it!",
                            customClass: { confirmButton: "btn btn-primary" },
                        });
                    },
                    complete: function () {
                        $btn.prop("disabled", false).text(originalText);
                        $btn.removeAttr("data-kt-indicator");
                    },
                });
            });
        });

    </script>

</body>

</html>