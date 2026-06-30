<?php

// ===================== USER INVESTMENT + ROI PAGE (PRO UI • USD ONLY) =====================
// NOTE: UI template. Connect values from DB/admin settings.
// It does NOT give financial advice. Show your own legal disclaimers/terms.

// --------------------- Demo / Fallback data (replace with DB) ---------------------



$user = $user ?? (object) [
  'name' => 'LucasSATZ',
  'uid' => 'FENIZO123',
];

$wallet_balance_usd = $wallet_balance_usd ?? 0.00;

$packages = $packages ?? [
  (object) [
    'id' => 1,
    'name' => 'ZEN',
    'min' => 100,
    'max' => 200,
    'period' => 'Daily',
    'roi_percent' => 0.10,
    'duration_days' => 30,
    'bv' => 0,
    'status' => 1,
    'note' => 'Best for starters'
  ],
  (object) [
    'id' => 2,
    'name' => 'PRO',
    'min' => 1000,
    'max' => 2000,
    'period' => 'Daily',
    'roi_percent' => 0.20,
    'duration_days' => 30,
    'bv' => 0,
    'status' => 1,
    'note' => 'Higher ROI (requires eligibility)'
  ],
];

$investments = $investments ?? [
  (object) [
    'ref' => 'INV-1021',
    'package' => 'ZEN',
    'amount' => 0,
    'roi_percent' => 0.10,
    'period' => 'Daily',
    'duration_days' => 30,
    'start_date' => date('Y-m-d'), //show current date only
    'end_date' => date('Y-m-d', strtotime('+30 days')), //next date of current
    'earned' => 0,
    'next_payout' => date('Y-m-d', strtotime('+1 days')), //next date of current
    'status' => 'ACTIVE'
  ],
  (object) [
    'ref' => 'INV-0998',
    'package' => 'PRO',
    'amount' => 0,
    'roi_percent' => 0.20,
    'period' => 'Daily',
    'duration_days' => 30,
    'start_date' => date('Y-m-d'), //show current date only
    'end_date' => date('Y-m-d', strtotime('+30 days')), //next date of current
    'earned' => 0,
    'next_payout' => date('Y-m-d', strtotime('+1 days')), //next date of current
    'status' => 'ACTIVE'
  ],
];


// $roi_history = $roi_history ?? [
//   (object) ['date' => '2026-01-30', 'ref' => 'INV-1021', 'title' => 'Daily ROI Credit', 'amount' => 0.15, 'status' => 'APPROVED'],
//   (object) ['date' => '2026-01-29', 'ref' => 'INV-1021', 'title' => 'Daily ROI Credit', 'amount' => 0.15, 'status' => 'APPROVED'],
//   (object) ['date' => '2026-01-26', 'ref' => 'INV-0998', 'title' => 'Final ROI Credit', 'amount' => 2.00, 'status' => 'APPROVED'],
// ];


// KPIs
$total_invested = 0;
$roi_earned = 0;
$active_count = 0;
$next_payout = null;

foreach ($investments as $inv) {
  $total_invested += (float) ($inv->amount ?? 0);
  $roi_earned += (float) ($inv->earned ?? 0);
  if (strtoupper($inv->status ?? '') === 'ACTIVE') {
    $active_count++;
    if (!empty($inv->next_payout) && $inv->next_payout !== '—') {
      if ($next_payout === null || strtotime($inv->next_payout) < strtotime($next_payout))
        $next_payout = $inv->next_payout;
    }
  }
}
$next_payout = $next_payout ?? '—';

