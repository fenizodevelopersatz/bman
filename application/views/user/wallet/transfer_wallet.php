<?php
// Expected vars from controller:
// $internal_balances  — ['exchange'=>float,'earning'=>float,'staking'=>float,'bonus'=>float]
// $has_transfer_password — bool
// $history            — array of wallet_internal_transfer rows
// $total, $page, $pages, $per_page
// $allowed_pairs_json — JSON string
// $from_filter, $to_filter, $status_filter, $date_from, $date_to
// $user               — ['username','email',...]
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php $this->load->view('user/layout/v2/user_style'); ?>
  <style>
    /* ===================== WALLET TRANSFER PAGE ===================== */
    :root {
      --wt-exchange: #6D4AFF;
      --wt-earning:  #0ea5e9;
      --wt-staking:  #10b981;
      --wt-bonus:    #f59e0b;
      --card-bg: #fff;
      --card-border: #f0eeff;
      --radius-lg: 24px;
      --radius-md: 16px;
      --radius-sm: 12px;
    }
    html[data-bs-theme="dark"] {
      --card-bg: #1a1a2e;
      --card-border: #2d2d4e;
    }

    .page-titlebar {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 12px;
      margin: 8px 0 20px;
      flex-wrap: wrap;
    }
    .page-titlebar h2 {
      font-size: 20px;
      font-weight: 900;
      color: var(--text-main);
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 0;
    }
    .page-titlebar h2 i { color: var(--primary); font-size: 22px; }
    .page-titlebar .sub { margin-top: 4px; color: var(--text-muted); font-size: 12px; }

    /* ─── Wallet Balance Cards ─── */
    .wallet-tiles {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
      margin-bottom: 20px;
    }
    .wallet-tile {
      background: var(--card-bg);
      border: 1.5px solid var(--card-border);
      border-radius: var(--radius-lg);
      padding: 18px 16px 16px;
      position: relative;
      overflow: hidden;
      transition: transform .18s, box-shadow .18s;
      box-shadow: 0 6px 24px rgba(0,0,0,.04);
    }
    .wallet-tile:hover { transform: translateY(-3px); box-shadow: 0 14px 36px rgba(0,0,0,.08); }
    .wallet-tile::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 4px;
      border-radius: 99px 99px 0 0;
    }
    .wallet-tile.exchange::before { background: var(--wt-exchange); }
    .wallet-tile.earning::before  { background: var(--wt-earning); }
    .wallet-tile.staking::before  { background: var(--wt-staking); }
    .wallet-tile.bonus::before    { background: var(--wt-bonus); }

    .wallet-tile .wt-icon {
      width: 42px; height: 42px;
      border-radius: 14px;
      display: grid; place-items: center;
      font-size: 20px;
      margin-bottom: 12px;
    }
    .wallet-tile.exchange .wt-icon { background: rgba(109,74,255,.12); color: var(--wt-exchange); }
    .wallet-tile.earning .wt-icon  { background: rgba(14,165,233,.12); color: var(--wt-earning); }
    .wallet-tile.staking .wt-icon  { background: rgba(16,185,129,.12); color: var(--wt-staking); }
    .wallet-tile.bonus .wt-icon    { background: rgba(245,158,11,.12);  color: var(--wt-bonus); }

    .wallet-tile .wt-label {
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: var(--text-muted);
    }
    .wallet-tile .wt-amount {
      font-size: 22px;
      font-weight: 900;
      color: var(--text-main);
      margin: 4px 0 2px;
      letter-spacing: -.5px;
    }
    .wallet-tile .wt-sub {
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 700;
    }

    /* ─── Two-column layout ─── */
    .transfer-layout {
      display: grid;
      grid-template-columns: 420px 1fr;
      gap: 18px;
      align-items: start;
    }

    /* ─── Form Card ─── */
    .form-card {
      background: var(--card-bg);
      border: 1.5px solid var(--card-border);
      border-radius: var(--radius-lg);
      box-shadow: 0 8px 32px rgba(109,74,255,.07);
      overflow: hidden;
    }
    .form-card-header {
      background: linear-gradient(135deg, var(--primary) 0%, var(--mp-accent, #a855f7) 100%);
      padding: 20px 22px 18px;
      color: #fff;
    }
    .form-card-header h3 {
      font-size: 16px;
      font-weight: 900;
      margin: 0 0 4px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .form-card-header p {
      font-size: 12px;
      margin: 0;
      opacity: .8;
    }
    .form-card-body { padding: 22px; }

    /* ─── Custom select ─── */
    .wt-select-row {
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      gap: 10px;
      align-items: end;
      margin-bottom: 18px;
    }
    .wt-select-arrow {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      background: linear-gradient(135deg, var(--primary), var(--mp-accent, #a855f7));
      border-radius: 50%;
      color: #fff;
      font-size: 16px;
      flex-shrink: 0;
      margin-bottom: 2px;
      cursor: pointer;
      transition: transform .2s;
    }
    .wt-select-arrow:hover { transform: rotate(180deg); }

    .wt-field-label {
      display: block;
      font-size: 11px;
      font-weight: 800;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: .06em;
      margin-bottom: 6px;
    }
    .wt-select {
      width: 100%;
      padding: 11px 14px;
      border: 1.5px solid var(--card-border);
      border-radius: var(--radius-sm);
      background: var(--card-bg);
      color: var(--text-main);
      font-weight: 800;
      font-size: 13px;
      outline: none;
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      padding-right: 32px;
      transition: border-color .18s;
    }
    .wt-select:focus { border-color: var(--primary); }
    .wt-select option[data-color="exchange"] { color: #6D4AFF; }
    .wt-select option[data-color="earning"]  { color: #0ea5e9; }
    .wt-select option[data-color="staking"]  { color: #10b981; }
    .wt-select option[data-color="bonus"]    { color: #f59e0b; }

    /* ─── Amount field ─── */
    .amount-wrap {
      position: relative;
      margin-bottom: 14px;
    }
    .amount-wrap input {
      width: 100%;
      padding: 13px 80px 13px 16px;
      border: 1.5px solid var(--card-border);
      border-radius: var(--radius-sm);
      font-size: 18px;
      font-weight: 900;
      color: var(--text-main);
      background: var(--card-bg);
      outline: none;
      transition: border-color .18s;
      box-sizing: border-box;
    }
    .amount-wrap input:focus { border-color: var(--primary); }
    .amount-wrap .max-btn {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: var(--primary);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 11px;
      font-weight: 900;
      padding: 5px 10px;
      cursor: pointer;
      letter-spacing: .04em;
    }
    .bal-hint {
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 700;
      margin-bottom: 14px;
      display: flex;
      gap: 6px;
      align-items: center;
    }
    .bal-hint span { font-weight: 900; color: var(--text-main); }

    /* ─── PIN field ─── */
    .pin-wrap {
      position: relative;
      margin-bottom: 14px;
    }
    .pin-wrap input {
      width: 100%;
      padding: 11px 42px 11px 14px;
      border: 1.5px solid var(--card-border);
      border-radius: var(--radius-sm);
      font-size: 14px;
      font-weight: 800;
      color: var(--text-main);
      background: var(--card-bg);
      outline: none;
      transition: border-color .18s;
      box-sizing: border-box;
      letter-spacing: .12em;
    }
    .pin-wrap input:focus { border-color: var(--primary); }
    .pin-wrap .eye-btn {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: var(--text-muted);
      font-size: 16px;
      padding: 4px;
    }

    /* ─── Note field ─── */
    .note-input {
      width: 100%;
      padding: 10px 14px;
      border: 1.5px solid var(--card-border);
      border-radius: var(--radius-sm);
      font-size: 13px;
      font-weight: 700;
      color: var(--text-main);
      background: var(--card-bg);
      outline: none;
      resize: none;
      transition: border-color .18s;
      box-sizing: border-box;
      margin-bottom: 14px;
    }
    .note-input:focus { border-color: var(--primary); }

    /* ─── Transfer preview pill ─── */
    .transfer-preview {
      background: linear-gradient(135deg, rgba(109,74,255,.07), rgba(168,85,247,.07));
      border: 1.5px solid rgba(109,74,255,.15);
      border-radius: var(--radius-sm);
      padding: 12px 16px;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 13px;
      font-weight: 800;
    }
    .transfer-preview i { font-size: 18px; color: var(--primary); }

    /* ─── Submit button ─── */
    .btn-transfer {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, var(--primary) 0%, var(--mp-accent, #a855f7) 100%);
      color: #fff;
      border: none;
      border-radius: var(--radius-md);
      font-size: 15px;
      font-weight: 900;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: opacity .18s, transform .15s;
      letter-spacing: .03em;
    }
    .btn-transfer:hover { opacity: .9; transform: translateY(-1px); }
    .btn-transfer:disabled { opacity: .5; cursor: not-allowed; transform: none; }

    /* ─── PIN not set banner ─── */
    .pin-not-set-banner {
      background: linear-gradient(135deg, #fef3c7, #fff7ed);
      border: 1.5px solid #fcd34d;
      border-radius: var(--radius-sm);
      padding: 14px 16px;
      margin-bottom: 18px;
      display: flex;
      gap: 12px;
      align-items: flex-start;
    }
    .pin-not-set-banner i { font-size: 20px; color: #d97706; flex-shrink: 0; margin-top: 1px; }
    .pin-not-set-banner strong { display: block; font-size: 13px; color: #92400e; margin-bottom: 4px; }
    .pin-not-set-banner p { font-size: 12px; color: #78350f; margin: 0; }
    .pin-not-set-banner .set-pin-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #d97706;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 900;
      padding: 6px 12px;
      cursor: pointer;
      margin-top: 8px;
      text-decoration: none;
    }

    /* ─── History card ─── */
    .history-card {
      background: var(--card-bg);
      border: 1.5px solid var(--card-border);
      border-radius: var(--radius-lg);
      box-shadow: 0 6px 24px rgba(0,0,0,.04);
      overflow: hidden;
    }
    .history-card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 18px 20px 14px;
      border-bottom: 1.5px solid var(--card-border);
      flex-wrap: wrap;
      gap: 10px;
    }
    .history-card-header h3 {
      font-size: 15px;
      font-weight: 900;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .history-card-header h3 i { color: var(--primary); }

    /* ─── Filters ─── */
    .filter-bar {
      display: flex;
      gap: 8px;
      padding: 12px 20px;
      border-bottom: 1.5px solid var(--card-border);
      flex-wrap: wrap;
      align-items: center;
    }
    .filter-bar select, .filter-bar input[type="date"] {
      padding: 8px 12px;
      border: 1.5px solid var(--card-border);
      border-radius: 10px;
      font-size: 12px;
      font-weight: 700;
      color: var(--text-main);
      background: var(--card-bg);
      outline: none;
      cursor: pointer;
    }
    .filter-bar select:focus, .filter-bar input:focus { border-color: var(--primary); }
    .filter-btn {
      padding: 8px 16px;
      border: none;
      border-radius: 10px;
      font-size: 12px;
      font-weight: 900;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: var(--primary);
      color: #fff;
    }
    .filter-reset {
      background: var(--card-bg);
      border: 1.5px solid var(--card-border);
      color: var(--text-main);
    }

    /* ─── Table ─── */
    .table-wrap { overflow-x: auto; }
    .tx-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 0;
    }
    .tx-table thead th {
      font-size: 11px;
      color: var(--text-muted);
      text-align: left;
      font-weight: 900;
      padding: 12px 16px 10px;
      white-space: nowrap;
      text-transform: uppercase;
      letter-spacing: .05em;
      border-bottom: 1.5px solid var(--card-border);
    }
    .tx-table tbody tr {
      transition: background .12s;
    }
    .tx-table tbody tr:hover { background: rgba(109,74,255,.04); }
    .tx-table tbody td {
      padding: 13px 16px;
      font-size: 13px;
      border-bottom: 1px solid var(--card-border);
      vertical-align: middle;
    }
    .wallet-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 10px;
      border-radius: 99px;
      font-size: 11px;
      font-weight: 900;
      white-space: nowrap;
    }
    .wb-exchange { background: rgba(109,74,255,.12); color: #6D4AFF; }
    .wb-earning  { background: rgba(14,165,233,.12);  color: #0ea5e9; }
    .wb-staking  { background: rgba(16,185,129,.12);  color: #10b981; }
    .wb-bonus    { background: rgba(245,158,11,.12);   color: #d97706; }

    .st-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 10px;
      border-radius: 99px;
      font-size: 11px;
      font-weight: 900;
    }
    .st-completed { background: #ecfdf3; color: #0f9d58; }
    .st-failed    { background: #fef2f2; color: #dc2626; }
    .st-reversed  { background: #fef9c3; color: #b45309; }

    .ref-text {
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 700;
      cursor: pointer;
      font-family: monospace;
    }
    .ref-text:hover { color: var(--primary); }

    .amt-cell { font-weight: 900; font-size: 14px; }

    .empty-state {
      text-align: center;
      padding: 50px 20px;
      color: var(--text-muted);
    }
    .empty-state i { font-size: 48px; opacity: .3; display: block; margin-bottom: 12px; }
    .empty-state p { font-size: 14px; font-weight: 700; }

    /* ─── Pagination ─── */
    .pagination-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 20px;
      border-top: 1.5px solid var(--card-border);
      flex-wrap: wrap;
      gap: 10px;
    }
    .pagination-bar .pg-info { font-size: 12px; color: var(--text-muted); font-weight: 700; }
    .pg-btns { display: flex; gap: 6px; }
    .pg-btn {
      padding: 6px 12px;
      border: 1.5px solid var(--card-border);
      border-radius: 8px;
      font-size: 12px;
      font-weight: 900;
      cursor: pointer;
      background: var(--card-bg);
      color: var(--text-main);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .pg-btn.active { background: var(--primary); border-color: var(--primary); color: #fff; }
    .pg-btn:hover:not(.active) { border-color: var(--primary); color: var(--primary); }

    /* ─── Modal: Set Transfer Password ─── */
    .wt-modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.45);
      z-index: 9990;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(4px);
    }
    .wt-modal-overlay.show { display: flex; }
    .wt-modal-box {
      background: var(--card-bg);
      border-radius: 24px;
      width: min(480px, calc(100vw - 32px));
      box-shadow: 0 32px 80px rgba(0,0,0,.2);
      overflow: hidden;
      animation: slideUp .25s ease;
    }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .wt-modal-head {
      background: linear-gradient(135deg, #111827, #1f2937);
      padding: 22px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      color: #fff;
    }
    .wt-modal-head h4 { margin: 0; font-size: 16px; font-weight: 900; display: flex; align-items: center; gap: 8px; }
    .wt-modal-close { background: rgba(255,255,255,.1); border: none; color: #fff; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 16px; display: grid; place-items: center; }
    .wt-modal-close:hover { background: rgba(255,255,255,.2); }
    .wt-modal-body { padding: 24px; }

    /* ─── Responsive ─── */
    @media (max-width: 1200px) {
      .transfer-layout { grid-template-columns: 1fr; }
      .wallet-tiles { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
      .wallet-tiles { grid-template-columns: 1fr 1fr; gap: 10px; }
      .wt-select-row { grid-template-columns: 1fr auto 1fr; }
      .filter-bar { padding: 10px 14px; }
    }
    @media (max-width: 440px) {
      .wallet-tiles { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="app-container">
    <?php $this->load->view('user/layout/v2/user_sidebar'); ?>
    <main class="main-content">
      <?php $this->load->view('user/layout/v2/user_header'); ?>

  <div class="content-area" style="padding:16px 20px 40px;">

    <!-- ── Page Title ── -->
    <div class="page-titlebar">
      <div>
        <h2><i class="ph-fill ph-arrows-left-right"></i> Wallet Transfer</h2>
        <p class="sub">Send balance to another member's account instantly. USDT is excluded (blockchain only).</p>
      </div>
      <?php if (!$has_transfer_password): ?>
      <button class="btn-transfer" style="width:auto;padding:10px 18px;font-size:13px;" onclick="openSetPinModal()">
        <i class="ph ph-lock-key"></i> Set Transfer Password
      </button>
      <?php else: ?>
      <button class="btn-transfer" style="width:auto;padding:10px 18px;font-size:13px;background:linear-gradient(135deg,#10b981,#0ea5e9);" onclick="openSetPinModal()">
        <i class="ph ph-lock-key-open"></i> Change Transfer Password
      </button>
      <?php endif; ?>
    </div>

    <!-- ── Wallet Summary Tiles ── -->
    <div class="wallet-tiles">
      <?php
      $tile_meta = [
        'exchange' => ['icon'=>'ph-currency-dollar','label'=>'Exchange Wallet','sub'=>'Main trading wallet'],
        'earning'  => ['icon'=>'ph-trend-up','label'=>'Earning Wallet','sub'=>'ROI & commissions'],
        'staking'  => ['icon'=>'ph-lock-key','label'=>'Staking Wallet','sub'=>'Staking pool rewards'],
        'bonus'    => ['icon'=>'ph-gift','label'=>'Bonus Wallet','sub'=>'Bonus & rewards'],
      ];
      foreach ($tile_meta as $key => $meta):
        $bal = $internal_balances[$key] ?? 0;
      ?>
      <div class="wallet-tile <?= $key ?>" id="tile_<?= $key ?>">
        <div class="wt-icon"><i class="ph-fill <?= $meta['icon'] ?>"></i></div>
        <div class="wt-label"><?= $meta['label'] ?></div>
        <div class="wt-amount" id="bal_<?= $key ?>"><?= number_format($bal, 4) ?></div>
        <div class="wt-sub"><?= $meta['sub'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- ── Two-column: Form + History ── -->
    <div class="transfer-layout">

      <!-- ── Transfer Form ── -->
      <div class="form-card">
        <div class="form-card-header">
          <h3><i class="ph-fill ph-arrows-left-right"></i> Internal Transfer</h3>
          <p>Select wallets, enter amount and confirm with Transfer Password</p>
        </div>
        <div class="form-card-body">

          <?php if (!$has_transfer_password): ?>
          <!-- PIN not set banner -->
          <div class="pin-not-set-banner">
            <i class="ph-fill ph-warning-circle"></i>
            <div>
              <strong>Transfer Password Not Set</strong>
              <p>You need to set a Transfer Password before making any internal transfers.</p>
              <button class="set-pin-link" onclick="openSetPinModal()">
                <i class="ph ph-lock-key"></i> Set Transfer Password Now
              </button>
            </div>
          </div>
          <?php endif; ?>

          <form id="transferForm" autocomplete="off">

            <!-- Mode: between my own wallets, or send to another member -->
            <input type="hidden" name="mode" id="transferMode" value="self">
            <div class="wt-mode">
              <button type="button" class="wt-mode-btn active" data-mode="self" onclick="setMode('self')">
                <i class="ph-fill ph-arrows-left-right"></i> Between My Wallets</button>
              <button type="button" class="wt-mode-btn" data-mode="member" onclick="setMode('member')">
                <i class="ph-fill ph-user-plus"></i> Send to a Member</button>
            </div>

            <div class="wt-select-row">
              <div>
                <label class="wt-field-label">From Wallet</label>
                <select class="wt-select" id="fromWallet" name="from_wallet" onchange="onFromChange()">
                  <option value="">— Select —</option>
                  <option value="exchange">Exchange Wallet</option>
                  <option value="earning">Earning Wallet</option>
                  <option value="staking">Staking Wallet</option>
                  <option value="bonus">Bonus Wallet</option>
                </select>
              </div>
              <div class="wt-select-arrow"><i class="ph ph-arrow-right"></i></div>

              <!-- SELF mode: To Wallet -->
              <div id="toWalletWrap">
                <label class="wt-field-label">To Wallet</label>
                <select class="wt-select" id="toWallet" name="to_wallet" onchange="updatePreview()">
                  <option value="">— Select From first —</option>
                </select>
              </div>

              <!-- MEMBER mode: Recipient -->
              <div id="recipientWrap" style="position:relative;display:none;">
                <label class="wt-field-label">Recipient (Referral ID / Username / Email)</label>
                <input class="wt-select" type="text" id="recipient" name="recipient"
                       placeholder="Select or search a member…" autocomplete="off"
                       onfocus="openRecipientList()" oninput="onRecipientInput()">
                <div id="recipientDrop" class="rcpt-drop" style="display:none;"></div>
                <div id="recipientName" style="font-size:12px;font-weight:700;margin-top:4px;"></div>
                <div id="memberRuleHint" style="display:none;font-size:11.5px;font-weight:700;color:#c2410c;margin-top:5px;"></div>
              </div>
              <style>
                .wt-mode{display:flex;gap:8px;margin-bottom:14px}
                .wt-mode-btn{flex:1;padding:10px;border:1.5px solid #e6e8ef;background:#fff;border-radius:10px;
                  cursor:pointer;font-weight:700;font-size:13px;color:#555}
                .wt-mode-btn.active{background:#4f46e5;color:#fff;border-color:#4f46e5}
                .rcpt-drop{position:absolute;left:0;right:0;top:100%;z-index:50;background:#fff;border:1px solid #e6e8ef;
                  border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.10);max-height:280px;overflow-y:auto;margin-top:4px}
                .rcpt-item{padding:9px 12px;cursor:pointer;border-bottom:1px solid #f1f3f9}
                .rcpt-item:last-child{border-bottom:0}
                .rcpt-item:hover,.rcpt-item.active{background:#f4f7ff}
                .rcpt-item b{font-size:13px;display:block}
                .rcpt-item small{color:#7a7f9a;font-size:11px}
                .rcpt-empty{padding:12px;color:#7a7f9a;font-size:12px;text-align:center}
              </style>
            </div>

            <!-- Amount -->
            <label class="wt-field-label">Amount</label>
            <div class="amount-wrap">
              <input type="number" id="amountInput" name="amount" placeholder="0.0000"
                     min="0.00000001" step="any" onkeyup="updatePreview()">
              <button type="button" class="max-btn" onclick="setMax()">MAX</button>
            </div>
            <div class="bal-hint">
              <i class="ph ph-info"></i>
              Available: <span id="balHint">—</span>
            </div>

            <!-- Transfer Preview -->
            <div class="transfer-preview" id="transferPreview" style="display:none;">
              <i class="ph-fill ph-arrows-left-right"></i>
              <span id="previewText">—</span>
            </div>

            <!-- Note (optional) -->
            <label class="wt-field-label">Note (optional)</label>
            <textarea class="note-input" name="note" rows="2" placeholder="Transfer reason or memo..."></textarea>

            <!-- Transfer Password -->
            <label class="wt-field-label">Transfer Password</label>
            <div class="pin-wrap">
              <input type="password" id="pinInput" name="transfer_password"
                     placeholder="Enter Transfer Password" autocomplete="new-password">
              <button type="button" class="eye-btn" onclick="togglePin(this, 'pinInput')">
                <i class="ph ph-eye"></i>
              </button>
            </div>

            <?php $kyc_ok = !empty($kyc_approved); ?>
            <?php if (!$kyc_ok): ?>
              <div style="background:#fff4e0;border:1.5px solid #f6c000;border-radius:12px;padding:12px 16px;margin:6px 0 12px;font-size:12.5px;font-weight:700;color:#8a5a00;">
                <i class="ph-fill ph-warning-circle"></i>
                KYC verification is required before you can transfer funds.
                <a href="<?= base_url('user/profile') ?>" style="color:#0a58ca;">Complete KYC →</a>
              </div>
            <?php endif; ?>
            <button type="button" class="btn-transfer" id="submitBtn"
                    <?= (!$kyc_ok || !$has_transfer_password) ? 'disabled' : '' ?>
                    onclick="submitTransfer()">
              <i class="ph-fill ph-paper-plane-tilt"></i>
              <?php if (!$kyc_ok): ?>Complete KYC First<?php
                elseif (!$has_transfer_password): ?>Set Transfer Password First<?php
                else: ?>Transfer Now<?php endif; ?>
            </button>
          </form>

        </div><!-- /form-card-body -->
      </div><!-- /form-card -->

      <!-- ── Transfer History ── -->
      <div class="history-card">
        <div class="history-card-header">
          <h3><i class="ph-fill ph-clock-countdown"></i> Transfer History</h3>
          <span style="font-size:12px;color:var(--text-muted);font-weight:700;"><?= $total ?> total records</span>
        </div>

        <!-- Filters -->
        <form method="get" action="">
          <div class="filter-bar">
            <select name="from_wallet">
              <option value="">All From</option>
              <?php foreach (['exchange','earning','staking','bonus'] as $w): ?>
              <option value="<?= $w ?>" <?= $from_filter === $w ? 'selected' : '' ?>><?= ucfirst($w) ?></option>
              <?php endforeach; ?>
            </select>
            <select name="to_wallet">
              <option value="">All To</option>
              <?php foreach (['exchange','earning','staking','bonus'] as $w): ?>
              <option value="<?= $w ?>" <?= $to_filter === $w ? 'selected' : '' ?>><?= ucfirst($w) ?></option>
              <?php endforeach; ?>
            </select>
            <select name="status">
              <option value="">All Status</option>
              <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
              <option value="failed"    <?= $status_filter === 'failed'    ? 'selected' : '' ?>>Failed</option>
              <option value="reversed"  <?= $status_filter === 'reversed'  ? 'selected' : '' ?>>Reversed</option>
            </select>
            <input type="date" name="date_from" value="<?= htmlspecialchars($date_from ?? '') ?>" placeholder="From date">
            <input type="date" name="date_to"   value="<?= htmlspecialchars($date_to   ?? '') ?>" placeholder="To date">
            <button type="submit" class="filter-btn"><i class="ph ph-funnel"></i> Filter</button>
            <a href="<?= base_url('user/transfer_wallet') ?>" class="filter-btn filter-reset" style="text-decoration:none;">
              <i class="ph ph-x"></i> Reset
            </a>
          </div>
        </form>

        <!-- Table -->
        <div class="table-wrap">
          <?php if (empty($history)): ?>
          <div class="empty-state">
            <i class="ph ph-arrows-left-right"></i>
            <p>No transfer records found.</p>
          </div>
          <?php else: ?>
          <table class="tx-table">
            <thead>
              <tr>
                <th>#</th>
                <th>TXN ID</th>
                <th>Reference</th>
                <th>Type</th>
                <th>Wallet</th>
                <th>Counterparty</th>
                <th>Amount</th>
                <th>Balance After</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($history as $i => $tx):
                $dir = $tx['direction'] ?? 'sent'; $isSelf = ($dir === 'self'); $sent = ($dir === 'sent');
                // the after-balance of the wallet shown on this row, from the user's perspective
                if ($isSelf || $sent) { $rowBefore = $tx['from_before'] ?? null; $rowAfter = $tx['from_after'] ?? null; }
                else                  { $rowBefore = $tx['to_before']   ?? null; $rowAfter = $tx['to_after']   ?? null; } ?>
              <tr>
                <td style="color:var(--text-muted);font-size:12px;"><?= ($page - 1) * $per_page + $i + 1 ?></td>
                <td style="font-size:12px;font-weight:800;letter-spacing:.5px;color:var(--primary);cursor:pointer;"
                    title="View transaction details"
                    onclick="WalletTransferUI.openDetail('<?= htmlspecialchars($tx['ref']) ?>')"><?= htmlspecialchars($tx['txn_uid'] ?? '—') ?></td>
                <td>
                  <span class="ref-text" onclick="WalletTransferUI.openDetail('<?= htmlspecialchars($tx['ref']) ?>')"
                        title="View details"><?= htmlspecialchars($tx['ref']) ?></span>
                </td>
                <td>
                  <?php if ($isSelf): ?>
                    <span class="wallet-badge" style="background:#eef2ff;color:#3730a3;"><i class="ph-fill ph-arrows-left-right"></i> Internal</span>
                  <?php else: ?>
                    <span class="wallet-badge" style="background:<?= $sent ? '#fdecec;color:#c0392b' : '#e7f9ef;color:#149a55' ?>">
                      <i class="ph-fill <?= $sent ? 'ph-arrow-up-right' : 'ph-arrow-down-left' ?>"></i>
                      <?= $sent ? 'Sent' : 'Received' ?>
                    </span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="wallet-badge wb-<?= $tx['wallet'] ?? $tx['from_wallet'] ?>">
                    <?= ucfirst($tx['wallet'] ?? $tx['from_wallet']) ?>
                  </span>
                </td>
                <td style="font-size:13px;font-weight:700;"><?= htmlspecialchars($tx['counterparty'] ?? '—') ?></td>
                <td class="amt-cell" style="color:<?= $isSelf ? '#3730a3' : ($sent ? '#c0392b' : '#149a55') ?>">
                  <?= ($isSelf ? '' : ($sent ? '−' : '+')) . number_format((float)$tx['amount'], 4) ?></td>
                <td style="font-size:12px;white-space:nowrap;"
                    title="<?= $rowBefore !== null ? 'Before: '.number_format((float)$rowBefore, 4) : '' ?>">
                  <?php if ($rowAfter !== null): ?>
                    <span style="color:var(--text-muted);"><?= number_format((float)$rowBefore, 4) ?></span>
                    <i class="ph ph-arrow-right" style="font-size:10px;color:var(--text-muted);"></i>
                    <b><?= number_format((float)$rowAfter, 4) ?></b>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                  <span class="st-badge st-<?= $tx['status'] ?>">
                    <?php if ($tx['status'] === 'completed'): ?><i class="ph-fill ph-check-circle"></i><?php
                    elseif ($tx['status'] === 'failed'):    ?><i class="ph-fill ph-x-circle"></i><?php
                    else: ?><i class="ph-fill ph-arrow-counter-clockwise"></i><?php endif; ?>
                    <?= ucfirst($tx['status']) ?>
                  </span>
                </td>
                <td style="font-size:12px;color:var(--text-muted);white-space:nowrap;">
                  <?= date('d M Y, H:i', strtotime($tx['created_at'])) ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div class="pagination-bar">
          <div class="pg-info">
            Showing <?= min(($page - 1) * $per_page + 1, $total) ?>–<?= min($page * $per_page, $total) ?> of <?= $total ?>
          </div>
          <div class="pg-btns">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&from_wallet=<?= urlencode($from_filter ?? '') ?>&to_wallet=<?= urlencode($to_filter ?? '') ?>&status=<?= urlencode($status_filter ?? '') ?>&date_from=<?= urlencode($date_from ?? '') ?>&date_to=<?= urlencode($date_to ?? '') ?>" class="pg-btn">
              <i class="ph ph-caret-left"></i> Prev
            </a>
            <?php endif; ?>

            <?php
            $start_p = max(1, $page - 2);
            $end_p   = min($pages, $page + 2);
            for ($p = $start_p; $p <= $end_p; $p++): ?>
            <a href="?page=<?= $p ?>&from_wallet=<?= urlencode($from_filter ?? '') ?>&to_wallet=<?= urlencode($to_filter ?? '') ?>&status=<?= urlencode($status_filter ?? '') ?>&date_from=<?= urlencode($date_from ?? '') ?>&date_to=<?= urlencode($date_to ?? '') ?>"
               class="pg-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>

            <?php if ($page < $pages): ?>
            <a href="?page=<?= $page + 1 ?>&from_wallet=<?= urlencode($from_filter ?? '') ?>&to_wallet=<?= urlencode($to_filter ?? '') ?>&status=<?= urlencode($status_filter ?? '') ?>&date_from=<?= urlencode($date_from ?? '') ?>&date_to=<?= urlencode($date_to ?? '') ?>" class="pg-btn">
              Next <i class="ph ph-caret-right"></i>
            </a>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

      </div><!-- /history-card -->
    </div><!-- /transfer-layout -->
  </div><!-- /content-area -->
    </main><!-- /main-content -->
  </div><!-- /app-container -->

<!-- ═══════════════════════════════════════════════════════════════
     MODAL: Set / Change Transfer Password
════════════════════════════════════════════════════════════════ -->
<div class="wt-modal-overlay" id="setPinModal">
  <div class="wt-modal-box">
    <div class="wt-modal-head">
      <h4><i class="ph-fill ph-lock-key"></i>
        <?= $has_transfer_password ? 'Change Transfer Password' : 'Set Transfer Password' ?>
      </h4>
      <button class="wt-modal-close" onclick="closeSetPinModal()"><i class="ph ph-x"></i></button>
    </div>
    <div class="wt-modal-body">
      <div style="background:rgba(109,74,255,.07);border:1.5px solid rgba(109,74,255,.15);border-radius:12px;padding:12px 16px;margin-bottom:18px;font-size:12px;color:var(--text-main);font-weight:700;">
        <i class="ph ph-info" style="color:var(--primary);"></i>
        Your Transfer Password is a separate security PIN used only for internal wallet transfers. Use a strong, unique PIN.
      </div>

      <form id="setPinForm" autocomplete="off">
        <label class="wt-field-label">Login Password (to verify it's you)</label>
        <div class="pin-wrap" style="margin-bottom:14px;">
          <input type="password" id="loginPassInput" name="login_password" placeholder="Enter your login password">
          <button type="button" class="eye-btn" onclick="togglePin(this,'loginPassInput')"><i class="ph ph-eye"></i></button>
        </div>

        <label class="wt-field-label">New Transfer Password (min 4 chars)</label>
        <div class="pin-wrap" style="margin-bottom:14px;">
          <input type="password" id="newPinInput" name="new_transfer_password" placeholder="Set a Transfer PIN / Password">
          <button type="button" class="eye-btn" onclick="togglePin(this,'newPinInput')"><i class="ph ph-eye"></i></button>
        </div>

        <label class="wt-field-label">Confirm Transfer Password</label>
        <div class="pin-wrap" style="margin-bottom:20px;">
          <input type="password" id="confirmPinInput" name="confirm_transfer_password" placeholder="Repeat Transfer PIN">
          <button type="button" class="eye-btn" onclick="togglePin(this,'confirmPinInput')"><i class="ph ph-eye"></i></button>
        </div>

        <button type="button" class="btn-transfer" id="setPinBtn" onclick="submitSetPin()">
          <i class="ph-fill ph-lock-key"></i>
          <?= $has_transfer_password ? 'Update Transfer Password' : 'Set Transfer Password' ?>
        </button>
      </form>
    </div>
  </div>
</div>

<!-- ── Toast ── -->
<div id="wtToast" style="
  position:fixed;bottom:28px;left:50%;transform:translateX(-50%);
  background:#111;color:#fff;padding:12px 20px;border-radius:14px;
  font-weight:900;font-size:13px;z-index:99999;display:none;
  box-shadow:0 8px 32px rgba(0,0,0,.3);min-width:200px;text-align:center;
  transition:.2s;"></div>

<script>
/* ===== Data from PHP ===== */
const ALLOWED_PAIRS = <?= $allowed_pairs_json ?>;
const BALANCES = {
  exchange: <?= (float)($internal_balances['exchange'] ?? 0) ?>,
  earning:  <?= (float)($internal_balances['earning']  ?? 0) ?>,
  staking:  <?= (float)($internal_balances['staking']  ?? 0) ?>,
  bonus:    <?= (float)($internal_balances['bonus']    ?? 0) ?>,
};
const HAS_PIN = <?= $has_transfer_password ? 'true' : 'false' ?>;
const BASE_URL = '<?= base_url() ?>';
let liveBalances = Object.assign({}, BALANCES);

/* ===== Wallet label map ===== */
const WL = {
  exchange: 'Exchange Wallet',
  earning:  'Earning Wallet',
  staking:  'Staking Wallet',
  bonus:    'Bonus Wallet',
};

/* ===== Transfer mode: 'self' (own wallets) or 'member' (to another user) ===== */
function currentMode() { return document.getElementById('transferMode').value; }

function setMode(mode) {
  document.getElementById('transferMode').value = mode;
  document.querySelectorAll('.wt-mode-btn').forEach(function(b){ b.classList.toggle('active', b.dataset.mode === mode); });
  const isSelf = mode === 'self';
  document.getElementById('toWalletWrap').style.display   = isSelf ? '' : 'none';
  document.getElementById('recipientWrap').style.display  = isSelf ? 'none' : 'block';
  // Shared matrix: disable source wallets not valid for this mode (self → Exchange only).
  if (window.WalletTransferUI) WalletTransferUI.applyMatrixToSelect(document.getElementById('fromWallet'), 'from', mode, '');
  // reset the destination fields
  document.getElementById('toWallet').innerHTML = '<option value="">— Select From first —</option>';
  const rc = document.getElementById('recipient'); if (rc) rc.value = '';
  const rn = document.getElementById('recipientName'); if (rn) rn.textContent = '';
  onFromChange();
}

/* ===== From wallet change ===== */
function onFromChange() {
  const mode = currentMode();
  // Keep the source list constrained to what this mode allows.
  if (window.WalletTransferUI) WalletTransferUI.applyMatrixToSelect(document.getElementById('fromWallet'), 'from', mode, '');
  const from = document.getElementById('fromWallet').value;
  document.getElementById('balHint').textContent = from ? ((liveBalances[from] ?? 0).toFixed(4) + ' ' + (WL[from] || from)) : '—';
  const hint = document.getElementById('memberRuleHint');
  if (mode === 'self') {
    const toSel = document.getElementById('toWallet');
    toSel.innerHTML = '<option value="">— Select To Wallet —</option>';
    (ALLOWED_PAIRS[from] || []).forEach(function(w){
      const o = document.createElement('option'); o.value = w; o.textContent = WL[w]; toSel.appendChild(o);
    });
    if (hint) hint.style.display = 'none';
  } else if (hint) {
    const txt = (from && window.WalletTransferUI) ? WalletTransferUI.memberRuleText(from) : '';
    hint.textContent = txt; hint.style.display = txt ? 'block' : 'none';
  }
  updatePreview();
}

/* ===== Recipient picker: default 20 members (name + email), searchable ===== */
let RECIPIENT_OK = false;
let _rcptTimer = null;

function csrfPair(fd) { fd.append('<?= $this->security->get_csrf_token_name() ?>', '<?= $this->security->get_csrf_hash() ?>'); return fd; }

function fetchRecipients(q) {
  const drop = document.getElementById('recipientDrop');
  drop.innerHTML = '<div class="rcpt-empty">Loading…</div>';
  drop.style.display = 'block';
  const fd = new FormData(); fd.append('q', q || '');
  fetch(BASE_URL + 'user/transfer_wallet/search_recipients', { method:'POST', body: csrfPair(fd), headers:{'X-Requested-With':'XMLHttpRequest'} })
    .then(r => r.json())
    .then(function(res){
      const rows = (res && res.rows) || [];
      if (!rows.length) { drop.innerHTML = '<div class="rcpt-empty">No members found.</div>'; return; }
      drop.innerHTML = rows.map(function(u){
        const ref = u.referral_id || ('#' + u.id);
        return '<div class="rcpt-item" data-ref="' + ref + '" data-name="' + (u.username||'') + '">' +
               '<b>' + (u.username || ref) + '</b>' +
               '<small>' + ref + (u.email ? ' · ' + u.email : '') + '</small></div>';
      }).join('');
    })
    .catch(function(){ drop.innerHTML = '<div class="rcpt-empty">Could not load members.</div>'; });
}

function openRecipientList() {
  const v = (document.getElementById('recipient').value || '').trim();
  fetchRecipients(v);       // default (empty) shows the first 20
}

function onRecipientInput() {
  RECIPIENT_OK = false;
  document.getElementById('recipientName').textContent = '';
  updatePreview();
  clearTimeout(_rcptTimer);
  const v = (document.getElementById('recipient').value || '').trim();
  _rcptTimer = setTimeout(function(){ fetchRecipients(v); }, 250);
}

function pickRecipient(ref, name) {
  const inp = document.getElementById('recipient');
  inp.value = ref;
  document.getElementById('recipientDrop').style.display = 'none';
  const box = document.getElementById('recipientName');
  box.style.color = '#149a55';
  box.textContent = '✓ ' + (name || ref) + ' (' + ref + ')';
  RECIPIENT_OK = true;
  updatePreview();
}

// event delegation: pick on mousedown (fires before input blur)
document.addEventListener('mousedown', function(e){
  const item = e.target.closest('.rcpt-item');
  if (item) { e.preventDefault(); pickRecipient(item.dataset.ref, item.dataset.name); return; }
  // click outside → close
  if (!e.target.closest('#recipient') && !e.target.closest('#recipientDrop')) {
    const d = document.getElementById('recipientDrop'); if (d) d.style.display = 'none';
  }
});

/* ===== Preview update ===== */
function updatePreview() {
  const from   = document.getElementById('fromWallet').value;
  const amount = parseFloat(document.getElementById('amountInput').value) || 0;
  const preview = document.getElementById('transferPreview');
  const previewText = document.getElementById('previewText');
  const dest = currentMode() === 'self'
      ? (WL[document.getElementById('toWallet').value] || '')
      : ((document.getElementById('recipient').value || '').trim());

  if (from && dest && amount > 0) {
    preview.style.display = 'flex';
    previewText.textContent = amount.toFixed(4) + ' ' + (WL[from] || from) + ' → ' + dest;
  } else {
    preview.style.display = 'none';
  }
  if (from) {
    const bal = liveBalances[from] ?? 0;
    document.getElementById('balHint').textContent = bal.toFixed(4) + ' available in ' + (WL[from] || from);
  }
}

/* ===== MAX button ===== */
function setMax() {
  const from = document.getElementById('fromWallet').value;
  if (!from) { toast('Select a From wallet first.', 'warn'); return; }
  const bal = liveBalances[from] ?? 0;
  document.getElementById('amountInput').value = bal.toFixed(8);
  updatePreview();
}

/* ===== Submit Transfer — validate client-side, then shared Confirm dialog ===== */
function submitTransfer() {
  if (!HAS_PIN) { openSetPinModal(); return; }

  const mode   = currentMode();
  const from   = document.getElementById('fromWallet').value;
  const toW    = document.getElementById('toWallet').value;
  const rcpt   = (document.getElementById('recipient').value || '').trim();
  const amount = document.getElementById('amountInput').value;
  const pin    = document.getElementById('pinInput').value;

  if (!from) { toast('Select the wallet to send from.', 'warn'); return; }
  if (mode === 'self') {
    if (!toW) { toast('Select the destination wallet.', 'warn'); return; }
    if (toW === from) { toast('Source and destination wallet must differ.', 'warn'); return; }
  } else {
    if (!rcpt) { toast('Enter the recipient (Referral ID / Username / Email).', 'warn'); return; }
  }
  if (!amount || parseFloat(amount) <= 0) { toast('Enter a valid amount.', 'warn'); return; }
  if (!pin)  { toast('Enter your Transfer Password.', 'warn'); return; }

  // Shared confirmation dialog runs the live preview (rules + balances) and only
  // enables Confirm when every business rule (and KYC/password gate) passes.
  if (window.WalletTransferUI) {
    WalletTransferUI.openConfirm({
      mode: mode,
      fields: { mode: mode, from_wallet: from, to_wallet: toW, recipient: rcpt, amount: amount },
      sourceUser: <?= json_encode(($user['username'] ?? 'You') . ' (#' . (int)($user['id'] ?? 0) . ')') ?>,
      fromWallet: from,
      toWallet: toW,
      amount: amount,
      recipientFallback: (mode === 'member' ? rcpt : 'Self (own wallets)'),
      onConfirm: doTransferPost
    });
    return;
  }
  doTransferPost();
}

/* ===== Actual POST to the centralized engine ===== */
function doTransferPost() {
  const form = document.getElementById('transferForm');
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="ph ph-circle-notch" style="animation:spin 1s linear infinite"></i> Processing...';

  const fd = new FormData(form);
  fd.append('<?= $this->security->get_csrf_token_name() ?>', '<?= $this->security->get_csrf_hash() ?>');

  fetch(BASE_URL + 'user/transfer_wallet/do_transfer', {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => r.json())
  .then(function(res) {
    if (res.status === 'success') {
      toast('✓ ' + res.message, 'success');
      // Update live balances
      if (res.balances) {
        liveBalances = res.balances;
        Object.keys(res.balances).forEach(function(w) {
          const el = document.getElementById('bal_' + w);
          if (el) el.textContent = parseFloat(res.balances[w]).toFixed(4);
        });
      }
      // Reset form
      form.reset();
      var rn = document.getElementById('recipientName'); if (rn) rn.textContent = '';
      document.getElementById('balHint').textContent = '—';
      document.getElementById('transferPreview').style.display = 'none';
      // Reload history after short delay
      setTimeout(function() { location.reload(); }, 2000);
    } else {
      toast('✗ ' + res.message, 'error');
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="ph-fill ph-paper-plane-tilt"></i> Transfer Now';
  })
  .catch(function(err) {
    toast('Network error. Try again.', 'error');
    btn.disabled = false;
    btn.innerHTML = '<i class="ph-fill ph-paper-plane-tilt"></i> Transfer Now';
  });
}

/* ===== Set Transfer PIN ===== */
function submitSetPin() {
  const loginPass = document.getElementById('loginPassInput').value;
  const newPin    = document.getElementById('newPinInput').value;
  const confPin   = document.getElementById('confirmPinInput').value;

  if (!loginPass) { toast('Enter your login password.', 'warn'); return; }
  if (newPin.length < 4) { toast('Transfer Password must be at least 4 characters.', 'warn'); return; }
  if (newPin !== confPin) { toast('Passwords do not match.', 'warn'); return; }

  const btn = document.getElementById('setPinBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="ph ph-circle-notch" style="animation:spin 1s linear infinite"></i> Saving...';

  const fd = new FormData();
  fd.append('login_password', loginPass);
  fd.append('new_transfer_password', newPin);
  fd.append('confirm_transfer_password', confPin);
  fd.append('<?= $this->security->get_csrf_token_name() ?>', '<?= $this->security->get_csrf_hash() ?>');

  fetch(BASE_URL + 'user/transfer_wallet/set_transfer_password', {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => r.json())
  .then(function(res) {
    if (res.status === 'success') {
      toast('✓ ' + res.message, 'success');
      closeSetPinModal();
      setTimeout(function() { location.reload(); }, 1500);
    } else {
      toast('✗ ' + res.message, 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="ph-fill ph-lock-key"></i> Set Transfer Password';
    }
  })
  .catch(function() {
    toast('Network error. Try again.', 'error');
    btn.disabled = false;
    btn.innerHTML = '<i class="ph-fill ph-lock-key"></i> Set Transfer Password';
  });
}

/* ===== Modal helpers ===== */
function openSetPinModal() {
  document.getElementById('setPinModal').classList.add('show');
}
function closeSetPinModal() {
  document.getElementById('setPinModal').classList.remove('show');
}
/* Modal closes ONLY via the ✕ / Cancel button — clicking outside does not
   dismiss it (prevents losing input while setting the transfer password). */
function closePinModalOutside(e) { /* intentionally no-op */ }

/* ===== Toggle password visibility ===== */
function togglePin(btn, inputId) {
  const inp = document.getElementById(inputId);
  const icon = btn.querySelector('i');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.className = 'ph ph-eye-slash';
  } else {
    inp.type = 'password';
    icon.className = 'ph ph-eye';
  }
}

/* ===== Copy reference ===== */
function copyRef(text) {
  navigator.clipboard.writeText(text).then(function() {
    toast('Reference copied!', 'success');
  });
}

/* ===== Toast ===== */
function toast(msg, type) {
  const el = document.getElementById('wtToast');
  el.textContent = msg;
  el.style.background = type === 'success' ? '#0f9d58'
                       : type === 'error'   ? '#dc2626'
                       : type === 'warn'    ? '#d97706'
                       : '#111';
  el.style.display = 'block';
  el.style.opacity = '1';
  clearTimeout(window._toastTimer);
  window._toastTimer = setTimeout(function() {
    el.style.opacity = '0';
    setTimeout(function() { el.style.display = 'none'; }, 300);
  }, 3000);
}

/* ===== Spin keyframe ===== */
const style = document.createElement('style');
style.textContent = '@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}';
document.head.appendChild(style);

/* ===== Init: default to "Between My Wallets" ===== */
document.addEventListener('DOMContentLoaded', function(){ if (typeof setMode === 'function') setMode('self'); });
</script>

<?php $this->load->view('shared/wallet_transfer_ui', [
  'wtx_panel'   => 'user',
  'wtx_preview' => 'user/transfer_wallet/preview',
  'wtx_detail'  => 'user/transfer_wallet/tx_detail',
]); ?>
</body>
</html>
