<?php

// ===================== USER INVESTMENT + ROI PAGE (PRO UI • USD ONLY) =====================
// NOTE: UI template. Connect values from DB/admin settings.
// It does NOT give financial advice. Show your own legal disclaimers/terms.

// --------------------- Demo / Fallback data (replace with DB) ---------------------



$user = $user ?? (object) [
  'name' => 'LucasSATZ',
  'uid' => 'NEXMAN123',
];

$wallet_balance_usd = $wallet_balance_usd ?? 0.00;
$roi_history = $roi_history ?? [];
$recent_staking_activity = $recent_staking_activity ?? [];

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
          <h2><i class="ph-fill ph-chart-pie"></i> Stakings</h2>
          <p>Put your USD balance to work. Choose a verified package, track your daily ROI credits, and grow your
            portfolio with transparency.</p>

          <div style="margin-top: 20px; display:flex; gap: 12px; flex-wrap:wrap;">
          
            <button class="btn-invest"
              style="background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.22);" onclick="openRules()">
              <i class="ph ph-info"></i> ROI Rules
            </button>
          </div>
        </div>

        <!-- GRAPHICAL BLOBS -->
        <div class="hero-blobs"><span></span><span></span><span></span></div>
      </div>

      <!-- KPI summary cards removed in Phase 2 (Stakings redesign): Available USDT /
           Total Invested / Total ROI / Next Payout / Active Plans now live only on
           the Wallet/Dashboard and are no longer duplicated here. -->
      <?php if (false): ?>
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-icon"><i class="ph ph-wallet"></i></div>
          <small>Available USDT Balance</small>
          <b><?= number_format((float)($wallet_usdt ?? 0), 2) ?> <span style="font-size:13px;font-weight:900;color:var(--muted);">USDT</span></b>
          <?php if (!empty($wallet_usdt_in_bman)): ?>
            <span style="font-size:11.5px;font-weight:900;color:var(--muted);">≈ <?= number_format((float)$wallet_usdt_in_bman) ?> BMAN</span>
          <?php endif; ?>
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
      <?php endif; // KPI grid disabled (Phase 2) ?>

      <!-- WALLET STRIP — USDT is the staking FUNDING wallet; Earning receives ROI;
           the other three BMAN wallets are shown for context. -->
      <?php
      $wallet_usdt = $wallet_usdt ?? 0;
      $wallet_usdt_in_bman = $wallet_usdt_in_bman ?? null;
      $wallet_bman = $wallet_bman ?? ['exchange'=>0,'staking'=>0,'bonus'=>0,'earning'=>0];
      // [label, icon, colour, tag]
      // Order: USDT (fixed first) → Exchange → Earning → Staking → Bonus
      $wstrip = [
        'exchange' => ['Exchange Wallet', 'ph-swap',        '#6366f1', ''],
        'earning'  => ['Earning Wallet',  'ph-trend-up',    '#0ea5e9', 'ROI credited here'],
        'staking'  => ['Staking Wallet',  'ph-lock-key',    '#10b981', ''],
        'bonus'    => ['Bonus Wallet',    'ph-gift',        '#f59e0b', ''],
      ];
      ?>
      <style>
        .wstrip{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin:4px 0 22px;}
        .wstrip .wtile{display:flex;align-items:center;gap:12px;background:#fff;border:1px solid rgba(15,23,42,.08);
          border-radius:16px;padding:14px 16px;box-shadow:0 6px 18px rgba(15,23,42,.04);}
        .wstrip .wtile.usdt{border:1.5px solid #26a17b55;background:linear-gradient(135deg,#26a17b0d,#fff);}
        .wstrip .wico{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;font-size:20px;flex:0 0 auto;}
        .wstrip .wlbl{font-size:11.5px;font-weight:900;color:var(--muted,#6b7280);text-transform:uppercase;letter-spacing:.3px;
          display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
        .wstrip .wval{font-size:18px;font-weight:1200;color:#0b1220;line-height:1.1;}
        .wstrip .wval small{font-size:11px;font-weight:900;color:var(--muted,#6b7280);}
        .wstrip .wsub{font-size:11px;font-weight:800;color:var(--muted,#6b7280);margin-top:2px;}
        .wstrip .wtag{font-size:9px;font-weight:900;letter-spacing:.2px;padding:2px 7px;border-radius:99px;
          background:#0ea5e91a;color:#0284c7;text-transform:uppercase;}
        .wstrip .wtag.src{background:#26a17b1a;color:#1b8f6b;}
      </style>
      <div class="wstrip">
        <!-- USDT — the wallet staking purchases are funded from -->
        <div class="wtile usdt">
          <div class="wico" style="background:#26a17b1a;color:#26a17b;"><i class="ph ph-currency-circle-dollar"></i></div>
          <div>
            <div class="wlbl">USDT Wallet <span class="wtag src">Staking source</span></div>
            <div class="wval"><?= number_format((float)$wallet_usdt, 2) ?> <small>USDT</small></div>
            <?php if ($wallet_usdt_in_bman !== null): ?>
            <div class="wsub">≈ <?= number_format((float)$wallet_usdt_in_bman) ?> BMAN at current rate</div>
            <?php endif; ?>
          </div>
        </div>
        <?php foreach ($wstrip as $k => $m): ?>
        <div class="wtile">
          <div class="wico" style="background:<?= $m[2] ?>1a;color:<?= $m[2] ?>;"><i class="ph <?= $m[1] ?>"></i></div>
          <div>
            <div class="wlbl"><?= $m[0] ?><?php if (!empty($m[3])): ?> <span class="wtag"><?= $m[3] ?></span><?php endif; ?></div>
            <div class="wval"><?= number_format((float)($wallet_bman[$k] ?? 0)) ?> <small>BMAN</small></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- BMAN STAKING PACKAGES (from staking system) -->
      <?php $this->load->view('user/wallet/_staking_packages', [
        'staking_packages' => $staking_packages ?? [],
        'staking_plans'    => $staking_plans ?? [],
        'owned_stake_ids'  => $owned_stake_ids ?? [],
        'swap_enabled'     => $swap_enabled ?? 0,
        'swap_enabled'     => $swap_enabled ?? 0,
      ]); ?>

      <!-- Legacy "Select Your Plan" packages + ROI calculator removed (staking packages shown above). -->

      <!-- MY STAKINGS PORTFOLIO SECTION REMOVED -->

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

      <?php if (!empty($recent_staking_activity) || !empty($roi_history)): ?>
      <div class="card" style="margin-top: 18px; border-radius: 28px;">
        <div class="card-h" style="padding:18px 22px;border-bottom:1px solid #f0f0f7;margin:0;display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;">
          <h3 style="font-size:16px;font-weight:1100;margin:0;">Recent Staking Activity</h3>
          <span class="chip"><i class="ph ph-clock-counter-clockwise"></i> On-chain Swaps</span>
        </div>
        <div style="padding:16px 20px;">
          <div class="table-scroll">
            <table class="table" style="border-spacing:0 8px;min-width:920px;">
              <thead>
                <tr><th>Date</th><th>Type</th><th>USDT</th><th>BMAN</th><th>Status</th><th>Description</th><th>Action</th></tr>
              </thead>
              <tbody>
                <?php if (empty($recent_staking_activity)): ?>
                  <tr><td colspan="7" style="text-align:center;color:#9ca3af;padding:18px;">No recent staking activity found.</td></tr>
                <?php else: foreach ($recent_staking_activity as $row): ?>
                  <tr style="cursor:pointer;transition:background 0.2s;" onclick="showSwapDetails(<?= (int)($row->order_id ?? 0) ?>)" onmouseover="this.style.background='#f9f9fb'" onmouseout="this.style.background=''">
                    <td style="font-size:12px;"><?= htmlspecialchars((string)($row->history_date ?? '—')) ?></td>
                    <td><b><?= htmlspecialchars((string)($row->type ?? '—')) ?></b></td>
                    <td><?= number_format((float)($row->amount ?? 0), 2) ?></td>
                    <td><?= number_format((float)($row->token_amount ?? 0), 0) ?></td>
                    <td>
                      <?php
                        $status = $row->status ?? '—';
                        $badge_class = 'secondary';
                        if (strpos($status, 'pending') !== false) $badge_class = 'warning';
                        elseif ($status === 'swap_completed') $badge_class = 'success';
                        elseif (strpos($status, 'failed') !== false) $badge_class = 'danger';
                      ?>
                      <span style="display:inline-block;padding:4px 8px;border-radius:6px;font-size:11px;font-weight:900;background:var(--<?= $badge_class ?>);color:#fff;">
                        <?= ucfirst(str_replace('_', ' ', $status)) ?>
                      </span>
                    </td>
                    <td style="font-size:11px;color:#666;"><?= htmlspecialchars((string)($row->description ?? '—')) ?></td>
                    <td><button class="btn-soft" onclick="event.stopPropagation();showSwapDetails(<?= (int)($row->order_id ?? 0) ?>)" style="padding:6px 10px;font-size:11px;">Details</button></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- ===================== SWAP DETAILS MODAL ===================== -->
      <div class="modal-backdrop" id="swapDetailsModal" style="display:none;z-index:2000;">
        <div class="modal" style="max-width:800px;max-height:90vh;overflow-y:auto;">
          <div class="modal-h">
            <b>Staking Purchase Details</b>
            <button class="xbtn" onclick="closeSwapDetails()" style="cursor:pointer;"><i class="ph ph-x"></i></button>
          </div>

          <div class="modal-b" id="swapDetailsContent" style="padding:20px;">
            <div style="text-align:center;padding:40px;">
              <div class="spinner-border text-primary"></div>
              <p style="margin-top:12px;color:#666;">Loading details...</p>
            </div>
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

    // --------------------- Swap Details Modal ---------------------
    function showSwapDetails(orderId) {
      if (!orderId) { toastMini("Invalid order ID"); return; }

      const modal = document.getElementById('swapDetailsModal');
      const content = document.getElementById('swapDetailsContent');

      if (!modal || !content) return;

      modal.style.display = 'flex';

      // Fetch details via AJAX
      fetch('<?= base_url("user/lending/swap_order_details"); ?>', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({ order_id: orderId })
      })
      .then(res => res.json())
      .then(data => {
        if (!data.status) {
          content.innerHTML = `<div style="color:red;text-align:center;">Error: ${data.message}</div>`;
          return;
        }

        const d = data.data;
        const s = d.status_info || {};

        // Build HTML
        let html = `
          <div style="margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
              <div style="font-size:24px;color:var(--${s.color || 'secondary'});">
                <i class="ph ph-${s.icon || 'question-mark'}"></i>
              </div>
              <div>
                <h4 style="margin:0;font-weight:1000;color:#111;">${s.label || d.current_status}</h4>
                <p style="margin:4px 0 0;font-size:12px;color:#666;">Order: <code>${d.ref}</code></p>
              </div>
            </div>

            <div style="background:#f9f9fb;border-radius:12px;padding:12px;margin-bottom:16px;">
              <p style="margin:0;font-size:11px;color:#666;">Created: <b>${d.created_at}</b></p>
              <p style="margin:4px 0 0;font-size:11px;color:#666;">Updated: <b>${d.updated_at}</b></p>
            </div>
          </div>

          <!-- Swap Status Progress -->
          <div style="background:linear-gradient(135deg,rgba(99,102,241,.05),rgba(34,197,94,.05));border:1px solid #e7e7f3;border-radius:12px;padding:12px;margin-bottom:16px;">
            <div style="font-size:10px;color:#666;font-weight:900;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px;">📊 SWAP STATUS</div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
              <div style="flex:1;height:6px;background:#e7e7f3;border-radius:3px;overflow:hidden;">
                <div style="height:100%;background:linear-gradient(90deg,#667eea,#22c55e);width:${d.cron_status?.gas === 1 && d.cron_status?.usdt === 1 && d.cron_status?.bonus === 1 ? 100 : (d.cron_status?.gas === 1 && d.cron_status?.usdt === 1 ? 66 : (d.cron_status?.gas === 1 ? 33 : 10))}%;"></div>
              </div>
              <div style="font-size:11px;color:#4338ca;font-weight:900;">${d.status_info?.label || 'Processing'}</div>
            </div>
          </div>

          <!-- BMAN Values Summary -->
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px;">
            <div class="card" style="box-shadow:none;border:1px solid #e7e7f3;background:#fbfbff;">
              <div style="font-size:10px;color:#666;font-weight:900;margin-bottom:6px;text-transform:uppercase;">💵 USDT Payment</div>
              <div style="font-size:18px;font-weight:1100;color:#334155;">${d.amounts.usdt.toFixed(2)}</div>
              <div style="font-size:9px;color:#999;margin-top:4px;">sent</div>
            </div>
            <div class="card" style="box-shadow:none;border:2px solid #22c55e;background:#f0fdf4;">
              <div style="font-size:10px;color:#15803d;font-weight:900;margin-bottom:6px;text-transform:uppercase;">🚀 BMAN To Receive</div>
              <div style="font-size:18px;font-weight:1100;color:#22c55e;">${Math.floor(d.amounts.bman).toLocaleString()}</div>
              <div style="font-size:9px;color:#15803d;margin-top:4px;">pending</div>
            </div>
            <div class="card" style="box-shadow:none;border:1px solid #e7e7f3;background:#fef3c7;">
              <div style="font-size:10px;color:#666;font-weight:900;margin-bottom:6px;text-transform:uppercase;">🎁 Bonus BMAN</div>
              <div style="font-size:18px;font-weight:1100;color:#b45309;">+ ${Math.floor(d.amounts.bonus_bman).toLocaleString()}</div>
              <div style="font-size:9px;color:#999;margin-top:4px;">extra</div>
            </div>
          </div>

          <div style="border-top:1px solid #e7e7f3;padding-top:12px;margin-bottom:16px;">
            <h5 style="margin:0 0 10px;font-size:13px;font-weight:1000;color:#111;">Distribution (Option ${d.distribution.option})</h5>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:12px;">
              <div><span style="color:#666;">Exchange Wallet:</span> <b>${Math.floor(d.distribution.exchange_bman).toLocaleString()} BMAN</b></div>
              <div><span style="color:#666;">Earning Wallet:</span> <b>${Math.floor(d.distribution.earning_bman).toLocaleString()} BMAN</b></div>
              <div><span style="color:#666;">Staking Wallet:</span> <b>${Math.floor(d.distribution.staking_bman).toLocaleString()} BMAN</b></div>
              <div><span style="color:#666;">Bonus Wallet:</span> <b>${Math.floor(d.distribution.bonus_bman).toLocaleString()} BMAN</b></div>
            </div>
          </div>

          <div style="border-top:1px solid #e7e7f3;padding-top:12px;margin-bottom:16px;">
            <h5 style="margin:0 0 10px;font-size:13px;font-weight:1000;color:#111;">Plan Details</h5>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:12px;">
              <div><span style="color:#666;">Plan:</span> <b>${d.plan.code}</b></div>
              <div><span style="color:#666;">Duration:</span> <b>${d.plan.duration_years} year(s)</b></div>
            </div>
          </div>

          <div style="border-top:1px solid #e7e7f3;padding-top:12px;margin-bottom:16px;background:#f9f9fb;border-radius:10px;padding:12px;">
            <h5 style="margin:0 0 12px;font-size:13px;font-weight:1000;color:#111;display:flex;align-items:center;gap:8px;">
              <i class="ph ph-chart-line"></i> ROI Details & Returns
            </h5>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
              <div style="background:#fff;border:1px solid #e7e7f3;border-radius:10px;padding:10px;text-align:center;">
                <div style="font-size:10px;color:#666;margin-bottom:6px;font-weight:900;">PRINCIPAL</div>
                <div style="font-size:16px;font-weight:1100;color:#4338ca;margin-bottom:2px;">${Math.floor(d.amounts.bman).toLocaleString()}</div>
                <div style="font-size:10px;color:#666;">BMAN</div>
                <div style="font-size:9px;color:#ef4444;margin-top:4px;">🔒 LOCKED</div>
              </div>
              <div style="background:#fff;border:1px solid #e7e7f3;border-radius:10px;padding:10px;text-align:center;">
                <div style="font-size:10px;color:#666;margin-bottom:6px;font-weight:900;">EXPECTED ROI</div>
                <div style="font-size:16px;font-weight:1100;color:#22c55e;margin-bottom:2px;">${calcROI(d.amounts.bman, d.roi_rate, d.plan.duration_years).toLocaleString()}</div>
                <div style="font-size:10px;color:#666;">BMAN</div>
                <div style="font-size:9px;color:#22c55e;margin-top:4px;">🔓 LIQUID</div>
              </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:12px;">
              <div style="background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);border-radius:10px;padding:8px;text-align:center;">
                <div style="font-size:9px;color:#4338ca;font-weight:1000;">ROI RATE</div>
                <div style="font-size:13px;font-weight:1100;color:#4338ca;margin-top:2px;">${d.roi_rate}%</div>
              </div>
              <div style="background:rgba(234,179,8,.08);border:1px solid rgba(234,179,8,.2);border-radius:10px;padding:8px;text-align:center;">
                <div style="font-size:9px;color:#a16207;font-weight:1000;">DURATION</div>
                <div style="font-size:13px;font-weight:1100;color:#a16207;margin-top:2px;">${d.plan.duration_years} Year${d.plan.duration_years > 1 ? 's' : ''}</div>
              </div>
            </div>

            <div style="background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);border-radius:10px;padding:10px;">
              <div style="font-size:10px;font-weight:1000;color:#4338ca;margin-bottom:6px;">✓ Key Points</div>
              <div style="font-size:10px;color:#334155;line-height:1.5;">
                <div style="margin-bottom:3px;">• <strong>Principal is LOCKED</strong> until maturity</div>
                <div style="margin-bottom:3px;">• <strong>ROI is LIQUID</strong> earned hourly</div>
                <div>• <strong>At Maturity:</strong> <span style="font-weight:1100;color:#4338ca;">${Math.floor(d.amounts.bman + calcROI(d.amounts.bman, d.roi_rate, d.plan.duration_years)).toLocaleString()} BMAN</span></div>
              </div>
            </div>
          </div>

          <div style="border-top:1px solid #e7e7f3;padding-top:12px;margin-bottom:16px;">
            <h5 style="margin:0 0 10px;font-size:13px;font-weight:1000;color:#111;">Transaction Steps</h5>
            <div style="font-size:12px;">
        `;

        // Transaction steps
        const steps = [
          { key: 'gas', label: '1. Gas Fee (BNB)', data: d.transactions.gas },
          { key: 'usdt', label: '2. USDT Payment', data: d.transactions.usdt },
          { key: 'bonus', label: '3. Bonus BMAN', data: d.transactions.bonus },
          { key: 'bman_exchange', label: '4. Exchange BMAN', data: d.transactions.bman_exchange },
          { key: 'bman_earning', label: '5. Earning BMAN', data: d.transactions.bman_earning },
          { key: 'bman_staking', label: '6. Staking BMAN', data: d.transactions.bman_staking },
          { key: 'bman_bonus', label: '7. Bonus Wallet BMAN', data: d.transactions.bman_bonus },
        ];

        steps.forEach(step => {
          const status = step.data.status === 'confirmed' ? '✓' : '○';
          const statusColor = step.data.status === 'confirmed' ? '#22C55E' : '#9ca3af';
          const txDisplay = step.data.tx_hash ? step.data.tx_hash.substring(0, 10) + '...' : 'pending';

          html += `
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;padding:8px;background:#f9f9fb;border-radius:6px;">
              <div style="width:20px;height:20px;border-radius:50%;background:${statusColor};color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:1000;">${status}</div>
              <div style="flex:1;">
                <div style="font-size:12px;font-weight:900;color:#111;">${step.label}</div>
                <div style="font-size:11px;color:#666;">
                  ${step.data.tx_hash ? `<code>${txDisplay}</code>` : '<i>Waiting...</i>'}
                  ${step.data.explorer ? ` <a href="${step.data.explorer}" target="_blank" style="color:#667eea;text-decoration:none;"><i class="ph ph-arrow-up-right"></i></a>` : ''}
                </div>
              </div>
            </div>
          `;
        });

        html += `
            </div>
          </div>
        `;

        // Error message if any
        if (d.error) {
          html += `
            <div style="background:#fff5f5;border:1px solid #feb2b2;border-radius:8px;padding:12px;margin-bottom:12px;">
              <p style="margin:0;font-size:12px;font-weight:900;color:#c53030;">Error:</p>
              <p style="margin:4px 0 0;font-size:11px;color:#742a2a;">${d.error}</p>
            </div>
          `;
        }

        html += `<div style="margin-top:16px;padding-top:12px;border-top:1px solid #e7e7f3;">
          <button class="btn-soft" onclick="closeSwapDetails()" style="width:100%;padding:10px;cursor:pointer;">Close</button>
        </div>`;

        content.innerHTML = html;
      })
      .catch(err => {
        console.error(err);
        content.innerHTML = `<div style="color:red;text-align:center;">Failed to load details</div>`;
      });
    }

    function closeSwapDetails() {
      const modal = document.getElementById('swapDetailsModal');
      if (modal) modal.style.display = 'none';
    }

    // Calculate ROI based on principal, rate, and duration
    function calcROI(principal, ratePercent, years) {
      if (!principal || !ratePercent || !years) return 0;
      return Math.floor(principal * (ratePercent / 100) * (years / 1));
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

    // (legacy loadInvDetails kept above but no longer bound — replaced by the
    //  full-screen 7-tab modal below.)
  </script>

  <!-- ============ PHASE 4: full-screen 7-tab Investment Details modal ============ -->
  <script>
  (function(){
    var BASE='<?= base_url() ?>';
    var esc=function(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});};
    var ov=document.createElement('div'); ov.id='sdrawer';
    ov.style.cssText='display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:99999;';
    ov.innerHTML='<div style="position:absolute;inset:0;margin:auto;max-width:1000px;width:100%;height:100%;background:#fff;display:flex;flex-direction:column;">'
      +'<div style="display:flex;justify-content:space-between;align-items:center;padding:16px 22px;border-bottom:1px solid #eef;">'
      +'<h3 id="sd-title" style="margin:0;font-size:17px;font-weight:1100;">Investment Details</h3>'
      +'<button id="sd-x" style="border:0;background:#f1f5f9;border-radius:10px;width:36px;height:36px;font-size:18px;cursor:pointer;">&times;</button></div>'
      +'<div id="sd-tabs" style="display:flex;gap:4px;padding:10px 18px 0;flex-wrap:wrap;border-bottom:1px solid #eef;overflow-x:auto;"></div>'
      +'<div id="sd-body" style="padding:18px 22px;overflow:auto;flex:1;"></div></div>';
    document.body.appendChild(ov);
    var TABS=[['Package','tab1'],['ROI History','tab2'],['Transactions','tab3'],['Ledger','tab4'],['Timeline','tab5'],['Documents','tab6'],['Audit','tab7']];
    var DATA=null, rendered={};
    function q(id){return document.getElementById(id);}
    function close(){ov.style.display='none';}
    q('sd-x').onclick=close; ov.addEventListener('click',function(e){if(e.target===ov)close();});
    function tabBar(active){
      q('sd-tabs').innerHTML=TABS.map(function(t,i){return '<button data-i="'+i+'" style="border:0;border-bottom:3px solid '+(i===active?'#4338ca':'transparent')+';background:none;padding:9px 14px;font-weight:900;font-size:13px;cursor:pointer;color:'+(i===active?'#4338ca':'#64748b')+';white-space:nowrap;">'+esc(t[0])+'</button>';}).join('');
      q('sd-tabs').querySelectorAll('button').forEach(function(b){b.onclick=function(){show(+b.dataset.i);};});
    }
    function show(i){tabBar(i);var key=TABS[i][1];if(!rendered[key])rendered[key]=RENDER[key](DATA);q('sd-body').innerHTML=rendered[key];} // lazy render
    var row=function(k,v){return '<div style="display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-bottom:1px dashed #eef;font-size:13px;"><span style="color:#64748b;font-weight:800;">'+esc(k)+'</span><span style="text-align:right;word-break:break-all;font-weight:700;">'+(v==null||v===''?'<span style="color:#cbd5e1;">—</span>':v)+'</span></div>';};
    function tbl(cols,rows,fn){var h='<div class="table-scroll"><table class="table" style="width:100%;font-size:12.5px;"><thead><tr>'+cols.map(function(c){return '<th>'+esc(c)+'</th>';}).join('')+'</tr></thead><tbody>';if(!rows||!rows.length)h+='<tr><td colspan="'+cols.length+'" style="text-align:center;color:#9ca3af;padding:14px;">No records</td></tr>';else h+=rows.map(fn).join('');return h+'</tbody></table></div>';}
    var RENDER={
      tab1:function(d){var t=d.tab1_package,c=d.calc;return row('Package ID',t.invest_id)+row('Package Name',esc(t.package_name))+row('Stake Amount',Number(t.stake_amount).toLocaleString())+row('Plan Type',esc(t.plan_type))+row('ROI Structure',esc(t.roi_structure))+row('Purchase Date',esc(t.purchase_date))+row('Activation Date',esc(t.activation_date))+row('Maturity Date',esc(t.maturity_date))+row('Lock Period',t.lock_period_days+' days')+row('Days Remaining',c.days_remaining)+row('Status',esc(t.status))+row('Wallet Used',esc(t.wallet_used))+row('Tx Hash',t.explorer_link?'<a href="'+t.explorer_link+'" target="_blank">'+esc(String(t.tx_hash).slice(0,18))+'…</a>':esc(t.tx_hash));},
      tab2:function(d){var t=d.tab2_roi;var tot='<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px;">'
        +'<div style="background:#f0fdf4;border-radius:12px;padding:10px 14px;font-weight:900;color:#15803d;">Earned: '+Number(t.totals.total_earned).toLocaleString(undefined,{maximumFractionDigits:4})+'</div>'
        +'<div style="background:#fffbeb;border-radius:12px;padding:10px 14px;font-weight:900;color:#b45309;">Remaining: '+Number(t.totals.remaining).toLocaleString(undefined,{maximumFractionDigits:4})+'</div>'
        +'<div style="background:#eef2ff;border-radius:12px;padding:10px 14px;font-weight:900;color:#4338ca;">Expected Final: '+Number(t.totals.expected_final).toLocaleString(undefined,{maximumFractionDigits:4})+'</div></div>';
        return tot+tbl(['Date','Cycle','ROI %','Amount','Wallet','Status'],t.rows,function(r){return '<tr><td>'+esc(r.date)+'</td><td>'+esc(r.cycle)+'</td><td>'+esc(r.roi_percent)+'%</td><td><b>'+Number(r.amount).toLocaleString(undefined,{maximumFractionDigits:4})+'</b></td><td>'+esc(r.wallet_credited)+'</td><td>'+esc(r.chain_status)+'</td></tr>';});},
      tab3:function(d){return tbl(['Tx','Block','Date','Type','Wallet','Amount','Gas Fee','Status'],d.tab3_transactions,function(r){return '<tr><td>'+(r.tx_hash?'<a href="'+esc(r.explorer)+'" target="_blank" style="font-family:monospace;">'+esc(String(r.tx_hash).slice(0,12))+'…</a>':'—')+'</td><td>'+esc(r.block_number||'—')+'</td><td style="font-size:11px;">'+esc((r.created_at||'').slice(0,16))+'</td><td>'+esc(r.tx_type)+'</td><td>'+esc(r.wallet_type)+'</td><td>'+esc(r.amount)+'</td><td>'+esc(r.gas_fee_total||'—')+'</td><td>'+esc(r.status)+'</td></tr>';});},
      tab4:function(d){return tbl(['Date','Wallet','Credit','Debit','Balance After','Reference','Description'],d.tab4_ledger,function(r){return '<tr><td style="font-size:11px;">'+esc((r.created_at||'').slice(0,16))+'</td><td>'+esc(r.wallet_type)+'</td><td style="color:#16a34a;">'+esc(r.credit)+'</td><td style="color:#dc2626;">'+esc(r.debit)+'</td><td>'+esc(r.balance_after)+'</td><td style="font-size:11px;">'+esc(r.reference_type)+'</td><td style="font-size:11px;">'+esc(r.description||'—')+'</td></tr>';});},
      tab5:function(d){return '<div>'+d.tab5_timeline.map(function(e){return '<div style="position:relative;padding:0 0 18px 20px;border-left:2px solid #e0e7ff;margin-left:6px;"><span style="position:absolute;left:-7px;top:2px;width:12px;height:12px;border-radius:50%;background:#4338ca;"></span><b style="font-size:13px;">'+esc(e.event)+'</b><div style="font-size:11.5px;color:#64748b;">'+esc(e.date)+'</div><div style="font-size:12.5px;">'+esc(e.desc)+'</div></div>';}).join('')+'</div>';},
      tab6:function(d){return '<div style="display:grid;gap:10px;">'+d.tab6_documents.map(function(x){return '<div style="display:flex;justify-content:space-between;align-items:center;background:#f8fafc;border-radius:12px;padding:12px 16px;"><span style="font-weight:800;">'+esc(x.name)+'</span>'+(x.available&&x.url?'<a class="btn-mini" href="'+esc(x.url)+'" target="_blank">Open</a>':'<span style="color:#cbd5e1;font-size:12px;font-weight:800;">Unavailable</span>')+'</div>';}).join('')+'</div>';},
      tab7:function(d){return tbl(['Event','Status','Actor','IP','Endpoint','When'],d.tab7_audit,function(r){return '<tr><td>'+esc(r.event_type)+'</td><td>'+esc(r.new_status||'—')+'</td><td>'+esc(r.actor_type)+(r.actor_id?(' #'+esc(r.actor_id)):'')+'</td><td style="font-size:11px;">'+esc(r.ip_address||'—')+'</td><td style="font-size:10px;">'+esc(r.rpc_endpoint||'—')+'</td><td style="font-size:11px;">'+esc((r.created_at||'').slice(0,16))+'</td></tr>';});}
    };
    document.addEventListener('click',function(e){
      var btn=e.target.closest('.js-invest-details'); if(!btn)return;
      var id=btn.dataset.investId; ov.style.display='block';
      q('sd-title').textContent='Investment #'+id; q('sd-tabs').innerHTML=''; q('sd-body').innerHTML='Loading blockchain details…';
      DATA=null; rendered={};
      var fd=new FormData(); fd.append('invest_id',id);
      fetch(BASE+'user/stakings/detail',{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){return r.json();}).then(function(j){
          if(!j.status){q('sd-body').innerHTML='<div style="color:#ef4444;">'+esc(j.message||'Failed')+'</div>';return;}
          DATA=j; show(0);
        }).catch(function(){q('sd-body').innerHTML='<div style="color:#ef4444;">Server error</div>';});
    });
  })();
  </script>
</body>

</html>