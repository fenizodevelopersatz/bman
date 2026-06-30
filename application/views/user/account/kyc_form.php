<!DOCTYPE html>
<html lang="en">

<head>
  <?php $this->load->view('user/layout/v2/user_style'); ?>
  <style>
    /* ===================== KYC PAGE (Modern Card UI) ===================== */

    .page-titlebar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin: 8px 0 18px;
    }

    .page-titlebar h2 {
      font-size: 18px;
      font-weight: 800;
      color: var(--text-main);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .page-titlebar h2 i {
      color: var(--primary);
      font-size: 20px;
    }

    .page-titlebar .sub {
      color: var(--text-muted);
      font-size: 12px;
      margin-top: 4px;
    }

    .kyc-wrap {
      background: #fff;
      border: 1px solid #f5f5f7;
      border-radius: 24px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.04);
      overflow: hidden;
    }

    .kyc-head {
      padding: 18px 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      background: linear-gradient(105deg, rgba(110, 86, 207, 0.12), rgba(110, 86, 207, 0.02));
      border-bottom: 1px solid #f5f5f7;
    }

    .kyc-head .left {
      display: flex;
      align-items: flex-start;
      gap: 12px;
    }

    .kyc-head .icon {
      width: 44px;
      height: 44px;
      border-radius: 16px;
      display: grid;
      place-items: center;
      background: #efedfb;
      color: var(--primary);
      flex-shrink: 0;
      font-size: 20px;
    }

    .kyc-head .meta b {
      display: block;
      font-size: 14px;
    }

    .kyc-head .meta small {
      display: block;
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 2px;
    }

    .status-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 12px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 800;
      background: #fff;
      border: 1px solid #f1f1f6;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.04);
      white-space: nowrap;
    }

    .status-pill i {
      font-size: 16px;
    }

    .status-none {
      color: #475569;
    }

    .status-pending {
      color: #c2410c;
      background: #fff7ed;
      border-color: #fed7aa;
    }

    .status-approved {
      color: #0f9d58;
      background: #ecfdf3;
      border-color: #d1fadf;
    }

    .status-rejected {
      color: #b91c1c;
      background: #fef2f2;
      border-color: #fecaca;
    }

    .kyc-body {
      padding: 18px;
    }

    .kyc-grid {
      display: grid;
      grid-template-columns: 1.4fr 0.6fr;
      gap: 16px;
    }

    .kyc-card {
      background: #fff;
      border: 1px solid #f5f5f7;
      border-radius: 22px;
      padding: 16px;
    }

    .section-h {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 12px;
    }

    .section-h b {
      font-size: 14px;
    }

    .section-h small {
      color: var(--text-muted);
      font-size: 12px;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(12, 1fr);
      gap: 12px;
    }

    .fg {
      grid-column: span 12;
    }

    .col-6 {
      grid-column: span 6;
    }

    .col-4 {
      grid-column: span 4;
    }

    .col-3 {
      grid-column: span 3;
    }

    .f-label {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      font-weight: 700;
      color: #111827;
      margin: 2px 0 8px;
    }

    .req-star {
      color: #ef4444;
    }

    .f-input,
    .f-select {
      width: 100%;
      border: 1px solid #e9e9f2;
      background: #fbfbff;
      border-radius: 14px;
      padding: 12px 12px;
      outline: none;
      font-size: 13px;
      transition: .15s;
    }

    .f-input:focus,
    .f-select:focus {
      border-color: #dcd7ff;
      background: #fff;
      box-shadow: 0 0 0 4px rgba(110, 86, 207, 0.10);
    }

    .file-drop {
      border: 1px dashed #dcd7ff;
      background: linear-gradient(180deg, #ffffff 0%, #fbfbff 100%);
      border-radius: 18px;
      padding: 14px;
      display: flex;
      align-items: center;
      gap: 12px;
      cursor: pointer;
      min-height: 76px;
    }

    .file-drop .ic {
      width: 44px;
      height: 44px;
      border-radius: 16px;
      display: grid;
      place-items: center;
      background: #efedfb;
      color: var(--primary);
      flex-shrink: 0;
      font-size: 20px;
    }

    .file-drop b {
      display: block;
      font-size: 12px;
    }

    .file-drop small {
      display: block;
      color: var(--text-muted);
      font-size: 11px;
      margin-top: 3px;
      line-height: 1.4;
    }

    .file-drop a {
      color: var(--primary);
      font-weight: 800;
      text-decoration: none;
    }

    .file-drop:hover {
      transform: translateY(-1px);
      transition: .15s;
    }

    .help-box {
      background: #f6f5ff;
      border: 1px solid #eeecff;
      border-radius: 22px;
      padding: 14px;
    }

    .help-box b {
      font-size: 13px;
      display: flex;
      gap: 8px;
      align-items: center;
    }

    .help-box b i {
      color: var(--primary);
    }

    .help-box p {
      font-size: 12px;
      color: var(--text-muted);
      line-height: 1.5;
      margin-top: 8px;
    }

    .help-list {
      margin-top: 10px;
      display: grid;
      gap: 8px;
    }

    .help-li {
      display: flex;
      gap: 10px;
      align-items: flex-start;
      font-size: 12px;
      color: #5d56a8;
      font-weight: 600;
    }

    .help-li i {
      color: var(--primary);
      margin-top: 1px;
    }

    .consent {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      margin-top: 12px;
      font-size: 12px;
      color: var(--text-muted);
      line-height: 1.4;
    }

    .consent input {
      margin-top: 3px;
    }

    .actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 16px;
    }

    .btn-lite {
      border: 1px solid #f1f1f6;
      background: #fff;
      padding: 12px 14px;
      border-radius: 14px;
      font-weight: 800;
      cursor: pointer;
    }

    .btn-primary2 {
      border: none;
      background: var(--primary);
      color: #fff;
      padding: 12px 16px;
      border-radius: 14px;
      font-weight: 900;
      cursor: pointer;
    }

    /* Responsive */
    @media(max-width: 1200px) {
      .kyc-grid {
        grid-template-columns: 1fr;
      }
    }

    @media(max-width: 900px) {
      .col-6 {
        grid-column: span 12;
      }

      .col-4 {
        grid-column: span 12;
      }

      .col-3 {
        grid-column: span 12;
      }
    }



