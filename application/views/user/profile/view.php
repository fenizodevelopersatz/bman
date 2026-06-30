<?php
// Normalize arrays -> objects (view uses ->)
$userArr = is_array($user ?? null) ? $user : [];
$kycArr = is_array($kyc ?? null) ? $kyc : [];
$bankArr = is_array($bank ?? null) ? $bank : [];
$prefsArr = is_array($prefs ?? null) ? $prefs : [];

// USERS TABLE fields
$user = (object) array_merge([
  'id' => '',
  'name' => '',
  'first_name' => '',
  'last_name' => '',
  'username' => '',
  'email' => '',
  'contact' => '',
  'country' => '',
  'time_zone' => '',
  'profile_img' => '',
  'referral_id' => '',
  'rank_id' => '',
  'twofa_status' => 0,
], $userArr);

// user_kyc table fields
$kyc = (object) array_merge([
  'status' => 'none', // none|pending|under_review|approved|rejected|resubmit
  'full_name_pan' => '',
  'pan_number' => '',
  'dob' => '',
  'aadhaar_last4' => '',
  'address' => '',
  'city' => '',
  'state' => '',
  'pincode' => '',
  'pan_doc' => '',
  'aadhaar_doc' => '',
  'reviewer_note' => '',
  'submitted_at' => '',
], $kycArr);

// user_bank table fields
$bank = (object) array_merge([
  'status' => 'not_added', // not_added|pending|approved|rejected
  'holder_name' => '',
  'bank_name' => '',
  'account_number' => '',
  'ifsc' => '',
  'upi_id' => '',
  'note' => '',
  'submitted_at' => '',
], $bankArr);

// prefs - you already send fallback in controller
$prefs = (object) array_merge([
  'success_payments' => 0,
  'payouts' => 1,
  'product_commission' => 0,
  'refund_alerts' => 0,
  'invoice_payments' => 1,
], $prefsArr);

// Display name / uid / rank
$displayName = trim(($user->first_name . ' ' . $user->last_name));
if ($displayName === '')
  $displayName = trim($user->name ?: 'User');

$displayUid = $user->referral_id ?: ('USER' . $user->id);
$displayRank = $user->rank_id ?: '—';

// Avatar
$avatarUrl = !empty($user->profile_img)
  ? base_url('assets/images/' . $user->profile_img)
  : 'https://i.pravatar.cc/160?u=' . urlencode(($user->email ?: $user->contact ?: 'user'));

// Rank progress + eligibility from controller
$rankPercent = isset($rankPercent) ? (int) $rankPercent : 0;
$eligibleText = isset($eligibleText) ? $eligibleText : 'Not Eligible';

