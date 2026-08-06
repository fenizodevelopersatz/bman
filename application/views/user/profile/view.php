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
  'email_verify_status' => 0,
  'gender' => '',
  'dob' => '',
  'address' => '',
  'address_line2' => '',
  'state' => '',
  'zipcode' => '',
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
  'country_iso2' => 'IN',
  'full_name' => '',
  'gender' => 'unspecified',
  'nationality_iso2' => 'IN',
  'addr_line1' => '',
  'addr_line2' => '',
  'addr_city' => '',
  'addr_region' => '',
  'addr_postal' => '',
  'doc_type' => 'passport',
  'doc_number' => '',
  'doc_issue_country' => 'IN',
  'doc_issue_date' => '',
  'doc_expiry_date' => '',
  'doc_front_url' => '',
  'doc_back_url' => '',
  'selfie_url' => '',
  'proof_address_url' => '',
  'consent' => 0,
  'review_notes' => '',
  'reviewer_note' => '',
  'submitted_at' => '',
], $kycArr);

if (empty($kyc->full_name)) {
  $kyc->full_name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? $user->username ?? '');
}
if (empty($kyc->addr_line1)) {
  $kyc->addr_line1 = $user->address ?? '';
}
if (empty($kyc->addr_line2)) {
  $kyc->addr_line2 = $user->address_line2 ?? '';
}
if (empty($kyc->addr_region)) {
  $kyc->addr_region = $user->state ?? '';
}
if (empty($kyc->addr_postal)) {
  $kyc->addr_postal = $user->zipcode ?? '';
}

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

// Mini rank badge — same "fails soft" source as the shared right-panel rank
// card (user_inner_right_panle.php): Memberrank_model::sidebar() reads the
// cached BMAN Rank Achievement System state, not the old pairing engine.
$CI =& get_instance();
$CI->load->model('user/Memberrank_model', 'rp_rank_mini');
$rkMini = $CI->rp_rank_mini->sidebar($user->id);
if (!empty($rkMini['name'])) {
  $displayRank = $rkMini['name'];
}

// Avatar — same local default the header already uses (user_profile_image()
// falls back to default_avatar_url()/assets/default-user.png), instead of an
// external stock-photo service keyed off the member's email.
$avatarUrl = user_profile_image($user->id);

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

function profileUploadUrl($path, $fallbackDir = '')
{
  $path = trim((string) $path);
  if ($path === '') {
    return '';
  }
  // Full URL (possibly with a stale, pre-domain-change host) OR a known
  // uploads/assets path: re-root onto the CURRENT base_url via media_url()
  // so images never break after a domain change.
  if (preg_match('#^https?://#i', $path) || strpos($path, 'uploads/') !== false || strpos($path, 'assets/') !== false) {
    return media_url($path);
  }
  return base_url(ltrim($fallbackDir . $path, '/'));
}

function profileFileName($url)
{
  $path = parse_url((string) $url, PHP_URL_PATH);
  return basename($path ?: (string) $url);
}

function isPreviewImage($url)
{
  return (bool) preg_match('/\.(jpe?g|png|webp|gif)$/i', parse_url((string) $url, PHP_URL_PATH) ?: '');
}

function docTypeLabel($type)
{
  $labels = ['passport' => 'Passport', 'national_id' => 'Aadhaar ID', 'driver_license' => 'Driver License'];
  return $labels[$type] ?? ucwords(str_replace('_', ' ', (string) $type));
}

function kycDisplayStatus($status)
{
  $status = strtolower(trim((string) $status));
  if ($status === '' || $status === 'none') {
    return 'Not submitted';
  }
  if ($status === 'under_review') {
    return 'Under review';
  }
  if ($status === 'resubmitted') {
    return 'Pending';
  }
  if ($status === 'rejected' || $status === 'resubmit') {
    return 'Resubmit required';
  }
  return ucwords(str_replace('_', ' ', $status));
}

