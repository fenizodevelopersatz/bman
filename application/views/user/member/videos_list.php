<!DOCTYPE html>
<html>

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <?php $this->load->view('user/layout/v2/user_style'); ?>
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <style>
    .vlist {
      padding: 20px
    }

    .vcard {
      background: #fff;
      border: 1px solid #f0f0f7;
      border-radius: 18px;
      padding: 16px;
      margin-bottom: 12px;
      display: flex;
      justify-content: space-between;
      align-items: center
    }

    .vtitle {
      font-weight: 800;
      font-size: 15px
    }

    .vsub {
      color: #8a8aa3;
      font-size: 13px
    }

    .btn {
      padding: 10px 14px;
      border-radius: 12px;
      border: 0;
      font-weight: 800;
      cursor: pointer
    }

    .btnp {
      background: linear-gradient(135deg, #6E56CF, #4D39A3);
      color: #fff
    }

    .btnd {
      background: #eee;
      color: #999;
      cursor: not-allowed
    }

    .pill {
      background: #f3f0ff;
      padding: 6px 10px;
      border-radius: 10px;
      font-weight: 800
    }
  </style>
  <style>
    :root {
      --p: #6E56CF;
      --pg: linear-gradient(135deg, #6E56CF, #4D39A3);
      --muted: #8a8aa3;
      --line: #f0f0f7;
      --card: #fff;
      --soft: #f7f7fb;
      --r: 18px;
    }

    * {
      box-sizing: border-box;
    }

    html,
    body {
      width: 100%;
      overflow-x: hidden;
    }

    .vlist {
      padding: 20px;
      max-width: 100%;
    }

    /* header */
    .vhead {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      margin-bottom: 12px;
      flex-wrap: wrap;
    }

    .vhead h2 {
      margin: 0;
      font-weight: 900;
      font-size: 20px;
      line-height: 1.2;
    }

    .vcard {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: var(--r);
      padding: 16px;
      margin-bottom: 12px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 14px;
      box-shadow: 0 8px 18px rgba(0, 0, 0, 0.03);
    }

    .vtitle {
      font-weight: 900;
      font-size: 15px;
      color: #12121a;
    }

    .vsub {
      color: var(--muted);
      font-size: 13px;
      margin-top: 4px;
      line-height: 1.45;
      max-width: 780px;
    }

    .vpills {
      margin-top: 10px;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .pill {
      background: #f3f0ff;
      border: 1px solid #eeeaff;
      padding: 7px 10px;
      border-radius: 12px;
      font-weight: 900;
      font-size: 12px;
      color: #2a2457;
      white-space: nowrap;
    }

    .btn {
      padding: 10px 14px;
      border-radius: 12px;
      border: 0;
      font-weight: 900;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      text-decoration: none;
      white-space: nowrap;
    }

    .btnp {
      background: var(--pg);
      color: #fff;
    }

    .btnd {
      background: #eee;
      color: #999;
      cursor: not-allowed;
    }

    /* ✅ Tablet */
    @media (max-width: 992px) {
      .vlist {
        padding: 14px;
      }

      .vcard {
        padding: 14px;
      }

      .vsub {
        max-width: 100%;
      }
    }

    /* ✅ Mobile: stack card + full width button */
    @media (max-width: 576px) {
      .vlist {
        padding: 12px;
      }

      .vhead {
        flex-direction: column;
        align-items: stretch;
      }

      .vhead a.btn {
        width: 100%;
      }

      .vcard {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
      }

      .vpills {
        gap: 8px;
      }

      .pill {
        font-size: 12px;
      }

      /* button becomes full width */
      .vcard .btn,
      .vcard a.btn {
        width: 100%;
        padding: 12px 14px;
        border-radius: 14px;
      }
    }
  </style>
</head>

<body>
  <div class="app-container">
    <?php $this->load->view('user/layout/v2/user_sidebar'); ?>
    <main class="main-content">
      <?php $this->load->view('user/layout/v2/user_header'); ?>

      <div class="vlist">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <h2 style="margin:0 0 12px;font-weight:900;">Watch Videos</h2>
          <a href="<?php echo base_url(); ?>user/earnings" class="btn btnp">Back</a>
        </div>

        <?php foreach ($videos as $v) { ?>
          <div class="vcard">
            <div>
              <div class="vtitle"><?= htmlspecialchars($v->title) ?></div>
              <div class="vsub"><?= htmlspecialchars($v->description) ?></div>
              <div style="margin-top:8px;display:flex;gap:10px;">
                <div class="pill"><?= (int) $v->duration_seconds ?>s</div>
                <div class="pill">$
                  <?= number_format((float) $v->reward_usd, 2) ?> reward
                </div>
              </div>
            </div>

            <?php if ($v->is_rewarded) { ?>
              <button class="btn btnd">Rewarded</button>
            <?php } else { ?>
              <a class="btn btnp" href="<?= base_url('user/earnings/videos/watch/' . $v->id) ?>">Watch</a>
            <?php } ?>
          </div>
        <?php } ?>
      </div>

    </main>
  </div>
</body>

</html>