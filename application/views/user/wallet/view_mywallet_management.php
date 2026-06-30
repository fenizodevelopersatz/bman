<?php
// ===================== WALLET PAGE (USER • ADVANCED & INFORMATIVE) =====================
// Expected vars (set from controller):
// $main_balance, $commission_balance, $bonus_balance, $pending_withdraw, $total_earned, $total_withdrawn
// $transactions = array of tx objects:
//   ->type (CREDIT/DEBIT), ->title, ->ref, ->created_at, ->amount, ->status, ->note
// $counts = ['ALL'=>0,'CREDIT'=>0,'DEBIT'=>0,'WITHDRAW'=>0,'COMMISSION'=>0,'TRANSFER'=>0,'ORDER'=>0];
// $paging = ['page'=>1,'pages'=>1,'total'=>0];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php $this->load->view('user/layout/v2/user_style'); ?>
  <style>
    /* ===================== WALLET (PAGE) ===================== */
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

    .btn-warn {
      border: none;
      background: #111;
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

    /* Wallet Hero */
    .wallet-hero {
      background: linear-gradient(105deg, #6E56CF 0%, #4c3ba0 100%);
      border-radius: 24px;
      padding: 18px;
      color: #fff;
      display: flex;
      justify-content: space-between;
      gap: 14px;
      align-items: stretch;
      margin-bottom: 14px;
      overflow: hidden;
      position: relative;
    }

    .wallet-hero:after {
      content: "";
      position: absolute;
      inset: -120px -140px auto auto;
      width: 300px;
      height: 300px;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.25), transparent 60%);
      pointer-events: none;
    }

    .wallet-hero .left {
      display: flex;
      flex-direction: column;
      gap: 10px;
      z-index: 1;
      max-width: 560px;
    }

    .wallet-hero .tag {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, 0.14);
      border: 1px solid rgba(255, 255, 255, 0.18);
      padding: 8px 12px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 1000;
      width: max-content;
    }

    .wallet-hero h3 {
      margin: 0;
      font-size: 18px;
      font-weight: 1000;
      line-height: 1.2;
    }

    .wallet-hero p {
      margin: 0;
      font-size: 12px;
      opacity: .9;
      line-height: 1.45;
    }

    .wallet-hero .actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 6px;
    }

    .wallet-hero .btnH {
      border: none;
      cursor: pointer;
      border-radius: 14px;
      padding: 10px 12px;
      font-weight: 1000;
      font-size: 12px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .wallet-hero .btnH.primary {
      background: #111;
      color: #fff;
    }

    .wallet-hero .btnH.ghost {
      background: rgba(255, 255, 255, 0.14);
      color: #fff;
      border: 1px solid rgba(255, 255, 255, 0.22);
    }

    .wallet-hero .right {
      z-index: 1;
      display: grid;
      gap: 10px;
      min-width: 320px;
    }

    .hero-mini {
      background: rgba(255, 255, 255, 0.12);
      border: 1px solid rgba(255, 255, 255, 0.18);
      border-radius: 20px;
      padding: 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .hero-mini small {
      display: block;
      font-size: 11px;
      opacity: .85;
      font-weight: 800;
    }

    .hero-mini b {
      display: block;
      font-size: 14px;
      font-weight: 1000;
      margin-top: 4px;
    }

    .hero-mini .ic {
      width: 40px;
      height: 40px;
      border-radius: 16px;
      display: grid;
      place-items: center;
      background: rgba(255, 255, 255, 0.18);
      font-size: 18px;
    }

    /* Summary Cards */
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

    /* Quick Actions */
    .quick-actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 14px;
    }

    .qa {
      background: #fff;
      border: 1px solid #f5f5f7;
      border-radius: 16px;
      padding: 10px 12px;
      font-weight: 1000;
      font-size: 12px;
      cursor: pointer;
      display: flex;
      gap: 10px;
      align-items: center;
      box-shadow: 0 10px 22px rgba(0, 0, 0, 0.03);
    }

    .qa i {
      color: var(--primary);
      font-size: 18px;
    }

    .qa:hover {
      transform: translateY(-1px);
      transition: .15s;
    }

    /* Filters + Chips */
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
      grid-template-columns: 1.2fr .8fr .8fr .8fr .6fr;
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
      font-weight: 1000;
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
      font-weight: 1000;
    }

    .chip.active .count {
      background: #fff;
      border-color: #eeecff;
      color: var(--primary);
    }

    /* Content */
    .content-grid {
      display: grid;
      /* grid-template-columns: 1.25fr .75fr; */
      grid-template-columns: 2.25fr .75fr;
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
      font-weight: 1000;
      margin: 0;
    }

    .mini-note {
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 800;
    }

    /* Table */
    .tbl {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 10px;
    }

    .table-wrap {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    .tbl thead th {
      font-size: 11px;
      color: var(--text-muted);
      text-align: left;
      font-weight: 1000;
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
      font-weight: 1000;
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

    .st-credit {
      background: #ecfdf3;
      border-color: #d1fadf;
      color: #0f9d58;
    }

    .st-debit {
      background: #fef2f2;
      border-color: #fecaca;
      color: #b91c1c;
    }

    .st-pending {
      background: #fff7ed;
      border-color: #fed7aa;
      color: #c2410c;
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
      font-weight: 900;
    }

    /* Wallet Breakdown */
    .wb {
      border: 1px dashed #dedafc;
      background: #fff;
      border-radius: 18px;
      padding: 12px;
      margin-top: 10px;
    }

    .wb .row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }

    .wb .bar {
      height: 10px;
      background: #f0f0f5;
      border-radius: 999px;
      overflow: hidden;
      margin-top: 10px;
    }

    .wb .bar>div {
      height: 100%;
      width: 72%;
      background: var(--primary);
      border-radius: 999px;
    }

    .wb .legend {
      display: grid;
      gap: 8px;
      margin-top: 10px;
    }

    .lg {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 12px;
      font-weight: 900;
    }

    .lg small {
      color: var(--text-muted);
      font-weight: 800;
    }

    .lg .dot {
      width: 10px;
      height: 10px;
      border-radius: 999px;
      background: var(--primary);
      margin-right: 8px;
      display: inline-block;
    }

    .lg .dot.alt {
      background: #22c55e;
    }

    .lg .dot.alt2 {
      background: #f97316;
    }

    /* popups / modals responsive */
    .modal-dialog {
      max-width: calc(100% - 2rem);
      margin: 1rem auto;
    }

    .modal-content {
      border-radius: 16px;
    }

    #demoBox {
      width: calc(100% - 24px);
      max-width: 420px;
      margin: 10vh auto;
    }

    .swal2-popup {
      width: min(92vw, 32em) !important;
    }



    /* responsive */
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

      .wallet-hero {
        flex-direction: column;
      }

      .wallet-hero .right {
        min-width: auto;
        grid-template-columns: 1fr 1fr;
      }
    }

    @media(max-width: 600px) {
      .page-titlebar {
        flex-direction: column;
        align-items: flex-start;
      }

      .page-actions {
        width: 100%;
      }

      .page-actions .btn-soft,
      .page-actions .btn-main,
      .page-actions .btn-warn {
        flex: 1 1 calc(50% - 8px);
        justify-content: center;
      }

      .sum-grid {
        grid-template-columns: 1fr;
      }

      .filter-grid {
        grid-template-columns: 1fr;
      }

      .filters,
      .table-card,
      .side-card {
        border-radius: 18px;
        padding: 12px;
      }

      .quick-actions .qa {
        flex: 1 1 calc(50% - 8px);
      }

      .wallet-hero .right {
        grid-template-columns: 1fr;
      }

      .tx-left {
        min-width: auto;
      }

      .table-wrap .tbl {
        min-width: 780px;
      }

      .modal-dialog {
        max-width: calc(100% - 1rem);
        margin: .5rem auto;
      }

      .modal-body,
      .modal-header,
      .modal-footer {
        padding: 12px !important;
      }

      #demoBox {
        margin: 8vh auto;
        padding: 14px;
      }
    }

    /* ===================== EXTRA RESPONSIVE PATCH (ADD AT END) ===================== */

    /* Improve hero + grids on mid screens */
    @media (max-width: 992px) {
      .wallet-hero {
        padding: 16px;
        border-radius: 20px;
      }

      .wallet-hero .right {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
      }

      .sum-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .filter-grid {
        grid-template-columns: 1fr 1fr;
      }
    }

    /* Mobile: actions, filters, chips, and HISTORY table -> CARD VIEW */
    @media (max-width: 600px) {

      /* Title bar */
      .page-titlebar {
        margin: 6px 0 12px;
      }

      .page-titlebar h2 {
        font-size: 16px;
      }

      .page-actions {
        width: 100%;
        gap: 8px;
      }

      .page-actions .btn-soft,
      .page-actions .btn-main,
      .page-actions .btn-warn {
        flex: 1 1 100%;
        justify-content: center;
        padding: 12px 12px;
        border-radius: 16px;
      }

      /* Hero */
      .wallet-hero {
        border-radius: 18px;
        padding: 14px;
      }

      .wallet-hero .right {
        grid-template-columns: 1fr;
      }

      .hero-mini {
        border-radius: 18px;
      }

      /* Summary cards + quick actions */
      .sum-grid {
        grid-template-columns: 1fr;
        gap: 10px;
      }

      .sum-card {
        border-radius: 18px;
        padding: 12px;
      }

      .quick-actions .qa {
        flex: 1 1 100%;
        justify-content: center;
      }

      /* Filters */
      .filters {
        border-radius: 18px;
        padding: 12px;
      }

      .filter-grid {
        grid-template-columns: 1fr;
      }

      .chips {
        gap: 6px;
      }

      .chip {
        padding: 8px 10px;
      }

      /* Content blocks */
      .table-card,
      .side-card {
        border-radius: 18px;
        padding: 12px;
      }

      /* ========= WALLET HISTORY: TABLE -> CARD VIEW ========= */

      /* stop forcing wide table on mobile */
      .table-wrap {
        overflow: visible;
      }

      .table-wrap .tbl {
        min-width: 0 !important;
        border-spacing: 0 10px;
      }

      /* hide table header */
      .tbl thead {
        display: none;
      }

      /* each row becomes a card */
      .tbl tbody tr {
        display: block;
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid #f5f5f7;
      }

      /* each cell becomes a row line */
      .tbl tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border: none !important;
      }

      /* First cell: Transaction block should be full width & vertical */
      .tbl tbody td:first-child {
        display: block;
        padding: 12px;
        border-bottom: 1px solid #f5f5f7 !important;
      }

      .tx-left {
        gap: 10px;
        align-items: flex-start;
      }

      /* Labels for each cell */
      .tbl tbody td:nth-child(2)::before {
        content: "Date";
      }

      .tbl tbody td:nth-child(3)::before {
        content: "Amount";
      }

      .tbl tbody td:nth-child(4)::before {
        content: "Flow";
      }

      .tbl tbody td:nth-child(5)::before {
        content: "Status";
      }

      .tbl tbody td:nth-child(6)::before {
        content: "Actions";
      }

      .tbl tbody td:nth-child(n+2)::before {
        font-size: 11px;
        font-weight: 1000;
        color: var(--text-muted);
        min-width: 78px;
      }

      /* Actions align */
      .row-actions {
        width: 100%;
        justify-content: flex-end;
      }

      .a-btn {
        width: 40px;
        height: 40px;
        border-radius: 14px;
      }

      /* Make amount look stronger */
      .amt {
        font-size: 14px;
      }
    }

    /* Very small screens */
    @media (max-width: 380px) {
      .bullet {
        width: 34px;
        height: 34px;
        border-radius: 12px;
      }

      .sum-ic {
        width: 40px;
        height: 40px;
        border-radius: 14px;
      }
    }
  </style>
