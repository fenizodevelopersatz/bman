<?php $this->load->view('admin/Layout/common_style'); ?>
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
                    <li class="breadcrumb-item text-muted">Staking</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">Binary Matching History</li>
                  </ul>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <button id="mh-run-now" class="btn btn-sm btn-primary">Run Matching Now</button>
                </div>
              </div>
            </div>
            <div id="kt_app_content" class="app-content flex-column-fluid mt-8">
              <div id="kt_app_content_container" class="app-container container-xxl">
                <?php $this->load->view('notification'); ?>

                <div class="alert alert-secondary d-flex align-items-center mb-6">
                  <i class="ki-outline ki-information fs-2 me-3"></i>
                  <div class="fw-semibold">Level-wise binary matching payouts, restricted by each recipient's own
                    staking package ceiling. Amounts above a recipient's ceiling are diverted to the
                    <a href="<?php echo base_url('admin/staking/ceiling-wallet'); ?>">Ceiling Wallet</a> instead of paid out.</div>
                </div>

                <!-- KPIs -->
                <div class="row g-5 g-xl-8 mb-6">
                  <div class="col-md-3">
                    <div class="card bg-light-primary">
                      <div class="card-body">
                        <div class="text-gray-600 fw-semibold fs-7 text-uppercase">Total Matched Volume</div>
                        <div class="text-primary fw-bold fs-2x mt-2"><?php echo number_format($matched_volume, 4); ?></div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="card bg-light-success">
                      <div class="card-body">
                        <div class="text-gray-600 fw-semibold fs-7 text-uppercase">Earning Wallet Paid (8%)</div>
                        <div class="text-success fw-bold fs-2x mt-2"><?php echo number_format($earning_paid, 4); ?></div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="card bg-light-info">
                      <div class="card-body">
                        <div class="text-gray-600 fw-semibold fs-7 text-uppercase">Staking Wallet Paid (2%)</div>
                        <div class="text-info fw-bold fs-2x mt-2"><?php echo number_format($staking_paid, 4); ?></div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="card bg-light-warning">
                      <div class="card-body">
                        <div class="text-gray-600 fw-semibold fs-7 text-uppercase">Ceiling Diverted</div>
                        <div class="text-warning fw-bold fs-2x mt-2"><?php echo number_format($ceiling_diverted, 4); ?></div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Recent engine runs -->
                <div class="card mb-6">
                  <div class="card-header pt-6"><h3 class="card-title fw-bold">Recent Engine Runs</h3></div>
                  <div class="card-body pt-3 pb-9">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-dashed fs-7 gy-4">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                          <th>Run Ref</th><th>Status</th><th>Scope</th><th class="text-end">Paid Users</th>
                          <th class="text-end">Matched</th><th class="text-end">Earning</th><th class="text-end">Staking</th>
                          <th>Started</th><th>Finished</th><th>Error</th>
                        </tr></thead>
                        <tbody class="text-gray-700 fw-semibold">
                        <?php if (empty($runs)): ?>
                          <tr><td colspan="10" class="text-center text-muted py-6">No engine runs yet.</td></tr>
                        <?php else: foreach ($runs as $r):
                          $badge = ['PENDING'=>'light','PROCESSING'=>'info','DONE'=>'success','FAILED'=>'danger'][$r['status']] ?? 'light';
                          $res = $r['result_json'] ? json_decode($r['result_json'], true) : null;
                        ?>
                          <tr>
                            <td class="fs-8"><?php echo html_escape($r['run_ref']); ?></td>
                            <td><span class="badge badge-light-<?php echo $badge; ?>"><?php echo html_escape($r['status']); ?></span></td>
                            <td class="text-muted"><?php echo html_escape($r['scope']); ?></td>
                            <td class="text-end"><?php echo $res ? (int)($res['paid_users'] ?? 0) : '—'; ?></td>
                            <td class="text-end"><?php echo $res ? number_format((float)($res['matched_volume'] ?? 0), 4) : '—'; ?></td>
                            <td class="text-end"><?php echo $res ? number_format((float)($res['earning_paid'] ?? 0), 4) : '—'; ?></td>
                            <td class="text-end"><?php echo $res ? number_format((float)($res['staking_paid'] ?? 0), 4) : '—'; ?></td>
                            <td class="fs-8 text-muted"><?php echo html_escape($r['started_at'] ?? ''); ?></td>
                            <td class="fs-8 text-muted"><?php echo html_escape($r['finished_at'] ?? ''); ?></td>
                            <td class="fs-8 text-danger"><?php echo html_escape($r['last_error'] ?? ''); ?></td>
                          </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- Payout history -->
                <div class="card">
                  <div class="card-header pt-6"><h3 class="card-title fw-bold">Payout History (latest 300)</h3></div>
                  <div class="card-body pt-3 pb-9">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-dashed fs-7 gy-4">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                          <th>#</th><th>User</th><th class="text-end">Matched Vol.</th><th class="text-end">%</th>
                          <th class="text-end">Earning</th><th class="text-end">Staking</th>
                          <th class="text-end">Left/Right Before</th><th>Run Ref</th>
                          <th>On-Chain Status</th><th>Tx Hash</th><th>When</th>
                        </tr></thead>
                        <tbody class="text-gray-700 fw-semibold">
                        <?php if (empty($payouts)): ?>
                          <tr><td colspan="11" class="text-center text-muted py-6">No matching payouts yet.</td></tr>
                        <?php else: foreach ($payouts as $p):
                          $pbadge = ['PENDING'=>'light','PROCESSING'=>'info','CONFIRMED'=>'success','FAILED'=>'danger','RETRY'=>'warning'];
                          $pStatus = $p['payout_status'] ?? null;
                        ?>
                          <tr>
                            <td class="text-muted fs-8"><?php echo (int)$p['id']; ?></td>
                            <td><?php echo html_escape(($p['username'] ?? '') ?: ('#'.$p['user_id'])); ?>
                              <div class="text-muted fs-8"><?php echo html_escape($p['referral_id'] ?? ''); ?></div></td>
                            <td class="text-end"><?php echo number_format((float)$p['matched_volume'], 4); ?></td>
                            <td class="text-end text-muted"><?php echo number_format((float)$p['total_percent'], 2); ?>%</td>
                            <td class="text-end text-success"><?php echo number_format((float)$p['earning_amount'], 4); ?></td>
                            <td class="text-end text-info"><?php echo number_format((float)$p['staking_amount'], 4); ?></td>
                            <td class="text-end fs-8 text-muted"><?php echo number_format((float)$p['left_before'], 2); ?> / <?php echo number_format((float)$p['right_before'], 2); ?></td>
                            <td class="fs-8 text-muted"><?php echo html_escape($p['run_ref'] ?? ''); ?></td>
                            <td>
                              <?php if ($pStatus): ?>
                                <span class="badge badge-light-<?php echo $pbadge[$pStatus] ?? 'light'; ?>"><?php echo html_escape($pStatus); ?></span>
                              <?php else: ?>
                                <span class="text-muted fs-8">—</span>
                              <?php endif; ?>
                            </td>
                            <td class="fs-8">
                              <?php if (!empty($p['payout_tx_hash'])): ?>
                                <a href="<?php echo $explorer_url.'/tx/'.$p['payout_tx_hash']; ?>" target="_blank" rel="noopener">
                                  <?php echo html_escape(substr($p['payout_tx_hash'], 0, 10)).'…'; ?></a>
                              <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="fs-8 text-muted"><?php echo html_escape($p['created_at']); ?></td>
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

  <script>
    var MH_RUN_NOW_URL = "<?php echo base_url('admin/staking/matching-history/run-now'); ?>";
  </script>
  <?php $this->load->view('admin/Layout/common_script'); ?>
  <script>
    (function () {
      var btn = document.getElementById('mh-run-now');
      if (!btn) return;
      btn.addEventListener('click', function () {
        btn.disabled = true;
        btn.textContent = 'Running…';
        fetch(MH_RUN_NOW_URL, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (r) {
          var claimed = r.result && r.result.claimed;
          var msg = claimed ? 'Run ' + (r.result.status || '') + ' — ' + (r.result.run_ref || '') : (r.result && r.result.reason) || 'Not run';
          if (window.Swal) Swal.fire(claimed ? 'Matching Run Complete' : 'Skipped', msg, claimed ? 'success' : 'info');
          setTimeout(function () { location.reload(); }, 900);
        }).catch(function () {
          if (window.Swal) Swal.fire('Error', 'Network error', 'error');
          btn.disabled = false; btn.textContent = 'Run Matching Now';
        });
      });
    })();
  </script>
</body>
</html>
