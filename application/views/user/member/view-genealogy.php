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
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
            <strong><?= number_format($user->left_exchange ?? 0, 2); ?> BMAN</strong>
            <span>Exchange Wallet · Left downline</span>
          </div>
        </div>

        <div class="sum-card">
          <div class="sum-ic sum-warn"><i class="ph ph-arrow-circle-right"></i></div>
          <div class="sum-meta">
            <small>Right Leg Investment</small>
            <strong><?= number_format($user->right_exchange ?? 0, 2); ?> BMAN</strong>
            <span>Exchange Wallet · Right downline</span>
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
              <small>Left Investment</small>
              <b id="sideLBV">0 BMAN</b>
            </div>
            <div class="tile">
              <small>Right Investment</small>
              <b id="sideRBV">0 BMAN</b>
            </div>
            <div class="tile" style="grid-column:1 / -1;">
              <small>Own Investment (Exchange Wallet)</small>
              <b id="sideInv"><?= number_format($tree['exchange'] ?? 0, 2); ?> BMAN</b>
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

    async function loadTree(depth = 10) {
      try {
        const res = await fetch(`${TREE_URL}?depth=${encodeURIComponent(depth)}`, { credentials: 'same-origin' });
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

    // ======= Tree view modes (Binary / Genealogy / Generation / Level Wise / Direct) =======
    let CURRENT_VIEW = 'binary';
    let CURRENT_DEPTH = 10;

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
      // Reset zoom/transform for the flat (non-binary) layouts.
      scale = 1; inner.style.transform = 'scale(1)';
      // Binary tree needs the wide canvas; flat views should fit the container.
      inner.style.minWidth = (CURRENT_VIEW === 'binary') ? '' : 'auto';

      switch (CURRENT_VIEW) {
        case 'genealogy':  renderGenealogy(); break;
        case 'generation': renderLevels(true); break;
        case 'levelwise':  renderLevels(false); break;
        case 'direct':     renderDirect(); break;
        case 'binary':
        default:
          root.className = 'tree';
          renderBinaryProgressive(CURRENT_DEPTH); // progressive, lag-free load
      }
    }

    // Compact card used by the flat/list views.
    function miniCard(n) {
      if (!n) return '';
      const sc = statusClass(n.status);
      const avatar = n.avatar ? n.avatar : ("https://i.pravatar.cc/100?u=" + encodeURIComponent(n.uid || n.id || ''));
      return `<div class="mini-card ${sc}"
        data-id="${escapeHtml(n.id || 0)}" data-uid="${escapeHtml(n.uid || '—')}"
        data-name="${escapeHtml(n.name || 'User')}" data-rank="${escapeHtml(n.rank || '—')}"
        data-status="${escapeHtml(n.status || 'ACTIVE')}" data-join="${escapeHtml(n.join_date || '—')}"
        data-lbv="${escapeHtml(n.left_bv || 0)}" data-rbv="${escapeHtml(n.right_bv || 0)}"
        data-inv="${escapeHtml(n.exchange || 0)}" data-avatar="${escapeHtml(avatar)}"
        onclick="selectNode(this)">
        <img class="mc-av" src="${avatar}" alt="">
        <div class="mc-meta"><b>${escapeHtml(ucfirstWords(n.name || 'User'))}</b><small>UID: ${escapeHtml(n.uid || '—')}</small></div>
        <div class="mc-inv"><small>Investment</small><b>${fmt(n.exchange || 0)} BMAN</b></div>
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
        const sum = byLevel[l].reduce((s, x) => s + (parseFloat(x.exchange) || 0), 0);
        return `<div class="alt-lvl"><div class="alt-h">${label}
          <span class="alt-badge">${byLevel[l].length} members · ${fmt(sum)} BMAN</span></div>
          <div class="alt-grid">${byLevel[l].map(miniCard).join('')}</div></div>`;
      }).join('') + '</div>';
    }

    // Genealogy Tree — full downline as a nested, indented list.
    function buildGenealogy(node) {
      if (!node || Object.keys(node).length === 0) return '';
      const st = (node.status || '').toUpperCase();
      if (!SHOW_EMPTY && st === 'EMPTY') return '';
      const childHtml = [node.left, node.right].filter(Boolean).map(buildGenealogy).filter(Boolean).join('');
      return `<li>${miniCard(node)}${childHtml ? `<ul>${childHtml}</ul>` : ''}</li>`;
    }
    function renderGenealogy() {
      const root = document.getElementById('treeRoot');
      root.className = 'alt-view';
      const html = buildGenealogy(TREE);
      root.innerHTML = html ? `<ul class="geneal-list">${html}</ul>` : '<div class="alt-empty">No team members yet.</div>';
    }

    // Total Exchange Wallet (BMAN) investment across a whole subtree (inclusive).
    // Memoised via a single post-order pass (precomputeSums) so rendering a deep
    // tree stays O(n) instead of O(n^2) — this is what keeps big trees lag-free.
    let EX_SUM = new Map();
    function precomputeSums(node) {
      EX_SUM = new Map();
      (function walk(n) {
        if (!n || Object.keys(n).length === 0) return 0;
        const st = (n.status || "").toUpperCase();
        const own = (SHOW_EMPTY || st !== "EMPTY") ? (parseFloat(n.exchange) || 0) : 0;
        const total = own + walk(n.left) + walk(n.right);
        EX_SUM.set(n, total);
        return total;
      })(node);
    }
    function sumExchange(node) {
      if (!node || Object.keys(node).length === 0) return 0;
      if (EX_SUM.has(node)) return EX_SUM.get(node);
      const st = (node.status || "").toUpperCase();
      let s = (SHOW_EMPTY || st !== "EMPTY") ? (parseFloat(node.exchange) || 0) : 0;
      s += sumExchange(node.left) + sumExchange(node.right);
      return s;
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

      // Left/Right downline investment (Exchange Wallet BMAN) totals for this node.
      const leftInvest = sumExchange(n.left);
      const rightInvest = sumExchange(n.right);

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
         data-inv="${escapeHtml((n.exchange || 0))}"
         data-linv="${escapeHtml(leftInvest)}"
         data-rinv="${escapeHtml(rightInvest)}"
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
            <small>Left Invest</small>
            <b>${fmt(leftInvest)} <span class="tagv">BMAN</span></b>
          </div>
          <div class="kv">
            <small>Right Invest</small>
            <b>${fmt(rightInvest)} <span class="tagv">BMAN</span></b>
          </div>
          <div class="kv" style="grid-column:1 / -1;">
            <small>Own Investment</small>
            <b>${fmt(n.exchange || 0)} <span class="tagv">BMAN</span></b>
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
      if (e.key === "Escape") closeModal();
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
      loadTree(parseInt(e.target.value, 10) || 10);
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