// Simple “overall progress” for hero ring (you can replace with real logic)
$hero_progress = 48;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php $this->load->view('user/layout/v2/user_style'); ?>
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <style>
    /* ===================== BASE UI (YOUR EXISTING) ===================== */
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
      max-width: 820px;
      line-height: 1.45;
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

    .muted {
      color: var(--text-muted);
      font-weight: 800;
      font-size: 12px;
      line-height: 1.45;
    }

    .grid-2 {
      display: grid;
      grid-template-columns: 1.1fr .9fr;
      gap: 14px;
      margin-top: 14px;
    }

    .filters {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
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
      vertical-align: top;
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
      line-height: 1.35;
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
      white-space: nowrap;
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
      border-color: #e7e7f3;
      background: #f7f7fb;
      color: #334155;
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

    /* Modal */
    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(10, 10, 20, .35);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 99999;
      padding: 14px;
    }

    .modal {
      width: min(760px, 100%);
      background: #fff;
      border-radius: 24px;
      border: 1px solid #f5f5f7;
      box-shadow: 0 26px 70px rgba(0, 0, 0, .18);
      overflow: hidden;
    }

    .modal-h {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 14px 16px;
      border-bottom: 1px solid #f5f5f7;
    }

    .modal-h b {
      font-size: 14px;
      font-weight: 1100;
    }

    .xbtn {
      width: 40px;
      height: 40px;
      border-radius: 14px;
      border: 1px solid #f1f1f6;
      background: #fff;
      cursor: pointer;
      display: grid;
      place-items: center;
      font-size: 18px;
    }

    .modal-b {
      padding: 14px 16px;
    }

    .row2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-top: 12px;
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

    .agree {
      display: flex;
      gap: 10px;
      align-items: flex-start;
      margin-top: 10px;
    }

    .agree input {
      transform: translateY(2px);
    }

    .agree label {
      font-size: 12px;
      color: var(--text-muted);
      font-weight: 900;
      line-height: 1.4;
    }

    /* Inputs */
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

    .warnbox {
      border: 1px dashed #e7e7f3;
      background: #fbfbff;
      border-radius: 18px;
      padding: 12px;
      font-size: 12px;
      color: var(--text-muted);
      font-weight: 900;
      line-height: 1.45;
    }

    /* ===================== COLORFUL GRAPHICAL THEME (UPGRADE) ===================== */
    :root {
      --primary: #6E56CF;
      --primary2: #8B5CF6;
      --pink: #EC4899;
      --cyan: #06B6D4;
      --amber: #F59E0B;
      --success: #22C55E;

      --bg: #f6f7ff;
      --card: #ffffff;
      --text: #111827;
      --muted: #6b7280;
      --radius: 26px;
    }

    body {
      background: var(--bg) !important;
    }

    /* HERO */
    .invest-hero {
      background:
        radial-gradient(1200px 500px at 10% 0%, rgba(255, 255, 255, .18), transparent 40%),
        radial-gradient(700px 380px at 95% 20%, rgba(255, 255, 255, .14), transparent 55%),
        linear-gradient(135deg, #6E56CF 0%, #4D39A3 50%, #EC4899 130%);
      border-radius: var(--radius);
      padding: 40px;
      color: #fff;
      margin-bottom: 22px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 28px 60px -16px rgba(110, 86, 207, .45);
    }

    .invest-hero::before {
      content: "";
      position: absolute;
      inset: -2px;
      background: linear-gradient(90deg, rgba(255, 255, 255, .22), rgba(255, 255, 255, 0));
      opacity: .25;
      transform: skewX(-12deg) translateX(-35%);
      animation: sheen 7s linear infinite;
      pointer-events: none;
    }

    @keyframes sheen {
      0% {
        transform: skewX(-12deg) translateX(-55%);
      }

      100% {
        transform: skewX(-12deg) translateX(85%);
      }
    }

    .hero-blobs {
      position: absolute;
      inset: 0;
      pointer-events: none;
    }

    .hero-blobs span {
      position: absolute;
      border-radius: 50%;
      opacity: .22;
      animation: floaty 9s ease-in-out infinite;
    }

    .hero-blobs span:nth-child(1) {
      width: 240px;
      height: 240px;
      right: -70px;
      top: -60px;
      background: radial-gradient(circle at 30% 30%, #fff, transparent 55%);
    }

    .hero-blobs span:nth-child(2) {
      width: 160px;
      height: 160px;
      left: -50px;
      bottom: -40px;
      background: radial-gradient(circle at 35% 35%, #22C55E, transparent 62%);
      animation-duration: 11s;
    }

    .hero-blobs span:nth-child(3) {
      width: 120px;
      height: 120px;
      right: 180px;
      bottom: -45px;
      background: radial-gradient(circle at 30% 30%, #06B6D4, transparent 62%);
      animation-duration: 13s;
    }

    @keyframes floaty {

      0%,
      100% {
        transform: translateY(0)
      }

      50% {
        transform: translateY(14px)
      }
    }

    .banner-content h2 {
      font-size: 30px;
      margin: 0;
      font-weight: 900;
      display: flex;
      align-items: center;
      gap: 14px;
      position: relative;
      z-index: 2;
    }

    .banner-content p {
      opacity: .9;
      margin: 10px 0 0;
      font-size: 15px;
      max-width: 720px;
      line-height: 1.6;
      position: relative;
      z-index: 2;
    }

    /* HERO ring */
    .hero-row {
      display: flex;
      gap: 18px;
      align-items: center;
      margin-top: 18px;
      position: relative;
      z-index: 2;
    }

    .ring {
      width: 86px;
      height: 86px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      background: conic-gradient(var(--success) 0deg, rgba(255, 255, 255, .16) 0deg);
      box-shadow: inset 0 0 0 10px rgba(255, 255, 255, .08);
    }

    .ring .inner {
      width: 62px;
      height: 62px;
      border-radius: 50%;
      background: rgba(10, 10, 20, .25);
      border: 1px solid rgba(255, 255, 255, .12);
      display: grid;
      place-items: center;
      color: #fff;
      font-weight: 1000;
    }

    /* Buttons */
    .btn-invest {
      border: none;
      border-radius: 16px;
      padding: 14px 18px;
      background: linear-gradient(135deg, #6E56CF, #8B5CF6);
      color: #fff;
      font-weight: 1000;
      cursor: pointer;
      transition: .25s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      box-shadow: 0 14px 34px rgba(0, 0, 0, .10);
    }

    .btn-invest:hover {
      transform: translateY(-1px) scale(1.02);
      opacity: .95;
    }

    /* KPI GRID */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 14px;
      margin-bottom: 22px;
    }

    .kpi-card {
      background: #fff;
      padding: 18px 18px 14px;
      border-radius: 22px;
      border: 1px solid rgba(17, 24, 39, .06);
      box-shadow: 0 12px 30px rgba(17, 24, 39, .05);
      position: relative;
      overflow: hidden;
      transition: .25s;
      display: block;
    }

    .kpi-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 18px 40px rgba(17, 24, 39, .08);
    }

    .kpi-card::after {
      content: "";
      position: absolute;
      right: -60px;
      top: -60px;
      width: 160px;
      height: 160px;
      border-radius: 50%;
      opacity: .18;
    }

    .kpi-icon {
      width: 44px;
      height: 44px;
      border-radius: 14px;
      display: grid;
      place-items: center;
      font-size: 20px;
      margin-bottom: 10px;
      background: rgba(110, 86, 207, .12);
      color: var(--primary);
    }

    .kpi-card small {
      color: var(--muted);
      font-size: 11px;
      font-weight: 1000;
      text-transform: uppercase;
      letter-spacing: .5px;
    }

    .kpi-card b {
      display: block;
      font-size: 18px;
      margin-top: 6px;
      color: #111827;
      font-weight: 1100;
    }

    /* Color per KPI */
    .kpi-card:nth-child(1)::after {
      background: radial-gradient(circle, #6E56CF, transparent 65%);
    }

    .kpi-card:nth-child(2) .kpi-icon {
      background: rgba(245, 158, 11, .14);
      color: var(--amber);
    }

    .kpi-card:nth-child(2)::after {
      background: radial-gradient(circle, #F59E0B, transparent 65%);
    }

    .kpi-card:nth-child(3) .kpi-icon {
      background: rgba(34, 197, 94, .14);
      color: var(--success);
    }

    .kpi-card:nth-child(3)::after {
      background: radial-gradient(circle, #22C55E, transparent 65%);
    }

    .kpi-card:nth-child(4) .kpi-icon {
      background: rgba(6, 182, 212, .14);
      color: var(--cyan);
    }

    .kpi-card:nth-child(4)::after {
      background: radial-gradient(circle, #06B6D4, transparent 65%);
    }

    .kpi-card:nth-child(5) .kpi-icon {
      background: rgba(236, 72, 153, .14);
      color: var(--pink);
    }

    .kpi-card:nth-child(5)::after {
      background: radial-gradient(circle, #EC4899, transparent 65%);
    }

    .spark {
      height: 36px;
      width: 100%;
      display: block;
      margin-top: 10px;
      opacity: .92;
    }

    /* Packages */
    .packages-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 18px;
    }

    .premium-pack {
      background: #fff;
      border-radius: 28px;
      padding: 26px;
      border: 1px solid #f0f0f7;
      position: relative;
      transition: .25s;
      overflow: hidden;
      box-shadow: 0 18px 44px rgba(17, 24, 39, .06);
    }

    .premium-pack::before {
      content: "";
      position: absolute;
      inset: 0;
      background:
        radial-gradient(260px 160px at 20% 10%, rgba(110, 86, 207, .12), transparent 60%),
        radial-gradient(240px 160px at 90% 20%, rgba(6, 182, 212, .10), transparent 60%);
      pointer-events: none;
    }

    .premium-pack:hover {
      transform: translateY(-3px);
      border-color: rgba(110, 86, 207, .35);
    }

    .pack-badge {
      position: absolute;
      top: 18px;
      right: 18px;
      padding: 6px 14px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 1100;
      background: rgba(34, 197, 94, .12);
      border: 1px solid rgba(34, 197, 94, .25);
      color: var(--success);
      /* z-index: 2; */
    }

    .pack-header {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 18px;
      position: relative;
      z-index: 2;
    }

    .pack-icon {
      width: 56px;
      height: 56px;
      border-radius: 18px;
      background: linear-gradient(135deg, #6E56CF, #8B5CF6);
      color: #fff;
      display: grid;
      place-items: center;
      font-size: 24px;
      box-shadow: 0 18px 30px rgba(110, 86, 207, .25);
    }

    .roi-tag {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--primary);
      font-weight: 1200;
      font-size: 18px;
      position: relative;
      z-index: 2;
    }

    .pack-price-range {
      display: flex;
      justify-content: space-between;
      background: #f8f9ff;
      padding: 14px;
      border-radius: 18px;
      margin: 18px 0;
      position: relative;
      /* z-index: 2; */
    }

    .price-box small {
      display: block;
      font-size: 10px;
      color: var(--muted);
      font-weight: 1000;
    }

    .price-box b {
      font-size: 15px;
      color: #111827;
      font-weight: 1100;
    }

    /* Calculator */
    .calculator-card {
      background:
        radial-gradient(900px 480px at 0% 0%, rgba(110, 86, 207, .30), transparent 45%),
        radial-gradient(600px 380px at 100% 0%, rgba(6, 182, 212, .22), transparent 50%),
        linear-gradient(180deg, #0b1020, #0a0f1f);
      border-radius: 28px;
      padding: 26px;
      color: #fff;
      border: 1px solid rgba(255, 255, 255, .08);
      box-shadow: 0 26px 60px rgba(0, 0, 0, .25);
    }

    .calc-input-group {
      margin-bottom: 18px;
    }

    .calc-input-group label {
      display: block;
      font-size: 12px;
      margin-bottom: 8px;
      opacity: .75;
      font-weight: 1000;
    }

    .calc-input-group select,
    .calc-input-group input {
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.10);
      color: #fff;
      padding: 14px;
      border-radius: 14px;
      width: 100%;
      outline: none;
      font-weight: 1000;
    }

    .calc-result-row {
      display: flex;
      justify-content: space-between;
      padding: 12px 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .calc-result-row span {
      opacity: .7;
      font-size: 13px;
      font-weight: 900;
    }

    .calc-result-row b {
      color: var(--success);
      font-size: 15px;
      font-weight: 1200;
    }

    @media (max-width: 1200px) {
      .kpi-grid {
        grid-template-columns: repeat(3, 1fr);
      }

      .grid-2 {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 700px) {
      .kpi-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .invest-hero {
        padding: 26px;
      }

      .banner-content h2 {
        font-size: 24px;
      }
    }
  </style>
</head>

<body>
  <div class="app-container">
    <?php $this->load->view('user/layout/v2/user_sidebar'); ?>

    <main class="main-content">
      <?php $this->load->view('user/layout/v2/user_header'); ?>

      <!-- HERO BANNER -->
      <div class="invest-hero">
        <div class="banner-content">
          <h2><i class="ph-fill ph-chart-pie"></i> Wealth Engine</h2>
          <p>Put your USD balance to work. Choose a verified package, track your daily ROI credits, and grow your
            portfolio with transparency.</p>

          <div style="margin-top: 20px; display:flex; gap: 12px; flex-wrap:wrap;">
            <button class="btn-invest" style="background:#fff;color:var(--primary);" onclick="openInvest()">
              <i class="ph ph-plus-circle"></i> New Investment
            </button>
            <button class="btn-invest"
              style="background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.22);" onclick="openRules()">
              <i class="ph ph-info"></i> ROI Rules
            </button>
          </div>
        </div>

        <!-- GRAPHICAL BLOBS -->
        <div class="hero-blobs"><span></span><span></span><span></span></div>
      </div>

      <!-- KPI GRID -->
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-icon"><i class="ph ph-wallet"></i></div>
          <small>Available Balance</small>
          <b><?= moneyUSD($wallet_balance_usd); ?></b>
          <svg class="spark" viewBox="0 0 120 36" preserveAspectRatio="none">
            <path d="M0,26 C14,16 22,30 34,22 C45,14 52,18 66,12 C78,6 88,18 100,10 C110,4 116,8 120,6" fill="none"
              stroke="currentColor" stroke-width="3" stroke-linecap="round" opacity=".45" />
            <path d="M0,26 C14,16 22,30 34,22 C45,14 52,18 66,12 C78,6 88,18 100,10 C110,4 116,8 120,6 L120,36 L0,36 Z"
              fill="currentColor" opacity=".08" />
          </svg>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon"><i class="ph ph-hand-coins"></i></div>
          <small>Total Invested</small>
          <b><?= moneyUSD($total_invested); ?></b>
          <svg class="spark" viewBox="0 0 120 36" preserveAspectRatio="none">
            <path d="M0,24 C16,28 22,10 38,16 C50,20 56,8 70,12 C84,16 92,6 106,10 C113,12 118,9 120,8" fill="none"
              stroke="currentColor" stroke-width="3" stroke-linecap="round" opacity=".45" />
            <path d="M0,24 C16,28 22,10 38,16 C50,20 56,8 70,12 C84,16 92,6 106,10 C113,12 118,9 120,8 L120,36 L0,36 Z"
              fill="currentColor" opacity=".08" />
          </svg>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon"><i class="ph ph-trend-up"></i></div>
          <small>Total ROI Earned</small>
          <b><?= moneyUSD($roi_earned); ?></b>
          <svg class="spark" viewBox="0 0 120 36" preserveAspectRatio="none">
            <path d="M0,28 C12,22 20,26 32,18 C44,10 52,18 64,10 C76,2 88,14 100,8 C110,4 116,6 120,4" fill="none"
              stroke="currentColor" stroke-width="3" stroke-linecap="round" opacity=".45" />
            <path d="M0,28 C12,22 20,26 32,18 C44,10 52,18 64,10 C76,2 88,14 100,8 C110,4 116,6 120,4 L120,36 L0,36 Z"
              fill="currentColor" opacity=".08" />
          </svg>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon"><i class="ph ph-calendar-check"></i></div>
          <small>Next Payout</small>
          <b><?= htmlspecialchars($next_payout); ?></b>
          <svg class="spark" viewBox="0 0 120 36" preserveAspectRatio="none">
            <path d="M0,20 C14,10 24,22 36,14 C48,6 58,18 70,12 C84,6 94,20 106,12 C113,8 118,10 120,9" fill="none"
              stroke="currentColor" stroke-width="3" stroke-linecap="round" opacity=".45" />
            <path d="M0,20 C14,10 24,22 36,14 C48,6 58,18 70,12 C84,6 94,20 106,12 C113,8 118,10 120,9 L120,36 L0,36 Z"
              fill="currentColor" opacity=".08" />
          </svg>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon"><i class="ph ph-briefcase"></i></div>
          <small>Active Plans</small>
          <b><?= (int) $active_count; ?> Running</b>
          <svg class="spark" viewBox="0 0 120 36" preserveAspectRatio="none">
            <path d="M0,26 C12,14 22,26 34,20 C46,14 56,18 68,12 C82,6 92,18 104,10 C112,6 116,8 120,7" fill="none"
              stroke="currentColor" stroke-width="3" stroke-linecap="round" opacity=".45" />
            <path d="M0,26 C12,14 22,26 34,20 C46,14 56,18 68,12 C82,6 92,18 104,10 C112,6 116,8 120,7 L120,36 L0,36 Z"
              fill="currentColor" opacity=".08" />
          </svg>
        </div>
      </div>

      <div class="grid-2">
        <!-- PACKAGES -->
        <section>
          <div class="card-h" style="margin-bottom: 16px;">
            <h3 style="font-size: 18px; font-weight: 1100;">Select Your Plan</h3>
            <span class="chip"><i class="ph ph-seal-check"></i> Verified Packages</span>
          </div>

          <div class="packages-grid">
            <?php foreach ($packages as $p): ?>
              <div class="premium-pack">
                <span class="pack-badge"><?= strtoupper(htmlspecialchars($p->period ?? 'DAILY')); ?> PAYOUT</span>

                <div class="pack-header">
                  <div class="pack-icon"><i class="ph ph-cube"></i></div>
                  <div>
                    <h4 style="font-size: 18px; margin: 0; font-weight: 1200;"><?= htmlspecialchars($p->name) ?></h4>
                    <small style="color:var(--muted); font-weight:900;"><?= htmlspecialchars($p->note ?? '') ?></small>
                  </div>
                </div>

                <div class="roi-tag">
                  <i class="ph ph-lightning-fill"></i>
                  <?= number_format((float) $p->roi_percent, 2) ?>%
                  <span style="font-size: 12px; opacity: 0.65; font-weight:1000;">/
                    <?= htmlspecialchars($p->period ?? 'Daily'); ?></span>
                </div>

                <div class="pack-price-range">
                  <div class="price-box">
                    <small>MINIMUM</small>
                    <b><?= moneyUSD($p->min) ?></b>
                  </div>
                  <div class="price-box" style="text-align:right;">
                    <small>MAXIMUM</small>
                    <b><?= moneyUSD($p->max) ?></b>
                  </div>
                </div>

                <div
                  style="font-size: 12px; margin-bottom: 18px; color: var(--muted); display:flex; justify-content:space-between; font-weight:900; position:relative;">
                  <span>Duration: <b style="color:#111;"><?= (int) $p->duration_days ?> Days</b></span>
                  <span>BV Points: <b style="color:#111;">+<?= (int) $p->bv ?></b></span>
                </div>

                <button class="btn-invest" onclick="openInvest(<?= (int) $p->id ?>)">Invest Now <i
                    class="ph ph-arrow-right"></i></button>
              </div>
            <?php endforeach; ?>
          </div>
        </section>

        <!-- CALCULATOR -->
        <aside class="calculator-card">
          <div class="card-h" style="border:none; margin-bottom: 14px;">
            <h3 style="color: #fff; font-size: 18px; font-weight: 1100;">ROI Profit Calculator</h3>
            <span class="badge b-soft"
              style="background:rgba(255,255,255,.08); border-color:rgba(255,255,255,.12); color:#fff;">
              <i class="ph ph-calculator"></i> Tool
            </span>
          </div>

          <div class="calc-input-group">
            <label>Choose Plan</label>
            <select id="calcPackage" onchange="calcROI()">
              <?php foreach ($packages as $p): ?>
                <!-- IMPORTANT: Keep these data-* attrs so JS works -->
                <option style="background-color: #0a0f1f;" value="<?= (int) $p->id ?>"
                  data-roi="<?= (float) $p->roi_percent ?>" data-period="<?= htmlspecialchars($p->period) ?>"
                  data-duration="<?= (int) $p->duration_days ?>" data-min="<?= (float) $p->min ?>"
                  data-max="<?= (float) $p->max ?>">
                  <?= htmlspecialchars($p->name) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="calc-input-group">
            <label>Investment Amount ($)</label>
            <input type="number" id="calcAmount" placeholder="0.00" oninput="calcROI()">
          </div>

          <div style="background: rgba(255,255,255,0.04); padding: 18px; border-radius: 18px; margin-top: 18px;">
            <div class="calc-result-row"><span>Daily Profit</span><b id="outDay">$ 0.00</b></div>
            <div class="calc-result-row"><span>Weekly Profit</span><b id="outWeek">$ 0.00</b></div>
            <div class="calc-result-row"><span>Net Profit (End of Term)</span><b id="outTotal"
                style="font-size: 18px;">$ 0.00</b></div>
            <div class="calc-result-row" style="border:none;"><span>Total Payout</span><b id="outReturn"
                style="color:#fff;">$ 0.00</b></div>
          </div>

          <div id="calcNote" style="margin-top:14px;font-size:12px;opacity:.75;font-weight:900;line-height:1.45;"></div>
          <p style="font-size: 11px; opacity: 0.55; margin-top: 16px; text-align: center; font-weight:900;">
            *Calculations are estimates based on package rates.
          </p>
        </aside>
      </div>

      <!-- TABLE: YOUR INVESTMENTS -->
      <div class="card" style="margin-top: 22px; border-radius: 28px;">
        <div class="card-h" style="padding: 18px 22px; border-bottom: 1px solid #f0f0f7; margin:0;">
          <h3 style="font-size: 16px; font-weight: 1100;">My Investment Portfolio</h3>
          <span class="chip"><i class="ph ph-folder-open"></i> Track Plans</span>
        </div>

        <div style="padding: 10px 16px 18px; overflow-x: auto;">
          <table class="table" style="border-spacing: 0 8px;">
            <thead>
              <tr>
                <th>Package / Ref</th>
                <th>Principal</th>
                <th>ROI Rate</th>
                <th>Duration</th>
                <th>Total Earned</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              foreach ($investments as $inv): ?>
                <tr class="tr" style="background: #fbfbff; border: 1px solid rgba(17,24,39,.06);">
                  <td class="td-title">
                    <b><?= htmlspecialchars($inv->package) ?></b>
                    <small><?= htmlspecialchars($inv->ref) ?> • Started <?= htmlspecialchars($inv->start_date) ?> • Ends
                      <?= htmlspecialchars($inv->end_date) ?></small>
                  </td>
                  <td><b style="font-size: 14px;"><?= moneyUSD($inv->amount) ?></b></td>
                  <td><span class="badge"><?= number_format((float) $inv->roi_percent, 2) ?>%
                      <?= htmlspecialchars($inv->period) ?></span></td>
                  <td><span class="badge"><?= (int) $inv->duration_days ?> Days</span></td>
                  <td><b style="color:#22C55E; font-size:14px;"><?= moneyUSD($inv->earned) ?></b></td>
                  <td><span class="badge <?= badgeClass($inv->status) ?>"><?= htmlspecialchars($inv->status) ?></span>
                  </td>
                  <td>
                  <td>
                    <button class="btn-mini js-invest-details" type="button"
                      data-invest-id="<?= (int) $inv->inverst_id ?? '0' ?>">
                      <i class="ph ph-eye"></i> Details
                    </button>
                  </td>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <style>
        /* modal body scroll */
        #invModalBody {
          max-height: 70vh;
          overflow: auto;
        }

        /* table horizontal scroll */
        .table-scroll {
          width: 100%;
          overflow-x: auto;
          -webkit-overflow-scrolling: touch;
        }

        .table-scroll table {
          width: 100%;
          min-width: 720px;
          /* important for mobile horizontal scroll */
          border-collapse: separate;
          border-spacing: 0 8px;
        }

        .table-scroll th,
        .table-scroll td {
          white-space: nowrap;
          /* prevents breaking */
        }

        /* optional: make description wrap instead of forcing huge width */
        .wrap-desc {
          white-space: normal !important;
          min-width: 240px;
          max-width: 380px;
        }
      </style>

      <div id="invModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.4);">
        <div style="max-width:900px; margin:6% auto; background:#fff; border-radius:12px; padding:16px;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0;">Investment ROI Details</h3>
            <button id="invModalClose" type="button" style="padding: 10px;">X</button>
          </div>

          <div id="invModalBody" style="margin-top:12px;">
            Loading...
          </div>
        </div>
      </div>

    </main>
  </div>

  <!-- ===================== INVEST MODAL ===================== -->
  <div class="modal-backdrop" id="investModal">
    <div class="modal">
      <div class="modal-h">
        <b>New Investment (USD)</b>
        <button class="xbtn" onclick="closeInvest()"><i class="ph ph-x"></i></button>
      </div>

      <div class="modal-b">
        <div class="muted">
          Funds will be taken from your wallet (USD). ROI credits are created automatically based on package rules.
          Eligibility checks (KYC/Active/Cap) are applied as per Admin settings.
        </div>

        <form method="post" action="<?= $action; ?>" id="investForm">
          <input type="hidden" name="payment_option" value="wallet">
          <div class="row2">
            <div>
              <div class="muted" style="margin:10px 0 6px;">Select Package</div>
              <select class="sel" name="package_id" id="invPackage" onchange="syncInvestPreview()">
                <?php foreach ($packages as $p): ?>
                  <option value="<?= (int) $p->id; ?>" data-roi="<?= (float) $p->roi_percent; ?>"
                    data-period="<?= htmlspecialchars($p->period); ?>" data-duration="<?= (int) $p->duration_days; ?>"
                    data-min="<?= (float) $p->min; ?>" data-max="<?= (float) $p->max; ?>" data-bv="<?= (int) $p->bv; ?>">
                    <?= htmlspecialchars($p->name); ?> • <?= number_format((float) $p->roi_percent, 2); ?>%
                    <?= htmlspecialchars($p->period); ?> • <?= (int) $p->duration_days; ?> days
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <div class="muted" style="margin:10px 0 6px;">Amount (USD)</div>
              <input class="inp" name="lending_amount" id="invAmount" type="number" step="0.01" min="0"
                placeholder="e.g., 150" oninput="syncInvestPreview()">
              <input type="hidden" name="currency" value="USD">
            </div>
          </div>

          <div>
            <div class="muted" style="margin:10px 0 6px;">Select Package</div>
            <select class="sel" name="payment_option" id="invPay">
              <option value="wallet">Wallet</option>
              <option value="paypal">PayPal</option>
              <option value="stripe">Stripe</option>
            </select>
          </div>

          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:12px;">
            <div class="card" style="box-shadow:none;">
              <div class="muted">ROI</div>
              <div style="margin-top:6px;font-weight:1100;" id="invRoiTxt">—</div>
            </div>
            <div class="card" style="box-shadow:none;">
              <div class="muted">Duration</div>
              <div style="margin-top:6px;font-weight:1100;" id="invDurTxt">—</div>
            </div>
            <div class="card" style="box-shadow:none;">
              <div class="muted">Estimated Total ROI</div>
              <div style="margin-top:6px;font-weight:1100;" id="invTotTxt">$ 0.00</div>
            </div>
          </div>

          <div class="warnbox" id="invLimitNote" style="margin-top:12px;">
            Min/Max limits are enforced. Ensure your wallet has enough balance.
          </div>

          <div class="agree">
            <input type="checkbox" id="agree" required>
            <label for="agree">
              I agree to the platform ROI rules, payout schedule, and all admin terms (caps, eligibility, KYC hold, and
              fraud prevention).
            </label>
          </div>

          <button class="btn-full primary" type="submit" style="margin-top:12px;">
            Confirm Investment <i class="ph ph-check"></i>
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- ===================== RULES MODAL ===================== -->
  <div class="modal-backdrop" id="rulesModal">
    <div class="modal">
      <div class="modal-h">
        <b>ROI Rules (Admin Controlled)</b>
        <button class="xbtn" onclick="closeRules()"><i class="ph ph-x"></i></button>
      </div>
      <div class="modal-b">
        <div class="warnbox">
          This page is a UI layer. Your Admin Panel decides: ROI %, payout time, duration, caps, eligibility, KYC holds,
          and investment limits.
        </div>

        <div class="row2 table-scroll">
          <div class="card" style="box-shadow:none;">
            <div class="card-h">
              <h3 style="margin:0;">Common Rules</h3><span class="chip"><i class="ph ph-shield-check"></i> Safety</span>
            </div>
            <ul style="margin:0 0 0 18px;font-size:12px;font-weight:900;line-height:1.55;color:#111;">
              <li>ROI credits can be held if KYC/Bank is not verified (if enabled).</li>
              <li>Min/Max investment limits per package.</li>
              <li>Payout time is fixed (daily/weekly) by admin schedule.</li>
              <li>Caps (daily ROI cap / max plans cap) may apply.</li>
            </ul>
          </div>

          <div class="card" style="box-shadow:none;">
            <div class="card-h">
              <h3 style="margin:0;">Transparency</h3><span class="chip"><i class="ph ph-eye"></i> Clear</span>
            </div>
            <ul style="margin:0 0 0 18px;font-size:12px;font-weight:900;line-height:1.55;color:#111;">
              <li>ROI History shows every wallet credit with reference ID.</li>
              <li>Investments show start/end dates and next payout date.</li>
              <li>All values shown are in USD only.</li>
            </ul>
          </div>
        </div>

        <button class="btn-full primary" style="margin-top:12px;" onclick="closeRules()">
          Understood <i class="ph ph-check"></i>
        </button>
      </div>
    </div>
  </div>

  <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>
  <script>
    // --------------------- (KEEP) Table filter: Investments (optional elements) ---------------------
    const invSearch = document.getElementById('invSearch');
    const invStatus = document.getElementById('invStatus');
    const invRows = () => Array.from(document.querySelectorAll('#invTable tbody .tr'));

    function applyInvFilters() {
      const q = (invSearch?.value || "").trim().toLowerCase();
      const st = (invStatus?.value || "").trim().toUpperCase();

      invRows().forEach(r => {
        const hay = (r.dataset.hay || "").toLowerCase();
        const okQ = !q || hay.includes(q);
        const okSt = !st || (r.dataset.status || "") === st;
        r.style.display = (okQ && okSt) ? "" : "none";
      });
    }
    function resetInvFilters() {
      if (invSearch) invSearch.value = "";
      if (invStatus) invStatus.value = "";
      applyInvFilters();
    }
    if (invSearch) invSearch.addEventListener('input', applyInvFilters);
    if (invStatus) invStatus.addEventListener('change', applyInvFilters);

    // --------------------- Money format ---------------------
    function fmtUSD(x) {
      const n = Number(x || 0);
      return '$ ' + n.toFixed(2);
    }

    // --------------------- ROI Calculator ---------------------
    function calcROI() {
      const sel = document.getElementById('calcPackage');
      const amt = Number(document.getElementById('calcAmount')?.value || 0);
      if (!sel) return;

      const opt = sel.options[sel.selectedIndex];
      const roi = Number(opt.getAttribute('data-roi') || 0);
      const period = (opt.getAttribute('data-period') || 'Daily').toLowerCase();
      const dur = Number(opt.getAttribute('data-duration') || 0);
      const min = Number(opt.getAttribute('data-min') || 0);
      const max = Number(opt.getAttribute('data-max') || 0);

      const rate = roi / 100;

      let perDay = 0;
      if (period.includes('daily')) perDay = amt * rate;
      else if (period.includes('weekly')) perDay = (amt * rate) / 7;
      else if (period.includes('monthly')) perDay = (amt * rate) / 30;
      else perDay = amt * rate;

      const perWeek = perDay * 7;
      const totalRoi = perDay * dur;
      const totalReturn = amt + totalRoi;

      document.getElementById('outDay').textContent = fmtUSD(perDay);
      document.getElementById('outWeek').textContent = fmtUSD(perWeek);
      document.getElementById('outTotal').textContent = fmtUSD(totalRoi);
      document.getElementById('outReturn').textContent = fmtUSD(totalReturn);

      const note = document.getElementById('calcNote');
      if (note) {
        if (amt && (amt < min || amt > max)) {
          note.textContent = `Amount must be within package limit: ${fmtUSD(min)} to ${fmtUSD(max)}.`;
        } else {
          note.textContent = `Tip: Keep wallet sufficient and follow eligibility rules (KYC/Active/Caps) set by admin.`;
        }
      }
    }
    calcROI();

    // --------------------- Invest Modal ---------------------
    function openInvest(packageId) {
      const m = document.getElementById('investModal');
      if (!m) return;

      const sel = document.getElementById('invPackage');
      if (sel && packageId) {
        for (let i = 0; i < sel.options.length; i++) {
          if (String(sel.options[i].value) === String(packageId)) {
            sel.selectedIndex = i;
            break;
          }
        }
      }

      const amt = document.getElementById('invAmount');
      if (sel && amt && !amt.value) {
        const opt = sel.options[sel.selectedIndex];
        const min = Number(opt.getAttribute('data-min') || 0);
        amt.value = min ? min : '';
      }

      syncInvestPreview();
      m.style.display = 'flex';
    }
    function closeInvest() {
      const m = document.getElementById('investModal');
      if (m) m.style.display = 'none';
    }

    function syncInvestPreview() {
      const sel = document.getElementById('invPackage');
      const amt = Number(document.getElementById('invAmount')?.value || 0);
      if (!sel) return;

      const opt = sel.options[sel.selectedIndex];
      const roi = Number(opt.getAttribute('data-roi') || 0);
      const period = (opt.getAttribute('data-period') || 'Daily');
      const dur = Number(opt.getAttribute('data-duration') || 0);
      const min = Number(opt.getAttribute('data-min') || 0);
      const max = Number(opt.getAttribute('data-max') || 0);

      document.getElementById('invRoiTxt').textContent = `${roi.toFixed(2)}% ${period}`;
      document.getElementById('invDurTxt').textContent = `${dur} days`;

      const rate = roi / 100;
      const periodLower = String(period).toLowerCase();
      let perDay = 0;
      if (periodLower.includes('daily')) perDay = amt * rate;
      else if (periodLower.includes('weekly')) perDay = (amt * rate) / 7;
      else if (periodLower.includes('monthly')) perDay = (amt * rate) / 30;
      else perDay = amt * rate;

      const totalRoi = perDay * dur;
      document.getElementById('invTotTxt').textContent = fmtUSD(totalRoi);

      const limitNote = document.getElementById('invLimitNote');
      if (limitNote) {
        if (amt && (amt < min || amt > max)) {
          limitNote.textContent = `Limit: ${fmtUSD(min)} to ${fmtUSD(max)}. Please enter a valid amount for this package.`;
          limitNote.style.borderColor = '#ffedd5';
          limitNote.style.background = '#fff7ed';
        } else {
          limitNote.textContent = `Wallet Balance: <?= moneyUSD($wallet_balance_usd); ?> • Limit: ${fmtUSD(min)} to ${fmtUSD(max)} • USD only.`;
          limitNote.style.borderColor = '#e7e7f3';
          limitNote.style.background = '#fbfbff';
        }
      }
    }

    // --------------------- Rules Modal ---------------------
    function openRules() {
      const m = document.getElementById('rulesModal');
      if (m) m.style.display = 'flex';
    }
    function closeRules() {
      const m = document.getElementById('rulesModal');
      if (m) m.style.display = 'none';
    }

    // --------------------- Investment details route ---------------------
    function openInvestment(ref) {
      if (!ref) { toastMini("No reference"); return; }
      location.href = "<?= base_url('user/invest/'); ?>" + encodeURIComponent(ref);
    }

    // Toast
    function toastMini(msg) {
      const t = document.createElement('div');
      t.textContent = msg;
      t.style.cssText =
        "position:fixed;bottom:22px;left:50%;transform:translateX(-50%);background:#111;color:#fff;padding:10px 14px;border-radius:14px;font-weight:1000;font-size:12px;z-index:99999;opacity:0;transition:.2s;";
      document.body.appendChild(t);
      requestAnimationFrame(() => t.style.opacity = "1");
      setTimeout(() => { t.style.opacity = "0"; setTimeout(() => t.remove(), 250); }, 1400);
    }

    // --------------------- HERO RING draw ---------------------
    (function () {
      const ring = document.querySelector('.ring');
      if (!ring) return;
      const pct = Number(ring.getAttribute('data-pct') || 0);
      const deg = Math.max(0, Math.min(100, pct)) * 3.6;
      ring.style.background = `conic-gradient(#22C55E 0deg ${deg}deg, rgba(255,255,255,.16) ${deg}deg 360deg)`;
      const label = document.getElementById('heroPct');
      if (label) label.textContent = pct + '%';
    })();
  </script>
  <!--begin::Global Javascript Bundle(mandatory for all pages)-->
  <script src="<?php echo base_url(); ?>assets/user/plugins/global/plugins.bundle.js"></script>
  <script src="<?php echo base_url(); ?>assets/user/js/scripts.bundle.js"></script>

  <script>
    $(document).on('submit', '#investForm', function (e) {
      e.preventDefault();

      const $form = $(this);
      const url = $form.attr('action');

      $.ajax({
        url: url,
        type: "POST",
        data: $form.serialize(),
        dataType: "json",
        success: function (res) {
          if (res && res.status) {

            // 1) Stripe redirect
            if (res.redirect_url) {
              window.location.href = res.redirect_url;
              return;
            }

            // 2) PayPal auto-submit html
            if (res.paypal_html) {
              $('body').append(res.paypal_html);
              return;
            }

            // 3) Wallet success (or generic)
            toastMini(res.message || "Success");
            closeInvest();

            // if you want refresh the page to show new investment list:
            setTimeout(() => location.href = "<?= $redirect_url ?>", 600);
            return;

          } else {
            toastMini((res && res.message) ? res.message : "Failed");
          }
        },
        error: function (xhr) {
          toastMini("Server error. Please try again.");
          console.log(xhr.responseText);
        }
      });
    });
  </script>
  <script>
    const modal = document.getElementById("invModal");
    const modalBody = document.getElementById("invModalBody");
    const modalClose = document.getElementById("invModalClose");

    let CURRENT_INVEST_ID = null;
    let CURRENT_PAGE = 1;
    let CURRENT_LIMIT = 10;

    modalClose.onclick = () => { modal.style.display = "none"; };

    async function loadInvDetails(investId, page = 1) {
      CURRENT_INVEST_ID = investId;
      CURRENT_PAGE = page;

      modal.style.display = "block";
      modalBody.innerHTML = "Loading...";

      try {
        const res = await fetch("<?= base_url('user/investments/details_ajax') ?>", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "invest_id=" + encodeURIComponent(investId)
            + "&page=" + encodeURIComponent(page)
            + "&limit=" + encodeURIComponent(CURRENT_LIMIT)
        });

        const data = await res.json();
        if (!data.status) {
          modalBody.innerHTML = `<div style="color:red;">${data.message || "Failed"}</div>`;
          return;
        }

        const p = data.pagination;

        // table rows
        let html = `
        <div style="margin-bottom:10px;">
          <b>Package:</b> ${data.investment.package} &nbsp; | &nbsp;          
        </div>
<div class="table-scroll">
        <table class="table" style="width:100%; border-spacing:0 8px;">
          <thead>
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Amount</th>
              <th>Description</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
      `;

        if (!data.rows.length) {
          html += `<tr><td colspan="5">No ROI history found</td></tr>`;
        } else {
          data.rows.forEach(r => {
            html += `
            <tr style="background:#fbfbff; border:1px solid rgba(17,24,39,.06);">
              <td>${r.history_date}</td>
              <td>${r.type}</td>
              <td><b>${r.amount}</b></td>
              <td>${r.description}</td>
              <td>${r.status == 1 ? "Success" : "Pending"}</td>
            </tr>
          `;
          });
        }

        html += `</tbody></table></div>`;

        // ✅ pagination controls
        const prevDisabled = (p.page <= 1) ? "disabled" : "";
        const nextDisabled = (p.page >= p.total_pages) ? "disabled" : "";

        html += `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px;">
          <div>
            Showing page <b>${p.page}</b> of <b>${p.total_pages}</b> 
            (Total: ${p.total_rows})
          </div>

          <div style="display:flex; gap:8px;">
            <button class="btn-mini" ${prevDisabled} onclick="loadInvDetails(${CURRENT_INVEST_ID}, ${p.page - 1})">Prev</button>
            <button class="btn-mini" ${nextDisabled} onclick="loadInvDetails(${CURRENT_INVEST_ID}, ${p.page + 1})">Next</button>
          </div>
        </div>
      `;

        modalBody.innerHTML = html;

      } catch (e) {
        modalBody.innerHTML = `<div style="color:red;">Server error</div>`;
      }
    }

    document.addEventListener("click", (e) => {
      const btn = e.target.closest(".js-invest-details");
      if (!btn) return;
      const investId = btn.dataset.investId;
      loadInvDetails(investId, 1);
    });
  </script>
</body>

</html>