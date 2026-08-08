<?php
// ===================== BINARY MATCHING HISTORY (USER) =====================
// Level-wise: one row per completed binary level, plus its on-chain delivery
// status. The wallet credit and the on-chain transfer are SEPARATE things —
// the balance is spendable as soon as it is "Credited"; the On-Chain column
// only tracks whether the backing transfer has confirmed, so a queued or
// retrying transfer never means the member is missing money.
//
// Expected vars (set from Historycontroller::lendingBinaryHistory()):
// $history = staking_matching_payouts rows (+ level, raw_bonus, ceiling_applied,
//            admin_overflow, payout_status, confirmations, required_confs)
// $summary = ['lifetime','today','weekly','monthly','earning','staking','to_admin',
//             'levels_paid','next_level','next_left','next_right','next_matched',
//             'next_complete','ceiling','ceiling_ok','ceiling_status',
//             'package_stake','pending_ceiling']
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
          <div><span class="lbl">Levels Paid</span><br><b><?= (int) $summary['levels_paid'] ?></b></div>
          <div><span class="lbl">Your Cap / Level</span><br><b><?= number_format($summary['ceiling'], 2) ?></b></div>
        </div>
        <div class="bm-card">
          <small>Next: Level <?= (int) $summary['next_level'] ?><?= $summary['next_complete'] ? ' (ready)' : '' ?></small>
          <b><?= number_format($summary['next_matched'], 2) ?> <span>BMAN matched</span></b>
        </div>
      </div>

      <style>
        /* Scoped here rather than the shared sheet — this note only exists on
           this page, and the surrounding cards already carry the page theme. */
        .bm-note { margin: 14px 0 4px; padding: 12px 14px; border-radius: 10px;
                   background: rgba(127, 127, 127, .10); font-size: 13px; line-height: 1.6; }
        .bm-note b { font-weight: 700; }
      </style>
      <div class="bm-note">
        <?php if (!$summary['ceiling_ok'] && $summary['ceiling_status'] === 'no_stake'): ?>
          You need an active staking package to receive binary matching income.
          Levels that complete while you hold no eligible stake are not paid to you.
        <?php else: ?>
          Level <?= (int) $summary['next_level'] ?> pays on
          <b><?= number_format($summary['next_left'], 2) ?></b> left vs
          <b><?= number_format($summary['next_right'], 2) ?></b> right Lock Wallet volume —
          matching <b><?= number_format($summary['next_matched'], 2) ?></b> BMAN at
          10%<?php if ($summary['ceiling'] > 0): ?>, capped at
          <b><?= number_format($summary['ceiling'], 2) ?></b> BMAN for this level
          (your highest active package, <?= number_format($summary['package_stake']) ?> BMAN)<?php endif; ?>.
          <?php if (!$summary['next_complete']): ?>
            This level is not complete yet — both legs need volume at that depth.
          <?php endif; ?>
        <?php endif; ?>
        <?php if ($summary['pending_ceiling'] > 0): ?>
          <br>Previously held at ceiling: <b><?= number_format($summary['pending_ceiling'], 2) ?></b> BMAN (released by admin).
        <?php endif; ?>
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
                <th>Level</th>
                <th>Left Volume</th>
                <th>Right Volume</th>
                <th>Matched Volume</th>
                <th>Bonus %</th>
                <th>Bonus Earned</th>
                <th>Earning Amount</th>
                <th>Staking Amount</th>
                <th>Wallet</th>
                <th>On-Chain</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($history)): ?>
                <?php foreach ($history as $row): ?>
                  <?php
                    $raw      = (float) ($row['raw_bonus'] ?? 0);
                    $credited = (float) $row['earning_amount'] + (float) $row['staking_amount'];
                    $capped   = $raw > 0 && $credited + 0.00005 < $raw;
                    // The on-chain leg is separate from the wallet credit: the
                    // balance is already spendable, this column only tracks
                    // whether the matching transfer has confirmed on chain.
                    $chain = $row['payout_status'] ?? null;
                    $chainLabel = ['PENDING' => 'Queued', 'RETRY' => 'Queued', 'PROCESSING' => 'Sending',
                                   'CONFIRMED' => 'Confirmed', 'FAILED' => 'Retrying'];
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                    <td><?= $row['level'] !== null ? 'L' . (int) $row['level'] : '—' ?></td>
                    <td class="num"><?= number_format((float) $row['left_before'], 2) ?></td>
                    <td class="num"><?= number_format((float) $row['right_before'], 2) ?></td>
                    <td class="num"><?= number_format((float) $row['matched_volume'], 2) ?></td>
                    <td class="num"><?= number_format((float) $row['total_percent'], 2) ?>%</td>
                    <td class="num">
                      <?= number_format($raw > 0 ? $raw : $credited, 2) ?>
                      <?php if ($capped): ?>
                        <br><small title="Capped at your package's Group Incentive Ceiling for this level">capped to <?= number_format($credited, 2) ?></small>
                      <?php endif; ?>
                    </td>
                    <td class="num"><?= number_format((float) $row['earning_amount'], 2) ?></td>
                    <td class="num"><?= number_format((float) $row['staking_amount'], 2) ?></td>
                    <td><span class="bm-pill"><?= $credited > 0 ? 'Credited' : 'Not eligible' ?></span></td>
                    <td>
                      <?php if ($credited <= 0): ?>
                        —
                      <?php elseif ($chain === null): ?>
                        <span class="bm-pill">Queued</span>
                      <?php else: ?>
                        <span class="bm-pill"><?= htmlspecialchars($chainLabel[$chain] ?? $chain) ?></span>
                        <?php if ($chain === 'PROCESSING' && (int) $row['required_confs'] > 0): ?>
                          <br><small><?= (int) $row['confirmations'] ?>/<?= (int) $row['required_confs'] ?></small>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="11" class="bm-empty">No binary matching history yet.</td></tr>
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
