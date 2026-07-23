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
        text-decoration: none !important;
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
        text-decoration: none !important;
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

      <!-- ===================== ANNOUNCEMENT BANNER ===================== -->
      <?php
      $defaultHeroImg = base_url() . 'assets/user/media/misc/city.png';
      $alertGradient = 'linear-gradient(135deg,#ef4444,#b91c1c)';
      ?>
      <div class="banner-wrapper banner-fixed">

        <div class="slide slide-hero active" id="slideHero" style="position: relative; inset:auto; opacity:1;">
          <div class="hero-grid" id="heroGrid">

            <!-- Left Content -->
            <div class="hero-left">
              <div class="tag"><i class="ph ph-megaphone"></i> Announcement</div>

              <?php if (!empty($notification)): ?>
                <div id="announcementCarousel" class="carousel slide carousel-fade text-only-carousel"
                  data-bs-ride="carousel" data-bs-interval="5000" data-bs-pause="false" data-bs-touch="true">

                  <div class="carousel-inner">
                    <?php $first = true;
                    foreach ($notification as $note):
                      $type = $note->announcement_type ?? 'text';
                      $showImage = in_array($type, ['image', 'text_image'], true);
                      $showFullText = in_array($type, ['text', 'text_image'], true);
                      $imageOnly = ($type === 'image');
                      $hasRealImage = $showImage && !empty($note->image);
                      $isAlert = in_array($note->category ?? 'general', ['alert', 'maintenance'], true);
                      $bg = $isAlert ? $alertGradient : ($showImage ? '' : ($note->bg_color ?: ''));
                      $img = $hasRealImage ? base_url($note->image) : $defaultHeroImg;
                      $textColor = htmlspecialchars($note->text_color ?: '#ffffff');
                      $textPos = in_array($note->text_position ?? 'middle-left', ['top-left', 'bottom-left', 'center'], true) ? $note->text_position : 'middle-left';
                    ?>
                      <div class="carousel-item <?= $first ? 'active' : ''; ?>"
                        data-id="<?= (int) $note->id ?>"
                        data-bg="<?= htmlspecialchars($bg); ?>" data-image="<?= htmlspecialchars($img); ?>"
                        data-has-image="<?= $hasRealImage ? '1' : '0' ?>"
                        data-text-pos="<?= htmlspecialchars($textPos) ?>"
                        data-text-color="<?= $textColor ?>">
                        <?php if ($isAlert && !$imageOnly): ?><div class="fs-8 fw-bold mb-1" style="color:<?= $textColor ?>;">⚠ <?= strtoupper(htmlspecialchars($note->category)) ?></div><?php endif; ?>
                        <?php if (!$imageOnly): ?>
                        <h1 class="hero-title" style="color:<?= $textColor ?>;">— <?= htmlspecialchars($note->title); ?></h1>
                        <?php endif; ?>
                        <?php if ($showFullText && !empty($note->subtitle)): ?>
                          <div class="hero-subtitle" style="color:<?= $textColor ?>;opacity:.85;font-weight:700;margin-top:4px;"><?= htmlspecialchars($note->subtitle) ?></div>
                        <?php endif; ?>
                        <?php if ($showFullText && !empty($note->description)): ?>
                          <p class="hero-desc" style="color:<?= $textColor ?>;">
                            <?= nl2br(htmlspecialchars($note->description)) ?>
                          </p>
                        <?php endif; ?>
                        <?php if (!empty($note->button_text) && !empty($note->button_url)): ?>
                          <div class="slide-actions">
                            <a href="<?= htmlspecialchars($note->button_url) ?>" class="btn primary announcement-cta" data-id="<?= (int) $note->id ?>">
                              <?= htmlspecialchars($note->button_text) ?> <i class="ph ph-arrow-circle-right"></i>
                            </a>
                          </div>
                        <?php endif; ?>
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
            </div>

            <!-- Right Image (swaps per active announcement's image, if any) -->
            <div class="hero-right">
              <img class="hero-img" id="heroImg" src="<?= $defaultHeroImg; ?>" alt="banner image">
            </div>

          </div>
        </div>

        <div class="dots" id="sliderDots"></div>

      </div>

      <?php if (!empty($popup_announcement)): $pa = $popup_announcement; ?>
      <div class="modal fade" id="announcementPopup" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content" style="background:<?= htmlspecialchars($pa->bg_color ?: '#4E2CF0') ?>;color:<?= htmlspecialchars($pa->text_color ?: '#ffffff') ?>;border:0;border-radius:16px;">
            <div class="modal-header border-0">
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="announcementPopupClose"></button>
            </div>
            <div class="modal-body text-center pb-5">
              <?php if (!empty($pa->image)): ?><img src="<?= base_url($pa->image) ?>" style="max-width:100%;border-radius:10px;margin-bottom:16px;"><?php endif; ?>
              <h3 class="fw-bold" style="color:inherit;"><?= htmlspecialchars($pa->title) ?></h3>
              <?php if (!empty($pa->subtitle)): ?><div class="fw-semibold mt-1" style="opacity:.9;"><?= htmlspecialchars($pa->subtitle) ?></div><?php endif; ?>
              <?php if (!empty($pa->description)): ?><p class="mt-3" style="color:inherit;opacity:.9;"><?= nl2br(htmlspecialchars($pa->description)) ?></p><?php endif; ?>
              <?php if (!empty($pa->button_text) && !empty($pa->button_url)): ?>
                <a href="<?= htmlspecialchars($pa->button_url) ?>" class="btn btn-light fw-bold mt-3 announcement-cta" data-id="<?= (int) $pa->id ?>"><?= htmlspecialchars($pa->button_text) ?></a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <script>
        (function () {
          var base = '<?php echo base_url(); ?>';
          function beacon(path) {
            fetch(base + path, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, keepalive: true }).catch(function () {});
          }

          <?php if (!empty($notification)): ?>
          var carousel = document.getElementById('announcementCarousel');
          var slideHero = document.getElementById('slideHero');
          var heroImg = document.getElementById('heroImg');
          var seenViews = {};

          function trackView(item) {
            if (!item) return;
            var id = item.getAttribute('data-id');
            if (!id || seenViews[id]) return;
            seenViews[id] = true;
            beacon('user/announcement/view/' + id);
          }

          var heroGrid = document.getElementById('heroGrid');
          function applySlide(item) {
            if (!item || !slideHero || !heroImg) return;
            slideHero.style.background = item.getAttribute('data-bg') || '';
            heroImg.src = item.getAttribute('data-image') || '<?= $defaultHeroImg; ?>';
            if (heroGrid) {
              heroGrid.classList.toggle('has-image', item.getAttribute('data-has-image') === '1');
              heroGrid.classList.remove('text-pos-top-left', 'text-pos-bottom-left', 'text-pos-center');
              var pos = item.getAttribute('data-text-pos');
              if (pos && pos !== 'middle-left') heroGrid.classList.add('text-pos-' + pos);
            }
            trackView(item);
          }

          if (carousel && slideHero && heroImg) {
            applySlide(carousel.querySelector('.carousel-item.active'));
            carousel.addEventListener('slide.bs.carousel', function (e) { applySlide(e.relatedTarget); });
          }
          <?php endif; ?>

          document.querySelectorAll('.announcement-cta').forEach(function (a) {
            a.addEventListener('click', function () {
              var id = a.getAttribute('data-id');
              if (id) beacon('user/announcement/click/' + id);
            });
          });

          <?php if (!empty($popup_announcement)): ?>
          var popupEl = document.getElementById('announcementPopup');
          if (popupEl && window.bootstrap) {
            var popupModal = new bootstrap.Modal(popupEl);
            popupModal.show();
            beacon('user/announcement/view/<?= (int) $popup_announcement->id ?>');
            popupEl.addEventListener('hidden.bs.modal', function () {
              beacon('user/announcement/dismiss/<?= (int) $popup_announcement->id ?>');
            });
          }
          <?php endif; ?>
        })();
      </script>


      <!-- User Activity & Coin Trend chart -->
      <style>
        /* Palette + panel styling per the reference design. The chart owns a
           deliberate, fixed palette (blue in / green out) rather than the panel
           theme tokens, because the colours ARE the spec here: blue vs green is
           how in-vs-out is read at a glance. */
        .fin-chart-card{
          --c-blue:#2563eb; --c-green:#22c55e; --c-amber:#f59e0b;
          --c-purple:#8b5cf6; --c-grey:#94a3b8;
          --c-ink:#0f172a; --c-muted:#64748b; --c-line:#e2e8f0; --c-card:#ffffff;
          background:var(--c-card); border:1px solid var(--c-line);
          border-radius:18px; padding:22px 24px; margin:0 0 18px;
          box-shadow:0 6px 24px rgba(15,23,42,.05); color:var(--c-ink);
        }
        html[data-bs-theme="dark"] .fin-chart-card{
          --c-ink:#e8eaf0; --c-muted:#8b93a5; --c-line:#2b2c34; --c-card:#1e1f26;
        }
        .fin-chart-head{ display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:16px; margin-bottom:6px; }
        .fin-chart-head h3{ margin:0; font-weight:700; font-size:20px; color:var(--c-ink); }
        .fin-chart-head small{ color:var(--c-muted); font-size:13px; margin-top:3px; display:block; }
        /* Period dropdown, per the reference (replaces the old button group). */
        .fin-period{
          appearance:none; border:1px solid var(--c-line); background:var(--c-card);
          border-radius:10px; padding:9px 34px 9px 14px; font-size:14px; font-weight:600;
          color:var(--c-ink); cursor:pointer; font-family:inherit;
          background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='3'><path d='M6 9l6 6 6-6'/></svg>");
          background-repeat:no-repeat; background-position:right 12px center;
        }
        .fin-period:disabled{ opacity:.6; cursor:progress; }
        .fin-filter{ display:none; }  /* superseded by .fin-period */
        .fin-filter button{ border:none; background:transparent; padding:6px 16px; border-radius:30px; font-weight:600;
          font-size:13px; color:var(--bs-secondary-color,#6b7280); cursor:pointer; transition:.2s; }
        .fin-filter button.active{ background:var(--mp-primary,#6D4AFF); color:#fff; }
        .fin-chart-body{ position:relative; height:300px; }
      </style>
      <style>
        /* KPI cards — one per series, dot colour-matched to its dataset. */
        .fin-tiles{ display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin:18px 0 8px; }
        .fin-tile{ border:1px solid var(--c-line); border-radius:14px; padding:12px 14px;
          background:#fafbfe; min-width:0; }
        html[data-bs-theme="dark"] .fin-tile{ background:#24252d; }
        .fin-tile .k{ display:flex; align-items:center; gap:6px; font-size:12px;
          color:var(--c-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .fin-tile .k i{ width:9px; height:9px; border-radius:3px; flex:0 0 9px; display:inline-block; }
        .fin-tile .v{ font-size:20px; font-weight:700; margin-top:4px; color:var(--c-ink);
          font-variant-numeric:tabular-nums; }
        .fin-tile .v small{ font-size:12px; font-weight:400; color:var(--c-muted); margin-left:3px; }
        .fin-tile.is-loading .v{ color:transparent; border-radius:6px;
          background:linear-gradient(90deg,#e9edf3 25%,#f6f8fb 50%,#e9edf3 75%);
          background-size:200% 100%; animation:finsh 1.3s infinite; }
        @keyframes finsh{ 0%{background-position:200% 0} 100%{background-position:-200% 0} }
        .fin-chart-body{ position:relative; height:420px; margin-top:8px; }
        @media (max-width:760px){ .fin-tiles{ grid-template-columns:repeat(2,1fr); } }
      </style>
      <div class="fin-chart-card">
        <div class="fin-chart-head">
          <div>
            <h3>User Activity &amp; Coin Trend</h3>
            <small>Blue &amp; green bars (in vs out) · Earning / Bonus / Staking as trend lines —
              <span id="finPeriodName">Months</span></small>
          </div>
          <select class="fin-period" id="finPeriod" aria-label="Period">
            <option value="daily">Days</option>
            <option value="monthly" selected>Months</option>
            <option value="yearly">Yearly</option>
          </select>
        </div>

        <div class="fin-tiles" id="finTiles">
          <div class="fin-tile is-loading"><div class="k"><i style="background:#94a3b8"></i>Active Users</div>
            <div class="v" id="finTileActive">0</div></div>
          <div class="fin-tile is-loading"><div class="k"><i style="background:#f59e0b"></i>Bonus Used</div>
            <div class="v" id="finTileBonus">0<small>BMAN</small></div></div>
          <div class="fin-tile is-loading"><div class="k"><i style="background:#8b5cf6"></i>Staking Done</div>
            <div class="v" id="finTileStaking">0</div></div>
          <div class="fin-tile is-loading"><div class="k"><i style="background:#2563eb"></i>Earning Coin</div>
            <div class="v" id="finTileEarning">0<small>BMAN</small></div></div>
          <div class="fin-tile is-loading"><div class="k"><i style="background:#22c55e"></i>Coin Withdrawal</div>
            <div class="v" id="finTileWithdraw">0<small>BMAN</small></div></div>
        </div>

        <div class="fin-chart-body"><canvas id="financeChart"></canvas></div>
      </div>

      <?php
      /**
       * The five wallets. Replaces the old Available Balance / Pending
       * Commission / Total Earned / Total Withdrawn cards, which had NO
       * javascript behind them and so permanently displayed 0.00.
       *
       * Balances come from Walletledger_model::balances() — the single source
       * of truth (it owns user_wallets + wallet_ledger and keeps them in step
       * inside one locked transaction). Rendered server-side: five numbers do
       * not need a round trip.
       */
      $wallets = $wallets ?? ['usdt'=>'0','exchange'=>'0','earning'=>'0','staking'=>'0','bonus'=>'0'];
      // Display standardized to 2dp for every wallet card (product decision —
      // full decimal(30,8) precision is still stored/used for calculations,
      // this only affects what's rendered here).
      $w_cards = [
        ['key'=>'usdt',     'label'=>'USDT Wallet',     'unit'=>'USDT', 'dp'=>2,
         'icon'=>'ph-currency-dollar',   'bg'=>'#ecfdf3', 'fg'=>'#059669'],
        ['key'=>'exchange', 'label'=>'Exchange Wallet', 'unit'=>'BMAN', 'dp'=>2,
         'icon'=>'ph-arrows-left-right', 'bg'=>'#eff6ff', 'fg'=>'#2563eb'],
        ['key'=>'earning',  'label'=>'Earning Wallet',  'unit'=>'BMAN', 'dp'=>2,
         'icon'=>'ph-trend-up',          'bg'=>'#f5f3ff', 'fg'=>'#7c3aed'],
        ['key'=>'staking',  'label'=>'Staking Wallet',  'unit'=>'BMAN', 'dp'=>2,
         'icon'=>'ph-lock-key',          'bg'=>'#fff7ed', 'fg'=>'#d97706'],
        ['key'=>'bonus',    'label'=>'Bonus Wallet',    'unit'=>'BMAN', 'dp'=>2,
         'icon'=>'ph-gift',              'bg'=>'#fdf2f8', 'fg'=>'#db2777'],
      ];
      ?>
      <style>
        /* Five across on desktop. The shared .kpi-grid is 4-up, so the wallet
           row gets its own grid rather than fighting that breakpoint. */
        .wallet-grid{ display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin:0 0 18px; }
        .wallet-card{
          display:flex; align-items:flex-start; gap:12px; padding:16px;
          background:var(--bs-body-bg,#fff); border:1px solid var(--bs-border-color,#eef0f4);
          border-radius:18px; box-shadow:0 8px 24px rgba(20,22,26,.05); min-width:0;
          transition:transform .15s ease, box-shadow .15s ease; text-decoration:none; color:inherit;
        }
        .wallet-card:hover{ transform:translateY(-2px); box-shadow:0 12px 30px rgba(20,22,26,.09); }
        .wallet-ico{ width:42px; height:42px; flex:0 0 42px; border-radius:13px; display:grid;
          place-items:center; font-size:20px; }
        .wallet-meta{ min-width:0; flex:1; }
        .wallet-meta small{ display:block; font-size:11.5px; font-weight:600;
          color:var(--bs-secondary-color,#8a8f99); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .wallet-meta strong{ display:block; font-size:19px; font-weight:800; margin:3px 0 2px;
          letter-spacing:-.4px; font-variant-numeric:tabular-nums; word-break:break-all; }
        .wallet-meta strong u{ text-decoration:none; font-size:11px; font-weight:600;
          color:var(--bs-secondary-color,#8a8f99); margin-left:3px; }
        .wallet-meta span{ font-size:10.5px; color:var(--bs-secondary-color,#8a8f99); }
        @media (max-width:1280px){ .wallet-grid{ grid-template-columns:repeat(3,1fr); } }
        @media (max-width:760px){  .wallet-grid{ grid-template-columns:repeat(2,1fr); } }
        @media (max-width:420px){  .wallet-grid{ grid-template-columns:1fr; } }
      </style>

      <!-- Wallets -->
      <div class="wallet-grid">
        <?php foreach ($w_cards as $wc):
          $bal = (float) ($wallets[$wc['key']] ?? 0);
          ?>
          <a class="wallet-card" href="<?= base_url('user/wallet'); ?>" title="<?= $wc['label']; ?>">
            <div class="wallet-ico" style="background:<?= $wc['bg']; ?>;color:<?= $wc['fg']; ?>">
              <i class="ph <?= $wc['icon']; ?>"></i>
            </div>
            <div class="wallet-meta">
              <small><?= $wc['label']; ?></small>
              <strong><?= number_format($bal, $wc['dp']); ?><u><?= $wc['unit']; ?></u></strong>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <a href="<?= base_url('user/withdraw'); ?>" class="qa"><i class="ph ph-money"></i> Withdraw</a>
        <a href="<?= base_url('user/transfer-wallet'); ?>" class="qa"><i class="ph ph-arrows-left-right"></i> Transfer Wallet</a>
        <a href="<?= base_url('user/binary-tree'); ?>" class="qa"><i class="ph ph-tree-structure"></i> View Binary Tree</a>
        <a href="<?= base_url('user/referrals'); ?>" class="qa"><i class="ph ph-user-plus"></i> Invite Member</a>
        <a href="<?= base_url('support'); ?>" class="qa"><i class="ph ph-headset"></i> Support Ticket</a>
      </div>

      <!-- Binary + Team -->
      <div class="two-col">

        <div class="panel">
          <div class="panel-title">
            <h3>Binary Summary</h3>
            <span class="chip">This Week</span>
          </div>

          <?php
            $legLeft = (int) ($leg_stakes['left_count'] ?? 0);
            $legRight = (int) ($leg_stakes['right_count'] ?? 0);
            $legLeftStrong = $legLeft >= $legRight;
          ?>
          <div class="binary-grid">
            <!-- Left -->
            <div class="mini">
              <div class="mini-top">
                <span>Left Leg</span>
                <b style="color:#2563eb;"><?= $legLeftStrong ? 'STRONG' : 'WEAK'; ?></b>
              </div>

              <div class="mini-value">
                <strong><?= $legLeft; ?></strong>
                <div style="font-size:11px;color:#8a8f99;font-weight:600;margin-top:2px;">Staking Purchases (BMAN)</div>
              </div>

            </div>

            <!-- Right -->
            <div class="mini">
              <div class="mini-top">
                <span>Right Leg</span>
                <b style="color:#f97316;"><?= $legLeftStrong ? 'WEAK' : 'STRONG'; ?></b>
              </div>

              <div class="mini-value">
                <strong><?= $legRight; ?></strong>
                <div style="font-size:11px;color:#8a8f99;font-weight:600;margin-top:2px;">Staking Purchases (BMAN)</div>
              </div>

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

            <?php
            // Achievement Rank / Rank Power / Group Volume / Next Rank Progress /
            // Group Incentive — replaces the old KYC/Bank/Account/Weak-Leg
            // checklist, which tracked pairing-engine gates that no longer exist.
            // Source: Memberrank_model::sidebar() (§10/§11), the same cheap,
            // cached-volume call the right-hand rank widget uses.
            $rs = $rank_summary ?? [];
            $nextLabel = !empty($rs['next_rank']) ? ' to ' . htmlspecialchars($rs['next_rank']) : '';
            ?>
            <div class="small-grid" style="margin-top:12px;">
              <div class="small-k">
                <small>Group Volume</small>
                <strong><?= number_format((float)($rs['group_volume'] ?? 0), 2); ?> BMAN</strong>
              </div>
              <div class="small-k">
                <small>Next Rank Progress</small>
                <strong><?= (int)($rs['progress'] ?? 0); ?>%<?= $nextLabel; ?></strong>
              </div>
              <div class="small-k">
                <small>Group Incentive Eligibility</small>
                <strong><?= !empty($rs['power_qualified']) ? 'Eligible' : 'Not Eligible'; ?></strong>
              </div>
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
              <strong><?= (int) ($team_snapshot['left_team'] ?? 0); ?></strong>
            </div>
            <div class="small-k">
              <small>Right Team</small>
              <strong><?= (int) ($team_snapshot['right_team'] ?? 0); ?></strong>
            </div>
            <div class="small-k">
              <small>Active Members</small>
              <strong><?= (int) ($team_snapshot['active_total'] ?? 0); ?></strong>
            </div>
            <div class="small-k">
              <small>New Joins</small>
              <strong><?= (int) ($team_snapshot['new_joins_week'] ?? 0); ?></strong>
            </div>
          </div>

          <div class="progress-wrap" style="margin-top:12px;">
            <div class="row">
              <b>Referral Link</b>
              <span style="font-size:11px;color:#5d56a8;font-weight:800;">Copy</span>
            </div>

            <div class="ref-leg-tabs" style="display:flex;gap:8px;margin-bottom:10px;">
              <button type="button" class="ref-tab active" data-side="left" onclick="selectDashRefTab('left')"
                style="flex:1;border:1px solid #eeecff;background:var(--primary);color:#fff;padding:8px 10px;border-radius:12px;font-weight:800;font-size:12px;cursor:pointer;">Left Leg</button>
              <button type="button" class="ref-tab" data-side="right" onclick="selectDashRefTab('right')"
                style="flex:1;border:1px solid #eeecff;background:#fff;color:#5d56a8;padding:8px 10px;border-radius:12px;font-weight:800;font-size:12px;cursor:pointer;">Right Leg</button>
            </div>

            <div style="display:flex;gap:10px;align-items:center;">
              <input id="referral_link"
                style="flex:1;border:none;outline:none;background:#fff;padding:10px 12px;border-radius:14px;border:1px solid #eeecff;font-size:12px;"
                value="<?php echo base_url() . 'user/re?ref=L-' . $userinfo->referral_id ?? ''; ?>" readonly />
              <button
                style="border:none;background:var(--primary);color:#fff;padding:10px 14px;border-radius:14px;font-weight:800;cursor:pointer;"
                onclick="copyText(dashRefSide)"><i class="ph ph-copy"></i></button>
            </div>

            <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap;">
              <button class="btn primary" style="padding:10px 14px;" onclick="shareLink(dashRefSide)">Invite <i
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
            <h3>Wallet Transaction</h3>
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
            <h3>Recent Staking Purchases</h3>
            <span class="chip">Staking</span>
          </div>

          <!-- AJAX will fill here -->
          <div id="recent_orders_list">
            <div class="row-item">
              <div class="left">
                <div class="bullet"><i class="ph ph-coins"></i></div>
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
            onclick="window.location.href='<?= base_url('user/stakings'); ?>'">View All Staking</button>
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

    // Swap state machine, not shipment states: the endpoint collapses
    // swap_completed / pending_* / failed_* / cancelled into these.
    function statusClass(status) {
      const s = (status || '').toLowerCase();
      if (s === 'completed') return 'success';
      if (s === 'processing') return 'ship';
      if (s === 'pending') return 'pending';
      if (s === 'failed' || s === 'cancelled') return 'failed';
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
              <div class="bullet"><i class="ph ph-coins"></i></div>
              <div class="txt">
                <b>No Staking Purchases</b>
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
              <div class="bullet"><i class="ph ph-coins"></i></div>
              <div class="txt">
                <b>No Staking Yet</b>
                <small>Buy a package to get started</small>
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
            // Staking purchase: package + plan/term on top, BMAN bought on the
            // right. The bonus is shown separately because it is NOT part of the
            // stake — it lands in the bonus wallet.
            const bonus = parseFloat(String(o.bonus_bman).replace(/,/g, '')) > 0
              ? ` • +${escapeHtml(o.bonus_bman)} bonus` : '';
            html += `
          <div class="row-item">
            <div class="left">
              <div class="bullet"><i class="ph ph-coins"></i></div>
              <div class="txt">
                <b>${escapeHtml(o.package)}</b>
                <small>${escapeHtml(o.order_date)} • ${escapeHtml(o.plan)} ${escapeHtml(o.term)}${bonus}</small>
              </div>
            </div>
            <div class="amount">
              ${escapeHtml(o.bman_amount)} ${symbol}
              <small><span class="status ${cls}">${escapeHtml((o.order_status || 'PENDING').toUpperCase())}</span></small>
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
            <div class="bullet"><i class="ph ph-coins"></i></div>
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
      if (t === 'binary_matching' || t === 'binary_commission') return 'ph-link';  // Binary matching bonus
      if (t === 'swap_bonus') return 'ph-gift';                // Instant 25% stake-purchase bonus
      if (t === 'level_commission') return 'ph-users-three';  // Matching/level
      if (t === 'direct_commission') return 'ph-user-plus';   // Direct bonus
      if (t === 'rank_reward' || t === 'rank') return 'ph-medal';
      if (t === 'profit' || t === 'roi') return 'ph-chart-line-up';
      return 'ph-receipt';
    }

    function commissionTitle(type) {
      const t = (type || '').toLowerCase();
      if (t === 'binary_matching' || t === 'binary_commission') return 'Binary Matching Bonus';
      if (t === 'swap_bonus') return 'Instant 25% Bonus (Stake Purchase)';
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
    // Left Leg / Right Leg tabs on the dashboard's Referral Link box — the
    // Copy/Invite buttons already read `dashRefSide` via getLink()/copyText()/
    // shareLink() below, so switching tabs just needs to update the state and
    // the visible link text.
    var dashRefSide = 'left';
    function selectDashRefTab(side) {
      dashRefSide = (side === 'right') ? 'right' : 'left';
      document.querySelectorAll('.ref-tab').forEach(function (btn) {
        var active = btn.dataset.side === dashRefSide;
        btn.classList.toggle('active', active);
        btn.style.background = active ? 'var(--primary)' : '#fff';
        btn.style.color = active ? '#fff' : '#5d56a8';
      });
      var input = document.getElementById('referral_link');
      if (input) input.value = getLink(dashRefSide);
    }

    function getLink(side) {
      const links = window.RefLinks || {};
      const url = (side === 'left') ? (links.left || '') : (links.right || '');
      return (url || '').trim();
    }

    async function copyText(side) {
      const url = getLink(side);
      if (!url) return toastMini("Link not available");

      if (navigator.clipboard && window.isSecureContext) {
        try {
          await navigator.clipboard.writeText(url);
          toastMini("Copied!");
          return;
        } catch (e) { /* fall through to legacy fallback below */ }
      }

      // Legacy fallback — also covers plain-http/non-secure contexts, where
      // navigator.clipboard can be entirely undefined.
      const tmp = document.createElement('textarea');
      tmp.value = url;
      tmp.style.position = 'fixed';
      tmp.style.opacity = '0';
      document.body.appendChild(tmp);
      tmp.select();
      const ok = document.execCommand('copy');
      tmp.remove();
      toastMini(ok ? "Copied!" : "Couldn't copy — please copy the link manually");
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
        } catch (e) { /* user cancelled the native share sheet — not an error */ }
        return;
      }

      // No Web Share API (e.g. desktop browsers) — copy the link instead.
      // copyText() shows its own success/failure toast.
      await copyText(side);
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

  <!-- User Activity & Coin Trend — LIVE from user/activityTrendAjax.
       Was reading assets/user_v2/data/dashboard_chart.json, which described
       itself as "Dummy dashboard finance data". Chart.js 4.4.1 and the
       day/month/year filter are unchanged; only the data source and the series. -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
  (function () {
    var canvas = document.getElementById('financeChart');
    if (!canvas || typeof Chart === 'undefined') return;

    var ENDPOINT = '<?php echo base_url('user/activityTrendAjax'); ?>';
    var chart = null, range = 'monthly';
    // Each range is fetched once and kept — changing the dropdown after the
    // first visit is instant and costs no further requests.
    var CACHE = {}, inflight = {};

    // Fixed palette per the reference design. Blue = coin IN, green = coin OUT
    // — the colours ARE the spec here, which is why they don't derive from the
    // panel theme tokens.
    var C = {
      blue:   '#2563eb',  // earning coin (in)
      green:  '#22c55e',  // coin withdrawal (out)
      amber:  '#f59e0b',  // bonus used
      purple: '#8b5cf6',  // staking done
      grey:   '#94a3b8'   // active users
    };
    var PERIOD_NAME = { daily: 'Days', monthly: 'Months', yearly: 'Yearly' };

    function fmt(v){ v = Number(v) || 0; return v >= 1000 ? (v/1000) + 'K' : v; }
    function num(v){ return Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 2 }); }

    function setTiles(s, loading){
      document.querySelectorAll('.fin-tile').forEach(function(t){
        t.classList.toggle('is-loading', !!loading);
      });
      if (!s) return;
      document.getElementById('finTileActive').textContent   = num(s.active_users);
      document.getElementById('finTileStaking').textContent  = num(s.staking_done);
      document.getElementById('finTileBonus').innerHTML      = num(s.bonus_used)      + '<small>BMAN</small>';
      document.getElementById('finTileEarning').innerHTML    = num(s.earning_coin)    + '<small>BMAN</small>';
      document.getElementById('finTileWithdraw').innerHTML   = num(s.coin_withdrawal) + '<small>BMAN</small>';
    }

    function render(d){
      var labels = d.points.map(function(p){ return p.date; });
      var pick = function(k){ return d.points.map(function(p){ return p[k]; }); };

      document.getElementById('finPeriodName').textContent = PERIOD_NAME[d.range] || d.label;
      setTiles(d.summary, false);

      var cfg = {
        data: { labels: labels, datasets: [
          // Grouped bars: blue = earning (in), green = withdrawal (out).
          { type:'bar', label:'Earning Coin', data:pick('earning_coin'),
            backgroundColor:'rgba(37,99,235,.85)', borderRadius:6, order:3, yAxisID:'y' },
          { type:'bar', label:'Coin Withdrawal', data:pick('coin_withdrawal'),
            backgroundColor:'rgba(34,197,94,.85)', borderRadius:6, order:3, yAxisID:'y' },
          // Trend lines. Bonus is a BMAN amount so it shares the left axis;
          // Staking/Active are counts and ride the right axis, otherwise the
          // coin amounts (orders of magnitude larger) flatten them to nothing.
          { type:'line', label:'Bonus Used', data:pick('bonus_used'),
            borderColor:C.amber, backgroundColor:C.amber, tension:.4, borderWidth:3,
            pointRadius:3, pointBackgroundColor:C.amber, fill:false, order:1, yAxisID:'y' },
          { type:'line', label:'Staking Done', data:pick('staking_done'), hidden:true,
            borderColor:C.purple, backgroundColor:C.purple, tension:.4, borderWidth:2,
            pointRadius:2, fill:false, order:1, yAxisID:'y1' },
          { type:'line', label:'Active Users', data:pick('active_users'), hidden:true,
            borderColor:C.grey, backgroundColor:C.grey, tension:.4, borderWidth:2,
            borderDash:[5,4], pointRadius:2, fill:false, order:1, yAxisID:'y1' }
        ]},
        options: {
          responsive:true, maintainAspectRatio:false,
          interaction:{ mode:'index', intersect:false },
          plugins:{
            legend:{ position:'top', labels:{ usePointStyle:true, boxWidth:8, padding:16, font:{ size:12 } } },
            tooltip:{ callbacks:{ label:function(c){ return ' ' + c.dataset.label + ': ' + num(c.parsed.y); } } }
          },
          scales:{
            x:{ grid:{ display:false }, ticks:{ font:{ size:11 } } },
            y:{  beginAtZero:true, position:'left',  grid:{ color:'#eef2f7' },
                 ticks:{ callback:function(v){ return fmt(v); } } },
            y1:{ beginAtZero:true, position:'right', grid:{ display:false },
                 ticks:{ precision:0, callback:function(v){ return fmt(v); } } }
          }
        }
      };
      if (chart) { chart.data = cfg.data; chart.options = cfg.options; chart.update(); }
      else { chart = new Chart(canvas.getContext('2d'), cfg); }
    }

    function fail(msg){
      document.querySelector('.fin-chart-body').innerHTML =
        '<div style="padding:40px;text-align:center;color:#64748b">' + msg + '</div>';
      setTiles(null, false);
    }

    var sel = document.getElementById('finPeriod');

    function load(r){
      if (CACHE[r]) { render(CACHE[r]); return; }
      if (inflight[r]) return;              // don't stack requests on fast changes
      inflight[r] = true;
      setTiles(null, true);
      if (sel) sel.disabled = true;

      fetch(ENDPOINT + '?range=' + encodeURIComponent(r), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin'
      })
        .then(function(res){ return res.json(); })
        .then(function(j){
          inflight[r] = false;
          if (sel) sel.disabled = false;
          if (!j || !j.ok) { fail((j && j.message) || 'Chart data could not be loaded.'); return; }
          CACHE[r] = j;
          if (range === r) render(j);       // ignore a stale response for an old range
        })
        .catch(function(){
          inflight[r] = false;
          if (sel) sel.disabled = false;
          fail('Chart data could not be loaded.');
        });
    }

    if (sel) sel.addEventListener('change', function(e){ range = e.target.value; load(range); });

    load(range);
  })();
  </script>
  <style>
    .quick-actions .qa{  text-decoration: none !important;}
  </style>

</body>

</html>
