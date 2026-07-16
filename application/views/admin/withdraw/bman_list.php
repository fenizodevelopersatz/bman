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
                                            <?php echo $title; ?>
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

                                <!-- Filters -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <input type="text" id="filter_q" class="form-control" placeholder="Search: request no, user, email...">
                                            </div>
                                            <div class="col-md-3">
                                                <select id="filter_status" class="form-select">
                                                    <option value="">All Status</option>
                                                    <option value="pending">Pending</option>
                                                    <option value="approved">Approved</option>
                                                    <option value="processing">Processing</option>
                                                    <option value="completed">Completed</option>
                                                    <option value="rejected">Rejected</option>
                                                    <option value="failed">Failed</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <select id="filter_source" class="form-select">
                                                    <option value="">All Wallets</option>
                                                    <option value="mixed">Mixed</option>
                                                    <option value="exchange">Exchange</option>
                                                    <option value="earning">Earning</option>
                                                    <option value="staking">Staking</option>
                                                    <option value="bonus">Bonus</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <button class="btn btn-secondary w-100" onclick="applyFilters()">Filter</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Table -->
                                <div class="card">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Request No</th>
                                                        <th>User</th>
                                                        <th>Bank Account</th>
                                                        <th>Wallet</th>
                                                        <th>BMAN Amount</th>
                                                        <th>USDT</th>
                                                        <th>Status</th>
                                                        <th>Created</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php if (!empty($rows)): foreach (($rows ?? []) as $row): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?= htmlspecialchars($row['request_no']); ?></strong>
                                                        </td>
                                                        <td>
                                                            <div><?= htmlspecialchars($row['username'] ?? '-'); ?></div>
                                                            <small class="text-muted"><?= htmlspecialchars($row['email'] ?? '-'); ?></small><br>
                                                            <small class="text-muted">UID: <?= htmlspecialchars($row['referral_id'] ?? '-'); ?></small>
                                                        </td>
                                                        <td>
                                                            <div><strong><?= htmlspecialchars($row['holder_name'] ?? '-'); ?></strong></div>
                                                            <small><?= htmlspecialchars($row['bank_name'] ?? '-'); ?></small><br>
                                                            <small class="text-muted">A/C: <?= htmlspecialchars(substr($row['account_number'] ?? '', -4) ?? '-'); ?></small><br>
                                                            <?php if (!empty($row['upi_id'])): ?>
                                                                <small class="text-muted">UPI: <?= htmlspecialchars($row['upi_id']); ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-info"><?= htmlspecialchars($row['source_wallet']); ?></span>
                                                        </td>
                                                        <td>
                                                            <div><?= number_format((float)$row['request_amount'], 4); ?> BMAN</div>
                                                            <small class="text-muted">Fee: <?= number_format((float)$row['fee_amount'], 4); ?></small><br>
                                                            <small class="text-muted">Net: <?= number_format((float)$row['net_amount'], 4); ?></small>
                                                        </td>
                                                        <td>
                                                            <div><?= number_format((float)$row['usdt_amount'], 2); ?> USDT</div>
                                                            <small class="text-muted">Rate: <?= number_format((float)$row['bman_usdt_rate'], 4); ?></small>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $statusClass = '';
                                                            if ($row['status'] === 'completed') $statusClass = 'bg-success';
                                                            elseif ($row['status'] === 'approved') $statusClass = 'bg-info';
                                                            elseif ($row['status'] === 'pending') $statusClass = 'bg-warning';
                                                            elseif ($row['status'] === 'processing') $statusClass = 'bg-primary';
                                                            else $statusClass = 'bg-danger';
                                                            ?>
                                                            <span class="badge <?= $statusClass; ?>"><?= htmlspecialchars($row['status']); ?></span>
                                                        </td>
                                                        <td>
                                                            <small><?= htmlspecialchars(date('M d, H:i', strtotime($row['created_at']))); ?></small>
                                                        </td>
                                                        <td>
                                                            <a class="btn btn-sm btn-primary" href="<?= base_url('admin/bman-withdrawals/view/' . $row['id']); ?>">View</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; else: ?>
                                                    <tr><td colspan="9" class="text-center text-muted">No requests found</td></tr>
                                                <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

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

    <script>
    function applyFilters() {
        const q = document.getElementById('filter_q').value;
        const status = document.getElementById('filter_status').value;
        const source = document.getElementById('filter_source').value;

        let url = '<?= base_url('admin/bman-withdrawals'); ?>';
        const params = [];
        if (q) params.push('q=' + encodeURIComponent(q));
        if (status) params.push('status=' + encodeURIComponent(status));
        if (source) params.push('source_wallet=' + encodeURIComponent(source));

        if (params.length) url += '?' + params.join('&');
        window.location.href = url;
    }
    </script>
</body>

</html>
