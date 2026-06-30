<!DOCTYPE html>
<html lang="en">

<head>
  <?php $this->load->view('user/layout/v2/user_style'); ?>
  <style>
    /* =========================
     MOBILE RESPONSIVE PATCH
     ========================= */

    html,
    body {
      overflow-x: hidden;
    }

    .main-content {
      min-width: 0;
    }

    /* Make images flexible */
    img {
      max-width: 100%;
      height: auto;
    }

    /* ---------- Default: keep desktop as-is ---------- */

    /* ---------- Tablet (<= 992px) ---------- */
    @media (max-width: 1024px) {
      .app-container {
        display: block !important;
      }

      .sidebar {
        position: fixed !important;
        left: 0;
        top: 0;
        height: 100vh;
        width: 280px;
        max-width: 85vw;
        transform: translateX(-110%);
        transition: .25s ease;
        z-index: 99999;
        background: #fff;
        box-shadow: 20px 0 60px rgba(0, 0, 0, .15);
      }

      .sidebar.open {
        transform: translateX(0);
      }

      .sidebar-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .35);
        z-index: 99998;
        display: none;
      }

      .sidebar-backdrop.show {
        display: block;
      }

      .main-content {
        width: 100% !important;
        margin-left: 0 !important;
        padding: 14px !important;
      }

      .right-panel {
        width: 100% !important;
        position: static !important;
        height: auto !important;
        overflow: visible !important;
        margin-top: 12px !important;
      }

      /* Banner stacks */
      .hero-grid {
        grid-template-columns: 1fr !important;
        gap: 14px !important;
      }

      .hero-right {
        display: none;
      }

      /* optional: hide image on tablet for space */

      /* KPI grid */
      .kpi-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 12px !important;
      }

      /* Quick actions */
      .quick-actions {
        flex-wrap: wrap !important;
        gap: 10px !important;
      }

      .quick-actions .qa {
        flex: 1 1 calc(50% - 10px) !important;
        min-width: 160px;
      }

      /* Two columns → one column */
      .two-col {
        grid-template-columns: 1fr !important;
        gap: 14px !important;
      }

      /* Recent lists → one column */
      .lists {
        grid-template-columns: 1fr !important;
        gap: 14px !important;
      }

      /* Binary grid becomes 2 columns */
      .binary-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 12px !important;
      }
    }

    /* ---------- Mobile (<= 576px) ---------- */
    @media (max-width: 576px) {
      .main-content {
        padding: 12px !important;
      }

      .right-panel {
        margin-top: 12px !important;
      }

      /* Banner padding */
      .banner-wrapper {
        border-radius: 18px !important;
      }

      .slide-hero {
        padding: 14px !important;
      }

      .hero-title {
        font-size: 18px !important;
        line-height: 1.2 !important;
      }

      .hero-desc {
        font-size: 12px !important;
      }

      /* KPI grid -> 1 column */
      .kpi-grid {
        grid-template-columns: 1fr !important;
        gap: 12px !important;
      }

      .kpi-card {
        padding: 12px !important;
        border-radius: 18px !important;
      }

      /* Quick actions -> 2 columns */
      .quick-actions {
        gap: 10px !important;
      }

      .quick-actions .qa {
        flex: 1 1 calc(50% - 10px) !important;
        padding: 10px 10px !important;
        border-radius: 14px !important;
        font-size: 12px !important;
      }

      /* Binary summary cards -> 1 column */
      .binary-grid {
        grid-template-columns: 1fr !important;
        gap: 12px !important;
      }

      /* Team snapshot -> 2 columns */
      .small-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 10px !important;
      }

      /* Lists */
      .list {
        border-radius: 18px !important;
      }

      .row-item {
        padding: 10px !important;
        border-radius: 14px !important;
      }

      /* Buttons */
      .btn,
      .btn-full {
        width: 100% !important;
      }
    }

    /* ---------- Optional: smaller font fixes ---------- */
    @media (max-width: 360px) {
      .hero-title {
        font-size: 16px !important;
      }

      .kpi-meta strong {
        font-size: 14px !important;
      }
    }
  </style>
</head>

