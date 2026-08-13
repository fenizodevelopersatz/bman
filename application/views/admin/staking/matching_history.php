<?php
// ============ ADMIN ▸ BINARY MATCHING ▸ DISTRIBUTION HISTORY ============
// Read-only historical ledger: one row per completed (user_id, level), shown
// exactly as it was paid. Every money figure below comes from the frozen
// columns on staking_matching_payouts — nothing is recomputed from the
// genealogy tree, which has kept moving since.
$fmt = function ($v, $d = 4) { return number_format((float)$v, $d); };
$avatar = function ($r) {
    // profile_img is the current upload field, image the older one; both live
    // under assets/images/ (same convention as Profile / Genealogy).
    $img = trim((string)($r['profile_img'] ?? '')) ?: trim((string)($r['image'] ?? ''));
    $initial = html_escape(strtoupper(substr((string)(($r['username'] ?? '') ?: 'U'), 0, 1)));
    echo '<div class="symbol symbol-35px me-3">';
    if ($img !== '') {
        echo '<img src="' . base_url('assets/images/') . rawurlencode($img) . '" alt="" class="rounded-circle" style="object-fit:cover;"'
           . ' onerror="this.outerHTML=\'<span class=&quot;symbol-label bg-light-primary text-primary fw-bold&quot;>' . $initial . '</span>\';">';
    } else {
        echo '<span class="symbol-label bg-light-primary text-primary fw-bold">' . $initial . '</span>';
    }
    echo '</div>';
};
$chainBadge = ['PENDING' => 'light', 'RETRY' => 'warning', 'PROCESSING' => 'info',
               'CONFIRMED' => 'success', 'FAILED' => 'danger'];