</head>
<?php
/**
 * view_mywallet_management.php  ✅ UPDATED
 * - Prefill filters from GET
 * - Apply filters (submit on change + Enter on search)
 * - Chips click => set type + submit
 * - Fix FLOW + STATUS mapping for your "history" table
 *   history.type can be: bonus / commission / withdraw / transfer / order / credit / debit
 *   history.status can be: 1(success) / 0(failed) / 2(pending)  (edit mapping if different)
 * - Pagination Prev/Next working with query params
 */
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$from = isset($_GET['from']) ? trim($_GET['from']) : '';
$to = isset($_GET['to']) ? trim($_GET['to']) : '';

$page = (int) ($paging['page'] ?? 1);
$pages = (int) ($paging['pages'] ?? 1);

$currency = currency_info()->currency_symbol;

// counts
$c = $counts ?? [];
$def = ['ALL' => ($paging['total'] ?? 0), 'CREDIT' => 0, 'DEBIT' => 0, 'WITHDRAW' => 0, 'COMMISSION' => 0, 'TRANSFER' => 0, 'ORDER' => 0];
$c = array_merge($def, $c);

// helper: build URL with existing GET params
function wallet_qs_url($overrides = [])
{
  $query = $_GET ?? [];
  foreach ($overrides as $k => $v) {
    if ($v === null)
      unset($query[$k]);
    else
      $query[$k] = $v;
  }
  $qs = http_build_query($query);
  return current_url() . ($qs ? ('?' . $qs) : '');
}

