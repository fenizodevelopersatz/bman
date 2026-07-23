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

    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    .usdt-history-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
    }

    .pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 10px;
      border-radius: 999px;
      background: #f7f7fb;
      border: 1px solid #f1f1f6;
      font-size: 11px;
      font-weight: 1000;
    }

    .pill.active {
      background: #efedfb;
      border-color: #eeecff;
      color: var(--primary);
    }

    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(17, 24, 39, 0.45);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 99999;
      padding: 18px;
    }

    .modal-overlay.open {
      display: flex;
    }

    .modal-box {
      width: min(920px, 100%);
      background: #fff;
      border-radius: 22px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
      overflow: hidden;
    }

    .modal-box .modal-top {
      padding: 16px 18px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #f1f1f6;
    }

    .modal-box .modal-body {
      padding: 18px;
    }

    .modal-close {
      border: none;
      background: #f7f7fb;
      border-radius: 12px;
      width: 38px;
      height: 38px;
      display: grid;
      place-items: center;
      cursor: pointer;
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
            <button class="btnH ghost" type="button" onclick="location.href='<?= base_url('user/profile#kyc'); ?>'">
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

      <!-- All Wallets strip — USDT, Exchange, Earning, Staking, Bonus -->
      <?php
      $wallet_usdt = $wallet_usdt ?? 0;
      $wallet_bman = $wallet_bman ?? ['exchange' => 0, 'earning' => 0, 'staking' => 0, 'bonus' => 0];
      // key => [label, icon, colour]
      $all_wallets = [
        'exchange' => ['Exchange Wallet', 'ph-swap',     '#6366f1'],
        'earning'  => ['Earning Wallet',  'ph-trend-up', '#0ea5e9'],
        'staking'  => ['Staking Wallet',  'ph-lock-key', '#10b981'],
        'bonus'    => ['Bonus Wallet',    'ph-gift',     '#f59e0b'],
      ];
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
        .allw-strip .wsub{font-size:11px;font-weight:700;color:#6b7280;margin-top:2px;}
        .allw-strip .wtag{font-size:9px;font-weight:900;letter-spacing:.2px;padding:2px 7px;border-radius:99px;
          background:#26a17b1a;color:#1b8f6b;text-transform:uppercase;}

        /* Spinner animation for loading states */
        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
      </style>
      <div class="allw-strip">
        <!-- USDT Wallet (IN & OUT) -->
        <div class="wtile usdt">
          <div class="wico" style="background:#26a17b1a;color:#26a17b;"><i class="ph ph-currency-circle-dollar"></i></div>
          <div>
            <div class="wlbl">USDT Wallet <span class="wtag">In &amp; Out</span></div>
            <div class="wval"><?= number_format((float) $wallet_usdt, 2) ?> <small>USDT</small></div>
          </div>
        </div>
        <?php
        $wallet_bman_total = $wallet_bman_total ?? $wallet_bman;
        foreach ($all_wallets as $k => $m):
          $withdrawable = (float) ($wallet_bman[$k] ?? 0);
          $total = (float) ($wallet_bman_total[$k] ?? $withdrawable);
          $locked = max(0, $total - $withdrawable);
        ?>
        <div class="wtile">
          <div class="wico" style="background:<?= $m[2] ?>1a;color:<?= $m[2] ?>;"><i class="ph <?= $m[1] ?>"></i></div>
          <div>
            <div class="wlbl"><?= $m[0] ?></div>
            <div class="wval"><?= number_format($withdrawable, 2) ?> <small>BMAN</small></div>
            <?php if ($locked > 0.000001): ?>
              <div class="wsub"><?= number_format($total, 2) ?> total &middot; <?= number_format($locked, 2) ?> locked</div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Summary Balances -->
      <!-- <div class="sum-grid">
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
      </div> -->

      <?php
        $depositWallet = $deposit_wallet ?? [];
        $walletMonitor = $wallet_monitor ?? null;
        $walletAddress = $depositWallet['wallet_address'] ?? '';
        $walletQr = $depositWallet['wallet_qrimage'] ?? '';
        $onchainUsdt = is_array($walletMonitor) ? ($walletMonitor['onchain_usdt'] ?? '0') : '0';
        $onchainBnb  = is_array($walletMonitor) ? ($walletMonitor['onchain_bnb'] ?? '0') : '0';
        $onchainBman = is_array($walletMonitor) ? ($walletMonitor['onchain_bman'] ?? '0') : '0';
        $dbUsdt      = is_array($walletMonitor) ? ($walletMonitor['db_usdt'] ?? '0') : '0';
        $diffUsdt    = is_array($walletMonitor) ? ($walletMonitor['difference'] ?? '0') : '0';
        $hasPending  = is_array($walletMonitor) ? !empty($walletMonitor['has_pending']) : false;
      ?>

      <div class="table-card" style="margin-bottom:14px;">
        <div class="card-h">
          <div>
            <h3>USDT / BMAN Deposit Wallet</h3>
            <div class="mini-note">BEP-20 address on BSC. Live RPC balance and deposit log.</div>
          </div>
          <div class="mini-note">
            <?= $hasPending ? '<span style="color:#c2410c;font-weight:1000;">Uncredited on-chain funds detected</span>' : 'Synced'; ?>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:140px 1fr;gap:14px;align-items:start;">
          <div style="display:grid;gap:10px;justify-items:center;">
            <?php if (!empty($walletQr)): ?>
              <img src="<?= htmlspecialchars($walletQr); ?>" alt="Deposit QR" style="width:140px;height:140px;border-radius:18px;border:1px solid #f1f1f6;background:#fff;object-fit:cover;">
            <?php else: ?>
              <div style="width:140px;height:140px;border-radius:18px;border:1px dashed #dedafc;background:#fbfbff;display:grid;place-items:center;font-weight:1000;color:#5d56a8;">No QR</div>
            <?php endif; ?>
            <button class="btn-soft" type="button" onclick="copyPlain('<?= htmlspecialchars($walletAddress, ENT_QUOTES); ?>')">
              <i class="ph ph-copy"></i> Copy Address
            </button>
          </div>

          <div style="display:grid;gap:10px;">
            <div>
              <div class="mini-note" style="margin-bottom:6px;">Your USDT / BMAN deposit address (BEP-20) · Network: BSC (BEP20)</div>
              <input class="f-in" type="text" readonly value="<?= htmlspecialchars($walletAddress); ?>" style="background:#fff;">
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;">
              <button class="btn-main" type="button" onclick="refreshWalletState()"><i class="ph ph-arrows-clockwise"></i> Check On-chain Balance</button>              
            </div>

            <div class="wb" style="margin-top:0;">
              <div class="row">
                <b style="font-size:12px;font-weight:1000;">Live Balances</b>
                <span id="wallet-sync-state" style="font-size:12px;color:#5d56a8;font-weight:1000;"><?= $hasPending ? 'Needs review' : 'Up to date'; ?></span>
              </div>
              <div class="legend">
                <div class="lg"><span><span class="dot"></span> On-chain USDT</span><small id="onchain-usdt"><?= htmlspecialchars((string)$onchainUsdt); ?></small></div>
                <div class="lg"><span><span class="dot alt"></span> DB USDT</span><small id="db-usdt"><?= htmlspecialchars((string)$dbUsdt); ?></small></div>
                <div class="lg"><span><span class="dot alt2"></span> Difference</span><small id="usdt-diff"><?= htmlspecialchars((string)$diffUsdt); ?></small></div>
              </div>
              <div class="legend" style="margin-top:12px;">
                <div class="lg"><span><span class="dot"></span> BNB</span><small id="onchain-bnb"><?= htmlspecialchars((string)$onchainBnb); ?></small></div>
                <div class="lg"><span><span class="dot alt"></span> BMAN</span><small id="onchain-bman"><?= htmlspecialchars((string)$onchainBman); ?></small></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 📌 Pending Deposits Alert & Credit Button -->
      <?php
        $pending_deposits = $pending_deposits ?? [];
        $pending_count = count($pending_deposits);
        if ($pending_count > 0):
      ?>
      <div class="table-card" style="margin-bottom:14px;background:linear-gradient(135deg,#fef3c7,#fef9f3);border:1px solid #fcd34d;border-radius:16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;gap:14px;">
          <div style="display:flex;align-items:center;gap:12px;flex:1;">
            <div style="background:#f59e0b;color:#fff;width:42px;height:42px;border-radius:12px;display:grid;place-items:center;flex:0 0 auto;">
              <i class="ph ph-warning" style="font-size:20px;"></i>
            </div>
            <div style="flex:1;">
              <b style="display:block;color:#92400e;font-size:14px;margin-bottom:4px;"><?= $pending_count; ?> Deposits Confirmed On-Chain</b>
              <small style="color:#b45309;">Confirmed on blockchain (15+ blocks) but waiting to be credited to your wallet. Click below to credit instantly.</small>
            </div>
          </div>
          <button id="creditPendingBtn" class="btn-main" type="button" style="flex:0 0 auto;background:#c2410c;border:none;padding:10px 16px;">
            <i class="ph ph-check"></i> Credit Now
          </button>
        </div>
      </div>
      <?php endif; ?>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <div class="qa" onclick="location.href='<?= base_url('user/withdraw'); ?>'"><i class="ph ph-money"></i> Withdraw
        </div>
        <div class="qa" onclick="location.href='<?= base_url('user/transfer_wallet'); ?>'"><i
            class="ph ph-arrows-left-right"></i> Transfer Wallet</div>        
        <div class="qa" onclick="location.href='<?= base_url('user/support'); ?>'"><i class="ph ph-headset"></i> Support
        </div>
      </div>

      <!-- Filters — On-Chain Transactions Only -->
      <div class="filters">
        <div class="filter-grid">
          <input id="q" class="f-in" placeholder="Search: tx hash, address..."
            value="<?= htmlspecialchars($q ?? ''); ?>" />
          <select id="type" class="f-sel">
            <option value="" <?= ($type ?? '') === '' ? 'selected' : ''; ?>>All Transactions</option>
            <option value="INCOMING" <?= strtoupper($type ?? '') === 'INCOMING' ? 'selected' : ''; ?>>Incoming (Deposits)</option>
            <option value="OUTGOING" <?= strtoupper($type ?? '') === 'OUTGOING' ? 'selected' : ''; ?>>Outgoing (Transfers)</option>
          </select>
        </div>

        <div class="chips" id="chips">
          <div class="chip <?= (($type ?? '') === '' ? 'active' : ''); ?>" data-type=""><i class="ph ph-squares-four"></i> All
            <span class="count"><?= (int) ($counts['ALL'] ?? 0); ?></span>
          </div>
          <div class="chip <?= (strtoupper($type ?? '') === 'INCOMING' ? 'active' : ''); ?>" data-type="INCOMING"><i
              class="ph ph-arrow-circle-down"></i> Incoming <span class="count"><?= (int) ($counts['INCOMING'] ?? 0); ?></span></div>
          <div class="chip <?= (strtoupper($type ?? '') === 'OUTGOING' ? 'active' : ''); ?>" data-type="OUTGOING"><i
              class="ph ph-arrow-circle-up"></i> Outgoing <span class="count"><?= (int) ($counts['OUTGOING'] ?? 0); ?></span></div>
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

                    // ✅ On-chain transaction data (from getOnchainTransactions)
                    $flow = $t['flow'] ?? 'DEBIT';  // CREDIT or DEBIT
                    $rawType = $t['tx_type'] ?? 'transfer';
                    $status = $t['status'] ?? 'SUCCESS';  // Already SUCCESS for confirmed
                    $title = $t['title'] ?? ucfirst($rawType);
                    $ref = $t['tx_hash'] ?? '';
                    $dt = $t['created_at'] ?? '';
                    $amt = (float) ($t['amount'] ?? 0);

                    // ✅ Icons for on-chain transaction types
                    $icon = 'ph-wallet';
                    $tkey = strtolower((string) $rawType);

                    if ($tkey === 'deposit' || stripos($title, 'deposit') !== false)
                      $icon = 'ph-arrow-circle-down';
                    else if ($tkey === 'transfer' || stripos($title, 'transfer') !== false)
                      $icon = 'ph-arrow-left-right';
                    else if ($tkey === 'bonus' || stripos($title, 'bonus') !== false)
                      $icon = 'ph-gift';
                    else if ($tkey === 'earn' || stripos($title, 'earn') !== false)
                      $icon = 'ph-trend-up';
                    else if ($tkey === 'roi' || stripos($title, 'roi') !== false)
                      $icon = 'ph-currency-circle-dollar';

                    ?>
                    <tr class="row" style="cursor:pointer;" onclick="showTransactionDetails(this, {
                      id: <?= (int)($t['id'] ?? 0); ?>,
                      tx_hash: '<?= htmlspecialchars($t['tx_hash'] ?? '', ENT_QUOTES); ?>',
                      type: '<?= htmlspecialchars($t['tx_type'] ?? 'transfer', ENT_QUOTES); ?>',
                      amount: '<?= htmlspecialchars($amt, ENT_QUOTES); ?>',
                      token_symbol: '<?= htmlspecialchars($t['token_symbol'] ?? 'USDT', ENT_QUOTES); ?>',
                      from_address: '<?= htmlspecialchars($t['from_address'] ?? '', ENT_QUOTES); ?>',
                      to_address: '<?= htmlspecialchars($t['to_address'] ?? '', ENT_QUOTES); ?>',
                      block_number: <?= (int)($t['block_number'] ?? 0); ?>,
                      confirmation_count: <?= (int)($t['confirmation_count'] ?? 0); ?>,
                      created_at: '<?= htmlspecialchars($dt, ENT_QUOTES); ?>',
                      status: '<?= htmlspecialchars($status, ENT_QUOTES); ?>',
                      network: '<?= htmlspecialchars($t['network'] ?? 'bsc', ENT_QUOTES); ?>',
                      flow: '<?= htmlspecialchars($flow, ENT_QUOTES); ?>',
                      balance_before: <?= (float)($t['balance_before'] ?? 0); ?>,
                      balance_after: <?= (float)($t['balance_after'] ?? 0); ?>
                    })">
                      <td>
                        <div class="tx-left">
                          <div class="bullet"><i class="ph <?= $flow === 'CREDIT' ? 'ph-arrow-circle-down' : 'ph-arrow-circle-up'; ?>"></i></div>
                          <div>
                            <b><?= htmlspecialchars($title); ?></b>
                            <small>
                              TX: <?= htmlspecialchars(substr($ref, 0, 16)); ?>...
                              <?php if ($flow === 'CREDIT'): ?>
                                From: <?= htmlspecialchars(substr($t['from_address'] ?? '', 0, 10)); ?>...
                              <?php else: ?>
                                To: <?= htmlspecialchars(substr($t['to_address'] ?? '', 0, 10)); ?>...
                              <?php endif; ?>
                            </small>
                          </div>
                        </div>
                      </td>
                      <td style="white-space:nowrap;color:var(--text-muted);font-weight:900;">
                        <?= htmlspecialchars($dt); ?>
                      </td>
                      <td class="amt">
                        <?= htmlspecialchars($t['token_symbol'] ?? 'USDT'); ?> <?= number_format((float)$amt, 2); ?>
                      </td>
                      <td>
                        <span class="status <?= $flow === 'CREDIT' ? 'st-credit' : 'st-debit'; ?>">
                          <i class="ph <?= $flow === 'CREDIT' ? 'ph-arrow-circle-down' : 'ph-arrow-circle-up'; ?>"></i>
                          <?= $flow === 'CREDIT' ? 'INCOMING' : 'OUTGOING'; ?>
                        </span>
                      </td>
                      <td>
                        <small style="color:#10b981;font-weight:900;">
                          <i class="ph ph-check-circle"></i> <?= (int)($t['confirmation_count'] ?? 0); ?> blocks
                        </small>
                      </td>
                      <td>
                        <div class="row-actions">
                          <a class="a-btn" href="https://bscscan.com/tx/<?= htmlspecialchars($ref); ?>" target="_blank" title="View on BscScan" onclick="event.stopPropagation()">
                            <i class="ph ph-link-simple"></i>
                          </a>
                          <button class="a-btn" type="button" title="View Details" onclick="event.stopPropagation(); this.closest('tr').click();">
                            <i class="ph ph-info"></i>
                          </button>
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

          <!-- <div class="wb" style="margin-top:12px;">
            <div id="usdt-history-root"></div>
          </div> -->

        </div>

      </div><!-- /content-grid -->

    </main>

    <div class="modal-overlay" id="walletPopup" onclick="if (event.target === this) closeWalletPopup();">
      <div class="modal-box">
        <div class="modal-top">
          <div>
            <b style="font-size:14px;">On-chain Wallet Details</b>
            <div class="mini-note">Live USDT / BNB / BMAN status and credit trail</div>
          </div>
          <button class="modal-close" type="button" onclick="closeWalletPopup()"><i class="ph ph-x"></i></button>
        </div>
        <div class="modal-body">
          <div class="wb" style="margin-top:0;">
            <div class="row">
              <b style="font-size:12px;font-weight:1000;">Current Status</b>
              <span id="wallet-popup-status" style="font-size:12px;color:#5d56a8;font-weight:1000;">Loading...</span>
            </div>
            <div class="legend">
              <div class="lg"><span><span class="dot"></span> On-chain USDT</span><small id="popup-onchain-usdt">0</small></div>
              <div class="lg"><span><span class="dot alt"></span> DB USDT</span><small id="popup-db-usdt">0</small></div>
              <div class="lg"><span><span class="dot alt2"></span> Difference</span><small id="popup-diff">0</small></div>
              <div class="lg"><span><span class="dot"></span> BNB</span><small id="popup-bnb">0</small></div>
              <div class="lg"><span><span class="dot alt"></span> BMAN</span><small id="popup-bman">0</small></div>
            </div>
          </div>
          <div class="wb">
            <div class="row">
              <b style="font-size:12px;font-weight:1000;">Latest On-chain Snapshot</b>
              <span class="mini-note">RPC</span>
            </div>
            <div id="popup-address" style="font-size:11px;font-weight:900;word-break:break-all;color:var(--text-muted);margin-top:8px;"></div>
            <div id="popup-message" style="font-size:12px;font-weight:900;margin-top:8px;"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Panel -->
    <!-- <aside class="right-panel">
      <?php //$this->load->view('user/layout/v2/user_inner_right_panle'); ?>
    </aside> -->
  </div>
  <style>
    .table-wrap .tbl {
      min-width: 780px;
    }
  </style>
  <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>
  <script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script>
  <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
  <script crossorigin src="https://unpkg.com/babel-standalone@6/babel.min.js"></script>

  <script>
    const walletCheckUrl = <?= json_encode($wallet_check_url ?? site_url('member/profile/wallet_check')); ?>;
    const usdtFiltersBase = <?= json_encode(base_url('user/wallet')); ?>;
    const usdtHistoryUrl = <?= json_encode(site_url('user/usersettings/historycontroller/usdt_history_json')); ?>;

    // ✅ Build & submit filters via GET (on-chain transactions only)
    function applyFilters(extra = {}) {
      const params = new URLSearchParams(window.location.search);

      const q = document.getElementById('q')?.value?.trim() || '';
      const type = document.getElementById('type')?.value?.trim() || '';

      // set/clear
      (q ? params.set('q', q) : params.delete('q'));
      (type ? params.set('type', type) : params.delete('type'));

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
    ['type'].forEach(id => {
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


    function copyToClipboard(text) {
      const value = String(text || '');

      if (navigator.clipboard && window.isSecureContext) {
        return navigator.clipboard.writeText(value);
      }

      return new Promise((resolve, reject) => {
        const textarea = document.createElement('textarea');
        textarea.value = value;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.top = '-9999px';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();

        try {
          const ok = document.execCommand('copy');
          document.body.removeChild(textarea);
          ok ? resolve() : reject(new Error('Copy failed'));
        } catch (err) {
          document.body.removeChild(textarea);
          reject(err);
        }
      });
    }

    function copyPlain(txt) {
      copyToClipboard(txt)
        .then(() => toastMini("Copied!"))
        .catch(() => toastMini("Copy failed. Please copy manually."));
    }

    async function refreshWalletState() {
      const state = document.getElementById('wallet-sync-state');
      if (state) state.textContent = 'Refreshing...';
      try {
        // 1. Check on-chain balance
        const res = await fetch(walletCheckUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
        const json = await res.json();
        const d = json.data || {};
        if (document.getElementById('onchain-usdt')) document.getElementById('onchain-usdt').textContent = d.onchain_usdt || '0';
        if (document.getElementById('db-usdt')) document.getElementById('db-usdt').textContent = d.db_usdt || '0';
        if (document.getElementById('usdt-diff')) document.getElementById('usdt-diff').textContent = d.difference || '0';
        if (document.getElementById('onchain-bnb')) document.getElementById('onchain-bnb').textContent = d.onchain_bnb || '0';
        if (document.getElementById('onchain-bman')) document.getElementById('onchain-bman').textContent = d.onchain_bman || '0';

        // 2. If balance mismatch detected, enrich from Etherscan
        if (d.difference > 0 || d.difference < 0) {
          if (state) state.textContent = 'Fetching transaction details...';
          await enrichTransactionsFromEtherscan();
          setTimeout(() => location.reload(), 1500);
          return;
        }

        if (state) state.textContent = (d.has_pending ? 'Uncredited funds found' : 'Up to date');
        if (json.message) toastMini(json.message);
      } catch (e) {
        if (state) state.textContent = 'Refresh failed';
        toastMini('Wallet refresh failed: ' + (e.message || 'Unknown error'));
      }
    }

    // Enrich transaction data from Etherscan when balance mismatch detected
    async function enrichTransactionsFromEtherscan() {
      try {
        const res = await fetch('<?= base_url('user/wallet-check-enrich'); ?>', {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();
        if (json.success && json.updated_count > 0) {
          toastMini(`✓ Enriched ${json.updated_count} transaction(s) with Etherscan data`);
        } else if (!json.balance_match) {
          toastMini(`⚠️ ${json.message}`);
        }
      } catch (e) {
        toastMini('Could not enrich transaction data');
      }
    }

    // ✅ Credit Pending Deposits
    async function creditPendingDeposits() {
      const btn = document.getElementById('creditPendingBtn');
      if (!btn) return;

      const originalHTML = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="ph ph-spinner" style="animation:spin 1s linear infinite;"></i> Processing...';

      try {
        const res = await fetch('<?= base_url('user/instant-credit-deposits'); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({})
        });

        const json = await res.json();

        if (json.success) {
          toastMini(`✓ ${json.credited_count} deposit(s) credited! Balance: ${json.new_balance_usdt} USDT`);
          setTimeout(() => location.reload(), 1500);
        } else {
          btn.innerHTML = originalHTML;
          btn.disabled = false;
          toastMini(`❌ ${json.message}`);
        }
      } catch (e) {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
        toastMini('Credit failed: ' + e.message);
      }
    }

    // ✅ Add click handler when DOM is ready
    document.addEventListener('DOMContentLoaded', () => {
      const btn = document.getElementById('creditPendingBtn');
      if (btn) {
        btn.addEventListener('click', creditPendingDeposits);
      }
    });

    function setUsdtType(type) {
      const params = new URLSearchParams(window.location.search);
      if (type) params.set('usdt_type', type); else params.delete('usdt_type');
      params.delete('page');
      window.location.search = params.toString();
    }

    function applyUsdtFilters() {
      const params = new URLSearchParams(window.location.search);
      const status = document.getElementById('usdt_status')?.value || '';
      const from = document.getElementById('usdt_from')?.value || '';
      const to = document.getElementById('usdt_to')?.value || '';
      if (status) params.set('usdt_status', status); else params.delete('usdt_status');
      if (from) params.set('usdt_from', from); else params.delete('usdt_from');
      if (to) params.set('usdt_to', to); else params.delete('usdt_to');
      params.delete('page');
      window.location.search = params.toString();
    }

    function openWalletPopup() {
      const modal = document.getElementById('walletPopup');
      if (modal) modal.classList.add('open');
      refreshWalletState();
    }

    function closeWalletPopup() {
      const modal = document.getElementById('walletPopup');
      if (modal) modal.classList.remove('open');
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
      v = String(v || "");
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
      t.style.cssText = "position:fixed;bottom:22px;left:50%;transform:translateX(-50%);background:#111;color:#fff;padding:10px 14px;border-radius:14px;font-weight:1000;font-size:12px;z-index:99999;opacity:0;transition:.2s;";
      document.body.appendChild(t);
      requestAnimationFrame(() => t.style.opacity = "1");
      setTimeout(() => { t.style.opacity = "0"; setTimeout(() => t.remove(), 250); }, 1400);
    }
  </script>
  <script type="text/babel">
    const { useEffect, useState } = React;

    function fmt(v) {
      if (!v) return '';
      try { return new Date(String(v).replace(' ', 'T')).toLocaleString(); } catch (e) { return String(v); }
    }

    function UsdtHistoryPanel() {
      const initial = new URLSearchParams(window.location.search);
      const [rows, setRows] = useState([]);
      const [loading, setLoading] = useState(true);
      const [type, setType] = useState(initial.get('usdt_type') || '');
      const [status, setStatus] = useState(initial.get('usdt_status') || '');
      const [from, setFrom] = useState(initial.get('usdt_from') || '');
      const [to, setTo] = useState(initial.get('usdt_to') || '');
      const [q, setQ] = useState(initial.get('usdt_q') || '');

      const load = async () => {
        setLoading(true);
        const p = new URLSearchParams();
        if (type) p.set('type', type);
        if (status) p.set('status', status);
        if (from) p.set('from', from);
        if (to) p.set('to', to);
        if (q) p.set('q', q);
        const res = await fetch(usdtHistoryUrl + '?' + p.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
        const json = await res.json();
        const d = json.data || {};
        setRows(d.rows || []);
        if (d.monitor) {
          const map = {
            'popup-onchain-usdt': d.monitor.onchain_usdt,
            'popup-db-usdt': d.monitor.db_usdt,
            'popup-diff': d.monitor.difference,
            'popup-bnb': d.monitor.onchain_bnb,
            'popup-bman': d.monitor.onchain_bman,
            'popup-address': d.monitor.address,
          };
          Object.keys(map).forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = map[id] || '';
          });
          const st = document.getElementById('wallet-popup-status');
          if (st) st.textContent = d.monitor.has_pending ? 'Uncredited funds found' : 'Synced';
        }
        const msg = document.getElementById('popup-message');
        if (msg) msg.textContent = json.message || '';
        setLoading(false);
      };

      useEffect(() => { load(); }, []);

      const creditPendingDeposits = async () => {
        const btn = document.getElementById('creditPendingBtn');
        if (!btn) return;

        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span style="display:inline-flex;align-items:center;gap:6px;"><svg style="width:14px;height:14px;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg> Processing...</span>';

        try {
          const response = await fetch('/user/instant-credit-deposits', { method: 'POST' });
          const data = await response.json();

          if (data.success) {
            alert(`✓ Success!\n${data.credited_count} deposit(s) credited\nNew balance: ${parseFloat(data.new_balance_usdt).toFixed(4)} USDT`);
            setTimeout(() => location.reload(), 1000);
          } else {
            alert(`❌ ${data.message}`);
            btn.disabled = false;
            btn.innerHTML = originalHTML;
          }
        } catch (err) {
          alert(`Error: ${err.message}`);
          btn.disabled = false;
          btn.innerHTML = originalHTML;
        }
      };

      const apply = () => {
        const p = new URLSearchParams(window.location.search);
        if (type) p.set('usdt_type', type); else p.delete('usdt_type');
        if (status) p.set('usdt_status', status); else p.delete('usdt_status');
        if (from) p.set('usdt_from', from); else p.delete('usdt_from');
        if (to) p.set('usdt_to', to); else p.delete('usdt_to');
        if (q) p.set('usdt_q', q); else p.delete('usdt_q');
        window.history.replaceState({}, '', window.location.pathname + '?' + p.toString());
        load();
      };

      const pendingCount = rows.filter(r => r.status === 'pending_confirmation').length;

      return (
        <div>
          {/* ✨ PENDING DEPOSITS ALERT */}
          {pendingCount > 0 && (
            <div style={{background:'#dbeafe',border:'1px solid #93c5fd',borderRadius:'14px',padding:'12px',marginBottom:'12px',display:'flex',alignItems:'center',justifyContent:'space-between',gap:'12px'}}>
              <div>
                <div style={{fontSize:'12px',fontWeight:1000,color:'#1e40af'}}>⚠️  {pendingCount} Deposits Confirmed On-Chain</div>
                <div style={{fontSize:'11px',color:'#1e40af',opacity:0.8,marginTop:'3px'}}>Confirmed on blockchain but waiting to be credited. Click "Credit Now" to credit instantly.</div>
              </div>
              <button id="creditPendingBtn" style={{border:'none',background:'#3b82f6',color:'#fff',borderRadius:'10px',padding:'8px 12px',fontWeight:900,fontSize:'12px',cursor:'pointer',whiteSpace:'nowrap',flexShrink:0}} onClick={creditPendingDeposits} type="button">
                ✓ Credit Now
              </button>
            </div>
          )}

          <div className="usdt-history-head">
            <div>
              <b style={{fontSize:'12px',fontWeight:1000}}>USDT Deposit History</b>
              <div className="mini-note">Live, no reload, full history below.</div>
            </div>
            <div style={{display:'flex',gap:'8px',flexWrap:'wrap'}}>
              <button className={`pill ${type === '' ? 'active' : ''}`} type="button" onClick={() => { setType(''); setTimeout(load, 0); }}>All</button>
              <button className={`pill ${type === 'DEPOSIT' ? 'active' : ''}`} type="button" onClick={() => { setType('DEPOSIT'); setTimeout(load, 0); }}>Deposit</button>
              <button className={`pill ${type === 'CREDIT' ? 'active' : ''}`} type="button" onClick={() => { setType('CREDIT'); setTimeout(load, 0); }}>Credit</button>
            </div>
          </div>
          <div style={{display:'grid',gridTemplateColumns:'repeat(4,1fr)',gap:'8px',marginTop:'10px'}}>
            <input className="f-in" type="date" value={from} onChange={e => setFrom(e.target.value)} />
            <input className="f-in" type="date" value={to} onChange={e => setTo(e.target.value)} />
            <select className="f-sel" value={status} onChange={e => setStatus(e.target.value)}>
              <option value="">All Status</option>
              <option value="pending">Pending</option>
              <option value="confirming">Confirming</option>
              <option value="confirmed">Confirmed</option>
              <option value="credited">Credited</option>
            </select>
            <input className="f-in" placeholder="Search tx hash..." value={q} onChange={e => setQ(e.target.value)} />
          </div>
          <div style={{display:'flex',gap:'8px',flexWrap:'wrap',marginTop:'10px'}}>
            <button className="btn-soft" type="button" onClick={apply}>Apply Filter</button>
            <button className="btn-warn" type="button" onClick={openWalletPopup}>On-chain Details</button>
            <button className="btn-soft" type="button" onClick={() => location.href = '<?= base_url('user/stakings'); ?>'}>Order to Staking</button>
          </div>
          <div style={{marginTop:'12px'}}>
            <div className="mini-note" style={{marginBottom:'8px'}}>Full History: {rows.length} record(s)</div>
            {loading ? <div className="empty">Loading USDT history...</div> : (
              <div style={{display:'grid',gap:'10px'}}>
                {rows.length ? rows.map((r, idx) => {
                  const isPending = r.status === 'pending_confirmation';
                  const isConfirmed = r.status === 'credited';
                  return (
                    <div key={r.id || idx} style={{display:'flex',justifyContent:'space-between',gap:'10px',alignItems:'flex-start',border:'1px solid ' + (isPending ? '#fed7aa' : '#f1f1f6'),borderRadius:'14px',padding:'10px',background:isPending ? '#fff7ed' : '#fff'}}>
                      <div style={{flex:1}}>
                        <div style={{fontSize:'12px',fontWeight:1000,lineHeight:1.2}}>{r.token || 'USDT'} deposit</div>
                        <div style={{fontSize:'11px',color:'var(--text-muted)',fontWeight:900,marginTop:'4px'}}>{fmt(r.detected_at || r.created_at)}</div>
                        <div style={{fontSize:'10px',color:'var(--text-muted)',fontWeight:900,marginTop:'3px'}}>
                          Confirmations: {r.confirmations || 0} / 15 {(r.confirmations || 0) >= 15 && <span style={{color:'#10b981'}}>✓</span>}
                        </div>
                        <div style={{fontSize:'10px',color:'var(--text-muted)',fontWeight:900,marginTop:'3px',wordBreak:'break-all',fontFamily:'monospace'}}>
                          <a href={'https://bscscan.com/tx/' + (r.tx_hash || '#')} target="_blank" style={{color:'#6366f1',textDecoration:'none'}}>{(r.tx_hash || '').substring(0, 12)}...</a>
                        </div>
                      </div>
                      <div style={{textAlign:'right',whiteSpace:'nowrap'}}>
                        <div style={{fontSize:'12px',fontWeight:1000}}>{Number(r.token === 'BMAN' ? (r.amount_bman || 0) : (r.amount_usdt || 0)).toFixed(2)} {r.token || 'USDT'}</div>
                        <div style={{fontSize:'10px',marginTop:'4px'}}>
                          <span style={{display:'inline-block',padding:'4px 8px',borderRadius:'999px',background:isConfirmed ? '#ecfdf3' : isPending ? '#fff7ed' : '#f7f7fb',border:'1px solid ' + (isConfirmed ? '#d1fadf' : isPending ? '#fed7aa' : '#f1f1f6'),color:isConfirmed ? '#0f9d58' : isPending ? '#c2410c' : '#6b7280',fontWeight:900,fontSize:'9px'}}>
                            {isConfirmed ? '✓ Credited' : isPending ? '⏳ Pending' : (r.status || '').toUpperCase()}
                          </span>
                        </div>
                        {isPending && (
                          <button style={{marginTop:'6px',border:'none',background:'#3b82f6',color:'#fff',borderRadius:'8px',padding:'5px 8px',fontWeight:900,fontSize:'9px',cursor:'pointer',display:'block',width:'100%'}} onClick={(e) => {
                            e.preventDefault();
                            if (confirm(`Credit ${Number(r.amount_usdt || 0).toFixed(4)} USDT?`)) creditPendingDeposits();
                          }} type="button">
                            → Credit
                          </button>
                        )}
                      </div>
                    </div>
                  );
                }) : <div className="empty">No deposits detected yet.</div>}
              </div>
            )}
          </div>
        </div>
      );
    }

    // ReactDOM.createRoot(document.getElementById('usdt-history-root')).render(<UsdtHistoryPanel />);
  </script>

  <!-- 📋 Transaction Details Modal - PROFESSIONAL DESIGN -->
  <div id="txDetailsModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.7);z-index:9999;padding:20px;overflow-y:auto;animation:fadeIn 0.2s ease-in;">
    <div style="background:linear-gradient(135deg,#fff 0%,#f9fafb 100%);border-radius:20px;max-width:700px;margin:40px auto;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);overflow:hidden;animation:slideUp 0.3s ease-out;">

      <!-- Header -->
      <div style="background:linear-gradient(135deg,#6e56cf 0%,#5d56a8 100%);padding:24px;display:flex;justify-content:space-between;align-items:center;">
        <h2 style="margin:0;font-size:22px;font-weight:900;color:#fff;display:flex;align-items:center;gap:10px;">
          <i class="ph ph-receipt" style="font-size:24px;"></i> Transaction Details
        </h2>
        <button style="background:rgba(255,255,255,0.2);border:none;font-size:28px;cursor:pointer;color:#fff;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'" onclick="closeTxModal()">×</button>
      </div>

      <!-- Content -->
      <div style="padding:30px;">
        <div id="txDetailsContent" style="display:grid;gap:20px;">
          <!-- Will be populated by JavaScript -->
        </div>

        <!-- Action Buttons -->
        <div style="display:flex;gap:12px;margin-top:30px;justify-content:flex-end;padding-top:20px;border-top:1px solid #e5e7eb;">
          <button class="btn-soft" type="button" onclick="closeTxModal()" style="padding:10px 16px;border-radius:12px;">
            <i class="ph ph-x"></i> Close
          </button>
          <a id="txBscLink" href="#" target="_blank" style="padding:10px 16px;border-radius:12px;background:#6e56cf;color:#fff;border:none;font-weight:900;cursor:pointer;display:inline-flex;align-items:center;gap:8px;text-decoration:none;">
            <i class="ph ph-link-simple"></i> View on BscScan
          </a>
        </div>
      </div>
    </div>
  </div>

  <style>
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    @keyframes slideUp {
      from { transform: translateY(30px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
  </style>

  <script>
    // ✅ Show transaction details modal with professional design
    function showTransactionDetails(row, tx) {
      const modal = document.getElementById('txDetailsModal');
      const content = document.getElementById('txDetailsContent');

      // Use actual balance snapshots from blockchain if available, otherwise calculate
      const balanceBefore = tx.balance_before > 0 ? tx.balance_before : 0;
      const balanceAfter = tx.balance_after > 0 ? tx.balance_after : (balanceBefore + parseFloat(tx.amount));

      const detailsHTML = `
        <!-- Amount Section -->
        <div style="background:linear-gradient(135deg,#dbeafe,#e0e7ff);border-left:4px solid #6e56cf;padding:16px;border-radius:12px;display:flex;justify-content:space-between;align-items:center;">
          <div>
            <small style="color:#6b7280;font-weight:900;font-size:12px;text-transform:uppercase;">Transaction Amount</small>
            <div style="font-size:28px;font-weight:900;color:#6e56cf;margin-top:4px;">
              ${parseFloat(tx.amount).toFixed(2)} <span style="font-size:16px;">${tx.token_symbol || 'USDT'}</span>
            </div>
          </div>
          <div style="font-size:48px;color:#6e56cf;opacity:0.2;">
            <i class="ph ph-arrow-right"></i>
          </div>
        </div>

        <!-- Key Details Grid -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div style="padding:14px;background:#f9fafb;border-radius:10px;border:1px solid #e5e7eb;">
            <small style="color:#6b7280;font-weight:900;font-size:11px;text-transform:uppercase;display:block;margin-bottom:6px;">Date & Time</small>
            <div style="font-size:13px;font-weight:900;color:#0b1220;">${tx.created_at}</div>
          </div>

          <div style="padding:14px;background:#f9fafb;border-radius:10px;border:1px solid #e5e7eb;">
            <small style="color:#6b7280;font-weight:900;font-size:11px;text-transform:uppercase;display:block;margin-bottom:6px;">Status</small>
            <div style="font-size:13px;font-weight:900;color:#10b981;display:flex;align-items:center;gap:6px;">
              <i class="ph ph-check-circle"></i> ${tx.status.toUpperCase()}
            </div>
          </div>

          <div style="padding:14px;background:#f9fafb;border-radius:10px;border:1px solid #e5e7eb;">
            <small style="color:#6b7280;font-weight:900;font-size:11px;text-transform:uppercase;display:block;margin-bottom:6px;">Confirmations</small>
            <div style="font-size:13px;font-weight:900;color:#0b1220;">${tx.confirmation_count} / 15 blocks</div>
          </div>

          <div style="padding:14px;background:#f9fafb;border-radius:10px;border:1px solid #e5e7eb;">
            <small style="color:#6b7280;font-weight:900;font-size:11px;text-transform:uppercase;display:block;margin-bottom:6px;">Type</small>
            <div style="font-size:13px;font-weight:900;color:#0b1220;">${tx.type.toUpperCase()}</div>
          </div>

          <div style="padding:14px;background:#f9fafb;border-radius:10px;border:1px solid #e5e7eb;">
            <small style="color:#6b7280;font-weight:900;font-size:11px;text-transform:uppercase;display:block;margin-bottom:6px;">Network</small>
            <div style="font-size:13px;font-weight:900;color:#0b1220;">${tx.network.toUpperCase()}</div>
          </div>

          <div style="padding:14px;background:#f9fafb;border-radius:10px;border:1px solid #e5e7eb;">
            <small style="color:#6b7280;font-weight:900;font-size:11px;text-transform:uppercase;display:block;margin-bottom:6px;">Block Number</small>
            <div style="font-size:13px;font-weight:900;color:#0b1220;">#${tx.block_number}</div>
          </div>
        </div>

        <!-- Balance Changes -->
        <div style="display:grid;grid-template-columns:1fr auto 1fr;gap:12px;align-items:center;padding:16px;background:linear-gradient(135deg,#f3f4f6,#fafbfc);border-radius:12px;border:1px solid #e5e7eb;">
          <div>
            <small style="color:#6b7280;font-weight:900;font-size:11px;text-transform:uppercase;">Balance Before</small>
            <div style="font-size:16px;font-weight:900;color:#0b1220;margin-top:4px;">${balanceBefore.toFixed(2)} ${tx.token_symbol || 'USDT'}</div>
          </div>
          <div style="text-align:center;">
            <div style="display:flex;flex-direction:column;align-items:center;">
              <i class="ph ph-arrow-right" style="font-size:24px;color:#6b7280;"></i>
              <span style="font-size:11px;color:#6b7280;font-weight:900;margin-top:4px;">+${parseFloat(tx.amount).toFixed(2)}</span>
            </div>
          </div>
          <div style="text-align:right;">
            <small style="color:#6b7280;font-weight:900;font-size:11px;text-transform:uppercase;">Balance After</small>
            <div style="font-size:16px;font-weight:900;color:#10b981;margin-top:4px;">${balanceAfter.toFixed(2)} ${tx.token_symbol || 'USDT'}</div>
          </div>
        </div>

        <!-- From Address -->
        <div style="padding:14px;background:#f9fafb;border-radius:10px;border:1px solid #e5e7eb;">
          <small style="color:#6b7280;font-weight:900;font-size:11px;text-transform:uppercase;display:block;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
            <i class="ph ph-arrow-up-right"></i> FROM ADDRESS
          </small>
          <div style="font-size:12px;font-weight:900;color:#0b1220;word-break:break-all;font-family:monospace;background:#fff;padding:10px;border-radius:8px;border:1px solid #e5e7eb;">${tx.from_address}</div>
        </div>

        <!-- To Address -->
        <div style="padding:14px;background:#f9fafb;border-radius:10px;border:1px solid #e5e7eb;">
          <small style="color:#6b7280;font-weight:900;font-size:11px;text-transform:uppercase;display:block;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
            <i class="ph ph-arrow-down-left"></i> TO ADDRESS
          </small>
          <div style="font-size:12px;font-weight:900;color:#0b1220;word-break:break-all;font-family:monospace;background:#fff;padding:10px;border-radius:8px;border:1px solid #e5e7eb;">${tx.to_address}</div>
        </div>

        <!-- Transaction Hash -->
        <div style="padding:14px;background:#f9fafb;border-radius:10px;border:1px solid #e5e7eb;">
          <small style="color:#6b7280;font-weight:900;font-size:11px;text-transform:uppercase;display:block;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
            <i class="ph ph-hash"></i> TRANSACTION HASH
          </small>
          <div style="font-size:12px;font-weight:900;color:#0b1220;word-break:break-all;font-family:monospace;background:#fff;padding:10px;border-radius:8px;border:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">
            <span>${tx.tx_hash}</span>
            <button style="background:none;border:none;cursor:pointer;color:#6e56cf;font-weight:900;" onclick="copyPlain('${tx.tx_hash}')">
              <i class="ph ph-copy"></i>
            </button>
          </div>
        </div>
      `;

      content.innerHTML = detailsHTML;
      document.getElementById('txBscLink').href = 'https://bscscan.com/tx/' + tx.tx_hash;
      modal.style.display = 'block';
    }

    function closeTxModal() {
      document.getElementById('txDetailsModal').style.display = 'none';
    }

    document.getElementById('txDetailsModal')?.addEventListener('click', function(e) {
      if (e.target === this) closeTxModal();
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeTxModal();
    });
  </script>
</body>

</html>

</html>
