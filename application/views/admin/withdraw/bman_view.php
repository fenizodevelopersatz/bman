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

    .wd-history-list {
        display: grid;
        gap: 10px;
    }

    .wd-history-item {
        border-left: 3px solid #94a3b8;
        background: #f8fafc;
        border-radius: 12px;
        padding: 10px 12px;
    }

    .wd-history-item.status-processing { border-left-color: #0d6efd; background: #eef5ff; }
    .wd-history-item.status-pending    { border-left-color: #f59e0b; background: #fff8ec; }
    .wd-history-item.status-approved,
    .wd-history-item.status-completed  { border-left-color: #16a34a; background: #eefdf3; }
    .wd-history-item.status-rejected,
    .wd-history-item.status-failed     { border-left-color: #dc2626; background: #fef2f2; }

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
                                $sponsorProfile = $sponsor_profile ?? [];
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
                                                    <hr class="my-4">
                                                    <div class="text-start">
                                                        <small class="text-muted d-block mb-1">User Wallet Address (on-chain custodial)</small>
                                                        <?php if (!empty($user_wallet_address)): ?>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <code id="user-wallet-addr" class="text-break small mb-0"><?= $esc($user_wallet_address); ?></code>
                                                                <button type="button" class="btn btn-link btn-sm py-0 px-0" data-copy="user-wallet-addr">copy</button>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted small">No custodial wallet generated yet for this member.</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!empty($is_super)): ?>
                                                        <div class="text-start mt-3">
                                                            <button type="button" class="btn btn-outline-warning btn-sm" id="btk-open">Reveal Treasury Private Key</button>
                                                            <div id="btk-result" class="mt-3 d-none">
                                                                <div class="alert alert-warning small mb-2">
                                                                    Do not screen-share or paste this anywhere but your wallet app. This panel clears in 60s.
                                                                </div>
                                                                <p class="mb-0"><strong>Private Key:</strong> <code id="btk-key" style="word-break:break-all;"></code>
                                                                    <button type="button" class="btn btn-link btn-sm py-0" data-copy="btk-key">copy</button></p>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
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
                                                        <?php if (!empty($sponsorProfile)): ?>
                                                            <div class="d-flex align-items-center gap-2 mt-1">
                                                                <img src="<?= $esc($sponsorProfile['profile_photo']); ?>" alt="Sponsor photo" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">
                                                                <div>
                                                                    <strong class="d-block"><?= $esc($sponsorProfile['display_name']); ?></strong>
                                                                    <span class="text-muted fs-7">#<?= $esc($userProfile['sponser']); ?> · <?= $esc($sponsorProfile['email'] ?: '-'); ?></span>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <strong><?= $esc($userProfile['sponser'] ?? '-'); ?></strong>
                                                        <?php endif; ?>
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
                                                <?php
                                                // 'approved' is terminal/paid under the cron flow (tx_hash set) but
                                                // still an in-flight legacy step without one — see approve_and_complete().
                                                $isPaidApproved = ($row['status'] === 'approved' && !empty($row['tx_hash']));
                                                $statusColor = (in_array($row['status'], ['completed'], true) || $isPaidApproved) ? 'success'
                                                    : (in_array($row['status'], ['rejected', 'failed'], true) ? 'danger' : 'warning');
                                                ?>
                                                <p><strong>Status:</strong> <span class="badge bg-<?= $statusColor; ?>"><?= htmlspecialchars($row['status']); ?></span></p>
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
                                        <p><strong>Withdraw Address:</strong> <code id="wd-addr"><?= htmlspecialchars($row['withdraw_address']); ?></code>
                                            <button type="button" class="btn btn-link btn-sm py-0" data-copy="wd-addr">copy</button></p>
                                        <?php if (!empty($row['collected_at'])): ?>
                                            <p class="text-muted small mb-0">Collected at <?= htmlspecialchars($row['collected_at']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($row['gas_cron_status_message']) || !empty($row['collect_cron_status_message'])): ?>
                                            <p class="text-muted small mb-0"><?= htmlspecialchars($row['gas_cron_status_message'] ?: $row['collect_cron_status_message']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($row['refunded_at'])): ?>
                                            <p class="text-muted small mb-0">BMAN refunded at <?= htmlspecialchars($row['refunded_at']); ?></p>
                                        <?php endif; ?>
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

                                <?php
                                // Only real broadcast hashes are clickable — a DRYRUN-* hash
                                // (dry-run testing mode, never actually sent on-chain) would
                                // just 404 on the explorer.
                                $explorerUrl = $explorer_url ?? 'https://bscscan.com';
                                $isRealHash = function ($hash) {
                                    return (bool) preg_match('/^0x[0-9a-fA-F]{6,}$/', (string) $hash);
                                };

                                // One combined leg-row renderer for BMAN/refund legs, so the
                                // BMAN and Rejected sections below render identically instead
                                // of duplicating this markup per leg.
                                $renderLeg = function ($label, $txHash, $confirmed, $fee) use ($explorerUrl, $isRealHash) {
                                    if (empty($txHash)) return;
                                    // Unique per CALL, not per (hash,label) — the same leg (e.g.
                                    // 'BMAN Collection') renders twice on this page (once in
                                    // Transaction Details, once in its History entry) with the
                                    // identical hash, so a value-derived id would collide into a
                                    // duplicate DOM id and the copy button would silently target
                                    // the wrong instance.
                                    static $callCount = 0;
                                    $hashId = 'leg-hash-' . (++$callCount);
                                    ?>
                                    <div class="d-flex justify-content-between align-items-start border-bottom py-2">
                                        <div>
                                            <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($label); ?></span>
                                            <?= $confirmed ? '<span class="badge bg-success ms-1">confirmed</span>' : '<span class="badge bg-warning ms-1">pending</span>'; ?>
                                            <div class="tiny text-muted mt-1">
                                                Tx Hash:
                                                <?php if ($isRealHash($txHash)): ?>
                                                    <a href="<?= htmlspecialchars($explorerUrl . '/tx/' . $txHash); ?>" target="_blank" rel="noopener" id="<?= $hashId; ?>" class="text-break"><?= htmlspecialchars($txHash); ?></a>
                                                <?php else: ?>
                                                    <code id="<?= $hashId; ?>" class="text-break"><?= htmlspecialchars($txHash); ?></code>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-link btn-sm py-0 px-1" data-copy="<?= $hashId; ?>">copy</button>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <?php if ($fee && $fee['bnb_fee'] !== null): ?>
                                                <strong><?= number_format($fee['bnb_fee'], 8); ?> BNB</strong>
                                                <?php if ($fee['is_estimate']): ?>
                                                    <div class="tiny text-warning">estimated</div>
                                                <?php endif; ?>
                                                <div class="tiny text-muted">gas fee for tx above</div>
                                            <?php else: ?>
                                                <span class="text-muted small">gas fee —</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php
                                };
                                ?>

                                <!-- Transaction Details -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div>
                                            <h5 class="mb-0">Transaction Details</h5>
                                            <small class="text-muted">Tx hash + gas fee for every leg, grouped BMAN / USDT / Rejected refund.</small>
                                        </div>
                                        <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#txDetailsPanel">View Transaction Details</button>
                                    </div>
                                    <div class="collapse" id="txDetailsPanel">
                                        <div class="card-body">

                                            <h6 class="fw-bold mb-2">BMAN <span class="text-muted fw-normal">(gas funding + collection legs, this app broadcasts these)</span></h6>
                                            <?php if (!empty($row['gas_tx_hash']) || !empty($row['collect_tx_hash'])): ?>
                                                <?php $renderLeg('Gas Funding', $row['gas_tx_hash'] ?? null, (int) ($row['gas_cron_status'] ?? 0) === 1, $gas_fees['bman']['gas'] ?? null); ?>
                                                <?php $renderLeg('BMAN Collection', $row['collect_tx_hash'] ?? null, (int) ($row['collect_cron_status'] ?? 0) === 1, $gas_fees['bman']['collect'] ?? null); ?>
                                            <?php else: ?>
                                                <p class="text-muted small mb-0">No BMAN broadcast yet — waiting on BmanWithdrawCollectCron.</p>
                                            <?php endif; ?>

                                            <hr class="my-3">

                                            <h6 class="fw-bold mb-2">USDT <span class="text-muted fw-normal">(manual payout, admin-entered)</span></h6>
                                            <?php if (!empty($row['tx_hash'])): ?>
                                                <div class="d-flex justify-content-between align-items-start border-bottom py-2">
                                                    <div class="tiny text-muted text-break">
                                                        <?php if ($isRealHash($row['tx_hash'])): ?>
                                                            <a href="<?= htmlspecialchars($explorerUrl . '/tx/' . $row['tx_hash']); ?>" target="_blank" rel="noopener"><?= htmlspecialchars($row['tx_hash']); ?></a>
                                                        <?php else: ?>
                                                            <?= htmlspecialchars($row['tx_hash']); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="badge bg-success">Confirmed</span>
                                                </div>
                                                <?php if (!empty($gas_fees['usdt']) && $gas_fees['usdt']['gas_fee_total'] !== null): ?>
                                                    <p class="text-muted small mt-2 mb-0">Gas: <?= number_format((float) $gas_fees['usdt']['gas_fee_total'], 8); ?> BNB</p>
                                                <?php else: ?>
                                                    <p class="text-muted small mt-2 mb-0">Sent externally by admin — gas cost isn't tracked here. Click the hash above to verify on the explorer.</p>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <p class="text-muted small mb-0">Not yet submitted — admin enters this when approving.</p>
                                            <?php endif; ?>

                                            <?php if (!empty($row['refund_tx_hash'])): ?>
                                                <hr class="my-3">
                                                <h6 class="fw-bold mb-2 text-danger">Rejected — BMAN Refund <span class="text-muted fw-normal">(treasury → user)</span></h6>
                                                <?php $renderLeg('Refund', $row['refund_tx_hash'], true, $gas_fees['bman']['refund'] ?? null); ?>
                                                <?php if (!empty($row['refunded_at'])): ?>
                                                    <p class="text-muted small mt-2 mb-0">Refunded at <?= htmlspecialchars($row['refunded_at']); ?></p>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                        </div>
                                    </div>
                                </div>

                                <!-- Withdrawal Status History -->
                                <?php if (!empty($withdraw_history)): ?>
                                <?php
                                $actionLabels = [
                                    'user_request'           => 'Request Submitted',
                                    'cron_collected'         => 'BMAN Collected On-Chain',
                                    'admin_approve_complete' => 'Approved & USDT Sent',
                                    'admin_reject'           => 'Rejected & BMAN Refunded',
                                    'admin_complete'         => 'Completed (legacy)',
                                    'admin_approve'          => 'Approved (legacy)',
                                    'admin_processing'       => 'Marked Processing (legacy)',
                                    'system_failed'          => 'Marked Failed',
                                ];
                                // Short reference only — NOT the full hash+fee+copy-button
                                // block (that's $renderLeg, used once above in Transaction
                                // Details). Repeating the full block here duplicated every
                                // hash and fee on the page; this is just "which tx", with a
                                // pointer to where the full detail + copy button lives.
                                $shortHash = function ($hash) {
                                    $hash = (string) $hash;
                                    return strlen($hash) > 16 ? substr($hash, 0, 8) . '…' . substr($hash, -6) : $hash;
                                };
                                ?>
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Withdrawal Status History</h5>
                                        <small class="text-muted">Every status change for this request, oldest first — color-coded by the resulting status.</small>
                                    </div>
                                    <div class="card-body">
                                        <div class="wd-history-list">
                                            <?php foreach ($withdraw_history as $item): ?>
                                                <?php $statusClass = strtolower((string) ($item['new_status'] ?? '')); ?>
                                                <div class="wd-history-item status-<?= htmlspecialchars($statusClass); ?>">
                                                    <div class="d-flex justify-content-between flex-wrap gap-2">
                                                        <div>
                                                            <strong><?= htmlspecialchars($actionLabels[$item['action']] ?? $item['action']); ?></strong>
                                                            <span class="badge bg-<?= in_array($statusClass, ['approved', 'completed'], true) ? 'success' : (in_array($statusClass, ['rejected', 'failed'], true) ? 'danger' : ($statusClass === 'pending' ? 'warning' : 'primary')); ?> ms-2 text-uppercase">
                                                                <?= htmlspecialchars($item['old_status'] ?: '—'); ?> → <?= htmlspecialchars($item['new_status']); ?>
                                                            </span>
                                                        </div>
                                                        <span class="text-muted small"><?= htmlspecialchars($item['created_at']); ?></span>
                                                    </div>
                                                    <?php if (!empty($item['admin_id'])): ?>
                                                        <div class="text-muted fs-7">by Admin #<?= (int) $item['admin_id']; ?></div>
                                                    <?php endif; ?>

                                                    <?php if ($item['action'] === 'cron_collected' && (!empty($row['gas_tx_hash']) || !empty($row['collect_tx_hash']))): ?>
                                                        <div class="tiny text-muted mt-1">
                                                            <?php if (!empty($row['gas_tx_hash'])): ?>Gas: <?= htmlspecialchars($shortHash($row['gas_tx_hash'])); ?><?php endif; ?>
                                                            <?php if (!empty($row['gas_tx_hash']) && !empty($row['collect_tx_hash'])): ?> · <?php endif; ?>
                                                            <?php if (!empty($row['collect_tx_hash'])): ?>Collect: <?= htmlspecialchars($shortHash($row['collect_tx_hash'])); ?><?php endif; ?>
                                                            — full hash + gas fee above in Transaction Details
                                                        </div>
                                                    <?php elseif (in_array($item['action'], ['admin_approve_complete', 'admin_complete'], true) && !empty($row['tx_hash'])): ?>
                                                        <div class="tiny text-muted mt-1">USDT: <?= htmlspecialchars($shortHash($row['tx_hash'])); ?> — full hash above in Transaction Details</div>
                                                    <?php elseif ($item['action'] === 'admin_reject' && !empty($row['refund_tx_hash'])): ?>
                                                        <div class="tiny text-muted mt-1">Refund: <?= htmlspecialchars($shortHash($row['refund_tx_hash'])); ?> — full hash + gas fee above in Transaction Details</div>
                                                    <?php elseif (!empty($item['remarks'])): ?>
                                                        <div class="mt-1 text-muted small"><?= htmlspecialchars($item['remarks']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

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

                                <!-- Treasury key reveal — password prompt modal (Super Admin only; button lives in the Member Profile card, under User Wallet Address) -->
                                <?php if (!empty($is_super)): ?>
                                <div class="modal fade" id="btkModal" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Payout Password Required</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <!-- Deliberately NOT type="password": Chrome offers its "Save password?"
                                                     bubble for real password fields no matter what autocomplete says.
                                                     A masked text input keeps the dots without being treated as a
                                                     credential, so no save prompt appears. -->
                                                <input type="text" class="form-control" id="btk-pw" autocomplete="off"
                                                    spellcheck="false" placeholder="Payout password"
                                                    style="-webkit-text-security:disc;">
                                                <div class="text-danger small mt-2 d-none" id="btk-err"></div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="button" class="btn btn-danger" id="btk-submit">Reveal</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php
                                // 'processing' + no approved_at yet = cron owns this, nothing for
                                // the admin to do. 'processing' + approved_at set = legacy manual
                                // flow, an admin already marked it processing by hand
                                // (mark_processing()).
                                $cronOwned = ($row['status'] === 'processing' && empty($row['approved_at']));
                                $legacyApprovedUnpaid = ($row['status'] === 'approved' && empty($row['tx_hash']));
                                $hasActionableOptions = ($row['status'] === 'pending')
                                    || ($row['status'] === 'processing' && !$cronOwned)
                                    || $legacyApprovedUnpaid;
                                ?>

                                <?php if (!$hasActionableOptions): ?>
                                <!-- No admin action possible right now — plain status message,
                                     not a dropdown with nothing real to select. -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Status</h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1">Current: <strong><?= htmlspecialchars($row['status']); ?></strong></p>
                                        <?php if ($cronOwned): ?>
                                            <p class="text-muted mb-3">BmanWithdrawCollectCron is collecting the BMAN on-chain (see above); this will move to "Pending" automatically once confirmed. Nothing to do here yet.</p>
                                        <?php else: ?>
                                            <p class="text-muted mb-3">Terminal state — no further changes possible.</p>
                                        <?php endif; ?>
                                        <a href="<?= base_url('admin/bman-withdrawals'); ?>" class="btn btn-secondary">Back to List</a>
                                    </div>
                                </div>
                                <?php else: ?>
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
                                                    <?php if ($row['status'] === 'processing'): ?>
                                                        <option value="completed">Complete (with tx_hash)</option>
                                                        <option value="failed">Mark as Failed</option>
                                                    <?php elseif ($row['status'] === 'pending'): ?>
                                                        <option value="approved">Approve &amp; Complete (with USDT tx_hash)</option>
                                                        <option value="rejected">Reject &amp; Refund BMAN</option>
                                                    <?php elseif ($legacyApprovedUnpaid): ?>
                                                        <option value="processing">Mark as Processing</option>
                                                        <option value="rejected">Reject Request</option>
                                                    <?php endif; ?>
                                                </select>
                                                <small class="text-muted">
                                                    Current: <strong><?= htmlspecialchars($row['status']); ?></strong>
                                                    <?php if ($row['status'] === 'pending'): ?>
                                                        — BMAN already collected on-chain (see above). Approve to send USDT manually as before, or reject to refund the BMAN.
                                                    <?php endif; ?>
                                                </small>
                                            </div>

                                            <?php if (in_array($row['status'], ['pending', 'approved', 'processing'], true)): ?>
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
    <script>
    (function () {
        // Copy-to-clipboard with "copied" feedback. execCommand fallback keeps
        // this working on the plain-HTTP LAN origin, where navigator.clipboard
        // is unavailable outside secure contexts.
        function markCopied(btn) {
            btn.textContent = 'copied';
            setTimeout(() => { btn.textContent = 'copy'; }, 1500);
        }
        function fallbackCopy(text) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            let ok = false;
            try { ok = document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
            return ok;
        }
        document.querySelectorAll('[data-copy]').forEach(btn => {
            btn.addEventListener('click', () => {
                const el = document.getElementById(btn.dataset.copy);
                if (!el || !el.textContent) return;
                const text = el.textContent;
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(() => markCopied(btn), () => { if (fallbackCopy(text)) markCopied(btn); });
                } else if (fallbackCopy(text)) {
                    markCopied(btn);
                }
            });
        });

        const openBtn = document.getElementById('btk-open');
        if (!openBtn) return;
        const modalEl = document.getElementById('btkModal');
        const modal = () => bootstrap.Modal.getOrCreateInstance(modalEl);
        const pwInput = document.getElementById('btk-pw');
        const err = document.getElementById('btk-err');
        const submitBtn = document.getElementById('btk-submit');
        const resultBox = document.getElementById('btk-result');
        const keyEl = document.getElementById('btk-key');
        let clearTimer = null;

        function clearReveal() {
            keyEl.textContent = '';
            resultBox.classList.add('d-none');
            if (clearTimer) { clearTimeout(clearTimer); clearTimer = null; }
        }

        openBtn.addEventListener('click', () => {
            clearReveal();
            pwInput.value = '';
            err.classList.add('d-none');
            modal().show();
            setTimeout(() => pwInput.focus(), 300);
        });

        async function submit() {
            const pw = pwInput.value;
            if (!pw) { err.textContent = 'Enter the payout password.'; err.classList.remove('d-none'); return; }
            submitBtn.disabled = true;
            err.classList.add('d-none');
            try {
                const res = await fetch('<?= base_url('admin/bman-withdrawals/reveal-treasury-key/' . $row['id']); ?>', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ payout_password: pw })
                });
                const j = await res.json();
                if (j.status !== 'success') {
                    err.textContent = j.message || 'Failed to reveal.';
                    err.classList.remove('d-none');
                    submitBtn.disabled = false;
                    return;
                }
                pwInput.value = '';
                modal().hide();
                keyEl.textContent = j.private_key;
                resultBox.classList.remove('d-none');
                // Auto-clear after 60s — this is convenience, not a security
                // control; the value already left the server the moment this
                // response landed.
                clearTimer = setTimeout(clearReveal, 60000);
            } catch (e) {
                err.textContent = 'Request failed.';
                err.classList.remove('d-none');
            }
            submitBtn.disabled = false;
        }
        submitBtn.addEventListener('click', submit);
        pwInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') submit(); });
    })();
    </script>
</body>

</html>
