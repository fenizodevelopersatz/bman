<?php
// NOTE: This view expects these from controller:
// $open_ticket_count, $pending_ticket_count, $closed_ticket_count, $all_ticket_count, $new_ticket_count
// $faqs (optional) - array of faq objects {question, answer}

function sBadge($stNumOrText)
{
    // db status: 0 pending, 1 open, 2 closed
    if (is_numeric($stNumOrText)) {
        if ((string) $stNumOrText === '1')
            return 'b-blue';
        if ((string) $stNumOrText === '2')
            return 'b-ok';
        return 'b-warn';
    }
    $st = strtoupper($stNumOrText ?? '');
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

function sText($stNumOrText)
{
    if (is_numeric($stNumOrText)) {
        if ((string) $stNumOrText === '1')
            return 'OPEN';
        if ((string) $stNumOrText === '2')
            return 'CLOSED';
        return 'PENDING';
    }
    return strtoupper($stNumOrText ?? 'PENDING');
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

        .grid-2 {
            display: grid;
            grid-template-columns: 1.15fr .85fr;
            gap: 14px;
        }

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

        .faq-pill p {
            margin: 8px 0 0;
            font-size: 12px;
            color: var(--muted);
            display: none;
            line-height: 1.5;
            font-weight: 800;
        }

        .faq-pill.open p {
            display: block;
        }

        /* chips */
        .filter-chip-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 0 0 12px;
        }

        .filter-chip {
            border: 1px solid #f1f1f6;
            background: #fff;
            border-radius: 999px;
            padding: 9px 12px;
            font-size: 12px;
            font-weight: 1100;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .filter-chip.active {
            background: #efedfb;
            border-color: #eeecff;
            color: var(--primary);
        }

        .ticket-loader {
            padding: 22px;
            border: 1px dashed #e6e6ef;
            border-radius: 22px;
            background: #fff;
            text-align: center;
            color: #8a8aa3;
            font-weight: 1000;
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

        /* ===================== FIX TICKET ROW WRAP ===================== */

        /* Allow ticket card to wrap */
        .ticket-card {
            flex-wrap: wrap;
            gap: 15px;
        }

        /* Make right side stack properly */
        .t-meta-group {
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Ensure main info does not overflow */
        .t-main-info {
            flex: 1;
            min-width: 0;
        }

        /* Prevent long titles breaking layout */
        .t-details h5 {
            word-break: break-word;
        }

        /* Mobile Fix */
        @media (max-width: 992px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }

            .insight-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .quick-help-row {
                grid-template-columns: 1fr;
            }

            .ticket-card {
                flex-direction: column;
                align-items: flex-start;
            }

            .t-meta-group {
                width: 100%;
                justify-content: space-between;
            }
        }

        @media (max-width: 576px) {
            .insight-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1100px) {
            .insight-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            /* ================= RESPONSIVE FIX ================= */

            /* Make everything use safe box sizing */
            *,
            *::before,
            *::after {
                box-sizing: border-box;
            }

            /* Prevent horizontal scroll */
            body {
                overflow-x: hidden;
            }

            /* Banner responsive */
            .support-banner {
                flex-wrap: wrap;
                gap: 20px;
            }

            .banner-content h2 {
                font-size: 20px;
            }

            .banner-content p {
                font-size: 13px;
            }

            /* Insight stats responsive */
            .insight-grid {
                grid-template-columns: repeat(4, 1fr);
            }

            @media (max-width: 1200px) {
                .insight-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media (max-width: 600px) {
                .insight-grid {
                    grid-template-columns: 1fr;
                }
            }

            /* Quick help responsive */
            .quick-help-row {
                grid-template-columns: repeat(3, 1fr);
            }

            @media (max-width: 992px) {
                .quick-help-row {
                    grid-template-columns: 1fr;
                }
            }

            /* Main grid responsive */
            .grid-2 {
                grid-template-columns: 1.15fr .85fr;
            }

            @media (max-width: 992px) {
                .grid-2 {
                    grid-template-columns: 1fr;
                }
            }

            /* Ticket card responsive */
            .ticket-card {
                flex-wrap: wrap;
                gap: 15px;
            }

            @media (max-width: 768px) {
                .ticket-card {
                    flex-direction: column;
                    align-items: flex-start;
                }

                .t-meta-group {
                    width: 100%;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 10px;
                }
            }

            /* Titlebar responsive */
            @media (max-width: 768px) {
                .titlebar {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 10px;
                }

                .actions {
                    width: 100%;
                    justify-content: flex-start;
                }
            }

            /* Filter row responsive */
            @media (max-width: 768px) {
                .filters {
                    flex-direction: column;
                    align-items: stretch;
                }

                .inp {
                    width: 100%;
                }

                .sel {
                    width: 100%;
                }
            }

            /* Fix cards overflowing */
            .stat-pill,
            .qa-card,
            .ticket-card,
            .side-card {
                width: 100%;
            }

            /* Improve padding on mobile */
            @media (max-width: 600px) {
                .support-wrap {
                    padding: 10px 0 80px;
                }

                .support-banner {
                    padding: 20px;
                }

                .ticket-card {
                    padding: 16px;
                }
            }

            .quick-help-row {
                grid-template-columns: 1fr;
            }

            .ticket-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .grid-2 {
                grid-template-columns: 1fr;
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

                <div class="support-banner">
                    <div class="banner-content">
                        <h2><i class="ph-fill ph-headset"></i> Support Center</h2>
                        <p>How can we help you today? Create a ticket or explore our help guide.</p>
                    </div>
                    <div class="banner-actions actions">
                        <button class="btn-main" style="background:rgba(255,255,255,0.2); border:none;"
                            onclick="openWhatsApp()">
                            <i class="ph ph-whatsapp-logo"></i> Priority Support
                        </button>
                    </div>
                </div>

                <!-- KPI -->
                <div class="insight-grid">
                    <div class="stat-pill">
                        <div class="ic" style="background:#eff6ff; color:var(--info);"><i
                                class="ph ph-envelope-open"></i></div>
                        <div><span>Open</span><b id="kpiOpen"><?= (int) ($open_ticket_count ?? 0); ?></b></div>
                    </div>
                    <div class="stat-pill">
                        <div class="ic" style="background:#fff7ed; color:var(--warning);"><i
                                class="ph ph-hourglass"></i></div>
                        <div><span>Pending</span><b id="kpiPending"><?= (int) ($pending_ticket_count ?? 0); ?></b></div>
                    </div>
                    <div class="stat-pill">
                        <div class="ic" style="background:#ecfdf5; color:var(--success);"><i
                                class="ph ph-check-circle"></i></div>
                        <div><span>Resolved</span><b id="kpiClosed"><?= (int) ($closed_ticket_count ?? 0); ?></b></div>
                    </div>
                    <div class="stat-pill">
                        <div class="ic" style="background:#f5f3ff; color:var(--primary);"><i class="ph ph-ticket"></i>
                        </div>
                        <div><span>All Tickets</span><b id="kpiAll"><?= (int) ($all_ticket_count ?? 0); ?></b></div>
                    </div>
                </div>

                <div class="grid-2">
                    <!-- LEFT -->
                    <section>
                        <div class="quick-help-row">
                            <div class="qa-card" onclick="openTicket()">
                                <div class="ic-box"><i class="ph ph-plus-circle"></i></div>
                                <h4>New Ticket</h4>
                                <p>Issues with payout or account?</p>
                            </div>
                            <div class="qa-card" onclick="goToKyc()">
                                <div class="ic-box" style="background:#fff7ed; color:var(--warning);"><i
                                        class="ph ph-fingerprint"></i></div>
                                <h4>KYC Help</h4>
                                <p>Rejected docs or bank errors?</p>
                            </div>
                            <div class="qa-card" onclick="goToWithdraw()">
                                <div class="ic-box" style="background:#f0fdf4; color:var(--success);"><i
                                        class="ph ph-hand-coins"></i></div>
                                <h4>Withdrawal</h4>
                                <p>Minimums and payout cycles.</p>
                            </div>
                        </div>

                        <div class="card-h" style="margin-bottom:12px;">
                            <h3 style="font-size:18px; font-weight:800;">Recent Conversations</h3>
                            <div class="filters">
                                <input class="inp" id="q" placeholder="Search ticket..." style="min-width:180px;" />
                                <button class="btn-soft" onclick="resetFilters()"><i
                                        class="ph ph-arrow-counter-clockwise"></i></button>
                            </div>
                        </div>

                        <!-- filter chips -->
                        <div class="filter-chip-row">
                            <button class="filter-chip active" data-filter="all_ticket" onclick="setFilter(this)">
                                <i class="ph ph-list"></i> All
                            </button>
                            <button class="filter-chip" data-filter="0" onclick="setFilter(this)">
                                <i class="ph ph-hourglass"></i> Pending
                            </button>
                            <button class="filter-chip" data-filter="1" onclick="setFilter(this)">
                                <i class="ph ph-envelope-open"></i> Open
                            </button>
                            <button class="filter-chip" data-filter="2" onclick="setFilter(this)">
                                <i class="ph ph-check-circle"></i> Closed
                            </button>
                            <button class="filter-chip" data-filter="new_ticket" onclick="setFilter(this)">
                                <i class="ph ph-sparkle"></i> New Today
                            </button>
                        </div>

                        <!-- list -->
                        <div id="ticketList">
                            <div class="ticket-loader">
                                <i class="ph ph-hourglass" style="font-size:20px;"></i><br><br>
                                Loading tickets...
                            </div>
                        </div>

                        <!-- pager -->
                        <div
                            style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;gap:10px;flex-wrap:wrap;">
                            <div style="font-weight:900;color:var(--muted);font-size:12px;">
                                Showing <span id="showCount">0</span> of <span
                                    id="totalCount"><?= (int) ($all_ticket_count ?? 0); ?></span>
                            </div>
                            <div style="display:flex;gap:10px;">
                                <button class="btn-soft" id="prevBtn" onclick="prevPage()"><i
                                        class="ph ph-caret-left"></i> Prev</button>
                                <button class="btn-soft" id="nextBtn" onclick="nextPage()">Next <i
                                        class="ph ph-caret-right"></i></button>
                            </div>
                        </div>
                    </section>

                    <!-- RIGHT -->
                    <aside>
                        <div class="side-card">
                            <h3><i class="ph ph-lightning" style="color:var(--warning)"></i> Quick Actions</h3>
                            <button class="sidebtn primary" onclick="openTicket()"><i class="ph ph-plus"></i> Create New
                                Ticket</button>
                            <button class="sidebtn soft" style="margin-top:10px;" onclick="goToWithdraw()"><i
                                    class="ph ph-bank"></i> Payout Status</button>
                        </div>

                        <div class="side-card">
                            <h3><i class="ph ph-question" style="color:var(--info)"></i> Common FAQs</h3>
                            <div class="faq">
                                <?php if (!empty($faqs)):
                                    foreach ($faqs as $f): ?>
                                        <div class="faq-pill" onclick="toggleFaq(this)">
                                            <b><?= htmlspecialchars($f->question); ?> <i class="ph ph-caret-down"></i></b>
                                            <p><?= htmlspecialchars($f->answer); ?></p>
                                        </div>
                                    <?php endforeach; else: ?>
                                    <div class="qa-card" style="padding:18px; border-style:dashed;">
                                        <p>No FAQs available.</p>
                                    </div>
                                <?php endif; ?>
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

    <!-- CREATE TICKET MODAL -->
    <div class="modal-backdrop" id="ticketModal">
        <div class="modal">
            <div class="modal-h">
                <b>Create Support Ticket</b>
                <button class="xbtn" onclick="closeTicket()"><i class="ph ph-x"></i></button>
            </div>

            <div class="modal-b">
                <form id="createTicketForm" method="post" action="<?= base_url('user/create-ticket'); ?>"
                    enctype="multipart/form-data">
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
                            <input class="inp" name="ticket_message"
                                placeholder="Example: Withdrawal pending after bank update" required>
                        </div>

                        <div class="field full">
                            <label>Describe your issue</label>
                            <textarea class="ta inp" name="ticket_discription"
                                placeholder="Add details like UID, date/time, transaction ref, screenshots info..."
                                required></textarea>
                            <div class="hint">Tip: More details = faster resolution.</div>
                        </div>

                        <div class="field full">
                            <label>Attachment (optional)</label>
                            <input class="inp" type="file" name="ticketimage" accept="image/*,.pdf">
                            <div class="hint">Upload screenshot or PDF (max size based on server settings).</div>
                        </div>

                        <div class="field full">
                            <div id="ticketFormMsg" style="display:none;margin:8px 0;font-weight:1000;font-size:12px;">
                            </div>
                            <button class="btn-main" id="ticketSubmitBtn" type="submit">
                                <i class="ph ph-check"></i> Submit Ticket
                            </button>
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
        // =============================
        // ✅ AJAX Ticket List
        // =============================
        const LIST_URL = "<?= base_url('user/user-support-tickets'); ?>";   // current API (datatable style)
        const VIEW_URL = "<?= base_url('user/view-ticket/'); ?>";   // view ticket
        const TICKET_VIEW_API = "<?= base_url('user/support-ticket-view'); ?>";
        let currentFilter = 'all_ticket'; // filter_by
        let searchText = '';
        let start = 0;
        const pageSize = 10;

        const qEl = document.getElementById('q');
        const ticketList = document.getElementById('ticketList');
        const showCountEl = document.getElementById('showCount');
        const totalCountEl = document.getElementById('totalCount');

        function setFilter(btn) {
            document.querySelectorAll('.filter-chip').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            currentFilter = btn.getAttribute('data-filter') || 'all_ticket';
            start = 0;
            loadTickets();
        }

        function resetFilters() {
            qEl.value = '';
            searchText = '';
            start = 0;

            document.querySelectorAll('.filter-chip').forEach(b => b.classList.remove('active'));
            const allBtn = document.querySelector('.filter-chip[data-filter="all_ticket"]');
            if (allBtn) allBtn.classList.add('active');

            currentFilter = 'all_ticket';
            loadTickets();
        }

        function prevPage() {
            start = Math.max(0, start - pageSize);
            loadTickets();
        }

        function nextPage() {
            start = start + pageSize;
            loadTickets();
        }

        qEl.addEventListener('input', () => {
            searchText = (qEl.value || '').trim().toLowerCase();
            start = 0;
            loadTickets();
        });

        function escapeHtml(str) {
            return (str || '')
                .toString()
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", "&#039;");
        }

        function openTicketView(id) {
            location.href = VIEW_URL + encodeURIComponent(id);
        }

        function renderEmpty(total) {
            ticketList.innerHTML = `
        <div class="qa-card" style="padding:40px; border-style:dashed;">
          <i class="ph ph-chat-circle-dots" style="font-size:40px; color:#ccc;"></i>
          <p>No tickets found.</p>
        </div>
      `;
            showCountEl.innerText = '0';
            if (typeof total === 'number') totalCountEl.innerText = String(total);
        }

        function renderWarning(total) {
            ticketList.innerHTML = `
        <div class="qa-card" style="padding:28px; border-style:dashed;">
          <i class="ph ph-warning-circle" style="font-size:34px; color:#f59e0b;"></i>
          <p style="margin-top:10px;">
            Your <b>support-list</b> API is returning HTML table format.<br>
            Please update controller to return raw ticket fields (items[]), then this UI will render cards.
          </p>
        </div>
      `;
            showCountEl.innerText = '0';
            if (typeof total === 'number') totalCountEl.innerText = String(total);
        }

        function statusTextFromNum(n) {
            n = String(n ?? '0');
            if (n === '1') return 'OPEN';
            if (n === '2') return 'CLOSED';
            return 'PENDING';
        }

        function badgeClassFromNum(n) {
            n = String(n ?? '0');
            if (n === '1') return 'b-blue';
            if (n === '2') return 'b-ok';
            return 'b-warn';
        }

        function renderTickets(items, total) {
            if (!items || items.length === 0) {
                renderEmpty(total);
                return;
            }

            // client-side quick search
            if (searchText) {
                items = items.filter(t => {
                    const hay = `${t.subject || ''} ${t.ticket_id || ''}`.toLowerCase();
                    return hay.includes(searchText);
                });
            }

            if (items.length === 0) {
                renderEmpty(total);
                return;
            }

            const html = items.map(t => {
                const id = t.id;
                const code = t.ticket_id || '--';
                const subject = t.subject || '--';
                const updated = t.updated_at || t.date || '--';
                const stNum = t.status; // 0/1/2
                const stText = t.status_label || statusTextFromNum(stNum);
                const stClass = badgeClassFromNum(stNum);

                const lastMsg = (t.last_msg || t.discription || '').toString();
                const lastShort = lastMsg.length > 80 ? lastMsg.slice(0, 80) + '...' : lastMsg;

                return `
          <div class="ticket-card">
            <div class="t-main-info">
              <div class="t-cat-ic"><i class="ph ph-tag-chevron"></i></div>
              <div class="t-details">
                <h5>${escapeHtml(subject)}</h5>
                <small>${escapeHtml(code)} • Updated ${escapeHtml(updated)}</small>
                <p class="t-msg">"${escapeHtml(lastShort)}"</p>
              </div>
            </div>

            <div class="t-meta-group">
              <div class="t-badge ${stClass}">${escapeHtml(stText)}</div>
              <button class="btn-mini"class="ticket-card row" data-id="${id}" onclick="openTicketPopup('${id}')" style="width:40px; height:40px; border-radius:50%;">
                <i class="ph ph-caret-right" style="font-size:18px;"></i>
              </button>
            </div>
          </div>
        `;
            }).join('');

            ticketList.innerHTML = html;
            showCountEl.innerText = String(items.length);
            if (typeof total === 'number') totalCountEl.innerText = String(total);
        }

        async function loadTickets() {
            ticketList.innerHTML = `
        <div class="ticket-loader">
          <i class="ph ph-hourglass" style="font-size:20px;"></i><br><br>
          Loading tickets...
        </div>
      `;

            const params = new URLSearchParams();
            params.set('draw', '1');
            params.set('start', String(start));
            params.set('length', String(pageSize));
            params.set('filter_by', String(currentFilter));

            try {
                const res = await fetch(LIST_URL + '?' + params.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();

                const total = Number(json.recordsTotal || 0);

                // ✅ preferred response for this UI (raw)
                if (json.items && Array.isArray(json.items)) {
                    renderTickets(json.items, total);
                    return;
                }

                // ❌ current response (datatable html)
                if (json.data && Array.isArray(json.data)) {
                    renderWarning(total);
                    return;
                }

                renderEmpty(total);
            } catch (e) {
                ticketList.innerHTML = `
          <div class="qa-card" style="padding:28px; border-style:dashed;">
            <i class="ph ph-x-circle" style="font-size:34px; color:#ef4444;"></i>
            <p style="margin-top:10px;">Failed to load tickets. Please try again.</p>
          </div>
        `;
                showCountEl.innerText = '0';
            }
        }

        // ===== Ticket Modal =====
        function openTicket() { document.getElementById('ticketModal').style.display = 'flex'; }
        function closeTicket() { document.getElementById('ticketModal').style.display = 'none'; }

        // ===== FAQ =====
        function toggleFaq(el) { el.classList.toggle('open'); }

        // ===== Quick actions =====
        function openWhatsApp() {
            const phone = "919999999999";
            const msg = encodeURIComponent("Hi Support, I need help.");
            window.open("https://wa.me/" + phone + "?text=" + msg, "_blank");
        }
        function goToKyc() { location.href = "<?= base_url('user/profile'); ?>#kyc"; }
        function goToWithdraw() { location.href = "<?= base_url('user/withdraw'); ?>"; }

        // init
        loadTickets();
    </script>

    <script>
        const CREATE_URL = "<?= base_url('user/create-ticket'); ?>";

        const form = document.getElementById('createTicketForm');
        const msgBox = document.getElementById('ticketFormMsg');
        const submitBtn = document.getElementById('ticketSubmitBtn');

        function showFormMsg(ok, text) {
            msgBox.style.display = 'block';
            msgBox.style.padding = '10px 12px';
            msgBox.style.borderRadius = '12px';
            msgBox.style.border = ok ? '1px solid #dcfce7' : '1px solid #fee2e2';
            msgBox.style.background = ok ? '#ecfdf3' : '#fef2f2';
            msgBox.style.color = ok ? '#0f9d58' : '#b91c1c';
            msgBox.innerText = text || (ok ? 'Success' : 'Failed');
        }

        function setLoading(isLoading) {
            submitBtn.disabled = isLoading;
            submitBtn.style.opacity = isLoading ? '0.7' : '1';
            submitBtn.innerHTML = isLoading
                ? '<i class="ph ph-circle-notch ph-spin"></i> Submitting...'
                : '<i class="ph ph-check"></i> Submit Ticket';
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            msgBox.style.display = 'none';

            const fd = new FormData(form);

            // ✅ basic client validation (optional)
            if (!fd.get('category') || !fd.get('priority') || !fd.get('ticket_message') || !fd.get('ticket_discription')) {
                showFormMsg(false, 'All fields are required.');
                return;
            }

            try {
                setLoading(true);

                const res = await fetch(CREATE_URL, {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const json = await res.json();

                if (json && json.status) {
                    showFormMsg(true, json.message || 'Ticket created successfully');

                    // ✅ reset form
                    form.reset();

                    // ✅ close modal after short delay
                    setTimeout(() => {
                        closeTicket();
                        msgBox.style.display = 'none';
                    }, 700);

                    // ✅ reload ticket list
                    if (typeof loadTickets === 'function') {
                        start = 0; // reset pagination
                        loadTickets();
                    }

                    // ✅ optional: refresh KPI counts
                    if (typeof refreshCounts === 'function') {
                        refreshCounts();
                    }

                } else {
                    showFormMsg(false, (json && json.message) ? json.message : 'Failed to create ticket');
                }

            } catch (err) {
                showFormMsg(false, 'Server error. Please try again.');
            } finally {
                setLoading(false);
            }
        });
    </script>
    <!-- ===================== VIEW TICKET MODAL ===================== -->
    <div class="modal-backdrop" id="viewTicketModal" style="display:none;">
        <div class="modal" style="width:min(860px,100%);">
            <div class="modal-h">
                <b id="vtTitle">Ticket Details</b>
                <button class="xbtn" onclick="closeTicketView()"><i class="ph ph-x"></i></button>
            </div>

            <div class="modal-b">
                <div id="vtBody" class="ticket-loader" style="text-align:left;">
                    Loading...
                </div>
            </div>
        </div>
    </div>
    <style>
        .vt-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .vt-top .left h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 1200;
        }

        .vt-top .left small {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-weight: 900;
        }

        .vt-top .right {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .vt-chip {
            border: 1px solid #f1f1f6;
            background: #fff;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: 1100;
        }

        .vt-hr {
            height: 1px;
            background: #f0f0f7;
            margin: 14px 0;
            border: 0;
        }

        .msg-list {
            display: grid;
            gap: 10px;
        }

        .msg {
            border: 1px solid #f1f1f6;
            background: #fff;
            border-radius: 16px;
            padding: 12px;
        }

        .msg.me {
            background: #fafbff;
            border-color: #e9e7ff;
        }

        .msg .meta {
            font-size: 11px;
            color: var(--muted);
            font-weight: 900;
            margin-bottom: 6px;
        }

        .msg .txt {
            font-size: 13px;
            font-weight: 900;
            color: #111;
            line-height: 1.5;
            white-space: pre-wrap;
        }

        .vt-attach a {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid #f1f1f6;
            background: #fff;
            font-weight: 1100;
            font-size: 12px;
            text-decoration: none;
        }
    </style>
    <script>
        function openTicketPopup(id) {
            document.getElementById('viewTicketModal').style.display = 'flex';
            document.getElementById('vtTitle').innerText = 'Ticket Details';
            document.getElementById('vtBody').innerHTML = `
    <div class="ticket-loader" style="text-align:left;">
      <i class="ph ph-hourglass" style="font-size:20px;"></i><br><br>
      Loading ticket...
    </div>
  `;
            fetchTicketDetails(id);
        }

        function closeTicketView() {
            document.getElementById('viewTicketModal').style.display = 'none';
        }

        async function fetchTicketDetails(id) {
            try {
                const res = await fetch(TICKET_VIEW_API + "?id=" + encodeURIComponent(id), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();

                if (!json || !json.status) {
                    document.getElementById('vtBody').innerHTML = `
        <div class="qa-card" style="padding:18px;border-style:dashed;">
          <i class="ph ph-x-circle" style="font-size:26px;color:#ef4444;"></i>
          <p style="margin-top:8px;">${escapeHtml(json?.message || 'Unable to load ticket')}</p>
        </div>`;
                    return;
                }

                renderTicketPopup(json.data);

            } catch (e) {
                document.getElementById('vtBody').innerHTML = `
      <div class="qa-card" style="padding:18px;border-style:dashed;">
        <i class="ph ph-x-circle" style="font-size:26px;color:#ef4444;"></i>
        <p style="margin-top:8px;">Network error. Please try again.</p>
      </div>`;
            }
        }

        function renderTicketPopup(d) {
            const stText = (d.status == 1 ? 'OPEN' : (d.status == 2 ? 'CLOSED' : 'PENDING'));
            const stClass = (d.status == 1 ? 'b-blue' : (d.status == 2 ? 'b-ok' : 'b-warn'));

            const attachHtml = d.files ? `
    <div class="vt-attach" style="margin-top:12px;">
      <a href="${escapeHtml(d.file_url)}" target="_blank">
        <i class="ph ph-paperclip"></i> View Attachment
      </a>
    </div>` : '';

            const msgs = Array.isArray(d.messages) ? d.messages : [];
            const msgHtml = msgs.length ? `
    <div class="msg-list">
      ${msgs.map(m => `
        <div class="msg ${m.admin == 0 ? 'me' : ''}">
          <div class="meta">${m.admin == 1 ? 'Admin' : 'You'} • ${escapeHtml(m.created_date || '')}</div>
          <div class="txt">${escapeHtml(m.message || '')}</div>
        </div>
      `).join('')}
    </div>` : `<div class="ticket-loader" style="text-align:left;">No messages yet.</div>`;

            document.getElementById('vtTitle').innerText = `${d.ticket_id || 'Ticket'} - ${stText}`;

            document.getElementById('vtBody').innerHTML = `
    <div class="vt-top">
      <div class="left">
        <h4>${escapeHtml(d.subject || '--')}</h4>
        <small>${escapeHtml(d.ticket_id || '')} • Updated ${escapeHtml(d.date || '')}</small>
      </div>
      <div class="right">
        <div class="t-badge ${stClass}">${stText}</div>
        ${d.category ? `<div class="vt-chip">${escapeHtml(d.category)}</div>` : ''}
        ${d.priority ? `<div class="vt-chip">${escapeHtml(d.priority)}</div>` : ''}
      </div>
    </div>

    <div style="font-size:13px;font-weight:900;color:#111;white-space:pre-wrap;">
      ${escapeHtml(d.discription || '')}
    </div>

    ${attachHtml}

    <hr class="vt-hr">

    <div style="font-weight:1200;margin-bottom:10px;">Conversation</div>
    ${msgHtml}
  `;
        }
    </script>
</body>

</html>