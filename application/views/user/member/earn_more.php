<!DOCTYPE html>
<html lang="en">

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <?php $this->load->view('user/layout/v2/user_style'); ?>
  <script src="https://unpkg.com/@phosphor-icons/web"></script>

  <style>
    :root {
      --primary: #6E56CF;
      --primary-soft: #efedfb;
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

      --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.02);
      --shadow-md: 0 10px 25px -5px rgba(110, 86, 207, 0.1);
      --radius: 20px;
    }

    .earn-wrap {
      padding: 10px 0 50px;
    }

    .earn-welcome-banner {
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

    .earn-welcome-banner::after {
      content: '';
      position: absolute;
      right: -50px;
      top: -50px;
      width: 200px;
      height: 200px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
    }

    .banner-txt h2 {
      font-size: 28px;
      margin: 0;
      font-weight: 800;
    }

    .banner-txt p {
      opacity: 0.9;
      margin: 8px 0 0;
      font-size: 15px;
    }

    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      margin-bottom: 30px;
    }

    .kpi-stat-card {
      background: white;
      padding: 20px;
      border-radius: var(--radius);
      border: 1px solid var(--line);
      display: flex;
      align-items: center;
      gap: 15px;
      transition: 0.3s;
      box-shadow: var(--shadow-sm);
    }

    .kpi-stat-card:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-md);
    }

    .kpi-icon {
      width: 50px;
      height: 50px;
      border-radius: 14px;
      display: grid;
      place-items: center;
      font-size: 22px;
    }

    .kpi-info small {
      color: var(--muted);
      font-weight: 600;
      font-size: 12px;
      display: block;
    }

    .kpi-info strong {
      font-size: 20px;
      color: var(--text);
    }

    .methods-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      margin-bottom: 30px;
    }

    .method-card {
      background: white;
      border-radius: 24px;
      padding: 24px;
      border: 1px solid var(--line);
      transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
    }

    .method-card:hover {
      border-color: var(--primary);
      box-shadow: var(--shadow-md);
    }

    .method-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 15px;
    }

    .method-brand {
      display: flex;
      gap: 15px;
      align-items: center;
    }

    .method-icon-box {
      width: 54px;
      height: 54px;
      border-radius: 16px;
      display: grid;
      place-items: center;
      font-size: 24px;
      background: var(--primary-soft);
      color: var(--primary);
    }

    .method-title h3 {
      margin: 0;
      font-size: 18px;
      font-weight: 700;
    }

    .method-title p {
      margin: 4px 0 0;
      color: var(--muted);
      font-size: 13px;
      line-height: 1.4;
    }

    .progress-container {
      margin: 20px 0;
    }

    .progress-label {
      display: flex;
      justify-content: space-between;
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 6px;
    }

    .progress-bar-bg {
      height: 8px;
      background: #f0f0f5;
      border-radius: 10px;
      overflow: hidden;
    }

    .progress-bar-fill {
      height: 100%;
      background: var(--primary-gradient);
      border-radius: 10px;
    }

    .reward-pills {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }

    .reward-pill {
      flex: 1;
      background: #f8f9ff;
      border: 1px solid #eef0ff;
      padding: 10px;
      border-radius: 14px;
      text-align: center;
    }

    .reward-pill span {
      display: block;
      font-size: 11px;
      color: var(--muted);
      font-weight: 700;
      text-transform: uppercase;
    }

    .reward-pill b {
      font-size: 15px;
      color: var(--primary);
    }

    .btn-main {
      width: 100%;
      padding: 14px;
      border-radius: 15px;
      border: none;
      background: var(--primary-gradient);
      color: white;
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: 0.3s;
    }

    .btn-main:hover {
      opacity: 0.9;
      transform: scale(1.02);
    }

    .btn-outline {
      padding: 10px 18px;
      border-radius: 12px;
      border: 1px solid var(--line);
      background: white;
      font-weight: 600;
      font-size: 13px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .section-label {
      font-size: 18px;
      font-weight: 800;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .task-card {
      background: white;
      border: 1px solid var(--line);
      border-radius: 20px;
      padding: 16px 20px;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      transition: 0.2s;
    }

    .task-card:hover {
      border-color: var(--primary-soft);
      background: #fafbff;
    }

    .task-info {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .task-reward {
      font-size: 18px;
      font-weight: 800;
      color: var(--text);
      background: #f3f0ff;
      padding: 8px 15px;
      border-radius: 12px;
    }

    .badge-pill {
      border: none;
      padding: 5px 12px;
      border-radius: 8px;
      font-weight: 800;
      font-size: 12px;
    }

    @media (max-width: 992px) {
      .kpi-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .methods-grid {
        grid-template-columns: 1fr;
      }
    }

    /* ===================== RESPONSIVE PATCH (EARNINGS PAGE) ===================== */
    * {
      box-sizing: border-box;
    }

    html,
    body {
      width: 100%;
      overflow-x: hidden;
    }

    /* Banner */
    @media (max-width: 992px) {
      .earn-welcome-banner {
        padding: 22px;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }

      .banner-txt h2 {
        font-size: 22px;
      }

      .banner-txt p {
        font-size: 13px;
      }
    }

    @media (max-width: 520px) {
      .earn-welcome-banner {
        padding: 18px;
        border-radius: 18px;
      }

      .earn-welcome-banner::after {
        width: 160px;
        height: 160px;
        right: -70px;
        top: -70px;
      }
    }

    /* KPI Grid: 4 -> 2 -> 1 */
    @media (max-width: 992px) {
      .kpi-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
      }
    }

    @media (max-width: 520px) {
      .kpi-grid {
        grid-template-columns: 1fr;
        gap: 12px;
      }

      .kpi-stat-card {
        padding: 16px;
        border-radius: 18px;
      }

      .kpi-icon {
        width: 46px;
        height: 46px;
        font-size: 20px;
      }

      .kpi-info strong {
        font-size: 18px;
      }
    }

    /* Methods Grid: keep 2 on desktop, 1 on tablet/mobile (you already have 992 rule) */
    @media (max-width: 520px) {
      .method-card {
        padding: 16px;
        border-radius: 18px;
      }

      .method-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }

      .method-brand {
        width: 100%;
      }

      .method-title h3 {
        font-size: 16px;
      }

      .method-title p {
        font-size: 12px;
      }

      .method-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        font-size: 22px;
      }
    }

    /* Reward pills: 2 columns always, but tighter on mobile */
    @media (max-width: 520px) {
      .reward-pills {
        gap: 10px;
      }

      .reward-pill {
        padding: 10px;
        border-radius: 12px;
      }

      .reward-pill b {
        font-size: 14px;
      }
    }

    /* Buttons */
    @media (max-width: 520px) {
      .btn-main {
        padding: 13px;
        border-radius: 14px;
        font-size: 13px;
      }

      .btn-outline {
        padding: 10px 12px;
        border-radius: 12px;
        font-size: 12px;
      }
    }

    /* Quick Tasks row -> stack on mobile */
    @media (max-width: 992px) {
      .task-card {
        gap: 12px;
      }
    }

    @media (max-width: 720px) {
      .task-card {
        flex-direction: column;
        align-items: stretch;
        padding: 14px;
      }

      .task-info {
        width: 100%;
      }

      .task-info b {
        font-size: 14px !important;
      }

      /* right-side (reward + button) goes to new row */
      .task-card>div:last-child {
        width: 100%;
        justify-content: space-between;
        gap: 10px;
      }

      .task-reward {
        font-size: 16px;
        padding: 8px 12px;
        border-radius: 12px;
        white-space: nowrap;
      }

      .btn-outline {
        margin-left: auto;
        white-space: nowrap;
      }
    }

    @media (max-width: 420px) {
      .task-card>div:last-child {
        flex-direction: column;
        align-items: stretch;
      }

      .btn-outline {
        width: 100%;
        justify-content: center;
      }

      .task-reward {
        width: 100%;
        text-align: center;
      }
    }

    /* Section label spacing */
    @media (max-width: 520px) {
      .section-label {
        font-size: 16px;
        margin-bottom: 12px;
      }
    }
  </style>