.kyc-preview{
  display:flex;
  align-items:center;
  gap:12px;
  margin-top:10px;
}

.kyc-preview .kyc-thumb{
  width:64px;          /* ✅ thumbnail width */
  height:64px;         /* ✅ thumbnail height */
  border-radius:10px;
  object-fit:cover;    /* ✅ crop nicely */
  border:1px solid rgba(0,0,0,.08);
  background:#f6f7f9;
  flex:0 0 64px;
}


.kyc-preview.sm .kyc-thumb{
  width:48px;
  height:48px;
  flex:0 0 48px;
}


.kyc-preview .meta .name{
  font-weight:600;
  font-size:13px;
  line-height:1.2;
  max-width:260px;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}
.kyc-preview .meta .size{
  font-size:12px;
  opacity:.7;
}

.kyc-preview img.kyc-thumb{
  width:64px !important;
  height:64px !important;
  max-width:64px !important;
  max-height:64px !important;
}



  </style>
</head>

<body>
  <div class="app-container">
    <!-- Sidebar -->
    <?php $this->load->view('user/layout/v2/user_sidebar'); ?>

    <!-- Main Content -->
    <main class="main-content">
      <?php $this->load->view('user/layout/v2/user_header'); ?>

      <!-- Page title -->
      <div class="page-titlebar">
        <div>
          <h2><i class="ph ph-identification-card"></i> KYC Verification</h2>
          <div class="sub">Submit your identity documents to enable withdrawals and secure your account.</div>
        </div>
      </div>

      <!-- KYC Container -->
      <div class="kyc-wrap">
        <!-- Header -->
        <div class="kyc-head">
          <div class="left">
            <div class="icon"><i class="ph ph-shield-check"></i></div>
            <div class="meta">
              <b>Verify your identity</b>
              <small>Usually takes 5–30 minutes after submission</small>
            </div>
          </div>

          <!-- Change status class based on your backend -->
          <!-- NONE / PENDING / APPROVED / REJECTED -->
          <?php
              $st = strtolower($kyc['status'] ?? 'none');

              $cls = 'status-none';
              $icon = 'ph ph-info';
              $label = 'NONE';

              if ($st === 'pending' || $st === 'under_review' || $st === 'resubmitted') {
                $cls = 'status-pending';
                $icon = 'ph ph-hourglass';
                $label = strtoupper($st);
              } elseif ($st === 'approved') {
                $cls = 'status-approved';
                $icon = 'ph ph-check-circle';
                $label = 'APPROVED';
              } elseif ($st === 'rejected') {
                $cls = 'status-rejected';
                $icon = 'ph ph-x-circle';
                $label = 'REJECTED';
              } elseif ($st !== 'none' && $st !== '') {
                // fallback for any other enum values you may add later
                $cls = 'status-none';
                $icon = 'ph ph-info';
                $label = strtoupper($st);
              }
              ?>

              <div class="status-pill <?php echo $cls; ?>">
                <i class="<?php echo $icon; ?>"></i>
                Current status:
                <span style="margin-left:4px;"><?php echo $label; ?></span>
              </div>

        </div>

        <div class="kyc-body">
          <div class="kyc-grid">
            <!-- LEFT: Form -->
            <div class="kyc-card">
              <div class="section-h">
                <b>Personal & Document Details</b>
                <small>* Required fields</small>
              </div>

              <form id="kyc_form" action="#" method="post" enctype="multipart/form-data">
                <!-- keep old uploaded URLs (for JS checks + resubmit UX) -->
                <input type="hidden" name="prev_doc_front_url"
                  value="<?php echo html_escape($kyc['doc_front_url'] ?? ''); ?>">
                <input type="hidden" name="prev_doc_back_url"
                  value="<?php echo html_escape($kyc['doc_back_url'] ?? ''); ?>">
                <input type="hidden" name="prev_selfie_url"
                  value="<?php echo html_escape($kyc['selfie_url'] ?? ''); ?>">

                <div class="form-grid">

                  <!-- Full Name / DOB / Gender -->
                  <div class="fg col-6">
                    <div class="f-label">Full Name <span class="req-star">*</span></div>
                    <input class="f-input" type="text" name="full_name"
                      value="<?php echo html_escape($kyc['full_name'] ?? ''); ?>" placeholder="Enter your full name"
                      <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                  </div>

                  <div class="fg col-3">
                    <div class="f-label">Date of Birth <span class="req-star">*</span></div>
                    <input class="f-input" type="date" name="dob" value="<?php echo html_escape($kyc['dob'] ?? ''); ?>"
                      <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                  </div>

                  <div class="fg col-3">
                    <div class="f-label">Gender</div>
                    <select class="f-select" name="gender" <?php echo !empty($read_only) ? 'disabled' : ''; ?>>
                      <?php $g = $kyc['gender'] ?? 'unspecified'; ?>
                      <option value="unspecified" <?php echo $g === 'unspecified' ? 'selected' : ''; ?>>Prefer not to say
                      </option>
                      <option value="male" <?php echo $g === 'male' ? 'selected' : ''; ?>>Male</option>
                      <option value="female" <?php echo $g === 'female' ? 'selected' : ''; ?>>Female</option>
                      <option value="other" <?php echo $g === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                    <?php if (!empty($read_only)): ?>
                      <!-- disabled fields don't submit; keep value -->
                      <input type="hidden" name="gender" value="<?php echo html_escape($g); ?>">
                    <?php endif; ?>
                  </div>

                  <!-- Country / Nationality (ISO2) -->
                  <div class="fg col-3">
                    <div class="f-label">Country of Residence <span class="req-star">*</span></div>
                    <select class="f-select" name="country_iso2" <?php echo !empty($read_only) ? 'disabled' : ''; ?>>
                      <?php $c = strtoupper($kyc['country_iso2'] ?? 'IN'); ?>
                      <?php foreach (($countries ?? []) as $iso2 => $label): ?>
                        <option value="<?php echo html_escape($iso2); ?>" <?php echo strtoupper($iso2) === $c ? 'selected' : ''; ?>>
                          <?php echo html_escape($label); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <?php if (!empty($read_only)): ?>
                      <input type="hidden" name="country_iso2" value="<?php echo html_escape($c); ?>">
                    <?php endif; ?>
                  </div>

                  <div class="fg col-3">
                    <div class="f-label">Nationality <span class="req-star">*</span></div>
                    <select class="f-select" name="nationality_iso2" <?php echo !empty($read_only) ? 'disabled' : ''; ?>>
                      <?php $n = strtoupper($kyc['nationality_iso2'] ?? 'IN'); ?>
                      <?php foreach (($countries ?? []) as $iso2 => $label): ?>
                        <option value="<?php echo html_escape($iso2); ?>" <?php echo strtoupper($iso2) === $n ? 'selected' : ''; ?>>
                          <?php echo html_escape($label); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <?php if (!empty($read_only)): ?>
                      <input type="hidden" name="nationality_iso2" value="<?php echo html_escape($n); ?>">
                    <?php endif; ?>
                  </div>

                  <div class="fg col-6"></div>

                  <!-- Address -->
                  <div class="fg col-6">
                    <div class="f-label">Address Line 1 <span class="req-star">*</span></div>
                    <input class="f-input" type="text" name="addr_line1"
                      value="<?php echo html_escape($kyc['addr_line1'] ?? ''); ?>" placeholder="House / Street / Area"
                      <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                  </div>

                  <div class="fg col-6">
                    <div class="f-label">Address Line 2</div>
                    <input class="f-input" type="text" name="addr_line2"
                      value="<?php echo html_escape($kyc['addr_line2'] ?? ''); ?>"
                      placeholder="Apartment / Landmark (optional)" <?php echo !empty($read_only) ? 'readonly' : ''; ?>
                    />
                  </div>

                  <div class="fg col-4">
                    <div class="f-label">City <span class="req-star">*</span></div>
                    <input class="f-input" type="text" name="addr_city"
                      value="<?php echo html_escape($kyc['addr_city'] ?? ''); ?>" placeholder="Enter city" <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                  </div>

                  <div class="fg col-4">
                    <div class="f-label">Region / State</div>
                    <input class="f-input" type="text" name="addr_region"
                      value="<?php echo html_escape($kyc['addr_region'] ?? ''); ?>" placeholder="Enter state" <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                  </div>

                  <div class="fg col-4">
                    <div class="f-label">Postal Code <span class="req-star">*</span></div>
                    <input class="f-input" type="text" name="addr_postal"
                      value="<?php echo html_escape($kyc['addr_postal'] ?? ''); ?>" placeholder="Enter postal code"
                      <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                  </div>

                  <!-- Document -->
                  <div class="fg col-4">
                    <div class="f-label">Document Type <span class="req-star">*</span></div>
                    <?php $dt = $kyc['doc_type'] ?? 'passport'; ?>
                    <select class="f-select" name="doc_type" <?php echo !empty($read_only) ? 'disabled' : ''; ?>>
                      <option value="passport" <?php echo $dt === 'passport' ? 'selected' : ''; ?>>Passport</option>
                      <option value="national_id" <?php echo $dt === 'national_id' ? 'selected' : ''; ?>>National ID</option>
                      <option value="driver_license" <?php echo $dt === 'driver_license' ? 'selected' : ''; ?>>Driver License
                      </option>
                    </select>
                    <?php if (!empty($read_only)): ?>
                      <input type="hidden" name="doc_type" value="<?php echo html_escape($dt); ?>">
                    <?php endif; ?>
                  </div>

                  <div class="fg col-4">
                    <div class="f-label">Document Number <span class="req-star">*</span></div>
                    <input class="f-input" type="text" name="doc_number"
                      value="<?php echo html_escape($kyc['doc_number'] ?? ''); ?>" placeholder="Enter document number"
                      <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                  </div>

                  <div class="fg col-4">
                    <div class="f-label">Issuing Country <span class="req-star">*</span></div>
                    <?php $ic = strtoupper($kyc['doc_issue_country'] ?? ($kyc['country_iso2'] ?? 'IN')); ?>
                    <select class="f-select" name="doc_issue_country" <?php echo !empty($read_only) ? 'disabled' : ''; ?>>
                      <?php foreach (($countries ?? []) as $iso2 => $label): ?>
                        <option value="<?php echo html_escape($iso2); ?>" <?php echo strtoupper($iso2) === $ic ? 'selected' : ''; ?>>
                          <?php echo html_escape($label); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <?php if (!empty($read_only)): ?>
                      <input type="hidden" name="doc_issue_country" value="<?php echo html_escape($ic); ?>">
                    <?php endif; ?>
                  </div>

                  <div class="fg col-3">
                    <div class="f-label">Issued</div>
                    <input class="f-input" type="date" name="doc_issue_date"
                      value="<?php echo html_escape($kyc['doc_issue_date'] ?? ''); ?>" <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                  </div>

                  <div class="fg col-3">
                    <div class="f-label">Expiry</div>
                    <input class="f-input" type="date" name="doc_expiry_date"
                      value="<?php echo html_escape($kyc['doc_expiry_date'] ?? ''); ?>" <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                  </div>

                  <div class="fg col-6"></div>

                  <!-- Uploads (names MUST match controller) -->
                  <div class="fg col-6 col-md-6">
                    <div class="f-label">ID Front (Image/PDF) <span class="req-star">*</span></div>

                    <div class="js-kyc file-drop" data-input="doc_front">
                      <div class="ic"><i class="ph ph-upload-simple"></i></div>
                      <div>
                        <b>Drag & drop or <a href="javascript:void(0)">browse</a></b>
                        <small>JPG/PNG/WEBP/GIF or PDF • Max 8 MB</small>
                        <?php if (!empty($kyc['doc_front_url'])): ?>
                          <div class="mt-1"><a target="_blank"
                              href="<?php echo html_escape($kyc['doc_front_url']); ?>">View uploaded</a></div>
                        <?php endif; ?>
                      </div>
                      <input type="file" name="doc_front" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf" hidden <?php echo !empty($read_only) ? 'disabled' : ''; ?> />
                      <a href="javascript:void(0)" class="js-remove d-none">Remove</a>
                    </div>

                    <div class="kyc-preview d-none">
                      <img class="kyc-thumb" src="" alt="">
                      <div class="meta">
                        <div class="name"></div>
                        <div class="size"></div>
                        <a class="js-view" target="_blank" href="#">View</a>
                      </div>
                    </div>
                  </div>

                  <div class="fg col-6 col-md-6">
                    <div class="f-label">ID Back (Image/PDF)</div>

                    <div class="js-kyc file-drop" data-input="doc_back">
                      <div class="ic"><i class="ph ph-upload-simple"></i></div>
                      <div>
                        <b>Drag & drop or <a href="javascript:void(0)">browse</a></b>
                        <small>JPG/PNG/WEBP/GIF or PDF • Max 8 MB</small>
                        <?php if (!empty($kyc['doc_back_url'])): ?>
                          <div class="mt-1"><a target="_blank"
                              href="<?php echo html_escape($kyc['doc_back_url']); ?>">View uploaded</a></div>
                        <?php endif; ?>
                      </div>
                      <input type="file" name="doc_back" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf" hidden <?php echo !empty($read_only) ? 'disabled' : ''; ?> />
                      <a href="javascript:void(0)" class="js-remove d-none">Remove</a>
                    </div>

                    <div class="kyc-preview d-none">
                      <img class="kyc-thumb" src="" alt="">
                      <div class="meta">
                        <div class="name"></div>
                        <div class="size"></div>
                        <a class="js-view" target="_blank" href="#">View</a>
                      </div>
                    </div>
                  </div>

                  <div class="fg col-6 col-md-6">
                    <div class="f-label">Selfie (Image) <span class="req-star">*</span></div>

                    <div class="js-kyc file-drop" data-input="selfie">
                      <div class="ic"><i class="ph ph-camera"></i></div>
                      <div>
                        <b>Drag & drop or <a href="javascript:void(0)">browse</a></b>
                        <small>JPG/PNG/WEBP/GIF • Max 8 MB</small>
                        <?php if (!empty($kyc['selfie_url'])): ?>
                          <div class="mt-1"><a target="_blank" href="<?php echo html_escape($kyc['selfie_url']); ?>">View
                              uploaded</a></div>
                        <?php endif; ?>
                      </div>
                      <input type="file" name="selfie" accept=".jpg,.jpeg,.png,.webp,.gif" hidden <?php echo !empty($read_only) ? 'disabled' : ''; ?> />
                      <a href="javascript:void(0)" class="js-remove d-none">Remove</a>
                    </div>

                    <div class="kyc-preview d-none">
                      <img class="kyc-thumb" src="" alt="">
                      <div class="meta">
                        <div class="name"></div>
                        <div class="size"></div>
                        <a class="js-view" target="_blank" href="#">View</a>
                      </div>
                    </div>
                  </div>

                  <div class="fg col-6 col-md-6">
                    <div class="f-label">Proof of Address (Image/PDF)</div>

                    <div class="js-kyc file-drop" data-input="proof_address">
                      <div class="ic"><i class="ph ph-file"></i></div>
                      <div>
                        <b>Drag & drop or <a href="javascript:void(0)">browse</a></b>
                        <small>JPG/PNG/WEBP/GIF or PDF • Max 8 MB</small>
                        <?php if (!empty($kyc['proof_address_url'])): ?>
                          <div class="mt-1"><a target="_blank"
                              href="<?php echo html_escape($kyc['proof_address_url']); ?>">View uploaded</a></div>
                        <?php endif; ?>
                      </div>
                      <input type="file" name="proof_address" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf" hidden <?php echo !empty($read_only) ? 'disabled' : ''; ?> />
                      <a href="javascript:void(0)" class="js-remove d-none">Remove</a>
                    </div>

                    <div class="kyc-preview d-none">
                      <img class="kyc-thumb" src="" alt="">
                      <div class="meta">
                        <div class="name"></div>
                        <div class="size"></div>
                        <a class="js-view" target="_blank" href="#">View</a>
                      </div>
                    </div>
                  </div>

                </div><!-- /form-grid -->

                <!-- Consent -->
                <?php $cons = (int) ($kyc['consent'] ?? 0); ?>
                <div class="consent">
                  <input type="checkbox" name="consent" id="consent" value="1" <?php echo ($cons === 1) ? 'checked' : ''; ?>
                  <?php echo !empty($read_only) ? 'disabled' : ''; ?> />
                  <label for="consent">I consent to verification & data processing as per platform policy.</label>
                  <?php if (!empty($read_only)): ?>
                    <input type="hidden" name="consent" value="<?php echo $cons ? '1' : '0'; ?>">
                  <?php endif; ?>
                </div>

                <div class="actions">
                  <button type="button" class="btn-lite" onclick="history.back()">Cancel</button>

                  <?php if (!empty($read_only)): ?>
                    <button type="button" class="btn-primary2" disabled>
                      <?php echo strtoupper($kyc['status'] ?? 'PENDING'); ?>
                    </button>
                  <?php else: ?>
                    <button type="submit" id="kyc_submit_btn" class="btn-primary2">
                      Submit KYC
                    </button>
                  <?php endif; ?>
                </div>
              </form>

            </div>

            <!-- RIGHT: Tips/Info -->
            <div class="kyc-card">
              <div class="section-h">
                <b>Quick Tips</b>
                <small>Improve approval rate</small>
              </div>

              <div class="help-box">
                <b><i class="ph ph-lightbulb"></i> Before you submit</b>
                <p>
                  Make sure your document images are clear and match the details you entered. Incorrect or blurred
                  uploads may be rejected.
                </p>

                <div class="help-list">
                  <div class="help-li"><i class="ph ph-check-circle"></i> Use original documents (no screenshots)</div>
                  <div class="help-li"><i class="ph ph-check-circle"></i> Ensure all corners are visible</div>
                  <div class="help-li"><i class="ph ph-check-circle"></i> Selfie must be well-lit, no sunglasses</div>
                  <div class="help-li"><i class="ph ph-check-circle"></i> Name & DOB must match your ID</div>
                </div>
              </div>

              <div class="help-box" style="margin-top:12px;">
                <b><i class="ph ph-headset"></i> Need Support?</b>
                <p>Facing issues while submitting? Contact support and we’ll help you quickly.</p>
                <button class="btn-primary2" style="width:100%;margin-top:10px;" onclick="window.location.href='<?php echo base_url(); ?>user/support'">Create Support Ticket</button>
              </div>

            </div>
          </div>
        </div>
      </div>

    </main>

    <!-- Right Panel (keep your existing) -->
    <aside class="right-panel">
      <?php $this->load->view('user/layout/v2/user_inner_right_panle'); ?>
    </aside>
  </div>


  <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    const base_url = "<?php echo base_url(); ?>";
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.querySelector('#kyc_form');
      const submitBtn = document.querySelector('#kyc_submit_btn');
      if (!form || !submitBtn) return;

      const csrfName = window.csrfName || "ci_csrf_token";
      let csrfHash = window.csrfHash || "";

      const MAX_MB = 8;
      const ACCEPT_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];

      // --- helper refs (SAFE) ---
      const el = {
        fullName: form.querySelector('input[name="full_name"]'),
        dob: form.querySelector('input[name="dob"]'),
        consent: form.querySelector('input[name="consent"]'),
        docType: form.querySelector('select[name="doc_type"]'),
        docFront: form.querySelector('input[name="doc_front"]'),
        docBack: form.querySelector('input[name="doc_back"]'),
        selfie: form.querySelector('input[name="selfie"]'),
        proof: form.querySelector('input[name="proof_address"]'),

        prevFront: form.querySelector('input[name="prev_doc_front_url"]'),
        prevBack: form.querySelector('input[name="prev_doc_back_url"]'),
        prevSelf: form.querySelector('input[name="prev_selfie_url"]'),
      };

      function fmtBytes(x) { if (!x) return ''; const u = ['B', 'KB', 'MB', 'GB']; let i = 0; while (x >= 1024 && i < u.length - 1) { x /= 1024; i++ } return x.toFixed(i ? 1 : 0) + ' ' + u[i]; }
      function busy(b) { if (b) { submitBtn.setAttribute('data-kt-indicator', 'on'); submitBtn.disabled = true; } else { submitBtn.removeAttribute('data-kt-indicator'); submitBtn.disabled = false; } }
      function validFile(file, imageOnly = false) {
        if (!file) return true;
        if (imageOnly && !file.type.startsWith('image/')) return false;
        if (!(ACCEPT_TYPES.includes(file.type) || file.type.startsWith('image/'))) return false;
        return file.size <= MAX_MB * 1024 * 1024;
      }

      // --- enhance tiles (expects .js-kyc tiles + preview blocks) ---
      document.querySelectorAll('.js-kyc').forEach(tile => {
        const name = tile.dataset.input; // doc_front / doc_back / selfie / proof_address
        const input = form.querySelector(`input[type="file"][name="${name}"]`);
        if (!input) return;

        const wrap = tile.closest('.col-md-6, .fg') || tile.parentElement;
        const pv = wrap?.querySelector('.kyc-preview');
        const img = pv?.querySelector('.kyc-thumb');
        const n = pv?.querySelector('.name');
        const s = pv?.querySelector('.size');
        const view = pv?.querySelector('.js-view');
        const rm = tile.querySelector('.js-remove');

        function show(file) {
          if (!pv || !img || !n || !s || !view) return;
          pv.classList.remove('d-none'); if (rm) rm.classList.remove('d-none');
          if (file.type === 'application/pdf') {
            img.src = 'data:image/svg+xml;utf8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="54" height="54"><rect width="54" height="54" rx="8" fill="#F1F3F5"/><text x="50%" y="55%" text-anchor="middle" font-size="14" fill="#6c757d" font-family="Arial">PDF</text></svg>');
          } else {
            img.src = URL.createObjectURL(file);
          }
          n.textContent = file.name; s.textContent = fmtBytes(file.size); view.href = URL.createObjectURL(file);
        }
        function clear() { input.value = ''; if (pv) pv.classList.add('d-none'); if (rm) rm.classList.add('d-none'); }

        tile.addEventListener('click', e => { if (!e.target.closest('.js-remove')) input.click(); });
        if (rm) rm.addEventListener('click', e => { e.preventDefault(); clear(); });

        ['dragenter', 'dragover'].forEach(ev => tile.addEventListener(ev, e => { e.preventDefault(); tile.classList.add('dragover'); }));
        ['dragleave', 'drop'].forEach(ev => tile.addEventListener(ev, e => { e.preventDefault(); tile.classList.remove('dragover'); }));
        tile.addEventListener('drop', e => {
          const f = e.dataTransfer.files[0]; if (!f) return;
          if (!validFile(f)) return Swal.fire('Invalid file', 'Allowed: images/PDF up to 8MB.', 'warning');
          input.files = e.dataTransfer.files; show(f);
        });

        input.addEventListener('change', () => {
          const f = input.files[0]; if (!f) { clear(); return; }
          // selfie should be image only
          const imageOnly = (name === 'selfie');
          if (!validFile(f, imageOnly)) { clear(); return Swal.fire('Invalid file', 'Allowed: images/PDF up to 8MB (Selfie must be image).', 'warning'); }
          show(f);
        });
      });

      async function doSubmit(e) {
        if (e) e.preventDefault();        
        if (!el.fullName?.value.trim() || !el.dob?.value || !el.consent?.checked) {
          return Swal.fire('Missing data', 'Please fill mandatory fields and check consent.', 'warning');
        }

        const docType = el.docType?.value || 'passport';
        const fFront = el.docFront?.files?.[0];
        const fBack = el.docBack?.files?.[0];
        const fSelfie = el.selfie?.files?.[0];

        const hasPrevFront = !!(el.prevFront?.value || '').trim();
        const hasPrevBack = !!(el.prevBack?.value || '').trim();
        const hasPrevSelfie = !!(el.prevSelf?.value || '').trim();

        if (!fFront && !hasPrevFront) return Swal.fire('Document required', 'Upload ID Front.', 'warning');
        if (!fSelfie && !hasPrevSelfie) return Swal.fire('Selfie required', 'Upload a selfie.', 'warning');

        if ((docType === 'national_id' || docType === 'driver_license') && !fBack && !hasPrevBack) {
          return Swal.fire('Back side required', 'Upload ID Back for this document type.', 'warning');
        }

        const checks = [
          [fFront, false],
          [fBack, false],
          [fSelfie, true],
          [el.proof?.files?.[0], false]
        ];
        for (const [f, imgOnly] of checks) {
          if (f && !validFile(f, imgOnly)) return Swal.fire('Invalid file', 'Allowed: images/PDF up to 8MB.', 'warning');
        }

        busy(true);
          try {
            const fd = new FormData(form);
            fd.append(csrfName, csrfHash);

            const res = await fetch(base_url + 'user/kyc/submit', {
              method: 'POST',
              body: fd,
              headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            // fetch: parse JSON manually
            const text = await res.text();
            let data = {};
            try { data = text ? JSON.parse(text) : {}; } catch (e) { data = { status:'error', message:text || 'Invalid server response' }; }

            // if server returns 4xx/5xx, res.ok is false
            if (!res.ok) {
              // keep csrf updated if backend sends it even on error
              if (data.csrf?.hash) csrfHash = data.csrf.hash;
              return Swal.fire('Error', data.message || `Request failed (${res.status})`, 'error');
            }

            if (data.status === 'success') {
              if (data.csrf?.hash) csrfHash = data.csrf.hash;
              Swal.fire('Submitted', data.message || 'KYC submitted.', 'success')
                .then(() => location.reload());
            } else {
              Swal.fire('Error', data.message || 'Could not submit KYC.', 'error');
            }

          } catch (err) {
            console.log(err);
            Swal.fire('Error', 'Network error or server not reachable.', 'error');
          } finally {
            busy(false);
          }

      }

      // Button click + form submit both handled
      submitBtn.addEventListener('click', doSubmit);
      form.addEventListener('submit', doSubmit);
    });

    function show(file) {
  if (!pv || !img || !n || !s || !view) return;

  pv.classList.remove('d-none');
  if (rm) rm.classList.remove('d-none');

  const objUrl = URL.createObjectURL(file);

  // store to revoke later
  pv.dataset.objUrl = objUrl;

  if (file.type === 'application/pdf') {
    img.src =
      'data:image/svg+xml;utf8,' +
      encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="54" height="54"><rect width="54" height="54" rx="8" fill="#F1F3F5"/><text x="50%" y="55%" text-anchor="middle" font-size="14" fill="#6c757d" font-family="Arial">PDF</text></svg>'
      );
    view.href = objUrl; // ✅ open PDF
  } else {
    img.src = objUrl;   // ✅ thumbnail
    view.href = objUrl; // ✅ open same
  }

  n.textContent = file.name;
  s.textContent = fmtBytes(file.size);
}

function clear() {
  input.value = '';

  // revoke old URL to avoid memory leak
  if (pv?.dataset?.objUrl) {
    URL.revokeObjectURL(pv.dataset.objUrl);
    delete pv.dataset.objUrl;
  }

  if (pv) pv.classList.add('d-none');
  if (rm) rm.classList.add('d-none');

  // ✅ important: remove empty src (prevents blank/broken preview)
  if (img) img.removeAttribute('src');

  if (n) n.textContent = '';
  if (s) s.textContent = '';
  if (view) view.removeAttribute('href');
}

  </script>
<style>
  /* Bootstrap-like helper (because your theme may not have it) */
.d-none { display: none !important; }

/* Hide preview by default (even if CSS above sets flex) */
.kyc-preview.d-none { display:none !important; }

/* Prevent empty img showing (when src="") */
.kyc-preview .kyc-thumb[src=""],
.kyc-preview .kyc-thumb:not([src]) {
  display: none !important;
}

</style>
</body>

</html>