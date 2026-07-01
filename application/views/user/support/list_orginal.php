<?php
// -------------------- SAMPLE DATA (replace with DB values) --------------------
$user = $user ?? (object) [
  'name' => 'Lucas',
  'uid' => 'NEXMAN123',
];

$supportStats = $supportStats ?? (object) [
  'open' => 2,
  'pending' => 1,
  'closed' => 8,
  'avg_response' => '2h 15m',
];

$tickets = $tickets ?? [
  (object) [
    'id' => 1012,
    'code' => 'TCK-1012',
    'subject' => 'Withdrawal stuck in processing',
    'category' => 'Payout',
    'priority' => 'High',
    'status' => 'PENDING',
    'updated_at' => '2026-01-30 10:10 AM',
    'last_msg' => 'We are verifying your bank details.'
  ],
  (object) [
    'id' => 1007,
    'code' => 'TCK-1007',
    'subject' => 'KYC rejected (blurred document)',
    'category' => 'KYC',
    'priority' => 'Medium',
    'status' => 'REJECTED',
    'updated_at' => '2026-01-28 02:45 PM',
    'last_msg' => 'Please upload clear PAN & Aadhaar images.'
  ],
  (object) [
    'id' => 996,
    'code' => 'TCK-0996',
    'subject' => 'Referral link not opening',
    'category' => 'Referral',
    'priority' => 'Low',
    'status' => 'CLOSED',
    'updated_at' => '2026-01-22 09:05 AM',
    'last_msg' => 'Resolved. Your link is active now.'
  ],
];