$this->load->view('admin/Layout/common_style');
?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
  data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
  data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
  data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">
  <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
      <?php $this->load->view('admin/Layout/admin_topbar'); ?>
      <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
        <?php $this->load->view('admin/Layout/admin_sidebar'); ?>
        <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
          <div class="d-flex flex-column flex-column-fluid">
            <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
              <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                  <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0"><?php echo $title; ?></h1>
                  <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Binary Matching</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">Distribution History</li>
                  </ul>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <a class="btn btn-sm btn-light" href="<?php echo base_url('admin/staking/matching-overflow'); ?>">Admin Overflow</a>
                  <a class="btn btn-sm btn-light" href="<?php echo base_url('admin/staking/payout-queue'); ?>">Payout Queue</a>
                </div>
              </div>
            </div>
            <div id="kt_app_content" class="app-content flex-column-fluid mt-8">
              <div id="kt_app_content_container" class="app-container container-xxl">
                <?php $this->load->view('notification'); ?>

                <div class="alert alert-secondary d-flex align-items-start mb-6">
                  <i class="ki-outline ki-information fs-2 me-3 mt-1"></i>
                  <div class="fw-semibold fs-7">
                    Completed binary matching levels, one row per member per level, reported <b>exactly as they were
                    paid</b>. Volumes, ceiling and split are read from the record written when the level closed —
                    they are never recalculated from today's genealogy tree, which has moved on since.
                    This page is <b>read-only</b>: it cannot trigger matching, credit a wallet, or send anything
                    on-chain. Manual runs live in <a href="<?php echo base_url('admin/wallet/cron-lab'); ?>">Cron Lab</a>.
                  </div>
                </div>

                <!-- Summary -->
                <div class="row g-4 mb-6">
                  <?php
                  $cards = [
                    ['Total Levels Paid',    number_format($summary['levels']),            'primary', $summary['forfeited'] . ' forfeited'],
                    ['Total Matched Volume', $fmt($summary['matched'], 2),                 'info',    'BMAN'],
                    ['Total User Bonus',     $fmt($summary['user_bonus'], 2),              'success', 'of ' . $fmt($summary['raw_bonus'], 2) . ' raw'],
                    ['Total Earning 8%',     $fmt($summary['earning'], 2),                 'success', 'Earning wallet'],
                    ['Total Staking 2%',     $fmt($summary['staking'], 2),                 'info',    'Staking wallet'],
                    ['Total Admin Overflow', $fmt($summary['overflow'], 2),                'warning', 'ceiling excess + forfeits'],
                    ['Pending On-Chain',     number_format($summary['pending_chain']),     'danger',  'credited, not yet confirmed'],
                  ];
                  foreach ($cards as $c): ?>
                    <div class="col-6 col-md-4 col-xl">
                      <div class="card bg-light-<?php echo $c[2]; ?> h-100"><div class="card-body p-4">
                        <div class="text-gray-600 fw-semibold fs-8 text-uppercase"><?php echo $c[0]; ?></div>
                        <div class="text-<?php echo $c[2]; ?> fw-bold fs-3 mt-2"><?php echo $c[1]; ?></div>
                        <div class="text-muted fs-8 mt-1"><?php echo $c[3]; ?></div>
                      </div></div>
                    </div>
                  <?php endforeach; ?>
                </div>

                <!-- Filters -->
                <form class="card mb-6" method="get" action="<?php echo base_url('admin/staking/matching-history'); ?>">
                  <div class="card-body py-5">
                    <div class="row g-3 align-items-end">
                      <div class="col-md-3">
                        <label class="form-label fs-8 text-muted">Search user</label>
                        <input type="text" name="q" value="<?php echo html_escape($filters['q']); ?>"
                               class="form-control form-control-sm form-control-solid" placeholder="username / referral / email">
                      </div>
                      <div class="col-md-1">
                        <label class="form-label fs-8 text-muted">Level</label>
                        <select name="level" class="form-select form-select-sm form-select-solid">
                          <option value="">All</option>
                          <?php foreach ($levels as $l): ?>
                            <option value="<?php echo $l; ?>" <?php echo (int)$filters['level'] === $l ? 'selected' : ''; ?>><?php echo $l; ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <label class="form-label fs-8 text-muted">Status</label>
                        <select name="status" class="form-select form-select-sm form-select-solid">
                          <?php foreach (['' => 'All', 'paid' => 'Paid', 'overflow' => 'Admin Overflow',
                                          'forfeited' => 'Forfeited (no stake)', 'pending' => 'Pending on-chain',
                                          'config' => 'Config Error (live)'] as $v => $lbl): ?>
                            <option value="<?php echo $v; ?>" <?php echo $filters['status'] === $v ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <label class="form-label fs-8 text-muted">On-Chain</label>
                        <select name="chain" class="form-select form-select-sm form-select-solid">
                          <?php foreach (['' => 'All', 'queued' => 'Queued', 'sent' => 'Sent',
                                          'confirmed' => 'Confirmed', 'failed' => 'Failed'] as $v => $lbl): ?>
                            <option value="<?php echo $v; ?>" <?php echo $filters['chain'] === $v ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <label class="form-label fs-8 text-muted">From</label>
                        <input type="date" name="from" value="<?php echo html_escape($filters['from']); ?>" class="form-control form-control-sm form-control-solid">
                      </div>
                      <div class="col-md-2">
                        <label class="form-label fs-8 text-muted">To</label>
                        <input type="date" name="to" value="<?php echo html_escape($filters['to']); ?>" class="form-control form-control-sm form-control-solid">
                      </div>
                      <div class="col-12 d-flex gap-2 mt-2">
                        <button class="btn btn-sm btn-primary" type="submit">Search</button>
                        <a class="btn btn-sm btn-light" href="<?php echo base_url('admin/staking/matching-history'); ?>">Reset</a>
                      </div>
                    </div>
                  </div>
                </form>

                <!-- Historical ledger -->
                <?php if (!$config_only): ?>
                <div class="card mb-6">
                  <div class="card-header pt-6">
                    <h3 class="card-title fw-bold">Distribution History <span class="text-muted fs-7 fw-semibold ms-2">click a row for the full calculation</span></h3>
                  </div>
                  <div class="card-body pt-3 pb-9">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-dashed fs-7 gy-4">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                          <th>Date / Time</th><th>User</th><th class="text-center">Level</th>
                          <th class="text-end">Left Vol.</th><th class="text-end">Right Vol.</th><th class="text-end">Matched</th>
                          <th class="text-end">Raw Bonus</th><th class="text-end">Ceiling</th><th class="text-end">Paid to User</th>
                          <th class="text-end">Earning 8%</th><th class="text-end">Staking 2%</th><th class="text-end">Admin Overflow</th>
                          <th>Status</th><th>On-Chain</th>
                        </tr></thead>
                        <tbody class="text-gray-700 fw-semibold" id="mh-body">
                        <?php if (empty($rows)): ?>
                          <tr><td colspan="14" class="text-center text-muted py-6">No completed levels match this filter.</td></tr>
                        <?php else: foreach ($rows as $r):
                          $userPaid  = (float)$r['earning_amount'] + (float)$r['staking_amount'];
                          $overflow  = (float)$r['admin_overflow'];
                          $forfeited = (int)$r['sponsor_eligible'] === 0;
                          $chain     = $r['chain_status'];
                        ?>
                          <tr class="mh-row cursor-pointer paged-row" data-id="<?php echo (int)$r['id']; ?>">
                            <td class="fs-8 text-muted"><?php echo html_escape($r['created_at']); ?></td>
                            <td>
                              <div class="d-flex align-items-center">
                                <?php $avatar($r); ?>
                                <div>
                                  <?php echo html_escape($r['username'] ?: ('#'.$r['user_id'])); ?>
                                  <div class="text-muted fs-8"><?php echo html_escape($r['referral_id'] ?? ''); ?></div>
                                </div>
                              </div>
                            </td>
                            <td class="text-center">
                              <?php if ($r['level'] !== null): ?>
                                <span class="badge badge-light-primary">L<?php echo (int)$r['level']; ?></span>
                              <?php else: ?><span class="badge badge-light" title="Written by the legacy carry engine">legacy</span><?php endif; ?>
                            </td>
                            <td class="text-end"><?php echo $fmt($r['left_before'], 2); ?></td>
                            <td class="text-end"><?php echo $fmt($r['right_before'], 2); ?></td>
                            <td class="text-end fw-bold"><?php echo $fmt($r['matched_volume'], 2); ?></td>
                            <td class="text-end"><?php echo $fmt($r['raw_bonus'], 2); ?>
                              <div class="text-muted fs-8"><?php echo $fmt($r['total_percent'], 2); ?>%</div></td>
                            <td class="text-end text-muted"><?php echo $fmt($r['ceiling_applied'], 2); ?>
                              <?php if (!empty($r['package_name'])): ?>
                                <div class="fs-8"><?php echo html_escape($r['package_name']); ?></div>
                              <?php endif; ?></td>
                            <td class="text-end fw-bold"><?php echo $fmt($userPaid, 2); ?></td>
                            <td class="text-end text-success"><?php echo $fmt($r['earning_amount'], 2); ?></td>
                            <td class="text-end text-info"><?php echo $fmt($r['staking_amount'], 2); ?></td>
                            <td class="text-end <?php echo $overflow > 0 ? 'text-warning fw-bold' : 'text-muted'; ?>"><?php echo $fmt($overflow, 2); ?></td>
                            <td>
                              <?php if ($forfeited): ?>
                                <span class="badge badge-light-danger" title="Sponsor held no eligible staking package when this level completed">Forfeited</span>
                              <?php elseif ($overflow > 0): ?>
                                <span class="badge badge-light-warning">Paid + Overflow</span>
                              <?php else: ?>
                                <span class="badge badge-light-success">Paid</span>
                              <?php endif; ?>
                            </td>
                            <td>
                              <?php if ($userPaid <= 0): ?>
                                <span class="text-muted fs-8">n/a</span>
                              <?php elseif ($chain === null): ?>
                                <span class="badge badge-light">Queued</span>
                              <?php else: ?>
                                <span class="badge badge-light-<?php echo $chainBadge[$chain] ?? 'light'; ?>"><?php echo html_escape($chain); ?></span>
                                <?php if ($chain === 'PROCESSING'): ?>
                                  <div class="text-muted fs-8"><?php echo (int)$r['confirmations']; ?>/<?php echo (int)$r['required_confs']; ?></div>
                                <?php endif; ?>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                      </table>
                    </div>
                    <?php if (count($rows) > 25): ?>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-4 border-top" id="mh-pager"></div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endif; ?>

                <!-- Blocked levels: LIVE diagnostic, deliberately not historical -->
                <div class="card mb-6 <?php echo empty($blocked) ? '' : 'border border-danger'; ?>">
                  <div class="card-header pt-6">
                    <h3 class="card-title fw-bold">
                      Blocked Levels — awaiting ceiling configuration
                      <?php if (!empty($blocked)): ?><span class="badge badge-danger ms-2"><?php echo count($blocked); ?></span><?php endif; ?>
                      <span class="text-muted fs-8 fw-semibold ms-2">live check, not history</span>
                    </h3>
                  </div>
                  <div class="card-body pt-3 pb-8">
                    <?php if (empty($blocked)): ?>
                      <div class="text-muted fs-7">None — every completed level resolves to a valid Group Incentive Ceiling.</div>
                    <?php else: ?>
                      <div class="alert alert-danger py-3 fs-7">
                        These levels are <b>complete and owed</b>, but the engine refused to pay them because the
                        Group Incentive Ceiling could not be resolved for the sponsor's highest eligible package.
                        Nothing was paid, nothing went to Admin, and the level was <b>left open on purpose</b> — an
                        admin mistake must not cost a member their level. That is also why they have no row in the
                        table above: a blocked level writes nothing, so it can only be found by a live check.
                        Fix the ceiling in
                        <a href="<?php echo base_url('admin/staking/rank-power'); ?>">Rank Power ▸ Group Incentive Ceiling</a>
                        and the next cron run pays them normally; this list clears itself.
                      </div>
                      <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-3">
                          <thead><tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                            <th>Sponsor</th><th class="text-center">Level</th><th>Problem</th>
                            <th class="text-end">Left / Right Vol.</th><th class="text-end">Matched</th>
                            <th class="text-end">Unpaid Bonus</th><th>Highest Package</th>
                          </tr></thead>
                          <tbody class="text-gray-700 fw-semibold">
                          <?php foreach ($blocked as $b): ?>
                            <tr>
                              <td><?php echo html_escape($b['username']); ?>
                                <div class="text-muted fs-8"><?php echo html_escape($b['referral_id']); ?></div></td>
                              <td class="text-center"><span class="badge badge-light-primary">L<?php echo (int)$b['level']; ?></span></td>
                              <td class="fs-8"><span class="badge badge-light-danger"><?php echo html_escape($b['status']); ?></span>
                                <div class="text-muted mt-1"><?php echo html_escape($b['detail']); ?></div></td>
                              <td class="text-end fs-8 text-muted"><?php echo $fmt($b['left_volume'], 2); ?> / <?php echo $fmt($b['right_volume'], 2); ?></td>
                              <td class="text-end"><?php echo $fmt($b['matched'], 2); ?></td>
                              <td class="text-end text-danger fw-bold"><?php echo $fmt($b['unpaid_bonus'], 2); ?></td>
                              <td class="fs-8 text-muted"><?php echo number_format((float)$b['stake_amount']); ?> BMAN
                                <?php if ($b['package_id']): ?><div>package #<?php echo (int)$b['package_id']; ?></div><?php endif; ?></td>
                            </tr>
                          <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- Engine run log -->
                <div class="card">
                  <div class="card-header pt-6"><h3 class="card-title fw-bold">Engine Runs (latest 25)</h3></div>
                  <div class="card-body pt-3 pb-9">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-dashed fs-7 gy-3">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                          <th>#</th><th>Run Ref</th><th>Status</th><th>Attempts</th><th>Started</th><th>Finished</th><th>Result / Error</th>
                        </tr></thead>
                        <tbody class="text-gray-700 fw-semibold">
                        <?php if (empty($runs)): ?>
                          <tr><td colspan="7" class="text-center text-muted py-6">No engine runs recorded yet.</td></tr>
                        <?php else: foreach ($runs as $run):
                          $rb = ['DONE' => 'success', 'FAILED' => 'danger', 'PROCESSING' => 'info', 'PENDING' => 'light'];
                        ?>
                          <tr>
                            <td class="text-muted fs-8"><?php echo (int)$run['id']; ?></td>
                            <td class="fs-8"><?php echo html_escape($run['run_ref']); ?></td>
                            <td><span class="badge badge-light-<?php echo $rb[$run['status']] ?? 'light'; ?>"><?php echo html_escape($run['status']); ?></span></td>
                            <td class="fs-8"><?php echo (int)$run['attempts']; ?> / <?php echo (int)$run['max_attempts']; ?></td>
                            <td class="fs-8 text-muted"><?php echo html_escape($run['started_at'] ?? ''); ?></td>
                            <td class="fs-8 text-muted"><?php echo html_escape($run['finished_at'] ?? ''); ?></td>
                            <td class="fs-8 text-muted" style="max-width:420px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                                title="<?php echo html_escape($run['result_json'] ?: ($run['last_error'] ?? '')); ?>">
                              <?php echo html_escape($run['result_json'] ?: ($run['last_error'] ?? '')); ?></td>
                          </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Detail drawer -->
  <div class="modal fade" id="mh-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
      <div class="modal-content">
        <div class="modal-header py-4">
          <h3 class="modal-title fs-5 fw-bold" id="mh-modal-title">Binary Matching</h3>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="mh-modal-body">Loading…</div>
      </div>
    </div>
  </div>

  <script>
    var MH_DETAIL_URL = "<?php echo base_url('admin/staking/matching-history/detail/'); ?>";
  </script>
  <?php $this->load->view('admin/Layout/common_script'); ?>
  <script>
    (function () {
      var body = document.getElementById('mh-modal-body');
      var titleEl = document.getElementById('mh-modal-title');
      var modal = null;

      function n(v, d) { return Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: d, maximumFractionDigits: d }); }
      function esc(s) { var e = document.createElement('div'); e.textContent = s == null ? '' : s; return e.innerHTML; }
      function line(k, v, cls) {
        return '<div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted fs-7">' + k +
               '</span><span class="fw-bold ' + (cls || '') + '">' + v + '</span></div>';
      }
      function head(t) { return '<div class="text-gray-500 fw-bold fs-8 text-uppercase mt-5 mb-2">' + t + '</div>'; }

      function render(rec, explorer) {
        var userPaid = Number(rec.earning_amount) + Number(rec.staking_amount);
        var lvl = rec.level === null ? 'legacy' : ('Level ' + rec.level);
        titleEl.textContent = 'Binary Matching #' + rec.id;

        var h = '';
        h += line('Member', esc(rec.username || ('#' + rec.user_id)) +
                  '<div class="text-muted fs-8 fw-semibold">' + esc(rec.referral_id || '') + '</div>');
        h += line('Level', lvl);
        h += line('Completed At', esc(rec.created_at));
        h += line('Run Reference', '<span class="font-monospace fs-8">' + esc(rec.run_ref || '—') + '</span>');

        h += head('Volume (as recorded when the level closed)');
        h += line('Left Leg', n(rec.left_before, 2) + ' BMAN');
        h += line('Right Leg', n(rec.right_before, 2) + ' BMAN');
        h += line('Matched Volume', n(rec.matched_volume, 2) + ' BMAN');

        h += head('Bonus Calculation');
        h += line(n(rec.matched_volume, 2) + ' × ' + n(rec.total_percent, 2) + '%',
                  n(rec.raw_bonus, 4) + ' BMAN');

        h += head('Ceiling');
        h += line('Highest Package', rec.package_name
              ? esc(rec.package_name) + ' (' + n(rec.package_stake, 0) + ' BMAN)'
              : '<span class="text-muted">not recorded</span>');
        h += line('Ceiling Applied', n(rec.ceiling_applied, 2) + ' BMAN');
        if (rec.ceiling_drifted) {
          h += '<div class="alert alert-warning py-2 mt-2 fs-8 mb-0">That package is configured at ' +
               n(rec.package_ceiling_now, 2) + ' BMAN <b>today</b>. The figure above is the ceiling that ' +
               'actually applied when this level was paid — history is not restated when configuration changes.</div>';
        }

        h += head('Distribution');
        h += line('Earning Wallet 8%', n(rec.earning_amount, 4) + ' BMAN', 'text-success');
        h += line('Staking Wallet 2%', n(rec.staking_amount, 4) + ' BMAN', 'text-info');
        h += line('User Total', n(userPaid, 4) + ' BMAN');
        h += line('Admin Overflow', n(rec.admin_overflow, 4) + ' BMAN',
                  Number(rec.admin_overflow) > 0 ? 'text-warning' : 'text-muted');

        h += head('Status');
        h += line('Level', '<span class="badge badge-light-success">COMPLETED</span>');
        h += line('Eligibility', Number(rec.sponsor_eligible) === 1
              ? '<span class="badge badge-light-success">ELIGIBLE</span>'
              : '<span class="badge badge-light-danger">NO ELIGIBLE STAKE — FORFEITED</span>');
        h += line('Internal Wallet', userPaid > 0
              ? '<span class="badge badge-light-success">CREDITED</span>'
              : '<span class="badge badge-light">NOTHING TO CREDIT</span>');
        var chain = rec.chain_status;
        h += line('On-Chain', userPaid <= 0 ? '<span class="text-muted fs-8">n/a</span>'
              : (chain ? '<span class="badge badge-light-info">' + esc(chain) + '</span>'
                       : '<span class="badge badge-light">QUEUED</span>'));
        if (rec.tx_hash) {
          h += line('Tx Hash', '<a href="' + explorer + '/tx/' + esc(rec.tx_hash) + '" target="_blank" rel="noopener" class="fs-8 font-monospace">' +
                    esc(String(rec.tx_hash).slice(0, 18)) + '…</a>');
          h += line('Confirmations', (rec.confirmations || 0) + ' / ' + (rec.required_confs || 0));
        }
        if (rec.chain_error) h += '<div class="alert alert-danger py-2 mt-2 fs-8 mb-0">' + esc(rec.chain_error) + '</div>';

        if (rec.wallet_rows && rec.wallet_rows.length) {
          h += head('Wallet Ledger References');
          rec.wallet_rows.forEach(function (w) {
            h += line('#' + w.id + ' · ' + esc(w.wallet_type),
                      '+' + n(w.credit, 4) + ' <span class="text-muted fs-8">bal ' + n(w.balance_after, 4) + '</span>');
          });
        }
        if (rec.admin_rows && rec.admin_rows.length) {
          h += head('Admin Overflow Ledger');
          rec.admin_rows.forEach(function (a) {
            h += line('#' + a.id, '+' + n(a.credit, 4) + ' <span class="text-muted fs-8">bal ' + n(a.balance_after, 4) + '</span>');
          });
        }
        body.innerHTML = h;
      }

      document.querySelectorAll('.mh-row').forEach(function (tr) {
        tr.addEventListener('click', function () {
          body.innerHTML = 'Loading…';
          titleEl.textContent = 'Binary Matching';
          if (!modal && window.bootstrap) modal = new bootstrap.Modal(document.getElementById('mh-modal'));
          if (modal) modal.show();
          fetch(MH_DETAIL_URL + this.dataset.id, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin'
          }).then(function (r) { return r.json(); })
            .then(function (r) {
              if (r.status !== 'success') { body.innerHTML = '<span class="text-danger">' + esc(r.message || 'Not found') + '</span>'; return; }
              render(r.record, r.explorer_url);
            })
            .catch(function () { body.innerHTML = '<span class="text-danger">Network error.</span>'; });
        });
      });

      /* Client-side pager — rows are all rendered server-side already (hard
         capped at 300), so pagination here just shows/hides .paged-row
         elements in pages rather than re-fetching. Attached listeners on
         .mh-row above stay bound regardless of display:none, so paging
         never breaks the detail-drawer click. */
      (function paginateStaticTable(tbodyId, pagerId, pageSize) {
        var tbody = document.getElementById(tbodyId);
        var pager = document.getElementById(pagerId);
        if (!tbody || !pager) return;
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr.paged-row'));
        if (!rows.length) return;
        var cur = 1;
        var totalPages = Math.max(1, Math.ceil(rows.length / pageSize));

        function render() {
          rows.forEach(function (tr, i) {
            var p = Math.floor(i / pageSize) + 1;
            tr.style.display = (p === cur) ? '' : 'none';
          });
          var start = (cur - 1) * pageSize + 1;
          var end = Math.min(rows.length, cur * pageSize);
          pager.innerHTML =
            '<div class="text-muted fs-8">Showing ' + start + '–' + end + ' of ' + rows.length + '</div>' +
            '<div class="gap-2 d-flex">' +
            '<button class="btn btn-sm btn-light" id="' + pagerId + '-prev"' + (cur <= 1 ? ' disabled' : '') + '>← Previous</button>' +
            '<span class="align-self-center fs-8 text-muted">Page ' + cur + ' / ' + totalPages + '</span>' +
            '<button class="btn btn-sm btn-light" id="' + pagerId + '-next"' + (cur >= totalPages ? ' disabled' : '') + '>Next →</button>' +
            '</div>';
          var prevBtn = document.getElementById(pagerId + '-prev');
          var nextBtn = document.getElementById(pagerId + '-next');
          if (prevBtn) prevBtn.addEventListener('click', function () { if (cur > 1) { cur--; render(); } });
          if (nextBtn) nextBtn.addEventListener('click', function () { if (cur < totalPages) { cur++; render(); } });
        }
        render();
      })('mh-body', 'mh-pager', 25);
    })();
  </script>
</body>
</html>
