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
                    <li class="breadcrumb-item text-muted">Payout Queue</li>
                  </ul>
                </div>
              </div>
            </div>
            <div id="kt_app_content" class="app-content flex-column-fluid mt-8">
              <div id="kt_app_content_container" class="app-container container-xxl">
                <?php $this->load->view('notification'); ?>

                <div class="alert alert-secondary d-flex align-items-center mb-6">
                  <i class="ki-outline ki-information fs-2 me-3"></i>
                  <div class="fw-semibold">On-chain transfers for binary matching payouts. Every broadcast is
                    gated on a treasury BNB (gas) + BMAN balance precheck; a shortfall or broadcast failure lands
                    a row here as <b>RETRY</b>/<b>FAILED</b> for manual retry. Retrying never re-credits any
                    wallet — the internal ledger credit already happened when the matching engine ran.</div>
                </div>

                <!-- KPIs -->
                <div class="row g-5 g-xl-8 mb-6">
                  <div class="col-md-3">
                    <div class="card bg-light-primary">
                      <div class="card-body">
                        <div class="text-gray-600 fw-semibold fs-7 text-uppercase">Pending</div>
                        <div class="text-primary fw-bold fs-2x mt-2"><?php echo (int)$summary['by_status']['PENDING']; ?></div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="card bg-light-info">
                      <div class="card-body">
                        <div class="text-gray-600 fw-semibold fs-7 text-uppercase">Processing</div>
                        <div class="text-info fw-bold fs-2x mt-2"><?php echo (int)$summary['by_status']['PROCESSING']; ?></div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="card <?php echo $summary['needs_attention'] > 0 ? 'bg-light-danger' : 'bg-light-success'; ?>">
                      <div class="card-body">
                        <div class="text-gray-600 fw-semibold fs-7 text-uppercase">Needs Attention</div>
                        <div class="<?php echo $summary['needs_attention'] > 0 ? 'text-danger' : 'text-success'; ?> fw-bold fs-2x mt-2">
                          <?php echo (int)$summary['needs_attention']; ?></div>
                        <div class="text-muted fs-8">Failed: <?php echo (int)$summary['by_status']['FAILED']; ?> · Retry: <?php echo (int)$summary['by_status']['RETRY']; ?></div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="card bg-light-success">
                      <div class="card-body">
                        <div class="text-gray-600 fw-semibold fs-7 text-uppercase">Confirmed Today</div>
                        <div class="text-success fw-bold fs-2x mt-2"><?php echo number_format($summary['confirmed_today_amt'], 4); ?></div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Filter -->
                <div class="d-flex align-items-center gap-2 mb-4">
                  <?php $statuses = ['' => 'All', 'PENDING' => 'Pending', 'PROCESSING' => 'Processing', 'CONFIRMED' => 'Confirmed', 'RETRY' => 'Retry', 'FAILED' => 'Failed'];
                  foreach ($statuses as $val => $label):
                    $active = ($status_filter ?: '') === $val; ?>
                    <a href="<?php echo base_url('admin/staking/payout-queue') . ($val ? '?status='.$val : ''); ?>"
                       class="btn btn-sm <?php echo $active ? 'btn-primary' : 'btn-light'; ?>"><?php echo $label; ?></a>
                  <?php endforeach; ?>
                </div>

                <!-- Queue table -->
                <div class="card">
                  <div class="card-header pt-6"><h3 class="card-title fw-bold">Payouts (latest 300)</h3></div>
                  <div class="card-body pt-3 pb-9">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-dashed fs-7 gy-4">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                          <th>#</th><th>User</th><th class="text-end">Amount</th><th>To Address</th>
                          <th>Status</th><th>Tx Hash</th><th class="text-end">Confirmations</th>
                          <th class="text-end">Retries</th><th>Last Error</th><th>Last Attempt</th><th>Actions</th>
                        </tr></thead>
                        <tbody class="text-gray-700 fw-semibold">
                        <?php if (empty($rows)): ?>
                          <tr><td colspan="11" class="text-center text-muted py-6">No payouts yet.</td></tr>
                        <?php else: foreach ($rows as $r):
                          $badge = ['PENDING'=>'light','PROCESSING'=>'info','CONFIRMED'=>'success','FAILED'=>'danger','RETRY'=>'warning'][$r['status']] ?? 'light';
                          $canRetry = in_array($r['status'], ['FAILED', 'RETRY'], true);
                        ?>
                          <tr>
                            <td class="text-muted fs-8"><?php echo (int)$r['id']; ?></td>
                            <td><?php echo html_escape(($r['username'] ?? '') ?: ('#'.$r['user_id'])); ?>
                              <div class="text-muted fs-8"><?php echo html_escape($r['referral_id'] ?? ''); ?></div></td>
                            <td class="text-end fw-bold"><?php echo number_format((float)$r['amount'], 4); ?> <span class="text-muted fs-8"><?php echo html_escape($r['token']); ?></span></td>
                            <td class="fs-8 text-muted" title="<?php echo html_escape($r['to_address']); ?>">
                              <?php echo html_escape(substr($r['to_address'], 0, 10)).'…'.html_escape(substr($r['to_address'], -6)); ?></td>
                            <td><span class="badge badge-light-<?php echo $badge; ?>"><?php echo html_escape($r['status']); ?></span></td>
                            <td class="fs-8">
                              <?php if (!empty($r['tx_hash'])): ?>
                                <a href="<?php echo $explorer_url.'/tx/'.$r['tx_hash']; ?>" target="_blank" rel="noopener">
                                  <?php echo html_escape(substr($r['tx_hash'], 0, 10)).'…'; ?></a>
                              <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="text-end fs-8"><?php echo (int)$r['confirmations']; ?> / <?php echo (int)$r['required_confs']; ?></td>
                            <td class="text-end fs-8"><?php echo (int)$r['retry_count']; ?> / <?php echo (int)$r['max_retries']; ?></td>
                            <td class="fs-8 text-danger" style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                                title="<?php echo html_escape($r['last_error'] ?? ''); ?>"><?php echo html_escape($r['last_error'] ?? ''); ?></td>
                            <td class="fs-8 text-muted"><?php echo html_escape($r['last_attempt_at'] ?? ''); ?></td>
                            <td>
                              <button class="btn btn-sm btn-light-warning pq-retry" data-id="<?php echo (int)$r['id']; ?>" <?php echo $canRetry ? '' : 'disabled'; ?>>Retry</button>
                            </td>
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
    var PQ_RETRY_URL_BASE = "<?php echo base_url('admin/staking/payout-queue/retry/'); ?>";
  </script>
  <?php $this->load->view('admin/Layout/common_script'); ?>
  <script>
    (function () {
      function post(url, done) {
        fetch(url, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(done).catch(function () { done({ status: 'error', message: 'Network error' }); });
      }
      document.querySelectorAll('.pq-retry').forEach(function (b) {
        b.addEventListener('click', function () {
          var id = this.dataset.id;
          post(PQ_RETRY_URL_BASE + id, function (r) {
            if (window.Swal) Swal.fire(r.status === 'success' ? 'Queued for Retry' : 'Error', r.message || '', r.status === 'success' ? 'success' : 'error');
            if (r.status === 'success') setTimeout(function () { location.reload(); }, 900);
          });
        });
      });
    })();
  </script>
</body>
</html>