function badgeClassPro($st)
{
  $st = strtolower(trim($st ?? ''));
  if ($st === 'approved' || $st === 'verified')
    return 'b-ok';
  if ($st === 'pending' || $st === 'under_review')
    return 'b-warn';
  if ($st === 'rejected' || $st === 'resubmit')
    return 'b-bad';
  return 'b-soft';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php $this->load->view('user/layout/v2/user_style'); ?>
  <script src="https://unpkg.com/@phosphor-icons/web"></script>

  <style>
    /* (your existing styles - kept as-is) */
    .titlebar {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      gap: 12px;
      margin: 8px 0 16px;
    }

    .titlebar h2 {
      margin: 0;
      font-size: 18px;
      font-weight: 1100;
      color: var(--text-main);
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .titlebar h2 i {
      color: var(--primary);
      font-size: 20px;
    }

    .titlebar .sub {
      margin-top: 4px;
      font-size: 12px;
      color: var(--text-muted);
      font-weight: 900;
    }

    .grid-2 {
      display: grid;
      grid-template-columns: 1.15fr .85fr;
      gap: 14px;
    }

    .card {
      background: #fff;
      border: 1px solid #f5f5f7;
      border-radius: 22px;
      padding: 14px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.04);
    }

    .card-h {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 12px;
    }

    .card-h h3 {
      margin: 0;
      font-size: 13px;
      font-weight: 1100;
    }

    .chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 7px 10px;
      border-radius: 999px;
      border: 1px solid #eeecff;
      background: #efedfb;
      color: var(--primary);
      font-size: 10px;
      font-weight: 1100;
    }

    .muted {
      color: var(--text-muted);
      font-weight: 900;
      font-size: 12px;
    }

    .btn-soft {
      border: 1px solid #f1f1f6;
      background: #fff;
      border-radius: 14px;
      padding: 10px 12px;
      font-weight: 1100;
      cursor: pointer;
      font-size: 12px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-main {
      border: none;
      background: var(--primary);
      color: #fff;
      border-radius: 14px;
      padding: 10px 12px;
      font-weight: 1100;
      cursor: pointer;
      font-size: 12px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-dark {
      border: none;
      background: #111;
      color: #fff;
      border-radius: 14px;
      padding: 10px 12px;
      font-weight: 1100;
      cursor: pointer;
      font-size: 12px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-danger {
      border: none;
      background: #ef4444;
      color: #fff;
      border-radius: 14px;
      padding: 10px 12px;
      font-weight: 1100;
      cursor: pointer;
      font-size: 12px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .tabs {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-bottom: 12px;
    }

    .tab {
      border: 1px solid #f1f1f6;
      background: #fff;
      border-radius: 999px;
      padding: 9px 12px;
      font-size: 12px;
      font-weight: 1100;
      cursor: pointer;
      display: flex;
      gap: 8px;
      align-items: center;
    }

    .tab.active {
      background: #efedfb;
      border-color: #eeecff;
      color: var(--primary);
    }

    .tab i {
      font-size: 16px;
    }

    .tabpanels>.panel {
      display: none;
    }

    .tabpanels>.panel.active {
      display: block;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    .field {
      display: grid;
      gap: 6px;
    }

    .field label {
      font-size: 11px;
      font-weight: 1100;
      color: #111;
      display: flex;
      gap: 8px;
      align-items: center;
    }

    .inp,
    .sel,
    .ta {
      border: 1px solid #f1f1f6;
      background: #f7f7fb;
      border-radius: 14px;
      padding: 11px 12px;
      outline: none;
      font-size: 12px;
      font-weight: 900;
      color: #111;
    }

    .ta {
      min-height: 92px;
      resize: vertical;
    }

    .inp:focus,
    .sel:focus,
    .ta:focus {
      background: #fff;
      border-color: #dcd7ff;
      box-shadow: 0 0 0 4px rgba(110, 86, 207, 0.10);
    }

    .full {
      grid-column: 1/-1;
    }

    .hint {
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 900;
      line-height: 1.4;
      margin-top: 4px;
    }

    .profile-top {
      display: flex;
      gap: 12px;
      align-items: center;
    }

    .ava {
      width: 66px;
      height: 66px;
      border-radius: 22px;
      object-fit: cover;
      border: 2px solid #f1f1f6;
      background: #f7f7fb;
    }

    .pn b {
      display: block;
      font-size: 14px;
      font-weight: 1200;
    }

    .pn small {
      display: block;
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 1000;
      margin-top: 3px;
    }

    .minirow {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-top: 8px;
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 10px;
      border-radius: 999px;
      font-size: 10px;
      font-weight: 1100;
      border: 1px solid #f1f1f6;
      background: #fff;
    }

    .b-ok {
      border-color: #dcfce7;
      background: #ecfdf3;
      color: #0f9d58;
    }

    .b-warn {
      border-color: #ffedd5;
      background: #fff7ed;
      color: #c2410c;
    }

    .b-bad {
      border-color: #fee2e2;
      background: #fef2f2;
      color: #b91c1c;
    }

    .b-soft {
      border-color: #eeecff;
      background: #efedfb;
      color: var(--primary);
    }

    .upload {
      border: 1px dashed #e7e7f3;
      background: #fbfbff;
      border-radius: 18px;
      padding: 12px;
      display: flex;
      justify-content: space-between;
      gap: 10px;
      align-items: center;
    }

    .upload b {
      font-size: 12px;
      font-weight: 1100;
    }

    .upload small {
      display: block;
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 900;
      margin-top: 4px;
    }

    .upload input {
      max-width: 220px;
    }

    .switch-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      border: 1px solid #f1f1f6;
      background: #f7f7fb;
      border-radius: 18px;
      padding: 12px;
    }

    .switch-row b {
      font-size: 12px;
      font-weight: 1100;
    }

    .switch-row span {
      display: block;
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 900;
      margin-top: 3px;
    }

    .tog {
      width: 52px;
      height: 30px;
      border-radius: 999px;
      background: #e9e7ff;
      position: relative;
      cursor: pointer;
      border: 1px solid #eeecff;
      flex: none;
    }

    .tog::after {
      content: "";
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      left: 4px;
      width: 22px;
      height: 22px;
      border-radius: 50%;
      background: #fff;
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.10);
      transition: .18s;
    }

    .tog.on {
      background: var(--primary);
    }

    .tog.on::after {
      left: 26px;
    }

    .side-grid {
      display: grid;
      gap: 14px;
    }

    .statCard {
      background: #fff;
      border: 1px solid #f5f5f7;
      border-radius: 22px;
      padding: 14px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.04);
    }

    .ring {
      --p: 48;
      width: 150px;
      height: 150px;
      border-radius: 50%;
      background: conic-gradient(var(--primary) calc(var(--p)*1%), #ecebff 0);
      display: grid;
      place-items: center;
      margin: 10px auto 0;
    }

    .ring .in {
      width: 115px;
      height: 115px;
      border-radius: 50%;
      background: #fff;
      display: grid;
      place-items: center;
      text-align: center;
      border: 1px solid #f1f1f6;
    }

    .ring .in b {
      font-size: 20px;
      font-weight: 1200;
    }

    .ring .in small {
      display: block;
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 1000;
      margin-top: 2px;
    }

    .sidebtn {
      width: 100%;
      border: none;
      border-radius: 16px;
      padding: 12px 14px;
      font-weight: 1100;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .sidebtn.primary {
      background: var(--primary);
      color: #fff;
    }

    .sidebtn.soft {
      background: #efedfb;
      color: var(--primary);
    }

    .sidebtn.dark {
      background: #111;
      color: #fff;
    }

    .rowpill {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      border: 1px solid #f1f1f6;
      background: #f7f7fb;
      border-radius: 16px;
      padding: 12px;
      font-size: 12px;
      font-weight: 1100;
    }

    .rowpill span {
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 900;
    }

    .danger {
      border: 1px solid #fee2e2;
      background: #fef2f2;
      border-radius: 22px;
      padding: 14px;
    }

    .danger h4 {
      margin: 0;
      font-size: 13px;
      font-weight: 1200;
      color: #b91c1c;
      display: flex;
      gap: 8px;
      align-items: center;
    }

    .danger p {
      margin: 8px 0 0;
      font-size: 12px;
      font-weight: 900;
      color: #7f1d1d;
      line-height: 1.45;
    }

    @media(max-width:1200px) {
      .grid-2 {
        grid-template-columns: 1fr;
      }

      .form-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
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
      /* grid-template-columns: 1.4fr 0.6fr; */
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



    .kyc-preview {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-top: 10px;
    }

    .kyc-preview .kyc-thumb {
      width: 64px;
      /* ✅ thumbnail width */
      height: 64px;
      /* ✅ thumbnail height */
      border-radius: 10px;
      object-fit: cover;
      /* ✅ crop nicely */
      border: 1px solid rgba(0, 0, 0, .08);
      background: #f6f7f9;
      flex: 0 0 64px;
    }


    .kyc-preview.sm .kyc-thumb {
      width: 48px;
      height: 48px;
      flex: 0 0 48px;
    }


    .kyc-preview .meta .name {
      font-weight: 600;
      font-size: 13px;
      line-height: 1.2;
      max-width: 260px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .kyc-preview .meta .size {
      font-size: 12px;
      opacity: .7;
    }

    .kyc-preview img.kyc-thumb {
      width: 64px !important;
      height: 64px !important;
      max-width: 64px !important;
      max-height: 64px !important;
    }
  </style>
</head>

<body>
  <div class="app-container">
    <?php $this->load->view('user/layout/v2/user_sidebar'); ?>

    <main class="main-content">
      <?php $this->load->view('user/layout/v2/user_header'); ?>

      <div class="titlebar">
        <div>
          <h2><i class="ph ph-gear"></i> Profile Settings</h2>
          <div class="sub">Manage your account details, KYC, bank info, security and notifications.</div>
        </div>
        <div class="actions">
          <button class="btn-soft" type="button" onclick="location.href='<?= base_url('user/main'); ?>'"><i
              class="ph ph-house"></i> Dashboard</button>
          <button class="btn-main" type="button" onclick="saveActiveTab()"><i class="ph ph-floppy-disk"></i> Save
            Changes</button>
        </div>
      </div>

      <div class="grid-2">
        <!-- LEFT -->
        <section class="card">
          <div class="profile-top">
            <img class="ava" src="<?= htmlspecialchars($avatarUrl); ?>" alt="">
            <div class="pn">
              <b><?= htmlspecialchars($displayName); ?></b>
              <small>
                UID: <?= htmlspecialchars($displayUid); ?>
                • Username: <?= htmlspecialchars($user->username ?: '—'); ?>
                • Rank: <?= htmlspecialchars($displayRank); ?>
              </small>

              <div class="minirow">
                <span class="badge <?= badgeClassPro($kyc->status); ?>"><i class="ph ph-identification-card"></i> KYC:
                  <?= htmlspecialchars($kyc->status); ?></span>
                <span class="badge <?= badgeClassPro($bank->status); ?>"><i class="ph ph-bank"></i> Bank:
                  <?= htmlspecialchars($bank->status); ?></span>
              </div>
            </div>
          </div>

          <div class="tabs" style="margin-top:14px;">
            <button class="tab active" data-tab="profile"><i class="ph ph-user"></i> Profile</button>
            <button class="tab" data-tab="kyc"><i class="ph ph-identification-card"></i> KYC</button>
            <button class="tab" data-tab="bank"><i class="ph ph-bank"></i> Bank</button>
            <button class="tab" data-tab="security"><i class="ph ph-shield-check"></i> Security</button>
            <button class="tab" data-tab="notifications"><i class="ph ph-bell"></i> Notifications</button>
            <button class="tab" data-tab="danger"><i class="ph ph-warning"></i> Danger</button>
          </div>

          <div class="tabpanels">
            <!-- PROFILE TAB -->
            <div class="panel active" id="tab-profile">
              <div class="card-h">
                <h3>Basic Information</h3>
                <span class="chip"><i class="ph ph-pencil-simple"></i> Public profile</span>
              </div>

              <form id="profileForm" method="post" action="<?= site_url('member/profile/profile_update'); ?>"
                enctype="multipart/form-data">

                <input type="hidden" name="<?= $csrfName; ?>" value="<?= $csrfHash; ?>">

                <div class="form-grid">
                  <div class="field">
                    <label><i class="ph ph-user"></i> First Name</label>
                    <input class="inp" name="first_name" value="<?= htmlspecialchars($user->username); ?>"
                      placeholder="Enter first name">
                  </div>
                  <div class="field">
                    <label><i class="ph ph-user"></i> Last Name</label>
                    <input class="inp" name="last_name" value="<?= htmlspecialchars($user->last_name); ?>"
                      placeholder="Enter last name">
                  </div>

                  <div class="field">
                    <label><i class="ph ph-user"></i> Email</label>
                    <input class="inp" name="email" value="<?= htmlspecialchars($user->email); ?>"
                      placeholder="Enter email">
                  </div>

                  <div class="field full">
                    <label><i class="ph ph-phone"></i> Phone</label>
                    <input class="inp" name="contact" value="<?= htmlspecialchars($user->contact); ?>"
                      placeholder="+91...">
                  </div>

                  <div class="field">
                    <label><i class="ph ph-globe"></i> Country</label>
                    <input class="inp" name="country" value="<?= htmlspecialchars($user->country); ?>"
                      placeholder="Country">
                  </div>
                  <div class="field">
                    <label><i class="ph ph-clock"></i> Timezone</label>
                    <input class="inp" name="time_zone" value="<?= htmlspecialchars($user->time_zone); ?>"
                      placeholder="Asia/Kolkata">
                  </div>

                  <div class="field full">
                    <div class="upload">
                      <div>
                        <b><i class="ph ph-image"></i> Update Profile Photo</b>
                        <small>PNG/JPG/WebP up to 2MB. Recommended: 400×400.</small>
                      </div>
                      <input type="file" name="profile_img" accept="image/*">
                    </div>
                  </div>

                  <div class="field full">
                    <button class="btn-main" type="submit"><i class="ph ph-check"></i> Save Profile</button>
                  </div>
                </div>
              </form>
            </div>

            <!-- KYC TAB -->
            <div class="panel" id="tab-kyc">

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

                  <?php
                  $st = strtolower($kyc->status ?? 'none');

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


                <!-- <div class="kyc-grid"> -->
                  <!-- LEFT: Form -->
                  <div class="kyc-card">
                    <div class="section-h">
                      <b>Personal & Document Details</b>
                      <small>* Required fields</small>
                    </div>

                    <form id="kyc_form" action="#" method="post" enctype="multipart/form-data">
                      <!-- keep old uploaded URLs (for JS checks + resubmit UX) -->
                      <input type="hidden" name="prev_doc_front_url"
                        value="<?php echo html_escape($kyc->doc_front_url ?? ''); ?>">
                      <input type="hidden" name="prev_doc_back_url"
                        value="<?php echo html_escape($kyc->doc_back_url ?? ''); ?>">
                      <input type="hidden" name="prev_selfie_url"
                        value="<?php echo html_escape($kyc->selfie_url ?? ''); ?>">

                      <div class="form-grid">

                        <!-- Full Name / DOB / Gender -->
                        <div class="fg col-6">
                          <div class="f-label">Full Name <span class="req-star">*</span></div>
                          <input class="f-input" type="text" name="full_name"
                            value="<?php echo html_escape($kyc->full_name ?? ''); ?>" placeholder="Enter your full name"
                            <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                        </div>

                        <div class="fg col-3">
                          <div class="f-label">Date of Birth <span class="req-star">*</span></div>
                          <input class="f-input" type="date" name="dob"
                            value="<?php echo html_escape($kyc->dob ?? ''); ?>" <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                        </div>

                        <div class="fg col-3">
                          <div class="f-label">Gender</div>
                          <select class="f-select" name="gender" <?php echo !empty($read_only) ? 'disabled' : ''; ?>>
                            <?php $g = $kyc->gender ?? 'unspecified'; ?>
                            <option value="unspecified" <?php echo $g === 'unspecified' ? 'selected' : ''; ?>>Prefer not
                              to say</option>
                            <option value="male" <?php echo $g === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo $g === 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo $g === 'other' ? 'selected' : ''; ?>>Other</option>
                          </select>
                          <?php if (!empty($read_only)): ?>
                            <input type="hidden" name="gender" value="<?php echo html_escape($g); ?>">
                          <?php endif; ?>
                        </div>

                        <!-- Country / Nationality -->
                        <div class="fg col-3">
                          <div class="f-label">Country of Residence <span class="req-star">*</span></div>
                          <select class="f-select" name="country_iso2" <?php echo !empty($read_only) ? 'disabled' : ''; ?>>
                            <?php $c = strtoupper($kyc->country_iso2 ?? 'IN'); ?>
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
                            <?php $n = strtoupper($kyc->nationality_iso2 ?? 'IN'); ?>
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
                            value="<?php echo html_escape($kyc->addr_line1 ?? ''); ?>"
                            placeholder="House / Street / Area" <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                        </div>

                        <div class="fg col-6">
                          <div class="f-label">Address Line 2</div>
                          <input class="f-input" type="text" name="addr_line2"
                            value="<?php echo html_escape($kyc->addr_line2 ?? ''); ?>"
                            placeholder="Apartment / Landmark (optional)" <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                        </div>

                        <div class="fg col-4">
                          <div class="f-label">City <span class="req-star">*</span></div>
                          <input class="f-input" type="text" name="addr_city"
                            value="<?php echo html_escape($kyc->addr_city ?? ''); ?>" placeholder="Enter city" <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                        </div>

                        <div class="fg col-4">
                          <div class="f-label">Region / State</div>
                          <input class="f-input" type="text" name="addr_region"
                            value="<?php echo html_escape($kyc->addr_region ?? ''); ?>" placeholder="Enter state" <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                        </div>

                        <div class="fg col-4">
                          <div class="f-label">Postal Code <span class="req-star">*</span></div>
                          <input class="f-input" type="text" name="addr_postal"
                            value="<?php echo html_escape($kyc->addr_postal ?? ''); ?>" placeholder="Enter postal code"
                            <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                        </div>

                        <!-- Document -->
                        <div class="fg col-4">
                          <div class="f-label">Document Type <span class="req-star">*</span></div>
                          <?php $dt = $kyc->doc_type ?? 'passport'; ?>
                          <select class="f-select" name="doc_type" <?php echo !empty($read_only) ? 'disabled' : ''; ?>>
                            <option value="passport" <?php echo $dt === 'passport' ? 'selected' : ''; ?>>Passport</option>
                            <option value="national_id" <?php echo $dt === 'national_id' ? 'selected' : ''; ?>>National ID
                            </option>
                            <option value="driver_license" <?php echo $dt === 'driver_license' ? 'selected' : ''; ?>>
                              Driver License</option>
                          </select>
                          <?php if (!empty($read_only)): ?>
                            <input type="hidden" name="doc_type" value="<?php echo html_escape($dt); ?>">
                          <?php endif; ?>
                        </div>

                        <div class="fg col-4">
                          <div class="f-label">Document Number <span class="req-star">*</span></div>
                          <input class="f-input" type="text" name="doc_number"
                            value="<?php echo html_escape($kyc->doc_number ?? ''); ?>"
                            placeholder="Enter document number" <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                        </div>

                        <div class="fg col-4">
                          <div class="f-label">Issuing Country <span class="req-star">*</span></div>
                          <?php $ic = strtoupper($kyc->doc_issue_country ?? ($kyc->country_iso2 ?? 'IN')); ?>
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
                            value="<?php echo html_escape($kyc->doc_issue_date ?? ''); ?>" <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                        </div>

                        <div class="fg col-3">
                          <div class="f-label">Expiry</div>
                          <input class="f-input" type="date" name="doc_expiry_date"
                            value="<?php echo html_escape($kyc->doc_expiry_date ?? ''); ?>" <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                        </div>

                        <div class="fg col-6"></div>

                        <!-- Uploads -->
                        <div class="fg col-6 col-md-6">
                          <div class="f-label">ID Front (Image/PDF) <span class="req-star">*</span></div>

                          <div class="js-kyc file-drop" data-input="doc_front">
                            <div class="ic"><i class="ph ph-upload-simple"></i></div>
                            <div>
                              <b>Drag & drop or <a href="javascript:void(0)">browse</a></b>
                              <small>JPG/PNG/WEBP/GIF or PDF • Max 8 MB</small>
                              <?php if (!empty($kyc->doc_front_url)): ?>
                                <div class="mt-1"><a target="_blank"
                                    href="<?php echo html_escape($kyc->doc_front_url); ?>">View uploaded</a></div>
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
                              <?php if (!empty($kyc->doc_back_url)): ?>
                                <div class="mt-1"><a target="_blank"
                                    href="<?php echo html_escape($kyc->doc_back_url); ?>">View uploaded</a></div>
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
                              <?php if (!empty($kyc->selfie_url)): ?>
                                <div class="mt-1"><a target="_blank"
                                    href="<?php echo html_escape($kyc->selfie_url); ?>">View uploaded</a></div>
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
                              <?php if (!empty($kyc->proof_address_url)): ?>
                                <div class="mt-1"><a target="_blank"
                                    href="<?php echo html_escape($kyc->proof_address_url); ?>">View uploaded</a></div>
                              <?php endif; ?>
                            </div>
                            <input type="file" name="proof_address" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf" hidden
                              <?php echo !empty($read_only) ? 'disabled' : ''; ?> />
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
                      <?php $cons = (int) ($kyc->consent ?? 0); ?>
                      <div class="consent">
                        <input type="checkbox" name="consent" id="consent" value="1" <?php echo ($cons === 1) ? 'checked' : ''; ?> <?php echo !empty($read_only) ? 'disabled' : ''; ?> />
                        <label for="consent">I consent to verification & data processing as per platform policy.</label>
                        <?php if (!empty($read_only)): ?>
                          <input type="hidden" name="consent" value="<?php echo $cons ? '1' : '0'; ?>">
                        <?php endif; ?>
                      </div>

                      <div class="actions">
                        <button type="button" class="btn-lite" onclick="history.back()">Cancel</button>

                        <?php if (!empty($read_only)): ?>
                          <button type="button" class="btn-primary2" disabled>
                            <?php echo strtoupper($kyc->status ?? 'PENDING'); ?>
                          </button>
                        <?php else: ?>
                          <button type="submit" id="kyc_submit_btn" class="btn-primary2">Submit KYC</button>
                        <?php endif; ?>
                      </div>
                    </form>
                  </div>
                <!-- </div> -->

              </div>
            </div>


              <!-- BANK TAB -->
              <div class="panel" id="tab-bank">
                <div class="card-h">
                  <h3>Bank Details</h3>
                  <span class="badge <?= badgeClassPro($bank->status); ?>"><i class="ph ph-bank"></i>
                    <?= htmlspecialchars($bank->status); ?></span>
                </div>
                <div class="muted">Bank details are required for withdrawals. Ensure name matches KYC.</div>

                <form id="bankForm" method="post" action="<?= site_url('member/profile/bank_save'); ?>"
                  style="margin-top:12px;">

                  <input type="hidden" name="<?= $csrfName; ?>" value="<?= $csrfHash; ?>">

                  <div class="form-grid">
                    <div class="field">
                      <label>Account Holder Name</label>
                      <input class="inp" name="holder_name" value="<?= htmlspecialchars($bank->holder_name); ?>"
                        placeholder="Holder name">
                    </div>
                    <div class="field">
                      <label>Bank Name</label>
                      <input class="inp" name="bank_name" value="<?= htmlspecialchars($bank->bank_name); ?>"
                        placeholder="Bank name">
                    </div>

                    <div class="field">
                      <label>Account Number</label>
                      <input class="inp" name="account_number" value="<?= htmlspecialchars($bank->account_number); ?>"
                        placeholder="1234...">
                    </div>
                    <div class="field">
                      <label>IFSC</label>
                      <input class="inp" name="ifsc" value="<?= htmlspecialchars($bank->ifsc); ?>"
                        placeholder="SBIN0000000">
                    </div>

                    <div class="field full">
                      <label>UPI ID (optional)</label>
                      <input class="inp" name="upi_id" value="<?= htmlspecialchars($bank->upi_id); ?>"
                        placeholder="name@upi">
                      <?php if (!empty($bank->note)): ?>
                        <div class="hint"><?= htmlspecialchars($bank->note); ?></div>
                      <?php endif; ?>
                    </div>

                    <div class="field full">
                      <button class="btn-main" type="submit"><i class="ph ph-check"></i> Save Bank Details</button>
                    </div>
                  </div>
                </form>
              </div>

              <!-- SECURITY TAB -->
              <div class="panel" id="tab-security">
                <div class="card-h">
                  <h3>Security</h3>
                  <span class="chip"><i class="ph ph-shield-check"></i> Protect your account</span>
                </div>

                <div class="form-grid">
                  <div class="field full">
                    <div class="switch-row">
                      <div>
                        <b>Two-Factor Authentication (2FA)</b>
                        <span>Recommended for withdrawals & profile changes.</span>
                      </div>
                      <div class="tog <?= ((int) $user->twofa_status === 1) ? 'on' : ''; ?>" id="twofaTog"></div>
                    </div>
                    <div class="hint">2FA save API not added here (you can add later).</div>
                  </div>

                  <div class="field full">
                    <div class="upload">
                      <div>
                        <b><i class="ph ph-password"></i> Change Password</b>
                        <small>Use strong password (min 8 chars).</small>
                      </div>
                      <button class="btn-soft" type="button" onclick="openPasswordModal()"><i
                          class="ph ph-lock-key"></i>
                        Update</button>
                    </div>
                  </div>

                  <div class="field full">
                    <div class="upload">
                      <div>
                        <b><i class="ph ph-device-mobile"></i> Logout from all devices</b>
                        <small>If you suspect suspicious login, logout everywhere.</small>
                      </div>
                      <button class="btn-dark" type="button" onclick="logoutAll()"><i class="ph ph-sign-out"></i> Logout
                        All</button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- NOTIFICATIONS TAB -->
              <div class="panel" id="tab-notifications">
                <div class="card-h">
                  <h3>Notifications</h3>
                  <span class="chip"><i class="ph ph-bell"></i> Alerts & updates</span>
                </div>

                <div class="hint">Select what notifications you want to receive.</div>

                <form id="prefsForm" method="post" action="<?= site_url('member/profile/update_email_preferences'); ?>"
                  style="display:grid;gap:10px;margin-top:12px;">
                  <input type="hidden" name="<?= $csrfName; ?>" value="<?= $csrfHash; ?>">

                  <?php
                  $prefItems = [
                    'success_payments' => ['Successful Payments', 'Payment success alerts'],
                    'payouts' => ['Payout Updates', 'Payout status updates'],
                    'product_commission' => ['Product Commission', 'Commission credit alerts'],
                    'refund_alerts' => ['Refund Alerts', 'Refund and reversal alerts'],
                    'invoice_payments' => ['Invoice Payments', 'Invoice payment updates'],
                  ];
                  ?>

                  <?php foreach ($prefItems as $k => $meta): ?>
                    <div class="switch-row">
                      <div>
                        <b><?= htmlspecialchars($meta[0]); ?></b>
                        <span><?= htmlspecialchars($meta[1]); ?></span>
                      </div>
                      <label style="display:flex;align-items:center;gap:10px;">
                        <input type="checkbox" name="pref[<?= $k; ?>]" value="1" <?= !empty($prefs->$k) ? 'checked' : ''; ?>>
                      </label>
                    </div>
                  <?php endforeach; ?>

                  <button class="btn-main" type="submit"><i class="ph ph-check"></i> Save Notification Settings</button>
                </form>
              </div>

              <!-- DANGER TAB -->
              <div class="panel" id="tab-danger">
                <div class="danger">
                  <h4><i class="ph ph-warning"></i> Danger Zone</h4>
                  <p>These actions are irreversible. Proceed carefully.</p>

                  <div style="display:grid;gap:10px;margin-top:12px;">
                    <div class="upload">
                      <div>
                        <b><i class="ph ph-trash"></i> Delete Account</b>
                        <small>Account will be permanently removed after admin approval.</small>
                      </div>
                      <button class="btn-danger" type="button" onclick="requestDelete()"><i
                          class="ph ph-warning-circle"></i> Request Delete</button>
                    </div>

                    <div class="upload">
                      <div>
                        <b><i class="ph ph-shield-warning"></i> Freeze Withdrawals</b>
                        <small>Temporarily block withdrawals for safety.</small>
                      </div>
                      <button class="btn-dark" type="button" onclick="freezeWithdraw()"><i class="ph ph-lock"></i>
                        Freeze</button>
                    </div>

                    <div class="hint">Tip: You can enforce OTP/2FA before danger actions.</div>
                  </div>
                </div>
              </div>
            </div>
        </section>

        <!-- RIGHT -->
        <aside class="side-grid">
          <div class="statCard">
            <div class="card-h">
              <h3>Profile & Stats</h3>
              <button class="btn-soft" type="button" onclick="location.href='<?= base_url('user/profile'); ?>'"><i
                  class="ph ph-sliders-horizontal"></i> Manage</button>
            </div>

            <div class="ring" style="--p:<?= (int) $rankPercent; ?>;">
              <div class="in">
                <b><?= (int) $rankPercent; ?>%</b>
                <small>Rank Progress</small>
              </div>
            </div>

            <div style="margin-top:12px;display:grid;gap:10px;">
              <div class="rowpill">
                <div>
                  <b>Withdraw Eligibility</b>
                  <span>Based on KYC + Bank</span>
                </div>
                <b style="color:<?= ($eligibleText === 'Eligible') ? '#0f9d58' : '#b91c1c'; ?>;">
                  <?= htmlspecialchars($eligibleText); ?>
                </b>
              </div>

              <div class="rowpill">
                <div><b>KYC Status</b><span>Verification progress</span></div>
                <span class="badge <?= badgeClassPro($kyc->status); ?>"><?= htmlspecialchars($kyc->status); ?></span>
              </div>

              <div class="rowpill">
                <div><b>Bank Status</b><span>Withdrawal account</span></div>
                <span class="badge <?= badgeClassPro($bank->status); ?>"><?= htmlspecialchars($bank->status); ?></span>
              </div>

              <button class="sidebtn primary" type="button" onclick="goTab('kyc')"><i
                  class="ph ph-identification-card"></i> Update KYC</button>
              <button class="sidebtn soft" type="button" onclick="goTab('bank')"><i class="ph ph-bank"></i> Update
                Bank</button>
              <button class="sidebtn dark" type="button" onclick="location.href='<?= base_url('user/support'); ?>'"><i
                  class="ph ph-headset"></i> Support Ticket</button>
            </div>
          </div>
        </aside>
      </div>
    </main>
  </div>

  <!-- Password Modal -->
  <div id="pwdModal"
    style="display:none;position:fixed;inset:0;background:rgba(10,10,20,.35);z-index:99999;align-items:center;justify-content:center;padding:14px;">
    <div
      style="width:min(520px,100%);background:#fff;border:1px solid #f5f5f7;border-radius:24px;box-shadow:0 26px 70px rgba(0,0,0,.18);overflow:hidden;">
      <div
        style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #f5f5f7;">
        <b style="font-size:14px;font-weight:1100;">Change Password</b>
        <button class="btn-soft" onclick="closePasswordModal()"><i class="ph ph-x"></i> Close</button>
      </div>
      <div style="padding:14px 16px;">
        <input type="hidden" id="csrfName" value="<?= $csrfName; ?>">
        <input type="hidden" id="csrfHash" value="<?= $csrfHash; ?>">

        <div class="form-grid">
          <div class="field full">
            <label>Current Password</label>
            <input class="inp" type="password" id="curPwd" placeholder="Current password">
          </div>
          <div class="field">
            <label>New Password</label>
            <input class="inp" type="password" id="newPwd" placeholder="New password">
          </div>
          <div class="field">
            <label>Confirm</label>
            <input class="inp" type="password" id="cnfPwd" placeholder="Confirm password">
          </div>
          <div class="field full">
            <button class="btn-main" type="button" onclick="savePassword()"><i class="ph ph-check"></i> Update
              Password</button>
            <div class="hint">Password should be at least 8 characters.</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>
  <script>
    // ---------- Tabs ----------
    const tabs = document.querySelectorAll('.tab');
    const panels = document.querySelectorAll('.panel');

    function setTab(key) {
      tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === key));
      panels.forEach(p => p.classList.toggle('active', p.id === 'tab-' + key));
      localStorage.setItem('fenizo_profile_tab', key);
    }
    tabs.forEach(t => t.addEventListener('click', () => setTab(t.dataset.tab)));

    const last = localStorage.getItem('fenizo_profile_tab');
    if (last) { setTab(last); }

    function goTab(key) {
      setTab(key);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function saveActiveTab() { toastMini("Saved."); }

    // ---------- CSRF helper ----------
    function setCsrfFromResponse(res) {
      if (res && res.csrfName && res.csrfHash) {
        // update hidden inputs in all forms
        document.querySelectorAll('input[name="' + res.csrfName + '"]').forEach(i => i.value = res.csrfHash);

        // also update modal hidden
        const h = document.getElementById('csrfHash');
        const n = document.getElementById('csrfName');
        if (h) h.value = res.csrfHash;
        if (n) n.value = res.csrfName;
      }
    }

    // ---------- AJAX submit helper (FormData) ----------
    async function postForm(formEl) {
      const url = formEl.getAttribute('action');
      const fd = new FormData(formEl);

      const r = await fetch(url, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await r.json().catch(() => null);
      if (!data) throw new Error('Invalid JSON response');
      setCsrfFromResponse(data);
      return data;
    }

    // PROFILE AJAX
    document.getElementById('profileForm')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        const res = await postForm(e.target);
        if (res.status === 'success') { toastMini(res.message || "Profile updated"); }
        else toastMini(res.message || "Profile update failed");
      } catch (err) {
        toastMini(err.message || "Profile update error");
      }
    });

    // KYC AJAX
    document.getElementById('kycForm')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        const res = await postForm(e.target);
        if (res.status === 'success') { toastMini(res.message || "KYC submitted"); }
        else toastMini(res.message || "KYC submit failed");
      } catch (err) {
        toastMini(err.message || "KYC submit error");
      }
    });

    // BANK AJAX
    document.getElementById('bankForm')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        const res = await postForm(e.target);
        if (res.status === 'success') { toastMini(res.message || "Bank saved"); }
        else toastMini(res.message || "Bank save failed");
      } catch (err) {
        toastMini(err.message || "Bank save error");
      }
    });

    // PREFS AJAX
    document.getElementById('prefsForm')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        const res = await postForm(e.target);
        if (res.status === 'success') { toastMini(res.message || "Preferences saved"); }
        else toastMini(res.message || "Preferences save failed");
      } catch (err) {
        toastMini(err.message || "Prefs error");
      }
    });

    // ---------- Password modal ----------
    function openPasswordModal() { document.getElementById('pwdModal').style.display = 'flex'; }
    function closePasswordModal() { document.getElementById('pwdModal').style.display = 'none'; }

    async function savePassword() {
      const cur = document.getElementById('curPwd').value.trim();
      const a = document.getElementById('newPwd').value.trim();
      const b = document.getElementById('cnfPwd').value.trim();
      if (a.length < 8) return toastMini("New password must be 8+ chars");
      if (a !== b) return toastMini("Confirm password mismatch");

      const csrfName = document.getElementById('csrfName').value;
      const csrfHash = document.getElementById('csrfHash').value;

      const fd = new FormData();
      fd.append('currentpassword', cur);
      fd.append('newpassword', a);
      fd.append('confirmpassword', b);
      fd.append(csrfName, csrfHash);

      try {
        const r = await fetch("<?= site_url('member/profile/update_password'); ?>", {
          method: 'POST',
          body: fd,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const res = await r.json();
        setCsrfFromResponse(res);
        if (res.status === 'success') {
          toastMini(res.message || "Password updated");
          closePasswordModal();
          document.getElementById('curPwd').value = '';
          document.getElementById('newPwd').value = '';
          document.getElementById('cnfPwd').value = '';
        } else {
          toastMini(res.message || "Password update failed");
        }
      } catch (err) {
        toastMini(err.message || "Password update error");
      }
    }

    // ---------- Danger actions ----------
    // async function requestDelete() {
    //   const reason = prompt("Reason for delete request?");
    //   if (!reason) return;

    //   const csrfName = document.getElementById('csrfName')?.value || "<?= $csrfName; ?>";
    //   const csrfHash = document.getElementById('csrfHash')?.value || "<?= $csrfHash; ?>";

    //   const fd = new FormData();
    //   fd.append('reason', reason);
    //   fd.append(csrfName, csrfHash);

    //   try {
    //     const r = await fetch("<?= site_url('member/profile/request_delete'); ?>", {
    //       method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
    //     });
    //     const res = await r.json();
    //     setCsrfFromResponse(res);
    //     toastMini(res.message || "Done");
    //   } catch (e) { toastMini("Error"); }
    // }

    // async function freezeWithdraw() {
    //   const reason = prompt("Reason for freezing withdraw?");
    //   if (!reason) return;

    //   const csrfName = document.getElementById('csrfName')?.value || "<?= $csrfName; ?>";
    //   const csrfHash = document.getElementById('csrfHash')?.value || "<?= $csrfHash; ?>";

    //   const fd = new FormData();
    //   fd.append('reason', reason);
    //   fd.append(csrfName, csrfHash);

    //   try {
    //     const r = await fetch("<?= site_url('member/profile/freeze_withdraw'); ?>", {
    //       method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
    //     });
    //     const res = await r.json();
    //     setCsrfFromResponse(res);
    //     toastMini(res.message || "Done");
    //   } catch (e) { toastMini("Error"); }
    // }


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

// ✅ CSRF helper (expects hidden inputs: #csrfName, #csrfHash)
function setCsrfFromResponse(res) {
  if (!res) return;

  const name =
    res.csrfName || res.csrf_name || res?.csrf?.name || document.getElementById("csrfName")?.value;
  const hash =
    res.csrfHash || res.csrf_hash || res?.csrf?.hash || document.getElementById("csrfHash")?.value;

  if (name && document.getElementById("csrfName")) document.getElementById("csrfName").value = name;
  if (hash && document.getElementById("csrfHash")) document.getElementById("csrfHash").value = hash;
}

// ✅ Helper: reusable confirm+reason modal
async function askReason({
  title,
  placeholder,
  confirmText,
  confirmBtnClass = "btn btn-danger",
}) {
  const { value, isConfirmed } = await Swal.fire({
    title: title || "Confirm",
    input: "textarea",
    inputLabel: "Reason",
    inputPlaceholder: placeholder || "Write your reason...",
    inputAttributes: { maxlength: 500 },
    showCancelButton: true,
    confirmButtonText: confirmText || "Submit",
    cancelButtonText: "Cancel",
    buttonsStyling: false,
    customClass: {
      confirmButton: confirmBtnClass,
      cancelButton: "btn btn-secondary",
    },
    inputValidator: (v) => (!v || !v.trim() ? "Reason is required" : undefined),
  });

  if (!isConfirmed) return null;
  return (value || "").trim();
}

// ✅ Helper: POST with CSRF + JSON
async function postWithCsrf(url, reason) {
  const csrfName = document.getElementById("csrfName")?.value || "<?= $csrfName; ?>";
  const csrfHash = document.getElementById("csrfHash")?.value || "<?= $csrfHash; ?>";

  const fd = new FormData();
  fd.append("reason", reason);
  fd.append(csrfName, csrfHash);

  const r = await fetch(url, {
    method: "POST",
    body: fd,
    headers: { "X-Requested-With": "XMLHttpRequest" },
  });

  const res = await r.json().catch(() => ({}));
  setCsrfFromResponse(res);
  return res;
}

// ---------- Danger actions ----------
async function requestDelete() {
  // ✅ DEMO MODE STOP
  if (isDemoMode()) {
    demoBlockAlert("You can not request delete in demo mode.");
    return;
  }

  const reason = await askReason({
    title: "Request Account Delete",
    placeholder: "Why are you requesting account deletion?",
    confirmText: "Submit Request",
    confirmBtnClass: "btn btn-danger",
  });
  if (!reason) return;

  try {
    const res = await postWithCsrf(
      "<?= site_url('member/profile/request_delete'); ?>",
      reason
    );

    if (res?.status === "success" || res?.status === true) {
      Swal.fire({
        icon: "success",
        title: "Submitted",
        text: res.message || "Delete request sent.",
        buttonsStyling: false,
        confirmButtonText: "Ok, got it!",
        customClass: { confirmButton: "btn btn-primary" },
      });
    } else {
      Swal.fire({
        icon: "error",
        title: "Failed",
        text: res.message || "Something went wrong!",
        buttonsStyling: false,
        confirmButtonText: "Ok, got it!",
        customClass: { confirmButton: "btn btn-primary" },
      });
    }
  } catch (e) {
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "Network error. Please try again.",
      buttonsStyling: false,
      confirmButtonText: "Ok, got it!",
      customClass: { confirmButton: "btn btn-primary" },
    });
  }
}

