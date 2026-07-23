<?php $this->load->view('admin/Layout/common_style'); ?>

<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet"
    type="text/css" />

<style>
    .review-profile-card {
        border: 1px solid #edf0f7;
        border-radius: 18px;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        padding: 22px;
        height: 100%;
    }

    .review-avatar {
        width: 96px;
        height: 96px;
        border-radius: 28px;
        object-fit: cover;
        border: 4px solid #fff;
        box-shadow: 0 16px 35px rgba(38, 43, 85, .14);
        background: #f1f3f9;
    }

    .review-pill-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
    }

    .review-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .review-info-box {
        border: 1px solid #edf0f7;
        border-radius: 14px;
        padding: 12px 14px;
        background: #fff;
        min-height: 74px;
    }

    .review-info-box small {
        display: block;
        color: #8a90a6;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .2px;
        margin-bottom: 5px;
    }

    .review-info-box strong,
    .review-info-box code {
        color: #111827;
        font-size: 13px;
        font-weight: 800;
        word-break: break-word;
    }

    .kyc-doc-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .kyc-doc-card {
        border: 1px dashed #dfe3f4;
        border-radius: 16px;
        padding: 10px;
        background: #fbfbff;
    }

    .kyc-doc-card img,
    .kyc-doc-placeholder {
        width: 100%;
        height: 130px;
        border-radius: 12px;
        object-fit: cover;
        background: #eef1f7;
        display: grid;
        place-items: center;
        color: #6b7280;
        font-weight: 800;
    }

    .kyc-history-list {
        display: grid;
        gap: 10px;
    }

    .kyc-history-item {
        border-left: 3px solid #5e55ea;
        background: #f8f7ff;
        border-radius: 12px;
        padding: 10px 12px;
    }

    @media (max-width: 991px) {
        .review-info-grid,
        .kyc-doc-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

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

                                <?php
                                $esc = function ($value, $fallback = '-') {
                                    $value = is_string($value) ? trim($value) : $value;
                                    if ($value === null || $value === '') {
                                        $value = $fallback;
                                    }
                                    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
                                };
                                $joinParts = function (array $parts) {
                                    $parts = array_filter(array_map(function ($part) {
                                        return trim((string) $part);
                                    }, $parts));
                                    return $parts ? implode(', ', $parts) : '-';
                                };
                                $statusLabel = function ($status) {
                                    $status = trim((string) $status);
                                    if ($status === '' || $status === '0' || strtolower($status) === 'none') {
                                        return 'Not Submitted';
                                    }
                                    if ($status === '1') {
                                        return 'Approved';
                                    }
                                    return ucwords(str_replace('_', ' ', strtolower($status)));
                                };
                                $badgeClass = function ($status) {
                                    $status = strtolower(trim((string) $status));
                                    if (in_array($status, ['1', 'active', 'approved', 'completed', 'success'], true)) {
                                        return 'badge-light-success';
                                    }
                                    if (in_array($status, ['pending', 'processing', 'under_review', 'approved_pending'], true)) {
                                        return 'badge-light-warning';
                                    }
                                    if (in_array($status, ['0', 'inactive', 'blocked', 'rejected', 'failed', 'not_submitted', 'none'], true)) {
                                        return 'badge-light-danger';
                                    }
                                    return 'badge-light-primary';
                                };
                                $docTypeLabel = function ($type) {
                                    $map = [
                                        'national_id' => 'Aadhaar Id',
                                        'driver_license' => 'Driving License',
                                        'passport' => 'Passport',
                                    ];
                                    $type = trim((string) $type);
                                    return $map[$type] ?? ucwords(str_replace('_', ' ', $type ?: 'Document'));
                                };

                                $userProfile = $user_profile ?? [];
                                $kycApp = $kyc_application ?? [];
                                $legacyKyc = $legacy_kyc ?? [];
                                $docs = $kyc_documents ?? [];
                                $history = $kyc_history ?? [];
                                $rowSafe = $row ?? [];

                                $profileName = $userProfile['display_name'] ?? ($rowSafe['username'] ?? 'Member');
                                $profilePhoto = $userProfile['profile_photo'] ?? base_url('assets/images/default-avatar.svg');
                                $userStatusRaw = $userProfile['status'] ?? ($rowSafe['user_status'] ?? '');
                                $accountStatus = ((string) $userStatusRaw === '1' || strtolower((string) $userStatusRaw) === 'active') ? 'Active' : $statusLabel($userStatusRaw);
                                $kycStatusRaw = $kycApp['status'] ?? ($legacyKyc['status'] ?? ($userProfile['kyc_status'] ?? ''));
                                $kycStatusText = $statusLabel($kycStatusRaw);
                                $kycName = $kycApp['full_name'] ?? ($legacyKyc['full_name_pan'] ?? $profileName);
                                $kycAddress = !empty($kycApp)
                                    ? $joinParts([$kycApp['addr_line1'] ?? '', $kycApp['addr_line2'] ?? '', $kycApp['addr_city'] ?? '', $kycApp['addr_region'] ?? '', $kycApp['addr_postal'] ?? ''])
                                    : $joinParts([$legacyKyc['address'] ?? '', $legacyKyc['city'] ?? '', $legacyKyc['state'] ?? '', $legacyKyc['pincode'] ?? '']);
                                $kycDocNumber = $kycApp['doc_number'] ?? '';
                                if ($kycDocNumber === '' && !empty($legacyKyc)) {
                                    $legacyNumbers = [];
                                    if (!empty($legacyKyc['pan_number'])) {
                                        $legacyNumbers[] = 'PAN: ' . $legacyKyc['pan_number'];
                                    }
                                    if (!empty($legacyKyc['aadhaar_last4'])) {
                                        $legacyNumbers[] = 'Aadhaar Last 4: ' . $legacyKyc['aadhaar_last4'];
                                    }
                                    $kycDocNumber = $legacyNumbers ? implode(' / ', $legacyNumbers) : '';
                                }
                                ?>

                                <?php if (empty($row)): ?>
                                    <div class="alert alert-danger">Request not found</div>
                                <?php else: ?>

                                <!-- Member Profile & KYC Verification -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light d-flex align-items-center justify-content-between">
                                        <div>
                                            <h5 class="mb-1">Member Profile & KYC Verification</h5>
                                            <small class="text-muted">Review the member identity, profile photo, and KYC files before approving this withdrawal.</small>
                                        </div>
                                        <span class="badge <?= $badgeClass($kycStatusRaw); ?>">KYC <?= $esc($kycStatusText); ?></span>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-4">
                                            <div class="col-xl-4">
                                                <div class="review-profile-card text-center">
                                                    <img class="review-avatar mb-4" src="<?= $esc($profilePhoto); ?>" alt="Member profile photo">
                                                    <h4 class="mb-1"><?= $esc($profileName); ?></h4>
                                                    <div class="text-muted fw-semibold mb-2">
                                                        <?= $esc($userProfile['username'] ?? ($row['username'] ?? '-')); ?> /
                                                        <?= $esc($userProfile['referral_id'] ?? ($row['referral_id'] ?? '-')); ?>
                                                    </div>
                                                    <div class="review-pill-row justify-content-center">
                                                        <span class="badge <?= $badgeClass($userStatusRaw); ?>">Account <?= $esc($accountStatus); ?></span>
                                                        <span class="badge <?= $badgeClass($kycStatusRaw); ?>">KYC <?= $esc($kycStatusText); ?></span>
                                                        <?php if (!empty($userProfile['kyc_verified_at'])): ?>
                                                            <span class="badge badge-light-success">Verified <?= $esc($userProfile['kyc_verified_at']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-8">
                                                <div class="review-info-grid">
                                                    <div class="review-info-box">
                                                        <small>User ID</small>
                                                        <strong>#<?= $esc($userProfile['id'] ?? ($row['user_id'] ?? '-')); ?></strong>
                                                    </div>
                                                    <div class="review-info-box">
                                                        <small>Email</small>
                                                        <strong><?= $esc($userProfile['email'] ?? ($row['email'] ?? '-')); ?></strong>
                                                    </div>
                                                    <div class="review-info-box">
                                                        <small>Phone</small>
                                                        <strong><?= $esc($userProfile['contact'] ?? '-'); ?></strong>
                                                    </div>
                                                    <div class="review-info-box">
                                                        <small>Registered</small>
                                                        <strong><?= $esc($userProfile['register_date'] ?? '-'); ?></strong>
                                                    </div>
                                                    <div class="review-info-box">
                                                        <small>Country / Gender</small>
                                                        <strong><?= $esc($joinParts([$userProfile['country'] ?? '', ucfirst((string) ($userProfile['gender'] ?? ''))])); ?></strong>
                                                    </div>
                                                    <div class="review-info-box">
                                                        <small>Date of Birth</small>
                                                        <strong><?= $esc($userProfile['dob'] ?? '-'); ?></strong>
                                                    </div>
                                                    <div class="review-info-box">
                                                        <small>Profile Address</small>
                                                        <strong><?= $esc($joinParts([$userProfile['address'] ?? '', $userProfile['zipcode'] ?? ''])); ?></strong>
                                                    </div>
                                                    <div class="review-info-box">
                                                        <small>Sponsor</small>
                                                        <strong><?= $esc($userProfile['sponser'] ?? '-'); ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-6">

                                        <div class="row g-4">
                                            <div class="col-lg-7">
                                                <h6 class="fw-bold mb-3">KYC Identity Details</h6>
                                                <?php if (!empty($kycApp) || !empty($legacyKyc)): ?>
                                                    <div class="review-info-grid">
                                                        <div class="review-info-box">
                                                            <small>Name on KYC</small>
                                                            <strong><?= $esc($kycName); ?></strong>
                                                        </div>
                                                        <div class="review-info-box">
                                                            <small>KYC Status</small>
                                                            <strong><?= $esc($kycStatusText); ?></strong>
                                                        </div>
                                                        <div class="review-info-box">
                                                            <small>Document Type</small>
                                                            <strong><?= $esc(!empty($kycApp['doc_type']) ? $docTypeLabel($kycApp['doc_type']) : 'PAN / Aadhaar'); ?></strong>
                                                        </div>
                                                        <div class="review-info-box">
                                                            <small>Document Number</small>
                                                            <code><?= $esc($kycDocNumber); ?></code>
                                                        </div>
                                                        <div class="review-info-box">
                                                            <small>DOB / Gender</small>
                                                            <strong><?= $esc($joinParts([$kycApp['dob'] ?? ($legacyKyc['dob'] ?? ''), ucfirst((string) ($kycApp['gender'] ?? ''))])); ?></strong>
                                                        </div>
                                                        <div class="review-info-box">
                                                            <small>Country / Nationality</small>
                                                            <strong><?= $esc($joinParts([$kycApp['country_iso2'] ?? '', $kycApp['nationality_iso2'] ?? ''])); ?></strong>
                                                        </div>
                                                        <div class="review-info-box">
                                                            <small>Issue / Expiry</small>
                                                            <strong><?= $esc($joinParts([$kycApp['doc_issue_date'] ?? '', $kycApp['doc_expiry_date'] ?? ''])); ?></strong>
                                                        </div>
                                                        <div class="review-info-box">
                                                            <small>Submitted / Updated</small>
                                                            <strong><?= $esc($joinParts([$kycApp['created_at'] ?? ($legacyKyc['submitted_at'] ?? ''), $kycApp['updated_at'] ?? ($legacyKyc['updated_at'] ?? '')])); ?></strong>
                                                        </div>
                                                        <div class="review-info-box">
                                                            <small>Reviewed By / At</small>
                                                            <strong><?= $esc($joinParts([$kycApp['reviewed_by'] ?? ($legacyKyc['reviewer_id'] ?? ''), $kycApp['reviewed_at'] ?? ($legacyKyc['reviewed_at'] ?? '')])); ?></strong>
                                                        </div>
                                                        <div class="review-info-box">
                                                            <small>PEP / Consent</small>
                                                            <strong><?= $esc($joinParts([
                                                                isset($kycApp['is_pep']) ? ('PEP: ' . ((int) $kycApp['is_pep'] ? 'Yes' : 'No')) : '',
                                                                isset($kycApp['consent']) ? ('Consent: ' . ((int) $kycApp['consent'] ? 'Yes' : 'No')) : ''
                                                            ])); ?></strong>
                                                        </div>
                                                        <div class="review-info-box" style="grid-column:1/-1;">
                                                            <small>KYC Address</small>
                                                            <strong><?= $esc($kycAddress); ?></strong>
                                                        </div>
                                                        <?php if (!empty($kycApp['review_notes']) || !empty($kycApp['rejection_code']) || !empty($legacyKyc['reviewer_note'])): ?>
                                                            <div class="review-info-box" style="grid-column:1/-1;">
                                                                <small>Review Notes</small>
                                                                <strong><?= $esc($joinParts([$kycApp['review_notes'] ?? ($legacyKyc['reviewer_note'] ?? ''), $kycApp['rejection_code'] ?? ''])); ?></strong>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="alert alert-warning mb-0">No KYC application or legacy KYC record found for this member.</div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="col-lg-5">
                                                <h6 class="fw-bold mb-3">KYC Documents & Selfie</h6>
                                                <?php if (!empty($docs)): ?>
                                                    <div class="kyc-doc-grid">
                                                        <?php foreach ($docs as $doc): ?>
                                                            <div class="kyc-doc-card">
                                                                <?php if (!empty($doc['is_image'])): ?>
                                                                    <a href="<?= $esc($doc['url']); ?>" target="_blank" rel="noopener">
                                                                        <img src="<?= $esc($doc['url']); ?>" alt="<?= $esc($doc['label']); ?>">
                                                                    </a>
                                                                <?php else: ?>
                                                                    <a class="kyc-doc-placeholder text-decoration-none" href="<?= $esc($doc['url']); ?>" target="_blank" rel="noopener">Open File</a>
                                                                <?php endif; ?>
                                                                <div class="d-flex justify-content-between align-items-center mt-3">
                                                                    <strong class="fs-7"><?= $esc($doc['label']); ?></strong>
                                                                    <a class="btn btn-sm btn-light-primary" href="<?= $esc($doc['url']); ?>" target="_blank" rel="noopener">View</a>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="alert alert-warning mb-0">No KYC document images uploaded yet.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <?php if (!empty($history)): ?>
                                            <hr class="my-6">
                                            <h6 class="fw-bold mb-3">KYC Review History</h6>
                                            <div class="kyc-history-list">
                                                <?php foreach ($history as $item): ?>
                                                    <div class="kyc-history-item">
                                                        <div class="d-flex justify-content-between flex-wrap gap-2">
                                                            <strong><?= $esc($item['action'] ?? '-'); ?></strong>
                                                            <span class="text-muted"><?= $esc($item['created_at'] ?? '-'); ?></span>
                                                        </div>
                                                        <div class="text-muted fs-7">Actor: <?= $esc($item['actor_user_id'] ?? '-'); ?></div>
                                                        <?php if (!empty($item['notes'])): ?>
                                                            <div class="mt-1"><?= $esc($item['notes']); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

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
                                                <p><strong>Fee:</strong> <?= number_format((float)$row['fee_amount'], 4); ?> USDT</p>
                                                <p><strong>Net Amount:</strong> <?= number_format((float)$row['net_amount'], 4); ?> USDT</p>
                                                <p><strong>USDT Amount:</strong> <?= number_format((float)$row['usdt_amount'], 2); ?> USDT</p>
                                                <p><strong>USDT Rate:</strong> <?= number_format((float)($row['bman_usdt_rate'] ?? 0), 8); ?> USDT/BMAN</p>
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
