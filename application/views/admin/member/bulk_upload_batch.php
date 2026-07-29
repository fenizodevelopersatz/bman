<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
<style>
  .bmu-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .8rem; }
</style>
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
              <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack flex-wrap gap-3">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                  <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0"><?php echo html_escape($batch['ref']); ?></h1>
                  <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted"><a href="<?php echo base_url(); ?>admin/member/bulk-upload" class="text-muted text-hover-primary">Bulk Member Upload</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted"><?php echo html_escape($batch['original_name']); ?></li>
                  </ul>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <a href="<?php echo base_url(); ?>admin/member/bulk-upload/export/<?php echo (int)$batch['id']; ?>" class="btn btn-sm btn-light-primary">
                    <i class="ki-outline ki-exit-down fs-4"></i> Export Result
                  </a>
                  <a href="<?php echo base_url(); ?>admin/member/bulk-upload" class="btn btn-sm btn-light">Back</a>
                </div>
              </div>
            </div>

            <div id="kt_app_content" class="app-content flex-column-fluid mt-10">
              <div id="kt_app_content_container" class="app-container container-xxl">

                <!-- Summary -->
                <div class="row g-5 mb-5">
                  <?php
                    $statusMap = ['completed' => 'success', 'failed' => 'danger', 'staged' => 'warning', 'importing' => 'info', 'cancelled' => 'secondary'];
                    $tiles = [
                      ['Total rows',   (int)$batch['total_rows'],    'text-gray-800'],
                      ['Imported',     (int)$batch['imported_rows'], 'text-success'],
                      ['Invalid',      (int)$batch['invalid_rows'],  'text-warning'],
                      ['Failed',       (int)$batch['failed_rows'],   'text-danger'],
                      ['BMAN queued',  (int)$batch['bman_queued'],   'text-primary'],
                    ];
                  ?>
                  <?php foreach ($tiles as $t): ?>
                  <div class="col-6 col-md-4 col-xl">
                    <div class="card h-100"><div class="card-body p-5">
                      <div class="fs-2hx fw-bold <?php echo $t[2]; ?>"><?php echo $t[1]; ?></div>
                      <div class="fs-8 text-muted text-uppercase mt-1"><?php echo $t[0]; ?></div>
                    </div></div>
                  </div>
                  <?php endforeach; ?>
                  <div class="col-6 col-md-4 col-xl">
                    <div class="card h-100"><div class="card-body p-5">
                      <span class="badge badge-light-<?php echo $statusMap[$batch['status']] ?? 'secondary'; ?> fs-7"><?php echo strtoupper($batch['status']); ?></span>
                      <div class="fs-8 text-muted text-uppercase mt-2">Batch status</div>
                      <div class="fs-8 text-muted mt-1"><?php echo html_escape($batch['imported_at'] ?: $batch['created_at']); ?></div>
                    </div></div>
                  </div>
                </div>

                <?php if ((int)$batch['bman_queued'] > 0): ?>
                <div class="alert alert-warning d-flex align-items-start p-5 mb-5">
                  <i class="ki-outline ki-time fs-2hx text-warning me-4 mt-1"></i>
                  <div class="d-flex flex-column">
                    <span class="fw-bold">BMAN transfers are queued, not yet sent.</span>
                    <span class="fs-7 text-gray-700">
                      The <span class="bmu-mono">member-bulk-bman-cron</span> sends them from the Treasury wallet on its next pass.
                      Until it runs — and until the cron is both <b>enabled</b> and out of <b>dry-run</b> — no real BMAN moves.
                    </span>
                  </div>
                </div>
                <?php endif; ?>

                <!-- Rows -->
                <div class="card mb-5">
                  <div class="card-header border-transparent pt-5">
                    <h3 class="card-title fw-bold">Rows</h3>
                  </div>
                  <div class="card-body pt-3 pb-9">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-dashed fs-7 gy-4">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                          <th>#</th><th>Member</th><th>Sponsor ref</th><th>Leg</th><th>Wallet address</th>
                          <th class="text-end">BMAN</th><th>Row</th><th>BMAN send</th><th>Message</th><th></th>
                        </tr></thead>
                        <tbody class="text-gray-700 fw-semibold">
                          <?php foreach ($rows as $r): ?>
                          <?php
                            $rowTone = ['imported' => 'success', 'failed' => 'danger', 'invalid' => 'warning', 'valid' => 'info', 'skipped' => 'secondary'][$r['status']] ?? 'secondary';
                            $bmanTone = ['completed' => 'success', 'failed' => 'danger', 'pending' => 'warning', 'processing' => 'info', 'none' => 'secondary'][$r['bman_status']] ?? 'secondary';
                          ?>
                          <tr>
                            <td class="text-muted"><?php echo (int)$r['row_number']; ?></td>
                            <td>
                              <?php if ($r['user_id']): ?>
                                <a href="<?php echo base_url(); ?>view-user/<?php echo (int)$r['user_id']; ?>" class="text-gray-800 text-hover-primary fw-bold"><?php echo html_escape($r['username']); ?></a>
                              <?php else: ?>
                                <span class="fw-bold"><?php echo html_escape($r['username']); ?></span>
                              <?php endif; ?>
                              <div class="text-muted fs-8"><?php echo html_escape($r['email']); ?></div>
                              <?php if ($r['referral_id']): ?><div class="bmu-mono text-primary fs-8"><?php echo html_escape($r['referral_id']); ?></div><?php endif; ?>
                            </td>
                            <td class="bmu-mono"><?php echo html_escape($r['reference_id']); ?></td>
                            <td><span class="badge badge-light fs-9"><?php echo html_escape($r['leg']); ?></span></td>
                            <td>
                              <?php if ($r['wallet_address']): ?>
                                <span class="bmu-mono" title="<?php echo html_escape($r['wallet_address']); ?>"><?php echo html_escape(substr($r['wallet_address'], 0, 10).'…'.substr($r['wallet_address'], -6)); ?></span>
                              <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                            </td>
                            <td class="text-end"><?php echo bccomp((string)$r['bman_amount'], '0', 8) > 0 ? rtrim(rtrim(number_format((float)$r['bman_amount'], 8, '.', ''), '0'), '.') : '—'; ?></td>
                            <td><span class="badge badge-light-<?php echo $rowTone; ?>"><?php echo strtoupper($r['status']); ?></span></td>
                            <td>
                              <span class="badge badge-light-<?php echo $bmanTone; ?>"><?php echo strtoupper($r['bman_status']); ?></span>
                              <?php if ($r['bman_tx_hash']): ?>
                                <div class="bmu-mono fs-9 text-muted" title="<?php echo html_escape($r['bman_tx_hash']); ?>"><?php echo html_escape(substr($r['bman_tx_hash'], 0, 16).'…'); ?></div>
                              <?php endif; ?>
                            </td>
                            <td class="fs-8 <?php echo ($r['error_message'] || $r['bman_error']) ? 'text-danger' : 'text-muted'; ?>">
                              <?php echo html_escape($r['error_message'] ?: ($r['bman_error'] ?: '—')); ?>
                            </td>
                            <td class="text-end">
                              <?php if ($r['bman_status'] === 'failed'): ?>
                                <button type="button" class="btn btn-sm btn-light-warning py-1 px-3 fs-8 bmu-requeue" data-row="<?php echo (int)$r['id']; ?>">Re-queue</button>
                              <?php endif; ?>
                            </td>
                          </tr>
                          <?php endforeach; ?>
                          <?php if (empty($rows)): ?><tr><td colspan="10" class="text-muted">This batch has no rows.</td></tr><?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
          <?php $this->load->view('admin/Layout/admin_footer'); ?>
        </div>
      </div>
    </div>
  </div>
  <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true"><i class="ki-duotone ki-arrow-up"><span class="path1"></span><span class="path2"></span></i></div>
  <?php $this->load->view('admin/Layout/common_script'); ?>
  <script src="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.js"></script>
  <script>
  (function () {
    const base = '<?php echo base_url(); ?>';
    function toast(m, ok) {
      if (window.Swal) Swal.fire({ text: m, icon: ok ? 'success' : 'error', buttonsStyling: false,
        confirmButtonText: 'Ok', customClass: { confirmButton: 'btn btn-primary' } });
      else alert(m);
    }
    document.querySelectorAll('.bmu-requeue').forEach(btn => {
      btn.addEventListener('click', async () => {
        btn.disabled = true;
        const fd = new FormData(); fd.set('row_id', btn.dataset.row);
        const r = await fetch(base + 'admin/member/bulk-upload/requeue', {
          method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        let j = {}; try { j = await r.json(); } catch (_) {}
        const ok = r.ok && j.status === 'success';
        toast(j.message || (ok ? 'Queued.' : 'Failed.'), ok);
        if (ok) setTimeout(() => location.reload(), 900); else btn.disabled = false;
      });
    });
  })();
  </script>
</body>
</html>
