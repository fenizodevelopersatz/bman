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

  /* responsive */
  @media(max-width: 900px) {
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
  @media (max-width: 900px) {
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
  @media (max-width: 420px) {
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
                  value="<?= $left_link ?? 'http://yourdomain.com/user/re?L-FENIZO165'; ?>" readonly>
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
                  <strong class="good"><?= currency_info()->currency_symbol; ?>
                    <?= number_format($left_invest ?? 0, 2); ?></strong>
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
                <input id="refRight" class="ref-input" value="<?= $right_link ?? base_url() . 'user/re?R-FENIZO165'; ?>"
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
                  <strong class="bad"><?= currency_info()->currency_symbol; ?>
                    <?= number_format($right_invest ?? 0, 2); ?></strong>
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

              <!-- Placeholder UI (you can integrate real QR later) -->
              <div class="qr">
                <div class="qrbox"><i class="ph ph-qr-code"></i></div>
                <b>Generate QR for your referral link</b>
                <small>Users can scan & join instantly. Add QR generator later (easy).</small>
                <button class="ref-btn ghost" style="width:100%;justify-content:center;" onclick="shareLink('refLeft')">
                  Share Left Link <i class="ph ph-share-network"></i>
                </button>
              </div>
            </div>

          </div>

        </div>
      </div>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
      <script>
        const base_url = "<?php echo base_url() ?>";
        function copyText(id) {
          const el = document.getElementById(id);
          el.select();
          el.setSelectionRange(0, 99999);
          navigator.clipboard.writeText(el.value).then(() => {
            toastMini("Copied!");
          }).catch(() => { alert("Copy failed"); });
        }

        function copyAllRefs() {
          const left = document.getElementById('refLeft')?.value || '';
          const right = document.getElementById('refRight')?.value || '';
          const txt = `Left Leg: ${left}\nRight Leg: ${right}`;
          navigator.clipboard.writeText(txt).then(() => toastMini("Both links copied!"));
        }

        function openLink(id) {
          const url = document.getElementById(id).value;
          window.open(url, '_blank');
        }

        async function shareLink(id) {
          const url = document.getElementById(id).value;
          if (navigator.share) {
            try {
              await navigator.share({ title: "Join my team", text: "Use my referral link to join:", url });
            } catch (e) { }
          } else {
            // fallback: copy
            navigator.clipboard.writeText(url);
            toastMini("Share not supported. Link copied!");
          }
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

        function downloadQR(url) {
          const container = document.getElementById('qr_container');

          // Clear previous QR code if exists
          container.innerHTML = '';

          // Example data: could be user referral link
          const referralLink = url;

          // Generate QR code
          const qr = new QRCode(container, {
            text: referralLink,
            width: 150,
            height: 150,
            colorDark: "#5d56a8",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
          });

          toastMini("QR code generated! Right-click to save.");
        }
      </script>

    </main>

    <!-- Right Panel (keep your existing) -->
    <aside class="right-panel">
      <?php $this->load->view('user/layout/v2/user_inner_right_panle'); ?>
    </aside>
  </div>

  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>
</body>

</html>