// helper: FLOW mapping from history.type -> CREDIT/DEBIT
function wallet_flow_from_history_type($t)
{
  $t = strtolower(trim((string) $t));

  // treat withdraw/debit as DEBIT, others as CREDIT
  if (in_array($t, ['debit', 'withdraw'], true))
    return 'DEBIT';
  if (in_array($t, ['credit', 'bonus', 'commission', 'transfer', 'order'], true))
    return 'CREDIT';

  // fallback
  return 'CREDIT';
}

// helper: STATUS mapping from history.status -> SUCCESS/PENDING/FAILED
function wallet_status_from_history_status($s)
{
  $s = trim((string) $s);

  // numeric status mapping
  if ($s === '1' || strtoupper($s) === 'SUCCESS')
    return 'SUCCESS';
  if ($s === '2' || strtoupper($s) === 'PENDING')
    return 'PENDING';
  if ($s === '0' || strtoupper($s) === 'FAILED')
    return 'FAILED';

  // fallback show original
  return strtoupper($s ?: 'SUCCESS');
}

// helper: title from history.type if title missing
function wallet_title_fallback($type)
{
  $type = strtolower(trim((string) $type));
  if ($type === 'bonus')
    return 'Bonus Wallet';
  if ($type === 'commission')
    return 'Commission';
  if ($type === 'withdraw')
    return 'Withdraw';
  if ($type === 'transfer')
    return 'Transfer';
  if ($type === 'order')
    return 'Order';
  if ($type === 'credit')
    return 'Credit';
  if ($type === 'debit')
    return 'Debit';
  return 'Wallet Transaction';
}
?>

