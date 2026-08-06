<?php
// ===================== BINARY TREE PAGE (USER • ADVANCED UI) =====================
// Expected vars from controller (sample):
// $user = (object)['uid'=>'NEXMAN123','name'=>'Lucas','rank'=>'SILVER','left_bv'=>2450,'right_bv'=>1120,'left_cf'=>320,'right_cf'=>0,'pairs'=>3];
// $tree = array root node with children
// Node format:
// [
//   'uid' => 'NEXMAN123',
//   'name' => 'Lucas',
//   'rank' => 'SILVER',
//   'avatar' => 'https://i.pravatar.cc/100?u=1',
//   'status' => 'ACTIVE', // ACTIVE/INACTIVE/BLOCKED
//   'left_bv' => 2450, 'right_bv'=>1120,
//   'join_date' => '2026-01-10',
//   'left' => [...node...], 'right' => [...node...]
// ]
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php $this->load->view('user/layout/v2/user_style'); ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <style>
    /* ===================== BINARY TREE ===================== */
    /* ===== SHARED BREAKPOINT SCALE — see assets/user_v2/css/style.css =====
       1400 xxl · 1200 xl · 1024 lg (must match user_sidebar.php JS) · 768 md · 600 sm · 380 xs
       ===================================================================== */
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

    .btn-dark {
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

    /* Summary strip */
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

    /* Layout */
    .grid-2 {
      display: grid;
      grid-template-columns: 1.35fr .65fr;
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
      font-size: 14px;
      font-weight: 1000;
      margin: 0;
    }

    .mini-note {
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 800;
    }

    /* Controls */
    .controls {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
    }

    .search {
      flex: 1;
      min-width: 220px;
      border: 1px solid #f1f1f6;
      background: #f7f7fb;
      border-radius: 14px;
      padding: 11px 12px;
      outline: none;
      font-size: 12px;
    }

    .search:focus {
      background: #fff;
      border-color: #dcd7ff;
      box-shadow: 0 0 0 4px rgba(110, 86, 207, 0.10);
    }

    .sel {
      border: 1px solid #f1f1f6;
      background: #f7f7fb;
      border-radius: 14px;
      padding: 11px 12px;
      outline: none;
      font-size: 12px;
    }

    .zoom {
      display: flex;
      gap: 8px;
    }

    .zbtn {
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

    .zbtn:hover {
      transform: translateY(-1px);
      transition: .15s;
    }

    /* Tree Canvas */
    .tree-wrap {
      background: linear-gradient(180deg, #fbfbff 0%, #ffffff 70%);
      border: 1px solid #f5f5f7;
      border-radius: 22px;
      overflow: hidden;
    }

    .tree-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 12px 14px;
      border-bottom: 1px solid #f5f5f7;
      background: #fff;
    }

    .legend {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
      font-size: 11px;
      font-weight: 900;
      color: #111827;
    }

    .lg {
      display: inline-flex;
      gap: 8px;
      align-items: center;
      padding: 6px 10px;
      border-radius: 999px;
      border: 1px solid #f1f1f6;
      background: #fff;
    }

    .dot {
      width: 10px;
      height: 10px;
      border-radius: 999px;
      background: #22c55e;
      display: inline-block;
    }

    .dot.inactive {
      background: #f97316;
    }

    .dot.blocked {
      background: #ef4444;
    }

    .dot.empty {
      background: #d4d4d8;
    }

    /* ===== Tree view selector tabs ===== */
    .tv-tabs {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      padding: 12px 14px;
      border-bottom: 1px solid #f5f5f7;
    }
    .tv-tab {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      border: 1px solid #ececf5;
      background: #fff;
      color: #4b5563;
      font-weight: 800;
      font-size: 12.5px;
      padding: 9px 13px;
      border-radius: 12px;
      cursor: pointer;
      white-space: nowrap;
      transition: .15s;
    }
    .tv-tab:hover { background: #f7f6ff; }
    .tv-tab.active {
      background: var(--primary, #6e56cf);
      border-color: var(--primary, #6e56cf);
      color: #fff;
    }

    /* ===== Pagination: breadcrumb trail + "load more downline" node ===== */
    .tree-breadcrumb {
      display: none;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
      padding: 10px 14px;
      border-bottom: 1px solid #f5f5f7;
      font-size: 12px;
      font-weight: 800;
      color: #6b7280;
    }
    .tree-breadcrumb a {
      color: var(--primary, #6e56cf);
      text-decoration: none;
      cursor: pointer;
    }
    .tree-breadcrumb a:hover { text-decoration: underline; }
    .tree-breadcrumb i { color: #c7c7d1; font-size: 11px; }

    .node.more-node {
      align-items: center;
      justify-content: center;
      width: 220px;
      min-height: 90px;
      border: 1.5px dashed #c9c3f5;
      background: #f7f6ff;
      color: var(--primary, #6e56cf);
      font-weight: 900;
      font-size: 12.5px;
      text-align: center;
      gap: 6px;
    }
    .node.more-node:hover {
      background: #efeaff;
      transform: translateY(-2px);
    }
    .node.more-node i { font-size: 20px; }

    /* ===== Flat views (genealogy / generation / level / direct) ===== */
    .alt-view { display: block; width: 100%; }
    .alt-empty {
      text-align: center;
      color: #9ca3af;
      font-weight: 700;
      padding: 40px 12px;
    }
    .mini-card {
      display: flex;
      align-items: center;
      gap: 12px;
      background: #fff;
      border: 1px solid #eef0f6;
      border-left: 4px solid #10b981;
      border-radius: 14px;
      padding: 10px 12px;
      cursor: pointer;
      box-shadow: 0 4px 14px rgba(15, 23, 42, .04);
      transition: .15s;
    }
    .mini-card:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(15, 23, 42, .08); }
    .mini-card.inactive { border-left-color: #f59e0b; }
    .mini-card.blocked  { border-left-color: #ef4444; }
    .mini-card.empty    { border-left-color: #cbd5e1; }
    .mini-card .mc-av { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; flex: 0 0 auto; }
    .mini-card .mc-meta { min-width: 0; flex: 1; }
    .mini-card .mc-meta b { display: block; font-size: 13px; font-weight: 900; color: #0b1220; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mini-card .mc-meta small { font-size: 11px; color: #6b7280; font-weight: 700; }
    .mini-card .mc-inv { text-align: right; flex: 0 0 auto; }
    .mini-card .mc-inv small { display: block; font-size: 10px; color: #6b7280; font-weight: 700; }
    .mini-card .mc-inv b { font-size: 13px; font-weight: 900; color: #10b981; }
    .mini-card.more-mini {
      border-left-color: var(--primary, #6e56cf);
      border-style: dashed;
      background: #f7f6ff;
      color: var(--primary, #6e56cf);
      font-weight: 900;
      font-size: 12.5px;
    }
    .mini-card.more-mini:hover { background: #efeaff; }
    .mini-card.more-mini i { font-size: 18px; }

    .alt-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .alt-col .alt-h, .alt-lvl .alt-h {
      font-size: 12px; font-weight: 900; color: #4b5563; text-transform: uppercase;
      letter-spacing: .3px; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;
    }
    .alt-badge {
      font-size: 10px; font-weight: 900; padding: 3px 9px; border-radius: 99px;
      background: #efedfb; color: var(--primary, #6e56cf); text-transform: none; letter-spacing: 0;
    }
    .alt-levels { display: grid; gap: 18px; }
    .alt-lvl .alt-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 10px; }

    .geneal-list, .geneal-list ul { list-style: none; margin: 0; padding: 0; }
    .geneal-list ul { margin-left: 22px; padding-left: 16px; border-left: 2px dashed #e5e7eb; }
    .geneal-list li { margin: 8px 0; }
    .geneal-list li > .mini-card { max-width: 460px; }

    @media (max-width: 600px) {
      .alt-cols { grid-template-columns: 1fr; }
    }

    .tree-canvas {
      height: 74vh;
      min-height: 640px;
      overflow: auto;
      position: relative;
      padding: 32px 24px 24px;
      background-color: #fafaff;
      background-image: radial-gradient(rgba(110, 86, 207, 0.12) 1.5px, transparent 1.5px);
      background-size: 24px 24px;
    }

    /* big draggable area */
    .tree-inner {
      width: max-content;
      min-width: 100%;
      padding-bottom: 16px;
      transform-origin: 0 0;
    }

    /* Tree lines & connectors */
    .tree {
      display: flex;
      justify-content: center;
    }

    .tree ul {
      padding-top: 36px;
      position: relative;
      transition: .2s;
      display: flex;
      justify-content: center;
      gap: 20px;
    }

    .tree li {
      list-style-type: none;
      text-align: center;
      position: relative;
      padding: 36px 6px 0 6px;
    }

    /* connectors */
    .tree li::before,
    .tree li::after {
      content: '';
      position: absolute;
      top: 0;
      right: 50%;
      border-top: 2px solid #dcd7fe;
      width: 50%;
      height: 36px;
    }

    .tree li::after {
      right: auto;
      left: 50%;
      border-left: 2px solid #dcd7fe;
    }

    .tree li:only-child::after,
    .tree li:only-child::before {
      display: none;
    }

    .tree li:only-child {
      padding-top: 0;
    }

    .tree li:first-child::before,
    .tree li:last-child::after {
      border: none;
    }

    .tree li:last-child::before {
      border-right: 2px solid #dcd7fe;
      border-radius: 0 12px 0 0;
    }

    .tree li:first-child::after {
      border-radius: 12px 0 0 0;
    }

    .tree ul ul::before {
      content: '';
      position: absolute;
      top: 0;
      left: 50%;
      border-left: 2px solid #dcd7fe;
      width: 0;
      height: 36px;
    }

    /* Node Card - Compact, Sleek & Delightful */
    .node {
      display: inline-flex;
      flex-direction: column;
      gap: 8px;
      width: 196px;
      background: #ffffff;
      border: 1px solid #e8e6fb;
      border-radius: 18px;
      box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
      padding: 11px 12px;
      cursor: pointer;
      transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
      position: relative;
      overflow: hidden;
      text-align: left;
    }

    .node:hover {
      transform: translateY(-4px) scale(1.02);
      border-color: #a79bf2;
      box-shadow: 0 16px 36px -6px rgba(110, 86, 207, 0.22);
      z-index: 10;
    }

    /* Left leg — Indigo accent */
    .node-left {
      border-left: 3.5px solid #6366f1;
    }
    .node-left:hover {
      background: linear-gradient(180deg, #f5f3ff 0%, #ffffff 100%);
    }

    /* Right leg — Emerald accent */
    .node-right {
      border-right: 3.5px solid #10b981;
    }
    .node-right:hover {
      background: linear-gradient(180deg, #ecfdf5 0%, #ffffff 100%);
    }

    .node-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }

    .node-user {
      display: flex;
      align-items: center;
      gap: 8px;
      min-width: 0;
      flex: 1 1 auto;
    }

    .av {
      width: 36px;
      height: 36px;
      border-radius: 12px;
      background: #f1f1f8;
      object-fit: cover;
      flex-shrink: 0;
      border: 1.5px solid #fff;
      box-shadow: 0 4px 10px rgba(15, 23, 42, 0.08);
    }

    .nm {
      font-size: 12px;
      font-weight: 900;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      line-height: 1.2;
      color: #0f172a;
    }

    .id {
      font-size: 10px;
      color: #64748b;
      font-weight: 800;
      margin-top: 1px;
    }

    .rank {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 3px 7px;
      border-radius: 999px;
      font-size: 9.5px;
      font-weight: 900;
      border: 1px solid #e2d9fe;
      background: #f4f0ff;
      color: #6e56cf;
      white-space: nowrap;
      flex-shrink: 0;
    }

    /* Pulsing status indicator dot */
    .st {
      position: absolute;
      top: 10px;
      right: 10px;
      left: auto;
      width: 9px;
      height: 9px;
      border-radius: 999px;
      background: #10b981;
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
    }
    .st::after {
      content: '';
      position: absolute;
      inset: -2px;
      border-radius: 999px;
      background: inherit;
      opacity: 0.6;
      animation: stPulse 2s infinite ease-in-out;
    }
    @keyframes stPulse {
      0%, 100% { transform: scale(1); opacity: 0.6; }
      50% { transform: scale(1.8); opacity: 0; }
    }

    .node.inactive .st { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2); }
    .node.blocked .st { background: #ef4444; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2); }
    .node.empty .st { background: #cbd5e1; box-shadow: 0 0 0 3px rgba(203, 213, 225, 0.2); }

    .node-mid-compact {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 6px;
      margin-top: 2px;
    }

    .kv-compact {
      background: #f8fafc;
      border: 1px solid #f1f5f9;
      border-radius: 10px;
      padding: 5px 7px;
      text-align: left;
    }
    .kv-compact small {
      display: block;
      font-size: 9px;
      color: #64748b;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.2px;
    }
    .kv-compact b {
      display: block;
      font-size: 11px;
      font-weight: 900;
      color: #1e293b;
      margin-top: 1px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .node-btm-compact {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 4px;
      margin-top: 2px;
    }

    .pill-sm {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 8px;
      border-radius: 999px;
      font-size: 9.5px;
      font-weight: 900;
      border: 1px solid #e2e8f0;
      background: #f8fafc;
      color: #334155;
    }
    .pill-sm.pill-eligible {
      background: #f0fdf4;
      border-color: #bbf7d0;
      color: #15803d;
    }
    .pill-sm.pill-ineligible {
      background: #fffbe6;
      border-color: #fef08a;
      color: #854d0e;
    }

    /* ===== Floating member card — hover popover on desktop, tap-to-pin on touch ===== */
    #treeNodeTooltip {
      position: fixed;
      z-index: 99999;
      pointer-events: none;
      display: none;
      opacity: 0;
      transform: translateY(6px) scale(0.96);
      transition: opacity 0.2s cubic-bezier(0.16, 1, 0.3, 1), transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
      width: 320px;
      background: #fff;
      border: 1px solid #eef0f6;
      box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18), 0 4px 14px rgba(15, 23, 42, 0.08);
      border-radius: 22px;
      color: #1e293b;
      overflow: hidden;
    }

    #treeNodeTooltip.active {
      display: block;
      opacity: 1;
      transform: translateY(0) scale(1);
    }

    /* Pinned = touch tap mode: centred fixed card + dim backdrop instead of
       following the pointer (there is no pointer to follow on a touchscreen). */
    #treeNodeTooltip.pinned {
      pointer-events: auto;
      left: 50% !important;
      top: 50% !important;
      transform: translate(-50%, -50%) scale(0.96);
      width: min(340px, calc(100vw - 32px));
      max-height: calc(100vh - 64px);
      overflow-y: auto;
    }
    #treeNodeTooltip.pinned.active { transform: translate(-50%, -50%) scale(1); }

    #ttBackdrop {
      position: fixed;
      inset: 0;
      z-index: 99998;
      background: rgba(15, 23, 42, 0.45);
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.2s ease;
    }
    #ttBackdrop.active { opacity: 1; pointer-events: auto; }

    .tt-accent { height: 5px; width: 100%; background: #10b981; }

    .tt-close {
      display: none;
      position: absolute;
      top: 12px;
      right: 12px;
      z-index: 2;
      width: 28px;
      height: 28px;
      border-radius: 999px;
      align-items: center;
      justify-content: center;
      border: none;
      background: #f1f5f9;
      color: #64748b;
      cursor: pointer;
      font-size: 14px;
    }
    #treeNodeTooltip.pinned .tt-close { display: flex; }

    .tt-body { padding: 16px; }

    .tt-header {
      display: flex;
      align-items: center;
      gap: 12px;
      padding-bottom: 14px;
      border-bottom: 1px dashed #eef0f6;
    }
    .tt-av-wrap { position: relative; flex: 0 0 auto; }
    .tt-av {
      width: 52px;
      height: 52px;
      border-radius: 16px;
      object-fit: cover;
      background: #f1f5f9;
      border: 2px solid #fff;
      box-shadow: 0 0 0 1px #eef0f6;
    }
    .tt-av-badge {
      position: absolute;
      right: -6px;
      bottom: -6px;
      width: 22px;
      height: 22px;
      border-radius: 999px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #f6c453, #d97706);
      color: #fff;
      font-size: 11px;
      border: 2px solid #fff;
      box-shadow: 0 2px 6px rgba(217, 119, 6, 0.4);
    }
    .tt-user { min-width: 0; flex: 1; }
    .tt-name { font-size: 14.5px; font-weight: 900; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tt-uid { font-size: 11px; color: #64748b; font-weight: 700; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tt-badges { display: flex; gap: 6px; margin-top: 7px; flex-wrap: wrap; }
    .tt-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 9.5px; font-weight: 900; padding: 3px 8px; border-radius: 99px; background: #f1f5f9; color: #475569; text-transform: uppercase; letter-spacing: .3px; }
    .tt-badge.tt-active { background: #dcfce7; color: #15803d; }
    .tt-badge.tt-inactive { background: #fef3c7; color: #92400e; }
    .tt-badge.tt-rank { background: #eef2ff; color: #4338ca; text-transform: none; }

    .tt-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 14px; }
    .tt-card { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 14px; padding: 10px 12px; }
    .tt-card.tt-full { grid-column: 1 / -1; }
    .tt-card.tt-highlight { background: #f5f3ff; border-color: #ede9fe; }
    .tt-card.tt-held { background: #fffbeb; border-color: #fef3c7; }
    .tt-card label { display: flex; align-items: center; gap: 5px; font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.3px; color: #64748b; font-weight: 800; }
    .tt-card val { display: block; font-size: 14px; font-weight: 900; color: #0f172a; margin-top: 4px; }
    .tt-card val span { font-size: 10px; color: #6e56cf; font-weight: 800; margin-left: 3px; }

    .tt-bar { margin-top: 8px; height: 6px; border-radius: 999px; background: #ede9fe; overflow: hidden; }
    .tt-bar-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #8b5cf6, #6e56cf); width: 0%; transition: width .3s ease; }

    .tt-mini-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-top: 8px; }
    .tt-mini { background: #fff; border: 1px solid #f1f5f9; border-radius: 10px; padding: 6px 8px; text-align: center; }
    .tt-mini label { display: block; font-size: 8.5px; text-transform: uppercase; letter-spacing: .3px; color: #94a3b8; font-weight: 800; }
    .tt-mini val { display: block; font-size: 11px; font-weight: 900; color: #334155; margin-top: 2px; }

    .tt-rows { margin-top: 8px; }
    .tt-row { display: flex; align-items: center; gap: 7px; font-size: 11.5px; font-weight: 700; color: #475569; padding: 5px 2px; }
    .tt-row i { color: #94a3b8; font-size: 14px; }

    .tt-eligible {
      margin-top: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      padding: 9px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 900;
      background: #f0fdf4;
      color: #15803d;
      border: 1px solid #bbf7d0;
    }
    .tt-eligible.ineligible { background: #fffbeb; color: #92400e; border-color: #fef08a; }

    .tt-footer {
      margin-top: 12px;
      padding-top: 10px;
      border-top: 1px dashed #eef0f6;
      font-size: 10.5px;
      color: #94a3b8;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      text-align: center;
    }

    /* Side: Member Details */
    .profile {
      display: flex;
      gap: 12px;
      align-items: center;
      padding: 12px;
      border-radius: 20px;
      border: 1px solid #f5f5f7;
      background: #fff;
    }

    .profile img {
      width: 54px;
      height: 54px;
      border-radius: 20px;
      object-fit: cover;
      background: #f2f2f7;
    }

    .profile b {
      display: block;
      font-size: 13px;
      font-weight: 1000;
    }

    .profile small {
      display: block;
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 900;
      margin-top: 3px;
    }

    .mini-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-top: 12px;
    }

    .tile {
      border: 1px solid #f1f1f6;
      background: #f7f7fb;
      border-radius: 18px;
      padding: 12px;
    }

    .tile small {
      display: block;
      font-size: 10px;
      color: var(--text-muted);
      font-weight: 1000;
    }

    .tile b {
      display: block;
      font-size: 14px;
      font-weight: 1000;
      margin-top: 4px;
    }

    .actions-col {
      display: grid;
      gap: 10px;
      margin-top: 12px;
    }

    .btn-full {
      width: 100%;
      border: none;
      border-radius: 16px;
      padding: 12px 14px;
      cursor: pointer;
      font-weight: 1000;
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
      width: min(620px, 100%);
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
      font-weight: 1000;
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

    .note {
      font-size: 12px;
      color: var(--text-muted);
      font-weight: 900;
      line-height: 1.4;
    }

    /* Responsive */
    @media(max-width:1200px) {
      .sum-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .grid-2 {
        grid-template-columns: 1fr;
      }

      .tree-inner {
        min-width: 900px;
      }
    }

    @media(max-width:600px) {
      .sum-grid {
        grid-template-columns: 1fr;
      }

      /* .tree-inner/.node width no longer forced here — below 768px the
         binary tab renders via renderCompactMobile(), not this scrolling tree. */
    }

    /* ===================== RESPONSIVE PATCH: BINARY TREE (ADD AT END) ===================== */

    /* Tablet */
    @media (max-width: 1024px) {
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
      .page-actions .btn-main,
      .page-actions .btn-dark {
        flex: 1 1 auto;
        justify-content: center;
      }

      /* .sum-grid/.grid-2 already set by the 1200px block above — same values,
         still in effect down through this tier. */

      .tree-canvas {
        height: 60vh;
        min-height: 540px;
      }

      .tree-inner {
        min-width: 860px;
        /* still scrollable but less wide — this tier keeps the desktop tree */
      }

      .node-btm {
        grid-template-columns: 1fr;
      }
    }

    /* Mobile */
    @media (max-width: 600px) {

      /* Title + actions */
      .page-titlebar h2 {
        font-size: 16px;
      }

      .page-actions .btn-soft,
      .page-actions .btn-main,
      .page-actions .btn-dark {
        flex: 1 1 calc(50% - 6px);
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

      /* Cards */
      .card,
      .tree-wrap {
        border-radius: 18px;
        padding: 12px;
      }

      /* Toolbar layout: make it wrap cleanly */
      .tree-toolbar {
        padding: 10px 12px;
      }

      .controls {
        width: 100%;
        gap: 8px;
      }

      .search {
        flex: 1 1 100%;
        min-width: 0;
      }

      .sel {
        flex: 1 1 100%;
        width: 100%;
      }

      .zoom {
        width: 100%;
        justify-content: space-between;
      }

      .zbtn {
        flex: 1 1 calc(50% - 6px);
        width: auto;
        height: 44px;
        border-radius: 16px;
      }

      /* Make genealogy button full-width */
      .btn-dark {
        width: 100%;
        justify-content: center;
        border-radius: 16px;
        padding: 12px 12px;
      }

      /* Legend wrap */
      .legend {
        gap: 8px;
        font-size: 10px;
      }

      .lg {
        padding: 6px 9px;
      }

      /* Tree canvas/.tree-inner min-width and .tree ul/li connector spacing
         removed here — below 768px renderCompactMobile() replaces the
         connector-line tree entirely, so those band-aid rules no longer apply. */

      /* Node smaller (compact mobile cards; width itself comes from the
         .tree-compact .node rule in the 768px block) */
      .node {
        border-radius: 20px;
        padding: 11px;
      }

      /* Focus/children stack full-width below 600px too */
      .tc-children {
        flex-direction: column;
      }

      .av {
        width: 38px;
        height: 38px;
        border-radius: 13px;
      }

      .rank {
        padding: 6px 9px;
        font-size: 9px;
      }

      .kv {
        border-radius: 14px;
        padding: 9px 10px;
      }

      .pill {
        padding: 7px 9px;
        font-size: 9px;
      }

      /* Side panel */
      .profile {
        border-radius: 18px;
        padding: 10px;
      }

      .profile img {
        width: 48px;
        height: 48px;
        border-radius: 18px;
      }

      .tile {
        border-radius: 16px;
        padding: 10px;
      }

      .btn-full {
        border-radius: 16px;
        padding: 12px 12px;
      }

      .row2 {
        grid-template-columns: 1fr;
      }

      /* Modal padding */
      .modal {
        border-radius: 18px;
      }
    }

    /* Compact mobile binary-tree view — focus node + Left/Right children only,
       replacing the connector-line org chart below 768px. Populated by
       renderCompactMobile(); see the script block near renderBinaryProgressive(). */
    @media (max-width: 768px) {
      .tree-canvas.tc-active {
        height: auto;
        min-height: 0;
        overflow: visible;
        padding: 16px 14px;
      }

      /* .tree-inner's base width:max-content (needed so the desktop tree can
         grow wider than its canvas and scroll) would otherwise starve the
         compact view's flex children of a real width to fill. */
      .tree-canvas.tc-active .tree-inner {
        width: 100%;
      }

      .tree-compact {
        display: flex;
        flex-direction: column;
        gap: 16px;
        width: 100%;
      }

      .tc-tag {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: var(--primary, #6e56cf);
        margin-bottom: 8px;
      }

      .tc-children {
        display: flex;
        gap: 12px;
      }

      .tc-child-col {
        flex: 1 1 0;
        min-width: 0;
      }

      .tc-child-label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 900;
        color: #6b7280;
        margin-bottom: 8px;
      }

      /* !important: wins over the pre-existing "Compact" toolbar button,
         which sets an inline node width for the desktop tree's density
         toggle — a fixed small width doesn't apply to this full-width layout. */
      .tree-compact .node,
      .tree-compact .more-node {
        width: 100% !important;
      }

      .tc-empty-slot {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 110px;
        border: 1.5px dashed #e5e7eb;
        border-radius: 20px;
        color: #9ca3af;
        font-size: 11px;
        font-weight: 800;
        text-align: center;
        padding: 12px;
      }

      .tc-empty-slot i {
        font-size: 20px;
      }
    }
  </style>
</head>

<body>
  <div class="app-container">
    <?php $this->load->view('user/layout/v2/user_sidebar'); ?>

    <main class="main-content">
      <?php $this->load->view('user/layout/v2/user_header'); ?>

      <!-- Title -->
      <div class="page-titlebar">
        <div>
          <h2><i class="ph ph-tree-structure"></i> Binary Tree</h2>
          <div class="sub">Explore your left/right leg network, positions, investment & activity status.</div>
        </div>

        <div class="page-actions">
          <button class="btn-soft" type="button" onclick="centerTree()"><i class="ph ph-crosshair"></i> Center</button>
          <button class="btn-soft" type="button" onclick="toggleCompact()"><i class="ph ph-layout"></i> Compact</button>
          <button class="btn-soft" type="button" id="exportPngBtn" onclick="exportTreePNG()"><i class="ph ph-download-simple"></i> Export PNG</button>
          <button class="btn-main" type="button" onclick="location.href='<?= base_url('user/referrals'); ?>'"><i
              class="ph ph-share-network"></i> Invite</button>
        </div>
      </div>

      <!-- Summary -->
      <div class="sum-grid">
        <div class="sum-card">
          <div class="sum-ic sum-info"><i class="ph ph-arrow-circle-left"></i></div>
          <div class="sum-meta">
            <small>Left Leg Investment</small>
            <strong><?= number_format($user->left_lock ?? 0, 2); ?> BMAN</strong>
            <span>Lock Wallet · Left downline</span>
          </div>
        </div>

        <div class="sum-card">
          <div class="sum-ic sum-warn"><i class="ph ph-arrow-circle-right"></i></div>
          <div class="sum-meta">
            <small>Right Leg Investment</small>
            <strong><?= number_format($user->right_lock ?? 0, 2); ?> BMAN</strong>
            <span>Lock Wallet · Right downline</span>
          </div>
        </div>

        <div class="sum-card">
          <div class="sum-ic sum-good"><i class="ph ph-link"></i></div>
          <div class="sum-meta">
            <small>Pairs Completed</small>
            <strong><?= number_format($user->pairs ?? 0); ?> Pairs</strong>
            <span>Pairing running</span>
          </div>
        </div>

        <div class="sum-card">
          <div class="sum-ic"><i class="ph ph-medal"></i></div>
          <div class="sum-meta">
            <small>Current Rank</small>
            <strong><?= htmlspecialchars($user->rank ?? '—'); ?></strong>
            <span>Maintain activity to rank up</span>
          </div>
        </div>
      </div>

      <div class="grid-2">

        <!-- Tree -->
        <div class="card tree-wrap">
          <!-- Tree view selector -->
          <div class="tv-tabs">
            <button class="tv-tab active" type="button" onclick="setTreeView('binary', event)"><i class="ph ph-tree-structure"></i> Binary Tree</button>
            <button class="tv-tab" type="button" onclick="setTreeView('genealogy', event)"><i class="ph ph-graph"></i> Genealogy Tree</button>
            <button class="tv-tab" type="button" onclick="setTreeView('generation', event)"><i class="ph ph-stack"></i> Generation Tree</button>
            <button class="tv-tab" type="button" onclick="setTreeView('levelwise', event)"><i class="ph ph-list-numbers"></i> Level Wise Team</button>
            <button class="tv-tab" type="button" onclick="setTreeView('direct', event)"><i class="ph ph-users-three"></i> Direct Team View</button>
          </div>
          <div class="tree-toolbar">
            <div class="controls" style="width:100%;">
              <input class="search" id="nodeSearch" placeholder="Search UID / name..." />
              <select class="sel" id="depthSel">
                <option value="3">Depth: 3 Levels</option>
                <option value="4">Depth: 4 Levels</option>
                <option value="5">Depth: 5 Levels</option>
                <option value="6">Depth: 6 Levels</option>
                <option value="7">Depth: 7 Levels</option>
                <option value="8">Depth: 8 Levels</option>
                <option value="9">Depth: 9 Levels</option>
                <option value="10" selected>Depth: 10 Levels</option>
              </select>

              <div class="zoom">
                <button class="zbtn" type="button" title="Zoom Out" onclick="zoomBy(-0.1)"><i
                    class="ph ph-minus"></i></button>
                <button class="zbtn" type="button" title="Zoom In" onclick="zoomBy(0.1)"><i
                    class="ph ph-plus"></i></button>
              </div>

              <button class="zbtn" type="button" title="Refresh Tree" onclick="refreshTree()"><i
                  class="ph ph-arrow-clockwise"></i></button>

              <button class="btn-dark" type="button" onclick="location.href='<?= base_url('user/genealogy'); ?>'">
                Genealogy <i class="ph ph-graph"></i>
              </button>
            </div>
          </div>

          <div class="tree-toolbar" style="border-top:1px solid #f5f5f7;">
            <div class="legend">
              <span class="lg"><span class="dot"></span> Active</span>
              <span class="lg"><span class="dot inactive"></span> Inactive</span>
              <span class="lg"><span class="dot blocked"></span> Blocked</span>
              <span class="lg"><span class="dot empty"></span> Empty</span>
              <span class="mini-note" style="margin-left:auto;">Tip: Click any member card for details.</span>
            </div>
          </div>

          <!-- Pagination trail: shown once the user drills into a "load more downline" node -->
          <div class="tree-breadcrumb" id="treeBreadcrumb"></div>

          <div class="tree-canvas" id="treeCanvas">
            <div class="tree-inner" id="treeInner">
              <div class="tree" id="treeRoot"></div>
            </div>
          </div>
        </div>

        <!-- Side Panel -->
        <div class="card">
          <div class="card-h">
            <h3>Member Details</h3>
            <span class="mini-note">Selected</span>
          </div>

          <div class="profile" id="sideProfile">
            <img id="sideAvatar"
              src="<?= !empty($tree['avatar']) ? $tree['avatar'] : default_avatar_url(); ?>" alt=""
              <?= avatar_onerror(); ?>>
            <div style="min-width:0;">
              <b id="sideName"><?= htmlspecialchars($tree['name'] ?? ucfirst($user->name ?? '—')); ?></b>
              <small id="sideUid">UID: <?= htmlspecialchars($tree['uid'] ?? ($user->uid ?? '—')); ?></small>
              <small id="sideRank">Rank: <?= htmlspecialchars($tree['rank'] ?? ($user->rank ?? '—')); ?></small>
            </div>
          </div>

          <div class="mini-grid">
            <div class="tile">
              <small>Status</small>
              <b id="sideStatus">ACTIVE</b>
            </div>
            <div class="tile">
              <small>Join Date</small>
              <b id="sideJoin"><?= htmlspecialchars($tree['join_date'] ?? '—'); ?></b>
            </div>
            <div class="tile">
              <small>Left Investment</small>
              <b id="sideLBV">0 BMAN</b>
            </div>
            <div class="tile">
              <small>Right Investment</small>
              <b id="sideRBV">0 BMAN</b>
            </div>
            <div class="tile" style="grid-column:1 / -1;">
              <small>Own Investment (Lock Wallet)</small>
              <b id="sideInv"><?= number_format($tree['lock_wallet'] ?? 0, 2); ?> BMAN</b>
            </div>
            <div class="tile">
              <small>Own Active Stake</small>
              <b id="sideOwnStake">0 BMAN</b>
            </div>
            <div class="tile">
              <small>Matching Ceiling</small>
              <b id="sideCeiling">0 / 0 BMAN</b>
            </div>
            <div class="tile" style="grid-column:1 / -1;">
              <small>Matching Eligibility</small>
              <b><span id="sideEligiblePill" class="pill" style="display:inline-flex;margin-top:4px;"><i class="ph ph-warning-circle"></i>&nbsp;<span id="sideEligibleText">Needs Stake</span></span>
                <span id="sideHeldPill" class="pill" style="display:none;margin-top:4px;margin-left:6px;"><i class="ph ph-lock"></i>&nbsp;Held <span id="sideHeldAmt">0</span> BMAN</span></b>
            </div>
          </div>

          <div class="actions-col">
            <button class="btn-full primary" type="button" onclick="goMember()"><i class="ph ph-user"></i> View Member
              Profile</button>
            <button class="btn-full" type="button" onclick="openModal()"><i class="ph ph-info"></i> View BV & Pair
              Rules</button>
            <button class="btn-full dark" type="button" onclick="location.href='<?= base_url('user/referrals'); ?>'"><i
                class="ph ph-share-network"></i> Invite to Left/Right</button>
          </div>

          <div style="margin-top:12px;" class="note">
            Keep your weak leg active to increase pairing frequency. Use BV from orders to maintain team volume.
          </div>
        </div>

      </div>
    </main>    
  </div>

  <!-- Modal -->
  <div class="modal-backdrop" id="modalBack">
    <div class="modal">
      <div class="modal-h">
        <b>BV &amp; Pair Rules</b>
        <button class="xbtn" onclick="closeModal()"><i class="ph ph-x"></i></button>
      </div>
      <div class="modal-b">
        <div class="note">
          A pair completes when your left and right legs both build matching team investment. Keep both legs active and
          balanced to maximise pairing income.
        </div>
        <div class="row2">
          <div class="tile">
            <small>Improve Pairing</small>
            <b>Balance Investment</b>
            <div class="note" style="margin-top:6px;">Add investment to your weak leg to keep both sides balanced.</div>
          </div>
          <div class="tile">
            <small>Eligibility</small>
            <b>KYC + Active</b>
            <div class="note" style="margin-top:6px;">Complete KYC and keep account active for payouts.</div>
          </div>
        </div>
        <button class="btn-full primary" style="margin-top:12px;" onclick="closeModal()">
          Got it <i class="ph ph-check"></i>
        </button>
      </div>
  </div>

  <!-- Floating member card: hover on desktop, tap-to-pin on touch (see "Node Card Logic" JS) -->
  <div id="treeNodeTooltip">
    <button type="button" class="tt-close" onclick="closeTooltipPinned()" aria-label="Close details"><i class="ph ph-x"></i></button>
    <div class="tt-accent" id="ttAccent"></div>
    <div class="tt-body">
      <div class="tt-header">
        <div class="tt-av-wrap">
          <img id="ttAvatar" class="tt-av" src="" alt="" onerror="this.onerror=null;this.src='<?= default_avatar_url(); ?>';">
          <span class="tt-av-badge" id="ttRankBadge"><i class="ph ph-user"></i></span>
        </div>
        <div class="tt-user">
          <div id="ttName" class="tt-name">User Name</div>
          <div id="ttEmail" class="tt-uid">UID: —</div>
          <div class="tt-badges">
            <span id="ttStatus" class="tt-badge tt-active">ACTIVE</span>
            <span id="ttRank" class="tt-badge tt-rank"><i class="ph ph-medal"></i> —</span>
          </div>
        </div>
      </div>

      <div class="tt-grid">
        <div class="tt-card">
          <label><i class="ph ph-arrow-circle-left"></i> Left Invest</label>
          <val id="ttLinv">0 <span>BMAN</span></val>
        </div>
        <div class="tt-card">
          <label><i class="ph ph-arrow-circle-right"></i> Right Invest</label>
          <val id="ttRinv">0 <span>BMAN</span></val>
        </div>
        <div class="tt-card tt-full tt-highlight">
          <label><i class="ph ph-wallet"></i> Own Investment (Exchange)</label>
          <val id="ttExchange">0 <span>BMAN</span></val>
        </div>
        <div class="tt-card tt-full">
          <label><i class="ph ph-coins"></i> Own Active Stake</label>
          <val id="ttOwnStake">0 <span>BMAN</span></val>
        </div>
      </div>

      <div class="tt-mini-row">
        <div class="tt-mini"><label>Earning</label><val id="ttEarning">0</val></div>
        <div class="tt-mini"><label>Staking</label><val id="ttStaking">0</val></div>
        <div class="tt-mini"><label>Bonus</label><val id="ttBonus">0</val></div>
      </div>

      <div class="tt-grid" style="margin-top:8px;">
        <div class="tt-card tt-full">
          <label><i class="ph ph-trend-up"></i> Matching Ceiling (Remaining / Total)</label>
          <val id="ttCeiling">0 / 0 <span>BMAN</span></val>
          <div class="tt-bar"><div class="tt-bar-fill" id="ttCeilingBar"></div></div>
        </div>
        <div class="tt-card tt-full tt-held" id="ttHeldRow" style="display:none;">
          <label><i class="ph ph-lock"></i> Ceiling Wallet Held</label>
          <val id="ttHeld">0 <span>BMAN</span></val>
        </div>
      </div>

      <div class="tt-rows">
        <div class="tt-row"><i class="ph ph-calendar"></i> Joined <span id="ttJoin">—</span></div>
      </div>

      <div class="tt-eligible" id="ttEligiblePill">
        <i class="ph ph-check-circle" id="ttEligibleIcon"></i> <span id="ttEligibleText">Matching Eligible</span>
      </div>

      <div class="tt-footer" id="ttFooter">
        <i class="ph ph-cursor-click"></i> <span>Click card to select &amp; view member profile</span>
      </div>
    </div>
  </div>
  <div id="ttBackdrop"></div>

  <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>

  <script>
    // ✅ AJAX endpoints
    const TREE_URL = "<?= base_url('user/usersettings/genealogycontroller/tree_json'); ?>";
    const MEMBER_URL = "<?= base_url('user/usersettings/genealogycontroller/member_json/'); ?>";
    const DEFAULT_AVATAR = "<?= default_avatar_url(); ?>";

    let TREE = {};
    let scale = 1;
    let compact = false;

    // ✅ render config
    const SHOW_EMPTY = false; // <<<<< important: false = hide empty nodes

    function nodeIcon(title) {
      title = (title || "").toLowerCase();
      if (title.includes("silver") || title.includes("gold") || title.includes("diamond")) return "ph-medal";
      return "ph-user";
    }

    function statusClass(s) {
      s = (s || "").toUpperCase();
      if (s === "BLOCKED") return "blocked";
      if (s === "INACTIVE") return "inactive";
      if (s === "EMPTY") return "empty";
      return "";
    }

    function fmt(n) {
      n = Number(n || 0);
      return n.toLocaleString();
    }

    function escapeHtml(s) {
      s = String(s ?? "");
      return s.replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
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

    // Pagination: rootId re-roots the fetch at a downline member instead of the
    // logged-in user, so "load more" can page arbitrarily deep without ever
    // fetching/rendering more than one page's worth of levels at a time.
    async function loadTree(depth = 10, rootId = null) {
      try {
        let url = `${TREE_URL}?depth=${encodeURIComponent(depth)}`;
        if (rootId) url += `&root_id=${encodeURIComponent(rootId)}`;
        const res = await fetch(url, { credentials: 'same-origin' });
        const json = await res.json();

        if (!json || json.status !== true) {
          toastMini(json?.message || "Failed to load tree");
          TREE = {};
          return;
        }

        TREE = json.data || {};
        renderCurrentView(depth);

      } catch (e) {
        console.error(e);
        toastMini("Tree load error");
      }
    }

    // ======= Pagination: "load more downline" (breadcrumb drill-down) =======
    // A binary tree's node count doubles per level, so instead of raising the depth
    // cap we let the user drill into any frontier node — that node becomes the new
    // page root and the breadcrumb trail lets them navigate back up.
    let ROOT_STACK = [];

    function currentRootId() {
      return ROOT_STACK.length ? ROOT_STACK[ROOT_STACK.length - 1].id : null;
    }

    function drillInto(node) {
      if (!node || !node.id) return;
      ROOT_STACK.push({ id: node.id, uid: node.uid || '', name: node.name || '' });
      renderBreadcrumb();
      loadTree(CURRENT_DEPTH, currentRootId());
    }

    function drillIntoEl(el) {
      drillInto({ id: el.dataset.id, uid: el.dataset.uid, name: el.dataset.name });
    }

    // index === -1 resets all the way back to the logged-in user's own root.
    function goToBreadcrumb(index) {
      ROOT_STACK = index < 0 ? [] : ROOT_STACK.slice(0, index + 1);
      renderBreadcrumb();
      loadTree(CURRENT_DEPTH, currentRootId());
    }

    function renderBreadcrumb() {
      const bar = document.getElementById('treeBreadcrumb');
      if (!bar) return;
      if (!ROOT_STACK.length) {
        bar.style.display = 'none';
        bar.innerHTML = '';
        return;
      }
      bar.style.display = 'flex';
      const crumbs = ['<a href="#" onclick="goToBreadcrumb(-1);return false;"><i class="ph ph-house"></i> Root</a>']
        .concat(ROOT_STACK.map((c, i) =>
          `<a href="#" onclick="goToBreadcrumb(${i});return false;">${escapeHtml(ucfirstWords(c.name) || c.uid)}</a>`));
      bar.innerHTML = crumbs.join(' <i class="ph ph-caret-right"></i> ');
    }

    // Placeholder card rendered in place of a pruned child that still has real
    // downline beyond the current depth window.
    function moreNodeHtml(node, side) {
      const label = side === 'right' ? 'Right' : 'Left';
      return `<div class="node more-node"
          data-id="${escapeHtml(node.id)}" data-uid="${escapeHtml(node.uid || '')}" data-name="${escapeHtml(node.name || '')}"
          onclick="drillIntoEl(this)">
        <i class="ph ph-arrow-circle-down"></i>
        <span>View more ${label} downline</span>
      </div>`;
    }

    // ======= Tree view modes (Binary / Genealogy / Generation / Level Wise / Direct) =======
    let CURRENT_VIEW = 'binary';
    let CURRENT_DEPTH = 10;

    // Below this width the binary tab renders as a single-focus compact card
    // stack (renderCompactMobile) instead of the full connector-line org chart.
    const treeMobileMq = window.matchMedia('(max-width: 768px)');
    treeMobileMq.addEventListener('change', () => {
      if (CURRENT_VIEW === 'binary') renderCurrentView(CURRENT_DEPTH);
    });

    function setTreeView(view, ev) {
      CURRENT_VIEW = view;
      document.querySelectorAll('.tv-tab').forEach(t => t.classList.remove('active'));
      if (ev && ev.currentTarget) ev.currentTarget.classList.add('active');
      renderCurrentView(CURRENT_DEPTH);
    }

    function renderCurrentView(depth) {
      CURRENT_DEPTH = depth || CURRENT_DEPTH;
      // Invalidate any in-flight progressive render before starting a new one.
      RENDER_TOKEN++;
      const root = document.getElementById('treeRoot');
      const inner = document.getElementById('treeInner');
      const canvas = document.getElementById('treeCanvas');
      const mobileBinary = CURRENT_VIEW === 'binary' && treeMobileMq.matches;
      // Reset zoom/transform for the flat (non-binary) layouts.
      scale = 1; inner.style.transform = 'scale(1)';
      // Binary tree needs the wide canvas; flat views and the compact mobile
      // binary view should both fit the container instead.
      inner.style.minWidth = (CURRENT_VIEW === 'binary' && !mobileBinary) ? '' : 'auto';
      canvas.classList.toggle('tc-active', mobileBinary);

      switch (CURRENT_VIEW) {
        case 'genealogy':  renderGenealogy(); break;
        case 'generation': renderLevels(true); break;
        case 'levelwise':  renderLevels(false); break;
        case 'direct':     renderDirect(); break;
        case 'binary':
        default:
          if (mobileBinary) {
            root.className = 'tree-compact';
            renderCompactMobile();
          } else {
            root.className = 'tree';
            renderBinaryProgressive(CURRENT_DEPTH); // progressive, lag-free load
          }
      }
    }

    // Compact card used by the flat/list views.
    function miniCard(n) {
      if (!n) return '';
      const sc = statusClass(n.status);
      const avatar = n.avatar ? n.avatar : DEFAULT_AVATAR;
      return `<div class="mini-card ${sc}"
        data-id="${escapeHtml(n.id || 0)}" data-uid="${escapeHtml(n.uid || '—')}"
        data-name="${escapeHtml(n.name || 'User')}" data-rank="${escapeHtml(n.rank || '—')}"
        data-status="${escapeHtml(n.status || 'ACTIVE')}" data-join="${escapeHtml(n.join_date || '—')}"
        data-lbv="${escapeHtml(n.left_bv || 0)}" data-rbv="${escapeHtml(n.right_bv || 0)}"
        data-inv="${escapeHtml(n.lock_wallet || 0)}" data-avatar="${escapeHtml(avatar)}"
        onclick="selectNode(this)">
        <img class="mc-av" src="${avatar}" alt="" onerror="this.onerror=null;this.src='${DEFAULT_AVATAR}';">
        <div class="mc-meta"><b>${escapeHtml(ucfirstWords(n.name || 'User'))}</b><small>UID: ${escapeHtml(n.uid || '—')}</small></div>
        <div class="mc-inv"><small>Lock Wallet</small><b>${fmt(n.lock_wallet || 0)} BMAN</b></div>
      </div>`;
    }

    // BFS collect nodes with their level (root = level 0), skipping empties.
    function collectNodes(rootNode) {
      const out = [];
      if (!rootNode || Object.keys(rootNode).length === 0) return out;
      const q = [{ n: rootNode, lvl: 0 }];
      while (q.length) {
        const { n, lvl } = q.shift();
        if (!n) continue;
        const st = (n.status || '').toUpperCase();
        if (SHOW_EMPTY || st !== 'EMPTY') out.push({ n, lvl });
        if (n.left) q.push({ n: n.left, lvl: lvl + 1 });
        if (n.right) q.push({ n: n.right, lvl: lvl + 1 });
      }
      return out;
    }

    // Direct Team View — the root's immediate left & right members.
    function renderDirect() {
      const root = document.getElementById('treeRoot');
      root.className = 'alt-view';
      const kids = [];
      if (TREE.left) kids.push(['Left Leg', TREE.left]);
      if (TREE.right) kids.push(['Right Leg', TREE.right]);
      if (!kids.length) { root.innerHTML = '<div class="alt-empty">No direct team members yet.</div>'; return; }
      root.innerHTML = '<div class="alt-cols">' + kids.map(([pos, n]) =>
        `<div class="alt-col"><div class="alt-h">${pos}</div>${miniCard(n)}</div>`).join('') + '</div>';
    }

    // Level Wise / Generation — group the downline by depth.
    function renderLevels(asGeneration) {
      const nodes = collectNodes(TREE);
      const byLevel = {};
      nodes.forEach(({ n, lvl }) => { if (lvl === 0) return; (byLevel[lvl] = byLevel[lvl] || []).push(n); });
      const root = document.getElementById('treeRoot');
      root.className = 'alt-view';
      const levels = Object.keys(byLevel).map(Number).sort((a, b) => a - b);
      if (!levels.length) { root.innerHTML = '<div class="alt-empty">No team members yet.</div>'; return; }
      root.innerHTML = '<div class="alt-levels">' + levels.map(l => {
        const label = asGeneration ? `Generation ${l}` : `Level ${l}`;
        const sum = byLevel[l].reduce((s, x) => s + (parseFloat(x.lock_wallet) || 0), 0);
        return `<div class="alt-lvl"><div class="alt-h">${label}
          <span class="alt-badge">${byLevel[l].length} members · ${fmt(sum)} BMAN</span></div>
          <div class="alt-grid">${byLevel[l].map(miniCard).join('')}</div></div>`;
      }).join('') + '</div>';
    }

    // Placeholder list item for a pruned child that still has real downline beyond
    // the current depth window (Genealogy Tree's analog of moreNodeHtml()).
    function moreListItem(node, side) {
      const label = side === 'right' ? 'Right' : 'Left';
      return `<li><div class="mini-card more-mini"
          data-id="${escapeHtml(node.id)}" data-uid="${escapeHtml(node.uid || '')}" data-name="${escapeHtml(node.name || '')}"
          onclick="drillIntoEl(this)">
        <i class="ph ph-arrow-circle-down"></i>
        <div class="mc-meta"><b>View more ${label} downline</b></div>
      </div></li>`;
    }

    // Genealogy Tree — full downline as a nested, indented list.
    function buildGenealogy(node) {
      if (!node || Object.keys(node).length === 0) return '';
      const st = (node.status || '').toUpperCase();
      if (!SHOW_EMPTY && st === 'EMPTY') return '';
      let childHtml = [node.left, node.right].filter(Boolean).map(buildGenealogy).filter(Boolean).join('');
      if (!node.left && node.left_has_more) childHtml += moreListItem(node, 'left');
      if (!node.right && node.right_has_more) childHtml += moreListItem(node, 'right');
      return `<li>${miniCard(node)}${childHtml ? `<ul>${childHtml}</ul>` : ''}</li>`;
    }
    function renderGenealogy() {
      const root = document.getElementById('treeRoot');
      root.className = 'alt-view';
      const html = buildGenealogy(TREE);
      root.innerHTML = html ? `<ul class="geneal-list">${html}</ul>` : '<div class="alt-empty">No team members yet.</div>';
    }

    // Total Lock Wallet (active, unmatured staking principal) across a whole
    // subtree (inclusive) — bounded to whatever depth is currently loaded in
    // the browser, same limitation this had when it summed exchange wallet.
    // Memoised via a single post-order pass (precomputeSums) so rendering a deep
    // tree stays O(n) instead of O(n^2) — this is what keeps big trees lag-free.
    let LOCK_SUM = new Map();
    function precomputeSums(node) {
      LOCK_SUM = new Map();
      (function walk(n) {
        if (!n || Object.keys(n).length === 0) return 0;
        const st = (n.status || "").toUpperCase();
        const own = (SHOW_EMPTY || st !== "EMPTY") ? (parseFloat(n.lock_wallet) || 0) : 0;
        const total = own + walk(n.left) + walk(n.right);
        LOCK_SUM.set(n, total);
        return total;
      })(node);
    }
    function sumLockWallet(node) {
      if (!node || Object.keys(node).length === 0) return 0;
      if (LOCK_SUM.has(node)) return LOCK_SUM.get(node);
      const st = (node.status || "").toUpperCase();
      let s = (SHOW_EMPTY || st !== "EMPTY") ? (parseFloat(node.lock_wallet) || 0) : 0;
      s += sumLockWallet(node.left) + sumLockWallet(node.right);
      return s;
    }

    function renderNode(n, position = "") {
      if (!n || Object.keys(n).length === 0) return "";

      // ✅ Hide empty nodes completely
      const st = (n.status || "").toUpperCase();
      if (!SHOW_EMPTY && st === "EMPTY") return "";

      const sc = statusClass(n.status);
      const avatar = n.avatar ? n.avatar : DEFAULT_AVATAR;
      const rank = n.rank ? n.rank : "—";
      const uid = n.uid ? n.uid : "—";
      const name = n.name ? n.name : "User";

      // Left/Right downline investment (Lock Wallet BMAN) totals for this node.
      const leftInvest = sumLockWallet(n.left);
      const rightInvest = sumLockWallet(n.right);

      const email = n.email ? n.email : "";
      const posLabel = position ? position.toLowerCase() : (n.position || "").toLowerCase();
      const statusLabel = (n.status || "ACTIVE").toLowerCase();

      return `
      <a class="node ${sc} node-${position.toLowerCase()}"
         data-id="${escapeHtml(n.id || 0)}"
         data-uid="${escapeHtml(uid)}"
         data-name="${escapeHtml(name)}"
         data-email="${escapeHtml(email)}"
         data-rank="${escapeHtml(rank)}"
         data-status="${escapeHtml((n.status || "ACTIVE"))}"
         data-join="${escapeHtml((n.join_date || "—"))}"
         data-lbv="${escapeHtml((n.left_bv || 0))}"
         data-rbv="${escapeHtml((n.right_bv || 0))}"
         data-inv="${escapeHtml((n.lock_wallet || 0))}"
         data-earning="${escapeHtml((n.earning || 0))}"
         data-staking="${escapeHtml((n.staking || n.own_stake_amount || 0))}"
         data-bonus="${escapeHtml((n.bonus || 0))}"
         data-linv="${escapeHtml(leftInvest)}"
         data-rinv="${escapeHtml(rightInvest)}"
         data-avatar="${escapeHtml(avatar)}"
         data-position="${escapeHtml(position)}"
         data-ceiling="${escapeHtml((n.ceiling_amount || 0))}"
         data-ceiling-remaining="${escapeHtml((n.ceiling_remaining || 0))}"
         data-ceiling-held="${escapeHtml((n.ceiling_wallet_held || 0))}"
         data-own-stake="${escapeHtml((n.own_stake_amount || 0))}"
         data-eligible="${n.matching_eligible ? "1" : "0"}"
         onclick="selectNode(this)">
        <span class="st"></span>

        <div class="node-top">
          <div class="node-user">
            <img class="av" src="${avatar}" alt="" onerror="this.onerror=null;this.src='${DEFAULT_AVATAR}';">
            <div style="min-width:0;text-align:left;">
              <div class="nm">${escapeHtml(ucfirstWords(name))}</div>
              <div class="id">UID: ${escapeHtml(uid)}</div>
            </div>
          </div>
          <div class="rank"><i class="ph ${nodeIcon(rank)}"></i> ${escapeHtml(rank)}</div>
        </div>

        <div class="node-mid-compact">
          <div class="kv-compact">
            <small>Left Leg</small>
            <b>${fmt(leftInvest)}</b>
          </div>
          <div class="kv-compact">
            <small>Right Leg</small>
            <b>${fmt(rightInvest)}</b>
          </div>
        </div>

        <div class="node-btm-compact">
          <div class="pill-sm ${n.matching_eligible ? "pill-eligible" : "pill-ineligible"}">
            <i class="ph ${n.matching_eligible ? "ph-check-circle" : "ph-warning-circle"}"></i>
            ${n.matching_eligible ? "Eligible" : "Needs Stake"}
          </div>
          <div class="pill-sm" style="font-size:9px;" title="Lock Wallet — active, unmatured staking principal">
            <i class="ph ph-lock-key"></i> ${fmt(n.lock_wallet || 0)} BMAN
          </div>
        </div>
      </a>
    `;
    }

    // ======= Node Card Logic — hover popover (desktop) / tap-to-pin (touch) =======
    const tooltip = document.getElementById('treeNodeTooltip');
    const ttBackdrop = document.getElementById('ttBackdrop');

    // No real hover device (phone/tablet) -> the card pins centre-screen on tap
    // instead of following a pointer that doesn't exist. Re-checked live (not
    // cached) so it still gets this right on a hybrid touch+mouse laptop.
    function isTouchMode() {
      return !window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    }

    let tooltipPinned = false;
    let pinnedNodeEl = null;
    const STATUS_ACCENT = { ACTIVE: '#10b981', INACTIVE: '#f59e0b', BLOCKED: '#ef4444', EMPTY: '#cbd5e1' };

    document.addEventListener('mouseover', (e) => {
      if (isTouchMode() || tooltipPinned) return;
      const nodeEl = e.target.closest('.node[data-id]');
      if (!nodeEl || nodeEl.classList.contains('more-node')) return;
      showTooltip(nodeEl, e);
    });

    document.addEventListener('mousemove', (e) => {
      if (tooltipPinned || !tooltip || tooltip.style.display !== 'block') return;
      const nodeEl = e.target.closest('.node[data-id]');
      if (nodeEl && !nodeEl.classList.contains('more-node')) {
        positionTooltip(e);
      } else {
        hideTooltip();
      }
    });

    document.addEventListener('mouseout', (e) => {
      if (tooltipPinned) return;
      const nodeEl = e.target.closest('.node[data-id]');
      if (nodeEl && !e.relatedTarget?.closest('.node[data-id]')) {
        hideTooltip();
      }
    });

    // Touch: tap a card to pin the same details card centre-screen. The two
    // drill-in child cells of the compact mobile focus view (renderCompactMobile)
    // are skipped — tapping those descends the tree instead (drillIntoEl), and
    // popping a card right before that navigation would just leave it stranded
    // on screen showing a node that is no longer on screen.
    document.addEventListener('click', (e) => {
      if (!isTouchMode()) return;
      const nodeEl = e.target.closest('.node[data-id]');
      if (!nodeEl || nodeEl.classList.contains('more-node')) return;
      if (nodeEl.closest('.tc-children')) return;
      if (tooltipPinned && pinnedNodeEl === nodeEl) { closeTooltipPinned(); return; }
      showTooltipPinned(nodeEl);
    });

    if (ttBackdrop) ttBackdrop.addEventListener('click', closeTooltipPinned);

    function fillTooltip(el) {
      if (!tooltip) return;
      const d = el.dataset;
      document.getElementById('ttAvatar').src = d.avatar || DEFAULT_AVATAR;
      document.getElementById('ttName').innerText = ucfirstWords(d.name) || 'User';
      document.getElementById('ttEmail').innerText = d.email || ('UID: ' + (d.uid || '—'));

      const st = (d.status || 'ACTIVE').toUpperCase();
      const stEl = document.getElementById('ttStatus');
      stEl.innerText = st;
      stEl.className = 'tt-badge ' + (st === 'ACTIVE' ? 'tt-active' : 'tt-inactive');
      document.getElementById('ttAccent').style.background = STATUS_ACCENT[st] || STATUS_ACCENT.ACTIVE;

      const rank = d.rank || '—';
      const rankIcon = nodeIcon(rank);
      document.getElementById('ttRank').innerHTML = `<i class="ph ${rankIcon}"></i> ${escapeHtml(rank)}`;
      document.getElementById('ttRankBadge').innerHTML = `<i class="ph ${rankIcon}"></i>`;

      document.getElementById('ttExchange').innerHTML = `${fmt(d.inv || 0)} <span>BMAN</span>`;
      document.getElementById('ttOwnStake').innerHTML = `${fmt(d.ownStake || 0)} <span>BMAN</span>`;
      document.getElementById('ttEarning').innerText = fmt(d.earning || 0);
      document.getElementById('ttStaking').innerText = fmt(d.staking || 0);
      document.getElementById('ttBonus').innerText = fmt(d.bonus || 0);
      document.getElementById('ttLinv').innerHTML = `${fmt(d.linv || 0)} <span>BMAN</span>`;
      document.getElementById('ttRinv').innerHTML = `${fmt(d.rinv || 0)} <span>BMAN</span>`;
      document.getElementById('ttJoin').innerText = d.join || '—';

      const ceiling = parseFloat(d.ceiling || 0);
      const ceilingRemaining = parseFloat(d.ceilingRemaining || 0);
      document.getElementById('ttCeiling').innerHTML = `${fmt(ceilingRemaining)} / ${fmt(ceiling)} <span>BMAN</span>`;
      const usedPct = ceiling > 0 ? Math.min(100, Math.max(0, ((ceiling - ceilingRemaining) / ceiling) * 100)) : 0;
      document.getElementById('ttCeilingBar').style.width = usedPct + '%';

      const held = parseFloat(d.ceilingHeld || 0);
      const heldRow = document.getElementById('ttHeldRow');
      if (held > 0) {
        document.getElementById('ttHeld').innerHTML = `${fmt(held)} <span>BMAN</span>`;
        heldRow.style.display = 'block';
      } else {
        heldRow.style.display = 'none';
      }

      const eligible = d.eligible === '1';
      document.getElementById('ttEligiblePill').classList.toggle('ineligible', !eligible);
      document.getElementById('ttEligibleIcon').className = 'ph ' + (eligible ? 'ph-check-circle' : 'ph-warning-circle');
      document.getElementById('ttEligibleText').innerText = eligible ? 'Matching Eligible' : 'Needs Stake';

      document.getElementById('ttFooter').innerHTML = tooltipPinned
        ? '<i class="ph ph-x"></i> <span>Tap outside or the × to close</span>'
        : '<i class="ph ph-cursor-click"></i> <span>Click card to select &amp; view member profile</span>';
    }

    function showTooltip(el, e) {
      fillTooltip(el);
      if (!tooltip) return;
      tooltip.style.display = 'block';
      requestAnimationFrame(() => tooltip.classList.add('active'));
      positionTooltip(e);
    }

    function showTooltipPinned(el) {
      if (!tooltip) return;
      pinnedNodeEl = el;
      tooltipPinned = true;
      fillTooltip(el);
      tooltip.classList.add('pinned');
      tooltip.style.display = 'block';
      if (ttBackdrop) ttBackdrop.classList.add('active');
      requestAnimationFrame(() => tooltip.classList.add('active'));
    }

    function closeTooltipPinned() {
      if (!tooltipPinned) return;
      tooltipPinned = false;
      pinnedNodeEl = null;
      tooltip.classList.remove('pinned');
      if (ttBackdrop) ttBackdrop.classList.remove('active');
      hideTooltip();
    }

    function positionTooltip(e) {
      if (!tooltip || tooltipPinned) return;
      const pad = 16;
      let x = e.clientX + pad;
      let y = e.clientY + pad;
      const ttW = tooltip.offsetWidth || 300;
      const ttH = tooltip.offsetHeight || 280;

      if (x + ttW > window.innerWidth) x = e.clientX - ttW - pad;
      if (y + ttH > window.innerHeight) y = e.clientY - ttH - pad;

      tooltip.style.left = Math.max(10, x) + 'px';
      tooltip.style.top = Math.max(10, y) + 'px';
    }

    function hideTooltip() {
      if (!tooltip || tooltipPinned) return;
      tooltip.classList.remove('active');
      setTimeout(() => {
        if (!tooltip.classList.contains('active')) tooltip.style.display = 'none';
      }, 150);
    }

    // ✅ Build UL/LI but only add children section if any child exists
    function buildTree(node, level, max, position = "") {
      if (!node || level > max) return "";

      const me = renderNode(node, position);
      if (!me) return "";

      let leftHtml = node.left ? buildTree(node.left, level + 1, max, "LEFT") : "";
      let rightHtml = node.right ? buildTree(node.right, level + 1, max, "RIGHT") : "";

      // Frontier node: no child was fetched at this depth, but one exists further
      // down — offer a "load more" card instead of just stopping.
      if (!leftHtml && node.left_has_more) leftHtml = moreNodeHtml(node, 'left');
      if (!rightHtml && node.right_has_more) rightHtml = moreNodeHtml(node, 'right');

      let children = "";
      if (leftHtml || rightHtml) {
        children = `<ul>
        ${leftHtml ? `<li>${leftHtml}</li>` : ""}
        ${rightHtml ? `<li>${rightHtml}</li>` : ""}
      </ul>`;
      }

      return `<ul><li>${me}${children}</li></ul>`;
    }

    function render(maxDepth = 3) {
      precomputeSums(TREE);
      document.getElementById("treeRoot").innerHTML = buildTree(TREE, 1, maxDepth);
      attachSearchIndex();
      centerTree();

      const firstNode = document.querySelector(".node");
      if (firstNode) selectNode(firstNode);
    }

    // Progressive binary render: draw the tree one level at a time, yielding to
    // the browser between levels so deep (up to 10-level) trees load continuously
    // without freezing the page. A render token cancels a stale run if the user
    // switches view or depth mid-load.
    let RENDER_TOKEN = 0;
    async function renderBinaryProgressive(maxDepth) {
      const myToken = RENDER_TOKEN;
      const root = document.getElementById("treeRoot");
      root.className = "tree";
      precomputeSums(TREE);

      let prevLen = -1;
      for (let d = 1; d <= maxDepth; d++) {
        if (myToken !== RENDER_TOKEN) return; // superseded — stop
        const html = buildTree(TREE, 1, d);
        if (html.length === prevLen) break;  // no deeper members — done early
        prevLen = html.length;
        root.innerHTML = html;
        if (d === 1) centerTree();
        // Yield a frame so the UI stays responsive between levels.
        await new Promise(r => requestAnimationFrame(r));
      }
      if (myToken !== RENDER_TOKEN) return;
      attachSearchIndex();
      const firstNode = document.querySelector(".node");
      if (firstNode) selectNode(firstNode);
    }

    // ======= Compact mobile view (binary tab, ≤768px) =======
    // Small screens can't show a multi-level connector tree without forcing
    // horizontal scroll, so below 768px we show just the current focus node
    // (TREE) plus its two direct children, and let tapping a child descend
    // into it — reusing renderNode()/drillIntoEl()/ROOT_STACK verbatim so the
    // side panel, search index, and PNG export all keep working unmodified.
    function childCellHtml(parent, side) {
      const child = parent ? parent[side] : null;
      const position = side.toUpperCase();
      let html = child ? renderNode(child, position) : "";
      if (!html) {
        const hasMore = parent && (side === 'left' ? parent.left_has_more : parent.right_has_more);
        html = hasMore
          ? moreNodeHtml(parent, side)
          : `<div class="tc-empty-slot"><i class="ph ph-user-plus"></i><span>No ${position === 'LEFT' ? 'Left' : 'Right'} Member Yet</span></div>`;
      }
      return html;
    }

    function renderCompactMobile() {
      const root = document.getElementById('treeRoot');
      precomputeSums(TREE);

      const focusHtml = renderNode(TREE, '') || '<div class="alt-empty">No team member here yet.</div>';

      root.innerHTML = `
        <div class="tc-focus-wrap">
          <div class="tc-tag"><i class="ph ph-crosshair-simple"></i> Currently Viewing</div>
          ${focusHtml}
        </div>
        <div class="tc-children">
          <div class="tc-child-col">
            <div class="tc-child-label"><i class="ph ph-arrow-circle-left"></i> Left Leg</div>
            ${childCellHtml(TREE, 'left')}
          </div>
          <div class="tc-child-col">
            <div class="tc-child-label"><i class="ph ph-arrow-circle-right"></i> Right Leg</div>
            ${childCellHtml(TREE, 'right')}
          </div>
        </div>`;

      // Children descend one level on tap; the focus card keeps selectNode().
      root.querySelectorAll('.tc-children .node[data-position]').forEach(el => {
        el.onclick = function () { drillIntoEl(this); };
      });

      attachSearchIndex();
      const focusEl = root.querySelector('.tc-focus-wrap .node');
      if (focusEl) selectNode(focusEl);
    }

    function zoomBy(delta) {
      scale = Math.min(1.6, Math.max(0.6, +(scale + delta).toFixed(2)));
      document.getElementById("treeInner").style.transform = `scale(${scale})`;
    }

    function refreshTree() {
      toastMini("Refreshing tree…");
      loadTree(CURRENT_DEPTH, currentRootId());
    }

    function toggleCompact() {
      compact = !compact;
      document.querySelectorAll(".node").forEach(n => {
        n.style.width = compact ? "190px" : "220px";
      });
      toastMini(compact ? "Compact mode ON" : "Compact mode OFF");
    }

    // ======= Export the current tree/view to a PNG (with all values) =======
    async function exportTreePNG() {
      if (typeof html2canvas === 'undefined') { toastMini('Export library still loading — try again.'); return; }
      const target = document.getElementById('treeInner');
      if (!target) return;
      const btn = document.getElementById('exportPngBtn');
      const prevScale = scale;
      try {
        if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }
        toastMini('Preparing image…');
        // Capture at natural size (ignore zoom) so the whole tree is rendered.
        target.style.transform = 'scale(1)';
        // Let layout settle before snapshotting.
        await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
        const canvas = await html2canvas(target, {
          backgroundColor: '#ffffff',
          scale: 2,
          useCORS: true,
          allowTaint: false,
          logging: false,
          width: target.scrollWidth,
          height: target.scrollHeight,
          windowWidth: target.scrollWidth,
          windowHeight: target.scrollHeight
        });
        const a = document.createElement('a');
        a.href = canvas.toDataURL('image/png');
        a.download = 'nexman-' + (CURRENT_VIEW || 'binary') + '-tree.png';
        document.body.appendChild(a);
        a.click();
        a.remove();
        toastMini('Tree image downloaded!');
      } catch (e) {
        console.error(e);
        toastMini('Export failed. Please try again.');
      } finally {
        // Restore the zoom transform.
        scale = prevScale;
        target.style.transform = 'scale(' + scale + ')';
        if (btn) { btn.disabled = false; btn.style.opacity = ''; }
      }
    }

    function centerTree() {
      const c = document.getElementById("treeCanvas");
      const inner = document.getElementById("treeInner");
      c.scrollLeft = (inner.scrollWidth - c.clientWidth) / 2;
      c.scrollTop = 0;
    }

    // ======= Select Node -> Side Details (AJAX) =======
    let selectedId = 0;
    // Capitalize first letter of a string
    // Capitalize first letter of each word (PascalCase)
    function ucfirstWords(str) {
      if (!str) return "—";
      return str
        .toLowerCase()
        .split(/[\s-_]+/)               // split by space, dash, underscore
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');                      // join with space for nicer display
    }

    async function selectNode(el) {
      document.querySelectorAll(".node").forEach(n => n.style.outline = "none");
      el.style.outline = "4px solid rgba(110,86,207,0.15)";

      selectedId = parseInt(el.dataset.id || "0", 10);

      // quick fill from dataset
      document.getElementById("sideAvatar").src = el.dataset.avatar || "";
      // document.getElementById("sideName").innerText = el.dataset.name || "—";
      // Example usage
      const name = el.dataset.name || "—";
      document.getElementById("sideName").innerText = ucfirstWords(el.dataset.name) || "—";
      document.getElementById("sideUid").innerText = "UID: " + (el.dataset.uid || "—");
      document.getElementById("sideRank").innerText = "Rank: " + (el.dataset.rank || "—");
      document.getElementById("sideStatus").innerText = (el.dataset.status || "—");
      document.getElementById("sideJoin").innerText = (el.dataset.join || "—");
      document.getElementById("sideLBV").innerText = fmt(el.dataset.linv || 0) + " BMAN";
      document.getElementById("sideRBV").innerText = fmt(el.dataset.rinv || 0) + " BMAN";
      document.getElementById("sideInv").innerText = fmt(el.dataset.inv || 0) + " BMAN";
      setEligibilityUI(el.dataset.ownStake, el.dataset.ceiling, el.dataset.ceilingRemaining, el.dataset.ceilingHeld, el.dataset.eligible === "1");

      // ✅ load full details if valid id
      if (selectedId > 0) {
        try {
          const res = await fetch(MEMBER_URL + selectedId, { credentials: 'same-origin' });
          const json = await res.json();
          if (json?.status === true) {
            const d = json.data;
            document.getElementById("sideName").innerText = ucfirstWords(d.name) || "—";
            document.getElementById("sideUid").innerText = "UID: " + (d.uid || "—");
            document.getElementById("sideStatus").innerText = d.status || "—";
            document.getElementById("sideJoin").innerText = d.join_date || "—";
            // Left/Right investment (BMAN) already set from the node dataset above;
            // member_json has no downline totals, so don't overwrite them here.
            setEligibilityUI(d.own_stake_amount, d.ceiling_amount, d.ceiling_remaining, d.ceiling_wallet_held, !!d.matching_eligible);
          } else if (json?.message) {
            toastMini(json.message);
          }
        } catch (e) {
          console.error(e);
        }
      }
    }

    // Ceiling / staking / matching-eligibility side-panel fields — shared by the
    // instant dataset fill and the member_json refresh above (same values,
    // just possibly a beat fresher from the server).
    function setEligibilityUI(ownStake, ceiling, ceilingRemaining, ceilingHeld, eligible) {
      document.getElementById("sideOwnStake").innerText = fmt(ownStake || 0) + " BMAN";
      document.getElementById("sideCeiling").innerText = fmt(ceilingRemaining || 0) + " / " + fmt(ceiling || 0) + " BMAN";

      const pill = document.getElementById("sideEligiblePill");
      const text = document.getElementById("sideEligibleText");
      const icon = pill.querySelector("i");
      if (eligible) {
        pill.classList.add("pill-eligible"); pill.classList.remove("pill-ineligible");
        icon.className = "ph ph-check-circle";
        text.innerText = "Matching Eligible";
      } else {
        pill.classList.add("pill-ineligible"); pill.classList.remove("pill-eligible");
        icon.className = "ph ph-warning-circle";
        text.innerText = "Needs Stake";
      }

      const heldPill = document.getElementById("sideHeldPill");
      const heldAmt = parseFloat(ceilingHeld || 0);
      if (heldAmt > 0) {
        document.getElementById("sideHeldAmt").innerText = fmt(heldAmt);
        heldPill.style.display = "inline-flex";
      } else {
        heldPill.style.display = "none";
      }
    }

    function goMember() {
      if (!selectedId || selectedId <= 0) { toastMini("Select a valid member"); return; }
      // location.href = "<?= base_url('user/genealogycontroller/viewuserinfo/'); ?>" + selectedId;
      location.href = "#";
    }

    // ======= BV & Pair Rules modal =======
    function openModal() {
      const b = document.getElementById("modalBack");
      if (b) b.style.display = "flex";
    }
    function closeModal() {
      const b = document.getElementById("modalBack");
      if (b) b.style.display = "none";
    }
    // Close when clicking the dim backdrop or pressing Escape.
    document.addEventListener("click", (e) => {
      if (e.target && e.target.id === "modalBack") closeModal();
    });
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") { closeModal(); closeTooltipPinned(); }
    });

    // ======= Search =======
    let index = [];
    function attachSearchIndex() {
      index = [];
      document.querySelectorAll(".node").forEach(n => {
        index.push({
          el: n,
          uid: (n.dataset.uid || "").toLowerCase(),
          name: (n.dataset.name || "").toLowerCase()
        });
      });
    }

    document.getElementById("nodeSearch").addEventListener("input", (e) => {
      const q = (e.target.value || "").trim().toLowerCase();
      if (!q) {
        document.querySelectorAll(".node").forEach(n => n.style.opacity = "1");
        return;
      }
      index.forEach(item => {
        const hit = item.uid.includes(q) || item.name.includes(q);
        item.el.style.opacity = hit ? "1" : "0.25";
      });
    });

    document.getElementById("depthSel").addEventListener("change", (e) => {
      loadTree(parseInt(e.target.value, 10) || 10, currentRootId());
    });

    // Init — default to a 10-level downline.
    loadTree(10);

  </script>
  <script>
    function centerTree() {
      const c = document.getElementById("treeCanvas");
      const inner = document.getElementById("treeInner");

      // horizontal center
      c.scrollLeft = (inner.scrollWidth - c.clientWidth) / 2;

      // small top padding so root node isn't glued to top
      c.scrollTop = 20;
    }

    document.querySelectorAll(".node .node-mid").forEach(m => {
      m.style.gridTemplateColumns = compact ? "1fr" : "1fr 1fr";
    });
  </script>


</body>

</html>
