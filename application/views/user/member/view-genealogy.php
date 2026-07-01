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
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <style>
    /* ===================== BINARY TREE ===================== */
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

    .tree-canvas {
      height: 640px;
      overflow: auto;
      position: relative;
      padding: 24px 18px;
    }

    /* big draggable area */
    .tree-inner {
      min-width: 980px;
      transform-origin: 0 0;
    }

    /* Tree lines */
    .tree {
      display: flex;
      justify-content: center;
    }

    .tree ul {
      padding-top: 40px;
      position: relative;
      transition: .2s;
      display: flex;
      justify-content: center;
      gap: 36px;
    }

    .tree li {
      list-style-type: none;
      text-align: center;
      position: relative;
      padding: 40px 10px 0 10px;
    }

    /* connectors */
    .tree li::before,
    .tree li::after {
      content: '';
      position: absolute;
      top: 0;
      right: 50%;
      border-top: 2px solid #ebeaff;
      width: 50%;
      height: 40px;
    }

    .tree li::after {
      right: auto;
      left: 50%;
      border-left: 2px solid #ebeaff;
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
      border-right: 2px solid #ebeaff;
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
      border-left: 2px solid #ebeaff;
      width: 0;
      height: 40px;
    }

    /* Node */
    .node {
      display: inline-flex;
      flex-direction: column;
      gap: 8px;
      width: 220px;
      background: #fff;
      border: 1px solid #f5f5f7;
      border-radius: 22px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.05);
      padding: 12px;
      cursor: pointer;
      transition: .15s;
      position: relative;
    }

    .node:hover {
      transform: translateY(-2px);
      box-shadow: 0 16px 40px rgba(0, 0, 0, 0.06);
    }

    .node-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }

    .node-user {
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 0;
    }

    .av {
      width: 40px;
      height: 40px;
      border-radius: 16px;
      background: #f2f2f7;
      object-fit: cover;
      flex-shrink: 0;
      border: 2px solid #fff;
      box-shadow: 0 10px 16px rgba(0, 0, 0, 0.06);
    }

    .nm {
      font-size: 12px;
      font-weight: 1000;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .id {
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 900;
      margin-top: 2px;
    }

    .rank {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 7px 10px;
      border-radius: 999px;
      font-size: 10px;
      font-weight: 1000;
      border: 1px solid #eeecff;
      background: #efedfb;
      color: var(--primary);
      white-space: nowrap;
    }

    .st {
      position: absolute;
      top: 10px;
      left: 10px;
      width: 10px;
      height: 10px;
      border-radius: 999px;
      background: #22c55e;
      box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15);
    }

    .node.inactive .st {
      background: #f97316;
      box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.15);
    }

    .node.blocked .st {
      background: #ef4444;
      box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.15);
    }

    .node.empty .st {
      background: #d4d4d8;
      box-shadow: 0 0 0 4px rgba(212, 212, 216, 0.20);
    }

    .node-mid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      padding-top: 6px;
    }

    .kv {
      background: #f7f7fb;
      border: 1px solid #f1f1f6;
      border-radius: 16px;
      padding: 10px;
      text-align: left;
    }

    .kv small {
      display: block;
      font-size: 10px;
      color: var(--text-muted);
      font-weight: 900;
    }

    .kv b {
      display: block;
      font-size: 12px;
      margin-top: 3px;
    }

    .kv b .tagv {
      font-size: 10px;
      font-weight: 1000;
      color: var(--primary);
      margin-left: 6px;
    }

    .node-btm {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      padding-top: 2px;
    }

    .pill {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 7px 10px;
      border-radius: 999px;
      border: 1px solid #f1f1f6;
      background: #fff;
      font-size: 10px;
      font-weight: 1000;
      color: #111827;
    }

    .pill i {
      color: var(--primary);
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
        min-width: 860px;
      }
    }

    @media(max-width:600px) {
      .sum-grid {
        grid-template-columns: 1fr;
      }

      .tree-inner {
        min-width: 760px;
      }

      .node {
        width: 200px;
      }
    }

    /* ===================== RESPONSIVE PATCH: BINARY TREE (ADD AT END) ===================== */

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
      .page-actions .btn-main,
      .page-actions .btn-dark {
        flex: 1 1 auto;
        justify-content: center;
      }

      .sum-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .grid-2 {
        grid-template-columns: 1fr;
      }

      .tree-canvas {
        height: 560px;
      }

      .tree-inner {
        min-width: 880px;
        /* still scrollable but less wide */
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

      /* Tree canvas: better mobile height */
      .tree-canvas {
        height: 520px;
        padding: 18px 12px;
      }

      /* Keep the tree scrollable without forcing too wide */
      .tree-inner {
        min-width: 720px;
      }

      /* Node smaller */
      .node {
        width: 190px;
        border-radius: 18px;
        padding: 10px;
      }

      .av {
        width: 36px;
        height: 36px;
        border-radius: 14px;
      }

      .rank {
        padding: 6px 9px;
        font-size: 9px;
      }

      .kv {
        border-radius: 14px;
        padding: 9px;
      }

      .pill {
        padding: 6px 9px;
        font-size: 9px;
      }

      /* Reduce spacing between branches */
      .tree ul {
        gap: 18px;
        padding-top: 34px;
      }

      .tree li {
        padding: 34px 6px 0 6px;
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

    /* Very small phones */
    @media (max-width: 380px) {
      .tree-inner {
        min-width: 680px;
      }

      .node {
        width: 178px;
      }

      .nm {
        max-width: 120px;
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
          <div class="sub">Explore your left/right leg network, positions, BV & activity status.</div>
        </div>

        <div class="page-actions">
          <button class="btn-soft" type="button" onclick="centerTree()"><i class="ph ph-crosshair"></i> Center</button>
          <button class="btn-soft" type="button" onclick="toggleCompact()"><i class="ph ph-layout"></i> Compact</button>
          <button class="btn-soft" type="button" onclick="window.print()"><i class="ph ph-printer"></i> Print</button>
          <button class="btn-main" type="button" onclick="location.href='<?= base_url('user/referrals'); ?>'"><i
              class="ph ph-share-network"></i> Invite</button>
        </div>
      </div>

      <!-- Summary -->
      <div class="sum-grid">
        <div class="sum-card">
          <div class="sum-ic sum-info"><i class="ph ph-arrow-circle-left"></i></div>
          <div class="sum-meta">
            <small>Left Leg BV</small>
            <strong><?= number_format($user->left_bv ?? 0); ?> BV</strong>
            <span>Carry Forward: <?= number_format($user->left_cf ?? 0); ?> BV</span>
          </div>
        </div>

        <div class="sum-card">
          <div class="sum-ic sum-warn"><i class="ph ph-arrow-circle-right"></i></div>
          <div class="sum-meta">
            <small>Right Leg BV</small>
            <strong><?= number_format($user->right_bv ?? 0); ?> BV</strong>
            <span>Carry Forward: <?= number_format($user->right_cf ?? 0); ?> BV</span>
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
          <div class="tree-toolbar">
            <div class="controls" style="width:100%;">
              <input class="search" id="nodeSearch" placeholder="Search UID / name..." />
              <select class="sel" id="depthSel">
                <option value="3">Depth: 3 Levels</option>
                <option value="4">Depth: 4 Levels</option>
                <option value="5">Depth: 5 Levels</option>
              </select>

              <div class="zoom">
                <button class="zbtn" type="button" title="Zoom Out" onclick="zoomBy(-0.1)"><i
                    class="ph ph-minus"></i></button>
                <button class="zbtn" type="button" title="Zoom In" onclick="zoomBy(0.1)"><i
                    class="ph ph-plus"></i></button>
              </div>

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
              src="<?= !empty($tree['avatar']) ? $tree['avatar'] : 'https://i.pravatar.cc/100?u=root'; ?>" alt="">
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
              <small>Left BV</small>
              <b id="sideLBV"><?= number_format($tree['left_bv'] ?? 0); ?></b>
            </div>
            <div class="tile">
              <small>Right BV</small>
              <b id="sideRBV"><?= number_format($tree['right_bv'] ?? 0); ?></b>
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

    <aside class="right-panel">
      <?php $this->load->view('user/layout/v2/user_inner_right_panle'); ?>
    </aside>
  </div>

  <!-- Modal -->
  <div class="modal-backdrop" id="modalBack">
    <div class="modal">
      <div class="modal-h">
        <b>Binary Tree Tips</b>
        <button class="xbtn" onclick="closeModal()"><i class="ph ph-x"></i></button>
      </div>
      <div class="modal-b">
        <div class="note">
          Pairing happens when left and right BV match. Keep both legs active. Carry-forward BV will be used for future
          pairs.
        </div>
        <div class="row2">
          <div class="tile">
            <small>Improve Pairing</small>
            <b>Balance BV</b>
            <div class="note" style="margin-top:6px;">Add BV to weak leg via products/orders.</div>
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
  </div>

  <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>

  <script>
    // ✅ AJAX endpoints
    const TREE_URL = "<?= base_url('user/usersettings/genealogycontroller/tree_json'); ?>";
    const MEMBER_URL = "<?= base_url('user/usersettings/genealogycontroller/member_json/'); ?>";

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

    async function loadTree(depth = 3) {
      try {
        const res = await fetch(`${TREE_URL}?depth=${encodeURIComponent(depth)}`, { credentials: 'same-origin' });
        const json = await res.json();

        if (!json || json.status !== true) {
          toastMini(json?.message || "Failed to load tree");
          TREE = {};
          return;
        }

        TREE = json.data || {};
        render(depth);

      } catch (e) {
        console.error(e);
        toastMini("Tree load error");
      }
    }

    function renderNode(n) {
      if (!n || Object.keys(n).length === 0) return "";

      // ✅ Hide empty nodes completely
      const st = (n.status || "").toUpperCase();
      if (!SHOW_EMPTY && st === "EMPTY") return "";

      const sc = statusClass(n.status);
      const avatar = n.avatar ? n.avatar : "https://i.pravatar.cc/100?u=" + encodeURIComponent(n.uid || n.id || Math.random());
      const rank = n.rank ? n.rank : "—";
      const uid = n.uid ? n.uid : "—";
      const name = n.name ? n.name : "User";

      return `
      <a class="node ${sc}"
         data-id="${escapeHtml(n.id || 0)}"
         data-uid="${escapeHtml(uid)}"
         data-name="${escapeHtml(name)}"
         data-rank="${escapeHtml(rank)}"
         data-status="${escapeHtml((n.status || "ACTIVE"))}"
         data-join="${escapeHtml((n.join_date || "—"))}"
         data-lbv="${escapeHtml((n.left_bv || 0))}"
         data-rbv="${escapeHtml((n.right_bv || 0))}"
         data-avatar="${escapeHtml(avatar)}"
         onclick="selectNode(this)">
        <span class="st"></span>

        <div class="node-top">
          <div class="node-user">
            <img class="av" src="${avatar}" alt="">
            <div style="min-width:0;text-align:left;">
              <div class="nm">${escapeHtml(ucfirstWords(name))}</div>
              <div class="id">UID: ${escapeHtml(uid)}</div>
            </div>
          </div>
          <div class="rank"><i class="ph ${nodeIcon(rank)}"></i> ${escapeHtml(rank)}</div>
        </div>

        <div class="node-mid">
          <div class="kv">
            <small>Left BV</small>
            <b>${fmt(n.left_bv)} <span class="tagv">BV</span></b>
          </div>
          <div class="kv">
            <small>Right BV</small>
            <b>${fmt(n.right_bv)} <span class="tagv">BV</span></b>
          </div>
        </div>

        <div class="node-btm">
          <div class="pill"><i class="ph ph-calendar"></i> ${escapeHtml(n.join_date || "—")}</div>
          <div class="pill"><i class="ph ph-activity"></i> ${escapeHtml((n.status || "ACTIVE"))}</div>
        </div>
      </a>
    `;
    }

    // ✅ Build UL/LI but only add children section if any child exists
    function buildTree(node, level, max) {
      if (!node || level > max) return "";

      const me = renderNode(node);
      if (!me) return "";

      const leftHtml = node.left ? buildTree(node.left, level + 1, max) : "";
      const rightHtml = node.right ? buildTree(node.right, level + 1, max) : "";

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
      document.getElementById("treeRoot").innerHTML = buildTree(TREE, 1, maxDepth);
      attachSearchIndex();
      centerTree();

      const firstNode = document.querySelector(".node");
      if (firstNode) selectNode(firstNode);
    }

    function zoomBy(delta) {
      scale = Math.min(1.6, Math.max(0.6, +(scale + delta).toFixed(2)));
      document.getElementById("treeInner").style.transform = `scale(${scale})`;
    }

    function toggleCompact() {
      compact = !compact;
      document.querySelectorAll(".node").forEach(n => {
        n.style.width = compact ? "190px" : "220px";
      });
      toastMini(compact ? "Compact mode ON" : "Compact mode OFF");
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
      document.getElementById("sideLBV").innerText = fmt(el.dataset.lbv || 0);
      document.getElementById("sideRBV").innerText = fmt(el.dataset.rbv || 0);

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
            document.getElementById("sideLBV").innerText = fmt(d.left_bv || 0);
            document.getElementById("sideRBV").innerText = fmt(d.right_bv || 0);
          }
        } catch (e) {
          console.error(e);
        }
      }
    }

    function goMember() {
      if (!selectedId || selectedId <= 0) { toastMini("Select a valid member"); return; }
      // location.href = "<?= base_url('user/genealogycontroller/viewuserinfo/'); ?>" + selectedId;
      location.href = "#";
    }

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
      loadTree(parseInt(e.target.value, 10) || 3);
    });

    // Init
    loadTree(3);

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