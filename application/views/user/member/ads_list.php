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
      cursor: pointer;
      text-decoration: none;
      display: inline-block
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
    * {
      box-sizing: border-box;
    }

    .vlist {
      padding: 20px;
    }

    .vcard {
      background: #fff;
      border: 1px solid #f0f0f7;
      border-radius: 18px;
      padding: 16px;
      margin-bottom: 14px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 15px;
      transition: 0.2s ease;
    }

    .vcard:hover {
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
    }

    .vtitle {
      font-weight: 800;
      font-size: 15px;
      margin-bottom: 4px;
    }

    .vsub {
      color: #8a8aa3;
      font-size: 13px;
      line-height: 1.4;
    }

    .btn {
      padding: 10px 14px;
      border-radius: 12px;
      border: 0;
      font-weight: 800;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      white-space: nowrap;
    }

    .btnp {
      background: linear-gradient(135deg, #6E56CF, #4D39A3);
      color: #fff;
    }

    .btnd {
      background: #eee;
      color: #999;
      cursor: not-allowed;
    }

    .pill {
      background: #f3f0ff;
      padding: 6px 10px;
      border-radius: 10px;
      font-weight: 800;
      font-size: 12px;
    }

    /* ================= MOBILE ================= */
    @media (max-width: 768px) {

      .vlist {
        padding: 14px;
      }

      .vcard {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
      }

      .vcard>div {
        width: 100%;
      }

      .vcard .btn {
        width: 100%;
        text-align: center;
      }

      .vcard .btnd {
        width: 100%;
        text-align: center;
      }

      .vcard div[style*="display:flex"] {
        flex-wrap: wrap;
        gap: 8px;
      }

      h2 {
        font-size: 20px;
      }
    }

    /* Extra small devices */
    @media (max-width: 480px) {

      .vtitle {
        font-size: 14px;
      }

      .vsub {
        font-size: 12px;
      }

      .pill {
        font-size: 11px;
        padding: 5px 8px;
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
          <h2 style="margin:0 0 12px;font-weight:900;">Watch Ads</h2>
          <a href="<?php echo base_url(); ?>user/earnings" class="btn btnp">Back</a>
        </div>

        <?php foreach ($ads as $a) { ?>
          <div class="vcard">
            <div>
              <div class="vtitle"><?= htmlspecialchars($a->title) ?></div>
              <div class="vsub"><?= htmlspecialchars($a->description) ?></div>
              <div style="margin-top:8px;display:flex;gap:10px;">
                <div class="pill"><?= (int) $a->duration_seconds ?>s</div>
                <div class="pill">$<?= number_format((float) $a->reward_usd, 2) ?> reward</div>
              </div>
            </div>

            <?php if ($a->is_rewarded) { ?>
              <span class="btn btnd">Rewarded</span>
            <?php } else { ?>
              <a class="btn btnp" href="<?= base_url('user/earnings/ads/watch/' . $a->id) ?>">Watch</a>
            <?php } ?>
          </div>
        <?php } ?>
      </div>

    </main>
  </div>
</body>

</html>