function sBadge($st)
{
  $st = strtoupper($st ?? '');
  if ($st === 'OPEN')
    return 'b-blue';
  if ($st === 'PENDING')
    return 'b-warn';
  if ($st === 'REJECTED')
    return 'b-bad';
  if ($st === 'CLOSED')
    return 'b-ok';
  return 'b-soft';
}
function pBadge($p)
{
  $p = strtoupper($p ?? '');
  if ($p === 'HIGH')
    return 'p-high';
  if ($p === 'MEDIUM')
    return 'p-med';
  return 'p-low';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php $this->load->view('user/layout/v2/user_style'); ?>
  <script src="https://unpkg.com/@phosphor-icons/web"></script>

  <style>
    /* ===================== SUPPORT PAGE ===================== */
    .titlebar {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      gap: 12px;
      margin: 8px 0 16px;
    }

    .titlebar h2 {
      margin: 0;
      font-size: 18px;
      font-weight: 1100;
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
      font-weight: 900;
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
      font-weight: 1100;
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
      font-weight: 1100;
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
      font-weight: 1100;
      cursor: pointer;
      font-size: 12px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .grid-2 {
      display: grid;
      grid-template-columns: 1.15fr .85fr;
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
      font-size: 13px;
      font-weight: 1100;
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
      font-weight: 1100;
    }

    /* Quick action cards */
    .quick {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
    }

    .qa {
      border: 1px solid #f1f1f6;
      background: #f7f7fb;
      border-radius: 20px;
      padding: 12px;
      display: flex;
      gap: 10px;
      align-items: flex-start;
      cursor: pointer;
      transition: .15s;
    }

    .qa:hover {
      transform: translateY(-2px);
      background: #fff;
      border-color: #e9e7ff;
      box-shadow: 0 14px 30px rgba(0, 0, 0, 0.06);
    }

    .qa .ic {
      width: 42px;
      height: 42px;
      border-radius: 16px;
      display: grid;
      place-items: center;
      background: #efedfb;
      color: var(--primary);
      border: 1px solid #eeecff;
    }

    .qa b {
      display: block;
      font-size: 12px;
      font-weight: 1200;
      color: #111;
    }

    .qa span {
      display: block;
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 900;
      margin-top: 3px;
      line-height: 1.35;
    }

    /* Filters */
    .filters {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
    }

    .inp,
    .sel {
      border: 1px solid #f1f1f6;
      background: #f7f7fb;
      border-radius: 14px;
      padding: 11px 12px;
      outline: none;
      font-size: 12px;
      font-weight: 900;
      color: #111;
    }

    .inp {
      flex: 1;
      min-width: 220px;
    }

    .inp:focus,
    .sel:focus {
      background: #fff;
      border-color: #dcd7ff;
      box-shadow: 0 0 0 4px rgba(110, 86, 207, 0.10);
    }

    /* Ticket list */
    .list {
      margin-top: 12px;
      display: grid;
      gap: 10px;
    }

    .row {
      border: 1px solid #f5f5f7;
      background: #fff;
      border-radius: 20px;
      padding: 12px;
      display: grid;
      grid-template-columns: 1.4fr .6fr .6fr .7fr .3fr;
      gap: 10px;
      align-items: center;
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.03);
    }

    .t-title b {
      display: block;
      font-size: 12px;
      font-weight: 1200;
      color: #111;
    }

    .t-title small {
      display: block;
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 900;
      margin-top: 3px;
      line-height: 1.35;
    }

    .meta {
      font-size: 11px;
      font-weight: 1100;
      color: #111;
    }

    .meta span {
      display: block;
      font-size: 10px;
      color: var(--text-muted);
      font-weight: 900;
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

    .b-blue {
      border-color: #dbeafe;
      background: #eff6ff;
      color: #2563eb;
    }

    .b-soft {
      border-color: #eeecff;
      background: #efedfb;
      color: var(--primary);
    }

    .p-high {
      border-color: #fee2e2;
      background: #fef2f2;
      color: #b91c1c;
    }

    .p-med {
      border-color: #ffedd5;
      background: #fff7ed;
      color: #c2410c;
    }

    .p-low {
      border-color: #dcfce7;
      background: #ecfdf3;
      color: #0f9d58;
    }

    .btn-mini {
      border: 1px solid #f1f1f6;
      background: #fff;
      border-radius: 14px;
      padding: 10px 12px;
      font-size: 12px;
      font-weight: 1100;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      justify-content: center;
    }

    .btn-mini:hover {
      transform: translateY(-1px);
      transition: .15s;
    }

    /* Right panel cards */
    .side-grid {
      display: grid;
      gap: 14px;
    }

    .pill {
      border: 1px solid #f1f1f6;
      background: #f7f7fb;
      border-radius: 18px;
      padding: 12px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      font-size: 12px;
      font-weight: 1200;
      color: #111;
    }

    .pill span {
      display: block;
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 900;
      margin-top: 2px;
    }

    .sidebtn {
      width: 100%;
      border: none;
      border-radius: 16px;
      padding: 12px 14px;
      font-weight: 1100;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .sidebtn.primary {
      background: var(--primary);
      color: #fff;
    }

    .sidebtn.soft {
      background: #efedfb;
      color: var(--primary);
    }

    .sidebtn.dark {
      background: #111;
      color: #fff;
    }

    .faq {
      display: grid;
      gap: 10px;
      margin-top: 10px;
    }

    .faq-item {
      border: 1px solid #f1f1f6;
      background: #fff;
      border-radius: 18px;
      padding: 12px;
      cursor: pointer;
    }

    .faq-item b {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      font-size: 12px;
      font-weight: 1200;
    }

    .faq-item p {
      margin: 8px 0 0;
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 900;
      line-height: 1.45;
      display: none;
    }

    .faq-item.open p {
      display: block;
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
      width: min(720px, 100%);
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
      font-weight: 1200;
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
      padding: 14px 16px;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    .field {
      display: grid;
      gap: 6px;
    }

    .field label {
      font-size: 11px;
      font-weight: 1100;
      color: #111;
    }

    .ta {
      min-height: 110px;
      resize: vertical;
    }

    .full {
      grid-column: 1/-1;
    }

    .hint {
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 900;
      line-height: 1.4;
      margin-top: 6px;
    }

    @media(max-width:1200px) {
      .grid-2 {
        grid-template-columns: 1fr;
      }

      .quick {
        grid-template-columns: 1fr;
      }

      .row {
        grid-template-columns: 1fr;
      }
    }

    :root {
      --primary: #6E56CF;
      --primary-gradient: linear-gradient(135deg, #6E56CF 0%, #4D39A3 100%);
      --bg: #f8f9fd;
      --card: #ffffff;
      --text: #1a1a20;
      --muted: #8a8aa3;
      --line: #f0f0f7;

      --success: #10b981;
      --warning: #f59e0b;
      --danger: #ef4444;
      --info: #3b82f6;

      --shadow-md: 0 10px 25px -5px rgba(110, 86, 207, 0.08);
      --radius: 20px;
    }

    .support-wrap {
      padding: 10px 0 50px;
    }

    /* --- Page Header Banner --- */
    .support-banner {
      background: var(--primary-gradient);
      border-radius: var(--radius);
      padding: 30px;
      color: white;
      margin-bottom: 25px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: relative;
      overflow: hidden;
    }

    .banner-content h2 {
      font-size: 26px;
      margin: 0;
      font-weight: 800;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .banner-content p {
      opacity: 0.85;
      margin: 8px 0 0;
      font-size: 14px;
    }

    /* --- Insights KPI Grid --- */
    .insight-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 15px;
      margin-bottom: 25px;
    }

    .stat-pill {
      background: white;
      padding: 15px 20px;
      border-radius: 18px;
      border: 1px solid var(--line);
      display: flex;
      align-items: center;
      gap: 15px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    }

    .stat-pill .ic {
      width: 45px;
      height: 45px;
      border-radius: 12px;
      display: grid;
      place-items: center;
      font-size: 20px;
    }

    .stat-pill b {
      display: block;
      font-size: 18px;
      color: var(--text);
    }

    .stat-pill span {
      font-size: 11px;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
    }

    /* --- Quick Action Tiles --- */
    .quick-help-row {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 15px;
      margin-bottom: 30px;
    }

    .qa-card {
      background: white;
      padding: 20px;
      border-radius: 22px;
      border: 1px solid var(--line);
      cursor: pointer;
      transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      text-align: center;
    }

    .qa-card:hover {
      transform: translateY(-5px);
      border-color: var(--primary);
      box-shadow: var(--shadow-md);
    }

    .qa-card .ic-box {
      width: 60px;
      height: 60px;
      border-radius: 20px;
      margin: 0 auto 15px;
      display: grid;
      place-items: center;
      font-size: 26px;
      background: #f5f3ff;
      color: var(--primary);
    }

    .qa-card h4 {
      margin: 0;
      font-size: 15px;
      font-weight: 800;
    }

    .qa-card p {
      font-size: 12px;
      color: var(--muted);
      margin-top: 5px;
      line-height: 1.4;
    }

    /* --- Enhanced Ticket Rows --- */
    .ticket-card {
      background: white;
      border: 1px solid var(--line);
      border-radius: 20px;
      padding: 20px;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      transition: 0.2s;
    }

    .ticket-card:hover {
      border-color: #dcd7ff;
      background: #fafbff;
    }

    .t-main-info {
      display: flex;
      gap: 15px;
      align-items: flex-start;
    }

    .t-cat-ic {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: #f1f3f9;
      color: #475569;
      display: grid;
      place-items: center;
      font-size: 20px;
    }

    .t-details h5 {
      margin: 0;
      font-size: 15px;
      font-weight: 700;
      color: var(--text);
    }

    .t-details small {
      color: var(--muted);
      font-size: 12px;
      margin-top: 3px;
      display: block;
    }

    .t-msg {
      font-size: 13px;
      color: #555;
      margin-top: 8px;
      font-style: italic;
      opacity: 0.8;
    }

    .t-meta-group {
      display: flex;
      gap: 20px;
      align-items: center;
    }

    .t-badge {
      padding: 6px 12px;
      border-radius: 10px;
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      border: 1px solid transparent;
    }

    /* --- Right Sidebar Styling --- */
    .side-card {
      background: white;
      border-radius: 22px;
      padding: 20px;
      border: 1px solid var(--line);
      margin-bottom: 15px;
    }

    .side-card h3 {
      font-size: 16px;
      font-weight: 800;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .faq-pill {
      padding: 12px;
      border-radius: 14px;
      background: #f8f9fc;
      margin-bottom: 10px;
      cursor: pointer;
      border: 1px solid #f1f1f6;
    }

    .faq-pill:hover {
      background: #fff;
      border-color: var(--primary);
    }

    .faq-pill b {
      font-size: 13px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    @media (max-width: 1100px) {
      .insight-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .quick-help-row {
        grid-template-columns: 1fr;
      }

      .ticket-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
    }
  </style>
</head>

<body>
  <div class="app-container">
    <?php $this->load->view('user/layout/v2/user_sidebar'); ?>

    <main class="main-content">
      <?php $this->load->view('user/layout/v2/user_header'); ?>


      <div class="support-wrap">

        <!-- GRAPHICAL BANNER -->
        <div class="support-banner">
          <div class="banner-content">
            <h2><i class="ph-fill ph-headset"></i> Support Center</h2>
            <p>How can we help you today? Create a ticket or explore our help guide.</p>
          </div>
          <div class="banner-actions actions">
            <button class="btn-main" style="background:rgba(255,255,255,0.2); border:none;" onclick="openWhatsApp()">
              <i class="ph ph-whatsapp-logo"></i> Priority Support
            </button>
          </div>
        </div>

        <!-- INSIGHTS KPI -->
        <div class="insight-grid">
          <div class="stat-pill">
            <div class="ic" style="background:#eff6ff; color:var(--info);"><i class="ph ph-envelope-open"></i></div>
            <div><span>Open</span><b><?= $supportStats->open; ?></b></div>
          </div>
          <div class="stat-pill">
            <div class="ic" style="background:#fff7ed; color:var(--warning);"><i class="ph ph-hourglass"></i></div>
            <div><span>Pending</span><b><?= $supportStats->pending; ?></b></div>
          </div>
          <div class="stat-pill">
            <div class="ic" style="background:#ecfdf5; color:var(--success);"><i class="ph ph-check-circle"></i></div>
            <div><span>Resolved</span><b><?= $supportStats->closed; ?></b></div>
          </div>
          <div class="stat-pill">
            <div class="ic" style="background:#f5f3ff; color:var(--primary);"><i class="ph ph-clock"></i></div>
            <div><span>Response</span><b><?= $supportStats->avg_response; ?></b></div>
          </div>
        </div>

        <div class="grid-2">
          <!-- LEFT PANEL -->
          <section>
            <div class="quick-help-row">
              <div class="qa-card" onclick="openTicket()">
                <div class="ic-box"><i class="ph ph-plus-circle"></i></div>
                <h4>New Ticket</h4>
                <p>Issues with payout or account?</p>
              </div>
              <div class="qa-card" onclick="goToKyc()">
                <div class="ic-box" style="background:#fff7ed; color:var(--warning);"><i class="ph ph-fingerprint"></i>
                </div>
                <h4>KYC Help</h4>
                <p>Rejected docs or bank errors?</p>
              </div>
              <div class="qa-card" onclick="goToWithdraw()">
                <div class="ic-box" style="background:#f0fdf4; color:var(--success);"><i class="ph ph-hand-coins"></i>
                </div>
                <h4>Withdrawal</h4>
                <p>Minimums and payout cycles.</p>
              </div>
            </div>

            <div class="card-h" style="margin-bottom:15px;">
              <h3 style="font-size:18px; font-weight:800;">Recent Conversations</h3>
              <div class="filters">
                <input class="inp" id="q" placeholder="Search ticket..." style="min-width:180px;" />
                <button class="btn-soft" onclick="resetFilters()"><i class="ph ph-arrow-counter-clockwise"></i></button>
              </div>
            </div>

            <div id="ticketList">
              <?php if (!empty($tickets)):
                foreach ($tickets as $t): ?>
                  <div class="ticket-card row" data-subject="<?= strtolower($t->subject); ?>"
                    data-status="<?= strtoupper($t->status); ?>">

                    <div class="t-main-info">
                      <div class="t-cat-ic"><i class="ph ph-tag-chevron"></i></div>
                      <div class="t-details">
                        <h5><?= $t->subject; ?></h5>
                        <small><?= $t->code; ?> • <?= $t->category; ?> • Updated <?= $t->updated_at; ?></small>
                        <p class="t-msg">"<?= substr($t->last_msg, 0, 60); ?>..."</p>
                      </div>
                    </div>

                    <div class="t-meta-group">
                      <div class="t-badge <?= sBadge($t->status); ?>"><?= $t->status; ?></div>
                      <button class="btn-mini" onclick="openTicketView('<?= $t->id; ?>')"
                        style="width:40px; height:40px; border-radius:50%;">
                        <i class="ph ph-caret-right" style="font-size:18px;"></i>
                      </button>
                    </div>
                  </div>
                <?php endforeach; else: ?>
                <div class="qa-card" style="padding:40px; border-style:dashed;">
                  <i class="ph ph-chat-circle-dots" style="font-size:40px; color:#ccc;"></i>
                  <p>No active tickets found.</p>
                </div>
              <?php endif; ?>
            </div>
          </section>

          <!-- RIGHT PANEL -->
          <aside>
            <div class="side-card">
              <h3><i class="ph ph-lightning" style="color:var(--warning)"></i> Quick Actions</h3>
              <button class="sidebtn primary" onclick="openTicket()"><i class="ph ph-plus"></i> Create New
                Ticket</button>
              <button class="sidebtn soft" style="margin-top:10px;" onclick="goToWithdraw()"><i class="ph ph-bank"></i>
                Payout Status</button>
            </div>

            <div class="side-card">
              <h3><i class="ph ph-question" style="color:var(--info)"></i> Common FAQs</h3>
              <div class="faq">
                <div class="faq-pill" onclick="toggleFaq(this)">
                  <b>Withdrawal pending? <i class="ph ph-caret-down"></i></b>
                  <p>Check if your bank details match your KYC documents. Payouts usually take 24-48 hours.</p>
                </div>
                <div class="faq-pill" onclick="toggleFaq(this)">
                  <b>KYC rejected? <i class="ph ph-caret-down"></i></b>
                  <p>Common reasons: Blurred photos, name mismatch, or expired ID cards.</p>
                </div>
              </div>
            </div>
          </aside>
        </div>
      </div>

    </main>

    <aside class="right-panel">
      <?php $this->load->view('user/layout/v2/user_inner_right_panle'); ?>
    </aside>
  </div>

  <!-- ===================== CREATE TICKET MODAL ===================== -->
  <div class="modal-backdrop" id="ticketModal">
    <div class="modal">
      <div class="modal-h">
        <b>Create Support Ticket</b>
        <button class="xbtn" onclick="closeTicket()"><i class="ph ph-x"></i></button>
      </div>

      <div class="modal-b">
        <form method="post" action="<?= base_url('user/support/create'); ?>" enctype="multipart/form-data">
          <div class="form-grid">
            <div class="field">
              <label>Category</label>
              <select class="sel" name="category" required>
                <option value="">Select</option>
                <option value="KYC">KYC</option>
                <option value="Bank">Bank</option>
                <option value="Payout">Payout</option>
                <option value="Wallet">Wallet</option>
                <option value="Referral">Referral</option>
                <option value="Binary">Binary / BV</option>
                <option value="Technical">Technical Issue</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="field">
              <label>Priority</label>
              <select class="sel" name="priority" required>
                <option value="LOW">Low</option>
                <option value="MEDIUM" selected>Medium</option>
                <option value="HIGH">High</option>
              </select>
            </div>

            <div class="field full">
              <label>Subject</label>
              <input class="inp" name="subject" placeholder="Example: Withdrawal pending after bank update" required>
            </div>

            <div class="field full">
              <label>Describe your issue</label>
              <textarea class="ta inp" name="message"
                placeholder="Add details like UID, date/time, transaction ref, screenshots info..." required></textarea>
              <div class="hint">Tip: More details = faster resolution.</div>
            </div>

            <div class="field full">
              <label>Attachment (optional)</label>
              <input class="inp" type="file" name="attachment" accept="image/*,.pdf">
              <div class="hint">Upload screenshot or PDF (max size based on server settings).</div>
            </div>

            <div class="field full">
              <button class="btn-main" type="submit"><i class="ph ph-check"></i> Submit Ticket</button>
              <button class="btn-soft" type="button" onclick="closeTicket()" style="margin-left:8px;"><i
                  class="ph ph-x-circle"></i> Cancel</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>
  <script>
    // ===== Filters =====
    const q = document.getElementById('q');
    const statusSel = document.getElementById('status');
    const prioritySel = document.getElementById('priority');
    const rows = () => Array.from(document.querySelectorAll('#ticketList .row'));

    function applyFilters() {
      const s = (q.value || "").trim().toLowerCase();
      const st = (statusSel.value || "").trim();
      const pr = (prioritySel.value || "").trim();

      rows().forEach(r => {
        const hay = (r.dataset.subject + " " + r.dataset.category + " " + r.dataset.code).toLowerCase();
        const okQ = !s || hay.includes(s);
        const okS = !st || r.dataset.status === st;
        const okP = !pr || r.dataset.priority === pr;
        r.style.display = (okQ && okS && okP) ? "" : "none";
      });
    }
    q.addEventListener('input', applyFilters);
    statusSel.addEventListener('change', applyFilters);
    prioritySel.addEventListener('change', applyFilters);

    function resetFilters() {
      q.value = ""; statusSel.value = ""; prioritySel.value = "";
      applyFilters();
    }

    // ===== Ticket Modal =====
    function openTicket() { document.getElementById('ticketModal').style.display = 'flex'; }
    function closeTicket() { document.getElementById('ticketModal').style.display = 'none'; }

    // ===== Ticket view (route example) =====
    function openTicketView(id) {
      // Change route to your actual ticket thread page
      location.href = "<?= base_url('user/support/view/'); ?>" + encodeURIComponent(id);
    }

    // ===== FAQ =====
    function toggleFaq(el) { el.classList.toggle('open'); }

    // ===== Quick actions =====
    function openWhatsApp() {
      // Replace with your support number
      const phone = "919999999999";
      const msg = encodeURIComponent("Hi Support, I need help. UID: <?= htmlspecialchars($user->uid); ?>");
      window.open("https://wa.me/" + phone + "?text=" + msg, "_blank");
    }
    function goToKyc() { location.href = "<?= base_url('user/profile_settings'); ?>#kyc"; }
    function goToWithdraw() { location.href = "<?= base_url('user/withdraw'); ?>"; }
  </script>
</body>

</html>