<body>
  <div class="app-container">
    <!-- Sidebar -->
    <?php $this->load->view('user/layout/v2/user_sidebar'); ?>

    <!-- Main Content -->
    <main class="main-content">

      <?php $this->load->view('user/layout/v2/user_header'); ?>

      <!-- ===================== ANNOUNCEMENT BANNER (ONLY TEXT SLIDES) ===================== -->
      <div class="banner-wrapper banner-fixed">

        <!-- ONE FIXED SLIDE (background + image stays static) -->
        <div class="slide slide-hero active" style="position: relative; inset:auto; opacity:1;">
          <div class="hero-grid">

            <!-- Left Content -->
            <div class="hero-left">
              <div class="tag"><i class="ph ph-megaphone"></i> Announcement</div>

              <?php if (!empty($notification)): ?>
                <!-- ✅ Only TEXT rotates here -->
                <div id="announcementCarousel" class="carousel slide carousel-fade text-only-carousel"
                  data-bs-ride="carousel" data-bs-interval="3200" data-bs-pause="false" data-bs-touch="true">

                  <div class="carousel-inner">
                    <?php $first = true;
                    foreach ($notification as $note): ?>
                      <div class="carousel-item <?= $first ? 'active' : ''; ?>">
                        <h1 class="hero-title">— <?= htmlspecialchars($note->title); ?></h1>
                      </div>
                      <?php $first = false; endforeach; ?>
                  </div>

                  <!-- OPTIONAL: tiny dots for text only (remove if you don't want) -->
                  <div class="text-dots">
                    <?php for ($i = 0; $i < count($notification); $i++): ?>
                      <button type="button" data-bs-target="#announcementCarousel" data-bs-slide-to="<?= $i ?>"
                        class="<?= $i === 0 ? 'active' : '' ?>" aria-label="Slide <?= $i + 1 ?>"></button>
                    <?php endfor; ?>
                  </div>

                </div>
              <?php else: ?>
                <h1 class="hero-title">— Latest announcements will appear here</h1>
              <?php endif; ?>

              <p class="hero-desc">
                Withdrawals and weekly commissions will be processed automatically. Ensure your KYC and bank details are
                updated.
              </p>

              <div class="slide-actions">
                <button class="btn primary">Withdraw Now <i class="ph ph-arrow-circle-right"></i></button>
                <button class="btn ghost">View Payout Rules <i class="ph ph-info"></i></button>
              </div>
            </div>

            <!-- Right Image (STATIC ALWAYS) -->
            <div class="hero-right">
              <img class="hero-img" src="<?= base_url(); ?>assets/user/media/misc/city.png" alt="banner image">
            </div>

          </div>
        </div>

        <!-- ❌ REMOVE old dot slider (this is for your custom JS slide rotation) -->
        <div class="dots" id="sliderDots"></div>

      </div>


      <!-- Wallet & Commission KPIs -->
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-icon" style="background:#ecfdf3;color:var(--good);"><i class="ph ph-wallet"></i></div>
          <div class="kpi-meta">
            <small>Available Balance</small>
            <strong> <b id="user_usd_balance">0.00</b></strong>
            <span>Withdraw anytime</span>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon" style="background:#fff7ed;color:var(--warn);"><i class="ph ph-hourglass"></i></div>
          <div class="kpi-meta">
            <small>Pending Commission</small>
            <strong> <b id="user_pending_commission">0.00</b></strong>
            <span>Next payout today</span>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon" style="background:#eff6ff;color:var(--info);"><i class="ph ph-trend-up"></i></div>
          <div class="kpi-meta">
            <small>Total Earned</small>
            <strong> <b id="direct_site_currency">0.00</b></strong>
            <span>Lifetime income</span>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon" style="background:#fef2f2;color:var(--bad);"><i class="ph ph-bank"></i></div>
          <div class="kpi-meta">
            <small>Total Withdrawn</small>
            <strong> <b id="user_total_withdrawn">0.00</b></strong>
            <span>To bank account</span>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <div class="qa"><i class="ph ph-money"></i> Withdraw</div>
        <div class="qa"><i class="ph ph-arrows-left-right"></i> Transfer Wallet</div>
        <div class="qa"><i class="ph ph-tree-structure"></i> View Binary Tree</div>
        <div class="qa"><i class="ph ph-user-plus"></i> Invite Member</div>
        <div class="qa"><i class="ph ph-bag"></i> Shop Products</div>
        <div class="qa"><i class="ph ph-headset"></i> Support Ticket</div>
      </div>

      <!-- Binary + Team -->
      <div class="two-col">

        <div class="panel">
          <div class="panel-title">
            <h3>Binary Summary</h3>
            <span class="chip">This Week</span>
          </div>

          <div class="binary-grid">
            <!-- Left -->
            <div class="mini">
              <div class="mini-top">
                <span>Left Leg BV</span>
                <b id="left_leg_strength" style="color:#2563eb;">STRONG</b>
              </div>

              <div class="mini-value">
                <strong><span id="left_leg_bv">0.00</span></strong>
              </div>

              <small>Carry Forward: <span id="left_carry_forward_bv">320</span> BV</small>
            </div>

            <!-- Right -->
            <div class="mini">
              <div class="mini-top">
                <span>Right Leg BV</span>
                <b id="right_leg_strength" style="color:#f97316;">WEAK</b>
              </div>

              <div class="mini-value">
                <strong><span id="right_leg_bv">0.00</span></strong>
              </div>

              <small>Carry Forward: <span id="right_carry_forward_bv">0</span> BV</small>
            </div>

            <!-- Pairs -->
            <div class="mini">
              <div class="mini-top">
                <span>Pairs Completed</span>
                <b>Today</b>
              </div>

              <div class="mini-value">
                <strong><span id="pairs_today">0</span> Pairs</strong>
              </div>

              <small>Pairing Bonus Running</small>
            </div>

            <!-- Need BV -->
            <div class="mini">
              <div class="mini-top">
                <span>Next Pair Target</span>
                <b>Need</b>
              </div>

              <div class="mini-value">
                <strong>+<span id="need_bv">0</span> BV</strong>
              </div>

              <small>To match weak leg</small>
            </div>
          </div>

          <div class="progress-wrap">
            <div class="row">
              <b>Weekly Pair Target Progress</b>
              <span id="weekly_progress" style="font-size:12px;color:#5d56a8;font-weight:800;">0%</span>
            </div>

            <div class="bar">
              <div id="weekly_progress_bar" style="width:0%"></div>
            </div>

            <div class="checklist">
              <div class="check" id="chk_kyc"><i class="ph ph-warning-circle"></i> KYC Verified</div>
              <div class="check" id="chk_bank"><i class="ph ph-warning-circle"></i> Bank Linked</div>
              <div class="check" id="chk_active"><i class="ph ph-warning-circle"></i> Account Active</div>
              <div class="check" id="chk_weak"><i class="ph ph-warning-circle"></i> Weak Leg Needs BV</div>
            </div>
          </div>
        </div>

        <!-- Team Snapshot panel stays same -->

        <div class="panel">
          <div class="panel-title">
            <h3>Team Snapshot</h3>
            <span class="chip">7 Days</span>
          </div>

          <div class="small-grid">
            <div class="small-k">
              <small>Left Team</small>
              <!-- <strong><span id="left_leg_count">0</span></strong> -->
              <strong><span id="left_team_count">0</span></strong>
            </div>
            <div class="small-k">
              <small>Right Team</small>
              <!-- <strong><span id="right_leg_count">0</span></strong> -->
              <strong><span id="right_team_count">0</span></strong>
            </div>
            <div class="small-k">
              <small>Active Members</small>
              <strong><span id="active_members_count">0</span></strong>
            </div>
            <div class="small-k">
              <small>New Joins</small>
              <strong><span id="new_joins_count">0</span></strong>
            </div>
          </div>

          <div class="progress-wrap" style="margin-top:12px;">
            <div class="row">
              <b>Referral Link</b>
              <span style="font-size:11px;color:#5d56a8;font-weight:800;">Copy</span>
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
              <input id="referral_link"
                style="flex:1;border:none;outline:none;background:#fff;padding:10px 12px;border-radius:14px;border:1px solid #eeecff;font-size:12px;"
                value="<?php echo base_url() . 'user/re?ref=L-' . $userinfo->referral_id ?? ''; ?>" readonly />
              <button
                style="border:none;background:var(--primary);color:#fff;padding:10px 14px;border-radius:14px;font-weight:800;cursor:pointer;"
                onclick="copyText('left')"><i class="ph ph-copy"></i></button>
            </div>

            <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap;">
              <button class="btn primary" style="padding:10px 14px;" onclick="shareLink('left')">Invite <i
                  class="ph ph-share-network"></i></button>
              <button class="btn ghost" style="padding:10px 14px;">View Team <i class="ph ph-users-three"></i></button>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Commissions + Recent Orders -->
      <div class="lists">
        <div class="list">
          <div class="panel-title" style="margin-bottom:10px;">
            <h3>Recent Commissions</h3>
            <span class="chip">Latest</span>
          </div>

          <!-- AJAX will fill here -->
          <div id="recent_commissions_list">
            <div class="row-item">
              <div class="left">
                <div class="bullet"><i class="ph ph-link"></i></div>
                <div class="txt">
                  <b>Loading...</b>
                  <small>Please wait</small>
                </div>
              </div>
              <div class="amount">
                <small><span class="status pending">...</span></small>
              </div>
            </div>
          </div>

          <button id="btn_view_all_commissions" class="btn-full"
            onclick="window.location.href='<?= base_url('commissions'); ?>'">
            View All Commissions
          </button>
        </div>

        <div class="list">
          <div class="panel-title" style="margin-bottom:10px;">
            <h3>Recent Orders</h3>
            <span class="chip">E-Commerce</span>
          </div>

          <!-- AJAX will fill here -->
          <div id="recent_orders_list">
            <div class="row-item">
              <div class="left">
                <div class="bullet"><i class="ph ph-receipt"></i></div>
                <div class="txt">
                  <b>Loading...</b>
                  <small>Please wait</small>
                </div>
              </div>
              <div class="amount">
                <small><span class="status pending">...</span></small>
              </div>
            </div>
          </div>

          <button id="btn_view_all_orders" class="btn-full"
            onclick="window.location.href='<?= base_url('orders'); ?>'">View All Orders</button>
        </div>


      </div>
    </main>

    <!-- Right Panel -->
    <aside class="right-panel">
      <?php $this->load->view('user/layout/v2/user_inner_right_panle'); ?>
    </aside>
  </div>
  <!-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"
    integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script> -->

  <!--begin::Global Javascript Bundle(mandatory for all pages)-->
  <script src="<?php echo base_url(); ?>/assets/user/plugins/global/plugins.bundle.js"></script>
  <script src="<?php echo base_url(); ?>/assets/user/js/scripts.bundle.js"></script>

  <script src="<?php echo base_url(); ?>/assets/user/plugins/custom/datatables/datatables.bundle.js"></script>
  <!--end::Vendors Javascript-->

  <!--begin::Custom Javascript(used for this page only)-->
  <script src="<?php echo base_url(); ?>/assets/user/js/widgets.bundle.js"></script>
  <script src="<?php echo base_url(); ?>/assets/user/js/custom/widgets.js"></script>
  <!--end::Global Javascript Bundle-->
  <script>
    const base_url = "<?php echo base_url(); ?>";
    const agent_id = "<?php echo $this->session->userdata('user_get_id'); ?>";
    const currency_symbol = "<?php echo currency_info()->currency_symbol; ?>";
  </script>
  <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>
  <script
    src="<?php echo base_url(); ?>/assets/admin/js/custom/authentication/sign-in/user-dashboard.js?ver=2.9"></script>

  <script>
    function escapeHtml(str) {
      return String(str ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", "&#039;");
    }

    function statusClass(status) {
      const s = (status || '').toLowerCase();
      if (['delivered', 'success', 'completed'].includes(s)) return 'success';
      if (['shipped', 'in_transit', 'dispatch', 'dispatched'].includes(s)) return 'ship';
      if (['processing', 'placed', 'packed', 'pending'].includes(s)) return 'pending';
      if (['cancelled', 'canceled', 'failed', 'returned'].includes(s)) return 'failed';
      return 'pending';
    }

    function loadRecentOrders() {
      $.ajax({
        url: "<?= base_url('user/recentOrdersAjax'); ?>",
        type: "GET",
        dataType: "json",
        data: { limit: 4 },
        success: function (res) {
          // / default hide (will show only if data exists)
          $('#btn_view_all_orders').hide();
          if (!res || res.status !== true) {
            $('#recent_orders_list').html(`
          <div class="row-item">
            <div class="left">
              <div class="bullet"><i class="ph ph-receipt"></i></div>
              <div class="txt">
                <b>No Orders</b>
                <small>Nothing found</small>
              </div>
            </div>
            <div class="amount">
              <small><span class="status failed">EMPTY</span></small>
            </div>
          </div>
        `);
            return;
          }

          const symbol = res.currency_symbol || '₹';
          const orders = res.orders || [];

          if (orders.length === 0) {
            $('#recent_orders_list').html(`
          <div class="row-item">
            <div class="left">
              <div class="bullet"><i class="ph ph-receipt"></i></div>
              <div class="txt">
                <b>No Recent Orders</b>
                <small>Try later</small>
              </div>
            </div>
            <div class="amount">
              <small><span class="status pending">NONE</span></small>
            </div>
          </div>
        `);
            return;
          }

          // ✅ data exists → show button
          $('#btn_view_all_orders').show();

          let html = '';
          orders.forEach(o => {
            const cls = statusClass(o.order_status);
            html += `
          <div class="row-item">
            <div class="left">
              <div class="bullet"><i class="ph ph-receipt"></i></div>
              <div class="txt">
                <b>Order #${escapeHtml(o.order_code)}</b>
                <small>${escapeHtml(o.order_date)} • BV Earned: ${escapeHtml(o.bv_earned)}</small>
              </div>
            </div>
            <div class="amount">
              ${symbol} ${escapeHtml(o.total_amount)}
              <small><span class="status ${cls}">${escapeHtml((o.order_status || 'PROCESSING').toUpperCase())}</span></small>
            </div>
          </div>
        `;
          });

          $('#recent_orders_list').html(html);
        },
        error: function () {
          $('#btn_view_all_orders').hide();
          $('#recent_orders_list').html(`
        <div class="row-item">
          <div class="left">
            <div class="bullet"><i class="ph ph-receipt"></i></div>
            <div class="txt">
              <b>Failed to load</b>
              <small>Check API</small>
            </div>
          </div>
          <div class="amount">
            <small><span class="status failed">ERROR</span></small>
          </div>
        </div>
      `);
        }
      });
    }

    $(document).ready(function () {
      loadRecentOrders();
    });
  </script>

  <script>
    function commissionIcon(type) {
      const t = (type || '').toLowerCase();
      if (t === 'binary_commission') return 'ph-link';        // Pairing bonus
      if (t === 'level_commission') return 'ph-users-three';  // Matching/level
      if (t === 'direct_commission') return 'ph-user-plus';   // Direct bonus
      if (t === 'rank_reward' || t === 'rank') return 'ph-medal';
      if (t === 'profit' || t === 'roi') return 'ph-chart-line-up';
      return 'ph-receipt';
    }

    function commissionTitle(type) {
      const t = (type || '').toLowerCase();
      if (t === 'binary_commission') return 'Pairing Bonus (Binary)';
      if (t === 'level_commission') return 'Matching Bonus';
      if (t === 'direct_commission') return 'Direct Referral Bonus';
      if (t === 'rank_reward' || t === 'rank') return 'Rank Reward';
      if (t === 'profit' || t === 'roi') return 'ROI / Profit';
      return 'Commission';
    }

    function commissionStatusClass(status) {
      // Handle numeric statuses first
      console.log('status', status, status === '1');
      if (status === '1') return 'success';
      if (status === '0') return 'pending';
      if (status === -1) return 'failed';

      // Handle string statuses
      const s = String(status || '').toLowerCase();

      if (['success', 'paid', 'approved', 'completed'].includes(s)) return 'success';
      if (['pending', 'hold', 'processing'].includes(s)) return 'pending';
      if (['failed', 'rejected', 'cancelled', 'canceled'].includes(s)) return 'failed';

      return 'pending';
    }

    function loadRecentCommissions() {
      $.ajax({
        url: "<?= base_url('user/recentCommissionsAjax'); ?>",
        type: "GET",
        dataType: "json",
        data: { limit: 4 },
        success: function (res) {
          $('#btn_view_all_commissions').hide();

          if (!res || res.status !== true) {
            $('#recent_commissions_list').html(`
          <div class="row-item">
            <div class="left">
              <div class="bullet"><i class="ph ph-warning"></i></div>
              <div class="txt">
                <b>No Commissions</b>
                <small>Nothing found</small>
              </div>
            </div>
            <div class="amount">
              <small><span class="status failed">EMPTY</span></small>
            </div>
          </div>
        `);
            return;
          }

          const symbol = res.currency_symbol || '₹';
          const list = res.commissions || [];

          if (list.length === 0) {
            $('#recent_commissions_list').html(`
          <div class="row-item">
            <div class="left">
              <div class="bullet"><i class="ph ph-warning"></i></div>
              <div class="txt">
                <b>No Recent Commissions</b>
                <small>Try later</small>
              </div>
            </div>
            <div class="amount">
              <small><span class="status pending">NONE</span></small>
            </div>
          </div>
        `);
            return;
          }

          $('#btn_view_all_commissions').show();
          let html = '';
          list.forEach(c => {
            const icon = commissionIcon(c.type);
            const title = c.title || commissionTitle(c.type);
            const cls = commissionStatusClass(c.status);

            html += `
          <div class="row-item">
            <div class="left">
              <div class="bullet"><i class="ph ${escapeHtml(icon)}"></i></div>
              <div class="txt">
                <b>${escapeHtml(title)}</b>
                <small>${escapeHtml(c.date_text)}${c.meta ? ' • ' + escapeHtml(c.meta) : ''}</small>
              </div>
            </div>
            <div class="amount">
              ${symbol} ${escapeHtml(c.amount)}
              <small><span class="status ${cls}">${escapeHtml((cls || 'PENDING').toUpperCase())}</span></small>
            </div>
          </div>
        `;
          });

          $('#recent_commissions_list').html(html);
        },
        error: function () {
          $('#btn_view_all_commissions').hide();
          $('#recent_commissions_list').html(`
        <div class="row-item">
          <div class="left">
            <div class="bullet"><i class="ph ph-warning"></i></div>
            <div class="txt">
              <b>Failed to load</b>
              <small>Check API</small>
            </div>
          </div>
          <div class="amount">
            <small><span class="status failed">ERROR</span></small>
          </div>
        </div>
      `);
        }
      });
    }

    $(document).ready(function () {
      loadRecentCommissions();
    });


  </script>
  <script>
    window.RefLinks = {
      left: "<?php echo base_url() . 'user/re?ref=L-' . $userinfo->referral_id ?? ''; ?>",
      right: "<?php echo base_url() . 'user/re?ref=R-' . $userinfo->referral_id ?? ''; ?>"
    };
  </script>
  <script>
    function getLink(side) {
      const links = window.RefLinks || {};
      const url = (side === 'left') ? (links.left || '') : (links.right || '');
      return (url || '').trim();
    }

    async function copyText(side) {
      const url = getLink(side);
      if (!url) return toastMini("Link not available");

      try {
        await navigator.clipboard.writeText(url);
        toastMini("Copied!");
      } catch (e) {
        // fallback
        const tmp = document.createElement('textarea');
        tmp.value = url;
        document.body.appendChild(tmp);
        tmp.select();
        document.execCommand('copy');
        tmp.remove();
        toastMini("Copied!");
      }
    }

    function openLink(side) {
      const url = getLink(side);
      if (!url) return toastMini("Link not available");
      window.open(url, '_blank');
    }

    async function shareLink(side) {
      const url = getLink(side);
      if (!url) return toastMini("Link not available");

      if (navigator.share) {
        try {
          await navigator.share({ title: "Join my team", text: "Use my referral link to join:", url });
        } catch (e) { }
      } else {
        await copyText(side);
        toastMini("Share not supported. Link copied!");
      }
    }

    function copyAllRefs() {
      const left = getLink('left');
      const right = getLink('right');
      const txt = `Left Leg: ${left}\nRight Leg: ${right}`;
      navigator.clipboard.writeText(txt).then(() => toastMini("Both links copied!"));
    }

    function downloadQR() {
      toastMini("QR generator not connected yet. Tell me if you want it with JS QR library.");
    }

    function toastMini(msg) {
      const t = document.createElement('div');
      t.textContent = msg;
      t.style.cssText =
        "position:fixed;bottom:22px;left:50%;transform:translateX(-50%);background:#111;color:#fff;padding:10px 14px;border-radius:14px;font-weight:800;font-size:12px;z-index:99999;opacity:0;transition:.2s;";
      document.body.appendChild(t);
      requestAnimationFrame(() => t.style.opacity = "1");
      setTimeout(() => { t.style.opacity = "0"; setTimeout(() => t.remove(), 250); }, 1400);
    }
  </script>
</body>

</html>
