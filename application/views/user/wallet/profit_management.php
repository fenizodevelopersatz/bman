<?php
// ===================== COMMISSIONS PAGE (USER • ADVANCED & INFORMATIVE) =====================
// Expected vars (set from controller):
// $wallet_balance, $pending_commission, $total_earned, $paid_out
// $counts = ['ALL'=>0,'PAIRING'=>0,'MATCHING'=>0,'DIRECT'=>0,'RANK'=>0,'LEADERSHIP'=>0,'WITHDRAW'=>0];
// $rows = array of commission objects:
//   ->type, ->title, ->ref, ->created_at, ->amount, ->status, ->note, ->from_user, ->level, ->order_id
// $paging = ['page'=>1,'pages'=>1,'total'=>0,'per_page'=>10];
// $filters = ['q'=>'','type'=>'','status'=>'','from'=>'YYYY-MM-DD','to'=>'YYYY-MM-DD'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php $this->load->view('user/layout/v2/user_style'); ?>
  <style>
    /* ===================== COMMISSIONS (PAGE) ===================== */
    .page-titlebar {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 12px;
      margin: 8px 0 18px;
    }

    .page-titlebar h2 {
      font-size: 18px;
      font-weight: 900;
      color: var(--text-main);
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 0;
    }

    .page-titlebar h2 i {
      color: var(--primary);
      font-size: 20px;
    }

    .page-titlebar .sub {
      margin-top: 4px;
      color: var(--text-muted);
      font-size: 12px;
    }

    .page-actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .btn-soft {
      border: 1px solid #f1f1f6;
      background: #fff;
      border-radius: 14px;
      padding: 10px 12px;
      font-weight: 900;
      cursor: pointer;
      font-size: 12px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #111827;
    }

    .btn-soft:hover {
      transform: translateY(-1px);
      transition: .15s;
    }

    .btn-main {
      border: none;
      background: var(--primary);
      color: #fff;
      border-radius: 14px;
      padding: 10px 12px;
      font-weight: 900;
      cursor: pointer;
      font-size: 12px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .sum-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
      margin-bottom: 14px;
    }

    .sum-card {
      background: #fff;
      border: 1px solid #f5f5f7;
      border-radius: 22px;
      padding: 14px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.04);
      display: flex;
      gap: 12px;
      align-items: flex-start;
    }

    .sum-ic {
      width: 44px;
      height: 44px;
      border-radius: 16px;
      display: grid;
      place-items: center;
      font-size: 20px;
      flex-shrink: 0;
      background: #efedfb;
      color: var(--primary);
    }

    .sum-meta small {
      display: block;
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 700;
    }

    .sum-meta strong {
      display: block;
      font-size: 18px;
      margin-top: 6px;
    }

    .sum-meta span {
      display: block;
      font-size: 11px;
      color: var(--text-muted);
      margin-top: 3px;
    }

    .sum-good {
      background: #ecfdf3;
      color: #0f9d58;
    }

    .sum-warn {
      background: #fff7ed;
      color: #c2410c;
    }

    .sum-info {
      background: #eff6ff;
      color: #1d4ed8;
    }

    .sum-bad {
      background: #fef2f2;
      color: #b91c1c;
    }

    .filters {
      background: #fff;
      border: 1px solid #f5f5f7;
      border-radius: 22px;
      padding: 14px;
      box-shadow: 0 10px 26px rgba(0, 0, 0, 0.03);
      margin-bottom: 14px;
    }

    .filter-grid {
      display: grid;
      grid-template-columns: 1.2fr .8fr .8fr .8fr .6fr .4fr;
      gap: 10px;
      align-items: center;
    }

    .f-in,
    .f-sel {
      width: 100%;
      border: 1px solid #f1f1f6;
      background: #f7f7fb;
      border-radius: 14px;
      padding: 11px 12px;
      outline: none;
      font-size: 12px;
    }

    .f-in:focus,
    .f-sel:focus {
      background: #fff;
      border-color: #dcd7ff;
      box-shadow: 0 0 0 4px rgba(110, 86, 207, 0.10);
    }

    .chips {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-top: 12px;
    }

    .chip {
      border: 1px solid #f1f1f6;
      background: #fff;
      border-radius: 999px;
      padding: 8px 10px;
      font-size: 11px;
      font-weight: 900;
      color: #111827;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .chip.active {
      background: #efedfb;
      border-color: #eeecff;
      color: var(--primary);
    }

    .chip .count {
      background: #f7f7fb;
      border: 1px solid #f1f1f6;
      border-radius: 999px;
      padding: 2px 8px;
      font-size: 10px;
      font-weight: 900;
    }

    .chip.active .count {
      background: #fff;
      border-color: #eeecff;
      color: var(--primary);
    }

    .content-grid {
      display: grid;
      grid-template-columns: 1.4fr .6fr;
      gap: 14px;
    }

    .table-card,
    .side-card {
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
      margin-bottom: 10px;
    }

    .card-h h3 {
      font-size: 14px;
      font-weight: 900;
      margin: 0;
    }

    .mini-note {
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 700;
    }

    .pill {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      align-items: center;
      padding: 10px 12px;
      border-radius: 16px;
      border: 1px solid #f5f5f7;
      background: #fbfbff;
      margin-top: 10px;
      font-size: 12px;
    }

    .pill b {
      font-weight: 900;
    }

    .pill span {
      font-weight: 900;
      color: var(--primary);
    }

    .goal {
      margin-top: 12px;
      border: 1px dashed #dedafc;
      background: #fff;
      border-radius: 18px;
      padding: 12px;
    }

    .goal .row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }

    .bar {
      height: 10px;
      background: #f0f0f5;
      border-radius: 999px;
      overflow: hidden;
      margin-top: 10px;
    }

    .bar>div {
      height: 100%;
      width: 62%;
      background: var(--primary);
      border-radius: 999px;
    }

    .tbl {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 10px;
    }

    .tbl thead th {
      font-size: 11px;
      color: var(--text-muted);
      text-align: left;
      font-weight: 900;
      padding: 0 10px 6px;
      white-space: nowrap;
    }

    .tbl tbody tr {
      background: #fff;
      border: 1px solid #f5f5f7;
      box-shadow: 0 10px 22px rgba(0, 0, 0, 0.03);
    }

    .tbl tbody td {
      padding: 12px 10px;
      vertical-align: middle;
      font-size: 12px;
      border-top: 1px solid #f5f5f7;
      border-bottom: 1px solid #f5f5f7;
    }

    .tbl tbody tr td:first-child {
      border-left: 1px solid #f5f5f7;
      border-top-left-radius: 16px;
      border-bottom-left-radius: 16px;
    }

    .tbl tbody tr td:last-child {
      border-right: 1px solid #f5f5f7;
      border-top-right-radius: 16px;
      border-bottom-right-radius: 16px;
    }

    .tx-left {
      display: flex;
      gap: 10px;
      align-items: flex-start;
      min-width: 280px;
    }

    .bullet {
      width: 38px;
      height: 38px;
      border-radius: 14px;
      display: grid;
      place-items: center;
      font-size: 18px;
      flex-shrink: 0;
      background: #efedfb;
      color: var(--primary);
    }

    .tx-left b {
      display: block;
      font-size: 12px;
      font-weight: 900;
    }

    .tx-left small {
      display: block;
      font-size: 11px;
      color: var(--text-muted);
      margin-top: 3px;
      line-height: 1.3;
    }

    .amt {
      font-weight: 1000;
      font-size: 13px;
      white-space: nowrap;
    }

    .status {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 10px;
      border-radius: 999px;
      font-size: 10px;
      font-weight: 1000;
      border: 1px solid #f1f1f6;
      background: #fff;
    }

    .st-success {
      background: #ecfdf3;
      border-color: #d1fadf;
      color: #0f9d58;
    }

    .st-pending {
      background: #fff7ed;
      border-color: #fed7aa;
      color: #c2410c;
    }

    .st-reject {
      background: #fef2f2;
      border-color: #fecaca;
      color: #b91c1c;
    }

    .row-actions {
      display: flex;
      gap: 8px;
      justify-content: flex-end;
      white-space: nowrap;
    }

    .a-btn {
      width: 38px;
      height: 38px;
      border-radius: 14px;
      border: 1px solid #f1f1f6;
      background: #fff;
      cursor: pointer;
      display: grid;
      place-items: center;
      color: #111827;
      font-size: 18px;
    }

    .a-btn:hover {
      transform: translateY(-1px);
      transition: .15s;
    }

    .empty {
      padding: 26px;
      border: 1px dashed #dedafc;
      border-radius: 18px;
      background: #fbfbff;
      text-align: center;
      color: var(--text-muted);
      font-weight: 800;
    }

    .modal-mask {
      position: fixed;
      inset: 0;
      background: rgba(17, 24, 39, .45);
      display: none;
      place-items: center;
      z-index: 99999;
      padding: 16px;
    }

    .modal {
      width: min(720px, 96vw);
      background: #fff;
      border-radius: 24px;
      border: 1px solid #f5f5f7;
      box-shadow: 0 30px 70px rgba(0, 0, 0, 0.22);
      overflow: hidden;
    }

    .modal-h {
      padding: 14px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      border-bottom: 1px solid #f5f5f7;
      background: linear-gradient(105deg, rgba(110, 86, 207, 0.10), rgba(110, 86, 207, 0.02));
    }

    .modal-h b {
      font-size: 14px;
      font-weight: 1000;
    }

    .xbtn {
      width: 42px;
      height: 42px;
      border-radius: 14px;
      border: 1px solid #f1f1f6;
      background: #fff;
      cursor: pointer;
      display: grid;
      place-items: center;
      font-size: 18px;
    }

    .modal-b {
      padding: 16px;
    }

    .kv {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }

    .kv .k {
      border: 1px solid #f5f5f7;
      border-radius: 18px;
      padding: 12px;
      background: #fff;
    }

    .kv .k small {
      display: block;
      color: var(--text-muted);
      font-size: 11px;
      font-weight: 800;
    }

    .kv .k b {
      display: block;
      margin-top: 6px;
      font-size: 12px;
      font-weight: 1000;
    }

    .modal-f {
      padding: 14px 16px;
      border-top: 1px solid #f5f5f7;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    .modal-f .btn-soft {
      padding: 10px 14px;
    }

    .modal-f .btn-main {
      padding: 10px 14px;
    }

    @media(max-width: 1200px) {
      .sum-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .content-grid {
        grid-template-columns: 1fr;
      }

      .filter-grid {
        grid-template-columns: 1fr 1fr;
      }
    }

    @media(max-width: 600px) {
      .sum-grid {
        grid-template-columns: 1fr;
      }

      .filter-grid {
        grid-template-columns: 1fr;
      }

      .kv {
        grid-template-columns: 1fr;
      }

      .tx-left {
        min-width: auto;
      }
    }


    .pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 600;
    }

    /* TRUE */
    .pill-success {
      background-color: #e8fff3;
      color: #0f5132;
      border: 1px solid #b7f0d8;
    }

    /* FALSE */
    .pill-danger {
      background-color: #fff0f0;
      color: #842029;
      border: 1px solid #f5c2c7;
    }

    /* ===================== RESPONSIVE PATCH: COMMISSIONS (ADD AT END) ===================== */

    /* Tablet */
    @media (max-width: 992px) {
      .page-titlebar {
        align-items: flex-start;
        flex-direction: column;
        gap: 10px;
      }

      .page-actions {
        width: 100%;
        gap: 8px;
      }

      .page-actions .btn-soft,
      .page-actions .btn-main {
        flex: 1 1 auto;
        justify-content: center;
      }

      .sum-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .content-grid {
        grid-template-columns: 1fr;
      }

      .filters {
        border-radius: 18px;
        padding: 12px;
      }

      .filter-grid {
        grid-template-columns: 1fr 1fr;
      }
    }

    /* Mobile */
    @media (max-width: 600px) {

      /* Title + actions */
      .page-titlebar {
        margin: 6px 0 12px;
      }

      .page-titlebar h2 {
        font-size: 16px;
      }

      .page-actions .btn-soft,
      .page-actions .btn-main {
        flex: 1 1 100%;
        justify-content: center;
        padding: 12px 12px;
        border-radius: 16px;
      }

      /* Summary cards */
      .sum-grid {
        grid-template-columns: 1fr;
        gap: 10px;
      }

      .sum-card {
        border-radius: 18px;
        padding: 12px;
      }

      /* Filters become stacked */
      .filter-grid {
        grid-template-columns: 1fr;
        gap: 10px;
      }

      /* Chips spacing */
      .chips {
        gap: 6px;
      }

      .chip {
        padding: 8px 10px;
      }

      /* Cards */
      .table-card,
      .side-card {
        border-radius: 18px;
        padding: 12px;
      }

      /* ========= TABLE -> CARD VIEW ========= */
      .tbl thead {
        display: none;
      }

      .tbl {
        border-spacing: 0 10px;
      }

      .tbl tbody tr {
        display: block;
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid #f5f5f7;
        box-shadow: 0 10px 22px rgba(0, 0, 0, 0.03);
      }

      .tbl tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border: none !important;
      }

      /* First cell (Commission title block) */
      .tbl tbody td:first-child {
        display: block;
        padding: 12px;
        border-bottom: 1px solid #f5f5f7 !important;
      }

      .tx-left {
        min-width: 0;
      }

      /* Labels */
      .tbl tbody td:nth-child(2)::before {
        content: "Date";
      }

      .tbl tbody td:nth-child(3)::before {
        content: "Amount";
      }

      .tbl tbody td:nth-child(4)::before {
        content: "Status";
      }

      .tbl tbody td:nth-child(5)::before {
        content: "Actions";
      }

      .tbl tbody td:nth-child(n+2)::before {
        font-size: 11px;
        font-weight: 900;
        color: var(--text-muted);
        min-width: 78px;
      }

      .row-actions {
        width: 100%;
        justify-content: flex-end;
      }

      .a-btn {
        width: 40px;
        height: 40px;
        border-radius: 14px;
      }

      .amt {
        font-size: 14px;
      }
    }

    /* Tiny phones */
    @media (max-width: 380px) {
      .sum-ic {
        width: 40px;
        height: 40px;
        border-radius: 14px;
      }

      .bullet {
        width: 34px;
        height: 34px;
        border-radius: 12px;
      }
    }

    /* ===================== TAB RESPONSIVE (1440 x 793) ===================== */
    @media (max-width: 1440px) and (min-width: 1024px) {

      /* page spacing */
      .page-titlebar {
        margin: 6px 0 14px;
      }

      .filters,
      .table-card,
      .side-card {
        padding: 12px;
        border-radius: 20px;
      }

      .sum-grid {
        gap: 12px;
      }

      .content-grid {
        gap: 12px;
        grid-template-columns: 1.25fr .75fr;
      }

      /* balance table + insights */

      /* buttons compact */
      .btn-soft,
      .btn-main {
        padding: 9px 10px;
        border-radius: 14px;
        font-size: 12px;
      }

      /* summary cards compact */
      .sum-card {
        padding: 12px;
      }

      .sum-meta strong {
        font-size: 16px;
      }

      .sum-ic {
        width: 42px;
        height: 42px;
        border-radius: 16px;
      }

      /* filters row tighter (still 2 rows nicely) */
      .filter-grid {
        grid-template-columns: 1.2fr .8fr .8fr .8fr .6fr .45fr;
        gap: 8px;
      }

      .f-in,
      .f-sel {
        padding: 10px 11px;
        border-radius: 14px;
      }

      /* table tighter */
      .tbl thead th {
        font-size: 11px;
        padding: 0 8px 6px;
      }

      .tbl tbody td {
        padding: 10px 8px;
        font-size: 12px;
      }

      /* reduce commission column width so it doesn't push actions */
      .tx-left {
        min-width: 220px;
      }

      .bullet {
        width: 36px;
        height: 36px;
        border-radius: 14px;
        font-size: 17px;
      }

      .tx-left b {
        font-size: 12px;
      }

      .tx-left small {
        font-size: 11px;
      }

      /* status + action buttons compact */
      .status {
        padding: 6px 9px;
        font-size: 10px;
      }

      .a-btn {
        width: 36px;
        height: 36px;
        border-radius: 14px;
        font-size: 17px;
      }

      /* side-card tiles tighter */
      .pill {
        padding: 9px 10px;
        border-radius: 16px;
        font-size: 12px;
      }

      .goal {
        padding: 11px;
        border-radius: 18px;
      }
    }

    .table-wrap {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
  </style>

</head>

<body>
  <?php
  $filters = $filters ?? [];
  $qv = $filters['q'] ?? '';
  $tv = strtoupper($filters['type'] ?? '');
  $sv = strtoupper($filters['status'] ?? '');
  $fv = $filters['from'] ?? '';
  $tov = $filters['to'] ?? '';
  $user_id = $this->session->userdata('user_userid') ?? '';
  $isEligible = is_withdraw_eligible($user_id);
  ?>
  <div class="app-container">
    <?php $this->load->view('user/layout/v2/user_sidebar'); ?>

    <main class="main-content">
      <?php $this->load->view('user/layout/v2/user_header'); ?>

      <div class="page-titlebar">
        <div>
          <h2><i class="ph ph-coins"></i> Commissions</h2>
          <div class="sub">Track all your earnings: pairing, matching, referrals, rank rewards & payouts.</div>
        </div>

        <div class="page-actions">
          <button class="btn-soft" onclick="window.print()"><i class="ph ph-printer"></i> Print</button>
          <button class="btn-soft" onclick="exportCSV()"><i class="ph ph-download-simple"></i> Export</button>
          <button class="btn-main" onclick="location.href='<?= base_url('user/withdraw'); ?>'"><i
              class="ph ph-money"></i> Withdraw</button>
        </div>
      </div>

      <div class="sum-grid">
        <div class="sum-card">
          <div class="sum-ic sum-good"><i class="ph ph-wallet"></i></div>
          <div class="sum-meta">
            <small>Available Balance</small>
            <strong><?= currency_format($wallet_balance ?? 0, 2); ?></strong>
            <span>Withdraw anytime</span>
          </div>
        </div>
        <div class="sum-card">
          <div class="sum-ic sum-warn"><i class="ph ph-hourglass"></i></div>
          <div class="sum-meta">
            <small>Pending Commission</small>
            <strong>
              <?= currency_format($pending_commission ?? 0, 2); ?></strong>
            <span>Awaiting payout</span>
          </div>
        </div>
        <div class="sum-card">
          <div class="sum-ic sum-info"><i class="ph ph-trend-up"></i></div>
          <div class="sum-meta">
            <small>Total Earned</small>
            <strong> <?= currency_format($total_earned ?? 0, 2); ?></strong>
            <span>Lifetime income</span>
          </div>
        </div>
        <div class="sum-card">
          <div class="sum-ic sum-bad"><i class="ph ph-bank"></i></div>
          <div class="sum-meta">
            <small>Total Paid Out</small>
            <strong><?= currency_format($paid_out ?? 0, 2); ?></strong>
            <span>Transferred to bank</span>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters">
        <div class="filter-grid">
          <input id="q" class="f-in" placeholder="Search: order id, member, reference..."
            value="<?= htmlspecialchars($qv, ENT_QUOTES); ?>" />
          <select id="type" class="f-sel">
            <option value="" <?= $tv === '' ? 'selected' : ''; ?>>All Types</option>
            <option value="PAIRING" <?= $tv === 'PAIRING' ? 'selected' : ''; ?>>Pairing Bonus</option>
            <option value="MATCHING" <?= $tv === 'MATCHING' ? 'selected' : ''; ?>>Matching Bonus</option>
            <option value="DIRECT" <?= $tv === 'DIRECT' ? 'selected' : ''; ?>>Direct Referral</option>
            <option value="RANK" <?= $tv === 'RANK' ? 'selected' : ''; ?>>Rank Reward</option>
            <option value="WITHDRAW" <?= $sv === 'REJECTED' ? 'selected' : ''; ?>>Withdraw</option>
          </select>
          <select id="status" class="f-sel">
            <option value="" <?= $sv === '' ? 'selected' : ''; ?>>All Status</option>
            <option value="SUCCESS" <?= $sv === 'SUCCESS' ? 'selected' : ''; ?>>Success</option>
            <option value="PENDING" <?= $sv === 'PENDING' ? 'selected' : ''; ?>>Pending</option>
            <option value="REJECTED" <?= $sv === 'REJECTED' ? 'selected' : ''; ?>>Rejected</option>

          </select>
          <input id="from" class="f-in" type="date" value="<?= htmlspecialchars($fv, ENT_QUOTES); ?>" />
          <input id="to" class="f-in" type="date" value="<?= htmlspecialchars($tov, ENT_QUOTES); ?>" />

          <button class="btn-main" type="button" onclick="applyFilters()">
            <i class="ph ph-funnel"></i> Apply
          </button>
        </div>

        <!-- Type Chips -->
        <div class="chips" id="chips">
          <?php
          $c = $counts ?? [];
          $def = ['ALL' => ($paging['total'] ?? 0), 'PAIRING' => 0, 'MATCHING' => 0, 'DIRECT' => 0, 'RANK' => 0, 'WITHDRAW' => 0];
          $c = array_merge($def, $c);

          $chipActive = $tv; // current selected
          ?>
          <div class="chip <?= $chipActive === '' ? 'active' : ''; ?>" data-type=""><i class="ph ph-squares-four"></i>
            All <span class="count"><?= (int) $c['ALL']; ?></span></div>
          <div class="chip <?= $chipActive === 'PAIRING' ? 'active' : ''; ?>" data-type="PAIRING"><i
              class="ph ph-link"></i> Pairing <span class="count"><?= (int) $c['PAIRING']; ?></span></div>
          <div class="chip <?= $chipActive === 'MATCHING' ? 'active' : ''; ?>" data-type="MATCHING"><i
              class="ph ph-users-three"></i> Matching <span class="count"><?= (int) $c['MATCHING']; ?></span></div>
          <div class="chip <?= $chipActive === 'DIRECT' ? 'active' : ''; ?>" data-type="DIRECT"><i
              class="ph ph-user-plus"></i> Direct <span class="count"><?= (int) $c['DIRECT']; ?></span></div>
          <div class="chip <?= $chipActive === 'RANK' ? 'active' : ''; ?>" data-type="RANK"><i class="ph ph-medal"></i>
            Rank <span class="count"><?= (int) $c['RANK']; ?></span></div>
          <div class="chip <?= $chipActive === 'WITHDRAW' ? 'active' : ''; ?>" data-type="WITHDRAW"><i
              class="ph ph-money"></i> Withdraw <span class="count"><?= (int) $c['WITHDRAW']; ?></span></div>
          <a class="chip" href="<?= base_url('user/profit'); ?>"><i class="ph ph-x"></i> Clear </a>
        </div>
      </div>

      <div class="content-grid">

        <!-- Table -->
        <div class="table-card">
          <div class="card-h">
            <div>
              <h3>Earnings History</h3>
              <div class="mini-note">Click any row to view full details.</div>
            </div>
            <div class="mini-note">Total: <?= (int) ($paging['total'] ?? 0); ?></div>
          </div>

          <?php if (!empty($rows)): ?>
            <div class="table-wrap">
              <table class="tbl" id="tbl">
                <thead>
                  <tr>
                    <th>Commission</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($rows as $r):
                    $type = strtoupper($r->type ?? 'OTHER');
                    $status = strtoupper($r->status ?? 'PENDING');

                    $icon = 'ph-coins';
                    if ($type === 'PAIRING')
                      $icon = 'ph-link';
                    else if ($type === 'MATCHING')
                      $icon = 'ph-users-three';
                    else if ($type === 'DIRECT')
                      $icon = 'ph-user-plus';
                    else if ($type === 'RANK')
                      $icon = 'ph-medal';
                    else if ($type === 'WITHDRAW')
                      $icon = 'ph-money';

                    $stClass = 'st-pending';
                    $statusContent = 'PENDING';
                    if ($status === 'SUCCESS' || $status === 'PAID' || $status === '1') {
                      $stClass = 'st-success';
                      $statusContent = 'SUCCESS';
                    } else if ($status === 'REJECTED' || $status === 'FAILED' || $status === '0') {
                      $stClass = 'st-reject';
                      $statusContent = 'PENDING';
                    }


                    $title = strtoupper($r->title ?? ($type . ' Bonus'));
                    $ref = $r->ref ?? ($r->id ? 'Order #' . $r->id : ($r->level ? 'Level ' . $r->level : ''));
                    $note = $r->note ?? '';
                    $fromU = $r->from_user ?? '';
                    $dt = $r->created_at ?? '';
                    $amt = (float) ($r->amount ?? 0);
                    ?>
                    <tr class="row" data-json="<?= htmlspecialchars(json_encode([
                      'Type' => $type,
                      'Title' => $title,
                      'Reference' => $ref,
                      'From' => $fromU,
                      'Level' => ($r->level ?? ''),
                      'Order ID' => ($r->id ?? ''),
                      'Date' => $dt,
                      'Amount' => currency_info()->currency_symbol . ' ' . number_format($amt, 2),
                      'Status' => $statusContent,
                      'Note' => $note,
                    ]), ENT_QUOTES, 'UTF-8'); ?>">
                      <td>
                        <div class="tx-left">
                          <div class="bullet"><i class="ph <?= $icon; ?>"></i></div>
                          <div>
                            <b><?= strtoupper(htmlspecialchars($title)); ?></b>
                            <small>
                              <?= htmlspecialchars($type); ?>
                              <?= $ref ? ' • ' . htmlspecialchars($ref) : ''; ?>
                              <?= $fromU ? ' • From ' . htmlspecialchars($fromU) : ''; ?>
                            </small>
                          </div>
                        </div>
                      </td>
                      <td style="white-space:nowrap;color:var(--text-muted);font-weight:800;">
                        <?= htmlspecialchars($dt); ?>
                      </td>
                      <td class="amt">
                        <?= currency_info()->currency_symbol; ?>     <?= number_format($amt, 2); ?>
                      </td>
                      <td>
                        <span class="status <?= ($status == 1) ? 'success' : 'pending'; ?>">
                          <i class="ph ph-dot-outline"></i> <?= ($status == 1) ? 'Success' : 'Pending'; ?>
                        </span>
                      </td>
                      <td>
                        <div class="row-actions">
                          <button class="a-btn" type="button" title="View Details"
                            onclick="event.stopPropagation(); openFromBtn(this)"><i class="ph ph-eye"></i></button>
                          <button class="a-btn" type="button" title="Copy Ref"
                            onclick="event.stopPropagation(); copyPlain('<?= htmlspecialchars($ref, ENT_QUOTES); ?>')"><i
                              class="ph ph-copy"></i></button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <?php
            $page = (int) ($paging['page'] ?? 1);
            $pages = (int) ($paging['pages'] ?? 1);

            $qs = $filters ?? [];
            unset($qs['page']);
            $base = base_url('user/profit'); // ✅ change if your route different
          
            $makeUrl = function ($p) use ($base, $qs) {
              $qs2 = $qs;
              $qs2['page'] = $p;
              return $base . '?' . http_build_query($qs2);
            };
            ?>

            <!-- Pagination -->
            <div
              style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;gap:10px;flex-wrap:wrap;">
              <div class="mini-note">
                Page <?= $page; ?> / <?= $pages; ?>
              </div>
              <div style="display:flex;gap:10px;">
                <button class="btn-soft" type="button" <?= $page <= 1 ? 'disabled' : ''; ?>
                  onclick="location.href='<?= $makeUrl(max(1, $page - 1)); ?>'"><i class="ph ph-caret-left"></i>
                  Prev</button>
                <button class="btn-soft" type="button" <?= $page >= $pages ? 'disabled' : ''; ?>
                  onclick="location.href='<?= $makeUrl(min($pages, $page + 1)); ?>'">Next <i
                    class="ph ph-caret-right"></i></button>
              </div>
            </div>

          <?php else: ?>
            <div class="empty">No commission records found for the selected filters.</div>
          <?php endif; ?>
        </div>

        <!-- Side Insights -->
        <div class="side-card">
          <div class="card-h">
            <h3>Insights</h3>
            <div class="mini-note">This week</div>
          </div>

          <div class="pill"><b>Best Earning Type</b> <span>Pairing</span></div>
          <div class="pill"><b>Next Payout</b> <span><?php echo NEXT_PAYOUT_TIME; ?></span></div>
          <div class="pill <?= $isEligible ? 'pill-success' : 'pill-danger'; ?>">
            <b>Withdraw Status</b>
            <span>
              <?= $isEligible ? 'Eligible' : 'Not Eligible'; ?>
            </span>
          </div>


          <div class="goal">
            <div class="row">
              <b>Weekly Target Progress</b>
              <span style="font-size:12px;color:#5d56a8;font-weight:1000;" id="weekly_progress">0%</span>
            </div>
            <div class="bar">
              <div id="weekly_progress_bar"></div>
            </div>
            <div class="mini-note" style="margin-top:10px;">Tip: Balance left/right BV to increase pairing frequency.
            </div>
          </div>

          <div class="goal" style="margin-top:12px;">
            <div class="row">
              <b>Quick Actions</b>
              <span class="mini-note">Fast</span>
            </div>
            <div style="display:grid;gap:10px;margin-top:10px;">
              <button class="btn-main" type="button" onclick="location.href='<?= base_url('user/referrals'); ?>'"><i
                  class="ph ph-share-network"></i> Share Referral</button>
              <button class="btn-soft" type="button" onclick="location.href='<?= base_url('user/binary_tree'); ?>'"><i
                  class="ph ph-tree-structure"></i> View Binary Tree</button>
              <button class="btn-soft" type="button" onclick="location.href='<?= base_url('user/myorders'); ?>'"><i
                  class="ph ph-bag"></i> My Orders</button>
            </div>
          </div>
        </div>

      </div><!-- /content-grid -->

    </main>

    <aside class="right-panel">
      <?php $this->load->view('user/layout/v2/user_inner_right_panle'); ?>
    </aside>
  </div>

  <!-- Details Modal -->
  <div class="modal-mask" id="mMask">
    <div class="modal">
      <div class="modal-h">
        <b>Commission Details</b>
        <button class="xbtn" onclick="closeModal()"><i class="ph ph-x"></i></button>
      </div>
      <div class="modal-b">
        <div class="kv" id="kv"></div>
      </div>
      <div class="modal-f">
        <button class="btn-soft" onclick="closeModal()"><i class="ph ph-x"></i> Close</button>
        <button class="btn-main" onclick="exportOne()"><i class="ph ph-download-simple"></i> Export</button>
      </div>
    </div>
  </div>
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>
  <script>
    const base_url = "<?php echo base_url() ?>";
    const tbl = document.getElementById('tbl');
    const mMask = document.getElementById('mMask');
    const kv = document.getElementById('kv');
    let lastRowData = null;

    // Row click -> modal
    if (tbl) {
      tbl.addEventListener('click', (e) => {
        const tr = e.target.closest('tr.row');
        if (!tr) return;
        openRow(tr);
      });
    }

    function openFromBtn(btn) {
      const tr = btn.closest('tr.row');
      if (!tr) return;
      openRow(tr);
    }

    function openRow(tr) {
      const raw = tr.getAttribute('data-json');
      if (!raw) return;
      try { lastRowData = JSON.parse(raw); } catch (err) { return; }
      renderKV(lastRowData);
      openModal();
    }

    function renderKV(obj) {
      kv.innerHTML = '';
      console.log("obj", obj);

      Object.keys(obj).forEach(k => {
        const v = obj[k] ?? '';
        if (v === '' || v === null) return;
        const d = document.createElement('div');
        d.className = 'k';
        d.innerHTML = `<small>${escapeHtml(k)}</small><b>${escapeHtml(String(v))}</b>`;
        kv.appendChild(d);
      });
    }

    function openModal() { mMask.style.display = 'grid'; }
    function closeModal() { mMask.style.display = 'none'; }

    // chips -> set type + reload
    document.querySelectorAll('#chips .chip[data-type]').forEach(ch => {
      ch.addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById('type').value = ch.dataset.type || '';
        applyFilters();
      });
    });

    // Apply filters -> reload with query params
    function applyFilters() {
      const q = document.getElementById('q').value.trim();
      const type = document.getElementById('type').value.trim();
      const status = document.getElementById('status').value.trim();
      const from = document.getElementById('from').value;
      const to = document.getElementById('to').value;

      const params = new URLSearchParams();
      if (q) params.set('q', q);
      if (type) params.set('type', type);
      if (status) params.set('status', status);
      if (from) params.set('from', from);
      if (to) params.set('to', to);

      // ✅ change this if route different
      window.location.href = "<?= base_url('user/profit'); ?>?" + params.toString();
    }

    // Enter key triggers apply
    ['q', 'type', 'status', 'from', 'to'].forEach(id => {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { applyFilters(); }
      });
    });

    // Export table to CSV
    function exportCSV() {
      if (!tbl) return;
      const rows = [];
      const headers = ["Type", "Title", "Date", "Amount", "Status", "Reference"];
      rows.push(headers.join(","));
      document.querySelectorAll("#tbl tbody tr.row").forEach(tr => {
        const obj = JSON.parse(tr.getAttribute('data-json'));
        const line = [
          obj["Type"] || "",
          obj["Title"] || "",
          obj["Date"] || "",
          obj["Amount"] || "",
          obj["Status"] || "",
          obj["Reference"] || ""
        ].map(csvEscape).join(",");
        rows.push(line);
      });
      downloadFile("commissions.csv", rows.join("\n"));
    }

    function exportOne() {
      if (!lastRowData) return;
      const lines = Object.keys(lastRowData).map(k => `${k}: ${lastRowData[k]}`);
      downloadFile("commission_details.txt", lines.join("\n"));
    }

    function copyPlain(txt) {
      navigator.clipboard.writeText(txt || "").then(() => toastMini("Copied!"));
    }

    function downloadFile(name, content) {
      const blob = new Blob([content], { type: "text/plain;charset=utf-8" });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = name;
      document.body.appendChild(a);
      a.click();
      a.remove();
    }

    function csvEscape(v) {
      v = String(v ?? "");
      if (v.includes('"') || v.includes(',') || v.includes('\n')) {
        v = '"' + v.replaceAll('"', '""') + '"';
      }
      return v;
    }

    function escapeHtml(s) {
      return String(s).replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;");
    }

    function toastMini(msg) {
      const t = document.createElement('div');
      t.textContent = msg;
      t.style.cssText =
        "position:fixed;bottom:22px;left:50%;transform:translateX(-50%);background:#111;color:#fff;padding:10px 14px;border-radius:14px;font-weight:900;font-size:12px;z-index:99999;opacity:0;transition:.2s;";
      document.body.appendChild(t);
      requestAnimationFrame(() => t.style.opacity = "1");
      setTimeout(() => { t.style.opacity = "0"; setTimeout(() => t.remove(), 250); }, 1400);
    }

    const userId = "<?php echo $this->session->userdata('user_userid'); ?>";

    async function updateWeeklyProgress() {
      try {
        const url = base_url + 'balance-info-user/' + userId;

        const r = await fetch(url, { headers: { "Accept": "application/json" } });
        const text = await r.text();          // read raw first
        let json;

        try {
          json = JSON.parse(text);
        } catch (e) {
          console.error("NOT JSON RESPONSE:", text);
          throw e;
        }

        if (json.result) {
          const p = Number(json.data.weekly_progress || 0);
          document.getElementById("weekly_progress").innerText = p.toFixed(0) + "%";
          document.getElementById("weekly_progress_bar").style.width = p + "%";
        }
      } catch (err) {
        console.error("Error fetching balance info:", err);
      }
    }

    updateWeeklyProgress();

  </script>
</body>

</html>