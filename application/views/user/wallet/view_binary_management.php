<?php
// ===================== BINARY MATCHING HISTORY (USER) =====================
// Expected vars (set from Historycontroller::lendingBinaryHistory()):
// $history = rows from staking_matching_payouts (id, matched_volume, total_percent,
//            earning_amount, staking_amount, left_before, right_before, run_ref, created_at)
// $summary = ['lifetime','today','weekly','monthly','earning','staking',
//             'carry_left','carry_right','pending_ceiling']
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php $this->load->view('user/layout/v2/user_style'); ?>
  <style>
    .bm-titlebar { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; margin: 8px 0 18px; flex-wrap: wrap; }
    .bm-titlebar h2 { font-size: 18px; font-weight: 900; color: var(--text-main); display: flex; align-items: center; gap: 10px; margin: 0; }
    .bm-titlebar h2 i { color: var(--primary); font-size: 20px; }
    .bm-titlebar .sub { margin-top: 4px; color: var(--text-muted); font-size: 12px; }

    .bm-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px; }
    .bm-card { background: #fff; border: 1px solid #f1f1f6; border-radius: 16px; padding: 16px 18px; }
    .bm-card small { display: block; color: var(--text-muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; }
    .bm-card b { font-size: 18px; font-weight: 900; color: var(--text-main); }
    .bm-card b span { font-size: 11px; font-weight: 700; color: var(--text-muted); margin-left: 3px; }
    .bm-card.split { display: flex; gap: 16px; }
    .bm-card.split div { flex: 1; }
    .bm-card.split .lbl { font-size: 10px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; }

    .bm-table-wrap { background: #fff; border: 1px solid #f1f1f6; border-radius: 16px; overflow: hidden; }
    .bm-table-head { padding: 16px 18px; border-bottom: 1px solid #f1f1f6; display: flex; align-items: center; justify-content: space-between; }
    .bm-table-head h3 { margin: 0; font-size: 15px; font-weight: 900; }
    .bm-table-head span { font-size: 12px; color: var(--text-muted); }
    .bm-scroll { overflow-x: auto; }
    table.bm-tbl { width: 100%; border-collapse: collapse; min-width: 880px; }
    table.bm-tbl th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .03em; color: var(--text-muted); font-weight: 700; padding: 10px 14px; background: #fafafc; white-space: nowrap; }
    table.bm-tbl td { padding: 12px 14px; font-size: 13px; border-top: 1px solid #f5f5f7; white-space: nowrap; }
    table.bm-tbl td.num { font-variant-numeric: tabular-nums; }
    .bm-empty { text-align: center; padding: 40px 20px; color: var(--text-muted); }
    .bm-pill { display: inline-block; padding: 2px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; background: #eafaf0; color: #18b76a; }
  </style>
</head>

<body>
  <div class="app-container">
    <?php $this->load->view('user/layout/v2/user_sidebar'); ?>

    <main class="main-content">
      <?php $this->load->view('user/layout/v2/user_header'); ?>

      <div class="bm-titlebar">
        <div>
          <h2><i class="ph ph-git-merge"></i> Binary Matching History</h2>
          <div class="sub">Every matched left/right volume, the 10% bonus it produced, and how it split across your Earning and Staking wallets.</div>
        </div>
      </div>

      <div class="bm-cards">
        <div class="bm-card">
          <small>Lifetime Matching Income</small>
          <b><?= number_format($summary['lifetime'], 2) ?> <span>BMAN</span></b>
        </div>
        <div class="bm-card">
          <small>This Month</small>
          <b><?= number_format($summary['monthly'], 2) ?> <span>BMAN</span></b>
        </div>
        <div class="bm-card split">
          <div><span class="lbl">Carry Fwd. Left</span><br><b><?= number_format($summary['carry_left'], 2) ?></b></div>
          <div><span class="lbl">Carry Fwd. Right</span><br><b><?= number_format($summary['carry_right'], 2) ?></b></div>
        </div>
        <div class="bm-card">
          <small>Held at Ceiling (Pending)</small>
          <b><?= number_format($summary['pending_ceiling'], 2) ?> <span>BMAN</span></b>
        </div>
      </div>

      <div class="bm-table-wrap">
        <div class="bm-table-head">
          <h3>Matching History</h3>
          <span><?= count($history) ?> record<?= count($history) === 1 ? '' : 's' ?></span>
        </div>
        <div class="bm-scroll">
          <table class="bm-tbl">
            <thead>
              <tr>
                <th>Date</th>
                <th>Left Volume</th>
                <th>Right Volume</th>
                <th>Matched Volume</th>
                <th>Carry Forward</th>
                <th>Bonus %</th>
                <th>Earning Amount</th>
                <th>Staking Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($history)): ?>
                <?php foreach ($history as $row): ?>
                  <?php $carry = abs((float) $row['left_before'] - (float) $row['right_before']); ?>
                  <tr>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                    <td class="num"><?= number_format((float) $row['left_before'], 2) ?></td>
                    <td class="num"><?= number_format((float) $row['right_before'], 2) ?></td>
                    <td class="num"><?= number_format((float) $row['matched_volume'], 2) ?></td>
                    <td class="num"><?= number_format($carry, 2) ?></td>
                    <td class="num"><?= number_format((float) $row['total_percent'], 2) ?>%</td>
                    <td class="num"><?= number_format((float) $row['earning_amount'], 2) ?></td>
                    <td class="num"><?= number_format((float) $row['staking_amount'], 2) ?></td>
                    <td><span class="bm-pill">Credited</span></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="9" class="bm-empty">No binary matching history yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>

  <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>
</body>

</html>