</head>

<body>
  <div class="app-container">
    <?php $this->load->view('user/layout/v2/user_sidebar'); ?>

    <main class="main-content">
      <?php $this->load->view('user/layout/v2/user_header'); ?>

      <div class="earn-wrap">

        <!-- BANNER -->
        <div class="earn-welcome-banner">
          <div class="banner-txt">
            <h2>Boost Your Earnings<?php if (!empty($user->name)) { ?>, <?= htmlspecialchars($user->name) ?><?php } ?>
            </h2>
            <p>Complete simple daily tasks and watch your USD balance grow instantly.</p>
          </div>
        </div>

        <!-- KPI -->
        <div class="kpi-grid">
          <div class="kpi-stat-card">
            <div class="kpi-icon" style="background:#ecfdf5; color:#10b981;"><i class="ph ph-trend-up"></i></div>
            <div class="kpi-info"><small>Today</small><strong>$<?= number_format((float) $kpi_today, 2) ?></strong>
            </div>
          </div>
          <div class="kpi-stat-card">
            <div class="kpi-icon" style="background:#eff6ff; color:#3b82f6;"><i class="ph ph-wallet"></i></div>
            <div class="kpi-info"><small>Balance</small><strong>$<?= number_format((float) $kpi_balance, 2) ?></strong>
            </div>
          </div>
          <div class="kpi-stat-card">
            <div class="kpi-icon" style="background:#fff7ed; color:#f59e0b;"><i class="ph ph-clock-countdown"></i></div>
            <div class="kpi-info"><small>Pending</small><strong>$<?= number_format((float) $kpi_pending, 2) ?></strong>
            </div>
          </div>
          <div class="kpi-stat-card" style="background: var(--primary); border:none;">
            <div class="kpi-icon" style="background:rgba(255,255,255,0.2); color:white;"><i class="ph ph-fire"></i>
            </div>
            <div class="kpi-info">
              <small style="color:rgba(255,255,255,0.7)">Streak</small>
              <strong style="color:white;">+<?= (int) $kpi_streak_percent ?>%</strong>
            </div>
          </div>
        </div>

        <div class="section-label"><i class="ph ph-lightning" style="color:var(--warning)"></i> Main Earning Methods
        </div>

        <!-- METHODS -->
        <div class="methods-grid">
          <?php if (!empty($methods)) {
            foreach ($methods as $m) {
              $badge_bg = !empty($m->badge_bg) ? $m->badge_bg : '#f0fdf4';
              $badge_color = !empty($m->badge_color) ? $m->badge_color : '#16a34a';
              $progressColor = !empty($m->progress_color) ? $m->progress_color : null;
              $btnGradient = !empty($m->btn_gradient) ? $m->btn_gradient : 'var(--primary-gradient)';
              ?>
              <div class="method-card">
                <div class="method-header">
                  <div class="method-brand">
                    <div class="method-icon-box"
                      style="<?php if ($m->code === 'videos') { ?>background:#fff0f6; color:#d63384;<?php } ?>">
                      <i class="ph <?= htmlspecialchars($m->icon) ?>"></i>
                    </div>
                    <div class="method-title">
                      <h3><?= htmlspecialchars($m->title) ?></h3>
                      <p><?= htmlspecialchars($m->subtitle) ?></p>
                    </div>
                  </div>

                  <?php if (!empty($m->badge_text)) { ?>
                    <div class="badge-pill"
                      style="background:<?= htmlspecialchars($badge_bg) ?>; color:<?= htmlspecialchars($badge_color) ?>;">
                      <?= htmlspecialchars($m->badge_text) ?>
                    </div>
                  <?php } ?>
                </div>

                <div class="progress-container">
                  <div class="progress-label">
                    <span>Daily Progress</span>
                    <span><?= (int) $m->done ?> / <?= (int) $m->target ?>
                      <?= htmlspecialchars($m->code === 'ads' ? 'Ads' : 'Videos') ?></span>
                  </div>
                  <div class="progress-bar-bg">
                    <div class="progress-bar-fill"
                      style="width: <?= (int) $m->percent ?>%; <?php if ($progressColor) { ?>background:<?= htmlspecialchars($progressColor) ?>;<?php } ?>">
                    </div>
                  </div>
                </div>

                <div class="reward-pills">
                  <div class="reward-pill"><span>Reward</span><b>$<?= number_format((float) $m->reward_usd, 2) ?></b></div>
                  <div class="reward-pill"><span>Est. Time</span><b><?= htmlspecialchars($m->est_time_label) ?></b></div>
                </div>

                <?php
                $methodDone = ((int) $m->done >= (int) $m->target);
                $actionUrl = 'javascript:void(0)';
                if ($m->code === 'videos') {
                  $actionUrl = base_url('user/earnings/videos');   // list videos
                } elseif ($m->code === 'ads') {
                  $actionUrl = base_url('user/earnings/ads');      // later create ads page like videos
                } else {
                  $actionUrl = base_url('user/earnings');          // fallback
                }
                ?>
                <a class="btn-main" href="<?= $methodDone ? 'javascript:void(0)' : $actionUrl ?>"
                  style="background:<?= htmlspecialchars($btnGradient) ?>; <?= $methodDone ? 'pointer-events:none;opacity:.6;' : '' ?>">
                  <i class="ph ph-play-circle"></i>
                  <?= $methodDone ? 'Completed Today' : htmlspecialchars($m->btn_text) ?>
                </a>

              </div>
            <?php }
          } else { ?>
            <div class="method-card"><b>No methods found.</b></div>
          <?php } ?>
        </div>

        <div class="section-label">Quick Tasks</div>

        <!-- TASKS -->
        <?php if (!empty($tasks)) {
          foreach ($tasks as $t) {
            $iconBg = !empty($t->icon_bg) ? $t->icon_bg : null;
            $iconColor = !empty($t->icon_color) ? $t->icon_color : null;
            $disabled = !empty($t->is_done_today);
            ?>
            <div class="task-card">
              <div class="task-info">
                <div class="method-icon-box"
                  style="width:40px; height:40px; font-size:18px; <?php if ($iconBg) { ?>background:<?= htmlspecialchars($iconBg) ?>;<?php } ?> <?php if ($iconColor) { ?>color:<?= htmlspecialchars($iconColor) ?>;<?php } ?>">
                  <i class="ph <?= htmlspecialchars($t->icon) ?>"></i>
                </div>
                <div>
                  <b style="display:block; font-size:15px;"><?= htmlspecialchars($t->title) ?></b>
                  <small style="color:var(--muted)"><?= htmlspecialchars($t->subtitle) ?></small>
                </div>
              </div>

              <div style="display:flex; align-items:center; gap:20px;">
                <div class="task-reward">$<?= number_format((float) $t->reward_usd, 2) ?></div>

                <?php
                $disabled = !empty($t->is_done_today);
                if ($t->action_type === 'claim') {
                  $url = base_url('user/earnings/task/claim/' . $t->code);
                  $label = $disabled ? 'Claimed' : 'Claim Now';
                } else {
                  $url = base_url('user/earnings/task/verify/' . $t->code);
                  $label = $disabled ? 'Submitted' : 'Verify Task';
                }
                ?>
                <a class="btn-outline" href="<?= $disabled ? 'javascript:void(0)' : $url ?>"
                  style="<?= ($t->code === 'checkin') ? 'border-color:var(--primary); color:var(--primary)' : '' ?>; <?= $disabled ? 'pointer-events:none;opacity:.6;' : '' ?>">
                  <?= $label ?>
                </a>

              </div>
            </div>
          <?php }
        } else { ?>
          <div class="task-card"><b>No tasks found.</b></div>
        <?php } ?>

      </div>
    </main>

    <aside class="right-panel">
      <?php $this->load->view('user/layout/v2/user_inner_right_panle'); ?>
    </aside>
  </div>

  <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>
</body>

</html>