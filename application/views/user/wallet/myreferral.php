<!DOCTYPE html>
<html lang="en">

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <?php $this->load->view('user/layout/v2/user_style'); ?>
</head>
<!-- ===================== REFERRAL INFORMATION (MODERN) ===================== -->
<style>
  .ref-wrap {
    background: #fff;
    border: 1px solid #f5f5f7;
    border-radius: 24px;
    box-shadow: 0 14px 34px rgba(0, 0, 0, 0.05);
    overflow: hidden;
  }

  .ref-head {
    padding: 16px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    border-bottom: 1px solid #f5f5f7;
    background: linear-gradient(105deg, rgba(110, 86, 207, 0.10), rgba(110, 86, 207, 0.02));
  }

  .ref-head .ttl {
    display: flex;
    gap: 12px;
    align-items: center;
  }

  .ref-head .ic {
    width: 44px;
    height: 44px;
    border-radius: 16px;
    display: grid;
    place-items: center;
    background: #efedfb;
    color: var(--primary);
    font-size: 20px;
    flex-shrink: 0;
  }

  .ref-head h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 900;
  }

  .ref-head p {
    margin: 2px 0 0;
    font-size: 12px;
    color: var(--text-muted);
  }

  .ref-close {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    border: 1px solid #f1f1f6;
    background: #fff;
    display: grid;
    place-items: center;
    cursor: pointer;
    font-size: 18px;
    color: #475569;
  }

  .ref-body {
    padding: 18px;
  }

  .ref-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
  }

  .ref-card {
    border: 1px solid #f5f5f7;
    border-radius: 22px;
    padding: 14px;
    background: linear-gradient(180deg, #ffffff 0%, #fbfbff 100%);
    position: relative;
    overflow: hidden;
  }

  .ref-card::before {
    content: "";
    position: absolute;
    inset: -60px -60px auto auto;
    width: 180px;
    height: 180px;
    background: radial-gradient(circle, rgba(110, 86, 207, 0.16), transparent 60%);
    pointer-events: none;
  }

  .ref-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 10px;
  }

  .leg-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 900;
    border: 1px solid #f1f1f6;
    background: #fff;
  }

  .leg-pill.left {
    color: #2563eb;
  }

  .leg-pill.right {
    color: #f97316;
  }

  .mini-chip {
    font-size: 10px;
    font-weight: 900;
    padding: 6px 10px;
    border-radius: 999px;
    background: #efedfb;
    color: var(--primary);
    border: 1px solid #eeecff;
    white-space: nowrap;
  }

  .ref-link-row {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-top: 8px;
  }

  .ref-input {
    flex: 1;
    border: 1px solid #e9e9f2;
    background: #fff;
    border-radius: 14px;
    padding: 11px 12px;
    font-size: 12px;
    outline: none;
  }

  .ref-btn {
    border: none;
    cursor: pointer;
    border-radius: 14px;
    padding: 11px 12px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
  }

  .ref-btn.primary {
    background: var(--primary);
    color: #fff;
  }

  .ref-btn.ghost {
    background: #efedfb;
    color: var(--primary);
  }

  .ref-btn.light {
    background: #fff;
    border: 1px solid #f1f1f6;
    color: #111827;
  }

  .ref-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 12px;
  }

  .stat {
    border: 1px dashed #dedafc;
    border-radius: 18px;
    padding: 12px;
    background: #fff;
  }

  .stat small {
    display: block;
    color: var(--text-muted);
    font-size: 11px;
    font-weight: 700;
  }

  .stat strong {
    display: block;
    font-size: 18px;
    margin-top: 6px;
  }

  .stat strong.good {
    color: #0f9d58;
  }

  .stat strong.bad {
    color: #ef4444;
  }

  /* Bottom section */
  .ref-bottom {
    margin-top: 14px;
    display: grid;
    grid-template-columns: 1.3fr 0.7fr;
    gap: 14px;
  }

  .box {
    border: 1px solid #f5f5f7;
    border-radius: 22px;
    padding: 14px;
    background: #fff;
  }

  .box-h {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
  }

  .box-h b {
    font-size: 13px;
  }

  .box-h small {
    font-size: 11px;
    color: var(--text-muted);
    font-weight: 700;
  }

  .tips {
    display: grid;
    gap: 8px;
    margin-top: 10px;
  }

  .tip {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    font-size: 12px;
    color: #5d56a8;
    font-weight: 700;
  }

  .tip i {
    color: var(--primary);
    margin-top: 2px;
  }

  .qr {
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: center;
    justify-content: center;
    min-height: 170px;
    background: linear-gradient(180deg, #ffffff 0%, #fbfbff 100%);
    border: 1px dashed #dcd7ff;
    border-radius: 20px;
    text-align: center;
    padding: 12px;
  }

  .qr .qrbox {
    width: 96px;
    height: 96px;
    border-radius: 18px;
    background: #efedfb;
    color: var(--primary);
    display: grid;
    place-items: center;
    font-size: 34px;
  }

  .qr b {
    font-size: 12px;
  }

  .qr small {
    font-size: 11px;
    color: var(--text-muted);
    line-height: 1.4;
  }

  .ref-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 12px;
  }

  body.ref-share-open {
    overflow: hidden;
  }

  .ref-share-backdrop {
    position: fixed;
    inset: 0;
    z-index: 100000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 18px;
    background: rgba(13, 16, 24, .48);
    backdrop-filter: blur(5px);
  }

  .ref-share-backdrop.show {
    display: flex;
  }

  .ref-share-sheet {
    width: min(420px, 100%);
    max-height: min(720px, calc(100vh - 36px));
    overflow: hidden auto;
    color: #fff;
    border: 1px solid rgba(255, 255, 255, .12);
    background:
      radial-gradient(circle at 20% 0%, rgba(255, 255, 255, .08), transparent 34%),
      linear-gradient(180deg, #25242a 0%, #17171a 100%);
    box-shadow: 0 30px 100px rgba(0, 0, 0, .42);
    animation: refShareIn .18s ease both;
  }

  @keyframes refShareIn {
    from {
      opacity: 0;
      transform: translateY(16px) scale(.98);
    }

    to {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }

  .ref-share-title {
    position: sticky;
    top: 0;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 52px;
    padding: 14px 54px;
    font-size: 15px;
    font-weight: 900;
    text-align: center;
    background: rgba(25, 25, 29, .92);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255, 255, 255, .08);
  }

  .ref-share-close {
    position: absolute;
    right: 12px;
    top: 9px;
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 10px;
    display: grid;
    place-items: center;
    color: #fff;
    background: rgba(255, 255, 255, .08);
    cursor: pointer;
  }

  .ref-share-body {
    padding: 12px 14px 0;
  }

  .ref-share-link-card {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr) 42px;
    gap: 10px;
    align-items: center;
    padding: 8px;
    border-radius: 3px;
    background: rgba(255, 255, 255, .08);
    border: 1px solid rgba(255, 255, 255, .08);
  }

  .ref-share-link-icon,
  .ref-share-copy {
    border: 0;
    display: grid;
    place-items: center;
    color: #cbd5e1;
    background: rgba(255, 255, 255, .12);
  }

  .ref-share-link-icon {
    width: 44px;
    height: 44px;
    font-size: 21px;
  }

  .ref-share-copy {
    width: 36px;
    height: 36px;
    cursor: pointer;
    font-size: 18px;
  }

  .ref-share-copy:hover,
  .ref-share-close:hover {
    background: rgba(255, 255, 255, .18);
  }

  .ref-share-link-text {
    min-width: 0;
  }

  .ref-share-link-text b,
  .ref-share-link-text small {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .ref-share-link-text b {
    font-size: 13px;
    font-weight: 800;
  }

  .ref-share-link-text small {
    margin-top: 2px;
    color: rgba(255, 255, 255, .76);
    font-size: 12px;
  }

  .ref-share-qr-card {
    display: grid;
    grid-template-columns: 126px minmax(0, 1fr);
    gap: 14px;
    align-items: center;
    margin-top: 12px;
    padding: 12px;
    border-radius: 4px;
    background: rgba(255, 255, 255, .08);
    border: 1px dashed rgba(255, 255, 255, .18);
  }

  .ref-share-qr-box {
    width: 112px;
    height: 112px;
    display: grid;
    place-items: center;
    padding: 8px;
    border-radius: 10px;
    background: #fff;
  }

  .ref-share-qr-box canvas,
  .ref-share-qr-box img {
    width: 96px !important;
    height: 96px !important;
    display: block;
  }

  .ref-share-qr-text b {
    display: block;
    font-size: 13px;
    font-weight: 900;
  }

  .ref-share-qr-text small {
    display: block;
    margin-top: 5px;
    color: rgba(255, 255, 255, .70);
    font-size: 12px;
    line-height: 1.45;
  }

  .ref-share-section {
    padding: 18px 4px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, .16);
  }

  .ref-share-section:last-child {
    border-bottom: 0;
  }

  .ref-share-device {
    width: 86px;
    border: 0;
    padding: 0;
    color: #fff;
    background: transparent;
    cursor: pointer;
    text-align: center;
  }

  .ref-share-device .icon,
  .ref-share-app .icon {
    width: 40px;
    height: 40px;
    margin: 0 auto 8px;
    display: grid;
    place-items: center;
    border-radius: 4px;
    font-size: 24px;
    color: #fff;
    box-shadow: inset 0 1px 10px rgba(255, 255, 255, .16), 0 6px 16px rgba(0, 0, 0, .18);
  }

  .ref-share-device .icon {
    background: linear-gradient(180deg, #31d5f6, #058fc7);
  }

  .ref-share-device span,
  .ref-share-app span {
    display: block;
    font-size: 12px;
    line-height: 1.25;
  }

  .ref-share-app-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    row-gap: 20px;
    column-gap: 14px;
  }

  .ref-share-app {
    border: 0;
    padding: 0;
    color: #fff;
    background: transparent;
    cursor: pointer;
    text-align: center;
  }

  .ref-share-app.outlook .icon {
    background: linear-gradient(135deg, #0a8fda, #0364b8);
  }

  .ref-share-app.onenote .icon {
    background: linear-gradient(135deg, #8039b7, #551a8b);
  }

  .ref-share-app.phone .icon {
    background: linear-gradient(135deg, #169be8, #0b63ce);
  }

  .ref-share-app.facebook .icon {
    background: #1877f2;
  }

  .ref-share-app.twitter .icon {
    background: #0f1419;
  }

  .ref-share-app.whatsapp .icon {
    background: #25d366;
  }

  .ref-share-app.gmail .icon {
    background: linear-gradient(135deg, #ea4335, #fbbc05);
  }

  .ref-share-app.linkedin .icon {
    background: #0a66c2;
  }

  .ref-share-footer {
    position: sticky;
    bottom: 0;
    display: flex;
    justify-content: center;
    padding: 13px 14px;
    background: rgba(25, 25, 29, .94);
    border-top: 1px solid rgba(255, 255, 255, .12);
  }

  .ref-share-store {
    border: 0;
    color: #8ee9ff;
    background: transparent;
    cursor: pointer;
    font-size: 12px;
    font-weight: 800;
  }

  .ref-share-store i {
    margin-right: 6px;
  }

  /* ===== SHARED BREAKPOINT SCALE — see assets/user_v2/css/style.css =====
     1400 xxl · 1200 xl · 1024 lg (must match user_sidebar.php JS) · 768 md · 600 sm · 380 xs
     ===================================================================== */
  @media(max-width: 768px) {
    .ref-grid {
      grid-template-columns: 1fr;
    }

    .ref-bottom {
      grid-template-columns: 1fr;
    }
  }

  /* ===================== GLOBAL RESPONSIVE PATCH ===================== */
  * {
    box-sizing: border-box;
  }

  html,
  body {
    width: 100%;
    overflow-x: hidden;
  }

  /* Main wrapper fit */
  .ref-wrap {
    width: 100%;
    max-width: 1100px;
    margin: 0 auto;
  }

  /* Reduce padding on small screens */
  @media (max-width: 600px) {
    .ref-head {
      padding: 14px 14px;
    }

    .ref-body {
      padding: 14px;
    }

    .ref-card,
    .box {
      padding: 12px;
      border-radius: 18px;
    }

    .ref-head .ic {
      width: 40px;
      height: 40px;
      border-radius: 14px;
    }
  }

  /* Title area: stack nicely */
  @media (max-width: 600px) {
    .ref-head {
      flex-direction: column;
      align-items: flex-start;
      gap: 12px;
    }

    .ref-close {
      align-self: flex-end;
    }

    .ref-head h3 {
      font-size: 15px;
    }

    .ref-head p {
      font-size: 11px;
    }
  }

  /* Link row: input + button stack on mobile */
  @media (max-width: 600px) {
    .ref-link-row {
      flex-direction: column;
      align-items: stretch;
    }

    .ref-input,
    .ref-btn {
      width: 100%;
    }

    .ref-btn {
      justify-content: center;
    }
  }

  /* Buttons row wrap: make full width on mobile */
  @media (max-width: 600px) {
    .ref-actions {
      display: grid;
      grid-template-columns: 1fr;
      gap: 10px;
    }

    .ref-actions .ref-btn {
      width: 100%;
      justify-content: center;
    }
  }

  @media (max-width: 600px) {
    .ref-share-backdrop {
      align-items: flex-end;
      padding: 0;
    }

    .ref-share-sheet {
      width: 100%;
      max-height: 86vh;
      border-radius: 22px 22px 0 0;
    }

    .ref-share-app-grid {
      grid-template-columns: repeat(3, 1fr);
    }

    .ref-share-qr-card {
      grid-template-columns: 1fr;
      justify-items: center;
      text-align: center;
    }
  }

  /* Stats: 2 -> 1 on small */
  @media (max-width: 600px) {
    .ref-stats {
      grid-template-columns: 1fr;
    }

    .stat strong {
      font-size: 16px;
    }
  }

  /* Bottom grid: always nice stacking */
  @media (max-width: 768px) {
    .ref-bottom {
      grid-template-columns: 1fr;
    }
  }

  /* QR box size for mobile */
  @media (max-width: 600px) {
    .qr {
      min-height: auto;
      padding: 12px;
      border-radius: 18px;
    }

    .qr .qrbox {
      width: 84px;
      height: 84px;
      border-radius: 16px;
      font-size: 30px;
    }
  }

  /* Big screens: keep centered and not too wide */
  @media (min-width: 1400px) {
    .ref-wrap {
      max-width: 1200px;
    }
  }

  /* Optional: improve leg pill spacing on very small screens */
  @media (max-width: 380px) {
    .leg-pill {
      font-size: 11px;
      padding: 7px 10px;
    }

    .mini-chip {
      font-size: 9px;
      padding: 5px 9px;
    }
  }
</style>

<body>
  <div class="app-container">
    <!-- Sidebar -->
    <?php $this->load->view('user/layout/v2/user_sidebar'); ?>

    <!-- Main Content -->
    <main class="main-content">
      <?php $this->load->view('user/layout/v2/user_header'); ?>
      <div class="ref-wrap">
        <div class="ref-head">
          <div class="ttl">
            <div class="ic"><i class="ph ph-share-network"></i></div>
            <div>
              <h3>Referral Information</h3>
              <p>Share your Left/Right leg links to grow your binary team faster.</p>
            </div>
          </div>

          <!-- If modal, keep close button -->
          <button class="ref-close" type="button" data-bs-dismiss="modal" aria-label="Close">
            <i class="ph ph-x"></i>
          </button>
        </div>

        <div class="ref-body">

          <!-- TOP: Left/Right Links -->
          <div class="ref-grid">

            <!-- LEFT LEG -->
            <div class="ref-card">
              <div class="ref-top">
                <div class="leg-pill left"><i class="ph ph-arrow-left"></i> Left Leg Link</div>
                <span class="mini-chip">Recommended</span>
              </div>

              <div class="ref-link-row">
                <input id="refLeft" class="ref-input"
                  value="<?= $left_link ?? 'http://yourdomain.com/user/re?L-NEXMAN165'; ?>" readonly>
                <button class="ref-btn primary" onclick="copyText('refLeft')"><i class="ph ph-copy"></i> Copy</button>
              </div>

              <div class="ref-actions">
                <button class="ref-btn ghost" onclick="shareLink('refLeft')"><i class="ph ph-paper-plane-tilt"></i>
                  Share</button>
                <button class="ref-btn light" onclick="openLink('refLeft')"><i class="ph ph-arrow-square-out"></i>
                  Open</button>
              </div>

              <div class="ref-stats">
                <div class="stat">
                  <small>Total Left Users</small>
                  <strong class="good"><?= $left_users ?? 0; ?></strong>
                </div>
                <div class="stat">
                  <small>Left Users Investment</small>
                  <strong class="good"><?= number_format($left_invest ?? 0, 2); ?>
                    <span style="font-size:12px;font-weight:900;color:var(--text-muted);">BMAN</span></strong>
                </div>
              </div>
            </div>

            <!-- RIGHT LEG -->
            <div class="ref-card">
              <div class="ref-top">
                <div class="leg-pill right"><i class="ph ph-arrow-right"></i> Right Leg Link</div>
                <span class="mini-chip">Balance Team</span>
              </div>

              <div class="ref-link-row">
                <input id="refRight" class="ref-input" value="<?= $right_link ?? base_url() . 'user/re?R-NEXMAN165'; ?>"
                  readonly>
                <button class="ref-btn primary" onclick="copyText('refRight')"><i class="ph ph-copy"></i> Copy</button>
              </div>

              <div class="ref-actions">
                <button class="ref-btn ghost" onclick="shareLink('refRight')"><i class="ph ph-paper-plane-tilt"></i>
                  Share</button>
                <button class="ref-btn light" onclick="openLink('refRight')"><i class="ph ph-arrow-square-out"></i>
                  Open</button>
              </div>

              <div class="ref-stats">
                <div class="stat">
                  <small>Total Right Users</small>
                  <strong class="bad"><?= $right_users ?? 0; ?></strong>
                </div>
                <div class="stat">
                  <small>Right Users Investment</small>
                  <strong class="bad"><?= number_format($right_invest ?? 0, 2); ?>
                    <span style="font-size:12px;font-weight:900;color:var(--text-muted);">BMAN</span></strong>
                </div>
              </div>
            </div>

          </div>

          <!-- BOTTOM: Tips + QR -->
          <div class="ref-bottom">

            <div class="box">
              <div class="box-h">
                <b>Smart Referral Tips</b>
                <small>Boost pairing income</small>
              </div>

              <div class="tips">
                <div class="tip"><i class="ph ph-check-circle"></i> Keep Left & Right users balanced for more pairs.
                </div>
                <div class="tip"><i class="ph ph-check-circle"></i> Share Left link for strong leg and Right link for
                  weak leg.</div>
                <div class="tip"><i class="ph ph-check-circle"></i> Encourage users to purchase to increase BV &
                  activation.</div>
                <div class="tip"><i class="ph ph-check-circle"></i> Track joins daily and follow up with inactive
                  members.</div>
              </div>

              <div class="ref-actions" style="margin-top:14px;">
                <button class="ref-btn primary" onclick="copyAllRefs()"><i class="ph ph-copy"></i> Copy Both
                  Links</button>
                <button class="ref-btn light" onclick="downloadQR('<?php echo $left_link ?>')"><i
                    class="ph ph-qr-code"></i> Get Left QR
                  (Optional)</button>
                <button class="ref-btn light" onclick="downloadQR('<?php echo $right_link ?>')"><i
                    class="ph ph-qr-code"></i> Get Right QR
                  (Optional)</button>
              </div>
              <div id="qr_container" style="margin-top: 10px;"></div>

            </div>

            <div class="box">
              <div class="box-h">
                <b>QR / Quick Share</b>
                <small>Optional</small>
              </div>

              <div class="qr">
                <div class="qrbox" id="qrBox"><i class="ph ph-qr-code"></i></div>
                <b>Generate QR for your referral link</b>
                <small>Users can scan &amp; join instantly. Tap a leg to generate its QR.</small>
                <div class="ref-actions" style="justify-content:center;margin-top:4px;">
                  <button class="ref-btn ghost" onclick="showQR('refLeft')">
                    <i class="ph ph-qr-code"></i> Left QR
                  </button>
                  <button class="ref-btn light" onclick="showQR('refRight')">
                    <i class="ph ph-qr-code"></i> Right QR
                  </button>
                </div>
                <button class="ref-btn ghost" style="width:100%;justify-content:center;" onclick="shareLink('refLeft')">
                  Share Left Link <i class="ph ph-share-network"></i>
                </button>
              </div>
            </div>

          </div>

        </div>
      </div>
      <script src="<?php echo base_url('assets/js/vendor/qrcode.min.js'); ?>"></script>
      <script>
        const base_url = "<?php echo base_url() ?>";

        // Robust clipboard copy that works even in insecure contexts (plain HTTP /
        // LAN IP), where navigator.clipboard is undefined. Falls back to a hidden
        // textarea + execCommand('copy').
        function copyToClipboard(text) {
          if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
          }
          return new Promise((resolve, reject) => {
            try {
              const ta = document.createElement('textarea');
              ta.value = text;
              ta.setAttribute('readonly', '');
              ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0;';
              document.body.appendChild(ta);
              ta.select();
              ta.setSelectionRange(0, ta.value.length);
              const ok = document.execCommand('copy');
              document.body.removeChild(ta);
              ok ? resolve() : reject(new Error('execCommand failed'));
            } catch (e) {
              reject(e);
            }
          });
        }

        function copyText(id) {
          const el = document.getElementById(id);
          if (!el) return;
          copyToClipboard(el.value)
            .then(() => toastMini("Copied!"))
            .catch(() => toastMini("Copy failed. Please copy manually."));
        }

        function copyAllRefs() {
          const left = document.getElementById('refLeft')?.value || '';
          const right = document.getElementById('refRight')?.value || '';
          const txt = `Left Leg: ${left}\nRight Leg: ${right}`;
          copyToClipboard(txt)
            .then(() => toastMini("Both links copied!"))
            .catch(() => toastMini("Copy failed. Please copy manually."));
        }

        function openLink(id) {
          const url = document.getElementById(id).value;
          window.open(url, '_blank');
        }

        let activeRefShareId = 'refLeft';

        function getRefLinkValue(id) {
          const input = document.getElementById(id);
          return input && input.value ? input.value.trim() : '';
        }

        function getRefShareMeta(id) {
          const url = getRefLinkValue(id);
          const leg = id === 'refRight' ? 'Right Leg' : 'Left Leg';
          let domain = 'Referral link';

          try {
            domain = new URL(url).hostname || domain;
          } catch (e) {
            domain = url.replace(/^https?:\/\//, '').split('/')[0] || domain;
          }

          return {
            url,
            leg,
            title: 'Share ' + leg + ' Link',
            text: 'Join my Nexman team using this ' + leg + ' referral link:',
            domain
          };
        }

        function renderRefSharePopup() {
          const meta = getRefShareMeta(activeRefShareId);
          const title = document.getElementById('refShareTitle');
          const domain = document.getElementById('refShareDomain');
          const link = document.getElementById('refShareUrl');
          const qr = document.getElementById('refShareQr');
          const qrLabel = document.getElementById('refShareQrLabel');

          if (title) title.textContent = meta.title;
          if (domain) domain.textContent = meta.domain;
          if (link) link.textContent = meta.url;
          if (qrLabel) qrLabel.textContent = 'QR for ' + meta.leg + ' referral link.';
          renderQR(qr, meta.url, 96);
        }

        function openRefSharePopup(id) {
          const meta = getRefShareMeta(id);
          if (!meta.url) {
            toastMini("Referral link not available.");
            return;
          }

          activeRefShareId = id === 'refRight' ? 'refRight' : 'refLeft';
          renderRefSharePopup();

          const modal = document.getElementById('refShareModal');
          if (!modal) return;

          modal.classList.add('show');
          modal.setAttribute('aria-hidden', 'false');
          document.body.classList.add('ref-share-open');
        }

        function closeRefSharePopup() {
          const modal = document.getElementById('refShareModal');
          if (!modal) return;

          modal.classList.remove('show');
          modal.setAttribute('aria-hidden', 'true');
          document.body.classList.remove('ref-share-open');
        }

        function copyActiveShareLink() {
          const url = getRefLinkValue(activeRefShareId);
          if (!url) {
            toastMini("Referral link not available.");
            return;
          }

          copyToClipboard(url)
            .then(() => toastMini("Link copied!"))
            .catch(() => toastMini("Copy failed. Please copy manually."));
        }

        function openShareWindow(url) {
          const opened = window.open(url, '_blank', 'width=720,height=640');
          if (!opened) {
            toastMini("Popup blocked. Link copied instead.");
            copyActiveShareLink();
            return;
          }

          try { opened.opener = null; } catch (e) { /* ignored */ }
        }

        function shareVia(channel) {
          const meta = getRefShareMeta(activeRefShareId);
          if (!meta.url) {
            toastMini("Referral link not available.");
            return;
          }

          const encodedUrl = encodeURIComponent(meta.url);
          const encodedTitle = encodeURIComponent(meta.title);
          const encodedText = encodeURIComponent(meta.text + ' ' + meta.url);
          const emailBody = encodeURIComponent(meta.text + '\n\n' + meta.url);
          let shareUrl = '';

          switch (channel) {
            case 'facebook':
              shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodedUrl;
              break;
            case 'twitter':
              shareUrl = 'https://twitter.com/intent/tweet?text=' + encodedText;
              break;
            case 'whatsapp':
              shareUrl = 'https://api.whatsapp.com/send?text=' + encodedText;
              break;
            case 'gmail':
              shareUrl = 'https://mail.google.com/mail/?view=cm&fs=1&su=' + encodedTitle + '&body=' + emailBody;
              break;
            case 'linkedin':
              shareUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodedUrl;
              break;
            case 'onenote':
              shareUrl = 'https://www.onenote.com/clipper/onenote?url=' + encodedUrl + '&title=' + encodedTitle;
              break;
            case 'outlook':
              shareUrl = 'https://outlook.live.com/mail/0/deeplink/compose?subject=' + encodedTitle + '&body=' + emailBody;
              break;
            default:
              copyActiveShareLink();
              return;
          }

          openShareWindow(shareUrl);
        }

        function initRefSharePopup() {
          const modal = document.getElementById('refShareModal');
          if (!modal) return;

          modal.addEventListener('click', function (event) {
            if (event.target === modal) {
              closeRefSharePopup();
            }
          });

          document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('show')) {
              closeRefSharePopup();
            }
          });
        }

        function shareLink(id) {
          openRefSharePopup(id);
        }

        // function downloadQR() {
        //   // placeholder (integrate real QR generator later)
        //   toastMini("QR generator not connected yet. Tell me if you want it with JS QR library.");
        // }

        // tiny toast (no dependency)
        function toastMini(msg) {
          const t = document.createElement('div');
          t.textContent = msg;
          t.style.cssText =
            "position:fixed;bottom:22px;left:50%;transform:translateX(-50%);background:#111;color:#fff;padding:10px 14px;border-radius:14px;font-weight:800;font-size:12px;z-index:99999;opacity:0;transition:.2s;";
          document.body.appendChild(t);
          requestAnimationFrame(() => t.style.opacity = "1");
          setTimeout(() => { t.style.opacity = "0"; setTimeout(() => t.remove(), 250); }, 1400);
        }

        // Render a QR into a target element and return the generated <img>/<canvas>.
        function renderQR(container, url, size) {
          if (!container) return null;
          container.innerHTML = '';
          if (!url) {
            container.innerHTML = '<i class="ph ph-qr-code" aria-hidden="true"></i>';
            return null;
          }

          if (typeof QRCode === 'undefined') {
            const img = document.createElement('img');
            img.alt = 'Referral QR code';
            img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=' + size + 'x' + size + '&data=' + encodeURIComponent(url);
            container.appendChild(img);
            return img;
          }

          new QRCode(container, {
            text: url,
            width: size,
            height: size,
            colorDark: "#5d56a8",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel ? QRCode.CorrectLevel.H : 2
          });
          return container.querySelector('img, canvas');
        }

        // "Get Left/Right QR" buttons: generate under the tips box + offer download.
        function downloadQR(url) {
          if (!url) { toastMini("No referral link available."); return; }
          const container = document.getElementById('qr_container');
          const el = renderQR(container, url, 150);
          if (!el) return;
          toastMini("QR code generated! Right-click to save.");
        }

        // QR box (right column): render the chosen leg's QR right inside the box.
        function showQR(inputId) {
          const input = document.getElementById(inputId);
          if (!input || !input.value) { toastMini("No referral link available."); return; }
          const box = document.getElementById('qrBox');
          const el = renderQR(box, input.value, 96);
          if (el) { el.style.width = '96px'; el.style.height = '96px'; }
        }

        document.addEventListener('DOMContentLoaded', initRefSharePopup);
      </script>

    </main>

    <!-- Right Panel (keep your existing) -->
    <aside class="right-panel">
      <?php $this->load->view('user/layout/v2/user_inner_right_panle'); ?>
    </aside>
  </div>

  <div class="ref-share-backdrop" id="refShareModal" aria-hidden="true">
    <div class="ref-share-sheet" role="dialog" aria-modal="true" aria-labelledby="refShareTitle">
      <div class="ref-share-title">
        <span id="refShareTitle">Share</span>
        <button class="ref-share-close" type="button" onclick="closeRefSharePopup()" aria-label="Close share popup">
          <i class="ph ph-x"></i>
        </button>
      </div>

      <div class="ref-share-body">
        <div class="ref-share-link-card">
          <div class="ref-share-link-icon"><i class="ph ph-link-simple"></i></div>
          <div class="ref-share-link-text">
            <b id="refShareDomain">Referral link</b>
            <small id="refShareUrl">Select a referral link</small>
          </div>
          <button class="ref-share-copy" type="button" onclick="copyActiveShareLink()" aria-label="Copy referral link">
            <i class="ph ph-link-simple"></i>
          </button>
        </div>

        <div class="ref-share-qr-card">
          <div class="ref-share-qr-box" id="refShareQr" aria-label="Referral QR code"></div>
          <div class="ref-share-qr-text">
            <b>Scan QR to join</b>
            <small id="refShareQrLabel">This QR updates for the selected referral link.</small>
          </div>
        </div>

        <div class="ref-share-section">
          <button class="ref-share-device" type="button" onclick="copyActiveShareLink()">
            <span class="icon"><i class="ph ph-device-mobile"></i></span>
            <span>My Phone</span>
          </button>
        </div>

        <div class="ref-share-section">
          <div class="ref-share-app-grid" aria-label="Share options">
            <button class="ref-share-app outlook" type="button" onclick="shareVia('outlook')">
              <span class="icon"><i class="ph ph-envelope-simple"></i></span>
              <span>Outlook</span>
            </button>
            <button class="ref-share-app onenote" type="button" onclick="shareVia('onenote')">
              <span class="icon"><i class="ph ph-notebook"></i></span>
              <span>OneNote</span>
            </button>
            <button class="ref-share-app phone" type="button" onclick="copyActiveShareLink()">
              <span class="icon"><i class="ph ph-device-mobile-camera"></i></span>
              <span>Phone Link</span>
            </button>
            <button class="ref-share-app facebook" type="button" onclick="shareVia('facebook')">
              <span class="icon"><i class="ph ph-facebook-logo"></i></span>
              <span>Facebook</span>
            </button>
            <button class="ref-share-app twitter" type="button" onclick="shareVia('twitter')">
              <span class="icon"><i class="ph ph-x-logo"></i></span>
              <span>Twitter</span>
            </button>
            <button class="ref-share-app whatsapp" type="button" onclick="shareVia('whatsapp')">
              <span class="icon"><i class="ph ph-whatsapp-logo"></i></span>
              <span>WhatsApp</span>
            </button>
            <button class="ref-share-app gmail" type="button" onclick="shareVia('gmail')">
              <span class="icon"><i class="ph ph-envelope"></i></span>
              <span>Gmail</span>
            </button>
            <button class="ref-share-app linkedin" type="button" onclick="shareVia('linkedin')">
              <span class="icon"><i class="ph ph-linkedin-logo"></i></span>
              <span>LinkedIn</span>
            </button>
          </div>
        </div>
      </div>

      <div class="ref-share-footer">
        <button class="ref-share-store" type="button" onclick="toastMini('Choose an app above to share your referral link.')">
          <i class="ph ph-shopping-bag"></i> Get apps in Store
        </button>
      </div>
    </div>
  </div>

  <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>
</body>

</html>
