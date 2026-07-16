<?php
// ===================== PAYOUTS PAGE (USER • ADVANCED UI) =====================
// Expected vars (sample):
// $user = (object)['name'=>'Lucas','uid'=>'NEXMAN123'];
// $payout = (object)[
//   'next_date'=>'Tonight 10:00 PM',
//   'min_withdraw'=>10,
//   'processing_fee'=>0,
//   'eligibility'=>true,
//   'kyc'=>true,
//   'bank'=>true,
//   'pending_amount'=>3980,
//   'available_amount'=>12450,
//   'paid_total'=>55750
// ];
// $withdraw_methods = [
//   (object)['key'=>'BANK','name'=>'Bank Transfer','desc'=>'Direct to your linked bank account'],
//   (object)['key'=>'UPI','name'=>'UPI','desc'=>'Instant payout to UPI ID (if enabled)'],
// ];
// $payouts = [
//   (object)['payout_id'=>'PAYOUT-1021','period'=>'Week 04, 2026','type'=>'WEEKLY','amount'=>1200,'fee'=>0,'status'=>'PAID','date'=>'2026-01-27','note'=>'Pairing + Matching'],
//   (object)['payout_id'=>'PAYOUT-1018','period'=>'Week 03, 2026','type'=>'WEEKLY','amount'=>950,'fee'=>0,'status'=>'PROCESSING','date'=>'2026-01-20','note'=>'Pairing'],
// ];

// echo "<pre>";
// print_r($payout);
// exit;
?>
<!DOCTYPE html>
<html lang="en">
<meta name="viewport" content="width=device-width, initial-scale=1" />