<body>
  <div class="app-container">
    <!-- Sidebar -->
    <?php $this->load->view('user/layout/v2/user_sidebar'); ?>

    <main class="main-content">
      <?php $this->load->view('user/layout/v2/user_header'); ?>

      <!-- Title -->
      <div class="page-titlebar">
        <div>
          <h2><i class="ph ph-wallet"></i> Wallet</h2>
          <div class="sub">Manage balances, transfers, withdrawals and view full wallet history.</div>
        </div>

        <div class="page-actions">
          <button class="btn-soft" onclick="window.print()"><i class="ph ph-printer"></i> Print</button>
          <button class="btn-soft" onclick="exportCSV()"><i class="ph ph-download-simple"></i> Export</button>
          <button class="btn-main" onclick="location.href='<?= base_url('user/withdraw'); ?>'"><i
              class="ph ph-money"></i> Withdraw</button>
        </div>
      </div>

      <!-- Wallet Hero -->
      <div class="wallet-hero">
        <div class="left">
          <div class="tag"><i class="ph ph-shield-check"></i> Wallet Security</div>
          <h3>Your funds are protected — keep your KYC & bank details updated.</h3>
          <p>Use wallet transfer for internal payments and withdraw earnings to your bank during payout windows.</p>
          <div class="actions">
            <button class="btnH primary" type="button"
              onclick="location.href='<?= base_url('user/transfer_wallet'); ?>'">
              Transfer Wallet <i class="ph ph-arrows-left-right"></i>
            </button>
            <button class="btnH ghost" type="button" onclick="location.href='<?= base_url('user/kyc'); ?>'">
              Update KYC <i class="ph ph-identification-card"></i>
            </button>
          </div>
        </div>

        <div class="right">
          <div class="hero-mini">
            <div>
              <small>Pending Withdraw</small>
              <b><?= $currency; ?> <?= number_format($pending_withdraw ?? 0, 2); ?></b>
            </div>
            <div class="ic"><i class="ph ph-hourglass"></i></div>
          </div>

          <div class="hero-mini">
            <div>
              <small>Total Withdrawn</small>
              <b><?= $currency; ?> <?= number_format($total_withdrawn ?? 0, 2); ?></b>
            </div>
            <div class="ic"><i class="ph ph-bank"></i></div>
          </div>

          <div class="hero-mini">
            <div>
              <small>Total Earned</small>
              <b><?= $currency; ?> <?= number_format($total_earned ?? 0, 2); ?></b>
            </div>
            <div class="ic"><i class="ph ph-trend-up"></i></div>
          </div>
        </div>
      </div>

      <!-- Summary Balances -->
      <div class="sum-grid">
        <div class="sum-card">
          <div class="sum-ic sum-good"><i class="ph ph-wallet"></i></div>
          <div class="sum-meta">
            <small>Main Balance</small>
            <strong><?= $currency; ?> <?= number_format($main_balance ?? 0, 2); ?></strong>
            <span>Primary wallet</span>
          </div>
        </div>

        <div class="sum-card">
          <div class="sum-ic sum-info"><i class="ph ph-coins"></i></div>
          <div class="sum-meta">
            <small>Commission Wallet</small>
            <strong><?= $currency; ?> <?= number_format($commission_balance ?? 0, 2); ?></strong>
            <span>From commissions</span>
          </div>
        </div>

        <div class="sum-card">
          <div class="sum-ic sum-warn"><i class="ph ph-gift"></i></div>
          <div class="sum-meta">
            <small>Bonus Wallet</small>
            <strong><?= $currency; ?> <?= number_format($bonus_balance ?? 0, 2); ?></strong>
            <span>Rewards & promo</span>
          </div>
        </div>

        <div class="sum-card">
          <div class="sum-ic sum-bad"><i class="ph ph-credit-card"></i></div>
          <div class="sum-meta">
            <small>Pending Withdraw</small>
            <strong><?= $currency; ?> <?= number_format($pending_withdraw ?? 0, 2); ?></strong>
            <span>Awaiting approval</span>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <div class="qa" onclick="location.href='<?= base_url('user/withdraw'); ?>'"><i class="ph ph-money"></i> Withdraw
        </div>
        <div class="qa" onclick="location.href='<?= base_url('user/transfer_wallet'); ?>'"><i
            class="ph ph-arrows-left-right"></i> Transfer Wallet</div>
        <div class="qa" onclick="location.href='<?= base_url('user/commissions'); ?>'"><i class="ph ph-coins"></i>
          Commission History</div>
        <div class="qa" onclick="location.href='<?= base_url('user/payouts'); ?>'"><i class="ph ph-bank"></i> Payouts
        </div>
        <div class="qa" onclick="location.href='<?= base_url('user/orders'); ?>'"><i class="ph ph-bag"></i> Orders</div>
        <div class="qa" onclick="location.href='<?= base_url('user/support'); ?>'"><i class="ph ph-headset"></i> Support
        </div>
      </div>

      <!-- Filters -->
      <div class="filters">
        <div class="filter-grid">
          <input id="q" class="f-in" placeholder="Search: txn id, reference, note..."
            value="<?= htmlspecialchars($q); ?>" />
          <select id="type" class="f-sel">
            <option value="" <?= $type === '' ? 'selected' : ''; ?>>All Types</option>
            <option value="CREDIT" <?= strtoupper($type) === 'CREDIT' ? 'selected' : ''; ?>>Credit</option>
            <option value="DEBIT" <?= strtoupper($type) === 'DEBIT' ? 'selected' : ''; ?>>Debit</option>
            <option value="WITHDRAW" <?= strtoupper($type) === 'WITHDRAW' ? 'selected' : ''; ?>>Withdraw</option>
            <option value="TRANSFER" <?= strtoupper($type) === 'TRANSFER' ? 'selected' : ''; ?>>Transfer</option>
            <option value="COMMISSION" <?= strtoupper($type) === 'COMMISSION' ? 'selected' : ''; ?>>Commission</option>
            <option value="ORDER" <?= strtoupper($type) === 'ORDER' ? 'selected' : ''; ?>>Order</option>
          </select>
          <select id="status" class="f-sel">
            <option value="" <?= $status === '' ? 'selected' : ''; ?>>All Status</option>
            <option value="SUCCESS" <?= strtoupper($status) === 'SUCCESS' ? 'selected' : ''; ?>>Success</option>
            <option value="PENDING" <?= strtoupper($status) === 'PENDING' ? 'selected' : ''; ?>>Pending</option>
            <option value="FAILED" <?= strtoupper($status) === 'FAILED' ? 'selected' : ''; ?>>Failed</option>
          </select>
          <input id="from" class="f-in" type="date" value="<?= htmlspecialchars($from); ?>" />
          <input id="to" class="f-in" type="date" value="<?= htmlspecialchars($to); ?>" />
        </div>

        <div class="chips" id="chips">
          <div class="chip <?= ($type === '' ? 'active' : ''); ?>" data-type=""><i class="ph ph-squares-four"></i> All
            <span class="count"><?= (int) $c['ALL']; ?></span>
          </div>
          <div class="chip <?= (strtoupper($type) === 'CREDIT' ? 'active' : ''); ?>" data-type="CREDIT"><i
              class="ph ph-arrow-circle-down"></i> Credit <span class="count"><?= (int) $c['CREDIT']; ?></span></div>
          <div class="chip <?= (strtoupper($type) === 'DEBIT' ? 'active' : ''); ?>" data-type="DEBIT"><i
              class="ph ph-arrow-circle-up"></i> Debit <span class="count"><?= (int) $c['DEBIT']; ?></span></div>
          <div class="chip <?= (strtoupper($type) === 'WITHDRAW' ? 'active' : ''); ?>" data-type="WITHDRAW"><i
              class="ph ph-money"></i> Withdraw <span class="count"><?= (int) $c['WITHDRAW']; ?></span></div>
          <div class="chip <?= (strtoupper($type) === 'TRANSFER' ? 'active' : ''); ?>" data-type="TRANSFER"><i
              class="ph ph-arrows-left-right"></i> Transfer <span class="count"><?= (int) $c['TRANSFER']; ?></span>
          </div>
          <div class="chip <?= (strtoupper($type) === 'COMMISSION' ? 'active' : ''); ?>" data-type="COMMISSION"><i
              class="ph ph-coins"></i> Commission <span class="count"><?= (int) $c['COMMISSION']; ?></span></div>
          <div class="chip <?= (strtoupper($type) === 'ORDER' ? 'active' : ''); ?>" data-type="ORDER"><i
              class="ph ph-bag"></i> Order <span class="count"><?= (int) $c['ORDER']; ?></span></div>
          <a class="chip" href="<?= base_url('user/wallet'); ?>"><i class="ph ph-x"></i> Clear </a>
        </div>

      </div>

      <!-- Content -->
      <div class="content-grid">

        <!-- Wallet History -->
        <div class="table-card">
          <div class="card-h">
            <div>
              <h3>Wallet History</h3>
              <div class="mini-note">All credits & debits in one place.</div>
            </div>
            <div class="mini-note">Total: <?= (int) ($paging['total'] ?? 0); ?></div>
          </div>

          <?php if (!empty($transactions)): ?>
            <div class="table-wrap">
              <table class="tbl" id="tbl">
                <thead>
                  <tr>
                    <th>Transaction</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Flow</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                  </tr>
                </thead>
                <tbody>

                  <?php
                  foreach ($transactions as $t):

                    // if model already maps these fields, use them; else derive
                    $rawType = $t->type ?? '';
                    $flow = wallet_flow_from_history_type($rawType);

                    $status = wallet_status_from_history_status($t->status ?? '');

                    $title = $t->title ?? wallet_title_fallback($rawType);
                    $ref = $t->ref ?? ($t->hash_id ?? '');
                    $note = $t->note ?? ($t->description ?? '');
                    $dt = $t->created_at ?? ($t->history_date ?? ($t->date ?? ''));
                    $amt = (float) ($t->amount ?? 0);

                    // icons based on history.type + title
                    $icon = 'ph-wallet';
                    $tkey = strtolower((string) $rawType);

                    if ($tkey === 'withdraw' || stripos($title, 'withdraw') !== false)
                      $icon = 'ph-money';
                    else if ($tkey === 'transfer' || stripos($title, 'transfer') !== false)
                      $icon = 'ph-arrows-left-right';
                    else if ($tkey === 'commission' || stripos($title, 'commission') !== false)
                      $icon = 'ph-coins';
                    else if ($tkey === 'bonus' || stripos($title, 'bonus') !== false)
                      $icon = 'ph-gift';
                    else if ($tkey === 'order' || stripos($title, 'order') !== false)
                      $icon = 'ph-bag';

                    ?>
                    <tr class="row">
                      <td>
                        <div class="tx-left">
                          <div class="bullet"><i class="ph <?= $icon; ?>"></i></div>
                          <div>
                            <b><?= htmlspecialchars($title); ?></b>
                            <small>
                              <?= $ref ? 'Ref: ' . htmlspecialchars($ref) . ' • ' : ''; ?>
                              <?= $note ? htmlspecialchars($note) : 'Wallet update'; ?>
                            </small>
                          </div>
                        </div>
                      </td>
                      <td style="white-space:nowrap;color:var(--text-muted);font-weight:900;">
                        <?= htmlspecialchars($dt); ?>
                      </td>
                      <td class="amt">
                        <?= $currency; ?>     <?= number_format($amt, 2); ?>
                      </td>
                      <td>
                        <span class="status <?= $flow === 'CREDIT' ? 'st-credit' : 'st-debit'; ?>">
                          <i class="ph <?= $flow === 'CREDIT' ? 'ph-arrow-circle-down' : 'ph-arrow-circle-up'; ?>"></i>
                          <?= $flow; ?>
                        </span>
                      </td>
                      <td>
                        <span
                          class="status <?= $status === 'SUCCESS' ? 'st-credit' : ($status === 'PENDING' ? 'st-pending' : 'st-debit'); ?>">
                          <i class="ph ph-dot-outline"></i> <?= htmlspecialchars($status); ?>
                        </span>
                      </td>
                      <td>
                        <div class="row-actions">
                          <button class="a-btn" type="button" title="Copy Ref"
                            onclick="copyPlain('<?= htmlspecialchars($ref, ENT_QUOTES); ?>')"><i
                              class="ph ph-copy"></i></button>
                          <button class="a-btn" type="button" title="Support"
                            onclick="location.href='<?= base_url('user/support'); ?>'"><i
                              class="ph ph-headset"></i></button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <div
              style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;gap:10px;flex-wrap:wrap;">
              <div class="mini-note">
                Page <?= $page; ?> / <?= $pages; ?>
              </div>

              <div style="display:flex;gap:10px;">
                <?php if ($page > 1): ?>
                  <button class="btn-soft" type="button"
                    onclick="location.href='<?= wallet_qs_url(['page' => $page - 1]); ?>'">
                    <i class="ph ph-caret-left"></i> Prev
                  </button>
                <?php else: ?>
                  <button class="btn-soft" type="button" disabled style="opacity:.5;cursor:not-allowed;">
                    <i class="ph ph-caret-left"></i> Prev
                  </button>
                <?php endif; ?>

                <?php if ($page < $pages): ?>
                  <button class="btn-soft" type="button"
                    onclick="location.href='<?= wallet_qs_url(['page' => $page + 1]); ?>'">
                    Next <i class="ph ph-caret-right"></i>
                  </button>
                <?php else: ?>
                  <button class="btn-soft" type="button" disabled style="opacity:.5;cursor:not-allowed;">
                    Next <i class="ph ph-caret-right"></i>
                  </button>
                <?php endif; ?>
              </div>
            </div>
          <?php else: ?>
            <div class="empty">No wallet transactions found.</div>
          <?php endif; ?>
        </div>

        <!-- Side Panel -->
        <div class="side-card">
          <div class="card-h">
            <h3>Wallet Insights</h3>
            <div class="mini-note">Today</div>
          </div>

          <div class="wb">
            <div class="row">
              <b style="font-size:12px;font-weight:1000;">Balance Utilization</b>
              <span style="font-size:12px;color:#5d56a8;font-weight:1000;">72%</span>
            </div>
            <div class="bar">
              <div></div>
            </div>

            <div class="legend">
              <div class="lg"><span><span class="dot"></span> Main
                  Wallet</span><small><?= $currency; ?>
                  <?= number_format($main_balance ?? 0, 2); ?></small></div>
              <div class="lg"><span><span class="dot alt"></span>
                  Commission</span><small><?= $currency; ?>
                  <?= number_format($commission_balance ?? 0, 2); ?></small></div>
              <div class="lg"><span><span class="dot alt2"></span>
                  Bonus</span><small><?= $currency; ?>
                  <?= number_format($bonus_balance ?? 0, 2); ?></small></div>
            </div>
          </div>

          <div class="wb" style="margin-top:12px;">
            <div class="row">
              <b style="font-size:12px;font-weight:1000;">Quick Actions</b>
              <span class="mini-note">Fast</span>
            </div>
            <div style="display:grid;gap:10px;margin-top:10px;">
              <button class="btn-main" type="button" onclick="location.href='<?= base_url('user/withdraw'); ?>'"><i
                  class="ph ph-money"></i> Withdraw Now</button>
              <button class="btn-soft" type="button"
                onclick="location.href='<?= base_url('user/transfer_wallet'); ?>'"><i
                  class="ph ph-arrows-left-right"></i> Transfer Wallet</button>
              <button class="btn-soft" type="button" onclick="location.href='<?= base_url('user/profit'); ?>'"><i
                  class="ph ph-coins"></i> View Commissions</button>
            </div>
          </div>

          <div class="wb" style="margin-top:12px;">
            <div class="row">
              <b style="font-size:12px;font-weight:1000;">Safety Tips</b>
              <span class="mini-note">Important</span>
            </div>
            <div style="display:grid;gap:8px;margin-top:10px;font-size:12px;font-weight:900;color:#5d56a8;">
              <div><i class="ph ph-check-circle" style="color:var(--primary);margin-right:8px;"></i> Never share OTP /
                password.</div>
              <div><i class="ph ph-check-circle" style="color:var(--primary);margin-right:8px;"></i> Verify receiver
                before transfer.</div>
              <div><i class="ph ph-check-circle" style="color:var(--primary);margin-right:8px;"></i> Complete KYC for
                withdrawals.</div>
            </div>
          </div>

        </div>

      </div><!-- /content-grid -->

    </main>

    <!-- Right Panel -->
    <aside class="right-panel">
      <?php $this->load->view('user/layout/v2/user_inner_right_panle'); ?>
    </aside>
  </div>
  <style>
    .table-wrap .tbl {
      min-width: 780px;
    }
  </style>
  <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>

  <script>
    // ✅ Build & submit filters via GET (same controller)
    function applyFilters(extra = {}) {
      const params = new URLSearchParams(window.location.search);

      const q = document.getElementById('q')?.value?.trim() || '';
      const type = document.getElementById('type')?.value?.trim() || '';
      const status = document.getElementById('status')?.value?.trim() || '';
      const from = document.getElementById('from')?.value || '';
      const to = document.getElementById('to')?.value || '';

      // set/clear
      (q ? params.set('q', q) : params.delete('q'));
      (type ? params.set('type', type) : params.delete('type'));
      (status ? params.set('status', status) : params.delete('status'));
      (from ? params.set('from', from) : params.delete('from'));
      (to ? params.set('to', to) : params.delete('to'));

      // reset page on filter change
      params.delete('page');

      // any extra override
      Object.keys(extra).forEach(k => {
        if (extra[k] === null || extra[k] === '') params.delete(k);
        else params.set(k, extra[k]);
      });

      window.location.search = params.toString();
    }

    // ✅ apply on change
    ['type', 'status', 'from', 'to'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.addEventListener('change', () => applyFilters());
    });

    // ✅ apply on Enter for search
    const qEl = document.getElementById('q');
    if (qEl) {
      qEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') applyFilters();
      });
    }

    // ✅ Chips click => set type + submit
    document.querySelectorAll('#chips .chip[data-type]').forEach(ch => {
      ch.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelectorAll('#chips .chip[data-type]').forEach(x => x.classList.remove('active'));
        ch.classList.add('active');
        document.getElementById('type').value = ch.dataset.type || '';
        applyFilters();
      });
    });


    function copyPlain(txt) {
      navigator.clipboard.writeText(txt || "").then(() => toastMini("Copied!"));
    }

    // Export wallet history to CSV
    function exportCSV() {
      const headers = ["Title", "Date", "Amount", "Flow", "Status", "Reference"];
      const rows = [headers.join(",")];

      document.querySelectorAll("#tbl tbody tr").forEach(tr => {
        const title = tr.querySelector(".tx-left b")?.innerText?.trim() || "";
        const date = tr.children[1]?.innerText?.trim() || "";
        const amt = tr.children[2]?.innerText?.trim() || "";
        const flow = tr.children[3]?.innerText?.trim() || "";
        const stat = tr.children[4]?.innerText?.trim() || "";
        const sub = tr.querySelector(".tx-left small")?.innerText || "";
        const ref = (sub.match(/Ref:\s*([^•]+)/i)?.[1] || "").trim();

        rows.push([title, date, amt, flow, stat, ref].map(csvEscape).join(","));
      });

      downloadFile("wallet_history.csv", rows.join("\n"), "text/csv");
    }

    function csvEscape(v) {
      v = String(v ?? "");
      if (v.includes('"') || v.includes(',') || v.includes('\n')) {
        v = '"' + v.replaceAll('"', '""') + '"';
      }
      return v;
    }

    function downloadFile(name, content, type = "text/plain") {
      const blob = new Blob([content], { type });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = name;
      document.body.appendChild(a);
      a.click();
      a.remove();
    }

    function toastMini(msg) {
      const t = document.createElement('div');
      t.textContent = msg;
      t.style.cssText =
        "position:fixed;bottom:22px;left:50%;transform:translateX(-50%);background:#111;color:#fff;padding:10px
      14px; border - radius: 14px; font - weight: 1000; font - size: 12px; z - index: 99999; opacity: 0; transition: .2s; ";
      document.body.appendChild(t);
      requestAnimationFrame(() => t.style.opacity = "1");
      setTimeout(() => { t.style.opacity = "0"; setTimeout(() => t.remove(), 250); }, 1400);
    }
  </script>
</body>

</html>

</html>