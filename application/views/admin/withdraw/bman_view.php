<?php $this->load->view('admin/Layout/common_style'); ?>

<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet"
    type="text/css" />

<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
    data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
    data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page  flex-column flex-column-fluid " id="kt_app_page">

            <!--  Header   -->
            <?php $this->load->view('admin/Layout/admin_topbar'); ?>

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
                                                Admin
                                            </a>
                                        </li>
                                        <li class="breadcrumb-item">
                                            <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                        </li>
                                        <li class="breadcrumb-item text-muted">
                                            <a href="<?= base_url('admin/bman-withdrawals'); ?>" class="text-muted text-hover-primary">
                                                BMAN Withdrawals
                                            </a>
                                        </li>
                                        <li class="breadcrumb-item">
                                            <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                        </li>
                                        <li class="breadcrumb-item text-muted">
                                            <?php echo $card_tilte ?? $title; ?>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!--end::Toolbar-->

                        <!--begin::Content-->
                        <div id="kt_app_content" class="app-content  flex-column-fluid mt-10">
                            <!--begin::Content container-->
                            <div id="kt_app_content_container" class="app-container  container-xxl ">

                                <?php $this->load->view('notification'); ?>

                                <?php if (!$this->session->flashdata('success') === null): ?>
                                    <div class="alert alert-success"><?= htmlspecialchars($this->session->flashdata('success')); ?></div>
                                <?php endif; ?>
                                <?php if ($this->session->flashdata('error')): ?>
                                    <div class="alert alert-danger"><?= htmlspecialchars($this->session->flashdata('error')); ?></div>
                                <?php endif; ?>

                                <?php if (empty($row)): ?>
                                    <div class="alert alert-danger">Request not found</div>
                                <?php else: ?>

                                <!-- Request Details -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Request Details</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Request No:</strong> <?= htmlspecialchars($row['request_no']); ?></p>
                                                <p><strong>User:</strong> <?= htmlspecialchars(($row['username'] ?? '-') . ' / ' . ($row['referral_id'] ?? '-')); ?></p>
                                                <p><strong>Email:</strong> <?= htmlspecialchars($row['email'] ?? '-'); ?></p>
                                                <p><strong>Source Wallet:</strong> <span class="badge bg-info"><?= htmlspecialchars($row['source_wallet']); ?></span></p>
                                                <p><strong>Status:</strong> <span class="badge bg-<?= $row['status'] === 'completed' ? 'success' : ($row['status'] === 'rejected' ? 'danger' : 'warning'); ?>"><?= htmlspecialchars($row['status']); ?></span></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Amount:</strong> <?= number_format((float)$row['request_amount'], 4); ?> BMAN</p>
                                                <p><strong>Fee:</strong> <?= number_format((float)$row['fee_amount'], 4); ?> BMAN</p>
                                                <p><strong>Net Amount:</strong> <?= number_format((float)$row['net_amount'], 4); ?> BMAN</p>
                                                <p><strong>USDT Amount:</strong> <?= number_format((float)$row['usdt_amount'], 2); ?> USDT</p>
                                                <p><strong>USDT Rate:</strong> <?= number_format((float)($row['bman_usdt_rate'] ?? 0), 8); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Withdrawal Details -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Withdrawal Address & Timestamps</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Withdraw Address:</strong> <code><?= htmlspecialchars($row['withdraw_address']); ?></code></p>
                                        <p><strong>Tx Hash:</strong> <?= empty($row['tx_hash']) ? '<em>Not yet confirmed</em>' : '<code>' . htmlspecialchars($row['tx_hash']) . '</code>'; ?></p>
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Created:</strong> <?= htmlspecialchars($row['created_at'] ?? '-'); ?></p>
                                                <?php if (!empty($row['approved_at'])): ?>
                                                    <p><strong>Approved At:</strong> <?= htmlspecialchars($row['approved_at']); ?></p>
                                                    <small class="text-muted">by Admin #<?= $row['approved_by']; ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6">
                                                <?php if (!empty($row['completed_at'])): ?>
                                                    <p><strong>Completed At:</strong> <?= htmlspecialchars($row['completed_at']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($row['remark'])): ?>
                                            <p><strong>User Remark:</strong> <em><?= htmlspecialchars($row['remark']); ?></em></p>
                                        <?php endif; ?>
                                        <?php if (!empty($row['admin_remark'])): ?>
                                            <p><strong>Admin Remark:</strong> <em><?= htmlspecialchars($row['admin_remark']); ?></em></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Wallet Allocations (if mixed) -->
                                <?php if ($row['source_wallet'] === 'mixed'): ?>
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Wallet Allocations</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr><th>Wallet</th><th>Amount</th></tr>
                                            </thead>
                                            <tbody>
                                            <?php if (!empty($allocations)): ?>
                                                <?php foreach ($allocations as $alloc): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($alloc['wallet']); ?></td>
                                                    <td><?= number_format((float)$alloc['amount'], 4); ?> BMAN</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="2" class="text-muted">No allocation data found</td></tr>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Status Transition Form -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Update Status</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" action="<?= base_url('admin/bman-withdrawals/update/' . $row['id']); ?>">
                                            <div class="mb-3">
                                                <label class="form-label">New Status</label>
                                                <select name="status" class="form-select" required>
                                                    <option value="">-- Select Status --</option>
                                                    <?php if ($row['status'] === 'pending'): ?>
                                                        <option value="approved">Approve Request</option>
                                                        <option value="rejected">Reject Request</option>
                                                    <?php elseif ($row['status'] === 'approved'): ?>
                                                        <option value="processing">Mark as Processing</option>
                                                        <option value="rejected">Reject Request</option>
                                                    <?php elseif ($row['status'] === 'processing'): ?>
                                                        <option value="completed">Complete (with tx_hash)</option>
                                                        <option value="failed">Mark as Failed</option>
                                                    <?php else: ?>
                                                        <option value="">-- Terminal State (No changes) --</option>
                                                    <?php endif; ?>
                                                </select>
                                                <small class="text-muted">
                                                    Current: <strong><?= htmlspecialchars($row['status']); ?></strong>
                                                </small>
                                            </div>

                                            <?php if ($row['status'] === 'processing' || in_array($row['status'], ['pending', 'approved', 'processing'])): ?>
                                            <div class="mb-3">
                                                <label class="form-label">Transaction Hash (for completion)</label>
                                                <input type="text" name="tx_hash" class="form-control" placeholder="0xabcd..." value="<?= htmlspecialchars($row['tx_hash'] ?? ''); ?>">
                                                <small class="text-muted">Required when marking as completed</small>
                                            </div>
                                            <?php endif; ?>

                                            <div class="mb-3">
                                                <label class="form-label">Admin Remark</label>
                                                <textarea name="admin_remark" class="form-control" rows="3" placeholder="Reason for status change..."></textarea>
                                            </div>

                                            <button class="btn btn-success" type="submit">Update Status</button>
                                            <a href="<?= base_url('admin/bman-withdrawals'); ?>" class="btn btn-secondary">Back to List</a>
                                        </form>
                                    </div>
                                </div>

                                <?php endif; ?>

                            </div>
                            <!--end::Content container-->
                        </div>
                        <!--end::Content-->
                    </div>

                    <!--begin::Footer-->
                    <?php $this->load->view('admin/Layout/admin_footer'); ?>
                </div>
                <!--end::Main-->
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
    <script src="<?php echo base_url(); ?>/assets/admin/js/widgets.bundle.js"></script>
    <script src="<?php echo base_url(); ?>/assets/admin/js/custom/widgets.js"></script>
    <script src="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.js"></script>
</body>

</html>