function renderExistingPreview($url, $title)
{
  $url = profileUploadUrl($url);
  if ($url === '') {
    return;
  }
  $name = profileFileName($url);
  ?>
  <div class="kyc-preview existing-preview">
    <?php if (isPreviewImage($url)): ?>
      <img class="kyc-thumb" src="<?php echo html_escape($url); ?>" alt="<?php echo html_escape($title); ?>">
    <?php else: ?>
      <div class="kyc-thumb file-thumb"><i class="ph ph-file-pdf"></i></div>
    <?php endif; ?>
    <div class="meta">
      <div class="name"><?php echo html_escape($title); ?></div>
      <div class="size"><?php echo html_escape($name); ?></div>
      <a class="js-view" target="_blank" href="<?php echo html_escape($url); ?>">View uploaded</a>
    </div>
  </div>
  <?php
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php $this->load->view('user/layout/v2/user_style'); ?>

  <style>
    /* ===== SHARED BREAKPOINT SCALE — see assets/user_v2/css/style.css =====
       1400 xxl · 1200 xl · 1024 lg (must match user_sidebar.php JS) · 768 md · 600 sm · 380 xs
       ===================================================================== */
    /* (your existing styles - kept as-is) */
    .titlebar {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      gap: 12px;
      margin: 8px 0 16px;
      flex-wrap: wrap;
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

    .btn-warn2 {
      border: none;
      background: #f59e0b;
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

    /* .form-grid is redefined below (KYC PAGE style block) as a 12-column grid
       that wins by source order — this 2-column declaration is dead. Plain
       .field elements (this tab, the password modal) get their span from the
       ".form-grid > .field" rule added next to that later definition. */

    .profile-grid {
      grid-template-columns: repeat(12, minmax(0, 1fr));
      align-items: start;
    }

    .profile-grid .field {
      grid-column: span 4;
    }

    .profile-grid .field.span-6 {
      grid-column: span 6;
    }

    .profile-grid .field.span-12 {
      grid-column: 1/-1;
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
      flex-wrap: wrap;
    }

    .pn {
      min-width: 0;
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

    .upload-preview {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-top: 10px;
      padding: 10px;
      border: 1px solid #f1f1f6;
      background: #fff;
      border-radius: 16px;
    }

    .upload-preview img {
      width: 72px;
      height: 72px;
      border-radius: 18px;
      object-fit: cover;
      border: 1px solid #efedf7;
      background: #f7f7fb;
      flex: 0 0 72px;
    }

    .upload-preview b {
      display: block;
      font-size: 12px;
      font-weight: 1100;
      color: #111;
    }

    .upload-preview a,
    .upload-preview small {
      display: inline-block;
      margin-top: 4px;
      font-size: 11px;
      font-weight: 900;
      color: var(--primary);
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
      flex-wrap: wrap;
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

    .status-pill small {
      display: block;
      margin-top: 2px;
      font-size: 10px;
      color: inherit;
      opacity: .72;
      font-weight: 900;
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

    /* Plain .field children (Profile tab, password modal) don't use the KYC
       form's .fg/.col-* span classes, so give them a sensible default half-width.
       :not(.full) matters here, not just documents intent — .field.full is a
       single class (specificity 0,1,0), which this 2-class compound selector
       would otherwise beat regardless of source order, silently halving every
       "full width" field instead of letting .full's 1/-1 apply. */
    .form-grid > .field:not(.full) {
      grid-column: span 6;
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

    .date-field-wrap {
      position: relative;
    }

    .date-field-wrap .f-input {
      padding-right: 64px;
    }

    .date-field-wrap .date-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 26px;
      height: 26px;
      border: none;
      background: transparent;
      border-radius: 8px;
      display: grid;
      place-items: center;
      cursor: pointer;
      color: var(--text-muted);
      font-size: 15px;
    }

    .date-field-wrap .date-btn:hover {
      background: #efedfb;
      color: var(--primary);
    }

    .date-field-wrap .date-btn-pick {
      right: 34px;
    }

    .date-field-wrap .date-btn-clear {
      right: 6px;
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
    /* .kyc-grid's own @media(max-width:1200px) fold removed — that class is
       never applied to any element (the wrapping <div class="kyc-grid"> is
       HTML-commented-out above), so the rule was always a no-op. */
    @media(max-width: 768px) {
      .col-6 {
        grid-column: span 12;
      }

      .col-4 {
        grid-column: span 12;
      }

      .col-3 {
        grid-column: span 12;
      }

      /* Login/New/Confirm currently share a hardcoded 3-column inline grid
         (Security tab, #transferPwForm) — !important since only an inline
         style itself otherwise beats another inline style. */
      #transferPwForm {
        grid-template-columns: 1fr !important;
      }
    }

    @media(max-width: 600px) {
      .upload {
        align-items: flex-start;
        flex-direction: column;
      }

      /* Stacks the Profile tab's fields and the password modal's New/Confirm
         pair — must come after the 12-column .form-grid definition above to
         actually win (media queries don't add specificity or reorder rules). */
      .form-grid {
        grid-template-columns: 1fr;
      }

      /* Without this, the child's still-active "span 6" would force Grid to
         auto-generate extra implicit columns instead of actually stacking. */
      .form-grid > .field {
        grid-column: 1/-1;
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
              <small style="display:inline-flex;align-items:center;gap:5px;flex-wrap:wrap;">
                UID: <?= htmlspecialchars($displayUid); ?>
                • Username: <?= htmlspecialchars($user->username ?: '—'); ?>
                • Rank:
                <?php if (!empty($rkMini['badge_image'])): ?>
                  <?= rank_badge_html($rkMini['badge_image'], $rkMini['badge_color'] ?? null, 20); ?>
                <?php endif; ?>
                <?= htmlspecialchars($displayRank); ?>
              </small>

              <div class="minirow">
                <span class="badge <?= badgeClassPro($kyc->status); ?>"><i class="ph ph-identification-card"></i> KYC:
                  <?= htmlspecialchars($kyc->status); ?></span>                
              </div>
            </div>
          </div>

          <div class="tabs" style="margin-top:14px;">
            <button class="tab active" data-tab="profile"><i class="ph ph-user"></i> Profile</button>
            <button class="tab" data-tab="kyc"><i class="ph ph-identification-card"></i> KYC</button>
            <button class="tab" data-tab="bank"><i class="ph ph-wallet"></i> Wallet</button>
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
                    <label><i class="ph ph-user"></i> First Name <span class="req-star">*</span></label>
                    <input class="inp" name="first_name" value="<?= htmlspecialchars($user->first_name); ?>"
                      placeholder="Enter first name" required>
                  </div>
                  <div class="field">
                    <label><i class="ph ph-user"></i> Last Name <span class="req-star">*</span></label>
                    <input class="inp" name="last_name" value="<?= htmlspecialchars($user->last_name); ?>"
                      placeholder="Enter last name" required>
                  </div>

                  <div class="field">
                    <label><i class="ph ph-user"></i> Email</label>
                    <input class="inp" type="email" name="email" value="<?= htmlspecialchars($user->email); ?>"
                      placeholder="Enter email">
                  </div>

                  <div class="field full">
                    <label><i class="ph ph-phone"></i> Phone <span class="req-star">*</span></label>
                    <input class="inp" type="tel" name="contact" value="<?= htmlspecialchars($user->contact); ?>"
                      placeholder="+91..." required minlength="7" maxlength="15"
                      pattern="[0-9+\-\s()]{7,15}" title="Digits only, 7-15 characters (+, -, spaces and () allowed)">
                  </div>

                  <div class="field">
                    <label><i class="ph ph-globe"></i> Country <span class="req-star">*</span></label>
                    <input class="inp" name="country" value="<?= htmlspecialchars($user->country); ?>"
                      placeholder="Country" required>
                  </div>
                  <div class="field">
                    <label><i class="ph ph-clock"></i> Timezone <span class="req-star">*</span></label>
                    <input class="inp" name="time_zone" value="<?= htmlspecialchars($user->time_zone); ?>"
                      placeholder="Asia/Kolkata" required>
                  </div>

                  <div class="field">
                    <label><i class="ph ph-gender-intersex"></i> Gender</label>
                    <?php $ug = strtolower($user->gender ?? ''); ?>
                    <select class="inp" name="gender">
                      <option value="" <?= $ug === '' ? 'selected' : ''; ?>>Select gender</option>
                      <option value="male" <?= $ug === 'male' ? 'selected' : ''; ?>>Male</option>
                      <option value="female" <?= $ug === 'female' ? 'selected' : ''; ?>>Female</option>
                      <option value="other" <?= $ug === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                  </div>
                  <div class="field">
                    <label><i class="ph ph-cake"></i> Date of Birth</label>
                    <input class="inp" type="date" name="dob" value="<?= htmlspecialchars($user->dob ?? ''); ?>">
                  </div>

                  <div class="field full">
                    <label><i class="ph ph-map-pin"></i> Address Line 1</label>
                    <input class="inp" name="address" value="<?= htmlspecialchars($user->address ?? ''); ?>"
                      placeholder="House / street">
                  </div>
                  <div class="field full">
                    <label><i class="ph ph-map-pin"></i> Address Line 2</label>
                    <input class="inp" name="address_line2" value="<?= htmlspecialchars($user->address_line2 ?? ''); ?>"
                      placeholder="Area / landmark (optional)">
                  </div>

                  <div class="field">
                    <label><i class="ph ph-map-trifold"></i> State</label>
                    <input class="inp" name="state" value="<?= htmlspecialchars($user->state ?? ''); ?>"
                      placeholder="State">
                  </div>
                  <div class="field">
                    <label><i class="ph ph-mailbox"></i> Pin Code</label>
                    <input class="inp" name="zipcode" value="<?= htmlspecialchars($user->zipcode ?? ''); ?>"
                      placeholder="Pin / ZIP code" pattern="[A-Za-z0-9 \-]{3,12}"
                      title="3-12 characters: letters, numbers, spaces and - only">
                  </div>

                  <div class="field full">
                    <div class="upload">
                      <div>
                        <b><i class="ph ph-image"></i> Update Profile Photo</b>
                        <small>PNG/JPG/WebP up to 2MB. Recommended: 400×400.</small>
                      </div>
                      <input type="file" id="profileImgInput" name="profile_img"
                        accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="upload-preview" id="profileImgPreview"
                      <?= empty($user->profile_img) ? 'style="display:none;"' : ''; ?>>
                      <img id="profileImgPreviewThumb"
                        src="<?= !empty($user->profile_img) ? htmlspecialchars($avatarUrl) : ''; ?>"
                        alt="Profile photo">
                      <div>
                        <b id="profileImgPreviewLabel"><?= !empty($user->profile_img) ? 'Current photo' : ''; ?></b>
                        <small id="profileImgPreviewHint"><?= !empty($user->profile_img) ? htmlspecialchars($user->profile_img) : ''; ?></small>
                      </div>
                    </div>
                    <div class="hint" id="profileImgError" style="display:none;color:#b91c1c;"></div>
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
                      <div id="kyc_error_summary" class="alert alert-danger d-none" role="alert" style="margin-bottom:16px;">
                        <b>Please correct the following errors:</b>
                        <ul class="mb-0 mt-2" id="kyc_error_list"></ul>
                      </div>
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
                            <option value="national_id" <?php echo $dt === 'national_id' ? 'selected' : ''; ?>>Aadhaar Id
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
                          <div class="f-label">Issued <small style="font-weight:600;color:var(--text-muted);">(optional)</small></div>
                          <div class="date-field-wrap">
                            <input class="f-input date-clearable" type="date" name="doc_issue_date"
                              value="<?php echo html_escape($kyc->doc_issue_date ?? ''); ?>" <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                            <button type="button" class="date-btn date-btn-pick" aria-label="Open calendar"><i class="ph ph-calendar"></i></button>
                            <button type="button" class="date-btn date-btn-clear" aria-label="Clear date"><i class="ph ph-x"></i></button>
                          </div>
                        </div>

                        <div class="fg col-3">
                          <div class="f-label">Expiry <small style="font-weight:600;color:var(--text-muted);">(optional)</small></div>
                          <div class="date-field-wrap">
                            <input class="f-input date-clearable" type="date" name="doc_expiry_date"
                              value="<?php echo html_escape($kyc->doc_expiry_date ?? ''); ?>" <?php echo !empty($read_only) ? 'readonly' : ''; ?> />
                            <button type="button" class="date-btn date-btn-pick" aria-label="Open calendar"><i class="ph ph-calendar"></i></button>
                            <button type="button" class="date-btn date-btn-clear" aria-label="Clear date"><i class="ph ph-x"></i></button>
                          </div>
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
                          <?php renderExistingPreview($kyc->doc_front_url ?? '', 'ID Front'); ?>
                        </div>

                        <div class="fg col-6 col-md-6">
                          <div class="f-label">ID Back (Image)</div>

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
                            <input type="file" name="doc_back" accept=".jpg,.jpeg,.png,.webp,.gif" hidden <?php echo !empty($read_only) ? 'disabled' : ''; ?> />
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
                          <?php renderExistingPreview($kyc->doc_back_url ?? '', 'ID Back'); ?>
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
                          <?php renderExistingPreview($kyc->selfie_url ?? '', 'Selfie'); ?>
                        </div>

                        <?php /* Proof of Address option removed — only Front, Back & Selfie are required. */ ?>

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

                      <?php
                        $kycStatus = strtolower(trim((string) ($kyc->status ?? 'none')));
                        $reviewNote = trim((string) ($kyc->review_notes ?? ''));
                      ?>
                      <?php if ($reviewNote !== '' && in_array($kycStatus, ['rejected', 'resubmit', 'under_review', 'resubmitted'], true)): ?>
                        <div class="danger" style="margin-top:12px;">
                          <h4><i class="ph ph-note-pencil"></i> Admin Note</h4>
                          <p><?php echo html_escape($reviewNote); ?></p>
                        </div>
                      <?php endif; ?>

                      <div class="actions">
                        <?php
                          $currentKycStatus = strtolower(trim((string) ($kyc->status ?? 'none')));
                          $canSubmitKyc = in_array($currentKycStatus, ['none', 'pending', 'rejected', 'resubmit', 'resubmitted'], true);
                          $submitLabel = 'Submit KYC';
                          if ($currentKycStatus === 'none') {
                            $submitLabel = 'Submit';
                          } elseif (in_array($currentKycStatus, ['rejected', 'resubmit', 'resubmitted'], true)) {
                            $submitLabel = 'Resubmit KYC';
                          }
                        ?>
                        <?php if ($currentKycStatus !== 'approved'): ?>
                          <button type="button" class="btn-lite" onclick="history.back()">Cancel</button>

                          <?php if ($canSubmitKyc): ?>
                            <button type="submit" id="kyc_submit_btn" class="btn-primary2"><?php echo $submitLabel; ?></button>
                          <?php else: ?>
                            <button type="button" class="btn-primary2" disabled>
                              <?php echo strtoupper($kyc->status ?? 'PENDING'); ?>
                            </button>
                          <?php endif; ?>
                        <?php endif; ?>
                      </div>
                    </form>
                  </div>
                <!-- </div> -->

              </div>
            </div>


              <!-- BANK TAB -->
              <div class="panel" id="tab-bank">
                <!-- <div class="card-h">
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
                </form> -->

                <!-- ===================== CUSTODIAL WALLET (proposal §3) ===================== -->
                <?php
                $fw = isset($five_wallets) ? $five_wallets : ['usdt'=>0,'exchange'=>0,'earning'=>0,'staking'=>0,'bonus'=>0];
                $fmt = function ($v) { return number_format((float) $v, 2, '.', ','); };
                $waddr = (!empty($wallet['wallet_address'])) ? $wallet['wallet_address'] : '';
                // Re-root stored QR URL onto the current domain (base_url baked
                // in at creation time breaks after a domain change).
                $wqr = qr_public_url($wallet['wallet_qrimage'] ?? '');
                ?>
                <style>
                  .wl-wrap{margin-top:26px}
                  .wl-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin:12px 0 20px}
                  .wl-card{border:1px solid #e6e8ef;border-radius:12px;padding:12px 14px;background:#fff}
                  .wl-card b{display:block;font-size:1.15rem}
                  .wl-card small{color:#7a7f9a;text-transform:uppercase;font-size:.68rem;letter-spacing:.03em}
                  .wl-dep{display:flex;gap:18px;flex-wrap:wrap;align-items:center;border:1px dashed #c9cee0;border-radius:12px;padding:16px;background:#fafbff}
                  .wl-dep img{width:132px;height:132px;border-radius:10px;border:1px solid #e6e8ef;background:#fff}
                  .wl-addr{font-family:monospace;word-break:break-all;background:#fff;border:1px solid #e6e8ef;border-radius:8px;padding:8px 10px}
                  .wl-badge{padding:2px 8px;border-radius:20px;font-size:.68rem;font-weight:600}
                  .wl-ok{background:#e7f9ef;color:#149a55}.wl-warn{background:#fff4e0;color:#b5730a}.wl-mut{background:#eef0f6;color:#7a7f9a}
                  .wl-diff-box{margin-top:12px}
                </style>

                <div class="wl-wrap">
                  <div class="card-h"><h3><i class="ph ph-wallet"></i> Wallet & Deposit Address</h3></div>
                  <div class="muted">Your 5 platform wallets and your unique on-chain deposit address (BEP-20). Send USDT to this address to fund your account.</div>

                  <!-- 5 wallet balances -->
                  <div class="wl-cards">
                    <div class="wl-card"><small>USDT Wallet (IN/OUT)</small><b><?= $fmt($fw['usdt']); ?></b></div>
                    <div class="wl-card"><small>Exchange Wallet</small><b><?= $fmt($fw['exchange']); ?></b></div>
                    <div class="wl-card"><small>Earning Wallet</small><b><?= $fmt($fw['earning']); ?></b></div>
                    <div class="wl-card"><small>Staking Wallet</small><b><?= $fmt($fw['staking']); ?></b></div>
                    <div class="wl-card"><small>Bonus Wallet</small><b><?= $fmt($fw['bonus']); ?></b></div>
                  </div>

                  <!-- Deposit address + QR + copy -->
                  <?php if ($waddr): ?>
                  <div class="wl-dep">
                    <div id="wlQr" data-addr="<?= htmlspecialchars($waddr); ?>" data-fallback="<?= htmlspecialchars($wqr); ?>"
                         title="Scan to deposit USDT (BEP-20)"
                         style="width:132px;height:132px;border-radius:10px;border:1px solid #e6e8ef;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden"></div>
                    <div style="flex:1;min-width:220px">
                      <small class="muted">Your USDT / BMAN deposit address (BEP-20) · Network: BSC (BEP20)</small>
                      <div class="wl-addr" id="wlAddr"><?= htmlspecialchars($waddr); ?></div>
                      <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
                        <button type="button" class="btn-main" id="wlCopy"><i class="ph ph-copy"></i> Copy Address</button>
                        <button type="button" class="btn-dark" id="wlCheck"><i class="ph ph-arrows-clockwise"></i> Check On-chain Balance</button>
                      </div>
                      <div class="wl-diff-box" id="wlDiff"></div>
                    </div>
                  </div>
                  <?php else: ?>
                  <div class="wl-dep"><div class="muted">A deposit address will appear here shortly. Refresh the page.</div></div>
                  <?php endif; ?>
                </div>

                <script src="<?= base_url('assets/js/vendor/qrcode.min.js'); ?>"></script>
                <script>
                (function () {
                  // Runtime QR: render the deposit address to a QR in-browser
                  // (no dependency on a pre-generated PNG). Server PNG is fallback.
                  var qrBox = document.getElementById('wlQr');
                  if (qrBox && qrBox.dataset.addr) {
                    try {
                      new QRCode(qrBox, { text: qrBox.dataset.addr, width: 128, height: 128,
                        correctLevel: (window.QRCode && QRCode.CorrectLevel ? QRCode.CorrectLevel.M : 0) });
                    } catch (e) {
                      if (qrBox.dataset.fallback) qrBox.innerHTML =
                        '<img src="'+qrBox.dataset.fallback+'" style="width:100%;height:100%" alt="QR">';
                    }
                  }
                  var addrEl = document.getElementById('wlAddr');
                  var copyBtn = document.getElementById('wlCopy');
                  var checkBtn = document.getElementById('wlCheck');
                  var diffBox = document.getElementById('wlDiff');
                  if (copyBtn) copyBtn.addEventListener('click', async function () {
                    var txt = addrEl.textContent.trim();
                    try { await navigator.clipboard.writeText(txt); copyBtn.innerHTML = '<i class="ph ph-check"></i> Copied'; }
                    catch (e) { 
                      // prompt('Copy your address:', txt);
                     }
                    setTimeout(function(){ copyBtn.innerHTML = '<i class="ph ph-copy"></i> Copy Address'; }, 1500);
                  });
                  if (checkBtn) checkBtn.addEventListener('click', async function () {
                    checkBtn.disabled = true; checkBtn.innerHTML = '<i class="ph ph-spinner"></i> Checking…';
                    diffBox.innerHTML = '';
                    try {
                      var res = await fetch("<?= site_url('member/profile/wallet_check'); ?>", {
                        method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: new URLSearchParams({ '<?= $csrfName; ?>': '<?= $csrfHash; ?>' })
                      });
                      var j = await res.json();
                      if (j.status === 'success') {
                        var d = j.data;
                        var pending = parseFloat(d.difference) > 0;
                        diffBox.innerHTML =
                          '<div style="font-size:.85rem;line-height:1.7">' +
                          'On-chain USDT: <b>' + d.onchain_usdt + '</b> · BNB: <b>' + d.onchain_bnb + '</b><br>' +
                          'Our records: <b>' + d.db_usdt + '</b> USDT · Difference: <b>' + d.difference + '</b> ' +
                          (pending ? '<span class="wl-badge wl-warn">NEW DEPOSIT PENDING — admin will credit</span>'
                                   : '<span class="wl-badge wl-ok">IN SYNC</span>') +
                          '</div>';
                      } else {
                        diffBox.innerHTML = '<span class="wl-badge wl-warn">' + (j.message || 'Check failed') + '</span>';
                      }
                    } catch (e) { diffBox.innerHTML = '<span class="wl-badge wl-warn">Network error</span>'; }
                    checkBtn.disabled = false; checkBtn.innerHTML = '<i class="ph ph-arrows-clockwise"></i> Check On-chain Balance';
                  });
                })();
                </script>
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
                        <span>Requires a one-time code at login. Recommended for withdrawals & profile changes.</span>
                      </div>
                      <div class="tog <?= ((int) $user->twofa_status === 1) ? 'on' : ''; ?>" id="twofaTog"></div>
                    </div>
                    <div class="hint" id="twofaTog_msg"></div>
                  </div>

                  <div class="field full">
                    <div class="switch-row">
                      <div>
                        <b>Email Verification</b>
                        <span>Emails a one-time code you must enter at login.</span>
                      </div>
                      <div class="tog <?= ((int) $user->email_verify_status === 1) ? 'on' : ''; ?>" id="emailVerifyTog"></div>
                    </div>
                    <div class="hint" id="emailVerifyTog_msg"></div>
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

                  <!-- Transfer Password — required before an internal wallet transfer -->
                  <div class="field full">
                    <div class="upload" style="align-items:flex-start;flex-direction:column;gap:12px">
                      <div>
                        <b><i class="ph ph-key"></i> Transfer Password
                          <?php if (!empty($user->transfer_password)): ?>
                            <span class="badge badgeClassPro" style="background:#e7f9ef;color:#149a55;padding:2px 8px;border-radius:20px;font-size:11px">SET</span>
                          <?php else: ?>
                            <span class="badge" style="background:#fff4e0;color:#b5730a;padding:2px 8px;border-radius:20px;font-size:11px">NOT SET</span>
                          <?php endif; ?>
                        </b>
                        <small>A separate PIN used to authorize <b>internal wallet transfers</b> (different from your login password).</small>
                      </div>
                      <form id="transferPwForm" style="width:100%;display:grid;grid-template-columns:repeat(3,1fr);gap:10px" onsubmit="return false;">
                        <input type="hidden" name="<?= $csrfName; ?>" value="<?= $csrfHash; ?>">
                        <input class="inp" type="password" id="tpw_login" placeholder="Login password" autocomplete="current-password">
                        <input class="inp" type="password" id="tpw_new" placeholder="New transfer password (min 6, strong)" autocomplete="new-password">
                        <input class="inp" type="password" id="tpw_confirm" placeholder="Confirm" autocomplete="new-password">
                      </form>
                      <button class="btn-main" type="button" onclick="saveTransferPassword()"><i class="ph ph-check"></i>
                        <?= !empty($user->transfer_password) ? 'Update Transfer Password' : 'Set Transfer Password'; ?></button>
                      <div class="hint" id="tpw_msg"></div>
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
                        <b><i class="ph ph-shield-warning"></i> Freeze Account</b>
                        <small>Immediately restricts your account and logs you out everywhere. Contact support to unfreeze.</small>
                      </div>
                      <button class="btn-warn2" type="button" onclick="freezeWithdraw()"><i class="ph ph-lock"></i>
                        Freeze</button>
                    </div>

                    <div class="hint">Both actions require you to confirm a one-time code sent to your registered email before they take effect.</div>
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

            <?php
              $kycPercent = 0;
              if (isset($kyc->status)) {
                if ($kyc->status === 'approved') $kycPercent = 100;
                elseif (in_array($kyc->status, ['under_review', 'pending'])) $kycPercent = 45;
                else $kycPercent = 0;
              }
            ?>

            <div class="ring" style="--p:<?= (int) $kycPercent; ?>;">
              <div class="in">
                <b><?= (int) $kycPercent; ?>%</b>
                <small>KYC Status</small>
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


              <button class="sidebtn primary" type="button" onclick="goTab('kyc')"><i
                  class="ph ph-identification-card"></i> Update KYC</button>
              <button class="sidebtn soft" type="button" onclick="goTab('bank')"><i class="ph ph-wallet"></i> Update
                Wallet</button>
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

  <!-- 2FA Setup Modal -->
  <div id="twofa2Modal"
    style="display:none;position:fixed;inset:0;background:rgba(10,10,20,.35);z-index:99999;align-items:center;justify-content:center;padding:14px;">
    <div
      style="width:min(480px,100%);background:#fff;border:1px solid #f5f5f7;border-radius:24px;box-shadow:0 26px 70px rgba(0,0,0,.18);overflow:hidden;">
      <div
        style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #f5f5f7;">
        <b style="font-size:14px;font-weight:1100;">Setup 2FA Authentication</b>
        <button class="btn-soft" onclick="close2FAModal()"><i class="ph ph-x"></i> Close</button>
      </div>
      <div style="padding:24px 16px;text-align:center;">
        <div id="twofa_step1" style="display:none;">
          <p style="margin:0 0 16px;color:#555;font-size:13px;">Scan this QR code with Google Authenticator</p>
          <div id="twofa_qr" style="background:#f5f5f7;padding:16px;border-radius:12px;margin-bottom:16px;display:flex;justify-content:center;align-items:center;"></div>
          <p style="margin:0 0 12px;color:#999;font-size:12px;">Or enter manually:</p>
          <code id="twofa_secret" style="background:#f5f5f7;padding:8px 12px;border-radius:8px;font-size:12px;display:inline-block;word-break:break-all;"></code>
          <div style="margin-top:20px;border-top:1px solid #f5f5f7;padding-top:16px;">
            <label style="font-size:13px;margin-bottom:8px;display:block;">Enter 6-digit code from your authenticator:</label>
            <input class="inp" type="text" id="twofa_code" placeholder="000000" maxlength="6" inputmode="numeric" style="text-align:center;font-size:18px;letter-spacing:4px;">
            <div class="hint" id="twofa_code_msg"></div>
          </div>
        </div>
        <div id="twofa_loading" style="display:none;text-align:center;">
          <p>Generating QR code...</p>
        </div>
        <div style="margin-top:20px;display:flex;gap:10px;justify-content:center;">
          <button class="btn-main" type="button" id="twofa_verify_btn" onclick="verify2FA()" style="display:none;"><i class="ph ph-check"></i> Verify & Save</button>
          <button class="btn-soft" type="button" onclick="close2FAModal()"><i class="ph ph-x"></i> Cancel</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>
  <script>
    // ---------- Tabs ----------
    const tabs = document.querySelectorAll('.tab');
    const panels = document.querySelectorAll('.panel');

    function setTab(key) {
      tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === key));
      panels.forEach(p => p.classList.toggle('active', p.id === 'tab-' + key));
      localStorage.setItem('nexman_profile_tab', key);
    }
    tabs.forEach(t => t.addEventListener('click', () => setTab(t.dataset.tab)));

    const last = localStorage.getItem('nexman_profile_tab');
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

    // KYC: calendar-open + clear buttons for optional date fields
    document.querySelectorAll('.date-field-wrap').forEach((wrap) => {
      const input = wrap.querySelector('input[type="date"]');
      const pickBtn = wrap.querySelector('.date-btn-pick');
      const clearBtn = wrap.querySelector('.date-btn-clear');
      if (!input) return;

      pickBtn?.addEventListener('click', () => {
        if (input.readOnly || input.disabled) return;
        if (typeof input.showPicker === 'function') {
          try { input.showPicker(); } catch (e) { input.focus(); }
        } else {
          input.focus();
        }
      });

      clearBtn?.addEventListener('click', () => {
        if (input.readOnly || input.disabled) return;
        input.value = '';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });
    });

    // PROFILE: image preview (shown immediately on pick, before upload)
    (function () {
      const input = document.getElementById('profileImgInput');
      const preview = document.getElementById('profileImgPreview');
      const thumb = document.getElementById('profileImgPreviewThumb');
      const label = document.getElementById('profileImgPreviewLabel');
      const hint = document.getElementById('profileImgPreviewHint');
      const errorBox = document.getElementById('profileImgError');
      if (!input) return;

      const MAX_BYTES = 2 * 1024 * 1024; // 2MB, matches server config
      const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];

      input.addEventListener('change', () => {
        errorBox.style.display = 'none';
        const file = input.files && input.files[0];
        if (!file) return;

        if (!ALLOWED.includes(file.type)) {
          errorBox.textContent = 'Only PNG, JPG or WebP images are allowed.';
          errorBox.style.display = 'block';
          input.value = '';
          return;
        }
        if (file.size > MAX_BYTES) {
          errorBox.textContent = 'Image must be 2MB or smaller.';
          errorBox.style.display = 'block';
          input.value = '';
          return;
        }

        const reader = new FileReader();
        reader.onload = () => {
          thumb.src = reader.result;
          label.textContent = 'Selected photo (not yet saved)';
          hint.textContent = file.name;
          preview.style.display = 'flex';
        };
        reader.readAsDataURL(file);
      });
    })();

    // PROFILE AJAX
    document.getElementById('profileForm')?.addEventListener('submit', async (e) => {
      e.preventDefault();

      // Basic frontend validation (required fields, phone/zip format, email
      // format) via the browser's native constraint validation — surfaces
      // the same inline bubble the user already sees on other forms.
      if (!e.target.checkValidity()) {
        e.target.reportValidity();
        return;
      }

      try {
        const res = await postForm(e.target);
        if (res.status === 'success') {
          Swal.fire({
            icon: "success",
            title: "Profile Updated",
            text: res.message || "Your profile has been updated.",
            buttonsStyling: false,
            confirmButtonText: "Ok, got it!",
            customClass: { confirmButton: "btn btn-primary" },
          }).then(() => location.reload());
        }
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

// ✅ Helper: POST with CSRF + JSON (+ optional extra fields, e.g. otp)
async function postWithCsrf(url, reason, extraFields) {
  const csrfName = document.getElementById("csrfName")?.value || "<?= $csrfName; ?>";
  const csrfHash = document.getElementById("csrfHash")?.value || "<?= $csrfHash; ?>";

  const fd = new FormData();
  fd.append("reason", reason);
  fd.append(csrfName, csrfHash);
  if (extraFields) {
    Object.keys(extraFields).forEach((k) => fd.append(k, extraFields[k]));
  }

  const r = await fetch(url, {
    method: "POST",
    body: fd,
    headers: { "X-Requested-With": "XMLHttpRequest" },
  });

  const res = await r.json().catch(() => ({}));
  setCsrfFromResponse(res);
  return res;
}

// ✅ Helper: email OTP gate for Danger-zone actions (Freeze / Delete). Sends
// a code to the account's own registered email, then prompts for it. Returns
// the entered code, or null if sending failed / the user cancelled.
async function askDangerOtp() {
  const csrfName = document.getElementById("csrfName")?.value || "<?= $csrfName; ?>";
  const csrfHash = document.getElementById("csrfHash")?.value || "<?= $csrfHash; ?>";

  try {
    const fd = new FormData();
    fd.append(csrfName, csrfHash);
    const r = await fetch("<?= site_url('member/profile/danger_send_otp'); ?>", {
      method: "POST",
      body: fd,
      headers: { "X-Requested-With": "XMLHttpRequest" },
    });
    const sendRes = await r.json().catch(() => ({}));
    setCsrfFromResponse(sendRes);
    if (sendRes?.status !== "success") {
      Swal.fire({
        icon: "error",
        title: "Couldn't send code",
        text: sendRes?.message || "Please try again.",
        buttonsStyling: false,
        confirmButtonText: "Ok, got it!",
        customClass: { confirmButton: "btn btn-primary" },
      });
      return null;
    }
  } catch (e) {
    Swal.fire({
      icon: "error",
      title: "Network error",
      text: "Could not send a verification code. Please try again.",
      buttonsStyling: false,
      confirmButtonText: "Ok, got it!",
      customClass: { confirmButton: "btn btn-primary" },
    });
    return null;
  }

  const { value, isConfirmed } = await Swal.fire({
    title: "Enter verification code",
    text: "We emailed a 6-digit code to your registered email address.",
    input: "text",
    inputLabel: "Verification code",
    inputPlaceholder: "123456",
    inputAttributes: { maxlength: 6, inputmode: "numeric", autocomplete: "one-time-code" },
    showCancelButton: true,
    confirmButtonText: "Verify",
    cancelButtonText: "Cancel",
    buttonsStyling: false,
    customClass: { confirmButton: "btn btn-primary", cancelButton: "btn btn-secondary" },
    inputValidator: (v) => (!v || !/^\d{6}$/.test(v.trim())) ? "Enter the 6-digit code" : undefined,
  });

  if (!isConfirmed) return null;
  return value.trim();
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

  const otp = await askDangerOtp();
  if (!otp) return;

  try {
    const res = await postWithCsrf(
      "<?= site_url('member/profile/request_delete'); ?>",
      reason,
      { otp }
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
    title: "Freeze Account",
    placeholder: "Why do you want to freeze your account?",
    confirmText: "Continue",
    confirmBtnClass: "btn btn-warning",
  });
  if (!reason) return;

  const otp = await askDangerOtp();
  if (!otp) return;

  try {
    const res = await postWithCsrf(
      "<?= site_url('member/profile/freeze_withdraw'); ?>",
      reason,
      { otp }
    );

    if (res?.status === "success" || res?.status === true) {
      Swal.fire({
        icon: "success",
        title: "Account Frozen",
        text: res.message || "Your account has been frozen and you have been logged out.",
        buttonsStyling: false,
        confirmButtonText: "Ok",
        customClass: { confirmButton: "btn btn-primary" },
        allowOutsideClick: false,
      }).then(() => {
        if (res.force_logout) {
          location.href = res.redirect || "<?= base_url('user/in'); ?>";
        }
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

    // ---------- Transfer Password (authorizes internal wallet transfers) ----------
    // Gmail-style strong password: length + a mix of character classes,
    // not just a minimum length.
    function transferPasswordIssues(pw) {
      const issues = [];
      if (pw.length < 6) issues.push('at least 6 characters');
      if (!/[a-z]/.test(pw)) issues.push('a lowercase letter');
      if (!/[A-Z]/.test(pw)) issues.push('an uppercase letter');
      if (!/[0-9]/.test(pw)) issues.push('a number');
      if (!/[^A-Za-z0-9]/.test(pw)) issues.push('a special character');
      return issues;
    }

    async function saveTransferPassword() {
      const login = document.getElementById('tpw_login').value;
      const npw   = document.getElementById('tpw_new').value;
      const conf  = document.getElementById('tpw_confirm').value;
      const msg   = document.getElementById('tpw_msg');
      msg.style.color = '';
      if (!login) { msg.textContent = 'Enter your login password.'; msg.style.color = '#b5730a'; return; }
      const issues = transferPasswordIssues(npw);
      if (issues.length) {
        msg.textContent = 'Password needs: ' + issues.join(', ') + '.';
        msg.style.color = '#b5730a';
        return;
      }
      if (npw !== conf) { msg.textContent = 'Passwords do not match.'; msg.style.color = '#c0392b'; return; }

      const fd = new FormData();
      fd.append('login_password', login);
      fd.append('new_transfer_password', npw);
      fd.append('confirm_transfer_password', conf);
      fd.append('<?= $csrfName; ?>', '<?= $csrfHash; ?>');
      msg.textContent = 'Saving…';
      try {
        const r = await fetch("<?= site_url('member/profile/set_transfer_password'); ?>", {
          method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const j = await r.json();
        const ok = j.status === 'success';
        msg.textContent = j.message || '';
        msg.style.color = ok ? '#149a55' : '#c0392b';
        if (ok) {
          document.getElementById('tpw_login').value = '';
          document.getElementById('tpw_new').value = '';
          document.getElementById('tpw_confirm').value = '';
          if (window.toastMini) toastMini('Transfer password saved');
        }
      } catch (e) { msg.textContent = 'Network error.'; msg.style.color = '#c0392b'; }
    }

    // ---------- Security toggles (2FA / Email Verification) ----------
    async function saveToggle(el, url, msgEl) {
      const wasOn = el.classList.contains('on');
      const next = wasOn ? 0 : 1;
      el.classList.toggle('on', !!next);
      if (msgEl) { msgEl.textContent = 'Saving…'; msgEl.style.color = ''; }

      const fd = new FormData();
      fd.append('status', next);
      fd.append('<?= $csrfName; ?>', '<?= $csrfHash; ?>');

      try {
        const r = await fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const j = await r.json();
        setCsrfFromResponse(j);
        if (j.status === 'success') {
          if (msgEl) { msgEl.textContent = next ? 'Enabled.' : 'Disabled.'; msgEl.style.color = '#149a55'; }
          if (window.toastMini) toastMini(j.message || (next ? 'Enabled' : 'Disabled'));
        } else {
          el.classList.toggle('on', !!wasOn);
          if (msgEl) { msgEl.textContent = j.message || 'Could not save.'; msgEl.style.color = '#c0392b'; }
        }
      } catch (e) {
        el.classList.toggle('on', !!wasOn);
        if (msgEl) { msgEl.textContent = 'Network error.'; msgEl.style.color = '#c0392b'; }
      }
    }

    // 2FA Toggle handler (with QR code setup modal)
    let twofa_setup_secret = '';

    function open2FAModal() {
      document.getElementById('twofa2Modal').style.display = 'flex';
      document.getElementById('twofa_loading').style.display = 'block';
      document.getElementById('twofa_step1').style.display = 'none';
      generate2FAQRCode();
    }

    function close2FAModal() {
      document.getElementById('twofa2Modal').style.display = 'none';
      document.getElementById('twofa_code').value = '';
      document.getElementById('twofa_code_msg').textContent = '';
      twofa_setup_secret = '';
    }

    async function generate2FAQRCode() {
      const csrfName = document.getElementById('csrfName').value;
      const csrfHash = document.getElementById('csrfHash').value;
      const fd = new FormData();
      fd.append(csrfName, csrfHash);

      try {
        const r = await fetch("<?= site_url('member/profile/twofa_setup_request'); ?>", {
          method: 'POST',
          body: fd,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const res = await r.json();

        if (res.status === 'success') {
          twofa_setup_secret = res.secret;
          document.getElementById('twofa_secret').textContent = res.secret;

          // Generate QR code client-side using qrcode.js
          const qrContainer = document.getElementById('twofa_qr');
          qrContainer.innerHTML = '<div id="twofa_qr_canvas"></div>';

          const otpauth = 'otpauth://totp/Nexman?secret=' + res.secret + '&issuer=Nexman';
          new QRCode(document.getElementById('twofa_qr_canvas'), {
            text: otpauth,
            width: 200,
            height: 200,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
          });

          document.getElementById('twofa_loading').style.display = 'none';
          document.getElementById('twofa_step1').style.display = 'block';
          document.getElementById('twofa_verify_btn').style.display = 'inline-block';
          setCsrfFromResponse(res);
        } else {
          toastMini(res.message || 'Failed to generate QR code');
          close2FAModal();
        }
      } catch (e) {
        toastMini('Error generating QR code');
        close2FAModal();
      }
    }

    async function verify2FA() {
      const code = document.getElementById('twofa_code').value.trim();
      const msgEl = document.getElementById('twofa_code_msg');

      if (!code || code.length !== 6 || !/^\d+$/.test(code)) {
        msgEl.textContent = 'Enter a valid 6-digit code';
        msgEl.style.color = '#c0392b';
        return;
      }

      const csrfName = document.getElementById('csrfName').value;
      const csrfHash = document.getElementById('csrfHash').value;
      const fd = new FormData();
      fd.append('secret', twofa_setup_secret);
      fd.append('code', code);
      fd.append(csrfName, csrfHash);

      msgEl.textContent = 'Verifying...';
      msgEl.style.color = '#999';

      try {
        const r = await fetch("<?= site_url('member/profile/twofa_setup_verify'); ?>", {
          method: 'POST',
          body: fd,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const res = await r.json();
        setCsrfFromResponse(res);

        if (res.status === 'success') {
          msgEl.textContent = '✅ Verified! 2FA is now enabled.';
          msgEl.style.color = '#149a55';
          toastMini('2FA setup complete');
          close2FAModal();
          // Refresh the toggle state
          document.getElementById('twofaTog').classList.add('on');

          // NOW SAVE THE TOGGLE STATUS TO DATABASE
          const saveFd = new FormData();
          saveFd.append('status', 1);
          saveFd.append(csrfName, csrfHash);

          try {
            const saveR = await fetch("<?= site_url('member/profile/twofa_toggle'); ?>", {
              method: 'POST',
              body: saveFd,
              headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const saveRes = await saveR.json();
            setCsrfFromResponse(saveRes);
            document.getElementById('twofaTog_msg').textContent = saveRes.message || '2FA enabled successfully';
            document.getElementById('twofaTog_msg').style.color = '#149a55';
          } catch (e) {
            document.getElementById('twofaTog_msg').textContent = 'Setup complete but database save failed!';
            document.getElementById('twofaTog_msg').style.color = '#c0392b';
          }
        } else {
          msgEl.textContent = res.message || 'Invalid code';
          msgEl.style.color = '#c0392b';
        }
      } catch (e) {
        msgEl.textContent = 'Network error. Try again.';
        msgEl.style.color = '#c0392b';
      }
    }

    document.getElementById('twofaTog')?.addEventListener('click', async function() {
      const wasOn = this.classList.contains('on');
      const msgEl = document.getElementById('twofaTog_msg');

      if (wasOn) {
        // Turning OFF: just toggle
        this.classList.remove('on');
        const csrfName = document.getElementById('csrfName').value;
        const csrfHash = document.getElementById('csrfHash').value;
        const fd = new FormData();
        fd.append('status', 0);
        fd.append(csrfName, csrfHash);

        try {
          const r = await fetch("<?= site_url('member/profile/twofa_toggle'); ?>", {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          });
          const res = await r.json();
          setCsrfFromResponse(res);
          msgEl.textContent = res.message || 'Two-Factor Authentication disabled.';
          msgEl.style.color = '#999';
        } catch (e) {
          this.classList.add('on');
          msgEl.textContent = 'Error disabling 2FA';
          msgEl.style.color = '#c0392b';
        }
      } else {
        // Turning ON: show QR code modal
        open2FAModal();
      }
    });

    // Email Verification toggle (simple toggle without setup modal)
    document.getElementById('emailVerifyTog')?.addEventListener('click', function () {
      saveToggle(this, "<?= site_url('member/profile/email_verify_toggle'); ?>", document.getElementById('emailVerifyTog_msg'));
    });

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
        gender: form.querySelector('select[name="gender"]'),
        country: form.querySelector('select[name="country_iso2"]'),
        nationality: form.querySelector('select[name="nationality_iso2"]'),
        address: form.querySelector('input[name="addr_line1"]'),
        region: form.querySelector('input[name="addr_region"]'),
        city: form.querySelector('input[name="addr_city"]'),
        pincode: form.querySelector('input[name="addr_postal"]'),
        consent: form.querySelector('input[name="consent"]'),
        docType: form.querySelector('select[name="doc_type"]'),
        docNumber: form.querySelector('input[name="doc_number"]'),
        docIssueCountry: form.querySelector('select[name="doc_issue_country"]'),
        docIssueDate: form.querySelector('input[name="doc_issue_date"]'),
        docExpiryDate: form.querySelector('input[name="doc_expiry_date"]'),
        docFront: form.querySelector('input[name="doc_front"]'),
        docBack: form.querySelector('input[name="doc_back"]'),
        selfie: form.querySelector('input[name="selfie"]'),

        prevFront: form.querySelector('input[name="prev_doc_front_url"]'),
        prevBack: form.querySelector('input[name="prev_doc_back_url"]'),
        prevSelf: form.querySelector('input[name="prev_selfie_url"]'),
      };

      function fmtBytes(x) { if (!x) return ''; const u = ['B', 'KB', 'MB', 'GB']; let i = 0; while (x >= 1024 && i < u.length - 1) { x /= 1024; i++ } return x.toFixed(i ? 1 : 0) + ' ' + u[i]; }
      function busy(b) { if (b) { submitBtn.setAttribute('data-kt-indicator', 'on'); submitBtn.disabled = true; } else { submitBtn.removeAttribute('data-kt-indicator'); submitBtn.disabled = false; } }
      function clearFieldError(field) {
        if (!field) return;
        field.classList.remove('is-invalid');
        field.removeAttribute('aria-invalid');
        const wrap = field.closest('.fg, .field, .consent, .file-drop, .form-group') || field.parentElement;
        const err = wrap?.querySelector('.field-error');
        if (err) err.remove();
      }
      function showFieldError(field, message) {
        if (!field) return;
        const wrap = field.closest('.fg, .field, .consent, .file-drop, .form-group') || field.parentElement;
        clearFieldError(field);
        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');
        const err = document.createElement('div');
        err.className = 'field-error';
        err.className = 'invalid-feedback field-error';
        err.textContent = message;
        if (wrap) {
          field.insertAdjacentElement('afterend', err);
        } else {
          field.parentElement?.appendChild(err);
        }
      }
      function focusField(field) {
        if (!field) return;
        field.scrollIntoView({ behavior: 'smooth', block: 'center' });
        field.focus({ preventScroll: true });
      }
      function showErrorSummary(messages) {
        const box = document.getElementById('kyc_error_summary');
        const list = document.getElementById('kyc_error_list');
        if (!box || !list) return;
        box.classList.add('d-none');
        list.innerHTML = '';
      }
      function escapeHtml(str) {
        return String(str ?? '')
          .replaceAll('&', '&amp;')
          .replaceAll('<', '&lt;')
          .replaceAll('>', '&gt;')
          .replaceAll('"', '&quot;')
          .replaceAll("'", '&#039;');
      }
      function parseUiDate(value) {
        const raw = String(value || '').trim();
        if (!raw) return null;
        const iso = new Date(raw);
        if (!Number.isNaN(iso.getTime())) return iso;
        const m = raw.match(/^(\d{2})-(\d{2})-(\d{4})$/);
        if (!m) return null;
        const dt = new Date(Number(m[3]), Number(m[2]) - 1, Number(m[1]));
        return Number.isNaN(dt.getTime()) ? null : dt;
      }
      function isValidDate(value) {
        return !!parseUiDate(value);
      }
      function isAdult(dob) {
        const d = parseUiDate(dob);
        if (!d) return false;
        const now = new Date();
        let age = now.getFullYear() - d.getFullYear();
        const m = now.getMonth() - d.getMonth();
        if (m < 0 || (m === 0 && now.getDate() < d.getDate())) age--;
        return age >= 18;
      }
      function fileIsValid(file, allowedExts, maxMb) {
        if (!file) return false;
        const ext = (file.name.split('.').pop() || '').toLowerCase();
        const mime = (file.type || '').toLowerCase();
        const extOk = allowedExts.includes(ext);
        const mimeOk = mime ? (
          allowedExts.includes('jpg') && mime.startsWith('image/jpeg') ||
          allowedExts.includes('jpeg') && mime.startsWith('image/jpeg') ||
          allowedExts.includes('png') && mime.startsWith('image/png') ||
          allowedExts.includes('webp') && mime.startsWith('image/webp') ||
          allowedExts.includes('pdf') && mime === 'application/pdf' ||
          allowedExts.includes('gif') && mime.startsWith('image/gif')
        ) : true;
        return extOk && mimeOk && file.size <= (maxMb * 1024 * 1024);
      }
      function setFileError(field, message) {
        showFieldError(field, message);
        const tile = field?.closest('.js-kyc');
        if (tile) tile.classList.add('is-invalid');
      }
      function validFile(file, imageOnly = false) {
        if (!file) return true;
        if (imageOnly && !file.type.startsWith('image/')) return false;
        if (!(ACCEPT_TYPES.includes(file.type) || file.type.startsWith('image/'))) return false;
        return file.size <= MAX_MB * 1024 * 1024;
      }
      function attachClear(field) {
        if (!field) return;
        ['keyup', 'change', 'blur'].forEach(ev => field.addEventListener(ev, () => clearFieldError(field)));
      }

      function fieldByErrorKey(key) {
        const map = {
          full_name: el.fullName,
          dob: el.dob,
          gender: el.gender,
          country_iso2: el.country,
          nationality_iso2: el.nationality,
          addr_line1: el.address,
          addr_region: el.region,
          addr_city: el.city,
          addr_postal: el.pincode,
          doc_type: el.docType,
          doc_number: el.docNumber,
          doc_issue_country: el.docIssueCountry,
          doc_issue_date: el.docIssueDate,
          doc_expiry_date: el.docExpiryDate,
          consent: el.consent,
          doc_front: el.docFront,
          doc_back: el.docBack,
          selfie: el.selfie,
        };
        return map[key] || null;
      }

      [
        el.fullName, el.dob, el.gender, el.country, el.nationality, el.address, el.region,
        el.city, el.pincode, el.consent, el.docType, el.docNumber, el.docIssueCountry,
        el.docIssueDate, el.docExpiryDate
      ].forEach(attachClear);

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
          const objUrl = URL.createObjectURL(file);
          if (file.type === 'application/pdf') {
            img.src = 'data:image/svg+xml;utf8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="54" height="54"><rect width="54" height="54" rx="8" fill="#F1F3F5"/><text x="50%" y="55%" text-anchor="middle" font-size="14" fill="#6c757d" font-family="Arial">PDF</text></svg>');
          } else {
            img.src = objUrl;
          }
          n.textContent = file.name; s.textContent = fmtBytes(file.size); view.href = objUrl;
        }
        function clear() { input.value = ''; if (pv) pv.classList.add('d-none'); if (rm) rm.classList.add('d-none'); }
        const clearError = () => clearFieldError(input);

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
          clearError();
          show(f);
        });
        ['keyup', 'blur', 'change'].forEach(ev => input.addEventListener(ev, clearError));
      });

      function validateKycForm() {
        const errors = [];
        const fields = [
          [el.fullName, 'Full name is required.', v => v.length >= 3 && v.length <= 100],
          [el.dob, 'Date of birth is required.', v => !!v],
        [el.gender, 'Gender is required.', v => v && v !== 'unspecified'],
          [el.country, 'Country of residence is required.', v => !!v],
          [el.nationality, 'Nationality is required.', v => !!v],
          [el.address, 'Address is required.', v => !!v],
          [el.region, 'Region / State is required.', v => !!v],
          [el.city, 'City is required.', v => !!v],
          [el.pincode, 'Postal code is required.', v => !!v],
          [el.docType, 'Please select a document type.', v => !!v],
          [el.docNumber, 'Document number is required.', v => String(v).trim().length >= 5],
          [el.docIssueCountry, 'Issuing country is required.', v => !!v],
        ];

        const valueOf = (field) => {
          if (!field) return '';
          if (field.type === 'checkbox') return field.checked ? '1' : '';
          return String(field.value || '').trim();
        };

        for (const [field, msg, rule] of fields) {
          if (!field) continue;
          const val = valueOf(field);
          if (!val || (rule && !rule(val))) {
            errors.push(msg);
            showFieldError(field, msg);
          }
        }

        if (el.dob && valueOf(el.dob) && !isAdult(valueOf(el.dob))) {
          errors.push('You must be at least 18 years old.');
          showFieldError(el.dob, 'You must be at least 18 years old.');
        }

        const docFront = el.docFront?.files?.[0];
        const docBack = el.docBack?.files?.[0];
        const selfie = el.selfie?.files?.[0];
        const hasFront = !!docFront || !!(el.prevFront?.value || '').trim();
        const hasBack = !!docBack || !!(el.prevBack?.value || '').trim();
        const hasSelf = !!selfie || !!(el.prevSelf?.value || '').trim();

        if (!hasFront) {
          errors.push('Front document is required.');
          showFieldError(el.docFront, 'Front document is required.');
        } else if (docFront && !fileIsValid(docFront, ['jpg', 'jpeg', 'png', 'pdf'], 8)) {
          errors.push('Front document must be JPG, JPEG, PNG or PDF up to 8 MB.');
          showFieldError(el.docFront, 'Allowed: JPG, JPEG, PNG, PDF up to 8 MB.');
        }

        if (!hasBack) {
          errors.push('Back document is required.');
          showFieldError(el.docBack, 'Back document is required.');
        } else if (docBack && !fileIsValid(docBack, ['jpg', 'jpeg', 'png', 'pdf'], 8)) {
          errors.push('Back document must be JPG, JPEG, PNG or PDF up to 8 MB.');
          showFieldError(el.docBack, 'Allowed: JPG, JPEG, PNG, PDF up to 8 MB.');
        }

        if (!hasSelf) {
          errors.push('Selfie image is required.');
          showFieldError(el.selfie, 'Selfie image is required.');
        } else if (selfie && !fileIsValid(selfie, ['jpg', 'jpeg', 'png', 'webp'], 8)) {
          errors.push('Selfie must be JPG, JPEG, PNG or WEBP up to 8 MB.');
          showFieldError(el.selfie, 'Allowed: JPG, JPEG, PNG, WEBP up to 8 MB.');
        }

        const consentOk = !!(el.consent && el.consent.checked);
        if (!consentOk) {
          errors.push('You must accept verification consent.');
          showFieldError(el.consent || form.querySelector('input[name="consent"]'), 'You must accept verification consent.');
        }

        showErrorSummary(errors);
        const first = form.querySelector('.is-invalid');
        if (first) {
          first.scrollIntoView({ behavior: 'smooth', block: 'center' });
          first.focus({ preventScroll: true });
        }
        return errors.length === 0;
      }

      async function doSubmit(e) {
        if (e) e.preventDefault();
        if (!validateKycForm()) return;

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

            if (data.errors && typeof data.errors === 'object') {
              const serverMsgs = [];
              Object.entries(data.errors).forEach(([key, msg]) => {
                const field = fieldByErrorKey(key);
                if (field) {
                  showFieldError(field, msg);
                  serverMsgs.push(msg);
                }
              });
              showErrorSummary(serverMsgs);
              const first = form.querySelector('.is-invalid');
              if (first) {
                first.scrollIntoView({ behavior: 'smooth', block: 'center' });
                first.focus({ preventScroll: true });
              }
              return;
            }

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
    .field-error {
      font-size: 0.875rem;
      color: #dc3545;
      margin-top: 0.25rem;
    }
  </style>
</body>

</html>