async function freezeWithdraw() {
  // ✅ DEMO MODE STOP
  if (isDemoMode()) {
    demoBlockAlert("You can not freeze withdraw in demo mode.");
    return;
  }

  const reason = await askReason({
    title: "Freeze Withdraw",
    placeholder: "Why do you want to freeze withdraw?",
    confirmText: "Freeze Now",
    confirmBtnClass: "btn btn-warning",
  });
  if (!reason) return;

  try {
    const res = await postWithCsrf(
      "<?= site_url('member/profile/freeze_withdraw'); ?>",
      reason
    );

    if (res?.status === "success" || res?.status === true) {
      Swal.fire({
        icon: "success",
        title: "Updated",
        text: res.message || "Withdraw has been frozen.",
        buttonsStyling: false,
        confirmButtonText: "Ok, got it!",
        customClass: { confirmButton: "btn btn-primary" },
      });
    } else {
      Swal.fire({
        icon: "error",
        title: "Failed",
        text: res.message || "Something went wrong!",
        buttonsStyling: false,
        confirmButtonText: "Ok, got it!",
        customClass: { confirmButton: "btn btn-primary" },
      });
    }
  } catch (e) {
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "Network error. Please try again.",
      buttonsStyling: false,
      confirmButtonText: "Ok, got it!",
      customClass: { confirmButton: "btn btn-primary" },
    });
  }
}

    function logoutAll() { toastMini("Logout all (API not connected)"); }

    // ---------- Toast ----------
    function toastMini(msg) {
      const t = document.createElement('div');
      t.textContent = msg;
      t.style.cssText =
        "position:fixed;bottom:22px;left:50%;transform:translateX(-50%);background:#111;color:#fff;padding:10px 14px;border-radius:14px;font-weight:1100;font-size:12px;z-index:99999;opacity:0;transition:.2s;";
      document.body.appendChild(t);
      requestAnimationFrame(() => t.style.opacity = "1");
      setTimeout(() => { t.style.opacity = "0"; setTimeout(() => t.remove(), 250); }, 1600);
    }
  </script>

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
          try { data = text ? JSON.parse(text) : {}; } catch (e) { data = { status: 'error', message: text || 'Invalid server response' }; }

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
    .d-none {
      display: none !important;
    }

    /* Hide preview by default (even if CSS above sets flex) */
    .kyc-preview.d-none {
      display: none !important;
    }

    /* Prevent empty img showing (when src="") */
    .kyc-preview .kyc-thumb[src=""],
    .kyc-preview .kyc-thumb:not([src]) {
      display: none !important;
    }
  </style>
</body>

</html>