<head>
  <?php $this->load->view('user/layout/v2/user_style'); ?>
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <style>
    /* ===================== PAYOUTS ===================== */
    .titlebar {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      gap: 12px;
      margin: 8px 0 18px;
    }

    .titlebar h2 {
      margin: 0;
      font-size: 18px;
      font-weight: 1000;
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
      font-weight: 800;
    }

    .actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .btn-soft {
      border: 1px solid #f1f1f6;
      background: #fff;
      border-radius: 14px;
      padding: 10px 12px;
      font-weight: 1000;
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
      font-weight: 1000;
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
      font-weight: 1000;
      cursor: pointer;
      font-size: 12px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .grid-2 {
      display: grid;
      grid-template-columns: 1.1fr .9fr;
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
      font-size: 14px;
      font-weight: 1000;
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
      font-weight: 1000;
    }

    /* KPI row */
    .kpis {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 12px;
      margin-bottom: 14px;
    }

    .kpi {
      border: 1px solid #f5f5f7;
      background: #fff;
      border-radius: 20px;
      padding: 14px;
      display: flex;
      gap: 12px;
      align-items: center;
    }

    .kpi .ic {
      width: 44px;
      height: 44px;
      border-radius: 16px;
      display: grid;
      place-items: center;
      font-size: 20px;
    }

    .kpi small {
      display: block;
      font-size: 10px;
      color: var(--text-muted);
      font-weight: 1000;
    }

    .kpi strong {
      display: block;
      font-size: 14px;
      font-weight: 1100;
      margin-top: 2px;
    }

    .kpi span {
      display: block;
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 800;
      margin-top: 3px;
    }

    @media(max-width:1100px) {
      .kpis {
        grid-template-columns: repeat(2, 1fr);
      }

      .grid-2 {
        grid-template-columns: 1fr;
      }
    }

    /* Eligibility */
    .reqs {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .req {
      display: inline-flex;
      gap: 8px;
      align-items: center;
      padding: 8px 10px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 1000;
      border: 1px solid #f1f1f6;
      background: #fff;
    }

    .req.ok {
      border-color: #dcfce7;
      background: #ecfdf3;
      color: #0f9d58;
    }

    .req.warn {
      border-color: #ffedd5;
      background: #fff7ed;
      color: #c2410c;
    }

    .req.bad {
      border-color: #fee2e2;
      background: #fef2f2;
      color: #b91c1c;
    }

    /* Withdraw panel */
    .form {
      display: grid;
      gap: 12px;
    }

    .row2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    .inp,
    .sel {
      width: 100%;
      border: 1px solid #f1f1f6;
      background: #f7f7fb;
      border-radius: 14px;
      padding: 12px;
      outline: none;
      font-size: 12px;
      font-weight: 900;
    }

    .inp:focus,
    .sel:focus {
      background: #fff;
      border-color: #dcd7ff;
      box-shadow: 0 0 0 4px rgba(110, 86, 207, 0.10);
    }

    label {
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 1000;
      display: block;
      margin-bottom: 6px;
    }

    .field {
      display: flex;
      flex-direction: column;
    }

    .hint {
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 800;
      line-height: 1.4;
      margin-top: 6px;
    }

    .method-cards {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    .mcard {
      border: 1px solid #f1f1f6;
      background: #fff;
      border-radius: 18px;
      padding: 12px;
      cursor: pointer;
      display: flex;
      gap: 10px;
      align-items: flex-start;
      transition: .15s;
    }

    .mcard:hover {
      transform: translateY(-1px);
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.05);
    }

    .mcard.active {
      border-color: #dcd7ff;
      box-shadow: 0 0 0 4px rgba(110, 86, 207, 0.10);
    }

    .mcard .ic {
      width: 42px;
      height: 42px;
      border-radius: 16px;
      background: #efedfb;
      color: var(--primary);
      display: grid;
      place-items: center;
      font-size: 20px;
      flex: 0 0 auto;
    }

    .mcard b {
      display: block;
      font-size: 12px;
      font-weight: 1100;
    }

    .mcard small {
      display: block;
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 800;
      margin-top: 3px;
      line-height: 1.35;
    }

    @media(max-width:900px) {
      .method-cards {
        grid-template-columns: 1fr;
      }

      .row2 {
        grid-template-columns: 1fr;
      }
    }

    /* Table */
    .filters {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
      margin-top: 8px;
    }

    .table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 10px;
    }

    .table th {
      font-size: 11px;
      color: var(--text-muted);
      text-align: left;
      font-weight: 1000;
      padding: 0 10px;
    }

    .tr {
      background: #fff;
      border: 1px solid #f5f5f7;
      border-radius: 18px;
      box-shadow: 0 12px 25px rgba(0, 0, 0, 0.03);
    }

    .tr td {
      padding: 12px 10px;
      font-size: 12px;
      font-weight: 900;
      color: #111;
    }

    .td-title b {
      display: block;
      font-size: 12px;
      font-weight: 1100;
    }

    .td-title small {
      display: block;
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 800;
      margin-top: 2px;
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

    .amt {
      font-size: 13px;
      font-weight: 1200;
      text-align: right;
    }

    .btn-mini {
      border: 1px solid #f1f1f6;
      background: #fff;
      border-radius: 12px;
      padding: 9px 10px;
      font-size: 12px;
      font-weight: 1000;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .empty {
      border: 1px dashed #e7e7f3;
      background: #fbfbff;
      border-radius: 18px;
      padding: 18px;
      text-align: center;
      color: var(--text-muted);
      font-weight: 900;
      font-size: 12px;
    }

    /* Side cards */
    .side-grid {
      display: grid;
      gap: 12px;
    }

    .pillx {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      border: 1px solid #f1f1f6;
      background: #f7f7fb;
      border-radius: 16px;
      padding: 12px;
      font-size: 12px;
      font-weight: 1000;
      color: #111;
    }

    .pillx span {
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 900;
    }

    .pillx b {
      font-size: 12px;
    }

    .btn-full {
      width: 100%;
      border: none;
      border-radius: 16px;
      padding: 12px 14px;
      cursor: pointer;
      font-weight: 1100;
      background: #efedfb;
      color: var(--primary);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-full.primary {
      background: var(--primary);
      color: #fff;
    }

    .btn-full.dark {
      background: #111;
      color: #fff;
    }

    /* ===================== RESPONSIVE PATCH ===================== */

    /* Prevent overflow issues */
    * {
      box-sizing: border-box;
    }

    html,
    body {
      width: 100%;
      overflow-x: hidden;
    }

    /* Titlebar: stack nicely */
    @media (max-width: 900px) {
      .titlebar {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }

      .actions {
        width: 100%;
      }

      .actions .btn-soft,
      .actions .btn-main,
      .actions .btn-dark {
        flex: 1 1 auto;
        justify-content: center;
      }
    }

    /* KPI grid: 4 -> 2 -> 1 */
    @media (max-width: 1100px) {
      .kpis {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 600px) {
      .kpis {
        grid-template-columns: 1fr;
      }

      .kpi {
        padding: 12px;
        border-radius: 18px;
      }

      .kpi .ic {
        width: 42px;
        height: 42px;
        border-radius: 14px;
      }

      .kpi strong {
        font-size: 13px;
      }
    }

    /* Main grid: 2 columns -> 1 column */
    @media (max-width: 1100px) {
      .grid-2 {
        grid-template-columns: 1fr;
      }
    }

    /* Card padding for small screens */
    @media (max-width: 600px) {
      .card {
        padding: 12px;
        border-radius: 18px;
      }
    }

    /* Withdraw methods: 2 -> 1 */
    @media (max-width: 900px) {
      .method-cards {
        grid-template-columns: 1fr;
      }
    }

    /* Form row2: 2 -> 1 */
    @media (max-width: 900px) {
      .row2 {
        grid-template-columns: 1fr;
      }
    }

    /* Inputs: nicer on mobile */
    @media (max-width: 600px) {

      .inp,
      .sel {
        padding: 12px;
        font-size: 13px;
        border-radius: 14px;
      }

      label {
        font-size: 12px;
      }
    }

    /* Reqs chips wrap + smaller */
    @media (max-width: 600px) {
      .reqs {
        gap: 8px;
      }

      .req {
        font-size: 10.5px;
        padding: 7px 9px;
      }
    }

    /* Make buttons full width where needed */
    @media (max-width: 600px) {
      .btn-full {
        border-radius: 14px;
      }

      .btn-mini {
        padding: 8px 9px;
        border-radius: 12px;
      }
    }

    /* ===== Table -> Mobile card rows =====
   Keeps desktop table as-is.
*/
    @media (max-width: 780px) {
      .table {
        border-spacing: 0 12px;
      }

      .table thead {
        display: none;
      }

      /* Turn each row into a card block */
      .table tbody tr.tr {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
        padding: 12px;
      }

      /* Each cell becomes a line */
      .table tbody tr.tr td {
        padding: 0;
      }

      /* Amount align left on mobile */
      .amt {
        text-align: left;
      }

      /* Add small labels before fields using nth-child */
      .table tbody tr.tr td:nth-child(2)::before {
        content: "Type";
        display: block;
        font-size: 10px;
        color: var(--text-muted);
        font-weight: 1000;
        margin-bottom: 4px;
      }

      .table tbody tr.tr td:nth-child(3)::before {
        content: "Date / Status";
        display: block;
        font-size: 10px;
        color: var(--text-muted);
        font-weight: 1000;
        margin-bottom: 4px;
      }

      .table tbody tr.tr td:nth-child(4)::before {
        content: "Amount";
        display: block;
        font-size: 10px;
        color: var(--text-muted);
        font-weight: 1000;
        margin-bottom: 4px;
      }

      /* Action button align left */
      .table tbody tr.tr td:nth-child(5) {
        text-align: left !important;
      }
    }

    /* Filters: stack clean on mobile */
    @media (max-width: 780px) {
      .filters {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
        align-items: stretch;
      }

      .filters .inp,
      .filters .sel,
      .filters .btn-soft {
        width: 100%;
      }
    }

    /* Modal: fit small screens */
    @media (max-width: 600px) {
      #payoutModal>div {
        width: calc(100% - 24px);
        margin: 18% auto !important;
        border-radius: 18px !important;
      }

      #payoutModalBody {
        max-height: 65vh !important;
      }
    }

    /* Right panel layout safety (if your template uses fixed widths) */
    @media (max-width: 1100px) {
      .right-panel {
        display: none;
      }

      /* optional: remove if you want it visible */
    }

    @media (max-width: 1100px) {
      .right-panel {
        display: block;
      }
    }

    /* ===== UNIVERSAL: Table -> Card rows (works for any page) ===== */
    @media (max-width: 780px) {

      table.resp-card {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 12px;
      }

      table.resp-card thead {
        display: none !important;
      }

      table.resp-card tbody,
      table.resp-card tr,
      table.resp-card td {
        display: block;
        width: 100%;
      }

      /* Each row becomes a card */
      table.resp-card tbody tr {
        background: #fff;
        border: 1px solid #f5f5f7;
        border-radius: 18px;
        box-shadow: 0 12px 25px rgba(0, 0, 0, 0.03);
        padding: 12px;
        margin-bottom: 12px;
      }

      table.resp-card tbody td {
        padding: 8px 0 !important;
        border: 0 !important;
      }

      /* label + value layout */
      table.resp-card tbody td {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
      }

      /* label text from data-label */
      table.resp-card tbody td::before {
        content: attr(data-label);
        font-size: 10px;
        color: var(--text-muted);
        font-weight: 1000;
        line-height: 1.2;
        padding-top: 2px;
        flex: 0 0 90px;
        max-width: 90px;
      }

      /* value area */
      table.resp-card tbody td>* {
        flex: 1 1 auto;
      }

      /* If a td is empty (like actions), keep it aligned */
      table.resp-card tbody td:empty {
        display: none;
      }
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
          <h2><i class="ph ph-bank"></i> Payouts</h2>
        </div>
        <div class="actions">
          <button class="btn-soft" type="button" onclick="location.href='<?= base_url('user/profit'); ?>'"><i
              class="ph ph-coins"></i> Commissions</button>
          <button class="btn-soft" type="button" onclick="window.print()"><i class="ph ph-printer"></i> Print</button>
          <button class="btn-main" type="button" onclick="exportCsv()"><i class="ph ph-download"></i> Export</button>
          <button class="btn-dark" type="button" onclick="scrollToWithdraw()"><i class="ph ph-arrow-down"></i>
            Withdraw</button>
        </div>
      </div>

      <!-- KPI Cards -->
      <div class="kpis">
        <div class="kpi">
          <div class="ic" style="background:#ecfdf3;color:#0f9d58;"><i class="ph ph-wallet"></i></div>
          <div>
            <small>Available BMAN Balance</small>
            <strong id="kpi-available">
              <?= currency_format((float) ($payout->available_amount ?? $wallet_balance ?? 0), 2); ?></strong>
            <span>Withdraw anytime</span>
          </div>
        </div>

        <div class="kpi">
          <div class="ic" style="background:#fff7ed;color:#c2410c;"><i class="ph ph-hourglass"></i></div>
          <div>
            <small>Pending for Payout</small>
            <strong id="kpi-pending">
              <?= currency_format((float) ($payout->pending_amount ?? 0), 2); ?></strong>
            <span>Next cycle: <?= htmlspecialchars($payout->next_date ?? '—'); ?></span>
          </div>
        </div>

        <div class="kpi">
          <div class="ic" style="background:#eff6ff;color:#2563eb;"><i class="ph ph-hand-coins"></i></div>
          <div>
            <small>Total Paid Out</small>
            <strong id="kpi-paid">
              <?= currency_format((float) ($payout->paid_total ?? 0), 2); ?></strong>
            <span>Transferred to bank</span>
          </div>
        </div>

        <div class="kpi">
          <div class="ic" style="background:#efedfb;color:var(--primary);"><i class="ph ph-calendar-check"></i></div>
          <div>
            <small>Next Payout Time</small>
            <strong><?= htmlspecialchars($payout->next_date ?? '—'); ?></strong>
            <span>Auto processing</span>
          </div>
        </div>
      </div>

      <?php
        $wallet_usdt = (float) ($wallet_usdt ?? 0);
        $wallet_bman = $wallet_bman ?? ['exchange' => 0, 'earning' => 0, 'staking' => 0, 'bonus' => 0];
      ?>
      <style>
        .allw-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin:0 0 18px;}
        .allw-strip .wtile{display:flex;align-items:center;gap:12px;background:#fff;border:1px solid rgba(15,23,42,.08);
          border-radius:16px;padding:14px 16px;box-shadow:0 6px 18px rgba(15,23,42,.04);}
        .allw-strip .wtile.usdt{border:1.5px solid #26a17b55;background:linear-gradient(135deg,#26a17b0d,#fff);}
        .allw-strip .wico{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;font-size:20px;flex:0 0 auto;}
        .allw-strip .wlbl{font-size:11.5px;font-weight:900;color:#6b7280;text-transform:uppercase;letter-spacing:.3px;
          display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
        .allw-strip .wval{font-size:18px;font-weight:900;color:#0b1220;line-height:1.1;}
        .allw-strip .wval small{font-size:11px;font-weight:900;color:#6b7280;}
        .allw-strip .wtag{font-size:9px;font-weight:900;letter-spacing:.2px;padding:2px 7px;border-radius:99px;
          background:#26a17b1a;color:#1b8f6b;text-transform:uppercase;}
      </style>
      <div class="allw-strip">
        <div class="wtile usdt">
          <div class="wico" style="background:#26a17b1a;color:#26a17b;"><i class="ph ph-currency-circle-dollar"></i></div>
          <div>
            <div class="wlbl">USDT Wallet <span class="wtag">Staking Source</span></div>
            <div class="wval" id="wallet-usdt-val"><?= number_format($wallet_usdt, 2); ?> <small>USDT</small></div>
          </div>
        </div>
        <div class="wtile">
          <div class="wico" style="background:#6366f11a;color:#6366f1;"><i class="ph ph-swap"></i></div>
          <div>
            <div class="wlbl">Exchange Wallet</div>
            <div class="wval" id="wallet-exchange-val"><?= number_format((float)($wallet_bman['exchange'] ?? 0), 4); ?> <small>BMAN</small></div>
          </div>
        </div>
        <div class="wtile">
          <div class="wico" style="background:#0ea5e91a;color:#0ea5e9;"><i class="ph ph-trend-up"></i></div>
          <div>
            <div class="wlbl">Earning Wallet</div>
            <div class="wval" id="wallet-earning-val"><?= number_format((float)($wallet_bman['earning'] ?? 0), 4); ?> <small>BMAN</small></div>
          </div>
        </div>
        <div class="wtile">
          <div class="wico" style="background:#10b9811a;color:#10b981;"><i class="ph ph-lock-key"></i></div>
          <div>
            <div class="wlbl">Staking Wallet</div>
            <div class="wval" id="wallet-staking-val"><?= number_format((float)($wallet_bman['staking'] ?? 0), 4); ?> <small>BMAN</small></div>
          </div>
        </div>
        <div class="wtile">
          <div class="wico" style="background:#f59e0b1a;color:#f59e0b;"><i class="ph ph-gift"></i></div>
          <div>
            <div class="wlbl">Bonus Wallet</div>
            <div class="wval" id="wallet-bonus-val"><?= number_format((float)($wallet_bman['bonus'] ?? 0), 4); ?> <small>BMAN</small></div>
          </div>
        </div>
      </div>

      <div class="grid-2">
        <!-- Left: Withdraw Request + History -->
        <div>
          <!-- Withdraw -->
          <div class="card" id="withdrawBox">
            <div class="card-h">
              <h3>Withdraw Request</h3>
              <span class="chip"><i class="ph ph-shield-check"></i> Secure</span>
            </div>

            <div class="reqs" style="margin-bottom:12px;">
              <span class="req <?= !empty($payout->eligibility) ? 'ok' : 'bad'; ?>"><i class="ph ph-seal-check"></i>
                <?= !empty($payout->eligibility) ? 'Eligible' : 'Not Eligible'; ?></span>
              <span class="req <?= !empty($payout->kyc) ? 'ok' : 'warn'; ?>"><i
                  class="ph ph-identification-card"></i>
                <?= !empty($payout->kyc) ? 'KYC Verified' : 'KYC Pending'; ?></span>
              <span class="req"><i class="ph ph-receipt"></i> Minimum Withdrawal:
                <?= currency_format((float) ($payout->min_withdraw ?? 0), 2); ?></span>
              <span class="req"><i class="ph ph-coins"></i> Min BMAN:
                <?= number_format((float) ($min_bman_required ?? 0), 4); ?></span>
            </div>

            <form id="withdrawForm" class="form" method="post" action="<?= base_url('user/bman-withdraw/request'); ?>">
              <input type="hidden" name="platform_address" id="platform_address" value="<?= htmlspecialchars($platform_address ?? ''); ?>" />

              <div class="row2">
                <div class="field">
                  <label>BMAN Amount *</label>
                  <input class="inp" type="number" step="0.0001" min="0" id="bman_amount" name="withdraw_bman" placeholder="Enter BMAN amount"
                    required>
                  <div class="hint">Available:
                    <?= number_format((float) ($payout->available_amount ?? $wallet_balance ?? 0), 4); ?> BMAN
                  </div>
                  <div class="hint" id="withdraw_amount_hint" style="color:#c2410c;"></div>
                </div>
                <div class="field">
                  <label>Destination Wallet Address *</label>
                  <input class="inp" type="text" name="wallet_address" placeholder="Enter USDT Wallet Address" minlength="20" maxlength="120" required>
                  <div class="hint">Required. Must be different from your platform custodial address.</div>
                </div>
              </div>

              <div class="row2">
                <div class="field">
                  <label>Processing Fee</label>
                  <input class="inp" type="text"
                    value="<?= number_format((float) ($payout->processing_fee ?? 0), 4); ?> USDT"
                    readonly>
                </div>
                <div class="field">
                  <label>Estimated USDT Payout</label>
                  <input class="inp" type="text" id="estimated_usdt" value="" readonly>
                  <div class="hint">Net payout after processing fee.</div>
                  <div class="hint" id="fee_warning" style="color:#c2410c;"></div>
                </div>
              </div>

              <div class="field">
                <label>Remark (optional)</label>
                <input class="inp" type="text" name="remark" placeholder="e.g., Manual review withdrawal">
                <div class="hint">Shown in your payout history.</div>
              </div>

              <button class="btn-full primary" type="submit" <?= empty($payout->eligibility) ? 'disabled style="opacity:.55;cursor:not-allowed;"' : '' ?>>
                Submit Withdrawal <i class="ph ph-arrow-circle-right"></i>
              </button>

              <?php if (empty($payout->eligibility)): ?>
                <div class="hint" style="margin-top:10px;">
                  Not Eligible
                </div>
              <?php endif; ?>
            </form>
          </div>

          <!-- History -->
          <div class="card" style="margin-top:14px;">
            <div class="card-h">
              <h3>Payout History</h3>
              <span class="chip"><i class="ph ph-clock-counter-clockwise"></i> Records</span>
            </div>

            <div class="filters">
              <input class="inp" id="q" placeholder="Search: payout id, period, note..." />
              <select class="sel" id="status">
                <option value="">All Status</option>
                <option value="PAID">Paid</option>
                <option value="PROCESSING">Processing</option>
                <option value="PENDING">Pending</option>
                <option value="REJECTED">Rejected</option>
              </select>
              <select class="sel" id="type">
                <option value="">All Types</option>
                <option value="WEEKLY">Weekly</option>
                <option value="MONTHLY">Monthly</option>
                <option value="MANUAL">Manual</option>
              </select>
              <button class="btn-soft" type="button" onclick="resetFilters()"><i class="ph ph-x-circle"></i>
                Reset</button>
            </div>

            <div class="table-wrap" style="margin-top:10px; overflow:auto;">
              <table class="table table resp-card" id="pTable">
                <thead>
                  <tr>
                    <th style="width:32%;">Payout</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th style="text-align:right;">Amount</th>
                    <th style="width:90px;"></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($payouts)):
                    foreach ($payouts as $p): ?>
                      <?php
                      $st = strtoupper($p->status ?? 'PENDING');

                      $badge = $st === 'APPROVED' ? 'b-ok' : (($st === 'PROCESSING' || $st === 'PENDING') ? 'b-warn' : 'b-bad');
                      $tp = strtoupper($p->type ?? 'WEEKLY');
                      ?>
                      <tr class="tr"
                        data-q="<?= htmlspecialchars(strtolower(($p->payout_id ?? '') . ' ' . ($p->period ?? '') . ' ' . ($p->note ?? ''))); ?>"
                        data-status="<?= htmlspecialchars($st); ?>" data-type="<?= htmlspecialchars($tp); ?>">
                        <td class="td-title">
                          <b><?= htmlspecialchars($p->payout_id ?? '—'); ?></b>
                          <small><?= htmlspecialchars($p->period ?? '—'); ?> •
                            <?= htmlspecialchars($p->note ?? ''); ?></small>
                        </td>
                        <td><span class="badge"><i class="ph ph-tag"></i> <?= $tp; ?></span></td>
                        <td>
                          <?= htmlspecialchars($p->date ?? '—'); ?>
                          <div style="margin-top:6px;"><span class="badge <?= $badge; ?>"><i class="ph ph-seal-check"></i>
                              <?= $st; ?></span></div>
                        </td>
                        <td class="amt">
                          <?= currency_info()->currency_symbol; ?>     <?= number_format((float) ($p->amount ?? 0), 2); ?>
                          <div style="margin-top:6px;font-size:11px;color:var(--text-muted);font-weight:900;">
                            Fee: <?= currency_info()->currency_symbol; ?>     <?= number_format((float) ($p->fee ?? 0), 2); ?>
                          </div>
                        </td>
                        <td style="text-align:right;">
                          <button class="btn-mini" type="button"
                            onclick="viewPayout('<?= htmlspecialchars($p->payout_id ?? ''); ?>')"><i
                              class="ph ph-eye"></i></button>
                        </td>
                      </tr>
                    <?php endforeach; else: ?>
                    <tr>
                      <td colspan="5">
                        <div class="empty">No payout history found.</div>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Right: Insights -->
        <div class="side-grid">
          <div class="card">
            <div class="card-h">
              <h3>Insights</h3>
              <span class="chip"><i class="ph ph-lightbulb"></i> Tips</span>
            </div>

            <div class="pillx">
              <div>
                <b>Next Payout</b>
                <div><span><?= htmlspecialchars($payout->next_date ?? '—'); ?></span></div>
              </div>
              <i class="ph ph-calendar-check" style="color:var(--primary);font-size:18px;"></i>
            </div>

            <div class="pillx" style="margin-top:10px;">
              <div>
                <b>Eligibility</b>
                <div><span><?= !empty($payout->eligibility) ? 'Eligible' : 'Not Eligible'; ?></span></div>
              </div>
              <i class="ph ph-shield-check"
                style="color:<?= !empty($payout->eligibility) ? '#0f9d58' : '#c2410c'; ?>;font-size:18px;"></i>
            </div>

            <div class="pillx" style="margin-top:10px;">
              <div>
                <b>Minimum Withdraw</b>
                <div><span><?= currency_info()->currency_symbol; ?>
                    <?= number_format((float) ($payout->min_withdraw ?? 0), 2); ?></span></div>
              </div>
              <i class="ph ph-receipt" style="color:var(--primary);font-size:18px;"></i>
            </div>

            <div class="hint" style="margin-top:10px;">
              Tip: Keep your KYC + wallet address updated to avoid payout delays.
            </div>

            <div class="cta">
              <button class="btn-full dark" type="button" onclick="location.href='<?= base_url('user/kyc'); ?>'">
                Update KYC <i class="ph ph-identification-card"></i>
              </button>
              <button class="btn-full" type="button" onclick="location.href='<?= base_url('user/support'); ?>'">
                Support Ticket <i class="ph ph-headset"></i>
              </button>
            </div>
          </div>

          <div class="card">
            <div class="card-h">
              <h3>Auto Payout Cycle</h3>
              <span class="chip"><i class="ph ph-clock"></i> Weekly</span>
            </div>
            <div class="hint" style="margin-top:4px;">
              Weekly commissions are processed automatically. Manual withdrawals follow admin rules.
            </div>

            <div style="margin-top:12px;display:grid;gap:10px;">
              <div class="pillx">
                <div><b>Cycle Close</b>
                  <div><span>Every Sunday (example)</span></div>
                </div>
                <i class="ph ph-hourglass" style="color:var(--primary);font-size:18px;"></i>
              </div>
              <div class="pillx">
                <div><b>Processing</b>
                  <div><span>Mon–Tue</span></div>
                </div>
                <i class="ph ph-gear" style="color:var(--primary);font-size:18px;"></i>
              </div>
              <div class="pillx">
                <div><b>Transfer</b>
                  <div><span>Tue 10:00 PM</span></div>
                </div>
                <i class="ph ph-paper-plane-tilt" style="color:var(--primary);font-size:18px;"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <aside class="right-panel">
      <?php $this->load->view('user/layout/v2/user_inner_right_panle'); ?>
    </aside>
  </div>

  <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>
  <script>
    // ===== BMAN -> USDT estimate =====
    const fee = <?= json_encode((float) ($payout->processing_fee ?? 0)); ?>;
    const bmanPrice = <?= json_encode((float) ($bman_price ?? 0)); ?>;
    const sym = <?= json_encode(currency_info()->currency_symbol); ?>;
    const amountInput = document.getElementById('bman_amount');
    const addressInput = document.querySelector('input[name="wallet_address"]');
    const estimatedEl = document.getElementById('estimated_usdt');
    const amountHintEl = document.getElementById('withdraw_amount_hint');
    const feeWarningEl = document.getElementById('fee_warning');
    const submitBtn = document.querySelector('#withdrawForm button[type="submit"]');
    if (amountInput) {
      const calc = () => {
        const raw = (amountInput.value || "").trim();
        const v = raw === "" ? 0 : Number(raw);
        const gross = Math.max(0, v * bmanPrice);
        const net = Math.max(0, gross - fee);
        if (estimatedEl) estimatedEl.value = net.toFixed(8) + " USDT";
        if (amountHintEl) {
          if (v > 0 && gross <= fee) {
            amountHintEl.textContent = `Enter more than ${fee.toFixed(4)} USDT worth of BMAN so the fee can be covered.`;
          } else if (v > 0) {
            amountHintEl.textContent = `Estimated payout: ${net.toFixed(8)} USDT after fee.`;
          } else {
            amountHintEl.textContent = "";
          }
        }
        if (feeWarningEl) {
          feeWarningEl.textContent = (v > 0 && gross <= fee)
            ? `Below fee threshold: the converted amount must be greater than ${fee.toFixed(4)} USDT.`
            : '';
        }
        refreshEligibility(gross, net);
      };
      amountInput.addEventListener('input', calc);
      amountInput.addEventListener('change', calc);
      amountInput.addEventListener('blur', calc);
      calc();
    }

    function refreshEligibility(grossOverride, netOverride) {
      if (!submitBtn) return;
      const available = <?= json_encode((float) ($payout->available_amount ?? $wallet_balance ?? 0)); ?>;
      const minWithdraw = <?= json_encode((float) ($payout->min_withdraw ?? 0)); ?>;
      const hasAddress = !!(addressInput && addressInput.value.trim().length >= 20);
      const kycOk = <?= json_encode(!empty($payout->kyc)); ?>;
      const activeOk = <?= json_encode(!empty($user) && (empty($user->status) || in_array(strtolower((string) $user->status), ['active', '1', 'approved'], true))); ?>;
      const amountRaw = amountInput ? (amountInput.value || '').trim() : '';
      const amount = amountRaw === '' ? 0 : Number(amountRaw);
      const gross = typeof grossOverride === 'number' ? grossOverride : Math.max(0, amount * bmanPrice);
      const net = typeof netOverride === 'number' ? netOverride : Math.max(0, gross - fee);
      const eligible = kycOk && activeOk && hasAddress && amount > 0 && amount <= available && gross > fee && net > 0 && gross >= minWithdraw;
      submitBtn.disabled = !eligible;
      submitBtn.style.opacity = eligible ? "" : ".55";
      submitBtn.style.cursor = eligible ? "pointer" : "not-allowed";
    }
    if (addressInput) addressInput.addEventListener('input', refreshEligibility);
    refreshEligibility();

    // ===== History filters =====
    const q = document.getElementById('q');
    const st = document.getElementById('status');
    const tp = document.getElementById('type');
    const rows = () => Array.from(document.querySelectorAll('#pTable tbody .tr'));

    function applyFilters() {
      const s = (q.value || "").trim().toLowerCase();
      const ss = (st.value || "").trim();
      const tt = (tp.value || "").trim();
      rows().forEach(r => {
        const okQ = !s || (r.dataset.q || "").includes(s);
        const okS = !ss || r.dataset.status === ss;
        const okT = !tt || r.dataset.type === tt;
        r.style.display = (okQ && okS && okT) ? "" : "none";
      });
    }
    if (q) { q.addEventListener('input', applyFilters); }
    if (st) { st.addEventListener('change', applyFilters); }
    if (tp) { tp.addEventListener('change', applyFilters); }

    function resetFilters() {
      if (q) q.value = "";
      if (st) st.value = "";
      if (tp) tp.value = "";
      applyFilters();
    }

    function scrollToWithdraw() {
      const el = document.getElementById('withdrawBox');
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function refreshWalletSnapshot() {
      const btn = document.querySelector('.actions .btn-soft[onclick*="refreshWalletSnapshot"]');
      const old = btn ? btn.innerHTML : '';
      if (btn) { btn.disabled = true; btn.innerHTML = '<i class="ph ph-spinner-gap"></i> Refreshing...'; }
      fetch('<?= base_url('member/profile/wallet_check'); ?>', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      }).then(r => r.json()).then(data => {
        if (data.status !== 'success') throw new Error(data.message || 'Refresh failed');
        if (data.data) {
          const onchainUsdt = document.getElementById('onchain-usdt');
          const dbUsdt = document.getElementById('db-usdt');
          const diffUsdt = document.getElementById('usdt-diff');
          const onchainBnb = document.getElementById('onchain-bnb');
          const onchainBman = document.getElementById('onchain-bman');
          if (onchainUsdt) onchainUsdt.textContent = data.data.onchain_usdt ?? '0';
          if (dbUsdt) dbUsdt.textContent = data.data.db_usdt ?? '0';
          if (diffUsdt) diffUsdt.textContent = data.data.difference ?? '0';
          if (onchainBnb) onchainBnb.textContent = data.data.onchain_bnb ?? '0';
          if (onchainBman) onchainBman.textContent = data.data.onchain_bman ?? '0';
          const state = document.getElementById('wallet-sync-state');
          if (state) state.textContent = 'Updated just now';
        }
        toastMini(data.message || 'Wallet refreshed');
      }).catch(err => {
        toastMini(err.message || 'Could not refresh wallet');
      }).finally(() => {
        if (btn) { btn.disabled = false; btn.innerHTML = old; }
      });
    }

    function viewPayout(id) {
      if (!id) { toastMini("No payout id"); return; }
      // Change route as per your project
      location.href = "<?= base_url('user/payouts/view/'); ?>" + encodeURIComponent(id);
    }

    function exportCsv() {
      // UI stub: connect to your real export endpoint
      location.href = "<?= base_url('user/payouts/export'); ?>";
    }

    function toastMini(msg) {
      const t = document.createElement('div');
      t.textContent = msg;
      t.style.cssText =
        "position:fixed;bottom:22px;left:50%;transform:translateX(-50%);background:#111;color:#fff;padding:10px 14px;border-radius:14px;font-weight:1000;font-size:12px;z-index:99999;opacity:0;transition:.2s;";
      document.body.appendChild(t);
      requestAnimationFrame(() => t.style.opacity = "1");
      setTimeout(() => { t.style.opacity = "0"; setTimeout(() => t.remove(), 250); }, 1400);
    }
  </script>

  <script>
    (function () {
      const form = document.getElementById('withdrawForm');
      if (!form) return;

      form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const btn = form.querySelector('button[type="submit"]');
        const oldText = btn ? btn.innerHTML : "";
        if (btn) { btn.disabled = true; btn.style.opacity = ".7"; btn.innerHTML = "Submitting..."; }

        const fd = new FormData(form);
        try {
          const res = await fetch(form.action, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          });

          const data = await res.json().catch(() => null);
          if (!data) throw new Error("Invalid server response");

          if (!data.status) {
            toastMini(data.message || "Failed");
            return;
          }

          toastMini(data.message || "Withdraw request submitted");

          // ✅ Clear inputs
          const amountInput = form.querySelector('input[name="withdraw_bman"]');
          const remarkInput = form.querySelector('input[name="remark"]');
          if (amountInput) amountInput.value = "";
          if (remarkInput) remarkInput.value = "";
          if (typeof applyFilters === "function") applyFilters(); // keep your filters safe

          // ✅ Update KPI cards if you want (optional)
          // If you have elements to update, you can bind IDs and update here.
          // For now, we refresh the page OR rebuild table.
          const kAvail = document.getElementById('kpi-available');
          if (typeof data.available_balance !== "undefined" && kAvail) {
            kAvail.textContent = Number(data.available_balance || 0).toFixed(4) + " BMAN";
          }
          if (data.history && Array.isArray(data.history)) {
            rebuildPayoutTable(data.history);
          }

        } catch (err) {
          toastMini(err.message || "Server error");
        } finally {
          if (btn) { btn.disabled = false; btn.style.opacity = ""; btn.innerHTML = oldText; }
        }
      });

      // ✅ Rebuild payout history table (keeps your filters working)
      function rebuildPayoutTable(payouts) {
        const tbody = document.querySelector('#pTable tbody');
        if (!tbody) return;

        if (!payouts.length) {
          tbody.innerHTML = `<tr><td colspan="5"><div class="empty">No payout history found.</div></td></tr>`;
          return;
        }

        tbody.innerHTML = payouts.map(p => {
          const st = (p.status || "PENDING").toUpperCase();
          const badge = st === 'PAID' ? 'b-ok' : ((st === 'PROCESSING' || st === 'PENDING') ? 'b-warn' : 'b-bad');
          const tp = (p.type || "MANUAL").toUpperCase();
          const q = ((p.payout_id || "") + " " + (p.period || "") + " " + (p.note || "")).toLowerCase()
            .replace(/"/g, '&quot;');

          return `
          <tr class="tr"
            data-q="${q}"
            data-status="${escapeHtml(st)}"
            data-type="${escapeHtml(tp)}">
            <td class="td-title">
              <b>${escapeHtml(p.payout_id || "—")}</b>
              <small>${escapeHtml(p.period || "—")} • ${escapeHtml(p.note || "")}</small>
            </td>
            <td><span class="badge"><i class="ph ph-tag"></i> ${escapeHtml(tp)}</span></td>
            <td>
              ${escapeHtml(p.date || "—")}
              <div style="margin-top:6px;"><span class="badge ${badge}"><i class="ph ph-seal-check"></i> ${escapeHtml(st)}</span></div>
            </td>
            <td class="amt">
              ${escapeHtml(p.currency_symbol || "")} ${toMoney(p.amount)}
              <div style="margin-top:6px;font-size:11px;color:var(--text-muted);font-weight:900;">
                Fee: ${escapeHtml(p.currency_symbol || "")} ${toMoney(p.fee)}
              </div>
            </td>
            <td style="text-align:right;">
              <button class="btn-mini" type="button" onclick="viewPayout('${escapeHtml(p.payout_id || "")}')"><i class="ph ph-eye"></i></button>
            </td>
          </tr>
        `;
        }).join('');

        // re-apply current filters if user typed something
        if (typeof applyFilters === "function") applyFilters();
      }

      function toMoney(v) {
        const n = parseFloat(v || "0") || 0;
        return n.toFixed(2);
      }
      function escapeHtml(str) {
        return String(str ?? '')
          .replaceAll('&', '&amp;')
          .replaceAll('<', '&lt;')
          .replaceAll('>', '&gt;')
          .replaceAll('"', '&quot;')
          .replaceAll("'", "&#039;");
      }
    })();
  </script>
  <script>
    const PAYOUT_DATA = <?= json_encode($payouts ?? [], JSON_UNESCAPED_UNICODE); ?>;
  </script>
  <!-- ===== PAYOUT DETAILS MODAL ===== -->
  <div id="payoutModal" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.55);">
    <div
      style="max-width:520px;margin:6% auto;background:#fff;border-radius:22px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,.25);">

      <div
        style="padding:14px 18px;border-bottom:1px solid #f1f1f6;display:flex;justify-content:space-between;align-items:center;">
        <b style="font-size:14px;">Withdrawal Details</b>
        <button onclick="closePayoutModal()"
          style="border:none;background:none;font-size:20px;cursor:pointer;">×</button>
      </div>

      <div id="payoutModalBody" style="padding:16px;display:grid;gap:10px;max-height:70vh;overflow:auto;">
        <!-- content injected by JS -->
      </div>

      <div style="padding:12px 16px;border-top:1px solid #f1f1f6;text-align:right;">
        <button class="btn-soft" onclick="closePayoutModal()">Close</button>
      </div>

    </div>
  </div>

  <script>
    function viewPayout(payoutId) {
      const modal = document.getElementById('payoutModal');
      const body = document.getElementById('payoutModalBody');

      const p = PAYOUT_DATA.find(x => x.payout_id === payoutId);
      if (!p) {
        toastMini("Withdrawal not found");
        return;
      }

      body.innerHTML = `
    <div class="pillx"><b>Payout ID</b><span>${esc(p.payout_id)}</span></div>
    <div class="pillx"><b>Transaction ID</b><span>${esc(p.txn_id || '—')}</span></div>

    <div class="pillx"><b>Status</b><span>${esc(p.status)}</span></div>
    <div class="pillx"><b>Method</b><span>${esc(p.method)}</span></div>
    <div class="pillx"><b>Type</b><span>${esc(p.type)}</span></div>
    <div class="pillx"><b>Period</b><span>${esc(p.period)}</span></div>

    <div class="pillx"><b>Requested Amount</b><span>₹ ${money(p.amount)}</span></div>
    <div class="pillx"><b>Fee</b><span>₹ ${money(p.fee)}</span></div>
    <div class="pillx"><b>Net Amount</b><span>₹ ${money(p.net_amount)}</span></div>

    <div class="pillx"><b>Requested At</b><span>${esc(p.created_at)}</span></div>    

    <div class="pillx"><b>Admin Review</b><span>${esc(p.admin_review || '—')}</span></div>    
    <div class="pillx"><b>Approved At</b><span>${esc(p.approved_at || '—')}</span></div>

    ${p.admin_proof_img ? `
      <div class="pillx">
        <b>Admin Proof</b>
        <span>
          <a href="${p.admin_proof_img}" target="_blank">
            <img src="${p.admin_proof_img}" style="max-width:120px;border-radius:10px;border:1px solid #eee;">
          </a>
        </span>
      </div>` : `
      <div class="pillx"><b>Admin Proof</b><span>—</span></div>
    `}
  `;

      modal.style.display = "block";
    }

    function closePayoutModal() {
      document.getElementById('payoutModal').style.display = "none";
    }

    function money(v) {
      return (parseFloat(v || 0)).toFixed(2);
    }

    function esc(str) {
      return String(str ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", "&#039;");
    }
  </script>

</body>

</html>
