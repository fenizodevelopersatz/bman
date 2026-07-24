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

    .binary-period {
      display: flex;
      gap: 6px;
      padding: 4px;
      border-radius: 999px;
      background: #f3f0ff;
    }

    .binary-period button {
      border: 0;
      border-radius: 999px;
      background: transparent;
      color: #5d56a8;
      cursor: pointer;
      font-size: 11px;
      font-weight: 800;
      padding: 6px 10px;
      transition: .18s ease;
    }

    .binary-period button.active {
      background: #fff;
      color: var(--primary);
      box-shadow: 0 6px 14px rgba(93, 86, 168, .12);
    }

    .binary-period button:disabled {
      cursor: wait;
      opacity: .65;
    }

    .binary-summary-loading .mini {
      opacity: .65;
    }

    body.share-modal-open {
      overflow: hidden;
    }

    .dash-share-backdrop {
      position: fixed;
      inset: 0;
      z-index: 100000;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 18px;
      background: rgba(15, 18, 32, .42);
      backdrop-filter: blur(8px);
    }

    .dash-share-backdrop.show {
      display: flex;
    }

    .dash-share-modal {
      width: min(440px, 100%);
      border-radius: 26px;
      background: #fff;
      box-shadow: 0 28px 80px rgba(26, 24, 64, .28);
      overflow: hidden;
      animation: dashShareIn .18s ease both;
    }

    @keyframes dashShareIn {
      from {
        opacity: 0;
        transform: translateY(12px) scale(.98);
      }

      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .dash-share-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 18px 20px;
      color: #fff;
      background: linear-gradient(135deg, #6c4cf1, #4e37d8);
    }

    .dash-share-head b {
      display: block;
      font-size: 16px;
      font-weight: 1000;
    }

    .dash-share-head small {
      display: block;
      margin-top: 3px;
      color: rgba(255, 255, 255, .76);
      font-size: 11px;
      font-weight: 700;
    }

    .dash-share-close {
      width: 36px;
      height: 36px;
      border: 0;
      border-radius: 14px;
      color: #fff;
      background: rgba(255, 255, 255, .16);
      cursor: pointer;
      display: grid;
      place-items: center;
    }

    .dash-share-body {
      padding: 18px;
    }

    .dash-share-tabs {
      display: flex;
      gap: 8px;
      padding: 5px;
      border-radius: 16px;
      background: #f3f0ff;
      margin-bottom: 14px;
    }

    .dash-share-tabs button {
      flex: 1;
      border: 0;
      border-radius: 12px;
      background: transparent;
      color: #5d56a8;
      cursor: pointer;
      font-size: 12px;
      font-weight: 900;
      padding: 10px;
    }

    .dash-share-tabs button.active {
      background: #fff;
      color: var(--primary);
      box-shadow: 0 8px 18px rgba(93, 86, 168, .12);
    }

    .dash-share-link {
      display: flex;
      gap: 10px;
      align-items: center;
      padding: 10px;
      border: 1px solid #eeecff;
      border-radius: 18px;
      background: #fbfaff;
    }

    .dash-share-link input {
      min-width: 0;
      flex: 1;
      border: 0;
      outline: none;
      color: #111827;
      background: transparent;
      font-size: 12px;
      font-weight: 700;
    }

    .dash-share-link button,
    .dash-share-actions button {
      border: 0;
      border-radius: 14px;
      cursor: pointer;
      font-size: 12px;
      font-weight: 900;
    }

    .dash-share-link button {
      width: 42px;
      height: 42px;
      color: #fff;
      background: var(--primary);
      display: grid;
      place-items: center;
    }

    .dash-share-qr {
      display: grid;
      grid-template-columns: 116px minmax(0, 1fr);
      gap: 14px;
      align-items: center;
      margin-top: 14px;
      padding: 12px;
      border: 1px dashed #dedafc;
      border-radius: 18px;
      background: linear-gradient(135deg, #ffffff 0%, #f8f6ff 100%);
    }

    .dash-share-qr-box {
      width: 104px;
      height: 104px;
      display: grid;
      place-items: center;
      padding: 8px;
      border-radius: 16px;
      background: #fff;
      box-shadow: 0 12px 30px rgba(93, 86, 168, .10);
    }

    .dash-share-qr-box canvas,
    .dash-share-qr-box img {
      width: 88px !important;
      height: 88px !important;
      display: block;
    }

    .dash-share-qr-text b {
      display: block;
      color: #111827;
      font-size: 13px;
      font-weight: 1000;
    }

    .dash-share-qr-text small {
      display: block;
      margin-top: 4px;
      color: #8a8f99;
      font-size: 11px;
      font-weight: 700;
      line-height: 1.45;
    }

    .dash-share-note {
      margin: 12px 0 0;
      color: #8a8f99;
      font-size: 11px;
      font-weight: 700;
      line-height: 1.5;
    }

    .dash-share-actions {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-top: 16px;
    }

    .dash-share-actions button {
      padding: 12px 10px;
      color: #111827;
      background: #f7f6ff;
    }

    .dash-share-actions button.primary {
      color: #fff;
      background: #111;
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

      .binary-period {
        width: 100%;
      }

      .binary-period button {
        flex: 1;
      }

      .dash-share-actions {
        grid-template-columns: 1fr;
      }

      .dash-share-qr {
        grid-template-columns: 1fr;
        justify-items: center;
        text-align: center;
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
      <?php $alertGradient = 'linear-gradient(135deg,#ef4444,#b91c1c)'; ?>
      <style>
        /* Self-contained announcement banner + fade rotator — deliberately NOT
           a Bootstrap carousel. Bootstrap's carousel JS (loaded globally via
           plugins.bundle.js) kept leaving the active fade-slide at opacity 0 on
           this page, rendering the whole banner blank. This owns its rotation
           with a tiny setInterval and plain opacity classes, so there is no
           dependency on Bootstrap's carousel state machine. Each slide still
           owns its full background/image/scrim/text and animates atomically. */
        .ann-banner{ position:relative; border-radius:22px; overflow:hidden; margin-bottom:22px;
          border:1px solid #eceafe; box-shadow:0 12px 34px rgba(76,60,241,.10); }
        .ann-rotator{ position:relative; min-height:236px; }
        .ann-slide-wrap{ position:absolute; inset:0; opacity:0; visibility:hidden;
          transition:opacity .6s ease; pointer-events:none; }
        .ann-slide-wrap.is-active{ opacity:1; visibility:visible; pointer-events:auto; }

        .ann-slide{ position:absolute; inset:0; display:flex; overflow:hidden;
          background:linear-gradient(120deg,#6C4CF1 0%,#4E2CF0 100%); }
        .ann-slide__img{ position:absolute; inset:0; width:100%; height:100%; object-fit:cover; z-index:0; }
        .ann-slide__scrim{ position:absolute; inset:0; z-index:1;
          background:linear-gradient(90deg, rgba(11,15,26,.82) 0%, rgba(11,15,26,.46) 48%, rgba(11,15,26,.05) 100%); }
        .ann-slide.pos-center .ann-slide__scrim{
          background:linear-gradient(180deg, rgba(11,15,26,.30) 0%, rgba(11,15,26,.62) 100%); }
        .ann-slide__content{ position:relative; z-index:2; display:flex; flex-direction:column;
          justify-content:center; gap:9px; padding:32px 42px; max-width:640px; width:100%; }
        .ann-slide.pos-top-left .ann-slide__content{ justify-content:flex-start; }
        .ann-slide.pos-bottom-left .ann-slide__content{ justify-content:flex-end; }
        .ann-slide.pos-center .ann-slide__content{ justify-content:center; align-items:center;
          text-align:center; max-width:760px; margin:0 auto; }
        .ann-slide.pos-center .ann-slide__tag{ margin-left:auto; margin-right:auto; }

        .ann-slide__tag{ display:inline-flex; align-items:center; gap:7px; width:fit-content;
          background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.28);
          padding:5px 12px; border-radius:999px; font-size:11px; font-weight:800;
          letter-spacing:.03em; text-transform:uppercase; margin-bottom:2px; }
        .ann-slide__alert{ font-size:12px; font-weight:900; letter-spacing:.06em; margin-bottom:2px; }
        .ann-slide__title{ margin:0; font-size:27px; line-height:1.16; font-weight:900; letter-spacing:-.5px; }
        .ann-slide__subtitle{ margin:0; font-size:14px; font-weight:800; opacity:.92; }
        .ann-slide__desc{ margin:2px 0 0; font-size:13px; line-height:1.55; opacity:.9; max-width:560px; }
        .ann-slide__btn{ display:inline-flex; align-items:center; gap:8px; width:fit-content; margin-top:12px;
          padding:11px 20px; border-radius:999px; font-size:13px; font-weight:900; background:#111827;
          color:#fff; text-decoration:none; transition:transform .15s ease, box-shadow .15s ease; }
        .ann-slide__btn:hover{ transform:translateY(-1px); box-shadow:0 8px 22px rgba(0,0,0,.28); color:#fff; }

        .ann-dots{ position:absolute; bottom:14px; left:50%; transform:translateX(-50%);
          display:flex; gap:7px; z-index:3; }
        .ann-dots button{ width:8px; height:8px; padding:0; border:0; border-radius:999px;
          background:rgba(255,255,255,.5); cursor:pointer; transition:.2s; }
        .ann-dots button.active{ width:22px; background:#fff; }

        .ann-empty{ min-height:150px; display:flex; align-items:center; padding:30px 42px;
          background:linear-gradient(120deg,#6C4CF1,#4E2CF0); color:#fff; }
        .ann-empty h2{ margin:0; font-size:19px; font-weight:800; opacity:.94; }

        @media (max-width:640px){
          .ann-rotator{ min-height:200px; }
          .ann-slide__content{ padding:22px 22px; max-width:100%; }
          .ann-slide__title{ font-size:20px; }
          .ann-slide__desc{ font-size:12.5px; }
        }
      </style>

      <?php if (!empty($notification)): /* No active announcements => render nothing at all (no empty placeholder banner). */ ?>
      <div class="ann-banner">
        <div class="ann-rotator" id="annRotator">
          <?php $first = true;
          foreach ($notification as $note):
            $type = $note->announcement_type ?? 'text';
            $showFullText = in_array($type, ['text', 'text_image'], true);
            $imageOnly = ($type === 'image');
            $hasRealImage = in_array($type, ['image', 'text_image'], true) && !empty($note->image);
            $isAlert = in_array($note->category ?? 'general', ['alert', 'maintenance'], true);
            $img = $hasRealImage ? base_url($note->image) : '';
            $textColor = htmlspecialchars($note->text_color ?: '#ffffff');
            $textPos = in_array($note->text_position ?? 'middle-left', ['top-left', 'bottom-left', 'center'], true) ? $note->text_position : 'middle-left';
            // Alert => red gradient. No image => bg_color / gradient. With image => image itself is the background.
            $slideBg = $isAlert ? $alertGradient : ($hasRealImage ? '' : ($note->bg_color ?: 'linear-gradient(120deg,#6C4CF1,#4E2CF0)'));
            $catLabel = ucfirst(str_replace('_', ' ', $note->category ?? 'general'));
            $slideClasses = 'pos-' . $textPos;
            if ($imageOnly)  $slideClasses .= ' is-image-only';
            if ($isAlert)    $slideClasses .= ' is-alert';
          ?>
            <div class="ann-slide-wrap <?= $first ? 'is-active' : '' ?>" data-id="<?= (int) $note->id ?>">
              <div class="ann-slide <?= $slideClasses ?>"<?= $slideBg ? ' style="background:' . htmlspecialchars($slideBg) . ';"' : '' ?>>
                <?php if ($hasRealImage): ?>
                  <img class="ann-slide__img" src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($note->title ?: 'Announcement') ?>">
                  <?php if (!$imageOnly): ?><div class="ann-slide__scrim"></div><?php endif; ?>
                <?php endif; ?>

                <?php if (!$imageOnly): ?>
                <div class="ann-slide__content" style="color:<?= $textColor ?>;">
                  <?php if ($isAlert): ?>
                    <div class="ann-slide__alert">⚠ <?= strtoupper(htmlspecialchars($catLabel)) ?></div>
                  <?php else: ?>
                    <span class="ann-slide__tag"><i class="ph ph-megaphone"></i> <?= htmlspecialchars($catLabel) ?></span>
                  <?php endif; ?>
                  <?php if (!empty($note->title)): ?>
                    <h2 class="ann-slide__title"><?= htmlspecialchars($note->title) ?></h2>
                  <?php endif; ?>
                  <?php if ($showFullText && !empty($note->subtitle)): ?>
                    <p class="ann-slide__subtitle"><?= htmlspecialchars($note->subtitle) ?></p>
                  <?php endif; ?>
                  <?php if ($showFullText && !empty($note->description)): ?>
                    <p class="ann-slide__desc"><?= nl2br(htmlspecialchars($note->description)) ?></p>
                  <?php endif; ?>
                  <?php if (!empty($note->button_text) && !empty($note->button_url)): ?>
                    <a href="<?= htmlspecialchars($note->button_url) ?>" class="ann-slide__btn announcement-cta" data-id="<?= (int) $note->id ?>">
                      <?= htmlspecialchars($note->button_text) ?> <i class="ph ph-arrow-right"></i>
                    </a>
                  <?php endif; ?>
                </div>
                <?php endif; ?>
              </div>
            </div>
            <?php $first = false; endforeach; ?>

          <?php if (count($notification) > 1): ?>
          <div class="ann-dots">
            <?php for ($i = 0; $i < count($notification); $i++): ?>
              <button type="button" data-ann-idx="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" aria-label="Slide <?= $i + 1 ?>"></button>
            <?php endfor; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($popup_announcement)): $pa = $popup_announcement; ?>
      <!-- <div class="modal fade" id="announcementPopup" tabindex="-1" aria-hidden="true">
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
      </div> -->
      <?php endif; ?>

      <script>
        (function () {
          var base = '<?php echo base_url(); ?>';
          function beacon(path) {
            fetch(base + path, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, keepalive: true }).catch(function () {});
          }

          <?php if (!empty($notification)): ?>
          // Own fade rotator — no Bootstrap carousel (its JS kept blanking the
          // active slide). Slides stack absolutely; we toggle .is-active for a
          // CSS opacity crossfade and advance on a timer.
          var annRot = document.getElementById('annRotator');
          var annSlides = annRot ? annRot.querySelectorAll('.ann-slide-wrap') : [];
          var annDots = annRot ? annRot.querySelectorAll('.ann-dots button') : [];
          var annSeen = {}, annCur = 0, annTimer = null;
          function annTrackView(el) {
            if (!el) return;
            var id = el.getAttribute('data-id');
            if (!id || annSeen[id]) return;
            annSeen[id] = true;
            beacon('user/announcement/view/' + id);
          }
          function annShow(n) {
            if (!annSlides.length) return;
            n = (n + annSlides.length) % annSlides.length;
            annSlides.forEach(function (s, i) { s.classList.toggle('is-active', i === n); });
            annDots.forEach(function (d, i) { d.classList.toggle('active', i === n); });
            annCur = n;
            annTrackView(annSlides[n]);
          }
          function annStart() { if (annSlides.length > 1) { annStop(); annTimer = setInterval(function () { annShow(annCur + 1); }, 6000); } }
          function annStop() { if (annTimer) { clearInterval(annTimer); annTimer = null; } }
          annDots.forEach(function (d, i) { d.addEventListener('click', function () { annShow(i); annStart(); }); });
          if (annRot) { annRot.addEventListener('mouseenter', annStop); annRot.addEventListener('mouseleave', annStart); }
          annTrackView(annSlides[0]);
          annStart();
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

        <div class="panel" id="binarySummaryPanel">
          <div class="panel-title">
            <h3>Binary Summary</h3>
            <div class="binary-period" id="binaryPeriodFilter" role="group" aria-label="Binary summary period">
              <button class="active" type="button" data-period="week">This Week</button>
              <button type="button" data-period="month">This Month</button>
            </div>
          </div>

          <?php
            $legLeft = (float) ($leg_investments['left_bman'] ?? 0);
            $legRight = (float) ($leg_investments['right_bman'] ?? 0);
            $legLeftStrong = $legLeft >= $legRight;
            $legProgress = ($legLeft + $legRight) > 0
              ? round((min($legLeft, $legRight) / ($legLeft + $legRight)) * 100, 2)
              : 0;
            $legProgressText = rtrim(rtrim(number_format($legProgress, 2), '0'), '.') . '%';
          ?>
          <div class="binary-grid">
            <!-- Left -->
            <div class="mini">
              <div class="mini-top">
                <span>Left Leg</span>
                <b id="binaryLeftStrength" style="color:#2563eb;"><?= $legLeftStrong ? 'STRONG' : 'WEAK'; ?></b>
              </div>

              <div class="mini-value">
                <strong id="binaryLeftAmount"><?= number_format($legLeft, 2); ?></strong>
                <div style="font-size:11px;color:#8a8f99;font-weight:600;margin-top:2px;">Leg Investment (BMAN)</div>
              </div>

            </div>

            <!-- Right -->
            <div class="mini">
              <div class="mini-top">
                <span>Right Leg</span>
                <b id="binaryRightStrength" style="color:#f97316;"><?= $legLeftStrong ? 'WEAK' : 'STRONG'; ?></b>
              </div>

              <div class="mini-value">
                <strong id="binaryRightAmount"><?= number_format($legRight, 2); ?></strong>
                <div style="font-size:11px;color:#8a8f99;font-weight:600;margin-top:2px;">Leg Investment (BMAN)</div>
              </div>

            </div>

          </div>

          <div class="progress-wrap">
            <div class="row">
              <b id="binaryProgressTitle">Weekly Pair Target Progress</b>
              <span id="weekly_progress" style="font-size:12px;color:#5d56a8;font-weight:800;"><?= $legProgressText; ?></span>
            </div>

            <div class="bar">
              <div id="weekly_progress_bar" style="width:<?= min(100, max(0, $legProgress)); ?>%"></div>
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

      <!-- Wallet Transactions + Recent Orders -->
      <div class="lists">
        <div class="list">
          <div class="panel-title" style="margin-bottom:10px;">
            <h3>Wallet Transaction</h3>
            <span class="chip">Latest</span>
          </div>

          <!-- AJAX will fill here -->
          <div id="recent_wallet_transactions_list">
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

          <button id="btn_view_all_wallet_transactions" class="btn-full"
            onclick="window.location.href='<?= base_url('user/wallet'); ?>'">
            View All Wallet Transactions
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

  <div class="dash-share-backdrop" id="dashShareModal" aria-hidden="true">
    <div class="dash-share-modal" role="dialog" aria-modal="true" aria-labelledby="dashShareTitle">
      <div class="dash-share-head">
        <div>
          <b id="dashShareTitle">Invite Member</b>
          <small id="dashShareSubtitle">Share your selected referral leg</small>
        </div>
        <button class="dash-share-close" type="button" onclick="closeDashSharePopup()" aria-label="Close share popup">
          <i class="ph ph-x"></i>
        </button>
      </div>
      <div class="dash-share-body">
        <div class="dash-share-tabs" role="group" aria-label="Referral leg">
          <button type="button" id="dashShareLeftTab" class="active" onclick="setDashSharePopupSide('left')">Left Leg</button>
          <button type="button" id="dashShareRightTab" onclick="setDashSharePopupSide('right')">Right Leg</button>
        </div>

        <div class="dash-share-link">
          <input id="dashShareLink" type="text" readonly value="">
          <button type="button" onclick="copyText(dashRefSide)" title="Copy referral link">
            <i class="ph ph-copy"></i>
          </button>
        </div>

        <div class="dash-share-qr">
          <div class="dash-share-qr-box" id="dashShareQr" aria-label="Referral QR code"></div>
          <div class="dash-share-qr-text">
            <b>Scan QR to join</b>
            <small id="dashShareQrLabel">This QR updates automatically for the selected referral leg.</small>
          </div>
        </div>

        <p class="dash-share-note">
          Send this link to invite a new member into the selected leg. Use Copy Link first, or open the link to preview it.
        </p>

        <div class="dash-share-actions">
          <button class="primary" type="button" onclick="copyText(dashRefSide)">
            <i class="ph ph-copy"></i> Copy Link
          </button>
          <button type="button" onclick="openLink(dashRefSide)">
            <i class="ph ph-arrow-square-out"></i> Open Link
          </button>
        </div>
      </div>
    </div>
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
  <script src="<?php echo base_url('assets/js/vendor/qrcode.min.js'); ?>"></script>
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

    function walletTransactionIcon(tx) {
      const title = String((tx && tx.title) || '').toLowerCase();
      const flow = String((tx && tx.flow) || '').toUpperCase();

      if (flow === 'DEBIT') return 'ph-arrow-circle-up';
      if (title.includes('deposit')) return 'ph-arrow-circle-down';
      if (title.includes('transfer')) return 'ph-arrows-left-right';
      if (title.includes('bonus')) return 'ph-gift';
      if (title.includes('roi') || title.includes('earn')) return 'ph-trend-up';
      return 'ph-wallet';
    }

    function walletFlowClass(flow) {
      return String(flow || '').toUpperCase() === 'DEBIT' ? 'ship' : 'success';
    }

    function walletFlowLabel(flow) {
      return String(flow || '').toUpperCase() === 'DEBIT' ? 'OUTGOING' : 'INCOMING';
    }

    function loadRecentWalletTransactions() {
      $.ajax({
        url: "<?= base_url('user/recentWalletTransactionsAjax'); ?>",
        type: "GET",
        dataType: "json",
        data: { limit: 5 },
        success: function (res) {
          $('#btn_view_all_wallet_transactions').hide();

          if (!res || res.status !== true) {
            $('#recent_wallet_transactions_list').html(`
          <div class="row-item">
            <div class="left">
              <div class="bullet"><i class="ph ph-warning"></i></div>
              <div class="txt">
                <b>No Wallet Transactions</b>
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

          const list = res.transactions || [];
          if (list.length === 0) {
            $('#recent_wallet_transactions_list').html(`
          <div class="row-item">
            <div class="left">
              <div class="bullet"><i class="ph ph-wallet"></i></div>
              <div class="txt">
                <b>No Wallet Transactions</b>
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

          $('#btn_view_all_wallet_transactions').show();
          let html = '';

          list.forEach(tx => {
            const icon = walletTransactionIcon(tx);
            const flowLabel = walletFlowLabel(tx.flow);
            const flowClass = walletFlowClass(tx.flow);
            const peer = tx.address_short ? `${flowLabel === 'INCOMING' ? 'From' : 'To'}: ${escapeHtml(tx.address_short)}` : '';
            const meta = `${escapeHtml(tx.date_text || '')}${tx.tx_short ? ' - TX: ' + escapeHtml(tx.tx_short) : ''}${peer ? ' - ' + peer : ''}`;

            html += `
          <div class="row-item">
            <div class="left">
              <div class="bullet"><i class="ph ${escapeHtml(icon)}"></i></div>
              <div class="txt">
                <b>${escapeHtml(tx.title || 'Wallet Transaction')}</b>
                <small>${meta}</small>
              </div>
            </div>
            <div class="amount">
              ${escapeHtml(tx.token_symbol || 'USDT')} ${escapeHtml(tx.amount || '0.00')}
              <small><span class="status ${flowClass}">${escapeHtml(flowLabel)}</span></small>
            </div>
          </div>
        `;
          });

          $('#recent_wallet_transactions_list').html(html);
        },
        error: function () {
          $('#btn_view_all_wallet_transactions').hide();
          $('#recent_wallet_transactions_list').html(`
        <div class="row-item">
          <div class="left">
            <div class="bullet"><i class="ph ph-warning"></i></div>
            <div class="txt">
              <b>Failed to load</b>
              <small>Check wallet API</small>
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
      loadRecentWalletTransactions();
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

      var modal = document.getElementById('dashShareModal');
      if (modal && modal.classList.contains('show')) {
        renderDashSharePopup();
      }
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

    function renderReferralQr(container, url, size) {
      if (!container) return;
      container.innerHTML = '';

      if (!url) {
        container.innerHTML = '<i class="ph ph-qr-code" aria-hidden="true"></i>';
        return;
      }

      if (typeof QRCode !== 'undefined') {
        new QRCode(container, {
          text: url,
          width: size,
          height: size,
          colorDark: '#111827',
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel ? QRCode.CorrectLevel.H : 2
        });
        return;
      }

      const img = document.createElement('img');
      img.alt = 'Referral QR code';
      img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=' + size + 'x' + size + '&data=' + encodeURIComponent(url);
      container.appendChild(img);
    }

    function renderDashSharePopup() {
      const url = getLink(dashRefSide);
      const input = document.getElementById('dashShareLink');
      const subtitle = document.getElementById('dashShareSubtitle');
      const qr = document.getElementById('dashShareQr');
      const qrLabel = document.getElementById('dashShareQrLabel');
      const leftTab = document.getElementById('dashShareLeftTab');
      const rightTab = document.getElementById('dashShareRightTab');
      const isRight = dashRefSide === 'right';

      if (input) input.value = url;
      if (subtitle) subtitle.textContent = 'Sharing ' + (isRight ? 'Right Leg' : 'Left Leg') + ' referral link';
      if (qrLabel) qrLabel.textContent = 'QR for ' + (isRight ? 'Right Leg' : 'Left Leg') + ' referral link.';
      renderReferralQr(qr, url, 88);
      if (leftTab) leftTab.classList.toggle('active', !isRight);
      if (rightTab) rightTab.classList.toggle('active', isRight);
    }

    function setDashSharePopupSide(side) {
      selectDashRefTab(side);
      renderDashSharePopup();
    }

    function openDashSharePopup(side) {
      const modal = document.getElementById('dashShareModal');
      if (!modal) return;

      selectDashRefTab(side);
      renderDashSharePopup();
      modal.classList.add('show');
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('share-modal-open');

      const input = document.getElementById('dashShareLink');
      if (input) {
        setTimeout(function () { input.focus(); input.select(); }, 50);
      }
    }

    function closeDashSharePopup() {
      const modal = document.getElementById('dashShareModal');
      if (!modal) return;

      modal.classList.remove('show');
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('share-modal-open');
    }

    function initDashSharePopup() {
      const modal = document.getElementById('dashShareModal');
      if (!modal) return;

      modal.addEventListener('click', function (event) {
        if (event.target === modal) {
          closeDashSharePopup();
        }
      });

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('show')) {
          closeDashSharePopup();
        }
      });
    }

    function shareLink(side) {
      const url = getLink(side);
      if (!url) return toastMini("Link not available");
      openDashSharePopup(side);
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

    function binaryToast(msg) {
      if (typeof toastMini === 'function') {
        toastMini(msg);
      }
    }

    function formatBmanAmount(value) {
      return Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    }

    function setBinarySummaryLoading(loading) {
      const panel = document.getElementById('binarySummaryPanel');
      if (panel) panel.classList.toggle('binary-summary-loading', !!loading);

      document.querySelectorAll('#binaryPeriodFilter button').forEach(function (btn) {
        btn.disabled = !!loading;
      });
    }

    function renderBinarySummary(data) {
      if (!data || data.status !== true) return;

      const leftAmount = document.getElementById('binaryLeftAmount');
      const rightAmount = document.getElementById('binaryRightAmount');
      const leftStrength = document.getElementById('binaryLeftStrength');
      const rightStrength = document.getElementById('binaryRightStrength');
      const progressText = document.getElementById('weekly_progress');
      const progressBar = document.getElementById('weekly_progress_bar');
      const progressTitle = document.getElementById('binaryProgressTitle');
      const progress = Math.max(0, Math.min(100, Number(data.progress || 0)));

      if (leftAmount) leftAmount.textContent = data.left_bman_text || formatBmanAmount(data.left_bman);
      if (rightAmount) rightAmount.textContent = data.right_bman_text || formatBmanAmount(data.right_bman);
      if (leftStrength) leftStrength.textContent = data.left_strength || 'WEAK';
      if (rightStrength) rightStrength.textContent = data.right_strength || 'WEAK';
      if (progressTitle) progressTitle.textContent = data.progress_title || 'Pair Target Progress';
      if (progressText) progressText.textContent = data.progress_text || '0%';
      if (progressBar) progressBar.style.width = progress + '%';
    }

    function loadBinarySummary(period) {
      period = period === 'month' ? 'month' : 'week';
      setBinarySummaryLoading(true);

      fetch("<?= base_url('user/binarySummaryAjax'); ?>?period=" + encodeURIComponent(period), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (data) {
          setBinarySummaryLoading(false);

          if (!data || data.status !== true) {
            binaryToast((data && data.message) || 'Binary summary could not be loaded.');
            return;
          }

          document.querySelectorAll('#binaryPeriodFilter button').forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.period === period);
          });
          renderBinarySummary(data);
        })
        .catch(function () {
          setBinarySummaryLoading(false);
          binaryToast('Binary summary could not be loaded.');
        });
    }

    function initBinarySummaryFilter() {
      document.querySelectorAll('#binaryPeriodFilter button').forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (btn.classList.contains('active')) return;
          loadBinarySummary(btn.dataset.period);
        });
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function () {
        initDashSharePopup();
        initBinarySummaryFilter();
      });
    } else {
      initDashSharePopup();
      initBinarySummaryFilter();
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
