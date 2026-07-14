<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
<style>
  .roi-card{border:1px solid rgba(0,0,0,.08);border-radius:16px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.04)}
  .roi-stat{padding:1rem 1.25rem;border-top:3px solid #6366f1;border-radius:12px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.04)}
  .roi-stat .v{font-size:1.5rem;font-weight:900;color:#0b1220}
  .roi-stat .l{font-size:.78rem;color:#6b7280;font-weight:700;text-transform:uppercase}
  .tiny{font-size:.78rem;color:#64748b}
  .mono{font-family:monospace;font-size:.78rem}
  .badge-fixed{background:rgba(239,68,68,.12);color:#7f1d1d}
  .badge-regular{background:rgba(34,197,94,.12);color:#065f46}
  .badge-combo{background:rgba(168,85,247,.12);color:#6b21a8}
  .err-cell{max-width:280px;white-space:normal;font-size:.75rem;color:#b42318}
</style>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">
<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
<div class="app-page flex-column flex-column-fluid" id="kt_app_page">
<?php $this->load->view('admin/Layout/admin_topbar'); ?>
<div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
<?php $this->load->view('admin/Layout/admin_sidebar'); ?>
<div class="app-main flex-column flex-row-fluid" id="kt_app_main">
<div class="d-flex flex-column flex-column-fluid">
  <div class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-xxl d-flex flex-stack">
      <div>
        <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0"><?php echo $title; ?></h1>
        <div class="text-muted fs-7">Real distribution history from <span class="mono">roi_staking_management</span> + <span class="mono">onchain_transactions</span>. Runs: <a href="<?php echo base_url('admin/wallet/cron-lab'); ?>">Cron Lab</a>.</div>
      </div>
      <button id="retry-all" class="btn btn-sm btn-warning">Retry All Failed</button>
    </div>
  </div>
  <div class="app-content flex-column-fluid">
    <div class="app-container container-xxl">

      <div class="row g-4 mb-6">
        <?php $stats = [
          ['Total ROI Paid', number_format($summary['total_paid'],4), '#22c55e'],
          ['Remaining To Pay', number_format($summary['total_remaining'],4), '#f59e0b'],
          ['Total Gas Paid', number_format($summary['total_gas_paid'],4), '#0ea5e9'],
          ['Distribution Events', number_format($summary['distributions']), '#6366f1'],
          ['Active Records', number_format($summary['active_count']), '#0ea5e9'],
          ['Completed', number_format($summary['completed_count']), '#22c55e'],
          ['Failed / Needs Retry', number_format($summary['failed_count']), '#ef4444'],
        ]; ?>
        <?php foreach ($stats as $s): ?>
        <div class="col-6 col-md-3 col-lg">
          <div class="roi-stat" style="border-top-color:<?php echo $s[2]; ?>;">
            <div class="v"><?php echo $s[1]; ?></div>
            <div class="l"><?php echo $s[0]; ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="roi-card mb-6">
        <div class="card-header border-0 pt-6">
          <div class="card-title"><div class="fw-bold fs-4">Failed / Needs Retry (<?php echo count($failed); ?>)</div></div>
        </div>
        <div class="card-body pt-2">
          <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-7 gy-3">
              <thead>
                <tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                  <th>ID</th><th>User</th><th>Plan</th><th>Progress</th><th>Next / Maturity</th><th>Error</th><th>Updated</th><th></th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($failed)): ?>
                <tr><td colspan="8" class="text-center text-muted py-6">No failed records. 🎉</td></tr>
                <?php else: foreach ($failed as $f): ?>
                <tr id="row-<?php echo $f['id']; ?>">
                  <td class="mono">#<?php echo $f['id']; ?></td>
                  <td><?php echo html_escape($f['username'] ?: ('User #'.$f['user_id'])); ?></td>
                  <td><span class="badge badge-<?php echo $f['plan_type']; ?>"><?php echo ucfirst($f['plan_type']); ?></span></td>
                  <td><?php echo (int)$f['regular_payments_completed'].'/'.(int)$f['regular_payment_count']; ?></td>
                  <td class="tiny"><?php echo html_escape($f['next_payment_date'] ?: $f['fixed_maturity_date'] ?: '—'); ?></td>
                  <td class="err-cell"><?php echo html_escape($f['error_message']); ?></td>
                  <td class="tiny"><?php echo html_escape($f['updated_at']); ?></td>
                  <td><button class="btn btn-sm btn-light-danger retry-one" data-id="<?php echo $f['id']; ?>">Retry</button></td>
                </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="roi-card mb-6">
        <div class="card-header border-0 pt-6">
          <div class="card-title"><div class="fw-bold fs-4">Upcoming Due (Next 7 Days)</div></div>
        </div>
        <div class="card-body pt-2">
          <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-7 gy-3">
              <thead>
                <tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                  <th>ID</th><th>User</th><th>Plan</th><th>Amount Due</th><th>Due Date</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($upcoming)): ?>
                <tr><td colspan="5" class="text-center text-muted py-6">Nothing due in the next 7 days.</td></tr>
                <?php else: foreach ($upcoming as $u): ?>
                <tr>
                  <td class="mono">#<?php echo $u['id']; ?></td>
                  <td><?php echo html_escape($u['username'] ?: ('User #'.$u['user_id'])); ?></td>
                  <td><span class="badge badge-<?php echo $u['plan_type']; ?>"><?php echo ucfirst($u['plan_type']); ?></span></td>
                  <td><?php echo number_format((float)($u['regular_payment_amount'] ?: $u['fixed_payment_amount']), 4); ?></td>
                  <td class="tiny"><?php echo html_escape($u['next_payment_date']); ?></td>
                </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="roi-card">
        <div class="card-header border-0 pt-6">
          <div class="card-title"><div class="fw-bold fs-4">Distribution History</div></div>
          <div class="card-toolbar gap-2">
            <select id="f-type" class="form-select form-select-sm w-auto">
              <option value="">All Types</option>
              <option value="roi_monthly">Monthly</option>
              <option value="roi_maturity">Maturity</option>
              <option value="principal_return">Principal Return</option>
            </select>
            <input id="f-user" type="number" min="1" placeholder="User ID" class="form-control form-control-sm w-auto" style="width:120px;">
            <button id="dist-refresh" class="btn btn-sm btn-light-primary">Filter</button>
          </div>
        </div>
        <div class="card-body pt-2">
          <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-7 gy-3">
              <thead>
                <tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                  <th>Date</th><th>User</th><th>Type</th><th class="text-end">Amount (BMAN)</th><th>Wallet</th><th>Gas Fee</th><th>Tx Hash</th><th>Ref</th><th>Status</th>
                </tr>
              </thead>
              <tbody id="dist-body"><tr><td colspan="9" class="text-center text-muted py-6">Loading...</td></tr></tbody>
            </table>
          </div>
          <div class="d-flex align-items-center justify-content-between pt-4 border-top">
            <div class="text-muted fs-8">Showing <span id="dist-showing">0</span> of <span id="dist-total">0</span> | Page <span id="dist-page">1</span></div>
            <div class="gap-2 d-flex">
              <button id="dist-prev" class="btn btn-sm btn-light">← Previous</button>
              <button id="dist-next" class="btn btn-sm btn-light">Next →</button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
  <?php $this->load->view('admin/Layout/admin_footer'); ?>
</div></div></div></div>
<div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true"><i class="ki-duotone ki-arrow-up"><span class="path1"></span><span class="path2"></span></i></div>
<?php $this->load->view('admin/Layout/common_script'); ?>
<script src="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.js"></script>
<script>
(function(){
  const base = '<?php echo base_url(); ?>';
  let page = 1, limit = 25;

  async function loadDist(){
    const fd = new FormData();
    fd.append('page', page);
    const type = document.getElementById('f-type').value;
    const uid = document.getElementById('f-user').value;
    if (type) fd.append('tx_type', type);
    if (uid) fd.append('user_id', uid);
    const body = document.getElementById('dist-body');
    body.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-6">Loading...</td></tr>';
    try {
      const res = await fetch(base + 'admin/staking/roi-history/list', { method:'POST', body: fd, headers:{'X-Requested-With':'XMLHttpRequest'} });
      const j = await res.json();
      if (j.status !== 'success') { body.innerHTML = '<tr><td colspan="9" class="text-danger text-center py-6">Failed to load.</td></tr>'; return; }
      if (!j.rows.length) { body.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-6">No distributions found.</td></tr>'; document.getElementById('dist-showing').textContent = '0'; document.getElementById('dist-total').textContent = j.total; return; }
      body.innerHTML = j.rows.map(r => {
        const amt = Number(r.amount || 0).toLocaleString(undefined, { maximumFractionDigits: 8 });
        const gas = r.gas_fee_total ? Number(r.gas_fee_total).toLocaleString(undefined,{maximumFractionDigits:8}) : '—';
        return '<tr>'+
          '<td class="tiny">'+String(r.created_at||'').slice(0,16)+'</td>'+
          '<td>'+(r.username || ('User #'+r.user_id))+'</td>'+
          '<td><span class="badge badge-light">'+(r.tx_type||'—')+'</span></td>'+
          '<td class="text-end">'+amt+'</td>'+
          '<td>'+(r.wallet_type||'—')+'</td>'+
          '<td>'+gas+'</td>'+
          '<td class="mono">'+(r.tx_hash||'internal')+'</td>'+
          '<td class="mono">'+(r.reference_id||'—')+'</td>'+
          '<td>'+(r.status||'—')+'</td>'+
        '</tr>';
      }).join('');
      document.getElementById('dist-showing').textContent = j.rows.length;
      document.getElementById('dist-total').textContent = j.total;
      document.getElementById('dist-page').textContent = page;
      document.getElementById('dist-prev').disabled = page <= 1;
      document.getElementById('dist-next').disabled = (page * limit) >= j.total;
    } catch (e) {
      body.innerHTML = '<tr><td colspan="9" class="text-danger text-center py-6">'+e.message+'</td></tr>';
    }
  }

  document.getElementById('dist-refresh').addEventListener('click', ()=>{ page = 1; loadDist(); });
  document.getElementById('dist-prev').addEventListener('click', ()=>{ if (page > 1) { page--; loadDist(); } });
  document.getElementById('dist-next').addEventListener('click', ()=>{ page++; loadDist(); });

  document.querySelectorAll('.retry-one').forEach(btn => btn.addEventListener('click', async () => {
    const id = btn.dataset.id;
    btn.disabled = true; btn.textContent = 'Retrying...';
    try {
      const res = await fetch(base + 'admin/staking/roi-history/retry/' + id, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'} });
      const j = await res.json();
      alert(j.message || 'Retry executed');
      window.location.reload();
    } catch (e) {
      alert('Retry failed: ' + e.message);
      btn.disabled = false; btn.textContent = 'Retry';
    }
  }));

  document.getElementById('retry-all').addEventListener('click', async () => {
    const btn = document.getElementById('retry-all');
    btn.disabled = true; btn.textContent = 'Retrying...';
    try {
      const res = await fetch(base + 'admin/staking/roi-history/retry-all', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'} });
      const j = await res.json();
      alert(j.message || 'Retry-all executed');
      window.location.reload();
    } catch (e) {
      alert('Retry failed: ' + e.message);
      btn.disabled = false; btn.textContent = 'Retry All Failed';
    }
  });

  loadDist();
})();
</script>
</body>
</html>
